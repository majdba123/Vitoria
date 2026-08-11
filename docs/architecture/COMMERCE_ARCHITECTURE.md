# Vetora — Commerce Architecture

Scope: what is **implemented and tested** as of Phase C (payments, returns,
refunds — §12–14 below — added on top of the Phase B cart/checkout/lifecycle
work in §1–11). Deferred work is listed in
[IMPLEMENTATION_DECISIONS.md](IMPLEMENTATION_DECISIONS.md) and is not described here.

---

## 1. The trust boundary

One rule governs the whole commerce path:

> The frontend sends **intent**. The backend computes **truth**.

Concretely, no endpoint accepts a price, a discount, a subtotal, a grand total,
or an availability claim. `cart_items` has columns for `product_id` and
`quantity` and nothing else — there is deliberately no `unit_price` column, so a
stale or hostile client has nowhere to pin an old price. Every monetary figure is
recomputed from `products.price` and the product's active discount on each read
and again inside the checkout transaction.

Tested by `ServerCartTest::it ignores any price the client tries to send`.

---

## 2. Stock

`products.quantity` is the only stock mechanism (decision D1). There is no
warehouse, batch, lot, reservation, movement or ledger table.

Two operations touch it, and only two:

**Consume** — `CheckoutService::consumeStock()`

```sql
UPDATE products SET quantity = quantity - :qty
 WHERE id = :id AND quantity >= :qty AND is_active = 1 AND status = 'approved'
```

The `quantity >= :qty` guard is inside the statement. Zero affected rows means a
concurrent checkout won the race, and the whole transaction rolls back with a 422.
This is correct on SQLite — where `lockForUpdate()` is a no-op — as well as on
MySQL and Postgres, so it survives a driver change.

**Restore** — `OrderCancellationService::restoreStockOnce()`

```sql
UPDATE orders SET stock_restored_at = NOW() WHERE id = :id AND stock_restored_at IS NULL
```

Exactly one caller can win that claim. Everyone else short-circuits and returns
`false` before touching a product row. The idempotency comes from this claim, not
from the order's status — which is why calling it five times in a row is safe.

Tested by `restores stock exactly once no matter how many times restoration is
invoked`, `never lets stock go negative`, and `does not decrement stock twice`.

---

## 3. Cart

| | |
|---|---|
| Tables | `carts`, `cart_items` |
| Guest identity | web session (`vetora_cart_token`), never a client-supplied id |
| User identity | `carts.user_id`, `unique` — one cart per account |
| Line identity | `unique(cart_id, product_id)` — re-adding updates, never duplicates |
| Line cap | `CartService::MAX_LINE_QUANTITY` = 999 |

**Merge at login.** `CartService::mergeGuestCartIntoUser()` runs from
`AuthController::login()` after `session()->regenerate()` (which rotates the id
but preserves the payload, so the guest token is still readable). If the account
has no cart, the guest cart is adopted wholesale. Otherwise quantities are summed
and then **clamped to available stock**, so a merge can never produce a line that
will only fail later at checkout. Out-of-stock guest lines are dropped.

**Reconciliation.** `CartService::reconcile()` runs on every cart read and again
at the start of checkout. It removes products that became inactive, unapproved,
vendor-suspended or out of stock, and clamps lines that now exceed availability.
The customer gets a notice rather than a hard failure.

---

## 4. Addresses and the order snapshot

`user_addresses` is a customer convenience list with labels reflecting Vetora's
actual buyers: `home`, `work`, `farm`, `clinic`, `pharmacy`, `other`.

Orders **do not reference it**. At creation, `UserAddress::toOrderSnapshot()`
copies the delivery details into the order's own `ship_*` columns. Editing or
soft-deleting an address afterwards cannot alter a historical order (decision D5).

Tested by `snapshots the delivery address onto the order and never reads it back`.

---

## 5. Checkout

`POST /api/checkout` — body is exactly `{address_id, payment_method}`.

Sequence:

