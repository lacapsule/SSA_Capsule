# Mode d'emploi : Site SSA Pays de Morlaix

- depot: SSA_Capsule
- type: associatif (confirmé le 2026-11-12)
- statut: validé le 2026-11-12
- mise à jour: 2026-11-12

Les règles `web-*` et `00-organisation` s'appliquent. Ce fichier ne contient que ce qui est propre au projet.

## Objectif

- **Demande** (mots de l'humain) : « correction de bug, css, application des conventions de code css etc, responsive mobile etc » (2026-11-12)
- **Pour qui** : l'expérimentation de Sécurité Sociale de l'Alimentation (SSA) en Pays de Morlaix, projet collectif et citoyen autogéré, porté conjointement par un collectif citoyen et quatre structures : ULAMIR-CPIE, Résam, Le Buzuk, Adess du Pays de Morlaix. Coordination opérationnelle : Géraldine Gabillet, chargée de mission à l'ULAMIR CPIE Pays de Lanmeur, ssapaysdemorlaix@mailo.com (interview du 2026-11-12)
- **Réussi quand** (critères vérifiables, validés le 2026-11-12) :
  - aucun défilement horizontal de 320 à 1920 px sur chaque page publique ;
  - boutons du héros lisibles et sans débordement de 320 px au grand écran ;
  - aucun trait décoratif (`::before`/`::after`) ne chevauche un texte, à toutes les largeurs ;
  - contrôle de centrage `check-centering.mjs` vert sur les pages publiques ;
  - contrastes mesurés ; tout écart AA signalé à l'humain (non corrigé, car cela touche au style) ;
  - CSS sans valeur en dur hors des tokens (couleurs, espaces, typo, rayons) ;
  - captures avant/après présentées pour chaque page touchée ;
  - mentions légales et politique de confidentialité prêtes ; `robots.txt` et `sitemap.xml` servis en 200.
- **Échéance** : sans échéance (interview du 2026-11-12)

## Contexte

- Code repris de « CapsulePHP », boilerplate MVC maison sans framework (constaté : `README.md`).
- Dépôt GitHub `lacapsule/SSA_Capsule`, branches `main`, `dev`, `Front`, `demo` (constaté : `git branch -a`).
- La version la plus avancée vivait hors git sur le partage `/mnt/team/#TEAM/#INTERNE/#WEB/SSA_DEV`. Elle est sauvegardée sur la branche `save/ssa-dev-2026-11-12`, poussée le 2026-11-12 (constaté).
- Activité git : 71 commits en octobre 2025, puis 3 en novembre, et plus rien jusqu'à octobre 2026. Contributeurs : trollheap, n0r3f, La Capsule / SandersonnDev / Kouzia (constaté : `git log`).
- Crédit du site : « Design et développement : Kouzia, Alexandre Kouziaeff EI, au sein de La Capsule » (constaté : `templates/partials/public/footer.tpl.php`).
- En production sur https://ssapaysdemorlaix.fr/ chez Hostinger, mise en ligne par FTP (`FTP.md`). Accès FTP et Hostinger détenus par La Capsule (interview du 2026-11-12).
- La production est plus ancienne que la sauvegarde : pas de crédit Kouzia dans son pied de page (constaté : `curl` du 2026-11-12).
- En-têtes de sécurité posés par PHP en production : CSP, `X-Frame-Options`, `nosniff`, `Referrer-Policy`. Pas de HSTS (constaté : `curl -I` du 2026-11-12).
- Langues : français et breton (constaté : `app/Lang/locales/fr`, `br`).
- Base locale : 2 comptes admin, 9 articles, 25 événements, 1 message de contact (constaté : `data/database.sqlite`, 2026-11-12).

## Publics et action principale

Ordre validé le 2026-11-12. Le travail ne change ni la mise en page ni les contenus : cet ordre sert à prioriser les corrections.

1. Habitants du Pays de Morlaix qui veulent comprendre la SSA et y participer : doivent trouver en un écran ce qu'est la SSA et comment participer (héros de l'accueil).
2. Participants : agenda et actualités.
3. Partenaires et financeurs : page projet, bloc partenaires.

## Périmètre

