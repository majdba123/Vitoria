import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Home, Search } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { LanguageSwitcher } from '@/Components/workspace/LanguageSwitcher';
import { ThemeToggle } from '@/Components/workspace/ThemeToggle';
import { useI18n } from '@/hooks/use-i18n';

export default function NotFound() {
    const { common } = useI18n();

    const goBack = () => {
        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        window.location.assign(route('home'));
    };

    return (
        <>
            <Head title={common.not_found_title}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <main className="relative flex min-h-svh flex-col overflow-hidden bg-background">
                <div className="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div className="absolute -top-36 end-[-8rem] size-96 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute -bottom-48 start-[-10rem] size-[30rem] rounded-full bg-accent/60 blur-3xl dark:bg-accent/20" />
                </div>

                <header className="relative z-10 flex items-center justify-between gap-4 px-5 py-5 sm:px-8 lg:px-12">
                    <Link href={route('home')} className="inline-flex min-h-11 items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <img src="/images/vetora-logo-transparent.png" alt="" className="size-9 object-contain" />
                        <span className="font-display text-lg font-bold text-foreground">Vetora</span>
                    </Link>
                    <div className="flex items-center gap-1">
                        <LanguageSwitcher />
                        <ThemeToggle label={common.toggle_theme} />
                    </div>
                </header>

                <section className="relative z-10 flex flex-1 items-center px-5 py-12 sm:px-8 lg:px-12">
                    <div className="mx-auto grid w-full max-w-6xl gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.72fr)] lg:items-center">
                        <div className="max-w-2xl">
                            <p className="text-xs font-semibold uppercase tracking-[0.1em] text-primary rtl:normal-case rtl:tracking-normal">{common.not_found_eyebrow}</p>
                            <h1 className="mt-5 max-w-xl font-display text-4xl font-bold leading-tight text-foreground sm:text-5xl lg:text-6xl">{common.not_found_heading}</h1>
                            <p className="mt-5 max-w-[60ch] text-base leading-7 text-muted-foreground sm:text-lg">{common.not_found_description}</p>

                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild size="lg" className="min-h-11">
                                    <Link href={route('home')}><Home className="size-4" />{common.return_home}</Link>
                                </Button>
                                <Button asChild variant="outline" size="lg" className="min-h-11">
                                    <Link href={route('products.index')}><Search className="size-4" />{common.browse_products}</Link>
                                </Button>
                                <Button type="button" variant="ghost" size="lg" className="min-h-11" onClick={goBack}>
                                    <ArrowLeft className="size-4 rtl:rotate-180" />{common.go_back}
                                </Button>
                            </div>
                        </div>

                        <div className="relative mx-auto flex aspect-square w-full max-w-sm items-center justify-center" aria-hidden="true">
                            <div className="absolute inset-0 rounded-full border border-border bg-card/70 shadow-[var(--shadow-2)]" />
                            <div className="absolute inset-[12%] rounded-full border border-dashed border-primary/35" />
                            <span className="relative font-display text-[8rem] font-bold leading-none tracking-[-0.08em] text-primary sm:text-[10rem]">404</span>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
