import { Head } from '@inertiajs/react';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { AdminSidebar } from '@/Components/admin/AdminSidebar';
import { AdminHeader } from '@/Components/admin/AdminHeader';

export default function AdminLayout({ title, children }) {
    return (
        <SidebarProvider style={{ '--sidebar-width': '17rem' }}>
            <Head title={title} />
            <AdminSidebar />
            <SidebarInset>
                <AdminHeader title={title} />
                <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}
