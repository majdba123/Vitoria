# Vetora — Test Coverage

Last run: 2026-08-12 · `php artisan test`

```
Tests:    256 passed (1442 assertions)
Duration: 56.79s
```

Baseline before this program: 145 passed (913 assertions). **111 tests added, 0
regressions** (45 from Phase B; 17 from payments/returns/refunds; 13 from
shipping/invoices/vendor ledger; 11 from vendor staff/RBAC; 11 from vendor documents;
7 from product documents; 7 from notification preferences).

The checkout flow was additionally exercised end-to-end in a real browser
against the dev server — guest cart → login merge → address creation → order
placement → cancellation. Findings are recorded in §"Browser verification" below.

Also verified in the same pass:

| Check | Command | Result |
|---|---|---|
| Style | `./vendor/bin/pint --test` | `{"result":"pass"}` |
| Frontend build | `npm run build` | 61 modules, built in 1.76s |
| Route integrity | `php artisan route:list` | resolves, no errors |

---

## New suites

### `tests/Feature/ServerCartTest.php` — 13 tests

Covers spec §5.

| Test | Property proved |
|---|---|
| creates a guest cart tied to the session | guest carts exist server-side and are session-owned |
| ignores any price the client tries to send | **client-supplied price/subtotal/line total are discarded**; no price column exists on the line |
| refuses to add more than in stock | quantity validated server-side |
| sums repeat additions and enforces stock against the total | cumulative quantity, not per-request |
| will not add unapproved / inactive / suspended-vendor products | purchasability gate |
| removes a line when quantity is set to zero | update semantics |
| drops unavailable items and clamps over-large lines | automatic invalid-item cleanup |
| validates coupons server-side and computes the discount itself | client-claimed discount of 990 → server applies real 100 |
| respects a coupon maximum discount cap | cap enforced |
| rejects a coupon below its minimum order subtotal | minimum enforced |
| **merges a guest cart into the account cart at login, clamped to stock** | guest→user merge; 3+3 clamped to the 4 available |
| keeps one cart per user and one per guest session | uniqueness constraints hold |
| caps a single line at the configured maximum | 999 line cap |

### `tests/Feature/CheckoutAndOrderLifecycleTest.php` — 22 tests

Covers spec §7–§10 and the vendor-isolation half of §54.

**Checkout and money**

| Test | Property proved |
|---|---|
| places an order, decrements stock once, empties the cart | happy path, stock 10 → 7 for qty 3 |
| snapshots the delivery address and never reads it back | editing *and deleting* the address leaves the order unchanged |
| splits a multi-vendor cart and allocates the coupon across them | 2 orders; discount parts sum to exactly 100.00, totals to 900.00 |
| consumes exactly one coupon use for a multi-vendor checkout | `used_count` = 1, one redemption row |
| will not let a coupon exceed its usage limit | 422, no order, stock untouched |
| enforces a per-user coupon limit | second use by same user rejected |
| rejects a payment method that is not configured | `card` → 422 |

**Stock integrity (audit R1, R2 — the core claims)**

| Test | Property proved |
|---|---|
| **never lets stock go negative** | stock drops to 1 mid-flight; line clamps, final quantity is 0, never below |
| **does not decrement stock twice** | replaying checkout finds an empty cart; quantity stays 3, one order |
| **restores stock exactly once no matter how many times invoked** | cancel restores 6→10, then 5 further `restoreStockOnce()` calls all return `false` and quantity stays 10 |
| **rejects a second cancellation over HTTP without touching stock** | second call 422, quantity still 10 |

**Cancellation**

| Test | Property proved |
|---|---|
| stores who cancelled, why, and when | all four metadata fields persisted |
| refuses an unrecognised cancellation reason | arbitrary reason string rejected, order unchanged |
| will not cancel an order that has already shipped | 422; stock stays consumed |
| records the order creation on the status timeline | opening history row with null previous status |

**State machine and authorization**

| Test | Property proved |
|---|---|
| rejects a status the state machine disallows | `pending → shipped` refused |
| rejects a status value that is not a real status | `refunded_lol` refused — **frontend cannot submit arbitrary status** |
| advances through the fulfilment path and logs every step | 5 transitions, 5 history rows |
| **stops a vendor from touching another vendor's order** | 403 on both status and cancel |
| **stops a customer from reading another customer's order** | 403 |
| stops a customer from driving fulfilment | actor-permission gate rejects customer→confirmed |

### `tests/Feature/UserAddressBookTest.php` — 6 tests

Covers spec §6.

| Test | Property proved |
|---|---|
| makes the first saved address the default | checkout always has a preselection |
| moves the default flag to exactly one address | no split-brain default |
| promotes another address when the default is deleted | customer never left without a default |
| rejects a label the business does not use | label whitelist |
| **never exposes or mutates another customer's address** | 403 on update, delete, and set-default |
| lists only the signed-in customer's addresses, default first | scoping and ordering |

### `tests/Feature/CheckoutPageTest.php` — 4 tests

