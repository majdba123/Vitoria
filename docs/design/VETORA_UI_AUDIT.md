# Vetora UI Audit

**Audit scope:** Design audit only. Laravel Blade, Tailwind CSS v4, vanilla JavaScript/Axios, Arabic/English, RTL/LTR, light/dark storefront and all operational workspaces.

**Proposed art direction:** **Vetora Precision Agritech Commerce**

**Severity model**

- **CRITICAL** — damages brand recognition, commerce comprehension, task completion, accessibility, or cross-surface consistency; address before visual refinement.
- **REFINEMENT** — materially improves hierarchy, density, credibility, and usability after critical foundations are stable.
- **POLISH** — improves finish or delight without changing comprehension.

## Evidence base and audit method

This audit is based on the actual repository, including `resources/css/app.css`; every layout and component under `resources/views/layouts/**` and `resources/views/components/**`; `resources/views/home.blade.php`; storefront categories, products, and vendors; authentication; admin, vendor, employee, and syndicate workspaces; and the markup assembled in JavaScript template strings or DOM APIs inside Blade files. The logo at `public/images/vetora-logo-transparent.png` was visually inspected as the brand source of truth.

The audit found 97 Blade views. Across the view layer and `app.css`, there are approximately 234 `rounded-full`, 232 `rounded-2xl`, 15 `rounded-3xl`, 89 arbitrary-radius, 88 gradient, 50 backdrop-blur, 39 `shadow-xl`/`shadow-2xl`, 27 hover-translate, 34 hover-scale, 176 `font-black`, and 46 `font-extrabold` occurrences. Emerald appears about 282 times; `brand-*` appears about 609 times, but `brand-*` is currently an emerald scale rather than a logo-derived Vetora scale. These counts are not defects by themselves; their distribution and repeated use for unrelated meanings are the problem.

Sixty-three Blade files generate or mutate markup with `innerHTML`, `outerHTML`, `createElement`, or related DOM operations. This materially expands the design-system surface beyond static Blade templates and is treated as part of the UI in every finding below.

## 1. Executive assessment

Vetora is visually polished but not visually authored around veterinary and agricultural commerce. Its current language is a coherent collection of contemporary frontend effects—large rounded translucent surfaces, green gradients, floating controls, pills, glows, heavy display weights, and hover lifts—but those effects are applied so consistently to unrelated content that they erase product, category, store, and workspace differences. The interface reads as “a polished SaaS kit filled with marketplace data” rather than a marketplace whose visual system grew from product comparison, merchant credibility, and agricultural context.

- **CRITICAL — The application’s declared brand is the wrong color identity.** `resources/css/app.css:11-20` defines `brand-*` as Tailwind emerald. The inspected logo is a blue-to-cyan serif/leaf wordmark centered around approximately `#297497`, `#288BAD`, and `#29A9D1`. Because `brand-*` drives buttons, focus rings, links, panels, charts, shadows, glows, badges, navigation, and dashboards, the mismatch is systemic rather than local.
- **CRITICAL — Marketplace hierarchy is weaker than surface styling.** On home and listing pages, category badges, metadata pills, rating, price, availability, favorite, and cart actions compete inside equally ornamental cards. Commercial decision data—pack/unit, manufacturer or commercial name, vendor identity, locality, stock confidence, and fulfillment—is either secondary, inconsistent, or missing.
- **CRITICAL — The same visual grammar represents unrelated things.** Categories, products, vendors, trust claims, dashboard metrics, navigation items, filter panels, auth benefits, and empty states all frequently become rounded cards with a small badge, heavy heading, supporting copy, icon chip, shadow, and lift. Users must read each surface to understand it; form alone communicates little.
- **CRITICAL — Store and vendor credibility is underdesigned.** `resources/views/vendors/show.blade.php` presents a generic gradient hero and a product grid but lacks a structured trust profile. It also diverges from the rest of the current dark-mode and localization conventions. In a marketplace, the seller is part of the product decision, not decoration above another product grid.
- **REFINEMENT — The workspaces are over-designed and under-differentiated.** Admin, vendor, employee, and syndicate layouts repeat translucent top bars, rounded square icon controls, decorative sidebars, stat tiles, badge-heavy headers, and card stacks. Role-specific operational priorities are visually flattened into the same dashboard kit.
- **REFINEMENT — Existing strengths make a disciplined redesign feasible without behavioral changes.** The application already has logical properties, a skip link, global focus styling, reduced-motion handling, semantic status colors, responsive grids, named Blade components, loading/empty/error states, and first-class `dir` on layouts. The redesign can be primarily a visual-system and information-architecture correction while preserving routes, APIs, and behavior.

## 2. Current visual language

The current language is “soft premium SaaS”: Manrope/Sora plus IBM Plex Sans Arabic; misted gradient page backgrounds; translucent white/dark panels; emerald brand accents; 24–36px radii; capsule badges; bold and black type; blurred navigation; glow-backed dark heroes; soft drop shadows; line icons in rounded squares; and cards that rise or zoom on hover.

- **CRITICAL — Decorative depth is the default material.** `resources/css/app.css:43-80` layers radial and linear gradients into both site and dashboard backgrounds. `.glass-panel`, `.dashboard-panel`, `.surface-card`, `.card`, `.dashboard-topbar`, `.dropdown-panel`, and `.toast-shell` add blur or translucent surfaces. Depth no longer distinguishes overlays from normal page content.
- **REFINEMENT — The radius language has no semantic hierarchy.** Global components use 12, 16, 24, 26, 28, 30, 32, 34, and 36px radii. `.surface-card` is 28px, `.surface-card-muted` 24px, `.workspace-hero` 34px, `.storefront-hero` 36px, detail panels 30px, gallery 32px, and small controls commonly 16px. The values appear tuned per composition rather than assigned by component role.
- **REFINEMENT — “Premium” is signaled by effects rather than evidence.** Shadows, glow, blur, black-weight headings, and gradients recur even where a simple border, rule, or grouped table would provide better clarity.
- **POLISH — The current system has real consistency at the utility level.** Shared classes such as `.form-input`, `.btn-*`, `.badge-*`, `.admin-table`, `.page-shell`, and `.responsive-*` are useful consolidation points and should be retained conceptually, though restyled.

## 3. What currently works and should remain

