import { useEffect, useMemo, useState } from 'react';
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
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
import { useTheme } from '../../components/ThemeProvider';
import { useAuth } from '../../context/AuthContext';
import { cn } from '../../lib/cn';
import { navItems } from '../../content/dashboard';

const iconMap = {
    home: Home,
    box: Box,
    dollar: DollarSign,
    wallet: Wallet,
    help: HelpCircle,
};

function SidebarNav({ onNavigate }) {
    const location = useLocation();
    const [openGroups, setOpenGroups] = useState(() => ({
        orders: location.pathname.includes('/orders'),
    }));

    useEffect(() => {
        setOpenGroups((prev) => ({
            ...prev,
            orders: location.pathname.includes('/orders') ? true : prev.orders,
        }));
    }, [location.pathname]);

    return (
        <ul className="flex w-full min-w-0 flex-col gap-1 p-2">
            {navItems.map((item) => {
                const Icon = iconMap[item.icon] || Home;

                if (item.children) {
                    const open = openGroups[item.id];
                    const childActive = item.children.some((c) => location.pathname.startsWith(c.href.split('?')[0]));
                    return (
                        <li key={item.id}>
                            <button
                                type="button"
                                onClick={() => setOpenGroups((s) => ({ ...s, [item.id]: !s[item.id] }))}
                                className={cn(
                                    'dash-nav-item flex h-10 w-full items-center gap-2 rounded-lg px-3 transition',
                                    childActive
                                        ? 'dash-nav-active'
                                        : 'text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground',
                                )}
                            >
                                <Icon className={cn('size-4.5', childActive ? 'text-primary' : 'text-muted-foreground')} />
                                <span className="flex-1 text-left">{item.label}</span>
                                <ChevronRight
                                    className={cn('size-4 text-muted-foreground transition-transform', open && 'rotate-90')}
                                />
                            </button>
                            {open && (
                                <ul className="mt-1 ml-4 space-y-0.5 border-l border-[var(--color-dash-border)] pl-3">
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
                                                {child.label}
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
                                    <span>{item.label}</span>
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
    const { theme, toggle } = useTheme();
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [loggingOut, setLoggingOut] = useState(false);

    const name = user?.name || 'User';
    const initials = name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('') || 'U';
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
            <div className="flex items-center gap-2 rounded-lg p-2 hover:bg-[var(--color-dash-row-hover)]">
                <div className="flex size-8 items-center justify-center rounded-lg bg-primary text-xs font-bold text-primary-foreground">
                    {initials}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-1.5">
                        <span className="truncate text-[0.9375rem] font-semibold">{name}</span>
                        <span className="rounded-md bg-primary px-1.5 py-0.5 text-[0.6875rem] font-bold text-white tabular-nums">
                            {balance.toFixed(0)} DA
                        </span>
                    </div>
                    <p className="truncate text-[0.8125rem] font-medium text-muted-foreground">{user?.email || 'BOOSTDZ'}</p>
                </div>
                <button
                    type="button"
                    onClick={toggle}
                    aria-label="Toggle theme"
                    className="flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                    {theme === 'dark' ? <Sun className="size-4" /> : <Moon className="size-4" />}
                </button>
                <button
                    type="button"
                    onClick={handleLogout}
                    disabled={loggingOut}
                    aria-label="Sign out"
                    className="flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                >
                    <LogOut className="size-4" />
                </button>
            </div>
        </div>
    );
}

function Sidebar({ mobileOpen, onClose }) {
    return (
        <>
            {mobileOpen && (
                <button
                    type="button"
                    aria-label="Close menu"
                    className="fixed inset-0 z-40 bg-black/40 md:hidden"
                    onClick={onClose}
                />
            )}
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-[var(--color-dash-sidebar)] transition-transform duration-300 md:static md:z-auto md:w-56 md:translate-x-0 md:bg-transparent',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="dash-sidebar-panel flex h-full flex-col rounded-none md:rounded-l-2xl md:p-1">
                    <div className="flex items-center justify-between gap-2 px-4 pt-4 pb-2 md:px-3">
                        <BrandLogo className="h-8" href="/dashboard" />
                        <button type="button" className="p-1 md:hidden" onClick={onClose} aria-label="Close">
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

function breadcrumbLabel(pathname) {
    if (pathname === '/dashboard') return 'Dashboard';
    if (pathname.includes('/orders/create')) return 'Create Order';
    if (pathname.includes('/checkout/ccp-baridimob')) return 'CCP / BaridiMob';
    if (pathname.includes('/checkout')) return 'Checkout';
    if (pathname.includes('/orders/history')) return 'Order History';
    if (pathname.includes('/orders/repeated')) return 'Repeated Orders';
    if (pathname.includes('/orders')) return 'Orders';
    if (pathname.includes('/pricing')) return 'Pricing';
    if (pathname.includes('/billing')) return 'Billing';
    if (pathname.includes('/faqs')) return 'FAQs & Help';
    return 'Dashboard';
}

export default function DashboardLayout() {
    const [mobileOpen, setMobileOpen] = useState(false);
    const location = useLocation();
    const crumb = useMemo(() => breadcrumbLabel(location.pathname), [location.pathname]);

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
                                    aria-label="Open menu"
                                >
                                    <Menu className="size-4" />
                                </button>
                                <button
                                    type="button"
                                    className="hidden size-7 items-center justify-center rounded-md opacity-70 hover:bg-muted md:flex"
                                    aria-label="Sidebar"
                                >
                                    <PanelLeft className="size-4" />
                                </button>
                                <div className="mx-2 hidden h-3 w-px bg-border sm:block" />
                                <nav aria-label="breadcrumb" className="text-[0.9375rem] font-semibold text-foreground">
                                    <span>{crumb}</span>
                                </nav>
                            </div>
                        </header>

                        <div className="relative min-h-0 flex-1 overflow-y-auto px-4 py-3">
                            <Outlet />
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
