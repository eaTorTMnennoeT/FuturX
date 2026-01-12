<?php
/**
 * Admin Settings API
 * 설정값 업데이트 API
 */

require_once __DIR__ . '/../auth.php';
requireAdmin();

header('Content-Type: application/json');

// POST 요청만 허용
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

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['key']) || !isset($input['value'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$key = $input['key'];
$value = $input['value'];

// 허용된 설정 키 목록
$allowedKeys = [
    'ws_server_url',
    'ws_server_port',
    'site_name',
    'site_description',
    'maintenance_mode',
    'max_leverage',
    'taker_fee',
    'maker_fee',
    'min_withdraw',
    'max_withdraw_daily',
    'registration_enabled',
    'kyc_required'
];

if (!in_array($key, $allowedKeys)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid setting key']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE settings SET `value` = ? WHERE `key` = ?");
    $stmt->execute([$value, $key]);

    if ($stmt->rowCount() === 0) {
        // 키가 없으면 삽입
        $stmt = $db->prepare("INSERT INTO settings (`key`, `value`, `type`, `group`) VALUES (?, ?, 'string', 'general')");
        $stmt->execute([$key, $value]);
    }

    echo json_encode(['success' => true, 'message' => 'Setting updated']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
