<?php
/**
 * Le projet, piste 2 « modernisée » (maquette docs/maquettes/pistes/2-modernisee/projet.html), réunie le 2026-11-17 avec
 * l'ancienne page « Qui sommes-nous » (décision de Kouzia) : titre et photo, buts avec le personnage, schéma « Kezako »
 * et les trois étapes, engagement à côté de la photo de groupe, vidéo de présentation, puis le collectif (personnage,
 * présentation du groupe, structures porteuses et financeurs sur le bleu de marque, objectif) et l'appel au contact.
 * @var array<string, string> $t @var string $path @var array{src: string, poster: string, titre: string, slug: string, resume: string}|null $video
 */
use App\Langue\Contenu;
use App\Pages\Pages;
use App\Site\Vignette;

$accueil = Contenu::traduire('page', '/', Pages::textes('/'));
$video = isset($video) && is_array($video) ? $video : null;
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo'], 'photo' => '/assets/img/bandeau-projet.webp', 'filTitre' => t('menu.projet')]);
?>
<section class="section buts" aria-labelledby="pourquoi-titre">
  <div class="buts__inner container">
    <span class="portail portail--buts portail--main-gauche" aria-hidden="true"><span class="portail__calque"><img src="/assets/img/illustration-projet.svg" alt="" width="240" height="324" loading="lazy" decoding="async"></span></span>
    <div class="prose">
      <h2 id="pourquoi-titre"><?= e($t['pourquoi_titre']) ?></h2>
      <p><?= e($t['pourquoi_texte']) ?></p>
      <ul class="puces">
        <?php foreach (['but_1', 'but_2', 'but_3'] as $cle): ?><li><?= e($t[$cle]) ?></li><?php endforeach ?>
      </ul>
      <p><?= e($t['objectif']) ?></p>
    </div>
  </div>
</section>

<section class="section schema-projet" aria-labelledby="concret-titre">
  <div class="container">
    <h2 id="concret-titre"><?= e($t['concret_titre']) ?></h2>
    <figure>
      <img <?= Vignette::attributs('/assets/img/schema-caisse-locale.webp', '(max-width: 80rem) 100vw, 75rem', 1280) ?> alt="<?= e($t['schema_alt']) ?>" width="1920" height="1357" loading="lazy" decoding="async">
      <figcaption><details><summary><?= e($t['schema_lire']) ?></summary><p><?= e($t['schema_texte']) ?></p></details></figcaption>
    </figure>
    <ol class="etapes-chiffrees__liste">
      <?php foreach (['concret_1', 'concret_2', 'concret_3'] as $cle): ?><li><?= e($t[$cle]) ?></li><?php endforeach ?>
    </ol>
  </div>
</section>

<section class="section engage" aria-labelledby="engagement-titre">
  <div class="engage__inner container">
    <img class="engage__photo" <?= Vignette::attributs('/assets/img/groupe.webp', '(max-width: 80rem) 100vw, 75rem', 1280) ?> alt="" width="1920" height="600" loading="lazy" decoding="async">
    <div>
      <h2 id="engagement-titre"><?= e($t['engagement_titre']) ?></h2>
      <p><?= e($t['engagement_texte']) ?></p>
    </div>
  </div>
</section>

<?php if ($video !== null): ?>
<section class="section projet-video" aria-labelledby="video-titre">
  <div class="projet-video__inner container">
    <div>
      <h2 id="video-titre"><?= e($t['video_titre']) ?></h2>
      <?php if ($video['resume'] !== ''): ?><p class="projet-video__texte"><?= e($video['resume']) ?></p><?php endif ?>
      <p><a class="lien-fleche" href="<?= e(lurl('/actualites/' . $video['slug'] . '/')) ?>"><?= e(t('actualites.article_video')) ?><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></p>
    </div>
    <figure class="article-media article-media--video projet-video__media" data-video>
      <video controls preload="none" playsinline<?= $video['poster'] !== '' ? ' poster="' . e(Vignette::chemin($video['poster'], 960)) . '"' : '' ?> width="1280" height="720"><source src="<?= e($video['src']) ?>" type="video/mp4"></video>
      <button type="button" class="video-lecture" hidden data-video-lecture><svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z" fill="currentColor"/></svg><span><?= e(t('actualites.lire_video')) ?><?php if ($video['duree'] > 0): ?> <small><?= e(\App\Site\Dates::duree($video['duree'])) ?></small><?php endif ?></span></button>
    </figure>
  </div>
</section>
<?php endif ?>

<section class="section buts buts--inverse collectif-groupe" id="collectif" aria-labelledby="groupe-titre">
  <div class="buts__inner container">
    <span class="portail portail--buts portail--buste portail--miroir" aria-hidden="true"><span class="portail__calque"><img src="/assets/img/personnages/groupe.svg" alt="" width="217" height="313" loading="lazy" decoding="async"></span></span>
    <div class="prose">
      <p class="sur-titre"><?= e($accueil['collectif_titre']) ?></p>
      <h2 id="groupe-titre"><?= e($accueil['collectif_soustitre']) ?></h2>
      <p class="qui__texte"><?= e($accueil['collectif_texte']) ?></p>
    </div>
  </div>
</section>

<section class="section qui qui--clair" aria-labelledby="structures-titre">
  <div class="container">
    <h2 id="structures-titre"><?= e($t['structures_titre']) ?></h2>
    <?php partial('site/structures', ['textes' => $t, 'variante' => 'large']) ?>
    <?php partial('site/financeurs') ?>
  </div>
</section>

<section class="section objectif" aria-labelledby="objectif-titre">
  <div class="objectif__inner container">
    <p class="sur-titre"><?= e($t['objectif_titre']) ?></p>
    <h2 class="objectif__texte" id="objectif-titre"><?= e($t['objectif_texte']) ?></h2>
  </div>
</section>

<?php partial('site/appel', ['titre' => $t['contact_titre'], 'actions' => [['href' => '/contact/', 'libelle' => t('contact.nous_contacter')], ['href' => '/lieux-conventionnes/', 'libelle' => t('accueil.voir_lieux'), 'secondaire' => true]], 'image' => '/assets/img/personnages/projet-appel.svg', 'compact' => true, 'coordonnees' => true]) ?>
