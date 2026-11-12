# SEO et découverte (Google et assistants IA)

Le balisage décrit la page affichée, rien de plus. Pas de `meta keywords`.

- `title` unique < 60 caractères, description unique 140 à 160 caractères, `link rel="canonical"` absolu vers une URL qui répond 200 (jamais vers une redirection).
- Une seule URL par contenu : `/index.html`, `www`, `http` et slash final redirigent en 301 vers la forme canonique.
- Open Graph (`og:title`, `og:description`, `og:url`, `og:image` absolue 1200×630 réelle, `og:locale`) et Twitter Card.
- `robots.txt` : autorise le public et les CSS/JS nécessaires au rendu Googlebot, interdit admin, API, brouillons ; ligne `Sitemap:` absolue. `sitemap.xml` : seulement les URL canoniques indexables.
- `noindex` (meta ou `X-Robots-Tag`) pour pages privées, utilitaires, résultats de recherche interne. Une variante à paramètres d'une page indexable garde sa canonique et ne reçoit pas de `noindex` (deux signaux contraires).
- Page d'erreur sur mesure obligatoire (404, et 500 si le serveur le permet) : vrai statut HTTP, gabarit du site (en-tête, pied de page, styles), `noindex`, liens vers l'accueil, les pages principales et le plan du site. Jamais la page par défaut du serveur ni une réponse texte. Multilingue : `hreflang` réciproques + `x-default`.
- Fil d'Ariane visible et `BreadcrumbList` en JSON-LD sur toute page sauf l'accueil.
- `llms.txt` à la racine : résumé Markdown, liens vers les pages réelles, mêmes faits que le site.
- Navigation par les agents IA (catégorie « agentic browsing » de Lighthouse) : chaque contrôle a un nom accessible, un formulaire se comprend sans le visuel (`label`, `name` unique, `autocomplete`), aucune action seulement en JavaScript quand un `a`, `button` ou `form` suffit, aucune injection tardive qui décale la page. Attributs WebMCP (`toolname` et `tooldescription`, toujours ensemble) : seulement par décision écrite, la norme est expérimentale.
- Liste d'objets (catalogue, produits, fiches) : de vrais liens `a` dans le HTML, pas seulement une fenêtre ou un bloc rempli en JavaScript.
- Socle dynamique, si `llms.txt` existe : l'accueil peut annoncer sa version Markdown (`Link: </llms.txt>; rel="alternate"; type="text/markdown"`, `Vary: Accept`) et la servir à un agent qui demande `Accept: text/markdown`.
- JSON-LD généré depuis la même source que le HTML visible. Un `@id` par nœud, un nœud par intention. Pas de `FAQPage` si le texte n'est pas affiché à l'identique. Aucun `Review`/`AggregateRating` sans avis publiés.

## Indexation : une seule règle

- Une page est indexable si et seulement si elle figure dans `sitemap.xml` ; sa canonique est alors exactement son `<loc>`, sans `noindex`. Toute autre page servie porte `noindex` (balise ou `X-Robots-Tag`). Une variante (paramètre, `index.html`, ancienne adresse) garde la canonique de la page qu'elle répète.
- `robots.txt` n'interdit jamais une URL du sitemap ni une page en `noindex` : une page interdite n'est plus explorée, son `noindex` n'est jamais lu. Interdire seulement ce qui ne doit pas être exploré du tout (admin, API, dossiers techniques).
- `robots.txt` déclare l'usage du contenu par les IA (`Content-Signal: search=yes, ai-input=yes, ai-train=no` par défaut) ; la valeur de `ai-train` se tranche à l'interview.

## SEO passif : le build fait le travail

Le référencement de fond ne dépend pas d'une action manuelle à chaque page : il est produit par le build depuis les données, et un contrôle échoue s'il manque.

- `title` et description générés par un patron depuis les données de la page (avec repli plus court si le patron dépasse la limite), jamais laissés au gabarit par défaut. Unicité contrôlée sur tout le site.
- `sitemap.xml`, `llms.txt`, JSON-LD et fil d'Ariane générés depuis la liste des routes. `lastmod` : date réelle de la dernière modification du contenu (historique Git), jamais la date du build ; absente plutôt que fausse.
- Maillage : chaque page indexable reçoit au moins un lien d'une autre page dans le corps ou la navigation (le sitemap ne compte pas). Une fiche renvoie vers ses voisines et vers sa rubrique.
- Une page par intention de recherche réelle (la question que le public tape), décidée à l'interview puis ajustée avec les données de Search Console. Pas de page satellite ni de variante par ville sans contenu propre.
- Activité locale : nom, adresse et téléphone identiques partout (une source de données), `LocalBusiness` avec `geo` et horaires affichés, fiche d'établissement Google tenue par le client (action humaine, notée au mode d'emploi).
- Après mise en ligne : sitemap soumis à Google Search Console et Bing Webmaster Tools (Bing alimente aussi des assistants IA), propriété vérifiée par DNS plutôt que par balise.

- Procédure : skill `web-seo-structured-data`.
