/**
 * Génère les icônes PWA PNG depuis le logo SVG Bia Namur.
 *
 * Inputs : assets-logo/logo-mark-512.svg, assets-logo/logo-maskable-512.svg
 * Outputs : public/pwa-icons/{icon-192,icon-512,icon-maskable-512,apple-touch-icon}.png
 *           public/favicon-32.png
 *
 * Usage : node scripts/generate-pwa-icons.mjs
 */

import sharp from 'sharp';
import { readFileSync, mkdirSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');

const LOGO_MARK = resolve(ROOT, 'assets-logo/logo-mark-512.svg');
const LOGO_MASKABLE = resolve(ROOT, 'assets-logo/logo-maskable-512.svg');
const OUT_DIR = resolve(ROOT, 'public/pwa-icons');
const PUBLIC_DIR = resolve(ROOT, 'public');

const BIA_CREAM = '#F5EDDC';

if (!existsSync(OUT_DIR)) mkdirSync(OUT_DIR, { recursive: true });

const targets = [
    { src: LOGO_MARK, out: `${OUT_DIR}/icon-192.png`, size: 192, bg: BIA_CREAM },
    { src: LOGO_MARK, out: `${OUT_DIR}/icon-512.png`, size: 512, bg: BIA_CREAM },
    { src: LOGO_MASKABLE, out: `${OUT_DIR}/icon-maskable-512.png`, size: 512, bg: '#C77F2C' },
    { src: LOGO_MARK, out: `${OUT_DIR}/apple-touch-icon.png`, size: 180, bg: BIA_CREAM },
    { src: LOGO_MARK, out: `${PUBLIC_DIR}/favicon-32.png`, size: 32, bg: BIA_CREAM },
    { src: LOGO_MARK, out: `${PUBLIC_DIR}/favicon-16.png`, size: 16, bg: BIA_CREAM },
];

console.log('Génération icônes PWA Bia Namur...\n');

for (const { src, out, size, bg } of targets) {
    const svg = readFileSync(src);
    await sharp(svg, { density: 384 })
        .resize(size, size, {
            fit: 'contain',
            background: bg,
        })
        .png({ compressionLevel: 9 })
        .toFile(out);
    console.log(`  ✓ ${out.split(/[\/\\]/).slice(-2).join('/')} (${size}×${size})`);
}

console.log('\nÀ l\'aise. Les icônes sont prêtes.');
