<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
?>
<!doctype html>
<html lang="<?=e(current_lang())?>">
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
    <link rel="icon" href="/uploads/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/uploads/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/uploads/images/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/uploads/images/apple-touch-icon.png">
    <meta name="theme-color" content="#111827">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Top progress bar -->
<div id="sa-nprog"></div>

<nav class="navbar navbar-expand-lg sticky-top border-bottom bg-body">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="index.php">Smart Apps · Hub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="<?=__('Toggle navigation')?>">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <!-- left: menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?=nav_active('index.php')?>"    href="index.php"><?=__('Overview')?></a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('news.php')?>"     href="news.php"><?=__('News')?></a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('releases.php')?>" href="releases.php"><?=__('Releases')?></a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('plugins.php')?>"  href="plugins.php"><?=__('Plugins')?></a></li>
                <li class="nav-item"><a class="nav-link <?=nav_active('docs.php')?>"     href="docs.php"><?=__('Docs')?></a></li>
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link <?=nav_active('admin.php')?>" href="admin.php"><?=__('Admin')?></a></li>
                <?php endif; ?>
            </ul>

            <!-- right: search + language + theme + profile -->
            <form class="d-flex me-2" method="get" action="search.php" role="search" id="sa-search-form" autocomplete="off">
                <div class="position-relative" style="min-width:260px">
                    <input
                            class="form-control"
                            id="sa-search"
                            name="q"
                            placeholder="<?=__('Search across hub')?>"
                            aria-label="<?=__('Search')?>"
                            inputmode="search"
                            autocapitalize="off"
                            spellcheck="false"
                            autocomplete="off"
                            data-1p-ignore
                            data-lpignore="true"
                    >
                    <div id="sa-suggest" class="dropdown-menu w-100 shadow" style="max-height:320px; overflow:auto;"></div>
                </div>
                <button class="btn btn-outline-primary ms-2" type="submit"><?=__('Search')?></button>
            </form>

            <!-- Language -->
            <div class="dropdown me-2">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <?=__('Language')?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?=url_with(['lang'=>'uk'])?>"><?=__('Ukrainian')?></a></li>
                    <li><a class="dropdown-item" href="<?=url_with(['lang'=>'ru'])?>"><?=__('Russian')?></a></li>
                    <li><a class="dropdown-item" href="<?=url_with(['lang'=>'en'])?>"><?=__('English')?></a></li>
                </ul>
            </div>

            <!-- Theme -->
            <div class="dropdown me-2">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" id="themeToggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <?=__('Theme')?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="themeToggle">
                    <li><button class="dropdown-item" data-theme="light"><?=__('Light')?></button></li>
                    <li><button class="dropdown-item" data-theme="dark"><?=__('Dark')?></button></li>
                    <li><button class="dropdown-item" data-theme="system"><?=__('System')?></button></li>
                </ul>
            </div>

            <!-- Profile -->
            <?php if (is_logged_in()): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <?=e(current_user()['username'])?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (is_admin()): ?>
                            <li><a class="dropdown-item" href="admin.php"><?=__('Admin panel')?></a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="logout.php"><?=__('Logout')?></a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="btn btn-outline-secondary btn-sm" href="login.php"><?=__('Login')?></a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- UI helpers: progress, search suggest, navbar behavior, responsive tables, entrance effects -->
