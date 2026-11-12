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
            'accountEmailAction' => '/dashboard/account/email',
            'csrf_input' => $csrfInput,
        ];
    }

    /**
     * @param array<int,array{
     *  id:int,name:string,description:?string,kind:string,position:int,is_active:int,
     *  logos:array<int,array{id:int,name:string,url:string,logo:string,position:int}>
     * }> $sections
     * @param array<string,string> $errors
     * @param array<string,mixed> $old
     * @param array<string,list<string>> $flash
     */
    public static function partners(
        array $base,
        array $sections,
        array $kinds,
        string $csrfInput,
        array $flash,
        array $errors,
        array $old
    ): array {
        $sections = array_map(function (array $section) use ($kinds): array {
            $section['isPartenaire'] = $section['kind'] === 'partenaire';
            $section['isFinanceur'] = $section['kind'] === 'financeur';
            $section['position'] = (int) $section['position'];
            $section['is_active_bool'] = (int) ($section['is_active'] ?? 1) === 1;
            $section['logos_count'] = (int) ($section['logos_count'] ?? count($section['logos'] ?? []));
            $section['logos_count_plural'] = $section['logos_count'] > 1;
            $section['kind_options'] = [];
            foreach ($kinds as $value => $label) {
                $section['kind_options'][] = [
                    'value' => $value,
                    'label' => $label,
                    'selected' => $section['kind'] === $value,
                ];
            }

            return $section;
        }, $sections);

        $newSectionKind = $old['kind'] ?? 'partenaire';
        $kindOptions = [];
        foreach ($kinds as $value => $label) {
            $kindOptions[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $newSectionKind,
            ];
        }

        return $base + [
            'title' => 'Partenaires',
            'component' => 'dashboard/components/partners',
            'sections' => $sections,
            'kind_options' => $kindOptions,
            'csrf_input' => $csrfInput,
            'flash_success' => $flash['success'] ?? [],
            'flash_error' => $flash['error'] ?? [],
            'errors' => $errors,
            'old' => $old,
            'createSectionAction' => '/dashboard/partners/sections',
        ];
    }
}
