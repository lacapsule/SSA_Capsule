<?php
declare(strict_types=1);

namespace App\Sauvegarde;

/**
 * Sauvegardes chiffrées d'une base SQLite : création, liste, rétention, vérification, restauration, copie hors serveur.
 *
 *   base ── VACUUM INTO (ou API backup) ──> copie cohérente ── gzip ──> chiffrement libsodium par blocs ──> sauvegarde-AAAAMMJJ-HHMMSS-<type>.sqlite.gz.enc
 *
 * Chiffrement : crypto_secretstream_xchacha20poly1305, blocs de 64 Kio. Chaque bloc est authentifié, leur ordre aussi,
 * et le dernier porte la marque FINAL : un fichier modifié d'un seul octet, tronqué ou rallongé est refusé.
 *
 * Format du fichier .enc (version 1) :
 *   "SAUVCHF" + octet de version (0x01)                    8 octets
 *   longueur de l'en-tête JSON (32 bits gros-boutiste)      4 octets
 *   en-tête JSON {format, cree_le, type, schema}            lisible sans la clé, authentifié (donnée additionnelle du 1er bloc)
 *   en-tête secretstream                                    24 octets
 *   blocs : longueur (32 bits) + bloc chiffré (marque FINAL sur le dernier)
 *
 * Clé : BACKUP_KEY du .env (64 caractères hexadécimaux), différente d'APP_KEY, jamais dans le code ni dans la sauvegarde.
 * Classe autonome (aucune dépendance au socle) : Sauvegarde::depuisConfig() la relie à gabarits/socle-php.
 */
final class Sauvegarde
{
    public const MAGIQUE = "SAUVCHF\x01";
    public const FORMAT = 1;
    private const BLOC = 65536;
    private const CLE_OCTETS = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES;

    /** @var array{prefixe:string, retention_jours:int, retention_mois:int, tables_requises:list<string>, tables_comptees:list<string>, exiger_chiffrement:bool} */
    private array $reglages;

