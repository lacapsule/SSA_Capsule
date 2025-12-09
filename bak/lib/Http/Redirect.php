<?php

declare(strict_types=1);

namespace CapsuleLib\Http;

final class Redirect
{
    public static function to(string $path, int $status = 303): never
    {
        header('Location: ' . $path, true, $status);
        exit;
    }

    /**
     * Redirige avec erreurs de formulaire et données repopulées
     *
     * @param array<string,string> $errors
     * @param array<string,mixed>  $data
     */
    public static function withErrors(string $to, string $message, array $errors, array $data = [], int $status = 303): never
    {
        FormState::set($errors, $data);
        FlashBag::add('error', $message);
        self::to($to, $status);
    }

    /**
     * Redirige avec message de succès
     */
    public static function withSuccess(string $to, string $message, int $status = 303): never
    {
        FlashBag::add('success', $message);
        self::to($to, $status);
    }
}
