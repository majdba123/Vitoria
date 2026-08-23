import { useState } from 'react';
import { Plus, Loader2, Image as ImageIcon } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { TextField, FileField } from '@/Components/admin/form/FormField';
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

const emptyForm = { title_en: '', title_ar: '', link_url: '', sort_order: '0', is_active: true, starts_at: '', ends_at: '', image: null };

function toDateInput(value) {
    if (!value) return '';
    const normalized = String(value).replace(' ', 'T');
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(normalized)) return normalized.slice(0, 16);
    const date = new Date(value);
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export default function BannersIndex() {
    const { admin, common } = useI18n();
    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/banners');
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [modalOpen, setModalOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [preview, setPreview] = useState(null);
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
        setPreview(null);
        setModalOpen(true);
    };

    const openEdit = (banner) => {
        setEditingId(banner.id);
        setForm({
            title_en: banner.title_en || '',
            title_ar: banner.title_ar || '',
            link_url: banner.link_url || '',
            sort_order: String(banner.sort_order ?? 0),
            is_active: !!banner.is_active,
            starts_at: toDateInput(banner.starts_at),
            ends_at: toDateInput(banner.ends_at),
            image: null,
        });
        setPreview(banner.image_path ? `/storage/${banner.image_path}` : null);
        setModalOpen(true);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        const payload = {
            title_en: form.title_en.trim(),
            title_ar: form.title_ar.trim(),
            link_url: form.link_url.trim() || undefined,
            sort_order: form.sort_order || 0,
            is_active: form.is_active ? '1' : '0',
            starts_at: form.starts_at || undefined,
            ends_at: form.ends_at || undefined,
        };
        if (form.image) payload.image = form.image;

        try {
            await submit(editingId ? 'put' : 'post', editingId ? `/api/admin/banners/${editingId}` : '/api/admin/banners', payload, { isMultipart: true });
            showFlash(editingId ? 'Banner updated successfully.' : 'Banner created successfully.');
            setModalOpen(false);
            reload();
        } catch {
            // handled by hook
        }
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/banners/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            showFlash('Banner deleted successfully.');
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        {
            key: 'image',
            label: 'Image',
            render: (row) => (
                <span className="flex h-10 w-16 items-center justify-center overflow-hidden rounded-md bg-accent text-accent-foreground">
                    {row.image_path ? <img src={`/storage/${row.image_path}`} alt="" className="size-full object-cover" /> : <ImageIcon className="size-4" />}
                </span>
            ),
        },
        { key: 'title', label: 'Title', render: (row) => <span className="font-semibold text-foreground">{row.title_en || '—'}</span> },
        { key: 'sort_order', label: 'Sort', render: (row) => row.sort_order ?? 0 },
        { key: 'status', label: 'Status', render: (row) => <StatusBadge tone={row.is_active ? 'success' : 'warning'}>{row.is_active ? 'Active' : 'Inactive'}</StatusBadge> },
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
        <AdminLayout title={admin.banners}>
            <PageHeader
                title={admin.banners}
                copy={admin.banners_copy}
                actions={
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        {admin.add_banner}
                    </Button>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={admin.no_banners_found} />

            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editingId ? admin.edit_banner : admin.create_banner}</DialogTitle>
                    </DialogHeader>

                    {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} id="banner-form" className="min-w-0 space-y-4">
                        <FileField
                            id="image"
                            label={editingId ? admin.banner_image : admin.banner_image_required}
                            preview={preview}
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                set('image')(file);
                                if (file) setPreview(URL.createObjectURL(file));
                            }}
                            error={errors.image}
                        />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="title_en" label={admin.banner_title_en} dir="ltr" value={form.title_en} onChange={(e) => set('title_en')(e.target.value)} error={errors.title_en} />
                            <TextField id="title_ar" label={admin.banner_title_ar} dir="rtl" value={form.title_ar} onChange={(e) => set('title_ar')(e.target.value)} error={errors.title_ar} />
                        </div>
                        <TextField id="link_url" label={admin.banner_link_url} type="url" dir="ltr" placeholder="https://..." value={form.link_url} onChange={(e) => set('link_url')(e.target.value)} error={errors.link_url} />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="sort_order" label={admin.banner_sort_order} type="number" min="0" value={form.sort_order} onChange={(e) => set('sort_order')(e.target.value)} error={errors.sort_order} />
                            <div className="flex items-end gap-3">
                                <label className="text-sm font-medium">{admin.banner_active}</label>
                                <Switch checked={form.is_active} onCheckedChange={set('is_active')} />
                            </div>
                            <TextField id="starts_at" label={admin.banner_starts_at} type="datetime-local" step="60" value={form.starts_at} onChange={(e) => set('starts_at')(e.target.value)} error={errors.starts_at} />
                            <TextField id="ends_at" label={admin.banner_ends_at} type="datetime-local" step="60" value={form.ends_at} onChange={(e) => set('ends_at')(e.target.value)} error={errors.ends_at} />
                        </div>
                    </form>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>{common.cancel ?? 'Cancel'}</Button>
                        <Button type="submit" form="banner-form" disabled={isSubmitting}>
                            {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                            {admin.save_banner}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.delete_banner}
                description={admin.delete_banner_warning}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
