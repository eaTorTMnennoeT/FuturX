<?php
/**
 * Futures Trading API
 * 주문, 포지션, 거래내역 관리
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// WebSocket Broadcast URL (config.php의 WS_SERVER_URL 사용)
define('WS_BROADCAST_URL', WS_SERVER_URL . '/api/broadcast');

/**
 * WebSocket 서버에 이벤트 브로드캐스트
 * 비동기로 전송 (응답 대기 안함)
 */
function broadcastToWS($type, $payload) {
    $data = json_encode([
        'type' => $type,
        'payload' => $payload
    ]);

    error_log("[DEBUG] broadcastToWS - type: $type, url: " . WS_BROADCAST_URL);

    // cURL로 비동기 전송
    $ch = curl_init(WS_BROADCAST_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 500, // 500ms 타임아웃 (응답 대기)
        CURLOPT_NOSIGNAL => 1
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    error_log("[DEBUG] broadcastToWS - httpCode: $httpCode, error: $error, response: $response");

    return $httpCode === 200;
}

/**
 * 사용자 계정 정보를 WebSocket으로 푸시 (안전하게)
 * 에러가 나도 무시하고 진행
 */
function broadcastAccountUpdateSafe($db, $userId) {
    try {
        error_log("[DEBUG] broadcastAccountUpdateSafe START - userId: $userId");

        // 잔고 조회
        $stmt = $db->prepare("SELECT balance, available_balance FROM wallets WHERE user_id = ? AND currency = 'USDT'");
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch();
        $balance = $wallet ? floatval($wallet['balance']) : 0;
        $availableBalance = $wallet ? floatval($wallet['available_balance']) : 0;

        error_log("[DEBUG] broadcastAccountUpdateSafe - balance: $balance, available: $availableBalance");

        // 포지션 조회
        $stmt = $db->prepare("
            SELECT id, symbol, side, size, entry_price, leverage, margin,
                   liquidation_price, tp_price, sl_price, created_at
            FROM positions
            WHERE user_id = ? AND status = 'open'
        ");
        $stmt->execute([$userId]);
        $positions = $stmt->fetchAll();

        // 마진 합계
        $totalMargin = 0;
        foreach ($positions as $pos) {
            $totalMargin += floatval($pos['margin']);
        }

        $marginBalance = $balance;

        error_log("[DEBUG] broadcastAccountUpdateSafe - calling broadcastToWS");

        $result = broadcastToWS('account:update', [
            'user_id' => $userId,
            'balance' => $balance,
            'positions' => $positions,
            'margin_balance' => $marginBalance,
            'available_balance' => $availableBalance,
            'unrealized_pnl' => 0
        ]);

        error_log("[DEBUG] broadcastAccountUpdateSafe - broadcastToWS result: " . ($result ? 'success' : 'failed'));
    } catch (Exception $e) {
        // 에러 무시 - 로그만 남김
        error_log("[DEBUG] broadcastAccountUpdate error: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// JSON body에서 action 읽기 지원
$jsonInput = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $_POST['action'] ?? ($jsonInput['action'] ?? '');
$db = getDB();

switch ($action) {
    // 주문 관련
    case 'place_order':
        handlePlaceOrder($db);
        break;

    case 'cancel_order':
        handleCancelOrder($db);
        break;

    case 'cancel_all_orders':
        handleCancelAllOrders($db);
        break;

    case 'get_orders':
        handleGetOrders($db);
        break;

    case 'get_open_orders':
        handleGetOpenOrders($db);
        break;

    // 포지션 관련
    case 'get_positions':
        handleGetPositions($db);
        break;

    case 'close_position':
        handleClosePosition($db);
        break;

    case 'set_leverage':
        handleSetLeverage($db);
        break;

    case 'set_tpsl':
        handleSetTPSL($db);
        break;

    // 거래내역
    case 'get_trades':
        handleGetTrades($db);
        break;

    // 주문 내역 (전체)
    case 'get_order_history':
        handleGetOrderHistory($db);
        break;

    // 거래 내역 (체결)
    case 'get_trade_history':
        handleGetTradeHistory($db);
        break;

    // 잔고
    case 'get_balance':
        handleGetBalance($db);
        break;

    // 현재가 조회 (바이낸스 프록시)
    case 'get_price':
        handleGetPrice();
        break;

    // P&L 히스토리 (종료된 포지션)
    case 'get_pnl_history':
        handleGetPnlHistory($db);
        break;

    // P&L 통계
    case 'get_pnl_stats':
        handleGetPnlStats($db);
        break;

    default:
        errorResponse('Unknown action', 400);
}

// ========================================
// 인증 확인
// ========================================
function requireAuth($db) {
    $token = getBearerToken();

    if (!$token) {
        errorResponse('인증이 필요합니다.', 401);
    }

    $stmt = $db->prepare("
        SELECT u.*
        FROM user_sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        errorResponse('세션이 만료되었습니다.', 401);
    }

    return $user;
}

function getBearerToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }

    return $_COOKIE['auth_token'] ?? null;
}

// ========================================
// 현재가 조회 (바이낸스)
// ========================================
function fetchBinancePrice($symbol) {
    $url = "https://fapi.binance.com/fapi/v1/ticker/price?symbol=" . strtoupper($symbol);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return floatval($data['price'] ?? 0);
}

// ========================================
// 시장가 주문용 호가 조회 (바이낸스 bookTicker)
// 매수는 ask(매도1호가), 매도는 bid(매수1호가)로 체결
// ========================================
function fetchBinanceBookPrice($symbol, $side) {
    $url = "https://fapi.binance.com/fapi/v1/ticker/bookTicker?symbol=" . strtoupper($symbol);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // 매수(buy)는 매도1호가(askPrice)로 체결
    // 매도(sell)는 매수1호가(bidPrice)로 체결
    if ($side === 'buy') {
        return floatval($data['askPrice'] ?? 0);
    } else {
        return floatval($data['bidPrice'] ?? 0);
    }
}

function handleGetPrice() {
    $symbol = strtoupper($_GET['symbol'] ?? 'BTCUSDT');
    $price = fetchBinancePrice($symbol);

    if ($price <= 0) {
        errorResponse('가격 조회 실패');
    }

    successResponse(['symbol' => $symbol, 'price' => $price]);
}

// ========================================
// 주문 생성
// ========================================
function handlePlaceOrder($db) {
    $user = requireAuth($db);
    $input = json_decode(file_get_contents('php://input'), true);

    $symbol = strtoupper(sanitize($input['symbol'] ?? 'BTCUSDT'));
    $side = strtolower(sanitize($input['side'] ?? '')); // buy, sell
    $positionSide = strtolower(sanitize($input['position_side'] ?? '')); // long, short
    $orderType = strtolower(sanitize($input['order_type'] ?? 'market')); // limit, market
    $quantity = floatval($input['quantity'] ?? 0);
    $price = floatval($input['price'] ?? 0);
    $leverage = intval($input['leverage'] ?? DEFAULT_LEVERAGE);
    $reduceOnly = boolval($input['reduce_only'] ?? false);
    $marketPrice = floatval($input['market_price'] ?? 0); // 클라이언트 WebSocket 실시간 가격

    // 유효성 검사
    if (!in_array($side, ['buy', 'sell'])) {
        errorResponse('올바른 주문 방향을 선택해주세요.');
    }

    if (!in_array($positionSide, ['long', 'short'])) {
        errorResponse('올바른 포지션 방향을 선택해주세요.');
    }

    if (!in_array($orderType, ['limit', 'market'])) {
        errorResponse('올바른 주문 유형을 선택해주세요.');
    }

    if ($quantity <= 0) {
        errorResponse('수량을 입력해주세요.');
    }

    if ($orderType === 'limit' && $price <= 0) {
        errorResponse('지정가 주문시 가격을 입력해주세요.');
    }

    if ($leverage < 1 || $leverage > MAX_LEVERAGE) {
        errorResponse('레버리지는 1~' . MAX_LEVERAGE . '배 사이어야 합니다.');
    }

    // 시장가 주문시 - 클라이언트 WebSocket 가격 우선 사용
    if ($orderType === 'market') {
        if ($marketPrice > 0) {
            // 클라이언트에서 전송한 WebSocket 실시간 가격 사용
            $price = $marketPrice;
        } else {
            // fallback: API 조회
            $price = fetchBinancePrice($symbol);
            if ($price <= 0) {
                errorResponse('현재가 조회에 실패했습니다.');
            }
        }
    }

    // 필요 마진 계산
    $notionalValue = $quantity * $price;
    $requiredMargin = $notionalValue / $leverage;
    $fee = $notionalValue * getTakerFee();
    $totalRequired = $requiredMargin + $fee;

    // 잔고 확인
    $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = ? AND currency = 'USDT' FOR UPDATE");
    $stmt->execute([$user['id']]);
    $wallet = $stmt->fetch();

    // 미실현 PnL 및 Initial Margin 계산 (바이낸스/바이빗 방식)
    $unrealizedPnl = 0;
    $initialMargin = 0;
    $stmt2 = $db->prepare("SELECT symbol, side, size, entry_price, margin FROM positions WHERE user_id = ? AND status = 'open'");
    $stmt2->execute([$user['id']]);
    $positions = $stmt2->fetchAll();

    foreach ($positions as $pos) {
        // Initial Margin 합산
        $initialMargin += floatval($pos['margin']);

        // 각 포지션 심볼의 현재가 조회
        $posSymbol = $pos['symbol'];
        $posCurrentPrice = $marketPrice ?: $price; // 기본값

        // 같은 심볼이면 전달받은 가격 사용, 다르면 API 조회
        if ($posSymbol === $symbol) {
            $posCurrentPrice = $marketPrice ?: $price;
        } else {
            $fetchedPrice = fetchBinancePrice($posSymbol);
            if ($fetchedPrice > 0) {
                $posCurrentPrice = $fetchedPrice;
            }
        }

        if ($pos['side'] === 'long') {
            $unrealizedPnl += ($posCurrentPrice - floatval($pos['entry_price'])) * floatval($pos['size']);
        } else {
            $unrealizedPnl += (floatval($pos['entry_price']) - $posCurrentPrice) * floatval($pos['size']);
        }
    }

    // 바이낸스/바이빗 방식: Available = Margin Balance - Initial Margin
    // Margin Balance = wallet balance + unrealized PnL
    $marginBalance = floatval($wallet['balance']) + $unrealizedPnl;
    $effectiveBalance = $marginBalance - $initialMargin;

    if (!$wallet || $effectiveBalance < $totalRequired) {
        errorResponse('잔고가 부족합니다. 필요: ' . number_format($totalRequired, 2) . ' USDT, 가용: ' . number_format($effectiveBalance, 2) . ' USDT');
    }

    try {
        $db->beginTransaction();

        // 주문 ID 생성
        $orderId = generateOrderId();

        // 주문 생성
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, order_id, symbol, side, position_side, order_type, price, quantity, leverage, reduce_only, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $status = ($orderType === 'market') ? 'filled' : 'open';
        $stmt->execute([
            $user['id'], $orderId, $symbol, $side, $positionSide, $orderType, $price, $quantity, $leverage, $reduceOnly ? 1 : 0, $status
        ]);
        $orderDbId = $db->lastInsertId();

        // 시장가 주문은 즉시 체결
        if ($orderType === 'market') {
            // 주문 업데이트
            $stmt = $db->prepare("UPDATE orders SET filled_quantity = ?, avg_fill_price = ?, status = 'filled' WHERE id = ?");
            $stmt->execute([$quantity, $price, $orderDbId]);

            // 포지션 처리
            $result = processPosition($db, $user['id'], $symbol, $side, $positionSide, $quantity, $price, $leverage, $orderId, $reduceOnly);

            // 잔고 차감 (신규 포지션 오픈시만)
            if (!$reduceOnly && !$result['is_close']) {
                $newAvailable = $wallet['available_balance'] - $totalRequired;
                $newFrozen = $wallet['frozen_balance'] + $requiredMargin;

                $stmt = $db->prepare("UPDATE wallets SET available_balance = ?, frozen_balance = ? WHERE id = ?");
                $stmt->execute([$newAvailable, $newFrozen, $wallet['id']]);

                // 트랜잭션 기록
                $stmt = $db->prepare("INSERT INTO transactions (user_id, type, currency, amount, balance_before, balance_after, reference_id, reference_type) VALUES (?, 'fee', 'USDT', ?, ?, ?, ?, 'order')");
                $stmt->execute([$user['id'], -$fee, $wallet['available_balance'], $newAvailable, $orderId]);
            }

            // 거래 기록
            $stmt = $db->prepare("
                INSERT INTO trades (user_id, order_id, position_id, symbol, side, position_side, price, quantity, quote_quantity, fee, realized_pnl)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'], $orderId, $result['position_id'], $symbol, $side, $positionSide, $price, $quantity, $notionalValue, $fee, $result['realized_pnl']
            ]);
        } else {
            // 지정가 주문 - 마진 동결
            $newAvailable = $wallet['available_balance'] - $totalRequired;
            $newFrozen = $wallet['frozen_balance'] + $totalRequired;

            $stmt = $db->prepare("UPDATE wallets SET available_balance = ?, frozen_balance = ? WHERE id = ?");
            $stmt->execute([$newAvailable, $newFrozen, $wallet['id']]);
        }

        $db->commit();

        // 결과 조회
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderDbId]);
        $order = $stmt->fetch();

        // WebSocket 브로드캐스트
        if ($orderType === 'market') {
            // 시장가 체결 브로드캐스트
            broadcastToWS('order:filled', [
                'user_id' => $user['id'],
                'login_id' => $user['username'] ?? $user['email'],
                'nickname' => $user['nickname'],
                'order_id' => $orderId,
                'symbol' => $symbol,
                'side' => $side,
                'position_side' => $positionSide,
                'price' => $price,
                'quantity' => $quantity,
                'leverage' => $leverage
            ]);

            // 포지션 오픈/추가 브로드캐스트
            if (!$result['is_close'] ?? false) {
                broadcastToWS('position:opened', [
                    'user_id' => $user['id'],
                    'login_id' => $user['username'] ?? $user['email'],
                    'nickname' => $user['nickname'],
                    'symbol' => $symbol,
                    'side' => $positionSide,
                    'entry_price' => $price,
                    'size' => $quantity,
                    'leverage' => $leverage
                ]);
            }
        } else {
            // 지정가 주문 접수 브로드캐스트
            broadcastToWS('order:placed', [
                'user_id' => $user['id'],
                'login_id' => $user['username'] ?? $user['email'],
                'nickname' => $user['nickname'],
                'order_id' => $orderId,
                'symbol' => $symbol,
                'side' => $side,
                'position_side' => $positionSide,
                'order_type' => 'limit',
                'price' => $price,
                'quantity' => $quantity
            ]);
        }

        // 같은 사용자의 모든 세션에 주문 리프레시 알림 (PC-모바일 동기화)
        broadcastToWS('orders:refresh', [
            'user_id' => $user['id'],
            'action' => $orderType === 'market' ? 'fill' : 'place',
            'order_id' => $orderId,
            'symbol' => $symbol
        ]);

        // WebSocket으로 계정 정보 푸시
        broadcastAccountUpdateSafe($db, $user['id']);

        successResponse([
            'order' => $order,
            'executed_price' => $price,
            'fee' => $fee,
            'margin' => $requiredMargin
        ], '주문이 ' . ($orderType === 'market' ? '체결' : '접수') . '되었습니다.');

    } catch (Exception $e) {
        $db->rollBack();
        errorResponse('주문 처리 중 오류가 발생했습니다: ' . $e->getMessage());
    }
}

