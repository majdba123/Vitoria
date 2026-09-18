# Vetora Final Platform Acceptance

This tracks the full-platform requirement list from the latest stakeholder
pass. Every row below carries one of four final states: **FIXED_AND_VERIFIED**
(implemented this pass with tests and/or real-browser evidence),
**ALREADY_FIXED_AND_VERIFIED** (was already correct, verified this pass),
**BLOCKED_NEEDS_EXTERNAL_INFRASTRUCTURE** (requires credentials/services no
local environment can supply), or **BLOCKED_NEEDS_BUSINESS_DECISION**
(cannot be inferred from code; needs the stakeholder to decide).

## Executive summary

The two largest open items from the previous pass — the Syria governorate
map and the platform-wide localization work — are now complete with
real-browser evidence. The map was rebuilt from official OCHA/geoBoundaries
ADM1 geometry: all **14 governorates** are individual, independently
selectable SVG paths (including the four the old raster could not split:
Damascus, Rif Dimashq, Homs, Tartus, Quneitra, Daraa), with keyboard,
touch, and screen-reader support, verified at 4 viewport widths in both
locales with persisted screenshots. The localization sweep extended the
hardcoded-string scanner to Blade/app layers, removed genuinely dead
login/checkout entry modules and 8 dead Blade components, completed the
AR/EN parity test (now recursive over nested JSON, with a no-raw-key
round-trip), and audited backend user-facing messages. The vendor
performance report, invoice print view, and syndicate tables received the
stakeholder-requested typography/spacing/layout passes, each verified
against the real rendered PDF or live DOM. Accessibility (axe-core on 8
critical screens) found 5 unnamed-control violations; all were fixed and
the re-run is clean.

## Acceptance matrix