- **CRITICAL — Keep the product-type decision and its behavior.** `resources/views/home.blade.php:7-90` explicitly separates agriculture and veterinary browsing and persists selection through existing routes/services. This is domain-specific and more meaningful than a generic marketplace homepage. Redesign its presentation, not its logic.
- **CRITICAL — Keep semantic status colors.** Success/approved/available in emerald, warning/pending in amber, destructive/error in rose/red, and informational states in blue are directionally correct in `.badge-*` and operational views. The correction is to stop using emerald for brand and reserve it for these states.
- **REFINEMENT — Keep logical direction support.** `dir` and locale are set at the document level in `resources/views/layouts/app.blade.php`, and the CSS uses logical properties such as `border-inline-*`, `start-*`, and `text-start` in important places.
- **REFINEMENT — Keep accessible foundations.** The skip link, visible global focus treatment, `prefers-reduced-motion`, dialog labels on several overlays, loading/empty/error feedback, explicit labels on many forms, and keyboard Escape handling for the mobile drawer are solid foundations.
- **REFINEMENT — Keep real marketplace metadata.** Product category/subcategory, commercial name, barcode, discount, quantity, vendor, ratings, and reviews already exist across `products/index.blade.php`, `products/show.blade.php`, and dashboard product views. The redesign should prioritize and normalize these fields rather than invent new backend data.
- **REFINEMENT — Keep image fallbacks and loading states.** `product-placeholder.svg`, category/vendor fallbacks, skeletons, and lazy-loaded images support production resilience.
- **POLISH — Keep the logo itself.** The serif wordmark and leaf-mark provide more agricultural character than the surrounding geometric SaaS UI. It should become the design anchor.

## 4. AI-looking patterns and why they feel synthetic

- **CRITICAL — Repetitive card grammar.** `.card`, `.surface-card`, `.surface-card-muted`, `.stat-tile`, `.metric-card`, `.storefront-category-card`, `.storefront-product-card`, `.storefront-subcard`, `.storefront-detail-panel`, and `.list-panel` largely vary the same recipe: pale/translucent surface + large radius + border + soft shadow + bold heading. When every object is a card, none communicates a unique object type.
- **CRITICAL — Generic section-intro formula.** Home categories, latest products, best sellers, most favorited, top rated, vendors, and dashboard sections repeatedly use “colored pill/eyebrow → black heading → gray paragraph → View All.” See `components/home/categories.blade.php:5-8`, `products.blade.php:5-11`, `best-selling-products.blade.php:5-10`, and `top-rated-products.blade.php:5-10`. This is a recognizable generated landing-page cadence.
- **CRITICAL — Decorative glow/blob language contradicts the requested market character.** `app.css` uses radial glows in the site shell, dashboard shell, product media, thumbnails, workspace hero, auth shell, storefront hero, and card pseudo-elements. `auth/login.blade.php:10-11` adds blurred circular blobs explicitly. These effects imply generic digital novelty, not agricultural authenticity or technical commerce.
- **REFINEMENT — Glassmorphism is overextended.** The main navigation is a floating `.glass-panel`; product/category page headers use `bg-white/60 backdrop-blur-xl`; dashboard panels/top bars blur; favorite controls blur; modal overlays blur. Blur is appropriate for transient overlays, but persistent content surfaces should be opaque and stable.
- **REFINEMENT — Excessive hover lift makes the UI feel demonstrative.** `.product-card`, `.cat-card`, and `.vendor-card` rise 6px globally; trust badges rise; type cards rise; dashboard links rise; favorite buttons scale; product images scale; social buttons rise. Repeated motion says “look at the component” instead of confirming an action.
- **REFINEMENT — Excessive font weight substitutes for hierarchy.** 222 `font-black`/`font-extrabold` occurrences make labels, page titles, prices, metrics, badges, and controls all shout. Hierarchy then depends on size and color rather than weight contrast and whitespace.
- **REFINEMENT — Generic icon-chip grammar.** Rounded square icon tiles recur in trust badges, stats, promo blocks, sidebars, auth, and dashboard links. Their ubiquity makes each context look assembled from the same template.
- **POLISH — Gradients are often ornamental rather than informative.** Gradient buttons, avatars, sidebar logos, vendor hero, dashboard hero, page backgrounds, media wells, and card overlays collectively produce a synthetic sheen. Retain only the logo’s restrained tonal transition or a single photographic treatment where it supports content.

## 5. Brand/logo color mismatch analysis

The inspected `vetora-logo-transparent.png` is a transparent blue/cyan serif wordmark with leaf forms. Its visual family is approximately `#297497`, `#288BAD`, and `#29A9D1`. The current CSS declares emerald `#10B981` as `--color-brand-500`, with the entire `brand-*` scale copied from Tailwind emerald.

- **CRITICAL — Rename and replace the current brand scale.** Every `brand-*` usage currently communicates Vetora even though it is green. Rebuild `brand-*` from the logo family. Keep `success-*` as green and migrate only state-specific emerald usage to success tokens.
- **CRITICAL — Emerald is incorrectly used as identity in page atmosphere and controls.** Examples include `--site-bg`, `--dashboard-bg`, `--site-ring`, `.text-gradient`, `.btn-primary`, `.dashboard-sidebar`, `.workspace-hero`, `.storefront-hero`, category selections, navigation hover states, pagination active states, auth focus, and card borders in `app.css`. These are brand/action roles, not success roles.
- **CRITICAL — Emerald is also incorrectly used in content labeling.** `components/home/products.blade.php:6`, `best-selling-products.blade.php:6`, vendor workspace labels, generic eyebrows, navigation, and “brand” badges use emerald without expressing success.
- **REFINEMENT — Some emerald is correct and should remain semantic.** In-stock in `products/show.blade.php:295`, approved/active metrics in dashboards, successful contact submission, verified or confirmed statuses, and cart-success feedback are appropriate green uses.
- **REFINEMENT — The logo is inconsistently represented.** The navbar uses the real image, but the footer in `layouts/app.blade.php` replaces it with a generic gradient storefront icon and text; dashboard sidebars use generic gradient icon containers. This fragments recognition and discards the logo’s strongest character.
- **REFINEMENT — Logo cyan needs accessible pairing rules.** White text on `#297497` is approximately 5.20:1 and passes WCAG AA for normal text; white on `#288BAD` is approximately 3.90:1 and should be limited to large text/icons; white on `#29A9D1` is approximately 2.73:1 and fails for text. The lighter logo tones should be accents, charts, rules, or backgrounds with dark ink—not primary button fills with white labels.
- **POLISH — The leaf motif can inform details without becoming decoration.** Use it sparingly in crop marks, empty-state illustration, or category imagery. Do not repeat leaf blobs as background ornaments.

## 6. Typography audit