<script>
    /* ---------- progress for navigation/fetch ---------- */
    (function(){
        const bar = document.getElementById('sa-nprog');
        if (!bar) return;
        function start(){ bar.classList.add('active'); bar.style.width = '18%'; requestAnimationFrame(()=> bar.style.width='62%'); }
        function done(){ bar.style.width='100%'; setTimeout(()=>{ bar.classList.remove('active'); bar.style.width='0%'; }, 260); }
        window.addEventListener('beforeunload', start);
        const _fetch = window.fetch;
        window.fetch = function(){ start(); return _fetch.apply(this, arguments).finally(done); };
        window.addEventListener('pageshow', ()=> setTimeout(done, 120));
    })();

    /* ---------- smart suggest for search ---------- */
    (function(){
        const form = document.getElementById('sa-search-form');
        const input = document.getElementById('sa-search');
        const menu  = document.getElementById('sa-suggest');
        if (!form || !input || !menu) return;
        let t, ix = -1, items = [];
        const DEBOUNCE = 180;

        const esc = s => (s??'').replace(/[&<>"]/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
        function show(){ menu.classList.add('show'); menu.style.display='block'; }
        function hide(){ menu.classList.remove('show'); menu.style.display='none'; ix=-1; }
        function setActive(i){
            [...menu.querySelectorAll('.dropdown-item')].forEach((el,k)=> el.classList.toggle('active', k===i));
            ix = i;
        }
        function build(list, q){
            if (!Array.isArray(list) || !list.length){ hide(); return; }
            const qre = q ? new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','ig') : null;
            menu.innerHTML = list.map(it=>{
                const title = esc(it.title);
                const type  = esc(it.type||'');
                const url   = esc(it.url||'#');
                const ttl   = qre ? title.replace(qre,'<mark>$1</mark>') : title;
                return `<a href="${url}" class="dropdown-item d-flex align-items-center gap-2" data-url="${url}">
                        <span class="badge text-bg-secondary">${type||'hub'}</span>
                        <span class="flex-grow-1 text-truncate">${ttl}</span>
                    </a>`;
            }).join('');
            items = [...menu.querySelectorAll('.dropdown-item')];
            show();
        }
        function fetchSuggest(q){
            if (!q || q.trim().length < 2){ hide(); return; }
            fetch('search_suggest.php?q=' + encodeURIComponent(q.trim()))
                .then(r=>r.ok ? r.json() : {items:[]})
                .then(data => build(data.items || [], q))
                .catch(()=> hide());
        }
        input.addEventListener('input', (e)=>{
            clearTimeout(t);
            const q = e.target.value;
            t = setTimeout(()=> fetchSuggest(q), DEBOUNCE);
        });
        input.addEventListener('keydown', (e)=>{
            const count = items.length;
            if (!count) return;
            if (e.key === 'ArrowDown'){ e.preventDefault(); setActive( (ix+1) % count ); }
            else if (e.key === 'ArrowUp'){ e.preventDefault(); setActive( (ix-1+count) % count ); }
            else if (e.key === 'Enter' && ix >= 0){ e.preventDefault(); const a = items[ix]; if (a && a.dataset.url){ window.location.href = a.dataset.url; } }
            else if (e.key === 'Escape'){ hide(); }
        });
        document.addEventListener('click', (e)=>{ if (!menu.contains(e.target) && e.target !== input) hide(); });
        menu.addEventListener('click', (e)=>{ const a = e.target.closest('.dropdown-item'); if (!a) return; e.preventDefault(); window.location.href = a.dataset.url || a.getAttribute('href'); });
        let autoT;
        input.addEventListener('input', ()=>{ clearTimeout(autoT); const val = input.value.trim(); if (!val) return; autoT = setTimeout(()=> { if (!menu.classList.contains('show')) form.requestSubmit(); }, 450); });
    })();

    /* ---------- navbar shrink on scroll ---------- */
    (function(){
        const nav = document.querySelector('.navbar.bg-body');
        if(!nav) return;
        const on = () => nav.classList.toggle('nav-scrolled', window.scrollY>12);
        document.addEventListener('scroll', on, {passive:true}); on();
    })();

    /* ---------- auto data-th for responsive tables ---------- */
    (function(){
        document.querySelectorAll('table.responsive-cards').forEach(table=>{
            const headRow = (table.tHead && table.tHead.rows[0]) ? table.tHead.rows[0] : null;
            const heads = headRow ? [...headRow.cells].map(th=>th.textContent.trim()) : [];
            table.querySelectorAll('tbody tr').forEach(tr=>{
                [...tr.cells].forEach((td,i)=>{ if(!td.hasAttribute('data-th')) td.setAttribute('data-th', heads[i]||''); });
            });
        });
    })();

    /* ---------- entrance animations ---------- */
    (function(){
        const show = el => el.classList.add('sa-entrance');
        const io = new IntersectionObserver(es => es.forEach(e => { if(e.isIntersecting){ show(e.target); io.unobserve(e.target); } }), {threshold:.06});
        document.querySelectorAll('.card, .table tbody tr').forEach(el => io.observe(el));
    })();
</script>

<div class="container my-4">
