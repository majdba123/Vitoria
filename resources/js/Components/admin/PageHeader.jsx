import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

/**
 * Shared header for every admin list/detail/form page: optional breadcrumb,
 * title, copy, and a right-aligned actions slot. Replaces the repeated
 * .page-header / breadcrumb <nav> markup at the top of every Blade view.
 */
export function PageHeader({ breadcrumb, title, copy, actions }) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
                {breadcrumb && breadcrumb.length > 0 && (
                    <nav className="mb-2 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                        {breadcrumb.map((crumb, index) => (
                            <span key={crumb.label} className="flex items-center gap-1.5">
                                {index > 0 && <ChevronRight className="size-3.5 rtl:rotate-180" />}
                                {crumb.href ? (
                                    <Link href={crumb.href} className="hover:text-foreground">
                                        {crumb.label}
                                    </Link>
                                ) : (
                                    <span className="font-semibold text-foreground">{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </nav>
                )}
                <h1 className="text-xl font-bold tracking-tight text-foreground sm:text-2xl">{title}</h1>
                {copy && <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{copy}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}
