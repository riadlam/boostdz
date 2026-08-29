import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ArrowRight, Plus } from 'lucide-react';
import {
    DashboardPanel,
    DashboardStat,
    DashboardStatGrid,
    DashboardTable,
} from '../../components/dashboard/DashboardPanel';
import { useAuth } from '../../context/AuthContext';
import { ApiError, ordersApi } from '../../lib/api';
import { cn } from '../../lib/cn';
import { DASHBOARD_PRESET_CONFIG, loadDashboardPresets } from '../../lib/dashboardPresets';
import { formatDateTime } from '../../lib/formatDate';
import { formatDzd } from '../../lib/formatMoney';
import { getPlatformIcon } from '../../lib/platformIcons';

const STATUS_STYLES = {
    completed: { dot: 'bg-emerald-500', key: 'status.completed' },
    processing: { dot: 'bg-blue-500', key: 'status.processing' },
    in_progress: { dot: 'bg-blue-500', key: 'status.inProgress' },
    pending: { dot: 'bg-amber-500', key: 'status.pending' },
    partial: { dot: 'bg-orange-500', key: 'status.partial' },
};

function greetingKey() {
    const h = new Date().getHours();
    if (h < 12) return { key: 'greeting.morning', emoji: '☀️' };
    if (h < 18) return { key: 'greeting.afternoon', emoji: '👋' };
    return { key: 'greeting.evening', emoji: '🌙' };
}

function userInitials(name) {
    if (!name) return '?';

    return name
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function firstNameFrom(name, fallback) {
    return name?.trim().split(/\s+/)[0] || fallback;
}

function startOfWeek(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = day === 0 ? -6 : 1 - day;
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + diff);
    return d;
}

function startOfMonth(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    d.setDate(1);
    return d;
}

function mapOrder(order, t) {
    const platform = String(order.service?.platform || '').toLowerCase();
    const percent = Number(order.delivery?.percent ?? 0);

    return {
        id: order.id,
        title: order.service?.name || t('orders:titleFallback'),
        platform,
        status: order.status || 'pending',
        progress: percent,
        when: formatDateTime(order.created_at),
        amount: Number(order.charge_dzd || 0),
    };
}

function buildDashboardStats(orders, t) {
    const now = new Date();
    const weekStart = startOfWeek(now);
    const monthStart = startOfMonth(now);

    let ordersThisWeek = 0;
    let completed = 0;
    let inProgress = 0;
    let spentThisMonth = 0;

    for (const order of orders) {
        const createdAt = order.created_at ? new Date(order.created_at) : null;
        const status = order.status || 'pending';
        const charge = Number(order.charge_dzd || 0);

        if (createdAt && createdAt >= weekStart) {
            ordersThisWeek += 1;
        }

        if (status === 'completed') {
            completed += 1;
        }

        if (['processing', 'in_progress', 'pending', 'partial'].includes(status)) {
            inProgress += 1;
        }

        if (createdAt && createdAt >= monthStart) {
            spentThisMonth += charge;
        }
    }

    return [
        {
            label: t('dashboard:stats.ordersThisWeek'),
            value: String(ordersThisWeek),
            hint: t('dashboard:stats.ordersPlaced', { count: ordersThisWeek }),
            tone: 'primary',
        },
        {
            label: t('dashboard:stats.completed'),
            value: String(completed),
            hint: t('dashboard:stats.deliveredOrders'),
            tone: 'ok',
        },
        {
            label: t('dashboard:stats.inProgress'),
            value: String(inProgress),
            hint: t('dashboard:stats.activeOrders', { count: inProgress }),
            tone: 'warn',
        },
        {
            label: t('dashboard:stats.spent'),
            value: formatDzd(spentThisMonth),
            hint: t('dashboard:stats.thisMonth'),
            tone: 'spend',
        },
    ];
}

function PlatformBadge({ platform }) {
    const Icon = getPlatformIcon(platform);

    return (
        <div className="flex size-8 items-center justify-center rounded-lg border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)]">
            <Icon className="size-4" />
        </div>
    );
}

