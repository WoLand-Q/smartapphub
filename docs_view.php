<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';

$id  = (int)($_GET['id'] ?? 0);
$doc = db_one("SELECT * FROM docs WHERE id=?", [$id]);
if (!$doc) {
    echo "<div class='container my-5'><div class='alert alert-danger'>".__('Document not found')."</div></div>";
    include __DIR__.'/partials_footer.php';
    exit;
}
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item"><a href="docs.php"><?=__('Documentation')?></a></li>
        <li class="breadcrumb-item active"><?=e($doc['title'])?></li>
    </ol>
</nav>

<div class="card card-hover">
    <div class="card-body">
        <h3 class="card-title mb-3"><?=e($doc['title'])?></h3>

        <?php if (!empty($doc['tags'])): ?>
            <div class="mb-3">
                <?php foreach (explode(',', $doc['tags']) as $tag): ?>
                    <span class="badge bg-secondary"><?=e(trim($tag))?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <?= md($doc['description_md'] ?? '') ?>
        </div>

        <?php if (!empty($doc['link'])): ?>
            <p><a href="<?=e($doc['link'])?>" target="_blank" class="btn btn-outline-primary"><?=__('Open link')?></a></p>
        <?php endif; ?>

        <?php if (!empty($doc['file_path'])): ?>
            <p><a href="<?=e($doc['file_path'])?>" class="btn btn-primary" download><?=__('Download file')?></a></p>
        <?php endif; ?>

        <p class="text-muted small"><?=__('Updated')?>: <?=e($doc['updated_at'])?></p>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
