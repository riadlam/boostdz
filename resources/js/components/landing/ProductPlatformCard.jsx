import { ArrowRight, Star } from 'lucide-react';
import { cn } from '../../lib/cn';
import { HighlightText } from './HighlightText';

function StarRating({ rating = 4 }) {
    return (
        <div className="mt-3 flex items-center gap-1">
            {Array.from({ length: 5 }, (_, i) => (
                <Star
                    key={i}
                    className={cn('size-3.5', i < rating ? 'fill-amber-400 text-amber-400' : 'fill-muted text-muted')}
                />
            ))}
        </div>
    );
}

export function ProductPlatformCard({
    icon: Icon,
    title,
    description,
    highlight,
    cta,
    meta,
    rating = 4,
    href = '/auth/sign-up',
    children,
    className,
    compact = false,
}) {
    return (
        <article
            className={cn(
                'relative isolate flex flex-col overflow-hidden rounded-xl bg-card text-card-foreground shadow-lg ring-1 shadow-black/5 ring-foreground/6.5',
                compact ? 'min-h-0' : 'min-h-72 sm:min-h-96',
                className,
            )}
        >
            {children}
            <div
                className={cn(
                    'relative z-10 flex flex-1 flex-col',
                    compact ? 'max-w-md p-4 sm:p-5 md:p-6' : 'p-5 sm:p-6 md:p-8',
                )}
            >
                <div>
                    <div className="flex items-center gap-2">
                        {Icon ? <Icon className={cn('shrink-0', compact ? 'size-7' : 'size-8')} /> : null}
                    </div>
                    <h3 className={cn('font-semibold text-balance', compact ? 'mt-2 text-lg' : 'mt-3 text-xl')}>{title}</h3>
                    <p className={cn('text-sm text-muted-foreground', compact ? 'mt-1.5' : 'mt-2 max-w-sm')}>
                        <HighlightText
                            text={description}
                            highlight={highlight}
                            underlineClassName="underline decoration-current/30 underline-offset-2"
                        />
                    </p>
                    <StarRating rating={rating} />
                    <div className={cn(compact ? 'mt-4' : 'mt-auto pt-6')}>
                        <a
                            href={href}
                            className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                        >
                            {cta}
                            <ArrowRight className="size-3.5" />
                        </a>
                        <p className="mt-1 text-xs text-muted-foreground">{meta}</p>
                    </div>
                </div>
            </div>
        </article>
    );
}
