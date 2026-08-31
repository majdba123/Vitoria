import { Bar, BarChart, CartesianGrid, LabelList, Legend, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartLegendContent, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Vendor coverage per category: a three-segment bar (active/pending/
 * inactive) plus counts, replacing the hand-built <div style="width:%">
 * bars in the original dashboard script.
 */
export function CategoryCoverage({ rows }) {
    const { admin } = useI18n();
    const data = rows
        .map((row) => ({
            name: row.name,
            active: Number(row.active_vendors || 0),
            pending: Number(row.pending_vendors || 0),
            inactive: Number(row.inactive_vendors || 0),
            total: Number(row.total_vendors || 0),
        }))
        .sort((first, second) => second.total - first.total)
        .slice(0, 8);
    const height = Math.max(260, data.length * 42 + 72);

    return (
        <>
            <ChartContainer config={{ active: { label: admin.status_active, color: 'var(--color-success-500)' }, pending: { label: admin.status_pending, color: 'var(--color-warning-500)' }, inactive: { label: admin.status_inactive, color: 'var(--color-danger-500)' } }} className="aspect-auto w-full" style={{ height }}>
                <BarChart data={data} layout="vertical" margin={{ top: 4, right: 32, bottom: 0, left: 0 }}>
                    <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                    <XAxis type="number" hide />
                    <YAxis dataKey="name" type="category" width={116} tickLine={false} axisLine={false} tickMargin={8} tick={{ fontSize: 12 }} />
                    <ChartTooltip content={<ChartTooltipContent labelKey="name" />} cursor={{ fill: 'var(--color-accent)' }} />
                    <Legend content={<ChartLegendContent />} />
                    <Bar dataKey="active" stackId="vendors" fill="var(--color-active)" maxBarSize={24} />
                    <Bar dataKey="pending" stackId="vendors" fill="var(--color-pending)" maxBarSize={24} />
                    <Bar dataKey="inactive" stackId="vendors" fill="var(--color-inactive)" radius={[0, 4, 4, 0]} maxBarSize={24}>
                        <LabelList dataKey="total" position="right" className="fill-foreground text-xs font-semibold" />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.name}: ${row.total} ${admin.total_vendors}; ${row.active} ${admin.status_active}, ${row.pending} ${admin.status_pending}, ${row.inactive} ${admin.status_inactive}.`).join(' ')}</p>
        </>
    );
}
