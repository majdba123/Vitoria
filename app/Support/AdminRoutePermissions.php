<?php

namespace App\Support;

/**
 * Route-name → admin permission map, enforced for every back-office route by
 * EnsureAdminPermission (alias `admin.permission`).
 *
 * Deny by default: an admin route whose name is not listed here is refused
 * with 403 for everyone except a super administrator, and
 * AdminRoutePermissionCoverageTest fails the build when a newly added admin
 * route is missing from this map. Entries mapped to OPEN are reachable by any
 * authenticated admin (reference-data reads, the /admin redirect).
 */
final class AdminRoutePermissions
{
    public const OPEN = '*';

    /**
     * @var array<string, string>
     */
    public const MAP = [
        // ── Web (Inertia page shells) ────────────────────────────────────
        'admin.' => self::OPEN,
        'admin.dashboard' => 'dashboard.view',
        'admin.financials.index' => 'financials.view',
        'admin.vendors.index' => 'vendors.view',
        'admin.vendors.show' => 'vendors.view',
        'admin.vendors.create' => 'vendors.create',
        'admin.vendors.edit' => 'vendors.update',
        'admin.vendors.commission' => 'financials.view',
        'admin.syndicates.index' => 'syndicates.view',
        'admin.syndicates.show' => 'syndicates.view',
        'admin.syndicates.create' => 'syndicates.manage',
        'admin.syndicates.edit' => 'syndicates.manage',
        'admin.users.index' => 'users.view',
        'admin.users.show' => 'users.view',
        'admin.users.create' => 'users.create',
        'admin.users.edit' => 'users.update',
        'admin.customers.index' => 'users.view',
        'admin.employees.index' => 'users.view',
        'admin.roles.index' => 'roles.view',
        'admin.roles.create' => 'roles.manage',
        'admin.roles.edit' => 'roles.manage',
        'admin.audit-logs.index' => 'audit_logs.view',
        'admin.products.index' => 'products.view',
        'admin.products.show' => 'products.view',
        'admin.products.reviews' => 'products.view',
        'admin.products.create' => 'products.manage',
        'admin.products.edit' => 'products.manage',
        'admin.products.reviews.destroy' => 'products.moderate',
        'admin.categories.index' => self::OPEN,
        'admin.categories.show' => self::OPEN,
        'admin.categories.create' => 'catalog.manage',
        'admin.categories.edit' => 'catalog.manage',
        'admin.subcategories.index' => self::OPEN,
        'admin.subcategories.show' => self::OPEN,
        'admin.subcategories.create' => 'catalog.manage',
        'admin.subcategories.edit' => 'catalog.manage',
        'admin.cities.index' => self::OPEN,
        'admin.cities.show' => self::OPEN,
        'admin.cities.create' => 'catalog.manage',
        'admin.cities.edit' => 'catalog.manage',
        'admin.orders.index' => 'orders.view',
        'admin.orders.show' => 'orders.view',
        'admin.coupons.index' => 'coupons.view',
        'admin.discounts.index' => 'coupons.view',
        'admin.notifications.index' => 'notifications.view',
        'admin.notifications.send' => 'notifications.send',
        'admin.contact-messages.index' => 'contact_messages.view',
        'admin.about-us.edit' => 'content.view',
        'admin.pages.index' => 'content.view',
        'admin.banners.index' => 'content.view',
        'admin.shipments.index' => 'import_shipments.view',
        'admin.shipments.show' => 'import_shipments.view',
        'admin.shipments.create' => 'import_shipments.manage',
        'admin.shipments.edit' => 'import_shipments.manage',
        'admin.ports.index' => 'import_shipments.view',

        // ── API: dashboard, reports, exports ─────────────────────────────
        'api.admin.dashboard.overview' => 'dashboard.view',
        'api.admin.dashboard.vendor-category-stats' => 'dashboard.view',
        'api.admin.financials.summary' => 'financials.view',
        'api.admin.reports.sales' => 'reports.view',
        'api.admin.reports.vendors' => 'reports.view',
        'api.admin.reports.products' => 'reports.view',
        'api.admin.exports.orders' => 'reports.export',
        'api.admin.exports.products' => 'reports.export',
        'api.admin.exports.vendors' => 'reports.export',

        // ── API: vendors, vendor finance, vendor documents ───────────────
        'api.admin.vendors.index' => 'vendors.view',
        'api.admin.vendors.show' => 'vendors.view',
        'api.admin.vendors.map' => 'vendors.view',
        'api.admin.vendors.store' => 'vendors.create',
        'api.admin.vendors.update' => 'vendors.update',
        'api.admin.vendors.destroy' => 'vendors.delete',
        'api.admin.vendors.approve' => 'vendors.approve',
        'api.admin.vendors.toggle-active' => 'vendors.approve',
        'api.admin.vendors.commercial-register' => 'vendors.view',
        'api.admin.vendors.staff.index' => 'vendors.view',
        'api.admin.vendors.analytics.overview' => 'vendors.view',
        'api.admin.vendors.analytics.products' => 'vendors.view',
        'api.admin.vendors.analytics.orders' => 'vendors.view',
        'api.admin.vendors.analytics.returns' => 'vendors.view',
        'api.admin.vendors.analytics.activity' => 'vendors.view',
        'api.admin.vendors.analytics.export-summary' => 'reports.export',
        'api.admin.vendors.report' => 'reports.export',
        'api.admin.vendors.commission-stats' => 'financials.view',
        'api.admin.vendors.ledger.index' => 'financials.view',
        'api.admin.vendors.ledger.summary' => 'financials.view',
        'api.admin.vendors.settlements.index' => 'financials.view',
        'api.admin.vendors.commission-paid' => 'financials.manage',
        'api.admin.vendors.ledger.adjust' => 'financials.manage',
        'api.admin.vendors.settlements.store' => 'financials.manage',
        'api.admin.vendor-documents.index' => 'vendor_documents.view',
        'api.admin.vendor-documents.show' => 'vendor_documents.view',
        'api.admin.vendor-documents.download' => 'vendor_documents.view',
        'api.admin.vendor-documents.review' => 'vendor_documents.review',
        'api.admin.vendor-documents.suspend' => 'vendor_documents.review',

        // ── API: syndicates ──────────────────────────────────────────────
        'api.admin.syndicates.index' => 'syndicates.view',
        'api.admin.syndicates.show' => 'syndicates.view',
        'api.admin.syndicates.store' => 'syndicates.manage',
        'api.admin.syndicates.update' => 'syndicates.manage',
        'api.admin.syndicates.destroy' => 'syndicates.manage',
        'api.admin.syndicates.toggle-active' => 'syndicates.manage',

        // ── API: users, roles ────────────────────────────────────────────
        'api.admin.users.index' => 'users.view',
        'api.admin.users.show' => 'users.view',
        'api.admin.users.favourites' => 'users.view',
        'api.admin.users.permissions' => 'users.view',
        'api.admin.users.store' => 'users.create',
        'api.admin.users.update' => 'users.update',
        'api.admin.users.employee-roles' => 'users.update',
        'api.admin.users.destroy' => 'users.delete',
        'api.admin.users.admin-roles' => 'roles.assign',
        'api.admin.roles.index' => 'roles.view',
        'api.admin.roles.show' => 'roles.view',
        'api.admin.roles.permissions' => 'roles.view',
        'api.admin.roles.store' => 'roles.manage',
        'api.admin.roles.update' => 'roles.manage',
        'api.admin.roles.destroy' => 'roles.manage',

        // ── API: products, product documents ─────────────────────────────
        'api.admin.products.index' => 'products.view',
        'api.admin.products.show' => 'products.view',
        'api.admin.products.photos.index' => 'products.view',
        'api.admin.products.reviews.index' => 'products.view',
        'api.admin.products.store' => 'products.manage',
        'api.admin.products.store-basic' => 'products.manage',
        'api.admin.products.store-agriculture' => 'products.manage',
        'api.admin.products.store-veterinary' => 'products.manage',
        'api.admin.products.update' => 'products.manage',
        'api.admin.products.destroy' => 'products.manage',
        'api.admin.products.import' => 'products.manage',
        'api.admin.products.import-template' => 'products.manage',
        'api.admin.products.photos.store' => 'products.manage',
        'api.admin.products.photos.update' => 'products.manage',
        'api.admin.products.photos.destroy' => 'products.manage',
        'api.admin.products.photos.bulk-destroy' => 'products.manage',
        'api.admin.products.set-primary-photo' => 'products.manage',
        'api.admin.products.update-status' => 'products.moderate',
        'api.admin.products.toggle-active' => 'products.moderate',
        'api.admin.product-documents.index' => 'product_documents.view',
        'api.admin.product-documents.show' => 'product_documents.view',
        'api.admin.product-documents.download' => 'product_documents.view',
        'api.admin.product-documents.review' => 'product_documents.review',
        'api.admin.product-documents.disable' => 'product_documents.review',

        // ── API: reference data (reads open to every admin) ──────────────
        'api.admin.categories.index' => self::OPEN,
        'api.admin.categories.show' => self::OPEN,
        'api.admin.categories.store' => 'catalog.manage',
        'api.admin.categories.update' => 'catalog.manage',
        'api.admin.categories.destroy' => 'catalog.manage',
        'api.admin.categories.import' => 'catalog.manage',
        'api.admin.categories.import-template' => 'catalog.manage',
        'api.admin.subcategories.index' => self::OPEN,
        'api.admin.subcategories.show' => self::OPEN,
        'api.admin.subcategories.store' => 'catalog.manage',
        'api.admin.subcategories.update' => 'catalog.manage',
        'api.admin.subcategories.destroy' => 'catalog.manage',
        'api.admin.subcategories.import' => 'catalog.manage',
        'api.admin.subcategories.import-template' => 'catalog.manage',
        'api.admin.cities.index' => self::OPEN,
        'api.admin.cities.show' => self::OPEN,
        'api.admin.cities.store' => 'catalog.manage',
        'api.admin.cities.update' => 'catalog.manage',
        'api.admin.cities.destroy' => 'catalog.manage',
        'api.admin.cities.import' => 'catalog.manage',
        'api.admin.cities.import-template' => 'catalog.manage',

        // ── API: orders, returns, refunds, invoices ──────────────────────
        'api.admin.orders.index' => 'orders.view',
        'api.admin.orders.show' => 'orders.view',
        'api.admin.orders.complete' => 'orders.complete',
        'api.admin.returns.index' => 'returns.view',
        'api.admin.returns.show' => 'returns.view',
        'api.admin.returns.status' => 'returns.review',
        'api.admin.returns.refund' => 'refunds.process',
        'api.admin.refunds.index' => 'refunds.view',
        'api.admin.refunds.show' => 'refunds.view',
        'api.admin.refunds.store' => 'refunds.process',
        'api.admin.refunds.complete' => 'refunds.process',
        'api.admin.refunds.cancel' => 'refunds.process',
        'api.admin.invoices.index' => 'invoices.view',
        'api.admin.invoices.show' => 'invoices.view',

        // ── API: shipments, shipping, import shipments, ports ────────────
        'api.admin.shipments.index' => 'shipments.view',
        'api.admin.shipments.show' => 'shipments.view',
        'api.admin.shipments.failed' => 'shipments.manage',
        'api.admin.shipments.returned' => 'shipments.manage',
        'api.admin.shipping.zones' => 'shipments.view',
        'api.admin.shipping.methods' => 'shipments.view',
        'api.admin.shipping.rates.update' => 'shipping.manage',
        'api.admin.import-shipments.index' => 'import_shipments.view',
        'api.admin.import-shipments.show' => 'import_shipments.view',
        'api.admin.import-shipments.options' => 'import_shipments.view',
        'api.admin.import-shipments.stats' => 'import_shipments.view',
        'api.admin.import-shipments.store' => 'import_shipments.manage',
        'api.admin.import-shipments.update' => 'import_shipments.manage',
        'api.admin.import-shipments.destroy' => 'import_shipments.manage',
        'api.admin.ports.index' => 'import_shipments.view',
        'api.admin.ports.store' => 'ports.manage',
        'api.admin.ports.update' => 'ports.manage',
        'api.admin.ports.destroy' => 'ports.manage',

        // ── API: coupons, content, notifications, contact messages ───────
        'api.admin.coupons.index' => 'coupons.view',
        'api.admin.coupons.show' => 'coupons.view',
        'api.admin.coupons.store' => 'coupons.manage',
        'api.admin.coupons.update' => 'coupons.manage',
        'api.admin.coupons.destroy' => 'coupons.manage',
        'api.admin.pages.index' => 'content.view',
        'api.admin.pages.show' => 'content.view',
        'api.admin.pages.store' => 'content.manage',
        'api.admin.pages.update' => 'content.manage',
        'api.admin.pages.destroy' => 'content.manage',
        'api.admin.banners.index' => 'content.view',
        'api.admin.banners.store' => 'content.manage',
        'api.admin.banners.update' => 'content.manage',
        'api.admin.banners.destroy' => 'content.manage',
        'api.admin.footer-settings.show' => 'content.view',
        'api.admin.footer-settings.update' => 'content.manage',
        'api.admin.notifications.send' => 'notifications.send',
        'api.admin.contact-messages.index' => 'contact_messages.view',
        'api.admin.contact-messages.show' => 'contact_messages.view',
        'api.admin.contact-messages.reply' => 'contact_messages.reply',

        // ── API: audit trail ─────────────────────────────────────────────
        'api.admin.audit-logs.index' => 'audit_logs.view',
        'api.admin.audit-logs.show' => 'audit_logs.view',
        'api.admin.audit-logs.export' => 'audit_logs.export',
    ];

    /**
     * The permission a route requires: a catalog key, OPEN, or null when the
     * route is unmapped (which the middleware treats as deny).
     */
    public static function for(?string $routeName): ?string
    {
        return $routeName === null ? null : (self::MAP[$routeName] ?? null);
    }
}
