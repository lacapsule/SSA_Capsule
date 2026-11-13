<?php
declare(strict_types=1);

namespace App\Langue;

use App\Core\Db;

/**
 * Traductions du contenu en base (table traductions), champ par champ : un article, une page, une fiche gardent
 * leurs textes dans leur propre table, dans la langue par défaut ; ce module ne stocke que les traductions.
 *
 * Désignation d'un contenu : objet (« article », « page ») + identifiant (« 12 », « /a-propos/ ») + champ (« titre »).
 * Le module qui enregistre la version d'origine appelle source() : l'empreinte du texte d'origine est gardée, et une
 * traduction faite sur un texte depuis modifié est signalée périmée (bin/traductions contenu).
 */
final class Contenu
{
    public static function empreinte(string $texte): string
    {
        return hash('sha256', $texte);
    }

    /** Texte d'origine d'un champ, à déclarer à chaque enregistrement dans le module (seule son empreinte est gardée). */
    public static function source(string $objet, string $id, string $champ, string $texte): void
    {
        self::ecrire($objet, $id, $champ, Langue::defaut(), '', self::empreinte($texte));
    }

    /**
     * Enregistre la traduction d'un champ. Valeur vide : traduction retirée, le texte d'origine s'affiche à nouveau.
     * $source : texte d'origine tel qu'il était sous les yeux du traducteur (son empreinte date la traduction).
     */
    public static function enregistrer(string $objet, string $id, string $champ, string $langue, string $valeur, string $source): void
    {
        if ($langue === Langue::defaut() || !Langue::existe($langue)) {
            throw new \InvalidArgumentException("Langue de traduction refusée : $langue");
        }
        self::verifier($objet, $champ);
        if (trim($valeur) === '') {
            Db::run('DELETE FROM traductions WHERE objet = ? AND objet_id = ? AND champ = ? AND langue = ?', [$objet, $id, $champ, $langue]);
            return;
        }
        self::ecrire($objet, $id, $champ, $langue, $valeur, self::empreinte($source));
    }

    /**
     * Champs d'un contenu dans une langue : chaque traduction non vide remplace le champ d'origine, les autres restent
     * dans la langue par défaut. La clé « _langues » dit la langue réelle de chaque champ (pour poser lang="fr"
     * sur un champ resté en français dans une page anglaise).
     * @param array<string, mixed> $champs ligne d'origine
     * @return array<string, mixed>
     */
    public static function traduire(string $objet, string $id, array $champs, ?string $langue = null): array
    {
        $langue ??= Langue::actuelle();
        $langues = array_fill_keys(array_keys($champs), Langue::defaut());
        if ($langue !== Langue::defaut()) {
            $lignes = Db::run('SELECT champ, valeur FROM traductions WHERE objet = ? AND objet_id = ? AND langue = ?', [$objet, $id, $langue])->fetchAll();
            foreach ($lignes as $l) {
                if (array_key_exists($l['champ'], $champs) && $l['valeur'] !== '') {
                    $champs[$l['champ']] = (string) $l['valeur'];
                    $langues[$l['champ']] = $langue;
                }
            }
        }
        return $champs + ['_langues' => $langues];
    }

    /**
     * Même chose pour une liste (page d'articles) en une seule requête.
     * @param list<array<string, mixed>> $lignes
     * @return list<array<string, mixed>>
     */
    public static function traduireListe(string $objet, array $lignes, string $cleId = 'id', ?string $langue = null): array
    {
        $langue ??= Langue::actuelle();
        $ids = array_values(array_unique(array_map(static fn (array $l): string => (string) $l[$cleId], $lignes)));
        $parId = [];
        if ($langue !== Langue::defaut() && $ids !== []) {
            $marques = implode(', ', array_fill(0, count($ids), '?'));
            $rows = Db::run("SELECT objet_id, champ, valeur FROM traductions WHERE objet = ? AND langue = ? AND objet_id IN ($marques)", [$objet, $langue, ...$ids])->fetchAll();
            foreach ($rows as $r) {
                $parId[(string) $r['objet_id']][(string) $r['champ']] = (string) $r['valeur'];
            }
        }
        foreach ($lignes as &$l) {
            $langues = array_fill_keys(array_keys($l), Langue::defaut());
            foreach ($parId[(string) $l[$cleId]] ?? [] as $champ => $valeur) {
                if (array_key_exists($champ, $l) && $valeur !== '') {
                    $l[$champ] = $valeur;
                    $langues[$champ] = $langue;
                }
            }
            $l['_langues'] = $langues;
        }
        return $lignes;
    }

    /** Langue réelle d'un champ renvoyé par traduire() : à poser en lang="" quand elle diffère de la page. @param array<string, mixed> $traduit */
    public static function langueDe(array $traduit, string $champ): string
    {
        return (string) ($traduit['_langues'][$champ] ?? Langue::defaut());
    }

