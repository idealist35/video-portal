<?php
/**
 * Videos API
 * 
 * GET    /api/videos          — list all videos
 * GET    /api/videos/{id}     — get single video
 * POST   /api/videos          — create video
 * PUT    /api/videos/{id}     — update video
 * DELETE /api/videos/{id}     — delete video
 * GET    /api/videos/{id}/url — get presigned download URL
 * POST   /api/videos/upload-url — get presigned upload URL
 */

$db = getDB();
$r2 = getR2();

// Extract ID from path: /api/videos/123 or /api/videos/123/url
$parts = explode('/', trim($apiPath, '/'));
$id = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null;
$subAction = $parts[2] ?? null;

switch ($method) {

    case 'GET':
        if ($id && $subAction === 'url') {
            // Get presigned download URL
            $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $video = $stmt->fetch();
            if (!$video) {
                http_response_code(404);
                echo json_encode(['error' => 'Video not found']);
                break;
            }
            echo json_encode([
                'url' => $r2->getPresignedUrl($video['r2_key'], VIDEO_URL_TTL),
                'expires_in' => VIDEO_URL_TTL,
            ]);
        } elseif ($id) {
            // Single video
            $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $video = $stmt->fetch();
            if (!$video) {
                http_response_code(404);
                echo json_encode(['error' => 'Video not found']);
                break;
            }
            echo json_encode($video);
        } else {
            // List all
            $videos = $db->query("SELECT * FROM videos ORDER BY sort_order ASC, created_at DESC")->fetchAll();
            echo json_encode($videos);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        // Special action: get upload URL for R2
        if (($parts[1] ?? '') === 'upload-url') {
            $key = $input['r2_key'] ?? '';
            if (!$key) {
                http_response_code(400);
                echo json_encode(['error' => 'r2_key is required']);
                break;
            }
            echo json_encode([
                'upload_url' => $r2->getUploadUrl($key, UPLOAD_URL_TTL),
                'expires_in' => UPLOAD_URL_TTL,
            ]);
            break;
        }

        // Create video
        if (empty($input['title']) || empty($input['r2_key'])) {
            http_response_code(400);
            echo json_encode(['error' => 'title and r2_key are required']);
            break;
        }

        $stmt = $db->prepare("
            INSERT INTO videos (title, description, r2_key, thumbnail, category, is_free, sort_order, duration)
            VALUES (:title, :desc, :r2_key, :thumb, :cat, :free, :sort, :dur)
        ");
        $stmt->execute([
            ':title'  => $input['title'],
            ':desc'   => $input['description'] ?? '',
            ':r2_key' => $input['r2_key'],
            ':thumb'  => $input['thumbnail'] ?? '',
            ':cat'    => $input['category'] ?? '',
            ':free'   => (int) ($input['is_free'] ?? 0),
            ':sort'   => (int) ($input['sort_order'] ?? 0),
            ':dur'    => (int) ($input['duration'] ?? 0),
        ]);

        $newId = (int) $db->lastInsertId();
        http_response_code(201);
        echo json_encode(['id' => $newId, 'message' => 'Video created']);
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Video ID required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        // Build dynamic update query from provided fields
        $allowed = ['title', 'description', 'r2_key', 'thumbnail', 'category', 'is_free', 'sort_order', 'duration'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $sets[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }

        if (empty($sets)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            break;
        }

        $sql = "UPDATE videos SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['message' => 'Video updated', 'affected' => $stmt->rowCount()]);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Video ID required']);
            break;
        }

        // Optionally delete from R2 too
        $stmt = $db->prepare("SELECT r2_key FROM videos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $video = $stmt->fetch();

        if ($video) {
            $r2->delete($video['r2_key']);
        }

        $stmt = $db->prepare("DELETE FROM videos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['message' => 'Video deleted', 'affected' => $stmt->rowCount()]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
