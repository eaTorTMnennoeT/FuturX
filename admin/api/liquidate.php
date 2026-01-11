<?php
/**
 * FuturX Admin API - 강제 청산
 * POST /admin/api/liquidate.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once dirname(__DIR__) . '/auth.php';

// 관리자 권한 확인
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '관리자 권한이 필요합니다.']);
    exit;
}

// POST 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$positionId = intval($input['position_id'] ?? 0);
$reason = $input['reason'] ?? 'admin_liquidation';

if (!$positionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '포지션 ID가 필요합니다.']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // 포지션 조회
    $stmt = $db->prepare("
        SELECT p.*, u.email, w.balance
        FROM positions p
        JOIN users u ON p.user_id = u.id
        JOIN wallets w ON w.user_id = u.id AND w.currency = 'USDT'
        WHERE p.id = ? AND p.status = 'open'
        FOR UPDATE
    ");
    $stmt->execute([$positionId]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$position) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => '포지션을 찾을 수 없거나 이미 청산되었습니다.']);
        exit;
    }

    // 현재가 조회 (캐시에서 또는 기본값)
    $currentPrice = floatval($position['mark_price'] ?? $position['entry_price']);

    // 바이낸스 현재가 가져오기 시도
    $binanceUrl = "https://fapi.binance.com/fapi/v1/ticker/price?symbol=" . $position['symbol'];
    $ch = curl_init($binanceUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $priceData = json_decode($response, true);
        if (isset($priceData['price'])) {
            $currentPrice = floatval($priceData['price']);
        }
    }

    // PnL 계산
    $entryPrice = floatval($position['entry_price']);
    $size = floatval($position['size']);
    $leverage = intval($position['leverage']);
    $side = strtolower($position['side']);

    $pnl = $side === 'long'
        ? ($currentPrice - $entryPrice) * $size
        : ($entryPrice - $currentPrice) * $size;

    $margin = ($entryPrice * $size) / $leverage;

    // 포지션 상태 업데이트
    $stmt = $db->prepare("
        UPDATE positions SET
            status = 'liquidated',
            exit_price = ?,
            realized_pnl = ?,
            closed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$currentPrice, $pnl, $positionId]);

    // 지갑 잔액 업데이트 (마진 반환 + PnL)
    $returnAmount = $margin + $pnl;
    if ($returnAmount > 0) {
        $stmt = $db->prepare("
            UPDATE wallets SET
                balance = balance + ?,
                updated_at = NOW()
            WHERE user_id = ? AND currency = 'USDT'
        ");
        $stmt->execute([$returnAmount, $position['user_id']]);
    }

    // 거래 기록 추가
    $orderId = 'LIQ_' . $positionId . '_' . time();
    $stmt = $db->prepare("
        INSERT INTO trades (user_id, order_id, position_id, symbol, side, position_side, price, quantity, quote_quantity, fee, realized_pnl, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())
    ");
    $stmt->execute([
        $position['user_id'],
        $orderId,
        $positionId,
        $position['symbol'],
        $side === 'long' ? 'sell' : 'buy',
        $side,
        $currentPrice,
        $size,
        $currentPrice * $size,
        $pnl
    ]);

    // 관리자 로그
    $adminId = $_SESSION['admin_id'] ?? 0;
    $stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'liquidate_position', 'position', ?, ?, ?, NOW())
    ");
    $details = json_encode([
        'user_id' => $position['user_id'],
        'email' => $position['email'],
        'symbol' => $position['symbol'],
        'side' => $side,
        'size' => $size,
        'entry_price' => $entryPrice,
        'exit_price' => $currentPrice,
        'pnl' => $pnl,
        'reason' => $reason
    ]);
    $stmt->execute([$adminId, $positionId, $details, $_SERVER['REMOTE_ADDR'] ?? '']);

    $db->commit();

    // WebSocket 서버에 청산 알림 전송
    $wsData = [
        'type' => 'position:liquidated',
        'data' => [
            'position_id' => $positionId,
            'user_id' => $position['user_id'],
            'email' => $position['email'],
            'symbol' => $position['symbol'],
            'side' => $side,
            'size' => $size,
            'entry_price' => $entryPrice,
            'exit_price' => $currentPrice,
            'pnl' => $pnl,
            'reason' => $reason
        ]
    ];

    // Node.js 서버에 브로드캐스트 요청
    $ch = curl_init('http://localhost:3002/api/broadcast');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($wsData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_exec($ch);
    curl_close($ch);

    echo json_encode([
        'success' => true,
        'message' => '포지션이 강제 청산되었습니다.',
        'data' => [
            'position_id' => $positionId,
            'user_email' => $position['email'],
            'symbol' => $position['symbol'],
            'side' => $side,
            'size' => $size,
            'entry_price' => $entryPrice,
            'exit_price' => $currentPrice,
            'pnl' => round($pnl, 2),
            'margin_returned' => round($returnAmount, 2)
        ]
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '데이터베이스 오류: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
