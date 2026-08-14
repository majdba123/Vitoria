import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';

const chartConfig = {
    total: {
        label: 'Products',
        color: 'var(--chart-1)',
    },
};

export function GrowthChart({ rows, totalLabel }) {
    const data = rows.map((row) => ({ month: row.month, total: Number(row.total || 0) }));

    return (
        <ChartContainer config={{ total: { ...chartConfig.total, label: totalLabel ?? chartConfig.total.label } }} className="aspect-auto h-56 w-full">
            <BarChart data={data} margin={{ left: 0, right: 8, top: 8 }}>
                <CartesianGrid vertical={false} strokeDasharray="3 3" />
                <XAxis
                    dataKey="month"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    tickFormatter={(value) => value?.slice?.(5) ?? value}
                />
                <ChartTooltip content={<ChartTooltipContent labelKey="month" />} cursor={{ fill: 'var(--color-accent)' }} />
                <Bar dataKey="total" fill="var(--color-total)" radius={[3, 3, 0, 0]} maxBarSize={28} />
            </BarChart>
        </ChartContainer>
    );
}
