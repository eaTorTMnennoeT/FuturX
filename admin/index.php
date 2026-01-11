<?php
/**
 * FuturX Admin - 대시보드
 * 통계, 차트, 실시간 데이터
 */

require_once 'layout.php';

try {
    $db = getDB();

    // 오늘 통계
    $todayStats = [
        'users' => 0,
        'trades' => 0,
        'volume' => 0,
        'revenue' => 0
    ];

    // 신규 가입자 수 (오늘)
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
    $todayStats['users'] = $stmt->fetchColumn();

    // 오늘 거래 수
    $stmt = $db->query("SELECT COUNT(*) FROM trades WHERE DATE(created_at) = CURDATE()");
    $todayStats['trades'] = $stmt->fetchColumn();

    // 오늘 거래량
    $stmt = $db->query("SELECT COALESCE(SUM(quote_quantity), 0) FROM trades WHERE DATE(created_at) = CURDATE()");
    $todayStats['volume'] = $stmt->fetchColumn();

    // 오늘 수수료 수익
    $stmt = $db->query("SELECT COALESCE(SUM(fee), 0) FROM trades WHERE DATE(created_at) = CURDATE()");
    $todayStats['revenue'] = $stmt->fetchColumn();

    // 전체 통계
    $totalStats = [
        'users' => 0,
        'trades' => 0,
        'volume' => 0,
        'positions' => 0
    ];

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $totalStats['users'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM trades");
    $totalStats['trades'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COALESCE(SUM(quote_quantity), 0) FROM trades");
    $totalStats['volume'] = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM positions WHERE status = 'open'");
    $totalStats['positions'] = $stmt->fetchColumn();

    // 최근 7일 거래량 차트 데이터
    $chartLabels = [];
    $chartVolumes = [];
    $chartTrades = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('m/d', strtotime($date));

        $stmt = $db->prepare("SELECT COALESCE(SUM(quote_quantity), 0) FROM trades WHERE DATE(created_at) = :date");
        $stmt->execute(['date' => $date]);
        $chartVolumes[] = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM trades WHERE DATE(created_at) = :date");
        $stmt->execute(['date' => $date]);
        $chartTrades[] = (int)$stmt->fetchColumn();
    }

    // 최근 거래 내역
    $recentTrades = $db->query("
        SELECT t.*, u.username, u.nickname
        FROM trades t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 최근 주문
    $recentOrders = $db->query("
        SELECT o.*, u.username, u.nickname
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 최근 가입자
    $recentUsers = $db->query("
        SELECT *
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 심볼별 거래량
    $symbolStats = $db->query("
        SELECT symbol, SUM(quote_quantity) as volume, COUNT(*) as trade_count
        FROM trades
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY symbol
        ORDER BY volume DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 입출금 대기 건수
    $pendingDeposits = $db->query("SELECT COUNT(*) FROM transactions WHERE type = 'deposit' AND status = 'pending'")->fetchColumn();
    $pendingWithdraws = $db->query("SELECT COUNT(*) FROM transactions WHERE type = 'withdraw' AND status = 'pending'")->fetchColumn();

} catch (PDOException $e) {
    $todayStats = ['users' => 0, 'trades' => 0, 'volume' => 0, 'revenue' => 0];
    $totalStats = ['users' => 0, 'trades' => 0, 'volume' => 0, 'positions' => 0];
    $chartLabels = [];
    $chartVolumes = [];
    $chartTrades = [];
    $recentTrades = [];
    $recentOrders = [];
    $recentUsers = [];
    $symbolStats = [];
    $pendingDeposits = 0;
    $pendingWithdraws = 0;
}

renderHeader('대시보드');
renderPageHeader('대시보드', '플랫폼 현황을 한눈에 확인하세요');
?>

<!-- 오늘 통계 카드 -->
<div class="stats-grid">
    <?php renderStatCard('fa-user-plus', '오늘 가입', number_format($todayStats['users']) . '명'); ?>
    <?php renderStatCard('fa-exchange-alt', '오늘 거래', number_format($todayStats['trades']) . '건'); ?>
    <?php renderStatCard('fa-chart-line', '오늘 거래량', '$' . number_format($todayStats['volume'], 0)); ?>
    <?php renderStatCard('fa-dollar-sign', '오늘 수수료', '$' . number_format($todayStats['revenue'], 2)); ?>
</div>

<!-- 알림 카드 (대기중인 항목) -->
<?php if ($pendingDeposits > 0 || $pendingWithdraws > 0): ?>
<div class="alert-cards">
    <?php if ($pendingDeposits > 0): ?>
    <a href="transactions.php?type=deposit&status=pending" class="alert-card alert-card--warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>입금 대기 <?= $pendingDeposits ?>건</span>
    </a>
    <?php endif; ?>
    <?php if ($pendingWithdraws > 0): ?>
    <a href="transactions.php?type=withdraw&status=pending" class="alert-card alert-card--danger">
        <i class="fas fa-exclamation-circle"></i>
        <span>출금 대기 <?= $pendingWithdraws ?>건</span>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- 차트 영역 -->
<div class="grid-2 mb-xl">
    <!-- 거래량 차트 -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-chart-area"></i>
                최근 7일 거래량
            </h3>
        </div>
        <div class="card__body">
            <div class="chart-container">
                <canvas id="volumeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 거래 수 차트 -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-chart-bar"></i>
                최근 7일 거래 수
            </h3>
        </div>
        <div class="card__body">
            <div class="chart-container">
                <canvas id="tradesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 하단 그리드 -->
<div class="grid-3">
    <!-- 최근 거래 -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-exchange-alt"></i>
                최근 거래
            </h3>
            <a href="trades.php" class="btn btn--sm btn--outline">전체보기</a>
        </div>
        <div class="card__body" style="padding: 0;">
            <?php if (empty($recentTrades)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>거래 내역이 없습니다</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>회원</th>
                        <th>심볼</th>
                        <th>금액</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTrades as $trade): ?>
                    <tr>
                        <td><?= htmlspecialchars($trade['nickname'] ?: $trade['username']) ?></td>
                        <td>
                            <span class="badge badge--<?= $trade['side'] === 'buy' ? 'success' : 'danger' ?>">
                                <?= strtoupper($trade['side']) ?>
                            </span>
                            <?= $trade['symbol'] ?>
                        </td>
                        <td class="num-mono">$<?= number_format($trade['quote_quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- 인기 심볼 -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-fire"></i>
                24시간 인기 심볼
            </h3>
        </div>
        <div class="card__body" style="padding: 0;">
            <?php if (empty($symbolStats)): ?>
            <div class="empty-state">
                <i class="fas fa-chart-pie"></i>
                <p>데이터가 없습니다</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>심볼</th>
                        <th>거래량</th>
                        <th>거래 수</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($symbolStats as $symbol): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($symbol['symbol']) ?></strong></td>
                        <td class="num-mono">$<?= number_format($symbol['volume'], 0) ?></td>
                        <td><?= number_format($symbol['trade_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- 최근 가입자 -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-user-plus"></i>
                최근 가입자
            </h3>
            <a href="users.php" class="btn btn--sm btn--outline">전체보기</a>
        </div>
        <div class="card__body" style="padding: 0;">
            <?php if (empty($recentUsers)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>가입자가 없습니다</p>
            </div>
            <?php else: ?>
            <div class="user-list">
                <?php foreach ($recentUsers as $user): ?>
                <div class="user-list__item">
                    <div class="user-list__avatar">
                        <?php if ($user['avatar']): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-list__info">
                        <span class="user-list__name"><?= htmlspecialchars($user['nickname'] ?: $user['username']) ?></span>
                        <span class="user-list__email"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <span class="user-list__date"><?= date('m/d H:i', strtotime($user['created_at'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 전체 통계 요약 -->
<div class="summary-card mt-xl">
    <div class="summary-card__title">
        <i class="fas fa-chart-pie"></i>
        전체 통계 요약
    </div>
    <div class="summary-card__grid">
        <div class="summary-item">
            <span class="summary-item__label">총 회원 수</span>
            <span class="summary-item__value"><?= number_format($totalStats['users']) ?>명</span>
        </div>
        <div class="summary-item">
            <span class="summary-item__label">총 거래 건수</span>
            <span class="summary-item__value"><?= number_format($totalStats['trades']) ?>건</span>
        </div>
        <div class="summary-item">
            <span class="summary-item__label">총 거래량</span>
            <span class="summary-item__value">$<?= number_format($totalStats['volume'], 0) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-item__label">활성 포지션</span>
            <span class="summary-item__value"><?= number_format($totalStats['positions']) ?>개</span>
        </div>
    </div>
</div>

<style>
/* 대시보드 추가 스타일 */
.alert-cards {
    display: flex;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
}

.alert-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--radius-md);
    font-weight: 500;
}

.alert-card--warning {
    background: var(--color-warning-bg);
    color: var(--color-warning);
    border: 1px solid var(--color-warning);
}

.alert-card--danger {
    background: var(--color-danger-bg);
    color: var(--color-danger);
    border: 1px solid var(--color-danger);
}

.user-list {
    padding: 0;
}

.user-list__item {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md) var(--spacing-lg);
    border-bottom: 1px solid var(--border-color);
}

.user-list__item:last-child {
    border-bottom: none;
}

.user-list__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bg-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-tertiary);
    overflow: hidden;
}

.user-list__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-list__info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.user-list__name {
    font-weight: 500;
}

.user-list__email {
    font-size: 12px;
    color: var(--text-secondary);
}

.user-list__date {
    font-size: 12px;
    color: var(--text-tertiary);
}

.summary-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
}

.summary-card__title {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-size: 16px;
    font-weight: 600;
    margin-bottom: var(--spacing-lg);
}

.summary-card__title i {
    color: var(--color-primary);
}

.summary-card__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-lg);
}

.summary-item {
    text-align: center;
    padding: var(--spacing-lg);
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
}

.summary-item__label {
    display: block;
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xs);
}

.summary-item__value {
    font-size: 20px;
    font-weight: 700;
    font-family: var(--font-mono);
    color: var(--color-primary);
}

@media (max-width: 992px) {
    .grid-3 {
        grid-template-columns: 1fr;
    }

    .summary-card__grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .alert-cards {
        flex-direction: column;
    }

    .summary-card__grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// 차트 데이터
const chartLabels = <?= json_encode($chartLabels) ?>;
const volumeData = <?= json_encode($chartVolumes) ?>;
const tradesData = <?= json_encode($chartTrades) ?>;

// 거래량 차트
createLineChart('volumeChart', chartLabels, [
    { label: '거래량 (USD)', data: volumeData, color: '#f0b90b' }
]);

// 거래 수 차트
createBarChart('tradesChart', chartLabels, [
    { label: '거래 수', data: tradesData, color: '#0ecb81' }
]);

// 자동 새로고침 (5분)
setInterval(() => {
    location.reload();
}, 5 * 60 * 1000);
</script>

<?php renderFooter(); ?>
