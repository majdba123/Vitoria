import { useState } from 'react';
import { Plus, Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
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

const emptyForm = { slug: '', title_en: '', title_ar: '', content_en: '', content_ar: '', meta_title: '', meta_description: '', is_published: true };

export default function PagesIndex() {
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
            showFlash(editingId ? 'Page updated successfully.' : 'Page created successfully.');
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
            showFlash('Page deleted successfully.');
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        { key: 'slug', label: 'Slug', render: (row) => <span className="font-mono text-xs">{row.slug}</span> },
        { key: 'title', label: 'Title', render: (row) => <span className="font-semibold text-foreground">{row.title_en}</span> },
        { key: 'status', label: 'Status', render: (row) => <StatusBadge tone={row.is_published ? 'success' : 'warning'}>{row.is_published ? 'Published' : 'Draft'}</StatusBadge> },
        {
            key: 'actions',
            label: 'Actions',
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button variant="outline" size="sm" onClick={() => openEdit(row)}>Edit</Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>Delete</Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Pages">
            <PageHeader
                title="Pages"
                copy="Manage static content pages (About, Terms, Privacy, FAQ...)."
                actions={
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        Add Page
                    </Button>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle="No pages found." />

            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editingId ? 'Edit Page' : 'Create Page'}</DialogTitle>
                    </DialogHeader>

                    {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} id="page-form" className="max-h-[60vh] space-y-4 overflow-y-auto pe-1">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="slug" label="Slug" placeholder="about-us" value={form.slug} onChange={(e) => set('slug')(e.target.value)} error={errors.slug} />
                            <div className="flex items-end gap-3">
                                <label className="text-sm font-medium">Published</label>
                                <Switch checked={form.is_published} onCheckedChange={set('is_published')} />
                            </div>
                            <TextField id="title_en" label="Title (EN)" value={form.title_en} onChange={(e) => set('title_en')(e.target.value)} error={errors.title_en} />
                            <TextField id="title_ar" label="Title (AR)" dir="rtl" value={form.title_ar} onChange={(e) => set('title_ar')(e.target.value)} error={errors.title_ar} />
                        </div>
                        <TextareaField id="content_en" label="Content (EN)" rows={5} value={form.content_en} onChange={(e) => set('content_en')(e.target.value)} error={errors.content_en} />
                        <TextareaField id="content_ar" label="Content (AR)" rows={5} dir="rtl" value={form.content_ar} onChange={(e) => set('content_ar')(e.target.value)} error={errors.content_ar} />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="meta_title" label="Meta title" value={form.meta_title} onChange={(e) => set('meta_title')(e.target.value)} error={errors.meta_title} />
                            <TextField id="meta_description" label="Meta description" value={form.meta_description} onChange={(e) => set('meta_description')(e.target.value)} error={errors.meta_description} />
                        </div>
                    </form>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>Cancel</Button>
                        <Button type="submit" form="page-form" disabled={isSubmitting}>
                            {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                            Save Page
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title="Delete page"
                description={`Delete page "${deleteTarget?.slug ?? ''}"? This cannot be undone.`}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
