<?php
require_once __DIR__.'/helpers.php';
include __DIR__.'/partials_header.php';

$slug = $_GET['slug'] ?? '';
$pl = db_one("SELECT * FROM plugins WHERE slug=?", [$slug]);
if(!$pl){
    http_response_code(404);
    echo '<div class="text-muted">'.__('Plugin not found').'</div>';
    include __DIR__.'/partials_footer.php';
    exit;
}

$versions = db_all("SELECT * FROM plugin_versions WHERE plugin_id=? ORDER BY datetime(released_at) DESC, id DESC", [$pl['id']]);
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item"><a href="plugins.php"><?=__('Plugins')?></a></li>
        <li class="breadcrumb-item active"><?=e($pl['name'])?></li>
    </ol>
</nav>

<div class="card card-hover mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <h3 class="mb-0"><?=e($pl['name'])?></h3>
            <?php if($pl['category']): ?><span class="chip"><?=e($pl['category'])?></span><?php endif; ?>
            <?php if($pl['repo_url']): ?><a class="btn btn-sm btn-outline-secondary" href="<?=e($pl['repo_url'])?>" target="_blank">Repo</a><?php endif; ?>
            <?php if($pl['homepage']): ?><a class="btn btn-sm btn-outline-secondary" href="<?=e($pl['homepage'])?>" target="_blank"><?=__('Site')?></a><?php endif; ?>
        </div>
        <div class="mt-2 text-muted"><?=nl2br(e($pl['description']))?></div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0 responsive-cards">
            <thead>
            <tr>
                <th><?=__('Version')?></th>
                <th><?=__('Channel')?></th>
                <th><?=__('Min. Syrve')?></th>
                <th><?=__('Date')?></th>
                <th><?=__('Files')?></th>
                <th><?=__('Changelog')?></th>
                <th class="text-end"><?=__('Downloads')?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($versions as $v):
                $files = db_all("SELECT * FROM plugin_files WHERE plugin_version_id=? ORDER BY id", [$v['id']]);
                ?>
                <tr>
                    <td class="fw-semibold">v<?=e($v['version'])?></td>
                    <td><span class="badge text-bg-info"><?=e($v['channel'])?></span></td>
                    <td><?=e($v['min_syrve'])?></td>
                    <td><?=e($v['released_at'])?></td>
                    <td>
                        <?php
                        if($files){
                            foreach($files as $f){
                                echo '<span class="badge bg-secondary me-1">'.e($f['label'] ?: basename($f['file_path'])).'</span>';
                            }
                        } elseif($v['file_path']){
                            echo '<span class="badge bg-secondary">'.__('file').'</span>';
                        } else {
                            echo '<span class="text-muted">—</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if (trim((string)$v['changelog_md']) !== ''): ?>
                            <button class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#ch-<?=$v['id']?>">
                                <?=__('View')?>
                            </button>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if($files){
                            echo '<div class="btn-group">';
                            echo '<a class="btn btn-sm btn-primary" href="'.e($files[0]['file_path']).'" download>'.__('Download').'</a>';
                            if(count($files)>1){
                                echo '<button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">'.__('More files').'</button>';
                                echo '<ul class="dropdown-menu dropdown-menu-end">';
                                foreach($files as $f){
                                    echo '<li><a class="dropdown-item" href="'.e($f['file_path']).'" download>'.e($f['label'] ?: basename($f['file_path'])).'</a></li>';
                                }
                                echo '</ul>';
                            }
                            echo '</div>';
                        } elseif($v['file_path']) {
                            echo '<a class="btn btn-sm btn-primary" href="'.e($v['file_path']).'" download>'.__('Download').'</a>';
                        } else {
                            echo '<span class="text-muted">'.__('No files').'</span>';
                        } ?>
                    </td>
                </tr>
            <?php endforeach; if(empty($versions)) echo '<tr><td colspan="7" class="text-center text-muted">'.__('No versions yet').'</td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach($versions as $v): if (trim((string)$v['changelog_md'])==='') continue; ?>
    <div class="modal fade" id="ch-<?=$v['id']?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?=e($pl['name'])?> — v<?=e($v['version'])?> · <?=__('Changelog')?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?=__('Close')?>"></button>
                </div>
                <div class="modal-body">
                    <article class="prose small">
                        <?= nl2br(e($v['changelog_md'])) ?>
                    </article>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include __DIR__.'/partials_footer.php'; ?>