1. Validate `payment_method` against `CheckoutService::availablePaymentMethods()`
   — currently `['cash']` only. COD is the only method the platform configures
   and no gateway is stubbed (decision D9).
2. Authorize the address through `UserAddressPolicy` — a foreign id returns 403.
3. Reconcile the cart.
4. Price it server-side; reject an empty cart.
5. Re-resolve the coupon against the live subtotal and the buying user.
6. **In one transaction:** group lines by vendor, create one order per vendor,
   consume stock atomically per line, write items, record the opening status
   history row, claim the coupon, clear the cart.

**Multi-vendor split** is preserved from the previous implementation: a cart
spanning three vendors produces three `orders` rows. One order-level coupon
discount is allocated across them in proportion to vendor subtotal, with the
rounding remainder assigned to the last vendor so the parts sum exactly to the
whole. One coupon *use* is consumed for the checkout as a whole.

**Legacy endpoint.** `POST /api/orders/checkout` still accepts the old
client-supplied `items[]` array, because shipped mobile clients call it. It now
writes those items into the caller's server cart and delegates to the same
`CheckoutService`, so there remains exactly one code path that decrements stock.
Orders created this way carry no address snapshot. It is marked deprecated in
`routes/api.php`.

### Money

| Column | Meaning |
|---|---|
| `subtotal_amount` | sum of line totals, post product-discount |
| `coupon_discount_amount` | this vendor's allocated share |
| `shipping_total` | structurally present, **0** — shipping is deferred |
| `tax_total` | structurally present, **0** — no VAT rate is invented (D7) |
| `grand_total` | subtotal − discount + shipping + tax, floored at 0 |
| `total_amount` | retained, mirrors `grand_total`, for backward compatibility |
| `currency` | persisted ISO code, default `SYP`, no FX (D6) |

---

## 6. Order lifecycle

States and legal transitions live in `Order::TRANSITIONS` — one declaration, and
the only source of truth:

```
pending ──▶ confirmed ──▶ preparing ──▶ shipped ──▶ out_for_delivery ──▶ completed
   │            │             │
   └────────────┴─────────────┴──▶ cancelled
```

`completed` is retained as the terminal success state rather than renamed to
`delivered`, so historical orders stay valid (decision D4). `ready` and pickup
states are not implemented — no pickup business model exists in the repository.

**Nothing writes `status` directly.** `OrderStatusService::transition()` is the
only path, and it enforces two independent gates:

1. **Actor permission** — customers may only cancel; vendors and admins drive
   fulfilment. A customer calling for `confirmed` is rejected even though the
   state machine allows that edge.
2. **State machine** — `pending → shipped` is rejected for everyone.

The write itself is conditional (`WHERE status = :from`), so a concurrent update
loses cleanly with a conflict message instead of silently overwriting.

Every transition writes an `order_status_histories` row: previous status, new
status, actor, actor type, reason, notes, timestamp. That table drives the
customer, vendor and admin timelines.

---

## 7. Cancellation

Allowed only from `pending`, `confirmed`, `preparing` — states where the goods
have not left the vendor. Reason is validated against `Order::CANCEL_REASONS`;
an arbitrary string is rejected.

The order records `cancelled_at`, `cancelled_by_user_id`, `cancellation_reason`,
`cancellation_notes`, and stock is restored through the exactly-once claim in §2.

Vendor and customer cancellation share one implementation. The previous code
duplicated the restore loop in `Api\OrderController` and `Api\Vendor\OrderController`,
and both copies carried the double-restore race (audit R1).

---

## 8. Coupons

Server-side only. `CouponService` validates active flag, status, window, global
usage limit, minimum subtotal, maximum discount cap, per-user limit, and
first-order-only. A failing rule returns `null` rather than naming which rule
failed, so the endpoint cannot be used to probe coupon configuration.

`coupon_redemptions` records who redeemed what on which order — the fact that
makes per-user limits expressible at all (audit R3). The global cap is enforced
by a conditional `UPDATE ... WHERE used_count < usage_limit`; zero affected rows
aborts the checkout.

---

## 9. Authorization

