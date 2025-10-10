# TL;DR

Bon squelette : clair, immuable, méfiant vis-à-vis des `X-Forwarded-*`. Les points à renforcer : parsing `Host` (IPv6 + port), normalisation du path (segments `.`/`..`), validation stricte des en-têtes (caractères de contrôle), typage/shape des tableaux, séparation `rawPath`/`decodedPath`, et politique explicite “proxy de confiance”.

---

# Plan

1. Sécurité & parsing HTTP : `Host`, `Port`, `Path`, `Headers`.
2. API & modélisation : champs, types, invariants.
3. Patch minimal (diff ciblé).
4. Risques & contre-arguments + checks mesurables.
5. Next actions.

---

# ASCII schema (flux d’extraction)

```
$_SERVER ──┐
           ├─ method (upper) ──> Request::method
           ├─ REQUEST_URI ─┐
           │               ├─ rawPath (origin-form) ─┐
           │               └─ query (via $_GET)      ├─> Request::{path,rawPath,query}
           ├─ HTTP_* / CONTENT_* ── sanitize ──> headers
           ├─ HTTPS / SERVER_NAME / SERVER_PORT ──┐
           │                                       ├─ parse Host [:port] (IPv4/IPv6)
           │                                       └─ scheme/host/port
           └─ php://input ──> rawBody
```

---

# Invariants (à garantir)

* `method ∈ [A-Z]+` et fait partie d’un set autorisé (optionnel).
* `rawPath` = `REQUEST_URI` sans `?query`. `path` = `rawPath` décodé *sans double-décodage* + normalisation segments.
* `path` ne contient **aucun** octet de contrôle `[\x00-\x1F\x7F]`.
* `headers` : clés en *case-insensitive*, valeurs sans CR/LF et sans C0 (sauf TAB si toléré).
* `host` = nom d’hôte **sans port** (IPv6 possible `[::1]` ➜ `::1`), `port` = entier (si absent ➜ null).
* `scheme ∈ {http, https}` indépendamment des en-têtes non fiables (proxy policy explicite ailleurs).
* `query` provient de `$_GET` (décodage standard) — pas de mélange avec `path`.

---

# Patch minimal / diff (centré sur robustesse)

```php
final class Request
{
    /** @param array<string,string> $headers
     *  @param array<string,scalar|array|null> $query
     *  @param array<string,string> $cookies
     *  @param array<string,mixed> $server
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly array $cookies,
        public readonly array $server,
        public readonly string $scheme = 'http',
        public readonly ?string $host = null,
        public readonly ?int $port = null,
        public readonly ?string $rawBody = null,
        public readonly ?string $rawPath = null, // NEW: conserve la forme originelle
    ) {}

    public static function fromGlobals(): self
    {
        $srv = $_SERVER;

        // 1) Méthode
        $method = strtoupper($srv['REQUEST_METHOD'] ?? 'GET');
        if (!preg_match('/^[A-Z]+$/', $method)) {
            $method = 'GET';
        }

        // 2) URI & path
        $uri = (string)($srv['REQUEST_URI'] ?? '/');
        $rawPath = strtok($uri, '?') ?: '/';

        // — sécurité bas niveau sur rawPath
        if ($rawPath === '' || str_contains($rawPath, "\0")) {
            $rawPath = '/';
        }

        // — décodage RFC3986 sans toucher '+'
        $decoded = rawurldecode($rawPath);

        // — normalisation segments (supprime /./ et résout /../ sans sortir de la racine)
        $parts = explode('/', $decoded);
        $stack = [];
        foreach ($parts as $seg) {
            if ($seg === '' || $seg === '.') {
                // ok (préserve leading slash par chaîne vide initiale)
            } elseif ($seg === '..') {
                array_pop($stack);
            } else {
                $stack[] = $seg;
            }
        }
        $path = '/' . implode('/', $stack);
        // compacter multiples slashes
        $path = preg_replace('#//+#', '/', $path) ?? $path;
        // supprimer octets de contrôle
        $path = preg_replace('/[\x00-\x1F\x7F]/', '', $path) ?? '/';

        // 3) En-têtes
        $headers = [];
        foreach ($srv as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = self::normalizeHeaderName(substr($k, 5));
                $headers[$name] = self::sanitizeHeaderValue((string)$v);
            } elseif (in_array($k, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = self::normalizeHeaderName($k);
                $headers[$name] = self::sanitizeHeaderValue((string)$v);
            }
        }
        // Authorization peut arriver via REDIRECT_HTTP_AUTHORIZATION
        if (!isset($headers['Authorization']) && isset($srv['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = self::sanitizeHeaderValue((string)$srv['REDIRECT_HTTP_AUTHORIZATION']);
        }

        // 4) Scheme / host / port (sans confiance proxy)
        $https  = ($srv['HTTPS'] ?? '') && strtolower((string)$srv['HTTPS']) !== 'off';
        $scheme = $https ? 'https' : 'http';

        // Host header peut contenir le port et IPv6
        $hostHeader = $headers['Host'] ?? ($srv['SERVER_NAME'] ?? null);
        [$host, $port] = self::parseHostAndPort($hostHeader, isset($srv['SERVER_PORT']) ? (int)$srv['SERVER_PORT'] : null, $scheme);

        // 5) Raw body
        $rawBody = file_get_contents('php://input') ?: null;

        return new self(
            method:  $method,
            path:    $path,
            query:   $_GET,
            headers: $headers,
            cookies: $_COOKIE,
            server:  $srv,
            scheme:  $scheme,
            host:    $host,
            port:    $port,
            rawBody: $rawBody,
            rawPath: $rawPath,
        );
    }

    /** @internal */
    private static function normalizeHeaderName(string $key): string
    {
        $name = str_replace('_', ' ', strtoupper($key));
        $name = ucwords(strtolower($name));
        $name = str_replace(' ', '-', $name);
        return $name;
    }

    private static function sanitizeHeaderValue(string $v): string
    {
        // Empêche l'injection + retire tout contrôle C0 (garde TAB ? -> ici non)
        $v = str_replace(["\r", "\n"], '', $v);
        $v = preg_replace('/[\x00-\x1F\x7F]/', '', $v) ?? '';
        return $v;
    }

    /** @return array{0:?string,1:?int} */
    private static function parseHostAndPort(?string $hostHeader, ?int $serverPort, string $scheme): array
    {
        if ($hostHeader === null || $hostHeader === '') {
            return [null, $serverPort];
        }

        $h = trim($hostHeader);

        // IPv6: "[::1]:8080" ou "[2001:db8::1]"
        if (str_starts_with($h, '[')) {
            $end = strpos($h, ']');
            if ($end === false) {
                return [null, $serverPort]; // invalide
            }
            $ipv6 = substr($h, 1, $end - 1);
            $rest = substr($h, $end + 1);
            $p = null;
            if (str_starts_with($rest, ':')) {
                $p = (int)substr($rest, 1);
            }
            return [$ipv6, $p ?: $serverPort];
        }

        // IPv4/hostname avec éventuel :port
        $host = $h;
        $port = $serverPort;
        $pos = strrpos($h, ':');
        if ($pos !== false && strpos($h, ':', $pos + 1) === false) {
            $maybePort = substr($h, $pos + 1);
            if (ctype_digit($maybePort)) {
                $host = substr($h, 0, $pos);
                $port = (int)$maybePort;
            }
        }

        // validation grossière de host (LDH) — on autorise aussi IPv4
        if ($host === '' || preg_match('/[^\w\.\-]/', $host)) {
            return [null, $port];
        }

        // normalisation IDNA (optionnelle si extension présente)
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false) {
                $host = $ascii;
            }
        }

        return [$host, $port];
    }
}
```

