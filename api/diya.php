<?php
/**
 * api/diya.php
 *
 * PUBLIC  GET              → returns approved diyas (with prayer messages)
 * PUBLIC  POST action=light → light a new diya (goes to pending/auto-approved based on settings)
 * ADMIN   POST action=approve/reject/delete → manage diyas
 * PUBLIC  GET  ?action=count → returns total count only
 * PUBLIC  GET  ?action=recent → returns last 10 diyas
 *
 * Database: Uses 'content' table with content_key = 'diyas'
 * Each diya: { id, name, prayer, lit_by, lit_at, status: approved|pending|rejected, ip_hash }
 */

require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// ── READ / WRITE DIYAS ──────────────────────────────────────────────────
function readDiyas(): array {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diyas' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? (json_decode($row['data'], true) ?: []) : [];
}

function writeDiyas(array $diyas): bool {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES ('community', 'diyas', ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ");
    return $stmt->execute([json_encode($diyas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

// ── READ / WRITE QUOTES ─────────────────────────────────────────────────
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
    $action = $_GET['action'] ?? '';
    $diyas = readDiyas();

    // Count only
    if ($action === 'count') {
        $approved = array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved');
        respond(['success' => true, 'count' => count($approved)]);
    }

    // Recent 10
    if ($action === 'recent') {
        $approved = array_values(array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved'));
        usort($approved, fn($a, $b) => strtotime($b['lit_at'] ?? '0') - strtotime($a['lit_at'] ?? '0'));
        $recent = array_slice($approved, 0, 10);
        // Strip ip_hash for public
        $recent = array_map(fn($d) => array_diff_key($d, ['ip_hash' => 1]), $recent);
        respond(['success' => true, 'data' => $recent]);
    }

    // Admin: all diyas (including pending)
    if ($action === 'admin') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        respond(['success' => true, 'data' => $diyas]);
    }

    // Public: all active quotes
    if ($action === 'get_quotes') {
        $quotes = readQuotes();
        $active = array_values(array_filter($quotes, fn($q) => ($q['status'] ?? '') === 'active'));
        respond(['success' => true, 'data' => $active]);
    }

    // Default: approved diyas (public) with pagination
    $approved = array_values(array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved'));
    $total    = count($approved);
    $perPage  = min(50, max(10, (int)($_GET['per_page'] ?? 20)));
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $offset   = ($page - 1) * $perPage;
    $paged    = array_slice($approved, $offset, $perPage);
    // Strip ip_hash
    $paged = array_map(fn($d) => array_diff_key($d, ['ip_hash' => 1]), $paged);
    respond([
        'success'     => true,
        'data'        => $paged,
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
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // ── PUBLIC: Light a Diya ──────────────────────────────────────────────
    if ($action === 'light') {
        // Rate limit: 5 diyas per 5 minutes per IP
        if (!checkRateLimit($clientIP, 5, 300, 'diya')) {
            respond(['success' => false, 'error' => 'You can light up to 5 diyas every 5 minutes. Please wait.'], 429);
        }

        $name = clean($input['name'] ?? '', 100);
        $prayer = clean($input['prayer'] ?? '', 500);
        $litBy = clean($input['lit_by'] ?? '', 100);

        if (empty($name)) {
            respond(['success' => false, 'error' => 'Please tell us who this diya is for.'], 400);
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diyas' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $row = $stmt->fetch();
        $diyas = $row ? (json_decode($row['data'], true) ?: []) : [];

        $newDiya = [
            'id' => 'diya_' . bin2hex(random_bytes(8)),
            'name' => $name,
            'prayer' => $prayer,
            'lit_by' => $litBy,
            'lit_at' => date('Y-m-d H:i:s'),
            'status' => 'approved', // Auto-approve diyas (they're prayers, not reviews)
            'ip_hash' => hash('sha256', $clientIP . date('Y-m'))
        ];

        array_unshift($diyas, $newDiya); // newest first
        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', 'diyas', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([json_encode($diyas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();

        // Return the diya (without ip_hash)
        $publicDiya = array_diff_key($newDiya, ['ip_hash' => 1]);
        respond([
            'success' => true,
            'data' => $publicDiya,
            'count' => count(array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved'))
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

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diyas' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $row = $stmt->fetch();
        $diyas = $row ? (json_decode($row['data'], true) ?: []) : [];
        $found = false;

        foreach ($diyas as $i => &$diya) {
            if (($diya['id'] ?? '') === $targetId) {
                $found = true;
                if ($action === 'delete') {
                    array_splice($diyas, $i, 1);
                } else {
                    $diya['status'] = ($action === 'approve') ? 'approved' : 'rejected';
                }
                break;
            }
        }
        unset($diya);

        if (!$found) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Diya not found'], 404);
        }

        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', 'diyas', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([json_encode($diyas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();
        respond(['success' => true, 'action' => $action, 'id' => $targetId]);
    }

    // ── ADMIN: Bulk update (for import) ───────────────────────────────────
    if ($action === 'bulk_update') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        $items = $input['items'] ?? [];
        if (!is_array($items)) {
            respond(['success' => false, 'error' => 'items must be an array'], 400);
        }
        writeDiyas($items);
        respond(['success' => true, 'count' => count($items)]);
    }

    // ── ADMIN: Add Quote ─────────────────────────────────────────────────
    if ($action === 'add_quote') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $text = clean($input['text'] ?? '', 500);
        $author = clean($input['author'] ?? '', 100);

        if (empty($text)) {
            respond(['success' => false, 'error' => 'Quote text is required'], 400);
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diya_quotes' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $row = $stmt->fetch();
        $quotes = $row ? (json_decode($row['data'], true) ?: []) : [];

        $newQuote = [
            'id' => 'quote_' . bin2hex(random_bytes(8)),
            'text' => $text,
            'author' => $author,
            'status' => 'active',
        ];

        $quotes[] = $newQuote;
        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', 'diya_quotes', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([json_encode($quotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();

        respond(['success' => true, 'data' => $newQuote]);
    }

    // ── ADMIN: Edit Quote ────────────────────────────────────────────────
    if ($action === 'edit_quote') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $targetId = $input['id'] ?? '';
        if (empty($targetId)) {
            respond(['success' => false, 'error' => 'Missing quote ID'], 400);
        }

        $text = clean($input['text'] ?? '', 500);
        $author = clean($input['author'] ?? '', 100);

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diya_quotes' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $row = $stmt->fetch();
        $quotes = $row ? (json_decode($row['data'], true) ?: []) : [];
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

        if (!$found) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Quote not found'], 404);
        }

        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', 'diya_quotes', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([json_encode($quotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();
        respond(['success' => true, 'data' => $updatedQuote]);
    }

    // ── ADMIN: Delete Quote ──────────────────────────────────────────────
    if ($action === 'delete_quote') {
        if (!isAdmin()) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $targetId = $input['id'] ?? '';
        if (empty($targetId)) {
            respond(['success' => false, 'error' => 'Missing quote ID'], 400);
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diya_quotes' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $row = $stmt->fetch();
        $quotes = $row ? (json_decode($row['data'], true) ?: []) : [];
        $found = false;

        foreach ($quotes as $i => $quote) {
            if (($quote['id'] ?? '') === $targetId) {
                $found = true;
                array_splice($quotes, $i, 1);
                break;
            }
        }

        if (!$found) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Quote not found'], 404);
        }

        $writeStmt = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('community', 'diya_quotes', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $writeStmt->execute([json_encode($quotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $pdo->commit();
        respond(['success' => true, 'id' => $targetId]);
    }

    respond(['success' => false, 'error' => 'Invalid action'], 400);
}

respond(['success' => false, 'error' => 'Method not allowed'], 405);
?>
