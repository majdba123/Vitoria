import { useI18n } from '@/hooks/use-i18n';

function pageRange(current, last) {
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = [1];
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) pages.push(i);
    if (current < last - 2) pages.push('...');
    pages.push(last);
    return pages;
}

export function PublicPagination({ meta, onPageChange }) {
    const { nav } = useI18n();
    if (!meta || meta.last_page <= 1) return null;

    const current = meta.current_page;
    const last = meta.last_page;

    return (
        <div className="mt-8 flex flex-wrap items-center justify-center gap-1.5">
            <button
                type="button"
                onClick={() => onPageChange(current - 1)}
                disabled={current === 1}
                className="flex h-10 items-center rounded-lg border border-border bg-card px-4 text-xs font-bold text-muted-foreground disabled:pointer-events-none disabled:opacity-40"
            >
                {nav.prev}
            </button>
            {pageRange(current, last).map((p, i) => p === '...' ? (
                <span key={`gap-${i}`} className="px-2 text-muted-foreground">…</span>
            ) : (
                <button
                    key={p}
                    type="button"
                    onClick={() => onPageChange(p)}
                    className={`flex h-10 w-10 items-center justify-center rounded-lg border text-xs font-bold ${p === current ? 'page-active' : 'border-border bg-card text-muted-foreground'}`}
                >
                    {p}
                </button>
            ))}
            <button
                type="button"
                onClick={() => onPageChange(current + 1)}
                disabled={current === last}
                className="flex h-10 items-center rounded-lg border border-border bg-card px-4 text-xs font-bold text-muted-foreground disabled:pointer-events-none disabled:opacity-40"
            >
                {nav.next}
            </button>
        </div>
    );
}
