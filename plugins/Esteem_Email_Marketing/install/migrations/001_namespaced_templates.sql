-- Required because rise_email_templates is a core CRM table with a different schema.
CREATE TABLE IF NOT EXISTS `rise_email_marketing_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `html_content` mediumtext NOT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `template_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
