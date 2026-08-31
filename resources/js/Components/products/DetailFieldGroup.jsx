import { TextField, TextareaField, SelectField } from '@/Components/admin/form/FormField';
import { RepeaterField } from '@/Components/products/RepeaterField';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Renders one schema slice (see lib/product-detail-schema.js) against a
 * single `values` object + `onChange(key, value)` setter. Used for every
 * shared/agricultural/veterinary field group so the ~80 detail fields don't
 * need one hand-written input each.
 *
 * Display text (labels, select-option labels, repeater button/placeholder/
 * hint) is resolved through the products.detail_labels /
 * products.repeater_fields / products.registration_status_options lang
 * keys, falling back to the schema's built-in English default when a
 * translation for that key doesn't exist yet.
 */
export function DetailFieldGroup({ fields = [], repeaters = [], textareas = [], selects = [], values, onChange, errors = {}, prefix }) {
    const { products } = useI18n();
    const errorFor = (key) => errors[`${prefix}.${key}`];
    const labelFor = (key, fallback) => products?.detail_labels?.[key] ?? fallback;
    const translateSelectOptions = (options) =>
        options.map((option) => ({ ...option, label: products?.registration_status_options?.[option.value] ?? option.label }));

    return (
        <>
            {(fields.length > 0 || selects.length > 0) && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {fields.map((field) =>
                        field.type === 'number' ? (
                            <TextField key={field.key} id={`${prefix}_${field.key}`} label={labelFor(field.key, field.label)} type="number" step="0.01" value={values[field.key] ?? ''} onChange={(e) => onChange(field.key, e.target.value)} error={errorFor(field.key)} />
                        ) : (
                            <TextField key={field.key} id={`${prefix}_${field.key}`} label={labelFor(field.key, field.label)} value={values[field.key] ?? ''} onChange={(e) => onChange(field.key, e.target.value)} error={errorFor(field.key)} />
                        ),
                    )}
                    {selects.map((select) => (
                        <SelectField
                            key={select.key}
                            id={`${prefix}_${select.key}`}
                            label={labelFor(select.key, select.label)}
                            value={values[select.key] ?? ''}
                            onValueChange={(value) => onChange(select.key, value)}
                            placeholder={select.placeholder}
                            options={translateSelectOptions(select.options)}
                            error={errorFor(select.key)}
                        />
                    ))}
                </div>
            )}

            {repeaters.length > 0 && (
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    {repeaters.map((repeater) => (
                        <RepeaterField
                            key={repeater.key}
                            label={labelFor(repeater.key, repeater.label)}
                            hint={products?.repeater_fields?.[repeater.key]?.hint ?? repeater.hint}
                            button={products?.repeater_fields?.[repeater.key]?.button ?? repeater.button}
                            placeholder={products?.repeater_fields?.[repeater.key]?.placeholder ?? repeater.placeholder}
                            values={values[repeater.key] ?? []}
                            onChange={(next) => onChange(repeater.key, next)}
                            error={errorFor(repeater.key)}
                        />
                    ))}
                </div>
            )}

            {textareas.length > 0 && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {textareas.map((textarea) => (
                        <TextareaField key={textarea.key} id={`${prefix}_${textarea.key}`} label={labelFor(textarea.key, textarea.label)} rows={3} value={values[textarea.key] ?? ''} onChange={(e) => onChange(textarea.key, e.target.value)} error={errorFor(textarea.key)} />
                    ))}
                </div>
            )}
        </>
    );
}
