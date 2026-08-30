export const PAYMENT_OPTION_DEFS = [
    {
        id: 'edahabia',
        icons: ['/images/payments/baridimob.svg', '/images/payments/ccp.svg'],
        action: 'redirect_gateway',
    },
    {
        id: 'ccp-baridimob',
        icons: ['/images/payments/baridimob.svg', '/images/payments/ccp.svg'],
        action: 'navigate',
    },
    {
        id: 'algerie-post',
        icons: ['/images/payments/algerie-post.png'],
        action: 'place_order',
    },
];

/**
 * @param {import('i18next').TFunction} t
 * @param {'checkout' | 'billing'} context
 */
export function getPaymentOptions(t, context = 'checkout') {
    const isBilling = context === 'billing';

    return PAYMENT_OPTION_DEFS.map((def) => {
        if (def.id === 'edahabia') {
            return {
                ...def,
                title: t('payment.edahabiaTitle', { ns: 'checkout' }),
                hint: t('payment.edahabiaHintInstant', { ns: 'checkout' }),
                description: t(isBilling ? 'payment.edahabiaDescBilling' : 'payment.edahabiaDescCheckout', { ns: 'checkout' }),
            };
        }

        if (def.id === 'ccp-baridimob') {
            return {
                ...def,
                title: t('payment.ccpTitle', { ns: 'checkout' }),
                hint: t('payment.ccpHintManual', { ns: 'checkout' }),
                description: t(isBilling ? 'payment.ccpDescBilling' : 'payment.ccpDescCheckout', { ns: 'checkout' }),
            };
        }

        return {
            ...def,
            title: t('payment.algerieTitle', { ns: 'checkout' }),
            hint: t(isBilling ? 'payment.algerieHintInstant' : 'payment.algerieHintPay', { ns: 'checkout' }),
            description: t(isBilling ? 'payment.algerieDescBilling' : 'payment.algerieDescCheckout', { ns: 'checkout' }),
        };
    });
}

/** @param {string} paymentOptionId */
export function depositMethodForPaymentOption(paymentOptionId) {
    if (paymentOptionId === 'edahabia') {
        return 'edahabia';
    }

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
    if (depositMethod === 'edahabia') {
        return t('payment.labelEdahabia', { ns: 'checkout' });
    }

    if (depositMethod === 'algerie_post') {
        return t('payment.labelAlgeriePost', { ns: 'checkout' });
    }

    if (depositMethod === 'ccp') {
        return t('payment.labelCcp', { ns: 'checkout' });
    }

    return depositMethod || t('emDash', { ns: 'common' });
}
