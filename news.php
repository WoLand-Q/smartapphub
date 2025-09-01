<?php
include __DIR__.'/partials_header.php';

/* ----- параметры пагинации ----- */
$allowed_per = [5,10,20];
$per = (int)($_GET['per'] ?? 10);
if(!in_array($per, $allowed_per, true)) $per = 10;

$page = max(1, (int)($_GET['page'] ?? 1));

/* всего записей */
$totalRow = db_one("SELECT COUNT(*) AS c FROM news");
$total = (int)($totalRow['c'] ?? 0);
$pages = max(1, (int)ceil($total / $per));
if($page > $pages) $page = $pages;

$offset = ($page - 1) * $per;

/* текущая страница */
$news = db_all("
    SELECT * FROM news
    ORDER BY datetime(created_at) DESC
    LIMIT $per OFFSET $offset
");

/* утилита: короткий анонс без HTML */
function news_excerpt(array $row, int $limit = 240): string {
    $html = $row['body_html'] ?: md_to_html($row['body_md']);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', trim($text));
    if (mb_strlen($text,'UTF-8') > $limit){
        $text = mb_substr($text, 0, $limit, 'UTF-8').'…';
    }
    return $text ?: '—';
}
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item active"><?=__('News')?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <h3 class="mb-0"><?=__('News')?></h3>
    <form method="get" class="d-flex align-items-center gap-2">
        <label class="text-muted small"><?=__('Per page')?>:</label>
        <select class="form-select form-select-sm" name="per" onchange="this.form.submit()">
            <?php foreach($allowed_per as $n): ?>
                <option value="<?=$n?>" <?=$n===$per?'selected':''?>><?=$n?></option>
            <?php endforeach; ?>
        </select>
        <?php if($page>1): ?><input type="hidden" name="page" value="<?=$page?>"><?php endif; ?>
    </form>
</div>

<?php foreach($news as $n): ?>
    <div class="card card-hover mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                <h5 class="card-title mb-0">
                    <a href="news_view.php?id=<?=$n['id']?>"><?=e($n['title'])?></a>
                </h5>
                <?php if(!empty($n['tags'])):
                    foreach(explode(',', $n['tags']) as $tg): ?>
                        <span class="chip"><?=e(trim($tg))?></span>
                    <?php endforeach; endif; ?>
                <span class="badge badge-muted ms-auto">
                    <?=e($n['created_at'])?><?= $n['author'] ? ' · '.e($n['author']) : '' ?>
                </span>
            </div>

            <p class="mb-2 text-muted"><?= e(news_excerpt($n)) ?></p>

            <a class="btn btn-sm btn-outline-primary btn-pill" href="news_view.php?id=<?=$n['id']?>"><?=__('Read')?></a>
        </div>
    </div>
<?php endforeach; if(empty($news)) echo '<p class="text-muted">'.__('No news').'</p>'; ?>

<?php
/* ---------- пагинация ---------- */
if($pages > 1):
    $win = 2;
    $start = max(1, $page - $win);
    $end   = min($pages, $page + $win);
    ?>
    <nav aria-label="<?=__('Pagination')?>">
        <ul class="pagination">
            <li class="page-item <?=$page<=1?'disabled':''?>">
                <a class="page-link" href="?page=<?=max(1,$page-1)?>&per=<?=$per?>" aria-label="<?=__('Previous')?>">&laquo;</a>
            </li>

            <?php if($start > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=1&per=<?=$per?>">1</a></li>
                <?php if($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <?php endif; ?>

            <?php for($i=$start; $i<=$end; $i++): ?>
                <li class="page-item <?=$i===$page?'active':''?>">
                    <a class="page-link" href="?page=<?=$i?>&per=<?=$per?>"><?=$i?></a>
                </li>
            <?php endfor; ?>

            <?php if($end < $pages): ?>
                <?php if($end < $pages-1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="?page=<?=$pages?>&per=<?=$per?>"><?=$pages?></a></li>
            <?php endif; ?>

            <li class="page-item <?=$page>=$pages?'disabled':''?>">
                <a class="page-link" href="?page=<?=min($pages,$page+1)?>&per=<?=$per?>" aria-label="<?=__('Next')?>">&raquo;</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php include __DIR__.'/partials_footer.php'; ?>
