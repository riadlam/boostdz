import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { CheckCircle2, LoaderCircle, XCircle } from 'lucide-react';
import { ApiError, sofizpayApi } from '../../lib/api';
import { useAuth } from '../../context/AuthContext';
import { clearCheckoutDraft } from '../../lib/orderRules';

const POLL_MS = 2500;
const MAX_POLLS = 24;

export default function CheckoutEdahabiaReturn() {
    const { t } = useTranslation(['checkout', 'common']);
    const [searchParams] = useSearchParams();
    const invoiceId = searchParams.get('invoice_id') || '';
    const { refreshUser } = useAuth();
    const [status, setStatus] = useState('pending');
    const [orderId, setOrderId] = useState(null);
    const [message, setMessage] = useState('');

    useEffect(() => {
        if (!invoiceId) {
            setStatus('failed');
            setMessage(t('paymentFailed'));
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
                        clearCheckoutDraft();
                        setOrderId(tx.order_id ?? null);
                        setStatus('completed');
                        return;
                    }

                    if (txStatus === 'failed') {
                        setStatus('failed');
                        setMessage(tx.failure_reason || t('paymentFailed'));
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
                setMessage(t('paymentFailed'));
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
                    <p className="text-sm text-muted-foreground">{t('paymentPending')}</p>
                </>
            ) : null}

            {status === 'completed' ? (
                <>
                    <CheckCircle2 className="size-10 text-emerald-500" />
                    <h1 className="text-lg font-semibold">{t('paymentSuccess')}</h1>
                    {orderId ? <p className="text-sm text-muted-foreground">{t('orderPlacedAfterPay', { id: orderId })}</p> : null}
                    <Link to="/dashboard/orders/history" className="btn-primary mt-2 inline-flex">
                        {t('goToHistory')}
                    </Link>
                </>
            ) : null}

            {status === 'failed' ? (
                <>
                    <XCircle className="size-10 text-red-500" />
                    <h1 className="text-lg font-semibold">{t('paymentFailed')}</h1>
                    {message ? <p className="text-sm text-muted-foreground">{message}</p> : null}
                    <Link to="/checkout" className="btn-primary mt-2 inline-flex">
                        {t('paymentTryAgain')}
                    </Link>
                </>
            ) : null}
        </div>
    );
}
