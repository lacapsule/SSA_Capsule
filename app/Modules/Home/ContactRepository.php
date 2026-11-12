<?php

declare(strict_types=1);

namespace App\Modules\Home;

use Capsule\Domain\Repository\BaseRepository;
use PDO;

final class ContactRepository extends BaseRepository
{
    protected string $table = 'contacts';
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function countSinceIp(string $ip, string $since): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE ip = :ip AND created_at >= :since";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'ip' => $ip,
            'since' => $since,
        ]);

        return (int) $stmt->fetchColumn();
    }
}


