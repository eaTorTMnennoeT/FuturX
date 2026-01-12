<?php
/**
 * 환율 업데이트 Extend
 * 1시간마다 USD/KRW 환율을 업데이트합니다.
 *
 * 그누보드 extend 방식으로 크론 없이 자동 실행됩니다.
 */

if (!defined('_GNUBOARD_')) {
    // 독립 실행 시
    define('_GNUBOARD_', true);
}

// DB 연결
function getExchangeDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=trade;charset=utf8mb4',
                'root',
                '123123',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("Exchange Rate DB Error: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

// 환율 업데이트 필요 여부 확인 (1시간 경과 시)
function needsExchangeRateUpdate() {
    $db = getExchangeDB();
    if (!$db) return false;

    try {
        $stmt = $db->query("SELECT updated_at FROM exchange_rates WHERE currency_pair = 'USD_KRW'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return true;

        $lastUpdate = strtotime($row['updated_at']);
        $oneHourAgo = time() - 3600;

        return $lastUpdate < $oneHourAgo;
    } catch (Exception $e) {
        return false;
    }
}

// 환율 가져오기 (여러 API 시도)
function fetchUsdKrwRate() {
    // 1. Exchange Rate API (무료)
    $rate = fetchFromExchangeRateApi();
    if ($rate) return $rate;

    // 2. 한국은행 API (백업)
    // $rate = fetchFromBokApi();
    // if ($rate) return $rate;

    return null;
}

// Exchange Rate API에서 환율 가져오기
function fetchFromExchangeRateApi() {
    $url = 'https://api.exchangerate-api.com/v4/latest/USD';

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if (!$response) return null;

    $data = json_decode($response, true);
    if (!$data || !isset($data['rates']['KRW'])) return null;

    return floatval($data['rates']['KRW']);
}

// 환율 DB에 저장
function saveExchangeRate($rate) {
    $db = getExchangeDB();
    if (!$db || !$rate) return false;

    try {
        $stmt = $db->prepare("
            INSERT INTO exchange_rates (currency_pair, rate, updated_at)
            VALUES ('USD_KRW', ?, NOW())
            ON DUPLICATE KEY UPDATE rate = ?, updated_at = NOW()
        ");
        $stmt->execute([$rate, $rate]);

        error_log("[ExchangeRate] USD/KRW 환율 업데이트: " . $rate);
        return true;
    } catch (Exception $e) {
        error_log("[ExchangeRate] 저장 실패: " . $e->getMessage());
        return false;
    }
}

// 현재 환율 가져오기
function getUsdKrwRate() {
    $db = getExchangeDB();
    if (!$db) return 1450; // 기본값

    try {
        $stmt = $db->query("SELECT rate FROM exchange_rates WHERE currency_pair = 'USD_KRW'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? floatval($row['rate']) : 1450;
    } catch (Exception $e) {
        return 1450;
    }
}

// 자동 업데이트 실행 (페이지 로드 시)
if (needsExchangeRateUpdate()) {
    // 비동기로 실행 (페이지 로딩 지연 방지)
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    $newRate = fetchUsdKrwRate();
    if ($newRate) {
        saveExchangeRate($newRate);
    }
}
