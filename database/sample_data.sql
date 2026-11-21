-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 15, 2026 at 06:06 PM
-- Server version: 5.7.44
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `batchfund`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `publish_date` date NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `entity` varchar(40) NOT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `details` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `entity`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(99, 3, 'add_event_images', 'collection', 19, '1 image(s) added', '::1', '2026-11-15 06:37:16'),
(100, 3, 'create_collection', 'collection', 19, 'monthly: October Collection', '::1', '2026-11-15 06:37:16'),
(101, 3, 'add_event_images', 'collection', 20, '1 image(s) added', '::1', '2026-11-15 06:37:48'),
(102, 3, 'create_collection', 'collection', 20, 'monthly: November Collection', '::1', '2026-11-15 06:37:48'),
(103, 3, 'add_event_images', 'collection', 21, '1 image(s) added', '::1', '2026-11-15 06:41:14'),
(104, 3, 'create_collection', 'collection', 21, 'event: Batch Trip - Hantana', '::1', '2026-11-15 06:41:14'),
(105, 3, 'logout', 'user', 3, '', '::1', '2026-11-15 06:57:37'),
(106, 3, 'login', 'user', 3, 'Successful login', '::1', '2026-11-15 06:58:59'),
(107, 3, 'submit_payment', 'payment', 25, 'Batch Trip - Hantana ref 1123123', '::1', '2026-11-15 07:00:33'),
(108, 3, 'submit_payment', 'payment', 26, 'October Collection ref 12312321', '::1', '2026-11-15 07:00:48'),
(109, 3, 'submit_payment', 'payment', 27, 'November Collection ref 12322', '::1', '2026-11-15 07:01:18'),
(110, 3, 'logout', 'user', 3, '', '::1', '2026-11-15 07:01:25'),
(111, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 07:02:03'),
(112, 2, 'submit_payment', 'payment', 28, 'October Collection ref 121241234', '::1', '2026-11-15 07:02:20'),
(113, 2, 'submit_payment', 'payment', 29, 'November Collection ref 1234123', '::1', '2026-11-15 07:02:31'),
(114, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 07:02:37'),
(115, 4, 'login', 'user', 4, 'Successful login', '::1', '2026-11-15 07:04:31'),
(116, 4, 'submit_payment', 'payment', 30, 'October Collection ref 23123123', '::1', '2026-11-15 07:05:20'),
(117, 4, 'logout', 'user', 4, '', '::1', '2026-11-15 07:05:27'),
(118, 3, 'login', 'user', 3, 'Successful login', '::1', '2026-11-15 07:06:11'),
(119, 3, 'logout', 'user', 3, '', '::1', '2026-11-15 07:06:18'),
(120, 5, 'login', 'user', 5, 'Successful login', '::1', '2026-11-15 07:06:31'),
(121, 5, 'submit_payment', 'payment', 31, 'October Collection ref 123123', '::1', '2026-11-15 07:06:45'),
(122, 5, 'logout', 'user', 5, '', '::1', '2026-11-15 07:07:18'),
(123, 6, 'login', 'user', 6, 'Successful login', '::1', '2026-11-15 07:07:30'),
(124, 6, 'submit_payment', 'payment', 32, 'October Collection ref 12345', '::1', '2026-11-15 07:07:44'),
(125, 6, 'logout', 'user', 6, '', '::1', '2026-11-15 07:10:01'),
(126, 7, 'login', 'user', 7, 'Successful login', '::1', '2026-11-15 07:10:14'),
(127, 7, 'submit_payment', 'payment', 33, 'October Collection ref 1234', '::1', '2026-11-15 07:10:55'),
(128, 7, 'logout', 'user', 7, '', '::1', '2026-11-15 07:11:01'),
(129, 8, 'login', 'user', 8, 'Successful login', '::1', '2026-11-15 07:11:08'),
(130, 8, 'submit_payment', 'payment', 34, 'October Collection ref 1231222', '::1', '2026-11-15 07:11:24'),
(131, 8, 'logout', 'user', 8, '', '::1', '2026-11-15 07:11:50'),
(132, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 07:11:58'),
(133, 9, 'submit_payment', 'payment', 35, 'October Collection ref 21231234123', '::1', '2026-11-15 07:12:08'),
(134, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 07:12:18'),
(135, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 07:12:21'),
(136, 1, 'approve_payment', 'payment', 35, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:32'),
(137, 1, 'approve_payment', 'payment', 25, 'Rs. 3,600.00 – Batch Trip - Hantana', '::1', '2026-11-15 07:12:35'),
(138, 1, 'approve_payment', 'payment', 26, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:37'),
(139, 1, 'approve_payment', 'payment', 27, 'Rs. 100.00 – November Collection', '::1', '2026-11-15 07:12:40'),
(140, 1, 'approve_payment', 'payment', 28, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:42'),
(141, 1, 'approve_payment', 'payment', 30, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:44'),
(142, 1, 'approve_payment', 'payment', 29, 'Rs. 100.00 – November Collection', '::1', '2026-11-15 07:12:46'),
(143, 1, 'approve_payment', 'payment', 31, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:48'),
(144, 1, 'approve_payment', 'payment', 32, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:50'),
(145, 1, 'approve_payment', 'payment', 33, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:52'),
(146, 1, 'approve_payment', 'payment', 34, 'Rs. 100.00 – October Collection', '::1', '2026-11-15 07:12:54'),
(147, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 07:13:09'),
(148, 7, 'login', 'user', 7, 'Successful login', '::1', '2026-11-15 07:13:13'),
(149, 7, 'submit_payment', 'payment', 36, 'Batch Trip - Hantana ref 124888', '::1', '2026-11-15 07:13:43'),
(150, 7, 'logout', 'user', 7, '', '::1', '2026-11-15 07:14:00'),
(151, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 07:14:03'),
(152, 1, 'close_collection', 'collection', 19, '', '::1', '2026-11-15 07:14:12'),
(153, 1, 'approve_payment', 'payment', 36, 'Rs. 3,600.00 – Batch Trip - Hantana', '::1', '2026-11-15 07:14:34'),
(154, 1, 'add_expense', 'expense', 7, 'Welfare Gift - Vihanga Rs. 2,000.00 [pending]', '::1', '2026-11-15 07:15:45'),
(155, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 07:16:00'),
(156, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 07:16:43'),
(157, 2, 'approve_expense', 'expense', 7, 'Welfare Gift - Vihanga Rs. 2,000.00', '::1', '2026-11-15 07:17:21'),
(158, 2, 'add_expense', 'expense', 8, 'Faculty T-Shirt Sample Rs. 4,000.00 [pending]', '::1', '2026-11-15 07:18:52'),
(159, 2, 'delete_expense', 'expense', 8, 'Faculty T-Shirt Sample', '::1', '2026-11-15 07:19:00'),
(160, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 07:19:03'),
(161, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 07:19:06'),
(162, 1, 'add_expense', 'expense', 9, 'Faculty T-Shirt Sample Rs. 4,000.00 [pending]', '::1', '2026-11-15 07:24:50'),
(163, 1, 'add_expense', 'expense', 10, 'Ceremony Decoration Rs. 5,000.00 [pending]', '::1', '2026-11-15 07:37:41'),
(164, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 07:37:49'),
(165, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 07:37:53'),
(166, 2, 'approve_expense', 'expense', 10, 'Ceremony Decoration Rs. 5,000.00', '::1', '2026-11-15 07:37:58'),
(167, 2, 'approve_expense', 'expense', 9, 'Faculty T-Shirt Sample Rs. 4,000.00', '::1', '2026-11-15 07:38:00'),
(168, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 07:38:34'),
(169, 8, 'login', 'user', 8, 'Successful login', '::1', '2026-11-15 07:38:44'),
(170, 8, 'submit_payment', 'payment', 37, 'Batch Trip - Hantana ref 1231222333', '::1', '2026-11-15 07:38:59'),
(171, 8, 'logout', 'user', 8, '', '::1', '2026-11-15 07:39:06'),
(172, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 07:39:22'),
(173, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 07:39:31'),
(174, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 07:39:41'),
(175, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 07:39:47'),
(176, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 07:39:51'),
(177, 1, 'approve_payment', 'payment', 37, 'Rs. 3,600.00 – Batch Trip - Hantana', '::1', '2026-11-15 07:40:03'),
(178, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 09:44:33'),
(179, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 09:44:37'),
(180, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 10:11:01'),
(181, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 10:12:03'),
(182, 1, 'reset_password', 'student', 3, '', '::1', '2026-11-15 10:25:54'),
(183, 1, 'add_expense', 'expense', 11, 'Food Stall Rs. 3,000.00 [pending]', '::1', '2026-11-15 10:58:15'),
(184, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 10:58:20'),
(185, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 10:58:24'),
(186, 2, 'approve_expense', 'expense', 11, 'Food Stall Rs. 3,000.00', '::1', '2026-11-15 10:58:30'),
(187, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 10:58:36'),
(188, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 10:58:39'),
(189, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 10:59:10'),
(190, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 10:59:14'),
(191, 1, 'update_expense', 'expense', 10, 'was Rs. 5,000.00 now Rs. 2,000.00 [pending]', '::1', '2026-11-15 10:59:35'),
(192, 1, 'update_expense', 'expense', 9, 'was Rs. 4,000.00 now Rs. 1,499.68 [pending]', '::1', '2026-11-15 10:59:50'),
(193, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 10:59:54'),
(194, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 10:59:57'),
(195, 2, 'approve_expense', 'expense', 10, 'Ceremony Decoration Rs. 2,000.00', '::1', '2026-11-15 11:00:06'),
(196, 2, 'approve_expense', 'expense', 9, 'Faculty T-Shirt Sample Rs. 1,499.68', '::1', '2026-11-15 11:00:08'),
(197, 2, 'reject_expense', 'expense', 9, 'Faculty T-Shirt Sample Rs. 1,499.68', '::1', '2026-11-15 11:00:32'),
(198, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 11:00:38'),
(199, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 11:00:42'),
(200, 1, 'update_expense', 'expense', 9, 'was Rs. 1,499.68 now Rs. 1,500.00 [rejected]', '::1', '2026-11-15 11:01:15'),
(201, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 11:01:19'),
(202, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 11:01:22'),
(203, 2, 'approve_expense', 'expense', 9, 'Faculty T-Shirt Sample Rs. 1,500.00', '::1', '2026-11-15 11:01:27'),
(204, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 11:02:39'),
(205, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 11:02:42'),
(206, 1, 'export_csv', 'report', NULL, 'collections', '::1', '2026-11-15 11:14:34'),
(207, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 11:21:37'),
(208, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 11:21:41'),
(209, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 16:31:52'),
(210, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 16:33:17'),
(211, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 16:39:34'),
(212, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 16:39:36'),
(213, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 16:41:25'),
(214, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 16:41:27'),
(215, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 16:48:15'),
(216, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 16:48:18'),
(217, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 16:53:51'),
(218, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 16:53:57'),
(219, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 16:57:48'),
(220, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 16:57:50'),
(221, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 17:01:33'),
(222, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 17:04:44'),
(223, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 17:04:57'),
(224, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 17:07:11'),
(225, 9, 'login', 'user', 9, 'Successful login', '::1', '2026-11-15 17:08:50'),
(226, 9, 'submit_payment', 'payment', 38, 'Batch Trip - Hantana ref 12312313123', '::1', '2026-11-15 17:09:53'),
(227, 9, 'logout', 'user', 9, '', '::1', '2026-11-15 17:10:22'),
(228, 1, 'login', 'user', 1, 'Successful login', '::1', '2026-11-15 17:10:29'),
(229, 1, 'add_event_images', 'collection', 22, '1 image(s) added', '::1', '2026-11-15 17:14:40'),
(230, 1, 'create_collection', 'collection', 22, 'event: Faculty T-Shirt', '::1', '2026-11-15 17:14:40'),
(231, 1, 'approve_payment', 'payment', 38, 'Rs. 3,600.00 – Batch Trip - Hantana', '::1', '2026-11-15 17:15:27'),
(232, 1, 'add_expense', 'expense', 12, 'Batch 26 Key Tags Rs. 1,000.00 [pending]', '::1', '2026-11-15 17:17:13'),
(233, 1, 'logout', 'user', 1, '', '::1', '2026-11-15 17:18:05'),
(234, 2, 'login', 'user', 2, 'Successful login', '::1', '2026-11-15 17:18:09'),
(235, 2, 'approve_expense', 'expense', 12, 'Batch 26 Key Tags Rs. 1,000.00', '::1', '2026-11-15 17:18:18'),
(236, 2, 'logout', 'user', 2, '', '::1', '2026-11-15 17:18:43');

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `collection_id` int(10) UNSIGNED NOT NULL,
  `kind` enum('monthly','event') NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `amount` decimal(10,2) NOT NULL,
  `target_amount` decimal(12,2) DEFAULT NULL,
  `collection_month` date DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `deadline` date NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`collection_id`, `kind`, `title`, `description`, `amount`, `target_amount`, `collection_month`, `event_date`, `deadline`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(19, 'monthly', 'October Collection', '', 100.00, NULL, '2026-10-01', NULL, '2026-10-31', 'closed', 3, 1, '2026-11-15 06:37:16', '2026-11-15 07:14:12'),
(20, 'monthly', 'November Collection', '', 100.00, NULL, '2026-11-01', NULL, '2026-11-30', 'open', 3, NULL, '2026-11-15 06:37:48', '2026-11-15 06:37:48'),
(21, 'event', 'Batch Trip - Hantana', '', 3600.00, NULL, NULL, '2026-12-20', '2026-10-31', 'open', 3, NULL, '2026-11-15 06:41:14', '2026-11-15 06:41:14'),
(22, 'event', 'Faculty T-Shirt', '', 2200.00, NULL, NULL, NULL, '2026-11-30', 'open', 1, NULL, '2026-11-15 17:14:40', '2026-11-15 17:14:40');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED DEFAULT NULL,
  `recipient_email` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `collection_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` varchar(250) DEFAULT NULL,
  `sent_by` int(10) UNSIGNED DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `event_images`
--

CREATE TABLE `event_images` (
  `image_id` int(10) UNSIGNED NOT NULL,
  `collection_id` int(10) UNSIGNED NOT NULL,
  `stored_name` varchar(80) NOT NULL,
  `original_name` varchar(200) NOT NULL,
  `mime_type` varchar(80) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `event_images`
--

INSERT INTO `event_images` (`image_id`, `collection_id`, `stored_name`, `original_name`, `mime_type`, `file_size`, `uploaded_by`, `uploaded_at`) VALUES
(13, 19, '54696c1232580cdab0e9a487870ab948.jpg', 'Gemini_Generated_Image_13koqx13koqx13ko.jpg', 'image/jpeg', 73236, 3, '2026-11-15 06:37:16'),
(14, 20, '55cc938d1ee56325abbc9d66d53636e6.jpg', 'Gemini_Generated_Image_13koqx13koqx13ko.jpg', 'image/jpeg', 72201, 3, '2026-11-15 06:37:48'),
(15, 21, '25922945fb09a7734980d9c8fd10a35e.jpg', 'images.jpg', 'image/jpeg', 48528, 3, '2026-11-15 06:41:14'),
(16, 22, 'b4741b2a5f9570d273625b467550d6ec.jpg', 'PHOTO-2026-04-10-00-07-25.jpg', 'image/jpeg', 114425, 1, '2026-11-15 17:14:40');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `collection_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `title`, `description`, `category`, `amount`, `expense_date`, `collection_id`, `status`, `created_by`, `approved_by`, `approved_at`, `updated_by`, `updated_at`, `created_at`) VALUES
(7, 'Welfare Gift - Vihanga', '', 'Gifts & Welfare', 2000.00, '2026-11-15', NULL, 'approved', 1, 2, '2026-11-15 12:47:21', NULL, NULL, '2026-11-15 07:15:45'),
(9, 'Faculty T-Shirt Sample', '', 'Equipment', 1500.00, '2026-11-15', NULL, 'approved', 1, 2, '2026-11-15 16:31:27', 1, '2026-11-15 16:31:15', '2026-11-15 07:24:50'),
(10, 'Ceremony Decoration', '', 'Event', 2000.00, '2026-11-15', NULL, 'approved', 1, 2, '2026-11-15 16:30:06', 1, '2026-11-15 16:29:35', '2026-11-15 07:37:41'),
(11, 'Food Stall', '', 'Food & Refreshments', 3000.00, '2026-11-11', NULL, 'approved', 1, 2, '2026-11-15 16:28:30', NULL, NULL, '2026-11-15 10:58:15'),
(12, 'Batch 26 Key Tags', '', 'Printing & Stationery', 1000.00, '2026-11-15', NULL, 'approved', 1, 2, '2026-11-15 22:48:18', NULL, NULL, '2026-11-15 17:17:13');

-- --------------------------------------------------------

--
-- Table structure for table `expense_documents`
--

CREATE TABLE `expense_documents` (
  `document_id` int(10) UNSIGNED NOT NULL,
  `expense_id` int(10) UNSIGNED NOT NULL,
  `stored_name` varchar(80) NOT NULL,
  `original_name` varchar(200) NOT NULL,
  `mime_type` varchar(80) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(30) NOT NULL,
  `message` varchar(255) NOT NULL,
  `ref_key` varchar(60) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `message`, `ref_key`, `is_read`, `created_at`) VALUES
(231, 2, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 1, '2026-11-15 06:37:16'),
(232, 3, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:37:16'),
(233, 4, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:37:16'),
(234, 5, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:37:16'),
(235, 6, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 1, '2026-11-15 06:37:16'),
(236, 7, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:37:16'),
(237, 8, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:37:16'),
(238, 9, 'new_collection', 'New monthly collection: October Collection – Rs. 100.00, due 31 Oct 2026.', NULL, 1, '2026-11-15 06:37:16'),
(246, 2, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 1, '2026-11-15 06:37:48'),
(247, 3, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 06:37:48'),
(248, 4, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 06:37:48'),
(249, 5, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 06:37:48'),
(250, 6, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 1, '2026-11-15 06:37:48'),
(251, 7, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 06:37:48'),
(252, 8, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 06:37:48'),
(253, 9, 'new_collection', 'New monthly collection: November Collection – Rs. 100.00, due 30 Nov 2026.', NULL, 1, '2026-11-15 06:37:48'),
(261, 3, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 0, '2026-11-15 06:37:54'),
(262, 2, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 1, '2026-11-15 06:41:14'),
(263, 3, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:41:14'),
(264, 4, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:41:14'),
(265, 5, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:41:14'),
(266, 6, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 1, '2026-11-15 06:41:14'),
(267, 7, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:41:14'),
(268, 8, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 0, '2026-11-15 06:41:14'),
(269, 9, 'new_event', 'New special-event collection: Batch Trip - Hantana – Rs. 3,600.00, due 31 Oct 2026.', NULL, 1, '2026-11-15 06:41:14'),
(277, 3, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 0, '2026-11-15 06:41:18'),
(283, 1, 'payment_submitted', 'Nipul Sanketh submitted a payment for \"Batch Trip - Hantana\".', NULL, 1, '2026-11-15 07:00:33'),
(284, 2, 'payment_submitted', 'Nipul Sanketh submitted a payment for \"Batch Trip - Hantana\".', NULL, 1, '2026-11-15 07:00:33'),
(287, 1, 'payment_submitted', 'Nipul Sanketh submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:00:48'),
(288, 2, 'payment_submitted', 'Nipul Sanketh submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:00:48'),
(290, 1, 'payment_submitted', 'Nipul Sanketh submitted a payment for \"November Collection\".', NULL, 1, '2026-11-15 07:01:18'),
(291, 2, 'payment_submitted', 'Nipul Sanketh submitted a payment for \"November Collection\".', NULL, 1, '2026-11-15 07:01:18'),
(293, 2, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 1, '2026-11-15 07:02:07'),
(294, 2, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 1, '2026-11-15 07:02:07'),
(295, 1, 'payment_submitted', 'Thilina Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:02:20'),
(296, 3, 'payment_submitted', 'Thilina Perera submitted a payment for \"October Collection\".', NULL, 0, '2026-11-15 07:02:20'),
(299, 1, 'payment_submitted', 'Thilina Perera submitted a payment for \"November Collection\".', NULL, 1, '2026-11-15 07:02:31'),
(300, 3, 'payment_submitted', 'Thilina Perera submitted a payment for \"November Collection\".', NULL, 0, '2026-11-15 07:02:31'),
(303, 4, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 0, '2026-11-15 07:04:31'),
(304, 4, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 0, '2026-11-15 07:04:31'),
(305, 1, 'payment_submitted', 'Lahiru Fernando submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:05:20'),
(306, 2, 'payment_submitted', 'Lahiru Fernando submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:05:20'),
(307, 3, 'payment_submitted', 'Lahiru Fernando submitted a payment for \"October Collection\".', NULL, 0, '2026-11-15 07:05:20'),
(309, 5, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 0, '2026-11-15 07:06:31'),
(310, 5, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 0, '2026-11-15 07:06:31'),
(311, 1, 'payment_submitted', 'Saman Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:06:45'),
(312, 2, 'payment_submitted', 'Saman Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:06:45'),
(313, 3, 'payment_submitted', 'Saman Perera submitted a payment for \"October Collection\".', NULL, 0, '2026-11-15 07:06:45'),
(315, 6, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 1, '2026-11-15 07:07:30'),
(316, 6, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 1, '2026-11-15 07:07:30'),
(317, 1, 'payment_submitted', 'Kamal Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:07:44'),
(318, 2, 'payment_submitted', 'Kamal Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:07:44'),
(319, 3, 'payment_submitted', 'Kamal Perera submitted a payment for \"October Collection\".', NULL, 0, '2026-11-15 07:07:44'),
(322, 7, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 0, '2026-11-15 07:10:14'),
(323, 7, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 0, '2026-11-15 07:10:14'),
(324, 1, 'payment_submitted', 'Nuwan Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:10:55'),
(325, 2, 'payment_submitted', 'Nuwan Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:10:55'),
(326, 3, 'payment_submitted', 'Nuwan Perera submitted a payment for \"October Collection\".', NULL, 0, '2026-11-15 07:10:55'),
(328, 8, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 0, '2026-11-15 07:11:08'),
(329, 8, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 0, '2026-11-15 07:11:08'),
(330, 1, 'payment_submitted', 'Heshan Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:11:24'),
(331, 2, 'payment_submitted', 'Heshan Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:11:24'),
(332, 3, 'payment_submitted', 'Heshan Perera submitted a payment for \"October Collection\".', NULL, 0, '2026-11-15 07:11:24'),
(334, 9, 'overdue', 'Overdue: \"Batch Trip - Hantana\" was due on 31 Oct 2026.', 'overdue-21', 1, '2026-11-15 07:11:58'),
(335, 9, 'overdue', 'Overdue: \"October Collection\" was due on 31 Oct 2026.', 'overdue-19', 1, '2026-11-15 07:11:58'),
(336, 1, 'payment_submitted', 'Anjana Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:12:08'),
(337, 2, 'payment_submitted', 'Anjana Perera submitted a payment for \"October Collection\".', NULL, 1, '2026-11-15 07:12:08'),
(338, 3, 'payment_submitted', 'Anjana Perera submitted a payment for \"October Collection\".', NULL, 0, '2026-11-15 07:12:08'),
(340, 9, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 1, '2026-11-15 07:12:32'),
(341, 3, 'payment_approved', 'Your payment for \"Batch Trip - Hantana\" (Rs. 3,600.00) was approved.', NULL, 0, '2026-11-15 07:12:35'),
(342, 3, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 0, '2026-11-15 07:12:37'),
(343, 3, 'payment_approved', 'Your payment for \"November Collection\" (Rs. 100.00) was approved.', NULL, 0, '2026-11-15 07:12:40'),
(344, 2, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 1, '2026-11-15 07:12:42'),
(345, 4, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 0, '2026-11-15 07:12:44'),
(346, 2, 'payment_approved', 'Your payment for \"November Collection\" (Rs. 100.00) was approved.', NULL, 1, '2026-11-15 07:12:46'),
(347, 5, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 0, '2026-11-15 07:12:48'),
(348, 6, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 0, '2026-11-15 07:12:50'),
(349, 7, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 0, '2026-11-15 07:12:52'),
(350, 8, 'payment_approved', 'Your payment for \"October Collection\" (Rs. 100.00) was approved.', NULL, 0, '2026-11-15 07:12:54'),
(353, 1, 'payment_submitted', 'Nuwan Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 1, '2026-11-15 07:13:43'),
(354, 2, 'payment_submitted', 'Nuwan Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 1, '2026-11-15 07:13:43'),
(355, 3, 'payment_submitted', 'Nuwan Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 0, '2026-11-15 07:13:43'),
(356, 7, 'payment_approved', 'Your payment for \"Batch Trip - Hantana\" (Rs. 3,600.00) was approved.', NULL, 0, '2026-11-15 07:14:34'),
(357, 2, 'expense_pending', 'Batch Coordinator added an expense for approval: Welfare Gift - Vihanga – Rs. 2,000.00.', NULL, 1, '2026-11-15 07:15:45'),
(358, 2, 'expense_pending', 'Batch Coordinator added an expense for approval: Faculty T-Shirt Sample – Rs. 4,000.00.', NULL, 1, '2026-11-15 07:24:50'),
(359, 2, 'expense_pending', 'Batch Coordinator added an expense for approval: Ceremony Decoration – Rs. 5,000.00.', NULL, 1, '2026-11-15 07:37:41'),
(361, 1, 'payment_submitted', 'Heshan Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 1, '2026-11-15 07:38:59'),
(362, 2, 'payment_submitted', 'Heshan Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 1, '2026-11-15 07:38:59'),
(363, 3, 'payment_submitted', 'Heshan Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 0, '2026-11-15 07:38:59'),
(366, 8, 'payment_approved', 'Your payment for \"Batch Trip - Hantana\" (Rs. 3,600.00) was approved.', NULL, 0, '2026-11-15 07:40:03'),
(369, 2, 'expense_pending', 'Batch Coordinator added an expense for approval: Food Stall – Rs. 3,000.00.', NULL, 1, '2026-11-15 10:58:15'),
(379, 1, 'payment_submitted', 'Anjana Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 0, '2026-11-15 17:09:53'),
(380, 2, 'payment_submitted', 'Anjana Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 0, '2026-11-15 17:09:53'),
(381, 3, 'payment_submitted', 'Anjana Perera submitted a payment for \"Batch Trip - Hantana\".', NULL, 0, '2026-11-15 17:09:53'),
(382, 2, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(383, 3, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(384, 4, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(385, 5, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(386, 6, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(387, 7, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(388, 8, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(389, 9, 'new_event', 'New special-event collection: Faculty T-Shirt – Rs. 2,200.00, due 30 Nov 2026.', NULL, 0, '2026-11-15 17:14:40'),
(397, 9, 'payment_approved', 'Your payment for \"Batch Trip - Hantana\" (Rs. 3,600.00) was approved.', NULL, 0, '2026-11-15 17:15:27'),
(398, 2, 'expense_pending', 'Batch Coordinator added an expense for approval: Batch 26 Key Tags – Rs. 1,000.00.', NULL, 0, '2026-11-15 17:17:13');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `collection_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_no` varchar(60) NOT NULL,
  `status` enum('receipt_submitted','under_verification','approved','rejected') NOT NULL DEFAULT 'receipt_submitted',
  `attempt_no` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `rejection_reason` varchar(250) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `student_id`, `collection_id`, `amount`, `reference_no`, `status`, `attempt_no`, `rejection_reason`, `submitted_at`, `reviewed_by`, `reviewed_at`) VALUES
(25, 2, 21, 3600.00, '1123123', 'approved', 1, NULL, '2026-11-15 12:30:33', 1, '2026-11-15 12:42:35'),
(26, 2, 19, 100.00, '12312321', 'approved', 1, NULL, '2026-11-15 12:30:48', 1, '2026-11-15 12:42:37'),
(27, 2, 20, 100.00, '12322', 'approved', 1, NULL, '2026-11-15 12:31:18', 1, '2026-11-15 12:42:40'),
(28, 1, 19, 100.00, '121241234', 'approved', 1, NULL, '2026-11-15 12:32:20', 1, '2026-11-15 12:42:42'),
(29, 1, 20, 100.00, '1234123', 'approved', 1, NULL, '2026-11-15 12:32:31', 1, '2026-11-15 12:42:46'),
(30, 3, 19, 100.00, '23123123', 'approved', 1, NULL, '2026-11-15 12:35:20', 1, '2026-11-15 12:42:44'),
(31, 4, 19, 100.00, '123123', 'approved', 1, NULL, '2026-11-15 12:36:45', 1, '2026-11-15 12:42:48'),
(32, 5, 19, 100.00, '12345', 'approved', 1, NULL, '2026-11-15 12:37:44', 1, '2026-11-15 12:42:50'),
(33, 6, 19, 100.00, '1234', 'approved', 1, NULL, '2026-11-15 12:40:55', 1, '2026-11-15 12:42:52'),
(34, 7, 19, 100.00, '1231222', 'approved', 1, NULL, '2026-11-15 12:41:24', 1, '2026-11-15 12:42:54'),
(35, 8, 19, 100.00, '21231234123', 'approved', 1, NULL, '2026-11-15 12:42:08', 1, '2026-11-15 12:42:32'),
(36, 6, 21, 3600.00, '124888', 'approved', 1, NULL, '2026-11-15 12:43:43', 1, '2026-11-15 12:44:34'),
(37, 7, 21, 3600.00, '1231222333', 'approved', 1, NULL, '2026-11-15 13:08:59', 1, '2026-11-15 13:10:03'),
(38, 8, 21, 3600.00, '12312313123', 'approved', 1, NULL, '2026-11-15 22:39:53', 1, '2026-11-15 22:45:27');

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `receipt_id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED NOT NULL,
  `attempt_no` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `stored_name` varchar(80) NOT NULL,
  `original_name` varchar(200) NOT NULL,
  `mime_type` varchar(80) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payment_receipts`
--

INSERT INTO `payment_receipts` (`receipt_id`, `payment_id`, `attempt_no`, `stored_name`, `original_name`, `mime_type`, `file_size`, `uploaded_at`) VALUES
(25, 25, 1, 'fe8e8560835fc6268452ae0778f154d8.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:00:33'),
(26, 26, 1, '95c85caa58b7cc831d7aeb8e9320a3f4.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:00:48'),
(27, 27, 1, 'c600442ae6c3b21e5db3afd160e8108d.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:01:18'),
(28, 28, 1, '83d368eb47e3c9c2f7ce5a5c6cb88ab0.png', 'Screenshot 2026-11-15 at 12.24.22.png', 'image/png', 280342, '2026-11-15 07:02:20'),
(29, 29, 1, 'a0ce3cae811dd4c2b06399ba3021064b.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:02:31'),
(30, 30, 1, '75da2874ca4830ce0536850d6de9abd6.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:05:20'),
(31, 31, 1, '182598ddf7e7418533ce236ecbcd58ea.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:06:45'),
(32, 32, 1, '4b2ea889eb3aa16a8d3e45c6667748e8.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:07:44'),
(33, 33, 1, '5176b296155b8f808eebe9e349c0f125.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:10:55'),
(34, 34, 1, '0f23ab401372b3fb3f69421668324892.png', 'Screenshot 2026-11-15 at 12.24.22.png', 'image/png', 280342, '2026-11-15 07:11:24'),
(35, 35, 1, '9ca4c63db9365fa04e4769519cc42f3f.png', 'Screenshot 2026-11-15 at 12.21.51.png', 'image/png', 133018, '2026-11-15 07:12:08'),
(36, 36, 1, 'dc1cf48d1854d09d437f740dbd839feb.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:13:43'),
(37, 37, 1, '2bbee82579477303b41a04483b5b03fe.png', 'Screenshot 2026-11-15 at 12.24.55.png', 'image/png', 1701721, '2026-11-15 07:38:59'),
(38, 38, 1, '0abc40c2ad976444628cfa4d3efa54b7.png', 'Screenshot 2026-11-15 at 12.24.22.png', 'image/png', 280342, '2026-11-15 17:09:53');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reg_no` varchar(30) NOT NULL,
  `contact_no` varchar(20) NOT NULL,
  `district` varchar(60) NOT NULL,
  `degree` varchar(60) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `reg_no`, `contact_no`, `district`, `degree`, `status`, `created_at`) VALUES
(1, 2, '254155J', '0771110001', 'Colombo', 'IT', 'active', '2026-11-13 08:48:54'),
(2, 3, '254095A', '0771110002', 'Colombo', 'IT', 'active', '2026-11-13 08:48:54'),
(3, 4, '254100B', '0771110003', 'Gampaha', 'ITM', 'active', '2026-11-13 08:48:54'),
(4, 5, '254110C', '0771110004', 'Colombo', 'IT', 'active', '2026-11-13 08:48:54'),
(5, 6, '254120D', '0771110005', 'Kandy', 'ITM', 'active', '2026-11-13 08:48:54'),
(6, 7, '254130E', '0771110006', 'Colombo', 'AI', 'active', '2026-11-13 08:48:54'),
(7, 8, '254140F', '0771110007', 'Galle', 'IT', 'active', '2026-11-13 08:48:54'),
(8, 9, '254150G', '0771110008', 'Colombo', 'AI', 'active', '2026-11-13 08:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('coordinator','student') NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_treasurer` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `email`, `password`, `full_name`, `is_active`, `is_treasurer`, `last_login_at`, `created_at`) VALUES
(1, 'coordinator', 'coordinator@uom.lk', 'Coord@123', 'Batch Coordinator', 1, 0, '2026-11-15 22:40:29', '2026-11-13 08:48:54'),
(2, 'coordinator', '254155j@uom.lk', 'Thi26@12lina', 'Thilina Perera', 1, 1, '2026-11-15 22:48:09', '2026-11-13 08:48:54'),
(3, 'coordinator', '254095a@uom.lk', 'Nipul@123', 'Nipul Sanketh', 1, 0, '2026-11-15 12:36:11', '2026-11-13 08:48:54'),
(4, 'student', '254100b@uom.lk', 'Student@123', 'Lahiru Fernando', 1, 0, '2026-11-15 12:34:31', '2026-11-13 08:48:54'),
(5, 'student', '254110c@uom.lk', 'Stud@123', 'Saman Perera', 1, 0, '2026-11-15 12:36:31', '2026-11-13 08:48:54'),
(6, 'student', '254120d@uom.lk', 'Stud@123', 'Kamal Perera', 1, 0, '2026-11-15 12:37:30', '2026-11-13 08:48:54'),
(7, 'student', '254130e@uom.lk', 'Stud@123', 'Nuwan Perera', 1, 0, '2026-11-15 12:43:13', '2026-11-13 08:48:54'),
(8, 'student', '254140f@uom.lk', 'Stud@123', 'Heshan Perera', 1, 0, '2026-11-15 13:08:44', '2026-11-13 08:48:54'),
(9, 'student', '254150g@uom.lk', 'Stud@123', 'Anjana Perera', 1, 0, '2026-11-15 22:38:50', '2026-11-13 08:48:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `idx_announcements_status` (`status`,`publish_date`),
  ADD KEY `fk_announcements_creator` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_audit_created` (`created_at`),
  ADD KEY `idx_audit_entity` (`entity`,`entity_id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`collection_id`),
  ADD KEY `idx_collections_kind` (`kind`,`status`),
  ADD KEY `idx_collections_deadline` (`deadline`),
  ADD KEY `fk_collections_creator` (`created_by`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_email_logs_sent` (`sent_at`),
  ADD KEY `fk_email_student` (`student_id`),
  ADD KEY `fk_email_collection` (`collection_id`),
  ADD KEY `fk_email_sender` (`sent_by`);

--
-- Indexes for table `event_images`
--
ALTER TABLE `event_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_event_images_collection` (`collection_id`),
  ADD KEY `fk_event_images_user` (`uploaded_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `idx_expenses_status` (`status`),
  ADD KEY `idx_expenses_date` (`expense_date`),
  ADD KEY `fk_expenses_collection` (`collection_id`),
  ADD KEY `fk_expenses_creator` (`created_by`),
  ADD KEY `fk_expenses_approver` (`approved_by`);

--
-- Indexes for table `expense_documents`
--
ALTER TABLE `expense_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `idx_expdocs_expense` (`expense_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD UNIQUE KEY `uq_notification_ref` (`user_id`,`ref_key`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_payment_student_collection` (`student_id`,`collection_id`),
  ADD UNIQUE KEY `uq_payment_reference` (`reference_no`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_collection` (`collection_id`),
  ADD KEY `fk_payments_reviewer` (`reviewed_by`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `idx_receipts_payment` (`payment_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uq_students_user` (`user_id`),
  ADD UNIQUE KEY `uq_students_reg` (`reg_no`),
  ADD KEY `idx_students_district` (`district`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `collection_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `event_images`
--
ALTER TABLE `event_images`
  MODIFY `image_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `expense_documents`
--
ALTER TABLE `expense_documents`
  MODIFY `document_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=400;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `receipt_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `collections`
--
ALTER TABLE `collections`
  ADD CONSTRAINT `fk_collections_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `fk_email_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_email_sender` FOREIGN KEY (`sent_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_email_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_images`
--
ALTER TABLE `event_images`
  ADD CONSTRAINT `fk_event_images_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_images_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expenses_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expenses_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `expense_documents`
--
ALTER TABLE `expense_documents`
  ADD CONSTRAINT `fk_expdocs_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`),
  ADD CONSTRAINT `fk_payments_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD CONSTRAINT `fk_receipts_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
