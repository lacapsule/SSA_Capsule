// SPDX-License-Identifier: GPL-3.0-or-later
// Hook PreToolUse (Write, Edit, MultiEdit) : refuse le texte nouveau qui enfreint une règle mécanique du kit.
// Seul le texte écrit est lu (contenu d'un Write, nouvelle chaîne d'un Edit) : la dette déjà en place
// n'est pas bloquée, mais rien de neuf ne s'y ajoute (rule web-method, « contrôle en cliquet »).
//  1. tirets cadratin et demi-cadratin, même échappés (rule web-method, « Tirets ») : partout ;
//  2. innerHTML, outerHTML, insertAdjacentHTML, document.write (rule web-code) : fichiers JS, PHP, HTML ;
//     exception justifiée : commentaire « exception web-code : <raison> » sur la même ligne ;
//  3. couleur brute (#hex, rgb(), hsl()) dans une déclaration CSS hors tokens.css (rule web-design, N0) ;
//  4. outline: none ou 0 dans une règle qui n'est pas :focus sans :focus-visible (rule web-a11y).
// Exclus : node_modules, vendor, dist, .git, fichiers verrouillés et ce hook.
import path from 'node:path';

let entree = '';
process.stdin.on('data', (m) => { entree += m; });
process.stdin.on('end', () => {
  let donnees;
  try { donnees = JSON.parse(entree); } catch { process.exit(0); }
  const outil = donnees.tool_input ?? {};
  const fichier = outil.file_path ?? '';
  const textes = [];
  if (typeof outil.content === 'string') textes.push(outil.content);
  if (typeof outil.new_string === 'string') textes.push(outil.new_string);
  for (const e of outil.edits ?? []) if (typeof e.new_string === 'string') textes.push(e.new_string);
  if (!fichier || !textes.length) process.exit(0);

  const normalise = fichier.split(path.sep).join('/');
  if (/(^|\/)(node_modules|vendor|dist|\.git)\//.test(normalise)) process.exit(0);
  if (/(package-lock\.json|\.lock)$/.test(normalise) || normalise.endsWith('.claude/hooks/verifier-ecriture.mjs')) process.exit(0);

  const ext = path.extname(normalise).toLowerCase();
  const fautes = [];
  const texte = textes.join('\n');

  // 1. Tirets, y compris échappés.
  const tiret = new RegExp('[\\u2013\\u2014]|&[mn]dash;|&#x?(8211|8212|2013|2014);|\\\\201[34]\\b|\\\\u201[34]', 'i');
  for (const [i, ligne] of texte.split('\n').entries()) {
    if (tiret.test(ligne)) { fautes.push(`tiret cadratin ou demi-cadratin (ligne ${i + 1} du texte écrit : « ${ligne.trim().slice(0, 80)} ») : deux-points, virgule, parenthèses ou trait d'union à la place (rule web-method)`); break; }
  }

  // 2. Injection de HTML.
  if (['.js', '.mjs', '.cjs', '.ts', '.php', '.html', '.htm', '.astro', '.jsx', '.tsx'].includes(ext)) {
    const html = /\.(innerHTML|outerHTML)\s*\+?=(?!=)|\binsertAdjacentHTML\s*\(|\bdocument\.write(ln)?\s*\(/;
    for (const ligne of texte.split('\n')) {
      if (html.test(ligne) && !/exception web-code\s*:/.test(ligne)) {
        fautes.push(`injection de HTML (« ${ligne.trim().slice(0, 80)} ») : createElement et textContent, ou <template> cloné (rule web-code) ; exception justifiée : commentaire « exception web-code : <raison> » sur la ligne`);
        break;
      }
    }
  }

  if (ext === '.css') {
    // Commentaires retirés pour ne pas juger un exemple cité.
    const css = texte.replace(/\/\*[\s\S]*?\*\//g, '');
    // 3. Couleur brute dans une valeur, hors du fichier des tokens.
    if (path.basename(normalise) !== 'tokens.css') {
      const couleur = /:\s*[^;{}]*?(#[0-9a-f]{3,8}\b|\b(rgba?|hsla?|oklch|oklab|lab|lch)\s*\()[^{}]*?(;|}|$)/im;
      const m = css.match(couleur);
      if (m) fautes.push(`couleur brute « ${m[1].trim()} » hors de tokens.css : déclarer un token N0 et l'utiliser en var(--…) (rule web-design)`);
    }
    // 4. Contour supprimé sans remplacement.
    const regles = css.matchAll(/([^{}]+)\{([^{}]*)\}/g);
    for (const [, selecteur, corps] of regles) {
      if (/outline\s*:\s*(none|0)\b/.test(corps) && !/:focus-visible/.test(selecteur) && !/:focus(?!-)/.test(selecteur)) {
        fautes.push(`outline supprimé dans « ${selecteur.trim().slice(0, 60)} » : garder un :focus-visible visible (rule web-a11y)`);
        break;
      }
    }
  }

  if (fautes.length) {
    process.stderr.write(`Refusé (${path.basename(normalise)}) :\n- ${fautes.join('\n- ')}\n`);
    process.exit(2);
  }
});
