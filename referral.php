<?php
/**
 * FuturX - 추천인 프로그램 페이지 (고급 버전)
 * 상세 통계, 수수료 내역, 출금 관리, 마케팅 도구
 */

require_once __DIR__ . '/includes/bootstrap.php';

$csrf_token = generateCSRFToken();

// 현재 탭
$currentTab = $_GET['tab'] ?? 'dashboard';

// 데모 사용자 데이터
$user = [
    'id' => 1,
    'nickname' => 'CryptoTrader',
    'referral_code' => 'FTX8K2M9',
    'vip_level' => 2,
    'tier' => 'Gold',
    'tier_expires' => '2024-12-31'
];

// 추천인 상세 통계
$stats = [
    'total_referrals' => 156,
    'active_referrals' => 89,
    'inactive_referrals' => 52,
    'pending_referrals' => 15,
    'total_earnings' => 12580.50,
    'this_month_earnings' => 1850.25,
    'last_month_earnings' => 2100.50,
    'pending_earnings' => 245.80,
    'withdrawn_earnings' => 10284.45,
    'commission_rate' => 20,
    'total_volume_generated' => 2580000.00,
    'avg_referral_volume' => 16538.46,
    'conversion_rate' => 68.5,
    'active_rate' => 57.05
];

// 일별 통계 (최근 30일)
$dailyStats = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyStats[] = [
        'date' => $date,
        'new_referrals' => rand(0, 8),
        'earnings' => rand(20, 150) + (rand(0, 99) / 100),
        'volume' => rand(5000, 50000)
    ];
}

// 월별 수익 데이터
$monthlyEarnings = [
    ['month' => '2023-07', 'earnings' => 420.50, 'referrals' => 8, 'volume' => 180000],
    ['month' => '2023-08', 'earnings' => 580.50, 'referrals' => 12, 'volume' => 250000],
    ['month' => '2023-09', 'earnings' => 890.25, 'referrals' => 18, 'volume' => 380000],
    ['month' => '2023-10', 'earnings' => 1250.00, 'referrals' => 22, 'volume' => 520000],
    ['month' => '2023-11', 'earnings' => 1580.75, 'referrals' => 28, 'volume' => 650000],
    ['month' => '2023-12', 'earnings' => 2100.50, 'referrals' => 35, 'volume' => 820000],
    ['month' => '2024-01', 'earnings' => 1850.25, 'referrals' => 33, 'volume' => 780000]
];

// 수수료 내역 (상세)
$commissionHistory = [
    [
        'id' => 'COM-20240120-001',
        'date' => '2024-01-20 14:32:15',
        'referral_nickname' => 'Whale***01',
        'type' => 'trading_fee',
        'trade_volume' => 125000.00,
        'fee_paid' => 125.00,
        'commission_rate' => 20,
        'commission' => 25.00,
        'status' => 'credited'
    ],
    [
        'id' => 'COM-20240120-002',
        'date' => '2024-01-20 12:15:30',
        'referral_nickname' => 'Bull***run',
        'type' => 'trading_fee',
        'trade_volume' => 85000.00,
        'fee_paid' => 85.00,
        'commission_rate' => 20,
        'commission' => 17.00,
        'status' => 'credited'
    ],
    [
        'id' => 'COM-20240120-003',
        'date' => '2024-01-20 10:45:00',
        'referral_nickname' => 'Moon***der',
        'type' => 'trading_fee',
        'trade_volume' => 45000.00,
        'fee_paid' => 45.00,
        'commission_rate' => 20,
        'commission' => 9.00,
        'status' => 'credited'
    ],
    [
        'id' => 'COM-20240119-001',
        'date' => '2024-01-19 22:10:45',
        'referral_nickname' => 'Crypto***er',
        'type' => 'trading_fee',
        'trade_volume' => 32000.00,
        'fee_paid' => 32.00,
        'commission_rate' => 20,
        'commission' => 6.40,
        'status' => 'credited'
    ],
    [
        'id' => 'COM-20240119-002',
        'date' => '2024-01-19 18:30:20',
        'referral_nickname' => 'Diam***ands',
        'type' => 'trading_fee',
        'trade_volume' => 78000.00,
        'fee_paid' => 78.00,
        'commission_rate' => 20,
        'commission' => 15.60,
        'status' => 'credited'
    ],
    [
        'id' => 'COM-20240119-003',
        'date' => '2024-01-19 15:22:10',
        'referral_nickname' => 'Trader***01',
        'type' => 'signup_bonus',
        'trade_volume' => 0,
        'fee_paid' => 0,
        'commission_rate' => 0,
        'commission' => 5.00,
        'status' => 'credited'
    ],
    [
        'id' => 'COM-20240118-001',
        'date' => '2024-01-18 20:45:30',
        'referral_nickname' => 'Whale***01',
        'type' => 'trading_fee',
        'trade_volume' => 250000.00,
        'fee_paid' => 250.00,
        'commission_rate' => 20,
        'commission' => 50.00,
        'status' => 'credited'
    ],
    [
        'id' => 'COM-20240118-002',
        'date' => '2024-01-18 16:12:00',
        'referral_nickname' => 'Bull***run',
        'type' => 'kyc_bonus',
        'trade_volume' => 0,
        'fee_paid' => 0,
        'commission_rate' => 0,
        'commission' => 10.00,
        'status' => 'credited'
    ]
];

// 출금 내역
$withdrawalHistory = [
    [
        'id' => 'WD-20240115-001',
        'date' => '2024-01-15 10:00:00',
        'amount' => 500.00,
        'fee' => 0,
        'net_amount' => 500.00,
        'method' => 'USDT (TRC20)',
        'address' => 'TXyz...abc123',
        'status' => 'completed',
        'completed_at' => '2024-01-15 10:05:32'
    ],
    [
        'id' => 'WD-20240101-001',
        'date' => '2024-01-01 09:30:00',
        'amount' => 1000.00,
        'fee' => 0,
        'net_amount' => 1000.00,
        'method' => 'USDT (TRC20)',
        'address' => 'TXyz...abc123',
        'status' => 'completed',
        'completed_at' => '2024-01-01 09:35:15'
    ],
    [
        'id' => 'WD-20231215-001',
        'date' => '2023-12-15 14:20:00',
        'amount' => 800.00,
        'fee' => 0,
        'net_amount' => 800.00,
        'method' => 'USDT (TRC20)',
        'address' => 'TXyz...abc123',
        'status' => 'completed',
        'completed_at' => '2023-12-15 14:25:48'
    ]
];

// 추천인 리스트 (상세 정보)
$referralList = [
    [
        'id' => 1,
        'nickname' => 'Whale***01',
        'email' => 'wha***@gmail.com',
        'joined_at' => '2024-01-03 20:15:00',
        'status' => 'active',
        'kyc_level' => 2,
        'total_volume' => 1250000.00,
        'total_trades' => 156,
        'my_earnings' => 2500.00,
        'last_trade' => '2024-01-20 12:45:00',
        'avg_trade_size' => 8012.82,
        'deposit_total' => 50000.00,
        'is_vip' => true
    ],
    [
        'id' => 2,
        'nickname' => 'Bull***run',
        'email' => 'bul***@naver.com',
        'joined_at' => '2024-01-08 12:00:00',
        'status' => 'active',
        'kyc_level' => 2,
        'total_volume' => 450000.00,
        'total_trades' => 89,
        'my_earnings' => 900.00,
        'last_trade' => '2024-01-20 10:00:00',
        'avg_trade_size' => 5056.18,
        'deposit_total' => 25000.00,
        'is_vip' => false
    ],
    [
        'id' => 3,
        'nickname' => 'Diam***ands',
        'email' => 'dia***@kakao.com',
        'joined_at' => '2024-01-05 09:30:00',
        'status' => 'active',
        'kyc_level' => 2,
        'total_volume' => 320500.25,
        'total_trades' => 67,
        'my_earnings' => 641.00,
        'last_trade' => '2024-01-19 18:20:00',
        'avg_trade_size' => 4783.59,
        'deposit_total' => 18000.00,
        'is_vip' => false
    ],
    [
        'id' => 4,
        'nickname' => 'Moon***der',
        'email' => 'moo***@gmail.com',
        'joined_at' => '2024-01-12 08:00:00',
        'status' => 'active',
        'kyc_level' => 2,
        'total_volume' => 256800.75,
        'total_trades' => 52,
        'my_earnings' => 513.60,
        'last_trade' => '2024-01-20 11:30:00',
        'avg_trade_size' => 4938.48,
        'deposit_total' => 15000.00,
        'is_vip' => false
    ],
    [
        'id' => 5,
        'nickname' => 'Trader***01',
        'email' => 'tra***@outlook.com',
        'joined_at' => '2024-01-15 14:30:00',
        'status' => 'active',
        'kyc_level' => 2,
        'total_volume' => 125000.50,
        'total_trades' => 34,
        'my_earnings' => 250.10,
        'last_trade' => '2024-01-20 09:15:00',
        'avg_trade_size' => 3676.49,
        'deposit_total' => 8000.00,
        'is_vip' => false
    ],
    [
        'id' => 6,
        'nickname' => 'Crypto***er',
        'email' => 'cry***@gmail.com',
        'joined_at' => '2024-01-14 10:20:00',
        'status' => 'active',
        'kyc_level' => 1,
        'total_volume' => 89500.00,
        'total_trades' => 28,
        'my_earnings' => 179.00,
        'last_trade' => '2024-01-19 22:45:00',
        'avg_trade_size' => 3196.43,
        'deposit_total' => 5000.00,
        'is_vip' => false
    ],
    [
        'id' => 7,
        'nickname' => 'Hodl***ing',
        'email' => 'hod***@naver.com',
        'joined_at' => '2024-01-10 16:45:00',
        'status' => 'inactive',
        'kyc_level' => 1,
        'total_volume' => 15000.00,
        'total_trades' => 5,
        'my_earnings' => 30.00,
        'last_trade' => '2024-01-11 08:20:00',
        'avg_trade_size' => 3000.00,
        'deposit_total' => 1000.00,
        'is_vip' => false
    ],
    [
        'id' => 8,
        'nickname' => 'NewT***der',
        'email' => 'new***@gmail.com',
        'joined_at' => '2024-01-19 00:00:00',
        'status' => 'pending',
        'kyc_level' => 0,
        'total_volume' => 0,
        'total_trades' => 0,
        'my_earnings' => 0,
        'last_trade' => null,
        'avg_trade_size' => 0,
        'deposit_total' => 0,
        'is_vip' => false
    ]
];

