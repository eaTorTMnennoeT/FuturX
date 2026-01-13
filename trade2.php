<?php
/**
 * Trade2 - 풀 반응형 선물거래 페이지
 * position: fixed 기반 viewport 고정 레이아웃
 */

require_once __DIR__ . '/extend/exchange_rate.extend.php';
require_once __DIR__ . '/config.php';

$usdKrwRate = getUsdKrwRate();
$symbolsData = getSymbols(true);
$supportedSymbols = array_keys($symbolsData);

// URL에서 심볼 파라미터 받기
$urlSymbol = isset($_GET['symbol']) ? strtoupper(trim($_GET['symbol'])) : 'BTCUSDT';
if (!in_array($urlSymbol, $supportedSymbols)) {
    $urlSymbol = 'BTCUSDT';
}

$currentLogo = isset($symbolsData[$urlSymbol]['logo_url']) && $symbolsData[$urlSymbol]['logo_url']
    ? $symbolsData[$urlSymbol]['logo_url']
    : 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
$baseCoin = str_replace('USDT', '', $urlSymbol);

$pageTitle = $urlSymbol . ' 무기한';
$pageCSS = ['trade2.css'];
$bodyClass = 'trade2-page'; // body에 클래스 추가
require_once 'includes/head.php';
?>
<script src="<?= WS_SERVER_URL ?>/socket.io/socket.io.js"></script>
<script src="https://unpkg.com/lightweight-charts@4.1.0/dist/lightweight-charts.standalone.production.js"></script>

<?php require_once 'includes/header.php'; ?>

<script>window.initialSymbol = '<?= $urlSymbol ?>';</script>