// ========================================
// 포지션 처리
// ========================================
function processPosition($db, $userId, $symbol, $side, $positionSide, $quantity, $price, $leverage, $orderId, $reduceOnly) {
    $result = [
        'position_id' => null,
        'is_close' => false,
        'realized_pnl' => 0
    ];

    // 기존 포지션 확인
    $stmt = $db->prepare("SELECT * FROM positions WHERE user_id = ? AND symbol = ? AND side = ? AND status = 'open' FOR UPDATE");
    $stmt->execute([$userId, $symbol, $positionSide]);
    $existingPosition = $stmt->fetch();

    // 포지션 오픈: buy+long 또는 sell+short
    // 포지션 클로즈: sell+long 또는 buy+short
    $isOpening = ($side === 'buy' && $positionSide === 'long') || ($side === 'sell' && $positionSide === 'short');

    if ($existingPosition) {
        if ($isOpening && !$reduceOnly) {
            // 포지션 추가 (평균단가 계산)
            $newSize = $existingPosition['size'] + $quantity;
            $newEntryPrice = (($existingPosition['entry_price'] * $existingPosition['size']) + ($price * $quantity)) / $newSize;
            $newMargin = $existingPosition['margin'] + ($quantity * $price / $leverage);

            $stmt = $db->prepare("UPDATE positions SET size = ?, entry_price = ?, margin = ?, leverage = ? WHERE id = ?");
            $stmt->execute([$newSize, $newEntryPrice, $newMargin, $leverage, $existingPosition['id']]);

            $result['position_id'] = $existingPosition['id'];
        } else {
            // 포지션 청산
            $closeQty = min($quantity, $existingPosition['size']);
            $remainingSize = $existingPosition['size'] - $closeQty;

            // PNL 계산
            if ($positionSide === 'long') {
                $pnl = ($price - $existingPosition['entry_price']) * $closeQty;
            } else {
                $pnl = ($existingPosition['entry_price'] - $price) * $closeQty;
            }

            $result['realized_pnl'] = $pnl;
            $result['is_close'] = true;

            if ($remainingSize <= 0) {
                // 포지션 완전 청산
                $stmt = $db->prepare("UPDATE positions SET status = 'closed', size = 0, realized_pnl = realized_pnl + ?, closed_at = NOW() WHERE id = ?");
                $stmt->execute([$pnl, $existingPosition['id']]);

                // 마진 반환
                $closedMargin = $existingPosition['margin'];
                $stmt = $db->prepare("UPDATE wallets SET available_balance = available_balance + ? + ?, frozen_balance = frozen_balance - ? WHERE user_id = ? AND currency = 'USDT'");
                $stmt->execute([$closedMargin, $pnl, $closedMargin, $userId]);

                // 트랜잭션 기록
                if ($pnl != 0) {
                    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, currency, amount, reference_id, reference_type, memo) VALUES (?, 'pnl', 'USDT', ?, ?, 'position', ?)");
                    $stmt->execute([$userId, $pnl, $orderId, ($pnl > 0 ? '포지션 수익' : '포지션 손실')]);
                }
            } else {
                // 부분 청산
                $partialMargin = $existingPosition['margin'] * ($closeQty / $existingPosition['size']);
                $remainingMargin = $existingPosition['margin'] - $partialMargin;

                $stmt = $db->prepare("UPDATE positions SET size = ?, margin = ?, realized_pnl = realized_pnl + ? WHERE id = ?");
                $stmt->execute([$remainingSize, $remainingMargin, $pnl, $existingPosition['id']]);

                // 마진 일부 반환
                $stmt = $db->prepare("UPDATE wallets SET available_balance = available_balance + ? + ?, frozen_balance = frozen_balance - ? WHERE user_id = ? AND currency = 'USDT'");
                $stmt->execute([$partialMargin, $pnl, $partialMargin, $userId]);
            }

            $result['position_id'] = $existingPosition['id'];
        }
    } else {
        if ($isOpening && !$reduceOnly) {
            // 신규 포지션 생성
            $margin = $quantity * $price / $leverage;
            $liqPrice = calculateLiquidationPrice($price, $leverage, $positionSide);

            $stmt = $db->prepare("
                INSERT INTO positions (user_id, symbol, side, size, entry_price, mark_price, liquidation_price, margin, leverage, margin_mode)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'cross')
            ");
            $stmt->execute([$userId, $symbol, $positionSide, $quantity, $price, $price, $liqPrice, $margin, $leverage]);

            $result['position_id'] = $db->lastInsertId();
        }
    }

    // 교차 마진 포지션 청산가 재계산 (포지션 추가/변경/종료 후)
    recalculateCrossLiquidationPrices($db, $userId);

    return $result;
}

