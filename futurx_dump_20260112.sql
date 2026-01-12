mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: trade
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int unsigned NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` int unsigned DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 02:14:24'),(2,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 02:39:53'),(3,5,'liquidate_position','position',16,'{\"pnl\": -5291.2458383994335, \"side\": \"long\", \"size\": 64.843699, \"email\": \"123\", \"reason\": \"admin_manual_liquidation\", \"symbol\": \"BTCUSDT\", \"user_id\": 4, \"exit_price\": 90928.3, \"entry_price\": 91009.9}','::1',NULL,'2026-01-12 02:49:33'),(4,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:39:37'),(5,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:41:07'),(6,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:42:12'),(7,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:43:04'),(8,5,'settings_update','settings',NULL,'{\"keys\": [\"maintenance_mode\", \"site_description\", \"site_name\", \"maker_fee\", \"max_leverage\", \"taker_fee\", \"max_withdraw_daily\", \"min_withdraw\", \"kyc_required\", \"registration_enabled\"]}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 04:34:55');
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('notice','event','maintenance','update') COLLATE utf8mb4_unicode_ci DEFAULT 'notice',
  `priority` int DEFAULT '0',
  `is_popup` tinyint(1) DEFAULT '0',
  `is_pinned` tinyint(1) DEFAULT '0',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `views` int DEFAULT '0',
  `author_id` int DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'FWWFWE','EWFEWFWEFWWEF','notice',0,0,0,'draft',0,5,NULL,NULL,'2026-01-12 03:03:47','2026-01-12 03:03:47');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competition_participants`
--

DROP TABLE IF EXISTS `competition_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competition_participants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `team_id` int unsigned DEFAULT NULL,
  `starting_balance` decimal(20,8) DEFAULT '0.00000000',
  `current_balance` decimal(20,8) DEFAULT '0.00000000',
  `pnl` decimal(20,8) DEFAULT '0.00000000',
  `roi` decimal(10,4) DEFAULT '0.0000',
  `trade_count` int DEFAULT '0',
  `volume` decimal(20,8) DEFAULT '0.00000000',
  `win_count` int DEFAULT '0',
  `loss_count` int DEFAULT '0',
  `current_rank` int DEFAULT NULL,
  `best_rank` int DEFAULT NULL,
  `status` enum('registered','active','disqualified','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'registered',
  `registered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_comp_user` (`competition_id`,`user_id`),
  KEY `idx_roi` (`roi`),
  KEY `idx_pnl` (`pnl`),
  KEY `idx_rank` (`current_rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competition_participants`
--

LOCK TABLES `competition_participants` WRITE;
/*!40000 ALTER TABLE `competition_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `competition_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competition_prizes`
--

DROP TABLE IF EXISTS `competition_prizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competition_prizes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` int unsigned NOT NULL,
  `rank_from` int NOT NULL,
  `rank_to` int NOT NULL,
  `prize_amount` decimal(20,8) NOT NULL,
  `prize_type` enum('usdt','bonus','nft','other') COLLATE utf8mb4_unicode_ci DEFAULT 'usdt',
  `prize_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competition_prizes`
--