---

# Pourquoi ces changements (raisonnement système)

* **`Host` propre** : RFC autorise IPv6 + port dans `Host`. Sans parsing, `host` peut contenir `:port` et fausser la logique (ACL, liens absolus).
* **Normalisation segments** : résoudre `.`/`..` évite des routes ambiguës et bloque des tentatives naïves de traversal quand on mappe plus tard vers un FS (même si ici c’est *routing-only*).
* **Contrôles C0** : interdisent CRLF injection et d’autres vecteurs dans les logs/reponses.
* **`rawPath` vs `path`** : utile pour *logging exact* / signatures / reproduction de bugs tout en fournissant un `path` nettoyé pour le routeur.
* **Typage arrays** : documentation de surface pour éviter les erreurs d’API (préparer des shapes si tu passes à Psalm/PhpStan strict).
* **`Authorization` fallback** : cas fréquent sous Apache/CGI.

---

# Menace (mini threat model) & abus à considérer

* **CRLF injection** via en-têtes → neutralisé par suppression `\r\n` + C0.
* **Host header poisoning** (cache poisoning, absolute URLs) → parsing strict + validation LDH + option *trust proxy* à implémenter ailleurs.
* **Path smuggling** (`%2F`, `//`, `./..`) → décodage contrôlé + normalisation segments + compactage slashes.
* **Proxy confusion** → par défaut *deny-by-default* (`X-Forwarded-*` ignorés). Introduire une **liste blanche** d’IP proxies si nécessaire.

---

# Contrepoints / limites

* **`rawurldecode`** : s’il y a des séquences invalides, PHP remplace quand même octets ; ici on garde `rawPath` pour forensic.
* **IDNA** : `idn_to_ascii` dépend de l’extension intl. Le code reste robuste sans.
* **Headers dupliqués** : HTTP autorise plusieurs occurrences (ex: `Cookie`, `Accept`). Ici, on *écrase*. Option : stocker `array<string,string|string[]>` pour certains noms.

---

# Checks mesurables (qualité / perfs)

* Fuzz tests (100k cas) sur `parseHostAndPort` (IPv4/IPv6/ports/IDNA) → 0 échecs.
* Bench : `fromGlobals()` ≤ 5µs p95 hors I/O (mesuré via `hrtime()` en environnement local).
* Property-based tests : `normalize(path)` idempotente (`norm(norm(p)) == norm(p)`).
* Invariants unit-testés : absence de C0 dans `headers` et `path`.

---

# Next actions

1. Ajouter une **politique proxy** (`Request::fromTrustedProxy($ips)` ou un middleware) pour activer `X-Forwarded-*` de façon *opt-in*.
2. Décider si on supporte **headers multiples** (liste blanche : `Accept`, `Cache-Control`, `Set-Cookie` côté réponse).
3. Exposer une API `authority()` qui renvoie `host:port_effectif` (port implicite 80/443 caché) pour construire des URLs absolues en toute sécurité.
4. (Option) Intégrer Psalm/PhpStan : activer generics `array-shape` sur `query/cookies/server`.

**Assumptions** : pas de besoin immédiat d’IDNA strict, pas de confiance proxy, routing path-based.
**TODO (faible confiance)** : définir la liste précise des caractères permis dans les *header names* si tu acceptes des noms non standards.
