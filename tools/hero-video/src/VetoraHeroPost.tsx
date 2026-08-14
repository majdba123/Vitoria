import { AbsoluteFill, OffthreadVideo, staticFile, useCurrentFrame } from "remotion";
import { theme } from "./theme";

// Deterministic post-production pass over the already-approved Google Flow
// footage. No synthetic camera movement, no graphics, no text — Google Flow
// already authored the cinematography. This component only harmonizes the
// existing pixels: an optional soft-light Vetora tint, optional procedural
// grain, everything else is a pass-through of the source clip.
const Grade: React.FC = () => (
  <AbsoluteFill style={{ pointerEvents: "none" }}>
    <AbsoluteFill
      style={{
        backgroundColor: theme.colors.brand500,
        mixBlendMode: "soft-light",
        opacity: theme.grade.tintOpacity,
      }}
    />
  </AbsoluteFill>
);

const Grain: React.FC = () => {
  const frame = useCurrentFrame();
  const noise =
    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='220'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='220' height='220' filter='url(%23n)' opacity='0.5'/%3E%3C/svg%3E\")";
  return (
    <AbsoluteFill
      style={{
        pointerEvents: "none",
        backgroundImage: noise,
        backgroundSize: "220px",
        backgroundPosition: `${(frame * 7) % 220}px ${(frame * 13) % 220}px`,
        opacity: theme.grain.opacity,
        mixBlendMode: "overlay",
      }}
    />
  );
};

export const VetoraHeroPost: React.FC = () => {
  return (
    <AbsoluteFill style={{ backgroundColor: theme.colors.ink900 }}>
      <OffthreadVideo
        src={staticFile("vetora-flow-source.mp4")}
        muted
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />
      <Grade />
      <Grain />
      {theme.vignette.enabled && (
        <AbsoluteFill
          style={{
            pointerEvents: "none",
            background: "radial-gradient(ellipse at center, transparent 62%, rgba(0,0,0,0.14) 100%)",
          }}
        />
      )}
    </AbsoluteFill>
  );
};
