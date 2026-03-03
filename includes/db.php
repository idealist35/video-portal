<?php
/**
 * SQLite Database Connection & Schema Initialization
 * 
 * Auto-creates tables on first run. Uses WAL mode for concurrent read/write.
 */

require_once __DIR__ . '/config.php';

/**
 * Get or create the singleton PDO connection.
 */
function getDB(): PDO
{
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    // Ensure data directory exists
    $dataDir = dirname(DB_PATH);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $isNew = !file_exists(DB_PATH);

    $db = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // WAL mode — allows concurrent reads during writes
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    // Create schema on first run
    if ($isNew) {
        initSchema($db);
    }

    return $db;
}

/**
 * Create all tables.
 */
function initSchema(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            is_verified INTEGER DEFAULT 0,
            subscription_until TEXT,
            verify_token TEXT,
            reset_token TEXT,
            reset_token_expires TEXT,
            remember_token TEXT,
            api_token TEXT UNIQUE,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS videos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            r2_key TEXT NOT NULL,
            thumbnail TEXT,
            category TEXT,
            is_free INTEGER DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            duration INTEGER DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            video_id INTEGER,
            watched_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (video_id) REFERENCES videos(id)
        );

        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            attempted_at TEXT DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
        CREATE INDEX IF NOT EXISTS idx_users_remember ON users(remember_token);
        CREATE INDEX IF NOT EXISTS idx_users_api_token ON users(api_token);
        CREATE INDEX IF NOT EXISTS idx_videos_category ON videos(category);
        CREATE INDEX IF NOT EXISTS idx_views_user ON views(user_id);
        CREATE INDEX IF NOT EXISTS idx_views_video ON views(video_id);
        CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email);
    ");
}
