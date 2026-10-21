import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import { StatusMessage } from '@/Components/admin/StatusMessage';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Card, CardContent } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { permissionLabel, useAdminPermissions } from '@/hooks/use-admin-permissions';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Back-office access for one administrator account: the admin roles it holds
 * (editable when the signed-in administrator may manage it) and the effective
 * permissions those roles grant, each with the role(s) it comes from. Every
 * rule — escalation, self-assignment, last super administrator — is enforced
 * by the server; this card only reflects what it reports.
 */
export function AdminAccessCard({ userId }) {
    const { admin, common } = useI18n();
    const { can } = useAdminPermissions();
    const [status, setStatus] = useState('loading');
    const [access, setAccess] = useState(null);
    const [roles, setRoles] = useState([]);
    const [selectedIds, setSelectedIds] = useState([]);
    const [isSaving, setIsSaving] = useState(false);
    const [message, setMessage] = useState(null);

    const canListRoles = can('roles.view');

    const load = () => {
        setStatus('loading');
        Promise.all([
            window.axios.get(`/api/admin/users/${userId}/permissions`, { silent: true }),
            canListRoles ? window.axios.get('/api/admin/roles', { silent: true }) : Promise.resolve(null),
        ])
            .then(([accessResponse, rolesResponse]) => {
                const data = accessResponse.data?.data;
                setAccess(data);
                setSelectedIds((data?.roles ?? []).map((role) => role.id));
                setRoles(rolesResponse?.data?.data ?? []);
                setStatus('ready');
            })
            .catch(() => setStatus('error'));
    };

    useEffect(load, [userId, canListRoles]); // eslint-disable-line react-hooks/exhaustive-deps

    const toggle = (roleId, checked) =>
        setSelectedIds((ids) => (checked ? [...new Set([...ids, roleId])] : ids.filter((id) => id !== roleId)));

    const save = async () => {
        setIsSaving(true);
        setMessage(null);
        try {
            const response = await window.axios.put(`/api/admin/users/${userId}/admin-roles`, { role_ids: selectedIds }, { silent: true });
            const data = response.data?.data;
            setAccess((current) => ({ ...current, ...data }));
            setSelectedIds((data?.roles ?? []).map((role) => role.id));
            setMessage({ tone: 'success', text: response.data?.message || admin.rbac_roles_assigned });
        } catch (error) {
            setMessage({ tone: 'error', text: error.response?.data?.message || admin.rbac_load_error });
        } finally {
            setIsSaving(false);
        }
    };

    const canEdit = access?.can_manage && can('roles.assign') && canListRoles;
    const roleName = (key) => roles.find((role) => role.key === key)?.name ?? access?.roles?.find((role) => role.key === key)?.name ?? key;

    return (
        <Card className="mt-5 max-w-2xl border-border/80 shadow-none">
            <CardContent className="p-5 sm:p-6">
                <h2 className="text-sm font-bold text-foreground">{admin.rbac_admin_access}</h2>
                <p className="mt-1 text-xs text-muted-foreground">{admin.rbac_admin_access_copy}</p>

                {status === 'loading' && <Skeleton className="mt-4 h-32 w-full" />}

                {status === 'error' && (
                    <div className="mt-4 flex flex-col items-start gap-3">
                        <StatusMessage tone="error">{admin.rbac_load_error}</StatusMessage>
                        <Button size="sm" variant="outline" onClick={load}>{common.retry}</Button>
                    </div>
                )}

                {status === 'ready' && access && (
                    <div className="mt-4 space-y-5">
                        {message && <StatusMessage tone={message.tone}>{message.text}</StatusMessage>}

                        <fieldset>
                            <legend className="mb-2 text-sm font-semibold text-foreground">{admin.rbac_assign_roles}</legend>
                            {canEdit ? (
                                <ul className="space-y-2">
                                    {roles.map((role) => {
                                        const id = `admin-role-${role.id}`;
                                        const isSelected = selectedIds.includes(role.id);
                                        return (
                                            <li key={role.id} className="flex items-start gap-3 rounded-md border border-border px-4 py-3">
                                                <Checkbox
                                                    id={id}
                                                    className="mt-0.5"
                                                    checked={isSelected}
                                                    disabled={!role.can_assign}
                                                    onCheckedChange={(checked) => toggle(role.id, checked === true)}
                                                />
                                                <label htmlFor={id} className="min-w-0 flex-1 text-sm">
                                                    <span className="flex flex-wrap items-center gap-2 font-medium text-foreground">
                                                        {role.name}
                                                        {role.is_super_admin && <StatusBadge tone="warning">{admin.rbac_super_admin_badge}</StatusBadge>}
                                                    </span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {role.key} · {role.permissions_count} {admin.rbac_th_permissions}
                                                    </span>
                                                </label>
                                            </li>
                                        );
                                    })}
                                </ul>
                            ) : (
                                <>
                                    <p className="text-sm text-foreground">{access.roles.length ? access.roles.map((role) => role.name).join(', ') : '-'}</p>
                                    {!access.can_manage && <p className="mt-1 text-xs text-muted-foreground">{admin.rbac_cannot_manage}</p>}
                                </>
                            )}
                        </fieldset>

                        {canEdit && (
                            <Button type="button" onClick={save} disabled={isSaving}>
                                {isSaving && <Loader2 className="size-4 animate-spin" aria-hidden="true" />}
                                {admin.rbac_save_roles}
                            </Button>
                        )}

                        <section aria-labelledby="effective-permissions-heading">
                            <h3 id="effective-permissions-heading" className="mb-2 text-sm font-semibold text-foreground">
                                {admin.rbac_effective_permissions}
                            </h3>
                            {access.is_super_admin ? (
                                <StatusBadge tone="warning">{admin.rbac_super_admin_badge}</StatusBadge>
                            ) : access.permissions.length === 0 ? (
                                <p className="text-sm text-muted-foreground">{admin.rbac_no_permissions}</p>
                            ) : (
                                <ul className="divide-y divide-border rounded-md border border-border">
                                    {access.permissions.map((key) => (
                                        <li key={key} className="px-4 py-2 text-sm">
                                            <span className="font-medium text-foreground">{permissionLabel(admin.rbac_permission_labels, key)}</span>
                                            <span className="block text-xs text-muted-foreground">
                                                {(admin.rbac_granted_by ?? '').replace(':roles', (access.sources?.[key] ?? []).map(roleName).join(', '))}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
