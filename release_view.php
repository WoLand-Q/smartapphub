<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';

$slug = (string)($_GET['slug'] ?? '');
$it   = db_one('SELECT * FROM syrve_releases WHERE slug=?', [$slug]);
if(!$it){
    http_response_code(404);
    echo '<div class="text-muted">'.__('Release not found').'</div>';
    include __DIR__.'/partials_footer.php';
    exit;
}
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item"><a href="releases.php"><?=__('Releases')?></a></li>
        <li class="breadcrumb-item active"><?=e($it['name'])?></li>
    </ol>
</nav>

<div class="card card-hover">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h3 class="mb-0"><?=e($it['name'])?></h3>
            <span class="chip"><?=e($it['channel'])?></span>
            <?php if($it['is_recommended']): ?><span class="chip"><?=__('Recommended')?></span><?php endif; ?>
            <?php if($it['is_lt']):           ?><span class="chip"><?=__('LT')?></span><?php endif; ?>
            <span class="badge bg-secondary ms-auto"><?=e($it['released_at'])?></span>
            <?php if(is_admin()): ?>
                <a class="btn btn-sm btn-outline-primary ms-2" href="release_edit.php?id=<?=$it['id']?>"><?=__('Edit')?></a>
            <?php endif; ?>
        </div>

        <?php if(!empty($it['notes_html'])): ?>
            <article class="prose"><?=$it['notes_html']?></article>
        <?php else: ?>
            <div class="text-muted"><?=__('No release notes yet')?></div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
