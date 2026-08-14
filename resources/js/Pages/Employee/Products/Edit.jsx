import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import EmployeeLayout from '@/Layouts/EmployeeLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { TextareaField, SelectField } from '@/Components/admin/form/FormField';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

export default function EmployeeProductsEdit({ productId }) {
    const { employee, common } = useI18n();
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [status, setStatus] = useState('loading');
    const [product, setProduct] = useState(null);
    const [description, setDescription] = useState('');
    const [productStatus, setProductStatus] = useState('pending');
    const [rejectionReason, setRejectionReason] = useState('');
    const [successMessage, setSuccessMessage] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/employee/products/${productId}`, { silent: true }).then((res) => {
            const p = res.data.data;
            setProduct(p);
            setDescription(p.description ?? '');
            setProductStatus(p.status ?? 'pending');
            setRejectionReason(p.rejection_reason ?? '');
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [productId]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSuccessMessage(null);
        const payload = {};
        if (description.trim()) payload.description = description.trim();
        if (productStatus) payload.status = productStatus;
        if (productStatus === 'rejected' && rejectionReason.trim()) payload.rejection_reason = rejectionReason.trim();

        try {
            const data = await submit('put', `/api/employee/products/${productId}`, payload, { isMultipart: true });
            setProduct(data.data);
            setSuccessMessage(data.message ?? common.save ?? 'Saved.');
        } catch {
            // handled by hook
        }
    };

    if (status === 'loading') {
        return (
            <EmployeeLayout title="Product Review">
                <Skeleton className="h-96 w-full max-w-4xl" />
            </EmployeeLayout>
        );
    }

    if (status === 'error' || !product) {
        return (
            <EmployeeLayout title="Product Review">
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">{common.unexpected_error ?? 'An unexpected error occurred.'}</p>
            </EmployeeLayout>
        );
    }

    return (
        <EmployeeLayout title="Product Review">
            <PageHeader title={employee.review_product} copy={employee.review_product_copy} />

            <div className="grid gap-6 lg:grid-cols-[1fr_1.1fr]">
                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-4 p-5 sm:p-6">
                        <div className="aspect-[4/3] overflow-hidden rounded-lg bg-muted">
                            <img src={product.first_photo_url || '/images/product-placeholder.svg'} className="size-full object-cover" alt="" />
                        </div>
                        <div className="space-y-2">
                            <h3 className="text-2xl font-bold text-foreground">{product.name || '-'}</h3>
                            <p className="text-sm leading-7 text-muted-foreground">{product.description}</p>
                        </div>
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <div className="rounded-md bg-muted p-4">
                                <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{employee.current_status}</p>
                                <p className="mt-2 font-semibold text-foreground">{product.status}</p>
                            </div>
                            <div className="rounded-md bg-muted p-4">
                                <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{employee.vendor_reason}</p>
                                <p className="mt-2 font-semibold text-foreground">{product.rejection_reason || employee.no_reason}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-border/80 shadow-none">
                    <CardContent className="space-y-5 p-5 sm:p-6">
                        <div>
                            <h2 className="text-lg font-bold text-foreground">{employee.moderation_form}</h2>
                            <p className="mt-0.5 text-sm text-muted-foreground">{employee.moderation_form_copy}</p>
                        </div>

                        {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}
                        {successMessage && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{successMessage}</p>}

                        <form onSubmit={handleSubmit} className="space-y-5">
                            <TextareaField id="description" label={employee.description} rows={4} value={description} onChange={(e) => setDescription(e.target.value)} error={errors.description} />

                            <SelectField
                                id="status"
                                label={employee.status}
                                value={productStatus}
                                onValueChange={setProductStatus}
                                options={[
                                    { value: 'pending', label: employee.pending },
                                    { value: 'approved', label: employee.approved },
                                    { value: 'rejected', label: employee.rejected },
                                ]}
                                error={errors.status}
                            />

                            {productStatus === 'rejected' && (
                                <TextareaField id="rejection_reason" label={employee.rejection_reason} rows={4} placeholder={employee.rejection_reason_placeholder} value={rejectionReason} onChange={(e) => setRejectionReason(e.target.value)} error={errors.rejection_reason} />
                            )}

                            <div className="flex flex-col-reverse gap-2 border-t border-border pt-5 sm:flex-row sm:justify-end">
                                <Button type="button" variant="outline" onClick={() => window.history.back()}>{common.cancel}</Button>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                                    {common.save}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </EmployeeLayout>
    );
}
