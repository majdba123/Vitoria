import { router, usePage } from '@inertiajs/react';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { MINISTRY_SECTORS } from '@/lib/syndicate-workspace';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Switches the Ministry's sector while staying on the current page; the
 * sector lives in the URL so it survives reloads and shared links.
 */
export function SectorSwitcher({ className }) {
    const { ministry } = useI18n();
    const { props } = usePage();
    const sector = props.sector ?? 'combined';

    const select = (next) => {
        if (!next || next === sector) {
            return;
        }

        router.get(window.location.pathname, next === 'combined' ? {} : { sector: next }, { preserveScroll: true });
    };

    return (
        <ToggleGroup type="single" variant="outline" size="sm" value={sector} onValueChange={select} aria-label={ministry.sector_label} className={className}>
            {MINISTRY_SECTORS.map((key) => (
                <ToggleGroupItem key={key} value={key} className="min-h-9 px-3 text-xs font-semibold data-[state=on]:bg-primary data-[state=on]:text-primary-foreground">
                    {ministry[`sector_${key}`]}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
