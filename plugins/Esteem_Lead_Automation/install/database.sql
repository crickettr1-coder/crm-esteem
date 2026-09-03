CREATE TABLE IF NOT EXISTS `lead_automation_settings` (
  `setting_name` varchar(100) NOT NULL,
  `setting_value` mediumtext NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'app',
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;#

INSERT INTO `lead_automation_settings` (`setting_name`, `setting_value`, `deleted`) VALUES
('lead_automation_enabled', '0', 0),
('lead_automation_delay_minutes', '15', 0),
('lead_automation_task_title', 'Follow up new lead: {Lead Name}', 0)
ON DUPLICATE KEY UPDATE `setting_name` = VALUES(`setting_name`);#

CREATE TABLE IF NOT EXISTS `lead_automation_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL DEFAULT 0,
  `trigger_type` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `status` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_trigger` (`lead_id`, `trigger_type`),
  KEY `lead_id` (`lead_id`),
  KEY `task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;#

