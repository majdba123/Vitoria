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

export function TextField({ id, label, required, error, hint, className, ...props }) {
    const descriptionId = (error || hint) ? `${id}-description` : undefined;
    return (
        <FieldShell id={id} label={label} required={required} error={error} hint={hint} descriptionId={descriptionId}>
            <Input id={id} name={id} aria-invalid={!!error} aria-describedby={descriptionId} className={cn(error && 'border-[var(--color-danger-500)]', className)} {...props} />
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
                <SelectTrigger id={id} aria-describedby={descriptionId} className={cn('w-full', error && 'border-[var(--color-danger-500)]')}>
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
