<?php
include __DIR__.'/partials_header.php';



$slug = $_GET['slug'] ?? '';
$pl = db_one("SELECT * FROM plugins WHERE slug=?", [$slug]);
if(!$pl){ http_response_code(404); echo '<div class="text-muted">Плагін не знайдено</div>'; include __DIR__.'/partials_footer.php'; exit; }

$versions = db_all("SELECT * FROM plugin_versions WHERE plugin_id=? ORDER BY datetime(released_at) DESC, id DESC", [$pl['id']]);
?>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Огляд</a></li>
            <li class="breadcrumb-item"><a href="plugins.php">Інтеграції</a></li>
            <li class="breadcrumb-item active"><?=htmlspecialchars($pl['name'])?></li>
        </ol>
    </nav>

    <div class="card card-hover mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <h3 class="mb-0"><?=htmlspecialchars($pl['name'])?></h3>
                <?php if($pl['category']): ?><span class="chip"><?=htmlspecialchars($pl['category'])?></span><?php endif; ?>
                <?php if($pl['repo_url']): ?><a class="btn btn-sm btn-outline-secondary" href="<?=htmlspecialchars($pl['repo_url'])?>" target="_blank">Repo</a><?php endif; ?>
                <?php if($pl['homepage']): ?><a class="btn btn-sm btn-outline-secondary" href="<?=htmlspecialchars($pl['homepage'])?>" target="_blank">Site</a><?php endif; ?>
            </div>
            <div class="mt-2 text-muted"><?=nl2br(htmlspecialchars($pl['description']))?></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Версія</th>
                    <th>Канал</th>
                    <th>Мін. Syrve</th>
                    <th>Дата</th>
                    <th>Файли</th>
                    <th class="text-end">Завантаження</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($versions as $v):
                    $files = db_all("SELECT * FROM plugin_files WHERE plugin_version_id=? ORDER BY id", [$v['id']]);
                    ?>
                    <tr>
                        <td class="fw-semibold">v<?=htmlspecialchars($v['version'])?></td>
                        <td><span class="badge text-bg-info"><?=htmlspecialchars($v['channel'])?></span></td>
                        <td><?=htmlspecialchars($v['min_syrve'])?></td>
                        <td><?=htmlspecialchars($v['released_at'])?></td>
                        <td>
                            <?php
                            if($files){
                                foreach($files as $f){
                                    echo '<span class="badge bg-secondary me-1">'.htmlspecialchars($f['label'] ?: basename($f['file_path'])).'</span>';
                                }
                            } elseif($v['file_path']){
                                echo '<span class="badge bg-secondary">file</span>';
                            } else {
                                echo '<span class="text-muted">—</span>';
                            }
                            ?>
                        </td>
                        <td class="text-end">
                            <?php if($files){
                                echo '<div class="btn-group">';
                                echo '<a class="btn btn-sm btn-primary" href="'.htmlspecialchars($files[0]['file_path']).'" download>Завантажити</a>';
                                if(count($files)>1){
                                    echo '<button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">Інші файли</button>';
                                    echo '<ul class="dropdown-menu dropdown-menu-end">';
                                    foreach($files as $f){
                                        echo '<li><a class="dropdown-item" href="'.htmlspecialchars($f['file_path']).'" download>'.htmlspecialchars($f['label'] ?: basename($f['file_path'])).'</a></li>';
                                    }
                                    echo '</ul>';
                                }
                                echo '</div>';
                            } elseif($v['file_path']) {
                                echo '<a class="btn btn-sm btn-primary" href="'.htmlspecialchars($v['file_path']).'" download>Завантажити</a>';
                            } else {
                                echo '<span class="text-muted">Немає файлів</span>';
                            } ?>
                        </td>
                    </tr>
                <?php endforeach; if(empty($versions)) echo '<tr><td colspan="6" class="text-center text-muted">Версій поки немає.</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include __DIR__.'/partials_footer.php';