<!-- 메인 레이아웃 -->
<div class="trade2-layout">

    <!-- 심볼 바 (trade.php 스타일) -->
    <div class="trade2-symbol-bar">
        <div class="symbol-selector" id="symbolSelector">
            <div class="symbol-main" id="symbolDropdownTrigger">
                <span class="symbol-star" id="symbolFavorite" title="즐겨찾기">☆</span>
                <img src="<?= htmlspecialchars($currentLogo) ?>" alt="<?= htmlspecialchars($baseCoin) ?>" class="coin-logo" id="currentCoinLogo">
                <div class="symbol-text">
                    <div class="symbol-row-1">
                        <span class="symbol-name" id="currentSymbolName"><?= htmlspecialchars($urlSymbol) ?></span>
                        <span class="dropdown-arrow">▾</span>
                    </div>
                    <div class="symbol-row-2">
                        <span class="symbol-type">Perp</span>
                        <span class="mobile-change up" id="mobileChangePercent">+0.00%</span>
                    </div>
                </div>
            </div>
            <!-- 바이낸스 스타일 코인 드롭다운 -->
            <div class="symbol-dropdown" id="symbolDropdown">
                <div class="dropdown-search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input type="text" class="dropdown-search" placeholder="Search" id="symbolSearch">
                </div>
                <div class="dropdown-tabs-row">
                    <div class="dropdown-tabs">
                        <button class="dropdown-tab" data-tab="favorites">Favorites</button>
                        <button class="dropdown-tab active" data-tab="usdm">USDⓈ-M</button>
                    </div>
                </div>
                <div class="dropdown-list-header">
                    <span class="header-col col-symbol">
                        <span class="sort-btn" data-sort="symbol">Symbols <span class="sort-icon"><span class="arrow-up">▲</span><span class="arrow-down">▼</span></span></span>
                        <span class="sort-separator">/</span>
                        <span class="sort-btn" data-sort="volume">Vol <span class="sort-icon"><span class="arrow-up">▲</span><span class="arrow-down">▼</span></span></span>
                    </span>
                    <span class="header-col col-price">
                        <span class="sort-btn" data-sort="price">Last Price <span class="sort-icon"><span class="arrow-up">▲</span><span class="arrow-down">▼</span></span></span>
                    </span>
                    <span class="header-col col-change">
                        <span class="sort-btn" data-sort="change">24h Chg <span class="sort-icon"><span class="arrow-up">▲</span><span class="arrow-down">▼</span></span></span>
                    </span>
                </div>
                <div class="dropdown-list" id="symbolList">
                    <!-- 동적으로 생성됨 -->
                </div>
                <div class="dropdown-empty" id="dropdownEmpty" style="display: none;">
                    <span>즐겨찾기가 없습니다</span>
                </div>
            </div>
        </div>
        <div class="price-section">
            <span class="current-price up" id="mainPrice">0.00</span>
            <span class="mark-price" id="markPrice">0.00</span>
        </div>
        <span class="bar-divider">|</span>
        <div class="stat-item">
            <span class="stat-label">Index Price</span>
            <span class="stat-value" id="indexPrice">0.00</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Change %</span>
            <span class="stat-value up" id="changePercent">+0.00 (+0.00%)</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">24H High</span>
            <span class="stat-value" id="high24h">0.00</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">24H Low</span>
            <span class="stat-value" id="low24h">0.00</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">24H Turnover(USDT)</span>
            <span class="stat-value" id="turnover24h">0.00</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Open Interest(<span id="oiCoinLabel"><?= htmlspecialchars($baseCoin) ?></span>)</span>
            <span class="stat-value" id="openInterest">0.00</span>
        </div>
        <div class="stat-item funding">
            <span class="stat-label">Funding Rate / Countdown</span>
            <span class="stat-value up" id="fundingRate">0.00% / 00:00:00</span>
        </div>
        <!-- 모바일용 아이콘 -->
        <div class="mobile-icons">
            <span class="mobile-icon" title="Chart">📈</span>
            <span class="mobile-icon" title="Settings">⚙</span>
            <span class="mobile-icon" title="More">⋯</span>
        </div>
    </div>

    <!-- 차트 영역 -->
    <div class="trade2-chart">
        <div class="chart-toolbar">
            <button class="interval-btn" data-interval="1">1m</button>
            <button class="interval-btn active" data-interval="5">5m</button>
            <button class="interval-btn" data-interval="15">15m</button>
            <button class="interval-btn" data-interval="60">1h</button>
            <button class="interval-btn" data-interval="240">4h</button>
            <button class="interval-btn" data-interval="D">1D</button>
        </div>
        <div class="chart-container">
            <div id="chart"></div>
        </div>
    </div>

    <!-- 오더북 -->
    <div class="trade2-orderbook">
        <div class="orderbook-header">
            <span>Price</span>
            <span>Qty</span>
            <span>Total</span>
        </div>
        <div class="orderbook-content">
            <div class="asks-section" id="asksList"></div>
            <div class="spread-row up" id="spreadPrice">0.00</div>
            <div class="bids-section" id="bidsList"></div>
        </div>
    </div>

    <!-- 하단 패널 -->
    <div class="trade2-bottom">
        <div class="bottom-tabs">
            <button class="bottom-tab active" data-tab="positions">Positions <span class="tab-badge" id="positionCount">0</span></button>
            <button class="bottom-tab" data-tab="orders">Open Orders <span class="tab-badge" id="orderCount">0</span></button>
            <button class="bottom-tab" data-tab="history">Order History</button>
            <button class="bottom-tab" data-tab="trades">Trade History</button>
            <button class="bottom-tab" data-tab="assets">Assets</button>
            <button class="bottom-tab" data-tab="pnl">P&L</button>
        </div>
        <!-- PC 필터 바 - trade.php 스타일 -->
        <div class="panel-filters" id="panelFilters">
            <button class="filter-btn active">Limit & Market Orders</button>
            <button class="filter-btn">Conditional</button>
            <button class="filter-btn">TP/SL</button>
            <button class="filter-btn">Trailing Stop</button>
            <button class="filter-btn">OCO</button>
            <button class="filter-btn">MMR Close</button>
        </div>
        <!-- 모바일 포지션 필터 바 -->
        <div class="mobile-position-filter">
            <label class="filter-checkbox">
                <input type="checkbox" id="currentMarketFilter">
                <span>Current Market</span>
            </label>
            <div class="filter-actions">
                <button class="filter-btn close-all-btn" id="closeAllBtn">Close All</button>
                <button class="filter-btn filter-icon-btn"><i class="fa fa-filter"></i></button>
            </div>
        </div>
        <div class="bottom-content">
            <div class="bottom-panel-section active" id="positionsPanel">
                <table class="positions-table">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Size</th>
                            <th>Entry Price</th>
                            <th>Mark Price</th>
                            <th>Liq. Price</th>
                            <th>Margin</th>
                            <th>PNL (ROE%)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="positionsBody">
                        <tr><td colspan="8" class="empty-cell">No open positions</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="bottom-panel-section" id="ordersPanel">
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <div>No open orders</div>
                </div>
            </div>
            <div class="bottom-panel-section" id="historyPanel">
                <div class="empty-state">
                    <div class="empty-icon">📜</div>
                    <div>No order history</div>
                </div>
            </div>
            <div class="bottom-panel-section" id="tradesPanel">
                <div class="empty-state">
                    <div class="empty-icon">💹</div>
                    <div>No trade history</div>
                </div>
            </div>
            <div class="bottom-panel-section" id="assetsPanel">
                <div class="empty-state">
                    <div class="empty-icon">💰</div>
                    <div>No assets</div>
                </div>
            </div>
            <div class="bottom-panel-section" id="pnlPanel">
                <!-- P&L 헤더 (Bybit 스타일) -->
                <div class="pnl-header">
                    <div class="pnl-header-left">
                        <div class="pnl-main-tabs">
                            <button class="pnl-main-tab" data-type="orders">Closed Orders</button>
                            <button class="pnl-main-tab active" data-type="positions">Closed Positions</button>
                        </div>
                        <div class="pnl-period-tabs">
                            <button class="pnl-period-tab active" data-period="7">Last 7D</button>
                            <button class="pnl-period-tab" data-period="30">Last 30D</button>
                            <button class="pnl-period-tab" data-period="180">Last 180D</button>
                        </div>
                    </div>
                    <div class="pnl-header-right">
                        <a href="wallet-pnl.php" class="pnl-performance-btn" target="_blank">
                            Trading Performance <span class="arrow">↗</span>
                        </a>
                    </div>
                </div>
                <!-- P&L 테이블 -->
                <div class="pnl-table-wrapper">
                    <table class="pnl-table" id="pnlTable">
                        <thead id="pnlTableHead">
                            <tr>
                                <th>Market</th>
                                <th>Instrument</th>
                                <th>Direction</th>
                                <th>Avg. Entry Price</th>
                                <th>Avg. Exit Price</th>
                                <th>Qty</th>
                                <th>P&L</th>
                                <th>Opening Fee</th>
                                <th>Closing Fee</th>
                                <th>Funding Fee</th>
                            </tr>
                        </thead>
                        <tbody id="pnlHistoryBody">
                            <tr><td colspan="10" class="empty-cell">No P&L data</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pnl-footer">
                    <div class="pnl-stats" id="pnlStats">Total: 0 records</div>
                    <div class="pnl-pagination" id="pnlPagination"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 거래 패널 -->
    <div class="trade2-panel">
        <div class="panel-header">
            <span class="panel-title">Trade</span>
            <div class="panel-icons">
                <span class="panel-icon" title="설정">⚙</span>
                <span class="panel-icon" title="계산기">🔢</span>
                <span class="panel-icon" title="더보기">⋮</span>
            </div>
        </div>

        <div class="panel-body">
            <!-- ========================================
                 모바일 전용 거래 영역 (768px 이하에서만 표시)
                 바이빗 스타일 2컬럼 레이아웃
                 ======================================== -->
            <div class="mobile-trade-area">
                <!-- 좌측: 주문 폼 -->
                <div class="bybit-order-form">
                    <!-- Cross / 레버리지 -->
                    <div class="bybit-margin-row">
                        <button class="bybit-dropdown-btn" id="marginTypeBtn">
                            Cross <span class="dropdown-arrow">▼</span>
                        </button>
                        <button class="bybit-dropdown-btn" id="leverageBtn2">
                            34x <span class="dropdown-arrow">▼</span>
                        </button>
                    </div>

                    <!-- Available 잔고 -->
                    <div class="bybit-available-row">
                        <span class="available-label">Available</span>
                        <span class="available-value" id="mobileAvailable">
                            <span class="available-amt">0.0000 USDT</span>
                            <button class="bybit-add-btn" type="button" aria-label="Add balance">+</button>
                        </span>
                    </div>

                    <!-- 주문 타입 드롭다운 -->
                    <div class="bybit-order-type">
                        <button class="bybit-order-type-btn" id="orderTypeBtn">
                            Limit <span class="dropdown-arrow">▼</span>
                        </button>
                    </div>

                    <!-- Price 입력 -->
                    <div class="bybit-input-group">
                        <label class="bybit-input-label">Price</label>
                        <div class="bybit-input-wrapper">
                            <input type="text" id="mobilePriceInput" value="91234.00" class="bybit-input">
                            <span class="bybit-input-suffix">USDT</span>
                        </div>
                    </div>

                    <!-- Order Value 입력 -->
                    <div class="bybit-input-group">
                        <label class="bybit-input-label">Order Value</label>
                        <div class="bybit-input-wrapper">
                            <input type="text" id="mobileValueInput" value="0.0000" class="bybit-input">
                            <span class="bybit-input-suffix swap-btn">USDT ⇄</span>
                        </div>
                    </div>

                    <!-- 슬라이더 -->
                    <div class="bybit-slider-container">
                        <div class="bybit-slider-track">
                            <div class="bybit-slider-fill" id="mobileSliderFill" style="width: 0%"></div>
                            <div class="bybit-slider-thumb" id="mobileSliderThumb" style="left: 0%"></div>
                            <div class="bybit-slider-dots">
                                <span class="slider-dot" data-pct="0"></span>
                                <span class="slider-dot" data-pct="25"></span>
                                <span class="slider-dot" data-pct="50"></span>
                                <span class="slider-dot" data-pct="75"></span>
                                <span class="slider-dot" data-pct="100"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 주문 정보 (Qty, Cost, Liq. Price) -->
                    <div class="bybit-order-info">
                        <div class="bybit-info-row">
                            <span class="info-label">Qty</span>
                            <span class="info-value"><span id="mobileQtyDisplay">0/0</span> <?= htmlspecialchars($baseCoin) ?></span>
                        </div>
                        <div class="bybit-info-row">
                            <span class="info-label">Cost</span>
                            <span class="info-value"><span id="mobileCostDisplay">0/0</span> USDT</span>
                        </div>
                        <div class="bybit-info-row">
                            <span class="info-label">Liq. Price</span>
                            <span class="info-value link" id="mobileLiqDisplay">Calculate</span>
                        </div>
                    </div>

                    <!-- 옵션 영역 - (스샷 기준) 3줄: TP/SL / Post-Only + GTC / Reduce-Only -->
                    <div class="bybit-opts-wrap">
                        <div class="bybit-opts-row">
                            <label class="bybit-chk">
                                <input type="checkbox" id="mobileTPSL">
                                <span>TP/SL</span>
                            </label>
                        </div>
                        <div class="bybit-opts-row">
                            <label class="bybit-chk">
                                <input type="checkbox" id="mobilePostOnly">
                                <span>Post-Only</span>
                            </label>
                            <div class="bybit-gtc">GTC <span>▼</span></div>
                        </div>
                        <div class="bybit-opts-row">
                            <label class="bybit-chk">
                                <input type="checkbox" id="mobileReduceOnly">
                                <span>Reduce-Only</span>
                            </label>
                        </div>
                    </div>

                    <!-- Long/Short 버튼 -->
                    <div class="bybit-trade-buttons">
                        <button class="bybit-btn-long" id="mobileBtnLong">Long</button>
                        <button class="bybit-btn-short" id="mobileBtnShort">Short</button>
                    </div>
                </div>

                <!-- 우측: 오더북 -->
                <div class="bybit-orderbook">
                    <!-- 펀딩 정보 (바이빗 스타일 2줄) -->
                    <div class="bybit-funding-row">
                        <span class="funding-label">Funding Rate(8hour(s)) / Countdown</span>
                        <div class="funding-values">
                            <span class="funding-value" id="mobileFundingRate">-0.0062%</span>
                            <span class="funding-time" id="mobileFundingTime">01:37:06</span>
                        </div>
                    </div>

                    <!-- 오더북 헤더 -->
                    <div class="bybit-ob-header">
                        <span>Price<br>(USDT)</span>
                        <span>Quantity<br>(<?= htmlspecialchars($baseCoin) ?>)</span>
                    </div>

                    <!-- 매도 호가 (빨간색) -->
                    <div class="bybit-asks" id="mobileAsks">
                        <!-- JS로 채워짐 -->
                    </div>

                    <!-- 현재가 -->
                    <div class="bybit-current-price">
                        <span class="current-price-value up" id="mobileCurrentPrice">91,234.56</span>
                        <span class="current-price-arrow">→</span>
                    </div>
                    <div class="bybit-mark-price" id="mobileMarkPrice">91,234.56</div>

                    <!-- 매수 호가 (녹색) -->
                    <div class="bybit-bids" id="mobileBids">
                        <!-- JS로 채워짐 -->
                    </div>

                    <!-- 하단 컨트롤 - 바이빗 스타일 (2줄) -->
                    <div class="bybit-ob-footer">
                        <!-- 1줄: B/S 비율 바 (바이빗 정확히) -->
                        <div class="ob-footer-row1">
                            <div class="ob-ratio-bar">
                                <div class="ob-ratio-buy-side">
                                    <span class="ob-b-box">B</span>
                                    <span class="ob-pct">48%</span>
                                </div>
                                <div class="ob-ratio-sell-side">
                                    <span class="ob-pct">52%</span>
                                    <span class="ob-s-box">S</span>
                                </div>
                            </div>
                        </div>
                        <!-- 2줄: 드롭다운 + 아이콘 -->
                        <div class="ob-footer-row2">
                            <div class="ob-decimal-box">
                                <span>1</span>
                                <span class="ob-decimal-arrow">▼</span>
                            </div>
                            <div class="ob-mode-box">
                                <span class="ob-icon-list">≡</span>
                                <span class="ob-icon-bars">
                                    <span class="bar-red"></span>
                                    <span class="bar-green"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================
                 데스크탑 전용 거래 영역 - trade.php 스타일
                 ======================================== -->
            <div class="desktop-trade-form">
                <div class="order-area">
                    <!-- 마진/레버리지 -->
                    <div class="margin-leverage-row">
                        <button class="margin-select-btn" id="pcMarginBtn">
                            Cross <span class="arrow">▼</span>
                        </button>
                        <button class="leverage-select-btn" id="pcLeverageBtn">
                            40.00x <span class="arrow">▼</span>
                        </button>
                    </div>

                    <!-- 주문 타입 선택 -->
                    <div class="order-type-tabs">
                        <button class="order-type-tab active">Limit</button>
                        <button class="order-type-tab">Market</button>
                        <button class="order-type-tab has-dropdown">Conditional <span class="arrow">▼</span></button>
                    </div>

                    <!-- 가격 입력 -->
                    <div class="input-group">
                        <label>Price</label>
                        <div class="input-wrapper">
                            <input type="text" value="" id="priceInput" placeholder="Price">
                            <span class="input-suffix">
                                <span class="last-btn" id="pcLastBtn">Last</span>
                                <span class="swap-btn">⇄</span>
                            </span>
                        </div>
                    </div>

                    <!-- 수량 입력 -->
                    <div class="input-group">
                        <label>Quantity</label>
                        <div class="input-wrapper">
                            <input type="text" value="0.001" id="quantityInput" placeholder="0.001">
                            <span class="input-suffix">
                                <span class="qty-unit" id="qtyUnitLabel"><?= htmlspecialchars($baseCoin) ?></span>
                            </span>
                        </div>
                    </div>

                    <!-- USDT 금액 입력 -->
                    <div class="input-group">
                        <label>Amount (USDT)</label>
                        <div class="input-wrapper">
                            <input type="text" value="" id="amountInput" placeholder="0.00">
                            <span class="input-suffix">
                                <span class="qty-unit">USDT</span>
                            </span>
                        </div>
                    </div>

                    <!-- 슬라이더 -->
                    <div class="slider-container">
                        <div class="slider-track">
                            <div class="slider-fill" style="width: 0%"></div>
                            <div class="slider-thumb" style="left: 0%"></div>
                        </div>
                        <div class="slider-labels">
                            <span class="slider-label" data-percent="0">0%</span>
                            <span class="slider-label" data-percent="25">25%</span>
                            <span class="slider-label" data-percent="50">50%</span>
                            <span class="slider-label" data-percent="75">75%</span>
                            <span class="slider-label" data-percent="100">100%</span>
                        </div>
                    </div>

                    <!-- 주문 정보 -->
                    <div class="order-info">
                        <div class="info-row">
                            <span>Available</span>
                            <span id="orderAvailable">0.00 USDT</span>
                        </div>
                        <div class="info-row">
                            <span>Max Size</span>
                            <span id="orderMaxSize">-- <?= htmlspecialchars($baseCoin) ?></span>
                        </div>
                        <div class="info-row">
                            <span>Cost</span>
                            <span id="orderCost">-- USDT</span>
                        </div>
                        <div class="info-row">
                            <span>Liq. Price</span>
                            <span class="link" id="orderLiqPrice">Calculate</span>
                        </div>
                    </div>

                    <!-- 옵션 -->
                    <div class="order-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="pcTpslCheck"> TP/SL
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="pcReduceOnly"> Reduce-Only
                        </label>
                    </div>

                    <!-- 롱/숏 버튼 -->
                    <div class="action-buttons">
                        <button class="action-btn long" id="btnLong">Long</button>
                        <button class="action-btn short" id="btnShort">Short</button>
                    </div>

                    <!-- 추가 정보 -->
                    <div class="extra-info">
                        <span>◎ Fee Rate</span>
                        <span>📊 Calculator</span>
                    </div>
                </div>
            </div>

            <!-- 계좌 정보 섹션 -->
            <div class="account-section">
                <div class="account-header">
                    <span class="account-title">Unified Trading Account</span>
                    <div class="account-icons">
                        <span class="acc-icon">⚙</span>
                        <span class="acc-icon">📊</span>
                        <span class="acc-icon">P&L</span>
                    </div>
                </div>
                <div class="account-row">
                    <span class="label">Margin Mode</span>
                    <span class="value link">Cross Margin ></span>
                </div>

                <!-- 마진 바 -->
                <div class="margin-bars">
                    <div class="margin-bar-row">
                        <span class="label">Initial Margin</span>
                        <div class="bar-container">
                            <div class="bar-fill green" id="initialMarginBar" style="width: 12%"></div>
                        </div>
                        <span class="value" id="initialMarginPercent">12.45%</span>
                    </div>
                    <div class="margin-bar-row">
                        <span class="label">Maint. Margin</span>
                        <div class="bar-container">
                            <div class="bar-fill yellow" id="maintenanceMarginBar" style="width: 3%"></div>
                        </div>
                        <span class="value" id="maintenanceMarginPercent">3.21%</span>
                    </div>
                </div>

                <!-- 잔액 정보 -->
                <div class="balance-rows">
                    <div class="balance-row">
                        <span class="label">Margin Balance</span>
                        <span class="value" id="marginBalance">0.00 USDT</span>
                    </div>
                    <div class="balance-row">
                        <span class="label">Available Balance</span>
                        <span class="value" id="availableBalanceAcc">0.00 USDT</span>
                    </div>
                    <div class="balance-row">
                        <span class="label">Unrealized PNL</span>
                        <span class="value positive" id="unrealizedPnl">+0.00 USDT</span>
                    </div>
                </div>

                <!-- 버튼들 -->
                <div class="account-buttons">
                    <button class="acc-btn">Deposit</button>
                    <button class="acc-btn">Convert</button>
                    <button class="acc-btn">Transfer</button>
                </div>
            </div>

            <!-- 계약 상세 -->
            <div class="contract-section">
                <div class="contract-header">
                    <span class="contract-title">Contract Details <?= htmlspecialchars($urlSymbol) ?></span>
                </div>
                <div class="contract-rows">
                    <div class="contract-row">
                        <span class="label">Expiration Date</span>
                        <span class="value">Perpetual</span>
                    </div>
                    <div class="contract-row">
                        <span class="label">Index Price</span>
                        <span class="value" id="indexPrice">91,234.56</span>
                    </div>
                    <div class="contract-row">
                        <span class="label">Mark Price</span>
                        <span class="value" id="markPriceContract">91,245.12</span>
                    </div>
                    <div class="contract-row">
                        <span class="label">Open Interest</span>
                        <span class="value" id="openInterest">125,432 BTC</span>
                    </div>
                    <div class="contract-row">
                        <span class="label">24h Turnover</span>
                        <span class="value" id="turnover24h">2.84B USDT</span>
                    </div>
                </div>
                <button class="show-more-btn">Show More ▼</button>
            </div>

            <!-- 실시간 거래 피드 -->
            <div class="trade-feed-section">
                <div class="trade-feed-header">
                    <span class="trade-feed-title">Live Trades</span>
                    <span class="online-badge">● <span id="onlineUsers">247</span> Online</span>
                </div>
                <div class="trade-feed-list" id="tradeFeedList">
                    <!-- 가짜 거래 데이터가 JS로 채워짐 -->
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// ========================================
// Trade2 JavaScript - MOCK DATA MODE
// ========================================

