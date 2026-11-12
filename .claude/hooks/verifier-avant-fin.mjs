// SPDX-License-Identifier: GPL-3.0-or-later
// Hook Stop : avant de rendre la main, si le site a changé depuis le dernier « make verify » vert,
// demande de le lancer et de remplir la checklist du skill livraison (rule 00-organisation, « Remettre »).
// Le marqueur .claude/etat/verify-ok est écrit par la cible verify du Makefile, seulement si tout est vert.
// Ne bloque qu'une fois par tour (stop_hook_active) : l'humain garde la main si le contrôle ne peut pas tourner.
import { execFileSync } from 'node:child_process';
import { existsSync, statSync } from 'node:fs';
import path from 'node:path';

let entree = '';
process.stdin.on('data', (m) => { entree += m; });
process.stdin.on('end', () => {
  let donnees = {};
  try { donnees = JSON.parse(entree); } catch { /* entrée vide : on contrôle quand même */ }
  if (donnees.stop_hook_active) process.exit(0);

  const racine = process.env.CLAUDE_PROJECT_DIR || process.cwd();
  // Seulement dans un projet de site construit par make (pas dans le modèle ni la boîte).
  if (!existsSync(path.join(racine, 'Makefile')) || !existsSync(path.join(racine, 'src'))) process.exit(0);

  let modifies = [];
  try {
    const sortie = execFileSync('git', ['-C', racine, 'status', '--porcelain', '--untracked-files=all', '--', 'src', 'public', 'bin', 'Makefile', 'package.json'], { encoding: 'utf8' });
    modifies = sortie.split('\n').filter(Boolean).map((l) => l.slice(3).replace(/^.* -> /, ''));
  } catch { process.exit(0); }
  if (!modifies.length) process.exit(0);

  const marqueur = path.join(racine, '.claude/etat/verify-ok');
  const dateVerify = existsSync(marqueur) ? statSync(marqueur).mtimeMs : 0;
  const plusRecents = modifies.filter((f) => {
    const p = path.join(racine, f);
    return existsSync(p) && statSync(p).mtimeMs > dateVerify;
  });
  if (!plusRecents.length) process.exit(0);

  const liste = plusRecents.slice(0, 5).join(', ') + (plusRecents.length > 5 ? `, +${plusRecents.length - 5}` : '');
  process.stdout.write(JSON.stringify({
    decision: 'block',
    reason: `Fichiers du site modifiés après le dernier « make verify » vert (${liste}). Lancer make verify et les contrôles du skill web-audit adaptés au changement, puis remettre avec la checklist du skill livraison. Si le contrôle ne peut pas tourner, le dire en ligne « non vérifié ».`,
  }));
});
