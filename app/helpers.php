<?php
declare(strict_types=1);

/** Échappement HTML de toute valeur affichée (texte et attributs). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirection vers une adresse du site (303 par défaut, après un POST).
 * Chemins locaux seulement : « //autre-site » ou « /\autre-site » deviendraient une redirection ouverte, ils sont ramenés à « / ».
 */
function redirect(string $path, int $status = 303): never
{
    if (!str_starts_with($path, '/') || preg_match('#^/[/\\\\]#', $path) === 1 || preg_match('/[\r\n]/', $path) === 1) {
        $path = '/';
    }
    header('Location: ' . $path, true, $status);
    exit;
}

function is_local(): bool
{
    return ($_ENV['APP_ENV'] ?? 'production') === 'local';
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * Réglage de config/app.php en notation pointée : config('security.hsts'), config('app.name', 'Site').
 * Le fichier est lu une seule fois par requête.
 */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    $config ??= is_file(ROOT . '/config/app.php') ? (array) require ROOT . '/config/app.php' : [];
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Adresse d'un fichier de public/ suivie de sa date de modification, pour invalider le cache navigateur. */
function asset(string $path): string
{
    $file = ROOT . '/public' . $path;
    return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Partiel de templates/partials avec ses seules variables (portée isolée). @param array<string, mixed> $vars */
function partial(string $__partial, array $vars = []): void
{
    if (preg_match('#^[a-z0-9_-]+(/[a-z0-9_-]+)*$#', $__partial) !== 1) {
        throw new InvalidArgumentException("Nom de partiel invalide : $__partial");
    }
    // Nom de paramètre réservé : un partiel peut recevoir une variable $name (champ de formulaire) sans collision.
    extract($vars, EXTR_SKIP);
    require ROOT . "/templates/partials/$__partial.php";
}

/** Chemin demandé, décodé, sans la chaîne de requête. */
function current_path(): string
{
    return rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
}

/** Longueur en caractères UTF-8, sans dépendre de l'extension mbstring. */
function text_length(string $value): int
{
    return (int) preg_match_all('/./su', $value);
}

/**
 * Page d'erreur au statut donné (templates/errors/error.php), puis fin de la requête.
 * Texte seul si le gabarit manque ou échoue : une erreur ne doit jamais en cacher une autre.
 */
function abort(int $status, ?string $message = null): never
{
    http_response_code($status);
    header('Cache-Control: no-store');
    try {
        App\Core\View::render('errors/error', ['status' => $status, 'message' => $message]);
    } catch (Throwable $e) {
        error_log('Page d\'erreur indisponible : ' . $e->getMessage());
        header('Content-Type: text/plain; charset=utf-8');
        echo $message ?? 'Erreur ' . $status . '.';
    }
    exit;
}

/** Requête d'un script (envoi sans rechargement) qui attend du JSON : en-tête Accept: application/json. */
function wants_json(): bool
{
    return str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
}

/** Réponse JSON puis fin de la requête (contrat {ok, message, errors} des formulaires). */
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}
