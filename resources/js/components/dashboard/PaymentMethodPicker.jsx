import { useTranslation } from 'react-i18next';
import { Wallet } from 'lucide-react';
import { cn } from '../../lib/cn';

export default function PaymentMethodPicker({
    value,
    onChange,
    disabled = false,
    options = [],
    heading,
    subheading,
    className,
}) {
    const { t } = useTranslation('billing');

    return (
        <section
            className={cn(
                'rounded-2xl border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] p-4 shadow-sm',
                className,
            )}
        >
            <div className="mb-3 flex items-center justify-between gap-2">
                <p className="text-sm font-semibold text-foreground">{heading ?? t('paymentMethod')}</p>
                {(subheading ?? t('selectOne')) ? (
                    <span className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                        {subheading ?? t('selectOne')}
                    </span>
                ) : null}
            </div>

            <div className="grid gap-2.5">
                {options.map((option) => {
                    const selected = value === option.id;
                    const isDisabled = disabled || option.disabled;

                    return (
                        <button
                            key={option.id}
                            type="button"
                            disabled={isDisabled}
                            onClick={() => onChange(option.id)}
                            className={cn(
                                'flex w-full items-center gap-3 rounded-xl border px-3.5 py-3 text-left transition',
                                selected
                                    ? 'border-primary/55 bg-primary/8 ring-1 ring-primary/25 shadow-sm'
                                    : 'border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)]/40 hover:border-primary/35',
                                isDisabled && 'pointer-events-none opacity-60',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-4 shrink-0 items-center justify-center rounded-full border',
                                    selected
                                        ? 'border-primary bg-primary'
                                        : 'border-muted-foreground/40 bg-[var(--color-dash-surface)]',
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
                                                : option.id === 'wallet'
                                                  ? 'bg-violet-500/12 text-violet-800 dark:text-violet-200'
                                                  : 'bg-sky-500/12 text-sky-800 dark:text-sky-200',
                                        )}
                                    >
                                        {option.hint}
                                    </span>
                                </span>
                                <span className="text-xs text-muted-foreground">{option.description}</span>
                            </span>
                            <span className="hidden shrink-0 items-center gap-1.5 sm:flex">
                                {option.id === 'wallet' ? (
                                    <span className="flex size-10 items-center justify-center rounded-xl border border-[var(--color-dash-border-subtle)] bg-white shadow-sm dark:bg-[var(--color-dash-canvas)]">
                                        <Wallet className="size-5 text-violet-600" strokeWidth={2} />
                                    </span>
                                ) : (
                                    option.icons.map((src) => (
                                        <img
                                            key={src}
                                            src={src}
                                            alt=""
                                            className="size-10 rounded-xl border border-[var(--color-dash-border-subtle)] bg-white object-contain p-1 shadow-sm"
                                        />
                                    ))
                                )}
                            </span>
                        </button>
                    );
                })}
            </div>
        </section>
    );
}
