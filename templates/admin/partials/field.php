<?php
/**
 * Champ de formulaire d'une ressource. Ordre GOV.UK : libellé, aide, erreur, puis le contrôle
 * (case interrupteur : erreur au-dessus, aide de la case sous son libellé).
 * @var string $name @var array<string, mixed> $f (label, type, required, hint, options, step, max) @var mixed $value
 * @var ?string $error @var string $key ressource (suggestions d'adresses) @var array<string, mixed> $values toutes les valeurs
 */
$id = 'f-' . $name;
$type = (string) $f['type'];
$hint = (string) ($f['hint'] ?? '');
$describedBy = trim(($type === 'address' ? "$id-geo " : '') . ($hint !== '' ? "$id-hint " : '') . ($error ? "$id-error" : ''));
$aria = ($describedBy !== '' ? ' aria-describedby="' . $describedBy . '"' : '') . ($error ? ' aria-invalid="true"' : '');
$req = !empty($f['required']) ? ' required' : '';
// large : champ sur toute la largeur de la grille (titre, lieu) ; ajout du projet SSA.
$wide = in_array($type, ['textarea', 'image', 'pdf', 'address'], true) || !empty($f['large']) ? ' adm-champ--large' : '';
?>
<div class="adm-champ adm-champ--pile<?= $wide ?>">
  <?php if ($type === 'checkbox'): ?>
  <?php if ($error): ?><p class="adm-champ__erreur" id="<?= $id ?>-error">Erreur : <?= e($error) ?></p><?php endif ?>
  <div class="adm-reglage adm-reglage--champ">
    <label class="adm-reglage__titre" for="<?= $id ?>"><?= e((string) $f['label']) ?></label>
    <span class="adm-interrupteur">
      <input class="adm-interrupteur__controle" type="checkbox" role="switch" id="<?= $id ?>" name="<?= e($name) ?>" value="1"<?= (int) $value === 1 ? ' checked' : '' ?><?= $aria ?>>
      <span class="adm-interrupteur__etat" aria-hidden="true"><span class="adm-interrupteur__oui">Oui</span><span class="adm-interrupteur__non">Non</span></span>
    </span>
  </div>
  <?php if ($hint !== ''): ?><p class="adm-champ__aide" id="<?= $id ?>-hint"><?= e($hint) ?></p><?php endif ?>
  <?php else: ?>
  <?php /* En-tête du champ (libellé, aide, erreur) : dans une grille, les contrôles voisins restent alignés (admin-ssa.css). */ ?>
  <div class="adm-champ__tete">
    <label for="<?= $id ?>"><?= e((string) $f['label']) ?><?= !empty($f['required']) ? ' <span class="adm-champ__requis">(obligatoire)</span>' : '' ?></label>
    <?php if ($hint !== ''): ?><p class="adm-champ__aide" id="<?= $id ?>-hint"><?= e($hint) ?></p><?php endif ?>
    <?php if ($error): ?><p class="adm-champ__erreur" id="<?= $id ?>-error">Erreur : <?= e($error) ?></p><?php endif ?>
  </div>
  <?php if ($type === 'textarea'): ?>
  <textarea id="<?= $id ?>" name="<?= e($name) ?>" rows="<?= (int) ($f['rows'] ?? 3) ?>"<?= isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '' ?><?= $req . $aria ?>><?= e((string) $value) ?></textarea>
  <?php elseif ($type === 'select'): ?>
  <select id="<?= $id ?>" name="<?= e($name) ?>"<?= $req . $aria ?>>
    <?php foreach ($f['options'] as $optValue => $optLabel): ?>
    <option value="<?= e((string) $optValue) ?>"<?= (string) $value === (string) $optValue ? ' selected' : '' ?>><?= e((string) $optLabel) ?></option>
    <?php endforeach ?>
  </select>
  <?php elseif ($type === 'image' || $type === 'pdf'): ?>
  <?php if ($value): ?>
  <div class="file-current">
    <?php if ($type === 'image'): ?><img src="<?= e((string) $value) ?>" alt="" height="64" loading="lazy" decoding="async"><?php else: ?><a href="<?= e((string) $value) ?>" target="_blank" rel="noopener">Ouvrir le fichier actuel<span class="adm-masque"> (nouvel onglet)</span></a><?php endif ?>
    <label class="file-current__remove" for="<?= $id ?>-remove"><input type="checkbox" id="<?= $id ?>-remove" name="<?= e($name) ?>_remove" value="1"> Retirer <?= $type === 'image' ? 'l\'image' : 'le fichier' ?></label>
  </div>
  <?php endif ?>
  <input type="file" id="<?= $id ?>" name="<?= e($name) ?>" accept="<?= $type === 'image' ? 'image/png,image/jpeg,image/webp' : 'application/pdf' ?>"<?= !$value ? $req : '' ?><?= $aria ?>>
  <?php elseif ($type === 'address'): ?>
  <?php
    // État de la position sur la carte, d'après la fiche enregistrée ; adresses.js le met à jour pendant la saisie.
    $onMap = ($values['latitude'] ?? null) !== null && ($values['latitude'] ?? '') !== '';
    [$geoClass, $geoText] = match (true) {
        $onMap && (string) $value === '' => ['', 'Sur la carte (position reprise de la carte du collectif), adresse à compléter.'],
        $onMap => ['ok', 'Sur la carte : l\'adresse a été trouvée.'],
        (string) $value !== '' => ['off', 'Pas sur la carte : adresse introuvable. Corrigez-la en choisissant une suggestion.'],
        default => ['', 'Sans adresse, la fiche figure dans la liste mais pas sur la carte.'],
    };
  ?>
  <?php // remplit : champs remplis par la suggestion choisie (code postal, commune), ajout du projet SSA. ?>
  <div class="combo" data-address data-address-source="<?= e((string) ($f['suggestions'] ?? '/admin/adresses?ressource=' . ($key ?? ''))) ?>"<?php foreach ((array) ($f['remplit'] ?? []) as $part => $champ): ?> data-address-fill-<?= e((string) $part) ?>="f-<?= e((string) $champ) ?>"<?php endforeach ?>>
    <input id="<?= $id ?>" name="<?= e($name) ?>" type="text" value="<?= e((string) $value) ?>" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="<?= $id ?>-list" data-address-input<?= $req . $aria ?>>
    <ul class="combo__list" id="<?= $id ?>-list" role="listbox" aria-label="Adresses suggérées" hidden data-address-list></ul>
  </div>
  <p class="geo-status<?= $geoClass !== '' ? ' geo-status--' . $geoClass : '' ?>" id="<?= $id ?>-geo" aria-live="polite" data-address-status><?= e($geoText) ?></p>
  <?php else: ?>
  <input id="<?= $id ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" value="<?= e((string) $value) ?>"<?= isset($f['step']) ? ' step="' . e((string) $f['step']) . '"' : '' ?><?= isset($f['max']) && $type === 'text' ? ' maxlength="' . (int) $f['max'] . '"' : '' ?><?php /* Coordonnées d'une fiche, pas de la personne connectée : pas de remplissage automatique. */ ?><?= in_array($type, ['email', 'tel', 'url'], true) ? ' autocomplete="off"' : '' ?><?= $req . $aria ?>>
  <?php endif ?>
  <?php endif ?>
</div>
