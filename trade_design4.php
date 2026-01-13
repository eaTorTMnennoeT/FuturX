<?php
/**
 * FUTX | 선물거래 (디자인 전용 시안 v4)
 * ------------------------------------------------------------
 * 중요: 실기능(거래 API, DB, 세션, 웹소켓 등) 연동 절대 금지
 * - 외부 include/require 금지
 * - 더미데이터로만 화면을 채웁니다.
 */

function fx_fmt($n, $dec = 2) { return number_format((float)$n, $dec, '.', ','); }
function fx_pct($n, $dec = 2) { $n=(float)$n; return ($n>0?'+':'') . number_format($n, $dec, '.', '') . '%'; }
function fx_compact($n) {
    $n=(float)$n; $a=abs($n);
    if ($a>=1e9) return number_format($n/1e9,2).'B';
    if ($a>=1e6) return number_format($n/1e6,2).'M';
    if ($a>=1e3) return number_format($n/1e3,2).'K';
    return number_format($n,0);
}
function fx_rf($min, $max, $dec=2){ $s=pow(10,$dec); return mt_rand((int)round($min*$s),(int)round($max*$s))/$s; }

$now = time();
$quote = 'USDT';
$baseSymbol = 'BTCUSDT';
$baseCoin = 'BTC';

// -------------------------------
// Market symbols (dummy)
// -------------------------------
$coins = [
    ['BTC','Bitcoin',52340.50, 2.48,'#f7931a','₿'],
    ['ETH','Ethereum',3125.80, 4.18,'#627eea','Ξ'],
    ['SOL','Solana',98.25, -1.32,'#14f195','S'],
    ['XRP','Ripple',0.6234, 5.76,'#00aae4','X'],
    ['DOGE','Dogecoin',0.0923,-2.14,'#c2a633','Ð'],
    ['ADA','Cardano',0.4568, 3.21,'#2a9df4','A'],
    ['AVAX','Avalanche',34.56, 1.89,'#e84142','V'],
    ['DOT','Polkadot',6.78, -0.54,'#ff2d9a','•'],
    ['MATIC','Polygon',0.8934, 2.67,'#8247e5','M'],
    ['LINK','Chainlink',14.32, 1.23,'#2a5ada','⛓'],
    ['UNI','Uniswap',7.89, -1.87,'#ff007a','U'],
    ['ATOM','Cosmos',9.45, 0.92,'#9aa2ff','⚛'],
    ['LTC','Litecoin',89.34, 1.56,'#345d9d','Ł'],
    ['BCH','Bitcoin Cash',234.67,-2.34,'#4cca47','Ƀ'],
    ['NEAR','Near',3.45, 3.78,'#00e599','N'],
    ['APT','Aptos',8.92, 4.56,'#87c7ff','A'],
    ['ARB','Arbitrum',1.234,2.34,'#28a0f0','A'],
    ['OP','Optimism',2.345,1.78,'#ff0420','O'],
    ['INJ','Injective',23.45,5.67,'#00d1ff','I'],
    ['TIA','Celestia',12.34,3.45,'#a37cff','T'],
    ['SUI','Sui',0.789,-0.98,'#6ac2ff','S'],
    ['FIL','Filecoin',5.67, 1.45,'#42c1ca','F'],
    ['AAVE','Aave',98.76,3.21,'#b6509e','A'],
    ['MKR','Maker',1567.89,1.89,'#1abc9c','M'],
    ['PEPE','Pepe',0.00000123,8.92,'#9bf042','P'],
    ['WLD','Worldcoin',3.45,4.56,'#a6f0ff','W'],
    ['RNDR','Render',5.67,3.21,'#ff4d4d','R'],
    ['AR','Arweave',8.92,2.67,'#f2c94c','A'],
    ['FTM','Fantom',0.456,3.21,'#13b5ec','F'],
    ['ICP','ICP',5.67,3.45,'#ff6b6b','I'],
    ['XLM','Stellar',0.123,2.14,'#14b8a6','S'],
];

$symbols = [];
foreach ($coins as $i => $c) {
    $symbols[] = [
        'symbol' => $c[0].$quote,
        'coin' => $c[0],
        'name' => $c[1],
        'price' => $c[2],
        'change' => $c[3],
        'color' => $c[4],
        'glyph' => $c[5],
        'vol' => mt_rand(12_000_000, 1_800_000_000),
        'oi' => fx_rf(1200, 98_000, 2),
        'funding' => fx_rf(-0.015, 0.018, 4),
        'fav' => ($i % 4 === 0) || (mt_rand(0, 10) > 8),
    ];
}

// -------------------------------
// Header stats (dummy)
// -------------------------------
$last = 52340.50;
$mark = 52360.10;
$index = 52310.20;
$chgPct = 2.48;
$high24 = 53120.00;
$low24 = 50890.50;
$turnover24 = 445_200_000;
$openInterest = 51_494.36;
$fundingRate = 0.0043;
$fundingCd = '00:53:55';

// -------------------------------
// Orderbook + trades (dummy)
// -------------------------------
$asks = [];
$bids = [];
$accA = 0; $accB = 0;
for ($i=0;$i<26;$i++){
    $p = $last + ($i+1)*fx_rf(0.4,2.6,2);
    $q = fx_rf(0.02,4.8,3);
    $accA += $q;
    $asks[] = ['p'=>$p,'q'=>$q,'t'=>$p*$q,'d'=>min(100,(int)round(($accA/35)*100))];
}
for ($i=0;$i<26;$i++){
    $p = $last - ($i+1)*fx_rf(0.4,2.6,2);
    $q = fx_rf(0.02,4.8,3);
    $accB += $q;
    $bids[] = ['p'=>$p,'q'=>$q,'t'=>$p*$q,'d'=>min(100,(int)round(($accB/35)*100))];
}

