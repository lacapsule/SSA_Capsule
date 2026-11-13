# Mode d'emploi : SSA_Refonte

- depot: SSA_Refonte
- type: associatif
- metier: association
- statut: validé le 2026-11-13
- mise à jour: 2026-11-13

Les règles `web-*` et `00-organisation` s'appliquent. Ce fichier ne contient que ce qui est propre au projet. Les sections de « Objectif » à « À fournir » sont reprises telles quelles dans le cahier des charges : elles s'écrivent pour le client, sans terme interne ; les détails techniques et les sources vont dans « Règles propres au projet ».

## Objectif

- **Demande** : le site actuel repose sur un outil fait sur mesure, devenu difficile à maintenir et en retard sur les bonnes pratiques actuelles (accessibilité, référencement, sécurité). Il est donc reconstruit entièrement, avec les mêmes contenus.
- **Pour qui** : le collectif Sécurité Sociale de l'Alimentation du Pays de Morlaix (« La Collective ») et les associations qui l'accompagnent.
- **Réussi quand** (critères vérifiables) : le site convient à Kouzia et au client, ce qui se vérifie ainsi :
  - tous les contenus actuels sont repris, et les 18 anciennes adresses mènent automatiquement à la bonne nouvelle page ;
  - l'audit d'accessibilité ne relève aucun défaut grave et prouve le niveau AA ;
  - tous les contrôles techniques sont au vert, et chaque page pèse moins de 500 Ko ;
  - le procès-verbal de recette est signé par le client.
- **Échéance** : aucune.

## Contexte

- **Le projet** : une expérimentation de Sécurité Sociale de l'Alimentation sur le Pays de Morlaix, portée par un collectif citoyen (« La Collective », une trentaine de citoyennes et citoyens) et co-animée par quatre associations : ULAMIR-CPIE, Résam, Le Buzuk (monnaie locale) et l'Adess du Pays de Morlaix.
- **Le fonctionnement** : chaque participant·e choisit sa cotisation mensuelle et reçoit une allocation en monnaie locale, le « Buzuk SSA », à dépenser dans des lieux choisis ensemble ; une réunion et un atelier ont lieu chaque mois.
- **La durée** : une expérimentation d'un an, avec des allocations versées pendant 12 mois à partir de début 2026, donc une fin vers début 2027. Le site continuera ensuite (à faire confirmer par le collectif, voir « Éléments à fournir »).
- **Le recrutement** : les candidatures ont été closes le 20 septembre 2025. Le bloc « On recrute ! » de la page Projet est donc retiré.
- **La responsable** : Géraldine Gabillet, chargée de mission à ULAMIR-CPIE, référente de l'expérimentation et directrice de la publication.
- **Les coordonnées affichées** : « Contactez l'association ULAMIR-CPIE », Le Buzuk, 39 bellevue de la Madeleine, 29600 Morlaix ; 06 15 06 82 08 ; ssapaysdemorlaix@mailo.com ; candidatures : candidature@mailo.com.
- **L'éditeur légal** : le collectif n'a pas d'existence juridique propre. L'éditeur du site est donc l'association ULAMIR-CPIE. D'après le répertoire officiel des entreprises (INSEE), son nom légal est « Union locale animation canton Lanmeur » (enseigne ULAMIR-CPIE Pays de Morlaix-Trégor), SIRET 300 796 349 00011, siège salle Steredenn, 4 rue du 19 Mars 1962, 29620 Lanmeur. Géraldine Gabillet doit encore le vérifier (voir « Éléments à fournir »).
- **Les financeurs affichés** : Région Bretagne, Europe (FEADER, programme Leader).
- **Les médias** : les vidéos sont des fichiers du site, lus directement sur la page, sans lecteur YouTube. Une chaîne YouTube est citée dans un article : on ne la présentera comme celle du collectif qu'après vérification.
- **Pas de fiche d'établissement Google.**
- **Les langues** : le site actuel existe en français et en breton, mais les deux langues partagent les mêmes adresses, et seuls les textes de l'interface sont traduits en breton (à la main).
- **Le site actuel** : https://ssapaysdemorlaix.fr/, 18 pages, 52 images de contenu, réalisé par Kouzia sur un outil maison et hébergé chez Hostinger. L'audit (livrable 01) a relevé :
  - le même titre et le même résumé sur toutes les pages, et aucune information lisible par les moteurs de recherche sur l'activité ;
  - des pages qui changent de langue selon la navigation précédente ;
  - des textes trop peu contrastés (vert et jaune sur blanc), donc difficiles à lire ;
  - une déclaration d'accessibilité « partiellement conforme » et des mentions légales qui citent une police non utilisée.