// 심볼 설정 (PHP에서 전달)
const symbolConfigs = <?php
    $jsConfigs = [];
    foreach ($symbolsData as $sym => $data) {
        $jsConfigs[$sym] = [
            'icon' => $data['icon'] ?? substr($data['base_asset'], 0, 1),
            'color' => $data['color'] ?? '#ffffff',
            'name' => $data['display_name'] ?? $data['base_asset'],
            'logo' => $data['logo_url'] ?? 'https://cryptologos.cc/logos/bitcoin-btc-logo.png'
        ];
    }
    echo json_encode($jsConfigs, JSON_UNESCAPED_UNICODE);
?>;

// ========================================
// 쿠키 유틸리티 (전역)
// ========================================
function setCookie(name, value, days = 365) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/';
}

function getCookie(name) {
    const value = document.cookie.split('; ').find(row => row.startsWith(name + '='));
    return value ? decodeURIComponent(value.split('=')[1]) : null;
}

// 헤더 별표 동기화 (전역)
function syncHeaderStar() {
    const headerStar = document.getElementById('symbolFavorite');
    const currentSymbolName = document.getElementById('currentSymbolName')?.textContent;
    if (!headerStar || !currentSymbolName) return;

    const saved = getCookie('futurx_favorites');
    let favorites = [];
    if (saved) {
        try { favorites = JSON.parse(saved); } catch(e) {}
    }

    if (favorites.includes(currentSymbolName)) {
        headerStar.classList.add('active');
        headerStar.textContent = '★';
    } else {
        headerStar.classList.remove('active');
        headerStar.textContent = '☆';
    }
}

