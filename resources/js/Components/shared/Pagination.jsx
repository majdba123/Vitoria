import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

export function Pagination({ meta, onPrev, onNext }) {
    const { nav } = useI18n();
    if (!meta) return null;

    return (
        <div className="flex flex-col items-center gap-3 border-t border-border px-4 py-3 sm:flex-row sm:justify-between">
            <p className="text-xs text-muted-foreground">
                {nav.page} {meta.current_page} {nav.of} {meta.last_page} · {meta.total}
            </p>
            <div className="flex gap-2">
                <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={onPrev}>
                    <ChevronLeft className="size-3.5 rtl:rotate-180" />
                    {nav.prev}
                </Button>
                <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page} onClick={onNext}>
                    {nav.next}
                    <ChevronRight className="size-3.5 rtl:rotate-180" />
                </Button>
            </div>
        </div>
    );
}
