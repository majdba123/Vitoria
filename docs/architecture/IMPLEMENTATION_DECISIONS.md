# Vetora — Implementation Decisions

Decisions are binding for this program and derived from
[CURRENT_SYSTEM_AUDIT.md](CURRENT_SYSTEM_AUDIT.md). Each records the evidence, the
choice, and what was rejected. A decision marked **Deferred** has not been built; it is
not described anywhere in these docs as if it had been.

---

## D1 — Stock stays a single scalar

**Decision:** `products.quantity` remains the only stock mechanism. No warehouse,
inventory, batch, lot, expiry, reservation, movement, or ledger table.

**Consequences that must hold, and are tested:**
- quantity never goes negative
- an order decrements each product exactly once
- a cancellation restores each product at most once, ever, even under repeat calls

**How, given SQLite (audit §1.1):** `lockForUpdate()` is a no-op on this driver, so
safety comes from **conditional atomic updates**, not locks:

```sql
UPDATE products SET quantity = quantity - :qty
 WHERE id = :id AND quantity >= :qty
```

An affected-row count of 0 means someone else took the stock; the transaction rolls
back with a 422. This is correct on SQLite, MySQL and Postgres alike, so it survives a
future driver change.

Restoration is guarded by a **`stock_restored_at` timestamp on `orders`**, claimed with
a conditional update (`WHERE stock_restored_at IS NULL`) before any increment runs.
That, not the status check, is what makes restoration idempotent (audit R1).

---

## D2 — No product variants

**Decision:** do not introduce `product_variants`.

**Evidence:** §15 explicitly requires inspecting the catalog first. The catalog already
carries `package_size` + `package_unit` on `shared_product_details`, whose `product_id`
is `unique` — one detail row per product. A 100ml and a 500ml presentation are already
two `products` rows, each with its own price, own `quantity`, own vendor, own approval
status, own reviews and photos.

**Why not migrate:** introducing variants would mean moving `price` and `quantity` off
`products` for some rows and not others, splitting the stock rules that D1 depends on,
re-pointing `order_items.product_id`, `favourites`, `product_reviews`, and
`product_photos`, and rewriting every catalog query. That is a destructive migration
with high regression risk against 145 passing tests, taken on to solve a problem the
catalog does not currently have.

**Revisit when:** vendors report maintaining duplicate listings that differ only by pack
size, and the duplication is measurable in the catalog. Not before.

---

## D3 — Extend the existing type system with Policies; defer table-driven RBAC

**Evidence:** the app has one Policy and five type-checking middleware (audit §2.4, R7).
The real risk is not the absence of a `permissions` table — it is that per-record
ownership is enforced ad hoc inside controllers, so vendor isolation depends on each
controller remembering to scope its query.

**Decision:** fix the actual vulnerability first. Add Laravel Policies for every model
that carries ownership (`Order`, `Cart`, `UserAddress`, `Product`, `Vendor`…) and
enforce them with `authorize()` / `Gate`, keeping the existing type middleware as the
coarse outer gate.

**Deferred:** `roles` / `permissions` / `role_permissions` / `user_roles` tables (§23).
Five fixed, non-overlapping actor types with no customer-defined roles do not yet
justify a permission-resolution layer; adding one now would mean two authorization
systems to keep in sync. Revisit when vendor staff (§22) lands, since that is the first
requirement for genuinely different permission sets *within* one type.

**Not deferred:** the security property §23 actually asks for — server-side enforcement,
never button-hiding — is delivered by the Policies.

---

## D4 — Order lifecycle: extend the existing states, do not replace them

**Evidence:** production data uses `pending`, `confirmed`, `completed`, `cancelled`.

**Decision:** keep all four. `completed` is retained as the existing terminal success
state and is **not** renamed to `delivered` — renaming would invalidate historical rows
for no user-visible gain (§60).

States added only where a real actor action exists to drive them: `preparing`,
`shipped`, `out_for_delivery`, plus `return_requested`, `returned`, `refunded` when
Phase C lands. Transitions are declared in one place and enforced server-side; the
frontend can never submit an arbitrary status (§8).

`ready` and `pickup` are **not** implemented — no pickup business model exists in the
repo, and §14 forbids inventing one.

---

## D5 — Address snapshot on the order is authoritative

**Decision:** `user_addresses` is a convenience book for the customer. The order stores
an **immutable snapshot** of the delivery address in its own columns at creation time.
Editing or deleting an address never alters a historical order (§6).

