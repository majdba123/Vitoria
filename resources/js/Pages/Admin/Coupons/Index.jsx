import { useState } from 'react';
import { Plus, Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { TextField, SelectField, TextareaField } from '@/Components/admin/form/FormField';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { useAdminList } from '@/hooks/use-admin-list';
import { useAdminForm } from '@/hooks/use-admin-form';

const emptyForm = { code: '', title: '', discount_type: 'percentage', discount_value: '', starts_at: '', ends_at: '', usage_limit: '', is_active: true, description: '' };

function toDateInput(value) {
    if (!value) return '';
    const normalized = String(value).replace(' ', 'T');
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(normalized)) return normalized.slice(0, 16);
    const date = new Date(value);
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function statusTone(status) {
    if (status === 'active') return 'success';
    if (status === 'expired') return 'danger';
    return 'warning';
}

export default function CouponsIndex() {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [flash, setFlash] = useState(null);

    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/coupons', {
        page,
        search: search || undefined,
        status: statusFilter === 'all' ? undefined : statusFilter,
    });
    const { submit, errors, generalError, isSubmitting } = useAdminForm();

    const showFlash = (message) => {
        setFlash(message);
        setTimeout(() => setFlash(null), 4500);
    };

    const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

    const openCreate = () => {
        setEditingId(null);
        setForm(emptyForm);
        setModalOpen(true);
    };

    const openEdit = (coupon) => {
        setEditingId(coupon.id);
        setForm({
            code: coupon.code || '',
            title: coupon.title || '',
            discount_type: coupon.discount_type || 'percentage',
            discount_value: String(coupon.discount_value ?? ''),
            starts_at: toDateInput(coupon.starts_at),
            ends_at: toDateInput(coupon.ends_at),
            usage_limit: coupon.usage_limit ? String(coupon.usage_limit) : '',
            is_active: !!coupon.is_active,
            description: coupon.description || '',
        });
        setModalOpen(true);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        if (form.discount_type === 'percentage' && Number(form.discount_value) > 100) return;

        const payload = {
            code: form.code.trim(),
            title: form.title.trim(),
            description: form.description.trim() || null,
            discount_type: form.discount_type,
            discount_value: form.discount_value,
            starts_at: form.starts_at || null,
            ends_at: form.ends_at || null,
            usage_limit: form.usage_limit || null,
            is_active: form.is_active,
        };

        try {
            await submit(editingId ? 'put' : 'post', editingId ? `/api/admin/coupons/${editingId}` : '/api/admin/coupons', payload);
            showFlash(editingId ? 'Coupon updated successfully.' : 'Coupon created successfully.');
            setModalOpen(false);
            reload();
        } catch {
            // handled by hook
        }
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/coupons/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            showFlash('Coupon deleted successfully.');
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        { key: 'code', label: 'Code', render: (row) => <span className="font-mono text-xs font-bold">{row.code}</span> },
        { key: 'title', label: 'Title', render: (row) => <span className="font-semibold text-foreground">{row.title}</span> },
        { key: 'discount', label: 'Discount', render: (row) => (row.discount_type === 'percentage' ? `${row.discount_value}%` : `${Number(row.discount_value).toLocaleString()} SYP`) },
        { key: 'starts_at', label: 'Start', render: (row) => (row.starts_at ? new Date(row.starts_at).toLocaleString() : '—') },
        { key: 'ends_at', label: 'End', render: (row) => (row.ends_at ? new Date(row.ends_at).toLocaleString() : '—') },
        { key: 'status', label: 'Status', render: (row) => <StatusBadge tone={statusTone(row.status)}>{row.status}</StatusBadge> },
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
        <AdminLayout title="Coupons">
            <PageHeader
                title="Coupons"
                copy="Create and manage coupons with status and date windows."
                actions={
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="size-4" />
                        Add Coupon
                    </Button>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <Card className="border-border/80 shadow-none">
                <CardContent className="flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <label className="mb-1.5 block text-sm font-medium">Search</label>
                        <Input placeholder="Code or title" value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} />
                    </div>
                    <div className="w-full sm:w-64">
                        <label className="mb-1.5 block text-sm font-medium">Status</label>
                        <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(1); }}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="expired">Expired</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle="No coupons found." />

            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{editingId ? 'Edit Coupon' : 'Create Coupon'}</DialogTitle>
                    </DialogHeader>

                    {generalError && <p className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-danger-strong)]">{generalError}</p>}

                    <form onSubmit={handleSubmit} id="coupon-form" className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField id="code" label="Code" placeholder="SAVE10" value={form.code} onChange={(e) => set('code')(e.target.value)} error={errors.code} />
                            <TextField id="title" label="Title" placeholder="Welcome Discount" value={form.title} onChange={(e) => set('title')(e.target.value)} error={errors.title} />
                            <SelectField id="discount_type" label="Type" value={form.discount_type} onValueChange={set('discount_type')} options={[{ value: 'percentage', label: 'Percentage' }, { value: 'fixed', label: 'Fixed' }]} error={errors.discount_type} />
                            <TextField id="discount_value" label="Value" type="number" step="0.01" max={form.discount_type === 'percentage' ? 100 : undefined} placeholder="10" value={form.discount_value} onChange={(e) => set('discount_value')(e.target.value)} error={errors.discount_value} />
                            <TextField id="starts_at" label="Start date & time" type="datetime-local" step="60" value={form.starts_at} onChange={(e) => set('starts_at')(e.target.value)} error={errors.starts_at} />
                            <TextField id="ends_at" label="End date & time" type="datetime-local" step="60" value={form.ends_at} onChange={(e) => set('ends_at')(e.target.value)} error={errors.ends_at} />
                            <TextField id="usage_limit" label="Usage limit" type="number" min="1" placeholder="Optional" value={form.usage_limit} onChange={(e) => set('usage_limit')(e.target.value)} error={errors.usage_limit} />
                            <div className="flex items-end gap-3">
                                <label className="text-sm font-medium">Active</label>
                                <Switch checked={form.is_active} onCheckedChange={set('is_active')} />
                            </div>
                        </div>
                        <TextareaField id="description" label="Description" rows={3} placeholder="Optional description" value={form.description} onChange={(e) => set('description')(e.target.value)} error={errors.description} />
                    </form>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>Cancel</Button>
                        <Button type="submit" form="coupon-form" disabled={isSubmitting}>
                            {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                            Save Coupon
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title="Delete coupon"
                description={`Delete coupon "${deleteTarget?.code ?? ''}"? This cannot be undone.`}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
