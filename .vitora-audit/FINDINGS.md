# FINDINGS

## F-SHARD1-01
SEVERITY: P2
CONFIDENCE: MEDIUM
FILE:LINE: app/Http/Requests/Admin/UpdateUserRequest.php:42
ROLE: Admin
FLOW: Admin edits any user via generic `PATCH /api/admin/users/{user}` form
EVIDENCE: `'type' => [..., Rule::in([TYPE_USER, TYPE_ADMIN, TYPE_VENDOR, TYPE_SYNDICATE, TYPE_EMPLOYEE])]` permits changing an existing plain USER's `type` to VENDOR or SYNDICATE through this endpoint. `StoreUserRequest` (sibling, same dir) explicitly excludes VENDOR/SYNDICATE from its `type` rule with a comment stating the reason: those types require an atomically-created paired row (`vendors`/`syndicates`), and an account with `type=vendor` but no `vendors` row is permanently broken (`User::managedVendor()` always null). `UpdateUserRequest`'s comment claims this is safe because the edit form always "resubmits its current type" for vendor/syndicate accounts — but the Request class itself does not enforce that the submitted type equals the current type; it only enforces that the submitted value is one of the five allowed constants.
ROOT_CAUSE: Validation layer alone does not prevent type-widening; correctness depends entirely on `UserController::update()` (not yet read — Admin backend is Shard 3) either ignoring changes to `type` for non-generic accounts or the admin frontend never sending a changed value. Not yet confirmed as exploitable.
IMPACT (if confirmed): Admin (or admin-tier automation) could create a "vendor" user with no vendor row via a crafted PATCH, producing a locked-out/broken account. Admin-only, not cross-tenant, so capped at P2 even if confirmed.
MINIMAL_FIX (if confirmed): In the controller, either ignore `type` transitions into VENDOR/SYNDICATE on this endpoint, or add `Rule::in` to only accept the user's current type value for those two constants.
REGRESSION_TEST (if confirmed): Admin PATCHes a TYPE_USER account with `type=vendor`; assert the account is not persisted as vendor-typed with no vendor row (either 422 or type is ignored).
STATUS: FIXED (Shard 3, checkpoint 4)
UPDATE (Shard 2): read `app/Services/Admin/UserService.php::update()` — `$user->update(array_filter($data, fn ($value) => $value !== null))`. This forwards EVERY non-null key in `$data` straight to the model with no allowlist, unlike its siblings in the same directory: `VendorService::update()` and `SyndicateService::update()` both use `array_intersect_key($data, array_flip([...]))` to allowlist fields first.
UPDATE (Shard 3): read `app/Http/Controllers/Api/Admin/UserController.php::update()` — confirmed it calls `$this->userService->update($user, $request->validated())`, i.e. `UpdateUserRequest`'s validated `type` (which allows `TYPE_VENDOR`/`TYPE_SYNDICATE` unconditionally) reaches `UserService::update()`'s unguarded pass-through with nothing in between to stop it. CONFIRMED exploitable: any admin could PATCH a plain `TYPE_USER` account with `type=2` (vendor) and produce a `users` row with no paired `vendors` row — the exact broken-account shape `StoreUserRequest` already goes out of its way to prevent at creation time (its own comment names this failure mode).
FIX APPLIED: `app/Http/Requests/Admin/UpdateUserRequest.php` — added a closure rule on `type` that rejects `TYPE_VENDOR`/`TYPE_SYNDICATE` unless it equals the target user's *current* type (i.e. only a same-value resubmit is allowed, matching the documented legitimate use case; any actual widening is now a 422). No change to `UserService::update()` itself — the allowlist gap there is real but out of scope for a minimal patch; the request-layer guard closes the actual exploitable path.
REGRESSION TEST: `tests/Feature/AdminUserManagementTest.php` — new test `'rejects widening a plain user into a vendor-type account through the generic admin edit endpoint'`. Ran `php artisan test --filter=AdminUserManagementTest`: 4 passed (13 assertions), including the two pre-existing tests proving same-type resubmission still works.

