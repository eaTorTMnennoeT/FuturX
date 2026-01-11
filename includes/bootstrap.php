<?php
/**
 * FuturX - Bootstrap (초기화)
 * 모든 페이지에서 가장 먼저 include
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 에러 리포팅 (개발 환경)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 타임존
date_default_timezone_set('Asia/Seoul');

// DB 설정
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123123');
define('DB_NAME', 'trade');

// PDO 연결
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("[DB_ERROR] Connection failed: " . $e->getMessage());
            die('Database connection failed');
        }
    }

    return $pdo;
}

// CSRF 토큰 생성
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF 토큰 검증
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// XSS 방지 출력
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// JSON 응답
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 에러 응답
function errorResponse($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

// 성공 응답
function successResponse($data = [], $message = 'Success') {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

// 로그인 체크
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// 로그인 필수 페이지용
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// 현재 사용자 정보
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    static $user = null;

    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }

    return $user;
}

// Request ID 생성 (로깅용)
function getRequestId() {
    static $requestId = null;
    if ($requestId === null) {
        $requestId = substr(md5(uniqid(mt_rand(), true)), 0, 12);
    }
    return $requestId;
}

// 로그 기록
function writeLog($level, $message, $context = []) {
    $logEntry = sprintf(
        "[%s] [%s] [%s] %s | %s",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        getRequestId(),
        $message,
        json_encode($context, JSON_UNESCAPED_UNICODE)
    );
    error_log($logEntry);
}
