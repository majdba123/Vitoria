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

## D17 — Product documents: a separate table from `vendor_documents`, public once approved

**Evidence:** spec §25 explicitly warns "do not expose private vendor documents," right
after describing a feature that sounds superficially like §24's vendor documents — a
document, a type, a review status. The critical functional difference is visibility: a
vendor document is never public at any status; an *approved* product document must be
downloadable by any storefront visitor, unauthenticated, on the product page.

**Decision:** `product_documents` is a fully separate table and model, sharing no schema or
review code with `vendor_documents` beyond the same upload-validation convention and the same
private `local` disk. Files are never moved to the `public` disk — the public download action
checks live `status = 'approved'` against the database on every request, so a disabled or
rejected document is unreachable on the very next request with no stale public URL left
pointing at it, and there is nothing to accidentally leak by storing the file in the wrong
place.

**No `unique(product_id, type)`**, unlike `vendor_documents`: a product can legitimately carry
two leaflets in different languages at once, so each upload is its own row rather than
replacing the last one.

**`ProductController::publicShow()` was not modified to embed documents.** That response is
cached per-product via `Cache::tags(['products'])`; threading document visibility through it
would complicate cache invalidation (a document being approved or disabled would need to bust
the product cache too) for a feature that has its own dedicated, uncached endpoint anyway.
`GET /api/products/{product}/documents` exists specifically so the storefront's "Documents &
Downloads" section doesn't need to touch the cached payload at all.

**`products.manage` is reused, not a new permission** — product documents are catalog content,
not a separate authorization concern from photos or listings, and the RBAC tables already
support exactly this from decision D15 without a migration.

---

## D18 — Notification preferences: in-app only, critical categories bypass storage

**Evidence:** §33 explicitly permits scoping this to what already works ("channels may
include... do not implement SMS or push unless infrastructure already exists"). Auditing
the actual codebase found that reasoning applies to email too: there is no
`app/Notifications/` directory, no `Mail::` call, no `->notify()` call anywhere in `app/`,
and `MAIL_MAILER` defaults to `log` — nothing in this repository has ever sent a real
email. `Notifiable` is declared on `User` but never invoked.

**Decision:** `notification_preferences` covers the in-app channel only — the one channel
with real, working delivery (`AdminNotification` + Reverb broadcast). No `channel` column
was added to the schema: unlike D7/D12's zero-rate money columns (needed by a real total
formula even before a rate is configured), a channel column here would hold exactly one
ever-used value, which is a speculative column, not a structural one. Offering an "email
notifications" toggle that delivers nothing would be the same fake-feature problem D9
already rejected for payment gateways — worse than not offering the control at all, because
it looks like it does something.

