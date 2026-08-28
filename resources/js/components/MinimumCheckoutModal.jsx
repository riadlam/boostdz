import { useNavigate } from 'react-router-dom';
import { Wallet, X } from 'lucide-react';
import { formatDzd } from '../lib/formatMoney';

export default function MinimumCheckoutModal({ charge, minimum, message, onClose }) {
    const navigate = useNavigate();

    function goToBilling() {
        navigate(`/dashboard/billing?topup=${minimum}`);
        onClose?.();
    }

    return (
        <div className="dash-modal-root" role="presentation">
            <button type="button" className="dash-modal-backdrop" aria-label="Close" onClick={onClose} />
            <div role="dialog" aria-modal="true" aria-labelledby="minimum-checkout-title" className="dash-modal-panel">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.08em] text-muted-foreground">Checkout</p>
                        <h2 id="minimum-checkout-title" className="mt-1 text-lg font-semibold tracking-tight">
                            Minimum order amount
                        </h2>
                    </div>
                    <button type="button" className="dash-modal-close" onClick={onClose} aria-label="Close dialog">
                        <X className="size-4" strokeWidth={2} />
                    </button>
                </div>

                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                    {message ||
                        `Your order total is below the minimum checkout amount. Please top up your wallet first, then try again.`}
                </p>

                <div className="mt-4 rounded-xl border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)] px-3.5 py-3">
                    <div className="flex items-center justify-between gap-4 text-sm">
                        <span className="text-muted-foreground">Order total</span>
                        <span className="font-semibold tabular-nums">{formatDzd(charge)}</span>
                    </div>
                    <div className="mt-2 flex items-center justify-between gap-4 text-sm">
                        <span className="text-muted-foreground">Minimum required</span>
                        <span className="font-semibold tabular-nums text-primary">{formatDzd(minimum)}</span>
                    </div>
                </div>

                <div className="mt-5 flex flex-wrap items-center justify-end gap-2">
                    <button type="button" className="dash-modal-btn-secondary" onClick={onClose}>
                        Close
                    </button>
                    <button type="button" className="dash-modal-btn-primary" onClick={goToBilling}>
                        <Wallet className="size-3.5" strokeWidth={2} />
                        Go to Billing
                    </button>
                </div>
            </div>
        </div>
    );
}
