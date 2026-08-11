# Vetora Design System

**Status:** Foundation implemented (tokens + shared primitives). Individual page redesigns are a separate, later step.
**Source of truth for brand color:** `public/images/vetora-logo-transparent.png`
**Implementation:** `resources/css/app.css`

This document describes the token system and shared component primitives introduced to correct the brand/color mismatch and reduce decorative-effect overuse identified in the Vetora UI audit, without changing any backend behavior, routes, or JavaScript contracts.

---

## 1. Art direction

**Vetora Precision Agritech Commerce** — an editorial commerce system with disciplined grid, agricultural authenticity, and marketplace usability. Brand blue is used sparingly for identity and action; warm mineral neutrals carry most of the surface; product/seller evidence (not decorative effects) establishes trust.

Retired as *default* design language (may still exist as deliberate, isolated exceptions — e.g. the storefront hero photo overlay):

- Glassmorphism / `backdrop-filter: blur()` on persistent surfaces (nav, sidebars, cards, dashboard panels). Blur is now reserved for true transient overlays only (mobile dialogs).
- Decorative radial-gradient "glow" and blob pseudo-elements (`::before`/`::after` glows on hero/card/metric surfaces).
- Large arbitrary border radii (24–36px) used indiscriminately.
- Heavy multi-layer drop shadows as the default card treatment.
- Global 6px hover-lift + image/button scale-on-hover on every card.
- `font-black`/`font-extrabold` as the default weight for routine labels and section titles.
- Emerald (`brand-*`) as the brand identity color — emerald is now reserved for the `success` semantic only.

## 2. Token architecture

Three layers, in order of authority: **primitive → semantic → component**. Component code should reach for a semantic or component token before a primitive value directly.

### 2.1 Primitives (`@theme` in `app.css`)

Raw values only, no assigned meaning.

