# Arabic Frontend Final Acceptance

Status as of 2026-09-19. Supersedes every earlier revision of this file. All evidence below was produced in this pass against a local server (`php artisan serve --port=8011`, production `npm run build` assets, seeded SQLite DB, Chromium 1243 via Python Playwright). Screenshots, PDFs and raw sweep JSON live in `qa-artifacts/frontend-improvement-final/` (git-ignored).

## Method

- **Automated Arabic sweep:** every page for every seeded role (public, customer, admin, vendor, syndicate, employee), logged in via the real login form, locale `ar`. For each page and width it records `dir`, horizontal overflow (`scrollWidth > clientWidth`), console errors and page errors, visible `<table>` count, visible `RecordCard` count (via the new `data-record-card` hook), raw-key patterns, and, at the widest width, Latin-script text and English `aria-label`/`placeholder`/`title`/`alt` values.
  - Role pages: 375/768/1024/1440, 184 checks.
  - Public and customer pages: 320/375/390/430/768/1024/1280/1440/1920, 144 checks.
- **PDFs:** the reports are real mPDF output from `SyndicateReportService` and `SyndicateVendorPdfService`, rasterized with `pdftoppm`. The invoice PDFs come from Chromium `page.pdf()` in print media, rasterized the same way. Page counts come from `pdfinfo` and fonts from `pdffonts`.
- **Accessibility:** axe-core (`node_modules/axe-core`), WCAG 2 A and AA rule sets.

## Fixes Made in This Pass

| Area | Defect found (live) | Fix |
| --- | --- | --- |
| Public header | Every public page overflowed horizontally at **1024px** (1099 > 1024); the login/register cluster was pushed 75px off-screen | Desktop nav links / hamburger breakpoint moved `lg` → `xl` (`PublicHeader.jsx`); search and auth actions stay visible at 1024 |
| Vendor report PDF | `&#8594;` separator rendered as a missing-glyph box in the header and identity line | Replaced with `&mdash;`, which the Arabic font has |
| Vendor report PDF | Running header rule collided with the first body heading on pages 1 and 2 | `margin_top` 25 → 32 mm (presentation-only mPDF page option) |
| Vendor report PDF | Empty tables showed a lone `—` in the first cell | Localized, centered, full-width empty row (`reports.vendor.labels.no_records`, AR and EN) |
| Report PDFs + Admin Financials | Zero refunds / zero paid amounts painted red or green | Semantic colour only when amount > 0 |
| Invoice (screen, 375px) | Invoice/order numbers, timestamp and money broke mid-token (`4,500./00`, `INV-20260905-/83015`); `الكمية` header split | `nowrap` + LTR isolation for codes and amounts; mobile layout (stacked header and parties, compact table) under 560px; print CSS untouched |
| Ranking chart (Admin + Syndicate dashboards) | Value label sat over its own bar in RTL | Label `direction` pinned to LTR and RTL anchor set to `end`; +48px reserved right margin. Bar lengths unchanged (no `minPointSize`) |
| Syndicate map | All 14 governorates filled identically, so the map showed no distribution | Count-based shading: governorates with no vendors are muted; the rest scale with vendor count (presentation only, same API data) |
| Syndicate dashboard | Large blank area under "الأعلى أداءً" beside the tall map | Vendor-status panel moved into the side column; monthly growth takes a full-width row |
| QA | Stale git-ignored `public/hot` pointed assets at a dead Vite server | Removed |

## Acceptance Table

