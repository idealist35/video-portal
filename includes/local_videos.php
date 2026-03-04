<?php
/**
 * Local videos catalog and streaming helpers.
 *
 * Allows showcasing videos placed in project root (BASE_PATH) without R2.
 */

/**
 * Build local showcase videos from files in project root.
 *
 * @return array<int, array<string, mixed>>
 */
function getLocalVideos(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $allowedExtensions = ['mp4', 'm4v', 'mov', 'webm'];

    $titleOverrides = [
        'heygen_blue_zones_full_20260226_092506_iphone.mp4' =>
            'The Blue Zones',
        'heygen_daily_stoic_full_20260227_153453_fit_scene_caption_captioned.mp4' =>
            'The Daily Stoic: 366 Meditations on Wisdom, Perseverance, and the Art of Living',
        'heygen_dubai_full_20260226_145723_captioned_tiktok_soft_smaller.mp4' =>
            'Dubai - The Epicenter of Modern Innovation',
        'obesity_alexis_captioned.mp4' =>
            'The Obesity Code: Unlocking the Secrets of Weight Loss',
        'rich_dad_final_20260302_captioned.mp4' =>
            'Rich Dad, Poor Dad',
    ];

    $descriptionOverrides = [
        'heygen_blue_zones_full_20260226_092506_iphone.mp4' =>
            'A short breakdown of habits from Blue Zones communities linked to longevity.',
        'heygen_daily_stoic_full_20260227_153453_fit_scene_caption_captioned.mp4' =>
            'A quick stoic reset for focus, composure, and better day-to-day decisions.',
        'heygen_dubai_full_20260226_145723_captioned_tiktok_soft_smaller.mp4' =>
            'A fast-paced Dubai cut made for short-form vertical viewing.',
        'obesity_alexis_captioned.mp4' =>
            'A concise explainer that separates common obesity myths from practical facts.',
        'rich_dad_final_20260302_captioned.mp4' =>
            'A compact summary of the money mindset lessons from Rich Dad, Poor Dad.',
    ];

    $categoryOverrides = [
        'heygen_blue_zones_full_20260226_092506_iphone.mp4' => 'Health',
        'heygen_daily_stoic_full_20260227_153453_fit_scene_caption_captioned.mp4' => 'Mindset',
        'heygen_dubai_full_20260226_145723_captioned_tiktok_soft_smaller.mp4' => 'Travel',
        'obesity_alexis_captioned.mp4' => 'Health',
        'rich_dad_final_20260302_captioned.mp4' => 'Finance',
    ];

    $durationOverrides = [
        'heygen_blue_zones_full_20260226_092506_iphone.mp4' => 117,
        'heygen_daily_stoic_full_20260227_153453_fit_scene_caption_captioned.mp4' => 102,
        'heygen_dubai_full_20260226_145723_captioned_tiktok_soft_smaller.mp4' => 95,
        'obesity_alexis_captioned.mp4' => 130,
        'rich_dad_final_20260302_captioned.mp4' => 125,
    ];

    $aspectRatioOverrides = [
        'heygen_blue_zones_full_20260226_092506_iphone.mp4' => '9:16',
        'heygen_daily_stoic_full_20260227_153453_fit_scene_caption_captioned.mp4' => '9:16',
        'heygen_dubai_full_20260226_145723_captioned_tiktok_soft_smaller.mp4' => '9:16',
        'obesity_alexis_captioned.mp4' => '9:16',
        'rich_dad_final_20260302_captioned.mp4' => '9:16',
    ];

    $videos = [];

    try {
        $iterator = new DirectoryIterator(BASE_PATH);
        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $filename = $item->getFilename();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }
            if (isTestVideoValue($filename)) {
                continue;
            }

            $title = $titleOverrides[$filename] ?? prettifyVideoTitle($filename);
            $description = $descriptionOverrides[$filename] ?? '';
            $category = $categoryOverrides[$filename] ?? 'Showcase';
            $duration = $durationOverrides[$filename] ?? 0;
            $aspectRatio = $aspectRatioOverrides[$filename] ?? '';
            $createdAt = gmdate('Y-m-d H:i:s', $item->getMTime());

            $videos[] = [
                'id' => 'local:' . $filename,
                'title' => $title,
                'description' => $description,
                'r2_key' => '',
                'thumbnail' => '',
                'category' => $category,
                'is_free' => 1,
                'sort_order' => -100,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'created_at' => $createdAt,
                'watch_url' => '/watch/local/' . rawurlencode($filename),
                'preview_url' => '/stream/local/' . rawurlencode($filename),
                'source' => 'local',
                'local_filename' => $filename,
            ];
        }
    } catch (\UnexpectedValueException $e) {
        // Directory could not be opened; keep local catalog empty.
    }

    usort($videos, function (array $a, array $b): int {
        $timeA = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
        $timeB = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
        return $timeB <=> $timeA;
    });

    $cache = $videos;
    return $cache;
}

