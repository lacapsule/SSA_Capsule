// SPDX-License-Identifier: GPL-3.0-or-later
// Polices libres (OFL) de Google Fonts, téléchargées une fois en woff2 « latin » dans docs/source/polices/ :
// le site les sert ensuite lui-même (rule web-perf-eco), aucune requête vers Google une fois en ligne.
// Décision du 2026-11-13 : Google Fonts téléchargé plutôt que Fontsource ou polices du système seules.
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

// Requête neutre : seul le nom de la police part vers Google, rien du projet ni de l'humain.
const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
export const DOSSIER_POLICES = path.join(process.cwd(), 'docs/source/polices');
const slug = (f) => f.toLowerCase().replace(/[^a-z0-9]+/g, '-');

/**
 * Télécharge une famille (graisses demandées, sous-ensemble latin) si elle n'est pas déjà là.
 * Renvoie { famille, fichiers: [{ fichier, graisses: [min, max] }], licence } ou null si Google ne la connaît pas.
 */
export async function obtenirPolice(famille, graisses = [400, 700]) {
  const dossier = path.join(DOSSIER_POLICES, slug(famille));
  const index = path.join(dossier, 'police.json');
  if (existsSync(index)) return JSON.parse(readFileSync(index, 'utf8'));
  const url = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(famille).replace(/%20/g, '+')}:wght@${graisses.join(';')}&display=swap`;
  const rep = await fetch(url, { headers: { 'User-Agent': AGENT } });
  if (!rep.ok) return null;
  const css = await rep.text();
  const blocs = [...css.matchAll(/\/\* latin \*\/\s*@font-face\s*{([^}]*)}/g)].map((m) => ({
    graisse: Number(m[1].match(/font-weight:\s*(\d+)/)?.[1] ?? 400),
    style: m[1].match(/font-style:\s*(\w+)/)?.[1] ?? 'normal',
    src: m[1].match(/url\((https:[^)]+\.woff2)\)/)?.[1],
  })).filter((b) => b.src && b.style === 'normal');
  if (!blocs.length) return null;
  mkdirSync(dossier, { recursive: true });
  const parFichier = new Map();
  for (const b of blocs) { const g = parFichier.get(b.src) ?? []; g.push(b.graisse); parFichier.set(b.src, g); }
  const fichiers = [];
  for (const [src, gs] of parFichier) {
    const r = await fetch(src, { headers: { 'User-Agent': AGENT } });
    if (!r.ok) return null;
    const min = Math.min(...gs);
    const max = Math.max(...gs);
    const nom = `${slug(famille)}-${min === max ? min : `${min}-${max}`}-latin.woff2`;
    writeFileSync(path.join(dossier, nom), Buffer.from(await r.arrayBuffer()));
    fichiers.push({ fichier: nom, graisses: [min, max] });
  }
  const police = { famille, fichiers, licence: 'SIL Open Font License 1.1 (Google Fonts)', source: 'https://fonts.google.com/specimen/' + famille.replace(/ /g, '+'), telechargee: new Date().toISOString().slice(0, 10) };
  writeFileSync(index, JSON.stringify(police, null, 2) + '\n');
  return police;
}

/** Règles @font-face d'une police téléchargée, adresses relatives à « base » (dossier des fichiers vu par la page). */
export function fontFace(police, base) {
  return police.fichiers.map((f) => `@font-face {
  font-family: "${police.famille}";
  font-style: normal;
  font-weight: ${f.graisses[0] === f.graisses[1] ? f.graisses[0] : `${f.graisses[0]} ${f.graisses[1]}`};
  font-display: swap;
  src: url("${base}/${slug(police.famille)}/${f.fichier}") format("woff2");
}`).join('\n');
}

/** Pile de repli selon la classe de la police. */
export const pile = (famille, classe) => `"${famille}", ${classe === 'serif' ? 'Georgia, "Times New Roman", serif' : 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'}`;
