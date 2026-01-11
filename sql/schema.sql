-- =============================================
-- FuturX 선물거래 플랫폼 데이터베이스 스키마
-- =============================================

-- 사용자 테이블
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nickname` VARCHAR(50) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('user', 'vip', 'admin', 'superadmin') DEFAULT 'user',
    `status` ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    `is_verified` TINYINT(1) DEFAULT 0,
    `kyc_level` TINYINT DEFAULT 0,
    `vip_level` TINYINT DEFAULT 0,
    `referral_code` VARCHAR(20) UNIQUE,
    `referred_by` INT UNSIGNED DEFAULT NULL,
    `api_key` VARCHAR(64) DEFAULT NULL,
    `api_secret` VARCHAR(128) DEFAULT NULL,
    `two_factor_secret` VARCHAR(32) DEFAULT NULL,
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`),
    INDEX `idx_referral_code` (`referral_code`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 사용자 지갑 테이블
CREATE TABLE IF NOT EXISTS `wallets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(20) NOT NULL DEFAULT 'USDT',
    `balance` DECIMAL(20,8) DEFAULT 0,
    `available` DECIMAL(20,8) DEFAULT 0,
    `frozen` DECIMAL(20,8) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_currency` (`user_id`, `currency`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 포지션 테이블
CREATE TABLE IF NOT EXISTS `positions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `symbol` VARCHAR(20) NOT NULL,
    `side` ENUM('long', 'short') NOT NULL,
    `size` DECIMAL(20,8) NOT NULL DEFAULT 0,
    `entry_price` DECIMAL(20,8) NOT NULL,
    `mark_price` DECIMAL(20,8) DEFAULT NULL,
    `liquidation_price` DECIMAL(20,8) DEFAULT NULL,
    `margin` DECIMAL(20,8) NOT NULL,
    `leverage` INT NOT NULL DEFAULT 10,
    `margin_mode` ENUM('cross', 'isolated') DEFAULT 'cross',
    `unrealized_pnl` DECIMAL(20,8) DEFAULT 0,
    `realized_pnl` DECIMAL(20,8) DEFAULT 0,
    `take_profit` DECIMAL(20,8) DEFAULT NULL,
    `stop_loss` DECIMAL(20,8) DEFAULT NULL,
    `status` ENUM('open', 'closed', 'liquidated') DEFAULT 'open',
    `opened_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `closed_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_symbol` (`user_id`, `symbol`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 주문 테이블
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `symbol` VARCHAR(20) NOT NULL,
    `type` ENUM('market', 'limit', 'stop_market', 'stop_limit', 'take_profit', 'take_profit_limit') NOT NULL,
    `side` ENUM('buy', 'sell') NOT NULL,
    `position_side` ENUM('long', 'short', 'both') DEFAULT 'both',
    `price` DECIMAL(20,8) DEFAULT NULL,
    `quantity` DECIMAL(20,8) NOT NULL,
    `filled_quantity` DECIMAL(20,8) DEFAULT 0,
    `avg_price` DECIMAL(20,8) DEFAULT NULL,
    `stop_price` DECIMAL(20,8) DEFAULT NULL,
    `leverage` INT DEFAULT 10,
    `reduce_only` TINYINT(1) DEFAULT 0,
    `status` ENUM('pending', 'open', 'partially_filled', 'filled', 'cancelled', 'expired', 'rejected') DEFAULT 'pending',
    `time_in_force` ENUM('GTC', 'IOC', 'FOK', 'GTX') DEFAULT 'GTC',
    `fee` DECIMAL(20,8) DEFAULT 0,
    `realized_pnl` DECIMAL(20,8) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_symbol` (`symbol`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 거래 내역 테이블
CREATE TABLE IF NOT EXISTS `trades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` VARCHAR(50) NOT NULL UNIQUE,
    `order_id` VARCHAR(50) NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `symbol` VARCHAR(20) NOT NULL,
    `side` ENUM('buy', 'sell') NOT NULL,
    `price` DECIMAL(20,8) NOT NULL,
    `quantity` DECIMAL(20,8) NOT NULL,
    `quote_quantity` DECIMAL(20,8) NOT NULL,
    `fee` DECIMAL(20,8) DEFAULT 0,
    `fee_currency` VARCHAR(20) DEFAULT 'USDT',
    `realized_pnl` DECIMAL(20,8) DEFAULT 0,
    `is_maker` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_symbol` (`symbol`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 대회 테이블
CREATE TABLE IF NOT EXISTS `competitions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `rules` TEXT,
    `banner_image` VARCHAR(255) DEFAULT NULL,
    `type` ENUM('roi', 'pnl', 'volume', 'team') DEFAULT 'roi',
    `status` ENUM('upcoming', 'active', 'ended', 'cancelled') DEFAULT 'upcoming',
    `entry_fee` DECIMAL(20,8) DEFAULT 0,
    `min_balance` DECIMAL(20,8) DEFAULT 0,
    `max_participants` INT DEFAULT NULL,
    `prize_pool` DECIMAL(20,8) DEFAULT 0,
    `prize_currency` VARCHAR(20) DEFAULT 'USDT',
    `start_at` DATETIME NOT NULL,
    `end_at` DATETIME NOT NULL,
    `registration_start` DATETIME DEFAULT NULL,
    `registration_end` DATETIME DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_start_at` (`start_at`),
    INDEX `idx_end_at` (`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 대회 보상 테이블
CREATE TABLE IF NOT EXISTS `competition_prizes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `competition_id` INT UNSIGNED NOT NULL,
    `rank_from` INT NOT NULL,
    `rank_to` INT NOT NULL,
    `prize_amount` DECIMAL(20,8) NOT NULL,
    `prize_type` ENUM('usdt', 'bonus', 'nft', 'other') DEFAULT 'usdt',
    `prize_description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 대회 참가자 테이블
CREATE TABLE IF NOT EXISTS `competition_participants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `competition_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `team_id` INT UNSIGNED DEFAULT NULL,
    `starting_balance` DECIMAL(20,8) DEFAULT 0,
    `current_balance` DECIMAL(20,8) DEFAULT 0,
    `pnl` DECIMAL(20,8) DEFAULT 0,
    `roi` DECIMAL(10,4) DEFAULT 0,
    `trade_count` INT DEFAULT 0,
    `volume` DECIMAL(20,8) DEFAULT 0,
    `win_count` INT DEFAULT 0,
    `loss_count` INT DEFAULT 0,
    `current_rank` INT DEFAULT NULL,
    `best_rank` INT DEFAULT NULL,
    `status` ENUM('registered', 'active', 'disqualified', 'completed') DEFAULT 'registered',
    `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_comp_user` (`competition_id`, `user_id`),
    INDEX `idx_roi` (`roi`),
    INDEX `idx_pnl` (`pnl`),
    INDEX `idx_rank` (`current_rank`),
    FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 리더보드 스냅샷 테이블 (캐싱용)
CREATE TABLE IF NOT EXISTS `leaderboard_snapshots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `period` ENUM('daily', 'weekly', 'monthly', 'all') NOT NULL,
    `period_key` VARCHAR(20) NOT NULL,
    `pnl` DECIMAL(20,8) DEFAULT 0,
    `roi` DECIMAL(10,4) DEFAULT 0,
    `volume` DECIMAL(20,8) DEFAULT 0,
    `trade_count` INT DEFAULT 0,
    `win_rate` DECIMAL(5,2) DEFAULT 0,
    `rank` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_period` (`user_id`, `period`, `period_key`),
    INDEX `idx_period` (`period`, `period_key`),
    INDEX `idx_rank` (`rank`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 입출금 내역 테이블
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tx_id` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('deposit', 'withdraw', 'transfer', 'bonus', 'fee', 'refund', 'prize') NOT NULL,
    `currency` VARCHAR(20) DEFAULT 'USDT',
    `amount` DECIMAL(20,8) NOT NULL,
    `fee` DECIMAL(20,8) DEFAULT 0,
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    `address` VARCHAR(255) DEFAULT NULL,
    `tx_hash` VARCHAR(255) DEFAULT NULL,
    `network` VARCHAR(20) DEFAULT NULL,
    `memo` VARCHAR(255) DEFAULT NULL,
    `admin_note` TEXT DEFAULT NULL,
    `processed_by` INT UNSIGNED DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 관리자 활동 로그
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) DEFAULT NULL,
    `target_id` INT UNSIGNED DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 시스템 설정 테이블
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    `type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `group` VARCHAR(50) DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 공지사항 테이블
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `type` ENUM('notice', 'event', 'maintenance', 'update') DEFAULT 'notice',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `is_pinned` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `start_at` DATETIME DEFAULT NULL,
    `end_at` DATETIME DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기본 설정 데이터 삽입
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `description`) VALUES
('site_name', 'FuturX', 'string', 'general', '사이트 이름'),
('site_description', '암호화폐 선물거래 플랫폼', 'string', 'general', '사이트 설명'),
('maintenance_mode', 'false', 'boolean', 'general', '점검 모드'),
('max_leverage', '125', 'number', 'trading', '최대 레버리지'),
('taker_fee', '0.0006', 'number', 'trading', '테이커 수수료'),
('maker_fee', '0.0002', 'number', 'trading', '메이커 수수료'),
('min_withdraw', '10', 'number', 'wallet', '최소 출금액'),
('max_withdraw_daily', '100000', 'number', 'wallet', '일일 최대 출금액'),
('registration_enabled', 'true', 'boolean', 'auth', '회원가입 허용'),
('kyc_required', 'false', 'boolean', 'auth', 'KYC 필수 여부')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 테스트용 관리자 계정 (비밀번호: admin123)
INSERT INTO `users` (`uuid`, `email`, `username`, `password`, `nickname`, `role`, `status`, `is_verified`) VALUES
('admin-uuid-0001', 'admin@futurx.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'superadmin', 'active', 1)
ON DUPLICATE KEY UPDATE `role` = 'superadmin';
