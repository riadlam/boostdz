import { useEffect, useMemo, useState } from 'react';
import { Moon, Sun, Menu, X, ArrowRight, LayoutDashboard, ShoppingBag, Minus, Square, Zap } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { BrandLogo, PressLogos } from '../components/Brand';
import { heroPlatforms, InstagramIcon } from '../components/PlatformIcons';
import { WhySection } from '../components/landing/WhySection';
import { PlatformsSection } from '../components/landing/PlatformsSection';
import { TestimonialsSection } from '../components/landing/TestimonialsSection';
import LanguageSwitcher from '../components/LanguageSwitcher';
import { useTheme } from '../components/ThemeProvider';
import { cn } from '../lib/cn';

function ThemeToggle() {
    const { theme, toggle } = useTheme();
    const { t } = useTranslation('common');

    return (
        <button
            type="button"
            onClick={toggle}
            aria-label={t('aria.toggleTheme')}
            className="flex h-9 w-9 items-center justify-center rounded-full border border-border hover:bg-muted"
        >
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </button>
    );
}

function Header() {
    const { t } = useTranslation('landing');
    const nav = t('nav', { returnObjects: true });
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <header
            className={cn(
                'sticky top-0 z-50 border-b border-transparent backdrop-blur-md transition-colors',
                scrolled && 'border-border bg-background/80',
            )}
        >
            <div className="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:h-16">
                <BrandLogo className="h-9 max-h-9 sm:h-14 sm:max-h-14" nameClassName="text-base sm:text-xl" />
                <nav className="hidden items-center gap-6 text-sm text-muted-foreground md:flex">
                    {nav.map((item) => (
                        <a key={item.href} href={item.href} className="hover:text-foreground">
                            {item.label}
                        </a>
                    ))}
                </nav>
                <div className="hidden items-center gap-2 md:flex">
                    <ThemeToggle />
                    <a href="/auth/sign-in" className="px-3 py-2 text-sm hover:text-muted-foreground">
                        {t('header.signIn')}
                    </a>
                    <a href="/auth/sign-in" className="btn-primary">
                        {t('header.startFree')}
                    </a>
                </div>
                <div className="flex items-center gap-2 md:hidden">
                    <ThemeToggle />
                    <button type="button" className="p-2" onClick={() => setOpen((v) => !v)} aria-label={t('header.menu')}>
                        {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>
                </div>
            </div>
            {open && (
                <div className="border-t border-border bg-background px-4 py-4 md:hidden">
                    <div className="flex flex-col gap-3 text-sm">
                        {nav.map((item) => (
                            <a key={item.href} href={item.href} onClick={() => setOpen(false)}>
                                {item.label}
                            </a>
                        ))}
                        <a href="/auth/sign-in" className="btn-ghost">
                            {t('header.signIn')}
                        </a>
                        <a href="/auth/sign-in" className="btn-primary" onClick={() => setOpen(false)}>
                            {t('header.startFree')}
                        </a>
                    </div>
                </div>
            )}
        </header>
    );
}

function HeroPlatformBadge() {
    return (
        <span className="relative isolate mx-1 -mt-2 inline-flex -rotate-2 items-center gap-1 overflow-hidden rounded-2xl bg-white/90 px-3 py-1.5 align-middle text-[0.85em] ring-1 ring-black/10 dark:bg-neutral-800/90 dark:ring-neutral-700">
            <span className="sr-only">Instagram</span>
            <span aria-hidden className="invisible inline-flex items-center gap-1">
                <InstagramIcon className="size-[0.9em]" />
                <span className="font-semibold">Instagram</span>
            </span>
            {heroPlatforms.map(({ label, Icon }, i) => (
                <span
                    key={label}
                    aria-hidden
                    className="landing-social-badge-item absolute inset-0 flex items-center justify-center gap-1 opacity-0"
                    data-current={i === 0 ? 'true' : undefined}
                    style={{ '--badge-delay': `${i * 2.5}s` }}
                >
                    <Icon className="size-[0.9em]" />
                    <span className="bg-gradient-to-r from-primary via-primary/70 to-primary/50 bg-clip-text font-semibold text-transparent">
                        {label}
                    </span>
                </span>
            ))}
        </span>
    );
}