- **CRITICAL — The type personality conflicts with the logo.** `app.css` pairs Manrope and Sora with IBM Plex Sans Arabic. Sora’s geometric startup character competes with the logo’s serif, agricultural wordmark and contributes to the SaaS feel.
- **REFINEMENT — Arabic and Latin hierarchy do not behave equivalently.** IBM Plex Sans Arabic is a sound UI face, but the same `font-black`, uppercase, and wide `tracking-[0.18em–0.24em]` patterns are designed around Latin. Arabic ignores uppercase and often suffers from forced tracking/very small 11px labels. Create script-aware label styles rather than reusing Latin eyebrow treatment.
- **REFINEMENT — Weight distribution is compressed at the top.** Black/extrabold is used for navigation, headings, prices, metrics, labels, and CTAs. Reserve 700/800 for true headings or key commercial numbers; use 500/600 for UI labels and 400/500 for body and metadata.
- **REFINEMENT — Tiny uppercase metadata is overused.** 10–11px uppercase with wide tracking appears in dashboard labels, card eyebrows, store/category metadata, and footer headings. It creates visual polish at the cost of scanability and Arabic parity.
- **REFINEMENT — Product typography does not create a comparison rhythm.** Product title, commercial name, pills, rating, price, currency, discount, and button are stacked with many weights and sizes. Establish a repeatable merchandise hierarchy: product identity → pack/commercial detail → seller/availability → price → action.
- **POLISH — Proposed pairing direction.** Keep IBM Plex Sans Arabic for Arabic UI/body if licensing and loading remain suitable. Evaluate a humanist or editorial Latin sans with less startup geometry for UI, while allowing the existing logo—not a second decorative display face—to provide brand character. Do not change fonts until bilingual specimens are tested with actual translations, numerals, units, and product names.

## 7. Navigation audit

- **CRITICAL — The primary nav is a floating glass capsule.** `components/navbar.blade.php:3` uses a rounded 28px glass panel with a green-tinted custom shadow. This visually separates navigation from the marketplace and resembles a landing-page component rather than durable commerce chrome.
- **CRITICAL — Information architecture is split and repetitive.** The mega-menu button, separate Products and Categories links, product-type context, mobile category accordion, account controls, notifications, cart, theme, and language all compete in a compact floating shell. The current UI does not clearly answer: which market am I in, what am I browsing, and what is my next shopping action?
- **REFINEMENT — Controls overuse rounded squares and pills.** Theme, cart, notification, menu, profile, product/category links, and category triggers all share rounded icon-button grammar. Use shape and grouping to distinguish global utilities from catalog navigation.
- **REFINEMENT — Mega-menu width is rigid.** `w-[780px]` at `navbar.blade.php:21` is fragile near the desktop breakpoint, with long Arabic category names, browser zoom, or reduced viewport width.
- **CRITICAL — Generated mega-menu buttons hard-code `text-left`.** `navbar.blade.php:676`, `690`, and other generated markup use physical text alignment despite RTL document direction. Replace in the future with logical `text-start`; this audit does not change code.
- **CRITICAL — Mobile drawer animation is directionally wrong.** The drawer moves to the left in RTL via classes, but `slideInRight` always animates from `translateX(100%)` in `app.css`. RTL should enter from its anchored side.
- **REFINEMENT — Mobile drawer has partial, not complete, dialog behavior.** It moves focus to a button and handles Escape, but does not trap focus or restore inertness to underlying content. The mega menu and dropdown triggers also lack consistent `aria-expanded`/`aria-controls` state.
- **POLISH — The real logo in the navbar works.** Preserve it, give it quiet space, and avoid boxing it in another decorative tile.

## 8. Homepage audit

- **CRITICAL — The hero explains a flow instead of merchandising the market.** `components/home/hero.blade.php` uses a dark photographic/glow hero, eyebrow, giant black heading, two CTAs, then nested glass strips explaining category → subcategory → product and support. It feels like product onboarding for SaaS. A marketplace hero should establish selection context, supply breadth, location/trust, and a direct path to relevant inventory.
- **CRITICAL — Product-type selection is strategically strong but visually overbuilt.** `home.blade.php:27-89` wraps two choices in a large surface card; each choice is another 28px card with 3xl icon tile, state pill, heavy heading, footer rule, and nested CTA capsule. The decision should be immediate and unmistakable, not a showcase of components.
- **CRITICAL — The page becomes several copies of the same product shelf.** Latest, best-selling, and most-favorited reuse identical cards and nearly identical intros. This creates length without adding a new browsing mode. Different collections should earn different display logic or be consolidated.
- **REFINEMENT — Category selection and the category bar duplicate context.** After selecting a type and category, `#sz-category-bar` introduces another rounded surface with visual, eyebrow, heading, helper copy, pills, and reset button. Persistent context is useful; the decorative container stack is not.
- **REFINEMENT — Trust badges are generic claims.** `components/home/trust-badges.blade.php` presents “Fast Delivery,” “Secure Shopping,” “Easy Returns,” and “24/7 Support” as four identical hover cards. Unless backed by actual policies and operational coverage, these are template-marketplace claims rather than credible trust evidence. Replace with verified, specific statements such as merchant approval, geographic coverage, payment methods, or support hours using existing facts.
- **REFINEMENT — Some home components appear inconsistent or possibly inactive.** `components/home/vendors.blade.php` and `top-rated-products.blade.php` contain English-only copy, purple/amber badges, older spacing, and different container conventions; the active `home.blade.php` composition does not render them in the main path shown. Delete stale presentation code only after route/render verification.
- **REFINEMENT — Contact is visually oversized relative to shopping.** The contact section receives a full surface-card treatment while core trust and vendor information remain weak. Contact should remain available but not compete with catalog exploration.
- **POLISH — Agricultural photography is directionally useful.** `hero-agriculture.webp` introduces domain authenticity. Its treatment should be editorial and restrained—fewer color overlays and no additional glow or blob layers.

## 9. Categories/subcategories audit

- **CRITICAL — Category cards do not make agricultural and veterinary taxonomies feel distinct.** `home.blade.php` and `categories/index.blade.php` render categories through similar rounded cards with image/icon, count/meta, description, and CTA. The visual system should emphasize category nomenclature, relevant imagery, and inventory count, not generic hover elevation.
- **CRITICAL — Category product cards are duplicated in JavaScript.** `categories/show.blade.php:125` contains a dense single-template-string implementation that differs from home, product listing, and vendor product cards. It includes English “Sold Out,” no image `alt`, physical left/right positioning, and its own badge/action order.
- **REFINEMENT — Subcategories are reduced to pills.** Horizontal capsule filters are efficient at small scale but degrade when Arabic labels are long or taxonomies are deep. On category pages, subcategories should be a structured filter/list with counts or grouping, not an endless chip row.
- **REFINEMENT — Backdrop-blurred page bands repeat across catalog pages.** `categories/index.blade.php:6`, `categories/show.blade.php:9`, and product pages use the same translucent header strip. This is decorative duplication and weakens location hierarchy.
- **REFINEMENT — Selected state depends heavily on green border/glow.** `.storefront-category-card.is-selected` and `.cat-card.ring-2` use emerald border and shadow. Future selected states need brand blue plus non-color cues such as a check, label, or stronger structural placement.
- **POLISH — Keep type-aware URLs and existing selection logic.** The logic preserves agriculture/veterinary context; presentation should make that context more legible.

