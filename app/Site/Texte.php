<?php
declare(strict_types=1);

namespace App\Site;

/** Mise en forme des textes saisis (sans mbstring : expressions régulières UTF-8). */
final class Texte
{
    /** Coupe au dernier mot entier sous $max caractères, suivi de « … ». */
    public static function couper(string $texte, int $max): string
    {
        $texte = trim((string) preg_replace('/\s+/u', ' ', $texte));
        if (text_length($texte) <= $max) {
            return $texte;
        }
        preg_match('/^.{0,' . ($max - 1) . '}(?=\s)/su', $texte, $m);
        return rtrim($m[0] ?? '', ' ,;:') . '…';
    }

    /** Texte brut en paragraphes HTML échappés (une ligne vide ou un retour = un paragraphe). */
    public static function paragraphes(string $texte): string
    {
        $blocs = preg_split('/\n+/u', trim($texte)) ?: [];
        return implode("\n", array_map(static fn (string $p): string => '<p>' . e(trim($p)) . '</p>', array_filter($blocs, static fn (string $p): bool => trim($p) !== '')));
    }
}
