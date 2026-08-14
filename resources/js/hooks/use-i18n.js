import { usePage } from '@inertiajs/react';

/**
 * Shared translation groups (admin/nav/common) exposed via
 * HandleInertiaRequests::share() — mirrors the __() calls the Blade
 * workspace layouts used, so React and Blade read the exact same lang files.
 */
export function useI18n() {
    const { props } = usePage();

    return props.i18n ?? {
        admin: {}, vendor: {}, syndicate: {}, employee: {}, nav: {}, common: {}, lang: {},
        products: {}, categories: {}, category: {}, cart: {}, checkout: {}, orders: {}, footer: {}, home: {}, startup: {}, profile: {}, authPage: {}, preferences: {}, addresses: {}, pagination: {},
    };
}

export function useAuthUser() {
    const { props } = usePage();

    return props.auth?.user ?? null;
}

/**
 * SSR-safe locale reader. Components previously read `document.documentElement.lang`
 * directly during render, which crashes under Inertia SSR (no `document` in Node) —
 * `locale` is already shared on every request via HandleInertiaRequests, so this reads
 * from page props instead and works identically on the server and the client.
 */
export function useLocale() {
    const { props } = usePage();

    return props.locale === 'ar' ? 'ar' : 'en';
}
