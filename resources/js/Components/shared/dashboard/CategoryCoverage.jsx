import { Bar, BarChart, CartesianGrid, LabelList, Legend, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartLegendContent, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useI18n, useIsRtl, useLocale } from '@/hooks/use-i18n';

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

function CategoryTick({ x, y, payload, maxChars, isRtl }) {
    const label = String(payload.value ?? '');
    const lines = labelLines(label, maxChars);
    return (
        <g transform={`translate(${x},${y})`}>
            <title>{label}</title>
            <text textAnchor={isRtl ? 'start' : 'end'} direction="ltr" fontSize={12} className="fill-foreground">
                {lines.map((line, index) => <tspan key={index} x="0" dy={index === 0 ? (lines.length === 1 ? 4 : -2) : 13}>{line}</tspan>)}
            </text>
        </g>
    );
}

const SEGMENTS = ['active', 'pending', 'inactive'];

/** Draws the row total once, after the outermost non-empty segment, since
 * recharts skips labels on zero-width stacked segments. */
function TotalLabel({ viewBox, value: row, segment, isRtl }) {
    if (!row || row.total === 0 || SEGMENTS.slice(SEGMENTS.indexOf(segment) + 1).some((key) => row[key] > 0)) {
        return null;
    }
    const { x, y, width, height } = viewBox;
    const tip = isRtl ? Math.min(x, x + width) : Math.max(x, x + width);
    return (
        <g transform={`translate(${tip},${y + height / 2})`}>
            <text dy={4} dx={isRtl ? -6 : 6} textAnchor={isRtl ? 'end' : 'start'} direction="ltr" fontSize={12} className="fill-foreground text-xs font-semibold">
                {row.total}
            </text>
        </g>
    );
}

/**
 * Vendor coverage per category: a three-segment bar (active/pending/
 * inactive) plus counts, replacing the hand-built <div style="width:%">
 * bars in the original dashboard script.
 */
export function CategoryCoverage({ rows }) {
    const { admin } = useI18n();
    const locale = useLocale();
    const isRtl = useIsRtl();
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
    const height = Math.max(260, data.length * 46 + 72);
    const axisWidth = AXIS_WIDTH[locale] ?? AXIS_WIDTH.en;
    const maxChars = MAX_LABEL_CHARS[locale] ?? MAX_LABEL_CHARS.en;

    return (
        <>
            <ChartContainer config={{ active: { label: admin.status_active, color: 'var(--color-success-500)' }, pending: { label: admin.status_pending, color: 'var(--color-warning-500)' }, inactive: { label: admin.status_inactive, color: 'var(--color-danger-500)' } }} className="aspect-auto w-full" style={{ height }}>
                <BarChart data={data} layout="vertical" margin={{ top: 4, right: isRtl ? 0 : 48, bottom: 0, left: isRtl ? 48 : 0 }} barCategoryGap="32%">
                    <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                    <XAxis type="number" hide reversed={isRtl} />
                    <YAxis
                        dataKey="name"
                        type="category"
                        orientation={isRtl ? 'right' : 'left'}
                        width={axisWidth}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        tick={<CategoryTick maxChars={maxChars} isRtl={isRtl} />}
                    />
                    <ChartTooltip content={<ChartTooltipContent />} cursor={{ fill: 'var(--color-accent)' }} offset={16} />
                    <Legend content={<ChartLegendContent />} verticalAlign="top" />
                    {SEGMENTS.map((segment) => (
                        <Bar key={segment} dataKey={segment} stackId="vendors" fill={`var(--color-${segment})`} maxBarSize={20} isAnimationActive={false}>
                            <LabelList valueAccessor={(entry) => entry.payload} content={(props) => <TotalLabel {...props} segment={segment} isRtl={isRtl} />} />
                        </Bar>
                    ))}
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.name}: ${row.total} ${admin.total_vendors}; ${row.active} ${admin.status_active}, ${row.pending} ${admin.status_pending}, ${row.inactive} ${admin.status_inactive}.`).join(' ')}</p>
        </>
    );
}
