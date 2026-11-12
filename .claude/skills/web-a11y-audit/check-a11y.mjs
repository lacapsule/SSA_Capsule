// SPDX-License-Identifier: GPL-3.0-or-later
// Scan axe-core (wcag2a, wcag2aa, wcag22aa) de chaque page servie, dans chaque thème déclaré.
// Usage : node check-a11y.mjs <url de base> [chemin…]
//   sans chemin : toutes les URL de dist/sitemap.xml, plus une adresse inexistante (page 404).
// Bloquant : impacts serious et critical (code 1). moderate et minor : listés, non bloquants.
// Thèmes : si la page porte data-theme sur <html>, elle est scannée en clair et en sombre.
// Dépendances : playwright-core et axe-core du projet (npm ci) ; navigateur : CHROME_PATH ou celui de Playwright.
import { readFileSync, existsSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(process.cwd() + '/');
let chromium;
let axeSource;
try {
  ({ chromium } = require('playwright-core'));
  axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
} catch {
  console.error('playwright-core ou axe-core absent : lancer « npm ci » à la racine du projet (make outils).');
  process.exit(2);
}

const [base, ...chemins] = process.argv.slice(2);
if (!base) {
  console.error('Usage : node check-a11y.mjs <url de base> [chemin…]');
  process.exit(2);
}

let pages = chemins;
if (!pages.length) {
  if (!existsSync('dist/sitemap.xml')) {
    console.error('dist/sitemap.xml absent : construire le site (make build) ou donner les chemins.');
    process.exit(2);
  }
  pages = [...readFileSync('dist/sitemap.xml', 'utf8').matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => new URL(m[1]).pathname);
  pages.push('/page-inexistante-controle-a11y/');
}

const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
const page = await context.newPage();
const bloquants = [];
const autres = [];

for (const chemin of pages) {
  const reponse = await page.goto(base.replace(/\/$/, '') + chemin, { waitUntil: 'load' });
  if (!reponse) {
    bloquants.push(`${chemin} : aucune réponse`);
    continue;
  }
  const aThemes = await page.evaluate(() => document.documentElement.hasAttribute('data-theme'));
  for (const theme of aThemes ? ['clair', 'sombre'] : [null]) {
    if (theme) await page.evaluate((t) => { document.documentElement.dataset.theme = t; }, theme);
    await page.addScriptTag({ content: axeSource });
    const resultat = await page.evaluate(() => window.axe.run(document, {
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] },
    }));
    const lieu = theme ? `${chemin} (${theme})` : chemin;
    for (const v of resultat.violations) {
      const cibles = v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(', ');
      const ligne = `${lieu} : [${v.impact}] ${v.id} : ${v.help} (${v.nodes.length} élément(s) : ${cibles})`;
      (v.impact === 'serious' || v.impact === 'critical' ? bloquants : autres).push(ligne);
    }
  }
}
await browser.close();

for (const l of autres) console.log(`avertissement ${l}`);
for (const l of bloquants) console.log(`ERREUR ${l}`);
console.log(`${pages.length} page(s) scannée(s) : ${bloquants.length} bloquant(s), ${autres.length} avertissement(s).`);
console.log('Le scan couvre environ un tiers des critères : passe manuelle obligatoire (skill web-a11y-audit § 2).');
process.exit(bloquants.length ? 1 : 0);
