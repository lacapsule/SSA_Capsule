// SPDX-License-Identifier: GPL-3.0-or-later
// Carte de communes en SVG, sans service extérieur : contours officiels des communes autour d'un jeu de points,
// simplifiés et projetés une fois pour toutes dans un fichier JSON ; le gabarit les dessine et place les points avec
// la même projection. Source des contours : geo.api.gouv.fr (découpage administratif, Licence Ouverte).
// Usage : node scripts/carte-communes.mjs --departements 29,22 --points points.json [--sortie config/carte-communes.json]
// Ce projet (lieux conventionnés, 2026-11-17) : node scripts/carte-communes.mjs --departements 29,22 --points docs/source/existant/lieux-umap.json
//   points.json : tableau d'objets { latitude, longitude } (ou { lieux: [...] }) ; l'emprise est celle des points + marge.
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const RACINE = process.cwd();
const arg = (nom, defaut) => { const i = process.argv.indexOf('--' + nom); return i > 0 ? process.argv[i + 1] : defaut; };
const DEPARTEMENTS = String(arg('departements', '')).split(',').filter(Boolean);
const POINTS = arg('points', '');
const SORTIE = path.resolve(RACINE, arg('sortie', 'config/carte-communes.json'));
if (!DEPARTEMENTS.length || !POINTS) { console.error('Usage : node scripts/carte-communes.mjs --departements 29 --points points.json'); process.exit(2); }
const MARGE = 0.04; // degrés autour des lieux
const LARGEUR = 1000; // unités du viewBox
const TOLERANCE = 0.8; // simplification, en unités du viewBox (frontières partagées : aucun trou entre communes)

const brut = JSON.parse(readFileSync(path.resolve(RACINE, POINTS), 'utf8'));
const lieux = (Array.isArray(brut) ? brut : brut.lieux ?? []).filter((l) => Number.isFinite(l.latitude) && Number.isFinite(l.longitude));
const lats = lieux.map((l) => l.latitude);
const lons = lieux.map((l) => l.longitude);
const emprise = { lonMin: Math.min(...lons) - MARGE, lonMax: Math.max(...lons) + MARGE, latMin: Math.min(...lats) - MARGE, latMax: Math.max(...lats) + MARGE };
const cos = Math.cos(((emprise.latMin + emprise.latMax) / 2) * Math.PI / 180);
const k = LARGEUR / ((emprise.lonMax - emprise.lonMin) * cos);
const hauteur = Math.round((emprise.latMax - emprise.latMin) * k);
const projeter = ([lon, lat]) => [(lon - emprise.lonMin) * cos * k, (emprise.latMax - lat) * k];

// Douglas-Peucker sur un anneau projeté.
function simplifier(points, tol) {
  if (points.length < 4) return points;
  const garder = new Uint8Array(points.length); garder[0] = garder[points.length - 1] = 1;
  const pile = [[0, points.length - 1]];
  while (pile.length) {
    const [a, b] = pile.pop();
    const [ax, ay] = points[a], [bx, by] = points[b];
    const dx = bx - ax, dy = by - ay, l2 = dx * dx + dy * dy || 1;
    let max = 0, idx = -1;
    for (let i = a + 1; i < b; i++) {
      const [px, py] = points[i];
      const t = Math.max(0, Math.min(1, ((px - ax) * dx + (py - ay) * dy) / l2));
      const d = Math.hypot(px - (ax + t * dx), py - (ay + t * dy));
      if (d > max) { max = d; idx = i; }
    }
    if (max > tol && idx > 0) { garder[idx] = 1; pile.push([a, idx], [idx, b]); }
  }
  return points.filter((_, i) => garder[i]);
}
const aire = (pts) => Math.abs(pts.reduce((s, [x, y], i) => { const [x2, y2] = pts[(i + 1) % pts.length]; return s + x * y2 - x2 * y; }, 0) / 2);
// Une décimale (au zoom fort, des coordonnées entières donnent des contours en escalier), en coordonnées relatives
// (chaque point par rapport au précédent, sur la valeur arrondie) : tracé deux fois plus court pour la même précision.
const nombre = (v) => { const t = (Math.round(v * 10) / 10).toFixed(1).replace(/\.0$/, '').replace(/^(-?)0\./, '$1.'); return t === '-0' ? '0' : t; };
const paire = (dx, dy) => { const b = nombre(dy); return nombre(dx) + (b.startsWith('-') ? '' : ' ') + b; };
const chemin = (anneaux) => anneaux.map((pts) => {
  const r = pts.map(([x, y]) => [Math.round(x * 10) / 10, Math.round(y * 10) / 10]).filter((p, i, l) => i === 0 || p[0] !== l[i - 1][0] || p[1] !== l[i - 1][1]);
  return 'M' + paire(r[0][0], r[0][1]) + 'l' + r.slice(1).map(([x, y], i) => paire(x - r[i][0], y - r[i][1])).join(' ') + 'z';
}).join('');

