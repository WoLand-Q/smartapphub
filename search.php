<?php
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';

$q = trim((string)($_GET['q'] ?? ''));
$items = [];
if ($q !== '') {
    // MATCH с подчисткой: подставим * как суффикс
    $query = preg_replace('/\s+/', ' ', $q);
    $match = $query.'*';

    $items = db_all("
      SELECT type, rid, title,
             snippet(search_all, 3, '<mark>','</mark>','…', 14) AS snip
      FROM search_all
      WHERE search_all MATCH ?
      ORDER BY rank
      LIMIT 100
    ", [$match]);
}

// Помощник: получить URL
function result_url(array $row): string {
    if ($row['type']==='news')   return 'news_view.php?id='.(int)$row['rid'];
    if ($row['type']==='doc')    return 'docs_view.php?id='.(int)$row['rid'];
    if ($row['type']==='plugin') {
        $slug = db_one('SELECT slug FROM plugins WHERE id=?', [(int)$row['rid']])['slug'] ?? '';
        return $slug ? 'plugin_view.php?slug='.rawurlencode($slug) : '#';
    }
    return '#';
}

/** Локализованный лейбл для типа результата */
function type_label(string $t): string {
    $t = strtolower($t);
    if ($t === 'news')   return __('News');
    if ($t === 'doc')    return __('Documentation');
    if ($t === 'plugin') return __('Plugin');
    return $t;
}
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item active"><?=__('Search')?></li>
    </ol>
</nav>

<div class="d-flex align-items-center gap-2 mb-2">
    <h3 class="mb-0"><?=__('Search')?></h3>
    <form class="ms-auto" action="search.php" method="get" role="search">
        <input class="form-control" name="q" value="<?=e($q)?>" placeholder="<?=__('Search the hub')?>" autofocus>
    </form>
</div>

<?php if ($q===''): ?>
    <div class="empty"><?=__('Enter a query in the search field above.')?></div>
<?php elseif (!$items): ?>
    <div class="empty"><?= t('Nothing found for “{q}”.', ['q'=>$q]) ?></div>
<?php else: ?>
    <?php foreach ($items as $r): $url = result_url($r); ?>
        <div class="card card-hover mb-2 sa-entrance">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge badge-muted text-uppercase"><?=e(type_label($r['type']))?></span>
                    <a class="fw-semibold" href="<?=$url?>"><?=e($r['title'])?></a>
                </div>
                <div class="text-muted prose"><?= $r['snip'] ?: '' ?></div>
                <div class="mt-2">
                    <a class="btn btn-sm btn-outline-primary" href="<?=$url?>"><?=__('Open')?></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__.'/partials_footer.php'; ?>
