# Arabic Frontend Final Acceptance

Frontend-only Arabic UX / localization / responsive / PDF polish pass.
Date: 2026-09-18. Branch: `main`. Nothing committed, pushed or deployed.

## Scope and Method

Scope was limited to presentation: `resources/js/**`, `resources/views/**` used for
UI/print/PDF, and `lang/{ar,en}/**`. No business logic, calculation, schema,
policy, service or API-contract change was made. Two genuine backend defects were
found through frontend symptoms; both are documented below and left untouched.

Verification was done by rendering, not by reading. Every page below was loaded in
a real browser against `php artisan serve` on port 8020, in the `ar` locale
(`dir="rtl"`, `lang="ar"`), and measured in-page. Every PDF below was generated as
an actual file and rasterized with `pdftoppm` for visual glyph inspection.

Overflow was asserted programmatically on each page as
`document.documentElement.scrollWidth <= clientWidth`.

## Arabic Translation Coverage

The translation layer was already in good shape: `lang/ar` and `lang/en` are
key-for-key synchronized (enforced by `LocalizationConsistencyTest` and
`TranslationParityTest`, both passing), with no blank values.

Three genuine leaks of English into the `ar` locale were found and fixed. All three
had the same root cause: the API returns a **pre-rendered English display string**
(`category.type_label`, `vendor.business_type_label`) next to the raw enum, and the
frontend was rendering the English string directly.

| Location | Was | Now |
| --- | --- | --- |
| `resources/js/Pages/Employee/Dashboard.jsx` | `Veterinary` / `Agriculture` in the products-by-type card | `بيطري` / `زراعي` |
| `resources/js/Pages/Employee/Dashboard.jsx` | hardcoded `{pct}% of all products` | `100% من كل المنتجات` |
| `resources/js/Pages/Vendors/Show.jsx` | `Agriculture` under the store name | `زراعي` |
| `resources/js/Pages/Vendor/Profile.jsx` | `business_type_label` and category `type_label` badges | localized, English kept only as fallback |

A second sweep cross-checked every `group.key` referenced in JSX against the `ar`
lang files, to catch keys that render blank or fall through to an English `??`
default. Three more genuine leaks were found:

| Location | Was | Now |
| --- | --- | --- |
| `Components/admin/CsvImportButton.jsx` | mixed-language `Import المنتجات` on the button and dialog title, from a `common.import ?? \`Import ${label}\`` fallback against a key that did not exist | `استيراد المنتجات` |
| `Pages/Syndicate/Dashboard.jsx` | `syndicate.noData` referenced in camelCase against a snake_case lang file, so three empty states rendered blank | `لا توجد بيانات لعرضها بعد.` |
| Admin filter summary | `admin.active_filters` missing | `عوامل التصفية المطبقة` |

The `CsvImportButton` fix reaches five screens (Admin Products, Categories, Cities,
Subcategories and Vendor Products), which all share the component.

A third sweep closed a blind spot in the checker itself. It only matched
`group.key` where `group` was the literal lang-group name destructured from
`useI18n()`; a page that passes a group down to a child under a different prop name
was invisible to it. `Pages/Syndicate/Dashboard.jsx` does exactly that
(`i18n={syndicate}`), and it was hiding two more missing keys:

| Key | Symptom |
| --- | --- |
| `syndicate.th_city` | blank column header on the syndicate vendors table |
| `syndicate.last_activity` | blank column header on the same table |

Both were genuinely absent from `lang/{ar,en}/syndicate.php`, so the headers
rendered empty — and in the new card layout the same two columns produced worse
output: a value with no label at all, so `دمشق` and `13‏/09‏/2026` sat in the card
as orphans. Adding `'th_city' => 'المدينة'` and `'last_activity' => 'آخر نشاط'`
fixed both surfaces at once. Verified live: the table header now reads
`المتجر | النوع | المدينة | المنتجات | مكتملة | المبيعات المكتملة | آخر نشاط | الحالة | الإجراءات`
with zero empty `<th>`, and the card rows read `المدينة => دمشق`,
`آخر نشاط => 13‏/09‏/2026`.

