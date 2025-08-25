<?php
include __DIR__.'/partials_header.php';

$docs = db_all("SELECT * FROM docs ORDER BY datetime(updated_at) DESC");
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Огляд</a></li>
        <li class="breadcrumb-item active">Документація</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h3 class="mb-0">Документація</h3>
    <?php if(is_admin()): ?>
        <a class="btn btn-sm btn-primary" href="docs_edit.php">+ Додати документ</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Назва</th>
                <th>Теги</th>
                <th>Оновлено</th>
                <th class="text-end">Дії</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($docs as $d): ?>
                <tr class="doc-row">
                    <td class="fw-semibold"><a href="docs_view.php?id=<?=$d['id']?>"><?=e($d['title'])?></a></td>
                    <td class="small text-muted"><?=e($d['tags'])?></td>
                    <td class="small text-muted"><?=e($d['updated_at'])?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="docs_view.php?id=<?=$d['id']?>">Відкрити</a>
                        <?php if(is_admin()): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="docs_edit.php?id=<?=$d['id']?>">Редагувати</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; if(empty($docs)) echo '<tr><td colspan="4" class="text-center text-muted">Немає документів.</td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__.'/partials_footer.php'; ?>
