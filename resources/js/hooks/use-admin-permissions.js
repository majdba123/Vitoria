import { useCallback } from 'react';
import { usePage } from '@inertiajs/react';

/**
 * The signed-in administrator's effective back-office permissions, shared by
 * HandleInertiaRequests. This only decides what the UI *shows* — every page,
 * API call and action is authorized again on the server, so a hidden button
 * is a convenience, never the security boundary.
 */
export function useAdminPermissions() {
    const { props } = usePage();
    const permissions = props.auth?.permissions ?? [];
    const isSuperAdmin = props.auth?.is_super_admin === true;

    const can = useCallback(
        (permission) => {
            if (!permission || permission === '*') return true;
            if (isSuperAdmin) return true;
            return permissions.includes(permission);
        },
        [permissions, isSuperAdmin],
    );

    return { can, permissions, isSuperAdmin };
}

/**
 * Translated label for a permission key such as `orders.view`. Labels are
 * nested by prefix in lang/{locale}/admin.php (`rbac_permission_labels`), so
 * the key itself is the fallback when no translation exists.
 */
export function permissionLabel(labels, key) {
    const [prefix, action] = key.split('.');
    return labels?.[prefix]?.[action] ?? key;
}
