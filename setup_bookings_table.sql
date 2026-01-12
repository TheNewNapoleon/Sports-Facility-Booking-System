-- SQL Script to Create Bookings Table for XAMPP
-- Run this in phpMyAdmin or MySQL command line if the bookings table doesn't exist

-- Create bookings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(100) NOT NULL,
  `venue_id` VARCHAR(100) NOT NULL,
  `court_id` INT(11) NULL DEFAULT NULL,
  `booking_date` DATE NOT NULL,
  `booking_time` TIME NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'booked',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_venue_date` (`venue_id`, `booking_date`),
  INDEX `idx_court_date` (`court_id`, `booking_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verify table was created
SELECT 'Bookings table created successfully!' AS message;

-- Show table structure
DESCRIBE bookings;

