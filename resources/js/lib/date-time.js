export function formatDateTime(value, locale = 'en', options = {}) {
    if (value === null || value === undefined || value === '') return '';

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const resolvedLocale = resolveLocale(locale);

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

export function formatDate(value, locale = 'en', options = {}) {
    return formatDateTime(value, locale, { dateStyle: 'medium', timeStyle: undefined, ...options });
}

export function formatNumber(value, locale = 'en', options = {}) {
    const number = Number(value ?? 0);
    if (!Number.isFinite(number)) return '';

    return new Intl.NumberFormat(resolveLocale(locale), options).format(number);
}

export function formatCurrency(value, locale = 'en', currency = 'SYP') {
    return new Intl.NumberFormat(resolveLocale(locale), {
        style: 'currency',
        currency,
        currencyDisplay: 'code',
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function resolveLocale(locale) {
    return locale === 'ar' ? 'ar-SY-u-nu-latn' : 'en-US';
}
