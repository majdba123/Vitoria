import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

export function PoweredBy({ className }) {
    const { footer } = useI18n();

    return (
        <p className={cn('text-xs text-muted-foreground', className)}>
            {footer.powered_by ?? 'System is powered by'}{' '}
            <a
                href="https://hexaterminal.com"
                target="_blank"
                rel="noopener noreferrer"
                className="font-semibold text-foreground underline-offset-4 transition-colors hover:text-primary hover:underline"
            >
                Hexaterminal
            </a>
        </p>
    );
}