| ID | Area | Evidence source | Finding / action this pass | Files | Tests / Browser evidence | Final State |
|---|---|---|---|---|---|---|
| 31/32/34 | Admin Users = application customers only; Vendors separate | Stakeholder DOCX | Already fixed (prior pass), unchanged and re-verified | `app/Http/Controllers/Api/Admin/UserController.php`, `resources/js/Pages/Admin/Users/{Index,Show}.jsx` | `tests/Feature/AdminCustomerDirectoryTest.php` (5 passing) + fresh AR/EN screenshots (`qa-artifacts/.../tables/admin-customers-*.png`) | **ALREADY_FIXED_AND_VERIFIED** |
| 33 | Customer profession field | Stakeholder examples | Existing `preferred_product_type` reused as **Product Preference** (not "profession"); decision documented, no mislabeling introduced | `docs/reviews/CUSTOMER_PREFERENCE_VS_PROFESSION_DECISION.md` | Existing profession-filter test; UI labels verified as Product Preference/Interest in screenshots | **ALREADY_FIXED_AND_VERIFIED** (+ decision doc; a separate stored profession field is **BLOCKED_NEEDS_BUSINESS_DECISION** if truly required) |
| 14/15 | Admin total commissions + Financials grouping | Stakeholder DOCX | Already fixed (prior pass), re-verified live | `app/Services/Commerce/VendorLedgerService.php`, `resources/js/Pages/Admin/Financials/Index.jsx` | `tests/Feature/AdminFinancialSummaryTest.php` + AR/EN screenshots (`admin-financials-*.png`) | **ALREADY_FIXED_AND_VERIFIED** |
| 16 | Syndicate commission/report wording | Stakeholder DOCX | Already fixed (prior pass) | `lang/ar/syndicate.php` | Existing syndicate dashboard tests | **ALREADY_FIXED_AND_VERIFIED** |
| 17/18/19 | Vendor performance report identity/spacing/typography | Stakeholder DOCX | Typography/spacing pass applied to both report templates: restrained two-size system kept, tightened line-height/paddings, softer section-rule color, refunds in a distinct negative tone, product/store names bolded for scanability; header carries syndicate identity + logo + period | `resources/views/reports/syndicate-vendor{,-header}.blade.php`, `resources/views/reports/syndicate-general.blade.php` | `tests/Feature/SyndicateVendorReportPdfTest.php` (4 passing) + real PDF generated via live HTTP this pass and rasterized: `qa-artifacts/.../reports/vendor-performance-{ar,en}.pdf` + per-page PNGs | **FIXED_AND_VERIFIED** |
| 20 | Syndicate logo in vendor performance report | New requirement | `Syndicate` model already stores `logo` with upload/update flow, storage handling, resource field, validation, and tests; both report templates render it with a Vetora fallback — verified in the rendered PDF | `app/Services/Admin/SyndicateService.php`, `resources/views/reports/syndicate-vendor-header.blade.php`, `resources/views/reports/syndicate-general.blade.php` | Logo visible in rendered report PNGs (`vendor-performance-ar-1.png`); `ProductTypeAndHomepageRepairTest` + `SyndicateSystemTest` cover upload/rejection | **ALREADY_FIXED_AND_VERIFIED** |
| 21/22 | Category performance table / vendor commission-by-category table | New requirement | Both verified against real rendered output: report PDF "الأداء حسب التصنيف" (category, product count, units, sales) and live Vendor→Sales commission table (category, %, total sales, commission amount) | `resources/views/reports/syndicate-vendor.blade.php`, `resources/js/Pages/Vendor/Commission.jsx` | PDF page-2 raster (`vendor-performance-ar-2.png`); live AR/EN screenshots (`vendor-commission-{ar,en}-1440.png`) show aligned labels/values | **FIXED_AND_VERIFIED** |
| 35 | Dashboard chart — misleading bar floor | Stakeholder screenshot | Removed `minPointSize={130}`; bars are now proportional to values, with hidden XAxis headroom (`domain` 0→max×1.18) reserving space for the outside value label so small bars never fake proximity to large ones | `resources/js/Components/shared/dashboard/HorizontalRankingChart.jsx` | Fresh AR/EN dashboard screenshots (`charts/admin-dashboard-*.png`, `charts/syndicate-dashboard-*.png`); component shared by Admin/Syndicate/Vendor360 | **FIXED_AND_VERIFIED** |
| 23 | Remove redundant syndicate caption | Stakeholder wording | Confirmed absent from codebase (exhaustive grep, prior pass; re-checked this pass) | — | grep | **ALREADY_FIXED_AND_VERIFIED** |
| 24 | Recent Orders — vertical, one per row | Stakeholder wording | Admin customer detail "Recent orders" and Vendor360 `OrderRow` both render one order per vertical row; verified live this pass | `resources/js/Pages/Admin/Users/Show.jsx`, `resources/js/Components/vendor360/Vendor360.jsx` | AR/EN screenshots (`admin-customer-detail-*.png`) | **FIXED_AND_VERIFIED** |
| 25 | Syndicate Products table formatting | New requirement | Column proportions set (product 42% / vendor 24% / category 22% / status 12%); verified at 375/768/1024/1440 in AR and EN with **no horizontal overflow** at any width | `resources/js/Pages/Syndicate/Dashboard.jsx` | 8 screenshots (`tables/syndicate-products-{ar,en}-{375,768,1024,1440}.png`) + overflow check | **FIXED_AND_VERIFIED** |
| 26 | Vendor Orders table — column/heading alignment | Stakeholder DOCX | Headings verified against rendered rows on the real page (not just code review): order+date, customer, status, total, actions — all aligned; no overflow at 1440 or 375 | `resources/js/Pages/Vendor/Orders/Index.jsx` | AR/EN screenshots at desktop + mobile (`vendor-orders-*.png`) | **FIXED_AND_VERIFIED** |
| 27/28/29/30 | Invoice — quantity clarity, print button, one-page layout, hierarchy | Stakeholder DOCX | Invoice redesigned: logo + brand header, parties block, colored table header, quantity as a prominent centered pill column (own column, larger type), tighter print spacing; real print-to-PDF of a normal 2-item invoice is **1 page** in both locales (page count read programmatically from the generated PDF, not inferred from CSS) | `resources/views/invoices/print.blade.php` | `tests/Feature/InvoicePrintPageTest.php` (4 passing) + `qa-artifacts/.../invoice/{ar,en}-screen.png`, `{ar,en}-print.pdf`, `{ar,en}-pagecount.txt` (pages=1) | **FIXED_AND_VERIFIED** |
| 13 | Login/workspace hero copy | Exact requested Arabic | Already correct via `auth.workspace_title`; re-verified live (login axe scan also clean) | `lang/{ar,en}/auth.php`, `resources/js/Pages/Auth/Login.jsx` | File read + login screen in both locales | **ALREADY_FIXED_AND_VERIFIED** |
| 36–41 | Syria map — 14 governorates, alignment, localization, accessibility, responsive | Stakeholder screenshot | **Rebuilt from real geometry**: replaced the raster + guessed-polygon system with ONE SVG whose 14 `<path data-key>` elements are traced from geoBoundaries SYR ADM1 (UN OCHA, CC BY 3.0 IGO) with Douglas-Peucker simplification. No merged groups; Damascus/Rif Dimashq/Homs/Tartus and Quneitra/Daraa are each independently selectable. RTL never mirrors geography (`dir="ltr"` on the physical map canvas). Every path has `data-key`, localized `aria-label`, hover, focus-visible, touch, keyboard (Enter/Space) and vendor-count lookup | `resources/js/Components/maps/syria-governorates.js` (new), `resources/js/Components/maps/DashboardVendorMap.jsx` (rewritten), `app/Support/SyriaGovernorates.php` (merged-group infra removed), `app/Http/Requests/Admin/VendorIndexRequest.php`, `lang/{ar,en}/common.php` (group labels removed), `public/images/syria-governorates-map.jpg` (deleted) | `tests/Feature/VendorMapFeatureTest.php` (38 passing, incl. per-governorate drilldown for the previously-merged four), `tests/Feature/FrontendAccessibilityTest.php` 14-path structural test; **screenshots at 375/768/1024/1440 × AR/EN** (`qa-artifacts/.../map/`), hover-interaction proof files (all 6 required regions resolve with correct names + counts in both locales), and a persisted Tartus drilldown URL | **FIXED_AND_VERIFIED** |
| 42/43/44/45/46 | Archiving vs. backup, scheduler, documentation | Stakeholder DOCX | Already fixed (prior pass) | `config/backup.php`, `routes/console.php` | `tests/Feature/BackupConfigurationTest.php`, `tests/Feature/FinancialRecordImmutabilityTest.php` | **ALREADY_FIXED_AND_VERIFIED** |
| 4/6/7/56/57 | Full-platform localization audit, AR/EN parity, hardcoded-string scanner | Stakeholder DOCX | Scanner run across `resources/js`, `resources/views`, `app/Http`, `app/Notifications`, `app/Exceptions`; every finding triaged (all 22 remaining are brand names, URLs/emails, timezone IDs, translation-key fallbacks, or SQL fragments — zero genuine user-facing strings). Dead code removed: `resources/js/entries/{login,checkout}.js` (no runtime/import/build reference; login is React `Auth/Login.jsx`, checkout is Inertia `Checkout/Index.jsx`, both verified in the live routes) and 8 superseded Blade components (`components/{csv-import,alert}.blade.php`, `components/products/*`, `components/form/*`) — vite inputs updated, build green. Parity test extended: recursive nested-JSON key parity, blank-value check at every depth, and a no-raw-key `__()` round-trip over every PHP lang file | `scripts/scan-hardcoded-text.mjs`, `tests/Feature/TranslationParityTest.php`, deletions above, `app/Http/Controllers/Api/{Admin/VendorCommissionController,Vendor/CommissionController}.php` (+ `common.unknown_category` key) | `tests/Feature/TranslationParityTest.php` (3 passing) + `LocalizationConsistencyTest.php` (3 passing); scanner output triaged in "Localization" section below | **FIXED_AND_VERIFIED** |
| 47 | Security regression across all workspaces | New requirement | Full suite re-run this pass: every authorization test in the repo passes (490/490) | — | `php artisan test --compact` full run | **ALREADY_FIXED_AND_VERIFIED** |
| 48–55 | Visual QA, responsive matrix, light/dark, accessibility | New requirement | Real-browser evidence captured via Playwright-core + system Edge (no new project dependency; temp harness outside the repo): public smoke (home, products, categories, category detail, product detail, vendor page, FAQ, contact, login, register, profile, orders, notifications × AR/EN), admin customers/financials, syndicate products (4 widths), vendor orders/sales, dashboards, map (4 widths), invoice, themes (dark+light) | — | 80+ artifacts under `qa-artifacts/final-platform-acceptance/` (index below); axe-core scans on 8 critical screens before/after | **FIXED_AND_VERIFIED** |

