import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';
import { useNavigate, useLocation, useSearchParams } from 'react-router-dom';
import {
    ArrowRight,
    Bookmark,
    Check,
    ChevronDown,
    ChevronsUpDown,
    ClipboardPaste,
    Eye,
    Globe2,
    Heart,
    Layers,
    MessageCircle,
    Package,
    Repeat2,
    Search,
    Settings2,
    Share2,
    ShieldCheck,
    Sparkles,
    Target,
    Users,
    Zap,
} from 'lucide-react';
import { CountryFlag, buildCountryOptions, countryLabel } from '../../components/CountryFlag';
import PaymentResultModal from '../../components/PaymentResultModal';
import { useAuth } from '../../context/AuthContext';
import { ApiError, catalogApi, ordersApi } from '../../lib/api';
import { cn } from '../../lib/cn';
import { chargeForService, formatDzd, roundDzd } from '../../lib/formatMoney';
import { getTargetFieldMeta } from '../../lib/targetFieldMeta';
import {
    formatCommentsForApi,
    isCustomCommentsPackage,
    isCustomCommentsService,
    parseCommentLines,
    saveCheckoutDraft,
    validateCustomComments,
} from '../../lib/orderRules';
import { getCategoryIcon } from '../../lib/categoryIcons';
import { buildFacebookReactionOptions, facebookReactionLabel } from '../../lib/facebookReactions';
import { scrollDashboardToTop, scrollToFirstFormError } from '../../lib/formScroll';
import { filterCatalogEntries, getPlatformIcon } from '../../lib/platformIcons';
import { canViewServiceCatalog } from '../../lib/serviceCatalogVisibility';

const EMPTY_FILTERS = {
    quality_tier: '',
    start_class: '',
    refill_mode: '',
    has_refill: '',
    is_hot: '',
    is_cheap: '',
    country_code: '',
    audience_gender: '',
    reaction_type: '',
    refill_days: '',
};

const EMPTY_REFINE = {
    quality: 'any',
    delivery: 'any',
    protection: 'any',
    country: 'any',
    audience: 'any',
    reaction: 'any',
    refillDays: [],
};

function buildDeliveryRefineOptions(services, platformSlug, categorySlug, t) {
    const list = Array.isArray(services) ? services : [];
    const tiers = new Set(list.map((s) => s.quality_tier).filter(Boolean));
    const starts = new Set(list.map((s) => (s.start_class || 'normal').toLowerCase()));
    const hasHot = list.some((s) => s.is_hot);
    const hasCheap = list.some((s) => s.is_cheap);
    const hasRefill = list.some((s) => s.refill || (s.refill_mode && s.refill_mode !== 'none'));
    const hasAuto = list.some((s) => s.refill_mode === 'auto');
    const hasLifetime = list.some((s) => s.refill_mode === 'lifetime');
    const hasNoRefill = list.some((s) => !s.refill && (!s.refill_mode || s.refill_mode === 'none'));
    const hasDrip = list.some((s) => s.dripfeed);

    const qualityOptions = [{ value: 'any', label: t('filters.any') }];
    if (tiers.has('premium')) qualityOptions.push({ value: 'premium', label: t('filters.premium') });
    if (tiers.has('standard')) qualityOptions.push({ value: 'standard', label: t('filters.standard') });
    if (tiers.has('economy')) qualityOptions.push({ value: 'economy', label: t('filters.economy') });
    if (hasHot) qualityOptions.push({ value: 'top', label: t('filters.topSellers') });
    if (hasCheap) qualityOptions.push({ value: 'budget', label: t('filters.budget') });

    const deliveryOptions = [{ value: 'any', label: t('filters.any') }];
    if (starts.has('instant')) deliveryOptions.push({ value: 'instant', label: t('filters.instant') });
    if (starts.has('instant') || starts.has('fast')) {
        deliveryOptions.push({ value: 'fast', label: t('filters.fast') });
    }
    if (starts.has('normal')) deliveryOptions.push({ value: 'normal', label: t('filters.normal') });
    if (starts.has('slow')) deliveryOptions.push({ value: 'slow', label: t('filters.slower') });

    const protectionOptions = [{ value: 'any', label: t('filters.any') }];
    if (hasRefill) protectionOptions.push({ value: 'refill', label: t('filters.withRefill') });
    if (hasAuto) protectionOptions.push({ value: 'auto', label: t('filters.autoRefill') });
    if (hasLifetime) protectionOptions.push({ value: 'lifetime', label: t('filters.lifetimeRefill') });
    if (hasNoRefill && hasRefill) protectionOptions.push({ value: 'none', label: t('filters.noRefill') });

    const refillDayOptions = [...new Set(
        list
            .map((s) => Number(s.refill_days))
            .filter((n) => Number.isFinite(n) && n > 0),
    )]
        .sort((a, b) => a - b)
        .map((days) => ({
            value: days,
            label: days === 365
                ? t('warrantyDaysOneYear', { defaultValue: '365 days (1 year)' })
                : t('warranty.days', { days }),
        }));

    const hasSpeedChoice = ['instant', 'fast', 'slow'].some((k) => starts.has(k));
    const countryCodes = list.map((s) => s.country_code).filter(Boolean);
    const countryOptions = buildCountryOptions(countryCodes);

    const hasMale = list.some((s) => s.audience_gender === 'male');
    const hasFemale = list.some((s) => s.audience_gender === 'female');
    const audienceOptions = [{ value: 'any', label: t('filters.any') }];
    if (hasMale) audienceOptions.push({ value: 'male', label: t('filters.men') });
    if (hasFemale) audienceOptions.push({ value: 'female', label: t('filters.women') });

    const reactionTypes = [...new Set(list.map((s) => s.reaction_type).filter(Boolean))];
    const reactionOptions = platformSlug === 'facebook' && ['likes', 'stories'].includes(categorySlug)
        ? buildFacebookReactionOptions(reactionTypes)
        : [];

    return {
        qualityOptions,
        deliveryOptions,
        protectionOptions,
        countryOptions,
        audienceOptions,
        reactionOptions,
        refillDayOptions,
        showQuality: qualityOptions.length > 1,
        showDelivery: hasSpeedChoice,
        showProtection: hasRefill,
        showCountry: countryOptions.length > 1,
        showAudience: hasMale && hasFemale,
        showReaction: reactionOptions.length > 1,
        showRefillDays: refillDayOptions.length > 0,
        showDrip: hasDrip,
    };
}

function isRefineEmpty(refine) {
    if (!refine) return true;

    return (
        refine.quality === 'any'
        && refine.delivery === 'any'
        && refine.protection === 'any'
        && (refine.country || 'any') === 'any'
        && (refine.audience || 'any') === 'any'
        && (refine.reaction || 'any') === 'any'
        && !(refine.refillDays || []).length
    );
}

function sameServiceId(left, right) {
    if (left == null || right == null) return false;
    return Number(left) === Number(right);
}

function pickPreferredService(items, {
    preferFirst = false,
    preferCustomComments = false,
    preferredServiceId = null,
    useFeaturedDefault = false,
} = {}) {
    if (!items?.length) return null;
    if (preferFirst && preferCustomComments) {
        const custom = items.find((service) => isCustomCommentsService(service));
        if (custom) return custom;
    }
    if (preferFirst && useFeaturedDefault && preferredServiceId) {
        const featured = items.find((service) => sameServiceId(service.id, preferredServiceId));
        if (featured) return featured;
    }
    if (preferFirst) {
        return items[0] ?? null;
    }
    return null;
}

function refineFromService(service) {
    if (!service) return { ...EMPTY_REFINE };

    const next = { ...EMPTY_REFINE };

    const tier = String(service.quality_tier || '').toLowerCase();
    if (tier === 'premium') next.quality = 'premium';
    else if (tier === 'standard') next.quality = 'standard';
    else if (tier === 'economy') next.quality = 'economy';
    else if (service.is_hot) next.quality = 'top';
    else if (service.is_cheap) next.quality = 'budget';

    const start = String(service.start_class || 'normal').toLowerCase();
    if (start === 'instant') next.delivery = 'instant';
    else if (start === 'fast') next.delivery = 'fast';
    else if (start === 'slow') next.delivery = 'slow';
    else if (start === 'normal') next.delivery = 'normal';

    const mode = String(service.refill_mode || '').toLowerCase();
    if (mode === 'auto') next.protection = 'auto';
    else if (mode === 'lifetime') next.protection = 'lifetime';
    else if (service.refill || (mode && mode !== 'none')) next.protection = 'refill';

    if (service.country_code) {
        next.country = String(service.country_code).toLowerCase();
    }

    if (service.audience_gender === 'male') next.audience = 'male';
    else if (service.audience_gender === 'female') next.audience = 'female';

    if (service.reaction_type) {
        next.reaction = String(service.reaction_type).toLowerCase();
    }

    const days = Number(service.refill_days);
    if (Number.isFinite(days) && days > 0) {
        next.refillDays = [days];
    }

    return next;
}

function clampRefineToOptions(refine, caps) {
    const next = { ...refine };
    let changed = false;
    if (!caps.qualityOptions.some((o) => o.value === next.quality)) {
        next.quality = 'any';
        changed = true;
    }
    if (!caps.showQuality && next.quality !== 'any') {
        next.quality = 'any';
        changed = true;
    }
    if (!caps.deliveryOptions.some((o) => o.value === next.delivery) || !caps.showDelivery) {
        if (next.delivery !== 'any') {
            next.delivery = 'any';
            changed = true;
        }
    }
    if (!caps.protectionOptions.some((o) => o.value === next.protection) || !caps.showProtection) {
        if (next.protection !== 'any') {
            next.protection = 'any';
            changed = true;
        }
    }
    if (!caps.countryOptions?.some((o) => o.value === next.country) || !caps.showCountry) {
        if (next.country !== 'any') {
            next.country = 'any';
            changed = true;
        }
    }
    if (!caps.audienceOptions?.some((o) => o.value === next.audience) || !caps.showAudience) {
        if (next.audience !== 'any') {
            next.audience = 'any';
            changed = true;
        }
    }
    if (!caps.reactionOptions?.some((o) => o.value === next.reaction) || !caps.showReaction) {
        if (next.reaction !== 'any') {
            next.reaction = 'any';
            changed = true;
        }
    }
    const allowedDays = new Set((caps.refillDayOptions || []).map((o) => o.value));
    const nextDays = (Array.isArray(next.refillDays) ? next.refillDays : []).filter((d) => allowedDays.has(d));
    if (nextDays.length !== (next.refillDays || []).length) {
        next.refillDays = nextDays;
        changed = true;
    }
    if (!caps.showRefillDays && nextDays.length) {
        next.refillDays = [];
        changed = true;
    }
    return { next, changed };
}

