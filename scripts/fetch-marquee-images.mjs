/**
 * Downloads MENA-relevant royalty-free images from Unsplash and saves
 * platform-specific crops under public/assets/marquee/{platform}/.
 *
 * Usage: node scripts/fetch-marquee-images.mjs
 */
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(fileURLToPath(new URL('.', import.meta.url)), '..');
const outRoot = join(root, 'public', 'assets', 'marquee');

const BASE_PHOTOS = [
    'photo-1578662996442-48f60103fc96', // Arabic coffee
    'photo-1555881400-74d7acaacd8b', // Moroccan architecture
    'photo-1518548419970-58e3b4079ab2', // Sahara dunes
    'photo-1567306301408-9b74779a11af', // Fresh produce market
    'photo-1547036967-23d11aacaee0', // Street scene
];

/** Vary focal-point crops so the same base photo looks distinct in the ticker */
const CROP_VARIANTS = [
    '',
    '&fp-x=0.2&fp-y=0.3&fp-z=1.2',
    '&fp-x=0.7&fp-y=0.4&fp-z=1.1',
    '&fp-x=0.5&fp-y=0.8&fp-z=1.3',
    '&fp-x=0.35&fp-y=0.55&fp-z=1.15',
    '&fp-x=0.8&fp-y=0.2&fp-z=1.25',
    '&fp-x=0.15&fp-y=0.65&fp-z=1.2',
    '&fp-x=0.6&fp-y=0.75&fp-z=1.1',
    '&fp-x=0.45&fp-y=0.25&fp-z=1.3',
    '&fp-x=0.25&fp-y=0.5&fp-z=1.2',
];

const PLATFORMS = {
    instagram: { w: 512, h: 512 },
    tiktok: { w: 360, h: 640 },
    youtube: { w: 640, h: 360 },
    facebook: { w: 512, h: 640 },
};

function unsplashUrl(photoId, w, h, crop = '') {
    return `https://images.unsplash.com/${photoId}?auto=format&fit=crop&crop=focalpoint${crop}&w=${w}&h=${h}&q=80&fm=webp`;
}

async function download(url) {
    const response = await fetch(url, {
        headers: { 'User-Agent': 'BoostDZ-Asset-Script/1.0' },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return Buffer.from(await response.arrayBuffer());
}

async function main() {
    for (const platform of Object.keys(PLATFORMS)) {
        const { w, h } = PLATFORMS[platform];
        const dir = join(outRoot, platform);
        mkdirSync(dir, { recursive: true });

        console.log(`\n${platform} (${w}x${h}):`);

        for (let i = 0; i < 10; i++) {
            const photoId = BASE_PHOTOS[i % BASE_PHOTOS.length];
            const crop = CROP_VARIANTS[i];
            const url = unsplashUrl(photoId, w, h, crop);
            const file = join(dir, `${i + 1}.webp`);

            process.stdout.write(`  ${i + 1}.webp <- ${photoId} ... `);
            const data = await download(url);
            writeFileSync(file, data);
            console.log(`ok (${data.length} bytes)`);
        }
    }

    console.log('\nDone — 40 marquee images written.');
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
