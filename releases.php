<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';

$rel = db_all("SELECT * FROM syrve_releases ORDER BY datetime(released_at) DESC");
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item active"><?=__('Releases')?></li>
    </ol>
</nav>

<h3 class="mb-3"><?=__('Releases')?></h3>

<ul class="list-group">
    <?php foreach($rel as $r): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <a href="release_view.php?slug=<?=urlencode($r['slug'])?>"><?=e($r['name'])?></a>
                <span class="small text-muted"> · <?=e($r['released_at'])?> · <?=e($r['channel'])?></span>
                <?php if($r['is_recommended']): ?><span class="badge text-bg-success ms-2"><?=__('Recommended')?></span><?php endif; ?>
                <?php if($r['is_lt']):           ?><span class="badge text-bg-secondary ms-1"><?=__('LT')?></span><?php endif; ?>
            </div>
            <span class="badge bg-primary rounded-pill"><?=__('View')?></span>
        </li>
    <?php endforeach; if(empty($rel)) echo '<li class="list-group-item">'.__('No releases yet').'</li>'; ?>
</ul>

<?php include __DIR__.'/partials_footer.php'; ?>
