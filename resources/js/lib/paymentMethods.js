export const PAYMENT_OPTION_DEFS = [
    {
        id: 'ccp-baridimob',
        icons: ['/images/payments/baridimob.svg', '/images/payments/ccp.svg'],
        action: 'navigate',
    },
    {
        id: 'algerie-post',
        icons: ['/images/payments/algerie-post.png'],
        action: 'redirect_gateway',
    },
    {
        id: 'wallet',
        icons: [],
        action: 'wallet',
        checkoutOnly: true,
    },
];

/**
 * @param {import('i18next').TFunction} t
 * @param {'checkout' | 'billing'} context
 * @param {{ walletBalance?: number, orderCharge?: number, formatMoney?: (n: number) => string }} [opts]
 */
export function getPaymentOptions(t, context = 'checkout', opts = {}) {
    const isBilling = context === 'billing';
    const walletBalance = Number(opts.walletBalance ?? 0);
    const orderCharge = Number(opts.orderCharge ?? 0);
    const canPayWithWallet = walletBalance >= orderCharge && orderCharge > 0;
    const formatMoney = opts.formatMoney || ((value) => String(value));

    return PAYMENT_OPTION_DEFS.filter((def) => !isBilling || !def.checkoutOnly).map((def) => {
        if (def.id === 'ccp-baridimob') {
            return {
                ...def,
                title: t('payment.ccpTitle', { ns: 'checkout' }),
                hint: t('payment.ccpHintManual', { ns: 'checkout' }),
                description: t(isBilling ? 'payment.ccpDescBilling' : 'payment.ccpDescCheckout', { ns: 'checkout' }),
            };
        }

        if (def.id === 'wallet') {
            return {
                ...def,
                title: t('payment.walletTitle', { ns: 'checkout' }),
                hint: t('payment.walletHint', { ns: 'checkout' }),
                description: canPayWithWallet
                    ? t('payment.walletDescReady', { ns: 'checkout', balance: formatMoney(walletBalance) })
                    : t('payment.walletDescInsufficient', { ns: 'checkout' }),
                disabled: !canPayWithWallet,
            };
        }

        return {
            ...def,
            title: t('payment.algerieTitle', { ns: 'checkout' }),
            hint: t(isBilling ? 'payment.algerieHintBilling' : 'payment.algerieHintCheckout', { ns: 'checkout' }),
            description: t(isBilling ? 'payment.algerieDescBilling' : 'payment.algerieDescCheckout', { ns: 'checkout' }),
        };
    });
}

/** @param {string} paymentOptionId */
export function depositMethodForPaymentOption(paymentOptionId) {
    if (paymentOptionId === 'algerie-post') {
        return 'algerie_post';
    }

    if (paymentOptionId === 'ccp-baridimob') {
        return 'ccp';
    }

    return null;
}

/**
 * @param {string} depositMethod
 * @param {import('i18next').TFunction} t
 */
export function paymentOptionLabelForDeposit(depositMethod, t) {
    if (depositMethod === 'edahabia' || depositMethod === 'algerie_post') {
        return t('payment.labelAlgeriePost', { ns: 'checkout' });
    }

    if (depositMethod === 'ccp') {
        return t('payment.labelCcp', { ns: 'checkout' });
    }

    return depositMethod || t('emDash', { ns: 'common' });
}
