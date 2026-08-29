import i18n, { intlLocale } from '../i18n';

/**
 * @param {string | null | undefined} iso
 * @param {{ withYear?: boolean }} [options]
 */
export function formatDateTime(iso, { withYear = false } = {}) {
    if (!iso) return i18n.t('common:emDash');

    try {
        const options = {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        };

        if (withYear) {
            options.year = 'numeric';
        }

        return new Date(iso).toLocaleString(intlLocale(i18n.language), options);
    } catch {
        return iso;
    }
}
