export const GLOBAL_ORDER_RULES = [
    'Enter the correct link or username. Private accounts and wrong targets are not refundable.',
    'Do not place another order for the same target until the previous one is completed, partial, or canceled.',
    'Speeds and start times are estimates and can change with server load.',
    'Complaints are accepted after 24 hours from order placement.',
    'If the target already has 100,000+ followers/likes/views/subs, refill protection does not apply.',
    'Partial or canceled orders are refunded automatically to your balance.',
    'Orders cannot be canceled for user input mistakes.',
];

export function categoryOrderRules(platformSlug, categorySlug) {
    const notes = [];

    if (platformSlug === 'instagram' && categorySlug === 'followers') {
        notes.push(
            'Disable Instagram “Flag / Report for review” (Settings → Follow and invite friends) so new followers appear automatically. No refill/refund if this stays enabled.',
            'For followers, enter the username only (not a private account).',
        );
    }

    if (['likes', 'views', 'comments', 'shares', 'page_likes'].includes(categorySlug) || categorySlug?.startsWith('reaction')) {
        notes.push('For likes, views, reactions, and comments, paste the exact post/media URL from the description.');
    }

    if (categorySlug === 'comments') {
        notes.push('For custom comment services, enter one comment per line. Quantity must match the number of comments.');
    }

    if (categorySlug === 'followers' || categorySlug === 'members') {
        notes.push('Make sure the profile is public and the username/link matches the service requirements.');
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

export function validateCustomComments({ service, quantity, commentsText }) {
    if (!isCustomCommentsService(service)) {
        return { ok: true, message: null, lines: [] };
    }

    const lines = parseCommentLines(commentsText);
    const qty = Number(quantity) || 0;

    if (lines.length === 0) {
        return { ok: false, message: 'Enter at least one comment (one per line).', lines };
    }

    if (isCustomCommentsPackage(service)) {
        return { ok: true, message: null, lines };
    }

    if (lines.length !== qty) {
        return {
            ok: false,
            message: `You entered ${lines.length} comment${lines.length === 1 ? '' : 's'} but quantity is ${qty}. They must match.`,
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

export const checkoutBankDetails = {
    accountName: 'BOOSTDZ SARL',
    bankName: 'Algérie Poste — CCP',
    ccpAccount: '0012345678 90',
    rip: '007 99999 001234567890 12',
    baridimobId: '00799999001234567890',
    note: 'Include your BOOSTDZ username in the payment reference.',
};
