<?php
/**
 * FuturX Admin - 거래관리
 */

require_once 'layout.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$symbol = $_GET['symbol'] ?? '';
$side = $_GET['side'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    $db = getDB();

    $where = ['1=1'];
    $params = [];

    if ($search) {
        $where[] = "(u.username LIKE :search OR u.email LIKE :search OR t.order_id LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($symbol) {
        $where[] = "t.symbol = :symbol";
        $params['symbol'] = $symbol;
    }

    if ($side) {
        $where[] = "t.side = :side";
        $params['side'] = $side;
    }

    if ($dateFrom) {
        $where[] = "DATE(t.created_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }

    if ($dateTo) {
        $where[] = "DATE(t.created_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }

    $whereClause = implode(' AND ', $where);

    // 총 개수
    $stmt = $db->prepare("SELECT COUNT(*) FROM trades t JOIN users u ON t.user_id = u.id WHERE $whereClause");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);

    // 데이터
    $sql = "SELECT t.*, u.username, u.nickname
            FROM trades t
            JOIN users u ON t.user_id = u.id
            WHERE $whereClause
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 심볼 목록
    $symbols = $db->query("SELECT DISTINCT symbol FROM trades ORDER BY symbol")->fetchAll(PDO::FETCH_COLUMN);

    // 통계
    $statsSQL = "SELECT
                    COUNT(*) as total_trades,
                    COALESCE(SUM(quote_quantity), 0) as total_volume,
                    COALESCE(SUM(fee), 0) as total_fees,
                    COALESCE(SUM(realized_pnl), 0) as total_pnl
                 FROM trades t
                 JOIN users u ON t.user_id = u.id
                 WHERE $whereClause";
    $stmt = $db->prepare($statsSQL);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $trades = [];
    $symbols = [];
    $totalCount = 0;
    $totalPages = 0;
    $stats = ['total_trades' => 0, 'total_volume' => 0, 'total_fees' => 0, 'total_pnl' => 0];
}

renderHeader('거래관리');
renderPageHeader('거래관리', '체결된 거래 내역을 관리합니다', [
    ['label' => '내보내기', 'icon' => 'fa-download', 'class' => 'btn--secondary', 'href' => 'export.php?type=trades']
]);
?>

<!-- 통계 -->
<div class="stats-grid">
    <?php renderStatCard('fa-exchange-alt', '총 거래', number_format($stats['total_trades']) . '건'); ?>
    <?php renderStatCard('fa-chart-line', '총 거래량', '$' . number_format($stats['total_volume'], 0)); ?>
    <?php renderStatCard('fa-coins', '총 수수료', '$' . number_format($stats['total_fees'], 2)); ?>
    <?php renderStatCard('fa-dollar-sign', '총 실현손익', '$' . number_format($stats['total_pnl'], 2)); ?>
</div>

<!-- 필터 -->
<div class="card mb-xl">
    <div class="card__body">
        <form method="GET" class="filter-form">
            <div class="filter-form__row">
                <div class="filter-form__group">
                    <label class="form-label">검색</label>
                    <input type="text" name="search" class="form-input" placeholder="회원, 거래ID" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-form__group">
                    <label class="form-label">심볼</label>
                    <select name="symbol" class="form-select">
                        <option value="">전체</option>
                        <?php foreach ($symbols as $s): ?>
                        <option value="<?= $s ?>" <?= $symbol === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-form__group">
                    <label class="form-label">방향</label>
                    <select name="side" class="form-select">
                        <option value="">전체</option>
                        <option value="buy" <?= $side === 'buy' ? 'selected' : '' ?>>매수</option>
                        <option value="sell" <?= $side === 'sell' ? 'selected' : '' ?>>매도</option>
                    </select>
                </div>
                <div class="filter-form__group">
                    <label class="form-label">시작일</label>
                    <input type="date" name="date_from" class="form-input" value="<?= $dateFrom ?>">
                </div>
                <div class="filter-form__group">
                    <label class="form-label">종료일</label>
                    <input type="date" name="date_to" class="form-input" value="<?= $dateTo ?>">
                </div>
                <div class="filter-form__actions">
                    <button type="submit" class="btn btn--primary"><i class="fas fa-search"></i> 검색</button>
                    <a href="trades.php" class="btn btn--secondary">초기화</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 거래 목록 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-list"></i>
            거래 내역
            <span class="text-secondary">(<?= number_format($totalCount) ?>건)</span>
        </h3>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>주문ID</th>
                    <th>회원</th>
                    <th>심볼</th>
                    <th>방향</th>
                    <th>가격</th>
                    <th>수량</th>
                    <th>거래금액</th>
                    <th>수수료</th>
                    <th>실현손익</th>
                    <th>시간</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trades)): ?>
                <tr>
                    <td colspan="10">
                        <div class="empty-state">
                            <i class="fas fa-exchange-alt"></i>
                            <p>거래 내역이 없습니다</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($trades as $trade): ?>
                <tr>
                    <td class="num-mono"><?= htmlspecialchars(substr($trade['order_id'], 0, 12)) ?>...</td>
                    <td><?= htmlspecialchars($trade['nickname'] ?: $trade['username']) ?></td>
                    <td><strong><?= $trade['symbol'] ?></strong></td>
                    <td>
                        <span class="badge badge--<?= $trade['side'] === 'buy' ? 'success' : 'danger' ?>">
                            <?= $trade['side'] === 'buy' ? '매수' : '매도' ?>
                        </span>
                    </td>
                    <td class="num-mono">$<?= number_format($trade['price'], 4) ?></td>
                    <td class="num-mono"><?= number_format($trade['quantity'], 4) ?></td>
                    <td class="num-mono">$<?= number_format($trade['quote_quantity'], 2) ?></td>
                    <td class="num-mono">$<?= number_format($trade['fee'], 4) ?></td>
                    <td class="num-mono <?= $trade['realized_pnl'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                        <?= $trade['realized_pnl'] >= 0 ? '+' : '' ?>$<?= number_format($trade['realized_pnl'], 2) ?>
                    </td>
                    <td><?= date('m/d H:i:s', strtotime($trade['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card__footer">
        <?php
        $baseUrl = "trades.php?search=" . urlencode($search) . "&symbol=" . urlencode($symbol) . "&side=" . urlencode($side) . "&date_from=" . urlencode($dateFrom) . "&date_to=" . urlencode($dateTo);
        renderPagination($page, $totalPages, $baseUrl);
        ?>
    </div>
    <?php endif; ?>
</div>

<style>
.filter-form__row {
    display: flex;
    gap: var(--spacing-lg);
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-form__group {
    flex: 1;
    min-width: 150px;
}

.filter-form__actions {
    display: flex;
    gap: var(--spacing-sm);
}
</style>

<?php renderFooter(); ?>
