// SPDX-License-Identifier: GPL-3.0-or-later
// Logos d'une rangée (partenaires, financeurs, labels) : marges vides retirées puis poids optique égalisé.
//   SVG : viewBox resserré sur le dessin réel (getBBox), le fichier garde ses tracés.
//   PNG, JPEG, WebP : recadré sur les pixels ni transparents ni presque blancs, réécrit en WebP.
// Puis la hauteur d'affichage de chaque logo pour une même surface visuelle (un logo très large est moins haut),
// écrite dans <sortie>/logos.json, à reporter en tokens ou en attribut height (rule web-design, « Univers du client »).
// Usage : node logos-recadres.mjs <dossier des logos> <dossier de sortie> [surface en rem², défaut 18]
import { mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { lancerNavigateur } from '../../livrables/lib/document.mjs';

const [entree, sortie, surfaceTexte] = process.argv.slice(2);
if (!entree || !sortie) { console.error('Usage : node logos-recadres.mjs <dossier des logos> <dossier de sortie> [surface en rem²]'); process.exit(2); }
const SURFACE = Number(surfaceTexte ?? 18);
mkdirSync(sortie, { recursive: true });
const fichiers = readdirSync(entree).filter((f) => /\.(svg|png|jpe?g|webp)$/i.test(f));
if (!fichiers.length) { console.error(`Aucun logo dans ${entree}.`); process.exit(1); }

const navigateur = await lancerNavigateur();
const page = await navigateur.newPage();
const resultat = [];
for (const f of fichiers) {
  const source = path.resolve(entree, f);
  if (/\.svg$/i.test(f)) {
    const svg = readFileSync(source, 'utf8');
    await page.setContent(`<!DOCTYPE html><body style="margin:0">${svg}</body>`);
    const boite = await page.evaluate(() => {
      const el = document.querySelector('svg');
      if (!el) return null;
      const b = el.getBBox();
      return b.width && b.height ? { x: b.x, y: b.y, l: b.width, h: b.height } : null;
    });
    if (!boite) { console.log(`${f} : dessin illisible, laissé tel quel.`); continue; }
    const viewBox = `${+boite.x.toFixed(2)} ${+boite.y.toFixed(2)} ${+boite.l.toFixed(2)} ${+boite.h.toFixed(2)}`;
    const recadre = svg.replace(/<svg\b([^>]*)>/, (tout, attributs) => `<svg${attributs.replace(/\s(viewBox|width|height)="[^"]*"/gi, '')} viewBox="${viewBox}">`);
    writeFileSync(path.join(sortie, f), recadre);
    resultat.push({ fichier: f, rapport: boite.l / boite.h });
  } else {
    // Recadrage par le contenu des pixels, dans le navigateur (aucune dépendance d'image côté Node).
    await page.goto(pathToFileURL(source).href);
    const donnees = await page.evaluate(() => {
      const img = document.querySelector('img');
      const c = document.createElement('canvas');
      c.width = img.naturalWidth; c.height = img.naturalHeight;
      const ctx = c.getContext('2d');
      ctx.drawImage(img, 0, 0);
      const { data, width, height } = ctx.getImageData(0, 0, c.width, c.height);
      let x0 = width; let y0 = height; let x1 = -1; let y1 = -1;
      for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
        const i = (y * width + x) * 4;
        const vide = data[i + 3] < 16 || (data[i] > 245 && data[i + 1] > 245 && data[i + 2] > 245);
        if (!vide) { if (x < x0) x0 = x; if (x > x1) x1 = x; if (y < y0) y0 = y; if (y > y1) y1 = y; }
      }
      if (x1 < 0) return null;
      const l = x1 - x0 + 1; const h = y1 - y0 + 1;
      const d = document.createElement('canvas');
      d.width = l; d.height = h;
      d.getContext('2d').drawImage(c, x0, y0, l, h, 0, 0, l, h);
      return { url: d.toDataURL('image/webp', 0.9), l, h, avant: [width, height] };
    });
    if (!donnees) { console.log(`${f} : image vide, laissée telle quelle.`); continue; }
    const nom = f.replace(/\.[a-z]+$/i, '.webp');
    writeFileSync(path.join(sortie, nom), Buffer.from(donnees.url.split(',')[1], 'base64'));
    console.log(`${f} : ${donnees.avant.join(' × ')} px recadré en ${donnees.l} × ${donnees.h} px (${nom}).`);
    resultat.push({ fichier: nom, rapport: donnees.l / donnees.h, largeur: donnees.l, hauteur: donnees.h });
  }
}
await navigateur.close();

// Même surface visuelle : hauteur = racine(surface / rapport), bornée pour rester lisible.
for (const r of resultat) r.hauteurRem = Math.round(Math.min(4, Math.max(1.5, Math.sqrt(SURFACE / r.rapport))) * 100) / 100;
writeFileSync(path.join(sortie, 'logos.json'), JSON.stringify(resultat, null, 2) + '\n');
console.log(`${resultat.length} logo(s) dans ${sortie} ; hauteurs pour une même surface (${SURFACE} rem²) : ${resultat.map((r) => `${r.fichier} ${r.hauteurRem} rem`).join(', ')}.`);
