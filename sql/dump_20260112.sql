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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 02:14:24'),(2,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 02:39:53'),(3,5,'liquidate_position','position',16,'{\"pnl\": -5291.2458383994335, \"side\": \"long\", \"size\": 64.843699, \"email\": \"123\", \"reason\": \"admin_manual_liquidation\", \"symbol\": \"BTCUSDT\", \"user_id\": 4, \"exit_price\": 90928.3, \"entry_price\": 91009.9}','::1',NULL,'2026-01-12 02:49:33'),(4,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:39:37'),(5,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:41:07'),(6,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:42:12'),(7,5,'login','users',5,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 03:43:04');
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
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,'ORD20260111225741EF4459ED','BTCUSDT','buy','long','market',90800.00000000,0.00100000,0.00100000,90800.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 22:57:41','2026-01-11 22:57:41'),(2,4,'ORD202601112316129648B3EC','BTCUSDT','buy','long','limit',90931.05000000,54.98671200,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-11 23:16:12','2026-01-12 00:01:12'),(3,4,'ORD20260111231640CD073BA4','BTCUSDT','buy','long','market',90886.00000000,13.66419900,13.66419900,90886.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 23:16:40','2026-01-11 23:16:40'),(4,4,'ORD20260111235919F51BD571','BTCUSDT','buy','long','market',91128.00000000,19.90767900,19.90767900,91128.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 23:59:19','2026-01-11 23:59:19'),(5,4,'ORD20260111235926A0E0F627','BTCUSDT','sell','short','market',91100.00000000,19.90767900,19.90767900,91100.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-11 23:59:26','2026-01-11 23:59:26'),(6,4,'ORD20260112004112C45853EB','BTCUSDT','sell','long','market',90971.00000000,33.57187800,33.57187800,90971.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 00:41:12','2026-01-12 00:41:12'),(7,4,'ORD2026011200414666477D17','BTCUSDT','buy','short','market',90942.10000000,19.90767900,19.90767900,90942.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 00:41:46','2026-01-12 00:41:46'),(8,4,'ORD20260112005125315352A2','BTCUSDT','buy','long','market',90742.30000000,96.10609300,96.10609300,90742.30000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 00:51:25','2026-01-12 00:51:25'),(9,4,'ORD20260112005215E4616785','BTCUSDT','sell','long','market',90749.90000000,96.10609300,96.10609300,90749.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 00:52:15','2026-01-12 00:52:15'),(10,4,'ORD20260112005725E79E7BC4','BTCUSDT','buy','long','market',90804.80000000,89.16427100,89.16427100,90804.80000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 00:57:25','2026-01-12 00:57:25'),(11,4,'ORD20260112010043B04FC86C','BTCUSDT','sell','long','market',90853.40000000,89.16427100,89.16427100,90853.40000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:00:43','2026-01-12 01:00:43'),(12,4,'ORD20260112010047C501DFBB','BTCUSDT','sell','short','market',90853.40000000,68.10891400,68.10891400,90853.40000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:00:47','2026-01-12 01:00:47'),(13,4,'ORD20260112010051C0897823','BTCUSDT','buy','short','market',90857.10000000,68.10891400,68.10891400,90857.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:00:51','2026-01-12 01:00:51'),(14,4,'ORD202601120101059D1A9158','BTCUSDT','buy','long','market',90864.00000000,68.10891400,68.10891400,90864.00000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:01:05','2026-01-12 01:01:05'),(15,4,'ORD2026011201010940F66076','BTCUSDT','sell','long','market',90864.00000000,68.10891400,68.10891400,90864.00000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:01:09','2026-01-12 01:01:09'),(16,4,'ORD202601120101388FA876C9','BTCUSDT','sell','short','market',90896.60000000,68.10891400,68.10891400,90896.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:01:38','2026-01-12 01:01:38'),(17,4,'ORD2026011201014157DE67E5','BTCUSDT','buy','short','market',90896.50000000,68.10891400,68.10891400,90896.50000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:01:41','2026-01-12 01:01:41'),(18,4,'ORD20260112010157DC9E177F','BTCUSDT','sell','short','market',90894.60000000,57.13005400,57.13005400,90894.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:01:57','2026-01-12 01:01:57'),(19,4,'ORD202601120102040AEDECED','BTCUSDT','buy','short','market',90911.90000000,57.13005400,57.13005400,90911.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:02:04','2026-01-12 01:02:04'),(20,4,'ORD202601120104474D9C61ED','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:04:47','2026-01-12 01:05:05'),(21,4,'ORD20260112010508D3BCB5D1','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:08','2026-01-12 01:05:14'),(22,4,'ORD20260112010515D1D2A8C3','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:15','2026-01-12 01:05:16'),(23,4,'ORD20260112010529401FD6F9','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:29','2026-01-12 01:05:31'),(24,4,'ORD20260112010535E178687B','BTCUSDT','buy','long','limit',90500.00000000,46.54700900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:05:35','2026-01-12 01:05:37'),(25,4,'ORD20260112010624C0C68A45','BTCUSDT','buy','long','market',90901.50000000,0.00100000,0.00100000,90901.50000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:06:24','2026-01-12 01:06:24'),(26,4,'ORD2026011201094887E010BA','BTCUSDT','sell','long','market',90949.90000000,0.00100000,0.00100000,90949.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:09:48','2026-01-12 01:09:48'),(27,4,'ORD20260112010952CCFBE0A5','BTCUSDT','buy','long','limit',90962.99000000,52.45651300,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:09:52','2026-01-12 01:24:05'),(28,4,'ORD20260112011954DB11C58E','BTCUSDT','buy','long','market',90911.10000000,33.50627700,33.50627700,90911.10000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:19:54','2026-01-12 01:19:54'),(29,4,'ORD2026011201205697729A66','BTCUSDT','sell','long','market',90913.80000000,33.50627700,33.50627700,90913.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:20:56','2026-01-12 01:20:56'),(30,4,'ORD202601120121091D80D767','BTCUSDT','buy','long','market',90913.80000000,33.50627700,33.50627700,90913.80000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:21:09','2026-01-12 01:21:09'),(31,4,'ORD20260112012113DC2BBC2D','BTCUSDT','sell','long','market',90913.80000000,33.50627700,33.50627700,90913.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:21:13','2026-01-12 01:21:13'),(32,4,'ORD20260112012413BA1556DF','BTCUSDT','buy','long','market',90979.42000000,70.93723500,70.93723500,90979.42000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:24:13','2026-01-12 01:24:13'),(33,1,'ORD202601120136407AB9DC8A','BTCUSDT','buy','long','market',91142.70000000,0.00100000,0.00100000,91142.70000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:36:40','2026-01-12 01:36:40'),(34,4,'ORD202601120141385F808553','BTCUSDT','sell','long','market',91061.10000000,70.93723500,70.93723500,91061.10000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:41:38','2026-01-12 01:41:38'),(35,4,'ORD2026011201415811949403','BTCUSDT','buy','long','limit',91075.00000000,50.21000200,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:41:58','2026-01-12 01:42:33'),(36,4,'ORD202601120142359B176707','BTCUSDT','buy','long','market',91069.60000000,50.21000200,50.21000200,91069.60000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:42:35','2026-01-12 01:42:35'),(37,4,'ORD20260112014503EC62C07F','BTCUSDT','sell','long','market',91080.90000000,50.21000200,50.21000200,91080.90000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:45:03','2026-01-12 01:45:03'),(38,4,'ORD202601120145204AAEE1E4','BTCUSDT','buy','long','market',91081.20000000,65.76313000,65.76313000,91081.20000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:45:20','2026-01-12 01:45:20'),(39,4,'ORD20260112014815FADA21FA','BTCUSDT','sell','long','market',91069.80000000,65.76313000,65.76313000,91069.80000000,NULL,10,'cross',1,'GTC','filled','2026-01-12 01:48:15','2026-01-12 01:48:15'),(40,4,'ORD202601120148233E61C7AB','BTCUSDT','buy','long','limit',91000.00000000,65.76313000,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:48:23','2026-01-12 01:49:29'),(41,4,'ORD20260112015559E1B7347B','BTCUSDT','buy','long','limit',91010.00000000,64.84369900,0.00000000,NULL,NULL,10,'cross',0,'GTC','cancelled','2026-01-12 01:55:59','2026-01-12 01:56:03'),(42,4,'ORD202601120156060ADBC65F','BTCUSDT','buy','long','market',91009.90000000,64.84369900,64.84369900,91009.90000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 01:56:06','2026-01-12 01:56:06'),(43,4,'ORD20260112025009C7BAA650','BTCUSDT','buy','long','market',90928.40000000,26.38203400,26.38203400,90928.40000000,NULL,10,'cross',0,'GTC','filled','2026-01-12 02:50:09','2026-01-12 02:50:09');
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,1,'BTCUSDT','long',0.00200000,90971.35000000,90800.00000000,NULL,82174.00000000,18.19427000,10,0.00000000,0.00000000,'cross',NULL,NULL,'open','2026-01-11 22:57:41',NULL,'2026-01-11 22:57:41','2026-01-12 01:36:40'),(2,4,'BTCUSDT','long',0.00000000,91029.50279475,90886.00000000,NULL,82251.83000000,305603.13622260,10,0.00000000,-3796.48907613,'cross',NULL,NULL,'closed','2026-01-11 23:16:40','2026-01-12 00:41:12','2026-01-11 23:16:40','2026-01-12 00:41:12'),(3,4,'BTCUSDT','short',0.00000000,91100.00000000,91100.00000000,NULL,99754.50000000,181358.95569000,10,0.00000000,2057.15483347,'cross',NULL,NULL,'closed','2026-01-11 23:59:26','2026-01-12 00:41:46','2026-01-11 23:59:26','2026-01-12 00:41:46'),(4,4,'BTCUSDT','long',0.00000000,90742.30000000,90742.30000000,NULL,82121.78150000,872088.79228339,10,0.00000000,-4502.56469069,'cross',NULL,NULL,'closed','2026-01-12 00:51:25','2026-01-12 00:52:15','2026-01-12 00:51:25','2026-01-12 00:52:15'),(5,4,'BTCUSDT','long',0.00000000,90804.80000000,90804.80000000,NULL,82178.34400000,809654.37953008,10,0.00000000,-527.14273672,'cross',NULL,NULL,'closed','2026-01-12 00:57:25','2026-01-12 01:00:43','2026-01-12 00:57:25','2026-01-12 01:00:43'),(6,4,'BTCUSDT','short',0.00000000,90853.40000000,90853.40000000,NULL,99484.47300000,618792.64072076,10,0.00000000,-3964.91002791,'cross',NULL,NULL,'closed','2026-01-12 01:00:47','2026-01-12 01:00:51','2026-01-12 01:00:47','2026-01-12 01:00:51'),(7,4,'BTCUSDT','long',0.00000000,90864.00000000,90864.00000000,NULL,82231.92000000,618864.83616960,10,0.00000000,-3713.18901702,'cross',NULL,NULL,'closed','2026-01-12 01:01:05','2026-01-12 01:01:09','2026-01-12 01:01:05','2026-01-12 01:01:09'),(8,4,'BTCUSDT','short',0.00000000,90896.60000000,90896.60000000,NULL,99531.77700000,619086.87122924,10,0.00000000,-3707.70624944,'cross',NULL,NULL,'closed','2026-01-12 01:01:38','2026-01-12 01:01:41','2026-01-12 01:01:38','2026-01-12 01:01:41'),(9,4,'BTCUSDT','short',0.00000000,90894.60000000,90894.60000000,NULL,99529.58700000,519281.34063084,10,0.00000000,-4104.63098794,'cross',NULL,NULL,'closed','2026-01-12 01:01:57','2026-01-12 01:02:04','2026-01-12 01:01:57','2026-01-12 01:02:04'),(10,4,'BTCUSDT','long',0.00000000,90901.50000000,90901.50000000,NULL,82265.85750000,9.09015000,10,0.00000000,-0.00616994,'cross',NULL,NULL,'closed','2026-01-12 01:06:24','2026-01-12 01:09:48','2026-01-12 01:06:24','2026-01-12 01:09:48'),(11,4,'BTCUSDT','long',0.00000000,90911.10000000,90911.10000000,NULL,82274.54550000,304609.24989747,10,0.00000000,-1737.24283165,'cross',NULL,NULL,'closed','2026-01-12 01:19:54','2026-01-12 01:20:56','2026-01-12 01:19:54','2026-01-12 01:20:56'),(12,4,'BTCUSDT','long',0.00000000,90913.80000000,90913.80000000,NULL,82276.98900000,304618.29659226,10,0.00000000,-1827.70977955,'cross',NULL,NULL,'closed','2026-01-12 01:21:09','2026-01-12 01:21:13','2026-01-12 01:21:09','2026-01-12 01:21:13'),(13,4,'BTCUSDT','long',0.00000000,90979.42000000,90979.42000000,NULL,82336.37510000,645382.84967037,10,0.00000000,1918.37976477,'cross',NULL,NULL,'closed','2026-01-12 01:24:13','2026-01-12 01:41:38','2026-01-12 01:24:13','2026-01-12 01:41:38'),(14,4,'BTCUSDT','long',0.00000000,91069.60000000,91069.60000000,NULL,82417.98800000,457260.47981392,10,0.00000000,-2176.53028010,'cross',NULL,NULL,'closed','2026-01-12 01:42:35','2026-01-12 01:45:03','2026-01-12 01:42:35','2026-01-12 01:45:03'),(15,4,'BTCUSDT','long',0.00000000,91081.20000000,91081.20000000,NULL,82428.48600000,598978.47961560,10,0.00000000,-4343.12073988,'cross',NULL,NULL,'closed','2026-01-12 01:45:20','2026-01-12 01:48:15','2026-01-12 01:45:20','2026-01-12 01:48:15'),(16,4,'BTCUSDT','long',64.84369900,91009.90000000,91009.90000000,90928.30000000,82363.95950000,590141.85616201,10,0.00000000,-5291.24583840,'cross',NULL,NULL,'liquidated','2026-01-12 01:56:06','2026-01-12 02:49:33','2026-01-12 01:56:06','2026-01-12 02:49:33'),(17,4,'BTCUSDT','long',26.38203400,90928.40000000,90928.40000000,NULL,82290.20200000,239887.61403656,10,0.00000000,0.00000000,'cross',NULL,NULL,'open','2026-01-12 02:50:09',NULL,'2026-01-12 02:50:09','2026-01-12 02:50:09');
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
INSERT INTO `settings` VALUES (1,'site_name','FuturX','string','general','사이트 이름','2026-01-12 01:53:16'),(2,'site_description','암호화폐 선물거래 플랫폼','string','general','사이트 설명','2026-01-12 01:53:16'),(3,'maintenance_mode','false','boolean','general','점검 모드','2026-01-12 01:53:16'),(4,'max_leverage','125','number','trading','최대 레버리지','2026-01-12 01:53:16'),(5,'taker_fee','0.0006','number','trading','테이커 수수료','2026-01-12 01:53:16'),(6,'maker_fee','0.0002','number','trading','메이커 수수료','2026-01-12 01:53:16'),(7,'min_withdraw','10','number','wallet','최소 출금액','2026-01-12 01:53:16'),(8,'max_withdraw_daily','100000','number','wallet','일일 최대 출금액','2026-01-12 01:53:16'),(9,'registration_enabled','true','boolean','auth','회원가입 허용','2026-01-12 01:53:16'),(10,'kyc_required','false','boolean','auth','KYC 필수 여부','2026-01-12 01:53:16');
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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trades`
--

LOCK TABLES `trades` WRITE;
/*!40000 ALTER TABLE `trades` DISABLE KEYS */;
INSERT INTO `trades` VALUES (1,1,'ORD20260111225741EF4459ED',1,'BTCUSDT','buy','long',90800.00000000,0.00100000,90.80000000,0.05448000,'USDT',0.00000000,0,'2026-01-11 22:57:41','2026-01-11 22:57:41'),(2,4,'ORD20260111231640CD073BA4',2,'BTCUSDT','buy','long',90886.00000000,13.66419900,1241884.39031400,745.13063419,'USDT',0.00000000,0,'2026-01-11 23:16:40','2026-01-11 23:16:40'),(3,4,'ORD20260111235919F51BD571',2,'BTCUSDT','buy','long',91128.00000000,19.90767900,1814146.97191200,1088.48818315,'USDT',0.00000000,0,'2026-01-11 23:59:19','2026-01-11 23:59:19'),(4,4,'ORD20260111235926A0E0F627',3,'BTCUSDT','sell','short',91100.00000000,19.90767900,1813589.55690000,1088.15373414,'USDT',0.00000000,0,'2026-01-11 23:59:26','2026-01-11 23:59:26'),(5,4,'ORD20260112004112C45853EB',2,'BTCUSDT','sell','long',90971.00000000,33.57187800,3054067.31353800,1832.44038812,'USDT',-3796.48907613,0,'2026-01-12 00:41:12','2026-01-12 00:41:12'),(6,4,'ORD2026011200414666477D17',3,'BTCUSDT','buy','short',90942.10000000,19.90767900,1810446.13438590,1086.26768063,'USDT',2057.15483347,0,'2026-01-12 00:41:46','2026-01-12 00:41:46'),(7,4,'ORD20260112005125315352A2',4,'BTCUSDT','buy','long',90742.30000000,96.10609300,8720887.92283390,5232.53275370,'USDT',0.00000000,0,'2026-01-12 00:51:25','2026-01-12 00:51:25'),(8,4,'ORD20260112005215E4616785',4,'BTCUSDT','sell','long',90749.90000000,96.10609300,8721618.32914070,5232.97099748,'USDT',-4502.56469069,0,'2026-01-12 00:52:15','2026-01-12 00:52:15'),(9,4,'ORD20260112005725E79E7BC4',5,'BTCUSDT','buy','long',90804.80000000,89.16427100,8096543.79530080,4857.92627718,'USDT',0.00000000,0,'2026-01-12 00:57:25','2026-01-12 00:57:25'),(10,4,'ORD20260112010043B04FC86C',5,'BTCUSDT','sell','long',90853.40000000,89.16427100,8100877.17887140,4860.52630732,'USDT',-527.14273672,0,'2026-01-12 01:00:43','2026-01-12 01:00:43'),(11,4,'ORD20260112010047C501DFBB',6,'BTCUSDT','sell','short',90853.40000000,68.10891400,6187926.40720760,3712.75584432,'USDT',0.00000000,0,'2026-01-12 01:00:47','2026-01-12 01:00:47'),(12,4,'ORD20260112010051C0897823',6,'BTCUSDT','buy','short',90857.10000000,68.10891400,6188178.41018940,3712.90704611,'USDT',-3964.91002791,0,'2026-01-12 01:00:51','2026-01-12 01:00:51'),(13,4,'ORD202601120101059D1A9158',7,'BTCUSDT','buy','long',90864.00000000,68.10891400,6188648.36169600,3713.18901702,'USDT',0.00000000,0,'2026-01-12 01:01:05','2026-01-12 01:01:05'),(14,4,'ORD2026011201010940F66076',7,'BTCUSDT','sell','long',90864.00000000,68.10891400,6188648.36169600,3713.18901702,'USDT',-3713.18901702,0,'2026-01-12 01:01:09','2026-01-12 01:01:09'),(15,4,'ORD202601120101388FA876C9',8,'BTCUSDT','sell','short',90896.60000000,68.10891400,6190868.71229240,3714.52122738,'USDT',0.00000000,0,'2026-01-12 01:01:38','2026-01-12 01:01:38'),(16,4,'ORD2026011201014157DE67E5',8,'BTCUSDT','buy','short',90896.50000000,68.10891400,6190861.90140100,3714.51714084,'USDT',-3707.70624944,0,'2026-01-12 01:01:41','2026-01-12 01:01:41'),(17,4,'ORD20260112010157DC9E177F',9,'BTCUSDT','sell','short',90894.60000000,57.13005400,5192813.40630840,3115.68804379,'USDT',0.00000000,0,'2026-01-12 01:01:57','2026-01-12 01:01:57'),(18,4,'ORD202601120102040AEDECED',9,'BTCUSDT','buy','short',90911.90000000,57.13005400,5193801.75624260,3116.28105375,'USDT',-4104.63098794,0,'2026-01-12 01:02:04','2026-01-12 01:02:04'),(19,4,'ORD20260112010624C0C68A45',10,'BTCUSDT','buy','long',90901.50000000,0.00100000,90.90150000,0.05454090,'USDT',0.00000000,0,'2026-01-12 01:06:24','2026-01-12 01:06:24'),(20,4,'ORD2026011201094887E010BA',10,'BTCUSDT','sell','long',90949.90000000,0.00100000,90.94990000,0.05456994,'USDT',-0.00616994,0,'2026-01-12 01:09:48','2026-01-12 01:09:48'),(21,4,'ORD20260112011954DB11C58E',11,'BTCUSDT','buy','long',90911.10000000,33.50627700,3046092.49897470,1827.65549938,'USDT',0.00000000,0,'2026-01-12 01:19:54','2026-01-12 01:19:54'),(22,4,'ORD2026011201205697729A66',11,'BTCUSDT','sell','long',90913.80000000,33.50627700,3046182.96592260,1827.70977955,'USDT',-1737.24283165,0,'2026-01-12 01:20:56','2026-01-12 01:20:56'),(23,4,'ORD202601120121091D80D767',12,'BTCUSDT','buy','long',90913.80000000,33.50627700,3046182.96592260,1827.70977955,'USDT',0.00000000,0,'2026-01-12 01:21:09','2026-01-12 01:21:09'),(24,4,'ORD20260112012113DC2BBC2D',12,'BTCUSDT','sell','long',90913.80000000,33.50627700,3046182.96592260,1827.70977955,'USDT',-1827.70977955,0,'2026-01-12 01:21:13','2026-01-12 01:21:13'),(25,4,'ORD20260112012413BA1556DF',13,'BTCUSDT','buy','long',90979.42000000,70.93723500,6453828.49670370,3872.29709802,'USDT',0.00000000,0,'2026-01-12 01:24:13','2026-01-12 01:24:13'),(26,1,'ORD202601120136407AB9DC8A',1,'BTCUSDT','buy','long',91142.70000000,0.00100000,91.14270000,0.05468562,'USDT',0.00000000,0,'2026-01-12 01:36:40','2026-01-12 01:36:40'),(27,4,'ORD202601120141385F808553',13,'BTCUSDT','sell','long',91061.10000000,70.93723500,6459622.65005850,3875.77359004,'USDT',1918.37976477,0,'2026-01-12 01:41:38','2026-01-12 01:41:38'),(28,4,'ORD202601120142359B176707',14,'BTCUSDT','buy','long',91069.60000000,50.21000200,4572604.79813920,2743.56287888,'USDT',0.00000000,0,'2026-01-12 01:42:35','2026-01-12 01:42:35'),(29,4,'ORD20260112014503EC62C07F',14,'BTCUSDT','sell','long',91080.90000000,50.21000200,4573172.17116180,2743.90330270,'USDT',-2176.53028010,0,'2026-01-12 01:45:03','2026-01-12 01:45:03'),(30,4,'ORD202601120145204AAEE1E4',15,'BTCUSDT','buy','long',91081.20000000,65.76313000,5989784.79615600,3593.87087769,'USDT',0.00000000,0,'2026-01-12 01:45:20','2026-01-12 01:45:20'),(31,4,'ORD20260112014815FADA21FA',15,'BTCUSDT','sell','long',91069.80000000,65.76313000,5989035.09647400,3593.42105788,'USDT',-4343.12073988,0,'2026-01-12 01:48:15','2026-01-12 01:48:15'),(32,4,'ORD202601120156060ADBC65F',16,'BTCUSDT','buy','long',91009.90000000,64.84369900,5901418.56162010,3540.85113697,'USDT',0.00000000,0,'2026-01-12 01:56:06','2026-01-12 01:56:06'),(33,4,'LIQ_16_1768153773',16,'BTCUSDT','sell','long',90928.30000000,64.84369900,5896127.31578170,0.00000000,'USDT',-5291.24583840,0,'2026-01-12 02:49:33','2026-01-12 02:49:33'),(34,4,'ORD20260112025009C7BAA650',17,'BTCUSDT','buy','long',90928.40000000,26.38203400,2398876.14036560,1439.32568422,'USDT',0.00000000,0,'2026-01-12 02:50:09','2026-01-12 02:50:09');
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1,'fee','USDT',-0.05448000,10000.00000000,9990.86552000,'ORD20260111225741EF4459ED','order','completed',NULL,'2026-01-11 22:57:41'),(3,4,'bonus','USDT',1000.00000000,NULL,1000.00000000,NULL,NULL,'completed','신규 가입 보너스','2026-01-11 23:09:28'),(4,4,'fee','USDT',-745.13063419,497000.05450432,372066.48483873,'ORD20260111231640CD073BA4','order','completed',NULL,'2026-01-11 23:16:40'),(5,4,'fee','USDT',-1088.48818315,372066.48483873,189563.29946438,'ORD20260111235919F51BD571','order','completed',NULL,'2026-01-11 23:59:19'),(6,4,'fee','USDT',-1088.15373414,189563.29946438,7116.19004024,'ORD20260111235926A0E0F627','order','completed',NULL,'2026-01-11 23:59:26'),(7,4,'pnl','USDT',-3796.48907613,NULL,NULL,'ORD20260112004112C45853EB','position','completed','포지션 손실','2026-01-12 00:41:12'),(8,4,'pnl','USDT',2057.15483347,NULL,NULL,'ORD2026011200414666477D17','position','completed','포지션 수익','2026-01-12 00:41:46'),(9,4,'fee','USDT',-5232.53275370,995338.89320586,118017.56816877,'ORD20260112005125315352A2','order','completed',NULL,'2026-01-12 00:51:25'),(10,4,'pnl','USDT',-4502.56469069,NULL,NULL,'ORD20260112005215E4616785','position','completed','포지션 손실','2026-01-12 00:52:15'),(11,4,'fee','USDT',-4857.92627718,985603.79576147,171091.48995421,'ORD20260112005725E79E7BC4','order','completed',NULL,'2026-01-12 00:57:25'),(12,4,'pnl','USDT',-527.14273672,NULL,NULL,'ORD20260112010043B04FC86C','position','completed','포지션 손실','2026-01-12 01:00:43'),(13,4,'fee','USDT',-3712.75584432,980218.72674757,357713.33018249,'ORD20260112010047C501DFBB','order','completed',NULL,'2026-01-12 01:00:47'),(14,4,'pnl','USDT',-3964.91002791,NULL,NULL,'ORD20260112010051C0897823','position','completed','포지션 손실','2026-01-12 01:00:51'),(15,4,'fee','USDT',-3713.18901702,972541.06087534,349963.03568872,'ORD202601120101059D1A9158','order','completed',NULL,'2026-01-12 01:01:05'),(16,4,'pnl','USDT',-3713.18901702,NULL,NULL,'ORD2026011201010940F66076','position','completed','포지션 손실','2026-01-12 01:01:09'),(17,4,'fee','USDT',-3714.52122738,965114.68284130,342313.29038468,'ORD202601120101388FA876C9','order','completed',NULL,'2026-01-12 01:01:38'),(18,4,'pnl','USDT',-3707.70624944,NULL,NULL,'ORD2026011201014157DE67E5','position','completed','포지션 손실','2026-01-12 01:01:41'),(19,4,'fee','USDT',-3115.68804379,957692.45536448,435295.42668985,'ORD20260112010157DC9E177F','order','completed',NULL,'2026-01-12 01:01:57'),(20,4,'pnl','USDT',-4104.63098794,NULL,NULL,'ORD202601120102040AEDECED','position','completed','포지션 손실','2026-01-12 01:02:04'),(21,4,'fee','USDT',-0.05454090,950472.13633275,950462.99164185,'ORD20260112010624C0C68A45','order','completed',NULL,'2026-01-12 01:06:24'),(22,4,'pnl','USDT',-0.00616994,NULL,NULL,'ORD2026011201094887E010BA','position','completed','포지션 손실','2026-01-12 01:09:48'),(23,4,'fee','USDT',-1827.65549938,470448.98811605,164012.08271920,'ORD20260112011954DB11C58E','order','completed',NULL,'2026-01-12 01:19:54'),(24,4,'pnl','USDT',-1737.24283165,NULL,NULL,'ORD2026011201205697729A66','position','completed','포지션 손실','2026-01-12 01:20:56'),(25,4,'fee','USDT',-1827.70977955,466884.08978502,160438.08341321,'ORD202601120121091D80D767','order','completed',NULL,'2026-01-12 01:21:09'),(26,4,'pnl','USDT',-1827.70977955,NULL,NULL,'ORD20260112012113DC2BBC2D','position','completed','포지션 손실','2026-01-12 01:21:13'),(27,4,'fee','USDT',-3872.29709802,943251.75773178,293996.61096339,'ORD20260112012413BA1556DF','order','completed',NULL,'2026-01-12 01:24:13'),(28,1,'fee','USDT',-0.05468562,9990.86552000,9981.69656438,'ORD202601120136407AB9DC8A','order','completed',NULL,'2026-01-12 01:36:40'),(29,4,'pnl','USDT',1918.37976477,NULL,NULL,'ORD202601120141385F808553','position','completed','포지션 수익','2026-01-12 01:41:38'),(30,4,'fee','USDT',-2743.56287888,941297.84039853,481293.79770573,'ORD202601120142359B176707','order','completed',NULL,'2026-01-12 01:42:35'),(31,4,'pnl','USDT',-2176.53028010,NULL,NULL,'ORD20260112014503EC62C07F','position','completed','포지션 손실','2026-01-12 01:45:03'),(32,4,'fee','USDT',-3593.87087769,936377.74723955,333805.39674626,'ORD202601120145204AAEE1E4','order','completed',NULL,'2026-01-12 01:45:20'),(33,4,'pnl','USDT',-4343.12073988,NULL,NULL,'ORD20260112014815FADA21FA','position','completed','포지션 손실','2026-01-12 01:48:15'),(34,4,'fee','USDT',-3540.85113697,928440.75562198,334758.04832300,'ORD202601120156060ADBC65F','order','completed',NULL,'2026-01-12 01:56:06'),(35,4,'fee','USDT',-1439.32568422,334758.04832300,93431.10860222,'ORD20260112025009C7BAA650','order','completed',NULL,'2026-01-12 02:50:09');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES (6,1,'493654fee1e470d0835204dabffd7aa2b1b616ca02a8ec6356d0fc07b98a3278e8cc398e29f650e69a18481a4f68db7089074c8cb62bdbd19f2db1fbe279a1b3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-19 01:36:25','2026-01-12 01:36:25'),(8,4,'aef75687090162abfd8357b91077c4530b321e28bdedb76d937a074a5c681c91238d98a65ed0f35743ab25c261b39b5289629506d7feabecf56db7df5f89c50f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0','2026-01-19 03:16:17','2026-01-12 03:16:17');
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
INSERT INTO `users` VALUES (1,'test@test.com',NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','TestUser',NULL,NULL,'user','active',NULL,NULL,1,1,0,0,'TEST001',NULL,'2026-01-12 01:36:25',NULL,NULL,'::1','2026-01-11 22:24:57','2026-01-12 01:36:25'),(2,'demo@demo.com',NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','DemoUser',NULL,NULL,'user','active',NULL,NULL,1,1,0,0,'DEMO001',NULL,NULL,NULL,NULL,NULL,'2026-01-11 22:24:57','2026-01-11 22:24:57'),(4,'123',NULL,'$2y$10$Hi5H3DUdLunIxvh5L14jmu4Cq.TNhj/NXPb62jEimSCSoO.OfIYki','VIP',NULL,NULL,'user','active',NULL,NULL,1,0,0,0,'14FA9CA7',NULL,'2026-01-12 03:16:17',NULL,NULL,'::1','2026-01-11 23:09:28','2026-01-12 03:16:17'),(5,'admin@futurx.com','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrator',NULL,NULL,'superadmin','active',NULL,NULL,1,1,0,0,NULL,NULL,NULL,'2026-01-12 03:43:04','::1',NULL,'2026-01-12 01:52:09','2026-01-12 03:43:04');
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
INSERT INTO `wallets` VALUES (1,1,'USDT',10000.00000000,9981.69656438,18.19427000,10000.00000000,0.00000000,'2026-01-11 22:25:03','2026-01-12 01:36:40'),(2,1,'BTC',0.50000000,0.50000000,0.00000000,0.50000000,0.00000000,'2026-01-11 22:25:03','2026-01-11 22:25:03'),(3,2,'USDT',5000.00000000,5000.00000000,0.00000000,5000.00000000,0.00000000,'2026-01-11 22:25:03','2026-01-11 22:25:03'),(5,4,'USDT',1584850.61032361,93431.10860222,830029.47019857,1000000.00000000,0.00000000,'2026-01-11 23:09:28','2026-01-12 02:50:09');
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

-- Dump completed on 2026-01-12  4:07:59
