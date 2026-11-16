<?php
/**
 * Accueil, piste 2 « modernisée » (validation 3 du 2026-11-16, maquette docs/maquettes/pistes/2-modernisee/accueil.html) :
 * titre et mosaïque de photos de La Collective, prochains événements, étapes, bande de photos, actualités
 * (une en grand), Qui sommes-nous avec les structures, financeurs, appel au contact.
 * @var array<string, string> $t textes de la page @var list<array> $evenements @var list<array> $articles
 * @var array<string, string> $collectif textes de la page Qui sommes-nous (structures, objectif), source unique
 */
use App\Core\Db;
use App\Site\Dates;
use App\Site\Site;
use App\Site\Texte;

$choix = (array) Site::get('accueil', []);
$existe = static fn (string $chemin): bool => is_file(ROOT . '/public' . $chemin);
$mosaique = array_values(array_filter((array) ($choix['mosaique'] ?? []), $existe));
$bande = array_values(array_filter((array) ($choix['bande'] ?? []), $existe));
$logos = (array) ($choix['logos_structures'] ?? []);
$fleche = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$financeurs = Db::run("SELECT nom, url, logo FROM partenaires WHERE groupe = 'financeur' ORDER BY position")->fetchAll();
// Lien de chaque structure : son site, lu dans la table des partenaires (source unique, modifiable dans l'administration).
$sites = [];
foreach (Db::run('SELECT logo, url FROM partenaires')->fetchAll() as $l) {
    if (preg_match('#^https?://#i', (string) $l['url']) === 1) {
        $sites[(string) $l['logo']] = (string) $l['url'];
    }
}
?>
<section class="ouverture" aria-labelledby="titre-page">
  <div class="ouverture__inner container">
    <div class="ouverture__texte">
      <h1 id="titre-page"><?= e($t['titre']) ?></h1>
      <p class="ouverture__devise"><?= e($t['devise']) ?></p>
      <p class="ouverture__actions">
        <a class="button" href="<?= e(lurl('/agenda/')) ?>"><?= e(t('accueil.voir_agenda')) ?></a>
        <a class="button button--ghost" href="<?= e(lurl('/lieux-conventionnes/')) ?>"><?= e(t('accueil.trouver_lieu')) ?></a>
      </p>
    </div>
    <div class="mosaique">
      <?php foreach ($mosaique as $i => $photo): ?>
      <img src="<?= e($photo) ?>" alt="" width="800" height="600"<?= $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?> decoding="async">
      <?php endforeach ?>
      <span class="portail portail--mosaique portail--main-droite" aria-hidden="true"><span class="portail__calque"><img src="/assets/img/illustration-collectif.svg" alt="" width="240" height="324" decoding="async"></span></span>
    </div>
  </div>
</section>

<?php if ($evenements !== []): ?>
<section class="section rendez-vous" aria-labelledby="evenements-titre">
  <div class="rendez-vous__inner container">
    <div class="rendez-vous__tete">
      <h2 id="evenements-titre"><?= e(t('accueil.prochains_evenements')) ?></h2>
      <a class="lien-fleche" href="<?= e(lurl('/agenda/')) ?>"><?= e(t('accueil.tout_agenda')) ?><?= $fleche ?></a>
    </div>
    <ul class="rendez-vous__liste">
      <?php foreach ($evenements as $e): $p = Dates::pastille((string) $e['starts_at']); ?>
      <li class="rendez-vous__item">
        <time class="rendez-vous__date" datetime="<?= e(Dates::iso((string) $e['starts_at'])) ?>"><strong><?= e($p['jour']) ?></strong><span><?= e($p['mois']) ?> <?= e(substr((string) $e['starts_at'], 0, 4)) ?></span></time>
        <div>
          <h3 class="rendez-vous__titre"><a href="<?= e(lurl('/agenda/' . $e['slug'] . '/')) ?>"<?= langue_attr($e, 'title') ?>><?= e((string) $e['title']) ?></a></h3>
          <p class="rendez-vous__infos"><?= e(Dates::longue((string) $e['starts_at'])) ?>, <?= e(Dates::heure((string) $e['starts_at'])) ?><?php if (!empty($e['address'])): ?> · <span<?= langue_attr($e, 'address') ?>><?= e((string) $e['address']) ?></span><?php endif ?></p>
        </div>
      </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
