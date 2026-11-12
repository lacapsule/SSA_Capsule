// SPDX-License-Identifier: GPL-3.0-or-later
// Gabarit unique des livrables (charte Kouzia) : Markdown sûr vers HTML, page HTML, PDF, registre des versions.
// Tokens et polices lus dans la boîte à outils (../kouzia-boite-a-outils/base) à chaque génération : une seule source.
import { createHash } from 'node:crypto';
import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const ICI = path.dirname(fileURLToPath(import.meta.url));
export const RACINE = process.cwd();
export const DOSSIER_LIVRABLES = path.join(RACINE, 'docs/livrables');

export function echapper(t) {
  return String(t ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function enLigne(t) {
  let s = echapper(t);
  s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
  s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  s = s.replace(/\[([^\]]+)\]\(((?:https?:\/\/|\/|\.\.?\/|#)[^)\s]*)\)/g, '<a href="$2">$1</a>');
  return s;
}

/** Liste Markdown (lignes d'un même bloc) vers HTML, sous-listes comprises. */
function liste(lignes) {
  const retrait = (l) => l.match(/^\s*/)[0].length;
  const base = retrait(lignes[0]);
  const ordonnee = /^\s*\d+\./.test(lignes[0]);
  const items = [];
  for (const l of lignes) {
    const puce = /^\s*([-*]|\d+\.)\s+/.test(l);
    if (puce && retrait(l) <= base) items.push({ texte: l.replace(/^\s*([-*]|\d+\.)\s+/, ''), enfants: [] });
    else if (puce && items.length) items[items.length - 1].enfants.push(l);
    else if (items.length) {
      const dernier = items[items.length - 1];
      if (dernier.enfants.length) dernier.enfants[dernier.enfants.length - 1] += ' ' + l.trim(); else dernier.texte += ' ' + l.trim();
    }
  }
  const balise = ordonnee ? 'ol' : 'ul';
  return `<${balise}>${items.map((it) => `<li>${enLigne(it.texte)}${it.enfants.length ? liste(it.enfants) : ''}</li>`).join('')}</${balise}>`;
}

/** Markdown du kit (titres, paragraphes, listes, tableaux, citations, code) vers HTML. Le texte est échappé d'abord. */
export function markdown(source) {
  const lignes = String(source).replace(/\r\n?/g, '\n').split('\n');
  const html = [];
  let i = 0;
  while (i < lignes.length) {
    const l = lignes[i];
    if (!l.trim()) { i++; continue; }
    let m;
    if ((m = l.match(/^(#{1,4})\s+(.*)$/))) {
      const n = Math.min(m[1].length + 1, 5);
      html.push(`<h${n}>${enLigne(m[2])}</h${n}>`);
      i++;
    } else if (/^```/.test(l)) {
      const bloc = [];
      i++;
      while (i < lignes.length && !/^```/.test(lignes[i])) bloc.push(lignes[i++]);
      i++;
      html.push(`<pre><code>${echapper(bloc.join('\n'))}</code></pre>`);
    } else if (/^\|/.test(l)) {
      const rangees = [];
      while (i < lignes.length && /^\|/.test(lignes[i])) rangees.push(lignes[i++]);
      const cellules = (r) => r.replace(/^\||\|$/g, '').split(/(?<!\\)\|/).map((c) => c.trim().replace(/\\\|/g, '|'));
      const entete = cellules(rangees[0]);
      const corps = rangees.slice(/^\|[\s:|-]+\|$/.test(rangees[1] ?? '') ? 2 : 1).map(cellules);
      html.push('<table><thead><tr>' + entete.map((c) => `<th scope="col">${enLigne(c)}</th>`).join('') + '</tr></thead><tbody>'
        + corps.map((r) => '<tr>' + r.map((c) => `<td>${enLigne(c)}</td>`).join('') + '</tr>').join('') + '</tbody></table>');
    } else if (/^\s*([-*]|\d+\.)\s+/.test(l)) {
      // Liste, imbrication par l'indentation (2 espaces ou plus de plus que le niveau au-dessus).
      const bloc = [];
      while (i < lignes.length && (/^\s*([-*]|\d+\.)\s+/.test(lignes[i]) || (/^\s{2,}\S/.test(lignes[i]) && bloc.length))) bloc.push(lignes[i++]);
      html.push(liste(bloc));
    } else if (/^>\s?/.test(l)) {
      const bloc = [];
      while (i < lignes.length && /^>\s?/.test(lignes[i])) bloc.push(lignes[i++].replace(/^>\s?/, ''));
      html.push(`<blockquote><p>${enLigne(bloc.join(' '))}</p></blockquote>`);
    } else {
      const bloc = [];
      while (i < lignes.length && lignes[i].trim() && !/^(#{1,4}\s|\||```|>|\s*([-*]|\d+\.)\s+)/.test(lignes[i])) bloc.push(lignes[i++].trim());
      html.push(`<p>${enLigne(bloc.join(' '))}</p>`);
    }
  }
  return html.join('\n');
}

/** Dossier de la boîte à outils, à côté du dépôt. */
export function boite() {
  const d = path.join(path.dirname(RACINE), 'kouzia-boite-a-outils');
  if (!existsSync(path.join(d, 'base/tokens.css'))) {
    throw new Error(`Boîte à outils absente (${d}) : lancer make deps, qui la clone à côté du dépôt.`);
  }
  return d;
}

/** Copie la charte (tokens et polices de la boîte, feuille du gabarit) dans docs/livrables/_commun. */
function poserCommun() {
  const commun = path.join(DOSSIER_LIVRABLES, '_commun');
  mkdirSync(path.join(commun, 'polices'), { recursive: true });
  const b = boite();
  copyFileSync(path.join(b, 'base/tokens.css'), path.join(commun, 'tokens.css'));
  for (const p of ['exo2-latin.woff2', 'share-tech-mono-latin.woff2']) {
    copyFileSync(path.join(b, 'base/polices', p), path.join(commun, 'polices', p));
  }
  copyFileSync(path.join(ICI, '../gabarit/document.css'), path.join(commun, 'document.css'));
}

function lireRegistre() {
  const f = path.join(DOSSIER_LIVRABLES, 'registre.json');
  return existsSync(f) ? JSON.parse(readFileSync(f, 'utf8')) : { livrables: {} };
}

function ecrireRegistre(r) {
  writeFileSync(path.join(DOSSIER_LIVRABLES, 'registre.json'), JSON.stringify(r, null, 2) + '\n');
}

/** Enregistre la validation d'un livrable (acte humain, sur instruction explicite). */
export function valider(id, par) {
  const r = lireRegistre();
  const e = r.livrables[id];
  if (!e) throw new Error(`Livrable inconnu : ${id} (générer d'abord : make livrables)`);
  if (e.statut === 'constat') throw new Error(`${id} est un constat : il ne se valide pas.`);
  if (e.statut === 'brouillon') throw new Error(`${id} est en brouillon (source incomplète ou mode d'emploi non validé) : compléter la source, make livrables, puis valider.`);
  e.statut = 'validé';
  e.validation = { par, date: new Date().toISOString().slice(0, 10), version: e.version };
  ecrireRegistre(r);
  return e;
}

/**
 * Écrit un livrable : docs/livrables/<id>/index.html et <id>.pdf.
 * Version : +1 quand le contenu change ; un contenu changé après validation repasse « à valider ».
 * doc : { id, titre, client, sousTitre, corps (HTML), brouillon (bool), constat (bool : jamais validé), avertissements: [texte] }
 */
export async function ecrireLivrable(doc) {
  mkdirSync(path.join(DOSSIER_LIVRABLES, doc.id), { recursive: true });
  poserCommun();
  const empreinte = createHash('sha256').update(doc.corps + (doc.avertissements ?? []).join('|')).digest('hex').slice(0, 16);
  const r = lireRegistre();
  const avant = r.livrables[doc.id];
  const date = new Date().toISOString().slice(0, 10);
  let entree = avant;
  if (!avant || avant.empreinte !== empreinte) {
    entree = { titre: doc.titre, version: (avant?.version ?? 0) + 1, date, empreinte, statut: doc.constat ? 'constat' : doc.brouillon ? 'brouillon' : 'à valider' };
  } else if (doc.constat) {
    entree.statut = 'constat';
  } else if (doc.brouillon && entree.statut !== 'brouillon') {
    entree.statut = 'brouillon';
  }
  r.livrables[doc.id] = entree;
  ecrireRegistre(r);

  const statut = entree.statut === 'validé' ? `validé le ${entree.validation.date} par ${entree.validation.par}` : entree.statut;
  const html = `<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>${echapper(doc.titre)} · ${echapper(doc.client)}</title>
<link rel="stylesheet" href="../_commun/tokens.css">
<link rel="stylesheet" href="../_commun/document.css">
</head>
<body${doc.brouillon ? ' class="brouillon"' : ''}>
<header class="couverture">
  <p class="marque">Kouzia</p>
  <p class="surtitre">${echapper(doc.id.replace(/^(\d+)-.*/, 'Livrable $1'))}</p>
  <h1>${echapper(doc.titre)}</h1>
  <p class="client">${echapper(doc.client)}</p>
  ${doc.sousTitre ? `<p class="chapo">${echapper(doc.sousTitre)}</p>` : ''}
  <dl class="meta">
    <div><dt>Version</dt><dd>${entree.version}</dd></div>
    <div><dt>Date</dt><dd>${entree.date}</dd></div>
    <div><dt>Statut</dt><dd>${echapper(statut)}</dd></div>
  </dl>
</header>
${(doc.avertissements ?? []).length ? `<aside class="avertissement" role="note"><h2>À compléter avant validation</h2><ul>${doc.avertissements.map((a) => `<li>${echapper(a)}</li>`).join('')}</ul></aside>` : ''}
<main>
${doc.corps}
</main>
<footer class="pied">Kouzia · ${echapper(doc.titre)} · ${echapper(doc.client)} · version ${entree.version} du ${entree.date}</footer>
</body>
</html>
`;
  const fichierHtml = path.join(DOSSIER_LIVRABLES, doc.id, 'index.html');
  writeFileSync(fichierHtml, html);

  const require = createRequire(RACINE + '/');
  let chromium;
  try { ({ chromium } = require('playwright-core')); } catch {
    throw new Error('playwright-core absent : lancer make deps (ou npm ci) à la racine du dépôt.');
  }
  const navigateur = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
  const page = await navigateur.newPage();
  await page.goto(pathToFileURL(fichierHtml).href, { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  const fichierPdf = path.join(DOSSIER_LIVRABLES, doc.id, `${doc.id}.pdf`);
  await page.pdf({
    path: fichierPdf, format: 'A4', printBackground: true, preferCSSPageSize: true,
    displayHeaderFooter: true, headerTemplate: '<span></span>',
    footerTemplate: `<div style="width:100%;font:8px sans-serif;color:#4a5270;padding:0 15mm;display:flex;justify-content:space-between"><span>Kouzia · ${echapper(doc.titre)} · v${entree.version}</span><span><span class="pageNumber"></span> / <span class="totalPages"></span></span></div>`,
  });
  await navigateur.close();
  return { ...entree, html: path.relative(RACINE, fichierHtml), pdf: path.relative(RACINE, fichierPdf) };
}