## F-SHARD2-01
SEVERITY: P3
CONFIDENCE: HIGH
FILE:LINE: app/Models/SharedProductDetail.php:64 (fillable), casts:78
ROLE: n/a (data-integrity/dead-code, not access-control)
FLOW: n/a — no caller currently sets this column
EVIDENCE: Migration `2026_07_18_005450_create_shared_product_details_table.php:29` adds `deleted_at timestamp nullable`. The model does NOT `use SoftDeletes` (contrast: `app/Models/UserAddress.php` does, correctly). `deleted_at` is listed in `$fillable` and cast to `datetime`, but with no `SoftDeletes` trait there is no global scope excluding soft-deleted rows from normal queries, and no `trashed()`/`restore()`/`withTrashed()`. Searched `app/Services/ProductService.php` (the only service that creates/updates `SharedProductDetail`, via `updateOrCreate`) — it never sets `deleted_at`. No other caller found in `app/`.
ROOT_CAUSE: Column and fillable/cast entry appear to be leftover scaffolding for a soft-delete feature that was never wired up with the trait.
IMPACT: Currently inert — nothing sets the column, so no live bug. Latent risk: if any future code (or a raw mass-update) sets `deleted_at` on this model expecting Eloquent soft-delete semantics, the row will NOT be hidden from any query (no global scope), producing the opposite of the intended effect — a "deleted" product detail still fully visible everywhere.
MINIMAL_FIX: Either add `use SoftDeletes;` to `SharedProductDetail` (if soft-delete is actually wanted) or drop `deleted_at` from `$fillable`/casts and the migration follow-up (if not). Out of scope for this audit to decide business intent — recording only.
REGRESSION_TEST: N/A until a fix direction is chosen.
STATUS: CONFIRMED (as a defect in current code shape), fix deferred — P3, no active exploit, business-intent decision needed from the user before patching.
RELATED_FILES: app/Models/UserAddress.php (correct pattern for comparison), app/Services/ProductService.php (only current writer)

## F-SHARD2-02
SEVERITY: P2
CONFIDENCE: MEDIUM
FILE:LINE: app/Services/Commerce/ReturnService.php:136 (`alreadyReturnedQuantity` check), 154 (create)
ROLE: customer (return requester)
FLOW: `POST /api/returns` (OrderReturnController::store, not yet read — Shard 4/5) → `ReturnService::attemptRequest()`
EVIDENCE: `attemptRequest()` computes `$remaining = $orderItem->quantity - $this->alreadyReturnedQuantity($orderItem->id)` via a plain `SUM()` read, checks `$quantity > $remaining`, then later `DB::transaction` creates the `OrderReturn` + `ReturnItem` rows — with no row lock (`lockForUpdate`) on the order/order item and no DB-level constraint capping total returned quantity per order item. Contrast with every other quantity-affecting flow in this codebase, which all use either a conditional `UPDATE ... WHERE` (stock consume/restore, order/return/shipment/document status transitions, payment settlement) or `lockForUpdate()` (coupon claim, vendor settlement) specifically to close this exact race.
REPRODUCTION (not executed — code-path analysis): two near-simultaneous `POST /api/returns` requests for the same order item, each requesting the full remaining quantity, could both read the same `$remaining` before either inserts its `ReturnItem` row, and both pass the `$quantity > $remaining` check.
ROOT_CAUSE: the per-item returned-quantity cap is enforced via a read-then-write check with no lock or atomic constraint, unlike the analogous stock/coupon/settlement flows elsewhere in this codebase.
IMPACT: a successful race would let a customer's return requests jointly exceed the order item's purchased quantity, inflating `refundable_amount` across two `OrderReturn` rows beyond what was actually bought — a real money-adjacent correctness bug, though it requires precise concurrent timing from the same authenticated customer to trigger, and is bounded by `Refund::amount <= Payment::refundableAmount()` at the *payment* level (RefundService.php:63,116) so it cannot itself cause an overdraw past what was paid — the exposure is two valid-looking returns each claiming the same stock/items, not unbounded money loss.
MINIMAL_FIX: wrap the read-check-create in `DB::transaction` with `lockForUpdate()` on the relevant `OrderItem` row(s) (mirroring `CouponService::claim()`'s pattern), so the remaining-quantity check and the `ReturnItem` insert are atomic.
REGRESSION_TEST: two concurrent `ReturnService::request()` calls for the same order item requesting the full remaining quantity each; assert only one succeeds and the second throws `returns.quantity_exceeds`.
STATUS: CONFIRMED (code-shape gap, not exploited/reproduced live — no concurrency test was run, this is static analysis of the locking pattern only)
RELATED_FILES: app/Services/Commerce/CouponService.php (correct lockForUpdate pattern for comparison), app/Services/Commerce/CheckoutService.php::consumeStock (correct conditional-UPDATE pattern for comparison)

No other findings at P0/P1/P2 confirmed in Shard 1/2. See STATE.md NEEDS_TRACE list for every "policy defined but controller invocation not yet verified" item — these are not findings, they are open verification items for later shards (controllers weren't in scope for Shard 1).

## Carried from Checkpoint 0 (INFO, no action)
- VendorMapRequest/VendorAnalyticsRequest `authorize()` returning true/`user()!==null` is safe: role gating happens at route-group middleware (confirmed by reading bootstrap/app.php in this shard) + Syndicate controller re-checks tenant scope per request.
- `trustProxies(at: '*')` in bootstrap/app.php — intentional per inline comment (app confirmed behind nginx in production). Flag as residual risk to verify env-level (not app-level) — if the app were ever exposed without a fixed reverse proxy in front, `X-Forwarded-*` headers become spoofable. Not actionable in this codebase; recorded for RESIDUAL_RISK.md.
