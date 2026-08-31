export function translatedStatus(value, common) {
    if (!value) {
        return common.not_available;
    }

    return common.status?.[value] ?? common[value] ?? common.not_available;
}

export function translatedEnum(value, fallback, ...dictionaries) {
    if (!value) {
        return fallback;
    }

    for (const dictionary of dictionaries) {
        const translation = dictionary?.[value];

        if (typeof translation === 'string' && translation.length > 0) {
            return translation;
        }
    }

    return fallback;
}