/**
 * Resolve local video by filename.
 *
 * @return array<string, mixed>|null
 */
function findLocalVideo(string $filename): ?array
{
    $decoded = rawurldecode($filename);
    if ($decoded === '' || basename($decoded) !== $decoded) {
        return null;
    }

    foreach (getLocalVideos() as $video) {
        if (($video['local_filename'] ?? '') === $decoded) {
            return $video;
        }
    }

    return null;
}

/**
 * Get absolute path for a local video.
 */
function getLocalVideoPath(array $video): string
{
    return BASE_PATH . '/' . ($video['local_filename'] ?? '');
}

/**
 * Stream a local video with HTTP range support.
 */
function streamLocalVideo(string $filePath): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        echo 'Video file not found';
        return;
    }

    $size = filesize($filePath);
    if ($size === false || $size <= 0) {
        http_response_code(404);
        echo 'Video file not found';
        return;
    }

    $start = 0;
    $end = $size - 1;
    $length = $size;

    $rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';
    if ($rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
        if ($matches[1] === '' && $matches[2] === '') {
            http_response_code(416);
            header("Content-Range: bytes */{$size}");
            return;
        }

        if ($matches[1] === '') {
            $suffix = (int) $matches[2];
            $start = max(0, $size - $suffix);
        } else {
            $start = (int) $matches[1];
        }

        if ($matches[2] !== '') {
            $end = (int) $matches[2];
        }

        $end = min($end, $size - 1);
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header("Content-Range: bytes */{$size}");
            return;
        }

        $length = $end - $start + 1;
        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$size}");
    }

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
    ];
    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . $length);
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=3600');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        return;
    }

    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        http_response_code(500);
        echo 'Failed to open video stream';
        return;
    }

    set_time_limit(0);
    fseek($handle, $start);

    $bufferSize = 1024 * 1024;
    $remaining = $length;

    while ($remaining > 0 && !feof($handle)) {
        $chunkSize = (int) min($bufferSize, $remaining);
        $buffer = fread($handle, $chunkSize);
        if ($buffer === false || $buffer === '') {
            break;
        }

        echo $buffer;
        $remaining -= strlen($buffer);

        if (connection_aborted()) {
            break;
        }
        flush();
    }

    fclose($handle);
}

/**
 * Convert technical filename into a human-readable fallback title.
 */
function prettifyVideoTitle(string $filename): string
{
    $title = pathinfo($filename, PATHINFO_FILENAME);

    $title = preg_replace('/\d{8}_\d{6}/', ' ', $title) ?? $title;
    $title = preg_replace('/\b(heygen|full|final|captioned|caption|scene|fit|iphone)\b/i', ' ', $title) ?? $title;
    $title = str_replace(['_', '-'], ' ', $title);
    $title = preg_replace('/\s+/', ' ', trim($title)) ?? $title;

    return $title !== '' ? ucwords(strtolower($title)) : 'Untitled Video';
}

/**
 * Check whether a text indicates a test/demo placeholder video.
 */
function isTestVideoValue(string $value): bool
{
    $normalized = strtolower(trim($value));
    if ($normalized === '') {
        return false;
    }

    return (bool) preg_match('/(^|[\s._-])(test|testing|demo|sample|draft|tmp)([\s._-]|$)/i', $normalized);
}

/**
 * Check whether a video record should be treated as test content.
 *
 * @param array<string, mixed> $video
 */
function isTestVideoRecord(array $video): bool
{
    $candidates = [
        (string) ($video['title'] ?? ''),
        (string) ($video['description'] ?? ''),
        (string) ($video['category'] ?? ''),
        (string) ($video['local_filename'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        if (isTestVideoValue($candidate)) {
            return true;
        }
    }

    return false;
}