## Publics et action principale

Par ordre de priorité :

1. **Les participant·es à l'expérimentation** : trouver les prochains événements et les lieux où dépenser leurs Buzuk SSA.
2. **Les habitant·es curieux** : comprendre la Sécurité Sociale de l'Alimentation et savoir comment rejoindre le projet.
3. **Les partenaires, financeurs et médias** : suivre les actualités et contacter le collectif.

## Périmètre

- **Dans** : un site public et un espace d'administration entièrement reconstruits ; la reprise de tous les contenus, photos et vidéos actuels ; une version bretonne traduite automatiquement ; une nouvelle page des lieux conventionnés ; le formulaire de contact ; la redirection des anciennes adresses ; les pages légales, la page Accessibilité, le plan du site et une page d'erreur ; le paquet d'installation et ses consignes.
- **Hors** : la mise en ligne (faite par Kouzia), l'administration du serveur et du domaine (La Capsule) ; tout nouveau texte, photo ou vidéo ; une carte interactive ; les comptes pour le public, le paiement, la lettre d'information ; la candidature en ligne ; la relecture humaine du breton ; la gestion des boîtes e-mail.

## Arborescence

Chaque page française a sa version bretonne, à la même adresse précédée de `/br/`. Les anciennes adresses en breton (`?lang=br`) mènent à la page bretonne correspondante.

| Page | Adresse | Intention du public (question tapée) | Remplace (ancienne adresse) |
|------|---------|--------------------------------------|-----------------------------|
| Accueil | `/` | sécurité sociale de l'alimentation Morlaix | `/`, `/?lang=fr`, `/?page=n` |
| Le projet | `/projet/` | comment fonctionne la caisse alimentaire de Morlaix | `/projet` |
| Qui sommes-nous | `/collectif/` | qui porte la SSA au Pays de Morlaix | bloc de l'accueil |
| Lieux conventionnés | `/lieux-conventionnes/` | où dépenser ses Buzuk SSA | `/article/16` |
| Actualités | `/actualites/` | actualités SSA Morlaix | bloc de l'accueil |
| Article | `/actualites/<titre>/` | titre de l'article | `/article/<numéro>` |
| Agenda | `/agenda/` | événements alimentation Morlaix | bloc de l'accueil |
| Événement | `/agenda/<titre>/` | titre de l'événement | aucune |
| Galerie | `/galerie/` | photos du collectif | `/galerie`, `/galerie?page=n` |
| Contact | `/contact/` | contacter la SSA Pays de Morlaix | bloc de l'accueil |
| Mentions légales | `/mentions-legales/` | sans objet | `/mentions-legales` |
| Confidentialité | `/confidentialite/` | sans objet | `/confidentialite` |
| Accessibilité | `/accessibilite/` | sans objet | `/accessibilite` |
| Plan du site | `/plan-du-site/` | sans objet | aucune |
| Administration | `/admin/` (non référencée) | sans objet | `/login` |

## Décisions validées

