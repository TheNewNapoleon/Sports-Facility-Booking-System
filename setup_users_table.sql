-- SQL Script to Create Users Table for XAMPP
-- Run this in phpMyAdmin or MySQL command line

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') DEFAULT 'user',
  `status` ENUM('active','pending','blacklisted') DEFAULT 'active',
  `living_place` VARCHAR(255) DEFAULT '',
  `phone_number` VARCHAR(20) DEFAULT '',
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `avatar_path` VARCHAR(255) DEFAULT 'images/avatar/default.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert a default admin user (password: admin123)
INSERT INTO `users` (`user_id`, `name`, `email`, `role`, `status`, `password`) 
VALUES ('admin', 'Administrator', 'admin@example.com', 'admin', 'active', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE `user_id`=`user_id`;

-- Verify table was created
SELECT 'Users table created successfully!' AS message;

-- Show table structure
DESCRIBE users;