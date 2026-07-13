-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2026 at 04:53 AM
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
-- Database: `smartpro`
--

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `approver_id` bigint(20) UNSIGNED NOT NULL,
  `decision` enum('approved','rejected') DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `approvals`
--

INSERT INTO `approvals` (`id`, `document_id`, `approver_id`, `decision`, `comment`, `signed_at`, `created_at`, `updated_at`) VALUES
(13, 19, 2, 'approved', NULL, '2026-07-12 17:00:50', '2026-07-12 17:00:50', '2026-07-12 17:00:50'),
(14, 32, 2, 'rejected', 'JELEK', '2026-07-12 17:01:12', '2026-07-12 17:01:12', '2026-07-12 17:01:12'),
(20, 10, 2, 'approved', NULL, '2026-07-12 17:10:20', '2026-07-12 17:10:20', '2026-07-12 17:10:20'),
(21, 32, 2, 'approved', NULL, '2026-07-12 17:10:26', '2026-07-12 17:10:26', '2026-07-12 17:10:26'),
(22, 48, 2, 'approved', NULL, '2026-07-12 17:20:09', '2026-07-12 17:20:09', '2026-07-12 17:20:09');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `section_key` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attachments`
--

INSERT INTO `attachments` (`id`, `document_id`, `section_key`, `path`, `original_name`, `mime`, `size`, `created_at`, `updated_at`) VALUES
(2, 12, 'lampiran', 'lampiran/ICTMD/SOP/img_6a53c2238bced.png', 'logo-ppa.png', 'image/png', 285233, '2026-07-12 08:34:43', '2026-07-12 08:34:43'),
(5, 19, 'lampiran', 'lampiran/ICTMD/SOP/img_6a542c110c206.png', 'logo-ppa.png', 'image/png', 285233, '2026-07-12 16:06:41', '2026-07-12 16:06:41'),
(6, 19, 'lampiran', 'lampiran/ICTMD/SOP/img_6a542c111432d.png', 'BXFYmN5KLwB2P6LZ8GH1F2D7iDfzvrDiVKytQ4qp.png', 'image/png', 82266, '2026-07-12 16:06:41', '2026-07-12 16:06:41'),
(7, 19, 'lampiran', 'lampiran/ICTMD/SOP/img_6a542c1115eb5.png', 'ivytRfc795jMf27ahtrbzv20m58uQLtpdWpgCQ0K.png', 'image/png', 99251, '2026-07-12 16:06:41', '2026-07-12 16:06:41'),
(10, 32, 'lampiran', 'lampiran/ICTMD/SOP/img_6a5436431b703.png', 'cZqHDv3T8bP3ZJ6TAgD8jK1xfr9vmSV8BGVAoH5S.png', 'image/png', 113517, '2026-07-12 16:50:11', '2026-07-12 16:50:11');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `document_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `document_id`, `action`, `meta_json`, `ip_address`, `created_at`) VALUES
(1, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 00:47:08'),
(2, NULL, NULL, 'user.register', '{\"user_id\":6,\"department_id\":\"1\"}', '127.0.0.1', '2026-07-12 00:47:41'),
(3, 1, NULL, 'user.approve_registration', '{\"approved_user_id\":6}', '127.0.0.1', '2026-07-12 00:48:15'),
(4, 6, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 00:48:18'),
(5, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 00:51:27'),
(6, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 00:56:55'),
(7, 1, NULL, 'user.create_staff', '{\"created_user_id\":7,\"role\":\"group_leader\",\"department_id\":\"2\"}', '127.0.0.1', '2026-07-12 00:57:00'),
(8, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 00:57:18'),
(9, 1, NULL, 'user.toggle_status', '{\"user_id\":7,\"status\":\"rejected\"}', '127.0.0.1', '2026-07-12 00:57:21'),
(10, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:07:29'),
(11, NULL, NULL, 'user.register', '{\"user_id\":8,\"department_id\":\"3\"}', '127.0.0.1', '2026-07-12 01:09:09'),
(12, 1, NULL, 'user.create_staff', '{\"created_user_id\":9,\"role\":\"section_head\",\"department_id\":\"1\"}', '127.0.0.1', '2026-07-12 01:09:13'),
(13, 9, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:09:15'),
(14, 1, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 01:10:56'),
(15, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:11:03'),
(16, 1, NULL, 'user.create_staff', '{\"created_user_id\":10,\"role\":\"section_head\",\"department_id\":\"7\"}', '127.0.0.1', '2026-07-12 01:12:37'),
(17, 1, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 01:13:13'),
(18, 10, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:13:27'),
(19, 10, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 01:14:00'),
(20, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:14:16'),
(21, 1, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 01:14:29'),
(22, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:15:25'),
(23, 5, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:20:15'),
(24, 5, 1, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-01\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 01:20:17'),
(25, 5, 2, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-02\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 01:21:32'),
(26, 5, 3, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-99\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 01:21:35'),
(27, 5, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 01:38:41'),
(28, 1, 4, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ENGINEERING-01\",\"type\":\"SOP\",\"department\":\"ENGINEERING\"}', '127.0.0.1', '2026-07-12 01:41:59'),
(29, 1, NULL, 'user.approve_registration', '{\"approved_user_id\":8}', '127.0.0.1', '2026-07-12 01:44:32'),
(30, 1, 5, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-03\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 01:46:55'),
(31, 1, 6, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-04\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 02:37:44'),
(32, 1, 7, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-05\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 03:03:02'),
(33, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 06:43:48'),
(34, NULL, NULL, 'user.register', '{\"user_id\":14,\"department_id\":\"7\"}', '127.0.0.1', '2026-07-12 06:43:54'),
(35, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 06:48:21'),
(36, 1, NULL, 'user.approve_registration', '{\"approved_user_id\":14}', '127.0.0.1', '2026-07-12 06:48:23'),
(37, 14, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 06:48:25'),
(38, 1, NULL, 'user.create_staff', '{\"created_user_id\":15,\"role\":\"section_head\",\"department_id\":\"3\"}', '127.0.0.1', '2026-07-12 06:48:28'),
(39, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 06:52:38'),
(40, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 07:09:20'),
(41, 13, 8, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-06\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 07:09:23'),
(42, 13, 8, 'document.submit', '{\"status\":\"in_review\"}', '127.0.0.1', '2026-07-12 07:11:48'),
(43, 1, 9, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-07\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 07:17:41'),
(44, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 07:38:49'),
(45, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 08:01:02'),
(46, 13, 10, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-08\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 08:01:05'),
(48, 1, 10, 'document.submit', '{\"status\":\"in_review\"}', '127.0.0.1', '2026-07-12 08:29:10'),
(49, 1, 12, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-09\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 08:32:55'),
(73, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:02:45'),
(74, 13, 19, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 16:03:27'),
(75, 13, 19, 'document.submit', '{\"status\":\"in_review\",\"round\":0}', '127.0.0.1', '2026-07-12 16:07:42'),
(76, 13, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 16:08:08'),
(77, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:08:15'),
(78, 4, 19, 'document.review_reject', '{\"annotations\":3}', '127.0.0.1', '2026-07-12 16:09:33'),
(79, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 16:09:43'),
(80, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:09:49'),
(81, 13, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 16:13:39'),
(82, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:13:45'),
(104, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:35:29'),
(121, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 16:47:42'),
(122, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:47:47'),
(123, 13, 32, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"type\":\"SOP\",\"department\":\"ICTMD\"}', '127.0.0.1', '2026-07-12 16:48:14'),
(124, 13, 32, 'document.submit', '{\"status\":\"in_review\",\"round\":0}', '127.0.0.1', '2026-07-12 16:50:11'),
(125, 13, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 16:50:15'),
(126, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:50:21'),
(127, 4, 32, 'document.review_reject', '{\"annotations\":1}', '127.0.0.1', '2026-07-12 16:56:43'),
(128, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 16:56:48'),
(129, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:56:55'),
(130, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:57:02'),
(145, 13, 32, 'document.submit', '{\"status\":\"in_review\",\"round\":1}', '127.0.0.1', '2026-07-12 16:57:37'),
(146, 13, 19, 'document.submit', '{\"status\":\"in_review\",\"round\":1}', '127.0.0.1', '2026-07-12 16:58:08'),
(147, 13, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 16:58:46'),
(148, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 16:58:53'),
(149, 4, 32, 'document.review_approve', NULL, '127.0.0.1', '2026-07-12 16:59:23'),
(150, 4, 19, 'document.review_approve', NULL, '127.0.0.1', '2026-07-12 17:00:08'),
(151, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:00:18'),
(152, 2, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:00:23'),
(153, 2, 19, 'document.approve', '{\"status\":\"published\"}', '127.0.0.1', '2026-07-12 17:00:50'),
(154, 2, 32, 'document.approval_reject', '{\"comment\":\"JELEK\"}', '127.0.0.1', '2026-07-12 17:01:12'),
(155, 2, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:01:16'),
(156, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:02:07'),
(157, 13, 32, 'document.submit', '{\"status\":\"in_review\",\"round\":2}', '127.0.0.1', '2026-07-12 17:02:22'),
(158, 13, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:02:25'),
(159, 11, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:02:32'),
(160, 11, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:02:41'),
(161, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:02:45'),
(176, 4, 32, 'document.review_approve', NULL, '127.0.0.1', '2026-07-12 17:03:28'),
(193, 4, 10, 'document.review_approve', NULL, '127.0.0.1', '2026-07-12 17:05:50'),
(194, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:05:52'),
(195, 2, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:05:58'),
(196, 2, 10, 'document.approve', '{\"status\":\"published\"}', '127.0.0.1', '2026-07-12 17:10:20'),
(197, 2, 32, 'document.approve', '{\"status\":\"published\"}', '127.0.0.1', '2026-07-12 17:10:26'),
(198, 2, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:10:47'),
(199, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:10:53'),
(200, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:13:06'),
(201, 13, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:13:13'),
(202, 13, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:13:52'),
(203, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:13:59'),
(204, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:14:24'),
(205, NULL, NULL, 'user.register', '{\"user_id\":16,\"department_id\":\"7\"}', '127.0.0.1', '2026-07-12 17:15:01'),
(206, 16, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:15:10'),
(207, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:15:14'),
(208, 4, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:15:30'),
(209, 1, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:15:46'),
(210, 1, NULL, 'user.toggle_status', '{\"user_id\":16,\"status\":\"active\"}', '127.0.0.1', '2026-07-12 17:16:28'),
(211, 1, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:16:33'),
(212, 16, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:16:40'),
(213, 16, 48, 'document.create', '{\"doc_number\":\"PPA-ADRO-SOP-ENGINEERING-02\",\"type\":\"SOP\",\"department\":\"ENGINEERING\"}', '127.0.0.1', '2026-07-12 17:17:11'),
(214, 16, 48, 'document.submit', '{\"status\":\"in_review\",\"round\":0}', '127.0.0.1', '2026-07-12 17:18:55'),
(215, 16, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:19:00'),
(216, 10, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:19:06'),
(217, 10, 48, 'document.review_approve', NULL, '127.0.0.1', '2026-07-12 17:19:27'),
(218, 10, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:19:31'),
(219, 2, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:19:38'),
(220, 2, 48, 'document.approve', '{\"status\":\"published\"}', '127.0.0.1', '2026-07-12 17:20:09'),
(221, 2, NULL, 'user.logout', NULL, '127.0.0.1', '2026-07-12 17:20:24'),
(222, 4, NULL, 'user.login', NULL, '127.0.0.1', '2026-07-12 17:20:33'),
(245, 4, 8, 'document.ai_review', '{\"findings\":0}', '127.0.0.1', '2026-07-12 17:34:35'),
(246, 4, 8, 'document.ai_review', '{\"findings\":0}', '127.0.0.1', '2026-07-12 18:12:50'),
(266, 4, 8, 'document.ai_review', '{\"findings\":5}', '127.0.0.1', '2026-07-12 18:19:56');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('smartpro_cache_spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:16:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"document.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:6;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"document.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:6;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:15:\"document.submit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:6;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:15:\"document.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:6;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"document.review\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:4;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:16:\"document.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:16:\"document.publish\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:24:\"document.view_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:19:\"document.view_scope\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:17:\"document.view_all\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:11:\"user.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:25:\"user.approve_registration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"user.create_staff\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:22:\"document.change_status\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:10:\"audit.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:25:\"document.request_revision\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}}s:5:\"roles\";a:5:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:8:\"admin_it\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"group_leader\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:5:\"staff\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"section_head\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:8:\"pimpinan\";s:1:\"c\";s:3:\"web\";}}}', 1783992857);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `alias`, `created_at`, `updated_at`) VALUES
(1, 'SHE', 'Safety, Health & Environment', NULL, '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(2, 'PLANT', 'Plant', NULL, '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(3, 'HCGA', 'Human Capital & General Affairs', NULL, '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(4, 'FWA', 'Finance, Warehouse & Accounting', 'FALOG', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(5, 'ICTMD', 'ICT & Management Development', NULL, '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(6, 'PRODUKSI', 'Produksi', NULL, '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(7, 'ENGINEERING', 'Engineering', NULL, '2026-07-12 00:37:14', '2026-07-12 00:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `doc_number` varchar(255) DEFAULT NULL,
  `doc_number_manual` tinyint(1) NOT NULL DEFAULT 0,
  `document_type_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('draft','in_review','rejected','pending_approval','published','sedang_direvisi','obsolete','submitted','needs_revision','archived') NOT NULL DEFAULT 'draft',
  `current_step` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `revision_round` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `no_revisi` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `edisi` varchar(255) DEFAULT NULL,
  `is_controlled` tinyint(1) NOT NULL DEFAULT 1,
  `reviewer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `doc_number`, `doc_number_manual`, `document_type_id`, `department_id`, `title`, `status`, `current_step`, `revision_round`, `no_revisi`, `edisi`, `is_controlled`, `reviewer_id`, `approver_id`, `created_by`, `submitted_at`, `published_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PPA-ADRO-SOP-ICTMD-01', 0, 1, 5, 'Prosedur Backup Data Server', 'draft', 5, 0, 0, NULL, 1, 4, 2, 5, NULL, NULL, '2026-07-12 01:20:17', '2026-07-12 01:39:32', NULL),
(2, 'PPA-ADRO-SOP-ICTMD-02', 0, 1, 5, 'Prosedur Restore', 'draft', 1, 0, 0, NULL, 1, NULL, NULL, 5, NULL, NULL, '2026-07-12 01:21:32', '2026-07-12 01:21:32', NULL),
(3, 'PPA-ADRO-SOP-ICTMD-99', 1, 1, 5, 'Manual Numbered', 'draft', 1, 0, 0, NULL, 1, NULL, NULL, 5, NULL, NULL, '2026-07-12 01:21:35', '2026-07-12 01:21:35', NULL),
(4, 'PPA-ADRO-SOP-ENGINEERING-01', 0, 1, 7, 'SOP-1-adr', 'draft', 5, 0, 0, NULL, 1, 4, 2, 1, NULL, NULL, '2026-07-12 01:41:59', '2026-07-12 01:43:55', NULL),
(5, 'PPA-ADRO-SOP-ICTMD-03', 0, 1, 5, 'QSHB', 'draft', 1, 0, 0, NULL, 1, NULL, NULL, 1, NULL, NULL, '2026-07-12 01:46:55', '2026-07-12 01:46:55', NULL),
(6, 'PPA-ADRO-SOP-ICTMD-04', 0, 1, 5, 'GHJKL,MKJNBHNV', 'draft', 5, 0, 0, NULL, 1, NULL, NULL, 1, NULL, NULL, '2026-07-12 02:37:44', '2026-07-12 02:38:40', NULL),
(7, 'PPA-ADRO-SOP-ICTMD-05', 0, 1, 5, 'weee', 'draft', 5, 0, 0, NULL, 1, 3, 2, 1, NULL, NULL, '2026-07-12 03:03:02', '2026-07-12 06:52:56', NULL),
(8, 'PPA-ADRO-SOP-ICTMD-06', 0, 1, 5, 'SOP Uji 2-Step', 'in_review', 2, 0, 0, NULL, 1, 4, 2, 13, '2026-07-12 07:11:48', NULL, '2026-07-12 07:09:23', '2026-07-12 07:11:48', NULL),
(9, 'PPA-ADRO-SOP-ICTMD-07', 0, 1, 5, 'scdfv', 'draft', 2, 0, 0, NULL, 1, 4, 2, 1, NULL, NULL, '2026-07-12 07:17:41', '2026-07-12 07:18:41', NULL),
(10, 'PPA-ADRO-SOP-ICTMD-08', 0, 1, 5, 'Uji Lampiran Gambar', 'published', 2, 0, 0, NULL, 1, 4, 2, 13, '2026-07-12 08:29:10', '2026-07-12 17:10:20', '2026-07-12 08:01:05', '2026-07-12 17:10:20', NULL),
(12, 'PPA-ADRO-SOP-ICTMD-09', 0, 1, 5, 'Testing lampiran gambar dan hasil dari word', 'draft', 2, 0, 0, NULL, 1, 11, 2, 1, NULL, NULL, '2026-07-12 08:32:55', '2026-07-12 08:36:48', NULL),
(19, 'PPA-ADRO-SOP-ICTMD-10', 0, 1, 5, 'Testing Fase 3 (revisi jika di reject)', 'published', 2, 1, 0, NULL, 1, 4, 2, 13, '2026-07-12 16:58:08', '2026-07-12 17:00:50', '2026-07-12 16:03:27', '2026-07-12 17:00:50', NULL),
(32, 'PPA-ADRO-SOP-ICTMD-11', 0, 1, 5, 'Testing notifikasi', 'published', 2, 2, 0, NULL, 1, 4, 2, 13, '2026-07-12 17:02:22', '2026-07-12 17:10:26', '2026-07-12 16:48:14', '2026-07-12 17:10:26', NULL),
(48, 'PPA-ADRO-SOP-ENGINEERING-02', 0, 1, 7, 'Lorep Ipsum', 'published', 2, 0, 0, NULL, 1, 10, 2, 16, '2026-07-12 17:18:55', '2026-07-12 17:20:09', '2026-07-12 17:17:11', '2026-07-12 17:20:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_authors`
--

CREATE TABLE `document_authors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_authors`
--

INSERT INTO `document_authors` (`id`, `document_id`, `user_id`, `is_primary`, `created_at`, `updated_at`) VALUES
(1, 8, 13, 1, '2026-07-12 07:09:23', '2026-07-12 07:09:23'),
(2, 8, 11, 0, '2026-07-12 07:11:48', '2026-07-12 07:11:48'),
(3, 9, 1, 1, '2026-07-12 07:17:41', '2026-07-12 07:17:41'),
(4, 10, 13, 1, '2026-07-12 08:01:05', '2026-07-12 08:01:05'),
(6, 12, 1, 1, '2026-07-12 08:32:55', '2026-07-12 08:32:55'),
(13, 19, 13, 1, '2026-07-12 16:03:27', '2026-07-12 16:03:27'),
(26, 32, 13, 1, '2026-07-12 16:48:14', '2026-07-12 16:48:14'),
(42, 48, 16, 1, '2026-07-12 17:17:11', '2026-07-12 17:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `document_contents`
--

CREATE TABLE `document_contents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_contents`
--

INSERT INTO `document_contents` (`id`, `document_id`, `section_key`, `value_json`, `created_at`, `updated_at`) VALUES
(1, 1, 'tujuan', '[\"Mengatur perawatan perangkat IT\",\"Memastikan perangkat siap operasi\"]', '2026-07-12 01:38:44', '2026-07-12 01:38:44'),
(2, 1, 'ruang_lingkup', '[\"Semua perangkat ICT di PPA site Adaro\"]', '2026-07-12 01:38:44', '2026-07-12 01:38:44'),
(3, 1, 'referensi', '[\"ISO 9001:2015\"]', '2026-07-12 01:39:25', '2026-07-12 01:39:25'),
(4, 1, 'definisi', '[\"Hardware adalah komponen fisik\"]', '2026-07-12 01:39:25', '2026-07-12 01:39:25'),
(5, 1, 'aktivitas', '[{\"sub_judul\":\"Pemeliharaan bulanan\",\"deskripsi\":\"Inspeksi perangkat setiap bulan\",\"pic\":\"ICT\"},{\"sub_judul\":\"Backup data server\",\"deskripsi\":\"Backup rutin 24 jam\",\"pic\":\"ICT\"}]', '2026-07-12 01:39:27', '2026-07-12 01:39:27'),
(6, 1, 'lampiran', NULL, '2026-07-12 01:39:28', '2026-07-12 01:39:28'),
(7, 4, 'tujuan', '[\"Belajar dengan benar\"]', '2026-07-12 01:42:16', '2026-07-12 01:42:16'),
(8, 4, 'ruang_lingkup', '[\"Area Office\",\"Area Site\"]', '2026-07-12 01:42:16', '2026-07-12 01:42:47'),
(9, 4, 'referensi', '[\"ISO 9001:2015 Sistem Manajemen Mutu\"]', '2026-07-12 01:42:37', '2026-07-12 01:42:37'),
(10, 4, 'definisi', '[\"FMNDKMKMKMKC\",\"sdksmdksdkdmskdmsmkds\"]', '2026-07-12 01:42:37', '2026-07-12 01:42:56'),
(11, 4, 'aktivitas', '[{\"sub_judul\":\"koawskdadsadsad\",\"deskripsi\":\"asdsadsdaddasdsa\",\"pic\":\"dsadsdsd\"},{\"sub_judul\":\"sdsadsadad\",\"deskripsi\":\"dsadsadadsad\",\"pic\":\"dasdsadasd\"},{\"sub_judul\":\"dasdsadsdaewdas\",\"deskripsi\":\"sdadasdadsad\",\"pic\":\"sadasdasdasdadsad\"}]', '2026-07-12 01:43:25', '2026-07-12 01:43:25'),
(12, 4, 'lampiran', '[{\"judul\":\"sefsadsadad\",\"isi\":\"adssdawedasd\"},{\"judul\":\"sadsadsadaewdxcs\",\"isi\":\"dasdaewdcdszcdawed\"}]', '2026-07-12 01:43:41', '2026-07-12 01:43:41'),
(13, 6, 'tujuan', '[\"FTYUHJKILKJH\",\"DRTFYUGHIJOKLJHUGF\"]', '2026-07-12 02:37:57', '2026-07-12 02:37:57'),
(14, 6, 'ruang_lingkup', '[\"OPIUYTFGUIHOPKJIHUJGJIKO\"]', '2026-07-12 02:37:57', '2026-07-12 02:37:57'),
(15, 6, 'referensi', '[\"[POIUHGYUIOJKH\"]', '2026-07-12 02:38:18', '2026-07-12 02:38:18'),
(16, 6, 'definisi', '[\"OIUGUYUIJKHKLK\"]', '2026-07-12 02:38:18', '2026-07-12 02:38:18'),
(17, 6, 'aktivitas', '[{\"sub_judul\":\"P;JNKLJNLNLJNM\",\"deskripsi\":\"OPHJHJK\",\"pic\":\"JKK\"}]', '2026-07-12 02:38:27', '2026-07-12 02:38:27'),
(18, 6, 'lampiran', '[{\"judul\":\"LMJKLKLM\",\"isi\":\"KLJKLMKLM\"},{\"judul\":\"LKJM\",\"isi\":\"KOLM\"}]', '2026-07-12 02:38:40', '2026-07-12 02:38:40'),
(19, 7, 'tujuan', '[]', '2026-07-12 03:04:41', '2026-07-12 03:04:41'),
(20, 7, 'ruang_lingkup', '[]', '2026-07-12 03:04:41', '2026-07-12 03:04:41'),
(21, 7, 'referensi', '[\"aww\"]', '2026-07-12 03:04:49', '2026-07-12 03:04:49'),
(22, 7, 'definisi', '[\"aww\"]', '2026-07-12 03:04:49', '2026-07-12 03:04:49'),
(23, 7, 'aktivitas', '[{\"sub_judul\":\"awwa\",\"deskripsi\":\"awww\",\"pic\":\"aww\"}]', '2026-07-12 03:05:01', '2026-07-12 03:05:01'),
(24, 7, 'lampiran', NULL, '2026-07-12 03:05:06', '2026-07-12 03:05:06'),
(25, 8, 'tujuan', '[\"Mengatur perawatan IT\"]', '2026-07-12 07:09:27', '2026-07-12 07:09:27'),
(26, 8, 'ruang_lingkup', '[\"Semua perangkat ICT\"]', '2026-07-12 07:09:27', '2026-07-12 07:09:27'),
(27, 8, 'referensi', '[\"ISO 9001:2015\"]', '2026-07-12 07:09:27', '2026-07-12 07:09:27'),
(28, 8, 'definisi', '[\"Hardware = komponen fisik\"]', '2026-07-12 07:09:27', '2026-07-12 07:09:27'),
(29, 8, 'aktivitas', '[{\"sub_judul\":\"Pemeliharaan\",\"deskripsi\":\"Rutin bulanan\",\"pic\":\"ICT\"}]', '2026-07-12 07:11:44', '2026-07-12 07:11:44'),
(30, 8, 'lampiran', '[{\"judul\":\"Form Ceklis\",\"isi\":\"Lihat lampiran\"}]', '2026-07-12 07:11:44', '2026-07-12 07:11:48'),
(31, 9, 'tujuan', '[\"esafsf\",\"sadasd\"]', '2026-07-12 07:17:48', '2026-07-12 07:17:51'),
(32, 9, 'ruang_lingkup', '[\"sadsad\",\"adasd\"]', '2026-07-12 07:17:48', '2026-07-12 07:17:58'),
(33, 9, 'referensi', '[\"sadsad\",\"sadsad\"]', '2026-07-12 07:17:48', '2026-07-12 07:18:03'),
(34, 9, 'definisi', '[\"sadsad\",\"szdsad\"]', '2026-07-12 07:17:48', '2026-07-12 07:18:05'),
(35, 9, 'aktivitas', '[{\"sub_judul\":\"aDASD\",\"deskripsi\":\"ASDASDASD\",\"pic\":\"AsaDS\"},{\"sub_judul\":\"ADADDA\",\"deskripsi\":\"ADSDASDAD\",\"pic\":\"ADASDAS\"}]', '2026-07-12 07:18:15', '2026-07-12 07:18:24'),
(36, 9, 'lampiran', '[{\"judul\":\"salkdmskalmdakl\",\"isi\":null}]', '2026-07-12 07:18:15', '2026-07-12 07:50:53'),
(37, 10, 'tujuan', '[\"Mengatur perawatan perangkat IT secara periodik\",\"Memastikan perangkat siap dioperasikan\"]', '2026-07-12 08:01:07', '2026-07-12 08:07:27'),
(38, 10, 'ruang_lingkup', '[\"Semua perangkat ICT di PPA site Adaro\"]', '2026-07-12 08:01:07', '2026-07-12 08:07:27'),
(39, 10, 'referensi', '[\"ISO 9001:2015 Sistem Manajemen Mutu\",\"SMKP Minerba\"]', '2026-07-12 08:01:07', '2026-07-12 08:07:27'),
(40, 10, 'definisi', NULL, '2026-07-12 08:01:07', '2026-07-12 08:01:07'),
(43, 10, 'aktivitas', '[{\"sub_judul\":\"Pemeliharaan Bulanan\",\"deskripsi\":\"Inspeksi perangkat ICT setiap bulan sesuai periode.\",\"pic\":\"ICT\"}]', '2026-07-12 08:07:27', '2026-07-12 08:07:27'),
(44, 10, 'lampiran', '[{\"judul\":\"Foto Perangkat Server\",\"isi\":null}]', '2026-07-12 08:07:27', '2026-07-12 08:28:56'),
(45, 12, 'tujuan', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 08:33:02', '2026-07-12 08:33:48'),
(46, 12, 'ruang_lingkup', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 08:33:02', '2026-07-12 08:33:54'),
(47, 12, 'referensi', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 08:33:02', '2026-07-12 08:33:59'),
(48, 12, 'definisi', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 08:33:02', '2026-07-12 08:34:02'),
(49, 12, 'aktivitas', '[{\"sub_judul\":\"Lipsum\",\"deskripsi\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"pic\":\"ICT\"},{\"sub_judul\":\"Lipsum 2\",\"deskripsi\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"pic\":\"ICT\"}]', '2026-07-12 08:34:09', '2026-07-12 08:34:23'),
(50, 12, 'lampiran', '[{\"judul\":\"Lipsum\",\"isi\":\"lampiran\\/ICTMD\\/SOP\\/img_6a53c2238bced.png\"}]', '2026-07-12 08:34:09', '2026-07-12 08:34:43'),
(59, 19, 'tujuan', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:03:33', '2026-07-12 16:03:42'),
(60, 19, 'ruang_lingkup', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:03:33', '2026-07-12 16:03:45'),
(61, 19, 'referensi', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:03:33', '2026-07-12 16:03:47'),
(62, 19, 'definisi', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:03:33', '2026-07-12 16:03:49'),
(63, 19, 'aktivitas', '[{\"sub_judul\":\"Lipsum\",\"deskripsi\":\"Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet\",\"pic\":\"ICT MD\"},{\"sub_judul\":\"Lipsum 2\",\"deskripsi\":\"Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet\",\"pic\":\"ICT MD\"},{\"sub_judul\":\"Lipsum 2\",\"deskripsi\":\"Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet Lorep ipsum dolor sit amet\",\"pic\":\"ICT MD\"}]', '2026-07-12 16:03:58', '2026-07-12 16:57:51'),
(64, 19, 'lampiran', '[{\"judul\":\"Lipsum\",\"isi\":\"lampiran\\/ICTMD\\/SOP\\/img_6a542c110c206.png\"},{\"judul\":\"Lipsum 2\",\"isi\":\"lampiran\\/ICTMD\\/SOP\\/img_6a542c111432d.png\"},{\"judul\":\"Lipsum 3\",\"isi\":\"lampiran\\/ICTMD\\/SOP\\/img_6a542c1115eb5.png\"}]', '2026-07-12 16:03:58', '2026-07-12 16:06:41'),
(77, 32, 'tujuan', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:48:19', '2026-07-12 16:48:24'),
(78, 32, 'ruang_lingkup', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:48:19', '2026-07-12 16:48:27'),
(79, 32, 'referensi', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:48:19', '2026-07-12 16:48:35'),
(80, 32, 'definisi', '[\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 16:48:19', '2026-07-12 16:48:38'),
(81, 32, 'aktivitas', '[{\"sub_judul\":\"Lipsum\",\"deskripsi\":\"lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet\",\"pic\":\"ICT\"},{\"sub_judul\":\"Lipsum 2\",\"deskripsi\":\"lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet\",\"pic\":\"ICT MD\"}]', '2026-07-12 16:48:52', '2026-07-12 16:57:34'),
(82, 32, 'lampiran', '[{\"judul\":\"Lipsum 1\",\"isi\":\"lampiran\\/ICTMD\\/SOP\\/img_6a5436431b703.png\"}]', '2026-07-12 16:48:52', '2026-07-12 16:50:11'),
(98, 48, 'tujuan', '[\"Lipsum\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 17:17:16', '2026-07-12 17:17:19'),
(99, 48, 'ruang_lingkup', '[\"Lipsum\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 17:17:16', '2026-07-12 17:17:24'),
(100, 48, 'referensi', '[\"Lipsum\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 17:17:16', '2026-07-12 17:17:32'),
(101, 48, 'definisi', '[\"Lipsum\",\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.\"]', '2026-07-12 17:17:16', '2026-07-12 17:17:34'),
(102, 48, 'aktivitas', '[{\"sub_judul\":\"Lipsum\",\"deskripsi\":\"Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet\",\"pic\":\"Engineering\"},{\"sub_judul\":\"Lipsum 2\",\"deskripsi\":\"Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet\",\"pic\":\"Tim Engineering\"}]', '2026-07-12 17:17:39', '2026-07-12 17:18:26'),
(103, 48, 'lampiran', '[{\"judul\":\"Lipsum 1\",\"isi\":null}]', '2026-07-12 17:17:39', '2026-07-12 17:18:35');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `schema_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema_json`)),
  `class` enum('inti','independen','lintas') NOT NULL DEFAULT 'inti',
  `scope` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `code`, `name`, `schema_json`, `class`, `scope`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'SOP', 'Standard Operating Procedure', '{\"doc_type\":\"SOP\",\"doc_type_label\":\"STANDARD OPERATING PROCEDURE\",\"header\":\"_kop\",\"footer\":\"_footer\",\"footer_text\":\"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.\",\"approval_page\":\"_pengesahan\",\"steps\":[{\"step\":1,\"title\":\"Tujuan, Ruang Lingkup, Referensi & Definisi\",\"sections\":[{\"key\":\"tujuan\",\"label\":\"I. TUJUAN\",\"type\":\"rich_list\",\"auto_number\":\"1.\",\"min_items\":1,\"placeholder\":\"Masukkan poin tujuan...\"},{\"key\":\"ruang_lingkup\",\"label\":\"II. RUANG LINGKUP\",\"type\":\"rich_list\",\"auto_number\":\"2.\",\"min_items\":1,\"placeholder\":\"Masukkan poin ruang lingkup...\"},{\"key\":\"referensi\",\"label\":\"III. REFERENSI\",\"type\":\"reference_picker\",\"auto_number\":\"3.\",\"allow_add\":true,\"placeholder\":\"Masukkan referensi...\",\"suggestions\":[\"ISO 9001:2015 Sistem Manajemen Mutu\",\"ISO 45001:2018 Sistem Manajemen K3\",\"ISO 14001:2015 Sistem Manajemen Lingkungan\",\"SMKP Minerba (Permen ESDM No. 26\\/2018)\",\"UU No. 1 Tahun 1970 tentang Keselamatan Kerja\"]},{\"key\":\"definisi\",\"label\":\"IV. DEFINISI\",\"type\":\"rich_list\",\"auto_number\":\"4.\",\"placeholder\":\"Masukkan definisi...\"}]},{\"step\":2,\"title\":\"Aktivitas, Lampiran & Verifikasi\",\"sections\":[{\"key\":\"aktivitas\",\"label\":\"V. AKTIVITAS DAN TANGGUNG JAWAB\",\"type\":\"repeatable_group\",\"auto_number\":\"5.\",\"min_groups\":1,\"add_button_label\":\"+ Tambah Aktivitas\\/Tanggung Jawab\",\"group_fields\":[{\"key\":\"sub_judul\",\"label\":\"Sub Judul\",\"type\":\"text\",\"placeholder\":\"Sub Judul (Contoh: Tahap Persiapan)\"},{\"key\":\"deskripsi\",\"label\":\"Deskripsi Aktivitas\",\"type\":\"textarea\",\"placeholder\":\"Deskripsi aktivitas...\"},{\"key\":\"pic\",\"label\":\"PIC\",\"type\":\"text\",\"placeholder\":\"PIC (Contoh: Tim ICT)\"}]},{\"key\":\"lampiran\",\"label\":\"VI. LAMPIRAN\",\"type\":\"repeatable_group\",\"min_groups\":0,\"add_button_label\":\"+ Tambah Lampiran Baru\",\"group_fields\":[{\"key\":\"judul\",\"label\":\"Judul Lampiran\",\"type\":\"text\",\"placeholder\":\"Judul Lampiran (Contoh: Form Ceklis)\"},{\"key\":\"isi\",\"label\":\"Isi Lampiran (pilih salah satu: teks atau gambar)\",\"type\":\"text_or_image\",\"text_placeholder\":\"Masukkan keterangan teks jika ada...\",\"image_accept\":\"image\\/jpeg,image\\/png\",\"image_max_mb\":2}]},{\"key\":\"pembuat_tambahan\",\"label\":\"Pembuat Tambahan (opsional)\",\"type\":\"user_picker\",\"multiple\":true,\"required\":false,\"hint\":\"Gunakan tombol + jika pembuat lebih dari 1 orang.\"},{\"key\":\"peninjau\",\"label\":\"Ditinjau Oleh (DH\\/SH)\",\"type\":\"user_picker\",\"role_filter\":[\"group_leader\",\"section_head\"],\"required\":true},{\"key\":\"penyetuju\",\"label\":\"Disetujui Oleh (PJO)\",\"type\":\"user_picker\",\"role_filter\":[\"pimpinan\"],\"required\":true}]}],\"approval_page_layout\":{\"columns\":[\"Nama\",\"Jabatan\",\"Tanggal\",\"Pengesahan\"],\"rows\":[{\"role_label\":\"Dibuat Oleh\",\"role\":\"pembuat\"},{\"role_label\":\"Ditinjau Oleh\",\"role\":\"peninjau\"},{\"role_label\":\"Disetujui Oleh\",\"role\":\"penyetuju\"}],\"stamp_on_published\":\"APPROVED\"}}', 'inti', 'all_departments', 1, '2026-07-12 00:37:14', '2026-07-12 07:06:08'),
(2, 'IK', 'Instruksi Kerja', '{\"doc_type\":\"IK\",\"header\":\"ppa_standard_header\",\"footer\":\"ppa_standard_footer\",\"approval_page\":\"ppa_pengesahan\",\"steps\":[]}', 'inti', 'all_departments', 0, '2026-07-12 06:36:16', '2026-07-12 06:36:16'),
(3, 'SP', 'Standar Parameter', '{\"doc_type\":\"SP\",\"header\":\"ppa_standard_header\",\"footer\":\"ppa_standard_footer\",\"approval_page\":\"ppa_pengesahan\",\"steps\":[]}', 'inti', 'all_departments', 0, '2026-07-12 06:36:16', '2026-07-12 06:36:16'),
(4, 'JSA', 'Job Safety Analysis', '{\"doc_type\":\"JSA\",\"header\":\"ppa_standard_header\",\"footer\":\"ppa_standard_footer\",\"approval_page\":\"ppa_pengesahan\",\"steps\":[]}', 'inti', 'all_departments', 0, '2026-07-12 06:36:16', '2026-07-12 06:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `document_versions`
--

CREATE TABLE `document_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `no_revisi` int(10) UNSIGNED NOT NULL,
  `snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot_json`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_12_082503_create_permission_tables', 1),
(5, '2026_07_12_090001_create_departments_table', 1),
(6, '2026_07_12_090002_create_document_types_table', 1),
(7, '2026_07_12_090003_create_documents_table', 1),
(8, '2026_07_12_090004_create_document_contents_table', 1),
(9, '2026_07_12_090005_create_document_versions_table', 1),
(10, '2026_07_12_090006_create_reviews_table', 1),
(11, '2026_07_12_090007_create_approvals_table', 1),
(12, '2026_07_12_090008_create_attachments_table', 1),
(13, '2026_07_12_090009_create_audit_logs_table', 1),
(14, '2026_07_12_090010_create_notifications_table', 1),
(15, '2026_07_12_100000_add_username_and_phone_to_users_table', 2),
(16, '2026_07_12_110000_create_document_authors_table', 3),
(17, '2026_07_13_090000_expand_documents_status_enum', 4);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(3, 'App\\Models\\User', 9),
(3, 'App\\Models\\User', 10),
(3, 'App\\Models\\User', 15),
(4, 'App\\Models\\User', 4),
(4, 'App\\Models\\User', 7),
(4, 'App\\Models\\User', 11),
(4, 'App\\Models\\User', 12),
(6, 'App\\Models\\User', 5),
(6, 'App\\Models\\User', 6),
(6, 'App\\Models\\User', 8),
(6, 'App\\Models\\User', 13),
(6, 'App\\Models\\User', 14),
(6, 'App\\Models\\User', 16);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('13c013f2-c8c8-4609-a869-44614887e861', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 13, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.index\"}', NULL, '2026-07-12 17:10:26', '2026-07-12 17:10:26'),
('2184a806-ae7a-40cf-88a5-b1c427efb4b3', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 4, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-07-12 17:02:22', '2026-07-12 17:02:22'),
('2b186397-ad1b-44e8-88fc-50451d8ec5c4', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 4, '{\"document_id\":19,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"Testing Fase 3 (revisi jika di reject)\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-10 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-07-12 16:58:08', '2026-07-12 16:58:08'),
('2bb9e4f9-e186-4c99-84d0-8dc39a0cb4c5', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 13, '{\"document_id\":19,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"Testing Fase 3 (revisi jika di reject)\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-10 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.index\"}', NULL, '2026-07-12 17:00:50', '2026-07-12 17:00:50'),
('2d608a6d-911c-406a-831c-736cf11bff5b', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 4, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 yang Anda loloskan ditolak approver.\",\"icon\":\"bi-exclamation-triangle\",\"route\":\"review.index\"}', NULL, '2026-07-12 17:01:12', '2026-07-12 17:01:12'),
('3142f1df-2aae-4b7a-9c12-8982f25901e6', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 2, '{\"document_id\":10,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-08\",\"title\":\"Uji Lampiran Gambar\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-08 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.index\"}', NULL, '2026-07-12 17:05:50', '2026-07-12 17:05:50'),
('3f1d0163-1be6-4288-b9eb-ea9b05d014ec', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 13, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 ditolak approver \\u2014 perlu revisi.\",\"icon\":\"bi-x-circle\",\"route\":\"documents.revisi\"}', '2026-07-12 17:02:15', '2026-07-12 17:01:12', '2026-07-12 17:02:15'),
('5a5eb848-bbb2-4d73-ac05-dd75559b8058', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 4, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-07-12 16:50:11', '2026-07-12 16:50:11'),
('816dab90-7377-450e-82fd-222d12519b9d', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 2, '{\"document_id\":19,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-10\",\"title\":\"Testing Fase 3 (revisi jika di reject)\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-10 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.index\"}', '2026-07-12 17:00:30', '2026-07-12 17:00:08', '2026-07-12 17:00:30'),
('ad74299a-06c3-4929-bef4-4569c4de0b8e', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 13, '{\"document_id\":10,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-08\",\"title\":\"Uji Lampiran Gambar\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-08 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.index\"}', NULL, '2026-07-12 17:10:20', '2026-07-12 17:10:20'),
('ad99142b-e351-403b-855e-6444fcab4413', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 13, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 dikembalikan untuk revisi.\",\"icon\":\"bi-arrow-counterclockwise\",\"route\":\"documents.revisi\"}', '2026-07-12 16:57:17', '2026-07-12 16:56:43', '2026-07-12 16:57:17'),
('d36e94a7-3bb2-4113-8eab-9c5fe4f2cf15', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 2, '{\"document_id\":48,\"doc_number\":\"PPA-ADRO-SOP-ENGINEERING-02\",\"title\":\"Lorep Ipsum\",\"message\":\"Dokumen PPA-ADRO-SOP-ENGINEERING-02 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.index\"}', NULL, '2026-07-12 17:19:27', '2026-07-12 17:19:27'),
('da79ae99-b892-4568-afba-51c12a248ffd', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 16, '{\"document_id\":48,\"doc_number\":\"PPA-ADRO-SOP-ENGINEERING-02\",\"title\":\"Lorep Ipsum\",\"message\":\"Dokumen PPA-ADRO-SOP-ENGINEERING-02 disetujui dan kini Berlaku.\",\"icon\":\"bi-check-circle\",\"route\":\"documents.index\"}', NULL, '2026-07-12 17:20:09', '2026-07-12 17:20:09'),
('e236c51e-bedb-4efa-b3ba-9fbcef4450ab', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 4, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-07-12 16:57:37', '2026-07-12 16:57:37'),
('eb4e437f-291e-423d-9856-94c242a2509e', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 2, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.index\"}', NULL, '2026-07-12 17:03:28', '2026-07-12 17:03:28'),
('f7c02df3-a1fb-4f96-a405-9cde53568853', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 2, '{\"document_id\":32,\"doc_number\":\"PPA-ADRO-SOP-ICTMD-11\",\"title\":\"Testing notifikasi\",\"message\":\"Dokumen PPA-ADRO-SOP-ICTMD-11 perlu disetujui.\",\"icon\":\"bi-patch-check\",\"route\":\"approvals.index\"}', NULL, '2026-07-12 16:59:23', '2026-07-12 16:59:23'),
('f7e6beb0-c510-411d-bea0-e5241ae68fc8', 'App\\Notifications\\DocumentNotification', 'App\\Models\\User', 10, '{\"document_id\":48,\"doc_number\":\"PPA-ADRO-SOP-ENGINEERING-02\",\"title\":\"Lorep Ipsum\",\"message\":\"Dokumen PPA-ADRO-SOP-ENGINEERING-02 perlu ditinjau.\",\"icon\":\"bi-clipboard-check\",\"route\":\"review.index\"}', NULL, '2026-07-12 17:18:55', '2026-07-12 17:18:55');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'document.create', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(2, 'document.edit', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(3, 'document.submit', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(4, 'document.delete', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(5, 'document.review', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(6, 'document.approve', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(7, 'document.publish', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(8, 'document.view_department', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(9, 'document.view_scope', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(10, 'document.view_all', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(11, 'user.manage', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(12, 'user.approve_registration', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(13, 'user.create_staff', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(14, 'document.change_status', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(15, 'audit.view', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(16, 'document.request_revision', 'web', '2026-07-12 06:36:15', '2026-07-12 06:36:15');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `reviewer_id` bigint(20) UNSIGNED NOT NULL,
  `revision_round` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `decision` enum('pending','approved','needs_revision') NOT NULL DEFAULT 'pending',
  `summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `document_id`, `reviewer_id`, `revision_round`, `decision`, `summary`, `created_at`, `updated_at`) VALUES
(8, 19, 4, 0, 'needs_revision', 'masih salah rata rata. ulangi lagi', '2026-07-12 16:09:33', '2026-07-12 16:09:33'),
(18, 32, 4, 0, 'needs_revision', 'SALAH', '2026-07-12 16:56:43', '2026-07-12 16:56:43'),
(22, 32, 4, 1, 'approved', NULL, '2026-07-12 16:59:23', '2026-07-12 16:59:23'),
(23, 19, 4, 1, 'approved', NULL, '2026-07-12 17:00:08', '2026-07-12 17:00:08'),
(27, 32, 4, 2, 'approved', NULL, '2026-07-12 17:03:28', '2026-07-12 17:03:28'),
(32, 10, 4, 0, 'approved', 'kjewcnfdkolmedcwfd', '2026-07-12 17:05:50', '2026-07-12 17:05:50'),
(33, 48, 10, 0, 'approved', 'iujnuiuijjn', '2026-07-12 17:19:27', '2026-07-12 17:19:27');

-- --------------------------------------------------------

--
-- Table structure for table `review_annotations`
--

CREATE TABLE `review_annotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `review_id` bigint(20) UNSIGNED NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `item_ref` varchar(255) DEFAULT NULL,
  `severity` enum('info','minor','major','critical') NOT NULL DEFAULT 'minor',
  `comment` text NOT NULL,
  `ai_generated` tinyint(1) NOT NULL DEFAULT 0,
  `ai_adopted` tinyint(1) NOT NULL DEFAULT 0,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `review_annotations`
--

INSERT INTO `review_annotations` (`id`, `review_id`, `section_key`, `item_ref`, `severity`, `comment`, `ai_generated`, `ai_adopted`, `resolved`, `created_at`, `updated_at`) VALUES
(5, 8, 'tujuan', '0', 'minor', 'Ini masih salah', 0, 0, 0, '2026-07-12 16:09:33', '2026-07-12 16:09:33'),
(6, 8, 'definisi', '0', 'minor', 'salah, ulangi lagi. define dengan benar', 0, 0, 0, '2026-07-12 16:09:33', '2026-07-12 16:09:33'),
(7, 8, 'lampiran', '0', 'minor', 'ini ganti jadi yang benar juga', 0, 0, 0, '2026-07-12 16:09:33', '2026-07-12 16:09:33'),
(10, 18, 'lampiran', '0', 'minor', 'SALAH', 0, 0, 0, '2026-07-12 16:56:43', '2026-07-12 16:56:43'),
(14, 32, 'lampiran', '0', 'minor', 'sac', 0, 0, 0, '2026-07-12 17:05:50', '2026-07-12 17:05:50');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin_it', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(2, 'pimpinan', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(3, 'section_head', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(4, 'group_leader', 'web', '2026-07-12 00:37:14', '2026-07-12 00:37:14'),
(6, 'staff', 'web', '2026-07-12 06:36:16', '2026-07-12 06:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 4),
(1, 6),
(2, 1),
(2, 4),
(2, 6),
(3, 1),
(3, 4),
(3, 6),
(4, 1),
(4, 4),
(4, 6),
(5, 1),
(5, 3),
(5, 4),
(6, 1),
(6, 2),
(6, 3),
(7, 1),
(7, 2),
(8, 1),
(8, 3),
(8, 4),
(8, 6),
(9, 1),
(10, 1),
(10, 2),
(11, 1),
(12, 1),
(12, 2),
(12, 3),
(12, 4),
(13, 1),
(14, 1),
(15, 1),
(15, 2),
(15, 3),
(15, 4),
(16, 1),
(16, 2),
(16, 3),
(16, 4);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('19nqWWFG7OXkSSyWDchxU4K4LWmN9ULUtXpLKRWF', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRUVRUW1jdlcza2IzV2dHMTFUUmVlVHNtVGN3TkdyTllod25TSGltcCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yZXZpZXcvOCI7czo1OiJyb3V0ZSI7czoxMToicmV2aWV3LnNob3ciO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0O30=', 1783909196),
('n1LVhWIh8x1jMZQowHsH7PfWfmVAiq6zEE20m81H', 4, '127.0.0.1', 'curl/8.21.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRXJwR2JPUHBuRTNDbWJLTzB0eXdvUDhOYmEyU2drWnNYcWl6V0p4TCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDt9', 1783902930),
('oE3AnFjQLDJNSAYANaoF9ZP2ETLZTJmHWkeVbUgE', 1, '127.0.0.1', 'curl/8.21.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVW5PNGx4cUFYREFuVGtwdTFIbzE0NXlLQTZ5ZzNTb2JVUHhCczlrWSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kb2N1bWVudHM/cT1TT1Amc3RhdHVzPXB1Ymxpc2hlZCZ0eXBlPVNPUCI7czo1OiJyb3V0ZSI7czoxNToiZG9jdW1lbnRzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1783904225);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `nrp` varchar(255) DEFAULT NULL,
  `jabatan` varchar(255) DEFAULT NULL,
  `nomor_hp` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `nrp`, `jabatan`, `nomor_hp`, `email`, `email_verified_at`, `password`, `department_id`, `status`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Admin IT', 'admin', 'ADM-0001', NULL, '081200000001', 'admin@smartpro.test', '2026-07-12 06:36:17', '$2y$12$.zSpz8Q5Qz/Z.cfMysse/ub8bQz1cB.ILVGEzeI56KmYPr43xVgvu', 5, 'active', 'mkzwLPaGnOBB0r3aL471pp7Mfmg8cllSXyieBO09WaoO4cj1d8gCj9hQlfUR', '2026-07-12 00:37:14', '2026-07-12 06:36:17', NULL),
(2, 'Wahyu Binuko', 'pimpinan', 'PJO-0001', 'pimpinan', '081200000002', 'pimpinan@smartpro.test', '2026-07-12 06:36:17', '$2y$12$BGc/.Ip/ibaKqrl6XU63AOCI4ZJoJR2S.HyDW4h21RHIrnRUGJ7Ci', 5, 'active', NULL, '2026-07-12 00:37:15', '2026-07-12 06:36:17', NULL),
(3, 'Arisal Farzan', 'sectionhead', 'SH-0001', 'section_head', '081200000003', 'sectionhead@smartpro.test', '2026-07-12 06:36:18', '$2y$12$Kcggpg4.0Adc1GJt5wPqr.V55Ac4i5V2Je/8IYufySRg/cW4g1fHC', 5, 'active', NULL, '2026-07-12 00:37:15', '2026-07-12 06:36:18', NULL),
(4, 'Angga Margi Saputro', 'groupleader', 'GL-0001', 'group_leader', '081200000004', 'groupleader@smartpro.test', '2026-07-12 06:36:18', '$2y$12$TQr49U1kR5p53fr7/HqNvevSc5thAwdC8G5IuLQcVnjXR/2AbdYGK', 5, 'active', 'sJB0x4rU99NbcYc72w4JVakZSdWIFwYirYaQXrIVHHIUqO1pBLcwvvXG4P7N', '2026-07-12 00:37:16', '2026-07-12 06:36:18', NULL),
(5, 'User ICTMD', 'user', 'USR-0001', 'Staff ICTMD', '081200000005', 'user@smartpro.test', '2026-07-12 00:37:16', '$2y$12$1wXxN1MuIwd30Q5NnfQq3.WarH75qMuyXtdkvwfn9wt6EKQHMSv8O', 5, 'active', NULL, '2026-07-12 00:37:16', '2026-07-12 00:37:16', NULL),
(6, 'Budi Santoso', 'budi', 'SHE-9999', 'Operator', '081200000006', 'budi@smartpro.test', NULL, '$2y$12$tOpjPWNVFLK/ST9GErwdhumwYUIOfDpCvL.gj.EcNrFc5/Rg6LNWO', 1, 'active', NULL, '2026-07-12 00:47:41', '2026-07-12 00:48:15', NULL),
(7, 'Andi Plant', 'andiplant', 'PLT-0007', 'Group Leader Plant', '081200000007', 'andi.plant@smartpro.test', NULL, '$2y$12$BlzYw2e8bKwJ0VyvUNv5zOcWv2BS7R3o/uoOTyYHPu.QG6h0Y0Kzy', 2, 'rejected', NULL, '2026-07-12 00:57:00', '2026-07-12 00:57:21', NULL),
(8, 'Citra Dewi', 'citra.dewi', 'HCGA-1234', 'Admin HR', '081298765432', NULL, NULL, '$2y$12$Ut3XysXsfOb7TLraEeEKK.esbhfDr9zeVwxxs/3TccKnwHPiT3Nwu', 3, 'active', NULL, '2026-07-12 01:09:09', '2026-07-12 01:44:32', NULL),
(9, 'Dedi SHE', 'dedi.she', 'SHE-5555', NULL, '081211112222', NULL, NULL, '$2y$12$d2WKhx9steNXXFLv5KFOiumJ8OapV/9BvSzKoU7epZr8Fw8RWS3nm', 1, 'active', NULL, '2026-07-12 01:09:13', '2026-07-12 01:09:13', NULL),
(10, 'udin anjaya', 'udin', '250504', 'Staff Eng.', '085753097927', 'joko@gmail.com', NULL, '$2y$12$7KDNB60F93nVTN1YDXj86eTCQhQqcWjQuHrsEBW22TV2PdYhjDPy.', 7, 'active', NULL, '2026-07-12 01:12:37', '2026-07-12 01:12:37', NULL),
(11, 'Budi Santoso', NULL, 'GL-0002', 'group_leader', NULL, NULL, '2026-07-12 06:36:19', '$2y$12$m97/LUOIRR9P4K/TI3xK6ujaY/PaPl3W4CKoZ169uTEsRi5eJlNlW', 2, 'active', NULL, '2026-07-12 06:36:19', '2026-07-12 06:36:19', NULL),
(12, 'Citra Dewi', NULL, 'GL-0003', 'group_leader', NULL, NULL, '2026-07-12 06:36:19', '$2y$12$/uxKoc53YDDJX76xKtBQYeE7PkBjD0Zf.YepwX8FaQlbAV36YtSTS', 1, 'active', NULL, '2026-07-12 06:36:19', '2026-07-12 06:36:19', NULL),
(13, 'Staff ICTMD', NULL, 'STF-0001', 'staff', NULL, NULL, '2026-07-12 06:36:20', '$2y$12$DEiqX8tZRJr.JvpB5ukjqeXMnULUtSjS5CHP04AQB.MAdfnWtBbDS', 5, 'active', NULL, '2026-07-12 06:36:20', '2026-07-12 06:36:20', NULL),
(14, 'Eko Prasetyo', NULL, 'ENG-7777', 'staff', '081277778888', NULL, NULL, '$2y$12$3b7LvH8fv4iLwoVJbkaeS.EEVWdQvVJ.OWPU2sMCmMrDEteY0g2Mm', 7, 'active', NULL, '2026-07-12 06:43:54', '2026-07-12 06:48:23', NULL),
(15, 'Rina Section', NULL, 'SH-HCGA-1', 'section_head', '0812', NULL, NULL, '$2y$12$M25Y.ZoK/pCpl/SqefMBYecl/wKCGtLk2vy9G4gKk8TrOPtWFqOwi', 3, 'active', NULL, '2026-07-12 06:48:28', '2026-07-12 06:48:28', NULL),
(16, 'Muhammad Surya Aji Praja', NULL, 'STF-471', 'staff', '085753097927', NULL, NULL, '$2y$12$PrTah0KBHAOFnFp3dMEt2.TYMns5oePb6H9TFBim1XbVB9M1d9rIa', 7, 'active', NULL, '2026-07-12 17:15:01', '2026-07-12 17:16:28', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approvals_document_id_foreign` (`document_id`),
  ADD KEY `approvals_approver_id_index` (`approver_id`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attachments_document_id_foreign` (`document_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_index` (`user_id`),
  ADD KEY `audit_logs_document_id_index` (`document_id`),
  ADD KEY `audit_logs_action_index` (`action`),
  ADD KEY `audit_logs_created_at_index` (`created_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_document_type_id_foreign` (`document_type_id`),
  ADD KEY `documents_doc_number_index` (`doc_number`),
  ADD KEY `documents_department_id_index` (`department_id`),
  ADD KEY `documents_status_index` (`status`),
  ADD KEY `documents_reviewer_id_index` (`reviewer_id`),
  ADD KEY `documents_approver_id_index` (`approver_id`),
  ADD KEY `documents_created_by_index` (`created_by`);

--
-- Indexes for table `document_authors`
--
ALTER TABLE `document_authors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_authors_document_id_user_id_unique` (`document_id`,`user_id`),
  ADD KEY `document_authors_user_id_foreign` (`user_id`);

--
-- Indexes for table `document_contents`
--
ALTER TABLE `document_contents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_contents_document_id_section_key_unique` (`document_id`,`section_key`),
  ADD KEY `document_contents_section_key_index` (`section_key`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_types_code_unique` (`code`);

--
-- Indexes for table `document_versions`
--
ALTER TABLE `document_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_versions_document_id_foreign` (`document_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_document_id_foreign` (`document_id`),
  ADD KEY `reviews_reviewer_id_index` (`reviewer_id`);

--
-- Indexes for table `review_annotations`
--
ALTER TABLE `review_annotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_annotations_review_id_foreign` (`review_id`),
  ADD KEY `review_annotations_section_key_index` (`section_key`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_nrp_unique` (`nrp`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_department_id_index` (`department_id`),
  ADD KEY `users_status_index` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `document_authors`
--
ALTER TABLE `document_authors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `document_contents`
--
ALTER TABLE `document_contents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `review_annotations`
--
ALTER TABLE `review_annotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `approvals_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`);

--
-- Constraints for table `document_authors`
--
ALTER TABLE `document_authors`
  ADD CONSTRAINT `document_authors_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_authors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `document_contents`
--
ALTER TABLE `document_contents`
  ADD CONSTRAINT `document_contents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_versions`
--
ALTER TABLE `document_versions`
  ADD CONSTRAINT `document_versions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_annotations`
--
ALTER TABLE `review_annotations`
  ADD CONSTRAINT `review_annotations_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
