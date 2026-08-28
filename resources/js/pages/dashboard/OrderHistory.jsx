import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Check, LoaderCircle, RotateCcw, X } from 'lucide-react';
import { DashboardPanel, DashboardTable } from '../../components/dashboard/DashboardPanel';
import { ApiError, ordersApi } from '../../lib/api';
import { cn } from '../../lib/cn';
import { formatDzd } from '../../lib/formatMoney';
import { getPlatformIcon } from '../../lib/platformIcons';

const statusStyles = {
    completed: { dot: 'bg-emerald-500', label: 'Completed' },
    processing: { dot: 'bg-blue-500', label: 'Processing' },
    in_progress: { dot: 'bg-blue-500', label: 'In progress' },
    pending: { dot: 'bg-amber-500', label: 'Pending' },
    partial: { dot: 'bg-orange-500', label: 'Partial' },
    canceled: { dot: 'bg-muted-foreground', label: 'Canceled' },
    refunded: { dot: 'bg-violet-500', label: 'Refunded' },
    failed: { dot: 'bg-red-500', label: 'Failed' },
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

function formatWhen(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
}

function warrantyLabel(order) {
    if (order.refillLifetime) return 'Lifetime';
    if (order.refillWarrantyDays) return `${order.refillWarrantyDays} days`;
    return null;
}

function mapOrder(order) {
    const platform = String(order.service?.platform || '').toLowerCase();
    const percent = Number(order.delivery?.percent ?? 0);
    const refillLifetime = Boolean(order.refill_lifetime || order.service?.refill_mode === 'lifetime');
    return {
        id: order.id,
        title: order.service?.name || 'Order',
        platform,
        status: order.status || 'pending',
        progress: percent,
        deliveryLabel: order.delivery?.label || null,
        when: formatWhen(order.created_at),
        amount: Number(order.charge_dzd || 0),
        canRefill: Boolean(order.can_request_refill),
        refillLifetime,
        refillWarrantyDays: order.refill_warranty_days ? Number(order.refill_warranty_days) : null,
        errorMessage: order.error_message,
    };
}

export default function OrderHistory() {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [requested, setRequested] = useState({});
    const [toast, setToast] = useState(null);
    const [confirmOrder, setConfirmOrder] = useState(null);
    const [confirmLoading, setConfirmLoading] = useState(false);
    const [confirmError, setConfirmError] = useState('');

    useEffect(() => {
        let cancelled = false;
        (async () => {
            setLoading(true);
            setError('');
            try {
                const data = await ordersApi.list({ per_page: 50 });
                const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
                if (!cancelled) setOrders(rows.map(mapOrder));
            } catch (err) {
                if (!cancelled) {
                    setError(err instanceof ApiError ? err.message : 'Could not load orders.');
                }
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, []);

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
                title: 'Refill pending',
                subtitle: order.title,
            });
        } catch (err) {
            setRequested((prev) => ({ ...prev, [order.id]: undefined }));
            const message = err instanceof ApiError ? err.message : 'Refill request failed.';
            setConfirmError(message);
            setToast({
                type: 'error',
                title: 'Refill failed',
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
                <h1 className="text-xl font-semibold tracking-tight">Order History</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Track every order with live status, refill protection, and delivery details.
                </p>
            </div>

            {error ? (
                <div role="alert" className="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    {error}
                </div>
            ) : null}

            <DashboardPanel
                title="All orders"
                action={
                    <Link
                        to="/dashboard/orders/create"
                        className="text-xs font-medium text-primary transition hover:text-primary/80"
                    >
                        New order
                    </Link>
                }
                bodyClassName="p-0"
            >
                <DashboardTable>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Status</th>
                            <th className="hidden md:table-cell">Delivery</th>
                            <th>Date</th>
                            <th className="text-right">Amount</th>
                            <th className="text-right"> </th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={6} className="py-10 text-center text-sm text-muted-foreground">
                                    <span className="inline-flex items-center gap-2">
                                        <LoaderCircle className="size-4 animate-spin" />
                                        Loading orders…
                                    </span>
                                </td>
                            </tr>
                        ) : orders.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="py-10 text-center text-sm text-muted-foreground">
                                    No orders yet.{' '}
                                    <Link to="/dashboard/orders/create" className="font-semibold text-primary underline-offset-2 hover:underline">
                                        Create one
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
                                                            <span className="dash-refill-chip">Protected · {warranty}</span>
                                                        ) : null}
                                                        {refillState === 'done' ? (
                                                            <span className="dash-refill-chip dash-refill-chip-done">Refill pending</span>
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
                                                    {order.status === 'completed' ? 'Delivered' : order.deliveryLabel || '—'}
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
                                                        Pending
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        disabled={refillState === 'loading'}
                                                        onClick={() => openRefillModal(order)}
                                                        className="dash-refill-btn"
                                                    >
                                                        <RotateCcw className="size-3.5" strokeWidth={2} />
                                                        Refill
                                                    </button>
                                                )
                                            ) : (
                                                <span className="select-none text-xs text-transparent">—</span>
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
                        aria-label="Close"
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
                                    Protection
                                </p>
                                <h2 id="refill-modal-title" className="mt-1 text-lg font-semibold tracking-tight">
                                    Request refill
                                </h2>
                            </div>
                            <button
                                type="button"
                                className="dash-modal-close"
                                disabled={confirmLoading}
                                onClick={closeRefillModal}
                                aria-label="Close dialog"
                            >
                                <X className="size-4" strokeWidth={2} />
                            </button>
                        </div>

                        <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                            <span className="font-medium text-foreground">Please confirm your count has actually dropped.</span>{' '}
                            Refill is for restoring drops during the protection period. Requests without a drop may be declined.
                        </p>

                        <div className="mt-4 rounded-xl border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)] px-3.5 py-3">
                            <p className="truncate text-sm font-medium">{confirmOrder.title}</p>
                            {confirmWarranty ? (
                                <p className="mt-1.5">
                                    <span className="dash-refill-chip">Protected · {confirmWarranty}</span>
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
                                Cancel
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
                                        Sending…
                                    </>
                                ) : (
                                    <>
                                        <RotateCcw className="size-3.5" strokeWidth={2} />
                                        Confirm refill
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
                    'pointer-events-none fixed bottom-6 right-6 z-[70] transition duration-300',
                    toast ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0',
                )}
            >
                {toast ? (
                    <div className="pointer-events-auto flex min-w-64 max-w-sm items-start gap-3 rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-4 py-3 shadow-lg shadow-black/5">
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
        </div>
    );
}