// 청산가 계산 (격리 마진용 - 단일 포지션 기준)
function calculateLiquidationPrice($entryPrice, $leverage, $side) {
    $maintenanceMarginRate = 0.005; // 0.5%

    if ($side === 'long') {
        return $entryPrice * (1 - (1 / $leverage) + $maintenanceMarginRate);
    } else {
        return $entryPrice * (1 + (1 / $leverage) - $maintenanceMarginRate);
    }
}

/**
 * 교차 마진 청산가 계산 (계좌 전체 잔고 기반)
 * 바이낸스 공식 기반:
 * Long: Liq Price = Entry Price - (Available Balance + Position Margin - Maintenance Margin) / Position Size
 * Short: Liq Price = Entry Price + (Available Balance + Position Margin - Maintenance Margin) / Position Size
 *
 * @param float $entryPrice 진입가
 * @param float $positionSize 포지션 크기
 * @param string $side long/short
 * @param float $walletBalance 지갑 잔고
 * @param float $totalUnrealizedPnl 전체 미실현 PNL
 * @param float $totalPositionMargin 전체 포지션 마진
 * @param float $totalMaintenanceMargin 전체 유지 마진
 * @param float $positionMargin 해당 포지션 마진
 * @return float 청산가
 */
function calculateCrossLiquidationPrice($entryPrice, $positionSize, $side, $walletBalance, $totalUnrealizedPnl, $totalPositionMargin, $totalMaintenanceMargin, $positionMargin) {
    if ($positionSize <= 0) return 0;

    // Margin Balance = Wallet Balance + Total Unrealized PNL
    $marginBalance = $walletBalance + $totalUnrealizedPnl;

    // Available Balance for this position = Margin Balance - Total Margin (excluding current position)
    // 다른 포지션들의 마진을 제외한 사용 가능 금액
    $otherPositionsMargin = $totalPositionMargin - $positionMargin;
    $availableForPosition = $marginBalance - $otherPositionsMargin;

    // MMR (Maintenance Margin Rate) - 티어 기반이지만 간단히 0.5% 사용
    $mmr = 0.005;
    $maintenanceMargin = $entryPrice * $positionSize * $mmr;

    // 청산까지 버틸 수 있는 손실 금액
    $maxLoss = $availableForPosition - $maintenanceMargin;

    if ($side === 'long') {
        // Long: 가격이 하락하면 손실
        // 청산가 = 진입가 - (버틸 수 있는 손실 / 포지션 크기)
        $liqPrice = $entryPrice - ($maxLoss / $positionSize);
    } else {
        // Short: 가격이 상승하면 손실
        // 청산가 = 진입가 + (버틸 수 있는 손실 / 포지션 크기)
        $liqPrice = $entryPrice + ($maxLoss / $positionSize);
    }

    // 청산가는 0 이상이어야 함
    return max(0, $liqPrice);
}

