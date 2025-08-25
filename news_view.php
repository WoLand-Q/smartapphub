<?php include __DIR__.'/partials_header.php';
$id = (int)($_GET['id'] ?? 0);
$it = db_one('SELECT * FROM news WHERE id=?', [$id]);
if(!$it){ http_response_code(404); echo '<div class="text-muted">Новина не знайдена</div>'; include __DIR__.'/partials_footer.php'; exit; }
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Огляд</a></li>
        <li class="breadcrumb-item"><a href="news.php">Новини</a></li>
        <li class="breadcrumb-item active"><?=e($it['title'])?></li>
    </ol>
</nav>

<div class="card card-hover">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h3 class="mb-0"><?=e($it['title'])?></h3>
            <?php if(!empty($it['tags'])): foreach(explode(',', $it['tags']) as $tg): ?><span class="chip"><?=e(trim($tg))?></span><?php endforeach; endif; ?>
            <span class="badge bg-secondary ms-auto"><?=e($it['created_at'])?><?= $it['author'] ? ' · '.e($it['author']) : '' ?></span>
            <?php if(is_admin()): ?><a class="btn btn-sm btn-outline-primary ms-2" href="news_edit.php?id=<?=$it['id']?>">Редагувати</a><?php endif; ?>
        </div>
        <article class="prose"><?= $it['body_html'] ?: md_to_html($it['body_md']) ?></article>
    </div>
</div>
<?php include __DIR__.'/partials_footer.php'; ?>
