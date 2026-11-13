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
  // Date écrite en toutes lettres et jamais coupée en fin de ligne (« 2026- / 11-13 ») ; hors des balises (adresses des liens).
  return s.split(/(<[^>]+>)/).map((bout) => (bout.startsWith('<') ? bout : bout.replace(/\b\d{4}-\d{2}-\d{2}\b/g, (d) => `<time class="date" datetime="${d}">${dateFr(d)}</time>`))).join('');
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
const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
/** 2026-11-13 → 13 novembre 2026 (texte destiné au client). */
export function dateFr(iso) {
  const [a, m, j] = String(iso).split('-').map(Number);
  return a && m && j ? `${j === 1 ? '1er' : j} ${MOIS[m - 1]} ${a}` : String(iso ?? '');
}

/** Nombre de pages d'un PDF produit par Chrome (objets /Type /Page). */
function pagesPdf(fichier) {
  return (readFileSync(fichier, 'latin1').match(/\/Type\s*\/Page(?![s\w])/g) ?? []).length;
}

export function boite() {
  const d = path.join(path.dirname(RACINE), 'kouzia-boite-a-outils');
  if (!existsSync(path.join(d, 'base/tokens.css'))) {
    throw new Error(`Boîte à outils absente (${d}) : lancer make deps, qui la clone à côté du dépôt.`);
  }
  return d;
}

/** Copie la charte (tokens et polices de la boîte, feuille du gabarit) dans docs/livrables/_commun. */
export function poserCommun() {
  const commun = path.join(DOSSIER_LIVRABLES, '_commun');
  mkdirSync(path.join(commun, 'polices'), { recursive: true });
  const b = boite();
  copyFileSync(path.join(b, 'base/tokens.css'), path.join(commun, 'tokens.css'));
  for (const p of ['exo2-latin.woff2', 'share-tech-mono-latin.woff2']) {
    copyFileSync(path.join(b, 'base/polices', p), path.join(commun, 'polices', p));
  }
  for (const f of ['document.css', 'wireframe.css', 'planche.css']) copyFileSync(path.join(ICI, '../gabarit', f), path.join(commun, f));
}

export function lireRegistre() {
  const f = path.join(DOSSIER_LIVRABLES, 'registre.json');
  return existsSync(f) ? JSON.parse(readFileSync(f, 'utf8')) : { livrables: {} };
}

function ecrireRegistre(r) {
  writeFileSync(path.join(DOSSIER_LIVRABLES, 'registre.json'), JSON.stringify(r, null, 2) + '\n');
}

/** Navigateur des livrables (rendu, captures, PDF) : CHROME_PATH, sinon celui de Playwright. */
export async function lancerNavigateur() {
  const require = createRequire(RACINE + '/');
  let chromium;
  try { ({ chromium } = require('playwright-core')); } catch {
    throw new Error('playwright-core absent : lancer make deps (ou npm ci) à la racine du dépôt.');
  }
  // CHROME_PATH, sinon le Chrome ou Chromium du système, sinon celui de Playwright.
  const systeme = ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'];
  const executablePath = process.env.CHROME_PATH || systeme.find((f) => existsSync(f));
  try {
    return await chromium.launch(executablePath ? { executablePath } : {});
  } catch (e) {
    throw new Error(`Navigateur introuvable ou incomplet (${String(e.message).split('\n')[0]}) : lancer make deps, ou CHROME_PATH=/chemin/vers/chrome.`);
  }
}

/** Lignes lisibles des sources d'un livrable (texte JSON aplati) : ce que le client valide, ligne à ligne. */
function lignesSources(sources) {
  let valeur;
  try { valeur = JSON.parse(sources); } catch { valeur = sources; }
  const textes = [];
  const parcourir = (v) => { if (typeof v === 'string') textes.push(v); else if (typeof v === 'number') textes.push(String(v)); else if (v && typeof v === 'object') Object.values(v).forEach(parcourir); };
  parcourir(valeur);
  return textes.flatMap((t) => t.split('\n')).map((l) => l.trim()).filter(Boolean);
}

/** Lignes retirées et ajoutées entre deux textes (comparaison par ligne, ordre ignoré). */
function ecartLignes(avant, apres) {
  const compter = (l) => l.reduce((m, x) => m.set(x, (m.get(x) ?? 0) + 1), new Map());
  const a = compter(avant); const b = compter(apres);
  const retirees = avant.filter((x, i) => avant.indexOf(x) === i && (b.get(x) ?? 0) < a.get(x));
  const ajoutees = apres.filter((x, i) => apres.indexOf(x) === i && (a.get(x) ?? 0) < b.get(x));
  return { retirees, ajoutees };
}

