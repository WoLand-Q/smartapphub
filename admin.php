<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';
require_admin();

/* ---------- mini flash ---------- */
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
function flash_add($type,$msg){ $_SESSION['flash'][]=['t'=>$type,'m'=>$msg]; }
function flash_show(){
    if (empty($_SESSION['flash'])) return;
    echo '<div class="container"><div class="position-fixed top-0 end-0 p-3" style="z-index:2000">';
    foreach($_SESSION['flash'] as $f){
        $cls = $f['t']==='err' ? 'alert-danger' : 'alert-success';
        echo '<div class="alert '.$cls.' shadow">'.$f['m'].'</div>';
    }
    echo '</div></div>';
    $_SESSION['flash'] = [];
}

/* small helper */
function ensure_dir($path){
    if (!is_dir($path)) {
        if (!@mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException("Не вдалося створити папку: $path");
        }
    }
}

/* ---------- deletes ---------- */
if (isset($_GET['del_news']))    { db()->prepare('DELETE FROM news WHERE id=?')->execute([(int)$_GET['del_news']]);    flash_add('ok','Новину видалено');    header('Location: admin.php'); exit; }
if (isset($_GET['del_release'])) { db()->prepare('DELETE FROM syrve_releases WHERE id=?')->execute([(int)$_GET['del_release']]); flash_add('ok','Реліз видалено'); header('Location: admin.php'); exit; }
if (isset($_GET['del_doc']))     { db()->prepare('DELETE FROM docs WHERE id=?')->execute([(int)$_GET['del_doc']]);     flash_add('ok','Документ видалено');  header('Location: admin.php'); exit; }
if (isset($_GET['del_plugin']))  { db()->prepare('DELETE FROM plugins WHERE id=?')->execute([(int)$_GET['del_plugin']]); flash_add('ok','Плагін видалено');  header('Location: admin.php'); exit; }

