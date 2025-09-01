<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
require_admin();

$id  = (int)($_GET['id'] ?? 0);
$rel = $id ? db_one('SELECT * FROM syrve_releases WHERE id=?',[$id]) : null;

if(!$rel){
    http_response_code(404);
    include __DIR__.'/partials_header.php';
    echo '<div class="container my-4"><div class="alert alert-danger">'.__('Release not found').'</div></div>';
    include __DIR__.'/partials_footer.php';
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name        = trim($_POST['name'] ?? $rel['name']);
    $channel     = (string)($_POST['channel'] ?? $rel['channel']);
    $released_at = (string)($_POST['released_at'] ?? $rel['released_at']);
    $is_rec      = isset($_POST['is_recommended']) ? 1 : 0;
    $is_lt       = isset($_POST['is_lt']) ? 1 : 0;
    $notes       = (string)($_POST['notes_html'] ?? '');
    $slug        = slugify($name);

    $st = db()->prepare("UPDATE syrve_releases
                         SET name=?, slug=?, channel=?, released_at=?, is_recommended=?, is_lt=?, notes_html=?
                         WHERE id=?");
    $st->execute([$name,$slug,$channel,$released_at,$is_rec,$is_lt,$notes,$id]);

    header('Location: release_view.php?slug='.$slug); exit;
}

include __DIR__.'/partials_header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item"><a href="releases.php"><?=__('Syrve releases')?></a></li>
        <li class="breadcrumb-item active"><?=__('Edit')?></li>
    </ol>
</nav>

<div class="card card-hover">
    <div class="card-body">
        <form method="post">
            <div class="row g-2">
                <div class="col-md-4">
                    <input class="form-control" name="name" value="<?=e($rel['name'])?>" placeholder="<?=__('Title')?>" required>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="channel" aria-label="<?=__('Channel')?>">
                        <?php foreach(['stable','beta','preview'] as $c): ?>
                            <option value="<?=$c?>" <?=$rel['channel']===$c?'selected':''?>><?=$c?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input class="form-control" type="date" name="released_at" value="<?=e($rel['released_at'])?>" placeholder="<?=__('Release date')?>" required>
                </div>
                <div class="col-md-3 d-flex align-items-center gap-3">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_recommended" <?=$rel['is_recommended']?'checked':''?>>
                        <?=__('Recommended')?>
                    </label>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_lt" <?=$rel['is_lt']?'checked':''?>>
                        <?=__('LT')?>
                    </label>
                </div>
            </div>

            <div class="mt-3">
                <textarea id="notes_html" name="notes_html" rows="18"><?=e($rel['notes_html'])?></textarea>
            </div>

            <div class="mt-3">
                <button class="btn btn-primary"><?=__('Save')?></button>
                <a class="btn btn-outline-secondary ms-2" href="release_view.php?slug=<?=e($rel['slug'])?>"><?=__('View')?></a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector:'#notes_html',
        height:520,
        menubar:true,
        plugins:'preview link image media table lists codesample fullscreen',
        toolbar:'undo redo | blocks | bold italic underline | align | bullist numlist | link image media table | codesample | fullscreen preview',
        images_upload_url:'upload_image.php',
        automatic_uploads:true,
        convert_urls:false
    });
</script>
<?php include __DIR__.'/partials_footer.php'; ?>