## 10. Product card audit

There is no single product-card system. At minimum, separate markup grammars exist in `home.blade.php`, `products/index.blade.php`, `categories/show.blade.php`, `vendors/show.blade.php`, `profile.blade.php`, and admin/vendor/employee product views.

- **CRITICAL — Consolidate one storefront merchandise grammar.** Home uses `.storefront-product-card` with square media and a minimum 240px body; listing/category pages use 4:5 `.shop-card-media`; vendor detail uses older `rounded-xl shadow-lg` cards with descriptions and gradients. Price, rating, metadata, stock, and action placement changes by context, preventing learned scanning.
- **CRITICAL — Critical product information is encoded as decorative pills.** Category type, subcategory, barcode, discount, and stock are frequently capsules. Barcodes should not look like promotional tags; subcategory should not compete with discounts; availability must be a clear semantic line.
- **CRITICAL — Vendor identity is absent from most cards.** A marketplace buyer needs seller/store context, especially for trust, fulfillment, and comparison. Cards generally show product identity and price but not a clear merchant line.
- **CRITICAL — Pack/unit and commercial identity are inconsistent.** `commercial_name` appears on some cards; barcode and quantity appear elsewhere. Agricultural/veterinary products often require exact formulation, package, unit, or brand/manufacturer distinction. Use available fields consistently and do not invent data.
- **REFINEMENT — Media treatment prioritizes clean emptiness over product comparison.** Large contained images on gradient/radial wells can create excessive empty space for inconsistent source imagery. Normalize image frame and background without glow; maintain aspect ratio but give more vertical space to information on compact screens.
- **REFINEMENT — Favorite, discount, and stock overlays clutter image corners.** Physical `left-*`/`right-*` placement is repeated in generated markup and can collide or mirror incorrectly in RTL. Use logical placement and reserve imagery for the product.
- **REFINEMENT — Add-to-cart dominates every card regardless of browsing intent.** Full-width black buttons repeated across five-column shelves create visual bars. Keep the behavior but tune prominence by context and preserve a clear disabled/out-of-stock state.
- **REFINEMENT — Hover motion is excessive.** Cards rise 6px, image scales, favorite scales, borders change, and shadows increase. One restrained affordance—border or image change—is enough.
- **CRITICAL — Generated controls lack consistent accessible names and types.** Favorite buttons in `home.blade.php:594`, `products/index.blade.php:299`, and `categories/show.blade.php:125` are icon-only without `aria-label`/pressed state; many generated buttons omit `type="button"`.

## 11. Product details audit

- **CRITICAL — The detail page is a stack of rounded panels rather than a purchasing narrative.** `products/show.blade.php` wraps gallery, overview, description, discount, and reviews in separate 24–32px panels. The primary task—evaluate product and seller, then purchase—is fragmented by container boundaries.
- **CRITICAL — Vendor is treated as one specification cell.** The seller appears beside category, subcategory, and quantity in `.storefront-spec-grid`. Vendor trust, location, fulfillment, and store link should form a distinct commerce block using existing data/routes.
- **REFINEMENT — Discount information is over-expanded.** A nested 24px discount panel contains three more rounded cells for value/start/end. Dates can remain available without competing with price and stock.
- **REFINEMENT — Gallery media is too “showroom” for practical commerce.** The 32px frame and translucent surface are visually dominant. Thumbnails also use rounded 2xl frames and green glow selection. Use a flatter image stage, precise active state, zoom affordance, and product-context caption.
- **CRITICAL — Lightbox close placement is not RTL-safe.** JavaScript at `products/show.blade.php:376` hard-codes negative right/top. The image has an empty or generic accessible story, and focus management for the programmatically created modal is absent.
- **REFINEMENT — Reviews repeat card grammar.** Each review becomes another rounded 24px panel. A divided list is easier to scan and visually quieter.
- **POLISH — Existing review star labels are a positive accessibility detail.** Preserve their explicit per-star `aria-label` behavior while reducing hover scale.

## 12. Vendor/store audit

- **CRITICAL — Store pages do not establish seller differentiation.** `vendors/show.blade.php` uses store name, description, address, logo, and products, but the presentation is a generic blue/green gradient banner over a generic grid. There is no clear verified status, categories sold, service area, fulfillment expectation, review summary, or policy context using available data.
- **CRITICAL — `vendors/show.blade.php` is visually and technically behind the active system.** It has English-only titles/loading/errors/actions, uses physical `sm:text-left`, `top/right/left`, lacks dark-mode classes across most content, and defines a separate cart/toast implementation. This is the most obvious storefront inconsistency.
- **CRITICAL — Vendor cards are image-overlay promos, not store records.** `vendors/index.blade.php` creates banner-like cards with a bottom black gradient, store name, “by user,” and generic description. The marketplace needs recognizable store identity, category specialization, location, and trust evidence.
- **REFINEMENT — Store logos are inconsistently cropped.** `.shop-thumb-box img` uses `object-fit: cover`, which may crop logos. Product imagery and store marks need different media rules; logos generally need `contain` with a neutral field.
- **REFINEMENT — Featured vendors use purple as an arbitrary section identity.** `components/home/vendors.blade.php` uses purple pills unrelated to Vetora or a semantic state.
- **REFINEMENT — Vendor product cards diverge from product listing cards.** `vendors/show.blade.php` adds descriptions, quantity pills, gradient hover overlays, large shadows, and two equal actions. Reuse one merchandise component grammar, with store context supplied by the page.
- **POLISH — Store description and address are valuable.** Keep them, but present them as structured, trustworthy facts rather than white-on-gradient hero copy.

## 13. Admin dashboard audit

- **CRITICAL — Seven equal stat tiles create false equivalence and poor density.** `admin/dashboard.blade.php:25+` places users, vendors, syndicates, active/inactive vendors, products, and active products in an `xl:grid-cols-7` row. At realistic widths this compresses labels/numbers and makes governance signals indistinguishable from inventory totals.
- **CRITICAL — Role-critical exceptions are not visually dominant.** Pending approvals, inactive vendors/products, contact messages, or operational failures should outrank total counts. Colorful icon chips and equal cards prioritize decoration over exception management.
- **REFINEMENT — Page headers are card containers.** `.dashboard-page-header` wraps title/copy/actions in a large 28px card, adding a surface before the actual content. A flat page header with rule and compact actions is clearer.
- **REFINEMENT — Dashboard content uses many competing colors.** Blue, cyan, emerald, rose, violet, amber, and brand green icon chips create a rainbow without a stable semantic legend.
- **REFINEMENT — Admin list pages alternate between tables and card feeds.** For example, `admin/orders/index.blade.php` renders each order as a rounded hover-lifting article with nested mini-cards, while product reviews use a table. Dense administrative data should use consistent table/list patterns based on comparison needs, not screen-by-screen aesthetics.
- **CRITICAL — Several operational strings remain hard-coded English.** `admin/orders/index.blade.php` contains English filter labels, empty text, field labels, and fallbacks. This breaks Arabic parity independently of visual redesign.
- **POLISH — Existing tables and status badges are the right base.** `.admin-table` can become the dominant dense-data grammar after typography, alignment, and responsive behavior are refined.

