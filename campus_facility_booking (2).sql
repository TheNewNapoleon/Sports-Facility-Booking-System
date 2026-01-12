-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Dec 10, 2025 at 04:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campus_facility_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcement` (
  `announcement_id` varchar(10) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `posted_by` varchar(30) NOT NULL,
  `posted_date` datetime DEFAULT current_timestamp(),
  `audience` enum('all','student','staff','admin') DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `message`, `posted_by`, `posted_date`, `audience`) VALUES
('AN001', 'Sports Complex Maintenance', 'Futsal Court (C200) will be closed for maintenance on 21 June 2025.', 'U004', '2025-11-20 14:04:31', 'all'),
('AN002', 'New Booking Policy', 'All students must book facilities at least 24 hours in advance starting July 2025.', 'U004', '2025-11-20 14:04:31', 'student'),
('AN003', 'Staff Meeting Reminder', 'All sports facility staff please attend the briefing on 15 June, 10 AM.', 'U003', '2025-11-20 14:04:31', 'staff'),
('AN004', 'Swimming Pool Temporary Closure', 'Pool area is closed for water inspection until further notice.', 'U004', '2025-11-20 14:04:31', 'all'),
('AN005', 'New Operating Hours', 'Sports Complex will operate from 8 AM to 10 PM daily starting 20 December 2025.', 'U006', '2025-12-06 16:22:11', ''),
('AN006', 'Gym Equipment Replacement', 'Several gym machines will be replaced on 18 December 2025. Expect temporary closures.', 'U002', '2025-12-06 17:55:42', ''),
('AN102', 'New Booking System Update', 'The online booking system will undergo system upgrades from 12 AM to 3 AM on 10 December 2025.', 'U002', '2025-12-05 09:20:14', 'all'),
('AN103', 'Swimming Pool Closure', 'Swimming Pool (A101) will be closed for cleaning from 15–17 December 2025.', 'U003', '2025-12-05 11:45:22', ''),
('AN104', 'Badminton Court Reservation Limit', 'Users can only reserve up to 2 badminton courts per day starting 1 January 2026.', 'U001', '2025-12-06 14:05:50', 'all'),
('AN107', 'Volleyball Court Booking Rules', 'All volleyball bookings must be made at least 24 hours in advance.', 'U008', '2025-12-07 09:33:12', ''),
('AN108', 'Emergency Water Shutdown', 'Water supply will be temporarily disrupted in Block C on 9 December 2025.', 'U003', '2025-12-07 10:12:45', 'all'),
('AN109', 'Tennis Court Repainting', 'Tennis Court (T300) will be unavailable from 22–24 December 2025 for repainting.', 'U005', '2025-12-07 13:18:20', ''),
('AN110', 'Holiday Closure Notice', 'The Sports Complex will be closed on 25 December 2025 for Christmas Day.', 'U001', '2025-12-08 08:40:11', 'all'),
('AN111', 'System Downtime', 'Booking portal may be slow due to backend updates on 12 December 2025.', 'U007', '2025-12-08 09:55:37', ''),
('AN112', 'Lost & Found Item', 'A wallet was found near the badminton court. Please claim at the reception.', 'U004', '2025-12-08 10:20:56', ''),
('AN113', 'Safety Reminder', 'Users must wear proper sports shoes when using the indoor courts.', 'U002', '2025-12-08 12:15:33', 'all'),
('AN114', 'Court Flooding Repair', 'Basketball Court B120 is closed until repairs are completed.', 'U006', '2025-12-08 14:49:02', ''),
('AN115', 'New Fitness Class', 'A new weekly HIIT fitness class starts 5 January 2026. Register online.', 'U008', '2025-12-08 15:22:44', ''),
('AN116', 'Parking Area Renovation', 'Parking Zone P2 will be partially closed from 10–12 December 2025.', 'U007', '2025-12-08 17:09:15', ''),
('AN117', 'Yoga Workshop', 'Join our free yoga workshop on 20 December 2025. Limited slots available.', 'U005', '2025-12-08 18:44:37', 'all'),
('AN118', 'Maintenance Delays', 'The refurbishment of Court A150 is extended until 19 December 2025.', 'U003', '2025-12-09 09:11:22', ''),
('AN119', 'Mobile App Launch', 'A new mobile app for sports booking will be launched on 1 January 2026.', 'U001', '2025-12-09 10:05:30', 'all'),
('AN120', 'Monthly Sanitisation', 'All courts will undergo sanitisation on 30 December 2025 from 6 PM onwards.', 'U004', '2025-12-09 11:22:08', ''),
('AN131', 'Sports Complex Maintenance', 'Futsal Court (C200) will be closed for maintenance on 21 June 2025.', 'U004', '2025-12-04 10:44:35', '');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `venue_id` varchar(10) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `status` enum('pending','approved','cancelled','completed','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `venue_id`, `booking_date`, `booking_time`, `status`) VALUES
(1, 'S123', 'A100', '2025-11-22', '20:00:00', 'approved'),
(2, 'U001', 'A101', '2025-11-20', '10:00:00', 'approved'),
(3, 'U001', 'B100', '2025-06-02', '15:00:00', 'pending'),
(4, 'U001', 'C200', '2025-11-19', '18:00:00', 'approved'),
(5, 'U001', 'B101', '2025-11-15', '09:00:00', 'cancelled'),
(36, 'U001', 'A100', '2025-01-05', '09:00:00', 'approved'),
(37, 'U002', 'A101', '2025-11-23', '10:00:00', 'approved'),
(38, 'U003', 'B100', '2025-01-07', '11:00:00', 'completed'),
(39, 'U004', 'B101', '2025-01-08', '12:30:00', 'cancelled'),
(40, 'U005', 'C200', '2025-01-09', '14:00:00', 'approved'),
(41, 'U006', 'C201', '2025-01-09', '15:00:00', 'pending'),
(42, 'U007', 'C202', '2025-01-10', '16:00:00', 'approved'),
(43, 'U008', 'C203', '2025-01-11', '09:30:00', 'completed'),
(44, 'U009', 'A100', '2025-01-12', '10:15:00', 'pending'),
(45, 'U010', 'A101', '2025-01-12', '11:45:00', 'approved'),
(46, 'U001', 'B100', '2025-11-23', '08:00:00', 'approved'),
(47, 'U002', 'B101', '2025-01-14', '13:00:00', 'pending'),
(48, 'U003', 'C200', '2025-01-15', '10:00:00', 'approved'),
(49, 'U004', 'C201', '2025-01-16', '11:30:00', 'cancelled'),
(50, 'U005', 'C202', '2025-01-16', '14:30:00', 'approved'),
(51, 'U006', 'C203', '2025-01-17', '16:30:00', 'rejected'),
(52, 'U007', 'A100', '2025-01-18', '09:15:00', 'completed'),
(53, 'U008', 'A101', '2025-01-19', '13:45:00', 'approved'),
(54, 'U009', 'B100', '2025-01-19', '15:45:00', 'cancelled'),
(55, 'U010', 'B101', '2025-01-20', '17:30:00', 'approved'),
(56, 'U001', 'C200', '2025-01-21', '08:30:00', 'completed'),
(57, 'U002', 'C201', '2025-01-22', '10:00:00', 'approved'),
(58, 'U003', 'C202', '2025-01-23', '12:30:00', 'approved'),
(59, 'U004', 'C203', '2025-01-24', '14:00:00', 'approved'),
(60, 'U005', 'A100', '2025-01-24', '16:00:00', 'cancelled'),
(61, 'U006', 'A101', '2025-01-25', '18:30:00', 'completed'),
(62, 'U007', 'B100', '2025-01-26', '09:45:00', 'approved'),
(63, 'U008', 'B101', '2025-01-27', '11:15:00', 'approved'),
(64, 'U009', 'C200', '2025-01-27', '13:30:00', 'approved'),
(65, 'U010', 'C201', '2025-01-28', '15:00:00', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `venue_id` varchar(10) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `name`, `venue_id`, `event_date`, `event_time`) VALUES
(1, 'Badminton Friendly Match', 'A100', '2025-06-10', '10:00:00'),
(2, 'Pickleball Beginner Training', 'A101', '2025-06-12', '15:00:00'),
(3, 'Basketball 3v3 Tournament', 'B100', '2025-07-05', '09:00:00'),
(4, 'Volleyball Team Training', 'B101', '2025-06-18', '17:00:00'),
(5, 'Futsal Inter-Faculty Match', 'C200', '2025-07-20', '20:00:00'),
(6, 'Table Tennis Friendly Challenge', 'C201', '2025-07-25', '14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `respond` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `submitted_at` datetime DEFAULT current_timestamp(),
  `respond_at` timestamp NULL DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `subject`, `message`, `respond`, `status`, `submitted_at`, `respond_at`, `category`) VALUES
(1, 'U001', 'Booking Issue', 'I was unable to book the badminton court for today.', 'Hi sorry', 'reviewed', '2025-11-20 14:35:59', '2025-11-02 06:44:07', 'General'),
(2, 'U002', 'Gym Equipment', 'Some dumbbells in the gym are broken.', 'Thank you for reporting. We will repair them tomorrow.', 'reviewed', '2025-11-20 14:35:59', '2025-01-05 04:00:00', 'General'),
(3, 'U003', 'Court Availability', 'Can we extend futsal court hours in the evening?', NULL, 'pending', '2025-11-20 14:35:59', NULL, 'General'),
(4, 'U004', 'Swimming Pool Cleanliness', 'The swimming pool water seems a bit dirty.', 'We have cleaned the pool today. Thank you for letting us know.', 'resolved', '2025-11-20 14:35:59', '2025-01-06 01:30:00', 'General'),
(5, 'U005', 'Volleyball Net', 'The volleyball net is loose and needs adjustment.', 'Maintenance has been notified and will fix it shortly.', 'reviewed', '2025-11-20 14:35:59', '2025-01-07 06:00:00', 'General'),
(6, 'U006', 'Booking Confirmation', 'I did not receive a confirmation email for my booking.', NULL, 'pending', '2025-11-20 14:35:59', NULL, 'General'),
(7, 'U007', 'Table Tennis Table', 'One of the table tennis tables is missing paddles.', 'New paddles have been added. Enjoy your game!', 'resolved', '2025-11-20 14:35:59', '2025-01-08 03:00:00', 'General'),
(8, 'U008', 'Futsal Court Lights', 'Lights in the futsal court are flickering.', 'We scheduled an electrician to check the lights tomorrow.', 'reviewed', '2025-11-20 14:35:59', '2025-01-09 07:30:00', 'General'),
(9, 'U009', 'Pickleball Court', 'Can we have more pickleball courts in Block C?', NULL, 'pending', '2025-11-20 14:35:59', NULL, 'General'),
(10, 'U010', 'Gym Hours', 'Can the gym open earlier in the morning?', 'We will adjust the opening hours starting next week.', 'reviewed', '2025-11-20 14:35:59', '2025-01-10 00:00:00', 'General'),
(11, 'U001', 'Test Subject', 'test test test', NULL, 'pending', '2025-11-20 14:43:37', NULL, 'General'),
(12, 'U001', 'Test Subject', 'test test test', NULL, 'pending', '2025-11-20 14:44:27', NULL, 'General'),
(13, 'U001', 'Test Subject', 'test test test', NULL, 'pending', '2025-11-20 14:46:28', NULL, 'General'),
(14, 'U001', 'Test Subject', 'test test test', NULL, 'pending', '2025-11-20 14:50:46', NULL, 'General'),
(15, 'U001', 'Test Subject 2', 'fdfdfdfdfdfdf', NULL, 'pending', '2025-11-20 14:50:58', NULL, 'General'),
(16, 'U001', 'fdfdfee', 'efefefefef', NULL, 'pending', '2025-11-20 14:56:21', NULL, 'General'),
(17, 'U001', 'tests', 'tetestsetsetest', NULL, 'pending', '2025-11-20 15:13:36', NULL, 'General'),
(18, 'U001', 'Test Subject', 'rererererere', NULL, 'pending', '2025-11-20 15:34:31', NULL, 'Suggestion'),
(19, 'U001', 'testttttttt23', 'tesssssssssssssssssst', 'goodjob\r\n', 'reviewed', '2025-11-22 16:05:27', '2025-11-22 09:00:00', 'Facility');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','staff','admin') DEFAULT 'student',
  `avatar_path` varchar(255) DEFAULT 'images/default_avatar.png',
  `living_place` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `status` enum('active','inactive','blacklisted') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users`
