import { TextField, TextareaField, SelectField } from '@/Components/admin/form/FormField';
import { RepeaterField } from '@/Components/products/RepeaterField';

/**
 * Renders one schema slice (see lib/product-detail-schema.js) against a
 * single `values` object + `onChange(key, value)` setter. Used for every
 * shared/agricultural/veterinary field group so the ~80 detail fields don't
 * need one hand-written input each.
 */
export function DetailFieldGroup({ fields = [], repeaters = [], textareas = [], selects = [], values, onChange, errors = {}, prefix }) {
    const errorFor = (key) => errors[`${prefix}.${key}`];

    return (
        <>
            {(fields.length > 0 || selects.length > 0) && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {fields.map((field) =>
                        field.type === 'number' ? (
                            <TextField key={field.key} id={`${prefix}_${field.key}`} label={field.label} type="number" step="0.01" value={values[field.key] ?? ''} onChange={(e) => onChange(field.key, e.target.value)} error={errorFor(field.key)} />
                        ) : (
                            <TextField key={field.key} id={`${prefix}_${field.key}`} label={field.label} value={values[field.key] ?? ''} onChange={(e) => onChange(field.key, e.target.value)} error={errorFor(field.key)} />
                        ),
                    )}
                    {selects.map((select) => (
                        <SelectField
                            key={select.key}
                            id={`${prefix}_${select.key}`}
                            label={select.label}
                            value={values[select.key] ?? ''}
                            onValueChange={(value) => onChange(select.key, value)}
                            placeholder={select.placeholder ?? 'Select...'}
                            options={select.options}
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
                            label={repeater.label}
                            hint={repeater.hint}
                            button={repeater.button}
                            placeholder={repeater.placeholder}
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
                        <TextareaField key={textarea.key} id={`${prefix}_${textarea.key}`} label={textarea.label} rows={3} value={values[textarea.key] ?? ''} onChange={(e) => onChange(textarea.key, e.target.value)} error={errorFor(textarea.key)} />
                    ))}
                </div>
            )}
        </>
    );
}
