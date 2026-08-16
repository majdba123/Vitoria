import { Head } from '@inertiajs/react';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { SyndicateSidebar } from '@/Components/syndicate/SyndicateSidebar';
import { SyndicateHeader } from '@/Components/syndicate/SyndicateHeader';

export default function SyndicateLayout({ title, children }) {
    return (
        <SidebarProvider className="dashboard-body" style={{ '--sidebar-width': '17rem' }}>
            <Head title={title} />
            <SyndicateSidebar />
            <SidebarInset>
                <SyndicateHeader title={title} />
                <div className="dashboard-content @container/main flex flex-1 flex-col gap-5 p-4 sm:gap-6 sm:p-6 lg:p-8">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}