function refineToFilters(refine) {
    const next = { ...EMPTY_FILTERS };

    if (refine.quality === 'premium') next.quality_tier = 'premium';
    if (refine.quality === 'standard') next.quality_tier = 'standard';
    if (refine.quality === 'economy') next.quality_tier = 'economy';
    if (refine.quality === 'top') next.is_hot = '1';
    if (refine.quality === 'budget') next.is_cheap = '1';

    if (refine.delivery === 'instant') next.start_class = 'instant';
    if (refine.delivery === 'fast') next.start_class = 'instant,fast';
    if (refine.delivery === 'normal') next.start_class = 'normal';
    if (refine.delivery === 'slow') next.start_class = 'slow';

    if (refine.protection === 'refill') next.has_refill = '1';
    if (refine.protection === 'auto') {
        next.refill_mode = 'auto';
        next.has_refill = '1';
    }
    if (refine.protection === 'lifetime') {
        next.refill_mode = 'lifetime';
        next.has_refill = '1';
    }
    if (refine.protection === 'none') {
        next.has_refill = '0';
        next.refill_mode = 'none';
    }

    if (refine.country && refine.country !== 'any') {
        next.country_code = refine.country;
    }

    if (refine.audience && refine.audience !== 'any') {
        next.audience_gender = refine.audience;
    }

    if (refine.reaction && refine.reaction !== 'any') {
        next.reaction_type = refine.reaction;
    }

    const days = Array.isArray(refine.refillDays) ? refine.refillDays.filter((d) => d > 0) : [];
    if (days.length) {
        next.refill_days = days.join(',');
        if (!next.has_refill) next.has_refill = '1';
    }

    return next;
}

function optionLabel(options, value, t) {
    return options.find((opt) => opt.value === value)?.label || t('filters.any');
}

function audienceLabel(value, t) {
    if (value === 'male') return t('filters.men');
    if (value === 'female') return t('filters.women');
    return t('filters.any');
}

function formatAmount(n) {
    return Number(n || 0).toLocaleString('fr-DZ', { maximumFractionDigits: 0 });
}

function formatTierOption(entry, t, showServiceId) {
    const name = t(`tiers.${entry.tier}`, { defaultValue: entry.tier });
    const price = entry.service?.sell_rate_dzd
        ? `${formatDzd(entry.service.sell_rate_dzd)} / 1k`
        : null;

    if (showServiceId && entry.service_id) {
        return {
            primary: `#${entry.service_id} · ${name}`,
            price,
        };
    }

    return { primary: name, price };
}

function SkeletonBar({ className }) {
    return <div className={cn('h-4 animate-pulse rounded bg-muted', className)} />;
}

function DropdownSkeletonRows({ count = 3 }) {
    return (
        <div className="space-y-1 p-1">
            {Array.from({ length: count }).map((_, index) => (
                <div key={index} className="px-3 py-2">
                    <SkeletonBar className="h-4 w-full" />
                </div>
            ))}
        </div>
    );
}

function chargeFor(service, quantity) {
    return chargeForService(service, quantity);
}

function quantityPresets(min, max) {
    const candidates = [min, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000, max];
    const unique = [...new Set(candidates.filter((n) => n >= min && n <= max))];
    return unique.sort((a, b) => a - b).slice(0, 10);
}

function serviceBadges(service, t) {
    const badges = [];
    if (service.is_hot) badges.push({ key: 'hot', label: t('badges.top', { defaultValue: 'Top' }), tone: 'hot' });
    if (service.is_cheap) badges.push({ key: 'cheap', label: t('badges.cheap'), tone: 'warn' });
    if (service.start_class === 'instant') badges.push({ key: 'instant', label: t('badges.instant'), tone: 'ok' });
    else if (service.start_class === 'fast') badges.push({ key: 'fast', label: t('badges.fast'), tone: 'ok' });
    if (service.refill_mode === 'auto') {
        badges.push({
            key: 'ar',
            label: service.refill_days
                ? t('badges.autoRefillDays', { days: service.refill_days, defaultValue: `AR${service.refill_days}` })
                : t('badges.autoRefill'),
            tone: 'info',
        });
    } else if (service.refill_mode === 'lifetime') {
        badges.push({ key: 'life', label: t('badges.lifetime'), tone: 'info' });
    } else if (service.refill_mode === 'manual' || service.refill) {
        badges.push({
            key: 'r',
            label: service.refill_days
                ? t('badges.refillDays', { days: service.refill_days, defaultValue: `R${service.refill_days}` })
                : t('badges.refill'),
            tone: 'info',
        });
    }
    if (service.country_code) {
        badges.push({ key: 'country', label: countryLabel(service.country_code), tone: 'muted' });
    }
    if (service.dripfeed) badges.push({ key: 'drip', label: t('badges.drip'), tone: 'muted' });
    if (service.quality_tier) {
        const tierKey = String(service.quality_tier).toLowerCase();
        const tierLabel = ['premium', 'standard', 'economy'].includes(tierKey)
            ? t(`filters.${tierKey}`)
            : service.quality_tier;
        badges.push({ key: 'tier', label: tierLabel, tone: 'muted' });
    }
    return badges;
}

function Badge({ label, tone }) {
    const tones = {
        hot: 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
        warn: 'bg-orange-500/12 text-orange-800 dark:text-orange-300',
        ok: 'bg-emerald-500/12 text-emerald-800 dark:text-emerald-300',
        info: 'bg-sky-500/12 text-sky-800 dark:text-sky-300',
        muted: 'bg-muted text-muted-foreground',
    };
    return (
        <span className={cn('inline-flex items-center rounded-md px-1.5 py-0.5 text-[0.6875rem] font-bold uppercase tracking-wide', tones[tone] || tones.muted)}>
            {label}
        </span>
    );
}

function StatusPill({ on, onLabel, offLabel }) {
    return (
        <span className="inline-flex rounded-full bg-secondary px-1.5 py-0 text-[10px] font-normal text-muted-foreground">
            {on ? onLabel : offLabel}
        </span>
    );
}

function SelectButton({ open, onClick, children, invalid, disabled, hasValue }) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            data-open={open ? 'true' : undefined}
            data-invalid={invalid ? 'true' : undefined}
            data-selected={hasValue ? 'true' : undefined}
            className="order-combobox disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
            data-order-trigger=""
        >
            <span className="group relative min-w-0 flex-1 overflow-hidden text-left">
                <span
                    className={cn(
                        'order-combobox-value flex min-w-0 items-center gap-2 text-sm font-semibold',
                        hasValue ? 'text-foreground' : 'text-muted-foreground',
                    )}
                >
                    {children}
                </span>
            </span>
            <ChevronsUpDown className="z-10 ml-1 size-4 shrink-0 opacity-50" />
        </button>
    );
}

function InlineSelect({ value, options, open, onOpen, onChange, disabled, emptyLabel }) {
    const selected = options.find((opt) => opt.value === value) || options[0];
    const isSet = value && value !== 'any';

    return (
        <div className="relative inline-flex align-middle">
            <button
                type="button"
                disabled={disabled}
                onClick={onOpen}
                data-order-trigger=""
                className={cn(
                    'inline-flex h-5 min-w-0 cursor-pointer items-center gap-0.5 border-0 border-b border-dashed border-muted-foreground/40 bg-transparent px-0.5 text-sm transition-colors hover:border-primary focus:border-primary focus:outline-none disabled:opacity-50',
                    open && 'border-primary',
                    isSet ? 'font-semibold text-primary' : 'font-medium text-muted-foreground',
                )}
            >
                {selected?.code ? (
                    <CountryFlag code={selected.code} labelClassName="text-inherit" />
                ) : (
                    <span className="truncate">{selected?.label || emptyLabel}</span>
                )}
                <ChevronDown className={cn('size-3.5 shrink-0 transition', isSet ? 'text-primary' : 'text-muted-foreground', open && 'rotate-180')} />
            </button>
            <Dropdown open={open} className="left-0 mt-1 min-w-[12rem]">
                {options.map((opt) => (
                    <Option key={opt.value} active={opt.value === value} onClick={() => onChange(opt.value)}>
                        {opt.code ? <CountryFlag code={opt.code} /> : opt.label}
                    </Option>
                ))}
            </Dropdown>
        </div>
    );
}

function SettingsRow({ icon: Icon, title, active, open, onToggle, children, statusOnLabel, statusOffLabel }) {
    return (
        <div className="rounded-md border border-input bg-transparent shadow-xs">
            <button
                type="button"
                onClick={onToggle}
                className={cn(
                    'flex h-9 w-full cursor-pointer items-center gap-2 px-3 text-left text-sm font-medium transition-colors',
                    open && 'border-b border-border/50',
                )}
            >
                <Icon className="size-4 text-muted-foreground" />
                <span className="font-medium">{title}</span>
                <StatusPill on={active} onLabel={statusOnLabel} offLabel={statusOffLabel} />
                <ChevronDown className={cn('ml-auto size-4 text-muted-foreground transition-transform duration-200', open && 'rotate-180')} />
            </button>
            <div className="order-collapsible" data-open={open ? 'true' : undefined}>
                <div className="order-collapsible-inner">
                    <div className="p-3">{children}</div>
                </div>
            </div>
        </div>
    );
}

