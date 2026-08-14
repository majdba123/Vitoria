import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function SubcategoriesEdit({ subcategoryId }) {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [categories, setCategories] = useState([]);
    const [form, setForm] = useState({ category_id: '', name_ar: '', name_en: '' });

    useEffect(() => {
        Promise.all([
            window.axios.get('/api/admin/categories', { params: { per_page: 100 }, silent: true }),
            window.axios.get(`/api/admin/subcategories/${subcategoryId}`, { silent: true }),
        ]).then(([categoriesRes, subcategoryRes]) => {
            setCategories(categoriesRes.data?.data ?? []);
            const subcategory = subcategoryRes.data.data;
            setForm({ category_id: String(subcategory.category_id ?? ''), name_ar: subcategory.name_ar ?? '', name_en: subcategory.name_en ?? '' });
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [subcategoryId]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        try {
            await submit('put', `/api/admin/subcategories/${subcategoryId}`, form);
            router.visit(route('admin.subcategories.show', subcategoryId));
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading ?? 'Loading...'}>
                <Skeleton className="h-64 w-full max-w-2xl" />
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title="Edit subcategory">
            <PageHeader breadcrumb={[{ label: admin.subcategories_heading, href: route('admin.subcategories.index') }, { label: common.edit ?? 'Edit' }]} title="Edit subcategory" />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <SelectField
                            id="category_id"
                            label={admin.parent_category_label}
                            required
                            value={form.category_id}
                            onValueChange={(value) => setForm({ ...form, category_id: value })}
                            options={categories.map((c) => ({ value: c.id, label: `${c.name} (${c.type})` }))}
                            error={errors.category_id}
                        />
                        <TextField id="name_ar" label="Arabic name" required value={form.name_ar} onChange={(e) => setForm({ ...form, name_ar: e.target.value })} error={errors.name_ar} dir="rtl" />
                        <TextField id="name_en" label="English name" required value={form.name_en} onChange={(e) => setForm({ ...form, name_en: e.target.value })} error={errors.name_en} />

                        <div className="flex gap-2 pt-2">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.subcategories.index'))}>
                                {common.cancel ?? 'Cancel'}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {common.save_changes ?? 'Save changes'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
