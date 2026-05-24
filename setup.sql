-- WIN Lost Property Management System database setup

DROP DATABASE IF EXISTS `win_lostproperty`;
CREATE DATABASE `win_lostproperty`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `win_lostproperty`;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `student_staff_id` VARCHAR(50) NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student','staff','admin') NOT NULL DEFAULT 'student',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_email` (`email`),
  UNIQUE KEY `idx_users_student_staff_id` (`student_staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lost_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `category` VARCHAR(100) NULL,
  `location_found` VARCHAR(150) NULL,
  `date_found` DATE NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `status` ENUM('unclaimed','claimed') NOT NULL DEFAULT 'unclaimed',
  `reported_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lost_items_reported_by` (`reported_by`),
  KEY `idx_lost_items_status` (`status`),
  CONSTRAINT `fk_lost_items_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `claims` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT UNSIGNED NOT NULL,
  `claimed_by` INT UNSIGNED NOT NULL,
  `claim_description` TEXT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_claims_item_id` (`item_id`),
  KEY `idx_claims_claimed_by` (`claimed_by`),
  KEY `idx_claims_status` (`status`),
  CONSTRAINT `fk_claims_item` FOREIGN KEY (`item_id`) REFERENCES `lost_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_claims_claimed_by` FOREIGN KEY (`claimed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`full_name`, `student_staff_id`, `email`, `phone`, `password`, `role`)
VALUES ('System Administrator', 'ADMIN001', 'admin@win.edu.au', NULL, SHA2('admin123', 256), 'admin');

INSERT INTO `users` (`id`, `full_name`, `student_staff_id`, `email`, `phone`, `password`, `role`)
VALUES
  (2, 'Jane Student', 'STU2026', 'jane.student@win.edu.au', '0400111222', SHA2('studentpass', 256), 'student'),
  (3, 'Mark Staff', 'STF1001', 'mark.staff@win.edu.au', '0400333444', SHA2('staffpass', 256), 'staff');

INSERT INTO `lost_items` (`id`, `title`, `description`, `category`, `location_found`, `date_found`, `image_path`, `status`, `reported_by`)
VALUES
  (1, 'Silver keycard', 'Student keycard found near library entrance', 'Electronics', 'Library Entrance', '2026-05-15', '/uploads/keycard1.jpg', 'unclaimed', 3),
  (2, 'Blue backpack', 'Backpack containing textbooks and water bottle', 'Accessories', 'Cafeteria', '2026-05-18', '/uploads/backpack1.jpg', 'claimed', 1);

INSERT INTO `claims` (`item_id`, `claimed_by`, `claim_description`, `status`)
VALUES
  (2, 2, 'I lost this blue backpack in the cafeteria this morning and can describe the contents.', 'approved'),
  (1, 2, 'I believe this silver keycard belongs to me; it has my name on the back.', 'pending');