| Scale | Values | Notes |
|---|---|---|
| `--color-brand-50…900` | `#eff8fb` → `#123748` | Logo-derived teal/blue. Anchors: `#297497` (600, primary action), `#288bad` (500), `#29a9d1` (400, accent-only — insufficient contrast for white body text). |
| `--color-ink-50…900` | `#f7fafa` → `#172126` | Neutral scale (kept the existing `ink` name — it's referenced as `text-ink-*`/`bg-ink-*` across ~100 Blade views; only the values shifted toward a calmer mineral-neutral). |
| `--color-success-500` | `#177a55` | Distinct from brand. Also expressed via Tailwind's built-in `emerald-*` in `.badge-success`. |
| `--color-warning-500` | `#a65f09` | |
| `--color-danger-500` | `#b93845` | |
| `--color-info-500` | `#296aa3` | |

### 2.2 Semantic tokens (plain CSS custom properties, `:root` / `.dark`)

Named by role. This is where light/dark mode branches.

```
--color-background, --color-surface, --color-surface-muted
--color-border, --color-border-strong
--color-text, --color-text-secondary, --color-text-muted, --color-text-inverse
--color-brand, --color-brand-strong, --color-brand-soft, --color-brand-on
--color-focus-ring
--shadow-1, --shadow-2, --shadow-3          (max 3 elevation tiers)
--radius-control, --radius-card, --radius-feature, --radius-pill
```

Radius scale (strict — do not introduce new arbitrary values in new work):

| Token | Value | Use |
|---|---|---|
| `--radius-control` | 8px | buttons, inputs, small chips |
| `--radius-card` | 12px | standard cards, panels, tables-in-cards |
| `--radius-feature` | 18px | large editorial surfaces (hero, gallery, auth shell) — the maximum radius in the system |
| `--radius-pill` | 999px | true pills only: badges, status dots |

Elevation scale (prefer border-only where possible; at most 3 tiers):

| Token | Use |
|---|---|
| `--shadow-1` | default card resting state (very subtle) |
| `--shadow-2` | raised content (e.g. auth shell) |
| `--shadow-3` | modal/dropdown/mobile-dialog overlays only |

### 2.3 Component tokens (`:root, .dark` block, narrow scope)

```
--button-primary-bg, --button-primary-bg-hover, --button-primary-text
--input-border, --input-border-focus, --input-bg
--nav-bg, --nav-border
--product-card-border, --product-card-border-selected
```

Change these — not the component rule itself — when a single control needs to diverge from the semantic default.

## 3. Dark mode mapping

Dark mode moved from a green-tinted near-black atmosphere to a blue-charcoal neutral, per the audit's recommendation to stop using the brand hue as ambient dark-mode tint:

- Canvas `#0e171c`, surface `#152127`, muted surface `#1b2a31`, border `#2d4049`.
- Brand accent uses the lighter end of the scale (`--color-brand-400`) for text/rules on dark surfaces, since `--color-brand-600` (the light-mode primary) does not have sufficient contrast reversed.
- `success`/`warning`/`danger`/`info` badges keep their existing Tailwind-native dark variants (`dark:bg-emerald-500/10` etc.) — untouched.

## 4. Typography

- Unchanged font stack for this step: `Manrope` (sans/UI) + `Sora` (display) + `IBM Plex Sans Arabic` fallback for both. A full pairing re-evaluation (per the audit's typography section) needs bilingual specimens with real product names/units and is deferred to the page-redesign phase.
- Weight discipline introduced in the shared primitives: `.dashboard-section-title` moved from `font-black` (900) to `font-bold` (700); `.eyebrow`/`.badge` moved from `font-extrabold` (800) to `font-semibold` (600). `.section-title` keeps `font-bold` (700) as a true heading.
- `.admin-table td` now sets `font-variant-numeric: tabular-nums` for numeric columns.
- Body base font size is explicitly `16px` (`body { font-size: 16px }`).

## 5. What changed vs. what didn't

**Changed (tokens + shared primitives only):**
`.card`, `.surface-card`, `.surface-card-muted`, `.btn-*`, `.form-input`/`.form-select`/`.form-textarea`, `.badge-brand`, `.admin-table`, `.dropdown-panel`/`.modal-shell`, `.mobile-dialog`/`.mobile-dialog-card`, `.empty-state`/`.state-panel`/`.dashboard-empty`, `.skeleton` (loading state, kept), focus-visible ring, `.dashboard-sidebar`/`.dashboard-topbar`, `.stat-tile`/`.metric-card`, `.storefront-hero`/`.storefront-*-card`, `.list-panel`, `.icon-chip`, `.nav-shell` and nav primitives, `.workspace-hero`, `.auth-shell`.

**Deliberately NOT changed in this step:**
- No Blade template markup, IDs, or `data-*` attributes.
- No JavaScript.
- No routes, controllers, models, or any backend behavior.
- No individual page composition/information-architecture changes (home hero copy, product card field order, vendor page structure, dashboard metric grouping, etc.) — those are the next phase per the audit's implementation sequence, sections 6–11.
- `badge-success`/`badge-danger`/`badge-warning`/`badge-info`/`badge-purple` keep their existing Tailwind-native utility classes (still correctly semantic, not brand-colored).
- Direct Tailwind utility usage inside individual Blade files (e.g. a page using `font-black` or `rounded-3xl` directly rather than through a shared component class) is untouched — normalizing those is page-level work.

## 6. Verification performed

- `npm run build` — clean, no errors/warnings.
- `php artisan test --compact` — 145 passed, 0 failed (unchanged from before this change; confirms no backend/behavioral regression).
- Manually verified in-browser (light/dark, Arabic/RTL): home hero, product-type selector cards, admin dashboard (sidebar, stat tiles, KPI colors).

## 7. Next steps (not part of this change)

Per the audit's implementation sequence: accessibility/RTL foundation fixes (logical-property physical-direction bugs, dialog focus management, generated-control accessible names), shared JS render contracts (one product-card template instead of 5+ divergent ones), then page-by-page recomposition (home, catalog, product detail, vendor/store pages, workspace dashboards) using the tokens defined here.
