<?php
/**
 * FuturX Admin - 인증 시스템
 * 관리자 로그인, 세션, 권한 관리
 */

session_start();

require_once dirname(__DIR__) . '/config.php';

// 관리자 권한 레벨
define('ROLE_USER', 0);
define('ROLE_ADMIN', 1);
define('ROLE_SUPERADMIN', 2);

$roleMap = [
    'user' => ROLE_USER,
    'vip' => ROLE_USER,
    'admin' => ROLE_ADMIN,
    'superadmin' => ROLE_SUPERADMIN
];

/**
 * 로그인 확인
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']);
}

/**
 * 관리자 권한 확인
 */
function isAdmin() {
    global $roleMap;
    if (!isLoggedIn()) return false;
    return $roleMap[$_SESSION['admin_role']] >= ROLE_ADMIN;
}

/**
 * 슈퍼관리자 권한 확인
 */
function isSuperAdmin() {
    global $roleMap;
    if (!isLoggedIn()) return false;
    return $roleMap[$_SESSION['admin_role']] >= ROLE_SUPERADMIN;
}

/**
 * 권한 요구
 */
function requireAdmin() {
    if (!isAdmin()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
}

/**
 * 슈퍼관리자 권한 요구
 */
function requireSuperAdmin() {
    if (!isSuperAdmin()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '슈퍼관리자 권한이 필요합니다.']);
            exit;
        }
        header('Location: index.php?error=permission');
        exit;
    }
}

/**
 * 로그인 처리
 */
function adminLogin($email, $password) {
    global $roleMap;

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role IN ('admin', 'superadmin')");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => '관리자 계정을 찾을 수 없습니다.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => '계정이 비활성화되었습니다.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => '비밀번호가 올바르지 않습니다.'];
        }

        // 세션 설정
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_nickname'] = $user['nickname'] ?: $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_avatar'] = $user['avatar'];

        // 로그인 시간 업데이트
        $updateStmt = $db->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id");
        $updateStmt->execute([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'id' => $user['id']
        ]);

        // 관리자 로그 기록
        logAdminAction('login', 'users', $user['id'], ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        return ['success' => true, 'message' => '로그인 성공'];

    } catch (PDOException $e) {
        return ['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.'];
    }
}

/**
 * 로그아웃 처리
 */
function adminLogout() {
    if (isset($_SESSION['admin_id'])) {
        logAdminAction('logout', 'users', $_SESSION['admin_id']);
    }

    session_unset();
    session_destroy();
}

/**
 * 관리자 활동 로그
 */
function logAdminAction($action, $targetType = null, $targetId = null, $details = null) {
    if (!isLoggedIn()) return;

    try {
        $db = getDB();

        $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address, user_agent)
                              VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip, :ua)");
        $stmt->execute([
            'admin_id' => $_SESSION['admin_id'],
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details ? json_encode($details) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);

    } catch (PDOException $e) {
        // 로그 실패는 무시
    }
}

/**
 * 현재 관리자 정보
 */
function getCurrentAdmin() {
    if (!isLoggedIn()) return null;

    return [
        'id' => $_SESSION['admin_id'],
        'email' => $_SESSION['admin_email'],
        'username' => $_SESSION['admin_username'],
        'nickname' => $_SESSION['admin_nickname'],
        'role' => $_SESSION['admin_role'],
        'avatar' => $_SESSION['admin_avatar']
    ];
}

/**
 * CSRF 토큰 생성
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 검증
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * JSON 응답
 */
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}
