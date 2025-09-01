<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';

$page_title = __('Plugins');
$q          = trim((string)($_GET['q'] ?? ''));
$user_syrve = normalize_syrve_short((string)($_GET['syrve'] ?? ''));

include __DIR__.'/partials_header.php';

/* ---------- helpers ---------- */

/** Подсветка терминов запроса в уже-экранированном тексте */
function highlight_safe(string $text, string $q): string {
    $out   = e($text);
    $terms = preg_split('/\s+/u', trim($q));
    $terms = array_values(array_filter(array_map(fn($t)=>mb_strtolower($t, 'UTF-8'), $terms)));
    if (!$terms) return $out;
    foreach ($terms as $t) {
        if ($t === '' || mb_strlen($t,'UTF-8') < 2) continue;
        $re = '/('.preg_quote($t, '/').')/iu';
        $out = preg_replace($re, '<mark>$1</mark>', $out);
    }
    return $out;
}

/** Превращает текст запроса в FTS5 MATCH (prefix-поиск), поддерживает cat:/category: */
function build_fts_match(string $q): string {
    $q = trim($q);
    if ($q === '') return '';
    $raw = preg_split('/\s+/u', $q);
    $parts = [];
    foreach ($raw as $tok) {
        $t = trim($tok);
        if ($t === '' || mb_strlen($t,'UTF-8') < 2) continue;
        $t = preg_replace('/[^\p{L}\p{N}_\-]+/u','',$t);
        if ($t === '') continue;

        if (stripos($t, 'cat:') === 0 || stripos($t, 'category:') === 0) {
            $term = substr($t, strpos($t, ':') + 1);
            if ($term !== '') $parts[] = "tags:$term*";
        } else {
            $parts[] = "(title:$t* OR body:$t* OR tags:$t*)";
        }
    }
    return $parts ? implode(' AND ', $parts) : '';
}

/* ---------- поиск плагинов ---------- */