`OrderPolicy` and `UserAddressPolicy` enforce per-record ownership. Controllers
call `$this->authorize(...)`; the base `Controller` now uses `AuthorizesRequests`.
Existing type middleware (`admin`, `vendor`, `employee`, `syndicate`) remains the
coarse outer gate. Table-driven RBAC is deferred (decision D3).

Vendor isolation is tested directly: a vendor calling status or cancel on another
vendor's order gets 403, and a customer reading another customer's order gets 403.

---

## 10. Endpoints added

| Method | Path | Auth |
|---|---|---|
| GET | `/api/cart` | guest or user |
| POST | `/api/cart/items` | guest or user |
| PATCH | `/api/cart/items` | guest or user |
| DELETE | `/api/cart/items/{productId}` | guest or user |
| DELETE | `/api/cart` | guest or user |
| POST · DELETE | `/api/cart/coupon` | guest or user |
| GET · POST | `/api/addresses` | user |
| PATCH · DELETE | `/api/addresses/{address}` | user |
| PATCH | `/api/addresses/{address}/default` | user |
| GET | `/api/checkout/summary` | user |
| POST | `/api/checkout` | user |
| PATCH | `/api/vendor/orders/{orderId}/status` | vendor |
| GET | `/checkout` (web page) | user |
| POST | `/api/orders/{orderId}/returns` | user |
| GET | `/api/returns`, `/api/returns/{id}` | user |
| PATCH | `/api/returns/{id}/cancel` | user |
| GET · PATCH | `/api/vendor/returns`, `/api/vendor/returns/{id}/status` | vendor |
| POST | `/api/vendor/returns/{id}/refund` | vendor |
| GET · PATCH | `/api/admin/returns`, `/api/admin/returns/{id}/status` | admin |
| GET · POST · PATCH | `/api/admin/refunds`, `/api/admin/refunds/{id}/complete`, `/cancel` | admin |

---

## 11. Storefront

`resources/js/cart.js` replaced the inline localStorage cart that previously
lived in `layouts/app.blade.php`, plus a second shadow implementation in
`vendors/show.blade.php`. There is now one cart in the browser and it is a thin
client over `/api/cart`:

- no pricing logic, no `localStorage` writes
- `addToCart(id, name, price, photo)` keeps its old signature so the ~6 existing
  call sites did not need editing — but **only the id is used**, which removes
  any path for a client-supplied price to reach the server
- line controls are delegated event handlers reading `data-product-id`, not
  inline `onclick` handlers carrying interpolated values
- every response replaces local state wholesale; a rejected mutation triggers a
  resync so the UI cannot drift from the database
- the `+` control disables at the product's available quantity, and the server
  rejects the request anyway if the client ignores that

`resources/js/pages/checkout.js` + `resources/views/checkout/index.blade.php`
implement the checkout page. It is deliberately **single-page, not a five-step
wizard**: the whole flow is address, review, payment, and a stepper would hide
the total behind a "next" button on mobile for no benefit. Address creation is
inline so the shopper is never bounced out of checkout.

The cart modal's button no longer places an order — it navigates to `/checkout`,
because an order now requires a delivery address.

---

## 12. Payments (Phase C)

One `payments` row per order (`unique(order_id)`), created inside the same
checkout transaction as the order (decision D9 — COD is the only configured
provider, no gateway is stubbed).

```
pending ──▶ paid ──▶ partially_refunded ──▶ refunded
   │                        ▲
   └──▶ cancelled            └── (a second, smaller refund can still land here)
```

- **`pending → paid`** happens in exactly one place: `OrderStatusService::transition()`,
  when an order reaches `completed`. COD settles when the courier hands over the
  goods and collects cash — i.e. exactly when fulfilment reaches its terminal
  state — so every completion path (vendor, admin) settles the payment the same
  way. `Admin\OrderController::markCompleted()` was rewired through
  `OrderStatusService` for this reason: it previously wrote `status` directly,
  bypassing both the state machine and payment settlement.
