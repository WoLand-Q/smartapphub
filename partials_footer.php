</div><!-- /.container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Theme: apply + persist + react to System changes + smooth transition
    (function () {
        const KEY = 'sa-theme'; // light | dark | system
        const mq = matchMedia('(prefers-color-scheme: dark)');
        const menu = document.getElementById('themeToggle');
        const icons = { light:'🌞 Світла', dark:'🌙 Темна', system:'🖥️ Системна' };

        function effectiveTheme(saved) {
            if (saved === 'system') return mq.matches ? 'dark' : 'light';
            return saved || 'light';
        }
        function setLabel(saved){
            if (!menu) return;
            const cur = saved || localStorage.getItem(KEY) || 'system';
            menu.innerText = icons[cur] ? icons[cur].split(' ')[0] : 'Тема';
            document.querySelectorAll('[data-theme]').forEach(i=>{
                i.classList.toggle('active', i.dataset.theme===cur);
            });
        }
        function apply(theme) {
            document.documentElement.classList.add('theming');
            const eff = effectiveTheme(theme);
            document.documentElement.setAttribute('data-theme', eff);
            document.documentElement.dataset.bsTheme = eff;
            localStorage.setItem(KEY, theme);
            setLabel(theme);
            setTimeout(()=>document.documentElement.classList.remove('theming'), 300);
        }
        document.querySelectorAll('[data-theme]').forEach(btn => {
            btn.addEventListener('click', () => apply(btn.dataset.theme));
        });
        mq.addEventListener('change', () => {
            const saved = localStorage.getItem(KEY) || 'system';
            if (saved === 'system') apply('system');
        });
        setLabel(localStorage.getItem(KEY)||'system');
    })();

    // Debounced search submit (ignore empty)
    (function(){
        const form = document.getElementById('sa-search-form');
        if (!form) return;
        const input   = document.getElementById('sa-search');
        const menu    = document.getElementById('sa-suggest');
        let timer, idx = -1;

        function hide(){ menu.style.display='none'; menu.innerHTML=''; idx = -1; }
        function show(){ menu.style.display='block'; }

        function render(items){
            if (!items.length) { hide(); return; }
            menu.innerHTML = items.map((it,i)=>`
      <a href="${it.url}" class="dropdown-item${i===0?' active':''}" data-i="${i}">
        <span class="text-muted small me-2">[${it.type}]</span>${it.title ? it.title.replace(/</g,'&lt;') : ''}
      </a>
    `).join('');
            idx = 0; show();
        }

        async function query(q){
            const r = await fetch('search_suggest.php?q='+encodeURIComponent(q));
            if (!r.ok) return hide();
            const items = await r.json();
            render(items);
        }

        input.addEventListener('input', ()=>{
            clearTimeout(timer);
            const q = input.value.trim();
            if (!q){ hide(); return; }
            timer = setTimeout(()=>query(q), 180);
        });

        input.addEventListener('focus', ()=>{
            if (menu.innerHTML) show();
        });

        document.addEventListener('click', (e)=>{
            if (!form.contains(e.target)) hide();
        });

        // клавиатура: стрелки и enter
        input.addEventListener('keydown', (e)=>{
            const links = [...menu.querySelectorAll('.dropdown-item')];
            if (!links.length) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                idx += (e.key === 'ArrowDown' ? 1 : -1);
                if (idx < 0) idx = links.length-1;
                if (idx >= links.length) idx = 0;
                links.forEach((a,i)=>a.classList.toggle('active', i===idx));
            } else if (e.key === 'Enter') {
                if (idx >= 0 && links[idx]) {
                    e.preventDefault();
                    window.location = links[idx].getAttribute('href');
                }
            } else if (e.key === 'Escape') {
                hide();
            }
        });
    })();
</script>
<script>
    (function(){
        document.querySelectorAll('table.responsive-cards').forEach(table=>{
            const heads=[...table.querySelectorAll('thead th')].map(th=>th.textContent.trim());
            table.querySelectorAll('tbody tr').forEach(tr=>{
                tr.querySelectorAll('td').forEach((td,i)=>{
                    if(!td.hasAttribute('data-th')) td.setAttribute('data-th', heads[i]||'');
                });
            });
        });
    })();
</script>
<script>
    (function(){
        const nav = document.querySelector('.navbar.bg-body');
        if(!nav) return;
        const on = () => nav.classList.toggle('nav-scrolled', window.scrollY>12);
        document.addEventListener('scroll', on, {passive:true}); on();
    })();
</script>
<script>
    (function(){
        const show = el => el.classList.add('sa-entrance');
        const io = new IntersectionObserver(es => es.forEach(e => {
            if(e.isIntersecting){ show(e.target); io.unobserve(e.target); }
        }), {threshold:.06});
        document.querySelectorAll('.card, .table tbody tr').forEach(el => io.observe(el));
    })();
</script>

</body>
</html>
