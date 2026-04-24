<?php
/**
 * AI Apollo — Shared Utilities
 * Common functions used across multiple API endpoints.
 */

if (!function_exists('respond')) {
    function respond(array $payload, int $code = 200): void {
        http_response_code($code);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('clean')) {
    function clean(string $val, int $maxLen = 500): string {
        $val = trim($val);
        $val = strip_tags($val);
        $val = htmlspecialchars($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (mb_strlen($val) > $maxLen) $val = mb_substr($val, 0, $maxLen);
        return $val;
    }
}

if (!function_exists('setCORSHeaders')) {
    function setCORSHeaders(): void {
        $allowed_origins = [
            'https://foxwisdom.com',
            'https://www.foxwisdom.com',
            'https://drjaykothari.in',
            'https://www.drjaykothari.in',
        ];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowed_origins, true)) {
            header("Access-Control-Allow-Origin: $origin");
        } elseif (php_sapi_name() === 'cli' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token, X-CSRF-Token, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }
}