// 티어 정보
$tiers = [
    ['name' => 'Bronze', 'min_referrals' => 0, 'commission' => 15, 'bonus' => 0, 'color' => '#cd7f32'],
    ['name' => 'Silver', 'min_referrals' => 10, 'commission' => 18, 'bonus' => 50, 'color' => '#c0c0c0'],
    ['name' => 'Gold', 'min_referrals' => 50, 'commission' => 20, 'bonus' => 200, 'color' => '#f0b90b'],
    ['name' => 'Platinum', 'min_referrals' => 100, 'commission' => 25, 'bonus' => 500, 'color' => '#e5e4e2'],
    ['name' => 'Diamond', 'min_referrals' => 500, 'commission' => 30, 'bonus' => 2000, 'color' => '#b9f2ff']
];

// 상위 추천인 (리더보드)
$topReferrers = [
    ['rank' => 1, 'nickname' => 'Whale***01', 'volume' => 1250000.00, 'earnings' => 2500.00],
    ['rank' => 2, 'nickname' => 'Bull***run', 'volume' => 450000.00, 'earnings' => 900.00],
    ['rank' => 3, 'nickname' => 'Diam***ands', 'volume' => 320500.25, 'earnings' => 641.00],
    ['rank' => 4, 'nickname' => 'Moon***der', 'volume' => 256800.75, 'earnings' => 513.60],
    ['rank' => 5, 'nickname' => 'Trader***01', 'volume' => 125000.50, 'earnings' => 250.10]
];

