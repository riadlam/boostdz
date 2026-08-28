import { Globe2 } from 'lucide-react';
import * as FlagIcons from 'country-flag-icons/react/3x2';
import { cn } from '../lib/cn';

const COUNTRY_LABELS = {
    worldwide: 'Worldwide',
    us: 'United States',
    gb: 'United Kingdom',
    br: 'Brazil',
    id: 'Indonesia',
    in: 'India',
    tr: 'Turkey',
    ru: 'Russia',
    fr: 'France',
    de: 'Germany',
    eg: 'Egypt',
    dz: 'Algeria',
    ar: 'Argentina',
    mx: 'Mexico',
    it: 'Italy',
    es: 'Spain',
    ca: 'Canada',
    au: 'Australia',
    jp: 'Japan',
    kr: 'South Korea',
    cn: 'China',
    sa: 'Saudi Arabia',
    ae: 'United Arab Emirates',
    ma: 'Morocco',
    tn: 'Tunisia',
    ng: 'Nigeria',
    pk: 'Pakistan',
    bd: 'Bangladesh',
    ph: 'Philippines',
    vn: 'Vietnam',
    th: 'Thailand',
    pl: 'Poland',
    ua: 'Ukraine',
    nl: 'Netherlands',
    se: 'Sweden',
    no: 'Norway',
    pt: 'Portugal',
    gr: 'Greece',
    ro: 'Romania',
    za: 'South Africa',
    co: 'Colombia',
    cl: 'Chile',
    pe: 'Peru',
    my: 'Malaysia',
    sg: 'Singapore',
    il: 'Israel',
    iq: 'Iraq',
    ir: 'Iran',
    kw: 'Kuwait',
    qa: 'Qatar',
    bh: 'Bahrain',
    jo: 'Jordan',
    lb: 'Lebanon',
    sy: 'Syria',
    ye: 'Yemen',
    om: 'Oman',
    kz: 'Kazakhstan',
    uz: 'Uzbekistan',
    az: 'Azerbaijan',
    ge: 'Georgia',
    am: 'Armenia',
};

export function countryLabel(code) {
    if (!code) return 'Unknown';
    const key = String(code).toLowerCase();
    return COUNTRY_LABELS[key] || key.toUpperCase();
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
        { value: 'any', label: 'Any' },
        ...unique.map((code) => ({ value: code, label: countryLabel(code), code })),
    ];
}
