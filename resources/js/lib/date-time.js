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

/**
 * Money is rendered from ICU parts rather than straight `format()` for two
 * reasons, both of which showed up as visible inconsistencies:
 *
 *  - Without an explicit minimum, ICU drops trailing zeros once
 *    `maximumFractionDigits` is set, so a column read "435,000" next to
 *    "1,234.5" and the decimal points never lined up.
 *  - ICU's Arabic symbol for SYP is "ل.س." with a trailing full stop, while the
 *    invoice and the PDF reports (the `reports.php` lang files) use "ل.س". Same amount,
 *    two spellings, depending on where you looked.
 *
 * Bidi marks ICU emits are left untouched — they isolate the amount correctly
 * when it sits inside an opposite-direction sentence.
 */
export function formatCurrency(value, locale = 'en', currency = 'SYP') {
    const formatter = new Intl.NumberFormat(resolveLocale(locale), {
        style: 'currency',
        currency,
        currencyDisplay: locale === 'ar' ? 'symbol' : 'code',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return formatter
        .formatToParts(Number(value ?? 0))
        .map((part) => (part.type === 'currency' ? part.value.replace(/\.$/, '') : part.value))
        .join('');
}

export function formatPercent(value, locale = 'en', options = {}) {
    const number = Number(value ?? 0);
    if (!Number.isFinite(number)) return '';

    return new Intl.NumberFormat(resolveLocale(locale), {
        style: 'percent',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
        ...options,
    }).format(number / 100);
}

/**
 * Calendar date ("YYYY-MM-DD") in the viewer's own timezone. `toISOString()`
 * would give the UTC date instead, which is the previous day for the first
 * hours after midnight in Damascus (UTC+3).
 */
export function localDateString(date = new Date()) {
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** `localDateString` for the date `days` days before today. */
export function localDateDaysAgo(days) {
    const date = new Date();
    date.setDate(date.getDate() - days);

    return localDateString(date);
}

function resolveLocale(locale) {
    return locale === 'ar' ? 'ar-SY-u-nu-latn' : 'en-US';
}
