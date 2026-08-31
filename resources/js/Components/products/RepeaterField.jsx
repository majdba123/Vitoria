import { Plus, X } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

/**
 * data-array-list equivalent: an ordered list of free-text strings
 * (barcodes, active ingredients, warnings, ...) with add/remove rows.
 */
export function RepeaterField({ label, hint, button, placeholder, values, onChange, error }) {
    const { products } = useI18n();
    const set = (index, value) => {
        const next = [...values];
        next[index] = value;
        onChange(next);
    };

    const remove = (index) => onChange(values.filter((_, i) => i !== index));
    const add = () => onChange([...values, '']);

    return (
        <div className="rounded-md border border-border bg-muted/40 p-4">
            <p className="text-sm font-medium text-foreground">{label}</p>
            {hint && <p className="mb-3 mt-1 text-xs text-muted-foreground">{hint}</p>}
            <div className="mt-2 space-y-2">
                {values.map((value, index) => (
                    <div key={index} className="flex items-center gap-2">
                        <Input value={value} placeholder={placeholder} onChange={(e) => set(index, e.target.value)} />
                        <Button type="button" variant="outline" size="icon" className="shrink-0 text-[var(--color-danger-strong)]" onClick={() => remove(index)} aria-label={products.form.remove_aria_label}>
                            <X className="size-4" />
                        </Button>
                    </div>
                ))}
            </div>
            <Button type="button" variant="secondary" size="sm" className="mt-3" onClick={add}>
                <Plus className="size-3.5" />
                {button}
            </Button>
            {error && <p className="mt-1.5 text-xs font-medium text-[var(--color-danger-strong)]">{error}</p>}
        </div>
    );
}