LOCK TABLES `competition_prizes` WRITE;
/*!40000 ALTER TABLE `competition_prizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `competition_prizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competitions`
--

DROP TABLE IF EXISTS `competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `rules` text COLLATE utf8mb4_unicode_ci,
  `banner_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('roi','pnl','volume','team') COLLATE utf8mb4_unicode_ci DEFAULT 'roi',
  `status` enum('upcoming','active','ended','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'upcoming',
  `entry_fee` decimal(20,8) DEFAULT '0.00000000',
  `min_balance` decimal(20,8) DEFAULT '0.00000000',
  `max_participants` int DEFAULT NULL,
  `prize_pool` decimal(20,8) DEFAULT '0.00000000',
  `prize_currency` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'USDT',
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `registration_start` datetime DEFAULT NULL,
  `registration_end` datetime DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_start_at` (`start_at`),
  KEY `idx_end_at` (`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competitions`
--

LOCK TABLES `competitions` WRITE;
/*!40000 ALTER TABLE `competitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `competitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaderboard_snapshots`
--

DROP TABLE IF EXISTS `leaderboard_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leaderboard_snapshots` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `period` enum('daily','weekly','monthly','all') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_key` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pnl` decimal(20,8) DEFAULT '0.00000000',
  `roi` decimal(10,4) DEFAULT '0.0000',
  `volume` decimal(20,8) DEFAULT '0.00000000',
  `trade_count` int DEFAULT '0',
  `win_rate` decimal(5,2) DEFAULT '0.00',
  `rank` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_period` (`user_id`,`period`,`period_key`),
  KEY `idx_period` (`period`,`period_key`),
  KEY `idx_rank` (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaderboard_snapshots`
--

LOCK TABLES `leaderboard_snapshots` WRITE;
/*!40000 ALTER TABLE `leaderboard_snapshots` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaderboard_snapshots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leverage_settings`
--

DROP TABLE IF EXISTS `leverage_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leverage_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `symbol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BTCUSDT',
  `leverage` int NOT NULL DEFAULT '10',
  `margin_mode` enum('cross','isolated') COLLATE utf8mb4_unicode_ci DEFAULT 'cross',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_symbol` (`user_id`,`symbol`),
  CONSTRAINT `leverage_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leverage_settings`
--

LOCK TABLES `leverage_settings` WRITE;
/*!40000 ALTER TABLE `leverage_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `leverage_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BTCUSDT',
  `side` enum('buy','sell') COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_side` enum('long','short') COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_type` enum('limit','market','stop_limit','stop_market','take_profit','stop_loss') COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(20,8) DEFAULT NULL,
  `quantity` decimal(20,8) NOT NULL,
  `filled_quantity` decimal(20,8) DEFAULT '0.00000000',
  `avg_fill_price` decimal(20,8) DEFAULT NULL,
  `stop_price` decimal(20,8) DEFAULT NULL,
  `leverage` int NOT NULL DEFAULT '10',
  `margin_mode` enum('cross','isolated') COLLATE utf8mb4_unicode_ci DEFAULT 'cross',
  `reduce_only` tinyint(1) DEFAULT '0',
  `time_in_force` enum('GTC','IOC','FOK','GTX') COLLATE utf8mb4_unicode_ci DEFAULT 'GTC',
  `status` enum('pending','open','partially_filled','filled','cancelled','rejected','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `idx_user_symbol` (`user_id`,`symbol`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,'ORD20260111225741EF4459ED','BTCUSDT','buy','long','market',90800.00000000,0.00100000,0.00100000,90800.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 22:57:41','2026-01-11 22:57:41'),(2,4,'ORD202601112316129648B3EC','BTCUSDT','buy','long','limit',90931.05000000,54.98671200,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-11 23:16:12','2026-01-12 00:01:12'),(3,4,'ORD20260111231640CD073BA4','BTCUSDT','buy','long','market',90886.00000000,13.66419900,13.66419900,90886.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 23:16:40','2026-01-11 23:16:40'),(4,4,'ORD20260111235919F51BD571','BTCUSDT','buy','long','market',91128.00000000,19.90767900,19.90767900,91128.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 23:59:19','2026-01-11 23:59:19'),(5,4,'ORD20260111235926A0E0F627','BTCUSDT','sell','short','market',91100.00000000,19.90767900,19.90767900,91100.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 23:59:26','2026-01-11 23:59:26'),(6,4,'ORD20260112004112C45853EB','BTCUSDT','sell','long','market',90971.00000000,33.57187800,33.57187800,90971.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 00:41:12','2026-01-12 00:41:12'),(7,4,'ORD2026011200414666477D17','BTCUSDT','buy','short','market',90942.10000000,19.90767900,19.90767900,90942.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 00:41:46','2026-01-12 00:41:46'),(8,4,'ORD20260112005125315352A2','BTCUSDT','buy','long','market',90742.30000000,96.10609300,96.10609300,90742.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 00:51:25','2026-01-12 00:51:25'),(9,4,'ORD20260112005215E4616785','BTCUSDT','sell','long','market',90749.90000000,96.10609300,96.10609300,90749.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 00:52:15','2026-01-12 00:52:15'),(10,4,'ORD20260112005725E79E7BC4','BTCUSDT','buy','long','market',90804.80000000,89.16427100,89.16427100,90804.80000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 00:57:25','2026-01-12 00:57:25'),(11,4,'ORD20260112010043B04FC86C','BTCUSDT','sell','long','market',90853.40000000,89.16427100,89.16427100,90853.40000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:00:43','2026-01-12 01:00:43'),(12,4,'ORD20260112010047C501DFBB','BTCUSDT','sell','short','market',90853.40000000,68.10891400,68.10891400,90853.40000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:00:47','2026-01-12 01:00:47'),(13,4,'ORD20260112010051C0897823','BTCUSDT','buy','short','market',90857.10000000,68.10891400,68.10891400,90857.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:00:51','2026-01-12 01:00:51'),(14,4,'ORD202601120101059D1A9158','BTCUSDT','buy','long','market',90864.00000000,68.10891400,68.10891400,90864.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:01:05','2026-01-12 01:01:05'),(15,4,'ORD2026011201010940F66076','BTCUSDT','sell','long','market',90864.00000000,68.10891400,68.10891400,90864.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:01:09','2026-01-12 01:01:09'),(16,4,'ORD202601120101388FA876C9','BTCUSDT','sell','short','market',90896.60000000,68.10891400,68.10891400,90896.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:01:38','2026-01-12 01:01:38'),(17,4,'ORD2026011201014157DE67E5','BTCUSDT','buy','short','market',90896.50000000,68.10891400,68.10891400,90896.50000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:01:41','2026-01-12 01:01:41'),(18,4,'ORD20260112010157DC9E177F','BTCUSDT','sell','short','market',90894.60000000,57.13005400,57.13005400,90894.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:01:57','2026-01-12 01:01:57'),(19,4,'ORD202601120102040AEDECED','BTCUSDT','buy','short','market',90911.90000000,57.13005400,57.13005400,90911.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:02:04','2026-01-12 01:02:04'),(20,4,'ORD202601120104474D9C61ED','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:04:47','2026-01-12 01:05:05'),(21,4,'ORD20260112010508D3BCB5D1','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:08','2026-01-12 01:05:14'),(22,4,'ORD20260112010515D1D2A8C3','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:15','2026-01-12 01:05:16'),(23,4,'ORD20260112010529401FD6F9','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:29','2026-01-12 01:05:31'),(24,4,'ORD20260112010535E178687B','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:35','2026-01-12 01:05:37'),(25,4,'ORD20260112010624C0C68A45','BTCUSDT','buy','long','market',90901.50000000,0.00100000,0.00100000,90901.50000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:06:24','2026-01-12 01:06:24'),(26,4,'ORD2026011201094887E010BA','BTCUSDT','sell','long','market',90949.90000000,0.00100000,0.00100000,90949.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:09:48','2026-01-12 01:09:48'),(27,4,'ORD20260112010952CCFBE0A5','BTCUSDT','buy','long','limit',90962.99000000,52.45651300,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:09:52','2026-01-12 01:24:05'),(28,4,'ORD20260112011954DB11C58E','BTCUSDT','buy','long','market',90911.10000000,33.50627700,33.50627700,90911.10000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:19:54','2026-01-12 01:19:54'),(29,4,'ORD2026011201205697729A66','BTCUSDT','sell','long','market',90913.80000000,33.50627700,33.50627700,90913.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:20:56','2026-01-12 01:20:56'),(30,4,'ORD202601120121091D80D767','BTCUSDT','buy','long','market',90913.80000000,33.50627700,33.50627700,90913.80000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:21:09','2026-01-12 01:21:09'),(31,4,'ORD20260112012113DC2BBC2D','BTCUSDT','sell','long','market',90913.80000000,33.50627700,33.50627700,90913.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:21:13','2026-01-12 01:21:13'),(32,4,'ORD20260112012413BA1556DF','BTCUSDT','buy','long','market',90979.42000000,70.93723500,70.93723500,90979.42000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:24:13','2026-01-12 01:24:13'),(33,1,'ORD202601120136407AB9DC8A','BTCUSDT','buy','long','market',91142.70000000,0.00100000,0.00100000,91142.70000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:36:40','2026-01-12 01:36:40'),(34,4,'ORD202601120141385F808553','BTCUSDT','sell','long','market',91061.10000000,70.93723500,70.93723500,91061.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:41:38','2026-01-12 01:41:38'),(35,4,'ORD2026011201415811949403','BTCUSDT','buy','long','limit',91075.00000000,50.21000200,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:41:58','2026-01-12 01:42:33'),(36,4,'ORD202601120142359B176707','BTCUSDT','buy','long','market',91069.60000000,50.21000200,50.21000200,91069.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:42:35','2026-01-12 01:42:35'),(37,4,'ORD20260112014503EC62C07F','BTCUSDT','sell','long','market',91080.90000000,50.21000200,50.21000200,91080.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:45:03','2026-01-12 01:45:03'),(38,4,'ORD202601120145204AAEE1E4','BTCUSDT','buy','long','market',91081.20000000,65.76313000,65.76313000,91081.20000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:45:20','2026-01-12 01:45:20'),(39,4,'ORD20260112014815FADA21FA','BTCUSDT','sell','long','market',91069.80000000,65.76313000,65.76313000,91069.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:48:15','2026-01-12 01:48:15'),(40,4,'ORD202601120148233E61C7AB','BTCUSDT','buy','long','limit',91000.00000000,65.76313000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:48:23','2026-01-12 01:49:29'),(41,4,'ORD20260112015559E1B7347B','BTCUSDT','buy','long','limit',91010.00000000,64.84369900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:55:59','2026-01-12 01:56:03'),(42,4,'ORD202601120156060ADBC65F','BTCUSDT','buy','long','market',91009.90000000,64.84369900,64.84369900,91009.90000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:56:06','2026-01-12 01:56:06'),(43,4,'ORD20260112025009C7BAA650','BTCUSDT','buy','long','market',90928.40000000,26.38203400,26.38203400,90928.40000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 02:50:09','2026-01-12 02:50:09'),(44,4,'ORD20260112041636795754C4','BTCUSDT','buy','long','market',90841.60000000,5.74630100,5.74630100,90841.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 04:16:36','2026-01-12 04:16:36'),(45,4,'ORD20260112041719782888F0','BTCUSDT','sell','long','market',90834.10000000,32.12833500,32.12833500,90834.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 04:17:19','2026-01-12 04:17:19'),(46,4,'ORD20260112041739D317E78D','BTCUSDT','buy','long','limit',90500.00000000,17.14237000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 04:17:39','2026-01-12 04:21:43'),(47,4,'ORD202601120422020CB4E9EF','BTCUSDT','buy','long','limit',90100.00000000,17.57595800,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 04:22:02','2026-01-12 04:22:13'),(48,4,'ORD20260112042251055B315F','BTCUSDT','buy','long','limit',90100.00000000,17.57595800,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 04:22:51','2026-01-12 04:36:03'),(49,4,'ORD20260112043611761BF467','BTCUSDT','buy','long','market',90808.40000000,18.17310300,18.17310300,90808.40000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 04:36:11','2026-01-12 04:36:11'),(50,4,'ORD202601120439147835496D','BTCUSDT','sell','long','market',90790.40000000,18.17310300,18.17310300,90790.40000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 04:39:14','2026-01-12 04:39:14'),(51,1,'ORD202601120539330739BA57','BTCUSDT','sell','short','limit',90566.50000000,0.00100000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 05:39:33','2026-01-12 05:55:43'),(52,1,'ORD202601120546062F9F9E1F','BTCUSDT','sell','long','market',90583.10000000,0.00200000,0.00200000,90583.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 05:46:06','2026-01-12 05:46:06'),(53,1,'ORD20260112081209EE6A1A8F','BTCUSDT','buy','long','market',90460.70000000,0.52353700,0.52353700,90460.70000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 08:12:09','2026-01-12 08:12:09'),(54,1,'ORD20260112081700F70531D7','BTCUSDT','buy','long','limit',90840.00000000,0.30491500,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 08:17:00','2026-01-12 08:17:14'),(55,1,'ORD20260112081741B9C867E3','BTCUSDT','buy','long','limit',90850.00000000,0.30491500,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 08:17:41','2026-01-12 08:17:50'),(56,1,'ORD202601120822033304FF13','BTCUSDT','sell','long','market',90802.80000000,0.52353700,0.52353700,90802.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 08:22:03','2026-01-12 08:22:03'),(57,1,'ORD2026011208395924EDA338','BTCUSDT','buy','long','market',90707.30000000,0.59511800,0.59511800,90707.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 08:39:59','2026-01-12 08:39:59'),(58,1,'ORD202601120840103D1BD1D7','BTCUSDT','sell','long','market',90691.20000000,0.59511800,0.59511800,90691.20000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 08:40:10','2026-01-12 08:40:10'),(59,1,'ORD2026011208401557C95F84','BTCUSDT','buy','long','market',90707.30000000,0.59511800,0.59511800,90707.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 08:40:15','2026-01-12 08:40:15'),(60,1,'ORD20260112084021E98432D1','BTCUSDT','sell','long','market',90707.40000000,0.59511800,0.59511800,90707.40000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 08:40:21','2026-01-12 08:40:21'),(61,1,'ORD2026011208425616D162DF','BTCUSDT','buy','long','limit',90755.00000000,0.59511800,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 08:42:56','2026-01-12 08:48:22'),(62,1,'ORD202601120848176CCB84AB','BTCUSDT','buy','long','market',90878.50000000,0.00100000,0.00100000,90878.50000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 08:48:17','2026-01-12 08:48:17'),(63,1,'ORD2026011208483462D8DA21','BTCUSDT','sell','long','market',90883.10000000,0.00100000,0.00100000,90883.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 08:48:34','2026-01-12 08:48:34'),(64,4,'ORD2026011209250731A9B7C6','BTCUSDT','buy','long','limit',91110.20000000,20.36815100,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:25:07','2026-01-12 09:25:13'),(65,4,'ORD20260112092523BDB0D49E','BTCUSDT','buy','long','limit',91104.60000000,20.36815100,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:25:23','2026-01-12 09:25:25'),(66,4,'ORD20260112092538DAE8EBEA','BTCUSDT','buy','long','limit',91108.00000000,20.36815100,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:25:38','2026-01-12 09:25:40'),(67,4,'ORD202601120931455DBB27AE','BTCUSDT','buy','long','limit',91102.30000000,0.00100000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:31:45','2026-01-12 09:31:49'),(68,4,'ORD20260112093200A38566DB','BTCUSDT','buy','long','limit',91100.00000000,35.93752600,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:32:00','2026-01-12 09:32:50'),(69,4,'ORD202601120949429EF3312C','BTCUSDT','buy','long','limit',90830.00000000,0.00100000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:49:42','2026-01-12 09:49:45'),(70,4,'ORD20260112094953ECECF104','BTCUSDT','buy','long','limit',90840.00000000,20.27969000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:49:53','2026-01-12 09:50:00'),(71,4,'ORD202601120950046FB643F1','BTCUSDT','buy','long','limit',90860.00000000,20.27969000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:50:04','2026-01-12 09:51:08'),(72,4,'ORD202601120959123DA039F3','BTCUSDT','buy','long','limit',91100.00000000,22.39550200,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 09:59:12','2026-01-12 09:59:37'),(73,4,'ORD2026011210054399A86016','BTCUSDT','buy','long','limit',91630.00000000,21.69739700,21.69739700,91618.20000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:05:43','2026-01-12 10:05:44'),(74,4,'ORD202601121012120247EA1F','BTCUSDT','sell','long','market',91539.70000000,21.69739700,21.69739700,91539.70000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:12:12','2026-01-12 10:12:12'),(75,4,'ORD20260112101233385D8810','BTCUSDT','buy','long','limit',91470.00000000,21.69739700,21.69739700,91470.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:12:33','2026-01-12 10:13:12'),(76,4,'ORD20260112101427C6EB7EA5','BTCUSDT','sell','long','market',91467.90000000,21.69739700,21.69739700,91467.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:14:27','2026-01-12 10:14:27'),(77,4,'ORD2026011210143273DAEA85','BTCUSDT','buy','long','limit',91470.00000000,21.69739700,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 10:14:32','2026-01-12 10:15:01'),(78,4,'ORD20260112101519C358ACB3','BTCUSDT','buy','long','limit',91400.00000000,21.69739700,21.69739700,91400.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:15:19','2026-01-12 10:15:20'),(79,4,'ORD20260112101608F018B352','BTCUSDT','sell','long','market',91368.10000000,21.69739700,21.69739700,91368.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:16:08','2026-01-12 10:16:08'),(80,4,'ORD202601121018512095A71A','BTCUSDT','buy','long','market',91421.00000000,21.69739700,21.69739700,91421.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:18:51','2026-01-12 10:18:51'),(81,4,'ORD202601121019105940CC2D','BTCUSDT','sell','long','market',91395.10000000,21.69739700,21.69739700,91395.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:19:10','2026-01-12 10:19:10'),(82,4,'ORD202601121019151813177E','BTCUSDT','buy','long','market',91380.20000000,21.69739700,21.69739700,91380.20000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:19:15','2026-01-12 10:19:15'),(83,4,'ORD2026011210191783E98143','BTCUSDT','sell','long','market',91380.10000000,21.69739700,21.69739700,91380.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:19:17','2026-01-12 10:19:17'),(84,4,'ORD202601121019509445F70B','BTCUSDT','buy','long','market',91426.30000000,21.69739700,21.69739700,91426.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:19:50','2026-01-12 10:19:50'),(85,4,'ORD2026011210195337CB784D','BTCUSDT','sell','long','market',91434.80000000,21.69739700,21.69739700,91434.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:19:53','2026-01-12 10:19:53'),(86,4,'ORD2026011210203838D4EAF6','BTCUSDT','buy','long','market',91400.10000000,21.69739700,21.69739700,91400.10000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:20:38','2026-01-12 10:20:38'),(87,4,'ORD2026011210204136D2F476','BTCUSDT','sell','long','market',91427.60000000,21.69739700,21.69739700,91427.60000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:20:41','2026-01-12 10:20:41'),(88,4,'ORD202601121021137242CFAE','BTCUSDT','buy','long','limit',91484.30000000,16.42086000,16.42086000,91484.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:21:13','2026-01-12 10:21:14'),(89,4,'ORD20260112102125D8FC8B57','BTCUSDT','sell','long','market',91457.20000000,16.42086000,16.42086000,91457.20000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:21:25','2026-01-12 10:21:25'),(90,4,'ORD20260112102151CEF4210A','BTCUSDT','buy','long','limit',91454.30000000,16.42086000,16.42086000,91454.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:21:51','2026-01-12 10:21:53'),(91,4,'ORD20260112102204487DC1CE','BTCUSDT','sell','long','market',91418.30000000,16.42086000,16.42086000,91418.30000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:22:04','2026-01-12 10:22:04'),(92,4,'ORD202601121022071AB75118','BTCUSDT','buy','long','market',91439.90000000,16.42086000,16.42086000,91439.90000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:22:07','2026-01-12 10:22:07'),(93,4,'ORD202601121022104C513E4B','BTCUSDT','sell','long','market',91440.00000000,16.42086000,16.42086000,91440.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:22:10','2026-01-12 10:22:10'),(94,4,'ORD20260112102254D65EA137','BTCUSDT','buy','long','market',91444.70000000,16.42086000,16.42086000,91444.70000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:22:54','2026-01-12 10:22:54'),(95,4,'ORD202601121022586E63A622','BTCUSDT','sell','long','market',91470.80000000,16.42086000,16.42086000,91470.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:22:58','2026-01-12 10:22:58'),(96,4,'ORD2026011210230410AD0150','BTCUSDT','buy','long','market',91483.50000000,14.34751100,14.34751100,91483.50000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:23:04','2026-01-12 10:23:04'),(97,4,'ORD20260112102326663ECE29','BTCUSDT','sell','long','market',91513.20000000,14.34751100,14.34751100,91513.20000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:23:26','2026-01-12 10:23:26'),(98,4,'ORD2026011210233388C7E7DF','BTCUSDT','buy','long','limit',91539.10000000,17.76893500,17.76893500,91539.10000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:23:33','2026-01-12 10:23:35'),(99,4,'ORD20260112102405D4CCB680','BTCUSDT','sell','long','market',91520.00000000,17.76893500,17.76893500,91520.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:24:05','2026-01-12 10:24:05'),(100,4,'ORD2026011210240976B7B0E1','BTCUSDT','buy','long','market',91519.90000000,17.76893500,17.76893500,91519.90000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:24:09','2026-01-12 10:24:09'),(101,4,'ORD202601121024127FF7C69A','BTCUSDT','sell','long','market',91526.70000000,17.76893500,17.76893500,91526.70000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:24:12','2026-01-12 10:24:12'),(102,4,'ORD20260112102441E008A31C','BTCUSDT','buy','long','limit',91479.50000000,17.76893500,17.76893500,91479.50000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:24:41','2026-01-12 10:25:04'),(103,4,'ORD202601121027020A7CD047','BTCUSDT','sell','long','market',91382.50000000,17.76893500,17.76893500,91382.50000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:27:02','2026-01-12 10:27:02'),(104,4,'ORD2026011210272521C442A7','BTCUSDT','buy','long','limit',91376.10000000,17.76893500,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 10:27:25','2026-01-12 10:27:32'),(105,4,'ORD20260112102733A9591F12','BTCUSDT','buy','long','limit',91386.20000000,17.76893500,17.76893500,91386.20000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:27:33','2026-01-12 10:27:34'),(106,4,'ORD2026011210280900D8C177','BTCUSDT','sell','long','market',91429.20000000,17.76893500,17.76893500,91429.20000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:28:09','2026-01-12 10:28:09'),(107,4,'ORD20260112102821B2EFFDC2','BTCUSDT','buy','long','limit',91421.00000000,17.47699000,17.47699000,91421.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:28:21','2026-01-12 10:28:21'),(108,4,'ORD2026011210282709BB2A92','BTCUSDT','sell','long','market',91422.00000000,17.47699000,17.47699000,91422.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:28:27','2026-01-12 10:28:27'),(109,4,'ORD20260112102833BCA9A7D8','BTCUSDT','buy','long','limit',91465.80000000,17.47699000,17.47699000,91465.80000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:28:33','2026-01-12 10:28:33'),(110,4,'ORD20260112102837E180B97D','BTCUSDT','sell','long','market',91443.10000000,17.47699000,17.47699000,91443.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:28:37','2026-01-12 10:28:37'),(111,4,'ORD202601121028417D2FFD5D','BTCUSDT','buy','long','limit',91428.30000000,17.47699000,17.47699000,91428.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:28:41','2026-01-12 10:28:42'),(112,4,'ORD20260112102847A5571CAD','BTCUSDT','sell','long','market',91423.10000000,17.47699000,17.47699000,91423.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:28:47','2026-01-12 10:28:47'),(113,4,'ORD202601121028535FC30563','BTCUSDT','buy','long','limit',91421.00000000,17.47699000,17.47699000,91421.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:28:53','2026-01-12 10:28:57'),(114,4,'ORD202601121029178DE56162','BTCUSDT','sell','long','market',91441.60000000,17.47699000,17.47699000,91441.60000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:29:17','2026-01-12 10:29:17'),(115,4,'ORD2026011210292402DEEFE2','BTCUSDT','buy','long','limit',91421.00000000,24.17274000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 10:29:24','2026-01-12 10:29:29'),(116,4,'ORD20260112102934DFBA753D','BTCUSDT','buy','long','limit',91455.90000000,24.17274000,24.17274000,91455.90000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:29:34','2026-01-12 10:29:35'),(117,4,'ORD20260112102950DBE9793F','BTCUSDT','sell','long','market',91483.10000000,24.17274000,24.17274000,91483.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:29:50','2026-01-12 10:29:50'),(118,4,'ORD202601121030407ABB9162','BTCUSDT','buy','long','market',91368.40000000,24.17274000,24.17274000,91368.40000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:30:40','2026-01-12 10:30:40'),(119,4,'ORD20260112103055D7A0BC15','BTCUSDT','sell','long','market',91360.00000000,24.17274000,24.17274000,91360.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:30:55','2026-01-12 10:30:55'),(120,4,'ORD20260112103203B93F7C56','BTCUSDT','buy','long','limit',91383.00000000,28.07333300,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 10:32:03','2026-01-12 10:32:52'),(121,4,'ORD20260112103257C164B2A6','BTCUSDT','buy','long','limit',91436.30000000,28.07333300,28.07333300,91436.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:32:57','2026-01-12 10:32:58'),(122,4,'ORD20260112103701715CA99A','BTCUSDT','sell','long','market',91141.10000000,28.07333300,28.07333300,91141.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:37:01','2026-01-12 10:37:01'),(123,4,'ORD20260112105337450ECCCE','BTCUSDT','buy','long','market',91382.60000000,17.20908100,17.20908100,91382.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 10:53:37','2026-01-12 10:53:37'),(124,4,'ORD20260112105342627D1E54','BTCUSDT','sell','long','market',91382.50000000,17.20908100,17.20908100,91382.50000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 10:53:42','2026-01-12 10:53:42'),(125,4,'ORD20260112105350CAD5741B','BTCUSDT','buy','long','limit',91300.00000000,17.20908100,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 10:53:50','2026-01-12 10:53:54'),(126,4,'ORD202601121100043F380897','BTCUSDT','buy','long','market',91385.80000000,17.20612200,17.20612200,91385.80000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:00:04','2026-01-12 11:00:04'),(127,4,'ORD20260112110020488F7FEB','BTCUSDT','sell','long','market',91394.70000000,17.20612200,17.20612200,91394.70000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:00:20','2026-01-12 11:00:20'),(128,4,'ORD20260112110031BD9DC82F','BTCUSDT','buy','long','limit',91300.00000000,17.20612200,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 11:00:31','2026-01-12 11:01:35'),(129,4,'ORD202601121100476C3A6B45','BTCUSDT','sell','short','limit',91400.00000000,8.64032400,8.64032400,91400.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:00:47','2026-01-12 11:00:49'),(130,4,'ORD20260112110059100EF74E','BTCUSDT','buy','short','market',91396.60000000,8.64032400,8.64032400,91396.60000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:00:59','2026-01-12 11:00:59'),(131,4,'ORD20260112110103386A9B2C','BTCUSDT','sell','short','limit',91500.00000000,8.64032400,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 11:01:03','2026-01-12 11:01:34'),(132,4,'ORD20260112110200C6E1CEA8','BTCUSDT','sell','short','limit',91500.00000000,18.95514600,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 11:02:00','2026-01-12 11:02:29'),(133,4,'ORD20260112111711AE216DF8','ETHUSDT','sell','short','market',3148.59000000,652.55496800,652.55496800,3148.59000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:17:11','2026-01-12 11:17:11'),(134,4,'ORD202601121120065B371495','ETHUSDT','sell','short','limit',3142.79000000,318.97256100,318.97256100,3142.79000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:20:06','2026-01-12 11:20:07'),(135,4,'ORD20260112113944338BC7D9','SOLUSDT','sell','short','market',141.96000000,0.39299000,0.39299000,141.96000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:39:44','2026-01-12 11:39:44'),(136,4,'ORD2026011211425553E878FD','ETHUSDT','buy','short','market',3146.57000000,971.52752900,971.52752900,3146.57000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:42:55','2026-01-12 11:42:55'),(137,4,'ORD20260112114305654622E7','ETHUSDT','buy','long','limit',3146.00000000,586.22783200,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 11:43:05','2026-01-12 11:43:41'),(138,4,'ORD20260112114321E96D2C0B','SOLUSDT','buy','short','market',3148.79000000,0.39299000,0.39299000,3148.79000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:43:21','2026-01-12 11:43:21'),(139,4,'ORD2026011211434993636F70','ETHUSDT','sell','short','limit',3150.10000000,606.87206600,606.87206600,3150.10000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:43:49','2026-01-12 11:43:49'),(140,4,'ORD20260112114725FFDF9F3A','ETHUSDT','buy','short','market',3158.89000000,606.87206600,606.87206600,3158.89000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:47:25','2026-01-12 11:47:25'),(141,4,'ORD20260112114950F0CF800C','BTCUSDT','sell','short','limit',92145.60000000,16.65793500,16.65793500,92145.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:49:50','2026-01-12 11:49:50'),(142,4,'ORD202601121149555E981372','BTCUSDT','buy','short','market',92155.80000000,16.65793500,16.65793500,92155.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:49:55','2026-01-12 11:49:55'),(143,4,'ORD202601121149574F421FF0','BTCUSDT','sell','short','limit',92151.30000000,16.65793500,16.65793500,92151.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:49:57','2026-01-12 11:49:58'),(144,4,'ORD2026011211500417C5F3DF','BTCUSDT','buy','short','market',92183.80000000,16.65793500,16.65793500,92183.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:50:05','2026-01-12 11:50:05'),(145,4,'ORD20260112115012C36A4E26','BTCUSDT','sell','short','limit',92155.00000000,16.65793500,16.65793500,92155.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:50:12','2026-01-12 11:50:12'),(146,4,'ORD2026011211501986F6DF7C','BTCUSDT','buy','short','market',92233.00000000,16.65793500,16.65793500,92233.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 11:50:19','2026-01-12 11:50:19'),(147,4,'ORD202601121150310DBAD2DA','BTCUSDT','sell','short','limit',92260.00000000,16.65793500,16.65793500,92260.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:50:31','2026-01-12 11:51:04'),(148,4,'ORD20260112115524850C07BF','ETHUSDT','sell','short','limit',3164.54000000,204.76269500,204.76269500,3164.54000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 11:55:24','2026-01-12 11:55:25'),(149,4,'ORD202601121308405B197212','XRPUSDT','buy','long','market',2.08520000,308647.03250400,308647.03250400,2.08520000,NULL,10,'cross',0,'GTC','filled','2026-01-12 13:08:40','2026-01-12 13:08:40');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `symbol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BTCUSDT',
  `side` enum('long','short') COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` decimal(20,8) NOT NULL DEFAULT '0.00000000',
  `entry_price` decimal(20,8) NOT NULL,
  `mark_price` decimal(20,8) DEFAULT NULL,
  `exit_price` decimal(20,8) DEFAULT NULL,
  `liquidation_price` decimal(20,8) DEFAULT NULL,
  `margin` decimal(20,8) NOT NULL,
  `leverage` int NOT NULL DEFAULT '10',
  `unrealized_pnl` decimal(20,8) DEFAULT '0.00000000',
  `realized_pnl` decimal(20,8) DEFAULT '0.00000000',
  `margin_mode` enum('cross','isolated') COLLATE utf8mb4_unicode_ci DEFAULT 'cross',
  `tp_price` decimal(20,8) DEFAULT NULL,
  `sl_price` decimal(20,8) DEFAULT NULL,
  `status` enum('open','closed','liquidated') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `opened_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_symbol` (`user_id`,`symbol`),
  KEY `idx_status` (`status`),
  CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,1,'BTCUSDT','long',0.00000000,90971.35000000,90800.00000000,NULL,82174.00000000,18.19427000,10,0.00000000,-0.77650000,'cross',NULL,NULL,'closed','2026-01-11 22:57:41','2026-01-12 05:46:06','2026-01-11 22:57:41','2026-01-12 05:46:06'),(2,4,'BTCUSDT','long',0.00000000,91029.50279475,90886.00000000,NULL,82251.83000000,305603.13622260,10,0.00000000,-3796.48907613,'cross',NULL,NULL,'closed','2026-01-11 23:16:40','2026-01-12 00:41:12','2026-01-11 23:16:40','2026-01-12 00:41:12'),(3,4,'BTCUSDT','short',0.00000000,91100.00000000,91100.00000000,NULL,99754.50000000,181358.95569000,10,0.00000000,2057.15483347,'cross',NULL,NULL,'closed','2026-01-11 23:59:26','2026-01-12 00:41:46','2026-01-11 23:59:26','2026-01-12 00:41:46'),(4,4,'BTCUSDT','long',0.00000000,90742.30000000,90742.30000000,NULL,82121.78150000,872088.79228339,10,0.00000000,-4502.56469069,'cross',NULL,NULL,'closed','2026-01-12 00:51:25','2026-01-12 00:52:15','2026-01-12 00:51:25','2026-01-12 00:52:15'),(5,4,'BTCUSDT','long',0.00000000,90804.80000000,90804.80000000,NULL,82178.34400000,809654.37953008,10,0.00000000,-527.14273672,'cross',NULL,NULL,'closed','2026-01-12 00:57:25','2026-01-12 01:00:43','2026-01-12 00:57:25','2026-01-12 01:00:43'),(6,4,'BTCUSDT','short',0.00000000,90853.40000000,90853.40000000,NULL,99484.47300000,618792.64072076,10,0.00000000,-3964.91002791,'cross',NULL,NULL,'closed','2026-01-12 01:00:47','2026-01-12 01:00:51','2026-01-12 01:00:47','2026-01-12 01:00:51'),(7,4,'BTCUSDT','long',0.00000000,90864.00000000,90864.00000000,NULL,82231.92000000,618864.83616960,10,0.00000000,-3713.18901702,'cross',NULL,NULL,'closed','2026-01-12 01:01:05','2026-01-12 01:01:09','2026-01-12 01:01:05','2026-01-12 01:01:09'),(8,4,'BTCUSDT','short',0.00000000,90896.60000000,90896.60000000,NULL,99531.77700000,619086.87122924,10,0.00000000,-3707.70624944,'cross',NULL,NULL,'closed','2026-01-12 01:01:38','2026-01-12 01:01:41','2026-01-12 01:01:38','2026-01-12 01:01:41'),(9,4,'BTCUSDT','short',0.00000000,90894.60000000,90894.60000000,NULL,99529.58700000,519281.34063084,10,0.00000000,-4104.63098794,'cross',NULL,NULL,'closed','2026-01-12 01:01:57','2026-01-12 01:02:04','2026-01-12 01:01:57','2026-01-12 01:02:04'),(10,4,'BTCUSDT','long',0.00000000,90901.50000000,90901.50000000,NULL,82265.85750000,9.09015000,10,0.00000000,-0.00616994,'cross',NULL,NULL,'closed','2026-01-12 01:06:24','2026-01-12 01:09:48','2026-01-12 01:06:24','2026-01-12 01:09:48'),(11,4,'BTCUSDT','long',0.00000000,90911.10000000,90911.10000000,NULL,82274.54550000,304609.24989747,10,0.00000000,-1737.24283165,'cross',NULL,NULL,'closed','2026-01-12 01:19:54','2026-01-12 01:20:56','2026-01-12 01:19:54','2026-01-12 01:20:56'),(12,4,'BTCUSDT','long',0.00000000,90913.80000000,90913.80000000,NULL,82276.98900000,304618.29659226,10,0.00000000,-1827.70977955,'cross',NULL,NULL,'closed','2026-01-12 01:21:09','2026-01-12 01:21:13','2026-01-12 01:21:09','2026-01-12 01:21:13'),(13,4,'BTCUSDT','long',0.00000000,90979.42000000,90979.42000000,NULL,82336.37510000,645382.84967037,10,0.00000000,1918.37976477,'cross',NULL,NULL,'closed','2026-01-12 01:24:13','2026-01-12 01:41:38','2026-01-12 01:24:13','2026-01-12 01:41:38'),(14,4,'BTCUSDT','long',0.00000000,91069.60000000,91069.60000000,NULL,82417.98800000,457260.47981392,10,0.00000000,-2176.53028010,'cross',NULL,NULL,'closed','2026-01-12 01:42:35','2026-01-12 01:45:03','2026-01-12 01:42:35','2026-01-12 01:45:03'),(15,4,'BTCUSDT','long',0.00000000,91081.20000000,91081.20000000,NULL,82428.48600000,598978.47961560,10,0.00000000,-4343.12073988,'cross',NULL,NULL,'closed','2026-01-12 01:45:20','2026-01-12 01:48:15','2026-01-12 01:45:20','2026-01-12 01:48:15'),(16,4,'BTCUSDT','long',64.84369900,91009.90000000,91009.90000000,90928.30000000,82363.95950000,590141.85616201,10,0.00000000,-5291.24583840,'cross',NULL,NULL,'liquidated','2026-01-12 01:56:06','2026-01-12 02:49:33','2026-01-12 01:56:06','2026-01-12 02:49:33'),(17,4,'BTCUSDT','long',0.00000000,90912.87541938,90928.40000000,NULL,82290.20200000,292087.93172872,10,0.00000000,-4281.93210014,'cross',NULL,NULL,'closed','2026-01-12 02:50:09','2026-01-12 04:17:19','2026-01-12 02:50:09','2026-01-12 04:17:19'),(18,4,'BTCUSDT','long',0.00000000,90808.40000000,90808.40000000,NULL,82181.60200000,165027.04064652,10,0.00000000,-327.11585400,'cross',NULL,NULL,'closed','2026-01-12 04:36:11','2026-01-12 04:39:14','2026-01-12 04:36:11','2026-01-12 04:39:14'),(19,1,'BTCUSDT','long',0.00000000,90460.70000000,90460.70000000,NULL,81866.93350000,4735.95234959,10,0.00000000,179.10200770,'cross',NULL,NULL,'closed','2026-01-12 08:12:09','2026-01-12 08:22:03','2026-01-12 08:12:09','2026-01-12 08:22:03'),(20,1,'BTCUSDT','long',0.00000000,90707.30000000,90707.30000000,NULL,82090.10650000,5398.15469614,10,0.00000000,-9.58139980,'cross',NULL,NULL,'closed','2026-01-12 08:39:59','2026-01-12 08:40:10','2026-01-12 08:39:59','2026-01-12 08:40:10'),(21,1,'BTCUSDT','long',0.00000000,90707.30000000,90707.30000000,NULL,82090.10650000,5398.15469614,10,0.00000000,0.05951180,'cross',NULL,NULL,'closed','2026-01-12 08:40:15','2026-01-12 08:40:21','2026-01-12 08:40:15','2026-01-12 08:40:21'),(22,1,'BTCUSDT','long',0.00000000,90878.50000000,90878.50000000,NULL,82245.04250000,9.08785000,10,0.00000000,0.00460000,'cross',NULL,NULL,'closed','2026-01-12 08:48:17','2026-01-12 08:48:34','2026-01-12 08:48:17','2026-01-12 08:48:34'),(23,4,'BTCUSDT','long',0.00000000,91618.20000000,91618.20000000,NULL,82914.47100000,198787.64578254,10,0.00000000,-1703.24566450,'cross',NULL,NULL,'closed','2026-01-12 10:05:44','2026-01-12 10:12:12','2026-01-12 10:05:44','2026-01-12 10:12:12'),(24,4,'BTCUSDT','long',0.00000000,91470.00000000,91470.00000000,NULL,82780.35000000,198466.09035900,10,0.00000000,-45.56453370,'cross',NULL,NULL,'closed','2026-01-12 10:13:12','2026-01-12 10:14:27','2026-01-12 10:13:12','2026-01-12 10:14:27'),(25,4,'BTCUSDT','long',0.00000000,91400.00000000,91400.00000000,NULL,82717.00000000,198314.20858000,10,0.00000000,-692.14696430,'cross',NULL,NULL,'closed','2026-01-12 10:15:20','2026-01-12 10:16:08','2026-01-12 10:15:20','2026-01-12 10:16:08'),(26,4,'BTCUSDT','long',0.00000000,91421.00000000,91421.00000000,NULL,82736.00500000,198359.77311370,10,0.00000000,-561.96258230,'cross',NULL,NULL,'closed','2026-01-12 10:18:51','2026-01-12 10:19:10','2026-01-12 10:18:51','2026-01-12 10:19:10'),(27,4,'BTCUSDT','long',0.00000000,91380.20000000,91380.20000000,NULL,82699.08100000,198271.24773394,10,0.00000000,-2.16973970,'cross',NULL,NULL,'closed','2026-01-12 10:19:15','2026-01-12 10:19:17','2026-01-12 10:19:15','2026-01-12 10:19:17'),(28,4,'BTCUSDT','long',0.00000000,91426.30000000,91426.30000000,NULL,82740.80150000,198371.27273411,10,0.00000000,184.42787450,'cross',NULL,NULL,'closed','2026-01-12 10:19:50','2026-01-12 10:19:53','2026-01-12 10:19:50','2026-01-12 10:19:53'),(29,4,'BTCUSDT','long',0.00000000,91400.10000000,91400.10000000,NULL,82717.09050000,198314.42555397,10,0.00000000,596.67841750,'cross',NULL,NULL,'closed','2026-01-12 10:20:38','2026-01-12 10:20:41','2026-01-12 10:20:38','2026-01-12 10:20:41'),(30,4,'BTCUSDT','long',0.00000000,91484.30000000,91484.30000000,NULL,82793.29150000,150225.08824980,10,0.00000000,-445.00530600,'cross',NULL,NULL,'closed','2026-01-12 10:21:14','2026-01-12 10:21:25','2026-01-12 10:21:14','2026-01-12 10:21:25'),(31,4,'BTCUSDT','long',0.00000000,91454.30000000,91454.30000000,NULL,82766.14150000,150175.82566980,10,0.00000000,-591.15096000,'cross',NULL,NULL,'closed','2026-01-12 10:21:53','2026-01-12 10:22:04','2026-01-12 10:21:53','2026-01-12 10:22:04'),(32,4,'BTCUSDT','long',0.00000000,91439.90000000,91439.90000000,NULL,82753.10950000,150152.17963140,10,0.00000000,1.64208600,'cross',NULL,NULL,'closed','2026-01-12 10:22:07','2026-01-12 10:22:10','2026-01-12 10:22:07','2026-01-12 10:22:10'),(33,4,'BTCUSDT','long',0.00000000,91444.70000000,91444.70000000,NULL,82757.45350000,150160.06164420,10,0.00000000,428.58444600,'cross',NULL,NULL,'closed','2026-01-12 10:22:54','2026-01-12 10:22:58','2026-01-12 10:22:54','2026-01-12 10:22:58'),(34,4,'BTCUSDT','long',0.00000000,91483.50000000,91483.50000000,NULL,82792.56750000,131256.05225685,10,0.00000000,426.12107670,'cross',NULL,NULL,'closed','2026-01-12 10:23:04','2026-01-12 10:23:26','2026-01-12 10:23:04','2026-01-12 10:23:26'),(35,4,'BTCUSDT','long',0.00000000,91539.10000000,91539.10000000,NULL,82842.88550000,162655.23178585,10,0.00000000,-339.38665850,'cross',NULL,NULL,'closed','2026-01-12 10:23:35','2026-01-12 10:24:05','2026-01-12 10:23:35','2026-01-12 10:24:05'),(36,4,'BTCUSDT','long',0.00000000,91519.90000000,91519.90000000,NULL,82825.50950000,162621.11543065,10,0.00000000,120.82875800,'cross',NULL,NULL,'closed','2026-01-12 10:24:09','2026-01-12 10:24:12','2026-01-12 10:24:09','2026-01-12 10:24:12'),(37,4,'BTCUSDT','long',0.00000000,91479.50000000,91479.50000000,NULL,82788.94750000,162549.32893325,10,0.00000000,-1723.58669500,'cross',NULL,NULL,'closed','2026-01-12 10:25:04','2026-01-12 10:27:02','2026-01-12 10:25:04','2026-01-12 10:27:02'),(38,4,'BTCUSDT','long',0.00000000,91386.20000000,91386.20000000,NULL,82704.51100000,162383.54476970,10,0.00000000,764.06420500,'cross',NULL,NULL,'closed','2026-01-12 10:27:34','2026-01-12 10:28:09','2026-01-12 10:27:34','2026-01-12 10:28:09'),(39,4,'BTCUSDT','long',0.00000000,91421.00000000,91421.00000000,NULL,82736.00500000,159776.39027900,10,0.00000000,17.47699000,'cross',NULL,NULL,'closed','2026-01-12 10:28:21','2026-01-12 10:28:27','2026-01-12 10:28:21','2026-01-12 10:28:27'),(40,4,'BTCUSDT','long',0.00000000,91465.80000000,91465.80000000,NULL,82776.54900000,159854.68719420,10,0.00000000,-396.72767300,'cross',NULL,NULL,'closed','2026-01-12 10:28:33','2026-01-12 10:28:37','2026-01-12 10:28:33','2026-01-12 10:28:37'),(41,4,'BTCUSDT','long',0.00000000,91428.30000000,91428.30000000,NULL,82742.61150000,159789.14848170,10,0.00000000,-90.88034800,'cross',NULL,NULL,'closed','2026-01-12 10:28:42','2026-01-12 10:28:47','2026-01-12 10:28:42','2026-01-12 10:28:47'),(42,4,'BTCUSDT','long',0.00000000,91421.00000000,91421.00000000,NULL,82736.00500000,159776.39027900,10,0.00000000,360.02599400,'cross',NULL,NULL,'closed','2026-01-12 10:28:57','2026-01-12 10:29:17','2026-01-12 10:28:57','2026-01-12 10:29:17'),(43,4,'BTCUSDT','long',0.00000000,91455.90000000,91455.90000000,NULL,82767.58950000,221073.96921660,10,0.00000000,657.49852800,'cross',NULL,NULL,'closed','2026-01-12 10:29:35','2026-01-12 10:29:50','2026-01-12 10:29:35','2026-01-12 10:29:50'),(44,4,'BTCUSDT','long',0.00000000,91368.40000000,91368.40000000,NULL,82688.40200000,220862.45774160,10,0.00000000,-203.05101600,'cross',NULL,NULL,'closed','2026-01-12 10:30:40','2026-01-12 10:30:55','2026-01-12 10:30:40','2026-01-12 10:30:55'),(45,4,'BTCUSDT','long',0.00000000,91436.30000000,91436.30000000,NULL,82749.85150000,256692.16981879,10,0.00000000,-8287.24790160,'cross',NULL,NULL,'closed','2026-01-12 10:32:58','2026-01-12 10:37:01','2026-01-12 10:32:58','2026-01-12 10:37:01'),(46,4,'BTCUSDT','long',0.00000000,91382.60000000,91382.60000000,NULL,82701.25300000,157261.05653906,10,0.00000000,-1.72090810,'cross',NULL,NULL,'closed','2026-01-12 10:53:37','2026-01-12 10:53:42','2026-01-12 10:53:37','2026-01-12 10:53:42'),(47,4,'BTCUSDT','long',0.00000000,91385.80000000,91385.80000000,NULL,82704.14900000,157239.52238676,10,0.00000000,153.13448580,'cross',NULL,NULL,'closed','2026-01-12 11:00:04','2026-01-12 11:00:20','2026-01-12 11:00:04','2026-01-12 11:00:20'),(48,4,'BTCUSDT','short',0.00000000,91400.00000000,91400.00000000,NULL,100083.00000000,78972.56136000,10,0.00000000,29.37710160,'cross',NULL,NULL,'closed','2026-01-12 11:00:49','2026-01-12 11:00:59','2026-01-12 11:00:49','2026-01-12 11:00:59'),(49,4,'ETHUSDT','short',0.00000000,3146.68574016,3148.59000000,NULL,3447.70605000,305709.18216803,10,0.00000000,112.44475165,'cross',NULL,NULL,'closed','2026-01-12 11:17:11','2026-01-12 11:42:55','2026-01-12 11:17:11','2026-01-12 11:42:55'),(50,4,'SOLUSDT','short',0.00000000,141.96000000,141.96000000,NULL,155.44620000,5.57888604,10,0.00000000,-1181.65412170,'cross',NULL,NULL,'closed','2026-01-12 11:39:44','2026-01-12 11:43:21','2026-01-12 11:39:44','2026-01-12 11:43:21'),(51,4,'ETHUSDT','short',0.00000000,3150.10000000,3150.10000000,NULL,3449.35950000,191170.76951066,10,0.00000000,-5334.40546014,'cross',NULL,NULL,'closed','2026-01-12 11:43:49','2026-01-12 11:47:25','2026-01-12 11:43:49','2026-01-12 11:47:25'),(52,4,'BTCUSDT','short',0.00000000,92145.60000000,92145.60000000,NULL,100899.43200000,153495.54153360,10,0.00000000,-169.91093700,'cross',NULL,NULL,'closed','2026-01-12 11:49:50','2026-01-12 11:49:55','2026-01-12 11:49:50','2026-01-12 11:49:55'),(53,4,'BTCUSDT','short',0.00000000,92151.30000000,92151.30000000,NULL,100905.67350000,153505.03655655,10,0.00000000,-541.38288750,'cross',NULL,NULL,'closed','2026-01-12 11:49:58','2026-01-12 11:50:05','2026-01-12 11:49:58','2026-01-12 11:50:05'),(54,4,'BTCUSDT','short',0.00000000,92155.00000000,92155.00000000,NULL,100909.72500000,153511.19999250,10,0.00000000,-1299.31893000,'cross',NULL,NULL,'closed','2026-01-12 11:50:12','2026-01-12 11:50:19','2026-01-12 11:50:12','2026-01-12 11:50:19'),(55,4,'BTCUSDT','short',16.65793500,92260.00000000,92260.00000000,NULL,101024.70000000,153686.10831000,10,0.00000000,0.00000000,'cross',NULL,NULL,'open','2026-01-12 11:51:04',NULL,'2026-01-12 11:51:04','2026-01-12 11:51:04'),(56,4,'ETHUSDT','short',204.76269500,3164.54000000,3164.54000000,NULL,3465.17130000,64797.97388353,10,0.00000000,0.00000000,'cross',NULL,NULL,'open','2026-01-12 11:55:25',NULL,'2026-01-12 11:55:25','2026-01-12 11:55:25'),(57,4,'XRPUSDT','long',308647.03250400,2.08520000,2.08520000,NULL,1.88710600,64359.07921773,10,0.00000000,0.00000000,'cross',NULL,NULL,'open','2026-01-12 13:08:40',NULL,'2026-01-12 13:08:40','2026-01-12 13:08:40');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` enum('string','number','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','FuturX','string','general','사이트 이름','2026-01-12 01:53:16'),(2,'site_description','암호화폐 선물거래 플랫폼','string','general','사이트 설명','2026-01-12 01:53:16'),(3,'maintenance_mode','false','boolean','general','점검 모드','2026-01-12 01:53:16'),(4,'max_leverage','125','number','trading','최대 레버리지','2026-01-12 01:53:16'),(5,'taker_fee','0.0000','number','trading','테이커 수수료','2026-01-12 04:34:55'),(6,'maker_fee','0.0002','number','trading','메이커 수수료','2026-01-12 01:53:16'),(7,'min_withdraw','10','number','wallet','최소 출금액','2026-01-12 01:53:16'),(8,'max_withdraw_daily','100000','number','wallet','일일 최대 출금액','2026-01-12 01:53:16'),(9,'registration_enabled','true','boolean','auth','회원가입 허용','2026-01-12 01:53:16'),(10,'kyc_required','false','boolean','auth','KYC 필수 여부','2026-01-12 01:53:16');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tournament_announcements`
--

DROP TABLE IF EXISTS `tournament_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournament_announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tournament_id` varchar(32) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `type` enum('info','warning','update','result') DEFAULT 'info',
  `is_pinned` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tournament` (`tournament_id`),
  KEY `idx_pinned` (`is_pinned`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tournament_announcements`
--

LOCK TABLES `tournament_announcements` WRITE;
/*!40000 ALTER TABLE `tournament_announcements` DISABLE KEYS */;
INSERT INTO `tournament_announcements` VALUES (1,'WEEKLY_001','대회 시작 안내','주간 트레이딩 챔피언십이 시작되었습니다. 모든 참가자분들의 건승을 빕니다!','info',1,'2026-01-11 18:10:25'),(2,'WEEKLY_001','중간 순위 발표','현재 1위는 25%의 수익률을 기록 중입니다. 아직 기회는 많습니다!','update',0,'2026-01-11 18:10:25'),(3,NULL,'1월 마스터 토너먼트 참가자 모집','역대 최대 규모의 토너먼트가 곧 시작됩니다. 지금 바로 등록하세요!','info',1,'2026-01-11 18:10:25');
/*!40000 ALTER TABLE `tournament_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tournament_participants`
--

DROP TABLE IF EXISTS `tournament_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournament_participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tournament_id` varchar(32) NOT NULL,
  `user_id` int NOT NULL,
  `tournament_balance` decimal(20,8) DEFAULT '0.00000000',
  `starting_balance` decimal(20,8) DEFAULT '0.00000000',
  `pnl` decimal(20,8) DEFAULT '0.00000000',
  `pnl_percent` decimal(10,4) DEFAULT '0.0000',
  `total_trades` int DEFAULT '0',
  `winning_trades` int DEFAULT '0',
  `losing_trades` int DEFAULT '0',
  `total_volume` decimal(30,8) DEFAULT '0.00000000',
  `max_drawdown` decimal(10,4) DEFAULT '0.0000',
  `current_rank` int DEFAULT '0',
  `best_rank` int DEFAULT '0',
  `final_rank` int DEFAULT '0',
  `prize_amount` decimal(20,8) DEFAULT '0.00000000',
  `prize_claimed` tinyint(1) DEFAULT '0',
  `prize_claimed_at` datetime DEFAULT NULL,
  `status` enum('registered','active','disqualified','completed') DEFAULT 'registered',
  `disqualify_reason` varchar(255) DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_trade_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`tournament_id`,`user_id`),
  KEY `idx_tournament` (`tournament_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_pnl_percent` (`pnl_percent` DESC),
  KEY `idx_current_rank` (`current_rank`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tournament_participants`
--

LOCK TABLES `tournament_participants` WRITE;
/*!40000 ALTER TABLE `tournament_participants` DISABLE KEYS */;
INSERT INTO `tournament_participants` VALUES (1,'WEEKLY_001',1,12500.00000000,10000.00000000,2500.00000000,25.0000,48,32,16,850000.00000000,0.0000,1,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(2,'WEEKLY_001',2,11800.00000000,10000.00000000,1800.00000000,18.0000,35,22,13,620000.00000000,0.0000,2,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(3,'WEEKLY_001',3,11200.00000000,10000.00000000,1200.00000000,12.0000,52,30,22,980000.00000000,0.0000,3,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(4,'WEEKLY_001',4,10800.00000000,10000.00000000,800.00000000,8.0000,28,16,12,420000.00000000,0.0000,4,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(5,'WEEKLY_001',5,10500.00000000,10000.00000000,500.00000000,5.0000,41,23,18,550000.00000000,0.0000,5,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(6,'WEEKLY_001',6,10200.00000000,10000.00000000,200.00000000,2.0000,19,11,8,280000.00000000,0.0000,6,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(7,'WEEKLY_001',7,9800.00000000,10000.00000000,-200.00000000,-2.0000,33,15,18,470000.00000000,0.0000,7,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(8,'WEEKLY_001',8,9500.00000000,10000.00000000,-500.00000000,-5.0000,25,10,15,380000.00000000,0.0000,8,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(9,'WEEKLY_001',9,9200.00000000,10000.00000000,-800.00000000,-8.0000,44,18,26,720000.00000000,0.0000,9,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(10,'WEEKLY_001',10,8800.00000000,10000.00000000,-1200.00000000,-12.0000,38,14,24,510000.00000000,0.0000,10,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(11,'SPECIAL_NEWBIE',1,1150.00000000,1000.00000000,150.00000000,15.0000,12,8,4,45000.00000000,0.0000,1,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(12,'SPECIAL_NEWBIE',2,1080.00000000,1000.00000000,80.00000000,8.0000,8,5,3,32000.00000000,0.0000,2,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL),(13,'SPECIAL_NEWBIE',3,1020.00000000,1000.00000000,20.00000000,2.0000,15,8,7,58000.00000000,0.0000,3,0,0,0.00000000,0,NULL,'active',NULL,'2026-01-11 18:10:17',NULL);
/*!40000 ALTER TABLE `tournament_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tournament_rewards`
--

DROP TABLE IF EXISTS `tournament_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournament_rewards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tournament_id` varchar(32) NOT NULL,
  `user_id` int NOT NULL,
  `reward_type` enum('prize','badge','title','bonus') NOT NULL,
  `rank_achieved` int DEFAULT NULL,
  `amount` decimal(20,8) DEFAULT '0.00000000',
  `currency` varchar(10) DEFAULT NULL,
  `badge_id` varchar(50) DEFAULT NULL,
  `title_text` varchar(100) DEFAULT NULL,
  `status` enum('pending','claimed','expired') DEFAULT 'pending',
  `claimed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tournament` (`tournament_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tournament_rewards`
--

LOCK TABLES `tournament_rewards` WRITE;
/*!40000 ALTER TABLE `tournament_rewards` DISABLE KEYS */;
/*!40000 ALTER TABLE `tournament_rewards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tournaments`
--

DROP TABLE IF EXISTS `tournaments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournaments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tournament_id` varchar(32) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(200) DEFAULT NULL,
  `description` text,
  `rules` text,
  `type` enum('daily','weekly','monthly','special') DEFAULT 'weekly',
  `status` enum('upcoming','registration','active','calculating','ended') DEFAULT 'upcoming',
  `registration_start` datetime NOT NULL,
  `registration_end` datetime NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `entry_fee` decimal(20,8) DEFAULT '0.00000000',
  `min_balance` decimal(20,8) DEFAULT '100.00000000',
  `max_participants` int DEFAULT '0',
  `allowed_symbols` json DEFAULT NULL,
  `starting_balance` decimal(20,8) DEFAULT '10000.00000000',
  `prize_pool` decimal(20,8) DEFAULT '0.00000000',
  `prize_currency` varchar(10) DEFAULT 'USDT',
  `prize_distribution` json DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `badge_icon` varchar(50) DEFAULT NULL,
  `badge_color` varchar(20) DEFAULT NULL,
  `total_participants` int DEFAULT '0',
  `total_trades` int DEFAULT '0',
  `total_volume` decimal(30,8) DEFAULT '0.00000000',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tournament_id` (`tournament_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_end_time` (`end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tournaments`
--

LOCK TABLES `tournaments` WRITE;
/*!40000 ALTER TABLE `tournaments` DISABLE KEYS */;
INSERT INTO `tournaments` VALUES (1,'WEEKLY_001','주간 트레이딩 챔피언십','매주 열리는 실력 검증의 장','매주 월요일부터 일요일까지 진행되는 트레이딩 대회입니다.\n\n참가 방법:\n1. 등록 기간 내 참가 신청\n2. 대회 시작 시 자동 시작 자금 지급\n3. 실시간 순위 확인 및 거래\n\n순위 산정: 수익률(PnL %) 기준, 최소 5회 이상 거래 필수',NULL,'weekly','active','2026-01-09 03:10:06','2026-01-11 03:10:06','2026-01-11 03:10:06','2026-01-18 03:10:06',0.00000000,100.00000000,0,NULL,10000.00000000,50000.00000000,'USDT','[{\"rank\": 1, \"title\": \"1위\", \"percent\": 40}, {\"rank\": 2, \"title\": \"2위\", \"percent\": 25}, {\"rank\": 3, \"title\": \"3위\", \"percent\": 15}, {\"title\": \"4-10위\", \"percent\": 15, \"rank_to\": 10, \"rank_from\": 4}, {\"title\": \"11-50위\", \"percent\": 5, \"rank_to\": 50, \"rank_from\": 11}]',NULL,'trophy','#f0b90b',156,4520,85000000.00000000,'2026-01-11 18:10:06','2026-01-11 18:10:06'),(2,'MONTHLY_JAN','1월 마스터 토너먼트','새해 첫 대형 대회 - 총 상금 500,000 USDT','2026년 새해를 맞아 진행되는 대형 토너먼트입니다.\n\n특별 보상:\n- 1위: 200,000 USDT + 골드 트로피 뱃지\n- 2위: 100,000 USDT + 실버 트로피 뱃지\n- 3위: 50,000 USDT + 브론즈 트로피 뱃지\n\n참가 조건: 최소 잔고 500 USDT 이상, 본인인증 완료 회원',NULL,'monthly','registration','2026-01-12 03:10:06','2026-01-17 03:10:06','2026-01-17 03:10:06','2026-02-16 03:10:06',10.00000000,500.00000000,1000,NULL,50000.00000000,500000.00000000,'USDT','[{\"rank\": 1, \"title\": \"챔피언\", \"amount\": 200000, \"percent\": 40}, {\"rank\": 2, \"title\": \"준우승\", \"amount\": 100000, \"percent\": 20}, {\"rank\": 3, \"title\": \"3위\", \"amount\": 50000, \"percent\": 10}, {\"title\": \"Top 10\", \"percent\": 14, \"rank_to\": 10, \"rank_from\": 4}, {\"title\": \"Top 50\", \"percent\": 10, \"rank_to\": 50, \"rank_from\": 11}, {\"title\": \"Top 100\", \"percent\": 6, \"rank_to\": 100, \"rank_from\": 51}]',NULL,'crown','#ffd700',89,0,0.00000000,'2026-01-11 18:10:06','2026-01-11 18:10:06'),(3,'DAILY_SPRINT','데일리 스프린트','24시간 단기 승부','매일 진행되는 24시간 단기 토너먼트입니다. 빠른 판단력과 실행력이 필요합니다.',NULL,'daily','upcoming','2026-01-13 03:10:06','2026-01-13 03:10:06','2026-01-13 03:10:06','2026-01-14 03:10:06',0.00000000,50.00000000,500,NULL,5000.00000000,10000.00000000,'USDT','[{\"rank\": 1, \"percent\": 50}, {\"rank\": 2, \"percent\": 30}, {\"rank\": 3, \"percent\": 20}]',NULL,'bolt','#00d4ff',0,0,0.00000000,'2026-01-11 18:10:06','2026-01-11 18:10:06'),(4,'SPECIAL_NEWBIE','신규회원 웰컴 토너먼트','가입 30일 이내 회원 전용','신규 회원들을 위한 특별 토너먼트입니다. 부담 없이 참여하세요!',NULL,'special','active','2026-01-02 03:10:06','2026-01-09 03:10:06','2026-01-09 03:10:06','2026-01-16 03:10:06',0.00000000,0.00000000,0,NULL,1000.00000000,5000.00000000,'USDT','[{\"rank\": 1, \"percent\": 40}, {\"rank\": 2, \"percent\": 30}, {\"rank\": 3, \"percent\": 20}, {\"percent\": 10, \"rank_to\": 10, \"rank_from\": 4}]',NULL,'gift','#ff6b6b',45,890,12000000.00000000,'2026-01-11 18:10:06','2026-01-11 18:10:06');
/*!40000 ALTER TABLE `tournaments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trades`
--

DROP TABLE IF EXISTS `trades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_id` int DEFAULT NULL,
  `symbol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BTCUSDT',
  `side` enum('buy','sell') COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_side` enum('long','short') COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(20,8) NOT NULL,
  `quantity` decimal(20,8) NOT NULL,
  `quote_quantity` decimal(20,8) NOT NULL,
  `fee` decimal(20,8) DEFAULT '0.00000000',
  `fee_currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USDT',
  `realized_pnl` decimal(20,8) DEFAULT '0.00000000',
  `is_maker` tinyint(1) DEFAULT '0',
  `executed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_position_id` (`position_id`),
  KEY `idx_symbol` (`symbol`),
  CONSTRAINT `trades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trades`
--

LOCK TABLES `trades` WRITE;
/*!40000 ALTER TABLE `trades` DISABLE KEYS */;
INSERT INTO `trades` VALUES (1,1,'ORD20260111225741EF4459ED',1,'BTCUSDT','buy','long',90800.00000000,0.00100000,90.80000000,0.05448000,'USDT',0.00000000,0,'2026-01-11 22:57:41','2026-01-11 22:57:41'),(2,4,'ORD20260111231640CD073BA4',2,'BTCUSDT','buy','long',90886.00000000,13.66419900,1241884.39031400,745.13063419,'USDT',0.00000000,0,'2026-01-11 23:16:40','2026-01-11 23:16:40'),(3,4,'ORD20260111235919F51BD571',2,'BTCUSDT','buy','long',91128.00000000,19.90767900,1814146.97191200,1088.48818315,'USDT',0.00000000,0,'2026-01-11 23:59:19','2026-01-11 23:59:19'),(4,4,'ORD20260111235926A0E0F627',3,'BTCUSDT','sell','short',91100.00000000,19.90767900,1813589.55690000,1088.15373414,'USDT',0.00000000,0,'2026-01-11 23:59:26','2026-01-11 23:59:26'),(5,4,'ORD20260112004112C45853EB',2,'BTCUSDT','sell','long',90971.00000000,33.57187800,3054067.31353800,1832.44038812,'USDT',-3796.48907613,0,'2026-01-12 00:41:12','2026-01-12 00:41:12'),(6,4,'ORD2026011200414666477D17',3,'BTCUSDT','buy','short',90942.10000000,19.90767900,1810446.13438590,1086.26768063,'USDT',2057.15483347,0,'2026-01-12 00:41:46','2026-01-12 00:41:46'),(7,4,'ORD20260112005125315352A2',4,'BTCUSDT','buy','long',90742.30000000,96.10609300,8720887.92283390,5232.53275370,'USDT',0.00000000,0,'2026-01-12 00:51:25','2026-01-12 00:51:25'),(8,4,'ORD20260112005215E4616785',4,'BTCUSDT','sell','long',90749.90000000,96.10609300,8721618.32914070,5232.97099748,'USDT',-4502.56469069,0,'2026-01-12 00:52:15','2026-01-12 00:52:15'),(9,4,'ORD20260112005725E79E7BC4',5,'BTCUSDT','buy','long',90804.80000000,89.16427100,8096543.79530080,4857.92627718,'USDT',0.00000000,0,'2026-01-12 00:57:25','2026-01-12 00:57:25'),(10,4,'ORD20260112010043B04FC86C',5,'BTCUSDT','sell','long',90853.40000000,89.16427100,8100877.17887140,4860.52630732,'USDT',-527.14273672,0,'2026-01-12 01:00:43','2026-01-12 01:00:43'),(11,4,'ORD20260112010047C501DFBB',6,'BTCUSDT','sell','short',90853.40000000,68.10891400,6187926.40720760,3712.75584432,'USDT',0.00000000,0,'2026-01-12 01:00:47','2026-01-12 01:00:47'),(12,4,'ORD20260112010051C0897823',6,'BTCUSDT','buy','short',90857.10000000,68.10891400,6188178.41018940,3712.90704611,'USDT',-3964.91002791,0,'2026-01-12 01:00:51','2026-01-12 01:00:51'),(13,4,'ORD202601120101059D1A9158',7,'BTCUSDT','buy','long',90864.00000000,68.10891400,6188648.36169600,3713.18901702,'USDT',0.00000000,0,'2026-01-12 01:01:05','2026-01-12 01:01:05'),(14,4,'ORD2026011201010940F66076',7,'BTCUSDT','sell','long',90864.00000000,68.10891400,6188648.36169600,3713.18901702,'USDT',-3713.18901702,0,'2026-01-12 01:01:09','2026-01-12 01:01:09'),(15,4,'ORD202601120101388FA876C9',8,'BTCUSDT','sell','short',90896.60000000,68.10891400,6190868.71229240,3714.52122738,'USDT',0.00000000,0,'2026-01-12 01:01:38','2026-01-12 01:01:38'),(16,4,'ORD2026011201014157DE67E5',8,'BTCUSDT','buy','short',90896.50000000,68.10891400,6190861.90140100,3714.51714084,'USDT',-3707.70624944,0,'2026-01-12 01:01:41','2026-01-12 01:01:41'),(17,4,'ORD20260112010157DC9E177F',9,'BTCUSDT','sell','short',90894.60000000,57.13005400,5192813.40630840,3115.68804379,'USDT',0.00000000,0,'2026-01-12 01:01:57','2026-01-12 01:01:57'),(18,4,'ORD202601120102040AEDECED',9,'BTCUSDT','buy','short',90911.90000000,57.13005400,5193801.75624260,3116.28105375,'USDT',-4104.63098794,0,'2026-01-12 01:02:04','2026-01-12 01:02:04'),(19,4,'ORD20260112010624C0C68A45',10,'BTCUSDT','buy','long',90901.50000000,0.00100000,90.90150000,0.05454090,'USDT',0.00000000,0,'2026-01-12 01:06:24','2026-01-12 01:06:24'),(20,4,'ORD2026011201094887E010BA',10,'BTCUSDT','sell','long',90949.90000000,0.00100000,90.94990000,0.05456994,'USDT',-0.00616994,0,'2026-01-12 01:09:48','2026-01-12 01:09:48'),(21,4,'ORD20260112011954DB11C58E',11,'BTCUSDT','buy','long',90911.10000000,33.50627700,3046092.49897470,1827.65549938,'USDT',0.00000000,0,'2026-01-12 01:19:54','2026-01-12 01:19:54'),(22,4,'ORD2026011201205697729A66',11,'BTCUSDT','sell','long',90913.80000000,33.50627700,3046182.96592260,1827.70977955,'USDT',-1737.24283165,0,'2026-01-12 01:20:56','2026-01-12 01:20:56'),(23,4,'ORD202601120121091D80D767',12,'BTCUSDT','buy','long',90913.80000000,33.50627700,3046182.96592260,1827.70977955,'USDT',0.00000000,0,'2026-01-12 01:21:09','2026-01-12 01:21:09'),(24,4,'ORD20260112012113DC2BBC2D',12,'BTCUSDT','sell','long',90913.80000000,33.50627700,3046182.96592260,1827.70977955,'USDT',-1827.70977955,0,'2026-01-12 01:21:13','2026-01-12 01:21:13'),(25,4,'ORD20260112012413BA1556DF',13,'BTCUSDT','buy','long',90979.42000000,70.93723500,6453828.49670370,3872.29709802,'USDT',0.00000000,0,'2026-01-12 01:24:13','2026-01-12 01:24:13'),(26,1,'ORD202601120136407AB9DC8A',1,'BTCUSDT','buy','long',91142.70000000,0.00100000,91.14270000,0.05468562,'USDT',0.00000000,0,'2026-01-12 01:36:40','2026-01-12 01:36:40'),(27,4,'ORD202601120141385F808553',13,'BTCUSDT','sell','long',91061.10000000,70.93723500,6459622.65005850,3875.77359004,'USDT',1918.37976477,0,'2026-01-12 01:41:38','2026-01-12 01:41:38'),(28,4,'ORD202601120142359B176707',14,'BTCUSDT','buy','long',91069.60000000,50.21000200,4572604.79813920,2743.56287888,'USDT',0.00000000,0,'2026-01-12 01:42:35','2026-01-12 01:42:35'),(29,4,'ORD20260112014503EC62C07F',14,'BTCUSDT','sell','long',91080.90000000,50.21000200,4573172.17116180,2743.90330270,'USDT',-2176.53028010,0,'2026-01-12 01:45:03','2026-01-12 01:45:03'),(30,4,'ORD202601120145204AAEE1E4',15,'BTCUSDT','buy','long',91081.20000000,65.76313000,5989784.79615600,3593.87087769,'USDT',0.00000000,0,'2026-01-12 01:45:20','2026-01-12 01:45:20'),(31,4,'ORD20260112014815FADA21FA',15,'BTCUSDT','sell','long',91069.80000000,65.76313000,5989035.09647400,3593.42105788,'USDT',-4343.12073988,0,'2026-01-12 01:48:15','2026-01-12 01:48:15'),(32,4,'ORD202601120156060ADBC65F',16,'BTCUSDT','buy','long',91009.90000000,64.84369900,5901418.56162010,3540.85113697,'USDT',0.00000000,0,'2026-01-12 01:56:06','2026-01-12 01:56:06'),(33,4,'LIQ_16_1768153773',16,'BTCUSDT','sell','long',90928.30000000,64.84369900,5896127.31578170,0.00000000,'USDT',-5291.24583840,0,'2026-01-12 02:49:33','2026-01-12 02:49:33'),(34,4,'ORD20260112025009C7BAA650',17,'BTCUSDT','buy','long',90928.40000000,26.38203400,2398876.14036560,1439.32568422,'USDT',0.00000000,0,'2026-01-12 02:50:09','2026-01-12 02:50:09'),(35,4,'ORD20260112041636795754C4',17,'BTCUSDT','buy','long',90841.60000000,5.74630100,522003.17692160,313.20190615,'USDT',0.00000000,0,'2026-01-12 04:16:36','2026-01-12 04:16:36'),(36,4,'ORD20260112041719782888F0',17,'BTCUSDT','sell','long',90834.10000000,32.12833500,2918348.39422350,1751.00903653,'USDT',-4281.93210014,0,'2026-01-12 04:17:19','2026-01-12 04:17:19'),(37,4,'ORD20260112043611761BF467',18,'BTCUSDT','buy','long',90808.40000000,18.17310300,1650270.40646520,990.16224388,'USDT',0.00000000,0,'2026-01-12 04:36:11','2026-01-12 04:36:11'),(38,4,'ORD202601120439147835496D',18,'BTCUSDT','sell','long',90790.40000000,18.17310300,1649943.29061120,0.00000000,'USDT',-327.11585400,0,'2026-01-12 04:39:14','2026-01-12 04:39:14'),(39,1,'ORD202601120546062F9F9E1F',1,'BTCUSDT','sell','long',90583.10000000,0.00200000,181.16620000,0.00000000,'USDT',-0.77650000,0,'2026-01-12 05:46:06','2026-01-12 05:46:06'),(40,1,'ORD20260112081209EE6A1A8F',19,'BTCUSDT','buy','long',90460.70000000,0.52353700,47359.52349590,0.00000000,'USDT',0.00000000,0,'2026-01-12 08:12:09','2026-01-12 08:12:09'),(41,1,'ORD202601120822033304FF13',19,'BTCUSDT','sell','long',90802.80000000,0.52353700,47538.62550360,0.00000000,'USDT',179.10200770,0,'2026-01-12 08:22:03','2026-01-12 08:22:03'),(42,1,'ORD2026011208395924EDA338',20,'BTCUSDT','buy','long',90707.30000000,0.59511800,53981.54696140,0.00000000,'USDT',0.00000000,0,'2026-01-12 08:39:59','2026-01-12 08:39:59'),(43,1,'ORD202601120840103D1BD1D7',20,'BTCUSDT','sell','long',90691.20000000,0.59511800,53971.96556160,0.00000000,'USDT',-9.58139980,0,'2026-01-12 08:40:10','2026-01-12 08:40:10'),(44,1,'ORD2026011208401557C95F84',21,'BTCUSDT','buy','long',90707.30000000,0.59511800,53981.54696140,0.00000000,'USDT',0.00000000,0,'2026-01-12 08:40:15','2026-01-12 08:40:15'),(45,1,'ORD20260112084021E98432D1',21,'BTCUSDT','sell','long',90707.40000000,0.59511800,53981.60647320,0.00000000,'USDT',0.05951180,0,'2026-01-12 08:40:21','2026-01-12 08:40:21'),(46,1,'ORD202601120848176CCB84AB',22,'BTCUSDT','buy','long',90878.50000000,0.00100000,90.87850000,0.00000000,'USDT',0.00000000,0,'2026-01-12 08:48:17','2026-01-12 08:48:17'),(47,1,'ORD2026011208483462D8DA21',22,'BTCUSDT','sell','long',90883.10000000,0.00100000,90.88310000,0.00000000,'USDT',0.00460000,0,'2026-01-12 08:48:34','2026-01-12 08:48:34'),(48,4,'ORD2026011210054399A86016',23,'BTCUSDT','buy','long',91618.20000000,21.69739700,1987876.45782540,795.15058313,'USDT',0.00000000,0,'2026-01-12 10:05:44','2026-01-12 10:05:44'),(49,4,'ORD202601121012120247EA1F',23,'BTCUSDT','sell','long',91539.70000000,21.69739700,1986173.21216090,0.00000000,'USDT',-1703.24566450,0,'2026-01-12 10:12:12','2026-01-12 10:12:12'),(50,4,'ORD20260112101233385D8810',24,'BTCUSDT','buy','long',91470.00000000,21.69739700,1984660.90359000,793.86436144,'USDT',0.00000000,0,'2026-01-12 10:13:12','2026-01-12 10:13:12'),(51,4,'ORD20260112101427C6EB7EA5',24,'BTCUSDT','sell','long',91467.90000000,21.69739700,1984615.33905630,0.00000000,'USDT',-45.56453370,0,'2026-01-12 10:14:27','2026-01-12 10:14:27'),(52,4,'ORD20260112101519C358ACB3',25,'BTCUSDT','buy','long',91400.00000000,21.69739700,1983142.08580000,793.25683432,'USDT',0.00000000,0,'2026-01-12 10:15:20','2026-01-12 10:15:20'),(53,4,'ORD20260112101608F018B352',25,'BTCUSDT','sell','long',91368.10000000,21.69739700,1982449.93883570,0.00000000,'USDT',-692.14696430,0,'2026-01-12 10:16:08','2026-01-12 10:16:08'),(54,4,'ORD202601121018512095A71A',26,'BTCUSDT','buy','long',91421.00000000,21.69739700,1983597.73113700,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:18:51','2026-01-12 10:18:51'),(55,4,'ORD202601121019105940CC2D',26,'BTCUSDT','sell','long',91395.10000000,21.69739700,1983035.76855470,0.00000000,'USDT',-561.96258230,0,'2026-01-12 10:19:10','2026-01-12 10:19:10'),(56,4,'ORD202601121019151813177E',27,'BTCUSDT','buy','long',91380.20000000,21.69739700,1982712.47733940,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:19:15','2026-01-12 10:19:15'),(57,4,'ORD2026011210191783E98143',27,'BTCUSDT','sell','long',91380.10000000,21.69739700,1982710.30759970,0.00000000,'USDT',-2.16973970,0,'2026-01-12 10:19:17','2026-01-12 10:19:17'),(58,4,'ORD202601121019509445F70B',28,'BTCUSDT','buy','long',91426.30000000,21.69739700,1983712.72734110,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:19:50','2026-01-12 10:19:50'),(59,4,'ORD2026011210195337CB784D',28,'BTCUSDT','sell','long',91434.80000000,21.69739700,1983897.15521560,0.00000000,'USDT',184.42787450,0,'2026-01-12 10:19:53','2026-01-12 10:19:53'),(60,4,'ORD2026011210203838D4EAF6',29,'BTCUSDT','buy','long',91400.10000000,21.69739700,1983144.25553970,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:20:38','2026-01-12 10:20:38'),(61,4,'ORD2026011210204136D2F476',29,'BTCUSDT','sell','long',91427.60000000,21.69739700,1983740.93395720,0.00000000,'USDT',596.67841750,0,'2026-01-12 10:20:41','2026-01-12 10:20:41'),(62,4,'ORD202601121021137242CFAE',30,'BTCUSDT','buy','long',91484.30000000,16.42086000,1502250.88249800,600.90035300,'USDT',0.00000000,0,'2026-01-12 10:21:14','2026-01-12 10:21:14'),(63,4,'ORD20260112102125D8FC8B57',30,'BTCUSDT','sell','long',91457.20000000,16.42086000,1501805.87719200,0.00000000,'USDT',-445.00530600,0,'2026-01-12 10:21:25','2026-01-12 10:21:25'),(64,4,'ORD20260112102151CEF4210A',31,'BTCUSDT','buy','long',91454.30000000,16.42086000,1501758.25669800,600.70330268,'USDT',0.00000000,0,'2026-01-12 10:21:53','2026-01-12 10:21:53'),(65,4,'ORD20260112102204487DC1CE',31,'BTCUSDT','sell','long',91418.30000000,16.42086000,1501167.10573800,0.00000000,'USDT',-591.15096000,0,'2026-01-12 10:22:04','2026-01-12 10:22:04'),(66,4,'ORD202601121022071AB75118',32,'BTCUSDT','buy','long',91439.90000000,16.42086000,1501521.79631400,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:22:07','2026-01-12 10:22:07'),(67,4,'ORD202601121022104C513E4B',32,'BTCUSDT','sell','long',91440.00000000,16.42086000,1501523.43840000,0.00000000,'USDT',1.64208600,0,'2026-01-12 10:22:10','2026-01-12 10:22:10'),(68,4,'ORD20260112102254D65EA137',33,'BTCUSDT','buy','long',91444.70000000,16.42086000,1501600.61644200,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:22:54','2026-01-12 10:22:54'),(69,4,'ORD202601121022586E63A622',33,'BTCUSDT','sell','long',91470.80000000,16.42086000,1502029.20088800,0.00000000,'USDT',428.58444600,0,'2026-01-12 10:22:58','2026-01-12 10:22:58'),(70,4,'ORD2026011210230410AD0150',34,'BTCUSDT','buy','long',91483.50000000,14.34751100,1312560.52256850,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:23:05','2026-01-12 10:23:05'),(71,4,'ORD20260112102326663ECE29',34,'BTCUSDT','sell','long',91513.20000000,14.34751100,1312986.64364520,0.00000000,'USDT',426.12107670,0,'2026-01-12 10:23:26','2026-01-12 10:23:26'),(72,4,'ORD2026011210233388C7E7DF',35,'BTCUSDT','buy','long',91539.10000000,17.76893500,1626552.31785850,650.62092714,'USDT',0.00000000,0,'2026-01-12 10:23:35','2026-01-12 10:23:35'),(73,4,'ORD20260112102405D4CCB680',35,'BTCUSDT','sell','long',91520.00000000,17.76893500,1626212.93120000,0.00000000,'USDT',-339.38665850,0,'2026-01-12 10:24:05','2026-01-12 10:24:05'),(74,4,'ORD2026011210240976B7B0E1',36,'BTCUSDT','buy','long',91519.90000000,17.76893500,1626211.15430650,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:24:09','2026-01-12 10:24:09'),(75,4,'ORD202601121024127FF7C69A',36,'BTCUSDT','sell','long',91526.70000000,17.76893500,1626331.98306450,0.00000000,'USDT',120.82875800,0,'2026-01-12 10:24:12','2026-01-12 10:24:12'),(76,4,'ORD20260112102441E008A31C',37,'BTCUSDT','buy','long',91479.50000000,17.76893500,1625493.28933250,650.19731573,'USDT',0.00000000,0,'2026-01-12 10:25:04','2026-01-12 10:25:04'),(77,4,'ORD202601121027020A7CD047',37,'BTCUSDT','sell','long',91382.50000000,17.76893500,1623769.70263750,0.00000000,'USDT',-1723.58669500,0,'2026-01-12 10:27:02','2026-01-12 10:27:02'),(78,4,'ORD20260112102733A9591F12',38,'BTCUSDT','buy','long',91386.20000000,17.76893500,1623835.44769700,649.53417908,'USDT',0.00000000,0,'2026-01-12 10:27:34','2026-01-12 10:27:34'),(79,4,'ORD2026011210280900D8C177',38,'BTCUSDT','sell','long',91429.20000000,17.76893500,1624599.51190200,0.00000000,'USDT',764.06420500,0,'2026-01-12 10:28:09','2026-01-12 10:28:09'),(80,4,'ORD20260112102821B2EFFDC2',39,'BTCUSDT','buy','long',91421.00000000,17.47699000,1597763.90279000,639.10556112,'USDT',0.00000000,0,'2026-01-12 10:28:21','2026-01-12 10:28:21'),(81,4,'ORD2026011210282709BB2A92',39,'BTCUSDT','sell','long',91422.00000000,17.47699000,1597781.37978000,0.00000000,'USDT',17.47699000,0,'2026-01-12 10:28:27','2026-01-12 10:28:27'),(82,4,'ORD20260112102833BCA9A7D8',40,'BTCUSDT','buy','long',91465.80000000,17.47699000,1598546.87194200,639.41874878,'USDT',0.00000000,0,'2026-01-12 10:28:33','2026-01-12 10:28:33'),(83,4,'ORD20260112102837E180B97D',40,'BTCUSDT','sell','long',91443.10000000,17.47699000,1598150.14426900,0.00000000,'USDT',-396.72767300,0,'2026-01-12 10:28:38','2026-01-12 10:28:38'),(84,4,'ORD202601121028417D2FFD5D',41,'BTCUSDT','buy','long',91428.30000000,17.47699000,1597891.48481700,639.15659393,'USDT',0.00000000,0,'2026-01-12 10:28:42','2026-01-12 10:28:42'),(85,4,'ORD20260112102847A5571CAD',41,'BTCUSDT','sell','long',91423.10000000,17.47699000,1597800.60446900,0.00000000,'USDT',-90.88034800,0,'2026-01-12 10:28:47','2026-01-12 10:28:47'),(86,4,'ORD202601121028535FC30563',42,'BTCUSDT','buy','long',91421.00000000,17.47699000,1597763.90279000,639.10556112,'USDT',0.00000000,0,'2026-01-12 10:28:57','2026-01-12 10:28:57'),(87,4,'ORD202601121029178DE56162',42,'BTCUSDT','sell','long',91441.60000000,17.47699000,1598123.92878400,0.00000000,'USDT',360.02599400,0,'2026-01-12 10:29:17','2026-01-12 10:29:17'),(88,4,'ORD20260112102934DFBA753D',43,'BTCUSDT','buy','long',91455.90000000,24.17274000,2210739.69216600,884.29587687,'USDT',0.00000000,0,'2026-01-12 10:29:35','2026-01-12 10:29:35'),(89,4,'ORD20260112102950DBE9793F',43,'BTCUSDT','sell','long',91483.10000000,24.17274000,2211397.19069400,0.00000000,'USDT',657.49852800,0,'2026-01-12 10:29:50','2026-01-12 10:29:50'),(90,4,'ORD202601121030407ABB9162',44,'BTCUSDT','buy','long',91368.40000000,24.17274000,2208624.57741600,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:30:40','2026-01-12 10:30:40'),(91,4,'ORD20260112103055D7A0BC15',44,'BTCUSDT','sell','long',91360.00000000,24.17274000,2208421.52640000,0.00000000,'USDT',-203.05101600,0,'2026-01-12 10:30:55','2026-01-12 10:30:55'),(92,4,'ORD20260112103257C164B2A6',45,'BTCUSDT','buy','long',91436.30000000,28.07333300,2566921.69818790,1026.76867928,'USDT',0.00000000,0,'2026-01-12 10:32:58','2026-01-12 10:32:58'),(93,4,'ORD20260112103701715CA99A',45,'BTCUSDT','sell','long',91141.10000000,28.07333300,2558634.45028630,0.00000000,'USDT',-8287.24790160,0,'2026-01-12 10:37:01','2026-01-12 10:37:01'),(94,4,'ORD20260112105337450ECCCE',46,'BTCUSDT','buy','long',91382.60000000,17.20908100,1572610.56539060,0.00000000,'USDT',0.00000000,0,'2026-01-12 10:53:37','2026-01-12 10:53:37'),(95,4,'ORD20260112105342627D1E54',46,'BTCUSDT','sell','long',91382.50000000,17.20908100,1572608.84448250,0.00000000,'USDT',-1.72090810,0,'2026-01-12 10:53:42','2026-01-12 10:53:42'),(96,4,'ORD202601121100043F380897',47,'BTCUSDT','buy','long',91385.80000000,17.20612200,1572395.22386760,0.00000000,'USDT',0.00000000,0,'2026-01-12 11:00:04','2026-01-12 11:00:04'),(97,4,'ORD20260112110020488F7FEB',47,'BTCUSDT','sell','long',91394.70000000,17.20612200,1572548.35835340,0.00000000,'USDT',153.13448580,0,'2026-01-12 11:00:20','2026-01-12 11:00:20'),(98,4,'ORD202601121100476C3A6B45',48,'BTCUSDT','sell','short',91400.00000000,8.64032400,789725.61360000,315.89024544,'USDT',0.00000000,0,'2026-01-12 11:00:49','2026-01-12 11:00:49'),(99,4,'ORD20260112110059100EF74E',48,'BTCUSDT','buy','short',91396.60000000,8.64032400,789696.23649840,0.00000000,'USDT',29.37710160,0,'2026-01-12 11:00:59','2026-01-12 11:00:59'),(100,4,'ORD20260112111711AE216DF8',49,'ETHUSDT','sell','short',3148.59000000,652.55496800,2054628.04669510,0.00000000,'USDT',0.00000000,0,'2026-01-12 11:17:11','2026-01-12 11:17:11'),(101,4,'ORD202601121120065B371495',49,'ETHUSDT','sell','short',3142.79000000,318.97256100,1002463.77498519,400.98550999,'USDT',0.00000000,0,'2026-01-12 11:20:07','2026-01-12 11:20:07'),(102,4,'ORD20260112113944338BC7D9',50,'SOLUSDT','sell','short',141.96000000,0.39299000,55.78886040,0.00000000,'USDT',0.00000000,0,'2026-01-12 11:39:44','2026-01-12 11:39:44'),(103,4,'ORD2026011211425553E878FD',49,'ETHUSDT','buy','short',3146.57000000,971.52752900,3056979.37692550,0.00000000,'USDT',112.44475165,0,'2026-01-12 11:42:55','2026-01-12 11:42:55'),(104,4,'ORD20260112114321E96D2C0B',50,'SOLUSDT','buy','short',3148.79000000,0.39299000,1237.44298210,0.00000000,'USDT',-1181.65412170,0,'2026-01-12 11:43:21','2026-01-12 11:43:21'),(105,4,'ORD2026011211434993636F70',51,'ETHUSDT','sell','short',3150.10000000,606.87206600,1911707.69510660,764.68307804,'USDT',0.00000000,0,'2026-01-12 11:43:49','2026-01-12 11:43:49'),(106,4,'ORD20260112114725FFDF9F3A',51,'ETHUSDT','buy','short',3158.89000000,606.87206600,1917042.10056670,0.00000000,'USDT',-5334.40546014,0,'2026-01-12 11:47:26','2026-01-12 11:47:26'),(107,4,'ORD20260112114950F0CF800C',52,'BTCUSDT','sell','short',92145.60000000,16.65793500,1534955.41533600,613.98216613,'USDT',0.00000000,0,'2026-01-12 11:49:51','2026-01-12 11:49:51'),(108,4,'ORD202601121149555E981372',52,'BTCUSDT','buy','short',92155.80000000,16.65793500,1535125.32627300,0.00000000,'USDT',-169.91093700,0,'2026-01-12 11:49:55','2026-01-12 11:49:55'),(109,4,'ORD202601121149574F421FF0',53,'BTCUSDT','sell','short',92151.30000000,16.65793500,1535050.36556550,614.02014623,'USDT',0.00000000,0,'2026-01-12 11:49:58','2026-01-12 11:49:58'),(110,4,'ORD2026011211500417C5F3DF',53,'BTCUSDT','buy','short',92183.80000000,16.65793500,1535591.74845300,0.00000000,'USDT',-541.38288750,0,'2026-01-12 11:50:05','2026-01-12 11:50:05'),(111,4,'ORD20260112115012C36A4E26',54,'BTCUSDT','sell','short',92155.00000000,16.65793500,1535111.99992500,614.04479997,'USDT',0.00000000,0,'2026-01-12 11:50:12','2026-01-12 11:50:12'),(112,4,'ORD2026011211501986F6DF7C',54,'BTCUSDT','buy','short',92233.00000000,16.65793500,1536411.31885500,0.00000000,'USDT',-1299.31893000,0,'2026-01-12 11:50:19','2026-01-12 11:50:19'),(113,4,'ORD202601121150310DBAD2DA',55,'BTCUSDT','sell','short',92260.00000000,16.65793500,1536861.08310000,614.74443324,'USDT',0.00000000,0,'2026-01-12 11:51:04','2026-01-12 11:51:04'),(114,4,'ORD20260112115524850C07BF',56,'ETHUSDT','sell','short',3164.54000000,204.76269500,647979.73883530,259.19189553,'USDT',0.00000000,0,'2026-01-12 11:55:25','2026-01-12 11:55:25'),(115,4,'ORD202601121308405B197212',57,'XRPUSDT','buy','long',2.08520000,308647.03250400,643590.79217734,0.00000000,'USDT',0.00000000,0,'2026-01-12 13:08:40','2026-01-12 13:08:40');
/*!40000 ALTER TABLE `trades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('deposit','withdraw','transfer_in','transfer_out','fee','funding','pnl','liquidation','bonus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USDT',
  `amount` decimal(20,8) NOT NULL,
  `balance_before` decimal(20,8) DEFAULT NULL,
  `balance_after` decimal(20,8) DEFAULT NULL,
  `reference_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1,'fee','USDT',-0.05448000,10000.00000000,9990.86552000,'ORD20260111225741EF4459ED','order','completed',NULL,'2026-01-11 22:57:41'),(3,4,'bonus','USDT',1000.00000000,NULL,1000.00000000,NULL,NULL,'completed','신규 가입 보너스','2026-01-11 23:09:28'),(4,4,'fee','USDT',-745.13063419,497000.05450432,372066.48483873,'ORD20260111231640CD073BA4','order','completed',NULL,'2026-01-11 23:16:40'),(5,4,'fee','USDT',-1088.48818315,372066.48483873,189563.29946438,'ORD20260111235919F51BD571','order','completed',NULL,'2026-01-11 23:59:19'),(6,4,'fee','USDT',-1088.15373414,189563.29946438,7116.19004024,'ORD20260111235926A0E0F627','order','completed',NULL,'2026-01-11 23:59:26'),(7,4,'pnl','USDT',-3796.48907613,NULL,NULL,'ORD20260112004112C45853EB','position','completed','포지션 손실','2026-01-12 00:41:12'),(8,4,'pnl','USDT',2057.15483347,NULL,NULL,'ORD2026011200414666477D17','position','completed','포지션 수익','2026-01-12 00:41:46'),(9,4,'fee','USDT',-5232.53275370,995338.89320586,118017.56816877,'ORD20260112005125315352A2','order','completed',NULL,'2026-01-12 00:51:25'),(10,4,'pnl','USDT',-4502.56469069,NULL,NULL,'ORD20260112005215E4616785','position','completed','포지션 손실','2026-01-12 00:52:15'),(11,4,'fee','USDT',-4857.92627718,985603.79576147,171091.48995421,'ORD20260112005725E79E7BC4','order','completed',NULL,'2026-01-12 00:57:25'),(12,4,'pnl','USDT',-527.14273672,NULL,NULL,'ORD20260112010043B04FC86C','position','completed','포지션 손실','2026-01-12 01:00:43'),(13,4,'fee','USDT',-3712.75584432,980218.72674757,357713.33018249,'ORD20260112010047C501DFBB','order','completed',NULL,'2026-01-12 01:00:47'),(14,4,'pnl','USDT',-3964.91002791,NULL,NULL,'ORD20260112010051C0897823','position','completed','포지션 손실','2026-01-12 01:00:51'),(15,4,'fee','USDT',-3713.18901702,972541.06087534,349963.03568872,'ORD202601120101059D1A9158','order','completed',NULL,'2026-01-12 01:01:05'),(16,4,'pnl','USDT',-3713.18901702,NULL,NULL,'ORD2026011201010940F66076','position','completed','포지션 손실','2026-01-12 01:01:09'),(17,4,'fee','USDT',-3714.52122738,965114.68284130,342313.29038468,'ORD202601120101388FA876C9','order','completed',NULL,'2026-01-12 01:01:38'),(18,4,'pnl','USDT',-3707.70624944,NULL,NULL,'ORD2026011201014157DE67E5','position','completed','포지션 손실','2026-01-12 01:01:41'),(19,4,'fee','USDT',-3115.68804379,957692.45536448,435295.42668985,'ORD20260112010157DC9E177F','order','completed',NULL,'2026-01-12 01:01:57'),(20,4,'pnl','USDT',-4104.63098794,NULL,NULL,'ORD202601120102040AEDECED','position','completed','포지션 손실','2026-01-12 01:02:04'),(21,4,'fee','USDT',-0.05454090,950472.13633275,950462.99164185,'ORD20260112010624C0C68A45','order','completed',NULL,'2026-01-12 01:06:24'),(22,4,'pnl','USDT',-0.00616994,NULL,NULL,'ORD2026011201094887E010BA','position','completed','포지션 손실','2026-01-12 01:09:48'),(23,4,'fee','USDT',-1827.65549938,470448.98811605,164012.08271920,'ORD20260112011954DB11C58E','order','completed',NULL,'2026-01-12 01:19:54'),(24,4,'pnl','USDT',-1737.24283165,NULL,NULL,'ORD2026011201205697729A66','position','completed','포지션 손실','2026-01-12 01:20:56'),(25,4,'fee','USDT',-1827.70977955,466884.08978502,160438.08341321,'ORD202601120121091D80D767','order','completed',NULL,'2026-01-12 01:21:09'),(26,4,'pnl','USDT',-1827.70977955,NULL,NULL,'ORD20260112012113DC2BBC2D','position','completed','포지션 손실','2026-01-12 01:21:13'),(27,4,'fee','USDT',-3872.29709802,943251.75773178,293996.61096339,'ORD20260112012413BA1556DF','order','completed',NULL,'2026-01-12 01:24:13'),(28,1,'fee','USDT',-0.05468562,9990.86552000,9981.69656438,'ORD202601120136407AB9DC8A','order','completed',NULL,'2026-01-12 01:36:40'),(29,4,'pnl','USDT',1918.37976477,NULL,NULL,'ORD202601120141385F808553','position','completed','포지션 수익','2026-01-12 01:41:38'),(30,4,'fee','USDT',-2743.56287888,941297.84039853,481293.79770573,'ORD202601120142359B176707','order','completed',NULL,'2026-01-12 01:42:35'),(31,4,'pnl','USDT',-2176.53028010,NULL,NULL,'ORD20260112014503EC62C07F','position','completed','포지션 손실','2026-01-12 01:45:03'),(32,4,'fee','USDT',-3593.87087769,936377.74723955,333805.39674626,'ORD202601120145204AAEE1E4','order','completed',NULL,'2026-01-12 01:45:20'),(33,4,'pnl','USDT',-4343.12073988,NULL,NULL,'ORD20260112014815FADA21FA','position','completed','포지션 손실','2026-01-12 01:48:15'),(34,4,'fee','USDT',-3540.85113697,928440.75562198,334758.04832300,'ORD202601120156060ADBC65F','order','completed',NULL,'2026-01-12 01:56:06'),(35,4,'fee','USDT',-1439.32568422,334758.04832300,93431.10860222,'ORD20260112025009C7BAA650','order','completed',NULL,'2026-01-12 02:50:09'),(36,4,'fee','USDT',-313.20190615,93431.10860222,40917.58900391,'ORD20260112041636795754C4','order','completed',NULL,'2026-01-12 04:16:36'),(37,4,'pnl','USDT',-4281.93210014,NULL,NULL,'ORD20260112041719782888F0','position','completed','포지션 손실','2026-01-12 04:17:19'),(38,4,'fee','USDT',-990.16224388,328723.58863249,162706.38574209,'ORD20260112043611761BF467','order','completed',NULL,'2026-01-12 04:36:11'),(39,4,'pnl','USDT',-327.11585400,NULL,NULL,'ORD202601120439147835496D','position','completed','포지션 손실','2026-01-12 04:39:14'),(40,1,'pnl','USDT',-0.77650000,NULL,NULL,'ORD202601120546062F9F9E1F','position','completed','포지션 손실','2026-01-12 05:46:06'),(41,1,'fee','USDT',0.00000000,9999.11433438,5263.16198479,'ORD20260112081209EE6A1A8F','order','completed',NULL,'2026-01-12 08:12:09'),(42,1,'pnl','USDT',179.10200770,NULL,NULL,'ORD202601120822033304FF13','position','completed','포지션 수익','2026-01-12 08:22:03'),(43,1,'fee','USDT',0.00000000,10178.21634208,4780.06164594,'ORD2026011208395924EDA338','order','completed',NULL,'2026-01-12 08:39:59'),(44,1,'pnl','USDT',-9.58139980,NULL,NULL,'ORD202601120840103D1BD1D7','position','completed','포지션 손실','2026-01-12 08:40:10'),(45,1,'fee','USDT',0.00000000,10168.63494228,4770.48024614,'ORD2026011208401557C95F84','order','completed',NULL,'2026-01-12 08:40:15'),(46,1,'pnl','USDT',0.05951180,NULL,NULL,'ORD20260112084021E98432D1','position','completed','포지션 수익','2026-01-12 08:40:21'),(47,1,'fee','USDT',0.00000000,4767.70104508,4758.61319508,'ORD202601120848176CCB84AB','order','completed',NULL,'2026-01-12 08:48:17'),(48,1,'pnl','USDT',0.00460000,NULL,NULL,'ORD2026011208483462D8DA21','position','completed','포지션 수익','2026-01-12 08:48:34'),(49,4,'pnl','USDT',-1703.24566450,NULL,NULL,'ORD202601121012120247EA1F','position','completed','포지션 손실','2026-01-12 10:12:12'),(50,4,'pnl','USDT',-45.56453370,NULL,NULL,'ORD20260112101427C6EB7EA5','position','completed','포지션 손실','2026-01-12 10:14:27'),(51,4,'pnl','USDT',-692.14696430,NULL,NULL,'ORD20260112101608F018B352','position','completed','포지션 손실','2026-01-12 10:16:08'),(52,4,'fee','USDT',0.00000000,322557.47866476,124197.70555106,'ORD202601121018512095A71A','order','completed',NULL,'2026-01-12 10:18:51'),(53,4,'pnl','USDT',-561.96258230,NULL,NULL,'ORD202601121019105940CC2D','position','completed','포지션 손실','2026-01-12 10:19:10'),(54,4,'fee','USDT',0.00000000,321995.51608246,123724.26834852,'ORD202601121019151813177E','order','completed',NULL,'2026-01-12 10:19:15'),(55,4,'pnl','USDT',-2.16973970,NULL,NULL,'ORD2026011210191783E98143','position','completed','포지션 손실','2026-01-12 10:19:17'),(56,4,'fee','USDT',0.00000000,321993.34634276,123622.07360865,'ORD202601121019509445F70B','order','completed',NULL,'2026-01-12 10:19:50'),(57,4,'pnl','USDT',184.42787450,NULL,NULL,'ORD2026011210195337CB784D','position','completed','포지션 수익','2026-01-12 10:19:53'),(58,4,'fee','USDT',0.00000000,322177.77421726,123863.34866329,'ORD2026011210203838D4EAF6','order','completed',NULL,'2026-01-12 10:20:38'),(59,4,'pnl','USDT',596.67841750,NULL,NULL,'ORD2026011210204136D2F476','position','completed','포지션 수익','2026-01-12 10:20:41'),(60,4,'pnl','USDT',-445.00530600,NULL,NULL,'ORD20260112102125D8FC8B57','position','completed','포지션 손실','2026-01-12 10:21:25'),(61,4,'pnl','USDT',-591.15096000,NULL,NULL,'ORD20260112102204487DC1CE','position','completed','포지션 손실','2026-01-12 10:22:04'),(62,4,'fee','USDT',0.00000000,320536.69271308,170384.51308168,'ORD202601121022071AB75118','order','completed',NULL,'2026-01-12 10:22:07'),(63,4,'pnl','USDT',1.64208600,NULL,NULL,'ORD202601121022104C513E4B','position','completed','포지션 수익','2026-01-12 10:22:10'),(64,4,'fee','USDT',0.00000000,320538.33479908,170378.27315488,'ORD20260112102254D65EA137','order','completed',NULL,'2026-01-12 10:22:54'),(65,4,'pnl','USDT',428.58444600,NULL,NULL,'ORD202601121022586E63A622','position','completed','포지션 수익','2026-01-12 10:22:58'),(66,4,'fee','USDT',0.00000000,320966.91924508,189710.86698823,'ORD2026011210230410AD0150','order','completed',NULL,'2026-01-12 10:23:04'),(67,4,'pnl','USDT',426.12107670,NULL,NULL,'ORD20260112102326663ECE29','position','completed','포지션 수익','2026-01-12 10:23:26'),(68,4,'pnl','USDT',-339.38665850,NULL,NULL,'ORD20260112102405D4CCB680','position','completed','포지션 손실','2026-01-12 10:24:05'),(69,4,'fee','USDT',0.00000000,320403.03273614,157781.91730549,'ORD2026011210240976B7B0E1','order','completed',NULL,'2026-01-12 10:24:09'),(70,4,'pnl','USDT',120.82875800,NULL,NULL,'ORD202601121024127FF7C69A','position','completed','포지션 수익','2026-01-12 10:24:12'),(71,4,'pnl','USDT',-1723.58669500,NULL,NULL,'ORD202601121027020A7CD047','position','completed','포지션 손실','2026-01-12 10:27:02'),(72,4,'pnl','USDT',764.06420500,NULL,NULL,'ORD2026011210280900D8C177','position','completed','포지션 수익','2026-01-12 10:28:09'),(73,4,'pnl','USDT',17.47699000,NULL,NULL,'ORD2026011210282709BB2A92','position','completed','포지션 수익','2026-01-12 10:28:27'),(74,4,'pnl','USDT',-396.72767300,NULL,NULL,'ORD20260112102837E180B97D','position','completed','포지션 손실','2026-01-12 10:28:37'),(75,4,'pnl','USDT',-90.88034800,NULL,NULL,'ORD20260112102847A5571CAD','position','completed','포지션 손실','2026-01-12 10:28:47'),(76,4,'pnl','USDT',360.02599400,NULL,NULL,'ORD202601121029178DE56162','position','completed','포지션 수익','2026-01-12 10:29:17'),(77,4,'pnl','USDT',657.49852800,NULL,NULL,'ORD20260112102950DBE9793F','position','completed','포지션 수익','2026-01-12 10:29:50'),(78,4,'fee','USDT',0.00000000,315370.91865851,94508.46091691,'ORD202601121030407ABB9162','order','completed',NULL,'2026-01-12 10:30:40'),(79,4,'pnl','USDT',-203.05101600,NULL,NULL,'ORD20260112103055D7A0BC15','position','completed','포지션 손실','2026-01-12 10:30:55'),(80,4,'pnl','USDT',-8287.24790160,NULL,NULL,'ORD20260112103701715CA99A','position','completed','포지션 손실','2026-01-12 10:37:01'),(81,4,'fee','USDT',0.00000000,305853.85106163,148592.79452257,'ORD20260112105337450ECCCE','order','completed',NULL,'2026-01-12 10:53:37'),(82,4,'pnl','USDT',-1.72090810,NULL,NULL,'ORD20260112105342627D1E54','position','completed','포지션 손실','2026-01-12 10:53:42'),(83,4,'fee','USDT',0.00000000,305852.13015353,148612.60776677,'ORD202601121100043F380897','order','completed',NULL,'2026-01-12 11:00:04'),(84,4,'pnl','USDT',153.13448580,NULL,NULL,'ORD20260112110020488F7FEB','position','completed','포지션 수익','2026-01-12 11:00:20'),(85,4,'pnl','USDT',29.37710160,NULL,NULL,'ORD20260112110059100EF74E','position','completed','포지션 수익','2026-01-12 11:00:59'),(86,4,'fee','USDT',0.00000000,305718.75149549,100255.94682598,'ORD20260112111711AE216DF8','order','completed',NULL,'2026-01-12 11:17:11'),(87,4,'fee','USDT',0.00000000,9.56932746,3.99044142,'ORD20260112113944338BC7D9','order','completed',NULL,'2026-01-12 11:39:44'),(88,4,'pnl','USDT',112.44475165,NULL,NULL,'ORD2026011211425553E878FD','position','completed','포지션 수익','2026-01-12 11:42:55'),(89,4,'pnl','USDT',-1181.65412170,NULL,NULL,'ORD20260112114321E96D2C0B','position','completed','포지션 손실','2026-01-12 11:43:21'),(90,4,'pnl','USDT',-5334.40546014,NULL,NULL,'ORD20260112114725FFDF9F3A','position','completed','포지션 손실','2026-01-12 11:47:25'),(91,4,'pnl','USDT',-169.91093700,NULL,NULL,'ORD202601121149555E981372','position','completed','포지션 손실','2026-01-12 11:49:55'),(92,4,'pnl','USDT',-541.38288750,NULL,NULL,'ORD2026011211500417C5F3DF','position','completed','포지션 손실','2026-01-12 11:50:05'),(93,4,'pnl','USDT',-1299.31893000,NULL,NULL,'ORD2026011211501986F6DF7C','position','completed','포지션 손실','2026-01-12 11:50:19'),(94,4,'fee','USDT',0.00000000,75339.77519813,10980.69598040,'ORD202601121308405B197212','order','completed',NULL,'2026-01-12 13:08:40');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES (6,1,'493654fee1e470d0835204dabffd7aa2b1b616ca02a8ec6356d0fc07b98a3278e8cc398e29f650e69a18481a4f68db7089074c8cb62bdbd19f2db1fbe279a1b3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-19 01:36:25','2026-01-12 01:36:25'),(9,4,'b468d3b33a56a6808907fb6be2dad86bab2b948e46cc3cd2f2aea575be6d8f91e3d9285f8cb27ddf9aee4d571d19dacb7e248721adf5d79e2240d15f9ca1008e','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-19 04:15:06','2026-01-12 04:15:06'),(10,1,'e80c7f14e2dd25c32a1e2fc4c52bcac9be13e30bb4cdcd964822b684c6a30132','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2026-01-13 06:49:30','2026-01-12 06:49:30'),(11,1,'58575f5bb7e19ed6c135dd79ca778f5177b717c8551a7cac5e7b452f7d265946','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2026-01-13 06:50:33','2026-01-12 06:50:33'),(12,1,'bb87fdaadafe4579c56d3b1ee17050064712c896df3922c068db57e83eb38d40','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2026-01-13 06:50:59','2026-01-12 06:50:59'),(15,4,'9f2dc91ae5de0a02f6b6a76c1192f9167f0d6845162197e2d306a9bf424da582','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-13 09:34:06','2026-01-12 09:34:06'),(16,4,'6b1599f768d88b74a9285124ffd9c6032a2ce2c6ea89c42c2380a2124a3df4f6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2026-01-13 09:58:03','2026-01-12 09:58:03'),(17,4,'70a58f0c02e0de6c1b74ed821bfda6e53424f8b8e0a949d2f873aa6307d097c8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-13 09:58:22','2026-01-12 09:58:22');
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','vip','admin','superadmin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `status` enum('active','suspended','banned') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_secret` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `kyc_level` tinyint(1) DEFAULT '0',
  `vip_level` tinyint DEFAULT '0',
  `referral_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by` int DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `idx_email` (`email`),
  KEY `idx_api_key` (`api_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'test@test.com',NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','TestUser',NULL,NULL,'user','active',NULL,NULL,1,1,0,0,'TEST001',NULL,'2026-01-12 06:51:45',NULL,'::1','::1','2026-01-11 22:24:57','2026-01-12 06:51:45'),(2,'demo@demo.com',NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','DemoUser',NULL,NULL,'user','active',NULL,NULL,1,1,0,0,'DEMO001',NULL,NULL,NULL,NULL,NULL,'2026-01-11 22:24:57','2026-01-11 22:24:57'),(4,'123',NULL,'$2y$10$Hi5H3DUdLunIxvh5L14jmu4Cq.TNhj/NXPb62jEimSCSoO.OfIYki','VIP',NULL,NULL,'user','active',NULL,NULL,1,0,0,0,'14FA9CA7',NULL,'2026-01-12 09:58:22',NULL,'::1','::1','2026-01-11 23:09:28','2026-01-12 09:58:22'),(5,'admin@futurx.com','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrator',NULL,NULL,'superadmin','active',NULL,NULL,1,1,0,0,NULL,NULL,NULL,'2026-01-12 03:43:04','::1',NULL,'2026-01-12 01:52:09','2026-01-12 03:43:04');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `currency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USDT',
  `balance` decimal(20,8) DEFAULT '0.00000000',
  `available_balance` decimal(20,8) DEFAULT '0.00000000',
  `frozen_balance` decimal(20,8) DEFAULT '0.00000000',
  `total_deposit` decimal(20,8) DEFAULT '0.00000000',
  `total_withdraw` decimal(20,8) DEFAULT '0.00000000',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_currency` (`user_id`,`currency`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES (1,1,'USDT',10000.00000000,10168.69905408,0.00000000,10000.00000000,0.00000000,'2026-01-11 22:25:03','2026-01-12 08:48:34'),(2,1,'BTC',0.50000000,0.50000000,0.00000000,0.50000000,0.00000000,'2026-01-11 22:25:03','2026-01-11 22:25:03'),(3,2,'USDT',5000.00000000,5000.00000000,0.00000000,5000.00000000,0.00000000,'2026-01-11 22:25:03','2026-01-11 22:25:03'),(5,4,'USDT',1584850.61032361,10980.69598040,872984.91516156,1000000.00000000,0.00000000,'2026-01-11 23:09:28','2026-01-12 13:08:40');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-12 13:23:48
