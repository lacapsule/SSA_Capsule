<?php
/** Carte d'article, piste 2 : photo arrondie, date, titre en lien étiré, résumé court. @var array<string, mixed> $a */
use App\Site\Dates;
use App\Site\Texte;
use App\Site\Vignette;
?>
<li class="carte-article">
  <?php $poster = json_decode((string) ($a['videos'] ?? ''), true)[0]['poster'] ?? null; $vignette = (string) ($a['image'] ?: $poster); ?>
  <?php if ($vignette !== ''): ?><span class="carte-article__visuel"><img class="carte-article__photo" <?= Vignette::attributs($vignette, '(max-width: 40rem) 100vw, 24rem', 640) ?> alt="" width="640" height="480" loading="lazy" decoding="async"><?php if ($poster !== null && empty($a['image'])): ?><span class="pastille-video"><svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path d="M4 2.5v11l9-5.5z" fill="currentColor"/></svg><?= e(t('actualites.video')) ?></span><?php endif ?></span><?php else: ?><span class="carte-article__photo carte-article__photo--vide" aria-hidden="true"><?php if (!empty($a['videos'])): ?><svg viewBox="0 0 24 24" width="56" height="56" focusable="false"><circle cx="12" cy="12" r="11" fill="currentColor"/><path d="M10 8l6 4-6 4z"/></svg><?php endif ?></span><?php endif ?>
  <div class="carte-article__corps">
    <time class="carte-article__date" datetime="<?= e(Dates::iso((string) $a['date_article'])) ?>"><?= e(Dates::longue((string) $a['date_article'])) ?></time>
    <h3 class="carte-article__titre"><a href="<?= e(lurl('/actualites/' . $a['slug'] . '/')) ?>"<?= langue_attr($a, 'titre') ?>><?= e(typo((string) $a['titre'])) ?></a></h3>
    <?php if (!empty($a['resume'])): ?><p<?= langue_attr($a, 'resume') ?>><?= e(typo((string) $a['resume'])) ?></p><?php endif ?>
  </div>
</li>