function Hero() {
    const { t } = useTranslation('landing');

    return (
        <section className="relative overflow-hidden px-4 pb-8 pt-10 text-center sm:pb-10 sm:pt-16 md:pt-24">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(59,130,246,0.14),transparent_55%)]" />
            <div className="relative mx-auto max-w-4xl">
                <div className="mb-4 inline-flex max-w-full flex-wrap items-center justify-center gap-2 rounded-full border border-border bg-card/80 px-3 py-1 text-xs text-muted-foreground shadow-sm sm:mb-6">
                    <span className="font-medium text-foreground">{t('hero.beta')}</span>
                    <a href="#why" className="text-primary hover:underline">
                        {t('hero.learnMore')}
                    </a>
                </div>
                <h1 className="text-[2rem] leading-[1.05] font-semibold tracking-tighter text-balance sm:text-5xl lg:text-6xl">
                    {t('hero.titleLine1')} <HeroPlatformBadge />
                    <br />
                    {t('hero.titleLine2')}
                </h1>
                <p className="mx-auto mt-4 max-w-xl text-sm text-muted-foreground sm:mt-5 sm:text-base">
                    {t('hero.subtitle')}
                </p>
                <div className="mt-6 flex flex-col items-stretch gap-3 sm:mt-8 sm:flex-row sm:flex-wrap sm:items-center sm:justify-center">
                    <a href="#platforms" className="btn-primary justify-center">
                        {t('hero.getStarted')} <ArrowRight className="h-4 w-4" />
                    </a>
                    <a href="#why" className="btn-ghost justify-center hover:bg-muted">
                        {t('hero.learnMoreCta')}
                    </a>
                </div>
                <div className="mt-8 sm:mt-12 opacity-70 transition-opacity hover:opacity-100">
                    <p className="text-[10px] uppercase tracking-widest text-muted-foreground">{t('hero.asSeenOn')}</p>
                    <div className="mt-3">
                        <PressLogos />
                    </div>
                    <p className="mt-2 text-[10px] text-muted-foreground/70">{t('hero.asSeenNote')}</p>
                </div>
            </div>
        </section>
    );
}

const PREVIEW_STACK = {
    widthStep: 12.5,
    yOffsetStep: 36,
    hoverLift: -8,
    aspectRatio: 1.64571,
};

function PreviewScreenshot({ alt, light, dark }) {
    return (
        <div className="relative aspect-video w-full overflow-hidden rounded-lg bg-muted/30 ring-1 ring-foreground/5 ring-inset">
            <img
                alt={alt}
                src={light}
                className="block size-full object-cover object-top dark:hidden"
                width={2138}
                height={1203}
                loading="lazy"
            />
            <img
                alt={alt}
                src={dark}
                className="hidden size-full object-cover object-top dark:block"
                width={2138}
                height={1203}
                loading="lazy"
            />
        </div>
    );
}

function PreviewCardChrome({ item, isActive }) {
    const Icon = item.Icon;
    return (
        <div className="flex items-center justify-between p-3 sm:px-4">
            <div className="flex min-w-0 items-center gap-1.5 sm:gap-2.5">
                <div
                    className={cn(
                        'flex size-5 shrink-0 items-center justify-center rounded-md bg-background shadow-sm sm:size-6 sm:rounded-lg [&>svg]:size-3 sm:[&>svg]:size-4',
                        isActive ? 'text-primary' : 'text-muted-foreground',
                    )}
                    style={isActive ? { color: item.accent } : undefined}
                >
                    <Icon />
                </div>
                <div className={cn('shrink-0 text-xs font-medium whitespace-nowrap sm:text-sm', isActive ? 'text-foreground' : 'text-muted-foreground')}>
                    {item.label}
                </div>
                <div className="hidden text-xs text-muted-foreground/50 sm:block">-</div>
                <div className="hidden truncate text-[10px] text-muted-foreground sm:block sm:text-sm">{item.description}</div>
            </div>
            <div className="hidden shrink-0 gap-2 text-muted-foreground/40 sm:flex [&>svg]:size-4">
                <Minus />
                <Square />
                <X />
            </div>
        </div>
    );
}

