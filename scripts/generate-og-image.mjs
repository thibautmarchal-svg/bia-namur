// Bia Namur — Genere l'image Open Graph (1200x630) depuis assets-logo/og-image.svg.
//
// Sortie : public/images/og/bia-namur-default.jpg + .png.
// La JPG est utilisee comme fallback OG quand un contenu n'a pas sa propre photo.
// Pour regenerer apres modification du SVG : node scripts/generate-og-image.mjs

import sharp from 'sharp';
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');

const svgPath = resolve(root, 'assets-logo/og-image.svg');
const svgBuffer = readFileSync(svgPath);

const targets = [
    {
        out: 'public/images/og/bia-namur-default.jpg',
        format: 'jpeg',
        options: { quality: 88, progressive: true, mozjpeg: true },
    },
    {
        out: 'public/images/og/bia-namur-default.png',
        format: 'png',
        options: { compressionLevel: 9 },
    },
];

for (const t of targets) {
    const dest = resolve(root, t.out);
    let pipeline = sharp(svgBuffer, { density: 192 }).resize(1200, 630, {
        fit: 'cover',
        position: 'center',
    });

    if (t.format === 'jpeg') {
        pipeline = pipeline.jpeg(t.options);
    } else {
        pipeline = pipeline.png(t.options);
    }

    await pipeline.toFile(dest);
    console.log(`OK ${t.out}`);
}

console.log('Image OG generee. URL prod : https://bianamur.be/images/og/bia-namur-default.jpg');
