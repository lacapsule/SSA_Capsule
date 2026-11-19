<?php
/**
 * Champ mot de passe avec bouton « Afficher le mot de passe » (bouton bascule : aria-pressed, libellé fixe).
 * partial('password', ['id' => 'p-new', 'name' => 'password', 'label' => 'Nouveau mot de passe', 'autocomplete' => 'new-password',
 *                      'hint' => 'Au moins 12 caractères.', 'error' => $errors['password'] ?? null]);
 * Le bouton est caché sans JavaScript (attribut hidden retiré par le script) : le champ reste un champ mot de passe ordinaire.
 * @var string $id @var string $name @var string $label @var string $autocomplete @var string|null $hint @var string|null $error
 * @var bool|null $required @var int|null $minlength @var bool|null $disabled
 * @var string|null $forgotUrl lien « Mot de passe oublié ? » à droite de l'étiquette (variante connexion)
 * @var bool|null $force variante .champ-mdp--force (création, changement) : critères cochés en direct et niveau écrit,
 *   règles de App\Securite\PasswordPolicy (même configuration que la validation serveur)
 * @var App\Securite\PasswordPolicy|null $politique politique à appliquer (par défaut : PasswordPolicy::parDefaut())
 * @var string|null $identifiantId id du champ e-mail ou identifiant du même formulaire : ajoute « Ne contient pas votre identifiant »
 */
$hint = isset($hint) && $hint !== '' ? $hint : null;
$error = isset($error) && $error !== '' ? $error : null;
$forgotUrl = isset($forgotUrl) && $forgotUrl !== '' ? $forgotUrl : null;
$disabled = !empty($disabled);
$force = !empty($force);
$identifiantId = isset($identifiantId) && $identifiantId !== '' ? $identifiantId : null;
$politique = $force ? (isset($politique) && $politique instanceof App\Securite\PasswordPolicy ? $politique : App\Securite\PasswordPolicy::parDefaut()) : null;
$describedBy = trim(($hint !== null ? "$id-aide " : '') . ($force ? "$id-criteres " : '') . ($error !== null ? "$id-erreur" : ''));
?>
<?php if ($politique !== null): ?>
<div class="champ champ-mdp champ-mdp--force" data-mdp-force data-mdp-min="<?= $politique->min ?>" data-mdp-max="<?= $politique->max ?>"<?= $identifiantId !== null ? ' data-mdp-identifiant="' . e($identifiantId) . '"' : '' ?>>
<?php else: ?>
<div class="champ champ-mdp">
<?php endif ?>
<?php if ($forgotUrl !== null): ?>
  <div class="champ-mdp__entete">
    <label for="<?= e($id) ?>"><?= e($label) ?></label>
    <a href="<?= e($forgotUrl) ?>">Mot de passe oublié ?</a>
  </div>
<?php else: ?>
  <label for="<?= e($id) ?>"><?= e($label) ?></label>
<?php endif ?>
  <?php if ($hint !== null): ?><p class="champ-aide" id="<?= e($id) ?>-aide"><?= e($hint) ?></p><?php endif ?>
  <?php if ($error !== null): ?><p class="champ-erreur" id="<?= e($id) ?>-erreur"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/></svg><?= e($error) ?></p><?php endif ?>
  <div class="mdp">
    <input id="<?= e($id) ?>" name="<?= e($name) ?>" type="password" autocomplete="<?= e($autocomplete) ?>" spellcheck="false" autocapitalize="off"
      <?= ($required ?? true) ? 'required' : '' ?><?= isset($minlength) ? ' minlength="' . (int) $minlength . '"' : '' ?>
      <?= $describedBy !== '' ? 'aria-describedby="' . e($describedBy) . '"' : '' ?><?= $error !== null ? ' aria-invalid="true"' : '' ?><?= $disabled ? ' disabled' : '' ?>>
    <button class="mdp-bascule" type="button" data-mdp="<?= e($id) ?>" aria-controls="<?= e($id) ?>" aria-pressed="false" hidden>
      <svg class="mdp-icone mdp-icone--masque" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
      <svg class="mdp-icone mdp-icone--visible" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6C3.7 8.5 2 12 2 12s3.5 7 10 7a9.6 9.6 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
      <span class="mdp-libelle">Afficher le mot de passe</span>
    </button>
  </div>
<?php if ($politique !== null): ?>
  <div class="mdp-force" data-niveau="0" hidden>
    <span class="mdp-force__jauge" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
    <p class="mdp-force__niveau">Niveau : <strong data-mdp-niveau>à saisir</strong></p>
  </div>
  <ul class="mdp-criteres" id="<?= e($id) ?>-criteres">
    <?php foreach ($politique->criteres('', $identifiantId !== null ? '' : null) as $critere): ?>
    <li data-critere="<?= e($critere['id']) ?>" data-ok="false">
      <svg class="mdp-critere-icone mdp-critere-icone--ko" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.5"/></svg>
      <svg class="mdp-critere-icone mdp-critere-icone--ok" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.5"/><path d="m8 12.5 2.75 2.75L16.5 9.5"/></svg>
      <span><?= e($critere['libelle']) ?><span class="mdp-libelle" data-etat></span></span>
    </li>
    <?php endforeach ?>
  </ul>
  <p class="mdp-libelle" role="status" data-mdp-annonce></p>
<?php endif ?>
</div>
