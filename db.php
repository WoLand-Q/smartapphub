<?php
declare(strict_types=1);

/**
 * SQLite connection + migrations
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dataDir = __DIR__ . '/data';
        if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);

        $pdo = new PDO('sqlite:' . $dataDir . '/app.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Немного здравых дефолтов для SQLite
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');

        migrate($pdo);
    }
    return $pdo;
}

/* helpers for additive migrations */
function col_exists(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->query("PRAGMA table_info($table)");
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (strcasecmp((string)$r['name'], $col) === 0) return true;
    }
    return false;
}
function add_col_if_missing(PDO $pdo, string $table, string $col, string $typeSql): void {
    if (!col_exists($pdo, $table, $col)) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $col $typeSql");
    }
}

function migrate(PDO $pdo): void {
    /* -------- base tables -------- */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS news(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL,
            body_md    TEXT NOT NULL DEFAULT '',
            body_html  TEXT NOT NULL DEFAULT '',
            tags       TEXT DEFAULT '',
            created_at TEXT NOT NULL,
            author     TEXT DEFAULT ''
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS docs(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title          TEXT NOT NULL,
            description_md TEXT NOT NULL,
            link           TEXT DEFAULT '',
            file_path      TEXT DEFAULT '',
            tags           TEXT DEFAULT '',
            updated_at     TEXT NOT NULL
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugins(
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            slug      TEXT NOT NULL UNIQUE,
            name      TEXT NOT NULL,
            description TEXT NOT NULL,
            repo_url  TEXT DEFAULT '',
            homepage  TEXT DEFAULT '',
            is_active INTEGER NOT NULL DEFAULT 1,
            category  TEXT NOT NULL DEFAULT ''
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_versions(
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            plugin_id   INTEGER NOT NULL REFERENCES plugins(id) ON DELETE CASCADE,
            version     TEXT NOT NULL,
            channel     TEXT NOT NULL,
            min_syrve   TEXT NOT NULL,
            released_at TEXT NOT NULL,
            file_path   TEXT DEFAULT '',
            checksum    TEXT DEFAULT '',
            changelog_md TEXT DEFAULT ''
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS syrve_releases(
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            name           TEXT NOT NULL,
            slug           TEXT NOT NULL UNIQUE,
            released_at    TEXT NOT NULL,
            channel        TEXT NOT NULL,
            is_recommended INTEGER NOT NULL DEFAULT 0,
            is_lt          INTEGER NOT NULL DEFAULT 0,
            notes_html     TEXT NOT NULL DEFAULT ''
        );
    ");

    // users for auth
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username     TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role         TEXT NOT NULL DEFAULT 'editor',
            is_active    INTEGER NOT NULL DEFAULT 1,
            created_at   TEXT NOT NULL
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
            label      TEXT NOT NULL DEFAULT '',
            file_path  TEXT NOT NULL,
            checksum   TEXT NOT NULL DEFAULT '',
            ext        TEXT NOT NULL DEFAULT '',
            size_bytes INTEGER NOT NULL DEFAULT 0
        );
    ");
    add_col_if_missing($pdo, 'plugin_files', 'ext', "TEXT NOT NULL DEFAULT ''");
    add_col_if_missing($pdo, 'plugin_files', 'size_bytes', "INTEGER NOT NULL DEFAULT 0");

    // uploads dirs
    $u = __DIR__ . '/uploads';
    if (!is_dir($u)) mkdir($u, 0777, true);
    foreach (['docs','plugins','images'] as $d) {
        if (!is_dir("$u/$d")) mkdir("$u/$d", 0777, true);
    }

    /* ---------- FTS5 Search (поиск по всему хабу) ---------- */
    // ВАЖНО: FTS5 должен быть собран в PHP (обычно есть в оф. образах).
    $pdo->exec("
        CREATE VIRTUAL TABLE IF NOT EXISTS search_all USING fts5(
            type,              -- 'news' | 'doc' | 'plugin'
            rid UNINDEXED,     -- id строки в своей таблице
            title,
            body,
            tags,
            tokenize='unicode61'
        );
    ");

    /* Триггеры NEWS */
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_news_ai AFTER INSERT ON news BEGIN
          INSERT INTO search_all(type,rid,title,body,tags)
          VALUES('news', NEW.id, NEW.title, COALESCE(NULLIF(NEW.body_html,''), NEW.body_md), COALESCE(NEW.tags,''));
        END;
    ");
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_news_au AFTER UPDATE ON news BEGIN
          DELETE FROM search_all WHERE type='news' AND rid=OLD.id;
          INSERT INTO search_all(type,rid,title,body,tags)
          VALUES('news', NEW.id, NEW.title, COALESCE(NULLIF(NEW.body_html,''), NEW.body_md), COALESCE(NEW.tags,''));
        END;
    ");
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_news_ad AFTER DELETE ON news BEGIN
          DELETE FROM search_all WHERE type='news' AND rid=OLD.id;
        END;
    ");

    /* Триггеры DOCS */
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_docs_ai AFTER INSERT ON docs BEGIN
          INSERT INTO search_all(type,rid,title,body,tags)
          VALUES('doc', NEW.id, NEW.title, NEW.description_md, COALESCE(NEW.tags,''));
        END;
    ");
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_docs_au AFTER UPDATE ON docs BEGIN
          DELETE FROM search_all WHERE type='doc' AND rid=OLD.id;
          INSERT INTO search_all(type,rid,title,body,tags)
          VALUES('doc', NEW.id, NEW.title, NEW.description_md, COALESCE(NEW.tags,''));
        END;
    ");
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_docs_ad AFTER DELETE ON docs BEGIN
          DELETE FROM search_all WHERE type='doc' AND rid=OLD.id;
        END;
    ");

    /* Триггеры PLUGINS */
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_plugins_ai AFTER INSERT ON plugins BEGIN
          INSERT INTO search_all(type,rid,title,body,tags)
          VALUES('plugin', NEW.id, NEW.name, NEW.description, COALESCE(NEW.category,''));
        END;
    ");
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_plugins_au AFTER UPDATE ON plugins BEGIN
          DELETE FROM search_all WHERE type='plugin' AND rid=OLD.id;
          INSERT INTO search_all(type,rid,title,body,tags)
          VALUES('plugin', NEW.id, NEW.name, NEW.description, COALESCE(NEW.category,''));
        END;
    ");
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_plugins_ad AFTER DELETE ON plugins BEGIN
          DELETE FROM search_all WHERE type='plugin' AND rid=OLD.id;
        END;
    ");

    /* Первичное наполнение индекса — только если пусто */
    try {
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM search_all")->fetchColumn();
    } catch (Throwable $e) {
        $cnt = 0;
    }
    if ($cnt === 0) {
        $pdo->exec("
            INSERT INTO search_all(type,rid,title,body,tags)
            SELECT 'news', id, title, COALESCE(NULLIF(body_html,''), body_md), COALESCE(tags,'') FROM news;
        ");
        $pdo->exec("
            INSERT INTO search_all(type,rid,title,body,tags)
            SELECT 'doc', id, title, description_md, COALESCE(tags,'') FROM docs;
        ");
        $pdo->exec("
            INSERT INTO search_all(type,rid,title,body,tags)
            SELECT 'plugin', id, name, description, COALESCE(category,'') FROM plugins;
        ");
    }
}
