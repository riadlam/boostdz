export const FACEBOOK_REACTION_OPTIONS = [
    { value: 'any', label: 'Any', icon: null },
    { value: 'like', label: 'Like', icon: '/images/reactions/facebook/like.svg' },
    { value: 'love', label: 'Love', icon: '/images/reactions/facebook/love.svg' },
    { value: 'haha', label: 'Haha', icon: '/images/reactions/facebook/haha.svg' },
    { value: 'wow', label: 'Wow', icon: '/images/reactions/facebook/wow.svg' },
    { value: 'sad', label: 'Sad', icon: '/images/reactions/facebook/sad.svg' },
    { value: 'angry', label: 'Angry', icon: '/images/reactions/facebook/angry.svg' },
];

export function buildFacebookReactionOptions(availableTypes) {
    const types = new Set((availableTypes || []).filter(Boolean));
    if (types.size === 0) {
        return [];
    }

    return FACEBOOK_REACTION_OPTIONS.filter((option) => option.value === 'any' || types.has(option.value));
}

export function facebookReactionLabel(value) {
    return FACEBOOK_REACTION_OPTIONS.find((option) => option.value === value)?.label || value;
}
