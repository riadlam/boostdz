import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { CheckCircle2, LoaderCircle, XCircle } from 'lucide-react';
import { ApiError, sofizpayApi } from '../../lib/api';
import { useAuth } from '../../context/AuthContext';
import { formatDzd } from '../../lib/formatMoney';

const POLL_MS = 2500;
const MAX_POLLS = 24;

export default function BillingReturn() {
    const { t } = useTranslation(['billing', 'checkout']);
    const [searchParams] = useSearchParams();
    const invoiceId = searchParams.get('invoice_id') || '';
    const { refreshUser } = useAuth();
    const [status, setStatus] = useState('pending');
    const [amount, setAmount] = useState(null);
    const [message, setMessage] = useState('');

    useEffect(() => {
        if (!invoiceId) {
            setStatus('failed');
            setMessage(t('billing:paymentFailed'));
            return;
        }

        let active = true;
        let attempts = 0;

        async function poll() {
            while (active && attempts < MAX_POLLS) {
                attempts += 1;
                try {
                    const data = await sofizpayApi.status(invoiceId);
                    const tx = data?.transaction ?? data;
                    const txStatus = tx?.status;

                    if (txStatus === 'completed') {
                        await refreshUser();
                        setAmount(tx.amount_dzd ?? null);
                        setStatus('completed');
                        return;
                    }

                    if (txStatus === 'failed') {
                        setStatus('failed');
                        setMessage(tx.failure_reason || t('billing:paymentFailed'));
                        return;
                    }
                } catch (error) {
                    if (error instanceof ApiError && attempts >= MAX_POLLS) {
                        setStatus('failed');
                        setMessage(error.message);
                        return;
                    }
                }

                await new Promise((resolve) => setTimeout(resolve, POLL_MS));
            }

            if (active) {
                setStatus('failed');
                setMessage(t('billing:paymentFailed'));
            }
        }

        poll();

        return () => {
            active = false;
        };
    }, [invoiceId, refreshUser, t]);

    return (
        <div className="mx-auto flex max-w-lg flex-col items-center gap-4 py-16 text-center">
            {status === 'pending' ? (
                <>
                    <LoaderCircle className="size-10 animate-spin text-primary" />
                    <p className="text-sm text-muted-foreground">{t('billing:paymentVerifying')}</p>
                </>
            ) : null}

            {status === 'completed' ? (
                <>
                    <CheckCircle2 className="size-10 text-emerald-500" />
                    <h1 className="text-lg font-semibold">{t('checkout:paymentSuccess')}</h1>
                    {amount ? (
                        <p className="text-sm text-muted-foreground">
                            {t('billing:paymentSuccessTopup', { amount: formatDzd(amount) })}
                        </p>
                    ) : null}
                    <Link to="/dashboard/billing" className="btn-primary mt-2 inline-flex">
                        {t('billing:title')}
                    </Link>
                </>
            ) : null}

            {status === 'failed' ? (
                <>
                    <XCircle className="size-10 text-red-500" />
                    <h1 className="text-lg font-semibold">{t('checkout:paymentFailed')}</h1>
                    {message ? <p className="text-sm text-muted-foreground">{message}</p> : null}
                    <Link to="/dashboard/billing" className="btn-primary mt-2 inline-flex">
                        {t('checkout:paymentTryAgain')}
                    </Link>
                </>
            ) : null}
        </div>
    );
}