- **Travail demandé** (2026-11-12) : corrections CSS et responsive des pages publiques d'abord (accueil, projet, galerie, article, connexion ; l'agenda/calendrier est une section de l'accueil via l'API `/agenda/api/events`, pas une page séparée), puis le tableau de bord. Bugs connus : boutons du héros en mobile, traits en `::before` qui chevauchent des textes. Le reste est inventorié par l'analyse puis corrigé.
- **Existant** (constaté) : accueil (à propos, actualités, événements, partenaires, contact), page projet, galerie, détail d'article, agenda public, connexion, tableau de bord (compte, utilisateurs, articles, agenda et catégories, galerie, partenaires, médias du projet), formulaire de contact enregistré en base et envoyé par `mail()`.
- **Exceptions écrites** (2026-11-12) : pages mentions légales, politique de confidentialité et déclaration d'accessibilité (obligation légale) ; `robots.txt` et `sitemap.xml` ; corrections d'accessibilité sans effet visible (focus clavier, `alt`, ARIA, structure des titres).
- **Hors** (interview du 2026-11-12) : toute refonte. Le reste du SEO (titres, textes, données structurées). On ne touche ni à la mise en page, ni au style visuel, ni aux contenus (textes, images). Seulement : relevé et correction de bugs, responsive, code mort, remise du code aux conventions du kit, sans changement visible sur ordinateur.

## Décisions validées

