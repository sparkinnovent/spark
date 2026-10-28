-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 18, 2026 at 10:58 AM
-- Server version: 10.3.39-MariaDB-log-cll-lve
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uitmktco_gdiid25`
--

-- --------------------------------------------------------

--
-- Table structure for table `award`
--

CREATE TABLE `award` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` int(8) NOT NULL,
  `member_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `member_password` varchar(64) NOT NULL,
  `member_email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `member_level` int(1) NOT NULL DEFAULT 3,
  `last_signin` datetime DEFAULT NULL,
  `current_signin` datetime DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `members`
--


-- --------------------------------------------------------

--
-- Table structure for table `members_role`
--

CREATE TABLE `members_role` (
  `code` int(8) NOT NULL,
  `jawatan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `members_role`
--

INSERT INTO `members_role` (`code`, `jawatan`) VALUES
(1, 'super admin'),
(2, 'admin'),
(3, 'bendahari'),
(4, 'sijil'),
(5, 'ajk');

-- --------------------------------------------------------

--
-- Table structure for table `payment_rate`
--

CREATE TABLE `payment_rate` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` varchar(50) NOT NULL,
  `date_end` date DEFAULT NULL,
  `currency` varchar(10) NOT NULL,
  `rate` int(8) NOT NULL,
  `link` varchar(2083) DEFAULT NULL,
  `remark` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payment_rate`
--


-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `id` int(11) NOT NULL,
  `reg_datetime` datetime DEFAULT NULL,
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `organisation` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `country` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `contact_no` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `cat_iid` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `title` varchar(500) NOT NULL,
  `status` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'REGISTER',
  `student_status` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'VERIFIED',
  `loa_status` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'NA',
  `payment_status` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'UNPAID',
  `presentation_status` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'NA',
  `abstract_status` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'NA',
  `uitmctkt` tinyint(1) NOT NULL DEFAULT 0,
  `upload_id` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `upload__timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploader_id` int(12) NOT NULL,
  `fees` int(5) DEFAULT NULL,
  `student_card_link` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `abstract_link` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `video_link` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `vid_yt_link` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `ex_abstract_file` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `turnitin_file` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `currency` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'MYR',
  `last_signin` datetime DEFAULT NULL,
  `current_signin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_award`
--

CREATE TABLE `project_award` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `cert_no` text NOT NULL,
  `code` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(100) NOT NULL DEFAULT 'GENERATE',
  `files` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_cert`
--

CREATE TABLE `project_cert` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `cert_no` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(100) NOT NULL DEFAULT 'GENERATE',
  `files` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_extended_abstract`
--

CREATE TABLE `project_extended_abstract` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `ex_abstract_file` varchar(255) NOT NULL,
  `turnitin_file` varchar(255) NOT NULL,
  `upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'UPLOAD',
  `verify_by` int(8) DEFAULT NULL,
  `status_timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_extended_abstract`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_ex_abstract_review`
--

CREATE TABLE `project_ex_abstract_review` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `review_file` varchar(255) NOT NULL,
  `uploaded_by` int(8) NOT NULL,
  `upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'REVIEW'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_ex_abstract_review`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_loa`
--

CREATE TABLE `project_loa` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `admin_id` int(8) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_loa`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_member`
--

CREATE TABLE `project_member` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `position` int(1) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `organisation` varchar(255) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `upload_id` varchar(255) NOT NULL,
  `uplod_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_member`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_payment`
--

CREATE TABLE `project_payment` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_proof` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_type` varchar(255) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'UPLOAD' COMMENT 'upload, accept, reject',
  `verified_by` int(8) DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `reject_comment` text DEFAULT NULL,
  `verification_timestamp` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_payment`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_presentation`
--

CREATE TABLE `project_presentation` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `video_link` varchar(300) NOT NULL,
  `recorded` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(8) DEFAULT NULL,
  `reject_reason` varchar(300) DEFAULT NULL,
  `reject_comment` varchar(300) DEFAULT NULL,
  `verified_timestamp` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_presentation`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_receipt`
--

CREATE TABLE `project_receipt` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `uploader_id` int(8) NOT NULL,
  `receipt_file` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_student_reject`
--

CREATE TABLE `project_student_reject` (
  `id` int(11) NOT NULL,
  `project_id` int(8) NOT NULL,
  `email` varchar(100) NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `verify_by` int(8) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_timeline`
--

CREATE TABLE `project_timeline` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `register` datetime DEFAULT NULL,
  `payment` datetime DEFAULT NULL,
  `payment_verified` datetime DEFAULT NULL,
  `presentation` datetime DEFAULT NULL,
  `presentation_verified` datetime DEFAULT NULL,
  `ex_abstract` datetime DEFAULT NULL,
  `ex_abstract_verified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_timeline`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_upload`
--

CREATE TABLE `project_upload` (
  `id` int(11) NOT NULL,
  `upload_id` varchar(100) NOT NULL COMMENT 'accommodate upload id 2023',
  `uploader_id` int(12) NOT NULL,
  `file_upload` varchar(100) NOT NULL,
  `upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='track record bila admin upload ni';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `award`
--
ALTER TABLE `award`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `payment_rate`
--
ALTER TABLE `payment_rate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_award`
--
ALTER TABLE `project_award`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_cert`
--
ALTER TABLE `project_cert`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_extended_abstract`
--
ALTER TABLE `project_extended_abstract`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_ex_abstract_review`
--
ALTER TABLE `project_ex_abstract_review`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_loa`
--
ALTER TABLE `project_loa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_member`
--
ALTER TABLE `project_member`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_payment`
--
ALTER TABLE `project_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_presentation`
--
ALTER TABLE `project_presentation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_receipt`
--
ALTER TABLE `project_receipt`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_student_reject`
--
ALTER TABLE `project_student_reject`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_timeline`
--
ALTER TABLE `project_timeline`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_upload`
--
ALTER TABLE `project_upload`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `award`
--
ALTER TABLE `award`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_rate`
--
ALTER TABLE `payment_rate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_award`
--
ALTER TABLE `project_award`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_cert`
--
ALTER TABLE `project_cert`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_extended_abstract`
--
ALTER TABLE `project_extended_abstract`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_ex_abstract_review`
--
ALTER TABLE `project_ex_abstract_review`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_loa`
--
ALTER TABLE `project_loa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_member`
--
ALTER TABLE `project_member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_payment`
--
ALTER TABLE `project_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_presentation`
--
ALTER TABLE `project_presentation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_receipt`
--
ALTER TABLE `project_receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_student_reject`
--
ALTER TABLE `project_student_reject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_timeline`
--
ALTER TABLE `project_timeline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_upload`
--
ALTER TABLE `project_upload`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
