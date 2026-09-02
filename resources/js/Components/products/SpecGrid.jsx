import { formatDetailValue } from '@/lib/product-detail-labels';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Read-only label/value grid for a product detail bucket (shared /
 * agricultural / veterinary). Only renders keys that actually have a
 * value, so an empty section collapses to nothing instead of a wall of
 * dashes.
 */
export function SpecGrid({ values, labels }) {
    const { products } = useI18n();
    const entries = Object.entries(values ?? {})
        // Only render keys the schema actually knows about - the API
        // returns the full detail row (id, foreign keys, timestamps
        // included), and those aren't meaningful to show here.
        .filter(([key]) => key in labels)
        .map(([key, value]) => [products.detail_labels[key], formatDetailValue(value)])
        .filter(([, value]) => value !== null && value !== undefined && value !== '');

    if (entries.length === 0) {
        return <p className="text-sm text-muted-foreground">{products.nothing_recorded_yet}</p>;
    }

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{label}</p>
                    <p className="mt-1 text-sm text-foreground">{value}</p>
                </div>
            ))}
        </div>
    );
}
