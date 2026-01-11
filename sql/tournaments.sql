-- ========================================
-- FuturX 토너먼트(대회) 시스템 테이블
-- ========================================

-- 토너먼트 메인 테이블
CREATE TABLE IF NOT EXISTS tournaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id VARCHAR(32) UNIQUE NOT NULL,

    -- 기본 정보
    title VARCHAR(100) NOT NULL,
    subtitle VARCHAR(200),
    description TEXT,
    rules TEXT,

    -- 타입 (daily, weekly, monthly, special)
    type ENUM('daily', 'weekly', 'monthly', 'special') DEFAULT 'weekly',

    -- 상태
    status ENUM('upcoming', 'registration', 'active', 'calculating', 'ended') DEFAULT 'upcoming',

    -- 기간
    registration_start DATETIME NOT NULL,
    registration_end DATETIME NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,

    -- 참가 조건
    entry_fee DECIMAL(20,8) DEFAULT 0,
    min_balance DECIMAL(20,8) DEFAULT 100,
    max_participants INT DEFAULT 0, -- 0 = 무제한
    allowed_symbols JSON, -- 허용 거래쌍 (null = 모두)

    -- 초기 자금 설정
    starting_balance DECIMAL(20,8) DEFAULT 10000, -- 대회용 시작 자금

    -- 상금풀
    prize_pool DECIMAL(20,8) DEFAULT 0,
    prize_currency VARCHAR(10) DEFAULT 'USDT',
    prize_distribution JSON, -- [{"rank": 1, "percent": 50}, {"rank": 2, "percent": 30}, ...]

    -- 메타
    banner_image VARCHAR(255),
    badge_icon VARCHAR(50),
    badge_color VARCHAR(20),

    -- 통계
    total_participants INT DEFAULT 0,
    total_trades INT DEFAULT 0,
    total_volume DECIMAL(30,8) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_start_time (start_time),
    INDEX idx_end_time (end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 토너먼트 참가자
CREATE TABLE IF NOT EXISTS tournament_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id VARCHAR(32) NOT NULL,
    user_id INT NOT NULL,

    -- 대회용 잔고 (실제 잔고와 분리)
    tournament_balance DECIMAL(20,8) DEFAULT 0,
    starting_balance DECIMAL(20,8) DEFAULT 0,

    -- 성과 지표
    pnl DECIMAL(20,8) DEFAULT 0,
    pnl_percent DECIMAL(10,4) DEFAULT 0,
    total_trades INT DEFAULT 0,
    winning_trades INT DEFAULT 0,
    losing_trades INT DEFAULT 0,
    total_volume DECIMAL(30,8) DEFAULT 0,
    max_drawdown DECIMAL(10,4) DEFAULT 0,

    -- 순위
    current_rank INT DEFAULT 0,
    best_rank INT DEFAULT 0,
    final_rank INT DEFAULT 0,

    -- 보상
    prize_amount DECIMAL(20,8) DEFAULT 0,
    prize_claimed BOOLEAN DEFAULT FALSE,
    prize_claimed_at DATETIME,

    -- 상태
    status ENUM('registered', 'active', 'disqualified', 'completed') DEFAULT 'registered',
    disqualify_reason VARCHAR(255),

    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_trade_at DATETIME,

    UNIQUE KEY unique_participant (tournament_id, user_id),
    INDEX idx_tournament (tournament_id),
    INDEX idx_user (user_id),
    INDEX idx_pnl_percent (pnl_percent DESC),
    INDEX idx_current_rank (current_rank),

    FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 토너먼트 거래 내역 (대회용 별도 기록)
CREATE TABLE IF NOT EXISTS tournament_trades (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    trade_id VARCHAR(32) UNIQUE NOT NULL,
    tournament_id VARCHAR(32) NOT NULL,
    user_id INT NOT NULL,

    -- 거래 정보
    symbol VARCHAR(20) NOT NULL,
    side ENUM('long', 'short') NOT NULL,
    type ENUM('market', 'limit') DEFAULT 'market',

    -- 주문 정보
    entry_price DECIMAL(20,8) NOT NULL,
    exit_price DECIMAL(20,8),
    quantity DECIMAL(20,8) NOT NULL,
    leverage INT DEFAULT 1,

    -- 수익
    pnl DECIMAL(20,8) DEFAULT 0,
    pnl_percent DECIMAL(10,4) DEFAULT 0,
    fee DECIMAL(20,8) DEFAULT 0,

    -- 상태
    status ENUM('open', 'closed', 'liquidated') DEFAULT 'open',

    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME,

    INDEX idx_tournament_user (tournament_id, user_id),
    INDEX idx_symbol (symbol),
    INDEX idx_status (status),
    INDEX idx_opened_at (opened_at),

    FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 리더보드 스냅샷 (시간별 순위 기록)
CREATE TABLE IF NOT EXISTS tournament_leaderboard_snapshots (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tournament_id VARCHAR(32) NOT NULL,
    user_id INT NOT NULL,

    rank_position INT NOT NULL,
    pnl DECIMAL(20,8) DEFAULT 0,
    pnl_percent DECIMAL(10,4) DEFAULT 0,
    balance DECIMAL(20,8) DEFAULT 0,

    snapshot_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tournament_snapshot (tournament_id, snapshot_at),
    INDEX idx_user_snapshot (user_id, snapshot_at),

    FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 토너먼트 보상 테이블
CREATE TABLE IF NOT EXISTS tournament_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id VARCHAR(32) NOT NULL,
    user_id INT NOT NULL,

    -- 보상 타입
    reward_type ENUM('prize', 'badge', 'title', 'bonus') NOT NULL,

    -- 보상 내용
    rank_achieved INT,
    amount DECIMAL(20,8) DEFAULT 0,
    currency VARCHAR(10),
    badge_id VARCHAR(50),
    title_text VARCHAR(100),

    -- 상태
    status ENUM('pending', 'claimed', 'expired') DEFAULT 'pending',
    claimed_at DATETIME,
    expires_at DATETIME,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tournament (tournament_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),

    FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 토너먼트 공지/뉴스
CREATE TABLE IF NOT EXISTS tournament_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id VARCHAR(32),

    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('info', 'warning', 'update', 'result') DEFAULT 'info',
    is_pinned BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tournament (tournament_id),
    INDEX idx_pinned (is_pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 샘플 토너먼트 데이터
INSERT INTO tournaments (
    tournament_id, title, subtitle, description, type, status,
    registration_start, registration_end, start_time, end_time,
    entry_fee, min_balance, max_participants, starting_balance,
    prize_pool, prize_currency, prize_distribution,
    badge_icon, badge_color
) VALUES
(
    'WEEKLY_001',
    '주간 트레이딩 챔피언십',
    '매주 열리는 실력 검증의 장',
    '# 주간 트레이딩 챔피언십\n\n매주 월요일부터 일요일까지 진행되는 트레이딩 대회입니다.\n\n## 참가 방법\n1. 등록 기간 내 참가 신청\n2. 대회 시작 시 자동 시작 자금 지급\n3. 실시간 순위 확인 및 거래\n\n## 순위 산정\n- 수익률(PnL %) 기준\n- 최소 5회 이상 거래 필수',
    'weekly',
    'active',
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_ADD(NOW(), INTERVAL 6 DAY),
    0,
    100,
    0,
    10000,
    50000,
    'USDT',
    '[{"rank":1,"percent":40,"title":"1위"},{"rank":2,"percent":25,"title":"2위"},{"rank":3,"percent":15,"title":"3위"},{"rank_from":4,"rank_to":10,"percent":15,"title":"4-10위"},{"rank_from":11,"rank_to":50,"percent":5,"title":"11-50위"}]',
    'trophy',
    '#f0b90b'
),
(
    'MONTHLY_JAN',
    '1월 마스터 토너먼트',
    '새해 첫 대형 대회 - 총 상금 500,000 USDT',
    '# 1월 마스터 토너먼트\n\n2026년 새해를 맞아 진행되는 대형 토너먼트입니다.\n\n## 특별 보상\n- 1위: 200,000 USDT + 골드 트로피 뱃지\n- 2위: 100,000 USDT + 실버 트로피 뱃지\n- 3위: 50,000 USDT + 브론즈 트로피 뱃지\n\n## 참가 조건\n- 최소 잔고 500 USDT 이상\n- 본인인증 완료 회원',
    'monthly',
    'registration',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    DATE_ADD(NOW(), INTERVAL 35 DAY),
    10,
    500,
    1000,
    50000,
    500000,
    'USDT',
    '[{"rank":1,"percent":40,"amount":200000,"title":"챔피언"},{"rank":2,"percent":20,"amount":100000,"title":"준우승"},{"rank":3,"percent":10,"amount":50000,"title":"3위"},{"rank_from":4,"rank_to":10,"percent":14,"title":"Top 10"},{"rank_from":11,"rank_to":50,"percent":10,"title":"Top 50"},{"rank_from":51,"rank_to":100,"percent":6,"title":"Top 100"}]',
    'crown',
    '#ffd700'
),
(
    'DAILY_SPRINT',
    '데일리 스프린트',
    '24시간 단기 승부',
    '매일 진행되는 24시간 단기 토너먼트입니다. 빠른 판단력과 실행력이 필요합니다.',
    'daily',
    'upcoming',
    DATE_ADD(NOW(), INTERVAL 1 DAY),
    DATE_ADD(NOW(), INTERVAL 1 DAY),
    DATE_ADD(NOW(), INTERVAL 1 DAY),
    DATE_ADD(NOW(), INTERVAL 2 DAY),
    0,
    50,
    500,
    5000,
    10000,
    'USDT',
    '[{"rank":1,"percent":50},{"rank":2,"percent":30},{"rank":3,"percent":20}]',
    'bolt',
    '#00d4ff'
),
(
    'SPECIAL_NEWBIE',
    '신규회원 웰컴 토너먼트',
    '가입 30일 이내 회원 전용',
    '신규 회원들을 위한 특별 토너먼트입니다. 부담 없이 참여하세요!',
    'special',
    'active',
    DATE_SUB(NOW(), INTERVAL 10 DAY),
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_ADD(NOW(), INTERVAL 4 DAY),
    0,
    0,
    0,
    1000,
    5000,
    'USDT',
    '[{"rank":1,"percent":40},{"rank":2,"percent":30},{"rank":3,"percent":20},{"rank_from":4,"rank_to":10,"percent":10}]',
    'gift',
    '#ff6b6b'
);

-- 샘플 참가자 데이터 (테스트용)
INSERT INTO tournament_participants (
    tournament_id, user_id, tournament_balance, starting_balance,
    pnl, pnl_percent, total_trades, winning_trades, losing_trades,
    total_volume, current_rank, status
) VALUES
('WEEKLY_001', 1, 12500.00, 10000.00, 2500.00, 25.00, 48, 32, 16, 850000, 1, 'active'),
('WEEKLY_001', 2, 11800.00, 10000.00, 1800.00, 18.00, 35, 22, 13, 620000, 2, 'active'),
('WEEKLY_001', 3, 11200.00, 10000.00, 1200.00, 12.00, 52, 30, 22, 980000, 3, 'active'),
('WEEKLY_001', 4, 10800.00, 10000.00, 800.00, 8.00, 28, 16, 12, 420000, 4, 'active'),
('WEEKLY_001', 5, 10500.00, 10000.00, 500.00, 5.00, 41, 23, 18, 550000, 5, 'active'),
('WEEKLY_001', 6, 10200.00, 10000.00, 200.00, 2.00, 19, 11, 8, 280000, 6, 'active'),
('WEEKLY_001', 7, 9800.00, 10000.00, -200.00, -2.00, 33, 15, 18, 470000, 7, 'active'),
('WEEKLY_001', 8, 9500.00, 10000.00, -500.00, -5.00, 25, 10, 15, 380000, 8, 'active'),
('WEEKLY_001', 9, 9200.00, 10000.00, -800.00, -8.00, 44, 18, 26, 720000, 9, 'active'),
('WEEKLY_001', 10, 8800.00, 10000.00, -1200.00, -12.00, 38, 14, 24, 510000, 10, 'active');

-- 샘플 공지사항
INSERT INTO tournament_announcements (tournament_id, title, content, type, is_pinned) VALUES
('WEEKLY_001', '대회 시작 안내', '주간 트레이딩 챔피언십이 시작되었습니다. 모든 참가자분들의 건승을 빕니다!', 'info', TRUE),
('WEEKLY_001', '중간 순위 발표', '현재 1위는 25%의 수익률을 기록 중입니다. 아직 기회는 많습니다!', 'update', FALSE),
(NULL, '1월 마스터 토너먼트 참가자 모집', '역대 최대 규모의 토너먼트가 곧 시작됩니다. 지금 바로 등록하세요!', 'info', TRUE);
