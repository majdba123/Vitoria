<?php

namespace App\Support;

/**
 * The platform back-office permission catalog.
 *
 * Every key here maps to a concrete admin route or business action in
 * routes/api_admin.php / routes/web.php (admin group). Keys are stable,
 * machine-readable identifiers stored in `permissions.key` and granted to
 * admin-scoped roles via `role_permissions` — the same tables vendor and
 * employee roles already use. A key that also exists for another scope
 * (e.g. `orders.view`) is the same permission row; the role's `scope` is what
 * keeps a vendor or employee role from ever being honoured as an admin role.
 *
 * Labels live in lang/{en,ar}/admin.php under `rbac_permission_labels` and
 * `rbac_groups` so the role editor stays bilingual.
 */
final class AdminPermissions
{
    /**
     * Permissions grouped by back-office module, in display order.
     *
     * @var array<string, list<string>>
     */
    public const GROUPS = [
        'dashboard' => ['dashboard.view'],
        'reports' => ['reports.view', 'reports.export'],
        'financials' => ['financials.view', 'financials.manage'],
        'vendors' => ['vendors.view', 'vendors.create', 'vendors.update', 'vendors.approve', 'vendors.delete'],
        'vendor_documents' => ['vendor_documents.view', 'vendor_documents.review'],
        'syndicates' => ['syndicates.view', 'syndicates.manage'],
        'users' => ['users.view', 'users.create', 'users.update', 'users.delete'],
        'roles' => ['roles.view', 'roles.manage', 'roles.assign'],
        'products' => ['products.view', 'products.manage', 'products.moderate'],
        'product_documents' => ['product_documents.view', 'product_documents.review'],
        'catalog' => ['catalog.manage'],
        'orders' => ['orders.view', 'orders.complete'],
        'returns' => ['returns.view', 'returns.review'],
        'refunds' => ['refunds.view', 'refunds.process'],
        'invoices' => ['invoices.view'],
        'shipments' => ['shipments.view', 'shipments.manage', 'shipping.manage'],
        'import_shipments' => ['import_shipments.view', 'import_shipments.manage', 'ports.manage'],
        'coupons' => ['coupons.view', 'coupons.manage'],
        'content' => ['content.view', 'content.manage'],
        'notifications' => ['notifications.view', 'notifications.send'],
        'contact_messages' => ['contact_messages.view', 'contact_messages.reply'],
        'audit_logs' => ['audit_logs.view', 'audit_logs.export'],
    ];

    /**
     * Capabilities that can change who may do what, move money, or expose
     * the audit trail. The role editor asks for explicit confirmation before
     * granting any of these.
     *
     * @var list<string>
     */
    public const SENSITIVE = [
        'roles.manage',
        'roles.assign',
        'users.delete',
        'financials.manage',
        'refunds.process',
        'vendors.delete',
        'audit_logs.export',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_merge(...array_values(self::GROUPS)));
    }

    public static function exists(string $key): bool
    {
        return in_array($key, self::all(), true);
    }

    public static function groupOf(string $key): ?string
    {
        foreach (self::GROUPS as $group => $keys) {
            if (in_array($key, $keys, true)) {
                return $group;
            }
        }

        return null;
    }
}