A second checker covering the alias pattern (`i18n`, `copy`, `labels`, `vendorCopy`)
across all of `resources/js` now reports no other alias reference without a matching
key. Re-running the original checker leaves 21 hits, all verified false positives:
route-name strings (`employee.notifications.index`, `products.show`), local data
objects (`vendor.address` in an edit form), and two optional keys that fall back to
existing Arabic values (`home.products_error ?? home.no_products_yet`,
`nav.primary_navigation ?? nav.menu`) — neither leaks English or renders blank.

New keys were added to both locales (so parity holds): `employee.of_all_products`,
`employee.type_agriculture`, `employee.type_veterinary`, `vendors.type_*`,
`vendor.type_*`, `vendor.completed_orders`, `common.import`, `syndicate.no_data`,
`admin.active_filters`.

The pattern used everywhere is `t[`type_${raw_enum}`] ?? api_english_label`, which
matches the existing correct usage in `Pages/Admin/Dashboard.jsx`.

**Hardcoded-string scanner:** 193 files scanned, 13 findings, **0 genuine
user-facing strings**. All 13 are legitimately exempt: the brand name `Vetora`,
asset paths (`/images/product-placeholder.svg`), URL and e-mail input placeholders
(`https://…`, `owner@example.com`, `support@vetora.test`), the IANA timezone
`Asia/Damascus`, and `type_default`, which is a translation-key fallback rather
than display text. The scanner was not modified.

## RTL Correctness

No defects found in the app shell. The codebase uses logical CSS properties
throughout (`ms/me/ps/pe/start/end`) with zero physical `margin-left`-style utility
classes and zero physical direction rules in CSS. Directional icons use `rtl:rotate-180`.
Dynamic user content (store names, product names) carries `dir="auto"`.

RTL defects **were** found in the PDF templates and are covered under PDF below.

The invoice template previously applied `text-transform: uppercase` and
`letter-spacing: 0.08em` to table headers and section labels. Uppercasing is a no-op
in Arabic, and letter-spacing actively breaks cursive joining by pulling connected
strokes apart. Both are now disabled under `[dir="rtl"]`.

## Responsive Matrix

Measured value is `scrollWidth` vs `clientWidth` on the document element. "PASS"
means no full-page horizontal overflow.

| Screen | 375 | 768 | 1024 | 1440 |
| --- | --- | --- | --- | --- |
| Admin — Dashboard | PASS | PASS | PASS | PASS |
| Admin — Users | PASS | PASS | PASS | PASS |
| Admin — Products | PASS | PASS | PASS | PASS |
| Admin — Product Reviews | PASS | PASS | PASS | PASS |
| Admin — Financials | PASS | PASS | PASS | PASS |
| Admin — Vendor Commission | PASS | PASS | PASS | PASS |
| Public — Home | PASS | PASS | PASS | PASS |
| Public — Products | PASS | PASS | PASS | PASS |
| Public — Categories | PASS | PASS | PASS | PASS |
| Public — Vendor Show | PASS | PASS | PASS | PASS |
| Vendor — Dashboard | PASS | PASS | PASS | PASS |
| Vendor — Orders | PASS | PASS | PASS | PASS |
| Vendor — Sales | PASS | PASS | PASS | PASS |
| Vendor — Products | PASS | PASS | PASS | PASS |
| Syndicate — Dashboard (incl. map) | PASS | PASS | PASS | PASS |
| Syndicate — Categories | PASS | PASS | PASS | PASS |
| Syndicate — Vendors | PASS | PASS | PASS | PASS |
| Syndicate — Products | PASS | PASS | PASS | PASS |
| Syndicate — Podcasts | PASS | PASS | PASS | PASS |
| Syndicate — Orders | PASS | PASS | PASS | PASS |
| Syndicate — Sales | PASS | PASS | PASS | PASS |
| Syndicate — Reports | PASS | PASS | PASS | PASS |
| Syndicate — Notifications | PASS | PASS | PASS | PASS |
| Employee — Dashboard | PASS | PASS | PASS | PASS |
| Employee — All Products | PASS | PASS | PASS | PASS |
| Employee — Product Review | PASS | PASS | PASS | PASS |
| Employee — Product Show | PASS | PASS | PASS | PASS |
| Employee — Notifications | PASS | PASS | PASS | PASS |
| Invoice print view | PASS | PASS | PASS | PASS |

