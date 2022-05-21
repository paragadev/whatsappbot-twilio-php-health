-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 21, 2022 at 04:56 AM
-- Server version: 8.0.29-0ubuntu0.20.04.3
-- PHP Version: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `healthbot`
--
CREATE DATABASE IF NOT EXISTS `healthbot` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `healthbot`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_hospitals`
--

DROP TABLE IF EXISTS `admin_hospitals`;
CREATE TABLE IF NOT EXISTS `admin_hospitals` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `hid` tinytext,
  `code` tinytext,
  `title` text,
  `gmap_url` tinytext,
  `helpline` tinytext,
  `website` tinytext,
  `isactive` tinyint(1) DEFAULT NULL,
  `last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `Id` (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_medicine_order`
--

DROP TABLE IF EXISTS `admin_medicine_order`;
CREATE TABLE IF NOT EXISTS `admin_medicine_order` (
  `id` int NOT NULL,
  `medicine_request_id` int DEFAULT NULL,
  `medicine_order_status_id` tinyint DEFAULT NULL,
  `domain_pharmacy_id` smallint DEFAULT NULL,
  `delivery_person_name` text,
  `delivery_person_mobile` text,
  `est_delivery` datetime DEFAULT NULL,
  `bill` text,
  `created_on` datetime DEFAULT NULL,
  `updated_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_pharmacy`
--

DROP TABLE IF EXISTS `admin_pharmacy`;
CREATE TABLE IF NOT EXISTS `admin_pharmacy` (
  `id` int NOT NULL,
  `name` text,
  `address` text,
  `zipcode_start` text,
  `zipcode_end` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_user_by_clinic`
--

DROP TABLE IF EXISTS `feedback_user_by_clinic`;
CREATE TABLE IF NOT EXISTS `feedback_user_by_clinic` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hospital_name` varchar(80) NOT NULL,
  `appointment_id` int NOT NULL,
  `iMemberId` int NOT NULL,
  `vPatientUserId` varchar(11) NOT NULL,
  `vMemberFname` varchar(11) NOT NULL,
  `vMemberLname` varchar(11) NOT NULL,
  `vMobileNo` varchar(13) NOT NULL,
  `vGender` varchar(15) NOT NULL,
  `clinic_id` int DEFAULT NULL,
  `appointment_time` varchar(88) NOT NULL,
  `reason` text NOT NULL,
  `feedback_sent` int NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step` int DEFAULT '0',
  `response` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `status` enum('active','inactive','') NOT NULL DEFAULT 'active',
  `q1` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `q2` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `q3` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `q4` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `q5` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `q6` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `q7` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_request`
--

DROP TABLE IF EXISTS `medicine_request`;
CREATE TABLE IF NOT EXISTS `medicine_request` (
  `Id` int NOT NULL,
  `patient_registration_id` int DEFAULT NULL,
  `address` text,
  `zipcode` text,
  `prescription` text,
  `workflow_completed` tinyint(1) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_on` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_registration`
--

DROP TABLE IF EXISTS `patient_registration`;
CREATE TABLE IF NOT EXISTS `patient_registration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `current_step` tinyint DEFAULT NULL,
  `name` text,
  `whatsapp_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `hid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'hospital id',
  `hid_code` text,
  `age` tinyint DEFAULT NULL,
  `gender` tinyint DEFAULT NULL COMMENT 'domain data from embebo ',
  `profile_pic` text,
  `reg_id` text COMMENT 'patient registration id',
  `speciality` text COMMENT 'domain data from embebo ',
  `doctor` text,
  `main_menu_option` text,
  `sub_menu_option` text,
  `symptom` text,
  `appt_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'patient appointment id',
  `payment_done` tinyint(1) DEFAULT NULL,
  `workflow_completed` tinyint(1) DEFAULT NULL,
  `feedback` int NOT NULL DEFAULT '0',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
