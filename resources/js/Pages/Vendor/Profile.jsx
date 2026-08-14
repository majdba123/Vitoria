import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextField, TextareaField } from '@/Components/admin/form/FormField';
import { ImageUploadCircle } from '@/Components/admin/ImageUploadCircle';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Card, CardContent } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorProfile() {
    const { common } = useI18n();
    const { submit, errors, generalError, isSubmitting, setGeneralError } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [form, setForm] = useState({ name: '', phone_number: '', national_id: '', email: '', password: '', current_password: '', store_name: '', address: '', description: '' });
    const [businessTypeLabel, setBusinessTypeLabel] = useState('Both');
    const [categories, setCategories] = useState([]);
    const [isActive, setIsActive] = useState(true);
    const [avatarFile, setAvatarFile] = useState(null);
    const [avatarPreview, setAvatarPreview] = useState(null);
    const [logoFile, setLogoFile] = useState(null);
    const [logoPreview, setLogoPreview] = useState(null);
    const [successMessage, setSuccessMessage] = useState(null);

    useEffect(() => {
        window.axios.get('/api/vendor/profile', { silent: true }).then((res) => {
            const { user, vendor } = res.data.data;
            setForm({
                name: user.name ?? '',
                phone_number: user.phone_number ?? '',
                national_id: user.national_id ?? '',
                email: user.email ?? '',
                password: '',
                current_password: '',
                store_name: vendor?.store_name ?? '',
                address: vendor?.address ?? '',
                description: vendor?.description ?? '',
            });
            setBusinessTypeLabel(vendor?.business_type_label || 'Both');
            setCategories(vendor?.categories ?? []);
            setIsActive(!!vendor?.is_active);
            setAvatarPreview(user.avatar_url ?? null);
            setLogoPreview(vendor?.logo_url ?? null);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, []);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        if (form.password && !form.current_password) {
            setGeneralError('Enter your current password to set a new one.');
            return;
        }

        const payload = {
            name: form.name.trim(),
            phone_number: form.phone_number.trim(),
            national_id: form.national_id.trim(),
            store_name: form.store_name.trim(),
            email: form.email.trim() || undefined,
            address: form.address.trim() || undefined,
            description: form.description.trim() || undefined,
        };
        if (form.password) {
            payload.password = form.password;
            payload.current_password = form.current_password;
        }
        if (avatarFile) payload.avatar = avatarFile;
        if (logoFile) payload.logo = logoFile;

        try {
            const data = await submit('post', '/api/vendor/profile', payload, { isMultipart: true });
            const { user, vendor } = data.data;
            if (user?.avatar_url) setAvatarPreview(user.avatar_url);
            if (vendor?.logo_url) setLogoPreview(vendor.logo_url);
            setAvatarFile(null);
            setLogoFile(null);
            setForm((f) => ({ ...f, password: '', current_password: '' }));
            setSuccessMessage('Profile updated successfully!');
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <VendorLayout title="My Profile">
                <Skeleton className="h-96 w-full max-w-3xl" />
            </VendorLayout>
        );
    }

    if (status === 'error') {
        return (
            <VendorLayout title="My Profile">
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load profile.</p>
            </VendorLayout>
        );
    }

    return (
        <VendorLayout title="My Profile">
            <PageHeader
                title={form.name || 'My Profile'}
                copy={form.store_name}
                actions={<StatusBadge tone={isActive ? 'success' : 'danger'}>{isActive ? 'Active Store' : 'Inactive Store'}</StatusBadge>}
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
                            <legend className="text-sm font-semibold text-foreground">Personal information</legend>
                            <p className="mb-2 text-xs text-muted-foreground">Your account details and login credentials.</p>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField id="name" label="Full name" required value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                                <TextField id="phone_number" label="Phone number" type="tel" required value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                                <TextField id="national_id" label="National ID" required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                                <TextField id="email" label="Email" type="email" placeholder="(optional)" value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                                <TextField id="password" label="New password" type="password" placeholder="Leave blank to keep current" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                                <TextField id="current_password" label="Current password" type="password" placeholder="Required to set a new password" value={form.current_password} onChange={(e) => set('current_password')(e.target.value)} error={errors.current_password} />
                            </div>
                        </fieldset>

                        <Separator />

                        <fieldset className="space-y-4">
                            <legend className="text-sm font-semibold text-foreground">Store information</legend>
                            <p className="mb-2 text-xs text-muted-foreground">Your store profile visible to customers.</p>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField id="store_name" label="Store name" required value={form.store_name} onChange={(e) => set('store_name')(e.target.value)} error={errors.store_name} />
                                <TextField id="address" label="Address" placeholder="(optional)" value={form.address} onChange={(e) => set('address')(e.target.value)} error={errors.address} />
                            </div>
                            <div className="rounded-md border border-border bg-muted/40 px-4 py-3">
                                <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Business type</p>
                                <p className="mt-1 text-sm font-semibold text-foreground">{businessTypeLabel}</p>
                            </div>
                            <TextareaField id="description" label="Description" rows={3} placeholder="Tell customers about your store..." value={form.description} onChange={(e) => set('description')(e.target.value)} error={errors.description} />
                        </fieldset>

                        <Separator />

                        <fieldset className="space-y-3">
                            <legend className="text-sm font-semibold text-foreground">Allowed categories</legend>
                            <p className="mb-2 text-xs text-muted-foreground">Categories assigned by admin. Contact admin to change.</p>
                            <div className="flex flex-wrap gap-2">
                                {categories.length === 0 ? (
                                    <span className="text-sm italic text-muted-foreground">No categories assigned</span>
                                ) : (
                                    categories.map((c) => (
                                        <StatusBadge key={c.id} tone={c.type === 'veterinary' ? 'brand' : 'success'}>
                                            {c.name} {c.type_label ? <span className="opacity-75">{c.type_label}</span> : null}
                                        </StatusBadge>
                                    ))
                                )}
                            </div>
                        </fieldset>

                        <div className="flex justify-end gap-2 border-t border-border pt-5">
                            <Button type="button" variant="outline" asChild>
                                <Link href={route('vendor.dashboard')}>{common.cancel ?? 'Cancel'}</Link>
                            </Button>
                            <Button type="submit" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {common.save_changes ?? 'Save changes'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </VendorLayout>
    );
}
