# Vetora — Test Coverage

Last run: 2026-08-12 · `php artisan test`

```
Tests:    186 passed (1075 assertions)
Duration: 64.01s
```

Baseline before this program: 145 passed (913 assertions). **41 tests added, 0 regressions.**

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

---

## Not yet covered

These areas have no tests because the features are not implemented. Listed so the
gap is explicit rather than implied by omission:

payments · returns · refunds · shipping · invoices · vendor ledger · settlements
· vendor staff · RBAC tables · vendor verification documents · product documents
· product comparison · notification preferences · admin audit log · reports ·
exports · CMS · SEO.

## Known gaps in what *is* implemented

- **Concurrency is proven by construction, not by parallel execution.** The
  exactly-once and never-negative properties are enforced by conditional
  `UPDATE … WHERE` statements and verified by repeat-invocation tests. A true
  parallel-request test is not meaningful against SQLite's single-writer model;
  it should be added if the platform moves to MySQL or Postgres.
- **Guest checkout is not supported.** Guests can build and merge a cart, but
  `POST /api/checkout` requires an authenticated user with a saved address.
- **The storefront still uses the localStorage cart.** The server cart API is
  complete and tested, but the Blade views have not yet been switched over to it.
  Until that lands, the two carts coexist and only the server one is authoritative
  at checkout.
