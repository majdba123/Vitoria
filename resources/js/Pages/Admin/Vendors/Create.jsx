import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField, TextareaField } from '@/Components/admin/form/FormField';
import { CategoryCheckboxGroup } from '@/Components/admin/CategoryCheckboxGroup';
import { Card, CardContent } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

const emptyForm = { name: '', phone_number: '', national_id: '', email: '', password: '', store_name: '', address: '', business_type: '', city_id: '', description: '', latitude: '', longitude: '' };

export default function VendorsCreate() {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [form, setForm] = useState(emptyForm);
    const [cities, setCities] = useState([]);
    const [categories, setCategories] = useState([]);
    const [categoryIds, setCategoryIds] = useState([]);

    useEffect(() => {
        window.axios.get('/api/cities', { silent: true }).then((res) => setCities(res.data?.data ?? []));
        window.axios.get('/api/admin/categories', { silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, []);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const toggleCategory = (id) => setCategoryIds((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));

    const handleSubmit = async (event) => {
        event.preventDefault();
        const payload = { ...form, city_id: form.city_id ? Number(form.city_id) : null, category_ids: categoryIds };
        Object.keys(payload).forEach((key) => {
            if (payload[key] === '') delete payload[key];
        });
        try {
            await submit('post', '/api/admin/vendors', payload);
            router.visit(route('admin.vendors.index'));
        } catch {
            // handled by hook
        }
    };

    return (
        <AdminLayout title={admin.vendors_create}>
            <PageHeader breadcrumb={[{ label: admin.vendors_breadcrumb, href: route('admin.vendors.index') }, { label: admin.create ?? 'Create' }]} title={admin.vendors_create_new} copy={admin.vendors_create_desc} />

            <Card className="max-w-3xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <fieldset className="space-y-4">
                            <div>
                                <legend className="text-sm font-semibold text-foreground">{admin.vendors_user_account}</legend>
                                <p className="mt-0.5 text-xs text-muted-foreground">{admin.vendors_credentials}</p>
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField id="name" label="Full name" required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                                <TextField id="phone_number" label="Phone number" type="tel" required placeholder="09XXXXXXXX" value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                                <TextField id="national_id" label="National ID" required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                                <TextField id="email" label="Email" type="email" placeholder="(optional)" value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                                <div className="sm:col-span-2">
                                    <TextField id="password" label="Password" type="password" required placeholder="Min 6 characters" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                                </div>
                            </div>
                        </fieldset>

                        <Separator />

                        <fieldset className="space-y-4">
                            <div>
                                <legend className="text-sm font-semibold text-foreground">Store details</legend>
                                <p className="mt-0.5 text-xs text-muted-foreground">Information about the vendor's store.</p>
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField id="store_name" label="Store name" required value={form.store_name} onChange={(e) => set('store_name')(e.target.value)} error={errors.store_name} />
                                <TextField id="address" label="Address" placeholder="(optional)" value={form.address} onChange={(e) => set('address')(e.target.value)} error={errors.address} />
                                <div className="sm:col-span-2">
                                    <SelectField
                                        id="business_type"
                                        label={admin.business_type_label}
                                        required
                                        value={form.business_type}
                                        onValueChange={set('business_type')}
                                        placeholder={common.select ?? 'Select...'}
                                        options={[{ value: 'agriculture', label: admin.type_agriculture }, { value: 'veterinary', label: admin.type_veterinary }, { value: 'both', label: admin.both }]}
                                        error={errors.business_type}
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <SelectField
                                        id="city_id"
                                        label="City"
                                        required
                                        value={form.city_id}
                                        onValueChange={set('city_id')}
                                        placeholder="Select store city"
                                        options={cities.map((c) => ({ value: c.id, label: c.name }))}
                                        error={errors.city_id}
                                    />
                                </div>
                            </div>
                            <TextareaField id="description" label={admin.vendors_description} rows={3} placeholder="(optional)" value={form.description} onChange={(e) => set('description')(e.target.value)} error={errors.description} />
                        </fieldset>

                        <Separator />

                        <fieldset className="space-y-3">
                            <div>
                                <legend className="text-sm font-semibold text-foreground">Allowed categories</legend>
                                <p className="mt-0.5 text-xs text-muted-foreground">Select which categories this vendor is allowed to sell products in.</p>
                            </div>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <CategoryCheckboxGroup businessType={form.business_type} categories={categories} selectedIds={categoryIds} onToggle={toggleCategory} emptyHint="Select a business type first." />
                            </div>
                            {errors.category_ids && <p className="text-xs font-medium text-[var(--color-danger-strong)]">{errors.category_ids}</p>}
                        </fieldset>

                        <div className="flex gap-2 border-t border-border pt-5">
                            <Button type="button" variant="outline" className="flex-1" onClick={() => router.visit(route('admin.vendors.index'))}>
                                {common.cancel ?? 'Cancel'}
                            </Button>
                            <Button type="submit" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {admin.vendors_create_btn}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
