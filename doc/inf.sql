-- --------------------------------------------------------
-- 主机:                           192.168.6.1
-- 服务器版本:                        5.6.26-log - MySQL Community Server (GPL)
-- 服务器操作系统:                      Linux
-- HeidiSQL 版本:                  9.3.0.4998
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 导出 inf 的数据库结构
CREATE DATABASE IF NOT EXISTS `inf` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `inf`;


-- 导出  表 inf.album 结构
CREATE TABLE IF NOT EXISTS `album` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `folder` varchar(8) NOT NULL,
  `description` varchar(255) NOT NULL,
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folder` (`folder`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 数据导出被取消选择。


-- 导出  表 inf.article 结构
CREATE TABLE IF NOT EXISTS `article` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '唯一值',
  `division_id` mediumint(8) unsigned NOT NULL COMMENT '所属事业部',
  `category_id` mediumint(8) unsigned DEFAULT NULL COMMENT '分类',
  `division_category_id` mediumint(8) unsigned NOT NULL COMMENT '事业部分类',
  `title` varchar(255) NOT NULL COMMENT '页面标题',
  `content` text NOT NULL COMMENT '内容',
  `author` varchar(50) DEFAULT NULL COMMENT '作者',
  `keyword` varchar(255) DEFAULT NULL COMMENT '关键字SEO',
  `description` varchar(255) DEFAULT NULL COMMENT '描述SEO',
  `image` varchar(100) DEFAULT NULL COMMENT '图片路径',
  `language` enum('cn','tw','en') NOT NULL DEFAULT 'cn' COMMENT '语言',
  `source` varchar(50) DEFAULT NULL COMMENT '来源',
  `share` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '分享',
  `attribute` mediumtext COMMENT '扩展属性',
  `visibility` enum('Visible','Hidden') NOT NULL DEFAULT 'Hidden' COMMENT '可见性',
  `status` enum('Enabled','Disabled','Deleted') NOT NULL DEFAULT 'Disabled' COMMENT '状态',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '编辑时间',
  PRIMARY KEY (`id`),
  KEY `FK_article_category` (`category_id`),
  KEY `ctime` (`ctime`),
  KEY `division_category_id` (`division_category_id`),
  KEY `division_id` (`division_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='内容'
/*!50100 PARTITION BY KEY (id)
PARTITIONS 16 */;

-- 数据导出被取消选择。


-- 导出  表 inf.category 结构
CREATE TABLE IF NOT EXISTS `category` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `division_id` mediumint(8) unsigned NOT NULL COMMENT '分类所属事业部',
  `name` varchar(20) NOT NULL COMMENT '分类名称',
  `description` varchar(255) DEFAULT NULL COMMENT '分类表述',
  `language` enum('en','cn','tw') NOT NULL DEFAULT 'cn',
  `visibility` enum('Visible','Hidden') NOT NULL DEFAULT 'Hidden' COMMENT '可见性',
  `status` enum('Enabled','Disabled') NOT NULL DEFAULT 'Disabled' COMMENT '分类状态',
  `parent_id` mediumint(8) unsigned DEFAULT NULL COMMENT '父节点',
  `path` varchar(255) NOT NULL DEFAULT '/' COMMENT '路径',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `path` (`path`),
  KEY `FK_category_division` (`division_id`),
  KEY `FK_category_category` (`parent_id`),
  CONSTRAINT `FK_category_category` FOREIGN KEY (`parent_id`) REFERENCES `category` (`id`),
  CONSTRAINT `FK_category_division` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分类';

-- 数据导出被取消选择。


-- 导出  表 inf.category_has_template 结构
CREATE TABLE IF NOT EXISTS `category_has_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` mediumint(8) unsigned NOT NULL,
  `template_id` mediumint(8) unsigned NOT NULL,
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_id_template_id` (`category_id`,`template_id`),
  KEY `FK_category_has_template_template` (`template_id`),
  CONSTRAINT `FK_category_has_template_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `FK_category_has_template_template` FOREIGN KEY (`template_id`) REFERENCES `template` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分类模板';

-- 数据导出被取消选择。


-- 导出  表 inf.division 结构
CREATE TABLE IF NOT EXISTS `division` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL,
  `url` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='事业部表';

-- 数据导出被取消选择。


-- 导出  过程 inf.gwfx 结构
DELIMITER //
//
DELIMITER ;


