<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FUTX | 선물거래 (디자인 시안)</title>
    <link rel="stylesheet" href="css/trade_design3.css?v=1">
</head>
<body>

<?php
// ============================================
// 더미데이터 생성 (실제 DB/API 연동 절대 금지)
// ============================================

// 심볼 목록 (70개)
$symbols = [];
$coins = [
    ['BTC', 'Bitcoin', 52340.50, 2.43],
    ['ETH', 'Ethereum', 3125.80, 4.18],
    ['SOL', 'Solana', 98.25, -1.32],
    ['XRP', 'Ripple', 0.6234, 5.76],
    ['DOGE', 'Dogecoin', 0.0923, -2.14],
    ['ADA', 'Cardano', 0.4568, 3.21],
    ['AVAX', 'Avalanche', 34.56, 1.89],
    ['DOT', 'Polkadot', 6.78, -0.54],
    ['MATIC', 'Polygon', 0.8934, 2.67],
    ['LINK', 'Chainlink', 14.32, 1.23],
    ['UNI', 'Uniswap', 7.89, -1.87],
    ['ATOM', 'Cosmos', 9.45, 0.92],
    ['LTC', 'Litecoin', 89.34, 1.56],
    ['BCH', 'Bitcoin Cash', 234.67, -2.34],
    ['NEAR', 'Near Protocol', 3.45, 3.78],
    ['APT', 'Aptos', 8.92, 4.56],
    ['ARB', 'Arbitrum', 1.234, 2.34],
    ['OP', 'Optimism', 2.345, 1.78],
    ['IMX', 'Immutable X', 1.567, -1.23],
    ['INJ', 'Injective', 23.45, 5.67],
    ['TIA', 'Celestia', 12.34, 3.45],
    ['SEI', 'Sei', 0.456, 2.89],
    ['SUI', 'Sui', 0.789, -0.98],
    ['FIL', 'Filecoin', 5.67, 1.45],
    ['GRT', 'The Graph', 0.234, 2.34],
    ['RUNE', 'THORChain', 4.56, -1.67],
    ['AAVE', 'Aave', 98.76, 3.21],
    ['MKR', 'Maker', 1567.89, 1.89],
    ['SNX', 'Synthetix', 2.89, -0.76],
    ['CRV', 'Curve', 0.987, 1.23],
    ['LDO', 'Lido DAO', 2.34, 2.78],
    ['PEPE', 'Pepe', 0.00000123, 8.92],
    ['WLD', 'Worldcoin', 3.45, 4.56],
    ['FET', 'Fetch.ai', 0.678, 2.34],
    ['RNDR', 'Render', 5.67, 3.21],
    ['AGIX', 'SingularityNET', 0.456, 1.89],
    ['OCEAN', 'Ocean Protocol', 0.567, -1.23],
    ['AR', 'Arweave', 8.92, 2.67],
    ['SAND', 'The Sandbox', 0.456, -2.14],
    ['MANA', 'Decentraland', 0.567, 1.78],
    ['AXS', 'Axie Infinity', 7.89, -0.92],
    ['GMT', 'STEPN', 0.234, 3.45],
    ['APE', 'ApeCoin', 1.89, -1.56],
    ['GALA', 'Gala', 0.0345, 2.89],
    ['ENJ', 'Enjin Coin', 0.389, 1.23],
    ['CHZ', 'Chiliz', 0.0912, -0.87],
    ['THETA', 'Theta Network', 1.23, 2.34],
    ['FTM', 'Fantom', 0.456, 3.21],
    ['ALGO', 'Algorand', 0.234, 1.45],
    ['EOS', 'EOS', 0.789, -1.23],
    ['XTZ', 'Tezos', 0.987, 2.67],
    ['EGLD', 'MultiversX', 45.67, 1.89],
    ['FLOW', 'Flow', 0.876, -0.56],
    ['ICP', 'Internet Computer', 5.67, 3.45],
    ['XLM', 'Stellar', 0.123, 2.14],
    ['VET', 'VeChain', 0.0234, 1.78],
    ['HBAR', 'Hedera', 0.0678, -1.34],
    ['QNT', 'Quant', 123.45, 2.89],
    ['XMR', 'Monero', 156.78, 1.56],
    ['KAS', 'Kaspa', 0.0912, 4.23],
    ['STX', 'Stacks', 0.789, 2.45],
    ['KAVA', 'Kava', 0.876, -1.12],
    ['COMP', 'Compound', 56.78, 1.89],
    ['YFI', 'yearn.finance', 7890.12, 2.34],
    ['1INCH', '1inch', 0.456, -0.78],
    ['SUSHI', 'SushiSwap', 1.23, 1.67],
    ['BAL', 'Balancer', 3.45, 2.11],
    ['ZRX', '0x', 0.345, -1.45],
    ['UMA', 'UMA', 2.34, 1.98],
    ['BNT', 'Bancor', 0.567, -0.89]
];

