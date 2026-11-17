// SPDX-License-Identifier: GPL-3.0-or-later
// Conventions de base de la mise en page (rule web-design), mesurées sur chaque page du plan du site
// à 390, 800, 1280 et 1920 px :
//   en-tête sur une seule ligne et de 5 rem au plus ; titre principal 2,75 rem au plus, titres de section 2 rem au plus ;
//   texte courant de 1,0625 à 1,25 rem ; aucun titre sur plus de 3 lignes à 1280 px ; aucun élément fixe posé sur
//   un contenu ; aucun défilement horizontal ; titre du haut de page 4 rem au plus ; pas plus de 3 grandes sections
//   de suite au même traitement (fond et style de titre), 3 en avertissement ; revue visuelle de chaque page ;
//   poids au chargement à 1280 px sous POIDS_MAX_KO (500 Ko par défaut, textes comptés compressés).
// Puis, si l'audit du site actuel existe : chaque page à côté de la page qu'elle remplace (.claude/etat/comparaison/),
// à regarder pour la ligne 5 de la checklist livraison.
// Une page de l'arborescence du mode d'emploi pas encore construite (404) est un avertissement pendant la construction,
// une erreur en recette (RECETTE=1) ; tout autre 404 est une erreur.
// Usage : [RECETTE=1] node check-mise-en-page.mjs [http://adresse-du-site]
//   sans adresse : sert dist/ (socle statique, après le build) ou lance scripts/dev.php (socle dynamique),
//   puis l'arrête par son PID.
import { execFileSync, spawn } from 'node:child_process';
import { gzipSync } from 'node:zlib';
import { existsSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { lancerNavigateur } from '../../livrables/lib/document.mjs';
import { comparerContenu } from './contenu-repris.mjs';
import { slugAdresse } from '../../livrables/lib/wireframes.mjs';

const RACINE = process.cwd();
const LARGEURS = [390, 800, 1280, 1920];
const REM = 16;
const LIMITES = { entete: 5 * REM, h1: 2.75 * REM, h1Haut: 4 * REM, h2: 2 * REM, texteMin: 1.0625 * REM, texteMax: 1.25 * REM, lignes: 3, airMin: 3 * REM };
const attendre = (ms) => new Promise((r) => setTimeout(r, ms));

async function demarrer() {
  if (process.argv[2]) return { base: process.argv[2].replace(/\/$/, ''), arreter: () => {} };
  const port = 8900 + Math.floor(Math.random() * 90);
  let commande;
  if (existsSync(path.join(RACINE, 'dist')) && existsSync(path.join(RACINE, 'bin/preview.php'))) commande = ['-S', `127.0.0.1:${port}`, '-t', 'dist', 'bin/preview.php'];
  else if (existsSync(path.join(RACINE, 'scripts/dev.php'))) {
    // Socle dynamique : php -S lancé ici, avec les extensions que scripts/dev.php ajouterait, pour garder le PID du
    // serveur lui-même (dev.php le lance en processus enfant : l'arrêter laisserait le serveur tourner).
    const dev = readFileSync(path.join(RACINE, 'scripts/dev.php'), 'utf8');
    const voulues = [...(dev.match(/const LOADABLE = \[([^\]]*)\]/)?.[1] ?? '').matchAll(/'([a-z_]+)'/g)].map((m) => m[1]);
    const chargees = execFileSync('php', ['-r', 'echo implode(",", get_loaded_extensions());'], { encoding: 'utf8' }).toLowerCase().split(',');
    const options = voulues.filter((e) => !chargees.includes(e)).flatMap((e) => ['-d', `extension=${e}`]);
    if (!existsSync(path.join(RACINE, 'data/dev.sqlite'))) execFileSync('php', ['scripts/dev.php', 'db'], { cwd: RACINE, stdio: 'ignore' });
    commande = [...options, '-S', `127.0.0.1:${port}`, '-t', 'public', 'public/router.php'];
  }
  else { console.error('Aucun site à servir : make build (socle statique), ou donner l\'adresse du site lancé.'); process.exit(2); }
  const serveur = spawn('php', commande, { cwd: RACINE, stdio: 'ignore' });
  const base = `http://127.0.0.1:${port}`;
  for (let i = 0; i < 50; i++) {
    try { if ((await fetch(base + '/')).status < 500) return { base, arreter: () => serveur.kill() }; } catch { /* pas encore prêt */ }
    await attendre(200);
  }
  serveur.kill();
  console.error(`Le site ne répond pas sur ${base}.`);
  process.exit(2);
}

