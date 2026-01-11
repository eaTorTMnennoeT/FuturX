<?php
/**
 * Database & App Configuration
 */

// 에러 표시 (개발용)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 타임존
date_default_timezone_set('Asia/Seoul');

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123123');
define('DB_NAME', 'trade');
define('DB_CHARSET', 'utf8mb4');

// JWT 설정
define('JWT_SECRET', 'your-super-secret-key-change-in-production-2024');
define('JWT_EXPIRY', 86400 * 7); // 7일

// 거래 설정
define('MIN_TRADE_AMOUNT', 0.001);
define('MAX_LEVERAGE', 125);
define('DEFAULT_LEVERAGE', 10);
define('TAKER_FEE', 0.0006); // 0.06%
define('MAKER_FEE', 0.0002); // 0.02%
define('FUNDING_RATE', 0.0001); // 0.01%

// 지원 심볼
define('SUPPORTED_SYMBOLS', [
    'BTCUSDT' => ['base' => 'BTC', 'quote' => 'USDT', 'tick_size' => 0.1, 'min_qty' => 0.001],
    'ETHUSDT' => ['base' => 'ETH', 'quote' => 'USDT', 'tick_size' => 0.01, 'min_qty' => 0.01],
    'SOLUSDT' => ['base' => 'SOL', 'quote' => 'USDT', 'tick_size' => 0.01, 'min_qty' => 0.1],
    'XRPUSDT' => ['base' => 'XRP', 'quote' => 'USDT', 'tick_size' => 0.0001, 'min_qty' => 1],
    'DOGEUSDT' => ['base' => 'DOGE', 'quote' => 'USDT', 'tick_size' => 0.00001, 'min_qty' => 10],
]);

// PDO 데이터베이스 연결
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }

    return $pdo;
}

// JSON 응답 헬퍼
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// 에러 응답
if (!function_exists('errorResponse')) {
    function errorResponse($message, $code = 400) {
        jsonResponse(['success' => false, 'error' => $message], $code);
    }
}

// 성공 응답
if (!function_exists('successResponse')) {
    function successResponse($data = [], $message = 'Success') {
        jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
    }
}

// 입력값 검증
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// UUID 생성
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// 주문 ID 생성
function generateOrderId() {
    return 'ORD' . date('YmdHis') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// API 키 생성
function generateApiKey() {
    return bin2hex(random_bytes(32));
}

// 세션 토큰 생성
function generateSessionToken() {
    return bin2hex(random_bytes(64));
}
