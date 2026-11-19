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
      <p class="appel__coordonnees"><a href="tel:+33<?= e(substr((string) preg_replace('/\D/', '', $tel), 1)) ?>"><?= e($tel) ?></a><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></p>
      <?php endif ?>
      <p class="appel__actions">
        <?php foreach ($actions as $a): ?><a class="button<?= !empty($a['secondaire']) ? ' button--ghost' : '' ?>" href="<?= e(lurl($a['href'])) ?>"><?= e($a['libelle']) ?></a><?php endforeach ?>
      </p>
    </div>
  </div>
</section>
