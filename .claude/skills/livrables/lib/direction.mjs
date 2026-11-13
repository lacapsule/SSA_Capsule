// SPDX-License-Identifier: GPL-3.0-or-later
// Direction artistique (livrable 04) : trois pistes construites depuis le branding relevé à l'audit et le métier
// du client (donnees/directions.json). Chaque piste donne un tokens.css prêt pour le site (noms de la boîte),
// vérifié en contraste, et une planche rendue en clair et en sombre avec les vrais textes du client.
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { contraste, couleursDeMarque, jeuCouleurs, verifierJeu } from './couleurs.mjs';
import { echapper } from './document.mjs';
import { fontFace, obtenirPolice, pile } from './polices.mjs';
import { boutonsDe, lireMaquette } from './wireframes.mjs';

const ICI = path.dirname(fileURLToPath(import.meta.url));
export const DIRECTIONS = JSON.parse(readFileSync(path.join(ICI, '../donnees/directions.json'), 'utf8')).metiers;
const ratio = (v) => v.toFixed(2).replace('.', ',');

// Polices du système : gardées telles quelles (aucun fichier à servir), jamais téléchargées.
const SYSTEME = { georgia: 'serif', 'times new roman': 'serif', times: 'serif', arial: 'sans', helvetica: 'sans', verdana: 'sans', tahoma: 'sans', 'trebuchet ms': 'sans', 'segoe ui': 'sans', 'system-ui': 'sans' };
const GENERIQUES = new Set(['serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', '-apple-system', 'blinkmacsystemfont', 'ui-sans-serif', 'ui-serif']);

