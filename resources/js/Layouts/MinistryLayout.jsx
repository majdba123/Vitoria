import { Head } from '@inertiajs/react';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { DashboardFooter } from '@/Components/shared/DashboardFooter';
import { MinistrySidebar } from '@/Components/ministry/MinistrySidebar';
import { MinistryHeader } from '@/Components/ministry/MinistryHeader';

export default function MinistryLayout({ title, children }) {
    return (
        <SidebarProvider className="dashboard-body" style={{ '--sidebar-width': '17rem' }}>
            <Head title={title} />
            <MinistrySidebar />
            <SidebarInset>
                <MinistryHeader title={title} />
                <div className="dashboard-content @container/main flex flex-1 flex-col gap-5 p-4 sm:gap-6 sm:p-6 lg:p-8">{children}</div>
                <DashboardFooter />
            </SidebarInset>
        </SidebarProvider>
    );
}
