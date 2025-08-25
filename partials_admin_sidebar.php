<?php
// partials_admin_sidebar.php — переиспользуемая правая панель для админки
// Ожидает: $plugins, а также локальные переменные $lastFiles, $files_total, $fpage, $fper
?>
<div class="admin-sticky d-flex flex-column gap-3">
    <div class="card card-hover files-panel">
        <div class="card-body">
            <h6 class="card-title mb-2">Останні завантажені файли</h6>
            <?php if(!empty($lastFiles)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach($lastFiles as $f): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between">
                            <div class="me-2">
                                <div class="small fw-semibold"><?=e($f['plugin_name'])?> · v<?=e($f['version'])?></div>
                                <div class="small text-muted"><?=e($f['label'])?> (<?=strtoupper(e($f['ext']))?> · <?=number_format((int)$f['size_bytes']/1024,1)?> KB)</div>
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

    <!-- Add plugin quick form -->
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
</div>
