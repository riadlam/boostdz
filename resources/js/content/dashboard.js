export const dashboardUser = {
    name: 'Riad Laamari',
    firstName: 'Riad',
    workspace: 'BOOSTDZ Workspace',
    balance: 48.5,
    avatarInitials: 'RL',
};

export const presets = [
    {
        id: 'ig-likes',
        title: 'Boost a post',
        subtitle: '1,000 Instagram likes',
        price: 1.4,
        href: '/dashboard/orders/create?product=ig-likes&amount=1000',
        accent: 'rose',
        platform: 'instagram',
    },
    {
        id: 'tt-views',
        title: 'Go viral',
        subtitle: '5,000 TikTok views',
        price: 5,
        href: '/dashboard/orders/create?product=tt-views&amount=5000',
        accent: 'neutral',
        platform: 'tiktok',
    },
    {
        id: 'yt-views',
        title: 'Grow on YouTube',
        subtitle: '10,000 video views',
        price: 22,
        href: '/dashboard/orders/create?product=yt-views&amount=10000',
        accent: 'red',
        platform: 'youtube',
    },
];

export const newsItems = [
    {
        id: 1,
        emoji: '💅',
        title: 'Instagram Likes Quality Improved',
        body: 'We improved Instagram likes quality — you should see better profiles liking your posts and reels.',
        tag: 'Improvement',
        tagTone: 'emerald',
        when: '1 mo ago',
    },
    {
        id: 2,
        emoji: '🎥',
        title: 'TikTok Services a bit slower than usual',
        body: 'TikTok services may take a little longer due to recent platform updates. Thanks for your patience.',
        tag: 'Notice',
        tagTone: 'blue',
        when: '2 mo ago',
    },
    {
        id: 3,
        emoji: '✨',
        title: 'AI Chat — Order with AI & Get Help',
        body: 'Introducing BOOSTDZ AI Chat — place orders and get help the way you want.',
        tag: 'Feature',
        tagTone: 'blue',
        when: '2 mo ago',
    },
    {
        id: 4,
        emoji: '🔄',
        title: 'Automatic Orders for past media',
        body: 'You can now add likes and views to past Instagram & TikTok media with automatic orders.',
        tag: 'Feature',
        tagTone: 'blue',
        when: '2 mo ago',
    },
    {
        id: 5,
        emoji: '🎉',
        title: 'Welcome to BOOSTDZ 2.0',
        body: 'A cleaner experience, AI-first tools, and a stronger focus on delivery quality.',
        tag: 'News',
        tagTone: 'purple',
        when: '3 mo ago',
    },
];

export const recentOrders = [
    {
        id: 'ORD-10482',
        title: '1,000 Instagram Likes',
        platform: 'instagram',
        status: 'processing',
        progress: 73,
        when: '12 min ago',
        amount: 1.4,
    },
    {
        id: 'ORD-10471',
        title: '5,000 TikTok Views',
        platform: 'tiktok',
        status: 'completed',
        progress: 100,
        when: '1 day ago',
        amount: 5,
    },
    {
        id: 'ORD-10455',
        title: '2,000 YouTube Views',
        platform: 'youtube',
        status: 'completed',
        progress: 100,
        when: '3 days ago',
        amount: 4.4,
    },
    {
        id: 'ORD-10440',
        title: '500 Instagram Followers',
        platform: 'instagram',
        status: 'completed',
        progress: 100,
        when: '5 days ago',
        amount: 8.9,
    },
];

export const dashboardStats = [
    { label: 'Orders this week', value: '12', hint: '+3 vs last week', tone: 'primary' },
    { label: 'Completed', value: '9', hint: '75% success rate', tone: 'ok' },
    { label: 'In progress', value: '2', hint: '1 awaiting start', tone: 'warn' },
    { label: 'Spent', value: '$86.40', hint: 'This month', tone: 'spend' },
];

export const navItems = [
    { id: 'dashboard', label: 'Dashboard', href: '/dashboard', icon: 'home' },
    {
        id: 'orders',
        label: 'Orders',
        icon: 'box',
        children: [
            { label: 'Create Order', href: '/dashboard/orders/create' },
            { label: 'Order History', href: '/dashboard/orders/history' },
            { label: 'Repeated Orders', href: '/dashboard/orders/repeated' },
        ],
    },
    { id: 'pricing', label: 'Pricing', href: '/dashboard/pricing', icon: 'dollar' },
    { id: 'billing', label: 'Billing', href: '/dashboard/billing', icon: 'wallet' },
    { id: 'faqs', label: 'FAQs & Help', href: '/dashboard/faqs', icon: 'help' },
];
