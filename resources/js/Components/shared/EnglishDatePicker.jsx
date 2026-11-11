import { useState } from 'react';
import { Popover as PopoverPrimitive } from 'radix-ui';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { localDateString } from '@/lib/date-time';
import { useI18n } from '@/hooks/use-i18n';

const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const DISPLAY_FORMAT = new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'short', day: '2-digit', timeZone: 'UTC' });
const MONTH_FORMAT = new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'long', timeZone: 'UTC' });
const FULL_FORMAT = new Intl.DateTimeFormat('en-US', { dateStyle: 'full', timeZone: 'UTC' });

const pad = (number) => String(number).padStart(2, '0');
const toValue = (year, month, day) => `${year}-${pad(month + 1)}-${pad(day)}`;

/** "YYYY-MM-DD" → {year, month (0-based), day}, or null. Never goes through a timezone. */
function parseValue(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value ?? '');

    return match ? { year: Number(match[1]), month: Number(match[2]) - 1, day: Number(match[3]) } : null;
}

const utc = (year, month, day = 1) => Date.UTC(year, month, day);

/**
 * Date field for report filters that always shows English (en-US) month and
 * weekday names with Latin digits, whatever the page or browser language.
 * A native `<input type="date">` cannot do that: browsers format it from the
 * OS/browser locale and ignore the `lang` attribute, so on an Arabic system
 * the Ministry saw Arabic-Indic dates. The value stays a plain "YYYY-MM-DD"
 * string — exactly what the report endpoints already receive — and all date
 * math is done on calendar parts in UTC so no timezone can shift a day.
 */
export function EnglishDatePicker({ value, onChange, min, max, id, className, 'aria-label': ariaLabel }) {
    const labels = useI18n().common?.date_picker ?? {};
    const selected = parseValue(value);
    const today = parseValue(localDateString());
    const [open, setOpen] = useState(false);
    const [view, setView] = useState(() => selected ?? today);

    const firstWeekday = new Date(utc(view.year, view.month)).getUTCDay();
    const daysInMonth = new Date(utc(view.year, view.month + 1, 0)).getUTCDate();
    const cells = [...Array(firstWeekday).fill(null), ...Array.from({ length: daysInMonth }, (_, index) => index + 1)];

    const shiftMonth = (delta) => {
        const date = new Date(utc(view.year, view.month + delta));
        setView({ year: date.getUTCFullYear(), month: date.getUTCMonth(), day: 1 });
    };

    const isDisabled = (dayValue) => (min && dayValue < min) || (max && dayValue > max);

    return (
        <PopoverPrimitive.Root
            open={open}
            onOpenChange={(next) => {
                if (next) {
                    setView(selected ?? today);
                }
                setOpen(next);
            }}
        >
            <PopoverPrimitive.Trigger asChild>
                <button
                    type="button"
                    id={id}
                    aria-label={ariaLabel}
                    data-slot="english-date-picker"
                    data-value={value || ''}
                    lang="en"
                    dir="ltr"
                    className={cn(
                        'flex h-11 w-full min-w-0 items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3.5 text-start text-base font-normal tabular-nums shadow-xs outline-none transition-[color,box-shadow] dark:bg-input/30',
                        'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        className,
                    )}
                >
                    <span className={selected ? 'text-foreground' : 'text-muted-foreground'}>{selected ? DISPLAY_FORMAT.format(utc(selected.year, selected.month, selected.day)) : labels.placeholder}</span>
                    <CalendarDays className="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                </button>
            </PopoverPrimitive.Trigger>
            <PopoverPrimitive.Portal>
                <PopoverPrimitive.Content
                    align="start"
                    sideOffset={6}
                    lang="en"
                    dir="ltr"
                    className="z-50 w-72 rounded-lg border border-border bg-popover p-3 text-popover-foreground shadow-md outline-none"
                >
                    <div className="mb-2 flex items-center justify-between">
                        <button type="button" onClick={() => shiftMonth(-1)} className="flex size-8 items-center justify-center rounded-md hover:bg-muted" aria-label={labels.previous_month}>
                            <ChevronLeft className="size-4" aria-hidden="true" />
                        </button>
                        <span className="text-sm font-semibold" aria-live="polite">{MONTH_FORMAT.format(utc(view.year, view.month))}</span>
                        <button type="button" onClick={() => shiftMonth(1)} className="flex size-8 items-center justify-center rounded-md hover:bg-muted" aria-label={labels.next_month}>
                            <ChevronRight className="size-4" aria-hidden="true" />
                        </button>
                    </div>
                    <div className="grid grid-cols-7 gap-1 text-center text-xs font-medium text-muted-foreground" aria-hidden="true">
                        {WEEKDAYS.map((weekday) => <span key={weekday}>{weekday}</span>)}
                    </div>
                    <div className="mt-1 grid grid-cols-7 gap-1" role="grid">
                        {cells.map((day, index) => {
                            if (day === null) {
                                return <span key={`blank-${index}`} />;
                            }
                            const dayValue = toValue(view.year, view.month, day);
                            const isSelected = dayValue === value;
                            const isToday = today && dayValue === toValue(today.year, today.month, today.day);

                            return (
                                <button
                                    key={dayValue}
                                    type="button"
                                    disabled={isDisabled(dayValue)}
                                    aria-pressed={isSelected}
                                    aria-label={FULL_FORMAT.format(utc(view.year, view.month, day))}
                                    onClick={() => {
                                        onChange(dayValue);
                                        setOpen(false);
                                    }}
                                    className={cn(
                                        'flex h-8 items-center justify-center rounded-md text-sm tabular-nums transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-35',
                                        isToday && !isSelected && 'font-bold text-primary',
                                        isSelected && 'bg-primary text-primary-foreground hover:bg-primary',
                                    )}
                                >
                                    {day}
                                </button>
                            );
                        })}
                    </div>
                </PopoverPrimitive.Content>
            </PopoverPrimitive.Portal>
        </PopoverPrimitive.Root>
    );
}