(`user_id`, `name`, `email`, `password`, `role`, `avatar_path`, `living_place`, `phone_number`, `date_of_birth`, `gender`, `status`) VALUES
('A001', 'ADMIN MASTER', 'admin@tarc.edu.my', 'a', 'admin', 'images/admin.png', 'Kuala Lumpur', '0123456789', '2001-01-15', 'male', 'active'),
('1001', 'LIM WEI JIAN', 'lwj@tarc.edu.my', 'Staff@123', 'staff', 'images/staff1.png', 'Petaling Jaya', '0112233445', '2002-03-12', 'male', 'active'),
('1002', 'TAN MEI LING', 'tml@tarc.edu.my', 'Ling#456', 'staff', 'images/staff2.png', 'Subang Jaya', '0123344556', '2003-07-25', 'female', 'active'),
('1003', 'ONG KAI XIN', 'okx@tarc.edu.my', 'KaiXin$89', 'staff', 'images/staff3.png', 'Cheras', '0134455667', '2001-11-08', 'male', 'inactive'),
('1004', 'CHUA PEI YEE', 'cpy@tarc.edu.my', 'PeiYee!7', 'staff', 'images/staff4.png', 'Ampang', '0145566778', '2004-05-19', 'female', 'active'),
('1005', 'GOH JUN HAO', 'gjh@tarc.edu.my', 'JunHao*8', 'staff', 'images/staff5.png', 'Kajang', '0166677889', '2002-09-30', 'male', 'active'),
('2001001', 'AARON TAN SHENG', 'aaronts-sm24@student.tarc.edu.my', 'Aaron@123', 'student', 'images/std1.png', 'Serdang', '0177788990', '2005-02-14', 'male', 'active'),
('2001002', 'NG MEI XUAN', 'ngmeix-sm24@student.tarc.edu.my', 'MeiXuan#9', 'student', 'images/std2.png', 'Puchong', '0188899001', '2006-06-21', 'female', 'active'),
('2001003', 'LEE JIA HAO', 'leejh-sm24@student.tarc.edu.my', 'JiaHao!5', 'student', 'images/std3.png', 'Bangi', '0199900112', '2004-10-10', 'male', 'inactive'),
('2001004', 'WONG YI LING', 'wongyl-sm24@student.tarc.edu.my', 'YiLing$7', 'student', 'images/std4.png', 'Rawang', '0111011121', '2003-01-03', 'female', 'active'),
('2001005', 'CHAN KAI WEN', 'chankw-sm24@student.tarc.edu.my', 'KaiWen@8', 'student', 'images/std5.png', 'Selayang', '0121213141', '2002-08-18', 'male', 'active'),
('2001006', 'TEO SHU MIN', 'teosm-sm24@student.tarc.edu.my', 'ShuMin#4', 'student', 'images/std6.png', 'Putrajaya', '0131314151', '2006-12-05', 'female', 'active'),
('2001007', 'LOW ZHI KANG', 'lowzk-sm24@student.tarc.edu.my', 'ZhiKang!6', 'student', 'images/std7.png', 'Cyberjaya', '0141415161', '2001-04-27', 'male', 'blacklisted'),
('2001008', 'HO PEI SHAN', 'hopeis-sm24@student.tarc.edu.my', 'PeiShan$2', 'student', 'images/std8.png', 'Nilai', '0161516171', '2005-09-09', 'female', 'active'),
('2001009', 'YAP JUN YAO', 'yapjy-sm24@student.tarc.edu.my', 'JunYao@9', 'student', 'images/std9.png', 'Gombak', '0171617181', '2003-07-01', 'male', 'active');


