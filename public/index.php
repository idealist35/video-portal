<?php
/**
 * Single Entry Point / Router
 * 
 * All requests are routed through this file via nginx rewrite.
 * Handles both frontend pages and API endpoints.
 */

// PHP built-in server: serve static files directly
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/r2.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/local_videos.php';

// Parse request path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($requestUri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// ── API Routes ───────────────────────────────────────────────
if (str_starts_with($path, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');

    // API auth check (Bearer token)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    if ($token !== API_ADMIN_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $apiPath = substr($path, 4); // strip /api prefix
    switch (true) {
        case str_starts_with($apiPath, '/videos'):
            require __DIR__ . '/../api/videos.php';
            break;
        case str_starts_with($apiPath, '/users'):
            require __DIR__ . '/../api/users.php';
            break;
        case str_starts_with($apiPath, '/stats'):
            require __DIR__ . '/../api/stats.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
    }
    exit;
}

// ── Frontend Routes ──────────────────────────────────────────

// Helper: render template with layout
function render(string $template, array $data = []): void
{
    $data['user'] = getCurrentUser();
    $data['csrf'] = csrfField();
    $data['siteTitle'] = SITE_TITLE;
    $data['hasSubscription'] = hasActiveSubscription($data['user']);
    extract($data);
    $contentTemplate = TEMPLATES_PATH . '/' . $template . '.php';
    require TEMPLATES_PATH . '/layout.php';
}

// Helper: flash messages
function setFlash(string $type, string $message): void
{
    ensureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    ensureSession();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Merge database videos with local showcase files.
 *
 * @return array<int, array<string, mixed>>
 */
function buildCatalogVideos(PDO $db): array
{
    $dbVideos = $db->query("SELECT * FROM videos ORDER BY sort_order ASC, created_at DESC")->fetchAll();
    $dbVideos = array_values(array_filter($dbVideos, fn(array $video): bool => !isTestVideoRecord($video)));
    foreach ($dbVideos as &$video) {
        $video['watch_url'] = '/watch/' . $video['id'];
        $video['source'] = 'r2';
    }
    unset($video);

    $videos = array_merge(getLocalVideos(), $dbVideos);

    usort($videos, function (array $a, array $b): int {
        $sortA = (int) ($a['sort_order'] ?? 0);
        $sortB = (int) ($b['sort_order'] ?? 0);
        if ($sortA !== $sortB) {
            return $sortA <=> $sortB;
        }

        $createdA = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
        $createdB = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
        if ($createdA !== $createdB) {
            return $createdB <=> $createdA;
        }

        return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    });

    return $videos;
}

switch ($path) {

    // ── Home / Catalog ───────────────────────────────────────
    case '/':
        $db = getDB();
        $videos = buildCatalogVideos($db);
        render('catalog', ['videos' => $videos, 'pageTitle' => 'Catalog']);
        break;

    // ── Login ────────────────────────────────────────────────
    case '/login':
        if (getCurrentUser()) {
            header('Location: /');
            exit;
        }
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', 'Invalid form submission');
                header('Location: /login');
                exit;
            }
            $user = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '', !empty($_POST['remember']));
            if ($user) {
                header('Location: /');
                exit;
            }
            setFlash('error', 'Invalid email or password');
            header('Location: /login');
            exit;
        }
        render('login', ['pageTitle' => 'Login']);
        break;

    // ── Register ─────────────────────────────────────────────
    case '/register':
        if (getCurrentUser()) {
            header('Location: /');
            exit;
        }
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', 'Invalid form submission');
                header('Location: /register');
                exit;
            }
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (strlen($password) < 6) {
                setFlash('error', 'Password must be at least 6 characters');
                header('Location: /register');
                exit;
            }
            if ($password !== $passwordConfirm) {
                setFlash('error', 'Passwords do not match');
                header('Location: /register');
                exit;
            }
            try {
                $userId = registerUser($email, $password);
                // Get verify token for welcome email
                $db = getDB();
                $stmt = $db->prepare("SELECT verify_token FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $row = $stmt->fetch();
                if ($row) {
                    sendWelcomeEmail($email, $row['verify_token']);
                }
                setFlash('success', 'Registration successful! Check your email to verify.');
                header('Location: /login');
                exit;
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE')) {
                    setFlash('error', 'This email is already registered');
                } else {
                    setFlash('error', 'Registration failed');
                }
                header('Location: /register');
                exit;
            }
        }
        render('register', ['pageTitle' => 'Register']);
        break;

    // ── Email Verification ───────────────────────────────────
    case '/verify':
        $token = $_GET['token'] ?? '';
        if ($token && verifyEmail($token)) {
            setFlash('success', 'Email verified! You can now log in.');
        } else {
            setFlash('error', 'Invalid or expired verification link');
        }
        header('Location: /login');
        exit;

    // ── Forgot Password ─────────────────────────────────────
    case '/forgot-password':
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', 'Invalid form submission');
                header('Location: /forgot-password');
                exit;
            }
            $email = $_POST['email'] ?? '';
            $token = createResetToken($email);
            if ($token) {
                sendResetEmail($email, $token);
            }
            // Always show success to prevent email enumeration
            setFlash('success', 'If the email exists, a reset link has been sent.');
            header('Location: /login');
            exit;
        }
        render('forgot-password', ['pageTitle' => 'Forgot Password']);
        break;

    // ── Reset Password ──────────────────────────────────────
    case '/reset-password':
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', 'Invalid form submission');
                header('Location: /reset-password?token=' . urlencode($token));
                exit;
            }
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 6) {
                setFlash('error', 'Password must be at least 6 characters');
                header('Location: /reset-password?token=' . urlencode($token));
                exit;
            }
            if (resetPassword($token, $password)) {
                setFlash('success', 'Password updated! You can now log in.');
                header('Location: /login');
            } else {
                setFlash('error', 'Invalid or expired reset link');
                header('Location: /forgot-password');
            }
            exit;
        }
        render('reset-password', ['pageTitle' => 'Reset Password', 'token' => $token]);
        break;

    // ── Watch Video ──────────────────────────────────────────
    case (preg_match('#^/stream/local/(.+)$#', $path, $mLocalStream) ? $path : null):
        $video = findLocalVideo($mLocalStream[1]);
        if (!$video) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Video not found';
            break;
        }

        streamLocalVideo(getLocalVideoPath($video));
        exit;

    case (preg_match('#^/watch/local/(.+)$#', $path, $mLocalWatch) ? $path : null):
        $video = findLocalVideo($mLocalWatch[1]);
        if (!$video) {
            http_response_code(404);
            $db = getDB();
            render('catalog', [
                'videos' => buildCatalogVideos($db),
                'pageTitle' => 'Not Found',
                'error' => 'Video not found',
            ]);
            break;
        }

        $videoUrl = '/stream/local/' . rawurlencode((string) $video['local_filename']);
        render('watch', ['video' => $video, 'videoUrl' => $videoUrl, 'pageTitle' => $video['title']]);
        break;

    case (preg_match('#^/watch/(\d+)$#', $path, $m) ? $path : null):
        $user = getCurrentUser();
        $videoId = (int) $m[1];
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id");
        $stmt->execute([':id' => $videoId]);
        $video = $stmt->fetch();

        if (!$video) {
            http_response_code(404);
            render('catalog', [
                'videos' => buildCatalogVideos($db),
                'pageTitle' => 'Not Found',
                'error' => 'Video not found',
            ]);
            break;
        }

        // Free videos are accessible without login.
        // Premium videos require authentication + active subscription.
        if (!$video['is_free']) {
            if (!$user) {
                setFlash('error', 'Please log in to watch premium videos');
                header('Location: /login');
                exit;
            }
            if (!hasActiveSubscription($user)) {
                setFlash('error', 'You need an active subscription to watch this video');
                header('Location: /');
                exit;
            }
        }

        // Generate presigned URL for streaming
        $r2 = getR2();
        $videoUrl = $r2->getPresignedUrl($video['r2_key'], VIDEO_URL_TTL);

        // Record view (user_id can be NULL for guests).
        $stmt = $db->prepare("INSERT INTO views (user_id, video_id) VALUES (:uid, :vid)");
        $stmt->execute([':uid' => $user['id'] ?? null, ':vid' => $videoId]);

        render('watch', ['video' => $video, 'videoUrl' => $videoUrl, 'pageTitle' => $video['title']]);
        break;

    // ── Logout ───────────────────────────────────────────────
    case '/logout':
        logoutUser();
        header('Location: /login');
        exit;

    // ── 404 ──────────────────────────────────────────────────
    default:
        http_response_code(404);
        render('catalog', ['videos' => [], 'pageTitle' => '404', 'error' => 'Page not found']);
}
