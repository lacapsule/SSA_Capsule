<?php
/**
 * Bande d'appel au contact (brique sections/appel-illustre) : titre, phrase, actions ; à côté, le personnage du collectif
 * qui sort de son rond jaune (signe fort du client), ou l'illustration de contact du site actuel.
 * @var string $titre @var string $texte (facultatif) @var list<array{href: string, libelle: string, secondaire?: bool}> $actions
 * @var bool $personnage (facultatif, vrai par défaut) @var string $image personnage à montrer (un par page : rule web-design,
 * « Un personnage, une place ») @var bool $mainDroite le bras sort du rond à droite @var bool $mainGauche le bras sort du rond à gauche @var bool $serre buste dont le bras descend jusqu'en bas du dessin : réduit pour que le coude reste dans le rond @var bool $compact bande moins haute
 * @var bool $coordonnees téléphone et e-mail du collectif sous le titre
 */
$texte = isset($texte) && is_string($texte) ? $texte : '';
$personnage = !isset($personnage) || $personnage !== false;
$image = isset($image) && is_string($image) ? $image : '/assets/img/illustration-collectif.svg';
$mainDroite = !empty($mainDroite);
$mainGauche = !empty($mainGauche);
$serre = !empty($serre);
[$largeur, $hauteur] = str_ends_with($image, 'illustration-collectif.svg') ? [240, 324] : [223, 313];
?>
<section class="section appel<?= !empty($compact) ? ' appel--compact' : '' ?>" aria-labelledby="appel-titre">
  <div class="appel__inner container">
    <?php if ($personnage): ?>
    <span class="appel__personnage" aria-hidden="true"><span class="portail portail--appel<?= $mainDroite ? ' portail--main-droite' : '' ?><?= $mainGauche ? ' portail--main-gauche' : '' ?><?= $serre ? ' portail--serre' : '' ?><?= str_contains($image, '/personnages/') ? ' portail--buste' : '' ?>"><span class="portail__calque"><img src="<?= e($image) ?>" alt="" width="<?= $largeur ?>" height="<?= $hauteur ?>" loading="lazy" decoding="async"></span></span></span>
    <?php else: ?>
    <span class="appel__illustration" aria-hidden="true"><img src="/assets/img/illustration-contact.svg" alt="" width="913" height="680" loading="lazy" decoding="async"></span>
    <?php endif ?>
    <div>
      <h2 id="appel-titre"><?= e($titre) ?></h2>
      <?php if ($texte !== ''): ?><p class="appel__texte"><?= e($texte) ?></p><?php endif ?>
      <?php if (!empty($coordonnees)): $tel = (string) \App\Site\Site::get('telephone'); $mail = (string) \App\Site\Site::get('email'); // revue du 2026-11-17 : le contact direct, sans passer par le formulaire ?>
      <?php $icone = static fn (string $d): string => '<span class="appel__icone" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>'; ?>
      <ul class="appel__coordonnees">
        <li><a href="tel:+33<?= e(substr((string) preg_replace('/\D/', '', $tel), 1)) ?>"><?= $icone('M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z') ?><?= e($tel) ?></a></li>
        <li><a href="mailto:<?= e($mail) ?>"><?= $icone('M3 6h18v12H3zM3 7l9 6 9-6') ?><?= e($mail) ?></a></li>
      </ul>
      <?php endif ?>
      <p class="appel__actions">
        <?php foreach ($actions as $a): ?><a class="button<?= !empty($a['secondaire']) ? ' button--ghost' : '' ?>" href="<?= e(lurl($a['href'])) ?>"><?= e($a['libelle']) ?></a><?php endforeach ?>
      </p>
    </div>
  </div>
</section>
