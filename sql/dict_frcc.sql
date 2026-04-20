-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 03:44 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

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
(81, 117, NULL, 'Yes', 'Yes', 'On Time', NULL, 1, '2026-03-29 22:56:22'),
(82, 117, 'ITO1', 'Yes', 'Yes', 'On Time', 'uploads/attendance/117/117_2026-03-30_06-56-22.jpeg', 0, '2026-03-29 22:56:22'),
(83, 114, NULL, 'Yes', 'Yes', 'Late', NULL, 0, '2026-03-30 02:02:06'),
(84, 114, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/114/114_2026-03-30_10-02-06.jpeg', 0, '2026-03-30 02:02:06'),
(85, 117, 'Employee', 'Yes', 'Yes', 'Late', NULL, 0, '2026-03-30 02:09:32'),
(86, 117, 'ITO I', 'Yes', 'Yes', 'Late', 'uploads/attendance/117/117_2026-03-30_10-09-32.jpeg', 0, '2026-03-30 02:09:32'),
(87, 114, 'Project Development', 'Yes', 'Yes', 'Late', 'uploads/attendance/114/114_2026-03-30_10-25-57.jpeg', 0, '2026-03-30 02:25:57'),
(88, 112, 'Planning Assistant', 'Yes', 'Yes', 'On Time', 'uploads/attendance/112/112_2026-03-30_07-28-38.jpeg', 1, '2026-03-29 23:28:38'),
(89, 113, 'AO III', 'Yes', 'Yes', 'Late', 'uploads/attendance/113/113_2026-03-30_14-50-05.jpeg', 0, '2026-03-30 06:50:05'),
(90, 115, 'ITO I', 'Yes', 'Yes', 'On Time', 'uploads/attendance/115/115_2026-04-13_11-08-17.jpeg', 1, '2026-04-13 03:08:17'),
(91, 115, 'ITO I', 'Yes', 'Yes', 'Late', 'uploads/attendance/115/115_2026-04-13_11-18-55.jpeg', 1, '2026-04-13 03:18:55'),
(92, 2, 'Planning Assistant', 'Yes', 'Yes', 'Late', 'uploads/attendance/2/2_2026-04-20_09-00-24.jpeg', 0, '2026-04-20 01:00:24');

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
(2, 'walid.salih@one.uz.edu.ph', 'Walid B. Salih', 'TOD', 'Tawi-Tawi', 'Planning Assistant', 'CSB/PNPKI', 26, 'FEMALE', 'Plantilla'),
(112, 'MDLuffy@gmail.com', 'Monkey D. Luffy', 'ORD', 'Zamboanga City', 'Planning Assistant', 'CSB/PNPKI', 29, 'MALE', 'Job Order'),
(113, 'alrajitheng@gmail.com', 'Al-Raji J. Theng', 'AFD', 'Basilan', 'AO III', 'Cashier', 60, 'MALE', 'Job Order'),
(114, 'Aldrin.cagan@one.uz.edu.ph', 'Aldrin Cagan', 'ORD', 'Tawi-Tawi', 'Project Development', 'eLGU', 90, 'MALE', 'Job Order'),
(115, 'Justin.shia@one.uz.edu.ph', 'Justin Shia', 'TOD', 'Zamboanga City', 'ITO I', 'SVSI', 67, 'MALE', 'Plantilla'),
(116, 'christian@gmail.com', 'Christian L. Mosquiza', 'AFD', 'Regional Office', 'ITO II', 'CSB/PNPKI', 74, 'MALE', 'Plantilla'),
(117, 'mohammadsalih.musa@one.uz.edu.ph', 'mohammad salih musa', 'TOD', 'Zamboanga City', 'ITO I', 'CSB/PNPKI', 22, 'MALE', 'Plantilla');

-- --------------------------------------------------------

--
-- Table structure for table `unlocked_dates`
--

CREATE TABLE `unlocked_dates` (
  `id` int(11) NOT NULL,
  `target_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 2, 'walid', '$2y$10$O7Mf7qaQvBpj00aDkVH8h.Mul/hvw7Jo2/s0sPZbAwTPhF9kUhvO.', 'user', '2026-03-23 02:54:25'),
(3, 112, 'Luffy', '$2y$10$KfxMULJNJsGjr19s7Rg2AOjvxjyP3bimqnobb.hAGWTFQCco9DrUa', 'user', '2026-03-24 01:50:22'),
(4, 113, 'alrajitheng', '$2y$10$5Nj.6jEsDh/XLonK68OCl.xhBhUxB0eubUBuXu9SG4YmwMCJTO7qS', 'admin', '2026-03-24 05:38:18'),
(5, 114, 'Aldrin', '$2y$10$ktyZ6K.5ZWMkHu5DRGb5wuVs8CUE.8VseS4ZsLmyWWqGjlvIaRYNW', 'user', '2026-03-24 06:17:42'),
(6, 115, 'Justin', '$2y$10$OPHivmkjsvLajmpQE8TtLOUonkSr1LcbE585ZfIc0tm4V0XRpZXR2', 'user', '2026-03-24 07:37:44'),
(8, 116, 'christian', '$2y$10$em/OWiNL13GtOn3let9AC.1Svy/hsgB9WrmJJS8HFaXUrJc4r/K6e', 'user', '2026-03-25 23:23:03'),
(9, 117, 'musa', '$2y$10$ul9r2nHZpxR3JB15Vuf.xOy87j8s0ztL4ZxbdGtCieGYiEkitg0ia', 'user', '2026-03-26 00:11:24');

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
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `unlocked_dates`
--
ALTER TABLE `unlocked_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
