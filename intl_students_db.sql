-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2026 at 01:34 AM
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
-- Database: `intl_students_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_terms`
--

CREATE TABLE `enrollment_terms` (
  `term_id` int(11) NOT NULL,
  `student_id` varchar(15) NOT NULL,
  `term_code` varchar(10) NOT NULL,
  `level` enum('GR','UG') NOT NULL,
  `major` varchar(150) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending I-20',
  `program_start_date` date DEFAULT NULL,
  `start_date_changed_to` date DEFAULT NULL,
  `accepted_term` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `i20_documents`
--

CREATE TABLE `i20_documents` (
  `i20_id` int(11) NOT NULL,
  `student_id` varchar(15) NOT NULL,
  `term_id` int(11) NOT NULL,
  `i20_number` varchar(30) DEFAULT NULL,
  `i20_document_received` date DEFAULT NULL,
  `export_controls_requested` date DEFAULT NULL,
  `export_controls_cleared` date DEFAULT NULL,
  `i20_issued` date DEFAULT NULL,
  `updated_i20` date DEFAULT NULL,
  `deferral_form_received` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orientation_checklist`
--

CREATE TABLE `orientation_checklist` (
  `checklist_id` int(11) NOT NULL,
  `student_id` varchar(15) NOT NULL,
  `term_id` int(11) NOT NULL,
  `acceptance_letter_sent` date DEFAULT NULL,
  `welcome_sent` date DEFAULT NULL,
  `welcome_resent` date DEFAULT NULL,
  `next_steps_letter_sent` date DEFAULT NULL,
  `emergency_appointment_letter` date DEFAULT NULL,
  `faculty_letter_sent` date DEFAULT NULL,
  `your_new_home_letter_sent` date DEFAULT NULL,
  `blackboard_course_emailed` date DEFAULT NULL,
  `housing_email_sent` date DEFAULT NULL,
  `id_username_login_sent` date DEFAULT NULL,
  `orientation_begun` date DEFAULT NULL,
  `orientation_complete` date DEFAULT NULL,
  `checked_in` date DEFAULT NULL,
  `updated_goaintl` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(15) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `personal_email` varchar(150) DEFAULT NULL,
  `university_email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `level` enum('GR','UG') NOT NULL,
  `major` varchar(150) DEFAULT NULL,
  `recruiter` enum('Y','N') DEFAULT 'N',
  `merit` enum('Y','N') DEFAULT 'N',
  `transfer_or_new` enum('T','N') DEFAULT 'N',
  `in_state` enum('Y','N') DEFAULT 'N',
  `notes` text DEFAULT NULL,
  `last_contact` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_information`
--

CREATE TABLE `visa_information` (
  `visa_id` int(11) NOT NULL,
  `student_id` varchar(15) NOT NULL,
  `visa_type` varchar(10) DEFAULT NULL,
  `visa_number` varchar(30) DEFAULT NULL,
  `visa_issuance_date` date DEFAULT NULL,
  `visa_expiration_date` date DEFAULT NULL,
  `visa_issuance_post` varchar(100) DEFAULT NULL,
  `port_of_entry` varchar(10) DEFAULT NULL,
  `date_of_entry` date DEFAULT NULL,
  `i94_admission_number` varchar(30) DEFAULT NULL,
  `admit_until_date` varchar(20) DEFAULT NULL,
  `sevis_fee_paid` tinyint(1) DEFAULT 0,
  `visa_issued` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_alerts`
-- (See below for the actual view)
--
CREATE TABLE `v_alerts` (
`student_id` varchar(15)
,`full_name` varchar(150)
,`term_code` varchar(10)
,`status` varchar(50)
,`alert_message` varchar(33)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_dashboard_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_dashboard_summary` (
`term_code` varchar(10)
,`level` enum('GR','UG')
,`total` bigint(21)
,`checked_in` decimal(23,0)
,`i20_pending` decimal(23,0)
,`visa_pending` decimal(23,0)
,`orientation_done` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_full`
-- (See below for the actual view)
--
CREATE TABLE `v_student_full` (
`student_id` varchar(15)
,`full_name` varchar(150)
,`personal_email` varchar(150)
,`university_email` varchar(150)
,`phone` varchar(30)
,`country` varchar(100)
,`level` enum('GR','UG')
,`major` varchar(150)
,`recruiter` enum('Y','N')
,`merit` enum('Y','N')
,`notes` text
,`last_contact` date
,`term_code` varchar(10)
,`status` varchar(50)
,`program_start_date` date
,`visa_type` varchar(10)
,`visa_number` varchar(30)
,`visa_expiration_date` date
,`visa_issuance_post` varchar(100)
,`port_of_entry` varchar(10)
,`date_of_entry` date
,`admit_until_date` varchar(20)
,`sevis_fee_paid` tinyint(1)
,`i20_number` varchar(30)
,`i20_document_received` date
,`export_controls_requested` date
,`export_controls_cleared` date
,`i20_issued` date
,`welcome_sent` date
,`next_steps_letter_sent` date
,`orientation_complete` date
,`checked_in` date
);

-- --------------------------------------------------------

--
-- Structure for view `v_alerts`
--
DROP TABLE IF EXISTS `v_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_alerts`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`full_name` AS `full_name`, `et`.`term_code` AS `term_code`, `et`.`status` AS `status`, CASE WHEN `i20`.`i20_document_received` is not null AND `i20`.`export_controls_requested` is null THEN 'Export controls not requested' WHEN `i20`.`export_controls_requested` is not null AND `i20`.`export_controls_cleared` is null THEN 'Export controls pending clearance' WHEN `i20`.`export_controls_cleared` is not null AND `i20`.`i20_issued` is null THEN 'Ready to issue I-20' WHEN `i20`.`i20_issued` is not null AND `vi`.`sevis_fee_paid` = 0 THEN 'SEVIS fee not paid' WHEN `et`.`status` = 'Visa Pending' THEN 'Visa pending' WHEN `i20`.`i20_issued` is not null AND `oc`.`orientation_complete` is null THEN 'Orientation not completed' ELSE NULL END AS `alert_message` FROM ((((`students` `s` join `enrollment_terms` `et` on(`s`.`student_id` = `et`.`student_id`)) left join `i20_documents` `i20` on(`et`.`term_id` = `i20`.`term_id`)) left join `visa_information` `vi` on(`s`.`student_id` = `vi`.`student_id`)) left join `orientation_checklist` `oc` on(`et`.`term_id` = `oc`.`term_id`)) HAVING `alert_message` is not null ;

-- --------------------------------------------------------

--
-- Structure for view `v_dashboard_summary`
--
DROP TABLE IF EXISTS `v_dashboard_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_dashboard_summary`  AS SELECT `et`.`term_code` AS `term_code`, `s`.`level` AS `level`, count(0) AS `total`, sum(`oc`.`checked_in` is not null) AS `checked_in`, sum(`i20`.`i20_issued` is null) AS `i20_pending`, sum(`et`.`status` = 'Visa Pending') AS `visa_pending`, sum(`oc`.`orientation_complete` is not null) AS `orientation_done` FROM (((`enrollment_terms` `et` join `students` `s` on(`s`.`student_id` = `et`.`student_id`)) left join `i20_documents` `i20` on(`et`.`term_id` = `i20`.`term_id`)) left join `orientation_checklist` `oc` on(`et`.`term_id` = `oc`.`term_id`)) GROUP BY `et`.`term_code`, `s`.`level` ORDER BY `et`.`term_code` ASC, `s`.`level` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_full`
--
DROP TABLE IF EXISTS `v_student_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_full`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`full_name` AS `full_name`, `s`.`personal_email` AS `personal_email`, `s`.`university_email` AS `university_email`, `s`.`phone` AS `phone`, `s`.`country` AS `country`, `s`.`level` AS `level`, `s`.`major` AS `major`, `s`.`recruiter` AS `recruiter`, `s`.`merit` AS `merit`, `s`.`notes` AS `notes`, `s`.`last_contact` AS `last_contact`, `et`.`term_code` AS `term_code`, `et`.`status` AS `status`, `et`.`program_start_date` AS `program_start_date`, `vi`.`visa_type` AS `visa_type`, `vi`.`visa_number` AS `visa_number`, `vi`.`visa_expiration_date` AS `visa_expiration_date`, `vi`.`visa_issuance_post` AS `visa_issuance_post`, `vi`.`port_of_entry` AS `port_of_entry`, `vi`.`date_of_entry` AS `date_of_entry`, `vi`.`admit_until_date` AS `admit_until_date`, `vi`.`sevis_fee_paid` AS `sevis_fee_paid`, `i20`.`i20_number` AS `i20_number`, `i20`.`i20_document_received` AS `i20_document_received`, `i20`.`export_controls_requested` AS `export_controls_requested`, `i20`.`export_controls_cleared` AS `export_controls_cleared`, `i20`.`i20_issued` AS `i20_issued`, `oc`.`welcome_sent` AS `welcome_sent`, `oc`.`next_steps_letter_sent` AS `next_steps_letter_sent`, `oc`.`orientation_complete` AS `orientation_complete`, `oc`.`checked_in` AS `checked_in` FROM ((((`students` `s` left join `enrollment_terms` `et` on(`s`.`student_id` = `et`.`student_id`)) left join `visa_information` `vi` on(`s`.`student_id` = `vi`.`student_id`)) left join `i20_documents` `i20` on(`et`.`term_id` = `i20`.`term_id`)) left join `orientation_checklist` `oc` on(`et`.`term_id` = `oc`.`term_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `enrollment_terms`
--
ALTER TABLE `enrollment_terms`
  ADD PRIMARY KEY (`term_id`),
  ADD UNIQUE KEY `uq_student_term` (`student_id`,`term_code`);

--
-- Indexes for table `i20_documents`
--
ALTER TABLE `i20_documents`
  ADD PRIMARY KEY (`i20_id`),
  ADD UNIQUE KEY `uq_i20_term` (`term_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `orientation_checklist`
--
ALTER TABLE `orientation_checklist`
  ADD PRIMARY KEY (`checklist_id`),
  ADD UNIQUE KEY `uq_checklist` (`student_id`,`term_id`),
  ADD KEY `term_id` (`term_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `visa_information`
--
ALTER TABLE `visa_information`
  ADD PRIMARY KEY (`visa_id`),
  ADD UNIQUE KEY `uq_visa_student` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `enrollment_terms`
--
ALTER TABLE `enrollment_terms`
  MODIFY `term_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `i20_documents`
--
ALTER TABLE `i20_documents`
  MODIFY `i20_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orientation_checklist`
--
ALTER TABLE `orientation_checklist`
  MODIFY `checklist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visa_information`
--
ALTER TABLE `visa_information`
  MODIFY `visa_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `enrollment_terms`
--
ALTER TABLE `enrollment_terms`
  ADD CONSTRAINT `enrollment_terms_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `i20_documents`
--
ALTER TABLE `i20_documents`
  ADD CONSTRAINT `i20_documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `i20_documents_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `enrollment_terms` (`term_id`) ON DELETE CASCADE;

--
-- Constraints for table `orientation_checklist`
--
ALTER TABLE `orientation_checklist`
  ADD CONSTRAINT `orientation_checklist_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orientation_checklist_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `enrollment_terms` (`term_id`) ON DELETE CASCADE;

--
-- Constraints for table `visa_information`
--
ALTER TABLE `visa_information`
  ADD CONSTRAINT `visa_information_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
