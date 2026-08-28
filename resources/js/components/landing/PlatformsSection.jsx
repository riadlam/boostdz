import { Heart, MessageCircle, Repeat2, Share } from 'lucide-react';
import { XIcon } from '../Brand';
import { FacebookIcon, InstagramIcon, TikTokIcon, YouTubeIcon } from '../PlatformIcons';
import { platformCards, tweets } from '../../content/landing';
import { ProductPlatformCard } from './ProductPlatformCard';
import { DualTickerDecoration, PlatformCardDecoration, VerticalTicker } from './VerticalTicker';

function SingleTickerDecoration({ gradient, direction = 'bottom', startIndex = 1 }) {
    return (
        <PlatformCardDecoration gradient={gradient}>
            <div className="grid h-full grid-cols-1 overflow-hidden rounded-xl border border-foreground/10 opacity-65 shadow-xl ring-1 ring-foreground/5 dark:opacity-50">
                <VerticalTicker direction={direction} startIndex={startIndex} speed={3} count={10} />
            </div>
        </PlatformCardDecoration>
    );
}

function TweetCard({ tweet }) {
    return (
        <div className="w-[240px] shrink-0 rounded-2xl border border-foreground/10 bg-background p-3.5 shadow-md ring-1 ring-foreground/5">
            <div className="flex items-start justify-between gap-2">
                <div className="flex min-w-0 items-center gap-2">
                    <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold">
                        {tweet.user.charAt(0)}
                    </div>
                    <div className="min-w-0">
                        <div className="flex items-center gap-1">
                            <p className="truncate text-sm font-bold">{tweet.user}</p>
                            <svg viewBox="0 0 24 24" className="size-3.5 shrink-0 text-blue-500" aria-hidden fill="currentColor">
                                <path d="M22.5 12.5c0-1.58-.875-2.95-2.148-3.6.154-.435.238-.905.238-1.4 0-2.21-1.71-3.998-3.818-3.998-.47 0-.92.084-1.336.25C14.818 2.415 13.51 1.5 12 1.5s-2.816.917-3.437 2.25a3.7 3.7 0 0 0-1.336-.25c-2.11 0-3.818 1.79-3.818 4 0 .494.087.964.237 1.4-1.272.65-2.147 2.018-2.147 3.6 0 1.495.782 2.798 1.942 3.486-.02.17-.032.34-.032.514 0 2.21 1.708 4 3.818 4 .47 0 .92-.086 1.335-.25.62 1.334 1.926 2.25 3.437 2.25 1.512 0 2.818-.916 3.437-2.25.415.163.865.248 1.336.248 2.11 0 3.818-1.79 3.818-4 0-.174-.012-.344-.033-.513 1.158-.687 1.943-1.99 1.943-3.484zm-6.616-3.334l-4.334 6.5a.75.75 0 0 1-1.139.1l-2.216-2.242a.75.75 0 0 1 1.07-1.052l1.574 1.593 3.822-5.731a.75.75 0 1 1 1.223.832z" />
                            </svg>
                        </div>
                        <p className="text-xs text-muted-foreground">{tweet.handle}</p>
                    </div>
                </div>
                <XIcon className="size-3.5 shrink-0 text-foreground" />
            </div>
            <p className="mt-3 line-clamp-3 text-sm leading-relaxed">{tweet.text}</p>
            <p className="mt-3 text-xs text-muted-foreground">10:05 AM · Dec 19, 2020</p>
            <div className="mt-3 flex max-w-[180px] justify-between text-muted-foreground">
                <MessageCircle className="size-3.5" />
                <Repeat2 className="size-3.5" />
                <Heart className="size-3.5" />
                <Share className="size-3.5" />
            </div>
        </div>
    );
}

function TwitterDecoration() {
    const track = [...tweets, ...tweets];

    return (
        <PlatformCardDecoration gradient="bg-[radial-gradient(circle,#000_0%,#1da1f2_40%,transparent_100%)]">
            <div
                className="h-[220px] overflow-hidden rounded-xl border border-foreground/10 opacity-70 shadow-xl ring-1 ring-foreground/5 dark:opacity-55 md:h-[200px]"
                style={{
                    WebkitMaskImage:
                        'linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,1) 12%, rgba(0,0,0,1) 88%, rgba(0,0,0,0) 100%)',
                    maskImage:
                        'linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,1) 12%, rgba(0,0,0,1) 88%, rgba(0,0,0,0) 100%)',
                }}
            >
                <div className="ticker-track ticker-down flex flex-col gap-3 p-3" style={{ '--ticker-duration': '45s' }}>
                    {track.map((t, i) => (
                        <TweetCard key={`${t.handle}-${i}`} tweet={t} />
                    ))}
                </div>
            </div>
        </PlatformCardDecoration>
    );
}

