import i18n from '../i18n';
import { formatDzd } from './formatMoney';

export function formatPlatformCardMeta({ starting_price_dzd, review_count_display }) {
    const price = formatDzd(starting_price_dzd);
    const startingAt = i18n.t('common:table.startingAt');
    const reviewsLabel = i18n.t('landing:platforms.reviewsLabel');

    return i18n.t('landing:platforms.metaLine', {
        startingAt,
        price,
        reviews: review_count_display,
        reviewsLabel,
    });
}
