import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { TextField } from '@/Components/admin/form/FormField';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

const DASHBOARD_ROUTE = { 1: 'admin.dashboard', 2: 'vendor.dashboard', 3: 'syndicate.dashboard', 4: 'employee.dashboard' };

function redirectByUser(user, fallbackUrl) {
    const route2 = DASHBOARD_ROUTE[Number(user?.type)];
    window.location.href = route2 ? route(route2) : (user?.redirect_url || fallbackUrl);
}

export default function Login() {
    const { authPage, nav } = useI18n();
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
        <PublicLayout title={authPage.sign_in} noindex>
            <section className="workspace-shell workspace-section">
                <div className="grid min-h-[calc(100vh-11rem)] items-center gap-8 lg:grid-cols-[1.05fr_0.95fr]">
                    <div className="border-y-2 border-foreground py-10 text-foreground sm:py-14">
                        <p className="text-xs font-semibold uppercase tracking-[0.14em] text-primary">{authPage.vetora_access}</p>
                        <h1 className="mt-5 max-w-2xl font-display text-4xl font-bold leading-tight sm:text-5xl">{authPage.workspace_title}</h1>
                        <p className="mt-5 max-w-xl text-base leading-8 text-muted-foreground">{authPage.workspace_copy}</p>
                        <dl className="mt-9 grid gap-5 border-t border-border pt-6 sm:grid-cols-3">
                            <div><dt className="text-sm font-semibold">{authPage.workspace_customers}</dt><dd className="mt-1 text-sm leading-6 text-muted-foreground">{authPage.workspace_customers_copy}</dd></div>
                            <div><dt className="text-sm font-semibold">{authPage.workspace_vendors}</dt><dd className="mt-1 text-sm leading-6 text-muted-foreground">{authPage.workspace_vendors_copy}</dd></div>
                            <div><dt className="text-sm font-semibold">{authPage.workspace_syndicates}</dt><dd className="mt-1 text-sm leading-6 text-muted-foreground">{authPage.workspace_syndicates_copy}</dd></div>
                        </dl>
                    </div>

                    <div className="auth-shell">
                        <div className="relative">
                            <span className="eyebrow">{authPage.secure_sign_in}</span>
                            <h2 className="mt-5 font-display text-3xl font-extrabold tracking-tight text-foreground">{authPage.welcome_back}</h2>
                            <p className="mt-2 text-sm leading-7 text-muted-foreground">{authPage.sign_in_to_account}</p>
                        </div>

                        <div className="mt-8">
                            {generalError && <p className="mb-4 rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                            {successMessage && <p className="mb-4 rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                            <form onSubmit={handleSubmit} className="space-y-5">
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
                                <Button type="submit" disabled={isSubmitting} className="w-full">
                                    {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                    {isSubmitting ? authPage.signing_in : nav.sign_in}
                                </Button>
                            </form>
                        </div>

                        <div className="mt-8 flex items-center justify-between border-t border-border pt-5 text-sm">
                            <p className="text-muted-foreground">{authPage.dont_have_account}</p>
                            <Link href={route('register')} className="font-bold text-primary hover:underline">{authPage.create_one}</Link>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
