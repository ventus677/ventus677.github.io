-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2025 at 05:56 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_2fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_code` varchar(6) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT 'iconUser.png',
  `security_question_type` varchar(50) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `password`, `is_2fa_enabled`, `two_factor_code`, `phone_number`, `address`, `created_at`, `updated_at`, `profile_picture`, `security_question_type`, `security_answer`) VALUES
(6, 'JOHN PAUL', 'MORALES', 'markedselmorales09@yahoo.com', '$2y$10$rolNk6B8xNoIxk0w2hGl8.qPaBjuVzftk0RkQQWhHjhvcdne4B3RW', 1, NULL, 'null', '218548545', '2025-10-12 11:35:51', '2025-11-29 02:35:31', 'images/default-profile.png', NULL, NULL),
(7, 'Mark Edsel', 'Morales', 'markedselmorales22@yahoo.com', '$2y$10$aLHMp76vaDW5el5OaD8uk.QJe9WTNGhjeJkGWSrx/03Ettad7kHMu', 1, NULL, '09331411265', '15456', '2025-10-12 12:06:32', '2025-12-03 06:46:38', 'images/default-profile.png', NULL, NULL),
(8, 'Mark Edsel', 'MORALES', 'markedselmorales777@gmail.com', '$2y$10$e1xGhNC9uTEhyHCPzQcRLOkGg9AK0/O/LoRgxvpMcD93uMrZf6u.e', 1, NULL, '09331411265', 'binan laguna', '2025-10-12 13:45:15', '2025-12-16 12:36:15', '692d6125d917a_8.png', NULL, NULL),
(9, 'Mark Edsel', 'MORALES', 'markedselmorales7777@gmail.com', '$2y$10$e3R9.dptIE2X7fQ6g82.lurQCcFAnJdg4qFrkwcwELjdFV4R8jy5C', 1, NULL, 'sdad', 'sadas', '2025-10-15 10:01:37', '2025-11-29 02:35:31', 'images/default-profile.png', NULL, NULL),
(10, 'Mark Edsel', 'MORALES', 'markedsel.morales@cvsu.edu.ph', '$2y$10$FAgOylPkdUxW9JBbsqGKwutzuvZKK6tgN1AhTdZyr8vl0UUuGgy22', 1, NULL, '09331411265', '09331411265', '2025-10-15 10:48:13', '2025-12-18 00:40:57', 'iconUser.png', NULL, NULL),
(11, 'JOHN PAUL', 'MORALES', 'joshuamiguel0922@gmail.com', '$2y$10$WYg4kCcVtqeqvW2iOWcOUOnPCT4e4T.EltG2GF.f35/on4DOgh0JK', 0, NULL, '09331411265', 'morales', '2025-12-01 10:04:27', '2025-12-01 10:04:27', 'iconUser.png', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers_orders`
--

CREATE TABLE `customers_orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `shipping_address` text NOT NULL,
  `order_status` enum('Pending','Processing','Shipped','Received','Cancelled') NOT NULL DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers_order_items`
--

CREATE TABLE `customers_order_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_cart`
--

CREATE TABLE `customer_cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `date_added` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_cart`
--

INSERT INTO `customer_cart` (`id`, `customer_id`, `product_id`, `quantity`, `date_added`) VALUES
(55, 6, 349, 1, '2025-10-12 19:36:07'),
(56, 6, 378, 1, '2025-10-12 19:36:10'),
(215, 10, 379, 4, '2025-12-02 17:35:38'),
(217, 10, 338, 2, '2025-12-02 17:59:29'),
(246, 8, 379, 3, '2025-12-12 23:23:38'),
(248, 8, 325, 3, '2025-12-12 23:56:48'),
(249, 8, 338, 1, '2025-12-13 12:58:19'),
(250, 8, 353, 1, '2025-12-13 16:11:31'),
(251, 8, 412, 1, '2025-12-16 20:36:44'),
(252, 8, 415, 1, '2025-12-16 20:36:54'),
(253, 8, 411, 1, '2025-12-16 20:36:56'),
(254, 10, 411, 1, '2025-12-16 20:37:36');

-- --------------------------------------------------------

--
-- Table structure for table `customer_password_reset_otps`
--

CREATE TABLE `customer_password_reset_otps` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_password_reset_otps`
--

INSERT INTO `customer_password_reset_otps` (`id`, `email`, `otp_code`, `created_at`) VALUES
(1, 'markedselmorales22@yahoo.com', '975576', '2025-10-12 15:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash on Delivery',
  `return_refund_status` enum('None','Requested','Approved','Rejected','Processing','Refunded') NOT NULL DEFAULT 'None'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `order_date`, `total_amount`, `payment_method`, `return_refund_status`) VALUES
(1, 'christian', '2025-06-18 20:48:27', 850.00, 'Cash on Delivery', 'None'),
(2, 'gkiholi', '2025-06-19 04:11:36', 480.00, 'Cash on Delivery', 'None'),
(3, 'christian', '2025-06-19 07:00:43', 1280.00, 'Cash on Delivery', 'None'),
(4, 'christian', '2025-06-19 07:00:57', 1280.00, 'Cash on Delivery', 'None'),
(5, 's', '2025-06-19 07:14:17', 1280.00, 'Cash on Delivery', 'None'),
(6, 'christian213', '2025-06-19 08:11:50', 1280.00, 'Cash on Delivery', 'None'),
(7, 'morakes', '2025-06-19 08:14:26', 1280.00, 'Cash on Delivery', 'None'),
(8, 'swe', '2025-06-19 08:22:56', 1280.00, 'Cash on Delivery', 'None'),
(9, 'christian213eqweqwe', '2025-06-19 08:23:27', 1050.00, 'Cash on Delivery', 'None'),
(10, 'christian', '2025-06-19 09:27:54', 1280.00, 'Cash on Delivery', 'None'),
(11, 'christian', '2025-06-19 09:28:12', 1280.00, 'Cash on Delivery', 'None');

-- --------------------------------------------------------

--
-- Table structure for table `orders_customer`
--

CREATE TABLE `orders_customer` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders_customer`
--

