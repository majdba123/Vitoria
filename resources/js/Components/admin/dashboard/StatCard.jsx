import { RefreshCw } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n } from '@/hooks/use-i18n';

/**
 * .stat-tile equivalent: label + big number + icon, with the same
 * per-tile retry-on-failure affordance the Blade dashboard had.
 */
export function StatCard({ label, value, icon: Icon, status = 'ready', onRetry, tone }) {
    const { common } = useI18n();

    const toneClass = tone === 'danger' ? 'text-[var(--color-danger-strong)]' : tone === 'success' ? 'text-[var(--color-success-strong)]' : 'text-foreground';

    return (
        <Card className="border-border/80 shadow-none">
            <CardContent className="flex items-start justify-between gap-4 px-5 py-4">
                <div className="min-w-0 flex-1">
                    <p className="truncate text-[11px] font-bold uppercase tracking-[0.16em] text-muted-foreground">{label}</p>
                    {status === 'loading' && <Skeleton className="mt-3 h-7 w-16" />}
                    {status === 'error' && (
                        <button type="button" onClick={onRetry} className="mt-2 flex items-center gap-1.5 text-xs font-semibold text-[var(--color-danger-strong)]">
                            <RefreshCw className="size-3" />
                            {common.refresh ?? 'Retry'}
                        </button>
                    )}
                    {status === 'ready' && <p className={`mt-2 text-2xl font-bold tabular-nums ${toneClass}`}><bdi>{value}</bdi></p>}
                </div>
                <span className="flex size-11 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">
                    <Icon className="size-5" strokeWidth={1.5} />
                </span>
            </CardContent>
        </Card>
    );
}
