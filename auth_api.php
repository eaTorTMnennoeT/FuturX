<?php
/**
 * Authentication API
 * 로그인, 회원가입, 로그아웃, 세션 검증
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'login':
        handleLogin($db);
        break;

    case 'register':
        handleRegister($db);
        break;

    case 'logout':
        handleLogout($db);
        break;

    case 'check':
        handleCheckSession($db);
        break;

    case 'me':
        handleGetUser($db);
        break;

    default:
        errorResponse('Unknown action', 400);
}

// ========================================
// 로그인
// ========================================
function handleLogin($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $email = sanitize($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        errorResponse('이메일과 비밀번호를 입력해주세요.');
    }

    // 사용자 조회
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        errorResponse('이메일 또는 비밀번호가 올바르지 않습니다.');
    }

    // 비밀번호 검증
    if (!password_verify($password, $user['password'])) {
        errorResponse('이메일 또는 비밀번호가 올바르지 않습니다.');
    }

    // 세션 토큰 생성
    $sessionToken = generateSessionToken();
    $expiresAt = date('Y-m-d H:i:s', time() + JWT_EXPIRY);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // 기존 세션 삭제 (선택적)
    $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
    $stmt->execute([$user['id']]);

    // 새 세션 저장
    $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user['id'], $sessionToken, $ip, $userAgent, $expiresAt]);

    // 마지막 로그인 업데이트
    $stmt = $db->prepare("UPDATE users SET last_login = NOW(), login_ip = ? WHERE id = ?");
    $stmt->execute([$ip, $user['id']]);

    // 지갑 정보 조회
    $stmt = $db->prepare("SELECT currency, balance, available_balance FROM wallets WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $wallets = $stmt->fetchAll();

    // 비밀번호 제외하고 반환
    unset($user['password']);

    successResponse([
        'user' => $user,
        'wallets' => $wallets,
        'token' => $sessionToken,
        'expires_at' => $expiresAt
    ], '로그인 성공');
}

// ========================================
// 회원가입
// ========================================
function handleRegister($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $email = sanitize($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    $nickname = sanitize($input['nickname'] ?? '');
    $referralCode = sanitize($input['referral_code'] ?? '');

    // 유효성 검사
    if (empty($email) || empty($password) || empty($nickname)) {
        errorResponse('필수 항목을 모두 입력해주세요.');
    }

    // 이메일 형식 검증 제거 - 아이디로 사용 가능
    // if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //     errorResponse('올바른 이메일 형식이 아닙니다.');
    // }

    // 비밀번호 길이 제한 완화
    if (strlen($password) < 1) {
        errorResponse('비밀번호를 입력해주세요.');
    }

    if ($password !== $confirmPassword) {
        errorResponse('비밀번호가 일치하지 않습니다.');
    }

    if (strlen($nickname) < 1 || strlen($nickname) > 20) {
        errorResponse('닉네임은 1~20자여야 합니다.');
    }

    // 이메일 중복 확인
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        errorResponse('이미 사용 중인 이메일입니다.');
    }

    // 닉네임 중복 확인
    $stmt = $db->prepare("SELECT id FROM users WHERE nickname = ?");
    $stmt->execute([$nickname]);
    if ($stmt->fetch()) {
        errorResponse('이미 사용 중인 닉네임입니다.');
    }

    // 추천인 확인
    $referredBy = null;
    if (!empty($referralCode)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->execute([$referralCode]);
        $referrer = $stmt->fetch();
        if ($referrer) {
            $referredBy = $referrer['id'];
        }
    }

    // 비밀번호 해시
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 고유 추천코드 생성
    $myReferralCode = strtoupper(substr(md5($email . time()), 0, 8));

    // 사용자 생성
    $stmt = $db->prepare("INSERT INTO users (email, password, nickname, referral_code, referred_by, is_active, is_verified) VALUES (?, ?, ?, ?, ?, 1, 0)");
    $stmt->execute([$email, $hashedPassword, $nickname, $myReferralCode, $referredBy]);
    $userId = $db->lastInsertId();

    // 기본 지갑 생성 (USDT - 테스트용 1000 USDT 지급)
    $stmt = $db->prepare("INSERT INTO wallets (user_id, currency, balance, available_balance, total_deposit) VALUES (?, 'USDT', 1000, 1000, 1000)");
    $stmt->execute([$userId]);

    // 보너스 트랜잭션 기록
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, currency, amount, balance_after, memo) VALUES (?, 'bonus', 'USDT', 1000, 1000, '신규 가입 보너스')");
    $stmt->execute([$userId]);

    successResponse([
        'user_id' => $userId,
        'email' => $email,
        'nickname' => $nickname,
        'referral_code' => $myReferralCode,
        'bonus' => 1000
    ], '회원가입이 완료되었습니다. 테스트용 1000 USDT가 지급되었습니다.');
}

// ========================================
// 로그아웃
// ========================================
function handleLogout($db) {
    $token = getBearerToken();

    if (!$token) {
        errorResponse('인증 토큰이 필요합니다.', 401);
    }

    $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
    $stmt->execute([$token]);

    successResponse([], '로그아웃되었습니다.');
}

// ========================================
// 세션 확인
// ========================================
function handleCheckSession($db) {
    $token = getBearerToken();

    if (!$token) {
        jsonResponse(['success' => false, 'authenticated' => false]);
    }

    $stmt = $db->prepare("
        SELECT u.id, u.email, u.nickname, s.expires_at
        FROM user_sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1
    ");
    $stmt->execute([$token]);
    $session = $stmt->fetch();

    if (!$session) {
        jsonResponse(['success' => false, 'authenticated' => false]);
    }

    jsonResponse([
        'success' => true,
        'authenticated' => true,
        'user' => [
            'id' => $session['id'],
            'email' => $session['email'],
            'nickname' => $session['nickname']
        ]
    ]);
}

// ========================================
// 현재 사용자 정보
// ========================================
function handleGetUser($db) {
    $user = requireAuth($db);

    // 지갑 정보
    $stmt = $db->prepare("SELECT currency, balance, available_balance, frozen_balance FROM wallets WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $wallets = $stmt->fetchAll();

    // 오픈 포지션 수
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM positions WHERE user_id = ? AND status = 'open'");
    $stmt->execute([$user['id']]);
    $positionCount = $stmt->fetch()['count'];

    // 미체결 주문 수
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status IN ('pending', 'open', 'partially_filled')");
    $stmt->execute([$user['id']]);
    $orderCount = $stmt->fetch()['count'];

    unset($user['password']);

    successResponse([
        'user' => $user,
        'wallets' => $wallets,
        'open_positions' => $positionCount,
        'open_orders' => $orderCount
    ]);
}

// ========================================
// 헬퍼 함수
// ========================================
function getBearerToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }

    // 쿠키에서도 확인
    return $_COOKIE['auth_token'] ?? null;
}

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
        errorResponse('세션이 만료되었습니다. 다시 로그인해주세요.', 401);
    }

    return $user;
}
