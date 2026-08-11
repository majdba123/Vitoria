# Vetora — Current System Audit

Audit date: 2026-08-12
Method: direct inspection of the repository at commit `df52453`.
Baseline verification: `php artisan test` → **145 passed (913 assertions)** before any change in this program.

This document records what the system **is**, not what it should be. Design decisions
derived from it live in [IMPLEMENTATION_DECISIONS.md](IMPLEMENTATION_DECISIONS.md).

---

## 1. Stack (confirmed, not assumed)

| Layer | Actual |
|---|---|
| Framework | Laravel 12, PHP ^8.2 (runtime 8.3.30) |
| Views | Blade — 97 `.blade.php` files, no SPA |
| CSS | Tailwind CSS v4 via `@tailwindcss/vite` |
| Bundler | Vite 7 (`npm run build` / `npm run dev`) |
| JS | 5 hand-written modules under `resources/js`, plus Axios. No framework. |
| Auth | Laravel Sanctum v4, `statefulApi()` enabled |
| Realtime | Laravel Reverb v1 + laravel-echo + pusher-js |
| Tests | Pest v3 (`tests/Feature`, `tests/Unit`), 145 tests |
| Lint | Laravel Pint |
| **Database** | **SQLite** (`DB_CONNECTION=sqlite`) |
| Cache | `file` store (Redis available via predis, documented in REDIS_SETUP.md, not the active driver) |
| Queue | `database` |
| Session | `file` |

### 1.1 The SQLite finding is load-bearing

`.env` sets `DB_CONNECTION=sqlite` with a file database. This constrains several
sections of the target spec and must not be glossed over:

- **`lockForUpdate()` is a no-op in SQLite.** The existing checkout in
  `Api/OrderController::store()` calls `->lockForUpdate()` and reads as if it were
  race-safe. It is not, on this driver. Concurrency safety has to come from
  conditional/atomic `UPDATE ... WHERE` statements, not from row locks.
- **No MySQL/Postgres full-text search.** §27's "evaluate full-text search" resolves
  to: SQLite FTS5 or well-indexed `LIKE` prefix queries. Not a portable win.
- Writer concurrency is single-writer. Fine at current scale; relevant to §53.

Whether SQLite is the intended production driver is an open question for the product
owner. Nothing in this program assumes a driver change.

---

## 2. Domain model as it exists

20 Eloquent models, 66 migrations.

**Present:** `User`, `Vendor`, `Syndicate`, `Product`, `ProductPhoto`, `ProductReview`,
`Category`, `Subcategory`, `Manufacturer`, `Brand`, `City`, `Coupon`, `Order`,
`OrderItem`, `AdminNotification`, `ContactMessage`, `FooterSetting`,
`SharedProductDetail`, `AgriculturalProductDetail`, `VeterinaryProductDetail`.

### 2.1 Products

`products`: `vendor_id, name, description, price, quantity, is_active, status,
discount_percentage, discount_is_active, discount_starts_at, discount_ends_at,
category_id, subcategory_id, rejection_reason` + localized name columns.

Product detail is **already normalized into three side tables** (a prior migration
effort, documented in `product-model-separation-plan-ar.md`):

- `shared_product_details` — commercial_name, sku, `aliases` (json), `barcodes` (json),
  country_of_origin, registration_number/status, **`package_size` + `package_unit`**,
  descriptions, `keywords` (json)
- `agricultural_product_details`
- `veterinary_product_details`

**Stock is a single scalar: `products.quantity` (unsigned int).** There is no
warehouse, batch, lot, reservation, or movement table anywhere in the schema. This
matches the §2 constraint and must stay that way.

**Variant-relevant finding:** `package_size` / `package_unit` already exist on
`shared_product_details`, one row per product (`product_id` is `unique`). Pack sizes
are therefore modelled today as *separate products*, not as variants of one product.
See IMPLEMENTATION_DECISIONS §D2.

### 2.2 Orders — the current commerce core

`orders`: `order_number (unique), user_id, vendor_id, status, payment_way,
coupon_id/code/type/value, items_count, subtotal_amount, coupon_discount_amount,
total_amount, timestamps`.

