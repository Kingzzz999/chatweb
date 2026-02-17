-- phpMyAdmin SQL Dump
-- Database: `telegram_clone`

CREATE DATABASE IF NOT EXISTS `telegram_clone`;
USE `telegram_clone`;

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(15) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `profile_photo` varchar(255) DEFAULT 'default.jpg',
  `bio` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `last_seen` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_number` (`phone_number`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `otp_verifications`
CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(15) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phone_otp` (`phone_number`,`otp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `messages`
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `message_type` enum('text','image','video','file') DEFAULT 'text',
  `is_read` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `idx_sender_receiver` (`sender_id`,`receiver_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `contacts`
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_contact` (`user_id`,`contact_id`),
  KEY `contact_id` (`contact_id`),
  CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `contacts_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `user_sessions`
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `device_info` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_activity` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default data
INSERT INTO `users` (`phone_number`, `username`, `full_name`, `profile_photo`) VALUES
('625117020277', 'admin', 'Admin', 'default.jpg');