## 14. Vendor dashboard audit

- **CRITICAL — Vendor workspace lacks a commerce-first hierarchy.** The vendor needs clear prioritization of orders requiring action, product approval/inventory problems, commission impact, and store status. The current dashboard uses the same stat-tile/card language as admin and employee screens.
- **REFINEMENT — Vendor identity is represented by emerald rather than Vetora plus merchant context.** `layouts/vendor.blade.php:52` styles the workspace label emerald and shares the generic dark/glow sidebar system.
- **REFINEMENT — Notifications, avatar, theme, home, and logout repeat rounded-square/pill controls from the public nav.** Operational chrome should be denser and calmer.
- **REFINEMENT — Order pages use card feeds with decorative hover movement.** `vendor/orders/index.blade.php` follows the same pattern as admin orders. Use structured order rows/cards optimized for status, amount, customer, and next action, with no lift.
- **CRITICAL — Vendor product creation/edit detail screens are among the highest synthetic-pattern concentrations.** Product show/create/edit views use many nested rounded panels, pills, shadows, and headings. Long forms need sections, progressive disclosure, sticky actions, and validation hierarchy—not decorative containers for each group.
- **POLISH — Keep the shared product field components.** `components/products/form-fields.blade.php`, `detail-fields.blade.php`, photo upload, and its script are valuable consolidation points for later visual normalization.

## 15. Employee/syndicate workspace audit

- **CRITICAL — Employee moderation actions are not the visual center.** `employee/dashboard.blade.php` presents status totals and four hover-lifting navigation cards before the actual category/type distribution. A moderation workspace should foreground pending queue, age, rejection reason, and next review action.
- **REFINEMENT — Employee color coding is partially semantic but too card-heavy.** Approved/pending/rejected colors are useful; wrapping every count and shortcut in `stat-tile`/`card` dilutes them.
- **CRITICAL — Syndicate sections are generic dashboard shells despite distinct domain needs.** `syndicate/dashboard.blade.php` switches among categories, vendors, products, podcasts, orders, sales, and reports but uses the same header, eight stat tiles, main card, side card, and summary card. Sales/reports require different information density from catalogs or podcasts.
- **REFINEMENT — Eight syndicate metrics are generated as visually identical tiles.** This optimizes code reuse but not prioritization. Group commercial totals, operational statuses, and catalog scope separately.
- **REFINEMENT — Sidebars differentiate roles mostly by accent color.** Admin, vendor, employee, and syndicate sidebars share layout and component grammar. Role differentiation should come from navigation taxonomy, density, and priority—not green/cyan labels.
- **POLISH — Keep role-specific routes, ready events, and API behavior.** The redesign can reorganize visual hierarchy without changing the existing workspace contracts.

## 16. RTL audit

- **CRITICAL — Physical directional utilities are widespread.** The view layer contains roughly 30 `left-*`, 42 `right-*`, 19 `ml-*`, 9 `mr-*`, 19 `pl-*`, 5 `pr-*`, 12 `text-left`, 7 `text-right`, and numerous physical border/radius utilities. Some are correctly paired with `rtl:` variants, many are not.
- **CRITICAL — JavaScript-generated markup bypasses direction discipline.** Product badges/favorites use `left-*` and `right-*` in `home.blade.php`, `products/index.blade.php`, `categories/show.blade.php`, and `vendors/show.blade.php`. Navbar generated category buttons use `text-left`. Vendor pages use `sm:text-left` and physical arrow logic.
- **CRITICAL — Carousel behavior is not RTL-aware.** `components/home/vendors.blade.php:11-12` calls `scrollBy({left:-320})` and `left:320` with fixed arrow meanings. RTL scrollLeft behavior varies by browser and must be normalized.
- **CRITICAL — Directional animation is wrong.** `slideInRight` does not mirror for the RTL mobile drawer; toast exits in `vendors/show.blade.php` with `translateX(100%)` regardless of direction.
- **REFINEMENT — Latin uppercase/tracking styles do not translate into Arabic hierarchy.** Eyebrows and dashboard labels need locale-aware styles, not simply mirrored geometry.
- **REFINEMENT — Mixed strings and numerals need isolation.** Prices, SYP, barcodes, dates, order numbers, and Arabic text should use bidi isolation/`dir="auto"` where needed so punctuation and numbers remain readable.
- **REFINEMENT — Arrow mirroring is inconsistent.** Some SVGs use `rtl:-scale-x-100` or `rtl:rotate-180`; many product/vendor/pagination arrows do not.
- **POLISH — Logical-property foundations are present.** Preserve `start/end`, `border-e`, `text-start`, document `dir`, and the existing sidebar inversion; expand them systematically.

## 17. Dark mode audit

- **CRITICAL — Dark mode is not complete on legacy vendor surfaces.** `vendors/show.blade.php` and portions of `vendors/index.blade.php` use light-only cards, text, pagination, error states, and toast markup. This creates abrupt mode breaks.
- **REFINEMENT — Dark mode is overly green/teal and atmospheric.** Site and dashboard backgrounds layer emerald glows over near-black green surfaces. It feels like a separate neon theme rather than the same calm marketplace under lower luminance.
- **REFINEMENT — Translucency reduces predictability.** Many dark panels use `white/5`, `gray-950/60`, or blurred backgrounds. Contrast depends on what sits behind them; opaque semantic surfaces are more robust.
- **CRITICAL — Several dark text/background combinations need formal testing.** Tiny `text-gray-500`/`text-white/45`/`text-white/55` labels, 10–11px metadata, and semi-transparent borders are likely insufficient at normal text size, especially over imagery or gradients.
- **REFINEMENT — Images and logos need dark-surface rules.** Transparent store marks and the Vetora wordmark may lose edge contrast. Provide a neutral logo field or alternate framing, not a new logo color.
- **POLISH — Theme initialization avoids flash for explicit dark selection.** The early layout script is directionally good. Theme controls should retain accessible names and state.

## 18. Responsive audit

