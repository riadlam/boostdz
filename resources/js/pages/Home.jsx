import { useEffect, useState } from 'react';
import { Moon, Sun, Menu, X, ArrowRight, LayoutDashboard, ShoppingBag, Minus, Square, Zap } from 'lucide-react';
import { BrandLogo, PressLogos } from '../components/Brand';
import { heroPlatforms, InstagramIcon } from '../components/PlatformIcons';
import { WhySection } from '../components/landing/WhySection';
import { PlatformsSection } from '../components/landing/PlatformsSection';
import { TestimonialsSection } from '../components/landing/TestimonialsSection';
import { useTheme } from '../components/ThemeProvider';
import { cn } from '../lib/cn';
import {
    nav,
    footer,
} from '../content/landing';

function ThemeToggle() {
    const { theme, toggle } = useTheme();
    return (
        <button
            type="button"
            onClick={toggle}
            aria-label="Toggle theme"
            className="flex h-9 w-9 items-center justify-center rounded-full border border-border hover:bg-muted"
        >
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </button>
    );
}

function Header() {
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
            <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <BrandLogo className="text-base" />
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
                        Sign in
                    </a>
                    <a href="/auth/sign-in" className="btn-primary">
                        Start for free
                    </a>
                </div>
                <div className="flex items-center gap-2 md:hidden">
                    <ThemeToggle />
                    <button type="button" className="p-2" onClick={() => setOpen((v) => !v)} aria-label="Menu">
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
                            Sign in
                        </a>
                        <a href="/auth/sign-in" className="btn-primary" onClick={() => setOpen(false)}>
                            Start for free
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
    return (
        <section className="relative overflow-hidden px-4 pb-10 pt-16 text-center md:pt-24">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(59,130,246,0.14),transparent_55%)]" />
            <div className="relative mx-auto max-w-4xl">
                <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-card/80 px-3 py-1 text-xs text-muted-foreground shadow-sm">
                    <span className="font-medium text-foreground">BOOSTDZ is now in public beta</span>
                    <a href="#why" className="text-primary hover:underline">
                        Learn more
                    </a>
                </div>
                <h1 className="text-5xl leading-none font-semibold tracking-tighter text-balance lg:text-6xl">
                    Grow Your <HeroPlatformBadge />
                    <br />
                    Real Likes, Real Followers.
                </h1>
                <p className="mx-auto mt-5 max-w-xl text-muted-foreground">
                    Buy real likes, followers, and views for Instagram, TikTok, YouTube, and X — with instant delivery and no
                    password required. Genuine engagement that helps every post travel further.
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="#platforms" className="btn-primary">
                        Get Started <ArrowRight className="h-4 w-4" />
                    </a>
                    <a href="#why" className="btn-ghost hover:bg-muted">
                        Learn More
                    </a>
                </div>
                <div className="mt-12 opacity-70 transition-opacity hover:opacity-100">
                    <p className="text-[10px] uppercase tracking-widest text-muted-foreground">As seen on</p>
                    <div className="mt-3">
                        <PressLogos />
                    </div>
                    <p className="mt-2 text-[10px] text-muted-foreground/70">Yes, it&apos;s not fake, we really did get featured</p>
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

const previewTabs = [
    {
        id: 'dashboard',
        label: 'Dashboard',
        description: 'Overview of your growth',
        accent: '#3b82f6',
        Icon: LayoutDashboard,
        light: '/assets/preview/dashboard.webp',
        dark: '/assets/preview/dashboard-dark.webp',
    },
    {
        id: 'orders',
        label: 'Orders',
        description: 'Manage your orders',
        accent: '#10b981',
        Icon: ShoppingBag,
        light: '/assets/preview/orders.webp',
        dark: '/assets/preview/orders-dark.webp',
    },
    {
        id: 'automation',
        label: 'Automation',
        description: 'Set rules and let it run',
        accent: '#f97316',
        Icon: Zap,
        light: '/assets/preview/automation.webp',
        dark: '/assets/preview/automation-dark.webp',
    },
];

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
                <div className="text-xs text-muted-foreground/50">-</div>
                <div className="truncate text-[10px] text-muted-foreground sm:text-sm">{item.description}</div>
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
        <section className="relative overflow-hidden pb-16 pt-4">
            {/* Mobile tabs */}
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

            {/* Desktop tab bar */}
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

            {/* Desktop stacked cards */}
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

            {/* Mobile single preview */}
            <div className="relative left-1/2 w-screen -translate-x-1/2 px-4 sm:hidden">
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
    return (
        <footer className="border-t border-border px-4 py-16">
            <div className="mx-auto grid max-w-6xl gap-10 md:grid-cols-4">
                <div>
                    <BrandLogo />
                    <p className="mt-4 text-sm text-muted-foreground">
                        Powering social media growth. BOOSTDZ serves creators, agencies, and brands with engagement services
                        across Instagram, TikTok, YouTube, and X. Based in Algeria.
                    </p>
                    <div className="mt-4 flex items-center gap-3 text-xs text-muted-foreground">
                        <span>EN English</span>
                        <ThemeToggle />
                    </div>
                </div>
                <div>
                    <p className="text-sm font-semibold">Links</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {footer.links.map((l) => (
                            <li key={l.label}>
                                <a href={l.href} className="hover:text-foreground">
                                    {l.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <p className="text-sm font-semibold">Featured Products</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {footer.products.slice(0, 8).map((l) => (
                            <li key={l}>
                                <a href="#platforms" className="hover:text-foreground">
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <p className="text-sm font-semibold">Free Tools</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {footer.tools.slice(0, 6).map((l) => (
                            <li key={l}>
                                <a href="#" className="hover:text-foreground">
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-6 text-sm font-semibold">Comparisons</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                        {footer.comparisons.slice(0, 5).map((l) => (
                            <li key={l}>
                                <a href="#" className="hover:text-foreground">
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
            <div className="mx-auto mt-12 flex max-w-6xl flex-col items-center justify-between gap-3 border-t border-border pt-6 text-xs text-muted-foreground md:flex-row">
                <p>© {new Date().getFullYear()} BOOSTDZ. All rights reserved.</p>
                <div className="flex gap-4">
                    <a href="#">Terms of Service</a>
                    <a href="#">Refund Policy</a>
                    <a href="#">Privacy Policy</a>
                </div>
            </div>
        </footer>
    );
}

export default function Home() {
    return (
        <div id="top">
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