    /**
     * Langues dans lesquelles le contenu existe : la langue par défaut, plus celles où le champ requis est traduit.
     * Sert aux hreflang, au sitemap et au sélecteur (une page non traduite n'est pas annoncée dans cette langue).
     * @return list<string>
     */
    public static function disponibles(string $objet, string $id, ?string $champRequis = null): array
    {
        $traduites = Db::run(
            "SELECT langue FROM traductions WHERE objet = ? AND objet_id = ? AND champ = ? AND valeur <> ''",
            [$objet, $id, $champRequis ?? Langue::champRequis()]
        )->fetchAll(\PDO::FETCH_COLUMN);
        return Langue::filtrer(array_merge([Langue::defaut()], array_map('strval', $traduites)));
    }

    /** « manquante », « perimee » (texte d'origine modifié depuis) ou « a-jour ». */
    public static function etat(string $objet, string $id, string $champ, string $langue): string
    {
        $lignes = Db::run(
            'SELECT langue, empreinte_source FROM traductions WHERE objet = ? AND objet_id = ? AND champ = ? AND langue IN (?, ?)',
            [$objet, $id, $champ, Langue::defaut(), $langue]
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
        if (!isset($lignes[$langue])) {
            return 'manquante';
        }
        return isset($lignes[Langue::defaut()]) && $lignes[Langue::defaut()] !== $lignes[$langue] ? 'perimee' : 'a-jour';
    }

    /**
     * Traductions à faire ou à revoir : pour chaque champ déclaré par source(), chaque autre langue où il manque
     * ou est périmé.
     * @param list<string>|null $langues
     * @return list<array{objet: string, objet_id: string, champ: string, langue: string, etat: string}>
     */
    public static function aRevoir(?array $langues = null): array
    {
        $defaut = Langue::defaut();
        $langues = array_values(array_diff($langues ?? array_keys(Langue::langues()), [$defaut]));
        $sources = Db::run('SELECT objet, objet_id, champ, empreinte_source FROM traductions WHERE langue = ? ORDER BY objet, objet_id, champ', [$defaut])->fetchAll();
        $traduites = [];
        foreach (Db::run('SELECT objet, objet_id, champ, langue, empreinte_source FROM traductions WHERE langue <> ?', [$defaut])->fetchAll() as $t) {
            $traduites[$t['objet'] . "\0" . $t['objet_id'] . "\0" . $t['champ'] . "\0" . $t['langue']] = (string) $t['empreinte_source'];
        }
        $out = [];
        foreach ($sources as $s) {
            foreach ($langues as $langue) {
                $empreinte = $traduites[$s['objet'] . "\0" . $s['objet_id'] . "\0" . $s['champ'] . "\0" . $langue] ?? null;
                $etat = $empreinte === null ? 'manquante' : ($empreinte !== $s['empreinte_source'] ? 'perimee' : null);
                if ($etat !== null) {
                    $out[] = ['objet' => (string) $s['objet'], 'objet_id' => (string) $s['objet_id'], 'champ' => (string) $s['champ'], 'langue' => $langue, 'etat' => $etat];
                }
            }
        }
        return $out;
    }

    /** À appeler quand le contenu est supprimé dans son module. */
    public static function supprimer(string $objet, string $id): void
    {
        Db::run('DELETE FROM traductions WHERE objet = ? AND objet_id = ?', [$objet, $id]);
    }

    private static function ecrire(string $objet, string $id, string $champ, string $langue, string $valeur, string $empreinte): void
    {
        self::verifier($objet, $champ);
        // Lecture puis mise à jour ou création : portable SQLite et MySQL (ni ON CONFLICT ni ON DUPLICATE KEY).
        // Pas de test sur rowCount() : MySQL compte 0 ligne modifiée quand la valeur ne change pas.
        $existe = Db::run(
            'SELECT COUNT(*) FROM traductions WHERE objet = ? AND objet_id = ? AND champ = ? AND langue = ?',
            [$objet, $id, $champ, $langue]
        )->fetchColumn() > 0;
        if ($existe) {
            Db::run(
                'UPDATE traductions SET valeur = ?, empreinte_source = ?, mis_a_jour = ? WHERE objet = ? AND objet_id = ? AND champ = ? AND langue = ?',
                [$valeur, $empreinte, now(), $objet, $id, $champ, $langue]
            );
        } else {
            Db::run(
                'INSERT INTO traductions (objet, objet_id, champ, langue, valeur, empreinte_source, mis_a_jour) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$objet, $id, $champ, $langue, $valeur, $empreinte, now()]
            );
        }
    }

    private static function verifier(string $objet, string $champ): void
    {
        if (preg_match('/^[a-z][a-z0-9_-]{0,59}$/', $objet) !== 1 || preg_match('/^[a-z][a-z0-9_-]{0,59}$/', $champ) !== 1) {
            throw new \InvalidArgumentException("Objet ou champ invalide : $objet.$champ");
        }
    }
}
