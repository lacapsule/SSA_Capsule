<?php

/** @var App\Dto\ArticleDTO[] $articles */
/** @var string $createUrl */
/** @var string $editBaseUrl */
/** @var string $deleteBaseUrl */
/** @var string|null $csrf */

declare(strict_types=1);

$e    = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$join = static fn(string $base, string|int $id) => rtrim($base, '/') . '/' . rawurlencode((string)$id);

?>
<section class="articles">
    <h1>Gestion des articles</h1>
    <p><a href="<?= $e($createUrl) ?>">Créer un article</a></p>

    <?php if (empty($articles)): ?>
        <p>Aucun article trouvé.</p>
    <?php else: ?>
        <div class="wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Résumé</th>
                        <th>Date</th>
                        <th>Auteur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <?php
                        // Date tolérante
                        $dateStr = '';
                        if (!empty($article->date_article)) {
                            try {
                                $dateStr = (new DateTime($article->date_article))->format('d/m/Y');
                            } catch (\Throwable) {
                                $dateStr = (string)$article->date_article;
                            }
                        }
                        $editUrl   = $join($editBaseUrl, $article->id);
                        $deleteUrl = $join($deleteBaseUrl, $article->id);
                        ?>
                        <tr>
                            <td><?= $e($article->id) ?></td>
                            <td><?= $e($article->titre) ?></td>
                            <td><?= $e($article->resume ?? '') ?></td>
                            <td><?= $e($dateStr) ?></td>
                            <td><?= $e($article->author ?? 'Inconnu') ?></td>
                            <td class="buttons">
                                <a href="<?= $e($editUrl) ?>">Modifier</a>
                                <form action="<?= $e($deleteUrl) ?>" method="post" style="display:inline;"
                                    onsubmit="return confirm('Supprimer cet article ?');">
                                    <?= \CapsuleLib\Security\CsrfTokenManager::insertInput(); ?>
                                    <button type="submit" style="background-color:#ED7F7F;">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
