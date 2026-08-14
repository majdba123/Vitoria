// theme.ts — single source of truth for this build tool.
// The footage stays natural; these tokens exist only for the optional,
// near-invisible harmonization pass (grade/grain/vignette), not for any
// motion-graphics layer drawn over the video.
export const theme = {
  colors: {
    brand600: "#297497",
    brand500: "#288BAD",
    brand400: "#29A9D1",
    ink900: "#172126",
    neutral50: "#F7FAF9",
  },
  grade: {
    // Soft-light tint at very low opacity — harmonize, never recolor.
    tintOpacity: 0.05,
  },
  grain: {
    // Spec target: 0.015-0.03, invisible at normal viewing distance.
    opacity: 0.02,
  },
  vignette: {
    // Extremely restrained; omit entirely if the page composition
    // already provides enough contrast for overlaid HTML.
    enabled: false,
  },
} as const;
