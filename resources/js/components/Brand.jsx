import { cn } from '../lib/cn';
import logoUrl from '../../images/logo.png';

export const LOGO_SRC = logoUrl;

export function BrandLogo({
    className = 'h-14',
    href = '/',
    showName = true,
    nameClassName = 'text-lg sm:text-xl',
}) {
    return (
        <a href={href} className="inline-flex shrink-0 items-center gap-2.5">
            <img
                src={LOGO_SRC}
                alt=""
                aria-hidden={showName}
                className={cn('w-auto max-h-14 shrink-0 object-contain', className)}
            />
            {showName ? (
                <span className={cn('flex items-baseline gap-0.5 leading-none', nameClassName)}>
                    <span className="bg-gradient-to-r from-emerald-500 via-primary to-primary bg-clip-text font-extrabold tracking-tight text-transparent">
                        BOOSTDZ
                    </span>
                    <span className="text-[0.5em] font-semibold text-muted-foreground">®</span>
                </span>
            ) : (
                <span className="sr-only">BOOSTDZ</span>
            )}
        </a>
    );
}

export function PressLogos() {
    const names = ['The New York Times', "Men's Journal", 'Forbes', 'Wired'];
    return (
        <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:gap-x-8 sm:gap-y-3">
            {names.map((n) => (
                <span key={n} className="text-xs font-semibold tracking-tight opacity-80 sm:text-sm md:text-base">
                    {n}
                </span>
            ))}
        </div>
    );
}

export function IgIcon({ className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" className={className} fill="currentColor" aria-hidden>
            <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm5 5.5A4.5 4.5 0 1 0 16.5 12 4.5 4.5 0 0 0 12 7.5zm0 7.4A2.9 2.9 0 1 1 14.9 12 2.9 2.9 0 0 1 12 14.9zm5.75-8.65a1.05 1.05 0 1 0 1.05 1.05 1.05 1.05 0 0 0-1.05-1.05z" />
        </svg>
    );
}

export function TikTokIcon({ className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" className={className} fill="currentColor" aria-hidden>
            <path d="M14.5 3c.4 2.6 1.8 4.3 4.5 4.6v2.6c-1.5 0-2.9-.5-4.4-1.4v6.6c0 3.6-2.8 6.4-6.6 6.4S1.4 19 1.4 15.4 4.2 9 8 9c.4 0 .8 0 1.2.1v2.8A3.8 3.8 0 0 0 8 11.7a3.7 3.7 0 1 0 3.7 3.7V3h2.8z" />
        </svg>
    );
}

export function XIcon({ className = 'h-4 w-4' }) {
    return (
        <svg viewBox="0 0 24 24" className={className} fill="currentColor" aria-hidden>
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
        </svg>
    );
}
