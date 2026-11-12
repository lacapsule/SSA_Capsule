// SPDX-License-Identifier: GPL-3.0-or-later
// Wireframes : maquettes en texte (docs/maquettes/<page>.md, format du skill web-contenu) rendues en zoning
// basse fidélité, sans couleur de marque ni contenu inventé : chaque zone montre sa place, sa forme et sa note.
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { echapper } from './document.mjs';

// La brique de la boîte prévue pour une zone est une note pour l'agent, jamais pour le client.
const pourLeClient = (t) => t.replace(/\s*;?\s*briques?\s+[a-z-]+\/[a-z0-9/-]+(\s*(et|,)\s*[a-z-]+\/[a-z0-9/-]+)*/gi, '').replace(/\s*;\s*$/, '').trim();
const sansAccent = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

/** Nom de fichier de la maquette d'une adresse : « / » → accueil, « /a/b/ » → a-b. */
export const slugAdresse = (adresse) => adresse.replace(/^\/|\/$/g, '').replace(/\//g, '-') || 'accueil';

/**
 * Lit une maquette en texte :
 *   Page : <titre> · URL : /<adresse>/ · public : <qui> · intention : « <question> »
 *   Action principale : <…>
 *   1. <Zone> : <rôle ; contenu ; brique>
 *   Masqué faute de donnée : <…>
 *   Liens entrants : <…> · liens sortants : <…>
 */
export function lireMaquette(texte) {
  const m = { entete: {}, action: '', zones: [], masque: '', liens: '' };
  let derniere = null;
  for (const brute of texte.replace(/\r\n?/g, '\n').split('\n')) {
    const l = brute.trim();
    if (!l || /^```/.test(l)) continue;
    let r;
    if (/^page\s*:/i.test(l)) {
      for (const morceau of l.split('·')) {
        const [cle, ...v] = morceau.split(':');
        m.entete[sansAccent(cle.trim())] = v.join(':').trim();
      }
    } else if ((r = l.match(/^action principale\s*:\s*(.*)$/i))) m.action = r[1];
    else if ((r = l.match(/^masqu[ée]e? faute de donn[ée]es?\s*:\s*(.*)$/i))) m.masque = r[1];
    else if (/^liens entrants\s*:/i.test(l)) m.liens = l;
    else if ((r = l.match(/^(\d+)\.\s+([^:]+?)\s*:\s*(.*)$/))) { derniere = { num: Number(r[1]), nom: r[2].trim(), description: r[3].trim() }; m.zones.push(derniere); }
    else if (derniere && /^\s{2,}\S/.test(brute)) derniere.description += ' ' + l;
  }
  return m;
}

const GENRES = [
  ['entete', /en-?tete|header|bandeau de navigation/],
  ['ariane', /fil d'ariane/],
  ['heros', /hero|banniere|accroche/],
  ['pied', /pied/],
  ['appel', /appel|cta|rappel/],
  ['formulaire', /formulaire|devis|contact|inscription/],
  ['galerie', /realisation|galerie|portfolio|projet|photo|reference/],
  ['equipe', /equipe|membre|portrait/],
  ['citations', /temoignage|avis|citation/],
  ['tarifs', /tarif|prix|formule|offre/],
  ['faq', /faq|question/],
  ['chiffres', /chiffre/],
  ['etapes', /etape|methode|processus|deroulement/],
  ['carte', /carte|plan d'acces|acces|itineraire/],
  ['grille', /service|atout|prestation|engagement|avantage|categorie|rubrique/],
];
const NOMBRES = { deux: 2, trois: 3, quatre: 4, cinq: 5, six: 6, sept: 7, huit: 8 };

function analyserZone(z) {
  const nom = sansAccent(z.nom);
  const genre = (GENRES.find(([, re]) => re.test(nom)) ?? ['texte'])[0];
  const boutons = [...z.description.matchAll(/«\s*([^»]+?)\s*»/g)].map((x) => x[1]);
  const liste = z.description.match(/\(([^()]*,[^()]*)\)/)?.[1].split(',').map((x) => x.trim()).filter(Boolean) ?? [];
  const n = z.description.match(/\b(\d+|deux|trois|quatre|cinq|six|sept|huit)\b\s+(?:services|cartes|realisations|réalisations|photos|projets|membres|offres|formules|questions|chiffres|étapes|etapes|atouts|témoignages|temoignages|avis|articles)/i)?.[1];
  const nombre = n ? (NOMBRES[sansAccent(n)] ?? Number(n)) : liste.length || null;
  return { ...z, genre, boutons, liste, nombre };
}

const lignes = (n, classe = '') => Array.from({ length: n }, (_, i) => `<span class="zf-ligne${classe}${i === n - 1 ? ' zf-ligne-courte' : ''}"></span>`).join('');
const image = (libelle = 'image') => `<span class="zf-image" role="img" aria-label="Emplacement : ${echapper(libelle)}"><span>${echapper(libelle)}</span></span>`;
const bouton = (t) => `<span class="zf-bouton">${echapper(t)}</span>`;

function rendreZone(z, contexte) {
  // Libellé d'un élément sans contenu fourni : premier mot du nom de la zone au singulier (« Réalisation 1 »).
  const unite = z.nom.split(/\s+/)[0].replace(/s$/i, '');
  const items = (defaut) => (z.liste.length ? z.liste : Array.from({ length: z.nombre ?? defaut }, (_, i) => `${unite} ${i + 1}`));
  switch (z.genre) {
    case 'entete': {
      const menu = z.liste.length ? z.liste : contexte.menu;
      return `<div class="zf-entete"><span class="zf-logo">logo</span><span class="zf-menu">${menu.map((x) => `<span>${echapper(x)}</span>`).join('')}</span>${z.boutons.map(bouton).join('')}</div>`;
    }
    case 'ariane':
      return `<div class="zf-ariane">Accueil / ${echapper(contexte.titre)}</div>`;
    case 'heros':
      return `<div class="zf-heros"><div><span class="zf-titre"></span>${lignes(3)}<div class="zf-actions">${(z.boutons.length ? z.boutons : ['Action principale']).map(bouton).join('')}</div></div>${image('visuel principal')}</div>`;
    case 'galerie':
    case 'equipe':
      return `<div class="zf-grille">${items(3).map((x) => `<div class="zf-carte${z.genre === 'equipe' ? ' zf-carte-portrait' : ''}">${image(z.genre === 'equipe' ? 'portrait' : 'photo')}<span class="zf-intitule">${echapper(x)}</span>${lignes(2)}</div>`).join('')}</div>`;
    case 'grille':
    case 'tarifs':
      return `<div class="zf-grille">${items(3).map((x) => `<div class="zf-carte"><span class="zf-pictogramme"></span><span class="zf-intitule">${echapper(x)}</span>${lignes(z.genre === 'tarifs' ? 4 : 3)}${z.genre === 'tarifs' ? bouton('Choisir') : ''}</div>`).join('')}</div>`;
    case 'citations':
      return `<div class="zf-grille">${items(2).map(() => `<div class="zf-carte zf-citation">${lignes(3)}<span class="zf-intitule">nom, ville</span></div>`).join('')}</div>`;
    case 'chiffres':
      return `<div class="zf-grille zf-grille-serree">${items(3).map((x) => `<div class="zf-chiffre"><span class="zf-nombre">00</span><span>${echapper(x)}</span></div>`).join('')}</div>`;
    case 'etapes':
      return `<ol class="zf-etapes">${items(3).map((x, i) => `<li><span class="zf-numero">${i + 1}</span><span class="zf-intitule">${echapper(x)}</span>${lignes(2)}</li>`).join('')}</ol>`;
    case 'faq':
      return `<div class="zf-faq">${items(4).map((x) => `<div class="zf-question"><span>${echapper(x)}</span><span aria-hidden="true">+</span></div>`).join('')}</div>`;
    case 'formulaire': {
      const champs = z.liste.length ? z.liste : ['Nom', 'E-mail', 'Message'];
      return `<div class="zf-formulaire">${champs.map((c) => `<span class="zf-champ"><span>${echapper(c)}</span><span class="zf-saisie"></span></span>`).join('')}${(z.boutons.length ? z.boutons : ['Envoyer']).map(bouton).join('')}</div>`;
    }
    case 'carte':
      return `<div class="zf-colonnes">${image('plan d\'accès')}<div>${lignes(4)}${bouton('Itinéraire')}</div></div>`;
    case 'appel':
      return `<div class="zf-appel"><span class="zf-titre"></span>${lignes(1)}<div class="zf-actions">${(z.boutons.length ? z.boutons : ['Action principale']).map(bouton).join('')}</div></div>`;
    case 'pied':
      return `<div class="zf-pied">${(z.liste.length ? z.liste : ['Coordonnées', 'Pages', 'Légal']).map((x) => `<div><span class="zf-intitule">${echapper(x)}</span>${lignes(3)}</div>`).join('')}</div>`;
    default:
      return `<div class="zf-texte"><span class="zf-titre"></span>${lignes(5)}</div>`;
  }
}

/** Page HTML du wireframe d'une maquette (feuilles : tokens de la charte et wireframe.css de _commun). */
export function pageWireframe(maquette, contexte) {
  const zones = maquette.zones.map(analyserZone);
  return { zones, html: `<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Wireframe : ${echapper(contexte.titre)}</title>
<link rel="stylesheet" href="../../_commun/tokens.css"><link rel="stylesheet" href="../../_commun/wireframe.css"></head>
<body>
${zones.map((z) => `<section class="zf zone-${z.genre}">
  <p class="zf-etiquette"><span class="zf-num">${z.num}</span> ${echapper(z.nom)}</p>
  ${rendreZone(z, contexte)}
</section>`).join('\n')}
</body>
</html>
` };
}

/**
 * Construit le livrable 03 : une page HTML de wireframe par page de l'arborescence, ses captures (bureau 1280,
 * mobile 390) et le corps du document. Renvoie { corps, avertissements, sourcesRendues }.
 */
export async function wireframes({ arborescence, dossier, navigateur }) {
  const avert = [];
  const menu = arborescence.map((p) => p.page);
  mkdirSync(path.join(dossier, 'pages'), { recursive: true });
  mkdirSync(path.join(dossier, 'captures'), { recursive: true });
  let corps = `<h2>Lire ces wireframes</h2>
<p>Un wireframe montre la structure d'une page : l'ordre des zones, leur forme et ce qu'elles contiennent, sans couleurs, sans photos et sans les textes définitifs. On valide ici ce qui est présent sur chaque page et dans quel ordre. Les couleurs, la typographie et les visuels viennent ensuite, avec la direction artistique.</p>
<p>Chaque zone porte un numéro, repris dans le tableau sous la page. Chaque page est montrée sur ordinateur et sur téléphone.</p>`;
  const page = await navigateur.newPage();
  let sourcesRendues = '';
  for (const p of arborescence) {
    const slug = slugAdresse(p.adresse);
    const fichier = path.join(process.cwd(), 'docs/maquettes', `${slug}.md`);
    corps += `<h2>${echapper(p.page)} <code>${echapper(p.adresse)}</code></h2>`;
    if (!existsSync(fichier)) {
      avert.push(`Maquette en texte absente pour « ${p.page} » : docs/maquettes/${slug}.md (skill web-contenu).`);
      corps += '<p>Maquette en texte de cette page à écrire.</p>';
      continue;
    }
    const maquette = lireMaquette(readFileSync(fichier, 'utf8'));
    if (!maquette.zones.length) { avert.push(`docs/maquettes/${slug}.md ne contient aucune zone numérotée (« 1. Zone : … »).`); continue; }
    const { zones, html } = pageWireframe(maquette, { titre: p.page, menu });
    writeFileSync(path.join(dossier, 'pages', `${slug}.html`), html);
    sourcesRendues += html;
    for (const [largeur, nom] of [[1280, 'bureau'], [390, 'mobile']]) {
      await page.setViewportSize({ width: largeur, height: 900 });
      await page.goto('file://' + path.join(dossier, 'pages', `${slug}.html`), { waitUntil: 'load' });
      await page.screenshot({ path: path.join(dossier, 'captures', `${slug}-${nom}.png`), fullPage: true });
    }
    const e = maquette.entete;
    corps += `<table><tbody>
<tr><th scope="row">Public</th><td>${echapper(e.public ?? p.public ?? 'à préciser')}</td></tr>
<tr><th scope="row">Recherche du public</th><td>${echapper(e.intention ?? p.intention ?? 'à préciser')}</td></tr>
<tr><th scope="row">Action principale</th><td>${echapper(maquette.action || 'à préciser')}</td></tr>
</tbody></table>
<div class="ecrans">
<figure class="ecran-bureau"><img src="captures/${slug}-bureau.png" alt="Wireframe de la page ${echapper(p.page)} sur ordinateur"><figcaption>Ordinateur</figcaption></figure>
<figure class="ecran-mobile"><img src="captures/${slug}-mobile.png" alt="Wireframe de la page ${echapper(p.page)} sur téléphone"><figcaption>Téléphone</figcaption></figure>
</div>
<table><thead><tr><th scope="col">N°</th><th scope="col">Zone</th><th scope="col">Rôle et contenu</th></tr></thead><tbody>
${zones.map((z) => `<tr><td>${z.num}</td><td>${echapper(z.nom)}</td><td>${echapper(pourLeClient(z.description))}</td></tr>`).join('\n')}
</tbody></table>
${maquette.masque ? `<p><strong>Masqué tant que la donnée manque :</strong> ${echapper(maquette.masque)}</p>` : ''}`;
    if (!maquette.action) avert.push(`Action principale non indiquée dans docs/maquettes/${slug}.md.`);
  }
  await page.close();
  // Les feuilles du rendu comptent aussi : un changement de dessin donne une nouvelle version.
  sourcesRendues += readFileSync(path.join(path.dirname(dossier), '_commun/wireframe.css'), 'utf8');
  return { corps, avertissements: avert, sourcesRendues };
}
