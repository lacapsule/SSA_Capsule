<?php
/**
 * Proposition discrète d'une autre langue (modules/multilingue), d'après la langue du navigateur : un lien, dans
 * la langue proposée, vers la même page. Jamais de redirection ni de cookie : le visiteur reste où il a cliqué,
 * les robots voient toujours la même page. Langue::demarrer() envoie Vary: Accept-Language.
 *   <?php partial('langue/proposition', ['disponibles' => $disponibles ?? null]) ?>
 * Facultatif : list<string>|null $disponibles, string $chemin (chemin interne).
 */
use App\Langue\Langue;
use App\Langue\T;

$chemin = isset($chemin) && is_string($chemin) ? $chemin : Langue::chemin();
$code = Langue::proposition($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null, isset($disponibles) && is_array($disponibles) ? $disponibles : null);
if ($code === null) {
    return;
}
?>
<p class="langue-proposition" lang="<?= e(Langue::hreflang($code)) ?>" dir="<?= e(Langue::dir($code)) ?>">
  <svg class="langues__icone" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.75 5.6 3.75 9S14.5 18.4 12 21M12 3C9.5 5.6 8.25 8.6 8.25 12S9.5 18.4 12 21"/></g></svg>
  <a href="<?= e(Langue::url($chemin, $code)) ?>" hreflang="<?= e(Langue::hreflang($code)) ?>"><?= e(T::t('langue.proposition', [], $code)) ?></a>
</p>