function PresetCard({ preset }) {
    const { t } = useTranslation('common');
    const tones = {
        rose: {
            card: 'hover:border-rose-200/80 dark:hover:border-rose-900/50',
            price: 'text-rose-600 dark:text-rose-400',
            btn: 'bg-rose-600 text-white',
        },
        neutral: {
            card: 'hover:border-[var(--color-dash-border)]',
            price: 'text-foreground',
            btn: 'bg-foreground text-background',
        },
        red: {
            card: 'hover:border-red-200/80 dark:hover:border-red-900/50',
            price: 'text-red-600 dark:text-red-400',
            btn: 'bg-red-600 text-white',
        },
    };
    const tone = tones[preset.accent] || tones.neutral;

    return (
        <Link
            to={preset.href}
            className={cn(
                'group flex h-full flex-col rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4 shadow-[0_1px_2px_oklch(0%_0_0_/0.05),0_10px_24px_-14px_oklch(0%_0_0_/0.12)] transition-colors duration-200 hover:border-primary/30 hover:bg-[color-mix(in_oklab,var(--color-primary)_5%,var(--color-dash-surface))]',
                tone.card,
            )}
        >
            <PlatformBadge platform={preset.platform} />
            <div className="mt-3 flex-1">
                <div className="text-sm font-semibold tracking-tight">{preset.title}</div>
                <div className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">{preset.subtitle}</div>
            </div>
            <div className="mt-4 flex items-center justify-between border-t border-[var(--color-dash-border-subtle)] pt-3">
                <div>
                    <span className={cn('text-sm font-semibold tabular-nums', tone.price)}>{formatDzd(preset.price)}</span>
                    <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{t('per1k')}</div>
                </div>
                <span
                    className={cn(
                        'flex size-7 items-center justify-center rounded-full text-white transition-transform duration-200 group-hover:translate-x-0.5',
                        tone.btn,
                    )}
                >
                    <ArrowRight className="size-3.5" />
                </span>
            </div>
        </Link>
    );
}

function PresetCardSkeleton() {
    return (
        <div className="flex h-full flex-col rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">
            <div className="size-8 animate-pulse rounded-lg bg-muted" />
            <div className="mt-3 space-y-2">
                <div className="h-4 w-28 animate-pulse rounded bg-muted" />
                <div className="h-3 w-full animate-pulse rounded bg-muted" />
            </div>
            <div className="mt-4 flex items-center justify-between border-t border-[var(--color-dash-border-subtle)] pt-3">
                <div className="h-4 w-20 animate-pulse rounded bg-muted" />
                <div className="size-7 animate-pulse rounded-full bg-muted" />
            </div>
        </div>
    );
}

function StatusBadge({ status }) {
    const { t } = useTranslation('common');
    const s = STATUS_STYLES[status] || STATUS_STYLES.pending;

    return (
        <span className="dash-badge">
            <span className={cn('dash-badge-dot', s.dot)} />
            {t(s.key)}
        </span>
    );
}

