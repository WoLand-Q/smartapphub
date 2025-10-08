<?php
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';

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
        <div class="card card-hover electric electric--on electric--dramatic">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('News')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="news.php"><?=__('See all')?></a>
                </div>

                <?php if ($news): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($news as $n): ?>
                            <li class="list-group-item d-flex align-items-center">
                                <a class="me-2 text-truncate" href="news_view.php?id=<?=$n['id']?>"><?=e($n['title'])?></a>
                                <?php if (!empty($n['tags'])): ?>
                                    <span class="chip ms-1"><?=e($n['tags'])?></span>
                                <?php endif; ?>
                                <span class="small text-muted ms-auto"><?=e($n['created_at'])?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty"><?=__('No news yet')?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RELEASES -->
    <div class="col-lg-6">
        <div class="card card-hover electric electric--on electric--dramatic">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('Releases')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="releases.php"><?=__('Go to list')?></a>
                </div>

                <?php if ($rels): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($rels as $r): ?>
                            <li class="list-group-item d-flex align-items-center">
                                <a class="me-2 text-truncate" href="release_view.php?slug=<?=urlencode($r['slug'])?>"><?=e($r['name'])?></a>
                                <span class="chip"><?=e($r['channel'])?></span>
                                <?php if ($r['is_recommended']): ?><span class="badge bg-success ms-2"><?=__('Recommended')?></span><?php endif; ?>
                                <?php if ($r['is_lt']): ?><span class="badge bg-secondary ms-1"><?=__('LT')?></span><?php endif; ?>
                                <span class="small text-muted ms-auto"><?=e($r['released_at'])?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty"><?=__('No releases yet')?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PLUGINS (grid) test deploy -->
    <div class="col-12">
        <div class="card card-hover electric electric--on electric--dramatic">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('Integrations / Plugins')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="plugins.php"><?=__('All integrations')?></a>
                </div>

                <?php if ($plugs): ?>
                    <div class="row g-2">
                        <?php foreach ($plugs as $p): ?>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded-3 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div class="fw-semibold text-truncate" title="<?=e($p['name'])?>"><?=e($p['name'])?></div>
                                        <?php if (!empty($p['category'])): ?>
                                            <span class="chip"><?=e($p['category'])?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small"><?=e($p['description'])?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty"><?=__('No plugins yet')?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DOCS -->
    <div class="col-12">
        <div class="card card-hover electric electric--on electric--dramatic">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0"><?=__('Documentation')?></h5>
                    <a class="btn btn-sm btn-pill btn-outline-primary ms-auto" href="docs.php"><?=__('Go')?></a>
                </div>

                <?php if ($docs): ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 responsive-cards">
                            <thead>
                            <tr>
                                <th><?=__('Title')?></th>
                                <th><?=__('Tags')?></th>
                                <th class="text-end"><?=__('Updated')?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($docs as $d): ?>
                                <tr>
                                    <td class="text-truncate" style="max-width:38ch" title="<?=e($d['title'])?>"><?=e($d['title'])?></td>
                                    <td class="text-muted small"><?=e($d['tags'])?></td>
                                    <td class="text-end text-muted small"><?=e($d['updated_at'])?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty"><?=__('No documents yet')?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
