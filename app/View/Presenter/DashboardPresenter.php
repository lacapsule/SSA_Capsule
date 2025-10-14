<?php

declare(strict_types=1);

namespace App\View\Presenter;

use Capsule\Domain\DTO\UserDTO;
use Capsule\View\Presenter\IterablePresenter;

final class DashboardPresenter
{
    /**
     * “Shell” du dashboard (layout commun).
     * @param array<string,mixed> $i18n
     * @param array<string,mixed> $currentUser
     * @param list<array{title:string,url:string,icon:string}> $links
     * @param array<string,array<mixed>> $flash
     * @return array<string,mixed>
     */
    public static function base(array $i18n, array $currentUser, bool $isAdmin, array $links, array $flash): array
    {
        return [
            'showHeader' => false,
            'showFooter' => false,
            'isDashboard' => true,
            'str' => $i18n,
            'user' => $currentUser,
            'isAdmin' => $isAdmin,
            'links' => $links,
            'flash' => $flash,
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
            $name = (string) ($u->username ?? ''); // si ta DTO a "username"
            $email = (string) $u->email;
            $role = (string) $u->role;

            return [
                'id' => $id,
               'name' => $name,   // ou 'username' côté template si tu préfères
                'email' => $email,
                'role' => $role,
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
     * Vue “Account”.
     * @param array<string,string> $errors
     * @param array<string,mixed> $prefill
     * @return array<string,mixed>
     */
    public static function account(array $base, array $errors, array $prefill, string $csrfInput): array
    {
        return $base + [
            'title' => 'Mon compte',
            'component' => 'dashboard/dash_account',
            'errors' => $errors,
            'prefill' => $prefill,
            'action' => '/dashboard/account/password',
            'editUserAction' => '/dashboard/account/update',
            'csrf_input' => $csrfInput,
        ];
    }
}