`order_items`: `order_id, product_id (nullOnDelete), product_name, original_unit_price,
has_discount, applied_discount_percentage, unit_price, quantity, line_total,
discount_amount`.

Statuses (`app/Models/Order.php`): `pending`, `confirmed`, `completed`, `cancelled`.
Four states, no transition rules — status is assigned by direct `update()`.

**Orders are split by vendor at checkout.** One cart producing items from 3 vendors
creates 3 `orders` rows. Coupon discount is proportionally allocated across the vendor
subtotals with the remainder pushed onto the last vendor. This is a real, working,
non-obvious behaviour and must be preserved.

**What orders do not have:** any delivery address (no address columns, no FK, no
snapshot), shipping cost, tax, grand-total separation, cancellation metadata, status
history, payment record, invoice, or return linkage.

### 2.3 Cart

There is **no cart table**. The cart is client-side only, held in `localStorage` and
rendered by `resources/views/components/home/cart-modal.blade.php` (67 lines), with
cart code spread across `home.blade.php`, `products/index`, `products/show`,
`categories/show`, `vendors/show`, and `navbar`.

At checkout the browser POSTs an `items[]` array of `{product_id, quantity}` to
`Api/OrderController::store()`.

Credit where due: **the backend does not trust client prices.** It re-reads
`Product::getDiscountedPrice()` server-side and recomputes every line. Quantity is
validated server-side. The trust boundary is better than the localStorage design
suggests. What is genuinely missing is cart *persistence*, guest→user merge, and
server-side cart state.

### 2.4 Authorization

Type-based, via an integer `users.type`:
`0=USER, 1=ADMIN, 2=VENDOR, 3=SYNDICATE, 4=EMPLOYEE`.

Enforced by five middleware aliases (`admin`, `vendor`, `syndicate`, `employee`,
`product.type.selected`) registered in `bootstrap/app.php`, applied per route group in
`routes/api_admin.php`, `api_vendor.php`, `api_syndicate.php`, `api_employee.php`.

**There is exactly one Policy in the entire application:** `ProductReviewPolicy`.
There are no `roles`/`permissions` tables. Authorization is coarse: "is this user a
vendor?" — not "may this vendor touch *this* record?". Per-record vendor isolation is
enforced ad hoc inside controllers where it is enforced at all.

### 2.5 Coupons

`coupons`: `code (unique), title, description, discount_type, discount_value,
starts_at, ends_at, is_active, status, usage_limit, used_count, created_by_user_id`.

Global only. **No** minimum subtotal, max discount cap, per-user limit,
first-order-only, or vendor/category/product scoping. **No usage-tracking table** —
`used_count` is a counter with no record of who redeemed what, so per-user limits are
not currently expressible.

### 2.6 Notifications

Custom implementation (`admin_notifications` + `admin_notification_reads` +
`NotificationService`), not Laravel's `notifications` table. Read state is tracked per
user via the join table. Reverb is wired for realtime delivery.

### 2.7 Other present features

Favourites (`favourites`), product reviews with admin + vendor moderation views,
categories with per-category commission, `category_vendor` pivot, cities, footer
settings, contact messages, vendor self-registration fields, syndicates, AR/EN
localization with RTL, dark mode, and a `vendors.paid_amount` scalar as the only
vendor financial state.

---

## 3. Confirmed defects and risks

Ordered by severity. These are findings, not speculation — each cites real code.

### R1 — Cancellation can double-restore stock (correctness, money-adjacent)
`Api/OrderController::cancel()` reads the order, checks `status !== cancelled`, then
inside a transaction sets status and increments every product's `quantity`. Two
concurrent cancel requests both pass the status check before either writes. On SQLite
there is no row lock to serialize them. Result: stock restored twice. There is no
idempotency flag on the order recording that restoration already happened.

