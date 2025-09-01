<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
require_admin();

/** helpers */
function ensure_dir($path){
    if (!is_dir($path)) {
        if (!@mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException("Не вдалося створити папку: $path");
        }
    }
}

$slug = (string)($_GET['slug'] ?? '');
$pl = db_one("SELECT * FROM plugins WHERE slug=?", [$slug]);
if(!$pl){
    http_response_code(404);
    include __DIR__.'/partials_header.php';
    echo '<div class="container my-4">'.__('Plugin not found').'</div>';
    include __DIR__.'/partials_footer.php';
    exit;
}

/* actions */
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $a = $_POST['action'] ?? '';

    if ($a==='save_meta'){
        $name = trim((string)$_POST['name']);
        $category = trim((string)$_POST['category']);
        $description = trim((string)$_POST['description']);
        $repo_url = trim((string)$_POST['repo_url']);
        $homepage = trim((string)$_POST['homepage']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $newslug = slugify($name);

        db()->prepare("UPDATE plugins SET slug=?, name=?, category=?, description=?, repo_url=?, homepage=?, is_active=? WHERE id=?")
            ->execute([$newslug,$name,$category,$description,$repo_url,$homepage,$is_active,$pl['id']]);

        header('Location: plugin_edit.php?slug='.urlencode($newslug).'&saved=1'); exit;
    }

    if ($a==='add_version'){
        $version     = trim((string)$_POST['version']);
        $channel     = trim((string)$_POST['channel']);
        $min_syrve   = trim((string)$_POST['min_syrve']);
        $released_at = trim((string)$_POST['released_at']);
        $changelog   = (string)($_POST['changelog_md'] ?? '');
        if ($version==='') die('version required');

        db()->prepare("INSERT INTO plugin_versions(plugin_id,version,channel,min_syrve,released_at,file_path,checksum,changelog_md)
                       VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$pl['id'],$version,$channel,$min_syrve,$released_at,'','',$changelog]);

        header('Location: plugin_edit.php?slug='.urlencode($pl['slug']).'&vsaved=1'); exit;
    }

    if ($a==='delete_version'){
        $vid = (int)$_POST['version_id'];
        db()->prepare("DELETE FROM plugin_versions WHERE id=? AND plugin_id=?")->execute([$vid, $pl['id']]);
        header('Location: plugin_edit.php?slug='.urlencode($pl['slug']).'&vdel=1'); exit;
    }

    if ($a==='upload_files'){
        $vid = (int)$_POST['version_id'];
        $ver = db_one("SELECT * FROM plugin_versions WHERE id=? AND plugin_id=?", [$vid, $pl['id']]);
        if(!$ver) die('version not found');

        $dirSlug = $pl['slug'];
        $base = __DIR__ . '/uploads/plugins/'.$dirSlug;
        ensure_dir($base);
        $firstInsertedPath = '';

        if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])){
            $n = count($_FILES['files']['name']);
            for($i=0;$i<$n;$i++){
                if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $orig = (string)$_FILES['files']['name'][$i];
                $ext  = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
                $cleanV = preg_replace('/[^A-Za-z0-9_\.-]/','',$ver['version']);
                $fnameRel = 'uploads/plugins/'.$dirSlug.'/'.$dirSlug.'_'.$cleanV.'_'.time().'_'.$i.($ext?'.'.$ext:'');

                $abs = __DIR__.'/'.$fnameRel;
                if (!move_uploaded_file($_FILES['files']['tmp_name'][$i], $abs)) {
                    continue;
                }
                $checksum = hash_file('sha256', $abs) ?: '';
                $size     = filesize($abs) ?: 0;
                if ($firstInsertedPath==='') $firstInsertedPath = $fnameRel;

                db()->prepare("INSERT INTO plugin_files(plugin_version_id,label,file_path,checksum,ext,size_bytes)
                               VALUES(?,?,?,?,?,?)")
                    ->execute([$vid,$orig,$fnameRel,$checksum,$ext,$size]);
            }
        }
        if ($firstInsertedPath && empty($ver['file_path'])){
            db()->prepare("UPDATE plugin_versions SET file_path=?, checksum=? WHERE id=?")
                ->execute([$firstInsertedPath, hash_file('sha256', __DIR__.'/'.$firstInsertedPath), $vid]);
        }
        header('Location: plugin_edit.php?slug='.urlencode($pl['slug']).'&fsaved=1#v'.$vid); exit;
    }

    if ($a==='delete_file'){
        $fid = (int)$_POST['file_id'];
        $f = db_one("SELECT * FROM plugin_files WHERE id=?", [$fid]);
        if($f){
            @unlink(__DIR__.'/'.$f['file_path']);
            db()->prepare("DELETE FROM plugin_files WHERE id=?")->execute([$fid]);
        }
        header('Location: plugin_edit.php?slug='.urlencode($pl['slug']).'&fdel=1#v'.(int)$f['plugin_version_id']); exit;
    }
}