/**
 * 특정 사용자의 모든 교차 마진 포지션 청산가 재계산
 * 포지션이 추가/종료될 때 호출
 */
function recalculateCrossLiquidationPrices($db, $userId) {
    // 1. 지갑 잔고 조회
    $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'USDT'");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    $walletBalance = $wallet ? floatval($wallet['balance']) : 0;

    // 2. 모든 오픈 포지션 조회
    $stmt = $db->prepare("
        SELECT id, symbol, side, size, entry_price, margin, leverage, margin_mode
        FROM positions
        WHERE user_id = ? AND status = 'open'
    ");
    $stmt->execute([$userId]);
    $positions = $stmt->fetchAll();

    if (empty($positions)) return;

    // 3. 전체 미실현 PNL 및 마진 계산
    $totalUnrealizedPnl = 0;
    $totalPositionMargin = 0;
    $totalMaintenanceMargin = 0;

    // 현재가 캐시 (심볼별)
    $currentPrices = [];

    foreach ($positions as $pos) {
        $symbol = $pos['symbol'];
        $size = floatval($pos['size']);
        $entryPrice = floatval($pos['entry_price']);
        $margin = floatval($pos['margin']);

        // 현재가 조회 (캐시 사용)
        if (!isset($currentPrices[$symbol])) {
            // 바이낸스 API에서 현재가 조회
            $fetchedPrice = fetchBinancePrice($symbol);
            $currentPrices[$symbol] = $fetchedPrice > 0 ? $fetchedPrice : $entryPrice;
        }
        $currentPrice = $currentPrices[$symbol];

        // 미실현 PNL 계산
        if ($pos['side'] === 'long') {
            $pnl = ($currentPrice - $entryPrice) * $size;
        } else {
            $pnl = ($entryPrice - $currentPrice) * $size;
        }

        $totalUnrealizedPnl += $pnl;
        $totalPositionMargin += $margin;

        // 유지 마진 (MMR 0.5%)
        $notional = $currentPrice * $size;
        $totalMaintenanceMargin += $notional * 0.005;
    }

    // 4. 각 교차 마진 포지션의 청산가 재계산
    foreach ($positions as $pos) {
        if ($pos['margin_mode'] !== 'cross') continue;

        $symbol = $pos['symbol'];
        $size = floatval($pos['size']);
        $entryPrice = floatval($pos['entry_price']);
        $margin = floatval($pos['margin']);

        $newLiqPrice = calculateCrossLiquidationPrice(
            $entryPrice,
            $size,
            $pos['side'],
            $walletBalance,
            $totalUnrealizedPnl,
            $totalPositionMargin,
            $totalMaintenanceMargin,
            $margin
        );

        // 청산가 업데이트
        $updateStmt = $db->prepare("UPDATE positions SET liquidation_price = ? WHERE id = ?");
        $updateStmt->execute([$newLiqPrice, $pos['id']]);
    }

    error_log("[Cross Margin] Recalculated liquidation prices for user $userId - " . count($positions) . " positions");
}

// ========================================
// 주문 취소
// ========================================
function handleCancelOrder($db) {
    $user = requireAuth($db);
    $input = json_decode(file_get_contents('php://input'), true);

    $orderId = sanitize($input['order_id'] ?? '');

    if (empty($orderId)) {
        errorResponse('주문 ID를 입력해주세요.');
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ? AND status IN ('pending', 'open', 'partially_filled')");
    $stmt->execute([$orderId, $user['id']]);
    $order = $stmt->fetch();

    if (!$order) {
        errorResponse('취소할 수 있는 주문이 없습니다.');
    }

    try {
        $db->beginTransaction();

        // 주문 취소
        $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$order['id']]);

        // 동결 마진 반환
        $remainingQty = $order['quantity'] - $order['filled_quantity'];
        $refundMargin = ($remainingQty * $order['price']) / $order['leverage'];
        $refundFee = ($remainingQty * $order['price']) * getTakerFee();
        $totalRefund = $refundMargin + $refundFee;

        $stmt = $db->prepare("UPDATE wallets SET available_balance = available_balance + ?, frozen_balance = frozen_balance - ? WHERE user_id = ? AND currency = 'USDT'");
        $stmt->execute([$totalRefund, $totalRefund, $user['id']]);

        $db->commit();

        // WebSocket 브로드캐스트 - 주문 취소
        broadcastToWS('order:cancelled', [
            'user_id' => $user['id'],
            'login_id' => $user['username'] ?? $user['email'],
            'nickname' => $user['nickname'],
            'order_id' => $orderId,
            'symbol' => $order['symbol'],
            'side' => $order['side'],
            'price' => $order['price'],
            'quantity' => $order['quantity']
        ]);

        // 같은 사용자의 모든 세션에 주문 리프레시 알림 (PC-모바일 동기화)
        broadcastToWS('orders:refresh', [
            'user_id' => $user['id'],
            'action' => 'cancel',
            'order_id' => $orderId,
            'symbol' => $order['symbol']
        ]);

        // WebSocket으로 계정 정보 푸시
        broadcastAccountUpdateSafe($db, $user['id']);

        successResponse(['order_id' => $orderId, 'refunded' => $totalRefund], '주문이 취소되었습니다.');

    } catch (Exception $e) {
        $db->rollBack();
        errorResponse('주문 취소 중 오류가 발생했습니다.');
    }
}