-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `shared_group` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'Detailed description or notes about the venue',
  `image` varchar(255) DEFAULT NULL COMMENT 'Path or URL to an image of the venue',
  `status` enum('open','close') NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `name`, `capacity`, `location`, `shared_group`, `description`, `image`, `status`) VALUES
('12345V', 'test', 1, '0', 'test123', NULL, 'images/coordinator.png', 'open'),
('A100', 'Badminton Court 1', 6, '0', 'A-GROUP-1', 'Test1', '/images/fac6.jpeg', ''),
('A101', 'Pickleball Court 1', 4, '0', 'A-GROUP-1', NULL, '/images/fac2.jpg', ''),
('B100', 'Basketball Court', 10, '0', '', NULL, '/images/fac5.jpg', ''),
('B101', 'Volleyball Court', 12, 'Block B - Level 2', NULL, NULL, '/images/fac7.jpg', 'open'),
('C200', 'Futsal Court', 10, 'Block C - Outdoor', NULL, NULL, '/images/fac1.jpg', 'open'),
('C201', 'Table Tennis Area', 4, 'Block C - Level 1', NULL, NULL, '/images/fac8.png', 'open'),
('C202', 'Badminton Court 2', 6, 'Block C - Level 1', 'C-GROUP-1', NULL, '/images/fac6.jpeg', 'open'),
('C203', 'Pickleball Court 2', 4, 'Block C - Level 1', 'C-GROUP-1', NULL, '/images/fac2.jpg', 'open'),
('ZTest1', 'test1', 10, '0', 'A-GROUP-1', NULL, 'images/street-shop-.png', 'open');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `venue_id` (`venue_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `venue_id` (`venue_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