/** Première vraie famille d'une pile CSS relevée (« "Lato", Arial, sans-serif » → Lato). */
function familleRelevee(pileCss) {
  for (const f of (pileCss ?? '').split(',').map((x) => x.trim().replace(/["']/g, ''))) if (f && !GENERIQUES.has(f.toLowerCase())) return f;
  return null;
}

/** Police d'une piste : système (pile seule) ou Google Fonts (fichiers téléchargés). */
async function police(famille, classe, notes) {
  const sys = SYSTEME[famille.toLowerCase()];
  if (sys) return { famille, classe: sys, systeme: true, pile: pile(famille, sys), licence: 'police du système, aucun fichier' };
  const p = await obtenirPolice(famille);
  if (!p) { notes.push(`Police « ${famille} » introuvable sur Google Fonts : à demander au client avec sa licence web.`); return null; }
  return { famille, classe, pile: pile(famille, classe), telechargee: p, licence: p.licence };
}

/** Arrondis secondaires et échelle des tailles, communs à toutes les chartes (pistes et charte neutre). */
export const ECHELLE = `  --radius-pill: 999px;
  /* Échelle des tailles, bornée par les conventions de base (rule web-design) : titre principal 2,75 rem au plus,
     titres de section 2 rem au plus, texte courant 1,0625 rem, introduction 1,25 rem au plus. */
  --step--2: 0.8125rem;
  --step--1: 0.9375rem;
  --step-0: 1.0625rem;
  --step-1: clamp(1.125rem, 1.05rem + 0.3vw, 1.25rem);
  --step-2: clamp(1.5rem, 1.25rem + 1vw, 1.875rem);
  --step-3: clamp(1.875rem, 1.4rem + 2vw, 2.75rem);`;

/**
 * tokens.css d'une piste : polices, couleurs claires par défaut, sombres selon la préférence du système ou data-theme.
 * baseFichiers : adresse du dossier des polices vu par la feuille (livrable, ou /polices une fois posée dans le site).
 */
export function tokensCss(piste, baseFichiers) {
  const lignes = (t) => Object.entries(t).map(([k, v]) => `  ${k}: ${v};`).join('\n');
  const faces = [piste.titres, piste.texte].filter((p) => p?.telechargee).map((p) => fontFace(p.telechargee, baseFichiers));
  return `/* Piste ${piste.numero} « ${piste.nom} » (livrable 04, direction artistique) : généré, à poser tel quel comme N0.
   Contrastes vérifiés (texte 4,5:1, contrôles 3:1) dans les deux thèmes. Polices : ${[piste.titres, piste.texte].map((p) => `${p.famille} (${p.licence})`).join(', ')}. */
${[...new Set(faces)].join('\n')}

:root {
${lignes(piste.clair)}
  --font-titres: ${piste.titres.pile};
  --font-ui: ${piste.texte.pile};
  --radius: ${piste.rayon}rem;
  --radius-small: ${Math.max(0.125, piste.rayon / 2)}rem;
  --radius-grand: ${Math.max(0.25, piste.rayon * 1.5)}rem;
${ECHELLE}
  color-scheme: light;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
${lignes(piste.sombre).replace(/^/gm, '  ')}
    color-scheme: dark;
  }
}

:root[data-theme="dark"] {
${lignes(piste.sombre)}
  color-scheme: dark;
}
`;
}

/** Planche de démonstration d'une piste, avec les vrais contenus du client. */
function planche(piste, contenu, theme) {
  return `<!DOCTYPE html>
<html lang="fr" data-theme="${theme}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Piste ${piste.numero} : ${echapper(piste.nom)}</title>
<link rel="stylesheet" href="tokens.css"><link rel="stylesheet" href="../../../_commun/planche.css"></head>
<body>
<header class="pl-entete">
  ${contenu.logo ? `<img class="pl-logo" src="${echapper(contenu.logo)}" alt="${echapper(contenu.nom)}">` : `<span class="pl-nom">${echapper(contenu.nom)}</span>`}
  <nav aria-label="Menu"><ul>${contenu.menu.map((m) => `<li><a href="#">${echapper(m)}</a></li>`).join('')}</ul></nav>
  <a class="pl-bouton" href="#">${echapper(contenu.action)}</a>
</header>
<section class="pl-heros">
  <h1>${echapper(contenu.titre)}</h1>
  ${contenu.chapo ? `<p>${echapper(contenu.chapo)}</p>` : ''}
  <p class="pl-actions"><a class="pl-bouton" href="#">${echapper(contenu.action)}</a> <a class="pl-bouton pl-bouton-secondaire" href="#">${echapper(contenu.actionSecondaire)}</a></p>
</section>
<section class="pl-cartes">
  ${contenu.cartes.map((c) => `<article class="pl-carte"><h2>${echapper(c.titre)}</h2><p>${echapper(c.texte)}</p><a href="#">${echapper(`Voir : ${c.titre}`)}</a></article>`).join('')}
</section>
<section class="pl-elements">
  <div><h2>Typographie</h2><p class="pl-specimen-titre">${echapper(piste.titres.famille)}</p><p>${echapper(piste.texte.famille)} pour le texte courant : ${echapper(contenu.chapo || contenu.titre)}</p></div>
  <div><h2>Formulaire</h2><label>Votre e-mail<input type="email" value="" placeholder="nom@exemple.fr"></label><p class="pl-erreur">Message d'erreur relié au champ</p><a class="pl-bouton pl-focus" href="#">Bouton au focus clavier</a></div>
</section>
<section class="pl-nuancier"><h2>Couleurs (${theme === 'light' ? 'clair' : 'sombre'})</h2><ul>
${Object.entries(theme === 'light' ? piste.clair : piste.sombre).filter(([k]) => !/danger|success|warning|info|line|surface-2|strong/.test(k)).map(([k, v]) => `<li><span style="background:${v}"></span><code>${k.replace('--color-', '')}</code> <code>${v}</code></li>`).join('\n')}
</ul></section>
</body>
</html>
`;
}

/**
 * Construit les trois pistes et le corps du livrable. entrees : { inventaire, mode, arborescence, metier, dossier, navigateur, maquetteAccueil }.
 * Renvoie { corps, avertissements, sourcesRendues }.
 */
export async function directionArtistique({ inventaire, mode, arborescence, metier, type, dossier, navigateur, maquetteAccueil }) {
  const avert = [];
  const notes = [];
  const dir = DIRECTIONS[metier] ?? DIRECTIONS.defaut;
  if (!DIRECTIONS[metier]) avert.push(`Métier « ${metier || 'non renseigné'} » inconnu : champ « - metier: » du mode d'emploi, parmi ${Object.keys(DIRECTIONS).filter((k) => k !== 'defaut').join(', ')}.`);
  const id = inventaire?.identite ?? { couleurs: [], couleursLogo: [] };
  const marque = couleursDeMarque(id);
  if (!marque.primaire) avert.push('Aucune couleur de marque relevée (audit absent ou site sans couleur) : demander la charte au client.');

  // Polices de la piste fidèle : celles du site actuel si elles sont disponibles, sinon celles du métier.
  const releveTitres = familleRelevee(id.polices?.usages?.titres);
  const releveTexte = familleRelevee(id.polices?.usages?.corps);
  const fideleTitres = (releveTitres && await police(releveTitres, SYSTEME[releveTitres.toLowerCase()] ?? 'sans', notes)) || await police(...dir.modernisee.titres, notes);
  const fideleTexte = (releveTexte && await police(releveTexte, SYSTEME[releveTexte.toLowerCase()] ?? 'sans', notes)) || await police(...dir.modernisee.texte, notes);

  const pistes = [
    { numero: 1, cle: 'fidele', nom: 'Fidèle', principe: "Les couleurs et les polices du site actuel, gardées et rendues conformes : en-tête dans la couleur de la marque, le client reconnaît son site, qui gagne en lisibilité.", primaire: marque.primaire, accent: marque.action ?? marque.accent, secondaire: marque.secondaire, fond: marque.fondActuel, titres: fideleTitres, texte: fideleTexte, rayon: dir.rayon, style: 'entete-marque' },
    { numero: 2, cle: 'modernisee', nom: 'Modernisée', principe: `Les mêmes teintes, utilisées avec retenue sur un fond clair et neutre, une typographie actuelle adaptée au métier (${dir.libelle.toLowerCase()}) et plus d'espace.`, primaire: marque.primaire, accent: marque.accent, secondaire: marque.secondaire, fond: null, titres: await police(...dir.modernisee.titres, notes), texte: await police(...dir.modernisee.texte, notes), rayon: dir.rayon, style: 'aere' },
    { numero: 3, cle: 'contrastee', nom: 'Contrastée', principe: "Un parti pris plus marqué : la couleur de marque en grand dans l'en-tête et le haut de page, une typographie à plus fort caractère, des angles plus nets.", primaire: marque.primaire, accent: marque.accent, secondaire: marque.secondaire, fond: null, titres: await police(...dir.contrastee.titres, notes), texte: await police(...dir.contrastee.texte, notes), rayon: Math.max(0.125, dir.rayon / 2), style: 'marque-en-grand' },
  ].filter((p) => p.titres && p.texte);
  // Outil interne : le design d'administration de référence est la coquille de la boîte (gabarits/admin-coquille) ;
  // seule une piste, qui en reprend le logo et la couleur d'accent du client.
  if (type === 'outil-interne') {
    pistes.splice(1);
    Object.assign(pistes[0], { cle: 'administration', nom: 'Administration', style: 'neutre', principe: "La coquille d'administration de référence (une seule pour tous les outils), avec le logo et la couleur d'accent du client : l'outil reste familier et lisible, sans charte à inventer." });
  }

  // Contenus réels du client pour les planches (accueil du site actuel, arborescence, maquette d'accueil).
  const accueil = inventaire?.pages?.[0];
  const logo = (id.logos ?? []).find((l) => l.fichier && /svg/.test(l.fichier)) ?? (id.logos ?? []).find((l) => l.fichier && !/icone/.test(l.type));
  // Libellés d'action : textes entre « » des zones de la maquette d'accueil (pas de la ligne d'en-tête, qui porte l'intention).
  const boutons = [...new Set(lireMaquette(maquetteAccueil ?? '').zones.flatMap((z) => boutonsDe(z.description)))];
  const contenu = {
    nom: mode?.nom ?? 'Site',
    logo: logo ? `../../../../source/existant/${logo.fichier}` : null,
    menu: arborescence.length ? arborescence.map((p) => p.page) : (inventaire?.menu ?? []).map((m) => m.texte),
    action: boutons[0] ?? 'Nous contacter',
    actionSecondaire: boutons[1] ?? (arborescence[1]?.page ? `Voir : ${arborescence[1].page}` : 'En savoir plus'),
    titre: accueil?.h1?.[0] ?? mode?.nom ?? 'Titre principal',
    chapo: accueil?.blocs?.find((b) => b.balise === 'p' && b.texte.length > 30)?.texte ?? '',
    cartes: arborescence.filter((p) => p.adresse !== '/' && !/contact/i.test(p.page)).slice(0, 3).map((p) => ({ titre: p.page, texte: p.intention ? `Pour qui cherche : ${p.intention}` : 'Page du site.' })),
  };
  if (!contenu.cartes.length) contenu.cartes = [{ titre: contenu.nom, texte: contenu.chapo || 'Page du site.' }];
  if (!logo) avert.push('Logo du client absent de l\'audit : les planches montrent le nom en texte.');

  let sourcesRendues = '';
  const page = await navigateur.newPage({ viewport: { width: 1280, height: 900 } });
  let corps = type === 'outil-interne' ? `<h2>Apparence de l'outil</h2>
<p>L'outil reprend la présentation d'administration de référence, avec le logo et la couleur d'accent du client, vérifiés en contraste dans les deux thèmes.</p>` : `<h2>Choisir une direction</h2>
<p>Voici trois directions possibles pour l'apparence du nouveau site. Toutes reprennent la structure validée dans les wireframes et respectent les contrastes exigés pour l'accessibilité, en thème clair comme en thème sombre. Le client en choisit une ; elle peut être ajustée (une couleur, une police) avant la validation.</p>
<h2>Point de départ</h2>
<table><tbody>
<tr><th scope="row">Métier</th><td>${echapper(dir.libelle)}</td></tr>
<tr><th scope="row">Ton</th><td>${echapper(dir.ton)}</td></tr>
<tr><th scope="row">Couleur de marque relevée</th><td>${marque.primaire ? `<code>${marque.primaire}</code>` : 'aucune'}${marque.accent && marque.accent !== marque.primaire ? `, accent <code>${marque.accent}</code> (contraste sur blanc ${ratio(contraste(marque.accent, '#ffffff'))}:1${contraste(marque.accent, '#ffffff') < 4.5 ? ', insuffisant pour du texte : décliné plus foncé dans les pistes' : ''})` : ''}</td></tr>
<tr><th scope="row">Polices relevées</th><td>${echapper([...new Set([releveTitres, releveTexte].filter(Boolean))].join(', ') || 'aucune')}</td></tr>
<tr><th scope="row">Photos</th><td>${echapper(dir.photos)}</td></tr>
<tr><th scope="row">À éviter</th><td>${echapper(dir.eviter.join(' ; '))}</td></tr>
</tbody></table>
${(id.univers ?? []).length ? `<h2>Univers</h2>
<p>Ce qui fait reconnaître le client, relevé sur son site actuel. La direction fidèle reprend chacun de ces signes ; les deux autres les réinterprètent (même idée, forme renouvelée). Les briques de la boîte à outils sont adaptées à cet univers, jamais montrées telles quelles.</p>
<ul class="vignettes">${id.univers.map((u) => `<li><figure><img src="../../source/existant/${echapper(u.capture)}" alt="Exemple : ${echapper(u.description)}"><figcaption>${echapper(u.description)}</figcaption></figure></li>`).join('')}</ul>` : ''}`;

  for (const p of pistes) {
    p.clair = jeuCouleurs({ primaire: p.primaire, accent: p.accent, secondaire: p.secondaire, fond: p.fond, sombre: false, style: p.style });
    p.sombre = jeuCouleurs({ primaire: p.primaire, accent: p.accent, secondaire: p.secondaire, sombre: true, style: p.style });
    const verif = [...verifierJeu(p.clair), ...verifierJeu(p.sombre)];
    const echecs = verif.filter((v) => !v.ok);
    if (echecs.length) avert.push(`Piste ${p.numero} : ${echecs.length} contraste(s) insuffisant(s) (${echecs.slice(0, 3).map((e) => `${e.avant} sur ${e.fond} ${ratio(e.ratio)}:1`).join(', ')}).`);
    const rep = path.join(dossier, 'pistes', `${p.numero}-${p.cle}`);
    mkdirSync(rep, { recursive: true });
    const css = tokensCss(p, '../../../../source/polices');
    writeFileSync(path.join(rep, 'tokens.css'), css);
    for (const theme of ['light', 'dark']) {
      const html = planche(p, contenu, theme);
      writeFileSync(path.join(rep, `planche-${theme === 'light' ? 'clair' : 'sombre'}.html`), html);
      sourcesRendues += css + html;
      await page.goto('file://' + path.join(rep, `planche-${theme === 'light' ? 'clair' : 'sombre'}.html`), { waitUntil: 'load' });
      await page.evaluate(() => document.fonts.ready);
      await page.screenshot({ path: path.join(rep, `${theme === 'light' ? 'clair' : 'sombre'}.png`), fullPage: true });
    }
    const minimum = Math.min(...verif.map((v) => v.ratio / v.cible));
    corps += `<h2>Piste ${p.numero} : ${echapper(p.nom)}</h2>
<p>${echapper(p.principe)}</p>
<table><tbody>
<tr><th scope="row">Couleur de marque</th><td><code>${p.clair['--color-marque']}</code></td></tr>
<tr><th scope="row">Accent (liens, boutons)</th><td>clair <code>${p.clair['--color-accent']}</code> · sombre <code>${p.sombre['--color-accent']}</code></td></tr>
<tr><th scope="row">Fond</th><td>clair <code>${p.clair['--color-bg']}</code> · sombre <code>${p.sombre['--color-bg']}</code></td></tr>
<tr><th scope="row">Titres</th><td>${echapper(p.titres.famille)} (${echapper(p.titres.licence)})</td></tr>
<tr><th scope="row">Texte</th><td>${echapper(p.texte.famille)} (${echapper(p.texte.licence)})</td></tr>
<tr><th scope="row">Arrondis</th><td>${String(p.rayon).replace('.', ',')} rem</td></tr>
<tr><th scope="row">Contrastes</th><td>${verif.length} paires contrôlées, ${echecs.length ? `${echecs.length} insuffisante(s)` : 'toutes conformes'} (marge minimale ${Math.round((minimum - 1) * 100)} %)</td></tr>
</tbody></table>
<div class="ecrans ecrans-egaux">
<figure><img src="pistes/${p.numero}-${p.cle}/clair.png" alt="Piste ${p.numero}, thème clair"><figcaption>Thème clair</figcaption></figure>
<figure><img src="pistes/${p.numero}-${p.cle}/sombre.png" alt="Piste ${p.numero}, thème sombre"><figcaption>Thème sombre</figcaption></figure>
</div>`;
  }
  await page.close();
  for (const n of [...new Set(notes)]) avert.push(n);
  corps += `<h2>Ce que le client valide</h2>
<p>Le numéro de la piste retenue, choisie en voyant ses propres pages, et les ajustements éventuels (une couleur, une police, les arrondis). C'est la validation 3 : ensuite, l'apparence du site ne change plus sans nouvel accord.</p>`;
  return { corps, avertissements: avert, sourcesRendues, pistes: pistes.map((p) => ({ numero: p.numero, nom: p.nom, tokens: path.relative(process.cwd(), path.join(dossier, 'pistes', `${p.numero}-${p.cle}`, 'tokens.css')) })) };
}

