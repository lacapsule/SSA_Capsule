<?php
/** Carte d'article : photo, date en pastille, titre en lien étiré, résumé. @var array<string, mixed> $a */
use App\Site\Dates;
use App\Site\Texte;
?>
<li class="carte-article">
  <?php $poster = json_decode((string) ($a['videos'] ?? ''), true)[0]['poster'] ?? null; $vignette = $a['image'] ?: $poster; ?>
  <?php if (!empty($vignette)): ?><img class="carte-article__photo" src="<?= e((string) $vignette) ?>" alt="" width="640" height="400" loading="lazy" decoding="async"><?php else: ?><span class="carte-article__photo carte-article__photo--vide" aria-hidden="true"><?php if (!empty($a['videos'])): ?><svg viewBox="0 0 24 24" width="56" height="56" focusable="false"><circle cx="12" cy="12" r="11" fill="currentColor"/><path d="M10 8l6 4-6 4z"/></svg><?php endif ?></span><?php endif ?>
  <div class="carte-article__corps">
    <time class="pastille" datetime="<?= e(Dates::iso((string) $a['date_article'])) ?>"><?= e(Dates::longue((string) $a['date_article'])) ?></time>
    <h3 class="carte-article__titre"><a href="<?= e(lurl('/actualites/' . $a['slug'] . '/')) ?>"<?= langue_attr($a, 'titre') ?>><?= e((string) $a['titre']) ?></a></h3>
    <?php if (!empty($a['resume'])): ?><p<?= langue_attr($a, 'resume') ?>><?= e(Texte::couper((string) $a['resume'], 120)) ?></p><?php endif ?>
  </div>
</li>
