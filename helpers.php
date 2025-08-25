<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__.'/db.php';

/* --------- utils --------- */
function e($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function h($s){ return e($s); } // alias for convenience
function now(){ return date('Y-m-d H:i'); }
function slugify($text){
    $text = preg_replace('~[\pP\pZ]+~u', '-', (string)$text);
    $text = strtolower(trim($text, '-'));
    return $text ?: ('item-' . bin2hex(random_bytes(2)));
}

/* DB helpers: только SQL и параметры */
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

/* Markdown -> HTML (простой) */
function md_to_html($md){
    $html = e($md);
    $html = preg_replace('/```([\s\S]*?)```/m', '<pre><code>$1</code></pre>', $html);
    $html = preg_replace('/^\s*\-\s+(.*)$/m', '<li>$1</li>', $html);
    $html = preg_replace('/(<li>.*<\/li>\s*)+/s', '<ul>$0</ul>', $html);
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
        echo "<div class='container my-5'><h4>403 · Доступ тільки для адміністраторів</h4></div>";
        exit;
    }
}

/* Доп. помощники для плагинов */
function normalize_syrve_short(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    $parts = explode('.', $s);
    return count($parts) >= 2 ? ($parts[0].'.'.$parts[1]) : $s;
}