Admin Product Reviews was also swept at 320 and 430 (PASS at both).

One genuine layout defect was found at 768 and fixed.

**The workspace shell could be pushed wider than the viewport by its own content.**
`SidebarInset` renders `<main>` as a flex item without `min-w-0`, so its automatic
minimum size was `min-content` rather than zero. On any page whose topbar title is
long — Admin Product Reviews, whose title is `التقييمات — {product name}` — the
header's min-content width (583px) exceeded the space left beside the 272px sidebar
(496px), and the *whole page* scrolled sideways by 87px instead of the title
truncating as intended. Short-titled pages such as Admin Users were unaffected,
which is why this only surfaced on a long title.

The fix is `min-w-0` on `SidebarInset` in `resources/js/Components/ui/sidebar.jsx`.
Confirmed by direct measurement: clamping `min-width` to 0 took `scrollWidth` from
855 back to 768 at a 768 viewport. This was verified not to be caused by the table —
hiding the table entirely left the overflow unchanged.

## Mobile Tables

Every table in the operator workspaces now renders as one card per row below the
`md` breakpoint (768px) instead of as a sideways-scrolling table. A column header
only carries meaning while the columns line up side by side; at phone width they
cannot, so the label moves next to its own value.

The card is defined once, in `resources/js/Components/shared/RecordCard.jsx`
(`RecordCard`, `RecordCardList`, `RecordCardSkeleton`). The first column becomes the
card title and carries the row link when the table has one; an `actions` column
drops its label and sits in the card footer; a cell that renders blank is omitted
rather than shown as an empty line.

`DataTable` builds its cards from the same column definitions it already had, so all
14 pages that use it were converted without touching those pages. Five pages build
raw `<Table>` markup and were converted individually:

Every one of the 19 table-bearing pages was then rendered and measured at 375 in
Arabic RTL, signing in as each role in turn (admin, vendor, syndicate, employee).

| Page | Cards at 375 | Table at ≥768 |
| --- | --- | --- |
| Admin — Users | 5 | yes |
| Admin — Vendors | 2 | yes |
| Admin — Products | 15 | yes |
| Admin — Orders | 6 | yes |
| Admin — Categories | 16 | yes |
| Admin — Subcategories | 21 | yes |
| Admin — Cities | 6 | yes |
| Admin — Syndicates | 2 | yes |
| Admin — Banners / Coupons / Pages | empty state | n/a |
| Admin — Product Reviews | 2 (seeded) | yes |
| Admin — Financials | 1 | yes |
| Admin — Vendor Commission | 4 | yes |
| Vendor — Orders | 6 | yes |
| Vendor — Product Reviews | 1 (seeded) | yes |
| Vendor — Sales (ledger + by-category) | 8 + 4 | yes |
| Syndicate — Vendors | 1 | yes |
| Syndicate — Products | 8 | yes |
| Syndicate — Categories | 8 | yes |
| Syndicate — Orders | 6 | yes |
| Employee — Products | 16 | yes |

Each check asserted in the live DOM that no `<table>` was visible, that the expected
number of cards was present, that no card had an empty label, and that
`scrollWidth <= clientWidth`; and at 768/1024/1440 that the table was visible and the
cards were not. Card contents were read back to confirm Arabic labels and
shared-formatter output, e.g.
`عمولة | التاريخ | 17‏/09‏/2026 | القيد | مدين | المبلغ | ‏58.80 ل.س‏`.

Banners, Coupons and Pages have no seeded rows. They were confirmed to reach
`DataTable`'s shared empty state (`لا توجد لافتات.`) rather than rendering nothing —
that branch returns before either the cards or the table, so it is unaffected.

Admin Vendor Commission was reported in the previous pass as reached only in its
empty state. That was a measurement error on my part, not an empty branch: the page
loads its stats asynchronously and I measured before they arrived. Re-checked
against the API (`/api/admin/vendors/1/commission-stats` returns four breakdown
rows) and then in the DOM, it renders four cards at 375 and the table at 768.

The Admin and Vendor review pages had no rows in the development database, so review
rows were temporarily inserted to exercise the populated card path, then deleted.
The database is back to zero `product_reviews` rows.

