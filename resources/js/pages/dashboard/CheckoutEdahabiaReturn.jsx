import { useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { LoaderCircle } from 'lucide-react';
import { ApiError, sofizpayApi } from '../../lib/api';
import { useAuth } from '../../context/AuthContext';
import { clearCheckoutDraft } from '../../lib/orderRules';

const POLL_MS = 2500;
const MAX_POLLS = 24;

export default function CheckoutEdahabiaReturn() {
    const { t } = useTranslation(['checkout', 'common']);
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const invoiceId = searchParams.get('invoice_id') || '';
    const { refreshUser } = useAuth();

    useEffect(() => {
        function goToCreateOrder(notice) {
            navigate('/dashboard/orders/create', {
                replace: true,
                state: { paymentNotice: notice },
            });
        }

        function goToOrderHistory(notice) {
            navigate('/dashboard/orders/history', {
                replace: true,
                state: { paymentNotice: notice },
            });
        }

        if (!invoiceId) {
            goToCreateOrder({ type: 'error' });
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
                        goToOrderHistory({
                            type: 'success',
                            orderId: tx.order_id ?? null,
                        });
                        return;
                    }

                    if (txStatus === 'failed') {
                        goToCreateOrder({ type: 'error' });
                        return;
                    }
                } catch (error) {
                    if (error instanceof ApiError && attempts >= MAX_POLLS) {
                        goToCreateOrder({ type: 'error' });
                        return;
                    }
                }

                await new Promise((resolve) => setTimeout(resolve, POLL_MS));
            }

            if (active) {
                goToCreateOrder({ type: 'error' });
            }
        }

        poll();

        return () => {
            active = false;
        };
    }, [invoiceId, navigate, refreshUser]);

    return (
        <div className="mx-auto flex max-w-lg flex-col items-center gap-4 py-16 text-center">
            <LoaderCircle className="size-10 animate-spin text-primary" />
            <p className="text-sm text-muted-foreground">{t('payment.paymentPending')}</p>
        </div>
    );
}
