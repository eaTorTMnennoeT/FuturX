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
    $where[] = "o.order_type = ?";
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
            <span class="badge badge--secondary" id="totalCount"><?= number_format($totalCount) ?>건</span>
            <span class="live-badge" id="liveBadge">LIVE</span>
        </h3>
        <div class="card__actions">
            <button class="btn btn--sm btn--outline" onclick="refreshOrders()" title="새로고침">
                <i class="fas fa-sync-alt" id="refreshIcon"></i>
            </button>
            <label class="toggle-switch">
                <input type="checkbox" id="autoRefreshToggle" checked>
                <span class="toggle-slider"></span>
                <span class="toggle-label">자동 갱신</span>
            </label>
        </div>
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
                        <span class="type-badge type-<?= $order['order_type'] ?>">
                            <?= getOrderTypeName($order['order_type']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="side-badge <?= $order['side'] ?>">
                            <?= $order['side'] === 'buy' ? 'BUY' : 'SELL' ?>
                        </span>
                    </td>
                    <td class="num-mono">
                        <?php if ($order['order_type'] === 'market'): ?>
                            <span class="text-muted">시장가</span>
                        <?php else: ?>
                            $<?= number_format($order['price'], 2) ?>
                        <?php endif; ?>
                    </td>
                    <td class="num-mono"><?= number_format($order['quantity'], 4) ?></td>
                    <td class="num-mono">
                        <?= number_format($order['filled_quantity'], 4) ?>
                        <span class="fill-percent">(<?= $order['quantity'] > 0 ? min(100, round($order['filled_quantity'] / $order['quantity'] * 100)) : 0 ?>%)</span>
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
    white-space: nowrap;
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
    white-space: nowrap;
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
    white-space: nowrap;
}

.num-mono {
    font-family: 'Roboto Mono', 'SF Mono', 'Monaco', 'Consolas', monospace;
    font-size: 13px;
    white-space: nowrap;
}

.table-wrapper {
    overflow-x: auto;
}

#ordersTable {
    min-width: 1100px;
}

#ordersTable td:last-child {
    white-space: nowrap;
}

#ordersTable td:last-child .btn {
    display: inline-flex;
    vertical-align: middle;
}

/* 실시간 관련 스타일 */
.live-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    background: var(--color-success);
    color: #fff;
    animation: pulse 2s infinite;
    margin-left: 8px;
}

.live-badge.disconnected {
    background: var(--color-danger);
    animation: none;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.card__actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.toggle-switch input {
    display: none;
}

.toggle-slider {
    width: 36px;
    height: 20px;
    background: var(--bg-tertiary);
    border-radius: 10px;
    position: relative;
    transition: background 0.3s;
}

.toggle-slider::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
    background: var(--color-success);
}

.toggle-switch input:checked + .toggle-slider::after {
    transform: translateX(16px);
}

.toggle-label {
    font-size: 12px;
    color: var(--text-secondary);
}

.refresh-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* 새 주문 하이라이트 */
.order-row-new {
    animation: newOrderHighlight 2s ease-out;
}

@keyframes newOrderHighlight {
    0% { background-color: rgba(74, 222, 128, 0.3); }
    100% { background-color: transparent; }
}

/* 취소된 주문 하이라이트 */
.order-row-cancelled {
    animation: cancelledHighlight 2s ease-out;
}

@keyframes cancelledHighlight {
    0% { background-color: rgba(248, 113, 113, 0.3); }
    100% { background-color: transparent; }
}

/* 상태 변경 하이라이트 */
.order-row-updated {
    animation: updatedHighlight 1s ease-out;
}