    /**
     * @param string $base chemin absolu de la base SQLite à sauvegarder (et à remplacer lors d'une restauration)
     * @param string $dossier dossier des sauvegardes (hors de la racine web)
     * @param string|null $cle clé binaire de 32 octets (Sauvegarde::cle()), null : sauvegarde non chiffrée, refusée si exiger_chiffrement
     * @param array<string, mixed> $reglages voir config/sauvegarde.php
     */
    public function __construct(private string $base, private string $dossier, private ?string $cle, array $reglages = [])
    {
        if ($cle !== null && strlen($cle) !== self::CLE_OCTETS) {
            throw new \InvalidArgumentException('Clé de sauvegarde : 32 octets attendus.');
        }
        $prefixe = (string) ($reglages['prefixe'] ?? 'sauvegarde');
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $prefixe) !== 1) {
            throw new \InvalidArgumentException('Préfixe de sauvegarde : lettres minuscules, chiffres et tirets seulement.');
        }
        $this->reglages = [
            'prefixe' => $prefixe,
            'retention_jours' => max(1, (int) ($reglages['retention_jours'] ?? 30)),
            'retention_mois' => max(0, (int) ($reglages['retention_mois'] ?? 12)),
            'tables_requises' => array_values(array_map('strval', (array) ($reglages['tables_requises'] ?? []))),
            'tables_comptees' => array_values(array_map('strval', (array) ($reglages['tables_comptees'] ?? []))),
            'exiger_chiffrement' => (bool) ($reglages['exiger_chiffrement'] ?? true),
        ];
        $this->dossier = rtrim($dossier, '/');
    }

    /**
     * Instance réglée par le socle : config('sauvegarde.*'), BACKUP_KEY et APP_KEY du .env, base SQLite de DB_DSN.
     * Une base MySQL ou MariaDB n'est pas prise en charge (voir NOTES.md).
     */
    public static function depuisConfig(): self
    {
        $cfg = (array) config('sauvegarde', []);
        $base = (string) ($cfg['base'] ?? '');
        if ($base === '') {
            $dsn = (string) ((require ROOT . '/config/database.php')['dsn'] ?? '');
            if (!str_starts_with($dsn, 'sqlite:')) {
                throw new \RuntimeException('Sauvegarde : seule une base SQLite est prise en charge (DB_DSN commence par sqlite:).');
            }
            $base = substr($dsn, 7);
        }
        $dossier = (string) ($cfg['dossier'] ?? ROOT . '/data/sauvegardes');
        $cle = self::cle((string) ($_ENV['BACKUP_KEY'] ?? ''), (string) ($_ENV['APP_KEY'] ?? ''));
        $cfg['exiger_chiffrement'] ??= !is_local();
        return new self($base, $dossier, $cle, $cfg);
    }

    // ------------------------------------------------------------------
    // Clé
    // ------------------------------------------------------------------

    /**
     * Lecture stricte de BACKUP_KEY : vide => null, sinon exactement 64 caractères hexadécimaux, différente d'APP_KEY.
     * Une seule fonction de lecture de la clé (une clé mal recopiée n'est jamais tronquée en silence).
     */
    public static function cle(string $hex, string $appKey = ''): ?string
    {
        $hex = trim($hex);
        if ($hex === '') {
            return null;
        }
        if (preg_match('/^[0-9a-f]{64}$/', $hex) !== 1) {
            throw new \RuntimeException('BACKUP_KEY invalide : 64 caractères hexadécimaux attendus (php scripts/sauvegarde.php cle).');
        }
        if ($appKey !== '' && hash_equals(strtolower(trim($appKey)), $hex)) {
            throw new \RuntimeException('BACKUP_KEY doit être différente d\'APP_KEY.');
        }
        return (string) hex2bin($hex);
    }

    /** Nouvelle clé, en hexadécimal, à écrire dans BACKUP_KEY. */
    public static function genererCle(): string
    {
        return sodium_bin2hex(sodium_crypto_secretstream_xchacha20poly1305_keygen());
    }

    public function estChiffree(): bool
    {
        return $this->cle !== null;
    }

    public function dossier(): string
    {
        return $this->dossier;
    }

    public function base(): string
    {
        return $this->base;
    }

    // ------------------------------------------------------------------
    // Création
    // ------------------------------------------------------------------

    /**
     * Crée une sauvegarde de la base. Verrou exclusif : deux sauvegardes ou restaurations ne se croisent jamais.
     * @return array{fichier:string, nom:string, taille:int, chiffre:bool, supprimes:list<string>}
     */
    public function creer(string $type = 'manuelle', bool $retention = true): array
    {
        return $this->sousVerrou(fn (): array => $this->creerSansVerrou($type, $retention));
    }

    /** @return array{fichier:string, nom:string, taille:int, chiffre:bool, supprimes:list<string>} */
    private function creerSansVerrou(string $type, bool $retention): array
    {
        $type = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($type)), '-') ?: 'manuelle';
        if ($this->cle === null && $this->reglages['exiger_chiffrement']) {
            throw new \RuntimeException('Sauvegarde refusée : BACKUP_KEY absente du .env. En production, une sauvegarde est toujours chiffrée '
                . '(php scripts/sauvegarde.php cle, puis conserver la clé hors du serveur).');
        }
        if (!is_file($this->base)) {
            throw new \RuntimeException('Base introuvable : ' . $this->base);
        }
        $this->preparerDossier();
        $horodatage = date('Ymd-His');
        $racine = sprintf('%s/%s-%s-%s', $this->dossier, $this->reglages['prefixe'], $horodatage, $type);
        // Deux sauvegardes dans la même seconde : suffixe numéroté, rien n'est écrasé.
        for ($i = 2; (glob($racine . '.sqlite.gz*') ?: []) !== []; $i++) {
            $racine = sprintf('%s/%s-%s-%s-%d', $this->dossier, $this->reglages['prefixe'], $horodatage, $type, $i);
        }
        $copie = $racine . '.sqlite.tmp';
        $gz = $racine . '.sqlite.gz';
        $final = $this->cle !== null ? $gz . '.enc' : $gz;
        $masque = umask(0077); // fichiers créés en 0600
        try {
            $schema = $this->copieCoherente($copie);
            self::gzip($copie, $gz);
            unlink($copie);
            if ($this->cle !== null) {
                self::chiffrer($gz, $final, $this->cle, ['format' => self::FORMAT, 'cree_le' => date('c'), 'type' => $type, 'schema' => $schema]);
                unlink($gz);
            }
            chmod($final, 0600);
        } finally {
            umask($masque);
            foreach ([$copie, $this->cle !== null ? $gz : ''] as $reste) {
                if ($reste !== '' && is_file($reste)) {
                    unlink($reste);
                }
            }
        }
        $supprimes = $retention ? $this->appliquerRetention() : [];
        return ['fichier' => $final, 'nom' => basename($final), 'taille' => (int) filesize($final), 'chiffre' => $this->cle !== null, 'supprimes' => $supprimes];
    }

    /**
     * Copie cohérente même si quelqu'un écrit en même temps : VACUUM INTO (SQLite 3.27+, sans les pages libres),
     * sinon l'API backup de l'extension sqlite3. @return string dernière migration appliquée (schema_migrations)
     */
    private function copieCoherente(string $cible): string
    {
        $pdo = self::ouvrir($this->base);
        $schema = self::versionSchema($pdo);
        try {
            $pdo->exec('VACUUM INTO ' . $pdo->quote($cible));
            return $schema;
        } catch (\PDOException $e) {
            $pdo = null;
            if (!class_exists(\SQLite3::class)) {
                throw new \RuntimeException('Copie cohérente impossible (SQLite antérieur à 3.27 et extension sqlite3 absente) : ' . $e->getMessage(), 0, $e);
            }
        }
        $source = new \SQLite3($this->base, SQLITE3_OPEN_READONLY);
        $source->busyTimeout(5000);
        $dest = new \SQLite3($cible);
        $ok = $source->backup($dest);
        $dest->close();
        $source->close();
        if (!$ok) {
            throw new \RuntimeException('Copie cohérente impossible (API backup de SQLite).');
        }
        return $schema;
    }

    // ------------------------------------------------------------------
    // Chiffrement et déchiffrement en flux
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $meta en-tête lisible (format, date, type, schéma), authentifié */
    public static function chiffrer(string $source, string $dest, string $cle, array $meta): void
    {
        $json = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $entete = self::MAGIQUE . pack('N', strlen($json)) . $json;
        [$etat, $enteteFlux] = sodium_crypto_secretstream_xchacha20poly1305_init_push($cle);
        $in = fopen($source, 'rb');
        $out = fopen($dest, 'xb');
        if ($in === false || $out === false) {
            throw new \RuntimeException('Chiffrement : fichier inaccessible.');
        }
        try {
            fwrite($out, $entete . $enteteFlux);
            $premier = true;
            // Un bloc lu d'avance pour savoir lequel est le dernier (marque FINAL), même pour un fichier vide.
            $bloc = (string) fread($in, self::BLOC);
            do {
                $suivant = feof($in) ? '' : (string) fread($in, self::BLOC);
                $fin = $suivant === '' && feof($in);
                $chiffre = sodium_crypto_secretstream_xchacha20poly1305_push(
                    $etat,
                    $bloc,
                    $premier ? $entete : '',
                    $fin ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE
                );
                fwrite($out, pack('N', strlen($chiffre)) . $chiffre);
                $premier = false;
                $bloc = $suivant;
            } while (!$fin);
        } finally {
            fclose($in);
            fclose($out);
            sodium_memzero($etat);
        }
    }

    /** En-tête JSON d'un fichier .enc, lisible sans la clé (non garanti tant que le fichier n'est pas déchiffré). @return array<string, mixed>|null */
    public static function lireEntete(string $fichier): ?array
    {
        $f = @fopen($fichier, 'rb');
        if ($f === false) {
            return null;
        }
        $magique = (string) fread($f, 8);
        $l = unpack('N', (string) fread($f, 4));
        $n = is_array($l) ? (int) $l[1] : 0;
        $json = $magique === self::MAGIQUE && $n > 0 && $n < 65536 ? (string) fread($f, $n) : '';
        fclose($f);
        $meta = json_decode($json, true);
        return is_array($meta) ? $meta : null;
    }

    /**
     * Déchiffre un fichier .enc vers $dest. Exception si le fichier est modifié, tronqué, rallongé ou si la clé est fausse ;
     * dans ce cas rien n'est laissé sur le disque. @return array<string, mixed> en-tête du fichier
     */
    public static function dechiffrer(string $source, string $dest, string $cle): array
    {
        $in = @fopen($source, 'rb');
        if ($in === false) {
            throw new \RuntimeException('Fichier de sauvegarde introuvable.');
        }
        $illisible = 'Sauvegarde illisible : fichier modifié ou abîmé, ou mauvaise clé BACKUP_KEY.';
        $out = null;
        try {
            $magique = (string) fread($in, 8);
            if (substr($magique, 0, 7) !== substr(self::MAGIQUE, 0, 7)) {
                throw new \RuntimeException('Ce fichier n\'est pas une sauvegarde chiffrée (format inconnu).');
            }
            if ($magique !== self::MAGIQUE) {
                throw new \RuntimeException('Version de format de sauvegarde non prise en charge (octet ' . ord($magique[7] ?? "\0") . ').');
            }
            $l = unpack('N', (string) fread($in, 4));
            $n = is_array($l) ? (int) $l[1] : 0;
            if ($n <= 0 || $n > 65535) {
                throw new \RuntimeException($illisible);
            }
            $json = (string) fread($in, $n);
            $meta = json_decode($json, true);
            if (!is_array($meta) || (int) ($meta['format'] ?? 0) !== self::FORMAT) {
                throw new \RuntimeException($illisible);
            }
            $entete = $magique . pack('N', $n) . $json;
            $enteteFlux = (string) fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            if (strlen($enteteFlux) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
                throw new \RuntimeException($illisible);
            }
            $etat = sodium_crypto_secretstream_xchacha20poly1305_init_pull($enteteFlux, $cle);
            $out = fopen($dest, 'xb');
            if ($out === false) {
                throw new \RuntimeException('Impossible d\'écrire le fichier déchiffré.');
            }
            $premier = true;
            $finVue = false;
            while (true) {
                $lb = (string) fread($in, 4);
                if ($lb === '') {
                    break;
                }
                $taille = strlen($lb) === 4 ? (int) unpack('N', $lb)[1] : 0;
                // Données après la marque FINAL, ou longueur absurde : refus.
                if ($finVue || $taille <= SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES - 1
                    || $taille > self::BLOC + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES) {
                    throw new \RuntimeException($illisible);
                }
                $chiffre = (string) fread($in, $taille);
                $r = strlen($chiffre) === $taille
                    ? sodium_crypto_secretstream_xchacha20poly1305_pull($etat, $chiffre, $premier ? $entete : '')
                    : false;
                if ($r === false) {
                    throw new \RuntimeException($illisible);
                }
                [$clair, $marque] = $r;
                fwrite($out, $clair);
                $premier = false;
                $finVue = $marque === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            }
            if (!$finVue) {
                throw new \RuntimeException('Sauvegarde incomplète (fichier tronqué).');
            }
            fclose($out);
            $out = null;
            return $meta;
        } catch (\Throwable $e) {
            if (is_resource($out)) {
                fclose($out);
            }
            if (is_file($dest)) {
                unlink($dest);
            }
            throw $e;
        } finally {
            fclose($in);
        }
    }

    // ------------------------------------------------------------------
    // Liste et rétention
    // ------------------------------------------------------------------

    /**
     * Sauvegardes présentes, de la plus récente à la plus ancienne.
     * @return list<array{fichier:string, nom:string, date:string, type:string, taille:int, chiffre:bool}>
     */
    public function lister(): array
    {
        $motif = '/^' . preg_quote($this->reglages['prefixe'], '/') . '-(\d{8})-(\d{6})-([a-z0-9-]+)\.sqlite\.gz(\.enc)?$/';
        $liste = [];
        foreach (glob($this->dossier . '/' . $this->reglages['prefixe'] . '-*.sqlite.gz*') ?: [] as $f) {
            if (preg_match($motif, basename($f), $m) !== 1) {
                continue;
            }
            $d = \DateTimeImmutable::createFromFormat('Ymd His', $m[1] . ' ' . $m[2]);
            $liste[] = [
                'fichier' => $f,
                'nom' => basename($f),
                'date' => $d ? $d->format('Y-m-d H:i:s') : '',
                'type' => (string) preg_replace('/-\d+$/', '', $m[3]),
                'taille' => (int) filesize($f),
                'chiffre' => ($m[4] ?? '') === '.enc',
            ];
        }
        usort($liste, static fn (array $a, array $b): int => strcmp($b['date'] . $b['nom'], $a['date'] . $a['nom']));
        return $liste;
    }

    /** @return array{fichier:string, nom:string, date:string, type:string, taille:int, chiffre:bool}|null */
    public function plusRecente(): ?array
    {
        return $this->lister()[0] ?? null;
    }

    /**
     * Chemin d'une sauvegarde désignée par son nom (formulaire, ligne de commande) : nom du motif seulement,
     * jamais de chemin ni de « .. » (un nom venu d'un formulaire n'entre pas tel quel dans un chemin de fichier).
     */
    public function chemin(string $nom): string
    {
        foreach ($this->lister() as $s) {
            if (hash_equals($s['nom'], $nom)) {
                return $s['fichier'];
            }
        }
        throw new \RuntimeException('Sauvegarde inconnue : ' . basename($nom));
    }

    /**
     * Rétention : tout ce qui a moins de retention_jours jours, plus la première sauvegarde de chacun des
     * retention_mois derniers mois (mois courant compris). La plus récente n'est jamais supprimée.
     * @return list<string> noms des fichiers supprimés
     */
    public function appliquerRetention(?int $maintenant = null): array
    {
        $maintenant ??= time();
        $liste = $this->lister();
        $limite = date('Y-m-d H:i:s', $maintenant - $this->reglages['retention_jours'] * 86400);
        $mois = [];
        $debut = new \DateTimeImmutable(date('Y-m-01', $maintenant));
        for ($i = 0; $i < $this->reglages['retention_mois']; $i++) {
            $mois[$debut->modify("-$i month")->format('Y-m')] = true;
        }
        $garder = [];
        if ($liste !== []) {
            $garder[$liste[0]['fichier']] = true;
        }
        $premiers = [];
        foreach (array_reverse($liste) as $s) { // de la plus ancienne à la plus récente
            if ($s['date'] >= $limite) {
                $garder[$s['fichier']] = true;
            }
            $m = substr($s['date'], 0, 7);
            if (isset($mois[$m]) && !isset($premiers[$m])) {
                $premiers[$m] = true;
                $garder[$s['fichier']] = true;
            }
        }
        $supprimes = [];
        foreach ($liste as $s) {
            if (!isset($garder[$s['fichier']]) && @unlink($s['fichier'])) {
                $supprimes[] = $s['nom'];
            }
        }
        return $supprimes;
    }

    // ------------------------------------------------------------------
    // Vérification
    // ------------------------------------------------------------------

    /**
     * Vérifie une sauvegarde : déchiffrement complet, décompression, PRAGMA integrity_check, tables requises,
     * nombre de lignes comparé à la base actuelle.
     * @param bool $garder true : garder la base décompressée (restauration), chemin dans 'copie'
     * @return array{ok:bool, fichier:string, problemes:list<string>, comptes:array<string, array{sauvegarde:?int, actuelle:?int}>, schema:string, cree_le:string, copie:?string}
     */
    public function verifier(string $fichier, bool $garder = false): array
    {
        $temp = $this->dossierTemporaire();
        $r = ['ok' => false, 'fichier' => $fichier, 'problemes' => [], 'comptes' => [], 'schema' => '', 'cree_le' => '', 'copie' => null];
        $copie = $temp . '/base.sqlite';
        try {
            if (!is_file($fichier)) {
                throw new \RuntimeException('Fichier introuvable : ' . basename($fichier));
            }
            $gz = $temp . '/base.sqlite.gz';
            if (str_ends_with($fichier, '.enc')) {
                if ($this->cle === null) {
                    throw new \RuntimeException('BACKUP_KEY absente du .env : impossible de déchiffrer cette sauvegarde.');
                }
                $meta = self::dechiffrer($fichier, $gz, $this->cle);
                $r['cree_le'] = (string) ($meta['cree_le'] ?? '');
            } elseif (!copy($fichier, $gz)) {
                throw new \RuntimeException('Copie de travail impossible.');
            }
            self::gunzip($gz, $copie);
            unlink($gz);
            $pdo = self::ouvrir($copie);
            $integrite = $pdo->query('PRAGMA integrity_check')->fetchAll(\PDO::FETCH_COLUMN);
            if ($integrite !== ['ok']) {
                $r['problemes'][] = 'Intégrité SQLite : ' . implode(' ; ', array_slice($integrite, 0, 3));
            }
            $tables = self::tables($pdo);
            foreach ($this->reglages['tables_requises'] as $t) {
                if (!in_array($t, $tables, true)) {
                    $r['problemes'][] = "Table attendue absente : $t";
                }
            }
            $r['schema'] = self::versionSchema($pdo);
            $actuelle = is_file($this->base) ? self::ouvrir($this->base, true) : null;
            $tablesActuelles = $actuelle ? self::tables($actuelle) : [];
            foreach ($this->reglages['tables_comptees'] as $t) {
                $dans = in_array($t, $tables, true);
                $ici = in_array($t, $tablesActuelles, true);
                if (!$dans && !$ici) {
                    continue;
                }
                // Nom de table issu de la configuration et vérifié contre sqlite_master : jamais d'une saisie.
                $r['comptes'][$t] = [
                    'sauvegarde' => $dans ? (int) $pdo->query('SELECT COUNT(*) FROM "' . $t . '"')->fetchColumn() : null,
                    'actuelle' => $ici && $actuelle ? (int) $actuelle->query('SELECT COUNT(*) FROM "' . $t . '"')->fetchColumn() : null,
                ];
            }
            $pdo = null;
            $actuelle = null;
            $r['ok'] = $r['problemes'] === [];
        } catch (\Throwable $e) {
            $r['problemes'][] = $e->getMessage();
            $r['ok'] = false;
        }
        if ($garder && $r['ok']) {
            $r['copie'] = $copie;
        } else {
            self::supprimerDossier($temp);
        }
        return $r;
    }

    /** Résultat de la dernière vérification, gardé dans le dossier des sauvegardes (lu par l'écran d'administration). @param array<string, mixed> $r */
    public function memoriserVerification(array $r): void
    {
        $this->preparerDossier();
        $resume = [
            'date' => date('Y-m-d H:i:s'),
            'fichier' => basename((string) ($r['fichier'] ?? '')),
            'ok' => (bool) ($r['ok'] ?? false),
            'problemes' => array_slice(array_map('strval', (array) ($r['problemes'] ?? [])), 0, 5),
        ];
        $f = $this->dossier . '/.derniere-verification.json';
        $masque = umask(0077);
        file_put_contents($f . '.tmp', json_encode($resume, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT), LOCK_EX);
        rename($f . '.tmp', $f);
        umask($masque);
    }

    /** @return array{date:string, fichier:string, ok:bool, problemes:list<string>}|null */
    public function derniereVerification(): ?array
    {
        $f = $this->dossier . '/.derniere-verification.json';
        $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
        return is_array($d) && isset($d['date'], $d['ok']) ? $d : null;
    }

    // ------------------------------------------------------------------
    // Restauration
    // ------------------------------------------------------------------

    /**
     * Remplace la base par une sauvegarde : vérification complète, puis sauvegarde de sécurité « avant-restauration »
     * de la base actuelle, puis copie par l'API backup de SQLite (sous verrou de la base : les autres connexions voient
     * la nouvelle base, le journal WAL reste cohérent). Sans l'extension sqlite3 : copie à côté puis rename,
     * réservé à la ligne de commande quand personne n'utilise le site.
     * @return array{verification:array<string, mixed>, securite:?string, methode:string}
     */
    public function restaurer(string $fichier): array
    {
        return $this->sousVerrou(function () use ($fichier): array {
            $v = $this->verifier($fichier, true);
            if (!$v['ok'] || $v['copie'] === null) {
                throw new \RuntimeException('Restauration annulée, rien n\'a été modifié : ' . implode(' ; ', $v['problemes']));
            }
            $copie = $v['copie'];
            try {
                $securite = is_file($this->base) ? $this->creerSansVerrou('avant-restauration', false)['fichier'] : null;
                if (class_exists(\SQLite3::class)) {
                    $source = new \SQLite3($copie, SQLITE3_OPEN_READONLY);
                    $dest = new \SQLite3($this->base);
                    $dest->busyTimeout(10000);
                    $ok = $source->backup($dest);
                    $dest->close();
                    $source->close();
                    if (!$ok) {
                        throw new \RuntimeException('Remplacement de la base impossible (base occupée ?). La sauvegarde de sécurité est gardée.');
                    }
                    $methode = 'api-backup';
                } else {
                    if (PHP_SAPI !== 'cli') {
                        throw new \RuntimeException('Restauration par le web impossible sans l\'extension sqlite3 : utiliser la ligne de commande.');
                    }
                    $tmp = $this->base . '.restauration-' . bin2hex(random_bytes(4));
                    if (!copy($copie, $tmp)) {
                        throw new \RuntimeException('Copie impossible vers ' . dirname($this->base));
                    }
                    chmod($tmp, 0600);
                    foreach (['-wal', '-shm', '-journal'] as $suffixe) {
                        // Ils appartiennent à l'ancienne base : à ne jamais rejouer sur la nouvelle.
                        if (is_file($this->base . $suffixe)) {
                            unlink($this->base . $suffixe);
                        }
                    }
                    if (!rename($tmp, $this->base)) {
                        @unlink($tmp);
                        throw new \RuntimeException('Remplacement de la base impossible.');
                    }
                    $methode = 'copie-rename';
                }
                $controle = self::ouvrir($this->base)->query('PRAGMA quick_check')->fetchColumn();
                if ($controle !== 'ok') {
                    throw new \RuntimeException('Base restaurée en défaut (quick_check) : restaurer la sauvegarde de sécurité ' . basename((string) $securite) . '.');
                }
            } finally {
                self::supprimerDossier(dirname($copie));
            }
            unset($v['copie']);
            return ['verification' => $v, 'securite' => $securite, 'methode' => $methode];
        });
    }

    // ------------------------------------------------------------------
    // Copie hors serveur
    // ------------------------------------------------------------------

    /**
     * Copie un fichier avec la commande BACKUP_REMOTE (rclone, scp...), où {fichier} est remplacé par son chemin.
     * Exécutée sans shell (proc_open avec un tableau d'arguments) : ni le chemin ni la commande ne sont réinterprétés.
     * @return array{code:int, sortie:list<string>}
     */
    public static function copierHorsServeur(string $commande, string $fichier): array
    {
        $args = self::decouperCommande($commande);
        if ($args === [] || !in_array('{fichier}', $args, true)) {
            throw new \RuntimeException('BACKUP_REMOTE doit contenir {fichier} comme argument (remplacé par le chemin de la sauvegarde).');
        }
        $args = array_map(static fn (string $a): string => $a === '{fichier}' ? $fichier : $a, $args);
        $proc = proc_open($args, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $tubes);
        if (!is_resource($proc)) {
            throw new \RuntimeException('Commande de copie impossible à lancer : ' . $args[0]);
        }
        $sortie = (string) stream_get_contents($tubes[1]);
        fclose($tubes[1]);
        $code = proc_close($proc);
        return ['code' => $code, 'sortie' => array_values(array_filter(explode("\n", $sortie), static fn (string $l): bool => trim($l) !== ''))];
    }

    /** Découpe « rclone copy {fichier} "distant:Mes sauvegardes" » en arguments (guillemets simples ou doubles). @return list<string> */
    public static function decouperCommande(string $commande): array
    {
        preg_match_all('/"([^"]*)"|\'([^\']*)\'|(\S+)/', $commande, $m, PREG_SET_ORDER);
        return array_map(static fn (array $x): string => ($x[3] ?? '') !== '' ? $x[3] : (($x[2] ?? '') !== '' ? $x[2] : $x[1]), $m);
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    public static function tailleLisible(int $octets): string
    {
        if ($octets < 1048576) {
            return number_format(max(1, $octets / 1024), 0, ',', "\u{202F}") . "\u{00A0}Ko";
        }
        return number_format($octets / 1048576, 1, ',', "\u{202F}") . "\u{00A0}Mo";
    }

    public static function supprimerDossier(string $dossier): void
    {
        if ($dossier === '' || !is_dir($dossier)) {
            return;
        }
        foreach (scandir($dossier) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $p = $dossier . '/' . $f;
            is_dir($p) && !is_link($p) ? self::supprimerDossier($p) : unlink($p);
        }
        rmdir($dossier);
    }

    /** @template T @param callable(): T $travail @return T */
    private function sousVerrou(callable $travail): mixed
    {
        $this->preparerDossier();
        $verrou = fopen($this->dossier . '/.verrou', 'c');
        if ($verrou === false || !flock($verrou, LOCK_EX | LOCK_NB)) {
            throw new \RuntimeException('Une sauvegarde ou une restauration est déjà en cours : réessayer dans une minute.');
        }
        try {
            return $travail();
        } finally {
            flock($verrou, LOCK_UN);
            fclose($verrou);
        }
    }

    private function preparerDossier(): void
    {
        if (!is_dir($this->dossier) && !mkdir($this->dossier, 0700, true) && !is_dir($this->dossier)) {
            throw new \RuntimeException('Dossier des sauvegardes impossible à créer : ' . $this->dossier);
        }
        // Défense en plus si le dossier se retrouve par erreur sous la racine web (Apache, LiteSpeed).
        if (!is_file($this->dossier . '/.htaccess')) {
            @file_put_contents($this->dossier . '/.htaccess', "Require all denied\n");
        }
    }

    /** Dossier de travail privé, à côté des sauvegardes (même disque, jamais dans un /tmp partagé). */
    private function dossierTemporaire(): string
    {
        $this->preparerDossier();
        $d = $this->dossier . '/.travail-' . bin2hex(random_bytes(6));
        mkdir($d, 0700);
        return $d;
    }

    private static function ouvrir(string $fichier, bool $lecture = false): \PDO
    {
        $pdo = new \PDO('sqlite:' . $fichier, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::SQLITE_ATTR_OPEN_FLAGS => $lecture ? \PDO::SQLITE_OPEN_READONLY : \PDO::SQLITE_OPEN_READWRITE,
        ]);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }

    /** @return list<string> */
    private static function tables(\PDO $pdo): array
    {
        return $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** Dernière migration appliquée (table schema_migrations du socle), vide si absente. */
    private static function versionSchema(\PDO $pdo): string
    {
        try {
            return (string) $pdo->query('SELECT MAX(name) FROM schema_migrations')->fetchColumn();
        } catch (\Throwable) {
            return '';
        }
    }

    private static function gzip(string $source, string $dest): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($dest, 'wb6');
        if ($in === false || $out === false) {
            throw new \RuntimeException('Compression impossible.');
        }
        while (!feof($in)) {
            gzwrite($out, (string) fread($in, self::BLOC));
        }
        fclose($in);
        gzclose($out);
    }

    private static function gunzip(string $source, string $dest): void
    {
        $in = gzopen($source, 'rb');
        $out = fopen($dest, 'xb');
        if ($in === false || $out === false) {
            throw new \RuntimeException('Décompression impossible.');
        }
        try {
            while (!gzeof($in)) {
                $d = gzread($in, self::BLOC);
                if ($d === false) {
                    throw new \RuntimeException('Archive gzip illisible.');
                }
                fwrite($out, $d);
            }
        } finally {
            gzclose($in);
            fclose($out);
        }
    }
}
