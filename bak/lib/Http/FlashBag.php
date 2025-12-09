<?php

declare(strict_types=1);

namespace CapsuleLib\Http;


final class FlashBag
{
    private const KEY = '__flash';
    public static function add(string $type, string $msg): void
    {
        $_SESSION[self::KEY][$type][] = $msg;
    }
    /** @return array{success?:string[],error?:string[]} */
    public static function consume(): array
    {
        $all = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        return $all;
    }
}
