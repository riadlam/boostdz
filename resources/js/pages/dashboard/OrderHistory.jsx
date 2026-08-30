import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Check, LoaderCircle, RotateCcw, X } from 'lucide-react';
import { DashboardPanel, DashboardTable } from '../../components/dashboard/DashboardPanel';
import PaymentResultModal from '../../components/PaymentResultModal';
import { ApiError, ordersApi } from '../../lib/api';
import { scrollDashboardToTop } from '../../lib/formScroll';
import { cn } from '../../lib/cn';
import { formatDateTime } from '../../lib/formatDate';
import { formatDzd } from '../../lib/formatMoney';
import { getPlatformIcon } from '../../lib/platformIcons';

const STATUS_STYLES = {
    completed: { dot: 'bg-emerald-500', key: 'status.completed' },
    processing: { dot: 'bg-blue-500', key: 'status.processing' },
    in_progress: { dot: 'bg-blue-500', key: 'status.inProgress' },
    pending: { dot: 'bg-amber-500', key: 'status.pending' },
    partial: { dot: 'bg-orange-500', key: 'status.partial' },
    canceled: { dot: 'bg-muted-foreground', key: 'status.canceled' },
    refunded: { dot: 'bg-violet-500', key: 'status.refunded' },
    failed: { dot: 'bg-red-500', key: 'status.failed' },
};

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

function mapOrder(order, t) {
    const platform = String(order.service?.platform || '').toLowerCase();
    const percent = Number(order.delivery?.percent ?? 0);
    const refillLifetime = Boolean(order.refill_lifetime || order.service?.refill_mode === 'lifetime');

    return {
        id: order.id,
        title: order.service?.name || t('orders:titleFallback'),
        platform,
        status: order.status || 'pending',
        progress: percent,
        deliveryLabel: order.delivery?.label || null,
        when: formatDateTime(order.created_at, { withYear: true }),
        amount: Number(order.charge_dzd || 0),
        canRefill: Boolean(order.can_request_refill),
        refillLifetime,
        refillWarrantyDays: order.refill_warranty_days ? Number(order.refill_warranty_days) : null,
        errorMessage: order.error_message,
    };
}

