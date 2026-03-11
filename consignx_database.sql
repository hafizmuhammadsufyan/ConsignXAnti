-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026
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
-- Database: `consignx_db`
--
CREATE DATABASE IF NOT EXISTS `consignx_database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `consignx_database`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
-- Default password is 'admin123'
--

INSERT INTO `admins` (`username`, `email`, `password_hash`) VALUES
('admin', 'admin@consignx.com', '$2y$10$U.y125mB.cE21r5A1r3.4OZmGfOMY4hEqZ0BwQ2M5m7X5pC1.D37u');

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
-- Default password is 'agent123'
--

INSERT INTO `agents` (`name`, `company_name`, `email`, `phone`, `password_hash`, `status`) VALUES
('John Agent', 'FastCourier Inc.', 'agent@consignx.com', '1234567890', '$2y$10$eO1vN5xU6Y78wH90xN3D.OCoUfN2t08LdY6rW482y7qgB1o3.ZpZe', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
-- Default password is 'customer123'
--

INSERT INTO `customers` (`name`, `email`, `phone`, `password_hash`) VALUES
('Alice Customer', 'customer@consignx.com', '0987654321', '$2y$10$S9.z.0d8/148e2Lz.Qd7c.Z1l3aG.w3x5zX/G1E/7GqG5B.G2N.4q');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`name`, `state`) VALUES
('New York', 'NY'),
('Los Angeles', 'CA'),
('Chicago', 'IL'),
('Houston', 'TX'),
('Phoenix', 'AZ');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracking_number` varchar(50) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `origin_city_id` int(11) NOT NULL,
  `destination_city_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `recipient_address` text NOT NULL,
  `weight` decimal(10,2) NOT NULL COMMENT 'Weight in kg',
  `price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Picked Up','In Transit','Out For Delivery','Delivered') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `agent_id` (`agent_id`),
  KEY `customer_id` (`customer_id`),
  KEY `origin_city_id` (`origin_city_id`),
  KEY `destination_city_id` (`destination_city_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_status_history`
--

CREATE TABLE `shipment_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) NOT NULL,
  `status` enum('Pending','Picked Up','In Transit','Out For Delivery','Delivered') NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `changed_by_role` enum('admin','agent') NOT NULL,
  `changed_by_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` enum('admin','agent','customer') NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_type_id` (`user_type`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revenue`
--

CREATE TABLE `revenue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shipment_id` (`shipment_id`),
  KEY `agent_id` (`agent_id`),
  KEY `transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipments_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shipments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shipments_dest_city` FOREIGN KEY (`destination_city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_shipments_orig_city` FOREIGN KEY (`origin_city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  ADD CONSTRAINT `fk_history_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `revenue`
--
ALTER TABLE `revenue`
  ADD CONSTRAINT `fk_revenue_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_revenue_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- <?php
-- require_once __DIR__ . '/includes/db.php';

-- try {
--     $sql = "CREATE TABLE IF NOT EXISTS `company_requests` ( 
--         `id` int(11) NOT NULL AUTO_INCREMENT, 
--         `name` varchar(100) NOT NULL, 
--         `company_name` varchar(150) NOT NULL, 
--         `email` varchar(100) NOT NULL, 
--         `phone` varchar(20) NOT NULL, 
--         `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending', 
--         `created_at` timestamp NOT NULL DEFAULT current_timestamp(), 
--         PRIMARY KEY (`id`) 
--     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
--     $pdo->exec($sql);
--     echo "Table created successfully.";
-- } catch (PDOException $e) {
--     echo "Error: " . $e->getMessage();
-- }
-- ?>

