<?php
/**
 * R2 showcase helpers.
 *
 * Legacy filename kept for compatibility with existing includes.
 */

/**
 * Canonical showcase metadata for known videos.
 *
 * @return array<int, array<string, mixed>>
 */
function getShowcaseCatalogBlueprint(): array
{
    static $items = null;
    if ($items !== null) {
        return $items;
    }

    $items = [
        [
            'slug' => 'blue_zones',
            'title' => 'The Blue Zones',
            'description' => 'A short breakdown of habits from Blue Zones communities linked to longevity.',
            'category' => 'Health',
            'duration' => 117,
            'aspect_ratio' => '9:16',
            'sort_order' => -100,
            'match_tokens' => [
                'heygen_blue_zones_full_20260226_092506_iphone.mp4',
                'blue_zones',
                'blue-zones',
                'blue zones',
            ],
        ],
        [
            'slug' => 'daily_stoic',
            'title' => 'The Daily Stoic: 366 Meditations on Wisdom, Perseverance, and the Art of Living',
            'description' => 'A quick stoic reset for focus, composure, and better day-to-day decisions.',
            'category' => 'Mindset',
            'duration' => 102,
            'aspect_ratio' => '9:16',
            'sort_order' => -99,
            'match_tokens' => [
                'heygen_daily_stoic_full_20260227_153453_fit_scene_caption_captioned.mp4',
                'daily_stoic',
                'daily-stoic',
                'daily stoic',
            ],
        ],
        [
            'slug' => 'dubai',
            'title' => 'Dubai - The Epicenter of Modern Innovation',
            'description' => 'A fast-paced Dubai cut made for short-form vertical viewing.',
            'category' => 'Travel',
            'duration' => 95,
            'aspect_ratio' => '9:16',
            'sort_order' => -98,
            'match_tokens' => [
                'heygen_dubai_full_20260226_145723_captioned_tiktok_soft_smaller.mp4',
                'dubai',
            ],
        ],
        [
            'slug' => 'obesity_code',
            'title' => 'The Obesity Code: Unlocking the Secrets of Weight Loss',
            'description' => 'A concise explainer that separates common obesity myths from practical facts.',
            'category' => 'Health',
            'duration' => 130,
            'aspect_ratio' => '9:16',
            'sort_order' => -97,
            'match_tokens' => [
                'obesity_alexis_captioned.mp4',
                'obesity_alexis',
                'obesity',
            ],
        ],
        [
            'slug' => 'rich_dad',
            'title' => 'Rich Dad, Poor Dad',
            'description' => 'A compact summary of the money mindset lessons from Rich Dad, Poor Dad.',
            'category' => 'Finance',
            'duration' => 125,
            'aspect_ratio' => '9:16',
            'sort_order' => -96,
            'match_tokens' => [
                'rich_dad_final_20260302_captioned.mp4',
                'rich_dad',
                'rich-dad',
                'rich dad',
            ],
        ],
    ];

    return $items;
}

/**
 * Insert missing showcase videos into DB by discovering keys from R2 bucket.
 */