// ========================================
// 전체 주문 취소
// ========================================
function handleCancelAllOrders($db) {
    $user = requireAuth($db);
    $input = json_decode(file_get_contents('php://input'), true);

    $symbol = isset($input['symbol']) ? strtoupper($input['symbol']) : null;

    // 취소할 주문 조회
    $sql = "SELECT * FROM orders WHERE user_id = ? AND status IN ('pending', 'open', 'partially_filled')";
    $params = [$user['id']];

    if (!empty($symbol)) {
        $sql .= " AND symbol = ?";
        $params[] = $symbol;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    if (count($orders) === 0) {
        successResponse(['canceled_count' => 0], '취소할 주문이 없습니다.');
        return;
    }

    try {
        $db->beginTransaction();

        $canceledCount = 0;
        $totalRefund = 0;

        foreach ($orders as $order) {
            // 주문 취소
            $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order['id']]);

            // 동결 마진 반환
            $remainingQty = $order['quantity'] - $order['filled_quantity'];
            $refundMargin = ($remainingQty * $order['price']) / $order['leverage'];
            $refundFee = ($remainingQty * $order['price']) * getTakerFee();
            $refund = $refundMargin + $refundFee;
            $totalRefund += $refund;

            $stmt = $db->prepare("UPDATE wallets SET available_balance = available_balance + ?, frozen_balance = frozen_balance - ? WHERE user_id = ? AND currency = 'USDT'");
            $stmt->execute([$refund, $refund, $user['id']]);

            $canceledCount++;
        }

        $db->commit();

        // 같은 사용자의 모든 세션에 주문 리프레시 알림
        broadcastToWS('orders:refresh', [
            'user_id' => $user['id'],
            'action' => 'cancel_all',
            'symbol' => $symbol
        ]);

        // WebSocket으로 계정 정보 푸시
        broadcastAccountUpdateSafe($db, $user['id']);

        successResponse([
            'canceled_count' => $canceledCount,
            'refunded' => $totalRefund
        ], "{$canceledCount}개 주문이 취소되었습니다.");

    } catch (Exception $e) {
        $db->rollBack();
        errorResponse('주문 취소 중 오류가 발생했습니다.');
    }
}

// ========================================
// 주문 조회
// ========================================
function handleGetOrders($db) {
    $user = requireAuth($db);

    $symbol = strtoupper($_GET['symbol'] ?? '');
    $status = $_GET['status'] ?? '';
    $limit = intval($_GET['limit'] ?? 50);

    $sql = "SELECT * FROM orders WHERE user_id = ?";
    $params = [$user['id']];

    if (!empty($symbol)) {
        $sql .= " AND symbol = ?";
        $params[] = $symbol;
    }

    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    successResponse(['orders' => $orders]);
}

