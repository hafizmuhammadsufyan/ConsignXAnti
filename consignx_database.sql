-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 10:07 PM
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
-- Database: `consignx_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `created_at`, `profile_image`) VALUES
(1, 'Administration', 'admin@consignx.com', '$2y$10$tF9Kkd/JBGbwbzAiVG6Gfe2J47.1d6n8jL8nqsrnm3PH8JgEuSr/a', '2026-03-08 16:17:59', 'profile_69cd6baf06f2f.png');

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `name`, `company_name`, `username`, `email`, `phone`, `password_hash`, `status`, `created_at`, `profile_image`) VALUES
(1, 'ConsignX Agent', 'ConsignX Logistics', NULL, 'agent@consignx.com', '03456888785', '$2a$12$lXx5AtKgziYt9fqfoxHzl.JY2GA6rQCnfXvMr5McGU5Dd0P9IJclS', 'active', '2026-03-31 21:09:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blocked_emails`
--

CREATE TABLE `blocked_emails` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `state`) VALUES
(6, 'Karachi', 'Sindh'),
(7, 'Lahore', 'Punjab'),
(8, 'Islamabad', 'Federal'),
(9, 'Rawalpindi', 'Punjab'),
(10, 'Faisalabad', 'Punjab'),
(11, 'Multan', 'Punjab'),
(12, 'Peshawar', 'KPK'),
(13, 'Quetta', 'Balochistan'),
(14, 'Hyderabad', 'Sindh'),
(15, 'Sialkot', 'Punjab'),
(16, 'Gujranwala', 'Punjab'),
(17, 'Bahawalpur', 'Punjab');

-- --------------------------------------------------------

--
-- Table structure for table `company_requests`
--

