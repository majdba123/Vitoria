# Vetora Hero — Motion & Post-Production Notes

## Source footage

- Origin: Google Flow (approved cinematic concept — not regenerated or altered narratively).
- Preserved unmodified as the master at `tools/hero-video/public/vetora-flow-source.mp4`.
- Story: agricultural field (dew-lit corn rows) → natural leaf-swipe transition → veterinarian examining a calf indoors → natural leaf-swipe transition back → field.

### Original metadata (ffprobe)

| Property | Value |
|---|---|
| Duration | 8.000s |
| Resolution | 1280×720 |
| Frame rate | 24 fps |
| Video codec | H.264 (yuv420p) |
| Video bitrate | ~1.92 Mbps |
| Audio | AAC, 128 kbps (present in source) |
| File size | ~2.02 MB |

## Visual inspection findings

Frames sampled at the opening, ~20%, both transition boundaries, mid veterinary scene,
and the final frame. No malformed anatomy, no unexpected text/logo/UI, no morphing
artifacts. First frame and final frame are near pixel-identical (static field shot),
so **the loop boundary was left untouched** — it was already seamless.

## Post-production (Remotion, build-tool only)

Location: `tools/hero-video/` — a standalone Remotion project, isolated from the
website's runtime bundle (`npm install` / `render` only; nothing from this tool ships
to the browser). Composition: `VetoraHero` (192 frames @ 24fps, 1280×720), source
loaded via `<OffthreadVideo src={staticFile(...)} muted>`.

Applied, in order of the stack:
1. Footage, unmodified — no synthetic camera movement, no Ken Burns (Google Flow
   already authored the cinematography).
2. Soft-light Vetora-teal tint at **5% opacity** — harmonizes without recoloring;
   confirmed invisible at normal viewing distance in side-by-side frame comparison.
3. Procedural film grain at **2% opacity**, overlay blend — likewise imperceptible.
4. Vignette — evaluated and **omitted**; the page's own scrim already provides the
   contrast the overlaid HTML needs, so a second darkening layer was redundant.

No text, logo, captions, or graphics were drawn into the footage at any point.

## Optimization decisions

| Asset | Encoding | Result | Notes |
|---|---|---|---|
| `public/videos/vetora-hero-loop.mp4` | H.264, CRF 23, `-movflags +faststart`, no audio | 1.70 MB, 8.000s, 1280×720 | Chosen over CRF 21 (2.3 MB) — visually indistinguishable, smaller than the original source. |
| WebM (VP9) | *dropped* | — | Encoded successfully and passed `ffprobe`, but threw a reproducible `PIPELINE_ERROR_DECODE` in browser testing, silently stalling playback. Reliability of the hero's motion outweighed the ~23% size saving, so only the universally-supported MP4 ships. |
| `public/images/vetora-hero-poster.webp` | WebP, quality 82 | 62 KB, 1280×720 | Frame at ~1s — calm establishing field shot, no transition blur, no motion artifacts. |

Audio: the source's AAC track is not present in any shipped asset — verified with
`ffprobe` (no audio stream on the final MP4).

## Reduced-motion behavior

`resources/js/hooks/use-reduced-motion.js` watches
`(prefers-reduced-motion: reduce)`. When set, `HeroMedia` in
`resources/js/Pages/Home.jsx` renders the poster as a plain `<img>` instead of
mounting `<video>` — no autoplay, same layout, same information hierarchy.

Autoplay reliability: `HeroMedia` pairs the native `autoPlay` attribute with an
explicit `play()` call (on mount, `loadeddata`, `canplay`, and `visibilitychange`),
since some browser/embedding contexts leave a muted autoplaying video briefly
`paused` after a hard reload before continuing on their own.

## Responsive crop strategy

One `<video>`/`<img>` element (`.storefront-hero-video`) fills the entire hero
section (`position: absolute; inset: 0; object-fit: cover`), with copy overlaid on
top rather than sitting beside it in a separate column:

- **Mobile** (`<1024px`): bottom-weighted scrim, copy anchored to the base of the
  frame, hero min-height 34–36rem.
- **Desktop** (`≥1024px`): side-weighted scrim (start side, RTL-mirrored via
  `html[dir='rtl']`), copy vertically centered on the start side, hero min-height 42rem.
- `object-position: center 42%` keeps both the field's establishing shot and the
  veterinarian/calf legible across the crop range actually used in production.

Hero copy uses fixed light colors (not the site's theme tokens) since it always sits
over the photographic video regardless of light/dark mode — verified in both themes.
