-- ============================================================
-- Wentworth Lost and Found Management System
-- Database: win_lostproperty
-- ============================================================

CREATE DATABASE IF NOT EXISTS win_lostproperty
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE win_lostproperty;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('student', 'staff', 'admin') DEFAULT 'student',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: items
-- ============================================================
CREATE TABLE IF NOT EXISTS items (
    item_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    type          ENUM('lost', 'found') NOT NULL,
    item_name     VARCHAR(150) NOT NULL,
    category      VARCHAR(100) NOT NULL,
    description   TEXT NOT NULL,
    location      VARCHAR(150) NOT NULL,
    date_reported DATE NOT NULL,
    status        ENUM('open', 'claimed', 'returned', 'disposed') DEFAULT 'open',
    image_path    VARCHAR(255),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: claims
-- ============================================================
CREATE TABLE IF NOT EXISTS claims (
    claim_id     INT AUTO_INCREMENT PRIMARY KEY,
    item_id      INT NOT NULL,
    claimant_id  INT NOT NULL,
    description  TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    claim_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (claimant_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    item_id         INT NOT NULL,
    message         TEXT NOT NULL,
    is_read         BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT ADMIN ACCOUNT
-- Email: admin@win.edu.au  |  Password: admin123
-- IMPORTANT: After importing this SQL, open your browser and
-- run http://localhost/lost-found-system/setup.php
-- to generate a proper password hash and create the admin user.
-- Then DELETE setup.php for security.
-- ============================================================
