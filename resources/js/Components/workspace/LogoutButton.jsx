import { LogOut } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Icon-only logout control shared by every operator workspace topbar
 * (admin/vendor/syndicate/employee), so signing out is reachable the same
 * way regardless of role rather than living inside the sidebar (which
 * collapses/hides on mobile and previously made logout inconsistent between
 * roles — some had it in a dropdown, others as a bare sidebar button).
 */
export function LogoutButton() {
    const { nav } = useI18n();
    const label = nav.sign_out ?? 'Sign out';

    const handleLogout = async () => {
        // The operator workspaces (admin/vendor/syndicate/employee) render
        // through resources/js/app.jsx, which only loads bootstrap.js — it
        // never imports workspace-shell.js, so window.VetoraWorkspace does
        // not exist here (that global is only set up on the legacy Blade
        // entry, resources/js/app.js). Call window.Auth.logout directly,
        // the same client resources/js/Components/public/ProfileMenu.jsx
        // and MobileDrawer.jsx already use correctly for the public navbar.
        await window.Auth?.logout?.(route('login'));
    };

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className="size-11 text-[var(--color-danger-strong)] hover:bg-[var(--color-danger-soft)] hover:text-[var(--color-danger-strong)]"
            aria-label={label}
            title={label}
            onClick={handleLogout}
        >
            <LogOut className="size-4" />
        </Button>
    );
}
