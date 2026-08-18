import { Map, Table2 } from 'lucide-react';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Table/Map switch. Two real buttons with `aria-pressed` rather than a tab
 * widget, since the two views are alternate renderings of the same records
 * rather than separate panels of content.
 */
export function ViewSwitch({ view, onChange }) {
    const { common } = useI18n();

    const options = [
        { value: 'table', label: common.view_table, icon: Table2 },
        { value: 'map', label: common.view_map, icon: Map },
    ];

    return (
        <div role="group" aria-label={common.view_switch_label} className="inline-flex rounded-md border border-border p-0.5">
            {options.map((option) => {
                const Icon = option.icon;
                const isActive = view === option.value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        aria-pressed={isActive}
                        onClick={() => onChange(option.value)}
                        className={`inline-flex items-center gap-1.5 rounded px-3 py-1.5 text-sm font-semibold transition-colors ${
                            isActive ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        <Icon className="size-3.5" aria-hidden="true" />
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
