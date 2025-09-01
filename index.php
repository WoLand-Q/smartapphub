<?php
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';

// latest data for overview
$news  = db_all("SELECT id,title,tags,created_at FROM news ORDER BY datetime(created_at) DESC LIMIT 5");
$rels  = db_all("SELECT name,slug,released_at,channel,is_recommended,is_lt FROM syrve_releases ORDER BY datetime(released_at) DESC LIMIT 5");
$plugs = db_all("SELECT id,name,description,category FROM plugins WHERE is_active=1 ORDER BY LOWER(category), LOWER(name) LIMIT 8");
$docs  = db_all("SELECT id,title,tags,updated_at FROM docs ORDER BY datetime(updated_at) DESC LIMIT 6");
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb"><li class="breadcrumb-item active"><?=__('Overview')?></li></ol>
</nav>

<div class="row g-3">
    <!-- NEWS -->
    <div class="col-lg-6">
        <div class="card card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('News')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="news.php"><?=__('See all')?></a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($news as $n): ?>
                        <li class="list-group-item d-flex align-items-center">
                            <a class="me-2" href="news_view.php?id=<?=$n['id']?>"><?=e($n['title'])?></a>
                            <?php if($n['tags']): ?><span class="chip ms-1"><?=e($n['tags'])?></span><?php endif; ?>
                            <span class="small text-muted ms-auto"><?=e($n['created_at'])?></span>
                        </li>
                    <?php endforeach; if(empty($news)) echo '<li class="list-group-item text-muted">'.__('No news yet').'</li>'; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- RELEASES -->
    <div class="col-lg-6">
        <div class="card card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('Releases')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="releases.php"><?=__('Go to list')?></a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($rels as $r): ?>
                        <li class="list-group-item d-flex align-items-center">
                            <a class="me-2" href="release_view.php?slug=<?=urlencode($r['slug'])?>"><?=e($r['name'])?></a>
                            <span class="chip"><?=e($r['channel'])?></span>
                            <?php if($r['is_recommended']): ?><span class="badge bg-success ms-2"><?=__('Recommended')?></span><?php endif; ?>
                            <?php if($r['is_lt']): ?><span class="badge bg-secondary ms-1"><?=__('LT')?></span><?php endif; ?>
                            <span class="small text-muted ms-auto"><?=e($r['released_at'])?></span>
                        </li>
                    <?php endforeach; if(empty($rels)) echo '<li class="list-group-item text-muted">'.__('No releases yet').'</li>'; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- PLUGINS (grid) -->
    <div class="col-12">
        <div class="card card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('Integrations / Plugins')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="plugins.php"><?=__('All integrations')?></a>
                </div>

                <div class="grid-cards">
                    <?php foreach($plugs as $p): ?>
                        <div class="p-3 border rounded-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="fw-semibold"><?=e($p['name'])?></div>
                                <?php if(!empty($p['category'])): ?><span class="chip"><?=e($p['category'])?></span><?php endif; ?>
                            </div>
                            <div class="text-muted small"><?=e($p['description'])?></div>
                        </div>
                    <?php endforeach; if(empty($plugs)) echo '<div class="text-muted">'.__('No plugins yet').'</div>'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DOCS -->
    <div class="col-12">
        <div class="card card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('Documentation')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="docs.php"><?=__('Go')?></a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 responsive-cards">
                        <thead><tr><th><?=__('Title')?></th><th><?=__('Tags')?></th><th class="text-end"><?=__('Updated')?></th></tr></thead>
                        <tbody>
                        <?php foreach($docs as $d): ?>
                            <tr>
                                <td><?=e($d['title'])?></td>
                                <td class="text-muted small"><?=e($d['tags'])?></td>
                                <td class="text-end text-muted small"><?=e($d['updated_at'])?></td>
                            </tr>
                        <?php endforeach; if(empty($docs)) echo '<tr><td colspan="3" class="text-muted">'.__('No documents yet').'</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