function handleGetOpenOrders($db) {
    $user = requireAuth($db);

    $symbol = strtoupper($_GET['symbol'] ?? '');

    $sql = "SELECT * FROM orders WHERE user_id = ? AND status IN ('pending', 'open', 'partially_filled')";
    $params = [$user['id']];

    if (!empty($symbol)) {
        $sql .= " AND symbol = ?";
        $params[] = $symbol;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    successResponse(['orders' => $orders, 'count' => count($orders)]);
}

// ========================================
// 포지션 조회
// ========================================
function handleGetPositions($db) {
    $user = requireAuth($db);

    $symbol = strtoupper($_GET['symbol'] ?? '');

    // 교차 마진 청산가 재계산 (최신 잔고/PNL 기준)
    recalculateCrossLiquidationPrices($db, $user['id']);

    $sql = "SELECT * FROM positions WHERE user_id = ? AND status = 'open'";
    $params = [$user['id']];

    if (!empty($symbol)) {
        $sql .= " AND symbol = ?";
        $params[] = $symbol;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $positions = $stmt->fetchAll();

    // 현재가로 미실현 손익 계산
    foreach ($positions as &$pos) {
        $currentPrice = fetchBinancePrice($pos['symbol']);
        if ($currentPrice > 0) {
            $pos['mark_price'] = $currentPrice;
            if ($pos['side'] === 'long') {
                $pos['unrealized_pnl'] = ($currentPrice - $pos['entry_price']) * $pos['size'];
            } else {
                $pos['unrealized_pnl'] = ($pos['entry_price'] - $currentPrice) * $pos['size'];
            }
            $pos['roe'] = $pos['margin'] > 0 ? ($pos['unrealized_pnl'] / $pos['margin']) * 100 : 0;
        }
    }

    successResponse(['positions' => $positions, 'count' => count($positions)]);
}

// ========================================
// 포지션 청산
// ========================================
function handleClosePosition($db) {
    $user = requireAuth($db);
    $input = json_decode(file_get_contents('php://input'), true);

    $positionId = intval($input['position_id'] ?? 0);
    $quantity = floatval($input['quantity'] ?? 0); // 0이면 전체 청산
    $marketPrice = floatval($input['market_price'] ?? 0); // WebSocket 실시간 가격

    $stmt = $db->prepare("SELECT * FROM positions WHERE id = ? AND user_id = ? AND status = 'open'");
    $stmt->execute([$positionId, $user['id']]);
    $position = $stmt->fetch();

    if (!$position) {
        errorResponse('청산할 포지션이 없습니다.');
    }

    $closeQty = ($quantity <= 0 || $quantity >= $position['size']) ? $position['size'] : $quantity;

    // 클라이언트 WebSocket 가격 우선 사용
    if ($marketPrice > 0) {
        $currentPrice = $marketPrice;
    } else {
        // fallback: API 조회
        $currentPrice = fetchBinancePrice($position['symbol']);
        if ($currentPrice <= 0) {
            errorResponse('현재가 조회에 실패했습니다.');
        }
    }

    // 청산 방향 결정
    $side = ($position['side'] === 'long') ? 'sell' : 'buy';
    $symbol = $position['symbol'];
    $positionSide = $position['side'];
    $leverage = $position['leverage'];

    // 필요 수수료 계산
    $notionalValue = $closeQty * $currentPrice;
    $fee = $notionalValue * getTakerFee();

    try {
        $db->beginTransaction();

        // 주문 ID 생성
        $orderId = generateOrderId();

        // 주문 생성 (체결된 상태로)
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, order_id, symbol, side, position_side, order_type, price, quantity, filled_quantity, avg_fill_price, leverage, reduce_only, status)
            VALUES (?, ?, ?, ?, ?, 'market', ?, ?, ?, ?, ?, 1, 'filled')
        ");
        $stmt->execute([
            $user['id'], $orderId, $symbol, $side, $positionSide, $currentPrice, $closeQty, $closeQty, $currentPrice, $leverage
        ]);
        $orderDbId = $db->lastInsertId();

        // PNL 계산
        if ($positionSide === 'long') {
            $pnl = ($currentPrice - $position['entry_price']) * $closeQty;
        } else {
            $pnl = ($position['entry_price'] - $currentPrice) * $closeQty;
        }

        // 수수료 차감
        $pnl -= $fee;

        $remainingSize = $position['size'] - $closeQty;

        if ($remainingSize <= 0) {
            // 포지션 완전 청산
            $stmt = $db->prepare("UPDATE positions SET status = 'closed', size = 0, realized_pnl = realized_pnl + ?, closed_at = NOW() WHERE id = ?");
            $stmt->execute([$pnl, $position['id']]);

            // 마진 반환 + PNL
            $closedMargin = $position['margin'];
            $stmt = $db->prepare("UPDATE wallets SET available_balance = available_balance + ? + ?, frozen_balance = frozen_balance - ? WHERE user_id = ? AND currency = 'USDT'");
            $stmt->execute([$closedMargin, $pnl, $closedMargin, $user['id']]);
        } else {
            // 부분 청산
            $partialMargin = $position['margin'] * ($closeQty / $position['size']);

            $stmt = $db->prepare("UPDATE positions SET size = ?, margin = margin - ?, realized_pnl = realized_pnl + ? WHERE id = ?");
            $stmt->execute([$remainingSize, $partialMargin, $pnl, $position['id']]);

            // 부분 마진 반환 + PNL
            $stmt = $db->prepare("UPDATE wallets SET available_balance = available_balance + ? + ?, frozen_balance = frozen_balance - ? WHERE user_id = ? AND currency = 'USDT'");
            $stmt->execute([$partialMargin, $pnl, $partialMargin, $user['id']]);
        }

        // 트랜잭션 기록 (PNL)
        if ($pnl != 0) {
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, currency, amount, reference_id, reference_type, memo) VALUES (?, 'pnl', 'USDT', ?, ?, 'position', ?)");
            $stmt->execute([$user['id'], $pnl, $orderId, ($pnl > 0 ? '포지션 수익' : '포지션 손실')]);
        }

        // 거래 기록
        $stmt = $db->prepare("
            INSERT INTO trades (user_id, order_id, position_id, symbol, side, position_side, price, quantity, quote_quantity, fee, realized_pnl)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'], $orderId, $position['id'], $symbol, $side, $positionSide, $currentPrice, $closeQty, $notionalValue, $fee, $pnl
        ]);

        $db->commit();

        // 교차 마진 포지션 청산가 재계산 (포지션 종료 후 다른 포지션들의 청산가도 변경됨)
        recalculateCrossLiquidationPrices($db, $user['id']);

        // WebSocket 브로드캐스트 - 포지션 청산
        broadcastToWS('position:closed', [
            'user_id' => $user['id'],
            'nickname' => $user['nickname'],
            'position_id' => $position['id'],
            'symbol' => $symbol,
            'side' => $positionSide,
            'close_price' => $currentPrice,
            'quantity' => $closeQty,
            'realized_pnl' => $pnl,
            'remaining_size' => $remainingSize
        ]);

        // WebSocket으로 계정 정보 푸시
        broadcastAccountUpdateSafe($db, $user['id']);

        successResponse([
            'position_id' => $position['id'],
            'closed_quantity' => $closeQty,
            'close_price' => $currentPrice,
            'realized_pnl' => $pnl,
            'fee' => $fee,
            'remaining_size' => $remainingSize
        ], '포지션이 청산되었습니다.');

    } catch (Exception $e) {
        $db->rollBack();
        errorResponse('포지션 청산 중 오류가 발생했습니다: ' . $e->getMessage());
    }
}

