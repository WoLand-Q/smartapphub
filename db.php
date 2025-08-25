<?php
// SQLite connection + migrations
function db(){
    static $pdo = null;
    if($pdo === null){
        $dataDir = __DIR__ . '/data';
        if(!is_dir($dataDir)) mkdir($dataDir, 0777, true);
        $pdo = new PDO('sqlite:' . $dataDir . '/app.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        migrate($pdo);
    }
    return $pdo;
}

/* helpers for additive migrations */
function col_exists(PDO $pdo, $table, $col){
    $st = $pdo->query("PRAGMA table_info($table)");
    foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){
        if(strcasecmp($r['name'],$col)===0) return true;
    }
    return false;
}
function add_col_if_missing(PDO $pdo, $table, $col, $typeSql){
    if(!col_exists($pdo,$table,$col)){
        $pdo->exec("ALTER TABLE $table ADD COLUMN $col $typeSql");
    }
}

function migrate(PDO $pdo){
    // -------- base tables --------
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS news(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            body_md TEXT NOT NULL DEFAULT '',
            body_html TEXT NOT NULL DEFAULT '',
            tags TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            author TEXT DEFAULT ''
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS docs(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description_md TEXT NOT NULL,
            link TEXT DEFAULT '',
            file_path TEXT DEFAULT '',
            tags TEXT DEFAULT '',
            updated_at TEXT NOT NULL
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugins(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            description TEXT NOT NULL,
            repo_url TEXT DEFAULT '',
            homepage TEXT DEFAULT '',
            is_active INTEGER NOT NULL DEFAULT 1,
            category TEXT NOT NULL DEFAULT ''
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_versions(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plugin_id INTEGER NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
            version TEXT NOT NULL,
            channel TEXT NOT NULL,
            min_syrve TEXT NOT NULL,
            released_at TEXT NOT NULL,
            file_path TEXT DEFAULT '',
            checksum TEXT DEFAULT '',
            changelog_md TEXT DEFAULT ''
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS syrve_releases(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            released_at TEXT NOT NULL,
            channel TEXT NOT NULL,
            is_recommended INTEGER NOT NULL DEFAULT 0,
            is_lt INTEGER NOT NULL DEFAULT 0,
            notes_html TEXT NOT NULL DEFAULT ''
        );
    ");

    // users for auth
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'editor',
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL
        );
    ");

    // ---- additive columns for older DBs ----
    add_col_if_missing($pdo, 'news', 'body_html', "TEXT NOT NULL DEFAULT ''");
    add_col_if_missing($pdo, 'syrve_releases', 'notes_html', "TEXT NOT NULL DEFAULT ''");
    add_col_if_missing($pdo, 'plugins', 'category', "TEXT NOT NULL DEFAULT ''");

    // ---- per-version multiple files ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_files(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plugin_version_id INTEGER NOT NULL REFERENCES plugin_versions(id) ON DELETE CASCADE,
            label TEXT NOT NULL DEFAULT '',
            file_path TEXT NOT NULL,
            checksum TEXT NOT NULL DEFAULT '',
            ext TEXT NOT NULL DEFAULT '',
            size_bytes INTEGER NOT NULL DEFAULT 0
        );
    ");
    add_col_if_missing($pdo, 'plugin_files', 'ext', "TEXT NOT NULL DEFAULT ''");
    add_col_if_missing($pdo, 'plugin_files', 'size_bytes', "INTEGER NOT NULL DEFAULT 0");

    // uploads dirs
    $u = __DIR__ . '/uploads';
    if(!is_dir($u)) mkdir($u,0777,true);
    foreach(['docs','plugins','images'] as $d){
        if(!is_dir("$u/$d")) mkdir("$u/$d",0777,true);
    }
}
