<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use Capsule\Domain\DTO\UserDTO;
use Capsule\View\Presenter\IterablePresenter;

final class DashboardPresenter
{
    /**
     * “Shell” du dashboard (layout commun).
     * @param array<string,mixed> $i18n
     * @param array<string,mixed> $currentUser
     * @param list<array{title:string,url:string,icon:string}> $links
     * @return array<string,mixed>
     */
    public static function base(array $i18n, array $currentUser, bool $isAdmin, array $links): array
    {
        return [
            'showHeader' => false,
            'showFooter' => false,
            'isDashboard' => true,
            'str' => $i18n,
            'user' => $currentUser,
            'isAdmin' => $isAdmin,
            'links' => $links,
        ];
    }

    /**
     * Vue “Users”.
     * @param iterable<object> $users  // lazy OK
     * @param array<string,string> $errors
     * @param array<string,mixed> $prefill
     * @return array<string,mixed>
     */
    public static function users(array $base, iterable $users, array $errors, array $prefill, string $csrfInput): array
    {
        // Projection minimaliste -> array<string,mixed> par utilisateur
        $mapped = IterablePresenter::map($users, function (UserDTO $u): array {
            // Ajuste les noms aux propriétés RÉELLES de UserDTO
            $id = (int)    $u->id;
            $username = (string) ($u->username ?? '');
            $email = (string) $u->email;
            $role = (string) $u->role;
            $createdAt = (string) ($u->createdAt ?? '');

            return [
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'created_at' => $createdAt,
            ];
        });

        // FRONTIÈRE VUE : matérialiser en array ré-itérable
        $usersArray = IterablePresenter::toArray($mapped);

        return $base + [
            'title' => 'Utilisateurs',
            'component' => 'dashboard/dash_users',
            'users' => $usersArray,
            'errors' => $errors,
            'prefill' => $prefill,
            'createAction' => '/dashboard/users/create',
            'deleteAction' => '/dashboard/users/delete',
            'csrf_input' => $csrfInput,
        ];
    }

    /**
     * Vue "Account".
     * @param array<string,string> $errors
     * @param array<string,mixed> $prefill
     * @return array<string,mixed>
     */
    public static function account(array $base, array $errors, array $prefill, string $csrfInput): array
    {
        // Ajouter les initiales de l'utilisateur
        $user = $base['user'] ?? [];
        $username = (string) ($user['username'] ?? 'User');
        $email = (string) ($user['email'] ?? '');

        // Générer les initiales
        $initials = implode('', array_map(
            fn($word) => strtoupper($word[0] ?? ''),
            explode(' ', trim($username))
        ));
        $initials = substr($initials, 0, 2) ?: 'U';

        return $base + [
            'title' => 'Mon compte',
            'component' => 'component:dashboard/components/account',
            'errors' => $errors,
            'prefill' => $prefill,
            'user' => [
                ...$user,
                'initial' => $initials,
            ],
            'accountPasswordAction' => '/dashboard/account/password',
            'csrf_input' => $csrfInput,
        ];
    }
}