@keyframes updatedHighlight {
    0% { background-color: rgba(251, 191, 36, 0.3); }
    100% { background-color: transparent; }
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

<script src="<?= WS_SERVER_URL ?>/socket.io/socket.io.js"></script>
<script>
// ========================================
// 실시간 주문 관리 - Socket.IO
// ========================================
const WS_SERVER = window.FuturXConfig?.wsServerUrl || 'http://localhost:3002';
let socket = null;
let isAutoRefresh = true;
let refreshInterval = null;
let ordersCache = new Map();

// 현재 필터 상태
const currentFilters = {
    status: '<?= htmlspecialchars($status) ?>',
    side: '<?= htmlspecialchars($side) ?>',
    type: '<?= htmlspecialchars($type) ?>',
    search: '<?= htmlspecialchars($search) ?>'
};

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    initSocket();
    initAutoRefresh();

    // 자동 갱신 토글
    document.getElementById('autoRefreshToggle').addEventListener('change', (e) => {
        isAutoRefresh = e.target.checked;
        if (isAutoRefresh) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
});

// Socket.IO 연결
function initSocket() {
    socket = io(WS_SERVER);

    socket.on('connect', () => {
        console.log('[Socket] Connected');
        updateLiveBadge(true);

        // 관리자 식별
        socket.emit('admin:identify', { page: 'orders' });
    });

    socket.on('disconnect', () => {
        console.log('[Socket] Disconnected');
        updateLiveBadge(false);
    });

    // 새 주문 이벤트
    socket.on('order:placed', (data) => {
        console.log('[Order] New order placed:', data);
        if (matchesFilter(data)) {
            addNewOrderToTable(data);
            updateStats();
            showToast('info', '새 주문', `${data.nickname || 'User'} - ${data.side?.toUpperCase()} ${data.symbol}`);
        }
    });

    // 주문 체결 이벤트
    socket.on('order:filled', (data) => {
        console.log('[Order] Order filled:', data);
        updateOrderInTable(data.order_id, 'filled', data);
        updateStats();
    });

    // 주문 취소 이벤트
    socket.on('order:cancelled', (data) => {
        console.log('[Order] Order cancelled:', data);
        updateOrderInTable(data.order_id, 'cancelled', data);
        updateStats();
    });
}

// LIVE 배지 업데이트
function updateLiveBadge(connected) {
    const badge = document.getElementById('liveBadge');
    if (connected) {
        badge.textContent = 'LIVE';
        badge.classList.remove('disconnected');
    } else {
        badge.textContent = 'OFFLINE';
        badge.classList.add('disconnected');
    }
}

// 필터 매칭 확인
function matchesFilter(order) {
    const orderType = order.order_type || order.type;
    if (currentFilters.status !== 'all' && order.status !== currentFilters.status) return false;
    if (currentFilters.side !== 'all' && order.side !== currentFilters.side) return false;
    if (currentFilters.type !== 'all' && orderType !== currentFilters.type) return false;
    if (currentFilters.search) {
        const search = currentFilters.search.toLowerCase();
        const matchEmail = order.email?.toLowerCase().includes(search);
        const matchNickname = order.nickname?.toLowerCase().includes(search);
        const matchOrderId = order.order_id?.toLowerCase().includes(search);
        if (!matchEmail && !matchNickname && !matchOrderId) return false;
    }
    return true;
}

// 새 주문 테이블에 추가
function addNewOrderToTable(order) {
    const tbody = document.querySelector('#ordersTable tbody');
    const emptyRow = tbody.querySelector('.text-center');
    if (emptyRow) {
        emptyRow.closest('tr').remove();
    }

    const tr = document.createElement('tr');
    tr.id = `order-${order.order_id}`;
    tr.className = 'order-row-new';
    tr.innerHTML = createOrderRowHTML(order);

    tbody.insertBefore(tr, tbody.firstChild);

    // 총 개수 업데이트
    const countEl = document.getElementById('totalCount');
    const currentCount = parseInt(countEl.textContent.replace(/[^0-9]/g, '')) || 0;
    countEl.textContent = (currentCount + 1).toLocaleString() + '건';
}

// 주문 상태 업데이트
function updateOrderInTable(orderId, newStatus, data) {
    const row = document.querySelector(`tr[id*="${orderId.substring(0,12)}"]`) ||
                document.querySelector(`tr:has(code:contains("${orderId.substring(0,12)}"))`);

    if (row) {
        // 상태 배지 업데이트
        const statusBadge = row.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.className = `status-badge status-${newStatus}`;
            statusBadge.textContent = getStatusName(newStatus);
        }

        // 체결량 업데이트
        if (data.filled_quantity !== undefined) {
            const cells = row.querySelectorAll('td');
            if (cells[7]) {
                const percent = data.quantity > 0 ? Math.min(100, Math.round(data.filled_quantity / data.quantity * 100)) : 0;
                cells[7].innerHTML = `${parseFloat(data.filled_quantity).toFixed(4)}<span class="fill-percent">(${percent}%)</span>`;
            }
        }

        // 하이라이트 효과
        row.classList.remove('order-row-new', 'order-row-updated', 'order-row-cancelled');
        row.classList.add(newStatus === 'cancelled' ? 'order-row-cancelled' : 'order-row-updated');

        // 취소 버튼 제거 (체결/취소된 경우)
        if (['filled', 'cancelled', 'expired'].includes(newStatus)) {
            const cancelBtn = row.querySelector('.btn--danger-outline');
            if (cancelBtn) cancelBtn.remove();
        }
    }
}