include __DIR__.'/partials_header.php';

$versions = db_all("SELECT * FROM plugin_versions WHERE plugin_id=? ORDER BY datetime(released_at) DESC, id DESC", [$pl['id']]);
?>
<style>
    /* ===== plugin_edit.php (scoped) ===== */
    .plugin-edit .file-chip{
        display:inline-flex;align-items:center;gap:.4rem;
        padding:.24rem .55rem;border-radius:999px;
        border:1px solid var(--sa-border);font-size:.8rem;
        background:linear-gradient(180deg, color-mix(in srgb, var(--sa-card-bg) 86%, #fff) 0%, var(--sa-card-bg) 100%);
        max-width: 420px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .plugin-edit .file-chip .dot{width:.5rem;height:.5rem;border-radius:999px;background:#94a3b8}
    .plugin-edit .file-chip.apk .dot{background:#10b981}
    .plugin-edit .file-chip.zip .dot{background:#6366f1}
    .plugin-edit .file-chip.jar .dot{background:#f59e0b}

    .plugin-edit .list-group-item{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;}
    .plugin-edit .list-group-item .flex-grow-1{min-width:0;}
    .plugin-edit .list-group-item .btn-group{flex:0 0 auto;}
    .plugin-edit .card{position:relative; z-index:1;}
    @media (max-width: 576px){ .plugin-edit .file-chip{max-width:260px;} }
</style>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item"><a href="admin.php#pane-plugins"><?=__('Admin')?></a></li>
        <li class="breadcrumb-item active"><?=__('Edit plugin')?></li>
    </ol>
</nav>

<?php if(!empty($_GET['saved'])): ?><div class="alert alert-success"><?=__('Saved')?></div><?php endif; ?>
<?php if(!empty($_GET['vsaved'])): ?><div class="alert alert-success"><?=__('Version created')?></div><?php endif; ?>
<?php if(!empty($_GET['vdel'])): ?><div class="alert alert-warning"><?=__('Version deleted')?></div><?php endif; ?>
<?php if(!empty($_GET['fsaved'])): ?><div class="alert alert-success"><?=__('Files saved')?></div><?php endif; ?>
<?php if(!empty($_GET['fdel'])): ?><div class="alert alert-warning"><?=__('File deleted')?></div><?php endif; ?>

<div class="plugin-edit">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-hover">
                <div class="card-body">
                    <h5 class="card-title mb-3"><?=__('Plugin')?>: <?=e($pl['name'])?></h5>
                    <form method="post">
                        <input type="hidden" name="action" value="save_meta">
                        <div class="mb-2">
                            <label class="form-label"><?=__('Name')?></label>
                            <input class="form-control" name="name" value="<?=e($pl['name'])?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label"><?=__('Category')?></label>
                            <input class="form-control" name="category" value="<?=e($pl['category'])?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label"><?=__('Description')?></label>
                            <textarea class="form-control" name="description" rows="4"><?=e($pl['description'])?></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Repo URL</label>
                            <input class="form-control" name="repo_url" value="<?=e($pl['repo_url'])?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label"><?=__('Homepage')?></label>
                            <input class="form-control" name="homepage" value="<?=e($pl['homepage'])?>">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="ia" name="is_active" <?=$pl['is_active']?'checked':''?>>
                            <label class="form-check-label" for="ia"><?=__('Active')?></label>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary"><?=__('Save')?></button>
                            <a class="btn btn-outline-secondary" href="plugins.php"><?=__('Back to list')?></a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-hover mt-3">
                <div class="card-body">
                    <h6 class="card-title"><?=__('Create new version')?></h6>
                    <form method="post">
                        <input type="hidden" name="action" value="add_version">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input class="form-control" name="version" placeholder="<?=__('Version')?>" required>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="channel">
                                    <option>stable</option><option>beta</option><option>preview</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input class="form-control" name="min_syrve" placeholder="<?=__('Min. Syrve')?>" required>
                            </div>
                            <div class="col-md-2">
                                <input class="form-control" type="date" name="released_at" required placeholder="<?=__('Release date')?>">
                            </div>
                        </div>
                        <div class="mt-2">
                            <textarea class="form-control" name="changelog_md" rows="3" placeholder="<?=__('Changelog (Markdown)')?>"></textarea>
                        </div>
                        <button class="btn btn-outline-primary mt-2"><?=__('Create version')?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-2"><?=__('Versions and files')?></h5>
                    <?php if(!$versions): ?>
                        <div class="text-muted"><?=__('No versions yet')?></div>
                    <?php endif; ?>

                    <?php foreach($versions as $v):
                        $files = db_all("SELECT * FROM plugin_files WHERE plugin_version_id=? ORDER BY id", [$v['id']]);
                        ?>
                        <div class="border rounded p-3 mb-3" id="v<?=$v['id']?>">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <strong>v<?=e($v['version'])?></strong>
                                    <span class="badge text-bg-info"><?=e($v['channel'])?></span>
                                    <span class="text-muted small">
                                        <?=__('min')?> <?=e($v['min_syrve'])?> · <?=e($v['released_at'])?>
                                    </span>
                                </div>
                                <form method="post" onsubmit="return confirm('<?=__('Delete version and all its files?')?>')">
                                    <input type="hidden" name="action" value="delete_version">
                                    <input type="hidden" name="version_id" value="<?=$v['id']?>">
                                    <button class="btn btn-sm btn-outline-danger"><?=__('Delete version')?></button>
                                </form>
                            </div>

                            <?php if($v['changelog_md']): ?>
                                <div class="mt-2 md"><?= md($v['changelog_md']) ?></div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <h6 class="mb-2"><?=__('Files')?></h6>
                                <?php if($files): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($files as $f): ?>
                                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                                <div class="flex-grow-1">
                                                    <span class="file-chip <?=e($f['ext'])?>">
                                                        <span class="dot"></span><?=e($f['label'] ?: basename($f['file_path']))?>
                                                    </span>
                                                    <span class="text-muted small ms-2">
                                                        <?=number_format((int)$f['size_bytes']/1024,1)?> KB
                                                    </span>
                                                </div>
                                                <div class="btn-group">
                                                    <a class="btn btn-sm btn-outline-primary" href="<?=e($f['file_path'])?>" download><?=__('Download')?></a>
                                                    <form method="post" onsubmit="return confirm('<?=__('Delete file?')?>')">
                                                        <input type="hidden" name="action" value="delete_file">
                                                        <input type="hidden" name="file_id" value="<?=$f['id']?>">
                                                        <button class="btn btn-sm btn-outline-danger"><?=__('Delete')?></button>
                                                    </form>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-muted small"><?=__('No files')?></div>
                                <?php endif; ?>

                                <form method="post" enctype="multipart/form-data" class="mt-2">
                                    <input type="hidden" name="action" value="upload_files">
                                    <input type="hidden" name="version_id" value="<?=$v['id']?>">
                                    <label class="form-label small mb-1">
                                        <?=__('Add files to version')?> v<?=e($v['version'])?>
                                    </label>
                                    <input class="form-control" type="file" name="files[]" multiple>
                                    <button class="btn btn-sm btn-outline-primary mt-2"><?=__('Upload')?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