- **CRITICAL — Product cards become information-poor or tall at intermediate widths.** Home uses five columns at XL and 240px minimum card bodies; listing grids move from one to two to three/four columns with different media ratios. Long Arabic names and metadata pills will produce uneven heights and excessive scrolling.
- **CRITICAL — Desktop mega-menu and dashboard stat grids are brittle.** A fixed 780px mega panel and seven-column admin metric row do not degrade gracefully under zoom, long translation, or smaller laptops.
- **REFINEMENT — Mobile filters stack into large rounded panels.** `filter-panel`, full-width inputs, and full-width actions create a long pre-results wall. Preserve all filters but provide a compact summary and explicit filter disclosure on small screens.
- **REFINEMENT — Horizontal chip and thumbnail scrollers hide affordance.** Hidden scrollbars on subcategories, category chips, gallery thumbnails, and vendor tracks make overflow undiscoverable.
- **REFINEMENT — Touch targets are inconsistent.** Many icon actions are 40–48px, which is good; 32–36px favorites, tiny pagination, pill toggles, and text-only destructive actions are less reliable.
- **REFINEMENT — Dense tables rely mostly on horizontal overflow.** `.admin-table-wrap` prevents breakage but does not prioritize columns or offer a useful mobile row grammar.
- **POLISH — The application uses responsive utility breakpoints extensively.** Keep the responsive foundations but design from content stress cases: Arabic, 200% zoom, long store names, four-digit prices, missing images, and multiple badges.

## 19. Accessibility audit

- **CRITICAL — Icon-only generated favorite controls lack names and state.** Add `aria-label` and `aria-pressed`; update both when toggled. Current global favorite logic only changes fill/classes.
- **CRITICAL — Programmatic dialogs lack full semantics/focus management.** The product lightbox created in `products/show.blade.php` has no dialog role, label, focus trap, or focus restoration. Other overlays have partial semantics but need consistent behavior.
- **CRITICAL — Non-button notification rows use `role="button"` without full keyboard equivalence.** Generated notification items in `navbar.blade.php:498+` need native links/buttons or Enter/Space handling and focusability.
- **CRITICAL — Image alternative text is inconsistent.** Category product images in `categories/show.blade.php:125` omit `alt`; decorative vs informative SVGs are not consistently hidden; vendor/store images sometimes use generated fallbacks but no stable accessible description.
- **CRITICAL — Color contrast is risky in the current palette.** White on current emerald `#10B981` is only about 2.54:1, so `.btn-primary` with white text does not meet 4.5:1 for normal text across much of its gradient. Logo cyan `#29A9D1` also cannot carry white body text.
- **REFINEMENT — Focus styles are broadly present but inconsistent.** Global `focus-visible` is good; many elements add `focus:outline-none focus:ring-*`, while programmatically generated controls and role-based clickable rows may not receive equivalent focus.
- **REFINEMENT — Uppercase 10–11px labels and low-opacity text impair readability.** This is especially problematic on gradient/image backgrounds and in dark mode.
- **REFINEMENT — Form error relationships need review.** Error text exists, but dynamic forms should consistently connect errors via `aria-describedby`, set `aria-invalid`, and move focus to the first invalid field.
- **REFINEMENT — Button type is omitted frequently.** Many buttons in layouts, generated cards, admin filters, and modal controls lack explicit `type="button"`. Outside forms this is harmless; inside or after future composition it risks accidental submission.
- **POLISH — Preserve the skip link, reduced motion, label use, and existing dialog headings.** These are good foundations for a formal WCAG 2.2 AA pass.

## 20. Motion audit

- **CRITICAL — Motion is used as a visual signature rather than task feedback.** Product/category/vendor cards lift; imagery zooms; buttons lift/scale; hero orbs float; glow pulses; skeletons shimmer; content fades upward. The combined effect is promotional and synthetic.
- **REFINEMENT — Inline staggered product animations are layout-specific.** `home.blade.php` inserts cards with inline opacity, transform, transition, and stagger delays, then observes them. This is difficult to standardize and duplicates CSS concerns in JavaScript.
- **REFINEMENT — Directional motion is not localized.** Drawer and toast animations assume LTR.
- **REFINEMENT — Keep purposeful motion only.** Retain immediate pressed/selected feedback, drawer/modal transitions, cart count confirmation, loading progress, and validation feedback. Use 120–200ms transitions for controls and 180–240ms for overlays; avoid continuous float/pulse except a true live status.
- **POLISH — Reduced-motion CSS is a strong base.** It disables animations/transitions globally and again for key storefront components. Ensure future JavaScript scrolling and inline styles also respect the preference.

## 21. Design-system fragmentation

- **CRITICAL — “Brand” and “success” are conflated at the primitive layer.** This contaminates every semantic and component decision downstream.
- **CRITICAL — Multiple generations of components coexist.** New shared classes (`surface-card`, `storefront-*`, `dashboard-*`) sit beside raw Tailwind compositions and older pages such as `vendors/show.blade.php`. The same product or order is rendered differently in each feature.
- **CRITICAL — Static Blade and JavaScript templates are separate design systems.** Class-heavy HTML strings duplicate product cards, pagination, badges, empty states, notifications, metrics, and order rows. Changes to CSS alone will not guarantee consistency.
- **REFINEMENT — Component classes contain duplicate definitions.** In `app.css`, `.list-panel`, `.stat-tile`, `.icon-chip`, `.dashboard-section-title`, and `.dashboard-section-copy` are defined more than once, with later rules altering earlier intent. This makes the cascade the real specification.
- **REFINEMENT — Tokens stop at color names.** Spacing, radius, typography, elevation, density, control height, media ratio, and motion are not expressed as a coherent semantic token set.
- **REFINEMENT — Page shells are inconsistent.** Some use `.page-shell`/`.workspace-shell`; others directly repeat `mx-auto max-w-screen-2xl px-*`; home legacy components use different vertical spacing and dark backgrounds.
- **POLISH — Tailwind v4 theme variables are the right integration point.** Build primitive → semantic → component tokens there, then expose a small number of Blade/component classes.

## 22. Components that should be deleted or simplified

Deletion here means removal or consolidation during implementation after confirming active usage; it does not authorize changing behavior now.

- **CRITICAL — Delete the emerald `brand-*` identity scale, not semantic success green.** Replace it with logo-derived primitives and explicit success tokens.
- **CRITICAL — Consolidate duplicate JavaScript product-card templates.** Remove page-specific card grammars from `home.blade.php`, `categories/show.blade.php`, `products/index.blade.php`, `vendors/show.blade.php`, and favorites after one rendering contract is established.
- **REFINEMENT — Simplify `.glass-panel`, `.surface-card`, `.surface-card-muted`, `.card`, and `.dashboard-panel` into a small surface set.** Normal content should be opaque/flat; overlays may blur; featured/editorial blocks may use a controlled image treatment.
- **REFINEMENT — Remove radial glow/blob pseudo-elements.** Target site/dashboard backgrounds, auth shell, workspace/storefront heroes, media wells, category/product card overlays, and login blobs.
- **REFINEMENT — Remove global 6px card lift and most image/button scale effects.** Keep a restrained hover state appropriate to each interactive role.
- **REFINEMENT — Simplify section intros.** Remove decorative eyebrow pills where they repeat page context; use a rule, index, count, or meaningful taxonomy label when needed.
- **REFINEMENT — Simplify trust-badge cards into a factual trust strip or policy module.** Do not display unsupported generic promises.
- **REFINEMENT — Simplify dashboard page headers and equal stat-card fields.** Use flat headers, grouped metrics, tables/queues, and exception blocks.
- **REFINEMENT — Remove generic gradient icon replacements for the logo in footer/sidebars.** Use the actual mark or a restrained wordmark treatment.
- **POLISH — Remove stale home vendor/top-rated components if route/render inspection confirms they are not used.** If used elsewhere, bring them into the shared system instead.