// 주문 행 HTML 생성
function createOrderRowHTML(order) {
    const orderType = order.order_type || order.type;
    const typeName = getTypeName(orderType);
    const statusName = getStatusName(order.status);
    const fillPercent = order.quantity > 0 ? Math.min(100, Math.round((order.filled_quantity || 0) / order.quantity * 100)) : 0;
    const priceDisplay = orderType === 'market' ? '<span class="text-muted">시장가</span>' : `$${parseFloat(order.price || 0).toFixed(2)}`;
    const createdAt = new Date(order.created_at).toLocaleString('ko-KR', { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });

    const canCancel = ['pending', 'open', 'partially_filled'].includes(order.status);

    return `
        <td><code class="order-id">${(order.order_id || '').substring(0, 12)}...</code></td>
        <td>
            <div class="user-cell">
                <span class="user-name">${escapeHtml(order.nickname || 'Unknown')}</span>
                <span class="user-email">${escapeHtml(order.email || '')}</span>
            </div>
        </td>
        <td><strong>${escapeHtml(order.symbol || 'BTCUSDT')}</strong></td>
        <td><span class="type-badge type-${orderType}">${typeName}</span></td>
        <td><span class="side-badge ${order.side}">${order.side === 'buy' ? 'BUY' : 'SELL'}</span></td>
        <td class="num-mono">${priceDisplay}</td>
        <td class="num-mono">${parseFloat(order.quantity || 0).toFixed(4)}</td>
        <td class="num-mono">${parseFloat(order.filled_quantity || 0).toFixed(4)}<span class="fill-percent">(${fillPercent}%)</span></td>
        <td><span class="status-badge status-${order.status}">${statusName}</span></td>
        <td class="text-muted">${createdAt}</td>
        <td>
            <button class="btn btn--sm btn--outline" onclick="viewOrder('${order.order_id}')" title="상세보기">
                <i class="fas fa-eye"></i>
            </button>
            ${canCancel ? `
            <button class="btn btn--sm btn--danger-outline" onclick="cancelOrder('${order.order_id}')" title="취소">
                <i class="fas fa-times"></i>
            </button>
            ` : ''}
        </td>
    `;
}

// 유틸리티 함수
function getTypeName(type) {
    const types = { market: '시장가', limit: '지정가', stop_market: '스톱마켓', stop_limit: '스톱리밋', take_profit: 'TP', stop_loss: 'SL' };
    return types[type] || type;
}

function getStatusName(status) {
    const statuses = { pending: '대기중', open: '활성', partially_filled: '부분체결', filled: '체결완료', cancelled: '취소됨', expired: '만료' };
    return statuses[status] || status;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// 자동 갱신
function initAutoRefresh() {
    if (isAutoRefresh) {
        startAutoRefresh();
    }
}

function startAutoRefresh() {
    stopAutoRefresh();
    refreshInterval = setInterval(() => {
        if (isAutoRefresh) {
            refreshOrders(true);
        }
    }, 10000); // 10초마다
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

// 주문 목록 새로고침 (AJAX)
async function refreshOrders(silent = false) {
    const refreshIcon = document.getElementById('refreshIcon');
    refreshIcon.classList.add('refresh-spin');

    try {
        const params = new URLSearchParams({
            status: currentFilters.status,
            side: currentFilters.side,
            type: currentFilters.type,
            search: currentFilters.search,
            ajax: '1'
        });

        const response = await fetch(`orders_ajax.php?${params}`);
        const data = await response.json();

        if (data.success) {
            updateOrdersTable(data.orders);
            updateStatsDisplay(data.stats);
            document.getElementById('totalCount').textContent = data.totalCount.toLocaleString() + '건';
        }
    } catch (error) {
        console.error('Refresh error:', error);
        if (!silent) {
            showToast('error', '새로고침 실패', '주문 목록을 불러오는데 실패했습니다.');
        }
    } finally {
        refreshIcon.classList.remove('refresh-spin');
    }
}

// 테이블 전체 업데이트
function updateOrdersTable(orders) {
    const tbody = document.querySelector('#ordersTable tbody');

    if (!orders || orders.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center text-muted py-4">
                    <i class="fas fa-inbox"></i> 주문이 없습니다
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = orders.map(order => `
        <tr id="order-${order.order_id}">
            ${createOrderRowHTML(order)}
        </tr>
    `).join('');
}

// 통계 업데이트
function updateStats() {
    // 비동기로 통계만 가져옴
    fetch('orders_ajax.php?stats_only=1')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateStatsDisplay(data.stats);
            }
        })
        .catch(console.error);
}

