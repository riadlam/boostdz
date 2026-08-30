import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { CheckCircle2, X, XCircle } from 'lucide-react';
import { cn } from '../lib/cn';

const AUTO_DISMISS_MS = 7000;

export default function PaymentResultModal({ type = 'success', orderId = null, onClose }) {
    const { t } = useTranslation(['checkout', 'common']);
    const isSuccess = type === 'success';

    useEffect(() => {
        const timer = window.setTimeout(() => onClose?.(), AUTO_DISMISS_MS);
        return () => window.clearTimeout(timer);
    }, [onClose]);

    const title = isSuccess
        ? t('paymentResult.successTitle')
        : t('paymentResult.failedTitle');
    const message = isSuccess
        ? (orderId
            ? t('paymentResult.successBodyWithOrder', { orderId })
            : t('paymentResult.successBody'))
        : t('paymentResult.failedBody');

    return (
        <div className="dash-modal-root" role="presentation">
            <button
                type="button"
                className="dash-modal-backdrop"
                aria-label={t('close', { ns: 'common' })}
                onClick={onClose}
            />
            <div role="dialog" aria-modal="true" aria-labelledby="payment-result-title" className="dash-modal-panel">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        {isSuccess ? (
                            <CheckCircle2 className="mt-0.5 size-6 shrink-0 text-emerald-500" />
                        ) : (
                            <XCircle className="mt-0.5 size-6 shrink-0 text-red-500" />
                        )}
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em] text-muted-foreground">
                                {t('paymentResult.kicker')}
                            </p>
                            <h2 id="payment-result-title" className="mt-1 text-lg font-semibold tracking-tight">
                                {title}
                            </h2>
                        </div>
                    </div>
                    <button
                        type="button"
                        className="dash-modal-close"
                        onClick={onClose}
                        aria-label={t('closeDialog', { ns: 'common' })}
                    >
                        <X className="size-4" strokeWidth={2} />
                    </button>
                </div>

                <p className={cn('mt-4 text-sm leading-relaxed text-muted-foreground', isSuccess && 'text-foreground/80')}>
                    {message}
                </p>

                <div className="mt-5 flex justify-end">
                    <button type="button" className="dash-modal-btn-primary" onClick={onClose}>
                        {t('close', { ns: 'common' })}
                    </button>
                </div>
            </div>
        </div>
    );
}