$trades = [];
for ($i=0;$i<80;$i++){
    $side = mt_rand(0,1)?'buy':'sell';
    $p = $last + fx_rf(-35,35,2);
    $q = fx_rf(0.01,2.6,3);
    $trades[] = ['time'=>date('H:i:s',$now-$i*mt_rand(3,12)),'p'=>$p,'q'=>$q,'side'=>$side];
}

// -------------------------------
// Positions / orders / assets (dummy)
// -------------------------------
$positions = [];
for ($i=0;$i<12;$i++){
    $sym = $symbols[mt_rand(0, min(18, count($symbols)-1))]['symbol'];
    $side = mt_rand(0,1)?'Long':'Short';
    $lev = [3,5,10,20,25,50][mt_rand(0,5)];
    $entry = fx_rf(800, 60000, 2);
    $markp = $entry + fx_rf(-1200,1200,2);
    $size = fx_rf(0.01, 1.9, 3);
    $pnl = ($markp-$entry)*$size*($side==='Long'?1:-1);
    $margin = max(10, ($entry*$size)/$lev);
    $roe = ($pnl/$margin)*100;
    $liq = $side==='Long' ? $entry*(1-(1/$lev)*0.82) : $entry*(1+(1/$lev)*0.82);
    $positions[] = ['symbol'=>$sym,'side'=>$side,'lev'=>$lev,'size'=>$size,'entry'=>$entry,'mark'=>$markp,'liq'=>$liq,'margin'=>$margin,'pnl'=>$pnl,'roe'=>$roe,'adl'=>mt_rand(1,5)];
}

$openOrders = [];
for ($i=0;$i<26;$i++){
    $side = mt_rand(0,1)?'Buy':'Sell';
    $type = ['Limit','Market','Stop','TP/SL'][mt_rand(0,3)];
    $status = ['New','Partially Filled'][mt_rand(0,1)];
    $sym = $symbols[mt_rand(0, min(20, count($symbols)-1))]['symbol'];
    $price = fx_rf(0.08, 61000, 2);
    $qty = fx_rf(0.01, 3.0, 3);
    $filled = mt_rand(0,85);
    $openOrders[] = ['time'=>date('Y-m-d H:i:s',$now-$i*mt_rand(1100,3300)),'symbol'=>$sym,'type'=>$type,'side'=>$side,'price'=>$price,'qty'=>$qty,'filled'=>$filled,'status'=>$status,'reduce'=>mt_rand(0,10)>7];
}

$history = [];
for ($i=0;$i<60;$i++){
    $side = mt_rand(0,1)?'Buy':'Sell';
    $type = ['Limit','Market','Stop','TP/SL'][mt_rand(0,3)];
    $status = ['Filled','Cancelled','Expired','Rejected'][mt_rand(0,3)];
    $sym = $symbols[mt_rand(0, min(28, count($symbols)-1))]['symbol'];
    $price = fx_rf(0.08, 61000, 2);
    $qty = fx_rf(0.01, 3.0, 3);
    $fee = fx_rf(0.02, 18.5, 4);
    $realized = fx_rf(-120.5, 220.8, 2);
    $history[] = ['time'=>date('Y-m-d H:i:s',$now-$i*mt_rand(1600,5200)),'symbol'=>$sym,'type'=>$type,'side'=>$side,'price'=>$price,'qty'=>$qty,'filled'=>mt_rand(60,100),'status'=>$status,'fee'=>$fee,'realized'=>$realized];
}

$assets = [
    ['USDT','Tether',125840.50,'#22c55e'],
    ['BTC','Bitcoin',2.45678,'#f7931a'],
    ['ETH','Ethereum',15.8934,'#627eea'],
    ['SOL','Solana',234.56,'#14f195'],
    ['BNB','BNB',45.23,'#f0b90b'],
    ['USDC','USD Coin',8920.30,'#2775ca'],
    ['DAI','Dai',5230.80,'#f5ac37'],
    ['XRP','Ripple',23450.20,'#00aae4'],
];

$equity = 168_420.35;
$availMargin = 125_840.50;
$unreal = fx_rf(-420.5, 1380.8, 2);
$dayPnl = fx_rf(-980.5, 1420.3, 2);
$usedMargin = max(1, $equity - $availMargin);
$marginRatio = min(95, max(6, (int)round(($usedMargin / max(1, $equity)) * 100)));
$riskLevel = $marginRatio < 25 ? '낮음' : ($marginRatio < 55 ? '보통' : '높음');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FUTX | 선물거래 (디자인 시안 v4)</title>
    <link rel="stylesheet" href="css/trade_design4.css?v=1">
</head>
<body>

<div class="fx-banner" role="note">
    <span class="fx-banner__dot" aria-hidden="true"></span>
    <div class="fx-banner__txt"><strong>디자인 전용 시안(v4)</strong> · 실기능 연동 없음(더미데이터)</div>
    <button class="fx-banner__x" type="button" onclick="document.querySelector('.fx-banner').style.display='none'">닫기</button>
</div>

<div class="toast-stack" id="toastStack" aria-live="polite" aria-relevant="additions"></div>

