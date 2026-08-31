import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField, SelectField, FileField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function CategoriesEdit({ categoryId }) {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [form, setForm] = useState({ name: '', type: '', commission: '0', logo: null });
    const [preview, setPreview] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/admin/categories/${categoryId}`, { silent: true }).then((res) => {
            const category = res.data.data;
            setForm({ name: category.name ?? '', type: category.type ?? '', commission: String(category.commission ?? 0), logo: null });
            setPreview(category.image_url ?? null);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [categoryId]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        const payload = { ...form };
        if (!payload.logo) delete payload.logo;
        try {
            await submit('put', `/api/admin/categories/${categoryId}`, payload, { isMultipart: true });
            router.visit(route('admin.categories.index'));
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading}>
                <Skeleton className="h-64 w-full max-w-2xl" />
            </AdminLayout>
        );
    }

    if (status === 'error') {
        return (
            <AdminLayout title={admin.categories}>
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">{admin.js_failed_load_category}</p>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title={admin.edit_category_title}>
            <PageHeader breadcrumb={[{ label: admin.categories, href: route('admin.categories.index') }, { label: common.edit }]} title={admin.edit_category_title} />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <TextField id="name" label={admin.category_name} required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} error={errors.name} />

                        <SelectField
                            id="type"
                            label={admin.category_type_label}
                            required
                            value={form.type}
                            onValueChange={(value) => setForm({ ...form, type: value })}
                            options={[
                                { value: 'agriculture', label: admin.type_agriculture },
                                { value: 'veterinary', label: admin.type_veterinary },
                            ]}
                            error={errors.type}
                        />

                        <TextField
                            id="commission"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            label={admin.category_commission}
                            value={form.commission}
                            onChange={(e) => setForm({ ...form, commission: e.target.value })}
                            error={errors.commission}
                        />

                        <FileField
                            id="logo"
                            label={admin.category_logo}
                            hint={admin.category_logo_edit_hint}
                            preview={preview}
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                setForm({ ...form, logo: file });
                                if (file) setPreview(URL.createObjectURL(file));
                            }}
                            error={errors.logo}
                        />

                        <div className="flex gap-2 pt-2">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.categories.index'))}>
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
        </AdminLayout>
    );
}
