#!/usr/bin/env node
// Extrait les contenus publiés du site actuel (décision du 2026-11-13 : import depuis les pages publiques) :
// articles, événements à venir affichés à l'accueil, galerie, logos des partenaires et financeurs.
// Écrit docs/source/existant/contenus.json et télécharge les médias dans docs/source/existant/medias/ (même chemin
// que sur le site). Ne lit que des pages publiques ; aucune donnée personnelle. Relançable : un média déjà
// téléchargé n'est pas repris.
//   node scripts/extraire-site-actuel.mjs [https://ssapaysdemorlaix.fr]
import { createRequire } from 'node:module';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const RACINE = path.resolve(import.meta.dirname, '..');
const SITE = (process.argv[2] ?? 'https://ssapaysdemorlaix.fr').replace(/\/$/, '');
const SORTIE = path.join(RACINE, 'docs/source/existant');
const MEDIAS = path.join(SORTIE, 'medias');
const inventaire = JSON.parse(readFileSync(path.join(SORTIE, 'inventaire.json'), 'utf8'));

const { chromium } = createRequire(RACINE + '/')('playwright-core');
const navigateur = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
const page = await navigateur.newPage();

async function ouvrir(chemin) {
  const r = await page.goto(SITE + chemin, { waitUntil: 'domcontentloaded' });
  if (!r || r.status() !== 200) throw new Error(`${chemin} : statut ${r?.status()}`);
}

const medias = new Set();
const garder = (src) => { if (src && src.startsWith('/')) medias.add(src.split('?')[0]); return src; };

// Articles : les adresses /article/<n> relevées par l'audit.
const numeros = [...new Set(inventaire.pages.map((p) => new URL(p.url).pathname).filter((p) => /^\/article\/\d+$/.test(p)))]
  .map((p) => Number(p.split('/').pop())).sort((a, b) => a - b);
const articles = [];
for (const n of numeros) {
  await ouvrir(`/article/${n}`);
  const a = await page.evaluate(() => {
    const txt = (s) => document.querySelector(s)?.textContent.trim() ?? '';
    const date = txt('main .date').match(/(\d{4}-\d{2}-\d{2})(?:\s*à\s*(\d{2}:\d{2}))?/);
    return {
      titre: txt('main h1'),
      resume: txt('main .details-content h3'),
      description: document.querySelector('main .description')?.innerText.trim() ?? '',
      date: date?.[1] ?? '',
      heure: date?.[2] ?? '',
      lieu: txt('main .lieu').replace(/^Lieu\s*:\s*/, ''),
      liens: [...document.querySelectorAll('main .liens-list li')].map((li) => {
        const a = li.querySelector('a');
        return { texte: li.textContent.replace(a?.textContent ?? '', '').replace(/\s*:\s*$/, '').trim(), url: a?.href ?? '', libelle: a?.textContent.trim() ?? '' };
      }).filter((l) => l.url),
      images: [...document.querySelectorAll('main .details-media img')].map((i) => ({ src: i.getAttribute('src'), alt: i.getAttribute('alt') ?? '' })),
      videos: [...document.querySelectorAll('main video')].map((v) => v.getAttribute('src') ?? v.querySelector('source')?.getAttribute('src')).filter(Boolean),
    };
  });
  a.images.forEach((i) => garder(i.src));
  a.videos.forEach(garder);
  articles.push({ ancien_numero: n, ...a });
}

// Accueil : événements à venir (seuls publiés), catégories, partenaires et financeurs.
await ouvrir('/');
const accueil = await page.evaluate(() => {
  const json = (id) => JSON.parse(document.getElementById(id)?.textContent ?? '[]');
  const logos = (titre) => {
    const h2 = [...document.querySelectorAll('h2')].find((h) => h.textContent.trim().toUpperCase().startsWith(titre));
    const bloc = h2?.closest('section') ?? h2?.parentElement;
    return bloc ? [...bloc.querySelectorAll('a img')].map((i) => ({ nom: i.getAttribute('alt') ?? '', url: i.closest('a').href, logo: i.getAttribute('src') })) : [];
  };
  return {
    evenements: json('home-events-data').map((e) => ({
      ancien_id: e.id, titre: e.title, description: (e.description || e.summary || '').replace(/\r\n/g, '\n'), infos: e.info, liens: e.links,
      lieu: e.location, categorie: e.category_label, debut: e.start, fin: e.end,
    })),
    categories: json('home-categories-data').map((c) => ({ cle: c.name, libelle: c.label })),
    partenaires: logos('ILS NOUS ACCOMPAGNENT'),
    financeurs: logos('FINANCEURS'),
  };
});
[...accueil.partenaires, ...accueil.financeurs].forEach((l) => garder(l.logo));

// Galerie : toutes les pages ?page=n.
const galerie = [];
for (let n = 1; ; n++) {
  await ouvrir(n === 1 ? '/galerie' : `/galerie?page=${n}`);
  const imgs = await page.evaluate(() => [...document.querySelectorAll('main img.gallery-img')].map((i) => i.getAttribute('src')));
  const nouvelles = imgs.filter((s) => !galerie.includes(s));
  if (!nouvelles.length) break;
  nouvelles.forEach((s) => { galerie.push(s); garder(s); });
}
await navigateur.close();

// Médias : téléchargés une fois, au même chemin que sur le site.
let telecharges = 0;
for (const src of medias) {
  const cible = path.join(MEDIAS, src);
  if (existsSync(cible)) continue;
  const r = await fetch(SITE + encodeURI(src));
  if (!r.ok) { console.error(`Média absent : ${src} (${r.status})`); continue; }
  mkdirSync(path.dirname(cible), { recursive: true });
  writeFileSync(cible, Buffer.from(await r.arrayBuffer()));
  telecharges++;
}

const contenus = { source: SITE, date: new Date().toISOString().slice(0, 10), articles, ...accueil, galerie };
writeFileSync(path.join(SORTIE, 'contenus.json'), JSON.stringify(contenus, null, 2) + '\n');
console.log(`${articles.length} articles, ${accueil.evenements.length} événements, ${galerie.length} photos de galerie, ${accueil.partenaires.length} partenaires, ${accueil.financeurs.length} financeurs ; ${medias.size} médias dont ${telecharges} téléchargés.`);