async function chemins(base) {
  const liste = [];
  try {
    const r = await fetch(base + '/sitemap.xml');
    if (r.ok) for (const m of (await r.text()).matchAll(/<loc>([^<]+)<\/loc>/g)) { try { liste.push(new URL(m[1], base).pathname); } catch { /* adresse illisible */ } }
  } catch { /* pas de plan du site */ }
  // Plus les liens internes de l'accueil (menu, pied de page) : un plan du site incomplet ne cache aucune page.
  try {
    const html = await (await fetch(base + '/')).text();
    for (const m of html.matchAll(/href="(\/[^"#?]*)"/g)) if (!/\.[a-z0-9]{2,5}$/i.test(m[1]) && !m[1].startsWith('//')) liste.push(m[1]);
  } catch { /* accueil illisible */ }
  return [...new Set(['/', ...liste])].slice(0, 30);
}

/** Mesures dans la page (exécuté dans le navigateur). */
function mesurer(limites) {
  const visible = (el) => {
    const r = el.getBoundingClientRect();
    const s = getComputedStyle(el);
    // Contenu d'un details fermé (menu de langue, volet) : non affiché, mais Chromium lui garde une position.
    if (el.closest('details:not([open])') && !el.closest('summary')) return false;
    return r.width > 1 && r.height > 1 && s.visibility !== 'hidden' && s.opacity !== '0' && r.bottom > 0 && !(s.clip === 'rect(0px, 0px, 0px, 0px)' || s.clipPath === 'inset(50%)');
  };
  const ecarts = [];
  const entete = document.querySelector('body > header, header');
  if (entete && visible(entete)) {
    const re = entete.getBoundingClientRect();
    if (re.height > limites.entete + 1) ecarts.push(`en-tête de ${Math.round(re.height)} px (5 rem au plus)`);
    const elements = [...entete.querySelectorAll('a, button, img, svg')].filter(visible).filter((el) => !el.closest('[hidden], [aria-hidden="true"] a') && el.getBoundingClientRect().top < re.bottom);
    const logo = entete.querySelector('a[href="/"] img, a[href="/"] svg, a[href="/"]') ?? elements[0];
    if (logo && visible(logo)) {
      const rl = logo.getBoundingClientRect();
      const centre = (rl.top + rl.bottom) / 2;
      const hors = elements.filter((el) => { const r = el.getBoundingClientRect(); const c = (r.top + r.bottom) / 2; return Math.abs(c - centre) > Math.max(12, rl.height / 2); });
      if (hors.length) ecarts.push(`en-tête sur plusieurs lignes : ${hors.slice(0, 3).map((el) => `« ${(el.textContent || el.getAttribute('aria-label') || el.tagName).trim().slice(0, 30)} »`).join(', ')} hors de la ligne du logo`);
    }
    // Menu : la page courante se marque par un soulignement ou la couleur, jamais par un fond (pastille).
    const courant = [...entete.querySelectorAll('nav a[aria-current="page"]')].find(visible);
    if (courant) {
      const f = getComputedStyle(courant).backgroundColor;
      if (f !== 'rgba(0, 0, 0, 0)' && f !== 'transparent') ecarts.push(`menu : page courante « ${courant.textContent.trim().slice(0, 30)} » marquée par un fond ; la souligner ou la mettre en couleur`);
    }
    // Langue : un nom de langue seul dans l'en-tête (« Brezhoneg ») ne se comprend pas ; bouton de langue explicite.
    const langueSeule = [...entete.querySelectorAll('a[hreflang]')].filter(visible).find((el) => !el.closest('details'));
    if (langueSeule) ecarts.push(`en-tête : lien de langue « ${langueSeule.textContent.trim().slice(0, 30)} » seul ; bouton de langue explicite (icône, langue affichée, liste des autres)`);
  }
  const taille = (el) => parseFloat(getComputedStyle(el).fontSize);
  // Le titre du haut de page (premier écran) peut être plus grand : c'est souvent le point focal de la page.
  for (const h of [...document.querySelectorAll('main h1')].filter(visible)) {
    const haut = h.getBoundingClientRect().top + scrollY < innerHeight;
    if (taille(h) > (haut ? limites.h1Haut : limites.h1) + 0.5) ecarts.push(`h1 de ${taille(h).toFixed(1)} px (${haut ? '4 rem au plus dans le haut de page' : '2,75 rem au plus'})`);
  }
  for (const h of [...document.querySelectorAll('main h2')].filter(visible)) if (taille(h) > limites.h2 + 0.5) { ecarts.push(`h2 « ${h.textContent.trim().slice(0, 40)} » de ${taille(h).toFixed(1)} px (2 rem au plus)`); break; }
  const paragraphes = [...document.querySelectorAll('main p')].filter(visible).filter((p) => p.textContent.trim().length > 40);
  const petits = paragraphes.filter((p) => taille(p) < limites.texteMin - 0.3);
  const gros = paragraphes.filter((p) => taille(p) > limites.texteMax + 0.3);
  if (petits.length) ecarts.push(`${petits.length} paragraphe(s) sous 1,0625 rem (${taille(petits[0]).toFixed(1)} px)`);
  if (gros.length) ecarts.push(`${gros.length} paragraphe(s) au-dessus de 1,25 rem (${taille(gros[0]).toFixed(1)} px)`);
  if (innerWidth >= 1280) {
    for (const h of [...document.querySelectorAll('main h1, main h2, main h3')].filter(visible)) {
      const lh = parseFloat(getComputedStyle(h).lineHeight) || taille(h) * 1.2;
      const lignes = Math.round(h.getBoundingClientRect().height / lh);
      if (lignes > limites.lignes) { ecarts.push(`titre « ${h.textContent.trim().slice(0, 40)} » sur ${lignes} lignes`); break; }
    }
  }
  // Air : sur grand écran, le contenu d'une grande section commence à 3 rem au moins de son bord haut.
  if (innerWidth >= 1280) {
    for (const sec of [...(document.querySelector('main')?.children ?? [])].filter((el) => visible(el) && el.getBoundingClientRect().height > 200)) {
      const haut = sec.getBoundingClientRect().top;
      const premiers = [...sec.querySelectorAll('h1, h2, h3, p, img, ul, ol')].filter(visible).map((el) => el.getBoundingClientRect().top - haut);
      if (!premiers.length) continue;
      const ecart = Math.min(...premiers);
      if (ecart >= 0 && ecart < limites.airMin) { ecarts.push(`section « ${(sec.querySelector('h1, h2, h3')?.textContent ?? sec.className).trim().slice(0, 40)} » serrée : contenu à ${Math.round(ecart)} px du haut (3 rem au moins)`); break; }
    }
  }
  if (document.documentElement.scrollWidth > innerWidth + 1) ecarts.push(`défilement horizontal (${document.documentElement.scrollWidth} px pour ${innerWidth})`);
  return ecarts;
}

/**
 * Contenu de la page (exécuté dans la page, à 1280 px) : blocs de texte (mêmes règles que l'audit), médias vides ou
 * cassés, hauteur du haut de page, liens du pied de page.
 */
function lireContenu() {
  const visible = (el) => { const r = el.getBoundingClientRect(); const c = getComputedStyle(el); return r.width > 1 && r.height > 1 && c.visibility !== 'hidden' && c.display !== 'none'; };
  const zone = document.querySelector('main') ?? document.body;
  const blocs = [];
  for (const el of zone.querySelectorAll('h1, h2, h3, h4, p, li, blockquote, td, dt, dd')) {
    if (el.closest('nav, footer, header, script, style, noscript, [aria-hidden="true"]')) continue;
    if (el.matches('li, td, dd') && el.querySelector('p, li')) continue;
    const t = (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim();
    if (t.length >= 2) blocs.push({ balise: el.tagName.toLowerCase(), texte: t.slice(0, 2000) });
  }
  // Pied de page : un passage déplacé de l'ancien contenu (adresse, horaires) y compte comme repris.
  for (const el of document.querySelectorAll('footer p, footer li, footer address, footer dd')) {
    const t = (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim();
    if (t.length >= 2) blocs.push({ balise: 'p', texte: t.slice(0, 2000), pied: true });
  }
  const medias = [];
  for (const v of zone.querySelectorAll('video')) if (!v.currentSrc && !v.querySelector('source[src]') || v.error || v.networkState === 3) medias.push(`vidéo sans source lisible${v.getAttribute('aria-label') ? ` (« ${v.getAttribute('aria-label')} »)` : ''}`);
  for (const i of zone.querySelectorAll('img')) if (visible(i) && i.complete && i.naturalWidth === 0) medias.push(`image cassée : ${(i.getAttribute('src') || '').slice(0, 80)}`);
  for (const g of zone.querySelectorAll('[class*="galerie"], [class*="gallery"], [class*="video"], [class*="media"]')) {
    if (visible(g) && g.offsetHeight > 40 && !g.querySelector('img, video, iframe, picture, svg') && !(g.innerText || '').trim()) medias.push(`bloc média vide (${g.className.toString().slice(0, 40)})`);
  }
  for (const s of zone.querySelectorAll(':scope > section, :scope > div > section')) if (visible(s) && !(s.innerText || '').trim() && !s.querySelector('img, video, iframe, svg, picture')) medias.push('section vide');
  const h1 = zone.querySelector('h1');
  let haut = h1?.parentElement;
  while (haut && haut !== zone && haut.getBoundingClientRect().height < 250) haut = haut.parentElement;
  const hauteurHaut = haut && haut !== zone ? haut.getBoundingClientRect().height / innerHeight : null;
  const pied = [...document.querySelectorAll('footer a[href]')].map((a) => a.href);
  return { blocs, medias, hauteurHaut, pied };
}

/**
 * Rythme (rule web-design, « Qualité visuelle ») : traitement de chaque grande section du contenu (fond et style du
 * titre). Renvoie les suites de sections au même traitement, avec leur longueur.
 */
function rythme() {
  const zone = document.querySelector('main') ?? document.body;
  const sections = [...zone.children].filter((el) => el.getBoundingClientRect().height > 120 && getComputedStyle(el).display !== 'none');
  // Fond réellement vu : une section transparente prend celui de son parent (blanc explicite et blanc hérité sont le même fond).
  const fond = (el) => {
    for (let n = el; n; n = n.parentElement) {
      const s = getComputedStyle(n);
      if (s.backgroundImage !== 'none') return n === el ? 'image' : 'image du parent';
      if (s.backgroundColor !== 'rgba(0, 0, 0, 0)' && s.backgroundColor !== 'transparent') return s.backgroundColor;
    }
    return 'rgb(255, 255, 255)';
  };
  const titre = (el) => {
    const h = el.querySelector('h2, h3');
    if (!h) return 'sans titre';
    const s = getComputedStyle(h);
    const apres = getComputedStyle(h, '::after');
    return [s.fontSize, s.color, s.textAlign, apres.content !== 'none' ? apres.backgroundColor : ''].join(' ');
  };
  const suites = [];
  let suite = [];
  for (const el of sections) {
    const t = `${fond(el)} | ${titre(el)}`;
    if (suite.length && suite[0].t === t) suite.push({ t, el }); else { if (suite.length > 1) suites.push(suite); suite = [{ t, el }]; }
  }
  if (suite.length > 1) suites.push(suite);
  return suites.map((x) => ({ longueur: x.length, premier: (x[0].el.querySelector('h2, h3')?.textContent ?? '').trim().slice(0, 40), fond: x[0].t.split(' | ')[0] }));
}

/** Éléments fixes (hors en-tête) posés sur un contenu, en haut, au milieu et en bas de la page. */
function flottants() {
  const textes = [...document.querySelectorAll('main h1, main h2, main h3, main p, main li, main a, main button, footer p, footer a, footer li')];
  const fixes = [...document.querySelectorAll('body *')].filter((el) => {
    const s = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    return s.position === 'fixed' && !el.closest('header') && r.width > 1 && r.height > 1 && s.visibility !== 'hidden' && s.opacity !== '0';
  });
  const touche = (a, b) => a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;
  const resultat = [];
  for (const f of fixes) {
    const rf = f.getBoundingClientRect();
    const sous = textes.find((t) => !f.contains(t) && !t.contains(f) && touche(rf, t.getBoundingClientRect()));
    if (sous) resultat.push(`élément fixe « ${(f.textContent || f.getAttribute('aria-label') || f.tagName).trim().slice(0, 30)} » posé sur « ${sous.textContent.trim().slice(0, 30)} »`);
  }
  return resultat;
}

// Arborescence du mode d'emploi : adresse de chaque page prévue et adresse qu'elle remplace sur le site actuel.
const arborescence = [];
const modeEmploi = path.join(RACINE, '.claude/rules/projet.md');
if (existsSync(modeEmploi)) {
  const section = readFileSync(modeEmploi, 'utf8').split(/^## /m).find((b) => b.startsWith('Arborescence')) ?? '';
  for (const l of section.split('\n').filter((x) => /^\|/.test(x) && !/^\|[\s:|-]+\|$/.test(x))) {
    const c = l.replace(/^\||\|$/g, '').split('|').map((x) => x.trim().replace(/`/g, ''));
    if (/^\//.test(c[1] ?? '')) arborescence.push({ adresse: c[1].split(/\s/)[0], remplace: (c[3] ?? '').split(/[\s,]+/).filter((x) => /^\//.test(x)) });
  }
}
const recette = process.env.RECETTE === '1';
const POIDS_MAX_KO = Number(process.env.POIDS_MAX_KO) || 500;
const aConstruire = [];

// Crédit du prestataire (rule web-design, « Conventions de base ») : lien vers son site dans le pied de page.
const fichierKouzia = path.join(RACINE, '.claude/kouzia.json');
const credit = existsSync(fichierKouzia) ? JSON.parse(readFileSync(fichierKouzia, 'utf8')).credit : null;

const site = await demarrer();
const navigateur = await lancerNavigateur();
const ecarts = [];
const avertissements = [];
const captures = {};
const contenus = {};
try {
  const liste = await chemins(site.base);
  for (const chemin of liste) {
    for (const largeur of LARGEURS) {
      // Mouvement réduit : mesures et captures sur l'état final (apparitions au défilement, bandes défilantes figées).
      const page = await navigateur.newPage({ viewport: { width: largeur, height: 900 }, reducedMotion: 'reduce' });
      // Poids de la première vue (rule web-perf-eco, < 500 Ko) : corps reçus au chargement, textes comptés compressés
      // (gzip, comme le serveur de production) ; le serveur de développement ne compresse pas.
      const corps = [];
      if (largeur === 1280) page.on('response', (r) => corps.push(r.body().then((b) => (/^(text\/|application\/(javascript|json|xml)|image\/svg)/.test(r.headers()['content-type'] ?? '') ? gzipSync(b).length : b.length)).catch(() => 0)));
      const rep = await page.goto(site.base + chemin, { waitUntil: 'load' });
      if (!rep || rep.status() >= 400) {
        const prevue = rep?.status() === 404 && arborescence.some((p) => p.adresse === chemin);
        if (prevue && !recette) aConstruire.push(chemin); else ecarts.push(`${chemin} : statut ${rep?.status() ?? 'aucune réponse'}${prevue ? ' (page de l\'arborescence, exigée en recette)' : ''}`);
        await page.close();
        break;
      }
      await page.evaluate(() => document.fonts.ready);
      for (const e of await page.evaluate(mesurer, LIMITES)) ecarts.push(`${chemin} à ${largeur} px : ${e}`);
      const vus = new Set();
      for (const position of [0, 0.5, 1]) {
        await page.evaluate((p) => scrollTo(0, (document.documentElement.scrollHeight - innerHeight) * p), position);
        await attendre(150);
        for (const e of await page.evaluate(flottants)) if (!vus.has(e)) { vus.add(e); ecarts.push(`${chemin} à ${largeur} px : ${e}`); }
      }
      await page.evaluate(() => scrollTo(0, 0));
      await attendre(150);
      // Capture pleine page : un élément collant ou fixe s'y dessine au mauvais endroit ; remis dans le flux le temps
      // de la capture seulement (style posé par le DOM, accepté par une CSP style-src 'self').
      if (largeur === 1280) await page.evaluate(() => { for (const el of document.querySelectorAll('body *')) if (['sticky', 'fixed'].includes(getComputedStyle(el).position)) el.style.setProperty('position', 'static', 'important'); });
      if (largeur === 1280) {
        await page.waitForLoadState('networkidle').catch(() => {});
        const ko = Math.round((await Promise.all(corps)).reduce((a, b) => a + b, 0) / 1024);
        if (ko > POIDS_MAX_KO) ecarts.push(`${chemin} : ${ko} Ko au chargement, pour ${POIDS_MAX_KO} Ko au plus (rule web-perf-eco) : images à la taille affichée (copies réduites, srcset), rien de lourd hors de la première vue`);
        const c = await page.evaluate(lireContenu);
        contenus[chemin] = c;
        for (const m of c.medias) ecarts.push(`${chemin} : ${m} (une page ne se présente pas avec un bloc prévu vide, skill web-contenu)`);
        for (const r of await page.evaluate(rythme)) {
          const texte = `${chemin} : ${r.longueur} sections de suite au même traitement (fond ${r.fond}, même style de titre), à partir de « ${r.premier} » ; varier le fond, la disposition ou le point focal (rule web-design, « Qualité visuelle »)`;
          if (r.longueur >= 4) ecarts.push(texte); else if (r.longueur === 3) avertissements.push(texte);
        }
        if (credit?.lien?.url && !c.pied.some((h) => h.replace(/\/$/, '') === credit.lien.url.replace(/\/$/, ''))) ecarts.push(`${chemin} : pied de page sans le lien de crédit vers ${credit.lien.url} (.claude/kouzia.json)`);
      }
      if (largeur === 1280) captures[chemin] = await page.screenshot({ fullPage: true, type: 'jpeg', quality: 70 });
      await page.close();
    }
  }
} finally {
  site.arreter();
}

// Comparaison avec le site actuel : la page remplacée (même chemin, ou colonne « Remplace » de l'arborescence).
const inventaire = path.join(RACINE, 'docs/source/existant/inventaire.json');
if (existsSync(inventaire)) {
  const inv = JSON.parse(readFileSync(inventaire, 'utf8'));
  const remplace = Object.fromEntries(arborescence.filter((p) => p.remplace.length).map((p) => [p.adresse, p.remplace[0]]));
  const sansBarreC = (u) => u.replace(/\/$/, '') || '/';
  const anciennePage = (chemin) => (inv.pages ?? []).find((x) => sansBarreC(new URL(x.url).pathname) === sansBarreC(chemin));
  // Retraits justifiés : lignes « Retiré : » de toutes les maquettes.
  const dossierMaquettes = path.join(RACINE, 'docs/maquettes');
  const retraits = existsSync(dossierMaquettes) ? readdirSync(dossierMaquettes).filter((f) => f.endsWith('.md')).flatMap((f) => readFileSync(path.join(dossierMaquettes, f), 'utf8').split('\n').filter((l) => /^\s*retir[ée]\s*:/i.test(l))) : [];
  // Reprise du contenu : chaque page de l'arborescence reprend tout le texte des pages qu'elle remplace.
  for (const p of arborescence) {
    const anciens = (p.remplace.length ? p.remplace : [p.adresse]).map(anciennePage).filter(Boolean);
    if (!anciens.length) continue;
    const neuf = contenus[p.adresse] ?? contenus[sansBarreC(p.adresse) + '/'];
    if (!neuf) continue;
    for (const a of anciens) {
      const r = comparerContenu(a.blocs ?? [], neuf.blocs, retraits);
      r.mots.nouveau = comparerContenu([], neuf.blocs.filter((b) => !b.pied)).mots.nouveau;
      const ancien = new URL(a.url).pathname;
      if (r.manquantes.length) {
        const liste = r.manquantes.slice(0, 6).map((t) => `« ${t.slice(0, 70)}${t.length > 70 ? '…' : ''} »`).join(', ');
        ecarts.push(`${p.adresse} : ${r.manquantes.length} passage(s) de ${ancien} non repris${r.sections.length ? ` (sections presque absentes : ${r.sections.slice(0, 4).map((x) => `« ${x} »`).join(', ')})` : ''} : ${liste}. Reprendre (reformuler permis) ou justifier dans la maquette : « Retiré : « extrait » (raison) ».`);
      }
      if (r.mots.nouveau < r.mots.ancien * 0.8) avertissements.push(`${p.adresse} : ${r.mots.nouveau} mots porteurs pour ${r.mots.ancien} sur ${ancien} : contenu réduit.`);
      const hauteurAncienne = (inv.identite?.univers ?? []).find((u) => u.sorte === 'photo-en-fond' && sansBarreC(new URL(u.page).pathname) === sansBarreC(ancien))?.hauteur;
      if (hauteurAncienne && neuf.hauteurHaut && neuf.hauteurHaut < hauteurAncienne * 0.8) avertissements.push(`${p.adresse} : haut de page à ${Math.round(neuf.hauteurHaut * 100)} % de l'écran pour ${Math.round(hauteurAncienne * 100)} % sur ${ancien}.`);
    }
  }
  const dossier = path.join(RACINE, '.claude/etat/comparaison');
  mkdirSync(dossier, { recursive: true });
  const faites = [];
  const navigateur2 = await lancerNavigateur();
  for (const [chemin, image] of Object.entries(captures)) {
    const ancien = remplace[chemin] ?? chemin;
    const sansBarre = (u) => u.replace(/\/$/, '') || '/';
    const p = (inv.pages ?? []).find((x) => sansBarre(new URL(x.url).pathname) === sansBarre(ancien));
    if (!p?.capture) continue;
    const nom = (chemin.replace(/^\/|\/$/g, '').replace(/\//g, '-') || 'accueil');
    const neuf = path.join(dossier, `${nom}-nouveau.jpg`);
    writeFileSync(neuf, image);
    const html = path.join(dossier, `${nom}.html`);
    writeFileSync(html, `<!DOCTYPE html><html lang="fr"><meta charset="utf-8"><style>body{margin:0;display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:16px;background:#777;font:16px sans-serif;color:#fff}img{width:100%;display:block;background:#fff}</style>
<div><p>Site actuel : ${ancien}</p><img src="${pathToFileURL(path.join(path.dirname(inventaire), p.capture)).href}" alt=""></div><div><p>Nouveau site : ${chemin}</p><img src="${nom}-nouveau.jpg" alt=""></div></html>`);
    const page = await navigateur2.newPage({ viewport: { width: 1500, height: 900 }, reducedMotion: 'reduce' });
    await page.goto(pathToFileURL(html).href, { waitUntil: 'load' });
    const hauteur = Math.min(await page.evaluate(() => document.documentElement.scrollHeight), 4000);
    await page.screenshot({ path: path.join(dossier, `${nom}.png`), clip: { x: 0, y: 0, width: 1500, height: hauteur } });
    await page.close();
    faites.push(path.relative(RACINE, path.join(dossier, `${nom}.png`)));
  }
  await navigateur2.close();
  if (faites.length) console.log(`Comparaison avec le site actuel, à regarder (univers du client repris ?) :\n  ${faites.join('\n  ')}`);
}
await navigateur.close();

// Revue visuelle (skill web-direction, « Revue visuelle ») : chaque page construite de l'arborescence a un avis écrit par un
// relecteur indépendant, face à la page remplacée et aux références. « à reprendre » bloque ; absente : à faire, exigée en recette.
// Une adresse modèle de l'arborescence (/actualites/<titre>/) vaut pour toutes ses fiches : elle compte comme construite dès
// qu'une page capturée lui correspond, et sa revue est celle du modèle (actualites-fiche.md).
const sansRevue = [];
const construite = (adresse) => {
  if (!/</.test(adresse)) return Boolean(captures[adresse]);
  const motif = new RegExp(`^${adresse.split(/<[^>]+>/).map((x) => x.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('[^/]+')}$`);
  return Object.keys(captures).some((c) => motif.test(c));
};
for (const p of arborescence) {
  if (!construite(p.adresse) || /\/admin/.test(p.adresse)) continue;
  const fichier = path.join(RACINE, 'docs/maquettes/revues', `${slugAdresse(p.adresse)}.md`);
  if (!existsSync(fichier)) { sansRevue.push(p.adresse); continue; }
  const verdict = (readFileSync(fichier, 'utf8').match(/^Verdict\s*:\s*(.+)$/im)?.[1] ?? '').trim();
  if (!/^meilleure/i.test(verdict)) ecarts.push(`${p.adresse} : revue visuelle « ${verdict || 'sans verdict'} » (docs/maquettes/revues/${slugAdresse(p.adresse)}.md) ; reprendre la page puis refaire la revue`);
}
if (sansRevue.length) (recette ? ecarts : avertissements).push(`revue visuelle absente : ${sansRevue.join(', ')} (skill web-direction, « Revue visuelle »)`);

for (const a of avertissements) console.log(`Avertissement : ${a}`);
if (aConstruire.length) console.log(`À construire (pages de l'arborescence encore en 404, exigées en recette, RECETTE=1) : ${aConstruire.join(', ')}`);
if (ecarts.length) {
  console.error(`Mise en page : ${ecarts.length} écart(s) aux conventions de base (rule web-design)\n${ecarts.map((e) => `  ${e}`).join('\n')}`);
  process.exit(1);
}
console.log(`Mise en page : conventions de base respectées (${Object.keys(captures).length} page(s), ${LARGEURS.join(', ')} px).`);
