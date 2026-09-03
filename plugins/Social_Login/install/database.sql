CREATE TABLE IF NOT EXISTS `social_login_settings` (
  `setting_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `setting_value` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'app',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; #

INSERT INTO `social_login_settings` (`setting_name`, `setting_value`, `deleted`) VALUES ('social_login_item_purchase_code', 'Social_Login-ITEM-PURCHASE-CODE', 0); #
