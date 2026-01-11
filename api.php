<?php
/**
 * Futures Trading - 바이낸스 오더북 프록시 API
 * 실시간 데이터 + 가짜 데이터 생성
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 캐시 설정
$cacheDir = __DIR__ . '/cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// 요청 타입
$action = $_GET['action'] ?? 'all';
$symbol = strtoupper($_GET['symbol'] ?? 'BTCUSDT');

// ========================================
// 바이낸스 API 호출
// ========================================
function fetchBinanceOrderbook($symbol, $limit = 1000) {
    $url = "https://fapi.binance.com/fapi/v1/depth?symbol={$symbol}&limit={$limit}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}

function fetchBinanceTicker($symbol) {
    $url = "https://fapi.binance.com/fapi/v1/ticker/24hr?symbol={$symbol}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function fetchBinanceTrades($symbol, $limit = 50) {
    $url = "https://fapi.binance.com/fapi/v1/trades?symbol={$symbol}&limit={$limit}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// ========================================
// 가짜 오더북 생성
// ========================================
function generateFakeOrderbook($realAsks, $realBids, $currentPrice, $precision = 1) {
    $fakeAsks = [];
    $fakeBids = [];

    // 실제 데이터를 Map으로 변환
    $realAsksMap = [];
    $realBidsMap = [];

    if ($realAsks) {
        foreach ($realAsks as $item) {
            $price = ceil(floatval($item[0]) / $precision) * $precision;
            if (isset($realAsksMap[$price])) {
                $realAsksMap[$price] += floatval($item[1]);
            } else {
                $realAsksMap[$price] = floatval($item[1]);
            }
        }
    }

    if ($realBids) {
        foreach ($realBids as $item) {
            $price = floor(floatval($item[0]) / $precision) * $precision;
            if (isset($realBidsMap[$price])) {
                $realBidsMap[$price] += floatval($item[1]);
            } else {
                $realBidsMap[$price] = floatval($item[1]);
            }
        }
    }

    // Asks (매도) - 현재가 위로 50개
    $baseAskPrice = ceil($currentPrice / $precision) * $precision;
    for ($i = 0; $i < 50; $i++) {
        $price = $baseAskPrice + ($i * $precision);

        if (isset($realAsksMap[$price])) {
            $qty = $realAsksMap[$price];
        } else {
            // 가짜 수량 생성 (거리에 따라 감소)
            $distance = $i + 1;
            $qty = (mt_rand(50, 550) / 100) / sqrt($distance);
        }

        $fakeAsks[] = [number_format($price, 2, '.', ''), number_format($qty, 4, '.', '')];
    }

    // Bids (매수) - 현재가 아래로 50개
    $baseBidPrice = floor($currentPrice / $precision) * $precision;
    for ($i = 0; $i < 50; $i++) {
        $price = $baseBidPrice - ($i * $precision);
        if ($price <= 0) break;

        if (isset($realBidsMap[$price])) {
            $qty = $realBidsMap[$price];
        } else {
            // 가짜 수량 생성
            $distance = $i + 1;
            $qty = (mt_rand(50, 550) / 100) / sqrt($distance);
        }

        $fakeBids[] = [number_format($price, 2, '.', ''), number_format($qty, 4, '.', '')];
    }

    return ['asks' => $fakeAsks, 'bids' => $fakeBids];
}

// ========================================
// 캐시 처리
// ========================================
function getCache($key, $ttl = 0.3) {
    global $cacheDir;
    $file = $cacheDir . md5($key) . '.json';

    if (file_exists($file)) {
        $mtime = filemtime($file);
        if ((microtime(true) - $mtime) < $ttl) {
            return json_decode(file_get_contents($file), true);
        }
    }
    return null;
}

function setCache($key, $data) {
    global $cacheDir;
    $file = $cacheDir . md5($key) . '.json';
    file_put_contents($file, json_encode($data));
}

// ========================================
// API 응답
// ========================================
switch ($action) {
    case 'depth':
    case 'orderbook':
        $cacheKey = "orderbook_{$symbol}";
        $cached = getCache($cacheKey, 0.2);

        if ($cached) {
            echo json_encode($cached);
            exit;
        }

        $orderbook = fetchBinanceOrderbook($symbol, 1000);
        $ticker = fetchBinanceTicker($symbol);

        if (!$orderbook || !$ticker) {
            echo json_encode(['error' => 'Failed to fetch data']);
            exit;
        }

        $currentPrice = floatval($ticker['lastPrice']);

        $result = [
            'symbol' => $symbol,
            'asks' => $orderbook['asks'],
            'bids' => $orderbook['bids'],
            'time' => round(microtime(true) * 1000),
            'extended' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 1),
            'extended10' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 10),
            'extended50' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 50),
            'extended100' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 100),
        ];

        setCache($cacheKey, $result);
        echo json_encode($result);
        break;

    case 'ticker':
        $cacheKey = "ticker_{$symbol}";
        $cached = getCache($cacheKey, 0.3);

        if ($cached) {
            echo json_encode($cached);
            exit;
        }

        $ticker = fetchBinanceTicker($symbol);

        if (!$ticker) {
            echo json_encode(['error' => 'Failed to fetch ticker']);
            exit;
        }

        $result = [
            'symbol' => $symbol,
            'lastPrice' => $ticker['lastPrice'],
            'priceChange' => $ticker['priceChange'],
            'priceChangePercent' => $ticker['priceChangePercent'],
            'highPrice' => $ticker['highPrice'],
            'lowPrice' => $ticker['lowPrice'],
            'volume' => $ticker['volume'],
            'quoteVolume' => $ticker['quoteVolume'],
            'time' => round(microtime(true) * 1000)
        ];

        setCache($cacheKey, $result);
        echo json_encode($result);
        break;

    case 'trades':
        $cacheKey = "trades_{$symbol}";
        $cached = getCache($cacheKey, 0.2);

        if ($cached) {
            echo json_encode($cached);
            exit;
        }

        $trades = fetchBinanceTrades($symbol, 50);

        if (!$trades) {
            echo json_encode(['error' => 'Failed to fetch trades']);
            exit;
        }

        $result = [];
        foreach ($trades as $trade) {
            $result[] = [
                'price' => $trade['price'],
                'qty' => $trade['qty'],
                'time' => date('H:i:s', $trade['time'] / 1000),
                'isBuy' => !$trade['isBuyerMaker']
            ];
        }

        setCache($cacheKey, array_reverse($result));
        echo json_encode(array_reverse($result));
        break;

    case 'all':
    default:
        // 모든 데이터 한번에 가져오기
        $cacheKey = "all_{$symbol}";
        $cached = getCache($cacheKey, 0.2);

        if ($cached) {
            echo json_encode($cached);
            exit;
        }

        $orderbook = fetchBinanceOrderbook($symbol, 1000);
        $ticker = fetchBinanceTicker($symbol);
        $trades = fetchBinanceTrades($symbol, 30);

        if (!$orderbook || !$ticker) {
            echo json_encode(['error' => 'Failed to fetch data']);
            exit;
        }

        $currentPrice = floatval($ticker['lastPrice']);

        $result = [
            'symbol' => $symbol,
            'depth' => [
                'asks' => $orderbook['asks'],
                'bids' => $orderbook['bids'],
                'extended' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 1),
                'extended10' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 10),
                'extended50' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 50),
                'extended100' => generateFakeOrderbook($orderbook['asks'], $orderbook['bids'], $currentPrice, 100),
            ],
            'ticker' => [
                'lastPrice' => $ticker['lastPrice'],
                'priceChange' => $ticker['priceChange'],
                'priceChangePercent' => $ticker['priceChangePercent'],
                'highPrice' => $ticker['highPrice'],
                'lowPrice' => $ticker['lowPrice'],
                'volume' => $ticker['volume'],
                'quoteVolume' => $ticker['quoteVolume'],
            ],
            'trades' => $trades ? array_map(function($t) {
                return [
                    'price' => $t['price'],
                    'qty' => $t['qty'],
                    'time' => date('H:i:s', $t['time'] / 1000),
                    'isBuy' => !$t['isBuyerMaker']
                ];
            }, array_slice(array_reverse($trades), 0, 30)) : [],
            'time' => round(microtime(true) * 1000)
        ];

        setCache($cacheKey, $result);
        echo json_encode($result);
        break;
}
