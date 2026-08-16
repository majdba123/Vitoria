import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Loader2, ShieldCheck } from 'lucide-react';
import { TextField } from '@/Components/admin/form/FormField';
import { Button } from '@/Components/ui/button';
import { LanguageSwitcher } from '@/Components/workspace/LanguageSwitcher';
import { ThemeToggle } from '@/Components/workspace/ThemeToggle';
import { useI18n } from '@/hooks/use-i18n';

const DASHBOARD_ROUTE = { 1: 'admin.dashboard', 2: 'vendor.dashboard', 3: 'syndicate.dashboard', 4: 'employee.dashboard' };

function redirectByUser(user, fallbackUrl) {
    const route2 = DASHBOARD_ROUTE[Number(user?.type)];
    window.location.href = route2 ? route(route2) : (user?.redirect_url || fallbackUrl);
}

export default function Login() {
    const { authPage, nav, admin } = useI18n();
    const [phoneNumber, setPhoneNumber] = useState('');
    const [password, setPassword] = useState('');
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState(null);
    const [successMessage, setSuccessMessage] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setGeneralError(null);
        setIsSubmitting(true);

        try {
            const response = await window.axios.post('/api/auth/login', { phone_number: phoneNumber.trim(), password }, { silent: true });
            window.Auth.setToken(response.data.data.token);
            if (response.data.data.user) window.Auth.setUser(response.data.data.user);

            const redirectUrl = response.data.data.redirect_url;
            setSuccessMessage(authPage.signed_in_success);

            setTimeout(() => redirectByUser(response.data.data.user, redirectUrl || '/'), 500);
        } catch (error) {
            if (error.response?.status === 422) {
                const fieldErrors = {};
                Object.entries(error.response.data?.errors ?? {}).forEach(([field, messages]) => {
                    fieldErrors[field] = Array.isArray(messages) ? messages[0] : messages;
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
            <Head title={authPage.sign_in}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <main className="grid min-h-svh bg-background lg:grid-cols-2">
                <section className="flex min-h-svh flex-col gap-8 p-5 sm:p-8 lg:p-10">
                    <header className="flex items-center justify-between gap-4">
                        <Link href={route('home')} className="inline-flex min-h-11 items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <img src="/images/vetora-logo-transparent.png" alt="" className="size-9 object-contain" />
                            <span className="font-display text-lg font-bold text-foreground">Vetora</span>
                        </Link>
                        <div className="flex items-center gap-1">
                            <LanguageSwitcher />
                            <ThemeToggle label={admin.toggle_theme ?? 'Toggle theme'} />
                        </div>
                    </header>

                    <div className="flex flex-1 items-center justify-center py-8">
                        <div className="w-full max-w-sm">
                            <div className="mb-8">
                                <span className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.08em] text-primary rtl:normal-case rtl:tracking-normal">
                                    <ShieldCheck className="size-4" />
                                    {authPage.secure_sign_in}
                                </span>
                                <h1 className="mt-4 font-display text-3xl font-bold leading-tight text-foreground sm:text-4xl">{authPage.welcome_back}</h1>
                                <p className="mt-3 max-w-[55ch] text-sm leading-6 text-muted-foreground">{authPage.sign_in_to_account}</p>
                            </div>

                            {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                            {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                            <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                                <TextField
                                    id="phone_number"
                                    label={authPage.phone_number}
                                    type="tel"
                                    placeholder={authPage.placeholder_phone}
                                    required
                                    autoComplete="tel"
                                    value={phoneNumber}
                                    onChange={(e) => setPhoneNumber(e.target.value)}
                                    error={errors.phone_number}
                                />
                                <TextField
                                    id="password"
                                    label={authPage.password}
                                    type="password"
                                    placeholder={authPage.placeholder_password}
                                    required
                                    autoComplete="current-password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    error={errors.password}
                                />
                                <Button type="submit" disabled={isSubmitting} className="min-h-11 w-full">
                                    {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                    {isSubmitting ? authPage.signing_in : nav.sign_in}
                                </Button>
                            </form>

                            <div className="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-5 text-sm">
                                <p className="text-muted-foreground">{authPage.dont_have_account}</p>
                                <Link href={route('register')} className="inline-flex min-h-11 items-center gap-1.5 font-semibold text-primary hover:text-[var(--color-brand-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    {authPage.create_one}
                                    <ArrowRight className="size-4 rtl:rotate-180" />
                                </Link>
                            </div>
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
                    </div>
                </aside>
            </main>
        </>
    );
}