function syncShowcaseVideosFromR2(PDO $db, R2Client $r2): void
{
    $keys = $r2->listObjects('', 2000);
    if (empty($keys)) {
        return;
    }

    $existingKeys = $db->query("SELECT r2_key FROM videos")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $existingSet = array_fill_keys(array_map('strval', $existingKeys), true);

    $insert = $db->prepare("
        INSERT INTO videos (title, description, r2_key, thumbnail, category, is_free, sort_order, duration)
        VALUES (:title, :description, :r2_key, :thumbnail, :category, :is_free, :sort_order, :duration)
    ");

    foreach ($keys as $keyRaw) {
        $key = (string) $keyRaw;
        if (!isVideoObjectKey($key) || isset($existingSet[$key])) {
            continue;
        }
        if (isTestVideoValue($key)) {
            continue;
        }

        $item = findShowcaseBlueprintForKey($key);
        $title = $item['title'] ?? prettifyR2ObjectTitle($key);
        $description = $item['description'] ?? '';
        $category = $item['category'] ?? 'Showcase';
        $sortOrder = (int) ($item['sort_order'] ?? 100);
        $duration = (int) ($item['duration'] ?? 0);

        if (isTestVideoValue((string) $title) || isTestVideoValue((string) $description)) {
            continue;
        }

        $insert->execute([
            ':title' => (string) $title,
            ':description' => (string) $description,
            ':r2_key' => $key,
            ':thumbnail' => '',
            ':category' => (string) $category,
            ':is_free' => 1,
            ':sort_order' => $sortOrder,
            ':duration' => $duration,
        ]);

        $existingSet[$key] = true;
    }
}

/**
 * Merge showcase metadata into a video row based on r2_key.
 *
 * @param array<string, mixed> $video
 * @return array<string, mixed>
 */
function applyShowcaseMetadataToVideo(array $video): array
{
    $item = findShowcaseBlueprintForKey((string) ($video['r2_key'] ?? ''));
    if ($item === null) {
        return $video;
    }

    $video['title'] = (string) ($item['title'] ?? $video['title'] ?? '');
    if (empty($video['description'])) {
        $video['description'] = (string) ($item['description'] ?? '');
    }
    if (empty($video['category'])) {
        $video['category'] = (string) ($item['category'] ?? '');
    }
    if ((int) ($video['duration'] ?? 0) <= 0) {
        $video['duration'] = (int) ($item['duration'] ?? 0);
    }
    if ((int) ($video['sort_order'] ?? 0) >= 0) {
        $video['sort_order'] = (int) ($item['sort_order'] ?? 0);
    }
    $video['aspect_ratio'] = (string) ($item['aspect_ratio'] ?? '');

    return $video;
}

/**
 * Resolve showcase metadata item by R2 key.
 *
 * @return array<string, mixed>|null
 */
function findShowcaseBlueprintForKey(string $r2Key): ?array
{
    if ($r2Key === '') {
        return null;
    }

    foreach (getShowcaseCatalogBlueprint() as $item) {
        if (showcaseKeyMatchesTokens($r2Key, (array) ($item['match_tokens'] ?? []))) {
            return $item;
        }
    }

    return null;
}

/**
 * Find best matching key from list of bucket keys.
 *
 * @param string[] $keys
 * @param string[] $tokens
 */
function findBestR2KeyForShowcase(array $keys, array $tokens): ?string
{
    $cleanTokens = array_values(array_filter(array_map(
        fn($t) => strtolower(trim((string) $t)),
        $tokens
    )));
    if (empty($cleanTokens)) {
        return null;
    }

    foreach ($keys as $key) {
        $base = strtolower(basename((string) $key));
        foreach ($cleanTokens as $token) {
            if ($base === $token) {
                return (string) $key;
            }
        }
    }

    foreach ($keys as $key) {
        if (showcaseKeyMatchesTokens((string) $key, $cleanTokens)) {
            return (string) $key;
        }
    }

    return null;
}

/**
 * Determine whether an object key is a supported video file.
 */
function isVideoObjectKey(string $key): bool
{
    $path = strtolower(trim($key));
    if ($path === '') {
        return false;
    }

    return (bool) preg_match('/\.(mp4|m4v|mov|webm)$/i', $path);
}

/**
 * Convert an R2 object key into human-readable title.
 */
function prettifyR2ObjectTitle(string $key): string
{
    $base = pathinfo(basename($key), PATHINFO_FILENAME);
    $title = str_replace(['_', '-'], ' ', $base);
    $title = preg_replace('/\d{8}_\d{6}/', ' ', $title) ?? $title;
    $title = preg_replace('/\s+/', ' ', trim($title)) ?? $title;

    return $title !== '' ? ucwords(strtolower($title)) : 'Untitled Video';
}

/**
 * Check if R2 key matches any configured token.
 *
 * @param string[] $tokens
 */
function showcaseKeyMatchesTokens(string $r2Key, array $tokens): bool
{
    $key = strtolower($r2Key);
    $base = strtolower(basename($r2Key));

    foreach ($tokens as $tokenRaw) {
        $token = strtolower(trim((string) $tokenRaw));
        if ($token === '') {
            continue;
        }
        if ($base === $token || str_contains($base, $token) || str_contains($key, $token)) {
            return true;
        }
    }

    return false;
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
        (string) ($video['r2_key'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        if (isTestVideoValue($candidate)) {
            return true;
        }
    }

    return false;
}