**Four categories**, derived from auditing all 13 existing `NotificationService` event
methods by what they actually communicate, not by which actor receives them:
`order_updates` and `account_security` are transaction/access-critical (§33: "should not be
accidentally disabled") and their target lists are never filtered; `vendor_compliance` and
`marketing` are operational/promotional and mutable.

**Critical categories bypass the permissions table entirely** —
`NotificationPreferenceService::isEnabled()` returns `true` for them before touching the
database, the same defense-in-depth shape as `User::hasVendorPermission()`'s owner bypass
(decision D15). A stale or directly-written preference row can never suppress an order,
return, refund, or account-access notice — proven in
`NotificationPreferencesTest::it never disables a critical category even from a
directly-written row`, which writes exactly such a row and confirms the service still
returns `true`.

**Public (broadcast-to-everyone) notifications are filtered at read time, not creation
time.** A private notification's recipient list is filtered before its `recipients()->sync()`
and broadcast — the disabled user simply isn't created as a recipient. A public notification
has no recipient rows at all (that's what "public" means in this schema), so
`NotificationController::index()`/`markAllRead()` instead exclude a disabled category from
the visibility query per request. This is why `admin_notifications` gained a nullable,
unbackfilled `category` column: historical rows have none and are therefore never hidden by
a preference that didn't exist when they were created.

---

## D19 — Product comparison: stateless, no table

**Evidence:** spec §29 says outright: "comparison state may be session/local storage; it
does not require a database table unless persistence is justified." Nothing in this
program's other features (favourites, cart, orders) reads or writes a saved comparison
list, and no requirement calls for one — there is no persistence need to justify.

**Decision:** `ProductComparisonService::compare()` takes a list of product ids and
returns a normalized payload in one stateless call — no `product_comparisons` table, no
per-user saved list. `GET /api/products/compare?ids=1,2,3` is the entire surface; which
products a shopper is comparing lives in the client, exactly as the spec suggests.

**"Comparison only between sensible product categories"** is enforced by requiring every
product in the set to share the same `categories.type` (`agriculture` or `veterinary`) —
reusing the same type split `Product::scopeForCategoryType()` already uses elsewhere in
this codebase, rather than inventing a new grouping concept. Mixing the two would compare
attributes that mean nothing across them (a dosage form against a fertilization method),
which is exactly what §29 says not to do.

**Specs shown are type-scoped**, not a flat merge of every possible field:
`ProductComparisonService::specsFor()` returns only the veterinary fields
(`active_ingredients`, `concentration`, `dosage_form`, `routes_of_administration`,
`target_species`) for veterinary products, and only the agricultural fields
(`agricultural_product_type`, `formulation`, `application_methods`, `target_crops`,
`target_pests`) for agricultural ones — never both, and never a field the compared
products' shared type doesn't support.

---

## D20 — Audit log: only where no domain-specific trail already exists

**Evidence:** several of §35's own named examples already have a purpose-built, tested
audit trail: order status changes are `order_status_histories` (previous status, new
status, actor, actor type, reason, notes, timestamp — decision D4); a return's reviewer
and decision are `order_returns.reviewed_by_user_id`/`reviewed_at`/`rejection_reason`; a
refund's lifecycle is its own `status`/`completed_at`/`initiated_by_user_id`. These were
each built, in earlier phases of this program, specifically to answer "who changed this
and when" for their own entity.

**Decision:** `audit_logs` is *not* wired into `OrderStatusService`, `OrderCancellationService`,
`ReturnService`, or `RefundService` — duplicating what those tables already record into a
second, generic table would be redundant data with no new capability, just two places to
look for the same fact. `AuditLogService::record()` is instead called from the actions
spec §35 names that have **no** existing equivalent: vendor approval/suspension
(`Admin\VendorController`), product moderation (`ProductController::updateStatus`/
`toggleActive`), coupon create/update/delete (`Admin\CouponController`), settings changes
(`Admin\FooterSettingController`), user role changes (`Admin\UserController`, only when
`type` actually changes — a name edit is not logged), and vendor-ledger financial
adjustments/settlements (`VendorLedgerService`, which already captures `created_by_user_id`
per entry but not IP/user-agent/request-id).

**Redaction is unconditional, not opt-in.** `AuditLogService::redact()` strips a fixed list
of keys (`password`, every token/secret variant, card/CVV fields, `otp`) from `old_values`/
`new_values` before the row is ever created — a caller cannot accidentally log a secret by
passing one in, because the service never trusts what it's handed.

**`request_id` correlates multiple audit rows from one request** without adding
distributed tracing infrastructure: `AssignRequestId` middleware (appended to the `api`
group, alongside `SetLocale`/`ApplyUserTimezone`) reuses an upstream `X-Request-Id` header
when present, otherwise generates a UUID, and echoes it back on the response — a caller
that already sends a correlation id keeps it; one that doesn't still gets a consistent id
across every log line the request produces.

---

## D21 — Reports and exports: computed on demand, no new schema

**Evidence:** §36 (reports) and §37 (exports) ask for aggregate views over data that
already exists in full — orders, order items, vendor ledger entries, products, vendors.
There is no requirement for scheduled report generation, saved report definitions, or
report snapshots that must survive independently of the underlying rows.

**Decision:** `ReportService` runs plain aggregate queries (`sum`/`count`/`group by`)
against `Order`, `OrderItem`, and `VendorLedgerEntry` on every request — no new tables,
no cached/materialized report rows, no scheduled jobs. `Admin\ReportController` exposes
three read endpoints: `GET /api/admin/reports/sales` (revenue and order count over a date
range, grouped by day or month, plus a status breakdown), `GET /api/admin/reports/vendors`
(per-vendor revenue alongside net vendor-ledger movement over the same range — kept as two
separate figures rather than merged, since a ledger adjustment and an order's revenue are
not interchangeable facts), and `GET /api/admin/reports/products` (top products by revenue
within the range, using `order_items.line_total`, since that's what a customer actually
paid — not the product's list price, which can have since changed).

Cancelled orders are excluded from all three reports' totals — spec §8's existing state
machine already treats a cancelled order as not-fulfilled, so counting its `grand_total`
toward revenue would overstate real sales.

**Exports reuse `App\Services\Import\CsvFile`**, the same streaming CSV helper already
built for the CSV import templates (UTF-8 BOM, `fputcsv`, `text/csv` streamed response) —
adding a second CSV library or hand-rolled writer would duplicate working code.
`Admin\ExportController` exposes `GET /api/admin/exports/{orders,products,vendors}`, each
accepting the same filter query parameters as the equivalent admin `index` endpoint (status,
vendor, date range, category) and capped at 5,000 rows per request to keep a single export
request bounded. Exports are not audit-logged: they are read-only downloads of data an admin
can already see via the paginated index endpoints, not a state change (see D20 above for why
only mutations are audit-logged).

---

## D22 — CMS: two flat tables, no page-builder, no revision history

**Evidence:** §38 asks for admin-managed static content (About/Terms/Privacy/FAQ-style
pages) and homepage banners. Nothing in the spec calls for a page-builder (arbitrary
block layouts), draft/review workflow, or version history — pages and banners each need
exactly one current version, editable in place.

**Decision:** two flat tables. `pages` (`slug`, bilingual `title_en`/`title_ar` and
`content_en`/`content_ar`, `meta_title`/`meta_description`, `is_published`) and `banners`
(`image_path`, optional `link_url`, `sort_order`, `is_active`, optional `starts_at`/
`ends_at` window). `content_en`/`content_ar` are stored as plain long text — the admin
frontend is responsible for whatever rich-text editing it wants; the API does not attempt
to sanitize or interpret markup, since page content is admin-authored, not user-submitted.

Public reads (`GET /pages/{slug}`, `GET /banners`) only ever return published pages /
currently-visible banners (`Banner::currentlyVisible()` checks `is_active` and the
optional `starts_at`/`ends_at` window) — an admin previewing a draft page must use the
admin `show` endpoint, which returns regardless of `is_published`.

Banner image uploads reuse the same `public` disk pattern as product photos
(`ProductService::addPhotos`) rather than inventing a new storage convention. Deleting a
banner deletes its stored file; replacing a banner's image on update deletes the old file
after the new one is stored successfully.

Both page and banner writes are audit-logged (`page.created`/`.updated`/`.deleted`,
`banner.created`/`.updated`/`.deleted`) under the same policy as D20: they are admin
mutations of publicly-visible content, so the same "who changed this and when" question
applies as it does to coupons or footer settings.

---

## D23 — SEO: meta fields plus sitemap/robots, no URL slugs

**Evidence:** §39 asks for search-engine-facing metadata and discoverability. Product and
category URLs are id-based throughout the existing frontend and API
(`/api/products/{product}`, `/api/categories/{category}`) — introducing slugs would be a
routing change touching every existing link, with no requirement in the spec calling for
it specifically (a numeric URL is still crawlable and indexable; it's just not as
readable). That rewrite was out of scope for this pass.

**Decision:** additive-only migration adds nullable `meta_title`/`meta_description` to
`products` and `categories` (already present on `pages` from §38). Wired into the
existing Admin and Vendor product store/update requests via a shared `metaRules()` helper
on `InteractsWithProductDetails`, and into the admin category store/update requests — no
new endpoints needed since both models already mass-assign their validated payload.

`GET /api/sitemap.xml` lists the homepage, every published page, every category, and
every active+approved product (pending/rejected/inactive products are excluded — nothing
to gain from indexing something a shopper cannot actually buy). `GET /api/robots.txt`
allows crawling by default and disallows `/api/admin/` and `/api/vendor/`, pointing at the
sitemap. Both are served under `/api/` (matching where every other route in this app
lives) rather than at the bare domain root — the frontend/edge server is responsible for
proxying `/sitemap.xml` and `/robots.txt` to these routes if search engines need them at
the conventional root path; that reverse-proxy config is outside this Laravel app's
repository.

---

## Deferred — not built, not documented as built

§11 payments, §12 returns, §13 refunds, §14 shipping, §19 invoices, §20 vendor
ledger/settlements, §22 vendor staff, §23 RBAC (vendor-scoped), §24 vendor documents,
§25 product documents, §29 product comparison, §33 notification preferences (in-app
only), §35 audit log, §36 reports, §37 exports, §38 CMS, and §39 SEO are now
implemented — see
[COMMERCE_ARCHITECTURE.md §12–17](COMMERCE_ARCHITECTURE.md#12-payments-phase-c),
[VENDOR_STAFF_RBAC.md](VENDOR_STAFF_RBAC.md), [VENDOR_DOCUMENTS.md](VENDOR_DOCUMENTS.md),
[PRODUCT_DOCUMENTS.md](PRODUCT_DOCUMENTS.md), and [TEST_COVERAGE.md](../testing/TEST_COVERAGE.md).

Still deferred: §40–52 UI redesign (frontend scope, tracked separately).

**§23 RBAC for admin/employee/syndicate/customer — deliberately not built.** Re-evaluated
after §22's vendor RBAC shipped, since that's the obvious template to reuse. It doesn't
apply here: D3's original reasoning still holds. Admin, employee, syndicate, and customer
are four fixed, non-overlapping `users.type` values with no internal sub-roles anywhere in
the codebase — unlike a vendor, which is one account that can have many staff members each
needing different permissions *within* that one vendor. There is no second admin-type actor
that needs a narrower permission set than "admin," no employee sub-role, and no
requirement describing one. Building a `roles`/`permissions` layer for types that only
ever have one role each would be exactly the premature generalization D3 rejected the
first time — a parallel table with a 1:1 mapping to `users.type`, adding a lookup for a
fact `users.type` already encodes directly. If a real requirement for admin sub-roles
(e.g. a "support admin" who cannot touch vendor commissions) shows up, the existing
`roles`/`permissions`/`role_permissions` tables the vendor-staff feature built are already
generic enough to extend to a second actor type without a schema change — only new rows.

These remain open scope. Their absence is stated here so no reader mistakes a plan for
an implementation.
