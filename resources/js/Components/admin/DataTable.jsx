import { AlertTriangle, RefreshCw, SearchX } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Generic list table: columns declare {key, label, align, render(row)}.
 * Handles loading/error/empty states so every admin index page shares one
 * table shell instead of re-deriving thead/skeleton/empty markup.
 */
export function DataTable({ columns, rows, status, errorMessage, onRetry, rowHref, emptyTitle, emptyHint, skeletonRows = 6 }) {
    const { common } = useI18n();

    if (status === 'error') {
        return (
            <div className="rounded-lg border border-dashed border-border py-14 text-center">
                <AlertTriangle className="mx-auto mb-3 size-6 text-[var(--color-danger-strong)]" aria-hidden="true" />
                <p className="text-sm font-semibold text-foreground">{errorMessage ?? common.error_generic ?? 'Something went wrong.'}</p>
                <button type="button" onClick={onRetry} className="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                    <RefreshCw className="size-3.5" />
                    {common.refresh ?? 'Retry'}
                </button>
            </div>
        );
    }

    if (status === 'ready' && rows.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-border py-14 text-center">
                <SearchX className="mx-auto mb-3 size-7 text-muted-foreground" strokeWidth={1.5} aria-hidden="true" />
                <p className="text-sm font-semibold text-foreground">{emptyTitle}</p>
                {emptyHint && <p className="mt-1 text-sm text-muted-foreground">{emptyHint}</p>}
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-border" role="region" aria-label={emptyTitle ?? 'Data table'} tabIndex={0}>
            <Table>
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        {columns.map((column) => (
                            <TableHead key={column.key} className={column.align === 'end' ? 'text-end' : undefined}>
                                {column.label}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {status === 'loading' &&
                        Array.from({ length: skeletonRows }).map((_, index) => (
                            <TableRow key={index}>
                                {columns.map((column) => (
                                    <TableCell key={column.key}>
                                        <Skeleton className="h-5 w-full max-w-40" />
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}

                    {status === 'ready' &&
                        rows.map((row) => (
                            <TableRow key={row.id} className={rowHref ? 'cursor-pointer' : undefined}>
                                {columns.map((column, index) => (
                                    <TableCell key={column.key} className={column.align === 'end' ? 'text-end tabular-nums' : undefined}>
                                        {index === 0 && rowHref ? (
                                            <Link href={rowHref(row)} className="block font-semibold text-foreground hover:text-primary">
                                                {column.render(row)}
                                            </Link>
                                        ) : (
                                            column.render(row)
                                        )}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                </TableBody>
            </Table>
        </div>
    );
}
