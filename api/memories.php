<?php
/**
 * api/memories.php
 *
 * Manages 3 content types for the Memories page:
 * 1. healing_stories  — family ICU journey stories (public submit → admin approve)
 * 2. gratitude_notes   — short thank-you notes (public submit → admin approve)
 * 3. memory_photos     — photo uploads (public submit → admin approve)
 *
 * PUBLIC  GET  ?type=healing_stories|gratitude_notes|memory_photos → approved items
 * PUBLIC  POST action=submit → submit new story/note/photo (pending approval)
 * ADMIN   GET  ?type=...&action=admin → all items including pending
 * ADMIN   POST action=approve/reject/delete → manage items
 * ADMIN   POST action=save → directly create/edit items (admin-created content)
 *
 * Database: Uses 'content' table with content_key = 'healing_stories' | 'gratitude_notes' | 'memory_photos'
 */

require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$VALID_TYPES = ['healing_stories', 'gratitude_notes', 'memory_photos'];

// ── READ / WRITE ─────────────────────────────────────────────────────────
function readItems(string $type): array {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = ? LIMIT 1");
    $stmt->execute([$type]);
    $row = $stmt->fetch();
    return $row ? (json_decode($row['data'], true) ?: []) : [];
}

function writeItems(string $type, array $items): bool {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES ('community', ?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ");
    return $stmt->execute([$type, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

// ══════════════════════════════════════════════════════════════════════════
// GET
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? '';
    $action = $_GET['action'] ?? '';

    // Get all types summary (for admin dashboard)
    if ($action === 'summary') {
        $summary = [];
        foreach ($VALID_TYPES as $t) {
            $items = readItems($t);
            $summary[$t] = [
                'total' => count($items),
                'pending' => count(array_filter($items, fn($i) => ($i['status'] ?? '') === 'pending')),
                'approved' => count(array_filter($items, fn($i) => ($i['status'] ?? '') === 'approved')),
            ];
        }
        respond(['success' => true, 'data' => $summary]);
    }

    if (!in_array($type, $VALID_TYPES, true)) {
        respond(['success' => false, 'error' => 'Invalid type. Valid: ' . implode(', ', $VALID_TYPES)], 400);
    }

    $items = readItems($type);

    // Admin view: return all
    if ($action === 'admin') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        respond(['success' => true, 'data' => $items]);
    }

    // Public view: only approved, strip ip_hash
    $approved = array_values(array_filter($items, fn($i) => ($i['status'] ?? '') === 'approved'));
    $approved = array_map(fn($i) => array_diff_key($i, ['ip_hash' => 1]), $approved);
    respond(['success' => true, 'data' => $approved, 'count' => count($approved)]);
}

// ══════════════════════════════════════════════════════════════════════════
// POST
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $type = $input['type'] ?? '';
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // ── PUBLIC: Submit a story/note/photo ──────────────────────────────────
    if ($action === 'submit') {
        if (!in_array($type, $VALID_TYPES, true)) {
            respond(['success' => false, 'error' => 'Invalid type'], 400);
        }

        if (!checkRateLimit($clientIP, 5, 3600, 'memories')) {
            respond(['success' => false, 'error' => 'Too many submissions. Please try again later.'], 429);
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmtLock = $pdo->prepare("SELECT data FROM content WHERE content_key = ? LIMIT 1 FOR UPDATE");
        $stmtLock->execute([$type]);
        $rowLock = $stmtLock->fetch();
        $items = $rowLock ? (json_decode($rowLock['data'], true) ?: []) : [];

        $newItem = [
            'id' => $type . '_' . bin2hex(random_bytes(8)),
            'status' => 'pending',
            'submitted_at' => date('Y-m-d H:i:s'),
            'ip_hash' => hash('sha256', $clientIP . date('Y-m'))
        ];

        // Type-specific fields
        if ($type === 'healing_stories') {
            $newItem['patient_name'] = clean($input['patient_name'] ?? '', 100);
            $newItem['family_name'] = clean($input['family_name'] ?? '', 100);
            $newItem['relationship'] = clean($input['relationship'] ?? '', 50);
            $newItem['duration'] = clean($input['duration'] ?? '', 50);
            $newItem['tag'] = clean($input['tag'] ?? '', 50); // "ECMO Survivor", "Sepsis Recovery", etc.
            $newItem['title'] = clean($input['title'] ?? '', 200);
            $newItem['story'] = clean($input['story'] ?? '', 5000);
            $newItem['quote'] = clean($input['quote'] ?? '', 500); // One-line highlight quote

            if (empty($newItem['story'])) {
                respond(['success' => false, 'error' => 'Please share your story.'], 400);
            }
        }

        if ($type === 'gratitude_notes') {
            $newItem['name'] = clean($input['name'] ?? '', 100);
            $newItem['note'] = clean($input['note'] ?? '', 1000);
            $newItem['relationship'] = clean($input['relationship'] ?? '', 50);

            if (empty($newItem['note'])) {
                respond(['success' => false, 'error' => 'Please write your note.'], 400);
            }
        }

        if ($type === 'memory_photos') {
            $newItem['caption'] = clean($input['caption'] ?? '', 300);
            $newItem['label'] = clean($input['label'] ?? '', 50); // Team, Clinical, Celebration, etc.
            $newItem['uploaded_by'] = clean($input['uploaded_by'] ?? '', 100);
            $newItem['photo_data'] = $input['photo_data'] ?? ''; // Base64 image data

            if (empty($newItem['photo_data'])) {
                respond(['success' => false, 'error' => 'Please upload a photo.'], 400);
            }

            // Validate base64 image (basic check)
            if (!preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $newItem['photo_data'])) {
                respond(['success' => false, 'error' => 'Invalid image format. Use JPG, PNG, or WebP.'], 400);
            }

            // Limit actual decoded image size to 5MB
            $b64Data = substr($newItem['photo_data'], strpos($newItem['photo_data'], ',') + 1);
            $decodedSize = (int)(strlen($b64Data) * 3 / 4);
            if ($decodedSize > 5 * 1024 * 1024) {
                respond(['success' => false, 'error' => 'Image too large. Maximum 5MB.'], 400);
            }
        }

        array_unshift($items, $newItem);
        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', ?, ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([$type, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();

        respond(['success' => true, 'message' => 'Submitted successfully! It will appear after admin review.']);
    }

    // ── ADMIN: Approve / Reject / Delete ──────────────────────────────────
    if (in_array($action, ['approve', 'reject', 'delete'])) {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        if (!in_array($type, $VALID_TYPES, true)) {
            respond(['success' => false, 'error' => 'Invalid type'], 400);
        }

        $targetId = $input['id'] ?? '';
        if (empty($targetId)) {
            respond(['success' => false, 'error' => 'Missing item ID'], 400);
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmtLock = $pdo->prepare("SELECT data FROM content WHERE content_key = ? LIMIT 1 FOR UPDATE");
        $stmtLock->execute([$type]);
        $rowLock = $stmtLock->fetch();
        $items = $rowLock ? (json_decode($rowLock['data'], true) ?: []) : [];
        $found = false;

        foreach ($items as $i => &$item) {
            if (($item['id'] ?? '') === $targetId) {
                $found = true;
                if ($action === 'delete') {
                    array_splice($items, $i, 1);
                } else {
                    $item['status'] = ($action === 'approve') ? 'approved' : 'rejected';
                }
                break;
            }
        }
        unset($item);

        if (!$found) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Item not found'], 404);
        }

        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', ?, ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([$type, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();
        respond(['success' => true, 'action' => $action, 'id' => $targetId]);
    }

    // ── ADMIN: Save (create or edit) ──────────────────────────────────────
    if ($action === 'save') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        if (!in_array($type, $VALID_TYPES, true)) {
            respond(['success' => false, 'error' => 'Invalid type'], 400);
        }

        $itemData = $input['item'] ?? [];
        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmtLock = $pdo->prepare("SELECT data FROM content WHERE content_key = ? LIMIT 1 FOR UPDATE");
        $stmtLock->execute([$type]);
        $rowLock = $stmtLock->fetch();
        $items = $rowLock ? (json_decode($rowLock['data'], true) ?: []) : [];

        // If item has an ID, update existing; otherwise create new
        $editId = $itemData['id'] ?? '';
        if ($editId) {
            foreach ($items as &$item) {
                if (($item['id'] ?? '') === $editId) {
                    $item = array_merge($item, $itemData);
                    break;
                }
            }
            unset($item);
        } else {
            $itemData['id'] = $type . '_' . bin2hex(random_bytes(8));
            $itemData['status'] = 'approved'; // Admin-created = auto-approved
            $itemData['submitted_at'] = date('Y-m-d H:i:s');
            array_unshift($items, $itemData);
        }

        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', ?, ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([$type, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();
        respond(['success' => true, 'id' => $itemData['id']]);
    }

    // ── ADMIN: Bulk update ────────────────────────────────────────────────
    if ($action === 'bulk_update') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        if (!in_array($type, $VALID_TYPES, true)) {
            respond(['success' => false, 'error' => 'Invalid type'], 400);
        }
        $newItems = $input['items'] ?? [];
        writeItems($type, $newItems);
        respond(['success' => true, 'count' => count($newItems)]);
    }

    respond(['success' => false, 'error' => 'Invalid action'], 400);
}

respond(['success' => false, 'error' => 'Method not allowed'], 405);
?>
