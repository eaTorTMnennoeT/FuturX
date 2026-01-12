<?php
/**
 * 로그인 API
 * POST /api/login.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// POST만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$remember = !empty($input['remember']);

// 유효성 검증
if (empty($email)) {
    errorResponse('이메일을 입력해주세요');
}

if (empty($password)) {
    errorResponse('비밀번호를 입력해주세요');
}

try {
    $db = getDB();

    // 사용자 조회
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        writeLog('warning', 'Login failed - user not found', ['email' => $email]);
        errorResponse('이메일 또는 비밀번호가 올바르지 않습니다');
    }

    // 비밀번호 확인
    if (!password_verify($password, $user['password'])) {
        writeLog('warning', 'Login failed - invalid password', ['email' => $email]);
        errorResponse('이메일 또는 비밀번호가 올바르지 않습니다');
    }

    // 세션 토큰 생성
    $token = bin2hex(random_bytes(32));
    $expiresAt = $remember
        ? date('Y-m-d H:i:s', strtotime('+30 days'))
        : date('Y-m-d H:i:s', strtotime('+24 hours'));

    // 기존 세션 삭제 (선택적)
    $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()");
    $stmt->execute([$user['id']]);

    // 새 세션 저장
    $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $user['id'],
        $token,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $expiresAt
    ]);

    // 마지막 로그인 업데이트
    $stmt = $db->prepare("UPDATE users SET last_login = NOW(), last_login_ip = ? WHERE id = ?");
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $user['id']]);

    // 세션 설정
    $_SESSION['user_id'] = $user['id'];

    // 쿠키 설정
    $cookieExpiry = $remember ? time() + (30 * 24 * 60 * 60) : 0;
    setcookie('auth_token', $token, $cookieExpiry, '/', '', false, true);

    writeLog('info', 'User logged in', ['user_id' => $user['id'], 'email' => $email]);

    // 잔액 가져오기
    $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'USDT'");
    $stmt->execute([$user['id']]);
    $balanceRow = $stmt->fetch();
    $balance = $balanceRow ? floatval($balanceRow['balance']) : 0;

    successResponse([
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'nickname' => $user['nickname'],
            'avatar' => $user['avatar'],
            'kyc_level' => $user['kyc_level'],
            'vip_level' => $user['vip_level']
        ],
        'balance' => $balance,
        'token' => $token,
        'requires_2fa' => false // 2FA 비활성화 (데모용)
    ], '로그인 성공');

} catch (Exception $e) {
    writeLog('error', 'Login error', ['error' => $e->getMessage()]);
    // 개발 모드에서 상세 오류 표시
    errorResponse('로그인 처리 중 오류가 발생했습니다: ' . $e->getMessage(), 500);
}
