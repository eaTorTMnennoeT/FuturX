<?php
/**
 * 지갑 Overview 페이지
 * Bybit 스타일 자산 총괄
 */

require_once __DIR__ . '/includes/bootstrap.php';

$csrf_token = generateCSRFToken();

// 데모 자산 데이터
$totalEquity = 15234.56;
$totalEquityBTC = 0.16892345;
$todayPnL = 123.45;
$todayPnLPercent = 0.82;

$accounts = [
    'funding' => [
        'name' => 'Funding',
        'icon' => 'funding',
        'balance' => 5234.56,
        'balanceBTC' => 0.05803456,
    ],
    'trading' => [
        'name' => 'Unified Trading',
        'icon' => 'trading',
        'balance' => 10000.00,
        'balanceBTC' => 0.11088889,
    ],
];

// 최근 입출금 내역
$recentHistory = [
    ['type' => 'withdraw', 'coin' => 'USDT', 'amount' => -3408.1121, 'time' => '2024-01-11 03:20:11', 'status' => 'completed'],
    ['type' => 'withdraw', 'coin' => 'USDT', 'amount' => -36319.5924, 'time' => '2024-01-08 08:39:10', 'status' => 'completed'],
    ['type' => 'deposit', 'coin' => 'BTC', 'amount' => 0.5, 'time' => '2024-01-07 14:22:05', 'status' => 'completed'],
    ['type' => 'deposit', 'coin' => 'ETH', 'amount' => 2.5, 'time' => '2024-01-05 09:15:30', 'status' => 'completed'],
];