<?php endif ?>

<section class="section etapes-chiffrees" aria-labelledby="etapes-titre">
  <div class="container">
    <h2 id="etapes-titre"><?= e($t['etapes_titre']) ?></h2>
    <ol class="etapes-chiffrees__liste">
      <?php foreach (['etape_1', 'etape_2', 'etape_3'] as $cle): ?><li><?= e($t[$cle]) ?></li><?php endforeach ?>
    </ol>
    <a class="lien-fleche" href="<?= e(lurl('/projet/')) ?>"><?= e(t('accueil.comprendre_projet')) ?><?= $fleche ?></a>
  </div>
</section>

<?php if (count($bande) >= 3): ?>
<?php // Bande défilante (technique de sections/logos-defilants) : CSS pur, pause par case à cocher sans JavaScript,
      // arrêt au survol, piste copiée aria-hidden pour la boucle ; mouvement réduit : bande fixe qui défile au doigt. ?>
<section class="bande-photos" aria-labelledby="galerie-titre">
  <input type="checkbox" id="bande-pause" class="bande-photos__pause visually-hidden">
  <div class="bande-photos__tete container">
    <h2 class="bande-photos__titre" id="galerie-titre"><a class="lien-fleche" href="<?= e(lurl('/galerie/')) ?>"><?= e(t('menu.galerie')) ?><?= $fleche ?></a></h2>
    <label class="bande-photos__pause-libelle" for="bande-pause">
      <svg class="bande-photos__icone-pause" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M8 5v14M16 5v14" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
      <svg class="bande-photos__icone-lecture" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M7 4.5v15l12-7.5z" fill="currentColor"/></svg>
      <?= e(t('site.pause')) ?><span class="visually-hidden"> <?= e(t('accueil.pause_photos')) ?></span>
    </label>
  </div>
  <div class="bande-photos__bandeau">
    <?php foreach ([false, true] as $copie): ?>
    <ul class="bande-photos__piste"<?= $copie ? ' aria-hidden="true"' : '' ?>>
      <?php foreach ($bande as $photo): ?><li><img src="<?= e($photo) ?>" alt="" width="400" height="500" loading="lazy" decoding="async"></li><?php endforeach ?>
    </ul>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>

<?php if ($articles !== []): $une = $articles[0]; $autres = array_slice($articles, 1); ?>
<section class="section actus" aria-labelledby="actualites-titre">
  <div class="container">
    <div class="section__head section__head--lien"><h2 id="actualites-titre"><?= e(t('accueil.dernieres_actualites')) ?></h2><a class="lien-fleche" href="<?= e(lurl('/actualites/')) ?>"><?= e(t('accueil.toutes_actualites')) ?><?= $fleche ?></a></div>
    <div class="actus__grille<?= $autres === [] ? ' actus__grille--seule' : '' ?>">
      <?php foreach ($articles as $i => $a): $poster = json_decode((string) ($a['videos'] ?? ''), true)[0]['poster'] ?? null; $vignette = $a['image'] ?: $poster; ?>
      <?php if ($i === 1): ?><div class="actus__autres"><?php endif ?>
      <article class="actu<?= $i === 0 ? ' actu--une' : '' ?>">
        <?php if (!empty($vignette)): ?><img class="actu__photo" src="<?= e((string) $vignette) ?>" alt="" width="640" height="400" loading="lazy" decoding="async"><?php else: ?><span class="actu__photo actu__photo--vide" aria-hidden="true"></span><?php endif ?>
        <div class="actu__corps">
          <time class="actu__date" datetime="<?= e(Dates::iso((string) $a['date_article'])) ?>"><?= e(Dates::longue((string) $a['date_article'])) ?></time>
          <h3 class="actu__titre"><a href="<?= e(lurl('/actualites/' . $a['slug'] . '/')) ?>"<?= langue_attr($a, 'titre') ?>><?= e((string) $a['titre']) ?></a></h3>
          <?php if (!empty($a['resume'])): ?><p<?= langue_attr($a, 'resume') ?>><?= e(Texte::couper((string) $a['resume'], $i === 0 ? 160 : 90)) ?></p><?php endif ?>
        </div>
      </article>
      <?php endforeach ?>
      <?php if ($autres !== []): ?></div><?php endif ?>
    </div>
  </div>
