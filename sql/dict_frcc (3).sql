-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2026 at 02:42 AM
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
-- Database: `dict_frcc`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_record`
--

CREATE TABLE `attendance_record` (
  `attendance_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `with_id` enum('Yes','No') DEFAULT 'No',
  `is_asean` enum('Yes','No') DEFAULT 'No',
  `status` enum('On Time','Late') DEFAULT 'On Time',
  `photo_path` varchar(255) DEFAULT NULL,
  `is_compliant` tinyint(1) DEFAULT 0,
  `time_recorded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_record`
--

INSERT INTO `attendance_record` (`attendance_id`, `emp_id`, `designation`, `with_id`, `is_asean`, `status`, `photo_path`, `is_compliant`, `time_recorded`) VALUES
(1, 24, 'Project Development', 'Yes', 'No', 'On Time', 'uploads/attendance/24_1.jpg', 1, '2026-03-29 23:30:00'),
(3, 43, 'AO II', 'Yes', 'Yes', 'On Time', '', 1, '2026-03-29 23:25:00'),
(4, 12, 'AO II', 'No', 'Yes', 'On Time', 'uploads/attendance/12_4.jpg', 1, '2026-03-30 00:23:00'),
(5, 104, 'AO II', 'No', 'Yes', 'On Time', 'uploads/attendance/104_5.jpg', 1, '2026-03-30 01:43:00'),
(6, 87, 'AO II', 'No', 'No', 'Late', '', 0, '2026-03-29 22:23:00'),
(7, 116, 'AO III', 'Yes', 'No', 'On Time', '', 1, '2026-03-30 02:09:00'),
(8, 117, 'AO II', 'No', 'No', 'On Time', '', 1, '2026-03-29 22:40:00'),
(9, 33, 'AO III', 'No', 'No', 'Late', 'uploads/attendance/33_9.jpg', 0, '2026-03-29 23:04:00'),
(10, 45, 'Planning Assistant', 'No', 'No', 'On Time', '', 1, '2026-03-29 22:53:00'),
(11, 112, 'AO II', 'No', 'No', 'On Time', 'uploads/attendance/112_11.jpg', 1, '2026-03-29 22:30:00'),
(13, 44, 'Planning Assistant', 'Yes', 'No', 'On Time', 'uploads/attendance/44_13.jpg', 1, '2026-03-30 02:39:00'),
(16, 54, 'ITO II', 'No', 'No', 'Late', 'uploads/attendance/54_16.jpg', 0, '2026-03-29 22:00:00'),
(18, 80, 'AO III', 'Yes', 'No', 'Late', 'uploads/attendance/80_18.jpg', 0, '2026-03-30 01:02:00'),
(19, 69, 'Project Development', 'No', 'Yes', 'On Time', '', 1, '2026-03-30 02:57:00'),
(20, 49, 'ITO II', 'Yes', 'No', 'On Time', 'uploads/attendance/49_20.jpg', 1, '2026-03-30 00:21:00'),
(21, 48, 'AO II', 'Yes', 'No', 'On Time', '', 1, '2026-03-30 02:45:00'),
(23, 96, 'Planning Assistant', 'No', 'Yes', 'Late', 'uploads/attendance/96_23.jpg', 0, '2026-03-30 01:04:00'),
(24, 31, 'ITO I', 'No', 'No', 'On Time', 'uploads/attendance/31_24.jpg', 1, '2026-03-30 00:44:00'),
(25, 86, 'Planning Assistant', 'Yes', 'Yes', 'Late', 'uploads/attendance/86_25.jpg', 0, '2026-03-29 22:23:00'),
(26, 23, 'Planning Assistant', 'Yes', 'Yes', 'On Time', 'uploads/attendance/23_26.jpg', 1, '2026-03-30 02:27:00'),
(27, 107, 'AO III', 'No', 'No', 'Late', 'uploads/attendance/107_27.jpg', 0, '2026-03-30 02:40:00'),
(29, 119, 'ITO II', 'No', 'Yes', 'On Time', 'uploads/attendance/119_29.jpg', 1, '2026-03-30 02:04:00'),
(30, 105, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/105_30.jpg', 0, '2026-03-30 00:59:00'),
(31, 104, 'Project Development', 'No', 'Yes', 'On Time', 'uploads/attendance/104_31.jpg', 1, '2026-03-29 23:53:00'),
(32, 98, 'AO III', 'No', 'Yes', 'On Time', 'uploads/attendance/98_32.jpg', 1, '2026-03-30 02:35:00'),
(33, 23, 'Project Development', 'No', 'Yes', 'Late', 'uploads/attendance/23_33.jpg', 0, '2026-03-30 02:37:00'),
(34, 106, 'Planning Assistant', 'Yes', 'Yes', 'On Time', '', 1, '2026-03-30 02:11:00'),
(37, 77, 'AO III', 'Yes', 'Yes', 'Late', '', 0, '2026-03-30 00:56:00'),
(38, 89, 'ITO I', 'Yes', 'Yes', 'On Time', '', 1, '2026-03-30 00:38:00'),
(40, 102, 'ITO II', 'No', 'Yes', 'Late', 'uploads/attendance/102_40.jpg', 0, '2026-03-30 01:14:00'),
(41, 120, 'ITO II', 'Yes', 'No', 'On Time', '', 1, '2026-03-29 22:08:00'),
(42, 59, 'Planning Assistant', 'Yes', 'No', 'Late', 'uploads/attendance/59_42.jpg', 0, '2026-03-30 00:55:00'),
(43, 38, 'Planning Assistant', 'No', 'No', 'On Time', '', 1, '2026-03-30 02:54:00'),
(44, 25, 'ITO II', 'Yes', 'Yes', 'On Time', 'uploads/attendance/25_44.jpg', 1, '2026-03-29 22:00:00'),
(45, 31, 'Project Development', 'No', 'No', 'On Time', 'uploads/attendance/31_45.jpg', 1, '2026-03-30 01:13:00'),
(46, 72, 'ITO II', 'No', 'Yes', 'On Time', '', 1, '2026-03-30 00:33:00'),
(47, 38, 'AO III', 'Yes', 'No', 'Late', '', 0, '2026-03-30 01:17:00'),
(48, 18, 'Planning Assistant', 'No', 'No', 'Late', 'uploads/attendance/18_48.jpg', 0, '2026-03-30 02:44:00'),
(49, 104, 'ITO II', 'No', 'Yes', 'Late', 'uploads/attendance/104_49.jpg', 0, '2026-03-30 01:06:00'),
(50, 73, 'AO II', 'No', 'Yes', 'Late', 'uploads/attendance/73_50.jpg', 0, '2026-03-29 23:21:00'),
(51, 18, 'Project Development', 'No', 'No', 'Late', 'uploads/attendance/18_51.jpg', 0, '2026-03-30 00:59:00'),
(52, 54, 'Project Development', 'No', 'Yes', 'Late', 'uploads/attendance/54_52.jpg', 0, '2026-03-30 01:47:00'),
(53, 35, 'Project Development', 'No', 'No', 'On Time', '', 1, '2026-03-29 22:54:00'),
(54, 96, 'AO II', 'No', 'Yes', 'Late', 'uploads/attendance/96_54.jpg', 0, '2026-03-29 22:13:00'),
(55, 50, 'Project Development', 'Yes', 'No', 'Late', 'uploads/attendance/50_55.jpg', 0, '2026-03-30 01:23:00'),
(56, 35, 'AO II', 'No', 'No', 'On Time', 'uploads/attendance/35_56.jpg', 1, '2026-03-30 02:15:00'),
(58, 102, 'AO II', 'Yes', 'No', 'Late', 'uploads/attendance/102_58.jpg', 0, '2026-03-29 23:34:00'),
(59, 119, 'ITO I', 'Yes', 'No', 'Late', 'uploads/attendance/119_59.jpg', 0, '2026-03-30 01:16:00'),
(60, 86, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/86_60.jpg', 1, '2026-03-30 02:58:00'),
(61, 60, 'Project Development', 'No', 'Yes', 'On Time', '', 1, '2026-03-29 23:39:00'),
(62, 35, 'ITO I', 'Yes', 'Yes', 'On Time', '', 1, '2026-03-29 22:57:00'),
(63, 20, 'AO II', 'Yes', 'Yes', 'Late', 'uploads/attendance/20_63.jpg', 0, '2026-03-30 02:30:00'),
(65, 67, 'ITO I', 'Yes', 'Yes', 'On Time', 'uploads/attendance/67_65.jpg', 1, '2026-03-29 22:16:00'),
(66, 51, 'AO III', 'Yes', 'No', 'On Time', 'uploads/attendance/51_66.jpg', 1, '2026-03-29 22:23:00'),
(67, 116, 'AO II', 'Yes', 'No', 'Late', '', 0, '2026-03-30 03:00:00'),
(68, 76, 'AO III', 'Yes', 'No', 'Late', '', 0, '2026-03-29 23:30:00'),
(69, 36, 'AO II', 'Yes', 'Yes', 'Late', 'uploads/attendance/36_69.jpg', 0, '2026-03-30 02:42:00'),
(70, 90, 'AO II', 'Yes', 'No', 'On Time', '', 1, '2026-03-29 22:30:00'),
(71, 21, 'AO II', 'No', 'No', 'Late', 'uploads/attendance/21_71.jpg', 0, '2026-03-30 02:18:00'),
(72, 70, 'ITO II', 'No', 'Yes', 'On Time', 'uploads/attendance/70_72.jpg', 1, '2026-03-30 00:20:00'),
(73, 117, 'AO II', 'Yes', 'No', 'On Time', 'uploads/attendance/117_73.jpg', 1, '2026-03-30 02:50:00'),
(74, 112, 'Project Development', 'No', 'Yes', 'On Time', '', 1, '2026-03-29 23:34:00'),
(75, 100, 'AO III', 'Yes', 'No', 'On Time', 'uploads/attendance/100_75.jpg', 1, '2026-03-30 02:13:00'),
(76, 85, 'Project Development', 'Yes', 'No', 'Late', 'uploads/attendance/85_76.jpg', 0, '2026-03-29 22:52:00'),
(78, 117, 'Project Development', 'No', 'No', 'Late', 'uploads/attendance/117_78.jpg', 0, '2026-03-30 01:13:00'),
(79, 20, 'Project Development', 'No', 'No', 'On Time', 'uploads/attendance/20_79.jpg', 1, '2026-03-30 01:39:00'),
(80, 104, 'Project Development', 'Yes', 'Yes', 'Late', '', 0, '2026-03-30 01:05:00'),
(81, 117, NULL, 'Yes', 'Yes', 'On Time', NULL, 1, '2026-03-29 22:56:22'),
(82, 117, 'ITO1', 'Yes', 'Yes', 'On Time', 'uploads/attendance/117/117_2026-03-30_06-56-22.jpeg', 0, '2026-03-29 22:56:22'),
(83, 114, NULL, 'Yes', 'Yes', 'Late', NULL, 0, '2026-03-30 02:02:06'),
(84, 114, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/114/114_2026-03-30_10-02-06.jpeg', 0, '2026-03-30 02:02:06'),
(85, 117, 'Employee', 'Yes', 'Yes', 'Late', NULL, 0, '2026-03-30 02:09:32'),
(86, 117, 'ITO I', 'Yes', 'Yes', 'Late', 'uploads/attendance/117/117_2026-03-30_10-09-32.jpeg', 0, '2026-03-30 02:09:32'),
(87, 114, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/114/114_2026-03-30_10-25-57.jpeg', 0, '2026-03-30 02:25:57'),
(88, 112, 'Planning Assistant', 'Yes', 'Yes', 'On Time', 'uploads/attendance/112/112_2026-03-30_07-28-38.jpeg', 1, '2026-03-29 23:28:38'),
(89, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-03-30_14-50-05.jpeg', 0, '2026-03-30 06:50:05'),
(90, 114, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/114/114_2026-03-30_07-43-08.jpeg', 1, '2026-03-29 23:43:08'),
(91, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-04-06_13-07-17.jpeg', 0, '2026-04-06 05:07:17'),
(92, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-04-06_13-10-15.jpeg', 0, '2026-04-06 05:10:15'),
(93, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-04-06_13-16-56.jpeg', 0, '2026-04-06 05:16:56'),
(94, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-04-06_13-20-21.jpeg', 0, '2026-04-06 05:20:21'),
(95, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-04-06_13-21-07.jpeg', 0, '2026-04-06 05:21:07'),
(96, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-04-06_13-58-09.jpeg', 0, '2026-04-06 05:58:09'),
(97, 114, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/114/114_2026-04-06_20-29-08.jpeg', 0, '2026-04-06 12:29:08'),
(98, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-06_07-30-11.jpeg', 1, '2026-04-05 23:30:11'),
(99, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-06_07-31-44.jpeg', 1, '2026-04-05 23:31:44'),
(100, 114, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/114/114_2026-04-06_14-49-00.jpeg', 0, '2026-04-06 06:49:00'),
(101, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-06_07-45-53.jpeg', 1, '2026-04-05 23:45:53'),
(102, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-06_07-46-00.jpeg', 1, '2026-04-05 23:46:00'),
(103, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-13_09-22-11.jpeg', 1, '2026-04-13 01:22:11'),
(104, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-13_07-23-00.jpeg', 1, '2026-04-12 23:23:00'),
(105, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-13_07-23-32.jpeg', 1, '2026-04-12 23:23:32'),
(106, 114, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/114/114_2026-04-13_10-56-20.jpeg', 1, '2026-04-13 02:56:20'),
(107, 23, 'ITO II', 'No', 'No', 'On Time', '', 1, '2026-03-30 01:05:00'),
(109, 24, 'AO II', 'Yes', 'No', 'On Time', 'uploads/attendance/24_109.jpg', 1, '2026-03-30 00:51:00'),
(110, 70, 'AO II', 'No', 'Yes', 'On Time', 'uploads/attendance/70_110.jpg', 1, '2026-03-29 22:13:00'),
(111, 33, 'Planning Assistant', 'Yes', 'Yes', 'On Time', 'uploads/attendance/33_111.jpg', 1, '2026-03-30 00:25:00'),
(112, 72, 'AO III', 'No', 'Yes', 'Late', 'uploads/attendance/72_112.jpg', 0, '2026-03-30 01:58:00'),
(113, 62, 'ITO I', 'No', 'No', 'Late', '', 0, '2026-03-29 23:14:00'),
(114, 40, 'AO III', 'No', 'Yes', 'Late', '', 0, '2026-03-30 01:29:00'),
(116, 42, 'AO III', 'No', 'No', 'Late', 'uploads/attendance/42_116.jpg', 0, '2026-03-29 23:01:00'),
(117, 22, 'ITO I', 'Yes', 'Yes', 'Late', 'uploads/attendance/22_117.jpg', 0, '2026-03-29 22:06:00'),
(119, 105, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/105_119.jpg', 0, '2026-03-29 22:32:00'),
(120, 65, 'Project Development', 'Yes', 'No', 'On Time', '', 1, '2026-03-29 22:35:00'),
(123, 76, 'ITO I', 'No', 'Yes', 'On Time', 'uploads/attendance/76_123.jpg', 1, '2026-03-30 02:21:00'),
(124, 64, 'AO II', 'Yes', 'No', 'Late', 'uploads/attendance/64_124.jpg', 0, '2026-03-30 00:30:00'),
(125, 96, 'AO III', 'Yes', 'No', 'On Time', 'uploads/attendance/96_125.jpg', 1, '2026-03-29 23:34:00'),
(126, 57, 'AO III', 'Yes', 'No', 'Late', 'uploads/attendance/57_126.jpg', 0, '2026-03-30 02:05:00'),
(127, 62, 'ITO II', 'No', 'Yes', 'Late', '', 0, '2026-03-30 00:53:00'),
(128, 35, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/35_128.jpg', 1, '2026-03-30 00:28:00'),
(129, 51, 'AO III', 'Yes', 'No', 'Late', 'uploads/attendance/51_129.jpg', 0, '2026-03-30 00:11:00'),
(130, 31, 'ITO II', 'No', 'No', 'Late', 'uploads/attendance/31_130.jpg', 0, '2026-03-29 22:11:00'),
(131, 42, 'Project Development', 'No', 'No', 'On Time', 'uploads/attendance/42_131.jpg', 1, '2026-03-30 00:44:00'),
(132, 100, 'AO III', 'No', 'No', 'On Time', '', 1, '2026-03-29 23:46:00'),
(133, 21, 'ITO II', 'Yes', 'No', 'Late', 'uploads/attendance/21_133.jpg', 0, '2026-03-30 00:27:00'),
(134, 21, 'AO II', 'No', 'Yes', 'On Time', 'uploads/attendance/21_134.jpg', 1, '2026-03-30 00:13:00'),
(135, 23, 'Project Development', 'Yes', 'Yes', 'On Time', '', 1, '2026-03-30 02:05:00'),
(136, 89, 'Project Development', 'Yes', 'No', 'Late', 'uploads/attendance/89_136.jpg', 0, '2026-03-30 00:41:00'),
(137, 98, 'ITO II', 'No', 'No', 'Late', '', 0, '2026-03-29 22:41:00'),
(139, 57, 'Project Development', 'No', 'Yes', 'Late', 'uploads/attendance/57_139.jpg', 0, '2026-03-29 23:41:00'),
(140, 91, 'Project Development', 'Yes', 'No', 'Late', 'uploads/attendance/91_140.jpg', 0, '2026-03-29 23:13:00'),
(141, 12, 'AO II', 'No', 'Yes', 'On Time', 'uploads/attendance/12_141.jpg', 1, '2026-03-30 01:57:00'),
(142, 52, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/52_142.jpg', 1, '2026-03-29 23:29:00'),
(143, 18, 'ITO I', 'No', 'Yes', 'On Time', 'uploads/attendance/18_143.jpg', 1, '2026-03-29 23:17:00'),
(144, 56, 'AO III', 'No', 'No', 'Late', 'uploads/attendance/56_144.jpg', 0, '2026-03-30 02:13:00'),
(145, 31, 'AO II', 'Yes', 'No', 'Late', 'uploads/attendance/31_145.jpg', 0, '2026-03-29 23:02:00'),
(146, 75, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/75_146.jpg', 0, '2026-03-29 22:41:00'),
(147, 112, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/112_147.jpg', 1, '2026-03-29 22:50:00'),
(148, 56, 'ITO I', 'Yes', 'No', 'On Time', '', 1, '2026-03-29 23:41:00'),
(149, 91, 'AO II', 'Yes', 'Yes', 'On Time', 'uploads/attendance/91_149.jpg', 1, '2026-03-29 23:12:00'),
(150, 70, 'Planning Assistant', 'No', 'No', 'Late', '', 0, '2026-03-30 00:31:00'),
(151, 114, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/114/114_2026-04-13_17-35-03.jpeg', 1, '2026-04-13 09:35:03'),
(152, 113, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/113/113_2026-04-16_08-24-05.jpeg', 1, '2026-04-16 00:24:05'),
(153, 100, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/100/100_2026-04-16_09-34-06.jpeg', 1, '2026-04-16 01:34:06'),
(154, 101, 'AO II', 'Yes', 'Yes', 'On Time', 'uploads/attendance/101/101_2026-04-16_09-34-17.jpeg', 1, '2026-04-16 01:34:17'),
(155, 102, 'Planning Assistant', 'Yes', 'Yes', 'On Time', 'uploads/attendance/102/102_2026-04-16_09-34-27.jpeg', 1, '2026-04-16 01:34:27'),
(156, 103, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/103/103_2026-04-16_09-34-37.jpeg', 1, '2026-04-16 01:34:37'),
(157, 104, 'AO II', 'Yes', 'No', 'On Time', 'uploads/attendance/104/104_2026-04-16_09-35-24.jpeg', 0, '2026-04-16 01:35:24'),
(158, 20, 'Planning Assistant', 'Yes', 'Yes', 'On Time', 'uploads/attendance/20/20_2026-04-16_09-41-15.jpeg', 1, '2026-04-16 01:41:15'),
(159, 14, 'Project Development', 'Yes', 'Yes', 'On Time', 'uploads/attendance/14/14_2026-04-16_09-42-00.jpeg', 1, '2026-04-16 01:42:00'),
(160, 22, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/22/22_2026-04-16_09-42-30.jpeg', 1, '2026-04-16 01:42:30'),
(161, 23, 'AO III', 'Yes', 'Yes', 'On Time', 'uploads/attendance/23/23_2026-04-16_09-42-41.jpeg', 1, '2026-04-16 01:42:41'),
(162, 116, 'ITO II', 'Yes', 'Yes', 'On Time', 'uploads/attendance/116/116_2026-04-16_10-41-55.jpeg', 1, '2026-04-16 02:41:55'),
(163, 117, 'ITO I', 'Yes', 'Yes', 'On Time', 'uploads/attendance/117/117_2026-04-16_14-07-49.jpeg', 1, '2026-04-16 06:07:49'),
(165, 113, 'AO III', 'Yes', 'No', 'Late', 'uploads/attendance/113/113_2026-04-20_08-37-23.jpeg', 0, '2026-04-20 02:37:00'),
(166, 114, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/114/114_2026-04-20_15-22-36.jpeg', 0, '2026-04-20 07:22:36'),
(167, 123, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/123/123_2026-04-20_17-13-59.jpeg', 0, '2026-04-20 09:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `emp_id` int(11) NOT NULL,
  `emp_email` varchar(150) DEFAULT NULL,
  `full` varchar(255) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `area_of_assignment` varchar(150) DEFAULT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`emp_id`, `emp_email`, `full`, `department`, `area_of_assignment`, `designation`, `unit`, `age`, `gender`, `status`) VALUES
(12, 'user12@email.com', 'User 12', 'ORD', 'Sulu', 'AO III', 'CSB/PNPKI', 47, 'MALE', 'Job Order'),
(14, 'user14@email.com', 'User 14', 'ORD', 'Zamboanga del Norte', 'Project Development', 'eLGU', 23, 'FEMALE', 'Job Order'),
(16, 'user16@email.com', 'User 16', 'TOD', 'Zamboanga Sibugay', 'ITO I', 'CSB/PNPKI', 33, 'FEMALE', 'Plantilla'),
(17, 'user17@email.com', 'User 17', 'TOD', 'Zamboanga City', 'ITO I', 'Cashier', 48, 'MALE', 'Job Order'),
(18, 'user18@email.com', 'User 18', 'ORD', 'Basilan', 'ITO II', 'Cashier', 59, 'MALE', 'Plantilla'),
(19, 'user19@email.com', 'User 19', 'TOD', 'Sulu', 'AO II', 'Cashier', 59, 'MALE', 'Job Order'),
(20, 'user20@email.com', 'User 20', 'AFD', 'Tawi-Tawi', 'Planning Assistant', 'eLGU', 39, 'FEMALE', 'Job Order'),
(21, 'user21@email.com', 'User 21', 'AFD', 'Zamboanga del Norte', 'AO II', 'eLGU', 52, 'MALE', 'Job Order'),
(22, 'user22@email.com', 'User 22', 'ORD', 'Zamboanga del Sur', 'AO III', 'eLGU', 33, 'MALE', 'Plantilla'),
(23, 'user23@email.com', 'User 23', 'TOD', 'Zamboanga Sibugay', 'AO III', 'CSB/PNPKI', 53, 'MALE', 'Job Order'),
(24, 'user24@email.com', 'User 24', 'AFD', 'Zamboanga City', 'ITO I', 'SVSI', 56, 'MALE', 'Plantilla'),
(25, 'user25@email.com', 'User 25', 'TOD', 'Basilan', 'ITO II', 'Cashier', 45, 'MALE', 'Plantilla'),
(26, 'user26@email.com', 'User 26', 'AFD', 'Sulu', 'Planning Assistant', 'CSB/PNPKI', 35, 'FEMALE', 'Job Order'),
(27, 'user27@email.com', 'User 27', 'TOD', 'Tawi-Tawi', 'ITO II', 'SVSI', 33, 'FEMALE', 'Job Order'),
(28, 'user28@email.com', 'User 28', 'AFD', 'Zamboanga del Norte', 'ITO I', 'Cashier', 25, 'MALE', 'Plantilla'),
(29, 'user29@email.com', 'User 29', 'ORD', 'Zamboanga del Sur', 'Project Development', 'eLGU', 30, 'MALE', 'Plantilla'),
(30, 'user30@email.com', 'User 30', 'AFD', 'Zamboanga Sibugay', 'Planning Assistant', 'eLGU', 56, 'MALE', 'Job Order'),
(31, 'user31@email.com', 'User 31', 'TOD', 'Zamboanga City', 'Project Development', 'Cashier', 55, 'MALE', 'Plantilla'),
(32, 'user32@email.com', 'User 32', 'ORD', 'Basilan', 'Planning Assistant', 'Cashier', 48, 'FEMALE', 'Plantilla'),
(33, 'user33@email.com', 'User 33', 'ORD', 'Sulu', 'ITO I', 'Cashier', 35, 'FEMALE', 'Plantilla'),
(34, 'user34@email.com', 'User 34', 'TOD', 'Tawi-Tawi', 'Project Development', 'eLGU', 58, 'FEMALE', 'Plantilla'),
(35, 'user35@email.com', 'User 35', 'AFD', 'Zamboanga del Norte', 'ITO I', 'SVSI', 35, 'FEMALE', 'Job Order'),
(36, 'user36@email.com', 'User 36', 'ORD', 'Zamboanga del Sur', 'ITO II', 'eLGU', 53, 'FEMALE', 'Plantilla'),
(37, 'user37@email.com', 'User 37', 'TOD', 'Zamboanga Sibugay', 'AO II', 'Cashier', 32, 'MALE', 'Job Order'),
(38, 'user38@email.com', 'User 38', 'TOD', 'Zamboanga City', 'Planning Assistant', 'Cashier', 23, 'FEMALE', 'Job Order'),
(40, 'user40@email.com', 'User 40', 'TOD', 'Sulu', 'Project Development', 'SVSI', 28, 'FEMALE', 'Job Order'),
(41, 'user41@email.com', 'User 41', 'AFD', 'Tawi-Tawi', 'AO II', 'Cashier', 59, 'FEMALE', 'Plantilla'),
(42, 'user42@email.com', 'User 42', 'ORD', 'Zamboanga del Norte', 'AO III', 'SVSI', 48, 'FEMALE', 'Job Order'),
(43, 'user43@email.com', 'User 43', 'AFD', 'Zamboanga del Sur', 'Project Development', 'SVSI', 35, 'FEMALE', 'Job Order'),
(44, 'user44@email.com', 'User 44', 'ORD', 'Zamboanga Sibugay', 'ITO II', 'SVSI', 46, 'MALE', 'Job Order'),
(45, 'user45@email.com', 'User 45', 'TOD', 'Zamboanga City', 'AO III', 'CSB/PNPKI', 53, 'FEMALE', 'Plantilla'),
(46, 'user46@email.com', 'User 46', 'ORD', 'Basilan', 'ITO II', 'SVSI', 42, 'FEMALE', 'Job Order'),
(47, 'user47@email.com', 'User 47', 'ORD', 'Sulu', 'ITO I', 'CSB/PNPKI', 44, 'MALE', 'Plantilla'),
(48, 'user48@email.com', 'User 48', 'ORD', 'Tawi-Tawi', 'AO III', 'eLGU', 22, 'FEMALE', 'Job Order'),
(49, 'user49@email.com', 'User 49', 'TOD', 'Zamboanga del Norte', 'Project Development', 'CSB/PNPKI', 37, 'FEMALE', 'Plantilla'),
(50, 'user50@email.com', 'User 50', 'ORD', 'Zamboanga del Sur', 'Planning Assistant', 'eLGU', 57, 'FEMALE', 'Plantilla'),
(51, 'user51@email.com', 'User 51', 'TOD', 'Zamboanga Sibugay', 'AO III', 'SVSI', 58, 'FEMALE', 'Job Order'),
(52, 'user52@email.com', 'User 52', 'TOD', 'Zamboanga City', 'ITO II', 'CSB/PNPKI', 35, 'MALE', 'Plantilla'),
(53, 'user53@email.com', 'User 53', 'TOD', 'Basilan', 'AO II', 'SVSI', 56, 'MALE', 'Plantilla'),
(54, 'user54@email.com', 'User 54', 'AFD', 'Sulu', 'Project Development', 'Cashier', 38, 'MALE', 'Plantilla'),
(55, 'user55@email.com', 'User 55', 'AFD', 'Tawi-Tawi', 'ITO II', 'Cashier', 58, 'MALE', 'Job Order'),
(56, 'user56@email.com', 'User 56', 'TOD', 'Zamboanga del Norte', 'ITO II', 'Cashier', 58, 'MALE', 'Plantilla'),
(57, 'user57@email.com', 'User 57', 'TOD', 'Zamboanga del Sur', 'Project Development', 'SVSI', 45, 'MALE', 'Job Order'),
(58, 'user58@email.com', 'User 58', 'AFD', 'Zamboanga Sibugay', 'AO II', 'Cashier', 37, 'MALE', 'Job Order'),
(59, 'user59@email.com', 'User 59', 'ORD', 'Zamboanga City', 'AO II', 'SVSI', 51, 'FEMALE', 'Job Order'),
(60, 'user60@email.com', 'User 60', 'TOD', 'Basilan', 'AO II', 'Cashier', 26, 'FEMALE', 'Plantilla'),
(61, 'user61@email.com', 'User 61', 'TOD', 'Sulu', 'ITO II', 'Cashier', 26, 'MALE', 'Plantilla'),
(62, 'user62@email.com', 'User 62', 'ORD', 'Tawi-Tawi', 'Planning Assistant', 'Cashier', 33, 'MALE', 'Job Order'),
(63, 'user63@email.com', 'User 63', 'ORD', 'Zamboanga del Norte', 'Planning Assistant', 'CSB/PNPKI', 35, 'FEMALE', 'Job Order'),
(64, 'user64@email.com', 'User 64', 'AFD', 'Zamboanga del Sur', 'ITO II', 'Cashier', 44, 'MALE', 'Plantilla'),
(65, 'user65@email.com', 'User 65', 'TOD', 'Zamboanga Sibugay', 'Planning Assistant', 'eLGU', 40, 'FEMALE', 'Plantilla'),
(66, 'user66@email.com', 'User 66', 'TOD', 'Zamboanga City', 'ITO I', 'CSB/PNPKI', 48, 'MALE', 'Plantilla'),
(67, 'user67@email.com', 'User 67', 'TOD', 'Basilan', 'AO III', 'SVSI', 49, 'FEMALE', 'Plantilla'),
(68, 'user68@email.com', 'User 68', 'TOD', 'Sulu', 'Project Development', 'eLGU', 34, 'MALE', 'Job Order'),
(69, 'user69@email.com', 'User 69', 'ORD', 'Tawi-Tawi', 'ITO II', 'eLGU', 36, 'MALE', 'Job Order'),
(70, 'user70@email.com', 'User 70', 'TOD', 'Zamboanga del Norte', 'AO II', 'SVSI', 35, 'MALE', 'Plantilla'),
(71, 'user71@email.com', 'User 71', 'ORD', 'Zamboanga del Sur', 'Planning Assistant', 'SVSI', 31, 'MALE', 'Plantilla'),
(72, 'user72@email.com', 'User 72', 'TOD', 'Zamboanga Sibugay', 'ITO II', 'Cashier', 22, 'FEMALE', 'Plantilla'),
(73, 'user73@email.com', 'User 73', 'AFD', 'Zamboanga City', 'ITO I', 'CSB/PNPKI', 56, 'MALE', 'Job Order'),
(74, 'user74@email.com', 'User 74', 'ORD', 'Basilan', 'ITO I', 'CSB/PNPKI', 35, 'FEMALE', 'Plantilla'),
(75, 'user75@email.com', 'User 75', 'TOD', 'Sulu', 'Planning Assistant', 'Cashier', 22, 'MALE', 'Plantilla'),
(76, 'user76@email.com', 'User 76', 'TOD', 'Tawi-Tawi', 'Planning Assistant', 'eLGU', 38, 'MALE', 'Plantilla'),
(77, 'user77@email.com', 'User 77', 'ORD', 'Zamboanga del Norte', 'AO III', 'SVSI', 22, 'FEMALE', 'Plantilla'),
(78, 'user78@email.com', 'User 78', 'ORD', 'Zamboanga del Sur', 'AO III', 'eLGU', 24, 'MALE', 'Job Order'),
(79, 'user79@email.com', 'User 79', 'AFD', 'Zamboanga Sibugay', 'Planning Assistant', 'SVSI', 33, 'FEMALE', 'Plantilla'),
(80, 'user80@email.com', 'User 80', 'AFD', 'Zamboanga City', 'AO III', 'eLGU', 26, 'MALE', 'Plantilla'),
(81, 'user81@email.com', 'User 81', 'TOD', 'Basilan', 'Planning Assistant', 'Cashier', 27, 'MALE', 'Plantilla'),
(82, 'user82@email.com', 'User 82', 'ORD', 'Sulu', 'AO III', 'eLGU', 26, 'MALE', 'Plantilla'),
(84, 'user84@email.com', 'User 84', 'AFD', 'Zamboanga del Norte', 'Planning Assistant', 'SVSI', 33, 'FEMALE', 'Job Order'),
(85, 'user85@email.com', 'User 85', 'AFD', 'Zamboanga del Sur', 'AO III', 'Cashier', 57, 'MALE', 'Plantilla'),
(86, 'user86@email.com', 'User 86', 'AFD', 'Zamboanga Sibugay', 'ITO I', 'CSB/PNPKI', 39, 'MALE', 'Plantilla'),
(87, 'user87@email.com', 'User 87', 'ORD', 'Zamboanga City', 'Planning Assistant', 'SVSI', 24, 'MALE', 'Job Order'),
(88, 'user88@email.com', 'User 88', 'AFD', 'Basilan', 'Planning Assistant', 'eLGU', 43, 'MALE', 'Job Order'),
(89, 'user89@email.com', 'User 89', 'TOD', 'Sulu', 'ITO II', 'CSB/PNPKI', 41, 'MALE', 'Plantilla'),
(90, 'user90@email.com', 'User 90', 'ORD', 'Tawi-Tawi', 'AO III', 'eLGU', 59, 'FEMALE', 'Job Order'),
(91, 'user91@email.com', 'User 91', 'ORD', 'Zamboanga del Norte', 'AO II', 'CSB/PNPKI', 42, 'MALE', 'Job Order'),
(92, 'user92@email.com', 'User 92', 'AFD', 'Zamboanga del Sur', 'ITO I', 'CSB/PNPKI', 34, 'MALE', 'Plantilla'),
(93, 'user93@email.com', 'User 93', 'ORD', 'Zamboanga Sibugay', 'Planning Assistant', 'CSB/PNPKI', 30, 'MALE', 'Plantilla'),
(94, 'user94@email.com', 'User 94', 'ORD', 'Zamboanga City', 'AO III', 'eLGU', 58, 'FEMALE', 'Job Order'),
(95, 'user95@email.com', 'User 95', 'AFD', 'Basilan', 'ITO I', 'CSB/PNPKI', 40, 'MALE', 'Plantilla'),
(96, 'user96@email.com', 'User 96', 'TOD', 'Sulu', 'AO III', 'eLGU', 55, 'MALE', 'Plantilla'),
(97, 'user97@email.com', 'User 97', 'ORD', 'Tawi-Tawi', 'AO II', 'Cashier', 48, 'MALE', 'Job Order'),
(98, 'user98@email.com', 'User 98', 'TOD', 'Zamboanga del Norte', 'ITO I', 'CSB/PNPKI', 38, 'MALE', 'Plantilla'),
(99, 'user99@email.com', 'User 99', 'ORD', 'Zamboanga del Sur', 'Planning Assistant', 'Cashier', 56, 'MALE', 'Job Order'),
(100, 'user100@email.com', 'User 100', 'TOD', 'Regional Office', 'Project Development', 'SVSI', 35, 'MALE', 'Job Order'),
(101, 'user101@email.com', 'User 101', 'TOD', 'Zamboanga City', 'AO II', 'SVSI', 52, 'MALE', 'Plantilla'),
(102, 'user102@email.com', 'User 102', 'ORD', 'Basilan', 'Planning Assistant', 'SVSI', 33, 'FEMALE', 'Plantilla'),
(103, 'user103@email.com', 'User 103', 'ORD', 'Sulu', 'AO III', 'Cashier', 26, 'MALE', 'Plantilla'),
(104, 'user104@email.com', 'User 104', 'ORD', 'Tawi-Tawi', 'AO II', 'CSB/PNPKI', 35, 'FEMALE', 'Job Order'),
(105, 'user105@email.com', 'User 105', 'AFD', 'Zamboanga del Norte', 'Project Development', 'SVSI', 41, 'MALE', 'Plantilla'),
(106, 'user106@email.com', 'User 106', 'ORD', 'Zamboanga del Sur', 'ITO II', 'eLGU', 50, 'MALE', 'Plantilla'),
(107, 'user107@email.com', 'User 107', 'ORD', 'Zamboanga Sibugay', 'ITO I', 'CSB/PNPKI', 47, 'FEMALE', 'Plantilla'),
(108, 'user108@email.com', 'User 108', 'TOD', 'Zamboanga City', 'ITO II', 'SVSI', 33, 'MALE', 'Plantilla'),
(109, 'user109@email.com', 'User 109', 'ORD', 'Basilan', 'Planning Assistant', 'SVSI', 35, 'FEMALE', 'Plantilla'),
(110, 'user110@email.com', 'User 110', 'TOD', 'Sulu', 'Project Development', 'Cashier', 56, 'FEMALE', 'Plantilla'),
(111, 'user111@email.com', 'User 111', 'TOD', 'Tawi-Tawi', 'Planning Assistant', 'Cashier', 51, 'FEMALE', 'Job Order'),
(112, 'MDLuffy@gmail.com', 'Monkey D. Luffy', 'ORD', 'Zamboanga City', 'Planning Assistant', 'CSB/PNPKI', 29, 'MALE', 'Job Order'),
(113, 'alrajitheng@gmail.com', 'Al-Raji J. Theng', 'AFD', 'Basilan', 'AO III', 'Cashier', 60, 'MALE', 'Job Order'),
(114, 'Aldrin.cagan@one.uz.edu.ph', 'Aldrin Cagan', 'ORD', 'Tawi-Tawi', 'Project Development', 'eLGU', 90, 'MALE', 'Job Order'),
(115, 'Justin.shia@one.uz.edu.ph', 'Justin Shia', 'TOD', 'Zamboanga City', 'ITO I', 'SVSI', 67, 'MALE', 'Plantilla'),
(116, 'christian@gmail.com', 'Christian L. Mosquiza', 'AFD', 'Regional Office', 'ITO II', 'CSB/PNPKI', 74, 'MALE', 'Plantilla'),
(117, 'mohammadsalih.musa@one.uz.edu.ph', 'mohammad salih musa', 'TOD', 'Zamboanga City', 'ITO I', 'CSB/PNPKI', 22, 'MALE', 'Plantilla'),
(119, 'user119@email.com', 'User 119', 'AFD', 'Zamboanga del Norte', 'ITO I', 'Cashier', 56, 'MALE', 'Job Order'),
(120, 'user120@email.com', 'User 120', 'ORD', 'Zamboanga del Sur', 'AO III', 'CSB/PNPKI', 33, 'MALE', 'Plantilla'),
(121, 'walid.salih@one.uz.edu.ph', 'Walid B. Salih', 'TOD', 'Regional Office', 'ITO I', 'CSB/PNPKI', 23, 'MALE', 'Plantilla'),
(123, 'al-raji.theng@one.uz.edu.ph', 'Al-Raji J. Theng', 'TOD', 'Regional Office', 'Project Development', 'CSB/PNPKI', 23, 'MALE', 'Plantilla');

-- --------------------------------------------------------

--
-- Table structure for table `unlocked_dates`
--

CREATE TABLE `unlocked_dates` (
  `id` int(11) NOT NULL,
  `target_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unlocked_dates`
--

INSERT INTO `unlocked_dates` (`id`, `target_date`) VALUES
(3, '2026-04-16');

-- --------------------------------------------------------

--
-- Table structure for table `user_account`
--

CREATE TABLE `user_account` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'user' CHECK (`role` in ('admin','user')),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_account`
--

INSERT INTO `user_account` (`id`, `emp_id`, `username`, `password`, `role`, `created_at`) VALUES
(3, 112, 'Luffy', '$2y$10$KfxMULJNJsGjr19s7Rg2AOjvxjyP3bimqnobb.hAGWTFQCco9DrUa', 'user', '2026-03-24 01:50:22'),
(4, 113, 'alrajitheng', '$2y$10$5Nj.6jEsDh/XLonK68OCl.xhBhUxB0eubUBuXu9SG4YmwMCJTO7qS', 'admin', '2026-03-24 05:38:18'),
(5, 114, 'Aldrin', '$2y$10$Y8LdZYvRxWGsJoSDYWXp7uznzfu0Zfrl8PfXe6qGmfP1zHrNX5ZOS', 'user', '2026-03-24 06:17:42'),
(6, 115, 'Justin', '$2y$10$OPHivmkjsvLajmpQE8TtLOUonkSr1LcbE585ZfIc0tm4V0XRpZXR2', 'user', '2026-03-24 07:37:44'),
(8, 116, 'christian', '$2y$10$em/OWiNL13GtOn3let9AC.1Svy/hsgB9WrmJJS8HFaXUrJc4r/K6e', 'user', '2026-03-25 23:23:03'),
(9, 117, 'musa', '$2y$10$WXU0gHd648doimJFPOZ4NO2XXL7o9KJwZAAM1xXzc4F1deyGgUWhW', 'user', '2026-03-26 00:11:24'),
(17, 121, 'walid', '$2y$10$MFv7ZivJQb/N7OvGKr6ZROgx1rVZz2QO6nlpSHxR0jM2nCGifVsOm', 'admin', '2026-04-15 00:45:04'),
(19, 100, 'User100', '$2y$10$5AeVqwT5FyNWPhgLYNaj1.Y5dem7y0KBqLC514OT.DdcaHNwxNCG2', 'user', '2026-04-16 01:31:12'),
(20, 101, 'User101', '$2y$10$4U2LhG0PxDtpSdsfAnIEsuP2rF6isl5mDMPjzEWKE26WAYusN.LZG', 'user', '2026-04-16 01:32:24'),
(22, 102, 'User102', '$2y$10$Rn9jSxstNLLGUbAkekn4yeCgpL5wCWY6YOLO0d39kMidbCXPHwquW', 'user', '2026-04-16 01:32:52'),
(23, 103, 'User103', '$2y$10$lTZDYwIoDuniAMfiIpU/4Ors7x2tIm5jMVYlkuJNJbAbJwJ/pN89u', 'user', '2026-04-16 01:33:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_record`
--
ALTER TABLE `attendance_record`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `fk_employee_attendance` (`emp_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`emp_id`);

--
-- Indexes for table `unlocked_dates`
--
ALTER TABLE `unlocked_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `target_date` (`target_date`);

--
-- Indexes for table `user_account`
--
ALTER TABLE `user_account`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_user_employee` (`emp_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_record`
--
ALTER TABLE `attendance_record`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `unlocked_dates`
--
ALTER TABLE `unlocked_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_record`
--
ALTER TABLE `attendance_record`
  ADD CONSTRAINT `fk_employee_attendance` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_account`
--
ALTER TABLE `user_account`
  ADD CONSTRAINT `fk_user_employee` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