export function PlatformsSection() {
    const ig = platformCards.find((p) => p.id === 'instagram');
    const tiktok = platformCards.find((p) => p.id === 'tiktok');
    const x = platformCards.find((p) => p.id === 'x');
    const youtube = platformCards.find((p) => p.id === 'youtube');
    const facebook = platformCards.find((p) => p.id === 'facebook');

    return (
        <section id="platforms" className="bg-background pt-8 pb-16 md:pt-12 md:pb-20 xl:pt-16">
            <div className="mx-auto max-w-6xl px-4 md:px-6">
                <div className="flex flex-col items-center gap-4 text-center">
                    <h2 className="text-4xl leading-none font-semibold tracking-tighter text-balance lg:text-6xl dark:text-white">
                        Real Hearts, Fans &amp; Plays, Delivered Fast
                    </h2>
                    <p className="mx-auto mt-4 max-w-2xl text-sm text-muted-foreground sm:text-sm">
                        Buy real likes, followers, and views for{' '}
                        <span className="font-medium text-foreground/70">
                            Instagram, TikTok, YouTube, X, and Facebook
                        </span>
                        . Pick your network, choose your service, start growing today.
                    </p>
                </div>

                <div className="mt-16 grid gap-2 md:grid-cols-2">
                    <ProductPlatformCard
                        icon={InstagramIcon}
                        title={ig.name}
                        description={ig.body}
                        highlight={ig.highlight}
                        cta={ig.cta}
                        meta={ig.meta}
                        rating={ig.rating}
                    >
                        <DualTickerDecoration leftStart={1} rightStart={11} />
                    </ProductPlatformCard>

                    <ProductPlatformCard
                        icon={TikTokIcon}
                        title={tiktok.name}
                        description={tiktok.body}
                        highlight={tiktok.highlight}
                        cta={tiktok.cta}
                        meta={tiktok.meta}
                        rating={tiktok.rating}
                    >
                        <PlatformCardDecoration gradient="bg-[radial-gradient(circle,#00f2ea_0%,#ff0050_45%,transparent_100%)]">
                            <div className="grid h-full grid-cols-2 overflow-hidden rounded-xl border border-foreground/10 opacity-65 shadow-xl ring-1 ring-foreground/5 dark:opacity-50">
                                <VerticalTicker direction="bottom" startIndex={3} speed={4} />
                                <VerticalTicker direction="top" startIndex={13} speed={3} />
                            </div>
                        </PlatformCardDecoration>
                    </ProductPlatformCard>

                    <ProductPlatformCard
                        icon={XIcon}
                        title={x.name}
                        description={x.body}
                        highlight={x.highlight}
                        cta={x.cta}
                        meta={x.meta}
                        rating={x.rating}
                        compact
                        className="md:col-span-2"
                    >
                        <TwitterDecoration />
                    </ProductPlatformCard>

                    <ProductPlatformCard
                        icon={YouTubeIcon}
                        title={youtube.name}
                        description={youtube.body}
                        highlight={youtube.highlight}
                        cta={youtube.cta}
                        meta={youtube.meta}
                        rating={youtube.rating}
                    >
                        <SingleTickerDecoration
                            gradient="bg-[radial-gradient(circle,#ff0000_0%,#cc0000_45%,transparent_100%)]"
                            startIndex={5}
                        />
                    </ProductPlatformCard>

                    <ProductPlatformCard
                        icon={FacebookIcon}
                        title={facebook.name}
                        description={facebook.body}
                        highlight={facebook.highlight}
                        cta={facebook.cta}
                        meta={facebook.meta}
                        rating={facebook.rating}
                    >
                        <SingleTickerDecoration
                            gradient="bg-[radial-gradient(circle,#1877f2_0%,#0d65d9_45%,transparent_100%)]"
                            direction="top"
                            startIndex={8}
                        />
                    </ProductPlatformCard>
                </div>
            </div>
        </section>
    );
}
