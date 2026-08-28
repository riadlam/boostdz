import { createElement } from 'react';
import { Globe2 } from 'lucide-react';
import { XIcon } from '../components/Brand';
import {
    FacebookIcon,
    InstagramIcon,
    LinkedInIcon,
    SpotifyIcon,
    TelegramIcon,
    ThreadsIcon,
    TikTokIcon,
    YouTubeIcon,
} from '../components/PlatformIcons';

export function GenericPlatformIcon({ className = 'size-4', ...props }) {
    return createElement(Globe2, { className, 'aria-hidden': true, ...props });
}

export const platformIcons = {
    instagram: InstagramIcon,
    tiktok: TikTokIcon,
    youtube: YouTubeIcon,
    facebook: FacebookIcon,
    twitter: XIcon,
    x: XIcon,
    threads: ThreadsIcon,
    telegram: TelegramIcon,
    spotify: SpotifyIcon,
    linkedin: LinkedInIcon,
};

export function getPlatformIcon(slug) {
    return platformIcons[slug] || GenericPlatformIcon;
}

export function isOtherCatalogEntry(item) {
    if (!item) return false;
    return (item.slug || '').toLowerCase() === 'other';
}

export function isHiddenFacebookReactionCategory(item) {
    if (!item) return false;
    const slug = (item.slug || '').toLowerCase();
    return /^reaction_(love|wow|haha|sad|angry)$/.test(slug);
}

export function filterCatalogEntries(items) {
    return (items || []).filter((item) => !isOtherCatalogEntry(item) && !isHiddenFacebookReactionCategory(item));
}
