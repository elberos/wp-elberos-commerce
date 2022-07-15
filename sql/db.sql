-- Adminer 4.8.1 MySQL 5.5.5-10.8.2-MariaDB-1:10.8.2+maria~focal dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `wp1_elberos_clients`;
CREATE TABLE `wp1_elberos_clients` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` tinyint(4) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_bin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `search_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recovery_password_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recovery_password_expire` datetime DEFAULT NULL,
  `gmtime_add` datetime NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_catalogs`;
CREATE TABLE `wp1_elberos_commerce_catalogs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classifier_id` bigint(20) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classifier_id` (`classifier_id`),
  KEY `code_1c` (`code_1c`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_categories`;
CREATE TABLE `wp1_elberos_commerce_categories` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `classifier_id` bigint(20) NOT NULL,
  `parent_category_id` bigint(20) NOT NULL,
  `show_in_catalog` tinyint(4) NOT NULL,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `xml` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_id` bigint(20) NOT NULL,
  `image_file_path` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_id` bigint(20) NOT NULL,
  `icon_file_path` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmtime_1c_change` datetime NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code_1c` (`code_1c`),
  KEY `classifier_id` (`classifier_id`),
  KEY `show_in_catalog` (`show_in_catalog`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_classifiers`;
CREATE TABLE `wp1_elberos_commerce_classifiers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code_1c` (`code_1c`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_invoice`;
CREATE TABLE `wp1_elberos_commerce_invoice` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` bigint(20) DEFAULT NULL,
  `client_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `basket_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `utm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `export_status` tinyint(4) NOT NULL COMMENT '0-не экспортировано,1-экспортировано',
  `status` tinyint(4) NOT NULL,
  `status_pay` tinyint(4) NOT NULL,
  `method_pay` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method_pay_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmtime_add` datetime NOT NULL,
  `gmtime_pay` datetime DEFAULT NULL,
  `gmtime_change` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_params`;
CREATE TABLE `wp1_elberos_commerce_params` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `classifier_id` bigint(20) NOT NULL,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `xml` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  `gmtime_1c_change` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classifier_id` (`classifier_id`),
  KEY `code_1c` (`code_1c`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_params_values`;
CREATE TABLE `wp1_elberos_commerce_params_values` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `param_id` bigint(20) NOT NULL,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `xml` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  `gmtime_1c_change` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `param_id` (`param_id`),
  KEY `code_1c` (`code_1c`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_price_types`;
CREATE TABLE `wp1_elberos_commerce_price_types` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `classifier_id` bigint(20) NOT NULL,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pos` int(11) NOT NULL,
  `xml` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmtime_1c_change` datetime NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classifier_id` (`classifier_id`),
  KEY `code_1c` (`code_1c`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products`;
CREATE TABLE `wp1_elberos_commerce_products` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `catalog_id` bigint(20) NOT NULL,
  `main_photo_id` bigint(20) DEFAULT NULL,
  `main_category_id` bigint(20) DEFAULT NULL,
  `show_in_catalog` tinyint(4) NOT NULL COMMENT '0-не показывать, 1-показывать',
  `show_in_top` tinyint(4) NOT NULL COMMENT '1-показывать на главной',
  `main_page_pos` bigint(20) NOT NULL,
  `just_show_in_catalog` tinyint(4) NOT NULL COMMENT 'На какой статус поменять после импорта 1C',
  `price_default` double NOT NULL,
  `default_unit` bigint(20) DEFAULT NULL,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kod` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `xml` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmtime_1c_change` datetime NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code_1c` (`code_1c`),
  KEY `catalog_id` (`catalog_id`),
  KEY `vendor_code` (`vendor_code`),
  KEY `show_in_catalog_catalog_id` (`show_in_catalog`,`catalog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products_categories`;
CREATE TABLE `wp1_elberos_commerce_products_categories` (
  `product_id` bigint(20) NOT NULL,
  `category_id` bigint(20) NOT NULL,
  UNIQUE KEY `category_id_product_id` (`category_id`,`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products_offers`;
CREATE TABLE `wp1_elberos_commerce_products_offers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `offer_params` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` bigint(20) NOT NULL,
  `in_stock` tinyint(4) NOT NULL,
  `xml` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `prepare_delete` tinyint(4) NOT NULL,
  `gmtime_1c_change` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code_1c` (`code_1c`),
  KEY `product_id` (`product_id`),
  KEY `prepare_delete` (`prepare_delete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products_offers_prices`;
CREATE TABLE `wp1_elberos_commerce_products_offers_prices` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) NOT NULL,
  `price_type_id` bigint(20) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double NOT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KZT',
  `coefficient` double NOT NULL DEFAULT 1,
  `unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prepare_delete` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `offer_id_price_type_id` (`offer_id`,`price_type_id`),
  KEY `price_type_id` (`price_type_id`),
  KEY `prepare_delete` (`prepare_delete`),
  KEY `price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products_params`;
CREATE TABLE `wp1_elberos_commerce_products_params` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'props, params',
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `param_id` bigint(20) DEFAULT NULL,
  `param_code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `param_value_id` bigint(20) DEFAULT NULL,
  `param_value_code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prepare_delete` tinyint(4) NOT NULL COMMENT 'Удаление при обновлении',
  PRIMARY KEY (`id`),
  KEY `param_id_param_value_id` (`param_id`,`param_value_id`),
  KEY `key` (`key`),
  KEY `product_id_type_key` (`product_id`,`type`,`key`),
  KEY `prepare_delete` (`prepare_delete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products_photos`;
CREATE TABLE `wp1_elberos_commerce_products_photos` (
  `product_id` bigint(20) NOT NULL,
  `photo_id` bigint(20) NOT NULL,
  `pos` bigint(20) NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  UNIQUE KEY `product_id_photo_id` (`product_id`,`photo_id`),
  KEY `photo_id` (`photo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products_relative`;
CREATE TABLE `wp1_elberos_commerce_products_relative` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) NOT NULL,
  `relative_id` bigint(20) NOT NULL,
  `pos` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `wp1_elberos_commerce_products_text`;
CREATE TABLE `wp1_elberos_commerce_products_text` (
  `id` bigint(20) NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wp1_elberos_commerce_warehouses`;
CREATE TABLE `wp1_elberos_commerce_warehouses` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `classifier_id` bigint(20) NOT NULL,
  `code_1c` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `xml` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmtime_1c_change` datetime NOT NULL,
  `is_deleted` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classifier_id` (`classifier_id`),
  KEY `code_1c` (`code_1c`),
  CONSTRAINT `wp1_elberos_commerce_warehouses_ibfk_1` FOREIGN KEY (`classifier_id`) REFERENCES `old_wp1_elberos_commerce_classifiers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2022-07-10 17:08:08