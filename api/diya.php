<?php
/**
 * api/diya.php — MIGRATED TO RELATIONAL TABLES
 *
 * PUBLIC  GET              → returns approved diyas
 * PUBLIC  POST action=light → light a new diya
 * ADMIN   POST action=approve/reject/delete → manage diyas
 * PUBLIC  GET  ?action=count → total approved count
 * PUBLIC  GET  ?action=recent → last 10 diyas
 *
 * Database: Uses 'diyas' table (relational) + 'content' table for quotes (JSON)
 * Response contract is identical to the JSON-blob version — no frontend changes needed.
 */

require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// ── Auto-create table on first request ──────────────────────────────────
function ensureDiyasTable(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    $pdo = get_db_connection();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS diyas (
            id VARCHAR(30) NOT NULL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            prayer VARCHAR(500) DEFAULT '',
            lit_by VARCHAR(100) DEFAULT '',
            lit_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status ENUM('approved','pending','rejected') DEFAULT 'approved',
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status),
            INDEX idx_lit_at (lit_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function diyaToArray(array $row, bool $includeIpHash = false): array {
    $d = [
        'id' => $row['id'],
        'name' => $row['name'],
        'prayer' => $row['prayer'] ?? '',
        'lit_by' => $row['lit_by'] ?? '',
        'lit_at' => $row['lit_at'],
        'status' => $row['status'],
    ];
    if ($includeIpHash) $d['ip_hash'] = $row['ip_hash'] ?? '';
    return $d;
}

// ── Quotes stay as JSON (low volume, no moderation needed) ──────────────
function readQuotes(): array {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diya_quotes' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? (json_decode($row['data'], true) ?: []) : [];
}

function writeQuotes(array $quotes): bool {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES ('community', 'diya_quotes', ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ");
    return $stmt->execute([json_encode($quotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

// ══════════════════════════════════════════════════════════════════════════
// GET
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ensureDiyasTable();
    $pdo = get_db_connection();
    $action = $_GET['action'] ?? '';

    if ($action === 'count') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM diyas WHERE status = 'approved'");
        respond(['success' => true, 'count' => (int)$stmt->fetchColumn()]);
    }

    if ($action === 'recent') {
        $stmt = $pdo->query("SELECT * FROM diyas WHERE status = 'approved' ORDER BY lit_at DESC LIMIT 10");
        $rows = $stmt->fetchAll();
        respond(['success' => true, 'data' => array_map(fn($r) => diyaToArray($r), $rows)]);
    }

    if ($action === 'admin') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        $stmt = $pdo->query("SELECT * FROM diyas ORDER BY lit_at DESC");
        $rows = $stmt->fetchAll();
        respond(['success' => true, 'data' => array_map(fn($r) => diyaToArray($r, true), $rows)]);
    }

    if ($action === 'get_quotes') {
        $quotes = readQuotes();
        $active = array_values(array_filter($quotes, fn($q) => ($q['status'] ?? '') === 'active'));
        respond(['success' => true, 'data' => $active]);
    }

    // Default: approved diyas with pagination
    $perPage = min(50, max(10, (int)($_GET['per_page'] ?? 20)));
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $offset  = ($page - 1) * $perPage;

    $countStmt = $pdo->query("SELECT COUNT(*) FROM diyas WHERE status = 'approved'");
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM diyas WHERE status = 'approved' ORDER BY lit_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $rows = $stmt->fetchAll();

    respond([
        'success'     => true,
        'data'        => array_map(fn($r) => diyaToArray($r), $rows),
        'count'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => (int)ceil($total / $perPage),
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// POST
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensureDiyasTable();
    $pdo = get_db_connection();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // ── PUBLIC: Light a Diya ──────────────────────────────────────────────
    if ($action === 'light') {
        if (!checkRateLimit($clientIP, 5, 300, 'diya')) {
            respond(['success' => false, 'error' => 'You can light up to 5 diyas every 5 minutes. Please wait.'], 429);
        }

        $name = clean($input['name'] ?? '', 100);
        $prayer = clean($input['prayer'] ?? '', 500);
        $litBy = clean($input['lit_by'] ?? '', 100);

        if (empty($name)) {
            respond(['success' => false, 'error' => 'Please tell us who this diya is for.'], 400);
        }

        $id = 'diya_' . bin2hex(random_bytes(8));
        $ipHash = hash('sha256', $clientIP . date('Y-m'));

        $stmt = $pdo->prepare("INSERT INTO diyas (id, name, prayer, lit_by, lit_at, status, ip_hash) VALUES (?, ?, ?, ?, NOW(), 'approved', ?)");
        $stmt->execute([$id, $name, $prayer, $litBy, $ipHash]);

        $countStmt = $pdo->query("SELECT COUNT(*) FROM diyas WHERE status = 'approved'");

        respond([
            'success' => true,
            'data' => ['id' => $id, 'name' => $name, 'prayer' => $prayer, 'lit_by' => $litBy, 'lit_at' => date('Y-m-d H:i:s'), 'status' => 'approved'],
            'count' => (int)$countStmt->fetchColumn()
        ]);
    }

    // ── ADMIN: Approve / Reject / Delete ──────────────────────────────────
    if (in_array($action, ['approve', 'reject', 'delete'])) {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $targetId = $input['id'] ?? '';
        if (empty($targetId)) {
            respond(['success' => false, 'error' => 'Missing diya ID'], 400);
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM diyas WHERE id = ?");
            $stmt->execute([$targetId]);
        } else {
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt = $pdo->prepare("UPDATE diyas SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $targetId]);
        }

        if ($stmt->rowCount() === 0) {
            respond(['success' => false, 'error' => 'Diya not found'], 404);
        }

        respond(['success' => true, 'action' => $action, 'id' => $targetId]);
    }

    // ── ADMIN: Bulk update (for import / migration) ──────────────────────
    if ($action === 'bulk_update') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        $items = $input['items'] ?? [];
        if (!is_array($items)) {
            respond(['success' => false, 'error' => 'items must be an array'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO diyas (id, name, prayer, lit_by, lit_at, status, ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), prayer=VALUES(prayer), lit_by=VALUES(lit_by), status=VALUES(status)");
        foreach ($items as $d) {
            $stmt->execute([
                $d['id'] ?? 'diya_' . bin2hex(random_bytes(8)),
                $d['name'] ?? '',
                $d['prayer'] ?? '',
                $d['lit_by'] ?? '',
                $d['lit_at'] ?? date('Y-m-d H:i:s'),
                $d['status'] ?? 'approved',
                $d['ip_hash'] ?? ''
            ]);
        }
        respond(['success' => true, 'count' => count($items)]);
    }

    // ── ADMIN: Quote management (stays JSON — low volume) ────────────────
    if ($action === 'add_quote') {
        if (!isAdmin()) respond(['success' => false, 'error' => 'Unauthorized'], 401);
        $text = clean($input['text'] ?? '', 500);
        $author = clean($input['author'] ?? '', 100);
        if (empty($text)) respond(['success' => false, 'error' => 'Quote text is required'], 400);

        $quotes = readQuotes();
        $newQuote = ['id' => 'quote_' . bin2hex(random_bytes(8)), 'text' => $text, 'author' => $author, 'status' => 'active'];
        $quotes[] = $newQuote;
        writeQuotes($quotes);
        respond(['success' => true, 'data' => $newQuote]);
    }

    if ($action === 'edit_quote') {
        if (!isAdmin()) respond(['success' => false, 'error' => 'Unauthorized'], 401);
        $targetId = $input['id'] ?? '';
        if (empty($targetId)) respond(['success' => false, 'error' => 'Missing quote ID'], 400);
        $text = clean($input['text'] ?? '', 500);
        $author = clean($input['author'] ?? '', 100);
        $quotes = readQuotes();
        $found = false;
        $updatedQuote = null;
        foreach ($quotes as &$quote) {
            if (($quote['id'] ?? '') === $targetId) {
                $found = true;
                $quote['text'] = $text;
                $quote['author'] = $author;
                $updatedQuote = $quote;
                break;
            }
        }
        unset($quote);
        if (!$found) respond(['success' => false, 'error' => 'Quote not found'], 404);
        writeQuotes($quotes);
        respond(['success' => true, 'data' => $updatedQuote]);
    }

    if ($action === 'delete_quote') {
        if (!isAdmin()) respond(['success' => false, 'error' => 'Unauthorized'], 401);
        $targetId = $input['id'] ?? '';
        if (empty($targetId)) respond(['success' => false, 'error' => 'Missing quote ID'], 400);
        $quotes = readQuotes();
        $found = false;
        foreach ($quotes as $i => $quote) {
            if (($quote['id'] ?? '') === $targetId) { $found = true; array_splice($quotes, $i, 1); break; }
        }
        if (!$found) respond(['success' => false, 'error' => 'Quote not found'], 404);
        writeQuotes($quotes);
        respond(['success' => true, 'id' => $targetId]);
    }

    respond(['success' => false, 'error' => 'Invalid action'], 400);
}

respond(['success' => false, 'error' => 'Method not allowed'], 405);
