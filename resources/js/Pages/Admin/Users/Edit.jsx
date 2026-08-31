import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function UsersEdit({ userId }) {
    const { admin, common } = useI18n();

    const TYPE_OPTIONS = [
        { value: '0', label: admin.user_type_labels?.[0] },
        { value: '1', label: admin.user_type_labels?.[1] },
        { value: '2', label: admin.user_type_labels?.[2] },
        { value: '3', label: admin.user_type_labels?.[3] },
        { value: '4', label: admin.user_type_labels?.[4] },
    ];

    const EMPLOYEE_ROLE_OPTIONS = [
        { key: 'catalog_moderator', label: admin.employee_role_catalog_moderator, hint: admin.employee_role_catalog_moderator_hint },
        { key: 'order_reviewer', label: admin.employee_role_order_reviewer, hint: admin.employee_role_order_reviewer_hint },
    ];
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [form, setForm] = useState({ name: '', phone_number: '', national_id: '', email: '', password: '', type: '0' });
    const [successMessage, setSuccessMessage] = useState(null);
    const [employeeRoleKeys, setEmployeeRoleKeys] = useState([]);
    const [rolesSaving, setRolesSaving] = useState(false);
    const [rolesMessage, setRolesMessage] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/users/${userId}`, { silent: true }).then((res) => {
            const u = res.data.data;
            setForm({ name: u.name ?? '', phone_number: u.phone_number ?? '', national_id: u.national_id ?? '', email: u.email ?? '', password: '', type: String(u.type ?? 0) });
            setEmployeeRoleKeys((u.employee_roles ?? []).map((r) => r.key));
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [userId]);

    const toggleRole = (key) => {
        setEmployeeRoleKeys((keys) => (keys.includes(key) ? keys.filter((k) => k !== key) : [...keys, key]));
    };

    const saveEmployeeRoles = async () => {
        setRolesSaving(true);
        setRolesMessage(null);
        try {
            await window.axios.patch(`/api/admin/users/${userId}/employee-roles`, { role_keys: employeeRoleKeys }, { silent: true });
            setRolesMessage({ tone: 'success', text: admin.employee_roles_updated });
        } catch (err) {
            setRolesMessage({ tone: 'error', text: err.response?.data?.message || admin.employee_roles_update_failed });
        } finally {
            setRolesSaving(false);
        }
    };

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const payload = { ...form, type: parseInt(form.type, 10) };
        if (!payload.email) delete payload.email;
        if (!payload.password) delete payload.password;
        try {
            await submit('put', `/api/admin/users/${userId}`, payload);
            setSuccessMessage(admin.user_updated_success);
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading}>
                <Skeleton className="h-96 w-full max-w-2xl" />
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title={admin.edit_user_title}>
            <PageHeader breadcrumb={[{ label: admin.users, href: route('admin.users.index') }, { label: common.edit }]} title={admin.edit_user_title} copy={admin.edit_user_copy} />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                    {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="name" label={admin.full_name_label} required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                            <TextField id="phone_number" label={admin.phone_number_label} type="tel" required value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                            <TextField id="national_id" label={admin.th_national_id} required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                            <TextField id="email" label={admin.email_label} type="email" placeholder={admin.email_optional_placeholder} value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                            <TextField id="password" label={admin.new_password_label} type="password" placeholder={admin.password_optional_hint} value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                            <SelectField id="type" label={admin.user_type_label} value={form.type} onValueChange={set('type')} options={TYPE_OPTIONS} error={errors.type} />
                        </div>

                        <div className="flex gap-2 border-t border-border pt-5">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.users.index'))}>
                                {common.cancel}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {common.save_changes}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            {form.type === '4' && (
                <Card className="mt-5 max-w-2xl border-border/80 shadow-none">
                    <CardContent className="p-5 sm:p-6">
                        <h2 className="text-sm font-bold text-foreground">{admin.employee_roles_heading}</h2>
                        <p className="mt-1 text-xs text-muted-foreground">{admin.employee_roles_hint}</p>

                        {rolesMessage && (
                            <p className={`mt-3 rounded-md border px-4 py-2.5 text-sm font-medium ${rolesMessage.tone === 'success' ? 'border-[var(--color-success-200)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]' : 'border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]'}`}>
                                {rolesMessage.text}
                            </p>
                        )}

                        <div className="mt-4 space-y-3">
                            {EMPLOYEE_ROLE_OPTIONS.map((role) => (
                                <label key={role.key} className="flex items-start gap-3 rounded-md border border-border px-4 py-3">
                                    <input
                                        type="checkbox"
                                        className="mt-0.5 h-4 w-4"
                                        checked={employeeRoleKeys.includes(role.key)}
                                        onChange={() => toggleRole(role.key)}
                                    />
                                    <span>
                                        <span className="block text-sm font-medium text-foreground">{role.label}</span>
                                        <span className="block text-xs text-muted-foreground">{role.hint}</span>
                                    </span>
                                </label>
                            ))}
                        </div>

                        <Button type="button" className="mt-4" onClick={saveEmployeeRoles} disabled={rolesSaving}>
                            {rolesSaving && <Loader2 className="size-4 animate-spin" />}
                            {admin.save_roles_btn}
                        </Button>
                    </CardContent>
                </Card>
            )}
        </AdminLayout>
    );
}
