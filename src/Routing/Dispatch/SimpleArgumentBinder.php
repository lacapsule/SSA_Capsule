<?php

declare(strict_types=1);

namespace Capsule\Routing\Dispatch;

use Capsule\Contracts\ContainerLike;
use Capsule\Http\Message\Request;

/**
 * SimpleArgumentBinder
 *
 * Rôle : construire la liste d'arguments pour la méthode de contrôleur
 * à partir de :
 *  - la Request (injectée par TYPE),
 *  - des variables de route (associées par NOM → {var} ↔ paramètre du même nom),
 *  - du container pour les paramètres typés classe (services),
 *  - des valeurs par défaut (si disponibles).
 *
 * Erreurs :
 *  - Paramètre requis manquant → RuntimeException("Missing required argument ...")
 *  - Coercion invalide (ex: int attendu, "abc") → RuntimeException("Invalid int for ...")
 *
 * Invariants :
 *  - Pas d'I/O, purement mémoire.
 *  - Coercion uniquement pour types scalaires (int/float/bool/string).
 */
final class SimpleArgumentBinder
{
    /**
     * @param array<string,string> $routeVars  Variables capturées par le Router (toujours strings)
     * @return array<int,mixed>                 Liste d'arguments prêts pour l'invocation
     */
    public static function bind(\ReflectionMethod $method, Request $req, array $routeVars, ContainerLike $c): array
    {
        $args = [];

        foreach ($method->getParameters() as $p) {
            $t = $p->getType();
            $name = $p->getName();

            // 1) Injecter la Request par type
            if ($t instanceof \ReflectionNamedType && !$t->isBuiltin() && is_a($t->getName(), Request::class, true)) {
                $args[] = $req;
                continue;
            }

            // 2) Variable de route par NOM (coercion par TYPE scalaire)
            if (array_key_exists($name, $routeVars)) {
                $raw = $routeVars[$name];
                $args[] = self::coerce($t, $raw, $name);
                continue;
            }

            // 3) Service via container pour tout paramètre typé classe
            if ($t instanceof \ReflectionNamedType && !$t->isBuiltin()) {
                /** @var class-string $cls */
                $cls = $t->getName();
                $args[] = $c->get($cls);
                continue;
            }

            // 4) Valeur par défaut si disponible
            if ($p->isDefaultValueAvailable()) {
                $args[] = $p->getDefaultValue();
                continue;
            }

            // 5) Sinon : paramètre requis manquant
            throw new \RuntimeException("Missing required argument '{$name}'");
        }

        return $args;
    }

    private static function coerce(?\ReflectionType $t, string $raw, string $name): mixed
    {
        if (!$t instanceof \ReflectionNamedType || !$t->isBuiltin()) {
            // Pas de type scalaire → on laisse en string
            return $raw;
        }

        return match ($t->getName()) {
            'int' => ctype_digit($raw) ? (int)$raw
                        : throw new \RuntimeException("Invalid int for '{$name}'"),
            'float' => is_numeric($raw) ? (float)$raw
                        : throw new \RuntimeException("Invalid float for '{$name}'"),
            'bool' => self::coerceBool($raw, $name),
            'string' => $raw,
            default => $raw,
        };
    }

    private static function coerceBool(string $raw, string $name): bool
    {
        $v = strtolower($raw);
        if (in_array($v, ['1','true','on','yes'], true)) {
            return true;
        }
        if (in_array($v, ['0','false','off','no'], true)) {
            return false;
        }
        throw new \RuntimeException("Invalid bool for '{$name}'");
    }
}
