import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { formatDate } from '@/lib/date-time';

/** A bounded seven-point trend, shared by the vendor and admin finance views. */
export function OrderTrendChart({ rows, label, locale }) {
    const data = rows.map((row) => ({ date: row.date, count: Number(row.count || 0) }));
    const dayLabel = (value) => {
        return formatDate(value, locale, { dateStyle: undefined, weekday: 'short' }) || value;
    };

    return (
        <>
            <ChartContainer config={{ count: { label, color: 'var(--chart-1)' } }} className="aspect-auto h-56 w-full">
                <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
                    <CartesianGrid vertical={false} strokeDasharray="3 3" />
                    <XAxis dataKey="date" tickLine={false} axisLine={false} tickMargin={8} tickFormatter={dayLabel} />
                    <ChartTooltip content={<ChartTooltipContent labelKey="date" labelFormatter={(value) => formatDate(value, locale)} />} cursor={{ fill: 'var(--color-accent)' }} />
                    <Bar dataKey="count" fill="var(--color-count)" radius={[4, 4, 0, 0]} maxBarSize={32} />
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.date}: ${row.count} ${label}.`).join(' ')}</p>
        </>
    );
}