| Date | Décision | Raison |
|------|----------|--------|
| constaté 2026-11-12 | Socle : PHP 8.2 dynamique, framework maison (`src/`), modules dans `app/Modules`, gabarits Mustache-like (`templates/`), SQLite (`data/database.sqlite`) | existant |
| constaté 2026-11-12 | Hébergement Hostinger (Apache, `.htaccess` racine vers `public/`) | existant, `.htaccess` |
| constaté 2026-11-12 | Rôles `admin` et `employee` | `migrations/sqlite_init.sql` |
| constaté 2026-11-12 | Bilingue français et breton | `app/Lang/locales` |
| 2026-11-12 | Type `associatif` | confirmé à l'interview |
| 2026-11-12 | Conventions CSS : celles du kit (`web-design`, `web-code`) | interview |
| 2026-11-12 | Ordre : pages publiques avant le tableau de bord | interview |
| 2026-11-12 | Mise en production manuelle par l'humain (FTP), sans lien avec le dépôt ; aucun déploiement par Claude | interview |
| 2026-11-12 | Pied de page : ligne SIRET et APE retirée, sans texte de remplacement | numéro faux, pas de SIRET pour un collectif ; pas d'ajout de contenu |
| 2026-11-12 | Pages légales créées malgré le périmètre sans contenu nouveau | obligation légale |
| 2026-11-12 | Code mort : liste soumise à l'humain avant toute suppression | rule `00-organisation` |
| 2026-11-12 | Git : `main` = production ; travail en commits directs sur `dev` (un commit par correction, les commits servent de sauvegarde), pas de branche par correction ; fusion de `dev` dans `main` après validation de l'humain | interview |
| 2026-11-12 | Option A : `dev` repart du contenu de `save/ssa-dev-2026-11-12`. L'ancienne `dev` distante (déc. 2025 : calendrier public, articles, SHA `336b6e2`) archivée en `archive/dev-2025-12` sur GitHub, puis `dev` distante remplacée par la nouvelle. Report au cas par cas si un manque apparaît | fusion directe trop risquée (20 fichiers code + 86 images en conflit) |
| 2026-11-12 | Paquet FTP limité aux fichiers utiles en production ; `main` garde tout le code | éviter les conflits de fusion |
| 2026-11-12 | Images du site versionnées dans git (logo, héros/illustrations projet en webp optimisé, logos, icônes, ~4,5 Mo) ; seuls `img/articles`, `img/gallery` et les vidéos restent hors git (téléversements du tableau de bord) | déploiement plus simple : les images partent avec le code (demande de l'humain) |
| 2026-11-12 | Remise aux conventions sans changement visuel : les tokens reprennent les valeurs existantes | périmètre sans refonte |
| 2026-11-12 | `FTP.md` tenu à jour : liste des fichiers modifiés à envoyer à chaque livraison | interview |

## Modèle à respecter

- **Modèle du projet** : le site existant tel qu'il s'affiche aujourd'hui sur ordinateur (gabarits et CSS de `save/ssa-dev-2026-11-12`). C'est la référence visuelle : aucune différence visible hors bug corrigé (interview du 2026-11-12).
- **Kit** : rules `web-*`, `web-architecture`, conventions de ce mode d'emploi. Conventions CSS du kit appliquées au code existant (confirmé le 2026-11-12).

## Ton et vocabulaire

- Sans objet pour le site existant (contenus inchangés).
- Pages légales : français neutre et juridique. Version bretonne : à fournir, humain, si souhaitée (sinon les pages restent en français).

## Données et contenus

- Sources uniques : textes fixes dans `app/Lang/locales/{fr,br}/index.php`, contenus édités dans `data/database.sqlite`, médias dans `public/assets/img` (1,9 Go, hors git).
- Données personnelles : comptes (nom, e-mail), messages de contact (nom, e-mail, message, IP) sans durée de conservation (constaté : table `contacts`).
- Images : des personnes figurent sur les photos ; les autorisations de droit à l'image ont été recueillies (interview du 2026-11-12).
- Mise à jour du contenu : par la SSA via le tableau de bord, fréquence inconnue (interview du 2026-11-12).
- **Données de test locales (2026-11-12)** : 3 événements à venir marqués « (test local) » ajoutés dans `data/database.sqlite` pour voir la section « événements à venir » remplie (la base restaurée du partage date de février 2026, tous ses événements sont passés). Local uniquement, `data/` hors git et hors paquet FTP. À retirer si une vraie base de prod est importée.

## Étapes

| # | Étape | Livrable | Preuve attendue | État |
|---|-------|----------|-----------------|------|
| 1 | Préparer `dev` (= contenu de `save/ssa-dev-2026-11-12`, option A), `.gitignore` corrigé | branche `dev` locale | `check-repo.mjs` vert ; commit `a855d13` | **livré** (local, non poussé) |
| 2 | Inventaire des bugs des pages publiques à 320/768/1280/1920 px (Chrome headless + mesure de débordement) | liste priorisée + captures | fait le 2026-11-12 | **livré** |
| 3 | Boutons du héros en mobile (empilés < 600 px) | commit `f7c6b7f` | captures avant/après, vérif 320→1920 px : 0 tronqué, 0 débord | **livré** |
| 3b | Héros mobile : pleine hauteur d'écran + titre équilibré (< 600 px) | commit `6ff8eeb` | héros = 100 % écran ≤600 px, desktop inchangé | **livré** (demande du 2026-11-12) |
| 4 | Traits `::before` qui chevauchent les textes (ancrés au bas du h2) | commit `7317496` | captures avant/après about+projet, 320 et 1280 : plus de chevauchement | **livré** |
| 5 | Connexion : débordement +5 px à 320 px (carte fluide ≤360 px) | commit `d8f1c86` | captures avant/après, vérif 320→1280 : 0 débord | **livré** |
| 6 | CSS aux conventions (tokens) | public entierement tokenise (plus aucun hex hors variables.css dans le CSS public) ; dashboard (60 couleurs) a consolider a part | public **fait**, dashboard **a faire** |
| 7 | Code mort : liste puis suppression validée | liste, puis commits | liste validée par l'humain | à faire |
| 8 | Pied de page sans SIRET ; `robots.txt` + `sitemap.xml` (module Seo) ; accessibilité invisible (audit RGAA) | tout fait | SIRET retiré, robots/sitemap en routes dynamiques, a11y 0 violation axe | **livré** |
| 9 | Mentions légales, confidentialité, déclaration d'accessibilité | pages créées et renseignées (`a330e28`, `ab387b2`) | mentions + confidentialité complètes ; accessibilité attend l'audit RGAA | **mentions/confid. OK, accessibilité à auditer** |
| 10 | Tableau de bord : étapes 2 à 7 | correctifs | idem | à faire |
| 11 | Paquet de mise en ligne : liste des fichiers de production dans `FTP.md`, sans `data/` ni images envoyées | `FTP.md` | liste relue par l'humain ; envoi FTP fait par l'humain | à faire |

## À fournir

| Élément | Qui | Pour quand | Bloque |
|---------|-----|------------|--------|
| Audit RGAA (état de conformité, résultats) | à planifier | avant publication | déclaration d'accessibilité |
| Implémenter la purge des messages de contact à 3 ans (la politique l'annonce, aucune purge automatique n'existe) | à faire | avant mise en conformité | respect de la durée déclarée |

## Règles propres au projet

Écarts relevés à l'analyse du 2026-11-12 :

- **P0** Le SIRET affiché « 300 79 63 489 000 11 » a 15 chiffres, un SIRET en a toujours 14 (SIREN 9 + NIC 5), aussi en production. Selon l'humain, il n'existe probablement pas de SIRET : le projet est un collectif d'associations (interview du 2026-11-12). Le numéro affiché est donc faux. Décision : ligne retirée sans remplacement (étape 8).
- **P0** Pas de mentions légales, de politique de confidentialité ni de déclaration d'accessibilité, ni en local ni en production (`/mentions-legales` renvoie 404). À faire (étape 9).
- **P2** Compte de démo `admin` / `admin@example.org` du seed (`migrations/sqlite_init.sql`). Absent de la base locale (seuls `Geraldine` et `n0r3f`, réels) ; le seed est en `ON CONFLICT DO NOTHING`. Jugé absent de la prod par l'humain le 2026-11-12 (non vérifié faute d'accès à la base de prod). Si un doute : `SELECT … FROM users WHERE email='admin@example.org'` côté Hostinger.
- **P0** Un renvoi complet du site ne doit jamais écraser `data/` (base de production) ni les images envoyées par le tableau de bord (`public/assets/img/articles`, `gallery`, `logos`, `projet`).
- **P1 (corrigé sur `dev`, `a855d13`)** `public/assets` était ignoré en entier. Désormais seul `/public/assets/img/` l'est. Les images lourdes (~1,9 Go) ne sont toujours sauvegardées que sur le partage : sauvegarde hors git à mettre en place (reste P1).
- **P1** Le travail se fait hors git (partage, FTP fichier par fichier) : risque de perte et de dérive entre la production et le dépôt.
- **P1** Pas de `robots.txt` ni de `sitemap.xml` (404 en production), pas de HSTS.
- **P1 (corrigé sur `dev`, `a855d13`)** `.gitignore` couvre `.env`.
- **P2** `vendor/` absent : tests PHPUnit et PHPStan non lancés. Pas de cible `make verify`.
- **P2** Traductions mortes avec des coordonnées factices (`contact_address`, `contact_phone`, `contact_email1/2` dans `app/Lang/locales`) : le gabarit `contact.tpl.php` affiche les coordonnées en dur, donc non traduites en breton.
- **Demandes du 2026-11-12 (suite) :** menu mobile plein écran + fermeture au clic (fait), cartes d'événements mobile (fait), titre d'article (fait), retour au site connexion (fait), page 404 (fait), pages légales + liens (fait), conventions anti-trace d'IA (fait). **À faire ensuite, avant l'audit RGAA :** audit d'optimisation, tri du code mort.
- **Audit complet du 2026-11-12 (5 pages × 5 largeurs) — à arbitrer par l'humain :**
  - ✅ Cartes d'événements mobile (date + description) — commit `bd82efe`.
  - ✅ Connexion : retour au site (lien + logo cliquable) — commit `efa1732`.
  - ✅ Page 404 au gabarit du site — commit `af72085`.
  - ✅ Titre d'article (césure) — commit `8c4d820`.
  - ✅ Menu mobile plein écran (règle aussi le « ? » seul) — commit `7a8d649`.
  - **Faux positifs écartés** : pagination actus/galerie (artefact de capture pleine page, `position:sticky`), « +302 px partout » (était l'erreur PHP/DB).
- **~~P2 pagination actus~~ (faux positif, 2026-11-12)** Le « chevauchement » de la pagination sur le titre « NOS ACTUALITÉS » était un artefact de capture pleine page (la pagination réutilise `.gallery-pagination` en `position:sticky`). Vérifié dans un vrai écran : aucun chevauchement. Aucune correction.
- **P2** Restes à trier : `templates/modules/wiki` (lien `projet-ssa.html`, sans route), `public/assets/css/bak`, `tests/Http/RequestTestV2.php.bak`, `bin/resolve-git-conflicts.sh`.

## Ajouts à la checklist de livraison

- Captures avant/après à 320, 768 et 1280 px de chaque page touchée : aucune différence visible sur ordinateur hors bug corrigé.
- `FTP.md` à jour avec les fichiers modifiés.
- Aucun fichier de `data/` ni image envoyée par le tableau de bord dans la liste FTP.

## Rapport hebdo

- Jour : vendredi
- Points suivis en particulier : avancement page par page ; liste des fichiers à envoyer en FTP (`FTP.md`).


## Audit optimisation et code mort (2026-11-12)

### Optimisation (poids des pages, cible kit < 500 Ko)
- Accueil 1,9 Mo ; projet **17,5 Mo** ; galerie 2,1 Mo ; article **57 Mo** ; connexion 181 Ko.
- Assets lourds à recompresser (touche aux fichiers, accord requis) :
  - `projet/projetssa.webp` **13,5 Mo** (héros) ; `projet/recrutementBanner.png` 2,4 Mo ; `projet/illustration_projet_ssa.png` 1,3 Mo.
  - `articles/15/....webm` **57 Mo → 7,4 Mo** et `articles/13/....webm` **529 Mo → 25 Mo** (ré-encodées VP9 720p, à redéployer en prod).
  - `logoSSA.png` **499 Ko** (logo, devrait faire < 20 Ko) ; `logo.svg` 115 Ko.
- Fait : `loading="lazy"` sur images sous la ligne de flottaison (commit). Vidéo déjà en `preload="metadata"`.
- JS : chargement conditionnel par page (accueil 5 fichiers au lieu de ~20).

### Héros (2026-11-12)
- Voile sombre ajouté sur l'image de fond des héros (accueil, projet) pour la lisibilité du texte blanc.

### Accessibilité (RGAA/WCAG 2.2 AA, 12 novembre 2026)
- Scan axe-core sur 6 gabarits + passe manuelle. **0 violation** après corrections.
- Corrigé : `<li>` orphelins, lien d'évitement, focus clavier (`:focus-visible`), `prefers-reduced-motion`, contrastes AA (boutons, cartes événement, 404, date d'article).
- Déclaration d'accessibilité renseignée (partiellement conforme ; audit RGAA tiers complet recommandé avant de revendiquer la conformité totale).

