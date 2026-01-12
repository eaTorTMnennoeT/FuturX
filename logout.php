<?php
/**
 * FuturX - 로그아웃
 * 세션 종료 및 쿠키 삭제
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 현재 사용자 ID 저장 (DB 세션 삭제용)
$userId = $_SESSION['user_id'] ?? null;
$authToken = $_COOKIE['auth_token'] ?? null;

// DB에서 세션 삭제
if ($authToken) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$authToken]);
    } catch (Exception $e) {
        // 에러 무시 (로그아웃은 진행)
        error_log("[LOGOUT] Failed to delete session from DB: " . $e->getMessage());
    }
}

// 세션 변수 모두 제거
$_SESSION = array();

// 세션 쿠키 삭제
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 세션 파괴
session_destroy();

// auth_token 쿠키 삭제
if (isset($_COOKIE['auth_token'])) {
    setcookie('auth_token', '', time() - 3600, '/');
    setcookie('auth_token', '', time() - 3600, '/', '', false, true);
}

// 로그 기록
if ($userId) {
    error_log("[LOGOUT] User ID: $userId logged out successfully");
}

// 로그인 페이지로 리다이렉트
header('Location: login.php');
exit;
