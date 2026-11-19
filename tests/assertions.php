<?php
declare(strict_types=1);

/*
 * Assertions minimales du lanceur (tests/run.php). Chacune lève EchecTest avec un message lisible ;
 * le lanceur affiche le message et la ligne du fichier de test.
 */

final class EchecTest extends RuntimeException
{
}

function affirmer(mixed $condition, string $message = 'condition fausse'): void
{
    if (!$condition) {
        throw new EchecTest($message);
    }
}

function affirmer_egal(mixed $attendu, mixed $obtenu, string $message = ''): void
{
    if ($attendu !== $obtenu) {
        throw new EchecTest(($message !== '' ? "$message : " : '') . 'attendu ' . var_export($attendu, true) . ', obtenu ' . var_export($obtenu, true));
    }
}

function affirmer_contient(string $aiguille, string $texte, string $message = ''): void
{
    if (!str_contains($texte, $aiguille)) {
        throw new EchecTest(($message !== '' ? "$message : " : '') . "« $aiguille » introuvable dans « " . mb_substr($texte, 0, 120) . ' »');
    }
}

function affirmer_proche(float $attendu, float $obtenu, float $ecart = 0.01, string $message = ''): void
{
    if (abs($attendu - $obtenu) > $ecart) {
        throw new EchecTest(($message !== '' ? "$message : " : '') . "attendu $attendu à $ecart près, obtenu $obtenu");
    }
}

/** @template T of Throwable @param class-string<T> $classe @return T l'exception levée, pour vérifier son message */
function affirmer_exception(callable $code, string $classe = Throwable::class): Throwable
{
    try {
        $code();
    } catch (EchecTest $e) {
        throw $e;
    } catch (Throwable $e) {
        if ($e instanceof $classe) {
            return $e;
        }
        throw new EchecTest('exception inattendue ' . $e::class . ' : ' . $e->getMessage());
    }
    throw new EchecTest("une exception $classe était attendue");
}
