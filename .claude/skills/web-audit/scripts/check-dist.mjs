#!/usr/bin/env node
/**
 * Audit statique d'un site construit (dist/) : SEO, JSON-LD, accessibilité de base,
 * liens locaux, variables CSS orphelines, ressources tierces, poids par page.
 * Indexation (rule web-seo) : indexable ⇔ dans sitemap.xml ⇔ canonique = <loc> ⇔ sans noindex ;
 * robots.txt n'interdit aucune URL du sitemap ; pages orphelines ; hreflang réciproques ; liens de llms.txt.
 * Sans dépendance. Usage : node check-dist.mjs [dist] [https://example.com]
 * Sortie 1 si au moins une erreur.
 */
import fs from 'node:fs';
import path from 'node:path';
import { gzipSync } from 'node:zlib';

const DIST = path.resolve(process.argv[2] || 'dist');
const SITE = (process.argv[3] || '').replace(/\/$/, '');
const BUDGET_KO = 500;

const errors = [];
const warnings = [];
const err = (file, msg) => errors.push(`${file}: ${msg}`);
const warn = (file, msg) => warnings.push(`${file}: ${msg}`);

if (!fs.existsSync(DIST)) {
  console.error(`Dossier introuvable : ${DIST} (lancer le build d'abord)`);
  process.exit(2);
}

const walk = (dir) => fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
  const p = path.join(dir, e.name);
  return e.isDirectory() ? walk(p) : [p];
});
const files = walk(DIST);
const htmlFiles = files.filter((f) => f.endsWith('.html'));
const rel = (f) => path.relative(DIST, f);

