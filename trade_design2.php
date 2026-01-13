<?php
// trade_design2.php
// 목적: 디자인 전용 시안 (실기능 연동 금지: API/DB/세션/웹소켓/외부차트 라이브러리 X)
header('Content-Type: text/html; charset=utf-8');

// PHP 8 미만 호환용 폴리필
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle) {
    return $needle === '' ? true : (strpos((string)$haystack, (string)$needle) !== false);
  }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function fmt($n, $dec = 2) { return number_format((float)$n, $dec, '.', ','); }
function clamp($v, $min, $max) { return max($min, min($max, $v)); }

// ---------- 더미 데이터 생성 ----------
$baseSymbols = [
  'BTCUSDT','ETHUSDT','SOLUSDT','BNBUSDT','XRPUSDT','ADAUSDT','DOGEUSDT','AVAXUSDT','TRXUSDT','LINKUSDT',
  'MATICUSDT','DOTUSDT','LTCUSDT','ATOMUSDT','ETCUSDT','BCHUSDT','NEARUSDT','APTUSDT','SUIUSDT','FILUSDT',
  'ARBUSDT','OPUSDT','INJUSDT','SEIUSDT','TIAUSDT','JUPUSDT','PEPEUSDT','WIFUSDT','BONKUSDT','IMXUSDT',
  'GALAUSDT','SANDUSDT','MANAUSDT','AAVEUSDT','UNIUSDT','RUNEUSDT','FTMUSDT','HBARUSDT','ICPUSDT','KASUSDT',
  'STXUSDT','RNDRUSDT','LDOUSDT','MKRUSDT','JTOUSDT','PYTHUSDT','WLDUSDT','ENAUSDT','ONDOUSDT','JASMYUSDT',
  '1000SHIBUSDT','1000FLOKIUSDT','IOSTUSDT','ZRXUSDT','KAVAUSDT','XLMUSDT','EGLDUSDT','FLOWUSDT','GMTUSDT','IOTAUSDT',
  'ALGOUSDT','DYDXUSDT','CRVUSDT','SNXUSDT','COMPUSDT','ZILUSDT','CHZUSDT','ENJUSDT','QTUMUSDT','WAVESUSDT'
];
$symbolsWanted = rand(40, 80);
shuffle($baseSymbols);
$symbolsSlice = array_slice($baseSymbols, 0, $symbolsWanted);

$priceSeed = [
  'BTCUSDT' => 42950.15, 'ETHUSDT' => 2288.31, 'SOLUSDT' => 108.51, 'BNBUSDT' => 312.72,
  'XRPUSDT' => 0.5432, 'ADAUSDT' => 0.4871, 'DOGEUSDT' => 0.08213, 'AVAXUSDT' => 35.41,
  'TRXUSDT' => 0.1067, 'LINKUSDT' => 15.93,
];
function guessDecimals($symbol, $price) {
  // 가격대에 따라 자연스러운 소수점
  if (str_contains($symbol, '1000')) return 6;
  if ($price >= 1000) return 2;
  if ($price >= 10) return 3;
  if ($price >= 1) return 4;
  return 6;
}

$symbols = [];
foreach ($symbolsSlice as $sym) {
  $base = $priceSeed[$sym] ?? (rand(50, 6500) / 10);
  if (str_contains($sym, '1000')) $base = rand(10, 450) / 10000;
  $chg = (rand(-480, 520) / 100); // -4.80% ~ +5.20%
  $last = $base * (1 + $chg / 100);
  $dec = guessDecimals($sym, $last);
  $high = $last * (1 + rand(20, 170) / 1000);
  $low  = $last * (1 - rand(20, 170) / 1000);
  $vol  = rand(200000, 98000000) / 100; // 가짜 거래량
  $fav = (rand(1, 100) <= 18); // 18% 즐겨찾기

  $symbols[] = [
    'symbol' => $sym,
    'last' => $last,
    'chg' => $chg,
    'high' => max($high, $low),
    'low' => min($high, $low),
    'vol' => $vol,
    'dec' => $dec,
    'fav' => $fav,
  ];
}
usort($symbols, function($a, $b) {
  // 즐겨찾기 우선, 그 다음 거래량
  if ($a['fav'] !== $b['fav']) return $a['fav'] ? -1 : 1;
  return $b['vol'] <=> $a['vol'];
});

$current = $symbols[0];
$currentSymbol = $current['symbol'];
$mid = $current['last'];
$pDec = $current['dec'];
$qDec = (str_contains($currentSymbol, '1000') ? 0 : 3) + rand(0, 3);

// 오더북 (asks/bids)
$obRows = rand(28, 38);
$tick = $mid * 0.00035; // 틱
$asks = [];
$bids = [];
$askSum = 0.0;
$bidSum = 0.0;
for ($i = 1; $i <= $obRows; $i++) {
  $ap = $mid + ($i * $tick) + (rand(-8, 8) * $tick * 0.2);
  $aq = max(0.001, (rand(8, 2400) / 1000));
  if ($mid > 1000) $aq = max(0.001, $aq * 0.12);
  $askSum += $aq;
  $asks[] = ['p' => $ap, 'q' => $aq, 's' => $askSum];

  $bp = $mid - ($i * $tick) + (rand(-8, 8) * $tick * 0.2);
  $bq = max(0.001, (rand(8, 2400) / 1000));
  if ($mid > 1000) $bq = max(0.001, $bq * 0.12);
  $bidSum += $bq;
  $bids[] = ['p' => $bp, 'q' => $bq, 's' => $bidSum];
}
$asksMaxSum = max(1e-9, $askSum);
$bidsMaxSum = max(1e-9, $bidSum);

// 최근 체결
$trades = [];
$tradeRows = 60;
$t = time();
$lastTradePrice = $mid;
for ($i = 0; $i < $tradeRows; $i++) {
  $dir = (rand(0, 100) < 52) ? 1 : -1;
  $delta = ($tick * rand(1, 12) * 0.22) * $dir;
  $price = $lastTradePrice + $delta;
  // 현재가 주변에서 자연스럽게 유지
  $price = clamp($price, $mid * 0.985, $mid * 1.015);
  $qty = max(0.001, rand(5, 4200) / 1000);
  if ($mid > 1000) $qty = max(0.001, $qty * 0.08);
  $side = ($price >= $lastTradePrice) ? 'buy' : 'sell';
  $trades[] = [
    'time' => date('H:i:s', $t - ($i * rand(2, 7))),
    'price' => $price,
    'qty' => $qty,
    'side' => $side,
  ];
  $lastTradePrice = $price;
}
$markPrice = $mid * (1 + rand(-22, 22) / 10000);
$idxPrice  = $mid * (1 + rand(-16, 16) / 10000);
$funding   = rand(-28, 32) / 10000; // -0.28%~0.32%
$oi        = rand(1200000, 98000000) / 100; // 오픈이자(더미)

