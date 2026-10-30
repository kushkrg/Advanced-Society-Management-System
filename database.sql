-- SocietyPro | Advanced Society Management System
-- Core PHP + MySQL. Import in phpMyAdmin.
-- Creates database `society_system`, all tables, demo accounts,
-- sample flats/resident, facilities, notices and demo billing data.

CREATE DATABASE IF NOT EXISTS `society_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `society_system`;

CREATE TABLE IF NOT EXISTS `flats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_name` varchar(10) NOT NULL,
  `flat_no` varchar(10) NOT NULL,
  `floor_no` int(11) NOT NULL DEFAULT 0,
  `flat_type` enum('1BHK','2BHK','3BHK','4BHK') NOT NULL DEFAULT '2BHK',
  `area_sqft` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `block_flat` (`block_name`, `flat_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','resident') NOT NULL DEFAULT 'resident',
  `flat_id` int(11) DEFAULT NULL,
  `resident_type` enum('owner','tenant') NOT NULL DEFAULT 'owner',
  `move_in_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `flat_id` (`flat_id`),
  CONSTRAINT `users_flat_fk` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `family_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `relation` varchar(50) NOT NULL DEFAULT 'Family',
  `age` int(11) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `family_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vehicle_no` varchar(20) NOT NULL,
  `vehicle_type` enum('Car','Bike') NOT NULL DEFAULT 'Car',
  `model` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_no` (`vehicle_no`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `vehicle_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flat_id` int(11) NOT NULL,
  `bill_month` char(7) NOT NULL,
  `maintenance_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `water_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `parking_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `flat_month` (`flat_id`, `bill_month`),
  KEY `bill_month` (`bill_month`),
  CONSTRAINT `bill_flat_fk` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(20) NOT NULL DEFAULT 'Cash',
  `txn_ref` varchar(80) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `payment_bill_fk` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `flat_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'Other',
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `description` text DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `admin_remark` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `complaint_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `vehicle_no` varchar(20) DEFAULT NULL,
  `flat_id` int(11) NOT NULL,
  `purpose` varchar(150) DEFAULT NULL,
  `in_time` datetime NOT NULL DEFAULT current_timestamp(),
  `out_time` datetime DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flat_id` (`flat_id`),
  CONSTRAINT `visitor_flat_fk` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'General',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `posted_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `venue` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `flat_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(60) DEFAULT NULL,
  `purpose` varchar(150) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `booking_facility_fk` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'Other',
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `vendor` varchar(120) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `expense_date` (`expense_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `skey` varchar(60) NOT NULL,
  `svalue` varchar(255) NOT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample flats (IDs 1-8)
INSERT INTO `flats` (`block_name`, `flat_no`, `floor_no`, `flat_type`, `area_sqft`) VALUES
('A', '101', 1, '2BHK', 950),
('A', '102', 1, '2BHK', 950),
('A', '201', 2, '3BHK', 1250),
('A', '202', 2, '1BHK', 650),
('B', '101', 1, '3BHK', 1250),
('B', '102', 1, '2BHK', 950),
('B', '201', 2, '4BHK', 1600),
('B', '202', 2, '2BHK', 950);

-- Demo accounts (change passwords after first login)
-- Admin: admin@example.com / Admin@123
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`) VALUES
('Society Admin', 'admin@example.com', '9999999999', '$2y$10$qJDRCJdH92zCPITfmkn.8eqcHvUweLH2VQT5paRi/gYbVRaPeoHW.', 'admin'),
-- Resident: resident@example.com / Resident@123 (Flat A-101)
('Rahul Sharma', 'resident@example.com', '9876543210', '$2y$10$Mcczn2BHubiXvSc18FSkA.GzO/sCnExCEnBuXvqBLINiOIJke/ptK', 'resident');

UPDATE `users` SET `flat_id` = 1, `resident_type` = 'owner', `move_in_date` = '2024-04-01' WHERE `email` = 'resident@example.com';

-- Demo family + vehicle for the demo resident (user id 2)
INSERT INTO `family_members` (`user_id`, `name`, `relation`, `age`, `phone`) VALUES
(2, 'Priya Sharma', 'Wife', 32, '9876543211'),
(2, 'Aarav Sharma', 'Son', 8, NULL);
INSERT INTO `vehicles` (`user_id`, `vehicle_no`, `vehicle_type`, `model`) VALUES
(2, 'BR01AB1234', 'Car', 'Maruti Swift');

INSERT INTO `facilities` (`name`, `description`, `capacity`, `charges`) VALUES
('Community Hall', 'For functions, parties and society meetings', 150, 2000.00),
('Club House', 'Indoor games, library and lounge', 50, 500.00),
('Guest Room', 'For residents guests, per night', 4, 1000.00),
('Swimming Pool', 'Open 6 AM - 9 PM, coaching available', 30, 0.00);

INSERT INTO `notices` (`title`, `body`, `category`, `is_pinned`, `posted_by`) VALUES
('Welcome to SocietyPro', 'Dear residents, our society is now managed on SocietyPro. Please check your maintenance bills, raise complaints and book facilities online. For help, visit the society office.', 'General', 1, 1),
('Water Supply Maintenance', 'Water supply will be shut on Sunday from 10 AM to 2 PM for tank cleaning. Please store water in advance. Sorry for the inconvenience.', 'Maintenance', 0, 1);

INSERT INTO `events` (`title`, `event_date`, `event_time`, `venue`, `description`, `created_by`) VALUES
('Diwali Celebration', '2026-11-08', '18:00:00', 'Community Hall', 'Diwali celebration with cultural programme and dinner. All residents are invited.', 1),
('Annual General Meeting', '2026-11-22', '11:00:00', 'Club House', 'AGM of the society. Agenda: annual accounts, elections and maintenance revision.', 1);

-- Demo bills for flat A-101 (2BHK: 2500 maintenance + 300 water + 200 parking = 3000)
INSERT INTO `bills` (`flat_id`, `bill_month`, `maintenance_charge`, `water_charge`, `parking_charge`, `other_charge`, `late_fee`, `total_amount`, `paid_amount`, `due_date`, `status`) VALUES
(1, '2026-09', 2500.00, 300.00, 200.00, 0.00, 0.00, 3000.00, 3000.00, '2026-09-10', 'paid'),
(1, '2026-10', 2500.00, 300.00, 200.00, 0.00, 0.00, 3000.00, 0.00, '2026-10-10', 'unpaid');
INSERT INTO `payments` (`bill_id`, `amount`, `method`, `txn_ref`, `payment_date`, `received_by`) VALUES
(1, 3000.00, 'UPI', 'UPI/DEMO123456', '2026-09-08', 1);

INSERT INTO `complaints` (`user_id`, `flat_id`, `title`, `category`, `priority`, `description`) VALUES
(2, 1, 'Water leakage in bathroom', 'Plumbing', 'Medium', 'There is a slow leakage from the bathroom tap since yesterday.');

INSERT INTO `expenses` (`title`, `category`, `amount`, `expense_date`, `vendor`) VALUES
('Security agency - October', 'Security', 45000.00, '2026-10-05', 'SafeGuard Services'),
('Common area electricity', 'Electricity', 12350.00, '2026-10-12', 'State Electricity Board');

INSERT INTO `settings` (`skey`, `svalue`) VALUES
('society_name', 'Green Valley Apartments'),
('society_address', 'Green Valley Apartments, MG Road'),
('rate_1bhk', '1500'),
('rate_2bhk', '2500'),
('rate_3bhk', '3500'),
('rate_4bhk', '4500'),
('water_charge', '300'),
('parking_rate', '200'),
('late_fee', '100'),
('due_day', '10');
