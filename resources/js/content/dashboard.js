export const navItems = [
    { id: 'dashboard', labelKey: 'dashboard', href: '/dashboard', icon: 'home' },
    {
        id: 'orders',
        labelKey: 'orders',
        icon: 'box',
        children: [
            { labelKey: 'createOrder', href: '/dashboard/orders/create' },
            { labelKey: 'orderHistory', href: '/dashboard/orders/history' },
            { labelKey: 'repeatedOrders', href: '/dashboard/orders/repeated' },
        ],
    },
    { id: 'pricing', labelKey: 'pricing', href: '/dashboard/pricing', icon: 'dollar' },
    { id: 'billing', labelKey: 'billing', href: '/dashboard/billing', icon: 'wallet' },
    { id: 'faqs', labelKey: 'faqsHelp', href: '/dashboard/faqs', icon: 'help' },
];