foreach ($coins as $coin) {
    $symbols[] = [
        'symbol' => $coin[0] . 'USDT',
        'name' => $coin[1],
        'price' => $coin[2],
        'change' => $coin[3],
        'volume' => rand(10000000, 500000000),
        'fav' => rand(0, 10) > 7
    ];
}

// 오더북 더미 (매도 30줄, 매수 30줄)
$currentPrice = 52340.50;
$asks = [];
for ($i = 0; $i < 30; $i++) {
    $price = $currentPrice + ($i + 1) * rand(5, 25) / 10;
    $qty = rand(100, 5000) / 100;
    $asks[] = [
        'price' => number_format($price, 2, '.', ''),
        'qty' => number_format($qty, 3, '.', ''),
        'total' => number_format($price * $qty, 2, '.', ''),
        'depth' => rand(20, 100)
    ];
}

$bids = [];
for ($i = 0; $i < 30; $i++) {
    $price = $currentPrice - ($i + 1) * rand(5, 25) / 10;
    $qty = rand(100, 5000) / 100;
    $bids[] = [
        'price' => number_format($price, 2, '.', ''),
        'qty' => number_format($qty, 3, '.', ''),
        'total' => number_format($price * $qty, 2, '.', ''),
        'depth' => rand(20, 100)
    ];
}

// 최근체결 더미 (60건)
$trades = [];
for ($i = 0; $i < 60; $i++) {
    $side = rand(0, 1) ? 'buy' : 'sell';
    $price = $currentPrice + rand(-100, 100);
    $qty = rand(10, 500) / 100;
    $trades[] = [
        'price' => number_format($price, 2, '.', ''),
        'qty' => number_format($qty, 3, '.', ''),
        'time' => date('H:i:s', time() - $i * rand(1, 10)),
        'side' => $side
    ];
}

// 오픈오더 더미 (20건)
$openOrders = [];
for ($i = 0; $i < 20; $i++) {
    $side = rand(0, 1) ? 'Buy' : 'Sell';
    $type = ['Limit', 'Market', 'Stop'][rand(0, 2)];
    $openOrders[] = [
        'time' => date('Y-m-d H:i:s', time() - $i * 3600),
        'symbol' => $symbols[rand(0, 9)]['symbol'],
        'type' => $type,
        'side' => $side,
        'price' => number_format(rand(100, 60000) + rand(0, 99) / 100, 2, '.', ''),
        'qty' => number_format(rand(10, 500) / 100, 3, '.', ''),
        'filled' => rand(0, 100) . '%'
    ];
}

// 주문 히스토리 더미 (50건)
$orderHistory = [];
for ($i = 0; $i < 50; $i++) {
    $side = rand(0, 1) ? 'Buy' : 'Sell';
    $status = ['Filled', 'Cancelled', 'Expired'][rand(0, 2)];
    $orderHistory[] = [
        'time' => date('Y-m-d H:i:s', time() - $i * 7200),
        'symbol' => $symbols[rand(0, 19)]['symbol'],
        'type' => ['Limit', 'Market'][rand(0, 1)],
        'side' => $side,
        'price' => number_format(rand(100, 60000) + rand(0, 99) / 100, 2, '.', ''),
        'qty' => number_format(rand(10, 500) / 100, 3, '.', ''),
        'filled' => rand(0, 100) . '%',
        'status' => $status
    ];
}

