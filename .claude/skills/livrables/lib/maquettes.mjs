// SPDX-License-Identifier: GPL-3.0-or-later
// Maquettes composées : chaque piste de direction artistique est une composition (skill web-direction), pas un
// changement de couleurs. L'agent écrit, après la validation 2 et avant toute intégration, une maquette HTML par piste :
//   docs/maquettes/pistes/<n>-<cle>/composition.md   principe de la composition, une phrase pour le client en tête
//   docs/maquettes/pistes/<n>-<cle>/accueil.html     l'accueil, avec les vrais contenus et les vrais visuels du client
//   docs/maquettes/pistes/<n>-<cle>/<page>.html      au moins une page intérieure (même nom de fichier d'une piste à l'autre)
// Les maquettes n'utilisent que des var(--…) : les tokens de la piste (couleurs, polices, arrondis) sont posés par-dessus
// à la capture. Deux pistes à la même composition (mêmes sections, mêmes fonds, mêmes colonnes) : livrable en brouillon.
import { existsSync, mkdirSync, readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { echapper } from './document.mjs';
import { DOSSIER_POLICES } from './polices.mjs';

const RACINE = process.cwd();
export const DOSSIER_MAQUETTES_PISTES = path.join(RACINE, 'docs/maquettes/pistes');
const ORIGINE = 'http://maquettes.invalid';
const TYPES = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.webp': 'image/webp', '.avif': 'image/avif', '.woff2': 'font/woff2', '.mp4': 'video/mp4', '.webm': 'video/webm' };

/** Signature de composition d'une page rendue : une entrée par grande section (fond, colonnes, visuel, hauteur). */
function signature() {
  const zone = document.querySelector('main') ?? document.body;
  const sections = [...zone.children].filter((el) => el.getBoundingClientRect().height > 120);
  const fond = (el) => {
    for (let n = el; n && n !== document.documentElement; n = n.parentElement) {
      const s = getComputedStyle(n);
      if (s.backgroundImage !== 'none') return 'image';
      if (s.backgroundColor !== 'rgba(0, 0, 0, 0)' && s.backgroundColor !== 'transparent') return s.backgroundColor;
    }
    return 'page';
  };
  const colonnes = (el) => {
    let max = 1;
    for (const c of el.querySelectorAll('*')) {
      if (!/grid|flex/.test(getComputedStyle(c).display)) continue;
      const enfants = [...c.children].filter((x) => x.getBoundingClientRect().width > 80);
      if (new Set(enfants.map((x) => Math.round(x.getBoundingClientRect().top))).size === 1) max = Math.max(max, enfants.length);
    }
    return max;
  };
  return sections.map((s) => [fond(s), colonnes(s), s.querySelector('img, picture, svg, video') ? 'visuel' : 'texte', Math.round(s.getBoundingClientRect().height / 200)].join('/'));
}

/** Part des sections identiques (même rang, même signature) entre deux pages. */
function ressemblance(a, b) {
  const n = Math.max(a.length, b.length);
  return n ? a.filter((x, i) => x === b[i]).length / n : 1;
}

/**
 * pistes : [{ numero, nom, dossier }] (dossier du livrable contenant tokens.css). Renvoie { corps, avertissements,
 * sourcesRendues } : les maquettes de chaque piste capturées à 1280 et 390 px, à placer dans le livrable 04.
 */
export async function maquettesComposees({ pistes, navigateur }) {
  const avert = [];
  let corps = `<h2>Vos pages selon chaque direction</h2>
<p>Chaque direction propose une composition différente de vos pages : place des photos et des illustrations, rythme des sections, taille des titres, couleurs et polices. Les maquettes reprennent vos vrais contenus.</p>`;
  let sourcesRendues = '';
  const signatures = [];
  for (const piste of pistes) {
    const nomDossier = path.basename(piste.dossier);
    const source = path.join(DOSSIER_MAQUETTES_PISTES, nomDossier);
    const pages = existsSync(source) ? readdirSync(source).filter((f) => f.endsWith('.html')).sort((a, b) => (a === 'accueil.html' ? -1 : b === 'accueil.html' ? 1 : a.localeCompare(b))) : [];
    if (!pages.includes('accueil.html') || pages.length < 2) {
      avert.push(`Piste ${piste.numero} : maquette composée absente ou incomplète (docs/maquettes/pistes/${nomDossier}/ : accueil.html et au moins une page intérieure, skill web-direction).`);
      continue;
    }
    const composition = existsSync(path.join(source, 'composition.md')) ? readFileSync(path.join(source, 'composition.md'), 'utf8') : '';
    const principe = composition.split('\n').find((l) => l.trim() && !l.startsWith('#'))?.trim() ?? '';
    if (!principe) avert.push(`Piste ${piste.numero} : principe de composition absent (docs/maquettes/pistes/${nomDossier}/composition.md).`);
    const tokens = readFileSync(path.join(piste.dossier, 'tokens.css'), 'utf8').replaceAll('../../../../source/polices', '/__polices');
    const sortie = path.join(piste.dossier, 'maquettes');
    mkdirSync(sortie, { recursive: true });
    // Maquettes servies sous une adresse fictive : chemins relatifs vers docs/ (photos, illustrations) et polices de la
    // piste chargés comme sur un vrai site (une page file:// refuse les polices d'un autre fichier).
    const contexte = await navigateur.newContext();
    await contexte.route(`${ORIGINE}/**`, (route) => {
      const chemin = decodeURIComponent(new URL(route.request().url()).pathname);
      const fichier = chemin.startsWith('/__polices/') ? path.join(DOSSIER_POLICES, chemin.slice('/__polices/'.length)) : path.join(RACINE, chemin);
      const racine = chemin.startsWith('/__polices/') ? DOSSIER_POLICES : RACINE;
      if (!fichier.startsWith(racine + path.sep) || !existsSync(fichier)) return route.fulfill({ status: 404, body: '' });
      return route.fulfill({ path: fichier, contentType: TYPES[path.extname(fichier).toLowerCase()] ?? 'application/octet-stream' });
    });
    const page = await contexte.newPage();
    const vues = [];
    for (const fichier of pages) {
      const nom = fichier.replace(/\.html$/, '');
      sourcesRendues += readFileSync(path.join(source, fichier), 'utf8');
      for (const [largeur, vue] of [[1280, 'bureau'], [390, 'mobile']]) {
        await page.setViewportSize({ width: largeur, height: 900 });
        await page.goto(`${ORIGINE}/${path.relative(RACINE, path.join(source, fichier)).split(path.sep).join('/')}`, { waitUntil: 'load' });
        await page.addStyleTag({ content: tokens });
        await page.evaluate(() => document.fonts.ready);
        if (fichier === 'accueil.html' && largeur === 1280) signatures.push({ numero: piste.numero, sig: await page.evaluate(signature) });
        await page.screenshot({ path: path.join(sortie, `${nom}-${vue}.png`), fullPage: true });
      }
      vues.push(nom);
    }
    for (const f of readdirSync(source).filter((f) => f.endsWith('.css'))) sourcesRendues += readFileSync(path.join(source, f), 'utf8');
    sourcesRendues += composition + tokens;
    await contexte.close();
    const rel = `pistes/${nomDossier}/maquettes`;
    const [accueil, ...autres] = vues;
    corps += `<h3>Direction ${piste.numero} : ${echapper(piste.nom)}</h3>
${principe ? `<p>${echapper(principe)}</p>` : ''}
<div class="ecrans">
<figure><img src="${rel}/${accueil}-bureau.png" alt="Accueil, direction ${piste.numero}, sur ordinateur"><figcaption>Accueil, ordinateur</figcaption></figure>
<figure><img src="${rel}/${accueil}-mobile.png" alt="Accueil, direction ${piste.numero}, sur téléphone"><figcaption>téléphone</figcaption></figure>
</div>
<ul class="vignettes">${autres.map((v) => `<li><figure><img src="${rel}/${v}-bureau.png" alt="Page ${echapper(v)}, direction ${piste.numero}"><figcaption>${echapper(v)}</figcaption></figure></li>`).join('')}</ul>`;
  }
  // Une direction se distingue par sa composition : deux accueils aux sections identiques ne sont qu'un changement de couleurs.
  for (let i = 0; i < signatures.length; i++) {
    for (let j = i + 1; j < signatures.length; j++) {
      const r = ressemblance(signatures[i].sig, signatures[j].sig);
      if (r >= 0.7) avert.push(`Pistes ${signatures[i].numero} et ${signatures[j].numero} : même composition de l'accueil (${Math.round(r * 100)} % des sections identiques en fond, colonnes et visuel) ; une direction change la composition, pas seulement les couleurs (skill web-direction).`);
    }
  }
  return { corps, avertissements: avert, sourcesRendues };
}