<header class="topbar">
    <div class="topbar__left">
        <a class="brand" href="#" onclick="fxToast('홈 이동 (더미)'); return false;">
            <span class="brand__mark">F</span><span class="brand__text">FUTX</span><span class="brand__sub">Futures</span>
        </a>
        <button class="sym-pill" type="button" onclick="fxToggleDrawer(true)">
            <span class="sym-pill__logo" id="symLogo" style="--coin:#f7931a">₿</span>
            <span class="sym-pill__main">
                <span class="sym-pill__sym mono" id="symText"><?= htmlspecialchars($baseSymbol) ?></span>
                <span class="sym-pill__meta">USDT Perpetual · Cross</span>
            </span>
            <span class="sym-pill__caret" aria-hidden="true">▾</span>
        </button>
    </div>

    <div class="topbar__mid desktop-only">
        <div class="pricechip">
            <div class="pricechip__row">
                <span class="pricechip__k">Last</span>
                <span class="pricechip__v mono is-up" id="lastPrice"><?= fx_fmt($last,2) ?></span>
                <span class="pricechip__c mono is-up" id="chgPct"><?= fx_pct($chgPct,2) ?></span>
            </div>
            <div class="pricechip__sub">
                <span class="muted">Mark</span> <span class="mono"><?= fx_fmt($mark,2) ?></span>
                <span class="dot">·</span>
                <span class="muted">Index</span> <span class="mono"><?= fx_fmt($index,2) ?></span>
            </div>
        </div>
        <div class="statstrip">
            <div class="stat"><div class="stat__k">24H High</div><div class="stat__v mono"><?= fx_fmt($high24,2) ?></div></div>
            <div class="stat"><div class="stat__k">24H Low</div><div class="stat__v mono"><?= fx_fmt($low24,2) ?></div></div>
            <div class="stat"><div class="stat__k">Turnover</div><div class="stat__v mono"><?= fx_compact($turnover24) ?></div></div>
            <div class="stat"><div class="stat__k">Open Interest</div><div class="stat__v mono"><?= fx_compact($openInterest) ?></div></div>
            <div class="stat"><div class="stat__k">Funding</div><div class="stat__v mono is-up"><?= number_format($fundingRate,4) ?>% <span class="muted">/ <?= htmlspecialchars($fundingCd) ?></span></div></div>
        </div>
    </div>

    <div class="topbar__right">
        <div class="acct desktop-only">
            <div class="acct__k">Equity</div>
            <div class="acct__v mono"><?= fx_fmt($equity,2) ?> <span class="muted">USDT</span></div>
        </div>
        <button class="icon-btn" type="button" onclick="fxToast('알림 (더미)')">🔔</button>
        <button class="icon-btn" type="button" onclick="fxToast('테마 (더미)')">◐</button>
        <button class="icon-btn" type="button" onclick="fxToast('설정 (더미)')">⚙</button>
        <button class="avatar" type="button" onclick="fxToast('프로필 (더미)')"><span class="avatar__dot"></span><span class="avatar__txt">AD</span></button>
    </div>
</header>

