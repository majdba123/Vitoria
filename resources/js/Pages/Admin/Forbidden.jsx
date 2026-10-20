import { Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useI18n } from '@/hooks/use-i18n';

export default function Forbidden() {
    const { admin, common } = useI18n();

    return (
        <AdminLayout title={admin.rbac_forbidden_title}>
            <Card className="mx-auto w-full max-w-xl">
                <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
                    <span className="flex size-14 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                        <ShieldAlert className="size-7" aria-hidden="true" />
                    </span>
                    <h2 className="font-display text-xl font-semibold text-foreground">{admin.rbac_forbidden_title}</h2>
                    <p className="max-w-[48ch] text-sm leading-6 text-muted-foreground">{admin.rbac_forbidden_copy ?? common.forbidden}</p>
                    <Button asChild className="min-h-11">
                        <Link href={route('admin.dashboard')}>{admin.rbac_forbidden_back}</Link>
                    </Button>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
