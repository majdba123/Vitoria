import { useState } from 'react';
import { Plus, Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { TextField, TextareaField } from '@/Components/admin/form/FormField';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { useAdminList } from '@/hooks/use-admin-list';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n } from '@/hooks/use-i18n';

const emptyForm = { slug: '', title_en: '', title_ar: '', content_en: '', content_ar: '', meta_title: '', meta_description: '', is_published: true };

export default function PagesIndex() {
    const { admin, common } = useI18n();
    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/pages');
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [modalOpen, setModalOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [flash, setFlash] = useState(null);

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const showFlash = (message) => {
        setFlash(message);
        setTimeout(() => setFlash(null), 4500);
    };

    const openCreate = () => {
        setEditingId(null);
        setForm(emptyForm);
        setModalOpen(true);
    };

    const openEdit = (page) => {
        setEditingId(page.id);
        setForm({
            slug: page.slug || '',
            title_en: page.title_en || '',
            title_ar: page.title_ar || '',
            content_en: page.content_en || '',
            content_ar: page.content_ar || '',
            meta_title: page.meta_title || '',
            meta_description: page.meta_description || '',
            is_published: !!page.is_published,
        });
        setModalOpen(true);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        const payload = {
            slug: form.slug.trim(),
            title_en: form.title_en.trim(),
            title_ar: form.title_ar.trim(),
            content_en: form.content_en,
            content_ar: form.content_ar,
            meta_title: form.meta_title.trim() || null,
            meta_description: form.meta_description.trim() || null,
            is_published: form.is_published,
        };

        try {
            await submit(editingId ? 'put' : 'post', editingId ? `/api/admin/pages/${editingId}` : '/api/admin/pages', payload);
            showFlash(editingId ? admin.page_updated_success : admin.page_created_success);
            setModalOpen(false);
            reload();
        } catch {
            // handled by hook
        }
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/pages/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            showFlash(admin.page_deleted_success);
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        { key: 'slug', label: admin.slug_label, render: (row) => <span className="font-mono text-xs">{row.slug}</span> },
        { key: 'title', label: admin.title_label, render: (row) => <span className="font-semibold text-foreground">{row.title_en}</span> },
        { key: 'status', label: admin.status_label, render: (row) => <StatusBadge tone={row.is_published ? 'success' : 'warning'}>{row.is_published ? admin.published_label : admin.draft_label}</StatusBadge> },
        {
            key: 'actions',
            label: admin.actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button variant="outline" size="sm" onClick={() => openEdit(row)}>{common.edit}</Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>{common.delete}</Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.pages}>
            <PageHeader
                title={admin.pages}
                copy={admin.pages_copy}
                actions={
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        {admin.add_page}
                    </Button>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={admin.no_pages_found} />

            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editingId ? admin.edit_page_title : admin.create_page_title}</DialogTitle>
                    </DialogHeader>

                    {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} id="page-form" className="max-h-[60vh] space-y-4 overflow-y-auto pe-1">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="slug" label={admin.slug_label} placeholder="about-us" value={form.slug} onChange={(e) => set('slug')(e.target.value)} error={errors.slug} />
                            <div className="flex items-end gap-3">
                                <label className="text-sm font-medium">{admin.published_label}</label>
                                <Switch checked={form.is_published} onCheckedChange={set('is_published')} />
                            </div>
                            <TextField id="title_en" label={admin.title_en_label} value={form.title_en} onChange={(e) => set('title_en')(e.target.value)} error={errors.title_en} />
                            <TextField id="title_ar" label={admin.title_ar_label} dir="rtl" value={form.title_ar} onChange={(e) => set('title_ar')(e.target.value)} error={errors.title_ar} />
                        </div>
                        <TextareaField id="content_en" label={admin.content_en_label} rows={5} value={form.content_en} onChange={(e) => set('content_en')(e.target.value)} error={errors.content_en} />
                        <TextareaField id="content_ar" label={admin.content_ar_label} rows={5} dir="rtl" value={form.content_ar} onChange={(e) => set('content_ar')(e.target.value)} error={errors.content_ar} />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="meta_title" label={admin.meta_title_label} value={form.meta_title} onChange={(e) => set('meta_title')(e.target.value)} error={errors.meta_title} />
                            <TextField id="meta_description" label={admin.meta_description_label} value={form.meta_description} onChange={(e) => set('meta_description')(e.target.value)} error={errors.meta_description} />
                        </div>
                    </form>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>{common.cancel}</Button>
                        <Button type="submit" form="page-form" disabled={isSubmitting}>
                            {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                            {admin.save_page_btn}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.delete_page_title}
                description={admin.delete_page_warning.replace(':slug', deleteTarget?.slug ?? '')}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
