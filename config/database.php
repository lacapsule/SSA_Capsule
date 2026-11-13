<?php
declare(strict_types=1);

// Développement : SQLite dans data/ (créé par make db). Production : DB_DSN=mysql:host=...;dbname=...;charset=utf8mb4 dans le .env.
return [
    'dsn' => ($_ENV['DB_DSN'] ?? '') !== '' ? $_ENV['DB_DSN'] : 'sqlite:' . ROOT . '/data/dev.sqlite',
    'user' => ($_ENV['DB_USER'] ?? '') !== '' ? $_ENV['DB_USER'] : null,
    'password' => ($_ENV['DB_PASSWORD'] ?? '') !== '' ? $_ENV['DB_PASSWORD'] : null,
];