$pageTitle = '추천인 프로그램';
$pageCSS = ['referral.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<main class="main">
    <div class="referral-page">
        <!-- 사이드바 -->
        <aside class="referral-sidebar">
            <div class="referral-sidebar__profile">
                <div class="referral-sidebar__avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="referral-sidebar__info">
                    <h3><?= e($user['nickname']) ?></h3>
                    <span class="referral-tier-label referral-tier-label--<?= strtolower($user['tier']) ?>">
                        <i class="fas fa-crown"></i> <?= $user['tier'] ?> 파트너
                    </span>
                </div>
            </div>

            <div class="referral-sidebar__balance">
                <span class="referral-sidebar__balance-label">출금 가능 잔액</span>
                <span class="referral-sidebar__balance-value">$<?= number_format($stats['pending_earnings'], 2) ?></span>
                <button class="btn btn--primary btn--sm btn--full" onclick="openWithdrawModal()">
                    <i class="fas fa-wallet"></i> 출금하기
                </button>
            </div>

            <nav class="referral-sidebar__nav">
                <a href="?tab=guide" class="referral-sidebar__nav-item <?= $currentTab === 'guide' ? 'active' : '' ?>">
                    <i class="fas fa-book-open"></i>
                    <span>안내</span>
                    <span class="referral-sidebar__badge" style="background: var(--color-primary); color: var(--bg-primary);">NEW</span>
                </a>
                <a href="?tab=dashboard" class="referral-sidebar__nav-item <?= $currentTab === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>대시보드</span>
                </a>
                <a href="?tab=referrals" class="referral-sidebar__nav-item <?= $currentTab === 'referrals' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>내 추천인</span>
                    <span class="referral-sidebar__badge"><?= $stats['total_referrals'] ?></span>
                </a>
                <a href="?tab=commissions" class="referral-sidebar__nav-item <?= $currentTab === 'commissions' ? 'active' : '' ?>">
                    <i class="fas fa-coins"></i>
                    <span>수수료 내역</span>
                </a>
                <a href="?tab=withdrawals" class="referral-sidebar__nav-item <?= $currentTab === 'withdrawals' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>출금 내역</span>
                </a>
                <a href="?tab=marketing" class="referral-sidebar__nav-item <?= $currentTab === 'marketing' ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>마케팅 도구</span>
                </a>
                <a href="?tab=settings" class="referral-sidebar__nav-item <?= $currentTab === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>설정</span>
                </a>
            </nav>

            <div class="referral-sidebar__code">
                <span class="referral-sidebar__code-label">내 추천 코드</span>
                <div class="referral-sidebar__code-value">
                    <code><?= e($user['referral_code']) ?></code>
                    <button class="referral-copy-btn" onclick="copyCode('<?= e($user['referral_code']) ?>')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- 메인 콘텐츠 -->
        <div class="referral-content">
            <?php if ($currentTab === 'guide'): ?>
            <!-- 안내 탭 - 3D 효과 포함 -->
            <div class="referral-guide-page">
                <!-- 히어로 섹션 -->
                <section class="guide-hero">
                    <div class="guide-hero__bg">
                        <div class="guide-hero__particles" id="particles"></div>
                        <div class="guide-hero__gradient"></div>
                    </div>
                    <div class="guide-hero__content">
                        <div class="guide-hero__badge">
                            <i class="fas fa-fire"></i>
                            최대 30% 커미션
                        </div>
                        <h1 class="guide-hero__title">
                            친구를 초대하고<br>
                            <span class="guide-hero__highlight">무한한 수익</span>을 만드세요
                        </h1>
                        <p class="guide-hero__desc">
                            FuturX 추천인 프로그램으로 평생 수익을 창출하세요.<br>
                            친구가 거래할 때마다 수수료의 최대 30%를 받을 수 있습니다.
                        </p>
                        <div class="guide-hero__cta">
                            <button class="btn btn--primary btn--lg guide-hero__btn" onclick="copyReferralLink()">
                                <i class="fas fa-link"></i>
                                내 추천 링크 복사하기
                            </button>
                            <button class="btn btn--outline btn--lg guide-hero__btn" onclick="scrollToSection('how-it-works')">
                                <i class="fas fa-play-circle"></i>
                                작동 방식 보기
                            </button>
                        </div>
                        <div class="guide-hero__stats">
                            <div class="guide-hero__stat">
                                <span class="guide-hero__stat-value">$2.5M+</span>
                                <span class="guide-hero__stat-label">총 지급 커미션</span>
                            </div>
                            <div class="guide-hero__stat">
                                <span class="guide-hero__stat-value">15,000+</span>
                                <span class="guide-hero__stat-label">활성 파트너</span>
                            </div>
                            <div class="guide-hero__stat">
                                <span class="guide-hero__stat-value">30%</span>
                                <span class="guide-hero__stat-label">최대 커미션율</span>
                            </div>
                        </div>
                    </div>
                    <div class="guide-hero__visual">
                        <div class="guide-hero__3d-card">
                            <div class="guide-3d-coin guide-3d-coin--btc">
                                <i class="fab fa-bitcoin"></i>
                            </div>
                            <div class="guide-3d-coin guide-3d-coin--eth">
                                <i class="fab fa-ethereum"></i>
                            </div>
                            <div class="guide-3d-coin guide-3d-coin--usdt">
                                <span>$</span>
                            </div>
                            <div class="guide-hero__money-flow">
                                <div class="guide-money-particle"></div>
                                <div class="guide-money-particle"></div>
                                <div class="guide-money-particle"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 왜 추천인 프로그램인가? -->
                <section class="guide-section guide-why" id="why-referral">
                    <div class="guide-section__header">
                        <span class="guide-section__badge">Why FuturX Referral?</span>
                        <h2 class="guide-section__title">왜 추천인 프로그램을 해야 할까요?</h2>
                        <p class="guide-section__desc">단순한 부수입이 아닙니다. 진정한 패시브 인컴을 만드세요.</p>
                    </div>

                    <div class="guide-why__grid">
                        <div class="guide-why-card guide-why-card--3d" data-tilt>
                            <div class="guide-why-card__icon">
                                <div class="guide-icon-3d guide-icon-3d--gold">
                                    <i class="fas fa-infinity"></i>
                                </div>
                            </div>
                            <h3>평생 수익</h3>
                            <p>한 번 추천하면 그 친구가 거래하는 한 영원히 수익이 발생합니다. 시간이 지날수록 수익은 눈덩이처럼 불어납니다.</p>
                            <div class="guide-why-card__highlight">
                                <span class="guide-why-card__number">∞</span>
                                <span class="guide-why-card__label">무한 수익 잠재력</span>
                            </div>
                        </div>

                        <div class="guide-why-card guide-why-card--3d" data-tilt>
                            <div class="guide-why-card__icon">
                                <div class="guide-icon-3d guide-icon-3d--green">
                                    <i class="fas fa-bed"></i>
                                </div>
                            </div>
                            <h3>잠자는 동안에도</h3>
                            <p>추천인이 새벽에 거래해도, 해외에서 거래해도 당신의 수익은 24시간 자동으로 쌓입니다.</p>
                            <div class="guide-why-card__highlight">
                                <span class="guide-why-card__number">24/7</span>
                                <span class="guide-why-card__label">실시간 수익 적립</span>
                            </div>
                        </div>

                        <div class="guide-why-card guide-why-card--3d" data-tilt>
                            <div class="guide-why-card__icon">
                                <div class="guide-icon-3d guide-icon-3d--blue">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <h3>복리 효과</h3>
                            <p>추천인이 많아질수록 티어가 올라가고, 티어가 올라가면 커미션율이 증가합니다. 수익이 수익을 만드는 구조입니다.</p>
                            <div class="guide-why-card__highlight">
                                <span class="guide-why-card__number">30%</span>
                                <span class="guide-why-card__label">최대 커미션율</span>
                            </div>
                        </div>

                        <div class="guide-why-card guide-why-card--3d" data-tilt>
                            <div class="guide-why-card__icon">
                                <div class="guide-icon-3d guide-icon-3d--purple">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                            </div>
                            <h3>제로 리스크</h3>
                            <p>투자금 없이, 손실 위험 없이 시작할 수 있습니다. 순수하게 공유만으로 수익을 창출하세요.</p>
                            <div class="guide-why-card__highlight">
                                <span class="guide-why-card__number">$0</span>
                                <span class="guide-why-card__label">시작 비용</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 수익 시뮬레이션 -->
                <section class="guide-section guide-calculator">
                    <div class="guide-section__header">
                        <span class="guide-section__badge">Income Simulator</span>
                        <h2 class="guide-section__title">당신의 수익을 계산해보세요</h2>
                        <p class="guide-section__desc">실제 수익 예상치를 확인하고 목표를 세워보세요.</p>
                    </div>

                    <div class="guide-calc-container">
                        <div class="guide-calc-card guide-calc-card--3d">
                            <div class="guide-calc-inputs">
                                <div class="guide-calc-input-group">
                                    <label>추천인 수</label>
                                    <div class="guide-calc-slider-wrap">
                                        <input type="range" id="calcReferrals" min="1" max="500" value="50" class="guide-calc-slider">
                                        <span class="guide-calc-value" id="calcReferralsValue">50명</span>
                                    </div>
                                </div>
                                <div class="guide-calc-input-group">
                                    <label>추천인 평균 월 거래량</label>
                                    <div class="guide-calc-slider-wrap">
                                        <input type="range" id="calcVolume" min="1000" max="100000" step="1000" value="10000" class="guide-calc-slider">
                                        <span class="guide-calc-value" id="calcVolumeValue">$10,000</span>
                                    </div>
                                </div>
                                <div class="guide-calc-input-group">
                                    <label>파트너 등급</label>
                                    <select id="calcTier" class="guide-calc-select">
                                        <option value="15">Bronze (15%)</option>
                                        <option value="18">Silver (18%)</option>
                                        <option value="20" selected>Gold (20%)</option>
                                        <option value="25">Platinum (25%)</option>
                                        <option value="30">Diamond (30%)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="guide-calc-results">
                                <div class="guide-calc-result guide-calc-result--main">
                                    <div class="guide-calc-result__icon">
                                        <i class="fas fa-sack-dollar"></i>
                                    </div>
                                    <div class="guide-calc-result__content">
                                        <span class="guide-calc-result__label">예상 월 수익</span>
                                        <span class="guide-calc-result__value" id="calcMonthly">$1,000</span>
                                    </div>
                                </div>
                                <div class="guide-calc-result-grid">
                                    <div class="guide-calc-result guide-calc-result--sub">
                                        <span class="guide-calc-result__label">연간 수익</span>
                                        <span class="guide-calc-result__value" id="calcYearly">$12,000</span>
                                    </div>
                                    <div class="guide-calc-result guide-calc-result--sub">
                                        <span class="guide-calc-result__label">일 평균</span>
                                        <span class="guide-calc-result__value" id="calcDaily">$33.33</span>
                                    </div>
                                </div>
                            </div>

                            <div class="guide-calc-note">
                                <i class="fas fa-info-circle"></i>
                                <span>실제 수익은 추천인의 거래 활동에 따라 달라질 수 있습니다.</span>
                            </div>
                        </div>

                        <div class="guide-calc-examples">
                            <h4>실제 파트너 수익 사례</h4>
                            <div class="guide-calc-example">
                                <div class="guide-calc-example__avatar">
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=trader1" alt="">
                                    <span class="guide-calc-example__badge">Diamond</span>
                                </div>
                                <div class="guide-calc-example__info">
                                    <span class="guide-calc-example__name">Crypto***King</span>
                                    <span class="guide-calc-example__stats">542 추천인 · 6개월차</span>
                                </div>
                                <div class="guide-calc-example__earning">
                                    <span class="guide-calc-example__amount">$8,450</span>
                                    <span class="guide-calc-example__period">/월</span>
                                </div>
                            </div>
                            <div class="guide-calc-example">
                                <div class="guide-calc-example__avatar">
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=trader2" alt="">
                                    <span class="guide-calc-example__badge">Platinum</span>
                                </div>
                                <div class="guide-calc-example__info">
                                    <span class="guide-calc-example__name">Bull***Master</span>
                                    <span class="guide-calc-example__stats">215 추천인 · 4개월차</span>
                                </div>
                                <div class="guide-calc-example__earning">
                                    <span class="guide-calc-example__amount">$3,280</span>
                                    <span class="guide-calc-example__period">/월</span>
                                </div>
                            </div>
                            <div class="guide-calc-example">
                                <div class="guide-calc-example__avatar">
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=trader3" alt="">
                                    <span class="guide-calc-example__badge">Gold</span>
                                </div>
                                <div class="guide-calc-example__info">
                                    <span class="guide-calc-example__name">Moon***Trader</span>
                                    <span class="guide-calc-example__stats">89 추천인 · 2개월차</span>
                                </div>
                                <div class="guide-calc-example__earning">
                                    <span class="guide-calc-example__amount">$1,120</span>
                                    <span class="guide-calc-example__period">/월</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 작동 방식 -->
                <section class="guide-section guide-how" id="how-it-works">
                    <div class="guide-section__header">
                        <span class="guide-section__badge">How It Works</span>
                        <h2 class="guide-section__title">3단계로 시작하는 수익 창출</h2>
                        <p class="guide-section__desc">복잡한 절차 없이 지금 바로 시작하세요.</p>
                    </div>

                    <div class="guide-how__timeline">
                        <div class="guide-how-step guide-how-step--3d" data-step="1">
                            <div class="guide-how-step__number">
                                <span>01</span>
                                <div class="guide-how-step__glow"></div>
                            </div>
                            <div class="guide-how-step__content">
                                <div class="guide-how-step__icon">
                                    <i class="fas fa-share-alt"></i>
                                </div>
                                <h3>링크 공유</h3>
                                <p>나만의 추천 링크를 SNS, 블로그, 유튜브, 카카오톡 등 어디서든 공유하세요.</p>
                                <div class="guide-how-step__tips">
                                    <span><i class="fas fa-check"></i> 트위터/텔레그램 트레이딩 커뮤니티</span>
                                    <span><i class="fas fa-check"></i> 유튜브 영상 설명란</span>
                                    <span><i class="fas fa-check"></i> 블로그 리뷰 글</span>
                                </div>
                            </div>
                        </div>

                        <div class="guide-how-step__connector">
                            <div class="guide-connector-line"></div>
                            <div class="guide-connector-arrow"><i class="fas fa-chevron-right"></i></div>
                        </div>

                        <div class="guide-how-step guide-how-step--3d" data-step="2">
                            <div class="guide-how-step__number">
                                <span>02</span>
                                <div class="guide-how-step__glow"></div>
                            </div>
                            <div class="guide-how-step__content">
                                <div class="guide-how-step__icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <h3>친구 가입</h3>
                                <p>친구가 내 링크로 가입하면 자동으로 내 추천인으로 등록됩니다. KYC 완료 시 추가 보너스!</p>
                                <div class="guide-how-step__bonus">
                                    <div class="guide-bonus-tag">
                                        <i class="fas fa-gift"></i>
                                        가입 보너스 $5
                                    </div>
                                    <div class="guide-bonus-tag">
                                        <i class="fas fa-id-card"></i>
                                        KYC 보너스 $10
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="guide-how-step__connector">
                            <div class="guide-connector-line"></div>
                            <div class="guide-connector-arrow"><i class="fas fa-chevron-right"></i></div>
                        </div>

                        <div class="guide-how-step guide-how-step--3d" data-step="3">
                            <div class="guide-how-step__number">
                                <span>03</span>
                                <div class="guide-how-step__glow"></div>
                            </div>
                            <div class="guide-how-step__content">
                                <div class="guide-how-step__icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <h3>수익 적립</h3>
                                <p>친구가 거래할 때마다 발생하는 수수료의 일부가 실시간으로 내 계정에 적립됩니다.</p>
                                <div class="guide-how-step__flow">
                                    <div class="guide-flow-item">
                                        <span>친구 거래</span>
                                    </div>
                                    <i class="fas fa-arrow-right"></i>
                                    <div class="guide-flow-item">
                                        <span>수수료 발생</span>
                                    </div>
                                    <i class="fas fa-arrow-right"></i>
                                    <div class="guide-flow-item guide-flow-item--highlight">
                                        <span>내 수익!</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 티어 시스템 -->
                <section class="guide-section guide-tiers">
                    <div class="guide-section__header">
                        <span class="guide-section__badge">Partner Tiers</span>
                        <h2 class="guide-section__title">더 많이 추천할수록 더 높은 보상</h2>
                        <p class="guide-section__desc">파트너 등급이 올라가면 커미션율과 추가 혜택이 증가합니다.</p>
                    </div>

                    <div class="guide-tiers__container">
                        <div class="guide-tier-card guide-tier-card--bronze" data-tilt>
                            <div class="guide-tier-card__header">
                                <div class="guide-tier-card__icon">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <h3>Bronze</h3>
                                <span class="guide-tier-card__req">0+ 추천</span>
                            </div>
                            <div class="guide-tier-card__rate">
                                <span class="guide-tier-card__percent">15%</span>
                                <span class="guide-tier-card__label">커미션</span>
                            </div>
                            <ul class="guide-tier-card__benefits">
                                <li><i class="fas fa-check"></i> 기본 커미션 지급</li>
                                <li><i class="fas fa-check"></i> 실시간 수익 추적</li>
                                <li><i class="fas fa-check"></i> 기본 마케팅 도구</li>
                            </ul>
                        </div>

                        <div class="guide-tier-card guide-tier-card--silver" data-tilt>
                            <div class="guide-tier-card__header">
                                <div class="guide-tier-card__icon">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <h3>Silver</h3>
                                <span class="guide-tier-card__req">10+ 추천</span>
                            </div>
                            <div class="guide-tier-card__rate">
                                <span class="guide-tier-card__percent">18%</span>
                                <span class="guide-tier-card__label">커미션</span>
                            </div>
                            <ul class="guide-tier-card__benefits">
                                <li><i class="fas fa-check"></i> 월 $50 추가 보너스</li>
                                <li><i class="fas fa-check"></i> 우선 지원</li>
                                <li><i class="fas fa-check"></i> 고급 통계 대시보드</li>
                            </ul>
                        </div>

                        <div class="guide-tier-card guide-tier-card--gold guide-tier-card--featured" data-tilt>
                            <div class="guide-tier-card__popular">POPULAR</div>
                            <div class="guide-tier-card__header">
                                <div class="guide-tier-card__icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <h3>Gold</h3>
                                <span class="guide-tier-card__req">50+ 추천</span>
                            </div>
                            <div class="guide-tier-card__rate">
                                <span class="guide-tier-card__percent">20%</span>
                                <span class="guide-tier-card__label">커미션</span>
                            </div>
                            <ul class="guide-tier-card__benefits">
                                <li><i class="fas fa-check"></i> 월 $200 추가 보너스</li>
                                <li><i class="fas fa-check"></i> 전용 매니저 배정</li>
                                <li><i class="fas fa-check"></i> 맞춤형 프로모션 코드</li>
                                <li><i class="fas fa-check"></i> 커스텀 배너 제작</li>
                            </ul>
                        </div>

                        <div class="guide-tier-card guide-tier-card--platinum" data-tilt>
                            <div class="guide-tier-card__header">
                                <div class="guide-tier-card__icon">
                                    <i class="fas fa-gem"></i>
                                </div>
                                <h3>Platinum</h3>
                                <span class="guide-tier-card__req">100+ 추천</span>
                            </div>
                            <div class="guide-tier-card__rate">
                                <span class="guide-tier-card__percent">25%</span>
                                <span class="guide-tier-card__label">커미션</span>
                            </div>
                            <ul class="guide-tier-card__benefits">
                                <li><i class="fas fa-check"></i> 월 $500 추가 보너스</li>
                                <li><i class="fas fa-check"></i> 독점 이벤트 초대</li>
                                <li><i class="fas fa-check"></i> 2차 레퍼럴 5%</li>
                                <li><i class="fas fa-check"></i> VIP 전용 API</li>
                            </ul>
                        </div>

                        <div class="guide-tier-card guide-tier-card--diamond" data-tilt>
                            <div class="guide-tier-card__header">
                                <div class="guide-tier-card__icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h3>Diamond</h3>
                                <span class="guide-tier-card__req">500+ 추천</span>
                            </div>
                            <div class="guide-tier-card__rate">
                                <span class="guide-tier-card__percent">30%</span>
                                <span class="guide-tier-card__label">커미션</span>
                            </div>
                            <ul class="guide-tier-card__benefits">
                                <li><i class="fas fa-check"></i> 월 $2,000 추가 보너스</li>
                                <li><i class="fas fa-check"></i> 수익 분배 협상 가능</li>
                                <li><i class="fas fa-check"></i> 공동 마케팅 지원</li>
                                <li><i class="fas fa-check"></i> 연간 파트너 미팅</li>
                                <li><i class="fas fa-check"></i> 최우선 출금 처리</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- 성공 팁 -->
                <section class="guide-section guide-tips">
                    <div class="guide-section__header">
                        <span class="guide-section__badge">Pro Tips</span>
                        <h2 class="guide-section__title">성공적인 추천을 위한 꿀팁</h2>
                        <p class="guide-section__desc">탑 파트너들이 공유하는 실전 노하우</p>
                    </div>

                    <div class="guide-tips__grid">
                        <div class="guide-tip-card guide-tip-card--3d">
                            <div class="guide-tip-card__number">01</div>
                            <div class="guide-tip-card__icon">
                                <i class="fab fa-youtube"></i>
                            </div>
                            <h4>유튜브 콘텐츠 활용</h4>
                            <p>트레이딩 강의, 분석 영상을 만들고 설명란에 추천 링크를 배치하세요. 신뢰도 높은 구독자가 가입합니다.</p>
                            <div class="guide-tip-card__stat">
                                <span class="guide-tip-card__stat-value">+340%</span>
                                <span class="guide-tip-card__stat-label">전환율 향상</span>
                            </div>
                        </div>

                        <div class="guide-tip-card guide-tip-card--3d">
                            <div class="guide-tip-card__number">02</div>
                            <div class="guide-tip-card__icon">
                                <i class="fab fa-telegram"></i>
                            </div>
                            <h4>텔레그램 커뮤니티</h4>
                            <p>트레이딩 시그널이나 정보를 공유하는 채널을 운영하세요. 활발한 트레이더를 모을 수 있습니다.</p>
                            <div class="guide-tip-card__stat">
                                <span class="guide-tip-card__stat-value">+280%</span>
                                <span class="guide-tip-card__stat-label">활성 사용자</span>
                            </div>
                        </div>

                        <div class="guide-tip-card guide-tip-card--3d">
                            <div class="guide-tip-card__number">03</div>
                            <div class="guide-tip-card__icon">
                                <i class="fas fa-blog"></i>
                            </div>
                            <h4>SEO 블로그 운영</h4>
                            <p>"선물거래 추천", "비트코인 거래소" 키워드로 블로그 글을 작성하면 검색 유입이 꾸준히 들어옵니다.</p>
                            <div class="guide-tip-card__stat">
                                <span class="guide-tip-card__stat-value">+520%</span>
                                <span class="guide-tip-card__stat-label">장기 유입</span>
                            </div>
                        </div>

                        <div class="guide-tip-card guide-tip-card--3d">
                            <div class="guide-tip-card__number">04</div>
                            <div class="guide-tip-card__icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4>오프라인 네트워킹</h4>
                            <p>투자 동호회, 트레이딩 모임에서 실제로 만나 추천하세요. 신뢰도가 높아 전환율이 매우 높습니다.</p>
                            <div class="guide-tip-card__stat">
                                <span class="guide-tip-card__stat-value">85%</span>
                                <span class="guide-tip-card__stat-label">가입 전환율</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FAQ -->
                <section class="guide-section guide-faq">
                    <div class="guide-section__header">
                        <span class="guide-section__badge">FAQ</span>
                        <h2 class="guide-section__title">자주 묻는 질문</h2>
                    </div>

                    <div class="guide-faq__list">
                        <div class="guide-faq-item" data-faq>
                            <button class="guide-faq-item__question">
                                <span>추천인 수익은 언제 받을 수 있나요?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="guide-faq-item__answer">
                                <p>추천인이 거래할 때마다 수익이 실시간으로 적립됩니다. 출금은 최소 $10부터 가능하며, 매주 월요일에 정산되어 USDT(TRC20/ERC20)로 출금할 수 있습니다. Diamond 등급은 실시간 출금도 가능합니다.</p>
                            </div>
                        </div>

                        <div class="guide-faq-item" data-faq>
                            <button class="guide-faq-item__question">
                                <span>추천인 수에 제한이 있나요?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="guide-faq-item__answer">
                                <p>전혀 없습니다! 무제한으로 추천할 수 있으며, 추천인이 많아질수록 파트너 등급이 올라가 더 높은 커미션율을 받을 수 있습니다.</p>
                            </div>
                        </div>

                        <div class="guide-faq-item" data-faq>
                            <button class="guide-faq-item__question">
                                <span>추천인이 손실을 보면 제 수익에 영향이 있나요?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="guide-faq-item__answer">
                                <p>없습니다. 커미션은 추천인의 거래 수수료에서 발생하므로, 추천인이 수익을 내든 손실을 보든 관계없이 거래가 발생하면 수익이 적립됩니다.</p>
                            </div>
                        </div>

                        <div class="guide-faq-item" data-faq>
                            <button class="guide-faq-item__question">
                                <span>추천 링크의 유효기간이 있나요?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="guide-faq-item__answer">
                                <p>추천 링크는 영구적으로 유효합니다. 한 번 내 링크로 가입한 사용자는 평생 나의 추천인으로 유지되며, 계속해서 수익을 받을 수 있습니다.</p>
                            </div>
                        </div>

                        <div class="guide-faq-item" data-faq>
                            <button class="guide-faq-item__question">
                                <span>2차 레퍼럴이란 무엇인가요?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="guide-faq-item__answer">
                                <p>Platinum 등급 이상부터 제공되는 혜택으로, 내 추천인이 추천한 사용자(2차 추천인)의 거래에서도 5%의 커미션을 받을 수 있습니다. 네트워크가 확장될수록 수익도 기하급수적으로 늘어납니다.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- CTA -->
                <section class="guide-section guide-cta">
                    <div class="guide-cta__content">
                        <div class="guide-cta__text">
                            <h2>지금 바로 시작하세요!</h2>
                            <p>수익 창출의 첫 걸음, 추천 링크를 공유하세요.</p>
                        </div>
                        <div class="guide-cta__actions">
                            <div class="guide-cta__code-box">
                                <span class="guide-cta__code-label">내 추천 코드</span>
                                <div class="guide-cta__code-value">
                                    <code><?= e($user['referral_code']) ?></code>
                                    <button class="guide-cta__copy-btn" onclick="copyCode('<?= e($user['referral_code']) ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="guide-cta__link-box">
                                <input type="text" readonly value="https://futurx.com/r/<?= e($user['referral_code']) ?>">
                                <button class="btn btn--primary btn--lg" onclick="copyReferralLink()">
                                    <i class="fas fa-link"></i>
                                    링크 복사
                                </button>
                            </div>
                            <div class="guide-cta__share">
                                <span>바로 공유:</span>
                                <div class="guide-cta__share-btns">
                                    <button class="guide-share-btn guide-share-btn--telegram"><i class="fab fa-telegram"></i></button>
                                    <button class="guide-share-btn guide-share-btn--twitter"><i class="fab fa-twitter"></i></button>
                                    <button class="guide-share-btn guide-share-btn--kakao"><i class="fas fa-comment"></i></button>
                                    <button class="guide-share-btn guide-share-btn--facebook"><i class="fab fa-facebook-f"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="guide-cta__decoration">
                        <div class="guide-cta__coin guide-cta__coin--1"><i class="fab fa-bitcoin"></i></div>
                        <div class="guide-cta__coin guide-cta__coin--2"><i class="fab fa-ethereum"></i></div>
                        <div class="guide-cta__coin guide-cta__coin--3"><span>$</span></div>
                    </div>
                </section>
            </div>

            <?php elseif ($currentTab === 'dashboard'): ?>
            <!-- 대시보드 탭 -->
            <div class="referral-section">
                <div class="referral-section__header">
                    <h1>추천인 대시보드</h1>
                    <div class="referral-date-filter">
                        <select id="dateRange">
                            <option value="7">최근 7일</option>
                            <option value="30" selected>최근 30일</option>
                            <option value="90">최근 90일</option>
                            <option value="all">전체</option>
                        </select>
                    </div>
                </div>

                <!-- 주요 지표 카드 -->
                <div class="referral-metrics">
                    <div class="referral-metric-card referral-metric-card--primary">
                        <div class="referral-metric-card__header">
                            <span class="referral-metric-card__title">총 수익</span>
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="referral-metric-card__value">$<?= number_format($stats['total_earnings'], 2) ?></div>
                        <div class="referral-metric-card__footer">
                            <span class="referral-metric-card__change positive">
                                <i class="fas fa-arrow-up"></i> <?= number_format((($stats['this_month_earnings'] - $stats['last_month_earnings']) / $stats['last_month_earnings']) * 100, 1) ?>%
                            </span>
                            <span class="referral-metric-card__period">전월 대비</span>
                        </div>
                    </div>
                    <div class="referral-metric-card">
                        <div class="referral-metric-card__header">
                            <span class="referral-metric-card__title">이번 달 수익</span>
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="referral-metric-card__value">$<?= number_format($stats['this_month_earnings'], 2) ?></div>
                        <div class="referral-metric-card__footer">
                            <span class="referral-metric-card__sub">지난 달: $<?= number_format($stats['last_month_earnings'], 2) ?></span>
                        </div>
                    </div>
                    <div class="referral-metric-card">
                        <div class="referral-metric-card__header">
                            <span class="referral-metric-card__title">총 추천인</span>
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="referral-metric-card__value"><?= number_format($stats['total_referrals']) ?></div>
                        <div class="referral-metric-card__footer">
                            <span class="referral-metric-card__breakdown">
                                <span class="active"><?= $stats['active_referrals'] ?> 활성</span> /
                                <span class="inactive"><?= $stats['inactive_referrals'] ?> 비활성</span> /
                                <span class="pending"><?= $stats['pending_referrals'] ?> 대기</span>
                            </span>
                        </div>
                    </div>
                    <div class="referral-metric-card">
                        <div class="referral-metric-card__header">
                            <span class="referral-metric-card__title">활성화율</span>
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="referral-metric-card__value"><?= number_format($stats['active_rate'], 1) ?>%</div>
                        <div class="referral-metric-card__footer">
                            <div class="referral-metric-card__bar">
                                <div class="referral-metric-card__bar-fill" style="width: <?= $stats['active_rate'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="referral-metric-card">
                        <div class="referral-metric-card__header">
                            <span class="referral-metric-card__title">총 거래량</span>
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="referral-metric-card__value">$<?= number_format($stats['total_volume_generated'], 0) ?></div>
                        <div class="referral-metric-card__footer">
                            <span class="referral-metric-card__sub">평균: $<?= number_format($stats['avg_referral_volume'], 2) ?>/인</span>
                        </div>
                    </div>
                    <div class="referral-metric-card">
                        <div class="referral-metric-card__header">
                            <span class="referral-metric-card__title">커미션 비율</span>
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="referral-metric-card__value"><?= $stats['commission_rate'] ?>%</div>
                        <div class="referral-metric-card__footer">
                            <span class="referral-tier-mini referral-tier-mini--<?= strtolower($user['tier']) ?>"><?= $user['tier'] ?> 등급</span>
                        </div>
                    </div>
                </div>

                <!-- 차트 섹션 -->
                <div class="referral-charts">
                    <div class="referral-chart-box">
                        <div class="referral-chart-box__header">
                            <h3>수익 추이</h3>
                            <div class="referral-chart-box__tabs">
                                <button class="active" data-chart="earnings">수익</button>
                                <button data-chart="volume">거래량</button>
                                <button data-chart="referrals">추천인</button>
                            </div>
                        </div>
                        <div class="referral-chart-box__body">
                            <canvas id="mainChart" height="300"></canvas>
                        </div>
                    </div>

                    <div class="referral-chart-box referral-chart-box--small">
                        <div class="referral-chart-box__header">
                            <h3>추천인 상태</h3>
                        </div>
                        <div class="referral-chart-box__body">
                            <canvas id="statusChart" height="200"></canvas>
                        </div>
                        <div class="referral-chart-legend">
                            <div class="referral-chart-legend__item">
                                <span class="referral-chart-legend__color" style="background: #0ecb81"></span>
                                <span>활성 (<?= $stats['active_referrals'] ?>)</span>
                            </div>
                            <div class="referral-chart-legend__item">
                                <span class="referral-chart-legend__color" style="background: #848e9c"></span>
                                <span>비활성 (<?= $stats['inactive_referrals'] ?>)</span>
                            </div>
                            <div class="referral-chart-legend__item">
                                <span class="referral-chart-legend__color" style="background: #f0b90b"></span>
                                <span>대기 (<?= $stats['pending_referrals'] ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 티어 & 리더보드 -->
                <div class="referral-bottom-section">
                    <!-- 파트너 등급 -->
                    <div class="referral-tier-box">
                        <div class="referral-tier-box__header">
                            <h3>파트너 등급</h3>
                            <span class="referral-tier-badge referral-tier-badge--<?= strtolower($user['tier']) ?>">
                                <i class="fas fa-crown"></i> <?= $user['tier'] ?>
                            </span>
                        </div>
                        <div class="referral-tier-box__progress">
                            <div class="referral-tier-progress">
                                <?php
                                $currentTierIndex = array_search($user['tier'], array_column($tiers, 'name'));
                                $nextTier = $tiers[$currentTierIndex + 1] ?? null;
                                $progress = $nextTier ? min(($stats['total_referrals'] / $nextTier['min_referrals']) * 100, 100) : 100;
                                ?>
                                <div class="referral-tier-progress__info">
                                    <span>현재: <?= $stats['total_referrals'] ?>명</span>
                                    <?php if ($nextTier): ?>
                                    <span>다음 등급: <?= $nextTier['min_referrals'] ?>명 (<?= $nextTier['name'] ?>)</span>
                                    <?php else: ?>
                                    <span>최고 등급 달성!</span>
                                    <?php endif; ?>
                                </div>
                                <div class="referral-tier-progress__bar">
                                    <div class="referral-tier-progress__fill" style="width: <?= $progress ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="referral-tier-box__list">
                            <?php foreach ($tiers as $index => $tier): ?>
                            <div class="referral-tier-row <?= $tier['name'] === $user['tier'] ? 'referral-tier-row--active' : '' ?> <?= $stats['total_referrals'] >= $tier['min_referrals'] ? 'referral-tier-row--unlocked' : '' ?>">
                                <div class="referral-tier-row__status">
                                    <?php if ($stats['total_referrals'] >= $tier['min_referrals']): ?>
                                    <i class="fas fa-check-circle" style="color: #0ecb81"></i>
                                    <?php else: ?>
                                    <i class="fas fa-lock" style="color: #848e9c"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="referral-tier-row__info">
                                    <span class="referral-tier-row__name" style="color: <?= $tier['color'] ?>"><?= $tier['name'] ?></span>
                                    <span class="referral-tier-row__req"><?= $tier['min_referrals'] ?>+ 추천</span>
                                </div>
                                <div class="referral-tier-row__rewards">
                                    <span class="referral-tier-row__commission"><?= $tier['commission'] ?>%</span>
                                    <?php if ($tier['bonus'] > 0): ?>
                                    <span class="referral-tier-row__bonus">+$<?= number_format($tier['bonus']) ?>/월</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 상위 추천인 -->
                    <div class="referral-leaderboard-box">
                        <div class="referral-leaderboard-box__header">
                            <h3>내 Top 추천인</h3>
                            <a href="?tab=referrals" class="referral-link">전체 보기 <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="referral-leaderboard-box__list">
                            <?php foreach ($topReferrers as $referrer): ?>
                            <div class="referral-leaderboard-item">
                                <div class="referral-leaderboard-item__rank <?= $referrer['rank'] <= 3 ? 'top' : '' ?>">
                                    <?php if ($referrer['rank'] === 1): ?>
                                    <i class="fas fa-trophy" style="color: #f0b90b"></i>
                                    <?php elseif ($referrer['rank'] === 2): ?>
                                    <i class="fas fa-medal" style="color: #c0c0c0"></i>
                                    <?php elseif ($referrer['rank'] === 3): ?>
                                    <i class="fas fa-medal" style="color: #cd7f32"></i>
                                    <?php else: ?>
                                    <?= $referrer['rank'] ?>
                                    <?php endif; ?>
                                </div>
                                <div class="referral-leaderboard-item__user">
                                    <div class="referral-leaderboard-item__avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="referral-leaderboard-item__name"><?= $referrer['nickname'] ?></span>
                                </div>
                                <div class="referral-leaderboard-item__stats">
                                    <span class="referral-leaderboard-item__volume">$<?= number_format($referrer['volume'], 0) ?></span>
                                    <span class="referral-leaderboard-item__earnings">+$<?= number_format($referrer['earnings'], 2) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 최근 활동 -->
                <div class="referral-recent-box">
                    <div class="referral-recent-box__header">
                        <h3>최근 수수료 내역</h3>
                        <a href="?tab=commissions" class="referral-link">전체 보기 <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="referral-recent-box__table">
                        <table>
                            <thead>
                                <tr>
                                    <th>일시</th>
                                    <th>추천인</th>
                                    <th>유형</th>
                                    <th>거래량</th>
                                    <th>수수료</th>
                                    <th>내 커미션</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($commissionHistory, 0, 5) as $comm): ?>
                                <tr>
                                    <td><?= date('m/d H:i', strtotime($comm['date'])) ?></td>
                                    <td>
                                        <div class="referral-user-cell">
                                            <div class="referral-user-cell__avatar"><i class="fas fa-user"></i></div>
                                            <span><?= $comm['referral_nickname'] ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="referral-type-badge referral-type-badge--<?= $comm['type'] ?>">
                                            <?php
                                            $typeLabels = [
                                                'trading_fee' => '거래 수수료',
                                                'signup_bonus' => '가입 보너스',
                                                'kyc_bonus' => 'KYC 보너스',
                                                'deposit_bonus' => '입금 보너스'
                                            ];
                                            echo $typeLabels[$comm['type']] ?? $comm['type'];
                                            ?>
                                        </span>
                                    </td>
                                    <td class="mono"><?= $comm['trade_volume'] > 0 ? '$' . number_format($comm['trade_volume'], 2) : '-' ?></td>
                                    <td class="mono"><?= $comm['fee_paid'] > 0 ? '$' . number_format($comm['fee_paid'], 2) : '-' ?></td>
                                    <td class="mono positive">+$<?= number_format($comm['commission'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php elseif ($currentTab === 'referrals'): ?>
            <!-- 내 추천인 탭 -->
            <div class="referral-section">
                <div class="referral-section__header">
                    <h1>내 추천인 목록</h1>
                    <div class="referral-section__actions">
                        <div class="referral-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="닉네임, 이메일 검색..." id="searchReferrals">
                        </div>
                        <select id="filterStatus" class="referral-filter-select">
                            <option value="all">전체 상태</option>
                            <option value="active">활성</option>
                            <option value="inactive">비활성</option>
                            <option value="pending">대기</option>
                        </select>
                        <select id="sortBy" class="referral-filter-select">
                            <option value="earnings">수익순</option>
                            <option value="volume">거래량순</option>
                            <option value="recent">최근 가입순</option>
                            <option value="active">최근 활동순</option>
                        </select>
                        <button class="btn btn--secondary btn--sm">
                            <i class="fas fa-download"></i> 내보내기
                        </button>
                    </div>
                </div>

                <!-- 추천인 통계 요약 -->
                <div class="referral-summary-cards">
                    <div class="referral-summary-card">
                        <div class="referral-summary-card__icon active"><i class="fas fa-user-check"></i></div>
                        <div class="referral-summary-card__info">
                            <span class="referral-summary-card__value"><?= $stats['active_referrals'] ?></span>
                            <span class="referral-summary-card__label">활성 추천인</span>
                        </div>
                    </div>
                    <div class="referral-summary-card">
                        <div class="referral-summary-card__icon inactive"><i class="fas fa-user-clock"></i></div>
                        <div class="referral-summary-card__info">
                            <span class="referral-summary-card__value"><?= $stats['inactive_referrals'] ?></span>
                            <span class="referral-summary-card__label">비활성 추천인</span>
                        </div>
                    </div>
                    <div class="referral-summary-card">
                        <div class="referral-summary-card__icon pending"><i class="fas fa-user-plus"></i></div>
                        <div class="referral-summary-card__info">
                            <span class="referral-summary-card__value"><?= $stats['pending_referrals'] ?></span>
                            <span class="referral-summary-card__label">대기 중</span>
                        </div>
                    </div>
                    <div class="referral-summary-card">
                        <div class="referral-summary-card__icon vip"><i class="fas fa-star"></i></div>
                        <div class="referral-summary-card__info">
                            <span class="referral-summary-card__value"><?= count(array_filter($referralList, fn($r) => $r['is_vip'])) ?></span>
                            <span class="referral-summary-card__label">VIP 추천인</span>
                        </div>
                    </div>
                </div>

                <!-- 추천인 테이블 -->
                <div class="referral-table-box">
                    <table class="referral-table">
                        <thead>
                            <tr>
                                <th>추천인</th>
                                <th>가입일</th>
                                <th>상태</th>
                                <th>KYC</th>
                                <th>총 거래량</th>
                                <th>거래 횟수</th>
                                <th>내 수익</th>
                                <th>최근 거래</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referralList as $ref): ?>
                            <tr data-status="<?= $ref['status'] ?>">
                                <td>
                                    <div class="referral-user-info">
                                        <div class="referral-user-info__avatar">
                                            <i class="fas fa-user"></i>
                                            <?php if ($ref['is_vip']): ?>
                                            <span class="referral-user-info__vip"><i class="fas fa-star"></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="referral-user-info__details">
                                            <span class="referral-user-info__name"><?= e($ref['nickname']) ?></span>
                                            <span class="referral-user-info__email"><?= e($ref['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="referral-date"><?= date('Y-m-d', strtotime($ref['joined_at'])) ?></span>
                                    <span class="referral-time"><?= date('H:i', strtotime($ref['joined_at'])) ?></span>
                                </td>
                                <td>
                                    <span class="referral-status-badge referral-status-badge--<?= $ref['status'] ?>">
                                        <?php
                                        $statusLabels = ['active' => '활성', 'inactive' => '비활성', 'pending' => '대기'];
                                        echo $statusLabels[$ref['status']] ?? $ref['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="referral-kyc-badge referral-kyc-badge--<?= $ref['kyc_level'] ?>">
                                        Lv.<?= $ref['kyc_level'] ?>
                                    </span>
                                </td>
                                <td class="mono">$<?= number_format($ref['total_volume'], 2) ?></td>
                                <td class="mono"><?= number_format($ref['total_trades']) ?></td>
                                <td class="mono positive">+$<?= number_format($ref['my_earnings'], 2) ?></td>
                                <td>
                                    <?php if ($ref['last_trade']): ?>
                                    <span class="referral-last-trade"><?= date('m/d H:i', strtotime($ref['last_trade'])) ?></span>
                                    <?php else: ?>
                                    <span class="referral-no-trade">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="referral-detail-btn" onclick="showReferralDetail(<?= $ref['id'] ?>)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <div class="referral-pagination">
                    <div class="referral-pagination__info">
                        총 <?= $stats['total_referrals'] ?>명 중 1-<?= min(count($referralList), 10) ?> 표시
                    </div>
                    <div class="referral-pagination__controls">
                        <button class="referral-pagination__btn" disabled><i class="fas fa-chevron-left"></i></button>
                        <span class="referral-pagination__page active">1</span>
                        <span class="referral-pagination__page">2</span>
                        <span class="referral-pagination__page">3</span>
                        <span class="referral-pagination__dots">...</span>
                        <span class="referral-pagination__page">16</span>
                        <button class="referral-pagination__btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>

            <?php elseif ($currentTab === 'commissions'): ?>
            <!-- 수수료 내역 탭 -->
            <div class="referral-section">
                <div class="referral-section__header">
                    <h1>수수료 내역</h1>
                    <div class="referral-section__actions">
                        <input type="date" class="referral-date-input" id="startDate">
                        <span class="referral-date-separator">~</span>
                        <input type="date" class="referral-date-input" id="endDate">
                        <select id="filterType" class="referral-filter-select">
                            <option value="all">전체 유형</option>
                            <option value="trading_fee">거래 수수료</option>
                            <option value="signup_bonus">가입 보너스</option>
                            <option value="kyc_bonus">KYC 보너스</option>
                        </select>
                        <button class="btn btn--secondary btn--sm">
                            <i class="fas fa-download"></i> 내보내기
                        </button>
                    </div>
                </div>

                <!-- 수수료 요약 -->
                <div class="referral-commission-summary">
                    <div class="referral-commission-summary__card">
                        <span class="referral-commission-summary__label">이번 달 수수료</span>
                        <span class="referral-commission-summary__value">$<?= number_format($stats['this_month_earnings'], 2) ?></span>
                        <span class="referral-commission-summary__count"><?= count(array_filter($commissionHistory, fn($c) => strtotime($c['date']) >= strtotime('first day of this month'))) ?>건</span>
                    </div>
                    <div class="referral-commission-summary__card">
                        <span class="referral-commission-summary__label">지난 달 수수료</span>
                        <span class="referral-commission-summary__value">$<?= number_format($stats['last_month_earnings'], 2) ?></span>
                    </div>
                    <div class="referral-commission-summary__card">
                        <span class="referral-commission-summary__label">정산 대기</span>
                        <span class="referral-commission-summary__value pending">$<?= number_format($stats['pending_earnings'], 2) ?></span>
                        <span class="referral-commission-summary__note">매주 월요일 정산</span>
                    </div>
                    <div class="referral-commission-summary__card">
                        <span class="referral-commission-summary__label">누적 출금</span>
                        <span class="referral-commission-summary__value">$<?= number_format($stats['withdrawn_earnings'], 2) ?></span>
                    </div>
                </div>

                <!-- 수수료 내역 테이블 -->
                <div class="referral-table-box">
                    <table class="referral-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>일시</th>
                                <th>추천인</th>
                                <th>유형</th>
                                <th>거래량</th>
                                <th>수수료</th>
                                <th>커미션율</th>
                                <th>내 커미션</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissionHistory as $comm): ?>
                            <tr>
                                <td class="mono"><?= $comm['id'] ?></td>
                                <td>
                                    <span class="referral-date"><?= date('Y-m-d', strtotime($comm['date'])) ?></span>
                                    <span class="referral-time"><?= date('H:i:s', strtotime($comm['date'])) ?></span>
                                </td>
                                <td>
                                    <div class="referral-user-cell">
                                        <div class="referral-user-cell__avatar"><i class="fas fa-user"></i></div>
                                        <span><?= $comm['referral_nickname'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="referral-type-badge referral-type-badge--<?= $comm['type'] ?>">
                                        <?php
                                        $typeLabels = [
                                            'trading_fee' => '거래 수수료',
                                            'signup_bonus' => '가입 보너스',
                                            'kyc_bonus' => 'KYC 보너스'
                                        ];
                                        echo $typeLabels[$comm['type']] ?? $comm['type'];
                                        ?>
                                    </span>
                                </td>
                                <td class="mono"><?= $comm['trade_volume'] > 0 ? '$' . number_format($comm['trade_volume'], 2) : '-' ?></td>
                                <td class="mono"><?= $comm['fee_paid'] > 0 ? '$' . number_format($comm['fee_paid'], 2) : '-' ?></td>
                                <td class="mono"><?= $comm['commission_rate'] > 0 ? $comm['commission_rate'] . '%' : '-' ?></td>
                                <td class="mono positive">+$<?= number_format($comm['commission'], 2) ?></td>
                                <td>
                                    <span class="referral-status-badge referral-status-badge--<?= $comm['status'] ?>">
                                        <?= $comm['status'] === 'credited' ? '적립됨' : $comm['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <div class="referral-pagination">
                    <div class="referral-pagination__info">
                        총 <?= count($commissionHistory) ?>건
                    </div>
                    <div class="referral-pagination__controls">
                        <button class="referral-pagination__btn" disabled><i class="fas fa-chevron-left"></i></button>
                        <span class="referral-pagination__page active">1</span>
                        <span class="referral-pagination__page">2</span>
                        <span class="referral-pagination__page">3</span>
                        <button class="referral-pagination__btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>

            <?php elseif ($currentTab === 'withdrawals'): ?>
            <!-- 출금 내역 탭 -->
            <div class="referral-section">
                <div class="referral-section__header">
                    <h1>출금 내역</h1>
                    <button class="btn btn--primary" onclick="openWithdrawModal()">
                        <i class="fas fa-wallet"></i> 출금 신청
                    </button>
                </div>

                <!-- 출금 가능 잔액 카드 -->
                <div class="referral-withdrawal-balance">
                    <div class="referral-withdrawal-balance__main">
                        <div class="referral-withdrawal-balance__info">
                            <span class="referral-withdrawal-balance__label">출금 가능 잔액</span>
                            <span class="referral-withdrawal-balance__value">$<?= number_format($stats['pending_earnings'], 2) ?></span>
                        </div>
                        <button class="btn btn--primary btn--lg" onclick="openWithdrawModal()">
                            <i class="fas fa-wallet"></i> 출금하기
                        </button>
                    </div>
                    <div class="referral-withdrawal-balance__details">
                        <div class="referral-withdrawal-balance__detail">
                            <span class="referral-withdrawal-balance__detail-label">총 수익</span>
                            <span class="referral-withdrawal-balance__detail-value">$<?= number_format($stats['total_earnings'], 2) ?></span>
                        </div>
                        <div class="referral-withdrawal-balance__detail">
                            <span class="referral-withdrawal-balance__detail-label">누적 출금</span>
                            <span class="referral-withdrawal-balance__detail-value">$<?= number_format($stats['withdrawn_earnings'], 2) ?></span>
                        </div>
                        <div class="referral-withdrawal-balance__detail">
                            <span class="referral-withdrawal-balance__detail-label">최소 출금액</span>
                            <span class="referral-withdrawal-balance__detail-value">$10.00</span>
                        </div>
                        <div class="referral-withdrawal-balance__detail">
                            <span class="referral-withdrawal-balance__detail-label">출금 수수료</span>
                            <span class="referral-withdrawal-balance__detail-value">무료</span>
                        </div>
                    </div>
                </div>

                <!-- 출금 내역 테이블 -->
                <div class="referral-table-box">
                    <div class="referral-table-box__header">
                        <h3>출금 내역</h3>
                    </div>
                    <table class="referral-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>신청일</th>
                                <th>금액</th>
                                <th>수수료</th>
                                <th>실 출금액</th>
                                <th>출금 방식</th>
                                <th>주소</th>
                                <th>상태</th>
                                <th>완료일</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawalHistory as $wd): ?>
                            <tr>
                                <td class="mono"><?= $wd['id'] ?></td>
                                <td>
                                    <span class="referral-date"><?= date('Y-m-d', strtotime($wd['date'])) ?></span>
                                    <span class="referral-time"><?= date('H:i', strtotime($wd['date'])) ?></span>
                                </td>
                                <td class="mono">$<?= number_format($wd['amount'], 2) ?></td>
                                <td class="mono">$<?= number_format($wd['fee'], 2) ?></td>
                                <td class="mono positive">$<?= number_format($wd['net_amount'], 2) ?></td>
                                <td><?= $wd['method'] ?></td>
                                <td class="mono"><?= $wd['address'] ?></td>
                                <td>
                                    <span class="referral-status-badge referral-status-badge--<?= $wd['status'] ?>">
                                        <?= $wd['status'] === 'completed' ? '완료' : ($wd['status'] === 'pending' ? '대기' : $wd['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($wd['completed_at']): ?>
                                    <span class="referral-date"><?= date('m/d H:i', strtotime($wd['completed_at'])) ?></span>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($currentTab === 'marketing'): ?>
            <!-- 마케팅 도구 탭 -->
            <div class="referral-section">
                <div class="referral-section__header">
                    <h1>마케팅 도구</h1>
                </div>

                <!-- 추천 코드 & 링크 -->
                <div class="referral-marketing-codes">
                    <div class="referral-marketing-code-card">
                        <h3>내 추천 코드</h3>
                        <div class="referral-marketing-code">
                            <code><?= e($user['referral_code']) ?></code>
                            <button class="btn btn--secondary btn--sm" onclick="copyCode('<?= e($user['referral_code']) ?>')">
                                <i class="fas fa-copy"></i> 복사
                            </button>
                        </div>
                    </div>
                    <div class="referral-marketing-code-card">
                        <h3>추천 링크</h3>
                        <div class="referral-marketing-link">
                            <input type="text" readonly value="https://futurx.com/r/<?= e($user['referral_code']) ?>" id="referralLink">
                            <button class="btn btn--secondary btn--sm" onclick="copyLink()">
                                <i class="fas fa-link"></i> 복사
                            </button>
                        </div>
                    </div>
                </div>

                <!-- QR 코드 -->
                <div class="referral-marketing-qr">
                    <div class="referral-marketing-qr__preview">
                        <div class="referral-marketing-qr__code">
                            <svg viewBox="0 0 100 100" width="200" height="200">
                                <rect width="100" height="100" fill="#fff"/>
                                <g fill="#000">
                                    <rect x="10" y="10" width="30" height="30"/>
                                    <rect x="60" y="10" width="30" height="30"/>
                                    <rect x="10" y="60" width="30" height="30"/>
                                    <rect x="15" y="15" width="20" height="20" fill="#fff"/>
                                    <rect x="65" y="15" width="20" height="20" fill="#fff"/>
                                    <rect x="15" y="65" width="20" height="20" fill="#fff"/>
                                    <rect x="20" y="20" width="10" height="10"/>
                                    <rect x="70" y="20" width="10" height="10"/>
                                    <rect x="20" y="70" width="10" height="10"/>
                                    <rect x="45" y="10" width="5" height="5"/>
                                    <rect x="50" y="15" width="5" height="5"/>
                                    <rect x="45" y="25" width="5" height="5"/>
                                    <rect x="10" y="45" width="5" height="5"/>
                                    <rect x="20" y="50" width="5" height="5"/>
                                    <rect x="45" y="45" width="10" height="10"/>
                                    <rect x="60" y="50" width="5" height="5"/>
                                    <rect x="70" y="45" width="5" height="5"/>
                                    <rect x="50" y="60" width="5" height="5"/>
                                    <rect x="60" y="70" width="10" height="5"/>
                                    <rect x="75" y="65" width="5" height="10"/>
                                    <rect x="80" y="80" width="10" height="10"/>
                                </g>
                            </svg>
                        </div>
                        <div class="referral-marketing-qr__actions">
                            <button class="btn btn--secondary btn--sm"><i class="fas fa-download"></i> PNG 다운로드</button>
                            <button class="btn btn--secondary btn--sm"><i class="fas fa-download"></i> SVG 다운로드</button>
                        </div>
                    </div>
                    <div class="referral-marketing-qr__info">
                        <h3>QR 코드 활용하기</h3>
                        <ul>
                            <li><i class="fas fa-check"></i> 명함이나 홍보물에 인쇄</li>
                            <li><i class="fas fa-check"></i> SNS 프로필에 게시</li>
                            <li><i class="fas fa-check"></i> 영상 콘텐츠에 삽입</li>
                            <li><i class="fas fa-check"></i> 오프라인 행사에서 배포</li>
                        </ul>
                    </div>
                </div>

                <!-- 공유 버튼 -->
                <div class="referral-marketing-share">
                    <h3>SNS 공유</h3>
                    <div class="referral-marketing-share__buttons">
                        <button class="referral-share-btn referral-share-btn--telegram">
                            <i class="fab fa-telegram-plane"></i>
                            <span>텔레그램</span>
                        </button>
                        <button class="referral-share-btn referral-share-btn--twitter">
                            <i class="fab fa-twitter"></i>
                            <span>트위터</span>
                        </button>
                        <button class="referral-share-btn referral-share-btn--facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>페이스북</span>
                        </button>
                        <button class="referral-share-btn referral-share-btn--kakao">
                            <i class="fas fa-comment"></i>
                            <span>카카오톡</span>
                        </button>
                        <button class="referral-share-btn referral-share-btn--line">
                            <i class="fab fa-line"></i>
                            <span>라인</span>
                        </button>
                    </div>
                </div>

                <!-- 배너 다운로드 -->
                <div class="referral-marketing-banners">
                    <h3>홍보 배너</h3>
                    <p class="referral-marketing-banners__desc">블로그, 웹사이트, SNS에 사용할 수 있는 배너 이미지입니다.</p>
                    <div class="referral-marketing-banners__grid">
                        <div class="referral-marketing-banner">
                            <div class="referral-marketing-banner__preview" style="background: linear-gradient(135deg, #f0b90b 0%, #1e2329 100%);">
                                <span>728 x 90</span>
                            </div>
                            <button class="btn btn--secondary btn--sm btn--full"><i class="fas fa-download"></i> 다운로드</button>
                        </div>
                        <div class="referral-marketing-banner">
                            <div class="referral-marketing-banner__preview" style="background: linear-gradient(135deg, #f0b90b 0%, #1e2329 100%); aspect-ratio: 1;">
                                <span>300 x 250</span>
                            </div>
                            <button class="btn btn--secondary btn--sm btn--full"><i class="fas fa-download"></i> 다운로드</button>
                        </div>
                        <div class="referral-marketing-banner">
                            <div class="referral-marketing-banner__preview" style="background: linear-gradient(135deg, #f0b90b 0%, #1e2329 100%); aspect-ratio: 9/16;">
                                <span>1080 x 1920</span>
                            </div>
                            <button class="btn btn--secondary btn--sm btn--full"><i class="fas fa-download"></i> 다운로드</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($currentTab === 'settings'): ?>
            <!-- 설정 탭 -->
            <div class="referral-section">
                <div class="referral-section__header">
                    <h1>추천인 설정</h1>
                </div>

                <div class="referral-settings">
                    <!-- 알림 설정 -->
                    <div class="referral-settings-card">
                        <h3>알림 설정</h3>
                        <div class="referral-settings-list">
                            <div class="referral-settings-item">
                                <div class="referral-settings-item__info">
                                    <span class="referral-settings-item__title">새 추천인 가입 알림</span>
                                    <span class="referral-settings-item__desc">누군가 내 추천 코드로 가입할 때 알림</span>
                                </div>
                                <label class="referral-toggle">
                                    <input type="checkbox" checked>
                                    <span class="referral-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="referral-settings-item">
                                <div class="referral-settings-item__info">
                                    <span class="referral-settings-item__title">수수료 적립 알림</span>
                                    <span class="referral-settings-item__desc">수수료가 적립될 때 알림</span>
                                </div>
                                <label class="referral-toggle">
                                    <input type="checkbox" checked>
                                    <span class="referral-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="referral-settings-item">
                                <div class="referral-settings-item__info">
                                    <span class="referral-settings-item__title">주간 리포트</span>
                                    <span class="referral-settings-item__desc">매주 월요일 추천인 현황 리포트 이메일</span>
                                </div>
                                <label class="referral-toggle">
                                    <input type="checkbox">
                                    <span class="referral-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="referral-settings-item">
                                <div class="referral-settings-item__info">
                                    <span class="referral-settings-item__title">등급 변경 알림</span>
                                    <span class="referral-settings-item__desc">파트너 등급이 변경될 때 알림</span>
                                </div>
                                <label class="referral-toggle">
                                    <input type="checkbox" checked>
                                    <span class="referral-toggle__slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 출금 설정 -->
                    <div class="referral-settings-card">
                        <h3>출금 설정</h3>
                        <div class="referral-settings-form">
                            <div class="referral-form-group">
                                <label>기본 출금 주소 (USDT TRC20)</label>
                                <input type="text" class="referral-input" value="TXyz...abc123" placeholder="USDT TRC20 주소 입력">
                            </div>
                            <div class="referral-form-group">
                                <label>자동 출금</label>
                                <select class="referral-select">
                                    <option value="disabled">비활성화</option>
                                    <option value="weekly">매주 자동 출금</option>
                                    <option value="monthly">매월 자동 출금</option>
                                    <option value="threshold">잔액 $100 초과 시 자동 출금</option>
                                </select>
                            </div>
                            <button class="btn btn--primary">설정 저장</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- 출금 모달 -->
<div class="referral-modal" id="withdrawModal" style="display: none;">
    <div class="referral-modal__backdrop" onclick="closeWithdrawModal()"></div>
    <div class="referral-modal__content">
        <div class="referral-modal__header">
            <h3>출금 신청</h3>
            <button class="referral-modal__close" onclick="closeWithdrawModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="referral-modal__body">
            <div class="referral-withdraw-balance">
                <span class="referral-withdraw-balance__label">출금 가능 잔액</span>
                <span class="referral-withdraw-balance__value">$<?= number_format($stats['pending_earnings'], 2) ?></span>
            </div>

            <div class="referral-form-group">
                <label>출금 금액</label>
                <div class="referral-input-group">
                    <span class="referral-input-group__prefix">$</span>
                    <input type="number" class="referral-input" id="withdrawAmount" placeholder="0.00" min="10" max="<?= $stats['pending_earnings'] ?>" step="0.01">
                    <button class="referral-input-group__suffix" onclick="document.getElementById('withdrawAmount').value='<?= $stats['pending_earnings'] ?>'">전액</button>
                </div>
                <span class="referral-form-hint">최소 출금액: $10.00</span>
            </div>

            <div class="referral-form-group">
                <label>출금 방식</label>
                <select class="referral-select" id="withdrawMethod">
                    <option value="usdt_trc20">USDT (TRC20)</option>
                    <option value="usdt_erc20">USDT (ERC20)</option>
                </select>
            </div>

            <div class="referral-form-group">
                <label>출금 주소</label>
                <input type="text" class="referral-input" id="withdrawAddress" placeholder="지갑 주소 입력">
            </div>

            <div class="referral-withdraw-summary">
                <div class="referral-withdraw-summary__row">
                    <span>출금 금액</span>
                    <span id="summaryAmount">$0.00</span>
                </div>
                <div class="referral-withdraw-summary__row">
                    <span>수수료</span>
                    <span>$0.00</span>
                </div>
                <div class="referral-withdraw-summary__row referral-withdraw-summary__row--total">
                    <span>실 출금액</span>
                    <span id="summaryTotal">$0.00</span>
                </div>
            </div>
        </div>
        <div class="referral-modal__footer">
            <button class="btn btn--secondary" onclick="closeWithdrawModal()">취소</button>
            <button class="btn btn--primary" onclick="submitWithdrawal()">출금 신청</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 메인 차트
    const mainCtx = document.getElementById('mainChart')?.getContext('2d');
    if (mainCtx) {
        const monthlyData = <?= json_encode($monthlyEarnings) ?>;

        const gradient = mainCtx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(240, 185, 11, 0.3)');
        gradient.addColorStop(1, 'rgba(240, 185, 11, 0)');

        new Chart(mainCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month.split('-')[1] + '월'),
                datasets: [{
                    label: '수익',
                    data: monthlyData.map(d => d.earnings),
                    borderColor: '#f0b90b',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#f0b90b',
                    pointBorderColor: '#181a20',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e2329',
                        titleColor: '#eaecef',
                        bodyColor: '#f0b90b',
                        borderColor: '#2b3139',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: ctx => '$' + ctx.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(43, 49, 57, 0.5)', drawBorder: false },
                        ticks: { color: '#848e9c' }
                    },
                    y: {
                        grid: { color: 'rgba(43, 49, 57, 0.5)', drawBorder: false },
                        ticks: { color: '#848e9c', callback: v => '$' + v }
                    }
                }
            }
        });
    }

    // 상태 차트 (도넛)
    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['활성', '비활성', '대기'],
                datasets: [{
                    data: [<?= $stats['active_referrals'] ?>, <?= $stats['inactive_referrals'] ?>, <?= $stats['pending_referrals'] ?>],
                    backgroundColor: ['#0ecb81', '#848e9c', '#f0b90b'],
                    borderColor: '#181a20',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // 출금 금액 입력 시 요약 업데이트
    document.getElementById('withdrawAmount')?.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        document.getElementById('summaryAmount').textContent = '$' + amount.toFixed(2);
        document.getElementById('summaryTotal').textContent = '$' + amount.toFixed(2);
    });
});

// 복사 함수
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        if (typeof Toast !== 'undefined') Toast.success('추천 코드가 복사되었습니다');
    });
}

