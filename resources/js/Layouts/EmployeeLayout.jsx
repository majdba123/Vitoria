import { Head } from '@inertiajs/react';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { EmployeeSidebar } from '@/Components/employee/EmployeeSidebar';
import { EmployeeHeader } from '@/Components/employee/EmployeeHeader';

export default function EmployeeLayout({ title, children }) {
    return (
        <SidebarProvider className="dashboard-body" style={{ '--sidebar-width': '17rem' }}>
            <Head title={title} />
            <EmployeeSidebar />
            <SidebarInset>
                <EmployeeHeader title={title} />
                <div className="dashboard-content @container/main flex flex-1 flex-col gap-5 p-4 sm:gap-6 sm:p-6 lg:p-8">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}
