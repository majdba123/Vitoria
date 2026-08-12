# Vetora Design System — "Vetora Precision Agritech Commerce"

Foundation layer only. This document describes the token system and shared primitives
implemented in this pass. It does not redesign individual pages — see
[VETORA_UI_AUDIT.md](VETORA_UI_AUDIT.md) for the full page-by-page findings and the
implementation sequence this work is step 1 of (audit §"Implementation sequence", steps 1–2
and part of 5/7).

Stack is unchanged: Laravel Blade + Tailwind CSS v4 + Vite + vanilla JS/Axios. No frontend
architecture migration. No backend/route/API/auth change.

---

## 1. Brand

Source of truth: `public/images/vetora-logo-transparent.png` — a teal-to-sky-blue wordmark.
Anchors `#297497` / `#288BAD` / `#29A9D1` map directly to `--color-brand-600/500/400`.

**Decision: the primitive scale keeps its existing name, `--color-brand-*`, rather than the
spec's illustrative `--vetora-blue-*`.** This scale was already correctly derived from the
logo before this pass (it was not the problem the audit identified — adoption was). Renaming
it would mean touching every one of the ~150 Blade files that already consume it correctly via
Tailwind's auto-generated `bg-brand-500`/`text-brand-600`/etc. utilities, which is exactly the
kind of individual-page churn this foundation step is scoped to avoid. The three anchor hexes,
the 50–900 range, and the intent are unchanged from the spec's example — only the variable name
differs, and it's documented here so the mapping is explicit.

Green is not a second brand color. Every decorative (non-status) use of `emerald-*` found in
shared primitives during this pass was re-pointed at brand or removed — see §4. Page-level
decorative emerald (vendor topbar accents, category color-coding, etc.) is cataloged in the
audit and is out of scope for this step; it is a page-redesign task, not a primitive one.

---

## 2. Token architecture — three layers

### Primitive (raw values, no meaning)

```
--color-brand-50 … --color-brand-900        (existing, unchanged)
--color-ink-50 … --color-ink-900             (existing, unchanged — the neutral scale)
--color-success-50/100/200/300/500/600/700/800   (expanded this pass)
--color-warning-50/100/200/300/500/600/700/800   (expanded this pass)
--color-danger-50/100/200/300/500/600/700/800    (expanded this pass)
--color-info-50/100/200/300/500/600/700/800      (expanded this pass)
```