| Requirement | Initial Issue | Fix | Live Evidence | Test Evidence | Status |
| --- | --- | --- | --- | --- | --- |
| Hardcoded UI strings | Scanner: 13 findings | None needed: all 13 classified (below) | Sweep leak check: no English UI chrome on any role page | scanner run | ALREADY_FIXED_AND_VERIFIED |
| Translation completeness | — | `no_records` added to AR + EN | no raw keys in sweep | TranslationParity / LocalizationConsistency pass | FIXED_AND_VERIFIED |
| RTL | — | chart label, invoice isolation | `dir=rtl` on 328/328 page checks | — | FIXED_AND_VERIFIED |
| Responsive, no page overflow | Public overflow at 1024 | header breakpoint | 0 overflow across all 328 checks (matrix below) | — | FIXED_AND_VERIFIED |
| Mobile tables → RecordCard | — | QA hook only | cards at 375, table at ≥768 on every table page, 0 violations | StakeholderUiStructureTest | ALREADY_FIXED_AND_VERIFIED |
| Admin total commissions | — | zero-amount tone | `إجمالي العمولات (كل البائعين)` is the first KPI (188.28 ل.س), with per-vendor table | full suite | FIXED_AND_VERIFIED |
| Admin financial navigation | — | none | single `العمولات` group with one link, no duplicates | — | ALREADY_FIXED_AND_VERIFIED |
| Admin customers | — | none | `/admin/customers` → `/admin/users` (customers only), cards/table verified | full suite | ALREADY_FIXED_AND_VERIFIED |
| Syndicate financial wording | — | none | sidebar uses `المالية`; no commission label on syndicate (no commission data is exposed to syndicates) | — | ALREADY_FIXED_AND_VERIFIED |
| Syndicate dashboard polish | blank gap beside map | rebalanced columns | `syndicate/dashboard-rebalanced-*.png`, 0 overflow at 375–1920 | — | FIXED_AND_VERIFIED |
| Syndicate map | uniform fill | count shading | 14 regions, 2 fill levels, Arabic tooltip in viewport, light + dark, 375/768/1440 | — | FIXED_AND_VERIFIED |
| Syndicate products | — | none | cards at 375, table at ≥768 | — | ALREADY_FIXED_AND_VERIFIED |
| Vendor orders column alignment | — | none | `desktop/vendor_vendor_orders_1440.png`: every value under its heading | — | ALREADY_FIXED_AND_VERIFIED |
| Vendor commission by category | — | none | التصنيف / نسبة العمولة / إجمالي المبيعات / مبلغ العمولة with ل.س formatting; cards at 375 | — | ALREADY_FIXED_AND_VERIFIED |
| Syndicate general report PDF | — | refund tone | 1 page, XB Riyaz embedded, tables aligned | Report tests | FIXED_AND_VERIFIED |
| Vendor report PDF | tofu, header collision, bare `—` | see fixes | 2 pages, no missing glyphs, header clear | Report tests | FIXED_AND_VERIFIED |
| Category performance PDF table | — | none | 4 aligned columns (التصنيف / عدد المنتجات / الوحدات المباعة / إجمالي المبيعات المكتملة) | — | ALREADY_FIXED_AND_VERIFIED |
| Invoice quantity | — | none | dedicated `الكمية` column with badge | Invoice tests | ALREADY_FIXED_AND_VERIFIED |
| Invoice print / one page | — | mobile screen fix | both invoices: 1 A4 page; print button not visible in print media | Invoice tests | FIXED_AND_VERIFIED |
| Dashboard charts | RTL label overlap | label anchoring | measured label/bar overlap 0px, not clipped, 375/768/1440, admin + syndicate (Arabic; the English LTR branch is code-inspected only) | — | FIXED_AND_VERIFIED |
| Accessibility | — | none needed | axe WCAG A/AA: 0 violations on 10 pages | — | ALREADY_FIXED_AND_VERIFIED |
| Light / dark | — | none | 20 captures in `dark/`; `.dark` class applied on every dark capture | — | ALREADY_FIXED_AND_VERIFIED |
| Console | — | — | 0 console errors / page errors across all sweeps, invoice and map runs (after the `public/hot` fix) | — | FIXED_AND_VERIFIED |
| Profession vs product preference | — | none | Admin Users shows product preference, not profession | — | BLOCKED_NEEDS_BUSINESS_DECISION (only if real professions such as مهندس زراعي / طبيب بيطري are wanted; no such field exists) |

## Hardcoded-Text Scanner: All 13 Findings Classified

