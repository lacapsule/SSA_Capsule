<?php

declare(strict_types=1);

namespace App\Modules\Login;

final class LoginPresenter
{
    /**
     * @param array<string,string> $i18n
     * @param array<string,string> $errors
     * @param array<string,mixed>  $prefill
     * @return array<string,mixed>
     */
    public static function loginForm(array $i18n, array $errors, array $prefill, string $csrfInput): array
    {
        return [
            'showHeader' => true,
            'showFooter' => true,
            'title' => 'Connexion',
            'str' => $i18n,
            'error' => $errors['_global'] ?? null,
            'errors' => $errors,
            'prefill' => [
                'username' => (string)($prefill['username'] ?? ''),
            ],
            'csrf_input' => $csrfInput,
            'action' => '/login',
        ];
    }
}