/** Enregistre la validation d'un livrable (acte humain, sur instruction explicite). */
export function valider(id, par, extra = {}) {
  const r = lireRegistre();
  const e = r.livrables[id];
  if (!e) throw new Error(`Livrable inconnu : ${id} (générer d'abord : make livrables)`);
  if (e.statut === 'constat') throw new Error(`${id} est un constat : il ne se valide pas.`);
  if (e.statut === 'brouillon') throw new Error(`${id} est en brouillon (source incomplète ou mode d'emploi non validé) : compléter la source, make livrables, puis valider.`);
  e.statut = 'validé';
  e.validation = { par, date: new Date().toISOString().slice(0, 10), version: e.version, ...extra };
  // Texte validé figé pour cette version : base de comparaison si les sources changent ensuite.
  const sources = path.join(DOSSIER_LIVRABLES, id, 'sources.txt');
  if (existsSync(sources)) copyFileSync(sources, path.join(DOSSIER_LIVRABLES, id, `sources-validees-v${e.version}.txt`));
  ecrireRegistre(r);
  return e;
}

/**
 * Écrit un livrable : docs/livrables/<id>/index.html et <id>.pdf.
 * Version : +1 quand le contenu change ; un contenu changé après validation repasse « à valider ».
 * doc : { id, titre, client, sousTitre, corps (HTML), brouillon (bool), constat (bool : jamais validé), avertissements: [texte],
 *         pagesMax (nombre : au-delà, le livrable repasse en brouillon avec un avertissement),
 *         sources (texte : ce que le client valide, lu dans les fichiers du projet ; sans lui, le rendu fait foi) }
 * Sources inchangées, rendu changé (gabarit ou générateur du kit) : même version, statut et validation conservés,
 * date de mise en forme notée. Sources changées : nouvelle version, « à valider ».
 */
