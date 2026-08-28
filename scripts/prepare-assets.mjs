import { cpSync, existsSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(fileURLToPath(new URL('.', import.meta.url)), '..');
const sourceImages = join(root, 'resources/images');
const targetImages = join(root, 'public/images');

if (! existsSync(sourceImages)) {
    process.exit(0);
}

mkdirSync(targetImages, { recursive: true });
cpSync(sourceImages, targetImages, { recursive: true });
