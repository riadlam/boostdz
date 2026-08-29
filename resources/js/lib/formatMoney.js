import i18n, { intlLocale } from '../i18n';

/**
 * Whole DZD amounts — no decimal points in user-facing prices.
 */
export function roundDzd(n) {
    return Math.round(Number(n || 0));
}

export function formatDzd(n) {
    const value = roundDzd(n);
    const currency = i18n.t('common:currency');

    return `${value.toLocaleString(intlLocale(i18n.language), { maximumFractionDigits: 0 })} ${currency}`;
}

export function formatDzdAmount(n) {
    return roundDzd(n).toLocaleString(intlLocale(i18n.language), { maximumFractionDigits: 0 });
}

export function chargeForService(service, quantity) {
    if (!service) return 0;
    return roundDzd((Number(quantity) / 1000) * Number(service.sell_rate_dzd || 0));
}
