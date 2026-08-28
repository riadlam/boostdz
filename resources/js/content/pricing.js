import { orderCatalog } from './orderCatalog';

const platformPrefix = {
    instagram: 'ig',
    tiktok: 'tt',
    youtube: 'yt',
    facebook: 'fb',
    x: 'x',
};

function pickDefaultPackage(packages) {
    return packages.find((p) => p.amount >= 1000) || packages[Math.min(1, packages.length - 1)] || packages[0];
}

export const pricingPlatforms = [
    { id: 'all', label: 'All platforms' },
    ...Object.values(orderCatalog).map((p) => ({ id: p.id, label: p.name })),
];

export function buildPricingRows() {
    return Object.values(orderCatalog).flatMap((platform) =>
        platform.products.map((product) => {
            const starter = product.packages[0];
            const featured = pickDefaultPackage(product.packages);
            const per1k = (starter.price / starter.amount) * 1000;
            const prefix = platformPrefix[platform.id] || platform.id;

            return {
                id: `${platform.id}-${product.id}`,
                platformId: platform.id,
                platformName: platform.name,
                productId: product.id,
                serviceName: product.name,
                label: `${platform.name} ${product.name}`,
                min: product.min,
                max: product.max,
                startingPrice: starter.price,
                startingAmount: starter.amount,
                pricePer1k: per1k,
                orderHref: `/dashboard/orders/create?product=${prefix}-${product.id}&amount=${featured.amount}`,
            };
        }),
    );
}

export const pricingRows = buildPricingRows();
