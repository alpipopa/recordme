-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 24, 2026 at 11:23 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `contact`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` enum('create','update','delete','login','logout','export') COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `record_id` int UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل التعديلات والأنشطة';

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 22:15:20'),
(2, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 22:33:00'),
(3, 1, 'update', 'settings', NULL, '{\"logo_path\": \"\", \"site_name\": \"نظام تسجيل بيانات الأشخاص\", \"print_footer\": \"نظام تسجيل البيانات - سري\", \"print_header\": \"الجمهورية اليمنية\", \"site_subtitle\": \"لوحة التحكم الإدارية\", \"items_per_page\": \"25\"}', '[{\"id\": 1, \"updated_at\": \"2026-02-24 22:35:00\", \"setting_key\": \"site_name\", \"setting_value\": \"سجلني\"}, {\"id\": 2, \"updated_at\": \"2026-02-24 21:24:04\", \"setting_key\": \"site_subtitle\", \"setting_value\": \"لوحة التحكم الإدارية\"}, {\"id\": 3, \"updated_at\": \"2026-02-24 22:35:00\", \"setting_key\": \"print_header\", \"setting_value\": \"الجمهورية اليمنية                                                                                                                                                                                                                                                                                                          التاريخ:\"}, {\"id\": 4, \"updated_at\": \"2026-02-24 22:35:00\", \"setting_key\": \"print_footer\", \"setting_value\": \"نظام تسجيل البيانات\"}, {\"id\": 5, \"updated_at\": \"2026-02-24 21:24:04\", \"setting_key\": \"items_per_page\", \"setting_value\": \"25\"}, {\"id\": 6, \"updated_at\": \"2026-02-24 21:24:04\", \"setting_key\": \"logo_path\", \"setting_value\": \"\"}]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 22:35:00'),
(4, 1, 'update', 'settings', NULL, '{\"logo_path\": \"\", \"site_name\": \"سجلني\", \"print_footer\": \"نظام تسجيل البيانات\", \"print_header\": \"الجمهورية اليمنية                                                                                                                                                                                                                                                                                                          التاريخ:\", \"site_subtitle\": \"لوحة التحكم الإدارية\", \"items_per_page\": \"25\"}', '[{\"id\": 1, \"updated_at\": \"2026-02-24 22:35:00\", \"setting_key\": \"site_name\", \"setting_value\": \"سجلني\"}, {\"id\": 2, \"updated_at\": \"2026-02-24 21:24:04\", \"setting_key\": \"site_subtitle\", \"setting_value\": \"لوحة التحكم الإدارية\"}, {\"id\": 3, \"updated_at\": \"2026-02-24 22:35:00\", \"setting_key\": \"print_header\", \"setting_value\": \"الجمهورية اليمنية                                                                                                                                                                                                                                                                                                          التاريخ:\"}, {\"id\": 4, \"updated_at\": \"2026-02-24 22:35:00\", \"setting_key\": \"print_footer\", \"setting_value\": \"نظام تسجيل البيانات\"}, {\"id\": 5, \"updated_at\": \"2026-02-24 21:24:04\", \"setting_key\": \"items_per_page\", \"setting_value\": \"25\"}, {\"id\": 6, \"updated_at\": \"2026-02-24 22:39:06\", \"setting_key\": \"logo_path\", \"setting_value\": \"logo_1771961946.png\"}]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 22:39:06'),
(5, 1, 'update', 'users', 1, '{\"id\": 1, \"role\": \"admin\", \"email\": \"admin@admin.com\", \"avatar\": null, \"password\": \"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\", \"username\": \"admin\", \"full_name\": \"مدير النظام\", \"is_active\": 1, \"created_at\": \"2026-02-24 21:24:04\", \"last_login\": \"2026-02-24 22:33:00\"}', '{\"id\": 1, \"role\": \"admin\", \"email\": \"admin@admin.com\", \"avatar\": \"user_1_1771962843.png\", \"password\": \"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\", \"username\": \"admin\", \"full_name\": \"مدير النظام\", \"is_active\": 1, \"created_at\": \"2026-02-24 21:24:04\", \"last_login\": \"2026-02-24 22:33:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 22:54:03'),
(6, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 01:28:08'),
(7, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 01:28:22'),
(8, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 01:30:39');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6c757d',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول تصنيفات الأشخاص';

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `color`, `sort_order`, `created_at`) VALUES
(1, 'شخص عادي', '#198754', 1, '2026-02-24 21:24:04'),
(2, 'عامل', '#0d6efd', 2, '2026-02-24 21:24:04'),
(3, 'موظف', '#6f42c1', 3, '2026-02-24 21:24:04'),
(4, 'مورد', '#fd7e14', 4, '2026-02-24 21:24:04'),
(5, 'أخرى', '#6c757d', 5, '2026-02-24 21:24:04');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`) VALUES
(1, 'اليمن');

-- --------------------------------------------------------

--
-- Table structure for table `custom_fields`
--

