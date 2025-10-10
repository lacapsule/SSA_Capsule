# 🧩 Guide MiniMustache — pour développeurs Frontend Junior

---

## 🎯 Objectif

MiniMustache est un **mini moteur de templates**.
Il te permet d’afficher des données (venues du backend PHP) dans le HTML, **sans écrire de PHP dans tes fichiers**.

Tu utilises des balises comme `{{title}}` ou `{{#each articles}}` pour dire :
➡️ “mets ici la valeur de `title`”
➡️ “répète ce bloc pour chaque article”.

---

## 🧠 1. Les bases

### 🔹 Variable simple

```html
<p>{{title}}</p>
```

👉 affiche le texte de `title` (échappé = protégé contre le HTML dangereux).

### 🔹 Variable brute (HTML autorisé)

```html
{{{csrf_input}}}
```

👉 insère le contenu HTML sans l’échapper (ex. un `<input type="hidden">`).

⚠️ À n’utiliser **que** pour du contenu sûr, fourni par le backend (jamais par l’utilisateur).

---

## 🔹 Accès à des sous-données

```html
<p>{{user.name}}</p>
```

👉 si les données sont :

```json
{ "user": { "name": "Valentin" } }
```

alors le rendu donnera :

```html
<p>Valentin</p>
```

---

## 🔹 Boucles (listes d’éléments)

```html
<ul>
  {{#each articles}}
    <li>{{title}} — {{date}}</li>
  {{/each}}
</ul>
```

👉 Répète le bloc `<li>` pour chaque élément du tableau `articles`.

Si `articles` contient :

```json
[
  { "title": "Concert", "date": "21/10" },
  { "title": "Atelier", "date": "23/10" }
]
```

Résultat :

```html
<ul>
  <li>Concert — 21/10</li>
  <li>Atelier — 23/10</li>
</ul>
```

---

## 🔹 Conditions “si vrai”

```html
{{#isAuthenticated}}
  <a href="/logout">Déconnexion</a>
{{/isAuthenticated}}
```

👉 S’affiche uniquement si `isAuthenticated` vaut `true`.

---

## 🔹 Conditions “si faux” (inverse)

```html
{{^articles}}
  <p>Aucun article à afficher.</p>
{{/articles}}
```

👉 S’affiche si `articles` est vide ou n’existe pas.

---

## 🔹 Inclure d’autres fichiers (partials)

C’est comme importer un morceau de code HTML réutilisable :

```html
{{> partial:header }}
```

👉 Inclut le fichier `templates/partials/header.tpl.php`.

Tu peux aussi inclure :

* `{{> partial:footer }}`
* `{{> component:homepage/apropos }}`
* `{{> component:homepage/actualites }}`

💡 **But** : ne pas répéter le même code (header/footer/sections…).

---

## 🔹 Inclusion dynamique (selon variable)

```html
{{> @partialRef }}
```

👉 Si `partialRef = "partial:footer"`, ça inclut le footer automatiquement.

---

## 🔹 Valeur courante dans une boucle

```html
<ul>
  {{#each tags}}
    <li>{{.}}</li>
  {{/each}}
</ul>
```

👉 Si `tags = ["php","linux"]`, rendu :

```html
<ul>
  <li>php</li>
  <li>linux</li>
</ul>
```

---

## 🧩 2. Exemple complet

### Données envoyées par le backend

```json
{
  "showHeader": true,
  "showFooter": true,
  "articles": [
    { "date": "2025-10-21", "time": "18:30", "title": "Conférence", "summary": "Présentation du projet" }
  ],
  "str": { "no_upcoming_articles": "Aucun événement à venir" },
  "csrf_input": "<input type='hidden' name='_csrf' value='abc123'>"
}
```

### Template :

```html
{{#showHeader}}
  {{> partial:header }}
{{/showHeader}}

<div class="articles">
  {{^articles}}
    <p>{{str.no_upcoming_articles}}</p>
  {{/articles}}

  {{#each articles}}
    <article>
      <h3>{{title}}</h3>
      <p>{{summary}}</p>
      <time>{{date}} à {{time}}</time>
      <form method="post" action="/add">
        {{{csrf_input}}}
        <button>Ajouter à mon agenda</button>
      </form>
    </article>
  {{/each}}
</div>

{{#showFooter}}
  {{> partial:footer }}
{{/showFooter}}
```

