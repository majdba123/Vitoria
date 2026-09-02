import { Cell, Pie, PieChart } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';

/** A compact part-to-whole view. Callers must provide mutually exclusive rows. */
export function DonutChart({ rows, total, totalLabel, formatValue }) {
    const locale = useLocale();
    const resolvedFormatValue = formatValue ?? ((value) => formatNumber(Number(value || 0), locale));
    const visibleRows = rows.filter((row) => Number(row.value || 0) > 0);
    const safeTotal = Number(total || 0);

    return (
        <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(10rem,0.8fr)] sm:items-center">
            <div className="relative mx-auto w-full max-w-[18rem]">
                <ChartContainer config={Object.fromEntries(rows.map((row) => [row.key, { label: row.label, color: row.color }]))} className="aspect-square h-60 w-full">
                    <PieChart>
                        <ChartTooltip content={<ChartTooltipContent nameKey="name" formatter={(value, _name, item) => <span className="font-mono font-medium tabular-nums text-foreground">{resolvedFormatValue(value)} · {safeTotal ? Math.round((Number(value) / safeTotal) * 100) : 0}%</span>} />} />
                        <Pie data={visibleRows} dataKey="value" nameKey="label" innerRadius="62%" outerRadius="88%" paddingAngle={2} strokeWidth={0}>
                            {visibleRows.map((row) => <Cell key={row.key} fill={row.color} />)}
                        </Pie>
                    </PieChart>
                </ChartContainer>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span className="text-2xl font-bold tabular-nums text-foreground">{resolvedFormatValue(safeTotal)}</span>
                    <span className="mt-0.5 max-w-24 text-[10px] font-bold uppercase tracking-[0.12em] text-muted-foreground">{totalLabel}</span>
                </div>
            </div>
            <ul className="space-y-2" aria-label={totalLabel}>
                {rows.map((row) => {
                    const value = Number(row.value || 0);
                    const percentage = safeTotal ? Math.round((value / safeTotal) * 100) : 0;

                    return <li key={row.key} className="flex items-start justify-between gap-3 text-sm"><span className="flex min-w-0 items-start gap-2 text-muted-foreground"><span className="mt-1 size-2.5 shrink-0 rounded-full" style={{ backgroundColor: row.color }} /><span className="break-words leading-snug">{row.label}</span></span><span className="shrink-0 font-semibold tabular-nums text-foreground">{resolvedFormatValue(value)} <span className="text-muted-foreground">({percentage}%)</span></span></li>;
                })}
            </ul>
            <p className="sr-only">{rows.map((row) => `${row.label}: ${resolvedFormatValue(row.value)}, ${safeTotal ? Math.round((Number(row.value || 0) / safeTotal) * 100) : 0}%.`).join(' ')}</p>
        </div>
    );
}
