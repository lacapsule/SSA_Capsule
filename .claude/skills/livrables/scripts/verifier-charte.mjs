// SPDX-License-Identifier: GPL-3.0-or-later
// Contrôle de make verify : le site porte la charte de son client, jamais celle de Kouzia.
//   1. Aucune feuille du site ne reprend la charte de Kouzia (base/tokens.css de la boîte : copie, accent, fonds, polices).
//   2. Marque connue (audit du site actuel ou logo fourni) : la charte générée du client est posée (make charte, puis
//      make appliquer après la validation 3) et, socle dynamique, liée dans templates/layout.php.
// Sans mode d'emploi (modèle de dépôt, boîte à outils) : rien à contrôler.
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import path from 'node:path';

const RACINE = process.cwd();
if (!existsSync(path.join(RACINE, '.claude/rules/projet.md'))) process.exit(0);

const BOITE = path.join(path.dirname(RACINE), 'kouzia-boite-a-outils/base/tokens.css');
// Signature de la charte de Kouzia : valeurs propres à base/tokens.css de la boîte.
const KOUZIA = [/#fe9a00\b/i, /#0b1020\b/i, /#121a2e\b/i, /["']Exo 2["']/, /Share Tech Mono/, /exo2-latin\.woff2/];
const normal = (t) => t.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s+/g, '');
const boite = existsSync(BOITE) ? normal(readFileSync(BOITE, 'utf8')) : null;

function feuilles(dossier) {
  if (!existsSync(dossier)) return [];
  return readdirSync(dossier).flatMap((n) => {
    const f = path.join(dossier, n);
    if (['node_modules', 'vendor', 'dist', '.git'].includes(n)) return [];
    return statSync(f).isDirectory() ? feuilles(f) : n.endsWith('.css') ? [f] : [];
  });
}

const ecarts = [];
for (const f of [...feuilles(path.join(RACINE, 'src')), ...feuilles(path.join(RACINE, 'public'))]) {
  const t = readFileSync(f, 'utf8');
  const rel = path.relative(RACINE, f);
  if (boite && normal(t) === boite) { ecarts.push(`${rel} : copie de base/tokens.css de la boîte, c'est la charte de Kouzia et non une charte neutre`); continue; }
  const traces = KOUZIA.filter((re) => re.test(t)).map((re) => t.match(re)[0]);
  if (traces.length) ecarts.push(`${rel} : valeurs de la charte de Kouzia (${[...new Set(traces)].join(', ')})`);
}

const statique = existsSync(path.join(RACINE, 'src/styles/ordre.json'));
const dynamique = existsSync(path.join(RACINE, 'app/bootstrap.php'));
const fournis = path.join(RACINE, 'docs/source/fournis/logo');
const marqueConnue = existsSync(path.join(RACINE, 'docs/source/existant/inventaire.json'))
  || (existsSync(fournis) && readdirSync(fournis).some((n) => !n.startsWith('.')));
if (marqueConnue && (statique || dynamique)) {
  const charte = path.join(RACINE, statique ? 'src/styles/base/charte.css' : 'public/assets/css/charte.css');
  const rel = path.relative(RACINE, charte);
  if (!existsSync(charte) || !/^\/\* Charte du client/.test(readFileSync(charte, 'utf8'))) {
    ecarts.push(`${rel} : charte du client absente alors que sa marque est connue (audit ou logo fourni) : make charte`);
  } else if (dynamique) {
    const layout = path.join(RACINE, 'templates/layout.php');
    if (!existsSync(layout) || !/assets\/css\/charte\.css/.test(readFileSync(layout, 'utf8'))) ecarts.push('templates/layout.php : public/assets/css/charte.css non liée (après les feuilles de tokens, avant la mise en page)');
  }
}

if (ecarts.length) {
  console.error(`Charte : ${ecarts.length} écart(s)\n${ecarts.map((e) => `  ${e}`).join('\n')}`);
  process.exit(1);
}
console.log('Charte : celle du client, aucune trace de la charte de Kouzia.');