const decode = (s) => s
  .replace(/&nbsp;/g, ' ')
  .replace(/&#x([0-9a-f]+);/gi, (_, h) => String.fromCodePoint(parseInt(h, 16)))
  .replace(/&#(\d+);/g, (_, d) => String.fromCodePoint(Number(d)))
  .replace(/&quot;/g, '"').replace(/&apos;/g, "'")
  .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
const norm = (s) => decode(s).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
const attr = (tag, name) => {
  const m = tag.match(new RegExp(`\\s${name}\\s*=\\s*("([^"]*)"|'([^']*)'|([^\\s>]+))`, 'i'));
  return m ? decode(m[2] ?? m[3] ?? m[4] ?? '') : null;
};
const tags = (html, name) => html.match(new RegExp(`<${name}\\b[^>]*>`, 'gi')) || [];
const meta = (html, key) => {
  const t = tags(html, 'meta').find((m) => attr(m, 'name') === key || attr(m, 'property') === key);
  return t ? attr(t, 'content') : null;
};

const isLocal = (url) => url && !/^(?:[a-z]+:)?\/\//i.test(url) && !/^(?:data|mailto|tel|javascript):|^#/i.test(url);
const isOwnAbsolute = (url) => SITE && url.startsWith(SITE);
const resolveLocal = (fromFile, url) => {
  const clean = url.split(/[?#]/)[0];
  if (!clean) return null;
  const base = clean.startsWith('/') ? path.join(DIST, clean) : path.join(path.dirname(fromFile), clean);
  const candidates = [base, path.join(base, 'index.html'), `${base}.html`];
  return candidates.find((c) => fs.existsSync(c) && fs.statSync(c).isFile()) || null;
};

/* Dimensions d'une image PNG, JPEG ou WebP lues dans l'en-tête du fichier (null si format inconnu). */
const imageSize = (file) => {
  const b = fs.readFileSync(file);
  if (b.length > 24 && b.toString('ascii', 1, 4) === 'PNG') return { w: b.readUInt32BE(16), h: b.readUInt32BE(20) };
  if (b[0] === 0xff && b[1] === 0xd8) {
    for (let i = 2; i + 9 < b.length;) {
      if (b[i] !== 0xff) { i += 1; continue; }
      const marker = b[i + 1];
      if (marker >= 0xc0 && marker <= 0xcf && ![0xc4, 0xc8, 0xcc].includes(marker)) return { w: b.readUInt16BE(i + 7), h: b.readUInt16BE(i + 5) };
      i += 2 + b.readUInt16BE(i + 2);
    }
    return null;
  }
  if (b.length > 30 && b.toString('ascii', 0, 4) === 'RIFF' && b.toString('ascii', 8, 12) === 'WEBP') {
    const chunk = b.toString('ascii', 12, 16);
    if (chunk === 'VP8X') return { w: 1 + b.readUIntLE(24, 3), h: 1 + b.readUIntLE(27, 3) };
    if (chunk === 'VP8L') { const v = b.readUInt32LE(21); return { w: 1 + (v & 0x3fff), h: 1 + ((v >> 14) & 0x3fff) }; }
    if (chunk === 'VP8 ') return { w: b.readUInt16LE(26) & 0x3fff, h: b.readUInt16LE(28) & 0x3fff };
  }
  return null;
};

/* Adresse publique d'un fichier construit : a/index.html → /a/, a.html → /a.html. */
const urlPath = (file) => '/' + rel(file).split(path.sep).join('/').replace(/(^|\/)index\.html$/, '$1');

const pages = new Map();
const titles = new Map();
const descriptions = new Map();
const cssDefined = new Set();
const cssUsed = new Map();
const report = [];

const collectCss = (css, file) => {
  for (const m of css.matchAll(/(--[\w-]+)\s*:/g)) cssDefined.add(m[1]);
  for (const m of css.matchAll(/var\(\s*(--[\w-]+)\s*(,)?/g)) {
    if (!cssUsed.has(m[1])) cssUsed.set(m[1], { file, fallback: Boolean(m[2]) });
  }
};
files.filter((f) => f.endsWith('.css')).forEach((f) => collectCss(fs.readFileSync(f, 'utf8'), rel(f)));

// Tirets cadratin et demi-cadratin interdits, y compris échappés (rule web-method, « Tirets »).
const DASH = /[\u2013\u2014]|&[mn]dash;|&#(?:8211|8212|x201[34]);|\\201[34]/i;
files.filter((f) => /\.(?:html|css|txt|xml|json|js|mjs)$/.test(f)).forEach((f) => {
  const lines = fs.readFileSync(f, 'utf8').split('\n');
  const i = lines.findIndex((l) => DASH.test(l));
  if (i >= 0) err(rel(f), `tiret cadratin ou demi-cadratin (ligne ${i + 1}) : ${lines[i].trim().slice(0, 80)}`);
});

for (const file of htmlFiles) {
  const name = rel(file);
  const html = fs.readFileSync(file, 'utf8');
  const robots = (meta(html, 'robots') || '').toLowerCase();
  const noindex = robots.includes('noindex') || /^404|\/404/.test(name);

  const page = { name, noindex, metaNoindex: robots.includes('noindex'), canonical: null, links: new Set(), alternates: [], breadcrumb: false };
  pages.set(file, page);

  // Langue et titres
  const htmlTag = tags(html, 'html')[0] || '';
  if (!attr(htmlTag, 'lang')) err(name, '<html> sans attribut lang');
  const h1 = (html.match(/<h1\b/gi) || []).length;
  if (h1 !== 1) err(name, `${h1} élément(s) h1 (attendu : 1)`);
  const levels = [...html.matchAll(/<h([1-6])\b/gi)].map((m) => Number(m[1]));
  levels.forEach((lv, i) => { if (i && lv > levels[i - 1] + 1) warn(name, `saut de titre h${levels[i - 1]} vers h${lv}`); });
  if (!/<main\b/i.test(html)) warn(name, 'pas de <main>');

  // SEO
  const title = norm((html.match(/<title>([\s\S]*?)<\/title>/i) || [])[1] || '');
  if (!title) err(name, 'title absent');
  else if (title.length > 60) warn(name, `title de ${title.length} caractères (> 60)`);
  const desc = meta(html, 'description');
  if (!desc) (noindex ? warn : err)(name, 'meta description absente');
  else if (desc.length < 140 || desc.length > 160) warn(name, `description de ${desc.length} caractères (140 à 160 conseillés)`);
  if (!noindex) {
    if (title) titles.set(title, [...(titles.get(title) || []), name]);
    if (desc) descriptions.set(desc, [...(descriptions.get(desc) || []), name]);
    const canonical = attr(tags(html, 'link').find((l) => attr(l, 'rel') === 'canonical') || '', 'href');
    page.canonical = canonical;
    if (!canonical) err(name, 'canonical absent');
    else if (!/^https:\/\//.test(canonical)) err(name, `canonical non absolu en https : ${canonical}`);
    else if (SITE && !canonical.startsWith(SITE)) warn(name, `canonical hors du domaine ${SITE} : ${canonical}`);
    const ogImage = meta(html, 'og:image');
    if (!ogImage) warn(name, 'og:image absente');
    else if (!/^https:\/\//.test(ogImage)) err(name, `og:image non absolue : ${ogImage}`);
    else if (isOwnAbsolute(ogImage)) {
      const img = resolveLocal(file, ogImage.slice(SITE.length) || '/');
      const size = img && imageSize(img);
      if (!img) err(name, `og:image introuvable dans ${path.basename(DIST)} : ${ogImage}`);
      else if (size && (size.w !== 1200 || size.h !== 630)) warn(name, `og:image de ${size.w}×${size.h} (1200×630 attendu) : ${ogImage}`);
    }
    for (const k of ['og:title', 'og:description', 'og:url']) if (!meta(html, k)) warn(name, `${k} absent`);
  }
  if (!tags(html, 'meta').some((m) => attr(m, 'name') === 'viewport')) err(name, 'meta viewport absente');
  tags(html, 'link').filter((l) => attr(l, 'rel') === 'alternate' && attr(l, 'hreflang')).forEach((l) => page.alternates.push({ lang: attr(l, 'hreflang'), href: attr(l, 'href') }));

  // Images
  tags(html, 'img').forEach((img) => {
    const src = attr(img, 'src') || '?';
    if (attr(img, 'alt') === null) err(name, `img sans alt : ${src}`);
    if (!attr(img, 'width') || !attr(img, 'height')) warn(name, `img sans width/height (risque CLS) : ${src}`);
  });

  // Liens locaux, ressources tierces, poids
  let weight = Buffer.byteLength(html);
  let requests = 1;
  const seen = new Set();
  const refs = [...html.matchAll(/\s(?:href|src|poster)\s*=\s*"([^"]+)"/gi)].map((m) => decode(m[1]));
  const srcsets = [...html.matchAll(/\ssrcset\s*=\s*"([^"]+)"/gi)].flatMap((m) => decode(m[1]).split(',').map((s) => s.trim().split(/\s+/)[0]));
  for (const url of [...refs, ...srcsets]) {
    let local = url;
    if (isOwnAbsolute(url)) local = url.slice(SITE.length) || '/';
    if (!isLocal(local)) continue;
    const target = resolveLocal(file, local);
    if (!target) {
      err(name, `lien local cassé : ${url}`);
      continue;
    }
    if (target.endsWith('.html') && target !== file) page.links.add(target);
    if (/\.(?:css|js|mjs|woff2?|webp|avif|png|jpe?g|gif|svg|ico|mp4|webm)$/i.test(target) && !seen.has(target)) {
      seen.add(target);
      requests += 1;
      weight += fs.statSync(target).size;
    }
  }
  const loaded = [
    ...tags(html, 'script').map((t) => attr(t, 'src')),
    ...tags(html, 'link').filter((t) => /stylesheet|preload|modulepreload|preconnect|dns-prefetch|icon/i.test(attr(t, 'rel') || '')).map((t) => attr(t, 'href')),
    ...tags(html, 'img').map((t) => attr(t, 'src')),
    ...tags(html, 'iframe').map((t) => attr(t, 'src')),
    ...[...html.matchAll(/url\(\s*['"]?(https?:\/\/[^'")]+)/gi)].map((m) => m[1]),
  ];
  loaded.filter((u) => u && /^(?:https?:)?\/\//i.test(u) && !isOwnAbsolute(u))
    .forEach((u) => warn(name, `ressource tierce chargée : ${u}`));
  const ko = Math.round(weight / 102.4) / 10;
  report.push({ page: name, ko, gzipHtmlKo: Math.round(gzipSync(html).length / 102.4) / 10, requests });
  if (ko > BUDGET_KO) warn(name, `poids local ${ko} Ko > budget ${BUDGET_KO} Ko (non compressé, toutes images comprises)`);

  // CSS inline
  for (const m of html.matchAll(/<style\b[^>]*>([\s\S]*?)<\/style>/gi)) collectCss(m[1], name);
  for (const m of html.matchAll(/\sstyle\s*=\s*"([^"]*)"/gi)) collectCss(decode(m[1]), name);

  // JSON-LD
  const visible = norm(html.replace(/<script\b[\s\S]*?<\/script>/gi, ' ').replace(/<style\b[\s\S]*?<\/style>/gi, ' ').replace(/<head\b[\s\S]*?<\/head>/i, ' '));
  for (const m of html.matchAll(/<script[^>]*type\s*=\s*"application\/ld\+json"[^>]*>([\s\S]*?)<\/script>/gi)) {
    let data;
    try { data = JSON.parse(m[1]); } catch (e) { err(name, `JSON-LD invalide : ${e.message}`); continue; }
    const nodes = [];
    const visit = (n) => {
      if (Array.isArray(n)) return n.forEach(visit);
      if (!n || typeof n !== 'object') return;
      if (n['@id'] && Object.keys(n).some((k) => !k.startsWith('@'))) nodes.push(n);
      Object.values(n).forEach(visit);
    };
    visit(data);
    const ids = new Map();
    nodes.forEach((n) => ids.set(n['@id'], (ids.get(n['@id']) || 0) + 1));
    ids.forEach((count, id) => { if (count > 1) err(name, `JSON-LD : @id défini ${count} fois : ${id}`); });
    const all = [];
    const flat = (n) => { if (Array.isArray(n)) n.forEach(flat); else if (n && typeof n === 'object') { all.push(n); Object.values(n).forEach(flat); } };
    flat(data);
    all.forEach((n) => {
      const types = [].concat(n['@type'] || []);
      if (types.includes('BreadcrumbList')) page.breadcrumb = true;
      if (types.some((t) => /^(Review|AggregateRating)$/.test(t))) warn(name, `JSON-LD ${types.join(',')} : vérifier que des avis réels sont affichés`);
      for (const [k, v] of Object.entries(n)) if (v === 'true' || v === 'false') err(name, `JSON-LD ${k} : booléen écrit en chaîne`);
      if (types.includes('FAQPage')) {
        [].concat(n.mainEntity || []).forEach((q) => {
          const question = norm(q.name || '');
          const answer = norm([].concat(q.acceptedAnswer || [])[0]?.text || '');
          if (question && !visible.includes(question)) err(name, `FAQPage : question absente du texte visible : « ${question.slice(0, 70)} »`);
          if (answer && !visible.includes(answer)) err(name, `FAQPage : réponse absente du texte visible : « ${answer.slice(0, 70)} »`);
        });
      }
    });
  }
}

titles.forEach((pages, t) => { if (pages.length > 1) err(pages.join(', '), `title dupliqué : ${t}`); });
descriptions.forEach((pages) => { if (pages.length > 1) err(pages.join(', '), 'meta description dupliquée'); });
cssUsed.forEach(({ file, fallback }, v) => {
  if (!cssDefined.has(v)) (fallback ? warn : err)(file, `var(${v}) utilisée mais jamais définie${fallback ? ' (repli présent)' : ''}`);
});

// Page d'erreur
const page404 = [...pages.entries()].find(([f]) => /^404(\/index)?\.html$/.test(rel(f).split(path.sep).join('/')));
if (!page404) err('404.html', 'absente (page d\'erreur au gabarit du site obligatoire)');
else if (!page404[1].metaNoindex) err(page404[1].name, 'page 404 sans meta robots noindex');

// Fil d'Ariane : toute page indexable sauf l'accueil
pages.forEach((p, f) => {
  if (!p.noindex && urlPath(f) !== '/' && !p.breadcrumb) err(p.name, 'BreadcrumbList absent (fil d\'Ariane obligatoire hors accueil)');
});

// Pages orphelines : aucune autre page n'y mène
const linked = new Set([...pages.values()].flatMap((p) => [...p.links]));
pages.forEach((p, f) => {
  if (!p.noindex && urlPath(f) !== '/' && !linked.has(f)) warn(p.name, 'page orpheline : aucun lien depuis une autre page (le sitemap ne suffit pas)');
});

// hreflang réciproques et x-default
const pageByUrl = (url) => (SITE && url && url.startsWith(SITE) ? resolveLocal(path.join(DIST, 'index.html'), url.slice(SITE.length) || '/') : null);
pages.forEach((p) => {
  if (!p.alternates.length) return;
  if (!p.alternates.some((a) => a.lang === 'x-default')) err(p.name, 'hreflang sans x-default');
  if (!SITE) return;
  p.alternates.forEach((a) => {
    const target = pageByUrl(a.href);
    if (!target) { if (a.href.startsWith(SITE)) err(p.name, `hreflang ${a.lang} vers une page absente : ${a.href}`); return; }
    const back = pages.get(target);
    if (back && p.canonical && !back.alternates.some((b) => b.href === p.canonical)) err(p.name, `hreflang ${a.lang} non réciproque : ${a.href} ne renvoie pas vers ${p.canonical}`);
  });
});

// Fichiers d'indexation
const robotsPath = path.join(DIST, 'robots.txt');
const robotsTxt = fs.existsSync(robotsPath) ? fs.readFileSync(robotsPath, 'utf8') : null;
let robotsRules = [];
if (robotsTxt === null) err('robots.txt', 'absent');
else {
  if (!/^Sitemap:\s*https:\/\//mi.test(robotsTxt)) err('robots.txt', 'ligne Sitemap: absolue absente');
  if (!/^Content-Signal:/mi.test(robotsTxt)) warn('robots.txt', 'Content-Signal absent (usage du contenu par les IA, valeur décidée à l\'interview)');
  // Règles du groupe « User-agent: * » ; la plus longue l'emporte, Allow à longueur égale.
  let agents = [];
  let inRules = false;
  for (const line of robotsTxt.split(/\r?\n/).map((l) => l.replace(/#.*/, '').trim()).filter(Boolean)) {
    const [, key, value] = line.match(/^([\w-]+)\s*:\s*(.*)$/) || [];
    if (!key) continue;
    if (/^user-agent$/i.test(key)) { if (inRules) agents = []; agents.push(value); inRules = false; continue; }
    if (/^(allow|disallow)$/i.test(key)) {
      inRules = true;
      if (agents.includes('*') && value) robotsRules.push({ allow: /^allow$/i.test(key), len: value.length, re: new RegExp('^' + value.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*').replace(/\\\$$/, '$')) });
    }
  }
}
const robotsBlocks = (p) => {
  const match = robotsRules.filter((r) => r.re.test(p)).sort((a, b) => b.len - a.len || b.allow - a.allow)[0];
  return Boolean(match && !match.allow);
};
pages.forEach((p, f) => {
  if (p.metaNoindex && robotsBlocks(urlPath(f))) warn(p.name, 'noindex jamais lu : robots.txt interdit son exploration');
});

const sitemapPath = path.join(DIST, 'sitemap.xml');
if (!fs.existsSync(sitemapPath)) err('sitemap.xml', 'absent');
else {
  const xml = fs.readFileSync(sitemapPath, 'utf8');
  const locs = [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => decode(m[1].trim()));
  if (!locs.length) err('sitemap.xml', 'aucune URL');
  new Set(locs.filter((l, i) => locs.indexOf(l) !== i)).forEach((l) => err('sitemap.xml', `URL en double : ${l}`));
  const lastmods = [...xml.matchAll(/<lastmod>([^<]+)<\/lastmod>/g)].map((m) => m[1].trim().slice(0, 10));
  if (lastmods.length > 3 && new Set(lastmods).size === 1) warn('sitemap.xml', `lastmod identique partout (${lastmods[0]}) : date du build au lieu de la date du contenu ?`);
  const today = new Date().toISOString().slice(0, 10);
  lastmods.filter((d) => d > today).forEach((d) => err('sitemap.xml', `lastmod dans le futur : ${d}`));
  const locSet = new Set(locs);
  locs.forEach((loc) => {
    if (!/^https:\/\//.test(loc)) err('sitemap.xml', `loc non absolu : ${loc}`);
    if (SITE && loc.startsWith(SITE)) {
      const target = pageByUrl(loc);
      if (robotsBlocks(loc.slice(SITE.length) || '/')) err('robots.txt', `interdit l'exploration de ${loc}, pourtant dans le sitemap`);
      if (!target) err('sitemap.xml', `URL sans page construite : ${loc}`);
      else if (target.endsWith('.html')) {
        const p = pages.get(target);
        if (p.metaNoindex) err('sitemap.xml', `URL en noindex : ${loc}`);
        else if (p.canonical && p.canonical !== loc) err(p.name, `canonique ${p.canonical} différente de son <loc> ${loc}`);
      }
    }
  });
  if (SITE) {
    pages.forEach((p) => {
      if (p.noindex || !p.canonical || !p.canonical.startsWith(SITE)) return;
      if (!locSet.has(p.canonical)) err(p.name, `indexable mais hors sitemap : ${p.canonical} (l'ajouter au sitemap, ou noindex)`);
    });
  }
}

const llmsPath = path.join(DIST, 'llms.txt');
if (!fs.existsSync(llmsPath)) warn('llms.txt', 'absent (découverte par assistants IA)');
else if (SITE) {
  for (const [, url] of fs.readFileSync(llmsPath, 'utf8').matchAll(/\]\((https?:\/\/[^)\s]+)\)/g)) {
    if (url.startsWith(SITE) && !pageByUrl(url.split('#')[0])) err('llms.txt', `lien vers une page absente : ${url}`);
  }
}
if (!SITE) warn('(appel)', 'domaine non fourni : cohérence sitemap, canonique, hreflang et llms.txt non contrôlée (node check-dist.mjs dist https://domaine)');

console.log('\nPoids par page (fichiers locaux, non compressés)');
console.table(report);
warnings.forEach((w) => console.log(`  ! ${w}`));
errors.forEach((e) => console.log(`  ✗ ${e}`));
console.log(`\n${errors.length} erreur(s), ${warnings.length} avertissement(s) sur ${htmlFiles.length} page(s).`);
process.exit(errors.length ? 1 : 0);