`user_addresses` uses soft deletes so a deleted address does not break the customer's
address list ordering, and orders carry no FK dependency on it.

---

## D6 — Currency: SYP, stored explicitly, no FX

**Decision:** currency is configuration, not a hardcoded string in Blade. Orders persist
their ISO code (`SYP`) at creation so historical orders stay interpretable if the
platform ever adds a second currency. No conversion, no rate table (§17).

---

## D7 — Tax exists structurally, defaults to zero

**Decision:** orders gain `tax_total` alongside `subtotal_amount`, `discount_total`,
`shipping_total`, `grand_total`. **No VAT rate is invented.** `tax_total` is 0 until a
business rule supplies a rate, and §18 explicitly forbids inventing one. The column
exists so adding tax later is not an order-schema migration.

---

## D8 — Money is integer-safe at the boundary, decimal in the DB

**Decision:** columns stay `decimal(12,2)`. All arithmetic is performed server-side in
PHP with explicit `round(…, 2)` at each step, matching the existing checkout. Frontend
totals are display-only and are never accepted as input (§16).

---

## D9 — Payment abstraction without a fake gateway

**Decision (Phase C):** a `payments` table with a provider abstraction, and **COD as the
only registered provider**, because COD is the only method the repository actually
configures. No third-party gateway is stubbed, mocked, or presented as working — §11
forbids fake gateways, and a fake one is worse than none because it looks integrated.

A payment settles in exactly one place — `OrderStatusService::transition()`, when an
order reaches `completed` — so every completion path (vendor, admin) settles it the
same way. `Admin\OrderController::markCompleted()` previously wrote `status` directly,
bypassing the state machine *and* payment settlement; it was rewired through
`OrderStatusService` as part of this decision, since a completion path that skips
settlement would leave COD payments permanently `pending`.

---

## D11 — Float parameters cannot be bound into a numeric SQLite comparison

**Evidence:** `RefundService::complete()`'s cumulative-refund cap
(`payments.refunded_amount`) was first written as
`whereRaw('(amount - refunded_amount) >= ?', [$amount])`. Every call failed with
"amount exceeds" even when the numbers were exactly equal. Laravel's `Connection::bindValues()`
binds any non-integer, non-resource value — including PHP floats — as `PDO::PARAM_STR`.
SQLite's type-ordering rule places any TEXT value above every NUMERIC value regardless of
content, so `200.0 >= '200'` evaluates false unconditionally, independent of the actual
numbers.

**Decision:** where a decimal amount must appear inside a raw numeric comparison against
a computed column, format it (`number_format($amount, 2, '.', '')`) and interpolate it
into the SQL text rather than binding it. `$amount` in this path is always a
trusted, server-computed decimal — never user input — so this carries the same
justification D1 already established for interpolating an integer quantity into
`DB::raw("quantity - {$quantity}")`. A bound parameter remains correct and preferred for
every other case (equality checks, `IN` lists, anything not embedded in raw arithmetic).

**How this was caught:** `PaymentsReturnsRefundsTest::it initiates and completes a
refund, settling the payment` failed against the bound-parameter version and passes
against the interpolated one — the test, not inspection, found it.

---

## D10 — Search: indexed SQL, no search engine

**Decision:** improve indexes and query shape on the existing SQL search. No
Elasticsearch, Meilisearch, or Algolia — §27 requires clear justification and the
catalog does not currently provide it. No semantic search, embeddings, or vector store
(§1, §65).

SQLite FTS5 is available but is not portable to a future MySQL deployment; prefix-
anchored indexed lookups on `name`, localized names, `commercial_name`, `sku`, and
`barcodes` are chosen instead.

---

## Deferred — not built, not documented as built

§11 payments, §12 returns, and §13 refunds are now implemented — see
[COMMERCE_ARCHITECTURE.md §12–14](COMMERCE_ARCHITECTURE.md#12-payments-phase-c) and
[TEST_COVERAGE.md](../testing/TEST_COVERAGE.md).

Still deferred: §14 shipping · §19 invoices · §20 vendor ledger · §22 vendor staff ·
§23 RBAC tables · §24 vendor documents · §25 product documents · §29 comparison ·
§33 notification preferences · §35 audit log · §36 reports · §37 exports · §38 CMS ·
§39 SEO · §40–52 UI redesign.

These remain open scope. Their absence is stated here so no reader mistakes a plan for
an implementation.
