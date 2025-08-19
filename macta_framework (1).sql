-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 19, 2025 at 12:32 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `macta_framework`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `feedback_text` text COLLATE utf8mb4_general_ci,
  `satisfaction_score` int DEFAULT NULL,
  `feedback_date` date DEFAULT NULL,
  `status` enum('pending','reviewed','resolved') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `job_descriptions`
--

CREATE TABLE `job_descriptions` (
  `id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `requirements` text COLLATE utf8mb4_general_ci,
  `performance_metrics` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metrics`
--

CREATE TABLE `metrics` (
  `id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `metric_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `measurement_date` date DEFAULT NULL,
  `category` enum('response_time','accuracy','efficiency','quality','satisfaction') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `process_models`
--

CREATE TABLE `process_models` (
  `id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `model_data` longtext COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `process_step_resources`
--

CREATE TABLE `process_step_resources` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `step_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `resource_id` int NOT NULL,
  `quantity_required` decimal(8,2) DEFAULT '1.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `client_id` int DEFAULT NULL,
  `status` enum('active','inactive','completed') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('human','equipment','material','software','facility','financial','information','other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'other',
  `description` text COLLATE utf8mb4_general_ci,
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `availability_status` enum('available','unavailable','maintenance','reserved') COLLATE utf8mb4_general_ci DEFAULT 'available',
  `specifications` text COLLATE utf8mb4_general_ci,
  `contact_info` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simulation_configs`
--

CREATE TABLE `simulation_configs` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `config_data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simulation_metrics`
--

CREATE TABLE `simulation_metrics` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `simulation_result_id` int NOT NULL,
  `metric_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `metric_value` decimal(15,4) NOT NULL,
  `metric_unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `scenario_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simulation_resources`
--

CREATE TABLE `simulation_resources` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('human','equipment','software','material') COLLATE utf8mb4_general_ci NOT NULL,
  `hourly_rate` decimal(10,2) DEFAULT '0.00',
  `availability_hours` decimal(5,2) DEFAULT '8.00',
  `skill_level` enum('beginner','intermediate','expert','specialist') COLLATE utf8mb4_general_ci DEFAULT 'intermediate',
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simulation_results`
--

CREATE TABLE `simulation_results` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `scenario_data` json NOT NULL,
  `results_data` json NOT NULL,
  `iterations` int DEFAULT '100',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simulation_templates`
--

CREATE TABLE `simulation_templates` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `industry` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `template_data` json NOT NULL,
  `is_public` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_programs`
--

CREATE TABLE `training_programs` (
  `id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `modules` json DEFAULT NULL,
  `status` enum('draft','active','completed') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user','client') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `job_descriptions`
--
ALTER TABLE `job_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `metrics`
--
ALTER TABLE `metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `process_models`
--
ALTER TABLE `process_models`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `process_step_resources`
--
ALTER TABLE `process_step_resources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_step_resource` (`process_id`,`step_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `idx_step_resources_process` (`process_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`availability_status`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `simulation_configs`
--
ALTER TABLE `simulation_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_process_config` (`process_id`),
  ADD KEY `idx_simulation_configs_process` (`process_id`);

--
-- Indexes for table `simulation_metrics`
--
ALTER TABLE `simulation_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `simulation_result_id` (`simulation_result_id`),
  ADD KEY `idx_metrics_lookup` (`process_id`,`metric_name`,`scenario_name`);

--
-- Indexes for table `simulation_resources`
--
ALTER TABLE `simulation_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resources_type_skill` (`type`,`skill_level`);

--
-- Indexes for table `simulation_results`
--
ALTER TABLE `simulation_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_process_created` (`process_id`,`created_at`),
  ADD KEY `idx_simulation_results_process_date` (`process_id`,`created_at` DESC);

--
-- Indexes for table `simulation_templates`
--
ALTER TABLE `simulation_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_templates_industry_public` (`industry`,`is_public`);

--
-- Indexes for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_descriptions`
--
ALTER TABLE `job_descriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `metrics`
--
ALTER TABLE `metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_models`
--
ALTER TABLE `process_models`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_step_resources`
--
ALTER TABLE `process_step_resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `simulation_configs`
--
ALTER TABLE `simulation_configs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `simulation_metrics`
--
ALTER TABLE `simulation_metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `simulation_resources`
--
ALTER TABLE `simulation_resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `simulation_results`
--
ALTER TABLE `simulation_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `simulation_templates`
--
ALTER TABLE `simulation_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_programs`
--
ALTER TABLE `training_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `customer_feedback_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Constraints for table `job_descriptions`
--
ALTER TABLE `job_descriptions`
  ADD CONSTRAINT `job_descriptions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Constraints for table `metrics`
--
ALTER TABLE `metrics`
  ADD CONSTRAINT `metrics_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Constraints for table `process_models`
--
ALTER TABLE `process_models`
  ADD CONSTRAINT `process_models_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Constraints for table `process_step_resources`
--
ALTER TABLE `process_step_resources`
  ADD CONSTRAINT `process_step_resources_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `process_step_resources_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `simulation_resources` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `simulation_configs`
--
ALTER TABLE `simulation_configs`
  ADD CONSTRAINT `simulation_configs_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `simulation_metrics`
--
ALTER TABLE `simulation_metrics`
  ADD CONSTRAINT `simulation_metrics_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `simulation_metrics_ibfk_2` FOREIGN KEY (`simulation_result_id`) REFERENCES `simulation_results` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `simulation_results`
--
ALTER TABLE `simulation_results`
  ADD CONSTRAINT `simulation_results_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `simulation_templates`
--
ALTER TABLE `simulation_templates`
  ADD CONSTRAINT `simulation_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD CONSTRAINT `training_programs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
