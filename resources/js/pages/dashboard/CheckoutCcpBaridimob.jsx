import { useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ArrowLeft } from 'lucide-react';
import CcpBankDetails from '../../components/dashboard/CcpBankDetails';
import CcpReceiptFields, { CcpSubmitButton } from '../../components/dashboard/CcpReceiptFields';
import MinimumCheckoutModal from '../../components/MinimumCheckoutModal';
import { ApiError, checkoutApi } from '../../lib/api';
import {
    fetchCheckoutSettings,
    isBelowMinimum,
    isMinimumCheckoutError,
    minimumCheckoutFromError,
} from '../../lib/checkoutPolicy';
import { formatDzd, roundDzd } from '../../lib/formatMoney';
import { clearCheckoutDraft, loadCheckoutDraft, previewComments, saveCheckoutDraft } from '../../lib/orderRules';

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
    const { t } = useTranslation(['checkout', 'common']);
    const draft = useMemo(() => location.state?.draft || loadCheckoutDraft(), [location.state]);
    const [amount, setAmount] = useState(String(Math.round(Number(draft?.charge || 0)) || ''));
    const [reference, setReference] = useState('');
    const [file, setFile] = useState(null);
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
                <h1 className="text-xl font-semibold tracking-tight">{t('ccpTitle')}</h1>
                <p className="text-sm text-muted-foreground">{t('ccpNoDraft')}</p>
                <Link to="/dashboard/orders/create" className="btn-primary inline-flex">
                    {t('createOrder')}
                </Link>
            </div>
        );
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
                setFormError(error instanceof ApiError ? error.message : t('uploadError'));
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="mx-auto w-full max-w-2xl space-y-4 py-2" data-test-id="checkout-ccp-page">
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => navigate('/checkout', { state: { draft } })}
                    className="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground"
                    aria-label={t('back', { ns: 'common' })}
                >
                    <ArrowLeft className="size-4" />
                </button>
                <div className="min-w-0 flex-1">
                    <h1 className="text-lg font-semibold tracking-tight">{t('ccpTitle')}</h1>
                    <p className="text-xs text-muted-foreground">{t('ccpSubtitle', { amount: formatDzd(draft.charge) })}</p>
                </div>
                <div className="flex items-center gap-1.5">
                    <img src="/images/payments/ccp.svg" alt="" className="size-8 rounded-lg border border-[var(--color-dash-border-subtle)] bg-white object-contain p-1" />
                    <img src="/images/payments/baridimob.png" alt="" className="size-8 rounded-lg border border-[var(--color-dash-border-subtle)] bg-white object-contain p-1" />
                </div>
            </div>

            <section className="min-w-0 rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{t('orderSummary')}</p>
                <p className="mt-1 font-semibold text-foreground">{draft.serviceName}</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    {t('qtySummary', {
                        amount: formatDzd(draft.charge),
                        quantity: Number(draft.quantity || 0).toLocaleString('fr-DZ', { maximumFractionDigits: 0 }),
                    })}
                </p>
                {commentsPreview ? (
                    <p className="mt-2 text-xs text-muted-foreground">
                        {t('commentsCustom', { total: commentsPreview.total, label: commentsPreview.label })}
                    </p>
                ) : null}
            </section>

            <section className="min-w-0 rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">
                <CcpBankDetails />
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
                            ? t('orderPlaced', { id: sent.order_id || t('emDash', { ns: 'common' }) })
                            : sent.status === 'failed'
                              ? t('receiptFailed')
                              : t('receiptPending')}
                    </p>
                    <p className="mt-1 text-xs opacity-90">
                        {sent.status === 'approved'
                            ? t('receiptApprovedNote')
                            : sent.status === 'failed'
                              ? sent.admin_note || t('receiptFailedNote')
                              : t('receiptPendingNote', { id: sent.id || t('emDash', { ns: 'common' }) })}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-3">
                        <Link to="/dashboard/orders/history" className="inline-flex text-xs font-semibold underline underline-offset-2">
                            {t('goToHistory')}
                        </Link>
                        <Link to="/dashboard/orders/create" className="inline-flex text-xs font-semibold underline underline-offset-2">
                            {t('createAnother')}
                        </Link>
                    </div>
                </div>
            ) : (
                <form onSubmit={onSubmit} className="min-w-0 space-y-3 rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">
                    <p className="text-sm font-semibold text-foreground">{t('uploadSend')}</p>
                    <CcpReceiptFields
                        amount={amount}
                        onAmountChange={setAmount}
                        reference={reference}
                        onReferenceChange={setReference}
                        file={file}
                        onFileChange={setFile}
                        disabled={submitting}
                    />
                    <CcpSubmitButton
                        submitting={submitting}
                        disabled={!amount || !file || Boolean(minimumModal)}
                    />
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
