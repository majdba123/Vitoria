import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

export default function Register() {
    const { authPage, nav } = useI18n();
    const [cities, setCities] = useState([]);
    const [form, setForm] = useState({
        name: '', phone_number: '', national_id: '', age: '', membership_number: '', city_id: '',
        email: '', password: '', password_confirmation: '',
    });
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState(null);
    const [successMessage, setSuccessMessage] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        window.axios.get('/api/cities', { silent: true }).then((res) => setCities(res.data?.data ?? [])).catch(() => {});
    }, []);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setGeneralError(null);
        setIsSubmitting(true);

        const payload = new FormData();
        payload.append('name', form.name.trim());
        payload.append('phone_number', form.phone_number.trim());
        payload.append('national_id', form.national_id.trim());
        payload.append('age', parseInt(form.age, 10) || '');
        payload.append('membership_number', form.membership_number.trim());
        payload.append('city_id', parseInt(form.city_id, 10) || '');
        payload.append('email', form.email.trim());
        payload.append('password', form.password);
        payload.append('password_confirmation', form.password_confirmation);

        try {
            const response = await window.axios.post('/api/auth/register', payload, { headers: { 'Content-Type': 'multipart/form-data' }, silent: true });
            window.Auth.setToken(response.data.data.token);
            if (response.data.data.user) window.Auth.setUser(response.data.data.user);
            setSuccessMessage(authPage.js_account_created_success);
            setTimeout(() => { window.location.href = response.data.data.redirect_url || '/'; }, 500);
        } catch (error) {
            if (error.response?.status === 422) {
                const fieldErrors = {};
                Object.entries(error.response.data?.errors ?? {}).forEach(([field, messages]) => {
                    const normalized = field.replace(/\./g, '_');
                    fieldErrors[normalized] = Array.isArray(messages) ? messages[0] : messages;
                });
                setErrors(fieldErrors);
            } else {
                setGeneralError(error.response?.data?.message || authPage.js_unexpected_error);
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <PublicLayout title={authPage.create_account_title} noindex>
            <section className="workspace-shell workspace-section">
                <div className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                    <aside className="surface-card overflow-hidden p-8 sm:p-10">
                        <span className="eyebrow">{authPage.register_intro_eyebrow}</span>
                        <h1 className="mt-6 font-display text-4xl font-extrabold tracking-tight text-foreground">{authPage.create_account_title}</h1>
                        <p className="mt-4 text-base leading-8 text-muted-foreground">{authPage.join_today}</p>
                    </aside>

                    <section className="auth-shell">
                        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-border pb-6">
                            <div>
                                <span className="eyebrow">{authPage.register_form_eyebrow}</span>
                                <h2 className="mt-4 font-display text-3xl font-extrabold tracking-tight text-foreground">{authPage.register_form_title}</h2>
                            </div>
                            <Button asChild variant="outline" size="sm"><Link href={route('login')}>{nav.sign_in}</Link></Button>
                        </div>

                        <div className="mt-8">
                            {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                            {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <TextField id="name" label={authPage.full_name} placeholder={authPage.placeholder_full_name} required autoComplete="name" value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                                    <TextField id="phone_number" label={authPage.phone_number} type="tel" placeholder={authPage.placeholder_phone} required autoComplete="tel" value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                                    <TextField id="national_id" label={authPage.national_id} placeholder={authPage.placeholder_national_id} required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                                    <TextField id="age" label={authPage.age} type="number" placeholder={authPage.age_placeholder} required min="1" max="120" inputMode="numeric" value={form.age} onChange={(e) => set('age')(e.target.value)} error={errors.age} />
                                    <TextField id="membership_number" label={authPage.membership_number} placeholder={authPage.membership_number_placeholder} required autoComplete="off" value={form.membership_number} onChange={(e) => set('membership_number')(e.target.value)} error={errors.membership_number} />
                                    <SelectField id="city_id" label={authPage.city} required value={form.city_id} onValueChange={set('city_id')} placeholder={authPage.select_city} options={cities.map((c) => ({ value: c.id, label: c.name }))} error={errors.city_id} />
                                </div>

                                <div className="grid gap-5 md:grid-cols-2">
                                    <TextField id="email" label={authPage.email} type="email" placeholder={authPage.placeholder_email} required autoComplete="email" value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} />
                                    <TextField id="password" label={authPage.password} type="password" placeholder={authPage.placeholder_password} required autoComplete="new-password" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} />
                                    <TextField id="password_confirmation" label={authPage.confirm_password} type="password" placeholder={authPage.placeholder_password_confirm} required autoComplete="new-password" value={form.password_confirmation} onChange={(e) => set('password_confirmation')(e.target.value)} error={errors.password_confirmation} />
                                </div>

                                <div className="flex flex-col gap-4 border-t border-border pt-6 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        {authPage.has_account} <Link href={route('login')} className="font-semibold text-primary hover:underline">{nav.sign_in}</Link>
                                    </p>
                                    <Button type="submit" disabled={isSubmitting} className="sm:w-auto">
                                        {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                        {isSubmitting ? authPage.creating_account : nav.register}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </section>
        </PublicLayout>
    );
}