| File:line | Value | Class |
| --- | --- | --- |
| `product-card.js:34` | `/images/product-placeholder.svg` | TECHNICAL_CONSTANT (asset path) |
| `Home.jsx:310`, `Syndicate/Dashboard.jsx:112`, `Pages/Show.jsx:32` | `Vetora` | VALID_DYNAMIC_CONTENT (brand) |
| `Syndicate/Dashboard.jsx:102`, `SyndicateHeader.jsx:19` | `type_default` | TRANSLATION_KEY |
| `Profile/Index.jsx:342` | `Asia/Damascus` | TECHNICAL_CONSTANT (IANA timezone) |
| `Admin/Vendors/Index.jsx:231`, `Admin/AboutUs/Edit.jsx:64` | `owner@example.com`, `support@vetora.test` | URL_EMAIL (format example) |
| `Admin/Banners/Index.jsx:176`, `Admin/AboutUs/Edit.jsx:54-56` | `https://…` | URL_EMAIL |

Genuine user-facing hardcoded strings: **0**.

## Localization

| Area | Arabic | English leakage | Missing key | RTL | Result |
| --- | --- | --- | --- | --- | --- |
| Public | yes | none in UI (only `alt="Vetora"` brand) | 0 | yes | FIXED_AND_VERIFIED |
| Auth | yes | none | 0 | yes | ALREADY_FIXED_AND_VERIFIED |
| Admin | yes | only data: seeded English names, emails, product/subcategory names | 0 | yes | ALREADY_FIXED_AND_VERIFIED |
| Vendor | yes | data only (see Notifications) | 0 | yes | ALREADY_FIXED_AND_VERIFIED |
| Syndicate | yes | none | 0 | yes | ALREADY_FIXED_AND_VERIFIED |
| Employee | yes | none | 0 | yes | ALREADY_FIXED_AND_VERIFIED |
| Reports | yes | historical English product-name snapshots in order lines (backend item 2) | 0 | yes | FIXED_AND_VERIFIED |
| Invoice | yes | same product-name snapshots | 0 | yes | FIXED_AND_VERIFIED |
| Notifications | yes | two stored rows from 2026-09-05 read "You have a new order…" (below) | 0 | yes | ALREADY_FIXED_AND_VERIFIED |

About the notification rows: notification text is rendered and stored in the recipient's locale at send time (`NotificationService::sendLocalizedNotification`). The seeded vendor's locale is now `ar`, so new notifications arrive in Arabic. The two English rows are historical stored data, not frontend strings. They were not altered.

## Responsive Matrix

Role pages were captured before the late Admin Financials, chart and syndicate-dashboard edits. Those three screens were re-verified separately after the edits (0 overflow, 0 errors).

| Screen | 375 | 768 | 1024 | 1440 | Overflow | Result |
| --- | --- | --- | --- | --- | --- | --- |
| public `/product-type/select` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/product-type/select?preferred_product_type=agriculture` → /categories (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/products` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/products/1` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/categories/1` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/vendors/1` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/categories` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/vendors` → / (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/faq` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/contact` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/login` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| public `/register` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| customer `/profile` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| customer `/checkout` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| customer `/orders/1` (also 320/390/430/1280/1920) | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| admin `/admin/dashboard` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| admin `/admin/users` | rtl, 0 err, 5 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/customers` → /admin/users | rtl, 0 err, 5 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/vendors` | rtl, 0 err, 2 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/products` | rtl, 0 err, 15 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/categories` | rtl, 0 err, 16 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/subcategories` | rtl, 0 err, 21 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/cities` | rtl, 0 err, 6 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/syndicates` | rtl, 0 err, 2 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/orders` | rtl, 0 err, 6 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/financials` | rtl, 0 err, 1 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/banners` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| admin `/admin/coupons` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| admin `/admin/pages` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| admin `/admin/contact-messages` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| admin `/admin/notifications` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| admin `/admin/employees` → /admin/users | rtl, 0 err, 1 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| admin `/admin/discounts` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| vendor `/vendor/dashboard` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| vendor `/vendor/products` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| vendor `/vendor/orders` | rtl, 0 err, 6 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| vendor `/vendor/sales` | rtl, 0 err, 12 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| vendor `/vendor/profile` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| vendor `/vendor/notifications` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/dashboard` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/vendors` | rtl, 0 err, 1 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/products` | rtl, 0 err, 8 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/categories` | rtl, 0 err, 8 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/orders` | rtl, 0 err, 6 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/sales` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/reports` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/podcasts` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| syndicate `/syndicate/notifications` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| employee `/employee/dashboard` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |
| employee `/employee/products` | rtl, 0 err, 16 cards | rtl, 0 err, table | rtl, 0 err, table | rtl, 0 err, table | 0 | FIXED_AND_VERIFIED |
| employee `/employee/notifications` | rtl, 0 err | rtl, 0 err | rtl, 0 err | rtl, 0 err | 0 | FIXED_AND_VERIFIED |

