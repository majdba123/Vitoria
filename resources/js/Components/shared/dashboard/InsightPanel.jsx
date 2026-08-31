import { Inbox, RefreshCw } from 'lucide-react';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Link } from '@inertiajs/react';
import { useI18n } from '@/hooks/use-i18n';

/**
 * .card + .panel-heading equivalent: title/copy header, optional
 * "view all" link, and a status-aware body (loading skeleton / error+retry
 * / empty message / real content) so every insight section on the
 * dashboard shares one visual and behavioral contract.
 */
export function InsightPanel({ title, copy, action, status, isEmpty, emptyMessage, onRetry, children, rows = 4 }) {
    const { admin, common } = useI18n();

    return (
        <Card className="gap-0 border-border/80 py-0 shadow-none">
            <CardHeader className="flex flex-row items-start justify-between gap-3 border-b border-border/80 py-4">
                <div>
                    <h3 className="text-sm font-bold text-foreground">{title}</h3>
                    {copy && <p className="mt-0.5 text-xs text-muted-foreground">{copy}</p>}
                </div>
                {action}
            </CardHeader>
            <CardContent className="p-4">
                {status === 'loading' && (
                    <div className="space-y-2">
                        {Array.from({ length: rows }).map((_, index) => (
                            <Skeleton key={index} className="h-11 w-full" />
                        ))}
                    </div>
                )}

                {status === 'error' && (
                    <div className="rounded-md border border-dashed border-border py-8 text-center text-sm text-muted-foreground">
                        <p>{admin.dashboard_load_failed}</p>
                        <button type="button" onClick={onRetry} className="mt-2 inline-flex items-center gap-1.5 font-semibold text-primary hover:underline">
                            <RefreshCw className="size-3.5" />
                            {common.retry}
                        </button>
                    </div>
                )}

                {status === 'ready' && isEmpty && (
                    <div className="rounded-md border border-dashed border-border py-8 text-center text-sm text-muted-foreground"><Inbox className="mx-auto mb-2 size-6" strokeWidth={1.5} aria-hidden="true" />{emptyMessage}</div>
                )}

                {status === 'ready' && !isEmpty && children}
            </CardContent>
        </Card>
    );
}

export function ViewAllLink({ href, children }) {
    return (
        <Link href={href} className="shrink-0 text-xs font-semibold text-primary hover:underline">
            {children}
        </Link>
    );
}
