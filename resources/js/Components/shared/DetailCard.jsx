import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';

/**
 * .card + label/value grid used on every "show" page (category, subcategory,
 * city, ...). `fields` is [{label, value}]; pass `isLoading` to render
 * skeletons instead of real values while the detail record is in flight.
 */
export function DetailCard({ title, fields, isLoading, columns = 1 }) {
    return (
        <Card className="gap-0 border-border/80 py-0 shadow-none">
            {title && (
                <CardHeader className="border-b border-border/80 py-4">
                    <CardTitle className="text-base font-bold">{title}</CardTitle>
                </CardHeader>
            )}
            <CardContent className={`grid gap-4 p-5 ${columns === 2 ? 'sm:grid-cols-2' : ''}`}>
                {fields.map((field) => (
                    <div key={field.label}>
                        <p className="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">{field.label}</p>
                        {isLoading ? <Skeleton className="mt-1.5 h-5 w-32" /> : <div className="mt-1 text-sm font-semibold text-foreground">{field.value ?? '—'}</div>}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