/* ---------- creates ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';

    // document
    if ($a === 'add_doc') {
        try{
            $filePath = '';
            if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK){
                $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                ensure_dir(__DIR__.'/uploads/docs');
                $fnameRel = 'uploads/docs/doc_'.time().'.'.$ext;
                if (!move_uploaded_file($_FILES['file']['tmp_name'], __DIR__.'/'.$fnameRel)) {
                    throw new RuntimeException('Помилка збереження файла документа.');
                }
                $filePath = $fnameRel;
            }
            db()->prepare("INSERT INTO docs(title, description_md, link, file_path, tags, updated_at) VALUES(?,?,?,?,?,?)")
                ->execute([$_POST['title'], $_POST['description_md'], $_POST['link']??'', $filePath, $_POST['tags']??'', now()]);
            flash_add('ok','Документ додано');
        }catch(Throwable $e){ flash_add('err','Помилка: '.$e->getMessage()); }
        header('Location: admin.php'); exit;
    }

    // plugin (catalog)
    if ($a === 'add_plugin') {
        try{
            $slug = slugify($_POST['name']);
            db()->prepare("INSERT INTO plugins(slug,name,description,repo_url,homepage,is_active,category)
                           VALUES(?,?,?,?,?,1,?)")
                ->execute([$slug,$_POST['name'],$_POST['description'],$_POST['repo_url']??'',$_POST['homepage']??'', $_POST['category']??'']);
            flash_add('ok','Плагін додано');
        }catch(Throwable $e){ flash_add('err','Помилка: '.$e->getMessage()); }
        header('Location: admin.php'); exit;
    }

    // plugin version (+ multiple files)
    if ($a === 'add_plugin_version') {
        try{
            $pid         = (int)$_POST['plugin_id'];
            $version     = trim((string)$_POST['version']);
            $channel     = (string)$_POST['channel'];
            $min_syrve   = (string)$_POST['min_syrve'];
            $released_at = (string)$_POST['released_at'];
            $changelog   = (string)($_POST['changelog_md'] ?? '');

            if ($pid<=0 || $version==='') throw new RuntimeException('Не заповнені обовʼязкові поля.');
            db()->prepare("INSERT INTO plugin_versions(plugin_id,version,channel,min_syrve,released_at,file_path,checksum,changelog_md)
                           VALUES(?,?,?,?,?,?,?,?)")
                ->execute([$pid,$version,$channel,$min_syrve,$released_at,'','',$changelog]);
            $ver_id = (int)db()->lastInsertId();

            // folder for this plugin
            $dirSlug = db_one("SELECT slug FROM plugins WHERE id=?",[$pid])['slug'] ?? ('p'.time());
            $base = __DIR__ . '/uploads/plugins/'.$dirSlug;
            ensure_dir($base);

            $firstPath = '';
            if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
                $n = count($_FILES['files']['name']);
                for ($i=0; $i<$n; $i++){
                    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $orig = (string)$_FILES['files']['name'][$i];
                    $ext  = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
                    $cleanV = preg_replace('/[^A-Za-z0-9_\.-]/','',$version);
                    $fnameRel = 'uploads/plugins/'.$dirSlug.'/'.$dirSlug.'_'.$cleanV.'_'.time().'_'.$i.($ext?'.'.$ext:'');

                    $abs = __DIR__.'/'.$fnameRel;
                    if (!move_uploaded_file($_FILES['files']['tmp_name'][$i], $abs)) {
                        throw new RuntimeException('Не вдалося зберегти файл: '.$orig);
                    }
                    $checksum = hash_file('sha256', $abs) ?: '';
                    $size     = filesize($abs) ?: 0;

                    if ($firstPath==='') $firstPath = $fnameRel;

                    db()->prepare("INSERT INTO plugin_files(plugin_version_id,label,file_path,checksum,ext,size_bytes)
                                   VALUES(?,?,?,?,?,?)")
                        ->execute([$ver_id,$orig,$fnameRel,$checksum,$ext,$size]);
                }
            }

            if ($firstPath!==''){
                db()->prepare("UPDATE plugin_versions SET file_path=?, checksum=? WHERE id=?")
                    ->execute([$firstPath, hash_file('sha256', __DIR__.'/'.$firstPath), $ver_id]);
            }

            flash_add('ok','Версію плагіна додано'.($firstPath?' і файли збережено':' (без файлів)'));
        }catch(Throwable $e){ flash_add('err','Помилка: '.$e->getMessage()); }
        header('Location: admin.php'); exit;
    }

    // Syrve release
    if ($a === 'add_release') {
        try{
            $slug = slugify($_POST['name']);
            db()->prepare("INSERT INTO syrve_releases(name,slug,released_at,channel,is_recommended,is_lt,notes_html)
                           VALUES(?,?,?,?,?,?,?)")
                ->execute([
                    $_POST['name'],$slug,$_POST['released_at'],$_POST['channel'],
                    isset($_POST['is_recommended'])?1:0, isset($_POST['is_lt'])?1:0, ''
                ]);
            flash_add('ok','Реліз додано');
        }catch(Throwable $e){ flash_add('err','Помилка: '.$e->getMessage()); }
        header('Location: admin.php'); exit;
    }
}

/* ---------- data ---------- */
$plugins = db_all("SELECT * FROM plugins ORDER BY name");
$news    = db_all("SELECT * FROM news ORDER BY datetime(created_at) DESC");
$docs    = db_all("SELECT * FROM docs ORDER BY datetime(updated_at) DESC");
$rels    = db_all("SELECT * FROM syrve_releases ORDER BY datetime(released_at) DESC");

