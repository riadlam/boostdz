import { useEffect, useMemo, useState } from 'react';

import { useSearchParams } from 'react-router-dom';

import { useTranslation } from 'react-i18next';

import { LoaderCircle, Wallet } from 'lucide-react';

import CcpBankDetails from '../../components/dashboard/CcpBankDetails';

import CcpReceiptFields from '../../components/dashboard/CcpReceiptFields';

import PaymentMethodPicker from '../../components/dashboard/PaymentMethodPicker';

import { DashboardPanel, DashboardTable } from '../../components/dashboard/DashboardPanel';

import MinimumCheckoutModal from '../../components/MinimumCheckoutModal';

import { useAuth } from '../../context/AuthContext';

import { ApiError, depositsApi, sofizpayApi } from '../../lib/api';

import {

    fetchCheckoutSettings,

    isBelowMinimum,

    isMinimumCheckoutError,

    minimumCheckoutFromError,

} from '../../lib/checkoutPolicy';

import { cn } from '../../lib/cn';

import { topUpPresets } from '../../content/billing';

import { formatDateTime } from '../../lib/formatDate';

import { formatDzd, roundDzd } from '../../lib/formatMoney';

import {

    getPaymentOptions,

    depositMethodForPaymentOption,

    paymentOptionLabelForDeposit,

} from '../../lib/paymentMethods';