## PDFs

| PDF | Generated | RTL | Arabic font | Tables | Page count | Result |
| --- | --- | --- | --- | --- | --- | --- |
| Syndicate general report (AR) | `reports/syndicate-general-ar.pdf` | yes | XB Riyaz + Bold, no missing glyphs | aligned, zebra, green header row | 1 | FIXED_AND_VERIFIED |
| Vendor performance report (AR) | `reports/vendor-report-ar.pdf` | yes | XB Riyaz + DejaVu Sans, no missing glyphs | aligned; empty-state row localized | 2 | FIXED_AND_VERIFIED |
| Invoice 1 (AR, browser print) | `invoice/invoice-1-ar.pdf` | yes | IBM Plex Sans Arabic | quantity column | 1 | FIXED_AND_VERIFIED |
| Invoice 2 (AR, browser print) | `invoice/invoice-2-ar.pdf` | yes | IBM Plex Sans Arabic | quantity column | 1 | FIXED_AND_VERIFIED |

Data-quality note: the agriculture syndicate's seeded logo is a checkerboard placeholder image. The template renders it correctly and falls back to the Vetora logo when no file exists.

## Backend Items: Documented, Not Changed

1. Locale middleware fallback semantics after session regeneration.
2. `order_items.product_name` is a creation-time snapshot, so older orders show English product names in reports and invoices.
3. API `type_label` is serialized in English. The frontend localizes from the raw enum.

## Fresh Test Results (this pass)

| Check | Result |
| --- | --- |
| `php artisan test --compact` | 490 passed (11986 assertions) |
| focused `TranslationParity\|LocalizationConsistency\|StakeholderUiStructure\|Report\|Invoice` | 45 passed (8698 assertions) |
| focused `Localization\|Report\|Invoice\|StakeholderUiStructure` (pre-commit review) | 45 passed (371 assertions) |
| `vendor/bin/pint --test` | `{"result":"pass"}` |
| `npm run build` | built |
| `git diff --check` | clean |
| `node scripts/scan-hardcoded-text.mjs` | 13 findings, 0 genuine |

## Files Changed

- `app/Services/Vendor/SyndicateVendorPdfService.php`: mPDF `margin_top` only
- `lang/ar/reports.php`, `lang/en/reports.php`: `no_records`
- `resources/js/Components/maps/DashboardVendorMap.jsx`
- `resources/js/Components/public/PublicHeader.jsx`
- `resources/js/Components/shared/RecordCard.jsx`: `data-record-card` attribute
- `resources/js/Components/shared/dashboard/HorizontalRankingChart.jsx`
- `resources/js/Pages/Admin/Financials/Index.jsx`
- `resources/js/Pages/Syndicate/Dashboard.jsx`
- `resources/views/invoices/print.blade.php`
- `resources/views/reports/syndicate-general.blade.php`, `syndicate-vendor.blade.php`, `syndicate-vendor-header.blade.php`

No migrations, no financial formulas, no permission or moderation changes. Nothing committed.