-- 导出  表 inf.gwfx_news 结构
CREATE TABLE IF NOT EXISTS `gwfx_news` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `publish` date DEFAULT NULL,
  `description` longtext,
  `language` char(2) DEFAULT NULL,
  `kind` char(2) DEFAULT NULL,
  `display` char(1) DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `mis` varchar(20) DEFAULT NULL,
  `image_b1` varchar(50) DEFAULT NULL,
  `image_s1` varchar(50) DEFAULT NULL,
  `image_b2` varchar(50) DEFAULT NULL,
  `image_s2` varchar(50) DEFAULT NULL,
  `area` varchar(2) DEFAULT NULL,
  `image_b3` varchar(50) DEFAULT NULL,
  `image_s3` varchar(50) DEFAULT NULL,
  `image_b4` varchar(50) DEFAULT NULL,
  `image_s4` varchar(50) DEFAULT NULL,
  `expertsId` int(11) DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `category` char(1) DEFAULT NULL COMMENT '0代表全部1代表外汇2代表贵金属',
  `pair_id` varchar(40) DEFAULT NULL COMMENT '配对号',
  `curr_data` varchar(200) DEFAULT NULL COMMENT '以字符串的形式保存产品名称、目标、止损、买或者卖，和建议买卖价',
  `is_index_dis` char(1) DEFAULT NULL COMMENT '0代表显示 1代表不显示',
  `account` varchar(20) DEFAULT NULL,
  `notice_category` varchar(100) DEFAULT NULL,
  `title2` varchar(1000) DEFAULT NULL,
  `SEO_TITLE` varchar(400) DEFAULT NULL,
  `SEO_KEYWORDS` varchar(400) DEFAULT NULL,
  `SEO_DESCRIPTION` varchar(800) DEFAULT NULL,
  `publish2` date DEFAULT NULL,
  `author` varchar(20) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `urlstatus` char(1) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `knowledge_type` varchar(20) DEFAULT NULL,
  `video` varchar(300) DEFAULT NULL COMMENT '视频',
  `audio` varchar(300) DEFAULT NULL COMMENT '音频',
  `video_image` varchar(300) DEFAULT NULL COMMENT '视频图片',
  `equipment` varchar(20) DEFAULT NULL,
  `praise` int(11) DEFAULT NULL COMMENT '赞同(点赞)',
  `not_praise` int(11) DEFAULT NULL COMMENT '不赞同(点赞)',
  `currency_type` varchar(20) DEFAULT NULL COMMENT '货币类型',
  `publish_mobile` datetime DEFAULT NULL,
  `audio_time` varchar(10) DEFAULT NULL,
  `source` char(1) DEFAULT NULL,
  PRIMARY KEY (`no`)
) ENGINE=FEDERATED DEFAULT CHARSET=utf8 CONNECTION='mysql://gwfx:gwfx@192.168.4.1:3306/whdata/news';

-- 数据导出被取消选择。


-- 导出  表 inf.gwfx_real_news 结构
CREATE TABLE IF NOT EXISTS `gwfx_real_news` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `newsid` varchar(50) DEFAULT NULL COMMENT '新闻ID',
  `newstime` datetime DEFAULT NULL,
  `jointime` datetime DEFAULT NULL,
  `language` varchar(2) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `content` longtext,
  `type` int(11) DEFAULT NULL COMMENT '用来区分读取各个不同的xml文件',
  `SEO_TITLE` varchar(200) DEFAULT NULL,
  `SEO_KEYWORDS` varchar(200) DEFAULT NULL,
  `SEO_DESCRIPTION` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`no`)
) ENGINE=FEDERATED DEFAULT CHARSET=utf8 CONNECTION='mysql://gwfx:gwfx@192.168.4.1:3306/whdata/real_news';

-- 数据导出被取消选择。


