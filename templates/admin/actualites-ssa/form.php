<?php
/**
 * Fiche d'un article (App\Actualites\ActualitesAdmin), dans le gabarit commun admin/edition/form ; photos
 * supplémentaires, vidéo et liens dans des cartes à part (sans script : cases et champs ordinaires).
 * @var string $title @var ?array $a @var array<string, array<string, mixed>> $champs @var array<string, mixed> $valeurs
 * @var array<string, string> $erreurs @var list<array<string, string>> $liens @var list<array<string, mixed>> $photos
 * @var ?array{src: string, poster: string, duree: int} $video
 */
use App\Admin\Admin;

ob_start(); ?>
<section class="adm-carte adm-carte--simple" aria-labelledby="photos-titre">
  <h2 class="adm-carte__titre" id="photos-titre">Photos supplémentaires</h2>
  <p class="adm-carte__intro">Montrées sous le texte de l'article, dans l'ordre d'envoi.</p>
  <?php if ($photos !== []): ?>
  <ul class="ssa-medias" role="list">
    <?php foreach ($photos as $p): $pid = (int) $p['id']; ?>
    <li class="ssa-medias__photo">
      <img src="<?= e((string) $p['path']) ?>" alt="" width="160" height="120" loading="lazy" decoding="async">
      <div class="adm-champ adm-champ--pile">
        <label for="photo-alt-<?= $pid ?>">Description de la photo</label>
        <input id="photo-alt-<?= $pid ?>" name="photos_alt[<?= $pid ?>]" maxlength="250" value="<?= e((string) $p['alt']) ?>">
      </div>
      <label class="adm-case"><input type="checkbox" name="photos_retirer[]" value="<?= $pid ?>"> Retirer cette photo</label>
    </li>
    <?php endforeach ?>
  </ul>
  <?php endif ?>
  <div class="adm-champ adm-champ--pile adm-champ--large">
    <label for="f-photos">Ajouter des photos</label>
    <p class="adm-champ__aide" id="f-photos-aide">Plusieurs à la fois : JPG, PNG ou WebP, 15 Mo au plus chacune. Converties en WebP.</p>
    <input type="file" id="f-photos" name="photos[]" multiple accept="image/png,image/jpeg,image/webp" aria-describedby="f-photos-aide">
  </div>
</section>
<section class="adm-carte adm-carte--simple" aria-labelledby="video-titre">
  <h2 class="adm-carte__titre" id="video-titre">Vidéo</h2>
  <p class="adm-carte__intro">Montrée en haut de l'article à la place de la photo, lue seulement au clic.</p>
  <?php if ($video !== null): ?>
  <p class="ssa-medias__video"><?php if ($video['poster']): ?><img src="<?= e($video['poster']) ?>" alt="" width="160" height="90" loading="lazy" decoding="async"><?php endif ?><a class="adm-lien" href="<?= e($video['src']) ?>" target="_blank" rel="noopener">Vidéo actuelle<?= $video['duree'] > 0 ? ' (' . e(App\Site\Dates::duree($video['duree'])) . ')' : '' ?><span class="adm-masque"> (nouvel onglet)</span></a></p>
  <label class="adm-case"><input type="checkbox" name="video_remove" value="1"> Retirer la vidéo</label>
  <?php endif ?>
  <div class="adm-grille-champs">
    <div class="adm-champ adm-champ--pile">
      <label for="f-video"><?= $video ? 'Remplacer la vidéo' : 'Envoyer une vidéo' ?></label>
      <p class="adm-champ__aide" id="f-video-aide">MP4 ou WebM, 64 Mo au plus, déjà prête pour le web (le format d'un téléphone convient).</p>
      <input type="file" id="f-video" name="video" accept="video/mp4,video/webm" aria-describedby="f-video-aide">
    </div>
    <div class="adm-champ adm-champ--pile">
      <label for="f-video_poster">Image d'attente</label>
      <p class="adm-champ__aide" id="f-video_poster-aide">Montrée avant la lecture. JPG, PNG ou WebP.</p>
      <input type="file" id="f-video_poster" name="video_poster" accept="image/png,image/jpeg,image/webp" aria-describedby="f-video_poster-aide">
    </div>
  </div>
</section>
<section class="adm-carte adm-carte--simple" aria-labelledby="liens-titre" data-lignes data-lignes-ajout="Ajouter un lien">
  <h2 class="adm-carte__titre" id="liens-titre">Liens</h2>
  <p class="adm-carte__intro">Émission, article de presse, document : montrés sous le texte. Une ligne laissée vide n'ajoute rien.</p>
  <?php if (isset($erreurs['liens'])): ?><p class="adm-champ__erreur" id="liens-erreur">Erreur : <?= e($erreurs['liens']) ?></p><?php endif ?>
  <?php foreach ($liens as $i => $l): ?>
  <fieldset class="ssa-lien" data-ligne>
    <legend class="adm-masque">Lien <?= $i + 1 ?></legend>
    <div class="adm-champ adm-champ--pile">
      <label for="lien-texte-<?= $i ?>">Texte du lien <?= $i + 1 ?></label>
      <input id="lien-texte-<?= $i ?>" name="liens_texte[]" maxlength="160" value="<?= e((string) $l['texte']) ?>">
    </div>
    <div class="adm-champ adm-champ--pile">
      <label for="lien-url-<?= $i ?>">Adresse du lien <?= $i + 1 ?></label>
      <input id="lien-url-<?= $i ?>" name="liens_url[]" type="url" inputmode="url" placeholder="https://" value="<?= e((string) $l['url']) ?>"<?= isset($erreurs['liens']) ? ' aria-describedby="liens-erreur"' : '' ?>>
    </div>
  </fieldset>
  <?php endforeach ?>
</section>
<?php
$apres = (string) ob_get_clean();
$retour = Admin::url('actualites/');
$retourLibelle = 'Actualités';
$action = $a ? Admin::url('actualites/' . (int) $a['id'] . '/') : Admin::url('actualites/nouveau/');
$groupes = ['L\'article' => ['titre', 'date_article', 'heure', 'lieu'], 'Texte' => ['resume', 'description'], 'Photo principale' => ['image', 'image_alt']];
$suppression = $a ? Admin::url('actualites/' . (int) $a['id'] . '/supprimer/') : null;
$voir = $a && (int) $a['is_published'] === 1 && $a['slug'] ? '/actualites/' . $a['slug'] . '/' : null;
$nomFiche = $a ? (string) $a['titre'] : '';
$fichiers = true;
require ROOT . '/templates/admin/edition/form.php';
