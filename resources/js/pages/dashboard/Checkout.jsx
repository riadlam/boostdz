import { useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
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
import { useAuth } from '../../context/AuthContext';
import { ApiError, ordersApi } from '../../lib/api';
import {
    fetchCheckoutSettings,
    isBelowMinimum,
    isMinimumCheckoutError,
    minimumCheckoutFromError,
} from '../../lib/checkoutPolicy';
import { cn } from '../../lib/cn';
import { formatDzd, roundDzd } from '../../lib/formatMoney';
import {
    GLOBAL_ORDER_RULES,
    categoryOrderRules,
    clearCheckoutDraft,
    loadCheckoutDraft,
    previewComments,
    saveCheckoutDraft,
} from '../../lib/orderRules';
import { getCategoryIcon } from '../../lib/categoryIcons';
import { getPlatformIcon } from '../../lib/platformIcons';

function formatAmount(n) {
    return Number(n || 0).toLocaleString('fr-DZ', { maximumFractionDigits: 0 });
}


function draftBadges(draft) {
    const badges = [];
    if (draft.isHot) badges.push({ key: 'hot', label: 'Top seller', tone: 'hot' });
    if (draft.isCheap) badges.push({ key: 'cheap', label: 'Best price', tone: 'warn' });
    if (draft.startClass === 'instant') badges.push({ key: 'instant', label: 'Instant start', tone: 'ok', icon: Zap });
    else if (draft.startClass === 'fast') badges.push({ key: 'fast', label: 'Fast start', tone: 'ok', icon: Zap });
    if (draft.refillMode === 'auto') {
        badges.push({
            key: 'ar',
            label: draft.refillDays ? `Auto refill ${draft.refillDays}d` : 'Auto refill',
            tone: 'info',
            icon: ShieldCheck,
        });
    } else if (draft.refillMode === 'lifetime') {
        badges.push({ key: 'life', label: 'Lifetime refill', tone: 'info', icon: ShieldCheck });
    } else if (draft.hasRefill || draft.refillDays) {
        badges.push({
            key: 'r',
            label: draft.refillDays ? `Refill ${draft.refillDays}d` : 'Refill',
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
    if (draft.dripfeed) badges.push({ key: 'drip', label: 'Drip-feed', tone: 'muted' });
    if (draft.isRepeat) badges.push({ key: 'repeat', label: 'Repeat order', tone: 'muted' });
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

const PAYMENT_OPTIONS = [
    {
        id: 'ccp-baridimob',
        title: 'CCP / BaridiMob',
        hint: 'Manual transfer',
        description: 'Pay via CCP or BaridiMob, then upload your receipt for verification.',
        icons: ['/images/payments/ccp.svg', '/images/payments/baridimob.png'],
        action: 'navigate',
    },
    {
        id: 'algerie-post',
        title: 'Algérie Post',
        hint: 'Pay & place',
        description: 'Demo payment for now — clicking Continue places the order via BuzzerPanel.',
        icons: ['/images/payments/algerie-post.png'],
        action: 'place_order',
    },
];

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
    const navigate = useNavigate();
    const location = useLocation();
    const { refreshUser } = useAuth();
    const draft = useMemo(() => location.state?.draft || loadCheckoutDraft(), [location.state]);
    const [method, setMethod] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [formError, setFormError] = useState('');
    const [minimumModal, setMinimumModal] = useState(null);

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
        return categoryOrderRules(draft.platformSlug, draft.categorySlug);
    }, [draft]);

    const badges = useMemo(() => (draft ? draftBadges(draft) : []), [draft]);
    const commentsPreview = useMemo(
        () => (draft?.comments ? previewComments(draft.comments) : null),
        [draft?.comments],
    );
    const PlatformIcon = getPlatformIcon(draft?.platformSlug);
    const CategoryIcon = getCategoryIcon(draft?.categorySlug);

    if (!draft?.serviceId) {
        return (
            <div className="mx-auto max-w-2xl space-y-4 py-6">
                <h1 className="text-xl font-semibold tracking-tight">Checkout</h1>
                <p className="text-sm text-muted-foreground">No order draft found. Pick a package first.</p>
                <Link to="/dashboard/orders/create" className="btn-primary inline-flex">
                    Create order
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
                await placeOrderViaApi('algerie-post');
            } catch (error) {
                if (isMinimumCheckoutError(error)) {
                    setMinimumModal(minimumCheckoutFromError(error));
                } else if (error instanceof ApiError) {
                    setFormError(error.message);
                } else {
                    setFormError(error?.message || 'Could not place order. Please try again.');
                }
            } finally {
                setSubmitting(false);
            }
        }
    }

    return (
        <div className="mx-auto w-full max-w-2xl space-y-4 py-2" data-test-id="checkout-page">
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => navigate('/dashboard/orders/create')}
                    className="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground"
                    aria-label="Back"
                >
                    <ArrowLeft className="size-4" />
                </button>
                <div>
                    <h1 className="text-lg font-semibold tracking-tight">Checkout</h1>
                    <p className="text-xs text-muted-foreground">Review your bag and choose how to pay.</p>
                </div>
            </div>

            {/* Offer / cart card */}
            <section className="overflow-hidden rounded-2xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] shadow-sm">
                <div className="flex items-center justify-between gap-3 border-b border-dashed border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)]/70 px-4 py-2.5">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">Order summary</p>
                    <span className="rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">
                        Ready to pay
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
                        <SummaryRow label="Quantity" value={formatAmount(draft.quantity)} strong />
                        <DottedDivider />
                        <SummaryRow label="Target" value={draft.link} mono />
                        {commentsPreview ? (
                            <>
                                <DottedDivider />
                                <SummaryRow
                                    label="Comments"
                                    value={`${commentsPreview.total} custom · ${commentsPreview.label}`}
                                />
                            </>
                        ) : null}
                        {draft.rate ? (
                            <>
                                <DottedDivider />
                                <SummaryRow label="Unit rate" value={`${formatDzd(draft.rate)} / 1k`} />
                            </>
                        ) : null}
                        <DottedDivider />
                        <div className="flex items-end justify-between gap-4 pt-3">
                            <div>
                                <p className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Order total</p>
                                <p className="mt-0.5 text-xs text-muted-foreground">Incl. service fees · DZD</p>
                            </div>
                            <p className="text-2xl font-bold tracking-tight tabular-nums text-primary">{formatDzd(draft.charge)}</p>
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

                <section className="rounded-2xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4 shadow-sm">
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <p className="text-sm font-semibold text-foreground">Payment method</p>
                        <span className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                            Select one
                        </span>
                    </div>

                    <div className="grid gap-2.5">
                        {PAYMENT_OPTIONS.map((option) => {
                            const selected = method === option.id;
                            return (
                                <button
                                    key={option.id}
                                    type="button"
                                    disabled={submitting}
                                    onClick={() => setMethod(option.id)}
                                    className={cn(
                                        'flex w-full items-center gap-3 rounded-xl border px-3.5 py-3 text-left transition',
                                        selected
                                            ? 'border-primary/55 bg-primary/8 ring-1 ring-primary/25 shadow-sm'
                                            : 'border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)]/40 hover:border-primary/35',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex size-4 shrink-0 items-center justify-center rounded-full border',
                                            selected ? 'border-primary bg-primary' : 'border-muted-foreground/40 bg-[var(--color-dash-surface)]',
                                        )}
                                    >
                                        {selected ? <span className="size-1.5 rounded-full bg-white" /> : null}
                                    </span>
                                    <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span className="flex flex-wrap items-center gap-2">
                                            <span className="font-semibold text-foreground">{option.title}</span>
                                            <span
                                                className={cn(
                                                    'rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                                                    option.id === 'algerie-post'
                                                        ? 'bg-emerald-500/12 text-emerald-800 dark:text-emerald-200'
                                                        : 'bg-sky-500/12 text-sky-800 dark:text-sky-200',
                                                )}
                                            >
                                                {option.hint}
                                            </span>
                                        </span>
                                        <span className="text-xs text-muted-foreground">{option.description}</span>
                                    </span>
                                    <span className="flex shrink-0 items-center gap-1.5">
                                        {option.icons.map((src) => (
                                            <img
                                                key={src}
                                                src={src}
                                                alt=""
                                                className="size-10 rounded-xl border border-[var(--color-dash-border-subtle)] bg-white object-contain p-1 shadow-sm"
                                            />
                                        ))}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </section>

                <section className="rounded-2xl border border-dashed border-amber-500/40 bg-amber-500/10 px-4 py-3.5">
                    <p className="inline-flex items-center gap-2 text-[0.8125rem] font-bold tracking-wide text-amber-900 uppercase dark:text-amber-200">
                        <Info className="size-4" />
                        Important for this service
                    </p>
                    <ul className="mt-2.5 list-disc space-y-1.5 pl-4 text-sm font-medium leading-relaxed text-amber-950 dark:text-amber-100">
                        {importantRules.map((rule) => (
                            <li key={rule}>{rule}</li>
                        ))}
                        {GLOBAL_ORDER_RULES.map((rule) => (
                            <li key={rule}>{rule}</li>
                        ))}
                    </ul>
                </section>

                <button
                    type="submit"
                    disabled={!method || submitting || Boolean(minimumModal)}
                    className="group inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-[0_1px_2px_0_rgba(14,18,27,0.24),0_0_0_1px_var(--color-primary)] transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-45"
                >
                    {submitting ? (
                        <>
                            <LoaderCircle className="size-4 animate-spin" />
                            Placing order…
                        </>
                    ) : method === 'algerie-post' ? (
                        <>
                            Pay &amp; place order
                            <ArrowRight className="size-4 transition group-hover:translate-x-0.5" />
                        </>
                    ) : (
                        <>
                            Continue to payment
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
                    Cancel and edit order
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
