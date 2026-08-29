import { Zap, Shield, Headphones, Lock, Waves, RefreshCw, LayoutGrid, Activity, DollarSign } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { HighlightText } from './HighlightText';

const whyIcons = [Zap, Shield, Headphones, Lock, Waves, RefreshCw, LayoutGrid, Activity, DollarSign];

function GridLine({ className, direction = 'vertical' }) {
    const gradient =
        direction === 'vertical'
            ? 'linear-gradient(180deg, transparent 0%, rgba(255,255,255,0.05) 20%, rgba(255,255,255,0.05) 80%, transparent 100%)'
            : 'linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.05) 20%, rgba(255,255,255,0.05) 80%, transparent 100%)';

    return (
        <div
            className={`absolute [--line-color:rgba(255,255,255,0.05)] dark:[--line-color:rgba(0,0,0,0.05)] ${
                direction === 'vertical' ? 'h-full w-px' : 'h-px w-full'
            } ${className}`}
            style={{ background: gradient }}
        />
    );
}

function GridDot({ className, style }) {
    return (
        <div
            className={`absolute size-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/20 ring-4 ring-gray-950 dark:bg-black/20 dark:ring-gray-50 ${className ?? ''}`}
            style={style}
        />
    );
}

function WhyGridDecorations() {
    return (
        <div className="pointer-events-none absolute inset-0">
            <GridLine className="top-0 left-1/2 hidden md:block xl:hidden" direction="vertical" />
            {[20, 40, 60, 80].map((left) => (
                <GridDot key={left} className="top-0 hidden md:block xl:hidden" style={{ left: `${left}%` }} />
            ))}
            <GridLine className="top-0 left-1/3 hidden xl:block" direction="vertical" />
            <GridLine className="top-0 left-2/3 hidden xl:block" direction="vertical" />
            <GridLine className="top-1/3 left-0 hidden xl:block" direction="horizontal" />
            <GridLine className="top-2/3 left-0 hidden xl:block" direction="horizontal" />
            <GridDot className="top-1/3 left-1/3 hidden xl:block" />
            <GridDot className="top-1/3 left-2/3 hidden xl:block" />
            <GridDot className="top-2/3 left-1/3 hidden xl:block" />
            <GridDot className="top-2/3 left-2/3 hidden xl:block" />
        </div>
    );
}

export function WhySection() {
    const { t } = useTranslation('landing');
    const whyItems = t('why.items', { returnObjects: true });

    return (
        <div id="why" className="mx-auto max-w-6xl bg-background px-4 py-12 sm:py-16 md:px-6 md:py-20">
            <section
                id="features"
                className="relative z-10 overflow-hidden rounded-2xl bg-gray-950 p-4 ring-2 ring-white/20 ring-inset sm:p-6 md:px-10 md:py-8 xl:rounded-[28px] xl:px-12 xl:py-10 dark:bg-gray-50 dark:ring-black/15"
            >
                <div className="relative z-20 mx-auto max-w-5xl">
                    <div className="flex flex-col items-center gap-3 text-center sm:gap-4">
                        <h2 className="text-2xl leading-tight font-semibold tracking-tighter text-balance text-white sm:text-4xl lg:text-6xl dark:text-gray-900">
                            {t('why.title')}
                        </h2>
                        <p className="mx-auto mt-4 max-w-2xl text-sm text-gray-400 sm:text-sm dark:text-gray-500">
                            <HighlightText
                                text={t('why.subtitle')}
                                highlight={t('why.growHighlight')}
                                underlineClassName="font-medium text-gray-300 dark:text-gray-700"
                            />
                        </p>
                    </div>

                    <div className="relative mt-5 sm:mt-6 md:mt-12">
                        <div className="relative grid divide-y divide-white/8 border-t border-white/8 md:grid-cols-2 md:gap-6 md:divide-y-0 md:border-0 xl:grid-cols-3 xl:gap-x-6 xl:gap-y-14 dark:divide-black/8 dark:border-black/8">
                            {whyItems.map((item, i) => {
                                const Icon = whyIcons[i];
                                return (
                                    <div
                                        key={item.title}
                                        className="flex gap-3 py-4 md:flex-col md:items-center md:gap-0 md:p-4 md:text-center"
                                    >
                                        <div className="size-4.5 shrink-0 text-primary md:size-6 [&>svg]:size-full">
                                            <Icon />
                                        </div>
                                        <div className="md:mt-5">
                                            <div className="text-sm font-medium text-white md:text-base dark:text-gray-900">
                                                {item.title}
                                            </div>
                                            <div className="mt-1 w-full text-xs text-pretty text-gray-500 md:max-w-68 md:text-sm dark:text-gray-500">
                                                <HighlightText text={item.body} highlight={item.highlight} />
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        <WhyGridDecorations />
                    </div>
                </div>
            </section>
        </div>
    );
}
