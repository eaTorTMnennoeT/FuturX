<?php
// Dummy data for design mock
$orderbookAsks = [
    ['price' => '3,091.20', 'qty' => '12.45', 'total' => '38.5'],
    ['price' => '3,091.10', 'qty' => '4.12',  'total' => '26.0'],
    ['price' => '3,091.00', 'qty' => '8.30',  'total' => '24.7'],
    ['price' => '3,090.90', 'qty' => '6.88',  'total' => '16.4'],
    ['price' => '3,090.80', 'qty' => '3.21',  'total' => '9.6'],
];

$orderbookBids = [
    ['price' => '3,090.70', 'qty' => '7.10',  'total' => '21.3'],
    ['price' => '3,090.60', 'qty' => '10.25', 'total' => '31.8'],
    ['price' => '3,090.50', 'qty' => '2.88',  'total' => '9.9'],
    ['price' => '3,090.40', 'qty' => '14.66', 'total' => '38.1'],
    ['price' => '3,090.30', 'qty' => '5.42',  'total' => '19.0'],
];

$recentTrades = [
    ['time' => '12:30:21', 'price' => '3,091.05', 'qty' => '0.58', 'side' => 'buy'],
    ['time' => '12:30:18', 'price' => '3,091.00', 'qty' => '1.24', 'side' => 'sell'],
    ['time' => '12:30:14', 'price' => '3,091.15', 'qty' => '0.32', 'side' => 'buy'],
    ['time' => '12:30:10', 'price' => '3,091.20', 'qty' => '2.04', 'side' => 'buy'],
    ['time' => '12:30:06', 'price' => '3,091.30', 'qty' => '0.80', 'side' => 'sell'],
];

$positions = [
    ['symbol' => 'ETHUSDT', 'side' => 'Long', 'size' => '12.50 ETH', 'entry' => '3,080.20', 'mark' => '3,091.00', 'liq' => '2,820.00', 'pnl' => '+128.35 (4.16%)'],
    ['symbol' => 'BTCUSDT', 'side' => 'Short', 'size' => '0.85 BTC', 'entry' => '91,400.00', 'mark' => '91,180.50', 'liq' => '96,200.00', 'pnl' => '+186.12 (2.18%)'],
];

$openOrders = [
    ['time' => '12:29:54', 'symbol' => 'ETHUSDT', 'type' => 'Limit', 'side' => 'Buy', 'price' => '3,085.00', 'qty' => '5.000', 'status' => 'Working'],
    ['time' => '12:28:10', 'symbol' => 'BTCUSDT', 'type' => 'Limit', 'side' => 'Sell', 'price' => '91,800.00', 'qty' => '0.250', 'status' => 'Working'],
];