// ========================================
// 레버리지 설정
// ========================================
function handleSetLeverage($db) {
    $user = requireAuth($db);
    $input = json_decode(file_get_contents('php://input'), true);

    $symbol = strtoupper(sanitize($input['symbol'] ?? 'BTCUSDT'));
    $leverage = intval($input['leverage'] ?? DEFAULT_LEVERAGE);

    if ($leverage < 1 || $leverage > MAX_LEVERAGE) {
        errorResponse('레버리지는 1~' . MAX_LEVERAGE . '배 사이어야 합니다.');
    }

    // 열린 포지션이 있으면 변경 불가
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM positions WHERE user_id = ? AND symbol = ? AND status = 'open'");
    $stmt->execute([$user['id'], $symbol]);
    $openCount = $stmt->fetch()['count'];

    if ($openCount > 0) {
        errorResponse('열린 포지션이 있어 레버리지를 변경할 수 없습니다.');
    }

    // 설정 저장/업데이트
    $stmt = $db->prepare("
        INSERT INTO leverage_settings (user_id, symbol, leverage) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE leverage = ?
    ");
    $stmt->execute([$user['id'], $symbol, $leverage, $leverage]);

    successResponse(['symbol' => $symbol, 'leverage' => $leverage], '레버리지가 ' . $leverage . '배로 설정되었습니다.');
}

// ========================================
// TP/SL 설정
// ========================================
function handleSetTPSL($db) {
    $user = requireAuth($db);
    $input = json_decode(file_get_contents('php://input'), true);

    $positionId = intval($input['position_id'] ?? 0);
    $tpPrice = floatval($input['tp_price'] ?? 0);
    $slPrice = floatval($input['sl_price'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM positions WHERE id = ? AND user_id = ? AND status = 'open'");
    $stmt->execute([$positionId, $user['id']]);
    $position = $stmt->fetch();

    if (!$position) {
        errorResponse('포지션을 찾을 수 없습니다.');
    }

    // TP/SL 유효성 검사
    if ($position['side'] === 'long') {
        if ($tpPrice > 0 && $tpPrice <= $position['entry_price']) {
            errorResponse('롱 포지션의 익절가는 진입가보다 높아야 합니다.');
        }
        if ($slPrice > 0 && $slPrice >= $position['entry_price']) {
            errorResponse('롱 포지션의 손절가는 진입가보다 낮아야 합니다.');
        }
    } else {
        if ($tpPrice > 0 && $tpPrice >= $position['entry_price']) {
            errorResponse('숏 포지션의 익절가는 진입가보다 낮아야 합니다.');
        }
        if ($slPrice > 0 && $slPrice <= $position['entry_price']) {
            errorResponse('숏 포지션의 손절가는 진입가보다 높아야 합니다.');
        }
    }

    $stmt = $db->prepare("UPDATE positions SET tp_price = ?, sl_price = ? WHERE id = ?");
    $stmt->execute([$tpPrice ?: null, $slPrice ?: null, $positionId]);

    successResponse([
        'position_id' => $positionId,
        'tp_price' => $tpPrice,
        'sl_price' => $slPrice
    ], 'TP/SL이 설정되었습니다.');
}

// ========================================
// 거래 내역 조회
// ========================================
function handleGetTrades($db) {
    $user = requireAuth($db);

    $symbol = strtoupper($_GET['symbol'] ?? '');
    $limit = intval($_GET['limit'] ?? 50);

    $sql = "SELECT * FROM trades WHERE user_id = ?";
    $params = [$user['id']];

    if (!empty($symbol)) {
        $sql .= " AND symbol = ?";
        $params[] = $symbol;
    }

    $sql .= " ORDER BY executed_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $trades = $stmt->fetchAll();

    successResponse(['trades' => $trades, 'count' => count($trades)]);
}

// ========================================
// 주문 내역 (전체 - 체결/취소 포함)
// ========================================
function handleGetOrderHistory($db) {
    $user = requireAuth($db);

    $symbol = strtoupper($_GET['symbol'] ?? '');
    $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));

    $where = "user_id = ?";
    $params = [$user['id']];

    if (!empty($symbol)) {
        $where .= " AND symbol = ?";
        $params[] = $symbol;
    }

    // 전체 개수 조회
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE $where");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();

    // 페이징된 데이터 조회
    $sql = "SELECT * FROM orders WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    successResponse([
        'orders' => $orders,
        'total' => intval($totalCount),
        'limit' => $limit,
        'offset' => $offset
    ]);
}

// ========================================
// 거래 내역 (체결 내역)
// ========================================
function handleGetTradeHistory($db) {
    $user = requireAuth($db);

    $symbol = strtoupper($_GET['symbol'] ?? '');
    $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));

    $where = "user_id = ?";
    $params = [$user['id']];

    if (!empty($symbol)) {
        $where .= " AND symbol = ?";
        $params[] = $symbol;
    }

    // 전체 개수 조회
    $stmt = $db->prepare("SELECT COUNT(*) FROM trades WHERE $where");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();

    // 페이징된 데이터 조회
    $sql = "SELECT * FROM trades WHERE $where ORDER BY executed_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $trades = $stmt->fetchAll();

    successResponse([
        'trades' => $trades,
        'total' => intval($totalCount),
        'limit' => $limit,
        'offset' => $offset
    ]);
}

