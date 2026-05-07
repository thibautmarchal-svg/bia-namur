/**
 * Optimise les photos par defaut Bia Namur (Wikimedia Commons sources).
 *
 * Pour chaque .jpg dans public/images/defaults/places/ :
 *  - resize a 1600px max (preserve aspect ratio)
 *  - export 2 variantes WebP (1600 + 800) + 1 JPG fallback (1600)
 *  - garde l'original .jpg comme backup (.jpg.original)
 *
 * Usage : node scripts/optimize-photos.mjs
 */

import sharp from 'sharp';
import { readdirSync, statSync, renameSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve, join, basename, extname } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');

const DIRS = [
    'public/images/defaults/places',
    'public/images/defaults/stories',
];

console.log('Optimisation photos Bia Namur...\n');

for (const relDir of DIRS) {
    const dir = resolve(ROOT, relDir);
    if (!existsSync(dir)) continue;

    const files = readdirSync(dir).filter((f) => /\.(jpe?g|png)$/i.test(f) && !f.endsWith('.original.jpg'));

    for (const file of files) {
        const inputPath = join(dir, file);
        const slug = basename(file, extname(file));
        const sizeBefore = statSync(inputPath).size;

        // Sauve l'original avant optimisation (pour rollback / re-export possible)
        const backupPath = join(dir, `${slug}.original${extname(file)}`);
        if (!existsSync(backupPath)) {
            const buffer = sharp(inputPath);
            await buffer.toFile(backupPath + '.tmp');
            renameSync(backupPath + '.tmp', backupPath);
        }

        // Versions web
        await sharp(inputPath)
            .resize({ width: 1600, height: 1600, fit: 'inside', withoutEnlargement: true })
            .jpeg({ quality: 82, mozjpeg: true })
            .toFile(join(dir, `${slug}-1600.jpg.tmp`));
        renameSync(join(dir, `${slug}-1600.jpg.tmp`), join(dir, `${slug}-1600.jpg`));

        await sharp(inputPath)
            .resize({ width: 1600, height: 1600, fit: 'inside', withoutEnlargement: true })
            .webp({ quality: 78 })
            .toFile(join(dir, `${slug}-1600.webp`));

        await sharp(inputPath)
            .resize({ width: 800, height: 800, fit: 'inside', withoutEnlargement: true })
            .webp({ quality: 76 })
            .toFile(join(dir, `${slug}-800.webp`));

        // Remplace le JPG d'origine par la version 1600 jpg pour pas avoir 2 versions
        renameSync(join(dir, `${slug}-1600.jpg`), inputPath);

        const sizeAfter = statSync(inputPath).size;
        console.log(
            `  ✓ ${relDir}/${file} : ${(sizeBefore / 1024).toFixed(0)} KB → ${(sizeAfter / 1024).toFixed(0)} KB ` +
            `(+ -800.webp + -1600.webp)`
        );
    }
}

console.log('\nÀ l\'aise.');
