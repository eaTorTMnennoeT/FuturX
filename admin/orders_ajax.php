<?php
/**
 * FuturX Admin - 주문 관리 AJAX API
 * 실시간 주문 목록 및 통계 조회
 */

header('Content-Type: application/json');

require_once 'layout.php';

$db = getDB();

// 통계만 요청
if (isset($_GET['stats_only'])) {
    $stats = getOrderStats($db);
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

// 주문 상세 조회
if (isset($_GET['action']) && $_GET['action'] === 'detail') {
    $orderId = $_GET['order_id'] ?? '';
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => '주문 ID가 필요합니다.']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT o.*, u.email, u.nickname
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        echo json_encode(['success' => false, 'error' => '주문을 찾을 수 없습니다.']);
    }
    exit;
}

// 주문 목록 조회 (AJAX)
if (isset($_GET['ajax'])) {
    $status = $_GET['status'] ?? 'all';
    $side = $_GET['side'] ?? 'all';
    $type = $_GET['type'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 20;

    // 필터 조건
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
    $stats = getOrderStats($db);

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'totalCount' => (int)$totalCount,
        'stats' => $stats
    ]);
    exit;
}

// 통계 조회 함수
function getOrderStats($db) {
    $stats = [];

    // 활성 주문
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'open', 'partially_filled')");
    $stats['active'] = (int)$stmt->fetchColumn();

    // 오늘 체결 주문
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'filled' AND DATE(updated_at) = CURDATE()");
    $stats['filled_today'] = (int)$stmt->fetchColumn();

    // 오늘 취소 주문
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled' AND DATE(updated_at) = CURDATE()");
    $stats['cancelled_today'] = (int)$stmt->fetchColumn();

    // 오늘 체결 금액
    $stmt = $db->query("SELECT COALESCE(SUM(price * filled_quantity), 0) FROM orders WHERE status = 'filled' AND DATE(updated_at) = CURDATE()");
    $stats['volume_today'] = (float)$stmt->fetchColumn();

    return $stats;
}

// 잘못된 요청
echo json_encode(['success' => false, 'error' => '잘못된 요청입니다.']);
