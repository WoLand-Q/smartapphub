<?php require_once __DIR__.'/helpers.php'; require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? db_one('SELECT * FROM news WHERE id=?',[$id]) : null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title']??'');
    $tags  = trim($_POST['tags']??'');
    $author= trim($_POST['author']??'');
    $html  = $_POST['body_html'] ?? '';
    if(!$title) $title='(без назви)';

    if($id){
        $st=db()->prepare('UPDATE news SET title=?, tags=?, author=?, body_html=? WHERE id=?');
        $st->execute([$title,$tags,$author,$html,$id]);
    }else{
        $st=db()->prepare('INSERT INTO news(title, body_md, body_html, tags, created_at, author) VALUES(?,?,?,?,?,?)');
        $st->execute([$title,'',$html,$tags, now(), $author]);
        $id = (int)db()->lastInsertId();
    }
    header('Location: news_view.php?id='.$id); exit;
}

include __DIR__.'/partials_header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Огляд</a></li><li class="breadcrumb-item"><a href="news.php">Новини</a></li><li class="breadcrumb-item active"><?=$item?'Редагування':'Нова новина'?></li></ol>
</nav>

<div class="card card-hover">
    <div class="card-body">
        <form method="post">
            <div class="mb-2"><input class="form-control" name="title" placeholder="Заголовок" value="<?=e($item['title']??'')?>" required></div>
            <div class="mb-2"><input class="form-control" name="tags" placeholder="Теги (через кому)" value="<?=e($item['tags']??'')?>"></div>
            <div class="mb-2"><input class="form-control" name="author" placeholder="Автор" value="<?=e($item['author']??'')?>"></div>
            <div class="mb-3">
                <textarea id="body_html" name="body_html" rows="18"><?=e($item['body_html']??'')?></textarea>
            </div>
            <button class="btn btn-primary">Зберегти</button>
            <?php if($item): ?><a class="btn btn-outline-secondary ms-2" href="news_view.php?id=<?=$item['id']?>">Переглянути</a><?php endif; ?>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector:'#body_html',
        height:560,
        menubar:true,
        plugins: 'preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help emoticons',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough forecolor backcolor removeformat | align lineheight | bullist numlist outdent indent | table link image media | codesample | fullscreen preview',
        images_upload_url: 'upload_image.php',
        automatic_uploads: true,
        convert_urls: false,
        image_caption: true,
        content_style: 'body{font-family:system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;line-height:1.6}'
    });
</script>
<?php include __DIR__.'/partials_footer.php'; ?>
