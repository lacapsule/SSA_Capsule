// SPDX-License-Identifier: GPL-3.0-or-later
// Reprise du contenu du site actuel (skill web-contenu, « Reprendre tout le contenu ») : chaque paragraphe, élément
// de liste ou citation de la page remplacée doit se retrouver dans la nouvelle page qui la remplace, reformulé ou non.
// Comparaison par les mots porteurs de sens (noms, verbes, chiffres) : une reformulation les garde, un résumé les perd.
// Une suppression voulue se justifie dans la maquette de la page : « Retiré : « extrait » (raison) ».

const VIDES = new Set(('alors aussi autre autres avec avoir cela celle celles celui cette ceux chaque comme dans depuis donc '
  + 'elle elles encore entre etre faire leur leurs mais meme nous notre nos pour plus sans sera sont sous tous tout toute '
  + 'toutes tres une vous votre vos ainsi afin chez dont lors quand quel quelle quels quelles qui que quoi sur par les des '
  + 'est ont aux ses son sa ce cet ces ici the and').split(' '));

/** Mots porteurs de sens d'un texte : minuscules sans accents, 3 lettres et plus ou chiffres, pluriel simple retiré. */
export function motsPorteurs(texte) {
  return String(texte ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/[’']/g, ' ').split(/[^a-z0-9]+/)
    .filter((m) => (m.length >= 3 || /^\d+$/.test(m)) && !VIDES.has(m))
    .map((m) => (m.length > 4 ? m.replace(/(s|x)$/, '') : m));
}

/** Unités de contenu d'une page (blocs de l'audit ou de la nouvelle page) : texte courant de 5 mots porteurs au moins. */
export function unites(blocs) {
  return blocs.filter((b) => /^(p|li|blockquote|dd|td)$/.test(b.balise) && motsPorteurs(b.texte).length >= 5);
}

/** Part des mots porteurs d'une unité présents dans un texte candidat (0 à 1). */
function couverture(mots, candidat) {
  const ens = new Set(motsPorteurs(candidat));
  return mots.filter((m) => ens.has(m)).length / mots.length;
}

/**
 * anciens : blocs de la page remplacée ; nouveaux : blocs des pages qui la remplacent ; retraits : textes des lignes
 * « Retiré : » des maquettes. Renvoie { manquantes: [texte], sections: [titre], mots: { ancien, nouveau } }.
 */
export function comparerContenu(anciens, nouveaux, retraits = [], seuil = 0.6) {
  const textesNeufs = nouveaux.map((b) => b.texte);
  // Candidats : chaque bloc seul et chaque paire de blocs voisins (un paragraphe découpé en deux reste repris).
  const candidats = [...textesNeufs, ...textesNeufs.slice(1).map((t, i) => `${textesNeufs[i]} ${t}`)];
  const manquantes = [];
  const parSection = new Map();
  let section = null;
  for (const b of anciens) {
    if (/^h[1-4]$/.test(b.balise)) { section = b.texte; continue; }
    if (!unites([b]).length) continue;
    const mots = motsPorteurs(b.texte);
    const reprise = candidats.some((c) => couverture(mots, c) >= seuil);
    const justifiee = retraits.some((r) => couverture(mots, r) >= seuil);
    const s = parSection.get(section) ?? { total: 0, manque: 0 };
    s.total += 1;
    if (!reprise && !justifiee) { manquantes.push(b.texte); s.manque += 1; }
    parSection.set(section, s);
  }
  const sections = [...parSection].filter(([t, s]) => t && s.total >= 2 && s.manque / s.total >= 0.5).map(([t]) => t);
  const compte = (l) => unites(l).reduce((n, b) => n + motsPorteurs(b.texte).length, 0);
  return { manquantes, sections, mots: { ancien: compte(anciens), nouveau: compte(nouveaux) } };
}