function updateStatsDisplay(stats) {
    if (!stats) return;
    // 통계 카드 값 업데이트
    const statCards = document.querySelectorAll('.stat-card__value');
    if (statCards[0]) statCards[0].textContent = (stats.active || 0).toLocaleString() + '건';
    if (statCards[1]) statCards[1].textContent = (stats.filled_today || 0).toLocaleString() + '건';
    if (statCards[2]) statCards[2].textContent = (stats.cancelled_today || 0).toLocaleString() + '건';
    if (statCards[3]) statCards[3].textContent = '$' + (stats.volume_today || 0).toLocaleString();
}

// 주문 상세 보기
async function viewOrder(orderId) {
    document.getElementById('orderModalBody').innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> 불러오는 중...
        </div>
    `;
    openModal('orderModal');

    try {
        const response = await fetch(`orders_ajax.php?action=detail&order_id=${orderId}`);
        const data = await response.json();

        if (data.success && data.order) {
            const order = data.order;
            document.getElementById('orderModalBody').innerHTML = `
                <div class="order-detail">
                    <div class="order-detail__item full-width">
                        <div class="order-detail__label">주문 ID</div>
                        <div class="order-detail__value"><code>${order.order_id}</code></div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">회원</div>
                        <div class="order-detail__value">${escapeHtml(order.nickname || order.email || 'Unknown')}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">상태</div>
                        <div class="order-detail__value"><span class="status-badge status-${order.status}">${getStatusName(order.status)}</span></div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">심볼</div>
                        <div class="order-detail__value">${order.symbol}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">유형</div>
                        <div class="order-detail__value">${getTypeName(order.order_type || order.type)}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">방향</div>
                        <div class="order-detail__value"><span class="side-badge ${order.side}">${order.side?.toUpperCase()}</span></div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">가격</div>
                        <div class="order-detail__value">${(order.order_type || order.type) === 'market' ? '시장가' : '$' + parseFloat(order.price || 0).toFixed(2)}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">수량</div>
                        <div class="order-detail__value">${parseFloat(order.quantity).toFixed(4)}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">체결량</div>
                        <div class="order-detail__value">${parseFloat(order.filled_quantity || 0).toFixed(4)}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">평균 체결가</div>
                        <div class="order-detail__value">${order.avg_fill_price ? '$' + parseFloat(order.avg_fill_price).toFixed(2) : '-'}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">생성일시</div>
                        <div class="order-detail__value">${new Date(order.created_at).toLocaleString()}</div>
                    </div>
                    <div class="order-detail__item">
                        <div class="order-detail__label">수정일시</div>
                        <div class="order-detail__value">${new Date(order.updated_at).toLocaleString()}</div>
                    </div>
                </div>
            `;
        } else {
            document.getElementById('orderModalBody').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle"></i> 주문 정보를 불러올 수 없습니다.
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('orderModalBody').innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="fas fa-exclamation-triangle"></i> 오류가 발생했습니다.
            </div>
        `;
    }
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
            updateOrderInTable(orderId, 'cancelled', {});
        } else {
            showToast('error', '취소 실패', result.error || '주문 취소에 실패했습니다.');
        }
    } catch (error) {
        showToast('error', '오류', '서버 통신 중 오류가 발생했습니다.');
    }
}

// 페이지 종료 시 소켓 정리
window.addEventListener('beforeunload', () => {
    stopAutoRefresh();
    if (socket) {
        socket.disconnect();
    }
});
</script>

<?php renderFooter(); ?>
