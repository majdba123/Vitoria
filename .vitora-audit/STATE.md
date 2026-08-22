AUDIT_CAMPAIGN_STATUS: IN_PROGRESS — checkpoint 3 (Shard 2 COMPLETE)

CHECKPOINT: 3
BASE_COMMIT: 1a4f1edb623d83d1ae9673c589e4dcbb77e8febf
WORKTREE_STATUS: dirty, unchanged since checkpoint 1/2 (re-verify with git status --short before trusting on next resume)

TOTAL_FILES: 1001
IN_SCOPE_TEXT_FILES: not yet fully classified (still owed — cheap metadata-only pass, do first if a session has spare time before Shard 3 substance)
REVIEWED_FILES: 168 (122 at checkpoint 2 + 33 new full-file reviews this run: remaining 7 Commerce services, 3 Admin services incl. VendorService promotion, ApplicationCacheService, AuditLogService, ImageOptimizationService, 7 Import files, 3 Notification files, 4 Product/* files, ReportService, SelectedProductTypeService, 3 Vendor/* files — see FILE_LEDGER.tsv rows tagged SHARD2 for exact list)
PENDING_FILES: everything outside Shard 1 + Shard 2
BLOCKED_FILES: 0

SHARD2_DISCOVERED: 86
SHARD2_REVIEWED: 86
SHARD2_PENDING: 0

COMPLETED_SHARDS: SHARD 1 (full), SHARD 2 (full — all app/Models/** and app/Services/** read first line to last line; app/Actions, app/Support, app/Enums confirmed absent)

CURRENT_FINDINGS:
- F-SHARD1-01: P2, BLOCKED (confidence raised to HIGH this run — see evidence update in FINDINGS.md: UserService::update() forwards $data with NO allowlist, unlike sibling VendorService/SyndicateService::update() which both use array_intersect_key. Still formally blocked pending UserController::update() read in Shard 3 to confirm what it actually passes as $data.)
- F-SHARD2-01: P3, CONFIRMED (code-shape defect, inert) — SharedProductDetail.deleted_at fillable/cast without SoftDeletes trait
- F-SHARD2-02: P2, CONFIRMED (code-shape gap, not live-reproduced) — ReturnService::attemptRequest() has no row lock / conditional-update on the per-order-item returned-quantity cap, unlike every analogous quantity/money flow elsewhere in the codebase (stock consume/restore, coupon claim, settlement, all status transitions). Bounded downstream by Payment::refundableAmount() check in RefundService, so not an unbounded money-loss path, but a real race nonetheless.

NEW_FINDINGS_THIS_RUN: 1 (F-SHARD2-02); F-SHARD1-01 evidence strengthened (not newly created)
FIXES_APPLIED: 0. F-SHARD2-01/02 are correctly left as record-first (P3/P2 with no P0/P1 urgency); F-SHARD1-01 still blocked on the controller read that would make the fix target concrete.

TESTS_RUN this checkpoint:
- php artisan test --compact --filter=CheckoutAndOrderLifecycleTest → 23 passed (92 assertions) — closes out Shard 2 Commerce/* review with real behavioral evidence, not just code reading.
(Carried from earlier checkpoints, still valid: VendorMapFeatureTest 22/22, VendorAnalyticsTest 9/9, AdversarialSecurityAuditTest+ApiSecurityAndRateLimitTest 19/19, npm run build clean.)

CROSS_SHARD_TRACES_PENDING:
  - All 10 Policy→Controller traces from checkpoint 1 (Shard 3/4/5 territory) — unchanged, still open.
  - F-SHARD1-01 → app/Http/Controllers/Api/Admin/UserController.php (Shard 3, now the first thing to check)
  - Vendor/UpdateProfileRequest current_password check → VendorProfileController::update() (Shard 5)
  - Vendor/UpdateProductRequest ownership → route binding + ProductPolicy::update() invocation (Shard 5)
  - F-SHARD2-02 → app/Http/Controllers/Api/OrderReturnController.php (or wherever ReturnService::request() is actually called from) — confirm no additional guard exists at the controller layer that would close the race (unlikely given the pattern seen elsewhere always lives in the service, but not yet verified) — Shard 4/5 territory.
  - CheckoutService/OrderStatusService/ShippingService/PaymentService/InvoiceService/CouponService/VendorLedgerService are now ALL fully reviewed and cross-confirm each other correctly (recordSale() called exactly once from OrderStatusService::transition() on COMPLETED, confirmed by direct code read — this closes the "NEEDS_TRACE" item opened in checkpoint 2 about commission math's single call site).

NEXT_SHARD: SHARD 3 — ADMIN BACKEND
Scope per campaign order: Admin controllers, Admin services (already done in Shard 2 — SyndicateService/UserService/VendorService — do not re-read unless changed), Admin resources, Admin actions, admin-specific authorization flow, Admin API contracts.

NEXT_FILES (in order):
  1. app/Http/Controllers/Api/Admin/UserController.php — FIRST, resolves F-SHARD1-01
  2. app/Http/Controllers/Api/Admin/VendorController.php — promote from CP0 diff-only to full read
  3. Remaining app/Http/Controllers/Api/Admin/**/*.php (full directory — SyndicateController, CategoryController, SubcategoryController, CityController, CouponController, OrderController, ReturnController, RefundController, InvoiceController, ShipmentController, ShippingConfigController, LedgerController, SettlementController, VendorStaffController, ProductDocumentController, AuditLogController, VendorDocumentController, NotificationController, ContactMessageController, FooterSettingController, PageController, BannerController, ExportController, ReportController, DashboardController, VendorCommissionController, ProductController if present under Admin)
  4. app/Http/Resources/Admin/**/*.php (check exact list via git ls-files)
  5. app/Http/Controllers/Admin/**/*.php (the web/Inertia-facing Admin controllers, distinct from Api/Admin — CategoryController, SubcategoryController, CityController, ContactMessageViewController, AboutUsController, ProductReviewViewController per routes/web.php)
  After Admin backend: reconcile ledger, then SHARD 4 (Syndicate backend) per campaign order.

DO_NOT_REREAD: every row in FILE_LEDGER.tsv marked REVIEWED, unless `git status --short` shows the file changed since this checkpoint (verify at the start of next session).
