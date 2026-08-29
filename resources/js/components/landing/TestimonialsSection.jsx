import { useCallback, useEffect, useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight, Play } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { contentApi } from '../../lib/api';
import { cn } from '../../lib/cn';

function TestimonialCard({ item, offset, isActive, onClick, tapToPlay }) {
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
                    {tapToPlay}
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

function mapFallbackItems(items) {
    if (!Array.isArray(items)) {
        return [];
    }

    return items.map((item, index) => ({
        id: index,
        name: item.name,
        quote: item.quote,
        role: item.role,
        avatar: item.avatar,
    }));
}

export function TestimonialsSection() {
    const { t, i18n } = useTranslation('landing');
    const fallbackItems = useMemo(
        () => mapFallbackItems(t('testimonials.items', { returnObjects: true })),
        [t],
    );

    const [content, setContent] = useState(null);

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const response = await contentApi.testimonials();
                if (!cancelled) {
                    setContent(response);
                }
            } catch {
                if (!cancelled) {
                    setContent(null);
                }
            }
        }

        load();

        return () => {
            cancelled = true;
        };
    }, [i18n.language]);

    if (content?.section_enabled === false) {
        return null;
    }

    const testimonials =
        Array.isArray(content?.testimonials) && content.testimonials.length > 0
            ? content.testimonials
            : fallbackItems;

    if (testimonials.length === 0) {
        return null;
    }

    const stats = content?.stats;
    const leaveReview = content?.leave_review;
    const showStats = stats?.show !== false;
    const likesDelivered = stats?.likes_delivered || '10M+';
    const satisfactionRate = stats?.satisfaction_rate || '98%';
    const showLeaveReview = leaveReview?.show !== false;
    const leaveReviewUrl = leaveReview?.url;

    return <TestimonialsCarousel
        testimonials={testimonials}
        showStats={showStats}
        likesDelivered={likesDelivered}
        satisfactionRate={satisfactionRate}
        showLeaveReview={showLeaveReview}
        leaveReviewUrl={leaveReviewUrl}
    />;
}

function TestimonialsCarousel({
    testimonials,
    showStats,
    likesDelivered,
    satisfactionRate,
    showLeaveReview,
    leaveReviewUrl,
}) {
    const { t } = useTranslation('landing');
    const [active, setActive] = useState(0);
    const total = testimonials.length;

    useEffect(() => {
        setActive(0);
    }, [total]);

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

            <section id="testimonials" className="relative pt-2 pb-12 sm:pt-4 sm:pb-16 md:pb-24">
                <div className="mx-auto max-w-6xl px-4 md:px-6">
                    <div className="mb-8 text-center sm:mb-10 md:mb-14">
                        <h2 className="text-2xl font-semibold tracking-tighter text-balance sm:text-3xl lg:text-5xl dark:text-white">
                            {t('testimonials.title')}
                        </h2>
                        <p className="mx-auto mt-4 max-w-2xl text-sm text-muted-foreground sm:text-sm">
                            {t('testimonials.subtitle')}
                        </p>
                    </div>

                    <div className="flex flex-col items-stretch gap-6 sm:gap-8 lg:flex-row lg:items-center lg:gap-6 xl:gap-8">
                        <div className="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                            <NavArrow label="Previous testimonial" onClick={() => go(-1)}>
                                <ChevronLeft className="size-4" />
                            </NavArrow>

                            <div className="relative h-64 w-full min-w-0 flex-1 sm:h-72 md:h-80" style={{ contain: 'layout' }}>
                                {visible.map(({ offset, index, item }) => (
                                    <TestimonialCard
                                        key={`${item.id ?? index}-${offset}`}
                                        item={item}
                                        offset={offset}
                                        isActive={offset === 0}
                                        onClick={() => (offset === 0 ? go(1) : setActive(index))}
                                        tapToPlay={t('testimonials.tapToPlay')}
                                    />
                                ))}
                            </div>

                            <NavArrow label="Next testimonial" onClick={() => go(1)}>
                                <ChevronRight className="size-4" />
                            </NavArrow>
                        </div>

                        {showStats ? (
                            <div className="flex shrink-0 flex-col items-center justify-center gap-5 px-2 sm:flex-row sm:gap-8 lg:flex-col lg:gap-6 lg:px-4">
                                <div className="text-center">
                                    <p className="text-3xl font-semibold tracking-tight xl:text-4xl">{likesDelivered}</p>
                                    <p className="mt-1 max-w-[9rem] text-xs text-muted-foreground sm:text-sm">
                                        {t('testimonials.likesDelivered')}
                                    </p>
                                </div>
                                <div className="text-center">
                                    <p className="text-3xl font-semibold tracking-tight xl:text-4xl">{satisfactionRate}</p>
                                    <p className="mt-1 max-w-[9rem] text-xs text-muted-foreground sm:text-sm">
                                        {t('testimonials.satisfaction')}
                                    </p>
                                </div>
                            </div>
                        ) : null}

                        {showLeaveReview ? (
                            <div className="flex w-full shrink-0 flex-col justify-center rounded-2xl border border-border bg-card p-6 text-center shadow-sm ring-1 ring-foreground/5 lg:w-64 xl:w-72">
                                <h3 className="text-base font-semibold xl:text-lg">{t('testimonials.leaveReviewTitle')}</h3>
                                <p className="mt-2 text-xs text-muted-foreground sm:text-sm">
                                    {t('testimonials.leaveReviewBody')}
                                </p>
                                {leaveReviewUrl ? (
                                    <a
                                        href={leaveReviewUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className={cn('btn-primary mt-5 inline-flex self-center')}
                                    >
                                        {t('testimonials.leaveReviewCta')}
                                    </a>
                                ) : (
                                    <span className={cn('btn-primary mt-5 inline-flex self-center opacity-60')}>
                                        {t('testimonials.leaveReviewCta')}
                                    </span>
                                )}
                            </div>
                        ) : null}
                    </div>
                </div>
            </section>
        </div>
    );
}
