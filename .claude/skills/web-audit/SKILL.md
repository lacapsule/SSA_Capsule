---
name: web-audit
description: Choisit et lance les contrôles adaptés à un changement de site web (SEO, JSON-LD, accessibilité, poids, CSS, sécurité, dépôt), puis la recette complète avant mise en production. À utiliser avant de déclarer une tâche terminée, avant un déploiement, ou pour auditer un site existant.
---

# Audit et recette d'un site

Source : `agent-validation-discipline.md` et la chaîne de gates `validate-all` de CapsuleOS. Principe : lancer le contrôle minimal du périmètre touché pendant le travail, la recette complète avant mise en production. Aucune tâche n'est terminée tant qu'un contrôle applicable est rouge ; ne jamais affaiblir un contrôle pour le faire passer.

## Scripts (sans dépendance)

| Script | Contrôle |
|--------|----------|
| `scripts/check-dist.mjs [dist] [https://domaine]` | lang, h1, titres, title/description (longueur, unicité), canonical, Open Graph, viewport, alt et dimensions d'images, liens locaux cassés, ressources tierces, `var(--x)` orphelines, JSON-LD (syntaxe, `@id` dupliqués, booléens, FAQ visible, `BreadcrumbList` hors accueil), indexation (indexable ⇔ sitemap ⇔ canonique ⇔ sans `noindex`, `robots.txt` qui n'interdit aucune URL du sitemap, `lastmod` réel), pages orphelines, `hreflang` réciproques, dimensions de `og:image`, liens de `llms.txt`, 404 en `noindex`, poids par page (une seule définition du poids, à réutiliser pour tout poids affiché par le site) |
| `scripts/check-repo.mjs` | secrets et fichiers sensibles suivis par Git, `.gitignore`, verrouillage des dépendances |
| `scripts/ordre-css.mjs [--check] <dossier>` | ordre des propriétés CSS par familles (rule `web-code`), tri stable sans changement de cascade ; sans `--check`, réécrit (copie de la brique `modules/ordre-proprietes-css` de la boîte) |
| `scripts/check-mise-en-page.mjs [url]` | conventions de base de la rule `web-design` mesurées sur chaque page à 390, 800, 1280 et 1920 px (en-tête sur une ligne et 5 rem au plus, tailles des titres et du texte, titres sur 3 lignes au plus, rien de fixe sur un contenu, pas de défilement horizontal), puis chaque page à côté de celle qu'elle remplace dans `.claude/etat/comparaison/` ; sans adresse, sert le site lui-même ; dans `make verify` |
| `scripts/check-rendu.mjs <url> <rendu-pages.json> [captures]` | rendu réel de chaque page dans les deux thèmes : même géométrie et typographie, filets alignés et de même couleur, hiérarchie des titres, barre d'enregistrement cachée sans modification, débordement à 360, 1280 et 2560 px ; écrit les captures, à regarder ensuite (serveur local lancé, `playwright-core`) |
| `scripts/check-outils.sh [--installer\|--simuler]` | liste unique des outils du kit : état (code 1 si un outil obligatoire manque, `make outils`) ou installation de tout ce qui manque en une commande (paquets système, `npm ci`, Chromium de Playwright sans Chrome, `make deps`, lancée par l'humain car `sudo`) |
| `../web-a11y-audit/check-a11y.mjs <url> [chemins]` | scan axe-core de chaque page du sitemap et de la 404 (`make a11y`) |
| `../web-media/scripts/images-partage.mjs` | image de partage 1200 × 630 et icônes depuis leurs sources (`make images`) |
| `scripts/check-headers.sh https://domaine` | en-têtes de sécurité, CSP, HTTPS, 404, compression, fichiers sensibles inaccessibles (site en ligne) |

Chemins relatifs à `.claude/skills/web-audit/`.

## Contrôle par type de changement

| Changement | Contrôle minimal |
|------------|------------------|
| Texte, données, FAQ | build + `check-dist.mjs` |
| Composant, page, CSS | build + `check-dist.mjs` + `ordre-css.mjs --check` + `make a11y` (skill `web-a11y-audit` §1) |
| Écran avec thèmes ou back-office | `check-rendu.mjs`, puis captures regardées en clair et en sombre, à 1280 et 360 px |
| Composant interactif (menu, modale, formulaire, filtre) | matrice d'états du skill `web-ui-states` rejouée au clavier et à la souris |
| Image, police, vidéo | skill `web-media` + build + `check-dist.mjs` (poids, dimensions) |
| Méta, JSON-LD, robots, sitemap, nouvelle page | `check-dist.mjs dist https://domaine` (le domaine active les contrôles d'indexation) + Test des résultats enrichis Google |
| `.htaccess`, en-têtes, formulaire | skills `web-security-headers` / `web-forms-security`, puis `check-headers.sh` après déploiement |
| Dépendance, `.env`, config | `check-repo.mjs` + `npm audit --omit=dev` |
| Branche, fusion, déploiement | skills `web-git` et `web-release` |
| Documentation seule | aucun |

## Recette avant mise en production

1. `make verify` vert (build sans erreur ni avertissement, `check-dist.mjs`, `check-repo.mjs`). Socle Astro : `npm ci` et `npm audit --omit=dev` en plus.
2. Socle dynamique : tests automatiques verts, y compris droits (403) et validation.
3. `node scripts/check-dist.mjs dist https://domaine` : 0 erreur ; chaque avertissement corrigé ou justifié.
4. Accessibilité : skill `web-a11y-audit` (scan + passe manuelle) sur chaque gabarit.
5. Performance : Lighthouse mobile (`npx --yes lighthouse@13.6.0 https://domaine --form-factor=mobile --only-categories=performance,accessibility,best-practices,seo --view`). Viser ≥ 90 partout, LCP ≤ 2,5 s, CLS ≤ 0,1, TBT faible. En production, suivre les Core Web Vitals réels (Search Console).
6. Éco-conception : poids et requêtes de `check-dist.mjs` dans le budget ; contrôle externe EcoIndex (ecoindex.fr), viser A ou B.
7. Page d'erreur (`check-headers.sh` en contrôle une partie, le statut seul ne prouve rien : la page par défaut d'un hébergeur répond aussi 404) : une URL inexistante (statique et dynamique, ex. `/article/999999`) répond 404 avec la page d'erreur du site (en-tête, pied de page, styles, `noindex`), jamais une page serveur par défaut ni un texte brut.
8. Après déploiement : `bash scripts/check-headers.sh https://domaine`, console du navigateur sans erreur ni blocage CSP, sitemap soumis dans Search Console.
9. Légal : skill `web-legal-fr`, aucun marqueur à remplir restant sur les pages publiées.

## Compte rendu

Le compte rendu est celui du skill `livraison` : les résultats de ces contrôles remplissent ses lignes 4 et 5.