/* last uploaded files + pagination */
$fpage = max(1, (int)($_GET['fpage'] ?? 1));
$fper  = 12;
$foff  = ($fpage-1) * $fper;
$files_total = (int) (db_one("SELECT COUNT(*) AS c FROM plugin_files")['c'] ?? 0);
$lastFiles = db_all("
    SELECT pf.*, pv.version, pv.plugin_id, p.name AS plugin_name
    FROM plugin_files pf
    JOIN plugin_versions pv ON pv.id=pf.plugin_version_id
    JOIN plugins p ON p.id=pv.plugin_id
    ORDER BY pf.id DESC
    LIMIT $fper OFFSET $foff
");
?>
    <style>
        /* ===== admin.php (scoped) ===== */
        #adminTabs .nav-link{border-radius:999px;}
        #adminTabs .nav-link.active{box-shadow:inset 0 0 0 1px var(--sa-border);}

        /* таблицы */
        .table thead th{position:sticky; top:0; background:var(--sa-card-bg); z-index:1; border-bottom:1px solid var(--sa-border);}
        .table tbody tr:hover{background: color-mix(in srgb, var(--sa-brand) 6%, transparent);}

        /* layout: чтобы правая колонка НЕ перекрывала центральную */
        .admin-main { position: relative; z-index: 2; }
        .admin-main .card { position: relative; z-index: 2; }
        .admin-sticky { position: sticky; top: 1rem; z-index: 1; } /* ниже центрального контейнера */
        @media (max-width: 991.98px){ .admin-sticky { position: static; z-index:auto; } }

        /* блок последних файлов */
        .files-panel .list-group{ max-height:56vh; overflow:auto; }
        @media (max-width:575.98px){ .files-panel .list-group{ max-height:none; } }
        .files-panel .list-group-item{ display:flex; gap:.75rem; align-items:center; justify-content:space-between; }
        .files-panel .list-group-item .flex-grow-1{ min-width:0; }
        .files-panel .file-title{ font-weight:600; font-size:.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .files-panel .file-meta{ font-size:.82rem; color:var(--sa-muted,#6c757d); overflow-wrap:anywhere; }
        .files-panel .btn-sm{ white-space:nowrap; }

        /* чтобы любые выпадашки не клипались */
        /* таблицы внутри вкладок: по X — скролл, по Y — можно показывать дропдауны */
        .tab-content .table-responsive{overflow-x:auto; overflow-y:visible;}

        /* конкретно для вкладки «Плагіни» — фиксированная раскладка и перенос слов,
           чтобы длинные ячейки не распирали таблицу шире контейнера */
        #pane-plugins .table{table-layout:fixed;}
        #pane-plugins .table td,
        #pane-plugins .table th{word-break:break-word;}

        /* дропдауны поверх всего, но без расползания контейнеров */
        .dropdown-menu{z-index:1900;}
    </style>

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Огляд</a></li>
            <li class="breadcrumb-item active">Адмін</li>
        </ol>
    </nav>

<?php flash_show(); ?>

    <div class="row g-3">
        <!-- LEFT -->
        <div class="col-lg-8 admin-main">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills mb-3" id="adminTabs">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-news">Контент</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-plugins">Плагіни</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-releases">Релізи</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-docs">Документація</button></li>
                    </ul>

                    <div class="tab-content">
                        <!-- NEWS -->
                        <div class="tab-pane fade show active" id="pane-news">
                            <h5 class="mb-2">Новини</h5>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Заголовок</th><th>Теги</th><th>Дата</th><th class="text-end">Дії</th></tr></thead>
                                    <tbody>
                                    <?php foreach($news as $n): ?>
                                        <tr>
                                            <td><a href="news_view.php?id=<?=$n['id']?>"><?=e($n['title'])?></a></td>
                                            <td class="text-muted small"><?=e($n['tags'] ?? '')?></td>
                                            <td class="text-muted small"><?=e($n['created_at'])?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="news_edit.php?id=<?=$n['id']?>">Редагувати</a>
                                                <a class="btn btn-sm btn-outline-danger" href="admin.php?del_news=<?=$n['id']?>" onclick="return confirm('Видалити?')">Видалити</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; if(empty($news)) echo '<tr><td colspan="4" class="text-muted">Порожньо</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- PLUGINS -->
                        <div class="tab-pane fade" id="pane-plugins">
                            <h5 class="mb-2">Плагіни</h5>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Назва</th><th>Категорія</th><th>Опис</th><th>Repo</th><th>Сайт</th><th class="text-end">Дії</th></tr></thead>
                                    <tbody>
                                    <?php foreach($plugins as $p): ?>
                                        <tr>
                                            <td><?=e($p['name'])?></td>
                                            <td class="small"><?=e($p['category']??'')?></td>
                                            <td class="text-truncate" style="max-width:320px"><?=e($p['description'])?></td>
                                            <td class="small"><?= $p['repo_url'] ? '<a href="'.e($p['repo_url']).'" target="_blank">repo</a>' : '—' ?></td>
                                            <td class="small"><?= $p['homepage'] ? '<a href="'.e($p['homepage']).'" target="_blank">site</a>' : '—' ?></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a class="btn btn-sm btn-outline-primary" href="plugin_edit.php?slug=<?=urlencode($p['slug'])?>">Редагувати</a>
                                                    <a class="btn btn-sm btn-outline-danger" href="admin.php?del_plugin=<?=$p['id']?>" onclick="return confirm('Видалити?')">Видалити</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; if (empty($plugins)) echo '<tr><td colspan="6" class="text-muted">Поки немає</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- RELEASES -->
                        <div class="tab-pane fade" id="pane-releases">
                            <h5 class="mb-2">Релізи Syrve</h5>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Назва</th><th>Канал</th><th>Дата</th><th class="text-end">Дії</th></tr></thead>
                                    <tbody>
                                    <?php foreach($rels as $r): ?>
                                        <tr>
                                            <td><a href="release_view.php?slug=<?=urlencode($r['slug'])?>"><?=e($r['name'])?></a></td>
                                            <td class="text-muted small"><?=e($r['channel'])?><?= $r['is_recommended'] ? ' · рек.' : '' ?><?= $r['is_lt'] ? ' · LT' : '' ?></td>
                                            <td class="text-muted small"><?=e($r['released_at'])?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="release_edit.php?id=<?=$r['id']?>">Редагувати</a>
                                                <a class="btn btn-sm btn-outline-danger" href="admin.php?del_release=<?=$r['id']?>" onclick="return confirm('Видалити?')">Видалити</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; if (empty($rels)) echo '<tr><td colspan="4" class="text-muted">Порожньо</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- DOCS -->
                        <div class="tab-pane fade" id="pane-docs">
                            <h5 class="mb-2">Документація</h5>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Назва</th><th>Теги</th><th>Оновлено</th><th class="text-end">Дії</th></tr></thead>
                                    <tbody>
                                    <?php foreach($docs as $d): ?>
                                        <tr>
                                            <td><?=e($d['title'])?></td>
                                            <td class="text-muted small"><?=e($d['tags'])?></td>
                                            <td class="text-muted small"><?=e($d['updated_at'])?></td>
                                            <td class="text-end"><a class="btn btn-sm btn-outline-danger" href="admin.php?del_doc=<?=$d['id']?>" onclick="return confirm('Видалити?')">Видалити</a></td>
                                        </tr>
                                    <?php endforeach; if (empty($docs)) echo '<tr><td colspan="4" class="text-muted">Порожньо</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- /tab-content -->
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">
            <div class="admin-sticky d-flex flex-column gap-3">
                <!-- Last uploaded files -->
                <div class="card card-hover files-panel">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Останні завантажені файли</h6>
                        <?php if($lastFiles): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach($lastFiles as $f): ?>
                                    <li class="list-group-item">
                                        <div class="flex-grow-1">
                                            <div class="file-title"><?=e($f['plugin_name'])?> · v<?=e($f['version'])?></div>
                                            <div class="file-meta"><?=e($f['label'])?> (<?=strtoupper(e($f['ext']))?> · <?=number_format((int)$f['size_bytes']/1024,1)?> KB)</div>
                                        </div>
                                        <a class="btn btn-sm btn-outline-primary" href="<?=e($f['file_path'])?>" download>Скачати</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php
                            $fpages = max(1, ceil($files_total/$fper));
                            if ($fpages>1){
                                echo '<div class="d-flex gap-2 justify-content-center mt-2">';
                                for($i=1;$i<=$fpages;$i++){
                                    $qs = $_GET; $qs['fpage']=$i;
                                    $cls = $i===$fpage ? 'btn-primary' : 'btn-outline-primary';
                                    echo '<a class="btn btn-sm '.$cls.'" href="?'.http_build_query($qs).'">'.$i.'</a>';
                                }
                                echo '</div>';
                            }
                            ?>
                        <?php else: ?>
                            <div class="text-muted small">Файлів поки що немає.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add plugin -->
                <div class="card card-hover"><div class="card-body">
                        <h6 class="card-title mb-2">Додати плагін</h6>
                        <form method="post">
                            <input type="hidden" name="action" value="add_plugin">
                            <div class="mb-2"><input class="form-control" name="name" placeholder="Назва плагіна" required></div>
                            <div class="mb-2"><input class="form-control" name="category" placeholder="Категорія (напр. CRM, Принтери)"></div>
                            <div class="mb-2"><textarea class="form-control" name="description" rows="2" placeholder="Короткий опис" required></textarea></div>
                            <div class="mb-2"><input class="form-control" name="repo_url" placeholder="Repo URL"></div>
                            <div class="mb-2"><input class="form-control" name="homepage" placeholder="Homepage"></div>
                            <button class="btn btn-outline-primary w-100">Додати</button>
                        </form>
                    </div></div>

                <!-- Add plugin version -->
                <div class="card card-hover"><div class="card-body">
                        <h6 class="card-title mb-2">Версія плагіна</h6>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_plugin_version">
                            <div class="mb-2">
                                <select class="form-select" name="plugin_id" required>
                                    <option value="">Оберіть плагін…</option>
                                    <?php foreach($plugins as $p): ?><option value="<?=$p['id']?>"><?=e($p['name'])?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2"><input class="form-control" name="version" placeholder="1.2.3" required></div>
                            <div class="mb-2"><input class="form-control" name="min_syrve" placeholder="мін. Syrve (напр. 9.1)" required></div>
                            <div class="mb-2">
                                <select class="form-select" name="channel"><option>stable</option><option>beta</option><option>preview</option></select>
                            </div>
                            <div class="mb-2"><input class="form-control" type="date" name="released_at" required></div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Файли (можна кілька)</label>
                                <input class="form-control" type="file" name="files[]" multiple>
                                <div class="form-text">Перший файл стане «основним». Інші збережуться як додаткові.</div>
                            </div>
                            <div class="mb-2"><textarea class="form-control" name="changelog_md" rows="2" placeholder="Changelog (Markdown)"></textarea></div>
                            <button class="btn btn-outline-primary w-100">Додати</button>
                        </form>
                    </div></div>

                <!-- Add doc -->
                <div class="card card-hover"><div class="card-body">
                        <h6 class="card-title mb-2">Додати документ</h6>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_doc">
                            <div class="mb-2"><input class="form-control" name="title" placeholder="Назва" required></div>
                            <div class="mb-2"><input class="form-control" name="tags" placeholder="Теги"></div>
                            <div class="mb-2"><textarea class="form-control" name="description_md" rows="3" placeholder="Опис (Markdown)" required></textarea></div>
                            <div class="mb-2"><input class="form-control" name="link" placeholder="https://…"></div>
                            <div class="mb-2"><input class="form-control" type="file" name="file"></div>
                            <button class="btn btn-primary w-100">Зберегти</button>
                        </form>
                    </div></div>
            </div>
        </div>
    </div>

<?php include __DIR__.'/partials_footer.php';
