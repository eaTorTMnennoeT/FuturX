-- g5_futures_trades 테이블 생성
-- 거래 기록 (체결, 청산 등)을 저장하여 서버 재시작 후에도 유지

CREATE TABLE IF NOT EXISTS `g5_futures_trades` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `mb_id` INT NOT NULL COMMENT '회원 ID',
    `symbol` VARCHAR(20) NOT NULL DEFAULT 'BTCUSDT' COMMENT '심볼',
    `type` VARCHAR(20) NOT NULL COMMENT '거래 타입 (FILLED, OPEN_POSITION, CLOSE_POSITION, LIQUIDATION)',
    `side` VARCHAR(10) NOT NULL COMMENT '방향 (buy, sell, long, short)',
    `position_side` VARCHAR(10) DEFAULT NULL COMMENT '포지션 방향 (LONG, SHORT)',
    `quantity` DECIMAL(20,8) NOT NULL COMMENT '수량',
    `price` DECIMAL(20,8) NOT NULL COMMENT '가격',
    `pnl` DECIMAL(20,8) DEFAULT NULL COMMENT '손익 (청산 시)',
    `leverage` INT DEFAULT NULL COMMENT '레버리지',
    `order_id` INT DEFAULT NULL COMMENT '관련 주문 ID',
    `position_id` INT DEFAULT NULL COMMENT '관련 포지션 ID',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_mb_id` (`mb_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`mb_id`) REFERENCES `g5_member`(`mb_no`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='선물 거래 기록';
