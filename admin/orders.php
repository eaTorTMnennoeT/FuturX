<?php
/**
 * FuturX Admin - 주문 관리
 * 실시간 주문 모니터링 및 관리
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'layout.php';

$db = getDB();

// 필터 파라미터
$status = $_GET['status'] ?? 'all';
$side = $_GET['side'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// 주문 목록 쿼리
$where = ["1=1"];
$params = [];

if ($status !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status;
}

if ($side !== 'all') {
    $where[] = "o.side = ?";
    $params[] = $side;
}

if ($type !== 'all') {
    $where[] = "o.type = ?";
    $params[] = $type;
}

if ($search) {
    $where[] = "(u.email LIKE ? OR u.nickname LIKE ? OR o.order_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

// 총 개수
$countSql = "
    SELECT COUNT(*) FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE $whereClause
";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 주문 목록
$sql = "
    SELECT
        o.*,
        u.email,
        u.nickname
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE $whereClause
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 통계
$stats = [];

// 활성 주문
$stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'open', 'partially_filled')");
$stats['active'] = $stmt->fetchColumn();

// 오늘 체결 주문
$stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'filled' AND DATE(updated_at) = CURDATE()");
$stats['filled_today'] = $stmt->fetchColumn();

// 오늘 취소 주문
$stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled' AND DATE(updated_at) = CURDATE()");
$stats['cancelled_today'] = $stmt->fetchColumn();

// 오늘 체결 금액
$stmt = $db->query("SELECT COALESCE(SUM(price * filled_quantity), 0) FROM orders WHERE status = 'filled' AND DATE(updated_at) = CURDATE()");
$stats['volume_today'] = $stmt->fetchColumn();

renderHeader('주문 관리');
renderPageHeader('주문 관리', '실시간 주문을 모니터링하고 관리합니다');
?>

<!-- 통계 카드 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">활성 주문</span>
            <span class="stat-card__value"><?= number_format($stats['active']) ?>건</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-success-bg); color: var(--color-success);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">오늘 체결</span>
            <span class="stat-card__value"><?= number_format($stats['filled_today']) ?>건</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-danger-bg); color: var(--color-danger);">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">오늘 취소</span>
            <span class="stat-card__value"><?= number_format($stats['cancelled_today']) ?>건</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-info-bg); color: var(--color-info);">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">오늘 체결액</span>
            <span class="stat-card__value">$<?= number_format($stats['volume_today'], 0) ?></span>
        </div>
    </div>
</div>

<!-- 필터 -->
<div class="filter-bar">
    <form method="get" class="filter-form">
        <div class="filter-group">
            <label>상태</label>
            <select name="status" class="form-control">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>전체</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>대기중</option>
                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>활성</option>
                <option value="partially_filled" <?= $status === 'partially_filled' ? 'selected' : '' ?>>부분체결</option>
                <option value="filled" <?= $status === 'filled' ? 'selected' : '' ?>>체결완료</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>취소</option>
            </select>
        </div>
        <div class="filter-group">
            <label>방향</label>
            <select name="side" class="form-control">
                <option value="all" <?= $side === 'all' ? 'selected' : '' ?>>전체</option>
                <option value="buy" <?= $side === 'buy' ? 'selected' : '' ?>>매수</option>
                <option value="sell" <?= $side === 'sell' ? 'selected' : '' ?>>매도</option>
            </select>
        </div>
        <div class="filter-group">
            <label>유형</label>
            <select name="type" class="form-control">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>전체</option>
                <option value="market" <?= $type === 'market' ? 'selected' : '' ?>>시장가</option>
                <option value="limit" <?= $type === 'limit' ? 'selected' : '' ?>>지정가</option>
                <option value="stop_market" <?= $type === 'stop_market' ? 'selected' : '' ?>>스톱마켓</option>
                <option value="stop_limit" <?= $type === 'stop_limit' ? 'selected' : '' ?>>스톱리밋</option>
            </select>
        </div>
        <div class="filter-group">
            <label>검색</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="이메일, 닉네임, 주문ID">
        </div>
        <button type="submit" class="btn btn--primary">
            <i class="fas fa-search"></i> 검색
        </button>
        <a href="orders.php" class="btn btn--secondary">
            <i class="fas fa-redo"></i> 초기화
        </a>
    </form>
</div>

<!-- 주문 테이블 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-list-alt"></i>
            주문 목록
            <span class="badge badge--secondary"><?= number_format($totalCount) ?>건</span>
        </h3>
    </div>
    <div class="table-wrapper">
        <table class="table" id="ordersTable">
            <thead>
                <tr>
                    <th>주문ID</th>
                    <th>회원</th>
                    <th>심볼</th>
                    <th>유형</th>
                    <th>방향</th>
                    <th>가격</th>
                    <th>수량</th>
                    <th>체결량</th>
                    <th>상태</th>
                    <th>생성일시</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        <i class="fas fa-inbox"></i> 주문이 없습니다
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <code class="order-id"><?= htmlspecialchars(substr($order['order_id'], 0, 12)) ?>...</code>
                    </td>
                    <td>
                        <div class="user-cell">
                            <span class="user-name"><?= htmlspecialchars($order['nickname'] ?? 'Unknown') ?></span>
                            <span class="user-email"><?= htmlspecialchars($order['email'] ?? '') ?></span>
                        </div>
                    </td>
                    <td><strong><?= htmlspecialchars($order['symbol']) ?></strong></td>
                    <td>
                        <span class="type-badge type-<?= $order['type'] ?>">
                            <?= getOrderTypeName($order['type']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="side-badge <?= $order['side'] ?>">
                            <?= $order['side'] === 'buy' ? 'BUY' : 'SELL' ?>
                        </span>
                    </td>
                    <td class="num-mono">
                        <?php if ($order['type'] === 'market'): ?>
                            <span class="text-muted">시장가</span>
                        <?php else: ?>
                            $<?= number_format($order['price'], 2) ?>
                        <?php endif; ?>
                    </td>
                    <td class="num-mono"><?= number_format($order['quantity'], 4) ?></td>
                    <td class="num-mono">
                        <?= number_format($order['filled_quantity'], 4) ?>
                        <span class="fill-percent">(<?= $order['quantity'] > 0 ? round($order['filled_quantity'] / $order['quantity'] * 100) : 0 ?>%)</span>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= getOrderStatusName($order['status']) ?>
                        </span>
                    </td>
                    <td class="text-muted"><?= date('m/d H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <button class="btn btn--sm btn--outline" onclick="viewOrder('<?= $order['order_id'] ?>')" title="상세보기">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if (in_array($order['status'], ['pending', 'open', 'partially_filled'])): ?>
                        <button class="btn btn--sm btn--danger-outline" onclick="cancelOrder('<?= $order['order_id'] ?>')" title="취소">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card__footer">
        <?php renderPagination($page, $totalPages, "orders.php?status=$status&side=$side&type=$type&search=" . urlencode($search)); ?>
    </div>
    <?php endif; ?>
</div>

<!-- 주문 상세 모달 -->
<div class="modal-overlay" id="orderModal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal__header">
            <h3 class="modal__title">주문 상세</h3>
            <button type="button" class="modal__close" onclick="closeModal('orderModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body" id="orderModalBody">
            <!-- 동적으로 채워짐 -->
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--secondary" onclick="closeModal('orderModal')">닫기</button>
        </div>
    </div>
</div>

<?php
function getOrderTypeName($type) {
    $types = [
        'market' => '시장가',
        'limit' => '지정가',
        'stop_market' => '스톱마켓',
        'stop_limit' => '스톱리밋',
        'take_profit' => 'TP',
        'stop_loss' => 'SL'
    ];
    return $types[$type] ?? $type;
}

function getOrderStatusName($status) {
    $statuses = [
        'pending' => '대기중',
        'open' => '활성',
        'partially_filled' => '부분체결',
        'filled' => '체결완료',
        'cancelled' => '취소됨',
        'expired' => '만료'
    ];
    return $statuses[$status] ?? $status;
}
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
}

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
    gap: 12px;
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

.filter-group .form-control {
    min-width: 120px;
}

.order-id {
    font-size: 11px;
    background: var(--bg-tertiary);
    padding: 2px 6px;
    border-radius: 4px;
}

.user-cell {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
    font-size: 13px;
}

.user-email {
    font-size: 11px;
    color: var(--text-secondary);
}

.side-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.side-badge.buy {
    background: var(--color-success-bg);
    color: var(--color-success);
}

.side-badge.sell {
    background: var(--color-danger-bg);
    color: var(--color-danger);
}

.type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    background: var(--bg-tertiary);
}

.type-badge.type-market { background: var(--color-info-bg); color: var(--color-info); }
.type-badge.type-limit { background: var(--color-primary-bg); color: var(--color-primary); }
.type-badge.type-stop_market,
.type-badge.type-stop_limit { background: var(--color-warning-bg); color: var(--color-warning); }

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.status-badge.status-pending,
.status-badge.status-open { background: var(--color-warning-bg); color: var(--color-warning); }
.status-badge.status-partially_filled { background: var(--color-info-bg); color: var(--color-info); }
.status-badge.status-filled { background: var(--color-success-bg); color: var(--color-success); }
.status-badge.status-cancelled,
.status-badge.status-expired { background: var(--color-danger-bg); color: var(--color-danger); }

.fill-percent {
    font-size: 10px;
    color: var(--text-secondary);
}

.num-mono {
    font-family: var(--font-mono);
    font-size: 13px;
}

.table-wrapper {
    overflow-x: auto;
}

#ordersTable {
    min-width: 1100px;
}

.order-detail {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.order-detail__item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.order-detail__label {
    font-size: 12px;
    color: var(--text-secondary);
}

.order-detail__value {
    font-weight: 500;
}

.order-detail__item.full-width {
    grid-column: 1 / -1;
}
</style>

<script>
// 주문 상세 보기
async function viewOrder(orderId) {
    // 실제로는 API 호출
    document.getElementById('orderModalBody').innerHTML = `
        <div class="order-detail">
            <div class="order-detail__item full-width">
                <div class="order-detail__label">주문 ID</div>
                <div class="order-detail__value"><code>${orderId}</code></div>
            </div>
            <div class="order-detail__item">
                <div class="order-detail__label">상태</div>
                <div class="order-detail__value">조회 중...</div>
            </div>
        </div>
    `;
    openModal('orderModal');
}

// 주문 취소
async function cancelOrder(orderId) {
    if (!confirm('이 주문을 취소하시겠습니까?')) return;

    try {
        const response = await fetch('api/cancel_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        });

        const result = await response.json();

        if (result.success) {
            showToast('success', '주문 취소', '주문이 취소되었습니다.');
            location.reload();
        } else {
            showToast('error', '취소 실패', result.error || '주문 취소에 실패했습니다.');
        }
    } catch (error) {
        showToast('error', '오류', '서버 통신 중 오류가 발생했습니다.');
    }
}
</script>

<?php renderFooter(); ?>
