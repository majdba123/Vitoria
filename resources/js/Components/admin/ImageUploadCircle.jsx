import { useRef } from 'react';
import { Camera } from 'lucide-react';

/**
 * Hover-to-change circular image picker used for vendor avatar/store logo.
 * Renders an initial-letter fallback when there's no image yet.
 */
export function ImageUploadCircle({ label, previewUrl, fallback, onChange, error }) {
    const inputRef = useRef(null);

    return (
        <div className="flex flex-col items-center gap-3">
            <div className="group relative">
                <div className="flex size-28 items-center justify-center overflow-hidden rounded-full bg-accent text-3xl font-bold text-accent-foreground ring-4 ring-background">
                    {previewUrl ? <img src={previewUrl} alt="" className="size-full object-cover" /> : fallback}
                </div>
                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    aria-label={`Change ${label}`}
                    className="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity duration-200 group-hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/60"
                >
                    <div className="text-center text-white">
                        <Camera className="mx-auto size-6" />
                        <span className="mt-1 block text-[10px] font-medium text-white/80">Change</span>
                    </div>
                </button>
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    className="hidden"
                    onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                />
            </div>
            <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{label}</span>
            {error && <p className="text-xs font-medium text-[var(--color-danger-strong)]">{error}</p>}
        </div>
    );
}
