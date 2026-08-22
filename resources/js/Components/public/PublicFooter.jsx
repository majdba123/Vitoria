import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin } from 'lucide-react';
import { useI18n, useLocale } from '@/hooks/use-i18n';

/**
 * lucide-react dropped brand/logo glyphs, so these three social marks are
 * inlined directly (standard, widely-reused icon path data) rather than
 * pulling in a second icon package for three links.
 */
function FacebookIcon(props) {
    return (
        <svg viewBox="0 0 320 512" fill="currentColor" aria-hidden="true" {...props}>
            <path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z" />
        </svg>
    );
}

function InstagramIcon(props) {
    return (
        <svg viewBox="0 0 448 512" fill="currentColor" aria-hidden="true" {...props}>
            <path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z" />
        </svg>
    );
}

function TwitterIcon(props) {
    return (
        <svg viewBox="0 0 512 512" fill="currentColor" aria-hidden="true" {...props}>
            <path d="M459.4 151.7c.325 4.548.325 9.097.325 13.65 0 138.7-105.6 298.6-298.6 298.6-59.45 0-114.7-17.22-161.1-47.11 8.447.974 16.57 1.299 25.34 1.299 49.06 0 94.16-16.57 130.3-44.83-46.13-.975-84.79-31.19-98.11-72.77 6.498.974 12.99 1.624 19.82 1.624 9.421 0 18.84-1.3 27.61-3.573-48.08-9.747-84.14-51.98-84.14-102.9v-1.299c13.97 7.797 30.21 12.67 47.43 13.32-28.26-18.84-46.78-51.01-46.78-87.39 0-19.49 5.197-37.36 14.29-52.95 51.65 63.7 129.3 105.3 216.4 109.8-1.624-7.797-2.599-15.92-2.599-24.04 0-57.83 46.78-104.9 104.9-104.9 30.21 0 57.5 12.67 76.66 33.14 23.72-4.548 46.46-13.32 66.6-25.34-7.798 24.37-24.37 44.83-46.13 57.83 21.12-2.273 41.58-8.122 60.42-16.24-14.29 20.79-32.16 39.31-52.63 54.25z" />
        </svg>
    );
}

const SOCIAL_LINKS = [
    { key: 'facebook_url', Icon: FacebookIcon, labelKey: 'follow_facebook' },
    { key: 'instagram_url', Icon: InstagramIcon, labelKey: 'follow_instagram' },
    { key: 'twitter_url', Icon: TwitterIcon, labelKey: 'follow_twitter' },
];

export function PublicFooter() {
    const { footer, home, nav } = useI18n();
    const locale = useLocale();
    const { props } = usePage();
    const settings = props.footerSettings ?? {};

    return (
        <footer className="site-footer">
            <div className="footer-shell">
                <div>
                    <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="col-span-1 sm:col-span-2 lg:col-span-2">
                            <Link href={route('home')} className="inline-flex items-center gap-3 text-2xl font-black tracking-tight text-foreground">
                                <img src="/images/vetora-logo-transparent.png" alt="Vetora" className="h-12 w-auto object-contain" />
                            </Link>
                            <p className="mt-4 max-w-md text-sm leading-7 text-muted-foreground">
                                {locale === 'ar' ? home.tagline : (settings.about_description || home.tagline)}
                            </p>
                            {SOCIAL_LINKS.some(({ key }) => settings[key]) && (
                                <div className="mt-5 flex items-center gap-3">
                                    {SOCIAL_LINKS.map(({ key, Icon, labelKey }) => settings[key] && (
                                        <a
                                            key={key}
                                            href={settings[key]}
                                            target="_blank"
                                            rel="noopener noreferrer me"
                                            aria-label={footer[labelKey]}
                                            className="flex size-9 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                                        >
                                            <Icon className="size-4" />
                                        </a>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div>
                            <h2 className="text-xs font-semibold uppercase tracking-[0.12em] text-foreground">{footer.shop}</h2>
                            <ul className="mt-4 space-y-3 text-sm">
                                <li><Link href={route('categories.index')} className="text-muted-foreground transition-colors hover:text-primary">{nav.categories}</Link></li>
                                <li><Link href={route('products.index')} className="text-muted-foreground transition-colors hover:text-primary">{footer.all_products}</Link></li>
                                <li><Link href={route('faq')} className="text-muted-foreground transition-colors hover:text-primary">{nav.faq}</Link></li>
                            </ul>
                        </div>

                        <div>
                            <h2 className="text-xs font-semibold uppercase tracking-[0.12em] text-foreground">{footer.account}</h2>
                            <ul className="mt-4 space-y-3 text-sm">
                                <li><Link href={route('login')} className="text-muted-foreground transition-colors hover:text-primary">{nav.sign_in}</Link></li>
                                <li><Link href={route('register')} className="text-muted-foreground transition-colors hover:text-primary">{footer.create_account}</Link></li>
                            </ul>
                        </div>

                        <div>
                            <h2 className="text-xs font-semibold uppercase tracking-[0.12em] text-foreground">{footer.contact}</h2>
                            <ul className="mt-4 space-y-3 text-sm">
                                <li><Link href={route('contact')} className="text-muted-foreground transition-colors hover:text-primary">{footer.contact_us}</Link></li>
                                {settings.contact_email && (
                                    <li className="flex items-center gap-2 text-muted-foreground">
                                        <Mail className="size-4 shrink-0" />
                                        <a href={`mailto:${settings.contact_email}`} className="hover:text-primary">{settings.contact_email}</a>
                                    </li>
                                )}
                                {settings.contact_address && (
                                    <li className="flex items-center gap-2 text-muted-foreground">
                                        <MapPin className="size-4 shrink-0" />
                                        {settings.contact_address}
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>

                    <div className="mt-8 flex flex-col items-center justify-between gap-4 border-t border-border/70 pt-5 sm:flex-row">
                        <p className="text-xs text-muted-foreground">&copy; {new Date().getFullYear()} Vetora. {footer.rights}</p>
                        <div className="flex gap-6 text-xs text-muted-foreground">
                            <Link href={route('pages.show', { slug: 'privacy-policy' })} className="hover:text-foreground">{footer.privacy}</Link>
                            <Link href={route('pages.show', { slug: 'terms-of-service' })} className="hover:text-foreground">{footer.terms}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    );
}
