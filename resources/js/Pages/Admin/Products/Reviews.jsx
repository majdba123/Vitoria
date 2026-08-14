import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Star } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Pagination } from '@/Components/admin/Pagination';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

function Stars({ rating }) {
    return (
        <span className="flex items-center gap-0.5">
            {Array.from({ length: 5 }).map((_, i) => (
                <Star key={i} className={`size-3.5 ${i < rating ? 'fill-[var(--color-warning-500)] text-[var(--color-warning-500)]' : 'text-muted-foreground/30'}`} />
            ))}
        </span>
    );
}

export default function ProductReviews({ product, reviews }) {
    const { admin, common, nav } = useI18n();
    const { props } = usePage();
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        router.delete(route('admin.products.reviews.destroy', [product.id, deleteTarget.id]), {
            preserveScroll: true,
            onFinish: () => {
                setIsDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <AdminLayout title={`${admin.reviews} — ${product.name}`}>
            <PageHeader
                breadcrumb={[
                    { label: admin.manage_products_title, href: route('admin.products.index') },
                    { label: product.name, href: route('admin.products.show', product.id) },
                    { label: admin.reviews },
                ]}
                title={`${admin.all_reviews_for ?? 'All reviews for'} ${product.name}`}
                copy={`${reviews.total} ${reviews.total === 1 ? admin.review_singular ?? 'review' : admin.review_plural ?? 'reviews'} ${admin.total_suffix ?? 'total'}`}
                actions={
                    <>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('admin.products.show', product.id)}>{admin.view_product ?? 'View product'}</Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('admin.products.index')}>{admin.back_to_products ?? 'Back to products'}</Link>
                        </Button>
                    </>
                }
            />

            {props.flash?.message && (
                <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{props.flash.message}</p>
            )}

            <Card className="gap-0 border-border/80 py-0 shadow-none">
                {reviews.data.length === 0 ? (
                    <CardContent className="py-14 text-center">
                        <p className="text-sm font-medium text-foreground">{admin.no_reviews_yet ?? 'No reviews yet.'}</p>
                        <Button asChild variant="outline" size="sm" className="mt-4">
                            <Link href={route('admin.products.index')}>{admin.back_to_products ?? 'Back to products'}</Link>
                        </Button>
                    </CardContent>
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{admin.th_user}</TableHead>
                                    <TableHead>{admin.th_rating}</TableHead>
                                    <TableHead>{admin.th_comment}</TableHead>
                                    <TableHead>{admin.th_date}</TableHead>
                                    <TableHead className="text-end">{admin.th_actions}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {reviews.data.map((review) => (
                                    <TableRow key={review.id}>
                                        <TableCell className="font-medium">{review.user?.name ?? '—'}</TableCell>
                                        <TableCell><Stars rating={Number(review.rating || 0)} /></TableCell>
                                        <TableCell className="max-w-xs text-muted-foreground">{review.body || '—'}</TableCell>
                                        <TableCell className="text-muted-foreground">{review.created_at ? new Date(review.created_at).toLocaleDateString() : '—'}</TableCell>
                                        <TableCell className="text-end">
                                            <button type="button" onClick={() => setDeleteTarget(review)} className="text-xs font-medium text-[var(--color-danger-strong)] hover:underline">
                                                {common.delete ?? 'Delete'}
                                            </button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <Pagination
                            meta={{ current_page: reviews.current_page, last_page: reviews.last_page, total: reviews.total }}
                            onPrev={() => router.get(route('admin.products.reviews', product.id), { page: reviews.current_page - 1 }, { preserveScroll: true })}
                            onNext={() => router.get(route('admin.products.reviews', product.id), { page: reviews.current_page + 1 }, { preserveScroll: true })}
                        />
                    </>
                )}
            </Card>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={common.delete ?? 'Delete review'}
                description={admin.confirm_delete_review ?? 'Are you sure you want to delete this review?'}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
