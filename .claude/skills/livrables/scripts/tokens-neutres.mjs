// SPDX-License-Identifier: GPL-3.0-or-later
// Écrit la charte neutre (gris) du modèle statique, base/charte.css : couleurs, rôles de zone, polices et arrondis
// pour intégrer les pages avant le choix de la direction artistique, mêmes rôles que les pistes (lib/couleurs.mjs),
// contrastes vérifiés. make appliquer remplace ensuite ce fichier par la piste validée.
// node .claude/skills/livrables/scripts/tokens-neutres.mjs <chemin de charte.css>
import { writeFileSync } from 'node:fs';
import { jeuCouleurs, verifierJeu } from '../lib/couleurs.mjs';
import { ECHELLE } from '../lib/direction.mjs';

const cible = process.argv[2];
if (!cible) { console.error('Usage : node tokens-neutres.mjs <chemin de charte.css>'); process.exit(2); }
const gris = { primaire: '#6b7280', accent: '#6b7280' };
const clair = jeuCouleurs(gris);
const sombre = jeuCouleurs({ ...gris, sombre: true });
const echecs = [...verifierJeu(clair), ...verifierJeu(sombre)].filter((v) => !v.ok);
if (echecs.length) { console.error(`Contrastes insuffisants : ${echecs.map((e) => `${e.avant}/${e.fond}`).join(', ')}`); process.exit(1); }
const lignes = (t, r = '  ') => Object.entries(t).map(([k, v]) => `${r}${k}: ${v};`).join('\n');
const pile = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
writeFileSync(cible, `/* Charte neutre, générée par .claude/skills/livrables/scripts/tokens-neutres.mjs : ne pas modifier.
   Palette grise pour intégrer les pages avant le choix de la direction artistique, contrastes vérifiés. Dans une
   zone (en-tête, haut de page), liens et boutons prennent --color-<zone>-accent. Remplacé par la marque du
   client (make charte) dès que l'audit ou le logo existe, puis par la piste choisie (make appliquer). Les autres tokens (espaces, durées) : base/tokens.css. */
:root {
${lignes(clair)}
  --font-titres: ${pile};
  --font-ui: ${pile};
  --radius: 0.5rem;
  --radius-small: 0.25rem;
  --radius-grand: 0.75rem;
${ECHELLE}
  color-scheme: light;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
${lignes(sombre, '    ')}
    color-scheme: dark;
  }
}

:root[data-theme="dark"] {
${lignes(sombre)}
  color-scheme: dark;
}
`);
console.log(`écrit ${cible} (${Object.keys(clair).length} couleurs par thème, contrastes conformes)`);
