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
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/r2.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/local_videos.php';
require_once __DIR__ . '/../includes/story_worlds.php';

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

// Resolve the language before anything is written to the response: an explicit
// ?lang= sets the cookie and redirects, and every string below goes through the
// catalogue it selects. Deliberately after the API block, which is language
// neutral and must never be answered with a redirect.
initLocale();

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
 * Stream a curated local story with byte-range support for the HTML5 player.
 */
function streamLocalStoryVideo(string $filePath): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo t('error.local_media');
        exit;
    }

    $size = (int) filesize($filePath);
    $start = 0;
    $end = max(0, $size - 1);
    $status = 200;

    $range = (string) ($_SERVER['HTTP_RANGE'] ?? '');
    if ($range !== '' && preg_match('/bytes=(\d*)-(\d*)/i', $range, $matches)) {
        if ($matches[1] !== '') {
            $start = max(0, (int) $matches[1]);
        }
        if ($matches[2] !== '') {
            $end = min($end, (int) $matches[2]);
        }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            exit;
        }
        $status = 206;
    }

    $length = $end - $start + 1;
    http_response_code($status);
    header('Content-Type: video/mp4');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $length);
    if ($status === 206) {
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }
    header('Cache-Control: private, max-age=3600');
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');

    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        exit;
    }

    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        http_response_code(500);
        exit;
    }

    fseek($handle, $start);
    $remaining = $length;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(1024 * 1024, $remaining));
        if ($chunk === false) {
            break;
        }
        echo $chunk;
        $remaining -= strlen($chunk);
        if (connection_aborted()) {
            break;
        }
        flush();
    }
    fclose($handle);
    exit;
}

/** Default catalog page size for "Load more" */
const CATALOG_PAGE_SIZE = 24;

/**
 * Build catalog videos from DB + R2 metadata, with optional pagination.
 *
 * @return array{videos: array, total: int, page: int, perPage: int, hasMore: bool}
 */
