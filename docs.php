<?php
include __DIR__.'/partials_header.php';

$docs = db_all("SELECT * FROM docs ORDER BY datetime(updated_at) DESC");
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><?=__('Overview')?></a></li>
        <li class="breadcrumb-item active"><?=__('Documentation')?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h3 class="mb-0"><?=__('Documentation')?></h3>
    <?php if(is_admin()): ?>
        <a class="btn btn-sm btn-primary" href="docs_edit.php">+ <?=__('Add document')?></a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0 responsive-cards">
            <thead>
            <tr>
                <th><?=__('Name')?></th>
                <th><?=__('Tags')?></th>
                <th><?=__('Updated')?></th>
                <th class="text-end"><?=__('Actions')?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($docs as $d): ?>
                <tr class="doc-row">
                    <td class="fw-semibold"><a href="docs_view.php?id=<?=$d['id']?>"><?=e($d['title'])?></a></td>
                    <td class="small text-muted"><?=e($d['tags'])?></td>
                    <td class="small text-muted"><?=e($d['updated_at'])?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="docs_view.php?id=<?=$d['id']?>"><?=__('Open')?></a>
                        <?php if(is_admin()): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="docs_edit.php?id=<?=$d['id']?>"><?=__('Edit')?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; if(empty($docs)) echo '<tr><td colspan="4" class="text-center text-muted">'.__('No documents').'</td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