function copyLink() {
    const link = document.getElementById('referralLink').value;
    navigator.clipboard.writeText(link).then(() => {
        if (typeof Toast !== 'undefined') Toast.success('추천 링크가 복사되었습니다');
    });
}

// 출금 모달
function openWithdrawModal() {
    document.getElementById('withdrawModal').style.display = 'flex';
}

function closeWithdrawModal() {
    document.getElementById('withdrawModal').style.display = 'none';
}

function submitWithdrawal() {
    const amount = parseFloat(document.getElementById('withdrawAmount').value);
    if (amount < 10) {
        if (typeof Toast !== 'undefined') Toast.error('최소 출금 금액은 $10입니다');
        return;
    }
    if (typeof Toast !== 'undefined') Toast.success('출금 신청이 완료되었습니다');
    closeWithdrawModal();
}

function showReferralDetail(id) {
    // 추천인 상세 모달 표시
    console.log('Show detail for referral:', id);
}

// ==========================================
// 가이드 페이지 - 수익 계산기
// ==========================================
const calcReferrals = document.getElementById('calcReferrals');
const calcVolume = document.getElementById('calcVolume');
const calcTier = document.getElementById('calcTier');

if (calcReferrals && calcVolume && calcTier) {
    function updateCalculator() {
        const referrals = parseInt(calcReferrals.value) || 50;
        const volume = parseInt(calcVolume.value) || 10000;
        const tierPercent = parseInt(calcTier.value) || 20;  // 15, 18, 20, 25, 30
        const rate = tierPercent / 100;  // 0.15 ~ 0.30

        // 수수료율 0.1% 가정 (거래량의 0.1%가 수수료)
        const feeRate = 0.001;
        const monthlyFees = referrals * volume * feeRate;
        const monthlyIncome = monthlyFees * rate;
        const yearlyIncome = monthlyIncome * 12;
        const dailyIncome = monthlyIncome / 30;

        // 값 표시 업데이트
        const referralsValueEl = document.getElementById('calcReferralsValue');
        const volumeValueEl = document.getElementById('calcVolumeValue');
        const monthlyEl = document.getElementById('calcMonthly');
        const yearlyEl = document.getElementById('calcYearly');
        const dailyEl = document.getElementById('calcDaily');

        if (referralsValueEl) referralsValueEl.textContent = referrals.toLocaleString() + '명';
        if (volumeValueEl) volumeValueEl.textContent = '$' + volume.toLocaleString();

        if (monthlyEl) monthlyEl.textContent = '$' + Math.round(monthlyIncome).toLocaleString();
        if (yearlyEl) yearlyEl.textContent = '$' + Math.round(yearlyIncome).toLocaleString();
        if (dailyEl) dailyEl.textContent = '$' + dailyIncome.toFixed(2);

        // 슬라이더 트랙 색상 업데이트 (진행률 표시)
        const referralsPercent = ((referrals - 1) / (500 - 1)) * 100;
        const volumePercent = ((volume - 1000) / (100000 - 1000)) * 100;

        calcReferrals.style.background = `linear-gradient(to right, #f0b90b 0%, #f0b90b ${referralsPercent}%, #2b3139 ${referralsPercent}%, #2b3139 100%)`;
        calcVolume.style.background = `linear-gradient(to right, #f0b90b 0%, #f0b90b ${volumePercent}%, #2b3139 ${volumePercent}%, #2b3139 100%)`;
    }

    calcReferrals.addEventListener('input', updateCalculator);
    calcVolume.addEventListener('input', updateCalculator);
    calcTier.addEventListener('change', updateCalculator);

    // 초기 계산
    updateCalculator();
}

