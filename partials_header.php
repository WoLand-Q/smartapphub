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

    <!-- Early boot: theme (no FOUC) + accent -->
    <script>
        (function () {
            const THEME_KEY  = 'sa-theme';   // 'light' | 'dark' | 'system'
            const ACCENT_KEY = 'sa-accent';  // 'ember' | 'cyan' | 'grape' | 'mint' | 'gold'

            let theme = localStorage.getItem(THEME_KEY) || 'system';
            if (theme === 'system') {
                theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.dataset.bsTheme = theme; // Bootstrap 5.3

            let accent = localStorage.getItem(ACCENT_KEY) || 'ember';
            document.documentElement.setAttribute('data-accent', accent);
        })();
    </script>

    <link rel="icon" href="/uploads/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/uploads/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/uploads/images/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/uploads/images/apple-touch-icon.png">
    <meta name="theme-color" content="#111827">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">

    <!-- Local tweaks for Electric cards (keeps Bootstrap border from fighting the glow) -->
    <style>
        .card.electric{ border-color:transparent; position:relative; overflow:clip; border-radius:var(--sa-radius); isolation:isolate; }
        .electric--hover .ebx__layer{ opacity:0; transition:opacity var(--sa-time-2) var(--sa-ease); }
        .electric--hover:hover .ebx__layer{ opacity:1; }
        .electric--on .ebx__layer{ opacity:1; }
        /* маленькие цветные точки для меню Accent */
        .accent-dot{ display:inline-block; width:.675rem; height:.675rem; border-radius:999px; margin-right:.5rem; vertical-align:-1px; }
        .accent-ember{ background:#dd8448; }
        .accent-cyan{  background:#22d3ee; }
        .accent-grape{ background:#8b5cf6; }
        .accent-mint{  background:#10b981; }
        .accent-gold{  background:#f59e0b; }
    </style>
</head>
<body>

<!-- Top progress bar -->
<div id="sa-nprog"></div>

<!-- SVG defs: turbulent displacement (for the “alive” edge) -->
<svg width="0" height="0" style="position:absolute">
    <defs>
        <filter id="turbulent-displace" color-interpolation-filters="sRGB" x="-20%" y="-20%" width="140%" height="140%">
            <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="10" result="noise1" seed="1"/>
            <feOffset in="noise1" dx="0" dy="0" result="offset1">
                <animate attributeName="dy" values="700;0" dur="6s" repeatCount="indefinite" calcMode="linear"/>
            </feOffset>

            <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="10" result="noise2" seed="1"/>
            <feOffset in="noise2" dx="0" dy="0" result="offset2">
                <animate attributeName="dy" values="0;-700" dur="6s" repeatCount="indefinite" calcMode="linear"/>
            </feOffset>

            <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="10" result="noise3" seed="2"/>
            <feOffset in="noise3" dx="0" dy="0" result="offset3">
                <animate attributeName="dx" values="490;0" dur="6s" repeatCount="indefinite" calcMode="linear"/>
            </feOffset>

            <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="10" result="noise4" seed="2"/>
            <feOffset in="noise4" dx="0" dy="0" result="offset4">
                <animate attributeName="dx" values="0;-490" dur="6s" repeatCount="indefinite" calcMode="linear"/>
            </feOffset>

            <feComposite in="offset1" in2="offset2" result="part12"/>
            <feComposite in="offset3" in2="offset4" result="part34"/>
            <feBlend in="part12" in2="part34" mode="color-dodge" result="combinedNoise"/>
            <feDisplacementMap in="SourceGraphic" in2="combinedNoise" scale="30" xChannelSelector="R" yChannelSelector="B"/>
        </filter>
    </defs>
</svg>

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

            <!-- right: search + language + theme + accent + profile -->
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
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="themeToggle" id="sa-theme-menu">
                    <li><button class="dropdown-item" data-theme="light"><?=__('Light')?></button></li>
                    <li><button class="dropdown-item" data-theme="dark"><?=__('Dark')?></button></li>
                    <li><button class="dropdown-item" data-theme="system"><?=__('System')?></button></li>
                </ul>
            </div>

            <!-- Accent -->
            <div class="dropdown me-2">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" id="accentToggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <?=__('Accent')?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accentToggle" id="sa-accent-menu">
                    <li><button class="dropdown-item" data-accent="ember"><span class="accent-dot accent-ember"></span>Ember</button></li>
                    <li><button class="dropdown-item" data-accent="cyan"><span class="accent-dot accent-cyan"></span>Cyan</button></li>
                    <li><button class="dropdown-item" data-accent="grape"><span class="accent-dot accent-grape"></span>Grape</button></li>
                    <li><button class="dropdown-item" data-accent="mint"><span class="accent-dot accent-mint"></span>Mint</button></li>
                    <li><button class="dropdown-item" data-accent="gold"><span class="accent-dot accent-gold"></span>Gold</button></li>
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
                .then(data => build((data&&data.items)||[], q))
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

    /* ---------- Electric Border injector (EBX) ----------
       Строит внутри .electric декоративные слои. */
    (function(){
        function mount(el){
            if (el.dataset.ebxReady === '1') return;
            el.dataset.ebxReady = '1';

            const wrap = document.createElement('div');
            wrap.className = 'ebx';
            wrap.style.position = 'relative';
            wrap.style.borderRadius = 'inherit';

            const mk = cls => {
                const d = document.createElement('div');
                d.className = 'ebx__layer ' + cls;
                d.style.position = 'absolute';
                d.style.inset = '0';
                d.style.borderRadius = 'inherit';
                d.style.pointerEvents = 'none';
                return d;
            };

            const borderOuter = mk('ebx__border-outer');
            const mainEdge    = mk('ebx__main');
            mainEdge.style.border = '2px solid var(--ebx-color,var(--sa-electric,#dd8448))';
            mainEdge.style.filter = 'url(#turbulent-displace)';

            const glow1    = mk('ebx__glow1');
            const glow2    = mk('ebx__glow2');
            const overlay1 = mk('ebx__overlay1');
            const overlay2 = mk('ebx__overlay2');
            const backGlow = mk('ebx__background'); backGlow.style.zIndex = '-1';

            const content = document.createElement('div');
            content.className = 'ebx__content';
            content.style.position = 'relative';
            content.style.borderRadius = 'inherit';
            content.style.zIndex = '5';

            while (el.firstChild) content.appendChild(el.firstChild);

            wrap.appendChild(borderOuter);
            wrap.appendChild(mainEdge);
            wrap.appendChild(glow1);
            wrap.appendChild(glow2);
            wrap.appendChild(overlay1);
            wrap.appendChild(overlay2);
            wrap.appendChild(backGlow);
            wrap.appendChild(content);

            el.appendChild(wrap);
        }

        function scan(root){
            (root.querySelectorAll ? root : document).querySelectorAll('.electric').forEach(mount);
        }

        document.addEventListener('DOMContentLoaded', scan);
        const mo = new MutationObserver(muts=>{
            for (const m of muts){
                for (const n of m.addedNodes){
                    if (n.nodeType !== 1) continue;
                    if (n.matches?.('.electric')) mount(n);
                    scan(n);
                }
            }
        });
        mo.observe(document.documentElement, {subtree:true, childList:true});
    })();

    /* ---------- Theme/Accent controls ---------- */
    (function(){
        const THEME_KEY  = 'sa-theme';
        const ACCENT_KEY = 'sa-accent';

        function setTheme(t){
            localStorage.setItem(THEME_KEY, t);
            if (t === 'system'){
                t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', t);
            document.documentElement.dataset.bsTheme = t;
            // лёгкий reload убирает редкие артефакты Bootstrap
            location.reload();
        }
        function setAccent(a){
            localStorage.setItem(ACCENT_KEY, a);
            document.documentElement.setAttribute('data-accent', a);
        }

        // expose (если понадобится программно)
        window.__saSetTheme  = setTheme;
        window.__saSetAccent = setAccent;

        // wire menus
        document.getElementById('sa-theme-menu')?.addEventListener('click', (e)=>{
            const btn = e.target.closest('[data-theme]');
            if (!btn) return;
            e.preventDefault();
            setTheme(btn.getAttribute('data-theme'));
        });
        document.getElementById('sa-accent-menu')?.addEventListener('click', (e)=>{
            const btn = e.target.closest('[data-accent]');
            if (!btn) return;
            e.preventDefault();
            setAccent(btn.getAttribute('data-accent'));
        });
    })();
</script>

<div class="container my-4">
