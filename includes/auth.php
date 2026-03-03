<?php
/**
 * Authentication Module
 * 
 * Handles registration, login, logout, sessions, remember_me,
 * CSRF tokens, rate limiting, password reset.
 */

require_once __DIR__ . '/db.php';

// Start session if not already started
function ensureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ── Registration ─────────────────────────────────────────────

/**
 * Register a new user. Returns user id or throws on duplicate email.
 */
function registerUser(string $email, string $password): int
{
    $db = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $verifyToken = bin2hex(random_bytes(32));

    $stmt = $db->prepare("
        INSERT INTO users (email, password_hash, verify_token)
        VALUES (:email, :hash, :token)
    ");
    $stmt->execute([
        ':email' => strtolower(trim($email)),
        ':hash'  => $hash,
        ':token' => $verifyToken,
    ]);

    return (int) $db->lastInsertId();
}

/**
 * Verify email by token.
 */
function verifyEmail(string $token): bool
{
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE users SET is_verified = 1, verify_token = NULL
        WHERE verify_token = :token
    ");
    $stmt->execute([':token' => $token]);
    return $stmt->rowCount() > 0;
}

// ── Login / Logout ───────────────────────────────────────────

/**
 * Attempt login. Returns user array on success, null on failure.
 */
function loginUser(string $email, string $password, bool $remember = false): ?array
{
    $email = strtolower(trim($email));

    // Check rate limit
    if (isLoginLocked($email)) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordLoginAttempt($email);
        return null;
    }

    // Clear old login attempts on success
    clearLoginAttempts($email);

    // Set session
    ensureSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];

    // Remember me — persistent cookie token
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $stmt = $db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
        $stmt->execute([':token' => $token, ':id' => $user['id']]);
        setcookie('remember_token', $token, [
            'expires'  => time() + SESSION_LIFETIME,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
    }

    return $user;
}

/**
 * Logout current user.
 */
function logoutUser(): void
{
    ensureSession();

    if (isset($_SESSION['user_id'])) {
        // Clear remember token in DB
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
    }

    $_SESSION = [];
    session_destroy();

    // Delete remember cookie
    setcookie('remember_token', '', ['expires' => 1, 'path' => '/']);
}

// ── Current User ─────────────────────────────────────────────

/**
 * Get current authenticated user or null.
 * Checks session first, then remember_me cookie.
 */
function getCurrentUser(): ?array
{
    ensureSession();

    // Already in session
    if (!empty($_SESSION['user_id'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    // Try remember_me cookie
    if (!empty($_COOKIE['remember_token'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = :token");
        $stmt->execute([':token' => $_COOKIE['remember_token']]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            return $user;
        }
    }

    return null;
}

/**
 * Require authentication — redirect to login if not logged in.
 */
function requireAuth(): array
{
    $user = getCurrentUser();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    return $user;
}

/**
 * Check if user has active subscription.
 */
function hasActiveSubscription(?array $user): bool
{
    if (!$user) return false;
    if (empty($user['subscription_until'])) return false;
    return $user['subscription_until'] >= date('Y-m-d H:i:s');
}

// ── Password Reset ───────────────────────────────────────────

/**
 * Generate password reset token. Returns token or null if email not found.
 */
function createResetToken(string $email): ?string
{
    $db = getDB();
    $email = strtolower(trim($email));
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    if (!$stmt->fetch()) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $stmt = $db->prepare("
        UPDATE users SET reset_token = :token, reset_token_expires = :expires
        WHERE email = :email
    ");
    $stmt->execute([':token' => $token, ':expires' => $expires, ':email' => $email]);

    return $token;
}

/**
 * Reset password using token.
 */
function resetPassword(string $token, string $newPassword): bool
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id FROM users
        WHERE reset_token = :token AND reset_token_expires >= datetime('now')
    ");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) return false;

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare("
        UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expires = NULL
        WHERE id = :id
    ");
    $stmt->execute([':hash' => $hash, ':id' => $user['id']]);

    return true;
}

// ── CSRF Protection ──────────────────────────────────────────

/**
 * Generate CSRF token and store in session.
 */
function csrfToken(): string
{
    ensureSession();
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_time']) 
        || (time() - $_SESSION['csrf_time']) > CSRF_TOKEN_LIFETIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden CSRF input for forms.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate CSRF token from POST request.
 */
function validateCsrf(): bool
{
    ensureSession();
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── Rate Limiting ────────────────────────────────────────────

function recordLoginAttempt(string $email): void
{
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO login_attempts (email) VALUES (:email)");
    $stmt->execute([':email' => $email]);
}

function isLoginLocked(string $email): bool
{
    $db = getDB();
    $since = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_SECONDS);
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt FROM login_attempts
        WHERE email = :email AND attempted_at >= :since
    ");
    $stmt->execute([':email' => $email, ':since' => $since]);
    $row = $stmt->fetch();
    return ($row['cnt'] ?? 0) >= LOGIN_MAX_ATTEMPTS;
}

function clearLoginAttempts(string $email): void
{
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE email = :email");
    $stmt->execute([':email' => $email]);
}