// 하단 테이블 더미
function genOrders($rows, $symbol, $pDec, $qDec, $mid, $tick, $typeLabel) {
  $out = [];
  $now = time();
  for ($i = 0; $i < $rows; $i++) {
    $side = (rand(0, 1) === 0) ? 'Buy' : 'Sell';
    $price = $mid + (rand(-120, 120) * $tick);
    $qty = max(0.001, rand(30, 9000) / 1000);
    if ($mid > 1000) $qty = max(0.001, $qty * 0.10);
    $filled = rand(0, 100) / 100;
    $status = ($filled >= 1) ? 'Filled' : (($filled <= 0.05) ? 'New' : 'Partially Filled');
    $out[] = [
      'time' => date('m-d H:i', $now - ($i * rand(40, 180))),
      'symbol' => $symbol,
      'type' => $typeLabel,
      'side' => $side,
      'price' => $price,
      'qty' => $qty,
      'filled' => $filled,
      'status' => $status,
      'pDec' => $pDec,
      'qDec' => $qDec,
    ];
  }
  return $out;
}
function genPositions($rows, $symbol, $mid, $tick, $pDec, $qDec) {
  $out = [];
  for ($i = 0; $i < $rows; $i++) {
    $side = (rand(0, 1) === 0) ? 'Long' : 'Short';
    $entry = $mid + (rand(-220, 220) * $tick);
    $mark = $mid + (rand(-80, 80) * $tick);
    $size = max(0.001, rand(60, 8200) / 1000);
    if ($mid > 1000) $size = max(0.001, $size * 0.10);
    $lev = [5, 10, 20, 25, 50][array_rand([5,10,20,25,50])];
    $pnl = ($mark - $entry) * $size * ($side === 'Long' ? 1 : -1);
    $roe = ($pnl / max(1e-9, ($entry * $size / $lev))) * 100;
    $liq = ($side === 'Long') ? ($entry * (1 - (1 / $lev) * 0.82)) : ($entry * (1 + (1 / $lev) * 0.82));
    $out[] = [
      'symbol' => $symbol,
      'side' => $side,
      'size' => $size,
      'entry' => $entry,
      'mark' => $mark,
      'liq' => $liq,
      'pnl' => $pnl,
      'roe' => $roe,
      'lev' => $lev,
      'pDec' => $pDec,
      'qDec' => $qDec,
    ];
  }
  return $out;
}
function genAssets($rows) {
  $coins = ['USDT','BTC','ETH','BNB','SOL','XRP','ADA','DOGE','AVAX','LINK','ARB','OP','SUI','APT','TRX'];
  shuffle($coins);
  $coins = array_slice($coins, 0, $rows);
  $out = [];
  foreach ($coins as $c) {
    $total = rand(0, 1000000) / 100;
    if ($c === 'BTC') $total = rand(0, 180000) / 100000; // 0~1.8
    if ($c === 'ETH') $total = rand(0, 320000) / 10000; // 0~32
    $available = $total * (rand(50, 98) / 100);
    $inOrder = max(0, $total - $available);
    $uPrice = ($c === 'USDT') ? 1.0 : (rand(80, 650000) / 100);
    $value = $total * $uPrice;
    $out[] = [
      'coin' => $c,
      'total' => $total,
      'available' => $available,
      'inOrder' => $inOrder,
      'uPrice' => $uPrice,
      'value' => $value,
    ];
  }
  return $out;
}

$openOrders = genOrders(20, $currentSymbol, $pDec, $qDec, $mid, $tick, 'Limit');
$orderHistory = genOrders(50, $currentSymbol, $pDec, $qDec, $mid, $tick, (rand(0,1)?'Market':'Limit'));
$tradeHistory = genOrders(50, $currentSymbol, $pDec, $qDec, $mid, $tick, 'Trade');
$positions = genPositions(10, $currentSymbol, $mid, $tick, $pDec, $qDec);
$assets = genAssets(10);

$isUp = ($current['chg'] >= 0);
$chgText = ($current['chg'] >= 0 ? '+' : '') . fmt($current['chg'], 2) . '%';
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title><?= h($currentSymbol) ?> | Trade Design 2 (Mock)</title>
  <link rel="stylesheet" href="css/trade_design2.css?v=1">
