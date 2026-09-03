import { Bar, BarChart, CartesianGrid, LabelList, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';

const MAX_LABEL_CHARS = { ar: 12, en: 16 };
const AXIS_WIDTH = { ar: 100, en: 116 };

function truncateLabel(label, max) {
    return label.length > max ? `${label.slice(0, max - 1)}…` : label;
}

/** Y-axis tick that truncates long category names but keeps the full name
 * reachable via a native SVG <title> tooltip, mirrored for RTL reading. */
function CategoryTick({ x, y, payload, rtl, maxChars }) {
    const label = String(payload.value ?? '');
    return (
        <g transform={`translate(${x},${y})`}>
            <title>{label}</title>
            <text dy={4} textAnchor={rtl ? 'start' : 'end'} fontSize={12} className="fill-foreground">
                {truncateLabel(label, maxChars)}
            </text>
        </g>
    );
}

/**
 * A compact, accessible Top-N comparison for dashboard aggregates. It is
 * deliberately for ranking data only; detailed, actionable records remain
 * in their tables and lists.
 */
export function HorizontalRankingChart({ rows, valueKey, labelKey = 'name', valueLabel, formatValue, maxItems = 5 }) {
    const locale = useLocale();
    const rtl = locale === 'ar';
    const resolvedFormatValue = formatValue ?? ((value) => formatNumber(Number(value || 0), locale));
    const data = rows
        .map((row) => ({ label: row[labelKey] ?? '—', value: Number(row[valueKey] ?? 0) }))
        .sort((first, second) => second.value - first.value)
        .slice(0, maxItems);
    const height = Math.max(220, data.length * 44 + 56);
    const axisWidth = AXIS_WIDTH[locale] ?? AXIS_WIDTH.en;
    const maxChars = MAX_LABEL_CHARS[locale] ?? MAX_LABEL_CHARS.en;
    const margin = rtl ? { top: 4, right: 0, bottom: 4, left: 48 } : { top: 4, right: 48, bottom: 4, left: 0 };

    return (
        <>
            <ChartContainer config={{ value: { label: valueLabel, color: 'var(--chart-1)' } }} className="aspect-auto w-full" style={{ height }}>
                <BarChart data={data} layout="vertical" margin={margin} barCategoryGap="30%">
                    <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                    <XAxis type="number" hide reversed={rtl} />
                    <YAxis
                        dataKey="label"
                        type="category"
                        orientation={rtl ? 'right' : 'left'}
                        width={axisWidth}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        tick={<CategoryTick rtl={rtl} maxChars={maxChars} />}
                    />
                    <ChartTooltip
                        cursor={{ fill: 'var(--color-accent)' }}
                        offset={16}
                        content={<ChartTooltipContent labelKey="label" formatter={(value) => <span className="font-mono font-medium tabular-nums text-foreground">{resolvedFormatValue(value)}</span>} />}
                    />
                    <Bar dataKey="value" fill="var(--color-value)" radius={rtl ? [4, 0, 0, 4] : [0, 4, 4, 0]} maxBarSize={18}>
                        <LabelList dataKey="value" position={rtl ? 'left' : 'right'} className="fill-foreground text-xs font-semibold" formatter={resolvedFormatValue} />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.label}: ${resolvedFormatValue(row.value)} ${valueLabel}.`).join(' ')}</p>
        </>
    );
}