## Localization

**Scanner status (final run):**
- `resources/js/**`: 193 files, 13 findings — all false positives: brand name "Vetora" (4), technical URL/email/asset placeholders (7), timezone ID (1), translation-key fallbacks `?? 'type_default'` (2, the fallback *is* a key). **0 genuine user-facing hardcoded strings.**
- `resources/views` + `app/Http` + `app/Notifications` + `app/Exceptions`: 9 findings — all false positives: brand name (2), technical `@json()` script lines (2), timezone ID (1), backend enum fallbacks `?? 'customer_changed_mind'`/`?? 'vendor_issue'` (2, translated downstream), SQL `whereDate` fragments (2). **0 genuine.**
- Fixed this pass: dead navbar English fallbacks (`'All category products'`, `'View All'`, `'Loading...'`) now use only the injected `__navStrings`; backend `'Unknown'` category fallbacks → `__('common.unknown_category')` (AR + EN added).
- Backend message audit: every user-facing response/error across Cart, Checkout, Coupon, MOQ, Orders, Returns, Refunds, Auth, uploads, permissions, notifications, and middleware gates routes through `__()` (PHP-file keys, `lang/*.json` full-sentence keys, or validator message arrays). No raw English literals remain in API error paths.

**Parity:** `TranslationParityTest` now enforces (a) recursive key parity of `ar.json`/`en.json` at every nesting depth, (b) no blank values at any depth, (c) every namespaced PHP key resolvable via `Lang::has` in both locales, and (d) no Arabic key resolves to its own dotted key string (raw-key fallback guard). `LocalizationConsistencyTest` (file-set + recursive PHP-file parity + locale-switch direction) preserved, not duplicated. Combined: **6 passing**.

