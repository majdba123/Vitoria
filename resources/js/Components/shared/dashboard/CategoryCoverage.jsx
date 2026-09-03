import { Bar, BarChart, CartesianGrid, LabelList, Legend, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartLegendContent, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { useI18n, useLocale } from '@/hooks/use-i18n';

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
 * Vendor coverage per category: a three-segment bar (active/pending/
 * inactive) plus counts, replacing the hand-built <div style="width:%">
 * bars in the original dashboard script.
 */
export function CategoryCoverage({ rows }) {
    const { admin } = useI18n();
    const locale = useLocale();
    const rtl = locale === 'ar';
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
    const margin = rtl ? { top: 4, right: 0, bottom: 0, left: 36 } : { top: 4, right: 36, bottom: 0, left: 0 };

    return (
        <>
            <ChartContainer config={{ active: { label: admin.status_active, color: 'var(--color-success-500)' }, pending: { label: admin.status_pending, color: 'var(--color-warning-500)' }, inactive: { label: admin.status_inactive, color: 'var(--color-danger-500)' } }} className="aspect-auto w-full" style={{ height }}>
                <BarChart data={data} layout="vertical" margin={margin} barCategoryGap="32%">
                    <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                    <XAxis type="number" hide reversed={rtl} />
                    <YAxis
                        dataKey="name"
                        type="category"
                        orientation={rtl ? 'right' : 'left'}
                        width={axisWidth}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        tick={<CategoryTick rtl={rtl} maxChars={maxChars} />}
                    />
                    <ChartTooltip content={<ChartTooltipContent labelKey="name" />} cursor={{ fill: 'var(--color-accent)' }} offset={16} />
                    <Legend content={<ChartLegendContent />} verticalAlign="top" />
                    <Bar dataKey="active" stackId="vendors" fill="var(--color-active)" maxBarSize={20} />
                    <Bar dataKey="pending" stackId="vendors" fill="var(--color-pending)" maxBarSize={20} />
                    <Bar dataKey="inactive" stackId="vendors" fill="var(--color-inactive)" radius={rtl ? [4, 0, 0, 4] : [0, 4, 4, 0]} maxBarSize={20}>
                        <LabelList dataKey="total" position={rtl ? 'left' : 'right'} className="fill-foreground text-xs font-semibold" />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.name}: ${row.total} ${admin.total_vendors}; ${row.active} ${admin.status_active}, ${row.pending} ${admin.status_pending}, ${row.inactive} ${admin.status_inactive}.`).join(' ')}</p>
        </>
    );
}
