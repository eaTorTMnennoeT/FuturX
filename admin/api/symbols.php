<?php
/**
 * Admin Symbols API
 * 코인(심볼) 관리 API
 */

require_once __DIR__ . '/../auth.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// CSRF 검증
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$db = getDB();

try {
    switch ($action) {
        case 'save':
            $id = $input['id'] ?? null;
            $symbol = strtoupper(trim($input['symbol'] ?? ''));
            $baseAsset = strtoupper(trim($input['base_asset'] ?? ''));
            $displayName = trim($input['display_name'] ?? '');
            $icon = trim($input['icon'] ?? '');
            $color = trim($input['color'] ?? '#ffffff');
            $logoUrl = trim($input['logo_url'] ?? '');
            $priceDecimals = (int)($input['price_decimals'] ?? 2);
            $qtyDecimals = (int)($input['qty_decimals'] ?? 3);
            $tickSize = $input['tick_size'] ?? '0.01';
            $minQty = $input['min_qty'] ?? '0.001';
            $maxLeverage = (int)($input['max_leverage'] ?? 125);
            $sortOrder = (int)($input['sort_order'] ?? 0);

            if (!$symbol || !$baseAsset) {
                echo json_encode(['success' => false, 'error' => '심볼과 기초 자산은 필수입니다.']);
                exit;
            }

            if ($id) {
                // 수정
                $stmt = $db->prepare("UPDATE symbols SET
                    base_asset = ?, display_name = ?, icon = ?, color = ?, logo_url = ?,
                    price_decimals = ?, qty_decimals = ?, tick_size = ?, min_qty = ?, max_leverage = ?, sort_order = ?
                    WHERE id = ?");
                $stmt->execute([$baseAsset, $displayName, $icon, $color, $logoUrl, $priceDecimals, $qtyDecimals, $tickSize, $minQty, $maxLeverage, $sortOrder, $id]);
                echo json_encode(['success' => true, 'message' => '수정되었습니다.']);
            } else {
                // 추가 - 중복 체크
                $stmt = $db->prepare("SELECT id FROM symbols WHERE symbol = ?");
                $stmt->execute([$symbol]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => '이미 존재하는 심볼입니다.']);
                    exit;
                }

                $stmt = $db->prepare("INSERT INTO symbols
                    (symbol, base_asset, display_name, icon, color, logo_url, quote_asset, price_decimals, qty_decimals, tick_size, min_qty, max_leverage, sort_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 'USDT', ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$symbol, $baseAsset, $displayName, $icon, $color, $logoUrl, $priceDecimals, $qtyDecimals, $tickSize, $minQty, $maxLeverage, $sortOrder]);
                echo json_encode(['success' => true, 'message' => '추가되었습니다.']);
            }
            break;

        case 'toggle':
            $id = (int)($input['id'] ?? 0);
            $isActive = (int)($input['is_active'] ?? 0);

            $stmt = $db->prepare("UPDATE symbols SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $id]);
            echo json_encode(['success' => true, 'message' => $isActive ? '활성화되었습니다.' : '비활성화되었습니다.']);
            break;

        case 'sort':
            $id = (int)($input['id'] ?? 0);
            $sortOrder = (int)($input['sort_order'] ?? 0);

            $stmt = $db->prepare("UPDATE symbols SET sort_order = ? WHERE id = ?");
            $stmt->execute([$sortOrder, $id]);
            echo json_encode(['success' => true, 'message' => '순서가 변경되었습니다.']);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);

            // 관련 포지션/주문 체크
            $stmt = $db->prepare("SELECT COUNT(*) FROM positions WHERE symbol = (SELECT symbol FROM symbols WHERE id = ?) AND status = 'open'");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => '열린 포지션이 있어 삭제할 수 없습니다.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM symbols WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
