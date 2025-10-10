
**Quelle méthode de `ResponseFactory`** utiliser — et **où** prendre la décision (contrôleur vs ailleurs).

# 1) Où décider ?

* **Contrôleur** : décide **du format de sortie** (HTML, JSON, fichier, redirect…) car c’est **métier/route-spécifique**.
* **Middlewares** : appliquent des **politiques transverses** (sécurité, erreurs, auth). Ils **ne choisissent pas** le format métier (sauf négociation de contenu si tu la mets ici).
* **Router** : décide **quelle action** est appelée (et valide la **méthode HTTP**).

Mnémotechnique : *format = contrôleur, politique = middleware, dispatch = router.*

---

# 2) Arbre de décision (dans le contrôleur)

```c
La réponse est-elle un document HTML ? ── oui → Res::html()
│
├─ Est-ce une API JSON ? ─────────────── oui → Res::json()
│   └─ Volume très gros / flux continu ? oui → Res::jsonStream()
│
├─ Est-ce un fichier à télécharger ? ─── oui → Res::download()
│   └─ Fichier volumineux/à la volée ?  oui → Res::downloadStream()
│
├─ Est-ce une redirection ? ──────────── oui → Res::redirect()
│
├─ Pas de contenu (DELETE ok, update sans body) ? ── oui → Res::empty(204)
│
└─ Erreur applicative normalisée ? ───── oui → Res::problem([...], 4xx/5xx)
   (ex : validation 422, business rule 409, etc.)
```

---

# 3) Règles HTTP (méthode → code → helper)

| Besoin                     | Méthode HTTP | Code conseillé       | Helper                                                                       |
| -------------------------- | ------------ | -------------------- | ---------------------------------------------------------------------------- |
| Lecture simple             | GET          | 200                  | `Res::html()` / `Res::json()`                                                |
| Création                   | POST         | **201** + `Location` | `Res::json()` + `->withHeader('Location', '/resource/123')`                  |
| Création puis redir. (web) | POST         | **303** vers GET     | `Res::redirect('/page', 303)`                                                |
| Mise à jour complète       | PUT          | 200/204              | `Res::json()` ou `Res::empty(204)`                                           |
| Mise à jour partielle      | PATCH        | 200/204              | idem                                                                         |
| Suppression                | DELETE       | **204**              | `Res::empty(204)`                                                            |
| Liste volumineuse          | GET          | 200                  | `Res::jsonStream($iter)`                                                     |
| Export                     | GET          | 200                  | `Res::download()` / `Res::downloadStream()`                                  |
| Non autorisé               | —            | **401**              | `Res::json(['error'=>'Unauthorized'], 401)` (ou redirect web via middleware) |
| Interdit                   | —            | **403**              | `Res::json(['error'=>'Forbidden'], 403)`                                     |
| Non trouvé                 | —            | **404**              | géré par `ErrorBoundary` → `Res::json([...],404)`                            |
| Conflit métier             | —            | **409**              | `Res::problem([...], 409)`                                                   |
| Entrée invalide            | —            | **422**              | `Res::problem([...], 422)`                                                   |

**Invariants**

* `POST` qui crée et renvoie un GET ensuite côté web : **303 See Other** (pas 302).
* `DELETE` réussi sans corps : **204 No Content**.
* Gros volumes : **stream** (jsonStream/downloadStream) → zéro `Content-Length` auto.

---

# 4) Seuils pour basculer en streaming (pratico-pratique)

* **JSON/CSV > ~256 KiB** ou **inconnu/illimité** → utilise `jsonStream()`/`downloadStream()`.
* Export **ligne à ligne** (DB cursor) → **toujours** en stream.
* Si tu dois compter/compresser **avant** d’envoyer, tu perds le stream (à éviter).

---

# 5) Exemples concrets

### HTML (SSR)

```php
use Capsule\Http\Factory\ResponseFactory as Res;

final class PageController {
    public function dashboard(Request $r): Response {
        $html = $this->renderer->render('dashboard.php', ['user' => $r->attributes['user'] ?? null]);
        return Res::html($html); // SecurityHeaders ajoutera CSP/nosniff
    }
}
```

### API list (petit volume)

```php
final class UserApi {
    public function list(Request $r): Response {
        $users = $this->repo->findAll(limit: 100);
        return Res::json(['data' => $users, 'count' => count($users)]);
    }
}
```

### API list (gros volume NDJSON)

```php
final class UserExportApi {
    public function ndjson(Request $r): Response {
        $iter = $this->repo->iterateAll(); // iterable de tableaux/DTO
        return Res::jsonStream($iter, fn($u) => ['id'=>$u->id, 'name'=>$u->name]);
    }
}
```

### Création (201 + Location)

```php
final class UserApi {
    public function create(Request $r): Response {
        $id = $this->svc->createUser($this->parseBody($r));
        return Res::json(['id'=>$id], 201)
            ->withHeader('Location', "/users/{$id}");
    }
}
```

### Redirect après POST (web)

```php
final class AuthController {
    public function login(Request $r): Response {
        $ok = $this->auth->login($r);
        return $ok ? Res::redirect('/dashboard', 303)
                   : Res::html($this->renderer->render('login.php', ['error'=>true]), 401);
    }
}
```

### Download stream (CSV)

```php
final class ExportController {
    public function csv(Request $r): Response {
        $rows = (function() {
            yield "id,name\n";
            foreach ($this->repo->iterateAll() as $u) {
                yield $u->id . ',' . $u->name . "\n";
            }
        })();
        return Res::downloadStream('users.csv', $rows, 'text/csv; charset=utf-8');
    }
}
```

### Erreur de validation uniforme (RFC 7807)

```php
final class UserApi {
    public function update(Request $r): Response {
        $errors = $this->validator->validate($r);
        if ($errors) {
            return Res::problem([
                'type'   => 'https://example.com/probs/validation',
                'title'  => 'Validation failed',
                'status' => 422,
                'errors' => $errors,
            ], 422);
        }
        // ...
    }
}
```