| Test | Property proved |
|---|---|
| requires authentication to reach `/checkout` | route is auth-gated |
| renders the checkout page for a signed-in customer | page boots with its controls |
| **no localStorage cart remains anywhere in the storefront** | scans every Blade + JS file; a second client-side cart would silently diverge from the server one |
| does not hardcode a currency literal in the cart modal | currency is configuration (spec §17) |

---

## Browser verification

Run against `php artisan serve` with the real seeded catalogue, driving the actual
UI controls rather than the API where possible.

| Step | Observed |
|---|---|
| Guest adds product to cart | server cart row created; `localStorage.cart` stayed `null`; badge showed 1 |
| Page reload | cart survived from the server — badge 1, modal rendered `4,500 SYP`, `1 منتج` |
| Increment via the UI `+` button ×4 | quantity reached 5 (the product's stock), button then `disabled` |
| Attempt to exceed stock via API | `422 لم يتبقَّ سوى 5 من جرار زراعي صغير.` |
| Login | guest cart merged into the account cart, quantity preserved |
| Checkout page | subtotal `22,500 SYP`, shipping `مجاني`, tax row hidden (zero), COD the only method |
| Add address via the inline form | saved, auto-selected, auto-defaulted, rendered in Arabic |
| Place order | `ORD-20260811-25109`, cart emptied, badge cleared |
| Database after order | `grand_total` 22500.00, `currency` SYP, Arabic address snapshot persisted, **stock 5 → 0**, 1 history row (`null → pending`) |
| Cancel | `200`; **stock restored 0 → 5**; `stock_restored_at` stamped |
| Cancel again | `422 هذا الطلب ملغى بالفعل.`; stock unchanged |
| `restoreStockOnce()` invoked again directly | returned `false`; **stock still 5** |
| Console errors | none except the 422 deliberately triggered |

All verification records were removed afterwards; `.env` was restored.

---

### `tests/Feature/PaymentsReturnsRefundsTest.php` — 17 tests

Covers spec §11–§13 (Phase C).

| Test | Property proved |
|---|---|
| creates a payment at checkout, for the grand total, that settles once delivered | payment created inside the checkout transaction, amount matches grand_total |
| **marks the payment paid only once the order reaches completed** | COD settles exactly at the terminal fulfilment state, via the admin completion path too |
| cancels an unsettled payment when the order is cancelled | pending payment cancelled alongside order cancellation |
| leaves an already-settled payment untouched by cancellation | a paid payment survives `cancelIfUnsettled()` — undoing collected money is a refund, not a cancellation |
| requests a return for a delivered order | happy path, status `requested` |
| rejects a return request for an order that has not been delivered | `Order::isReturnable()` gate |
| rejects returning more than was purchased | quantity-already-claimed check |
| stops a customer from requesting a return on someone else's order | 403 |
| stops a vendor from reviewing another vendor's return | 403, `OrderReturnPolicy` vendor isolation |
| rejects an invalid return transition | `requested → received` refused |
| **restores stock exactly once when a return is received** | quantity restored on `received`; a second direct `restoreStockOnce()` call returns `false` and stock is unchanged |
| lets a customer cancel their own pending return but not someone else's | ownership-scoped cancel |
| initiates and completes a refund, settling the payment | full lifecycle: return received → refund initiated → refund completed → payment `refunded`, return `completed` |
| **prevents a duplicate refund for the same return** | second `initiate()` for the same return rejected; `unique(order_return_id)` backstop |
| **cannot complete the same refund twice** | second `complete()` call rejected; `refunded_amount` not double-incremented |
| caps a refund at what remains available on the payment | amount above `refundableAmount()` rejected before a refund row is created |
| only an admin can complete or cancel a refund | vendor hitting the admin completion route gets 403 |

### `tests/Feature/ShippingInvoicesLedgerTest.php` — 13 tests

Covers spec §14, §19, §20.

| Test | Property proved |
|---|---|
| creates a pending shipment at checkout with the seeded zero rate | shipment created in the checkout transaction, default rate is 0 |
| **keeps the shipment status in lockstep with order fulfilment** | 5 order transitions → shipment ends `delivered`, 4 shipment events logged |
| lets a vendor report a failed delivery and then a return to the vendor | `failed` → `returned`; a second `failed` from `returned` is refused (terminal) |
| stops a vendor from managing another vendor's shipment | 403 |
| **applies a real, non-zero shipping rate once an admin configures one** | seeded rate is 0; after `PATCH .../shipping/rates/{id}`, a fresh checkout's `grand_total` includes exactly the configured 1500 — proves the mechanism is real, not hardcoded |
| creates an invoice snapshot matching the order totals at checkout | `invoice.grand_total` equals `order.grand_total`; number starts `INV-` |
| only the invoice owner, its vendor, or an admin may view it | 403 for a stranger, 200 for the owner |
| requires ownership to open the printable invoice page | web route: 403 for a stranger, 200 + visible invoice number for the owner |
| **records a sale and its commission using the category rate captured at completion** | 200 subtotal, 10% category commission → sale credit 200, commission debit 20, summary net_earnings 180 |
| does not record a sale twice for the same order | calling `recordSale()` again on an already-recorded order is a no-op |
| **records a refund on the ledger and reduces the outstanding balance** | full return→refund flow; refund debit 100 drops net_earnings to −10, outstanding floored to 0 |
| **caps a settlement at the outstanding balance and records it on the ledger** | settling 999 against a 200 balance is rejected; settling 150 succeeds and outstanding drops to 50 |
| records a manual admin adjustment on the ledger | a credit adjustment raises net_earnings by exactly its amount |

### `tests/Feature/VendorStaffRbacTest.php` — 11 tests

Covers spec §22, §23 (vendor-scoped RBAC).

| Test | Property proved |
|---|---|
| lets an owner add a staff member, who gains vendor-scoped access by role | Catalog Manager can browse products, cannot update order status |
| lets an order manager update fulfilment but not manage the product catalog | role differentiation the other direction |
| restricts a viewer to read-only access | no order update, no staff management |
| **always grants the owner full access, independent of the permissions table** | no `vendor_members` row exists for the owner; `hasVendorPermission()` is true even for a made-up permission key |
| rejects adding a staff member with no matching account | no invite-a-stranger-by-email flow |
| rejects adding a user who owns another vendor as staff | an owner can't double as someone else's staff |
| rejects adding a user who is already active staff at another vendor | one active membership at a time |
| **revokes access when a staff member is removed** | `DELETE` sets `removed`; the same user immediately gets 403, and `managedVendor()` returns null |
| reactivates a removed member instead of duplicating the row | re-adding updates the same row; count stays 1 |
| isolates a staff member strictly to their own vendor | 403/404 on a foreign vendor's order |
| lets finance staff view the ledger but not manage the product catalog | financial read access without catalog write access |

### `tests/Feature/VendorDocumentsTest.php` — 11 tests

Covers spec §24.

| Test | Property proved |
|---|---|
| **stores an uploaded document privately, never on the public disk** | exists on `local`, missing on `public`; stored path never contains the client's original filename |
| rejects an unsupported document type | type whitelist enforced |
| **replaces the file and resets review state when a document type is resubmitted** | same row id reused, old file deleted from disk, review fields cleared |
| lets a manager upload documents but not a viewer | `documents.manage` gating; a viewer can still list |
| lets an admin approve a pending document | happy path |
| requires a rejection reason to reject a document | validation |
| **cannot review the same document twice** | second review call rejected; status unchanged after the first |
| lets an admin suspend a previously verified document | `verified → suspended` |
| stops a vendor from viewing or downloading another vendor's document | 403 on both endpoints |
| expires overdue verified documents when the admin queue is loaded | lazy expiry runs before the queue is returned |
| creates a `commercial_registration` document automatically at vendor self-registration | the new table is populated from day one, not left disconnected from the only real submission path |

### `tests/Feature/ProductDocumentsTest.php` — 7 tests

Covers spec §25.

| Test | Property proved |
|---|---|
| lets a vendor upload a product document for review | stored privately, not on the `public` disk |
| **is not publicly visible or downloadable until approved** | absent from the public list, 404 on direct download by id |
| **becomes publicly visible and downloadable once approved** | same document appears and streams after admin approval |
| **stops being publicly downloadable once disabled** | approved → disabled removes public access on the very next request |
| requires a rejection reason and prevents double review | validation + conditional-update idempotency |
| stops a vendor from managing another vendor's product documents | 403 |
| allows several documents of the same type on one product | no `unique(product_id, type)` — two leaflets, two languages |

### `tests/Feature/NotificationPreferencesTest.php` — 7 tests

Covers spec §33.

| Test | Property proved |
|---|---|
| lists default preferences with the critical categories locked | `order_updates`/`account_security` come back `editable: false` with no row written |
| lets a user disable a mutable category | persists and reflects on the next read |
| rejects disabling a critical category | 422, no row written |
| rejects an invalid category | validation |
| **never disables a critical category even from a directly-written row** | a factory-written `enabled: false` row for `order_updates` is still overridden — `isEnabled()` returns `true` regardless |
| **hides a marketing notification from a user who disabled it, but not from one who did not** | same public broadcast, two users, two different `unread_count` results |
| stops a vendor from receiving a document-review notification once they opt out | no recipient row created for a `vendor_compliance` notice once disabled |

---

## Not yet covered

These areas have no tests because the features are not implemented. Listed so the
gap is explicit rather than implied by omission:

product comparison · admin audit log · reports · exports · CMS · SEO.

## Known gaps in what *is* implemented

- **Concurrency is proven by construction, not by parallel execution.** The
  exactly-once and never-negative properties are enforced by conditional
  `UPDATE … WHERE` statements and verified by repeat-invocation tests. A true
  parallel-request test is not meaningful against SQLite's single-writer model;
  it should be added if the platform moves to MySQL or Postgres.
- **Guest checkout is not supported.** Guests can build and merge a cart, but
  `POST /api/checkout` requires an authenticated user with a saved address.
- **Sanctum statefulness is port-bound.** `SANCTUM_STATEFUL_DOMAINS` lists
  `localhost:8010`. Session-authenticated API calls return 401 on any other port,
  which is correct behaviour but easy to misread as an auth bug when running the
  dev server elsewhere.
