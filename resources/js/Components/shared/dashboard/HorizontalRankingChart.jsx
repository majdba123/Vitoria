import { Bar, BarChart, CartesianGrid, LabelList, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';

const MAX_LABEL_CHARS = { ar: 12, en: 16 };
const AXIS_WIDTH = { ar: 100, en: 116 };

function truncateLabel(label, max) {
    return label.length > max ? `${label.slice(0, max - 1)}…` : label;
}

/**
 * The chart is always laid out left-to-right internally (YAxis orientation
 * "right" mis-measures its own tick position in the installed recharts
 * version - the tick lands inside the plot, overlapping the bar, once a bar
 * gets close to full length) and mirrored with a CSS scaleX(-1) on the SVG
 * for RTL locales instead. Text nodes inside the mirrored SVG re-mirror
 * themselves with a nested scale(-1,1) so glyphs stay readable while their
 * position still flips with the rest of the chart.
 */
function CategoryTick({ x, y, payload, rtl, maxChars }) {
    const label = String(payload.value ?? '');
    return (
        <g transform={`translate(${x},${y})`}>
            <title>{label}</title>
            <g transform={rtl ? 'scale(-1,1)' : undefined}>
                <text dy={4} textAnchor="end" fontSize={12} className="fill-foreground">
                    {truncateLabel(label, maxChars)}
                </text>
            </g>
        </g>
    );
}

/**
 * Un-mirrors the bar's value label the same way CategoryTick does. A custom
 * LabelList `content` renderer receives the bar's raw `viewBox`, not a
 * pre-computed position - the x/y offsetting that `position="right"` would
 * normally do has to happen here instead.
 */
function ValueLabel({ viewBox, value, rtl, formatValue }) {
    const { x, y, width, height } = viewBox;
    return (
        <g transform={`translate(${x + width},${y + height / 2})`}>
            <g transform={rtl ? 'scale(-1,1)' : undefined}>
                <text dy={4} dx={4} textAnchor="start" fontSize={12} className="fill-foreground text-xs font-semibold">
                    {formatValue(value)}
                </text>
            </g>
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

    return (
        <>
            <ChartContainer
                config={{ value: { label: valueLabel, color: 'var(--chart-1)' } }}
                className={`aspect-auto w-full ${rtl ? '[&_.recharts-surface]:-scale-x-100' : ''}`}
                style={{ height }}
            >
                <BarChart data={data} layout="vertical" margin={{ top: 4, right: 48, bottom: 4, left: 0 }} barCategoryGap="30%">
                    <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                    <XAxis type="number" hide />
                    <YAxis
                        dataKey="label"
                        type="category"
                        orientation="left"
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
                    <Bar dataKey="value" fill="var(--color-value)" radius={[0, 4, 4, 0]} maxBarSize={18} isAnimationActive={false}>
                        <LabelList dataKey="value" content={(props) => <ValueLabel {...props} rtl={rtl} formatValue={resolvedFormatValue} />} />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.label}: ${resolvedFormatValue(row.value)} ${valueLabel}.`).join(' ')}</p>
        </>
    );
}
