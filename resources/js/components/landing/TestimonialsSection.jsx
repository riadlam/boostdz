import { useCallback, useState } from 'react';
import { ChevronLeft, ChevronRight, Play } from 'lucide-react';
import { brand, testimonials } from '../../content/landing';
import { cn } from '../../lib/cn';

function TestimonialCard({ item, offset, isActive, onClick }) {
    const absOffset = Math.abs(offset);
    const scale = isActive ? 1 : Math.max(0.92, 1 - absOffset * 0.035);
    const y = isActive ? 0 : absOffset * 12;
    const blur = isActive ? 0 : Math.min(absOffset * 1.5, 4);
    const opacity = isActive ? 1 : Math.max(0.5, 1 - absOffset * 0.25);

    return (
        <button
            type="button"
            onClick={onClick}
            className="absolute inset-0 origin-bottom overflow-hidden rounded-2xl border border-transparent text-left ring-1 bg-card shadow-lg shadow-black/10 ring-border transition-[transform,filter,opacity] duration-500 ease-out"
            style={{
                zIndex: 10 - absOffset,
                transform: `translateY(${y}px) scale(${scale})`,
                filter: blur ? `blur(${blur}px)` : undefined,
                opacity,
                pointerEvents: absOffset > 1 ? 'none' : undefined,
            }}
        >
            <div className="relative z-10 flex h-full flex-col overflow-hidden p-5 sm:p-6">
                <div className="flex items-center gap-3">
                    <img
                        src={item.avatar}
                        alt={item.name}
                        className="size-11 rounded-full object-cover ring-2 ring-border"
                        width={44}
                        height={44}
                    />
                    <div className="min-w-0">
                        <p className="truncate font-semibold">{item.name}</p>
                        <p className="truncate text-sm text-muted-foreground">{item.role}</p>
                    </div>
                </div>
                <blockquote className="mt-4 line-clamp-5 flex-1 overflow-hidden text-sm leading-relaxed text-foreground/90 sm:text-base">
                    <span className="font-serif">“</span>
                    {item.quote}
                    <span className="font-serif">”</span>
                </blockquote>
                <div className="mt-3 flex items-center gap-2 text-xs text-muted-foreground">
                    <span className="inline-flex size-7 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <Play className="size-3 fill-current" />
                    </span>
                    Tap to play · swipe for more
                </div>
            </div>
        </button>
    );
}

function NavArrow({ label, onClick, children }) {
    return (
        <button
            type="button"
            aria-label={label}
            onClick={onClick}
            className="hit-area-2 flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full border border-transparent bg-card shadow-sm ring-1 shadow-black/15 ring-foreground/10 duration-200 hover:bg-muted/50 dark:ring-foreground/15"
        >
            {children}
        </button>
    );
}

export function TestimonialsSection() {
    const [active, setActive] = useState(0);
    const total = testimonials.length;

    const go = useCallback(
        (dir) => {
            setActive((i) => (i + dir + total) % total);
        },
        [total],
    );

    const visible = [-1, 0, 1].map((offset) => {
        const index = (active + offset + total) % total;
        return { offset, index, item: testimonials[index] };
    });

    return (
        <div className="bg-background">
            <div className="relative z-10 mx-auto -mt-16 -mb-4 h-20 w-px bg-gradient-to-b from-transparent via-foreground/15 to-transparent md:-mt-20 md:-mb-8 md:h-32" />

            <section id="testimonials" className="relative pt-4 pb-16 md:pb-24">
                <div className="mx-auto max-w-6xl px-4 md:px-6">
                    <div className="mb-10 text-center md:mb-14">
                        <h2 className="text-3xl font-semibold tracking-tighter text-balance lg:text-5xl dark:text-white">
                            Hear It From Creators, Brands &amp; Agencies
                        </h2>
                        <p className="mx-auto mt-4 max-w-2xl text-sm text-muted-foreground sm:text-sm">
                            See how creators, businesses, and agencies use {brand.name} for real social proof and measurable
                            social media growth.
                        </p>
                    </div>

                    {/* One row on lg: reviews + arrows | stats | leave review */}
                    <div className="flex flex-col items-stretch gap-8 lg:flex-row lg:items-center lg:gap-6 xl:gap-8">
                        {/* Review carousel with inline arrows */}
                        <div className="flex min-w-0 flex-1 items-center gap-3">
                            <NavArrow label="Previous" onClick={() => go(-1)}>
                                <ChevronLeft className="size-4" />
                            </NavArrow>

                            <div className="relative h-72 w-full min-w-0 flex-1 sm:h-80" style={{ contain: 'layout' }}>
                                {visible.map(({ offset, index, item }) => (
                                    <TestimonialCard
                                        key={`${index}-${offset}`}
                                        item={item}
                                        offset={offset}
                                        isActive={offset === 0}
                                        onClick={() => (offset === 0 ? go(1) : setActive(index))}
                                    />
                                ))}
                            </div>

                            <NavArrow label="Next" onClick={() => go(1)}>
                                <ChevronRight className="size-4" />
                            </NavArrow>
                        </div>

                        {/* Stats */}
                        <div className="flex shrink-0 items-center justify-center gap-8 px-2 lg:flex-col lg:gap-6 lg:px-4">
                            <div className="text-center">
                                <p className="text-3xl font-semibold tracking-tight xl:text-4xl">10M+</p>
                                <p className="mt-1 max-w-[9rem] text-xs text-muted-foreground sm:text-sm">
                                    Likes delivered this month
                                </p>
                            </div>
                            <div className="text-center">
                                <p className="text-3xl font-semibold tracking-tight xl:text-4xl">98%</p>
                                <p className="mt-1 max-w-[9rem] text-xs text-muted-foreground sm:text-sm">
                                    Customer satisfaction rate
                                </p>
                            </div>
                        </div>

                        {/* Leave a review */}
                        <div className="flex w-full shrink-0 flex-col justify-center rounded-2xl border border-border bg-card p-6 text-center shadow-sm ring-1 ring-foreground/5 lg:w-64 xl:w-72">
                            <h3 className="text-base font-semibold xl:text-lg">Love {brand.name}? Leave a review</h3>
                            <p className="mt-2 text-xs text-muted-foreground sm:text-sm">
                                Tell other creators what worked for you. Your review helps the community pick the right tools.
                            </p>
                            <a href="#" className={cn('btn-primary mt-5 inline-flex self-center')}>
                                Leave a Review
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    );
}
