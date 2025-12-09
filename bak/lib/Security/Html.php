<?php

declare(strict_types=1);

namespace CapsuleLib\Security;

/**
 * Classe utilitaire pour l'échappement HTML sécurisé.
 *
 * Fournit des méthodes statiques pour protéger les sorties HTML
 * contre les attaques XSS dans différents contextes : texte, attributs, URLs, JS inline, structures complexes.
 */
final class Html
{
    /**
     * Échappe une chaîne pour un affichage HTML classique (texte entre balises).
     *
     * Utilise `htmlspecialchars()` avec `ENT_QUOTES` et `ENT_SUBSTITUTE` pour une couverture complète :
     * - Échappe les guillemets simples et doubles
     * - Remplace les caractères invalides par une substitution UTF-8
     *
     * @param string|null $str Chaîne brute à sécuriser.
     * @return string Chaîne sécurisée pour sortie HTML.
     */
    public static function escape(?string $str): string
    {
        return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Échappe une chaîne destinée à être injectée dans un attribut HTML (`href`, `value`, `alt`, etc.).
     *
     * Peut être personnalisée pour filtrer des caractères interdits dans certains attributs.
     * Par défaut, délègue à `escape()`.
     *
     * @param string|null $str Valeur brute.
     * @return string Chaîne sécurisée pour un attribut HTML.
     */
    public static function escapeAttr(?string $str): string
    {
        return self::escape($str);
    }

    /**
     * Échappe une URL pour l'insérer dans un attribut HTML (`href`, `src`), tout en filtrant les schémas dangereux.
     *
     * Seuls les schémas `http`, `https`, `ftp`, et `mailto` sont autorisés.
     * Une URL non valide ou vide renvoie une chaîne vide.
     *
     * @param string|null $url URL potentiellement dangereuse.
     * @return string URL sécurisée ou vide si invalide.
     */
    public static function escapeUrl(?string $url): string
    {
        $url = trim($url ?? '');
        if (!preg_match('#^(https?|ftp|mailto):#i', $url)) {
            return '';
        }
        return self::escape($url);
    }

    /**
     * Échappe une chaîne à injecter dans un contexte JavaScript inline.
     *
     * Exemple : `var msg = '<?= escapeJs($val) ?>';`
     * Protège contre les séquences JS problématiques (`</script>`, guillemets, retours à la ligne).
     *
     * @param string|null $str Texte à insérer dans du JS inline.
     * @return string Chaîne sécurisée, échappée pour JS.
     */
    public static function escapeJs(?string $str): string
    {
        return str_replace(
            ["\\",  "'",   "\"",  "\r", "\n", "</"],
            ["\\\\", "\\'", "\\\"", "\\r", "\\n", "<\\/"],
            $str ?? ''
        );
    }

    /**
     * Échappe récursivement tous les éléments `string` d’un tableau ou objet (ex : DTO, array associatif).
     *
     * Les clés sont conservées, et les types non string (int, bool, null…) ne sont pas modifiés.
     * Utile pour sécuriser des données à afficher ou envoyer.
     *
     * @param array|object $data Données à échapper (deep map).
     * @return array Tableau échappé récursivement.
     */
    public static function escapeArray(array|object $data): array
    {
        $escape = fn($v) => is_string($v) ? self::escape($v) : $v;

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = (is_array($v) || is_object($v))
                    ? self::escapeArray($v)
                    : $escape($v);
            }
            return $data;
        }

        if (is_object($data)) {
            $result = [];
            foreach (get_object_vars($data) as $k => $v) {
                $result[$k] = (is_array($v) || is_object($v))
                    ? self::escapeArray($v)
                    : $escape($v);
            }
            return $result;
        }

        return [];
    }
}
