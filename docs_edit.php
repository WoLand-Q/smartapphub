<?php
require_once __DIR__.'/helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$doc = $id ? db_one('SELECT * FROM docs WHERE id=?', [$id]) : null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title'] ?? '');
    $tags  = trim($_POST['tags'] ?? '');
    $desc  = (string)($_POST['description_md'] ?? '');
    $link  = trim($_POST['link'] ?? '');
    $filePath = $doc['file_path'] ?? '';

    // upload (optional)
    if(!empty($_FILES['file']['name']) && $_FILES['file']['error']===UPLOAD_ERR_OK){
        $ext=pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
        if(!is_dir(__DIR__.'/uploads/docs')) mkdir(__DIR__.'/uploads/docs',0777,true);
        $fname='uploads/docs/doc_'.time().'.'.$ext;
        move_uploaded_file($_FILES['file']['tmp_name'], __DIR__.'/'.$fname);
        $filePath=$fname;
    }

    if($id){
        db()->prepare("UPDATE docs SET title=?, description_md=?, link=?, file_path=?, tags=?, updated_at=? WHERE id=?")
            ->execute([$title,$desc,$link,$filePath,$tags, now(), $id]);
    } else {
        db()->prepare("INSERT INTO docs(title, description_md, link, file_path, tags, updated_at) VALUES(?,?,?,?,?,?)")
            ->execute([$title,$desc,$link,$filePath,$tags, now()]);
        $id = (int)db()->lastInsertId();
    }
    header('Location: docs_view.php?id='.$id); exit;
}

include __DIR__.'/partials_header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Огляд</a></li>
        <li class="breadcrumb-item"><a href="docs.php">Документація</a></li>
        <li class="breadcrumb-item active"><?=$doc?'Редагування':'Новий документ'?></li>
    </ol>
</nav>

<div class="card card-hover">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-7">
                <label class="form-label">Назва</label>
                <input class="form-control" name="title" required value="<?=e($doc['title'] ?? '')?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">Теги (через кому)</label>
                <input class="form-control" name="tags" value="<?=e($doc['tags'] ?? '')?>">
            </div>

            <div class="col-12">
                <label class="form-label">Опис (Markdown)</label>
                <textarea class="form-control" name="description_md" rows="10"><?=e($doc['description_md'] ?? '')?></textarea>
            </div>

            <div class="col-md-7">
                <label class="form-label">Зовнішнє посилання (опціонально)</label>
                <input class="form-control" name="link" placeholder="https://…" value="<?=e($doc['link'] ?? '')?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">Файл (опціонально)</label>
                <input class="form-control" type="file" name="file">
                <?php if(!empty($doc['file_path'])): ?>
                    <div class="form-text">Поточний файл: <a href="<?=e($doc['file_path'])?>" target="_blank"><?=basename($doc['file_path'])?></a></div>
                <?php endif; ?>
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Зберегти</button>
                <a class="btn btn-outline-secondary" href="<?=$id ? 'docs_view.php?id='.$id : 'docs.php'?>">Скасувати</a>
            </div>
        </form>
    </div>
</div>

<!-- (по желанию можно подключить TinyMCE) -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: 'textarea[name=description_md]',
        menubar: false,
        height: 380,
        plugins: 'link lists code',
        toolbar: 'undo redo | bold italic | bullist numlist | link | code',
        skin: document.documentElement.dataset.bsTheme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: document.documentElement.dataset.bsTheme === 'dark' ? 'dark' : 'default'
    });
</script>

<?php include __DIR__.'/partials_footer.php'; ?>
