// SPDX-License-Identifier: GPL-3.0-or-later
// Aperçu d'un livrable : son PDF rendu en images (une par page) et en une planche, à regarder page par page
// avant de le présenter (skill livraison, ligne 5). Sortie hors du dépôt versionné : .claude/etat/apercu/<id>/.
// Usage : node apercu.mjs <id>
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { DOSSIER_LIVRABLES, lancerNavigateur, RACINE } from '../lib/document.mjs';

const id = process.argv[2];
const pdf = id && path.join(DOSSIER_LIVRABLES, id, `${id}.pdf`);
if (!pdf || !existsSync(pdf)) { console.error(`Usage : make apercu ID=<livrable> (PDF introuvable${id ? ` : ${path.relative(RACINE, pdf)}` : ''} ; make livrables d'abord).`); process.exit(2); }
const sortie = path.join(RACINE, '.claude/etat/apercu', id);
// Dossier propre à l'aperçu, entièrement régénéré : aucune page d'une version précédente ne reste.
if (existsSync(sortie)) rmSync(sortie, { recursive: true });
mkdirSync(sortie, { recursive: true });
execFileSync('pdftoppm', ['-r', '80', '-png', pdf, path.join(sortie, 'page')]);
const pages = readdirSync(sortie).filter((f) => /^page-\d+\.png$/.test(f)).sort();
const planche = path.join(sortie, 'planche.html');
writeFileSync(planche, `<!DOCTYPE html><html lang="fr"><meta charset="utf-8"><style>
body{margin:0;padding:16px;background:#888;font:14px sans-serif}
ol{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:0;padding:0;list-style:none}
img{display:block;width:100%;background:#fff}p{margin:4px 0 0;color:#fff}</style>
<ol>${pages.map((p, i) => `<li><img src="${p}" alt=""><p>Page ${i + 1}</p></li>`).join('')}</ol></html>`);
const navigateur = await lancerNavigateur();
const page = await navigateur.newPage({ viewport: { width: 1500, height: 800 } });
await page.goto(pathToFileURL(planche).href, { waitUntil: 'load' });
await page.screenshot({ path: path.join(sortie, 'planche.png'), fullPage: true });
await navigateur.close();
console.log(`${id} : ${pages.length} page(s), PDF du ${statSync(pdf).mtime.toLocaleString('fr-FR')}.`);
console.log(`Planche : ${path.relative(RACINE, path.join(sortie, 'planche.png'))}`);
console.log(`Pages   : ${pages.map((p) => path.relative(RACINE, path.join(sortie, p))).join(' ')}`);
console.log('Regarder chaque page : tableau ou date coupés, page presque vide, terme interne, lisible par le client.');
