<?php
/**
 * FuturX - 리더보드 페이지
 * 트레이더 순위, 실시간 랭킹, 기간별 통계
 */

require_once 'config.php';

// 기간 파라미터
$period = $_GET['period'] ?? 'daily';
$validPeriods = ['daily', 'weekly', 'monthly', 'all'];
if (!in_array($period, $validPeriods)) {
    $period = 'daily';
}

// 기간별 키 계산
function getPeriodKey($period) {
    switch ($period) {
        case 'daily':
            return date('Y-m-d');
        case 'weekly':
            return date('Y-W');
        case 'monthly':
            return date('Y-m');
        case 'all':
            return 'all';
    }
}

$periodKey = getPeriodKey($period);

try {
    $db = getDB();

    // 리더보드 데이터 조회
    $sql = "SELECT
                ls.*,
                u.username,
                u.nickname,
                u.avatar,
                u.vip_level
            FROM leaderboard_snapshots ls
            JOIN users u ON ls.user_id = u.id
            WHERE ls.period = :period
            AND ls.period_key = :period_key
            ORDER BY ls.rank ASC
            LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute(['period' => $period, 'period_key' => $periodKey]);
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 만약 스냅샷이 없으면 실시간 계산 (trades 테이블에서)
    if (empty($leaderboard)) {
        $dateCondition = '';
        switch ($period) {
            case 'daily':
                $dateCondition = "AND t.created_at >= CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
                break;
            case 'monthly':
                $dateCondition = "AND t.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                break;
            case 'all':
                $dateCondition = "";
                break;
        }

        $sql = "SELECT
                    u.id as user_id,
                    u.username,
                    u.nickname,
                    u.avatar,
                    u.vip_level,
                    COALESCE(SUM(t.realized_pnl), 0) as pnl,
                    COALESCE(SUM(t.quote_quantity), 0) as volume,
                    COUNT(t.id) as trade_count,
                    SUM(CASE WHEN t.realized_pnl > 0 THEN 1 ELSE 0 END) as win_count,
                    COUNT(t.id) as total_trades
                FROM users u
                LEFT JOIN trades t ON u.id = t.user_id {$dateCondition}
                WHERE u.status = 'active'
                GROUP BY u.id
                HAVING trade_count > 0
                ORDER BY pnl DESC
                LIMIT 100";

        $stmt = $db->query($sql);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ROI 및 승률 계산
        foreach ($leaderboard as $key => $row) {
            $leaderboard[$key]['rank'] = $key + 1;
            $leaderboard[$key]['roi'] = 0; // 초기 잔액 대비 ROI (별도 로직 필요)
            $leaderboard[$key]['win_rate'] = $row['total_trades'] > 0
                ? round(($row['win_count'] / $row['total_trades']) * 100, 2)
                : 0;
        }
    }

    // 전체 통계
    $statsSQL = "SELECT
                    COUNT(DISTINCT user_id) as total_traders,
                    SUM(volume) as total_volume,
                    SUM(trade_count) as total_trades,
                    AVG(roi) as avg_roi
                 FROM leaderboard_snapshots
                 WHERE period = :period AND period_key = :period_key";
    $stmt = $db->prepare($statsSQL);
    $stmt->execute(['period' => $period, 'period_key' => $periodKey]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 기본값 설정
    if (!$stats['total_traders']) {
        $stats = [
            'total_traders' => count($leaderboard),
            'total_volume' => array_sum(array_column($leaderboard, 'volume')),
            'total_trades' => array_sum(array_column($leaderboard, 'trade_count')),
            'avg_roi' => count($leaderboard) > 0 ? array_sum(array_column($leaderboard, 'roi')) / count($leaderboard) : 0
        ];
    }

} catch (PDOException $e) {
    // 개발 환경에서만 에러 표시
    $leaderboard = [];
    $stats = ['total_traders' => 0, 'total_volume' => 0, 'total_trades' => 0, 'avg_roi' => 0];
}

// 기간 라벨
$periodLabels = [
    'daily' => '일간',
    'weekly' => '주간',
    'monthly' => '월간',
    'all' => '전체'
];
?>
<?php
$pageTitle = '리더보드';
$pageCSS = ['css/leaderboard.css'];
require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 컨텐츠 -->
    <main class="main">
        <!-- 히어로 섹션 -->
        <section class="leaderboard-hero">
            <div class="leaderboard-hero__bg"></div>
            <div class="container">
                <div class="leaderboard-hero__content">
                    <h1 class="leaderboard-hero__title">
                        <i class="fas fa-trophy"></i>
                        트레이더 리더보드
                    </h1>
                    <p class="leaderboard-hero__subtitle">
                        최고의 트레이더들과 경쟁하고 순위에 도전하세요
                    </p>
                </div>

                <!-- 통계 카드 -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card__icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-card__content">
                            <span class="stat-card__value"><?= number_format($stats['total_traders']) ?></span>
                            <span class="stat-card__label">활성 트레이더</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-card__content">
                            <span class="stat-card__value">$<?= number_format($stats['total_volume'], 0) ?></span>
                            <span class="stat-card__label">총 거래량</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-card__content">
                            <span class="stat-card__value"><?= number_format($stats['total_trades']) ?></span>
                            <span class="stat-card__label">총 거래 수</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__icon">
                            <i class="fas fa-percent"></i>
                        </div>
                        <div class="stat-card__content">
                            <span class="stat-card__value <?= $stats['avg_roi'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                <?= $stats['avg_roi'] >= 0 ? '+' : '' ?><?= number_format($stats['avg_roi'], 2) ?>%
                            </span>
                            <span class="stat-card__label">평균 ROI</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 리더보드 메인 -->
        <section class="leaderboard-main">
            <div class="container">
                <!-- 필터 및 기간 선택 -->
                <div class="leaderboard-controls">
                    <div class="period-tabs">
                        <?php foreach ($periodLabels as $key => $label): ?>
                        <a href="?period=<?= $key ?>" class="period-tab <?= $period === $key ? 'active' : '' ?>">
                            <?= $label ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="leaderboard-filters">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="트레이더 검색..." id="searchInput">
                        </div>
                        <select class="filter-select" id="sortBy">
                            <option value="pnl">수익 (PnL)</option>
                            <option value="roi">수익률 (ROI)</option>
                            <option value="volume">거래량</option>
                            <option value="win_rate">승률</option>
                        </select>
                    </div>
                </div>

                <!-- Top 3 포디움 -->
                <?php if (count($leaderboard) >= 3): ?>
                <div class="podium">
                    <!-- 2등 -->
                    <div class="podium__item podium__item--silver">
                        <div class="podium__rank">2</div>
                        <div class="podium__avatar">
                            <?php if ($leaderboard[1]['avatar']): ?>
                                <img src="<?= htmlspecialchars($leaderboard[1]['avatar']) ?>" alt="">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="podium__name">
                            <?= htmlspecialchars($leaderboard[1]['nickname'] ?: $leaderboard[1]['username']) ?>
                            <?php if ($leaderboard[1]['vip_level'] > 0): ?>
                                <span class="vip-badge">VIP <?= $leaderboard[1]['vip_level'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="podium__pnl <?= $leaderboard[1]['pnl'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                            <?= $leaderboard[1]['pnl'] >= 0 ? '+' : '' ?>$<?= number_format($leaderboard[1]['pnl'], 2) ?>
                        </div>
                        <div class="podium__roi <?= $leaderboard[1]['roi'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                            <?= $leaderboard[1]['roi'] >= 0 ? '+' : '' ?><?= number_format($leaderboard[1]['roi'], 2) ?>%
                        </div>
                        <div class="podium__stand podium__stand--silver"></div>
                    </div>

                    <!-- 1등 -->
                    <div class="podium__item podium__item--gold">
                        <div class="podium__crown">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="podium__rank">1</div>
                        <div class="podium__avatar podium__avatar--gold">
                            <?php if ($leaderboard[0]['avatar']): ?>
                                <img src="<?= htmlspecialchars($leaderboard[0]['avatar']) ?>" alt="">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="podium__name">
                            <?= htmlspecialchars($leaderboard[0]['nickname'] ?: $leaderboard[0]['username']) ?>
                            <?php if ($leaderboard[0]['vip_level'] > 0): ?>
                                <span class="vip-badge">VIP <?= $leaderboard[0]['vip_level'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="podium__pnl <?= $leaderboard[0]['pnl'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                            <?= $leaderboard[0]['pnl'] >= 0 ? '+' : '' ?>$<?= number_format($leaderboard[0]['pnl'], 2) ?>
                        </div>
                        <div class="podium__roi <?= $leaderboard[0]['roi'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                            <?= $leaderboard[0]['roi'] >= 0 ? '+' : '' ?><?= number_format($leaderboard[0]['roi'], 2) ?>%
                        </div>
                        <div class="podium__stand podium__stand--gold"></div>
                    </div>

                    <!-- 3등 -->
                    <div class="podium__item podium__item--bronze">
                        <div class="podium__rank">3</div>
                        <div class="podium__avatar">
                            <?php if ($leaderboard[2]['avatar']): ?>
                                <img src="<?= htmlspecialchars($leaderboard[2]['avatar']) ?>" alt="">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="podium__name">
                            <?= htmlspecialchars($leaderboard[2]['nickname'] ?: $leaderboard[2]['username']) ?>
                            <?php if ($leaderboard[2]['vip_level'] > 0): ?>
                                <span class="vip-badge">VIP <?= $leaderboard[2]['vip_level'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="podium__pnl <?= $leaderboard[2]['pnl'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                            <?= $leaderboard[2]['pnl'] >= 0 ? '+' : '' ?>$<?= number_format($leaderboard[2]['pnl'], 2) ?>
                        </div>
                        <div class="podium__roi <?= $leaderboard[2]['roi'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                            <?= $leaderboard[2]['roi'] >= 0 ? '+' : '' ?><?= number_format($leaderboard[2]['roi'], 2) ?>%
                        </div>
                        <div class="podium__stand podium__stand--bronze"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 순위 테이블 -->
                <div class="leaderboard-table-wrapper">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th class="col-rank">순위</th>
                                <th class="col-trader">트레이더</th>
                                <th class="col-pnl">수익 (PnL)</th>
                                <th class="col-roi">수익률 (ROI)</th>
                                <th class="col-volume">거래량</th>
                                <th class="col-trades">거래 수</th>
                                <th class="col-winrate">승률</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardBody">
                            <?php if (empty($leaderboard)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-chart-bar"></i>
                                    <p>아직 거래 데이터가 없습니다</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($leaderboard as $index => $trader): ?>
                            <tr class="trader-row" data-username="<?= htmlspecialchars(strtolower($trader['nickname'] ?: $trader['username'])) ?>">
                                <td class="col-rank">
                                    <?php if ($index < 3): ?>
                                        <span class="rank-badge rank-badge--<?= ['gold', 'silver', 'bronze'][$index] ?>">
                                            <?= $index + 1 ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="rank-number"><?= $index + 1 ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-trader">
                                    <div class="trader-info">
                                        <div class="trader-avatar">
                                            <?php if ($trader['avatar']): ?>
                                                <img src="<?= htmlspecialchars($trader['avatar']) ?>" alt="">
                                            <?php else: ?>
                                                <i class="fas fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="trader-details">
                                            <span class="trader-name">
                                                <?= htmlspecialchars($trader['nickname'] ?: $trader['username']) ?>
                                            </span>
                                            <?php if ($trader['vip_level'] > 0): ?>
                                                <span class="vip-badge vip-badge--sm">VIP <?= $trader['vip_level'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-pnl">
                                    <span class="<?= $trader['pnl'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                        <?= $trader['pnl'] >= 0 ? '+' : '' ?>$<?= number_format($trader['pnl'], 2) ?>
                                    </span>
                                </td>
                                <td class="col-roi">
                                    <span class="<?= $trader['roi'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                        <?= $trader['roi'] >= 0 ? '+' : '' ?><?= number_format($trader['roi'], 2) ?>%
                                    </span>
                                </td>
                                <td class="col-volume">
                                    $<?= number_format($trader['volume'], 0) ?>
                                </td>
                                <td class="col-trades">
                                    <?= number_format($trader['trade_count']) ?>
                                </td>
                                <td class="col-winrate">
                                    <div class="winrate-bar">
                                        <div class="winrate-fill" style="width: <?= min($trader['win_rate'], 100) ?>%"></div>
                                        <span class="winrate-text"><?= number_format($trader['win_rate'], 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 내 순위 (로그인 시) -->
                <div class="my-rank-card" id="myRankCard" style="display: none;">
                    <div class="my-rank-card__header">
                        <i class="fas fa-user-circle"></i>
                        <span>내 순위</span>
                    </div>
                    <div class="my-rank-card__content">
                        <div class="my-rank-card__rank">
                            <span class="rank-number">-</span>
                            <span class="rank-label">순위</span>
                        </div>
                        <div class="my-rank-card__stats">
                            <div class="my-stat">
                                <span class="my-stat__label">수익</span>
                                <span class="my-stat__value">$0.00</span>
                            </div>
                            <div class="my-stat">
                                <span class="my-stat__label">ROI</span>
                                <span class="my-stat__value">0.00%</span>
                            </div>
                            <div class="my-stat">
                                <span class="my-stat__label">승률</span>
                                <span class="my-stat__value">0.0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- 푸터 -->
    <footer class="footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__logo">
                    <div class="header__logo-icon">F</div>
                    <span>FuturX</span>
                </div>
                <div class="footer__links">
                    <a href="#">이용약관</a>
                    <a href="#">개인정보처리방침</a>
                    <a href="#">고객센터</a>
                </div>
                <div class="footer__copyright">
                    © 2024 FuturX. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <script>
    // 검색 기능
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.trader-row');

        rows.forEach(row => {
            const username = row.dataset.username;
            if (username.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // 정렬 기능
    document.getElementById('sortBy').addEventListener('change', function(e) {
        const sortBy = e.target.value;
        window.location.href = `?period=<?= $period ?>&sort=${sortBy}`;
    });

    // 실시간 업데이트 (30초마다)
    setInterval(function() {
        fetch(`api/leaderboard.php?period=<?= $period ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 순위 업데이트 로직
                    updateLeaderboard(data.leaderboard);
                }
            })
            .catch(err => console.log('Update failed'));
    }, 30000);

    function updateLeaderboard(data) {
        // 실시간 업데이트 구현
        console.log('Leaderboard updated:', data);
    }
    </script>
</body>
</html>