$assets = [
    ['coin' => 'USDT', 'wallet' => '18,520.00', 'available' => '14,200.00', 'inOrder' => '4,320.00', 'usd' => '18,520.00'],
    ['coin' => 'ETH', 'wallet' => '24.15', 'available' => '17.80', 'inOrder' => '6.35', 'usd' => '74,500.00'],
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Design Mock - FuturX</title>
    <link rel="stylesheet" href="trade_design.css">
</head>
<body>
    <header class="topbar">
        <div class="logo">FuturX</div>
        <nav class="nav">
            <a class="active" href="#">선물거래</a>
            <a href="#">마켓</a>
            <a href="#">지갑</a>
            <a href="#">리더보드</a>
            <a href="#">지원</a>
        </nav>
        <div class="top-actions">
            <div class="balance">잔고: 18,520.00 USDT</div>
            <button class="btn ghost">입금</button>
            <button class="btn primary">트레이딩 시작</button>
        </div>
    </header>

    <section class="symbol-bar">
        <div class="symbol-main">
            <div class="badge">Ξ</div>
            <div>
                <div class="symbol-name">ETHUSDT</div>
                <div class="symbol-sub">USDT Perpetual</div>
            </div>
            <div class="favorite">☆</div>
        </div>
        <div class="symbol-metrics">
            <div><span class="label">Last</span><span class="value up">3,089.90</span></div>
            <div><span class="label">Index</span><span class="value">3,090.80</span></div>
            <div><span class="label">24h Change</span><span class="value down">-1.03%</span></div>
            <div><span class="label">24h High</span><span class="value">3,170.88</span></div>
            <div><span class="label">24h Low</span><span class="value">3,061.35</span></div>
            <div><span class="label">Funding</span><span class="value">0.0100% / 07:12:33</span></div>
        </div>
        <div class="symbol-actions">
            <button class="btn ghost">교차  Cross</button>
            <button class="btn ghost">20.0x</button>
            <button class="btn ghost">알림</button>
        </div>
    </section>

    <main class="layout">
        <section class="chart-card">
            <div class="card-header">
                <div>
                    <div class="title">차트</div>
                    <div class="subtitle">TradingView 스타일 플레이스홀더</div>
                </div>
                <div class="tab-group">
                    <button class="pill active">Standard</button>
                    <button class="pill">TradingView</button>
                    <button class="pill">Depth</button>
                </div>
            </div>
            <div class="chart-placeholder">Chart Placeholder (Dummy)</div>
            <div class="intervals">
                <?php foreach (['1m','5m','15m','1h','4h','1d'] as $int): ?>
                    <button class="pill ghost"><?= $int ?></button>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="orderbook-card">
            <div class="card-header">
                <div class="title">Order Book</div>
                <div class="pill-group">
                    <button class="pill ghost active">양쪽</button>
                    <button class="pill ghost">매수</button>
                    <button class="pill ghost">매도</button>
                </div>
            </div>
            <div class="orderbook">
                <div class="asks">
                    <?php foreach ($orderbookAsks as $row): ?>
                        <div class="ob-row ask">
                            <span><?= $row['price'] ?></span>
                            <span><?= $row['qty'] ?></span>
                            <span><?= $row['total'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="spread">
                    <div class="spread-price">3,089.88</div>
                    <div class="spread-label">Mid Price</div>
                </div>
                <div class="bids">
                    <?php foreach ($orderbookBids as $row): ?>
                        <div class="ob-row bid">
                            <span><?= $row['price'] ?></span>
                            <span><?= $row['qty'] ?></span>
                            <span><?= $row['total'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer small">
                <div>상단: 매도 호가 | 하단: 매수 호가</div>
                <div class="depth-mix">매수 48% / 매도 52%</div>
            </div>
        </section>

        <section class="trade-card">
            <div class="card-header">
                <div class="title">Trade</div>
                <div class="pill-group">
                    <button class="pill ghost active">Limit</button>
                    <button class="pill ghost">Market</button>
                    <button class="pill ghost">TP/SL</button>
                </div>
            </div>
            <div class="input-grid">
                <label>가격
                    <div class="input">
                        <input type="text" value="3,089.88">
                        <span class="unit">USDT</span>
                    </div>
                </label>
                <label>수량
                    <div class="input">
                        <input type="text" value="1.5000">
                        <span class="unit">ETH</span>
                    </div>
                </label>
                <label>주문가치
                    <div class="input disabled">
                        <input type="text" value="4,634.82" disabled>
                        <span class="unit">USDT</span>
                    </div>
                </label>
                <div class="slider-row">
                    <?php foreach ([0,25,50,75,100] as $pct): ?>
                        <button class="pill ghost"><?= $pct ?>%</button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="order-actions">
                <button class="btn buy">Long</button>
                <button class="btn sell">Short</button>
            </div>
            <div class="order-meta">
                <div>수수료: 0.02% / 0.05%</div>
                <div>예상 청산가: 2,820.00</div>
                <div>가용: 18,520.00 USDT</div>
            </div>
        </section>

        <section class="trades-card">
            <div class="card-header">
                <div class="title">Recent Trades</div>
                <div class="pill-group">
                    <button class="pill ghost active">전체</button>
                    <button class="pill ghost">매수</button>
                    <button class="pill ghost">매도</button>
                </div>
            </div>
            <div class="table small">
                <div class="table-head">
                    <span>Time</span><span>Price(USDT)</span><span>Qty(ETH)</span><span>Side</span>
                </div>
                <?php foreach ($recentTrades as $t): ?>
                    <div class="table-row">
                        <span><?= $t['time'] ?></span>
                        <span><?= $t['price'] ?></span>
                        <span><?= $t['qty'] ?></span>
                        <span class="<?= $t['side'] === 'buy' ? 'up' : 'down' ?>"><?= strtoupper($t['side']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="summary-card">
            <div class="stat-grid">
                <div class="stat">
                    <div class="label">예상 PNL</div>
                    <div class="value up">+2,154.22 USDT</div>
                    <div class="sub">ROE 12.4%</div>
                </div>
                <div class="stat">
                    <div class="label">마진 밸런스</div>
                    <div class="value">18,520.00 USDT</div>
                    <div class="sub">가용 14,200.00</div>
                </div>
                <div class="stat">
                    <div class="label">펀딩 카운트다운</div>
                    <div class="value">07:12:33</div>
                    <div class="sub">0.0100%</div>
                </div>
                <div class="stat">
                    <div class="label">OI (ETH)</div>
                    <div class="value">2.23M</div>
                    <div class="sub">24h Vol 12.4B</div>
                </div>
            </div>
        </section>

        <section class="bottom-card">
            <div class="tab-bar">
                <button class="tab active">Positions</button>
                <button class="tab">Open Orders</button>
                <button class="tab">Order History</button>
                <button class="tab">Trades</button>
                <button class="tab">Assets</button>
            </div>
            <div class="table">
                <div class="table-head">
                    <span>Symbol</span><span>Side</span><span>Size</span><span>Entry</span><span>Mark</span><span>Liq</span><span>PNL</span>
                </div>
                <?php foreach ($positions as $p): ?>
                    <div class="table-row">
                        <span><?= $p['symbol'] ?></span>
                        <span class="<?= strtolower($p['side']) === 'long' ? 'up' : 'down' ?>"><?= $p['side'] ?></span>
                        <span><?= $p['size'] ?></span>
                        <span><?= $p['entry'] ?></span>
                        <span><?= $p['mark'] ?></span>
                        <span><?= $p['liq'] ?></span>
                        <span class="up"><?= $p['pnl'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="assets-card">
            <div class="card-header">
                <div class="title">Wallet Overview</div>
                <div class="subtitle">더미 데이터</div>
            </div>
            <div class="table small">
                <div class="table-head">
                    <span>Coin</span><span>Wallet</span><span>Available</span><span>In Order</span><span>USD Value</span>
                </div>
                <?php foreach ($assets as $a): ?>
                    <div class="table-row">
                        <span><?= $a['coin'] ?></span>
                        <span><?= $a['wallet'] ?></span>
                        <span><?= $a['available'] ?></span>
                        <span><?= $a['inOrder'] ?></span>
                        <span><?= $a['usd'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
