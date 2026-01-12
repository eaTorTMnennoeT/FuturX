<?php
/**
 * Database & App Configuration
 * 모든 설정은 DB 기반으로 관리
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

// 데이터베이스 설정 (이것만 하드코딩)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123123');
define('DB_NAME', 'trade');
define('DB_CHARSET', 'utf8mb4');

// JWT 설정
define('JWT_SECRET', 'your-super-secret-key-change-in-production-2024');
define('JWT_EXPIRY', 86400 * 7); // 7일

// ========================================
// PDO 데이터베이스 연결
// ========================================
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

// ========================================
// DB 기반 설정 시스템
// ========================================

/**
 * DB에서 설정값 가져오기 (캐싱)
 */
function getSetting($key, $default = null) {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT `value`, `type` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) return $default;

        $value = $row['value'];
        switch ($row['type']) {
            case 'number':
                $value = is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : $value;
                break;
            case 'boolean':
                $value = ($value === 'true' || $value === '1');
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
        }
        $cache[$key] = $value;
        return $value;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * 설정값 업데이트
 */
function updateSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE settings SET `value` = ? WHERE `key` = ?");
        $stmt->execute([$value, $key]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ========================================
// 심볼(코인) 관리 시스템
// ========================================

/**
 * 활성화된 모든 심볼 가져오기
 */
function getSymbols($activeOnly = true) {
    static $cache = null;
    if ($cache !== null && $activeOnly) {
        return $cache;
    }
    try {
        $db = getDB();
        $sql = "SELECT * FROM symbols";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, symbol ASC";
        $stmt = $db->query($sql);
        $symbols = $stmt->fetchAll();

        // symbol을 키로 하는 배열로 변환
        $result = [];
        foreach ($symbols as $s) {
            $result[$s['symbol']] = $s;
        }

        if ($activeOnly) {
            $cache = $result;
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * 특정 심볼 정보 가져오기
 */
function getSymbol($symbol) {
    $symbols = getSymbols();
    return isset($symbols[$symbol]) ? $symbols[$symbol] : null;
}

/**
 * 심볼 유효성 검사
 */
function isValidSymbol($symbol) {
    return getSymbol($symbol) !== null;
}

/**
 * 심볼의 가격 소수점 자릿수
 */
function getPriceDecimals($symbol) {
    $s = getSymbol($symbol);
    return $s ? (int)$s['price_decimals'] : 2;
}

/**
 * 심볼의 수량 소수점 자릿수
 */
function getQtyDecimals($symbol) {
    $s = getSymbol($symbol);
    return $s ? (int)$s['qty_decimals'] : 3;
}

/**
 * 가격 포맷팅
 */
function formatPrice($price, $symbol = null) {
    $decimals = $symbol ? getPriceDecimals($symbol) : 2;
    return number_format((float)$price, $decimals, '.', ',');
}

/**
 * 수량 포맷팅
 */
function formatQty($qty, $symbol = null) {
    $decimals = $symbol ? getQtyDecimals($symbol) : 3;
    return number_format((float)$qty, $decimals, '.', ',');
}

// ========================================
// DB 기반 상수들
// ========================================

// WebSocket 서버 설정
define('WS_SERVER_URL', getSetting('ws_server_url', 'http://localhost:3002'));

// 거래 설정
define('MAX_LEVERAGE', getSetting('max_leverage', 125));
define('DEFAULT_LEVERAGE', 10);
define('FUNDING_RATE', 0.0001);

// ========================================
// 수수료 함수
// ========================================

function getTradingFees() {
    static $fees = null;
    if ($fees === null) {
        $fees = [
            'taker' => getSetting('taker_fee', 0.0006),
            'maker' => getSetting('maker_fee', 0.0002)
        ];
    }
    return $fees;
}

function getTakerFee() {
    return getTradingFees()['taker'];
}

function getMakerFee() {
    return getTradingFees()['maker'];
}

// ========================================
// JSON 응답 헬퍼
// ========================================

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('errorResponse')) {
    function errorResponse($message, $code = 400) {
        jsonResponse(['success' => false, 'error' => $message], $code);
    }
}

if (!function_exists('successResponse')) {
    function successResponse($data = [], $message = 'Success') {
        jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
    }
}

// ========================================
// 유틸리티 함수
// ========================================

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function generateOrderId() {
    return 'ORD' . date('YmdHis') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

function generateApiKey() {
    return bin2hex(random_bytes(32));
}

function generateSessionToken() {
    return bin2hex(random_bytes(64));
}
