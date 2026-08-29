import i18n from '../i18n';
import { catalogApi } from './api';

export const UNIT_QUANTITY = 1000;

export const DASHBOARD_PRESET_CONFIG = [
    {
        id: 'ig-likes',
        platform: 'instagram',
        categorySlug: 'likes',
        titleKey: 'presets.igLikes',
        accent: 'rose',
    },
    {
        id: 'tt-views',
        platform: 'tiktok',
        categorySlug: 'views',
        titleKey: 'presets.ttViews',
        accent: 'neutral',
    },
    {
        id: 'yt-views',
        platform: 'youtube',
        categorySlug: 'views',
        titleKey: 'presets.ytViews',
        accent: 'red',
    },
];

function presetTitle(config) {
    return i18n.t(config.titleKey, { ns: 'dashboard' });
}

export function buildCreateOrderHref({ platformSlug, categorySlug, serviceId }) {
    const params = new URLSearchParams();
    if (platformSlug) params.set('platform', platformSlug);
    if (categorySlug) params.set('category', categorySlug);
    if (serviceId) params.set('service', String(serviceId));
    const qs = params.toString();

    return `/dashboard/orders/create${qs ? `?${qs}` : ''}`;
}

export function mapStorefrontItem(item) {
    const platform = item.platform || {};
    const category = item.category || {};
    const service = item.service || {};
    const startingAmount = Number(item.min) > 0 ? Number(item.min) : UNIT_QUANTITY;

    return {
        id: `${platform.slug}-${category.slug}`,
        platformId: platform.slug,
        platformSlug: platform.slug,
        categorySlug: category.slug,
        platformName: platform.name,
        categoryName: category.name,
        label: `${platform.name} ${category.name}`,
        min: Number(item.min) || 0,
        max: Number(item.max) || 0,
        pricePer1k: Number(item.price_per_1k_dzd) || 0,
        startingPrice: Number(item.starting_price_dzd) || 0,
        startingAmount,
        orderHref: buildCreateOrderHref({
            platformSlug: platform.slug,
            categorySlug: category.slug,
            serviceId: service.id,
        }),
        serviceId: service.id,
    };
}

export async function loadStorefrontItems() {
    const response = await catalogApi.storefront();
    const items = Array.isArray(response?.items) ? response.items : [];

    return items.map(mapStorefrontItem);
}

export async function loadCatalogPricingRows() {
    return loadStorefrontItems();
}

export async function loadDashboardPresets() {
    const items = await loadStorefrontItems();

    return DASHBOARD_PRESET_CONFIG.map((config) => {
        const storefrontItem = items.find(
            (item) => item.platformId === config.platform && item.id === `${config.platform}-${config.categorySlug}`,
        );

        if (!storefrontItem) {
            return null;
        }

        return {
            id: config.id,
            title: presetTitle(config),
            subtitle: storefrontItem.categoryName,
            price: storefrontItem.pricePer1k,
            href: storefrontItem.orderHref,
            accent: config.accent,
            platform: config.platform,
            serviceId: storefrontItem.serviceId,
        };
    }).filter(Boolean);
}
