<?php
/**
 * Statistics API
 * 
 * GET /api/stats              — overall stats summary
 * GET /api/stats?period=7d    — stats for period (1d, 7d, 30d, all)
 * GET /api/stats/videos       — per-video view counts
 * GET /api/stats/users        — user activity
 */

$db = getDB();

$parts = explode('/', trim($apiPath, '/'));
$subAction = $parts[1] ?? null;
$period = $_GET['period'] ?? '7d';

// Calculate period filter
$periodMap = [
    '1d'  => "datetime('now', '-1 day')",
    '7d'  => "datetime('now', '-7 days')",
    '30d' => "datetime('now', '-30 days')",
    'all' => "'1970-01-01'",
];
$since = $periodMap[$period] ?? $periodMap['7d'];

switch ($subAction) {

    case 'videos':
        // Views per video in period
        $stmt = $db->prepare("
            SELECT v.id, v.title, v.category, COUNT(vw.id) as view_count
            FROM videos v
            LEFT JOIN views vw ON vw.video_id = v.id AND vw.watched_at >= $since
            GROUP BY v.id
            ORDER BY view_count DESC
        ");
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;

    case 'users':
        // Active users in period
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.subscription_until, COUNT(vw.id) as views_count,
                   MAX(vw.watched_at) as last_watched
            FROM users u
            LEFT JOIN views vw ON vw.user_id = u.id AND vw.watched_at >= $since
            GROUP BY u.id
            ORDER BY views_count DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll();
        foreach ($users as &$u) {
            $u['is_subscribed'] = !empty($u['subscription_until']) && $u['subscription_until'] >= date('Y-m-d H:i:s');
        }
        echo json_encode($users);
        break;

    default:
        // Overall summary
        $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeSubscribers = $db->query("SELECT COUNT(*) FROM users WHERE subscription_until >= datetime('now')")->fetchColumn();
        $totalVideos = $db->query("SELECT COUNT(*) FROM videos")->fetchColumn();

        $viewsInPeriod = $db->query("SELECT COUNT(*) FROM views WHERE watched_at >= $since")->fetchColumn();
        $uniqueViewers = $db->query("SELECT COUNT(DISTINCT user_id) FROM views WHERE watched_at >= $since")->fetchColumn();

        // Most popular video in period
        $topVideo = $db->query("
            SELECT v.title, COUNT(vw.id) as cnt
            FROM views vw
            JOIN videos v ON v.id = vw.video_id
            WHERE vw.watched_at >= $since
            GROUP BY vw.video_id
            ORDER BY cnt DESC
            LIMIT 1
        ")->fetch();

        echo json_encode([
            'period'             => $period,
            'total_users'        => (int) $totalUsers,
            'active_subscribers' => (int) $activeSubscribers,
            'total_videos'       => (int) $totalVideos,
            'views_in_period'    => (int) $viewsInPeriod,
            'unique_viewers'     => (int) $uniqueViewers,
            'top_video'          => $topVideo ?: null,
        ]);
}