// 포지션 더미 (10건)
$positions = [];
for ($i = 0; $i < 10; $i++) {
    $side = rand(0, 1) ? 'Long' : 'Short';
    $entry = rand(10000, 60000) + rand(0, 99) / 100;
    $mark = $entry + rand(-1000, 1000);
    $qty = rand(10, 200) / 100;
    $pnl = ($mark - $entry) * $qty * ($side === 'Long' ? 1 : -1);
    $roe = ($pnl / ($entry * $qty / 10)) * 100;
    
    $positions[] = [
        'symbol' => $symbols[rand(0, 19)]['symbol'],
        'side' => $side,
        'size' => number_format($qty, 3, '.', ''),
        'entry' => number_format($entry, 2, '.', ''),
        'mark' => number_format($mark, 2, '.', ''),
        'liq' => number_format($entry * ($side === 'Long' ? 0.85 : 1.15), 2, '.', ''),
        'margin' => number_format($entry * $qty / 10, 2, '.', ''),
        'pnl' => number_format($pnl, 2, '.', ''),
        'roe' => number_format($roe, 2, '.', '')
    ];
}

// 자산 더미 (8건)
$assets = [];
$assetCoins = [
    ['USDT', 'Tether', 125840.50],
    ['BTC', 'Bitcoin', 2.45678],
    ['ETH', 'Ethereum', 15.8934],
    ['SOL', 'Solana', 234.56],
    ['BNB', 'BNB', 45.23],
    ['USDC', 'USD Coin', 8920.30],
    ['DAI', 'Dai', 5230.80],
    ['BUSD', 'Binance USD', 3450.20]
];

foreach ($assetCoins as $asset) {
    $usdValue = $asset[2] * (isset($symbols[0]) ? rand(1, 50000) / 10 : 1);
    $assets[] = [
        'coin' => $asset[0],
        'name' => $asset[1],
        'total' => number_format($asset[2], 4, '.', ','),
        'available' => number_format($asset[2] * 0.92, 4, '.', ','),
        'in_order' => number_format($asset[2] * 0.08, 4, '.', ','),
        'usd_value' => number_format($usdValue, 2, '.', ',')
    ];
}

?>

<!-- Toast 컨테이너 -->
<div class="toast-container" id="toastContainer"></div>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar__left">
        <!-- Logo -->
        <a href="#" class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <span class="logo__text">FUTX</span>
        </a>
        
        <!-- Current Symbol -->
        <button class="symbol-btn" onclick="toggleDrawer('symbolDrawer')">
            <span class="coin-badge" style="background:#f7931a">₿</span>
            <div class="symbol-info">
                <div class="symbol-main">BTCUSDT <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor"><path d="M6 8l-4-4h8l-4 4z"/></svg></div>
                <div class="symbol-sub">무기한 선물</div>
            </div>
        </button>
    </div>
    
    <div class="topbar__mid">
        <!-- Price & Stats -->
        <div class="price-display">
            <div class="price-main is-up">52,340.50</div>
            <div class="price-change is-up">+1,265.30 (+2.48%)</div>
        </div>
        
        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-label">24h 고가</span>
                <span class="stat-value">53,120.00</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">24h 저가</span>
                <span class="stat-value">50,890.50</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">24h 거래량(BTC)</span>
                <span class="stat-value">8,542.32</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">24h 거래량(USDT)</span>
                <span class="stat-value">445.2M</span>
            </div>
        </div>
    </div>
    
    <div class="topbar__right">
        <!-- Theme Toggle -->
        <button class="icon-btn" title="테마">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        </button>
        
        <!-- Language -->
        <button class="icon-btn" title="언어">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </button>
        
        <!-- Settings -->
        <button class="icon-btn" title="설정">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/></svg>
        </button>
    </div>
</div>