$plugins = [];
if ($q !== '') {
    $match = build_fts_match($q);
    try {
        if ($match !== '') {
            $plugins = db_all("
                SELECT p.*, bm25(sa) AS score
                FROM search_all sa
                JOIN plugins p ON p.id = sa.rid
                WHERE sa.type='plugin' AND sa MATCH :m
                ORDER BY score ASC, LOWER(p.category), LOWER(p.name)
                LIMIT 500
            ", [':m' => $match]);
        }
    } catch (Throwable $e) {
        $plugins = [];
    }

    if (!$plugins) {
        $plugins = db_all("
            SELECT p.*
            FROM plugins p
            WHERE (p.name LIKE :q OR p.description LIKE :q OR p.category LIKE :q)
            ORDER BY LOWER(p.category), LOWER(p.name)
        ", [':q' => '%'.$q.'%']);
    }
} else {
    $plugins = db_all("
        SELECT p.* FROM plugins p
        ORDER BY LOWER(p.category), LOWER(p.name)
    ");
}

/* --- все версии для выведенных плагинов --- */
$pluginIds = array_map(fn($p)=>$p['id'], $plugins);
$versionsByPlugin = [];
if ($pluginIds) {
    $in  = implode(',', array_fill(0, count($pluginIds), '?'));
    $rows = db_all("
        SELECT v.* FROM plugin_versions v
        WHERE v.plugin_id IN ($in)
        ORDER BY datetime(v.released_at) DESC, v.id DESC
    ", $pluginIds);
    foreach ($rows as $r) $versionsByPlugin[$r['plugin_id']][] = $r;
}

/* --- файлы по версии --- */
function files_for_version(int $ver_id): array {
    return db_all("SELECT * FROM plugin_files WHERE plugin_version_id=? ORDER BY id", [$ver_id]);
}

/* --- выбрать лучшую версию под user_syrve --- */
function pick_best_version(array $versions, string $user_short): ?array {
    if (!$versions) return null;
    if ($user_short !== '') {
        foreach ($versions as $v) {
            $minShort = normalize_syrve_short($v['min_syrve'] ?? '');
            if ($minShort === '' || version_compare($user_short, $minShort, '>=')) return $v;
        }
    }
    return $versions[0] ?? null;
}
function channel_badge(string $ch): string {
    $c = strtolower($ch);
    if ($c==='stable')  return '<span class="badge badge-stable">stable</span>';
    if ($c==='beta')    return '<span class="badge badge-beta">beta</span>';
    return '<span class="badge badge-preview">'.e($ch).'</span>';
}

/* --- группы по категории --- */
$groups = [];
foreach ($plugins as $p) {
    $cat = trim($p['category'] ?? ''); if ($cat==='') $cat=__('Other');
    $groups[$cat][] = $p;
}
ksort($groups);
?>
<style>
    /* ====== plugins.php (scoped) ====== */
    .page-plugins .table-responsive{overflow:visible;}
    .page-plugins .dropdown-menu{z-index: 2000; max-width:92vw;}
    .page-plugins .btn-group.position-static{position:static;}
    .page-plugins mark{ background: color-mix(in srgb, var(--sa-brand-500) 28%, transparent); padding:.05em .2em; border-radius:.2rem; }

    .page-plugins .file-chip{
        display:inline-flex; align-items:center; gap:.4rem;
        padding:.24rem .55rem; border:1px solid var(--sa-border);
        border-radius:999px; font-size:.8rem;
        background:linear-gradient(180deg, color-mix(in srgb, var(--sa-card) 86%, #fff) 0%, var(--sa-card) 100%);
        max-width:420px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .page-plugins .file-chip .dot{width:.5rem;height:.5rem;border-radius:999px;background:#94a3b8}
    .page-plugins .file-chip.apk .dot{background:#10b981}
    .page-plugins .file-chip.zip .dot{background:#6366f1}
    .page-plugins .file-chip.jar .dot{background:#f59e0b}
    .page-plugins .overflow-anywhere{overflow-wrap:anywhere; word-break:break-word;}

    .badge-stable{background:rgba(34,197,94,.14);color:#22c55e;border:1px solid rgba(34,197,94,.35);}
    .badge-beta{background:rgba(250,204,21,.14);color:#eab308;border:1px solid rgba(250,204,21,.35);}
    .badge-preview{background:rgba(59,130,246,.14);color:#3b82f6;border:1px solid rgba(59,130,246,.35);}

    @media (max-width: 992px){
        .page-plugins .table-responsive{overflow-x:auto; overflow-y:visible;}
        .page-plugins .file-chip{max-width:260px;}
    }
</style>

<div class="page-plugins">

    <section class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="get" action="plugins.php">
                <div class="col-md-8">
                    <input class="form-control" type="search" name="q"
                           value="<?=e($q)?>" placeholder="<?=__('Search plugins…')?>">
                </div>
                <div class="col-md-4">
                    <input class="form-control" type="text" name="syrve"
                           value="<?=e($user_syrve)?>" placeholder="<?=__('Your Syrve version (e.g., 9.2)')?>">
                </div>
            </form>
            <div class="small text-muted mt-2">
                <?=__('Tip: enter your Syrve version and we’ll highlight the most compatible release.')?>
                <?=__('Supports filters:')?> <code>cat:&lt;<?=__('category')?>&gt;</code> / <code>category:&lt;<?=__('category')?>&gt;</code>.
            </div>
        </div>
    </section>

    <?php foreach ($groups as $cat => $items): ?>
        <h5 class="mb-2"><?=e($cat)?></h5>
        <div class="card mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0 responsive-cards">
                    <thead>
                    <tr>
                        <th style="width:220px"><?=__('Name')?></th>
                        <th><?=__('Description')?></th>
                        <th style="width:140px"><?=__('Recommended version')?></th>
                        <th style="width:120px"><?=__('Min. Syrve')?></th>
                        <th style="width:90px"><?=__('Channel')?></th>
                        <th style="width:120px"><?=__('Date')?></th>
                        <th class="text-end" style="width:260px"><?=__('Downloads')?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $p):
                        $vers  = $versionsByPlugin[$p['id']] ?? [];
                        $best  = pick_best_version($vers, $user_syrve);
                        $files = $best ? files_for_version((int)$best['id']) : [];
                        ?>
                        <tr id="plugin-<?=$p['id']?>">
                            <td class="fw-semibold" data-th="<?=__('Name')?>">
                                <a href="plugin_view.php?slug=<?=urlencode($p['slug'])?>"><?=highlight_safe($p['name'], $q)?></a>
                            </td>
                            <td class="text-truncate" style="max-width:460px" data-th="<?=__('Description')?>">
                                <?=highlight_safe($p['description'], $q)?>
                            </td>
                            <td data-th="<?=__('Recommended version')?>"><?=e($best['version'] ?? '—')?></td>
                            <td data-th="<?=__('Min. Syrve')?>"><?=e($best['min_syrve'] ?? '—')?></td>
                            <td data-th="<?=__('Channel')?>"><?= !empty($best['channel']) ? channel_badge($best['channel']) : '—' ?></td>
                            <td data-th="<?=__('Date')?>"><?=e($best['released_at'] ?? '—')?></td>
                            <td class="text-end" data-th="<?=__('Downloads')?>">
                                <?php if ($best): ?>
                                    <?php if ($files): ?>
                                        <div class="btn-group position-static">
                                            <a class="btn btn-sm btn-primary" href="<?=e($files[0]['file_path'])?>" download><?=__('Download')?></a>
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                    data-bs-toggle="dropdown" data-bs-boundary="viewport"
                                                    aria-expanded="false"><?=__('Files')?></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li class="px-3 py-2">
                                                    <div class="vers-head">v<?=e($best['version'])?></div>
                                                    <div class="vers-meta mb-2"><?=e($best['released_at'])?> · <?=__('min')?> <?=e($best['min_syrve'])?></div>
                                                    <?php foreach ($files as $f): ?>
                                                        <a class="dropdown-item overflow-anywhere" href="<?=e($f['file_path'])?>" download>
                                                            <span class="file-chip <?=e($f['ext'])?>">
                                                                <span class="dot"></span><?=e($f['label'] ?: basename($f['file_path']))?>
                                                            </span>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </li>
                                                <?php
                                                if (count($vers)>1){
                                                    foreach ($vers as $v){
                                                        if ($best && (int)$v['id']===(int)$best['id']) continue;
                                                        $vfiles = files_for_version((int)$v['id']);
                                                        echo '<li><hr class="dropdown-divider"></li><li class="px-3 py-2">';
                                                        echo '<div class="vers-head">v'.e($v['version']).' '.channel_badge($v['channel']).'</div>';
                                                        echo '<div class="vers-meta mb-2">'.e($v['released_at']).' · '. __('min') .' '.e($v['min_syrve']).'</div>';
                                                        if($vfiles){
                                                            foreach($vfiles as $vf){
                                                                echo '<a class="dropdown-item overflow-anywhere" href="'.e($vf['file_path']).'" download>'.
                                                                    '<span class="file-chip '.e($vf['ext']).'"><span class="dot"></span>'.
                                                                    e($vf['label'] ?: basename($vf['file_path'])).'</span></a>';
                                                            }
                                                        } elseif(!empty($v['file_path'])) {
                                                            echo '<a class="dropdown-item" href="'.e($v['file_path']).'" download>'.__('Download').'</a>';
                                                        } else {
                                                            echo '<div class="vers-meta">'.__('No files').'</div>';
                                                        }
                                                        echo '</li>';
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    <?php elseif(!empty($best['file_path'])): ?>
                                        <a class="btn btn-sm btn-primary" href="<?=e($best['file_path'])?>" download><?=__('Download')?></a>
                                    <?php else: ?>
                                        <span class="text-muted"><?=__('No files')?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted"><?=__('No versions yet')?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; if (empty($items)) echo '<tr><td colspan="7" class="text-center text-muted">'.__('Empty').'</td></tr>'; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; if (empty($groups)) : ?>
        <div class="empty"><?=__('Nothing found')?></div>
    <?php endif; ?>

</div>

<script>
    /* Автосабмит поиска + Esc для очистки; и авто data-th */
    (function(){
        const form = document.querySelector('.page-plugins form');
        if (!form) return;
        const q = form.querySelector('input[name="q"]');
        const s = form.querySelector('input[name="syrve"]');
        let t;
        function debouncedSubmit(){ clearTimeout(t); t = setTimeout(()=> form.requestSubmit(), 450); }
        if (q){
            q.addEventListener('input', debouncedSubmit);
            q.addEventListener('keydown', e=>{ if(e.key==='Escape'){ q.value=''; form.requestSubmit(); }});
        }
        if (s){ s.addEventListener('input', debouncedSubmit); }

        // data-th подписи для мобильной таблицы
        document.querySelectorAll('table.responsive-cards').forEach(table=>{
            const heads=[...table.querySelectorAll('thead th')].map(th=>th.textContent.trim());
            table.querySelectorAll('tbody tr').forEach(tr=>{
                tr.querySelectorAll('td').forEach((td,i)=>{ if(!td.hasAttribute('data-th')) td.setAttribute('data-th', heads[i]||''); });
            });
        });
    })();
</script>

<?php include __DIR__.'/partials_footer.php'; ?>
