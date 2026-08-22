import { useState } from 'react';
import { AlertTriangle, CheckCircle2 } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useI18n } from '@/hooks/use-i18n';

export default function Contact() {
    const { home } = useI18n();
    const [form, setForm] = useState({ name: '', email: '', message: '' });
    const [errors, setErrors] = useState({});
    const [status, setStatus] = useState('idle');

    const submit = (event) => {
        event.preventDefault();
        setErrors({});
        setStatus('sending');
        window.axios.post('/api/contact', {
            name: form.name.trim() || null,
            email: form.email.trim(),
            message: form.message.trim(),
        }, { silent: true }).then(() => {
            setStatus('success');
            setForm((current) => ({ ...current, message: '' }));
        }).catch((error) => {
            setStatus('error');
            if (error.response?.status === 422) {
                setErrors(Object.fromEntries(Object.entries(error.response.data?.errors ?? {}).map(([field, messages]) => [field, messages[0]])));
            }
        });
    };

    return (
        <PublicLayout title={home.contact_title} description={home.contact_subtitle}>
            <section className="workspace-shell py-10 sm:py-16">
                <div className="mx-auto max-w-2xl">
                    <header className="mb-7 text-center">
                        <h1 className="text-3xl font-black text-foreground sm:text-4xl">{home.contact_title}</h1>
                        <p className="mt-3 text-sm leading-7 text-muted-foreground sm:text-base">{home.contact_subtitle}</p>
                    </header>
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-border bg-card p-6 shadow-sm sm:p-8">
                        <Field htmlFor="contact-name" label={home.contact_name_label} error={errors.name}>
                            <input id="contact-name" type="text" maxLength={255} className="form-input" placeholder={home.contact_name_placeholder} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
                        </Field>
                        <Field htmlFor="contact-email" label={home.contact_email_label} error={errors.email} required>
                            <input id="contact-email" type="email" required maxLength={255} className="form-input" placeholder={home.contact_email_placeholder} value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
                        </Field>
                        <Field htmlFor="contact-message" label={home.contact_message_label} error={errors.message} required>
                            <textarea id="contact-message" required rows={6} maxLength={5000} className="form-textarea" placeholder={home.contact_message_placeholder} value={form.message} onChange={(event) => setForm({ ...form, message: event.target.value })} />
                        </Field>
                        {status === 'success' && <p role="status" className="alert-shell border-[var(--color-success-200)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]"><CheckCircle2 className="size-5" />{home.contact_success}</p>}
                        {status === 'error' && Object.keys(errors).length === 0 && <p role="alert" className="alert-shell border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]"><AlertTriangle className="size-5" />{home.contact_error_default}</p>}
                        <button type="submit" disabled={status === 'sending'} className="btn-primary w-full justify-center">{status === 'sending' ? home.contact_sending : home.contact_send}</button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}

function Field({ htmlFor, label, error, required = false, children }) {
    return <div><label htmlFor={htmlFor} className="form-label">{label}{required && <span className="text-[var(--color-danger-strong)]"> *</span>}</label>{children}{error && <p className="form-error">{error}</p>}</div>;
}
