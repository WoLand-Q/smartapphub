<?php
require_once __DIR__.'/helpers.php';
require_admin();

$slug = $_GET['slug'] ?? '';
$pl = db_one("SELECT * FROM plugins WHERE slug=?", [$slug]);
if(!$pl){ http_response_code(404); include __DIR__.'/partials_header.php'; echo '<div class="container my-4">Плагін не знайдено</div>'; include __DIR__.'/partials_footer.php'; exit; }

if ($_SERVER['REQUEST_METHOD']==='POST'){
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $repo_url = trim($_POST['repo_url']);
    $homepage = trim($_POST['homepage']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $newslug = slugify($name);
    db()->prepare("UPDATE plugins SET slug=?, name=?, category=?, description=?, repo_url=?, homepage=?, is_active=? WHERE id=?")
        ->execute([$newslug,$name,$category,$description,$repo_url,$homepage,$is_active,$pl['id']]);
    header('Location: plugin_edit.php?slug='.urlencode($newslug).'&saved=1'); exit;
}

include __DIR__.'/partials_header.php';
?>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Огляд</a></li>
            <li class="breadcrumb-item"><a href="admin.php#pane-plugins">Адмін</a></li>
            <li class="breadcrumb-item active">Редагування плагіна</li>
        </ol>
    </nav>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Плагін: <?=e($pl['name'])?></h5>
                    <?php if(!empty($_GET['saved'])): ?><div class="alert alert-success py-2">Збережено</div><?php endif; ?>
                    <form method="post">
                        <div class="mb-2"><label class="form-label">Назва</label><input class="form-control" name="name" value="<?=e($pl['name'])?>" required></div>
                        <div class="mb-2"><label class="form-label">Категорія</label><input class="form-control" name="category" value="<?=e($pl['category'])?>"></div>
                        <div class="mb-2"><label class="form-label">Опис</label><textarea class="form-control" name="description" rows="4"><?=e($pl['description'])?></textarea></div>
                        <div class="mb-2"><label class="form-label">Repo URL</label><input class="form-control" name="repo_url" value="<?=e($pl['repo_url'])?>"></div>
                        <div class="mb-2"><label class="form-label">Homepage</label><input class="form-control" name="homepage" value="<?=e($pl['homepage'])?>"></div>
                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="ia" name="is_active" <?=$pl['is_active']?'checked':''?>><label class="form-check-label" for="ia">Активний</label></div>
                        <button class="btn btn-primary">Зберегти</button>
                        <a class="btn btn-outline-secondary" href="plugins.php">До списку</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Останні версії</h6>
                    <?php
                    $vers = db_all("SELECT * FROM plugin_versions WHERE plugin_id=? ORDER BY datetime(released_at) DESC, id DESC LIMIT 12", [$pl['id']]);
                    if($vers){
                        echo '<ul class="list-group list-group-flush">';
                        foreach($vers as $v){
                            $cnt = (int)(db_one("SELECT COUNT(*) c FROM plugin_files WHERE plugin_version_id=?",[$v['id']])['c'] ?? 0);
                            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            echo '<span>v'.e($v['version']).' · '.e($v['channel']).' · min '.e($v['min_syrve']).'</span>';
                            echo '<span class="badge text-bg-secondary">'.$cnt.' файлів</span>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<div class="text-muted small">Поки немає версій.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__.'/partials_footer.php';
