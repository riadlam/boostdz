import { Link } from 'react-router-dom';
import { ArrowRight, Plus } from 'lucide-react';
import { InstagramIcon, TikTokIcon, YouTubeIcon } from '../../components/PlatformIcons';
import {
    DashboardPanel,
    DashboardStat,
    DashboardStatGrid,
    DashboardTable,
} from '../../components/dashboard/DashboardPanel';
import { cn } from '../../lib/cn';
import {
    dashboardStats,
    dashboardUser,
    newsItems,
    presets,
    recentOrders,
} from '../../content/dashboard';

function greeting() {
    const h = new Date().getHours();
    if (h < 12) return { text: 'Good morning', emoji: '☀️' };
    if (h < 18) return { text: 'Good afternoon', emoji: '👋' };
    return { text: 'Good evening', emoji: '🌙' };
}

function PlatformBadge({ platform }) {
    const map = {
        instagram: InstagramIcon,
        tiktok: TikTokIcon,
        youtube: YouTubeIcon,
    };
    const Icon = map[platform] || InstagramIcon;
    return (
        <div className="flex size-8 items-center justify-center rounded-lg border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)]">
            <Icon className="size-4" />
        </div>
    );
}

function PresetCard({ preset }) {
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
                <div className="mt-0.5 text-xs text-muted-foreground">{preset.subtitle}</div>
            </div>
            <div className="mt-4 flex items-center justify-between border-t border-[var(--color-dash-border-subtle)] pt-3">
                <span className={cn('text-sm font-semibold tabular-nums', tone.price)}>${preset.price.toFixed(2)}</span>
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

const statusStyles = {
    completed: { dot: 'bg-emerald-500', label: 'Completed' },
    processing: { dot: 'bg-blue-500', label: 'Processing' },
    pending: { dot: 'bg-amber-500', label: 'Pending' },
};

function StatusBadge({ status }) {
    const s = statusStyles[status] || statusStyles.pending;
    return (
        <span className="dash-badge">
            <span className={cn('dash-badge-dot', s.dot)} />
            {s.label}
        </span>
    );
}

const tagTone = {
    emerald: 'border-emerald-200/70 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300',
    blue: 'border-blue-200/70 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300',
    purple: 'border-purple-200/70 bg-purple-50 text-purple-700 dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-300',
};

export default function DashboardHome() {
    const g = greeting();

    return (
        <div className="space-y-4 py-1" data-test-id="dashboard-page">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                    <div className="flex size-10 items-center justify-center rounded-full border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-nav-active)] text-sm font-bold text-primary">
                        {dashboardUser.avatarInitials}
                    </div>
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {g.text}, {dashboardUser.firstName} {g.emoji}
                        </h1>
                        <p className="text-sm text-muted-foreground">Pick a preset, or just see how you&apos;re growing.</p>
                    </div>
                </div>
                <div className="flex w-full items-center justify-between gap-3 sm:w-auto sm:justify-end">
                    <div className="leading-tight sm:text-right">
                        <div className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Balance</div>
                        <div className="text-lg font-semibold tabular-nums">${dashboardUser.balance.toFixed(2)}</div>
                    </div>
                    <Link
                        to="/dashboard/billing"
                        className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground transition hover:bg-primary/90"
                    >
                        <Plus className="size-3.5" />
                        Top up
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
                {presets.map((p) => (
                    <PresetCard key={p.id} preset={p} />
                ))}
            </div>

            <DashboardPanel
                title="What's new"
                bodyClassName="p-0"
            >
                <DashboardTable>
                    <thead>
                        <tr>
                            <th>Update</th>
                            <th className="hidden sm:table-cell">Category</th>
                            <th className="hidden md:table-cell">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        {newsItems.map((n) => (
                            <tr key={n.id}>
                                <td>
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={cn(
                                                'flex size-8 shrink-0 items-center justify-center rounded-lg border text-sm',
                                                tagTone[n.tagTone],
                                            )}
                                        >
                                            {n.emoji}
                                        </div>
                                        <div className="min-w-0">
                                            <div className="truncate font-medium">{n.title}</div>
                                            <div className="truncate text-xs text-muted-foreground">{n.body}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="hidden sm:table-cell">
                                    <span className={cn('dash-badge border', tagTone[n.tagTone])}>{n.tag}</span>
                                </td>
                                <td className="hidden text-muted-foreground md:table-cell">{n.when}</td>
                            </tr>
                        ))}
                    </tbody>
                </DashboardTable>
            </DashboardPanel>

            <DashboardPanel
                title="Your recent activity"
                action={
                    <Link
                        to="/dashboard/orders/history"
                        className="text-xs font-medium text-primary transition hover:text-primary/80"
                    >
                        See all
                    </Link>
                }
                bodyClassName="p-0"
            >
                <DashboardTable>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th className="hidden sm:table-cell">Order</th>
                            <th>Status</th>
                            <th className="hidden md:table-cell">Progress</th>
                            <th className="text-right">Amount</th>
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
                                            <div className="text-xs text-muted-foreground sm:hidden">{order.id}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="hidden font-mono text-xs text-muted-foreground sm:table-cell">{order.id}</td>
                                <td>
                                    <StatusBadge status={order.status} />
                                </td>
                                <td className="hidden md:table-cell">
                                    {order.status === 'processing' ? (
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
                                <td className="text-right font-medium tabular-nums">${order.amount.toFixed(2)}</td>
                            </tr>
                        ))}
                    </tbody>
                </DashboardTable>
            </DashboardPanel>
        </div>
    );
}
