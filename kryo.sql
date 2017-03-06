-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `ceny`;
CREATE TABLE `ceny` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produkty_id` int(11) NOT NULL,
  `instituce_id` int(11) NOT NULL,
  `cena` decimal(19,4) NOT NULL,
  `platna_od` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `platna_do` timestamp NULL DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `produkty_id` (`produkty_id`),
  KEY `instituce_id` (`instituce_id`),
  CONSTRAINT `ceny_ibfk_1` FOREIGN KEY (`produkty_id`) REFERENCES `produkty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ceny_ibfk_2` FOREIGN KEY (`instituce_id`) REFERENCES `instituce` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `instituce`;
CREATE TABLE `instituce` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) NOT NULL,
  `dph` varchar(255) NOT NULL DEFAULT 'dph.zadne' COMMENT 'key for settings table',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `nastaveni`;
CREATE TABLE `nastaveni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `objednavky`;
CREATE TABLE `objednavky` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ceny_id` int(11) NOT NULL,
  `produkty_id` int(11) NOT NULL,
  `uzivatele_id` int(11) NOT NULL,
  `skupiny_id` int(11) NOT NULL,
  `instituce_id` int(11) NOT NULL,
  `objednavky_stav_id` int(11) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `vyrizeno` timestamp NULL DEFAULT NULL,
  `stornovano` timestamp NULL DEFAULT NULL,
  `dokonceno` timestamp NULL DEFAULT NULL,
  `objem` decimal(19,4) NOT NULL,
  `vaha` decimal(19,4) DEFAULT NULL,
  `objem_vraceno` decimal(19,4) NOT NULL DEFAULT '0.0000',
  `vaha_vraceno` decimal(19,4) DEFAULT NULL,
  `datum_vyzvednuti` datetime NOT NULL,
  `jmeno` varchar(255) NOT NULL,
  `adresa` text,
  `pdf` varchar(255) DEFAULT NULL,
  `ico` varchar(255) DEFAULT NULL,
  `dic` varchar(255) DEFAULT NULL,
  `ucet` varchar(255) DEFAULT NULL,
  `dph` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ceny_id` (`ceny_id`),
  KEY `produkty_id` (`produkty_id`),
  KEY `uzivatele_id` (`uzivatele_id`),
  KEY `objednavky_stav_id` (`objednavky_stav_id`),
  KEY `skupiny_id` (`skupiny_id`),
  KEY `instituce_id` (`instituce_id`),
  CONSTRAINT `objednavky_ibfk_1` FOREIGN KEY (`ceny_id`) REFERENCES `ceny` (`id`),
  CONSTRAINT `objednavky_ibfk_2` FOREIGN KEY (`produkty_id`) REFERENCES `produkty` (`id`),
  CONSTRAINT `objednavky_ibfk_3` FOREIGN KEY (`uzivatele_id`) REFERENCES `uzivatele` (`id`),
  CONSTRAINT `objednavky_ibfk_4` FOREIGN KEY (`objednavky_stav_id`) REFERENCES `objednavky_stav` (`id`),
  CONSTRAINT `objednavky_ibfk_5` FOREIGN KEY (`skupiny_id`) REFERENCES `skupiny` (`id`),
  CONSTRAINT `objednavky_ibfk_6` FOREIGN KEY (`instituce_id`) REFERENCES `instituce` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `objednavky_stav`;
CREATE TABLE `objednavky_stav` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `produkty`;
CREATE TABLE `produkty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `skupiny`;
CREATE TABLE `skupiny` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituce_id` int(11) NOT NULL,
  `nazev` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `instituce_id` (`instituce_id`),
  CONSTRAINT `skupiny_ibfk_1` FOREIGN KEY (`instituce_id`) REFERENCES `instituce` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `upozorneni`;
CREATE TABLE `upozorneni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typ` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  `seen` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `uzivatele`;
CREATE TABLE `uzivatele` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instituce_id` int(11) NOT NULL,
  `skupiny_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `heslo` varchar(255) NOT NULL,
  `jmeno` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `instituce_id` (`instituce_id`),
  KEY `skupiny_id` (`skupiny_id`),
  CONSTRAINT `uzivatele_ibfk_1` FOREIGN KEY (`instituce_id`) REFERENCES `instituce` (`id`),
  CONSTRAINT `uzivatele_ibfk_2` FOREIGN KEY (`skupiny_id`) REFERENCES `skupiny` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2017-03-06 16:08:02