## Money and Number Formatting

There is one shared frontend money formatter, `formatCurrency` in
`resources/js/lib/date-time.js`, used by 73 call sites. It had two visible defects:

1. With only `maximumFractionDigits` set, ICU drops trailing zeros, so a column read
   `435,000` next to `1,234.5` and the decimal points never lined up. An explicit
   `minimumFractionDigits: 2` fixes the alignment.
2. ICU's Arabic symbol for SYP is `ل.س.` with a trailing full stop, while the invoice
   and the PDF reports (from `lang/*/reports.php`) use `ل.س`. The same amount was
   spelled two ways depending on where you looked. The formatter now renders from
   `formatToParts` and strips that trailing stop so the app agrees with the PDFs.

Verified output — `ar`: `435,000.00 ل.س`, `1,234.50 ل.س`, `0.00 ل.س`; `en`:
`SYP 435,000.00`. Confirmed live on Vendor Sales and on the invoice.

Bidi marks that ICU emits are deliberately left in place; they isolate the amount
correctly when it sits inside an opposite-direction sentence.

**No monetary value was altered. This is formatting only.**

Three un-localized number/date render sites were also fixed to use the shared
helpers: `Components/ui/chart.jsx` (tooltip `toLocaleString()` with no locale),
`Components/workspace/NotificationCenter.jsx`, and
`Pages/Admin/ContactMessages/Index.jsx` — the last of which called
`new Date(...).toLocaleString()` with no locale at all, so it rendered in the
viewer's OS locale regardless of the app language. Timezone behavior is unchanged.

A follow-up sweep for raw date rendering found four more sites now routed through
the shared `formatDate`: `Pages/Orders/Show.jsx`, `Pages/Vendor/Products/Reviews.jsx`,
`Pages/Admin/Products/Reviews.jsx`, and `Pages/Pages/Show.jsx`. The last was calling
`toLocaleDateString('ar')`, which resolves to Modern Standard Arabic month names
(`سبتمبر`); the shared helper pins `ar-SY`, which is what the rest of the app renders
(`أيلول`).

`Components/workspace/NotificationBell.jsx` was checked and deliberately left alone:
`Intl.RelativeTimeFormat('ar')` was suspected of emitting Arabic-Indic digits, but
testing it in the browser returned `قبل 3 دقائق` — Latin digits, identical to the
`-u-nu-latn` variant. It is not a defect.

`resources/js/workspace-shell.js` contains an unlocalized `toLocaleDateString`, but
it is dead code: `app.blade.php` loads only `app.jsx`, and no route renders the
legacy Blade entry. It is not user-facing and was not changed.

## PDF and Print Matrix

All four PDFs were generated as real files and rasterized at 150–300 DPI for visual
inspection. Glyph shaping, joining and the absence of tofu were confirmed by eye on
the rasterized pages, not inferred from text extraction.

| Report | Locale | File | Pages | Glyphs | Layout |
| --- | --- | --- | --- | --- | --- |
| Syndicate vendor report | ar | `vendor-report-ar-after.pdf` | 2 | PASS | PASS |
| Syndicate vendor report | en | `vendor-report-en-after.pdf` | 2 | PASS | PASS |
| Syndicate general report | ar | `syndicate-general-ar-after.pdf` | 1 | PASS | PASS |
| Syndicate general report | en | `syndicate-general-en-after.pdf` | 1 | PASS | PASS |
| Invoice print view | ar | rendered in browser | 1 | PASS | PASS |

Two severe Arabic PDF defects were found and fixed in both report templates:

**Fused label/value pairs.** mPDF does not honour `display:block` or `inline-block`
on an inline `<span>`. Every `<span class="lbl">Label</span>Value` pair in the meta
and KPI blocks therefore rendered as one run-together string. Labels and values are
now separate table cells (`<td class="lbl">` / `<td class="val">`), and KPI labels
are block-level `<p>` elements.

**Inconsistent money direction.** Money cells carried `.number`, which forces
`direction: ltr`. That reordered `1,234.00 ل.س` in table cells against the
identically-formatted figures in the KPI blocks — the same amount rendered two
different ways on the same page. Money now uses a separate `.money` class that keeps
the page direction and only makes the digits tabular. Bare figures and ISO dates
keep `.number` (they are genuine LTR runs).