// 가이드 페이지 - FAQ 아코디언
document.querySelectorAll('.guide-faq-item__question').forEach(question => {
    question.addEventListener('click', function(e) {
        e.preventDefault();
        const item = this.closest('.guide-faq-item');
        const answer = item.querySelector('.guide-faq-item__answer');
        const icon = this.querySelector('.guide-faq-item__icon');
        const isOpen = item.classList.contains('active');

        // 다른 모든 FAQ 닫기
        document.querySelectorAll('.guide-faq-item').forEach(faq => {
            faq.classList.remove('active');
            const faqAnswer = faq.querySelector('.guide-faq-item__answer');
            if (faqAnswer) {
                faqAnswer.style.maxHeight = '0';
                faqAnswer.style.padding = '0 24px';
                faqAnswer.style.opacity = '0';
            }
        });

        // 클릭한 항목 토글
        if (!isOpen) {
            item.classList.add('active');
            if (answer) {
                answer.style.maxHeight = answer.scrollHeight + 40 + 'px';
                answer.style.padding = '20px 24px';
                answer.style.opacity = '1';
            }
        }
    });
});

// 가이드 페이지 - 추천 링크 복사
document.querySelector('.guide-cta-link__btn')?.addEventListener('click', function() {
    const input = document.querySelector('.guide-cta-link__input');
    if (input) {
        navigator.clipboard.writeText(input.value).then(() => {
            this.innerHTML = '<i class="fas fa-check"></i> 복사됨!';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-copy"></i> 링크 복사';
            }, 2000);
        });
    }
});

// 가이드 페이지 - 코드 복사
document.querySelector('.guide-cta-code__copy')?.addEventListener('click', function() {
    const code = document.querySelector('.guide-cta-code__value')?.textContent;
    if (code) {
        navigator.clipboard.writeText(code).then(() => {
            if (typeof Toast !== 'undefined') Toast.success('추천 코드가 복사되었습니다');
        });
    }
});

// 가이드 페이지 - SNS 공유
document.querySelectorAll('.guide-share-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const platform = this.dataset.platform;
        const referralLink = 'https://futurx.com/r/FTX8K2M9';
        const text = 'FuturX에서 선물거래하고 보상받으세요!';

        let shareUrl = '';
        switch(platform) {
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(referralLink)}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(text)}`;
                break;
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}`;
                break;
            case 'kakao':
                // 카카오톡 공유는 SDK 필요
                alert('카카오톡 공유 기능은 준비 중입니다.');
                return;
        }

        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
