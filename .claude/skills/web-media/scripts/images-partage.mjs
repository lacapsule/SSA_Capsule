// SPDX-License-Identifier: GPL-3.0-or-later
// Fabrique l'image de partage et les icônes du site depuis leurs sources, par le vrai rendu du navigateur.
// Usage : node images-partage.mjs [--og <page.html>] [--logo <logo.svg>] [--sortie public]
//   --og   : page HTML de 1200 × 630 (textes et visuels réels du site, styles du site) → <sortie>/og.jpg
//   --logo : logo SVG carré (ou centré dans un carré) → favicon.svg, apple-touch-icon.png (180),
//            icon-192.png, icon-512.png ; le fond est celui du SVG (prévoir un fond plein pour iOS).
// Les images produites sont des fichiers générés : on modifie la source (page ou logo) et on relance.
// Dépendance : playwright-core du projet (npm ci) ; navigateur : CHROME_PATH ou celui de Playwright.
import { copyFileSync, existsSync, mkdirSync, readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { lancerNavigateur } from '../../livrables/lib/document.mjs';

const require = createRequire(process.cwd() + '/');
let chromium;
try { ({ chromium } = require('playwright-core')); } catch {
  console.error('playwright-core absent : lancer « make deps » (ou « npm ci ») à la racine du projet.');
  process.exit(2);
}

const args = process.argv.slice(2);
const option = (nom) => { const i = args.indexOf(nom); return i >= 0 ? args[i + 1] : null; };
const og = option('--og');
const logo = option('--logo');
const sortie = option('--sortie') ?? 'public';
if (!og && !logo) {
  console.error('Usage : node images-partage.mjs [--og <page.html>] [--logo <logo.svg>] [--sortie public]');
  process.exit(2);
}
for (const f of [og, logo].filter(Boolean)) {
  if (!existsSync(f)) { console.error(`Source introuvable : ${f}`); process.exit(2); }
}
mkdirSync(sortie, { recursive: true });

const browser = await lancerNavigateur();
const produits = [];

if (og) {
  const page = await browser.newPage({ viewport: { width: 1200, height: 630 } });
  await page.goto(pathToFileURL(path.resolve(og)).href, { waitUntil: 'networkidle' });
  const deborde = await page.evaluate(() => document.documentElement.scrollWidth > 1200 || document.documentElement.scrollHeight > 630);
  if (deborde) console.log(`avertissement ${og} déborde de 1200 × 630 : le surplus est coupé.`);
  const cible = path.join(sortie, 'og.jpg');
  await page.screenshot({ path: cible, type: 'jpeg', quality: 85, clip: { x: 0, y: 0, width: 1200, height: 630 } });
  produits.push(cible);
  await page.close();
}

if (logo) {
  const svg = readFileSync(logo, 'utf8');
  if (!/<svg[\s>]/.test(svg)) { console.error(`${logo} n'est pas un SVG.`); process.exit(2); }
  copyFileSync(logo, path.join(sortie, 'favicon.svg'));
  produits.push(path.join(sortie, 'favicon.svg'));
  for (const [nom, taille] of [['apple-touch-icon.png', 180], ['icon-192.png', 192], ['icon-512.png', 512]]) {
    const page = await browser.newPage({ viewport: { width: taille, height: taille } });
    await page.setContent(`<!DOCTYPE html><html><body style="margin:0"><img src="${pathToFileURL(path.resolve(logo)).href}" width="${taille}" height="${taille}" style="display:block" alt=""></body></html>`);
    await page.waitForLoadState('load');
    const cible = path.join(sortie, nom);
    await page.screenshot({ path: cible, type: 'png', omitBackground: true });
    produits.push(cible);
    await page.close();
  }
}

await browser.close();
for (const p of produits) console.log(`écrit ${p}`);
if (logo) console.log('À poser dans le gabarit : <link rel="icon" href="/favicon.svg" type="image/svg+xml"> et <link rel="apple-touch-icon" href="/apple-touch-icon.png">.');