## 23. Components that should remain

- **CRITICAL — Product-type selection behavior and category/subcategory hierarchy.** These are core domain navigation.
- **CRITICAL — Product, store, order, review, approval, and status functionality.** Recompose visually without altering APIs, routes, forms, or business logic.
- **REFINEMENT — `page-shell`, `workspace-shell`, responsive grid intent, form primitives, button semantics, badges, alerts, and tables.** Keep as consolidation points; retoken and simplify.
- **REFINEMENT — Navbar capabilities.** Logo, catalog navigation, type context, language, theme, account, notifications, and cart remain; reorganize hierarchy and interaction semantics.
- **REFINEMENT — Loading, empty, error, disabled, validation, and dark states.** Normalize rather than remove.
- **REFINEMENT — Existing shared product form/detail/photo components.** They reduce divergence in operational screens.
- **POLISH — Agricultural hero imagery and logo leaf character.** Use sparingly and authentically.

## 24. Proposed new visual direction: Vetora Precision Agritech Commerce

**Definition:** An editorial commerce system with Swiss-inspired grid discipline, agricultural authenticity, marketplace usability, and technical precision. It uses Vetora’s blue-cyan identity sparingly against warm mineral neutrals; product and seller evidence establish trust; typography and rules create hierarchy; cards are reserved for real object boundaries; motion confirms state rather than advertising polish.

### Direction principles

- **CRITICAL — Commerce before atmosphere.** Product identity, exact commercial/pack details, seller, stock, price, and next action are visible in a consistent order.
- **CRITICAL — Seller trust is part of merchandise.** Product cards carry a compact store line; detail pages expose store identity; store pages behave like verified merchant records, not promotional banners.
- **CRITICAL — One brand blue, one action hierarchy, semantic state colors.** Blue-cyan identifies Vetora and primary interaction. Green means success/available/approved only.
- **REFINEMENT — Grid and rules replace nested cards.** Use consistent max-widths, columns, alignment lines, section dividers, table rows, and controlled whitespace.
- **REFINEMENT — Agricultural authenticity comes from real content.** Product imagery, category taxonomy, merchant locality, service facts, and exact nomenclature carry character. Avoid decorative leaves, fake textures, or pastoral clichés.
- **REFINEMENT — Editorial variation is purposeful.** Category landing, product grid, store profile, and operational dashboard each get a grammar suited to their information—not one universal card.
- **REFINEMENT — Calm density.** Storefront density is moderate and comparison-friendly; dashboards are denser, using tables and queues. Mobile reduces metadata by priority, not by shrinking everything.
- **POLISH — Precision details.** Fine rules, tabular numerals for prices/metrics, clear unit styling, consistent image ratios, explicit selected states, and small brand-blue accents create finish without effects.

### Surface grammar

- **Storefront:** warm off-white canvas, white/near-white opaque inventory surfaces, 1px mineral borders, restrained 4–12px radii, logo blue for actions/links, photographic category anchors, and consistent merchandise rows/cards.
- **Product detail:** two-column gallery/purchase composition with a continuous information hierarchy; store/trust block directly below purchase facts; description/specifications/reviews separated by rules or tabs/anchors, not floating islands.
- **Store page:** merchant masthead with contained logo, location/specialty/status facts, policy/trust block, then catalog tools and inventory.
- **Workspaces:** neutral shell, compact role navigation, flat top bar, exception-first overview, dense tables/lists, minimal elevation only for overlays.
- **Dark mode:** deep blue-charcoal neutrals, opaque surfaces, desaturated brand accents, preserved semantic states, and no green atmospheric glow.

## 25. Proposed token system

Use three layers: **primitive values → semantic roles → component tokens**. The values below are an audit proposal, not implementation code. Validate all pairs in real components and both scripts before adoption.

### Primitive color proposal

| Primitive | Value | Intended use |
|---|---:|---|
| `vetora-50` | `#EFF8FB` | selected/background tint |
| `vetora-100` | `#D8EEF5` | subtle borders/highlights |
| `vetora-200` | `#ADDCEB` | charts/decorative rule |
| `vetora-300` | `#74C6DE` | non-text accent |
| `vetora-400` | `#29A9D1` | logo cyan/accent; dark text only |
| `vetora-500` | `#288BAD` | medium accent; avoid normal white text |
| `vetora-600` | `#297497` | primary brand/action; white text passes AA |
| `vetora-700` | `#20607E` | hover/strong link |
| `vetora-800` | `#1A4D66` | pressed/dark brand |
| `vetora-900` | `#123748` | deep brand surface |
| `mineral-0` | `#FFFFFF` | raised/clean surface |
| `mineral-50` | `#F7FAF9` | page canvas |
| `mineral-100` | `#EEF3F1` | muted surface |
| `mineral-200` | `#DCE5E1` | divider/border |
| `mineral-500` | `#667085` | secondary text; verify by size/background |
| `mineral-700` | `#34424A` | strong secondary text |
| `mineral-900` | `#172126` | primary ink |
| `success-600` | `#177A55` | success/available/approved text/fill |
| `warning-600` | `#A65F09` | pending/caution |
| `danger-600` | `#B93845` | destructive/error/out of stock |
| `info-600` | `#296AA3` | non-brand informational state |

### Semantic color roles

- `color-canvas`, `color-surface`, `color-surface-muted`, `color-border`, `color-border-strong`
- `color-text`, `color-text-secondary`, `color-text-muted`, `color-text-inverse`
- `color-brand`, `color-brand-hover`, `color-brand-pressed`, `color-brand-subtle`
- `color-focus` distinct enough in both themes
- `color-success`, `color-warning`, `color-danger`, `color-info` plus subtle/background/border variants
- `color-price`, `color-discount`, `color-stock`, `color-link`, and `color-selection` as commerce-specific aliases

### Typography tokens