const STATUS_STYLES = {

    completed: { dot: 'bg-emerald-500', key: 'status.completed' },

    approved: { dot: 'bg-emerald-500', key: 'status.approved' },

    pending: { dot: 'bg-amber-500', key: 'status.pending' },

    rejected: { dot: 'bg-red-500', key: 'status.rejected' },

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



function pickInitialAmount(minimum, topupParam, presets) {

    const requested = topupParam ? roundDzd(topupParam) : 0;

    const target = Math.max(requested, minimum);

    const preset = presets.find((value) => value >= target) || presets[presets.length - 1] || target;



    return {

        amount: preset,

        customAmount: requested > 0 && !presets.includes(requested) ? String(requested) : '',

    };

}



function mapDepositRow(deposit, t) {

    const payload = deposit?.data ?? deposit;



    return {

        id: payload.id,

        label: t('billing:balanceTopUp'),

        amount: Number(payload.amount_dzd) || 0,

        method: paymentOptionLabelForDeposit(payload.method, t),

        status: payload.status || 'pending',

        when: formatDateTime(payload.created_at, { withYear: true }),

    };

}



export default function Billing() {

    const { t } = useTranslation(['billing', 'common', 'checkout']);

    const { user, refreshUser } = useAuth();

    const [searchParams] = useSearchParams();

    const topupParam = searchParams.get('topup');

    const [minimumTopup, setMinimumTopup] = useState(0);

    const [amount, setAmount] = useState(2000);

    const [customAmount, setCustomAmount] = useState('');

    const [method, setMethod] = useState('edahabia');

    const [phone, setPhone] = useState('');

    const [wireAmount, setWireAmount] = useState('');

    const [reference, setReference] = useState('');

    const [attachment, setAttachment] = useState(null);

    const [submitting, setSubmitting] = useState(false);

    const [formError, setFormError] = useState('');

    const [successMessage, setSuccessMessage] = useState('');

    const [minimumModal, setMinimumModal] = useState(null);

    const [deposits, setDeposits] = useState([]);

    const [loadingDeposits, setLoadingDeposits] = useState(true);



    const paymentOptions = useMemo(() => getPaymentOptions(t, 'billing'), [t]);

    useEffect(() => {
        if (user?.phone && !phone) {
            setPhone(user.phone);
        }
    }, [user?.phone, phone]);



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



    useEffect(() => {

        let active = true;



        async function loadDeposits() {

            setLoadingDeposits(true);

            try {

                const data = await depositsApi.list({ per_page: 20 });

                if (!active) {

                    return;

                }



                const rows = Array.isArray(data?.data) ? data.data : [];

                setDeposits(rows.map((deposit) => mapDepositRow(deposit, t)));

            } catch {

                if (active) {

                    setDeposits([]);

                }

            } finally {

                if (active) {

                    setLoadingDeposits(false);

                }

            }

        }



        loadDeposits();



        return () => {

            active = false;

        };

    }, [successMessage, t]);



    const isCcp = method === 'ccp-baridimob';
    const isEdahabia = method === 'edahabia';

    const displayAmount = customAmount ? Number(customAmount) || 0 : amount;

    const balance = Number(user?.wallet?.available_balance ?? user?.wallet?.balance ?? 0);

    const canSubmit =
        displayAmount >= minimumTopup
        && method
        && (!isCcp || (wireAmount && Number(wireAmount) > 0 && attachment))
        && (!isEdahabia || phone.trim() !== '');



    async function onTopUp(event) {

        event.preventDefault();

        if (!canSubmit || submitting) {

            return;

        }



        setFormError('');

        setSuccessMessage('');



        if (minimumTopup > 0 && isBelowMinimum(displayAmount, minimumTopup)) {

            setMinimumModal({

                charge: roundDzd(displayAmount),

                minimum: minimumTopup,

                message: t('checkout:minimumTopUp', { amount: formatDzd(minimumTopup) }),

            });

            return;

        }



        const depositMethod = depositMethodForPaymentOption(method);

        if (!depositMethod) {

            setFormError(t('billing:selectPaymentMethod'));

            return;

        }



        setSubmitting(true);

        if (depositMethod === 'edahabia') {
            try {
                const data = await sofizpayApi.initTopup({
                    amount_dzd: roundDzd(displayAmount),
                    phone: phone.trim(),
                });
                const paymentUrl = data?.payment_url;
                if (!paymentUrl) {
                    throw new Error(t('billing:submitError'));
                }
                window.location.href = paymentUrl;
            } catch (error) {
                if (isMinimumCheckoutError(error)) {
                    setMinimumModal(minimumCheckoutFromError(error));
                } else {
                    setFormError(error instanceof ApiError ? error.message : t('billing:submitError'));
                }
                setSubmitting(false);
            }
            return;
        }

        const formData = new FormData();

        formData.append('amount_dzd', String(roundDzd(displayAmount)));

        formData.append('method', depositMethod);



        if (isCcp) {

            formData.append('wired_amount_dzd', String(roundDzd(wireAmount)));

            if (reference.trim()) {

                formData.append('provider_reference', reference.trim());

            }

            if (attachment) {

                formData.append('proof', attachment);

            }

        }



        try {

            const data = await depositsApi.create(formData);

            const deposit = data?.deposit?.data ?? data?.deposit;

            await refreshUser();



            if (depositMethod === 'algerie_post') {

                setSuccessMessage(t('billing:addedToBalance', { amount: formatDzd(displayAmount) }));

            } else {

                setSuccessMessage(t('billing:receiptSubmitted'));

                setWireAmount('');

                setReference('');

                setAttachment(null);

            }

        } catch (error) {

            if (isMinimumCheckoutError(error)) {

                setMinimumModal(minimumCheckoutFromError(error));

            } else {

                setFormError(error instanceof ApiError ? error.message : t('billing:submitError'));

            }

        } finally {

            setSubmitting(false);

        }

    }



    return (

        <div className="space-y-4 py-1" data-test-id="billing-page">

            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

                <div>

                    <h1 className="text-xl font-semibold tracking-tight">{t('billing:title')}</h1>

                    <p className="mt-1 text-sm text-muted-foreground">{t('billing:subtitle')}</p>

                </div>

                <div className="rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-4 py-3">

                    <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{t('common:balance')}</p>

                    <p className="text-xl font-semibold tabular-nums">{formatDzd(balance)}</p>

                </div>

            </div>



            <DashboardPanel title={t('billing:topUp')} bodyClassName="dash-panel-body-padded">

                <form className="mx-auto max-w-xl space-y-5" onSubmit={onTopUp}>

                    {formError ? (

                        <div role="alert" className="rounded-lg border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm text-red-700 dark:text-red-300">

                            {formError}

                        </div>

                    ) : null}



                    {successMessage ? (

                        <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300">

                            {successMessage}

                        </div>

                    ) : null}



                    <div>

                        <p className="mb-2 text-xs font-medium text-muted-foreground">{t('billing:amountDa')}</p>

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

                                    {formatDzd(preset)}

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

                                    ? t('billing:customAmountMin', { amount: formatDzd(minimumTopup) })

                                    : t('billing:customAmount')

                            }

                            className="mt-2 h-10 w-full rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-primary/30"

                        />

                    </div>



                    <PaymentMethodPicker

                        value={method}

                        onChange={setMethod}

                        disabled={submitting}

                        options={paymentOptions}

                        heading={t('billing:paymentMethod')}

                        subheading={t('billing:selectOne')}

                        className="shadow-none"

                    />



                    {isEdahabia ? (
                        <div className="space-y-1.5">
                            <label htmlFor="billing-phone" className="text-xs font-medium text-muted-foreground">
                                {t('billing:phoneLabel')}
                            </label>
                            <input
                                id="billing-phone"
                                type="tel"
                                value={phone}
                                onChange={(event) => setPhone(event.target.value)}
                                placeholder={t('billing:phonePlaceholder')}
                                className="h-11 w-full rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] px-3 text-sm"
                                required
                            />
                            <p className="text-xs text-muted-foreground">{t('billing:phoneHint')}</p>
                        </div>
                    ) : null}



                    {isCcp ? (

                        <div className="space-y-4 rounded-xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4">

                            <CcpBankDetails className="" />

                            <CcpReceiptFields

                                amount={wireAmount}

                                onAmountChange={setWireAmount}

                                reference={reference}

                                onReferenceChange={setReference}

                                file={attachment}

                                onFileChange={setAttachment}

                                disabled={submitting}

                            />

                            <p className="text-xs text-muted-foreground">{t('billing:ccpManualNote')}</p>

                        </div>

                    ) : (

                        <p className="rounded-lg border border-emerald-500/20 bg-emerald-500/5 px-3 py-2 text-xs text-emerald-800 dark:text-emerald-200">

                            {t('billing:algeriePostInstantNote')}

                        </p>

                    )}



                    <button

                        type="submit"

                        disabled={!canSubmit || submitting}

                        className="btn-primary flex h-10 w-full disabled:cursor-not-allowed disabled:opacity-50"

                    >

                        {submitting ? (

                            <>

                                <LoaderCircle className="size-4 animate-spin" />

                                {t('billing:processing')}

                            </>

                        ) : (

                            <>

                                <Wallet className="size-4" />

                                {isCcp ? t('billing:submitForVerification') : t('billing:payNow', { amount: formatDzd(displayAmount) })}

                            </>

                        )}

                    </button>

                </form>

            </DashboardPanel>



            <DashboardPanel title={t('billing:recentTopUps')} bodyClassName="p-0">

                <DashboardTable>

                    <thead>

                        <tr>

                            <th>{t('common:table.reference')}</th>

                            <th>{t('common:table.method')}</th>

                            <th>{t('common:table.status')}</th>

                            <th className="hidden sm:table-cell">{t('common:table.date')}</th>

                            <th className="text-right">{t('common:table.amount')}</th>

                        </tr>

                    </thead>

                    <tbody>

                        {loadingDeposits ? (

                            <tr>

                                <td colSpan={5} className="px-4 py-8 text-center text-sm text-muted-foreground">

                                    {t('billing:loadingTopUps')}

                                </td>

                            </tr>

                        ) : deposits.length === 0 ? (

                            <tr>

                                <td colSpan={5} className="px-4 py-8 text-center text-sm text-muted-foreground">

                                    {t('billing:noTopUpsYet')}

                                </td>

                            </tr>

                        ) : (

                            deposits.map((txn) => (

                                <tr key={txn.id}>

                                    <td>

                                        <div className="font-medium">{txn.label}</div>

                                        <div className="font-mono text-xs text-muted-foreground">#{txn.id}</div>

                                    </td>

                                    <td className="text-muted-foreground">{txn.method}</td>

                                    <td>

                                        <StatusBadge status={txn.status} />

                                    </td>

                                    <td className="hidden text-muted-foreground sm:table-cell">{txn.when}</td>

                                    <td className="text-right font-medium tabular-nums text-emerald-600 dark:text-emerald-400">

                                        +{formatDzd(txn.amount)}

                                    </td>

                                </tr>

                            ))

                        )}

                    </tbody>

                </DashboardTable>

            </DashboardPanel>



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


