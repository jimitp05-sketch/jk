<?php
/**
 * api/memories.php — MIGRATED TO RELATIONAL TABLES
 *
 * Manages 3 content types: healing_stories, gratitude_notes, memory_photos
 * Each type has its own table. Response contract identical to JSON version.
 *
 * PUBLIC  GET  ?type=... → approved items
 * PUBLIC  POST action=submit → submit (pending approval)
 * ADMIN   GET  ?type=...&action=admin → all items
 * ADMIN   POST action=approve/reject/delete → moderate
 * ADMIN   POST action=save → create/edit directly
 */

require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$VALID_TYPES = ['healing_stories', 'gratitude_notes', 'memory_photos'];

function ensureMemoryTables(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    $pdo = get_db_connection();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS healing_stories (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            patient_name VARCHAR(100) DEFAULT '',
            family_name VARCHAR(100) DEFAULT '',
            relationship VARCHAR(50) DEFAULT '',
            duration VARCHAR(50) DEFAULT '',
            tag VARCHAR(50) DEFAULT '',
            title VARCHAR(200) DEFAULT '',
            story TEXT,
            quote VARCHAR(500) DEFAULT '',
            status ENUM('approved','pending','rejected') DEFAULT 'pending',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS gratitude_notes (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            name VARCHAR(100) DEFAULT '',
            note TEXT,
            relationship VARCHAR(50) DEFAULT '',
            status ENUM('approved','pending','rejected') DEFAULT 'pending',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS memory_photos (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            caption VARCHAR(300) DEFAULT '',
            label VARCHAR(50) DEFAULT '',
            uploaded_by VARCHAR(100) DEFAULT '',
            photo_data LONGTEXT,
            status ENUM('approved','pending','rejected') DEFAULT 'pending',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function rowToArray(array $row, bool $includeIpHash = false): array {
    $out = $row;
    if (!$includeIpHash) unset($out['ip_hash']);
    return $out;
}

// Column lists per type for INSERT
$COLUMNS = [
    'healing_stories' => ['id','patient_name','family_name','relationship','duration','tag','title','story','quote','status','submitted_at','ip_hash'],
    'gratitude_notes' => ['id','name','note','relationship','status','submitted_at','ip_hash'],
    'memory_photos'   => ['id','caption','label','uploaded_by','photo_data','status','submitted_at','ip_hash'],
];

// ══════════════════════════════════════════════════════════════════════════
// GET
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ensureMemoryTables();
    $pdo = get_db_connection();
    $type = $_GET['type'] ?? '';
    $action = $_GET['action'] ?? '';

    if ($action === 'summary') {
        $summary = [];
        foreach ($VALID_TYPES as $t) {
            $total = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $pending = (int)$pdo->query("SELECT COUNT(*) FROM `$t` WHERE status='pending'")->fetchColumn();
            $approved = (int)$pdo->query("SELECT COUNT(*) FROM `$t` WHERE status='approved'")->fetchColumn();
            $summary[$t] = ['total' => $total, 'pending' => $pending, 'approved' => $approved];
        }
        respond(['success' => true, 'data' => $summary]);
    }

    if (!in_array($type, $VALID_TYPES, true)) {
        respond(['success' => false, 'error' => 'Invalid type. Valid: ' . implode(', ', $VALID_TYPES)], 400);
    }

    if ($action === 'admin') {
        if (!isAdmin()) respond(['success' => false, 'error' => 'Unauthorized'], 401);
        $stmt = $pdo->query("SELECT * FROM `$type` ORDER BY submitted_at DESC");
        $rows = $stmt->fetchAll();
        respond(['success' => true, 'data' => array_map(fn($r) => rowToArray($r, true), $rows)]);
    }

    $stmt = $pdo->query("SELECT * FROM `$type` WHERE status='approved' ORDER BY submitted_at DESC");
    $rows = $stmt->fetchAll();
    $clean = array_map(fn($r) => rowToArray($r), $rows);
    respond(['success' => true, 'data' => $clean, 'count' => count($clean)]);
}

// ══════════════════════════════════════════════════════════════════════════
// POST
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensureMemoryTables();
    $pdo = get_db_connection();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $type = $input['type'] ?? '';
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // ── PUBLIC: Submit ────────────────────────────────────────────────────
    if ($action === 'submit') {
        if (!in_array($type, $VALID_TYPES, true)) respond(['success' => false, 'error' => 'Invalid type'], 400);

        if (!checkRateLimit($clientIP, 5, 3600, 'memories')) {
            respond(['success' => false, 'error' => 'Too many submissions. Please try again later.'], 429);
        }

        $id = $type . '_' . bin2hex(random_bytes(8));
        $ipHash = hash('sha256', $clientIP . date('Y-m'));
        $now = date('Y-m-d H:i:s');

        if ($type === 'healing_stories') {
            $story = clean($input['story'] ?? '', 5000);
            if (empty($story)) respond(['success' => false, 'error' => 'Please share your story.'], 400);
            $pdo->prepare("INSERT INTO healing_stories (id,patient_name,family_name,relationship,duration,tag,title,story,quote,status,submitted_at,ip_hash) VALUES (?,?,?,?,?,?,?,?,?,'pending',?,?)")
                ->execute([$id, clean($input['patient_name']??'',100), clean($input['family_name']??'',100), clean($input['relationship']??'',50), clean($input['duration']??'',50), clean($input['tag']??'',50), clean($input['title']??'',200), $story, clean($input['quote']??'',500), $now, $ipHash]);
        }

        if ($type === 'gratitude_notes') {
            $note = clean($input['note'] ?? '', 1000);
            if (empty($note)) respond(['success' => false, 'error' => 'Please write your note.'], 400);
            $pdo->prepare("INSERT INTO gratitude_notes (id,name,note,relationship,status,submitted_at,ip_hash) VALUES (?,?,?,?,'pending',?,?)")
                ->execute([$id, clean($input['name']??'',100), $note, clean($input['relationship']??'',50), $now, $ipHash]);
        }

        if ($type === 'memory_photos') {
            $photoData = $input['photo_data'] ?? '';
            if (empty($photoData)) respond(['success' => false, 'error' => 'Please upload a photo.'], 400);
            if (!preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $photoData)) {
                respond(['success' => false, 'error' => 'Invalid image format. Use JPG, PNG, or WebP.'], 400);
            }
            $b64Data = substr($photoData, strpos($photoData, ',') + 1);
            if ((int)(strlen($b64Data) * 3 / 4) > 5 * 1024 * 1024) {
                respond(['success' => false, 'error' => 'Image too large. Maximum 5MB.'], 400);
            }
            $pdo->prepare("INSERT INTO memory_photos (id,caption,label,uploaded_by,photo_data,status,submitted_at,ip_hash) VALUES (?,?,?,?,?,'pending',?,?)")
                ->execute([$id, clean($input['caption']??'',300), clean($input['label']??'',50), clean($input['uploaded_by']??'',100), $photoData, $now, $ipHash]);
        }

        respond(['success' => true, 'message' => 'Submitted successfully! It will appear after admin review.']);
    }

    // ── ADMIN: Approve / Reject / Delete ──────────────────────────────────
    if (in_array($action, ['approve', 'reject', 'delete'])) {
        if (!isAdmin()) respond(['success' => false, 'error' => 'Unauthorized'], 401);
        if (!in_array($type, $VALID_TYPES, true)) respond(['success' => false, 'error' => 'Invalid type'], 400);
        $targetId = $input['id'] ?? '';
        if (empty($targetId)) respond(['success' => false, 'error' => 'Missing item ID'], 400);

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM `$type` WHERE id = ?");
        } else {
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt = $pdo->prepare("UPDATE `$type` SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $targetId]);
            if ($stmt->rowCount() === 0) respond(['success' => false, 'error' => 'Item not found'], 404);
            respond(['success' => true, 'action' => $action, 'id' => $targetId]);
        }
        $stmt->execute([$targetId]);
        if ($stmt->rowCount() === 0) respond(['success' => false, 'error' => 'Item not found'], 404);
        respond(['success' => true, 'action' => $action, 'id' => $targetId]);
    }

    // ── ADMIN: Save (create or edit) ──────────────────────────────────────
    if ($action === 'save') {
        if (!isAdmin()) respond(['success' => false, 'error' => 'Unauthorized'], 401);
        if (!in_array($type, $VALID_TYPES, true)) respond(['success' => false, 'error' => 'Invalid type'], 400);

        $itemData = $input['item'] ?? [];
        $editId = $itemData['id'] ?? '';

        if ($editId) {
            // Update existing
            $sets = [];
            $vals = [];
            $cols = $COLUMNS[$type];
            foreach ($itemData as $k => $v) {
                if ($k === 'id' || !in_array($k, $cols, true)) continue;
                $sets[] = "`$k` = ?";
                $vals[] = $v;
            }
            if ($sets) {
                $vals[] = $editId;
                $pdo->prepare("UPDATE `$type` SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
            }
            respond(['success' => true, 'id' => $editId]);
        } else {
            // Create new
            $id = $type . '_' . bin2hex(random_bytes(8));
            $itemData['id'] = $id;
            $itemData['status'] = 'approved';
            $itemData['submitted_at'] = date('Y-m-d H:i:s');

            $cols = $COLUMNS[$type];
            $insertCols = [];
            $placeholders = [];
            $vals = [];
            foreach ($cols as $c) {
                $insertCols[] = "`$c`";
                $placeholders[] = '?';
                $vals[] = $itemData[$c] ?? '';
            }
            $pdo->prepare("INSERT INTO `$type` (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $placeholders) . ")")->execute($vals);
            respond(['success' => true, 'id' => $id]);
        }
    }

    // ── ADMIN: Bulk update (import/migration) ────────────────────────────
    if ($action === 'bulk_update') {
        if (!isAdmin()) respond(['success' => false, 'error' => 'Unauthorized'], 401);
        if (!in_array($type, $VALID_TYPES, true)) respond(['success' => false, 'error' => 'Invalid type'], 400);
        $newItems = $input['items'] ?? [];
        $cols = $COLUMNS[$type];
        $insertCols = array_map(fn($c) => "`$c`", $cols);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO `$type` (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $placeholders) . ") ON DUPLICATE KEY UPDATE status=VALUES(status)";
        $stmt = $pdo->prepare($sql);
        foreach ($newItems as $item) {
            $vals = [];
            foreach ($cols as $c) $vals[] = $item[$c] ?? '';
            $stmt->execute($vals);
        }
        respond(['success' => true, 'count' => count($newItems)]);
    }

    respond(['success' => false, 'error' => 'Invalid action'], 400);
}

respond(['success' => false, 'error' => 'Method not allowed'], 405);
