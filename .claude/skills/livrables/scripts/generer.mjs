// SPDX-License-Identifier: GPL-3.0-or-later
// Génère les livrables du projet (HTML et PDF, charte Kouzia) depuis leurs sources, jamais écrits à la main :
//   01-audit-existant      ← docs/source/existant/inventaire.json (make audit URL=…)
//   02-cahier-des-charges  ← .claude/rules/projet.md (mode d'emploi) et l'audit
// Usage : node generer.mjs [id…]            génère les livrables dont la source existe
//         node generer.mjs --valider <id> "<qui>"   enregistre la validation (sur instruction de l'humain)
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { echapper, ecrireLivrable, markdown, RACINE, valider } from '../lib/document.mjs';

const args = process.argv.slice(2);
if (args[0] === '--valider') {
  if (!args[1] || !args[2]) { console.error('Usage : node generer.mjs --valider <id> "<qui, comment>"'); process.exit(2); }
  const e = valider(args[1], args[2]);
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
  return { nom, depot: champ('depot'), type: champ('type'), statut: champ('statut'), maj: champ('mise à jour'), sections, texte };
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
const nomClient = (mode, inv) => mode?.nom ?? (inv ? new URL(inv.source).hostname.replace(/^www\./, '') : 'Projet');

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

function cahierDesCharges(mode, inv) {
  const s = mode.sections;
  const avert = [];
  // L'emplacement des accès est une information interne : jamais dans un document client.
  // Indications du modèle de mode d'emploi, reformulées pour le client.
  const POUR_LE_CLIENT = [[/\s*\(mots de l'humain\)/g, ''], [/\s*\(critères vérifiables\)/g, ''], [/\(rule [^)]*\)/g, '']];
  const garder = (t) => POUR_LE_CLIENT.reduce((x, [re, par]) => x.replace(re, par), t.split('\n').filter((l) => !/^\s*- Accès rangés/.test(l)).join('\n'));
  const section = (titre, cle, texteSiVide) => {
    const t = (s[cle] ?? '').trim();
    if (t.replace(/^- …$/gm, '').trim() === '') { avert.push(`Section « ${cle} » du mode d'emploi vide.`); return `## ${titre}\n\n${texteSiVide}\n`; }
    return `## ${titre}\n\n${garder(t)}\n`;
  };
  if (!/^validé/.test(mode.statut)) avert.push(`Mode d'emploi en statut « ${mode.statut || 'inconnu'} » : terminer l'interview avant d'envoyer ce cahier des charges.`);
  const aConfirmer = mode.texte.split('\n').filter((l) => /à confirmer/i.test(l));
  if (aConfirmer.length) avert.push(`${aConfirmer.length} point(s) encore « à confirmer » dans le mode d'emploi.`);
  if (/…/.test(mode.texte)) avert.push("Des champs du mode d'emploi contiennent encore « … ».");

  const md = [
    section('Objectif', 'Objectif', 'À définir.'),
    section('Contexte', 'Contexte', 'À définir.'),
    section('Publics et action principale', 'Publics et action principale', 'À définir.'),
    section('Périmètre', 'Périmètre', 'À définir.'),
    section('Arborescence du nouveau site', 'Arborescence', "À définir à partir de l'audit et des recherches du public."),
    inv ? `## Reprise de l'existant\n\n- Site actuel audité : ${inv.source} (livrable 01, ${inv.pagesLues} pages).\n- ${inv.anciennesAdresses.length} anciennes adresses redirigées en 301 vers les nouvelles pages.\n- Logo, couleurs et polices relevés : point de départ de la direction artistique (validation 3).\n` : '',
    section('Ton et vocabulaire', 'Ton et vocabulaire', 'À définir.'),
    section('Contenus et données', 'Données et contenus', 'À définir.'),
    `## Exigences\n\n${EXIGENCES_COMMUNES}\n${EXIGENCES_TYPE[mode.type] ?? ''}\n`,
    section('Hébergement', 'Hébergement et accès', 'À définir.'),
    section('Décisions prises', 'Décisions validées', 'Aucune décision encore notée.'),
    section('Étapes', 'Étapes', 'À définir.'),
    `## Validations\n\n${VALIDATIONS}\n`,
    section('Éléments à fournir', 'À fournir', 'Rien à fournir à ce jour.'),
  ];
  if (!EXIGENCES_TYPE[mode.type]) avert.push(`Type de site absent ou invalide (« ${mode.type || 'rien'} ») dans le mode d'emploi.`);
  return {
    id: '02-cahier-des-charges', titre: 'Cahier des charges', client: mode.nom,
    sousTitre: `Ce que sera le nouveau site, pour qui, et comment sa réussite se vérifie. Généré depuis le mode d'emploi du projet (mise à jour ${mode.maj || 'inconnue'}).`,
    corps: markdown(md.join('\n')), brouillon: !/^validé/.test(mode.statut) || avert.length > 0, avertissements: avert,
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
for (const f of faits) console.log(`${f.titre} : version ${f.version} (${f.statut}) · ${f.html} · ${f.pdf}`);
if (!faits.length) process.exit(1);
