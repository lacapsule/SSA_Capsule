// SPDX-License-Identifier: GPL-3.0-or-later
// Maquettes : chaque piste de direction artistique appliquée aux vraies pages du site intégré (après la
// validation 2). Seuls les tokens changent : le client choisit sa piste en voyant ses propres pages.
// Site servi : SITE_LOCAL=http://… (socle dynamique lancé à part), sinon socle statique construit puis servi ici.
import { execFileSync, spawn } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { echapper } from './document.mjs';
import { DOSSIER_POLICES } from './polices.mjs';
import { slugAdresse } from './wireframes.mjs';

const RACINE = process.cwd();
const attendre = (ms) => new Promise((r) => setTimeout(r, ms));

/** Adresse du site à capturer et fonction d'arrêt ; null et la raison si le site n'est pas prêt. */
async function demarrerSite() {
  if (process.env.SITE_LOCAL) return { base: process.env.SITE_LOCAL.replace(/\/$/, ''), arreter: () => {} };
  if (!existsSync(path.join(RACINE, 'bin/build.php'))) return { raison: "socle pas encore posé (make socle), ou socle dynamique à lancer à part puis SITE_LOCAL=http://… make livrables" };
  try {
    execFileSync('php', ['bin/build.php'], { cwd: RACINE, stdio: 'pipe' });
  } catch (e) {
    const ligne = String(e.stderr ?? e.message).split('\n').find((l) => /Exception|Error/.test(l)) ?? 'erreur inconnue';
    return { raison: `le site ne se construit pas encore (${ligne.replace(/^PHP Fatal error:\s*Uncaught\s*/, '').slice(0, 160)})` };
  }
  const port = 8100 + Math.floor(Math.random() * 800);
  const serveur = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', 'dist', 'bin/preview.php'], { cwd: RACINE, stdio: 'ignore' });
  const base = `http://127.0.0.1:${port}`;
  for (let i = 0; i < 30; i++) {
    try { if ((await fetch(base + '/')).ok) break; } catch { /* serveur pas encore prêt */ }
    await attendre(200);
  }
  return { base, arreter: () => serveur.kill() };
}

/**
 * pistes : [{ numero, nom, cle, dossier }] (dossier contenant tokens.css). Renvoie { corps, avertissements, sourcesRendues }.
 */
export async function pistesSurPages({ pistes, arborescence, navigateur }) {
  const avert = [];
  const site = await demarrerSite();
  if (!site.base) {
    avert.push(`Pages pas encore intégrées : ${site.raison}. Les pistes doivent être montrées sur les vraies pages avant la validation 3.`);
    return { corps: '', avertissements: avert, sourcesRendues: '' };
  }
  let corps = `<h2>Vos pages avec chaque piste</h2>
<p>Les pages ci-dessous sont celles du nouveau site, avec leurs vrais contenus. D'une piste à l'autre, seules les couleurs, les polices et les arrondis changent.</p>`;
  let sourcesRendues = '';
  try {
    const pages = [];
    for (const p of arborescence) {
      const r = await fetch(site.base + p.adresse).catch(() => null);
      if (r?.ok) pages.push(p); else avert.push(`Page « ${p.page} » (${p.adresse}) pas encore intégrée : absente des maquettes.`);
    }
    if (!pages.length) return { corps: '', avertissements: avert, sourcesRendues: '' };
    for (const piste of pistes) {
      const tokens = readFileSync(path.join(piste.dossier, 'tokens.css'), 'utf8').replaceAll('../../../../source/polices', '/__charte/polices');
      const contexte = await navigateur.newContext();
      // Polices de la piste servies depuis docs/source/polices, sous une adresse que le site n'utilise pas.
      await contexte.route('**/__charte/polices/**', (route) => {
        const relatif = decodeURIComponent(new URL(route.request().url()).pathname.replace('/__charte/polices/', ''));
        const fichier = path.join(DOSSIER_POLICES, relatif);
        return fichier.startsWith(DOSSIER_POLICES) && existsSync(fichier) ? route.fulfill({ path: fichier, contentType: 'font/woff2' }) : route.abort();
      });
      const page = await contexte.newPage();
      const sortie = path.join(piste.dossier, 'pages');
      mkdirSync(sortie, { recursive: true });
      const vues = [];
      for (const [i, p] of pages.entries()) {
        const slug = slugAdresse(p.adresse);
        for (const [largeur, nom, theme] of [[1280, 'bureau', null], [390, 'mobile', null], ...(i === 0 ? [[1280, 'sombre', 'dark']] : [])]) {
          await page.setViewportSize({ width: largeur, height: 900 });
          await page.goto(site.base + p.adresse, { waitUntil: 'load' });
          await page.addStyleTag({ content: tokens });
          if (theme) await page.evaluate((t) => { document.documentElement.dataset.theme = t; }, theme);
          await page.evaluate(() => document.fonts.ready);
          await page.screenshot({ path: path.join(sortie, `${slug}-${nom}.png`), fullPage: true });
          if (nom === 'bureau') sourcesRendues += await page.content();
        }
        vues.push({ p, slug });
      }
      sourcesRendues += tokens;
      await contexte.close();
      const rel = path.relative(path.dirname(path.dirname(piste.dossier)), piste.dossier);
      const [accueil, ...autres] = vues;
      corps += `<h3>Piste ${piste.numero} : ${echapper(piste.nom)}</h3>
<div class="ecrans">
<figure><img src="${rel}/pages/${accueil.slug}-bureau.png" alt="${echapper(accueil.p.page)}, piste ${piste.numero}, sur ordinateur"><figcaption>${echapper(accueil.p.page)}, ordinateur</figcaption></figure>
<figure><img src="${rel}/pages/${accueil.slug}-mobile.png" alt="${echapper(accueil.p.page)}, piste ${piste.numero}, sur téléphone"><figcaption>téléphone</figcaption></figure>
</div>
<ul class="vignettes"><li><figure><img src="${rel}/pages/${accueil.slug}-sombre.png" alt="${echapper(accueil.p.page)}, piste ${piste.numero}, thème sombre"><figcaption>${echapper(accueil.p.page)}, thème sombre</figcaption></figure></li>${autres.map((v) => `<li><figure><img src="${rel}/pages/${v.slug}-bureau.png" alt="${echapper(v.p.page)}, piste ${piste.numero}"><figcaption>${echapper(v.p.page)}</figcaption></figure></li>`).join('')}</ul>`;
    }
  } finally {
    site.arreter();
  }
  return { corps, avertissements: avert, sourcesRendues };
}
