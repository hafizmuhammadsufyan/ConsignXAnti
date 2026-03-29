-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 09:17 PM
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

CREATE DATABASE IF NOT EXISTS `consignx_database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `consignx_database`;

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
(1, 'Administration', 'admin@consignx.com', '$2y$10$tF9Kkd/JBGbwbzAiVG6Gfe2J47.1d6n8jL8nqsrnm3PH8JgEuSr/a', '2026-03-08 16:17:59', NULL);

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
(3, 'sufyan amir', 'Sufyan Exports 2', NULL, 'sufyanamir810+1@gmail.com', '1234567', '$2y$10$huYi6SbbhDeWRvGXrp4R0eXK4.yHcNlDjlhjO5Z2zcCipoFLDVb/C', 'active', '2026-03-13 15:14:34', NULL),
(4, 'Sameer', 'Sufi Ships', NULL, 'sufyanamir810+2@gmail.com', '1234567', '$2y$10$CWjwzzSM6bCerqwFXqZAGeBagM3U6auAIvGF/bMJuDanGcssXVucO', 'active', '2026-03-14 14:52:50', NULL),
(5, 'ConsignX Logistics', 'ConsignX Logistics', 'agent', 'agent@consignx.com', '030000000111', '$2a$12$mxSmLaLYb/07HWtMOmJHtuIdinHIAE0egOiF5oa2coytFP4dYZqE6', 'active', '2026-03-15 10:39:57', 'profile_69c57da20b09a.png'),
(7, 'Tester', 'Testing Agent', NULL, 'sufyanamir810+5@gmail.com', '030002345', '$2y$10$yI22Stjuo3DzNTGAoHqnqOl3Oz3tXBevJFBPgMEOOLNyMUjCU5x6O', 'active', '2026-03-26 18:15:58', NULL),
(9, 'sufyan amir', 'Sufyan Exports', NULL, 'sufyanamir810+6@gmail.com', '1234567', '$2y$10$NGpXaSpWDfb6z0W0QjGlZuvf.3o/zPUeHDaPqitEKheOWh8MuBcwW', 'active', '2026-03-29 09:23:17', NULL),
(12, 'sufyan amir', 'Testing Agent', NULL, 'sufyanamir810+7@gmail.com', '0345789076', '$2y$10$9oQUeijet2YM9B6536MvXOQvN15IxG.BI.XbcrrWwcCA9ZRi2EU1i', 'active', '2026-03-29 14:01:27', NULL);

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

--
-- Dumping data for table `company_requests`
--

INSERT INTO `company_requests` (`id`, `company_name`, `name`, `email`, `phone`, `status`, `created_at`) VALUES
(1, 'Sufyan Exports 2', 'sufyan amir', 'sufyanamir810+1@gmail.com', '1234567', 'approved', '2026-03-13 15:14:05'),
(2, 'Sufi Ships', 'sufyan amir', 'sufyanamir810+2@gmail.com', '1234567', 'approved', '2026-03-14 14:51:41'),
(3, 'xyz', 'sufyan amir', 'sufyanamir810+4@gmail.com', '1234567', 'approved', '2026-03-24 18:47:31'),
(4, 'Sufyan Exports', 'sufyan amir', 'sufyanamir810+6@gmail.com', '1234567', 'approved', '2026-03-29 09:04:13'),
(5, 'Testing Agent', 'sufyan amir', 'sufyanamir810+7@gmail.com', '1234567', 'approved', '2026-03-29 13:51:50'),
(6, 'Testing Company', 'Testing Agent', 'sufyanamir810+8@gmail.com', '0310200000', 'pending', '2026-03-29 19:03:37');

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
(1, 'Alice', 'customer@consignx.com', '0345678910', '$2a$12$zvUv/./p4x1KumrtBYDiu.1xbEB7lOJr9CzbdoeQagjCgWyIsGI5u', '2026-03-08 16:18:00', NULL),
(2, 'sufyan amir', 'sufyanamir810@gmail.com', '1234567', '$2a$12$zvUv/./p4x1KumrtBYDiu.1xbEB7lOJr9CzbdoeQagjCgWyIsGI5u', '2026-03-08 17:09:13', NULL),
(3, 'sufyan amir ali', 'sufyanamir810+1@gmail.com', '1234567', '$2y$10$eIES6mostghbapeTRlA4bOCNJayzg806ishVMjHyjtQI.FVwxqHv6', '2026-03-13 14:13:00', NULL),
(4, 'Sufi', 'sufyanamir810+2@gmail.com', '1234567', '$2y$10$FBRliXnZz8j4PzJRa.DCuu8q1UjrB6J4g7LqxTM8X9oV73KrruJsa', '2026-03-14 14:45:47', NULL),
(5, 'sufyan amir', 'sufyanamir810+4@gmail.com', '1234567', '$2y$10$4OLEZZjE6YRpKBJdSRLMHe65gWAsIaQ1RCI37rENGeHfzbphfcD56', '2026-03-15 11:31:32', NULL),
(6, 'sufyan amir', 'customer@consigx.com', '1234567', '$2y$10$aFZfW/z5fQx5AI0y/ehs.u2tP25Imxe97YcmbLh2DJry3hf/Y97gi', '2026-03-24 19:31:48', NULL);

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
(4, 8, 5, 360.00, '2026-03-15', '2026-03-15 10:55:40'),
(7, 13, NULL, 3453.00, '2025-10-21', '2026-03-15 11:25:07'),
(8, 14, 3, 3886.00, '2025-10-24', '2026-03-15 11:25:07'),
(9, 15, 5, 2777.00, '2025-10-03', '2026-03-15 11:25:07'),
(10, 16, 3, 3036.00, '2025-10-25', '2026-03-15 11:25:07'),
(11, 17, NULL, 4649.00, '2025-10-26', '2026-03-15 11:25:07'),
(12, 18, 5, 2930.00, '2025-10-01', '2026-03-15 11:25:07'),
(13, 19, 5, 3348.00, '2025-10-12', '2026-03-15 11:25:07'),
(14, 20, NULL, 5764.00, '2025-10-18', '2026-03-15 11:25:07'),
(15, 21, NULL, 5062.00, '2025-10-10', '2026-03-15 11:25:07'),
(16, 22, 4, 2498.00, '2025-11-10', '2026-03-15 11:25:07'),
(17, 23, 3, 3539.00, '2025-11-12', '2026-03-15 11:25:07'),
(19, 25, 3, 3523.00, '2025-11-12', '2026-03-15 11:25:07'),
(20, 26, 5, 3708.00, '2025-11-26', '2026-03-15 11:25:07'),
(22, 28, 4, 4810.00, '2025-11-24', '2026-03-15 11:25:07'),
(23, 29, NULL, 3082.00, '2025-11-07', '2026-03-15 11:25:07'),
(24, 30, NULL, 4834.00, '2025-11-25', '2026-03-15 11:25:07'),
(26, 32, 5, 4146.00, '2025-11-04', '2026-03-15 11:25:07'),
(27, 33, 3, 5901.00, '2025-11-24', '2026-03-15 11:25:07'),
(28, 34, 4, 5413.00, '2025-11-08', '2026-03-15 11:25:07'),
(29, 35, 3, 4506.00, '2025-12-26', '2026-03-15 11:25:07'),
(30, 36, 3, 3818.00, '2025-12-13', '2026-03-15 11:25:07'),
(32, 38, 3, 3555.00, '2025-12-18', '2026-03-15 11:25:07'),
(35, 41, 3, 1455.00, '2025-12-07', '2026-03-15 11:25:07'),
(36, 42, 3, 2665.00, '2025-12-23', '2026-03-15 11:25:07'),
(37, 43, 3, 5722.00, '2025-12-06', '2026-03-15 11:25:07'),
(44, 50, NULL, 2226.00, '2026-01-26', '2026-03-15 11:25:07'),
(47, 53, 3, 5280.00, '2026-01-08', '2026-03-15 11:25:07'),
(48, 54, 4, 4363.00, '2026-01-01', '2026-03-15 11:25:07'),
(49, 55, 3, 2894.00, '2026-01-25', '2026-03-15 11:25:07'),
(51, 57, NULL, 2438.00, '2026-01-21', '2026-03-15 11:25:07'),
(53, 59, 4, 1047.00, '2026-01-25', '2026-03-15 11:25:07'),
(57, 63, 4, 2361.00, '2026-01-12', '2026-03-15 11:25:07'),
(59, 65, NULL, 2351.00, '2026-01-24', '2026-03-15 11:25:07'),
(62, 68, 3, 1088.00, '2026-02-13', '2026-03-15 11:25:07'),
(63, 69, 4, 3634.00, '2026-02-12', '2026-03-15 11:25:07'),
(65, 71, 3, 1893.00, '2026-02-12', '2026-03-15 11:25:07'),
(69, 75, 4, 5908.00, '2026-02-12', '2026-03-15 11:25:07'),
(73, 79, 5, 5791.00, '2026-02-16', '2026-03-15 11:25:07'),
(74, 80, 3, 1082.00, '2026-02-22', '2026-03-15 11:25:07'),
(77, 83, 4, 4646.00, '2026-02-10', '2026-03-15 11:25:07'),
(78, 84, NULL, 4344.00, '2026-02-28', '2026-03-15 11:25:07'),
(80, 86, 4, 2181.00, '2026-02-13', '2026-03-15 11:25:07'),
(88, 94, NULL, 4601.00, '2026-03-19', '2026-03-15 11:25:07'),
(91, 7, NULL, 20.00, '2026-03-15', '2026-03-15 11:34:55'),
(92, 98, NULL, 800.90, '2026-03-15', '2026-03-15 11:51:30'),
(93, 97, NULL, 863.61, '2026-03-15', '2026-03-15 11:51:38'),
(94, 6, 3, 440.00, '2026-03-20', '2026-03-20 11:22:04'),
(95, 99, 5, 440.00, '2026-03-25', '2026-03-25 16:33:10'),
(96, 103, NULL, 847.25, '2026-03-26', '2026-03-26 17:40:39');

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
(6, 'C-8A1D-0D16', 3, 4, 12, 8, 'Huzaifa', '1234567', 'Sialkoat', 3.00, 440.00, 'Delivered', '2026-03-14 14:46:02', '2026-03-20 11:22:04'),
(7, 'C-9F3E-3792', NULL, 2, 7, 6, 'sufyan amir', '1234567', 'Sialkoat', 1.00, 20.00, 'Delivered', '2026-03-14 14:54:26', '2026-03-15 11:34:55'),
(8, 'C-93BD-A076', 5, 2, 11, 6, 'sufyan amir', '1234567', 'Sialkoat', 2.00, 360.00, 'Delivered', '2026-03-15 10:50:58', '2026-03-15 10:55:40'),
(13, 'CX-626255', NULL, 2, 9, 15, '', '', '', 11.50, 3453.00, 'Picked Up', '2025-10-21 08:17:17', '2026-03-15 11:25:07'),
(14, 'CX-324882', 3, 2, 6, 15, '', '', '', 13.50, 3886.00, 'Delivered', '2025-10-24 10:42:23', '2026-03-15 11:25:07'),
(15, 'CX-675306', 5, 1, 16, 12, '', '', '', 24.50, 2777.00, 'Delivered', '2025-10-03 14:00:17', '2026-03-26 17:56:36'),
(16, 'CX-875761', 3, 4, 9, 6, '', '', '', 20.00, 3036.00, 'Pending', '2025-10-25 13:44:16', '2026-03-15 11:25:07'),
(17, 'CX-763580', NULL, 3, 10, 6, '', '', '', 9.50, 4649.00, 'In Transit', '2025-10-26 03:51:37', '2026-03-15 11:25:07'),
(18, 'CX-836353', 5, 2, 12, 10, '', '', '', 15.00, 2930.00, 'Delivered', '2025-10-01 10:53:53', '2026-03-15 11:42:22'),
(19, 'CX-905411', 5, 3, 13, 8, '', '', '', 24.50, 3348.00, 'Delivered', '2025-10-12 11:54:25', '2026-03-26 16:39:30'),
(20, 'CX-211744', NULL, 3, 9, 13, '', '', '', 9.00, 5764.00, 'Picked Up', '2025-10-18 12:43:57', '2026-03-15 11:25:07'),
(21, 'CX-756997', NULL, 1, 12, 6, '', '', '', 15.50, 5062.00, 'Delivered', '2025-10-10 04:14:12', '2026-03-15 11:25:07'),
(22, 'CX-302505', 4, 1, 6, 13, '', '', '', 3.50, 2498.00, 'Out For Delivery', '2025-11-10 05:05:30', '2026-03-15 11:25:07'),
(23, 'CX-966024', 3, 3, 8, 7, '', '', '', 19.50, 3539.00, 'In Transit', '2025-11-12 04:57:26', '2026-03-15 11:25:07'),
(25, 'CX-764305', 3, 2, 12, 13, '', '', '', 24.50, 3523.00, 'Delivered', '2025-11-12 10:15:47', '2026-03-15 11:25:07'),
(26, 'CX-178109', 5, 3, 6, 8, '', '', '', 13.00, 3708.00, 'Delivered', '2025-11-26 13:59:33', '2026-03-26 17:56:16'),
(28, 'CX-272185', 4, 2, 8, 6, '', '', '', 1.50, 4810.00, 'In Transit', '2025-11-24 12:56:53', '2026-03-15 11:25:07'),
(29, 'CX-596375', NULL, 1, 6, 9, '', '', '', 14.50, 3082.00, 'In Transit', '2025-11-07 07:38:40', '2026-03-15 11:25:07'),
(30, 'CX-458002', NULL, 4, 15, 17, '', '', '', 16.50, 4834.00, 'Picked Up', '2025-11-25 15:33:10', '2026-03-15 11:25:07'),
(32, 'CX-682882', 5, 3, 10, 7, '', '', '', 23.00, 4146.00, 'Delivered', '2025-11-04 14:12:05', '2026-03-26 17:56:27'),
(33, 'CX-631521', 3, 3, 16, 15, '', '', '', 12.50, 5901.00, 'Pending', '2025-11-24 10:08:35', '2026-03-15 11:25:07'),
(34, 'CX-515014', 4, 3, 16, 6, '', '', '', 3.00, 5413.00, 'Out For Delivery', '2025-11-08 05:04:01', '2026-03-15 11:25:07'),
(35, 'CX-287387', 3, 4, 15, 7, '', '', '', 3.50, 4506.00, 'Delivered', '2025-12-26 06:20:41', '2026-03-26 18:11:43'),
(36, 'CX-554093', 3, 4, 9, 10, '', '', '', 11.00, 3818.00, 'Out For Delivery', '2025-12-13 10:24:56', '2026-03-15 11:25:07'),
(38, 'CX-825986', 3, 2, 14, 17, '', '', '', 2.50, 3555.00, 'In Transit', '2025-12-18 06:48:40', '2026-03-15 11:25:07'),
(41, 'CX-620990', 3, 2, 13, 11, '', '', '', 17.00, 1455.00, 'Picked Up', '2025-12-07 10:57:29', '2026-03-15 11:25:07'),
(42, 'CX-316861', 3, 4, 13, 6, '', '', '', 12.50, 2665.00, 'Out For Delivery', '2025-12-23 14:40:14', '2026-03-15 11:25:07'),
(43, 'CX-892930', 3, 2, 17, 13, '', '', '', 12.50, 5722.00, 'Picked Up', '2025-12-06 13:06:57', '2026-03-15 11:25:07'),
(50, 'CX-222365', NULL, 4, 12, 16, '', '', '', 17.00, 2226.00, 'Picked Up', '2026-01-26 10:11:14', '2026-03-15 11:25:07'),
(53, 'CX-914323', 3, 4, 8, 16, '', '', '', 16.50, 5280.00, 'Delivered', '2026-01-08 08:24:30', '2026-03-15 11:25:07'),
(54, 'CX-231679', 4, 4, 15, 7, '', '', '', 14.00, 4363.00, 'Pending', '2026-01-01 09:01:25', '2026-03-15 11:25:07'),
(55, 'CX-688836', 3, 4, 6, 10, '', '', '', 17.50, 2894.00, 'Out For Delivery', '2026-01-25 09:38:11', '2026-03-15 11:25:07'),
(57, 'CX-286574', NULL, 4, 9, 12, '', '', '', 22.50, 2438.00, 'Pending', '2026-01-21 04:45:05', '2026-03-15 11:25:07'),
(59, 'CX-345912', 4, 2, 15, 6, '', '', '', 5.50, 1047.00, 'In Transit', '2026-01-25 04:11:41', '2026-03-15 11:25:07'),
(63, 'CX-329348', 4, 4, 17, 13, '', '', '', 25.00, 2361.00, 'In Transit', '2026-01-12 10:08:17', '2026-03-15 11:25:07'),
(65, 'CX-134844', NULL, 1, 11, 10, '', '', '', 19.00, 2351.00, 'In Transit', '2026-01-24 04:44:19', '2026-03-15 11:25:07'),
(68, 'CX-255637', 3, 1, 9, 10, '', '', '', 24.00, 1088.00, 'Out For Delivery', '2026-02-13 12:49:23', '2026-03-29 08:56:29'),
(69, 'CX-254656', 4, 1, 9, 7, '', '', '', 16.50, 3634.00, 'Out For Delivery', '2026-02-12 08:19:00', '2026-03-15 11:25:07'),
(71, 'CX-903465', 3, 1, 8, 17, '', '', '', 14.50, 1893.00, 'In Transit', '2026-02-12 03:44:18', '2026-03-15 11:25:07'),
(75, 'CX-885209', 4, 2, 15, 14, '', '', '', 15.50, 5908.00, 'Picked Up', '2026-02-12 04:53:30', '2026-03-15 11:25:07'),
(79, 'CX-311441', 5, 4, 17, 10, '', '', '', 8.00, 5791.00, 'Delivered', '2026-02-16 04:47:48', '2026-03-26 16:52:45'),
(80, 'CX-343079', 3, 4, 7, 10, '', '', '', 23.00, 1082.00, 'In Transit', '2026-02-22 04:35:07', '2026-03-15 11:25:07'),
(83, 'CX-882331', 4, 4, 13, 14, '', '', '', 18.50, 4646.00, 'Out For Delivery', '2026-02-10 03:39:49', '2026-03-15 11:25:07'),
(84, 'CX-521687', NULL, 3, 14, 15, '', '', '', 17.50, 4344.00, 'Returned', '2026-02-28 15:10:13', '2026-03-26 17:18:03'),
(86, 'CX-777984', 4, 3, 16, 17, '', '', '', 11.50, 2181.00, 'Delivered', '2026-02-13 08:14:53', '2026-03-29 07:38:22'),
(94, 'CX-307727', NULL, 2, 6, 10, '', '', '', 0.50, 4601.00, 'Delivered', '2026-03-19 12:44:25', '2026-03-15 11:34:01'),
(97, 'C-C9BD-EAC1', NULL, 5, 12, 6, 'sufyan amir', '1234567', 'Sialkoat', 2.00, 863.61, 'Delivered', '2026-03-15 11:31:35', '2026-03-15 11:51:38'),
(98, 'C-4D13-4D66', NULL, 2, 6, 8, 'sufyan amir', '1234567', 'Sialkoat', 1.00, 800.90, 'Delivered', '2026-03-15 11:50:00', '2026-03-15 11:51:30'),
(99, 'C-267B-CD75', 5, 6, 7, 6, 'sufyan amir', '1234567', 'Sialkoat', 3.00, 440.00, 'Delivered', '2026-03-24 19:31:54', '2026-03-25 16:33:10'),
(102, 'C-EA65-E9B7', NULL, 2, 14, 7, 'sufyan amir', '1234567', 'Sialkoat', 1.00, 678.80, 'Returned', '2026-03-25 20:29:02', '2026-03-26 17:03:46'),
(103, 'C-17F6-FF6A', NULL, 2, 16, 6, 'sufyan amir', '1234567', 'Sialkoat', 2.00, 847.25, 'Delivered', '2026-03-26 16:35:41', '2026-03-26 17:40:39');

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
(14, 6, 'Pending', NULL, 'Shipment Created by Agent', 'agent', 3, '2026-03-14 14:46:02'),
(15, 6, 'Picked Up', '', '', 'agent', 3, '2026-03-14 14:49:31'),
(16, 7, 'Pending', NULL, 'Shipment Created by Admin', 'admin', 1, '2026-03-14 14:54:26'),
(17, 8, 'Pending', NULL, 'Shipment Created by Agent', 'agent', 5, '2026-03-15 10:50:58'),
(18, 8, 'Delivered', '', '', 'agent', 5, '2026-03-15 10:55:40'),
(20, 97, 'Pending', NULL, 'Shipment Created by Admin', 'admin', 1, '2026-03-15 11:31:35'),
(22, 94, 'Out For Delivery', '', '', 'admin', 1, '2026-03-15 11:33:54'),
(23, 94, 'Delivered', '', '', 'admin', 1, '2026-03-15 11:34:01'),
(24, 97, 'Out For Delivery', '', '', 'admin', 1, '2026-03-15 11:34:12'),
(26, 7, 'Delivered', '', '', 'admin', 1, '2026-03-15 11:34:55'),
(27, 18, 'Delivered', '', '', 'admin', 1, '2026-03-15 11:42:22'),
(28, 98, 'Pending', NULL, 'Shipment Created by Admin', 'admin', 1, '2026-03-15 11:50:00'),
(29, 98, 'Delivered', '', '', 'admin', 1, '2026-03-15 11:51:30'),
(30, 97, 'Delivered', '', '', 'admin', 1, '2026-03-15 11:51:38'),
(31, 6, 'Delivered', '', '', 'admin', 1, '2026-03-20 11:22:04'),
(36, 99, 'Pending', NULL, 'Shipment Created by Agent', 'agent', 5, '2026-03-24 19:31:54'),
(37, 99, 'Out For Delivery', '', '', 'admin', 1, '2026-03-25 16:18:31'),
(38, 99, 'Delivered', '', '', 'admin', 1, '2026-03-25 16:33:10'),
(46, 102, 'Pending', NULL, 'Shipment Created by Admin', 'admin', 1, '2026-03-25 20:29:02'),
(53, 103, 'Pending', NULL, 'Shipment Created by Admin', 'admin', 1, '2026-03-26 16:35:41'),
(57, 19, 'Delivered', '', '', 'agent', 5, '2026-03-26 16:39:30'),
(61, 79, 'Delivered', '', '', 'agent', 5, '2026-03-26 16:52:45'),
(64, 102, '', '', '', 'admin', 1, '2026-03-26 17:03:46'),
(65, 84, '', '', '', 'admin', 1, '2026-03-26 17:18:03'),
(66, 103, 'Picked Up', '', '', 'admin', 1, '2026-03-26 17:40:20'),
(67, 103, 'Out For Delivery', '', '', 'admin', 1, '2026-03-26 17:40:32'),
(68, 103, 'Delivered', '', '', 'admin', 1, '2026-03-26 17:40:39'),
(69, 26, 'Delivered', 'Arrived to destination', '', 'agent', 5, '2026-03-26 17:56:16'),
(70, 32, 'Delivered', '', '', 'agent', 5, '2026-03-26 17:56:27'),
(71, 15, 'Delivered', '', '', 'agent', 5, '2026-03-26 17:56:36'),
(73, 35, 'Delivered', '', '', 'admin', 1, '2026-03-26 18:11:43'),
(74, 86, 'Delivered', '', '', 'admin', 1, '2026-03-29 07:38:22'),
(75, 68, 'Out For Delivery', '', '', 'admin', 1, '2026-03-29 08:56:29');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `company_requests`
--
ALTER TABLE `company_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revenue`
--
ALTER TABLE `revenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `shipment_status_history`
--
ALTER TABLE `shipment_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
