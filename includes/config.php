<?php
/**
 * Portal Configuration
 * 
 * Reads credentials from .env file in project root.
 * Copy .env.example to .env and fill in real values.
 */

// Load .env file
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Helper: read from .env with fallback
function env(string $key, string $default = ''): string
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// -- Site Settings --
define('SITE_TITLE', env('SITE_TITLE', 'Anime Portal'));
define('SITE_URL', env('SITE_URL', 'http://localhost:8080'));
define('SITE_TIMEZONE', env('SITE_TIMEZONE', 'UTC'));

// -- Paths --
define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('EMAILS_PATH', BASE_PATH . '/emails');

// -- SQLite --
define('DB_PATH', DATA_PATH . '/portal.db');

// -- R2 / S3 --
define('R2_ACCOUNT_ID', env('R2_ACCOUNT_ID'));
define('R2_ACCESS_KEY', env('R2_ACCESS_KEY'));
define('R2_SECRET_KEY', env('R2_SECRET_KEY'));
define('R2_BUCKET', env('R2_BUCKET'));
define('R2_ENDPOINT', 'https://' . R2_ACCOUNT_ID . '.r2.cloudflarestorage.com');
define('R2_REGION', 'auto');

// -- SMTP (PHPMailer) --
define('SMTP_HOST', env('SMTP_HOST', 'smtp.example.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_USER', env('SMTP_USER'));
define('SMTP_PASS', env('SMTP_PASS'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL'));
define('SMTP_FROM_NAME', SITE_TITLE);

// -- API Auth --
define('API_ADMIN_TOKEN', env('API_ADMIN_TOKEN'));

// -- Session --
define('SESSION_LIFETIME', 86400 * 30); // 30 days for remember_me
define('CSRF_TOKEN_LIFETIME', 3600);    // 1 hour

// -- Rate Limiting --
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900); // 15 minutes

// -- Presigned URL TTL --
define('VIDEO_URL_TTL', 1800);  // 30 minutes
define('UPLOAD_URL_TTL', 3600); // 1 hour

// -- Timezone --
date_default_timezone_set(SITE_TIMEZONE);
