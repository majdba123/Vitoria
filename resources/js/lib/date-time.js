export function formatDateTime(value, locale = 'en', options = {}) {
    if (value === null || value === undefined || value === '') return '';

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const resolvedLocale = locale === 'ar' ? 'ar' : 'en';

    try {
        return new Intl.DateTimeFormat(resolvedLocale, {
            dateStyle: 'short',
            timeStyle: 'short',
            ...options,
        }).format(date);
    } catch {
        try {
            return date.toLocaleString(resolvedLocale);
        } catch {
            return '';
        }
    }
}
