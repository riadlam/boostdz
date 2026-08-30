import { useMemo, useState } from 'react';
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
    Box,
    ChevronRight,
    DollarSign,
    HelpCircle,
    Home,
    LogOut,
    Menu,
    Moon,
    PanelLeft,
    Sun,
    Wallet,
    X,
} from 'lucide-react';
import { BrandLogo } from '../../components/Brand';
import LanguageSwitcher from '../../components/LanguageSwitcher';
import { useTheme } from '../../components/ThemeProvider';
import { useAuth } from '../../context/AuthContext';
import { cn } from '../../lib/cn';
import { formatDzd } from '../../lib/formatMoney';
import { navItems } from '../../content/dashboard';

const iconMap = {
    home: Home,
    box: Box,
    dollar: DollarSign,
    wallet: Wallet,
    help: HelpCircle,
};

function SidebarNav({ onNavigate }) {
    const { t } = useTranslation('nav');
    const location = useLocation();
    const [openGroups, setOpenGroups] = useState(() => ({
        orders: true,
    }));

    return (
        <ul className="flex w-full min-w-0 flex-col gap-1 p-2">
            {navItems.map((item) => {
                const Icon = iconMap[item.icon] || Home;

                if (item.children) {
                    const isOrders = item.id === 'orders';
                    const open = isOrders ? true : openGroups[item.id];
                    const groupActive = isOrders || item.children.some((c) => location.pathname.startsWith(c.href.split('?')[0]));

                    return (
                        <li key={item.id}>
                            <button
                                type="button"
                                onClick={() => {
                                    if (isOrders) return;
                                    setOpenGroups((s) => ({ ...s, [item.id]: !s[item.id] }));
                                }}
                                className={cn(
                                    'dash-nav-item flex h-10 w-full items-center gap-2 rounded-lg px-3 transition',
                                    groupActive
                                        ? 'dash-nav-active'
                                        : 'text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground',
                                )}
                            >
                                <Icon className={cn('size-4.5', groupActive ? 'text-primary' : 'text-muted-foreground')} />
                                <span className="flex-1 text-start">{t(item.labelKey)}</span>
                                <ChevronRight
                                    className={cn('size-4 text-muted-foreground transition-transform rtl:rotate-180', open && 'rotate-90 rtl:rotate-90')}
                                />
                            </button>
                            {open && (
                                <ul className="mt-1 ms-4 space-y-0.5 border-s border-[var(--color-dash-border)] ps-3">
                                    {item.children.map((child) => (
                                        <li key={child.href}>
                                            <NavLink
                                                to={child.href}
                                                onClick={onNavigate}
                                                className={({ isActive }) =>
                                                    cn(
                                                        'dash-nav-subitem block rounded-md px-2.5 py-2 transition',
                                                        isActive
                                                            ? 'dash-nav-active rounded-md'
                                                            : 'text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground',
                                                    )
                                                }
                                            >
                                                {t(child.labelKey)}
                                            </NavLink>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </li>
                    );
                }

                return (
                    <li key={item.id}>
                        <NavLink
                            to={item.href}
                            end={item.href === '/dashboard'}
                            onClick={onNavigate}
                            className={({ isActive }) =>
                                cn(
                                    'dash-nav-item flex h-10 w-full items-center gap-2 rounded-lg px-3 transition',
                                    isActive
                                        ? 'dash-nav-active'
                                        : 'text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground',
                                )
                            }
                        >
                            {({ isActive }) => (
                                <>
                                    <Icon className={cn('size-4.5', isActive ? 'text-primary' : 'text-muted-foreground')} />
                                    <span>{t(item.labelKey)}</span>
                                </>
                            )}
                        </NavLink>
                    </li>
                );
            })}
        </ul>
    );
}

function SidebarFooter() {
    const { t } = useTranslation('common');
    const { theme, toggle } = useTheme();
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [loggingOut, setLoggingOut] = useState(false);

    const name = user?.name || t('userFallback');
    const balance = Number(user?.wallet?.available_balance ?? user?.wallet?.balance ?? 0);

    async function handleLogout() {
        setLoggingOut(true);
        try {
            await logout();
            navigate('/auth/sign-in', { replace: true });
        } finally {
            setLoggingOut(false);
        }
    }

    return (
        <div className="mt-auto border-t border-[var(--color-dash-border)] p-2">
            <div className="space-y-2 rounded-lg p-2 hover:bg-[var(--color-dash-row-hover)]">
                <div className="flex items-center gap-2">
                    <div className="min-w-0 flex-1">
                        <span className="block truncate text-[0.9375rem] font-semibold">{name}</span>
                    </div>
                    <div className="flex shrink-0 items-center gap-0.5">
                        <LanguageSwitcher compact />
                        <button
                            type="button"
                            onClick={toggle}
                            aria-label={t('aria.toggleTheme')}
                            className="flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
                        >
                            {theme === 'dark' ? <Sun className="size-4" /> : <Moon className="size-4" />}
                        </button>
                        <button
                            type="button"
                            onClick={handleLogout}
                            disabled={loggingOut}
                            aria-label={t('aria.signOut')}
                            className="flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        >
                            <LogOut className="size-4" />
                        </button>
                    </div>
                </div>
                <Link
                    to="/dashboard/billing"
                    className="flex items-center gap-2 rounded-lg bg-primary/10 px-2.5 py-2 transition hover:bg-primary/15"
                >
                    <Wallet className="size-3.5 shrink-0 text-primary" />
                    <span className="text-[0.6875rem] font-semibold uppercase tracking-wide text-muted-foreground">{t('balance')}</span>
                    <span className="ms-auto text-sm font-bold tabular-nums text-primary">{formatDzd(balance)}</span>
                </Link>
                <p className="truncate px-0.5 text-xs font-medium text-muted-foreground">{user?.email || 'BOOSTDZ'}</p>
            </div>
        </div>
    );
}

function Sidebar({ mobileOpen, onClose }) {
    const { t } = useTranslation('common');

    return (
        <>
            {mobileOpen && (
                <button
                    type="button"
                    aria-label={t('aria.closeMenu')}
                    className="fixed inset-0 z-40 bg-black/40 md:hidden"
                    onClick={onClose}
                />
            )}
            <aside
                className={cn(
                    'fixed inset-y-0 start-0 z-50 flex w-64 flex-col bg-[var(--color-dash-sidebar)] transition-transform duration-300 md:static md:z-auto md:w-56 md:translate-x-0 md:bg-transparent',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full',
                )}
            >
                <div className="dash-sidebar-panel flex h-full flex-col rounded-none md:rounded-s-2xl md:p-1">
                    <div className="flex items-center justify-between gap-2 px-4 pt-4 pb-2 md:px-3">
                        <BrandLogo className="h-8" href="/dashboard" nameClassName="text-sm" />
                        <button type="button" className="p-1 md:hidden" onClick={onClose} aria-label={t('close')}>
                            <X className="size-5" />
                        </button>
                    </div>
                    <div className="min-h-0 flex-1 overflow-y-auto">
                        <SidebarNav onNavigate={onClose} />
                    </div>
                    <SidebarFooter />
                </div>
            </aside>
        </>
    );
}

function breadcrumbKey(pathname) {
    if (pathname === '/dashboard') return 'dashboard';
    if (pathname.includes('/orders/create')) return 'createOrder';
    if (pathname.includes('/checkout/ccp-baridimob')) return 'ccpBaridimob';
    if (pathname.includes('/checkout')) return 'checkout';
    if (pathname.includes('/orders/history')) return 'orderHistory';
    if (pathname.includes('/orders/repeated')) return 'repeatedOrders';
    if (pathname.includes('/orders')) return 'orders';
    if (pathname.includes('/pricing')) return 'pricing';
    if (pathname.includes('/billing')) return 'billing';
    if (pathname.includes('/faqs')) return 'faqsHelp';
    return 'dashboard';
}

export default function DashboardLayout() {
    const { t } = useTranslation(['nav', 'common']);
    const [mobileOpen, setMobileOpen] = useState(false);
    const location = useLocation();
    const crumb = useMemo(() => t(`nav:${breadcrumbKey(location.pathname)}`), [location.pathname, t]);

    return (
        <div className="dash-app dash-app-frame fixed inset-0 flex justify-center overflow-hidden md:px-4 md:py-3">
            <div className="mx-auto flex h-full w-full max-w-[78rem] flex-col">
                <div className="dash-shell flex min-h-0 flex-1 overflow-hidden border border-[var(--color-dash-border)] md:rounded-[1.3rem]">
                    <Sidebar mobileOpen={mobileOpen} onClose={() => setMobileOpen(false)} />

                    <main className="dash-main-well relative flex min-h-0 min-w-0 flex-1 flex-col md:m-2 md:overflow-hidden md:rounded-xl md:border md:border-[var(--color-dash-border)]">
                        <header className="dash-header z-30 flex shrink-0 items-center gap-2 border-b py-2 md:rounded-t-xl">
                            <div className="flex w-full items-center gap-1 px-3">
                                <button
                                    type="button"
                                    className="flex size-7 items-center justify-center rounded-md opacity-70 hover:bg-muted md:hidden"
                                    onClick={() => setMobileOpen(true)}
                                    aria-label={t('common:aria.openMenu')}
                                >
                                    <Menu className="size-4" />
                                </button>
                                <button
                                    type="button"
                                    className="hidden size-7 items-center justify-center rounded-md opacity-70 hover:bg-muted md:flex"
                                    aria-label={t('common:aria.sidebar')}
                                >
                                    <PanelLeft className="size-4" />
                                </button>
                                <div className="mx-2 hidden h-3 w-px bg-border sm:block" />
                                <nav aria-label="breadcrumb" className="min-w-0 truncate text-[0.9375rem] font-semibold text-foreground">
                                    <span className="truncate">{crumb}</span>
                                </nav>
                            </div>
                        </header>

                        <div data-dashboard-scroll className="relative min-h-0 flex-1 overflow-y-auto px-3 py-2 sm:px-4 sm:py-3">
                            <Outlet />
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
