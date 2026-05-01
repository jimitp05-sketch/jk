-- AI Apollo — Database Migration Script
-- Run this ONCE on your MySQL database via phpMyAdmin or Hostinger DB tools
-- Date: 2026-04-12

-- 0. Create Bookings Table (if not exists)
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time VARCHAR(20) NOT NULL,
    reason TEXT,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Add missing indexes for booking queries
CREATE INDEX IF NOT EXISTS idx_booking_date_status ON bookings(booking_date, status);
CREATE INDEX IF NOT EXISTS idx_email ON bookings(email);

-- 2. Prevent double-bookings (CRITICAL)
-- Note: IGNORE error if already exists
ALTER TABLE bookings ADD UNIQUE KEY IF NOT EXISTS uk_slot (booking_date, booking_time);

-- 3. Email queue (async email delivery via cron)
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(200) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    headers TEXT DEFAULT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Auth sessions (replaces data/auth_tokens.json)
CREATE TABLE IF NOT EXISTS auth_sessions (
    token CHAR(64) NOT NULL PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Login attempts / brute-force tracking (replaces data/login_attempts.json)
CREATE TABLE IF NOT EXISTS login_attempts (
    ip VARCHAR(45) NOT NULL PRIMARY KEY,
    count INT NOT NULL DEFAULT 0,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CSRF tokens (replaces data/csrf_tokens.json)
CREATE TABLE IF NOT EXISTS csrf_tokens (
    token CHAR(64) NOT NULL PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Rate limits (replaces data/rate_limits.json)
CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    prefix_ip VARCHAR(200) NOT NULL,
    hit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_prefix_ip_hit (prefix_ip, hit_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Password reset tokens (forgot password flow)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_hash CHAR(64) NOT NULL,
    admin_email VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_token_hash (token_hash),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. CMS content table (migrate from JSON files)
CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(50) NOT NULL,
    content_key VARCHAR(100) NOT NULL,
    data JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_type_key (content_type, content_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
