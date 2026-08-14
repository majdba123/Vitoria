import { useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { TextField, SelectField, TextareaField } from '@/Components/admin/form/FormField';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

function CategoryOption({ category, checked, onToggle }) {
    return (
        <label className={`surface-card-muted flex min-h-20 cursor-pointer items-start gap-3 p-3 text-sm ${checked ? 'border-primary bg-accent/50' : ''}`}>
            <input type="checkbox" className="mt-1 size-4 rounded border-border text-primary focus:ring-primary" checked={checked} onChange={() => onToggle(category.id)} />
            <span className="min-w-0">
                <span className="block font-bold text-foreground">{category.name}</span>
                <span className="mt-1 block text-xs text-muted-foreground">{category.type || ''}</span>
            </span>
        </label>
    );
}

export default function Register() {
    const { authPage, nav } = useI18n();
    const [accountType, setAccountType] = useState('user');
    const [cities, setCities] = useState([]);
    const [allCategories, setAllCategories] = useState([]);
    const [categoryIds, setCategoryIds] = useState([]);
    const [commercialRegisterFile, setCommercialRegisterFile] = useState(null);
    const [form, setForm] = useState({
        name: '', phone_number: '', national_id: '', age: '', membership_number: '', city_id: '',
        store_name: '', business_type: '', address: '', description: '',
        email: '', password: '', password_confirmation: '',
    });
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState(null);
    const [successMessage, setSuccessMessage] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        window.axios.get('/api/cities', { silent: true }).then((res) => setCities(res.data?.data ?? [])).catch(() => {});
        window.axios.get('/api/categories', { params: { per_page: 100 }, silent: true }).then((res) => setAllCategories(res.data?.data ?? [])).catch(() => {});
    }, []);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));
    const isMerchant = accountType === 'vendor';

    const toggleCategory = (id) => setCategoryIds((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));

    const visibleCategoryGroups = useMemo(() => {
        if (!form.business_type) return null;
        if (form.business_type === 'both') {
            return [
                { title: authPage.merchant_categories_group_agriculture, rows: allCategories.filter((c) => c.type === 'agriculture') },
                { title: authPage.merchant_categories_group_veterinary, rows: allCategories.filter((c) => c.type === 'veterinary') },
            ];
        }
        return [{ title: null, rows: allCategories.filter((c) => c.type === form.business_type) }];
    }, [allCategories, form.business_type, authPage]);

    const selectAccountType = (type) => {
        setAccountType(type);
        if (type !== 'vendor') {
            setForm((f) => ({ ...f, store_name: '', business_type: '', address: '', description: '' }));
            setCategoryIds([]);
            setCommercialRegisterFile(null);
        }
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setGeneralError(null);
        setIsSubmitting(true);

        const payload = new FormData();
        payload.append('account_type', accountType);
        payload.append('name', form.name.trim());
        payload.append('phone_number', form.phone_number.trim());
        payload.append('national_id', form.national_id.trim());
        payload.append('age', parseInt(form.age, 10) || '');
        payload.append('membership_number', form.membership_number.trim());
        payload.append('city_id', parseInt(form.city_id, 10) || '');
        payload.append('email', form.email.trim());
        payload.append('password', form.password);
        payload.append('password_confirmation', form.password_confirmation);

        if (isMerchant) {
            payload.append('store_name', form.store_name.trim());
            payload.append('business_type', form.business_type);
            categoryIds.forEach((id) => payload.append('category_ids[]', id));
            payload.append('address', form.address.trim());
            payload.append('description', form.description.trim());
            if (commercialRegisterFile) payload.append('commercial_register_file', commercialRegisterFile);
        }

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
                    const normalized = field.replace(/\./g, '_').replace(/^category_ids_\d+$/, 'category_ids').replace(/^commercial_register$/, 'commercial_register_file');
                    fieldErrors[normalized] = Array.isArray(messages) ? messages[0] : messages;
                });
                setErrors(fieldErrors);
                if (fieldErrors.commercial_register_file) setCommercialRegisterFile(null);
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

                        <div className="auth-role-list mt-8">
                            {[
                                { title: authPage.register_customer_title, copy: authPage.register_customer_copy },
                                { title: authPage.register_vendor_title, copy: authPage.register_vendor_copy },
                                { title: authPage.register_platform_title, copy: authPage.register_platform_copy },
                            ].map((item, index) => (
                                <div key={item.title} className="auth-role-item">
                                    <span className="auth-role-index">0{index + 1}</span>
                                    <p className="text-[11px] font-extrabold uppercase tracking-[0.22em] text-primary">{item.title}</p>
                                    <p className="mt-2 text-sm leading-7 text-muted-foreground">{item.copy}</p>
                                </div>
                            ))}
                        </div>
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
                                <div>
                                    <label className="form-label">{authPage.account_type} <span className="text-[var(--color-danger-strong)]">*</span></label>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        {[{ value: 'user', label: authPage.account_type_user, copy: authPage.account_type_user_copy }, { value: 'vendor', label: authPage.account_type_vendor, copy: authPage.account_type_vendor_copy }].map((opt) => (
                                            <label key={opt.value} className={`surface-card-muted flex cursor-pointer items-start gap-3 p-4 ${accountType === opt.value ? 'border-primary bg-accent/50' : ''}`}>
                                                <input type="radio" name="account_type" className="mt-1 size-4 border-border text-primary focus:ring-primary" checked={accountType === opt.value} onChange={() => selectAccountType(opt.value)} />
                                                <span>
                                                    <span className="block text-sm font-bold text-foreground">{opt.label}</span>
                                                    <span className="mt-1 block text-xs text-muted-foreground">{opt.copy}</span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="grid gap-5 md:grid-cols-2">
                                    <TextField id="name" label={authPage.full_name} placeholder={authPage.placeholder_full_name} required autoComplete="name" value={form.name} onChange={(e) => set('name')(e.target.value)} error={errors.name} />
                                    <TextField id="phone_number" label={authPage.phone_number} type="tel" placeholder={authPage.placeholder_phone} required autoComplete="tel" value={form.phone_number} onChange={(e) => set('phone_number')(e.target.value)} error={errors.phone_number} />
                                    <TextField id="national_id" label={authPage.national_id} placeholder={authPage.placeholder_national_id} required value={form.national_id} onChange={(e) => set('national_id')(e.target.value)} error={errors.national_id} />
                                    <TextField id="age" label={authPage.age} type="number" placeholder={authPage.age_placeholder} required min="1" max="120" inputMode="numeric" value={form.age} onChange={(e) => set('age')(e.target.value)} error={errors.age} />
                                    <TextField id="membership_number" label={authPage.membership_number} placeholder={authPage.membership_number_placeholder} required autoComplete="off" value={form.membership_number} onChange={(e) => set('membership_number')(e.target.value)} error={errors.membership_number} />
                                    <SelectField id="city_id" label={authPage.city} required value={form.city_id} onValueChange={set('city_id')} placeholder={authPage.select_city} options={cities.map((c) => ({ value: c.id, label: c.name }))} error={errors.city_id} />
                                </div>

                                {isMerchant && (
                                    <div className="surface-card-muted p-5">
                                        <div className="mb-5 flex items-start justify-between gap-4">
                                            <div>
                                                <h2 className="text-base font-bold text-foreground">{authPage.merchant_details}</h2>
                                                <p className="mt-1 text-sm text-muted-foreground">{authPage.merchant_details_copy}</p>
                                            </div>
                                            <span className="badge badge-warning shrink-0">{authPage.review_badge}</span>
                                        </div>

                                        <div className="grid gap-5 md:grid-cols-2">
                                            <TextField id="store_name" label={authPage.store_name} placeholder={authPage.store_name_placeholder} value={form.store_name} onChange={(e) => set('store_name')(e.target.value)} error={errors.store_name} />
                                            <SelectField
                                                id="business_type"
                                                label={authPage.business_type}
                                                required
                                                value={form.business_type}
                                                onValueChange={(v) => { set('business_type')(v); setCategoryIds([]); }}
                                                placeholder={authPage.select_business_type}
                                                options={[
                                                    { value: 'agriculture', label: authPage.business_type_agriculture },
                                                    { value: 'veterinary', label: authPage.business_type_veterinary },
                                                    { value: 'both', label: authPage.business_type_both },
                                                ]}
                                                error={errors.business_type}
                                            />

                                            <div>
                                                <label htmlFor="commercial_register_file" className="form-label">{authPage.commercial_register_document} <span className="text-[var(--color-danger-strong)]">*</span></label>
                                                <input
                                                    type="file"
                                                    id="commercial_register_file"
                                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png"
                                                    className="form-input"
                                                    onChange={(e) => setCommercialRegisterFile(e.target.files?.[0] ?? null)}
                                                />
                                                <p className="mt-2 text-xs text-muted-foreground">{authPage.commercial_register_hint}</p>
                                                {errors.commercial_register_file && <p className="form-error">{errors.commercial_register_file}</p>}
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="form-label">{authPage.merchant_categories} <span className="text-[var(--color-danger-strong)]">*</span></label>
                                                {!visibleCategoryGroups ? (
                                                    <p className="rounded-md border border-dashed border-border bg-card/70 px-4 py-3 text-sm text-muted-foreground sm:col-span-2 lg:col-span-3">{authPage.merchant_categories_prompt}</p>
                                                ) : (
                                                    <div className="space-y-4">
                                                        {visibleCategoryGroups.map((group, index) => (
                                                            <section key={index}>
                                                                {group.title && <h3 className="mb-3 text-sm font-bold text-foreground">{group.title}</h3>}
                                                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                                    {group.rows.map((category) => (
                                                                        <CategoryOption key={category.id} category={category} checked={categoryIds.includes(category.id)} onToggle={toggleCategory} />
                                                                    ))}
                                                                </div>
                                                            </section>
                                                        ))}
                                                    </div>
                                                )}
                                                {errors.category_ids && <p className="form-error">{errors.category_ids}</p>}
                                            </div>

                                            <TextField id="address" label={authPage.store_address} placeholder={authPage.store_address_placeholder} value={form.address} onChange={(e) => set('address')(e.target.value)} error={errors.address} />
                                            <TextareaField id="description" label={authPage.store_description} rows={3} placeholder={authPage.store_description_placeholder} value={form.description} onChange={(e) => set('description')(e.target.value)} error={errors.description} />
                                        </div>
                                    </div>
                                )}

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
