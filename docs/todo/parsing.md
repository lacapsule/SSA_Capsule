# FUTUR : PARSING

# 1) Le pipeline standard (pourquoi & comment)

1. **Lexer**
   Tu lis la chaîne du template et tu la coupes en **tokens** (TEXT, VAR, RAW, OPEN_SECTION(each|if), CLOSE_SECTION, PARTIAL…).
   But : plus de regex fragiles, tu obtiens un flux typé, prêt pour une grammaire.

2. **Parser (pile → AST)**
   Tu parcours les tokens en maintenant une **pile** d’ouvertures (`#each`, `#if`, etc.) et tu construis un **AST** (arbre syntaxique) correctement **appareillé**.
   Bénéfice : les imbrications sont **canoniquement** gérées (c’est exactement le problème que tu as).
   → C’est ce que décrit Twig : *lexer → parser → compiler* ; le parser produit un arbre de nœuds (AST). ([twig.symfony.com][1])

3. **Compile (en PHP)**
   Tu transformes l’AST en **PHP natif** (une classe/closure avec `render(array $ctx): string`).
   Bénéfice : au runtime, tu **exécutes du PHP** (pas des regex), donc c’est rapide et prévisible.
   → Blade fait exactement ça : *« all Blade templates are compiled into plain PHP code and cached until they are modified »*. ([Laravel][2])
   → Twig aussi compile ses templates et les **met en cache** pour éviter de reparser. ([NIFT][3])
   → Mustache a un **tokenizer + parser** vers un parse tree, puis rendu. ([GitLab][4])

4. **Cache**
   Tu stockes le résultat compilé (fichier PHP) sous `var/cache/views/<sha1>.php` et tu n’y retouches **que si** le template source a changé (mtime/hash).
   → Pratique identique à Blade (compilation à la demande + `view:cache` pour précompiler en prod). ([Laravel][5])
   → Twig parle clairement de *compilation cache directory*. ([NIFT][3])

**Résultat** : imbrications fiables, erreurs syntaxiques claires (au parse), exécution rapide (PHP compilé), et **zéro regex cascade**.


* **Intégration** : pas de sidecar/service externe ; déploiement simple, OPcache tire déjà parti du PHP compilé.
* **Perf** : compilation **une fois** (ou au déploiement), exécution en PHP pur → sur le hot path, c’est essentiellement des concaténations et des `htmlspecialchars`.
* **Maintenance** : tout est dans ton repo ; pas de toolchain Go/Node à embarquer.

---

# 3) Implémentation Capsule (plan minimal, robuste)

## Contrats (non-négociables)

* **Entrée** : string template UTF-8 ; **Sortie** : string HTML.
* **Erreurs** : `TemplateSyntaxError` si balise non fermée / ordre invalide.
* **Sécurité** : `{{var}}` échappée (`ENT_QUOTES|ENT_SUBSTITUTE`), `{{{raw}}}` non échappée.
* **Imbrications** : `#each`, `#if`, `^else?` (si tu l’ajoutes) appariées via pile.
* **Cache** : hit si `sha1(source)` connu **et** `mtime(compiled) >= mtime(source)`.

## Étapes concrètes

1. **Lexer** (100–150 lignes)

   * Délimiteurs `{{` `}}`, triple `{{{` `}}}`, sections `{{#…}}/{{/…}}`, inverses `{{^…}}`, partiels `{{>…}}`, VAR `{{ path }}`.
   * Retourne un `TokenStream` (objets simples).

2. **Parser** (150–200 lignes)

   * Parcourt le flux, pousse `OPEN_SECTION` sur **pile**, dépile à `CLOSE_SECTION`.
   * Construit un AST avec nœuds : `TextNode`, `VarNode`, `RawNode`, `SectionEachNode`, `SectionIfNode`, `PartialNode`, etc.

3. **Compiler PHP** (150–250 lignes)

   * Visiteur d’AST qui émet un **PHP string** (ou `PhpEmitter` qui écrit dans un buffer).
   * Génère une **classe finale** ou une **closure** :

     * `render(array $ctx): string`
     * `resolve($ctx, 'a.b.c')` pour l’accès dot-notation.
     * Chaque `VarNode` devient `.$this->e($this->get($ctx,'a.b')).`
     * Chaque `SectionEachNode` génère un `foreach`.

4. **Cache**

   * Fichier `var/cache/views/<sha1>.php` contenant la classe compilée (`return new CompiledTemplate();`).
   * Loader : si cache présent et frais, `require` et exécute. Sinon, compile et écrit.

## Invariants perf

* **Compilation** : O(n) sur la longueur du template, **hors hot-path**.
* **Rendu** : O(n) pour la taille de sortie ; **pas** de regex ; essentiellement concat de strings.
* **Mémoire** : proportionnelle à la sortie ; zéro structure géante provisoire côté rendu.

---

# 4) Quand garder ton “Option B” (regex ré-entrantes) ?

* Comme **pansement** tant que le parseur n’est pas prêt.
* Dès que tu as l’AST + compile, tu supprimes ce code : il devient inutile.

---

# 5) Références (ce que font les moteurs majeurs)

* **Twig** : *lexer → parser (AST) → compiler (PHP) → cache* (doc “Internals”). ([twig.symfony.com][1])
* **Twig (cache de compilation)** : répertoire de cache pour éviter re-parse/compile. ([NIFT][3])
* **Blade** : “compiled into plain PHP and cached until modified” (+ compilation à la demande et `view:cache`). ([Laravel][2])
* **Mustache** : spécification + implémentations avec tokenizer & parser vers un parse tree. ([Mustache][6])

---

[1]: https://twig.symfony.com/doc/3.x/internals.html?utm_source=chatgpt.com "Twig Internals - Documentation - Twig PHP - Symfony"
[2]: https://laravel.com/docs/12.x/blade?utm_source=chatgpt.com "Blade Templates - Laravel 12.x - The PHP Framework For ..."
[3]: https://www.nift.ac.in/kangra/sites/kangra/files/2017-10/Twig.pdf?utm_source=chatgpt.com "The Twig Book"
[4]: https://gitlab.c3sl.ufpr.br/cdn/slx-admin/-/blob/abb77df02a814759ccd92f560237b05cf7af241e/Mustache/Parser.php?utm_source=chatgpt.com "Mustache/Parser.php"
[5]: https://laravel.com/docs/12.x/views?utm_source=chatgpt.com "Views - Laravel 12.x - The PHP Framework For Web Artisans"
[6]: https://mustache.github.io/mustache.5.html?utm_source=chatgpt.com "Logic-less templates. - mustache(5)"
