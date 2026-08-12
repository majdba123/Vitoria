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

**Resolved:** vendor staff (§22) landed — see decision D15. `roles` / `permissions` /
`role_permissions` now exist, scoped to vendors via `vendor_members`. The other four
actor types (admin/employee/syndicate/customer) still have no requirement for
differentiated permission sets and are untouched — there is no generic `user_roles`
table, because nothing yet needs one outside the vendor-staff case D3 named as the
trigger.

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

## D12 — Shipping: real mechanism, zero rate by default

**Decision:** `shipping_zones`, `shipping_zone_governorates`, `shipping_methods`,
`shipping_rates`, `shipments`, `shipment_events` — no warehouse or inventory concept
(D1 is untouched). Three methods are seeded (`standard_delivery`, `express_delivery`,
`vendor_delivery`); `pickup` is not implemented, for the same reason D4 excluded it —
no pickup business model exists in the repository.

**Rates default to 0.** No real shipping rate exists in the business configuration,
and §63's final quality gate explicitly forbids "fake shipping... behaviour" —
exactly the reasoning D7 already applied to tax. The seeded default zone's rates are
0 for every method; an admin can set a real one via `PATCH /api/admin/shipping/rates/{id}`,
and `ShippingService` will apply whatever is configured, including 0.

**Why seeded in the migration, not a Seeder class:** `.github/workflows/deploy.yml`
runs `php artisan migrate --force` on every deploy and never `db:seed`. A Seeder
populating the default zone/methods would work in every local/CI environment and
silently never run in production, leaving checkout with no zone to resolve to. The
migration's `up()` inserts the rows directly.

**Shipment status is a separate entity from order status**, not a duplicate state
machine: the happy path (`preparing`/`shipped`/`out_for_delivery`/`delivered`) is
driven automatically from `OrderStatusService::transition()`, so there is one source
of truth for that part. `failed` and `returned` exist only on the shipment — a
delivery attempt failing is a courier-side fact that does not by itself cancel the
order, and the order's own state machine has no equivalent states to reuse.

---

## D13 — Invoices: no PDF dependency added

**Decision:** one `invoices` row per order, created inside the checkout transaction,
snapshotting figures that are already immutable at that point (D8). It exists for a
stable `invoice_number` sequence and an explicit `issued_at`, not as a second source
of truth for the amounts.

**No PDF library was added to `composer.json`.** None existed in the repository, and
`AGENTS.md` — Laravel Boost's own project guidelines — states plainly: "Do not change
the application's dependencies without approval." §19 makes a PDF conditional on
existing tooling supporting it "safely"; it doesn't, so `GET /invoices/{id}/print`
renders a self-contained, print-styled HTML page instead. A browser's native
print-to-PDF satisfies the requirement without an unreviewed new dependency.

---

## D14 — Vendor ledger replaces live-recomputed commission

