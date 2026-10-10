import { useState } from 'react';
import { Loader2, MapPin, Plus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Card, CardContent } from '@/Components/ui/card';
import { useAdminList } from '@/hooks/use-admin-list';
import { useAdminForm } from '@/hooks/use-admin-form';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/date-time';

export default function PortsIndex() {
    const { importShipments: t, admin, common } = useI18n();
    const locale = useLocale();
    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/ports', { all: 1 });
    const { submit, errors, generalError, isSubmitting } = useAdminForm();
    const [newName, setNewName] = useState('');
    const [editing, setEditing] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleteError, setDeleteError] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const createPort = async (event) => {
        event.preventDefault();
        try {
            await submit('post', '/api/admin/ports', { name: newName });
            setNewName('');
            reload();
        } catch {
            // errors surfaced by useAdminForm
        }
    };

    const savePort = async (port, changes) => {
        try {
            await submit('put', `/api/admin/ports/${port.id}`, { name: port.name, is_active: port.is_active, ...changes });
            setEditing(null);
            reload();
        } catch {
            // errors surfaced by useAdminForm
        }
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        setDeleteError(null);
        window.axios
            .delete(`/api/admin/ports/${deleteTarget.id}`, { silent: true })
            .then(() => {
                setDeleteTarget(null);
                reload();
            })
            .catch((error) => setDeleteError(error.response?.data?.message ?? common.generic_error))
            .finally(() => setIsDeleting(false));
    };

    const columns = [
        {
            key: 'name',
            label: t.port_name,
            render: (row) =>
                editing?.id === row.id ? (
                    <form
                        className="flex items-center gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            savePort(row, { name: editing.name });
                        }}
                    >
                        <Input aria-label={t.port_name} value={editing.name} onChange={(e) => setEditing({ ...editing, name: e.target.value })} className="h-9 max-w-64" autoFocus />
                        <Button type="submit" size="sm" disabled={isSubmitting}>{common.save_changes}</Button>
                        <Button type="button" size="sm" variant="ghost" onClick={() => setEditing(null)}>{common.cancel}</Button>
                    </form>
                ) : (
                    <span className="flex items-center gap-3">
                        <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">
                            <MapPin className="size-4" />
                        </span>
                        {row.name}
                    </span>
                ),
        },
        { key: 'shipments', label: t.port_shipments, align: 'end', render: (row) => formatNumber(row.import_shipments_count ?? 0, locale) },
        {
            key: 'active',
            label: t.port_active,
            render: (row) => (
                <span className="inline-flex items-center gap-2">
                    <Switch checked={row.is_active} aria-label={`${t.port_active}: ${row.name}`} onCheckedChange={(checked) => savePort(row, { is_active: checked })} />
                    <span className="text-xs text-muted-foreground">{row.is_active ? t.port_active : t.port_inactive}</span>
                </span>
            ),
        },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button variant="outline" size="sm" onClick={() => setEditing({ id: row.id, name: row.name })}>{common.edit}</Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => { setDeleteError(null); setDeleteTarget(row); }}>
                        {common.delete}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={t.ports_title}>
            <PageHeader title={t.ports_title} copy={t.ports_subtitle} />

            <Card className="border-border/80 shadow-none">
                <CardContent className="p-4">
                    <form onSubmit={createPort} className="flex flex-col gap-2 sm:flex-row sm:items-end">
                        <div className="flex-1 sm:max-w-sm">
                            <Label htmlFor="new-port-name" className="mb-1.5 block text-sm font-semibold">{t.port_name}</Label>
                            <Input id="new-port-name" value={newName} onChange={(e) => setNewName(e.target.value)} required aria-invalid={!!errors.name} />
                        </div>
                        <Button type="submit" disabled={isSubmitting || newName.trim() === ''}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" /> : <Plus className="size-4" />}
                            {t.add_port}
                        </Button>
                    </form>
                    {(errors.name || generalError) && <p role="alert" className="mt-2 text-sm font-medium text-[var(--color-danger-strong)]">{errors.name ?? generalError}</p>}
                </CardContent>
            </Card>

            <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={t.no_ports} />

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={t.delete_port_title}
                description={deleteError ?? t.delete_port_warning}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
