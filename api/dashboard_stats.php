<?php
/**
 * GET /api/dashboard_stats.php
 * Returns live analytics counts for the admin dashboard.
 * Requires admin auth via X-Admin-Token header.
 */
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
requireAdmin();

$pdo = get_db_connection();

// Total & pending bookings
try {
    $totalBookings   = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $pendingBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
} catch (Exception $e) {
    $totalBookings = $pendingBookings = 0;
}

// Articles from JSON content store
$articlesRow      = $pdo->query("SELECT data FROM content WHERE content_key = 'knowledge_articles' LIMIT 1")->fetch();
$articles         = $articlesRow ? (json_decode($articlesRow['data'], true) ?: []) : [];
$publishedArticles = array_filter($articles, fn($a) => ($a['status'] ?? '') === 'published');
$totalArticles    = count($publishedArticles);

// Top 5 published articles by view_count
$publishedList = array_values($publishedArticles);
usort($publishedList, fn($a, $b) => ($b['view_count'] ?? 0) - ($a['view_count'] ?? 0));
$topArticles = array_map(fn($a) => [
    'title'      => $a['title'] ?? 'Untitled',
    'pillar'     => $a['pillar'] ?? '',
    'view_count' => (int)($a['view_count'] ?? 0),
], array_slice($publishedList, 0, 5));

// Pending community items
$diyaRow    = $pdo->query("SELECT data FROM content WHERE content_key = 'diyas' LIMIT 1")->fetch();
$diyas      = $diyaRow ? (json_decode($diyaRow['data'], true) ?: []) : [];
$pendingDiyas = count(array_filter($diyas, fn($d) => ($d['status'] ?? 'pending') === 'pending'));

$reviewsRow    = $pdo->query("SELECT data FROM content WHERE content_key = 'peer_recognitions' LIMIT 1")->fetch();
$reviews       = $reviewsRow ? (json_decode($reviewsRow['data'], true) ?: []) : [];
$pendingReviews = count(array_filter($reviews, fn($r) => ($r['status'] ?? 'pending') === 'pending'));

$memoriesRow    = $pdo->query("SELECT data FROM content WHERE content_key = 'memories' LIMIT 1")->fetch();
$memories       = $memoriesRow ? (json_decode($memoriesRow['data'], true) ?: []) : [];
$pendingMemories = count(array_filter($memories, fn($m) => ($m['status'] ?? 'pending') === 'pending'));

// Subscribers
try {
    $totalSubscribers   = (int)$pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
    $newSubscribersWeek = (int)$pdo->query("SELECT COUNT(*) FROM subscribers WHERE subscribed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} catch (Exception $e) {
    $totalSubscribers = $newSubscribersWeek = 0;
}

respond([
    'success' => true,
    'data'    => [
        'bookings'         => $totalBookings,
        'pending_bookings' => $pendingBookings,
        'articles'         => $totalArticles,
        'pending_reviews'  => $pendingReviews,
        'pending_diyas'    => $pendingDiyas,
        'pending_memories' => $pendingMemories,
        'top_articles'     => $topArticles,
        'subscribers'      => $totalSubscribers,
        'new_subscribers'  => $newSubscribersWeek,
    ],
    'error' => null,
]);