- Body/UI sizes: `12`, `14`, `16`, `18`; commerce/display sizes: `24`, `32`, `40`, with script-aware line heights.
- Weights: `400` body, `500` metadata/UI, `600` buttons/labels, `700` headings/key numbers. Use `800` only for a rare campaign/display need; remove routine `900`.
- Add `font-numeric: tabular-nums` for prices, quantities, order IDs, dashboard metrics, and dates.
- Define separate Latin uppercase metadata and Arabic metadata tokens; Arabic should not inherit forced uppercase or wide tracking.

### Spacing, radius, border, and elevation

- Spacing primitives: `4, 8, 12, 16, 24, 32, 48, 64`.
- Radius roles: `radius-control: 6px`, `radius-card: 8px`, `radius-feature: 12px`, `radius-pill: 999px` only for true tags/statuses, `radius-overlay: 12px`.
- Border roles: 1px default, 2px selected/focus where needed; use rules/grouping before background containers.
- Elevation roles: `none`, `overlay`, `modal`; normal cards and dashboard data should usually use `none`.

### Density and component tokens

- Controls: `control-sm: 36px`, `control-md: 44px`, `control-lg: 48px`; touch targets remain at least 44px where isolated.
- Product card: fixed media ratio per context, title line allowance, seller line, metadata row, price row, action zone, and explicit compact/mobile variants.
- Table row: compact/default heights, sticky header, selected/hover/error states.
- Navigation: public catalog height and denser workspace height are separate component tokens.
- Motion: `fast 120ms`, `standard 180ms`, `overlay 220ms`; standard ease-out, no default translate/scale.

### Dark semantic mapping

- Canvas `#0E171C`, surface `#152127`, muted surface `#1B2A31`, border `#2D4049`, primary text `#F1F5F4`, secondary text `#B7C3C0`.
- Brand accent should use `vetora-300/400` for text/rules on dark surfaces and `vetora-600/700` for filled controls only after contrast validation.
- Success remains green and is never used as the dark-mode ambient tint.

## 26. Before → After conceptual mapping

| Before | Why it fails | After |
|---|---|---|
| Emerald `brand-*` everywhere | contradicts logo; green loses semantic meaning | logo-derived Vetora blue brand scale; green reserved for success/availability |
| Floating rounded glass navbar | reads as landing-page chrome | stable commerce header with clear market context, catalog navigation, search/actions, and compact utilities |
| Badge → heading → paragraph for every section | repetitive generated cadence | section-specific headers using count, taxonomy, rule, seller context, or no intro at all |
| Every object in a 24–36px card | destroys object-type distinction | flat grids/rows and small-radius object boundaries; cards only where the object truly needs enclosure |
| Radial glows and translucent panels | generic SaaS atmosphere | opaque mineral surfaces, editorial imagery, thin rules, precise alignment |
| Identical product shelves | page length without new browsing value | one main assortment plus genuinely distinct ranking/filter modules or consolidated tabs/links |
| Metadata as many pills | visual noise; weak meaning | ordered text rows; pills only for status or true selectable filters |
| Vendor as a single spec or gradient hero | weak marketplace trust | merchant identity block with logo, location, specialization, status/policies, and catalog |
| Seven/eight equal metric cards | false equivalence and low density | grouped KPIs, exception queues, and dense comparison tables |
| Hover lift + zoom + shadow | demonstrative/artificial | restrained border/color affordance and action feedback |
| Tiny uppercase black labels | weak Arabic parity and readability | script-aware metadata styles at usable sizes/weights |
| Physical left/right in JS templates | RTL bugs | logical start/end placement and direction-aware motion |
| Page-specific JS card HTML | fragmented system | one shared render contract/component markup with controlled variants |
| Green-glow dark mode | separate neon identity | blue-charcoal dark mapping with opaque surfaces and semantic state colors |

## Implementation sequence

This sequence preserves backend behavior and reduces risk. Each phase should be verified in Arabic/English, RTL/LTR, light/dark, keyboard-only, reduced-motion, and mobile/desktop before proceeding.

1. **Inventory and visual regression baseline — CRITICAL.** Confirm which Blade components/routes are active; capture key states for home, type/category selection, listings, product detail, stores, auth, and every workspace. Include generated/loading/empty/error/modal states. Do not delete suspected stale components until confirmed.
2. **Semantic token correction — CRITICAL.** Introduce logo-derived primitives, semantic brand/success roles, accessible text/background pairs, neutral light/dark surfaces, script-aware typography, radius, spacing, elevation, control, and motion tokens. Keep compatibility aliases temporarily so behavior is untouched.
3. **Accessibility and RTL foundation — CRITICAL.** Replace physical direction assumptions with logical rules, fix direction-aware motion/scroll, normalize focus and dialog behavior, add accessible names/pressed states to generated controls, connect form errors, and establish contrast tests.
4. **Shared rendering contracts — CRITICAL.** Define one storefront product-card information order and variants; one store identity pattern; one status/badge system; one pagination pattern; one overlay pattern. Consolidate JavaScript-generated markup so static and dynamic UI use the same contracts.
5. **Public navigation and shell — CRITICAL.** Replace floating glass grammar with stable marketplace chrome while retaining every existing capability, route, locale, theme, notification, account, and cart behavior.
6. **Homepage commerce hierarchy — CRITICAL.** Simplify hero and product-type choice; strengthen category browsing; consolidate repetitive shelves; introduce factual trust/store context; keep existing data and selection flows.
7. **Catalog and product merchandising — CRITICAL.** Normalize category/subcategory presentation, filters, cards, product detail purchase hierarchy, stock/price/unit metadata, reviews, and mobile density.
8. **Vendor/store experience — CRITICAL.** Rebuild vendor index/detail presentation around merchant identity and trust; bring all strings, RTL, dark mode, cart behavior, cards, and pagination into the shared system.
9. **Workspace shell normalization — REFINEMENT.** Simplify admin/vendor/employee/syndicate layouts, sidebars, top bars, page headers, surfaces, and responsive behavior without changing role access or routes.
10. **Role-specific operational hierarchy — CRITICAL.** Recompose admin around governance/exceptions, vendor around orders/inventory/commission, employee around moderation queue, and syndicate around section-specific commercial/catalog tasks. Replace decorative stat-card grids with grouped metrics and dense lists/tables.
11. **Auth and long-form cleanup — REFINEMENT.** Remove login blobs/glass benefit cards, simplify registration/product forms, create clearer sections and sticky actions, and verify validation in both scripts.
12. **Motion and finish — POLISH.** Remove continuous/decorative motion, standardize purposeful transitions, refine imagery/empty states, and validate reduced motion.
13. **Cross-surface QA and controlled deletion — CRITICAL.** Test routes/APIs/behavior unchanged; run visual, accessibility, RTL, dark, responsive, zoom, and content stress checks; only then remove compatibility classes, duplicate CSS definitions, stale markup, and unused components.

---

**Audit stop point:** This document defines findings and direction only. No application design or production code has been modified.
