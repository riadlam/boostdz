import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ArrowRight, Search } from 'lucide-react';
import { DashboardPanel, DashboardTable } from '../../components/dashboard/DashboardPanel';
import { ApiError } from '../../lib/api';
import { loadCatalogPricingRows } from '../../lib/catalogPricing';
import { formatDzd, formatDzdAmount } from '../../lib/formatMoney';
import { cn } from '../../lib/cn';
import { getPlatformIcon, platformIcons } from '../../lib/platformIcons';

function PlatformIcon({ platformId, className }) {
    const Icon = getPlatformIcon(platformId);

    return (
        <div
            className={cn(
                'flex size-8 shrink-0 items-center justify-center rounded-lg border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)]',
                className,
            )}
        >
            <Icon className="size-4" />
        </div>
    );
}

export default function Pricing() {
    const { t } = useTranslation(['pricing', 'common']);
    const [platform, setPlatform] = useState('all');
    const [query, setQuery] = useState('');
    const [rows, setRows] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let cancelled = false;

        (async () => {
            setLoading(true);
            setError('');

            try {
                const loaded = await loadCatalogPricingRows();
                if (!cancelled) {
                    setRows(loaded);
                }
            } catch (err) {
                if (!cancelled) {
                    setError(err instanceof ApiError ? err.message : t('pricing:loadError'));
                    setRows([]);
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [t]);

    const pricingPlatforms = useMemo(() => {
        const unique = new Map();

        for (const row of rows) {
            if (!unique.has(row.platformId)) {
                unique.set(row.platformId, row.platformName);
            }
        }

        return [
            { id: 'all', label: t('pricing:allPlatforms') },
            ...[...unique.entries()].map(([id, label]) => ({ id, label })),
        ];
    }, [rows, t]);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();

        return rows.filter((row) => {
            if (platform !== 'all' && row.platformId !== platform) {
                return false;
            }

            if (!q) {
                return true;
            }

            return (
                row.label.toLowerCase().includes(q) ||
                row.platformName.toLowerCase().includes(q) ||
                row.categoryName.toLowerCase().includes(q)
            );
        });
    }, [platform, query, rows]);

    return (
        <div className="space-y-4 py-1" data-test-id="pricing-page">
            <div>
                <h1 className="text-xl font-semibold tracking-tight">{t('pricing:title')}</h1>
                <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{t('pricing:subtitle')}</p>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex gap-1 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    {pricingPlatforms.map((tab) => {
                        const active = platform === tab.id;
                        const Icon = tab.id !== 'all' ? platformIcons[tab.id] : null;

                        return (
                            <button
                                key={tab.id}
                                type="button"
                                data-test-id={`pricing-tab-${tab.id}`}
                                onClick={() => setPlatform(tab.id)}
                                className={cn(
                                    'inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                                    active
                                        ? 'border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] text-foreground shadow-sm'
                                        : 'border-transparent bg-transparent text-muted-foreground hover:border-[var(--color-dash-border-subtle)] hover:bg-[var(--color-dash-surface)] hover:text-foreground',
                                )}
                            >
                                {Icon ? <Icon className="size-3.5" /> : null}
                                {tab.label}
                            </button>
                        );
                    })}
                </div>

                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                    <input
                        type="search"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder={t('pricing:searchPlaceholder')}
                        data-test-id="pricing-search"
                        className="h-9 w-full rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] py-2 pr-3 pl-9 text-sm outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-primary/30"
                    />
                </div>
            </div>

            <DashboardPanel
                title={t('pricing:categoryCount', { count: filtered.length })}
                bodyClassName="p-0"
            >
                {loading ? (
                    <div className="p-6 text-sm text-muted-foreground">{t('pricing:loading')}</div>
                ) : error ? (
                    <div className="p-6 text-sm text-red-600 dark:text-red-400">{error}</div>
                ) : (
                    <DashboardTable>
                        <thead>
                            <tr>
                                <th>{t('common:table.category')}</th>
                                <th className="hidden sm:table-cell">{t('common:table.min')}</th>
                                <th className="hidden sm:table-cell">{t('common:table.max')}</th>
                                <th className="hidden md:table-cell">{t('common:table.pricePer1k')}</th>
                                <th>{t('common:table.startingAt')}</th>
                                <th className="text-right">{t('common:table.action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-10 text-center text-sm text-muted-foreground">
                                        {t('pricing:noResults')}
                                    </td>
                                </tr>
                            ) : (
                                filtered.map((row) => (
                                    <tr key={row.id}>
                                        <td>
                                            <div className="flex items-center gap-3">
                                                <PlatformIcon platformId={row.platformId} />
                                                <div className="min-w-0">
                                                    <div className="font-medium">{row.label}</div>
                                                    <div className="text-xs text-muted-foreground sm:hidden">
                                                        {formatDzdAmount(row.min)} – {formatDzdAmount(row.max)}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="hidden tabular-nums text-muted-foreground sm:table-cell">
                                            {formatDzdAmount(row.min)}
                                        </td>
                                        <td className="hidden tabular-nums text-muted-foreground sm:table-cell">
                                            {formatDzdAmount(row.max)}
                                        </td>
                                        <td className="hidden font-medium tabular-nums md:table-cell">
                                            {formatDzd(row.pricePer1k)}
                                        </td>
                                        <td>
                                            <div className="font-medium tabular-nums">{formatDzd(row.startingPrice)}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {t('common:units', { count: row.startingAmount })}
                                            </div>
                                        </td>
                                        <td className="text-right">
                                            <Link
                                                to={row.orderHref}
                                                className="inline-flex items-center gap-1 rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)] px-2.5 py-1.5 text-xs font-medium transition hover:bg-[var(--color-dash-row-hover)]"
                                            >
                                                {t('pricing:order')}
                                                <ArrowRight className="size-3" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </DashboardTable>
                )}
            </DashboardPanel>

            <p className="text-xs leading-relaxed text-muted-foreground">{t('pricing:footerNote')}</p>
        </div>
    );
}