export async function ecrireLivrable(doc) {
  mkdirSync(path.join(DOSSIER_LIVRABLES, doc.id), { recursive: true });
  poserCommun();
  const r = lireRegistre();
  // Second rendu (trop de pages) : même version que le premier, comparée à l'entrée d'avant le premier.
  const avant = doc.recompte ? doc.avantRecompte : r.livrables[doc.id];
  const date = new Date().toISOString().slice(0, 10);
  const dossier = path.join(DOSSIER_LIVRABLES, doc.id);
  // Sources seules : un avertissement ajouté par une nouvelle règle du kit ne rouvre pas une validation.
  const empreinteSource = doc.sources == null ? null : createHash('sha256').update(doc.sources).digest('hex').slice(0, 16);
  // Registre d'avant ce calcul (empreinte absente ou d'un calcul antérieur) : la version enregistrée est prise comme
  // celle des sources actuelles (signalé). CALCUL_SOURCE change quand le calcul de l'empreinte change.
  const CALCUL_SOURCE = 2;
  const reprise = avant && empreinteSource && avant.calculSource !== CALCUL_SOURCE;
  const memesSources = avant && empreinteSource && (avant.empreinteSource === empreinteSource || reprise);
  const historique = avant?.historique ?? [];
  const derniereValidee = avant?.statut === 'validé' ? avant : [...historique].reverse().find((h) => h.statut === 'validé');
  // Sources revenues à celles d'une version validée : cette version et sa validation reviennent d'elles-mêmes.
  const retour = empreinteSource && !memesSources ? [...historique].reverse().find((h) => h.statut === 'validé' && h.empreinteSource === empreinteSource) : null;
  const avertissements = [...(doc.avertissements ?? [])];
  if (empreinteSource) writeFileSync(path.join(dossier, 'sources.txt'), lignesSources(doc.sources).join('\n') + '\n');
  // Sources changées depuis la dernière validation : le dire et nommer les lignes du texte du client qui ont changé.
  if (empreinteSource && !memesSources && !retour && derniereValidee && !doc.recompte) {
    const f = path.join(dossier, `sources-validees-v${derniereValidee.version}.txt`);
    const ecart = existsSync(f) ? ecartLignes(readFileSync(f, 'utf8').split('\n').filter(Boolean), lignesSources(doc.sources)) : null;
    const quoi = ecart ? [...ecart.retirees.map((l) => `retiré « ${l.slice(0, 90)} »`), ...ecart.ajoutees.map((l) => `ajouté « ${l.slice(0, 90)} »`)].slice(0, 8) : ['texte validé non conservé (validation antérieure à cette règle)'];
    const message = `Texte changé depuis la version ${derniereValidee.version} validée le ${dateFr(derniereValidee.validation.date)} par ${derniereValidee.validation.par} : ${quoi.join(' ; ')}. Changement voulu : nouvelle validation du client ; sinon remettre le texte, la version validée revient seule.`;
    console.log(`${doc.id} : ${message}`);
    avertissements.push(message);
  }
  // Empreinte du rendu : texte du document, avertissements et sources rendues à part (pages de wireframe…).
  const empreinte = createHash('sha256').update(doc.corps + avertissements.join('|') + (doc.sourcesRendues ?? '')).digest('hex').slice(0, 16);
  const instantane = (e) => { const { historique: _h, ...reste } = e; return reste; };
  let entree = avant;
  if (retour) {
    entree = { ...retour, titre: doc.titre, empreinte, calculSource: CALCUL_SOURCE, miseEnForme: date, historique: historique.filter((h) => h !== retour) };
    console.log(`${doc.id} : sources identiques à la version ${retour.version} validée le ${dateFr(retour.validation.date)} : version et validation rétablies.`);
  } else if (memesSources && avant.empreinte !== empreinte) {
    entree = { ...avant, empreinte, empreinteSource, calculSource: CALCUL_SOURCE, miseEnForme: date };
    console.log(`${doc.id} : mise en forme mise à jour, contenu inchangé, version ${avant.version} et statut « ${avant.statut} » conservés.`);
  } else if (memesSources && reprise) {
    entree = { ...avant, empreinteSource, calculSource: CALCUL_SOURCE };
  } else if (!avant || avant.empreinte !== empreinte || (empreinteSource && avant.empreinteSource !== empreinteSource)) {
    // Nouvelle version : l'entrée précédente passe dans l'historique avec sa validation, jamais effacée.
    // Une version jamais validée et remplacée n'est pas gardée (aucun accord à conserver).
    const garde = avant && (avant.statut === 'validé' || avant.statut === 'constat') ? [...historique, instantane(avant)] : historique;
    const version = Math.max(avant?.version ?? 0, ...historique.map((h) => h.version)) + 1;
    entree = { titre: doc.titre, version, date, empreinte, ...(empreinteSource ? { empreinteSource, calculSource: CALCUL_SOURCE } : {}), statut: doc.constat ? 'constat' : doc.brouillon ? 'brouillon' : 'à valider', ...(garde.length ? { historique: garde } : {}) };
  } else if (doc.constat) {
    entree.statut = 'constat';
  } else if (doc.brouillon && !['brouillon', 'validé'].includes(entree.statut)) {
    entree.statut = 'brouillon';
  }
  r.livrables[doc.id] = entree;
  ecrireRegistre(r);
  doc = { ...doc, avertissements };
  // Validation antérieure à cette règle, sources inchangées : le texte actuel est celui qui a été validé, figé ici.
  const fige = entree.statut === 'validé' && memesSources ? path.join(dossier, `sources-validees-v${entree.version}.txt`) : null;
  if (fige && !existsSync(fige)) copyFileSync(path.join(dossier, 'sources.txt'), fige);

  if (reprise && avant.statut === 'validé') console.log(`${doc.id} : empreinte des sources enregistrée sur la version ${avant.version} validée ; vérifier que son texte n'a pas changé depuis la validation.`);
  const valideeAvant = entree.statut !== 'validé' ? [...(entree.historique ?? [])].reverse().find((h) => h.statut === 'validé') : null;
  const statut = (entree.statut === 'validé' ? `validé le ${dateFr(entree.validation.date)} par ${entree.validation.par}` : entree.statut)
    + (entree.miseEnForme ? ` ; mise en forme mise à jour le ${dateFr(entree.miseEnForme)}, validation conservée` : '')
    + (valideeAvant ? ` ; version ${valideeAvant.version} validée le ${dateFr(valideeAvant.validation.date)} par ${valideeAvant.validation.par}` : '');
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
    <div><dt>Date</dt><dd>${dateFr(entree.date)}</dd></div>
    <div><dt>Statut</dt><dd>${echapper(statut)}</dd></div>
  </dl>
</header>
${(doc.avertissements ?? []).length ? `<aside class="avertissement" role="note"><h2>À compléter avant validation</h2><ul>${doc.avertissements.map((a) => `<li>${echapper(a)}</li>`).join('')}</ul></aside>` : ''}
<main>
${doc.corps}
</main>
<footer class="pied">Kouzia · ${echapper(doc.titre)} · ${echapper(doc.client)} · version ${entree.version} du ${dateFr(entree.date)}</footer>
</body>
</html>
`;
  const fichierHtml = path.join(DOSSIER_LIVRABLES, doc.id, 'index.html');
  writeFileSync(fichierHtml, html);

  const navigateur = await lancerNavigateur();
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
  const pages = pagesPdf(fichierPdf);
  if (doc.pagesMax && pages > doc.pagesMax && !doc.recompte) {
    const avertissement = `${pages} pages en PDF pour ${doc.pagesMax} au plus : raccourcir la source.`;
    return ecrireLivrable({ ...doc, recompte: true, avantRecompte: avant, brouillon: true, avertissements: [...(doc.avertissements ?? []), avertissement] });
  }
  return { ...entree, pages, html: path.relative(RACINE, fichierHtml), pdf: path.relative(RACINE, fichierPdf) };
}
