-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 09:55 PM
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
-- Database: `siosio_store1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `username`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Admin User', 'admin', 'admin@siosio.com', '$2y$10$vISaEeX7WzJ95XlYh7uUDO/iUde6C1Wr7UQBoA9fYSAfush5.X60a', 'super_admin', 'active', '2025-10-01 02:17:57'),
(2, 'Dela Cruz Jericho', 'manager', 'manager@siosio.com', '$2y$10$dqVepqdkpm.MX2hF3oawPexlEdySM3JwAl495X/VzH.pBPVo/gYfW', 'admin', 'inactive', '2025-10-21 16:12:16'),
(3, 'dsafsadfas', 'ekoeko', 'ekoeko@siosio.com', '$2y$10$RGBizq0V0Z7xrd2hizSln.50fnqLwwY0MzXLc/I.5eRW.sDBRTDMi', 'admin', 'active', '2025-10-21 16:19:54'),
(4, 'xasdasd', 'chief', 'chief@siosio.com', '$2y$10$21TYJfD.BfQmIpiz38jhNuIF0XpwRfKnHRJMgZ38ED7/NWN4EEvJy', 'admin', 'active', '2025-10-21 16:22:44'),
(5, 'Dela Cruz Jericho', 'asdasd', 'asdasdasdasd@sdfs.com', '$2y$10$2QM0k5AWQXMVSWclvmQeyOhMAO0hDTN6SHyqwULVkPKx0A7HqC6kG', 'admin', 'active', '2025-10-22 06:46:47');

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `admin_role` enum('admin','super_admin') NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text NOT NULL,
  `affected_table` varchar(50) DEFAULT NULL,
  `affected_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`id`, `admin_id`, `admin_name`, `admin_role`, `action_type`, `action_description`, `affected_table`, `affected_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Admin User', 'super_admin', 'login', 'Admin \'admin\' logged in successfully', 'admins', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:28:34'),
(2, 1, 'Admin User', 'super_admin', 'user_add', 'Added new user \'ekoeko\' (ID: 26)', 'userss', 26, NULL, '{\"name\":\"Dela Cruz, Jericho Athan P,\",\"username\":\"ekoeko\",\"email\":\"gekkoretto@gmail.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:42:52'),
(3, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'ekoeko\' (ID: 26)', 'userss', 26, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:43:42'),
(4, 1, 'Admin User', 'super_admin', 'user_delete', 'Deleted user ID #26', 'userss', 26, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:43:56'),
(5, 1, 'Admin User', 'super_admin', 'user_suspend', 'User ID #25 status changed to \'suspended\'', 'userss', 25, '{\"status\":\"active\"}', '{\"status\":\"suspended\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:44:42'),
(6, 1, 'Admin User', 'super_admin', 'user_unsuspend', 'User ID #25 status changed to \'active\'', 'userss', 25, '{\"status\":\"suspended\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:44:49'),
(7, 1, 'Admin User', 'super_admin', 'admin_status_change', 'Admin ID #4 status changed to \'inactive\'', 'admins', 4, '{\"status\":\"active\"}', '{\"status\":\"inactive\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:45:11'),
(8, 1, 'Admin User', 'super_admin', 'admin_status_change', 'Admin ID #4 status changed to \'active\'', 'admins', 4, '{\"status\":\"inactive\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:45:21'),
(9, 1, 'Admin User', 'super_admin', 'admin_add', 'Added new admin \'asdasd\' (ID: 5, Role: admin)', 'admins', 5, NULL, '{\"name\":\"Dela Cruz Jericho\",\"username\":\"asdasd\",\"role\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:46:47'),
(10, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'asdasdada\' (ID: 47)', 'products', 47, NULL, '{\"name\":\"asdasdada\",\"category\":\"siomai\",\"price\":12,\"quantity\":1231}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:48:46'),
(11, 1, 'Admin User', 'super_admin', 'product_update', 'Updated product \'sdadasdasdasdasdas\' (ID: 47)', 'products', 47, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:49:16'),
(12, 1, 'Admin User', 'super_admin', 'order_status_update', 'Updated order #44 status to \'processing\'', 'orders', 44, NULL, '{\"order_status\":\"processing\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:51:46'),
(13, 1, 'Admin User', 'super_admin', 'product_status_change', 'Product ID #47 status changed to \'inactive\'', 'products', 47, '{\"status\":\"active\"}', '{\"status\":\"inactive\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:52:09'),
(14, 1, 'Admin User', 'super_admin', 'product_status_change', 'Product ID #47 status changed to \'active\'', 'products', 47, '{\"status\":\"inactive\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:52:15'),
(15, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #47', 'products', 47, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:52:22'),
(16, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #45', 'orders', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:52:53'),
(17, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #45', 'orders', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:53:37'),
(18, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #45', 'orders', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:53:37'),
(19, 1, 'Admin User', 'super_admin', 'inventory_update', 'Updated inventory for product ID #36 to 91000', 'products', 36, NULL, '{\"quantity\":91000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:57:15'),
(20, 1, 'Admin User', 'super_admin', 'return_reject', 'Return request #8 for order #45 was rejected', 'return_requests', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 06:57:41'),
(21, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'jerichodelacruzz\' (ID: 7)', 'userss', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:01:18'),
(22, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'kuletlet\' (ID: 25)', 'userss', 25, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:02:10'),
(23, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'kuletlet\' (ID: 25)', 'userss', 25, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:09:28'),
(24, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #45', 'orders', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:10:33'),
(25, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #45', 'orders', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:10:33'),
(26, 1, 'Admin User', 'super_admin', 'user_delete', 'Deleted user ID #24', 'userss', 24, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:20:47'),
(27, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #45', 'products', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:24:21'),
(28, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'asdasdasd\' (ID: 48)', 'products', 48, NULL, '{\"name\":\"asdasdasd\",\"category\":\"siopao\",\"price\":12,\"quantity\":123}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:24:45'),
(29, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #48', 'products', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:26:40'),
(30, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'sdasdadads\' (ID: 49)', 'products', 49, NULL, '{\"name\":\"sdasdadads\",\"category\":\"siopao\",\"price\":12,\"quantity\":12}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:26:53'),
(31, 1, 'Admin User', 'super_admin', 'order_status_update', 'Updated order #46 status to \'shipped\'', 'orders', 46, NULL, '{\"order_status\":\"shipped\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:28:17'),
(32, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #49', 'products', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:33:42'),
(33, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'asdasdadas\' (ID: 50)', 'products', 50, NULL, '{\"name\":\"asdasdadas\",\"category\":\"siomai\",\"price\":12,\"quantity\":12}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:33:52'),
(34, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #50', 'products', 50, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:37:52'),
(35, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #51', 'products', 51, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:38:44'),
(36, 1, 'Admin User', 'super_admin', 'order_status_update', 'Updated order #46 status to \'delivered\'', 'orders', 46, NULL, '{\"order_status\":\"delivered\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:43:24'),
(37, 1, 'Admin User', 'super_admin', 'order_status_update', 'Updated order #46 status to \'cancelled\'', 'orders', 46, NULL, '{\"order_status\":\"cancelled\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:45:40'),
(38, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'asdasdasdadasd\' (ID: 53)', 'products', 53, NULL, '{\"name\":\"asdasdasdadasd\",\"category\":\"siomai\",\"price\":12,\"quantity\":12,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:51:42'),
(39, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'asdadsadasdadasdas\' (ID: 54)', 'products', 54, NULL, '{\"name\":\"asdadsadasdadasdas\",\"category\":\"siomai\",\"price\":123,\"quantity\":123,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:54:14'),
(40, 1, 'Admin User', 'super_admin', 'user_suspend', 'User ID #27 status changed to \'suspended\'', 'userss', 27, '{\"status\":\"active\"}', '{\"status\":\"suspended\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:55:30'),
(41, 1, 'Admin User', 'super_admin', 'user_unsuspend', 'User ID #27 status changed to \'active\'', 'userss', 27, '{\"status\":\"suspended\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:55:31'),
(42, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'j3richoo\' (ID: 27)', 'userss', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:55:36'),
(43, 1, 'Admin User', 'super_admin', 'user_soft_delete', 'Soft-deleted and anonymized user ID #27', 'userss', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 07:58:24'),
(44, 1, 'Admin User', 'super_admin', 'user_add', 'Added new user \'123asdasda\' (ID: 28)', 'userss', 28, NULL, '{\"name\":\"Dela Cruz, Jericho Athan P,\",\"username\":\"123asdasda\",\"email\":\"ekojet1521@gmail.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:02:44'),
(45, 1, 'Admin User', 'super_admin', 'user_unsuspend', 'User ID #27 status changed to \'active\'', 'userss', 27, '{\"status\":\"\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:10:33'),
(46, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'jerichodelacruz\' (ID: 7)', 'userss', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:11:31'),
(47, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'jerichodelacruz\' (ID: 7)', 'userss', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:11:40'),
(48, 1, 'Admin User', 'super_admin', 'user_add', 'Added new user \'ekoeko\' (ID: 30)', 'userss', 30, NULL, '{\"name\":\"Dela Cruz, Jericho Athan P,\",\"username\":\"ekoeko\",\"email\":\"ekojet1521@gmail.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:20:49'),
(49, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'sdfasdfsadfsadfasdfsafsadfsa\' (ID: 55)', 'products', 55, NULL, '{\"name\":\"sdfasdfsadfsadfasdfsafsadfsa\",\"category\":\"siomai\",\"price\":12,\"quantity\":12,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:21:27'),
(50, 1, 'Admin User', 'super_admin', 'product_add', 'Added new product \'123123\' (ID: 56)', 'products', 56, NULL, '{\"name\":\"123123\",\"category\":\"siomai\",\"price\":12,\"quantity\":12,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:22:45'),
(51, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'asd\' (ID: 27)', 'userss', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:24:13'),
(52, 1, 'Admin User', 'super_admin', 'order_status_update', 'Updated order #47 status to \'delivered\'', 'orders', 47, NULL, '{\"order_status\":\"delivered\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:25:17'),
(53, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #47', 'orders', 47, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:25:19'),
(54, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #47', 'orders', 47, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:25:19'),
(55, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #55', 'products', 55, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:25:50'),
(56, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #54', 'products', 54, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:25:54'),
(57, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #53', 'products', 53, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:25:56'),
(58, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #52', 'products', 52, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:25:58'),
(59, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #44', 'products', 44, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:26:05'),
(60, 1, 'Admin User', 'super_admin', 'product_delete', 'Deleted product ID #40', 'products', 40, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:26:09'),
(61, 1, 'Admin User', 'super_admin', 'product_update', 'Updated product \'asd\' (ID: 41)', 'products', 41, '{\"name\":\"asd\",\"category\":\"siomai\",\"price\":\"123.00\",\"quantity\":123,\"status\":\"active\"}', '{\"name\":\"asd\",\"category\":\"siomai\",\"price\":123,\"quantity\":123,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:27:30'),
(62, 1, 'Admin User', 'super_admin', 'product_update', 'Updated product \'xasdasd\' (ID: 23)', 'products', 23, '{\"name\":\"xasdasd\",\"category\":\"siopao\",\"price\":\"10.00\",\"quantity\":201,\"status\":\"active\"}', '{\"name\":\"xasdasd\",\"category\":\"siopao\",\"price\":10,\"quantity\":201,\"status\":\"inactive\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:27:37'),
(63, 1, 'Admin User', 'super_admin', 'product_update', 'Updated product \'xasdasd\' (ID: 23)', 'products', 23, '{\"name\":\"xasdasd\",\"category\":\"siopao\",\"price\":\"10.00\",\"quantity\":201,\"status\":\"inactive\"}', '{\"name\":\"xasdasd\",\"category\":\"siopao\",\"price\":10,\"quantity\":201,\"status\":\"inactive\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:27:47'),
(64, 1, 'Admin User', 'super_admin', 'product_update', 'Updated product \'asdasdasd\' (ID: 22)', 'products', 22, '{\"name\":\"Dela Cruz Jericho\",\"category\":\"siomai\",\"price\":\"45.00\",\"quantity\":99,\"status\":\"active\"}', '{\"name\":\"asdasdasd\",\"category\":\"siomai\",\"price\":45,\"quantity\":99,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:27:59'),
(65, 1, 'Admin User', 'super_admin', 'product_update', 'Updated product \'123123\' (ID: 56)', 'products', 56, '{\"name\":\"123123\",\"category\":\"siomai\",\"price\":\"12.00\",\"quantity\":12,\"status\":\"active\"}', '{\"name\":\"123123\",\"category\":\"siomai\",\"price\":12,\"quantity\":12,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:28:09'),
(66, 1, 'Admin User', 'super_admin', 'product_update', 'Updated product \'asd\' (ID: 41)', 'products', 41, '{\"name\":\"asd\",\"category\":\"siomai\",\"price\":\"123.00\",\"quantity\":123,\"status\":\"active\"}', '{\"name\":\"asd\",\"category\":\"siomai\",\"price\":123,\"quantity\":10,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:29:39'),
(67, 1, 'Admin User', 'super_admin', 'product_delete_permanent', 'Permanently deleted product \'123123\' (ID: 56) and related data.', 'products', 56, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:31:44'),
(68, 1, 'Admin User', 'super_admin', 'product_status_change', 'Product ID #23 status changed to \'active\'', 'products', 23, '{\"status\":\"inactive\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:32:18'),
(69, 1, 'Admin User', 'super_admin', 'product_status_change', 'Product ID #41 status changed to \'inactive\'', 'products', 41, '{\"status\":\"active\"}', '{\"status\":\"inactive\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:32:20'),
(70, 1, 'Admin User', 'super_admin', 'product_status_change', 'Product ID #23 status changed to \'inactive\'', 'products', 23, '{\"status\":\"active\"}', '{\"status\":\"inactive\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:32:23'),
(71, 1, 'Admin User', 'super_admin', 'product_status_change', 'Product ID #22 status changed to \'inactive\'', 'products', 22, '{\"status\":\"active\"}', '{\"status\":\"inactive\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 08:32:25'),
(72, 1, 'Admin User', 'super_admin', 'login', 'Admin \'admin\' logged in successfully', 'admins', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 16:37:59'),
(73, 1, 'Admin User', 'super_admin', 'login', 'Admin \'admin\' logged in successfully', 'admins', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-26 23:58:25'),
(74, 1, 'Admin User', 'super_admin', 'login', 'Admin \'admin\' logged in successfully', 'admins', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:31:34'),
(75, 1, 'Admin User', 'super_admin', 'user_soft_delete', 'Soft-deleted and anonymized user ID #27', 'userss', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:34:47'),
(76, 1, 'Admin User', 'super_admin', 'user_unsuspend', 'User ID #27 status changed to \'active\'', 'userss', 27, '{\"status\":\"\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:34:53'),
(77, 1, 'Admin User', 'super_admin', 'user_soft_delete', 'Soft-deleted and anonymized user ID #27', 'userss', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:34:59'),
(78, 1, 'Admin User', 'super_admin', 'user_update', 'Updated user \'ocrampolo\' (ID: 17)', 'userss', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:35:26'),
(79, 1, 'Admin User', 'super_admin', 'user_soft_delete', 'Soft-deleted and anonymized user ID #27', 'userss', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:40:44'),
(80, 1, 'Admin User', 'super_admin', 'user_unsuspend', 'User ID #27 status changed to \'active\'', 'userss', 27, '{\"status\":\"\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:40:53'),
(81, 1, 'Admin User', 'super_admin', 'user_soft_delete', 'Soft-deleted and anonymized user ID #27', 'userss', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:40:59'),
(82, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #48', 'orders', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:46:57'),
(83, 1, 'Admin User', 'super_admin', 'order_view', 'Viewed order details for order #48', 'orders', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:46:57'),
(84, 1, 'Admin User', 'super_admin', 'order_status_update', 'Updated order #48 status to \'shipped\'', 'orders', 48, NULL, '{\"order_status\":\"shipped\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 20:47:29');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_time` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `price_at_time`) STORED,
  `status` enum('active','ordered','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `session_id`, `product_id`, `product_name`, `quantity`, `price_at_time`, `status`, `created_at`, `updated_at`) VALUES
(162, 7, NULL, 3, '', 19, 28.00, 'ordered', '2025-10-02 13:19:45', '2025-10-03 04:06:53'),
(166, 7, NULL, 2, '', 4, 22.00, 'ordered', '2025-10-02 13:50:44', '2025-10-03 04:06:55'),
(168, 7, NULL, 6, '', 5, 32.00, 'ordered', '2025-10-02 13:50:47', '2025-10-03 04:13:53'),
(172, 7, NULL, 2, '', 5, 22.00, 'ordered', '2025-10-03 04:12:54', '2025-10-03 04:13:53'),
(173, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-03 04:19:41', '2025-10-03 04:20:15'),
(174, 7, NULL, 2, '', 10, 22.00, 'ordered', '2025-10-03 04:21:26', '2025-10-03 04:21:54'),
(175, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-03 04:24:41', '2025-10-03 04:25:34'),
(176, 7, NULL, 2, '', 3, 22.00, 'ordered', '2025-10-09 14:12:37', '2025-10-09 15:05:58'),
(177, 7, NULL, 2, '', 1, 22.00, 'ordered', '2025-10-09 15:08:02', '2025-10-09 15:17:58'),
(178, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-09 15:21:07', '2025-10-09 15:26:04'),
(179, 7, NULL, 2, '', 1, 22.00, 'ordered', '2025-10-09 15:29:22', '2025-10-09 15:29:44'),
(180, 7, NULL, 2, '', 2, 22.00, 'ordered', '2025-10-09 15:30:13', '2025-10-17 09:14:49'),
(181, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-09 15:34:47', '2025-10-18 06:03:13'),
(182, 7, NULL, 2, '', 1, 22.00, 'ordered', '2025-10-18 08:58:39', '2025-10-18 08:59:19'),
(183, 7, NULL, 1, '', 6, 25.00, 'ordered', '2025-10-18 09:11:00', '2025-10-18 09:11:25'),
(184, 7, NULL, 2, '', 4, 22.00, 'ordered', '2025-10-18 11:18:15', '2025-10-18 11:18:30'),
(185, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-18 11:24:44', '2025-10-18 11:25:08'),
(190, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-19 07:43:42', '2025-10-19 07:52:17'),
(191, 7, NULL, 2, '', 1, 22.00, 'ordered', '2025-10-19 07:54:26', '2025-10-19 07:55:04'),
(192, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-19 07:56:15', '2025-10-19 08:32:27'),
(193, 7, NULL, 36, '', 1, 3800.00, 'ordered', '2025-10-19 08:10:49', '2025-10-19 08:32:27'),
(194, 7, NULL, 6, '', 11, 32.00, 'ordered', '2025-10-19 08:11:10', '2025-10-19 08:32:27'),
(195, 7, NULL, 22, '', 3, 45.00, 'active', '2025-10-19 08:14:04', '2025-10-21 16:50:11'),
(196, 7, NULL, 4, '', 3, 30.00, 'ordered', '2025-10-19 08:22:38', '2025-10-21 13:56:11'),
(198, 17, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-19 19:28:44', '2025-10-19 19:30:00'),
(199, 17, NULL, 24, '', 1, 1200.00, 'ordered', '2025-10-19 19:28:50', '2025-10-19 19:30:00'),
(200, 21, NULL, 3, '', 3, 28.00, 'ordered', '2025-10-20 18:58:07', '2025-10-20 18:58:32'),
(201, 21, NULL, 4, '', 6, 30.00, 'ordered', '2025-10-21 03:54:21', '2025-10-21 04:18:34'),
(202, 21, NULL, 3, '', 3, 28.00, 'active', '2025-10-21 04:12:10', '2025-10-21 08:11:57'),
(203, 21, NULL, 34, '', 1, 950.00, 'ordered', '2025-10-21 04:24:09', '2025-10-21 04:31:54'),
(204, 17, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-21 07:12:47', '2025-10-22 02:43:29'),
(205, 7, NULL, 3, '', 1, 28.00, 'ordered', '2025-10-21 14:34:05', '2025-10-21 14:34:17'),
(206, 25, NULL, 41, '', 1, 123.00, 'ordered', '2025-10-21 16:42:04', '2025-10-22 04:50:16'),
(207, 7, NULL, 2, '', 1, 22.00, 'active', '2025-10-21 16:50:14', '2025-10-21 16:50:14'),
(208, 17, NULL, 4, '', 1, 30.00, 'ordered', '2025-10-22 04:12:43', '2025-10-22 04:13:06'),
(209, 17, NULL, 2, '', 1, 22.00, 'ordered', '2025-10-22 04:12:45', '2025-10-22 04:13:06'),
(210, 25, NULL, 22, '', 1, 45.00, 'ordered', '2025-10-22 04:56:30', '2025-10-22 04:56:58'),
(211, 25, NULL, 41, '', 2, 123.00, 'ordered', '2025-10-22 05:28:41', '2025-10-22 05:28:59'),
(212, 27, NULL, 2, '', 1, 22.00, 'ordered', '2025-10-22 07:27:10', '2025-10-22 07:27:21'),
(213, 7, NULL, 56, '', 1, 12.00, 'ordered', '2025-10-22 08:24:45', '2025-10-22 08:25:01'),
(214, 7, NULL, 41, '', 1, 123.00, 'ordered', '2025-10-22 08:24:47', '2025-10-22 08:25:01'),
(215, 17, NULL, 3, '', 2, 28.00, 'ordered', '2025-10-28 20:35:42', '2025-10-28 20:46:14'),
(216, 17, NULL, 2, '', 2, 22.00, 'ordered', '2025-10-28 20:35:45', '2025-10-28 20:46:14'),
(217, 17, NULL, 7, '', 1, 45.00, 'ordered', '2025-10-28 20:36:29', '2025-10-28 20:46:14'),
(218, 17, NULL, 10, '', 1, 40.00, 'ordered', '2025-10-28 20:36:32', '2025-10-28 20:46:14'),
(219, 17, NULL, 3, '', 1, 28.00, 'active', '2025-10-28 20:50:14', '2025-10-28 20:50:14'),
(220, 17, NULL, 7, '', 1, 45.00, 'active', '2025-10-28 20:50:16', '2025-10-28 20:50:16'),
(221, 17, NULL, 35, '', 1, 2000.00, 'active', '2025-10-28 20:50:19', '2025-10-28 20:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `cms_content`
--

CREATE TABLE `cms_content` (
  `id` int(11) NOT NULL,
  `page_name` varchar(50) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `content_type` enum('text','html','image','json') NOT NULL DEFAULT 'text',
  `content_value` longtext NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_content`
--

INSERT INTO `cms_content` (`id`, `page_name`, `section_name`, `content_type`, `content_value`, `display_order`, `is_active`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'homepage', 'hero_title', 'html', 'The <span class=\"sio-highlight\">medyo NO.1 <span class=\"sio-highlight\">Sio</span>mai and <span class=\"sio-highlight\">Sio</span>pao Brand</span>', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:10:16'),
(2, 'homepage', 'hero_subtitle', 'text', 'in the Philippines', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:10:16'),
(3, 'homepage', 'hero_tagline', 'html', '<em><span class=\"sio-highlight\">Sio</span>per Sarap, <span class=\"sio-highlight\">Sio</span>per Affordable pa!</em>', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:10:16'),
(4, 'homepage', 'hero_background', 'image', 'uploads/cms/cms_68f85928703fa1.44345882.jpg', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:10:16'),
(5, 'about', 'page_title', 'html', 'About <span class=\"sio-highlight\">Sio</span><span class=\"sio-highlight\">Sio</span>', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(6, 'about', 'page_subtitle', 'text', 'Discover the story behind the Philippines\' beloved Siomai and Siopao brand', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(7, 'about', 'story_title', 'text', 'Our Story', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(8, 'about', 'story_lead', 'html', '<p class=\"lead mb-4\">From humble beginnings to becoming the Philippines\' most beloved <span class=\"sio-highlight\">Sio</span>mai and <span class=\"sio-highlight\">Sio</span>pao brand, our journey has been fueled by passion, tradition, and authentic flavors.</p>', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(9, 'about', 'story_content', 'html', '<p class=\"mb-4\">Founded with a simple mission: to bring authentic, delicious, and affordable Filipino comfort food to every Filipino family. Our signature tagline \"<em><span class=\"sio-highlight\">Sio</span>per Sarap, <span class=\"sio-highlight\">Sio</span>per Affordable pa!</em>\" reflects our commitment to quality without compromise.</p>', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(10, 'about', 'story_image', 'image', 'uploads/cms/cms_68f85b1e6f0f75.61283781.png', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:18:38'),
(11, 'about', 'value1_title', 'text', 'Quality First', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(12, 'about', 'value1_content', 'text', 'We use only the freshest ingredients and time-tested recipes to ensure every bite delivers exceptional taste and quality.', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(13, 'about', 'value2_title', 'text', 'Family Tradition', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(14, 'about', 'value2_content', 'text', 'Our recipes and cooking methods honor Filipino culinary traditions, bringing families together over delicious, authentic meals.', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(15, 'about', 'value3_title', 'text', 'Affordable Excellence', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(16, 'about', 'value3_content', 'text', 'We believe great food shouldn\'t break the bank. Our commitment is to provide premium quality at prices everyone can enjoy.', 0, 1, 1, '2025-10-22 01:22:25', '2025-10-22 04:20:25'),
(21, 'contact', 'page_title', 'html', 'Get In <span class=\"sio-highlight\">Touch</span>', 1, 1, 1, '2025-10-22 03:08:29', '2025-10-22 04:10:16'),
(22, 'contact', 'page_subtitle', 'text', 'We are here to help! Reach out to us with any questions or feedback.', 2, 1, 1, '2025-10-22 03:08:29', '2025-10-22 04:10:16'),
(23, 'contact', 'phone', 'text', '(02) 8-123-4567', 3, 1, 1, '2025-10-22 03:08:29', '2025-10-22 04:10:16'),
(24, 'contact', 'email', 'text', 'siosioretail@gmail.com', 4, 1, 1, '2025-10-22 03:08:29', '2025-10-22 04:10:16'),
(25, 'contact', 'address', 'html', '123 SioSio Building\r\nMakati City, Metro Manila\r\nPhilippines 1234', 5, 1, 1, '2025-10-22 03:08:29', '2025-10-22 04:10:16'),
(26, 'contact', 'business_hours', 'html', 'Monday - Sunday\r\n8:00 AM - 8:00 PM', 6, 1, 1, '2025-10-22 03:08:29', '2025-10-22 04:10:16'),
(27, 'about', 'products_title', 'html', 'Our <span class=\"sio-highlight\">Signature</span> Products', 10, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:20:25'),
(28, 'about', 'products_subtitle', 'text', 'The icons that started it all. Experience the flavors that made SioSio a household name.', 11, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:20:25'),
(29, 'about', 'siomai_title', 'text', 'SioSio Siomai', 12, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:20:25'),
(30, 'about', 'siomai_desc', 'html', 'Our classic pork siomai, steamed to perfection. Served with our signature chili garlic sauce and calamansi.', 13, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:20:25'),
(31, 'about', 'siomai_image', 'image', 'uploads/cms/cms_68f854a4a3b054.61106917.png', 14, 1, 1, '2025-10-22 03:16:55', '2025-10-22 03:51:00'),
(32, 'about', 'siopao_title', 'text', 'SioSio Siopao', 15, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:20:25'),
(33, 'about', 'siopao_desc', 'html', 'Fluffy steamed buns with your choice of savory asado or bola-bola filling. A timeless snack.', 16, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:20:25'),
(34, 'about', 'siopao_image', 'image', 'uploads/cms/cms_68f85493003f43.59455885.png', 17, 1, 1, '2025-10-22 03:16:55', '2025-10-22 03:50:43'),
(35, 'contact', 'faq_title', 'html', 'Frequently Asked <span class=\"sio-highlight\">Questions</span>', 10, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(36, 'contact', 'faq1_question', 'text', 'What are your best-sellers?', 11, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(37, 'contact', 'faq1_answer', 'html', 'Our absolute best-sellers are the classic <strong>Pork Siomai</strong> and our <strong>Asado Siopao</strong>. You can\'t go wrong with these!', 12, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(38, 'contact', 'faq2_question', 'text', 'Where are you located?', 13, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(39, 'contact', 'faq2_answer', 'html', 'We are located at: <br>123 SioSio Building<br>Makati City, Metro Manila<br>Philippines 1234. <br>We also have numerous food carts across the metro.', 14, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(40, 'contact', 'faq3_question', 'text', 'Do you accept bulk orders?', 15, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(41, 'contact', 'faq3_answer', 'text', 'No, we fucking dont so GTFO!', 16, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(42, 'contact', 'faq4_question', 'text', 'What is your return policy?', 17, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16'),
(43, 'contact', 'faq4_answer', 'text', 'Due to the perishable nature of our food products, we do not accept returns. However, if you are unsatisfied with your order or if there was an error, please contact us immediately.', 18, 1, 1, '2025-10-22 03:16:55', '2025-10-22 04:10:16');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `product_image` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_name`, `product_price`, `product_image`, `created_at`) VALUES
(35, 7, 'Japanese Siomai', 32.00, 'https://media.istockphoto.com/id/1221287744/photo/ground-pork-with-crab-stick-wrapped-in-nori.jpg?s=612x612&w=0&k=20&c=Rniq7tdyCqVZHpwngsbzOk1dG1u8pTEeUDE8arsfOUY=', '2025-09-28 13:44:24'),
(36, 7, 'Choco Siopao', 38.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTxSCl2zlIK85vMZ6nRYuWpqde6JnIxBUTe-w&s', '2025-09-28 13:45:15'),
(43, 7, 'Pork Siomai', 25.00, 'https://media.istockphoto.com/id/2182583656/photo/chinese-steamed-dumpling-or-shumai-in-japanese-language-meatball-dumpling-with-wanton-skin.jpg?s=612x612&w=0&k=20&c=0K7_ee0dwfAZhcZZajZRSv8uTifXZhG6LVmlKnSe-0U=', '2025-09-28 16:47:04'),
(44, 7, 'Beef Siomai', 28.00, 'https://media.istockphoto.com/id/2189370578/photo/delicious-shumai-shumay-siomay-chicken-in-bowl-snack-menu.jpg?s=612x612&w=0&k=20&c=hD4kuZsiGIjgyUPq-seqv229pFE43CnS0Do3EH_2E_Y=', '2025-09-28 16:47:28'),
(68, 7, 'Chicken Siomai', 22.00, 'https://media.istockphoto.com/id/1336438874/photo/delicious-dim-sum-home-made-chinese-dumplings-served-on-plate.jpg?s=612x612&w=0&k=20&c=11KB0bXoZeMrlzaHN2q9aZq8kqtdvp-d4Oggc2TF8M4=', '2025-10-19 10:35:32'),
(69, 7, 'Dela Cruz Jericho', 45.00, '../uploads/1760024957_logo.png', '2025-10-19 10:35:38'),
(72, 17, 'Beef Siomai', 28.00, 'https://media.istockphoto.com/id/2189370578/photo/delicious-shumai-shumay-siomay-chicken-in-bowl-snack-menu.jpg?s=612x612&w=0&k=20&c=hD4kuZsiGIjgyUPq-seqv229pFE43CnS0Do3EH_2E_Y=', '2025-10-21 07:12:51'),
(73, 17, 'Chicken Siomai', 22.00, 'https://media.istockphoto.com/id/1336438874/photo/delicious-dim-sum-home-made-chinese-dumplings-served-on-plate.jpg?s=612x612&w=0&k=20&c=11KB0bXoZeMrlzaHN2q9aZq8kqtdvp-d4Oggc2TF8M4=', '2025-10-21 07:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  `success` tinyint(4) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `user_id`, `email`, `attempt_time`, `success`, `ip_address`) VALUES
(1, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:00', 0, '::1'),
(2, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:02', 0, '::1'),
(3, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:04', 0, '::1'),
(4, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:05', 0, '::1'),
(5, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:05', 0, '::1'),
(6, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:07', 0, '::1'),
(7, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:07', 0, '::1'),
(8, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:18', 0, '::1'),
(9, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:21:20', 0, '::1'),
(10, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:21:32', 0, '::1'),
(12, 7, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:23:32', 1, '::1'),
(13, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:29:36', 0, '::1'),
(16, NULL, 'gekkoretto@gmail.com', '2025-10-21 23:31:10', 0, '::1'),
(17, NULL, 'gekkoretto@gmail.com', '2025-10-21 23:31:11', 0, '::1'),
(18, NULL, 'gekkoretto@gmail.com', '2025-10-21 23:31:12', 0, '::1'),
(19, NULL, 'gekkoretto@gmail.com', '2025-10-21 23:31:13', 0, '::1'),
(20, NULL, 'gekkoretto@gmail.com', '2025-10-21 23:31:13', 0, '::1'),
(21, NULL, 'gekkoretto@gmail.com', '2025-10-21 23:31:28', 0, '::1'),
(22, NULL, 'gekkoretto@gmail.com', '2025-10-21 23:32:40', 0, '::1'),
(23, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:32:49', 0, '::1'),
(24, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:32:54', 0, '::1'),
(25, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:32:56', 0, '::1'),
(26, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:32:58', 0, '::1'),
(27, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:32:59', 0, '::1'),
(28, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:34:18', 0, '::1'),
(29, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:34:20', 0, '::1'),
(30, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:34:22', 0, '::1'),
(31, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:34:32', 0, '::1'),
(32, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:34:36', 0, '::1'),
(33, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:15', 0, '::1'),
(34, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:19', 0, '::1'),
(35, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:25', 0, '::1'),
(36, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:26', 0, '::1'),
(37, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:27', 0, '::1'),
(38, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:28', 0, '::1'),
(39, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:30', 0, '::1'),
(40, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:36', 0, '::1'),
(41, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:40', 0, '::1'),
(42, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:42', 0, '::1'),
(43, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:44', 0, '::1'),
(44, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:47', 0, '::1'),
(45, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:49', 0, '::1'),
(46, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:51', 0, '::1'),
(47, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:53', 0, '::1'),
(48, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:36:55', 0, '::1'),
(49, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:37:44', 0, '::1'),
(50, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:38:30', 0, '::1'),
(51, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:38:32', 0, '::1'),
(52, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:38:33', 0, '::1'),
(53, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:38:33', 0, '::1'),
(54, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:38:34', 0, '::1'),
(55, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:38:40', 0, '::1'),
(56, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:38:42', 0, '::1'),
(57, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:39:01', 0, '::1'),
(58, 7, 'qjapdelacruz@tip.edu.ph', '2025-10-21 23:39:04', 1, '::1'),
(59, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:39:22', 0, '::1'),
(60, NULL, 'ekojet1521@gmail.com', '2025-10-21 23:39:24', 0, '::1'),
(62, 25, 'brandonverro@gmail.com', '2025-10-21 23:48:31', 1, '::1'),
(63, 25, 'brandonverro@gmail.com', '2025-10-22 00:41:57', 1, '::1'),
(64, 25, 'brandonverro@gmail.com', '2025-10-22 00:44:19', 1, '::1'),
(65, NULL, 'qjapdelacruz@tip.edu.ph', '2025-10-22 00:45:07', 0, '::1'),
(66, 7, 'qjapdelacruz@tip.edu.ph', '2025-10-22 00:45:10', 1, '::1'),
(67, 17, 'marcomilanez', '2025-10-22 09:09:31', 1, '::1'),
(68, 17, 'ocrampolo', '2025-10-22 10:35:44', 1, '::1'),
(69, 17, 'ocrampolo', '2025-10-22 12:12:33', 1, '::1'),
(70, NULL, 'kuletlet', '2025-10-22 12:45:13', 0, '::1'),
(71, 25, 'kulet', '2025-10-22 12:49:18', 1, '::1'),
(72, 25, 'kuletlet', '2025-10-22 13:17:39', 1, '::1'),
(73, 25, 'kuletlet', '2025-10-22 13:18:31', 1, '::1'),
(74, 25, 'kuletlet', '2025-10-22 13:28:31', 1, '::1'),
(75, 25, 'kuletlet', '2025-10-22 15:02:55', 1, '::1'),
(76, 25, 'kuletlet', '2025-10-22 15:06:08', 1, '::1'),
(77, 25, 'kuletlet', '2025-10-22 15:12:22', 1, '::1'),
(78, NULL, 'kuletlet', '2025-10-22 15:13:33', 0, '::1'),
(79, NULL, 'kuletlet', '2025-10-22 15:13:35', 0, '::1'),
(80, NULL, 'kuletlet', '2025-10-22 15:13:38', 0, '::1'),
(81, NULL, 'kuletlet', '2025-10-22 15:13:39', 0, '::1'),
(82, NULL, 'kuletlet', '2025-10-22 15:13:41', 0, '::1'),
(83, NULL, 'kuletlet', '2025-10-22 15:13:44', 0, '::1'),
(84, NULL, 'ekoeko', '2025-10-22 15:16:47', 0, '::1'),
(85, NULL, 'ekoeko', '2025-10-22 15:16:48', 0, '::1'),
(86, NULL, 'ekoeko', '2025-10-22 15:16:50', 0, '::1'),
(87, NULL, 'ekoeko', '2025-10-22 15:16:51', 0, '::1'),
(88, NULL, 'ekoeko', '2025-10-22 15:16:52', 0, '::1'),
(89, NULL, 'ekoeko', '2025-10-22 15:16:52', 0, '::1'),
(90, NULL, 'ekoeko', '2025-10-22 15:16:52', 0, '::1'),
(91, 27, 'j3richoo', '2025-10-22 15:24:11', 1, '::1'),
(92, 25, 'kuletlet', '2025-10-22 15:39:11', 1, '::1'),
(93, 27, 'j3richoo', '2025-10-22 15:41:19', 1, '::1'),
(94, 7, 'jerichodelacruzz', '2025-10-22 15:42:18', 1, '::1'),
(95, NULL, 'j3richoo', '2025-10-22 15:43:10', 0, '::1'),
(96, 27, 'j3richoo', '2025-10-22 15:43:12', 1, '::1'),
(97, NULL, 'j3richoo', '2025-10-22 15:45:08', 0, '::1'),
(98, 27, 'j3richoo', '2025-10-22 15:45:12', 1, '::1'),
(99, NULL, 'j3richoo', '2025-10-22 15:46:15', 0, '::1'),
(100, 27, 'j3richoo', '2025-10-22 15:46:22', 1, '::1'),
(101, 7, 'qjapdelacruz@tip.edu.ph', '2025-10-22 15:54:29', 1, '::1'),
(103, 7, 'jerichodelacruz', '2025-10-22 16:11:53', 1, '::1'),
(105, 7, 'jerichodelacruz', '2025-10-22 16:21:41', 1, '::1'),
(106, 7, 'jerichodelacruz', '2025-10-23 00:38:51', 1, '::1'),
(107, 7, 'Jerichodelacruz', '2025-10-23 00:39:12', 1, '::1'),
(108, NULL, 'jerichodelacruz', '2025-10-23 00:39:24', 0, '::1'),
(109, NULL, 'jerichodelacruz', '2025-10-23 00:39:27', 0, '::1'),
(110, NULL, 'jerichodelacruz', '2025-10-23 00:39:29', 0, '::1'),
(111, NULL, 'jerichodelacruz', '2025-10-23 00:39:31', 0, '::1'),
(112, NULL, 'jerichodelacruz', '2025-10-23 00:39:32', 0, '::1'),
(113, NULL, 'jerichodelacruz', '2025-10-23 00:39:33', 0, '::1'),
(114, NULL, 'jerichodelacruz', '2025-10-23 00:39:35', 0, '::1'),
(115, NULL, 'ocrampolo', '2025-10-29 04:33:52', 0, '::1'),
(116, NULL, 'markus', '2025-10-29 04:34:10', 0, '::1'),
(117, NULL, 'markus', '2025-10-29 04:34:16', 0, '::1'),
(118, 17, 'ocrampolo', '2025-10-29 04:35:35', 1, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL for admin notifications',
  `admin_id` int(11) DEFAULT NULL COMMENT 'NULL for user notifications',
  `recipient_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `type` enum('order_placed','order_processing','order_shipped','order_delivered','order_cancelled','payment_success','payment_failed','return_requested','return_approved','return_rejected','refund_processed','new_product','low_stock_favorite','low_stock_alert','new_user_registered','promotion') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(255) DEFAULT NULL COMMENT 'URL to redirect when clicked',
  `is_read` tinyint(1) DEFAULT 0,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `admin_id`, `recipient_type`, `order_id`, `product_id`, `type`, `title`, `message`, `action_url`, `is_read`, `priority`, `created_at`, `read_at`) VALUES
(1, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"bobmarley\" (marcomilanez08@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-20 18:57:40', '2025-10-21 03:37:38'),
(2, NULL, 1, 'admin', 36, NULL, 'order_placed', 'New Order Received', 'Order #36 has been placed. Total: ₱134.00', '../admin/admin_order_details.php?id=36', 1, 'high', '2025-10-20 18:58:32', '2025-10-21 03:37:47'),
(3, 21, NULL, 'user', 36, NULL, 'order_shipped', 'Order Shipped!', 'Your order #36 is on its way! Track: TRK68F68658E6E68', '../cart/order_details.php?order_id=36', 1, 'high', '2025-10-21 03:44:48', '2025-10-21 04:06:19'),
(4, 21, NULL, '', 36, NULL, 'order_shipped', 'Order Shipped!', 'Your order is on its way! Track: TRK68F68658E6E68', '../cart/order_details.php?order_id=36', 0, 'high', '2025-10-21 03:44:48', NULL),
(5, 21, NULL, 'user', 36, NULL, 'order_cancelled', 'Order Cancelled', 'Your order #36 has been cancelled.', '../cart/order_details.php?order_id=36', 1, 'normal', '2025-10-21 03:52:55', '2025-10-21 04:06:19'),
(6, 21, NULL, '', 36, NULL, 'order_cancelled', 'Order Cancelled', 'Your order has been cancelled.', '../cart/order_details.php?order_id=36', 0, 'normal', '2025-10-21 03:52:55', NULL),
(7, 21, NULL, 'user', 36, NULL, 'order_delivered', 'Order Delivered', 'Your order #36 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=36', 1, 'high', '2025-10-21 03:58:08', '2025-10-21 04:06:19'),
(8, 21, NULL, '', 36, NULL, 'order_delivered', 'Order Delivered', 'Your order has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=36', 0, 'high', '2025-10-21 03:58:08', NULL),
(9, 21, NULL, 'user', 36, NULL, 'order_shipped', 'Order Shipped!', 'Your order #36 is on its way! Track: TRK68F68658E6E68', '../cart/order_details.php?order_id=36', 1, 'high', '2025-10-21 03:59:11', '2025-10-21 04:06:19'),
(10, 21, NULL, '', 36, NULL, 'order_shipped', 'Order Shipped!', 'Your order is on its way! Track: TRK68F68658E6E68', '../cart/order_details.php?order_id=36', 0, 'high', '2025-10-21 03:59:11', NULL),
(11, 21, NULL, 'user', 36, NULL, 'order_processing', 'Order is Being Processed', 'Your order #36 is now being processed.', '../cart/order_details.php?order_id=36', 1, 'normal', '2025-10-21 03:59:23', '2025-10-21 04:06:16'),
(12, 21, NULL, '', 36, NULL, 'order_processing', 'Order is Being Processed', 'Your order is now being processed.', '../cart/order_details.php?order_id=36', 0, 'normal', '2025-10-21 03:59:23', NULL),
(13, 21, NULL, 'user', 36, NULL, 'order_cancelled', 'Order Cancelled', 'Your order #36 has been cancelled.', '../cart/order_details.php?order_id=36', 1, 'normal', '2025-10-21 03:59:31', '2025-10-21 04:06:13'),
(14, 21, NULL, 'user', 36, NULL, 'order_cancelled', 'Order Cancelled', 'Your order #36 has been cancelled successfully.', NULL, 1, 'normal', '2025-10-21 03:59:31', '2025-10-21 04:06:14'),
(15, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"jojodima\" (jojodima@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-21 04:08:45', '2025-10-21 04:23:44'),
(16, NULL, 1, 'admin', 37, NULL, 'order_placed', 'New Order Received', 'Order #37 has been placed. Total: ₱230.00', '../admin/admin_order_details.php?id=37', 1, 'high', '2025-10-21 04:18:34', '2025-10-21 04:23:44'),
(17, 21, NULL, 'user', 37, NULL, 'order_delivered', 'Order Delivered', 'Your order #37 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=37', 1, 'high', '2025-10-21 04:19:04', '2025-10-21 04:26:22'),
(18, 21, NULL, '', 37, NULL, 'order_delivered', 'Order Delivered', 'Your order has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=37', 0, 'high', '2025-10-21 04:19:04', NULL),
(19, NULL, 1, 'admin', 37, NULL, 'return_requested', 'New Return Request', 'Return request #7 for order #37 needs review.', '../admin/admin_returns.php?id=7', 1, 'high', '2025-10-21 04:19:35', '2025-10-21 04:19:47'),
(20, 21, NULL, 'user', 37, NULL, 'order_processing', 'Order Update', 'Order #37 status updated to return_requested', '../cart/order_details.php?order_id=37', 1, 'normal', '2025-10-21 04:19:35', '2025-10-21 04:24:14'),
(21, NULL, 1, 'admin', 38, NULL, 'order_placed', 'New Order Received', 'Order #38 has been placed. Total: ₱1,000.00', '../admin/admin_order_details.php?id=38', 1, 'high', '2025-10-21 04:31:54', '2025-10-21 14:02:51'),
(22, NULL, 1, 'admin', NULL, 3, 'low_stock_alert', 'Low Stock Alert', 'Product \"Beef Siomai\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 1, 'urgent', '2025-10-21 07:13:37', '2025-10-21 14:02:51'),
(23, NULL, 1, 'admin', NULL, 2, 'low_stock_alert', 'Low Stock Alert', 'Product \"Chicken Siomai\" is running low. Only 0 units remaining.', '../admin/admin_products.php', 1, 'urgent', '2025-10-21 07:14:54', '2025-10-21 14:02:51'),
(24, 21, NULL, 'user', 38, NULL, 'order_cancelled', 'Order Cancelled', 'Your order #38 has been cancelled.', '../cart/order_details.php?order_id=38', 0, 'normal', '2025-10-21 08:12:14', NULL),
(25, 21, NULL, 'user', 38, NULL, 'order_cancelled', 'Order Cancelled', 'Your order #38 has been cancelled successfully.', NULL, 1, 'normal', '2025-10-21 08:12:14', '2025-10-21 08:59:47'),
(26, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: Marco Siomai - ₱123.00', '../products/product_details.php?id=40', 1, 'low', '2025-10-21 08:13:18', '2025-10-21 13:55:29'),
(29, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: Marco Siomai - ₱123.00', '../products/product_details.php?id=40', 1, 'low', '2025-10-21 08:13:18', '2025-10-28 20:35:37'),
(33, 21, NULL, 'user', 37, NULL, 'return_approved', 'Return Request Approved', 'Your return request for order #37 has been approved. Refund will be processed soon.', '../cart/order_details.php?order_id=37', 0, 'high', '2025-10-21 08:53:56', NULL),
(34, 21, NULL, 'user', 37, NULL, 'order_processing', 'Order Update', 'Order #37 status updated to return_approved', '../cart/order_details.php?order_id=37', 0, 'normal', '2025-10-21 08:53:56', NULL),
(35, 21, NULL, '', 37, NULL, 'return_approved', 'Return Request Approved', 'Your return request for order #37 has been approved. Refund will be processed soon.', '../cart/order_details.php?order_id=37', 0, 'high', '2025-10-21 08:53:56', NULL),
(36, 21, NULL, '', 37, NULL, 'refund_processed', 'Refund Processed', 'Your refund of ₱230.00 for order #37 has been processed.', '../cart/order_details.php?order_id=37', 0, 'high', '2025-10-21 08:53:59', NULL),
(37, 21, NULL, 'user', 37, NULL, 'return_rejected', 'Return Request Rejected', 'Your return request for order #37 has been rejected. ', '../cart/order_details.php?order_id=37', 0, 'high', '2025-10-21 08:55:25', NULL),
(38, 21, NULL, 'user', 37, NULL, 'order_processing', 'Order Update', 'Order #37 status updated to return_rejected', '../cart/order_details.php?order_id=37', 0, 'normal', '2025-10-21 08:55:25', NULL),
(39, 21, NULL, '', 37, NULL, 'return_rejected', 'Return Request Rejected', 'Your return request for order #37 has been rejected. ', '../cart/order_details.php?order_id=37', 0, 'high', '2025-10-21 08:55:25', NULL),
(40, 21, NULL, 'user', 38, NULL, 'order_processing', 'Order is Being Processed', 'Your order #38 is now being processed.', '../cart/order_details.php?order_id=38', 0, 'normal', '2025-10-21 08:56:56', NULL),
(41, 21, NULL, '', 38, NULL, 'order_processing', 'Order is Being Processed', 'Your order is now being processed.', '../cart/order_details.php?order_id=38', 0, 'normal', '2025-10-21 08:56:56', NULL),
(42, 21, NULL, 'user', 38, NULL, 'order_delivered', 'Order Delivered', 'Your order #38 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=38', 0, 'high', '2025-10-21 08:57:03', NULL),
(43, 21, NULL, '', 38, NULL, 'order_delivered', 'Order Delivered', 'Your order has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=38', 0, 'high', '2025-10-21 08:57:03', NULL),
(44, NULL, 1, 'admin', 39, NULL, 'order_placed', 'New Order Received', 'Order #39 has been placed. Total: ₱140.00', '../admin/admin_order_details.php?id=39', 1, 'high', '2025-10-21 13:56:11', '2025-10-21 14:02:53'),
(45, 7, NULL, 'user', 39, NULL, 'order_shipped', 'Order Shipped!', 'Your order #39 is on its way! Track: TRK68F790FB80F41', '../cart/order_details.php?order_id=39', 1, 'high', '2025-10-21 13:57:05', '2025-10-21 13:57:59'),
(46, 7, NULL, '', 39, NULL, 'order_shipped', 'Order Shipped!', 'Your order is on its way! Track: TRK68F790FB80F41', '../cart/order_details.php?order_id=39', 0, 'high', '2025-10-21 13:57:05', NULL),
(47, 7, NULL, 'user', 39, NULL, 'order_processing', 'Order is Being Processed', 'Your order #39 is now being processed.', '../cart/order_details.php?order_id=39', 1, 'normal', '2025-10-21 13:57:36', '2025-10-21 13:57:57'),
(48, 7, NULL, '', 39, NULL, 'order_processing', 'Order is Being Processed', 'Your order is now being processed.', '../cart/order_details.php?order_id=39', 0, 'normal', '2025-10-21 13:57:36', NULL),
(49, 7, NULL, 'user', 39, NULL, 'order_delivered', 'Order Delivered', 'Your order #39 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=39', 1, 'high', '2025-10-21 13:57:40', '2025-10-21 13:57:56'),
(50, 7, NULL, '', 39, NULL, 'order_delivered', 'Order Delivered', 'Your order has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=39', 0, 'high', '2025-10-21 13:57:40', NULL),
(51, 7, NULL, 'user', 39, NULL, 'order_cancelled', 'Order Cancelled', 'Your order #39 has been cancelled.', '../cart/order_details.php?order_id=39', 1, 'normal', '2025-10-21 14:28:40', '2025-10-21 14:57:20'),
(52, 7, NULL, '', 39, NULL, 'order_cancelled', 'Order Cancelled', 'Your order has been cancelled.', '../cart/order_details.php?order_id=39', 0, 'normal', '2025-10-21 14:28:40', NULL),
(53, NULL, 1, 'admin', 40, NULL, 'order_placed', 'New Order Received', 'Order #40 has been placed. Total: ₱78.00', '../admin/admin_order_details.php?id=40', 1, 'high', '2025-10-21 14:34:17', '2025-10-21 15:01:26'),
(54, 7, NULL, 'user', NULL, 41, 'new_product', 'New Product Available!', 'Check out our new siomai: asd - ₱123.00', '../products/product_details.php?id=41', 1, 'low', '2025-10-21 14:42:16', '2025-10-21 14:57:20'),
(57, 17, NULL, 'user', NULL, 41, 'new_product', 'New Product Available!', 'Check out our new siomai: asd - ₱123.00', '../products/product_details.php?id=41', 1, 'low', '2025-10-21 14:42:16', '2025-10-28 20:35:37'),
(59, 7, NULL, '', NULL, 41, 'new_product', 'New Product Available!', 'Check out our new siomai: asd - ₱123.00', '../products/product_details.php?id=41', 0, 'low', '2025-10-21 14:42:16', NULL),
(62, 17, NULL, '', NULL, 41, 'new_product', 'New Product Available!', 'Check out our new siomai: asd - ₱123.00', '../products/product_details.php?id=41', 0, 'low', '2025-10-21 14:42:16', NULL),
(64, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asadd - ₱12.00', '../products/product_details.php?id=42', 1, 'low', '2025-10-21 14:45:03', '2025-10-21 14:57:20'),
(67, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asadd - ₱12.00', '../products/product_details.php?id=42', 1, 'low', '2025-10-21 14:45:03', '2025-10-22 04:18:53'),
(69, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asadd - ₱12.00', '../products/product_details.php?id=42', 0, 'low', '2025-10-21 14:45:03', NULL),
(72, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asadd - ₱12.00', '../products/product_details.php?id=42', 0, 'low', '2025-10-21 14:45:03', NULL),
(74, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasfgsdgs - ₱123.00', '../products/product_details.php?id=43', 1, 'low', '2025-10-21 14:47:16', '2025-10-21 14:57:20'),
(77, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasfgsdgs - ₱123.00', '../products/product_details.php?id=43', 1, 'low', '2025-10-21 14:47:16', '2025-10-28 20:35:37'),
(79, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasfgsdgs - ₱123.00', '../products/product_details.php?id=43', 0, 'low', '2025-10-21 14:47:16', NULL),
(82, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasfgsdgs - ₱123.00', '../products/product_details.php?id=43', 0, 'low', '2025-10-21 14:47:16', NULL),
(84, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new bundle: asdasdasdasdasd - ₱123.00', '../products/product_details.php?id=44', 1, 'low', '2025-10-21 14:48:13', '2025-10-21 14:57:20'),
(87, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new bundle: asdasdasdasdasd - ₱123.00', '../products/product_details.php?id=44', 1, 'low', '2025-10-21 14:48:13', '2025-10-22 03:51:51'),
(89, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new bundle: asdasdasdasdasd - ₱123.00', '../products/product_details.php?id=44', 0, 'low', '2025-10-21 14:48:13', NULL),
(92, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new bundle: asdasdasdasdasd - ₱123.00', '../products/product_details.php?id=44', 0, 'low', '2025-10-21 14:48:13', NULL),
(94, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 5 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:21:05', NULL),
(95, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-21 15:23:06', '2025-10-21 15:40:03'),
(96, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"asdasd\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-21 15:27:16', '2025-10-21 15:40:03'),
(97, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"gekkoretto@gmail.com\" has 5 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:31:13', NULL),
(98, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"gekkoretto@gmail.com\" has 7 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:32:40', NULL),
(99, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 5 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:32:59', NULL),
(100, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 6 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:34:18', NULL),
(101, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 7 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:34:20', NULL),
(102, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 8 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:34:22', NULL),
(103, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 9 failed login attempts. Account is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:34:36', NULL),
(104, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:28', NULL),
(105, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:30', NULL),
(106, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 8 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:36', NULL),
(107, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 9 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:40', NULL),
(108, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 10 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:42', NULL),
(109, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 11 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:45', NULL),
(110, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 12 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:47', NULL),
(111, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 13 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:49', NULL),
(112, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 14 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:51', NULL),
(113, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 15 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:53', NULL),
(114, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"qjapdelacruz@tip.edu.ph\" has 16 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:36:55', NULL),
(115, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekojet1521@gmail.com\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:38:40', NULL),
(116, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekojet1521@gmail.com\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:38:42', NULL),
(117, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekojet1521@gmail.com\" has 8 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:39:22', NULL),
(118, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekojet1521@gmail.com\" has 9 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-21 15:39:24', NULL),
(119, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (brandonverro@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-21 15:45:08', '2025-10-21 15:49:02'),
(120, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Suspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-21 16:31:54', NULL),
(121, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Unsuspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-21 16:41:41', NULL),
(122, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Suspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-21 16:43:47', NULL),
(123, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Unsuspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-21 16:44:06', NULL),
(124, NULL, 1, 'admin', 41, NULL, 'order_placed', 'New Order Received', 'Order #41 has been placed. Total: ₱78.00', '../admin/admin_order_details.php?id=41', 1, 'high', '2025-10-22 02:43:29', '2025-10-22 04:46:04'),
(125, NULL, 3, 'admin', 41, NULL, 'order_placed', 'New Order Received', 'Order #41 has been placed. Total: ₱78.00', '../admin/admin_order_details.php?id=41', 0, 'high', '2025-10-22 02:43:29', NULL),
(126, NULL, 4, 'admin', 41, NULL, 'order_placed', 'New Order Received', 'Order #41 has been placed. Total: ₱78.00', '../admin/admin_order_details.php?id=41', 0, 'high', '2025-10-22 02:43:29', NULL),
(127, NULL, 1, 'admin', 42, NULL, 'order_placed', 'New Order Received', 'Order #42 has been placed. Total: ₱102.00', '../admin/admin_order_details.php?id=42', 1, 'high', '2025-10-22 04:13:06', '2025-10-22 04:46:04'),
(128, NULL, 3, 'admin', 42, NULL, 'order_placed', 'New Order Received', 'Order #42 has been placed. Total: ₱102.00', '../admin/admin_order_details.php?id=42', 0, 'high', '2025-10-22 04:13:06', NULL),
(129, NULL, 4, 'admin', 42, NULL, 'order_placed', 'New Order Received', 'Order #42 has been placed. Total: ₱102.00', '../admin/admin_order_details.php?id=42', 0, 'high', '2025-10-22 04:13:06', NULL),
(130, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasda - ₱12.00', '../products/product_details.php?id=45', 1, 'low', '2025-10-22 04:49:02', '2025-10-22 08:17:18'),
(132, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasda - ₱12.00', '../products/product_details.php?id=45', 1, 'low', '2025-10-22 04:49:02', '2025-10-28 20:35:37'),
(134, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasda - ₱12.00', '../products/products.php?product_id=45', 0, 'low', '2025-10-22 04:49:02', NULL),
(136, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasda - ₱12.00', '../products/products.php?product_id=45', 0, 'low', '2025-10-22 04:49:02', NULL),
(137, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasda - ₱12.00', '../products/products.php?product_id=45', 0, 'low', '2025-10-22 04:49:02', NULL),
(138, NULL, 1, 'admin', 43, NULL, 'order_placed', 'New Order Received', 'Order #43 has been placed. Total: ₱173.00', '../admin/admin_order_details.php?id=43', 1, 'high', '2025-10-22 04:50:16', '2025-10-22 05:33:15'),
(139, NULL, 3, 'admin', 43, NULL, 'order_placed', 'New Order Received', 'Order #43 has been placed. Total: ₱173.00', '../admin/admin_order_details.php?id=43', 0, 'high', '2025-10-22 04:50:16', NULL),
(140, NULL, 4, 'admin', 43, NULL, 'order_placed', 'New Order Received', 'Order #43 has been placed. Total: ₱173.00', '../admin/admin_order_details.php?id=43', 0, 'high', '2025-10-22 04:50:16', NULL),
(141, NULL, 1, 'admin', 44, NULL, 'order_placed', 'New Order Received', 'Order #44 has been placed. Total: ₱95.00', '../admin/admin_order_details.php?id=44', 1, 'high', '2025-10-22 04:56:58', '2025-10-22 05:33:15'),
(142, NULL, 3, 'admin', 44, NULL, 'order_placed', 'New Order Received', 'Order #44 has been placed. Total: ₱95.00', '../admin/admin_order_details.php?id=44', 0, 'high', '2025-10-22 04:56:58', NULL),
(143, NULL, 4, 'admin', 44, NULL, 'order_placed', 'New Order Received', 'Order #44 has been placed. Total: ₱95.00', '../admin/admin_order_details.php?id=44', 0, 'high', '2025-10-22 04:56:58', NULL),
(144, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfasdfasdfasdf - ₱12.00', '../products/product_details.php?id=46', 1, 'low', '2025-10-22 05:00:03', '2025-10-22 08:17:18'),
(146, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfasdfasdfasdf - ₱12.00', '../products/product_details.php?id=46', 1, 'low', '2025-10-22 05:00:03', '2025-10-28 20:35:37'),
(147, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfasdfasdfasdf - ₱12.00', '../products/product_details.php?id=46', 1, 'low', '2025-10-22 05:00:03', '2025-10-22 05:00:16'),
(148, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfasdfasdfasdf - ₱12.00', '../products/products.php?product_id=46', 0, 'low', '2025-10-22 05:00:03', NULL),
(150, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfasdfasdfasdf - ₱12.00', '../products/products.php?product_id=46', 0, 'low', '2025-10-22 05:00:03', NULL),
(151, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfasdfasdfasdf - ₱12.00', '../products/products.php?product_id=46', 0, 'low', '2025-10-22 05:00:03', NULL),
(152, NULL, 1, 'admin', NULL, NULL, 'low_stock_alert', 'Low Stock Alert', 'Product \"asdasd\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 1, 'urgent', '2025-10-22 05:08:57', '2025-10-22 05:33:15'),
(153, NULL, 3, 'admin', NULL, NULL, 'low_stock_alert', 'Low Stock Alert', 'Product \"asdasd\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 0, 'urgent', '2025-10-22 05:08:57', NULL),
(154, NULL, 4, 'admin', NULL, NULL, 'low_stock_alert', 'Low Stock Alert', 'Product \"asdasd\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 0, 'urgent', '2025-10-22 05:08:57', NULL),
(155, 25, NULL, 'user', 44, NULL, 'order_shipped', 'Order Shipped!', 'Your order #44 is on its way! Track: TRK68F8641A0B3E1', '../cart/order_details.php?order_id=44', 0, 'high', '2025-10-22 05:09:17', NULL),
(156, 25, NULL, '', 44, NULL, 'order_shipped', 'Order Shipped!', 'Your order is on its way! Track: TRK68F8641A0B3E1', '../cart/order_details.php?order_id=44', 0, 'high', '2025-10-22 05:09:17', NULL),
(157, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Suspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:10:15', NULL),
(158, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Unsuspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:15:27', NULL),
(159, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #10 was permanently deleted by Admin User.', 'admin_users.php', 0, 'high', '2025-10-22 05:15:37', NULL),
(160, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Suspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:17:16', NULL),
(161, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Unsuspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:17:24', NULL),
(162, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Suspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:17:57', NULL),
(163, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Unsuspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:18:25', NULL),
(164, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Suspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:18:40', NULL),
(165, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Unsuspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:23:50', NULL),
(166, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Suspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:23:56', NULL),
(167, NULL, 1, '', NULL, NULL, '', 'User Status Updated', 'User ID #25 was Unsuspended by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 05:28:27', NULL),
(168, NULL, 1, 'admin', 45, NULL, 'order_placed', 'New Order Received', 'Order #45 has been placed. Total: ₱296.00', '../admin/admin_order_details.php?id=45', 1, 'high', '2025-10-22 05:28:59', '2025-10-22 05:29:24'),
(169, NULL, 3, 'admin', 45, NULL, 'order_placed', 'New Order Received', 'Order #45 has been placed. Total: ₱296.00', '../admin/admin_order_details.php?id=45', 0, 'high', '2025-10-22 05:28:59', NULL),
(170, NULL, 4, 'admin', 45, NULL, 'order_placed', 'New Order Received', 'Order #45 has been placed. Total: ₱296.00', '../admin/admin_order_details.php?id=45', 0, 'high', '2025-10-22 05:28:59', NULL),
(171, 25, NULL, 'user', 45, NULL, 'order_delivered', 'Order Delivered', 'Your order #45 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=45', 0, 'high', '2025-10-22 05:29:56', NULL),
(172, 25, NULL, '', 45, NULL, 'order_delivered', 'Order Delivered', 'Your order has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=45', 0, 'high', '2025-10-22 05:29:56', NULL),
(173, NULL, 1, 'admin', 45, NULL, 'return_requested', 'New Return Request', 'Return request #8 for order #45 needs review.', '../admin/admin_returns.php?id=8', 1, 'high', '2025-10-22 05:30:53', '2025-10-22 05:31:03'),
(174, NULL, 3, 'admin', 45, NULL, 'return_requested', 'New Return Request', 'Return request #8 for order #45 needs review.', '../admin/admin_returns.php?id=8', 0, 'high', '2025-10-22 05:30:53', NULL),
(175, NULL, 4, 'admin', 45, NULL, 'return_requested', 'New Return Request', 'Return request #8 for order #45 needs review.', '../admin/admin_returns.php?id=8', 0, 'high', '2025-10-22 05:30:53', NULL),
(176, 25, NULL, 'user', 45, NULL, 'order_processing', 'Order Update', 'Order #45 status updated to return_requested', '../cart/order_details.php?order_id=45', 1, 'normal', '2025-10-22 05:30:53', '2025-10-22 05:31:29'),
(177, 25, NULL, 'user', 45, NULL, 'return_approved', 'Return Request Approved', 'Your return request for order #45 has been approved. Refund will be processed soon.', '../cart/order_details.php?order_id=45', 0, 'high', '2025-10-22 05:31:24', NULL),
(178, 25, NULL, 'user', 45, NULL, 'order_processing', 'Order Update', 'Order #45 status updated to return_approved', '../cart/order_details.php?order_id=45', 0, 'normal', '2025-10-22 05:31:24', NULL),
(179, 25, NULL, '', 45, NULL, 'return_approved', 'Return Request Approved', 'Your return request for order #45 has been approved. Refund will be processed soon.', '../cart/order_details.php?order_id=45', 0, 'high', '2025-10-22 05:31:24', NULL),
(180, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (gekkoretto@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-22 06:42:52', '2025-10-22 16:46:13'),
(181, NULL, 3, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (gekkoretto@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 06:42:52', NULL),
(182, NULL, 4, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (gekkoretto@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 06:42:52', NULL),
(183, NULL, 1, '', NULL, NULL, '', 'New User Registered', 'A new user \'ekoeko\' was added by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 06:42:52', NULL),
(184, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #26 was permanently deleted by Admin User.', 'admin_users.php', 0, 'high', '2025-10-22 06:43:56', NULL),
(185, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdada - ₱12.00', '../products/product_details.php?id=47', 1, 'low', '2025-10-22 06:48:46', '2025-10-22 08:17:18'),
(186, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdada - ₱12.00', '../products/product_details.php?id=47', 1, 'low', '2025-10-22 06:48:46', '2025-10-28 20:35:37'),
(187, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdada - ₱12.00', '../products/product_details.php?id=47', 0, 'low', '2025-10-22 06:48:46', NULL),
(188, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdada - ₱12.00', '../products/products.php?product_id=47', 0, 'low', '2025-10-22 06:48:46', NULL),
(189, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdada - ₱12.00', '../products/products.php?product_id=47', 0, 'low', '2025-10-22 06:48:46', NULL),
(190, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdada - ₱12.00', '../products/products.php?product_id=47', 0, 'low', '2025-10-22 06:48:46', NULL),
(191, 25, NULL, 'user', 44, NULL, 'order_processing', 'Order is Being Processed', 'Your order #44 is now being processed.', '../cart/order_details.php?order_id=44', 0, 'normal', '2025-10-22 06:51:46', NULL),
(192, 25, NULL, '', 44, NULL, 'order_processing', 'Order is Being Processed', 'Your order is now being processed.', '../cart/order_details.php?order_id=44', 0, 'normal', '2025-10-22 06:51:46', NULL),
(193, 25, NULL, 'user', 45, NULL, 'return_rejected', 'Return Request Rejected', 'Your return request for order #45 has been rejected. sge po', '../cart/order_details.php?order_id=45', 0, 'high', '2025-10-22 06:57:41', NULL),
(194, 25, NULL, 'user', 45, NULL, 'order_processing', 'Order Update', 'Order #45 status updated to return_rejected', '../cart/order_details.php?order_id=45', 0, 'normal', '2025-10-22 06:57:41', NULL),
(195, 25, NULL, '', 45, NULL, 'return_rejected', 'Return Request Rejected', 'Your return request for order #45 has been rejected. sge po', '../cart/order_details.php?order_id=45', 0, 'high', '2025-10-22 06:57:41', NULL),
(196, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"kuletlet\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:13:44', NULL),
(197, NULL, 3, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"kuletlet\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:13:44', NULL),
(198, NULL, 4, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"kuletlet\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:13:44', NULL),
(199, NULL, 5, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"kuletlet\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:13:44', NULL),
(200, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(201, NULL, 3, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(202, NULL, 4, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(203, NULL, 5, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(204, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(205, NULL, 3, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(206, NULL, 4, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(207, NULL, 5, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"ekoeko\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 07:16:52', NULL),
(208, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #24 was permanently deleted by Admin User.', 'admin_users.php', 0, 'high', '2025-10-22 07:20:47', NULL),
(209, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-22 07:23:11', '2025-10-22 07:23:48'),
(210, NULL, 3, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 07:23:11', NULL),
(211, NULL, 4, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 07:23:11', NULL),
(212, NULL, 5, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 07:23:11', NULL),
(213, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdasd - ₱12.00', '../products/product_details.php?id=48', 1, 'low', '2025-10-22 07:24:45', '2025-10-22 08:17:18'),
(214, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdasd - ₱12.00', '../products/product_details.php?id=48', 1, 'low', '2025-10-22 07:24:45', '2025-10-28 20:35:37'),
(215, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdasd - ₱12.00', '../products/product_details.php?id=48', 0, 'low', '2025-10-22 07:24:45', NULL),
(216, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdasd - ₱12.00', '../products/products.php?product_id=48', 0, 'low', '2025-10-22 07:24:45', NULL),
(217, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdasd - ₱12.00', '../products/products.php?product_id=48', 0, 'low', '2025-10-22 07:24:45', NULL),
(218, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdasd - ₱12.00', '../products/products.php?product_id=48', 0, 'low', '2025-10-22 07:24:45', NULL),
(219, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: sdasdadads - ₱12.00', '../products/product_details.php?id=49', 1, 'low', '2025-10-22 07:26:53', '2025-10-22 08:17:18'),
(220, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: sdasdadads - ₱12.00', '../products/product_details.php?id=49', 1, 'low', '2025-10-22 07:26:53', '2025-10-28 20:35:37'),
(221, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: sdasdadads - ₱12.00', '../products/product_details.php?id=49', 0, 'low', '2025-10-22 07:26:53', NULL),
(222, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: sdasdadads - ₱12.00', '../products/products.php?product_id=49', 0, 'low', '2025-10-22 07:26:53', NULL),
(223, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: sdasdadads - ₱12.00', '../products/products.php?product_id=49', 0, 'low', '2025-10-22 07:26:53', NULL),
(224, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: sdasdadads - ₱12.00', '../products/products.php?product_id=49', 0, 'low', '2025-10-22 07:26:53', NULL),
(225, NULL, 1, 'admin', 46, NULL, 'order_placed', 'New Order Received', 'Order #46 has been placed. Total: ₱72.00', '../admin/admin_order_details.php?id=46', 1, 'high', '2025-10-22 07:27:21', '2025-10-22 16:46:13'),
(226, NULL, 3, 'admin', 46, NULL, 'order_placed', 'New Order Received', 'Order #46 has been placed. Total: ₱72.00', '../admin/admin_order_details.php?id=46', 0, 'high', '2025-10-22 07:27:21', NULL),
(227, NULL, 4, 'admin', 46, NULL, 'order_placed', 'New Order Received', 'Order #46 has been placed. Total: ₱72.00', '../admin/admin_order_details.php?id=46', 0, 'high', '2025-10-22 07:27:21', NULL),
(228, NULL, 5, 'admin', 46, NULL, 'order_placed', 'New Order Received', 'Order #46 has been placed. Total: ₱72.00', '../admin/admin_order_details.php?id=46', 0, 'high', '2025-10-22 07:27:21', NULL),
(229, 27, NULL, 'user', 46, NULL, 'order_shipped', 'Order Shipped!', 'Your order #46 is on its way! Track: TRK68F887598A068', '../cart/order_details.php?order_id=46', 1, 'high', '2025-10-22 07:28:17', '2025-10-22 07:28:21'),
(230, 27, NULL, '', 46, NULL, 'order_shipped', 'Order Shipped!', 'Your order is on its way! Track: TRK68F887598A068', '../cart/order_details.php?order_id=46', 0, 'high', '2025-10-22 07:28:17', NULL),
(231, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdadas - ₱12.00', '../products/product_details.php?id=50', 1, 'low', '2025-10-22 07:33:52', '2025-10-22 08:17:18'),
(232, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdadas - ₱12.00', '../products/product_details.php?id=50', 1, 'low', '2025-10-22 07:33:52', '2025-10-28 20:35:37'),
(233, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdadas - ₱12.00', '../products/product_details.php?id=50', 0, 'low', '2025-10-22 07:33:52', NULL),
(234, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdadas - ₱12.00', '../products/products.php?product_id=50', 0, 'low', '2025-10-22 07:33:52', NULL),
(235, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdadas - ₱12.00', '../products/products.php?product_id=50', 0, 'low', '2025-10-22 07:33:52', NULL),
(236, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdadas - ₱12.00', '../products/products.php?product_id=50', 0, 'low', '2025-10-22 07:33:52', NULL),
(237, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdads - ₱12.00', '../products/product_details.php?id=51', 1, 'low', '2025-10-22 07:38:03', '2025-10-22 08:17:18'),
(238, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdads - ₱12.00', '../products/product_details.php?id=51', 1, 'low', '2025-10-22 07:38:03', '2025-10-28 20:35:37'),
(239, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdads - ₱12.00', '../products/product_details.php?id=51', 0, 'low', '2025-10-22 07:38:03', NULL),
(240, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdads - ₱12.00', '../products/products.php?product_id=51', 0, 'low', '2025-10-22 07:38:03', NULL),
(241, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdads - ₱12.00', '../products/products.php?product_id=51', 0, 'low', '2025-10-22 07:38:03', NULL),
(242, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siopao: asdasdads - ₱12.00', '../products/products.php?product_id=51', 0, 'low', '2025-10-22 07:38:03', NULL),
(243, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasd - ₱12.00', '../products/product_details.php?id=52', 1, 'low', '2025-10-22 07:38:55', '2025-10-22 08:17:18'),
(244, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasd - ₱12.00', '../products/product_details.php?id=52', 1, 'low', '2025-10-22 07:38:55', '2025-10-28 20:35:37'),
(245, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasd - ₱12.00', '../products/product_details.php?id=52', 1, 'low', '2025-10-22 07:38:55', '2025-10-22 07:39:52'),
(246, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasd - ₱12.00', '../products/products.php?product_id=52', 0, 'low', '2025-10-22 07:38:55', NULL),
(247, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasd - ₱12.00', '../products/products.php?product_id=52', 0, 'low', '2025-10-22 07:38:55', NULL),
(248, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasd - ₱12.00', '../products/products.php?product_id=52', 0, 'low', '2025-10-22 07:38:55', NULL),
(249, 27, NULL, 'user', 46, NULL, 'order_delivered', 'Order Delivered', 'Your order #46 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=46', 1, 'high', '2025-10-22 07:43:24', '2025-10-22 07:45:14'),
(250, 27, NULL, '', 46, NULL, 'order_delivered', 'Order Delivered', 'Your order has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=46', 0, 'high', '2025-10-22 07:43:24', NULL),
(251, 27, NULL, 'user', 46, NULL, 'order_cancelled', 'Order Cancelled', 'Your order #46 has been cancelled.', '../cart/order_details.php?order_id=46', 1, 'normal', '2025-10-22 07:45:40', '2025-10-22 07:46:24'),
(252, 27, NULL, '', 46, NULL, 'order_cancelled', 'Order Cancelled', 'Your order has been cancelled.', '../cart/order_details.php?order_id=46', 0, 'normal', '2025-10-22 07:45:40', NULL),
(253, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasdadasd - ₱12.00', '../products/product_details.php?id=53', 1, 'low', '2025-10-22 07:51:42', '2025-10-22 08:14:06'),
(254, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasdadasd - ₱12.00', '../products/product_details.php?id=53', 1, 'low', '2025-10-22 07:51:42', '2025-10-28 20:35:37'),
(255, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasdadasd - ₱12.00', '../products/product_details.php?id=53', 0, 'low', '2025-10-22 07:51:42', NULL),
(256, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasdadasd - ₱12.00', '../products/products.php?product_id=53', 0, 'low', '2025-10-22 07:51:42', NULL),
(257, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasdadasd - ₱12.00', '../products/products.php?product_id=53', 0, 'low', '2025-10-22 07:51:42', NULL),
(258, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdasdasdadasd - ₱12.00', '../products/products.php?product_id=53', 0, 'low', '2025-10-22 07:51:42', NULL),
(259, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdadsadasdadasdas - ₱123.00', '../products/product_details.php?id=54', 1, 'low', '2025-10-22 07:54:14', '2025-10-22 08:20:08'),
(260, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdadsadasdadasdas - ₱123.00', '../products/product_details.php?id=54', 1, 'low', '2025-10-22 07:54:14', '2025-10-28 20:35:37'),
(261, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdadsadasdadasdas - ₱123.00', '../products/product_details.php?id=54', 0, 'low', '2025-10-22 07:54:14', NULL),
(262, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdadsadasdadasdas - ₱123.00', '../products/products.php?product_id=54', 0, 'low', '2025-10-22 07:54:14', NULL),
(263, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdadsadasdadasdas - ₱123.00', '../products/products.php?product_id=54', 0, 'low', '2025-10-22 07:54:14', NULL),
(264, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: asdadsadasdadasdas - ₱123.00', '../products/products.php?product_id=54', 0, 'low', '2025-10-22 07:54:14', NULL),
(265, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #27 was permanently deleted/anonymized by Admin User.', 'admin_user_audit.php?user_id=27', 0, 'high', '2025-10-22 07:58:24', NULL),
(266, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"123asdasda\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-22 08:02:44', '2025-10-22 16:46:13'),
(267, NULL, 3, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"123asdasda\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:02:44', NULL),
(268, NULL, 4, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"123asdasda\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:02:44', NULL),
(269, NULL, 5, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"123asdasda\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:02:44', NULL);
INSERT INTO `notifications` (`notification_id`, `user_id`, `admin_id`, `recipient_type`, `order_id`, `product_id`, `type`, `title`, `message`, `action_url`, `is_read`, `priority`, `created_at`, `read_at`) VALUES
(270, NULL, 1, '', NULL, NULL, '', 'New User Registered', 'A new user \'123asdasda\' was added by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 08:02:44', NULL),
(271, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #28 was permanently deleted by Admin User.', 'admin_users.php', 0, 'high', '2025-10-22 08:02:47', NULL),
(272, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-22 08:05:26', '2025-10-22 16:46:13'),
(273, NULL, 3, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:05:26', NULL),
(274, NULL, 4, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:05:26', NULL),
(275, NULL, 5, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"j3richoo\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:05:26', NULL),
(276, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #29 was permanently deleted by Admin User.', 'admin_users.php', 0, 'high', '2025-10-22 08:06:24', NULL),
(277, NULL, 1, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 1, 'low', '2025-10-22 08:20:49', '2025-10-22 16:46:13'),
(278, NULL, 3, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:20:49', NULL),
(279, NULL, 4, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:20:49', NULL),
(280, NULL, 5, 'admin', NULL, NULL, 'new_user_registered', 'New User Registered', 'New user \"ekoeko\" (ekojet1521@gmail.com) has registered.', '../admin/admin_users.php', 0, 'low', '2025-10-22 08:20:49', NULL),
(281, NULL, 1, '', NULL, NULL, '', 'New User Registered', 'A new user \'ekoeko\' was added by Admin User.', 'admin_users.php', 0, 'normal', '2025-10-22 08:20:49', NULL),
(282, 7, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product_details.php?id=55', 1, 'low', '2025-10-22 08:21:27', '2025-10-22 08:21:44'),
(283, 17, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product_details.php?id=55', 1, 'low', '2025-10-22 08:21:27', '2025-10-28 20:35:37'),
(284, 25, NULL, 'user', NULL, NULL, 'new_product', 'New Product Available!', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product_details.php?id=55', 0, 'low', '2025-10-22 08:21:27', NULL),
(285, 7, NULL, '', NULL, NULL, 'new_product', 'New Product Available! 🎉', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product.php', 0, 'low', '2025-10-22 08:21:27', NULL),
(286, 17, NULL, '', NULL, NULL, 'new_product', 'New Product Available! 🎉', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product.php', 0, 'low', '2025-10-22 08:21:27', NULL),
(287, 25, NULL, '', NULL, NULL, 'new_product', 'New Product Available! 🎉', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product.php', 0, 'low', '2025-10-22 08:21:27', NULL),
(288, 21, NULL, '', NULL, NULL, 'new_product', 'New Product Available! 🎉', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product.php', 0, 'low', '2025-10-22 08:21:27', NULL),
(289, 22, NULL, '', NULL, NULL, 'new_product', 'New Product Available! 🎉', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product.php', 0, 'low', '2025-10-22 08:21:27', NULL),
(290, 27, NULL, '', NULL, NULL, 'new_product', 'New Product Available! 🎉', 'Check out our new siomai: sdfasdfsadfsadfasdfsafsadfsa - ₱12.00', '../products/product.php', 0, 'low', '2025-10-22 08:21:27', NULL),
(298, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #30 was permanently deleted by Admin User.', 'admin_users.php', 0, 'high', '2025-10-22 08:23:41', NULL),
(299, NULL, 1, '', NULL, NULL, '', 'User Deleted', 'User ID #30 was permanently deleted by Admin User.', 'admin_users.php', 0, 'high', '2025-10-22 08:23:43', NULL),
(300, NULL, 1, 'admin', 47, NULL, 'order_placed', 'New Order Received', 'Order #47 has been placed. Total: ₱185.00', '../admin/admin_order_details.php?id=47', 1, 'high', '2025-10-22 08:25:01', '2025-10-22 16:46:13'),
(301, NULL, 3, 'admin', 47, NULL, 'order_placed', 'New Order Received', 'Order #47 has been placed. Total: ₱185.00', '../admin/admin_order_details.php?id=47', 0, 'high', '2025-10-22 08:25:01', NULL),
(302, NULL, 4, 'admin', 47, NULL, 'order_placed', 'New Order Received', 'Order #47 has been placed. Total: ₱185.00', '../admin/admin_order_details.php?id=47', 0, 'high', '2025-10-22 08:25:01', NULL),
(303, NULL, 5, 'admin', 47, NULL, 'order_placed', 'New Order Received', 'Order #47 has been placed. Total: ₱185.00', '../admin/admin_order_details.php?id=47', 0, 'high', '2025-10-22 08:25:01', NULL),
(304, 7, NULL, 'user', 47, NULL, 'order_delivered', 'Order Delivered', 'Your order #47 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=47', 0, 'high', '2025-10-22 08:25:17', NULL),
(305, 7, NULL, '', 47, NULL, 'order_delivered', 'Order Delivered', 'Your order has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=47', 0, 'high', '2025-10-22 08:25:17', NULL),
(306, 25, NULL, 'user', 44, NULL, 'order_delivered', 'Order Delivered', 'Your order #44 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=44', 0, 'high', '2025-10-22 08:26:38', NULL),
(307, 25, NULL, 'user', 43, NULL, 'order_delivered', 'Order Delivered', 'Your order #43 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=43', 0, 'high', '2025-10-22 08:26:38', NULL),
(308, 17, NULL, 'user', 42, NULL, 'order_delivered', 'Order Delivered', 'Your order #42 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=42', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(309, 17, NULL, 'user', 41, NULL, 'order_delivered', 'Order Delivered', 'Your order #41 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=41', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(310, 7, NULL, 'user', 40, NULL, 'order_delivered', 'Order Delivered', 'Your order #40 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=40', 0, 'high', '2025-10-22 08:26:38', NULL),
(311, 17, NULL, 'user', 35, NULL, 'order_delivered', 'Order Delivered', 'Your order #35 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=35', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(312, 7, NULL, 'user', 34, NULL, 'order_delivered', 'Order Delivered', 'Your order #34 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=34', 0, 'high', '2025-10-22 08:26:38', NULL),
(313, 7, NULL, 'user', 33, NULL, 'order_delivered', 'Order Delivered', 'Your order #33 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=33', 0, 'high', '2025-10-22 08:26:38', NULL),
(314, 7, NULL, 'user', 32, NULL, 'order_delivered', 'Order Delivered', 'Your order #32 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=32', 0, 'high', '2025-10-22 08:26:38', NULL),
(315, 7, NULL, 'user', 27, NULL, 'order_delivered', 'Order Delivered', 'Your order #27 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=27', 0, 'high', '2025-10-22 08:26:38', NULL),
(316, 7, NULL, 'user', 26, NULL, 'order_delivered', 'Order Delivered', 'Your order #26 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=26', 0, 'high', '2025-10-22 08:26:38', NULL),
(317, 7, NULL, 'user', 25, NULL, 'order_delivered', 'Order Delivered', 'Your order #25 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=25', 0, 'high', '2025-10-22 08:26:38', NULL),
(318, 7, NULL, 'user', 24, NULL, 'order_delivered', 'Order Delivered', 'Your order #24 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=24', 0, 'high', '2025-10-22 08:26:38', NULL),
(319, 7, NULL, 'user', 23, NULL, 'order_delivered', 'Order Delivered', 'Your order #23 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=23', 0, 'high', '2025-10-22 08:26:38', NULL),
(320, 7, NULL, 'user', 22, NULL, 'order_delivered', 'Order Delivered', 'Your order #22 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=22', 0, 'high', '2025-10-22 08:26:38', NULL),
(321, 7, NULL, 'user', 21, NULL, 'order_delivered', 'Order Delivered', 'Your order #21 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=21', 0, 'high', '2025-10-22 08:26:38', NULL),
(322, 7, NULL, 'user', 20, NULL, 'order_delivered', 'Order Delivered', 'Your order #20 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=20', 0, 'high', '2025-10-22 08:26:38', NULL),
(323, 7, NULL, 'user', 19, NULL, 'order_delivered', 'Order Delivered', 'Your order #19 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=19', 0, 'high', '2025-10-22 08:26:38', NULL),
(324, 7, NULL, 'user', 17, NULL, 'order_delivered', 'Order Delivered', 'Your order #17 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=17', 0, 'high', '2025-10-22 08:26:38', NULL),
(325, 7, NULL, 'user', 16, NULL, 'order_delivered', 'Order Delivered', 'Your order #16 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=16', 0, 'high', '2025-10-22 08:26:38', NULL),
(326, 7, NULL, 'user', 15, NULL, 'order_delivered', 'Order Delivered', 'Your order #15 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=15', 0, 'high', '2025-10-22 08:26:38', NULL),
(327, 7, NULL, 'user', 14, NULL, 'order_delivered', 'Order Delivered', 'Your order #14 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=14', 0, 'high', '2025-10-22 08:26:38', NULL),
(328, 7, NULL, 'user', 13, NULL, 'order_delivered', 'Order Delivered', 'Your order #13 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=13', 0, 'high', '2025-10-22 08:26:38', NULL),
(329, 7, NULL, 'user', 12, NULL, 'order_delivered', 'Order Delivered', 'Your order #12 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=12', 0, 'high', '2025-10-22 08:26:38', NULL),
(330, 17, NULL, 'user', 11, NULL, 'order_delivered', 'Order Delivered', 'Your order #11 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=11', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(331, 17, NULL, 'user', 10, NULL, 'order_delivered', 'Order Delivered', 'Your order #10 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=10', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(332, 17, NULL, 'user', 9, NULL, 'order_delivered', 'Order Delivered', 'Your order #9 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=9', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(333, 17, NULL, 'user', 8, NULL, 'order_delivered', 'Order Delivered', 'Your order #8 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=8', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(334, 17, NULL, 'user', 7, NULL, 'order_delivered', 'Order Delivered', 'Your order #7 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=7', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(335, 17, NULL, 'user', 6, NULL, 'order_delivered', 'Order Delivered', 'Your order #6 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=6', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(336, 17, NULL, 'user', 5, NULL, 'order_delivered', 'Order Delivered', 'Your order #5 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=5', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(337, 17, NULL, 'user', 4, NULL, 'order_delivered', 'Order Delivered', 'Your order #4 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=4', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(338, 17, NULL, 'user', 3, NULL, 'order_delivered', 'Order Delivered', 'Your order #3 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=3', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(339, 17, NULL, 'user', 2, NULL, 'order_delivered', 'Order Delivered', 'Your order #2 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=2', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(340, 17, NULL, 'user', 1, NULL, 'order_delivered', 'Order Delivered', 'Your order #1 has been delivered. Thank you for shopping with us!', '../cart/order_details.php?order_id=1', 1, 'high', '2025-10-22 08:26:38', '2025-10-28 20:35:37'),
(341, NULL, 1, 'admin', NULL, 41, 'low_stock_alert', 'Low Stock Alert', 'Product \"asd\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 1, 'urgent', '2025-10-22 08:29:39', '2025-10-22 16:46:13'),
(342, NULL, 3, 'admin', NULL, 41, 'low_stock_alert', 'Low Stock Alert', 'Product \"asd\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 0, 'urgent', '2025-10-22 08:29:39', NULL),
(343, NULL, 4, 'admin', NULL, 41, 'low_stock_alert', 'Low Stock Alert', 'Product \"asd\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 0, 'urgent', '2025-10-22 08:29:39', NULL),
(344, NULL, 5, 'admin', NULL, 41, 'low_stock_alert', 'Low Stock Alert', 'Product \"asd\" is running low. Only 10 units remaining.', '../admin/admin_products.php', 0, 'urgent', '2025-10-22 08:29:39', NULL),
(345, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:33', NULL),
(346, NULL, 3, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:35', NULL),
(347, NULL, 4, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:35', NULL),
(348, NULL, 5, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 6 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:35', NULL),
(349, NULL, 1, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:35', NULL),
(350, NULL, 3, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:37', NULL),
(351, NULL, 4, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:37', NULL),
(352, NULL, 5, '', NULL, NULL, '', 'Multiple Failed Login Attempts', 'Email \"jerichodelacruz\" has 7 failed login attempts and is temporarily locked for 1 minute.', '../admin/admin_users.php', 0, 'urgent', '2025-10-22 16:39:37', NULL),
(353, NULL, 1, 'admin', 48, NULL, 'order_placed', 'New Order Received', 'Order #48 has been placed. Total: ₱209.50', '../admin/admin_order_details.php?id=48', 1, 'high', '2025-10-28 20:46:12', '2025-10-28 20:46:57'),
(354, NULL, 3, 'admin', 48, NULL, 'order_placed', 'New Order Received', 'Order #48 has been placed. Total: ₱209.50', '../admin/admin_order_details.php?id=48', 0, 'high', '2025-10-28 20:46:12', NULL),
(355, NULL, 4, 'admin', 48, NULL, 'order_placed', 'New Order Received', 'Order #48 has been placed. Total: ₱209.50', '../admin/admin_order_details.php?id=48', 0, 'high', '2025-10-28 20:46:12', NULL),
(356, NULL, 5, 'admin', 48, NULL, 'order_placed', 'New Order Received', 'Order #48 has been placed. Total: ₱209.50', '../admin/admin_order_details.php?id=48', 0, 'high', '2025-10-28 20:46:12', NULL),
(357, NULL, 1, '', 48, NULL, 'promotion', 'Voucher Used in Order', 'Voucher \'SIOPAO30\' was used in order #48. Discount: ₱25.50', '../admin/admin_order_details.php?id=48', 0, 'low', '2025-10-28 20:46:12', NULL),
(358, NULL, 3, '', 48, NULL, 'promotion', 'Voucher Used in Order', 'Voucher \'SIOPAO30\' was used in order #48. Discount: ₱25.50', '../admin/admin_order_details.php?id=48', 0, 'low', '2025-10-28 20:46:14', NULL),
(359, NULL, 4, '', 48, NULL, 'promotion', 'Voucher Used in Order', 'Voucher \'SIOPAO30\' was used in order #48. Discount: ₱25.50', '../admin/admin_order_details.php?id=48', 0, 'low', '2025-10-28 20:46:14', NULL),
(360, NULL, 5, '', 48, NULL, 'promotion', 'Voucher Used in Order', 'Voucher \'SIOPAO30\' was used in order #48. Discount: ₱25.50', '../admin/admin_order_details.php?id=48', 0, 'low', '2025-10-28 20:46:14', NULL),
(361, 17, NULL, 'user', 48, NULL, 'order_shipped', 'Order Shipped!', 'Your order #48 is on its way! Track: TRK69012B94515B7', '../cart/order_details.php?order_id=48', 1, 'high', '2025-10-28 20:47:29', '2025-10-28 20:47:35'),
(362, 17, NULL, '', 48, NULL, 'order_shipped', 'Order Shipped!', 'Your order is on its way! Track: TRK69012B94515B7', '../cart/order_details.php?order_id=48', 0, 'high', '2025-10-28 20:47:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `push_notifications` tinyint(1) DEFAULT 1,
  `order_updates` tinyint(1) DEFAULT 1,
  `new_products` tinyint(1) DEFAULT 1,
  `promotions` tinyint(1) DEFAULT 1,
  `low_stock_alerts` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`id`, `user_id`, `admin_id`, `email_notifications`, `push_notifications`, `order_updates`, `new_products`, `promotions`, `low_stock_alerts`, `created_at`, `updated_at`) VALUES
(1, 7, NULL, 1, 1, 1, 1, 1, 1, '2025-10-20 12:59:37', '2025-10-20 12:59:37'),
(4, 17, NULL, 1, 1, 1, 1, 1, 1, '2025-10-20 12:59:37', '2025-10-20 12:59:37'),
(8, NULL, 1, 1, 1, 1, 1, 1, 1, '2025-10-20 12:59:37', '2025-10-20 12:59:37'),
(9, 25, NULL, 1, 1, 1, 1, 1, 1, '2025-10-21 15:50:32', '2025-10-21 15:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `pay_method` enum('cod','stripe') NOT NULL DEFAULT 'cod',
  `Courier` varchar(50) NOT NULL,
  `pay_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('processing','shipped','delivered','cancelled','return_requested','return_approved','return_rejected') DEFAULT 'processing',
  `subtotal` decimal(10,2) NOT NULL,
  `vat` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `voucher_code` varchar(50) DEFAULT NULL,
  `voucher_discount` decimal(10,2) DEFAULT 0.00,
  `stripe_payment_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `discount_percentage` int(11) DEFAULT 0,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `id_proof_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `tracking_number`, `address_line1`, `address_line2`, `barangay`, `city`, `province`, `postal_code`, `pay_method`, `Courier`, `pay_status`, `order_status`, `subtotal`, `vat`, `delivery_fee`, `total`, `voucher_code`, `voucher_discount`, `stripe_payment_id`, `created_at`, `discount_percentage`, `discount_amount`, `id_proof_path`) VALUES
(1, 17, 'TRK68dc0b49b0613', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 168.00, 20.16, 50.00, 238.16, NULL, 0.00, NULL, '2025-09-30 16:54:33', 0, 0.00, NULL),
(2, 17, 'TRK68dc0cc03a9f3', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 56.00, 6.72, 50.00, 112.72, NULL, 0.00, NULL, '2025-09-30 17:00:48', 0, 0.00, NULL),
(3, 17, 'TRK68dc0e2de06ca', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 56.00, 6.72, 50.00, 112.72, NULL, 0.00, NULL, '2025-09-30 17:06:53', 0, 0.00, NULL),
(4, 17, 'TRK68dc0eac0af5f', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 22.00, 2.64, 50.00, 74.64, NULL, 0.00, NULL, '2025-09-30 17:09:00', 0, 0.00, NULL),
(5, 17, 'TRK68dc0f2ca4429', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 28.00, 3.36, 50.00, 81.36, NULL, 0.00, NULL, '2025-09-30 17:11:08', 0, 0.00, NULL),
(6, 17, 'TRK68dc0f65263dc', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 0.00, 0.00, 50.00, 50.00, NULL, 0.00, NULL, '2025-09-30 17:12:05', 0, 0.00, NULL),
(7, 17, 'TRK68dc0f6855582', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 0.00, 0.00, 50.00, 50.00, NULL, 0.00, NULL, '2025-09-30 17:12:08', 0, 0.00, NULL),
(8, 17, 'TRK68dc0f70be9dd', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 0.00, 0.00, 50.00, 50.00, NULL, 0.00, NULL, '2025-09-30 17:12:16', 0, 0.00, NULL),
(9, 17, 'TRK68dc0fda3f8f1', '', NULL, '', '', '', '', 'cod', '', 'pending', 'delivered', 0.00, 0.00, 50.00, 50.00, NULL, 0.00, NULL, '2025-09-30 17:14:02', 0, 0.00, NULL),
(10, 17, 'TRK68dc126830f07', 'BLK 10 LOT 24', 'SUMMIT VIEW SUBD', 'SAN RAFAEL', 'Bacoor', 'Cavite', '1212', 'cod', 'Lalamove', 'pending', 'delivered', 70.00, 14.00, 50.00, 120.00, NULL, 0.00, NULL, '2025-09-30 17:24:56', 0, 0.00, NULL),
(11, 17, 'TRK68dc13bcc7de1', 'BL;K 10 LOT 24', 'SUMMIT VIEW', 'SAN RAFAEL', 'Quezon City', 'Metro Manila', '123123', 'cod', 'LBC Express', 'pending', 'delivered', 112.00, 22.40, 50.00, 162.00, NULL, 0.00, NULL, '2025-09-30 17:30:36', 0, 0.00, NULL),
(12, 7, 'TRK68dc815fa7b20', 'fsdfs', 'sdfsdf', 'sdfsdf', 'Quezon City', 'Metro Manila', '1231', 'cod', 'Lalamove', 'pending', 'delivered', 56.00, 11.20, 50.00, 106.00, NULL, 0.00, NULL, '2025-10-01 01:18:23', 0, 0.00, NULL),
(13, 7, 'TRK68dc81ae80c89', 'asd', 'asd', 'asdas', 'San Pedro', 'Laguna', 'asdasd', 'cod', 'Lalamove', 'pending', 'delivered', 56.00, 11.20, 50.00, 106.00, NULL, 0.00, NULL, '2025-10-01 01:19:42', 0, 0.00, NULL),
(14, 7, 'TRK68dc84678d2d3', 'asd', 'asd', 'asdas', 'Biñan', 'Laguna', 'asdasd', 'cod', 'Lalamove', 'pending', 'delivered', 28.00, 5.60, 50.00, 78.00, NULL, 0.00, NULL, '2025-10-01 01:31:19', 0, 0.00, NULL),
(15, 7, 'TRK68dc877585bd4', 'asd', 'asd', 'asdas', 'Biñan', 'Laguna', 'asdasd', 'cod', 'Lalamove', 'pending', 'delivered', 56.00, 11.20, 50.00, 106.00, NULL, 0.00, NULL, '2025-10-01 01:44:21', 0, 0.00, NULL),
(16, 7, 'TRK68dd4644ef929', 'asd', 'asd', 'asdas', 'San Pedro', 'Laguna', 'asdasd', 'cod', 'J&T Express', 'pending', 'delivered', 308.00, 61.60, 50.00, 358.00, NULL, 0.00, NULL, '2025-10-01 15:18:28', 0, 0.00, NULL),
(17, 7, 'TRK68DF1478CD80E', 'asda', 'asd', 'asdas', 'San Pedro', 'Laguna', '12312', 'stripe', 'J&T Express', 'paid', 'delivered', 41.67, 8.33, 50.00, 100.00, NULL, 0.00, 'ch_3SDwZw8CuwBmuHaz1ORdcQt7', '2025-10-03 00:10:32', 0, 0.00, NULL),
(18, 7, 'TRK68DF4D81763A9', 'asda', 'asd', 'asdas', 'San Pedro', 'Laguna', '12312', 'stripe', 'J&T Express', 'paid', 'delivered', 225.00, 45.00, 50.00, 320.00, NULL, 0.00, 'ch_3SE0NN8CuwBmuHaz1Bsz4F7G', '2025-10-03 04:13:53', 0, 0.00, NULL),
(19, 7, 'TRK68DF4EFFBC0EB', 'asda', 'asd', 'asdas', 'San Pedro', 'Laguna', '12312', 'stripe', 'J&T Express', 'paid', 'delivered', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, 'ch_3SE0Tb8CuwBmuHaz1Cx6AdWh', '2025-10-03 04:20:15', 0, 0.00, NULL),
(20, 7, 'TRK68DF4F62B5D71', 'asda', 'asd', 'asdas', 'San Pablo', 'Laguna', '12312', 'stripe', 'J&T Express', 'paid', 'delivered', 183.33, 36.67, 50.00, 270.00, NULL, 0.00, 'ch_3SE0VC8CuwBmuHaz0oGDzSaz', '2025-10-03 04:21:54', 0, 0.00, NULL),
(21, 7, 'TRK68DF503E673FB', 'asda', 'asd', 'asdas', 'San Pedro', 'Laguna', '12312', 'stripe', 'J&T Express', 'paid', 'delivered', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, 'ch_3SE0Yk8CuwBmuHaz1xN5Na99', '2025-10-03 04:25:34', 0, 0.00, NULL),
(22, 7, 'TRK68E7CF566FC65', '59 B manansala st', 'N/A', 'U.P. Campus', '137404000', '', '1123', 'cod', 'Lalamove', 'pending', 'delivered', 55.00, 11.00, 50.00, 116.00, NULL, 0.00, NULL, '2025-10-09 15:05:58', 0, 0.00, NULL),
(23, 7, 'TRK68E7D2268EB2D', '59 B manansala st', 'N/A', 'U.P. Campus', '137404000', '', '1123', 'cod', 'Lalamove', 'pending', 'delivered', 18.33, 3.67, 50.00, 72.00, NULL, 0.00, NULL, '2025-10-09 15:17:58', 0, 0.00, NULL),
(24, 7, 'TRK68E7D40C49F45', '59 B manansala st', 'N/A', 'U.P. Campus', '137404000', '', '1123', 'cod', 'Lalamove', 'pending', 'delivered', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, NULL, '2025-10-09 15:26:04', 0, 0.00, NULL),
(25, 7, 'TRK68E7D4E8A7EAE', '59 B manansala st', 'N/A', 'U.P. Campus', '137404000', '', '1123', 'cod', 'Lalamove', 'pending', 'delivered', 18.33, 3.67, 50.00, 72.00, NULL, 0.00, NULL, '2025-10-09 15:29:44', 0, 0.00, NULL),
(26, 7, 'TRK68F209096EEB3', 'asda', 'asd', 'sadasd', 'Biñan', 'Laguna', '12312', 'cod', 'Lalamove', 'pending', 'delivered', 36.67, 7.33, 50.00, 94.00, NULL, 0.00, NULL, '2025-10-17 09:14:49', 0, 0.00, NULL),
(27, 7, 'TRK68F32DA1C371C', 'asda', 'asd', 'sadasd', 'San Pedro', 'Laguna', '12312', 'cod', 'Lalamove', 'pending', 'delivered', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, NULL, '2025-10-18 06:03:13', 0, 0.00, NULL),
(28, 7, 'TRK68F356E72A833', 'asda', 'asd', 'sadasd', 'San Pedro', 'Laguna', '12312', 'stripe', 'J&T Express', 'paid', 'return_rejected', 18.33, 3.67, 50.00, 72.00, NULL, 0.00, 'ch_3SJVyp8CuwBmuHaz0TV4h0Ry', '2025-10-18 08:59:19', 0, 0.00, NULL),
(29, 7, 'TRK68F359BD6AB45', 'asda', 'asd', 'sadasd', 'San Pedro', 'Laguna', '12312', 'stripe', 'Lalamove', 'paid', 'return_approved', 125.00, 25.00, 50.00, 200.00, NULL, 0.00, 'ch_3SJWAc8CuwBmuHaz1JuSRjCz', '2025-10-18 09:11:25', 0, 0.00, NULL),
(30, 7, 'TRK68F3778665F39', 'asda', 'asd', 'sadasd', 'Biñan', 'Laguna', '12312', 'cod', 'J&T Express', 'pending', 'return_approved', 73.33, 14.67, 50.00, 138.00, NULL, 0.00, NULL, '2025-10-18 11:18:30', 0, 0.00, NULL),
(31, 7, 'TRK68F37914105BF', 'asda', 'asd', 'sadasd', 'Biñan', 'Laguna', '12312', 'stripe', 'Lalamove', 'paid', 'return_rejected', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, 'ch_3SJYG08CuwBmuHaz1pK2ewxK', '2025-10-18 11:25:08', 0, 0.00, NULL),
(32, 7, 'TRK68F498B1BB302', 'asdfsdfsafsadfsafdsadf', 'asda', 'asdas', 'Imus', 'Cavite', '231231', 'stripe', 'Lalamove', 'paid', 'delivered', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, 'ch_3SJrPV8CuwBmuHaz0cH6VdLM', '2025-10-19 07:52:17', 0, 0.00, NULL),
(33, 7, 'TRK68F4995828778', 'asdfsdfsafsadfsafdsadf', 'asda', 'asdas', 'General Trias', 'Cavite', '123312', 'stripe', 'Lalamove', 'paid', 'delivered', 18.33, 3.67, 50.00, 72.00, NULL, 0.00, 'ch_3SJrSG8CuwBmuHaz0J70dP5s', '2025-10-19 07:55:04', 0, 0.00, NULL),
(34, 7, 'TRK68F4A21B65A03', 'asdfsdfsafsadfsafdsadf', 'asda', 'asdas', 'Bacoor', 'Cavite', '123312', 'stripe', 'J&T Express', 'paid', 'delivered', 3483.33, 696.67, 50.00, 4230.00, NULL, 0.00, 'ch_3SJs2R8CuwBmuHaz09bmIJGD', '2025-10-19 08:32:27', 0, 0.00, NULL),
(35, 17, 'TRK68F53C38A202D', 'BLK 1 Lot 79, Maginhawa Subdivision', 'Topaz Street', 'SAN RAFAEL', 'Imus', 'Cavite', '1860', 'cod', 'J&T Express', 'pending', 'delivered', 1023.33, 204.67, 50.00, 1278.00, NULL, 0.00, NULL, '2025-10-19 19:30:00', 0, 0.00, NULL),
(36, 21, 'TRK68F68658E6E68', '59 B Manansala ST', 'Topaz Street', 'SAN RAFAEL', 'Pasig', 'Metro Manila', '1860', 'cod', 'LBC Express', 'pending', 'cancelled', 70.00, 14.00, 50.00, 134.00, NULL, 0.00, NULL, '2025-10-20 18:58:32', 0, 0.00, NULL),
(37, 21, 'TRK68F7099AA3DDC', '59 B Manansala ST', 'SUMMIT VIEW SUBD', 'SAN RAFAEL', 'Quezon City', 'Metro Manila', '1234', 'stripe', 'LBC Express', 'paid', 'return_rejected', 150.00, 30.00, 50.00, 230.00, NULL, 0.00, 'ch_3SKX1p8CuwBmuHaz1HvROExC', '2025-10-21 04:18:34', 0, 0.00, NULL),
(38, 21, 'TRK68F70CBABA194', 'BLK 1 Lot 79, Maginhawa Subdivision', 'SUMMIT VIEW SUBD', 'SAN RAFAEL', 'Manila', 'Metro Manila', '1860', 'cod', 'J&T Express', 'pending', 'delivered', 791.67, 158.33, 50.00, 1000.00, NULL, 0.00, NULL, '2025-10-21 04:31:54', 0, 0.00, NULL),
(39, 7, 'TRK68F790FB80F41', 'asdfsdfsafsadfsafdsadf', 'asda', 'asdas', 'Dasmariñas', 'Cavite', '123312', 'cod', 'J&T Express', 'pending', 'cancelled', 75.00, 15.00, 50.00, 140.00, NULL, 0.00, NULL, '2025-10-21 13:56:11', 0, 0.00, NULL),
(40, 7, 'TRK68F799E912CC4', 'asdfsdfsafsadfsafdsadf', 'asda', 'asdas', 'Dasmariñas', 'Cavite', '123312', 'cod', 'LBC Express', 'pending', 'delivered', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, NULL, '2025-10-21 14:34:17', 0, 0.00, NULL),
(41, 17, 'TRK68F844D119A66', 'BLK 10 LOT 24', 'SUMMIT VIEW SUBD', 'San Makati', 'Makati', '', '1201', 'cod', 'LBC Express', 'pending', 'delivered', 23.33, 4.67, 50.00, 78.00, NULL, 0.00, NULL, '2025-10-22 02:43:29', 0, 0.00, NULL),
(42, 17, 'TRK68F859D25CBFA', 'BLK 10 LOT 24', 'SUMMIT VIEW SUBD', 'San Makati', 'Makati', '', '1201', 'stripe', 'LBC Express', 'paid', 'delivered', 43.33, 8.67, 50.00, 102.00, NULL, 0.00, 'ch_3SKtQ58CuwBmuHaz0uOErbjI', '2025-10-22 04:13:06', 0, 0.00, NULL),
(43, 25, 'TRK68F86288DE9F3', '59 B manansala St up diliman qc', 'asda', 'UP Campus', 'Quezon City', '', '1101', 'cod', 'J&T Express', 'pending', 'delivered', 102.50, 20.50, 50.00, 173.00, NULL, 0.00, NULL, '2025-10-22 04:50:16', 0, 0.00, NULL),
(44, 25, 'TRK68F8641A0B3E1', '59 B manansala St up diliman qc', '', 'UP Campus', 'Quezon City', '', '1101', 'stripe', 'J&T Express', 'paid', 'delivered', 37.50, 7.50, 50.00, 95.00, NULL, 0.00, 'ch_3SKu6X8CuwBmuHaz0fR69Nyl', '2025-10-22 04:56:58', 0, 0.00, NULL),
(45, 25, 'TRK68F86B9B30AB0', '59 B manansala St up diliman qc', '', 'UP Campus', 'Quezon City', '', '1101', 'cod', 'J&T Express', 'pending', 'return_rejected', 205.00, 41.00, 50.00, 296.00, NULL, 0.00, NULL, '2025-10-22 05:28:59', 0, 0.00, NULL),
(46, 27, 'TRK68F887598A068', '59 B manansala St up diliman qc', '', 'UP Campus', 'Quezon City', '', '1101', 'cod', 'J&T Express', 'pending', 'cancelled', 18.33, 3.67, 50.00, 72.00, NULL, 0.00, NULL, '2025-10-22 07:27:21', 0, 0.00, NULL),
(47, 7, 'TRK68F894DD70921', '59 B manansala St up diliman qc', '', 'UP Campus', 'Quezon City', '', '1101', 'cod', 'J&T Express', 'pending', 'delivered', 112.50, 22.50, 50.00, 185.00, NULL, 0.00, NULL, '2025-10-22 08:25:01', 0, 0.00, NULL),
(48, 17, 'TRK69012B94515B7', 'BLK 10 LOT 24', 'SUMMIT VIEW SUBD', 'San Makati', 'Makati', '', '1201', '', 'LBC Express', 'pending', 'shipped', 154.17, 30.83, 50.00, 209.50, 'SIOPAO30', 25.50, NULL, '2025-10-28 20:46:12', 0, 0.00, NULL);

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_status_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
  IF NEW.order_status != OLD.order_status THEN
    INSERT INTO order_tracking (order_id, status, notes, created_at)
    VALUES (NEW.order_id, NEW.order_status, 
            CONCAT('Status changed from ', OLD.order_status, ' to ', NEW.order_status),
            NOW());
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `notify_admin_new_order` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
  DECLARE admin_cursor_done INT DEFAULT FALSE;
  DECLARE admin_id_var INT;
  DECLARE admin_cursor CURSOR FOR SELECT id FROM admins WHERE status = 'active';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET admin_cursor_done = TRUE;
  
  OPEN admin_cursor;
  admin_loop: LOOP
    FETCH admin_cursor INTO admin_id_var;
    IF admin_cursor_done THEN
      LEAVE admin_loop;
    END IF;
    
    INSERT INTO notifications (
      admin_id, 
      recipient_type, 
      order_id, 
      type, 
      title, 
      message, 
      action_url, 
      priority
    ) VALUES (
      admin_id_var,
      'admin',
      NEW.order_id,
      'order_placed',
      'New Order Received',
      CONCAT('Order #', NEW.order_id, ' has been placed. Total: ₱', FORMAT(NEW.total, 2)),
      CONCAT('../admin/admin_order_details.php?id=', NEW.order_id),
      'high'
    );
  END LOOP;
  CLOSE admin_cursor;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `notify_user_order_status` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
  IF NEW.order_status != OLD.order_status THEN
    INSERT INTO notifications (
      user_id,
      recipient_type,
      order_id,
      type,
      title,
      message,
      action_url,
      priority
    ) VALUES (
      NEW.user_id,
      'user',
      NEW.order_id,
      CASE NEW.order_status
        WHEN 'processing' THEN 'order_processing'
        WHEN 'shipped' THEN 'order_shipped'
        WHEN 'delivered' THEN 'order_delivered'
        WHEN 'cancelled' THEN 'order_cancelled'
        ELSE 'order_processing'
      END,
      CASE NEW.order_status
        WHEN 'processing' THEN 'Order is Being Processed'
        WHEN 'shipped' THEN 'Order Shipped!'
        WHEN 'delivered' THEN 'Order Delivered'
        WHEN 'cancelled' THEN 'Order Cancelled'
        ELSE 'Order Update'
      END,
      CASE NEW.order_status
        WHEN 'processing' THEN CONCAT('Your order #', NEW.order_id, ' is now being processed.')
        WHEN 'shipped' THEN CONCAT('Your order #', NEW.order_id, ' is on its way! Track: ', NEW.tracking_number)
        WHEN 'delivered' THEN CONCAT('Your order #', NEW.order_id, ' has been delivered. Thank you for shopping with us!')
        WHEN 'cancelled' THEN CONCAT('Your order #', NEW.order_id, ' has been cancelled.')
        ELSE CONCAT('Order #', NEW.order_id, ' status updated to ', NEW.order_status)
      END,
      CONCAT('../cart/order_details.php?order_id=', NEW.order_id),
      CASE NEW.order_status
        WHEN 'shipped' THEN 'high'
        WHEN 'delivered' THEN 'high'
        ELSE 'normal'
      END
    );
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `notify_user_payment_success` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
  IF NEW.pay_status = 'paid' AND OLD.pay_status != 'paid' THEN
    INSERT INTO notifications (
      user_id,
      recipient_type,
      order_id,
      type,
      title,
      message,
      action_url,
      priority
    ) VALUES (
      NEW.user_id,
      'user',
      NEW.order_id,
      'payment_success',
      'Payment Confirmed',
      CONCAT('Payment of ₱', FORMAT(NEW.total, 2), ' for order #', NEW.order_id, ' has been confirmed.'),
      CONCAT('../cart/order_details.php?order_id=', NEW.order_id),
      'high'
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `product_name`, `quantity`, `price_at_time`, `price`) VALUES
(1, 1, 8, 'Bola-Bola Siopao', 4, 0.00, 42.00),
(2, 2, 3, 'Beef Siomai', 2, 0.00, 28.00),
(3, 3, 3, 'Beef Siomai', 2, 0.00, 28.00),
(4, 4, 2, 'Chicken Siomai', 1, 0.00, 22.00),
(5, 5, 3, 'Beef Siomai', 1, 0.00, 28.00),
(6, 17, 3, 'Beef Siomai', 1, 28.00, 0.00),
(7, 17, 2, 'Chicken Siomai', 1, 22.00, 0.00),
(8, 18, 6, 'Japanese Siomai', 5, 32.00, 0.00),
(9, 18, 2, 'Chicken Siomai', 5, 22.00, 0.00),
(10, 19, 3, 'Beef Siomai', 1, 28.00, 0.00),
(11, 20, 2, 'Chicken Siomai', 10, 22.00, 0.00),
(12, 21, 3, 'Beef Siomai', 1, 28.00, 0.00),
(13, 22, 2, 'Chicken Siomai', 3, 22.00, 0.00),
(14, 23, 2, 'Chicken Siomai', 1, 22.00, 0.00),
(15, 24, 3, 'Beef Siomai', 1, 28.00, 0.00),
(16, 25, 2, 'Chicken Siomai', 1, 22.00, 0.00),
(17, 26, 2, 'Chicken Siomai', 2, 22.00, 0.00),
(18, 27, 3, 'Beef Siomai', 1, 28.00, 0.00),
(19, 28, 2, 'Chicken Siomai', 1, 22.00, 0.00),
(20, 29, 1, 'Pork Siomai', 6, 25.00, 0.00),
(21, 30, 2, 'Chicken Siomai', 4, 22.00, 0.00),
(22, 31, 3, 'Beef Siomai', 1, 28.00, 0.00),
(23, 32, 3, 'Beef Siomai', 1, 28.00, 0.00),
(24, 33, 2, 'Chicken Siomai', 1, 22.00, 0.00),
(25, 34, 3, 'Beef Siomai', 1, 28.00, 0.00),
(26, 34, 36, 'Party Pack Deluxe - 160pcs Siomai + 80pcs Siopao', 1, 3800.00, 0.00),
(27, 34, 6, 'Japanese Siomai', 11, 32.00, 0.00),
(28, 35, 3, 'Beef Siomai', 1, 28.00, 0.00),
(29, 35, 24, 'Siomai Bundle 80pcs - Pork', 1, 1200.00, 0.00),
(30, 36, 3, 'Beef Siomai', 3, 28.00, 0.00),
(31, 37, 4, 'Tuna Siomai', 6, 30.00, 0.00),
(32, 38, 34, 'Siopao Bundle 40pcs - Mixed Flavors', 1, 950.00, 0.00),
(33, 39, 4, 'Tuna Siomai', 3, 30.00, 0.00),
(34, 40, 3, 'Beef Siomai', 1, 28.00, 0.00),
(35, 41, 3, 'Beef Siomai', 1, 28.00, 0.00),
(36, 42, 4, 'Tuna Siomai', 1, 30.00, 0.00),
(37, 42, 2, 'Chicken Siomai', 1, 22.00, 0.00),
(38, 43, 41, 'asd', 1, 123.00, 0.00),
(39, 44, 22, 'Dela Cruz Jericho', 1, 45.00, 0.00),
(40, 45, 41, 'asd', 2, 123.00, 0.00),
(41, 46, 2, 'Chicken Siomai', 1, 22.00, 0.00),
(43, 47, 41, 'asd', 1, 123.00, 0.00),
(44, 48, 3, 'Beef Siomai', 2, 28.00, 0.00),
(45, 48, 2, 'Chicken Siomai', 2, 22.00, 0.00),
(46, 48, 7, 'Asado Siopao', 1, 45.00, 0.00),
(47, 48, 10, 'Ube Siopao', 1, 40.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `tracking_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('processing','shipped','delivered','cancelled') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL COMMENT 'Admin user ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`tracking_id`, `order_id`, `status`, `location`, `notes`, `updated_by`, `created_at`) VALUES
(1, 12, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-03 00:12:43'),
(2, 17, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-03 00:35:39'),
(3, 18, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-03 04:14:24'),
(4, 21, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-09 14:07:12'),
(5, 21, 'shipped', NULL, 'Status changed from cancelled to shipped', NULL, '2025-10-09 14:07:19'),
(6, 22, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-09 15:06:32'),
(7, 24, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-09 15:26:30'),
(8, 25, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-09 15:30:10'),
(9, 25, 'delivered', NULL, 'Status changed from cancelled to delivered', NULL, '2025-10-15 08:11:42'),
(10, 25, 'cancelled', NULL, 'Status changed from delivered to cancelled', NULL, '2025-10-15 08:12:08'),
(11, 25, 'delivered', NULL, 'Status changed from cancelled to delivered', NULL, '2025-10-15 09:13:27'),
(12, 25, 'shipped', NULL, 'Status changed from delivered to shipped', NULL, '2025-10-15 12:30:47'),
(13, 25, 'delivered', NULL, 'Status changed from shipped to delivered', NULL, '2025-10-15 12:31:16'),
(14, 25, '', NULL, 'Status changed from delivered to ', NULL, '2025-10-15 12:53:40'),
(15, 23, 'shipped', NULL, 'Status changed from processing to shipped', NULL, '2025-10-15 13:09:24'),
(16, 25, 'shipped', NULL, 'Status changed from  to shipped', NULL, '2025-10-15 13:09:43'),
(17, 18, '', NULL, 'Status changed from delivered to ', NULL, '2025-10-15 15:01:59'),
(18, 27, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-18 06:05:20'),
(19, 17, '', NULL, 'Status changed from delivered to ', NULL, '2025-10-18 09:27:32'),
(20, 27, 'processing', NULL, 'Status changed from cancelled to processing', NULL, '2025-10-18 09:31:52'),
(21, 25, 'processing', NULL, 'Status changed from shipped to processing', NULL, '2025-10-18 09:31:52'),
(22, 24, 'processing', NULL, 'Status changed from cancelled to processing', NULL, '2025-10-18 09:31:52'),
(23, 23, 'processing', NULL, 'Status changed from shipped to processing', NULL, '2025-10-18 09:31:52'),
(24, 22, 'processing', NULL, 'Status changed from cancelled to processing', NULL, '2025-10-18 09:31:52'),
(25, 21, 'processing', NULL, 'Status changed from shipped to processing', NULL, '2025-10-18 09:31:52'),
(26, 18, 'processing', NULL, 'Status changed from  to processing', NULL, '2025-10-18 09:31:52'),
(27, 17, 'processing', NULL, 'Status changed from  to processing', NULL, '2025-10-18 09:31:52'),
(28, 16, 'processing', NULL, 'Status changed from delivered to processing', NULL, '2025-10-18 09:31:52'),
(29, 14, 'processing', NULL, 'Status changed from shipped to processing', NULL, '2025-10-18 09:31:52'),
(30, 12, 'processing', NULL, 'Status changed from cancelled to processing', NULL, '2025-10-18 09:31:52'),
(31, 29, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-18 09:32:09'),
(32, 28, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-18 09:37:56'),
(33, 18, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-18 09:39:59'),
(34, 29, '', NULL, 'Status changed from delivered to return_requested', NULL, '2025-10-18 10:36:39'),
(35, 29, '', NULL, 'Return Pending: asdasda', NULL, '2025-10-18 10:37:01'),
(44, 29, '', NULL, 'Status changed from return_requested to return_approved', NULL, '2025-10-18 10:57:08'),
(45, 29, '', NULL, 'Return Approved: asdasda', NULL, '2025-10-18 10:57:08'),
(46, 30, 'shipped', NULL, 'Status changed from processing to shipped', NULL, '2025-10-18 11:18:51'),
(47, 30, 'delivered', NULL, 'Status changed from shipped to delivered', NULL, '2025-10-18 11:19:09'),
(48, 30, '', NULL, 'Status changed from delivered to return_requested', NULL, '2025-10-18 11:19:42'),
(49, 30, '', NULL, 'Status changed from return_requested to return_approved', NULL, '2025-10-18 11:20:27'),
(50, 30, '', NULL, 'Return Approved: ', NULL, '2025-10-18 11:20:27'),
(51, 31, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-18 11:25:31'),
(52, 31, '', NULL, 'Status changed from delivered to return_requested', NULL, '2025-10-18 11:26:31'),
(53, 31, '', NULL, 'Status changed from return_requested to return_rejected', NULL, '2025-10-18 11:26:54'),
(54, 31, '', NULL, 'Return Rejected: hmnhgmbm', NULL, '2025-10-18 11:26:54'),
(55, 28, '', NULL, 'Status changed from delivered to return_requested', NULL, '2025-10-18 12:05:44'),
(56, 28, '', NULL, 'Status changed from return_requested to return_rejected', NULL, '2025-10-18 12:06:24'),
(57, 28, '', NULL, 'Return Rejected: trfhfghfg', NULL, '2025-10-18 12:06:24'),
(58, 36, 'shipped', NULL, 'Status changed from processing to shipped', NULL, '2025-10-21 03:44:48'),
(59, 36, 'cancelled', NULL, 'Status changed from shipped to cancelled', NULL, '2025-10-21 03:52:55'),
(60, 36, 'delivered', NULL, 'Status changed from cancelled to delivered', NULL, '2025-10-21 03:58:08'),
(61, 36, 'shipped', NULL, 'Status changed from delivered to shipped', NULL, '2025-10-21 03:59:11'),
(62, 36, 'processing', NULL, 'Status changed from shipped to processing', NULL, '2025-10-21 03:59:23'),
(63, 36, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-21 03:59:31'),
(64, 37, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-21 04:19:04'),
(65, 37, '', NULL, 'Status changed from delivered to return_requested', NULL, '2025-10-21 04:19:35'),
(66, 38, 'cancelled', NULL, 'Status changed from processing to cancelled', NULL, '2025-10-21 08:12:14'),
(69, 37, '', NULL, 'Status changed from return_requested to return_approved', NULL, '2025-10-21 08:53:56'),
(70, 37, '', NULL, 'Return Approved: ', NULL, '2025-10-21 08:53:56'),
(71, 37, '', NULL, 'Status changed from return_approved to return_rejected', NULL, '2025-10-21 08:55:25'),
(72, 37, '', NULL, 'Return Rejected: ', NULL, '2025-10-21 08:55:25'),
(73, 38, 'processing', NULL, 'Status changed from cancelled to processing', NULL, '2025-10-21 08:56:56'),
(74, 38, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-21 08:57:03'),
(75, 39, 'shipped', NULL, 'Status changed from processing to shipped', NULL, '2025-10-21 13:57:05'),
(76, 39, 'processing', NULL, 'Status changed from shipped to processing', NULL, '2025-10-21 13:57:36'),
(77, 39, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-21 13:57:40'),
(78, 39, 'cancelled', NULL, 'Status changed from delivered to cancelled', NULL, '2025-10-21 14:28:40'),
(79, 44, 'shipped', NULL, 'Status changed from processing to shipped', NULL, '2025-10-22 05:09:17'),
(80, 45, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 05:29:56'),
(81, 45, '', NULL, 'Status changed from delivered to return_requested', NULL, '2025-10-22 05:30:53'),
(82, 45, '', NULL, 'Status changed from return_requested to return_approved', NULL, '2025-10-22 05:31:24'),
(83, 45, '', NULL, 'Return Approved: sge po', NULL, '2025-10-22 05:31:24'),
(84, 44, 'processing', NULL, 'Status changed from shipped to processing', NULL, '2025-10-22 06:51:46'),
(85, 45, '', NULL, 'Status changed from return_approved to return_rejected', NULL, '2025-10-22 06:57:41'),
(86, 45, '', NULL, 'Return Rejected: sge po', NULL, '2025-10-22 06:57:41'),
(87, 46, 'shipped', NULL, 'Status changed from processing to shipped', NULL, '2025-10-22 07:28:17'),
(88, 46, 'delivered', NULL, 'Status changed from shipped to delivered', NULL, '2025-10-22 07:43:24'),
(89, 46, 'cancelled', NULL, 'Status changed from delivered to cancelled', NULL, '2025-10-22 07:45:40'),
(90, 47, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:25:17'),
(91, 44, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(92, 43, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(93, 42, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(94, 41, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(95, 40, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(96, 35, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(97, 34, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(98, 33, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(99, 32, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(100, 27, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(101, 26, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(102, 25, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(103, 24, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(104, 23, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(105, 22, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(106, 21, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(107, 20, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(108, 19, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(109, 17, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(110, 16, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(111, 15, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(112, 14, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(113, 13, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(114, 12, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(115, 11, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(116, 10, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(117, 9, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(118, 8, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(119, 7, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(120, 6, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(121, 5, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(122, 4, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(123, 3, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(124, 2, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(125, 1, 'delivered', NULL, 'Status changed from processing to delivered', NULL, '2025-10-22 08:26:38'),
(126, 48, 'shipped', NULL, 'Status changed from processing to shipped', NULL, '2025-10-28 20:47:29');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `otp_type` enum('registration','password_reset','profile_change') NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `email`, `otp_code`, `otp_type`, `is_verified`, `expires_at`, `created_at`, `verified_at`) VALUES
(81, 'gekkoretto@gmail.com', '996267', 'registration', 0, '2025-09-23 06:21:37', '2025-09-23 12:19:37', NULL),
(82, 'gekkoretto@gmail.com', '891824', 'registration', 1, '2025-09-23 12:23:11', '2025-09-23 12:22:35', '2025-09-23 12:23:11'),
(83, 'gekkoretto@gmail.com', '940982', 'registration', 1, '2025-09-23 12:25:02', '2025-09-23 12:24:40', '2025-09-23 12:25:02'),
(84, 'brandonverro@gmail.com', '505327', 'registration', 0, '2025-09-23 06:31:07', '2025-09-23 12:29:07', NULL),
(85, 'brandonverro@gmail.com', '558791', 'registration', 0, '2025-09-23 06:31:33', '2025-09-23 12:29:33', NULL),
(86, 'brandonverro@gmail.com', '246284', 'registration', 0, '2025-09-23 06:31:54', '2025-09-23 12:29:54', NULL),
(87, 'brandonverro@gmail.com', '477901', 'registration', 0, '2025-09-23 06:32:29', '2025-09-23 12:30:29', NULL),
(88, 'brandonverro@gmail.com', '191613', 'registration', 0, '2025-09-23 06:33:00', '2025-09-23 12:31:00', NULL),
(89, 'brandonverro@gmail.com', '139466', 'registration', 1, '2025-09-23 12:31:53', '2025-09-23 12:31:37', '2025-09-23 12:31:53'),
(90, 'brandonverro@gmail.com', '380556', 'registration', 0, '2025-09-23 06:34:18', '2025-09-23 12:32:18', NULL),
(91, 'brandonverro@gmail.com', '528786', 'registration', 1, '2025-09-23 12:32:45', '2025-09-23 12:32:24', '2025-09-23 12:32:45'),
(92, 'brandonverro@gmail.com', '972414', 'registration', 0, '2025-09-23 06:35:01', '2025-09-23 12:33:01', NULL),
(93, 'brandonverro@gmail.com', '358439', 'registration', 1, '2025-09-23 12:33:50', '2025-09-23 12:33:30', '2025-09-23 12:33:50'),
(94, 'brandonverro@gmail.com', '250603', 'registration', 0, '2025-09-23 06:36:18', '2025-09-23 12:34:18', NULL),
(95, 'brandonverro@gmail.com', '408088', 'registration', 1, '2025-09-23 12:34:53', '2025-09-23 12:34:37', '2025-09-23 12:34:53'),
(96, 'brandonverro@gmail.com', '336393', 'registration', 0, '2025-09-23 06:37:39', '2025-09-23 12:35:39', NULL),
(97, 'brandonverro@gmail.com', '991350', 'registration', 1, '2025-09-23 12:36:15', '2025-09-23 12:35:59', '2025-09-23 12:36:15'),
(98, 'brandonverro@gmail.com', '162516', 'registration', 0, '2025-09-28 07:56:55', '2025-09-28 13:54:55', NULL),
(99, 'brandonverro@gmail.com', '878212', 'registration', 1, '2025-09-28 13:56:07', '2025-09-28 13:55:45', '2025-09-28 13:56:07'),
(105, 'makoymilanez@gmail.com', '248971', 'registration', 0, '2025-09-28 12:44:46', '2025-09-28 18:38:46', NULL),
(106, 'makoymilanez@gmail.com', '218662', 'registration', 1, '2025-09-28 18:39:28', '2025-09-28 18:39:15', '2025-09-28 18:39:28'),
(107, 'makoymilanez@gmail.com', '555544', 'registration', 1, '2025-09-28 18:44:09', '2025-09-28 18:43:51', '2025-09-28 18:44:09'),
(108, 'makoymilanez@gmail.com', '756455', 'registration', 0, '2025-09-29 08:23:06', '2025-09-29 14:21:06', NULL),
(109, 'kokoymilanez@gmail.com', '637319', 'registration', 0, '2025-09-30 00:01:43', '2025-09-30 05:59:43', NULL),
(110, 'kokoymilanez@gmail.com', '939719', 'registration', 0, '2025-09-30 00:04:34', '2025-09-30 06:02:34', NULL),
(111, 'qjapdelacruz@tip.edu.ph', '764616', 'registration', 0, '2025-09-30 19:17:23', '2025-10-01 01:15:23', NULL),
(112, 'qjapdelacruz@tip.edu.ph', '236756', 'registration', 1, '2025-10-01 01:15:50', '2025-10-01 01:15:39', '2025-10-01 01:15:50'),
(113, 'ekojet1521@gmail.com', '712412', 'registration', 0, '2025-10-02 23:03:46', '2025-10-03 05:01:46', NULL),
(114, 'ekojet1521@gmail.com', '508248', 'registration', 1, '2025-10-03 05:02:57', '2025-10-03 05:02:30', '2025-10-03 05:02:57'),
(115, 'ekojet1521@gmail.com', '784220', 'registration', 0, '2025-10-02 23:10:23', '2025-10-03 05:08:23', NULL),
(116, 'ekojet1521@gmail.com', '180600', 'registration', 1, '2025-10-03 05:08:48', '2025-10-03 05:08:37', '2025-10-03 05:08:48'),
(117, 'marcomilanez08@gmail.com', '296950', 'registration', 0, '2025-10-20 12:58:45', '2025-10-20 18:56:45', NULL),
(118, 'marcomilanez08@gmail.com', '265103', 'registration', 1, '2025-10-20 18:57:40', '2025-10-20 18:57:27', '2025-10-20 18:57:40'),
(119, 'ekojet1521@gmail.com', '246936', 'registration', 0, '2025-10-21 09:24:29', '2025-10-21 15:22:29', NULL),
(120, 'ekojet1521@gmail.com', '487978', 'registration', 1, '2025-10-21 15:23:06', '2025-10-21 15:22:55', '2025-10-21 15:23:06'),
(127, 'makoymilanez@gmail.com', '480524', 'profile_change', 1, '2025-10-22 02:17:53', '2025-10-22 02:16:32', '2025-10-22 02:17:53'),
(129, 'thefoolm4acc@gmail.com', '290069', 'profile_change', 1, '2025-10-22 02:18:56', '2025-10-22 02:18:28', '2025-10-22 02:18:56'),
(131, 'thefoolm4acc@gmail.com', '674354', 'profile_change', 1, '2025-10-22 02:33:39', '2025-10-22 02:33:17', '2025-10-22 02:33:39'),
(132, 'thefoolm4acc@gmail.com', '101198', 'profile_change', 1, '2025-10-22 02:39:32', '2025-10-22 02:39:07', '2025-10-22 02:39:32'),
(133, 'thefoolm4acc@gmail.com', '308501', 'profile_change', 1, '2025-10-22 02:39:54', '2025-10-22 02:39:39', '2025-10-22 02:39:54'),
(137, 'brandonverro@gmail.com', '119544', 'profile_change', 1, '2025-10-22 04:52:58', '2025-10-22 04:52:40', '2025-10-22 04:52:58'),
(138, 'brandonverro@gmail.com', '621818', 'profile_change', 1, '2025-10-22 04:55:45', '2025-10-22 04:55:20', '2025-10-22 04:55:45'),
(139, 'gekkoretto@gmail.com', '210685', 'registration', 0, '2025-10-22 05:37:30', '2025-10-22 05:35:30', NULL),
(140, 'ekojet1521@gmail.com', '685412', 'registration', 0, '2025-10-22 07:23:52', '2025-10-22 07:21:52', NULL),
(141, 'ekojet1521@gmail.com', '813992', 'registration', 1, '2025-10-22 07:23:11', '2025-10-22 07:22:39', '2025-10-22 07:23:11'),
(142, 'ekojet1521@gmail.com', '857129', 'registration', 1, '2025-10-22 08:05:26', '2025-10-22 08:05:00', '2025-10-22 08:05:26');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('siomai','siopao','bundle') NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` text NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `description`, `price`, `quantity`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Pork Siomai', 'siomai', 'Juicy pork siomai with authentic Filipino flavors', 25.00, 144, 'https://media.istockphoto.com/id/2182583656/photo/chinese-steamed-dumpling-or-shumai-in-japanese-language-meatball-dumpling-with-wanton-skin.jpg?s=612x612&w=0&k=20&c=0K7_ee0dwfAZhcZZajZRSv8uTifXZhG6LVmlKnSe-0U=', 'active', '2025-09-18 04:00:00', '2025-10-18 09:11:25'),
(2, 'Chicken Siomai', 'siomai', 'Tender chicken siomai with fresh ingredients', 22.00, 100, 'https://media.istockphoto.com/id/1336438874/photo/delicious-dim-sum-home-made-chinese-dumplings-served-on-plate.jpg?s=612x612&w=0&k=20&c=11KB0bXoZeMrlzaHN2q9aZq8kqtdvp-d4Oggc2TF8M4=', 'active', '2025-09-18 04:00:00', '2025-10-22 05:09:31'),
(3, 'Beef Siomai', 'siomai', 'Premium beef siomai with rich savory taste', 28.00, 10, 'https://media.istockphoto.com/id/2189370578/photo/delicious-shumai-shumay-siomay-chicken-in-bowl-snack-menu.jpg?s=612x612&w=0&k=20&c=hD4kuZsiGIjgyUPq-seqv229pFE43CnS0Do3EH_2E_Y=', 'active', '2025-09-18 04:00:00', '2025-10-21 07:13:37'),
(4, 'Tuna Siomai', 'siomai', 'Fresh tuna siomai with ocean-fresh flavor', 30.00, 93, 'https://media.istockphoto.com/id/1084916088/photo/close-up-cooking-homemade-shumai.jpg?s=612x612&w=0&k=20&c=M1RyWV62MACQffBC40UzZ_h-BsXOj4bkaMBrxnbMTzc=', 'active', '2025-09-18 04:00:00', '2025-10-22 04:13:06'),
(5, 'Shark\'s Fin Siomai', 'siomai', 'Premium shark\'s fin siomai with delicate texture', 35.00, 80, 'https://media.istockphoto.com/id/1330456626/photo/steamed-shark-fin-dumplings-served-with-chili-garlic-oil-and-calamansi.jpg?s=612x612&w=0&k=20&c=9Zi1JmbwvYtIlZJqZb6tHOVC21rS-IbwZXS-IeflE30=', 'active', '2025-09-18 04:00:00', '2025-09-18 04:00:00'),
(6, 'Japanese Siomai', 'siomai', 'Japanese-style siomai with nori wrapping', 32.00, 79, 'https://media.istockphoto.com/id/1221287744/photo/ground-pork-with-crab-stick-wrapped-in-nori.jpg?s=612x612&w=0&k=20&c=Rniq7tdyCqVZHpwngsbzOk1dG1u8pTEeUDE8arsfOUY=', 'active', '2025-09-18 04:00:00', '2025-10-19 08:32:27'),
(7, 'Asado Siopao', 'siopao', 'Classic asado siopao with sweet-savory pork filling', 45.00, 75, 'https://media.istockphoto.com/id/1163708923/photo/hong-kong-style-chicken-char-siew-in-classic-polo-bun-polo-bun-or-is-a-kind-of-crunchy-and.jpg?s=612x612&w=0&k=20&c=R9DC49-UsxYUPlImX6O47LQyafOu1Cp5rNxp3XifFNI=', 'active', '2025-09-18 04:00:00', '2025-09-18 04:00:00'),
(8, 'Bola-Bola Siopao', 'siopao', 'Hearty bola-bola siopao with meatball filling', 42.00, 81, 'https://media.istockphoto.com/id/1184080523/photo/wanton-noodle-soup-and-siopao.jpg?s=612x612&w=0&k=20&c=oRJanjrTxICQfuzm9bXVPYkw9nKh74tcwjH1cVzXzN8=', 'active', '2025-09-18 04:00:00', '2025-10-01 13:58:55'),
(9, 'Choco Siopao', 'siopao', 'Sweet chocolate-filled siopao for dessert lovers', 38.00, 110, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTxSCl2zlIK85vMZ6nRYuWpqde6JnIxBUTe-w&s', 'active', '2025-09-18 04:00:00', '2025-09-18 04:00:00'),
(10, 'Ube Siopao', 'siopao', 'Filipino ube-flavored siopao with purple yam', 40.00, 95, 'https://media.istockphoto.com/id/2161276374/photo/vivid-steamed-purple-ube-sweet-potato-dumplings.jpg?s=612x612&w=0&k=20&c=Mb2rl1JZPvG0d5v-_gSC7Mx50DNggFJiTEcoTayqB1Q=', 'active', '2025-09-18 04:00:00', '2025-09-18 04:00:00'),
(15, 'Red Bean Siopao', 'siopao', 'Traditional red bean paste siopao with sweet flavor', 36.00, 100, 'https://media.istockphoto.com/id/1172915611/photo/asian-steamed-bun-with-adzuki-red-bean-paste-filling-or-bakpao.jpg?s=612x612&w=0&k=20&c=hImY86ZyoR8y2FC17yLpkCA5amxrZDxCeuVokJnY5w0=', 'active', '2025-10-01 14:35:55', '2025-10-01 14:40:50'),
(16, 'Custard Siopao', 'siopao', 'Creamy custard-filled siopao with rich texture', 44.00, 54, 'https://media.istockphoto.com/id/957584318/photo/chinese-steamed-bun-and-orange-sweet-creamy-lava-on-chinese-pattern-dish.jpg?s=612x612&w=0&k=20&c=5CJuHZdTLVIlN5gq_jmer--RWri-TDliTtQoIvAc97M=', 'active', '2025-10-01 14:44:00', '2025-10-18 08:46:52'),
(22, 'asdasdasd', 'siomai', 'adsadadad', 45.00, 99, '../uploads/1761121679_asdas.jpg', 'inactive', '2025-10-09 15:49:17', '2025-10-22 08:32:25'),
(23, 'xasdasd', 'siopao', 'asdasda', 10.00, 201, '../uploads/1761121667_asdas.jpg', 'inactive', '2025-10-09 15:55:32', '2025-10-22 08:32:23'),
(24, 'Siomai Bundle 80pcs - Pork', 'bundle', '80 pieces of delicious pork siomai. Perfect for parties, gatherings, and family meals. Freshly made with premium ground pork, shrimp, and Asian spices wrapped in thin wonton skin. Great value bundle!', 1200.00, 50, 'https://media.istockphoto.com/id/2182583656/photo/chinese-steamed-dumpling-or-shumai-in-japanese-language-meatball-dumpling-with-wanton-skin.jpg?s=612x612&w=0&k=20&c=0K7_ee0dwfAZhcZZajZRSv8uTifXZhG6LVmlKnSe-0U=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(25, 'Siomai Bundle 80pcs - Chicken', 'bundle', '80 pieces of tender chicken siomai. A healthier option packed with lean chicken meat, vegetables, and traditional Asian flavors. Ideal for health-conscious customers and family gatherings.', 1100.00, 45, 'https://media.istockphoto.com/id/1336438874/photo/delicious-dim-sum-home-made-chinese-dumplings-served-on-plate.jpg?s=612x612&w=0&k=20&c=11KB0bXoZeMrlzaHN2q9aZq8kqtdvp-d4Oggc2TF8M4=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(26, 'Siomai Bundle 80pcs - Beef', 'bundle', '80 pieces of premium beef siomai with rich savory taste. Made with quality ground beef, garlic, and special seasonings for a bold flavor profile. Perfect for meat lovers!', 1300.00, 40, 'https://media.istockphoto.com/id/2189370578/photo/delicious-shumai-shumay-siomay-chicken-in-bowl-snack-menu.jpg?s=612x612&w=0&k=20&c=hD4kuZsiGIjgyUPq-seqv229pFE43CnS0Do3EH_2E_Y=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(27, 'Siomai Bundle 80pcs - Mixed Variety', 'bundle', '80 pieces assorted siomai featuring pork, chicken, and beef varieties in one bundle. Great for trying different flavors and sharing with family. Mix of all our best siomai!', 1250.00, 35, 'https://media.istockphoto.com/id/1084916088/photo/close-up-cooking-homemade-shumai.jpg?s=612x612&w=0&k=20&c=M1RyWV62MACQffBC40UzZ_h-BsXOj4bkaMBrxnbMTzc=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(28, 'Siopao Bundle 40pcs - Asado', 'bundle', '40 pieces of traditional Filipino asado siopao. Classic combination of seasoned pork, carrots, and potatoes in a savory sauce wrapped in soft, fluffy bread. A customer favorite!', 950.00, 40, 'https://media.istockphoto.com/id/1163708923/photo/hong-kong-style-chicken-char-siew-in-classic-polo-bun-polo-bun-or-is-a-kind-of-crunchy-and.jpg?s=612x612&w=0&k=20&c=R9DC49-UsxYUPlImX6O47LQyafOu1Cp5rNxp3XifFNI=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(29, 'Siopao Bundle 40pcs - Pork BBQ', 'bundle', '40 pieces of tender BBQ pork siopao. Perfect for breakfast, snacks, or anytime cravings. Soft and fluffy bread filled with delicious BBQ pork. Freshly baked daily!', 900.00, 50, 'https://media.istockphoto.com/id/1184080523/photo/wanton-noodle-soup-and-siopao.jpg?s=612x612&w=0&k=20&c=oRJanjrTxICQfuzm9bXVPYkw9nKh74tcwjH1cVzXzN8=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(30, 'Siopao Bundle 40pcs - Chicken', 'bundle', '40 pieces of chicken-filled siopao. Delicious combination of tender chicken meat, onions, and special sauce in a soft, pillowy bread. Great protein-packed option!', 850.00, 50, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTxSCl2zlIK85vMZ6nRYuWpqde6JnIxBUTe-w&s', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(31, 'Siopao Bundle 40pcs - Ube Sweet', 'bundle', '40 pieces of sweet ube-filled siopao for dessert lovers. Purple yam goodness in soft, pillowy bread. Perfect with coffee or tea. Filipino favorite for sweet cravings!', 1000.00, 30, 'https://media.istockphoto.com/id/2161276374/photo/vivid-steamed-purple-ube-sweet-potato-dumplings.jpg?s=612x612&w=0&k=20&c=Mb2rl1JZPvG0d5v-_gSC7Mx50DNggFJiTEcoTayqB1Q=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(32, 'Siopao Bundle 40pcs - Red Bean', 'bundle', '40 pieces of traditional red bean siopao. Classic sweet filling with creamy red bean paste. Perfect for those who love traditional Asian sweets. Great with tea!', 850.00, 35, 'https://media.istockphoto.com/id/1172915611/photo/asian-steamed-bun-with-adzuki-red-bean-paste-filling-or-bakpao.jpg?s=612x612&w=0&k=20&c=hImY86ZyoR8y2FC17yLpkCA5amxrZDxCeuVokJnY5w0=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(33, 'Siopao Bundle 40pcs - Custard', 'bundle', '40 pieces of creamy custard-filled siopao. Rich and smooth custard center with soft bread exterior. Indulgent treat perfect for special occasions or gifts!', 1050.00, 25, 'https://media.istockphoto.com/id/957584318/photo/chinese-steamed-bun-and-orange-sweet-creamy-lava-on-chinese-pattern-dish.jpg?s=612x612&w=0&k=20&c=5CJuHZdTLVIlN5gq_jmer--RWri-TDliTtQoIvAc97M=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(34, 'Siopao Bundle 40pcs - Mixed Flavors', 'bundle', '40 pieces assorted siopao featuring asado, pork BBQ, chicken, ube, and red bean varieties. Best of everything in one bundle! Perfect for sharing and variety!', 950.00, 30, 'https://media.istockphoto.com/id/1330456626/photo/steamed-shark-fin-dumplings-served-with-chili-garlic-oil-and-calamansi.jpg?s=612x612&w=0&k=20&c=9Zi1JmbwvYtIlZJqZb6tHOVC21rS-IbwZXS-IeflE30=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(35, 'Mega Combo - 80pcs Siomai + 40pcs Siopao', 'bundle', 'The ultimate combo! 80 pieces of assorted siomai (pork, chicken, beef mix) PLUS 40 pieces of mixed siopao. Perfect for large gatherings, office parties, and family celebrations. Best value combo pack!', 2000.00, 20, 'https://media.istockphoto.com/id/1221287744/photo/ground-pork-with-crab-stick-wrapped-in-nori.jpg?s=612x612&w=0&k=20&c=Rniq7tdyCqVZHpwngsbzOk1dG1u8pTEeUDE8arsfOUY=', 'active', '2025-10-19 08:05:56', '2025-10-19 08:05:56'),
(36, 'Party Pack Deluxe - 160pcs Siomai + 80pcs Siopao', 'bundle', 'Biggest bundle! 160 pieces of siomai and 80 pieces of siopao. Ideal for catering, corporate events, weddings, and large family celebrations. Save big with this mega bundle!', 3800.00, 91000, 'https://media.istockphoto.com/id/1330456626/photo/steamed-shark-fin-dumplings-served-with-chili-garlic-oil-and-calamansi.jpg?s=612x612&w=0&k=20&c=9Zi1JmbwvYtIlZJqZb6tHOVC21rS-IbwZXS-IeflE30=', 'active', '2025-10-19 08:05:56', '2025-10-22 06:57:15'),
(41, 'asd', 'siomai', 'asdasd', 123.00, 10, '../uploads/1761121650_asdas.jpg', 'inactive', '2025-10-21 14:42:16', '2025-10-22 08:32:20');

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `notify_admin_low_stock` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
  DECLARE admin_cursor_done INT DEFAULT FALSE;
  DECLARE admin_id_var INT;
  DECLARE admin_cursor CURSOR FOR SELECT id FROM admins WHERE status = 'active';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET admin_cursor_done = TRUE;
  
  IF NEW.quantity <= 10 AND OLD.quantity > 10 THEN
    OPEN admin_cursor;
    admin_loop: LOOP
      FETCH admin_cursor INTO admin_id_var;
      IF admin_cursor_done THEN
        LEAVE admin_loop;
      END IF;
      
      INSERT INTO notifications (
        admin_id,
        recipient_type,
        product_id,
        type,
        title,
        message,
        action_url,
        priority
      ) VALUES (
        admin_id_var,
        'admin',
        NEW.id,
        'low_stock_alert',
        'Low Stock Alert',
        CONCAT('Product "', NEW.name, '" is running low. Only ', NEW.quantity, ' units remaining.'),
        CONCAT('../admin/admin_products.php'),
        'urgent'
      );
    END LOOP;
    CLOSE admin_cursor;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `notify_users_new_product` AFTER INSERT ON `products` FOR EACH ROW BEGIN
  DECLARE user_cursor_done INT DEFAULT FALSE;
  DECLARE user_id_var INT;
  DECLARE user_cursor CURSOR FOR 
    SELECT user_id FROM userss WHERE status = 'active'
    AND user_id IN (
      SELECT user_id FROM notification_preferences 
      WHERE new_products = 1
    );
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET user_cursor_done = TRUE;
  
  IF NEW.status = 'active' THEN
    OPEN user_cursor;
    user_loop: LOOP
      FETCH user_cursor INTO user_id_var;
      IF user_cursor_done THEN
        LEAVE user_loop;
      END IF;
      
      INSERT INTO notifications (
        user_id,
        recipient_type,
        product_id,
        type,
        title,
        message,
        action_url,
        priority
      ) VALUES (
        user_id_var,
        'user',
        NEW.id,
        'new_product',
        'New Product Available!',
        CONCAT('Check out our new ', NEW.category, ': ', NEW.name, ' - ₱', FORMAT(NEW.price, 2)),
        CONCAT('../products/product_details.php?id=', NEW.id),
        'low'
      );
    END LOOP;
    CLOSE user_cursor;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('stripe','cod') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `stripe_refund_id` varchar(255) DEFAULT NULL COMMENT 'Stripe refund transaction ID',
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`refund_id`, `return_id`, `order_id`, `payment_method`, `amount`, `stripe_refund_id`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 29, 'stripe', 200.00, 're_3SJWAc8CuwBmuHaz19lvfiwp', 'completed', 'Stripe refund processed successfully', '2025-10-18 10:57:12', '2025-10-18 10:57:12'),
(2, 7, 37, 'stripe', 230.00, 're_3SKX1p8CuwBmuHaz1jciK9AY', 'completed', 'Stripe refund processed successfully', '2025-10-21 08:53:59', '2025-10-21 08:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `return_requests`
--

CREATE TABLE `return_requests` (
  `return_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `video_proof_path` varchar(255) NOT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_requests`
--

INSERT INTO `return_requests` (`return_id`, `order_id`, `user_id`, `reason`, `video_proof_path`, `comments`, `status`, `created_at`, `updated_at`, `admin_notes`) VALUES
(1, 18, 7, 'defective', '../uploads/return_videos/return_18_68efb7673f83e.mp4', 'sdfsdfs', 'approved', '2025-10-15 15:01:59', '2025-10-15 15:13:10', 'asdasda'),
(2, 17, 7, 'not_as_described', '../uploads/return_videos/return_17_68f35d84b4233.mp4', 'asdasdasda', 'rejected', '2025-10-18 09:27:32', '2025-10-18 09:28:12', 'fdsdfsdfs'),
(3, 29, 7, 'wrong_item', '../uploads/return_videos/return_29_68f36db7b8e83.mp4', 'asdasda', 'approved', '2025-10-18 10:36:39', '2025-10-18 10:57:08', 'asdasda'),
(4, 30, 7, 'not_as_described', '../uploads/return_videos/return_30_68f377cee82cc.mp4', 'tfgfy', 'approved', '2025-10-18 11:19:42', '2025-10-18 11:20:27', ''),
(5, 31, 7, 'wrong_item', '../uploads/return_videos/return_31_68f37967ee684.mp4', 'tyhfghjgfh', 'rejected', '2025-10-18 11:26:31', '2025-10-18 11:26:54', 'hmnhgmbm'),
(6, 28, 7, 'defective', '../uploads/return_videos/return_28_68f38298599e3.mp4', 'sadasda', 'rejected', '2025-10-18 12:05:44', '2025-10-18 12:06:24', 'trfhfghfg'),
(7, 37, 21, 'defective', '../uploads/return_videos/return_37_68f709d76a0e0.mp4', 'Expired Siopao', 'rejected', '2025-10-21 04:19:35', '2025-10-21 08:55:25', ''),
(8, 45, 25, 'defective', '../uploads/return_videos/return_45_68f86c0dc59d2.mp4', 'pangit po', 'rejected', '2025-10-22 05:30:53', '2025-10-22 06:57:41', 'sge po');

--
-- Triggers `return_requests`
--
DELIMITER $$
CREATE TRIGGER `notify_admin_return_request` AFTER INSERT ON `return_requests` FOR EACH ROW BEGIN
  DECLARE admin_cursor_done INT DEFAULT FALSE;
  DECLARE admin_id_var INT;
  DECLARE admin_cursor CURSOR FOR SELECT id FROM admins WHERE status = 'active';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET admin_cursor_done = TRUE;
  
  OPEN admin_cursor;
  admin_loop: LOOP
    FETCH admin_cursor INTO admin_id_var;
    IF admin_cursor_done THEN
      LEAVE admin_loop;
    END IF;
    
    INSERT INTO notifications (
      admin_id,
      recipient_type,
      order_id,
      type,
      title,
      message,
      action_url,
      priority
    ) VALUES (
      admin_id_var,
      'admin',
      NEW.order_id,
      'return_requested',
      'New Return Request',
      CONCAT('Return request #', NEW.return_id, ' for order #', NEW.order_id, ' needs review.'),
      CONCAT('../admin/admin_returns.php?id=', NEW.return_id),
      'high'
    );
  END LOOP;
  CLOSE admin_cursor;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `notify_user_return_status` AFTER UPDATE ON `return_requests` FOR EACH ROW BEGIN
  IF NEW.status != OLD.status THEN
    INSERT INTO notifications (
      user_id,
      recipient_type,
      order_id,
      type,
      title,
      message,
      action_url,
      priority
    ) VALUES (
      NEW.user_id,
      'user',
      NEW.order_id,
      CASE NEW.status
        WHEN 'approved' THEN 'return_approved'
        WHEN 'rejected' THEN 'return_rejected'
        ELSE 'return_requested'
      END,
      CASE NEW.status
        WHEN 'approved' THEN 'Return Request Approved'
        WHEN 'rejected' THEN 'Return Request Rejected'
        ELSE 'Return Status Update'
      END,
      CASE NEW.status
        WHEN 'approved' THEN CONCAT('Your return request for order #', NEW.order_id, ' has been approved. Refund will be processed soon.')
        WHEN 'rejected' THEN CONCAT('Your return request for order #', NEW.order_id, ' has been rejected. ', COALESCE(NEW.admin_notes, ''))
        ELSE CONCAT('Your return request status has been updated.')
      END,
      CONCAT('../cart/order_details.php?order_id=', NEW.order_id),
      'high'
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `user_name`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 1, 'Juan D.', 5, 'Sio-per sarap! The best siomai I\'ve ever tasted. Will order again!', '2025-10-19 09:34:39'),
(2, 1, 2, 'Maria C.', 4, 'Good quality and fast delivery. The siopao was still warm.', '2025-10-19 09:34:39'),
(3, 2, 3, 'Pedro G.', 5, 'The asado siopao is so soft and flavorful!', '2025-10-19 09:34:39'),
(4, 1, 3, 'Pedro G.', 4, 'My second time ordering. Still great!', '2025-10-19 09:34:39'),
(5, 3, 1, 'Juan D.', 3, 'It was okay. A bit small for the price.', '2025-10-19 09:34:39'),
(6, 23, 7, 'Anonymous', 5, 'ansarap', '2025-10-19 09:51:42'),
(7, 22, 7, 'jerichodelacruz', 5, 'sarap', '2025-10-19 09:53:52'),
(8, 23, 7, 'jerichodelacruz', 5, 'sarap', '2025-10-19 09:54:11'),
(9, 22, 7, 'jerichodelacruz', 5, 'sarafgfhf', '2025-10-19 10:35:48'),
(10, 22, 7, 'jerichodelacruz', 2, 'pangit', '2025-10-19 10:36:01'),
(11, 23, 17, 'marcomilanez', 1, 'lasang pusa', '2025-10-21 03:39:05'),
(12, 41, 25, 'Anonymous', 5, 'hehe', '2025-10-21 16:42:10'),
(13, 5, 25, 'Anonymous', 5, 'hehe', '2025-10-21 16:42:25'),
(14, 22, 25, 'Anonymous', 5, 'hehe', '2025-10-21 16:42:35'),
(15, 22, 7, 'Anonymous', 5, 'asdasd', '2025-10-21 16:45:18'),
(16, 22, 7, 'jerichodelacruz', 5, 'hehe', '2025-10-21 16:46:59');

-- --------------------------------------------------------

--
-- Table structure for table `userss`
--

CREATE TABLE `userss` (
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_num` varchar(20) NOT NULL,
  `delivery_address` text NOT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `profile_photo` varchar(255) NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `suspended_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userss`
--

INSERT INTO `userss` (`user_id`, `name`, `username`, `email`, `password`, `contact_num`, `delivery_address`, `address_line1`, `address_line2`, `barangay`, `city`, `postal_code`, `profile_photo`, `status`, `created_at`, `suspended_until`) VALUES
(7, 'Jericho athan dela cruz', 'jerichodelacruz', 'qjapdelacruz@tip.edu.ph', '$2y$10$vWCGfQbYALMcnxMcbzHw5.2axOqeBACu2wyo4RcETh4gK6pKhDSHi', '', '', NULL, NULL, NULL, NULL, NULL, '', 'active', '2025-09-23 11:48:43', NULL),
(17, 'Ocram D. Polo', 'ocrampolo', 'makoymilanez@gmail.com', '$2y$10$PF9lNNgYfb9WSG17MvTGJeZam34b1b9KyUXUCJuGN13C8/Ytqkawu', '09992342880', 'BLK 10 LOT 24 Summit View Subdivision', 'BLK 10 LOT 24', 'SUMMIT VIEW SUBD', 'San Makati', 'Makati', '1201', '../uploads/profile_photos/profile_17_1761098005.jpeg', 'active', '2025-09-28 18:44:09', NULL),
(21, 'Bob Eddy Marley', 'bobmarley', 'marcomilanez08@gmail.com', '$2y$10$wcibzav4acOjiOcmoqAP4.z/GIyO4xi.UZdDAJDxXsS2iXleXqAKO', '09941146423', '', NULL, NULL, NULL, NULL, NULL, '', 'active', '2025-10-20 18:57:40', NULL),
(22, 'Joseph Dimagiba', 'jojodima', 'jojodima@gmail.com', '$2y$10$4yiKCCMx6kZ1PksYT4G9Be7LmSHCYTEjgqOmlQz8tWDMioj4X/0nm', '09941146423', 'BLK 10 LOT 24', NULL, NULL, NULL, NULL, NULL, '', 'active', '2025-10-21 04:08:45', NULL),
(25, 'kulet Delaa Cruz', 'kuletlet', 'brandonverro@gmail.com', '$2y$10$4OTD2iOJWk3FOe2N3lASmuTyBbLHPYKhGAGCjMdGouYwsCITQ.cvq', '09618830492', 'asdfsdfsafsadfsafdsadf\r\nasda', '59 B manansala St up diliman qc', '', 'UP Campus', 'Quezon City', '1101', '', 'active', '2025-10-21 15:45:08', NULL),
(27, 'Deleted User #27', 'deleted_27', 'deleted27@deleted.com', '', '', '', NULL, NULL, NULL, NULL, NULL, '', '', '2025-10-22 07:23:11', NULL);

--
-- Triggers `userss`
--
DELIMITER $$
CREATE TRIGGER `notify_admin_new_user` AFTER INSERT ON `userss` FOR EACH ROW BEGIN
  DECLARE admin_cursor_done INT DEFAULT FALSE;
  DECLARE admin_id_var INT;
  DECLARE admin_cursor CURSOR FOR SELECT id FROM admins WHERE status = 'active';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET admin_cursor_done = TRUE;
  
  OPEN admin_cursor;
  admin_loop: LOOP
    FETCH admin_cursor INTO admin_id_var;
    IF admin_cursor_done THEN
      LEAVE admin_loop;
    END IF;
    
    INSERT INTO notifications (
      admin_id,
      recipient_type,
      type,
      title,
      message,
      action_url,
      priority
    ) VALUES (
      admin_id_var,
      'admin',
      'new_user_registered',
      'New User Registered',
      CONCAT('New user "', NEW.username, '" (', NEW.email, ') has registered.'),
      CONCAT('../admin/admin_users.php'),
      'low'
    );
  END LOOP;
  CLOSE admin_cursor;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_audit_trail`
--

CREATE TABLE `user_audit_trail` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_audit_trail`
--

INSERT INTO `user_audit_trail` (`id`, `user_id`, `username`, `action_type`, `action_description`, `ip_address`, `created_at`) VALUES
(1, 25, 'kuletlet', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:12:22'),
(2, 25, 'kuletlet', 'login_fail_password', 'Failed login attempt (wrong password) for user \'kuletlet\' (ID: 25).', '::1', '2025-10-22 15:13:33'),
(3, 25, 'kuletlet', 'login_fail_password', 'Failed login attempt (wrong password) for user \'kuletlet\' (ID: 25).', '::1', '2025-10-22 15:13:35'),
(4, 25, 'kuletlet', 'login_fail_password', 'Failed login attempt (wrong password) for user \'kuletlet\' (ID: 25).', '::1', '2025-10-22 15:13:38'),
(5, 25, 'kuletlet', 'login_fail_password', 'Failed login attempt (wrong password) for user \'kuletlet\' (ID: 25).', '::1', '2025-10-22 15:13:39'),
(6, 25, 'kuletlet', 'login_fail_password', 'Failed login attempt (wrong password) for user \'kuletlet\' (ID: 25).', '::1', '2025-10-22 15:13:41'),
(7, NULL, 'kuletlet', 'login_fail_locked', 'Account locked (5+ attempts) for identifier \'kuletlet\'.', '::1', '2025-10-22 15:13:44'),
(8, NULL, 'ekoeko', 'login_fail_locked', 'Account locked (5+ attempts) for identifier \'ekoeko\'.', '::1', '2025-10-22 15:16:52'),
(9, NULL, 'ekoeko', 'login_fail_locked', 'Account locked (5+ attempts) for identifier \'ekoeko\'.', '::1', '2025-10-22 15:16:52'),
(10, 27, 'j3richoo', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:24:11'),
(11, 25, 'kuletlet', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:39:11'),
(12, 27, 'j3richoo', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:41:19'),
(13, 7, 'jerichodelacruzz', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:42:18'),
(14, 27, 'j3richoo', 'login_fail_password', 'Failed login attempt (wrong password) for user \'j3richoo\' (ID: 27).', '::1', '2025-10-22 15:43:10'),
(15, 27, 'j3richoo', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:43:12'),
(16, 27, 'j3richoo', 'login_fail_password', 'Failed login attempt (wrong password) for user \'j3richoo\' (ID: 27).', '::1', '2025-10-22 15:45:08'),
(17, 27, 'j3richoo', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:45:12'),
(18, 27, 'j3richoo', 'login_fail_password', 'Failed login attempt (wrong password) for user \'j3richoo\' (ID: 27).', '::1', '2025-10-22 15:46:15'),
(19, 27, 'j3richoo', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:46:22'),
(20, 7, 'jerichodelacruzz', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 15:54:29'),
(21, 29, 'j3richoo', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 16:05:46'),
(22, 7, 'jerichodelacruz', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 16:11:53'),
(23, 30, 'ekoeko', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 16:20:56'),
(24, 7, 'jerichodelacruz', 'login_success', 'User successfully logged in.', '::1', '2025-10-22 16:21:41'),
(25, 7, 'jerichodelacruz', 'login_success', 'User successfully logged in.', '::1', '2025-10-23 00:38:51'),
(26, 7, 'jerichodelacruz', 'login_success', 'User successfully logged in.', '::1', '2025-10-23 00:39:12'),
(27, 7, 'jerichodelacruz', 'login_fail_password', 'Failed login attempt (wrong password) for user \'jerichodelacruz\' (ID: 7).', '::1', '2025-10-23 00:39:24'),
(28, 7, 'jerichodelacruz', 'login_fail_password', 'Failed login attempt (wrong password) for user \'jerichodelacruz\' (ID: 7).', '::1', '2025-10-23 00:39:27'),
(29, 7, 'jerichodelacruz', 'login_fail_password', 'Failed login attempt (wrong password) for user \'jerichodelacruz\' (ID: 7).', '::1', '2025-10-23 00:39:29'),
(30, 7, 'jerichodelacruz', 'login_fail_password', 'Failed login attempt (wrong password) for user \'jerichodelacruz\' (ID: 7).', '::1', '2025-10-23 00:39:31'),
(31, 7, 'jerichodelacruz', 'login_fail_password', 'Failed login attempt (wrong password) for user \'jerichodelacruz\' (ID: 7).', '::1', '2025-10-23 00:39:32'),
(32, NULL, 'jerichodelacruz', 'login_fail_locked', 'Account locked (5+ attempts) for identifier \'jerichodelacruz\'.', '::1', '2025-10-23 00:39:35'),
(33, NULL, 'jerichodelacruz', 'login_fail_locked', 'Account locked (5+ attempts) for identifier \'jerichodelacruz\'.', '::1', '2025-10-23 00:39:37'),
(34, 17, 'ocrampolo', 'login_fail_password', 'Failed login attempt (wrong password) for user \'ocrampolo\' (ID: 17).', '::1', '2025-10-29 04:33:52'),
(35, 17, 'ocrampolo', 'login_success', 'User successfully logged in.', '::1', '2025-10-29 04:35:35');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `voucher_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `applicable_to` enum('all','siomai','siopao','bundle') NOT NULL DEFAULT 'all',
  `usage_limit` int(11) DEFAULT NULL COMMENT 'Total times this voucher can be used',
  `usage_count` int(11) DEFAULT 0,
  `per_user_limit` int(11) DEFAULT 1 COMMENT 'Times each user can use this voucher',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`voucher_id`, `code`, `description`, `discount_type`, `discount_value`, `min_purchase`, `max_discount`, `applicable_to`, `usage_limit`, `usage_count`, `per_user_limit`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME20', 'Welcome discount for new customers', 'percentage', 20.00, 100.00, 200.00, 'all', 100, 0, 1, '2025-10-29 04:06:58', '2026-01-29 04:06:58', 'active', 1, '2025-10-28 20:06:58', '2025-10-28 20:06:58'),
(2, 'SIOMAI50', 'Get 50 pesos off on Siomai orders', 'fixed', 50.00, 200.00, NULL, 'siomai', 50, 0, 2, '2025-10-29 04:06:58', '2025-11-29 04:06:58', 'active', 1, '2025-10-28 20:06:58', '2025-10-28 20:06:58'),
(3, 'SIOPAO30', '30% off on Siopao products', 'percentage', 30.00, 150.00, 100.00, 'siopao', NULL, 1, 1, '2025-10-29 04:06:58', '2025-12-29 04:06:58', 'active', 1, '2025-10-28 20:06:58', '2025-10-28 20:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `voucher_usage`
--

CREATE TABLE `voucher_usage` (
  `usage_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher_usage`
--

INSERT INTO `voucher_usage` (`usage_id`, `voucher_id`, `user_id`, `order_id`, `discount_amount`, `used_at`) VALUES
(1, 3, 17, 48, 25.50, '2025-10-28 20:46:12');

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
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_affected_table` (`affected_table`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id_idx` (`user_id`),
  ADD KEY `session_id_idx` (`session_id`),
  ADD KEY `product_id_idx` (`product_id`),
  ADD KEY `product_name_idx` (`product_name`),
  ADD KEY `status_idx` (`status`),
  ADD KEY `created_at_idx` (`created_at`);

--
-- Indexes for table `cms_content`
--
ALTER TABLE `cms_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_page_section` (`page_name`,`section_name`),
  ADD KEY `idx_page` (`page_name`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`),
  ADD KEY `attempt_time` (`attempt_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_recipient_type` (`recipient_type`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_tracking` (`tracking_number`),
  ADD KEY `idx_user_status` (`user_id`,`order_status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `voucher_code` (`voucher_code`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_idx` (`email`),
  ADD KEY `expires_at_idx` (`expires_at`),
  ADD KEY `idx_email_type_verified` (`email`,`otp_type`,`is_verified`,`expires_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_idx` (`category`),
  ADD KEY `status_idx` (`status`),
  ADD KEY `price_idx` (`price`),
  ADD KEY `quantity_idx` (`quantity`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`refund_id`),
  ADD KEY `idx_return` (`return_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `userss`
--
ALTER TABLE `userss`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_city_postal` (`city`,`postal_code`);

--
-- Indexes for table `user_audit_trail`
--
ALTER TABLE `user_audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`voucher_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `status` (`status`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT for table `cms_content`
--
ALTER TABLE `cms_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=363;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `return_requests`
--
ALTER TABLE `return_requests`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `userss`
--
ALTER TABLE `userss`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `user_audit_trail`
--
ALTER TABLE `user_audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `voucher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userss` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userss` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userss` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_preferences_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userss` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `return_requests` (`return_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD CONSTRAINT `return_requests_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `return_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `userss` (`user_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD CONSTRAINT `voucher_usage_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`voucher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voucher_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `userss` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voucher_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
