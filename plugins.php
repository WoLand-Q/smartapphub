<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';

$page_title = 'Інтеграції';
$q          = trim((string)($_GET['q'] ?? ''));
$user_syrve = normalize_syrve_short((string)($_GET['syrve'] ?? ''));

include __DIR__.'/partials_header.php';

/* --- поиск --- */
$params = [];
$where  = '';
if ($q !== '') {
    $where = "WHERE (p.name LIKE :q OR p.description LIKE :q)";
    $params[':q'] = "%$q%";
}

/* --- плагины --- */
$plugins = db_all("
    SELECT p.*
    FROM plugins p
    $where
    ORDER BY LOWER(p.category), LOWER(p.name)
", $params);

/* --- все версии для выведенных плагинов --- */
$pluginIds = array_map(fn($p)=>$p['id'], $plugins);
$versionsByPlugin = [];
if ($pluginIds) {
    $in  = implode(',', array_fill(0, count($pluginIds), '?'));
    $rows = db_all("
        SELECT v.*
        FROM plugin_versions v
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
    $cat = trim($p['category'] ?? ''); if ($cat==='') $cat='Інше';
    $groups[$cat][] = $p;
}
ksort($groups);
?>
    <style>
        /* ====== plugins.php (scoped) ====== */
        .page-plugins .table-responsive{overflow:visible;} /* чтобы dropdown не клипался */
        .page-plugins .dropdown-menu{z-index: 2000; max-width:92vw;}
        .page-plugins .btn-group.position-static{position:static;} /* для корректного boundary */

        .page-plugins .file-chip{
            display:inline-flex; align-items:center; gap:.4rem;
            padding:.24rem .55rem; border:1px solid var(--sa-border);
            border-radius:999px; font-size:.8rem;
            background:linear-gradient(180deg, color-mix(in srgb, var(--sa-card-bg) 86%, #fff) 0%, var(--sa-card-bg) 100%);
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
                    <div class="col-md-8"><input class="form-control" type="search" name="q" value="<?=e($q)?>" placeholder="Пошук плагінів…"></div>
                    <div class="col-md-4"><input class="form-control" type="text" name="syrve" value="<?=e($user_syrve)?>" placeholder="Ваша версія Syrve (напр. 9.2)"></div>
                </form>
                <div class="small text-muted mt-2">Порада: введіть свою версію Syrve, і ми підсвітимо найбільш сумісну версію.</div>
            </div>
        </section>

        <?php foreach ($groups as $cat => $items): ?>
            <h5 class="mb-2"><?=e($cat)?></h5>
            <div class="card mb-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th style="width:220px">Найменування</th>
                            <th>Опис</th>
                            <th style="width:140px">Рек. версія</th>
                            <th style="width:120px">мін. Syrve</th>
                            <th style="width:90px">Канал</th>
                            <th style="width:120px">Дата</th>
                            <th class="text-end" style="width:260px">Завантаження</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $p):
                            $vers  = $versionsByPlugin[$p['id']] ?? [];
                            $best  = pick_best_version($vers, $user_syrve);
                            $files = $best ? files_for_version((int)$best['id']) : [];
                            ?>
                            <tr id="plugin-<?=$p['id']?>">
                                <td class="fw-semibold">
                                    <a href="plugin_view.php?slug=<?=urlencode($p['slug'])?>"><?=e($p['name'])?></a>
                                </td>
                                <td class="text-truncate" style="max-width:460px"><?=e($p['description'])?></td>
                                <td><?=e($best['version'] ?? '—')?></td>
                                <td><?=e($best['min_syrve'] ?? '—')?></td>
                                <td><?= !empty($best['channel']) ? channel_badge($best['channel']) : '—' ?></td>
                                <td><?=e($best['released_at'] ?? '—')?></td>
                                <td class="text-end">
                                    <?php if ($best): ?>
                                        <?php if ($files): ?>
                                            <div class="btn-group position-static">
                                                <a class="btn btn-sm btn-primary" href="<?=e($files[0]['file_path'])?>" download>Завантажити</a>
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                        data-bs-toggle="dropdown" data-bs-boundary="viewport"
                                                        aria-expanded="false">Файли</button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li class="px-3 py-2">
                                                        <div class="vers-head">v<?=e($best['version'])?></div>
                                                        <div class="vers-meta mb-2"><?=e($best['released_at'])?> · min <?=e($best['min_syrve'])?></div>
                                                        <?php foreach ($files as $f): ?>
                                                            <a class="dropdown-item overflow-anywhere" href="<?=e($f['file_path'])?>" download>
                                                                <span class="file-chip <?=e($f['ext'])?>"><span class="dot"></span><?=e($f['label'] ?: basename($f['file_path']))?></span>
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
                                                            echo '<div class="vers-meta mb-2">'.e($v['released_at']).' · min '.e($v['min_syrve']).'</div>';
                                                            if($vfiles){
                                                                foreach($vfiles as $vf){
                                                                    echo '<a class="dropdown-item overflow-anywhere" href="'.e($vf['file_path']).'" download>'.
                                                                        '<span class="file-chip '.e($vf['ext']).'"><span class="dot"></span>'.
                                                                        e($vf['label'] ?: basename($vf['file_path'])).'</span></a>';
                                                                }
                                                            } elseif(!empty($v['file_path'])) {
                                                                echo '<a class="dropdown-item" href="'.e($v['file_path']).'" download>Завантажити</a>';
                                                            } else {
                                                                echo '<div class="vers-meta">Файлів немає</div>';
                                                            }
                                                            echo '</li>';
                                                        }
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                        <?php elseif(!empty($best['file_path'])): ?>
                                            <a class="btn btn-sm btn-primary" href="<?=e($best['file_path'])?>" download>Завантажити</a>
                                        <?php else: ?>
                                            <span class="text-muted">Файлів немає</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Версій немає</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; if (empty($items)) echo '<tr><td colspan="7" class="text-center text-muted">Порожньо.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

<?php include __DIR__.'/partials_footer.php';
