-- AI Apollo — Database Migration Script
-- Run this ONCE on your MySQL database via phpMyAdmin or Hostinger DB tools
-- Date: 2026-04-12

-- 1. Add missing indexes for booking queries
CREATE INDEX IF NOT EXISTS idx_booking_date_status ON bookings(booking_date, status);
CREATE INDEX IF NOT EXISTS idx_email ON bookings(email);

-- 2. Prevent double-bookings (CRITICAL)
ALTER TABLE bookings ADD UNIQUE KEY uk_slot (booking_date, booking_time);

-- 3. Add audit trail column
ALTER TABLE bookings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 4. Add soft delete capability
ALTER TABLE bookings ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- 5. Email queue (async email delivery via cron)
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

-- 6. CMS content table (migrate from JSON files)
CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(50) NOT NULL,
    content_key VARCHAR(100) NOT NULL,
    data JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_type_key (content_type, content_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
