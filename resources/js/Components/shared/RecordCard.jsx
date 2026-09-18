import { Skeleton } from '@/Components/ui/skeleton';
import { cn } from '@/lib/utils';

/** A rendered cell worth showing: `false`/`null`/`''` would just be a blank card row. */
export function hasValue(value) {
    return !(value === null || value === undefined || value === false || value === '');
}

/**
 * One table row rendered as a card, for the widths below `md` where columns
 * cannot line up side by side. A column header only makes sense while they do,
 * so the label moves next to its own value; the first column becomes the card
 * title and row actions sit in the footer.
 *
 * `rows` are {key, label, value, numeric} — a row whose value is blank is
 * dropped rather than rendered as an empty line.
 */
export function RecordCard({ title, rows = [], actions }) {
    return (
        <li className="rounded-lg border border-border bg-card p-4">
            {hasValue(title) && <h3 className="text-sm font-semibold text-foreground">{title}</h3>}

            <dl className="mt-3 space-y-2 border-t border-border pt-3 text-sm empty:mt-0 empty:border-0 empty:pt-0">
                {rows.map((row) =>
                    hasValue(row?.value) ? (
                        <div key={row.key} className="flex items-start justify-between gap-3">
                            <dt className="shrink-0 text-muted-foreground">{row.label}</dt>
                            <dd className={cn('min-w-0 text-end font-medium text-foreground', row.numeric && 'tabular-nums')}>
                                {row.value}
                            </dd>
                        </div>
                    ) : null,
                )}
            </dl>

            {hasValue(actions) && (
                <div className="mt-3 flex flex-wrap items-center gap-2 border-t border-border pt-3">{actions}</div>
            )}
        </li>
    );
}

/** Placeholder card matching {@link RecordCard}'s shape while rows load. */
export function RecordCardSkeleton() {
    return (
        <li className="rounded-lg border border-border bg-card p-4">
            <Skeleton className="h-5 w-2/3" />
            <Skeleton className="mt-3 h-4 w-full" />
            <Skeleton className="mt-2 h-4 w-5/6" />
        </li>
    );
}

/** The card stack itself: shown below `md`, where its sibling table is hidden. */
export function RecordCardList({ children, className }) {
    return <ul className={cn('space-y-3 md:hidden', className)}>{children}</ul>;
}
