/**
 * Whole DZD amounts — no decimal points in user-facing prices.
 */
export function roundDzd(n) {
    return Math.round(Number(n || 0));
}

export function formatDzd(n) {
    const value = roundDzd(n);
    return `${value.toLocaleString('fr-DZ', { maximumFractionDigits: 0 })} DA`;
}

export function formatDzdAmount(n) {
    return roundDzd(n).toLocaleString('fr-DZ', { maximumFractionDigits: 0 });
}

export function chargeForService(service, quantity) {
    if (!service) return 0;
    return roundDzd((Number(quantity) / 1000) * Number(service.sell_rate_dzd || 0));
}
