-- Adminer 4.8.1 MySQL 5.5.5-10.11.6-MariaDB-log dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(16) NOT NULL COMMENT 'error,info,success',
  `code` varchar(120) NOT NULL COMMENT '代碼 作為快速搜尋判斷',
  `message` text NOT NULL COMMENT '主訊息',
  `path` varchar(512) NOT NULL COMMENT '路徑，如果非本地路徑直接帶網址',
  `category` varchar(120) NOT NULL COMMENT '分類標記',
  `created_at` datetime NOT NULL COMMENT '創建時間',
  PRIMARY KEY (`id`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='紀錄';


DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_num` int(6) unsigned NOT NULL COMMENT '學員編號（六碼）',
  `member_name` varchar(120) NOT NULL COMMENT '姓名',
  `member_area` varchar(120) NOT NULL COMMENT '分營區名稱',
  `scout_name` varchar(120) NOT NULL COMMENT '團名',
  `scout_num` varchar(120) NOT NULL COMMENT '地區＋團次',
  `member_phone` varchar(120) NOT NULL COMMENT '聯絡電話（聯絡用途，非必要）',
  `member_contact_name` varchar(120) NOT NULL COMMENT '緊急聯絡人（通常是帶隊老師名字）',
  `member_contact_phone` varchar(120) NOT NULL COMMENT '緊急聯絡電話',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_num` (`member_num`),
  KEY `member_name` (`member_name`),
  KEY `member_area` (`member_area`),
  KEY `scout_name` (`scout_name`),
  KEY `scout_num` (`scout_num`),
  KEY `member_contact_name` (`member_contact_name`),
  KEY `member_area_member_name` (`member_area`,`member_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='學員名單';


DROP TABLE IF EXISTS `sign_logs`;
CREATE TABLE `sign_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workshop_username` int(11) unsigned NOT NULL COMMENT '工作坊編號',
  `member_num` int(10) unsigned NOT NULL COMMENT '學員編號',
  `workshop_session` varchar(32) NOT NULL COMMENT '場次（日期四碼＋場次序兩碼 e.g. 070101 則為 7 月 1 號第一場）',
  `sign_in` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '簽到日期 若無預設則為 0000-00-00 00:00:00',
  `sign_out` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '簽出日期若無預設則為 0000-00-00 00:00:00',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workshop_username_member_num_workshop_session` (`workshop_username`,`member_num`,`workshop_session`),
  KEY `簽到日期_簽出日期` (`sign_in`,`sign_out`),
  KEY `workshop_username_member_num` (`workshop_username`,`member_num`),
  KEY `workshop_username` (`workshop_username`),
  KEY `member_num` (`member_num`),
  KEY `workshop_username_member_num_workshop_session_sign_in_sign_out` (`workshop_username`,`member_num`,`workshop_session`,`sign_in`,`sign_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='簽到簽出紀錄';


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID 不公開',
  `username` varchar(255) NOT NULL COMMENT '帳號',
  `user_slug` varchar(32) NOT NULL COMMENT '用戶標準化標記',
  `name` varchar(120) NOT NULL COMMENT '名字',
  `password` varchar(255) NOT NULL COMMENT '密碼',
  `permissions` longtext NOT NULL DEFAULT 'user' COMMENT '用戶權限',
  `created_at` datetime NOT NULL COMMENT '開始時間',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp() COMMENT '結束時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`username`),
  KEY `user_slug` (`user_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='用戶表';

TRUNCATE `users`;
INSERT INTO `users` (`id`, `username`, `user_slug`, `name`, `password`, `permissions`, `created_at`, `updated_at`) VALUES
(1,	'admin',	'69f2c0f896d3889fac562f48ddb0d251',	'管理員',	'$2y$10$XW6X52PiPtFxPgexc22/eOvucbXx1EuDzBmrtUtz.jOsqDPjm3sgO',	'admin',	'2023-05-04 20:31:47',	'2024-03-24 00:56:23');

DROP TABLE IF EXISTS `workshops`;
CREATE TABLE `workshops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workshop_name` varchar(120) NOT NULL COMMENT '工作坊名稱',
  `workshop_username` int(10) unsigned NOT NULL COMMENT '工作坊編號（活動中心編號 + 序號 六碼）',
  `workshop_password` varchar(120) NOT NULL COMMENT '工作坊密碼',
  `workshop_sessions` longtext NOT NULL COMMENT '工座坊場次清單日期四碼＋場次序兩碼 e.g. 070101 則為 7 月 1 號第一場',
  `workshop_area` varchar(120) NOT NULL COMMENT '所在的活動中心',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workshop_username` (`workshop_username`),
  KEY `workshop_name` (`workshop_name`),
  KEY `workshop_sessions` (`workshop_sessions`(1024)),
  KEY `workshop_name_workshop_username` (`workshop_name`,`workshop_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='工作坊';


-- 2024-07-16 12:05:18
