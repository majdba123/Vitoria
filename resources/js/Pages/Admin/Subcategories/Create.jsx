import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function SubcategoriesCreate() {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [categories, setCategories] = useState([]);
    const [form, setForm] = useState({ category_id: '', name_ar: '', name_en: '' });

    useEffect(() => {
        window.axios.get('/api/admin/categories', { params: { per_page: 100 }, silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, []);

    const handleSubmit = async (event) => {
        event.preventDefault();
        try {
            await submit('post', '/api/admin/subcategories', form);
            router.visit(route('admin.subcategories.index'));
        } catch {
            // handled by hook
        }
    };

    return (
        <AdminLayout title={admin.add_subcategory}>
            <PageHeader breadcrumb={[{ label: admin.subcategories_heading, href: route('admin.subcategories.index') }, { label: admin.add_subcategory }]} title={admin.add_subcategory} />

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
                            placeholder={common.select ?? 'Select...'}
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
                                {admin.add_subcategory}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
