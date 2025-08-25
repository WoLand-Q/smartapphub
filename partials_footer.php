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
        const f = document.querySelector('form[action="search.php"]');
        if (!f) return;
        const i = f.querySelector('input[name="q"]');
        let t;
        i.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => { if (i.value.trim()) f.requestSubmit(); }, 450);
        });
    })();
</script>
</body>
</html>
