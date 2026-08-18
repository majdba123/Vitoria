import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, SelectField, TextareaField } from '@/Components/admin/form/FormField';
import { CategoryCheckboxGroup } from '@/Components/admin/CategoryCheckboxGroup';
import { ImageUploadCircle } from '@/Components/admin/ImageUploadCircle';
import { LocationPicker } from '@/Components/maps/LocationPicker';
import { Card, CardContent } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { Switch } from '@/Components/ui/switch';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorsEdit({ vendorId }) {
    const { admin, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [form, setForm] = useState({ name: '', phone_number: '', national_id: '', email: '', password: '', store_name: '', address: '', business_type: '', city_id: '', description: '', latitude: '', longitude: '' });
    const [isActive, setIsActive] = useState(true);
    const [cities, setCities] = useState([]);
    const [categories, setCategories] = useState([]);
    const [categoryIds, setCategoryIds] = useState([]);
    const [avatarFile, setAvatarFile] = useState(null);
    const [avatarPreview, setAvatarPreview] = useState(null);
    const [logoFile, setLogoFile] = useState(null);
    const [logoPreview, setLogoPreview] = useState(null);
    const [successMessage, setSuccessMessage] = useState(null);

    useEffect(() => {
        Promise.all([
            window.axios.get(`/api/admin/vendors/${vendorId}`, { silent: true }),
            window.axios.get('/api/admin/categories', { silent: true }),
            window.axios.get('/api/cities', { silent: true }),
        ]).then(([vendorRes, categoriesRes, citiesRes]) => {
            const vendor = vendorRes.data.data;
            setCategories(categoriesRes.data?.data ?? []);
            setCities(citiesRes.data?.data ?? []);
            setCategoryIds(vendor.category_ids ?? []);
            setForm({
                name: vendor.user?.name ?? '',
                phone_number: vendor.user?.phone_number ?? '',
                national_id: vendor.user?.national_id ?? '',
                email: vendor.user?.email ?? '',
                password: '',
                store_name: vendor.store_name ?? '',
                address: vendor.address ?? '',
                business_type: vendor.business_type ?? 'both',
                city_id: vendor.city_id ? String(vendor.city_id) : '',
                description: vendor.description ?? '',
                latitude: vendor.latitude ?? '',
                longitude: vendor.longitude ?? '',
            });
            setIsActive(!!vendor.is_active);
            setAvatarPreview(vendor.user?.avatar_url ?? null);
            setLogoPreview(vendor.logo_url ?? null);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [vendorId]);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));
    const toggleCategory = (id) => setCategoryIds((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));

    const toggleActive = () => {
        window.axios.patch(`/api/admin/vendors/${vendorId}/toggle-active`, {}, { silent: true }).then((res) => {
            setIsActive(res.data.data.is_active);
            setSuccessMessage(res.data.message);
        });
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const payload = { ...form, city_id: form.city_id ? Number(form.city_id) : null, is_active: isActive ? 1 : 0, category_ids: categoryIds };
        if (!payload.password) delete payload.password;
        if (avatarFile) payload.avatar = avatarFile;
        if (logoFile) payload.logo = logoFile;
        try {
            const data = await submit('put', `/api/admin/vendors/${vendorId}`, payload, { isMultipart: true });
            if (data.data?.user?.avatar_url) setAvatarPreview(data.data.user.avatar_url);
            if (data.data?.logo_url) setLogoPreview(data.data.logo_url);
            setAvatarFile(null);
            setLogoFile(null);
            setForm((f) => ({ ...f, password: '' }));
            setSuccessMessage('Vendor updated successfully.');
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading ?? 'Loading...'}>
                <Skeleton className="h-96 w-full max-w-3xl" />
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title="Edit vendor">
            <PageHeader
                breadcrumb={[{ label: admin.vendors_breadcrumb, href: route('admin.vendors.index') }, { label: common.edit ?? 'Edit' }]}
                title="Edit vendor"
                copy="Update vendor account and store information."
                actions={
                    <label className="flex items-center gap-2 text-sm font-medium">
                        <Switch checked={isActive} onCheckedChange={toggleActive} />
                        {isActive ? common.active : common.inactive}
                    </label>
                }
            />

            {generalError && <p className="max-w-3xl rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
            {successMessage && <p className="max-w-3xl rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

            <Card className="max-w-3xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="flex flex-wrap items-center justify-center gap-10 py-2">
                            <ImageUploadCircle
                                label="Profile photo"
                                previewUrl={avatarPreview}
                                fallback={(form.name || 'V').charAt(0).toUpperCase()}
                                onChange={(file) => {
                                    setAvatarFile(file);
                                    if (file) setAvatarPreview(URL.createObjectURL(file));
                                }}
                                error={errors.avatar}
                            />
                            <ImageUploadCircle
                                label="Store logo"
                                previewUrl={logoPreview}
                                fallback={<span className="text-xs text-muted-foreground">No logo</span>}
                                onChange={(file) => {
                                    setLogoFile(file);
                                    if (file) setLogoPreview(URL.createObjectURL(file));
                                }}
                                error={errors.logo}
                            />
                        </div>

                        <Separator />

                        <fieldset className="space-y-4">
                            <legend className="text-sm font-semibold text-foreground">{admin.vendors_user_account}</legend>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField id="name" label="Full name" required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                                <TextField id="phone_number" label="Phone number" type="tel" required value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                                <TextField id="national_id" label="National ID" required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                                <TextField id="email" label="Email" type="email" placeholder="(optional)" value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                                <div className="sm:col-span-2">
                                    <TextField id="password" label="New password" type="password" placeholder="Leave blank to keep current" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                                </div>
                            </div>
                        </fieldset>

                        <Separator />

                        <fieldset className="space-y-4">
                            <legend className="text-sm font-semibold text-foreground">Store details</legend>
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
                                        options={[{ value: 'agriculture', label: admin.type_agriculture }, { value: 'veterinary', label: admin.type_veterinary }, { value: 'both', label: admin.both }]}
                                        error={errors.business_type}
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <SelectField id="city_id" label="City" required value={form.city_id} onValueChange={set('city_id')} options={cities.map((c) => ({ value: c.id, label: c.name }))} error={errors.city_id} />
                                </div>
                            </div>
                            <TextareaField id="description" label="Description" rows={3} value={form.description} onChange={(e) => set('description')(e.target.value)} error={errors.description} />
                        </fieldset>

                        <Separator />

                        <LocationPicker
                            latitude={form.latitude}
                            longitude={form.longitude}
                            onChange={({ latitude, longitude }) => setForm((f) => ({ ...f, latitude, longitude }))}
                            error={errors.latitude ?? errors.longitude}
                        />

                        <Separator />

                        <fieldset className="space-y-3">
                            <legend className="text-sm font-semibold text-foreground">Allowed categories</legend>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <CategoryCheckboxGroup businessType={form.business_type} categories={categories} selectedIds={categoryIds} onToggle={toggleCategory} emptyHint="Select a business type first." />
                            </div>
                        </fieldset>

                        <div className="flex justify-end gap-2 border-t border-border pt-5">
                            <Button type="submit" disabled={isSubmitting}>
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
