// SPDX-License-Identifier: GPL-3.0-or-later
// Adresse de chaque lieu de la carte du collectif (docs/source/existant/lieux-umap.json), déduite de sa position par le
// géocodage inverse de l'IGN (Géoplateforme, Licence Ouverte), une fois pour toutes (demande de Kouzia, 2026-11-17).
// Numéro gardé seulement si l'adresse trouvée est à 30 m au plus du point ; rue seule jusqu'à 120 m ; au-delà, commune
// seule. Sortie : docs/source/existant/lieux-adresses.json, lu par scripts/importer-contenus.php.
// Usage : node scripts/adresses-lieux.mjs
import { readFileSync, writeFileSync } from 'node:fs';

const lieux = JSON.parse(readFileSync('docs/source/existant/lieux-umap.json', 'utf8')).lieux;
const sortie = { _note: 'Généré par scripts/adresses-lieux.mjs : ne pas modifier à la main.', source: 'IGN, géocodage inverse (Géoplateforme), Licence Ouverte', date: new Date().toISOString().slice(0, 10), adresses: {} };
let n = { numero: 0, rue: 0, commune: 0 };
for (const l of lieux) {
  const cle = `${l.latitude},${l.longitude}`;
  if (sortie.adresses[cle]) continue;
  const r = await fetch(`https://data.geopf.fr/geocodage/reverse?lon=${l.longitude}&lat=${l.latitude}&index=address&limit=1`);
  const p = (await r.json()).features?.[0]?.properties;
  if (!p) continue;
  const d = Number(p.distance ?? 9999);
  const adresse = d <= 30 && p.housenumber ? `${p.housenumber} ${p.street}` : d <= 120 && p.street ? p.street : '';
  n[adresse === '' ? 'commune' : d <= 30 && p.housenumber ? 'numero' : 'rue']++;
  sortie.adresses[cle] = { adresse, code_postal: p.postcode ?? '', ville: p.city ?? '', distance: d };
}
writeFileSync('docs/source/existant/lieux-adresses.json', JSON.stringify(sortie, null, 1) + '\n');
console.log(`${Object.keys(sortie.adresses).length} positions : ${n.numero} au numéro, ${n.rue} à la rue, ${n.commune} à la commune`);
