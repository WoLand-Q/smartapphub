<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Smart Apps · Hub</title>

    <!-- Early theme boot (no FOUC) -->
    <script>
        (function () {
            const KEY = 'sa-theme'; // 'light' | 'dark' | 'system'
            let pref = localStorage.getItem(KEY) || 'system';
            if (pref === 'system') {
                pref = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', pref);
            document.documentElement.dataset.bsTheme = pref; // Bootstrap 5.3
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top border-bottom bg-body">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="index.php">Smart Apps · Hub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <!-- left: menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?=nav_active('index.php')?>" href="index.php">Огляд</a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('news.php')?>" href="news.php">Новини</a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('releases.php')?>" href="releases.php">Релізи</a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('plugins.php')?>" href="plugins.php">Інтеграції</a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('docs.php')?>" href="docs.php">Документація</a></li>
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link <?=nav_active('admin.php')?>" href="admin.php">Адмін</a></li>
                <?php endif; ?>
            </ul>

            <!-- right: search + theme + profile -->
            <form class="d-flex me-2" method="get" action="search.php" role="search">
                <input class="form-control" name="q" placeholder="Пошук по всьому хабу" aria-label="Пошук">
                <button class="btn btn-outline-primary ms-2" type="submit">Пошук</button>
            </form>

            <div class="dropdown me-2">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" id="themeToggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Тема
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="themeToggle">
                    <li><button class="dropdown-item" data-theme="light">Світла</button></li>
                    <li><button class="dropdown-item" data-theme="dark">Темна</button></li>
                    <li><button class="dropdown-item" data-theme="system">Системна</button></li>
                </ul>
            </div>

            <?php if (is_logged_in()): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <?=e(current_user()['username'])?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (is_admin()): ?>
                            <li><a class="dropdown-item" href="admin.php">Адмін панель</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="logout.php">Вихід</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="btn btn-outline-secondary btn-sm" href="login.php">Вхід</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container my-4">
