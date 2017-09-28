# Host: 192.168.0.102  (Version 5.5.39)
# Date: 2017-09-13 22:00:33
# Generator: MySQL-Front 5.3  (Build 8.1)

/*!40101 SET NAMES utf8 */;

#
# Structure for table "catalogentryfloatvalue"
#

DROP TABLE IF EXISTS `catalogentryfloatvalue`;
CREATE TABLE `catalogentryfloatvalue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry` int(10) unsigned DEFAULT NULL,
  `field` int(10) unsigned DEFAULT NULL,
  `value` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entry__idx` (`entry`),
  KEY `field__idx` (`field`),
  KEY `value__idx` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Data for table "catalogentryfloatvalue"
#

