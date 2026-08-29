import { useTranslation } from 'react-i18next';
import { LoaderCircle, Paperclip, Upload } from 'lucide-react';
import { cn } from '../../lib/cn';

export default function CcpReceiptFields({
    amount,
    onAmountChange,
    reference,
    onReferenceChange,
    file,
    onFileChange,
    amountLabel,
    referenceLabel,
    referencePlaceholder,
    uploadLabel,
    disabled = false,
    amountRequired = true,
    fileRequired = true,
}) {
    const { t } = useTranslation('billing');

    return (
        <div className="space-y-3">
            <label className="block">
                <span className="mb-1 block text-xs font-medium text-muted-foreground">
                    {amountLabel ?? t('ccp.amountTransferred')}
                </span>
                <input
                    type="number"
                    min="1"
                    step="1"
                    value={amount}
                    onChange={(e) => onAmountChange(e.target.value)}
                    className="dash-input"
                    required={amountRequired}
                    disabled={disabled}
                />
            </label>
            <label className="block">
                <span className="mb-1 block text-xs font-medium text-muted-foreground">
                    {referenceLabel ?? t('ccp.paymentReference')}
                </span>
                <input
                    type="text"
                    value={reference}
                    onChange={(e) => onReferenceChange(e.target.value)}
                    placeholder={referencePlaceholder ?? t('ccp.referencePlaceholder')}
                    className="dash-input"
                    disabled={disabled}
                />
            </label>
            <label
                className={cn(
                    'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-6 text-center transition',
                    file
                        ? 'border-primary/40 bg-primary/5'
                        : 'border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)] hover:border-primary/30',
                    disabled && 'pointer-events-none opacity-60',
                )}
            >
                {file ? <Paperclip className="size-5 text-primary" /> : <Upload className="size-5 text-muted-foreground" />}
                <span className="text-sm font-medium text-foreground">
                    {file ? file.name : (uploadLabel ?? t('ccp.uploadReceipt'))}
                </span>
                <span className="text-xs text-muted-foreground">{t('ccp.fileTypes')}</span>
                <input
                    type="file"
                    accept="image/*,.pdf"
                    className="hidden"
                    onChange={(e) => onFileChange(e.target.files?.[0] || null)}
                    required={fileRequired}
                    disabled={disabled}
                />
            </label>
        </div>
    );
}

export function CcpSubmitButton({ submitting, disabled, label }) {
    const { t } = useTranslation('billing');

    return (
        <button
            type="submit"
            disabled={disabled || submitting}
            className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-45"
        >
            {submitting ? (
                <>
                    <LoaderCircle className="size-4 animate-spin" />
                    {t('ccp.sendingReceipt')}
                </>
            ) : (
                (label ?? t('submitForVerification'))
            )}
        </button>
    );
}
