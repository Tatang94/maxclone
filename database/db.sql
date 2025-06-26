-- RideMax Super App Database Schema
-- Mobile-first ride-hailing application
-- Created: 2025-06-25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

-- Database: `ridemax_db`
CREATE DATABASE IF NOT EXISTS `ridemax_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ridemax_db`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_driver` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_driver` (`is_driver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `vehicle_make` varchar(50) NOT NULL,
  `vehicle_model` varchar(50) NOT NULL,
  `vehicle_plate` varchar(20) NOT NULL,
  `vehicle_year` year(4) DEFAULT NULL,
  `vehicle_color` varchar(30) DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `current_latitude` decimal(10,8) DEFAULT NULL,
  `current_longitude` decimal(11,8) DEFAULT NULL,
  `last_location_update` timestamp NULL DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 5.00,
  `total_rides` int(11) NOT NULL DEFAULT 0,
  `total_earnings` decimal(12,2) NOT NULL DEFAULT 0.00,
  `documents_verified` tinyint(1) NOT NULL DEFAULT 0,
  `background_check_passed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `license_number` (`license_number`),
  UNIQUE KEY `vehicle_plate` (`vehicle_plate`),
  KEY `idx_is_online` (`is_online`),
  KEY `idx_location` (`current_latitude`,`current_longitude`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `fk_drivers_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `pickup_location` text NOT NULL,
  `pickup_latitude` decimal(10,8) DEFAULT NULL,
  `pickup_longitude` decimal(11,8) DEFAULT NULL,
  `destination` text NOT NULL,
  `destination_latitude` decimal(10,8) DEFAULT NULL,
  `destination_longitude` decimal(11,8) DEFAULT NULL,
  `vehicle_type` enum('economy','comfort','premium') NOT NULL DEFAULT 'economy',
  `status` enum('pending','accepted','in_progress','completed','cancelled','scheduled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','wallet') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `total_price` decimal(10,2) NOT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `distance_price` decimal(10,2) DEFAULT NULL,
  `service_fee` decimal(10,2) DEFAULT NULL,
  `surge_multiplier` decimal(3,2) DEFAULT 1.00,
  `estimated_distance` decimal(6,2) DEFAULT NULL,
  `actual_distance` decimal(6,2) DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL,
  `actual_duration` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `is_round_trip` tinyint(1) NOT NULL DEFAULT 0,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_driver_id` (`driver_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_vehicle_type` (`vehicle_type`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_scheduled_for` (`scheduled_for`),
  CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_drivers` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` text DEFAULT NULL,
  `rated_by` enum('user','driver') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rating` (`order_id`,`rated_by`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_driver_id` (`driver_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `fk_ratings_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_drivers` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_activity_logs_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_type` (`setting_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_remember_tokens_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','wallet','bank_transfer') NOT NULL,
  `payment_gateway` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_payments_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promocodes`
--

CREATE TABLE `promocodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order_amount` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `user_limit` int(11) DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `valid_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valid_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_valid_dates` (`valid_from`,`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Insert default admin user
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `user_type`, `status`) VALUES
('Admin User', 'admin@demo.com', '+6281234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert demo passenger user
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `user_type`, `is_driver`, `status`) VALUES
('John Passenger', 'user@demo.com', '+6281234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, 'active');

-- Insert demo driver user
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `user_type`, `is_driver`, `status`) VALUES
('Mike Driver', 'driver@demo.com', '+6281234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, 'active');

-- Insert driver details for demo driver
INSERT INTO `drivers` (`user_id`, `license_number`, `vehicle_make`, `vehicle_model`, `vehicle_plate`, `vehicle_year`, `vehicle_color`, `is_online`, `rating`, `total_rides`, `documents_verified`, `background_check_passed`) VALUES
(3, 'DL123456789', 'Toyota', 'Avanza', 'B1234ABC', 2020, 'Silver', 0, 4.85, 150, 1, 1);

-- Insert default application settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('app_name', 'RideMax', 'string', 'Application name'),
('app_version', '1.0.0', 'string', 'Application version'),
('default_language', 'en', 'string', 'Default application language'),
('default_currency', 'IDR', 'string', 'Default currency'),
('support_email', 'support@ridemax.com', 'string', 'Support email address'),
('support_phone', '+62-800-123-4567', 'string', 'Support phone number'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('economy_base_price', '15000', 'number', 'Economy vehicle base price'),
('comfort_base_price', '25000', 'number', 'Comfort vehicle base price'),
('premium_base_price', '40000', 'number', 'Premium vehicle base price'),
('price_per_km', '3000', 'number', 'Price per kilometer'),
('service_fee', '2000', 'number', 'Service fee amount'),
('driver_commission', '80', 'number', 'Driver commission percentage'),
('surge_multiplier', '1.5', 'number', 'Surge pricing multiplier'),
('cancellation_fee', '5000', 'number', 'Cancellation fee amount'),
('email_new_order', 'true', 'boolean', 'Send email for new orders'),
('email_order_complete', 'true', 'boolean', 'Send email when order completes'),
('email_new_user', 'true', 'boolean', 'Send email for new user registration'),
('sms_order_status', 'false', 'boolean', 'Send SMS for order status updates'),
('sms_driver_arrival', 'false', 'boolean', 'Send SMS when driver arrives'),
('password_min_length', '6', 'number', 'Minimum password length'),
('max_login_attempts', '5', 'number', 'Maximum login attempts'),
('session_timeout', '120', 'number', 'Session timeout in minutes'),
('lockout_duration', '15', 'number', 'Account lockout duration in minutes'),
('require_email_verification', 'false', 'boolean', 'Require email verification'),
('require_phone_verification', 'false', 'boolean', 'Require phone verification'),
('enable_two_factor', 'false', 'boolean', 'Enable two-factor authentication');

-- Create indexes for better performance
CREATE INDEX idx_orders_user_status ON orders(user_id, status);
CREATE INDEX idx_orders_driver_status ON orders(driver_id, status);
CREATE INDEX idx_orders_status_created ON orders(status, created_at);
CREATE INDEX idx_drivers_online_location ON drivers(is_online, current_latitude, current_longitude);
CREATE INDEX idx_activity_logs_user_created ON activity_logs(user_id, created_at);

-- Create triggers for automatic updates

DELIMITER $$

-- Trigger to update driver rating when new rating is added
CREATE TRIGGER update_driver_rating_after_insert
AFTER INSERT ON ratings
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    
    IF NEW.rated_by = 'user' THEN
        SELECT AVG(rating) INTO avg_rating 
        FROM ratings 
        WHERE driver_id = NEW.driver_id AND rated_by = 'user';
        
        UPDATE drivers 
        SET rating = COALESCE(avg_rating, 5.00), updated_at = CURRENT_TIMESTAMP 
        WHERE user_id = NEW.driver_id;
    END IF;
END$$

-- Trigger to update driver rating when rating is updated
CREATE TRIGGER update_driver_rating_after_update
AFTER UPDATE ON ratings
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    
    IF NEW.rated_by = 'user' THEN
        SELECT AVG(rating) INTO avg_rating 
        FROM ratings 
        WHERE driver_id = NEW.driver_id AND rated_by = 'user';
        
        UPDATE drivers 
        SET rating = COALESCE(avg_rating, 5.00), updated_at = CURRENT_TIMESTAMP 
        WHERE user_id = NEW.driver_id;
    END IF;
END$$

-- Trigger to clean up old activity logs (keep only last 90 days)
CREATE EVENT cleanup_old_logs
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

DELIMITER ;

-- Set auto increment starting values
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE drivers AUTO_INCREMENT = 1;
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE ratings AUTO_INCREMENT = 1;
ALTER TABLE activity_logs AUTO_INCREMENT = 1;
ALTER TABLE settings AUTO_INCREMENT = 1;
ALTER TABLE remember_tokens AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE promocodes AUTO_INCREMENT = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