function Dropdown({ open, children, className }) {
    const markerRef = useRef(null);
    const [pos, setPos] = useState(null);

    useLayoutEffect(() => {
        if (!open) {
            setPos(null);
            return undefined;
        }

        function update() {
            const parent = markerRef.current?.parentElement;
            if (!parent) return;
            const rect = parent.getBoundingClientRect();
            const minWidth = Math.max(rect.width, 192);
            const margin = 8;
            const maxWidth = window.innerWidth - margin * 2;
            const clampedMinWidth = Math.min(minWidth, maxWidth);
            let left = rect.left;
            if (left + clampedMinWidth > window.innerWidth - margin) {
                left = Math.max(margin, window.innerWidth - margin - clampedMinWidth);
            }
            setPos({
                top: rect.bottom + 6,
                left,
                minWidth: clampedMinWidth,
                maxWidth,
            });
        }

        update();
        window.addEventListener('resize', update);
        window.addEventListener('scroll', update, true);
        return () => {
            window.removeEventListener('resize', update);
            window.removeEventListener('scroll', update, true);
        };
    }, [open]);

    return (
        <>
            <span ref={markerRef} className="pointer-events-none absolute" aria-hidden />
            {open && pos
                ? createPortal(
                    <div
                        className={cn(
                            'order-dropdown z-[90] max-h-80 overflow-auto rounded-md border border-border bg-card p-1 shadow-md',
                            className,
                        )}
                        style={{
                            position: 'fixed',
                            top: pos.top,
                            left: pos.left,
                            minWidth: pos.minWidth,
                            maxWidth: pos.maxWidth,
                        }}
                    >
                        {children}
                    </div>,
                    document.body,
                )
                : null}
        </>
    );
}

function Option({ active, onClick, children, dense }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 text-left font-semibold text-foreground transition-colors duration-150 hover:bg-accent',
                dense ? 'py-0.5 text-[0.8125rem] leading-tight' : 'px-2.5 py-2 text-sm',
                active && 'bg-accent',
            )}
        >
            <span className="min-w-0 flex-1">{children}</span>
            {active ? <Check className={cn('shrink-0 text-primary', dense ? 'size-3' : 'size-3.5')} /> : null}
        </button>
    );
}

function FieldLabel({ children, required }) {
    return (
        <label className="inline-flex items-center gap-1 text-sm leading-none font-medium select-none">
            <span className="contents">{children}</span>
            {required ? <span className="text-xs text-destructive/40">*</span> : null}
        </label>
    );
}

function filtersToParams(filters) {
    const params = { per_page: 200 };
    Object.entries(filters).forEach(([key, value]) => {
        if (value === '' || value === null || value === undefined) return;
        params[key] = value;
    });
    return params;
}