export default function OrderHistory() {
    const { t } = useTranslation(['orders', 'common']);
    const navigate = useNavigate();
    const location = useLocation();
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [requested, setRequested] = useState({});
    const [toast, setToast] = useState(null);
    const [confirmOrder, setConfirmOrder] = useState(null);
    const [confirmLoading, setConfirmLoading] = useState(false);
    const [confirmError, setConfirmError] = useState('');
    const [paymentNotice, setPaymentNotice] = useState(null);

    function warrantyLabel(order) {
        if (order.refillLifetime) return t('orders:warranty.lifetime');
        if (order.refillWarrantyDays) return t('orders:warranty.days', { days: order.refillWarrantyDays });
        return null;
    }

    useEffect(() => {
        const notice = location.state?.paymentNotice;
        if (!notice?.type) return;

        setPaymentNotice(notice);
        scrollDashboardToTop();
        navigate(`${location.pathname}${location.search}`, { replace: true, state: null });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [location.state?.paymentNotice]);

    useEffect(() => {
        let cancelled = false;
        (async () => {
            setLoading(true);
            setError('');
            try {
                const data = await ordersApi.list({ per_page: 50 });
                const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
                if (!cancelled) setOrders(rows.map((order) => mapOrder(order, t)));
            } catch (err) {
                if (!cancelled) {
                    setError(err instanceof ApiError ? err.message : t('orders:loadError'));
                }
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [t]);

    useEffect(() => {
        if (!toast) return undefined;
        const id = window.setTimeout(() => setToast(null), 3200);
        return () => window.clearTimeout(id);
    }, [toast]);

    useEffect(() => {
        if (!confirmOrder) return undefined;
        function onKey(e) {
            if (e.key === 'Escape' && !confirmLoading) {
                setConfirmOrder(null);
                setConfirmError('');
            }
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [confirmOrder, confirmLoading]);

    function openRefillModal(order) {
        setConfirmError('');
        setConfirmOrder(order);
    }

    function closeRefillModal() {
        if (confirmLoading) return;
        setConfirmOrder(null);
        setConfirmError('');
    }

    async function confirmRefill() {
        if (!confirmOrder) return;
        const order = confirmOrder;
        setConfirmLoading(true);
        setConfirmError('');
        setRequested((prev) => ({ ...prev, [order.id]: 'loading' }));
        try {
            await ordersApi.refill(order.id);
            setRequested((prev) => ({ ...prev, [order.id]: 'done' }));
            setOrders((prev) =>
                prev.map((row) => (row.id === order.id ? { ...row, canRefill: false } : row)),
            );
            setConfirmOrder(null);
            setToast({
                type: 'success',
                title: t('orders:refillSuccessTitle'),
                subtitle: order.title,
            });
        } catch (err) {
            setRequested((prev) => ({ ...prev, [order.id]: undefined }));
            const message = err instanceof ApiError ? err.message : t('common:requestFailed');
            setConfirmError(message);
            setToast({
                type: 'error',
                title: t('orders:refillFailedTitle'),
                subtitle: message,
            });
        } finally {
            setConfirmLoading(false);
        }
    }

    const confirmWarranty = confirmOrder ? warrantyLabel(confirmOrder) : null;

    return (
        <div className="relative space-y-4 py-1">
            <div>
                <h1 className="text-xl font-semibold tracking-tight">{t('orders:orderHistoryTitle')}</h1>
                <p className="mt-1 text-sm text-muted-foreground">{t('orders:orderHistorySubtitle')}</p>
            </div>

            {error ? (
                <div role="alert" className="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    {error}
                </div>
            ) : null}

            <DashboardPanel
                title={t('orders:allOrders')}
                action={
                    <Link
                        to="/dashboard/orders/create"
                        className="text-xs font-medium text-primary transition hover:text-primary/80"
                    >
                        {t('common:newOrder')}
                    </Link>
                }
                bodyClassName="p-0"
            >
                <DashboardTable>
                    <thead>
                        <tr>
                            <th>{t('common:table.service')}</th>
                            <th>{t('common:table.status')}</th>
                            <th className="hidden md:table-cell">{t('common:table.delivery')}</th>
                            <th>{t('common:table.date')}</th>
                            <th className="text-right">{t('common:table.amount')}</th>
                            <th className="text-right"> </th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={6} className="py-10 text-center text-sm text-muted-foreground">
                                    <span className="inline-flex items-center gap-2">
                                        <LoaderCircle className="size-4 animate-spin" />
                                        {t('orders:loadingOrders')}
                                    </span>
                                </td>
                            </tr>
                        ) : orders.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="py-10 text-center text-sm text-muted-foreground">
                                    {t('orders:noOrdersYet')}{' '}
                                    <Link to="/dashboard/orders/create" className="font-semibold text-primary underline-offset-2 hover:underline">
                                        {t('orders:createOne')}
                                    </Link>
                                </td>
                            </tr>
                        ) : (
                            orders.map((order) => {
                                const Icon = getPlatformIcon(order.platform);
                                const refillState = requested[order.id];
                                const showRefill = order.canRefill;
                                const warranty = warrantyLabel(order);
                                const showProgress = ['processing', 'in_progress', 'pending', 'partial'].includes(order.status);

                                return (
                                    <tr key={order.id} className={cn(showRefill && refillState !== 'done' && 'dash-row-refill')}>
                                        <td>
                                            <div className="flex items-center gap-2.5">
                                                <div className="flex size-8 shrink-0 items-center justify-center rounded-lg border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)]">
                                                    <Icon className="size-4" />
                                                </div>
                                                <div className="min-w-0">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="font-medium">{order.title}</span>
                                                        {showRefill && refillState !== 'done' && warranty ? (
                                                            <span className="dash-refill-chip">{t('orders:protected', { warranty })}</span>
                                                        ) : null}
                                                        {refillState === 'done' ? (
                                                            <span className="dash-refill-chip dash-refill-chip-done">{t('orders:refillPending')}</span>
                                                        ) : null}
                                                    </div>
                                                    {order.errorMessage ? (
                                                        <p className="mt-0.5 truncate text-xs text-red-600 dark:text-red-400">{order.errorMessage}</p>
                                                    ) : null}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <StatusBadge status={order.status} />
                                        </td>
                                        <td className="hidden md:table-cell">
                                            {showProgress ? (
                                                <div className="flex max-w-36 items-center gap-2">
                                                    <div className="h-1.5 flex-1 overflow-hidden rounded-full border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)]">
                                                        <div
                                                            className="h-full rounded-full bg-blue-500/80"
                                                            style={{ width: `${Math.min(100, order.progress)}%` }}
                                                        />
                                                    </div>
                                                    <span className="text-xs tabular-nums text-muted-foreground">
                                                        {order.deliveryLabel || `${Math.round(order.progress)}%`}
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    {order.status === 'completed' ? t('orders:delivered') : order.deliveryLabel || t('common:emDash')}
                                                </span>
                                            )}
                                        </td>
                                        <td className="text-muted-foreground">{order.when}</td>
                                        <td className="text-right font-medium tabular-nums">{formatDzd(order.amount)}</td>
                                        <td className="text-right">
                                            {showRefill ? (
                                                refillState === 'done' ? (
                                                    <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                                        <Check className="size-3.5 text-emerald-500" strokeWidth={2.25} />
                                                        {t('common:status.pending')}
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        disabled={refillState === 'loading'}
                                                        onClick={() => openRefillModal(order)}
                                                        className="dash-refill-btn"
                                                    >
                                                        <RotateCcw className="size-3.5" strokeWidth={2} />
                                                        {t('orders:badges.refill')}
                                                    </button>
                                                )
                                            ) : (
                                                <span className="select-none text-xs text-transparent">{t('common:emDash')}</span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </DashboardTable>
            </DashboardPanel>

            {confirmOrder ? (
                <div className="dash-modal-root" role="presentation">
                    <button
                        type="button"
                        className="dash-modal-backdrop"
                        aria-label={t('common:close')}
                        disabled={confirmLoading}
                        onClick={closeRefillModal}
                    />
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="refill-modal-title"
                        className="dash-modal-panel"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.08em] text-muted-foreground">
                                    {t('orders:protection')}
                                </p>
                                <h2 id="refill-modal-title" className="mt-1 text-lg font-semibold tracking-tight">
                                    {t('orders:requestRefill')}
                                </h2>
                            </div>
                            <button
                                type="button"
                                className="dash-modal-close"
                                disabled={confirmLoading}
                                onClick={closeRefillModal}
                                aria-label={t('common:closeDialog')}
                            >
                                <X className="size-4" strokeWidth={2} />
                            </button>
                        </div>

                        <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                            <span className="font-medium text-foreground">{t('orders:refillConfirmTitle')}</span>{' '}
                            {t('orders:refillConfirmBody')}
                        </p>

                        <div className="mt-4 rounded-xl border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)] px-3.5 py-3">
                            <p className="truncate text-sm font-medium">{confirmOrder.title}</p>
                            {confirmWarranty ? (
                                <p className="mt-1.5">
                                    <span className="dash-refill-chip">{t('orders:protected', { warranty: confirmWarranty })}</span>
                                </p>
                            ) : null}
                        </div>

                        {confirmError ? (
                            <p role="alert" className="mt-3 text-sm text-red-600 dark:text-red-400">
                                {confirmError}
                            </p>
                        ) : null}

                        <div className="mt-5 flex flex-wrap items-center justify-end gap-2">
                            <button
                                type="button"
                                className="dash-modal-btn-secondary"
                                disabled={confirmLoading}
                                onClick={closeRefillModal}
                            >
                                {t('common:cancel')}
                            </button>
                            <button
                                type="button"
                                className="dash-modal-btn-primary"
                                disabled={confirmLoading}
                                onClick={confirmRefill}
                            >
                                {confirmLoading ? (
                                    <>
                                        <LoaderCircle className="size-3.5 animate-spin" />
                                        {t('orders:sending')}
                                    </>
                                ) : (
                                    <>
                                        <RotateCcw className="size-3.5" strokeWidth={2} />
                                        {t('orders:confirmRefill')}
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}

            <div
                aria-live="polite"
                className={cn(
                    'pointer-events-none fixed bottom-4 left-3 right-3 z-[70] transition duration-300 sm:bottom-6 sm:left-auto sm:right-6 sm:max-w-sm',
                    toast ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0',
                )}
            >
                {toast ? (
                    <div className="pointer-events-auto flex w-full items-start gap-3 rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-4 py-3 shadow-lg shadow-black/5 sm:max-w-sm">
                        <span
                            className={cn(
                                'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full',
                                toast.type === 'error'
                                    ? 'bg-red-500/12 text-red-600 dark:text-red-400'
                                    : 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400',
                            )}
                        >
                            {toast.type === 'error' ? (
                                <X className="size-3.5" strokeWidth={2.5} />
                            ) : (
                                <Check className="size-3.5" strokeWidth={2.5} />
                            )}
                        </span>
                        <div className="min-w-0">
                            <p className="text-sm font-medium tracking-tight">{toast.title}</p>
                            <p className="mt-0.5 truncate text-xs text-muted-foreground">{toast.subtitle}</p>
                        </div>
                    </div>
                ) : null}
            </div>

            {paymentNotice ? (
                <PaymentResultModal
                    type={paymentNotice.type}
                    orderId={paymentNotice.orderId ?? null}
                    onClose={() => setPaymentNotice(null)}
                />
            ) : null}
        </div>
    );
}

