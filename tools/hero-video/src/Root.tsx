import { Composition } from "remotion";
import { VetoraHeroPost } from "./VetoraHeroPost";

// Source footage: 8.000s @ 24fps, 1280x720 (verified via ffprobe).
const FPS = 24;
const DURATION_IN_FRAMES = 192;

export const Root: React.FC = () => {
  return (
    <Composition
      id="VetoraHero"
      component={VetoraHeroPost}
      durationInFrames={DURATION_IN_FRAMES}
      fps={FPS}
      width={1280}
      height={720}
    />
  );
};
