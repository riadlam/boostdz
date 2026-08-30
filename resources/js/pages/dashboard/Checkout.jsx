import { useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
    ArrowLeft,
    ArrowRight,
    Bookmark,
    Eye,
    Info,
    Layers,
    LoaderCircle,
    MessageCircle,
    Package,
    Share2,
    ShieldCheck,
    Target,
    Users,
    Zap,
} from 'lucide-react';
import { CountryFlag, countryLabel } from '../../components/CountryFlag';
import MinimumCheckoutModal from '../../components/MinimumCheckoutModal';
import PaymentMethodPicker from '../../components/dashboard/PaymentMethodPicker';
import { useAuth } from '../../context/AuthContext';
import { ApiError, ordersApi, sofizpayApi } from '../../lib/api';
import {
    fetchCheckoutSettings,
    isBelowMinimum,
    isMinimumCheckoutError,
    minimumCheckoutFromError,
} from '../../lib/checkoutPolicy';
import { cn } from '../../lib/cn';
import { formatDzd, roundDzd } from '../../lib/formatMoney';
import {
    getCategoryOrderRules,
    getGlobalOrderRules,
    clearCheckoutDraft,
    loadCheckoutDraft,
    previewComments,
    saveCheckoutDraft,
} from '../../lib/orderRules';
import { getPaymentOptions } from '../../lib/paymentMethods';
import { scrollDashboardToTop } from '../../lib/formScroll';
import { getCategoryIcon } from '../../lib/categoryIcons';
import { getPlatformIcon } from '../../lib/platformIcons';

function formatAmount(n) {
    return Number(n || 0).toLocaleString('fr-DZ', { maximumFractionDigits: 0 });
}


function draftBadges(draft, t) {
    const badges = [];
    if (draft.isHot) badges.push({ key: 'hot', label: t('badges.topSeller'), tone: 'hot' });
    if (draft.isCheap) badges.push({ key: 'cheap', label: t('badges.bestPrice'), tone: 'warn' });
    if (draft.startClass === 'instant') badges.push({ key: 'instant', label: t('badges.instantStart'), tone: 'ok', icon: Zap });
    else if (draft.startClass === 'fast') badges.push({ key: 'fast', label: t('badges.fastStart'), tone: 'ok', icon: Zap });
    if (draft.refillMode === 'auto') {
        badges.push({
            key: 'ar',
            label: draft.refillDays ? t('badges.autoRefill', { days: draft.refillDays }) : t('badges.autoRefillShort'),
            tone: 'info',
            icon: ShieldCheck,
        });
    } else if (draft.refillMode === 'lifetime') {
        badges.push({ key: 'life', label: t('badges.lifetimeRefill'), tone: 'info', icon: ShieldCheck });
    } else if (draft.hasRefill || draft.refillDays) {
        badges.push({
            key: 'r',
            label: draft.refillDays ? t('badges.refill', { days: draft.refillDays }) : t('badges.refillShort'),
            tone: 'info',
            icon: ShieldCheck,
        });
    }
    if (draft.qualityTier) {
        badges.push({
            key: 'tier',
            label: String(draft.qualityTier).charAt(0).toUpperCase() + String(draft.qualityTier).slice(1),
            tone: 'muted',
        });
    }
    if (draft.dripfeed) badges.push({ key: 'drip', label: t('badges.dripfeed'), tone: 'muted' });
    if (draft.isRepeat) badges.push({ key: 'repeat', label: t('badges.repeatOrder'), tone: 'muted' });
    if (draft.countryCode) {
        badges.push({ key: 'country', label: countryLabel(draft.countryCode), tone: 'muted', country: draft.countryCode });
    }
    return badges;
}