INSERT INTO `orders_customer` (`id`, `customer_id`, `order_date`, `total_amount`, `status`, `shipping_address`, `payment_method`) VALUES
(31, 8, '2025-11-20 01:10:11', 595.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(32, 8, '2025-11-20 01:14:23', 2300.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(33, 8, '2025-11-20 02:42:33', 890.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(34, 8, '2025-11-20 02:46:32', 690.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(35, 8, '2025-11-20 03:50:48', 690.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(36, 8, '2025-11-20 04:07:26', 1840.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(37, 8, '2025-11-20 04:16:01', 690.00, 'completed', 'dasdasd', 'GCash'),
(38, 8, '2025-11-20 05:15:17', 690.00, 'completed', 'sdasd', 'Cash on Delivery'),
(39, 8, '2025-11-20 05:44:36', 690.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(40, 8, '2025-11-20 06:03:49', 690.00, 'completed', 'Default Shipping Address', 'Cash on Delivery'),
(41, 8, '2025-11-20 06:05:19', 690.00, 'completed', 'binan laguna', 'GCash'),
(42, 8, '2025-11-20 06:18:22', 690.00, 'completed', 'binan laguna', 'Credit/Debit Card'),
(43, 8, '2025-11-29 01:34:44', 2660.00, 'completed', 'binan laguna', 'Cash on Delivery'),
(44, 8, '2025-11-29 01:41:32', 2300.00, 'completed', 'binan laguna', 'Cash on Delivery'),
(45, 8, '2025-11-29 01:53:36', 690.00, 'completed', 'binan laguna', 'Cash on Delivery'),
(46, 8, '2025-11-29 01:59:11', 595.00, 'cancelled', 'binan laguna', 'Cash on Delivery'),
(47, 8, '2025-11-29 02:22:08', 2960.00, 'completed', 'binan laguna', 'Cash on Delivery'),
(48, 8, '2025-11-29 02:23:55', 690.00, 'pending', 'binan laguna', 'Cash on Delivery'),
(49, 8, '2025-11-29 02:24:09', 690.00, 'pending', 'binan laguna', 'Cash on Delivery'),
(50, 8, '2025-11-29 02:24:13', 1150.00, 'pending', 'binan laguna', 'Cash on Delivery'),
(51, 8, '2025-11-29 02:24:17', 595.00, 'cancelled', 'binan laguna', 'Cash on Delivery'),
(52, 8, '2025-12-01 07:18:52', 3380.00, 'completed', 'binan laguna', 'Cash on Delivery'),
(53, 8, '2025-12-01 08:44:19', 4140.00, 'pending', 'binan laguna', 'Cash on Delivery'),
(54, 8, '2025-12-01 08:44:40', 890.00, 'pending', 'binan laguna', 'Cash on Delivery'),
(55, 8, '2025-12-01 08:56:42', 890.00, 'pending', 'binan laguna', 'Cash on Delivery (COD)'),
(56, 8, '2025-12-01 08:57:29', 4830.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(57, 8, '2025-12-02 10:15:39', 590.00, 'pending', 'binan laguna', 'Cash on Delivery'),
(58, 8, '2025-12-02 10:15:54', 1150.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(59, 8, '2025-12-02 10:18:12', 230.00, 'pending', 'binan laguna', 'Bank Transfer'),
(60, 8, '2025-12-02 10:18:30', 890.00, 'pending', 'binan laguna', 'PayMaya'),
(61, 8, '2025-12-02 10:19:44', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(62, 8, '2025-12-02 10:27:49', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(63, 8, '2025-12-02 10:31:10', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(64, 8, '2025-12-02 10:51:26', 1150.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(65, 8, '2025-12-02 11:04:05', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(66, 8, '2025-12-02 11:08:36', 850.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(67, 8, '2025-12-03 01:24:38', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(68, 8, '2025-12-03 01:36:38', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(69, 8, '2025-12-04 01:41:25', 1150.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(70, 8, '2025-12-04 04:15:44', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(71, 8, '2025-12-04 04:46:28', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(72, 8, '2025-12-04 04:46:53', 1780.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(73, 8, '2025-12-04 06:13:26', 4800.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(74, 8, '2025-12-06 13:56:27', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(75, 8, '2025-12-11 03:09:43', 890.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(76, 8, '2025-12-11 03:10:46', 2940.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(77, 8, '2025-12-11 03:11:12', 2870.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(78, 8, '2025-12-11 04:48:28', 690.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(79, 8, '2025-12-11 05:41:59', 2550.00, 'completed', 'binan laguna', 'Cash on Delivery (COD)'),
(80, 8, '2025-12-12 16:26:11', 670.00, 'pending', 'binan laguna', 'Cash on Delivery (COD)'),
(81, 8, '2025-12-13 08:25:13', 9450.00, 'pending', 'binan laguna', 'Cash on Delivery (COD)');

-- --------------------------------------------------------

--
-- Table structure for table `orders_user`
--

CREATE TABLE `orders_user` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_reason` varchar(255) DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders_user`
--

INSERT INTO `orders_user` (`id`, `user_id`, `order_date`, `total_amount`, `status`, `discount_amount`, `discount_reason`, `received_date`, `shipping_address`, `payment_method`) VALUES
(1, 43, '2025-12-12 17:12:08', 595.00, 'completed', 0.00, NULL, NULL, NULL, 'Cash on Delivery (COD)'),
(2, 43, '2025-12-12 17:49:39', 1150.00, 'completed', 0.00, NULL, '2025-12-13 01:57:00', NULL, 'Cash on Delivery (COD)'),
(3, 43, '2025-12-12 18:07:23', 850.00, 'completed', 0.00, NULL, '2025-12-13 02:07:27', NULL, 'Cash on Delivery (COD)'),
(4, 43, '2025-12-12 18:22:02', 2270.00, 'pending', 0.00, NULL, NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(5, 43, '2025-12-12 18:29:42', 1050.00, 'pending', 0.00, NULL, NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(6, 43, '2025-12-12 18:58:08', 1150.00, 'pending', 0.00, NULL, NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(7, 43, '2025-12-12 18:58:31', 1050.00, 'pending', 0.00, NULL, NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(8, 43, '2025-12-12 19:13:07', 1424.00, 'pending', 356.00, 'Employee discount (20%)', NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(9, 43, '2025-12-12 19:13:47', 680.00, 'pending', 170.00, 'Employee discount (20%)', NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(10, 50, '2025-12-18 03:00:26', 639.20, 'pending', 159.80, 'Logged-in Customer Discount (20%)', NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(11, 43, '2025-12-18 03:00:29', 840.00, 'pending', 210.00, 'Employee discount (20%)', NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(12, 50, '2025-12-18 03:01:16', 1039.20, 'pending', 259.80, 'Logged-in Customer Discount (20%)', NULL, 'Default Shipping Address', 'Cash on Delivery (COD)'),
(13, 43, '2025-12-18 03:01:24', 1039.20, 'completed', 259.80, 'Employee discount (20%)', '2025-12-18 12:27:41', 'Default Shipping Address', 'Cash on Delivery (COD)'),
(14, 43, '2025-12-18 03:03:06', 1039.20, 'completed', 259.80, 'Employee discount (20%)', '2025-12-18 11:14:38', 'Default Shipping Address', 'Cash on Delivery (COD)'),
(15, 50, '2025-12-18 03:03:13', 1299.00, 'completed', 0.00, NULL, '2025-12-18 11:14:14', 'Default Shipping Address', 'Cash on Delivery (COD)'),
(16, 43, '2025-12-18 04:01:26', 920.00, 'completed', 230.00, 'Employee discount (20%)', '2025-12-18 12:01:30', 'Default Shipping Address', 'Cash on Delivery (COD)');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_sale` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_sale`) VALUES
(1, 1, 325, 1, 850.00),
(2, 2, 330, 1, 480.00),
(3, 3, 324, 1, 1280.00),
(4, 4, 324, 1, 1280.00),
(5, 5, 324, 1, 1280.00),
(6, 6, 324, 1, 1280.00),
(7, 7, 324, 1, 1280.00),
(8, 8, 324, 1, 1280.00),
(9, 9, 326, 1, 1050.00),
(10, 10, 324, 1, 1280.00),
(11, 11, 324, 1, 1280.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_product`
--

CREATE TABLE `order_product` (
  `id` int(11) NOT NULL,
  `supplier` int(11) DEFAULT NULL,
  `product` int(11) DEFAULT NULL,
  `manufactured_at` date DEFAULT NULL,
  `expiration` date DEFAULT NULL,
  `quantity_ordered` int(11) DEFAULT NULL,
  `quantity_received` int(11) DEFAULT NULL,
  `remaining_quantity` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `batch` varchar(32) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_product`
--

INSERT INTO `order_product` (`id`, `supplier`, `product`, `manufactured_at`, `expiration`, `quantity_ordered`, `quantity_received`, `remaining_quantity`, `status`, `batch`, `created_by`, `created_at`, `updated_at`) VALUES
(32, 13, 330, NULL, NULL, 10, 10, 0, 'Complete', 'BATCH-20250612-070856-107', 1, '2025-06-12 07:08:56', '2025-06-12 07:08:56'),
(33, 9, 329, NULL, NULL, 10, 10, 0, 'Complete', 'BATCH-20250612-071042-503', 1, '2025-06-12 07:10:42', '2025-06-12 07:10:42'),
(34, 9, 329, NULL, NULL, 4, 4, 0, 'Complete', 'BATCH-20250613-200902-288', 1, '2025-06-13 20:09:02', '2025-06-13 20:09:02'),
(35, 12, 362, NULL, NULL, 2, 2, 0, 'Complete', 'BATCH-20250613-200902-288', 1, '2025-06-13 20:09:02', '2025-06-13 20:09:02'),
(36, 14, 354, NULL, NULL, 1, 1, 0, 'Complete', 'BATCH-20250614-100743-325', 1, '2025-06-14 10:07:43', '2025-06-14 10:07:43'),
(37, 10, 324, NULL, NULL, 1, 1, 0, 'Complete', 'BATCH-20250615-081950-957', 1, '2025-06-15 08:19:50', '2025-06-15 08:19:50'),
(38, 10, 324, NULL, NULL, 2, 2, 0, 'Complete', 'BATCH-20250614-112200-268', 1, '2025-06-14 11:22:00', '2025-06-14 11:22:00'),
(39, 12, 337, NULL, NULL, 7, 7, 0, 'Complete', 'BATCH-20250614-165215-479', 1, '2025-06-14 16:52:15', '2025-06-14 16:52:15'),
(40, 10, 332, NULL, NULL, 5, 5, 0, 'Complete', 'BATCH-20250614-171252-349', 1, '2025-06-14 17:12:52', '2025-06-14 17:12:52'),
(41, 10, 324, NULL, NULL, 5, 5, 0, 'Complete', 'BATCH-20250615-051830-993', 1, '2025-06-15 05:18:30', '2025-06-15 05:18:30'),
(43, 10, 324, NULL, NULL, 12, 12, 0, 'Complete', 'BATCH-20250617-031841-541', 1, '2025-06-17 03:18:41', '2025-06-17 03:18:41'),
(45, 8, 325, NULL, NULL, 1, 0, 1, 'ORDERED', 'BATCH-20251218-120915-322', 50, '2025-12-18 12:09:15', '2025-12-18 12:09:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_products`
--

CREATE TABLE `order_products` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_order` decimal(10,2) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_products`
--

INSERT INTO `order_products` (`id`, `order_id`, `product_id`, `quantity`, `price_at_order`, `product_name`) VALUES
(45, 31, 358, 1, 595.00, 'Brightening Eye Cream'),
(46, 32, 338, 2, 1150.00, 'Brow Definer Pencil'),
(47, 33, 353, 1, 890.00, 'Balm Cleanser'),
(48, 34, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(49, 35, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(50, 36, 338, 1, 1150.00, 'Brow Definer Pencil'),
(51, 36, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(52, 37, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(53, 38, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(54, 39, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(55, 40, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(56, 41, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(57, 42, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(58, 43, 332, 1, 590.00, 'Color Correcting Palette'),
(59, 43, 379, 3, 690.00, 'Anti-Frizz Hair Serum'),
(60, 44, 338, 2, 1150.00, 'Brow Definer Pencil'),
(61, 45, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(62, 46, 358, 1, 595.00, 'Brightening Eye Cream'),
(63, 47, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(64, 47, 353, 1, 890.00, 'Balm Cleanser'),
(65, 47, 338, 1, 1150.00, 'Brow Definer Pencil'),
(66, 47, 378, 1, 230.00, 'Charcoal Nose Strips'),
(67, 48, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(68, 49, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(69, 50, 338, 1, 1150.00, 'Brow Definer Pencil'),
(70, 51, 358, 1, 595.00, 'Brightening Eye Cream'),
(71, 52, 347, 2, 1690.00, 'Glow Oil'),
(72, 53, 379, 6, 690.00, 'Anti-Frizz Hair Serum'),
(73, 54, 353, 1, 890.00, 'Balm Cleanser'),
(74, 55, 353, 1, 890.00, 'Balm Cleanser'),
(75, 56, 379, 7, 690.00, 'Anti-Frizz Hair Serum'),
(76, 57, 332, 1, 590.00, 'Color Correcting Palette'),
(77, 58, 338, 1, 1150.00, 'Brow Definer Pencil'),
(78, 59, 378, 1, 230.00, 'Charcoal Nose Strips'),
(79, 60, 353, 1, 890.00, 'Balm Cleanser'),
(80, 61, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(81, 62, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(82, 63, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(83, 64, 338, 1, 1150.00, 'Brow Definer Pencil'),
(84, 65, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(85, 66, 325, 1, 850.00, 'Brow Pomade'),
(86, 67, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(87, 68, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(88, 69, 338, 1, 1150.00, 'Brow Definer Pencil'),
(89, 70, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(90, 71, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(91, 72, 353, 2, 890.00, 'Balm Cleanser'),
(92, 73, 330, 10, 480.00, 'Mattifying Setting Powder'),
(93, 74, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(94, 75, 353, 1, 890.00, 'Balm Cleanser'),
(95, 76, 338, 1, 1150.00, 'Brow Definer Pencil'),
(96, 76, 348, 1, 740.00, 'Pressed Pigment Palette'),
(97, 76, 326, 1, 1050.00, 'Sheer Lipstick'),
(98, 77, 359, 1, 670.00, 'Shimmer Blush'),
(99, 77, 326, 1, 1050.00, 'Sheer Lipstick'),
(100, 77, 338, 1, 1150.00, 'Brow Definer Pencil'),
(101, 78, 379, 1, 690.00, 'Anti-Frizz Hair Serum'),
(102, 79, 325, 3, 850.00, 'Brow Pomade'),
(103, 80, 359, 1, 670.00, 'Shimmer Blush'),
(104, 81, 326, 9, 1050.00, 'Sheer Lipstick');

-- --------------------------------------------------------

--
-- Table structure for table `order_products_user`
--

CREATE TABLE `order_products_user` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_order` decimal(10,2) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_products_user`
--

INSERT INTO `order_products_user` (`id`, `order_id`, `product_id`, `quantity`, `price_at_order`, `product_name`) VALUES
(1, 1, 358, 1, 595.00, 'Brightening Eye Cream'),
(2, 2, 338, 1, 1150.00, 'Brow Definer Pencil'),
(3, 3, 325, 1, 850.00, 'Brow Pomade'),
(4, 4, 353, 1, 890.00, 'Balm Cleanser'),
(5, 4, 379, 2, 690.00, 'Anti-Frizz Hair Serum'),
(6, 5, 326, 1, 1050.00, 'Sheer Lipstick'),
(7, 6, 338, 1, 1150.00, 'Brow Definer Pencil'),
(8, 7, 326, 1, 1050.00, 'Sheer Lipstick'),
(9, 8, 353, 2, 890.00, 'Balm Cleanser'),
(10, 9, 325, 1, 850.00, 'Brow Pomade'),
(11, 10, 412, 1, 799.00, 'Hydra Boost Essence'),
(12, 11, 326, 1, 1050.00, 'Sheer Lipstick'),
(13, 12, 406, 1, 1299.00, 'Velvet Matte Lipstick'),
(14, 13, 406, 1, 1299.00, 'Velvet Matte Lipstick'),
(15, 14, 406, 1, 1299.00, 'Velvet Matte Lipstick'),
(16, 15, 406, 1, 1299.00, 'Velvet Matte Lipstick'),
(17, 16, 338, 1, 1150.00, 'Brow Definer Pencil');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otps`
--

CREATE TABLE `password_reset_otps` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_otps`
--

INSERT INTO `password_reset_otps` (`id`, `email`, `otp_code`, `created_at`) VALUES
(37, 'markedselmorales@yahoo.com', '399909', '2025-10-12 10:50:19'),
(40, 'markedselmorales0922@gmail.com', '254807', '2025-11-19 12:20:05'),
(41, 'markedselmorales777@gmail.com', '620970', '2025-11-20 02:20:26');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(225) NOT NULL,
  `brand_name` varchar(225) NOT NULL,
  `stock` int(11) NOT NULL,
  `category` text NOT NULL,
  `product_type` varchar(225) NOT NULL,
  `weight` float NOT NULL,
  `description` text NOT NULL,
  `img` varchar(100) DEFAULT NULL,
  `ingredients` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `manufactured_at` date DEFAULT NULL,
  `expiration` date NOT NULL,
  `price` int(11) NOT NULL,
  `average_rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `total_reviews` int(11) NOT NULL DEFAULT 0,
  `cost` float DEFAULT NULL,
  `units_sold` int(11) NOT NULL DEFAULT 0,
  `choose` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_id`, `product_name`, `brand_name`, `stock`, `category`, `product_type`, `weight`, `description`, `img`, `ingredients`, `created_by`, `created_at`, `updated_at`, `manufactured_at`, `expiration`, `price`, `average_rating`, `total_reviews`, `cost`, `units_sold`, `choose`) VALUES
(324, 0, 'Cushion Foundation', 'Laneige', 33, 'Face', 'Cushion', 15, 'Dewy cushion foundation with SPF', 'products1748780095.jpg', 'Water, Glycerin, Titanium Dioxide', 37, '2025-06-01 14:14:55', '2025-06-01 14:14:55', '2025-06-05', '2026-06-05', 1280, 0.0, 0, 512, 0, ''),
(325, 0, 'Brow Pomade', 'Anastasia', 14, 'Eye', 'Cream', 4, 'Smudge-proof brow pomade', 'products1748780642.webp', 'Beeswax, Iron Oxide, Silica', 37, '2025-06-01 14:24:02', '2025-06-01 14:24:02', '2025-09-02', '2026-09-02', 850, 5.0, 2, 340, 4, ''),
(326, 0, 'Sheer Lipstick', 'Bobbi Brown', 39, 'Lips', 'Stick', 3.5, 'Lightweight sheer lipstick', 'products1748780843.jpg', 'Castor Oil, Pigments, Beeswax', 37, '2025-06-01 14:27:23', '2025-06-01 14:27:23', '2025-07-11', '2026-07-11', 1050, 5.0, 1, 420, 11, ''),
(327, 0, 'Liquid Highlighter', 'Rare Beauty', 20, 'Face', 'Liquid', 10, 'Radiant liquid highlighter', 'products1748781014.webp', 'Mica, Dimethicone, Water', 37, '2025-06-01 14:30:14', '2025-06-01 14:30:14', '2025-02-04', '2026-02-04', 920, 0.0, 0, 368, 0, ''),
(328, 0, 'Eyeshadow Palette', 'Morphe', 71, 'Eye', 'Powder', 12, 'Blendable eyeshadow palette', 'products1748781293.webp', 'Talc, Mica, Color Pigments', 37, '2025-06-01 14:33:09', '2025-06-01 14:33:09', '2025-12-25', '2026-12-25', 999, 0.0, 0, 399.6, 0, ''),
(329, 0, 'Tinted Lip Balm', 'Glossier', 84, 'Lips', 'Balm', 4.5, 'Hydrating tinted lip balm', 'products1748781618.avif', 'Beeswax, Shea Butter, Pigments', 37, '2025-06-01 14:40:18', '2025-06-01 14:40:18', '2025-03-01', '2026-03-01', 460, 0.0, 0, 184, 0, ''),
(330, 0, 'Mattifying Setting Powder', 'e.l.f', 81, 'Face', 'Powder', 7, 'Controls shine and sets makeup', 'products1748781840.jpg', 'Talc, Silica, Mica', 37, '2025-06-01 14:44:00', '2025-06-01 14:44:00', '2025-05-19', '2026-05-19', 480, 5.0, 1, 192, 10, ''),
(331, 0, 'Lash Serum', 'Grande Cosmetics', 5, 'Eye', 'Liquid', 3, 'Lash-enhancing serum', 'products1748782231.jpg', 'Water, Peptides, Glycerin', 37, '2025-06-01 14:50:31', '2025-06-01 14:50:31', '2025-06-01', '2027-01-01', 1200, 0.0, 0, 480, 0, ''),
(332, 0, 'Color Correcting Palette', 'NYX', 53, 'Face', 'Cream', 9, 'Neutralizes skin discoloration', 'products1748782592.webp', 'Titanium Dioxide, Mica, Glycerin', 37, '2025-06-01 14:56:32', '2025-06-01 14:56:32', '2025-11-11', '2027-11-11', 590, 0.0, 0, 236, 0, ''),
(333, 0, 'Lip Oil', 'Dior', 56, 'Lips', 'Oil', 6, 'Glossy lip oil treatment', 'products1748782920.webp', 'Jojoba Oil, Cherry Oil, Pigments', 37, '2025-06-01 15:00:44', '2025-06-01 15:00:44', '2025-04-04', '2027-04-04', 1850, 0.0, 0, 740, 0, ''),
(334, 0, 'Velvet Matte Lip Pencil', 'NARS', 15, 'Lips', 'Pencil', 2.4, 'Rich pigment with matte finish', 'products1748785378.avif', 'Dimethicone, Synthetic Wax, Pigment', 37, '2025-05-12 10:00:00', '2025-05-12 10:00:00', '2025-01-05', '2026-01-05', 1150, 0.0, 0, 460, 0, 'g'),
(335, 0, 'Hydrating Primer', 'Too Faced', 9, 'Face', 'Cream', 30, 'Moisturizing primer base', 'products1748785395.avif', 'Coconut Water, Glycerin, Dimethicone', 37, '2025-05-12 10:05:00', '2025-05-12 10:05:00', '2025-01-10', '2026-01-10', 1100, 0.0, 0, 440, 0, ''),
(336, 0, 'Waterproof Mascara', 'Maybelline', 95, 'Eye', 'Liquid', 9, 'Long-lasting waterproof mascara', 'products1748785542.jpg', 'Aqua, Beeswax, Iron Oxides', 37, '2025-05-13 11:00:00', '2025-05-13 11:00:00', '2025-02-01', '2026-02-01', 420, 0.0, 0, 168, 0, ''),
(337, 0, 'Loose Setting Powder', 'Laura Mercier', 73, 'Face', 'Powder', 29, 'Flawless soft-focus finish', 'products1748785562.webp', 'Talc, Silica, Dimethicone', 37, '2025-05-13 11:05:00', '2025-05-13 11:05:00', '2025-02-12', '2026-02-12', 1690, 0.0, 0, 676, 0, ''),
(338, 0, 'Brow Definer Pencil', 'Benefit', 43, 'Eye', 'Pencil', 1.2, 'Angled brow pencil with spoolie', 'products1748785576.webp', 'Wax, Pigment, Silica', 37, '2025-05-14 09:00:00', '2025-05-14 09:00:00', '2025-03-01', '2026-03-01', 1150, 0.0, 0, 460, 3, ''),
(339, 0, 'Tinted Moisturizer', 'IT Cosmetics', 89, 'Face', 'Cream', 32, 'Light coverage moisturizer with SPF', 'products1748785586.webp', 'Titanium Dioxide, Glycerin, Hyaluronic Acid', 37, '2025-05-14 09:10:00', '2025-05-14 09:10:00', '2025-03-15', '2026-03-15', 1450, 0.0, 0, 580, 0, ''),
(340, 0, 'Cream Blush', 'Fenty Beauty', 70, 'Face', 'Cream', 5, 'Blendable, buildable cream blush', 'products1748785600.webp', 'Dimethicone, Mica, Pigments', 37, '2025-05-15 14:00:00', '2025-05-15 14:00:00', '2025-04-01', '2026-04-01', 980, 0.0, 0, 392, 0, ''),
(341, 0, 'Gel Eyeliner', 'Inglot', 76, 'Eye', 'Gel', 5.5, 'Smudge-proof, intense eyeliner gel', 'products1748785614.jpg', 'Cyclopentasiloxane, Trimethylsiloxysilicate', 37, '2025-05-15 14:10:00', '2025-05-15 14:10:00', '2025-04-08', '2026-04-08', 750, 0.0, 0, 300, 0, ''),
(342, 0, 'Plumping Lip Gloss', 'Buxom', 70, 'Lips', 'Gloss', 4.2, 'Glossy finish with plumping effect', 'products1748785627.jpg', 'Menthol, Vitamin E, Mica', 37, '2025-05-16 08:00:00', '2025-05-16 08:00:00', '2025-04-22', '2026-04-22', 890, 0.0, 0, 356, 0, ''),
(343, 0, 'Makeup Remover Balm', 'Clinique', 21, 'Face', 'Balm', 125, 'Melts away makeup and sunscreen', 'products1748785638.jpg', 'Safflower Oil, PEG-20, Sorbitan', 37, '2025-05-16 08:10:00', '2025-05-16 08:10:00', '2025-05-01', '2026-05-01', 1190, 0.0, 0, 476, 0, ''),
(344, 0, 'Glow Serum Foundation', 'Rare Beauty', 98, 'Face', 'Liquid', 28, 'Lightweight serum foundation with glow finish', 'products1765706465.jpg', 'Water, Glycerin, Titanium Dioxide', 37, '2024-05-17 09:00:00', '2024-05-17 09:00:00', '2024-05-17', '2026-05-17', 1350, 0.0, 0, 540, 0, ''),
(345, 0, 'Lip Soufflé Matte Cream', 'Rare Beauty', 22, 'Lips', 'Cream', 3.9, 'Air-whipped lip cream with velvety matte finish', 'products1765706588.jpg', 'Dimethicone, Pigment, Isododecane', 37, '2024-05-17 09:10:00', '2024-05-17 09:10:00', '2024-05-17', '2026-05-17', 950, 0.0, 0, 380, 0, ''),
(346, 0, 'Soft Focus Setting Spray', 'Urban Decay', 9, 'Face', 'Spray', 118, 'Sets makeup with dewy, long-lasting finish', 'products1765706609.jpg', 'Alcohol, Water, Butylene Glycol', 37, '2024-05-17 09:20:00', '2024-05-17 09:20:00', '2024-05-17', '2026-05-17', 1450, 0.0, 0, 580, 0, ''),
(347, 0, 'Glow Oil', 'Sol de Janeiro', 21, 'Body', 'Oil', 75, 'Shimmering glow body oil with tropical scent', 'products1765707769.jpg', 'Caprylic/Capric Triglyceride, Mica, Fragrance', 37, '2024-05-18 09:00:00', '2024-05-18 09:00:00', '2024-05-18', '2026-05-18', 1690, 0.0, 0, 676, 0, ''),
(348, 0, 'Pressed Pigment Palette', 'ColourPop', 61, 'Eye', 'Powder', 12, 'Highly pigmented eyeshadow palette', 'products1765707788.jpg', 'Mica, Talc, Silica', 37, '2024-05-18 09:10:00', '2024-05-18 09:10:00', '2024-05-18', '2026-05-18', 740, 0.0, 0, 296, 1, ''),
(349, 0, 'Cheek Tint Stick', 'Milk Makeup', 40, 'Face', 'Stick', 6, 'Sheer, dewy color for cheeks and lips', 'products1765707798.jpg', 'Coconut Oil, Mango Butter, Pigment', 37, '2024-05-18 09:20:00', '2024-05-18 09:20:00', '2024-05-18', '2026-05-18', 1090, 0.0, 0, 436, 0, ''),
(350, 0, 'Mattifying Face Primer', 'Smashbox', 15, 'Face', 'Gel', 30, 'Oil-free primer that controls shine', 'products1765707808.jpg', 'Silica, Dimethicone, Cyclopentasiloxane', 37, '2024-05-19 09:00:00', '2024-05-19 09:00:00', '2024-05-19', '2026-05-19', 1190, 0.0, 0, 476, 0, ''),
(351, 0, 'Color Correcting Concealer', 'Tarte', 55, 'Face', 'Cream', 10, 'Neutralizes discoloration and brightens skin', 'products1765707817.jpg', 'Water, Titanium Dioxide, Clay', 37, '2024-05-19 09:10:00', '2024-05-19 09:10:00', '2024-05-19', '2026-05-19', 1050, 0.0, 0, 420, 0, ''),
(352, 0, 'Lash Lifting Mascara', 'L\'Oréal Paris', 29, 'Eye', 'Liquid', 8, 'Lifts and separates lashes with long hold', 'products1765707826.jpg', 'Beeswax, Black Iron Oxide, Glycerin', 37, '2024-05-19 09:20:00', '2024-05-19 09:20:00', '2024-05-19', '2026-05-19', 520, 0.0, 0, 208, 0, ''),
(353, 0, 'Balm Cleanser', 'Banila Co', 65, 'Face', 'Balm', 100, 'Melts makeup and nourishes skin', 'products1765707838.jpg', 'Papaya Extract, Vitamin C, PEG Compounds', 37, '2024-05-19 09:30:00', '2024-05-19 09:30:00', '2024-05-19', '2026-05-19', 890, 5.0, 1, 356, 3, ''),
(354, 0, 'Velvet Matte Lipstick', 'Maybelline', 22, 'Lips', 'Stick', 3.5, 'Comfort matte lipstick with intense pigment', 'products1765707882.jpg', 'Octyldodecanol, Dimethicone, Pigment', 37, '2024-05-20 09:40:00', '2024-05-20 09:40:00', '2024-05-20', '2026-05-20', 399, 0.0, 0, 159.6, 0, ''),
(355, 0, 'Hydra Glow Moisturizer', 'Laneige', 61, 'Face', 'Cream', 50, 'Hydrating cream for glowing skin', 'products1765707891.jpg', 'Glycerin, Niacinamide, Water', 37, '2024-05-20 09:50:00', '2024-05-20 09:50:00', '2024-05-20', '2026-05-20', 1450, 0.0, 0, 580, 0, ''),
(356, 0, 'Volumizing Brow Gel', 'Benefit Cosmetics', 42, 'Brows', 'Gel', 3, 'Adds volume and sets brows all day', 'products1765709570.jpg', 'Silica, Acrylates Copolymer, Pigment', 37, '2024-05-20 10:00:00', '2024-05-20 10:00:00', '2024-05-20', '2026-05-20', 1150, 0.0, 0, 460, 0, ''),
(357, 0, 'Butter Bronzer', 'Physicians Formula', 1, 'Face', 'Powder', 11, 'Soft-focus bronzer with tropical scent', 'products1765709579.jpg', 'Mica, Dimethicone, Murumuru Butter', 37, '2024-05-20 10:10:00', '2024-05-20 10:10:00', '2024-05-20', '2026-05-20', 785, 0.0, 0, 314, 0, ''),
(358, 0, 'Brightening Eye Cream', 'The Inkey List', 6, 'Face', 'Cream', 15, 'Reduces puffiness and brightens under eyes', 'products1765709652.jpg', 'Caffeine, Peptides, Glycerin', 37, '2024-05-20 10:20:00', '2024-05-20 10:20:00', '2024-05-20', '2026-05-20', 595, 0.0, 0, 238, 0, ''),
(359, 0, 'Shimmer Blush', 'Milani', 77, 'Face', 'Powder', 9, 'Radiant blush with baked shimmer finish', 'products1765709662.jpg', 'Talc, Mica, Mineral Oil', 37, '2024-05-20 10:30:00', '2024-05-20 10:30:00', '2024-05-20', '2026-05-20', 670, 5.0, 1, 268, 2, ''),
(360, 0, 'Hydrating Setting Mist', 'Morphe', 54, 'Face', 'Spray', 79, 'Sets makeup and hydrates skin', 'products1765709676.jpg', 'Water, Glycerin, Fragrance', 37, '2024-05-20 10:40:00', '2024-05-20 10:40:00', '2024-05-20', '2026-05-20', 860, 0.0, 0, 344, 0, ''),
(361, 0, 'Poreless Putty Primer', 'e.l.f. Cosmetics', 35, 'Face', 'Cream', 21, 'Smooths skin and blurs imperfections', 'products1765709695.jpg', 'Dimethicone, Squalane, Cyclopentasiloxane', 37, '2024-05-20 10:50:00', '2024-05-20 10:50:00', '2024-05-20', '2026-05-20', 490, 0.0, 0, 196, 0, ''),
(362, 0, 'Tinted Lip Balm', 'Burt\'s Bees', 18, 'Lips', 'Balm', 4.25, 'Moisturizing lip balm with a sheer tint', 'products1765709705.jpg', 'Beeswax, Shea Butter, Iron Oxides', 37, '2024-05-20 11:00:00', '2024-05-20 11:00:00', '2024-05-20', '2026-05-20', 320, 0.0, 0, 128, 0, ''),
(363, 0, 'Lengthening Lash Primer', 'Essence', 73, 'Eye', 'Primer', 7, 'Preps lashes for extra length and volume', 'products1765709731.jpg', 'Beeswax, Water, Stearic Acid', 37, '2024-05-20 11:10:00', '2024-05-20 11:10:00', '2024-05-20', '2026-05-20', 330, 0.0, 0, 132, 0, ''),
(364, 0, 'Oil Control Setting Powder', 'Laura Mercier', 16, 'Face', 'Powder', 29, 'Translucent powder for long-lasting matte finish', 'products1765709925.jpg', 'Talc, Silica, Dimethicone', 37, '2024-05-20 11:20:00', '2024-05-20 11:20:00', '2024-05-20', '2026-05-20', 1890, 0.0, 0, 756, 0, ''),
(365, 0, 'Waterproof Mascara', 'L\'Oréal Paris', 62, 'Eye', 'Mascara', 8.5, 'Long-wearing mascara for voluminous lashes', 'products1765709936.jpg', 'Water, Paraffin, Iron Oxides', 37, '2024-05-20 11:30:00', '2024-05-20 11:30:00', '2024-05-20', '2026-05-20', 460, 0.0, 0, 184, 0, ''),
(366, 0, 'Creamy Highlighter Stick', 'Glossier', 59, 'Face', 'Stick', 5, 'Natural glow stick for subtle radiance', 'products1765709951.jpg', 'Castor Oil, Beeswax, Titanium Dioxide', 37, '2024-05-20 11:40:00', '2024-05-20 11:40:00', '2024-05-20', '2026-05-20', 980, 0.0, 0, 392, 0, ''),
(367, 0, 'Niacinamide Serum 10%', 'The Ordinary', 11, 'Face', 'Serum', 30, 'Reduces blemishes and improves skin texture', 'products1765709962.jpg', 'Niacinamide, Zinc PCA, Water', 37, '2024-05-20 11:50:00', '2024-05-20 11:50:00', '2024-05-20', '2026-05-20', 590, 0.0, 0, 236, 0, ''),
(368, 0, 'Matte Lip Cream', 'NYX', 82, 'Lips', 'Cream', 8, 'Soft matte finish with rich color payoff', 'products1765709982.jpg', 'Isododecane, Dimethicone, Colorant', 37, '2024-05-20 12:00:00', '2024-05-20 12:00:00', '2024-05-20', '2026-05-20', 420, 0.0, 0, 168, 0, ''),
(369, 0, 'SPF 50 Tinted Sunscreen', 'Supergoop!', 72, 'Face', 'Cream', 50, 'Daily sunscreen with light tint for even skin tone', 'products1765709995.jpg', 'Zinc Oxide, Titanium Dioxide, Iron Oxides', 37, '2024-05-20 12:10:00', '2024-05-20 12:10:00', '2024-05-20', '2026-05-20', 1780, 0.0, 0, 712, 0, ''),
(370, 0, 'Lip Plumping Gloss', 'Too Faced', 14, 'Lips', 'Gloss', 4, 'High-shine gloss that visibly plumps lips', 'products1765710033.jpg', 'Caprylyl Glycol, Menthol, Mica', 37, '2024-05-20 12:20:00', '2024-05-20 12:20:00', '2024-05-20', '2026-05-20', 1390, 0.0, 0, 556, 0, ''),
(371, 0, 'Clarifying Face Mask', 'Origins', 58, 'Face', 'Mask', 75, 'Detoxifying mask with clay and charcoal', 'products1765710060.jpg', 'Kaolin, Charcoal Powder, Glycerin', 37, '2024-05-20 12:30:00', '2024-05-20 12:30:00', '2024-05-20', '2026-05-20', 1250, 0.0, 0, 500, 0, ''),
(372, 0, 'Pressed Glitter Eyeshadow', 'ColourPop', 46, 'Eye', 'Powder', 2, 'Sparkly eyeshadow with intense shimmer', 'products1765709559.jpg', 'Polyethylene Terephthalate, Mica, Dimethicone', 37, '2024-05-20 12:40:00', '2024-05-20 12:40:00', '2024-05-20', '2026-05-20', 299, 0.0, 0, 119.6, 0, ''),
(373, 0, 'Moisture Lip Mask', 'LANEIGE', 55, 'Lips', 'Mask', 20, 'Overnight lip treatment for soft, supple lips', 'products1765710855.jpg', 'Shea Butter, Vitamin C, Hyaluronic Acid', 37, '2024-05-20 12:50:00', '2024-05-20 12:50:00', '2024-05-20', '2026-05-20', 950, 0.0, 0, 380, 0, ''),
(374, 0, 'Hydrating Facial Mist', 'Herbivore', 39, 'Face', 'Mist', 120, 'Refreshing mist to hydrate and soothe skin', 'products1765710919.jpg', 'Aloe Vera, Rose Water, Glycerin', 37, '2024-05-20 13:00:00', '2024-05-20 13:00:00', '2024-05-20', '2026-05-20', 880, 0.0, 0, 352, 0, ''),
(376, 0, 'Caffeine Eye Cream', 'The Inkey List', 36, 'Face', 'Cream', 15, 'Reduces puffiness and dark circles under the eyes', 'products1765710941.jpg', 'Caffeine, Glycerin, Squalane', 37, '2024-05-20 13:20:00', '2024-05-20 13:20:00', '2024-05-20', '2026-05-20', 510, 0.0, 0, 204, 0, ''),
(377, 0, 'Lip and Cheek Tint', 'Peripera', 88, 'Multi-use', 'Tint', 9, 'Dual-use tint for lips and cheeks with a natural finish', 'products1765710948.jpg', 'Water, Glycerin, Colorant', 37, '2024-05-20 13:30:00', '2024-05-20 13:30:00', '2024-05-20', '2026-05-20', 370, 0.0, 0, 148, 0, ''),
(378, 0, 'Charcoal Nose Strips', 'Biore', 30, 'Face', 'Strips', 6, 'Cleansing strips to remove blackheads and unclog pores', 'products1765710959.jpg', 'Polyquaternium-37, Charcoal Powder, Water', 37, '2024-05-20 13:40:00', '2024-05-20 13:40:00', '2024-05-20', '2026-05-20', 230, 0.0, 0, 92, 0, ''),
(379, 0, 'Anti-Frizz Hair Serum', 'OGX', 45, 'Hair', 'Serum', 100, 'Tames frizz and adds shine for smooth hair', 'products1765710978.jpg', 'Cyclopentasiloxane, Argan Oil, Fragrance', 37, '2024-05-20 13:50:00', '2024-05-20 13:50:00', '2024-05-20', '2026-05-20', 690, 5.0, 6, 276, 7, ''),
(380, 0, 'Velvet Matte Lipstick', 'Revlon', 82, 'Lips', 'Lipstick', 3.7, 'Creamy matte lipstick for long-lasting color', 'products1765710799.jpg', 'Ricinus Communis Oil, Beeswax, Pigment', 37, '2024-05-20 14:00:00', '2024-05-20 14:00:00', '2024-05-20', '2026-05-20', 399, 0.0, 0, 159.6, 0, ''),
(381, 0, 'Soothing Aloe Gel', 'Nature Republic', 22, 'Face', 'Gel', 300, 'Multipurpose gel with 92% aloe vera for soothing skin', 'products1765710769.jpg', 'Aloe Barbadensis Leaf Extract, Glycerin, Alcohol', 37, '2024-05-20 14:10:00', '2024-05-20 14:10:00', '2024-05-20', '2026-05-20', 320, 0.0, 0, 128, 0, ''),
(382, 0, 'Silk Touch Foundation', 'Milani', 67, 'Face', 'Liquid', 30, 'Silky foundation with buildable coverage', 'products1765710688.jpg', 'Water, Cyclopentasiloxane, Talc', 37, '2024-05-20 14:20:00', '2024-05-20 14:20:00', '2024-05-20', '2026-05-20', 720, 0.0, 0, 288, 0, ''),
(402, 413, 'Vitamin C Brightening Serum', 'Dear Klairs', 170, 'Skincare', 'Serum', 35, 'Brightens dull skin and evens tone', 'products1765713457.jpg', 'Vitamin C, Centella', 37, '2025-12-14 19:29:09', '2025-12-14 19:29:09', '2024-02-05', '2027-02-05', 1399, 4.6, 410, 780, 730, ''),
(403, 410, 'Brow Powder Duo', 'Etude House', 150, 'Makeup', 'Eyebrow Powder', 4, 'Soft powder for natural gradient brows', 'products1765713467.jpg', 'Mica, Talc', 37, '2025-12-14 19:30:22', '2025-12-14 19:30:22', '2024-02-18', '2027-02-18', 699, 4.3, 270, 350, 520, ''),
(404, 404, 'Volumizing Hair Mousse', 'Schwarzkopf', 140, 'Hair', 'Hair Styling', 200, 'Adds volume and body without stiffness', 'products1765713478.jpg', 'Panthenol, Proteins', 37, '2025-12-14 19:30:22', '2025-12-14 19:30:22', '2024-02-14', '2027-02-14', 549, 4.2, 210, 280, 390, ''),
(406, 422, 'Velvet Matte Lipstick', 'MAC', 176, 'Makeup', 'Lipstick', 3, 'Rich matte lipstick with intense color payoff', 'products1765713487.jpg', 'Wax, Pigments, Vitamin E', 37, '2025-12-14 19:32:49', '2025-12-14 19:32:49', '2024-02-12', '2027-02-12', 1299, 5.0, 2, 700, 980, ''),
(407, 423, 'Hydra Shine Lip Gloss', 'Fenty Beauty', 160, 'Makeup', 'Lip Gloss', 5, 'High-shine gloss with hydrating formula', 'products1765713521.jpg', 'Shea Butter, Oils', 37, '2025-12-14 19:32:49', '2025-12-14 19:32:49', '2024-03-01', '2027-03-01', 1199, 4.5, 420, 650, 760, ''),
(409, 425, 'Charcoal Nose Scrub', 'Garnier', 170, 'Nose', 'Scrub', 50, 'Exfoliates nose area to remove blackheads', 'products1765713531.jpg', 'Charcoal, Salicylic Acid', 1, '2025-12-14 19:32:49', '2025-12-14 19:32:49', '2024-02-18', '2026-02-18', 399, 4.2, 260, 210, 480, ''),
(410, 426, 'Pore Tightening Nose Cream', 'Neutrogena', 140, 'Nose', 'Cream', 30, 'Minimizes appearance of pores on nose', 'products1765713542.jpg', 'Niacinamide, Glycerin', 37, '2025-12-14 19:32:49', '2025-12-14 19:32:49', '2024-03-05', '2026-03-05', 649, 4.3, 230, 350, 390, ''),
(411, 427, 'Daily Foam Facial Cleanser', 'Cetaphil', 220, 'Skincare', 'Cleanser', 150, 'Gentle daily cleanser for sensitive skin', 'products1765713555.jpg', 'Glycerin, Panthenol', 37, '2025-12-14 19:33:50', '2025-12-14 19:33:50', '2024-02-01', '2027-02-01', 599, 4.5, 340, 320, 610, ''),
(412, 428, 'Hydra Boost Essence', 'Hada Labo', 179, 'Skincare', 'Essence', 100, 'Deep hydration essence for plump skin', 'products1765713571.jpg', 'Hyaluronic Acid, Water', 37, '2025-12-14 19:33:50', '2025-12-14 19:33:50', '2024-01-20', '2027-01-20', 799, 4.6, 420, 450, 720, ''),
(413, 429, 'Green Tea Balancing Emulsion', 'Innisfree', 160, 'Skincare', 'Emulsion', 130, 'Balances oil and moisture levels', 'products1765713667.jpg', 'Green Tea Extract, Glycerin', 37, '2025-12-14 19:33:50', '2025-12-14 19:33:50', '2024-03-05', '2027-03-05', 899, 4.4, 290, 520, 480, ''),
(414, 430, 'Brightening Rice Mask', 'I’m From', 140, 'Skincare', 'Wash-Off Mask', 110, 'Improves skin tone and texture', 'products1765713682.jpg', 'Rice Extract, Niacinamide', 52, '2025-12-14 19:33:50', '2025-12-14 19:33:50', '2024-02-15', '2027-02-15', 1099, 4.6, 360, 650, 540, ''),
(415, 431, 'Barrier Repair Night Cream', 'Illiyoon', 120, 'Skincare', 'Night Cream', 60, 'Strengthens skin barrier while sleeping', 'products1765713439.jpg', 'Ceramides, Fatty Acids', 37, '2025-12-14 19:33:50', '2025-12-14 19:33:50', '2024-01-28', '2027-01-28', 1299, 4.7, 410, 780, 620, '');

-- --------------------------------------------------------

--
-- Table structure for table `productsuppliers`
--

CREATE TABLE `productsuppliers` (
  `id` int(11) NOT NULL,
  `supplier` int(11) NOT NULL,
  `product` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productsuppliers`
--

INSERT INTO `productsuppliers` (`id`, `supplier`, `product`, `created_at`, `updated_at`) VALUES
(1, 8, 333, '2025-06-01 12:00:00', '2025-06-01 12:00:00'),
(2, 9, 333, '2025-06-01 12:05:00', '2025-06-01 12:05:00'),
(3, 10, 336, '2025-06-01 12:10:00', '2025-06-01 12:10:00'),
(4, 12, 337, '2025-06-01 12:15:00', '2025-06-01 12:15:00'),
(5, 13, 338, '2025-06-01 12:20:00', '2025-06-01 12:20:00'),
(6, 8, 339, '2025-06-01 12:25:00', '2025-06-01 12:25:00'),
(7, 9, 340, '2025-06-01 12:30:00', '2025-06-01 12:30:00'),
(8, 10, 341, '2025-06-01 12:35:00', '2025-06-01 12:35:00'),
(9, 12, 342, '2025-06-01 12:40:00', '2025-06-01 12:40:00'),
(10, 13, 343, '2025-06-01 12:45:00', '2025-06-01 12:45:00'),
(11, 8, 344, '2025-06-01 12:50:00', '2025-06-01 12:50:00'),
(12, 9, 345, '2025-06-01 12:55:00', '2025-06-01 12:55:00'),
(13, 10, 346, '2025-06-01 13:00:00', '2025-06-01 13:00:00'),
(14, 12, 347, '2025-06-01 13:05:00', '2025-06-01 13:05:00'),
(15, 13, 348, '2025-06-01 13:10:00', '2025-06-01 13:10:00'),
(16, 14, 349, '2025-06-01 13:15:00', '2025-06-01 13:15:00'),
(17, 15, 350, '2025-06-01 13:20:00', '2025-06-01 13:20:00'),
(18, 16, 351, '2025-06-01 13:25:00', '2025-06-01 13:25:00'),
(19, 17, 352, '2025-06-01 13:30:00', '2025-06-01 13:30:00'),
(20, 13, 353, '2025-06-01 13:35:00', '2025-06-01 13:35:00'),
(21, 14, 354, '2025-06-01 13:40:00', '2025-06-01 13:40:00'),
(22, 15, 355, '2025-06-01 13:45:00', '2025-06-01 13:45:00'),
(23, 16, 356, '2025-06-01 13:50:00', '2025-06-01 13:50:00'),
(24, 17, 357, '2025-06-01 13:55:00', '2025-06-01 13:55:00'),
(25, 18, 358, '2025-06-01 14:00:00', '2025-06-01 14:00:00'),
(26, 8, 359, '2025-06-01 14:05:00', '2025-06-01 14:05:00'),
(27, 9, 360, '2025-06-01 14:10:00', '2025-06-01 14:10:00'),
(28, 10, 361, '2025-06-01 14:15:00', '2025-06-01 14:15:00'),
(29, 12, 362, '2025-06-01 14:20:00', '2025-06-01 14:20:00'),
(30, 13, 363, '2025-06-01 14:25:00', '2025-06-01 14:25:00'),
(31, 14, 364, '2025-06-01 14:30:00', '2025-06-01 14:30:00'),
(32, 15, 365, '2025-06-01 14:35:00', '2025-06-01 14:35:00'),
(33, 8, 366, '2025-06-01 14:40:00', '2025-06-01 14:40:00'),
(34, 9, 367, '2025-06-01 14:45:00', '2025-06-01 14:45:00'),
(35, 10, 368, '2025-06-01 14:50:00', '2025-06-01 14:50:00'),
(36, 12, 369, '2025-06-01 14:55:00', '2025-06-01 14:55:00'),
(37, 13, 370, '2025-06-01 15:00:00', '2025-06-01 15:00:00'),
(38, 14, 371, '2025-06-01 15:05:00', '2025-06-01 15:05:00'),
(39, 15, 372, '2025-06-01 15:10:00', '2025-06-01 15:10:00'),
(40, 16, 373, '2025-06-01 15:15:00', '2025-06-01 15:15:00'),
(41, 17, 374, '2025-06-01 15:20:00', '2025-06-01 15:20:00'),
(42, 18, 375, '2025-06-01 15:25:00', '2025-06-01 15:25:00'),
(43, 8, 376, '2025-06-01 15:30:00', '2025-06-01 15:30:00'),
(44, 9, 377, '2025-06-01 15:35:00', '2025-06-01 15:35:00'),
(45, 10, 320, '2025-06-01 12:40:44', '2025-06-01 12:40:44'),
(46, 9, 322, '2025-06-01 12:53:13', '2025-06-01 12:53:13'),
(47, 10, 324, '2025-06-01 14:14:55', '2025-06-01 14:14:55'),
(48, 8, 325, '2025-06-01 14:24:02', '2025-06-01 14:24:02'),
(49, 13, 326, '2025-06-01 14:27:23', '2025-06-01 14:27:23'),
(50, 9, 327, '2025-06-01 14:30:14', '2025-06-01 14:30:14'),
(51, 12, 328, '2025-06-01 14:33:09', '2025-06-01 14:33:09'),
(52, 9, 329, '2025-06-01 14:40:18', '2025-06-01 14:40:18'),
(53, 13, 330, '2025-06-01 14:44:00', '2025-06-01 14:44:00'),
(54, 9, 331, '2025-06-01 14:50:31', '2025-06-01 14:50:31'),
(55, 10, 332, '2025-06-01 14:56:32', '2025-06-01 14:56:32'),
(56, 12, 333, '2025-06-01 15:00:44', '2025-06-01 15:00:44'),
(80, 12, 352, '2025-06-01 13:30:00', '2025-06-01 13:30:00'),
(81, 10, 378, '2025-06-01 15:40:00', '2025-06-01 15:40:00'),
(82, 12, 379, '2025-06-01 15:45:00', '2025-06-01 15:45:00'),
(87, 13, 380, '2025-06-01 15:50:00', '2025-06-01 15:50:00'),
(88, 14, 381, '2025-06-01 15:55:00', '2025-06-01 15:55:00'),
(89, 15, 382, '2025-06-01 16:00:00', '2025-06-01 16:00:00'),
(90, 16, 383, '2025-06-01 16:05:00', '2025-06-01 16:05:00'),
(350, 15, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(372, 16, 371, '2025-06-01 16:15:00', '2025-06-01 16:15:00'),
(373, 18, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(374, 14, 370, '2025-06-01 16:10:00', '2025-06-01 16:10:00'),
(375, 9, 372, '2025-06-01 16:20:00', '2025-06-01 16:20:00'),
(376, 13, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(377, 10, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(378, 8, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(379, 15, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(380, 17, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(381, 16, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(382, 12, 0, '2025-06-01 00:00:00', '2025-06-01 00:00:00'),
(424, 8, 334, '2025-06-01 12:00:00', '2025-06-01 12:00:00'),
(425, 9, 335, '2025-06-01 12:05:00', '2025-06-01 12:05:00'),
(426, 10, 336, '2025-06-01 12:10:00', '2025-06-01 12:10:00'),
(427, 12, 337, '2025-06-01 12:15:00', '2025-06-01 12:15:00'),
(428, 13, 338, '2025-06-01 12:20:00', '2025-06-01 12:20:00'),
(429, 8, 339, '2025-06-01 12:25:00', '2025-06-01 12:25:00'),
(430, 9, 340, '2025-06-01 12:30:00', '2025-06-01 12:30:00'),
(431, 10, 341, '2025-06-01 12:35:00', '2025-06-01 12:35:00'),
(432, 12, 342, '2025-06-01 12:40:00', '2025-06-01 12:40:00'),
(433, 13, 343, '2025-06-01 12:45:00', '2025-06-01 12:45:00'),
(437, 9, 384, '2025-06-03 13:22:37', '2025-06-03 13:22:37'),
(439, 17, 386, '2025-06-13 14:34:08', '2025-06-13 14:34:08'),
(440, 8, 0, '2025-06-17 03:17:11', '2025-06-17 03:17:11'),
(469, 8, 402, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(470, 9, 403, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(471, 10, 404, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(472, 12, 405, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(473, 13, 406, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(474, 14, 407, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(475, 15, 408, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(476, 16, 409, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(477, 8, 410, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(478, 9, 411, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(479, 10, 412, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(480, 12, 413, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(481, 13, 414, '2025-12-14 22:03:06', '2025-12-14 22:03:06'),
(482, 14, 415, '2025-12-14 22:03:06', '2025-12-14 22:03:06');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_product_id` int(11) DEFAULT NULL,
  `submitter_type` varchar(10) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT 'Rating from 1 to 5',
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `order_product_id`, `submitter_type`, `rating`, `comment`, `created_at`) VALUES
(14, 406, 43, 15, '', 5, 'sasdwsdasd', '2025-12-18 03:36:04'),
(15, 406, 50, 16, '', 5, 'ang ganda naman', '2025-12-18 03:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `returns_refunds`
--

CREATE TABLE `returns_refunds` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `request_type` varchar(50) NOT NULL,
  `return_quantity` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `proof_image_path` varchar(255) DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returns_refunds`
--

INSERT INTO `returns_refunds` (`id`, `order_id`, `product_id`, `request_type`, `return_quantity`, `user_id`, `reason`, `proof_image_path`, `request_date`, `status`, `processed_by`, `processed_at`, `admin_notes`) VALUES
(18, 3, 325, 'Return and Refund', 1, 43, 'sdasdasdasd', NULL, '2025-12-18 12:16:51', 'Pending', NULL, NULL, NULL),
(24, 2, 338, 'Return and Refund', 1, 43, 'rerewrwer', NULL, '2025-12-18 12:20:14', 'Pending', NULL, NULL, NULL),
(40, 16, 338, 'Return and Refund', 1, 43, 'dqwdqw', NULL, '2025-12-18 12:43:11', 'Pending', NULL, NULL, NULL),
(41, 15, 406, 'Return and Refund', 1, 50, 'gjgkjhjgjg', NULL, '2025-12-18 12:44:21', 'DECLINED', 43, '2025-12-18 12:59:39', '');

-- --------------------------------------------------------

--
-- Table structure for table `review_images`
--

CREATE TABLE `review_images` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `selling_product`
--

CREATE TABLE `selling_product` (
  `id` int(11) NOT NULL,
  `price` double NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `supplier_address` varchar(225) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `email`, `phone`, `supplier_address`, `created_by`, `created_at`, `updated_at`) VALUES
(8, 'Glow Beauty Co.', 'james@GlowBeauty.com', 9331411265, '123 Makati Ave, Makati City, Metro Manila', 37, '2025-06-01', '2025-06-19'),
(9, 'Skin Essentials', 'carl.josh@SkinEssentials.com', 63, '45 Rizal St., Quezon City, Metro Manila\r\n', 37, '2025-06-01', '2025-06-01'),
(10, 'Skin White Co.', 'lopez.markjohn22@Skinwhite.com', 63, '67 Mabini St., Davao City, Davao del Sur\r\n', 37, '2025-06-01', '2025-06-01'),
(12, 'Organic Touch Ltd.', 'dizon.miguel61@OrganicTouch.com', 63, '89 Magsaysay Blvd, Cebu City, Cebu\r\n', 37, '2025-06-01', '2025-06-01'),
(13, 'Beau Skin Cosmetics', 'jasmine@BeauSkin.com', 63, '102 Bonifacio St., Iloilo City, Iloilo\r\n', 37, '2025-06-01', '2025-06-01'),
(14, 'PureSilk Naturals', 'camille.diaz@PureSilkNaturals.com', 63, '56 Luna St., Baguio City, Benguet', 37, '2025-06-01', '2025-06-01'),
(15, 'Bella Derma Products', 'jhonny.tan@BellaDermaPro.com', 63, '12 V. Rama Ave., Cebu City, Cebu', 37, '2025-06-01', '2025-06-01'),
(16, 'FreshBliss Organics', 'leah.ortega@FreshBliss.com', 63, '98 Pioneer St., Mandaluyong City, Metro Manila', 37, '2025-06-01', '2025-06-01'),
(17, 'LuxeSkin Lab', 'mia.santos@LuxeSkinLab.com', 63, '301 Zamora St., Tacloban City, Leyte', 37, '2025-06-01', '2025-06-01'),
(18, 'GlimmerCare Solutions', 'nico.delacruz@GlimmerCare.com', 63, '75 Del Pilar St., Zamboanga City, Zamboanga del Sur', 37, '2025-06-01', '2025-06-01'),
(21, 'qwe', 'markedselweqmorales@yahoo.com', 0, 'qwe', 1, '2025-06-19', '2025-06-19'),
(22, 'cristian', 'markeds213elmorales@yahoo.com', 123, '123', 1, '2025-06-19', '2025-06-19'),
(23, 'qwe', 'markedse213lmorales@yahoo.com', 123, '123', 1, '2025-06-19', '2025-06-19'),
(24, 'qwe', 'markedsqweelmoqwerales@yahoo.com', 0, 'qwe', 1, '2025-06-19', '2025-06-19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(225) NOT NULL,
  `last_name` varchar(225) NOT NULL,
  `password` varchar(225) NOT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `email` varchar(225) NOT NULL,
  `Created_AT` datetime DEFAULT NULL,
  `Updated_AT` int(11) DEFAULT NULL,
  `role` enum('admin','user','customer') NOT NULL DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT NULL,
  `security_question_type` varchar(50) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  `is_2fa_enabled` tinyint(1) DEFAULT 0,
  `two_factor_code` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `password`, `password_reset_token`, `password_reset_expires_at`, `email`, `Created_AT`, `Updated_AT`, `role`, `profile_picture`, `security_question_type`, `security_answer`, `is_2fa_enabled`, `two_factor_code`) VALUES
(1, 'Mark Edsel', 'Morales', '$2y$10$lGfttxiF7yMWbCDX8Jhngu2zXOZUVstmiVdy0cE.jHigc77XEEWDu', '', '2025-06-16 15:21:31', 'markedselmorales@yahoo.com', NULL, NULL, 'admin', 'profile_684c25a3e35ee6.84326810.png', 'maiden_name', '$2y$10$Kvk6Yp/LhSv0tP0WLKvspuPpkZkBLYqXDpzB7SKfKG225ErNIZTcO', 1, NULL),
(2, 'Christian', 'magbanua', 'markedsel123', NULL, NULL, 'christian@yahoo.com', NULL, NULL, 'user', NULL, 'favorite_food', '123', 1, NULL),
(37, 'Cheska', 'Noche', '$2y$10$xaXTqf0zKt.bpkODHRERB.OH.92Xc8DdOUJmJ1cm6ses.HJ9op80O', NULL, NULL, 'cheska@yahoo.com', NULL, NULL, 'user', NULL, NULL, NULL, 1, '472684'),
(39, 'JOHN PAUL', 'asdasdasd', '123', NULL, NULL, 'markedselmorawqeles22@yahoo.com', '2025-06-04 14:34:47', 2147483647, 'user', NULL, NULL, NULL, 1, NULL),
(40, 'JOHN PAUL', 'asdasdasd', '123', NULL, NULL, 'markedse123lmorales22@yahoo.com', '2025-06-13 13:40:50', 2147483647, 'user', NULL, NULL, NULL, 1, NULL),
(43, 'mark', 'morales', '$2y$10$GGxL1NtVgZNZKh/AJjEtG.heDZH9TzkgnHE/SV1GE9DadFE7wzZt.', NULL, NULL, 'markedselmorales777@gmail.com', NULL, NULL, 'admin', '693c27c9bac65_43.png', NULL, NULL, 1, NULL),
(48, 'JOHN PAUL', 'MORALES', '$2y$10$gvxbOQmGAibNWFzQS0hVSORG3fa5QeoaEDvBQdmf12xjeOSlnXH0O', NULL, NULL, 'markedselmorales0922@gmail.com', '2025-06-17 22:25:12', 2147483647, 'admin', NULL, NULL, NULL, 1, NULL),
(49, 'JOHN PAUL3213', 'MORALES2131', '$2y$10$nJVDECJncTLCsdZbpKbj9.Uy8nyLRkynDJZJZd9qBdxYuIjAgWvMa', NULL, NULL, 'markedselmora22les@yahoo.com', '2025-06-17 22:25:54', 2147483647, 'admin', NULL, NULL, NULL, 1, NULL),
(50, 'mark', 'morales', '$2y$10$H2HOEWSHA5MsE7/fPIkT4O1dJrwWmvCLT3Z/loXgu758sjUsfP/me', NULL, NULL, 'markedsel.morales@cvsu.edu.ph', '2025-10-12 16:40:33', 2147483647, 'user', 'profile_693c0e945ce249.24570515.png', NULL, NULL, 1, NULL),
(51, 'morales', 'morales', '$2y$10$/D4LR49oBW0LdzGvLDNwKe57Vhq5JdQJIsIBX1uY82rFCYAtIYYBG', NULL, NULL, 'seanbatuta22@gmail.com', NULL, NULL, 'user', NULL, NULL, NULL, 1, NULL),
(52, 'Cheska', 'Noche', '$2y$10$Swe9PnWPpobqo3HeYwMtcuqm9Sgyz0oGtdUzT/1QhxcRQN5fAjjKy', NULL, NULL, 'cheskanoche11@gmail.com', NULL, NULL, 'user', NULL, NULL, NULL, 0, NULL),
(56, 'Mark Edsel', 'undefined', '$2y$10$cO3/6tLvW2woYUPQuNGVPOd5uxXehGUQHtByQIVoX22T4KNSLct2u', NULL, NULL, 'joshuamiguel0922@gmail.com', '2025-12-19 12:29:55', NULL, 'customer', NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `region` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `street_name_building_house_no` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_current` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`address_id`, `user_id`, `phone_number`, `region`, `province`, `city`, `barangay`, `postal_code`, `street_name_building_house_no`, `created_at`, `is_current`) VALUES
(1, 50, '09331411265', '', '', '', '', '', '', '2025-12-19 12:19:05', 1),
(2, 56, '09331411265', 'region 4a', 'laguna', 'san pedro', 'binan', '4023', 'muntinlupa', '2025-12-19 12:29:55', 0),
(3, 56, '09331411265', 'region 4agfgdfg', 'laguna', 'san pedro', 'binan', '4023', 'muntinlupa', '2025-12-19 12:33:46', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_cart`
--

CREATE TABLE `user_cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `date_added` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_cart`
--

INSERT INTO `user_cart` (`id`, `user_id`, `product_id`, `quantity`, `date_added`) VALUES
(15, 50, 411, 5, '2025-12-18 08:46:38'),
(16, 50, 340, 1, '2025-12-18 09:00:52'),
(17, 50, 368, 1, '2025-12-18 09:00:59'),
(19, 43, 353, 1, '2025-12-18 10:48:19'),
(24, 43, 404, 1, '2025-12-18 12:27:52'),
(25, 50, 338, 1, '2025-12-18 19:06:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customers_orders`
--
ALTER TABLE `customers_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers_order_items`
--
ALTER TABLE `customers_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_COI_OrderRef` (`order_id`),
  ADD KEY `FK_COI_OrderRef2` (`product_id`);

--
-- Indexes for table `customer_cart`
--
ALTER TABLE `customer_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`product_id`),
  ADD KEY `product_cart_ibfk_2` (`product_id`);

--
-- Indexes for table `customer_password_reset_otps`
--
ALTER TABLE `customer_password_reset_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_idx` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders_customer`
--
ALTER TABLE `orders_customer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `orders_user`
--
ALTER TABLE `orders_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_product`
--
ALTER TABLE `order_product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_supplier_ibfk_1` (`supplier`),
  ADD KEY `product_supplier_ibfk_3` (`created_by`),
  ADD KEY `product_supplier_ibfk_2` (`product`);

--
-- Indexes for table `order_products`
--
ALTER TABLE `order_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_products_ibfk_1` (`order_id`),
  ADD KEY `order_products_ibfk_2` (`product_id`);

--
-- Indexes for table `order_products_user`
--
ALTER TABLE `order_products_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_idx` (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`created_by`);

--
-- Indexes for table `productsuppliers`
--
ALTER TABLE `productsuppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `productsuppliers_ibfk_1` (`supplier`),
  ADD KEY `product` (`product`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_product_id` (`order_product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`user_id`);

--
-- Indexes for table `returns_refunds`
--
ALTER TABLE `returns_refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`user_id`);

--
-- Indexes for table `review_images`
--
ALTER TABLE `review_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `selling_product`
--
ALTER TABLE `selling_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `fk_product_name2` (`product_id`),
  ADD KEY `selling_product_ibfk_1` (`created_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD KEY `fk_created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_cart`
--
ALTER TABLE `user_cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customers_orders`
--
ALTER TABLE `customers_orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers_order_items`
--
ALTER TABLE `customers_order_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_cart`
--
ALTER TABLE `customer_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `customer_password_reset_otps`
--
ALTER TABLE `customer_password_reset_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders_customer`
--
ALTER TABLE `orders_customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `orders_user`
--
ALTER TABLE `orders_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_product`
--
ALTER TABLE `order_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `order_products`
--
ALTER TABLE `order_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `order_products_user`
--
ALTER TABLE `order_products_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=416;

--
-- AUTO_INCREMENT for table `productsuppliers`
--
ALTER TABLE `productsuppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=483;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `returns_refunds`
--
ALTER TABLE `returns_refunds`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `review_images`
--
ALTER TABLE `review_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `selling_product`
--
ALTER TABLE `selling_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_cart`
--
ALTER TABLE `user_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers_order_items`
--
ALTER TABLE `customers_order_items`
  ADD CONSTRAINT `FK_COI_OrderRef` FOREIGN KEY (`order_id`) REFERENCES `customers_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_COI_OrderRef2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_customer_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `customers_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_cart`
--
ALTER TABLE `customer_cart`
  ADD CONSTRAINT `customer_cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders_customer`
--
ALTER TABLE `orders_customer`
  ADD CONSTRAINT `orders_customer_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders_user`
--
ALTER TABLE `orders_user`
  ADD CONSTRAINT `orders_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_product`
--
ALTER TABLE `order_product`
  ADD CONSTRAINT `fk_product_id` FOREIGN KEY (`product`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_1` FOREIGN KEY (`supplier`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_2` FOREIGN KEY (`product`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_products`
--
ALTER TABLE `order_products`
  ADD CONSTRAINT `order_products_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_products_user`
--
ALTER TABLE `order_products_user`
  ADD CONSTRAINT `order_products_user_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_products_user_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `productsuppliers`
--
ALTER TABLE `productsuppliers`
  ADD CONSTRAINT `productsuppliers_ibfk_1` FOREIGN KEY (`supplier`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returns_refunds`
--
ALTER TABLE `returns_refunds`
  ADD CONSTRAINT `returns_refunds_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `returns_refunds_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `review_images`
--
ALTER TABLE `review_images`
  ADD CONSTRAINT `review_images_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `selling_product`
--
ALTER TABLE `selling_product`
  ADD CONSTRAINT `fk_product_name2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `selling_product_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_cart`
--
ALTER TABLE `user_cart`
  ADD CONSTRAINT `user_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