function ProductPreview() {
    const { t } = useTranslation('landing');
    const previewTabs = useMemo(
        () => [
            {
                id: 'dashboard',
                label: t('preview.dashboard'),
                description: t('preview.dashboardDesc'),
                accent: '#3b82f6',
                Icon: LayoutDashboard,
                light: '/assets/preview/dashboard.webp',
                dark: '/assets/preview/dashboard-dark.webp',
            },
            {
                id: 'orders',
                label: t('preview.orders'),
                description: t('preview.ordersDesc'),
                accent: '#10b981',
                Icon: ShoppingBag,
                light: '/assets/preview/orders.webp',
                dark: '/assets/preview/orders-dark.webp',
            },
            {
                id: 'automation',
                label: t('preview.automation'),
                description: t('preview.automationDesc'),
                accent: '#f97316',
                Icon: Zap,
                light: '/assets/preview/automation.webp',
                dark: '/assets/preview/automation-dark.webp',
            },
        ],
        [t],
    );

    const [active, setActive] = useState(0);
    const [hoverIndex, setHoverIndex] = useState(null);
    const total = previewTabs.length;
    const stackPad = (total - 1) * PREVIEW_STACK.yOffsetStep;

    const visualOrder = (index) => {
        if (index === active) return 0;
        const delta = index - active;
        return delta > 0 ? delta : total + delta;
    };

    return (
        <section className="relative overflow-hidden pb-12 pt-2 sm:pb-16 sm:pt-4">
            <div className="mb-6 flex justify-center px-4 sm:hidden">
                <div className="flex items-center gap-1 rounded-full bg-muted/40 p-1">
                    {previewTabs.map((item, index) => {
                        const Icon = item.Icon;
                        const selected = index === active;
                        return (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => setActive(index)}
                                className={cn(
                                    'relative isolate flex h-9 min-w-9 items-center overflow-hidden rounded-full px-2 transition-[background-color,box-shadow] duration-300',
                                    selected && 'bg-background shadow-md',
                                )}
                            >
                                <div
                                    className="flex size-6 shrink-0 items-center justify-center overflow-hidden rounded-full text-white [&>svg]:size-3.5"
                                    style={{ backgroundColor: item.accent }}
                                >
                                    <Icon />
                                </div>
                                <div className={cn('grid transition-[grid-template-columns] duration-300', selected ? 'grid-cols-[1fr]' : 'grid-cols-[0fr]')}>
                                    <div className="overflow-hidden">
                                        <span
                                            className={cn(
                                                'block pr-1 pl-2 text-sm font-medium whitespace-nowrap text-foreground transition-opacity duration-200',
                                                selected ? 'opacity-100 delay-150' : 'opacity-0',
                                            )}
                                        >
                                            {item.label}
                                        </span>
                                    </div>
                                </div>
                            </button>
                        );
                    })}
                </div>
            </div>

            <div className="mx-auto hidden w-fit max-w-full px-4 sm:block">
                <div className="relative bg-foreground/[0.02] p-1.5 before:pointer-events-none before:absolute before:-inset-x-4 before:inset-y-0 before:border-y before:border-foreground/10 after:pointer-events-none after:absolute after:inset-x-0 after:-inset-y-2 after:border-x after:border-foreground/10">
                    <div className="grid grid-cols-[1fr_auto_1fr] border-y border-foreground/10">
                        <div />
                        <div className="grid auto-cols-fr grid-flow-col divide-x divide-foreground/10 border-x border-foreground/10">
                            {previewTabs.map((item, index) => {
                                const Icon = item.Icon;
                                const selected = index === active;
                                return (
                                    <button
                                        key={item.id}
                                        type="button"
                                        onClick={() => setActive(index)}
                                        className={cn(
                                            'group flex h-14 cursor-pointer items-center justify-center px-4 transition duration-200',
                                            selected ? 'bg-muted/50' : 'hover:bg-muted/30',
                                        )}
                                    >
                                        <div
                                            className={cn(
                                                'flex h-9 items-center gap-2 rounded-full px-4 transition-all duration-150 group-active:scale-[0.98]',
                                                selected
                                                    ? 'bg-background shadow-sm ring-1 shadow-black/10 ring-foreground/10'
                                                    : 'ring-1 ring-foreground/10 group-hover:bg-background/50',
                                            )}
                                        >
                                            <div
                                                className="flex size-5 items-center justify-center overflow-hidden rounded-md text-white [&>svg]:size-3.5"
                                                style={{ backgroundColor: item.accent }}
                                            >
                                                <Icon />
                                            </div>
                                            <span className="text-sm font-medium text-foreground">{item.label}</span>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                        <div />
                    </div>
                </div>
            </div>

            <div className="relative z-10 mx-auto mt-2 hidden w-full max-w-5xl overflow-visible px-4 mask-radial-[115%_100%] mask-radial-from-65% mask-radial-at-top sm:block">
                <div
                    className="relative w-full"
                    style={{
                        paddingTop: `${stackPad + 96}px`,
                        aspectRatio: `${PREVIEW_STACK.aspectRatio} / 1`,
                    }}
                >
                    {previewTabs.map((item, index) => {
                        const order = visualOrder(index);
                        const isActive = order === 0;
                        const width = 100 - order * PREVIEW_STACK.widthStep;
                        const y = -order * PREVIEW_STACK.yOffsetStep + (!isActive && hoverIndex === index ? PREVIEW_STACK.hoverLift : 0);
                        const top = stackPad + 40;
                        const chromeBg = order === 0 ? 'bg-neutral-200 dark:bg-neutral-600' : 'bg-neutral-100 dark:bg-neutral-800';

                        return (
                            <div
                                key={item.id}
                                className="absolute left-1/2 origin-top will-change-transform transition-[width,transform,filter] duration-500 ease-[cubic-bezier(.32,.72,0,1)]"
                                style={{
                                    width: `${width}%`,
                                    top: `${top}px`,
                                    transform: `translateX(-50%) translateY(${y}px)`,
                                    zIndex: total - order,
                                }}
                                onClick={() => setActive(index)}
                                onMouseEnter={() => setHoverIndex(index)}
                                onMouseLeave={() => setHoverIndex(null)}
                            >
                                <div
                                    className={cn(
                                        'w-full origin-top cursor-pointer overflow-hidden rounded-t-3xl ring-1 ring-foreground/10 ring-inset select-none',
                                        chromeBg,
                                    )}
                                    style={{ aspectRatio: `${PREVIEW_STACK.aspectRatio} / 1` }}
                                >
                                    <PreviewCardChrome item={item} isActive={isActive} />
                                    <div className="px-3 pb-3">
                                        <PreviewScreenshot alt={`${item.label} preview`} light={item.light} dark={item.dark} />
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            <div className="relative mx-auto w-full max-w-lg px-4 sm:hidden">
                <div className="overflow-hidden rounded-t-2xl bg-neutral-200 ring-1 ring-foreground/10 ring-inset dark:bg-neutral-700">
                    <PreviewCardChrome item={previewTabs[active]} isActive />
                    <div className="px-3 pb-3">
                        <PreviewScreenshot
                            alt={`${previewTabs[active].label} preview`}
                            light={previewTabs[active].light}
                            dark={previewTabs[active].dark}
                        />
                    </div>
                </div>
                <div className="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-background to-transparent" />
            </div>
        </section>
    );
}

function Footer() {
    const { t } = useTranslation('landing');
    const links = t('footer.links', { returnObjects: true });
    const products = t('footer.products', { returnObjects: true });
    const tools = t('footer.tools', { returnObjects: true });

    return (
        <footer className="border-t border-border px-4 py-12 sm:py-16">
            <div className="mx-auto grid max-w-6xl gap-8 sm:grid-cols-2 md:grid-cols-4 md:gap-10">
                <div>
                    <BrandLogo />
                    <p className="mt-4 text-sm text-muted-foreground">{t('footer.about')}</p>
                    <div className="mt-4 flex items-center gap-3">
                        <LanguageSwitcher />
                        <ThemeToggle />
                    </div>
                </div>
                <div>
                    <p className="text-sm font-semibold">{t('footer.sections.links')}</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {links.map((l) => (
                            <li key={l.label}>
                                <a href={l.href} className="hover:text-foreground">
                                    {l.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <p className="text-sm font-semibold">{t('footer.sections.products')}</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {products.slice(0, 8).map((l) => (
                            <li key={l}>
                                <a href="#platforms" className="hover:text-foreground">
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <p className="text-sm font-semibold">{t('footer.sections.tools')}</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {tools.slice(0, 6).map((l) => (
                            <li key={l}>
                                <a href="#" className="hover:text-foreground">
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-6 text-sm font-semibold">{t('footer.sections.comparisons')}</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {comparisons.slice(0, 5).map((l) => (
                            <li key={l}>
                                <a href="#" className="hover:text-foreground">
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
            <div className="mx-auto mt-10 flex max-w-6xl flex-col items-center justify-between gap-4 border-t border-border pt-6 text-center text-xs text-muted-foreground sm:mt-12 md:flex-row md:text-left">
                <p>
                    © {new Date().getFullYear()} {t('brand.name')}. {t('footer.rights')}
                </p>
                <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
                    <a href="#">{t('footer.terms')}</a>
                    <a href="#">{t('footer.refund')}</a>
                    <a href="#">{t('footer.privacy')}</a>
                </div>
            </div>
        </footer>
    );
}

export default function Home() {
    return (
        <div id="top" className="overflow-x-hidden">
            <Header />
            <main className="overflow-hidden" role="main">
                <Hero />
                <ProductPreview />
                <WhySection />
                <PlatformsSection />
                <TestimonialsSection />
            </main>
            <Footer />
        </div>
    );
}
