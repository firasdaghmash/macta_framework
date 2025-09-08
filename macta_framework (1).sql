-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 06, 2025 at 10:22 PM
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
-- Table structure for table `dashboard_metrics_cache`
--

CREATE TABLE `dashboard_metrics_cache` (
  `id` int NOT NULL,
  `metric_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `metric_value` decimal(15,2) NOT NULL,
  `process_id` int DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enhanced_resources`
--

CREATE TABLE `enhanced_resources` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('human','machine','hybrid','software') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'human',
  `hourly_cost` decimal(10,2) DEFAULT '0.00',
  `skill_level` enum('entry','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'intermediate',
  `availability` decimal(5,2) DEFAULT '100.00' COMMENT 'Percentage availability',
  `efficiency_factor` decimal(3,2) DEFAULT '1.00' COMMENT 'Efficiency multiplier',
  `max_concurrent_tasks` int DEFAULT '1',
  `department` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `optimization_recommendations`
--

CREATE TABLE `optimization_recommendations` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `recommendation_type` enum('automation','parallel_processing','skill_routing','resource_reallocation','process_redesign') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expected_impact` json NOT NULL COMMENT 'Expected improvements in time, cost, quality',
  `implementation_effort` enum('low','medium','high') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `investment_required` decimal(10,2) DEFAULT '0.00',
  `payback_period_months` int DEFAULT NULL,
  `priority_score` int DEFAULT '0' COMMENT 'Priority score 0-100',
  `status` enum('suggested','approved','in_progress','completed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'suggested',
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `process_arrival_configs`
--

CREATE TABLE `process_arrival_configs` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `arrival_rate_per_hour` decimal(6,2) NOT NULL,
  `arrival_pattern` enum('regular','random','burst','scheduled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'regular',
  `process_type` enum('standard','priority','batch','adhoc') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'standard',
  `complexity_distribution` json DEFAULT NULL COMMENT 'Distribution of complexity levels',
  `seasonal_factors` json DEFAULT NULL COMMENT 'Seasonal arrival rate adjustments',
  `active_hours_start` time DEFAULT '09:00:00',
  `active_hours_end` time DEFAULT '17:00:00',
  `weekend_factor` decimal(3,2) DEFAULT '0.20' COMMENT 'Weekend activity factor',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `process_deployments`
--

CREATE TABLE `process_deployments` (
  `id` int NOT NULL,
  `process_model_id` int NOT NULL,
  `deployment_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `flowable_process_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('deployed','undeployed','failed') COLLATE utf8mb4_general_ci DEFAULT 'deployed',
  `deployed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `process_instances`
--

CREATE TABLE `process_instances` (
  `id` int NOT NULL,
  `process_model_id` int NOT NULL,
  `instance_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `flowable_instance_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('running','completed','suspended','terminated') COLLATE utf8mb4_general_ci DEFAULT 'running',
  `start_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` timestamp NULL DEFAULT NULL,
  `variables` json DEFAULT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'system'
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
-- Table structure for table `process_path_analysis`
--

CREATE TABLE `process_path_analysis` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `analysis_type` enum('critical','time_consuming','resource_intensive','costly','ideal','frequent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `path_data` json NOT NULL COMMENT 'Stores path steps and metrics',
  `total_duration` int NOT NULL COMMENT 'Total duration in minutes',
  `total_cost` decimal(10,2) NOT NULL,
  `total_resources` int NOT NULL,
  `frequency_percentage` decimal(5,2) DEFAULT NULL COMMENT 'How often this path is taken',
  `bottleneck_tasks` json DEFAULT NULL COMMENT 'Array of bottleneck task IDs',
  `optimization_suggestions` json DEFAULT NULL,
  `analysis_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
-- Table structure for table `process_tasks`
--

CREATE TABLE `process_tasks` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `task_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `task_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `position_x` float DEFAULT NULL,
  `position_y` float DEFAULT NULL,
  `task_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
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
-- Table structure for table `resource_allocations`
--

CREATE TABLE `resource_allocations` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `task_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `allocation_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resource_type` enum('human','machine','both') COLLATE utf8mb4_general_ci DEFAULT 'human',
  `cost` decimal(10,2) DEFAULT '0.00',
  `processing_time` int DEFAULT '0' COMMENT 'in minutes',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource_templates`
--

CREATE TABLE `resource_templates` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `resource_type` enum('human','machine','hybrid','software') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `default_duration` int NOT NULL COMMENT 'Default duration in minutes',
  `default_cost` decimal(10,2) NOT NULL,
  `default_skill_level` enum('entry','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `default_complexity` enum('simple','moderate','complex','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_public` tinyint(1) DEFAULT '1',
  `industry` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simple_timers`
--

CREATE TABLE `simple_timers` (
  `id` int NOT NULL,
  `process_id` int DEFAULT NULL,
  `task_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
-- Table structure for table `task_resource_assignments`
--

CREATE TABLE `task_resource_assignments` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `task_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `resource_id` int NOT NULL,
  `quantity_required` decimal(8,2) DEFAULT '1.00',
  `duration_minutes` int NOT NULL,
  `complexity_level` enum('simple','moderate','complex','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'moderate',
  `priority_level` enum('low','normal','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'normal',
  `setup_time` int DEFAULT '0' COMMENT 'Setup time in minutes',
  `cleanup_time` int DEFAULT '0' COMMENT 'Cleanup time in minutes',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timer_averages`
--

CREATE TABLE `timer_averages` (
  `id` int NOT NULL,
  `process_id` int NOT NULL,
  `task_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `average_duration` int NOT NULL COMMENT 'in seconds',
  `session_count` int DEFAULT '1',
  `last_calculated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_overridden` tinyint(1) DEFAULT '0',
  `override_value` int DEFAULT NULL COMMENT 'manual override in seconds'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timer_sessions`
--

CREATE TABLE `timer_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `process_id` int NOT NULL,
  `task_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `pause_duration` int DEFAULT '0' COMMENT 'total pause time in seconds',
  `total_duration` int DEFAULT '0' COMMENT 'total working time in seconds',
  `status` enum('active','paused','completed') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `notes` text COLLATE utf8mb4_general_ci,
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
-- Indexes for table `dashboard_metrics_cache`
--
ALTER TABLE `dashboard_metrics_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_metric_process` (`metric_name`,`process_id`);

--
-- Indexes for table `enhanced_resources`
--
ALTER TABLE `enhanced_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_skill` (`type`,`skill_level`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_enhanced_resources_type_skill` (`type`,`skill_level`);

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
-- Indexes for table `optimization_recommendations`
--
ALTER TABLE `optimization_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_process_priority` (`process_id`,`priority_score`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `process_arrival_configs`
--
ALTER TABLE `process_arrival_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_process` (`process_id`);

--
-- Indexes for table `process_deployments`
--
ALTER TABLE `process_deployments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `process_model_id` (`process_model_id`);

--
-- Indexes for table `process_instances`
--
ALTER TABLE `process_instances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `process_model_id` (`process_model_id`);

--
-- Indexes for table `process_models`
--
ALTER TABLE `process_models`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `process_path_analysis`
--
ALTER TABLE `process_path_analysis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_process_type` (`process_id`,`analysis_type`),
  ADD KEY `idx_analysis_date` (`analysis_date`),
  ADD KEY `idx_path_analysis_type` (`analysis_type`);

--
-- Indexes for table `process_step_resources`
--
ALTER TABLE `process_step_resources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_step_resource` (`process_id`,`step_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `idx_step_resources_process` (`process_id`);

--
-- Indexes for table `process_tasks`
--
ALTER TABLE `process_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_task_per_process` (`process_id`,`task_id`),
  ADD KEY `idx_task_type` (`task_type`);

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
-- Indexes for table `resource_allocations`
--
ALTER TABLE `resource_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_process_task` (`process_id`,`task_id`),
  ADD KEY `idx_resource_type` (`resource_type`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `resource_templates`
--
ALTER TABLE `resource_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_industry` (`industry`);

--
-- Indexes for table `simple_timers`
--
ALTER TABLE `simple_timers`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `task_resource_assignments`
--
ALTER TABLE `task_resource_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_process_task` (`process_id`,`task_id`),
  ADD KEY `idx_resource` (`resource_id`),
  ADD KEY `idx_task_resource_process` (`process_id`),
  ADD KEY `idx_task_resource_complexity` (`complexity_level`);

--
-- Indexes for table `timer_averages`
--
ALTER TABLE `timer_averages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_average_per_task` (`process_id`,`task_id`),
  ADD KEY `idx_last_calculated` (`last_calculated`);

--
-- Indexes for table `timer_sessions`
--
ALTER TABLE `timer_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_process_task` (`process_id`,`task_id`),
  ADD KEY `idx_start_time` (`start_time`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`);

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
-- AUTO_INCREMENT for table `dashboard_metrics_cache`
--
ALTER TABLE `dashboard_metrics_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enhanced_resources`
--
ALTER TABLE `enhanced_resources`
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
-- AUTO_INCREMENT for table `optimization_recommendations`
--
ALTER TABLE `optimization_recommendations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_arrival_configs`
--
ALTER TABLE `process_arrival_configs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_deployments`
--
ALTER TABLE `process_deployments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_instances`
--
ALTER TABLE `process_instances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_models`
--
ALTER TABLE `process_models`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_path_analysis`
--
ALTER TABLE `process_path_analysis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_step_resources`
--
ALTER TABLE `process_step_resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `process_tasks`
--
ALTER TABLE `process_tasks`
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
-- AUTO_INCREMENT for table `resource_allocations`
--
ALTER TABLE `resource_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_templates`
--
ALTER TABLE `resource_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `simple_timers`
--
ALTER TABLE `simple_timers`
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
-- AUTO_INCREMENT for table `task_resource_assignments`
--
ALTER TABLE `task_resource_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timer_averages`
--
ALTER TABLE `timer_averages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timer_sessions`
--
ALTER TABLE `timer_sessions`
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
-- Constraints for table `process_deployments`
--
ALTER TABLE `process_deployments`
  ADD CONSTRAINT `process_deployments_ibfk_1` FOREIGN KEY (`process_model_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `process_instances`
--
ALTER TABLE `process_instances`
  ADD CONSTRAINT `process_instances_ibfk_1` FOREIGN KEY (`process_model_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `process_tasks`
--
ALTER TABLE `process_tasks`
  ADD CONSTRAINT `process_tasks_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `resource_allocations`
--
ALTER TABLE `resource_allocations`
  ADD CONSTRAINT `resource_allocations_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_allocations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `task_resource_assignments`
--
ALTER TABLE `task_resource_assignments`
  ADD CONSTRAINT `task_resource_assignments_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `enhanced_resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_resource_assignments_ibfk_2` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timer_averages`
--
ALTER TABLE `timer_averages`
  ADD CONSTRAINT `timer_averages_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timer_sessions`
--
ALTER TABLE `timer_sessions`
  ADD CONSTRAINT `timer_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timer_sessions_ibfk_2` FOREIGN KEY (`process_id`) REFERENCES `process_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD CONSTRAINT `training_programs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
