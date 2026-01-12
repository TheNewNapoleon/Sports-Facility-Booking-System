-- SQL Script to Update Users Table Schema for Add User Functionality
-- Run this in phpMyAdmin or MySQL command line
-- This updates the ENUM values to match what the admin dashboard form sends

-- Update role ENUM to include 'staff' and 'student' (remove 'user' if it exists)
ALTER TABLE `users` 
  MODIFY COLUMN `role` ENUM('admin','staff','student') DEFAULT 'student';

-- Update status ENUM to include 'inactive' option
ALTER TABLE `users` 
  MODIFY COLUMN `status` ENUM('active','pending','inactive','blacklisted') DEFAULT 'active';

-- Verify the changes
DESCRIBE users;