$pageTitle = '자산 Overview';
$pageCSS = ['wallet.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 콘텐츠 -->
    <main class="main">
        <div class="wallet-container">
            <!-- 사이드바 -->
            <aside class="wallet-sidebar">
                <button class="wallet-sidebar__toggle" id="sidebarToggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <nav class="wallet-nav">
                    <a href="wallet.php" class="wallet-nav__item active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                        <span>Overview</span>
                    </a>
                    <a href="wallet-funding.php" class="wallet-nav__item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                        <span>Funding</span>
                    </a>
                    <a href="wallet-trading.php" class="wallet-nav__item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <span>Unified Trading</span>
                    </a>
                    <div class="wallet-nav__divider"></div>
                    <span class="wallet-nav__label">Analysis</span>
                    <a href="wallet-pnl.php" class="wallet-nav__item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                        <span>P&L Analysis</span>
                    </a>
                    <a href="orders.php" class="wallet-nav__item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        <span>Orders</span>
                    </a>
                </nav>
            </aside>

            <!-- 콘텐츠 영역 -->
            <div class="wallet-content">
                <!-- 페이지 헤더 -->
                <div class="wallet-header">
                    <div class="wallet-header__title">
                        <h1>Assets Overview</h1>
                        <button class="wallet-header__eye" id="toggleBalance">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    <div class="wallet-header__actions">
                        <a href="deposit.php" class="btn btn--primary">Deposit</a>
                        <a href="withdraw.php" class="btn btn--outline">Withdraw</a>
                        <a href="transfer.php" class="btn btn--outline">Transfer</a>
                        <a href="convert.php" class="btn btn--outline">Convert</a>
                    </div>
                </div>

                <!-- 총 자산 -->
                <div class="wallet-total">
                    <div class="wallet-total__main">
                        <span class="wallet-total__value" id="totalEquity">
                            <?php echo number_format($totalEquity, 2); ?>
                        </span>
                        <span class="wallet-total__currency">USD</span>
                    </div>
                    <div class="wallet-total__btc">
                        ≈ <span id="totalEquityBTC"><?php echo number_format($totalEquityBTC, 8); ?></span> BTC
                    </div>
                    <div class="wallet-total__pnl <?php echo $todayPnL >= 0 ? 'positive' : 'negative'; ?>">
                        Today's P&L
                        <span><?php echo ($todayPnL >= 0 ? '+' : '') . number_format($todayPnL, 2); ?></span>
                        <span>(<?php echo ($todayPnLPercent >= 0 ? '+' : '') . number_format($todayPnLPercent, 2); ?>%)</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </div>

                <!-- 메인 그리드 -->
                <div class="wallet-grid">
                    <!-- 계정 섹션 -->
                    <div class="wallet-accounts">
                        <div class="wallet-accounts__tabs">
                            <button class="wallet-accounts__tab active">Account</button>
                            <button class="wallet-accounts__tab">Asset</button>
                        </div>

                        <div class="wallet-accounts__label">My Assets</div>

                        <div class="wallet-accounts__list">
                            <!-- Funding Account -->
                            <div class="wallet-account">
                                <div class="wallet-account__icon wallet-account__icon--funding">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                </div>
                                <div class="wallet-account__info">
                                    <span class="wallet-account__name">Funding</span>
                                </div>
                                <div class="wallet-account__balance">
                                    <span class="wallet-account__value"><?php echo number_format($accounts['funding']['balance'], 2); ?> USD</span>
                                    <span class="wallet-account__btc">≈ <?php echo number_format($accounts['funding']['balanceBTC'], 8); ?> BTC</span>
                                </div>
                                <div class="wallet-account__actions">
                                    <button class="wallet-account__btn" title="입금">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                    </button>
                                    <button class="wallet-account__btn" title="출금">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="7 10 12 15 17 10"/>
                                            <line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                    </button>
                                    <button class="wallet-account__btn" title="전송">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="17 1 21 5 17 9"/>
                                            <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                                            <polyline points="7 23 3 19 7 15"/>
                                            <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                                        </svg>
                                    </button>
                                    <button class="wallet-account__btn" title="설정">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Unified Trading Account -->
                            <div class="wallet-account">
                                <div class="wallet-account__icon wallet-account__icon--trading">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                        <line x1="8" y1="21" x2="16" y2="21"/>
                                        <line x1="12" y1="17" x2="12" y2="21"/>
                                    </svg>
                                </div>
                                <div class="wallet-account__info">
                                    <span class="wallet-account__name">Unified Trading</span>
                                </div>
                                <div class="wallet-account__balance">
                                    <span class="wallet-account__value"><?php echo number_format($accounts['trading']['balance'], 2); ?> USD</span>
                                    <span class="wallet-account__btc">≈ <?php echo number_format($accounts['trading']['balanceBTC'], 8); ?> BTC</span>
                                </div>
                                <div class="wallet-account__actions">
                                    <button class="wallet-account__btn" title="입금">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                    </button>
                                    <button class="wallet-account__btn" title="전송">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="17 1 21 5 17 9"/>
                                            <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                                            <polyline points="7 23 3 19 7 15"/>
                                            <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                                        </svg>
                                    </button>
                                    <button class="wallet-account__btn" title="설정">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 차트 및 최근 내역 -->
                    <div class="wallet-right">
                        <!-- 자산 차트 -->
                        <div class="wallet-chart">
                            <div class="wallet-chart__header">
                                <div class="wallet-chart__tabs">
                                    <button class="wallet-chart__tab active">7D</button>
                                    <button class="wallet-chart__tab">30D</button>
                                    <button class="wallet-chart__tab">90D</button>
                                    <button class="wallet-chart__tab">180D</button>
                                </div>
                            </div>
                            <div class="wallet-chart__body">
                                <canvas id="assetChart"></canvas>
                            </div>
                            <div class="wallet-chart__footer">
                                Last Updated: <?php echo date('Y-m-d H:i'); ?> (UTC)
                            </div>
                        </div>

                        <!-- 최근 입출금 내역 -->
                        <div class="wallet-history">
                            <div class="wallet-history__header">
                                <h3>Recent Deposit & Withdrawal History</h3>
                                <a href="history.php" class="wallet-history__link">All <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
                            </div>
                            <div class="wallet-history__list">
                                <?php foreach ($recentHistory as $item): ?>
                                <div class="wallet-history__item">
                                    <div class="wallet-history__info">
                                        <span class="wallet-history__type"><?php echo ucfirst($item['type']); ?></span>
                                        <span class="wallet-history__time"><?php echo $item['time']; ?></span>
                                    </div>
                                    <div class="wallet-history__amount <?php echo $item['amount'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <span><?php echo ($item['amount'] >= 0 ? '+' : '') . number_format($item['amount'], 4); ?> <?php echo $item['coin']; ?></span>
                                        <span class="wallet-history__status wallet-history__status--<?php echo $item['status']; ?>">
                                            <?php echo $item['status'] === 'completed' ? '• Successfully Transferred' : '• Pending'; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 잔액 숨기기 토글
        const toggleBtn = document.getElementById('toggleBalance');
        const balanceElements = document.querySelectorAll('.wallet-total__value, .wallet-total__btc, .wallet-account__value, .wallet-account__btc, .wallet-history__amount span');
        let isHidden = false;

        toggleBtn.addEventListener('click', function() {
            isHidden = !isHidden;
            const iconEye = this.querySelector('.icon-eye');
            const iconEyeOff = this.querySelector('.icon-eye-off');

            if (isHidden) {
                iconEye.style.display = 'none';
                iconEyeOff.style.display = 'block';
                balanceElements.forEach(el => {
                    el.dataset.originalText = el.textContent;
                    el.textContent = '******';
                });
            } else {
                iconEye.style.display = 'block';
                iconEyeOff.style.display = 'none';
                balanceElements.forEach(el => {
                    if (el.dataset.originalText) {
                        el.textContent = el.dataset.originalText;
                    }
                });
            }
        });

        // Account/Asset 탭
        document.querySelectorAll('.wallet-accounts__tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.wallet-accounts__tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // 차트 기간 탭
        document.querySelectorAll('.wallet-chart__tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.wallet-chart__tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                // 차트 데이터 업데이트
                updateChart(this.textContent);
            });
        });

        // 간단한 차트 (Canvas)
        const canvas = document.getElementById('assetChart');
        const ctx = canvas.getContext('2d');

        function resizeCanvas() {
            const rect = canvas.parentElement.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = 200;
            drawChart();
        }

        function drawChart() {
            const width = canvas.width;
            const height = canvas.height;
            const padding = 20;

            // 데모 데이터
            const data = [14500, 14800, 15100, 14900, 15300, 15100, 15234];
            const labels = ['01-04', '01-05', '01-06', '01-07', '01-08', '01-09', '01-10'];

            const max = Math.max(...data);
            const min = Math.min(...data);
            const range = max - min || 1;

            ctx.clearRect(0, 0, width, height);

            // 그라데이션 영역
            const gradient = ctx.createLinearGradient(0, 0, 0, height);
            gradient.addColorStop(0, 'rgba(240, 185, 11, 0.3)');
            gradient.addColorStop(1, 'rgba(240, 185, 11, 0)');

            ctx.beginPath();
            ctx.moveTo(padding, height - padding);

            const stepX = (width - padding * 2) / (data.length - 1);

            data.forEach((value, index) => {
                const x = padding + index * stepX;
                const y = height - padding - ((value - min) / range) * (height - padding * 2);
                ctx.lineTo(x, y);
            });

            ctx.lineTo(width - padding, height - padding);
            ctx.closePath();
            ctx.fillStyle = gradient;
            ctx.fill();

            // 라인
            ctx.beginPath();
            data.forEach((value, index) => {
                const x = padding + index * stepX;
                const y = height - padding - ((value - min) / range) * (height - padding * 2);
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.strokeStyle = '#f0b90b';
            ctx.lineWidth = 2;
            ctx.stroke();

            // X축 라벨
            ctx.fillStyle = '#848e9c';
            ctx.font = '11px Inter';
            ctx.textAlign = 'center';
            labels.forEach((label, index) => {
                const x = padding + index * stepX;
                ctx.fillText(label, x, height - 5);
            });
        }

        function updateChart(period) {
            // 실제 구현에서는 API 호출
            drawChart();
        }

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // 사이드바 토글 (모바일)
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.wallet-sidebar');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }
    });
    </script>
</body>
</html>
