// SPDX-License-Identifier: GPL-3.0-or-later
// Détails des cartes en SVG, récupérés une fois pour toutes auprès de l'IGN (Géoplateforme, BD TOPO, Licence Ouverte) :
// aucun service extérieur n'est appelé par les visiteurs. Deux sorties :
//  1. config/carte-routes.json : grandes routes (importance 1 à 3) et cours d'eau principaux sur l'emprise de la carte des
//     lieux, projetés comme config/carte-communes.json (même projection) ;
//  2. config/carte-quartiers.json : pour chaque lieu de config/lieux-evenements.tsv (et l'adresse du contact), les routes
//     (importance 1 à 5) et l'eau dans un rayon d'un kilomètre, en mètres autour du point.
// Usage : node scripts/carte-ign.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const RACINE = process.cwd();
const WFS = 'https://data.geopf.fr/wfs/ows';
const lire = (f) => readFileSync(path.join(RACINE, f), 'utf8');

async function entites(type, bbox, filtre, champs) {
  const toutes = [];
  for (let debut = 0; ; debut += 5000) {
    const q = new URLSearchParams({ SERVICE: 'WFS', VERSION: '2.0.0', REQUEST: 'GetFeature', TYPENAMES: type, OUTPUTFORMAT: 'application/json', COUNT: '5000', STARTINDEX: String(debut), SRSNAME: 'EPSG:4326', PROPERTYNAME: champs.join(',') });
    const zone = `BBOX(geometrie,${bbox.lonMin},${bbox.latMin},${bbox.lonMax},${bbox.latMax},'EPSG:4326')`; // CQL : longitude d'abord
    q.set('CQL_FILTER', filtre ? `${filtre} AND ${zone}` : zone);
    const r = await fetch(`${WFS}?${q}`);
    if (!r.ok) throw new Error(`${type} : HTTP ${r.status} ${(await r.text()).slice(0, 300)}`);
    const lot = (await r.json()).features ?? [];
    toutes.push(...lot);
    if (lot.length < 5000) return toutes;
  }
}

// Douglas-Peucker sur une ligne projetée.
function simplifier(points, tol) {
  if (points.length < 3) return points;
  const garder = new Uint8Array(points.length); garder[0] = garder[points.length - 1] = 1;
  const pile = [[0, points.length - 1]];
  while (pile.length) {
    const [a, b] = pile.pop();
    const [ax, ay] = points[a], [bx, by] = points[b];
    const dx = bx - ax, dy = by - ay, l2 = dx * dx + dy * dy || 1;
    let max = 0, idx = -1;
    for (let i = a + 1; i < b; i++) {
      const t = Math.max(0, Math.min(1, ((points[i][0] - ax) * dx + (points[i][1] - ay) * dy) / l2));
      const d = Math.hypot(points[i][0] - (ax + t * dx), points[i][1] - (ay + t * dy));
      if (d > max) { max = d; idx = i; }
    }
    if (max > tol && idx > 0) { garder[idx] = 1; pile.push([a, idx], [idx, b]); }
  }
  return points.filter((_, i) => garder[i]);
}
const lignes = (g) => (g?.type === 'LineString' ? [g.coordinates] : g?.type === 'MultiLineString' ? g.coordinates : []);
function chemin(features, projeter, tol, chiffres) {
  const morceaux = [];
  for (const f of features) for (const l of lignes(f.geometry)) {
    const p = simplifier(l.map(projeter), tol);
    if (p.length < 2) continue;
    morceaux.push('M' + p.map(([x, y]) => `${x.toFixed(chiffres)} ${y.toFixed(chiffres)}`).join('L'));
  }
  return morceaux.join('');
}

// 1. Carte des lieux : grandes routes et cours d'eau principaux.
const communes = JSON.parse(lire('config/carte-communes.json'));
const pr = communes.projection;
const emprise = { lonMin: pr.lonMin, latMax: pr.latMax, lonMax: pr.lonMin + communes.largeur / (pr.cos * pr.k), latMin: pr.latMax - communes.hauteur / pr.k };
const projeterCarte = ([lon, lat]) => [(lon - pr.lonMin) * pr.cos * pr.k, (pr.latMax - lat) * pr.k];
const routes = await entites('BDTOPO_V3:troncon_de_route', emprise, "importance IN ('1','2','3') AND fictif = false", ['geometrie', 'importance']);
const eaux = await entites('BDTOPO_V3:cours_d_eau', emprise, "importance IN ('1','2','3','4')", ['geometrie', 'importance']);
const parImportance = (liste, valeurs) => liste.filter((f) => valeurs.includes(String(f.properties.importance)));
const carte = {
  _note: 'Généré par scripts/carte-ign.mjs : ne pas modifier à la main.',
  source: 'IGN, BD TOPO (Géoplateforme), Licence Ouverte',
  date: new Date().toISOString().slice(0, 10),
  routes: { grandes: chemin(parImportance(routes, ['1', '2']), projeterCarte, 0.8, 1), moyennes: chemin(parImportance(routes, ['3']), projeterCarte, 1.2, 1) },
  eau: chemin(eaux, projeterCarte, 1.2, 1),
};
writeFileSync(path.join(RACINE, 'config/carte-routes.json'), JSON.stringify(carte) + '\n');
console.log(`carte-routes.json : ${routes.length} tronçons, ${eaux.length} cours d'eau, ${(JSON.stringify(carte).length / 1024).toFixed(0)} Ko`);

// 2. Quartiers autour des lieux placés.
const RAYON = 1000; // mètres
const lieux = lire('config/lieux-evenements.tsv').split('\n').filter((l) => l && !l.startsWith('#')).map((l) => l.split('\t'));
const points = new Map();
for (const [, lat, lon, , cle] of lieux) points.set(cle, { lat: +lat, lon: +lon });
const quartiers = { _note: carte._note, source: carte.source, date: carte.date, rayon: RAYON, points: {} };
for (const [cle, { lat, lon }] of points) {
  const mx = Math.cos((lat * Math.PI) / 180) * 111320;
  const my = 110540;
  const projeter = ([x, y]) => [(x - lon) * mx, (lat - y) * my];
  const marge = (RAYON * 1.6) / my;
  const bbox = { latMin: lat - marge, latMax: lat + marge, lonMin: lon - (RAYON * 1.6) / mx, lonMax: lon + (RAYON * 1.6) / mx };
  const r = await entites('BDTOPO_V3:troncon_de_route', bbox, "importance IN ('1','2','3','4','5') AND fictif = false", ['geometrie', 'importance']);
  const e = await entites('BDTOPO_V3:troncon_hydrographique', bbox, "persistance = 'Permanent'", ['geometrie']);
  quartiers.points[cle] = {
    lat, lon,
    routes: { grandes: chemin(parImportance(r, ['1', '2', '3']), projeter, 4, 0), moyennes: chemin(parImportance(r, ['4']), projeter, 4, 0), petites: chemin(parImportance(r, ['5']), projeter, 4, 0) },
    eau: chemin(e, projeter, 4, 0),
  };
  console.log(`${cle} : ${r.length} routes, ${e.length} tronçons d'eau`);
}
writeFileSync(path.join(RACINE, 'config/carte-quartiers.json'), JSON.stringify(quartiers) + '\n');
console.log(`carte-quartiers.json : ${(JSON.stringify(quartiers).length / 1024).toFixed(0)} Ko`);