// ========================================
// 잔고 조회 (바이낸스 스타일)
// ========================================
function handleGetBalance($db) {
    $user = requireAuth($db);

    // 1. 지갑 잔고 조회
    $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $wallets = $stmt->fetchAll();

    $walletBalance = 0;
    foreach ($wallets as $wallet) {
        if ($wallet['currency'] === 'USDT') {
            $walletBalance = floatval($wallet['balance']);
            break;
        }
    }

    // 2. 오픈 포지션 조회 (개별 포지션별로 MMR 계산 필요)
    $stmt = $db->prepare("
        SELECT id, symbol, side, size, entry_price, margin, leverage, unrealized_pnl
        FROM positions
        WHERE user_id = ? AND status = 'open'
    ");
    $stmt->execute([$user['id']]);
    $positions = $stmt->fetchAll();

    $positionIM = 0;  // Position Initial Margin
    $positionMM = 0;  // Position Maintenance Margin
    $totalPnl = 0;

    foreach ($positions as $pos) {
        $positionIM += floatval($pos['margin']);
        $totalPnl += floatval($pos['unrealized_pnl']);

        // Notional Value = Size × Entry Price
        $notional = floatval($pos['size']) * floatval($pos['entry_price']);

        // MMR 티어에서 해당 심볼/노셔널에 맞는 MMR 가져오기
        $mmr = getMMRForPosition($db, $pos['symbol'], $notional);
        $positionMM += $notional * $mmr;
    }

    // 3. 미체결 주문의 Initial Margin 계산 (Order IM)
    $stmt = $db->prepare("
        SELECT symbol, side, order_type, price, quantity, leverage
        FROM orders
        WHERE user_id = ? AND status IN ('pending', 'open', 'partially_filled')
    ");
    $stmt->execute([$user['id']]);
    $openOrders = $stmt->fetchAll();

    $orderIM = 0;
    foreach ($openOrders as $order) {
        $orderPrice = floatval($order['price']);
        $orderQty = floatval($order['quantity']);
        $leverage = intval($order['leverage']) ?: 10;

        // Market order는 price가 0일 수 있음 - 현재가 기준으로 계산해야 하지만
        // 여기서는 간단히 limit order만 계산 (market은 즉시 체결되므로)
        if ($orderPrice > 0) {
            $orderNotional = $orderPrice * $orderQty;
            $orderIM += $orderNotional / $leverage;
        }
    }

    // 4. 바이낸스 스타일 계산
    // Total IM = Position IM + Order IM
    $totalIM = $positionIM + $orderIM;

    // Equity (Margin Balance) = Wallet Balance + Unrealized PNL
    $marginBalance = $walletBalance + $totalPnl;

    // Available Balance = Equity - Total IM
    $availableBalance = $marginBalance - $totalIM;

    // IMR% = Total IM / Equity × 100
    $imRatio = ($marginBalance > 0) ? ($totalIM / $marginBalance) * 100 : 0;

    // MMR% = MM / Equity × 100
    $mmRatio = ($marginBalance > 0) ? ($positionMM / $marginBalance) * 100 : 0;

    $result = [
        'wallets' => $wallets,
        'wallet_balance' => $walletBalance,
        'unrealized_pnl' => $totalPnl,
        'margin_balance' => $marginBalance,
        'available_balance' => $availableBalance,
        'total_equity' => $marginBalance,

        // Initial Margin (상세)
        'position_margin' => $positionIM,   // 포지션 IM
        'order_margin' => $orderIM,         // 미체결 주문 IM
        'total_im' => $totalIM,             // 총 IM

        // Maintenance Margin
        'maintenance_margin' => $positionMM,  // MMR 티어 기반 계산

        // 비율 (%)
        'im_ratio' => round($imRatio, 2),
        'mm_ratio' => round($mmRatio, 2),

        // 오픈 주문 수
        'open_orders_count' => count($openOrders)
    ];

    successResponse($result);
}

/**
 * MMR 티어에서 해당 심볼/노셔널에 맞는 MMR 가져오기
 */
function getMMRForPosition($db, $symbol, $notional) {
    // 해당 심볼의 티어 조회
    $stmt = $db->prepare("
        SELECT mmr FROM mmr_tiers
        WHERE symbol = ? AND ? >= notional_min AND ? < notional_max
        LIMIT 1
    ");
    $stmt->execute([$symbol, $notional, $notional]);
    $tier = $stmt->fetch();

    if ($tier) {
        return floatval($tier['mmr']);
    }

    // 심볼별 티어가 없으면 DEFAULT 티어 사용
    $stmt = $db->prepare("
        SELECT mmr FROM mmr_tiers
        WHERE symbol = 'DEFAULT' AND ? >= notional_min AND ? < notional_max
        LIMIT 1
    ");
    $stmt->execute([$notional, $notional]);
    $tier = $stmt->fetch();

    if ($tier) {
        return floatval($tier['mmr']);
    }

    // 그래도 없으면 기본값 0.5% 반환
    return 0.005;
}

// ========================================
// P&L 히스토리 (종료된 포지션)
// ========================================
function handleGetPnlHistory($db) {
    $user = requireAuth($db);

    $symbol = $_GET['symbol'] ?? '';
    $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
    $offset = max(0, intval($_GET['offset'] ?? 0));

    $where = "user_id = ? AND status IN ('closed', 'liquidated')";
    $params = [$user['id']];

    if ($symbol) {
        $where .= " AND symbol = ?";
        $params[] = strtoupper($symbol);
    }

    // 종료된 포지션 조회
    $stmt = $db->prepare("
        SELECT
            id,
            symbol,
            side,
            entry_price,
            exit_price,
            size,
            margin,
            leverage,
            realized_pnl,
            status,
            opened_at,
            closed_at,
            TIMESTAMPDIFF(SECOND, opened_at, closed_at) as duration_seconds
        FROM positions
        WHERE $where
        ORDER BY closed_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 연관된 거래(trades)에서 수수료 합계 및 마지막 거래 가격 조회
    foreach ($positions as &$pos) {
        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(fee), 0) as total_fee,
                COALESCE(SUM(quote_quantity), 0) as total_volume,
                (SELECT price FROM trades WHERE position_id = ? ORDER BY created_at DESC LIMIT 1) as last_trade_price
            FROM trades
            WHERE position_id = ?
        ");
        $stmt->execute([$pos['id'], $pos['id']]);
        $tradeData = $stmt->fetch(PDO::FETCH_ASSOC);

        $pos['total_fee'] = floatval($tradeData['total_fee']);
        $pos['total_volume'] = floatval($tradeData['total_volume']);
        $pos['entry_price'] = floatval($pos['entry_price']);

        // exit_price가 NULL이면 마지막 거래 가격 또는 entry_price 사용
        $exitPrice = $pos['exit_price'] ?? $tradeData['last_trade_price'] ?? $pos['entry_price'];
        $pos['exit_price'] = floatval($exitPrice);

        $pos['size'] = floatval($pos['size']);
        $pos['margin'] = floatval($pos['margin']);
        $pos['realized_pnl'] = floatval($pos['realized_pnl']);
        $pos['leverage'] = intval($pos['leverage']);

        // ROE% 계산: (PNL / 마진) * 100
        $pos['roe_percent'] = $pos['margin'] > 0
            ? round(($pos['realized_pnl'] / $pos['margin']) * 100, 2)
            : 0;

        // Duration 포맷
        $pos['duration'] = formatDuration($pos['duration_seconds']);
    }

    // 전체 개수
    $stmt = $db->prepare("SELECT COUNT(*) FROM positions WHERE $where");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();

    successResponse([
        'positions' => $positions,
        'total' => intval($totalCount),
        'limit' => $limit,
        'offset' => $offset
    ]);
}

// 시간 포맷 헬퍼
function formatDuration($seconds) {
    if (!$seconds || $seconds < 0) return '-';

    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($days > 0) {
        return "{$days}D {$hours}H";
    } elseif ($hours > 0) {
        return "{$hours}H {$minutes}M";
    } elseif ($minutes > 0) {
        return "{$minutes}M {$secs}S";
    } else {
        return "{$secs}S";
    }
}

// ========================================
// P&L 통계 (기간별 손익)
// ========================================
function handleGetPnlStats($db) {
    $user = requireAuth($db);

    $userId = $user['id'];

    // 오늘 PNL
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(realized_pnl), 0) as pnl
        FROM positions
        WHERE user_id = ? AND status IN ('closed', 'liquidated') AND DATE(closed_at) = CURDATE()
    ");
    $stmt->execute([$userId]);
    $todayPnl = floatval($stmt->fetchColumn());

    // 7일 PNL
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(realized_pnl), 0) as pnl
        FROM positions
        WHERE user_id = ? AND status IN ('closed', 'liquidated') AND closed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId]);
    $weekPnl = floatval($stmt->fetchColumn());

    // 30일 PNL
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(realized_pnl), 0) as pnl
        FROM positions
        WHERE user_id = ? AND status IN ('closed', 'liquidated') AND closed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$userId]);
    $monthPnl = floatval($stmt->fetchColumn());

    // 전체 PNL
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(realized_pnl), 0) as pnl
        FROM positions
        WHERE user_id = ? AND status IN ('closed', 'liquidated')
    ");
    $stmt->execute([$userId]);
    $totalPnl = floatval($stmt->fetchColumn());

    // 전체 수수료
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(fee), 0) as total_fee
        FROM trades
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $totalFee = floatval($stmt->fetchColumn());

    // 거래 통계
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_trades,
            SUM(CASE WHEN realized_pnl > 0 THEN 1 ELSE 0 END) as win_count,
            SUM(CASE WHEN realized_pnl <= 0 THEN 1 ELSE 0 END) as loss_count
        FROM positions
        WHERE user_id = ? AND status IN ('closed', 'liquidated')
    ");
    $stmt->execute([$userId]);
    $tradeStats = $stmt->fetch(PDO::FETCH_ASSOC);

    $winRate = $tradeStats['total_trades'] > 0
        ? round(($tradeStats['win_count'] / $tradeStats['total_trades']) * 100, 1)
        : 0;

    successResponse([
        'today' => $todayPnl,
        'week' => $weekPnl,
        'month' => $monthPnl,
        'total' => $totalPnl,
        'total_fee' => $totalFee,
        'total_trades' => intval($tradeStats['total_trades']),
        'win_count' => intval($tradeStats['win_count']),
        'loss_count' => intval($tradeStats['loss_count']),
        'win_rate' => $winRate
    ]);
}