function Badge({ label, tone, icon: Icon, country }) {
    const tones = {
        hot: 'border-amber-500/30 bg-amber-500/12 text-amber-900 dark:text-amber-200',
        warn: 'border-orange-500/30 bg-orange-500/12 text-orange-900 dark:text-orange-200',
        ok: 'border-emerald-500/30 bg-emerald-500/12 text-emerald-900 dark:text-emerald-200',
        info: 'border-sky-500/30 bg-sky-500/12 text-sky-900 dark:text-sky-200',
        muted: 'border-[var(--color-dash-border)] bg-muted/60 text-muted-foreground',
    };
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase',
                tones[tone] || tones.muted,
            )}
        >
            {country ? <CountryFlag code={country} showLabel={false} className="shrink-0" /> : null}
            {Icon ? <Icon className="size-3" /> : null}
            {label}
        </span>
    );
}

function DottedDivider({ className }) {
    return (
        <div
            className={cn('h-px w-full', className)}
            style={{
                backgroundImage:
                    'radial-gradient(circle, color-mix(in oklab, var(--color-muted-foreground) 45%, transparent) 1px, transparent 1.5px)',
                backgroundSize: '8px 1px',
                backgroundRepeat: 'repeat-x',
                backgroundPosition: 'center',
            }}
            aria-hidden
        />
    );
}

function SummaryRow({ label, value, mono, strong }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2.5">
            <span className="text-xs font-medium text-muted-foreground">{label}</span>
            <span
                className={cn(
                    'max-w-[65%] text-right text-sm text-foreground',
                    mono && 'break-all font-mono text-xs',
                    strong && 'font-semibold tabular-nums',
                )}
            >
                {value}
            </span>
        </div>
    );
}

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