<!-- Main Layout -->
<div class="main-layout" id="mainLayout" data-mobile-tab="chart">
    
    <!-- Left: Symbols Panel (Desktop Only) -->
    <aside class="symbols-panel desktop-only">
        <div class="panel-header">
            <div class="panel-title">마켓</div>
            <div class="panel-actions">
                <button class="icon-btn-sm" title="즐겨찾기">⭐</button>
            </div>
        </div>
        
        <div class="panel-toolbar">
            <div class="tabs-mini">
                <button class="tab-mini is-active">즐겨찾기</button>
                <button class="tab-mini">USDT</button>
                <button class="tab-mini">BTC</button>
                <button class="tab-mini">알트</button>
            </div>
        </div>
        
        <div class="symbols-search">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" placeholder="검색" class="search-input">
        </div>
        
        <div class="symbols-list-header">
            <div class="col-sym">심볼</div>
            <div class="col-price">가격</div>
            <div class="col-change">24h%</div>
            <div class="col-chart">차트</div>
        </div>
        
        <div class="symbols-list scroll">
            <?php foreach (array_slice($symbols, 0, 30) as $sym): ?>
            <button class="symbol-row <?= $sym['symbol'] === 'BTCUSDT' ? 'is-active' : '' ?>">
                <div class="col-star"><?= $sym['fav'] ? '⭐' : '☆' ?></div>
                <div class="col-sym"><?= substr($sym['symbol'], 0, -4) ?></div>
                <div class="col-price mono"><?= number_format($sym['price'], 2, '.', ',') ?></div>
                <div class="col-change mono <?= $sym['change'] >= 0 ? 'is-up' : 'is-down' ?>"><?= $sym['change'] >= 0 ? '+' : '' ?><?= number_format($sym['change'], 2) ?>%</div>
                <div class="col-chart">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <?php
                        $points = [];
                        for ($i = 0; $i < 12; $i++) {
                            $x = $i * 5;
                            $y = rand(2, 18);
                            $points[] = "$x,$y";
                        }
                        ?>
                        <polyline points="<?= implode(' ', $points) ?>" fill="none" stroke="<?= $sym['change'] >= 0 ? 'rgba(14,203,129,0.6)' : 'rgba(246,70,93,0.6)' ?>" stroke-width="1.5"/>
                    </svg>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </aside>
    
    <!-- Center: Chart & Bottom Tabs -->
    <div class="center-panel">
        
        <!-- Chart Section -->
        <div class="chart-panel">
            <div class="chart-toolbar">
                <div class="timeframes">
                    <button class="tf-btn">1m</button>
                    <button class="tf-btn">5m</button>
                    <button class="tf-btn is-active">15m</button>
                    <button class="tf-btn">1h</button>
                    <button class="tf-btn">4h</button>
                    <button class="tf-btn">1D</button>
                </div>
                
                <div class="indicators">
                    <button class="ind-btn">MA</button>
                    <button class="ind-btn is-active">EMA</button>
                    <button class="ind-btn">BOLL</button>
                    <button class="ind-btn">RSI</button>
                    <button class="ind-btn">MACD</button>
                </div>
            </div>
            
            <div class="chart-container">
                <canvas id="chartCanvas" width="1200" height="500"></canvas>
                
                <!-- Chart HUD -->
                <div class="chart-hud">
                    <div class="hud-row">
                        <span class="hud-label">O</span>
                        <span class="hud-value mono">51,234.50</span>
                    </div>
                    <div class="hud-row">
                        <span class="hud-label">H</span>
                        <span class="hud-value mono is-up">53,120.00</span>
                    </div>
                    <div class="hud-row">
                        <span class="hud-label">L</span>
                        <span class="hud-value mono is-down">50,890.50</span>
                    </div>
                    <div class="hud-row">
                        <span class="hud-label">C</span>
                        <span class="hud-value mono">52,340.50</span>
                    </div>
                    <div class="hud-row">
                        <span class="hud-label">Vol</span>
                        <span class="hud-value mono">8,542.32</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Tabs Panel -->
        <div class="bottom-panel">
            <div class="bottom-tabs">
                <button class="btab is-active" data-tab="open-orders">오픈오더 <span class="badge">20</span></button>
                <button class="btab" data-tab="order-history">주문내역 <span class="badge">50</span></button>
                <button class="btab" data-tab="trade-history">거래내역</button>
                <button class="btab" data-tab="positions">포지션 <span class="badge">10</span></button>
                <button class="btab" data-tab="assets">자산</button>
            </div>
            
            <div class="bottom-content scroll">
                <!-- Open Orders -->
                <div class="tab-content is-active" id="open-orders">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>시간</th>
                                <th>심볼</th>
                                <th>타입</th>
                                <th>방향</th>
                                <th class="tar">가격</th>
                                <th class="tar">수량</th>
                                <th class="tar">체결률</th>
                                <th class="tac">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($openOrders as $order): ?>
                            <tr>
                                <td class="muted"><?= $order['time'] ?></td>
                                <td class="mono fw-700"><?= $order['symbol'] ?></td>
                                <td><?= $order['type'] ?></td>
                                <td class="<?= $order['side'] === 'Buy' ? 'is-up' : 'is-down' ?> fw-700"><?= $order['side'] ?></td>
                                <td class="mono tar"><?= $order['price'] ?></td>
                                <td class="mono tar"><?= $order['qty'] ?></td>
                                <td class="mono tar muted"><?= $order['filled'] ?></td>
                                <td class="tac">
                                    <button class="btn-cancel" onclick="showToast('주문 취소 (더미)', 'info')">취소</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Order History -->
                <div class="tab-content" id="order-history">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>시간</th>
                                <th>심볼</th>
                                <th>타입</th>
                                <th>방향</th>
                                <th class="tar">가격</th>
                                <th class="tar">수량</th>
                                <th class="tar">체결률</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderHistory as $order): ?>
                            <tr>
                                <td class="muted"><?= $order['time'] ?></td>
                                <td class="mono fw-700"><?= $order['symbol'] ?></td>
                                <td><?= $order['type'] ?></td>
                                <td class="<?= $order['side'] === 'Buy' ? 'is-up' : 'is-down' ?> fw-700"><?= $order['side'] ?></td>
                                <td class="mono tar"><?= $order['price'] ?></td>
                                <td class="mono tar"><?= $order['qty'] ?></td>
                                <td class="mono tar muted"><?= $order['filled'] ?></td>
                                <td class="muted"><?= $order['status'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Trade History -->
                <div class="tab-content" id="trade-history">
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <div class="empty-text">거래내역이 없습니다</div>
                    </div>
                </div>
                
                <!-- Positions -->
                <div class="tab-content" id="positions">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>심볼</th>
                                <th>방향</th>
                                <th class="tar">수량</th>
                                <th class="tar">진입가</th>
                                <th class="tar">마크가</th>
                                <th class="tar">청산가</th>
                                <th class="tar">증거금</th>
                                <th class="tar">PNL (ROE%)</th>
                                <th class="tac">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($positions as $pos): ?>
                            <tr>
                                <td class="mono fw-700"><?= $pos['symbol'] ?></td>
                                <td class="<?= $pos['side'] === 'Long' ? 'is-up' : 'is-down' ?> fw-700"><?= $pos['side'] ?></td>
                                <td class="mono tar"><?= $pos['size'] ?></td>
                                <td class="mono tar"><?= $pos['entry'] ?></td>
                                <td class="mono tar"><?= $pos['mark'] ?></td>
                                <td class="mono tar muted"><?= $pos['liq'] ?></td>
                                <td class="mono tar"><?= $pos['margin'] ?></td>
                                <td class="mono tar <?= floatval($pos['pnl']) >= 0 ? 'is-up' : 'is-down' ?> fw-700">
                                    <?= floatval($pos['pnl']) >= 0 ? '+' : '' ?><?= $pos['pnl'] ?>
                                    <span class="muted">(<?= floatval($pos['roe']) >= 0 ? '+' : '' ?><?= $pos['roe'] ?>%)</span>
                                </td>
                                <td class="tac">
                                    <button class="btn-close" onclick="showToast('포지션 청산 (더미)', 'info')">청산</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Assets -->
                <div class="tab-content" id="assets">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>코인</th>
                                <th class="tar">총 수량</th>
                                <th class="tar">사용 가능</th>
                                <th class="tar">주문 중</th>
                                <th class="tar">USD 가치</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td>
                                    <div class="asset-coin">
                                        <span class="coin-icon"><?= substr($asset['coin'], 0, 1) ?></span>
                                        <div>
                                            <div class="fw-700"><?= $asset['coin'] ?></div>
                                            <div class="muted fs-11"><?= $asset['name'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="mono tar"><?= $asset['total'] ?></td>
                                <td class="mono tar"><?= $asset['available'] ?></td>
                                <td class="mono tar muted"><?= $asset['in_order'] ?></td>
                                <td class="mono tar fw-700"><?= $asset['usd_value'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Right: Orderbook + Order Panel -->
    <aside class="right-panel">
        
        <!-- Orderbook & Recent Trades Tabs -->
        <div class="ob-panel">
            <div class="ob-tabs">
                <button class="ob-tab is-active" data-obtab="orderbook">오더북</button>
                <button class="ob-tab" data-obtab="trades">최근체결</button>
            </div>
            
            <!-- Orderbook -->
            <div class="ob-content is-active" id="orderbook">
                <div class="ob-header">
                    <div class="col">가격(USDT)</div>
                    <div class="col tac">수량(BTC)</div>
                    <div class="col tar">합계</div>
                </div>
                
                <div class="ob-asks scroll">
                    <?php foreach (array_slice($asks, 0, 15) as $ask): ?>
                    <div class="ob-row ob-ask" style="--fill:<?= $ask['depth'] ?>%">
                        <div class="col price is-down mono"><?= $ask['price'] ?></div>
                        <div class="col qty tac mono"><?= $ask['qty'] ?></div>
                        <div class="col total tar mono muted"><?= $ask['total'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="ob-spread">
                    <div class="spread-price is-up mono">52,340.50</div>
                    <div class="spread-info muted">스프레드: 0.50 (0.001%)</div>
                </div>
                
                <div class="ob-bids scroll">
                    <?php foreach (array_slice($bids, 0, 15) as $bid): ?>
                    <div class="ob-row ob-bid" style="--fill:<?= $bid['depth'] ?>%">
                        <div class="col price is-up mono"><?= $bid['price'] ?></div>
                        <div class="col qty tac mono"><?= $bid['qty'] ?></div>
                        <div class="col total tar mono muted"><?= $bid['total'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Trades -->
            <div class="ob-content" id="trades">
                <div class="ob-header">
                    <div class="col">가격(USDT)</div>
                    <div class="col tac">수량(BTC)</div>
                    <div class="col tar">시간</div>
                </div>
                
                <div class="trades-list scroll">
                    <?php foreach ($trades as $trade): ?>
                    <div class="trade-row">
                        <div class="col price <?= $trade['side'] === 'buy' ? 'is-up' : 'is-down' ?> mono"><?= $trade['price'] ?></div>
                        <div class="col qty tac mono"><?= $trade['qty'] ?></div>
                        <div class="col time tar muted"><?= $trade['time'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Panel -->
        <div class="order-panel">
            <div class="order-tabs">
                <button class="order-tab is-active">Limit</button>
                <button class="order-tab">Market</button>
                <button class="order-tab">Stop</button>
            </div>
            
            <div class="order-mode">
                <button class="mode-btn">
                    <span>격리</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor"><path d="M6 8l-4-4h8l-4 4z"/></svg>
                </button>
                <button class="leverage-btn">
                    <span class="leverage-icon">⚡</span>
                    <span>10x</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor"><path d="M6 8l-4-4h8l-4 4z"/></svg>
                </button>
            </div>
            
            <div class="order-form">
                <!-- Buy/Sell Toggle -->
                <div class="side-toggle">
                    <button class="side-btn buy-btn is-active">롱 / 매수</button>
                    <button class="side-btn sell-btn">숏 / 매도</button>
                </div>
                
                <!-- Balance -->
                <div class="balance-row">
                    <span class="muted">사용가능</span>
                    <span class="mono fw-700">125,840.50 USDT</span>
                </div>
                
                <!-- Price Input -->
                <div class="input-field">
                    <label>가격</label>
                    <div class="input-wrap">
                        <input type="text" class="input mono" value="52,340.50" placeholder="0.00">
                        <button class="input-btn" onclick="showToast('마지막 가격 적용 (더미)', 'info')">Last</button>
                    </div>
                </div>
                
                <!-- Quantity Input -->
                <div class="input-field">
                    <label>수량</label>
                    <div class="input-wrap">
                        <input type="text" class="input mono" placeholder="0.000">
                        <span class="input-unit">BTC</span>
                    </div>
                </div>
                
                <!-- Amount Slider -->
                <div class="slider-field">
                    <input type="range" class="slider" min="0" max="100" value="0">
                    <div class="slider-labels">
                        <span>0%</span>
                        <span>25%</span>
                        <span>50%</span>
                        <span>75%</span>
                        <span>100%</span>
                    </div>
                </div>
                
                <!-- Total -->
                <div class="total-row">
                    <span class="muted">총액</span>
                    <span class="mono fw-700">0.00 USDT</span>
                </div>
                
                <!-- Order Info -->
                <div class="order-info">
                    <div class="info-row">
                        <span class="muted">수수료</span>
                        <span class="mono">0.075%</span>
                    </div>
                    <div class="info-row">
                        <span class="muted">추정 청산가</span>
                        <span class="mono is-down">47,106.45</span>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="order-actions">
                    <button class="order-submit buy-submit" onclick="showToast('롱 매수 주문 제출 (더미)', 'success')">
                        <span class="fw-800">매수 / 롱</span>
                    </button>
                </div>
            </div>
        </div>
        
    </aside>
    
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav">
    <button class="mnav-btn is-active" onclick="switchMobileTab('chart')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        <span>차트</span>
    </button>
    <button class="mnav-btn" onclick="switchMobileTab('orderbook')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        <span>오더북</span>
    </button>
    <button class="mnav-btn" onclick="switchMobileTab('order')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span>주문</span>
    </button>
    <button class="mnav-btn" onclick="switchMobileTab('positions')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        <span>포지션</span>
    </button>
</nav>

<!-- Symbol Drawer (Mobile) -->
<div class="drawer" id="symbolDrawer">
    <div class="drawer-backdrop" onclick="toggleDrawer('symbolDrawer')"></div>
    <div class="drawer-panel">
        <div class="drawer-header">
            <div class="drawer-title">마켓</div>
            <button class="icon-btn" onclick="toggleDrawer('symbolDrawer')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        
        <div class="drawer-body">
            <div class="symbols-search">
                <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" placeholder="검색" class="search-input">
            </div>
            
            <div class="tabs-mini">
                <button class="tab-mini is-active">즐겨찾기</button>
                <button class="tab-mini">USDT</button>
                <button class="tab-mini">BTC</button>
            </div>
            
            <div class="drawer-symbols scroll">
                <?php foreach (array_slice($symbols, 0, 40) as $sym): ?>
                <button class="symbol-row-mobile">
                    <div class="row-left">
                        <span class="fav-icon"><?= $sym['fav'] ? '⭐' : '☆' ?></span>
                        <div>
                            <div class="fw-700"><?= substr($sym['symbol'], 0, -4) ?></div>
                            <div class="muted fs-11">Vol <?= number_format($sym['volume'] / 1000000, 1) ?>M</div>
                        </div>
                    </div>
                    <div class="row-right">
                        <div class="mono fw-700"><?= number_format($sym['price'], 2) ?></div>
                        <div class="mono <?= $sym['change'] >= 0 ? 'is-up' : 'is-down' ?> fs-12"><?= $sym['change'] >= 0 ? '+' : '' ?><?= number_format($sym['change'], 2) ?>%</div>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// 더미 차트 렌더링 (캔버스)
// ============================================
function renderDummyChart() {
    const canvas = document.getElementById('chartCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const w = canvas.width;
    const h = canvas.height;
    
    // 배경
    ctx.fillStyle = 'rgba(0,0,0,0.1)';
    ctx.fillRect(0, 0, w, h);
    
    // 그리드
    ctx.strokeStyle = 'rgba(255,255,255,0.04)';
    ctx.lineWidth = 1;
    for (let i = 0; i < 10; i++) {
        ctx.beginPath();
        ctx.moveTo(0, (h / 10) * i);
        ctx.lineTo(w, (h / 10) * i);
        ctx.stroke();
        
        ctx.beginPath();
        ctx.moveTo((w / 20) * i, 0);
        ctx.lineTo((w / 20) * i, h);
        ctx.stroke();
    }
    
    // 더미 캔들스틱 생성
    const candleCount = 80;
    const candleWidth = w / candleCount - 4;
    const basePrice = 52000;
    const priceRange = 1500;
    
    let currentClose = basePrice + Math.random() * 500 - 250;
    
    for (let i = 0; i < candleCount; i++) {
        const x = i * (w / candleCount) + 2;
        
        const open = currentClose;
        const close = currentClose + Math.random() * 400 - 200;
        const high = Math.max(open, close) + Math.random() * 150;
        const low = Math.min(open, close) - Math.random() * 150;
        
        currentClose = close;
        
        const isUp = close >= open;
        const color = isUp ? 'rgba(14, 203, 129, 0.85)' : 'rgba(246, 70, 93, 0.85)';
        
        // 가격을 y 좌표로 변환
        const normalize = (price) => {
            return h - ((price - (basePrice - priceRange / 2)) / priceRange) * h;
        };
        
        const yOpen = normalize(open);
        const yClose = normalize(close);
        const yHigh = normalize(high);
        const yLow = normalize(low);
        
        // 심지
        ctx.strokeStyle = color;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x + candleWidth / 2, yHigh);
        ctx.lineTo(x + candleWidth / 2, yLow);
        ctx.stroke();
        
        // 몸통
        ctx.fillStyle = color;
        const bodyHeight = Math.abs(yClose - yOpen);
        ctx.fillRect(x, Math.min(yOpen, yClose), candleWidth, Math.max(bodyHeight, 2));
    }
    
    // 현재가 라인
    const currentY = normalize(52340.50);
    ctx.strokeStyle = 'rgba(240, 185, 11, 0.6)';
    ctx.lineWidth = 2;
    ctx.setLineDash([8, 4]);
    ctx.beginPath();
    ctx.moveTo(0, currentY);
    ctx.lineTo(w, currentY);
    ctx.stroke();
    ctx.setLineDash([]);
    
    // 가격 레이블
    ctx.fillStyle = 'rgba(240, 185, 11, 0.9)';
    ctx.fillRect(w - 120, currentY - 14, 110, 28);
    ctx.fillStyle = '#000';
    ctx.font = 'bold 14px monospace';
    ctx.fillText('52,340.50', w - 110, currentY + 5);
}

// ============================================
// 탭 전환
// ============================================
function switchTab(tabName) {
    document.querySelectorAll('.btab').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.tab === tabName);
    });
    
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.toggle('is-active', content.id === tabName);
    });
}

function switchOBTab(tabName) {
    document.querySelectorAll('.ob-tab').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.obtab === tabName);
    });
    
    document.querySelectorAll('.ob-content').forEach(content => {
        content.classList.toggle('is-active', content.id === tabName);
    });
}

