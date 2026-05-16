<?php declare(strict_types=1);
/**
 * Fragment template for AJAX-injected post list items (no layout).
 * Used by PostController::fragment().
 */
?><?php foreach ($posts as $i => $post): ?>
<?= $this->renderPostItem($post, false) ?>
<?php endforeach; ?>
