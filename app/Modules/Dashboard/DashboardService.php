<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use Capsule\Domain\Service\UserService;
use Capsule\Support\Pagination\Page;

final class DashboardService
{
    public function __construct(private UserService $users)
    {
    }

    /**
     * Renvoie la liste des utilisateurs (iterable) — paginable si ton UserService le supporte.
     * @return iterable<object>  // ex: UserDTO ou stdClass/array hydratés par UserService
     */
    public function getUsers(Page $page): iterable
    {
        // Si ton UserService expose une pagination, utilise-la.
        // Sinon, récupère tout et laisse le Presenter découper (ou fais une pagination simple ici).
        return $this->users->getAllUsers(); // supposé iterable|array<object>
    }
}
