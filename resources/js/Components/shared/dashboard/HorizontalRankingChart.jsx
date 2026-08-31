import { Bar, BarChart, CartesianGrid, LabelList, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';

/**
 * A compact, accessible Top-N comparison for dashboard aggregates. It is
 * deliberately for ranking data only; detailed, actionable records remain
 * in their tables and lists.
 */
export function HorizontalRankingChart({ rows, valueKey, labelKey = 'name', valueLabel, formatValue, maxItems = 5 }) {
    const locale = useLocale();
    const resolvedFormatValue = formatValue ?? ((value) => formatNumber(Number(value || 0), locale));
    const data = rows
        .map((row) => ({ label: row[labelKey] ?? '—', value: Number(row[valueKey] ?? 0) }))
        .sort((first, second) => second.value - first.value)
        .slice(0, maxItems);
    const height = Math.max(220, data.length * 38 + 56);

    return (
        <>
            <ChartContainer config={{ value: { label: valueLabel, color: 'var(--chart-1)' } }} className="aspect-auto w-full" style={{ height }}>
                <BarChart data={data} layout="vertical" margin={{ top: 4, right: 44, bottom: 4, left: 0 }}>
                    <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                    <XAxis type="number" hide />
                    <YAxis dataKey="label" type="category" width={116} tickLine={false} axisLine={false} tickMargin={8} tick={{ fontSize: 12 }} />
                    <ChartTooltip
                        cursor={{ fill: 'var(--color-accent)' }}
                        content={<ChartTooltipContent labelKey="label" formatter={(value) => <span className="font-mono font-medium tabular-nums text-foreground">{resolvedFormatValue(value)}</span>} />}
                    />
                    <Bar dataKey="value" fill="var(--color-value)" radius={[0, 4, 4, 0]} maxBarSize={22}>
                        <LabelList dataKey="value" position="right" className="fill-foreground text-xs font-semibold" formatter={resolvedFormatValue} />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.label}: ${resolvedFormatValue(row.value)} ${valueLabel}.`).join(' ')}</p>
        </>
    );
}