export default function DashboardHome() {
    const { t } = useTranslation(['dashboard', 'common', 'orders']);
    const { user } = useAuth();
    const g = greeting();

    const [presets, setPresets] = useState([]);
    const [presetsLoading, setPresetsLoading] = useState(true);
    const [orders, setOrders] = useState([]);
    const [ordersLoading, setOrdersLoading] = useState(true);
    const [ordersError, setOrdersError] = useState('');

    useEffect(() => {
        let cancelled = false;

        (async () => {
            setPresetsLoading(true);
            try {
                const loaded = await loadDashboardPresets();
                if (!cancelled) setPresets(loaded);
            } catch {
                if (!cancelled) setPresets([]);
            } finally {
                if (!cancelled) setPresetsLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            setOrdersLoading(true);
            setOrdersError('');
            try {
                const data = await ordersApi.list({ per_page: 100 });
                const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
                if (!cancelled) setOrders(rows);
            } catch (err) {
                if (!cancelled) {
                    setOrdersError(err instanceof ApiError ? err.message : t('dashboard:loadOrdersError'));
                }
            } finally {
                if (!cancelled) setOrdersLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [t]);

    const dashboardStats = useMemo(() => buildDashboardStats(orders, t), [orders, t]);
    const recentOrders = useMemo(() => orders.slice(0, 4).map((order) => mapOrder(order, t)), [orders, t]);

    const balance = Number(user?.wallet?.available_balance ?? user?.wallet?.balance ?? 0);
    const displayName = firstNameFrom(user?.name, t('dashboard:nameFallback'));

    return (
        <div className="space-y-4 py-1" data-test-id="dashboard-page">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                    <div className="flex size-10 items-center justify-center rounded-full border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-nav-active)] text-sm font-bold text-primary">
                        {userInitials(user?.name)}
                    </div>
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {t(`dashboard:${g.key}`)}, {displayName} {g.emoji}
                        </h1>
                        <p className="text-sm text-muted-foreground">{t('dashboard:subtitle')}</p>
                    </div>
                </div>
                <div className="flex w-full items-center justify-between gap-3 sm:w-auto sm:justify-end">
                    <div className="leading-tight sm:text-right">
                        <div className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{t('common:balance')}</div>
                        <div className="text-lg font-semibold tabular-nums">{formatDzd(balance)}</div>
                    </div>
                    <Link
                        to="/dashboard/billing"
                        className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground transition hover:bg-primary/90"
                    >
                        <Plus className="size-3.5" />
                        {t('dashboard:topUp')}
                    </Link>
                </div>
            </div>

            <DashboardPanel bodyClassName="p-0">
                <DashboardStatGrid>
                    {dashboardStats.map((s) => (
                        <DashboardStat key={s.label} label={s.label} value={s.value} hint={s.hint} tone={s.tone} />
                    ))}
                </DashboardStatGrid>
            </DashboardPanel>

            <div className="grid gap-3 sm:grid-cols-3">
                {presetsLoading
                    ? DASHBOARD_PRESET_CONFIG.map((preset) => <PresetCardSkeleton key={preset.id} />)
                    : presets.length > 0
                      ? presets.map((preset) => <PresetCard key={preset.id} preset={preset} />)
                      : (
                          <div className="col-span-full rounded-xl border border-dashed border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-6 text-center text-sm text-muted-foreground">
                              {t('dashboard:presetsEmpty')}{' '}
                              <Link to="/dashboard/orders/create" className="font-medium text-primary underline-offset-2 hover:underline">
                                  {t('orders:createOrderLink')}
                              </Link>
                              .
                          </div>
                      )}
            </div>

            <DashboardPanel
                title={t('dashboard:recentActivity')}
                action={
                    <Link
                        to="/dashboard/orders/history"
                        className="text-xs font-medium text-primary transition hover:text-primary/80"
                    >
                        {t('common:seeAll')}
                    </Link>
                }
                bodyClassName="p-0"
            >
                {ordersLoading ? (
                    <div className="p-6 text-sm text-muted-foreground">{t('dashboard:loadingRecentOrders')}</div>
                ) : ordersError ? (
                    <div className="p-6 text-sm text-red-600 dark:text-red-400">{ordersError}</div>
                ) : recentOrders.length === 0 ? (
                    <div className="p-6 text-sm text-muted-foreground">
                        {t('dashboard:noOrdersYet')}{' '}
                        <Link to="/dashboard/orders/create" className="font-medium text-primary underline-offset-2 hover:underline">
                            {t('dashboard:placeFirstOrder')}
                        </Link>
                        .
                    </div>
                ) : (
                    <DashboardTable>
                        <thead>
                            <tr>
                                <th>{t('common:table.service')}</th>
                                <th className="hidden sm:table-cell">{t('common:table.order')}</th>
                                <th>{t('common:table.status')}</th>
                                <th className="hidden md:table-cell">{t('common:table.progress')}</th>
                                <th className="text-right">{t('common:table.amount')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentOrders.map((order) => (
                                <tr key={order.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <PlatformBadge platform={order.platform} />
                                            <div className="min-w-0">
                                                <div className="truncate font-medium">{order.title}</div>
                                                <div className="text-xs text-muted-foreground sm:hidden">#{order.id}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="hidden font-mono text-xs text-muted-foreground sm:table-cell">#{order.id}</td>
                                    <td>
                                        <StatusBadge status={order.status} />
                                    </td>
                                    <td className="hidden md:table-cell">
                                        {['processing', 'in_progress', 'partial'].includes(order.status) ? (
                                            <div className="flex max-w-36 items-center gap-2">
                                                <div className="h-1.5 flex-1 overflow-hidden rounded-full border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)]">
                                                    <div
                                                        className="h-full rounded-full bg-blue-500/80"
                                                        style={{ width: `${order.progress}%` }}
                                                    />
                                                </div>
                                                <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                                                    {order.progress}%
                                                </span>
                                            </div>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">{order.when}</span>
                                        )}
                                    </td>
                                    <td className="text-right font-medium tabular-nums">{formatDzd(order.amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </DashboardTable>
                )}
            </DashboardPanel>
        </div>
    );
}