</section>
<?php endif ?>

<section class="section qui" aria-labelledby="collectif-titre">
  <div class="qui__inner container">
    <div>
      <p class="sur-titre"><?= e($t['collectif_soustitre']) ?></p>
      <h2 id="collectif-titre"><?= e($t['collectif_titre']) ?></h2>
      <p class="qui__texte"><?= e($t['collectif_texte']) ?></p>
      <p class="qui__objectif"><strong><?= e($collectif['objectif_titre']) ?></strong> : <?= e($collectif['objectif_texte']) ?></p>
      <a class="lien-fleche" href="<?= e(lurl('/collectif/')) ?>"><?= e(t('accueil.decouvrir_collectif')) ?><?= $fleche ?></a>
    </div>
    <ul class="structures">
      <?php foreach ([1, 2, 3, 4] as $n): $logo = (string) ($logos[$n - 1] ?? ''); $site = $sites[$logo] ?? ''; $nom = $collectif['structure_' . $n . '_nom']; ?>
      <li class="structure<?= $site !== '' ? ' structure--lien' : '' ?>">
        <?php if ($logo !== '' && $existe($logo)): ?><img src="<?= e($logo) ?>" alt="" width="160" height="64" loading="lazy" decoding="async"><?php endif ?>
        <p><strong><?php if ($site !== ''): ?><a href="<?= e($site) ?>" rel="noopener"><?= e($nom) ?></a><?php else: ?><?= e($nom) ?><?php endif ?></strong><?= e($collectif['structure_' . $n . '_texte']) ?></p>
      </li>
      <?php endforeach ?>
    </ul>
    <?php if ($financeurs !== []): ?>
    <div class="qui__financeurs">
      <h3 class="sur-titre"><?= e(t('partenaires.financeurs')) ?></h3>
      <ul>
        <?php foreach ($financeurs as $l): $taille = $existe((string) $l['logo']) ? getimagesize(ROOT . '/public' . $l['logo']) : false; $w = $taille ? (int) round(80 * $taille[0] / max(1, $taille[1])) : 80; $img = '<img src="' . e((string) $l['logo']) . '" alt="' . e((string) $l['nom']) . '" width="' . $w . '" height="80" loading="lazy" decoding="async">'; ?>
        <li><?php if (!empty($l['url'])): ?><a href="<?= e((string) $l['url']) ?>" rel="noopener"><?= $img ?></a><?php else: ?><?= $img ?><?php endif ?></li>
        <?php endforeach ?>
      </ul>
    </div>
    <?php endif ?>
  </div>
</section>

<section class="section appel" aria-labelledby="contact-titre">
  <div class="appel__inner container">
    <span class="appel__illustration" aria-hidden="true"><img src="/assets/img/illustration-contact.svg" alt="" width="913" height="680" loading="lazy" decoding="async"></span>
    <div>
      <h2 id="contact-titre"><?= e($t['contact_titre']) ?></h2>
      <p class="appel__actions"><a class="button" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a></p>
    </div>
  </div>
</section>
