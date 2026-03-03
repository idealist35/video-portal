<?php
/**
 * Users API
 * 
 * GET    /api/users           — list all users
 * GET    /api/users/{id}      — get single user
 * POST   /api/users           — create user (with optional subscription)
 * PUT    /api/users/{id}      — update user (extend subscription, etc.)
 * DELETE /api/users/{id}      — delete user
 */

$db = getDB();

$parts = explode('/', trim($apiPath, '/'));
$id = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null;

// Fields safe to return (no password hash)
$safeFields = 'id, email, is_verified, subscription_until, created_at';

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT $safeFields FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            // Add subscription status
            $user['is_subscribed'] = !empty($user['subscription_until']) && $user['subscription_until'] >= date('Y-m-d H:i:s');
            echo json_encode($user);
        } else {
            // List with optional search
            $search = $_GET['search'] ?? '';
            if ($search) {
                $stmt = $db->prepare("SELECT $safeFields FROM users WHERE email LIKE :search ORDER BY created_at DESC");
                $stmt->execute([':search' => '%' . $search . '%']);
            } else {
                $stmt = $db->query("SELECT $safeFields FROM users ORDER BY created_at DESC");
            }
            $users = $stmt->fetchAll();
            // Add subscription status
            foreach ($users as &$u) {
                $u['is_subscribed'] = !empty($u['subscription_until']) && $u['subscription_until'] >= date('Y-m-d H:i:s');
            }
            echo json_encode($users);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        if (empty($input['email']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'email and password are required']);
            break;
        }

        try {
            $hash = password_hash($input['password'], PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                INSERT INTO users (email, password_hash, is_verified, subscription_until)
                VALUES (:email, :hash, :verified, :sub)
            ");
            $stmt->execute([
                ':email'    => strtolower(trim($input['email'])),
                ':hash'     => $hash,
                ':verified' => (int) ($input['is_verified'] ?? 1),
                ':sub'      => $input['subscription_until'] ?? null,
            ]);

            $newId = (int) $db->lastInsertId();
            http_response_code(201);
            echo json_encode(['id' => $newId, 'message' => 'User created']);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create user']);
            }
        }
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $allowed = ['email', 'is_verified', 'subscription_until'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $sets[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }

        // Password update (separate handling for hashing)
        if (!empty($input['password'])) {
            $sets[] = "password_hash = :hash";
            $params[':hash'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        if (empty($sets)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            break;
        }

        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['message' => 'User updated', 'affected' => $stmt->rowCount()]);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            break;
        }
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['message' => 'User deleted', 'affected' => $stmt->rowCount()]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
