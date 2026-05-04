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
    /**
     * Sanitize input for database storage.
     * NOTE: Do NOT use htmlspecialchars here — that belongs at output/display time.
     * This function supports all Unicode: Hindi, Gujarati, Arabic, emoji, etc.
     */
    function clean(string $val, int $maxLen = 500): string {
        // Ensure valid UTF-8 — replace invalid sequences rather than erroring
        $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8');
        $val = trim($val);
        // Remove HTML/script tags only — preserve all Unicode characters
        $val = strip_tags($val);
        // Truncate by character count (mb_substr handles multi-byte correctly)
        if (mb_strlen($val, 'UTF-8') > $maxLen) {
            $val = mb_substr($val, 0, $maxLen, 'UTF-8');
        }
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
