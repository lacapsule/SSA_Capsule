<?php

declare(strict_types=1);

namespace CapsuleLib\Http;

final class FormState
{
    private const ERRORS = '__form_errors';
    private const DATA   = '__form_data';

    /** @param array<string,string> $errors  @param array<string,mixed> $data */
    public static function set(array $errors, array $data): void
    {
        $_SESSION[self::ERRORS] = $errors;
        $_SESSION[self::DATA]   = $data;
    }

    /** @return array<string,string>|null */
    public static function consumeErrors(): ?array
    {
        $e = $_SESSION[self::ERRORS] ?? null;
        unset($_SESSION[self::ERRORS]);
        return $e;
    }

    /** @return array<string,mixed>|null */
    public static function consumeData(): ?array
    {
        $d = $_SESSION[self::DATA] ?? null;
        unset($_SESSION[self::DATA]);
        return $d;
    }
}