export default function Checkout() {
    const { t } = useTranslation(['checkout', 'common']);
    const navigate = useNavigate();
    const location = useLocation();
    const { refreshUser, user } = useAuth();
    const paymentOptions = useMemo(() => getPaymentOptions(t, 'checkout'), [t]);
    const draft = useMemo(() => location.state?.draft || loadCheckoutDraft(), [location.state]);
    const [method, setMethod] = useState('');
    const [phone, setPhone] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [formError, setFormError] = useState('');
    const [minimumModal, setMinimumModal] = useState(null);

    useEffect(() => {
        scrollDashboardToTop();
    }, []);

    useEffect(() => {
        if (user?.phone && !phone) {
            setPhone(user.phone);
        }
    }, [user?.phone, phone]);

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

    const importantRules = useMemo(() => {
        if (!draft) return [];
        return getCategoryOrderRules(t, draft.platformSlug, draft.categorySlug);
    }, [draft, t]);

    const globalRules = useMemo(() => getGlobalOrderRules(t), [t]);

    const badges = useMemo(() => (draft ? draftBadges(draft, t) : []), [draft, t]);
    const commentsPreview = useMemo(
        () => (draft?.comments ? previewComments(draft.comments) : null),
        [draft?.comments],
    );
    const PlatformIcon = getPlatformIcon(draft?.platformSlug);
    const CategoryIcon = getCategoryIcon(draft?.categorySlug);

    if (!draft?.serviceId) {
        return (
            <div className="mx-auto max-w-2xl space-y-4 py-6">
                <h1 className="text-xl font-semibold tracking-tight">{t('title')}</h1>
                <p className="text-sm text-muted-foreground">{t('noDraft')}</p>
                <Link to="/dashboard/orders/create" className="btn-primary inline-flex">
                    {t('createOrder')}
                </Link>
            </div>
        );
    }
    async function placeOrderViaApi(paymentMethod) {
        const payload = {
            service_id: draft.serviceId,
            link: draft.link,
            quantity: Number(draft.quantity),
            is_repeat: Boolean(draft.isRepeat),
            idempotency_key: newIdempotencyKey(),
            expected_charge_dzd: roundDzd(draft.charge || 0),
            country: draft.countryCode || undefined,
            quality: draft.qualityTier || undefined,
            comments: draft.comments || undefined,
            meta: {
                payment_method: paymentMethod,
                checkout_demo: paymentMethod === 'algerie-post',
                platform_slug: draft.platformSlug || null,
                category_slug: draft.categorySlug || null,
            },
        };

        const data = await ordersApi.create(payload);
        const order = data?.order?.data ?? data?.order;

        if (!order) {
            throw new Error('Order response was empty.');
        }

        if (order.status === 'failed') {
            throw new ApiError(order.error_message || 'Provider rejected the order.', { status: 422 });
        }

        await refreshUser();
        clearCheckoutDraft();
        navigate('/dashboard/orders/history', {
            state: {
                placedOrderId: order.id,
                placedStatus: order.status,
            },
            replace: true,
        });
    }

    async function onContinue(event) {
        event.preventDefault();
        if (!method || submitting) return;
        setFormError('');

        if (minimumModal) {
            return;
        }

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

        if (method === 'ccp-baridimob') {
            saveCheckoutDraft({ ...draft, paymentMethod: method });
            navigate('/checkout/ccp-baridimob', { state: { draft: { ...draft, paymentMethod: method } } });
            return;
        }

        if (method === 'algerie-post') {
            setSubmitting(true);
            try {
                const data = await sofizpayApi.initCheckout({
                    service_id: draft.serviceId,
                    link: draft.link,
                    quantity: Number(draft.quantity),
                    is_repeat: Boolean(draft.isRepeat),
                    idempotency_key: newIdempotencyKey(),
                    phone: phone.trim(),
                    country: draft.countryCode || undefined,
                    quality: draft.qualityTier || undefined,
                    comments: draft.comments || undefined,
                    platform_slug: draft.platformSlug || undefined,
                    category_slug: draft.categorySlug || undefined,
                });
                const paymentUrl = data?.payment_url;
                if (!paymentUrl) {
                    throw new Error(t('uploadError'));
                }
                window.location.href = paymentUrl;
            } catch (error) {
                if (isMinimumCheckoutError(error)) {
                    setMinimumModal(minimumCheckoutFromError(error));
                } else if (error instanceof ApiError) {
                    setFormError(error.message);
                } else {
                    setFormError(error?.message || t('uploadError'));
                }
            } finally {
                setSubmitting(false);
            }
            return;
        }
    }

    return (
        <div className="mx-auto w-full max-w-2xl space-y-4 py-2" data-test-id="checkout-page">
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => navigate('/dashboard/orders/create')}
                    className="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground"
                    aria-label={t('back', { ns: 'common' })}
                >
                    <ArrowLeft className="size-4" />
                </button>
                <div>
                    <h1 className="text-lg font-semibold tracking-tight">{t('title')}</h1>
                    <p className="text-xs text-muted-foreground">{t('subtitle')}</p>
                </div>
            </div>

            {/* Offer / cart card */}
            <section className="overflow-hidden rounded-2xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] shadow-sm">
                <div className="flex items-center justify-between gap-3 border-b border-dashed border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)]/70 px-4 py-2.5">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{t('orderSummary')}</p>
                    <span className="rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">
                        {t('readyToPay')}
                    </span>
                </div>

                <div className="p-4">
                    <div className="flex gap-3.5">
                        <div className="relative shrink-0">
                            <div className="flex size-16 items-center justify-center rounded-2xl border border-[var(--color-dash-border)] bg-gradient-to-br from-[var(--color-dash-canvas)] to-muted/40 shadow-inner">
                                <PlatformIcon className="size-8" />
                            </div>
                            <span className="absolute -right-1.5 -bottom-1.5 flex size-7 items-center justify-center rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] shadow-sm">
                                <CategoryIcon className="size-3.5 text-primary" />
                            </span>
                        </div>

                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-1.5">
                                <span className="rounded-md bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-primary uppercase">
                                    {draft.platformName}
                                </span>
                                <span className="rounded-md bg-muted px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground uppercase">
                                    {draft.categoryName}
                                </span>
                            </div>
                            <h2 className="mt-1.5 line-clamp-2 text-[0.95rem] leading-snug font-semibold text-foreground">
                                {draft.serviceName}
                            </h2>
                            {badges.length ? (
                                <div className="mt-2 flex flex-wrap gap-1.5">
                                    {badges.map((b) => (
                                        <Badge key={b.key} {...b} />
                                    ))}
                                </div>
                            ) : null}
                        </div>
                    </div>

                    <DottedDivider className="my-3.5" />

                    <div className="space-y-0">
                        <SummaryRow label={t('quantity')} value={formatAmount(draft.quantity)} strong />
                        <DottedDivider />
                        <SummaryRow label={t('target')} value={draft.link} mono />
                        {commentsPreview ? (
                            <>
                                <DottedDivider />
                                <SummaryRow
                                    label={t('comments')}
                                    value={t('commentsPreview', { total: commentsPreview.total, label: commentsPreview.label })}
                                />
                            </>
                        ) : null}
                        {draft.rate ? (
                            <>
                                <DottedDivider />
                                <SummaryRow label={t('unitRate')} value={t('unitRateValue', { rate: formatDzd(draft.rate) })} />
                            </>
                        ) : null}
                        <DottedDivider />
                        <div className="flex items-end justify-between gap-4 pt-3">
                            <div>
                                <p className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">{t('orderTotal')}</p>
                                <p className="mt-0.5 text-xs text-muted-foreground">{t('orderTotalHint')}</p>
                            </div>
                            <p className="text-xl font-bold tracking-tight tabular-nums text-primary sm:text-2xl">{formatDzd(draft.charge)}</p>
                        </div>
                    </div>
                </div>
            </section>

            <form onSubmit={onContinue} className="space-y-4">
                {formError ? (
                    <div role="alert" className="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-700 dark:text-red-300">
                        {formError}
                    </div>
                ) : null}

                <PaymentMethodPicker
                    value={method}
                    onChange={setMethod}
                    disabled={submitting}
                    options={paymentOptions}
                />

                {method === 'algerie-post' ? (
                    <div className="space-y-1.5">
                        <label htmlFor="checkout-phone" className="text-xs font-medium text-muted-foreground">
                            {t('payment.phoneLabel', { ns: 'checkout' })}
                        </label>
                        <input
                            id="checkout-phone"
                            type="tel"
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            placeholder={t('payment.phonePlaceholder', { ns: 'checkout' })}
                            className="h-11 w-full rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-3 text-sm"
                            required
                        />
                        <p className="text-xs text-muted-foreground">{t('payment.phoneHint', { ns: 'checkout' })}</p>
                    </div>
                ) : null}

                <section className="rounded-2xl border border-dashed border-amber-500/40 bg-amber-500/10 px-4 py-3.5">
                    <p className="inline-flex items-center gap-2 text-[0.8125rem] font-bold tracking-wide text-amber-900 uppercase dark:text-amber-200">
                        <Info className="size-4" />
                        {t('importantTitle')}
                    </p>
                    <ul className="mt-2.5 list-disc space-y-1.5 pl-4 text-sm font-medium leading-relaxed text-amber-950 dark:text-amber-100">
                        {importantRules.map((rule) => (
                            <li key={rule}>{rule}</li>
                        ))}
                        {globalRules.map((rule) => (
                            <li key={rule}>{rule}</li>
                        ))}
                    </ul>
                </section>

                <button
                    type="submit"
                    disabled={!method || submitting || Boolean(minimumModal) || (method === 'algerie-post' && !phone.trim())}
                    className="group inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-[0_1px_2px_0_rgba(14,18,27,0.24),0_0_0_1px_var(--color-primary)] transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-45"
                >
                    {submitting ? (
                        <>
                            <LoaderCircle className="size-4 animate-spin" />
                            {t('placingOrder')}
                        </>
                    ) : method === 'algerie-post' ? (
                        <>
                            {submitting ? t('payment.redirectingToGateway', { ns: 'checkout' }) : t('payAndPlace')}
                            <ArrowRight className="size-4 transition group-hover:translate-x-0.5" />
                        </>
                    ) : (
                        <>
                            {t('continueToPayment')}
                            <ArrowRight className="size-4 transition group-hover:translate-x-0.5" />
                        </>
                    )}
                </button>

                <button
                    type="button"
                    onClick={() => {
                        clearCheckoutDraft();
                        navigate('/dashboard/orders/create');
                    }}
                    className="w-full text-center text-xs font-medium text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                >
                    {t('cancelEditOrder')}
                </button>
            </form>

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
