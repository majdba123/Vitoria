# Final Stakeholder Document — Requirements Resolution

Regression pass and audit across every stakeholder requirement addressed in
this body of work, per the final task's instruction: "do not declare the
stakeholder document complete while any requirement is silently ignored."

| Requirement | Root Cause | Implementation | Files | Tests | Status |
|---|---|---|---|---|---|
| **Admin – Users**: "حذف التجار – إبقاء مستخدمون التطبيق" (remove merchants, keep app users). Admin "Users" page must show application customers only — no vendors/admins/employees/syndicate accounts. | The shared Users page (`admin.users.index`) defaulted to listing *every* account type mixed together, with no `type` filter applied. | Page now defaults to `type=0` (customers). Vendors stay fully separate under their own admin screen; the redundant "Customers" sidebar link was removed (Users now IS Customers by default, Employees stays an explicit exception). Added search/city/profession/status filters, server-side pagination, order-count/total-purchases/last-order aggregates on the customer list, and profession/order-summary/recent-orders on the customer detail page — reusing the existing `preferred_product_type` field for "مهندس زراعي / طبيب بيطري" rather than adding a new column. | `app/Http/Controllers/Api/Admin/UserController.php`, `resources/js/Pages/Admin/Users/{Index,Show}.jsx`, `resources/js/lib/nav-admin.js`, `lang/{ar,en}/admin.php` | `tests/Feature/AdminCustomerDirectoryTest.php` (5 tests: customer-only listing, search, profession filter + pagination, order aggregates without leaking password/token, non-admin 403) | **FIXED** |
| Admin – "إجمالي العمولات — ربط العمولات لجميع التجار تظهر على صفحة الأدمن" (total commission across all vendors on the admin page). | No platform-wide commission aggregate existed; only per-vendor commission stats were exposed. | `VendorLedgerService::adminSummary()` aggregates gross sales, commission, refunds, adjustments, settled and outstanding across every vendor in one grouped DB query over the same immutable ledger each per-vendor screen already reads (no separate/duplicated computation), plus a top-vendors-by-commission breakdown. Exposed at `GET /api/admin/financials/summary` and rendered on a new Admin Financials page. | `app/Services/Commerce/VendorLedgerService.php`, `app/Http/Controllers/Api/Admin/FinancialSummaryController.php`, `resources/js/Pages/Admin/Financials/Index.jsx`, `routes/api_admin.php`, `routes/web.php` | `tests/Feature/AdminFinancialSummaryTest.php` | **FIXED** |
| Syndicate – commission/report wording. | The syndicate sidebar grouped "Sales" + "Reports" under the label "العمولات" (Commissions), but `SyndicateDashboardService::salesStats()` only exposes gross sales — no vendor-ledger/commission figure is ever shown to a syndicate role. The label implied a financial figure that doesn't exist in that view. | Relabeled the group to "المالية" (Financial) instead of "العمولات" (Commissions), matching what the screen actually contains. | `lang/ar/syndicate.php` | Covered indirectly by existing syndicate dashboard/report tests (no numeric behavior changed, wording only). | **FIXED** |
| Reports formatting — vendor performance report. | The vendor performance report PDF/print header didn't identify which syndicate a vendor is under. | Added a `syndicate` label/value to the report's identity block. | `lang/{ar,en}/reports.php`, `resources/views/reports/syndicate-vendor-header.blade.php`, `resources/views/reports/syndicate-vendor.blade.php`, `resources/views/reports/syndicate-general.blade.php` | `tests/Feature/SyndicateVendorReportPdfTest.php` (4 tests: PDF totals match underlying completed orders, HTTP-served report, cross-syndicate access denied, non-syndicate access denied) | **FIXED** |
| Vendor performance report — RTL bar-chart label overlap on small-value bars (screenshot: "أفضل المنتجات", values 84/72 overlapping their labels). | Recharts rendered near-zero-value bars too short, so the value label (anchored at bar-end) landed in the same x-region as the category label — a bar-length problem, not the axis-width/truncation issue it first looked like. | Added `minPointSize={130}` to the shared ranking chart's `<Bar>`, a floor on rendered pixel length, empirically tuned against live `getBoundingClientRect()` measurements until every row had a safe gap. This component is shared by Vendor360 (vendor performance), Admin Dashboard ("Top vendors"), and the Syndicate Dashboard. | `resources/js/Components/shared/dashboard/HorizontalRankingChart.jsx` | Verified live via Playwright (pixel-level DOM measurement, screenshot) on Vendor360; not re-screenshotted on the Admin/Syndicate dashboards that share the component (low risk — the change only raises a floor, never shrinks existing longer bars). | **FIXED** (Vendor360, verified) / **ALREADY_FIXED** (shared component covers Admin/Syndicate dashboards without further change) |
| Vendor invoice/print. | No print-optimized invoice view existed with verified authorization and correct conditional totals. | Print-friendly invoice page: unaltered quantities/totals, discount/tax lines shown only when the invoice actually carries them, a print control hidden from the printed output itself, and denies access to a customer who doesn't own the order. | (invoice print view + controller, already present in the working tree) | `tests/Feature/InvoicePrintPageTest.php` (4 tests) | **ALREADY_FIXED** (present before this audit pass; re-verified in this regression run) |
| Vendor orders table — cramped single-line rows, no visible order date. | `Vendor360.jsx`'s `OrderRow` packed order number, amount, and status into one line with no date. | Reworked to a two-line row: order number + status badge on line one, date (localized, `dir="auto"`) + amount on line two. | `resources/js/Components/vendor360/Vendor360.jsx` | Covered by existing Vendor360/order-list feature tests (layout-only change, no API behavior changed). | **FIXED** |
| Data archiving — "أرشفة المعلومات": keep historical financial/legal records available but out of active operational clutter, without hard-deleting them. | Never formally documented; relied on undocumented convention (no destroy routes, status enums) that a future change could accidentally violate. | Audited every financial/legal entity (orders, invoices, refunds, returns, vendor ledger entries, vendor settlements, audit logs, vendor/product documents) and confirmed **no destroy route exists for any of them** — this was already true in code, not newly built. Documented the policy per entity: status/date scope is the existing "archived" signal (e.g. vendor/product document `expired`/`rejected`/`suspended`); no new `archived_at` columns, no soft-deletes, no batch archive job were added, since none of that was needed. Also found and fixed two pre-existing untranslated user-facing guard messages (vendor-deletion-blocked, customer-deletion-blocked) that are the enforcement mechanism for this policy. | `docs/architecture/DATA_RETENTION_AND_ARCHIVING.md` (new), `lang/ar.json`, `lang/en.json` | `tests/Feature/FinancialRecordImmutabilityTest.php` (5 tests: no destroy route on any of the 7 protected resources, direct DELETE rejected on all of them with data intact, vendor-with-history deletion blocked, customer-with-history deletion blocked, audit log has no update route) | **FIXED** (documented) / **ALREADY_FIXED** (the underlying protection itself) |
| Data backup — "احتياطي المعلومات — كيف؟": production-safe, scheduled, non-silent-failure backup of database and uploaded files. | No backup mechanism existed beyond a one-off pre-deploy `mysqldump` snapshot tied to releases (`.github/workflows/deploy.yml`) — nothing recurring, nothing covering uploaded files, no retention, no health monitoring. | Added `spatie/laravel-backup` (justified: DB dump + file archive + retention + health monitoring + failure notifications, tested against MySQL/SQLite, with no bespoke code to maintain). Configured a dedicated private `backups` disk (never public/reachable), included `storage/app/private` + `storage/app/public` + `.env`, excluded `vendor/node_modules/.git/build artifacts/logs/framework cache/tmp`. Scheduled `backup:run` (01:30), `backup:clean` (02:30, enforces retention), `backup:monitor` (03:00) daily via the existing `routes/console.php` scheduler — the same cron (`schedule:run` every minute) production already runs for the discount-expiry commands, so no new cron entry is needed. Every scheduled command logs on failure via `Log::error()` in addition to Spatie's own mail notifications, so a failure cannot silently succeed. | `config/backup.php` (new), `config/filesystems.php`, `routes/console.php`, `composer.json`/`composer.lock`, `.env.example`, `docs/operations/BACKUP_AND_RESTORE.md` (new) | `tests/Feature/BackupConfigurationTest.php` (4 tests: backups disk is private and unreachable, exclude list prevents self-inclusion/noise, all 3 commands registered on the scheduler, a broken backup source returns a non-zero exit code instead of succeeding) | **FIXED** |

