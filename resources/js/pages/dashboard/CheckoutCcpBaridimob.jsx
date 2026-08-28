import { useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { ArrowLeft, Check, Copy, LoaderCircle, Paperclip, Upload } from 'lucide-react';
import MinimumCheckoutModal from '../../components/MinimumCheckoutModal';
import { ApiError, checkoutApi } from '../../lib/api';
import {
    fetchCheckoutSettings,
    isBelowMinimum,
    isMinimumCheckoutError,
    minimumCheckoutFromError,
} from '../../lib/checkoutPolicy';
import { cn } from '../../lib/cn';
import { formatDzd, roundDzd } from '../../lib/formatMoney';
import { checkoutBankDetails, clearCheckoutDraft, loadCheckoutDraft, previewComments, saveCheckoutDraft } from '../../lib/orderRules';

function newIdempotencyKey() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

export default function CheckoutCcpBaridimob() {
    const navigate = useNavigate();
    const location = useLocation();
    const draft = useMemo(() => location.state?.draft || loadCheckoutDraft(), [location.state]);
    const [amount, setAmount] = useState(String(Math.round(Number(draft?.charge || 0)) || ''));
    const [reference, setReference] = useState('');
    const [file, setFile] = useState(null);
    const [copied, setCopied] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [formError, setFormError] = useState('');
    const [sent, setSent] = useState(null);
    const [minimumModal, setMinimumModal] = useState(null);
    const commentsPreview = useMemo(
        () => (draft?.comments ? previewComments(draft.comments) : null),
        [draft?.comments],
    );

    useEffect(() => {
        let active = true;

        async function checkMinimum() {
            if (!draft?.charge) {
                return;
            }

            try {
                const settings = await fetchCheckoutSettings();
                if (!active) {
                    return;
                }

                if (isBelowMinimum(draft.charge, settings.minimum_amount_dzd)) {
                    setMinimumModal({
                        charge: roundDzd(draft.charge),
                        minimum: settings.minimum_amount_dzd,
                    });
                }
            } catch {
                // Server enforces the minimum if settings cannot be loaded.
            }
        }

        checkMinimum();

        return () => {
            active = false;
        };
    }, [draft?.charge]);

    if (!draft?.serviceId) {
        return (
            <div className="mx-auto max-w-2xl space-y-4 py-6">
                <h1 className="text-xl font-semibold tracking-tight">CCP / BaridiMob</h1>
                <p className="text-sm text-muted-foreground">No checkout draft found.</p>
                <Link to="/dashboard/orders/create" className="btn-primary inline-flex">
                    Create order
                </Link>
            </div>
        );
    }

    async function copyValue(key, value) {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(key);
            window.setTimeout(() => setCopied(''), 1600);
        } catch {
            // ignore
        }
    }

    async function onSubmit(event) {
        event.preventDefault();
        if (!amount || !file || submitting || minimumModal) return;

        setFormError('');

        try {
            const settings = await fetchCheckoutSettings();
            if (isBelowMinimum(draft.charge, settings.minimum_amount_dzd)) {
                setMinimumModal({
                    charge: roundDzd(draft.charge),
                    minimum: settings.minimum_amount_dzd,
                });
                return;
            }
        } catch {
            // Server enforces the minimum if settings cannot be loaded.
        }

        setSubmitting(true);

        const formData = new FormData();
        formData.append('service_id', String(draft.serviceId));
        formData.append('link', draft.link);
        formData.append('quantity', String(draft.quantity));
        formData.append('amount_dzd', String(amount));
        formData.append('is_repeat', draft.isRepeat ? '1' : '0');
        formData.append('idempotency_key', newIdempotencyKey());
        if (reference.trim()) formData.append('reference', reference.trim());
        if (draft.countryCode) formData.append('country', draft.countryCode);
        if (draft.qualityTier) formData.append('quality', draft.qualityTier);
        if (draft.platformSlug) formData.append('platform_slug', draft.platformSlug);
        if (draft.categorySlug) formData.append('category_slug', draft.categorySlug);
        if (draft.comments) formData.append('comments', draft.comments);
        formData.append('receipt', file);

        try {
            const data = await checkoutApi.submitCcpReceipt(formData);
            const submission = data?.submission?.data ?? data?.submission;
            saveCheckoutDraft({
                ...draft,
                paymentMethod: 'ccp-baridimob',
                transferAmount: amount,
                reference,
                paymentSubmissionId: submission?.id,
            });
            setSent(submission || { status: 'pending' });
            clearCheckoutDraft();
        } catch (error) {
            if (isMinimumCheckoutError(error)) {
                setMinimumModal(minimumCheckoutFromError(error));
            } else {
                setFormError(error instanceof ApiError ? error.message : 'Could not upload receipt. Please try again.');
            }
        } finally {
            setSubmitting(false);
        }
    }

    const rows = [
        { key: 'accountName', label: 'Account name', value: checkoutBankDetails.accountName },
        { key: 'bankName', label: 'Bank / Channel', value: checkoutBankDetails.bankName },
        { key: 'ccpAccount', label: 'CCP account', value: checkoutBankDetails.ccpAccount },
        { key: 'rip', label: 'RIP', value: checkoutBankDetails.rip },
        { key: 'baridimobId', label: 'BaridiMob ID', value: checkoutBankDetails.baridimobId },
    ];

    return (
        <div className="mx-auto w-full max-w-2xl space-y-4 py-2" data-test-id="checkout-ccp-page">
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => navigate('/checkout', { state: { draft } })}
                    className="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground"
                    aria-label="Back"
                >
                    <ArrowLeft className="size-4" />
                </button>
                <div className="min-w-0 flex-1">
                    <h1 className="text-lg font-semibold tracking-tight">CCP / BaridiMob</h1>
                    <p className="text-xs text-muted-foreground">Transfer {formatDzd(draft.charge)} then upload your receipt.</p>
                </div>
                <div className="flex items-center gap-1.5">
                    <img src="/images/payments/ccp.svg" alt="" className="size-8 rounded-lg border border-[var(--color-dash-border-subtle)] bg-white object-contain p-1" />
                    <img src="/images/payments/baridimob.png" alt="" className="size-8 rounded-lg border border-[var(--color-dash-border-subtle)] bg-white object-contain p-1" />
                </div>
            </div>

            <section className="rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Order summary</p>
                <p className="mt-1 font-semibold text-foreground">{draft.serviceName}</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    {formatDzd(draft.charge)} · qty {Number(draft.quantity || 0).toLocaleString('fr-DZ', { maximumFractionDigits: 0 })}
                </p>
                {commentsPreview ? (
                    <p className="mt-2 text-xs text-muted-foreground">
                        {commentsPreview.total} custom comment{commentsPreview.total === 1 ? '' : 's'}: {commentsPreview.label}
                    </p>
                ) : null}
            </section>

            <section className="rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">
                <p className="text-sm font-semibold text-foreground">Our bank details</p>
                <p className="mt-1 text-xs text-muted-foreground">{checkoutBankDetails.note}</p>
                <div className="mt-3 space-y-2">
                    {rows.map((row) => (
                        <div
                            key={row.key}
                            className="flex items-center justify-between gap-3 rounded-lg border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)] px-3 py-2"
                        >
                            <div className="min-w-0">
                                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{row.label}</p>
                                <p className="truncate font-semibold tabular-nums text-foreground">{row.value}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => copyValue(row.key, row.value)}
                                className="inline-flex size-8 shrink-0 items-center justify-center rounded-md border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] text-muted-foreground hover:text-foreground"
                                aria-label={`Copy ${row.label}`}
                            >
                                {copied === row.key ? <Check className="size-3.5 text-emerald-600" /> : <Copy className="size-3.5" />}
                            </button>
                        </div>
                    ))}
                </div>
            </section>

            {formError ? (
                <div role="alert" className="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-700 dark:text-red-300">
                    {formError}
                </div>
            ) : null}

            {sent ? (
                <div className="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                    <p className="font-semibold">
                        {sent.status === 'approved'
                            ? `Order #${sent.order_id || '—'} placed`
                            : sent.status === 'failed'
                              ? 'Receipt saved — order failed at provider'
                              : 'Receipt sent — waiting for admin approval'}
                    </p>
                    <p className="mt-1 text-xs opacity-90">
                        {sent.status === 'approved'
                            ? 'Payment auto-accepted for local testing. Track delivery in order history.'
                            : sent.status === 'failed'
                              ? sent.admin_note || 'Provider rejected the order. Check logs / try another service.'
                              : `Submission #${sent.id || '—'} is pending on Telegram. Your order will be placed after Accept.`}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-3">
                        <Link to="/dashboard/orders/history" className="inline-flex text-xs font-semibold underline underline-offset-2">
                            Go to order history
                        </Link>
                        <Link to="/dashboard/orders/create" className="inline-flex text-xs font-semibold underline underline-offset-2">
                            Create another order
                        </Link>
                    </div>
                </div>
            ) : (
                <form onSubmit={onSubmit} className="space-y-3 rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">
                    <p className="text-sm font-semibold text-foreground">Upload &amp; send</p>
                    <label className="block">
                        <span className="mb-1 block text-xs font-medium text-muted-foreground">Amount transferred (DA)</span>
                        <input
                            type="number"
                            min="1"
                            step="1"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            className="dash-input"
                            required
                            disabled={submitting}
                        />
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-xs font-medium text-muted-foreground">Payment reference (optional)</span>
                        <input
                            type="text"
                            value={reference}
                            onChange={(e) => setReference(e.target.value)}
                            placeholder="Your username or note"
                            className="dash-input"
                            disabled={submitting}
                        />
                    </label>
                    <label
                        className={cn(
                            'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-6 text-center transition',
                            file
                                ? 'border-primary/40 bg-primary/5'
                                : 'border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)] hover:border-primary/30',
                            submitting && 'pointer-events-none opacity-60',
                        )}
                    >
                        {file ? <Paperclip className="size-5 text-primary" /> : <Upload className="size-5 text-muted-foreground" />}
                        <span className="text-sm font-medium text-foreground">
                            {file ? file.name : 'Upload transfer receipt'}
                        </span>
                        <span className="text-xs text-muted-foreground">PNG, JPG or PDF</span>
                        <input
                            type="file"
                            accept="image/*,.pdf"
                            className="hidden"
                            onChange={(e) => setFile(e.target.files?.[0] || null)}
                            required
                        />
                    </label>
                    <button
                        type="submit"
                        disabled={!amount || !file || submitting || Boolean(minimumModal)}
                        className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-45"
                    >
                        {submitting ? (
                            <>
                                <LoaderCircle className="size-4 animate-spin" />
                                Sending receipt…
                            </>
                        ) : (
                            'Send for verification'
                        )}
                    </button>
                </form>
            )}

            {minimumModal ? (
                <MinimumCheckoutModal
                    charge={minimumModal.charge}
                    minimum={minimumModal.minimum}
                    message={minimumModal.message}
                    onClose={() => setMinimumModal(null)}
                />
            ) : null}
        </div>
    );
}