function buildCatalogVideos(PDO $db, R2Client $r2, int $page = 1, int $perPage = CATALOG_PAGE_SIZE): array
{
    try {
        syncShowcaseVideosFromR2($db, $r2);
    } catch (\Throwable $e) {
        // Keep catalog available even if listing bucket fails.
    }

    $dbVideos = $db->query("SELECT * FROM videos ORDER BY sort_order ASC, created_at DESC")->fetchAll();
    $dbVideos = array_values(array_filter($dbVideos, fn(array $video): bool => !isTestVideoRecord($video)));

    foreach ($dbVideos as &$video) {
        $video = applyShowcaseMetadataToVideo($video);
        $video['watch_url'] = '/watch/' . $video['id'];
        $video['source'] = 'r2';
        if (!empty($video['r2_key']) && (int) ($video['is_free'] ?? 0) === 1) {
            $video['preview_url'] = $r2->getPresignedUrl((string) $video['r2_key'], VIDEO_URL_TTL);
        }
        // Thumbnail: if it's an R2 key (not a full URL), resolve to presigned URL so img can load it
        if (!empty($video['thumbnail'])) {
            $thumb = (string) $video['thumbnail'];
            if (strpos($thumb, 'http://') !== 0 && strpos($thumb, 'https://') !== 0) {
                $video['thumbnail'] = $r2->getPresignedUrl($thumb, VIDEO_URL_TTL);
            }
        }
    }
    unset($video);

    usort($dbVideos, function (array $a, array $b): int {
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

    $total = count($dbVideos);
    $perPage = max(1, min(50, $perPage));
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;
    $videos = array_slice($dbVideos, $offset, $perPage);

    return [
        'videos'  => $videos,
        'total'   => $total,
        'page'    => $page,
        'perPage' => $perPage,
        'hasMore' => $total > $offset + count($videos),
    ];
}

switch ($path) {

    case '/favicon.ico':
        $faviconPath = __DIR__ . '/favicon.ico';
        if (!is_file($faviconPath)) {
            http_response_code(204);
            exit;
        }
        header('Content-Type: image/x-icon');
        header('Content-Length: ' . filesize($faviconPath));
        header('Cache-Control: public, max-age=300, must-revalidate');
        readfile($faviconPath);
        exit;

    // ── Home / Catalog ───────────────────────────────────────
    case '/':
        render('catalog', [
            'worlds' => getStoryWorlds(),
            'episodes' => getOasisEpisodes(),
            'seasons' => getOasisSeasons(),
            'featuredWorlds' => [
                ['world' => getStoryWorlds()['city-on-wheels'], 'episodes' => getCityEpisodes()],
                ['world' => getStoryWorlds()['push-questions'], 'episodes' => getPushEpisodes()],
                ['world' => getStoryWorlds()['star-workshop'], 'episodes' => getMiraEpisodes()],
                ['world' => getStoryWorlds()['robot-family'], 'episodes' => getRobotEpisodes()],
            ],
            'pageTitle' => t('page.home'),
            'bodyClass' => 'page-home',
        ]);
        break;

    case '/characters':
        render('characters', [
            'characters' => getCharacterCollection(),
            'pageTitle' => t('page.characters'),
            'bodyClass' => 'page-characters',
        ]);
        break;

    // ── Story World ──────────────────────────────────────────
    case (preg_match('#^/world/([a-z-]+)/map$#', $path, $m) ? $path : null):
        $worlds = getStoryWorlds();
        $world = $worlds[$m[1]] ?? null;
        $episodes = $world === null ? [] : getStoryWorldEpisodes((string) $world['slug']);
        if ($world === null || empty($episodes)) {
            http_response_code(404);
            render('catalog', [
                'worlds' => $worlds,
                'episodes' => getOasisEpisodes(),
                'seasons' => getOasisSeasons(),
                'featuredWorlds' => [
                    ['world' => $worlds['city-on-wheels'], 'episodes' => getCityEpisodes()],
                    ['world' => $worlds['push-questions'], 'episodes' => getPushEpisodes()],
                    ['world' => $worlds['star-workshop'], 'episodes' => getMiraEpisodes()],
                    ['world' => $worlds['robot-family'], 'episodes' => getRobotEpisodes()],
                ],
                'pageTitle' => t('page.map_not_found'),
                'bodyClass' => 'page-home',
                'error' => t('error.map_unavailable'),
            ]);
            break;
        }

        render('series-map', [
            'world' => $world,
            'episodes' => $episodes,
            'seasons' => getStoryWorldSeasons((string) $world['slug']),
            'pageTitle' => t('page.series_map', ['title' => $world['title']]),
            'bodyClass' => 'page-series-map page-world--' . $world['slug'],
        ]);
        break;

    case (preg_match('#^/world/([a-z-]+)$#', $path, $m) ? $path : null):
        $worlds = getStoryWorlds();
        $world = $worlds[$m[1]] ?? null;
        if ($world === null) {
            http_response_code(404);
            render('catalog', [
                'worlds' => $worlds,
                'episodes' => getOasisEpisodes(),
                'characters' => getCharacterCollection(),
                'pageTitle' => t('page.world_not_found'),
                'bodyClass' => 'page-home',
                'error' => t('error.world_missing'),
            ]);
            break;
        }

        render('world', [
            'world' => $world,
            'episodes' => getStoryWorldEpisodes((string) $world['slug']),
            'seasons' => getStoryWorldSeasons((string) $world['slug']),
            'characters' => getCharacterCollection(),
            'pageTitle' => $world['title'],
            'bodyClass' => 'page-world page-world--' . $world['slug'],
        ]);
        break;

    // ── Local Story Player ───────────────────────────────────
    case (preg_match('#^/story/([a-z-]+)$#', $path, $m) ? $path : null):
        $episode = findStoryEpisode($m[1]);
        if ($episode === null) {
            http_response_code(404);
            render('world', [
                'world' => getStoryWorlds()['oasis'],
                'episodes' => getOasisEpisodes(),
                'characters' => getCharacterCollection(),
                'pageTitle' => t('page.story_not_found'),
                'bodyClass' => 'page-world page-world--oasis',
                'error' => t('error.story_missing'),
            ]);
            break;
        }

        $allEpisodes = getStoryWorldEpisodes((string) $episode['world_slug']);
        $index = array_search($episode['slug'], array_column($allEpisodes, 'slug'), true);
        $index = $index === false ? 0 : (int) $index;
        $previousEpisode = $index > 0 ? $allEpisodes[$index - 1] : null;
        $nextEpisode = isset($allEpisodes[$index + 1]) ? $allEpisodes[$index + 1] : null;
        render('watch', [
            'video' => $episode,
            'videoUrl' => $episode['media_url'],
            'posterUrl' => $episode['poster'],
            'previousEpisode' => $previousEpisode,
            'nextEpisode' => $nextEpisode,
            'storyEpisode' => true,
            'pageTitle' => $episode['title'],
            'bodyClass' => 'page-watch page-watch--story',
        ]);
        break;

    // ── Byte-range media for curated local stories ───────────
    case (preg_match('#^/media/story/([a-z-]+)$#', $path, $m) ? $path : null):
        $episode = findStoryEpisode($m[1]);
        if ($episode === null) {
            http_response_code(404);
            exit;
        }
        $localStoryFile = getStoryEpisodeFile($episode);
        if (is_file($localStoryFile) && is_readable($localStoryFile)) {
            streamLocalStoryVideo($localStoryFile);
        }

        $r2Key = getStoryEpisodeR2Key($episode);
        if ($r2Key === '' || R2_ACCOUNT_ID === '' || R2_ACCESS_KEY === '' || R2_SECRET_KEY === '' || R2_BUCKET === '') {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo t('error.stream_media');
            exit;
        }

        header('Cache-Control: private, no-store');
        header('Location: ' . getR2()->getPresignedUrl($r2Key, VIDEO_URL_TTL), true, 302);
        exit;

    // ── Catalog "Load more" (returns JSON: html, hasMore, nextPage) ─
    case '/catalog-more':
        $page = max(1, (int) ($_GET['page'] ?? 2));
        $perPage = max(1, min(50, (int) ($_GET['per_page'] ?? CATALOG_PAGE_SIZE)));
        $db = getDB();
        $r2 = getR2();
        $catalog = buildCatalogVideos($db, $r2, $page, $perPage);
        $videos = $catalog['videos'];
        ob_start();
        require TEMPLATES_PATH . '/partials/catalog_cards.php';
        $html = ob_get_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'html'     => $html,
            'hasMore'  => $catalog['hasMore'],
            'nextPage' => $page + 1,
            'total'    => $catalog['total'],
        ]);
        exit;

    // ── Login ────────────────────────────────────────────────
    case '/login':
        if (getCurrentUser()) {
            header('Location: /');
            exit;
        }
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', t('flash.invalid_form'));
                header('Location: /login');
                exit;
            }
            $user = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '', !empty($_POST['remember']));
            if ($user) {
                header('Location: /');
                exit;
            }
            setFlash('error', t('flash.invalid_credentials'));
            header('Location: /login');
            exit;
        }
        render('login', ['pageTitle' => t('page.login')]);
        break;

    // ── Register ─────────────────────────────────────────────
    case '/register':
        if (getCurrentUser()) {
            header('Location: /');
            exit;
        }
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', t('flash.invalid_form'));
                header('Location: /register');
                exit;
            }
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (strlen($password) < 6) {
                setFlash('error', t('flash.password_too_short'));
                header('Location: /register');
                exit;
            }
            if ($password !== $passwordConfirm) {
                setFlash('error', t('flash.passwords_mismatch'));
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
                setFlash('success', t('flash.register_success'));
                header('Location: /login');
                exit;
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE')) {
                    setFlash('error', t('flash.email_taken'));
                } else {
                    setFlash('error', t('flash.register_failed'));
                }
                header('Location: /register');
                exit;
            }
        }
        render('register', ['pageTitle' => t('page.register')]);
        break;

    // ── Email Verification ───────────────────────────────────
    case '/verify':
        $token = $_GET['token'] ?? '';
        if ($token && verifyEmail($token)) {
            setFlash('success', t('flash.email_verified'));
        } else {
            setFlash('error', t('flash.verify_invalid'));
        }
        header('Location: /login');
        exit;

    // ── Forgot Password ─────────────────────────────────────
    case '/forgot-password':
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', t('flash.invalid_form'));
                header('Location: /forgot-password');
                exit;
            }
            $email = $_POST['email'] ?? '';
            $token = createResetToken($email);
            if ($token) {
                sendResetEmail($email, $token);
            }
            // Always show success to prevent email enumeration
            setFlash('success', t('flash.reset_sent'));
            header('Location: /login');
            exit;
        }
        render('forgot-password', ['pageTitle' => t('page.forgot')]);
        break;

    // ── Reset Password ──────────────────────────────────────
    case '/reset-password':
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        if ($method === 'POST') {
            if (!validateCsrf()) {
                setFlash('error', t('flash.invalid_form'));
                header('Location: /reset-password?token=' . urlencode($token));
                exit;
            }
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 6) {
                setFlash('error', t('flash.password_too_short'));
                header('Location: /reset-password?token=' . urlencode($token));
                exit;
            }
            if (resetPassword($token, $password)) {
                setFlash('success', t('flash.password_updated'));
                header('Location: /login');
            } else {
                setFlash('error', t('flash.reset_invalid'));
                header('Location: /forgot-password');
            }
            exit;
        }
        render('reset-password', ['pageTitle' => t('page.reset'), 'token' => $token]);
        break;

    // ── Watch Video ──────────────────────────────────────────
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
                'worlds' => getStoryWorlds(),
                'episodes' => getOasisEpisodes(),
                'characters' => getCharacterCollection(),
                'pageTitle' => t('page.not_found'),
                'bodyClass' => 'page-home',
                'error' => t('error.video_missing'),
            ]);
            break;
        }

        $video = applyShowcaseMetadataToVideo($video);

        // Free videos are accessible without login.
        // Premium videos require authentication + active subscription.
        if (!$video['is_free']) {
            if (!$user) {
                setFlash('error', t('flash.login_for_premium'));
                header('Location: /login');
                exit;
            }
            if (!hasActiveSubscription($user)) {
                setFlash('error', t('flash.subscription_required'));
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

        $posterUrl = null;
        if (!empty($video['thumbnail'])) {
            $thumb = (string) $video['thumbnail'];
            $posterUrl = (strpos($thumb, 'http://') === 0 || strpos($thumb, 'https://') === 0)
                ? $thumb
                : $r2->getPresignedUrl($thumb, VIDEO_URL_TTL);
        }
        render('watch', ['video' => $video, 'videoUrl' => $videoUrl, 'posterUrl' => $posterUrl, 'pageTitle' => $video['title']]);
        break;

    // ── One-time: set thumbnail keys in DB (R2 key = thumbnails/{id}.jpg) ─
    case '/update-thumbnails-db':
        $token = $_GET['token'] ?? '';
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $fromLocalhost = in_array($remote, ['127.0.0.1', '::1'], true);
        $updateToken = (string) (defined('UPDATE_THUMBNAILS_TOKEN') ? UPDATE_THUMBNAILS_TOKEN : '');
        $apiToken = (string) (defined('API_ADMIN_TOKEN') ? API_ADMIN_TOKEN : '');
        $tokenOk = $token !== '' && ($updateToken !== '' && hash_equals($updateToken, $token)
            || $apiToken !== '' && hash_equals($apiToken, $token));
        if (!$fromLocalhost && !$tokenOk) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Forbidden. Either:\n1) Open from server: curl http://127.0.0.1/update-thumbnails-db\n2) Add to .env: UPDATE_THUMBNAILS_TOKEN=your_secret and open ?token=your_secret";
            exit;
        }
        $db = getDB();
        $stmt = $db->query("SELECT id FROM videos");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $update = $db->prepare("UPDATE videos SET thumbnail = :thumb WHERE id = :id");
        $count = 0;
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $update->execute([':thumb' => 'thumbnails/' . $id . '.jpg', ':id' => $id]);
            $count += $update->rowCount();
        }
        header('Content-Type: text/plain; charset=utf-8');
        echo "OK. Updated thumbnail key for {$count} video(s). Thumbnails in R2: thumbnails/{id}.jpg\n";
        exit;

    // ── Logout ───────────────────────────────────────────────
    case '/logout':
        logoutUser();
        header('Location: /login');
        exit;

    // ── 404 ──────────────────────────────────────────────────
    default:
        http_response_code(404);
        render('catalog', [
            'worlds' => getStoryWorlds(),
            'episodes' => getOasisEpisodes(),
            'characters' => getCharacterCollection(),
            'pageTitle' => t('page.404'),
            'bodyClass' => 'page-home',
            'error' => t('error.page_missing'),
        ]);
}