<main class="layout" id="layout" data-mobile-tab="chart">
    <!-- LEFT: Market -->
    <aside class="panel market desktop-only">
        <div class="panel__head">
            <div class="panel__title">마켓</div>
            <div class="panel__actions">
                <button class="chip chip--ghost" type="button" onclick="fxToast('정렬 (더미)')">정렬</button>
                <button class="chip chip--ghost" type="button" onclick="fxToast('필터 (더미)')">필터</button>
            </div>
        </div>
        <div class="panel__body">
            <div class="seg-tabs">
                <button class="seg is-active" type="button">Favorites</button>
                <button class="seg" type="button">USDT-M</button>
                <button class="seg" type="button">Hot</button>
                <button class="seg" type="button">New</button>
            </div>
            <div class="searchline">
                <span class="searchline__ico" aria-hidden="true">⌕</span>
                <input class="searchline__in" id="marketSearch" type="text" placeholder="심볼 검색" autocomplete="off">
                <button class="searchline__btn" type="button" onclick="document.getElementById('marketSearch').value=''; fxFilterList('');">Clear</button>
            </div>
            <div class="thead">
                <div class="th th--sym">Symbol</div>
                <div class="th tar">Last</div>
                <div class="th tar">24h%</div>
                <div class="th tar">Trend</div>
            </div>
            <div class="marketlist scroll" id="marketList">
                <?php foreach ($symbols as $s): ?>
                    <button class="mrow <?= $s['symbol']===$baseSymbol?'is-active':'' ?>" type="button"
                            data-symbol="<?= htmlspecialchars($s['symbol']) ?>"
                            data-coin="<?= htmlspecialchars($s['coin']) ?>"
                            onclick="fxPick('<?= htmlspecialchars($s['symbol']) ?>','<?= htmlspecialchars($s['color']) ?>','<?= htmlspecialchars($s['glyph']) ?>',<?= (float)$s['price'] ?>,<?= (float)$s['change'] ?>)">
                        <div class="mrow__fav" aria-hidden="true"><?= $s['fav']?'★':'☆' ?></div>
                        <div class="mrow__sym">
                            <div class="fw-800"><?= htmlspecialchars($s['coin']) ?><span class="muted">/<?= htmlspecialchars($quote) ?></span></div>
                            <div class="muted fs-11">Vol <?= fx_compact($s['vol']) ?> · OI <?= fx_compact($s['oi']) ?></div>
                        </div>
                        <div class="mrow__p mono tar"><?= fx_fmt($s['price'], $s['price']<1?6:2) ?></div>
                        <div class="mrow__c mono tar <?= $s['change']>=0?'is-up':'is-down' ?>"><?= fx_pct($s['change'],2) ?></div>
                        <div class="mrow__t tar" aria-hidden="true">
                            <span class="spark <?= $s['change']>=0?'spark--up':'spark--dn' ?>"></span>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- CENTER -->
    <section class="center">
        <section class="panel chart">
            <div class="panel__head panel__head--wrap">
                <div class="panel__title"><span class="pill mono" id="activeSymbol"><?= htmlspecialchars($baseSymbol) ?></span> <span class="muted">Standard</span></div>
                <div class="panel__actions">
                    <div class="btnset">
                        <button class="btn btn--ghost is-active" type="button" data-tf="1m">1m</button>
                        <button class="btn btn--ghost" type="button" data-tf="5m">5m</button>
                        <button class="btn btn--ghost" type="button" data-tf="15m">15m</button>
                        <button class="btn btn--ghost" type="button" data-tf="1h">1h</button>
                        <button class="btn btn--ghost" type="button" data-tf="4h">4h</button>
                        <button class="btn btn--ghost" type="button" data-tf="1D">1D</button>
                    </div>
                    <button class="chip desktop-only" type="button" onclick="fxToast('지표 (더미)')">Indicators</button>
                </div>
            </div>
            <div class="chartwrap">
                <canvas id="fxChart" aria-label="더미 차트"></canvas>
                <div class="charthud desktop-only" aria-hidden="true">
                    <div class="hudrow"><span class="muted">O</span><span class="mono" id="hudO">51,234.50</span></div>
                    <div class="hudrow"><span class="muted">H</span><span class="mono is-up" id="hudH">53,120.00</span></div>
                    <div class="hudrow"><span class="muted">L</span><span class="mono is-down" id="hudL">50,890.50</span></div>
                    <div class="hudrow"><span class="muted">C</span><span class="mono" id="hudC">52,340.50</span></div>
                </div>
                <div class="chartfoot">
                    <div class="minikpis">
                        <div class="kpi"><div class="kpi__k">Funding</div><div class="kpi__v mono is-up"><?= number_format($fundingRate,4) ?>%</div></div>
                        <div class="kpi"><div class="kpi__k">Basis</div><div class="kpi__v mono"><?= fx_fmt($mark-$index,2) ?></div></div>
                        <div class="kpi"><div class="kpi__k">Vol (24H)</div><div class="kpi__v mono"><?= fx_compact($turnover24) ?></div></div>
                        <div class="kpi desktop-only"><div class="kpi__k">OI</div><div class="kpi__v mono"><?= fx_compact($openInterest) ?></div></div>
                    </div>
                    <div class="muted fs-11 desktop-only">차트/가격은 더미 시뮬레이션입니다.</div>
                </div>
            </div>
        </section>

        <section class="panel bottom">
            <div class="panel__head">
                <div class="tabs">
                    <button class="tab is-active" type="button" data-tab="pos">포지션 <span class="badge"><?= count($positions) ?></span></button>
                    <button class="tab" type="button" data-tab="open">오픈오더 <span class="badge"><?= count($openOrders) ?></span></button>
                    <button class="tab" type="button" data-tab="hist">주문/체결내역 <span class="badge"><?= count($history) ?></span></button>
                    <button class="tab" type="button" data-tab="assets">자산</button>
                </div>
                <div class="panel__actions">
                    <button class="chip" type="button" onclick="fxToast('Export (더미)')">Export</button>
                    <button class="chip chip--ghost desktop-only" type="button" onclick="fxToast('Columns (더미)')">Columns</button>
                </div>
            </div>
            <div class="panel__body scroll">
                <div class="tabpane is-active" id="tab-pos">
                    <div class="summary">
                        <div class="sumcard">
                            <div class="sumcard__k">미실현 PnL</div>
                            <div class="sumcard__v mono <?= $unreal>=0?'is-up':'is-down' ?>"><?= ($unreal>=0?'+':'').fx_fmt($unreal,2) ?> USDT</div>
                            <div class="sumcard__s muted">더미 합산</div>
                        </div>
                        <div class="sumcard">
                            <div class="sumcard__k">일간 PnL</div>
                            <div class="sumcard__v mono <?= $dayPnl>=0?'is-up':'is-down' ?>"><?= ($dayPnl>=0?'+':'').fx_fmt($dayPnl,2) ?> USDT</div>
                            <div class="sumcard__s muted">00:00 ~ 현재</div>
                        </div>
                        <div class="sumcard desktop-only">
                            <div class="sumcard__k">Margin Ratio</div>
                            <div class="sumcard__v mono"><?= (int)$marginRatio ?>%</div>
                            <div class="sumcard__s muted">리스크: <?= htmlspecialchars($riskLevel) ?></div>
                        </div>
                    </div>
                    <table class="fxtable">
                        <thead><tr>
                            <th>Symbol</th><th>Side</th><th class="tar">Lev</th><th class="tar">Size</th>
                            <th class="tar">Entry</th><th class="tar">Mark</th><th class="tar">Liq</th>
                            <th class="tar">Margin</th><th class="tar">PnL (ROE)</th><th class="tac">ADL</th><th class="tac">Action</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($positions as $p): ?>
                            <tr>
                                <td class="mono fw-800"><?= htmlspecialchars($p['symbol']) ?></td>
                                <td class="<?= $p['side']==='Long'?'is-up':'is-down' ?> fw-800"><?= htmlspecialchars($p['side']) ?></td>
                                <td class="mono tar"><?= (int)$p['lev'] ?>x</td>
                                <td class="mono tar"><?= fx_fmt($p['size'],3) ?></td>
                                <td class="mono tar"><?= fx_fmt($p['entry'],2) ?></td>
                                <td class="mono tar"><?= fx_fmt($p['mark'],2) ?></td>
                                <td class="mono tar muted"><?= fx_fmt($p['liq'],2) ?></td>
                                <td class="mono tar"><?= fx_fmt($p['margin'],2) ?></td>
                                <td class="mono tar <?= $p['pnl']>=0?'is-up':'is-down' ?> fw-800"><?= ($p['pnl']>=0?'+':'').fx_fmt($p['pnl'],2) ?> <span class="muted">(<?= ($p['roe']>=0?'+':'').fx_fmt($p['roe'],2) ?>%)</span></td>
                                <td class="tac"><div class="adl" aria-hidden="true"><?php for($k=1;$k<=5;$k++): ?><span class="adl__d <?= $k<=$p['adl']?'is-on':'' ?>"></span><?php endfor; ?></div></td>
                                <td class="tac"><button class="mini-btn mini-btn--danger" type="button" onclick="fxToast('포지션 청산 (더미)','info')">청산</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tabpane" id="tab-open">
                    <table class="fxtable">
                        <thead><tr>
                            <th>Time</th><th>Symbol</th><th>Type</th><th>Side</th>
                            <th class="tar">Price</th><th class="tar">Qty</th><th class="tar">Filled</th><th>Status</th><th class="tac">Flags</th><th class="tac">Action</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($openOrders as $o): ?>
                            <tr>
                                <td class="muted"><?= htmlspecialchars($o['time']) ?></td>
                                <td class="mono fw-800"><?= htmlspecialchars($o['symbol']) ?></td>
                                <td><?= htmlspecialchars($o['type']) ?></td>
                                <td class="<?= $o['side']==='Buy'?'is-up':'is-down' ?> fw-800"><?= htmlspecialchars($o['side']) ?></td>
                                <td class="mono tar"><?= fx_fmt($o['price'],2) ?></td>
                                <td class="mono tar"><?= fx_fmt($o['qty'],3) ?></td>
                                <td class="mono tar"><?= (int)$o['filled'] ?>%</td>
                                <td class="muted"><?= htmlspecialchars($o['status']) ?></td>
                                <td class="tac"><?= $o['reduce']?'<span class="flag">Reduce</span>':'<span class="flag flag--muted">—</span>' ?></td>
                                <td class="tac"><button class="mini-btn mini-btn--danger" type="button" onclick="fxToast('주문 취소 (더미)','info')">취소</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tabpane" id="tab-hist">
                    <table class="fxtable">
                        <thead><tr>
                            <th>Time</th><th>Symbol</th><th>Type</th><th>Side</th>
                            <th class="tar">Price</th><th class="tar">Qty</th><th class="tar">Filled</th><th>Status</th><th class="tar">Fee</th><th class="tar">Realized</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="muted"><?= htmlspecialchars($h['time']) ?></td>
                                <td class="mono fw-800"><?= htmlspecialchars($h['symbol']) ?></td>
                                <td><?= htmlspecialchars($h['type']) ?></td>
                                <td class="<?= $h['side']==='Buy'?'is-up':'is-down' ?> fw-800"><?= htmlspecialchars($h['side']) ?></td>
                                <td class="mono tar"><?= fx_fmt($h['price'],2) ?></td>
                                <td class="mono tar"><?= fx_fmt($h['qty'],3) ?></td>
                                <td class="mono tar"><?= (int)$h['filled'] ?>%</td>
                                <td class="muted"><?= htmlspecialchars($h['status']) ?></td>
                                <td class="mono tar muted"><?= fx_fmt($h['fee'],4) ?></td>
                                <td class="mono tar <?= $h['realized']>=0?'is-up':'is-down' ?> fw-800"><?= ($h['realized']>=0?'+':'').fx_fmt($h['realized'],2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tabpane" id="tab-assets">
                    <div class="grid2">
                        <div class="card"><div class="card__k">Available Margin</div><div class="card__v mono"><?= fx_fmt($availMargin,2) ?> <span class="muted">USDT</span></div><div class="card__s muted">Cross 더미</div></div>
                        <div class="card"><div class="card__k">Margin Ratio</div><div class="card__v mono"><?= (int)$marginRatio ?>%</div><div class="card__s muted">리스크: <?= htmlspecialchars($riskLevel) ?></div></div>
                    </div>
                    <table class="fxtable">
                        <thead><tr><th>Asset</th><th class="tar">Total</th><th class="tar">Available</th><th class="tar">In Order</th><th class="tar">USD Value</th></tr></thead>
                        <tbody>
                        <?php foreach ($assets as $a): $total=(float)$a[2]; $avail=$total*fx_rf(0.72,0.96,2); $in=max(0,$total-$avail); $priceRef=1.0; foreach($symbols as $s){ if($s['coin']===$a[0]){$priceRef=(float)$s['price']; break; } } if(in_array($a[0],['USDT','USDC','DAI'],true)) $priceRef=1.0; $usd=$total*$priceRef; ?>
                            <tr>
                                <td><div class="asset"><span class="asset__logo" style="--coin:<?= htmlspecialchars($a[3]) ?>"><?= htmlspecialchars(substr($a[0],0,1)) ?></span><div><div class="fw-800"><?= htmlspecialchars($a[0]) ?></div><div class="muted fs-11"><?= htmlspecialchars($a[1]) ?></div></div></div></td>
                                <td class="mono tar"><?= fx_fmt($total, $total<10?4:2) ?></td>
                                <td class="mono tar"><?= fx_fmt($avail, $avail<10?4:2) ?></td>
                                <td class="mono tar muted"><?= fx_fmt($in, $in<10?4:2) ?></td>
                                <td class="mono tar fw-800"><?= fx_fmt($usd,2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </section>

    <!-- RIGHT -->
    <aside class="right">
        <section class="panel ob">
            <div class="panel__head">
                <div class="tabs tabs--mini">
                    <button class="tab-mini is-active" type="button" data-ob="book">Order Book</button>
                    <button class="tab-mini" type="button" data-ob="trades">Recent Trades</button>
                </div>
                <div class="panel__actions"><button class="chip chip--ghost" type="button" onclick="fxToast('오더북 뷰 (더미)')">뷰</button></div>
            </div>
            <div class="panel__body">
                <div class="obpane is-active" id="ob-book">
                    <div class="obhead"><div class="muted">Price(<?= htmlspecialchars($quote) ?>)</div><div class="muted tac">Qty(<?= htmlspecialchars($baseCoin) ?>)</div><div class="muted tar">Total</div></div>
                    <div class="oblist scroll">
                        <?php foreach ($asks as $a): ?>
                            <div class="obrow obrow--ask" style="--fill:<?= (int)$a['d'] ?>%"><div class="mono is-down"><?= fx_fmt($a['p'],2) ?></div><div class="mono tac"><?= fx_fmt($a['q'],3) ?></div><div class="mono tar muted"><?= fx_fmt($a['t'],2) ?></div></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="obmid"><div class="obmid__p mono is-up" id="obMid"><?= fx_fmt($last,2) ?></div><div class="obmid__s muted">Spread 0.50 (0.001%)</div></div>
                    <div class="oblist scroll">
                        <?php foreach ($bids as $b): ?>
                            <div class="obrow obrow--bid" style="--fill:<?= (int)$b['d'] ?>%"><div class="mono is-up"><?= fx_fmt($b['p'],2) ?></div><div class="mono tac"><?= fx_fmt($b['q'],3) ?></div><div class="mono tar muted"><?= fx_fmt($b['t'],2) ?></div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="obpane" id="ob-trades">
                    <div class="obhead"><div class="muted">Price</div><div class="muted tac">Qty</div><div class="muted tar">Time</div></div>
                    <div class="trlist scroll">
                        <?php foreach ($trades as $t): ?>
                            <div class="trrow"><div class="mono <?= $t['side']==='buy'?'is-up':'is-down' ?>"><?= fx_fmt($t['p'],2) ?></div><div class="mono tac"><?= fx_fmt($t['q'],3) ?></div><div class="mono tar muted"><?= htmlspecialchars($t['time']) ?></div></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel order">
            <div class="panel__head">
                <div class="tabs tabs--mini">
                    <button class="tab-mini is-active" type="button">Limit</button>
                    <button class="tab-mini" type="button">Market</button>
                    <button class="tab-mini" type="button">Stop</button>
                    <button class="tab-mini" type="button">TP/SL</button>
                </div>
                <div class="panel__actions">
                    <button class="chip chip--ghost" type="button" onclick="fxToast('Cross/Isolated (더미)')">Cross</button>
                    <button class="chip" type="button" onclick="fxToast('레버리지 (더미)')">10x</button>
                </div>
            </div>
            <div class="panel__body">
                <div class="side">
                    <button class="side__btn side__btn--buy is-active" id="btnBuy" type="button" onclick="fxSetSide('buy')">롱 / 매수</button>
                    <button class="side__btn side__btn--sell" id="btnSell" type="button" onclick="fxSetSide('sell')">숏 / 매도</button>
                </div>
                <div class="bal"><div class="muted">사용가능</div><div class="mono fw-800"><?= fx_fmt($availMargin,2) ?> <?= htmlspecialchars($quote) ?></div></div>

                <div class="field">
                    <label class="field__lbl">가격</label>
                    <div class="input">
                        <input class="input__el mono" id="inPrice" type="text" value="<?= fx_fmt($last,2) ?>" inputmode="decimal">
                        <button class="input__btn" type="button" onclick="fxFillLast()">Last</button>
                        <span class="input__unit"><?= htmlspecialchars($quote) ?></span>
                    </div>
                </div>
                <div class="field">
                    <label class="field__lbl">수량</label>
                    <div class="input">
                        <input class="input__el mono" id="inQty" type="text" value="" placeholder="0.000" inputmode="decimal">
                        <span class="input__unit"><?= htmlspecialchars($baseCoin) ?></span>
                    </div>
                </div>
                <div class="slider">
                    <input class="slider__el" id="inPct" type="range" min="0" max="100" value="0" step="1">
                    <div class="slider__marks"><span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span></div>
                </div>
                <div class="row2">
                    <div class="pillcard"><div class="pillcard__k">예상 증거금</div><div class="pillcard__v mono" id="estMargin">0.00</div></div>
                    <div class="pillcard"><div class="pillcard__k">총액</div><div class="pillcard__v mono" id="estNotional">0.00</div></div>
                </div>
                <div class="toggles">
                    <label class="toggle"><input type="checkbox" id="ckReduce"><span class="toggle__ui"></span><span class="toggle__txt">Reduce-only</span></label>
                    <label class="toggle"><input type="checkbox" id="ckPost"><span class="toggle__ui"></span><span class="toggle__txt">Post-only</span></label>
                    <label class="toggle"><input type="checkbox" id="ckTpsl" checked><span class="toggle__ui"></span><span class="toggle__txt">TP/SL</span></label>
                </div>
                <div class="risk">
                    <div class="risk__k">Risk</div>
                    <div class="risk__bar" aria-hidden="true"><div class="risk__fill" style="--p:<?= (int)$marginRatio ?>%"></div></div>
                    <div class="risk__meta"><span class="muted">Margin Ratio</span> <span class="mono fw-800"><?= (int)$marginRatio ?>%</span><span class="dot">·</span><span class="muted">Level</span> <span class="fw-800"><?= htmlspecialchars($riskLevel) ?></span></div>
                </div>
                <button class="submit submit--buy" id="submitBtn" type="button" onclick="fxToast('주문 제출 (더미)','success')">롱 / 매수 주문 (더미)</button>
                <div class="muted fs-11" style="margin-top:10px;">본 페이지는 디자인 시안이므로 실제 주문/청산이 수행되지 않습니다.</div>
            </div>
        </section>
    </aside>
</main>

<nav class="mnav mobile-only" aria-label="모바일 하단 네비">
    <button class="mnav__btn is-active" type="button" onclick="fxSwitchMobile('chart', this)"><span class="mnav__ico">📈</span><span class="mnav__txt">차트</span></button>
    <button class="mnav__btn" type="button" onclick="fxSwitchMobile('book', this)"><span class="mnav__ico">🧾</span><span class="mnav__txt">오더북</span></button>
    <button class="mnav__btn" type="button" onclick="fxSwitchMobile('order', this)"><span class="mnav__ico">🧠</span><span class="mnav__txt">주문</span></button>
    <button class="mnav__btn" type="button" onclick="fxSwitchMobile('bottom', this)"><span class="mnav__ico">📋</span><span class="mnav__txt">포지션</span></button>
</nav>

<!-- Drawer: Symbol list -->
<div class="drawer" id="drawer" role="dialog" aria-modal="true" aria-label="심볼 선택">
    <div class="drawer__backdrop" onclick="fxToggleDrawer(false)"></div>
    <div class="drawer__panel">
        <div class="drawer__head">
            <div class="drawer__title">마켓</div>
            <button class="icon-btn" type="button" onclick="fxToggleDrawer(false)">✕</button>
        </div>
        <div class="drawer__body">
            <div class="searchline">
                <span class="searchline__ico" aria-hidden="true">⌕</span>
                <input class="searchline__in" id="drawerSearch" type="text" placeholder="심볼 검색" autocomplete="off">
                <button class="searchline__btn" type="button" onclick="document.getElementById('drawerSearch').value=''; fxFilterDrawer('');">Clear</button>
            </div>
            <div class="seg-tabs">
                <button class="seg is-active" type="button">Favorites</button>
                <button class="seg" type="button">USDT-M</button>
                <button class="seg" type="button">Hot</button>
            </div>
            <div class="drawerlist scroll" id="drawerList">
                <?php foreach ($symbols as $s): ?>
                    <button class="drow" type="button" data-symbol="<?= htmlspecialchars($s['symbol']) ?>"
                            onclick="fxPick('<?= htmlspecialchars($s['symbol']) ?>','<?= htmlspecialchars($s['color']) ?>','<?= htmlspecialchars($s['glyph']) ?>',<?= (float)$s['price'] ?>,<?= (float)$s['change'] ?>); fxToggleDrawer(false);">
                        <div class="drow__l">
                            <span class="coin" style="--coin:<?= htmlspecialchars($s['color']) ?>"><?= htmlspecialchars($s['glyph']) ?></span>
                            <div>
                                <div class="fw-800"><?= htmlspecialchars($s['coin']) ?><span class="muted">/<?= htmlspecialchars($quote) ?></span></div>
                                <div class="muted fs-11">Vol <?= fx_compact($s['vol']) ?> · Funding <?= number_format($s['funding'],4) ?>%</div>
                            </div>
                        </div>
                        <div class="drow__r">
                            <div class="mono fw-800 tar"><?= fx_fmt($s['price'],$s['price']<1?6:2) ?></div>
                            <div class="mono tar <?= $s['change']>=0?'is-up':'is-down' ?>"><?= fx_pct($s['change'],2) ?></div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
const fxState = { side:'buy', last: <?= json_encode((float)$last) ?>, lev:10 };

function fxToast(msg, type='info'){
  const stack=document.getElementById('toastStack');
  const el=document.createElement('div');
  el.className=`toast toast--${type}`;
  el.innerHTML=`<div class="toast__ico">${type==='success'?'✓':type==='error'?'!':'i'}</div><div class="toast__msg">${msg}</div><button class="toast__x" type="button" aria-label="닫기">✕</button>`;
  stack.appendChild(el);
  const close=()=>{ el.classList.remove('is-on'); setTimeout(()=>el.remove(),220); };
  el.querySelector('.toast__x').addEventListener('click', close);
  setTimeout(()=>el.classList.add('is-on'),10);
  setTimeout(close, 2600);
}

function fxToggleDrawer(open){
  const d=document.getElementById('drawer');
  d.classList.toggle('is-open', !!open);
  document.body.classList.toggle('has-drawer', !!open);
}

function fxSwitchMobile(tab, btn){
  document.getElementById('layout').setAttribute('data-mobile-tab', tab);
  document.querySelectorAll('.mnav__btn').forEach(b=>b.classList.remove('is-active'));
  btn && btn.classList.add('is-active');
}

function fxSetSide(side){
  fxState.side=side;
  document.getElementById('btnBuy').classList.toggle('is-active', side==='buy');
  document.getElementById('btnSell').classList.toggle('is-active', side==='sell');
  const submit=document.getElementById('submitBtn');
  submit.classList.toggle('submit--buy', side==='buy');
  submit.classList.toggle('submit--sell', side==='sell');
  submit.textContent = side==='buy' ? '롱 / 매수 주문 (더미)' : '숏 / 매도 주문 (더미)';
}

function fxFillLast(){
  document.getElementById('inPrice').value = fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  fxToast('마지막 가격 적용 (더미)');
  fxRecalc();
}

function fxRecalc(){
  const price=Number(String(document.getElementById('inPrice').value||'').replace(/,/g,'').trim())||0;
  const qty=Number(String(document.getElementById('inQty').value||'').replace(/,/g,'').trim())||0;
  const notional=price*qty;
  const margin = fxState.lev>0 ? (notional/fxState.lev) : 0;
  document.getElementById('estNotional').textContent = notional.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('estMargin').textContent = margin.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
}

function fxPick(symbol, color, glyph, price, change){
  fxState.last = Number(price)||fxState.last;
  document.getElementById('activeSymbol').textContent = symbol;
  document.getElementById('symText').textContent = symbol;
  const logo=document.getElementById('symLogo');
  logo.style.setProperty('--coin', color);
  logo.textContent = glyph;
  const lastEl=document.getElementById('lastPrice');
  lastEl.textContent = fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  lastEl.classList.toggle('is-up', change>=0);
  lastEl.classList.toggle('is-down', change<0);
  const chg=document.getElementById('chgPct');
  chg.textContent = `${change>=0?'+':''}${Number(change).toFixed(2)}%`;
  chg.classList.toggle('is-up', change>=0);
  chg.classList.toggle('is-down', change<0);
  document.getElementById('obMid').textContent = fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('inPrice').value = fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  document.querySelectorAll('.mrow').forEach(r=>r.classList.toggle('is-active', r.dataset.symbol===symbol));
  fxRecalc();
  fxRenderChart();
  fxToast(`${symbol} 선택 (더미)`);
}

function fxFilterList(q){
  const query=String(q||'').trim().toUpperCase();
  document.querySelectorAll('#marketList .mrow').forEach(r=>{
    const sym=String(r.dataset.symbol||'').toUpperCase();
    const coin=String(r.dataset.coin||'').toUpperCase();
    r.style.display = (!query || sym.includes(query) || coin.includes(query)) ? '' : 'none';
  });
}

function fxFilterDrawer(q){
  const query=String(q||'').trim().toUpperCase();
  document.querySelectorAll('#drawerList .drow').forEach(r=>{
    const sym=String(r.dataset.symbol||'').toUpperCase();
    r.style.display = (!query || sym.includes(query)) ? '' : 'none';
  });
}

function fxInitTabs(){
  document.querySelectorAll('.tab[data-tab]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const key=btn.dataset.tab;
      document.querySelectorAll('.tab[data-tab]').forEach(b=>b.classList.toggle('is-active', b===btn));
      document.querySelectorAll('.tabpane').forEach(p=>p.classList.toggle('is-active', p.id===`tab-${key}`));
    });
  });
  document.querySelectorAll('.tab-mini[data-ob]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const key=btn.dataset.ob;
      document.querySelectorAll('.tab-mini[data-ob]').forEach(b=>b.classList.toggle('is-active', b===btn));
      document.querySelectorAll('.obpane').forEach(p=>p.classList.toggle('is-active', p.id===`ob-${key}`));
    });
  });
  document.querySelectorAll('.btn[data-tf]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.btn[data-tf]').forEach(b=>b.classList.toggle('is-active', b===btn));
      fxRenderChart();
      fxToast(`타임프레임 ${btn.dataset.tf} (더미)`);
    });
  });
}

