function normalizePlatform(slug) {
    if (slug === 'x') return 'twitter';
    return String(slug || '').trim();
}

function normalizeCategory(slug) {
    const value = String(slug || '').trim();
    if (!value) return '';
    if (value.startsWith('reaction_') || value === 'reactions') return 'reactions';
    return value;
}

function isFieldObject(value) {
    return value && typeof value === 'object' && typeof value.label === 'string';
}

function readTargetField(fields, platform, category) {
    if (!fields || typeof fields !== 'object') return null;

    const platformBlock = fields[platform];
    if (platformBlock?.[category] && isFieldObject(platformBlock[category])) {
        return platformBlock[category];
    }

    const defaultBlock = fields._default;
    if (defaultBlock?.[category] && isFieldObject(defaultBlock[category])) {
        return defaultBlock[category];
    }

    if (defaultBlock?._default && isFieldObject(defaultBlock._default)) {
        return defaultBlock._default;
    }

    return null;
}

function serviceNameFallback(service, t) {
    const hay = `${service?.name || ''} ${service?.type || ''} ${service?.description || ''}`.toLowerCase();

    if (hay.includes('follower') || hay.includes('subscribe') || hay.includes('member')) {
        return {
            label: t('linkTypes.username'),
            hint: t('linkTypes.usernameHint'),
            placeholder: '@username',
        };
    }

    if (hay.includes('comment') || hay.includes('like') || hay.includes('view') || hay.includes('reaction')) {
        return {
            label: t('linkTypes.postUrl'),
            hint: t('linkTypes.postUrlHint'),
            placeholder: 'https://…',
        };
    }

    return {
        label: t('linkTypes.linkTarget'),
        hint: t('linkTypes.linkTargetHint'),
        placeholder: 'https://…',
    };
}

/**
 * @param {{ platformSlug?: string, categorySlug?: string, service?: object }} params
 * @param {import('i18next').TFunction} t — orders namespace
 */
export function getTargetFieldMeta({ platformSlug, categorySlug, service }, t) {
    const platform = normalizePlatform(platformSlug);
    const rawCategory = String(categorySlug || '').trim();
    const category = normalizeCategory(rawCategory) || rawCategory;
    const fields = t('targetFields', { returnObjects: true });

    let field =
        readTargetField(fields, platform, category)
        || (rawCategory && rawCategory !== category ? readTargetField(fields, platform, rawCategory) : null);

    if (!field && (!category || category === 'other')) {
        field = serviceNameFallback(service, t);
    }

    if (!field) {
        field = serviceNameFallback(service, t);
    }

    return {
        label: field.label,
        hint: field.hint,
        placeholder: field.placeholder,
    };
}