---

## 🧭 3. Schéma de fonctionnement

```
[ PHP Controller ]
       │
       ▼
   Données JSON
       │
       ▼
[ MiniMustache ]
       │
   - remplace {{var}}
   - répète {{#each}} blocs
   - insère {{> partials }}
       │
       ▼
[ HTML final envoyé au navigateur ]
```

---

## 💡 4. Règles simples à retenir

| Type               | Syntaxe                     | Exemple                                         | Explication     |
| ------------------ | --------------------------- | ----------------------------------------------- | --------------- |
| Variable échappée  | `{{title}}`                 | `<p>Bonjour</p>` → `&lt;p&gt;Bonjour&lt;/p&gt;` | Sécurité        |
| Variable brute     | `{{{html}}}`                | `<p>Bonjour</p>`                                | Affiche le HTML |
| Section "si vrai"  | `{{#flag}}…{{/flag}}`       | Si `flag = true`, affiche le bloc               |                 |
| Section inverse    | `{{^flag}}…{{/flag}}`       | Si `flag = false`, affiche le bloc              |                 |
| Boucle each        | `{{#each items}}…{{/each}}` | Répète pour chaque item                         |                 |
| Inclusion          | `{{> partial:header }}`     | Insère un autre template                        |                 |
| Variable dynamique | `{{> @name}}`               | Inclusion selon data                            |                 |
| Sous-clé           | `{{user.name}}`             | Accès à un champ interne                        |                 |

---

## 🧪 5. Petits tests à faire

| Test         | Template                                | Données                 | Résultat               |
| ------------ | --------------------------------------- | ----------------------- | ---------------------- |
| Variable     | `<p>{{name}}</p>`                       | `{ "name": "Alex" }`    | `<p>Alex</p>`          |
| Booléen vrai | `{{#ok}}YES{{/ok}}`                     | `{ "ok": true }`        | `YES`                  |
| Booléen faux | `{{#ok}}YES{{/ok}}`                     | `{ "ok": false }`       | *(vide)*               |
| Inverse      | `{{^ok}}NO{{/ok}}`                      | `{ "ok": false }`       | `NO`                   |
| Liste        | `{{#each tags}}<li>{{.}}</li>{{/each}}` | `{ "tags": ["a","b"] }` | `<li>a</li><li>b</li>` |

---

## 🧱 6. Bonnes pratiques pour intégrateurs

✅ Utilise `{{{...}}}` **seulement** pour HTML sûr (comme les formulaires backend).
✅ Garde les templates **simples** : pas de logique, juste du markup.
✅ Utilise les partials (`partial:*`, `component:*`) pour éviter la duplication.
✅ Prévois les états vides avec `{{^liste}}...{{/liste}}`.
✅ Utilise des noms cohérents pour les fichiers (ex : `partial:header`, `component:homepage/actualites`).

---

## 🧩 7. Résumé visuel

```
{{> partial:header }}
      │
      ▼
[header.tpl.php]

{{#each articles}}
  → répète bloc
{{/each}}

{{^articles}}
  → affiche si vide
{{/articles}}

{{{csrf_input}}}
  → insère HTML pur
```

---

## ✅ 8. À retenir

* `{{...}}` → texte échappé (sécurité)
* `{{{...}}}` → HTML direct
* `{{#...}} ... {{/...}}` → si vrai
* `{{^...}} ... {{/...}}` → si faux
* `{{#each ...}} ... {{/each}}` → boucle
* `{{> partial:... }}` → inclusion

---

## 📦 Dossier conseillé

```
templates/
 ├─ pages/
 │   └─ home.tpl.php
 ├─ partials/
 │   ├─ header.tpl.php
 │   └─ footer.tpl.php
 └─ components/
     └─ homepage/
         ├─ apropos.tpl.php
         └─ actualites.tpl.php
```

---

## 🚀 En résumé

MiniMustache est ton ami pour **assembler du HTML dynamique** proprement.
Tu **n’écris pas de PHP**, tu te concentres sur **le balisage et la structure**.
Tout le contenu vient du backend, et tu peux te concentrer sur le design.

👉 Règle d’or :
**“1 template = 1 structure HTML propre, sans logique.”**

