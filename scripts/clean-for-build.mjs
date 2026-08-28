import { existsSync, rmSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(fileURLToPath(new URL('.', import.meta.url)), '..');

const removeTargets = [
    'public/build',
    'public/hot',
    'public/images',
    'public/js/filament',
    'public/css/filament',
    'public/fonts/filament',
];

for (const relativePath of removeTargets) {
    const absolutePath = join(root, relativePath);

    if (existsSync(absolutePath)) {
        rmSync(absolutePath, { recursive: true, force: true });
    }
}

await import('./prepare-assets.mjs');