| Date | Décision | Raison |
|------|----------|--------|
| 2026-11-13 | Site associatif : le collectif publie lui-même ses contenus | actualités, agenda et galerie changent souvent |
| 2026-11-13 | Reprise des contenus du site actuel, mais pas de son code | outil maison fragile, hors des bonnes pratiques actuelles |
| 2026-11-13 | Site en français et en breton, une adresse par langue | meilleur référencement, langue stable |
| 2026-11-13 | Saisie en français seulement ; le breton est traduit automatiquement à l'enregistrement et publié aussitôt, avec la mention visible « Traduit automatiquement » et une correction possible à la main | demande de Kouzia ; risque d'erreurs de breton accepté |
| 2026-11-13 | Traduction par Gallek, un modèle libre et gratuit installé sur le serveur ; aucun service extérieur ; les noms propres et termes du projet ne sont jamais traduits | une traduction mot à mot donne un breton fautif ; Gallek est léger et gratuit |
| 2026-11-13 | Espace d'administration pour une seule personne (Géraldine Gabillet), connexion en deux étapes, sauvegardes chiffrées ; rubriques : actualités (photos, vidéos), agenda, galerie, partenaires et financeurs, lieux conventionnés | rubriques du site actuel, plus les lieux conventionnés |
| 2026-11-13 | Page dédiée aux lieux conventionnés : liste avec adresse et lien d'itinéraire, sans carte interactive | premier public ; pas de service extérieur sur le site |
| 2026-11-13 | Formulaire de contact gardé ; les messages partent par e-mail à ssapaysdemorlaix@mailo.com et ne sont pas conservés sur le site | moins de données personnelles stockées |
| 2026-11-13 | Bloc « On recrute ! » retiré | candidatures closes le 20 septembre 2025 |
| 2026-11-13 | Éditeur légal : ULAMIR-CPIE ; directrice de la publication : Géraldine Gabillet | le collectif n'a pas d'existence juridique |
| 2026-11-13 | Hébergement chez La Capsule, chantier d'insertion numérique de l'ULAMIR-CPIE, sur ses serveurs du centre AFPA de Morlaix | choix de Kouzia |
| 2026-11-13 | Mise en ligne faite par Kouzia | choix de Kouzia |
| 2026-11-13 | Technique : PHP 8.4 et base de données SQLite | versions maintenues jusqu'à fin 2028, simples à installer et à sauvegarder pour un site de cette taille |
| 2026-11-13 | Vidéos gardées sur le site, chargées seulement au clic | pages légères, aucun service extérieur |
| 2026-11-13 | Accessibilité : la page Accessibilité affiche le niveau prouvé par l'audit, jamais « totalement conforme » | honnêteté ; pas d'obligation réglementaire établie |
| 2026-11-13 | Recherches visées : « sécurité sociale de l'alimentation Morlaix », « caisse alimentaire Morlaix », « monnaie locale Buzuk », « lieux conventionnés SSA » ; contenus non cédés à l'entraînement des IA | choix de Kouzia |
| 2026-11-13 | Une seule personne valide chaque étape côté client : Géraldine Gabillet | choix de Kouzia |

## Modèle à respecter

- **Modèle du projet** : le logo fourni. Pas de charte graphique : la direction artistique part du logo et des couleurs actuelles, avec des contrastes corrigés.
- **Kit** : rules `web-*`, `web-architecture`, conventions de ce mode d'emploi.

## Ton et vocabulaire

- Registre clair, chaleureux, engagé sans jargon ; vouvoiement.
- Écriture inclusive au point médian (citoyen·nes, paysan·nes), comme sur le site actuel.
- Vocabulaire du projet : « La Collective », « parcours citoyen », « caisse citoyenne », « lieux conventionnés », « Buzuk SSA ».
- Langues : textes écrits en français, traduits automatiquement en breton.

## Hébergement

