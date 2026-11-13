<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Applique une seule fois chaque fichier migrations/NNN_nom.sql, dans l'ordre des noms, en transaction.
 * Même fichier pour SQLite et MySQL grâce aux jetons :
 *   {{pk}}      clé primaire auto-incrémentée
 *   {{engine}}  options de table MySQL (InnoDB, utf8mb4), vide en SQLite
 * Découpage en instructions : un « ; » dans une chaîne ('a;b'), un identifiant ("a;b", `a;b`) ou un commentaire ne coupe pas.
 * Pour un corps qui contient lui-même des « ; » (déclencheur BEGIN ... END), séparer les instructions par une ligne
 *   -- @separateur
 * dans ce fichier : il n'est alors découpé que sur ces lignes.
 */
final class Migrator
{
    private const SEPARATOR = '/^[ \t]*--[ \t]*@separateur[ \t]*$/mi';

    /** @return list<string> migrations appliquées par cet appel */
    public static function run(?string $dir = null): array
    {
        $dir ??= ROOT . '/migrations';
        $pdo = Db::pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL)');
        $done = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(\PDO::FETCH_COLUMN);
        $files = glob($dir . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        $applied = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            $statements = self::statements(self::dialect((string) file_get_contents($file)));
            // SQLite garde le DDL en transaction ; MySQL valide chaque CREATE aussitôt, d'où l'ordre : schéma puis trace.
            $pdo->beginTransaction();
            try {
                foreach ($statements as $statement) {
                    $pdo->exec($statement);
                }
                Db::run('INSERT INTO schema_migrations (name, applied_at) VALUES (?, ?)', [$name, now()]);
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new \RuntimeException("Migration $name : " . $e->getMessage(), 0, $e);
            }
            $applied[] = $name;
        }
        return $applied;
    }

    private static function dialect(string $sql): string
    {
        $sqlite = Db::isSqlite();
        return strtr($sql, [
            '{{pk}}' => $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
            '{{engine}}' => $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ]);
    }

    /**
     * Instructions d'un fichier, sans les commentaires.
     * Chaînes au standard SQL : apostrophe doublée ('l''été'), pas d'échappement par barre oblique inverse.
     * @return list<string>
     */
    public static function statements(string $sql): array
    {
        $chunks = preg_match(self::SEPARATOR, $sql) === 1 ? (preg_split(self::SEPARATOR, $sql) ?: []) : self::split($sql);
        $out = [];
        foreach ($chunks as $chunk) {
            $chunk = trim(self::stripComments($chunk));
            if ($chunk !== '') {
                $out[] = $chunk;
            }
        }
        return $out;
    }

    /** @return list<string> découpage sur les « ; » hors chaînes, identifiants et commentaires */
    private static function split(string $sql): array
    {
        $parts = [];
        $current = '';
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $c = $sql[$i];
            if ($c === "'" || $c === '"' || $c === '`') {
                $end = self::closing($sql, $i, $c);
                $current .= substr($sql, $i, $end - $i + 1);
                $i = $end;
            } elseif ($c === '-' && ($sql[$i + 1] ?? '') === '-') {
                $end = strpos($sql, "\n", $i);
                $end = $end === false ? $length - 1 : $end;
                $current .= substr($sql, $i, $end - $i + 1);
                $i = $end;
            } elseif ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $end = $end === false ? $length - 1 : $end + 1;
                $current .= substr($sql, $i, $end - $i + 1);
                $i = $end;
            } elseif ($c === ';') {
                $parts[] = $current;
                $current = '';
            } else {
                $current .= $c;
            }
        }
        $parts[] = $current;
        return $parts;
    }

    /** Position du délimiteur fermant ; un délimiteur doublé ('' ou "") reste dans la chaîne. */
    private static function closing(string $sql, int $start, string $quote): int
    {
        $length = strlen($sql);
        for ($i = $start + 1; $i < $length; $i++) {
            if ($sql[$i] === $quote) {
                if (($sql[$i + 1] ?? '') === $quote) {
                    $i++;
                    continue;
                }
                return $i;
            }
        }
        throw new \RuntimeException('Chaîne ou identifiant non fermé (' . $quote . ') dans une migration.');
    }

    // Commentaires de ligne (deux tirets) et de bloc (barre oblique et étoile) retirés, chaînes intactes.
    private static function stripComments(string $sql): string
    {
        $out = '';
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $c = $sql[$i];
            if ($c === "'" || $c === '"' || $c === '`') {
                $end = self::closing($sql, $i, $c);
                $out .= substr($sql, $i, $end - $i + 1);
                $i = $end;
            } elseif ($c === '-' && ($sql[$i + 1] ?? '') === '-') {
                $end = strpos($sql, "\n", $i);
                if ($end === false) {
                    break;
                }
                $out .= "\n";
                $i = $end;
            } elseif ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                if ($end === false) {
                    break;
                }
                $out .= ' ';
                $i = $end + 1;
            } else {
                $out .= $c;
            }
        }
        return $out;
    }
}