function switchMobileTab(tabName) {
    const layout = document.getElementById('mainLayout');
    layout.setAttribute('data-mobile-tab', tabName);
    
    document.querySelectorAll('.mnav-btn').forEach(btn => {
        btn.classList.remove('is-active');
    });
    event.currentTarget.classList.add('is-active');
}

// ============================================
// Drawer Toggle
// ============================================
function toggleDrawer(drawerId) {
    const drawer = document.getElementById(drawerId);
    if (drawer) {
        drawer.classList.toggle('is-open');
    }
}

// ============================================
// Toast 알림
// ============================================
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            ${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}
        </div>
        <div class="toast-message">${message}</div>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => toast.classList.add('is-show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('is-show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ============================================
// Side Toggle (Buy/Sell)
// ============================================
document.querySelectorAll('.side-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.side-btn').forEach(b => b.classList.remove('is-active'));
        this.classList.add('is-active');
        
        const isBuy = this.classList.contains('buy-btn');
        const submitBtn = document.querySelector('.order-submit');
        
        if (isBuy) {
            submitBtn.className = 'order-submit buy-submit';
            submitBtn.innerHTML = '<span class="fw-800">매수 / 롱</span>';
        } else {
            submitBtn.className = 'order-submit sell-submit';
            submitBtn.innerHTML = '<span class="fw-800">매도 / 숏</span>';
        }
    });
});

// ============================================
// Bottom Tabs Event
// ============================================
document.querySelectorAll('.btab').forEach(btn => {
    btn.addEventListener('click', function() {
        switchTab(this.dataset.tab);
    });
});

document.querySelectorAll('.ob-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        switchOBTab(this.dataset.obtab);
    });
});

// ============================================
// 초기화
// ============================================
window.addEventListener('DOMContentLoaded', () => {
    renderDummyChart();
    
    // 차트 리사이즈
    window.addEventListener('resize', () => {
        const canvas = document.getElementById('chartCanvas');
        if (canvas) {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            renderDummyChart();
        }
    });
    
    // 실시간 가격 업데이트 시뮬레이션
    setInterval(() => {
        const priceEl = document.querySelector('.price-main');
        if (priceEl) {
            const current = parseFloat(priceEl.textContent.replace(/,/g, ''));
            const change = (Math.random() - 0.5) * 10;
            const newPrice = current + change;
            priceEl.textContent = newPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            priceEl.className = change >= 0 ? 'price-main is-up' : 'price-main is-down';
        }
    }, 2000);
});
</script>

</body>
</html>