- **Hébergeur** : La Capsule, chantier d'insertion numérique de l'ULAMIR-CPIE, serveurs au centre AFPA de Morlaix, 39 Bellevue de la Madeleine, 29600 Morlaix ; contact@lacapsule.org ; 07 86 44 77 52. La Capsule administre les serveurs.
- **Domaine, DNS, e-mails** : le domaine ssapaysdemorlaix.fr est géré par La Capsule ; les boîtes e-mail restent chez Mailo.
- **Mise en ligne** : faite par Kouzia à partir du paquet d'installation et de ses consignes.

## Données et contenus

- **Contenus repris** : les 11 articles, les événements, la galerie et les pages légales du site actuel, importés automatiquement.
- **Photos et vidéos** : seulement celles déjà publiées, rien d'ajouté ; les personnes représentées ont donné leur autorisation écrite.
- **Coordonnées** : saisies à un seul endroit et reprises partout (pages, pied de page, informations pour les moteurs).

## Étapes

| # | Étape | Livrable | Preuve attendue | État |
|---|-------|----------|-----------------|------|
| 1 | Audit du site actuel | livrable 01 | inventaire des contenus | livré |
| 2 | Recueil des besoins | mode d'emploi du projet | validé par Kouzia | validé |
| 3 | Cahier des charges | livrable 02 | validation 1 par Géraldine Gabillet | en cours |
| 4 | Mise en place technique | base du site | contrôles techniques au vert | à faire |
| 5 | Structure des pages | wireframes, livrable 03 | validation 2 | à faire |
| 6 | Construction : pages, import des contenus, administration, traduction bretonne | site complet en test | contrôles techniques au vert | à faire |
| 7 | Direction artistique : 3 pistes montrées sur les vraies pages | livrable 04 | validation 3 : choix d'une piste | à faire |
| 8 | Finitions et audits (référencement, accessibilité, sécurité, poids des pages) | rapports d'audit | contrôles au vert | à faire |
| 9 | Recette | procès-verbal de recette | validation 4 signée | à faire |
| 10 | Livraison | paquet d'installation, consignes, dossier de livraison | site en ligne contrôlé | à faire |

## À fournir

| Élément | Qui | Pour quand | Bloque |
|---------|-----|------------|--------|
| Vérification de l'identité légale d'ULAMIR-CPIE (nom, SIRET, siège) | Géraldine Gabillet | avec la validation du cahier des charges | mentions légales |
| Suite du site après la fin de l'expérimentation | Géraldine Gabillet | avec la validation du cahier des charges | rien |
| Liste des lieux conventionnés (nom, adresse, horaires si connus) | Géraldine Gabillet | avant la construction (étape 6) | page Lieux conventionnés |
| La chaîne YouTube citée dans l'article du colloque est-elle celle du collectif ? | Géraldine Gabillet | avant la construction (étape 6) | rien (non citée sinon) |

## Règles propres au projet

### Sources (pour l'agent)

- Demande dans les mots de l'humain (2026-11-13) : « il est pas propre, il est basé sur un framework un peu bancal qui a été fait main, il est pas à jour sur les bonnes pratiques que l'on va appliquer » ; reformulée pour le client le 2026-11-13 à sa demande.
- Audit : `docs/source/existant/inventaire.json` et livrable 01 version 2 (relancé le 2026-11-13) : 18 pages, 40 variantes à paramètres, 18 anciennes adresses, 52 images. Défaut constaté : `?page=n` s'affiche dans la langue gardée en session.
- Code et données de l'ancien site : `../SSA_Capsule1` (confirmé le 2026-11-13), lu seulement, jamais modifié. Base `data/database.sqlite`, médias `public/assets/img/` (vidéos dans `articles/13/` et `articles/15/`, lues par `templates/modules/article/articleDetails.tpl.php`), chaînes bretonnes humaines `app/Lang/locales/br` (ex. « Gwareziñ Sokial ar Boued · Bro Vorlaix ») à reprendre dans le glossaire.
- Dossier de candidature : `docs/source/fournis/documents/dossier_de_candidature.pdf` (durée, date limite, adresse du Buzuk). Logo : `docs/source/fournis/logo/logo.svg`.
- Identité légale d'ULAMIR-CPIE : API recherche-entreprises.api.gouv.fr, données INSEE du 2026-10-13 (SIREN 300796349, nature juridique 9220, active) ; fiche annuaire-entreprises illisible lors de la recherche.
- Chaîne YouTube : `https://www.youtube.com/@jneasso9668/featured`, citée seulement dans `/article/10` ; aucun `sameAs` avant vérification.
- Recherche traduction (2026-11-13) : Apertium `br-fr` sans sens fr->br publié (mode `fr-br` non installé), NLLB-200 sans breton, Azure sans breton, Gallek `amurienne/gallek-m2m100` (base m2m100_418M, MIT) fr->br seulement.

