<?php
declare(strict_types=1);

/**
 * Mot de passe lu sur l'entrée standard, une ligne, jamais affiché (scripts create-admin et reset-admin-password).
 * Saisi au clavier : masqué quand le terminal le permet (stty sous Linux et macOS ; sous Windows, la saisie reste visible).
 */
function read_password(string $prompt): string
{
    $hidden = false;
    if (function_exists('stream_isatty') && stream_isatty(STDIN)) {
        fwrite(STDOUT, $prompt);
        $hidden = DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec') && @shell_exec('stty -echo 2>/dev/null') !== null;
    }
    $line = fgets(STDIN);
    if ($hidden) {
        @shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, PHP_EOL);
    }
    return $line === false ? '' : rtrim($line, "\r\n");
}
