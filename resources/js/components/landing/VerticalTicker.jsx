import { cn } from '../../lib/cn';
import { MARQUEE_IMAGES } from '../../content/marquee-manifest';

const PLATFORM_CONFIG = {
    instagram: {
        aspect: 'aspect-square',
        thumbClass: 'rounded-md',
        width: 100,
    },
    tiktok: {
        aspect: 'aspect-[9/16]',
        thumbClass: 'rounded-lg',
        width: 72,
    },
    youtube: {
        aspect: 'aspect-video',
        thumbClass: 'rounded-md',
        width: 112,
        overlay: true,
    },
    facebook: {
        aspect: 'aspect-[4/5]',
        thumbClass: 'rounded-xl',
        width: 100,
    },
};

function marqueeSrc(platform, n) {
    const files = MARQUEE_IMAGES[platform] ?? MARQUEE_IMAGES.instagram;
    return `/assets/marquee/${platform}/${files[((n - 1) % files.length)]}`;
}

function MarqueeThumb({ platform, n }) {
    const config = PLATFORM_CONFIG[platform] ?? PLATFORM_CONFIG.instagram;

    return (
        <div className={cn('shrink-0', config.aspect)} style={{ width: config.width }}>
            <div className={cn('relative size-full overflow-hidden border border-white/30', config.thumbClass)}>
                <img
                    alt=""
                    className="size-full object-cover"
                    height={512}
                    loading="lazy"
                    src={marqueeSrc(platform, n)}
                    width={512}
                />
                {config.overlay ? (
                    <div
                        aria-hidden="true"
                        className="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/70 to-transparent"
                    />
                ) : null}
            </div>
        </div>
    );
}

export function VerticalTicker({
    platform = 'instagram',
    direction = 'bottom',
    startIndex = 1,
    count = 10,
    speed = 4,
    className,
}) {
    const indices = Array.from({ length: count }, (_, i) => startIndex + i);
    const track = [...indices, ...indices];
    const duration = speed * 10;

    return (
        <div
            className={cn('ticker-vertical overflow-hidden', className)}
            style={{
                WebkitMaskImage:
                    'linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,1) 15%, rgba(0,0,0,1) 85%, rgba(0,0,0,0) 100%)',
                maskImage:
                    'linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,1) 15%, rgba(0,0,0,1) 85%, rgba(0,0,0,0) 100%)',
            }}
        >
            <div
                className={cn('ticker-track flex flex-col gap-2.5', direction === 'top' ? 'ticker-up' : 'ticker-down')}
                style={{ '--ticker-duration': `${duration}s` }}
            >
                {track.map((n, i) => (
                    <MarqueeThumb key={`${n}-${i}`} n={n} platform={platform} />
                ))}
            </div>
        </div>
    );
}

export function PlatformCardDecoration({ gradient, children }) {
    return (
        <div aria-hidden="true" className="pointer-events-none absolute -inset-y-8 -right-16 -z-10 rotate-[4deg] sm:-right-24 md:-right-8">
            <div className="absolute -inset-x-2 inset-y-0 border-y border-dashed border-foreground/10" />
            <div className="absolute inset-x-0 -inset-y-2 border-x border-dashed border-foreground/10" />
            <div
                aria-hidden="true"
                className={cn(
                    'absolute inset-0 -z-10 opacity-15 blur-3xl dark:opacity-[0.08]',
                    gradient,
                )}
            />
            {children}
        </div>
    );
}

export function DualTickerDecoration({
    platform = 'instagram',
    leftDirection = 'bottom',
    rightDirection = 'top',
    leftStart = 1,
    rightStart = 6,
}) {
    return (
        <PlatformCardDecoration gradient="bg-[radial-gradient(circle,#E1306C_0%,#F77737_40%,#FCAF45_70%,transparent_100%)]">
            <div className="grid h-full grid-cols-2 overflow-hidden rounded-xl border border-foreground/10 opacity-65 shadow-xl ring-1 ring-foreground/5 dark:opacity-50">
                <VerticalTicker direction={leftDirection} platform={platform} startIndex={leftStart} speed={4} />
                <VerticalTicker direction={rightDirection} platform={platform} startIndex={rightStart} speed={3} />
            </div>
        </PlatformCardDecoration>
    );
}

export function SingleTickerDecoration({
    platform = 'youtube',
    gradient,
    direction = 'bottom',
    startIndex = 1,
}) {
    return (
        <PlatformCardDecoration gradient={gradient}>
            <div className="grid h-full grid-cols-1 overflow-hidden rounded-xl border border-foreground/10 opacity-65 shadow-xl ring-1 ring-foreground/5 dark:opacity-50">
                <VerticalTicker direction={direction} platform={platform} startIndex={startIndex} speed={3} count={10} />
            </div>
        </PlatformCardDecoration>
    );
}
