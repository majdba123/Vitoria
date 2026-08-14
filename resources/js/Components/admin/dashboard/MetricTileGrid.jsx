import { Link } from '@inertiajs/react';

/**
 * .list-panel grid used for "N by type" breakdowns (vendors/syndicates/
 * categories/products by agriculture|veterinary|both).
 */
export function MetricTileGrid({ rows, hrefFor, columns = 3 }) {
    return (
        <div className={`grid gap-3 ${columns === 2 ? 'sm:grid-cols-2' : 'sm:grid-cols-3'}`}>
            {rows.map((row) => (
                <Link
                    key={row.type}
                    href={hrefFor(row.type)}
                    className="rounded-md border border-border/70 bg-card px-4 py-3 transition-colors hover:border-primary/50 hover:bg-accent/40"
                >
                    <p className="truncate text-[11px] font-bold uppercase tracking-[0.14em] text-muted-foreground">{row.label}</p>
                    <p className="mt-2 text-xl font-bold tabular-nums text-foreground">{Number(row.total || 0)}</p>
                </Link>
            ))}
        </div>
    );
}
