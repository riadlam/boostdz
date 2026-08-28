import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Building2, Paperclip, Wallet, Zap } from 'lucide-react';
import { DashboardPanel, DashboardTable } from '../../components/dashboard/DashboardPanel';
import { cn } from '../../lib/cn';
import { fetchCheckoutSettings } from '../../lib/checkoutPolicy';
import { roundDzd } from '../../lib/formatMoney';
import { billingTransactions, ccpDetails, paymentMethods, topUpPresets } from '../../content/billing';
import { dashboardUser } from '../../content/dashboard';

const statusStyles = {
    completed: { dot: 'bg-emerald-500', label: 'Completed' },
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

function formatDa(n) {
    return `${n.toLocaleString('fr-DZ')} DA`;
}

function pickInitialAmount(minimum, topupParam, presets) {
    const requested = topupParam ? roundDzd(topupParam) : 0;
    const target = Math.max(requested, minimum);
    const preset = presets.find((value) => value >= target) || presets[presets.length - 1] || target;

    return {
        amount: preset,
        customAmount: requested > 0 && !presets.includes(requested) ? String(requested) : '',
    };
}

export default function Billing() {
    const [searchParams] = useSearchParams();
    const topupParam = searchParams.get('topup');
    const [minimumTopup, setMinimumTopup] = useState(0);
    const [amount, setAmount] = useState(2000);
    const [customAmount, setCustomAmount] = useState('');
    const [method, setMethod] = useState('algerie-post');
    const [wireAmount, setWireAmount] = useState('');
    const [attachment, setAttachment] = useState(null);
    const [submitted, setSubmitted] = useState(false);

    const visiblePresets = useMemo(
        () => topUpPresets.filter((preset) => preset >= minimumTopup),
        [minimumTopup],
    );

    useEffect(() => {
        let active = true;

        async function loadSettings() {
            try {
                const settings = await fetchCheckoutSettings();
                if (!active) {
                    return;
                }

                const minimum = settings.minimum_topup_dzd;
                setMinimumTopup(minimum);

                const initial = pickInitialAmount(minimum, topupParam, topUpPresets.filter((preset) => preset >= minimum));
                setAmount(initial.amount);
                setCustomAmount(initial.customAmount);
            } catch {
                if (!active) {
                    return;
                }

                const initial = pickInitialAmount(0, topupParam, topUpPresets);
                setAmount(initial.amount);
                setCustomAmount(initial.customAmount);
            }
        }

        loadSettings();

        return () => {
            active = false;
        };
    }, [topupParam]);

    const isCcp = method === 'ccp';
    const displayAmount = customAmount ? Number(customAmount) || 0 : amount;
    const canSubmit =
        displayAmount >= minimumTopup &&
        (!isCcp || (wireAmount && Number(wireAmount) > 0 && attachment));

    function onTopUp(e) {
        e.preventDefault();
        if (!canSubmit) return;
        setSubmitted(true);
        setTimeout(() => {
            setSubmitted(false);
            if (isCcp) {
                setWireAmount('');
                setAttachment(null);
            }
        }, 3500);
    }

    return (
        <div className="space-y-4 py-1" data-test-id="billing-page">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">Billing</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Top up your balance to place orders.</p>
                </div>
                <div className="rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-4 py-3">
                    <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Balance</p>
                    <p className="text-xl font-semibold tabular-nums">${dashboardUser.balance.toFixed(2)}</p>
                </div>
            </div>

            <DashboardPanel title="Top up" bodyClassName="dash-panel-body-padded">
                <form className="mx-auto max-w-xl space-y-5" onSubmit={onTopUp}>
                    <div>
                        <p className="mb-2 text-xs font-medium text-muted-foreground">Amount (DA)</p>
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            {visiblePresets.map((preset) => (
                                <button
                                    key={preset}
                                    type="button"
                                    onClick={() => {
                                        setAmount(preset);
                                        setCustomAmount('');
                                    }}
                                    className={cn(
                                        'rounded-lg border px-3 py-2 text-sm font-medium tabular-nums transition',
                                        !customAmount && amount === preset
                                            ? 'border-primary/40 bg-primary/8 text-primary'
                                            : 'border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)] hover:bg-[var(--color-dash-row-hover)]',
                                    )}
                                >
                                    {formatDa(preset)}
                                </button>
                            ))}
                        </div>
                        <input
                            type="number"
                            min={minimumTopup > 0 ? minimumTopup : 1}
                            step={100}
                            value={customAmount}
                            onChange={(e) => setCustomAmount(e.target.value)}
                            placeholder={
                                minimumTopup > 0
                                    ? `Custom amount (min ${formatDa(minimumTopup)})`
                                    : 'Custom amount'
                            }
                            className="mt-2 h-10 w-full rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                        />
                    </div>

                    <div className="space-y-2">
                        <p className="text-xs font-medium text-muted-foreground">Payment method</p>
                        {paymentMethods.map((pm) => (
                            <label
                                key={pm.id}
                                className={cn(
                                    'flex cursor-pointer gap-3 rounded-lg border p-3 transition',
                                    method === pm.id
                                        ? 'border-primary/40 bg-primary/5'
                                        : 'border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-surface)] hover:bg-[var(--color-dash-row-hover)]',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="payment-method"
                                    checked={method === pm.id}
                                    onChange={() => setMethod(pm.id)}
                                    className="mt-1 size-4 accent-primary"
                                />
                                <span className="min-w-0 flex-1">
                                    <span className="flex items-center gap-2">
                                        {pm.id === 'ccp' ? (
                                            <Building2 className="size-4 text-muted-foreground" />
                                        ) : (
                                            <Zap className="size-4 text-muted-foreground" />
                                        )}
                                        <span className="text-sm font-medium">{pm.label}</span>
                                        <span
                                            className={cn(
                                                'rounded-full px-2 py-0.5 text-[10px] font-medium',
                                                pm.processing === 'instant'
                                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
                                            )}
                                        >
                                            {pm.hint}
                                        </span>
                                    </span>
                                    <span className="mt-1 block text-xs text-muted-foreground">{pm.description}</span>
                                </span>
                            </label>
                        ))}
                    </div>

                    {isCcp ? (
                        <div className="space-y-3 rounded-lg border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)] p-3">
                            <p className="text-xs font-medium text-muted-foreground">Wire to this CCP account</p>
                            <dl className="space-y-1.5 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Account name</dt>
                                    <dd className="font-medium">{ccpDetails.accountName}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">CCP N°</dt>
                                    <dd className="font-mono text-xs">{ccpDetails.ccpAccount}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">RIP</dt>
                                    <dd className="font-mono text-xs">{ccpDetails.rip}</dd>
                                </div>
                            </dl>

                            <div>
                                <label className="text-xs font-medium text-muted-foreground" htmlFor="wire-amount">
                                    Wired amount (DA) *
                                </label>
                                <input
                                    id="wire-amount"
                                    type="number"
                                    min={1}
                                    value={wireAmount}
                                    onChange={(e) => setWireAmount(e.target.value)}
                                    placeholder="Exact amount you sent"
                                    className="mt-1 h-10 w-full rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                                />
                            </div>

                            <div>
                                <label className="text-xs font-medium text-muted-foreground" htmlFor="wire-proof">
                                    Receipt / proof *
                                </label>
                                <label
                                    htmlFor="wire-proof"
                                    className="mt-1 flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-3 py-3 text-sm text-muted-foreground transition hover:bg-[var(--color-dash-row-hover)]"
                                >
                                    <Paperclip className="size-4 shrink-0" />
                                    <span className="truncate">
                                        {attachment ? attachment.name : 'Attach wire receipt or screenshot'}
                                    </span>
                                </label>
                                <input
                                    id="wire-proof"
                                    type="file"
                                    accept="image/*,.pdf"
                                    className="sr-only"
                                    onChange={(e) => setAttachment(e.target.files?.[0] || null)}
                                />
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Manual processing — balance is credited after we verify your wire (usually within a few hours).
                            </p>
                        </div>
                    ) : (
                        <p className="rounded-lg border border-emerald-500/20 bg-emerald-500/5 px-3 py-2 text-xs text-emerald-800 dark:text-emerald-200">
                            Algérie Post payments are processed automatically. Your balance updates instantly.
                        </p>
                    )}

                    <button
                        type="submit"
                        disabled={!canSubmit}
                        className="btn-primary flex h-10 w-full disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Wallet className="size-4" />
                        {isCcp ? 'Submit for verification' : `Pay ${formatDa(displayAmount)} now`}
                    </button>

                    {submitted ? (
                        <div
                            className={cn(
                                'rounded-lg border px-3 py-2 text-sm',
                                isCcp
                                    ? 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200'
                                    : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                            )}
                        >
                            {isCcp
                                ? 'Request submitted (demo). We will credit your balance after verifying your CCP wire.'
                                : `Payment received (demo). ${formatDa(displayAmount)} added to your balance.`}
                        </div>
                    ) : null}
                </form>
            </DashboardPanel>

            <DashboardPanel title="Recent top-ups" bodyClassName="p-0">
                <DashboardTable>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th className="hidden sm:table-cell">Date</th>
                            <th className="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {billingTransactions.map((txn) => (
                            <tr key={txn.id}>
                                <td>
                                    <div className="font-medium">{txn.label}</div>
                                    <div className="font-mono text-xs text-muted-foreground">{txn.id}</div>
                                </td>
                                <td className="text-muted-foreground">{txn.method}</td>
                                <td>
                                    <StatusBadge status={txn.status} />
                                </td>
                                <td className="hidden text-muted-foreground sm:table-cell">{txn.when}</td>
                                <td className="text-right font-medium tabular-nums text-emerald-600 dark:text-emerald-400">
                                    +{formatDa(txn.amount)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </DashboardTable>
            </DashboardPanel>
        </div>
    );
}
