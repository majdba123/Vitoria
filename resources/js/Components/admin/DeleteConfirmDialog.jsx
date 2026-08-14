import { Loader2 } from 'lucide-react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Shared destructive-confirm dialog for every "delete this row" action
 * across the admin CRUD pages, replacing the mix of window.confirm() and
 * one-off hand-built modals in the Blade views.
 */
export function DeleteConfirmDialog({ open, onOpenChange, title, description, isDeleting, onConfirm }) {
    const { common } = useI18n();

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>{description}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{common.cancel ?? 'Cancel'}</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm();
                        }}
                        disabled={isDeleting}
                        className="bg-[var(--color-danger-500)] text-white hover:bg-[var(--color-danger-600)] focus-visible:ring-[var(--color-danger-500)]/40"
                    >
                        {isDeleting && <Loader2 className="size-4 animate-spin" />}
                        {common.delete ?? 'Delete'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