### R2 — Checkout relies on `lockForUpdate()` that SQLite ignores
`store()` re-reads products with `->lockForUpdate()` inside the transaction and
re-checks `quantity`, then calls `decrement()`. The re-check is good practice but the
lock does nothing on this driver, so two concurrent checkouts for the last unit can
both pass. `quantity` is `unsignedInteger`; a negative result is a driver-level error,
not a graceful rejection.

### R3 — Coupon usage limit is checkable but not enforceable per user
`resolveCoupon()` checks `used_count >= usage_limit` outside the transaction and again
under a (no-op) lock inside it. Without a redemption table there is no way to enforce
"one per customer", and the increment is not conditional (`UPDATE ... WHERE used_count
< usage_limit`), so the cap can be exceeded under concurrency.

### R4 — Orders have no delivery address at all
Nothing in `orders` or `order_items` records where the order goes. Any address shown
today is not persisted with the order, so there is no immutable snapshot and no
historical record. This blocks §6, §7, §14, §19.

### R5 — Order status is a free string with no state machine
`status` is written by direct `update()` from multiple controllers. Nothing prevents an
invalid transition (`cancelled` → `pending`, `completed` → `confirmed`) and nothing
records who changed it, when, or why.

### R6 — Cart state is unrecoverable
localStorage-only means: cart lost on device change, no guest→account merge, no
server-side visibility, no abandoned-cart data, and no server validation of cart
contents until the checkout POST.

### R7 — Single Policy / coarse authorization
With one Policy and middleware-level checks only, per-record ownership (IDOR) defence
depends on each controller remembering to scope its query. This is the highest-risk
area for §54 and needs a systematic audit, not a spot fix.

### R8 — Vendor finance is a single mutable scalar
`vendors.paid_amount` has no history. There is no way to reconstruct gross sales,
commission, refunds, or settlement state, and no audit trail for corrections.

---

## 4. Explicitly out of scope for this program

Per §1 and §65: `app/Http/Controllers/Api/AiProductController.php` and
`tests/Feature/AiProductApiTest.php` are pre-existing. They are **left untouched**. No
AI capability is added, extended, or wired into any new code path.

Per §2 and §64: no warehouse, inventory, batch, lot, expiry, reservation, movement, or
stock-ledger table is introduced. `products.quantity` remains the sole stock mechanism.

---

## 5. Gap summary against the target spec

| Spec area | Status |
|---|---|
| §5 Server cart | **Absent** — localStorage only |
| §6 Address book | **Absent** |
| §7 Checkout | Exists, but consumes a client `items[]` array and has no address step |
| §8 Order lifecycle | 4 states, no transition rules |
| §9 Status history | **Absent** |
| §10 Cancellation metadata | **Absent** (status flip only, R1) |
| §11 Payments | **Absent** — `orders.payment_way` string, COD only |
| §12–13 Returns / refunds | **Absent** |
| §14 Shipping | **Absent** |
| §15 Variants | Not needed — see decision D2 |
| §16–17 Pricing / currency | Product price + discount % exist; currency not modelled |
| §18 Tax | **Absent** |
| §19 Invoices | **Absent** |
| §20 Vendor ledger | **Absent** — `paid_amount` scalar only (R8) |
| §21 Coupons | Partial — no scoping, caps, or per-user limits (R3) |
| §22 Vendor staff | **Absent** — 1:1 user↔vendor |
| §23 RBAC | **Absent** — integer user type + 5 middleware (R7) |
| §24 Vendor verification | Partial — self-registration fields, no document workflow |
| §25 Product documents | **Absent** |
| §26 Master data | Largely done — manufacturers, brands, 3-way detail split already exist |
| §27–28 Search / filters | Basic; needs indexing review |
| §29 Comparison | **Absent** |
| §30–31 Favourites / reviews | Present, working |
| §32–33 Notifications | Custom system present; preferences absent |
| §34 Account security | Needs audit |
| §35 Audit log | **Absent** |
| §36–37 Reports / exports | **Absent** |
| §38 CMS | Partial — `footer_settings`, About page; most content hardcoded |
| §39 SEO | **Absent** — no slugs, sitemap, or structured data |
| §40–52 Design system / UI | Prior design pass exists (`docs/design/`), see those documents |