Status primitives were previously a single `-500` value each. They're expanded to an 8-step
scale (50/100/200/300/500/600/700/800 — skipping 400/900, which nothing currently needs) so
badges, alerts, and success/danger UI states can be built entirely from tokens instead of
Tailwind's stock `emerald-*`/`rose-*`/`amber-*`/`blue-*` palettes. The anchors (`#177a55`,
`#a65f09`, `#b93845`, `#296aa3`) are unchanged from before this pass — this is an expansion of
an existing decision, not a new one. They're deliberately more muted/earthy than Tailwind's
defaults (compare `--color-success-500: #177a55` to Tailwind's `emerald-500: #10b981`) — a
brighter candy-green success state next to the muted brand teal would itself read as generic
SaaS; the whole palette, brand and status alike, stays in one restrained register.

### Semantic (named by role)

```
--color-brand / --color-brand-strong / --color-brand-soft / --color-brand-on   (existing)
--color-{success,warning,danger,info}-soft/strong/on                          (new this pass)
--color-background / --color-surface / --color-surface-muted                  (existing)
--color-border / --color-border-strong                                        (existing)
--color-text / --color-text-secondary / --color-text-muted / --color-text-inverse (existing)
--color-focus (new alias of --color-brand) / --color-focus-ring (existing)
```

`soft` = tinted background for a status chip/alert. `strong` = readable text color on that tint.
`on` = text color for a solid `-500` fill (an icon chip, a solid button). This mirrors the
existing brand triad exactly, extended to the four status colors so nothing downstream needs to
pick a raw shade and guess whether it's readable.

### Component (bound to one control's role)

```
--button-primary-bg / --button-primary-bg-hover / --button-primary-text   (existing)
--input-border / --input-border-focus / --input-bg                        (existing)
--nav-bg / --nav-border                                                    (existing)
--product-card-border / --product-card-border-selected                    (existing)
```

No new component tokens were needed — the existing set already covers buttons, inputs, and nav.
Badges and alerts consume the semantic layer directly (`.badge-success { background:
var(--color-success-soft); color: var(--color-success-strong); }`) rather than getting their own
component tokens, because a badge's color is entirely a function of its status, with no
control-specific override ever required — adding a token layer there would be indirection with
no payoff.

**Rule going forward: no new raw Tailwind color utility (`emerald-*`, `rose-*`, `gray-*` for
anything but pure neutral chrome, etc.) in a shared component or a new page.** If a needed shade
doesn't exist yet, add it to the primitive scale — don't reach past the token system.

---

## 3. Radius scale

Unchanged — it already matched the brief before this pass, which is itself a finding worth
recording: the token layer was ahead of the markup.

```
--radius-control: 8px   → small controls, buttons, inputs
--radius-card: 12px     → standard cards
--radius-feature: 18px  → large editorial surfaces (hero, auth panel, feature modal)
--radius-pill: 999px    → true pills (avatar circles, small status badges)
```

Nothing above 18px is a token. `rounded-3xl` (24px) and arbitrary `rounded-[22–36px]` values
found throughout the audit are legacy markup, not part of this system — see §6 for how they're
handled during the transition.

## 4. Shadow scale

Unchanged, already compliant with "border over shadow, max 3 tiers":

```
--shadow-1   hairline, near-flat — default for cards
--shadow-2   subtle lift — dropdowns, popovers
--shadow-3   overlay — modals, slide-over drawers
```

Colored/glow shadows (`shadow-emerald-500/30`, `shadow-brand-500/20`, `shadow-slate-100/50`)
are not part of this scale and were removed from every shared primitive touched this pass (cart
checkout button, cart order-success icon chip). They remain a known page-level cleanup item per
the audit.

---

## 5. Typography

Kept the existing pairing: **Manrope** for Latin, **IBM Plex Sans Arabic** for Arabic — already
loaded via `fonts.bunny.net` in `layouts/app.blade.php` with a sensible weight subset
(400/500/600/700/800 Manrope, 400/500/600/700 Arabic). This already satisfies "maximum 2 font
families" and "Arabic and English should feel like the same brand" — Sora was evaluated per the
brief and not adopted, since introducing a third typographic voice into an app that already has
an adoption problem (one designed system, inconsistently used) would add another thing to
govern rather than fewer.

`--font-display` remains a distinct token from `--font-sans` even though both currently resolve
to the same stack. It is referenced by Tailwind's auto-generated `font-display` utility class,
which is already used in 6+ Blade files (auth pages, dashboard sidebars, the homepage hero).
Removing the token would silently no-op those classes for no visual gain — it's kept as a
reserved slot for a genuinely distinct display face later, not a live inconsistency.

**Weight discipline** (enforced in every primitive touched this pass, and the rule for
everything after): 400/500/600/700 for all normal UI text; 800 only for rare hero/display
moments (the homepage hero already does this correctly); avoid `font-black` (900) — it was found
inconsistently applied to product prices across pages (`font-bold` in the card grammar vs.
`font-black` on the PDP) and is flagged as a page-level fix in the audit, not touched here since
no shared primitive sets `font-black`.

**Tabular numerals** — already applied correctly at the primitive level (`.commerce-product-price`,
`.admin-table td`, `.dashboard-body table`, `.product-card-price` all set
`font-variant-numeric: tabular-nums`). No change needed; this was already right.

Body text is 16px (`body { font-size: 16px; }`) — already compliant, unchanged.

---

## 6. The `.dashboard-body` override block — what it is and isn't

`app.css`'s `.dashboard-body` scope neutralizes leftover AI-dashboard utility classes
(oversized radius, heavy/colored shadow, backdrop-blur, gradient fills, hover-lift/scale)
wherever they still appear in admin/vendor/employee/syndicate markup. It predates this pass. Two
changes were made to it here:

1. **Coverage was widened.** The previous selector list only caught `bg-gradient-to-r` (missing
   `-to-br/-bl/-tr/-tl`), a fixed set of `rounded-[24/28/30/32px]` brackets (missing `rounded-2xl`
   and other bracket values), and `shadow-xl`/`shadow-2xl` (missing `shadow-lg`, colored shadow
   variants, and `hover:shadow-*`). The audit found concrete instances slipping through every one
   of those gaps. The list now catches every gradient direction, every `rounded-2xl`+ value,
   every mid-to-heavy shadow tier including hover and colored variants, and
   `group-hover:scale`/`group-hover:-translate` in addition to the plain `hover:`/`active:` forms.
2. **Hardcoded values were replaced with tokens.** The block previously hardcoded
   `border-radius: 0.375rem` and `background-color: #10242d` instead of referencing
   `var(--radius-control)` and `var(--dashboard-shell)`, which are defined a few hundred lines
   earlier in the same file. Fixed.

**This block is explicitly a migration aid, not a permanent architectural feature** — the
comment in the CSS says so. It exists so operator screens read as dense and neutral today, while
the underlying page markup (vendor profile, several admin show pages, per the audit) still
needs to be rewritten against the primitives directly. Widening its net is a stopgap, not a
substitute for that rewrite, which is a page-level task in a later phase, not this one.

---

## 7. Motion

Global budget: transitions only, 150–250ms, communicating feedback/state/relationship —
never ambient. This was already the intent in the existing CSS (`prefers-reduced-motion`
handling, the "border-color only" card hover comment) and is unchanged; no new animation was
added anywhere in this pass. Two motion removals:

- Cart checkout button lost `hover:shadow-xl active:scale-[.98]` (glow + press-scale) when it
  was switched to `.btn-primary`, which transitions `background-color`/`border-color`/`color`
  only.
- Cart order-success icon chip lost `shadow-lg shadow-emerald-500/30` (a static glow, not
  motion, but the same "decoration standing in for feedback" instinct — removed for the same
  reason).

Page-level motion problems identified in the audit (the homepage's JS scroll-reveal bypassing
`prefers-reduced-motion`, various `hover:-translate-y`/`group-hover:scale` on photo cards) are
markup-level, not primitive-level, and are unchanged by this pass — they're on the
implementation sequence for the page-redesign phase.

---

## 8. Accessibility

Two changes land in this pass as a direct consequence of the token work:

- `.btn-primary`/`.btn-secondary`/`.btn-danger`/`.btn-ghost` now set `min-h-11` (44px), closing
  the touch-target gap the audit found on every button in the app (previously ~41px).
- `--color-focus` was added as an explicit semantic alias of `--color-brand`, so anything that
  needs "the focus color" as a plain color (not the full `--color-focus-ring` box-shadow value)
  has a token to reach for instead of hardcoding brand-600.

Everything else the audit flagged under accessibility (missing `aria-label`s, unassociated form
errors, keyboard-inaccessible galleries, missing skip links on dashboard shells) is page/markup
-level and is unchanged by this pass — tracked in the audit's implementation sequence, not a
token or shared-primitive concern.

---

## 9. What changed, primitive by primitive

| Primitive | Before | After |
|---|---|---|
| Badges (`.badge-success/warning/danger/info`) | Raw Tailwind `emerald/amber/rose/blue` utility strings via `@apply` | Consume `--color-{status}-soft/strong` tokens directly; ring replaced with `color-mix()`-based inset box-shadow |
| `.badge-purple` | Raw `violet-*` | Routed to `--color-info-*` (not a real status; kept for existing Blade references, no longer inventing an untokenized hue) |
| `.badge-cyan` | **Did not exist** — `admin/users/index.blade.php:108` referenced an undefined class | Added, routed to `--color-info-*` |
| `components/alert.blade.php` | Inline `match()` returning raw Tailwind color strings per type | Returns one of 4 new `.alert-success/error/warning/info` classes, each token-driven with dark-mode variants |
| `components/form/input.blade.php` | `text-red-500` for the required-field asterisk | `text-danger-500` (already a valid Tailwind utility via the expanded `@theme` scale) |
| `.workspace-hero` | `linear-gradient(135deg, #123748, #297497)` — raw hex, ungoverned by any token | Flat `var(--color-brand-strong)` fill |
| `.btn-primary/secondary/danger/ghost` | `min-h-` unset (~41px actual height) | `min-h-11` (44px) |
| `components/navbar.blade.php` avatar circles (×3) | `bg-gradient-to-br from-brand-400 to-brand-600` | Flat `bg-brand-600` |
| `components/navbar.blade.php` mobile drawer | Ad hoc `shadow-2xl` + scrim `backdrop-blur-sm`, outside any token, unmuted by `.dashboard-body` since this is a public page | `.mobile-drawer-shell` (shadow-3 + RTL-aware slide animation, already defined) reused; scrim blur dropped, opacity bumped slightly to compensate |
| `components/navbar.blade.php` mega-menu active state | Inline JS `el.style.boxShadow = 'inset 3px 0 0 #10b981'` — a literal hex leak into behavior code | `.mega-cat-btn.is-active { box-shadow: inset 3px 0 0 var(--color-brand); }` in CSS, RTL-mirrored; JS now only toggles the class |
| `components/navbar.blade.php` drawer/mega-menu radii | Mixed `rounded-2xl` (16px, no matching token) | `rounded-xl` (12px, matches `--radius-card`) |
| `components/home/cart-modal.blade.php` panel | Hardcoded `right-0` + inline `animation:slideInRight`, **no RTL handling at all** | `.mobile-drawer-shell` reused — same fix as the nav drawer, closes the audit's CRITICAL RTL finding for the app's highest-frequency overlay |
| `components/home/cart-modal.blade.php` success panel | `emerald-*` throughout, including a colored glow shadow | `success-*` tokens; glow shadow removed |
| `components/home/cart-modal.blade.php` coupon input / payment note | Raw `rounded-xl border-gray-200 bg-white ...` | `.form-input` / `.surface-card-muted` (existing primitives, just not previously used here) |
| `components/home/cart-modal.blade.php` checkout button | Hand-rolled `bg-brand-500 ... shadow-lg shadow-brand-500/20 ... active:scale-[.98]` | `.btn-primary` |
| `components/csv-import.blade.php` modal panel | `rounded-xl bg-white p-6 shadow-2xl`, **no dark-mode classes anywhere in the file** | `.modal-shell` (adds dark-aware background/border/shadow for free) + `dark:` text variants added throughout |
| `components/csv-import.blade.php` result colors | `emerald-600`/`rose-600`/`rose-700` | `success-600`/`danger-600`/`danger-700` (+ dark variants) |
| `.dashboard-body` override selector list | Caught only `-to-r` gradients, 4 explicit `rounded-[Npx]` brackets, `shadow-xl/2xl` | Catches every gradient direction, `rounded-2xl`+, every shadow tier including hover/colored variants, `group-hover:scale/-translate` |
| `.dashboard-body` override values | Hardcoded `0.375rem`, `#10242d` | `var(--radius-control)`, `var(--dashboard-shell)` |

---

## 10. Explicitly not done in this pass

Per the brief, this step is the foundation only. The following are cataloged in
[VETORA_UI_AUDIT.md](VETORA_UI_AUDIT.md) and intentionally untouched here:

- Any individual page rebuild (vendor profile, admin show pages, homepage sections, product
  card unification, admin resource-list grammar unification).
- The four dead `components/home/*` files (trust-badges, vendors, top-rated-products,
  promo-banner) — deletion is a page-content decision, not a primitive one.
- Page-level decorative `emerald-*` usage (vendor topbar, category color-coding, register page).
- The four near-duplicate dashboard layout files — collapsing them into one shared shell is
  structural, not a token/primitive change, and is the audit's step-2 recommendation for a
  dedicated pass.
- Ad hoc modals in `admin/coupons/index.blade.php` and `admin/contact-messages/index.blade.php`
  that don't use `.modal-shell` — page-specific instances, not shared components.
- Form `aria-describedby`/`aria-invalid` wiring, focus management on failed submits, missing
  skip links on dashboard shells, custom 404/419/429/500 templates.

---

## 11. Verification

`npm run build` and `php artisan test --compact` were run after this pass; results are reported
in the accompanying summary, not duplicated here since this document describes the system, not
a single run's CI output.
