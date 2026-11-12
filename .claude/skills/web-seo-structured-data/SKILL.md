---
name: web-seo-structured-data
description: Renseigne et contrôle title, meta, Open Graph, canonical, robots.txt, sitemap.xml, llms.txt et le graphe JSON-LD schema.org à partir du contenu visible, et met en place le SEO passif (indexation cohérente, patrons de titres, lastmod réel, maillage, pages par intention). À utiliser pour le référencement Google, les résultats enrichis, une FAQ, le partage social, la visibilité dans les assistants IA (GEO) ou le suivi après mise en ligne.
---

# Référencement et données structurées

Source : `convention-schema-org.md` (§ 5b URL canoniques), `strategie-seo-2026-11.md`, `seo-lib.mjs`, `build-sitemap.mjs` et les gates `validate-schema-org.mjs` et `validate-canonical-urls.mjs` de CapsuleOS. Règles : rule `web-seo`.

## 1. Une source, plusieurs projections

Les données (identité, services, pages, FAQ) vivent dans un seul fichier de données. Le HTML visible, les méta, le JSON-LD, `sitemap.xml` et `llms.txt` sont générés depuis lui et depuis la liste des routes. Ne jamais recopier une FAQ, une adresse ou une liste de pages à la main dans deux fichiers.

## 2. Indexation : une seule règle

Indexable ⇔ dans `sitemap.xml` ⇔ canonique égale au `<loc>` ⇔ sans `noindex`. Tout le reste est `noindex`.

| Page | Canonique | `noindex` | Sitemap |
|------|-----------|-----------|---------|
| Page publique | elle-même (absolue, slash final) | non | oui |
| Variante à paramètres (`?utm_…`, `?tri=`) | la page sans paramètre | non | non |
| Recherche interne, compte, panier, page utilitaire | aucune | oui (`noindex, follow`) | non |
| Administration | aucune | oui (`noindex, nofollow`) et accès protégé | non |
| 404, 500 | aucune | oui | non |
| Fragments servis au navigateur (HTML partiel, démo) | aucune | en-tête `X-Robots-Tag: noindex` | non |

- `robots.txt` interdit seulement ce qui ne doit pas être exploré du tout. Une page en `noindex` reste explorable, sinon son `noindex` n'est jamais lu et elle peut rester indexée par ses liens entrants.
- Règle de lecture de `robots.txt` : la règle la plus longue l'emporte, `Allow` gagne à longueur égale. Un `Disallow: /a` interdit aussi `/actions/`.
- Une page sortie du site : 301 vers son remplaçant (ou 410 si rien ne la remplace), retirée du sitemap.

## 3. SEO passif : ce que le build produit seul

