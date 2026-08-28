import { createElement } from 'react';
import {
    Bookmark,
    Eye,
    Heart,
    Layers,
    MessageCircle,
    Package,
    Share2,
    Target,
    Users,
} from 'lucide-react';

const REACTION_ICON_SRC = {
    reaction_love: '/images/reactions/facebook/love.svg',
    reaction_haha: '/images/reactions/facebook/haha.svg',
    reaction_wow: '/images/reactions/facebook/wow.svg',
    reaction_sad: '/images/reactions/facebook/sad.svg',
    reaction_angry: '/images/reactions/facebook/angry.svg',
    reaction_heart: '/images/reactions/telegram/heart.svg',
    reaction_thumbs_up: '/images/reactions/telegram/thumbs_up.svg',
    reaction_thumbs_down: '/images/reactions/telegram/thumbs_down.svg',
    reaction_fire: '/images/reactions/telegram/fire.svg',
    reaction_party: '/images/reactions/telegram/party.svg',
    reaction_starstruck: '/images/reactions/telegram/starstruck.svg',
    reaction_scream: '/images/reactions/telegram/scream.svg',
    reaction_grin: '/images/reactions/telegram/grin.svg',
    reaction_cry: '/images/reactions/telegram/cry.svg',
    reaction_poo: '/images/reactions/telegram/poo.svg',
    reaction_vomit: '/images/reactions/telegram/vomit.svg',
    reactions: '/images/reactions/telegram/reactions.svg',
};

function reactionIcon(src) {
    return function ReactionIcon({ className = 'size-4', ...props }) {
        return createElement('img', {
            src,
            className: `${className} object-contain`.trim(),
            alt: '',
            'aria-hidden': true,
            ...props,
        });
    };
}

const reactionIcons = Object.fromEntries(
    Object.entries(REACTION_ICON_SRC).map(([slug, src]) => [slug, reactionIcon(src)]),
);

export function getCategoryIcon(categorySlug) {
    if (categorySlug && reactionIcons[categorySlug]) {
        return reactionIcons[categorySlug];
    }

    if (categorySlug?.includes('follow') || categorySlug === 'members' || categorySlug === 'friends') {
        return Users;
    }
    if (categorySlug?.includes('like')) {
        return Heart;
    }
    if (categorySlug?.includes('view') || categorySlug === 'plays') {
        return Eye;
    }
    if (categorySlug?.includes('comment')) {
        return MessageCircle;
    }
    if (categorySlug?.includes('share')) {
        return Share2;
    }
    if (categorySlug === 'stories' || categorySlug?.includes('story')) {
        return Layers;
    }
    if (categorySlug === 'reach') {
        return Target;
    }
    if (categorySlug === 'saves' || categorySlug?.includes('save')) {
        return Bookmark;
    }
    if (categorySlug === 'impressions' || categorySlug === 'engagement') {
        return Eye;
    }

    return Layers;
}