-- 导出  表 inf.gwfx_video 结构
CREATE TABLE IF NOT EXISTS `gwfx_video` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `video` varchar(300) DEFAULT NULL,
  `smallimage` varchar(100) DEFAULT NULL,
  `largeimage` varchar(100) DEFAULT NULL,
  `display` char(1) DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `mis` varchar(20) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` longtext,
  `kind` char(2) DEFAULT NULL,
  `publish` date DEFAULT NULL,
  `source` char(1) DEFAULT NULL,
  `equipment` varchar(20) DEFAULT NULL,
  `expertsId` int(11) DEFAULT NULL,
  `author` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`no`)
) ENGINE=FEDERATED DEFAULT CHARSET=utf8 CONNECTION='mysql://gwfx:gwfx@192.168.4.1:3306/whdata/news';

-- 数据导出被取消选择。


-- 导出  表 inf.images 结构
CREATE TABLE IF NOT EXISTS `images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` bigint(20) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 数据导出被取消选择。


-- 导出  表 inf.statistical 结构
CREATE TABLE IF NOT EXISTS `statistical` (
  `id` bigint(20) unsigned DEFAULT NULL,
  `click` bigint(20) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统计表';

-- 数据导出被取消选择。


-- 导出  表 inf.synchronous 结构
CREATE TABLE IF NOT EXISTS `synchronous` (
  `division_id` mediumint(8) unsigned NOT NULL COMMENT '事业部',
  `category_id` mediumint(8) unsigned NOT NULL COMMENT '分类',
  `type` varchar(8) NOT NULL COMMENT '事业部所属类型',
  `table` enum('news','real_news','video','info','t_hotpoint','goldnews','t_review') NOT NULL COMMENT '同步表',
  `lang` enum('en','cn','tw') NOT NULL DEFAULT 'cn',
  `position` bigint(20) unsigned NOT NULL DEFAULT '1' COMMENT '位置',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_id_type` (`category_id`,`type`),
  KEY `FK_synchronous_division` (`division_id`),
  CONSTRAINT `FK_synchronous_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `FK_synchronous_division` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据同步设置';

-- 数据导出被取消选择。


-- 导出  表 inf.template 结构
CREATE TABLE IF NOT EXISTS `template` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `division_id` mediumint(8) unsigned NOT NULL COMMENT '模板所属分类',
  `name` varchar(50) NOT NULL COMMENT '模板名字',
  `decription` varchar(255) DEFAULT NULL COMMENT '简短描述',
  `content` text NOT NULL COMMENT '模板内容',
  `type` enum('Category','List','Detail','Video') NOT NULL DEFAULT 'Category' COMMENT '模板类型',
  `status` enum('Enabled','Disabled') NOT NULL DEFAULT 'Disabled' COMMENT '模板状态',
  `engine` enum('PHP','Smarty','Volt') NOT NULL DEFAULT 'PHP' COMMENT '模板引擎',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间 ',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `FK_template_division` (`division_id`),
  CONSTRAINT `FK_template_division` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模板';

-- 数据导出被取消选择。


-- 导出  表 inf.template_history 结构
CREATE TABLE IF NOT EXISTS `template_history` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` mediumint(8) unsigned NOT NULL,
  `division_id` mediumint(8) unsigned NOT NULL COMMENT '模板所属分类',
  `name` varchar(50) NOT NULL COMMENT '模板名字',
  `decription` varchar(255) DEFAULT NULL COMMENT '简短描述',
  `content` text NOT NULL COMMENT '模板内容',
  `type` enum('Category','List','Detail','Video') NOT NULL DEFAULT 'Category' COMMENT '模板类型',
  `status` enum('Enabled','Disabled') NOT NULL DEFAULT 'Disabled' COMMENT '模板状态',
  `engine` enum('PHP','Smarty','Volt') NOT NULL DEFAULT 'PHP' COMMENT '模板引擎',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间 ',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `FK_template_division` (`division_id`),
  KEY `FK_template_history_template` (`template_id`),
  CONSTRAINT `FK_template_history_division` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`),
  CONSTRAINT `FK_template_history_template` FOREIGN KEY (`template_id`) REFERENCES `template` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模板';

-- 数据导出被取消选择。


-- 导出  表 inf.video 结构
CREATE TABLE IF NOT EXISTS `video` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `division_id` mediumint(8) unsigned NOT NULL COMMENT '所属事业部',
  `category_id` mediumint(8) unsigned NOT NULL COMMENT '隶属分类',
  `title` varchar(255) NOT NULL COMMENT '标题',
  `description` varchar(1024) DEFAULT NULL COMMENT '描述',
  `thumbnail` varchar(255) DEFAULT NULL COMMENT '缩图',
  `image` varchar(255) DEFAULT NULL COMMENT '图片',
  `video` varchar(255) NOT NULL COMMENT '视频',
  `author` varchar(32) DEFAULT NULL COMMENT '作者',
  `language` enum('cn','tw','en') NOT NULL DEFAULT 'cn' COMMENT '语言',
  `player` enum('youku','JW Player') NOT NULL,
  `visibility` enum('Visible','Hidden') NOT NULL DEFAULT 'Hidden' COMMENT '可见否',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `FK_videos_division` (`division_id`),
  KEY `FK_videos_category` (`category_id`),
  CONSTRAINT `FK_videos_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `FK_videos_division` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='视频';

-- 数据导出被取消选择。


-- 导出  触发器 inf.category_before_insert 结构
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `category_before_insert` BEFORE UPDATE ON `category` FOR EACH ROW BEGIN
	IF old.parent_id IS NULL THEN
		-- new.parent_id IS NOT NULL
		set new.parent_id = NULL;
	END IF;
	IF new.id = new.parent_id THEN
		set new.parent_id = old.parent_id;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;


-- 导出  触发器 inf.template_before_update 结构
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `template_before_update` BEFORE UPDATE ON `template` FOR EACH ROW BEGIN
	INSERT INTO template_history(	`template_id`,  `division_id`,  `name`,  `decription`,  `content`,  `type`,  `status`,  `engine`,  `ctime`,  `mtime`)
	VALUES (old.id, old.division_id, old.name, old.decription, old.content, old.type, old.status, old.engine, old.ctime, old.mtime);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
