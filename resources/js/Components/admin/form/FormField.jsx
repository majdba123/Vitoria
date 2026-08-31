import { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import { useI18n, useLocale } from '@/hooks/use-i18n';

function FieldShell({ id, label, required, error, hint, descriptionId, children }) {
    return (
        <div>
            <Label htmlFor={id} className="mb-1.5">
                {label}
                {required && <span className="text-[var(--color-danger-strong)]"> *</span>}
            </Label>
            {children}
            {hint && !error && <p id={descriptionId} className="mt-1.5 text-xs text-muted-foreground">{hint}</p>}
            {error && <p id={descriptionId} className="mt-1.5 text-xs font-medium text-[var(--color-danger-strong)]">{error}</p>}
        </div>
    );
}

export function TextField({ id, label, required, error, hint, className, type, ...props }) {
    const locale = useLocale();
    const { common } = useI18n();
    const isDateControl = ['date', 'time', 'datetime-local'].includes(type);
    const dateHint = type === 'date' ? common.date_format_hint : undefined;
    const effectiveHint = hint ?? dateHint;
    const descriptionId = (error || effectiveHint) ? `${id}-description` : undefined;
    const [visible, setVisible] = useState(false);
    const [dateFocused, setDateFocused] = useState(false);

    if (type === 'password') {
        return (
            <FieldShell id={id} label={label} required={required} error={error} hint={hint} descriptionId={descriptionId}>
                <div className="relative">
                    <Input
                        id={id}
                        name={id}
                        type={visible ? 'text' : 'password'}
                        aria-invalid={!!error}
                        aria-describedby={descriptionId}
                        className={cn('pe-10', error && 'border-[var(--color-danger-500)]', className)}
                        {...props}
                    />
                    <button
                        type="button"
                        onClick={() => setVisible((v) => !v)}
                        aria-label={visible ? common.hide_password : common.show_password}
                        aria-pressed={visible}
                        className="absolute inset-y-0 flex w-10 items-center justify-center text-muted-foreground transition-colors hover:text-foreground ltr:right-0 rtl:left-0"
                    >
                        {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                    </button>
                </div>
            </FieldShell>
        );
    }

    if (type === 'date') {
        const showArabicDateFormat = locale === 'ar' && !props.value && !dateFocused;

        return (
            <FieldShell id={id} label={label} required={required} error={error} hint={effectiveHint} descriptionId={descriptionId}>
                <div className="relative">
                    <Input
                        id={id}
                        name={id}
                        type="date"
                        lang={locale}
                        dir={locale === 'ar' ? 'rtl' : 'ltr'}
                        aria-invalid={!!error}
                        aria-describedby={descriptionId}
                        className={cn(error && 'border-[var(--color-danger-500)]', className)}
                        {...props}
                        onFocus={(event) => {
                            setDateFocused(true);
                            props.onFocus?.(event);
                        }}
                        onBlur={(event) => {
                            setDateFocused(false);
                            props.onBlur?.(event);
                        }}
                    />
                    {showArabicDateFormat && (
                        <span aria-hidden="true" className="pointer-events-none absolute inset-y-px start-3 end-10 flex items-center bg-background text-sm text-muted-foreground">
                            {common.date_format_hint}
                        </span>
                    )}
                </div>
            </FieldShell>
        );
    }

    return (
        <FieldShell id={id} label={label} required={required} error={error} hint={effectiveHint} descriptionId={descriptionId}>
            <Input id={id} name={id} type={type} lang={isDateControl ? locale : undefined} dir={isDateControl ? 'ltr' : props.dir} aria-invalid={!!error} aria-describedby={descriptionId} className={cn(isDateControl && 'text-left [unicode-bidi:plaintext]', error && 'border-[var(--color-danger-500)]', className)} {...props} />
        </FieldShell>
    );
}

export function TextareaField({ id, label, required, error, hint, className, ...props }) {
    const descriptionId = (error || hint) ? `${id}-description` : undefined;
    return (
        <FieldShell id={id} label={label} required={required} error={error} hint={hint} descriptionId={descriptionId}>
            <Textarea id={id} name={id} aria-invalid={!!error} aria-describedby={descriptionId} className={cn(error && 'border-[var(--color-danger-500)]', className)} {...props} />
        </FieldShell>
    );
}

export function SelectField({ id, label, required, error, hint, value, onValueChange, placeholder, options }) {
    const descriptionId = (error || hint) ? `${id}-description` : undefined;
    return (
        <FieldShell id={id} label={label} required={required} error={error} hint={hint} descriptionId={descriptionId}>
            <Select value={value} onValueChange={onValueChange}>
                {/* Radix's combobox-role trigger button doesn't reliably pick up
                    the <label for> association the way native form controls do
                    (axe-core flagged it as a critical "no discernible name"
                    violation), so the accessible name is given explicitly. */}
                <SelectTrigger id={id} aria-label={label} aria-describedby={descriptionId} className={cn('w-full', error && 'border-[var(--color-danger-500)]')}>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={String(option.value)}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </FieldShell>
    );
}

export function FileField({ id, label, required, error, hint, preview, onChange }) {
    const descriptionId = (error || hint) ? `${id}-description` : undefined;
    return (
        <FieldShell id={id} label={label} required={required} error={error} hint={hint} descriptionId={descriptionId}>
            {preview && <img src={preview} alt="" className="mb-2 size-20 rounded-md border border-border object-cover" />}
            <Input id={id} name={id} type="file" accept="image/*" onChange={onChange} aria-invalid={!!error} aria-describedby={descriptionId} className={cn(error && 'border-[var(--color-danger-500)]')} />
        </FieldShell>
    );
}
