import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Check, Copy } from 'lucide-react';
import { checkoutBankDetails } from '../../lib/orderRules';

export default function CcpBankDetails({
    details = checkoutBankDetails,
    title,
    showNote = true,
    className = '',
}) {
    const { t } = useTranslation('billing');
    const [copied, setCopied] = useState('');

    const rows = useMemo(
        () => [
            { key: 'accountName', label: t('ccp.accountName'), valueKey: 'accountName' },
            { key: 'bankName', label: t('ccp.bankChannel'), valueKey: 'bankName' },
            { key: 'ccpAccount', label: t('ccp.ccpAccount'), valueKey: 'ccpAccount' },
            { key: 'rip', label: t('ccp.rip'), valueKey: 'rip' },
            { key: 'baridimobId', label: t('ccp.baridimobId'), valueKey: 'baridimobId' },
        ],
        [t],
    );

    async function copyValue(key, value, label) {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(key);
            window.setTimeout(() => setCopied(''), 1600);
        } catch {
            // ignore
        }
    }

    return (
        <section className={className}>
            <p className="text-sm font-semibold text-foreground">{title ?? t('ccp.bankDetailsTitle')}</p>
            {showNote ? <p className="mt-1 text-xs text-muted-foreground">{t('ccp.usernameNote')}</p> : null}
            <div className="mt-3 space-y-2">
                {rows.map((row) => (
                    <div
                        key={row.key}
                        className="flex items-center justify-between gap-3 rounded-lg border border-[var(--color-dash-border-subtle)] bg-[var(--color-dash-canvas)] px-3 py-2"
                    >
                        <div className="min-w-0">
                            <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                                {row.label}
                            </p>
                            <p className="truncate font-semibold tabular-nums text-foreground">
                                {details[row.valueKey]}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => copyValue(row.key, details[row.valueKey], row.label)}
                            className="inline-flex size-8 shrink-0 items-center justify-center rounded-md border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] text-muted-foreground hover:text-foreground"
                            aria-label={t('ccp.copyLabel', { label: row.label })}
                        >
                            {copied === row.key ? (
                                <Check className="size-3.5 text-emerald-600" />
                            ) : (
                                <Copy className="size-3.5" />
                            )}
                        </button>
                    </div>
                ))}
            </div>
        </section>
    );
}