</head>
<body>
  <div class="app" id="appRoot" data-theme="dark">
    <!-- Top Bar -->
    <header class="topbar" id="topbar">
      <div class="topbar__left">
        <a class="brand" href="trade_design2.php" aria-label="FuturX Mock">
          <span class="brand__mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 16.4L12 4l8 12.4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7.1 16.4L12 8.7l4.9 7.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity=".55"/>
            </svg>
          </span>
          <span class="brand__text">FuturX</span>
        </a>

        <button class="iconbtn only-m" id="openSymbols" type="button" aria-label="심볼 리스트 열기">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>

        <div class="pair">
          <div class="pair__main">
            <span class="pair__symbol" id="pairSymbol"><?= h($currentSymbol) ?></span>
            <span class="pair__badge">USDT Perp</span>
          </div>
          <div class="pair__sub">Mark <?= fmt($markPrice, $pDec) ?> · Index <?= fmt($idxPrice, $pDec) ?></div>
        </div>
      </div>

      <div class="topbar__mid">
        <div class="search">
          <span class="search__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>
              <path d="M16.2 16.2 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <input id="symbolSearchTop" class="search__input" type="text" placeholder="심볼 검색 (더미)" autocomplete="off" />
          <button class="search__btn" type="button" id="searchGo" title="검색(더미)">↵</button>
        </div>
      </div>

      <div class="topbar__right">
        <div class="metrics">
          <div class="metric">
            <div class="metric__label">Last</div>
            <div class="metric__value <?= $isUp ? 'is-up' : 'is-down' ?>"><?= fmt($current['last'], $pDec) ?></div>
          </div>
          <div class="metric">
            <div class="metric__label">24h Chg</div>
            <div class="metric__value <?= $isUp ? 'is-up' : 'is-down' ?>"><?= h($chgText) ?></div>
          </div>
          <div class="metric only-d">
            <div class="metric__label">24h High</div>
            <div class="metric__value"><?= fmt($current['high'], $pDec) ?></div>
          </div>
          <div class="metric only-d">
            <div class="metric__label">24h Low</div>
            <div class="metric__value"><?= fmt($current['low'], $pDec) ?></div>
          </div>
          <div class="metric only-d">
            <div class="metric__label">Volume</div>
            <div class="metric__value"><?= fmt($current['vol'], 0) ?></div>
          </div>
        </div>

        <div class="controls">
          <div class="seg" role="tablist" aria-label="마켓 선택(더미)">
            <button class="seg__btn is-active" type="button">USDT Perp</button>
            <button class="seg__btn" type="button">COIN-M</button>
          </div>
          <button class="iconbtn" id="toggleTheme" type="button" aria-label="테마(더미)">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 3a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0V4a1 1 0 0 1 1-1Z" fill="currentColor"/>
              <path d="M4 11a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1Z" fill="currentColor"/>
              <path d="M17 11a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2h-2a1 1 0 0 1-1-1Z" fill="currentColor"/>
              <path d="M12 18a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0v-2a1 1 0 0 1 1-1Z" fill="currentColor"/>
              <path d="M12 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke="currentColor" stroke-width="2"/>
            </svg>
          </button>
          <button class="iconbtn" id="toggleLang" type="button" aria-label="언어(더미)">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="2"/>
              <path d="M3 12h18" stroke="currentColor" stroke-width="2"/>
              <path d="M12 3c2.6 2.4 4.2 5.4 4.2 9S14.6 18.6 12 21c-2.6-2.4-4.2-5.4-4.2-9S9.4 5.4 12 3Z" stroke="currentColor" stroke-width="2" opacity=".65"/>
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- Main 3-column -->
    <main class="layout" id="layoutRoot">
      <!-- Left: Symbols -->
      <aside class="panel symbols only-d" aria-label="심볼 리스트">
        <div class="panel__head is-sticky">
          <div class="panel__title">Markets</div>
          <div class="tabs tabs--mini" role="tablist" aria-label="심볼 필터(더미)">
            <button class="tab is-active" type="button" data-symtab="fav">★</button>
            <button class="tab" type="button" data-symtab="usdt">USDT</button>
            <button class="tab" type="button" data-symtab="btc">BTC</button>
            <button class="tab" type="button" data-symtab="alt">Alt</button>
          </div>
        </div>
        <div class="panel__subhead is-sticky">
          <div class="mini-search">
            <input class="mini-search__input" id="symbolSearchLeft" type="text" placeholder="Search" autocomplete="off" />
            <button class="mini-search__x" type="button" id="symbolSearchClear" aria-label="검색 초기화">×</button>
          </div>
          <div class="symhead">
            <span class="symhead__c1">Symbol</span>
            <span class="symhead__c2">Last</span>
            <span class="symhead__c3">24h%</span>
            <span class="symhead__c4">Trend</span>
          </div>
        </div>
        <div class="panel__body scroll" id="symbolsList" data-skeleton="true">
          <?php foreach ($symbols as $i => $s): ?>
            <?php
              $up = ($s['chg'] >= 0);
              $chg = ($s['chg'] >= 0 ? '+' : '') . fmt($s['chg'], 2) . '%';
              $isActive = ($s['symbol'] === $currentSymbol);
            ?>
            <button
              class="symrow <?= $isActive ? 'is-active' : '' ?>"
              type="button"
              data-symbol="<?= h($s['symbol']) ?>"
              data-last="<?= h(fmt($s['last'], $s['dec'])) ?>"
              data-chg="<?= h($chg) ?>"
              data-chg-raw="<?= h($s['chg']) ?>"
              data-fav="<?= $s['fav'] ? '1' : '0' ?>"
              data-spark-seed="<?= h(($i+7) * 13) ?>"
            >
              <span class="symrow__fav" aria-label="즐겨찾기(더미)"><?= $s['fav'] ? '★' : '☆' ?></span>
              <span class="symrow__sym"><?= h($s['symbol']) ?></span>
              <span class="symrow__last"><?= fmt($s['last'], $s['dec']) ?></span>
              <span class="symrow__chg <?= $up ? 'is-up' : 'is-down' ?>"><?= h($chg) ?></span>
              <span class="symrow__spark">
                <canvas class="spark" width="64" height="18" aria-hidden="true"></canvas>
              </span>
            </button>
          <?php endforeach; ?>
          <div class="empty-state" hidden id="symbolsEmpty">
            <div class="empty-state__icon">◎</div>
            <div class="empty-state__title">검색 결과가 없습니다</div>
            <div class="empty-state__desc">다른 키워드로 시도해보세요. (더미)</div>
          </div>
        </div>
      </aside>

      <!-- Center: Chart + Bottom tabs -->
      <section class="center">
        <section class="panel chart" aria-label="차트(더미)">
          <div class="panel__head">
            <div class="panel__title">
              Chart
              <span class="pill pill--muted">Mock</span>
            </div>
            <div class="toolbar">
              <div class="seg seg--tiny" role="tablist" aria-label="타임프레임(더미)">
                <button class="seg__btn is-active" type="button" data-tf="1m">1m</button>
                <button class="seg__btn" type="button" data-tf="5m">5m</button>
                <button class="seg__btn" type="button" data-tf="15m">15m</button>
                <button class="seg__btn" type="button" data-tf="1h">1h</button>
                <button class="seg__btn only-d" type="button" data-tf="4h">4h</button>
                <button class="seg__btn only-d" type="button" data-tf="1d">1d</button>
              </div>
              <div class="seg seg--tiny" role="tablist" aria-label="지표(더미)">
                <button class="seg__btn" type="button" data-ind="ma">MA</button>
                <button class="seg__btn" type="button" data-ind="ema">EMA</button>
                <button class="seg__btn" type="button" data-ind="boll">BOLL</button>
              </div>
            </div>
          </div>
          <div class="panel__body">
            <div class="chartwrap" data-skeleton="true">
              <canvas id="candleCanvas" width="1200" height="420" aria-label="더미 캔들 차트"></canvas>
              <div class="chart__hud">
                <div class="hudline">
                  <span class="hudlabel">Funding</span>
                  <span class="hudval <?= $funding >= 0 ? 'is-up' : 'is-down' ?>"><?= ($funding >= 0 ? '+' : '') . fmt($funding * 100, 4) ?>%</span>
                </div>
                <div class="hudline">
                  <span class="hudlabel">Open Interest</span>
                  <span class="hudval"><?= fmt($oi, 0) ?></span>
                </div>
              </div>
            </div>
          </div>
          <div class="panel__foot">
            <div class="mini-tabs" role="tablist" aria-label="차트 하단 탭(더미)">
              <button class="mini-tab is-active" type="button" data-mini="open">Open Orders</button>
              <button class="mini-tab" type="button" data-mini="pos">Positions</button>
              <button class="mini-tab" type="button" data-mini="asset">Assets</button>
            </div>
            <div class="mini-panels">
              <div class="mini-panel is-active" data-mini-panel="open">
                <div class="mini-table">
                  <div class="mini-table__head">
                    <span>Time</span><span>Side</span><span>Price</span><span>Qty</span><span>Status</span>
                  </div>
                  <?php foreach (array_slice($openOrders, 0, 8) as $r): ?>
                    <?php $isBuy = ($r['side'] === 'Buy'); ?>
                    <div class="mini-table__row">
                      <span class="muted"><?= h($r['time']) ?></span>
                      <span class="<?= $isBuy ? 'is-up' : 'is-down' ?>"><?= h($r['side']) ?></span>
                      <span><?= fmt($r['price'], $r['pDec']) ?></span>
                      <span><?= fmt($r['qty'], $r['qDec']) ?></span>
                      <span class="muted"><?= h($r['status']) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="mini-panel" data-mini-panel="pos">
                <div class="empty-state empty-state--compact">
                  <div class="empty-state__title">포지션 요약은 하단 “Positions” 탭에서 확인 (더미)</div>
                  <div class="empty-state__desc">이 영역은 차트 하단 요약용 슬롯입니다.</div>
                </div>
              </div>
              <div class="mini-panel" data-mini-panel="asset">
                <div class="mini-table">
                  <div class="mini-table__head">
                    <span>Coin</span><span>Total</span><span>Available</span><span>Value(USDT)</span><span></span>
                  </div>
                  <?php foreach (array_slice($assets, 0, 6) as $a): ?>
                    <div class="mini-table__row">
                      <span><?= h($a['coin']) ?></span>
                      <span><?= fmt($a['total'], ($a['coin']==='BTC'?6:($a['coin']==='ETH'?4:2))) ?></span>
                      <span class="muted"><?= fmt($a['available'], ($a['coin']==='BTC'?6:($a['coin']==='ETH'?4:2))) ?></span>
                      <span><?= fmt($a['value'], 2) ?></span>
                      <span class="muted">···</span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="panel bottom" aria-label="하단 테이블(더미)">
          <div class="panel__head">
            <div class="panel__title">Activity</div>
            <div class="tabs" role="tablist" aria-label="하단 탭(더미)" id="bottomTabs">
              <button class="tab is-active" type="button" data-tab="openOrders">Open Orders</button>
              <button class="tab" type="button" data-tab="orderHistory">Order History</button>
              <button class="tab" type="button" data-tab="tradeHistory">Trade History</button>
              <button class="tab" type="button" data-tab="positions">Positions</button>
              <button class="tab" type="button" data-tab="assets">Assets</button>
            </div>
          </div>
          <div class="panel__body">
            <div class="tablewrap" id="bottomPanels">
              <!-- Open Orders -->
              <div class="tabpanel is-active" data-tabpanel="openOrders">
                <div class="table table--wide">
                  <div class="trow thead">
                    <div>Time</div><div>Symbol</div><div>Type</div><div>Side</div><div>Price</div><div>Qty</div><div>Filled</div><div>Status</div>
                  </div>
                  <?php foreach ($openOrders as $r): ?>
                    <?php $isBuy = ($r['side'] === 'Buy'); ?>
                    <div class="trow">
                      <div class="muted"><?= h($r['time']) ?></div>
                      <div><?= h($r['symbol']) ?></div>
                      <div class="muted"><?= h($r['type']) ?></div>
                      <div class="<?= $isBuy ? 'is-up' : 'is-down' ?>"><?= h($r['side']) ?></div>
                      <div><?= fmt($r['price'], $r['pDec']) ?></div>
                      <div><?= fmt($r['qty'], $r['qDec']) ?></div>
                      <div class="muted"><?= fmt($r['filled'] * 100, 0) ?>%</div>
                      <div class="muted"><?= h($r['status']) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="tablefoot">
                  <div class="pager">
                    <button class="btn btn--ghost" type="button">Prev</button>
                    <span class="pager__meta muted">1 / 5</span>
                    <button class="btn btn--ghost" type="button">Next</button>
                  </div>
                  <span class="muted">스크롤/페이지네이션은 더미입니다.</span>
                </div>
              </div>

              <!-- Order History -->
              <div class="tabpanel" data-tabpanel="orderHistory">
                <div class="table table--wide">
                  <div class="trow thead">
                    <div>Time</div><div>Symbol</div><div>Type</div><div>Side</div><div>Avg</div><div>Qty</div><div>Filled</div><div>Status</div>
                  </div>
                  <?php foreach ($orderHistory as $r): ?>
                    <?php $isBuy = ($r['side'] === 'Buy'); ?>
                    <div class="trow">
                      <div class="muted"><?= h($r['time']) ?></div>
                      <div><?= h($r['symbol']) ?></div>
                      <div class="muted"><?= h($r['type']) ?></div>
                      <div class="<?= $isBuy ? 'is-up' : 'is-down' ?>"><?= h($r['side']) ?></div>
                      <div><?= fmt($r['price'], $r['pDec']) ?></div>
                      <div><?= fmt($r['qty'], $r['qDec']) ?></div>
                      <div class="muted"><?= fmt($r['filled'] * 100, 0) ?>%</div>
                      <div class="muted"><?= h($r['status']) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="tablefoot">
                  <div class="pager">
                    <button class="btn btn--ghost" type="button">Prev</button>
                    <span class="pager__meta muted">1 / 12</span>
                    <button class="btn btn--ghost" type="button">Next</button>
                  </div>
                  <span class="muted">내역은 랜덤 더미입니다.</span>
                </div>
              </div>

              <!-- Trade History -->
              <div class="tabpanel" data-tabpanel="tradeHistory">
                <div class="table table--wide">
                  <div class="trow thead">
                    <div>Time</div><div>Symbol</div><div>Side</div><div>Price</div><div>Qty</div><div>Fee</div><div>Realized PnL</div><div>Status</div>
                  </div>
                  <?php foreach ($tradeHistory as $r): ?>
                    <?php
                      $isBuy = ($r['side'] === 'Buy');
                      $fee = $r['qty'] * 0.0004;
                      $rpnl = (($isBuy ? -1 : 1) * (rand(-90, 130) / 10));
                    ?>
                    <div class="trow">
                      <div class="muted"><?= h($r['time']) ?></div>
                      <div><?= h($r['symbol']) ?></div>
                      <div class="<?= $isBuy ? 'is-up' : 'is-down' ?>"><?= h($r['side']) ?></div>
                      <div><?= fmt($r['price'], $r['pDec']) ?></div>
                      <div><?= fmt($r['qty'], $r['qDec']) ?></div>
                      <div class="muted"><?= fmt($fee, 4) ?></div>
                      <div class="<?= $rpnl >= 0 ? 'is-up' : 'is-down' ?>"><?= ($rpnl >= 0 ? '+' : '') . fmt($rpnl, 2) ?></div>
                      <div class="muted">Done</div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="tablefoot">
                  <div class="pager">
                    <button class="btn btn--ghost" type="button">Prev</button>
                    <span class="pager__meta muted">1 / 10</span>
                    <button class="btn btn--ghost" type="button">Next</button>
                  </div>
                </div>
              </div>

              <!-- Positions -->
              <div class="tabpanel" data-tabpanel="positions">
                <div class="table table--pos">
                  <div class="trow thead">
                    <div>Symbol</div><div>Side</div><div>Size</div><div>Entry</div><div>Mark</div><div>Liq</div><div>PnL</div><div>ROE</div>
                  </div>
                  <?php foreach ($positions as $p): ?>
                    <?php
                      $isLong = ($p['side'] === 'Long');
                      $pnl = $p['pnl'];
                      $roe = $p['roe'];
                    ?>
                    <div class="trow">
                      <div><?= h($p['symbol']) ?> <span class="pill pill--muted">x<?= h($p['lev']) ?></span></div>
                      <div class="<?= $isLong ? 'is-up' : 'is-down' ?>"><?= h($p['side']) ?></div>
                      <div><?= fmt($p['size'], $p['qDec']) ?></div>
                      <div><?= fmt($p['entry'], $p['pDec']) ?></div>
                      <div><?= fmt($p['mark'], $p['pDec']) ?></div>
                      <div class="muted"><?= fmt($p['liq'], $p['pDec']) ?></div>
                      <div class="<?= $pnl >= 0 ? 'is-up' : 'is-down' ?>"><?= ($pnl >= 0 ? '+' : '') . fmt($pnl, 2) ?></div>
                      <div class="<?= $roe >= 0 ? 'is-up' : 'is-down' ?>"><?= ($roe >= 0 ? '+' : '') . fmt($roe, 2) ?>%</div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="tablefoot">
                  <span class="muted">청산가/ROE는 계산 흉내만 낸 더미입니다.</span>
                </div>
              </div>

              <!-- Assets -->
              <div class="tabpanel" data-tabpanel="assets">
                <div class="table table--asset">
                  <div class="trow thead">
                    <div>Coin</div><div>Total</div><div>Available</div><div>In Order</div><div>Price</div><div>Value</div>
                  </div>
                  <?php foreach ($assets as $a): ?>
                    <?php
                      $dec = ($a['coin'] === 'BTC') ? 6 : (($a['coin'] === 'ETH') ? 4 : 2);
                    ?>
                    <div class="trow">
                      <div><?= h($a['coin']) ?></div>
                      <div><?= fmt($a['total'], $dec) ?></div>
                      <div class="muted"><?= fmt($a['available'], $dec) ?></div>
                      <div class="muted"><?= fmt($a['inOrder'], $dec) ?></div>
                      <div class="muted"><?= fmt($a['uPrice'], 2) ?></div>
                      <div><?= fmt($a['value'], 2) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="tablefoot">
                  <span class="muted">자산은 랜덤 더미(가격/평가 포함)입니다.</span>
                </div>
              </div>
            </div><!-- /tablewrap -->
          </div>
        </section>
      </section>

      <!-- Right: Orderbook + Trades + Order panel -->
      <aside class="right">
        <section class="panel orderbook" aria-label="오더북/최근체결(더미)" id="obPanel">
          <div class="panel__head">
            <div class="panel__title">Order Book</div>
            <div class="tabs tabs--mini" role="tablist" aria-label="오더북 탭(더미)" id="obTabs">
              <button class="tab is-active" type="button" data-ob="book">Order Book</button>
              <button class="tab" type="button" data-ob="trades">Recent Trades</button>
            </div>
          </div>
          <div class="panel__body">
            <div class="obwrap tabpanel is-active" data-obpanel="book">
              <div class="obhead">
                <span>Price</span><span>Qty</span><span>Total</span>
              </div>
              <div class="oblist oblist--asks scroll">
                <?php foreach ($asks as $row): ?>
                  <?php $pct = ($row['s'] / $asksMaxSum) * 100; ?>
                  <div class="obrow obrow--ask" style="--fill: <?= h(number_format($pct, 2, '.', '')) ?>%">
                    <span class="obrow__p is-down"><?= fmt($row['p'], $pDec) ?></span>
                    <span class="obrow__q"><?= fmt($row['q'], $qDec) ?></span>
                    <span class="obrow__s muted"><?= fmt($row['s'], $qDec) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="obmid">
                <div class="obmid__price <?= $isUp ? 'is-up' : 'is-down' ?>" id="midPrice"><?= fmt($mid, $pDec) ?></div>
                <div class="obmid__meta muted">Spread <?= fmt(($asks[0]['p'] - $bids[0]['p']), $pDec) ?> (<?= fmt((($asks[0]['p'] - $bids[0]['p']) / $mid) * 100, 2) ?>%)</div>
              </div>
              <div class="oblist oblist--bids scroll">
                <?php foreach ($bids as $row): ?>
                  <?php $pct = ($row['s'] / $bidsMaxSum) * 100; ?>
                  <div class="obrow obrow--bid" style="--fill: <?= h(number_format($pct, 2, '.', '')) ?>%">
                    <span class="obrow__p is-up"><?= fmt($row['p'], $pDec) ?></span>
                    <span class="obrow__q"><?= fmt($row['q'], $qDec) ?></span>
                    <span class="obrow__s muted"><?= fmt($row['s'], $qDec) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="trades tabpanel" data-obpanel="trades">
              <div class="obhead">
                <span>Time</span><span>Price</span><span>Qty</span>
              </div>
              <div class="trlist scroll" id="recentTrades">
                <?php foreach ($trades as $tr): ?>
                  <div class="trrow">
                    <span class="muted"><?= h($tr['time']) ?></span>
                    <span class="<?= $tr['side'] === 'buy' ? 'is-up' : 'is-down' ?>"><?= fmt($tr['price'], $pDec) ?></span>
                    <span><?= fmt($tr['qty'], $qDec) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="panel__hint muted">가격 컬러는 더미 로직(직전 체결 대비)입니다.</div>
            </div>
          </div>
        </section>

        <section class="panel order" aria-label="주문 패널(더미)" id="orderPanel">
          <div class="panel__head">
            <div class="panel__title">Place Order</div>
            <div class="tabs tabs--mini" role="tablist" aria-label="주문 타입(더미)" id="orderTypeTabs">
              <button class="tab is-active" type="button" data-otype="limit">Limit</button>
              <button class="tab" type="button" data-otype="market">Market</button>
              <button class="tab" type="button" data-otype="stop">Stop</button>
            </div>
          </div>
          <div class="panel__body">
            <div class="two-col">
              <div class="card ghost">
                <div class="field">
                  <div class="field__label">Price <span class="muted">(<?= h($currentSymbol) ?>)</span></div>
                  <div class="input">
                    <input id="orderPrice" inputmode="decimal" value="<?= h(fmt($mid, $pDec)) ?>" />
                    <span class="input__unit">USDT</span>
                  </div>
                </div>
                <div class="field" data-otype-only="stop" hidden>
                  <div class="field__label">Stop Price</div>
                  <div class="input">
                    <input id="stopPrice" inputmode="decimal" value="<?= h(fmt($mid * 0.998, $pDec)) ?>" />
                    <span class="input__unit">USDT</span>
                  </div>
                </div>
                <div class="field">
                  <div class="field__label">Qty</div>
                  <div class="input">
                    <input id="orderQty" inputmode="decimal" value="<?= h(fmt(max(0.001, rand(5, 1200)/1000), $qDec)) ?>" />
                    <span class="input__unit"><?= h(str_replace('USDT','',$currentSymbol)) ?></span>
                  </div>
                </div>
                <div class="field">
                  <div class="field__label">Total</div>
                  <div class="input is-disabled">
                    <input id="orderTotal" value="0.00" disabled />
                    <span class="input__unit">USDT</span>
                  </div>
                </div>

                <div class="slider">
                  <div class="slider__row">
                    <span class="muted">0%</span>
                    <input id="pctSlider" type="range" min="0" max="100" step="1" value="25" />
                    <span class="muted">100%</span>
                  </div>
                  <div class="chips" aria-label="프리셋(더미)">
                    <button class="chip" type="button" data-pct="25">25%</button>
                    <button class="chip" type="button" data-pct="50">50%</button>
                    <button class="chip" type="button" data-pct="75">75%</button>
                    <button class="chip" type="button" data-pct="100">100%</button>
                  </div>
                </div>
              </div>

              <div class="card ghost">
                <div class="kv">
                  <div class="kv__row">
                    <span class="kv__k muted">Leverage</span>
                    <span class="kv__v"><span class="pill pill--blue">Cross</span> <span class="pill pill--muted">x20</span></span>
                  </div>
                  <div class="kv__row">
                    <span class="kv__k muted">Fee (est.)</span>
                    <span class="kv__v muted" id="feeEst">0.00 USDT</span>
                  </div>
                  <div class="kv__row">
                    <span class="kv__k muted">Est. Liq</span>
                    <span class="kv__v muted" id="liqEst"><?= fmt($mid * 0.923, $pDec) ?> USDT</span>
                  </div>
                </div>
                <div class="divider"></div>
                <div class="notice">
                  <div class="notice__title">시안 안내</div>
                  <div class="notice__desc">
                    이 페이지는 <b>디자인 전용</b>이며, 주문/포지션/잔고는 전부 더미입니다.
                  </div>
                </div>
              </div>
            </div>

            <div class="actions">
              <button class="btn btn--buy" type="button" id="btnBuy">Buy / Long</button>
              <button class="btn btn--sell" type="button" id="btnSell">Sell / Short</button>
            </div>
          </div>
        </section>
      </aside>
    </main>

    <!-- Mobile bottom nav -->
    <nav class="mnav only-m" aria-label="하단 탭" id="mobileNav">
      <button class="mnav__btn is-active" type="button" data-mtab="chart">
        <span class="mnav__ico">📈</span><span class="mnav__txt">Chart</span>
      </button>
      <button class="mnav__btn" type="button" data-mtab="ob">
        <span class="mnav__ico">≋</span><span class="mnav__txt">Orderbook</span>
      </button>
      <button class="mnav__btn" type="button" data-mtab="order">
        <span class="mnav__ico">＋</span><span class="mnav__txt">Order</span>
      </button>
      <button class="mnav__btn" type="button" data-mtab="pos">
        <span class="mnav__ico">◎</span><span class="mnav__txt">Positions</span>
      </button>
    </nav>

    <!-- Mobile overlays -->
    <div class="drawer" id="symbolsDrawer" aria-hidden="true">
      <div class="drawer__backdrop" data-close-drawer="1"></div>
      <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="심볼 리스트(모바일)">
        <div class="drawer__head">
          <div class="panel__title">Markets</div>
          <button class="iconbtn" type="button" data-close-drawer="1" aria-label="닫기">×</button>
        </div>
        <div class="drawer__body">
          <div class="mini-search">
            <input class="mini-search__input" id="symbolSearchDrawer" type="text" placeholder="Search" autocomplete="off" />
            <button class="mini-search__x" type="button" id="symbolSearchDrawerClear" aria-label="검색 초기화">×</button>
          </div>
          <div class="symhead">
            <span class="symhead__c1">Symbol</span>
            <span class="symhead__c2">Last</span>
            <span class="symhead__c3">24h%</span>
            <span class="symhead__c4">Trend</span>
          </div>
          <div class="scroll drawer__list" id="symbolsDrawerList"></div>
          <div class="empty-state" hidden id="drawerEmpty">
            <div class="empty-state__title">검색 결과가 없습니다</div>
            <div class="empty-state__desc">다른 키워드로 시도해보세요. (더미)</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div class="toastwrap" id="toastWrap" aria-live="polite" aria-atomic="true"></div>
  </div>

  <script>
  (() => {
    // -----------------------------
    // Helpers
    // -----------------------------
    const $ = (sel, el=document) => el.querySelector(sel);
    const $$ = (sel, el=document) => Array.from(el.querySelectorAll(sel));
    const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
    const num = (s) => {
      const v = parseFloat(String(s).replace(/,/g,''));
      return Number.isFinite(v) ? v : 0;
    };
    const fmt = (n, dec=2) => {
      const f = Number(n);
      if (!Number.isFinite(f)) return '0';
      return f.toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec });
    };

    const toastWrap = $('#toastWrap');
    const toast = (msg, tone='info') => {
      const el = document.createElement('div');
      el.className = `toast toast--${tone}`;
      el.innerHTML = `
        <div class="toast__dot" aria-hidden="true"></div>
        <div class="toast__msg">${msg}</div>
        <button class="toast__x" type="button" aria-label="닫기">×</button>
      `;
      toastWrap.appendChild(el);
      const kill = () => {
        el.classList.add('is-out');
        setTimeout(() => el.remove(), 220);
      };
      $('.toast__x', el).addEventListener('click', kill);
      setTimeout(kill, 2400);
      requestAnimationFrame(() => el.classList.add('is-in'));
    };

    // -----------------------------
    // Skeleton 느낌(더미)
    // -----------------------------
    const skeletonOff = () => {
      $$('[data-skeleton="true"]').forEach(el => el.classList.add('is-loaded'));
    };
    setTimeout(skeletonOff, 420);

    // -----------------------------
    // Symbol list: sparkline render
    // -----------------------------
    const drawSpark = (canvas, seed=17, up=true) => {
      const ctx = canvas.getContext('2d');
      if (!ctx) return;
      const w = canvas.width, h = canvas.height;
      ctx.clearRect(0,0,w,h);
      const bars = 14;
      let x = 0;
      const gap = 2;
      const bw = Math.floor((w - (bars-1)*gap)/bars);
      let v = (seed % 9) + 5;
      for (let i=0;i<bars;i++){
        v = clamp(v + ((Math.sin((seed+i)*1.7)*3) + (Math.cos((seed+i)*0.9)*2)), 2, 18);
        const bh = Math.floor((v/18) * (h-2)) + 2;
        const y = h - bh;
        const grad = ctx.createLinearGradient(0,y,0,h);
        if (up) {
          grad.addColorStop(0, 'rgba(14,203,129,.65)');
          grad.addColorStop(1, 'rgba(14,203,129,.10)');
        } else {
          grad.addColorStop(0, 'rgba(246,70,93,.65)');
          grad.addColorStop(1, 'rgba(246,70,93,.10)');
        }
        ctx.fillStyle = grad;
        ctx.fillRect(x, y, bw, bh);
        x += bw + gap;
      }
    };

    const renderSparks = (root) => {
      $$('.symrow', root).forEach(btn => {
        const c = $('canvas.spark', btn);
        const seed = parseInt(btn.getAttribute('data-spark-seed') || '17', 10);
        const chg = parseFloat(btn.getAttribute('data-chg-raw') || '0');
        drawSpark(c, seed, chg >= 0);
      });
    };
    renderSparks(document);

    // -----------------------------
    // Dummy candle chart
    // -----------------------------
    const candleCanvas = $('#candleCanvas');
    const drawCandleChart = (opts={}) => {
      const canvas = candleCanvas;
      const ctx = canvas.getContext('2d');
      if (!ctx) return;

      const dpr = Math.max(1, Math.floor(window.devicePixelRatio || 1));
      const cssW = canvas.clientWidth || 1200;
      const cssH = canvas.clientHeight || 420;
      canvas.width = Math.floor(cssW * dpr);
      canvas.height = Math.floor(cssH * dpr);
      ctx.setTransform(dpr,0,0,dpr,0,0);

      const W = cssW, H = cssH;
      ctx.clearRect(0,0,W,H);

      // background grid
      ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--panel').trim() || '#0f141a';
      ctx.fillRect(0,0,W,H);

      const grid = getComputedStyle(document.documentElement).getPropertyValue('--line').trim() || 'rgba(255,255,255,.06)';
      ctx.strokeStyle = grid;
      ctx.lineWidth = 1;
      ctx.globalAlpha = 1;
      for (let i=1;i<=5;i++){
        const y = Math.round((H/6) * i);
        ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(W,y); ctx.stroke();
      }
      for (let i=1;i<=7;i++){
        const x = Math.round((W/8) * i);
        ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x,H); ctx.stroke();
      }

      // candles
      const n = opts.n ?? 72;
      const base = opts.base ?? num($('#midPrice')?.textContent || '0');
      const volH = Math.floor(H * 0.20);
      const chartH = H - volH - 12;
      const pad = 14;
      const usableW = W - pad*2;
      const cw = usableW / n;
      const bodyW = Math.max(3, Math.floor(cw * 0.55));
      let p = base;
      const candles = [];
      for (let i=0;i<n;i++){
        const drift = Math.sin((i + (opts.seed||17)) * 0.17) * base * 0.0008;
        const shock = (Math.cos((i + (opts.seed||17)) * 0.93) * base * 0.0005);
        const o = p;
        const c = clamp(o + drift + shock + (Math.random()-0.5)*base*0.0012, base*0.93, base*1.07);
        const hi = Math.max(o,c) + Math.random()*base*0.0016;
        const lo = Math.min(o,c) - Math.random()*base*0.0016;
        const v = Math.random();
        candles.push({o,c,hi,lo,v});
        p = c;
      }
      const maxP = Math.max(...candles.map(x => x.hi));
      const minP = Math.min(...candles.map(x => x.lo));

      const yOf = (price) => {
        const t = (price - minP) / Math.max(1e-9, (maxP - minP));
        return pad + (1 - t) * (chartH - pad);
      };

      const green = getComputedStyle(document.documentElement).getPropertyValue('--green').trim() || '#0ecb81';
      const red = getComputedStyle(document.documentElement).getPropertyValue('--red').trim() || '#f6465d';
      const glow = getComputedStyle(document.documentElement).getPropertyValue('--glow').trim() || 'rgba(240,185,11,.16)';

      // subtle glow at top-left
      const g = ctx.createRadialGradient(40, 40, 10, 40, 40, 220);
      g.addColorStop(0, glow);
      g.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = g;
      ctx.fillRect(0,0,260,260);

      for (let i=0;i<n;i++){
        const x = pad + i*cw + (cw - bodyW)/2;
        const {o,c,hi,lo} = candles[i];
        const up = c >= o;
        const col = up ? green : red;
        const yo = yOf(o), yc = yOf(c);
        const yhi = yOf(hi), ylo = yOf(lo);
        // wick
        ctx.strokeStyle = col;
        ctx.globalAlpha = 0.9;
        ctx.beginPath();
        ctx.moveTo(x + bodyW/2, yhi);
        ctx.lineTo(x + bodyW/2, ylo);
        ctx.stroke();
        // body
        ctx.globalAlpha = 1;
        const top = Math.min(yo, yc);
        const bh = Math.max(2, Math.abs(yc - yo));
        ctx.fillStyle = up ? 'rgba(14,203,129,.85)' : 'rgba(246,70,93,.85)';
        ctx.fillRect(x, top, bodyW, bh);
      }

      // volume bars
      const vTop = chartH + 10;
      for (let i=0;i<n;i++){
        const x = pad + i*cw + 1;
        const {o,c,v} = candles[i];
        const up = c >= o;
        const col = up ? 'rgba(14,203,129,.25)' : 'rgba(246,70,93,.25)';
        const vh = Math.max(2, Math.floor(v * (volH - 6)));
        ctx.fillStyle = col;
        ctx.fillRect(x, vTop + (volH - vh), Math.max(2, Math.floor(cw - 2)), vh);
      }

      // right-side price labels (fake)
      ctx.font = '12px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';
      ctx.fillStyle = 'rgba(232,236,242,.72)';
      ctx.textAlign = 'right';
      for (let i=0;i<=4;i++){
        const t = i/4;
        const p = maxP*(1-t) + minP*t;
        const y = pad + t*(chartH - pad);
        ctx.fillText(fmt(p, base >= 1000 ? 2 : (base>=10?3:4)), W - 10, y+4);
      }
    };

    // initial chart draw
    const chartSeed = Math.floor(Math.random()*1000) + 7;
    drawCandleChart({ seed: chartSeed, n: 72 });
    window.addEventListener('resize', () => drawCandleChart({ seed: chartSeed, n: 72 }));

    // timeframe / indicator toggles (visual + re-render)
    $$('.toolbar [data-tf]').forEach(btn => {
      btn.addEventListener('click', () => {
        $$('.toolbar [data-tf]').forEach(x => x.classList.remove('is-active'));
        btn.classList.add('is-active');
        drawCandleChart({ seed: chartSeed + (btn.dataset.tf?.length || 1)*13, n: 72 });
        toast(`타임프레임: ${btn.dataset.tf} (더미)`, 'info');
      });
    });
    $$('.toolbar [data-ind]').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.classList.toggle('is-active');
        toast(`지표: ${btn.dataset.ind?.toUpperCase()} ${btn.classList.contains('is-active') ? 'ON' : 'OFF'} (더미)`, 'info');
      });
    });

    // -----------------------------
    // Bottom tabs (Activity)
    // -----------------------------
    const bottomTabs = $('#bottomTabs');
    const bottomPanels = $('#bottomPanels');
    bottomTabs?.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-tab]');
      if (!btn) return;
      const key = btn.dataset.tab;
      $$('#bottomTabs .tab').forEach(b => b.classList.toggle('is-active', b === btn));
      $$('#bottomPanels .tabpanel').forEach(p => p.classList.toggle('is-active', p.dataset.tabpanel === key));
    });

    // Chart mini tabs
    $$('.mini-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        const key = btn.dataset.mini;
        $$('.mini-tab').forEach(b => b.classList.toggle('is-active', b === btn));
        $$('.mini-panel').forEach(p => p.classList.toggle('is-active', p.dataset.miniPanel === key));
      });
    });

    // -----------------------------
    // Orderbook tabs
    // -----------------------------
    const obTabs = $('#obTabs');
    obTabs?.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-ob]');
      if (!btn) return;
      const key = btn.dataset.ob;
      $$('#obTabs .tab').forEach(b => b.classList.toggle('is-active', b === btn));
      $$('#obPanel [data-obpanel]').forEach(p => p.classList.toggle('is-active', p.dataset.obpanel === key));
    });

    // -----------------------------
    // Order type tabs + derived totals/fee
    // -----------------------------
    const orderTypeTabs = $('#orderTypeTabs');
    const priceInput = $('#orderPrice');
    const qtyInput = $('#orderQty');
    const totalInput = $('#orderTotal');
    const slider = $('#pctSlider');
    const feeEl = $('#feeEst');
    const stopField = $('[data-otype-only="stop"]');

    const recalc = () => {
      const p = num(priceInput?.value || 0);
      const q = num(qtyInput?.value || 0);
      const tot = p * q;
      if (totalInput) totalInput.value = fmt(tot, 2);
      if (feeEl) feeEl.textContent = `${fmt(tot * 0.0004, 4)} USDT`;
    };
    [priceInput, qtyInput].forEach(el => el?.addEventListener('input', recalc));
    recalc();

    orderTypeTabs?.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-otype]');
      if (!btn) return;
      const key = btn.dataset.otype;
      $$('#orderTypeTabs .tab').forEach(b => b.classList.toggle('is-active', b === btn));
      if (stopField) stopField.hidden = (key !== 'stop');
      if (key === 'market' && priceInput) priceInput.value = $('#midPrice')?.textContent?.trim() || priceInput.value;
      toast(`주문 타입: ${key.toUpperCase()} (더미)`, 'info');
      recalc();
    });

    // slider + chips
    const applyPct = (pct) => {
      pct = clamp(pct, 0, 100);
      if (slider) slider.value = String(pct);
      // 가짜 로직: pct에 비례해 수량을 변경
      const baseQ = 0.25 + (pct/100) * 1.75;
      if (qtyInput) qtyInput.value = fmt(baseQ, 4);
      recalc();
    };
    slider?.addEventListener('input', () => applyPct(parseInt(slider.value, 10)));
    $$('.chip[data-pct]').forEach(chip => chip.addEventListener('click', () => applyPct(parseInt(chip.dataset.pct, 10))));

    // buy/sell buttons -> toast only
    $('#btnBuy')?.addEventListener('click', () => toast('주문이 제출되었습니다 (더미) — Buy/Long', 'up'));
    $('#btnSell')?.addEventListener('click', () => toast('주문이 제출되었습니다 (더미) — Sell/Short', 'down'));

    // -----------------------------
    // Symbol search + selection (no navigation, purely visual)
    // -----------------------------
    const symbolsRoot = $('#symbolsList');
    const symbolsEmpty = $('#symbolsEmpty');
    const filterSymbols = (q) => {
      q = (q || '').trim().toUpperCase();
      let shown = 0;
      $$('.symrow', symbolsRoot).forEach(row => {
        const sym = row.dataset.symbol || '';
        const ok = !q || sym.includes(q);
        row.hidden = !ok;
        if (ok) shown++;
      });
      if (symbolsEmpty) symbolsEmpty.hidden = shown !== 0;
    };
    $('#symbolSearchLeft')?.addEventListener('input', (e) => filterSymbols(e.target.value));
    $('#symbolSearchClear')?.addEventListener('click', () => { const i = $('#symbolSearchLeft'); if (i) { i.value=''; filterSymbols(''); i.focus(); }});
    $('#symbolSearchTop')?.addEventListener('input', (e) => filterSymbols(e.target.value));
    $('#searchGo')?.addEventListener('click', () => toast('검색은 더미 동작입니다.', 'info'));

    // click symbol -> update header visually + toast
    const pickSymbol = (btn) => {
      if (!btn) return;
      $$('.symrow', symbolsRoot).forEach(x => x.classList.toggle('is-active', x === btn));
      const sym = btn.dataset.symbol || '—';
      $('#pairSymbol').textContent = sym;
      toast(`심볼 전환: ${sym} (더미)`, 'info');
    };
    symbolsRoot?.addEventListener('click', (e) => {
      const btn = e.target.closest('.symrow');
      if (!btn) return;
      pickSymbol(btn);
    });

    // -----------------------------
    // Mobile drawer: clone symbol list
    // -----------------------------
    const drawer = $('#symbolsDrawer');
    const drawerList = $('#symbolsDrawerList');
    const openDrawer = () => {
      if (!drawer) return;
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden','false');
      document.body.classList.add('is-modal');
      // clone list once per open (simple)
      if (drawerList) {
        drawerList.innerHTML = '';
        const cloned = $$('.symrow', symbolsRoot).map(x => x.cloneNode(true));
        cloned.forEach(n => {
          n.classList.remove('is-active'); // purely visual in drawer
          drawerList.appendChild(n);
        });
        renderSparks(drawerList);
      }
      setTimeout(() => $('#symbolSearchDrawer')?.focus(), 60);
    };
    const closeDrawer = () => {
      if (!drawer) return;
      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden','true');
      document.body.classList.remove('is-modal');
    };
    $('#openSymbols')?.addEventListener('click', openDrawer);
    $$('[data-close-drawer="1"]').forEach(x => x.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });

    const drawerEmpty = $('#drawerEmpty');
    const filterDrawer = (q) => {
      q = (q || '').trim().toUpperCase();
      let shown = 0;
      $$('.symrow', drawerList).forEach(row => {
        const sym = row.dataset.symbol || '';
        const ok = !q || sym.includes(q);
        row.hidden = !ok;
        if (ok) shown++;
      });
      if (drawerEmpty) drawerEmpty.hidden = shown !== 0;
    };
    $('#symbolSearchDrawer')?.addEventListener('input', (e) => filterDrawer(e.target.value));
    $('#symbolSearchDrawerClear')?.addEventListener('click', () => { const i = $('#symbolSearchDrawer'); if (i) { i.value=''; filterDrawer(''); i.focus(); }});
    drawerList?.addEventListener('click', (e) => {
      const btn = e.target.closest('.symrow');
      if (!btn) return;
      const sym = btn.dataset.symbol || '—';
      $('#pairSymbol').textContent = sym;
      toast(`심볼 전환: ${sym} (더미)`, 'info');
      closeDrawer();
    });

    // -----------------------------
    // Mobile bottom nav: show/hide sections
    // -----------------------------
    const mnav = $('#mobileNav');
    const layoutRoot = $('#layoutRoot');
    const setMobileTab = (key) => {
      if (!layoutRoot) return;
      layoutRoot.dataset.mtab = key;
      $$('.mnav__btn', mnav).forEach(b => b.classList.toggle('is-active', b.dataset.mtab === key));
    };
    mnav?.addEventListener('click', (e) => {
      const btn = e.target.closest('.mnav__btn');
      if (!btn) return;
      setMobileTab(btn.dataset.mtab);
    });
    setMobileTab('chart');
  })();
  </script>
</body>
</html>