**Evidence:** `Vendor\CommissionController` and `Admin\VendorCommissionController`
both compute commission **live, on every request**, from each order item's
category's *current* `commission` rate, compared against `vendors.paid_amount` — a
single mutable scalar with no record of when or why it changed. A category rate
change retroactively rewrites every past vendor's historical commission, which is
not a property a financial record should have (spec §20's own framing: "not
sufficient as the only financial history").

**Decision:** `vendor_ledger_entries` (immutable — a correction is a new
`adjustment` entry, never an edit) + `vendor_settlements`. Entries are written at
exactly two already-exactly-once business events: order completion
(`OrderStatusService::transition()`, via `VendorLedgerService::recordSale()`) and
refund completion (`RefundService::complete()`, via `recordRefund()`). The
commission rate is captured at that moment and frozen — never recomputed later.

**`vendors.paid_amount` and the two existing commission-stats endpoints are left
in place, untouched, not migrated.** Rewriting them would be the destructive
migration §60 forbids, and nothing currently reads `paid_amount` incorrectly — they
are superseded by the ledger for new reporting, not broken by its addition.

**Settlements are capped at outstanding balance** (`recordSettlement()` computes
`summary()` first and rejects an amount that would push the balance negative) —
the same "never overdraw" reasoning D11 established for refunds against a payment,
applied to a vendor's balance against the platform.

---

## D15 — Vendor staff: additive membership, owner untouched, permissions scoped to vendors only

**Evidence:** `vendors.user_id` is a single owning user, read via `User::vendor(): HasOne`
and re-derived independently in ~19 files (five policies, the `vendor` route middleware,
and every `Api\Vendor\*` / vendor-branch controller) — always the same
`$user->vendor?->id` idiom, never a shared helper.

**Decision:** `vendor_members` is purely additive. The owner is **never** represented as
a row in it — ownership stays exactly `vendors.user_id`, unmigrated, and
`User::hasVendorPermission()` bypasses the permissions table unconditionally when
`$vendor->user_id === $user->id`, so a missing or misconfigured role can never lock an
owner out of their own store. `User::managedVendor()` resolves "the vendor this user may
act on" — the owned vendor if one exists, otherwise the vendor of an active
`vendor_members` row — and every one of those ~19 call sites was pointed at it instead of
`vendor` directly. This was a mechanical, verified-by-full-suite sweep, not a schema
change to `vendor()` itself: `vendor()` remains a real `hasOne`, so anything relying on
its exact shape (e.g. vendor-registration's `Vendor::create(['user_id' => ...])`) is
unaffected.

`roles` / `permissions` / `role_permissions` are generic primitives (D3 deferred exactly
this), but only `vendor_members.role_id` currently assigns one — there is no `user_roles`
table, because admin/employee/syndicate/customer still have no differentiated-permission
requirement. The six roles spec §22 lists are seeded directly in the migration's `up()`
(production only runs `migrate --force`, per the pattern already established for shipping
and the RBAC permission set), with `owner` seeded for completeness but never assignable
via staff invite (`Role::INVITABLE_KEYS` excludes it) — assigning "Owner" through
`vendor_members` would create a second, weaker path to full access that the bypass above
makes redundant anyway.

**Permission enforcement is scoped to where a role actually differs from another** —
`orders.update`/`cancel`, `returns.review`/`refund`, `shipments.manage`,
`products.manage`, `ledger.view`/`settlements.view`, `staff.manage`, `profile.manage`.
Read access (`*.view`) is granted to every seeded role, so viewing is gated only by
vendor ownership/membership, not a per-permission check — adding one there would be
enforcement with no corresponding restriction to enforce.

**Staff invitation requires an existing account** (looked up by email or phone). There is
no invite-by-email-to-a-new-user flow with its own token/acceptance state machine — §22
does not ask for one, and building it would be speculative. Adding a user flips their
`type` to `TYPE_VENDOR` if it was not already (required for `EnsureUserIsVendor` to admit
them at all); removing sets `status = 'removed'` rather than deleting the row, so
re-adding the same person later reactivates one auditable row instead of creating a
duplicate.

---

## D16 — Vendor documents: additive table, existing field untouched, no new permission

**Evidence:** `vendors.commercial_register_file` is a single nullable string, written once at
self-registration (`AuthService::register`) and downloadable only through one admin
endpoint that never inspects it — no reject action, no expiry, no second document type.

**Decision:** `vendor_documents` is purely additive. `commercial_register_file` is left in
place, untouched, not migrated or backfilled from history — the same reasoning D9 and D14
already applied to `payments`/`vendors.paid_amount`: it still works exactly as it did, and
rewriting it would be a destructive migration for no functional gain (§60). Self-registration
is wired to *also* create a `commercial_registration` row in the new table (a small, additive
change to `AuthService::register`, wrapped in try/catch so a document-row failure can never
break account creation) — otherwise the new admin review queue would start empty and stay
disconnected from the one place vendors actually submit a document today.

`documents.manage` reuses the `roles`/`permissions`/`role_permissions` tables decision D15
already created — this is a data-only migration (two new rows: a permission, and its
`role_permissions` assignment to Owner and Manager), not a schema change. Documents are
treated as part of the store's compliance profile and gated by the same permission tier as
`profile.manage`, rather than inventing a document-specific permission split (Catalog Manager
managing licenses makes no more sense than Finance managing them — there is no role for which
partial document access is the right answer).

**`draft` is not a reachable status.** Spec §24 lists `draft → pending_review → verified →
rejected → expired → suspended` as the lifecycle, but every `vendor_documents` row is created
together with an uploaded file — there is no code path that produces a documentless "draft"
row a vendor edits before submitting. Claiming a state no workflow reaches would be worse than
omitting it (same principle as D4 excluding `pickup`).

**File security (§55):** documents are stored on the `local` disk — the same private disk
`commercial_register_file` already used (`storage/app/private`, not the `public`-disk
`/storage` symlink) — under a server-generated path (`Storage::putFile()`), never the
client-supplied filename. The only way to read one back is `Storage::download()` from inside
an authorized controller action; there is no public URL for a `vendor_documents` file.

**Resubmission replaces rather than accumulates.** `unique(vendor_id, type)` plus
`VendorDocumentService::upload()`'s upsert means a vendor fixing a rejected document overwrites
the old file and row rather than creating a growing history — only the current document per
type is ever meaningful to a reviewer, and the old file is deleted from disk once the new one
is confirmed stored.

---

## Deferred — not built, not documented as built

§11 payments, §12 returns, §13 refunds, §14 shipping, §19 invoices, §20 vendor
ledger/settlements, §22 vendor staff, §23 RBAC (vendor-scoped), and §24 vendor documents
are now implemented — see [COMMERCE_ARCHITECTURE.md §12–17](COMMERCE_ARCHITECTURE.md#12-payments-phase-c),
[VENDOR_STAFF_RBAC.md](VENDOR_STAFF_RBAC.md), [VENDOR_DOCUMENTS.md](VENDOR_DOCUMENTS.md),
and [TEST_COVERAGE.md](../testing/TEST_COVERAGE.md).

Still deferred: §23 RBAC for admin/employee/syndicate/customer (no requirement yet) ·
§25 product documents · §29 comparison · §33 notification preferences · §35 audit log ·
§36 reports · §37 exports · §38 CMS · §39 SEO · §40–52 UI redesign.

These remain open scope. Their absence is stated here so no reader mistakes a plan for
an implementation.