function fxRenderChart(){
  const c=document.getElementById('fxChart');
  const dpr=window.devicePixelRatio||1;
  const rect=c.getBoundingClientRect();
  const w=Math.max(320, Math.floor(rect.width));
  const h=Math.max(260, Math.floor(rect.height));
  c.width=Math.floor(w*dpr);
  c.height=Math.floor(h*dpr);
  const ctx=c.getContext('2d');
  ctx.setTransform(dpr,0,0,dpr,0,0);
  ctx.clearRect(0,0,w,h);
  // bg
  const g=ctx.createLinearGradient(0,0,0,h);
  g.addColorStop(0,'rgba(255,255,255,0.02)');
  g.addColorStop(1,'rgba(0,0,0,0.18)');
  ctx.fillStyle=g; ctx.fillRect(0,0,w,h);
  // grid
  ctx.strokeStyle='rgba(255,255,255,0.05)'; ctx.lineWidth=1;
  for(let i=1;i<=5;i++){ const y=(h/6)*i; ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(w,y); ctx.stroke(); }
  for(let i=1;i<=8;i++){ const x=(w/9)*i; ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x,h); ctx.stroke(); }
  // candles
  const pad=18;
  const count=Math.max(70, Math.floor(w/14));
  let last=fxState.last + (Math.random()-0.5)*180;
  const cs=[];
  for(let i=0;i<count;i++){
    const o=last;
    const cl=o+(Math.random()-0.5)*220;
    const hi=Math.max(o,cl)+Math.random()*120;
    const lo=Math.min(o,cl)-Math.random()*120;
    cs.push({o,cl,hi,lo});
    last=cl;
  }
  const maxP=Math.max(...cs.map(x=>x.hi));
  const minP=Math.min(...cs.map(x=>x.lo));
  const rng=Math.max(1e-6, maxP-minP);
  const toY=p=> pad + (h-pad*2)*(1-((p-minP)/rng));
  const slot=(w-pad*2)/count;
  const cw=Math.max(3, Math.min(10, slot*0.6));
  for(let i=0;i<cs.length;i++){
    const x=pad+i*slot+(slot-cw)/2;
    const {o,cl,hi,lo}=cs[i];
    const up=cl>=o;
    ctx.strokeStyle=up?'rgba(14,203,129,0.85)':'rgba(246,70,93,0.85)';
    ctx.fillStyle=up?'rgba(14,203,129,0.70)':'rgba(246,70,93,0.70)';
    ctx.beginPath(); ctx.moveTo(x+cw/2,toY(hi)); ctx.lineTo(x+cw/2,toY(lo)); ctx.stroke();
    const y1=toY(o), y2=toY(cl);
    const top=Math.min(y1,y2);
    const bh=Math.max(2, Math.abs(y2-y1));
    ctx.fillRect(x, top, cw, bh);
  }
  // last line + tag
  const y=toY(fxState.last);
  ctx.setLineDash([8,6]);
  ctx.strokeStyle='rgba(255,255,255,0.25)';
  ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(w,y); ctx.stroke();
  ctx.setLineDash([]);
  const tagW=118, tagH=26, tx=w-tagW-12, ty=Math.max(10, Math.min(h-tagH-10, y-tagH/2));
  ctx.fillStyle='rgba(240,185,11,0.92)'; ctx.fillRect(tx,ty,tagW,tagH);
  ctx.fillStyle='#0b0e11'; ctx.font='700 13px ui-monospace, Menlo, Consolas, monospace';
  ctx.fillText(fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}), tx+10, ty+17);
}

