<?php
/**
 * Sélecteur de langue (modules/multilingue) : de vrais liens vers la page équivalente (ou l'accueil de la langue si
 * la page n'y existe pas), chaque langue écrite dans sa langue avec lang et hreflang, langue affichée en aria-current.
 * Pas de drapeau (un drapeau est un pays, pas une langue) ni de script.
 *   <?php partial('langue/selecteur', ['variante' => 'menu', 'disponibles' => $disponibles ?? null]) ?>
 * @var string $variante 'menu' (repliable, en-tête) ou 'liste' (en ligne, pied de page)
 * Facultatif : list<string>|null $disponibles, string $chemin (chemin interne), string $id.
 */
use App\Langue\Langue;
use App\Langue\T;

$chemin = isset($chemin) && is_string($chemin) ? $chemin : Langue::chemin();
$entrees = Langue::selecteur($chemin, isset($disponibles) && is_array($disponibles) ? $disponibles : null);
$variante = ($variante ?? 'liste') === 'menu' ? 'menu' : 'liste';
$id = isset($id) && is_string($id) ? $id : 'langues-' . $variante;
if (count($entrees) < 2) {
    return;
}
$liens = static function (array $entrees): void {
    foreach ($entrees as $l): ?>
      <li><a class="langues__lien" href="<?= e($l['href']) ?>" hreflang="<?= e($l['hreflang']) ?>" lang="<?= e($l['hreflang']) ?>"<?= $l['actuelle'] ? ' aria-current="true"' : '' ?>><?= e($l['nom']) ?></a></li>
    <?php endforeach;
};
?>
<?php if ($variante === 'menu'): ?>
<nav class="langues langues--menu" aria-label="<?= e(T::t('langue.choisir')) ?>">
  <details class="langues__details">
    <summary class="langues__bouton">
      <svg class="langues__icone" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.75 5.6 3.75 9S14.5 18.4 12 21M12 3C9.5 5.6 8.25 8.6 8.25 12S9.5 18.4 12 21"/></g></svg>
      <span class="visually-hidden"><?= e(T::t('langue.choisir')) ?> : </span><span lang="<?= e(Langue::hreflang()) ?>"><?= e(Langue::nom()) ?></span>
      <svg class="langues__chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <ul class="langues__liste" id="<?= e($id) ?>">
      <?php $liens($entrees) ?>
    </ul>
  </details>
</nav>
<?php else: ?>
<nav class="langues" aria-label="<?= e(T::t('langue.choisir')) ?>">
  <ul class="langues__liste" id="<?= e($id) ?>">
    <?php $liens($entrees) ?>
  </ul>
</nav>
<?php endif ?>