### Technique

- Socle dynamique PHP (`make dynamique`, après la validation 1), PHP 8.4, SQLite. Choix techniques de l'agent (rule `00-organisation`).
- Traduction : service Python local Gallek, appelé à l'enregistrement d'un contenu ; résultat stocké en base ; glossaire fr-br versionné imposé avant et après le modèle (termes jamais traduits : « La Collective », « Buzuk SSA », noms de structures et de lieux). Mémoire et processeur nécessaires mesurés et écrits dans les consignes d'installation.
- Une page bretonne existe pour chaque page française publiée ; mention « Traduit automatiquement » tant qu'elle n'a pas été corrigée à la main ; `hreflang` réciproques et `x-default`.
- `/admin/` en `noindex` et interdit dans `robots.txt` ; `ai-train=no` dans `Content-Signal`.
- Vidéos : `preload="none"` et image d'attente.
- Coordonnées, éditeur et hébergeur : une source unique dans `config/` ; contenus en base SQLite ; anciennes adresses dans la table des redirections 301.
- Mise en ligne par l'humain : l'agent ne demande ni n'utilise aucun accès ; SMTP et secrets posés par l'humain dans le `.env` du serveur.

### Écarts de l'ancien site à ne pas reproduire

- P0 : contrastes sous 4,5:1 (vert `#43c466` 2,25:1, jaune `#fdb544` 1,77:1 sur blanc) ; déclaration « partiellement conforme ».
- P1 : `title` et description identiques partout, aucune canonique, aucun JSON-LD ; deux langues sur une même URL ; langue gardée en session.
- P2 : police Outfit citée dans les mentions légales mais non servie.

### Contenu

- Aucun contenu, chiffre, photo ou lieu inventé : seulement ce qui existe sur l'ancien site ou ce que fournit Géraldine Gabillet.

## Ajouts à la checklist de livraison

- Les 18 anciennes adresses et `?lang=br` répondent 301 vers la bonne nouvelle page (test HTTP).
- Chaque page française a sa page `/br/`, `hreflang` réciproques et `x-default`, mention « Traduit automatiquement ».
- Mentions légales : éditeur ULAMIR-CPIE (nom, SIRET, siège vérifiés), directrice de la publication, hébergeur La Capsule avec téléphone.
- Consignes d'installation à jour : PHP 8.4, extensions, service Gallek (mémoire et processeur mesurés), SMTP, tâches planifiées, sauvegarde chiffrée et restauration testée.
- Dossier de livraison : rappel des actions de l'humain après mise en ligne (sitemap soumis à Search Console et Bing Webmaster, propriété vérifiée par DNS).

## Rapport hebdo

- Jour : vendredi
- Points suivis en particulier : aucun en plus du modèle

## Journal des erreurs

| Date | Erreur | Cause | Règle ajoutée (où) |
|------|--------|-------|--------------------|
| 2026-11-13 | Cahier des charges version 1 rempli de termes internes (chemins, commandes, balises de source) | sections du mode d'emploi écrites pour l'agent, reprises telles quelles, sans contrôle | phrase d'en-tête de ce fichier ; contrôle des termes internes dans `skills/livrables` (section 7) |
