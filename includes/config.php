<?php
/**
 * Portal Configuration
 * 
 * Copy this file and fill in real credentials for production.
 */

// -- Site Settings --
define('SITE_TITLE', 'Anime Portal');
define('SITE_URL', 'https://portal.example.com');
define('SITE_TIMEZONE', 'UTC');

// -- Paths --
define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('EMAILS_PATH', BASE_PATH . '/emails');

// -- SQLite --
define('DB_PATH', DATA_PATH . '/portal.db');

// -- R2 / S3 --
define('R2_ACCOUNT_ID', 'YOUR_ACCOUNT_ID');
define('R2_ACCESS_KEY', 'YOUR_ACCESS_KEY');
define('R2_SECRET_KEY', 'YOUR_SECRET_KEY');
define('R2_BUCKET', 'YOUR_BUCKET');
define('R2_ENDPOINT', 'https://' . R2_ACCOUNT_ID . '.r2.cloudflarestorage.com');
define('R2_REGION', 'auto');

// -- SMTP (PHPMailer) --
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@example.com');
define('SMTP_PASS', 'YOUR_SMTP_PASSWORD');
define('SMTP_FROM_EMAIL', 'noreply@example.com');
define('SMTP_FROM_NAME', SITE_TITLE);

// -- API Auth --
// Static admin token for Cursor Skill API access
define('API_ADMIN_TOKEN', 'CHANGE_ME_TO_RANDOM_TOKEN');

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