### Code mort (liste, suppression soumise à l'humain)
1. `app/Modules/Agenda/PublicAgendaController.php` : non enregistré (bootstrap/container), route `/agenda/api/events` morte → le calendrier public (modale « Voir le calendrier complet ») ne charge rien. **Décision : l'enregistrer (activer le calendrier) ou le supprimer (abandonner le calendrier).**
2. Modules JS orphelins (référencés nulle part) : `public/modules/dashboard/partners.js`, `public/modules/utils/form.js`, `public/modules/users/userEditor.js`.
3. Modules JS qui se chargent mais dont le déclencheur n'existe dans aucun gabarit : `users/userCheckboxes.js` (`.user-checkbox`), `users/userModal.js` (`#createUserBtn`), `calendar/eventManager.js` (`#modalCreateEvent`).
4. `templates/modules/wiki/` + clés `nav_wiki` (fr/br) : aucune route, page inaccessible (404).
5. `tests/Http/RequestTestV2.php.bak` : fichier de sauvegarde suivi par git.
6. Clés de pied de page inutilisées (0 gabarit) : `footer_siret`, `footer_APE`, `footer_copyright`, `footer_tel`, `footer_email_1`, `footer_email_2`.

## Journal des erreurs

| Date | Erreur | Cause | Règle ajoutée (où) |
|------|--------|-------|--------------------|
| 2026-11-12 | CSS absents en local après clone de `main` | `/public/assets` ignoré en entier par `.gitignore`, CSS jamais commités | `.gitignore` : n'ignorer que `/public/assets/img/` (commit `a855d13` sur `dev`) |
| 2026-11-12 | `data/database.sqlite` et `config/database.php` disparus du dossier local → site en erreur fatale PDO sur toutes les pages | fichiers ignorés par git perdus pendant des manips de branches/stash ; `git stash -u` ne les protège pas (il ignore les fichiers gitignored) | **Règle** : ne jamais faire de manips git risquées (stash -u, bascule de branche, merge test) sans sauvegarder d'abord `data/` et `config/database.php` hors dépôt. Restaurés depuis le partage SSA_DEV. |
| 2026-11-12 | Inventaire faussé : « +302 px de débordement sur toutes les pages » | c'était la bannière d'erreur PHP (DB KO), pas un bug CSS | toujours vérifier que le site répond sans erreur avant un audit visuel |
| 2026-11-12 | Faux bug « pagination qui chevauche le titre des actus » | artefact de capture pleine page : les éléments `position:sticky` s'y placent mal | vérifier tout défaut suspect dans un vrai écran (viewport) avant de conclure, pas seulement en capture fullPage |
| 2026-11-12 | todo.md recréé par erreur alors que l'humain l'avait supprimé | j'ai cru à une perte comme pour `data/`, sans demander | devant un fichier suivi disparu, demander avant de restaurer : ce peut être une suppression volontaire |
| 2026-11-12 | Tirets cadratin « — » et bordure gauche accent utilisés dans mon code (pages légales, 404, connexion, données de test) | convention kit `web-method` (Tirets) et `web-design` (bordure accent) non appliquée | tirets retirés partout (textes, templates, JS, commentaires PHP), bordure accent remplacée par contour 1 px ; à respecter aussi dans les messages de commit |
