import { Languages, Check } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';
import { usePage } from '@inertiajs/react';

export function LanguageSwitcher() {
    const { lang } = useI18n();
    const { props } = usePage();
    const current = props.locale ?? 'en';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={lang?.choose_language}
                    title={lang?.choose_language}
                >
                    <Languages className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem asChild>
                    <a href={route('locale.switch', { locale: 'ar' })} className="flex items-center justify-between gap-3">
                        {lang?.arabic}
                        {current === 'ar' && <Check className="size-4 text-primary" />}
                    </a>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <a href={route('locale.switch', { locale: 'en' })} className="flex items-center justify-between gap-3">
                        {lang?.english}
                        {current === 'en' && <Check className="size-4 text-primary" />}
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