function fxSimulate(){
  setInterval(()=>{
    const d=(Math.random()-0.5)*6.5;
    fxState.last=Math.max(0.01, fxState.last+d);
    const el=document.getElementById('lastPrice');
    el.textContent=fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
    el.classList.toggle('is-up', d>=0);
    el.classList.toggle('is-down', d<0);
    document.getElementById('obMid').textContent=fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
    const inPrice=document.getElementById('inPrice');
    if(document.activeElement!==inPrice){
      inPrice.value=fxState.last.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
    }
    fxRenderChart();
  }, 2200);
}

window.addEventListener('DOMContentLoaded', ()=>{
  fxInitTabs();
  fxSetSide('buy');
  fxRecalc();
  fxRenderChart();
  fxSimulate();
  document.getElementById('marketSearch').addEventListener('input', e=>fxFilterList(e.target.value));
  document.getElementById('drawerSearch').addEventListener('input', e=>fxFilterDrawer(e.target.value));
  document.getElementById('inPrice').addEventListener('input', fxRecalc);
  document.getElementById('inQty').addEventListener('input', fxRecalc);
  document.getElementById('inPct').addEventListener('input', e=>{
    const pct=Number(e.target.value)||0;
    const price=Number(String(document.getElementById('inPrice').value||'').replace(/,/g,''))||fxState.last||1;
    const avail=<?= json_encode((float)$availMargin) ?>;
    const notional=(avail*(pct/100))*fxState.lev;
    const qty=Math.max(0, notional/Math.max(1e-9,price));
    document.getElementById('inQty').value=qty.toFixed(3);
    fxRecalc();
  });
  window.addEventListener('resize', fxRenderChart);
  document.addEventListener('keydown', (e)=>{
    if(e.key==='Escape') fxToggleDrawer(false);
  });
});
</script>

</body>
</html>