- **`pending → cancelled`** happens in `OrderCancellationService::cancel()`. A
  payment that has already settled is left alone — undoing collected money is a
  refund, not a cancellation.
- Both settlement and cancellation are conditional updates
  (`WHERE status = 'pending'`), so a payment can only ever be claimed once.

No card number, CVV, token, or PAN is accepted, stored, or logged anywhere —
`provider_reference` is a free-text string a real gateway would populate later.

---

## 13. Returns (Phase C)

`order_returns` (named to avoid the `RETURN` reserved word in MySQL) +
`return_items`. A return may only be requested once the order is `completed`
(`Order::isReturnable()`) — returning something that never arrived is a
cancellation, not a return.

```
requested ──▶ under_review ──▶ approved ──▶ received ──▶ completed
    │               │               │
    └───────────────┴───────────────┴──▶ cancelled
```

- **Quantity validation.** `ReturnService::request()` sums quantity already
  claimed by any non-rejected, non-cancelled return against the same order item
  and rejects a request that would exceed what was actually purchased.
- **Actor permissions**, mirroring `OrderStatusService`: customers may only
  cancel their own return; vendors and admins drive review. Vendor isolation is
  enforced by `OrderReturnPolicy` against `order_returns.vendor_id`.
- **Stock restoration** happens on `received`, not `approved` — approval is a
  decision, receipt is the physical fact — through the same exactly-once claim
  pattern as order cancellation: a conditional `UPDATE ... WHERE
  stock_restored_at IS NULL` on `order_returns`, not a status check.
- The write itself is conditional (`WHERE status = :from`), so a concurrent
  review action loses cleanly with a conflict message.

---

## 14. Refunds (Phase C)

`refunds`, linked to the order, optionally to the return that justified it and
the payment it draws against. `unique(order_return_id)` makes "at most one
refund per return" a database guarantee, not just an application check.

```
pending ──▶ processing ──▶ completed
   │             │
   └─────────────┴──▶ failed / cancelled
```

**The invariant that matters:** cumulative completed refunds against a payment
must never exceed what was actually paid.
`RefundService::complete()` enforces it with a conditional UPDATE against
`payments.refunded_amount`:

```sql
UPDATE payments SET refunded_amount = refunded_amount + :amount
 WHERE id = :id AND (amount - refunded_amount) >= :amount
```

rather than trusting the amount validated when the refund was initiated — a
second refund racing the first cannot overdraw the payment.

**A SQLite footgun worth documenting.** That check was first written as a bound
parameter (`whereRaw('(amount - refunded_amount) >= ?', [$amount])`). It failed
closed on every request: Laravel binds PHP floats as `PDO::PARAM_STR`, and
SQLite's type-ordering rule places any TEXT value above every NUMERIC value
regardless of content, so `200.0 >= '200'` is *always false* in SQLite,
independent of the numbers involved. `$amount` is a trusted, server-computed
decimal — never user input — so the fix formats it with `number_format($amount,
2, '.', '')` and interpolates it into the raw SQL directly, the same reasoning
`CheckoutService` and `OrderCancellationService` already apply to the analogous
integer case (decision D1). Covered by
`PaymentsReturnsRefundsTest::it initiates and completes a refund, settling the
payment`, which would not pass against the bound-parameter version.

- `complete()` claims the refund (`WHERE status IN (pending, processing)`)
  before touching the payment, so calling it twice — accidentally or
  concurrently — finds nothing left to claim on the second call and rejects
  rather than double-paying.
- Completing a refund tied to a `received` return also closes the return to
  `completed`: money moved, so the loop the return started is done.
- `RefundService::initiateAdHoc()` supports an admin-initiated refund with no
  return behind it (a duplicate COD collection, a goodwill adjustment),
  restricted to `Refund::ADHOC_REASONS` and still capped by the same
  payment-draw check.
- Settling money is admin-only (`RefundPolicy::manage`): a vendor may request a
  refund for a return it owns (`OrderReturnPolicy::initiateRefund`), but only an
  admin can complete or cancel one.