// 토스트 알림 (전역)
function showToast(message, type = 'info') {
    // 기존 토스트 제거
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

(function() {
    'use strict';

    const symbol = window.initialSymbol || 'BTCUSDT';
    let currentSymbol = symbol;
    let chart = null;
    let candleSeries = null;
    let currentPrice = 91234.56;

    // ========================================
    // MOCK DATA
    // ========================================
    const MOCK_PRICE = 91234.56;
    const MOCK_TICKER = {
        priceChangePercent: 2.34,
        highPrice: 92150.00,
        lowPrice: 89800.50,
        quoteVolume: 2847563210,
        markPrice: 91245.12
    };

    // 가짜 오더북 생성
    function generateMockOrderbook() {
        const basePrice = MOCK_PRICE;
        const asks = [];
        const bids = [];

        for (let i = 0; i < 12; i++) {
            asks.push({
                price: basePrice + (i + 1) * 5.5 + Math.random() * 2,
                quantity: 0.5 + Math.random() * 3
            });
            bids.push({
                price: basePrice - (i + 1) * 5.5 - Math.random() * 2,
                quantity: 0.5 + Math.random() * 3
            });
        }

        return { asks, bids };
    }

    // 가짜 캔들 데이터 생성 (최근 100개)
    function generateMockKlines() {
        const klines = [];
        const now = Date.now();
        let price = MOCK_PRICE - 500; // 시작가격

        for (let i = 99; i >= 0; i--) {
            const time = now - i * 5 * 60 * 1000; // 5분봉
            const open = price;
            const close = open + (Math.random() - 0.45) * 100;
            const high = Math.max(open, close) + Math.random() * 50;
            const low = Math.min(open, close) - Math.random() * 50;

            klines.push([time, open, high, low, close]);
            price = close;
        }

        return klines;
    }

    // 가짜 포지션 데이터
    const MOCK_POSITIONS = [
        { symbol: 'BTCUSDT', side: 'LONG', size: 0.125, entryPrice: 90150.00, markPrice: MOCK_PRICE, pnl: 135.57, pnlPercent: 1.20, leverage: 20, margin: 564.69, liqPrice: 85420.50 },
        { symbol: 'ETHUSDT', side: 'SHORT', size: 2.5, entryPrice: 3245.80, markPrice: 3198.45, pnl: 118.38, pnlPercent: 1.89, leverage: 10, margin: 811.45, liqPrice: 3520.60 },
        { symbol: 'SOLUSDT', side: 'LONG', size: 15.5, entryPrice: 185.20, markPrice: 192.45, pnl: 112.38, pnlPercent: 3.91, leverage: 25, margin: 114.88, liqPrice: 165.30 },
        { symbol: 'XRPUSDT', side: 'SHORT', size: 5000, entryPrice: 2.45, markPrice: 2.52, pnl: -350.00, pnlPercent: -2.86, leverage: 50, margin: 245.00, liqPrice: 2.72 },
        { symbol: 'BNBUSDT', side: 'LONG', size: 5.2, entryPrice: 580.50, markPrice: 595.20, pnl: 76.44, pnlPercent: 2.53, leverage: 15, margin: 201.51, liqPrice: 520.40 },
        { symbol: 'DOGEUSDT', side: 'LONG', size: 50000, entryPrice: 0.082, markPrice: 0.0875, pnl: 275.00, pnlPercent: 6.71, leverage: 20, margin: 205.00, liqPrice: 0.065 },
        { symbol: 'ADAUSDT', side: 'SHORT', size: 8000, entryPrice: 0.45, markPrice: 0.42, pnl: 240.00, pnlPercent: 6.67, leverage: 25, margin: 144.00, liqPrice: 0.52 },
        { symbol: 'AVAXUSDT', side: 'LONG', size: 25, entryPrice: 35.50, markPrice: 38.20, pnl: 67.50, pnlPercent: 7.61, leverage: 10, margin: 88.75, liqPrice: 28.40 },
        { symbol: 'MATICUSDT', side: 'SHORT', size: 15000, entryPrice: 0.85, markPrice: 0.78, pnl: 1050.00, pnlPercent: 8.24, leverage: 30, margin: 425.00, liqPrice: 0.98 },
        { symbol: 'LINKUSDT', side: 'LONG', size: 150, entryPrice: 14.20, markPrice: 15.85, pnl: 247.50, pnlPercent: 11.62, leverage: 15, margin: 142.00, liqPrice: 11.50 }
    ];

    // 가짜 주문 데이터
    const MOCK_ORDERS = [
        { symbol: 'BTCUSDT', type: 'LIMIT', side: 'BUY', price: 89500.00, quantity: 0.05, filled: 0, status: 'NEW', time: Date.now() - 3600000 },
        { symbol: 'BTCUSDT', type: 'STOP_MARKET', side: 'SELL', stopPrice: 88000.00, quantity: 0.125, filled: 0, status: 'NEW', time: Date.now() - 7200000 },
        { symbol: 'ETHUSDT', type: 'LIMIT', side: 'BUY', price: 3150.00, quantity: 1.5, filled: 0, status: 'NEW', time: Date.now() - 1800000 },
        { symbol: 'SOLUSDT', type: 'LIMIT', side: 'SELL', price: 200.00, quantity: 10, filled: 0, status: 'NEW', time: Date.now() - 5400000 },
        { symbol: 'XRPUSDT', type: 'STOP_LIMIT', side: 'BUY', price: 2.30, stopPrice: 2.25, quantity: 3000, filled: 0, status: 'NEW', time: Date.now() - 9000000 },
        { symbol: 'BNBUSDT', type: 'LIMIT', side: 'BUY', price: 560.00, quantity: 2.0, filled: 0, status: 'NEW', time: Date.now() - 10800000 },
        { symbol: 'DOGEUSDT', type: 'LIMIT', side: 'SELL', price: 0.095, quantity: 25000, filled: 0, status: 'NEW', time: Date.now() - 14400000 },
        { symbol: 'ADAUSDT', type: 'STOP_MARKET', side: 'BUY', stopPrice: 0.50, quantity: 5000, filled: 0, status: 'NEW', time: Date.now() - 18000000 },
        { symbol: 'AVAXUSDT', type: 'LIMIT', side: 'BUY', price: 32.00, quantity: 15, filled: 0, status: 'NEW', time: Date.now() - 21600000 },
        { symbol: 'LINKUSDT', type: 'LIMIT', side: 'SELL', price: 18.00, quantity: 100, filled: 0, status: 'NEW', time: Date.now() - 25200000 }
    ];

    // 가짜 거래 내역
    const MOCK_TRADES = [
        { symbol: 'BTCUSDT', side: 'BUY', price: 90150.00, quantity: 0.125, fee: 2.26, time: Date.now() - 86400000, pnl: null },
        { symbol: 'ETHUSDT', side: 'SELL', price: 3245.80, quantity: 2.5, fee: 1.62, time: Date.now() - 172800000, pnl: null },
        { symbol: 'BTCUSDT', side: 'SELL', price: 89800.00, quantity: 0.08, fee: 1.44, time: Date.now() - 259200000, pnl: 45.20 },
        { symbol: 'SOLUSDT', side: 'BUY', price: 185.20, quantity: 15.5, fee: 0.57, time: Date.now() - 345600000, pnl: null },
        { symbol: 'XRPUSDT', side: 'SELL', price: 2.45, quantity: 5000, fee: 2.45, time: Date.now() - 432000000, pnl: -120.50 },
        { symbol: 'BNBUSDT', side: 'BUY', price: 580.50, quantity: 5.2, fee: 0.60, time: Date.now() - 518400000, pnl: null },
        { symbol: 'DOGEUSDT', side: 'BUY', price: 0.082, quantity: 50000, fee: 0.82, time: Date.now() - 604800000, pnl: null },
        { symbol: 'ADAUSDT', side: 'SELL', price: 0.45, quantity: 8000, fee: 0.72, time: Date.now() - 691200000, pnl: 85.00 },
        { symbol: 'AVAXUSDT', side: 'BUY', price: 35.50, quantity: 25, fee: 0.18, time: Date.now() - 777600000, pnl: null },
        { symbol: 'LINKUSDT', side: 'BUY', price: 14.20, quantity: 150, fee: 0.43, time: Date.now() - 864000000, pnl: null }
    ];

    // 가짜 계정 정보
    const MOCK_ACCOUNT = {
        marginBalance: 12584.32,
        availableBalance: 10245.67,
        unrealizedPnl: 253.95
    };

    // ========================================
    // Mock 데이터 로드 (Socket 대신)
    // ========================================
    function loadMockData() {
        console.log('[Trade2] Loading mock data...');

        // 가격 업데이트
        updatePrice(MOCK_PRICE);

        // 티커 업데이트
        updateTicker(MOCK_TICKER);

        // 오더북 업데이트
        updateOrderbook(generateMockOrderbook());

        // 차트 초기화
        initChart(generateMockKlines());

        // 포지션 렌더링
        renderPositions(MOCK_POSITIONS);

        // 주문 렌더링
        renderOrders(MOCK_ORDERS);

        // 거래 내역 렌더링
        renderTrades(MOCK_TRADES);

        // Order History 렌더링
        renderOrderHistory();

        // Assets 렌더링
        renderAssets();

        // P&L 렌더링
        renderPnL();

        // 계정 정보 렌더링
        renderAccount(MOCK_ACCOUNT);

        // 펀딩 비율
        document.getElementById('fundingRate').textContent = '+0.0100%';

        // 거래 피드 초기화
        renderTradeFeed();

        // 실시간 업데이트 시뮬레이션
        startMockUpdates();
    }

    // Order History 렌더링
    function renderOrderHistory() {
        const panel = document.getElementById('historyPanel');
        const orders = [
            { symbol: 'BTCUSDT', type: 'LIMIT', side: 'BUY', price: 88500.00, quantity: 0.1, filled: 0.1, status: 'FILLED', time: Date.now() - 86400000 },
            { symbol: 'ETHUSDT', type: 'MARKET', side: 'SELL', price: 3200.00, quantity: 2.0, filled: 2.0, status: 'FILLED', time: Date.now() - 172800000 },
            { symbol: 'BTCUSDT', type: 'LIMIT', side: 'SELL', price: 92000.00, quantity: 0.05, filled: 0, status: 'CANCELED', time: Date.now() - 259200000 },
            { symbol: 'SOLUSDT', type: 'LIMIT', side: 'BUY', price: 180.00, quantity: 20, filled: 20, status: 'FILLED', time: Date.now() - 345600000 },
            { symbol: 'XRPUSDT', type: 'STOP_MARKET', side: 'SELL', price: 2.20, quantity: 2000, filled: 2000, status: 'FILLED', time: Date.now() - 432000000 },
            { symbol: 'BNBUSDT', type: 'LIMIT', side: 'BUY', price: 550.00, quantity: 3.0, filled: 0, status: 'CANCELED', time: Date.now() - 518400000 },
            { symbol: 'DOGEUSDT', type: 'MARKET', side: 'BUY', price: 0.078, quantity: 30000, filled: 30000, status: 'FILLED', time: Date.now() - 604800000 },
            { symbol: 'ADAUSDT', type: 'LIMIT', side: 'SELL', price: 0.48, quantity: 5000, filled: 5000, status: 'FILLED', time: Date.now() - 691200000 },
            { symbol: 'AVAXUSDT', type: 'LIMIT', side: 'BUY', price: 30.00, quantity: 10, filled: 0, status: 'EXPIRED', time: Date.now() - 777600000 },
            { symbol: 'LINKUSDT', type: 'MARKET', side: 'BUY', price: 13.50, quantity: 80, filled: 80, status: 'FILLED', time: Date.now() - 864000000 }
        ];

        let html = `
            <div class="orders-table">
                <div class="orders-header">
                    <span>Time</span>
                    <span>Symbol</span>
                    <span>Type</span>
                    <span>Side</span>
                    <span>Price</span>
                    <span>Quantity</span>
                    <span>Filled</span>
                    <span>Status</span>
                </div>
        `;

        orders.forEach(order => {
            const sideClass = order.side === 'BUY' ? 'buy' : 'sell';
            const statusClass = order.status === 'FILLED' ? 'positive' : order.status === 'CANCELED' ? 'negative' : '';
            html += `
                <div class="orders-row">
                    <span>${new Date(order.time).toLocaleDateString()}</span>
                    <span>${order.symbol}</span>
                    <span>${order.type}</span>
                    <span class="${sideClass}">${order.side}</span>
                    <span>${order.price.toLocaleString()}</span>
                    <span>${order.quantity}</span>
                    <span>${order.filled}</span>
                    <span class="${statusClass}">${order.status}</span>
                </div>
            `;
        });

        html += '</div>';
        panel.innerHTML = html;
    }

    // Assets 렌더링 - 고퀄리티 바이빗 스타일
    function renderAssets() {
        const panel = document.getElementById('assetsPanel');
        const assets = [
            { coin: 'USDT', name: 'Tether', available: 10245.67, inOrder: 2338.65, total: 12584.32, usdValue: 12584.32 },
            { coin: 'BTC', name: 'Bitcoin', available: 0.125, inOrder: 0, total: 0.125, usdValue: 11404.32 },
            { coin: 'ETH', name: 'Ethereum', available: 2.5, inOrder: 0, total: 2.5, usdValue: 8250.00 },
            { coin: 'SOL', name: 'Solana', available: 15.5, inOrder: 0, total: 15.5, usdValue: 3100.00 },
            { coin: 'BNB', name: 'BNB', available: 5.2, inOrder: 2.0, total: 7.2, usdValue: 4032.00 },
            { coin: 'XRP', name: 'Ripple', available: 5000, inOrder: 3000, total: 8000, usdValue: 17200.00 },
            { coin: 'DOGE', name: 'Dogecoin', available: 50000, inOrder: 25000, total: 75000, usdValue: 6375.00 },
            { coin: 'ADA', name: 'Cardano', available: 8000, inOrder: 5000, total: 13000, usdValue: 6760.00 },
            { coin: 'AVAX', name: 'Avalanche', available: 25, inOrder: 15, total: 40, usdValue: 1440.00 },
            { coin: 'LINK', name: 'Chainlink', available: 150, inOrder: 100, total: 250, usdValue: 4500.00 }
        ];

        // 코인 이미지 URL (CoinGecko CDN 사용)
        const getCoinIcon = (coin) => {
            const icons = {
                'USDT': 'https://assets.coingecko.com/coins/images/325/small/Tether.png',
                'BTC': 'https://assets.coingecko.com/coins/images/1/small/bitcoin.png',
                'ETH': 'https://assets.coingecko.com/coins/images/279/small/ethereum.png',
                'SOL': 'https://assets.coingecko.com/coins/images/4128/small/solana.png',
                'BNB': 'https://assets.coingecko.com/coins/images/825/small/bnb-icon2_2x.png',
                'XRP': 'https://assets.coingecko.com/coins/images/44/small/xrp-symbol-white-128.png',
                'DOGE': 'https://assets.coingecko.com/coins/images/5/small/dogecoin.png',
                'ADA': 'https://assets.coingecko.com/coins/images/975/small/cardano.png',
                'AVAX': 'https://assets.coingecko.com/coins/images/12559/small/Avalanche_Circle_RedWhite_Trans.png',
                'LINK': 'https://assets.coingecko.com/coins/images/877/small/chainlink-new-logo.png'
            };
            return icons[coin] || 'https://assets.coingecko.com/coins/images/1/small/bitcoin.png';
        };

        let html = `
            <div class="assets-table">
                <div class="assets-header">
                    <span class="col-coin">Coin</span>
                    <span class="col-available">Available</span>
                    <span class="col-inorder">In Order</span>
                    <span class="col-total">Total</span>
                </div>
                <div class="assets-body">
        `;

        assets.forEach(asset => {
            const formattedAvailable = asset.coin === 'USDT' ? asset.available.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : asset.available.toLocaleString();
            const formattedInOrder = asset.coin === 'USDT' ? asset.inOrder.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : asset.inOrder.toLocaleString();
            const formattedTotal = asset.coin === 'USDT' ? asset.total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : asset.total.toLocaleString();

            html += `
                <div class="assets-row">
                    <div class="col-coin">
                        <img src="${getCoinIcon(asset.coin)}" alt="${asset.coin}" class="coin-icon" onerror="this.style.display='none'">
                        <div class="coin-info">
                            <span class="coin-symbol">${asset.coin}</span>
                            <span class="coin-name">${asset.name}</span>
                        </div>
                    </div>
                    <div class="col-available">
                        <span class="amount">${formattedAvailable}</span>
                    </div>
                    <div class="col-inorder">
                        <span class="amount ${asset.inOrder > 0 ? 'has-order' : ''}">${formattedInOrder}</span>
                    </div>
                    <div class="col-total">
                        <span class="amount">${formattedTotal}</span>
                        <span class="usd-value">≈ $${asset.usdValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
        panel.innerHTML = html;
    }

    // P&L 렌더링 (trade.php 스타일)
    function renderPnL() {
        const tbody = document.getElementById('pnlHistoryBody');
        const stats = document.getElementById('pnlStats');
        if (!tbody) return;

        const KRW_RATE = 1450; // USD to KRW 환율

        const pnlData = [
            { market: 'Derivatives', symbol: 'BTCUSDT', side: 'Long', entryPrice: 89500.00, exitPrice: 91234.56, qty: 0.05, pnl: 135.57, openFee: -4.48, closeFee: -4.56, fundingFee: -2.15 },
            { market: 'Derivatives', symbol: 'ETHUSDT', side: 'Short', entryPrice: 3280.00, exitPrice: 3150.50, qty: 1.5, pnl: 118.38, openFee: -2.46, closeFee: -2.36, fundingFee: -1.05 },
            { market: 'Derivatives', symbol: 'SOLUSDT', side: 'Long', entryPrice: 195.00, exitPrice: 190.48, qty: 10, pnl: -45.20, openFee: -0.98, closeFee: -0.95, fundingFee: -0.42 },
            { market: 'Derivatives', symbol: 'XRPUSDT', side: 'Short', entryPrice: 2.15, exitPrice: 2.27, qty: 3000, pnl: -350.00, openFee: -3.23, closeFee: -3.41, fundingFee: -1.52 },
            { market: 'Derivatives', symbol: 'BNBUSDT', side: 'Long', entryPrice: 545.00, exitPrice: 560.22, qty: 2, pnl: 76.44, openFee: -0.55, closeFee: -0.56, fundingFee: -0.25 },
            { market: 'Derivatives', symbol: 'DOGEUSDT', side: 'Long', entryPrice: 0.0850, exitPrice: 0.0961, qty: 25000, pnl: 275.00, openFee: -1.06, closeFee: -1.20, fundingFee: -0.53 },
            { market: 'Derivatives', symbol: 'ADAUSDT', side: 'Short', entryPrice: 0.52, exitPrice: 0.44, qty: 5000, pnl: 240.00, openFee: -1.30, closeFee: -1.10, fundingFee: -0.49 },
            { market: 'Derivatives', symbol: 'AVAXUSDT', side: 'Long', entryPrice: 28.50, exitPrice: 32.00, qty: 15, pnl: 67.50, openFee: -0.21, closeFee: -0.24, fundingFee: -0.11 },
            { market: 'Derivatives', symbol: 'MATICUSDT', side: 'Short', entryPrice: 1.25, exitPrice: 0.98, qty: 4000, pnl: 1050.00, openFee: -2.50, closeFee: -1.96, fundingFee: -0.87 },
            { market: 'Derivatives', symbol: 'LINKUSDT', side: 'Long', entryPrice: 14.50, exitPrice: 18.15, qty: 100, pnl: 247.50, openFee: -0.73, closeFee: -0.91, fundingFee: -0.40 }
        ];

        let html = '';
        pnlData.forEach(item => {
            const sideClass = item.side === 'Long' ? 'side-long' : 'side-short';
            const pnlClass = item.pnl >= 0 ? 'pnl-positive' : 'pnl-negative';
            const qtyUsdt = item.qty * item.exitPrice; // USDT 가치
            const pnlKrw = item.pnl * KRW_RATE; // 원화 가치

            html += `
                <tr>
                    <td>${item.market}</td>
                    <td><div class="symbol-cell"><span class="symbol-name">${item.symbol}</span><span class="symbol-type">Perpetual</span></div></td>
                    <td class="${sideClass}">${item.side}</td>
                    <td>${item.entryPrice.toLocaleString()}</td>
                    <td>${item.exitPrice.toLocaleString()}</td>
                    <td class="qty-cell">
                        <div class="qty-value">${item.qty.toLocaleString()}</div>
                        <div class="qty-usdt">${formatNumber(qtyUsdt, 0)} USDT</div>
                    </td>
                    <td class="pnl-cell ${pnlClass}">
                        <div class="pnl-value">${item.pnl >= 0 ? '+' : ''}${item.pnl.toFixed(2)}</div>
                        <div class="pnl-krw">₩${formatNumber(Math.abs(pnlKrw), 0)} 원</div>
                    </td>
                    <td>${item.openFee.toFixed(2)}</td>
                    <td>${item.closeFee.toFixed(2)}</td>
                    <td>${item.fundingFee.toFixed(2)}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        if (stats) stats.textContent = `Total: ${pnlData.length} records`;

        // P&L 탭 버튼 이벤트
        document.querySelectorAll('.pnl-main-tab').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.pnl-main-tab').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.pnl-period-tab').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.pnl-period-tab').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    // 거래 피드 렌더링
    function renderTradeFeed() {
        const feedList = document.getElementById('tradeFeedList');
        const users = ['Trader***21', 'Whale_88', 'BTC_Fan', 'CryptoKing', 'Moon***er', 'Diamond', 'Hodler99', 'Anon***45'];
        const symbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT'];

        let feedHtml = '';
        for (let i = 0; i < 10; i++) {
            const user = users[Math.floor(Math.random() * users.length)];
            const isLong = Math.random() > 0.5;
            const symbol = symbols[Math.floor(Math.random() * symbols.length)];
            const amount = (Math.random() * 50000 + 1000).toFixed(0);
            const mins = Math.floor(Math.random() * 30);

            feedHtml += `
                <div class="trade-feed-item">
                    <span class="user">${user}</span>
                    <span class="action ${isLong ? 'long' : 'short'}">${isLong ? 'Long' : 'Short'}</span>
                    <span class="symbol">${symbol}</span>
                    <span class="amount">$${parseInt(amount).toLocaleString()}</span>
                    <span class="time">${mins}m ago</span>
                </div>
            `;
        }
        feedList.innerHTML = feedHtml;
    }

    // 실시간 업데이트 시뮬레이션
    function startMockUpdates() {
        // 가격 변동 시뮬레이션
        setInterval(() => {
            currentPrice += (Math.random() - 0.5) * 20;
            updatePrice(currentPrice);

            // 오더북도 업데이트
            updateOrderbook(generateMockOrderbook());
        }, 1500);

        // 캔들 업데이트 시뮬레이션
        setInterval(() => {
            if (candleSeries) {
                const now = Math.floor(Date.now() / 1000);
                const bar = {
                    time: now - (now % 300), // 5분 단위로 맞춤
                    open: currentPrice - Math.random() * 20,
                    high: currentPrice + Math.random() * 30,
                    low: currentPrice - Math.random() * 30,
                    close: currentPrice
                };
                candleSeries.update(bar);
            }
        }, 5000);

        // 거래 피드 업데이트
        setInterval(() => {
            addNewTradeFeedItem();
        }, 3000);
    }

    // 새 거래 피드 아이템 추가
    function addNewTradeFeedItem() {
        const feedList = document.getElementById('tradeFeedList');
        const users = ['Trader***21', 'Whale_88', 'BTC_Fan', 'CryptoKing', 'Moon***er', 'Diamond', 'Hodler99', 'Anon***45'];
        const symbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT'];

        const user = users[Math.floor(Math.random() * users.length)];
        const isLong = Math.random() > 0.5;
        const symbol = symbols[Math.floor(Math.random() * symbols.length)];
        const amount = (Math.random() * 50000 + 1000).toFixed(0);

        const newItem = document.createElement('div');
        newItem.className = 'trade-feed-item';
        newItem.innerHTML = `
            <span class="user">${user}</span>
            <span class="action ${isLong ? 'long' : 'short'}">${isLong ? 'Long' : 'Short'}</span>
            <span class="symbol">${symbol}</span>
            <span class="amount">$${parseInt(amount).toLocaleString()}</span>
            <span class="time">now</span>
        `;
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-10px)';

        feedList.insertBefore(newItem, feedList.firstChild);

        // 애니메이션
        setTimeout(() => {
            newItem.style.transition = 'all 0.3s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        }, 10);

        // 오래된 아이템 제거
        if (feedList.children.length > 15) {
            feedList.removeChild(feedList.lastChild);
        }
    }

    // ========================================
    // 가격 업데이트
    // ========================================
    function updatePrice(price) {
        currentPrice = parseFloat(price);
        const mainPriceEl = document.getElementById('mainPrice');
        const priceInput = document.getElementById('priceInput');

        const formatted = formatPrice(currentPrice);
        mainPriceEl.textContent = formatted;

        if (document.activeElement !== priceInput) {
            priceInput.value = formatted;
        }
    }

    // ========================================
    // 티커 업데이트
    // ========================================
    function updateTicker(data) {
        const change = parseFloat(data.priceChangePercent || 0);
        const priceChange = parseFloat(data.priceChange || 0);

        // Change % 업데이트 (새 심볼바 ID: changePercent)
        const changeEl = document.getElementById('changePercent');
        if (changeEl) {
            changeEl.textContent = (priceChange >= 0 ? '+' : '') + formatPrice(priceChange) + ' (' + (change >= 0 ? '+' : '') + change.toFixed(2) + '%)';
            changeEl.className = 'stat-value ' + (change >= 0 ? 'up' : 'down');
        }

        // 24H High/Low
        const high24hEl = document.getElementById('high24h');
        const low24hEl = document.getElementById('low24h');
        if (high24hEl) high24hEl.textContent = formatPrice(data.highPrice);
        if (low24hEl) low24hEl.textContent = formatPrice(data.lowPrice);

        // 24H Turnover (새 심볼바 ID: turnover24h)
        const turnoverEl = document.getElementById('turnover24h');
        if (turnoverEl) turnoverEl.textContent = formatVolume(data.quoteVolume);

        // Mark Price (새 형식)
        const markPriceEl = document.getElementById('markPrice');
        if (markPriceEl && data.markPrice) {
            markPriceEl.textContent = formatPrice(data.markPrice);
        }

        // Index Price
        const indexPriceEl = document.getElementById('indexPrice');
        if (indexPriceEl && data.indexPrice) {
            indexPriceEl.textContent = formatPrice(data.indexPrice);
        }

        // Open Interest
        const openInterestEl = document.getElementById('openInterest');
        if (openInterestEl && data.openInterest) {
            openInterestEl.textContent = formatVolume(data.openInterest);
        }

        // 모바일용 변동률
        const mobileChangeEl = document.getElementById('mobileChangePercent');
        if (mobileChangeEl) {
            mobileChangeEl.textContent = (change >= 0 ? '+' : '') + change.toFixed(2) + '%';
            mobileChangeEl.className = 'mobile-change ' + (change >= 0 ? 'up' : 'down');
        }
    }

    // ========================================
    // 오더북 업데이트
    // ========================================
    function updateOrderbook(data) {
        const asks = (data.asks || []).slice(0, 12).reverse();
        const bids = (data.bids || []).slice(0, 12);

        const maxAskQty = Math.max(...asks.map(a => parseFloat(a.quantity || a[1] || 0)));
        const maxBidQty = Math.max(...bids.map(b => parseFloat(b.quantity || b[1] || 0)));
        const maxQty = Math.max(maxAskQty, maxBidQty);

        let asksHtml = '';
        let runningAskTotal = 0;
        asks.forEach(ask => {
            const price = parseFloat(ask.price || ask[0]);
            const qty = parseFloat(ask.quantity || ask[1]);
            runningAskTotal += qty;
            const pct = (qty / maxQty) * 100;
            asksHtml += `
                <div class="ob-row ask">
                    <span class="price">${formatPrice(price)}</span>
                    <span class="qty">${qty.toFixed(4)}</span>
                    <span class="total">${runningAskTotal.toFixed(4)}</span>
                    <div class="depth-bar" style="width: ${pct}%"></div>
                </div>
            `;
        });

        let bidsHtml = '';
        let runningBidTotal = 0;
        bids.forEach(bid => {
            const price = parseFloat(bid.price || bid[0]);
            const qty = parseFloat(bid.quantity || bid[1]);
            runningBidTotal += qty;
            const pct = (qty / maxQty) * 100;
            bidsHtml += `
                <div class="ob-row bid">
                    <span class="price">${formatPrice(price)}</span>
                    <span class="qty">${qty.toFixed(4)}</span>
                    <span class="total">${runningBidTotal.toFixed(4)}</span>
                    <div class="depth-bar" style="width: ${pct}%"></div>
                </div>
            `;
        });

        document.getElementById('asksList').innerHTML = asksHtml;
        document.getElementById('bidsList').innerHTML = bidsHtml;

        if (currentPrice > 0) {
            const spreadEl = document.getElementById('spreadPrice');
            spreadEl.textContent = formatPrice(currentPrice);
        }

        // ========================================
        // 바이빗 스타일 모바일 오더북 렌더링
        // ========================================
        const mobileAsks = document.getElementById('mobileAsks');
        const mobileBids = document.getElementById('mobileBids');
        const mobileCurrentPrice = document.getElementById('mobileCurrentPrice');
        const mobileMarkPrice = document.getElementById('mobileMarkPrice');

        if (mobileAsks && mobileBids) {
            // 8개씩 표시 (바이빗 스타일)
            const mobileAskData = (data.asks || []).slice(0, 8).reverse();
            const mobileBidData = (data.bids || []).slice(0, 8);

            let mobileAsksHtml = '';
            mobileAskData.forEach(ask => {
                const price = parseFloat(ask.price || ask[0]);
                const qty = parseFloat(ask.quantity || ask[1]);
                const pct = (qty / maxQty) * 100;
                mobileAsksHtml += `
                    <div class="bybit-ob-row ask">
                        <span class="price">${formatPrice(price)}</span>
                        <span class="qty">${qty.toFixed(2)}</span>
                        <div class="depth-bar" style="width: ${pct}%"></div>
                    </div>
                `;
            });

            let mobileBidsHtml = '';
            mobileBidData.forEach(bid => {
                const price = parseFloat(bid.price || bid[0]);
                const qty = parseFloat(bid.quantity || bid[1]);
                const pct = (qty / maxQty) * 100;
                mobileBidsHtml += `
                    <div class="bybit-ob-row bid">
                        <span class="price">${formatPrice(price)}</span>
                        <span class="qty">${qty.toFixed(2)}</span>
                        <div class="depth-bar" style="width: ${pct}%"></div>
                    </div>
                `;
            });

            mobileAsks.innerHTML = mobileAsksHtml;
            mobileBids.innerHTML = mobileBidsHtml;

            // 현재가 업데이트
            if (mobileCurrentPrice && currentPrice > 0) {
                mobileCurrentPrice.textContent = formatPrice(currentPrice);
            }
            if (mobileMarkPrice && currentPrice > 0) {
                mobileMarkPrice.textContent = formatPrice(currentPrice);
            }
        }
    }

    // ========================================
    // 차트 초기화
    // ========================================
    function initChart(klines) {
        const container = document.getElementById('chart');
        if (!container) return;

        chart = LightweightCharts.createChart(container, {
            width: container.clientWidth,
            height: container.clientHeight,
            layout: {
                background: { type: 'solid', color: '#161a1e' },
                textColor: '#848e9c'
            },
            grid: {
                vertLines: { color: '#2b3139' },
                horzLines: { color: '#2b3139' }
            },
            crosshair: {
                mode: LightweightCharts.CrosshairMode.Normal
            },
            timeScale: {
                borderColor: '#2b3139',
                timeVisible: true
            },
            rightPriceScale: {
                borderColor: '#2b3139'
            }
        });

        candleSeries = chart.addCandlestickSeries({
            upColor: '#0ecb81',
            downColor: '#f6465d',
            borderUpColor: '#0ecb81',
            borderDownColor: '#f6465d',
            wickUpColor: '#0ecb81',
            wickDownColor: '#f6465d'
        });

        if (klines && klines.length > 0) {
            const data = klines.map(k => ({
                time: Math.floor(k[0] / 1000),
                open: parseFloat(k[1]),
                high: parseFloat(k[2]),
                low: parseFloat(k[3]),
                close: parseFloat(k[4])
            }));
            candleSeries.setData(data);
        }

        // 리사이즈 핸들러
        const resizeObserver = new ResizeObserver(() => {
            chart.applyOptions({
                width: container.clientWidth,
                height: container.clientHeight
            });
        });
        resizeObserver.observe(container);
    }

    // ========================================
    // 차트 업데이트
    // ========================================
    function updateChart(kline) {
        if (!candleSeries || !kline) return;

        const bar = {
            time: Math.floor(kline.t / 1000),
            open: parseFloat(kline.o),
            high: parseFloat(kline.h),
            low: parseFloat(kline.l),
            close: parseFloat(kline.c)
        };
        candleSeries.update(bar);
    }

    // ========================================
    // 포맷 함수
    // ========================================
    function formatPrice(price) {
        const p = parseFloat(price);
        if (isNaN(p)) return '0.00';
        if (p >= 1000) return p.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (p >= 1) return p.toFixed(2);
        return p.toFixed(4);
    }

    function formatVolume(vol) {
        const v = parseFloat(vol);
        if (isNaN(v)) return '0';
        if (v >= 1e9) return (v / 1e9).toFixed(2) + 'B';
        if (v >= 1e6) return (v / 1e6).toFixed(2) + 'M';
        if (v >= 1e3) return (v / 1e3).toFixed(2) + 'K';
        return v.toFixed(2);
    }

    function formatTime(timestamp) {
        const d = new Date(timestamp);
        return d.toLocaleString('ko-KR', {
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // ========================================
    // 포지션 렌더링
    // ========================================
    function renderPositions(positions) {
        const panel = document.getElementById('positionsPanel');
        const countEl = document.getElementById('positionCount');

        if (!positions || positions.length === 0) {
            panel.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <div>No open positions</div>
                </div>
            `;
            countEl.textContent = '0';
            return;
        }

        countEl.textContent = positions.length;

        // 모바일 감지
        const isMobile = window.innerWidth <= 768;

        let html = '';

        if (isMobile) {
            // 바이빗 스타일 모바일 카드
            positions.forEach(pos => {
                const pnlClass = pos.pnl >= 0 ? 'positive' : 'negative';
                const sideClass = pos.side === 'LONG' ? 'long' : 'short';
                const pnlSign = pos.pnl >= 0 ? '' : '';
                const roiSign = pos.pnlPercent >= 0 ? '' : '';

                // 레버리지 바 계산 (최대 125x 기준, 4단계)
                const levBars = Math.min(4, Math.ceil(pos.leverage / 30));
                let levBarsHtml = '';
                for (let i = 0; i < 4; i++) {
                    levBarsHtml += `<span class="mpc-lev-bar ${i < levBars ? 'active' : ''} ${sideClass}"></span>`;
                }

                // MMR 계산 (임시 - 실제는 API에서)
                const mmr = (pos.margin / (pos.size * pos.markPrice) * 100).toFixed(2);

                html += `
                    <div class="mobile-position-card">
                        <!-- 심볼 헤더 -->
                        <div class="mpc-header">
                            <span class="mpc-symbol">${pos.symbol}</span>
                            <span class="mpc-settings">⚙</span>
                        </div>

                        <!-- 방향/모드/레버리지 -->
                        <div class="mpc-position-info">
                            <span class="mpc-side ${sideClass}">${pos.side}</span>
                            <span class="mpc-mode">Cross</span>
                            <span class="mpc-leverage">
                                ${pos.leverage}x
                                <i>✏</i>
                                <span class="mpc-leverage-bars">${levBarsHtml}</span>
                            </span>
                        </div>

                        <!-- PNL 섹션 -->
                        <div class="mpc-pnl-section">
                            <div class="mpc-pnl-left">
                                <span class="mpc-pnl-label">Unrealized PnL (USDT)</span>
                                <span class="mpc-pnl-value ${pnlClass}">${pnlSign}${pos.pnl.toFixed(2)}</span>
                                <span class="mpc-pnl-usd">≈$ ${pnlSign}${pos.pnl.toFixed(2)}</span>
                            </div>
                            <div class="mpc-pnl-right">
                                <span class="mpc-roi-label">ROI <i>ⓘ</i></span>
                                <span class="mpc-roi-value ${pnlClass}">${roiSign}${pos.pnlPercent.toFixed(2)}%</span>
                            </div>
                        </div>

                        <!-- 정보 그리드 -->
                        <div class="mpc-info-grid">
                            <div class="mpc-info-item">
                                <span class="mpc-info-label">Size (${pos.symbol.replace('USDT', '')})</span>
                                <span class="mpc-info-value">${pos.size}</span>
                            </div>
                            <div class="mpc-info-item">
                                <span class="mpc-info-label">Margin (USDT)</span>
                                <span class="mpc-info-value">${formatNumber(pos.margin, 2)}</span>
                            </div>
                            <div class="mpc-info-item">
                                <span class="mpc-info-label">MMR</span>
                                <span class="mpc-info-value mmr">${mmr}%</span>
                            </div>
                            <div class="mpc-info-item">
                                <span class="mpc-info-label">Entry Price</span>
                                <span class="mpc-info-value">${formatPrice(pos.entryPrice)}</span>
                            </div>
                            <div class="mpc-info-item">
                                <span class="mpc-info-label">Mark Price</span>
                                <span class="mpc-info-value">${formatPrice(pos.markPrice)}</span>
                            </div>
                            <div class="mpc-info-item">
                                <span class="mpc-info-label">Est. Liq. Price</span>
                                <span class="mpc-info-value highlight">${formatPrice(pos.liqPrice)}</span>
                            </div>
                        </div>

                        <!-- 액션 버튼 -->
                        <div class="mpc-actions">
                            <button class="mpc-btn mpc-btn-reverse">Reverse</button>
                            <button class="mpc-btn mpc-btn-tpsl">TP/SL</button>
                            <button class="mpc-btn mpc-btn-close">Close</button>
                        </div>
                    </div>
                `;
            });
        } else {
            // 데스크탑 테이블 스타일
            const KRW_RATE = 1450; // USD to KRW 환율

            html = `
                <div class="positions-table">
                    <div class="positions-header">
                        <span>Symbol</span>
                        <span>Size</span>
                        <span>Entry Price</span>
                        <span>Mark Price</span>
                        <span>Liq. Price</span>
                        <span>Margin</span>
                        <span>PNL (ROE%)</span>
                        <span>Actions</span>
                    </div>
            `;

            positions.forEach(pos => {
                const pnlClass = pos.pnl >= 0 ? 'positive' : 'negative';
                const sideClass = pos.side === 'LONG' ? 'long' : 'short';
                const sizeUsdt = pos.size * pos.markPrice; // USDT 가치
                const pnlKrw = pos.pnl * KRW_RATE; // 원화 가치

                html += `
                    <div class="positions-row">
                        <span class="symbol-cell">
                            <span class="side-badge ${sideClass}">${pos.side}</span>
                            <span>${pos.symbol}</span>
                            <span class="leverage-badge">${pos.leverage}x</span>
                        </span>
                        <span class="size-cell">
                            <span class="size-qty">${pos.size}</span>
                            <span class="size-usdt">${formatNumber(sizeUsdt, 2)} USDT</span>
                        </span>
                        <span>${formatPrice(pos.entryPrice)}</span>
                        <span>${formatPrice(pos.markPrice)}</span>
                        <span class="liq-price">${formatPrice(pos.liqPrice)}</span>
                        <span>${pos.margin.toFixed(2)} USDT</span>
                        <span class="pnl-cell ${pnlClass}">
                            <span class="pnl-main">
                                <span class="pnl-value">${pos.pnl >= 0 ? '+' : ''}${pos.pnl.toFixed(2)} USDT</span>
                                <span class="pnl-percent">(${pos.pnl >= 0 ? '+' : ''}${pos.pnlPercent.toFixed(2)}%)</span>
                            </span>
                            <span class="pnl-krw">₩${formatNumber(Math.abs(pnlKrw), 0)} 원</span>
                        </span>
                        <span class="actions-cell">
                            <button class="btn-close-pos">Close</button>
                        </span>
                    </div>
                `;
            });

            html += '</div>';
        }

        panel.innerHTML = html;
    }

    // 숫자 포맷 헬퍼
    function formatNumber(num, decimals = 2) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // ========================================
    // 주문 렌더링
    // ========================================
    function renderOrders(orders) {
        const panel = document.getElementById('ordersPanel');
        const countEl = document.getElementById('orderCount');

        if (!orders || orders.length === 0) {
            panel.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <div>No open orders</div>
                </div>
            `;
            countEl.textContent = '0';
            return;
        }

        countEl.textContent = orders.length;

        let html = `
            <div class="orders-table">
                <div class="orders-header">
                    <span>Time</span>
                    <span>Symbol</span>
                    <span>Type</span>
                    <span>Side</span>
                    <span>Price</span>
                    <span>Quantity</span>
                    <span>Filled</span>
                    <span>Actions</span>
                </div>
        `;

        orders.forEach(order => {
            const sideClass = order.side === 'BUY' ? 'buy' : 'sell';
            const price = order.price || order.stopPrice || '--';

            html += `
                <div class="orders-row">
                    <span>${formatTime(order.time)}</span>
                    <span>${order.symbol}</span>
                    <span>${order.type}</span>
                    <span class="${sideClass}">${order.side}</span>
                    <span>${price !== '--' ? formatPrice(price) : '--'}</span>
                    <span>${order.quantity}</span>
                    <span>${order.filled}/${order.quantity}</span>
                    <span class="actions-cell">
                        <button class="btn-cancel">Cancel</button>
                    </span>
                </div>
            `;
        });

        html += '</div>';
        panel.innerHTML = html;
    }

    // ========================================
    // 거래 내역 렌더링
    // ========================================
    function renderTrades(trades) {
        const panel = document.getElementById('tradesPanel');

        if (!trades || trades.length === 0) {
            panel.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">💹</div>
                    <div>No trade history</div>
                </div>
            `;
            return;
        }

        let html = `
            <div class="trades-table">
                <div class="trades-header">
                    <span>Time</span>
                    <span>Symbol</span>
                    <span>Side</span>
                    <span>Price</span>
                    <span>Quantity</span>
                    <span>Fee</span>
                    <span>Realized PNL</span>
                </div>
        `;

        trades.forEach(trade => {
            const sideClass = trade.side === 'BUY' ? 'buy' : 'sell';
            const pnlText = trade.pnl !== null
                ? `<span class="${trade.pnl >= 0 ? 'positive' : 'negative'}">${trade.pnl >= 0 ? '+' : ''}${trade.pnl.toFixed(2)} USDT</span>`
                : '--';

            html += `
                <div class="trades-row">
                    <span>${formatTime(trade.time)}</span>
                    <span>${trade.symbol}</span>
                    <span class="${sideClass}">${trade.side}</span>
                    <span>${formatPrice(trade.price)}</span>
                    <span>${trade.quantity}</span>
                    <span>${trade.fee.toFixed(2)} USDT</span>
                    <span>${pnlText}</span>
                </div>
            `;
        });

        html += '</div>';
        panel.innerHTML = html;
    }

    // ========================================
    // 계정 정보 렌더링
    // ========================================
    function renderAccount(account) {
        const marginBalanceEl = document.getElementById('marginBalance');
        const availableBalanceEl = document.getElementById('availableBalance');
        const availableBalanceAccEl = document.getElementById('availableBalanceAcc');
        const pnlEl = document.getElementById('unrealizedPnl');
        const maxSizeEl = document.getElementById('maxSize');
        const orderAvailableEl = document.getElementById('orderAvailable');

        if (marginBalanceEl) marginBalanceEl.textContent = account.marginBalance.toFixed(2) + ' USDT';
        if (availableBalanceEl) availableBalanceEl.textContent = account.availableBalance.toFixed(2) + ' USDT';
        if (availableBalanceAccEl) availableBalanceAccEl.textContent = account.availableBalance.toFixed(2) + ' USDT';
        if (orderAvailableEl) orderAvailableEl.textContent = account.availableBalance.toFixed(2) + ' USDT';

        if (pnlEl) {
            const pnlClass = account.unrealizedPnl >= 0 ? 'positive' : 'negative';
            pnlEl.className = 'value ' + pnlClass;
            pnlEl.textContent = (account.unrealizedPnl >= 0 ? '+' : '') + account.unrealizedPnl.toFixed(2) + ' USDT';
        }

        // Max Size 계산 (20x 레버리지 기준)
        if (maxSizeEl) {
            const maxSize = (account.availableBalance * 20) / currentPrice;
            maxSizeEl.textContent = maxSize.toFixed(5) + ' <?= htmlspecialchars($baseCoin) ?>';
        }
    }

    // ========================================
    // 탭 전환
    // ========================================
    function initTabs() {
        // 하단 패널 탭
        document.querySelectorAll('.bottom-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.bottom-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.bottom-panel-section').forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
                const panel = document.getElementById(tab.dataset.tab + 'Panel');
                if (panel) panel.classList.add('active');
            });
        });

        // 필터 버튼
        document.querySelectorAll('.panel-filters .filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.panel-filters .filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // 주문 타입 탭
        document.querySelectorAll('.order-type-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.order-type-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });

        // 인터벌 버튼
        document.querySelectorAll('.interval-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const interval = btn.dataset.interval;
                if (socket) {
                    socket.emit('subscribe', { symbol: symbol, interval: interval + 'm' });
                }
            });
        });

        // 슬라이더 버튼
        document.querySelectorAll('.slider-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.slider-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // ========================================
        // 모바일 전용 이벤트 핸들러
        // ========================================

        // 모바일 Open/Close 탭
        document.querySelectorAll('.mobile-oc-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.mobile-oc-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const mode = tab.dataset.mode;
                // Open/Close 모드에 따른 UI 변경 가능
                console.log('[Mobile] Trade mode:', mode);
            });
        });

        // 모바일 퍼센트 버튼
        document.querySelectorAll('.mobile-pct-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.mobile-pct-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const pct = parseInt(btn.dataset.pct);
                const availableBalance = MOCK_ACCOUNT.availableBalance;
                const leverage = 20;
                const maxAmount = (availableBalance * leverage) / currentPrice;
                const selectedAmount = (maxAmount * pct / 100).toFixed(5);

                // 모바일 수량 입력 필드에 값 설정
                const mobileQtyInput = document.getElementById('mobileQtyInput');
                if (mobileQtyInput) {
                    mobileQtyInput.value = selectedAmount;
                }
            });
        });

        // 모바일 오더북 클릭 시 가격 입력
        document.querySelectorAll('#mobileAsks, #mobileBids').forEach(container => {
            container.addEventListener('click', (e) => {
                const row = e.target.closest('.mobile-ob-row');
                if (row) {
                    const priceEl = row.querySelector('.price');
                    if (priceEl) {
                        const price = priceEl.textContent.replace(/,/g, '');
                        const mobilePriceInput = document.getElementById('mobilePriceInput');
                        if (mobilePriceInput) {
                            mobilePriceInput.value = price;
                        }
                    }
                }
            });
        });

        // 모바일 Long/Short 버튼
        const mobileBtnLong = document.getElementById('mobileBtnLong');
        const mobileBtnShort = document.getElementById('mobileBtnShort');

        if (mobileBtnLong) {
            mobileBtnLong.addEventListener('click', () => {
                alert('Open Long position (mock)');
            });
        }

        if (mobileBtnShort) {
            mobileBtnShort.addEventListener('click', () => {
                alert('Open Short position (mock)');
            });
        }
    }

    // ========================================
    // 심볼 드롭다운
    // ========================================

    // 심볼 드롭다운 목록 동적 생성 (바이낸스 스타일)
    function generateSymbolList() {
        const symbolList = document.getElementById('symbolList');
        if (!symbolList) return;

        // 지원하는 심볼 목록
        const supportedSymbols = Object.keys(symbolConfigs);

        // 심볼별 거래량 (임시 데이터)
        const volumes = {};
        supportedSymbols.forEach(symbol => {
            volumes[symbol] = (Math.random() * 10).toFixed(2);
        });

        // HTML 생성
        let html = '';
        supportedSymbols.forEach(sym => {
            const config = symbolConfigs[sym];
            const baseName = sym.replace('USDT', '').toLowerCase();
            const isActive = sym === currentSymbol;
            const volNum = volumes[sym];

            html += `
                <div class="dropdown-item${isActive ? ' active' : ''}" data-symbol="${sym}" data-volume="${volNum}">
                    <span class="item-star">☆</span>
                    <img class="item-logo" src="${config.logo}" alt="${config.name}" onerror="this.style.display='none'">
                    <div class="item-info">
                        <span class="item-name">${sym} <span class="perp-badge">Perp</span></span>
                        <span class="item-vol">Vol ${volNum}B</span>
                    </div>
                    <span class="item-price" id="dropdown-${baseName}-price">-</span>
                    <span class="item-change" id="dropdown-${baseName}-change">-</span>
                </div>
            `;
        });

        symbolList.innerHTML = html;
    }

    function setupSymbolDropdown() {
        const selector = document.querySelector('.symbol-selector');
        const trigger = document.getElementById('symbolDropdownTrigger');
        const dropdown = document.getElementById('symbolDropdown');
        const searchInput = document.getElementById('symbolSearch');
        const symbolList = document.getElementById('symbolList');

        if (!trigger || !dropdown) return;

        // 심볼 리스트 동적 생성
        generateSymbolList();

        // 헤더 즐겨찾기 별 클릭 이벤트
        const headerStar = document.getElementById('symbolFavorite');
        if (headerStar) {
            headerStar.addEventListener('click', (e) => {
                e.stopPropagation();
                headerStar.classList.toggle('active');
                const isAdded = headerStar.classList.contains('active');
                headerStar.textContent = isAdded ? '★' : '☆';

                // 현재 심볼의 드롭다운 별표도 동기화
                const currentSymbolName = document.getElementById('currentSymbolName')?.textContent;
                if (currentSymbolName) {
                    const dropdownItem = document.querySelector(`.dropdown-item[data-symbol="${currentSymbolName}"]`);
                    if (dropdownItem) {
                        const itemStar = dropdownItem.querySelector('.item-star');
                        if (itemStar) {
                            if (isAdded) {
                                itemStar.classList.add('active');
                                itemStar.textContent = '★';
                            } else {
                                itemStar.classList.remove('active');
                                itemStar.textContent = '☆';
                            }
                        }
                    }
                    // 쿠키에 저장
                    saveFavorites();

                    // 토스트 알림
                    if (isAdded) {
                        showToast(`${currentSymbolName} 즐겨찾기에 추가됨`, 'success');
                    } else {
                        showToast(`${currentSymbolName} 즐겨찾기에서 제거됨`, 'info');
                    }
                }
            });
        }

        // 드롭다운 토글 (별 클릭 제외)
        trigger.addEventListener('click', (e) => {
            // 별 클릭시 드롭다운 열지 않음
            if (e.target.classList.contains('symbol-star')) return;

            e.stopPropagation();
            selector.classList.toggle('open');
            if (selector.classList.contains('open')) {
                searchInput.focus();
                // 현재 활성 탭에 따라 필터링 적용
                const activeTab = document.querySelector('.dropdown-tab.active');
                if (activeTab) {
                    filterSymbolList(activeTab.dataset.tab);
                }
            }
        });

        // 외부 클릭시 닫기
        document.addEventListener('click', (e) => {
            if (!selector.contains(e.target)) {
                selector.classList.remove('open');
            }
        });

        // 검색 기능
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.dropdown-item').forEach(item => {
                const symbol = item.dataset.symbol.toLowerCase();
                item.style.display = symbol.includes(query) ? '' : 'none';
            });
        });

        // 심볼 선택
        symbolList.addEventListener('click', (e) => {
            const item = e.target.closest('.dropdown-item');
            if (!item) return;

            // 별표 클릭시 즐겨찾기
            if (e.target.classList.contains('item-star')) {
                e.target.classList.toggle('active');
                const isAdded = e.target.classList.contains('active');
                e.target.textContent = isAdded ? '★' : '☆';

                // 쿠키에 즐겨찾기 저장
                saveFavorites();

                // 토스트 알림
                const symbolName = item.dataset.symbol;
                if (isAdded) {
                    showToast(`${symbolName} 즐겨찾기에 추가됨`, 'success');
                } else {
                    showToast(`${symbolName} 즐겨찾기에서 제거됨`, 'info');
                }

                // Favorites 탭이 활성화된 경우 필터링 다시 적용
                const activeTab = document.querySelector('.dropdown-tab.active');
                if (activeTab && activeTab.dataset.tab === 'favorites') {
                    filterSymbolList('favorites');
                }
                return;
            }

            const sym = item.dataset.symbol;
            selectSymbol(sym);
            selector.classList.remove('open');
        });

        // 드롭다운 메인 탭 (Favorites / USDⓈ-M)
        document.querySelectorAll('.dropdown-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const tabType = this.dataset.tab;
                filterSymbolList(tabType);
            });
        });

        // 탭에 따른 심볼 목록 필터링
        function filterSymbolList(tabType) {
            const items = document.querySelectorAll('.dropdown-item');
            const dropdownList = document.getElementById('symbolList');
            const dropdownEmpty = document.getElementById('dropdownEmpty');
            let visibleCount = 0;

            items.forEach(item => {
                if (tabType === 'favorites') {
                    // 즐겨찾기 탭: 즐겨찾기된 항목만 표시
                    const star = item.querySelector('.item-star');
                    const isFavorite = star && star.classList.contains('active');
                    item.style.display = isFavorite ? '' : 'none';
                    if (isFavorite) visibleCount++;
                } else {
                    // USDⓈ-M 탭: 모든 항목 표시
                    item.style.display = '';
                    visibleCount++;
                }
            });

            // 즐겨찾기 탭에서 항목이 없으면 빈 상태 메시지 표시
            if (tabType === 'favorites' && visibleCount === 0) {
                dropdownList.style.display = 'none';
                dropdownEmpty.style.display = 'flex';
            } else {
                dropdownList.style.display = '';
                dropdownEmpty.style.display = 'none';
            }
        }

        // 즐겨찾기 저장 (쿠키)
        function saveFavorites() {
            const favorites = [];
            document.querySelectorAll('.dropdown-item').forEach(item => {
                const star = item.querySelector('.item-star');
                if (star && star.classList.contains('active')) {
                    favorites.push(item.dataset.symbol);
                }
            });
            setCookie('futurx_favorites', JSON.stringify(favorites));

            // 헤더 별표 동기화
            syncHeaderStar();
        }

        // 즐겨찾기 불러오기 (쿠키)
        function loadFavorites() {
            const saved = getCookie('futurx_favorites');
            if (saved) {
                try {
                    const favorites = JSON.parse(saved);
                    document.querySelectorAll('.dropdown-item').forEach(item => {
                        const star = item.querySelector('.item-star');
                        if (star && favorites.includes(item.dataset.symbol)) {
                            star.classList.add('active');
                            star.textContent = '★';
                        }
                    });
                } catch(e) {}
            }
        }

        // 정렬 상태
        let currentSort = { field: null, direction: null };
        let originalOrder = [];

        // 정렬 기능
        function sortSymbolList(field) {
            const list = document.getElementById('symbolList');
            const items = Array.from(list.querySelectorAll('.dropdown-item'));

            // 원래 순서 저장 (최초 1회)
            if (originalOrder.length === 0) {
                originalOrder = items.map(item => item.dataset.symbol);
            }

            // 정렬 방향 토글: 내림차순 → 오름차순 → 해제
            if (currentSort.field === field) {
                if (currentSort.direction === 'desc') {
                    currentSort.direction = 'asc';
                } else if (currentSort.direction === 'asc') {
                    currentSort.direction = null;
                    currentSort.field = null;
                }
            } else {
                currentSort.field = field;
                currentSort.direction = 'desc';
            }

            // 정렬 버튼 UI 업데이트
            document.querySelectorAll('.sort-btn').forEach(btn => {
                btn.classList.remove('asc', 'desc');
            });

            if (currentSort.direction) {
                const activeBtn = document.querySelector(`.sort-btn[data-sort="${field}"]`);
                if (activeBtn) {
                    activeBtn.classList.add(currentSort.direction);
                }
            }

            // 정렬 해제 시 원래 순서로 복원
            if (!currentSort.direction) {
                items.sort((a, b) => {
                    return originalOrder.indexOf(a.dataset.symbol) - originalOrder.indexOf(b.dataset.symbol);
                });
                items.forEach(item => list.appendChild(item));
                return;
            }

            // 정렬 실행
            items.sort((a, b) => {
                let valA, valB;

                switch (currentSort.field) {
                    case 'symbol':
                        valA = a.dataset.symbol || '';
                        valB = b.dataset.symbol || '';
                        return currentSort.direction === 'asc'
                            ? valA.localeCompare(valB)
                            : valB.localeCompare(valA);
                    case 'volume':
                        valA = parseFloat(a.dataset.volume) || 0;
                        valB = parseFloat(b.dataset.volume) || 0;
                        break;
                    case 'price':
                        valA = parseFloat(a.querySelector('.item-price')?.textContent.replace(/,/g, '')) || 0;
                        valB = parseFloat(b.querySelector('.item-price')?.textContent.replace(/,/g, '')) || 0;
                        break;
                    case 'change':
                        valA = parseFloat(a.querySelector('.item-change')?.textContent.replace(/[+%]/g, '')) || 0;
                        valB = parseFloat(b.querySelector('.item-change')?.textContent.replace(/[+%]/g, '')) || 0;
                        break;
                    default:
                        return 0;
                }

                return currentSort.direction === 'asc' ? valA - valB : valB - valA;
            });

            // DOM에 다시 추가
            items.forEach(item => list.appendChild(item));
        }

        // 정렬 버튼 이벤트
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const sortField = btn.dataset.sort;
                sortSymbolList(sortField);
            });
        });

        // 즐겨찾기 초기화
        loadFavorites();

        // 헤더 별표 초기 동기화
        syncHeaderStar();

        // USDⓈ-M 탭이 기본이므로 초기 필터링
        filterSymbolList('usdm');
    }

    // 심볼 선택
    function selectSymbol(sym) {
        const config = symbolConfigs[sym];
        if (!config) {
            console.warn('Unknown symbol:', sym);
            return;
        }

        // 이미 같은 심볼이면 무시
        if (currentSymbol === sym.toUpperCase()) {
            return;
        }

        console.log('Switching symbol to:', sym);
        currentSymbol = sym.toUpperCase();

        // UI 업데이트
        updateSymbolUI(sym);

        // 토스트 알림
        showToast(`${sym} 로딩중...`, 'info');
    }

    // 심볼 UI 업데이트
    function updateSymbolUI(sym) {
        const config = symbolConfigs[sym];
        if (!config) return;

        // 헤더 심볼명 업데이트
        document.getElementById('currentSymbolName').textContent = sym;
        const logoEl = document.getElementById('currentCoinLogo');
        if (logoEl && config.logo) {
            logoEl.src = config.logo;
        }

        // 페이지 타이틀 업데이트
        document.title = `${sym} 무기한 - FuturX`;

        // URL 업데이트 (브라우저 히스토리에 추가)
        if (window.history && window.history.pushState) {
            const newUrl = `trade2.php?symbol=${sym}`;
            window.history.pushState({ symbol: sym }, document.title, newUrl);
        }

        // 활성 항목 표시
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.classList.toggle('active', item.dataset.symbol === sym);
        });

        // 드롭다운 닫기
        const selector = document.querySelector('.symbol-selector');
        if (selector) selector.classList.remove('open');

        // 헤더 별표 동기화
        syncHeaderStar();

        console.log('Symbol UI updated to:', sym);
    }

    // ========================================
    // 초기화
    // ========================================
    document.addEventListener('DOMContentLoaded', () => {
        // loadMockData()로 가짜 데이터 사용 (실DB 연결 안함)
        loadMockData();
        initTabs();
        setupSymbolDropdown();
    });

})();
</script>

<?php require_once 'includes/footer.php'; ?>
