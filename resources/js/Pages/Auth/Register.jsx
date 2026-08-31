import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Loader2 } from 'lucide-react';
import { TextField, SelectField } from '@/Components/admin/form/FormField';
import { Button } from '@/Components/ui/button';
import { LanguageSwitcher } from '@/Components/workspace/LanguageSwitcher';
import { ThemeToggle } from '@/Components/workspace/ThemeToggle';
import { useI18n } from '@/hooks/use-i18n';

export default function Register() {
    const { authPage, nav, admin } = useI18n();
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
        <>
            <Head title={authPage.create_account_title}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <main className="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.65fr)]">
                <section className="flex min-h-svh min-w-0 flex-col gap-8 p-5 sm:p-8 lg:p-10 xl:p-12">
                    <header className="flex items-center justify-between gap-4">
                        <Link href={route('home')} aria-label="Vetora" className="inline-flex min-h-11 items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <img src="/images/vetora-logo-transparent.png" alt="Vetora" className="h-9 w-auto object-contain" />
                        </Link>
                        <div className="flex items-center gap-1">
                            <LanguageSwitcher />
                            <ThemeToggle label={admin.toggle_theme} />
                        </div>
                    </header>

                    <div className="flex flex-1 items-center justify-center py-4 sm:py-8">
                        <div className="w-full max-w-4xl">
                            <div className="mb-8 flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.1em] text-primary rtl:normal-case rtl:tracking-normal">{authPage.register_intro_eyebrow}</p>
                                    <h1 className="mt-3 font-display text-4xl font-bold leading-tight text-foreground sm:text-5xl">{authPage.create_account_title}</h1>
                                    <p className="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">{authPage.join_today}</p>
                                </div>
                                <Button asChild variant="outline" size="sm" className="shrink-0">
                                    <Link href={route('login')}>{nav.sign_in}</Link>
                                </Button>
                            </div>

                            {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                            {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                            <form onSubmit={handleSubmit} className="space-y-7" noValidate>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <TextField id="name" label={authPage.full_name} placeholder={authPage.placeholder_full_name} required autoComplete="name" value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} className="h-11 text-base" />
                                    <TextField id="phone_number" label={authPage.phone_number} type="tel" placeholder={authPage.placeholder_phone} required autoComplete="tel" value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} className="h-11 text-base" />
                                    <TextField id="national_id" label={authPage.national_id} placeholder={authPage.placeholder_national_id} required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} className="h-11 text-base" />
                                    <TextField id="age" label={authPage.age} type="number" placeholder={authPage.age_placeholder} required min="1" max="120" inputMode="numeric" value={form.age} onChange={(e) => set('age')(e.target.value)} error={errors.age} className="h-11 text-base" />
                                    <TextField id="membership_number" label={authPage.membership_number} placeholder={authPage.membership_number_placeholder} required autoComplete="off" value={form.membership_number} onChange={(e) => set('membership_number')(e.target.value)} error={errors.membership_number} className="h-11 text-base" />
                                    <SelectField id="city_id" label={authPage.city} required value={form.city_id} onValueChange={set('city_id')} placeholder={authPage.select_city} options={cities.map((c) => ({ value: c.id, label: c.name }))} error={errors.city_id} />
                                </div>

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <TextField id="email" label={authPage.email} type="email" placeholder={authPage.placeholder_email} required autoComplete="email" value={form.email} onChange={(e) => set('email')(e.target.value)} error={errors.email} className="h-11 text-base sm:col-span-2" />
                                    <TextField id="password" label={authPage.password} type="password" placeholder={authPage.placeholder_password} required autoComplete="new-password" value={form.password} onChange={(e) => set('password')(e.target.value)} error={errors.password} className="h-11 text-base" />
                                    <TextField id="password_confirmation" label={authPage.confirm_password} type="password" placeholder={authPage.placeholder_password_confirm} required autoComplete="new-password" value={form.password_confirmation} onChange={(e) => set('password_confirmation')(e.target.value)} error={errors.password_confirmation} className="h-11 text-base" />
                                </div>

                                <div className="flex flex-col gap-4 border-t border-border pt-6 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        {authPage.has_account} <Link href={route('login')} className="font-semibold text-primary hover:underline">{nav.sign_in}</Link>
                                    </p>
                                    <Button type="submit" size="lg" disabled={isSubmitting} className="w-full sm:w-auto">
                                        {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                        {isSubmitting ? authPage.creating_account : nav.register}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <p className="text-xs leading-5 text-muted-foreground">{authPage.vetora_access}</p>
                </section>

                <aside className="relative hidden overflow-hidden bg-slate-950 lg:block" aria-label={authPage.workspace_title}>
                    <img src="/images/vetora-hero-poster.webp" alt="" className="absolute inset-0 h-full w-full object-cover opacity-70 dark:opacity-45" />
                    <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent" />
                    <div className="absolute inset-x-0 bottom-0 p-10 text-white xl:p-14">
                        <p className="text-xs font-semibold uppercase tracking-[0.1em] text-sky-200 rtl:normal-case rtl:tracking-normal">{authPage.vetora_access}</p>
                        <h2 className="mt-4 max-w-xl font-display text-4xl font-bold leading-tight xl:text-5xl">{authPage.workspace_title}</h2>
                        <p className="mt-4 max-w-lg text-base leading-7 text-slate-200">{authPage.workspace_copy}</p>
                        <Link href={route('login')} className="mt-6 inline-flex min-h-11 items-center gap-1.5 font-semibold text-white hover:text-sky-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            {nav.sign_in}
                            <ArrowRight className="size-4 rtl:rotate-180" aria-hidden="true" />
                        </Link>
                    </div>
                </aside>
            </main>
        </>
    );
}
