<?php
include __DIR__.'/partials_header.php';
$rel = db_all("SELECT * FROM syrve_releases ORDER BY datetime(released_at) DESC");
?>
<h3>Релізи Syrve</h3>
<ul class="list-group">
    <?php foreach($rel as $r): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <a href="release_view.php?slug=<?=urlencode($r['slug'])?>"><?=e($r['name'])?></a>
                <span class="small text-muted"> · <?=e($r['released_at'])?> · <?=e($r['channel'])?></span>
                <?php if($r['is_recommended']): ?><span class="badge text-bg-success ms-2">Рекомендовано</span><?php endif; ?>
                <?php if($r['is_lt']): ?><span class="badge text-bg-secondary ms-1">LT</span><?php endif; ?>
            </div>
            <span class="badge bg-primary rounded-pill">Переглянути</span>
        </li>
    <?php endforeach; if(empty($rel)) echo '<li class="list-group-item">Релізи відсутні.</li>'; ?>
</ul>
<?php include __DIR__.'/partials_footer.php'; ?>