CREATE TABLE `custom_fields` (
  `id` int UNSIGNED NOT NULL,
  `field_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'المعرف البرمجي',
  `field_label` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'التسمية للعرض',
  `field_type` enum('text','number','date','select','textarea','checkbox','email','phone') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `field_options` text COLLATE utf8mb4_unicode_ci COMMENT 'خيارات قائمة الاختيار (JSON)',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول الحقول المخصصة الديناميكية';

-- --------------------------------------------------------

--
-- Table structure for table `custom_field_values`
--

CREATE TABLE `custom_field_values` (
  `id` int UNSIGNED NOT NULL,
  `person_id` int UNSIGNED NOT NULL,
  `field_id` int UNSIGNED NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='قيم الحقول المخصصة لكل شخص';

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int UNSIGNED NOT NULL,
  `governorate_id` int UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `governorates`
--

CREATE TABLE `governorates` (
  `id` int UNSIGNED NOT NULL,
  `country_id` int UNSIGNED DEFAULT '1',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `governorates`
--

INSERT INTO `governorates` (`id`, `country_id`, `name`) VALUES
(1, 1, 'أمانة العاصمة'),
(2, 1, 'صنعاء'),
(3, 1, 'عدن'),
(4, 1, 'تعز'),
(5, 1, 'الحديدة'),
(6, 1, 'إب'),
(7, 1, 'ذمار'),
(8, 1, 'حضرموت'),
(9, 1, 'مأرب'),
(10, 1, 'البيضاء'),
(11, 1, 'شبوة'),
(12, 1, 'أبين'),
(13, 1, 'لحج'),
(14, 1, 'الضالع'),
(15, 1, 'ريمة'),
(16, 1, 'المحويت'),
(17, 1, 'صعدة'),
(18, 1, 'حجة'),
(19, 1, 'عمران'),
(20, 1, 'الجوف'),
(21, 1, 'المهرة'),
(22, 1, 'سقطرى'),
(23, 1, 'حضرموت الوادي');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL COMMENT 'المستخدم المستهدف',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','success','warning','danger') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE `persons` (
  `id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED NOT NULL DEFAULT '1',
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_type` enum('national_id','passport','driving_license','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'national_id',
  `id_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone2` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `id_image` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `personal_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `children_total` int NOT NULL DEFAULT '0',
  `children_male` int NOT NULL DEFAULT '0',
  `children_female` int NOT NULL DEFAULT '0',
  `job_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residence` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residence_type` enum('owned','rented') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `governorate` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `chronic_diseases` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `updated_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول بيانات الأشخاص';

-- --------------------------------------------------------

--
-- Table structure for table `person_documents`
--

CREATE TABLE `person_documents` (
  `id` int UNSIGNED NOT NULL,
  `person_id` int UNSIGNED NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int UNSIGNED DEFAULT NULL,
  `uploaded_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int UNSIGNED NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'سجلني', '2026-02-24 22:35:00'),
(2, 'site_subtitle', 'لوحة التحكم الإدارية', '2026-02-24 21:24:04'),
(3, 'print_header', 'الجمهورية اليمنية                                                                                                                                                                                                                                                                                                          التاريخ:', '2026-02-24 22:35:00'),
(4, 'print_footer', 'نظام تسجيل البيانات', '2026-02-24 22:35:00'),
(5, 'items_per_page', '25', '2026-02-24 21:24:04'),
(6, 'logo_path', 'logo_1771961946.png', '2026-02-24 22:39:06');

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint NOT NULL DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `title`, `description`, `image`, `link_url`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'حفظ البيانات بأمان واحترافية', 'نظام متكامل لحفظ وإدارة بيانات الأشخاص بأعلى معايير الأمان والخصوصية. أدخل البيانات مرة واسترجعها في أي وقت.', NULL, '', 1, 'active', '2026-02-24 21:24:21', '2026-02-24 22:43:17'),
(2, 'نظام إدارة الموظفين والفئات', 'تصنيف الأشخاص ضمن فئات مرنة مع حقول مخصصة ديناميكية تتكيف مع احتياجاتك الإدارية.', NULL, NULL, 2, 'active', '2026-02-24 21:24:21', '2026-02-24 21:24:21'),
(3, 'تقارير وطباعة احترافية', 'أنشئ تقارير مفصلة وطابعها بضغطة واحدة، أو صدّرها بصيغة Excel لمزيد من المعالجة.', NULL, NULL, 3, 'active', '2026-02-24 21:24:21', '2026-02-24 21:24:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','manager','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول المستخدمين';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `avatar`, `role`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin@admin.com', 'user_1_1771962843.png', 'admin', 1, '2026-02-25 01:30:39', '2026-02-24 21:24:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_fields`
--
ALTER TABLE `custom_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `field_name` (`field_name`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `custom_field_values`
--
ALTER TABLE `custom_field_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_person_field` (`person_id`,`field_id`),
  ADD KEY `idx_person` (`person_id`),
  ADD KEY `idx_field` (`field_id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gov` (`governorate_id`);

--
-- Indexes for table `governorates`
--
ALTER TABLE `governorates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_name` (`full_name`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_governorate` (`governorate`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `person_documents`
--
ALTER TABLE `person_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_person_docs` (`person_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_order` (`status`,`sort_order`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `custom_fields`
--
ALTER TABLE `custom_fields`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_field_values`
--
ALTER TABLE `custom_field_values`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `governorates`
--
ALTER TABLE `governorates`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `persons`
--
ALTER TABLE `persons`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `person_documents`
--
ALTER TABLE `person_documents`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `custom_field_values`
--
ALTER TABLE `custom_field_values`
  ADD CONSTRAINT `fk_cfv_field` FOREIGN KEY (`field_id`) REFERENCES `custom_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cfv_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `persons`
--
ALTER TABLE `persons`
  ADD CONSTRAINT `fk_persons_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `person_documents`
--
ALTER TABLE `person_documents`
  ADD CONSTRAINT `fk_docs_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