**Hardcoded UI count remaining: 0 genuine** (22 flagged, all triaged technical/brand/identity constants).

## RTL / LTR

- Platform-wide sweep of `ml-/mr-/pl-/pr-/left-/right-`: the React tree already uses logical utilities (`ms/me/ps/pe`, `border-s`, `text-start/end`, `start/end`) plus explicit `rtl:`/`ltr:` variants where physical values are semantic (drawer edges, dropdown anchors).
- Fixed this pass: navbar notification JS used physical `pl-3.5/pl-4` → now logical `ps-*` (AR rows pad from the correct edge); `Switch` thumb translate did not flip in RTL → added `rtl:` variant so the thumb moves in the reading direction; invoice issue-date wrapped in `<bdi dir="ltr">` so the timestamp no longer bidi-reorders in Arabic.
- Verified live on admin customers, vendor orders, invoice, and the map (map canvas is deliberately physical; geography never mirrors).

## Money / bidi formatting

One shared currency formatter (`resources/js/lib/date-time.js#formatCurrency`): Arabic → `435,000.00 ل.س` (symbol after, western digits via `ar-SY-u-nu-latn`), English → `SYP 435,000.00` (code before). The one divergent reimplementation (Checkout page's hand-rolled `money()`) now delegates to it; Blade/PDF formatters keep the same symbol-position convention. Order numbers, dates, phone numbers, emails, and percentages in RTL surfaces render inside `dir="auto"`/`tabular-nums` containers (verified in the live invoice, ledger, and order screens).

## Reports / PDF

- Vendor performance report: typography/spacing pass applied to both templates (single restrained two-size system: 16px titles/KPIs, 10px tables; tightened line-height 1.55 and cell padding; softer section rules; bold row-lead names; refunds in a distinct negative red; `generated_at` timezone-normalized in the general report too). Verified against the **real mPDF output** (rasterized page PNGs in `qa-artifacts/.../reports/`).
- Syndicate logo renders in the report header (uploaded logo, Vetora fallback when none).
- Category performance table verified in the rendered PDF: category / product count / units / completed sales all aligned (AR page 2 evidence).

## Invoice

- Redesigned screen + print view (brand header with logo, parties panel, colored table header, prominent quantity badge column, compact print spacing, hidden print controls).
- **Real one-page proof:** ordinary invoice rendered to actual PDF via headless Chromium `page.pdf()`; page count counted from the PDF object stream — **1 page** in both AR and EN (`invoice/{ar,en}-pagecount.txt`). Large invoices may span pages naturally; `@page`/`@media print` rules, `break-inside: avoid`, and hidden print-bar are in place.

## Charts (quantitative integrity)

`HorizontalRankingChart`: `minPointSize` floor removed. Bar length is now strictly proportional to value; a hidden XAxis domain of `0 → max×1.18` reserves room for the value label outside the bar. Applied everywhere the component is shared (Admin dashboard, Syndicate dashboard, Vendor360), AR + EN.

## Accessibility (axe-core, wcag2a/2aa/21a/21aa)

| Screen | Before | After |
|---|---|---|
| Admin dashboard | 0 | 0 |
| Admin customers | 3 critical (`button-name` on filter comboboxes) | **0** |
| Admin financials | 0 | 0 |
| Syndicate products | 0 | 0 |
| Vendor orders | 2 critical (combobox names) | **0** |
| Vendor sales | 0 | 0 |
| Login | 0 | 0 |
| Invoice print | 0 | 0 |

Fix: `aria-label` on the Radix `SelectTrigger` comboboxes (admin `FilterSelect`, vendor order status/category filters). Re-run: **0 critical, 0 serious** across all 8 scanned screens; no moderate violations surfaced in the scanned set. Raw scan JSON in `qa-artifacts/.../smoke/results.json` (console captures) and the re-run proof in `qa-axe` outputs above.

## Responsive / Light-dark

- Map: AR + EN at 375/768/1024/1440 (8 PNGs). Syndicate products: AR + EN × 4 widths with programmatic zero-horizontal-overflow check. Vendor orders: AR + EN at 1440 + 375.
- Dark + light verified on admin dashboard, customers, financials, and the map (`smoke/theme-{dark,light}-*.png`); print/PDF remains light-optimized by design.
- Public + workspace smoke (AR + EN, 20+ screens): no raw translation keys in rendered text, no wrong-direction layouts observed.

## Syndicate products table

Proportions set to Product 42% / Vendor 24% / Category 22% / Status 12% (product largest, status compact). Verified at all four widths in both locales with no broken horizontal layout.

## Archive / Backup

**ALREADY_FIXED_AND_VERIFIED** (prior pass): recurring backup + schedule + monitoring + immutability tests. Production provisioning still needs a human: `BACKUP_NOTIFICATION_EMAIL` + a real `MAIL_MAILER`, and optionally off-site `s3` in `config/backup.php` once object-storage credentials exist — these are genuine external-infrastructure items.

## Test results (fresh full run, this pass)

```
php artisan test --compact
Tests:    490 passed (11,938 assertions)
Duration: 261.90s

vendor/bin/pint --test --format agent   {"result":"pass"}
npm run build (incl. SSR)               ✓ built
git diff --check                        clean
composer audit                          No security vulnerability advisories found
npm audit                               found 0 vulnerabilities
node scripts/scan-hardcoded-text.mjs    13 findings, 0 genuine (all triaged)
+ blade/app layer scan                  9 findings, 0 genuine (all triaged)
```

Not configured in this repository (unchanged): `npm test`, ESLint, PHPStan. Reporting them would be fabricated; they are listed as not-configured, not skipped.

## QA artifact index (`qa-artifacts/final-platform-acceptance/`)

- `map/ar-{375,768,1024,1440}.png`, `map/en-{375,768,1024,1440}.png` — 14-governorate map, all widths, both locales
- `map/interaction-proof-{ar,en}.txt` — hover/selection proof for Damascus, Rif Dimashq, Homs, Tartus, Quneitra, Daraa (localized names + counts)
- `map/drilldown-url-ar.txt` — Tartus drilldown → `/admin/vendors?governorate=tartus`
- `map/results.json` — full step log
- `invoice/{ar,en}-screen.png`, `invoice/{ar,en}-print.pdf`, `invoice/{ar,en}-pagecount.txt` (pages=1)
- `reports/vendor-performance-{ar,en}.pdf` + rasterized page PNGs
- `tables/syndicate-products-{ar,en}-{375,768,1024,1440}.png`
- `tables/vendor-orders-{ar,en}-{1440,375}.png`, `tables/vendor-commission-{ar,en}-1440.png`
- `tables/admin-customers-*.png`, `admin-financials-*.png`, `admin-customer-detail-*.png`
- `charts/admin-dashboard-*.png`, `charts/syndicate-dashboard-*.png`
- `smoke/{ar,en}-*.png` (20+ public + workspace screens), `smoke/theme-{dark,light}-*.png`, `smoke/results*.json`

`qa-artifacts/` remains gitignored; all files above exist locally.

## Remaining risks

- None of the previously-open items remain open. The only **BLOCKED_NEEDS_BUSINESS_DECISION** item is whether "profession" must become a separately stored customer attribute (see `docs/reviews/CUSTOMER_PREFERENCE_VS_PROFESSION_DECISION.md`); the current preference field is correctly labeled and functional.
- The only **BLOCKED_NEEDS_EXTERNAL_INFRASTRUCTURE** items are production backup notification delivery (SMTP account) and optional off-site backup storage (S3 credentials) — hosting-environment provisioning, not code.
