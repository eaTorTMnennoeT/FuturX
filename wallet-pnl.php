<?php
/**
 * P&L Analysis Page
 * 손익 분석 페이지 - 거래 내역 및 수익률 분석
 */

include_once 'includes/bootstrap.php';

// 인증 체크
$authToken = $_COOKIE['auth_token'] ?? null;
$currentUser = null;
$trades = [];

if ($authToken) {
    try {
        $db = getDB();

        // 사용자 정보 가져오기
        $stmt = $db->prepare("SELECT u.* FROM users u JOIN user_sessions s ON u.id = s.user_id WHERE s.session_token = ? AND s.expires_at > NOW()");
        $stmt->execute([$authToken]);
        $currentUser = $stmt->fetch();

        if ($currentUser) {
            // 기간 필터
            $period = $_GET['period'] ?? '30';
            $periodDays = intval($period);

            // 실제 closed positions + 수수료 가져오기
            $sql = "SELECT p.*,
                    TIMESTAMPDIFF(SECOND, p.opened_at, p.closed_at) as duration_seconds,
                    (SELECT SUM(t.fee) FROM trades t WHERE t.position_id = p.id
                        AND ((p.side = 'long' AND t.side = 'buy') OR (p.side = 'short' AND t.side = 'sell'))
                    ) as opening_fee,
                    (SELECT SUM(t.fee) FROM trades t WHERE t.position_id = p.id
                        AND ((p.side = 'long' AND t.side = 'sell') OR (p.side = 'short' AND t.side = 'buy'))
                    ) as closing_fee
                    FROM positions p
                    WHERE p.user_id = ? AND p.status = 'closed'";

            if ($periodDays > 0) {
                $sql .= " AND p.closed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            }
            $sql .= " ORDER BY p.closed_at DESC";

            $stmt = $db->prepare($sql);
            if ($periodDays > 0) {
                $stmt->execute([$currentUser['id'], $periodDays]);
            } else {
                $stmt->execute([$currentUser['id']]);
            }
            $positions = $stmt->fetchAll();

            // 데이터 변환
            foreach ($positions as $pos) {
                $entryPrice = floatval($pos['entry_price']);
                $exitPrice = floatval($pos['exit_price'] ?? 0);
                $margin = floatval($pos['margin'] ?? 0);
                $leverage = floatval($pos['leverage'] ?? 1);
                $size = floatval($pos['size'] ?? 0);
                $realizedPnl = floatval($pos['realized_pnl'] ?? 0);

                // 수량 계산
                $qty = $size > 0 ? $size : ($entryPrice > 0 ? ($margin / $entryPrice * $leverage) : 0);
                $entryValue = $qty * $entryPrice;

                // exit_price가 없으면 PnL로 역산
                if ($exitPrice <= 0 && $entryPrice > 0 && $qty > 0) {
                    $side = strtolower($pos['side']);
                    if ($side === 'long') {
                        $exitPrice = $entryPrice + ($realizedPnl / $qty);
                    } else {
                        $exitPrice = $entryPrice - ($realizedPnl / $qty);
                    }
                }

                $exitValue = $qty * $exitPrice;
                $pnlPercent = $entryValue > 0 ? ($realizedPnl / $entryValue) * 100 : 0;

                // Duration 계산
                $durationSec = $pos['duration_seconds'] ?? 0;
                if ($durationSec >= 86400) {
                    $days = floor($durationSec / 86400);
                    $hours = floor(($durationSec % 86400) / 3600);
                    $duration = "{$days}D {$hours}H";
                } elseif ($durationSec >= 3600) {
                    $hours = floor($durationSec / 3600);
                    $mins = floor(($durationSec % 3600) / 60);
                    $duration = "{$hours}H {$mins}M";
                } else {
                    $mins = floor($durationSec / 60);
                    $secs = $durationSec % 60;
                    $duration = "{$mins}M {$secs}S";
                }

                // DB에서 가져온 실제 수수료
                $openingFee = floatval($pos['opening_fee'] ?? 0);
                $closingFee = floatval($pos['closing_fee'] ?? 0);
                $totalFee = $openingFee + $closingFee;

                // Net P&L = Gross P&L - Total Fee (바이빗 방식)
                $netPnl = $realizedPnl - $totalFee;

                $trades[] = [
                    'id' => $pos['id'],
                    'symbol' => $pos['symbol'],
                    'type' => 'Perp',
                    'market' => 'USDT Perpetuals',
                    'side' => ucfirst($pos['side']),
                    'entry_price' => $entryPrice,
                    'exit_price' => $exitPrice,
                    'leverage' => $leverage,
                    'qty' => $qty,
                    'pnl' => $netPnl,
                    'gross_pnl' => $realizedPnl,
                    'pnl_percent' => $pnlPercent,
                    'entry_value' => $entryValue,
                    'exit_value' => $exitValue,
                    'opening_fee' => $openingFee,
                    'closing_fee' => $closingFee,
                    'funding_fee' => 0,
                    'open_time' => $pos['opened_at'],
                    'duration' => $duration,
                    'close_time' => $pos['closed_at'],
                    'status' => 'closed'
                ];
            }
        }
    } catch (Exception $e) {
        // 오류 무시
    }
}

// 요약 통계 계산 (gross_pnl 기반으로 Win/Loss 판단)
$totalPnl = array_sum(array_column($trades, 'pnl'));
$totalGrossPnl = array_sum(array_column($trades, 'gross_pnl'));
$winTrades = array_filter($trades, fn($t) => $t['gross_pnl'] > 0);
$loseTrades = array_filter($trades, fn($t) => $t['gross_pnl'] <= 0);
$winRate = count($trades) > 0 ? (count($winTrades) / count($trades)) * 100 : 0;
$totalFees = array_sum(array_column($trades, 'opening_fee')) + array_sum(array_column($trades, 'closing_fee'));
$avgPnl = count($trades) > 0 ? $totalPnl / count($trades) : 0;
$totalVolume = array_sum(array_column($trades, 'entry_value'));

// Long/Short 분리 통계 (gross_pnl 기반)
$longTrades = array_filter($trades, fn($t) => strtolower($t['side']) === 'long');
$shortTrades = array_filter($trades, fn($t) => strtolower($t['side']) === 'short');
$longGrossPnl = array_sum(array_column($longTrades, 'gross_pnl'));
$shortGrossPnl = array_sum(array_column($shortTrades, 'gross_pnl'));
$longPnl = array_sum(array_column($longTrades, 'pnl'));
$shortPnl = array_sum(array_column($shortTrades, 'pnl'));
$longWins = array_filter($longTrades, fn($t) => $t['gross_pnl'] > 0);
$longLoses = array_filter($longTrades, fn($t) => $t['gross_pnl'] <= 0);
$shortWins = array_filter($shortTrades, fn($t) => $t['gross_pnl'] > 0);
$shortLoses = array_filter($shortTrades, fn($t) => $t['gross_pnl'] <= 0);
$longWinRate = count($longTrades) > 0 ? (count($longWins) / count($longTrades)) * 100 : 0;
$shortWinRate = count($shortTrades) > 0 ? (count($shortWins) / count($shortTrades)) * 100 : 0;

// 평균 거래 시간 계산
function calcAvgDuration($trades) {
    if (count($trades) === 0) return '0M';
    $totalSeconds = 0;
    foreach ($trades as $t) {
        if (isset($t['open_time']) && isset($t['close_time'])) {
            $totalSeconds += strtotime($t['close_time']) - strtotime($t['open_time']);
        }
    }
    $avgSeconds = $totalSeconds / count($trades);
    if ($avgSeconds >= 86400) {
        $days = floor($avgSeconds / 86400);
        $hours = floor(($avgSeconds % 86400) / 3600);
        return "{$days}D {$hours}H";
    } elseif ($avgSeconds >= 3600) {
        $hours = floor($avgSeconds / 3600);
        $mins = floor(($avgSeconds % 3600) / 60);
        return "{$hours}H {$mins}M";
    } else {
        $mins = floor($avgSeconds / 60);
        return "{$mins}M";
    }
}
$longAvgDuration = calcAvgDuration($longTrades);
$shortAvgDuration = calcAvgDuration($shortTrades);

// Long/Short P&L 랭킹 (상위 3개 심볼)
function getTopSymbols($trades, $limit = 3) {
    $symbolPnl = [];
    foreach ($trades as $t) {
        $symbol = $t['symbol'];
        if (!isset($symbolPnl[$symbol])) $symbolPnl[$symbol] = 0;
        $symbolPnl[$symbol] += $t['gross_pnl'];
    }
    arsort($symbolPnl);
    return array_slice(array_keys($symbolPnl), 0, $limit);
}
$longTopSymbols = getTopSymbols($longTrades);
$shortTopSymbols = getTopSymbols($shortTrades);

// 심볼별 P&L 랭킹
$symbolPnl = [];
foreach ($trades as $t) {
    if (!isset($symbolPnl[$t['symbol']])) {
        $symbolPnl[$t['symbol']] = 0;
    }
    $symbolPnl[$t['symbol']] += $t['pnl'];
}
arsort($symbolPnl);
$pnlRanking = array_slice($symbolPnl, 0, 5, true);

$pageTitle = 'P&L Analysis';
$pageCSS = ['wallet.css', 'wallet-pnl.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<main class="main">
    <div class="wallet-container">
        <!-- 사이드바 -->
        <aside class="wallet-sidebar">
            <button class="wallet-sidebar__toggle" id="sidebarToggle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>
            <nav class="wallet-nav">
                <a href="wallet.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Overview
                </a>
                <a href="wallet-funding.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="M2 10h20"/>
                    </svg>
                    Funding
                </a>
                <a href="wallet-trading.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Unified Trading
                </a>
                <div class="wallet-nav__divider"></div>
                <span class="wallet-nav__label">분석</span>
                <a href="wallet-pnl.php" class="wallet-nav__item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18h18"/>
                        <path d="M18 17V9"/>
                        <path d="M13 17V5"/>
                        <path d="M8 17v-3"/>
                    </svg>
                    P&L Analysis
                </a>
                <a href="history.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Orders
                </a>
            </nav>
        </aside>

        <!-- 메인 콘텐츠 -->
        <main class="wallet-content">
            <!-- 페이지 헤더 -->
            <div class="pnl-header">
                <div class="pnl-header__left">
                    <h1>P&L Analysis</h1>
                    <span class="pnl-header__subtitle">손익 분석</span>
                </div>
                <div class="pnl-header__right">
                    <div class="pnl-period">
                        <button class="pnl-period__btn <?= ($_GET['period'] ?? '30') == '7' ? 'active' : '' ?>" data-period="7">7D</button>
                        <button class="pnl-period__btn <?= ($_GET['period'] ?? '30') == '30' ? 'active' : '' ?>" data-period="30">30D</button>
                        <button class="pnl-period__btn <?= ($_GET['period'] ?? '30') == '90' ? 'active' : '' ?>" data-period="90">90D</button>
                        <button class="pnl-period__btn <?= ($_GET['period'] ?? '30') == '180' ? 'active' : '' ?>" data-period="180">180D</button>
                        <button class="pnl-period__btn <?= ($_GET['period'] ?? '30') == '0' ? 'active' : '' ?>" data-period="0">All</button>
                    </div>
                    <button class="btn btn--outline" id="exportBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            <!-- P&L 요약 (상단) -->
            <div class="pnl-top-summary">
                <div class="pnl-top-summary__item">
                    <span class="pnl-top-summary__label">Total P&L</span>
                    <span class="pnl-top-summary__value <?= $totalPnl >= 0 ? 'positive' : 'negative' ?>">
                        <?= $totalPnl >= 0 ? '+' : '' ?><?= number_format($totalPnl, 2) ?> <small>USD</small>
                    </span>
                </div>
                <div class="pnl-top-summary__item">
                    <span class="pnl-top-summary__label">Trading Volume</span>
                    <span class="pnl-top-summary__value">
                        +<?= number_format($totalVolume, 2) ?> <small>USD</small>
                    </span>
                </div>
            </div>

            <!-- 차트 + 랭킹 섹션 -->
            <div class="pnl-charts-section">
                <!-- P&L 차트 -->
                <div class="pnl-chart-card">
                    <div class="pnl-chart-card__header">
                        <h3>P&L Chart <span class="chart-link">↗</span></h3>
                    </div>
                    <div class="pnl-chart-card__body">
                        <canvas id="pnlChart"></canvas>
                    </div>
                </div>

                <!-- P&L Ranking -->
                <div class="pnl-ranking-card">
                    <div class="pnl-ranking-card__header">
                        <h3>P&L Ranking <span class="chart-link">↗</span></h3>
                    </div>
                    <div class="pnl-ranking-card__body">
                        <div class="pnl-ranking__header">
                            <span>Contracts</span>
                            <span>P&L</span>
                        </div>
                        <?php
                        $maxAbsPnl = count($pnlRanking) > 0 ? max(array_map('abs', $pnlRanking)) : 1;
                        foreach ($pnlRanking as $symbol => $pnl):
                            $barWidth = $maxAbsPnl > 0 ? (abs($pnl) / $maxAbsPnl * 100) : 0;
                        ?>
                        <div class="pnl-ranking__item">
                            <div class="pnl-ranking__coin">
                                <img src="https://assets.coingecko.com/coins/images/<?= $symbol === 'BTCUSDT' ? '1/small/bitcoin.png' : ($symbol === 'ETHUSDT' ? '279/small/ethereum.png' : ($symbol === 'SOLUSDT' ? '4128/small/solana.png' : '1/small/bitcoin.png')) ?>" alt="<?= $symbol ?>">
                                <span><?= $symbol ?></span>
                            </div>
                            <div class="pnl-ranking__bar-container">
                                <div class="pnl-ranking__bar <?= $pnl >= 0 ? 'positive' : 'negative' ?>" style="width: <?= $barWidth ?>%"></div>
                            </div>
                            <div class="pnl-ranking__value <?= $pnl >= 0 ? 'positive' : 'negative' ?>">
                                <?= $pnl >= 0 ? '+' : '' ?><?= number_format($pnl, 2) ?> USD
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($pnlRanking) === 0): ?>
                        <div class="pnl-ranking__empty">No data available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Long/Short Positions 섹션 (바이빗 스타일) -->
            <div class="pnl-positions-section">
                <!-- Long Positions -->
                <div class="pnl-position-card">
                    <div class="pnl-position-card__header">
                        <h3>Long Positions <a href="#" class="pnl-position-card__link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></a></h3>
                    </div>
                    <div class="pnl-position-card__body">
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">Total</span>
                            <div class="pnl-position-stat__value"><?= count($longTrades) ?></div>
                            <span class="pnl-position-stat__sub"><?= count($longWins) ?> Win / <?= count($longLoses) ?> Loss</span>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">Win Rate</span>
                            <div class="pnl-position-stat__value">
                                <span class="pnl-position-stat__icon green">◐</span>
                                <?= number_format($longWinRate, 0) ?> %
                            </div>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">Avg. Duration</span>
                            <div class="pnl-position-stat__value"><?= $longAvgDuration ?></div>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">P&L</span>
                            <div class="pnl-position-stat__value <?= $longGrossPnl >= 0 ? 'positive' : 'negative' ?>">
                                <?= $longGrossPnl >= 0 ? '+' : '' ?><?= number_format($longGrossPnl, 2) ?> <small>USD</small>
                            </div>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">P&L Ranking</span>
                            <div class="pnl-position-stat__coins">
                                <?php foreach ($longTopSymbols as $symbol): ?>
                                <img src="https://assets.coingecko.com/coins/images/<?= $symbol === 'BTCUSDT' ? '1/small/bitcoin.png' : ($symbol === 'ETHUSDT' ? '279/small/ethereum.png' : ($symbol === 'SOLUSDT' ? '4128/small/solana.png' : ($symbol === 'DOGEUSDT' ? '5/small/dogecoin.png' : ($symbol === 'BNBUSDT' ? '825/small/bnb.png' : '1/small/bitcoin.png')))) ?>" alt="<?= $symbol ?>" title="<?= $symbol ?>">
                                <?php endforeach; ?>
                                <?php if (count($longTopSymbols) === 0): ?>
                                <span class="no-data">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Short Positions -->
                <div class="pnl-position-card">
                    <div class="pnl-position-card__header">
                        <h3>Short Positions <a href="#" class="pnl-position-card__link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></a></h3>
                    </div>
                    <div class="pnl-position-card__body">
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">Total</span>
                            <div class="pnl-position-stat__value"><?= count($shortTrades) ?></div>
                            <span class="pnl-position-stat__sub"><?= count($shortWins) ?> Win / <?= count($shortLoses) ?> Loss</span>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">Win Rate</span>
                            <div class="pnl-position-stat__value">
                                <span class="pnl-position-stat__icon green">◐</span>
                                <?= number_format($shortWinRate, 0) ?> %
                            </div>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">Avg. Duration</span>
                            <div class="pnl-position-stat__value"><?= $shortAvgDuration ?></div>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">P&L</span>
                            <div class="pnl-position-stat__value <?= $shortGrossPnl >= 0 ? 'positive' : 'negative' ?>">
                                <?= $shortGrossPnl >= 0 ? '+' : '' ?><?= number_format($shortGrossPnl, 2) ?> <small>USD</small>
                            </div>
                        </div>
                        <div class="pnl-position-stat">
                            <span class="pnl-position-stat__label">P&L Ranking</span>
                            <div class="pnl-position-stat__coins">
                                <?php foreach ($shortTopSymbols as $symbol): ?>
                                <img src="https://assets.coingecko.com/coins/images/<?= $symbol === 'BTCUSDT' ? '1/small/bitcoin.png' : ($symbol === 'ETHUSDT' ? '279/small/ethereum.png' : ($symbol === 'SOLUSDT' ? '4128/small/solana.png' : ($symbol === 'DOGEUSDT' ? '5/small/dogecoin.png' : ($symbol === 'BNBUSDT' ? '825/small/bnb.png' : '1/small/bitcoin.png')))) ?>" alt="<?= $symbol ?>" title="<?= $symbol ?>">
                                <?php endforeach; ?>
                                <?php if (count($shortTopSymbols) === 0): ?>
                                <span class="no-data">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 상세 테이블 섹션 -->
            <div class="pnl-details-section">
                <div class="pnl-details-header">
                    <h3>Details of Closed Orders</h3>
                    <div class="pnl-details-controls">
                        <span class="pnl-details-timestamp">Real-time updates, last refreshed at <?= date('Y-m-d H:i:s') ?> (UTC)</span>
                        <button class="pnl-refresh-btn" onclick="location.reload()">↻</button>
                    </div>
                </div>

                <div class="pnl-type-tabs">
                    <button class="pnl-type-tab active">USDT Perpetuals</button>
                </div>

                <!-- 거래 내역 테이블 -->
                <div class="pnl-table-wrapper">
                <div class="pnl-table">
                    <div class="pnl-table__header">
                        <div class="pnl-table__col pnl-table__col--symbol">Contracts</div>
                        <div class="pnl-table__col pnl-table__col--qty">Qty</div>
                        <div class="pnl-table__col pnl-table__col--entry">Entry Price</div>
                        <div class="pnl-table__col pnl-table__col--exit">Exit Price</div>
                        <div class="pnl-table__col pnl-table__col--type">Trade Type</div>
                        <div class="pnl-table__col pnl-table__col--pnl">Closed P&L</div>
                        <div class="pnl-table__col pnl-table__col--pnl">Real P&L</div>
                        <div class="pnl-table__col pnl-table__col--result">Result</div>
                        <div class="pnl-table__col pnl-table__col--volume">Closed Trade Vol</div>
                        <div class="pnl-table__col pnl-table__col--fee">Opening Fee</div>
                        <div class="pnl-table__col pnl-table__col--fee">Closing Fee</div>
                        <div class="pnl-table__col pnl-table__col--fee">Funding Fee</div>
                        <div class="pnl-table__col pnl-table__col--time">Trade Time</div>
                    </div>
                    <div class="pnl-table__body" id="tradeList">
                        <?php if (count($trades) === 0): ?>
                        <div class="pnl-table__empty">
                            <p>No trading data available</p>
                            <span>Start trading to see your performance</span>
                        </div>
                        <?php else: ?>
                        <?php foreach ($trades as $trade): ?>
                        <div class="pnl-table__row"
                             data-symbol="<?= $trade['symbol'] ?>"
                             data-side="<?= $trade['side'] ?>"
                             data-result="<?= $trade['pnl'] >= 0 ? 'win' : 'lose' ?>">
                            <div class="pnl-table__col pnl-table__col--symbol" data-label="Contracts">
                                <div class="pnl-contract">
                                    <img src="https://assets.coingecko.com/coins/images/<?= $trade['symbol'] === 'BTCUSDT' ? '1/small/bitcoin.png' : ($trade['symbol'] === 'ETHUSDT' ? '279/small/ethereum.png' : ($trade['symbol'] === 'SOLUSDT' ? '4128/small/solana.png' : '1/small/bitcoin.png')) ?>" alt="<?= $trade['symbol'] ?>">
                                    <span><?= $trade['symbol'] ?></span>
                                </div>
                            </div>
                            <div class="pnl-table__col pnl-table__col--qty" data-label="Qty">
                                <?= number_format($trade['qty'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--entry" data-label="Entry Price">
                                <?= number_format($trade['entry_price'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--exit" data-label="Exit Price">
                                <?= number_format($trade['exit_price'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--type" data-label="Trade Type">
                                <span class="pnl-trade-type <?= strtolower($trade['side']) === 'long' ? 'short' : 'long' ?>">
                                    Close <?= strtolower($trade['side']) === 'long' ? 'Short' : 'Long' ?>
                                </span>
                            </div>
                            <div class="pnl-table__col pnl-table__col--pnl <?= $trade['gross_pnl'] >= 0 ? 'positive' : 'negative' ?>" data-label="Closed P&L">
                                <?= $trade['gross_pnl'] >= 0 ? '+' : '' ?><?= number_format($trade['gross_pnl'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--pnl <?= $trade['pnl'] >= 0 ? 'positive' : 'negative' ?>" data-label="Real P&L">
                                <?= $trade['pnl'] >= 0 ? '+' : '' ?><?= number_format($trade['pnl'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--result" data-label="Result">
                                <span class="pnl-result-badge <?= $trade['gross_pnl'] >= 0 ? 'win' : 'loss' ?>">
                                    <?= $trade['gross_pnl'] >= 0 ? 'Win' : 'Loss' ?>
                                </span>
                            </div>
                            <div class="pnl-table__col pnl-table__col--volume" data-label="Closed Trade Vol">
                                <?= number_format($trade['exit_value'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--fee" data-label="Opening Fee">
                                -<?= number_format($trade['opening_fee'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--fee" data-label="Closing Fee">
                                -<?= number_format($trade['closing_fee'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--fee" data-label="Funding Fee">
                                <?= number_format($trade['funding_fee'], 2) ?>
                            </div>
                            <div class="pnl-table__col pnl-table__col--time" data-label="Trade Time">
                                <div class="pnl-time">
                                    <span class="pnl-time__open"><?= date('Y-m-d H:i', strtotime($trade['open_time'])) ?></span>
                                    <span class="pnl-time__close"><?= date('Y-m-d H:i', strtotime($trade['close_time'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                </div>

                <!-- 페이지네이션 -->
                <div class="pnl-pagination">
                    <button class="pnl-pagination__btn" disabled>Previous</button>
                    <button class="pnl-pagination__btn" disabled>Next</button>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 사이드바 토글
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.wallet-sidebar');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }

        // 기간 선택
        const periodBtns = document.querySelectorAll('.pnl-period__btn');
        periodBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const period = this.dataset.period;
                window.location.href = `wallet-pnl.php?period=${period}`;
            });
        });

        // 통계 탭
        const statsTabs = document.querySelectorAll('.pnl-stats-tab');
        statsTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                statsTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Type 탭
        const typeTabs = document.querySelectorAll('.pnl-type-tab');
        typeTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                typeTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // P&L 차트 초기화
        initChart();

        function initChart() {
            const canvas = document.getElementById('pnlChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const rect = canvas.parentElement.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = 220;

            // PHP에서 전달받은 데이터
            const trades = <?= json_encode($trades) ?>;

            // 날짜별 누적 P&L 계산
            const dailyPnl = {};
            trades.forEach(trade => {
                const date = trade.close_time ? trade.close_time.split(' ')[0] : '';
                if (date) {
                    if (!dailyPnl[date]) dailyPnl[date] = 0;
                    dailyPnl[date] += parseFloat(trade.pnl) || 0;
                }
            });

            const sortedDates = Object.keys(dailyPnl).sort();
            let cumulative = 0;
            const data = sortedDates.map(date => {
                cumulative += dailyPnl[date];
                return cumulative;
            });

            const labels = sortedDates.map(d => d.substring(5)); // MM-DD 형식

            if (data.length === 0) {
                ctx.fillStyle = '#848e9c';
                ctx.font = '14px Inter';
                ctx.textAlign = 'center';
                ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
                return;
            }

            drawChart(ctx, canvas, labels, data);
        }

        function drawChart(ctx, canvas, labels, data) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const padding = { top: 20, right: 20, bottom: 30, left: 70 };
            const chartWidth = canvas.width - padding.left - padding.right;
            const chartHeight = canvas.height - padding.top - padding.bottom;

            const maxVal = Math.max(...data, 0);
            const minVal = Math.min(...data, 0);
            const range = (maxVal - minVal) || 1;

            // 그리드
            ctx.strokeStyle = '#2b3139';
            ctx.lineWidth = 1;

            for (let i = 0; i <= 4; i++) {
                const y = padding.top + (chartHeight * i / 4);
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(canvas.width - padding.right, y);
                ctx.stroke();

                const value = maxVal - (range * i / 4);
                ctx.fillStyle = '#848e9c';
                ctx.font = '11px Inter';
                ctx.textAlign = 'right';
                ctx.fillText(value.toFixed(0), padding.left - 10, y + 4);
            }

            // X축 레이블
            ctx.textAlign = 'center';
            const labelStep = Math.ceil(labels.length / 6);
            labels.forEach((label, i) => {
                if (i % labelStep === 0) {
                    const x = padding.left + (chartWidth * i / (labels.length - 1 || 1));
                    ctx.fillText(label, x, canvas.height - 8);
                }
            });

            // 0 기준선
            if (minVal < 0 && maxVal > 0) {
                const zeroY = padding.top + chartHeight * (maxVal / range);
                ctx.strokeStyle = '#5e6673';
                ctx.setLineDash([5, 5]);
                ctx.beginPath();
                ctx.moveTo(padding.left, zeroY);
                ctx.lineTo(canvas.width - padding.right, zeroY);
                ctx.stroke();
                ctx.setLineDash([]);
            }

            // 영역 채우기
            const zeroY = padding.top + chartHeight * (maxVal / range);
            ctx.beginPath();
            ctx.moveTo(padding.left, zeroY);

            data.forEach((val, i) => {
                const x = padding.left + (chartWidth * i / (data.length - 1 || 1));
                const y = padding.top + chartHeight * ((maxVal - val) / range);
                ctx.lineTo(x, y);
            });

            ctx.lineTo(padding.left + chartWidth, zeroY);
            ctx.closePath();

            const gradient = ctx.createLinearGradient(0, padding.top, 0, canvas.height - padding.bottom);
            const lastVal = data[data.length - 1] || 0;
            if (lastVal >= 0) {
                gradient.addColorStop(0, 'rgba(14, 203, 129, 0.3)');
                gradient.addColorStop(1, 'rgba(14, 203, 129, 0)');
            } else {
                gradient.addColorStop(0, 'rgba(246, 70, 93, 0)');
                gradient.addColorStop(1, 'rgba(246, 70, 93, 0.3)');
            }
            ctx.fillStyle = gradient;
            ctx.fill();

            // 라인
            ctx.beginPath();
            data.forEach((val, i) => {
                const x = padding.left + (chartWidth * i / (data.length - 1 || 1));
                const y = padding.top + chartHeight * ((maxVal - val) / range);
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.strokeStyle = lastVal >= 0 ? '#0ecb81' : '#f6465d';
            ctx.lineWidth = 2;
            ctx.stroke();

            // 포인트
            data.forEach((val, i) => {
                const x = padding.left + (chartWidth * i / (data.length - 1 || 1));
                const y = padding.top + chartHeight * ((maxVal - val) / range);

                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fillStyle = val >= 0 ? '#0ecb81' : '#f6465d';
                ctx.fill();
            });
        }

        // Export 버튼
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                alert('Trade history exported to CSV');
            });
        }

        // 리사이즈 대응
        window.addEventListener('resize', initChart);
    });
    </script>
</body>
</html>