| Élément | Production | Contrôle |
|---------|-----------|----------|
| `title` | patron par type de page, ex. fiche : `<Nom> à <Ville> : <service>` ; si > 60 caractères, patron court (`<Nom> : <service>`), puis `<Nom> · <Site>` | longueur, unicité (`check-dist.mjs`) |
| Description | patron qui reprend le fait utile de la page (ce qu'on y trouve, pour qui), 140 à 160 caractères | longueur, unicité |
| Canonique | `url` du site + chemin de la route, jamais saisie à la main | égale au `<loc>` |
| `sitemap.xml` | toutes les routes sauf celles en `noindex` ; `lastmod` = `git log -1 --format=%cs -- <source>` (contenu ou page), omis si inconnu | chaque page indexable y figure |
| `llms.txt` | titre, résumé et faits lus dans le fichier de données ; une ligne par page (titre et description) | liens vers des pages construites |
| JSON-LD | graphe depuis le fichier de données (§ 4) | syntaxe, `@id`, FAQ visible |
| Fil d'Ariane | depuis le chemin de la route et le titre des pages parentes, HTML et `BreadcrumbList` d'une même fonction | présent hors accueil |
| `og:image` | image par défaut du site (1200 × 630 réelle), surchargée par page si elle a son visuel | dimensions lues dans le fichier |

Deux pages qui portent le même nom (deux éditions, deux villes) se distinguent dans le patron (bureau, ville, édition) : aucun titre en double.

## 4. Règles du graphe JSON-LD

1. Un `@graph` par page, un `@id` stable par nœud (`https://example.com/#organization`, `#website`, `<url>#webpage`). Deux types sur le même `@id` fusionnent en un nœud inexploitable.
2. Les nœuds se référencent par `{"@id": "…"}`, ils ne se recopient pas.
3. `logo` est un `ImageObject` vers un fichier qui existe, pas une page.
4. Booléens en booléens (`true`), nombres en nombres, dates ISO 8601.
5. Un nœud par intention de recherche : l'entreprise (`Organization`/`LocalBusiness`/`ProfessionalService`), le site (`WebSite`), le produit ou service, le contenu. Ne pas tout fusionner.
6. Domaines distincts (site et maison mère) : ne pas les mélanger dans `url` et `@id`, sinon l'attribution se disperse.
7. `sameAs` : seulement des profils réels et actifs de l'entité (réseau social, annuaire officiel, dépôt public). Un lien vers un profil inexistant ou privé se retire.
8. Une seule catégorie reconnue par propriété (`applicationCategory`) ; une précision va dans la sous-propriété prévue (`applicationSubCategory`) plutôt que dans une valeur inventée.
9. Types utiles selon le site : `BreadcrumbList` (sous-pages), `FAQPage`, `Article`/`TechArticle` (avec `author`, `datePublished`, `dateModified` réels), `Product`/`Offer`, `Event`, `Course`, `LocalBusiness` (adresse, horaires affichés, `geo`), `CollectionPage` + `ItemList` (index d'une rubrique). Pas de `Review` ni `AggregateRating` sans avis réels affichés.

## 5. FAQ

- Chaque question et chaque réponse balisées figurent mot pour mot dans le HTML servi (pas injectées en JS).
- Rendu conseillé : `<details>`/`<summary>`, sans script.
- L'accueil montre un extrait (quelques questions) ; la FAQ complète et son `FAQPage` vivent sur leur propre page.
- Une réponse ne promet que ce que le site ou l'entreprise fait réellement.

## 6. Pages par intention et maillage

- À l'interview : lister les 3 à 10 questions que le public tape réellement (« plombier dépannage Morlaix dimanche », « tester Linux sans installer »). Chacune a une page qui y répond dans son `h1`, son premier paragraphe et son `title`. Sans donnée, partir des services et de la zone ; ajuster ensuite avec les requêtes de Search Console (rubrique Performances).
- Une page d'atterrissage apporte un contenu propre (méthode, comparatif, étapes, prix, zone) : pas de copie d'une autre page avec le nom de ville changé.
- Maillage : rubrique → pages (vrais liens `a`), page → rubrique, voisines et page d'action (contact, devis). Aucune page indexable orpheline : `check-dist.mjs` signale celles qui ne reçoivent aucun lien d'une autre page.
- Ancre de lien descriptive (« voir les tarifs du ramonage »), jamais « cliquez ici ».

## 7. Fichiers d'indexation

| Fichier | Contenu |
|---------|---------|
| `robots.txt` | `User-agent: *`, `Content-Signal: search=yes, ai-input=yes, ai-train=no` (valeur de l'interview), `Allow: /` ; `Allow` explicite des CSS/JS utiles au rendu ; `Disallow` admin, API, dossiers techniques ; `Sitemap:` absolu |
| `sitemap.xml` | URL canoniques indexables uniquement, `loc` absolu, `lastmod` réel ; plus de 50 000 URL : index de sitemaps |
| `llms.txt` | titre `#`, résumé `>` d'une phrase, faits clés (qui, quoi, où, contact), sections `##` de liens `[Page](url): description` ; mêmes faits que le site ; dire ce que le site n'est pas si une confusion est probable |
| `404.html` | gabarit du site, `noindex`, liens vers l'accueil et les rubriques |

Ne pas bloquer les robots des assistants (`GPTBot`, `ClaudeBot`, `PerplexityBot`, `Google-Extended`) sans décision écrite : le `Content-Signal` exprime déjà le refus d'entraînement.

## 8. Contrôle

```bash
make check   # build puis check-dist.mjs sur dist/ (socle Astro : npm run build puis le script)
```

`check-dist.mjs` vérifie : `lang`, `h1` unique, longueur et unicité de `title` et description, canonical absolu, `og:image` absolue et 1200 × 630, JSON-LD parsable, `@id` dupliqués, questions et réponses `FAQPage` présentes dans le texte visible, `BreadcrumbList` hors accueil, cohérence sitemap ⇔ canonique ⇔ `noindex`, `robots.txt` qui n'interdit aucune URL du sitemap, pages orphelines, `hreflang` réciproques, liens de `llms.txt`, 404 en `noindex`.

## 9. Après mise en ligne (action humaine, notée au mode d'emploi)

1. Propriété vérifiée dans Google Search Console et Bing Webmaster Tools (enregistrement DNS de préférence), sitemap soumis aux deux.
2. Test des résultats enrichis Google et validateur schema.org sur 3 pages types (accueil, page de rubrique, fiche).
3. Partage : aperçu de lien vérifié (image, titre, description) sur une messagerie réelle.
4. Un mois plus tard : Search Console, pages « Explorée, actuellement non indexée » et requêtes réelles ; ajuster titres et pages par intention (§ 6).
5. Activité locale : fiche d'établissement Google à jour (nom, adresse, téléphone identiques au site, lien vers le site), tenue par le client.

## 10. Liens entrants (SEO actif, par décision)

Rien n'est envoyé sans validation humaine (rule `00-organisation`). Si le client le demande :

- Viser des sites qui servent déjà le même public (annuaire professionnel, réseau local, partenaire, presse locale, ressource du domaine).
- Message court et personnel : pourquoi ce site, ce que la page apporte à ses lecteurs, une URL précise (une page par intention, pas l'accueil), une proposition concrète (captures, démonstration).
- Une seule relance. Jamais de lien acheté ni d'échange de liens en masse.
- Les faits du message (versions, offres, chiffres) sont revérifiés le jour de l'envoi.
