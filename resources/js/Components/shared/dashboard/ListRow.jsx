import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';

const TONE_CLASSES = {
    success: 'border-transparent bg-[var(--color-success-soft)] text-[var(--color-success-strong)]',
    warning: 'border-transparent bg-[var(--color-warning-soft)] text-[var(--color-warning-strong)]',
    danger: 'border-transparent bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]',
    brand: 'border-transparent bg-accent text-accent-foreground',
};

export function StatusBadge({ tone = 'brand', children }) {
    return <Badge className={TONE_CLASSES[tone] ?? TONE_CLASSES.brand}>{children}</Badge>;
}

/**
 * .list-panel equivalent: a clickable row with a title/subtitle pair and a
 * trailing badge or value, used across every "recent X" / "top X" list on
 * the dashboard.
 */
export function ListRow({ href, title, subtitle, trailing, chevron = true }) {
    return (
        <Link
            href={href}
            className="group flex items-center justify-between gap-3 rounded-md border border-border/70 bg-card px-4 py-3 transition-colors hover:border-primary/50 hover:bg-accent/40"
        >
            <div className="min-w-0">
                <p className="truncate text-sm font-semibold text-foreground">{title}</p>
                {subtitle && <p className="mt-0.5 truncate text-xs text-muted-foreground">{subtitle}</p>}
            </div>
            <div className="flex shrink-0 items-center gap-2.5">
                {trailing}
                {chevron && (
                    <ChevronRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" />
                )}
            </div>
        </Link>
    );
}
