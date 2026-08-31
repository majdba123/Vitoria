import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField, SelectField, FileField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function CategoriesCreate() {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [form, setForm] = useState({ name: '', type: '', commission: '0', logo: null });
    const [preview, setPreview] = useState(null);

    const handleSubmit = async (event) => {
        event.preventDefault();
        try {
            await submit('post', '/api/admin/categories', form, { isMultipart: true });
            router.visit(route('admin.categories.index'));
        } catch {
            // errors already captured by useAdminForm
        }
    };

    return (
        <AdminLayout title={admin.add_category}>
            <PageHeader breadcrumb={[{ label: admin.categories, href: route('admin.categories.index') }, { label: admin.add_category }]} title={admin.add_category} />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <TextField
                            id="name"
                            label={admin.category_name}
                            required
                            value={form.name}
                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                            error={errors.name}
                        />

                        <SelectField
                            id="type"
                            label={admin.category_type_label}
                            required
                            value={form.type}
                            onValueChange={(value) => setForm({ ...form, type: value })}
                            placeholder={common.select}
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
                            hint={admin.category_commission_hint}
                            value={form.commission}
                            onChange={(e) => setForm({ ...form, commission: e.target.value })}
                            error={errors.commission}
                        />

                        <FileField
                            id="logo"
                            label={admin.category_image_label}
                            hint={admin.category_image_hint}
                            preview={preview}
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                setForm({ ...form, logo: file });
                                setPreview(file ? URL.createObjectURL(file) : null);
                            }}
                            error={errors.logo}
                        />

                        <div className="flex gap-2 pt-2">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.categories.index'))}>
                                {common.cancel}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {admin.add_category}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
