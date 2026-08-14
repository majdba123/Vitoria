import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import EmployeeLayout from '@/Layouts/EmployeeLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n } from '@/hooks/use-i18n';

export default function EmployeeProductsShow({ productId }) {
    const { employee, common } = useI18n();
    const [status, setStatus] = useState('loading');
    const [product, setProduct] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/employee/products/${productId}`, { silent: true }).then((res) => {
            setProduct(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [productId]);

    if (status === 'loading') {
        return (
            <EmployeeLayout title={common.loading ?? 'Loading...'}>
                <Skeleton className="h-96 w-full max-w-5xl" />
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
        <EmployeeLayout title={product.name}>
            <PageHeader
                title={product.name}
                copy={employee.review_product_copy}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('employee.products.index')}>{employee.back_products}</Link>
                    </Button>
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-6 p-5 sm:p-6 lg:grid-cols-2">
                    <div className="overflow-hidden rounded-lg bg-muted">
                        <img src={product.first_photo_url || '/images/product-placeholder.svg'} className="size-full object-cover" alt="" />
                    </div>
                    <div className="space-y-4">
                        <div className="rounded-md bg-muted p-4">
                            <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{employee.description}</p>
                            <p className="mt-2 text-sm leading-7 text-foreground">{product.description}</p>
                        </div>
                        <div className="rounded-md bg-muted p-4">
                            <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{employee.current_status}</p>
                            <p className="mt-2 text-sm font-semibold text-foreground">{product.status}</p>
                        </div>
                        <div className="rounded-md bg-muted p-4">
                            <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{employee.vendor_reason}</p>
                            <p className="mt-2 text-sm font-semibold text-foreground">{product.rejection_reason || employee.no_reason}</p>
                        </div>
                        <Button asChild size="sm">
                            <Link href={route('employee.products.edit', product.id)}>{employee.review_product}</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </EmployeeLayout>
    );
}
