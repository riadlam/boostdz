import { api } from './api';
import { roundDzd } from './formatMoney';

let cachedSettings = null;
let settingsPromise = null;

export async function fetchCheckoutSettings() {
    if (cachedSettings) {
        return cachedSettings;
    }

    if (!settingsPromise) {
        settingsPromise = api('/checkout/settings')
            .then((data) => {
                cachedSettings = {
                    minimum_amount_dzd: roundDzd(data.minimum_amount_dzd),
                    minimum_topup_dzd: roundDzd(data.minimum_topup_dzd ?? data.minimum_amount_dzd),
                };
                return cachedSettings;
            })
            .catch((error) => {
                settingsPromise = null;
                throw error;
            });
    }

    return settingsPromise;
}

export function isBelowMinimum(charge, minimum) {
    const min = roundDzd(minimum);
    if (min <= 0) {
        return false;
    }

    return roundDzd(charge) < min;
}

export function isMinimumCheckoutError(error) {
    if (!error) {
        return false;
    }

    return error.code === 'minimum_checkout_not_met' || error.payload?.code === 'minimum_checkout_not_met';
}

export function minimumCheckoutFromError(error) {
    const payload = error?.payload || error;

    return {
        minimum: roundDzd(payload.minimum_amount_dzd),
        charge: roundDzd(payload.charge_dzd),
        message: payload.message || error?.message || null,
    };
}
