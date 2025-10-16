<?php
$pictures = [];
for ($i = 1; $i <= 191; $i++) {
    $pictures[] = "/assets/img/gallery/image_" . $i . ".webp";
}

$imagesPerPage = 25;
$totalImages = count($pictures);
$totalPages = ceil($totalImages / $imagesPerPage);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$page = min($page, $totalPages);

$startIndex = ($page - 1) * $imagesPerPage;
$picturesPage = array_slice($pictures, $startIndex, $imagesPerPage);

?>

<section class="gallery" id="gallery">
    <h2>Galerie</h2>
    <div class="gallery-grid">
        <?php foreach ($picturesPage as $img): ?>
            <picture>
                <img
                    src="<?php echo htmlspecialchars($img); ?>"
                    alt="Image de la galerie"
                    loading="lazy"
                    decoding="async"
                    width="200" height="200">
            </picture>
        <?php endforeach; ?>
    </div>

    <!-- Pagination controls -->
    <nav class="gallery-pagination" style="margin-top:2rem;display:flex;justify-content:center;gap:1rem;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="prev-page">&larr; Précédent</a>
        <?php endif; ?>
        <span>Page <?php echo $page; ?> / <?php echo $totalPages; ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="next-page">Suivant &rarr;</a>
        <?php endif; ?>
    </nav>
</section>

<!-- Overlay -->
<div class="galleryOverlay" id="image-overlay" tabindex="-1">
    <span class="close-btn" id="close-overlay">&times;</span>
    <button id="prev-img">&#8592;</button>
    <img id="overlay-img" src="" alt="Image en grand">
    <button id="next-img">&#8594;</button>

</div>