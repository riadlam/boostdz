import i18n from '../i18n';

const REACTION_VALUES = ['any', 'like', 'love', 'haha', 'wow', 'sad', 'angry'];

const REACTION_ICONS = {
    like: '/images/reactions/facebook/like.svg',
    love: '/images/reactions/facebook/love.svg',
    haha: '/images/reactions/facebook/haha.svg',
    wow: '/images/reactions/facebook/wow.svg',
    sad: '/images/reactions/facebook/sad.svg',
    angry: '/images/reactions/facebook/angry.svg',
};

export function buildFacebookReactionOptions(availableTypes) {
    const types = new Set((availableTypes || []).filter(Boolean));
    if (types.size === 0) {
        return [];
    }

    return REACTION_VALUES.filter((value) => value === 'any' || types.has(value)).map((value) => ({
        value,
        label: facebookReactionLabel(value),
        icon: value === 'any' ? null : REACTION_ICONS[value],
    }));
}

export function facebookReactionLabel(value) {
    if (!value) return '';
    return i18n.t(`orders:reactions.${value}`, { defaultValue: String(value) });
}
