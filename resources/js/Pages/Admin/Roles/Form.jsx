import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Loader2, ShieldAlert } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField } from '@/Components/admin/form/FormField';
import { StatusMessage } from '@/Components/admin/StatusMessage';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Card, CardContent } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';
import { permissionLabel } from '@/hooks/use-admin-permissions';

/**
 * Create / edit an administrator role. The permission catalog comes from the
 * server, which also marks which permissions the signed-in administrator may
 * grant — the rest are shown but disabled, and the server rejects them anyway.
 */
export default function RoleForm({ roleId }) {
    const { admin, common } = useI18n();
    const { url } = usePage();
    const isEdit = roleId !== null && roleId !== undefined;
    const cloneId = isEdit ? null : new URLSearchParams(url.split('?')[1] ?? '').get('clone');

    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [groups, setGroups] = useState([]);
    const [form, setForm] = useState({ key: '', name_en: '', name_ar: '', permissions: [] });
    const [search, setSearch] = useState('');
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [successMessage, setSuccessMessage] = useState(null);

    const load = () => {
        setStatus('loading');
        const sourceId = isEdit ? roleId : cloneId;
        Promise.all([
            window.axios.get('/api/admin/roles/permissions', { silent: true }),
            sourceId ? window.axios.get(`/api/admin/roles/${sourceId}`, { silent: true }) : Promise.resolve(null),
        ])
            .then(([catalog, source]) => {
                setGroups(catalog.data?.data?.groups ?? []);
                const role = source?.data?.data;
                if (role) {
                    setForm({
                        key: isEdit ? role.key : '',
                        name_en: isEdit ? role.name_en : '',
                        name_ar: isEdit ? role.name_ar : '',
                        permissions: role.permissions ?? [],
                    });
                }
                setStatus(isEdit && role && !role.can_edit ? 'locked' : 'ready');
            })
            .catch(() => setStatus('error'));
    };

    useEffect(load, [roleId, cloneId]); // eslint-disable-line react-hooks/exhaustive-deps

    const permissionMeta = useMemo(() => {
        const meta = {};
        groups.forEach((group) => group.permissions.forEach((permission) => (meta[permission.key] = permission)));
        return meta;
    }, [groups]);

    const totalPermissions = Object.keys(permissionMeta).length;
    const labelFor = (key) => permissionLabel(admin.rbac_permission_labels, key);
    const selected = new Set(form.permissions);
    const query = search.trim().toLowerCase();

    const visibleGroups = groups
        .map((group) => ({
            ...group,
            permissions: group.permissions.filter(
                (permission) => !query || permission.key.includes(query) || labelFor(permission.key).toLowerCase().includes(query),
            ),
        }))
        .filter((group) => group.permissions.length > 0);

    const setPermissions = (updater) => setForm((current) => ({ ...current, permissions: updater(new Set(current.permissions)) }));

    const toggle = (key, checked) =>
        setPermissions((keys) => {
            checked ? keys.add(key) : keys.delete(key);
            return [...keys];
        });

    const setGroup = (group, checked) =>
        setPermissions((keys) => {
            group.permissions.filter((permission) => permission.grantable).forEach((permission) => (checked ? keys.add(permission.key) : keys.delete(permission.key)));
            return [...keys];
        });

    const sensitiveSelected = form.permissions.filter((key) => permissionMeta[key]?.sensitive);

    const save = async () => {
        setConfirmOpen(false);
        setSuccessMessage(null);
        const payload = isEdit
            ? { name_en: form.name_en, name_ar: form.name_ar, permissions: form.permissions }
            : form;

        try {
            await submit(isEdit ? 'put' : 'post', isEdit ? `/api/admin/roles/${roleId}` : '/api/admin/roles', payload);
            if (isEdit) {
                setSuccessMessage(admin.rbac_role_updated);
            } else {
                router.visit(route('admin.roles.index'));
            }
        } catch {
            // field and general errors are surfaced by useAdminForm
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        if (sensitiveSelected.length > 0) {
            setConfirmOpen(true);
            return;
        }
        save();
    };

    const title = isEdit ? admin.rbac_edit_role : admin.rbac_new_role;
    const breadcrumb = [{ label: admin.rbac_roles, href: route('admin.roles.index') }, { label: title }];

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading}>
                <Skeleton className="h-96 w-full max-w-4xl" />
            </AdminLayout>
        );
    }

    if (status === 'error' || status === 'locked') {
        return (
            <AdminLayout title={title}>
                <PageHeader breadcrumb={breadcrumb} title={title} />
                <Card className="max-w-2xl border-border/80 shadow-none">
                    <CardContent className="flex flex-col items-start gap-4 p-5 sm:p-6">
                        <StatusMessage tone="error">{status === 'locked' ? admin.rbac_locked_role : admin.rbac_load_error}</StatusMessage>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => router.visit(route('admin.roles.index'))}>
                                {common.cancel}
                            </Button>
                            {status === 'error' && <Button onClick={load}>{common.retry}</Button>}
                        </div>
                    </CardContent>
                </Card>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title={title}>
            <PageHeader breadcrumb={breadcrumb} title={title} copy={admin.rbac_roles_copy} />

            <form onSubmit={handleSubmit} className="max-w-4xl space-y-5">
                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-4 p-5 sm:p-6">
                        {generalError && <StatusMessage tone="error">{generalError}</StatusMessage>}
                        {successMessage && <StatusMessage tone="success">{successMessage}</StatusMessage>}

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <TextField
                                id="key"
                                label={admin.rbac_role_key}
                                required={!isEdit}
                                disabled={isEdit}
                                hint={admin.rbac_role_key_hint}
                                className="font-mono"
                                value={form.key}
                                onChange={(e) => setForm((f) => ({ ...f, key: e.target.value }))}
                                error={errors.key}
                            />
                            <TextField id="name_en" label={admin.rbac_role_name_en} required value={form.name_en} onChange={(e) => setForm((f) => ({ ...f, name_en: e.target.value }))} error={errors.name_en} />
                            <TextField id="name_ar" label={admin.rbac_role_name_ar} required dir="rtl" value={form.name_ar} onChange={(e) => setForm((f) => ({ ...f, name_ar: e.target.value }))} error={errors.name_ar} />
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-4 p-5 sm:p-6">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-sm font-bold text-foreground">{admin.rbac_th_permissions}</h2>
                                <p className="mt-1 text-xs text-muted-foreground" aria-live="polite">
                                    {(admin.rbac_permissions_selected ?? '').replace(':count', form.permissions.length).replace(':total', totalPermissions)}
                                </p>
                            </div>
                            <Input
                                type="search"
                                className="sm:max-w-xs"
                                aria-label={admin.rbac_search_permissions}
                                placeholder={admin.rbac_search_permissions}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        {(errors.permissions || errors['permissions.0']) && (
                            <StatusMessage tone="error">{errors.permissions || errors['permissions.0']}</StatusMessage>
                        )}

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            {visibleGroups.map((group) => (
                                <fieldset key={group.key} className="rounded-lg border border-border p-4">
                                    <div className="mb-3 flex items-center justify-between gap-2">
                                        <legend className="text-sm font-semibold text-foreground">{admin.rbac_groups?.[group.key] ?? group.key}</legend>
                                        <div className="flex gap-1">
                                            <Button type="button" variant="ghost" size="sm" onClick={() => setGroup(group, true)}>
                                                {admin.rbac_select_group}
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => setGroup(group, false)}>
                                                {admin.rbac_clear_group}
                                            </Button>
                                        </div>
                                    </div>
                                    <ul className="space-y-2">
                                        {group.permissions.map((permission) => {
                                            const id = `permission-${permission.key.replace('.', '-')}`;
                                            return (
                                                <li key={permission.key} className="flex items-start gap-3">
                                                    <Checkbox
                                                        id={id}
                                                        className="mt-0.5"
                                                        checked={selected.has(permission.key)}
                                                        disabled={!permission.grantable}
                                                        aria-describedby={!permission.grantable ? `${id}-hint` : undefined}
                                                        onCheckedChange={(checked) => toggle(permission.key, checked === true)}
                                                    />
                                                    <label htmlFor={id} className="min-w-0 flex-1 text-sm">
                                                        <span className="flex flex-wrap items-center gap-2 font-medium text-foreground">
                                                            {labelFor(permission.key)}
                                                            {permission.sensitive && <StatusBadge tone="warning">{admin.rbac_sensitive}</StatusBadge>}
                                                        </span>
                                                        <span className="block font-mono text-xs text-muted-foreground">{permission.key}</span>
                                                        {!permission.grantable && (
                                                            <span id={`${id}-hint`} className="block text-xs text-muted-foreground">
                                                                {admin.rbac_not_grantable}
                                                            </span>
                                                        )}
                                                    </label>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </fieldset>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <div className="flex gap-2">
                    <Button type="button" variant="outline" onClick={() => router.visit(route('admin.roles.index'))}>
                        {common.cancel}
                    </Button>
                    <Button type="submit" disabled={isSubmitting}>
                        {isSubmitting && <Loader2 className="size-4 animate-spin" aria-hidden="true" />}
                        {admin.rbac_save_role}
                    </Button>
                </div>
            </form>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ShieldAlert className="size-5 text-[var(--color-danger-strong)]" aria-hidden="true" />
                            {admin.rbac_sensitive_confirm_title}
                        </DialogTitle>
                        <DialogDescription>{admin.rbac_sensitive_confirm_copy}</DialogDescription>
                    </DialogHeader>
                    <ul className="list-disc space-y-1 ps-5 text-sm text-foreground">
                        {sensitiveSelected.map((key) => (
                            <li key={key}>{labelFor(key)}</li>
                        ))}
                    </ul>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setConfirmOpen(false)}>
                            {common.cancel}
                        </Button>
                        <Button type="button" onClick={save} disabled={isSubmitting}>
                            {admin.rbac_sensitive_confirm_btn}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
