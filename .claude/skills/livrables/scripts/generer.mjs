// SPDX-License-Identifier: GPL-3.0-or-later
// Génère les livrables du projet (HTML et PDF, charte Kouzia) depuis leurs sources, jamais écrits à la main :
//   01-audit-existant      ← docs/source/existant/inventaire.json (make audit URL=…)
//   02-cahier-des-charges  ← docs/livrables/sources/02-cahier-des-charges.md (texte rédigé pour le client),
//                             arborescence et type du mode d'emploi, audit
//   03-wireframes          ← section « Arborescence » du mode d'emploi et docs/maquettes/<page>.md
//   04-direction-artistique ← branding de l'audit, champ « metier » du mode d'emploi, donnees/directions.json,
//                             puis chaque piste sur les vraies pages du site intégré (maquettes)
// Usage : node generer.mjs [id…]            génère les livrables dont la source existe
//         node generer.mjs --valider <id> "<qui>" [piste]   enregistre la validation (sur instruction de l'humain) ;
//                                                   04 : numéro de la piste choisie obligatoire
//         node generer.mjs --appliquer               pose la piste validée dans le site (base/charte.css, polices)
import { copyFileSync, existsSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { DOSSIER_LIVRABLES, echapper, ecrireLivrable, lancerNavigateur, lireRegistre, markdown, poserCommun, RACINE, valider } from '../lib/document.mjs';
import { adresseSeule, wireframes } from '../lib/wireframes.mjs';
import { directionArtistique } from '../lib/direction.mjs';
import { pistesSurPages } from '../lib/maquettes.mjs';
import { DOSSIER_POLICES } from '../lib/polices.mjs';

const DOSSIER_PISTES = path.join(DOSSIER_LIVRABLES, '04-direction-artistique/pistes');
const dossierPiste = (n) => readdirSync(DOSSIER_PISTES).find((d) => d.startsWith(`${n}-`));

const args = process.argv.slice(2);
if (args[0] === '--valider') {
  if (!args[1] || !args[2]) { console.error('Usage : node generer.mjs --valider <id> "<qui, comment>" [piste]'); process.exit(2); }
  const extra = {};
  if (args[1] === '04-direction-artistique') {
    if (!args[3] || !existsSync(DOSSIER_PISTES) || !dossierPiste(args[3])) { console.error('04 : préciser la piste choisie par le client (PISTE=1, 2 ou 3).'); process.exit(2); }
    extra.piste = Number(args[3]);
  }
  const e = valider(args[1], args[2], extra);
  if (extra.piste) console.log(`Piste ${extra.piste} retenue : make appliquer la pose dans le site.`);
  console.log(`${args[1]} : version ${e.version} validée le ${e.validation.date} par ${e.validation.par}. Le noter dans « Décisions validées » du mode d'emploi.`);
  process.exit(0);
}

const FICHIER_MODE = path.join(RACINE, '.claude/rules/projet.md');
const FICHIER_INVENTAIRE = path.join(RACINE, 'docs/source/existant/inventaire.json');

/** Mode d'emploi découpé : champs de tête et sections « ## ». */
function lireModeEmploi() {
  if (!existsSync(FICHIER_MODE)) return null;
  const texte = readFileSync(FICHIER_MODE, 'utf8');
  const nom = texte.match(/^# Mode d'emploi\s*:\s*(.+)$/m)?.[1]?.trim() ?? 'Projet';
  const champ = (c) => texte.match(new RegExp(`^- ${c}:\\s*(.+)$`, 'm'))?.[1]?.trim() ?? '';
  const sections = {};
  for (const bloc of texte.split(/^## /m).slice(1)) {
    const [titre, ...corps] = bloc.split('\n');
    sections[titre.trim()] = corps.join('\n').trim();
  }
  return { nom, client: champ('client'), depot: champ('depot'), type: champ('type'), metier: champ('metier'), statut: champ('statut'), maj: champ('mise à jour'), sections, texte };
}

/** Lignes du tableau « Arborescence » du mode d'emploi : { page, adresse, intention, remplace }. */
function lireArborescence(mode) {
  const t = mode?.sections?.Arborescence ?? '';
  return t.split('\n').filter((l) => /^\|/.test(l) && !/^\|[\s:|-]+\|$/.test(l)).slice(1)
    .map((l) => l.replace(/^\||\|$/g, '').split('|').map((c) => c.trim().replace(/`/g, '')))
    .filter((c) => c[0] && c[0] !== '…' && /^\//.test(c[1] ?? ''))
    // Adresse seule : une note qui la suit (« /admin/ (non référencée) ») n'entre ni dans un nom de fichier ni dans une URL testée.
    .map(([page, adresse, intention, remplace]) => ({ page, adresse: adresseSeule(adresse), intention, remplace }));
}

function lireInventaire() {
  return existsSync(FICHIER_INVENTAIRE) ? JSON.parse(readFileSync(FICHIER_INVENTAIRE, 'utf8')) : null;
}

// Contraste WCAG entre deux couleurs hexadécimales.
function luminance(h) {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16) / 255).map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}
const contraste = (a, b) => { const [x, y] = [luminance(a), luminance(b)].sort((m, n) => n - m); return (x + 0.05) / (y + 0.05); };
const ratio = (v) => v.toFixed(2).replace('.', ',');

const lienFichier = (f) => (f ? `../../source/existant/${f}` : null);
const nomClient = (mode, inv) => mode?.client || mode?.nom || (inv ? new URL(inv.source).hostname.replace(/^www\./, '') : 'Projet');

function auditExistant(inv, mode) {
  const id = inv.identite;
  const avert = [];
  const pagesSansDescription = inv.pages.filter((p) => !p.description);
  const pagesSansH1 = inv.pages.filter((p) => p.h1.length !== 1);
  const imagesSansAlt = inv.images.filter((i) => i.alt === null);
  const md = [];
  md.push(`## Synthèse

| Élément | Relevé |
|---------|--------|
| Site audité | ${inv.source} |
| Date de l'audit | ${inv.date.slice(0, 10)} |
| Pages lues | ${inv.pagesLues}${inv.pagesLues >= inv.limite ? ` (limite de ${inv.limite} atteinte)` : ''} |
| Anciennes adresses (redirections à prévoir) | ${inv.anciennesAdresses.length} |
| Variantes à paramètres (langue, pagination) | ${(inv.variantes ?? []).length}${(inv.variantes ?? []).some((v) => v.langue) ? ` ; langues : ${[...new Set(inv.variantes.filter((v) => v.langue).map((v) => v.langue))].join(', ')}` : ''} |
| Images de contenu récupérées | ${inv.images.length} |
| Fichiers fournis par le client | ${inv.fournis.length} |

Chaque fait ci-dessous a été lu sur le site actuel, avec la page source. Il reste **à confirmer** par le client avant d'entrer dans le nouveau site.`);
  let html = markdown(md.join('\n'));

  html += '<h2>Identité visuelle relevée</h2><h3>Logo</h3>';
  const logos = id.logos.filter((l) => l.fichier).slice(0, 6);
  html += logos.length
    ? `<ul class="vignettes logos">${logos.map((l) => `<li><figure><img src="${echapper(lienFichier(l.fichier))}" alt="Logo candidat : ${echapper(l.type)}"><figcaption>${echapper(l.type)}${l.tailles ? ` (${echapper(l.tailles)})` : ''} · <code>${echapper(l.fichier)}</code></figcaption></figure></li>`).join('')}</ul>`
    : '<p>Aucun logo exploitable trouvé : à demander au client (SVG de préférence).</p>';
  if (!logos.some((l) => /svg/.test(l.fichier))) avert.push('Logo en SVG absent du site actuel : demander le fichier source au client.');

  html += '<h3>Couleurs</h3><p>Couleurs les plus présentes, avec leur contraste sur blanc et sur noir (texte courant : 4,5:1 au moins ; grand texte et contrôles : 3:1).</p><ul class="nuancier">';
  const pastille = (h, role) => `<li><span class="pastille" style="background:${echapper(h)}"></span><span class="valeurs"><code>${echapper(h)}</code>${role ? ` · ${echapper(role)}` : ''}<br>sur blanc ${ratio(contraste(h, '#ffffff'))}:1 · sur noir ${ratio(contraste(h, '#000000'))}:1</span></li>`;
  html += id.couleurs.filter((c) => !c.note).slice(0, 10).map((c) => pastille(c.hex, c.roleProbable)).join('');
  html += '</ul>';
  if ((id.couleursLogo ?? []).length) html += `<h4>Couleurs du logo</h4><ul class="nuancier">${id.couleursLogo.map((h) => pastille(h, 'logo')).join('')}</ul>`;
  const defauts = id.couleurs.filter((c) => c.note);
  if (defauts.length) html += `<p>Couleurs par défaut du navigateur relevées (liens non stylés, à ne pas reprendre) : ${defauts.map((c) => `<code>${echapper(c.hex)}</code>`).join(', ')}.</p>`;

  const p = id.polices;
  html += markdown(`### Polices

| Usage | Valeur relevée |
|-------|----------------|
| Corps de texte | ${p.usages?.corps ?? 'non relevé'} |
| Titres | ${p.usages?.titres ?? 'non relevé'} |
| Boutons et liens | ${p.usages?.actions ?? 'non relevé'} |
| Polices chargées | ${p.chargees.join(', ') || 'aucune (polices du système)'} |

Licence de chaque police à vérifier avant reprise : une police libre (Google Fonts, OFL) est servie depuis le site en woff2 ; une police commerciale demande la licence web du client.`);

  const faits = (liste, vide) => (liste.length ? liste.map((f) => `| ${f.valeur} | ${f.sources.slice(0, 3).map((s) => new URL(s).pathname).join(', ')} |`).join('\n') : `| ${vide} | |`);
  html += markdown(`## Coordonnées et identité légale relevées

| Téléphone | Pages sources |
|-----------|---------------|
${faits(id.telephones, 'aucun relevé')}

| E-mail | Pages sources |
|--------|---------------|
${faits(id.emails, 'aucun relevé')}

| Code postal et ville | Pages sources |
|----------------------|---------------|
${faits(id.codesPostaux, 'aucune adresse relevée')}

| SIREN ou SIRET | Pages sources |
|----------------|---------------|
${faits(id.siren, 'aucun relevé : à demander (mentions légales obligatoires)')}

| Réseaux sociaux | Pages sources |
|-----------------|---------------|
${faits(id.reseaux, 'aucun relevé')}`);
  if (!id.siren.length) avert.push('SIREN ou SIRET introuvable sur le site actuel : à fournir par le client (mentions légales).');
  if (id.telephones.length > 1) avert.push(`${id.telephones.length} numéros de téléphone différents relevés : le client choisit celui du nouveau site.`);

  html += markdown(`## Arborescence actuelle

Menu principal : ${inv.menu.length ? inv.menu.map((m) => `${m.texte} (\`${m.chemin}\`)`).join(', ') : 'aucun menu détecté'}.

| Adresse | Titre | Titre principal (h1) | Mots |
|---------|-------|----------------------|------|
${inv.pages.map((pg) => `| \`${new URL(pg.url).pathname}\` | ${(pg.titre || 'sans titre').replace(/\|/g, '\\|')} | ${(pg.h1[0] ?? 'aucun').replace(/\|/g, '\\|')} | ${pg.mots} |`).join('\n')}

Toutes les anciennes adresses recevront une redirection permanente (301) vers leur nouvelle page : le référencement acquis est conservé.`);

  html += markdown(`## Constats

| Constat | Pages concernées |
|---------|------------------|
| Pages sans description pour les moteurs | ${pagesSansDescription.length} sur ${inv.pages.length} |
| Pages sans titre principal unique (h1) | ${pagesSansH1.length} sur ${inv.pages.length} |
| Images sans texte alternatif | ${imagesSansAlt.length} sur ${inv.images.length} |
| Pages en échec de lecture | ${inv.echecs.length} |
| Adresses en erreur (404 ou autre) | ${(inv.pagesEnErreur ?? []).length}${(inv.pagesEnErreur ?? []).length ? ' : ' + inv.pagesEnErreur.map((e) => `\`${new URL(e.url).pathname}\` (${e.statut})`).join(', ') : ''} |
| Site servi en HTTPS | ${inv.source.startsWith('https') ? 'oui' : 'non'} |
| Données structurées (schema.org) sur l'accueil | ${id.donneesStructurees.length ? 'oui' : 'non'} |`);

  const captures = inv.pages.slice(0, 8).filter((pg) => pg.capture);
  if (captures.length) {
    html += `<h2>Captures du site actuel</h2><ul class="vignettes">${captures.map((pg) => `<li><figure><img src="${echapper(lienFichier(pg.capture))}" alt="Capture de ${echapper(new URL(pg.url).pathname)} sur ordinateur"><figcaption>${echapper(new URL(pg.url).pathname)}</figcaption></figure></li>`).join('')}</ul>`;
  }
  if (inv.fournis.length) {
    html += markdown(`## Fichiers fournis par le client

| Fichier | Type | Poids | Dimensions |
|---------|------|-------|------------|
${inv.fournis.map((f) => `| \`${f.fichier}\` | ${f.type} | ${Math.round(f.octets / 1024)} Ko | ${f.largeur ? `${f.largeur} × ${f.hauteur}` : ''} |`).join('\n')}`);
  }
  return { id: '01-audit-existant', titre: "Audit de l'existant", client: nomClient(mode, inv), sousTitre: `Inventaire du site actuel ${inv.source} : contenus, identité visuelle, coordonnées, adresses.`, corps: html, brouillon: false, constat: true, avertissements: avert };
}

// Exigences par type de site (rules types-de-site, web-*) : texte client, sans terme interne.
const EXIGENCES_COMMUNES = `- **Accessibilité** : niveau AA des règles internationales WCAG 2.2 (contrastes, navigation au clavier, textes alternatifs, lecture à 320 px et zoom 200 %), vérifié par un audit automatique et manuel ; page « Accessibilité » publiée.
- **Référencement** : un titre et une description uniques par page, plan du site et fichier pour les moteurs, données structurées de l'activité, adresses propres et redirections des anciennes adresses.
- **Performance et sobriété** : moins de 500 Ko et 30 requêtes par page en première visite, images au format web à la taille affichée, aucun script tiers sans accord écrit.
- **Sécurité** : HTTPS partout, en-têtes de sécurité, aucun fichier technique accessible en ligne.
- **Conformité** : mentions légales, politique de confidentialité si des données personnelles sont traitées, aucune information inventée (avis, chiffres, logos, clients).
- **Mobile d'abord** : conçu pour le téléphone, adapté jusqu'aux grands écrans.`;
const EXIGENCES_TYPE = {
  vitrine: '- **Type vitrine** : site de présentation sans compte ni base de données, publié en pages statiques (rapide, sûr, peu coûteux à héberger).',
  'vitrine-formulaire': '- **Type vitrine avec formulaire** : vitrine plus un formulaire protégé contre les robots, collecte minimale, politique de confidentialité liée.',
  associatif: "- **Type associatif** : contenus mis à jour par l'association (back-office protégé par double authentification), sauvegardes chiffrées, journal des actions.",
  boutique: '- **Type boutique** : panier, paiement par un prestataire (aucune carte stockée), CGV, prix TTC, droit de rétractation, médiateur de la consommation.',
  'espace-client': '- **Type espace client** : comptes, accès contrôlé à chaque ressource, export et suppression des données personnelles.',
  'outil-interne': "- **Type outil interne** : accès protégé (mot de passe et double authentification), jamais indexé par les moteurs.",
};
const VALIDATIONS = `| # | Validation | Ce que le client valide |
|---|------------|-------------------------|
| 1 | Cahier des charges | ce document : objectifs, publics, périmètre, arborescence, contraintes |
| 2 | Wireframes | la structure de chaque page (zones, ordre, contenus), sans couleurs |
| 3 | Direction artistique et maquettes | couleurs, typographie, usage du logo, puis les pages avec leurs vrais contenus |
| 4 | Recette | le procès-verbal des contrôles avant la mise en ligne |`;

// Source rédigée du cahier des charges : le texte pour le client, écrit d'après le mode d'emploi (seule source des faits).
const FICHIER_SOURCE_CDC = path.join(DOSSIER_LIVRABLES, 'sources/02-cahier-des-charges.md');
const SECTIONS_CDC = ['Synthèse', 'Contexte et objectifs', 'Publics', 'Fonctionnement du site', 'Hébergement', 'Calendrier', 'Éléments à fournir'];
const EN_PHRASES = new Set(['Synthèse', 'Contexte et objectifs', 'Publics', 'Fonctionnement du site', 'Hébergement']);
// Un texte destiné au client ne contient aucun terme du kit : un défaut à corriger dans la source.
const TERMES_INTERNES = [
  [/\.\.\/|\b(?:src|docs|config|public|app|templates|data)\/[\w./-]+/, 'chemin de fichier'],
  [/[\w-]+\.(?:md|mjs|js|json|php|sqlite|tpl|css|env)\b/, 'nom de fichier'],
  [/`make\b|\bmake (?:audit|verify|socle|statique|dynamique|livrables|valider|appliquer|apercu)\b/, 'commande make'],
  [/\b(?:constaté|déduit)\b/, 'balise de source (constaté, déduit)'],
  [/\bl'agent\b|\bl'humain\b|\b(?:rule|skill|mode d'emploi)s?\b/i, 'vocabulaire du kit'],
];

function blocAccord(client) {
  const partie = (qui) => `<div class="partie"><h4>${echapper(qui)}</h4><dl>
<div><dt>Nom et qualité</dt><dd></dd></div><div><dt>Date</dt><dd></dd></div><div><dt>Signature</dt><dd class="signature"></dd></div>
</dl></div>`;
  return `<section class="accord"><h3>Bon pour accord</h3>
<p>La signature de ce document vaut validation de la présente version du cahier des charges (validation 1). Toute modification ultérieure fera l'objet d'une nouvelle version, à valider de la même façon.</p>
<div class="parties">${partie(`Pour ${client}`)}${partie('Pour Kouzia')}</div></section>`;
}

function cahierDesCharges(mode, inv) {
  const avert = [];
  if (!/^validé/.test(mode.statut)) avert.push(`Mode d'emploi en statut « ${mode.statut || 'inconnu'} » : terminer l'interview avant d'envoyer ce cahier des charges.`);
  const aConfirmer = mode.texte.split('\n').filter((l) => /à confirmer/i.test(l));
  if (aConfirmer.length) avert.push(`${aConfirmer.length} point(s) encore « à confirmer » dans le mode d'emploi.`);
  if (!mode.client) avert.push("Champ « - client: » absent du mode d'emploi : le nom du client manque sur la page de titre.");
  if (!EXIGENCES_TYPE[mode.type]) avert.push(`Type de site absent ou invalide (« ${mode.type || 'rien'} ») dans le mode d'emploi.`);

  if (!existsSync(FICHIER_SOURCE_CDC)) {
    mkdirSync(path.dirname(FICHIER_SOURCE_CDC), { recursive: true });
    copyFileSync(path.join(import.meta.dirname, '../gabarit/cahier-des-charges.md'), FICHIER_SOURCE_CDC);
    avert.push(`Source rédigée posée (${path.relative(RACINE, FICHIER_SOURCE_CDC)}) : la rédiger en phrases d'après le mode d'emploi.`);
  }
  const source = readFileSync(FICHIER_SOURCE_CDC, 'utf8').replace(/<!--[\s\S]*?-->/g, '');
  const sections = {};
  for (const bloc of source.split(/^## /m).slice(1)) {
    const [titre, ...corps] = bloc.split('\n');
    sections[titre.trim()] = corps.join('\n').trim();
  }
  for (const t of SECTIONS_CDC) {
    const texte = sections[t] ?? '';
    if (!texte || /^…$/m.test(texte)) { avert.push(`Section « ${t} » de la source rédigée vide ou incomplète.`); continue; }
    const puces = texte.split('\n').filter((l) => /^\s*([-*]|\d+\.)\s/.test(l)).length;
    if (EN_PHRASES.has(t) && puces > 4) avert.push(`Section « ${t} » : ${puces} puces, à rédiger en phrases.`);
  }
  for (const t of Object.keys(sections)) if (!SECTIONS_CDC.includes(t)) avert.push(`Section « ${t} » inconnue dans la source rédigée (titres permis : ${SECTIONS_CDC.join(', ')}).`);
  const mots = (sections['Synthèse'] ?? '').split(/\s+/).filter(Boolean).length;
  if (mots > 220) avert.push(`Synthèse de ${mots} mots pour 220 au plus.`);

  const arbo = lireArborescence(mode);
  if (!arbo.length) avert.push("Arborescence du mode d'emploi vide : le tableau des pages manque.");
  const texteClient = [source, ...arbo.map((p) => `${p.page} ${p.intention ?? ''}`)].join('\n');
  for (const [re, nom] of TERMES_INTERNES) {
    const trouves = [...new Set(texteClient.split('\n').filter((l) => re.test(l)).map((l) => l.match(re)[0]))];
    if (trouves.length) avert.push(`Terme interne dans un texte destiné au client (${nom}) : ${trouves.slice(0, 5).join(', ')}. À réécrire dans la source.`);
  }

  const client = nomClient(mode, inv);
  const section = (t) => (sections[t] ? `## ${t}\n\n${sections[t]}\n` : '');
  const pages = arbo.length ? `## Pages du site\n\n| Page | Adresse | Ce que cherche le visiteur |\n|------|---------|-------------------|\n${arbo.map((p) => `| ${p.page} | ${p.adresse} | ${p.intention ?? ''} |`).join('\n')}\n` : '';
  // Un outil interne n'est jamais indexé : pas d'exigence de référencement.
  const exigences = `## Exigences\n\n${mode.type === 'outil-interne' ? EXIGENCES_COMMUNES.split('\n').filter((l) => !/Référencement/.test(l)).join('\n') : EXIGENCES_COMMUNES}\n${EXIGENCES_TYPE[mode.type] ?? ''}\n`;
  const reprise = inv ? `## Reprise du site actuel\n\nLe site actuel (${inv.source}) a été relu page par page : ${inv.pagesLues} pages, dont les contenus utiles sont repris. Ses ${inv.anciennesAdresses.length} anciennes adresses renverront automatiquement vers les nouvelles pages, pour ne perdre ni visiteurs ni référencement. Le logo, les couleurs et les polices relevés servent de point de départ à la direction artistique.\n` : '';
  // À l'impression : la synthèse seule sur sa page, puis le détail, puis validations et signature sur la dernière page.
  const md = [
    section('Contexte et objectifs'), section('Publics'), pages, section('Fonctionnement du site'),
    exigences, reprise, section('Hébergement'), section('Calendrier'), section('Éléments à fournir'),
  ];
  const fin = `<section class="cloture">${markdown(`## Validations\n\nLe projet avance par étapes ; chacune attend l'accord écrit du client avant la suivante.\n\n${VALIDATIONS}\n`)}\n${blocAccord(client)}</section>`;
  return {
    id: '02-cahier-des-charges', titre: 'Cahier des charges', client,
    sousTitre: 'Ce que sera le nouveau site, pour qui, et comment sa réussite se vérifie.',
    corps: `<section class="synthese">${markdown(section('Synthèse'))}</section>\n${markdown(md.join('\n'))}\n${fin}`, pagesMax: 6,
    brouillon: !/^validé/.test(mode.statut) || avert.length > 0, avertissements: avert,
  };
}

const demandes = args.filter((a) => !a.startsWith('--'));
const veut = (id) => !demandes.length || demandes.includes(id);
const mode = lireModeEmploi();
const inv = lireInventaire();
const faits = [];
if (veut('01-audit-existant')) {
  if (inv) faits.push(await ecrireLivrable(auditExistant(inv, mode)));
  else if (demandes.length) console.log('01-audit-existant : pas d\'inventaire (make audit URL=https://site-actuel).');
}
if (veut('02-cahier-des-charges')) {
  if (mode) faits.push(await ecrireLivrable(cahierDesCharges(mode, inv)));
  else console.log("02-cahier-des-charges : pas de mode d'emploi (.claude/rules/projet.md) : lancer l'interview.");
}
if (veut('03-wireframes') && mode) {
  const arbo = lireArborescence(mode);
  if (!arbo.length) console.log("03-wireframes : section « Arborescence » du mode d'emploi vide.");
  else {
    poserCommun();
    const navigateur = await lancerNavigateur();
    const { corps, avertissements, sourcesRendues } = await wireframes({ arborescence: arbo, dossier: path.join(DOSSIER_LIVRABLES, '03-wireframes'), navigateur });
    await navigateur.close();
    // Ordre de la chaîne : la structure se valide après le cahier des charges (validation 1).
    if (lireRegistre().livrables['02-cahier-des-charges']?.statut !== 'validé') avertissements.unshift('Cahier des charges pas encore validé par le client : les wireframes attendent la validation 1.');
    faits.push(await ecrireLivrable({
      id: '03-wireframes', titre: 'Wireframes', client: nomClient(mode, inv),
      sousTitre: `Structure de chaque page du nouveau site (${arbo.length} page${arbo.length > 1 ? 's' : ''}), sur ordinateur et sur téléphone, avant tout travail graphique.`,
      corps, sourcesRendues, brouillon: avertissements.length > 0, avertissements,
    }));
  }
}
if (args[0] === '--appliquer') {
  // La piste validée (validation 3) devient la charte du site : fichier généré, jamais modifié à la main.
  const e = lireRegistre().livrables['04-direction-artistique'];
  if (e?.statut !== 'validé' || !e.validation?.piste) { console.error('Direction artistique pas validée avec une piste (make valider ID=04-direction-artistique PAR="…" PISTE=n).'); process.exit(1); }
  const rep = path.join(DOSSIER_PISTES, dossierPiste(e.validation.piste));
  const statique = existsSync(path.join(RACINE, 'src/styles/ordre.json'));
  const dynamique = existsSync(path.join(RACINE, 'app/bootstrap.php'));
  if (!statique && !dynamique) { console.error('Aucun socle posé : make socle d\'abord.'); process.exit(1); }
  const basePolices = statique ? '/polices' : '/assets/polices';
  const dossierPublic = path.join(RACINE, statique ? 'public/polices' : 'public/assets/polices');
  const css = readFileSync(path.join(rep, 'tokens.css'), 'utf8').replaceAll('../../../../source/polices', basePolices);
  const entete = `/* Généré par make appliquer depuis la piste ${e.validation.piste} du livrable 04, validée le ${e.validation.date} (${e.validation.par}).\n   Ne pas modifier : changer la piste ou ses sources, régénérer, revalider, puis make appliquer. */\n`;
  for (const m of css.matchAll(new RegExp(`url\\("${basePolices}/([^"]+)"\\)`, 'g'))) {
    mkdirSync(path.dirname(path.join(dossierPublic, m[1])), { recursive: true });
    copyFileSync(path.join(DOSSIER_POLICES, m[1]), path.join(dossierPublic, m[1]));
  }
  if (statique) {
    // charte.css remplace la charte neutre du modèle (même fichier, même place dans ordre.json).
    writeFileSync(path.join(RACINE, 'src/styles/base/charte.css'), entete + css);
    const f = path.join(RACINE, 'src/styles/ordre.json');
    const ordre = JSON.parse(readFileSync(f, 'utf8'));
    if (!ordre.fichiers.includes('base/charte.css')) {
      ordre.fichiers.splice(ordre.fichiers.indexOf('base/tokens.css') + 1, 0, 'base/charte.css');
      writeFileSync(f, JSON.stringify(ordre, null, 2) + '\n');
    }
    console.log(`Piste ${e.validation.piste} posée : src/styles/base/charte.css, polices dans public/polices/.`);
  } else {
    mkdirSync(path.join(RACINE, 'public/assets/css'), { recursive: true });
    writeFileSync(path.join(RACINE, 'public/assets/css/charte.css'), entete + css);
    console.log(`Piste ${e.validation.piste} posée : public/assets/css/charte.css et public/assets/polices/. À lier dans templates/layout.php juste après la feuille des tokens.`);
  }
  process.exit(0);
}

if (veut('04-direction-artistique') && mode && (inv || demandes.length)) {
  poserCommun();
  const navigateur = await lancerNavigateur();
  const maquetteAccueil = existsSync(path.join(RACINE, 'docs/maquettes/accueil.md')) ? readFileSync(path.join(RACINE, 'docs/maquettes/accueil.md'), 'utf8') : '';
  const da = await directionArtistique({
    inventaire: inv, mode, arborescence: lireArborescence(mode), metier: mode.metier, type: mode.type, maquetteAccueil,
    dossier: path.join(DOSSIER_LIVRABLES, '04-direction-artistique'), navigateur,
  });
  // Maquettes : les mêmes pistes sur les vraies pages du site intégré.
  const surPages = await pistesSurPages({ pistes: da.pistes.map((p) => ({ ...p, dossier: path.dirname(path.join(RACINE, p.tokens)) })), arborescence: lireArborescence(mode), navigateur });
  const corps = da.corps.replace('<h2>Ce que le client valide</h2>', surPages.corps + '<h2>Ce que le client valide</h2>');
  const avertissements = [...da.avertissements, ...surPages.avertissements];
  const sourcesRendues = da.sourcesRendues + surPages.sourcesRendues;
  await navigateur.close();
  // Ordre de la chaîne : l'apparence se choisit sur une structure validée (validation 2).
  if (lireRegistre().livrables['03-wireframes']?.statut !== 'validé') avertissements.unshift('Wireframes pas encore validés par le client : la direction artistique attend la validation 2.');
  if (!inv) avertissements.push("Pas d'audit du site actuel : couleurs, logo et polices du client inconnus (make audit URL=…, ou fichiers dans docs/source/fournis/).");
  faits.push(await ecrireLivrable({
    id: '04-direction-artistique', titre: 'Direction artistique et maquettes', client: nomClient(mode, inv),
    sousTitre: "Trois directions possibles pour l'apparence du site, construites depuis la marque actuelle et le métier, toutes conformes en contraste, puis montrées sur les vraies pages du nouveau site.",
    corps, sourcesRendues, brouillon: avertissements.length > 0, avertissements,
  }));
}
for (const f of faits) console.log(`${f.titre} : version ${f.version} (${f.statut}) · ${f.html} · ${f.pdf}`);
if (!faits.length) process.exit(1);