const features = [];
for (const dep of DEPARTEMENTS) {
  const source = `https://geo.api.gouv.fr/departements/${encodeURIComponent(dep)}/communes?format=geojson&geometry=contour&fields=nom,code`;
  const reponse = await fetch(source, { headers: { 'User-Agent': 'carte-communes' } });
  if (!reponse.ok) { console.error(`Contours introuvables (${reponse.status}) : ${source}`); process.exit(1); }
  features.push(...(await reponse.json()).features);
}
// Simplification par frontière (topologie gardée) : un sommet partagé par un autre ensemble de communes que ses voisins
// est un nœud ; chaque morceau de contour entre deux nœuds est simplifié une seule fois et repris tel quel (ou à
// l'envers) par les deux communes qu'il sépare. Sans cela, deux voisines simplifiées chacune de leur côté laissent des
// trous et des chevauchements visibles au zoom (constaté le 2026-11-17).
const cle = ([lon, lat]) => `${lon},${lat}`;
const anneauxDe = (f) => (f.geometry.type === 'Polygon' ? [f.geometry.coordinates] : f.geometry.type === 'MultiPolygon' ? f.geometry.coordinates : []).map((p) => p[0]);
const membres = new Map();
features.forEach((f, n) => anneauxDe(f).forEach((anneau, r) => {
  for (const v of anneau) {
    const k = cle(v);
    if (!membres.has(k)) membres.set(k, new Set());
    membres.get(k).add(`${n}:${r}`);
  }
}));
const signature = (v) => [...membres.get(cle(v))].sort().join(' ');
const arcs = new Map();
function anneauSimplifie(brut) {
  const pts = cle(brut[0]) === cle(brut[brut.length - 1]) ? brut.slice(0, -1) : brut.slice();
  const n = pts.length;
  const sig = pts.map(signature);
  let noeuds = pts.map((_, i) => sig[i] !== sig[(i - 1 + n) % n] || sig[i] !== sig[(i + 1) % n]);
  if (!noeuds.some(Boolean)) {
    // Anneau entier partagé avec une seule voisine (ou isolé) : nœud au plus petit sommet, le même des deux côtés.
    const premier = pts.reduce((m, v, i) => (cle(v) < cle(pts[m]) ? i : m), 0);
    noeuds = pts.map((_, i) => i === premier);
  }
  const depart = noeuds.indexOf(true);
  const tour = [...pts.slice(depart), ...pts.slice(0, depart), pts[depart]];
  const marques = [...noeuds.slice(depart), ...noeuds.slice(0, depart), true];
  const sortie = [];
  let debut = 0;
  for (let i = 1; i < tour.length; i++) {
    if (!marques[i]) continue;
    const morceau = tour.slice(debut, i + 1);
    const cles = morceau.map(cle).join('|');
    const inverse = morceau.map(cle).reverse().join('|');
    const canon = cles <= inverse ? cles : inverse;
    if (!arcs.has(canon)) {
      const sens = cles <= inverse ? morceau : morceau.slice().reverse();
      arcs.set(canon, simplifier(sens.map(projeter), TOLERANCE));
    }
    const arc = arcs.get(canon);
    const oriente = cles <= inverse ? arc : arc.slice().reverse();
    sortie.push(...(sortie.length ? oriente.slice(1) : oriente));
    debut = i;
  }
  return sortie.slice(0, -1);
}

const communes = [];
for (const f of features) {
  const anneauxBruts = anneauxDe(f);
  const toutes = anneauxBruts.flat();
  const dans = toutes.some(([lon, lat]) => lon >= emprise.lonMin && lon <= emprise.lonMax && lat >= emprise.latMin && lat <= emprise.latMax);
  if (!dans) continue;
  const anneaux = anneauxBruts.map(anneauSimplifie).filter((a) => a.length >= 3 && aire(a) > 4);
  if (!anneaux.length) continue;
  const principal = anneaux.reduce((a, b) => (aire(b) > aire(a) ? b : a));
  const cx = principal.reduce((s, p) => s + p[0], 0) / principal.length;
  const cy = principal.reduce((s, p) => s + p[1], 0) / principal.length;
  communes.push({ nom: f.properties.nom, code: f.properties.code, d: chemin(anneaux), cx: +cx.toFixed(1), cy: +cy.toFixed(1) });
}
communes.sort((a, b) => a.nom.localeCompare(b.nom, 'fr'));
const sortie = {
  _note: 'Généré par scripts/carte-communes.mjs : ne pas modifier à la main.',
  source: 'geo.api.gouv.fr (découpage administratif, Licence Ouverte)',
  date: new Date().toISOString().slice(0, 10),
  largeur: LARGEUR,
  hauteur,
  projection: { lonMin: emprise.lonMin, latMax: emprise.latMax, cos, k },
  communes,
};
writeFileSync(SORTIE, JSON.stringify(sortie) + '\n');
console.log(`${communes.length} communes, viewBox ${LARGEUR} × ${hauteur}, ${Math.round(JSON.stringify(sortie).length / 1024)} Ko`);
