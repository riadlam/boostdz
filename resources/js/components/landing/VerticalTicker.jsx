import { cn } from '../../lib/cn';

function marqueeSrc(n) {
    return `/assets/marquee/${((n - 1) % 20) + 1}.webp`;
}

export function VerticalTicker({ direction = 'bottom', startIndex = 1, count = 10, speed = 4, className }) {
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
                    <div key={`${n}-${i}`} className="aspect-square size-25 shrink-0">
                        <img
                            alt={`Post ${n}`}
                            className="aspect-square size-full rounded-md border border-white/30 object-cover"
                            height={512}
                            loading="lazy"
                            src={marqueeSrc(n)}
                            width={512}
                        />
                    </div>
                ))}
            </div>
        </div>
    );
}

export function PlatformCardDecoration({ gradient, children }) {
    return (
        <div aria-hidden="true" className="pointer-events-none absolute -inset-y-8 -right-32 -z-10 rotate-[4deg] md:-right-8">
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

export function DualTickerDecoration({ leftDirection = 'bottom', rightDirection = 'top', leftStart = 1, rightStart = 11 }) {
    return (
        <PlatformCardDecoration gradient="bg-[radial-gradient(circle,#E1306C_0%,#F77737_40%,#FCAF45_70%,transparent_100%)]">
            <div className="grid h-full grid-cols-2 overflow-hidden rounded-xl border border-foreground/10 opacity-65 shadow-xl ring-1 ring-foreground/5 dark:opacity-50">
                <VerticalTicker direction={leftDirection} startIndex={leftStart} speed={4} />
                <VerticalTicker direction={rightDirection} startIndex={rightStart} speed={3} />
            </div>
        </PlatformCardDecoration>
    );
}
