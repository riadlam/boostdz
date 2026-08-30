import i18n from '../i18n';

/** @param {import('i18next').TFunction} t */
export function getGlobalOrderRules(t) {
    const rules = t('globalRules', { ns: 'validation', returnObjects: true });
    return Array.isArray(rules) ? rules : [];
}

/** @param {import('i18next').TFunction} t */
export function getCategoryOrderRules(t, platformSlug, categorySlug) {
    const notes = [];

    if (platformSlug === 'instagram' && categorySlug === 'followers') {
        notes.push(t('categoryRules.igFollowersFlag', { ns: 'validation' }));
        notes.push(t('categoryRules.igFollowersUsername', { ns: 'validation' }));
    }

    if (['likes', 'views', 'comments', 'shares', 'page_likes'].includes(categorySlug) || categorySlug?.startsWith('reaction')) {
        notes.push(t('categoryRules.postUrl', { ns: 'validation' }));
    }

    if (categorySlug === 'comments') {
        notes.push(t('categoryRules.commentsLines', { ns: 'validation' }));
    }

    if (categorySlug === 'followers' || categorySlug === 'members') {
        notes.push(t('categoryRules.publicProfile', { ns: 'validation' }));
    }

    return notes;
}

function serviceTypeValue(service) {
    return String(service?.type || service?.meta?.jenis || '').toLowerCase().trim();
}

export function isCustomCommentsPackage(service) {
    if (service?.is_custom_comments_package) return true;
    const type = serviceTypeValue(service);
    if (type.includes('custom comments package')) return true;
    return false;
}

export function isCustomCommentsService(service) {
    if (!service) return false;
    if (service.requires_custom_comments) return true;
    const type = serviceTypeValue(service);
    if (type.includes('custom comment')) return true;
    // BuzzerPanel uses type/jenis "Comment" for custom comments services.
    if (type === 'comment') return true;
    return false;
}

export function parseCommentLines(text) {
    return String(text || '')
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean);
}

export function formatCommentsForApi(lines) {
    return (Array.isArray(lines) ? lines : parseCommentLines(lines)).join('\n');
}

export function validateCustomComments({ service, quantity, commentsText, t }) {
    const translate =
        t ||
        ((key, options) =>
            i18n.t(key, {
                ns: 'validation',
                ...options,
            }));

    if (!isCustomCommentsService(service)) {
        return { ok: true, message: null, lines: [] };
    }

    const lines = parseCommentLines(commentsText);
    const qty = Number(quantity) || 0;

    if (lines.length === 0) {
        return { ok: false, message: translate('commentsRequired'), lines };
    }

    if (isCustomCommentsPackage(service)) {
        return { ok: true, message: null, lines };
    }

    if (lines.length !== qty) {
        return {
            ok: false,
            message: translate('commentsCountMismatch', { count: lines.length, qty }),
            lines,
        };
    }

    return { ok: true, message: null, lines };
}

export function previewComments(text, maxLines = 2) {
    const lines = parseCommentLines(text);
    if (lines.length === 0) return null;
    const shown = lines.slice(0, maxLines);
    const rest = lines.length - shown.length;
    return {
        lines: shown,
        total: lines.length,
        rest,
        label: rest > 0 ? `${shown.join(' · ')} (+${rest} more)` : shown.join(' · '),
    };
}

export const CHECKOUT_DRAFT_KEY = 'boostdz.checkoutDraft';

export function saveCheckoutDraft(draft) {
    try {
        sessionStorage.setItem(CHECKOUT_DRAFT_KEY, JSON.stringify(draft));
    } catch {
        // ignore
    }
}

export function loadCheckoutDraft() {
    try {
        const raw = sessionStorage.getItem(CHECKOUT_DRAFT_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export function clearCheckoutDraft() {
    try {
        sessionStorage.removeItem(CHECKOUT_DRAFT_KEY);
    } catch {
        // ignore
    }
}

export function isCheckoutDraftValid(draft) {
    if (!draft || typeof draft !== 'object') {
        return false;
    }

    const serviceId = Number(draft.serviceId);
    const quantity = Number(draft.quantity);
    const charge = Number(draft.charge);
    const link = String(draft.link || '').trim();

    return serviceId > 0 && quantity > 0 && charge > 0 && link !== '';
}

export const checkoutBankDetails = {
    accountName: 'BOOSTDZ SARL',
    bankName: 'Algérie Poste — CCP',
    ccpAccount: '0012345678 90',
    rip: '007 99999 001234567890 12',
    baridimobId: '00799999001234567890',
};
