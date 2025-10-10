<?php

declare(strict_types=1);

namespace Capsule\Routing\Compiler;

/**
 * RouteCompiler
 *
 * Rôle : transformer un chemin déclaratif "humain"
 *   ex: "/users/{id}/posts/{slug}"
 * en une regex exploitable + liste des variables + template normalisé.
 *
 * Conventions appliquées :
 *  - Chaque placeholder {name} reçoit un motif selon ParamNameConventions::regexFor(name).
 *  - Parties statiques du chemin sont entièrement échappées (preg_quote).
 *  - Normalisation :
 *      * toujours un slash initial
 *      * pas de slash final (sauf "/")
 *
 * Erreurs levées :
 *  - Accolade non fermée
 *  - Nom de variable invalide (doit matcher ^[A-Za-z_][A-Za-z0-9_]*$)
 *
 * Sortie :
 *  - regex    : string (avec délimiteurs "#^ ... $#u")
 *  - vars     : list<string> (ordre d'apparition)
 *  - template : string (chemin normalisé, ex: "/users/{id}/posts/{slug}")
 */
final class RouteCompiler
{
    /**
     * @return array{regex:string, vars:list<string>, template:string}
     */
    public static function compile(string $path): array
    {
        // 1) Normalisation du template
        $path = '/' . ltrim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $vars = [];
        $regexBody = '';

        // 2) Parcours manuel pour échapper le texte statique
        //    et substituer les placeholders {name}
        for ($i = 0, $L = strlen($path); $i < $L;) {
            $pos = strpos($path, '{', $i);

            // Pas (ou plus) de placeholder : échapper le reste et finir
            if ($pos === false) {
                $regexBody .= preg_quote(substr($path, $i), '#');
                break;
            }

            // Partie statique avant le placeholder
            if ($pos > $i) {
                $regexBody .= preg_quote(substr($path, $i, $pos - $i), '#');
            }

            // Trouver la fermeture
            $end = strpos($path, '}', $pos);
            if ($end === false) {
                throw new \InvalidArgumentException("Unclosed '{' in route path: {$path}");
            }

            // Extraire le nom du placeholder
            $name = substr($path, $pos + 1, $end - $pos - 1);
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
                throw new \InvalidArgumentException("Invalid placeholder name '{$name}' in route path: {$path}");
            }

            $vars[] = $name;
            $pattern = ParamNameConventions::regexFor($name);

            // Insérer un groupe nommé (?P<name>pattern)
            $regexBody .= '(?P<' . $name . '>' . $pattern . ')';

            // Avancer après la fermeture
            $i = $end + 1;
        }

        // 3) Délimiteurs + ancrages + unicode
        $regex = '#^' . $regexBody . '$#u';

        return [
            'regex' => $regex,
            'vars' => $vars,
            'template' => $path,
        ];
    }
}
