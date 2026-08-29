import { Globe2 } from 'lucide-react';
import * as FlagIcons from 'country-flag-icons/react/3x2';
import i18n from '../i18n';
import { cn } from '../lib/cn';

export function countryLabel(code) {
    if (!code) return i18n.t('unknown', { ns: 'countries' });
    const key = String(code).toLowerCase();
    return i18n.t(key, { ns: 'countries', defaultValue: key.toUpperCase() });
}

export function CountryFlag({ code, className, labelClassName, showLabel = true }) {
    const key = String(code || '').toLowerCase();
    const label = countryLabel(key);
    const Flag = key !== 'worldwide' ? FlagIcons[key.toUpperCase()] : null;

    return (
        <span className={cn('inline-flex min-w-0 items-center gap-1.5', className)}>
            {key === 'worldwide' || !Flag ? (
                <Globe2 className="size-4 shrink-0 text-primary" />
            ) : (
                <Flag className="size-4 shrink-0 rounded-[2px] shadow-sm" title={label} />
            )}
            {showLabel ? <span className={cn('truncate', labelClassName)}>{label}</span> : null}
        </span>
    );
}

export function buildCountryOptions(codes) {
    const unique = [...new Set((codes || []).map((c) => String(c).toLowerCase()).filter(Boolean))];
    unique.sort((a, b) => {
        if (a === 'worldwide') return -1;
        if (b === 'worldwide') return 1;
        return countryLabel(a).localeCompare(countryLabel(b));
    });
    return [
        { value: 'any', label: i18n.t('filters.any', { ns: 'orders' }) },
        ...unique.map((code) => ({ value: code, label: countryLabel(code), code })),
    ];
}