export default function CreateOrder() {
    const { t } = useTranslation(['orders', 'common', 'validation']);
    const { user, refreshUser } = useAuth();
    const showServiceCatalog = canViewServiceCatalog(user);
    const navigate = useNavigate();
    const location = useLocation();
    const [searchParams] = useSearchParams();
    const initialUrlRef = useRef({
        platform: searchParams.get('platform'),
        category: searchParams.get('category'),
        service: searchParams.get('service') ? Number(searchParams.get('service')) : null,
    });
    const urlAppliedRef = useRef(false);

    const [platforms, setPlatforms] = useState([]);
    const [platformSlug, setPlatformSlug] = useState('');
    const [categories, setCategories] = useState([]);
    const [categoryId, setCategoryId] = useState(null);
    const [groups, setGroups] = useState([]);
    const [categoryServices, setCategoryServices] = useState([]);
    const [serviceSearch, setServiceSearch] = useState('');
    const [tierCatalog, setTierCatalog] = useState([]);
    const [selectedTier, setSelectedTier] = useState('');
    const [loadingTiers, setLoadingTiers] = useState(false);
    const [refine, setRefine] = useState(EMPTY_REFINE);
    const [serviceId, setServiceId] = useState(null);
    const [loadingPlatforms, setLoadingPlatforms] = useState(true);
    const [loadingCategories, setLoadingCategories] = useState(false);
    const [loadingServices, setLoadingServices] = useState(false);
    const [quantity, setQuantity] = useState(1000);
    const [customMode, setCustomMode] = useState(false);
    const [customQuantity, setCustomQuantity] = useState('1000');
    const [link, setLink] = useState('');
    const [customCommentsText, setCustomCommentsText] = useState('');
    const [useCustomComments, setUseCustomComments] = useState(false);
    const [isRepeat, setIsRepeat] = useState(false);
    const [openMenu, setOpenMenu] = useState(null);
    const [deliverySettingsOpen, setDeliverySettingsOpen] = useState(false);
    const [settingsRowOpen, setSettingsRowOpen] = useState(null);
    const [errors, setErrors] = useState({});
    const [formError, setFormError] = useState('');
    const [paymentNotice, setPaymentNotice] = useState(null);

    const searchTimer = useRef(null);
    const requestSeq = useRef(0);
    const storefrontApplyRef = useRef(false);

    const selectedPlatform = useMemo(
        () => platforms.find((p) => p.slug === platformSlug) || null,
        [platforms, platformSlug],
    );
    const selectedCategory = useMemo(
        () => categories.find((c) => c.id === categoryId) || null,
        [categories, categoryId],
    );
    const flatServices = useMemo(
        () => groups.flatMap((group) => group.items || []),
        [groups],
    );
    const selectedService = useMemo(() => {
        if (!showServiceCatalog) {
            const fromTier = tierCatalog.find((entry) => entry.tier === selectedTier)?.service
                || tierCatalog.find((entry) => sameServiceId(entry.service_id, serviceId))?.service;
            if (fromTier) {
                return fromTier;
            }
        }

        return flatServices.find((s) => sameServiceId(s.id, serviceId)) || null;
    }, [showServiceCatalog, tierCatalog, selectedTier, serviceId, flatServices]);
    const selectedTierEntry = useMemo(
        () => tierCatalog.find((entry) => entry.tier === selectedTier) || null,
        [tierCatalog, selectedTier],
    );
    const selectedTierText = useMemo(() => {
        if (!selectedTierEntry) {
            return null;
        }

        return formatTierOption(selectedTierEntry, t, showServiceCatalog);
    }, [selectedTierEntry, showServiceCatalog, t]);
    const deliveryCaps = useMemo(() => {
        // Prefer full category catalog; fall back to current list / selected product.
        const pool = categoryServices.length
            ? categoryServices
            : selectedService
              ? [selectedService, ...flatServices]
              : flatServices;
        return buildDeliveryRefineOptions(pool, platformSlug, selectedCategory?.slug, t);
    }, [categoryServices, flatServices, selectedService, platformSlug, selectedCategory?.slug, t]);
    const qualityOptions = deliveryCaps.qualityOptions;
    const deliveryOptions = deliveryCaps.deliveryOptions;
    const protectionOptions = deliveryCaps.protectionOptions;
    const countryOptions = deliveryCaps.countryOptions;
    const audienceOptions = deliveryCaps.audienceOptions;
    const reactionOptions = deliveryCaps.reactionOptions || [];
    const refillDayOptions = deliveryCaps.refillDayOptions || [];
    const showQualitySettings = deliveryCaps.showQuality;
    const showDeliverySettings = deliveryCaps.showDelivery;
    const showProtectionSettings = deliveryCaps.showProtection;
    const showCountrySettings = deliveryCaps.showCountry;
    const showAudienceSettings = deliveryCaps.showAudience;
    const showReactionSettings = deliveryCaps.showReaction;
    const showRefillDays = deliveryCaps.showRefillDays;
    const isCommentsCategory = selectedCategory?.slug === 'comments';
    const requiresCustomComments = useMemo(
        () => isCustomCommentsService(selectedService),
        [selectedService],
    );
    const showCustomCommentsSettings = isCommentsCategory;
    const hasRefineRows = showQualitySettings || showDeliverySettings || showProtectionSettings || showCountrySettings || showAudienceSettings || showReactionSettings || showCustomCommentsSettings;
    const presets = useMemo(
        () => (selectedService ? quantityPresets(selectedService.min, selectedService.max) : []),
        [selectedService],
    );
    const target = useMemo(
        () =>
            getTargetFieldMeta(
                {
                    platformSlug,
                    categorySlug: selectedCategory?.slug,
                    service: selectedService,
                },
                t,
            ),
        [platformSlug, selectedCategory?.slug, selectedService, t],
    );
    const charge = useMemo(() => chargeFor(selectedService, quantity), [selectedService, quantity]);
    const commentLines = useMemo(() => parseCommentLines(customCommentsText), [customCommentsText]);
    const commentValidation = useMemo(
        () => validateCustomComments({
            service: selectedService,
            quantity,
            commentsText: useCustomComments ? customCommentsText : '',
            t: (key, opts) => t(key, { ns: 'validation', ...opts }),
        }),
        [selectedService, quantity, customCommentsText, useCustomComments, t],
    );
    const commentsQtyMatch = !useCustomComments
        || !requiresCustomComments
        || isCustomCommentsPackage(selectedService)
        || commentLines.length === quantity;
    const balance = Number(user?.wallet?.available_balance ?? user?.wallet?.balance ?? 0);
    const hasRefillDayRefine = (refine.refillDays || []).length > 0;
    const hasActiveRefine =
        (showQualitySettings && refine.quality !== 'any')
        || (showDeliverySettings && refine.delivery !== 'any')
        || (showProtectionSettings && refine.protection !== 'any')
        || (showCountrySettings && refine.country !== 'any')
        || (showAudienceSettings && refine.audience !== 'any')
        || (showReactionSettings && refine.reaction !== 'any')
        || hasRefillDayRefine;
    const deliverySettingsActive = hasActiveRefine || isRepeat || (showCustomCommentsSettings && useCustomComments);
    const protectionRowActive = (showProtectionSettings && refine.protection !== 'any') || hasRefillDayRefine;

    useEffect(() => {
        const notice = location.state?.paymentNotice;
        if (!notice?.type) return;

        setPaymentNotice(notice);
        scrollDashboardToTop();
        navigate(`${location.pathname}${location.search}`, { replace: true, state: null });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [location.state?.paymentNotice]);

    useEffect(() => {
        let cancelled = false;
        (async () => {
            setLoadingPlatforms(true);
            try {
                const data = await catalogApi.platforms();
                if (cancelled) return;
                const list = filterCatalogEntries(data.platforms || []).filter(
                    (p) => (p.services_count ?? 0) > 0 || (p.categories_count ?? 0) > 0,
                );
                const usable = list.length ? list : filterCatalogEntries(data.platforms || []);
                setPlatforms(usable);
                const urlPlatform = !urlAppliedRef.current ? initialUrlRef.current.platform : null;
                const fromUrl = urlPlatform ? usable.find((p) => p.slug === urlPlatform) : null;
                const preferred = fromUrl || usable.find((p) => p.slug === 'instagram') || usable[0];
                setPlatformSlug(preferred?.slug || '');
            } catch (error) {
                if (!cancelled) {
                    setFormError(error instanceof ApiError ? error.message : t('errors.loadPlatforms'));
                }
            } finally {
                if (!cancelled) setLoadingPlatforms(false);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        if (!platformSlug) return undefined;
        let cancelled = false;

        (async () => {
            setLoadingCategories(true);
            setFormError('');
            setCategories([]);
            setCategoryId(null);
            setGroups([]);
            setCategoryServices([]);
            setServiceId(null);
            setServiceSearch('');
            setRefine(EMPTY_REFINE);
            try {
                const data = await catalogApi.categories(platformSlug);
                if (cancelled) return;
                const rows = filterCatalogEntries(data.categories || []).filter((c) => (c.services_count ?? 0) > 0);
                const list = rows.length ? rows : filterCatalogEntries(data.categories || []);
                setCategories(list);
                const urlCategory = !urlAppliedRef.current ? initialUrlRef.current.category : null;
                const fromUrl = urlCategory ? list.find((c) => c.slug === urlCategory) : null;
                setCategoryId(fromUrl?.id ?? list[0]?.id ?? null);
            } catch (error) {
                if (!cancelled) {
                    setCategories([]);
                    setCategoryId(null);
                    setFormError(error instanceof ApiError ? error.message : t('errors.loadCategories'));
                }
            } finally {
                if (!cancelled) setLoadingCategories(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [platformSlug]);

    function tierLabel(tier) {
        return t(`tiers.${tier}`, { defaultValue: tier });
    }

    function syncTierFromService(id) {
        if (!showServiceCatalog || tierCatalog.length === 0) {
            return;
        }

        const matchedTier = tierCatalog.find((entry) => sameServiceId(entry.service_id, id));
        setSelectedTier(matchedTier?.tier || '');
    }

    function applyTierSelection(tier, catalog, { resetRefine = false } = {}) {
        const row = (catalog || tierCatalog).find((entry) => entry.tier === tier);
        const service = row?.service;

        if (!service) {
            return null;
        }

        setSelectedTier(tier);
        setServiceId(service.id);

        if (resetRefine) {
            setRefine(refineFromService(service));
        }

        const nextQty = Math.min(Math.max(service.min, 1000), service.max);
        setQuantity(nextQty);
        setCustomQuantity(String(nextQty));
        setCustomMode(false);

        return service.id;
    }

    async function loadTierCatalog(categoryRecord) {
        if (!categoryRecord) {
            setTierCatalog([]);
            setSelectedTier('');
            return null;
        }

        setLoadingTiers(true);
        try {
            const data = await catalogApi.tiers(categoryRecord.id);
            const tiers = Array.isArray(data?.tiers) ? data.tiers : [];
            setTierCatalog(tiers);

            const defaultTier = data.default_tier || tiers[0]?.tier || '';
            if (defaultTier) {
                applyTierSelection(defaultTier, tiers, { resetRefine: !showServiceCatalog });
            } else {
                setSelectedTier('');
            }

            return data.default_service_id ?? tiers[0]?.service_id ?? null;
        } catch (error) {
            setTierCatalog([]);
            setSelectedTier('');
            if (error instanceof ApiError) {
                setFormError(error.message);
            }
            return null;
        } finally {
            setLoadingTiers(false);
        }
    }

    function selectTier(tier) {
        const row = tierCatalog.find((entry) => entry.tier === tier);
        if (!row?.service) return;

        setOpenMenu(null);
        applyTierSelection(tier, tierCatalog, { resetRefine: true });
        loadServices({
            category: categoryId,
            search: serviceSearch,
            nextRefine: refineFromService(row.service),
            preferFirst: true,
            preferCustomComments: selectedCategory?.slug === 'comments',
            preferredServiceId: row.service_id,
        });
    }

    async function loadServices({
        category,
        search,
        nextRefine,
        preferFirst = false,
        preferCustomComments = false,
        preferredServiceId = null,
    }) {
        if (!category) {
            setGroups([]);
            setCategoryServices([]);
            setServiceId(null);
            return { groups: [], items: [] };
        }

        const seq = ++requestSeq.current;
        setLoadingServices(true);
        setFormError('');
        try {
            const data = await catalogApi.services(category, {
                ...filtersToParams(refineToFilters(nextRefine)),
                search,
            });
            if (seq !== requestSeq.current) return { groups: [], items: [] };

            const nextGroups = data.groups || [];
            const items = nextGroups.flatMap((g) => g.items || []);
            const resolvedDefaultId = preferredServiceId ?? data.category?.default_service_id ?? null;
            setGroups(nextGroups);

            const useFeaturedDefault = isRefineEmpty(nextRefine) && !search;
            if (useFeaturedDefault) {
                setCategoryServices(items);
            }

            const pickOptions = {
                preferFirst,
                preferCustomComments,
                preferredServiceId: resolvedDefaultId,
                useFeaturedDefault,
            };

            setServiceId((current) => {
                if (!preferFirst && current && items.some((row) => sameServiceId(row.id, current))) return current;
                return pickPreferredService(items, pickOptions)?.id ?? null;
            });
            const preferred = pickPreferredService(items, pickOptions);
            if (preferred && preferFirst) {
                const nextQty = Math.min(Math.max(preferred.min, 1000), preferred.max);
                setQuantity(nextQty);
                setCustomQuantity(String(nextQty));
                setCustomMode(false);
            }

            if (preferFirst && useFeaturedDefault && resolvedDefaultId) {
                const storefrontDefault = items.find((service) => sameServiceId(service.id, resolvedDefaultId));
                setRefine(storefrontDefault ? refineFromService(storefrontDefault) : EMPTY_REFINE);
            }

            return { groups: nextGroups, items, defaultServiceId: resolvedDefaultId };
        } catch (error) {
            if (seq === requestSeq.current) {
                setGroups([]);
                setServiceId(null);
                setFormError(error instanceof ApiError ? error.message : t('errors.loadServices'));
            }
            return { groups: [], items: [] };
        } finally {
            if (seq === requestSeq.current) setLoadingServices(false);
        }
    }

    useEffect(() => {
        if (!categoryId || !categories.length) return undefined;
        let cancelled = false;

        (async () => {
            window.clearTimeout(searchTimer.current);
            storefrontApplyRef.current = true;

            const cat = categories.find((c) => sameServiceId(c.id, categoryId));
            let preferredServiceId = cat?.default_service_id ?? null;

            const tierDefaultId = await loadTierCatalog(cat);
            if (tierDefaultId) {
                preferredServiceId = tierDefaultId;
            }

            if (!urlAppliedRef.current && initialUrlRef.current.service) {
                preferredServiceId = initialUrlRef.current.service;
            }

            await loadServices({
                category: categoryId,
                search: '',
                nextRefine: EMPTY_REFINE,
                preferFirst: true,
                preferCustomComments: cat?.slug === 'comments',
                preferredServiceId,
            });

            if (
                !urlAppliedRef.current
                && (initialUrlRef.current.platform || initialUrlRef.current.category || initialUrlRef.current.service)
            ) {
                urlAppliedRef.current = true;
            }

            if (cancelled) return;
            storefrontApplyRef.current = false;
        })();

        return () => {
            cancelled = true;
            storefrontApplyRef.current = false;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [categoryId, categories]);

    useEffect(() => {
        if (!categoryId || storefrontApplyRef.current) return undefined;
        window.clearTimeout(searchTimer.current);
        searchTimer.current = window.setTimeout(() => {
            loadServices({
                category: categoryId,
                search: serviceSearch,
                nextRefine: refine,
            });
        }, 280);

        return () => window.clearTimeout(searchTimer.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [serviceSearch]);

    useEffect(() => {
        if (!selectedService) return;
        const next = Math.min(Math.max(quantity, selectedService.min), selectedService.max);
        if (next !== quantity) {
            setQuantity(next);
            setCustomQuantity(String(next));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedService]);

    useEffect(() => {
        if (!isCommentsCategory) {
            setUseCustomComments(false);
            setCustomCommentsText('');
            return;
        }
        setDeliverySettingsOpen(true);
        setSettingsRowOpen('comments');
    }, [isCommentsCategory, categoryId]);

    useEffect(() => {
        if (!isCommentsCategory) return;
        setUseCustomComments(requiresCustomComments);
        if (!requiresCustomComments) {
            setCustomCommentsText('');
            setErrors((prev) => ({ ...prev, comments: undefined }));
        }
    }, [isCommentsCategory, selectedService?.id, requiresCustomComments]);

    function toggleCustomComments(enabled) {
        setUseCustomComments(enabled);
        if (!enabled) {
            setCustomCommentsText('');
            setErrors((prev) => ({ ...prev, comments: undefined }));
        }
    }

    function syncQuantityToComments() {
        if (!selectedService || commentLines.length === 0) return;
        const next = Math.min(Math.max(commentLines.length, selectedService.min), selectedService.max);
        setQuantity(next);
        setCustomQuantity(String(next));
        setCustomMode(true);
        setErrors((e) => ({ ...e, comments: undefined, quantity: undefined }));
    }

    useEffect(() => {
        if (!openMenu) return undefined;

        function onPointerDown(event) {
            const target = event.target;
            if (!(target instanceof Element)) return;
            if (target.closest('.order-dropdown') || target.closest('[data-order-trigger]')) return;
            setOpenMenu(null);
        }

        // Full-screen overlay blocks scrolling the dashboard; detect outside clicks on document instead.
        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('touchstart', onPointerDown, { passive: true });
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('touchstart', onPointerDown);
        };
    }, [openMenu]);

    useEffect(() => {
        if (!categoryServices.length || storefrontApplyRef.current) return;
        const { next, changed } = clampRefineToOptions(refine, deliveryCaps);
        if (!changed) return;
        setRefine(next);
        if (categoryId) {
            loadServices({
                category: categoryId,
                search: serviceSearch,
                nextRefine: next,
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [deliveryCaps.showQuality, deliveryCaps.showDelivery, deliveryCaps.showProtection, deliveryCaps.showCountry, deliveryCaps.showAudience, deliveryCaps.showReaction, deliveryCaps.showRefillDays, categoryServices.length]);

    useEffect(() => {
        if (settingsRowOpen === 'quality' && !showQualitySettings) setSettingsRowOpen(null);
        if (settingsRowOpen === 'delivery' && !showDeliverySettings) setSettingsRowOpen(null);
        if (settingsRowOpen === 'protection' && !showProtectionSettings) setSettingsRowOpen(null);
        if (settingsRowOpen === 'country' && !showCountrySettings) setSettingsRowOpen(null);
        if (settingsRowOpen === 'audience' && !showAudienceSettings) setSettingsRowOpen(null);
        if (settingsRowOpen === 'reaction' && !showReactionSettings) setSettingsRowOpen(null);
    }, [settingsRowOpen, showQualitySettings, showDeliverySettings, showProtectionSettings, showCountrySettings, showAudienceSettings, showReactionSettings]);

    function selectPlatform(slug) {
        urlAppliedRef.current = true;
        setPlatformSlug(slug);
        setOpenMenu(null);
    }

    function selectCategory(id) {
        urlAppliedRef.current = true;
        window.clearTimeout(searchTimer.current);
        setCategoryId(id);
        setServiceSearch('');
        setTierCatalog([]);
        setSelectedTier('');
        setRefine(EMPTY_REFINE);
        setOpenMenu(null);
    }

    function setRefineField(field, value) {
        let next = { ...refine, [field]: value };
        if (field === 'protection' && value === 'none') {
            next = { ...next, refillDays: [] };
        }
        setRefine(next);
        setOpenMenu(null);
        loadServices({
            category: categoryId,
            search: serviceSearch,
            nextRefine: next,
            preferFirst: true,
            preferCustomComments: selectedCategory?.slug === 'comments',
        });
    }

    function toggleRefillDay(days) {
        const current = Array.isArray(refine.refillDays) ? refine.refillDays : [];
        const nextDays = current.includes(days) ? [] : [days];
        const next = {
            ...refine,
            refillDays: nextDays,
            protection: nextDays.length && refine.protection === 'none' ? 'any' : refine.protection,
        };
        setRefine(next);
        loadServices({
            category: categoryId,
            search: serviceSearch,
            nextRefine: next,
            preferFirst: true,
            preferCustomComments: selectedCategory?.slug === 'comments',
        });
    }

    function clearRefine() {
        setRefine(EMPTY_REFINE);
        const tierServiceId = selectedTier
            ? tierCatalog.find((entry) => entry.tier === selectedTier)?.service_id
            : null;
        loadServices({
            category: categoryId,
            search: serviceSearch,
            nextRefine: EMPTY_REFINE,
            preferFirst: true,
            preferCustomComments: selectedCategory?.slug === 'comments',
            preferredServiceId: tierServiceId ?? selectedCategory?.default_service_id ?? null,
        });
    }

    function selectService(id) {
        const next = flatServices.find((s) => s.id === id);
        setServiceId(id);
        setOpenMenu(null);
        if (next) {
            const nextQty = Math.min(Math.max(next.min, quantity || next.min), next.max);
            setQuantity(nextQty);
            setCustomQuantity(String(nextQty));
            syncTierFromService(id);
        }
    }

    async function pasteLink() {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                setLink(text.trim());
                setErrors((e) => ({ ...e, link: undefined }));
            }
        } catch {
            // ignore clipboard errors
        }
    }

    function validate() {
        const next = {};
        if (!platformSlug) next.platform = t('errors.selectPlatform');
        if (!selectedCategory) next.category = t('errors.selectCategory');
        if (!showServiceCatalog && tierCatalog.length > 0 && !selectedTier) {
            next.tier = t('errors.selectServiceTier');
        }
        if (!selectedService) next.service = t('errors.selectService');
        if (!link.trim()) next.link = t('errors.targetRequired');
        if (selectedService && (quantity < selectedService.min || quantity > selectedService.max)) {
            next.quantity = t('errors.quantityBetween', {
                min: formatAmount(selectedService.min),
                max: formatAmount(selectedService.max),
                defaultValue: `Quantity must be between ${formatAmount(selectedService.min)} and ${formatAmount(selectedService.max)}`,
            });
        }
        if (requiresCustomComments && !useCustomComments) {
            next.comments = t('errors.customCommentsRequired');
        } else if (useCustomComments && requiresCustomComments && !commentValidation.ok) {
            next.comments = commentValidation.message;
        }

        if (next.comments) {
            setDeliverySettingsOpen(true);
            setSettingsRowOpen('comments');
        }

        setErrors(next);
        if (Object.keys(next).length > 0) {
            scrollToFirstFormError(next, { formErrorSelector: '[data-form-error-banner]' });
            return false;
        }
        return true;
    }

    async function onCheckout(event) {
        event.preventDefault();
        setFormError('');
        if (!validate()) return;

        const draft = {
            serviceId: selectedService.id,
            serviceName: showServiceCatalog ? selectedService.name : '',
            serviceType: selectedService.type || null,
            requiresCustomComments,
            useCustomComments,
            comments: useCustomComments && commentLines.length ? formatCommentsForApi(commentLines) : null,
            commentCount: useCustomComments ? commentLines.length : null,
            platformSlug,
            platformName: selectedPlatform?.name || platformSlug,
            categoryId,
            categorySlug: selectedCategory?.slug || '',
            categoryName: selectedCategory?.name || '',
            quantity,
            link: link.trim(),
            targetLabel: target.label,
            charge: roundDzd(charge),
            isRepeat,
            countryCode: selectedService.country_code || null,
            refillDays: selectedService.refill_days || null,
            startClass: selectedService.start_class || null,
            qualityTier: selectedService.quality_tier || null,
            refillMode: selectedService.refill_mode || null,
            hasRefill: Boolean(selectedService.refill || selectedService.refill_mode),
            isHot: Boolean(selectedService.is_hot),
            isCheap: Boolean(selectedService.is_cheap),
            dripfeed: Boolean(selectedService.dripfeed),
            rate: selectedService.rate || null,
            min: selectedService.min || null,
            max: selectedService.max || null,
        };

        saveCheckoutDraft(draft);

        try {
            const targetCheck = await ordersApi.checkTarget(draft.link);
            if (!targetCheck?.available) {
                const message = targetCheck?.message || t('duplicateTargetPending', { ns: 'validation' });
                setErrors((current) => ({
                    ...current,
                    link: message,
                }));
                setFormError(message);
                scrollToFirstFormError({ link: message }, { formErrorSelector: '[data-form-error-banner]' });
                return;
            }
        } catch (error) {
            if (error instanceof ApiError && error.message) {
                setErrors((current) => ({ ...current, link: error.message }));
                setFormError(error.message);
                scrollToFirstFormError({ link: error.message }, { formErrorSelector: '[data-form-error-banner]' });
                return;
            }
        }

        navigate('/checkout', { state: { draft } });
    }

    const PlatformIcon = getPlatformIcon(platformSlug);
    const CategoryIcon = getCategoryIcon(selectedCategory?.slug);
    const statusOnLabel = t('deliverySettingsActive');
    const statusOffLabel = t('deliverySettingsInactive');

    return (
        <div className="mx-auto w-full max-w-3xl space-y-2 py-2">
            <div className="flex items-end justify-between gap-3 px-0.5 pb-1">
                <div>
                    <h1 className="text-lg font-semibold tracking-tight text-foreground">{t('title')}</h1>
                    <p className="mt-0.5 text-xs text-muted-foreground">{t('balance', { amount: formatDzd(balance) })}</p>
                </div>
            </div>

            <form className="w-full space-y-2" onSubmit={onCheckout}>
                {formError ? (
                    <div data-form-error-banner role="alert" className="rounded-md border border-red-500/20 bg-red-500/8 px-4 py-3 text-sm font-medium text-red-700 dark:text-red-300">
                        {formError}
                    </div>
                ) : null}

                <div className="relative flex flex-col overflow-visible rounded-md border bg-card text-card-foreground shadow-sm">
                    <div className="p-4">
                        <fieldset className="grid w-full gap-y-4">
                        <div className="space-y-1.5">
                            <FieldLabel required>{t('categoryProduct')}</FieldLabel>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div className="relative min-w-0" data-form-field="platform">
                                    <SelectButton
                                        open={openMenu === 'platform'}
                                        disabled={loadingPlatforms || platforms.length === 0}
                                        invalid={Boolean(errors.platform)}
                                        hasValue={Boolean(selectedPlatform || platformSlug)}
                                        onClick={() => setOpenMenu(openMenu === 'platform' ? null : 'platform')}
                                    >
                                        <PlatformIcon className="size-4 shrink-0" />
                                        <span className="block min-w-0 flex-1 truncate whitespace-nowrap">
                                            {selectedPlatform?.name || platformSlug || t('selectPlatform')}
                                        </span>
                                    </SelectButton>
                                    <Dropdown open={openMenu === 'platform'}>
                                        {platforms.map((platform) => {
                                            const Icon = getPlatformIcon(platform.slug);
                                            return (
                                                <Option key={platform.slug} active={platform.slug === platformSlug} onClick={() => selectPlatform(platform.slug)}>
                                                    <span className="flex min-w-0 items-center gap-2">
                                                        <Icon className="size-4 shrink-0" />
                                                        <span className="min-w-0 flex-1 truncate">{platform.name}</span>
                                                    </span>
                                                </Option>
                                            );
                                        })}
                                    </Dropdown>
                                </div>

                                <div className="relative min-w-0 sm:col-span-2" data-form-field="category">
                                    <SelectButton
                                        open={openMenu === 'category'}
                                        disabled={loadingCategories || categories.length === 0}
                                        invalid={Boolean(errors.category)}
                                        hasValue={Boolean(selectedCategory)}
                                        onClick={() => setOpenMenu(openMenu === 'category' ? null : 'category')}
                                    >
                                        <CategoryIcon className="size-4 shrink-0 opacity-80" />
                                        <span className="block min-w-0 flex-1 truncate whitespace-nowrap">
                                            {selectedCategory?.name || (loadingCategories ? t('loading', { ns: 'common' }) : t('selectCategory'))}
                                        </span>
                                    </SelectButton>
                                    <Dropdown open={openMenu === 'category'}>
                                        {categories.map((category) => {
                                            const Icon = getCategoryIcon(category.slug);
                                            return (
                                                <Option key={category.id} active={category.id === categoryId} onClick={() => selectCategory(category.id)}>
                                                    <span className="flex min-w-0 items-center gap-2">
                                                        <Icon className="size-4 shrink-0 text-muted-foreground" />
                                                        <span className="min-w-0 flex-1 truncate">{category.name}</span>
                                                    </span>
                                                </Option>
                                            );
                                        })}
                                    </Dropdown>
                                </div>
                            </div>
                        </div>

                        {showServiceCatalog ? (
                        <div className="grid min-w-0 gap-2" data-form-field="service">
                            <FieldLabel required>{t('servicePackage')}</FieldLabel>
                            <div className="relative min-w-0">
                                <SelectButton
                                    open={openMenu === 'service'}
                                    disabled={loadingServices || (!loadingServices && flatServices.length === 0)}
                                    invalid={Boolean(errors.service)}
                                    hasValue={Boolean(selectedService)}
                                    onClick={() => setOpenMenu(openMenu === 'service' ? null : 'service')}
                                >
                                    <Package className="size-4 shrink-0 opacity-80" />
                                    <span className="block min-w-0 flex-1 truncate whitespace-nowrap">
                                        {loadingServices ? (
                                            <SkeletonBar className="w-40" />
                                        ) : (
                                            selectedService?.name || t('selectService')
                                        )}
                                    </span>
                                </SelectButton>
                                <Dropdown open={openMenu === 'service'} className="p-0">
                                    <div className="sticky top-0 z-10 border-b border-border/60 bg-card p-2">
                                        <div className="relative">
                                            <Search className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground" />
                                            <input
                                                value={serviceSearch}
                                                onChange={(e) => setServiceSearch(e.target.value)}
                                                placeholder={t('searchServices')}
                                                className="order-input h-9 pl-8"
                                            />
                                        </div>
                                    </div>
                                    <div className="max-h-72 overflow-auto p-1">
                                        {loadingServices ? (
                                            <DropdownSkeletonRows count={5} />
                                        ) : groups.length === 0 ? (
                                            <p className="px-3 py-3 text-sm text-muted-foreground">
                                                {t('noServicesMatch')}
                                            </p>
                                        ) : (
                                            groups.map((group) => (
                                                <div key={group.key} className="mb-1">
                                                    <p className="px-2.5 py-1.5 text-[11px] font-medium tracking-wide text-muted-foreground">
                                                        {group.label}
                                                    </p>
                                                    {(group.items || []).map((service) => (
                                                        <Option
                                                            key={service.id}
                                                            active={service.id === serviceId}
                                                            onClick={() => selectService(service.id)}
                                                        >
                                                            <span className="block">
                                                                <span className="line-clamp-2 leading-snug">{service.name}</span>
                                                                <span className="mt-1.5 flex flex-wrap gap-1">
                                                                    {serviceBadges(service, t).map((badge) => (
                                                                        <Badge key={badge.key} label={badge.label} tone={badge.tone} />
                                                                    ))}
                                                                </span>
                                                                <span className="mt-1 block text-xs text-muted-foreground">
                                                                    {t('ratePerMin', {
                                                                        price: formatDzd(service.sell_rate_dzd),
                                                                        min: formatAmount(service.min),
                                                                        defaultValue: `${formatDzd(service.sell_rate_dzd)} / 1k · min ${formatAmount(service.min)}`,
                                                                    })}
                                                                </span>
                                                            </span>
                                                        </Option>
                                                    ))}
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </Dropdown>
                            </div>
                            {selectedService ? (
                                <div className="flex flex-wrap gap-1">
                                    {serviceBadges(selectedService, t).map((badge) => (
                                        <Badge key={badge.key} label={badge.label} tone={badge.tone} />
                                    ))}
                                </div>
                            ) : null}
                            {errors.service ? (
                                <p className="text-xs font-medium text-red-600 dark:text-red-400">{errors.service}</p>
                            ) : null}
                        </div>
                        ) : null}

                        {selectedCategory && (loadingTiers || tierCatalog.length > 0) ? (
                        <div className="grid min-w-0 gap-2" data-form-field="tier">
                            <FieldLabel required={!showServiceCatalog}>{t('serviceTier')}</FieldLabel>
                            <div className="relative min-w-0">
                                <SelectButton
                                    open={openMenu === 'tier'}
                                    disabled={loadingTiers || (!loadingTiers && tierCatalog.length === 0)}
                                    invalid={Boolean(errors.tier)}
                                    hasValue={Boolean(selectedTier)}
                                    onClick={() => setOpenMenu(openMenu === 'tier' ? null : 'tier')}
                                >
                                    <Sparkles className="size-4 shrink-0 opacity-80" />
                                    <span className="block min-w-0 flex-1 truncate whitespace-nowrap">
                                        {loadingTiers ? (
                                            <SkeletonBar className="w-40" />
                                        ) : selectedTierText ? (
                                            <span className="flex w-full min-w-0 items-center justify-between gap-2">
                                                <span className="truncate">{selectedTierText.primary}</span>
                                                {selectedTierText.price ? (
                                                    <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                                        {selectedTierText.price}
                                                    </span>
                                                ) : null}
                                            </span>
                                        ) : tierCatalog.length === 0 ? (
                                            t('noTiersConfigured')
                                        ) : (
                                            t('selectServiceTier')
                                        )}
                                    </span>
                                </SelectButton>
                                <Dropdown open={openMenu === 'tier'}>
                                    {loadingTiers ? (
                                        <DropdownSkeletonRows count={3} />
                                    ) : (
                                        tierCatalog.map((entry) => {
                                            const option = formatTierOption(entry, t, showServiceCatalog);

                                            return (
                                                <Option
                                                    key={entry.tier}
                                                    active={entry.tier === selectedTier}
                                                    onClick={() => selectTier(entry.tier)}
                                                >
                                                    <span className="flex w-full items-center justify-between gap-2">
                                                        <span>{option.primary}</span>
                                                        {option.price ? (
                                                            <span className="text-xs text-muted-foreground tabular-nums">
                                                                {option.price}
                                                            </span>
                                                        ) : null}
                                                    </span>
                                                </Option>
                                            );
                                        })
                                    )}
                                </Dropdown>
                            </div>
                            {showServiceCatalog && selectedTierEntry?.service_id ? (
                                <p className="text-xs text-muted-foreground">
                                    {t('tierFulfillmentService', {
                                        id: selectedTierEntry.service_id,
                                        defaultValue: `Fulfillment maps to service #${selectedTierEntry.service_id}`,
                                    })}
                                </p>
                            ) : null}
                            {errors.tier ? (
                                <p className="text-xs font-medium text-red-600 dark:text-red-400">{errors.tier}</p>
                            ) : null}
                        </div>
                        ) : null}

                        <div className="grid min-w-0 gap-2" data-form-field="quantity">
                            <FieldLabel required>{t('amount')}</FieldLabel>
                            {customMode || !selectedService ? (
                                <input
                                    type="number"
                                    min={selectedService?.min || 1}
                                    max={selectedService?.max || undefined}
                                    value={customQuantity}
                                    disabled={!selectedService}
                                    onChange={(e) => {
                                        setCustomQuantity(e.target.value);
                                        const n = Number(e.target.value);
                                        if (!Number.isNaN(n)) setQuantity(n);
                                    }}
                                    className="order-input"
                                />
                            ) : (
                                <div className="relative min-w-0">
                                    <SelectButton
                                        open={openMenu === 'quantity'}
                                        invalid={Boolean(errors.quantity)}
                                        hasValue={Boolean(quantity)}
                                        onClick={() => setOpenMenu(openMenu === 'quantity' ? null : 'quantity')}
                                    >
                                        <span className="flex w-full min-w-0 items-center justify-between overflow-hidden text-left">
                                            <span className="truncate font-medium tabular-nums">{formatAmount(quantity)}</span>
                                            <span className="shrink-0 rounded-md bg-primary/10 px-1.5 py-0.5 text-xs font-bold tabular-nums text-primary">
                                                {formatDzd(charge)}
                                            </span>
                                        </span>
                                    </SelectButton>
                                    <Dropdown open={openMenu === 'quantity'} className="!p-0.5">
                                        {presets.map((amount) => (
                                            <Option
                                                key={amount}
                                                dense
                                                active={amount === quantity}
                                                onClick={() => {
                                                    setQuantity(amount);
                                                    setCustomQuantity(String(amount));
                                                    setOpenMenu(null);
                                                }}
                                            >
                                                <span className="flex w-full items-center justify-between gap-2 leading-tight">
                                                    <span className="font-medium tabular-nums">{formatAmount(amount)}</span>
                                                    <span className="rounded-md bg-muted px-1.5 py-0.5 text-xs font-bold tabular-nums text-foreground">
                                                        {formatDzd(chargeFor(selectedService, amount))}
                                                    </span>
                                                </span>
                                            </Option>
                                        ))}
                                    </Dropdown>
                                </div>
                            )}
                            {selectedService ? (
                                <p className="text-xs text-muted-foreground">
                                    {t('quantityLimit', {
                                        min: formatAmount(selectedService.min),
                                        max: formatAmount(selectedService.max),
                                        defaultValue: `Limit of ${formatAmount(selectedService.min)} to ${formatAmount(selectedService.max)} per link. Want to input manually?`,
                                    })}{' '}
                                    <button
                                        type="button"
                                        className="cursor-pointer font-bold text-primary underline"
                                        onClick={() => {
                                            setCustomMode((v) => !v);
                                            setOpenMenu(null);
                                        }}
                                    >
                                        {t('clickHere', { defaultValue: 'Click here' })}
                                    </button>
                                </p>
                            ) : null}
                            {errors.quantity ? <p className="text-xs font-medium text-red-600 dark:text-red-400">{errors.quantity}</p> : null}
                        </div>

                        <div className="grid min-w-0 gap-2" data-form-field="link">
                            <FieldLabel required>{target.label}</FieldLabel>
                            <div className="group relative">
                                <input
                                    type="text"
                                    value={link}
                                    onChange={(e) => {
                                        setLink(e.target.value);
                                        setErrors((er) => ({ ...er, link: undefined }));
                                    }}
                                    placeholder={target.placeholder}
                                    className={cn('order-input pr-9', errors.link && 'border-destructive/80')}
                                />
                                <button
                                    type="button"
                                    onClick={pasteLink}
                                    aria-label={t('pasteLink')}
                                    className="absolute inset-y-0 right-3 flex items-center text-muted-foreground/70 hover:text-foreground"
                                >
                                    <ClipboardPaste className="size-4" />
                                </button>
                            </div>
                            {errors.link ? (
                                <p className="text-xs font-medium text-red-600 dark:text-red-400">
                                    {Array.isArray(errors.link) ? errors.link[0] : errors.link}
                                </p>
                            ) : target.hint ? (
                                <p className="text-xs text-muted-foreground" dir="auto">
                                    {target.hint}
                                </p>
                            ) : null}
                        </div>
                        </fieldset>
                    </div>
                </div>

                        <div className="relative flex flex-col overflow-visible rounded-md border bg-card text-card-foreground shadow-sm">
                            <button
                                type="button"
                                onClick={() => setDeliverySettingsOpen((v) => !v)}
                                className="flex w-full cursor-pointer items-center gap-2 px-4 py-3 text-left text-sm"
                            >
                                <Settings2 className="size-4 text-muted-foreground" />
                                <span className="font-medium text-foreground">{t('deliverySettings')}</span>
                                <StatusPill on={deliverySettingsActive} onLabel={statusOnLabel} offLabel={statusOffLabel} />
                                <ChevronDown className={cn('ml-auto size-4 text-muted-foreground transition-transform duration-200', deliverySettingsOpen && 'rotate-180')} />
                            </button>

                            <div className="order-collapsible" data-open={deliverySettingsOpen ? 'true' : undefined}>
                                <div className="order-collapsible-inner">
                                <div className="space-y-2 border-t px-4 py-3">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <p className="min-w-0 flex-1 text-[0.8125rem] font-medium text-muted-foreground">
                                            {hasRefineRows
                                                ? t('refineHint')
                                                : t('repeatHint')}
                                        </p>
                                        {hasActiveRefine ? (
                                            <button
                                                type="button"
                                                onClick={clearRefine}
                                                className="shrink-0 text-[0.8125rem] font-semibold text-primary underline underline-offset-2"
                                            >
                                                {t('reset')}
                                            </button>
                                        ) : null}
                                    </div>

                                    <SettingsRow
                                        icon={Repeat2}
                                        title={t('repeatOrder')}
                                        active={isRepeat}
                                        open={settingsRowOpen === 'repeat'}
                                        onToggle={() => setSettingsRowOpen(settingsRowOpen === 'repeat' ? null : 'repeat')}
                                        statusOnLabel={statusOnLabel}
                                        statusOffLabel={statusOffLabel}
                                    >
                                        <div className="flex items-center justify-between gap-3 rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2.5">
                                            <span className="text-sm text-muted-foreground">
                                                {t('repeatOrderDesc')}
                                            </span>
                                            <button
                                                type="button"
                                                role="switch"
                                                aria-checked={isRepeat}
                                                onClick={() => setIsRepeat((v) => !v)}
                                                className={cn('relative h-6 w-11 shrink-0 rounded-full transition', isRepeat ? 'bg-primary' : 'bg-muted')}
                                            >
                                                <span
                                                    className={cn(
                                                        'absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition',
                                                        isRepeat && 'translate-x-5',
                                                    )}
                                                />
                                            </button>
                                        </div>
                                    </SettingsRow>

                                    {showCustomCommentsSettings ? (
                                        <div data-form-field="comments">
                                        <SettingsRow
                                            icon={MessageCircle}
                                            title={t('customComments')}
                                            active={useCustomComments}
                                            open={settingsRowOpen === 'comments'}
                                            onToggle={() => setSettingsRowOpen(settingsRowOpen === 'comments' ? null : 'comments')}
                                            statusOnLabel={statusOnLabel}
                                            statusOffLabel={statusOffLabel}
                                        >
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between gap-3 rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2.5">
                                                    <span className="text-sm text-muted-foreground">
                                                        {t('customCommentsDesc')}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        role="switch"
                                                        aria-checked={useCustomComments}
                                                        onClick={() => toggleCustomComments(!useCustomComments)}
                                                        className={cn('relative h-6 w-11 shrink-0 rounded-full transition', useCustomComments ? 'bg-primary' : 'bg-muted')}
                                                    >
                                                        <span
                                                            className={cn(
                                                                'absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition',
                                                                useCustomComments && 'translate-x-5',
                                                            )}
                                                        />
                                                    </button>
                                                </div>

                                                {useCustomComments ? (
                                                    <>
                                                <p className="text-[0.8125rem] font-medium text-muted-foreground">
                                                    {requiresCustomComments ? (
                                                        <>
                                                            {t('customCommentsIntro', {
                                                                defaultValue: 'Enter one comment per line. Each line is posted as a separate comment.',
                                                            })}
                                                            {isCustomCommentsPackage(selectedService)
                                                                ? ` ${t('customCommentsPackageNote', { defaultValue: 'This package uses your full comment list.' })}`
                                                                : ` ${t('customCommentsQtyNote', { defaultValue: 'Quantity must match the number of comments.' })}`}
                                                        </>
                                                    ) : (
                                                        t('customCommentsOptional')
                                                    )}
                                                </p>
                                                <textarea
                                                    value={customCommentsText}
                                                    onChange={(e) => {
                                                        setCustomCommentsText(e.target.value);
                                                        setErrors((prev) => ({ ...prev, comments: undefined }));
                                                    }}
                                                    rows={6}
                                                    placeholder={t('customCommentsPlaceholder')}
                                                    className="dash-comments-textarea"
                                                    aria-invalid={Boolean(errors.comments)}
                                                />
                                                <div className="flex flex-wrap items-center justify-between gap-2 text-[0.8125rem]">
                                                    <span className={cn(
                                                        'font-medium',
                                                        !requiresCustomComments
                                                            ? 'text-muted-foreground'
                                                            : commentsQtyMatch
                                                              ? 'text-emerald-600 dark:text-emerald-400'
                                                              : 'text-amber-600 dark:text-amber-400',
                                                    )}
                                                    >
                                                        {t('commentsEntered', {
                                                            count: commentLines.length,
                                                            defaultValue: `${commentLines.length} comment${commentLines.length === 1 ? '' : 's'} entered`,
                                                        })}
                                                        {requiresCustomComments && !isCustomCommentsPackage(selectedService) ? (
                                                            <> · {t('quantityShort', {
                                                                quantity,
                                                                defaultValue: `quantity ${quantity}`,
                                                            })}</>
                                                        ) : null}
                                                    </span>
                                                    {requiresCustomComments && !isCustomCommentsPackage(selectedService) && !commentsQtyMatch && commentLines.length > 0 ? (
                                                        <button
                                                            type="button"
                                                            onClick={syncQuantityToComments}
                                                            className="text-xs font-semibold text-primary underline underline-offset-2"
                                                        >
                                                            {t('syncQuantityTo', {
                                                                count: commentLines.length,
                                                                defaultValue: `Sync quantity to ${commentLines.length}`,
                                                            })}
                                                        </button>
                                                    ) : null}
                                                </div>
                                                {!requiresCustomComments || isCustomCommentsPackage(selectedService) ? null : !commentsQtyMatch && commentLines.length > 0 ? (
                                                    <p className="text-xs text-amber-600 dark:text-amber-400">
                                                        {t('commentsQtyHint', {
                                                            defaultValue: 'Add or remove lines, or change quantity so they match.',
                                                        })}
                                                    </p>
                                                ) : null}
                                                {errors.comments ? (
                                                    <p role="alert" className="text-xs text-red-600 dark:text-red-400">
                                                        {errors.comments}
                                                    </p>
                                                ) : null}
                                                    </>
                                                ) : (
                                                    <p className="text-[0.8125rem] font-medium text-muted-foreground">
                                                        {t('customCommentsOff', {
                                                            defaultValue: 'Custom comments are off. Turn on to type your own lines.',
                                                        })}
                                                    </p>
                                                )}
                                            </div>
                                        </SettingsRow>
                                        </div>
                                    ) : null}

                                    {showQualitySettings ? (
                                        <SettingsRow
                                            icon={Sparkles}
                                            title={t('quality')}
                                            active={refine.quality !== 'any'}
                                            open={settingsRowOpen === 'quality'}
                                            onToggle={() => setSettingsRowOpen(settingsRowOpen === 'quality' ? null : 'quality')}
                                            statusOnLabel={statusOnLabel}
                                            statusOffLabel={statusOffLabel}
                                        >
                                            <div className="flex flex-wrap items-center gap-1 rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                                <span>{t('quality')}</span>
                                                <InlineSelect
                                                    value={refine.quality}
                                                    options={qualityOptions}
                                                    open={openMenu === 'quality'}
                                                    disabled={!categoryId}
                                                    onOpen={() => setOpenMenu(openMenu === 'quality' ? null : 'quality')}
                                                    onChange={(value) => setRefineField('quality', value)}
                                                    emptyLabel={t('selectEllipsis')}
                                                />
                                            </div>
                                        </SettingsRow>
                                    ) : null}

                                    {showDeliverySettings ? (
                                        <SettingsRow
                                            icon={Zap}
                                            title={t('startTime')}
                                            active={refine.delivery !== 'any'}
                                            open={settingsRowOpen === 'delivery'}
                                            onToggle={() => setSettingsRowOpen(settingsRowOpen === 'delivery' ? null : 'delivery')}
                                            statusOnLabel={statusOnLabel}
                                            statusOffLabel={statusOffLabel}
                                        >
                                            <div className="flex flex-wrap items-center gap-1 rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                                <span>{t('prefer', { defaultValue: 'Prefer' })}</span>
                                                <InlineSelect
                                                    value={refine.delivery}
                                                    options={deliveryOptions}
                                                    open={openMenu === 'delivery'}
                                                    disabled={!categoryId}
                                                    onOpen={() => setOpenMenu(openMenu === 'delivery' ? null : 'delivery')}
                                                    onChange={(value) => setRefineField('delivery', value)}
                                                    emptyLabel={t('selectEllipsis')}
                                                />
                                                <span>{t('startWord', { defaultValue: 'start' })}</span>
                                            </div>
                                        </SettingsRow>
                                    ) : null}

                                    {showProtectionSettings ? (
                                        <SettingsRow
                                            icon={ShieldCheck}
                                            title={t('protection')}
                                            active={protectionRowActive}
                                            open={settingsRowOpen === 'protection'}
                                            onToggle={() => setSettingsRowOpen(settingsRowOpen === 'protection' ? null : 'protection')}
                                            statusOnLabel={statusOnLabel}
                                            statusOffLabel={statusOffLabel}
                                        >
                                            <div className="space-y-2">
                                                <div className="flex flex-wrap items-center gap-1 rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                                    <span>{t('refillWord', { defaultValue: 'Refill' })}</span>
                                                    <InlineSelect
                                                        value={refine.protection}
                                                        options={protectionOptions}
                                                        open={openMenu === 'protection'}
                                                        disabled={!categoryId}
                                                        onOpen={() => setOpenMenu(openMenu === 'protection' ? null : 'protection')}
                                                        onChange={(value) => setRefineField('protection', value)}
                                                        emptyLabel={t('selectEllipsis')}
                                                    />
                                                </div>

                                                {showRefillDays ? (
                                                    <div className="rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2">
                                                        <p className="mb-2 text-[0.8125rem] font-medium text-muted-foreground">
                                                            {t('warrantyPeriod', { defaultValue: 'Warranty period' })}
                                                        </p>
                                                        <div className="flex flex-wrap gap-2">
                                                            {refillDayOptions.map((opt) => {
                                                                const checked = (refine.refillDays || [])[0] === opt.value;
                                                                return (
                                                                    <label
                                                                        key={opt.value}
                                                                        className={cn(
                                                                            'inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-semibold transition',
                                                                            checked
                                                                                ? 'border-primary/40 bg-primary/10 text-primary'
                                                                                : 'border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] text-foreground hover:border-primary/30',
                                                                        )}
                                                                    >
                                                                        <input
                                                                            type="radio"
                                                                            name="refill-days"
                                                                            className="size-3.5 accent-primary"
                                                                            checked={checked}
                                                                            onChange={() => toggleRefillDay(opt.value)}
                                                                            onClick={() => {
                                                                                if (checked) toggleRefillDay(opt.value);
                                                                            }}
                                                                        />
                                                                        {opt.label}
                                                                    </label>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>
                                                ) : null}

                                                {selectedService?.refill ? (
                                                    <p className="text-[0.8125rem] font-medium text-muted-foreground">
                                                        {t('packageSupportsRefill', { defaultValue: 'Current package supports refill' })}
                                                        {selectedService.refill_days
                                                            ? ` ${t('packageRefillDays', {
                                                                days: selectedService.refill_days,
                                                                defaultValue: `(~${selectedService.refill_days} days)`,
                                                            })}`
                                                            : ''}.
                                                    </p>
                                                ) : selectedService ? (
                                                    <p className="text-[0.8125rem] font-medium text-muted-foreground">
                                                        {t('packageNoRefill', {
                                                            defaultValue: 'Current package has no refill — pick a refill option above to switch packages.',
                                                        })}
                                                    </p>
                                                ) : null}
                                            </div>
                                        </SettingsRow>
                                    ) : null}

                                    {showCountrySettings ? (
                                        <SettingsRow
                                            icon={Globe2}
                                            title={t('country')}
                                            active={refine.country !== 'any'}
                                            open={settingsRowOpen === 'country'}
                                            onToggle={() => setSettingsRowOpen(settingsRowOpen === 'country' ? null : 'country')}
                                            statusOnLabel={statusOnLabel}
                                            statusOffLabel={statusOffLabel}
                                        >
                                            <div className="flex flex-wrap items-center gap-1 rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                                <span>{t('location', { defaultValue: 'Location' })}</span>
                                                <InlineSelect
                                                    value={refine.country}
                                                    options={countryOptions}
                                                    open={openMenu === 'country'}
                                                    disabled={!categoryId}
                                                    onOpen={() => setOpenMenu(openMenu === 'country' ? null : 'country')}
                                                    onChange={(value) => setRefineField('country', value)}
                                                    emptyLabel={t('selectEllipsis')}
                                                />
                                            </div>
                                            {selectedService?.country_code ? (
                                                <p className="mt-2 text-[0.8125rem] font-medium text-muted-foreground">
                                                    {t('currentPackage', { defaultValue: 'Current package:' })}{' '}
                                                    <CountryFlag code={selectedService.country_code} className="align-middle" />
                                                </p>
                                            ) : null}
                                        </SettingsRow>
                                    ) : null}

                                    {showAudienceSettings ? (
                                        <SettingsRow
                                            icon={Users}
                                            title={t('audience')}
                                            active={refine.audience !== 'any'}
                                            open={settingsRowOpen === 'audience'}
                                            onToggle={() => setSettingsRowOpen(settingsRowOpen === 'audience' ? null : 'audience')}
                                            statusOnLabel={statusOnLabel}
                                            statusOffLabel={statusOffLabel}
                                        >
                                            <div className="flex flex-wrap items-center gap-1 rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                                <span>{t('targetAudience', { defaultValue: 'Target' })}</span>
                                                <InlineSelect
                                                    value={refine.audience}
                                                    options={audienceOptions}
                                                    open={openMenu === 'audience'}
                                                    disabled={!categoryId}
                                                    onOpen={() => setOpenMenu(openMenu === 'audience' ? null : 'audience')}
                                                    onChange={(value) => setRefineField('audience', value)}
                                                    emptyLabel={t('selectEllipsis')}
                                                />
                                            </div>
                                            {selectedService?.audience_gender ? (
                                                <p className="mt-2 text-[0.8125rem] font-medium text-muted-foreground">
                                                    {t('currentPackage', { defaultValue: 'Current package:' })} {audienceLabel(selectedService.audience_gender, t)}
                                                </p>
                                            ) : null}
                                        </SettingsRow>
                                    ) : null}

                                    {showReactionSettings ? (
                                        <SettingsRow
                                            icon={Heart}
                                            title={t('reaction')}
                                            active={refine.reaction !== 'any'}
                                            open={settingsRowOpen === 'reaction'}
                                            onToggle={() => setSettingsRowOpen(settingsRowOpen === 'reaction' ? null : 'reaction')}
                                            statusOnLabel={statusOnLabel}
                                            statusOffLabel={statusOffLabel}
                                        >
                                            <div className="rounded-md border border-[var(--color-dash-border-subtle)] bg-muted/30 px-3 py-2">
                                                <p className="mb-2 text-[0.8125rem] font-medium text-muted-foreground">
                                                    {t('pickReaction', { defaultValue: 'Pick one reaction type' })}
                                                </p>
                                                <div className="flex flex-wrap gap-2">
                                                    {reactionOptions.map((opt) => {
                                                        const checked = refine.reaction === opt.value;
                                                        return (
                                                            <label
                                                                key={opt.value}
                                                                className={cn(
                                                                    'inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-semibold transition',
                                                                    checked
                                                                        ? 'border-primary/40 bg-primary/10 text-primary'
                                                                        : 'border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] text-foreground hover:border-primary/30',
                                                                )}
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    name="reaction-type"
                                                                    className="size-3.5 accent-primary"
                                                                    checked={checked}
                                                                    onChange={() => setRefineField('reaction', opt.value)}
                                                                />
                                                                {opt.icon ? (
                                                                    <img src={opt.icon} alt="" className="size-4 object-contain" aria-hidden />
                                                                ) : null}
                                                                {opt.label}
                                                            </label>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                            {selectedService?.reaction_type ? (
                                                <p className="mt-2 text-[0.8125rem] font-medium text-muted-foreground">
                                                    {t('currentPackage', { defaultValue: 'Current package:' })} {facebookReactionLabel(selectedService.reaction_type)}
                                                </p>
                                            ) : null}
                                        </SettingsRow>
                                    ) : null}

                                    {hasActiveRefine ? (
                                        <p className="text-[0.8125rem] font-medium text-muted-foreground">
                                            {t('activeRefine', { defaultValue: 'Active:' })}{' '}
                                            {[
                                                showQualitySettings && refine.quality !== 'any' ? optionLabel(qualityOptions, refine.quality, t) : null,
                                                showDeliverySettings && refine.delivery !== 'any' ? optionLabel(deliveryOptions, refine.delivery, t) : null,
                                                showProtectionSettings && refine.protection !== 'any' ? optionLabel(protectionOptions, refine.protection, t) : null,
                                                hasRefillDayRefine
                                                    ? (refine.refillDays || []).map((d) => `${d}d`).join(', ')
                                                    : null,
                                                showCountrySettings && refine.country !== 'any' ? countryLabel(refine.country) : null,
                                                showAudienceSettings && refine.audience !== 'any' ? audienceLabel(refine.audience, t) : null,
                                                showReactionSettings && refine.reaction !== 'any' ? facebookReactionLabel(refine.reaction) : null,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    ) : null}
                                </div>
                                </div>
                            </div>
                        </div>

                <button
                    type="submit"
                    disabled={!selectedService || loadingServices}
                    className="group relative inline-flex h-auto w-full items-center justify-center rounded-md bg-primary px-4 py-2.5 text-base font-medium text-primary-foreground shadow-[0_1px_2px_0_rgba(14,18,27,0.24),0_0_0_1px_var(--color-primary)] transition-all duration-150 hover:bg-primary/90 active:scale-[0.99] disabled:pointer-events-none disabled:opacity-50"
                >
                    <span className="flex flex-col items-center gap-0.5">
                        <span className="flex items-center gap-1.5">
                            <span className="leading-none font-semibold">{t('checkoutNow', { amount: formatDzd(charge) })}</span>
                            <ArrowRight className="-ml-1 size-5 transition-transform duration-200 group-hover:translate-x-1" />
                        </span>
                        <span className="-mt-1 text-[10px] leading-none opacity-60">
                            {selectedService?.start_class === 'instant' ? t('estimatedInstant') : t('estimatedHours')}
                        </span>
                    </span>
                </button>
                <p className="mx-auto max-w-md text-center text-[0.6rem] leading-tight text-muted-foreground/60">
                    {t('confirmTarget')}
                </p>
            </form>

            {paymentNotice ? (
                <PaymentResultModal
                    type={paymentNotice.type}
                    orderId={paymentNotice.orderId ?? null}
                    onClose={() => setPaymentNotice(null)}
                />
            ) : null}
        </div>
    );
}
