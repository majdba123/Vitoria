import { Bar, BarChart, CartesianGrid, LabelList, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';

const MAX_LABEL_CHARS = { ar: 15, en: 16 };
const AXIS_WIDTH = { ar: 132, en: 140 };

function truncateLabel(label, max) {
    return label.length > max ? `${label.slice(0, max - 1)}…` : label;
}

function labelLines(label, max) {
    if (label.length <= max) return [label];
    const nextSpace = label.indexOf(' ', max);
    const previousSpace = label.lastIndexOf(' ', max);
    const split = nextSpace > 0 && nextSpace <= max + 3 ? nextSpace : (previousSpace > 0 ? previousSpace : max);
    return [label.slice(0, split), truncateLabel(label.slice(split).trim(), max)];
}

function CategoryTick({ x, y, payload, maxChars }) {
    const label = String(payload.value ?? '');
    const lines = labelLines(label, maxChars);
    return (
        <g transform={`translate(${x},${y})`}>
            <title>{label}</title>
            <text textAnchor="end" direction="ltr" fontSize={12} className="fill-foreground">
                {lines.map((line, index) => <tspan key={index} x="0" dy={index === 0 ? (lines.length === 1 ? 4 : -2) : 13}>{line}</tspan>)}
            </text>
        </g>
    );
}

function ValueLabel({ viewBox, value, formatValue }) {
    const { x, y, width, height } = viewBox;
    return (
        <g transform={`translate(${x + width},${y + height / 2})`}>
            <text dy={4} dx={6} textAnchor="start" direction="ltr" fontSize={12} className="fill-foreground text-xs font-semibold" data-bar-value>
                {formatValue(value)}
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
    const resolvedFormatValue = formatValue ?? ((value) => formatNumber(Number(value || 0), locale));
    const data = rows
        .map((row) => ({ label: row[labelKey] ?? '—', value: Number(row[valueKey] ?? 0) }))
        .sort((first, second) => second.value - first.value)
        .slice(0, maxItems);
    const height = Math.max(144, data.length * 44 + 56);
    const axisWidth = AXIS_WIDTH[locale] ?? AXIS_WIDTH.en;
    const maxChars = MAX_LABEL_CHARS[locale] ?? MAX_LABEL_CHARS.en;
    const valueMargin = Math.max(56, Math.min(120, resolvedFormatValue(Math.max(0, ...data.map((row) => row.value))).length * 7 + 14));

    return (
        <>
            <ChartContainer
                config={{ value: { label: valueLabel, color: 'var(--chart-1)' } }}
                className="aspect-auto w-full"
                style={{ height }}
            >
                <BarChart data={data} layout="vertical" margin={{ top: 4, right: valueMargin, bottom: 4, left: 0 }} barCategoryGap="30%">
                    <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                    {/* Headroom past the longest bar keeps the outside value label
                        inside the plot; bar lengths stay proportional to their
                        values - a low value must never look close to a high one. */}
                    <XAxis type="number" hide domain={[0, (dataMax) => Math.max(1, Math.ceil(Number(dataMax) * 1.18))]} />
                    <YAxis
                        dataKey="label"
                        type="category"
                        orientation="left"
                        width={axisWidth}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        tick={<CategoryTick maxChars={maxChars} />}
                    />
                    <ChartTooltip
                        cursor={{ fill: 'var(--color-accent)' }}
                        offset={16}
                        content={<ChartTooltipContent formatter={(value) => <span className="font-mono font-medium tabular-nums text-foreground">{resolvedFormatValue(value)}</span>} />}
                    />
                    <Bar dataKey="value" fill="var(--color-value)" radius={[0, 4, 4, 0]} maxBarSize={18} isAnimationActive={false}>
                        <LabelList dataKey="value" content={(props) => <ValueLabel {...props} formatValue={resolvedFormatValue} />} />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.label}: ${resolvedFormatValue(row.value)} ${valueLabel}.`).join(' ')}</p>
        </>
    );
}
