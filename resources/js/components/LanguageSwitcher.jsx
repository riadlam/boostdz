import { useTranslation } from 'react-i18next';
import { cn } from '../lib/cn';

const LOCALES = [
    { code: 'en', label: 'EN' },
    { code: 'fr', label: 'FR' },
    { code: 'ar', label: 'AR' },
];

export default function LanguageSwitcher({ className, compact = false }) {
    const { i18n, t } = useTranslation('common');

    return (
        <div
            className={cn('inline-flex items-center rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)] p-0.5', className)}
            role="group"
            aria-label={t('language.label')}
        >
            {LOCALES.map(({ code, label }) => {
                const active = i18n.language?.startsWith(code);
                return (
                    <button
                        key={code}
                        type="button"
                        onClick={() => i18n.changeLanguage(code)}
                        className={cn(
                            'rounded-md px-2 py-1 text-xs font-semibold transition',
                            compact ? 'min-w-7' : 'min-w-8',
                            active
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-[var(--color-dash-row-hover)] hover:text-foreground',
                        )}
                        aria-pressed={active}
                    >
                        {label}
                    </button>
                );
            })}
        </div>
    );
}
