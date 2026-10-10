import { PoweredBy } from '@/Components/shared/PoweredBy';
import { useI18n } from '@/hooks/use-i18n';

export function DashboardFooter() {
    const { footer } = useI18n();

    return (
        <footer className="flex flex-col items-center justify-between gap-1 border-t border-border/70 px-4 py-4 text-center sm:flex-row sm:px-6 sm:text-start lg:px-8">
            <p className="text-xs text-muted-foreground">&copy; {new Date().getFullYear()} Vetora. {footer.rights}</p>
            <PoweredBy />
        </footer>
    );
}
