import { useState } from 'react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { TextField, TextareaField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { Button } from '@/Components/ui/button';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function AboutUsEdit({ about_description, facebook_url, instagram_url, twitter_url, contact_email, contact_address }) {
    const { admin } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [form, setForm] = useState({
        about_description: about_description ?? '',
        facebook_url: facebook_url ?? '',
        instagram_url: instagram_url ?? '',
        twitter_url: twitter_url ?? '',
        contact_email: contact_email ?? '',
        contact_address: contact_address ?? '',
    });
    const [successMessage, setSuccessMessage] = useState(null);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const payload = {};
        Object.entries(form).forEach(([key, value]) => {
            payload[key] = value.trim() || null;
        });
        try {
            await submit('put', '/api/admin/footer-settings', payload);
            setSuccessMessage(admin.about_saved);
        } catch {
            // handled by hook
        }
    };

    return (
        <AdminLayout title={admin.about_us}>
            <PageHeader title={admin.about_us} copy={admin.about_edit_desc} />

            <Card className="max-w-2xl border-border/80 shadow-none">
                <CardContent className="p-5 sm:p-6">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <TextareaField id="about_description" label={admin.about_description} rows={4} maxLength={2000} placeholder={admin.about_description_placeholder} hint={admin.about_description_hint} value={form.about_description} onChange={(e) => set('about_description')(e.target.value)} error={errors.about_description} />

                        <Separator />
                        <h3 className="text-sm font-semibold text-foreground">{admin.about_social}</h3>

                        <TextField id="facebook_url" label={admin.about_facebook} type="url" placeholder="https://facebook.com/..." value={form.facebook_url} onChange={(e) => set('facebook_url')(e.target.value)} error={errors.facebook_url} />
                        <TextField id="instagram_url" label={admin.about_instagram} type="url" placeholder="https://instagram.com/..." value={form.instagram_url} onChange={(e) => set('instagram_url')(e.target.value)} error={errors.instagram_url} />
                        <TextField id="twitter_url" label={admin.about_twitter} type="url" placeholder="https://twitter.com/..." value={form.twitter_url} onChange={(e) => set('twitter_url')(e.target.value)} error={errors.twitter_url} />

                        <Separator />
                        <div>
                            <h3 className="text-sm font-semibold text-foreground">{admin.about_contact}</h3>
                            <p className="mt-1 text-xs text-muted-foreground">{admin.about_contact_hint}</p>
                        </div>

                        <TextField id="contact_email" label={admin.about_contact_email} type="email" placeholder="support@vetora.test" value={form.contact_email} onChange={(e) => set('contact_email')(e.target.value)} error={errors.contact_email} />
                        <TextField id="contact_address" label={admin.about_contact_address} placeholder={admin.contact_address_placeholder} hint={admin.about_max_500} value={form.contact_address} onChange={(e) => set('contact_address')(e.target.value)} error={errors.contact_address} />

                        {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                        {successMessage && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                        <div className="flex justify-end gap-2 pt-2">
                            <Button type="submit" disabled={isSubmitting}>
                                {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                {admin.about_save_btn}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
