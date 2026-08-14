import { Head } from '@inertiajs/react';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import { SyndicateSidebar } from '@/Components/syndicate/SyndicateSidebar';
import { SyndicateHeader } from '@/Components/syndicate/SyndicateHeader';

export default function SyndicateLayout({ title, children }) {
    return (
        <SidebarProvider style={{ '--sidebar-width': '17rem' }}>
            <Head title={title} />
            <SyndicateSidebar />
            <SidebarInset>
                <SyndicateHeader title={title} />
                <main className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">{children}</main>
            </SidebarInset>
        </SidebarProvider>
    );
}
