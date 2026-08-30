export const topUpPresets = [1000, 2000, 5000, 10000];

export const ccpDetails = {
    accountName: 'BOOSTDZ',
    ccpAccount: '1234567890 12',
    rip: '007 999 123456789012 34',
};

export const paymentMethods = [
    {
        id: 'ccp',
        label: 'CCP — Bank wire',
        hint: 'Manual processing',
        description: 'Send a wire to our CCP account, then upload your receipt. We credit your balance after verification.',
        processing: 'manual',
    },
    {
        id: 'algerie-post',
        label: 'Algérie Post',
        hint: 'Online payment',
        description: 'Pay through Algérie Post. You will be redirected to complete payment securely.',
        processing: 'gateway',
    },
];

export const billingTransactions = [
    {
        id: 'TXN-8821',
        label: 'Balance top-up',
        amount: 5000,
        method: 'Algérie Post',
        status: 'completed',
        when: 'Aug 20, 2026',
    },
    {
        id: 'TXN-8798',
        label: 'Balance top-up',
        amount: 2000,
        method: 'CCP',
        status: 'pending',
        when: 'Aug 18, 2026',
    },
    {
        id: 'TXN-8755',
        label: 'Balance top-up',
        amount: 10000,
        method: 'Algérie Post',
        status: 'completed',
        when: 'Aug 2, 2026',
    },
];
