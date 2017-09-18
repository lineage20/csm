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

-- 导出 sender 的数据库结构
CREATE DATABASE IF NOT EXISTS `sender` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `sender`;


-- 导出  表 sender.contact 结构
CREATE TABLE IF NOT EXISTS `contact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '唯一ID',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `mobile` varbinary(32) DEFAULT NULL COMMENT '手机号码',
  `email` varbinary(50) DEFAULT NULL COMMENT '电子邮件',
  `mobile_digest` varchar(32) NOT NULL COMMENT '摘要',
  `email_digest` varchar(32) DEFAULT NULL COMMENT '邮件摘要',
  `description` varchar(255) DEFAULT NULL COMMENT '备注描述',
  `status` enum('Subscription','Unsubscribe') NOT NULL DEFAULT 'Subscription' COMMENT '订阅状态',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `digest` (`mobile_digest`),
  UNIQUE KEY `email_digest` (`email_digest`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员手机短信与电子邮件映射表';

-- 数据导出被取消选择。


-- 导出  表 sender.group 结构
CREATE TABLE IF NOT EXISTS `group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(512) NOT NULL,
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短信分组';

-- 数据导出被取消选择。


-- 导出  表 sender.group_has_contact 结构
CREATE TABLE IF NOT EXISTS `group_has_contact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL,
  `contact_id` int(10) unsigned NOT NULL,
  `ctime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_contact` (`group_id`,`contact_id`),
  KEY `FK_group_has_contact_contact` (`contact_id`),
  CONSTRAINT `FK_group_has_contact_contact` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_group_has_contact_group` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='N:M';

-- 数据导出被取消选择。


-- 导出  表 sender.import 结构
CREATE TABLE IF NOT EXISTS `import` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL COMMENT '联系人组',
  `file` varchar(128) NOT NULL COMMENT '文件',
  `status` enum('New','Processing','Completed','Failed') NOT NULL DEFAULT 'New' COMMENT '状态',
  `succeed` mediumint(8) NOT NULL DEFAULT '0' COMMENT '成功导入',
  `ignore` mediumint(8) NOT NULL DEFAULT '0' COMMENT '忽略已存在',
  `failed` mediumint(8) NOT NULL DEFAULT '0' COMMENT '失败格式',
  PRIMARY KEY (`id`),
  KEY `FK_import_group` (`group_id`),
  CONSTRAINT `FK_import_group` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='联系人导入表';

-- 数据导出被取消选择。


-- 导出  表 sender.logging 结构
CREATE TABLE IF NOT EXISTS `logging` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` enum('unknow','http','cli') NOT NULL DEFAULT 'unknow' COMMENT '日志标签',
  `asctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '产生时间',
  `facility` enum('unknow','sms','email','test','daemon') NOT NULL DEFAULT 'unknow' COMMENT '类别',
  `priority` enum('info','warning','error','critical','exception','debug') NOT NULL DEFAULT 'debug' COMMENT '级别',
  `message` varchar(512) NOT NULL COMMENT '内容',
  `operator` varchar(50) NOT NULL DEFAULT 'computer' COMMENT '操作者',
  PRIMARY KEY (`id`),
  KEY `tag` (`tag`),
  KEY `asctime` (`asctime`),
  KEY `facility` (`facility`),
  KEY `message` (`message`(255)),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日志表';

-- 数据导出被取消选择。


-- 导出  表 sender.message 结构
CREATE TABLE IF NOT EXISTS `message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` set('SMS','Email') NOT NULL DEFAULT 'Email' COMMENT '类型',
  `title` varchar(50) DEFAULT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `reply_to` varchar(50) DEFAULT NULL COMMENT '回复到指定邮箱',
  `status` enum('New','Sent','Drafts','Trash') NOT NULL DEFAULT 'New' COMMENT '状态',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短消息内容';

-- 数据导出被取消选择。


-- 导出  表 sender.queue 结构
CREATE TABLE IF NOT EXISTS `queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) unsigned NOT NULL COMMENT '任务',
  `contact_id` int(10) unsigned NOT NULL COMMENT '发送到',
  `status` enum('New','Processing','Completed','Failed') NOT NULL DEFAULT 'New' COMMENT '队列状态',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '加入时间',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '完成时间',
  PRIMARY KEY (`id`),
  KEY `FK_queue_task` (`task_id`),
  KEY `FK_queue_contact` (`contact_id`),
  CONSTRAINT `FK_queue_contact` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_queue_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='发送队列';

-- 数据导出被取消选择。


-- 导出  表 sender.task 结构
CREATE TABLE IF NOT EXISTS `task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '任务名称',
  `type` enum('SMS','Email') NOT NULL DEFAULT 'Email' COMMENT '类型',
  `gateway` enum('Diexin','Yimei') DEFAULT NULL COMMENT '网关',
  `group_id` int(10) unsigned DEFAULT NULL COMMENT 'Null 表示所有人',
  `template_id` mediumint(8) unsigned NOT NULL COMMENT '模板',
  `message_id` int(10) unsigned NOT NULL COMMENT '消息',
  `status` enum('New','Processing','Completed','Failed') NOT NULL DEFAULT 'New' COMMENT '状态',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `FK_task_group` (`group_id`),
  KEY `FK_task_message` (`message_id`),
  KEY `FK_task_inf.template` (`template_id`),
  CONSTRAINT `FK_task_group` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_task_inf.template` FOREIGN KEY (`template_id`) REFERENCES `template` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_task_message` FOREIGN KEY (`message_id`) REFERENCES `message` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 数据导出被取消选择。


-- 导出  表 sender.template 结构
CREATE TABLE IF NOT EXISTS `template` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '模板名字',
  `decription` varchar(255) DEFAULT NULL COMMENT '简短描述',
  `content` text NOT NULL COMMENT '模板内容',
  `type` enum('SMS','Email') NOT NULL DEFAULT 'Email' COMMENT '模板类型',
  `status` enum('Enabled','Disabled') NOT NULL DEFAULT 'Disabled' COMMENT '模板状态',
  `engine` enum('PHP','Smarty','Volt') NOT NULL DEFAULT 'PHP' COMMENT '模板引擎',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间 ',
  `mtime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模板';

-- 数据导出被取消选择。


-- 导出  表 sender.user 结构
CREATE TABLE IF NOT EXISTS `user` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `description` varchar(50) DEFAULT NULL COMMENT '描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

-- 数据导出被取消选择。
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
