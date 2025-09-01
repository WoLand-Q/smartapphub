<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__.'/db.php';

/* ================== I18N bootstrap (единственная) ================== */
/* Если __() уже объявлена — значит кто-то подключил другой helper; не конфликтуем. */
if (!function_exists('__')) {

    /** Список поддерживаемых языков (коды совпадают с файлами /lang/<code>.php) */
    function sa_supported_langs(): array { return ['uk','ru','en']; }

    /** Выбрать текущий язык: сначала ?lang=, потом cookie, иначе 'uk' */
    function sa_pick_lang(): string {
        $lang = strtolower($_GET['lang'] ?? ($_COOKIE['sa_lang'] ?? 'uk'));
        if (!in_array($lang, sa_supported_langs(), true)) $lang = 'uk';
        // запомним выбор пользователя
        if (isset($_GET['lang'])) {
            setcookie('sa_lang', $lang, time()+60*60*24*365, '/');
        }
        $GLOBALS['SA_LANG'] = $lang;
        return $lang;
    }

    /** Загрузить словарь из /lang/<code>.php (файл должен return [ 'Key' => 'Строка', ... ]) */
    function sa_load_dict(string $lang): array {
        $file = __DIR__ . "/lang/{$lang}.php";
        if (is_file($file)) {
            $arr = include $file;
            if (is_array($arr)) return $arr;
        }
        // запасной вариант — английский
        $fallback = __DIR__ . "/lang/en.php";
        return is_file($fallback) ? (include $fallback) : [];
    }

    // инициализация словаря при загрузке helper'а
    $GLOBALS['I18N'] = sa_load_dict(sa_pick_lang());

    /** Перевод по ключу; если ключа нет — возвращаем сам ключ */
    function __($key): string {
        $d = $GLOBALS['I18N'] ?? [];
        return (string)($d[$key] ?? $key);
    }

    /** Эхо-версия */
    function _e($key): void { echo __($key); }

    /** Текущий код языка */
    function current_lang(): string { return $GLOBALS['SA_LANG'] ?? 'uk'; }
}
/* ================== /I18N bootstrap ================== */

/* --------- utils --------- */
function e($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function h($s){ return e($s); } // alias
function now(){ return date('Y-m-d H:i'); }
function slugify($text){
    $text = preg_replace('~[\pP\pZ]+~u', '-', (string)$text);
    $text = strtolower(trim($text, '-'));
    return $text ?: ('item-' . bin2hex(random_bytes(2)));
}

/** Построить текущий URL, подменив/добавив query-параметры (напр. ['lang'=>'ru']) */
function url_with(array $ov): string {
    $qs = array_merge($_GET, $ov);
    $base = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    return $base . ( $qs ? ('?' . http_build_query($qs)) : '' );
}

/* --------- DB helpers (SQL + params) --------- */
function db_all($sql, $params = []){
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
function db_one($sql, $params = []){
    $st = db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function nav_active($file){ return basename($_SERVER['PHP_SELF']) === $file ? 'active' : ''; }

/* --------- Markdown -> HTML (простой) --------- */
function md_to_html($md){
    $html = e($md);
    // блоки кода ```...```
    $html = preg_replace('/```([\s\S]*?)```/m', '<pre><code>$1</code></pre>', $html);
    // списки
    $html = preg_replace('/^\s*\-\s+(.*)$/m', '<li>$1</li>', $html);
    $html = preg_replace('/(<li>.*<\/li>\s*)+/s', '<ul>$0</ul>', $html);
    // ссылки [text](http)
    $html = preg_replace('/\[(.*?)\]\((https?:[^\s]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $html);
    return nl2br($html);
}
function md($s){ return md_to_html($s); } // alias

/* --------- auth --------- */
function login($username, $password){
    $u = db_one('SELECT * FROM users WHERE username=? AND is_active=1', [$username]);
    if(!$u || !password_verify($password, $u['password_hash'])) return false;
    $_SESSION['user'] = ['id'=>$u['id'],'username'=>$u['username'],'role'=>$u['role']];
    return true;
}
function logout(){ $_SESSION = []; if (session_status()!==PHP_SESSION_NONE) session_destroy(); }
function current_user(){ return $_SESSION['user'] ?? null; }
function is_logged_in(){ return !!current_user(); }
function is_admin(){ $u=current_user(); return $u && $u['role']==='admin'; }
function require_login(){
    if(!is_logged_in()){
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'])); exit;
    }
}
function require_admin(){
    require_login();
    if(!is_admin()){
        http_response_code(403);
        echo "<div class='container my-5'><h4>403 · " . e(__('Admins only')) . "</h4></div>";
        exit;
    }
}

/* --------- другое --------- */
function normalize_syrve_short(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    $parts = explode('.', $s);
    return count($parts) >= 2 ? ($parts[0].'.'.$parts[1]) : $s;
}
