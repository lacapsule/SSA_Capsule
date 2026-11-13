<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Arrêt d'une requête (redirection, 403, 404) sans exit() : le contrôleur frontal l'attrape et répond.
 * Les contrôleurs restent testables : un test attrape l'exception au lieu de voir le processus s'arrêter.
 */
final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '', public readonly ?string $location = null)
    {
        parent::__construct($message, $status);
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return new self($status, '', $location);
    }

    public static function forbidden(string $message = 'Accès refusé.'): self
    {
        return new self(403, $message);
    }

    public static function notFound(string $message = 'Page introuvable.'): self
    {
        return new self(404, $message);
    }

    /** Réponse à envoyer : à appeler depuis le contrôleur frontal (voir NOTES.md). */
    public function send(): void
    {
        if ($this->location !== null) {
            header('Location: ' . $this->location, true, $this->status);
            return;
        }
        http_response_code($this->status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $this->getMessage();
    }
}
