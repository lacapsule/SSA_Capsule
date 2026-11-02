// Contrôle du rendu de chaque page, dans les deux thèmes : ce qu'un test fonctionnel et axe ne voient pas.
// Usage : node check-rendu.mjs <url de base> <rendu-pages.json> [dossier de captures]
// rendu-pages.json : { "login": { "path", "fill": { sélecteur: valeur }, "submit", "after": { "contains", "fill", "submit" } }, "pages": ["/chemin/"] }
// Contrôles :
//  1. thèmes : même géométrie et même typographie en clair et en sombre (seules les couleurs changent) ;
//  2. filets : dans un même conteneur, les filets horizontaux ont les mêmes bords ; dans une ligne de tableau, toutes les cellules ont le même ;
//  3. hiérarchie : titre de page > titre de section > libellé de champ, écart visible de taille ou de graisse ;
//  4. barre d'enregistrement : invisible sans modification, visible et collante après une modification ;
//  5. débordement horizontal à 320, 360, 1280 et 2560 px ;
//  7. tableau de back-office avec ascenseur, texte d'une scène claire (mur) illisible dans un thème ;
//  6. bloc décalé de sa place (position relative avec top ou left résiduel), lien d'en-tête sur deux lignes, zone défilante ouverte sur du vide.
// Écrit les captures de chaque page, à regarder ensuite : un script ne juge pas une mise en page.
import { readFileSync, mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(process.cwd() + '/');
let chromium;
try { ({ chromium } = require('playwright-core')); } catch { ({ chromium } = await import('playwright-core')); }

const [base, file, shots] = process.argv.slice(2);
if (!base || !file) {
  console.error('Usage : node check-rendu.mjs <url de base> <rendu-pages.json> [dossier de captures]');
  process.exit(2);
}
const conf = JSON.parse(readFileSync(file, 'utf8'));
if (shots) mkdirSync(shots, { recursive: true });
const errors = [];
const err = (page, message) => errors.push(`${page} : ${message}`);

const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
const open = async (width, height) => {
  const page = await (await browser.newContext({ viewport: { width, height }, reducedMotion: 'reduce' })).newPage();
  if (conf.login) {
    await page.goto(base + conf.login.path);
    for (const [s, v] of Object.entries(conf.login.fill)) await page.fill(s, v);
    await Promise.all([page.waitForNavigation(), page.click(conf.login.submit)]);
    const after = conf.login.after;
    if (after && page.url().includes(after.contains)) {
      for (const [s, v] of Object.entries(after.fill)) await page.fill(s, v);
      await Promise.all([page.waitForNavigation(), page.click(after.submit)]);
    }
  }
  return page;
};
const setTheme = (page, t) => page.evaluate((x) => { document.documentElement.dataset.theme = x; }, t);

// Empreinte de ce qui ne doit pas dépendre du thème.
const shape = () => [...document.querySelectorAll('.side *, main *')].filter((el) => el.getClientRects().length && !el.closest('svg')).map((el) => {
  const c = getComputedStyle(el);
  const r = el.getBoundingClientRect();
  const name = `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}${el.classList[0] ? '.' + el.classList[0] : ''}`;
  return { name, text: (el.textContent || '').trim().slice(0, 30), v: [c.fontSize, c.fontWeight, c.letterSpacing, c.textTransform, c.borderTopWidth, c.borderBottomWidth, c.borderLeftWidth, c.borderRightWidth, c.paddingTop, c.paddingLeft, Math.round(r.width), Math.round(r.height), Math.round(r.left), Math.round(r.top)].join('|') };
});

// Filets : bords de tous les traits horizontaux, par conteneur.
const rules = () => {
  const out = [];
  const lines = new Map();
  document.querySelectorAll('main *').forEach((el) => {
    if (!el.getClientRects().length || el.closest('svg, .frame-demo, .frame, dialog, .savebar')) return;
    const c = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    const box = parseFloat(c.borderLeftWidth) > 0 && parseFloat(c.borderRightWidth) > 0;
    if (box || r.width < 120) return;
    const top = parseFloat(c.borderTopWidth) > 0 && c.borderTopStyle !== 'none';
    const bottom = parseFloat(c.borderBottomWidth) > 0 && c.borderBottomStyle !== 'none';
    if (!top && !bottom) return;
    const scope = el.closest('.inbox > *, [role="tabpanel"], .card-section, .card, section, form') || document.body;
    if (!lines.has(scope)) lines.set(scope, new Map());
    const inTable = el.closest('tr');
    const left = Math.round((inTable ? inTable.getBoundingClientRect() : r).left);
    const right = Math.round((inTable ? inTable.getBoundingClientRect() : r).right);
    const k = `${left}-${right}`;
    if (!lines.get(scope).has(k)) lines.get(scope).set(k, el.tagName.toLowerCase() + (el.classList[0] ? '.' + el.classList[0] : ''));
  });
  lines.forEach((m) => { if (m.size > 1) out.push(`filets de longueurs différentes : ${[...m].map(([k, n]) => `${n} ${k}`).join(', ')}`); });
  document.querySelectorAll('main tr').forEach((tr) => {
    if (!tr.getClientRects().length) return;
    const widths = new Set([...tr.cells].map((c) => { const s = getComputedStyle(c); return `${s.borderBottomWidth} ${s.borderBottomColor}`; }));
    if (widths.size > 1) out.push(`filet interrompu dans une ligne de tableau : « ${tr.textContent.trim().slice(0, 30)} »`);
  });
  // Cartes d'une même rangée : même nombre de lignes pour le même rôle.
  document.querySelectorAll('main .kpis').forEach((row) => {
    const lines = new Set([...row.querySelectorAll('.vs')].map((v) => Math.round(v.getBoundingClientRect().height)));
    if (lines.size > 1) out.push('cartes d\'une même rangée : textes sur un nombre de lignes différent');
  });
  // Un titre de section ne doit pas être traversé par un filet : ligne qui démarre à sa droite.
  document.querySelectorAll('main fieldset').forEach((f) => {
    const l = f.querySelector(':scope > legend');
    if (!l || !l.getClientRects().length || l.classList.contains('sr')) return;
    const c = getComputedStyle(f);
    if (parseFloat(c.borderTopWidth) > 0 && getComputedStyle(l).float === 'none' && getComputedStyle(f).display !== 'contents') out.push(`filet qui traverse le titre « ${l.textContent.trim().slice(0, 30)} »`);
  });
  return out;
};

// Hiérarchie : chaque niveau se distingue du suivant (taille d'au moins 10 % ou graisse d'au moins 100).
const hierarchy = () => {
  const out = [];
  const read = (el) => { const c = getComputedStyle(el); return { s: parseFloat(c.fontSize), w: parseInt(c.fontWeight, 10) }; };
  const visible = (s) => [...document.querySelectorAll(s)].find((e) => e.getClientRects().length && !e.classList.contains('sr'));
  const levels = [['h1', 'main h1'], ['h2', 'main h2'], ['titre de section', 'main fieldset > legend, main .card-section h3, main .frame-group-title'], ['libellé', 'main .field > label']];
  const present = levels.map(([n, s]) => [n, visible(s)]).filter(([, e]) => e);
  for (let i = 1; i < present.length; i++) {
    const [a, b] = [read(present[i - 1][1]), read(present[i][1])];
    if (!(a.s >= b.s * 1.1)) out.push(`${present[i - 1][0]} (${a.s} px, ${a.w}) trop proche de ${present[i][0]} (${b.s} px, ${b.w})`);
  }
  const grp = visible('.settings-nav .grp');
  const item = visible('.settings-nav a');
  if (grp && item) {
    const [a, b] = [getComputedStyle(grp), getComputedStyle(item)];
    if (a.textTransform === b.textTransform && a.letterSpacing === b.letterSpacing) out.push('rubrique et sous-rubrique du menu sans différence de style');
  }
  return out;
};

const page = await open(1280, 900);
for (const path of conf.pages) {
  await page.goto(base + path, { waitUntil: 'networkidle' });
  await page.evaluate(() => { document.querySelectorAll('[data-note]').forEach((n) => n.remove()); });
  await setTheme(page, 'dark');
  const dark = await page.evaluate(shape);
  await setTheme(page, 'light');
  const light = await page.evaluate(shape);
  if (dark.length !== light.length) err(path, `thèmes : ${dark.length} et ${light.length} éléments visibles`);
  else {
    const diff = dark.filter((d, i) => d.v !== light[i].v);
    if (diff.length) err(path, `thèmes : ${diff.length} élément(s) différent(s) hors couleur, dont ${diff[0].name} « ${diff[0].text} »`);
  }
  for (const m of await page.evaluate(rules)) err(path, m);
  for (const m of await page.evaluate(hierarchy)) err(path, m);
  const bar = page.locator('[data-savebar]').first();
  if (await bar.count()) {
    if (await bar.isVisible()) err(path, 'barre d\'enregistrement visible sans modification');
    const field = page.locator('[data-savebar]').first().locator('xpath=ancestor::form').locator('input:visible:not([type=checkbox]):not([type=radio]):not([type=color]):not([type=file]), textarea:visible').first();
    if (await field.count()) {
      const old = await field.inputValue();
      await field.fill(old + '1');
      const m = await bar.evaluate((b) => ({ shown: b.getClientRects().length > 0, pos: getComputedStyle(b).position }));
      if (!m.shown || m.pos !== 'sticky') err(path, `barre d'enregistrement après modification : ${JSON.stringify(m)}`);
      await field.fill(old);
    }
  }
  if (shots) {
    const name = path.replace(/\W+/g, '_');
    for (const t of ['dark', 'light']) {
      await setTheme(page, t);
      await page.screenshot({ path: `${shots}/1280-${t}${name}.png`, fullPage: true });
    }
  }
}
const placement = () => {
  const out = [];
  document.querySelectorAll('main .table-wrap').forEach((t) => {
    if (!t.getClientRects().length || t.closest('details') || t.closest('.prose')) return;
    if (t.scrollWidth > t.clientWidth + 1 || t.scrollHeight > t.clientHeight) out.push(`ascenseur dans un tableau (${t.scrollWidth - t.clientWidth} x ${t.scrollHeight - t.clientHeight} px)`);
  });
  // Texte posé directement sur un mur : la scène reste claire dans les deux thèmes.
  const lum = (c) => { const m = c.match(/[\d.]+/g).map(Number); const f = (v) => { const s = v / 255; return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(m[0]) + 0.7152 * f(m[1]) + 0.0722 * f(m[2]); };
  document.querySelectorAll('.hero-wall *, .wall *').forEach((el) => {
    if (!el.getClientRects().length || ![...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim())) return;
    for (let p = el; p && !p.matches('.hero-wall, .wall'); p = p.parentElement) { if (getComputedStyle(p).backgroundColor !== 'rgba(0, 0, 0, 0)' || getComputedStyle(p).backgroundImage !== 'none') return; }
    if ((lum('rgb(230, 221, 205)') + 0.05) / (lum(getComputedStyle(el).color) + 0.05) < 3) out.push(`texte illisible sur le mur : « ${el.textContent.trim().slice(0, 20)} »`);
  });
  document.querySelectorAll('main *, header a').forEach((el) => {
    if (!el.getClientRects().length || el.closest('svg, [aria-hidden="true"]')) return;
    const c = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    if (c.position === 'relative' && r.height > 40 && r.width > 120 && (Math.abs(parseFloat(c.top) || 0) > 8 || Math.abs(parseFloat(c.left) || 0) > 8)) out.push(`bloc décalé de sa place : ${el.tagName.toLowerCase()}.${el.classList[0] || ''} (top ${c.top})`);
    if (el.matches('header a') && innerWidth >= 1101 && el.textContent.trim() && el.getBoundingClientRect().height > 60) out.push(`lien d'en-tête sur deux lignes : « ${el.textContent.trim().slice(0, 30)} »`);
    if (el.matches('[data-chart]') && el.scrollWidth > el.clientWidth + 1 && el.scrollLeft < el.scrollWidth - el.clientWidth - 1) out.push('graphique ouvert sur sa partie ancienne');
  });
  return [...new Set(out)];
};
for (const [w, h] of [[320, 640], [360, 780], [1024, 768], [1280, 900], [2560, 1440]]) {
  const p = await open(w, h);
  for (const path of conf.pages) {
    await p.goto(base + path);
    const over = await p.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    if (over > 0) err(path, `débordement de ${over} px à ${w} px`);
    await setTheme(p, 'dark');
    for (const m of await p.evaluate(placement)) err(path, `${m} (${w} px)`);
    if (shots && w === 360) await p.screenshot({ path: `${shots}/360${path.replace(/\W+/g, '_')}.png`, fullPage: true });
  }
}
await browser.close();
errors.forEach((e) => console.log('ÉCHEC', e));
console.log(`${conf.pages.length} pages, ${errors.length} écart(s).`);
if (shots) console.log(`Captures à regarder : ${shots}`);
process.exit(errors.length ? 1 : 0);
