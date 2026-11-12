#!/usr/bin/env node
// Ordre des propriétés CSS : vérifie (--check) ou réécrit chaque bloc de déclarations,
// par familles, en tri stable. Sans dépendance.
// Copie de la brique modules/ordre-proprietes-css de ../kouzia-boite-a-outils, qui fait foi : la corriger là-bas d'abord.
// Usage : node ordre-css.mjs [--check] <fichier.css | dossier> ...
import { readFileSync, writeFileSync, readdirSync, statSync } from "node:fs";
import { join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

export const FAMILLES = [
  "propriétés personnalisées",
  "position",
  "affichage",
  "dimensions",
  "margin",
  "padding",
  "bordure",
  "typographie",
  "couleur",
  "fond",
  "ombre",
  "transform",
  "animation",
  "transition",
  "overflow",
  "z-index",
  "reste",
];

// Premier motif qui correspond, dans cet ordre (les cas particuliers avant les motifs larges).
const REGLES = [
  [0, /^(--.*|all)$/],
  [10, /^(box-shadow|text-shadow)$/],
  [7, /^(overflow-wrap|text-overflow|line-clamp)$/],
  [3, /^contain-intrinsic-.*$/],
  [14, /^(overflow(-x|-y|-inline|-block|-clip-margin)?|overscroll-behavior.*|scroll-.*|scrollbar-.*|contain|content-visibility)$/],
  [1, /^(position|inset(-.*)?|top|right|bottom|left|float|clear)$/],
  [2, /^(display|flex(-.*)?|grid(-.*)?|align-.*|justify-.*|place-.*|gap|row-gap|column-gap|order|columns|column-.*|container(-.*)?|box-sizing|box-(orient|direction|pack|align|flex)|vertical-align|list-style(-.*)?|table-layout|caption-side|empty-cells|content|counter-.*|visibility|appearance)$/],
  [3, /^((min-|max-)?(width|height|inline-size|block-size)|aspect-ratio|field-sizing)$/],
  [4, /^margin(-.*)?$/],
  [5, /^padding(-.*)?$/],
  [6, /^(border(-.*)?|outline(-.*)?)$/],
  [7, /^(font(-.*)?|line-height|letter-spacing|word-.*|text-.*|white-space(-.*)?|hyphens|hyphenate-.*|tab-size|direction|unicode-bidi|writing-mode|quotes|initial-letter)$/],
  [8, /^(color|color-scheme|caret(-.*)?|accent-color|fill(-.*)?|stroke(-.*)?|forced-color-adjust|print-color-adjust)$/],
  [9, /^(background(-.*)?|opacity|filter|backdrop-filter|mix-blend-mode|isolation|object-(fit|position)|mask(-.*)?|clip-path|image-rendering)$/],
  [11, /^(transform(-.*)?|translate|rotate|scale|perspective(-.*)?|backface-visibility|offset(-.*)?|will-change)$/],
  [12, /^(animation(-.*)?|view-timeline(-.*)?|scroll-timeline(-.*)?|timeline-scope)$/],
  [13, /^transition(-.*)?$/],
  [15, /^z-index$/],
];

const sansPrefixe = (p) => (p.startsWith("--") ? p : p.toLowerCase().replace(/^-(webkit|moz|ms|o)-/, ""));

export function familleDe(propriete) {
  const p = sansPrefixe(propriete);
  for (const [f, motif] of REGLES) if (motif.test(p)) return f;
  return 16;
}

// Noms qui commencent comme un raccourci sans en être un détail.
const FAUX_DETAILS = new Set(["overflow-wrap", "content-visibility", "contain-intrinsic-size", "contain-intrinsic-width", "contain-intrinsic-height", "contain-intrinsic-inline-size", "contain-intrinsic-block-size"]);
const RACCOURCIS = { gap: ["row-gap", "column-gap"], inset: ["top", "right", "bottom", "left"], font: ["line-height"], flex: ["flex-grow", "flex-shrink", "flex-basis"] };

// Deux déclarations qui touchent la même propriété calculée : leur ordre relatif ne doit pas changer.
function chevauchent(a, b) {
  const x = sansPrefixe(a);
  const y = sansPrefixe(b);
  if (x.startsWith("--") || y.startsWith("--")) return x === y;
  if (x === "all" || y === "all" || x === y) return true;
  const detail = (court, long) => long.startsWith(`${court}-`) && !FAUX_DETAILS.has(long);
  return detail(x, y) || detail(y, x) || (RACCOURCIS[x] || []).includes(y) || (RACCOURCIS[y] || []).includes(x);
}

// Lecture : arbre de blocs, sans interpréter les valeurs. Chaînes, url(), parenthèses,
// crochets et accolades d'une propriété personnalisée sont sautés : ni « ; » ni « { » n'y coupent.
function analyser(src) {
  let i = 0;
  const n = src.length;

  const sauterChaine = () => {
    const q = src[i++];
    while (i < n && src[i] !== q && src[i] !== "\n") i += src[i] === "\\" ? 2 : 1;
    i++;
  };
  const sauterCommentaire = () => {
    const fin = src.indexOf("*/", i + 2);
    i = fin < 0 ? n : fin + 2;
  };
  const sauterUrl = () => {
    // url( non citée : tout jusqu'à la parenthèse fermante, échappements compris.
    while (i < n && src[i] !== ")") i += src[i] === "\\" ? 2 : 1;
  };

  const lireBloc = (finAttendue) => {
    const elements = [];
    for (;;) {
      while (i < n && /\s/.test(src[i])) i++;
      if (i >= n) return elements;
      if (src[i] === "}") {
        if (finAttendue) { i++; return elements; }
        i++;
        continue;
      }
      if (src.startsWith("/*", i)) {
        const debut = i;
        sauterCommentaire();
        elements.push({ type: "commentaire", debut, fin: i });
        continue;
      }
      const debut = i;
      let profondeur = 0;
      let commentaire = false;
      for (;;) {
        if (i >= n) {
          elements.push({ type: "instruction", debut, fin: i, pointVirgule: false, commentaire });
          return elements;
        }
        const c = src[i];
        if (c === "\\") { i += 2; continue; }
        if (c === '"' || c === "'") { sauterChaine(); continue; }
        if (c === "/" && src[i + 1] === "*") { commentaire = true; sauterCommentaire(); continue; }
        if (c === "(" || c === "[") {
          if (c === "(" && /url\($/i.test(src.slice(Math.max(debut, i - 3), i + 1))) {
            i++;
            while (i < n && /\s/.test(src[i])) i++;
            if (src[i] !== '"' && src[i] !== "'") sauterUrl();
            profondeur++;
            continue;
          }
          profondeur++;
          i++;
          continue;
        }
        if (c === ")" || c === "]") { profondeur = Math.max(0, profondeur - 1); i++; continue; }
        if (profondeur > 0) { i++; continue; }
        if (c === "{") {
          if (/^--[\w-]*\s*:/.test(src.slice(debut, i))) {
            // Valeur de propriété personnalisée entre accolades : sautée d'un bloc.
            let p = 0;
            do {
              if (src[i] === '"' || src[i] === "'") { sauterChaine(); continue; }
              if (src[i] === "{") p++;
              else if (src[i] === "}") p--;
              i++;
            } while (i < n && p > 0);
            continue;
          }
          const prelude = { debut, fin: i };
          i++;
          const enfants = lireBloc(true);
          elements.push({ type: "bloc", prelude, enfants, debut, fin: i });
          break;
        }
        if (c === ";") {
          elements.push({ type: "instruction", debut, fin: i, pointVirgule: true, commentaire });
          i++;
          break;
        }
        if (c === "}") {
          elements.push({ type: "instruction", debut, fin: i, pointVirgule: false, commentaire });
          break;
        }
        i++;
      }
    }
  };
  return lireBloc(false);
}

const resume = (t) => {
  const s = t.replace(/\s+/g, " ").trim();
  return s.length > 70 ? `${s.slice(0, 67)}...` : s;
};

/**
 * @returns {{ sortie: string, ecarts: {ligne: number, regle: string, message: string}[],
 *   laissees: {ligne: number, regle: string, raison: string}[], blocs: number }}
 */
export function ordonner(src) {
  const debutsDeLigne = [0];
  for (let k = 0; k < src.length; k++) if (src[k] === "\n") debutsDeLigne.push(k + 1);
  const ligne = (pos) => {
    let bas = 0, haut = debutsDeLigne.length - 1;
    while (bas < haut) {
      const m = (bas + haut + 1) >> 1;
      if (debutsDeLigne[m] <= pos) bas = m; else haut = m - 1;
    }
    return bas + 1;
  };

  const remplacements = [];
  const ecarts = [];
  const laissees = [];
  let blocs = 0;

  const traiter = (elements, parents, premierNiveau) => {
    // Nom lisible : le sélecteur, puis entre parenthèses les at-règles qui l'entourent.
    const selecteurs = parents.filter((p) => !p.startsWith("@"));
    const enveloppes = parents.filter((p) => p.startsWith("@"));
    const contexte = (selecteurs.join(" ") || parents[parents.length - 1] || "") + (selecteurs.length && enveloppes.length ? ` (${enveloppes.join(", ")})` : "");
    const aCommentaire = elements.some((e) => e.type === "commentaire" || (e.type === "instruction" && e.commentaire));
    let serie = [];
    let declarations = 0;
    let horsOrdreCommente = false;

    const trierSerie = () => {
      const s = serie;
      serie = [];
      if (s.length < 2 || premierNiveau) return;
      const ordre = s.map((d, k) => ({ ...d, k })).sort((a, b) => a.famille - b.famille || a.k - b.k);
      const premier = ordre.findIndex((d, k) => d.k !== k);
      if (premier < 0) return;
      if (aCommentaire) {
        horsOrdreCommente = true;
        return;
      }
      for (let a = 0; a < s.length; a++) {
        for (let b = a + 1; b < s.length; b++) {
          if (s[a].famille > s[b].famille && chevauchent(s[a].propriete, s[b].propriete)) {
            laissees.push({ ligne: ligne(s[b].debut), regle: contexte, raison: `remonter « ${s[b].propriete} » (${FAMILLES[s[b].famille]}) avant « ${s[a].propriete} » (${FAMILLES[s[a].famille]}) changerait la cascade (à reprendre à la main)` });
            return;
          }
        }
      }
      const monte = ordre[premier];
      const depasse = s[premier];
      ecarts.push({
        ligne: ligne(monte.debut),
        regle: contexte,
        message: `« ${monte.propriete} » (${FAMILLES[monte.famille]}) doit venir avant « ${depasse.propriete} » (${FAMILLES[depasse.famille]})`,
      });
      s.forEach((emplacement, k) => {
        if (ordre[k] !== emplacement) remplacements.push([emplacement.debut, emplacement.fin, ordre[k].texte]);
      });
    };

    for (const e of elements) {
      if (e.type === "instruction") {
        const texte = src.slice(e.debut, e.fin).replace(/\s+$/, "");
        const m = /^(--[\w-]*|-?[A-Za-z_][\w-]*)\s*:/.exec(texte);
        if (m) {
          declarations++;
          serie.push({ debut: e.debut, fin: e.debut + texte.length, texte, propriete: m[1], famille: familleDe(m[1]) });
          continue;
        }
      }
      // Un commentaire ne coupe pas la série (la règle est alors seulement examinée) ;
      // une at-règle sans bloc ou un bloc imbriqué la termine.
      if (e.type === "commentaire") continue;
      trierSerie();
      if (e.type === "bloc") {
        const prelude = resume(src.slice(e.prelude.debut, e.prelude.fin));
        traiter(e.enfants, [...parents, prelude], false);
      }
    }
    trierSerie();
    if (declarations > 0) blocs++;
    if (horsOrdreCommente) {
      const d = elements.find((e) => e.type === "instruction") || elements[0];
      laissees.push({ ligne: ligne(d.debut), regle: contexte, raison: "hors ordre, mais elle contient un commentaire (à reprendre à la main)" });
    }
  };

  traiter(analyser(src), [], true);
  let sortie = src;
  for (const [debut, fin, texte] of remplacements.sort((a, b) => b[0] - a[0])) sortie = sortie.slice(0, debut) + texte + sortie.slice(fin);
  ecarts.sort((a, b) => a.ligne - b.ligne);
  laissees.sort((a, b) => a.ligne - b.ligne);
  return { sortie, ecarts, laissees, blocs };
}

const IGNORES = new Set(["node_modules", "vendor", ".git", "dist"]);
function fichiersCss(cible, liste = []) {
  const st = statSync(cible);
  if (st.isFile()) liste.push(cible);
  else if (st.isDirectory()) {
    for (const nom of readdirSync(cible).sort()) {
      const chemin = join(cible, nom);
      if (IGNORES.has(nom) || nom.startsWith(".")) continue;
      if (statSync(chemin).isDirectory()) fichiersCss(chemin, liste);
      else if (nom.endsWith(".css") && !nom.endsWith(".min.css")) liste.push(chemin);
    }
  }
  return liste;
}

const AIDE = `Ordre des propriétés CSS, par familles :
  ${FAMILLES.join(" > ")}

  node ordre-css.mjs [--check] <fichier.css | dossier> ...

  sans option   réécrit les fichiers dont une règle est hors ordre
  --check       ne réécrit rien : fichier:ligne de chaque règle hors ordre, code 1 s'il y en a
  --aide        cette aide

Codes de sortie : 0 en ordre (ou réécrit), 1 règle hors ordre (--check), 2 usage ou fichier illisible.`;

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const args = process.argv.slice(2);
  const verifier = args.includes("--check");
  const cibles = args.filter((a) => !a.startsWith("--"));
  const inconnues = args.filter((a) => a.startsWith("--") && a !== "--check");
  if (args.includes("--aide") || args.includes("--help")) {
    console.log(AIDE);
    process.exit(0);
  }
  if (inconnues.length || !cibles.length) {
    console.error(inconnues.length ? `Option inconnue : ${inconnues[0]} (voir --aide)` : "Usage : node ordre-css.mjs [--check] <fichier.css | dossier> ...");
    process.exit(2);
  }
  let fichiers;
  try {
    fichiers = cibles.flatMap((c) => fichiersCss(c));
  } catch (e) {
    console.error(`Introuvable : ${e.path || e.message}`);
    process.exit(2);
  }
  let horsOrdre = 0, fichiersTouches = 0, laisseesTotal = 0;
  for (const f of fichiers) {
    const nom = relative(process.cwd(), f) || f;
    const src = readFileSync(f, "utf8");
    const { sortie, ecarts, laissees } = ordonner(src);
    horsOrdre += ecarts.length;
    laisseesTotal += laissees.length;
    if (ecarts.length) fichiersTouches++;
    if (verifier) for (const e of ecarts) console.log(`${nom}:${e.ligne} : ${e.regle} : ${e.message}`);
    else if (ecarts.length) {
      writeFileSync(f, sortie);
      console.log(`${nom} : ${ecarts.length} règle(s) réordonnée(s)`);
    }
    for (const l of laissees) console.log(`${nom}:${l.ligne} : ${l.regle} : laissée telle quelle, ${l.raison}`);
  }
  const notes = laisseesTotal ? `, ${laisseesTotal} laissée(s) telle(s) quelle(s)` : "";
  if (verifier) {
    console.log(horsOrdre ? `${horsOrdre} règle(s) hors ordre dans ${fichiersTouches} fichier(s) sur ${fichiers.length}${notes}.` : `${fichiers.length} fichier(s) : ordre respecté${notes}.`);
    process.exit(horsOrdre ? 1 : 0);
  }
  console.log(`${fichiers.length} fichier(s), ${horsOrdre} règle(s) réordonnée(s) dans ${fichiersTouches} fichier(s)${notes}.`);
}
