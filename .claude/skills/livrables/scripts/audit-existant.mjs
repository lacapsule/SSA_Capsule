// SPDX-License-Identifier: GPL-3.0-or-later
// Audit du site actuel du client et des fichiers qu'il fournit : inventaire sourcé, base des livrables et du nouveau site.
// Usage : node audit-existant.mjs <url> [--max 40] [--sortie docs/source/existant]
// Écrit : <sortie>/inventaire.json, <sortie>/captures/*.jpg (bureau 1440 et mobile 390), <sortie>/fichiers/* (logos, images).
// Fichiers fournis : tout ce qui est dans docs/source/fournis/ est listé (type, poids, dimensions des images).
// Chaque fait relevé garde l'URL de la page où il a été lu : il reste « à confirmer » tant que l'humain ne l'a pas validé.
// Accord du client acquis quand l'humain demande l'audit (le client veut un nouveau site).
import { createHash } from 'node:crypto';
import { existsSync, mkdirSync, readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const require = createRequire(process.cwd() + '/');
let chromium;
try { ({ chromium } = require('playwright-core')); } catch {
  console.error('playwright-core absent : lancer make deps (ou npm ci) à la racine du dépôt.');
  process.exit(2);
}

const args = process.argv.slice(2);
const option = (nom, defaut) => { const i = args.indexOf(nom); return i >= 0 ? args[i + 1] : defaut; };
const depart = args.find((a) => /^https?:\/\//.test(a));
if (!depart) {
  console.error('Usage : node audit-existant.mjs <url> [--max 40] [--sortie docs/source/existant]');
  process.exit(2);
}
const MAX = Number(option('--max', 40));
const SORTIE = path.resolve(option('--sortie', 'docs/source/existant'));
const FOURNIS = path.resolve('docs/source/fournis');
const origine = new URL(depart).origin;
const hote = new URL(depart).hostname.replace(/^www\./, '');
mkdirSync(path.join(SORTIE, 'captures'), { recursive: true });
mkdirSync(path.join(SORTIE, 'fichiers'), { recursive: true });

const EXT_FICHIER = /\.(pdf|jpe?g|png|gif|webp|avif|svg|zip|docx?|xlsx?|pptx?|mp4|mp3|ico|css|js|xml|txt)$/i;
const normaliser = (u) => {
  try {
    const x = new URL(u, origine);
    if (x.hostname.replace(/^www\./, '') !== hote || !/^https?:$/.test(x.protocol)) return null;
    x.hash = '';
    for (const p of [...x.searchParams.keys()]) if (/^(utm_|fbclid|gclid)/.test(p)) x.searchParams.delete(p);
    return x.href;
  } catch { return null; }
};
const slug = (u) => (new URL(u).pathname.replace(/^\/|\/$/g, '').replace(/[^a-z0-9]+/gi, '-').toLowerCase() || 'accueil').slice(0, 60);

const navigateur = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
const contexte = await navigateur.newContext({ viewport: { width: 1440, height: 900 }, locale: 'fr-FR' });
const page = await contexte.newPage();

// Graines : l'adresse donnée et le sitemap s'il existe.
// Deux files : les pages (un chemin, une page, comptées dans --max et capturées) et les variantes à paramètres
// (?lang=, ?page=…), lues seulement pour découvrir des liens et listées à part : elles ne mangent pas la limite.
const file = [normaliser(depart)];
const fileVariantes = [];
const vus = new Set(file);
const MAX_VARIANTES = 40;
const ajouterAFile = (u) => {
  if (!u || vus.has(u) || EXT_FICHIER.test(new URL(u).pathname)) return;
  vus.add(u);
  (new URL(u).search ? fileVariantes : file).push(u);
};
const anciennesAdresses = new Set();
try {
  const r = await contexte.request.get(origine + '/sitemap.xml', { timeout: 10000 });
  if (r.ok()) {
    for (const m of (await r.text()).matchAll(/<loc>\s*([^<\s]+)\s*<\/loc>/g)) {
      const u = normaliser(m[1]);
      if (u && !EXT_FICHIER.test(new URL(u).pathname)) { anciennesAdresses.add(new URL(u).pathname); ajouterAFile(u); }
    }
  }
} catch { /* pas de sitemap : parcours par liens seulement */ }

// Lecture d'une page, dans le navigateur.
function lirePage() {
  const visible = (el) => { const r = el.getBoundingClientRect(); const c = getComputedStyle(el); return r.width > 0 && r.height > 0 && c.visibility !== 'hidden' && c.display !== 'none'; };
  const texte = (el) => (el?.innerText ?? '').replace(/\s+/g, ' ').trim();
  const meta = (n) => document.querySelector(`meta[name="${n}"], meta[property="${n}"]`)?.content ?? null;
  const zone = document.querySelector('main, [role="main"], article') ?? document.body;
  const blocs = [];
  for (const el of zone.querySelectorAll('h1, h2, h3, h4, p, li, blockquote, td, dt, dd')) {
    if (!visible(el) || el.closest('nav, footer, header, script, style, noscript, [aria-hidden="true"]')) continue;
    if (el.matches('li, td, dd') && el.querySelector('p, li')) continue;
    const t = texte(el);
    if (t.length >= 2) blocs.push({ balise: el.tagName.toLowerCase(), texte: t.slice(0, 2000) });
  }
  const images = [...document.images].filter((i) => i.currentSrc || i.src).map((i) => ({
    src: i.currentSrc || i.src, alt: i.getAttribute('alt'), largeur: i.naturalWidth, hauteur: i.naturalHeight,
    affichee: Math.round(i.getBoundingClientRect().width), dansEnTete: !!i.closest('header, nav, [class*="header"], [class*="logo"]'),
    lienAccueil: !!i.closest('a[href="/"], a[href="./"], a[href="' + location.origin + '/"], a[href="' + location.origin + '"]'),
    indiceLogo: /logo/i.test((i.src || '') + ' ' + (i.alt || '') + ' ' + (i.className || '') + ' ' + (i.closest('[class*="logo"], [id*="logo"]') ? 'logo' : '')),
  }));
  const svgLogos = [...document.querySelectorAll('header svg, [class*="logo"] svg, a[href="/"] svg')].slice(0, 3).map((s) => s.outerHTML.slice(0, 200000));
  const liens = [...document.querySelectorAll('a[href]')].map((a) => ({ href: a.href, texte: texte(a).slice(0, 120), dansNav: !!a.closest('nav') && !a.closest('[class*="pagin"], [aria-label*="agination"], [aria-label*="pages"]') && !/[?&](page|p|paged)=/.test(a.search), dansPied: !!a.closest('footer') }));
  const icones = [...document.querySelectorAll('link[rel~="icon"], link[rel="apple-touch-icon"], link[rel="mask-icon"]')].map((l) => ({ rel: l.rel, href: l.href, tailles: l.sizes?.value ?? null }));
  const jsonld = [...document.querySelectorAll('script[type="application/ld+json"]')].map((s) => { try { return JSON.parse(s.textContent); } catch { return null; } }).filter(Boolean);

  // Couleurs : poids par surface pour les fonds, par usage pour le texte et les actions.
  const couleurs = {};
  const ajouter = (c, role, poids) => {
    if (!c || c === 'rgba(0, 0, 0, 0)' || c === 'transparent') return;
    couleurs[c] ??= { couleur: c, roles: {}, poids: 0 };
    couleurs[c].roles[role] = (couleurs[c].roles[role] ?? 0) + poids;
    couleurs[c].poids += poids;
  };
  for (const el of document.querySelectorAll('body, header, nav, main, section, footer, div, a, button, h1, h2, h3, [class*="btn"], [class*="button"]')) {
    if (!visible(el)) continue;
    const c = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    const aire = Math.min(r.width * r.height, 1440 * 900) / 10000;
    const action = el.matches('a, button, [class*="btn"], [class*="button"]');
    ajouter(c.backgroundColor, action ? 'fond-action' : 'fond', action ? aire + 40 : aire);
    if (el.matches('a, button, [class*="btn"], [class*="button"]')) ajouter(c.color, 'texte-action', 5);
    else if (el.matches('h1, h2, h3')) ajouter(c.color, 'titre', 5);
    else if (el.matches('body')) ajouter(c.color, 'texte', 20);
    if (parseFloat(c.borderTopWidth) > 0) ajouter(c.borderTopColor, 'bordure', 1);
  }
  const couleursLogo = [];
  for (const el of document.querySelectorAll('header svg *, [class*="logo"] svg *, a[href="/"] svg *')) {
    const c = getComputedStyle(el);
    for (const v of [c.fill, c.stroke]) if (v && v !== 'none' && !v.startsWith('url') && !couleursLogo.includes(v)) couleursLogo.push(v);
  }
  const police = (sel) => { const el = document.querySelector(sel); return el ? getComputedStyle(el).fontFamily : null; };
  const policesChargees = [...document.fonts].filter((f) => f.status === 'loaded').map((f) => `${f.family.replace(/"/g, '')} ${f.weight} ${f.style}`);
  const sourcesPolices = [];
  for (const feuille of document.styleSheets) {
    let regles;
    try { regles = feuille.cssRules; } catch { continue; }
    for (const r of regles) if (r.type === CSSRule.FONT_FACE_RULE) sourcesPolices.push({ famille: r.style.getPropertyValue('font-family').replace(/["']/g, ''), src: r.style.getPropertyValue('src').slice(0, 400) });
  }
  return {
    titre: document.title, description: meta('description'), langue: document.documentElement.lang || null,
    canonique: document.querySelector('link[rel="canonical"]')?.href ?? null, ogImage: meta('og:image'),
    h1: [...document.querySelectorAll('h1')].map(texte), blocs, images, svgLogos, liens, icones, jsonld,
    enTete: texte(document.querySelector('header')).slice(0, 1500), pied: texte(document.querySelector('footer')).slice(0, 3000),
    adresses: [...document.querySelectorAll('address')].map(texte),
    texteComplet: texte(document.body).slice(0, 60000),
    couleurs: Object.values(couleurs), couleursLogo, polices: { corps: police('body'), titres: police('h1, h2'), actions: police('button, a') },
    policesChargees, sourcesPolices,
  };
}

const pages = [];
const echecs = [];
const pagesEnErreur = [];
const variantes = [];
const LANGUE = /^(lang|language|langue|hl|locale|lng)$/i;
while ((file.length && pages.length < MAX) || (fileVariantes.length && variantes.length < MAX_VARIANTES)) {
  if (!(file.length && pages.length < MAX)) {
    // Variante : liens seulement, pas de capture.
    const url = fileVariantes.shift();
    try {
      const rep = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
      if (!rep || rep.status() >= 400) { pagesEnErreur.push({ url, statut: rep?.status() ?? 0 }); continue; }
      const x = new URL(url);
      const lu = await page.evaluate(() => ({ titre: document.title, h1: document.querySelector('h1')?.innerText?.trim() ?? null, langue: document.documentElement.lang || null, liens: [...document.querySelectorAll('a[href]')].map((a) => a.href) }));
      const langue = [...x.searchParams].find(([k]) => LANGUE.test(k))?.[1] ?? null;
      variantes.push({ url, chemin: x.pathname, parametres: [...x.searchParams.keys()], ...(langue ? { langue } : {}), langueDocument: lu.langue, titre: lu.titre, h1: lu.h1 });
      for (const h of lu.liens) ajouterAFile(normaliser(h));
    } catch (e) { echecs.push({ url, erreur: e.message.split('\n')[0] }); }
    continue;
  }
  const url = file.shift();
  try {
    const rep = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    const type = rep?.headers()['content-type'] ?? '';
    if (!rep || !type.includes('html')) continue;
    if (rep.status() >= 400) { pagesEnErreur.push({ url, statut: rep.status() }); continue; }
    const finale = normaliser(page.url());
    const donnees = await page.evaluate(lirePage);
    const s = slug(url);
    await page.screenshot({ path: path.join(SORTIE, 'captures', `${s}-bureau.jpg`), type: 'jpeg', quality: 70, fullPage: true });
    pages.push({ url, urlFinale: finale, statut: rep.status(), capture: `captures/${s}-bureau.jpg`, ...donnees });
    anciennesAdresses.add(new URL(url).pathname);
    for (const l of donnees.liens) ajouterAFile(normaliser(l.href));
    await new Promise((r) => setTimeout(r, 300));
  } catch (e) {
    echecs.push({ url, erreur: e.message.split('\n')[0] });
  }
}

// Captures mobiles des pages principales (accueil et liens du menu).
const mobile = await navigateur.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, locale: 'fr-FR' });
const pm = await mobile.newPage();
const principales = pages.filter((p, i) => i === 0 || pages[0].liens.some((l) => l.dansNav && normaliser(l.href) === p.url)).slice(0, 8);
for (const p of principales) {
  try {
    await pm.goto(p.url, { waitUntil: 'networkidle', timeout: 30000 });
    const s = slug(p.url);
    await pm.screenshot({ path: path.join(SORTIE, 'captures', `${s}-mobile.jpg`), type: 'jpeg', quality: 70, fullPage: true });
    p.captureMobile = `captures/${s}-mobile.jpg`;
  } catch { /* capture mobile facultative */ }
}

// Identité : faits agrégés, chacun avec ses pages sources.
const cleTelephone = (t) => t.replace(/[^0-9+]/g, '').replace(/^\+33|^0033|^33(?=\d{9}$)/, '0');
const ajoutSource = (map, cle, valeur, url) => { if (!valeur) return; map[cle] ??= { valeur, sources: [] }; if (!map[cle].sources.includes(url)) map[cle].sources.push(url); };
const telephones = {}; const emails = {}; const siren = {}; const reseaux = {}; const codesPostaux = {};
const RESEAUX = /(facebook|instagram|linkedin|youtube|tiktok|x\.com|twitter|pinterest|threads|mastodon|bsky)\./i;
for (const p of pages) {
  for (const l of p.liens) {
    if (l.href.startsWith('tel:')) ajoutSource(telephones, cleTelephone(l.href.slice(4)), l.texte || l.href.slice(4), p.url);
    if (l.href.startsWith('mailto:')) { const m = decodeURIComponent(l.href.slice(7).split('?')[0]).toLowerCase(); ajoutSource(emails, m, m, p.url); }
    if (RESEAUX.test(l.href)) { const u = l.href.split('?')[0].replace(/\/$/, ''); ajoutSource(reseaux, u, u, p.url); }
  }
  for (const m of p.texteComplet.matchAll(/(?:\+33\s?|0)[1-9](?:[\s.-]?\d{2}){4}/g)) ajoutSource(telephones, cleTelephone(m[0]), m[0], p.url);
  // Le point qui finit une phrase n'appartient pas à l'adresse.
  for (const m of p.texteComplet.matchAll(/[\w.+-]+@[\w-]+\.[\w.-]+/g)) { const a = m[0].toLowerCase().replace(/\.+$/, ''); ajoutSource(emails, a, a, p.url); }
  for (const m of p.texteComplet.matchAll(/(SIRE[NT]|RCS)[^0-9]{0,30}((?:\d[\s.]?){9}(?:(?:\d[\s.]?){5})?)/gi)) ajoutSource(siren, m[2].replace(/\D/g, ''), `${m[1].toUpperCase()} ${m[2].trim()}`, p.url);
  for (const m of (p.pied + ' ' + p.adresses.join(' ')).matchAll(/\b(\d{5})\s+([A-ZÀ-Ý][A-Za-zÀ-ÿ' -]{1,40})/g)) ajoutSource(codesPostaux, `${m[1]} ${m[2].trim()}`, `${m[1]} ${m[2].trim()}`, p.url);
}

// Couleurs du site : fusion de toutes les pages, en hexadécimal, les plus présentes d'abord.
const hex = (c) => { const m = c.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?/); if (!m || (m[4] !== undefined && Number(m[4]) < 0.5)) return null; return '#' + [m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join(''); };
const palette = {};
for (const p of pages) for (const c of p.couleurs) {
  const h = hex(c.couleur); if (!h) continue;
  palette[h] ??= { hex: h, poids: 0, roles: {} };
  palette[h].poids += c.poids;
  for (const [r, n] of Object.entries(c.roles)) palette[h].roles[r] = (palette[h].roles[r] ?? 0) + n;
}
const DEFAUT_NAVIGATEUR = new Set(['#0000ee', '#551a8b', '#ee0000']);
const couleurs = Object.values(palette).sort((a, b) => b.poids - a.poids).slice(0, 12)
  .map((c) => ({ hex: c.hex, poids: Math.round(c.poids), roleProbable: Object.entries(c.roles).sort((a, b) => b[1] - a[1])[0][0], ...(DEFAUT_NAVIGATEUR.has(c.hex) ? { note: 'couleur par défaut du navigateur (lien non stylé), pas une couleur de marque' } : {}) }));
const couleursLogo = [...new Set(pages.slice(0, 1).flatMap((p) => p.couleursLogo).map(hex).filter(Boolean))];

// Logo : candidats classés (SVG d'en-tête, image d'en-tête nommée logo, icônes, og:image, JSON-LD).
const candidats = [];
const accueil = pages[0];
if (accueil) {
  accueil.svgLogos.forEach((svg, i) => candidats.push({ type: 'svg-en-tete', svg, rang: 1 + i }));
  // Image qui renvoie à l'accueil : le logo. Nommée « logo » : probable. Autre image d'en-tête : partenaire ou décor.
  accueil.images.filter((i) => i.lienAccueil || i.indiceLogo || (i.dansEnTete && i.affichee < 400)).forEach((i) => candidats.push({
    type: i.lienAccueil ? 'logo (lien vers l\'accueil)' : i.indiceLogo ? 'image nommée logo' : 'image d\'en-tête (partenaire ou décor ?)',
    src: i.src, alt: i.alt, rang: i.lienAccueil ? 1.5 : i.indiceLogo ? 2 : 3,
  }));
  for (const j of accueil.jsonld.flatMap((x) => (Array.isArray(x) ? x : x['@graph'] ?? [x]))) {
    const l = j?.logo?.url ?? j?.logo; if (typeof l === 'string') candidats.push({ type: 'json-ld', src: l, rang: 2 });
  }
  accueil.icones.forEach((i) => candidats.push({ type: `icone ${i.rel}`, src: i.href, tailles: i.tailles, rang: 4 }));
  if (accueil.ogImage) candidats.push({ type: 'og:image', src: new URL(accueil.ogImage, origine).href, rang: 5 });
}

// Téléchargement : candidats de logo et images de contenu (dédoublonnées par empreinte).
const telecharges = {};
async function telecharger(src) {
  if (!src || src.startsWith('data:')) return null;
  if (telecharges[src] !== undefined) return telecharges[src];
  try {
    const r = await contexte.request.get(src, { timeout: 20000 });
    if (!r.ok()) return (telecharges[src] = null);
    const corps = await r.body();
    if (corps.length > 15 * 1024 * 1024) return (telecharges[src] = null);
    const empreinte = createHash('sha256').update(corps).digest('hex').slice(0, 12);
    const ext = (path.extname(new URL(src).pathname).toLowerCase().match(/^\.(svg|png|jpe?g|gif|webp|avif|ico)$/)?.[0]) ?? '.bin';
    const nom = `${path.basename(new URL(src).pathname, path.extname(new URL(src).pathname)).replace(/[^a-z0-9-]+/gi, '-').toLowerCase().slice(0, 40) || 'fichier'}-${empreinte}${ext}`;
    writeFileSync(path.join(SORTIE, 'fichiers', nom), corps);
    return (telecharges[src] = `fichiers/${nom}`);
  } catch { return (telecharges[src] = null); }
}
for (const c of candidats) {
  if (c.svg) { const nom = `logo-en-tete-${c.rang}.svg`; writeFileSync(path.join(SORTIE, 'fichiers', nom), c.svg.includes('xmlns') ? c.svg : c.svg.replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg"')); c.fichier = `fichiers/${nom}`; delete c.svg; }
  else c.fichier = await telecharger(c.src);
}
// Couleurs des logos SVG : fichiers récupérés (deux premiers candidats) et logos fournis par le client.
const couleursSvg = (texte) => [...texte.matchAll(/(?:fill|stroke|stop-color)\s*[:=]\s*["']?\s*(#[0-9a-f]{3,8}\b|rgb\([^)]*\))/gi)].map((m) => m[1]);
const versHex6 = (c) => { if (c.startsWith('rgb')) return hex(c); const h = c.slice(1).toLowerCase(); return '#' + (h.length === 3 ? h.split('').map((x) => x + x).join('') : h.slice(0, 6)); };
const sourcesLogo = [
  ...candidats.filter((c) => c.fichier && /\.svg$/.test(c.fichier)).sort((a, b) => a.rang - b.rang).slice(0, 2).map((c) => path.join(SORTIE, c.fichier)),
  ...(existsSync(path.join(FOURNIS, 'logo')) ? readdirSync(path.join(FOURNIS, 'logo')).filter((n) => /\.svg$/i.test(n)).map((n) => path.join(FOURNIS, 'logo', n)) : []),
];
for (const f of sourcesLogo) for (const c of couleursSvg(readFileSync(f, 'utf8'))) { const h = versHex6(c); if (h && !couleursLogo.includes(h)) couleursLogo.push(h); }

const images = [];
const vuesImages = new Set();
for (const p of pages) for (const i of p.images) {
  if (vuesImages.has(i.src) || i.largeur < 200 || images.length >= 200) continue;
  vuesImages.add(i.src);
  images.push({ ...i, page: p.url, fichier: await telecharger(i.src) });
}

// Fichiers fournis par le client.
const fournis = [];
if (existsSync(FOURNIS)) {
  const pf = await contexte.newPage();
  const parcourir = async (d) => {
    for (const n of readdirSync(d).sort()) {
      const f = path.join(d, n);
      if (n.startsWith('.')) continue;
      if (statSync(f).isDirectory()) { await parcourir(f); continue; }
      const e = { fichier: path.relative(process.cwd(), f), type: path.extname(n).slice(1).toLowerCase(), octets: statSync(f).size };
      if (/^(png|jpe?g|gif|webp|avif|svg)$/.test(e.type)) {
        await pf.goto(pathToFileURL(f).href).catch(() => {});
        const dim = await pf.evaluate(() => { const i = document.querySelector('img, svg'); if (!i) return null; const r = i.getBoundingClientRect(); return { largeur: i.naturalWidth ?? Math.round(r.width), hauteur: i.naturalHeight ?? Math.round(r.height) }; }).catch(() => null);
        Object.assign(e, dim ?? {});
      }
      fournis.push(e);
    }
  };
  await parcourir(FOURNIS);
}
await navigateur.close();

const inventaire = {
  source: depart, date: new Date().toISOString(), pagesLues: pages.length, limite: MAX, echecs, pagesEnErreur, variantes,
  identite: {
    logos: candidats.sort((a, b) => a.rang - b.rang),
    couleurs,
    couleursLogo,
    polices: { usages: accueil?.polices ?? null, chargees: [...new Set(pages.flatMap((p) => p.policesChargees))], sources: [...new Map(pages.flatMap((p) => p.sourcesPolices).map((s) => [s.src, s])).values()] },
    telephones: Object.values(telephones), emails: Object.values(emails), siren: Object.values(siren),
    reseaux: Object.values(reseaux), codesPostaux: Object.values(codesPostaux),
    donneesStructurees: accueil?.jsonld ?? [],
  },
  // Menu : chemin et ancre (un site d'une page navigue par #sections), sans entrée vide (lien du logo).
  menu: (accueil?.liens ?? []).filter((l) => l.dansNav && l.texte && normaliser(l.href)).map((l) => ({ texte: l.texte, chemin: new URL(l.href).pathname + new URL(l.href).search + new URL(l.href).hash })),
  pied: (accueil?.liens ?? []).filter((l) => l.dansPied && l.texte && normaliser(l.href)).map((l) => ({ texte: l.texte, chemin: new URL(l.href).pathname + new URL(l.href).search + new URL(l.href).hash })),
  anciennesAdresses: [...anciennesAdresses].filter((a) => !pagesEnErreur.some((e) => new URL(e.url).pathname === a)).sort(),
  pages: pages.map(({ liens, texteComplet, couleurs: _c, couleursLogo: _l, policesChargees, sourcesPolices, svgLogos, ...p }) => ({ ...p, mots: texteComplet.split(' ').length, liensInternes: liens.filter((l) => normaliser(l.href)).length })),
  images, fournis,
};
writeFileSync(path.join(SORTIE, 'inventaire.json'), JSON.stringify(inventaire, null, 2) + '\n');

console.log(`Audit de ${depart} : ${pages.length} page(s) lue(s)${file.length ? ` (limite ${MAX} atteinte, ${file.length} non lue(s) : --max pour en lire plus)` : ''}, ${variantes.length} variante(s) à paramètres${fileVariantes.length ? ` (${fileVariantes.length} non lue(s))` : ''}, ${echecs.length} échec(s).`);
console.log(`Logos candidats : ${candidats.filter((c) => c.fichier).length} · couleurs : ${couleurs.slice(0, 6).map((c) => c.hex).join(' ')} · polices : ${inventaire.identite.polices.chargees.slice(0, 4).join(', ') || inventaire.identite.polices.usages?.corps || 'aucune'}`);
console.log(`Téléphones : ${inventaire.identite.telephones.map((t) => t.valeur).join(', ') || 'aucun'} · e-mails : ${inventaire.identite.emails.map((t) => t.valeur).join(', ') || 'aucun'} · SIREN/SIRET : ${inventaire.identite.siren.map((t) => t.valeur).join(', ') || 'aucun'}`);
console.log(`Images : ${images.length} · fichiers fournis : ${fournis.length} · écrit ${path.relative(process.cwd(), path.join(SORTIE, 'inventaire.json'))}`);
