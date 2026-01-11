<?php
/**
 * FuturX Admin - 통계 관리
 * 월/일별 입출금, 거래량, PnL 등 종합 통계
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'layout.php';

$db = getDB();

// 기간 설정
$period = $_GET['period'] ?? 'daily';
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

// 오늘/이번달/전체 통계
function getOverviewStats($db) {
    $stats = [
        'today_users' => 0,
        'today_trades' => 0,
        'today_volume' => 0,
        'today_fees' => 0,
        'today_deposits' => 0,
        'today_withdrawals' => 0,
        'today_pnl' => 0,
        'total_users' => 0,
        'total_volume' => 0,
        'total_fees' => 0,
        'open_positions' => 0,
        'unrealized_pnl' => 0
    ];

    $today = date('Y-m-d');

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = '$today'");
        $stats['today_users'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM trades WHERE DATE(created_at) = '$today'");
        $stats['today_trades'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(quote_quantity), 0) FROM trades WHERE DATE(created_at) = '$today'");
        $stats['today_volume'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(fee), 0) FROM trades WHERE DATE(created_at) = '$today'");
        $stats['today_fees'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND status = 'completed' AND DATE(created_at) = '$today'");
        $stats['today_deposits'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdraw' AND status = 'completed' AND DATE(created_at) = '$today'");
        $stats['today_withdrawals'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(realized_pnl), 0) FROM trades WHERE DATE(created_at) = '$today'");
        $stats['today_pnl'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $stats['total_users'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(quote_quantity), 0) FROM trades");
        $stats['total_volume'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(fee), 0) FROM trades");
        $stats['total_fees'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM positions WHERE status = 'open'");
        $stats['open_positions'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COALESCE(SUM(unrealized_pnl), 0) FROM positions WHERE status = 'open'");
        $stats['unrealized_pnl'] = $stmt->fetchColumn();
    } catch (Exception $e) {}

    return $stats;
}

// 일별 통계
function getDailyStats($db, $startDate, $endDate) {
    try {
        $sql = "
            SELECT
                DATE(t.created_at) as date,
                COUNT(DISTINCT t.id) as trade_count,
                COALESCE(SUM(t.quote_quantity), 0) as volume,
                COALESCE(SUM(t.fee), 0) as fees,
                COALESCE(SUM(t.realized_pnl), 0) as pnl,
                (SELECT COUNT(*) FROM users u WHERE DATE(u.created_at) = DATE(t.created_at)) as new_users
            FROM trades t
            WHERE DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY DATE(t.created_at)
            ORDER BY date DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// 월별 통계
function getMonthlyStats($db, $year = null) {
    try {
        $year = $year ?? date('Y');
        $sql = "
            SELECT
                DATE_FORMAT(t.created_at, '%Y-%m') as month,
                COUNT(DISTINCT t.id) as trade_count,
                COALESCE(SUM(t.quote_quantity), 0) as volume,
                COALESCE(SUM(t.fee), 0) as fees,
                COALESCE(SUM(t.realized_pnl), 0) as pnl,
                (SELECT COUNT(*) FROM users u WHERE DATE_FORMAT(u.created_at, '%Y-%m') = DATE_FORMAT(t.created_at, '%Y-%m')) as new_users
            FROM trades t
            WHERE YEAR(t.created_at) = ?
            GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
            ORDER BY month DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// 입출금 통계
function getTransactionStats($db, $startDate, $endDate, $groupBy = 'daily') {
    try {
        $dateFormat = $groupBy === 'monthly' ? '%Y-%m' : '%Y-%m-%d';
        $sql = "
            SELECT
                DATE_FORMAT(created_at, '$dateFormat') as period,
                SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END) as deposits,
                SUM(CASE WHEN type = 'withdraw' AND status = 'completed' THEN amount ELSE 0 END) as withdrawals,
                COUNT(CASE WHEN type = 'deposit' THEN 1 END) as deposit_count,
                COUNT(CASE WHEN type = 'withdraw' THEN 1 END) as withdrawal_count
            FROM transactions
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(created_at, '$dateFormat')
            ORDER BY period DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// 사용자별 PnL 순위
function getTopTraders($db, $limit = 10, $type = 'profit') {
    try {
        $order = $type === 'profit' ? 'DESC' : 'ASC';
        $sql = "
            SELECT
                u.id,
                u.nickname,
                u.email,
                COALESCE(SUM(t.realized_pnl), 0) as total_pnl,
                COUNT(t.id) as trade_count,
                COALESCE(SUM(t.quote_quantity), 0) as volume
            FROM users u
            LEFT JOIN trades t ON u.id = t.user_id
            GROUP BY u.id
            HAVING total_pnl != 0
            ORDER BY total_pnl $order
            LIMIT ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// 심볼별 통계
function getSymbolStats($db) {
    try {
        $sql = "
            SELECT
                symbol,
                COUNT(*) as trade_count,
                COALESCE(SUM(quote_quantity), 0) as volume,
                COALESCE(SUM(fee), 0) as fees,
                COALESCE(SUM(realized_pnl), 0) as pnl
            FROM trades
            GROUP BY symbol
            ORDER BY volume DESC
        ";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// 데이터 가져오기
$overview = getOverviewStats($db);
$dailyStats = getDailyStats($db, $startDate, $endDate);
$monthlyStats = getMonthlyStats($db);
$transactionStats = getTransactionStats($db, $startDate, $endDate, $period);
$topProfitTraders = getTopTraders($db, 10, 'profit');
$topLossTraders = getTopTraders($db, 10, 'loss');
$symbolStats = getSymbolStats($db);

// 차트용 데이터
$chartLabels = [];
$chartVolume = [];
$chartPnl = [];

foreach (array_reverse($dailyStats) as $day) {
    $chartLabels[] = date('m/d', strtotime($day['date']));
    $chartVolume[] = round($day['volume'], 2);
    $chartPnl[] = round($day['pnl'], 2);
}

renderHeader('통계 관리');
renderPageHeader('통계 관리', '플랫폼 운영 현황을 분석합니다', [
    ['label' => 'CSV 다운로드', 'icon' => 'fa-download', 'class' => 'btn--secondary', 'onclick' => 'exportCSV()']
]);
?>

<!-- 기간 필터 -->
<div class="filter-bar">
    <form method="get" class="filter-form">
        <div class="filter-group">
            <label>기간</label>
            <select name="period" class="form-control" onchange="this.form.submit()">
                <option value="daily" <?= $period === 'daily' ? 'selected' : '' ?>>일별</option>
                <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>월별</option>
            </select>
        </div>
        <div class="filter-group">
            <label>시작일</label>
            <input type="date" name="start" value="<?= $startDate ?>" class="form-control">
        </div>
        <div class="filter-group">
            <label>종료일</label>
            <input type="date" name="end" value="<?= $endDate ?>" class="form-control">
        </div>
        <button type="submit" class="btn btn--primary">
            <i class="fas fa-search"></i> 조회
        </button>
    </form>
</div>

<!-- 오늘 통계 -->
<div class="section-title">
    <i class="fas fa-calendar-day"></i> 오늘 현황
</div>
<div class="stats-grid stats-grid--6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-card__content">
            <span class="stat-card__label">신규 가입</span>
            <span class="stat-card__value"><?= number_format($overview['today_users']) ?>명</span>
        </div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="stat-card__content">
            <span class="stat-card__label">거래 건수</span>
            <span class="stat-card__value"><?= number_format($overview['today_trades']) ?>건</span>
        </div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i class="fas fa-chart-bar"></i></div>
        <div class="stat-card__content">
            <span class="stat-card__label">거래량</span>
            <span class="stat-card__value">$<?= number_format($overview['today_volume'], 0) ?></span>
        </div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__icon"><i class="fas fa-coins"></i></div>
        <div class="stat-card__content">
            <span class="stat-card__label">수수료 수익</span>
            <span class="stat-card__value">$<?= number_format($overview['today_fees'], 2) ?></span>
        </div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__icon"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-card__content">
            <span class="stat-card__label">입금</span>
            <span class="stat-card__value">$<?= number_format($overview['today_deposits'], 0) ?></span>
        </div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__icon"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-card__content">
            <span class="stat-card__label">출금</span>
            <span class="stat-card__value">$<?= number_format($overview['today_withdrawals'], 0) ?></span>
        </div>
    </div>
</div>

<!-- 전체 통계 -->
<div class="section-title">
    <i class="fas fa-globe"></i> 전체 현황
</div>
<div class="stats-grid stats-grid--4">
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-primary-bg); color: var(--color-primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">총 회원</span>
            <span class="stat-card__value"><?= number_format($overview['total_users']) ?>명</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-success-bg); color: var(--color-success);">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">총 거래량</span>
            <span class="stat-card__value">$<?= number_format($overview['total_volume'], 0) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">총 수수료</span>
            <span class="stat-card__value">$<?= number_format($overview['total_fees'], 2) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-info-bg); color: var(--color-info);">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">활성 포지션</span>
            <span class="stat-card__value"><?= number_format($overview['open_positions']) ?>개</span>
        </div>
    </div>
</div>

<!-- 차트 영역 -->
<div class="grid grid--2">
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="fas fa-chart-area"></i> 거래량 추이</h3>
        </div>
        <div class="card__body">
            <canvas id="volumeChart" height="250"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="fas fa-chart-line"></i> 손익 추이</h3>
        </div>
        <div class="card__body">
            <canvas id="pnlChart" height="250"></canvas>
        </div>
    </div>
</div>

<!-- 일별/월별 통계 테이블 -->
<div class="grid grid--2" style="margin-top: 20px;">
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="fas fa-table"></i> <?= $period === 'monthly' ? '월별' : '일별' ?> 거래 통계</h3>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= $period === 'monthly' ? '월' : '날짜' ?></th>
                        <th>거래 수</th>
                        <th>거래량</th>
                        <th>수수료</th>
                        <th>실현 PnL</th>
                        <th>신규가입</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stats = $period === 'monthly' ? $monthlyStats : $dailyStats;
                    if (empty($stats)):
                    ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">데이터가 없습니다</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($stats as $row): ?>
                    <tr>
                        <td><strong><?= $period === 'monthly' ? $row['month'] : date('Y-m-d', strtotime($row['date'])) ?></strong></td>
                        <td><?= number_format($row['trade_count']) ?></td>
                        <td>$<?= number_format($row['volume'], 0) ?></td>
                        <td>$<?= number_format($row['fees'], 2) ?></td>
                        <td class="<?= $row['pnl'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $row['pnl'] >= 0 ? '+' : '' ?>$<?= number_format($row['pnl'], 2) ?>
                        </td>
                        <td><?= number_format($row['new_users']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="fas fa-wallet"></i> 입출금 통계</h3>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>기간</th>
                        <th>입금액</th>
                        <th>입금건수</th>
                        <th>출금액</th>
                        <th>출금건수</th>
                        <th>순유입</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactionStats)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">데이터가 없습니다</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transactionStats as $row):
                        $netFlow = $row['deposits'] - $row['withdrawals'];
                    ?>
                    <tr>
                        <td><strong><?= $row['period'] ?></strong></td>
                        <td class="text-success">$<?= number_format($row['deposits'], 0) ?></td>
                        <td><?= number_format($row['deposit_count']) ?></td>
                        <td class="text-danger">$<?= number_format($row['withdrawals'], 0) ?></td>
                        <td><?= number_format($row['withdrawal_count']) ?></td>
                        <td class="<?= $netFlow >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $netFlow >= 0 ? '+' : '' ?>$<?= number_format($netFlow, 0) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 순위 테이블 -->
<div class="grid grid--3" style="margin-top: 20px;">
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="fas fa-trophy text-warning"></i> 수익 TOP 10</h3>
        </div>
        <div class="table-wrapper">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>사용자</th>
                        <th>수익</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topProfitTraders)): ?>
                    <tr><td colspan="3" class="text-center text-muted">데이터 없음</td></tr>
                    <?php else: ?>
                    <?php foreach ($topProfitTraders as $i => $trader): ?>
                    <tr>
                        <td><span class="rank-badge rank-<?= $i + 1 ?>"><?= $i + 1 ?></span></td>
                        <td>
                            <div class="user-cell-sm">
                                <span class="user-name"><?= htmlspecialchars($trader['nickname'] ?? 'User') ?></span>
                            </div>
                        </td>
                        <td class="text-success">+$<?= number_format($trader['total_pnl'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="fas fa-chart-line text-danger"></i> 손실 TOP 10</h3>
        </div>
        <div class="table-wrapper">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>사용자</th>
                        <th>손실</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topLossTraders)): ?>
                    <tr><td colspan="3" class="text-center text-muted">데이터 없음</td></tr>
                    <?php else: ?>
                    <?php foreach ($topLossTraders as $i => $trader): ?>
                    <tr>
                        <td><span class="rank-badge"><?= $i + 1 ?></span></td>
                        <td>
                            <div class="user-cell-sm">
                                <span class="user-name"><?= htmlspecialchars($trader['nickname'] ?? 'User') ?></span>
                            </div>
                        </td>
                        <td class="text-danger">$<?= number_format($trader['total_pnl'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="fas fa-coins"></i> 심볼별 거래량</h3>
        </div>
        <div class="table-wrapper">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th>심볼</th>
                        <th>거래수</th>
                        <th>거래량</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($symbolStats)): ?>
                    <tr><td colspan="3" class="text-center text-muted">데이터 없음</td></tr>
                    <?php else: ?>
                    <?php foreach ($symbolStats as $symbol): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($symbol['symbol']) ?></strong></td>
                        <td><?= number_format($symbol['trade_count']) ?></td>
                        <td>$<?= number_format($symbol['volume'], 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.filter-bar {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 16px;
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-secondary);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    margin: 24px 0 12px;
    color: var(--text-primary);
}

.section-title i {
    color: var(--color-primary);
}

.stats-grid {
    display: grid;
    gap: 16px;
    margin-bottom: 20px;
}

.stats-grid--4 {
    grid-template-columns: repeat(4, 1fr);
}

.stats-grid--6 {
    grid-template-columns: repeat(6, 1fr);
}

@media (max-width: 1400px) {
    .stats-grid--6 { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 1200px) {
    .stats-grid--4 { grid-template-columns: repeat(2, 1fr); }
    .stats-grid--6 { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .stats-grid--4,
    .stats-grid--6 { grid-template-columns: 1fr; }
}

.stat-card--primary { border-left: 3px solid var(--color-primary); }
.stat-card--success { border-left: 3px solid var(--color-success); }
.stat-card--warning { border-left: 3px solid var(--color-warning); }
.stat-card--danger { border-left: 3px solid var(--color-danger); }
.stat-card--info { border-left: 3px solid var(--color-info); }

.grid--2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.grid--3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 1200px) {
    .grid--3 { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 992px) {
    .grid--2,
    .grid--3 { grid-template-columns: 1fr; }
}

.table--compact td,
.table--compact th {
    padding: 8px 12px;
    font-size: 13px;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bg-tertiary);
    font-size: 12px;
    font-weight: 600;
}

.rank-badge.rank-1 { background: #ffd700; color: #000; }
.rank-badge.rank-2 { background: #c0c0c0; color: #000; }
.rank-badge.rank-3 { background: #cd7f32; color: #fff; }

.user-cell-sm {
    display: flex;
    flex-direction: column;
}

.user-cell-sm .user-name {
    font-weight: 500;
    font-size: 13px;
}

.text-success { color: var(--color-success) !important; }
.text-danger { color: var(--color-danger) !important; }
.text-warning { color: var(--color-warning) !important; }
.text-muted { color: var(--text-secondary) !important; }
</style>

<script>
// 차트 데이터
const chartLabels = <?= json_encode($chartLabels) ?>;
const chartVolume = <?= json_encode($chartVolume) ?>;
const chartPnl = <?= json_encode($chartPnl) ?>;

// 거래량 차트
const volumeCtx = document.getElementById('volumeChart').getContext('2d');
new Chart(volumeCtx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [{
            label: '거래량 ($)',
            data: chartVolume,
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: 'rgb(99, 102, 241)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// PnL 차트
const pnlCtx = document.getElementById('pnlChart').getContext('2d');
new Chart(pnlCtx, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: '실현 PnL ($)',
            data: chartPnl,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// CSV 내보내기
function exportCSV() {
    const period = '<?= $period ?>';
    const start = '<?= $startDate ?>';
    const end = '<?= $endDate ?>';

    window.location.href = `api/export_stats.php?period=${period}&start=${start}&end=${end}`;
}
</script>

<?php renderFooter(); ?>