CREATE TABLE `company_requests` (
  `id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `password_hash`, `created_at`, `profile_image`) VALUES
(1, 'Alice', 'customer@consignx.com', '0345678910', '$2a$12$f9yq1Su/ZiuU7ZfuAZeBOuVrkAWKgZr5f0mKbQ1BdWQthT6rOb2hS', '2026-03-08 16:18:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_type` enum('admin','agent','customer') NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revenue`
--

CREATE TABLE `revenue` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `revenue`
--

INSERT INTO `revenue` (`id`, `shipment_id`, `agent_id`, `amount`, `transaction_date`, `created_at`) VALUES
(97, 34, 1, 1500.00, '2026-04-01', '2026-03-31 21:24:53'),
(98, 25, 1, 600.00, '2026-04-01', '2026-03-31 21:26:24'),
(99, 1, NULL, 746.25, '2026-04-01', '2026-03-31 21:27:05'),
(100, 41, 1, 1500.00, '2026-04-01', '2026-04-01 04:54:32'),
(101, 2, 1, 500.00, '2026-04-01', '2026-04-01 08:21:27'),
(104, 22, 1, 950.00, '2026-04-01', '2026-04-01 13:45:20'),
(107, 15, 1, 400.00, '2026-04-02', '2026-04-01 19:01:23');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
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
  `status` enum('Pending','Picked Up','In Transit','Out For Delivery','Delivered','Returned','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `tracking_number`, `agent_id`, `customer_id`, `origin_city_id`, `destination_city_id`, `recipient_name`, `recipient_phone`, `recipient_address`, `weight`, `price`, `status`, `created_at`, `updated_at`) VALUES
(1, 'C-B65C-5F16', NULL, 1, 7, 6, 'Agent', '03356478987', 'Sialkoat', 1.00, 746.25, 'Delivered', '2025-12-31 21:12:13', '2025-12-31 21:27:05'),
(2, 'C-A2B3-C4D5', 1, 1, 7, 12, 'Ahmed Ali', '03000000002', 'Street 2, Lahore', 1.20, 500.00, 'Delivered', '2025-11-05 07:00:00', '2026-01-01 08:21:27'),
(3, 'C-A3B4-C5D6', 1, 1, 8, 15, 'Usman Khan', '03000000003', 'Street 3, Islamabad', 3.00, 1100.00, 'In Transit', '2025-11-08 04:00:00', '2025-11-09 06:00:00'),
(4, 'C-A4B5-C6D7', 1, 1, 9, 14, 'Saad Ali', '03000000004', 'Street 4, Multan', 2.00, 800.00, 'Delivered', '2025-11-12 09:00:00', '2025-11-15 11:00:00'),
(5, 'C-A5B6-C7D8', 1, 1, 10, 16, 'Bilal Khan', '03000000005', 'Street 5, Peshawar', 4.00, 1300.00, 'In Transit', '2025-11-20 06:00:00', '2026-04-01 13:45:05'),
(6, 'C-B1C2-D3E4', 1, 1, 6, 11, 'Hassan Ali', '03000000006', 'Street 6', 2.80, 1000.00, 'Delivered', '2025-11-30 19:00:00', '2025-12-02 19:00:00'),
(7, 'C-B2C3-D4E5', 1, 1, 7, 13, 'Zain Ali', '03000000007', 'Street 7', 1.50, 600.00, 'Delivered', '2025-12-02 19:00:00', '2025-12-04 19:00:00'),
(8, 'C-B3C4-D5E6', 1, 1, 8, 12, 'Hamza Khan', '03000000008', 'Street 8', 3.50, 1200.00, 'Out For Delivery', '2025-12-04 19:00:00', '2025-12-05 19:00:00'),
(9, 'C-B4C5-D6E7', 1, 1, 9, 17, 'Danish Ali', '03000000009', 'Street 9', 2.20, 700.00, 'Picked Up', '2025-12-06 19:00:00', '2026-04-01 18:55:31'),
(10, 'C-B5C6-D7E8', 1, 1, 10, 15, 'Fahad Khan', '03000000010', 'Street 10', 4.10, 1400.00, 'In Transit', '2025-12-09 19:00:00', '2025-12-10 19:00:00'),
(11, 'C-B6C7-D8E9', 1, 1, 11, 16, 'Imran Ali', '03000000011', 'Street 11', 2.70, 950.00, 'Delivered', '2025-12-14 19:00:00', '2025-12-17 19:00:00'),
(12, 'C-B7C8-D9E1', 1, 1, 12, 14, 'Adnan Khan', '03000000012', 'Street 12', 5.00, 1600.00, 'Picked Up', '2025-12-17 19:00:00', '2025-12-17 19:00:00'),
(13, 'C-B8C9-D1E2', 1, 1, 13, 6, 'Noman Ali', '03000000013', 'Street 13', 3.20, 1100.00, 'Returned', '2025-12-21 19:00:00', '2025-12-24 19:00:00'),
(14, 'C-C1D2-E3F4', 1, 1, 6, 14, 'Asad Ali', '03000000014', 'Street 14', 2.30, 900.00, 'Delivered', '2025-12-31 19:00:00', '2026-01-02 19:00:00'),
(15, 'C-C2D3-E4F5', 1, 1, 7, 15, 'Rizwan Khan', '03000000015', 'Street 15', 1.10, 400.00, 'Delivered', '2026-01-01 19:00:00', '2026-04-01 19:01:23'),
(16, 'C-C3D4-E5F6', 1, 1, 8, 16, 'Farhan Ali', '03000000016', 'Street 16', 3.90, 1300.00, 'In Transit', '2026-01-03 19:00:00', '2026-01-04 19:00:00'),
(17, 'C-C4D5-E6F7', 1, 1, 9, 17, 'Shahid Khan', '03000000017', 'Street 17', 2.60, 1000.00, 'Delivered', '2026-01-05 19:00:00', '2026-01-07 19:00:00'),
(18, 'C-C5D6-E7F8', 1, 1, 10, 6, 'Salman Ali', '03000000018', 'Street 18', 4.30, 1500.00, 'Out For Delivery', '2026-01-07 19:00:00', '2026-01-08 19:00:00'),
(19, 'C-C6D7-E8F9', 1, 1, 11, 7, 'Tariq Khan', '03000000019', 'Street 19', 2.00, 800.00, 'Picked Up', '2026-01-09 19:00:00', '2026-01-09 19:00:00'),
(20, 'C-C7D8-E9F1', 1, 1, 12, 8, 'Yasir Ali', '03000000020', 'Street 20', 3.10, 1100.00, 'Delivered', '2026-01-11 19:00:00', '2026-01-14 19:00:00'),
(21, 'C-C8D9-E1F2', 1, 1, 13, 9, 'Junaid Khan', '03000000021', 'Street 21', 5.00, 1600.00, 'Picked Up', '2026-01-14 19:00:00', '2026-04-01 08:41:00'),
(22, 'C-C9D1-E2F3', 1, 1, 14, 10, 'Kashif Ali', '03000000022', 'Street 22', 2.70, 950.00, 'Delivered', '2026-01-17 19:00:00', '2026-04-01 13:45:20'),
(23, 'C-C1D3-E3F4', 1, 1, 15, 11, 'Naveed Khan', '03000000023', 'Street 23', 3.80, 1300.00, 'Delivered', '2026-01-19 19:00:00', '2026-01-22 19:00:00'),
(24, 'C-D1E2-F3G4', 1, 1, 6, 12, 'Adeel Ali', '03000000024', 'Street 24', 2.40, 900.00, 'Delivered', '2025-12-31 19:00:00', '2026-01-02 19:00:00'),
(25, 'C-D2E3-F4G5', 1, 1, 7, 13, 'Owais Khan', '03000000025', 'Street 25', 1.30, 600.00, 'Delivered', '2026-01-02 19:00:00', '2025-12-31 21:26:24'),
(27, 'C-D4E5-F6G7', 1, 1, 9, 15, 'Sameer Khan', '03000000027', 'Street 27', 2.10, 800.00, 'Picked Up', '2026-01-07 19:00:00', '2026-01-07 19:00:00'),
(28, 'C-D5E6-F7G8', 1, 1, 10, 16, 'Umer Ali', '03000000028', 'Street 28', 4.20, 1400.00, 'Out For Delivery', '2026-01-09 19:00:00', '2026-01-10 19:00:00'),
(29, 'C-D6E7-F8G9', 1, 1, 11, 17, 'Waqas Khan', '03000000029', 'Street 29', 3.00, 1100.00, 'Delivered', '2026-01-11 19:00:00', '2026-01-14 19:00:00'),
(30, 'C-E1F2-G3H4', 1, 1, 12, 6, 'Zubair Ali', '03000000030', 'Street 30', 2.20, 900.00, 'Delivered', '2026-01-31 19:00:00', '2026-02-02 19:00:00'),
(31, 'C-E2F3-G4H5', 1, 1, 13, 7, 'Irfan Khan', '03000000031', 'Street 31', 1.60, 700.00, 'Cancelled', '2026-02-01 19:00:00', '2025-12-31 21:25:03'),
(33, 'C-E4F5-G6H7', 1, 1, 15, 9, 'Rauf Khan', '03000000033', 'Street 33', 2.50, 1000.00, 'Delivered', '2026-02-05 19:00:00', '2026-02-07 19:00:00'),
(34, 'C-E5F6-G7H8', 1, 1, 16, 10, 'Sajid Ali', '03000000034', 'Street 34', 4.60, 1500.00, 'Delivered', '2026-02-07 19:00:00', '2025-12-31 21:24:53'),
(36, 'C-E7F8-G9H1', 1, 1, 6, 12, 'Usaid Ali', '03000000036', 'Street 36', 3.30, 1200.00, 'Delivered', '2026-02-11 19:00:00', '2026-02-14 19:00:00'),
(37, 'C-F1G2-H3I4', 1, 1, 7, 13, 'Waleed Khan', '03000000037', 'Street 37', 2.90, 1000.00, 'Delivered', '2026-02-28 19:00:00', '2026-03-02 19:00:00'),
(38, 'C-F2G3-H4I5', 1, 1, 8, 14, 'Yousuf Ali', '03000000038', 'Street 38', 1.40, 600.00, 'Cancelled', '2026-03-01 19:00:00', '2026-01-01 04:54:52'),
(39, 'C-F3G4-H5I6', 1, 1, 9, 15, 'Zeeshan Khan', '03000000039', 'Street 39', 3.80, 1300.00, 'Returned', '2026-03-03 19:00:00', '2026-01-01 04:54:40'),
(40, 'C-F4G5-H6I7', 1, 1, 10, 16, 'Ahsan Ali', '03000000040', 'Street 40', 2.30, 900.00, 'Delivered', '2026-03-05 19:00:00', '2026-03-07 19:00:00'),
(41, 'C-F5G6-H7I8', 1, 1, 11, 17, 'Basit Khan', '03000000041', 'Street 41', 4.40, 1500.00, 'Delivered', '2026-03-07 19:00:00', '2026-01-01 04:54:32'),
(42, 'C-F6G7-H8I9', 1, 1, 12, 6, 'Dawood Ali', '03000000042', 'Street 42', 2.60, 1100.00, 'Returned', '2026-03-09 19:00:00', '2026-01-01 04:43:13'),
(43, 'C-F7G8-H9I1', 1, 1, 13, 7, 'Ehsan Khan', '03000000043', 'Street 43', 3.50, 1200.00, 'Delivered', '2026-03-11 19:00:00', '2026-03-14 19:00:00'),
(44, 'C-F8G9-H1I2', 1, 1, 14, 8, 'Furqan Ali', '03000000044', 'Street 44', 2.70, 1000.00, 'Returned', '2026-03-14 19:00:00', '2026-03-17 19:00:00'),
(45, 'C-F9G1-H2I3', 1, 1, 15, 9, 'Gohar Khan', '03000000045', 'Street 45', 3.90, 1400.00, 'Returned', '2026-03-17 19:00:00', '2025-12-31 21:38:37');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_status_history`
--

CREATE TABLE `shipment_status_history` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `status` enum('Pending','Picked Up','In Transit','Out For Delivery','Delivered') NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `changed_by_role` enum('admin','agent') NOT NULL,
  `changed_by_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment_status_history`
--

INSERT INTO `shipment_status_history` (`id`, `shipment_id`, `status`, `location`, `remarks`, `changed_by_role`, `changed_by_id`, `created_at`) VALUES
(85, 1, 'Pending', NULL, 'Shipment Created by Admin', 'admin', 1, '2026-03-31 21:12:13'),
(87, 34, 'Delivered', '', '', 'admin', 1, '2026-03-31 21:24:53'),
(88, 31, '', '', '', 'admin', 1, '2026-03-31 21:25:03'),
(89, 25, 'Delivered', '', '', 'admin', 1, '2026-03-31 21:26:24'),
(90, 1, 'Delivered', '', '', 'admin', 1, '2026-03-31 21:27:05'),
(91, 45, '', '', '', 'agent', 1, '2026-03-31 21:38:37'),
(92, 42, 'In Transit', '', '', 'agent', 1, '2026-04-01 04:42:52'),
(93, 42, 'Out For Delivery', '', '', 'agent', 1, '2026-04-01 04:43:03'),
(94, 42, '', '', '', 'agent', 1, '2026-04-01 04:43:13'),
(95, 41, 'Delivered', '', '', 'admin', 1, '2026-04-01 04:54:32'),
(96, 39, '', '', '', 'admin', 1, '2026-04-01 04:54:40'),
(97, 38, '', '', '', 'admin', 1, '2026-04-01 04:54:52'),
(98, 2, 'Delivered', '', '', 'admin', 1, '2026-04-01 08:21:27'),
(104, 21, 'Picked Up', '', '', 'agent', 1, '2026-04-01 08:41:00'),
(121, 5, 'In Transit', '', '', 'admin', 1, '2026-04-01 13:45:05'),
(122, 22, 'Delivered', '', '', 'admin', 1, '2026-04-01 13:45:20'),
(134, 9, 'Picked Up', '', '', 'admin', 1, '2026-04-01 18:55:31'),
(135, 15, 'Delivered', '', '', 'admin', 1, '2026-04-01 19:01:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `blocked_emails`
--
ALTER TABLE `blocked_emails`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_requests`
--
ALTER TABLE `company_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_type_id` (`user_type`,`user_id`);

--
-- Indexes for table `revenue`
--
ALTER TABLE `revenue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `transaction_date` (`transaction_date`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `origin_city_id` (`origin_city_id`),
  ADD KEY `destination_city_id` (`destination_city_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blocked_emails`
--
ALTER TABLE `blocked_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `company_requests`
--
ALTER TABLE `company_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revenue`
--
ALTER TABLE `revenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `revenue`
--
ALTER TABLE `revenue`
  ADD CONSTRAINT `fk_revenue_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_revenue_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipments_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shipments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shipments_dest_city` FOREIGN KEY (`destination_city_id`) REFERENCES `cities` (`id`),
  ADD CONSTRAINT `fk_shipments_orig_city` FOREIGN KEY (`origin_city_id`) REFERENCES `cities` (`id`);

--
-- Constraints for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  ADD CONSTRAINT `fk_history_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