Also fixed: order and invoice identifiers were breaking mid-token across two lines
(`ORD-20260917-41` / `477`); they now use a `.code` class with `white-space: nowrap`
and an explicit LTR embedding. Table column widths were rebalanced in the orders,
returns and product-performance tables to remove the crowding this exposed.

`generated_at` and period stamps are wrapped in `<bdi dir="ltr">`.

The mPDF font configuration (`dejavusans`) was deliberately **not** changed. Text
extraction from the PDFs appears to drop letters (`المتملة` for `المكتملة`), which
looks alarming, but 300 DPI rasterization confirms the glyphs are rendered and
joined correctly. That is a PDF text-extraction artifact of presentation forms, not
a rendering defect. Changing the font here would have been a fix for a bug that
does not exist.

## Invoice

The invoice makes quantity obvious: it sits in its own dedicated column with a
pill-shaped badge at `font-weight: 800`, not buried in a line of text.

It fits on one A4 page when content allows, without shrinking type: measured content
height is 486px against roughly 1054px of printable A4 height at 9mm margins. The
print stylesheet hides the app's print bar (`.print-bar { display: none !important }`),
so no app chrome reaches paper, and sets `break-inside: avoid` on the header, parties
block, rows, totals and payment sections.

Typography was aligned with the app: the invoice now loads and uses `Manrope` +
`IBM Plex Sans Arabic` (the app's own `--font-sans`) rather than falling back to
`Segoe UI`/`Tahoma`, so the printed document and the screen match.

## Light and Dark Mode

Dark mode was toggled through the app's own control and verified on the Employee
dashboard at 1440 in Arabic RTL: background resolves to `rgb(14, 23, 28)`, foreground
to `rgb(241, 245, 244)`, cards, charts, badges and the sidebar all render correctly
with no unreadable or unthemed regions. No dark-mode defect found.

## Accessibility

All interactive controls in the app shell expose Arabic accessible names — verified
by reading `aria-label`/`title` off the live DOM: `اختر اللغة`, `الإشعارات`,
`تبديل الوضع الفاتح أو الداكن`, `تسجيل الخروج`. Decorative icons are
`aria-hidden="true"`. Touch targets on notification actions use `min-h-11`.

One suspected defect was investigated and dismissed: the accessibility tree reports a
sidebar link with no accessible name, but the anchor's own `innerText` is `العمولات`
and it is `display: block`. It is not a defect and is not reported as one.

## Console Cleanliness

No React exceptions, no hydration errors, no missing-translation warnings and no
asset 404s across the pages exercised. The only console output is the local dev
Boost browser-logger info line. Two stale `405`/`419` entries in the buffer are from
manual logout attempts made during this session, not from application code.

## Build and Test Results

| Check | Result |
| --- | --- |
| `npm run build` (client + SSR) | PASS |
| `git diff --check` | clean |
| `vendor/bin/pint --test` | `{"result":"pass"}` |
| `php artisan test --filter='Localization\|Report\|Invoice\|StakeholderUiStructure'` | 45 passed, 371 assertions |
| `php artisan test` (full suite) | 490 passed, 11983 assertions |
| `node scripts/scan-hardcoded-text.mjs` | 13 findings, 0 genuine |

Two follow-ups were needed to reach the results above.

**Line endings.** Editing the `lang` files on Windows wrote CRLF into six of them,
which `pint --test` rejected (`line_ending`). They were converted back to LF.

**One test assertion was updated.** `tests/Feature/StakeholderUiStructureTest.php:99`
asserted the general report contains `<span class="lbl">…</span><strong>`. The
fused-label fix documented under PDF replaced that `<span>` with a `<p>`, precisely
because mPDF ignores `display:block` on an inline element. The assertion's stated
intent — a `.lbl` element immediately followed by `<strong>`, never a bare `<br>` —
is unchanged; only the tag name in the expected string was updated, and the comment
now records why it is a `<p>`. This is the one file changed outside the declared
frontend scope, and it is a test expectation rather than behavior.

## Backend Defects — Documented Only, Not Changed

Both were found through frontend symptoms. Per scope, neither was touched.

**1. Locale falls back to the browser's `Accept-Language` before the app default.**
`app/Http/Middleware/SetLocale.php:58` consults
`$request->getPreferredLanguage(['ar', 'en'])` *before* `config('app.locale')` at
line 63. Since the app default is `ar`, a first-time visitor (or any user whose
session was just regenerated by login/logout) arriving from an English-language
browser gets an English UI despite the application being Arabic-first. Reproduced
in this session: after logging out and back in, the app served `lang="en"`,
`dir="ltr"` with `config('app.locale') === 'ar'`. Fixing this means reordering the
fallback chain, which is middleware behavior, not presentation.

**2. Order line items snapshot English product names.**
`app/Services/Vendor/VendorAnalyticsService.php:256` reads
`'name' => $item->product_name`, which is the frozen snapshot written into
`order_items` at purchase time. Arabic orders therefore display English product
names in the vendor report tables and on the invoice (visible as
`Premium Wheat Seeds` on invoice `INV-20260905-22406`). This is data captured at
write time; no presentation-layer change can recover the Arabic name.

**3. Minor observation.** The API serializes `category.type_label` and
`vendor.business_type_label` as English display strings regardless of the request
locale. The frontend now works around this by preferring the raw enum, but the
cleaner fix is to localize these on the server.

## Files Changed

```
lang/ar/admin.php                                   lang/en/admin.php
lang/ar/common.php                                  lang/en/common.php
lang/ar/employee.php                                lang/en/employee.php
lang/ar/syndicate.php                               lang/en/syndicate.php
lang/ar/vendor.php                                  lang/en/vendor.php
lang/ar/vendors.php                                 lang/en/vendors.php

resources/js/Components/shared/RecordCard.jsx       (new)
resources/js/Components/shared/DataTable.jsx
resources/js/Components/admin/CsvImportButton.jsx
resources/js/Components/ui/chart.jsx
resources/js/Components/ui/sidebar.jsx
resources/js/Components/workspace/NotificationCenter.jsx
resources/js/Pages/Admin/ContactMessages/Index.jsx
resources/js/Pages/Admin/Financials/Index.jsx
resources/js/Pages/Admin/Products/Reviews.jsx
resources/js/Pages/Admin/Vendors/Commission.jsx
resources/js/Pages/Employee/Dashboard.jsx
resources/js/Pages/Orders/Show.jsx
resources/js/Pages/Pages/Show.jsx
resources/js/Pages/Syndicate/Dashboard.jsx
resources/js/Pages/Vendor/Commission.jsx
resources/js/Pages/Vendor/Products/Reviews.jsx
resources/js/Pages/Vendor/Profile.jsx
resources/js/Pages/Vendors/Show.jsx
resources/js/lib/date-time.js

resources/views/invoices/print.blade.php
resources/views/reports/syndicate-general.blade.php
resources/views/reports/syndicate-vendor.blade.php

tests/Feature/StakeholderUiStructureTest.php        (expectation only)
```

34 tracked files changed (+339 / −61), plus two new files
(`RecordCard.jsx` and this report).

Nothing was committed, pushed or deployed.

## Evidence

PDF artifacts and rasterized page images are under
`qa-artifacts/arabic-frontend-final/pdf/`, including before/after pairs for the
vendor report and zoomed crops of the KPI and money regions that demonstrate the
fused-label and money-direction fixes. These artifacts are intentionally left
uncommitted.

Browser verification was performed live in-session; page measurements and rendered
text are quoted inline in the sections above rather than stored as image files.

## Remaining Frontend-Only Issues

None blocking. Three cosmetic notes:

- On the Admin Products cards, the status column renders label `نشط` above value
  `نشط`, which is redundant. The card layer is faithfully reproducing that column's
  existing definition, where the header and the badge carry the same word; changing
  it means changing the column config, not the card.

- The syndicate report header renders the syndicate's seeded logo, which is a
  placeholder product image with a baked-in transparency checkerboard. This is seed
  data, not a template defect.
- `/vendor/products` uses `إجمالي المنتجات` ("total products") as its page title,
  which reads like a metric label rather than a page name. Terminology only.