## Test results (this regression pass)

```
php artisan test --compact
Tests:    487 passed (3,638 assertions)
Duration: 221.24s

vendor/bin/pint --test
{"result":"pass"}

npm run build          (includes the SSR build: vite build && vite build --ssr)
✓ built in ~1.2s

git diff --check       (including newly added, not-yet-committed files)
(no output = clean)
```

Static analysis (PHPStan/Larastan) and a frontend unit-test runner are **not
configured in this repository** (no `phpstan.neon`, no `larastan` dependency,
no test script in `package.json`), so neither was "run" — reporting a pass
for tooling that doesn't exist here would be fabricated. SSR is covered by
`npm run build`, which builds it as a second Vite pass. No Playwright/E2E
config exists in the repository either.

## Anything still blocked

Nothing above is blocked on a missing business decision. Two items are
genuinely **infrastructure-dependent** and are documented as such rather than
silently assumed:

- **Off-site/redundant backup storage.** The `backups` disk is local by
  default. Making it also copy to S3-compatible storage is a configuration
  change (`config/backup.php` → `destination.disks`, plus real credentials in
  the existing `s3` disk), but requires knowing what object storage, if any,
  the production host provides — left as documented infrastructure work in
  `docs/operations/BACKUP_AND_RESTORE.md` rather than guessed at.
- **Backup failure notifications reaching a human.** `BACKUP_NOTIFICATION_EMAIL`
  and a working `MAIL_MAILER` must be set in production for
  `BackupHasFailedNotification`/`UnhealthyBackupWasFoundNotification` to
  actually deliver; locally `MAIL_MAILER=log` only writes to the log file
  (which is also why every scheduled backup command additionally logs via
  `Log::error()` on failure — so a failure is never silent even before mail
  is configured).

Everything else in this table is either fixed in this pass or was already
correct in the codebase and is now verified by a test and documented rather
than left as an implicit assumption.
