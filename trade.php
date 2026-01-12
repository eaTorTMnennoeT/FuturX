<?php
// 환율 extend 로드 (1시간마다 자동 업데이트)
require_once __DIR__ . '/extend/exchange_rate.extend.php';
require_once __DIR__ . '/config.php';
$usdKrwRate = getUsdKrwRate();

// DB에서 심볼 목록 가져오기
$symbolsData = getSymbols(true);
$supportedSymbols = array_keys($symbolsData);

// URL에서 심볼 파라미터 받기 (예: trade.php?symbol=ETHUSDT 또는 /ETHUSDT)
$urlSymbol = isset($_GET['symbol']) ? strtoupper(trim($_GET['symbol'])) : 'BTCUSDT';

// PATH_INFO로도 받을 수 있게 (예: trade.php/ETHUSDT)
if (isset($_SERVER['PATH_INFO'])) {
    $pathSymbol = strtoupper(trim($_SERVER['PATH_INFO'], '/'));
    if (in_array($pathSymbol, $supportedSymbols)) {
        $urlSymbol = $pathSymbol;
    }
}

// 지원되는 심볼인지 확인
if (!in_array($urlSymbol, $supportedSymbols)) {
    $urlSymbol = 'BTCUSDT';
}

// DB에서 로고 URL 가져오기
$currentLogo = isset($symbolsData[$urlSymbol]['logo_url']) && $symbolsData[$urlSymbol]['logo_url']
    ? $symbolsData[$urlSymbol]['logo_url']
    : 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';
$baseCoin = str_replace('USDT', '', $urlSymbol);

$pageTitle = $urlSymbol . ' 무기한';
$pageCSS = ['trade.css'];
require_once 'includes/head.php';
?>
    <!-- Socket.IO Client (로컬 ws-server 제공 버전 사용: CDN 차단/오프라인 이슈 방지) -->
    <script src="<?= WS_SERVER_URL ?>/socket.io/socket.io.js"></script>
    <!-- Lightweight Charts for custom markers -->
    <script src="https://unpkg.com/lightweight-charts@4.1.0/dist/lightweight-charts.standalone.production.js"></script>

    <!-- 연결 상태 오버레이 -->
    <div id="connectionOverlay" class="connection-overlay">
        <div class="connection-content">
            <div class="connection-spinner"></div>
            <div class="connection-text">서버에 연결중...</div>
            <div class="connection-subtext">실시간 데이터를 불러오는 중입니다</div>
        </div>
    </div>

<?php require_once 'includes/header.php'; ?>

    <!-- 심볼 정보 바 (바이비트 스타일 - 전체 너비) -->
    <!-- 초기 심볼 (PHP에서 전달) -->
    <script>
        window.initialSymbol = '<?= $urlSymbol ?>';
    </script>

    <div class="symbol-info-bar">
        <div class="symbol-selector">
            <div class="symbol-main" id="symbolDropdownTrigger">
                <span class="symbol-star" id="symbolFavorite" title="즐겨찾기">☆</span>
                <img src="<?= $currentLogo ?>" alt="<?= $baseCoin ?>" class="coin-logo" id="currentCoinLogo">
                <div class="symbol-text">
                    <span class="symbol-name" id="currentSymbolName"><?= $urlSymbol ?></span>
                    <span class="symbol-type">USDT Perpetual</span>
                </div>
                <span class="dropdown-arrow">▾</span>
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
            <span class="current-price up" id="mainPrice">90,995.40</span>
            <span class="mark-price" id="markPrice">91,000.87</span>
        </div>
        <span class="bar-divider">|</span>
        <div class="stat-item">
            <span class="stat-label">Index Price</span>
            <span class="stat-value" id="indexPrice">91,045.20</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Change %</span>
            <span class="stat-value up" id="changePercent">+423.39 (+0.46%)</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">24H High</span>
            <span class="stat-value" id="high24h">91,174.40</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">24H Low</span>
            <span class="stat-value" id="low24h">90,332.80</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">24H Turnover(USDT)</span>
            <span class="stat-value" id="turnover24h">1,032,903,419.58</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Open Interest(<span id="oiCoinLabel"><?= $baseCoin ?></span>)</span>
            <span class="stat-value" id="openInterest">51,494.36</span>
        </div>
        <div class="stat-item funding">
            <span class="stat-label">Funding Rate / Countdown</span>
            <span class="stat-value up" id="fundingRate">0.0043% / 00:53:55</span>
        </div>
    </div>

    <!-- 메인 레이아웃 (바이빗 스타일) -->
    <div class="trade-wrapper">

        <!-- 1열: 왼쪽 사이드바 (도구 아이콘) - 숨김 -->
        <div class="left-sidebar">
            <div class="sidebar-icon active" title="커서">✚</div>
            <div class="sidebar-icon" title="추세선">╱</div>
            <div class="sidebar-icon" title="피보나치">⌇</div>
            <div class="sidebar-icon" title="브러시">✎</div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-icon" title="텍스트">T</div>
            <div class="sidebar-icon" title="측정">📏</div>
            <div class="sidebar-icon" title="줌">🔍</div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-icon" title="마그넷">⊙</div>
            <div class="sidebar-icon" title="잠금">🔒</div>
            <div class="sidebar-icon" title="표시">👁</div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-icon" title="삭제">🗑</div>
        </div>

        <!-- 1열: 차트 영역 -->
        <div class="chart-section">
            <!-- 차트 탭 -->
            <div class="chart-tabs">
                <button class="chart-tab active">Chart</button>
                <button class="chart-tab">Overview</button>
                <button class="chart-tab">Data</button>
                <button class="chart-tab">Feed</button>
                <div class="chart-tabs-right">
                    <span class="active">Standard</span>
                    <span>TradingView</span>
                    <span>Depth</span>
                </div>
            </div>

            <!-- 차트 컨테이너 -->
            <div class="chart-container">
                <!-- 차트 도구바 -->
                <div class="chart-toolbar">
                    <div class="time-intervals">
                        <button class="interval-btn">1s</button>
                        <button class="interval-btn active">1m</button>
                        <button class="interval-btn">5m</button>
                        <button class="interval-btn">15m</button>
                        <button class="interval-btn">30m</button>
                        <button class="interval-btn">1h</button>
                        <button class="interval-btn">4h</button>
                        <button class="interval-btn">1d</button>
                        <button class="interval-btn">1w</button>
                        <button class="interval-btn">1M</button>
                    </div>
                </div>
                <!-- TradingView 차트 -->
                <div class="chart-wrapper">
                    <div id="tradingview-chart"></div>
                    <!-- 차트 마커 오버레이 (TradingView 모드용) -->
                    <div id="chartMarkersOverlay" class="chart-markers-overlay"></div>
                </div>
            </div>
        </div><!-- /chart-section -->

        <!-- 2열: 오더북 -->
        <div class="orderbook-section">
            <div class="orderbook-area">
            <div class="orderbook-tabs">
                <button class="ob-tab active">Order Book</button>
                <button class="ob-tab">Recent Trades</button>
            </div>
            <div class="orderbook-options">
                <div class="ob-view-btns">
                    <button class="ob-view active" data-view="both" title="전체">
                        <span class="bar red"></span>
                        <span class="bar green"></span>
                    </button>
                    <button class="ob-view" data-view="bids" title="매수만">
                        <span class="bar green"></span>
                        <span class="bar green"></span>
                    </button>
                    <button class="ob-view" data-view="asks" title="매도만">
                        <span class="bar red"></span>
                        <span class="bar red"></span>
                    </button>
                </div>
                <div class="ob-precision-wrapper">
                    <select class="precision-select" id="precisionSelect">
                        <?php
                        // 심볼별 정밀도 옵션
                        $precisionConfig = [
                            'BTCUSDT' => ['default' => 0.1, 'options' => [0.1, 1, 10, 50, 100]],
                            'ETHUSDT' => ['default' => 0.01, 'options' => [0.01, 0.1, 1, 10, 50]],
                            'SOLUSDT' => ['default' => 0.01, 'options' => [0.01, 0.1, 1, 5, 10]],
                            'XRPUSDT' => ['default' => 0.0001, 'options' => [0.0001, 0.001, 0.01, 0.1]],
                            'DOGEUSDT' => ['default' => 0.00001, 'options' => [0.00001, 0.0001, 0.001, 0.01]],
                            'ADAUSDT' => ['default' => 0.0001, 'options' => [0.0001, 0.001, 0.01, 0.1]],
                            'AVAXUSDT' => ['default' => 0.01, 'options' => [0.01, 0.1, 1, 5, 10]],
                        ];
                        $config = $precisionConfig[$urlSymbol] ?? ['default' => 0.01, 'options' => [0.01, 0.1, 1, 10]];
                        foreach ($config['options'] as $opt) {
                            $selected = ($opt == $config['default']) ? 'selected' : '';
                            echo "<option value=\"{$opt}\" {$selected}>{$opt}</option>";
                        }
                        ?>
                    </select>
                    <span class="precision-arrow">▼</span>
                </div>
            </div>
            <div class="orderbook-header" id="orderbookHeader">
                <span>Price(<span class="ob-coin"><?= $baseCoin ?></span>)</span>
                <span>Qty(<span class="ob-coin"><?= $baseCoin ?></span>)</span>
                <span>Total(<span class="ob-coin"><?= $baseCoin ?></span>)</span>
            </div>
            <div class="orderbook-content">
                <div class="asks-list" id="asksList"></div>
                <div class="orderbook-spread">
                    <span class="spread-price up" id="spreadPrice">Loading...</span>
                    <span class="spread-mark" id="spreadMark"></span>
                </div>
                <div class="bids-list" id="bidsList"></div>
            </div>
            <div class="orderbook-footer">
                <div class="depth-bar">
                    <div class="depth-bid" style="width: 60%">B 60%</div>
                    <div class="depth-ask" style="width: 40%">40% S</div>
                </div>
            </div>
            </div><!-- /orderbook-area -->
        </div><!-- /orderbook-section -->

        <!-- 하단 패널 (차트+오더북 아래) -->
        <div class="bottom-panel">
                <div class="panel-tabs">
                    <button class="panel-tab active">Open Orders <span class="tab-count">0</span></button>
                    <button class="panel-tab">Positions <span class="tab-count">0</span></button>
                    <button class="panel-tab">Order History</button>
                    <button class="panel-tab">Trade History</button>
                    <button class="panel-tab">Assets</button>
                    <button class="panel-tab">P&L</button>
                </div>
                <div class="panel-filters" id="panelFilters">
                    <button class="filter-btn active">Limit & Market Orders</button>
                    <button class="filter-btn">Conditional</button>
                    <button class="filter-btn">TP/SL</button>
                    <button class="filter-btn">Trailing Stop</button>
                    <button class="filter-btn">OCO</button>
                    <button class="filter-btn">MMR Close</button>
                    <button class="filter-btn">Spread Trading</button>
                </div>
                <div class="panel-content" id="panelContent">
                    <!-- Open Orders -->
                    <div class="panel-section active" id="openOrdersPanel">
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <div class="empty-text">No Open Orders</div>
                        </div>
                    </div>
                    <!-- Positions -->
                    <div class="panel-section" id="positionsPanel">
                        <div class="panel-table-scroll">
                            <table class="data-table">
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
                                <tbody id="positionsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Order History -->
                    <div class="panel-section" id="orderHistoryPanel">
                        <div class="panel-table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Symbol</th>
                                        <th>Type</th>
                                        <th>Side</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="orderHistoryBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Trade History -->
                    <div class="panel-section" id="tradeHistoryPanel">
                        <div class="panel-table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Symbol</th>
                                        <th>Side</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Fee</th>
                                        <th>Realized PNL</th>
                                    </tr>
                                </thead>
                                <tbody id="tradeHistoryBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Assets -->
                    <div class="panel-section" id="assetsPanel">
                        <div class="panel-table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Coin</th>
                                        <th>Wallet Balance</th>
                                        <th>Available</th>
                                        <th>In Order</th>
                                        <th>USD Value</th>
                                    </tr>
                                </thead>
                                <tbody id="assetsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <!-- P&L -->
                    <div class="panel-section" id="pnlPanel">
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
                            <table class="data-table pnl-table" id="pnlTable">
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
                                    <tr><td colspan="10" class="empty-cell">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pnl-footer">
                            <div class="pnl-stats" id="pnlStats">Total: 0 records</div>
                            <div class="pnl-pagination" id="pnlPagination"></div>
                        </div>
                    </div>

                    <!-- Trading Performance Modal -->
                    <div class="performance-modal" id="performanceModal">
                        <div class="performance-backdrop" onclick="closePerformanceModal()"></div>
                        <div class="performance-content">
                            <div class="performance-header">
                                <h3>Trading Performance</h3>
                                <button class="perf-close-btn" onclick="closePerformanceModal()">×</button>
                            </div>
                            <div class="performance-body">
                                <div class="perf-grid">
                                    <div class="perf-card">
                                        <div class="perf-label">Today's P&L</div>
                                        <div class="perf-value" id="perfToday">+0.00 USDT</div>
                                    </div>
                                    <div class="perf-card">
                                        <div class="perf-label">7D P&L</div>
                                        <div class="perf-value" id="perfWeek">+0.00 USDT</div>
                                    </div>
                                    <div class="perf-card">
                                        <div class="perf-label">30D P&L</div>
                                        <div class="perf-value" id="perfMonth">+0.00 USDT</div>
                                    </div>
                                    <div class="perf-card">
                                        <div class="perf-label">Total P&L</div>
                                        <div class="perf-value" id="perfTotal">+0.00 USDT</div>
                                    </div>
                                    <div class="perf-card">
                                        <div class="perf-label">Win Rate</div>
                                        <div class="perf-value" id="perfWinRate">0%</div>
                                    </div>
                                    <div class="perf-card">
                                        <div class="perf-label">Total Trades</div>
                                        <div class="perf-value" id="perfTotalTrades">0</div>
                                    </div>
                                    <div class="perf-card">
                                        <div class="perf-label">Total Fees</div>
                                        <div class="perf-value negative" id="perfTotalFees">0.00 USDT</div>
                                    </div>
                                    <div class="perf-card">
                                        <div class="perf-label">Win/Loss</div>
                                        <div class="perf-value" id="perfWinLoss">0W / 0L</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div><!-- /bottom-panel -->

        <!-- 3열: 주문 패널 -->
        <div class="trade-section">
            <!-- Trade 헤더 (바이비트 스타일) -->
            <div class="trade-header">
                <span class="trade-title">Trade</span>
                <div class="trade-header-icons">
                    <button class="trade-icon-btn" title="설정">⚙</button>
                    <button class="trade-icon-btn" title="계산기">🔢</button>
                    <button class="trade-icon-btn" title="더보기">⋮</button>
                </div>
            </div>

            <div class="order-area">
            <!-- 마진/레버리지 -->
            <div class="margin-leverage-row">
                <button class="margin-select-btn">
                    Cross <span class="arrow">▼</span>
                </button>
                <button class="leverage-select-btn">
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
                        <span class="last-btn">Last</span>
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
                        <span class="qty-unit" id="qtyUnitLabel"><?= $baseCoin ?></span>
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
                    <span id="orderMaxSize">-- <?= $baseCoin ?></span>
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
                    <input type="checkbox"> TP/SL
                </label>
                <label class="checkbox-label">
                    <input type="checkbox"> Reduce-Only
                </label>
            </div>

            <!-- 롱/숏 버튼 -->
            <div class="action-buttons">
                <button class="action-btn long">Long</button>
                <button class="action-btn short">Short</button>
            </div>

            <!-- 추가 정보 -->
            <div class="extra-info">
                <span>◎ Fee Rate</span>
                <span>📊 Calculator</span>
            </div>

            <!-- 계좌 정보 -->
            <div class="account-section">
                <div class="account-header">
                    <span>Unified Trading Account</span>
                    <span class="account-icons">⚙ 📊 P&L</span>
                </div>
                <div class="account-row">
                    <span>Margin Mode</span>
                    <span class="link">Cross Margin ></span>
                </div>
                <div class="margin-bars">
                    <div class="margin-bar-row">
                        <span>Initial Margin</span>
                        <div class="bar-container">
                            <div class="bar-fill green" id="initialMarginBar" style="width: 0%"></div>
                        </div>
                        <span id="initialMarginPercent">0.00%</span>
                    </div>
                    <div class="margin-bar-row">
                        <span>Maintenance Margin</span>
                        <div class="bar-container">
                            <div class="bar-fill green" id="maintenanceMarginBar" style="width: 0%"></div>
                        </div>
                        <span id="maintenanceMarginPercent">0.00%</span>
                    </div>
                </div>
                <div class="balance-rows">
                    <div class="balance-row">
                        <span>Margin Balance</span>
                        <span id="marginBalanceDisplay">0.0000 USDT</span>
                    </div>
                    <div class="balance-row">
                        <span>Available Balance</span>
                        <span id="availableBalanceDisplay">0.0000 USDT</span>
                    </div>
                    <div class="balance-row">
                        <span>Unrealized PNL</span>
                        <span id="unrealizedPnlDisplay" class="pnl-value">0.0000 USDT</span>
                    </div>
                </div>
                <div class="account-actions">
                    <button class="acc-btn">Deposit</button>
                    <button class="acc-btn">Convert</button>
                    <button class="acc-btn">Transfer</button>
                </div>
            </div>

            <!-- 계약 상세 -->
            <div class="contract-details">
                <div class="contract-header">
                    <span>Contract Details BTCUSDT</span>
                </div>
                <div class="contract-rows">
                    <div class="contract-row">
                        <span>Expiration Date</span>
                        <span>Perpetual</span>
                    </div>
                    <div class="contract-row">
                        <span>Index Price</span>
                        <span id="indexPrice">Loading...</span>
                    </div>
                    <div class="contract-row">
                        <span>Mark Price</span>
                        <span id="markPrice">Loading...</span>
                    </div>
                </div>
                <button class="show-more">Show ▼</button>
            </div>

            <!-- 실시간 거래 피드 -->
            <div class="trade-feed-container">
                <div class="trade-feed-header">
                    <span class="trade-feed-title">Live Trades</span>
                    <span class="online-users">● <span id="onlineUsers">0</span> Online</span>
                </div>
                <div class="trade-feed-list" id="tradeFeedList">
                    <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 11px;">
                        Waiting for trades...
                    </div>
                </div>
            </div>
            </div><!-- /order-area -->
        </div><!-- /trade-section -->
    </div><!-- /trade-wrapper -->

    <!-- Lightweight Charts with Position/Order Markers -->
    <script>
        // ========================================
        // Lightweight Charts 차트 시스템
        // ========================================
        // PHP에서 URL 심볼 전달 (가장 먼저 설정)
        window.initialSymbol = '<?= $urlSymbol ?>';

        // 현재 심볼 (전역) - 초기화 시점에 바로 설정
        let currentSymbol = window.initialSymbol || 'BTCUSDT';

        // USD/KRW 환율 (PHP에서 전달, 1시간마다 자동 업데이트)
        const USD_KRW = <?php echo $usdKrwRate; ?>;

        let chart = null;
        let candleSeries = null;
        let volumeSeries = null;
        let positionLines = [];
        let orderLines = [];
        let liquidationLine = null;
        let chartMode = 'lightweight'; // 'tradingview' or 'lightweight' - Standard가 마커 지원

        // 차트 초기화
        function initBinanceChart() {
            const chartContainer = document.getElementById('tradingview-chart');
            if (!chartContainer) return;

            if (chartMode === 'tradingview') {
                // 기본 TradingView 위젯 (현재 심볼 사용)
                const tvSymbol = currentSymbol || 'BTCUSDT';
                chartContainer.innerHTML = `
                    <iframe
                        src="https://www.tradingview.com/widgetembed/?frameElementId=tradingview_chart&symbol=BINANCE%3A${tvSymbol}&interval=1&hidesidetoolbar=0&symboledit=0&saveimage=0&toolbarbg=161a1e&studies=%5B%5D&theme=dark&style=1&timezone=Asia%2FSeoul&studies_overrides=%7B%7D&overrides=%7B%7D&enabled_features=%5B%5D&disabled_features=%5B%5D&locale=kr&utm_source=&utm_medium=widget&utm_campaign=chart&utm_term=BINANCE%3A${tvSymbol}"
                        style="width:100%;height:100%;border:none;"
                        allowtransparency="true"
                        scrolling="no"
                        allowfullscreen>
                    </iframe>
                `;
            } else {
                // Lightweight Charts (커스텀 마커 지원)
                initLightweightChart(chartContainer);
            }
        }

        // Lightweight Charts 초기화
        function initLightweightChart(container) {
            container.innerHTML = '';

            chart = LightweightCharts.createChart(container, {
                width: container.clientWidth,
                height: container.clientHeight,
                layout: {
                    background: { type: 'solid', color: '#0b0e11' },
                    textColor: '#848e9c',
                },
                grid: {
                    vertLines: { color: '#1e2329' },
                    horzLines: { color: '#1e2329' },
                },
                crosshair: {
                    mode: LightweightCharts.CrosshairMode.Normal,
                },
                rightPriceScale: {
                    borderColor: '#2b3139',
                },
                timeScale: {
                    borderColor: '#2b3139',
                    timeVisible: true,
                    secondsVisible: false,
                },
            });

            // 캔들스틱 시리즈 (심볼별 소수점 설정)
            const chartDecimals = getPriceDecimals(currentSymbol);
            candleSeries = chart.addCandlestickSeries({
                upColor: '#0ecb81',
                downColor: '#f6465d',
                borderUpColor: '#0ecb81',
                borderDownColor: '#f6465d',
                wickUpColor: '#0ecb81',
                wickDownColor: '#f6465d',
                priceFormat: {
                    type: 'price',
                    precision: chartDecimals,
                    minMove: Math.pow(10, -chartDecimals),
                },
            });

            // 볼륨 시리즈 (별도의 priceScale 사용)
            volumeSeries = chart.addHistogramSeries({
                color: '#26a69a',
                priceFormat: { type: 'volume' },
                priceScaleId: 'volume',
            });

            // 볼륨 스케일 설정 (하단 15% 영역에만 표시)
            chart.priceScale('volume').applyOptions({
                scaleMargins: { top: 0.88, bottom: 0 },
            });

            // 캔들스틱 스케일 설정 (상단 75% 영역, 볼륨과 완전 분리)
            candleSeries.priceScale().applyOptions({
                scaleMargins: { top: 0.02, bottom: 0.25 },
            });

            // Socket.IO에서 캔들 데이터 요청 (초기 데이터는 서버에서 받음)

            // 반응형 리사이즈
            const resizeObserver = new ResizeObserver(entries => {
                if (chart) {
                    chart.applyOptions({
                        width: container.clientWidth,
                        height: container.clientHeight
                    });
                }
            });
            resizeObserver.observe(container);
        }

        // 캔들 데이터 설정 (Socket.IO 서버에서 받은 데이터)
        let currentInterval = '1m';

        function setCandleData(data) {
            if (!data || !Array.isArray(data)) return;

            const candles = data.map(d => ({
                time: d.time || d[0] / 1000,
                open: parseFloat(d.open || d[1]),
                high: parseFloat(d.high || d[2]),
                low: parseFloat(d.low || d[3]),
                close: parseFloat(d.close || d[4]),
            }));

            const volumes = data.map(d => ({
                time: d.time || d[0] / 1000,
                value: parseFloat(d.volume || d[5]),
                color: parseFloat(d.close || d[4]) >= parseFloat(d.open || d[1]) ? 'rgba(14, 203, 129, 0.5)' : 'rgba(246, 70, 93, 0.5)',
            }));

            if (candleSeries) candleSeries.setData(candles);
            if (volumeSeries) volumeSeries.setData(volumes);

            // 포지션/주문 라인 업데이트
            updateChartMarkers();
        }

        // 캔들 실시간 업데이트 (Socket.IO 서버에서 받은 데이터)
        function updateCandle(kline) {
            if (!kline) return;

            const closePrice = parseFloat(kline.close || kline.c);

            // 차트 업데이트
            if (candleSeries) {
                candleSeries.update({
                    time: kline.time || kline.t / 1000,
                    open: parseFloat(kline.open || kline.o),
                    high: parseFloat(kline.high || kline.h),
                    low: parseFloat(kline.low || kline.l),
                    close: closePrice,
                });
            }

            if (volumeSeries) {
                volumeSeries.update({
                    time: kline.time || kline.t / 1000,
                    value: parseFloat(kline.volume || kline.v),
                    color: closePrice >= parseFloat(kline.open || kline.o) ? 'rgba(14, 203, 129, 0.5)' : 'rgba(246, 70, 93, 0.5)',
                });
            }
        }

        // 차트 마커 업데이트 (포지션, 청산라인, 주문)
        // userPositions, userOrders 변수 사용 (데이터 기반)
        function updateChartMarkers() {
            if (!candleSeries || chartMode !== 'lightweight') return;

            // 기존 라인 제거
            positionLines.forEach(line => {
                try { candleSeries.removePriceLine(line); } catch(e) {}
            });
            orderLines.forEach(line => {
                try { candleSeries.removePriceLine(line); } catch(e) {}
            });
            if (liquidationLine) {
                try { candleSeries.removePriceLine(liquidationLine); } catch(e) {}
            }

            positionLines = [];
            orderLines = [];
            liquidationLine = null;

            // 포지션 라인 추가 (userPositions 데이터 사용)
            if (typeof userPositions !== 'undefined' && userPositions.length > 0) {
                userPositions.forEach(pos => {
                    const entryPrice = parseFloat(pos.entry_price);
                    const liqPrice = parseFloat(pos.liquidation_price);
                    const isLong = pos.side === 'long';
                    const size = formatQty(parseFloat(pos.size), pos.symbol);

                    if (entryPrice && !isNaN(entryPrice)) {
                        // 진입가 라인 (굵은 실선)
                        const entryLine = candleSeries.createPriceLine({
                            price: entryPrice,
                            color: isLong ? '#0ecb81' : '#f6465d',
                            lineWidth: 2,
                            lineStyle: LightweightCharts.LineStyle.Solid,
                            axisLabelVisible: true,
                            title: `${isLong ? 'Long' : 'Short'} ${size}`,
                        });
                        positionLines.push(entryLine);
                    }

                    if (liqPrice && !isNaN(liqPrice) && liqPrice > 0) {
                        // 청산가 라인 (빨간 점선)
                        const liqLine = candleSeries.createPriceLine({
                            price: liqPrice,
                            color: '#ff4444',
                            lineWidth: 1,
                            lineStyle: LightweightCharts.LineStyle.Dashed,
                            axisLabelVisible: true,
                            title: 'Liq ' + formatPrice(liqPrice, currentSymbol),
                        });
                        positionLines.push(liqLine);
                    }
                });
            }

            // 주문 라인 추가 (userOrders 데이터 사용)
            if (typeof userOrders !== 'undefined') {
                // 매수 주문 (bids)
                userOrders.bids.forEach(order => {
                    const price = parseFloat(order.price);
                    const qty = parseFloat(order.qty || order.quantity || 0);
                    if (price && !isNaN(price)) {
                        const orderLine = candleSeries.createPriceLine({
                            price: price,
                            color: '#0ecb81',
                            lineWidth: 1,
                            lineStyle: LightweightCharts.LineStyle.Dotted,
                            axisLabelVisible: true,
                            title: `Buy ${formatQty(qty, currentSymbol)}`,
                        });
                        orderLines.push(orderLine);
                    }
                });

                // 매도 주문 (asks)
                userOrders.asks.forEach(order => {
                    const price = parseFloat(order.price);
                    const qty = parseFloat(order.qty || order.quantity || 0);
                    if (price && !isNaN(price)) {
                        const orderLine = candleSeries.createPriceLine({
                            price: price,
                            color: '#f6465d',
                            lineWidth: 1,
                            lineStyle: LightweightCharts.LineStyle.Dotted,
                            axisLabelVisible: true,
                            title: `Sell ${formatQty(qty, currentSymbol)}`,
                        });
                        orderLines.push(orderLine);
                    }
                });
            }
        }

        // 차트 모드 전환
        function toggleChartMode() {
            chartMode = chartMode === 'tradingview' ? 'lightweight' : 'tradingview';
            initBinanceChart();

            // 버튼 상태 업데이트
            document.querySelectorAll('.chart-tabs-right span').forEach(span => {
                span.classList.remove('active');
                if ((span.textContent === 'TradingView' && chartMode === 'tradingview') ||
                    (span.textContent === 'Standard' && chartMode === 'lightweight')) {
                    span.classList.add('active');
                }
            });
        }

        // DOM 로드 후 차트 초기화
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initBinanceChart();
                setupChartModeToggle();
                fetchOpenInterest(); // 펀딩레이트 & OI 초기 로드
                setupPnlTabs(); // P&L 탭 이벤트 설정
            });
        } else {
            initBinanceChart();
            setupChartModeToggle();
            fetchOpenInterest(); // 펀딩레이트 & OI 초기 로드
            setupPnlTabs(); // P&L 탭 이벤트 설정
        }

        // 차트 모드 토글 설정
        function setupChartModeToggle() {
            document.querySelectorAll('.chart-tabs-right span').forEach(span => {
                span.style.cursor = 'pointer';
                span.addEventListener('click', function() {
                    const text = this.textContent;
                    if (text === 'Standard') {
                        chartMode = 'lightweight';
                    } else if (text === 'TradingView') {
                        chartMode = 'tradingview';
                    }

                    document.querySelectorAll('.chart-tabs-right span').forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    initBinanceChart();
                });
            });
        }

        // ========================================
        // Socket.IO 실시간 데이터
        // ========================================
        const SOCKET_SERVER = window.FuturXConfig?.wsServerUrl || 'http://localhost:3002';
        let socket = null;

        // 심볼별 소수점 자리수 (단일 소스: DB symbols -> window.FuturXConfig.*DecimalsBySymbol)
        const PRICE_DECIMALS = window.FuturXConfig?.priceDecimalsBySymbol || {};
        const QTY_DECIMALS = window.FuturXConfig?.qtyDecimalsBySymbol || {};
        function getPriceDecimals(symbol) {
            return Number(PRICE_DECIMALS[symbol]) || 2;
        }
        function getQtyDecimals(symbol) {
            return Number(QTY_DECIMALS[symbol]) || 3;
        }

        // ========================================
        // 공통 숫자 포맷 함수 (USD_KRW는 677줄에서 PHP로 설정됨)
        // ========================================

        // 가격 포맷팅 (심볼별 소수점)
        function formatPrice(price, symbol = null) {
            const sym = symbol || currentSymbol;
            if (window.FuturX?.formatPrice) return window.FuturX.formatPrice(price, sym);
            const p = parseFloat(price);
            if (isNaN(p)) return '-';
            const decimals = getPriceDecimals(sym);
            return p.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        // 일반 숫자 포맷팅 (천단위 콤마)
        function formatNum(num, decimals = 2) {
            const n = parseFloat(num);
            if (isNaN(n)) return '-';
            return n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        // 수량 포맷팅 (심볼별 소수점, 표시용: 천단위 포함)
        function formatQty(qty, symbol = null) {
            const sym = symbol || currentSymbol;
            if (window.FuturX?.formatQty) return window.FuturX.formatQty(qty, sym);
            const q = parseFloat(qty);
            if (isNaN(q)) return '-';
            const decimals = getQtyDecimals(sym);
            return q.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        // 수량 포맷팅 (입력/페이로드용: 콤마 없이 고정 소수)
        function formatQtyPlain(qty, symbol = null) {
            const sym = symbol || currentSymbol;
            const q = parseFloat(qty);
            if (isNaN(q)) return '';
            return q.toFixed(getQtyDecimals(sym));
        }

        // KRW 포맷팅 (정수, 원)
        function formatKrw(usd) {
            const krw = parseFloat(usd) * USD_KRW;
            if (isNaN(krw)) return '-';
            return Math.round(krw).toLocaleString('ko-KR');
        }

        // P&L 포맷팅 (+/- 부호 포함)
        function formatPnl(pnl, decimals = 2) {
            const p = parseFloat(pnl);
            if (isNaN(p)) return '-';
            const sign = p >= 0 ? '+' : '';
            return sign + p.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        // 수수료 포맷팅 (4자리)
        function formatFee(fee) {
            const f = parseFloat(fee);
            if (isNaN(f)) return '0.0000';
            return f.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
        }

        let currentPrice = 0;
        let previousPrice = 0;
        let indexPrice = 0;
        let allSymbolPrices = {}; // 모든 심볼의 최신 가격 저장
        let high24h = 0;
        let low24h = 0;
        let turnover = 0;
        let fundingRate = 0.0072;
        let fundingCountdown = 4 * 3600 + 23 * 60 + 26;
        let openInterest = 0;
        let initialMargin = 89.13;
        let maintenanceMargin = 35.65;
        let marginBalance = 0.3044;
        let availableBalance = 0.0330;

        // 오더북 데이터
        let asksData = [];
        let bidsData = [];
        let rawAsksData = [];  // 원본 데이터 (정밀도 적용 전)
        let rawBidsData = [];

        // 오더북 설정
        let obPrecision = 0.1;  // 가격 정밀도
        let obViewMode = 'both'; // 'both', 'bids', 'asks'

        // Recent Trades 데이터
        let recentTrades = [];

        // 사용자 주문 (내부 처리용)
        let userOrders = {
            asks: [], // 사용자 매도 주문
            bids: []  // 사용자 매수 주문
        };

        // ========================================
        // Socket.IO 연결
        // ========================================
        function connectSocketIO() {
            socket = io(SOCKET_SERVER, {
                transports: ['websocket', 'polling'],
                reconnection: true,
                reconnectionAttempts: Infinity,
                reconnectionDelay: 1000,
                reconnectionDelayMax: 5000
            });

            socket.on('connect', () => {
                console.log('Socket.IO Connected:', socket.id);
                updateConnectionStatus(true);

                // 브라우저 정보 (로그인 여부 상관없이 전송)
                const browserInfo = {
                    userAgent: navigator.userAgent,
                    screenSize: `${window.screen.width}x${window.screen.height}`,
                    language: navigator.language,
                    currentPage: 'trade'
                };

                // 사용자 인증 전송 (로그인 상태면 유저 정보 포함)
                if (authToken && currentUser) {
                    socket.emit('auth', {
                        userId: currentUser.id,
                        email: currentUser.email,
                        nickname: currentUser.nickname || currentUser.email,
                        token: authToken,
                        ...browserInfo
                    });
                } else {
                    // 비로그인 상태도 브라우저 정보 전송
                    socket.emit('auth', browserInfo);
                }

                // 심볼 목록 및 가격 요청 (드롭다운용)
                socket.emit('get:symbols');

                // 항상 현재 심볼로 switch:symbol 요청 (초기 klines 데이터 로드를 위해)
                console.log('[Init] Switching to symbol:', currentSymbol);
                socket.emit('switch:symbol', { symbol: currentSymbol });
            });

            socket.on('disconnect', () => {
                console.log('Socket.IO Disconnected');
                updateConnectionStatus(false);
            });

            socket.on('connect_error', (error) => {
                console.error('Socket.IO Connection Error:', error);
                updateConnectionStatus(false);
            });

            // 오더북 데이터 수신
            socket.on('depth', (data) => {
                processOrderbookData(data);
            });

            // 티커 데이터 수신 (24h 통계)
            socket.on('ticker', (data) => {
                processTickerData(data);
            });

            // 티커 업데이트 (서버에서 ticker:update로 보냄)
            socket.on('ticker:update', (data) => {
                processTickerData(data);
            });

            // 체결 데이터 수신
            socket.on('trade', (data) => {
                processTradeData(data);
            });

            // 가격 데이터 수신
            socket.on('price', (data) => {
                // 가격 업데이트만 처리 (티커와 별도)
                const price = parseFloat(data.price);
                if (price > 0 && Math.abs(price - currentPrice) > 0.01) {
                    previousPrice = currentPrice;
                    currentPrice = price;
                    updatePriceDisplay();
                }
            });

            // 초기 데이터 수신
            socket.on('init', (data) => {
                console.log('[WS] Init data received:', data);

                // 온라인 유저 수
                if (data.onlineCount) {
                    const el = document.getElementById('onlineUsers');
                    if (el) el.textContent = data.onlineCount;
                }

                // 초기 가격
                if (data.price && data.price > 0) {
                    currentPrice = data.price;
                    updatePriceDisplay();
                }

                // 초기 호가창
                if (data.orderbook && data.orderbook.bids && data.orderbook.asks) {
                    const wsAsks = data.orderbook.asks.map(item => [item.price.toString(), item.quantity.toString()]);
                    const wsBids = data.orderbook.bids.map(item => [item.price.toString(), item.quantity.toString()]);
                    processOrderbookData({ asks: wsAsks, bids: wsBids });
                }

                // 초기 Recent Trades
                if (data.recentTrades && Array.isArray(data.recentTrades)) {
                    setRecentTrades(data.recentTrades);
                }

                // 24h 통계
                if (data.ticker) {
                    high24h = data.ticker.high || high24h;
                    low24h = data.ticker.low || low24h;
                    turnover = data.ticker.quoteVolume || turnover;
                    updateStats(true, 0);
                }
            });

            // 실시간 가격 업데이트
            socket.on('price:update', (data) => {
                if (data.price && data.price > 0) {
                    previousPrice = currentPrice;
                    currentPrice = data.price;
                    updatePriceDisplay();
                }
            });

            // 포지션 심볼 실시간 가격 업데이트 (관리자 설정 간격 적용)
            socket.on('position:price', (data) => {
                if (!data.symbol || !data.price) return;
                // 전역 가격 저장소 업데이트
                allSymbolPrices[data.symbol] = data.price;
                // 해당 심볼 포지션만 실시간 업데이트
                updateSinglePositionPrice(data.symbol, data.price);
            });

            // 유저 거래 피드
            socket.on('trade:user', (trade) => {
                showTradeFeed(trade);
            });

            // 청산 알림
            socket.on('alert:liquidation', (data) => {
                showLiquidationAlert(data);
            });

            // 잔고 업데이트
            socket.on('balance:updated', (data) => {
                if (data.available_balance !== undefined) {
                    userAvailableBalance = parseFloat(data.available_balance);
                    updateBalanceDisplay();
                }
                // 잔고 업데이트 시 데이터 새로고침
                loadUserBalance();
                loadUserOrders();
                loadUserPositions();
            });

            // 계정 정보 통합 업데이트 (WebSocket 실시간 푸시)
            socket.on('account:update', (data) => {
                console.log('[WS] Account update received:', data);

                // 잔고 업데이트
                if (data.balance !== undefined) {
                    userBalance = parseFloat(data.balance);
                }
                if (data.availableBalance !== undefined) {
                    userAvailableBalance = parseFloat(data.availableBalance);
                }
                if (data.marginBalance !== undefined) {
                    userMarginBalance = parseFloat(data.marginBalance);
                }
                if (data.unrealizedPnl !== undefined) {
                    userUnrealizedPnl = parseFloat(data.unrealizedPnl);
                }

                // UI 업데이트
                updateBalanceDisplay();
                updateOrderInfo();

                // 포지션 데이터가 있으면 업데이트
                if (data.positions && Array.isArray(data.positions)) {
                    renderPositions(data.positions);
                }
            });

            // 지정가 주문 체결 알림 (본인 주문)
            socket.on('order:my_filled', (data) => {
                // 체결음 재생
                playOrderFilledSound();

                // 토스트 알림
                const pnlText = data.realized_pnl ?
                    (data.realized_pnl >= 0 ? `+$${data.realized_pnl.toFixed(2)}` : `-$${Math.abs(data.realized_pnl).toFixed(2)}`) : '';

                const message = `$${parseFloat(data.fill_price).toLocaleString()}에 체결되었습니다.${pnlText ? ` (${pnlText})` : ''}`;
                showToast(message, data.realized_pnl >= 0 ? 'success' : 'warning');

                // 데이터 새로고침
                loadUserOrders();
                loadUserPositions();
                loadUserBalance();
            });

            // 호가창 업데이트
            socket.on('orderbook:update', (data) => {
                if (data.bids && data.asks) {
                    const wsAsks = data.asks.map(item => [item.price.toString(), item.quantity.toString()]);
                    const wsBids = data.bids.map(item => [item.price.toString(), item.quantity.toString()]);
                    processOrderbookData({ asks: wsAsks, bids: wsBids });
                }
            });

            // 연결된 유저 수
            socket.on('users:count', (count) => {
                const el = document.getElementById('onlineUsers');
                if (el) el.textContent = count;
            });

            // ========================================
            // 차트 데이터 수신 (노드 서버에서만 받음)
            // ========================================

            // 초기 캔들 데이터
            socket.on('klines', (data) => {
                console.log('[WS] Klines data received:', data?.length);
                setCandleData(data);
            });

            // 실시간 캔들 업데이트
            socket.on('kline', (data) => {
                // 현재 심볼의 kline만 차트에 반영
                if (data.symbol && data.symbol !== currentSymbol) return;
                updateCandle(data);
            });

            // ========================================
            // Recent Trades 데이터 수신 (노드 서버에서만 받음)
            // ========================================

            // 초기 Recent Trades 데이터
            socket.on('trades', (data) => {
                console.log('[WS] Recent trades received:', data?.length);
                setRecentTrades(data);
            });

            // 실시간 체결 (Recent Trades 추가 + 가격 업데이트)
            socket.on('trade:new', (data) => {
                addRecentTrade(data);
            });

            // 마크가격 + 펀딩비율 (Node 서버에서 수신)
            socket.on('markPrice', (data) => {
                // 마크가격 업데이트
                if (data.markPrice) {
                    markPrice = data.markPrice;
                    const markPriceEl = document.getElementById('markPrice');
                    if (markPriceEl) {
                        markPriceEl.textContent = formatPrice(markPrice, currentSymbol);
                    }
                }

                // 인덱스 가격 업데이트
                if (data.indexPrice) {
                    indexPrice = data.indexPrice;
                    const indexEl = document.getElementById('indexPrice');
                    if (indexEl) {
                        indexEl.textContent = formatPrice(indexPrice, currentSymbol);
                    }
                }

                // 펀딩비율 업데이트
                if (data.fundingRate !== undefined) {
                    fundingRate = data.fundingRate * 100; // 퍼센트로 변환
                    updateFundingRateUI();
                }

                // 다음 펀딩 시간
                if (data.nextFundingTime) {
                    const now = Date.now();
                    fundingCountdown = Math.max(0, Math.floor((data.nextFundingTime - now) / 1000));
                }
            });

            // ========================================
            // 멀티 심볼 지원 이벤트
            // ========================================

            // 심볼별 실시간 가격 업데이트 (드롭다운 목록용)
            socket.on('symbol:price', (data) => {
                updateSymbolListPrice(data.symbol, data.price, data.priceChangePercent);
            });

            // 심볼 변경 완료
            socket.on('symbol:switched', (data) => {
                console.log('[WS] Symbol switched:', data.symbol);
                console.log('[WS] Orderbook data:', data.orderbook?.bids?.length, 'bids,', data.orderbook?.asks?.length, 'asks');
                currentSymbol = data.symbol;

                // 1. 먼저 코인명 라벨과 정밀도 업데이트 (오더북 처리 전에!)
                updateCoinLabels(data.symbol);

                // 2. 차트 priceFormat 업데이트 (심볼별 소수점)
                if (candleSeries) {
                    const newDecimals = getPriceDecimals(data.symbol);
                    candleSeries.applyOptions({
                        priceFormat: {
                            type: 'price',
                            precision: newDecimals,
                            minMove: Math.pow(10, -newDecimals),
                        },
                    });
                }

                // 3. 차트 데이터 업데이트
                if (data.klines && data.klines.length > 0) {
                    setCandleData(data.klines);
                }

                // 3. 초기 오더북 업데이트
                if (data.orderbook && data.orderbook.bids && data.orderbook.asks) {
                    console.log('[WS] Processing orderbook, first bid:', data.orderbook.bids[0], 'first ask:', data.orderbook.asks[0]);

                    // 오더북 처리 전에 현재 가격 업데이트 (중간 가격 사용)
                    const midPrice = (data.orderbook.bids[0].price + data.orderbook.asks[0].price) / 2;
                    if (midPrice > 0) {
                        currentPrice = midPrice;
                        console.log('[WS] Updated currentPrice from orderbook:', currentPrice);
                    }

                    const wsAsks = data.orderbook.asks.map(item => [item.price.toString(), item.quantity.toString()]);
                    const wsBids = data.orderbook.bids.map(item => [item.price.toString(), item.quantity.toString()]);
                    processOrderbookData({ asks: wsAsks, bids: wsBids });
                } else {
                    console.warn('[WS] No orderbook data received!');
                }

                // 4. 심볼 데이터 업데이트
                if (data.symbolData) {
                    if (data.symbolData.markPrice) {
                        markPrice = data.symbolData.markPrice;
                        const markPriceEl = document.getElementById('markPrice');
                        if (markPriceEl) markPriceEl.textContent = formatPrice(markPrice, currentSymbol);
                    }
                    if (data.symbolData.indexPrice) {
                        indexPrice = data.symbolData.indexPrice;
                        const indexEl = document.getElementById('indexPrice');
                        if (indexEl) indexEl.textContent = formatPrice(indexPrice, currentSymbol);
                    }
                    // 가격 업데이트 (price 또는 ticker.lastPrice)
                    const newPrice = data.symbolData.price || data.symbolData.ticker?.lastPrice;
                    if (newPrice) {
                        currentPrice = newPrice;
                        updatePriceDisplay();
                    }
                    // 24h 통계 업데이트
                    if (data.symbolData.ticker) {
                        const ticker = data.symbolData.ticker;
                        high24h = ticker.high || high24h;
                        low24h = ticker.low || low24h;
                        turnover = ticker.quoteVolume || turnover;
                        const priceChange = ticker.priceChange || 0;
                        const priceChangePercent = ticker.priceChangePercent || 0;
                        updateStats(priceChange >= 0, priceChange, priceChangePercent);
                    }
                }

                // 5. UI 업데이트 (popstate에서 온 경우 URL 업데이트 건너뛰기)
                const skipUrl = window._skipUrlUpdate || false;
                window._skipUrlUpdate = false; // 플래그 리셋
                updateSymbolUI(data.symbol, !skipUrl);
                showToast(`${data.symbol} 차트로 전환되었습니다.`, 'info');
            });

            // 활성 심볼 변경 알림 (다른 클라이언트가 변경했을 때)
            socket.on('active:symbol', (data) => {
                console.log('[WS] Active symbol changed:', data.symbol);
                // 현재 보고있는 심볼과 같으면 무시
                if (currentSymbol === data.symbol) return;
                // 다른 사용자가 변경한 경우 알림만 표시 (자동 전환하지 않음)
            });

            // 심볼 목록 응답
            socket.on('symbols:list', (data) => {
                console.log('[WS] Symbols list received:', data.symbols?.length);
                if (data.symbols) {
                    data.symbols.forEach(sym => {
                        updateSymbolListPrice(sym.symbol, sym.price, sym.priceChangePercent);
                    });
                }
            });
        }

        // 연결 상태 표시
        function updateConnectionStatus(connected) {
            const statusEl = document.querySelector('.symbol-badge');
            const overlay = document.getElementById('connectionOverlay');

            if (overlay) {
                if (connected) {
                    // 연결 성공 - 오버레이 숨김 (페이드 아웃)
                    overlay.classList.add('hidden');
                    setTimeout(() => {
                        overlay.style.display = 'none';
                    }, 300);
                } else {
                    // 연결 끊김 - 오버레이 표시
                    overlay.style.display = 'flex';
                    overlay.querySelector('.connection-text').textContent = '연결이 끊어졌습니다';
                    overlay.querySelector('.connection-subtext').textContent = '재연결 시도중...';
                    setTimeout(() => {
                        overlay.classList.remove('hidden');
                    }, 10);
                }
            }

            if (statusEl) {
                if (connected) {
                    statusEl.style.background = 'rgba(14, 203, 129, 0.2)';
                    statusEl.style.color = '#0ecb81';
                    statusEl.textContent = 'USDT Perpetual';
                } else {
                    statusEl.style.background = 'rgba(246, 70, 93, 0.2)';
                    statusEl.style.color = '#f6465d';
                    statusEl.textContent = '연결 끊김';
                }
            }
        }

        // 가격 표시 업데이트
        function updatePriceDisplay() {
            const isUp = currentPrice >= previousPrice;
            const priceEl = document.getElementById('mainPrice');
            const spreadPriceEl = document.getElementById('spreadPrice');

            if (priceEl) {
                priceEl.textContent = formatPrice(currentPrice, currentSymbol);
                priceEl.className = `current-price ${isUp ? 'up flash-green' : 'down flash-red'}`;

                setTimeout(() => {
                    priceEl.className = `current-price ${isUp ? 'up' : 'down'}`;
                }, 300);
            }

            if (spreadPriceEl) {
                spreadPriceEl.textContent = formatPrice(currentPrice, currentSymbol);
                spreadPriceEl.className = `spread-price ${isUp ? 'up' : 'down'}`;
            }

            // 스프레드 마크 업데이트
            const spreadMarkEl = document.getElementById('spreadMark');
            if (spreadMarkEl) {
                spreadMarkEl.innerHTML = `${isUp ? '▲' : '▼'} ${formatPrice(currentPrice, currentSymbol)}`;
            }

            // 주문 입력창 업데이트 (Last 버튼이 활성화된 경우에만)
            const priceInput = document.getElementById('priceInput');
            const lastBtn = document.querySelector('.last-btn');
            if (priceInput && lastBtn && lastBtn.classList.contains('active')) {
                priceInput.value = formatPrice(currentPrice, currentSymbol);
            }

            // 포지션 실시간 업데이트는 position:price 이벤트로 통합 (관리자 설정 간격 적용)
        }

        // ========================================
        // 오더북 데이터 처리
        // ========================================
        function processOrderbookData(data) {
            // 원본 데이터 저장 (모든 정밀도에서 사용)
            rawAsksData = data.asks || [];
            rawBidsData = data.bids || [];

            // 정밀도 적용하여 데이터 가공
            applyPrecisionAndRender();
        }

        // 정밀도 적용 및 렌더링
        function applyPrecisionAndRender() {
            // 모든 정밀도에서 원본 데이터 기준으로 그룹화
            asksData = groupByPrecision(rawAsksData, obPrecision, 'asks');
            bidsData = groupByPrecision(rawBidsData, obPrecision, 'bids');

            // 사용자 주문 합산 (B-Book 방식)
            mergeUserOrders();

            // 누적 합계 계산
            calculateTotals();

            renderOrderbook();
            updateDepthBar();
        }

        // 가격 정밀도에 따라 그룹화
        function groupByPrecision(data, precision, side) {
            if (!data || data.length === 0) return [];

            const grouped = new Map();

            data.forEach(item => {
                const price = parseFloat(item[0]);
                const qty = parseFloat(item[1]);

                if (isNaN(price) || isNaN(qty)) return;

                // 정밀도에 맞게 가격 반올림
                const groupedPrice = side === 'asks'
                    ? Math.ceil(price / precision) * precision
                    : Math.floor(price / precision) * precision;

                if (grouped.has(groupedPrice)) {
                    grouped.get(groupedPrice).qty += qty;
                } else {
                    grouped.set(groupedPrice, {
                        price: groupedPrice,
                        qty: qty,
                        total: 0,
                        isUser: false
                    });
                }
            });

            // 뷰 모드에 따라 최대 표시 행 수 결정
            let maxRows = 15;
            if (obViewMode === 'bids' && side === 'bids') maxRows = 30;
            if (obViewMode === 'asks' && side === 'asks') maxRows = 30;

            // 빈 가격대 채우기 (모든 정밀도에서)
            if (currentPrice > 0) {
                // 정밀도에 맞게 소수점 자릿수 계산
                const decimalPlaces = precision < 1 ? Math.abs(Math.floor(Math.log10(precision))) : 0;
                const basePrice = parseFloat((Math.round(currentPrice / precision) * precision).toFixed(decimalPlaces));
                const avgQty = grouped.size > 0
                    ? Array.from(grouped.values()).reduce((sum, v) => sum + v.qty, 0) / grouped.size
                    : 1;

                for (let i = 1; i <= maxRows; i++) {
                    let targetPrice;
                    if (side === 'asks') {
                        targetPrice = parseFloat((basePrice + (i * precision)).toFixed(decimalPlaces));
                    } else {
                        targetPrice = parseFloat((basePrice - (i * precision)).toFixed(decimalPlaces));
                    }

                    if (targetPrice > 0 && !grouped.has(targetPrice)) {
                        // 랜덤 수량 생성 (평균의 0.5~1.5배)
                        const randomQty = avgQty * (0.5 + Math.random());
                        grouped.set(targetPrice, {
                            price: targetPrice,
                            qty: randomQty,
                            total: 0,
                            isUser: false
                        });
                    }
                }
            }

            // 배열로 변환 후 정렬
            let result = Array.from(grouped.values());

            if (side === 'asks') {
                result.sort((a, b) => a.price - b.price); // 오름차순 후 역순 표시
                result = result.slice(0, maxRows).reverse();
            } else {
                result.sort((a, b) => b.price - a.price); // 내림차순
                result = result.slice(0, maxRows);
            }

            return result;
        }

        // ========================================
        // 사용자 주문 오더북에 합산
        // ========================================
        function mergeUserOrders() {
            // 사용자 매도 주문 합산
            userOrders.asks.forEach(userOrder => {
                const existingIdx = asksData.findIndex(a => Math.abs(a.price - userOrder.price) < 0.01);
                if (existingIdx !== -1) {
                    asksData[existingIdx].qty += userOrder.qty;
                    asksData[existingIdx].isUser = true;
                } else {
                    // 적절한 위치에 삽입
                    const insertIdx = asksData.findIndex(a => a.price > userOrder.price);
                    if (insertIdx !== -1) {
                        asksData.splice(insertIdx, 0, { ...userOrder, isUser: true });
                    }
                }
            });

            // 사용자 매수 주문 합산
            userOrders.bids.forEach(userOrder => {
                const existingIdx = bidsData.findIndex(b => Math.abs(b.price - userOrder.price) < 0.01);
                if (existingIdx !== -1) {
                    bidsData[existingIdx].qty += userOrder.qty;
                    bidsData[existingIdx].isUser = true;
                } else {
                    const insertIdx = bidsData.findIndex(b => b.price < userOrder.price);
                    if (insertIdx !== -1) {
                        bidsData.splice(insertIdx, 0, { ...userOrder, isUser: true });
                    }
                }
            });
        }

        // ========================================
        // 누적 합계 계산
        // ========================================
        function calculateTotals() {
            let askTotal = 0;
            for (let i = asksData.length - 1; i >= 0; i--) {
                askTotal += asksData[i].qty;
                asksData[i].total = askTotal;
            }

            let bidTotal = 0;
            for (let i = 0; i < bidsData.length; i++) {
                bidTotal += bidsData[i].qty;
                bidsData[i].total = bidTotal;
            }
        }

        // ========================================
        // 티커 데이터 처리 (가격, 24h 정보)
        // ========================================
        function processTickerData(data) {
            // Socket.IO 서버에서 오는 형식: { high, low, volume, quoteVolume, priceChange, priceChangePercent, lastPrice }
            previousPrice = currentPrice;
            currentPrice = parseFloat(data.lastPrice || data.c || currentPrice);
            high24h = parseFloat(data.high || data.highPrice || data.h || high24h);
            low24h = parseFloat(data.low || data.lowPrice || data.l || low24h);
            turnover = parseFloat(data.quoteVolume || data.q || turnover);

            // 현재 심볼의 가격을 전역 저장소에 저장 (포지션 PNL 계산용)
            if (currentSymbol && currentPrice > 0) {
                allSymbolPrices[currentSymbol] = currentPrice;
            }

            // priceChange와 priceChangePercent
            const priceChange = parseFloat(data.priceChange || data.p || 0);
            const priceChangePercent = parseFloat(data.priceChangePercent || data.P || 0);
            const isUp = priceChange >= 0;

            updatePriceDisplay();
            updateStats(isUp, priceChange, priceChangePercent);

            // 포지션 PNL 실시간 업데이트는 position:price 이벤트로 통합 (관리자 설정 간격 적용)
        }

        // ========================================
        // 체결 데이터 처리
        // ========================================
        function processTradeData(data) {
            // Socket.IO 서버 형식에 맞게 처리
            const trade = {
                price: parseFloat(data.price || data.p || 0),
                qty: parseFloat(data.qty || data.q || 0),
                side: data.isBuy ? 'buy' : 'sell',
                time: data.time || formatCurrentTime()
            };

            recentTrades.unshift(trade);

            // 최대 50개 유지
            if (recentTrades.length > 50) {
                recentTrades.pop();
            }

            renderRecentTrades();
        }

        function formatCurrentTime() {
            const now = new Date();
            return `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}`;
        }

        // ========================================
        // Recent Trades 설정 (Socket.IO 서버에서 받은 데이터)
        // ========================================
        function setRecentTrades(trades) {
            if (!trades || !Array.isArray(trades)) return;

            console.log('[DEBUG] setRecentTrades raw data:', trades.slice(0, 2));

            recentTrades = trades.map(t => {
                const price = parseFloat(t.price || t.p) || 0;
                const qty = parseFloat(t.qty || t.quantity || t.q) || 0;
                const side = t.side || (t.isBuyerMaker || t.m ? 'sell' : 'buy');

                // time 처리: 숫자면 timestamp로 변환, 문자열이면 그대로
                let time;
                if (typeof t.time === 'number' && t.time > 1000000000000) {
                    time = formatTimestamp(t.time);
                } else if (typeof t.time === 'string' && t.time.includes(':')) {
                    time = t.time;
                } else if (t.T) {
                    time = formatTimestamp(t.T);
                } else {
                    time = formatTimestamp(Date.now());
                }

                return { price, qty, side, time };
            });

            renderRecentTrades();
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}:${date.getSeconds().toString().padStart(2, '0')}`;
        }

        // 실시간 체결 추가 (Socket.IO 서버에서 받은 데이터)
        function addRecentTrade(trade) {
            if (!trade) return;

            const price = parseFloat(trade.price || trade.p) || 0;
            const qty = parseFloat(trade.qty || trade.quantity || trade.q) || 0;
            const side = trade.side || (trade.isBuyerMaker || trade.m ? 'sell' : 'buy');

            // time 처리
            let time;
            if (typeof trade.time === 'number' && trade.time > 1000000000000) {
                time = formatTimestamp(trade.time);
            } else if (typeof trade.time === 'string' && trade.time.includes(':')) {
                time = trade.time;
            } else if (trade.T) {
                time = formatTimestamp(trade.T);
            } else {
                time = formatTimestamp(Date.now());
            }

            const tradeData = { price, qty, side, time };

            recentTrades.unshift(tradeData);
            if (recentTrades.length > 50) {
                recentTrades.pop();
            }

            // Recent Trades 탭이 활성화되어 있을 때만 렌더링
            if (currentObTab === 'trades') {
                renderRecentTrades();
            }

            // 실시간 가격 업데이트 (헤더 & 오더북 스프레드)
            const tradePrice = parseFloat(trade.price || trade.p);
            if (tradePrice > 0) {
                previousPrice = currentPrice;
                currentPrice = tradePrice;
                updatePriceDisplay();
            }
        }

        // ========================================
        // 사용자 주문 추가 (B-Book) - Socket.IO 서버로 전송
        // ========================================
        let orderIdCounter = 1;

        function addUserOrder(side, price, qty, mb_id = 'guest') {
            const orderId = `order_${Date.now()}_${orderIdCounter++}`;
            const order = {
                orderId: orderId,
                price: parseFloat(price),
                qty: parseFloat(qty),
                total: parseFloat(qty),
                timestamp: Date.now()
            };

            if (side === 'sell') {
                userOrders.asks.push(order);
            } else {
                userOrders.bids.push(order);
            }

            // Socket.IO 서버로 주문 전송
            if (socket && socket.connected) {
                socket.emit('order:add', {
                    orderId: orderId,
                    side: side,
                    price: price,
                    qty: qty,
                    mb_id: mb_id
                });
            }

            console.log(`User ${side} order added: ${qty} BTC @ ${price} (ID: ${orderId})`);
            return orderId;
        }

        // ========================================
        // 사용자 주문 제거
        // ========================================
        function removeUserOrder(side, price, orderId = null) {
            if (orderId) {
                // orderId로 제거
                if (side === 'sell') {
                    userOrders.asks = userOrders.asks.filter(o => o.orderId !== orderId);
                } else {
                    userOrders.bids = userOrders.bids.filter(o => o.orderId !== orderId);
                }
            } else {
                // 가격으로 제거
                if (side === 'sell') {
                    userOrders.asks = userOrders.asks.filter(o => Math.abs(o.price - price) > 0.01);
                } else {
                    userOrders.bids = userOrders.bids.filter(o => Math.abs(o.price - price) > 0.01);
                }
            }

            // Socket.IO 서버로 주문 제거 전송
            if (socket && socket.connected) {
                socket.emit('order:remove', {
                    orderId: orderId,
                    side: side
                });
            }
        }

        // 초기화 (Socket.IO 연결)
        function initOrderbook() {
            // Socket.IO 연결 (모든 데이터는 노드 서버에서 받음)
            connectSocketIO();
        }

        function formatNumber(num) {
            if (num >= 1000000000) return (num / 1000000000).toFixed(2) + 'B';
            if (num >= 1000000) return (num / 1000000).toFixed(2) + 'M';
            return num.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }

        // ========================================
        // 오더북 렌더링 (최적화 - requestAnimationFrame + DocumentFragment)
        // ========================================
        let lastRenderTime = 0;
        const RENDER_THROTTLE = 100; // 100ms 최소 간격
        let pendingRender = false;

        function renderOrderbook() {
            const now = performance.now();

            // 쓰로틀링 - 너무 자주 렌더링 방지
            if (now - lastRenderTime < RENDER_THROTTLE) {
                if (!pendingRender) {
                    pendingRender = true;
                    requestAnimationFrame(() => {
                        pendingRender = false;
                        renderOrderbookImmediate();
                    });
                }
                return;
            }

            lastRenderTime = now;
            renderOrderbookImmediate();
        }

        function renderOrderbookImmediate() {
            const asksList = document.getElementById('asksList');
            const bidsList = document.getElementById('bidsList');
            const spreadEl = document.querySelector('.orderbook-spread');

            // 뷰 모드에 따른 표시/숨김 (CSS 클래스 사용)
            asksList.style.display = obViewMode === 'bids' ? 'none' : 'flex';
            bidsList.style.display = obViewMode === 'asks' ? 'none' : 'flex';
            if (obViewMode !== 'both') {
                (obViewMode === 'bids' ? bidsList : asksList).style.flex = '1';
            } else {
                asksList.style.flex = '1';
                bidsList.style.flex = '1';
            }
            if (spreadEl) spreadEl.style.display = 'flex';

            // 최대 누적 합계 계산 (바이낸스 스타일 - 누적 합계 기준 깊이 바)
            const maxAskTotal = asksData.length > 0 ? asksData[asksData.length - 1].total : 0.1;
            const maxBidTotal = bidsData.length > 0 ? bidsData[bidsData.length - 1].total : 0.1;
            const maxTotal = Math.max(maxAskTotal, maxBidTotal, 0.1);

            // 정밀도에 따른 소수점 자릿수 계산 (심볼별 기본값 참조)
            const defaultDecimals = getPriceDecimals(currentSymbol);
            const precisionDecimals = obPrecision < 1 ? Math.round(Math.abs(Math.log10(obPrecision))) : 0;
            const priceDecimals = Math.max(precisionDecimals, defaultDecimals);

            // 가격 포맷 함수 (BTC/ETH는 천단위 구분, 소액코인은 toFixed)
            function formatObPrice(price, decimals) {
                if (price >= 1) {
                    // BTC, ETH 등 고가 코인은 천단위 구분자 사용
                    return price.toLocaleString(undefined, {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
                } else {
                    // DOGE, XRP 등 소액 코인은 단순 toFixed
                    return price.toFixed(decimals);
                }
            }

            // DocumentFragment 사용하여 리플로우 최소화
            if (obViewMode !== 'bids' && asksData.length > 0) {
                const fragment = document.createDocumentFragment();
                asksData.forEach(a => {
                    const row = document.createElement('div');
                    row.className = `ob-row ask${a.isUser ? ' user-order' : ''}`;
                    row.dataset.price = a.price.toFixed(priceDecimals);
                    row.innerHTML = `
                        <span class="ob-price">${formatObPrice(a.price, priceDecimals)}</span>
                        <span class="ob-qty">${formatQty(a.qty)}</span>
                        <span class="ob-total">${formatQty(a.total)}</span>
                        <div class="ob-depth" style="width: ${(a.total / maxTotal) * 100}%"></div>
                    `;
                    fragment.appendChild(row);
                });
                asksList.innerHTML = '';
                asksList.appendChild(fragment);
            }

            // Bids (매수) 렌더링
            if (obViewMode !== 'asks' && bidsData.length > 0) {
                const fragment = document.createDocumentFragment();
                bidsData.forEach(b => {
                    const row = document.createElement('div');
                    row.className = `ob-row bid${b.isUser ? ' user-order' : ''}`;
                    row.dataset.price = b.price.toFixed(priceDecimals);
                    row.innerHTML = `
                        <span class="ob-price">${formatObPrice(b.price, priceDecimals)}</span>
                        <span class="ob-qty">${formatQty(b.qty)}</span>
                        <span class="ob-total">${formatQty(b.total)}</span>
                        <div class="ob-depth" style="width: ${(b.total / maxTotal) * 100}%"></div>
                    `;
                    fragment.appendChild(row);
                });
                bidsList.innerHTML = '';
                bidsList.appendChild(fragment);
            }
        }

        // 수량 포맷 (K, M 단위)
        function formatQty(qty) {
            if (qty >= 1000000) return (qty / 1000000).toFixed(2) + 'M';
            if (qty >= 1000) return (qty / 1000).toFixed(2) + 'K';
            return qty.toFixed(4);
        }

        // ========================================
        // Recent Trades 렌더링
        // ========================================
        function renderRecentTrades() {
            const container = document.getElementById('recentTradesContent');
            if (!container) return;

            container.innerHTML = recentTrades.slice(0, 20).map(t => {
                const qtyDisplay = typeof t.qty === 'number' ? t.qty.toFixed(4) : t.qty;
                return `
                    <div class="trade-row ${t.side}">
                        <span class="trade-price">${formatPrice(t.price, currentSymbol)}</span>
                        <span class="trade-qty">${qtyDisplay}</span>
                        <span class="trade-time">${t.time}</span>
                    </div>
                `;
            }).join('');
        }

        // ========================================
        // 통계 정보 업데이트
        // ========================================
        function updateStats(isUp, priceChange, priceChangePercent = null) {
            // Index Price
            const indexEl = document.getElementById('indexPrice');
            if (indexEl && indexPrice > 0) {
                indexEl.textContent = formatPrice(indexPrice, currentSymbol);
            }

            // Change %
            const changeEl = document.getElementById('changePercent');
            if (changeEl) {
                // 서버에서 priceChangePercent를 직접 받으면 사용, 아니면 계산
                const changePercent = priceChangePercent !== null ? priceChangePercent :
                    (priceChange && currentPrice ? (priceChange / (currentPrice - priceChange) * 100) : 0);
                changeEl.textContent = `${priceChange >= 0 ? '+' : ''}${formatPrice(Math.abs(priceChange), currentSymbol)} (${changePercent >= 0 ? '+' : ''}${changePercent.toFixed(2)}%)`;
                changeEl.className = `stat-value ${priceChange >= 0 ? 'up' : 'down'}`;
            }

            // 24H High
            const highEl = document.getElementById('high24h');
            if (highEl) {
                highEl.textContent = formatPrice(high24h, currentSymbol);
            }

            // 24H Low
            const lowEl = document.getElementById('low24h');
            if (lowEl) {
                lowEl.textContent = formatPrice(low24h, currentSymbol);
            }

            // Turnover
            const turnoverEl = document.getElementById('turnover24h');
            if (turnoverEl) {
                turnoverEl.textContent = formatNumber(turnover);
            }

            // Open Interest - fetchOpenInterest()에서 별도 업데이트

            // Funding Rate - updateFundingCountdown()에서 1초마다 업데이트

            // Contract Details 업데이트
            const contractRows = document.querySelectorAll('.contract-row');
            if (contractRows[1]) {
                contractRows[1].querySelector('span:last-child').textContent = formatPrice(indexPrice, currentSymbol);
            }
            if (contractRows[2]) {
                contractRows[2].querySelector('span:last-child').textContent = formatPrice(currentPrice, currentSymbol);
            }
        }

        // ========================================
        // Depth Bar 업데이트
        // ========================================
        function updateDepthBar() {
            const totalBids = bidsData.reduce((sum, b) => sum + b.qty, 0);
            const totalAsks = asksData.reduce((sum, a) => sum + a.qty, 0);
            const total = totalBids + totalAsks;

            const bidPercent = Math.round((totalBids / total) * 100);
            const askPercent = 100 - bidPercent;

            const bidBar = document.querySelector('.depth-bid');
            const askBar = document.querySelector('.depth-ask');

            if (bidBar && askBar) {
                bidBar.style.width = bidPercent + '%';
                bidBar.textContent = `B ${bidPercent}%`;
                askBar.style.width = askPercent + '%';
                askBar.textContent = `${askPercent}% S`;
            }
        }

        // ========================================
        // 오픈인터레스트 (REST API - WebSocket 미제공)
        // 펀딩비율은 Node 서버의 @markPrice 스트림에서 수신
        // ========================================
        async function fetchOpenInterest() {
            try {
                const oiRes = await fetch(`https://fapi.binance.com/fapi/v1/openInterest?symbol=${currentSymbol}`);
                const oiData = await oiRes.json();

                if (oiData.openInterest) {
                    openInterest = parseFloat(oiData.openInterest);
                    const oiElement = document.getElementById('openInterest');
                    if (oiElement) {
                        oiElement.textContent = formatNumber(openInterest);
                    }
                }
            } catch (error) {
                console.error('OI 데이터 로드 실패:', error);
            }
        }

        // 펀딩비율 UI 업데이트 함수 (markPrice 수신 시 호출)
        function updateFundingRateUI() {
            const fundingEl = document.getElementById('fundingRate');
            if (fundingEl) {
                const rateText = fundingRate >= 0
                    ? `+${fundingRate.toFixed(4)}%`
                    : `${fundingRate.toFixed(4)}%`;
                fundingEl.textContent = `${rateText} / ${formatTime(fundingCountdown)}`;
                fundingEl.className = `stat-value ${fundingRate >= 0 ? 'up' : 'down'}`;
            }
        }

        // ========================================
        // 펀딩 카운트다운
        // ========================================
        function updateFundingCountdown() {
            fundingCountdown--;
            if (fundingCountdown <= 0) {
                fundingCountdown = 8 * 3600; // 8시간 리셋
                // 펀딩비율은 Node 서버 @markPrice에서 자동 업데이트됨
            }

            // UI 업데이트
            const fundingEl = document.getElementById('fundingRate');
            if (fundingEl) {
                const rateText = fundingRate >= 0
                    ? `+${fundingRate.toFixed(4)}%`
                    : `${fundingRate.toFixed(4)}%`;
                fundingEl.textContent = `${rateText} / ${formatTime(fundingCountdown)}`;
                fundingEl.className = `stat-value ${fundingRate >= 0 ? 'up' : 'down'}`;
            }
        }

        // ========================================
        // 마진 정보 업데이트 (로그인 시에만 API 호출)
        // ========================================
        function updateMarginInfo() {
            // 빈 함수 - API 데이터 사용
        }

        // ========================================
        // 오더북/최근거래 탭 전환
        // ========================================
        let currentObTab = 'orderbook';

        function setupOrderbookTabs() {
            const obContent = document.querySelector('.orderbook-content');
            const obTabs = document.querySelectorAll('.ob-tab');

            // Recent Trades 헤더
            const obHeader = document.querySelector('.orderbook-header');

            obTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    obTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    const isOrderbook = this.textContent.includes('Order Book');
                    currentObTab = isOrderbook ? 'orderbook' : 'trades';

                    if (isOrderbook) {
                        document.getElementById('asksList').style.display = '';
                        document.getElementById('bidsList').style.display = '';
                        document.querySelector('.orderbook-spread').style.display = '';
                        if (document.getElementById('recentTradesContent')) {
                            document.getElementById('recentTradesContent').style.display = 'none';
                        }
                        obHeader.innerHTML = '<span>Price(BTC)</span><span>Qty(BTC)</span><span>Total(BTC)</span>';
                    } else {
                        document.getElementById('asksList').style.display = 'none';
                        document.getElementById('bidsList').style.display = 'none';
                        document.querySelector('.orderbook-spread').style.display = 'none';

                        if (!document.getElementById('recentTradesContent')) {
                            obContent.insertAdjacentHTML('afterbegin', '<div class="recent-trades-content" id="recentTradesContent"></div>');
                        }
                        document.getElementById('recentTradesContent').style.display = '';
                        obHeader.innerHTML = '<span>Price(BTC)</span><span>Qty(BTC)</span><span>Time</span>';
                        renderRecentTrades();
                    }
                });
            });

            // 정밀도 변경
            const precisionSelect = document.getElementById('precisionSelect');
            if (precisionSelect) {
                precisionSelect.addEventListener('change', function() {
                    obPrecision = parseFloat(this.value);
                    console.log('Precision changed to:', obPrecision);
                    if (rawAsksData.length > 0 || rawBidsData.length > 0) {
                        applyPrecisionAndRender();
                    }
                });
            }

            // 뷰 모드 변경
            document.querySelectorAll('.ob-view').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.ob-view').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    obViewMode = this.dataset.view;
                    console.log('View mode changed to:', obViewMode);

                    // 뷰 모드에 따라 행 수가 달라지므로 다시 처리
                    if (rawAsksData.length > 0 || rawBidsData.length > 0) {
                        applyPrecisionAndRender();
                    } else {
                        renderOrderbook();
                    }
                });
            });
        }

        // ========================================
        // 탭 이벤트 설정
        // ========================================
        const panelIds = ['openOrdersPanel', 'positionsPanel', 'orderHistoryPanel', 'tradeHistoryPanel', 'assetsPanel', 'pnlPanel'];

        function setupTabs() {
            // Panel Tabs - 각 탭별 콘텐츠 전환
            document.querySelectorAll('.panel-tab').forEach((tab, index) => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // 모든 패널 숨기고 선택된 패널만 표시
                    document.querySelectorAll('.panel-section').forEach(p => p.classList.remove('active'));
                    const targetPanel = document.getElementById(panelIds[index]);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }

                    // 필터 표시/숨김 (Open Orders, Order History에서만 표시)
                    const panelFilters = document.getElementById('panelFilters');
                    if (panelFilters) {
                        const showFilters = [0, 2].includes(index); // Open Orders, Order History
                        panelFilters.style.display = showFilters ? 'flex' : 'none';
                    }

                    // 탭 전환 시 데이터 로드
                    switch(index) {
                        case 0: // Open Orders
                            loadUserOrders();
                            break;
                        case 1: // Positions
                            loadUserPositions();
                            break;
                        case 2: // Order History
                            loadOrderHistory();
                            break;
                        case 3: // Trade History
                            loadTradeHistory();
                            break;
                        case 4: // Assets
                            loadAssetsData();
                            break;
                        case 5: // P&L
                            updatePnlData();
                            break;
                    }
                });
            });

            // Limit Tabs
            document.querySelectorAll('.limit-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.limit-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Chart Tabs
            document.querySelectorAll('.chart-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Interval Buttons (분봉 변경)
            document.querySelectorAll('.interval-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    // 분봉 변경
                    const intervalText = this.textContent.trim();
                    currentInterval = intervalText;

                    // 서버에 새 분봉 데이터 요청
                    if (socket && socket.connected) {
                        socket.emit('get:klines', { interval: currentInterval, limit: 500 });
                    }

                    console.log('[Chart] Interval changed to:', currentInterval);
                });
            });

            // Filter Buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // OB View Buttons
            document.querySelectorAll('.ob-view').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.ob-view').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Order Type Tabs (Limit / Market / Conditional)
            const orderTypeTabs = document.querySelectorAll('.order-type-tab');
            const priceInputGroup = document.querySelector('.input-group:has(#priceInput)');

            orderTypeTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Conditional 탭은 드롭다운이므로 제외
                    if (this.classList.contains('has-dropdown')) return;

                    orderTypeTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    const orderType = this.textContent.trim().toLowerCase();

                    // Market인 경우 가격 입력 비활성화
                    if (priceInputGroup) {
                        if (orderType === 'market') {
                            priceInputGroup.style.opacity = '0.5';
                            priceInputGroup.style.pointerEvents = 'none';
                            const priceInput = document.getElementById('priceInput');
                            if (priceInput) priceInput.value = 'Market Price';
                        } else {
                            priceInputGroup.style.opacity = '1';
                            priceInputGroup.style.pointerEvents = 'auto';
                            const priceInput = document.getElementById('priceInput');
                            if (priceInput && currentPrice > 0) {
                                priceInput.value = formatPrice(currentPrice, currentSymbol);
                            }
                        }
                    }
                });
            });

            // Last 버튼 토글 (클릭 시 활성화/비활성화)
            const lastBtn = document.querySelector('.last-btn');
            if (lastBtn) {
                // 초기 상태: 활성화
                lastBtn.classList.add('active');

                lastBtn.addEventListener('click', function() {
                    this.classList.toggle('active');

                    // 활성화되면 현재가로 즉시 업데이트
                    if (this.classList.contains('active') && currentPrice > 0) {
                        const priceInput = document.getElementById('priceInput');
                        if (priceInput) {
                            priceInput.value = formatPrice(currentPrice, currentSymbol);
                        }
                    }
                });
            }

            // 가격 입력창 직접 수정 시 Last 버튼 비활성화
            const priceInput = document.getElementById('priceInput');
            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    const lastBtn = document.querySelector('.last-btn');
                    if (lastBtn) {
                        lastBtn.classList.remove('active');
                    }
                });
            }
        }

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

        // ========================================
        // 심볼 드롭다운 설정 (DB에서 로드)
        // ========================================
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

        // 심볼 드롭다운 목록 동적 생성 (바이낸스 스타일)
        function generateSymbolList() {
            const symbolList = document.getElementById('symbolList');
            if (!symbolList) return;

            // 지원하는 심볼 목록
            const supportedSymbols = Object.keys(symbolConfigs);

            // 심볼별 거래량 (임시 데이터 - 나중에 실시간으로 업데이트)
            // 숫자 값으로 저장 (정렬용)
            const volumes = {};
            supportedSymbols.forEach(symbol => {
                volumes[symbol] = (Math.random() * 10).toFixed(2); // 숫자로 저장
            });

            // HTML 생성
            let html = '';
            supportedSymbols.forEach(symbol => {
                const config = symbolConfigs[symbol];
                const baseName = symbol.replace('USDT', '').toLowerCase();
                const isActive = symbol === currentSymbol;
                const volNum = volumes[symbol];

                html += `
                    <div class="dropdown-item${isActive ? ' active' : ''}" data-symbol="${symbol}" data-volume="${volNum}">
                        <span class="item-star">☆</span>
                        <img class="item-logo" src="${config.logo}" alt="${config.name}" onerror="this.style.display='none'">
                        <div class="item-info">
                            <span class="item-name">${symbol} <span class="perp-badge">Perp</span></span>
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
                    const currentSymbol = document.getElementById('currentSymbolName')?.textContent;
                    if (currentSymbol) {
                        const dropdownItem = document.querySelector(`.dropdown-item[data-symbol="${currentSymbol}"]`);
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
                            showToast(`${currentSymbol} 즐겨찾기에 추가됨`, 'success');
                        } else {
                            showToast(`${currentSymbol} 즐겨찾기에서 제거됨`, 'info');
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

                const symbol = item.dataset.symbol;
                selectSymbol(symbol);
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

            // 즐겨찾기 저장 (쿠키) - 전역 함수 사용
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

            // 즐겨찾기 불러오기 (쿠키) - 전역 함수 사용
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

            // 정렬 상태: direction은 'desc' → 'asc' → null 순환
            let currentSort = { field: null, direction: null };
            let originalOrder = []; // 원래 순서 저장

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
                    currentSort.direction = 'desc'; // 첫 클릭은 내림차순
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

            // 헤더 정렬 클릭
            document.querySelectorAll('.header-sort').forEach(sort => {
                sort.addEventListener('click', function() {
                    document.querySelectorAll('.header-sort').forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // 브라우저 뒤로가기/앞으로가기 처리
            window.addEventListener('popstate', function(event) {
                if (event.state && event.state.symbol) {
                    console.log('[History] Navigating to:', event.state.symbol);
                    // popstate에서는 URL 업데이트 없이 심볼만 변경
                    window._skipUrlUpdate = true; // 플래그 설정
                    if (socket && socket.connected) {
                        socket.emit('switch:symbol', { symbol: event.state.symbol });
                    }
                }
            });
        }

        // 포지션/주문 목록에서 심볼 클릭 시 해당 심볼로 전환
        function switchToSymbol(symbol) {
            selectSymbol(symbol);
        }

        function selectSymbol(symbol) {
            const config = symbolConfigs[symbol];
            if (!config) {
                console.warn('Unknown symbol:', symbol);
                return;
            }

            // 이미 같은 심볼이면 무시
            if (currentSymbol === symbol.toUpperCase()) {
                return;
            }

            console.log('Switching symbol to:', symbol);

            // Lightweight Charts 모드이면 서버에 심볼 변경 요청
            if (chartMode === 'lightweight' && socket && socket.connected) {
                // 로딩 표시
                showToast(`${symbol} 로딩중...`, 'info');

                // 서버에 심볼 변경 요청 (서버가 스트림 구독 변경 후 데이터 전송)
                socket.emit('switch:symbol', { symbol: symbol.toUpperCase() });
            } else if (chartMode !== 'lightweight') {
                // TradingView 위젯 모드
                currentSymbol = symbol.toUpperCase();
                updateChartSymbol(symbol);
                updateSymbolUI(symbol);
            }

            // OI 업데이트 (펀딩비율은 Node @markPrice에서 자동 수신)
            fetchOpenInterest();
        }

        // 심볼 UI 업데이트 (헤더, 드롭다운 등)
        // updateUrl: true이면 브라우저 히스토리에 추가 (기본값 true)
        function updateSymbolUI(symbol, updateUrl = true) {
            const config = symbolConfigs[symbol];
            if (!config) return;

            // 헤더 심볼명 업데이트
            document.getElementById('currentSymbolName').textContent = symbol;
            const logoEl = document.getElementById('currentCoinLogo');
            if (logoEl && config.logo) {
                logoEl.src = config.logo;
            }

            // 페이지 타이틀 업데이트
            document.title = `${symbol} 무기한 - FuturX`;

            // URL 업데이트 (브라우저 히스토리에 추가, 페이지 새로고침 없이)
            if (updateUrl && window.history && window.history.pushState) {
                const newUrl = `trade.php?symbol=${symbol}`;
                window.history.pushState({ symbol: symbol }, document.title, newUrl);
            }

            // 활성 항목 표시
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.classList.toggle('active', item.dataset.symbol === symbol);
            });

            // 드롭다운 닫기
            const selector = document.querySelector('.symbol-selector');
            if (selector) selector.classList.remove('open');

            // 헤더 별표 동기화
            syncHeaderStar();

            console.log('Symbol UI updated to:', symbol);
        }

        // 심볼 목록 가격 업데이트 (드롭다운 리스트용)
        function updateSymbolListPrice(symbol, price, changePercent) {
            if (!symbol || price === undefined) return;

            // 심볼 이름에서 USDT 제거하여 ID 생성
            const baseName = symbol.replace('USDT', '').toLowerCase();
            const priceEl = document.getElementById(`dropdown-${baseName}-price`);
            const changeEl = document.getElementById(`dropdown-${baseName}-change`);

            if (priceEl) {
                // formatPrice 함수 사용하여 심볼별 소수점 통일
                priceEl.textContent = formatPrice(price, symbol);
            }

            if (changeEl && changePercent !== undefined) {
                const isPositive = changePercent >= 0;
                const sign = isPositive ? '+' : '';
                changeEl.textContent = `${sign}${changePercent.toFixed(2)}%`;
                changeEl.classList.remove('up', 'down');
                changeEl.classList.add(isPositive ? 'up' : 'down');
            }
        }

        // 코인명 라벨 업데이트 (BTC → ETH 등)
        function updateCoinLabels(symbol) {
            const baseCoin = symbol.replace('USDT', '');

            // 심볼별 기본 정밀도 및 옵션 (PRICE_DECIMALS 기반 자동 생성)
            const decimals = getPriceDecimals(symbol);
            const minPrecision = Math.pow(10, -decimals);
            const defaultOptions = [minPrecision, minPrecision * 10, minPrecision * 100, minPrecision * 1000];

            const precisionConfig = {
                'BTCUSDT': { default: 0.1, options: [0.1, 1, 10, 50, 100] },
                'ETHUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10, 50] },
                'BNBUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10] },
                'SOLUSDT': { default: 0.01, options: [0.01, 0.1, 1, 5, 10] },
                'XRPUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] },
                'DOGEUSDT': { default: 0.00001, options: [0.00001, 0.0001, 0.001, 0.01] },
                'ADAUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] },
                'AVAXUSDT': { default: 0.01, options: [0.01, 0.1, 1, 5, 10] },
                'DOTUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'LINKUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'LTCUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10] },
                'BCHUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10] },
                'ATOMUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'ETCUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10] },
                'XLMUSDT': { default: 0.00001, options: [0.00001, 0.0001, 0.001, 0.01] },
                'TRXUSDT': { default: 0.00001, options: [0.00001, 0.0001, 0.001, 0.01] },
                'NEARUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'APTUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'ARBUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] },
                'OPUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] },
                'MATICUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] },
                'FILUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'ICPUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'SHIBUSDT': { default: 0.000001, options: [0.000001, 0.00001, 0.0001, 0.001] },
                'UNIUSDT': { default: 0.001, options: [0.001, 0.01, 0.1, 1] },
                'XMRUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10] },
                'DASHUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10] },
                'ZECUSDT': { default: 0.01, options: [0.01, 0.1, 1, 10] },
                'XTZUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] },
                'ONTUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] },
                'IOTAUSDT': { default: 0.0001, options: [0.0001, 0.001, 0.01, 0.1] }
            };
            const config = precisionConfig[symbol] || { default: minPrecision, options: defaultOptions };
            obPrecision = config.default;

            // 정밀도 드롭다운 옵션 동적 생성
            const precisionSelect = document.getElementById('precisionSelect');
            if (precisionSelect) {
                precisionSelect.innerHTML = config.options.map(opt =>
                    `<option value="${opt}" ${opt === obPrecision ? 'selected' : ''}>${opt}</option>`
                ).join('');
            }

            // 수량 입력 단위
            const qtyUnitLabel = document.getElementById('qtyUnitLabel');
            if (qtyUnitLabel) qtyUnitLabel.textContent = baseCoin;

            // Open Interest 라벨
            const oiCoinLabel = document.getElementById('oiCoinLabel');
            if (oiCoinLabel) oiCoinLabel.textContent = baseCoin;

            // 호가창 헤더
            document.querySelectorAll('.ob-coin').forEach(el => {
                el.textContent = baseCoin;
            });

            // Max Size 라벨 업데이트
            const orderMaxSize = document.getElementById('orderMaxSize');
            if (orderMaxSize) {
                const currentText = orderMaxSize.textContent;
                const numMatch = currentText.match(/[\d.,]+/);
                if (numMatch) {
                    orderMaxSize.textContent = numMatch[0] + ' ' + baseCoin;
                } else {
                    orderMaxSize.textContent = '-- ' + baseCoin;
                }
            }

            console.log('Coin labels updated to:', baseCoin, 'precision:', obPrecision);
        }

        function updateChartSymbol(symbol) {
            const chartContainer = document.getElementById('tradingview-chart');
            if (!chartContainer) return;

            chartContainer.innerHTML = `
                <iframe
                    src="https://www.tradingview.com/widgetembed/?frameElementId=tradingview_chart&symbol=BINANCE%3A${symbol}&interval=1&hidesidetoolbar=0&symboledit=0&saveimage=0&toolbarbg=161a1e&studies=%5B%5D&theme=dark&style=1&timezone=Asia%2FSeoul&studies_overrides=%7B%7D&overrides=%7B%7D&enabled_features=%5B%5D&disabled_features=%5B%5D&locale=kr&utm_source=&utm_medium=widget&utm_campaign=chart&utm_term=BINANCE%3A${symbol}"
                    style="width:100%;height:100%;border:none;"
                    allowtransparency="true"
                    scrolling="no"
                    allowfullscreen>
                </iframe>
            `;
        }

        // ========================================
        // 버튼 호버/클릭 효과
        // ========================================
        function setupButtonEffects() {
            // Long/Short 버튼 효과
            const longBtn = document.querySelector('.action-btn.long');
            const shortBtn = document.querySelector('.action-btn.short');

            if (longBtn) {
                longBtn.addEventListener('click', function() {
                    this.classList.add('clicked');
                    setTimeout(() => this.classList.remove('clicked'), 200);
                });
            }

            if (shortBtn) {
                shortBtn.addEventListener('click', function() {
                    this.classList.add('clicked');
                    setTimeout(() => this.classList.remove('clicked'), 200);
                });
            }
        }

        // ========================================
        // 슬라이더 및 주문 입력 연동
        // ========================================
        let userBalance = 0;
        let userAvailableBalance = 0;
        let userMarginBalance = 0;
        let userUnrealizedPnl = 0;
        let sliderPercent = 0;
        let updateOrderInfoPending = false;

        function setupSlider() {
            const track = document.querySelector('.slider-track');
            const fill = document.querySelector('.slider-fill');
            const thumb = document.querySelector('.slider-thumb');
            const quantityInput = document.getElementById('quantityInput');
            const amountInput = document.getElementById('amountInput');

            if (!track || !fill || !thumb) return;

            let isDragging = false;
            let trackRect = track.getBoundingClientRect();

            // 슬라이더 위치만 업데이트 (빠름)
            function setSliderPosition(percent) {
                percent = Math.max(0, Math.min(100, percent));
                sliderPercent = percent;
                fill.style.width = percent + '%';
                thumb.style.left = percent + '%';
            }

            // 입력값 업데이트 (드래그 종료 또는 클릭 시)
            function updateInputsFromSlider(percent) {
                if (userAvailableBalance > 0 && currentPrice > 0) {
                    const margin = (userAvailableBalance * percent / 100);
                    const positionSize = margin * userLeverage;
                    const quantity = positionSize / currentPrice;

                    if (quantityInput) quantityInput.value = quantity.toFixed(6);
                    if (amountInput) amountInput.value = margin.toFixed(2);
                    scheduleOrderInfoUpdate();
                }
            }

            // 트랙 클릭
            track.addEventListener('click', function(e) {
                if (isDragging) return;
                trackRect = track.getBoundingClientRect();
                const percent = ((e.clientX - trackRect.left) / trackRect.width) * 100;
                setSliderPosition(percent);
                updateInputsFromSlider(percent);
            });

            // 드래그 시작
            thumb.addEventListener('mousedown', function(e) {
                e.preventDefault();
                isDragging = true;
                trackRect = track.getBoundingClientRect();
                document.body.style.userSelect = 'none';
            });

            // 드래그 중 - 즉각 반응 (딜레이 없음)
            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;

                const percent = ((e.clientX - trackRect.left) / trackRect.width) * 100;
                setSliderPosition(percent);

                // 입력값도 즉시 업데이트
                if (userAvailableBalance > 0 && currentPrice > 0) {
                    const margin = (userAvailableBalance * sliderPercent / 100);
                    const positionSize = margin * userLeverage;
                    const quantity = positionSize / currentPrice;
                    if (quantityInput) quantityInput.value = quantity.toFixed(6);
                    if (amountInput) amountInput.value = margin.toFixed(2);
                }
            });

            // 드래그 종료
            document.addEventListener('mouseup', function() {
                if (isDragging) {
                    isDragging = false;
                    document.body.style.userSelect = '';
                    updateOrderInfo(); // 드래그 끝날 때만 전체 업데이트
                }
            });

            // 퍼센트 라벨 클릭
            document.querySelectorAll('.slider-label').forEach(label => {
                label.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const percent = parseInt(this.dataset.percent);
                    setSliderPosition(percent);
                    updateInputsFromSlider(percent);
                });
            });

            // 수량 입력시 금액/슬라이더 연동
            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    const qty = parseFloat(this.value) || 0;
                    if (currentPrice > 0) {
                        const positionValue = qty * currentPrice;
                        const margin = positionValue / userLeverage;
                        if (amountInput) amountInput.value = margin.toFixed(2);

                        if (userAvailableBalance > 0) {
                            const percent = Math.min(100, (margin / userAvailableBalance) * 100);
                            setSliderPosition(percent);
                        }
                        scheduleOrderInfoUpdate();
                    }
                });
            }

            // 금액 입력시 수량/슬라이더 연동
            if (amountInput) {
                amountInput.addEventListener('input', function() {
                    const margin = parseFloat(this.value) || 0;
                    if (currentPrice > 0) {
                        const positionSize = margin * userLeverage;
                        const qty = positionSize / currentPrice;
                        if (quantityInput) quantityInput.value = qty.toFixed(6);

                        if (userAvailableBalance > 0) {
                            const percent = Math.min(100, (margin / userAvailableBalance) * 100);
                            setSliderPosition(percent);
                        }
                        scheduleOrderInfoUpdate();
                    }
                });
            }
        }

        // 주문 정보 업데이트 스케줄링 (debounce)
        function scheduleOrderInfoUpdate() {
            if (updateOrderInfoPending) return;
            updateOrderInfoPending = true;
            requestAnimationFrame(() => {
                updateOrderInfo();
                updateOrderInfoPending = false;
            });
        }

        // 잔고 표시 업데이트 (WebSocket account:update용)
        function updateBalanceDisplay() {
            const formatBalance = (val) => val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const marginBalanceEl = document.getElementById('marginBalanceDisplay');
            const availableBalanceEl = document.getElementById('availableBalanceDisplay');
            const orderAvailable = document.getElementById('orderAvailable');

            // 실제 주문 가능 금액 = 가용잔고 + 미실현 PNL (바이낸스 방식)
            const orderableBalance = userAvailableBalance + userUnrealizedPnl;

            if (marginBalanceEl) marginBalanceEl.textContent = formatBalance(userMarginBalance + userUnrealizedPnl) + ' USDT';
            if (availableBalanceEl) availableBalanceEl.textContent = formatBalance(orderableBalance) + ' USDT';
            if (orderAvailable) orderAvailable.textContent = formatBalance(orderableBalance) + ' USDT';
            if (typeof updateUnrealizedPnlDisplay === 'function') updateUnrealizedPnlDisplay(userUnrealizedPnl);
            if (typeof updateMarginBars === 'function' && userMarginBalance > 0) {
                const initialMargin = userMarginBalance - userAvailableBalance;
                updateMarginBars((initialMargin / userMarginBalance) * 100, (initialMargin / userMarginBalance) * 20);
            }
        }


        // 주문 정보 업데이트
        function updateOrderInfo() {
            const quantityInput = document.getElementById('quantityInput');
            const amountInput = document.getElementById('amountInput');
            const orderAvailable = document.getElementById('orderAvailable');
            const orderMaxSize = document.getElementById('orderMaxSize');
            const orderCost = document.getElementById('orderCost');
            const orderLiqPrice = document.getElementById('orderLiqPrice');

            const qty = parseFloat(quantityInput?.value) || 0;
            const margin = parseFloat(amountInput?.value) || 0;

            // Available (미실현 PNL 포함 - 바이낸스 방식)
            const orderableBalance = userAvailableBalance + userUnrealizedPnl;
            if (orderAvailable) {
                orderAvailable.textContent = orderableBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT';
            }

            // Max Size (미실현 PNL 포함)
            if (orderMaxSize && currentPrice > 0 && orderableBalance > 0) {
                const maxQty = (orderableBalance * userLeverage) / currentPrice;
                orderMaxSize.textContent = maxQty.toFixed(6) + ' BTC';
            }

            // Cost
            if (orderCost) {
                orderCost.textContent = margin.toFixed(2) + ' USDT';
            }

            // Liq Price
            if (orderLiqPrice && qty > 0 && currentPrice > 0) {
                const liqPrice = currentPrice * (1 - (1 / userLeverage) * 0.9);
                orderLiqPrice.textContent = formatPrice(liqPrice, currentSymbol);
            }
        }

        // ========================================
        // 하단 패널 데이터 초기화 (빈 상태)
        // ========================================
        function initPanelData() {
            // 로그인 전에는 빈 상태, 로그인 후 API로 실제 데이터 로드
            const positionsBody = document.getElementById('positionsBody');
            if (positionsBody) positionsBody.innerHTML = '<tr><td colspan="8" class="empty-cell">포지션이 없습니다</td></tr>';

            const orderHistoryBody = document.getElementById('orderHistoryBody');
            if (orderHistoryBody) orderHistoryBody.innerHTML = '<tr><td colspan="7" class="empty-cell">주문 내역이 없습니다</td></tr>';

            const tradeHistoryBody = document.getElementById('tradeHistoryBody');
            if (tradeHistoryBody) tradeHistoryBody.innerHTML = '<tr><td colspan="7" class="empty-cell">거래 내역이 없습니다</td></tr>';

            const assetsBody = document.getElementById('assetsBody');
            if (assetsBody) assetsBody.innerHTML = '<tr><td colspan="5" class="empty-cell">자산이 없습니다</td></tr>';

            const borrowingsBody = document.getElementById('borrowingsBody');
            if (borrowingsBody) borrowingsBody.innerHTML = '<tr><td colspan="4" class="empty-cell">대출 내역이 없습니다</td></tr>';
        }

        // 자산 데이터 로드 (API)
        async function loadUserAssets() {
            if (!authToken) return;
            try {
                const response = await fetch(`${TRADE_API}?action=get_balance`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                const data = await response.json();
                if (data.success && data.data.wallets) {
                    renderAssets(data.data.wallets);
                }
            } catch (error) {
                console.error('Load assets error:', error);
            }
        }

        function renderAssets(wallets) {
            const assetsBody = document.getElementById('assetsBody');
            if (!assetsBody || wallets.length === 0) return;

            assetsBody.innerHTML = wallets.map(w => {
                const balance = parseFloat(w.balance) || 0;
                const available = parseFloat(w.available_balance) || 0;
                const frozen = parseFloat(w.frozen_balance) || 0;
                return `
                    <tr>
                        <td><div class="coin-cell"><span class="coin-icon usdt">$</span>${w.currency}</div></td>
                        <td>${balance.toFixed(2)}</td>
                        <td>${available.toFixed(2)}</td>
                        <td>${frozen.toFixed(2)}</td>
                        <td>$${balance.toFixed(2)}</td>
                    </tr>
                `;
            }).join('');
        }

        // 주문 내역 로드 (API)
        async function loadOrderHistory() {
            if (!authToken) return;
            try {
                const response = await fetch(`${TRADE_API}?action=get_order_history`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                const data = await response.json();
                if (data.success) {
                    renderOrderHistory(data.data.orders || []);
                }
            } catch (error) {
                console.error('Load order history error:', error);
            }
        }

        function renderOrderHistory(orders) {
            const tbody = document.getElementById('orderHistoryBody');
            if (!tbody) return;

            if (orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-cell">주문 내역이 없습니다</td></tr>';
                return;
            }

            tbody.innerHTML = orders.map(o => {
                const time = new Date(o.created_at).toLocaleTimeString('ko-KR');
                const sideClass = o.side === 'buy' ? 'long' : 'short';
                return `
                    <tr>
                        <td>${time}</td>
                        <td>${o.symbol}</td>
                        <td>${o.order_type}</td>
                        <td class="${sideClass}">${o.side === 'buy' ? 'Buy' : 'Sell'}</td>
                        <td>${formatPrice(parseFloat(o.price), o.symbol)}</td>
                        <td>${parseFloat(o.quantity).toFixed(6)}</td>
                        <td>${o.status}</td>
                    </tr>
                `;
            }).join('');
        }

        // 거래 내역 로드 (API)
        async function loadTradeHistory() {
            if (!authToken) return;
            try {
                const response = await fetch(`${TRADE_API}?action=get_trade_history`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                const data = await response.json();
                if (data.success) {
                    renderTradeHistory(data.data.trades || []);
                }
            } catch (error) {
                console.error('Load trade history error:', error);
            }
        }

        function renderTradeHistory(trades) {
            const tbody = document.getElementById('tradeHistoryBody');
            if (!tbody) return;

            if (trades.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-cell">거래 내역이 없습니다</td></tr>';
                return;
            }

            tbody.innerHTML = trades.map(t => {
                const time = new Date(t.created_at).toLocaleTimeString('ko-KR');
                const sideClass = t.side === 'buy' ? 'long' : 'short';
                const pnlClass = parseFloat(t.realized_pnl) >= 0 ? 'up' : 'down';
                return `
                    <tr>
                        <td>${time}</td>
                        <td>${t.symbol}</td>
                        <td class="${sideClass}">${t.side === 'buy' ? 'Buy' : 'Sell'}</td>
                        <td>${formatPrice(parseFloat(t.price), t.symbol)}</td>
                        <td>${parseFloat(t.quantity).toFixed(6)}</td>
                        <td>${parseFloat(t.fee).toFixed(4)} USDT</td>
                        <td class="${pnlClass}">${parseFloat(t.realized_pnl).toFixed(2)} USDT</td>
                    </tr>
                `;
            }).join('');
        }

        // PNL 데이터 업데이트 (실제 API에서 가져옴)
        let pnlCurrentPage = 0;
        const pnlPageSize = 20;
        let pnlTotalCount = 0;
        let pnlCurrentTab = 'positions'; // 'orders' or 'positions'
        let pnlCurrentPeriod = 7; // 7, 30, 180 days

        // P&L 탭 이벤트 설정
        function setupPnlTabs() {
            // Main tabs (Orders/Positions)
            document.querySelectorAll('.pnl-main-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.pnl-main-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    pnlCurrentTab = this.dataset.type;
                    pnlCurrentPage = 0;
                    updatePnlTableHeader();
                    loadPnlHistory();
                });
            });

            // Period tabs
            document.querySelectorAll('.pnl-period-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.pnl-period-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    pnlCurrentPeriod = parseInt(this.dataset.period);
                    pnlCurrentPage = 0;
                    loadPnlHistory();
                });
            });
        }

        // P&L 테이블 헤더 업데이트
        function updatePnlTableHeader() {
            const thead = document.getElementById('pnlTableHead');
            if (!thead) return;

            if (pnlCurrentTab === 'positions') {
                thead.innerHTML = `
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
                `;
            } else {
                thead.innerHTML = `
                    <tr>
                        <th>Market</th>
                        <th>P&L</th>
                        <th>Opening Fee</th>
                        <th>Closing Fee</th>
                        <th>Funding Fee</th>
                        <th>Open Time</th>
                        <th>Open Duration</th>
                        <th>Close Time</th>
                    </tr>
                `;
            }
        }

        // Trading Performance Modal
        function openPerformanceModal() {
            document.getElementById('performanceModal').classList.add('active');
            loadPerformanceData();
        }

        function closePerformanceModal() {
            document.getElementById('performanceModal').classList.remove('active');
        }

        async function loadPerformanceData() {
            if (!authToken) return;

            try {
                const res = await fetch(`${TRADE_API}?action=get_pnl_stats`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                const data = await res.json();

                if (data.success) {
                    const stats = data.data;

                    updatePerfValue('perfToday', stats.today);
                    updatePerfValue('perfWeek', stats.week);
                    updatePerfValue('perfMonth', stats.month);
                    updatePerfValue('perfTotal', stats.total);

                    document.getElementById('perfWinRate').textContent = `${stats.win_rate}%`;
                    document.getElementById('perfWinRate').className = `perf-value ${stats.win_rate >= 50 ? 'positive' : 'negative'}`;

                    document.getElementById('perfTotalTrades').textContent = stats.total_trades;
                    document.getElementById('perfTotalFees').textContent = `${parseFloat(stats.total_fee || 0).toFixed(2)} USDT`;
                    document.getElementById('perfWinLoss').textContent = `${stats.win_count}W / ${stats.loss_count}L`;
                }
            } catch (err) {
                console.error('Performance load error:', err);
            }
        }

        function updatePerfValue(id, value) {
            const el = document.getElementById(id);
            if (!el) return;
            const formatted = value >= 0 ? `+${parseFloat(value).toFixed(2)}` : parseFloat(value).toFixed(2);
            el.textContent = `${formatted} USDT`;
            el.className = `perf-value ${value >= 0 ? 'positive' : 'negative'}`;
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePerformanceModal();
            }
        });

        async function updatePnlData() {
            if (!authToken) return;

            try {
                // P&L 히스토리 가져오기
                await loadPnlHistory();
                // Performance 데이터 로드 (모달용)
                loadPerformanceData();

            } catch (err) {
                console.error('PNL data error:', err);
            }
        }

        function updatePnlValue(elementId, value) {
            const el = document.getElementById(elementId);
            if (!el) return;

            const formatted = value >= 0 ? `+${value.toFixed(2)}` : value.toFixed(2);
            el.textContent = `${formatted} USDT`;
            el.classList.remove('up', 'down');
            if (value > 0) el.classList.add('up');
            else if (value < 0) el.classList.add('down');
        }

        async function loadPnlHistory(page = 0) {
            const colSpan = pnlCurrentTab === 'positions' ? 11 : 9;

            if (!authToken) {
                document.getElementById('pnlHistoryBody').innerHTML =
                    `<tr><td colspan="${colSpan}" class="empty-cell">로그인이 필요합니다</td></tr>`;
                return;
            }

            pnlCurrentPage = page;
            const offset = page * pnlPageSize;

            // 로딩 표시
            document.getElementById('pnlHistoryBody').innerHTML =
                `<tr><td colspan="${colSpan}" class="empty-cell">Loading...</td></tr>`;

            try {
                const res = await fetch(`${TRADE_API}?action=get_pnl_history&limit=${pnlPageSize}&offset=${offset}&period=${pnlCurrentPeriod}`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                const data = await res.json();

                if (data.success) {
                    pnlTotalCount = data.data.total;
                    renderPnlHistory(data.data.positions);
                    renderPnlPagination();
                    updatePnlStats();
                } else {
                    document.getElementById('pnlHistoryBody').innerHTML =
                        `<tr><td colspan="${colSpan}" class="empty-cell">${data.error || '데이터 로드 실패'}</td></tr>`;
                }
            } catch (err) {
                console.error('PNL history error:', err);
                document.getElementById('pnlHistoryBody').innerHTML =
                    `<tr><td colspan="${colSpan}" class="empty-cell">데이터 로드 중 오류 발생</td></tr>`;
            }
        }

        function updatePnlStats() {
            const statsEl = document.getElementById('pnlStats');
            if (statsEl) {
                statsEl.textContent = `Total: ${pnlTotalCount} records`;
            }
        }

        function renderPnlHistory(positions) {
            const tbody = document.getElementById('pnlHistoryBody');
            const colSpan = pnlCurrentTab === 'positions' ? 10 : 8;

            if (!positions || positions.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${colSpan}" class="empty-cell">No closed positions found</td></tr>`;
                return;
            }

            if (pnlCurrentTab === 'positions') {
                // Closed Positions 탭 (Bybit 스타일)
                tbody.innerHTML = positions.map(pos => {
                    const pnlClass = pos.realized_pnl >= 0 ? 'pnl-positive' : 'pnl-negative';
                    const dirClass = pos.side === 'long' ? 'side-long' : 'side-short';
                    const qty = pos.size > 0 ? pos.size : (pos.margin / pos.entry_price * pos.leverage);
                    const fee = pos.total_fee / 2;
                    const qtyUsdt = qty * pos.exit_price;
                    const baseCoin = pos.symbol.replace('USDT', '');
                    const pnlKrw = pos.realized_pnl * USD_KRW;

                    return `
                        <tr>
                            <td>
                                <div class="symbol-cell">
                                    <span class="symbol-name">${pos.symbol}</span>
                                    <span class="symbol-type">Perp</span>
                                </div>
                            </td>
                            <td>USDT Perp</td>
                            <td class="${dirClass}">${pos.side.charAt(0).toUpperCase() + pos.side.slice(1)}</td>
                            <td>${formatPrice(pos.entry_price, pos.symbol)}</td>
                            <td>${formatPrice(pos.exit_price, pos.symbol)}</td>
                            <td>
                                <div class="qty-cell">
                                    <span>${formatQty(qty)} ${baseCoin}</span>
                                    <span class="qty-usdt">${formatNum(qtyUsdt)} USDT</span>
                                </div>
                            </td>
                            <td class="${pnlClass}">
                                <div class="pnl-cell">
                                    <span>${formatPnl(pos.realized_pnl)}</span>
                                    <span class="pnl-krw">${formatKrw(pos.realized_pnl)} KRW</span>
                                </div>
                            </td>
                            <td>${formatFee(fee)}</td>
                            <td>${formatFee(fee)}</td>
                            <td>${formatFee(0)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                // Closed Orders 탭
                tbody.innerHTML = positions.map(pos => {
                    const pnlClass = pos.realized_pnl >= 0 ? 'pnl-positive' : 'pnl-negative';
                    const openedAt = formatDateTime(pos.opened_at);
                    const closedAt = formatDateTime(pos.closed_at);
                    const fee = pos.total_fee / 2;

                    return `
                        <tr>
                            <td>
                                <div class="symbol-cell">
                                    <span class="symbol-name">${pos.symbol}</span>
                                    <span class="symbol-type">Perp</span>
                                </div>
                            </td>
                            <td class="${pnlClass}">
                                <div class="pnl-cell">
                                    <span>${formatPnl(pos.realized_pnl)}</span>
                                    <span class="pnl-krw">${formatKrw(pos.realized_pnl)} KRW</span>
                                </div>
                            </td>
                            <td>${formatFee(fee)}</td>
                            <td>${formatFee(fee)}</td>
                            <td>${formatFee(0)}</td>
                            <td class="date-cell">${openedAt}</td>
                            <td class="duration-cell">${pos.duration}</td>
                            <td class="date-cell">${closedAt}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        function viewPnlDetails(positionId) {
            alert('Position ID: ' + positionId + '\n\n상세보기 기능 구현 예정');
        }

        function formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleString('ko-KR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function renderPnlPagination() {
            const container = document.getElementById('pnlPagination');
            if (!container) return;

            const totalPages = Math.ceil(pnlTotalCount / pnlPageSize);
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            const currentPage = pnlCurrentPage + 1;

            container.innerHTML = `
                <button onclick="loadPnlHistory(0)" ${pnlCurrentPage === 0 ? 'disabled' : ''}>First</button>
                <button onclick="loadPnlHistory(${pnlCurrentPage - 1})" ${pnlCurrentPage === 0 ? 'disabled' : ''}>Prev</button>
                <span class="page-info">${currentPage} / ${totalPages} (Total: ${pnlTotalCount})</span>
                <button onclick="loadPnlHistory(${pnlCurrentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}>Next</button>
                <button onclick="loadPnlHistory(${totalPages - 1})" ${currentPage >= totalPages ? 'disabled' : ''}>Last</button>
            `;
        }

        // ========================================
        // 인증 관련 변수 및 함수
        // ========================================
        const AUTH_API = 'auth_api.php';
        const TRADE_API = 'trade_api.php';

        let authToken = localStorage.getItem('auth_token');
        let currentUser = null;
        let userLeverage = 10;

        // 거래 피드 표시
        function showTradeFeed(trade) {
            const feedContainer = document.getElementById('tradeFeedList');
            if (!feedContainer) return;

            const typeLabels = {
                'FILLED': '체결',
                'OPEN_POSITION': '포지션 오픈',
                'CLOSE_POSITION': '포지션 청산',
                'LIQUIDATION': '강제 청산'
            };

            const item = document.createElement('div');
            item.className = 'trade-feed-item ' + (trade.side === 'long' ? 'buy' : 'sell');
            item.innerHTML = `
                <span class="feed-type">${typeLabels[trade.type] || trade.type}</span>
                <span class="feed-user">${trade.nickname}</span>
                <span class="feed-side ${trade.side}">${trade.side?.toUpperCase()}</span>
                <span class="feed-qty">${trade.quantity}</span>
                ${trade.pnl ? `<span class="feed-pnl ${trade.pnl > 0 ? 'profit' : 'loss'}">${trade.pnl > 0 ? '+' : ''}${trade.pnl.toFixed(2)}</span>` : ''}
            `;

            feedContainer.insertBefore(item, feedContainer.firstChild);

            // 최대 20개 유지
            while (feedContainer.children.length > 20) {
                feedContainer.removeChild(feedContainer.lastChild);
            }

            // 애니메이션
            item.style.animation = 'fadeInSlide 0.3s ease-out';
        }

        // 강제 청산 알림
        function showLiquidationAlert(data) {
            showToast(`${data.nickname}님 ${data.side.toUpperCase()} 포지션 강제 청산! ${data.size} BTC`, 'warning');
        }

        // WebSocket 인증 갱신
        function updateWSAuth() {
            if (socket && socket.connected && currentUser) {
                socket.emit('auth', {
                    userId: currentUser.id,
                    email: currentUser.email,
                    nickname: currentUser.nickname || currentUser.email,
                    token: authToken,
                    currentPage: 'trade',
                    userAgent: navigator.userAgent,
                    screenSize: `${window.screen.width}x${window.screen.height}`,
                    language: navigator.language
                });
            }
        }

        // 모달 관리
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function switchModal(fromId, toId) {
            closeModal(fromId);
            openModal(toId);
        }

        // 로그인 버튼 이벤트
        document.getElementById('loginBtn')?.addEventListener('click', () => openModal('loginModal'));
        document.getElementById('registerBtn')?.addEventListener('click', () => openModal('registerModal'));
        document.getElementById('logoutBtn')?.addEventListener('click', handleLogout);

        // 모달 외부 클릭시 닫기
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal(modal.id);
            });
        });

        // ========================================
        // 로그인 처리
        // ========================================
        async function handleLogin() {
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const errorEl = document.getElementById('loginError');

            if (!email || !password) {
                errorEl.textContent = '이메일과 비밀번호를 입력해주세요.';
                return;
            }

            try {
                const response = await fetch(`${AUTH_API}?action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    authToken = data.data.token;
                    currentUser = data.data.user;
                    localStorage.setItem('auth_token', authToken);

                    updateAuthUI(true);
                    closeModal('loginModal');

                    // 잔고 업데이트
                    if (data.data.wallets) {
                        updateWalletDisplay(data.data.wallets);
                    }

                    // 포지션 및 주문 로드
                    loadUserPositions();
                    loadUserOrders();

                    // WebSocket 인증 갱신
                    updateWSAuth();

                    showToast('로그인 성공!', 'success');
                } else {
                    errorEl.textContent = data.error || '로그인에 실패했습니다.';
                }
            } catch (error) {
                errorEl.textContent = '서버 연결에 실패했습니다.';
                console.error('Login error:', error);
            }
        }

        // ========================================
        // 회원가입 처리
        // ========================================
        async function handleRegister() {
            const email = document.getElementById('registerEmail').value;
            const nickname = document.getElementById('registerNickname').value;
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('registerConfirmPassword').value;
            const referralCode = document.getElementById('registerReferral').value;
            const errorEl = document.getElementById('registerError');

            if (!email || !nickname || !password) {
                errorEl.textContent = '필수 항목을 모두 입력해주세요.';
                return;
            }

            if (password !== confirmPassword) {
                errorEl.textContent = '비밀번호가 일치하지 않습니다.';
                return;
            }

            try {
                const response = await fetch(`${AUTH_API}?action=register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email, nickname, password,
                        confirm_password: confirmPassword,
                        referral_code: referralCode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    switchModal('registerModal', 'loginModal');
                    document.getElementById('loginEmail').value = email;
                } else {
                    errorEl.textContent = data.error || '회원가입에 실패했습니다.';
                }
            } catch (error) {
                errorEl.textContent = '서버 연결에 실패했습니다.';
                console.error('Register error:', error);
            }
        }

        // ========================================
        // 로그아웃 처리
        // ========================================
        async function handleLogout() {
            try {
                await fetch(`${AUTH_API}?action=logout`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
            } catch (e) {}

            authToken = null;
            currentUser = null;
            localStorage.removeItem('auth_token');
            updateAuthUI(false);
            showToast('로그아웃되었습니다.', 'info');
        }

        // ========================================
        // 세션 확인
        // ========================================
        async function checkSession() {
            if (!authToken) return;

            try {
                const response = await fetch(`${AUTH_API}?action=me`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });

                const data = await response.json();

                if (data.success) {
                    currentUser = data.data.user;
                    updateAuthUI(true);
                    // 먼저 기본 wallet 표시 후, 상세 balance 로드
                    updateWalletDisplay(data.data.wallets);
                    // loadUserBalance는 아래에서 정의되므로 setTimeout으로 호출
                    setTimeout(() => {
                        if (typeof loadUserBalance === 'function') {
                            loadUserBalance();
                        }
                    }, 100);
                    loadUserPositions();
                    loadUserOrders();

                    // WebSocket 인증 전송
                    updateWSAuth();
                } else {
                    handleLogout();
                }
            } catch (error) {
                console.error('Session check error:', error);
            }
        }

        // ========================================
        // UI 업데이트
        // ========================================
        function updateAuthUI(isLoggedIn) {
            const authButtons = document.getElementById('authButtons');
            const userInfo = document.getElementById('userInfo');
            const userName = document.getElementById('userName');

            if (isLoggedIn && currentUser) {
                if (authButtons) authButtons.style.display = 'none';
                if (userInfo) userInfo.style.display = 'flex';
                if (userName) userName.textContent = currentUser.nickname;
            } else {
                if (authButtons) authButtons.style.display = 'flex';
                if (userInfo) userInfo.style.display = 'none';
            }
        }

        // 전역 wallet 데이터 저장
        let currentWallets = [];

        function updateWalletDisplay(wallets, balanceData = null) {
            // 전역 wallet 데이터 저장
            currentWallets = wallets;

            const usdtWallet = wallets.find(w => w.currency === 'USDT');
            if (usdtWallet) {
                const walletBalance = parseFloat(usdtWallet.balance) || 0;

                // 바이낸스 스타일 계산 (API에서 계산된 값 사용)
                let marginBalance = walletBalance;
                let availableBalance = parseFloat(usdtWallet.available_balance) || 0;
                let initialMargin = 0;
                let maintenanceMargin = 0;
                let unrealizedPnl = 0;

                // API 응답에서 계산된 비율 가져오기
                let imRatio = 0;
                let mmRatio = 0;

                if (balanceData) {
                    // API에서 계산된 값 사용 (바이낸스 스타일)
                    marginBalance = parseFloat(balanceData.margin_balance) || walletBalance;
                    availableBalance = parseFloat(balanceData.available_balance) || availableBalance;
                    // Total IM = Position IM + Order IM
                    initialMargin = parseFloat(balanceData.total_im) || parseFloat(balanceData.position_margin) || 0;
                    maintenanceMargin = parseFloat(balanceData.maintenance_margin) || 0;
                    unrealizedPnl = parseFloat(balanceData.unrealized_pnl) || 0;
                    // API에서 계산된 비율 직접 사용
                    imRatio = parseFloat(balanceData.im_ratio) || 0;
                    mmRatio = parseFloat(balanceData.mm_ratio) || 0;
                }

                // 전역 변수 업데이트 (주문 패널용 + WebSocket용)
                userBalance = walletBalance;
                userAvailableBalance = availableBalance;
                userMarginBalance = marginBalance;
                userUnrealizedPnl = unrealizedPnl;

                // 헤더 잔고 표시
                const userBalanceEl = document.getElementById('userBalance');
                if (userBalanceEl) {
                    userBalanceEl.textContent = marginBalance.toFixed(2) + ' USDT';
                }

                // 계좌 정보 업데이트 (바이낸스 스타일)
                const marginBalanceEl = document.getElementById('marginBalanceDisplay');
                const availableBalanceEl = document.getElementById('availableBalanceDisplay');

                // 숫자 포맷 함수 (간결하게)
                const formatBalance = (val) => {
                    return val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                };

                // Margin Balance = Wallet Balance + Unrealized PNL (실질 계정 가치)
                if (marginBalanceEl) {
                    marginBalanceEl.textContent = formatBalance(marginBalance) + ' USDT';
                }
                // Available Balance = Margin Balance - Initial Margin (새 포지션에 사용 가능한 금액)
                if (availableBalanceEl) {
                    availableBalanceEl.textContent = formatBalance(availableBalance) + ' USDT';
                }

                // 전역 변수에 저장 (실시간 PNL 업데이트용)
                window.currentWalletBalance = parseFloat(balanceData?.wallet_balance) || walletBalance;
                window.currentInitialMargin = initialMargin;
                window.currentMaintenanceMargin = maintenanceMargin;

                // 미실현 PNL 표시
                updateUnrealizedPnlDisplay(unrealizedPnl);

                // 마진 비율 표시 (API에서 계산된 비율 사용, 또는 직접 계산)
                if (imRatio > 0 || mmRatio > 0) {
                    updateMarginBars(imRatio, mmRatio);
                } else if (marginBalance > 0 && initialMargin > 0) {
                    const calcImRatio = (initialMargin / marginBalance) * 100;
                    const calcMmRatio = (maintenanceMargin / marginBalance) * 100;
                    updateMarginBars(calcImRatio, calcMmRatio);
                } else {
                    updateMarginBars(0, 0);
                }

                // 주문 패널 정보 업데이트
                updateOrderInfo();
            }

            // Assets 탭이 활성화되어 있으면 테이블도 업데이트
            const assetsPanel = document.getElementById('assetsPanel');
            if (assetsPanel && assetsPanel.classList.contains('active')) {
                renderAssetsTable(wallets);
            }
        }

        // 이전 PNL 값 캐싱 (깜빡임 방지)
        let lastValidPnl = null;

        // 미실현 PNL 표시 업데이트 (Margin Balance, Available Balance도 함께 업데이트)
        function updateUnrealizedPnlDisplay(pnl) {
            const pnlEl = document.getElementById('unrealizedPnlDisplay');
            if (!pnlEl) return;

            const pnlValue = parseFloat(pnl);

            // 0이거나 NaN이면 이전 값 유지 (깜빡임 방지)
            // 단, 실제로 포지션이 없어서 0인 경우는 예외
            if ((isNaN(pnlValue) || pnlValue === 0) && lastValidPnl !== null && window.currentInitialMargin > 0) {
                return; // 이전 값 유지
            }

            // 유효한 값이면 캐시 업데이트
            if (!isNaN(pnlValue)) {
                lastValidPnl = pnlValue;
                userUnrealizedPnl = pnlValue; // 전역 변수도 업데이트
            }

            const displayValue = lastValidPnl !== null ? lastValidPnl : 0;
            pnlEl.textContent = (displayValue >= 0 ? '+' : '') + displayValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT';

            // 색상 클래스 적용
            pnlEl.classList.remove('positive', 'negative');
            if (displayValue > 0) {
                pnlEl.classList.add('positive');
            } else if (displayValue < 0) {
                pnlEl.classList.add('negative');
            }

            // Margin Balance = Wallet Balance + Unrealized PNL (실시간 업데이트)
            const walletBalance = window.currentWalletBalance || 0;
            const initialMargin = window.currentInitialMargin || 0;
            const maintenanceMargin = window.currentMaintenanceMargin || 0;

            if (walletBalance > 0) {
                const marginBalance = walletBalance + displayValue;
                const availableBalance = marginBalance - initialMargin;

                const marginBalanceEl = document.getElementById('marginBalanceDisplay');
                const availableBalanceEl = document.getElementById('availableBalanceDisplay');
                const orderAvailable = document.getElementById('orderAvailable');

                const formatBalance = (val) => val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                if (marginBalanceEl) {
                    marginBalanceEl.textContent = formatBalance(marginBalance) + ' USDT';
                }
                if (availableBalanceEl) {
                    availableBalanceEl.textContent = formatBalance(availableBalance) + ' USDT';
                }
                // 주문창 Available도 동일하게 업데이트 (미실현 PNL 포함)
                if (orderAvailable) {
                    orderAvailable.textContent = formatBalance(availableBalance) + ' USDT';
                }

                // 마진 비율도 실시간 업데이트
                if (marginBalance > 0) {
                    const initialMarginRatio = (initialMargin / marginBalance) * 100;
                    const maintenanceMarginRatio = (maintenanceMargin / marginBalance) * 100;
                    updateMarginBars(initialMarginRatio, maintenanceMarginRatio);
                }
            }
        }

        // 마진 바 업데이트
        function updateMarginBars(initialMargin, maintenanceMargin) {
            const initialBar = document.getElementById('initialMarginBar');
            const initialPercent = document.getElementById('initialMarginPercent');
            const maintenanceBar = document.getElementById('maintenanceMarginBar');
            const maintenancePercent = document.getElementById('maintenanceMarginPercent');

            // 최대 100%로 제한
            const initialCapped = Math.min(100, Math.max(0, initialMargin));
            const maintenanceCapped = Math.min(100, Math.max(0, maintenanceMargin));

            if (initialBar) {
                initialBar.style.width = initialCapped + '%';
                // 위험도에 따른 색상 변경
                initialBar.classList.remove('green', 'yellow', 'red');
                if (initialCapped >= 80) {
                    initialBar.classList.add('red');
                } else if (initialCapped >= 50) {
                    initialBar.classList.add('yellow');
                } else {
                    initialBar.classList.add('green');
                }
            }
            if (initialPercent) {
                initialPercent.textContent = initialCapped.toFixed(2) + '%';
            }

            if (maintenanceBar) {
                maintenanceBar.style.width = maintenanceCapped + '%';
                maintenanceBar.classList.remove('green', 'yellow', 'red');
                if (maintenanceCapped >= 80) {
                    maintenanceBar.classList.add('red');
                } else if (maintenanceCapped >= 50) {
                    maintenanceBar.classList.add('yellow');
                } else {
                    maintenanceBar.classList.add('green');
                }
            }
            if (maintenancePercent) {
                maintenancePercent.textContent = maintenanceCapped.toFixed(2) + '%';
            }
        }

        // ========================================
        // 거래 기능 - 주문 생성
        // ========================================
        async function placeOrder(side, positionSide) {
            if (!authToken) {
                openModal('loginModal');
                return;
            }

            // order-type-tab에서 현재 선택된 주문 유형 가져오기
            const activeTab = document.querySelector('.order-type-tab.active');
            const orderType = activeTab?.textContent.trim().toLowerCase() || 'limit';
            const isMarketOrder = orderType === 'market';

            const priceInput = document.getElementById('priceInput');
            const quantityInput = document.getElementById('quantityInput');
            const quantity = parseFloat(quantityInput?.value) || 0;

            // 시장가 주문이면 현재가 사용, 지정가면 입력값 사용
            let price;
            if (isMarketOrder) {
                price = currentPrice; // 시장가는 현재가 사용
            } else {
                price = parseFloat(priceInput.value.replace(/,/g, ''));
            }

            if (quantity <= 0) {
                showToast('주문 수량을 입력해주세요.', 'error');
                return;
            }

            // 지정가 주문일 때만 가격 검증
            if (!isMarketOrder && price <= 0) {
                showToast('주문 가격을 입력해주세요.', 'error');
                return;
            }

            // 마진 계산
            const positionValue = quantity * price;
            const requiredMargin = positionValue / userLeverage;

            if (requiredMargin > userAvailableBalance) {
                showToast('잔고가 부족합니다.', 'error');
                return;
            }

            try {
                const response = await fetch(`${TRADE_API}?action=place_order`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${authToken}`
                    },
                    body: JSON.stringify({
                        symbol: currentSymbol.toUpperCase(),
                        side: side,
                        position_side: positionSide,
                        order_type: isMarketOrder ? 'market' : 'limit',
                        quantity: quantity.toFixed(6),
                        price: price,
                        leverage: userLeverage,
                        market_price: currentPrice // WebSocket 실시간 가격
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');

                    // 시장가 주문은 즉시 체결되므로 소리 재생
                    if (isMarketOrder) {
                        playOrderFilledSound();
                    }

                    // 지정가 주문이고 pending/open 상태면 오더북에 표시
                    if (!isMarketOrder && data.data?.order?.status !== 'filled') {
                        addUserOrder(side, price, quantity);
                    }

                    loadUserBalance();
                    loadUserPositions();
                    loadUserOrders();
                } else {
                    showToast(data.error || '주문 실패', 'error');
                }
            } catch (error) {
                showToast('주문 처리 중 오류가 발생했습니다.', 'error');
                console.error('Order error:', error);
            }
        }

        // Long/Short 버튼 이벤트 연결
        document.querySelector('.action-btn.long')?.addEventListener('click', () => {
            placeOrder('buy', 'long');
        });

        document.querySelector('.action-btn.short')?.addEventListener('click', () => {
            placeOrder('sell', 'short');
        });

        // ========================================
        // 사용자 데이터 로드
        // ========================================
        async function loadUserBalance() {
            if (!authToken) return;

            try {
                const response = await fetch(`${TRADE_API}?action=get_balance`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });

                const data = await response.json();
                if (data.success) {
                    // balanceData 전체를 전달하여 마진/PNL 정보도 업데이트
                    updateWalletDisplay(data.data.wallets, data.data);
                }
            } catch (error) {
                console.error('Load balance error:', error);
            }
        }

        // Assets 탭 데이터 로드
        async function loadAssetsData() {
            if (!authToken) return;

            // 이미 캐시된 데이터가 있으면 바로 표시
            if (currentWallets.length > 0) {
                renderAssetsTable(currentWallets);
            }

            // 최신 데이터 가져오기
            try {
                const response = await fetch(`${TRADE_API}?action=get_balance`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });

                const data = await response.json();
                if (data.success) {
                    currentWallets = data.data.wallets;
                    renderAssetsTable(data.data.wallets);
                    // 헤더 잔고도 업데이트 (balanceData 포함)
                    updateWalletDisplay(data.data.wallets, data.data);
                }
            } catch (error) {
                console.error('Load assets error:', error);
            }
        }

        // 코인 아이콘 URL 가져오기 (DB symbolConfigs 활용)
        function getCoinIconUrl(currency) {
            const symbol = currency + 'USDT';
            return symbolConfigs[symbol]?.logo || 'https://assets.coingecko.com/coins/images/1/small/bitcoin.png';
        }

        // Assets 테이블 렌더링
        function renderAssetsTable(wallets) {
            const tbody = document.getElementById('assetsBody');
            if (!tbody) return;

            // 잔고가 0보다 큰 코인만 필터링
            const activeWallets = wallets.filter(wallet => {
                const balance = parseFloat(wallet.balance) || 0;
                const availableBalance = parseFloat(wallet.available_balance) || 0;
                return balance > 0 || availableBalance > 0;
            });

            if (!activeWallets || activeWallets.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="empty-row">No assets found</td></tr>';
                return;
            }

            tbody.innerHTML = activeWallets.map(wallet => {
                const balance = parseFloat(wallet.balance) || 0;
                const availableBalance = parseFloat(wallet.available_balance) || balance;
                const marginUsed = parseFloat(wallet.margin_used) || (balance - availableBalance);

                // USD Value 계산 (USDT는 1:1, 다른 코인은 현재가 기준)
                let usdValue = balance;
                if (wallet.currency !== 'USDT' && currentPrice > 0) {
                    // BTC 등 다른 코인의 경우 현재가로 환산
                    if (wallet.currency === 'BTC') {
                        usdValue = balance * currentPrice;
                    }
                }

                const iconUrl = getCoinIconUrl(wallet.currency);

                return `
                    <tr>
                        <td>
                            <div class="asset-coin">
                                <img src="${iconUrl}" alt="${wallet.currency}" class="coin-icon-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span class="coin-icon-fallback" style="display:none;">${wallet.currency.charAt(0)}</span>
                                <span class="coin-name">${wallet.currency}</span>
                            </div>
                        </td>
                        <td class="num-cell">${balance.toFixed(4)}</td>
                        <td class="num-cell">${availableBalance.toFixed(4)}</td>
                        <td class="num-cell">${marginUsed.toFixed(4)}</td>
                        <td class="num-cell">$${usdValue.toFixed(2)}</td>
                    </tr>
                `;
            }).join('');
        }

        async function loadUserPositions() {
            if (!authToken) return;

            try {
                const response = await fetch(`${TRADE_API}?action=get_positions`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });

                const data = await response.json();
                if (data.success) {
                    renderPositions(data.data.positions);

                    // 포지션 심볼들 실시간 구독 (거래소 방식)
                    if (socket && socket.connected && data.data.positions.length > 0) {
                        const positionSymbols = [...new Set(data.data.positions.map(p => p.symbol))];
                        socket.emit('subscribe:positions', { symbols: positionSymbols });
                    }

                    // 탭 카운트 업데이트
                    const posTab = document.querySelectorAll('.panel-tab')[1];
                    if (posTab) {
                        posTab.innerHTML = `Positions <span class="tab-count">${data.data.count}</span>`;
                    }

                    // 차트 마커 업데이트 (Lightweight Charts 모드일 때)
                    updateChartMarkers();
                }
            } catch (error) {
                console.error('Load positions error:', error);
            }
        }

        async function loadUserOrders() {
            if (!authToken) return;

            try {
                const response = await fetch(`${TRADE_API}?action=get_open_orders`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });

                const data = await response.json();
                if (data.success) {
                    renderOpenOrders(data.data.orders);

                    // userOrders 동기화 (오더북 표시용)
                    syncUserOrdersFromAPI(data.data.orders);

                    // 탭 카운트 업데이트
                    const orderTab = document.querySelectorAll('.panel-tab')[0];
                    if (orderTab) {
                        orderTab.innerHTML = `Open Orders <span class="tab-count">${data.data.count}</span>`;
                    }

                    // 차트 마커 업데이트 (Lightweight Charts 모드일 때)
                    updateChartMarkers();

                    // 오더북 리렌더링 (내수량 반영)
                    if (rawAsksData.length > 0 || rawBidsData.length > 0) {
                        applyPrecisionAndRender();
                    }
                }
            } catch (error) {
                console.error('Load orders error:', error);
            }
        }

        // API에서 받은 주문으로 userOrders 동기화
        function syncUserOrdersFromAPI(orders) {
            // 초기화
            userOrders.asks = [];
            userOrders.bids = [];

            // 현재 심볼 주문만 필터링하여 추가
            orders.forEach(order => {
                if (order.symbol.toUpperCase() === currentSymbol.toUpperCase()) {
                    const orderData = {
                        orderId: order.order_id,
                        price: parseFloat(order.price),
                        qty: parseFloat(order.quantity),
                        total: 0
                    };

                    if (order.side === 'sell') {
                        userOrders.asks.push(orderData);
                    } else {
                        userOrders.bids.push(orderData);
                    }
                }
            });
        }

        // ========================================
        // 렌더링 함수
        // ========================================
        // 포지션 데이터 저장 (실시간 업데이트용)
        let userPositions = [];

        // 심볼별 로고 URL (DB symbolConfigs 활용)
        function getSymbolLogo(symbol) {
            return symbolConfigs[symbol]?.logo || '';
        }

        function renderPositions(positions) {
            const tbody = document.getElementById('positionsBody');
            if (!tbody) return;

            userPositions = positions; // 저장

            if (positions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-cell">포지션이 없습니다</td></tr>';
                return;
            }

            tbody.innerHTML = positions.map(p => {
                const pnlClass = parseFloat(p.unrealized_pnl) >= 0 ? 'up' : 'down';
                const logoUrl = getSymbolLogo(p.symbol);
                const size = parseFloat(p.size);
                const markPrice = parseFloat(p.mark_price) || 0;
                const sizeUsdt = size * markPrice;
                const pnlUsdt = parseFloat(p.unrealized_pnl);
                const pnlKrw = pnlUsdt * USD_KRW;
                const roe = parseFloat(p.roe);
                const formatNum = (n, d = 2) => n.toLocaleString('en-US', {minimumFractionDigits: d, maximumFractionDigits: d});
                const formatKrw = (n) => Math.round(n).toLocaleString('ko-KR');
                const coinName = p.symbol.replace('USDT', '');
                return `
                    <tr data-position-id="${p.id}" data-symbol="${p.symbol}" data-side="${p.side}" data-entry="${p.entry_price}">
                        <td>
                            <div class="coin-cell symbol-link" onclick="switchToSymbol('${p.symbol}')" style="cursor: pointer;">
                                <img src="${logoUrl}" alt="${p.symbol}" class="position-coin-logo" style="width: 20px; height: 20px; margin-right: 6px; vertical-align: middle;">${p.symbol}
                            </div>
                        </td>
                        <td class="${p.side}">
                            <div>${p.side === 'long' ? '+' : '-'}${formatNum(size, 2)} ${coinName}</div>
                            <div style="font-size: 11px; color: #888;">${formatNum(sizeUsdt, 2)} USDT</div>
                        </td>
                        <td>${formatPrice(parseFloat(p.entry_price), p.symbol)}</td>
                        <td class="mark-price-cell">${formatPrice(markPrice, p.symbol)}</td>
                        <td>${formatPrice(parseFloat(p.liquidation_price), p.symbol)}</td>
                        <td>${formatNum(parseFloat(p.margin))} USDT</td>
                        <td class="pnl-cell ${pnlClass}">
                            <div>${pnlUsdt >= 0 ? '+' : ''}${formatNum(pnlUsdt)} USDT (${formatNum(roe)}%)</div>
                            <div style="font-size: 11px; color: #888;">₩${pnlKrw >= 0 ? '+' : ''}${formatKrw(pnlKrw)} KRW</div>
                        </td>
                        <td>
                            <button class="close-position-btn" onclick="closePosition('${p.id}', '${p.symbol}', '${p.side}', ${p.size})">
                                닫기
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // 포지션 닫기 함수
        async function closePosition(positionId, symbol, side, size) {
            if (!confirm(`${symbol} ${side.toUpperCase()} 포지션 ${size} BTC를 시장가로 청산하시겠습니까?`)) {
                return;
            }

            try {
                const response = await fetch(TRADE_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${authToken}`
                    },
                    body: JSON.stringify({
                        action: 'close_position',
                        position_id: positionId,
                        symbol: symbol,
                        market_price: currentPrice // WebSocket 실시간 가격
                    })
                });

                const data = await response.json();
                if (data.success) {
                    playOrderFilledSound(); // 청산 완료 소리
                    showToast('포지션이 청산되었습니다.', 'success');
                    loadUserPositions();
                    loadUserBalance();
                } else {
                    showToast('오류: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Close position error:', error);
                alert('포지션 청산 중 오류가 발생했습니다.');
            }
        }

        // 포지션 실시간 가격/PnL 업데이트
        function updatePositionsPrices(newPrice) {
            if (!userPositions || userPositions.length === 0) return;

            let totalUnrealizedPnl = 0; // 총 미실현 PNL

            const rows = document.querySelectorAll('#positionsBody tr[data-position-id]');
            rows.forEach(row => {
                const symbol = row.dataset.symbol;
                const side = row.dataset.side;
                const entryPrice = parseFloat(row.dataset.entry);
                const position = userPositions.find(p => p.id == row.dataset.positionId);
                if (!position) return;

                const size = parseFloat(position.size);
                const leverage = parseFloat(position.leverage) || 10;

                // 현재 심볼이면 새 가격 사용, 아니면 기존 저장된 가격 사용
                const priceToUse = (symbol === currentSymbol) ? newPrice : (allSymbolPrices[symbol] || parseFloat(position.mark_price));

                // PnL 계산
                let pnl = 0;
                if (side === 'long') {
                    pnl = (priceToUse - entryPrice) * size;
                } else {
                    pnl = (entryPrice - priceToUse) * size;
                }

                // 총 PNL에 합산
                totalUnrealizedPnl += pnl;

                const margin = parseFloat(position.margin);
                const roe = margin > 0 ? (pnl / margin) * 100 : 0;

                // 현재 활성 심볼의 포지션만 DOM 업데이트
                if (symbol === currentSymbol) {
                    const markPriceCell = row.querySelector('.mark-price-cell');
                    const pnlCell = row.querySelector('.pnl-cell');
                    const sizeCell = row.querySelector('td:nth-child(2)');

                    if (markPriceCell) {
                        markPriceCell.textContent = formatPrice(newPrice, symbol);
                    }

                    if (sizeCell) {
                        const coinName = symbol.replace('USDT', '');
                        const sizeUsdt = size * priceToUse;
                        const fmtSize = size.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const fmtSizeUsdt = sizeUsdt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        sizeCell.innerHTML = `<div>${side === 'long' ? '+' : '-'}${fmtSize} ${coinName}</div><div style="font-size: 11px; color: #888;">${fmtSizeUsdt} USDT</div>`;
                    }

                    if (pnlCell) {
                        pnlCell.className = `pnl-cell ${pnl >= 0 ? 'up' : 'down'}`;
                        const fmtPnl = pnl.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const fmtRoe = roe.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const pnlKrwVal = pnl * USD_KRW;
                        const fmtKrw = Math.round(pnlKrwVal).toLocaleString('ko-KR');
                        pnlCell.innerHTML = `<div>${pnl >= 0 ? '+' : ''}${fmtPnl} USDT (${fmtRoe}%)</div><div style="font-size: 11px; color: #888;">₩${pnlKrwVal >= 0 ? '+' : ''}${fmtKrw} KRW</div>`;
                    }
                }
            });

            // 총 미실현 PNL 업데이트
            updateUnrealizedPnlDisplay(totalUnrealizedPnl);
        }

        // 모든 심볼의 포지션 가격/PnL 업데이트
        function updateAllPositionsPrices(allPrices) {
            if (!userPositions || userPositions.length === 0) {
                // 포지션이 없으면 PNL을 0으로
                updateUnrealizedPnlDisplay(0);
                return;
            }

            let totalUnrealizedPnl = 0; // 총 미실현 PNL

            const rows = document.querySelectorAll('#positionsBody tr[data-position-id]');
            rows.forEach(row => {
                const symbol = row.dataset.symbol;
                const price = allPrices[symbol];
                if (!price) return;

                const side = row.dataset.side;
                const entryPrice = parseFloat(row.dataset.entry);
                const position = userPositions.find(p => p.id == row.dataset.positionId);
                if (!position) return;

                const size = parseFloat(position.size);
                const leverage = parseFloat(position.leverage) || 10;

                // PnL 계산
                let pnl = 0;
                if (side === 'long') {
                    pnl = (price - entryPrice) * size;
                } else {
                    pnl = (entryPrice - price) * size;
                }

                // 총 PNL에 합산
                totalUnrealizedPnl += pnl;

                const margin = parseFloat(position.margin);
                const roe = margin > 0 ? (pnl / margin) * 100 : 0;

                // DOM 업데이트
                const markPriceCell = row.querySelector('.mark-price-cell');
                const pnlCell = row.querySelector('.pnl-cell');
                const sizeCell = row.querySelector('td:nth-child(2)');

                if (markPriceCell) {
                    markPriceCell.textContent = formatPrice(price, symbol);
                }

                if (sizeCell) {
                    const coinName = symbol.replace('USDT', '');
                    const sizeUsdt = size * price;
                    const fmtSize = size.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    const fmtSizeUsdt = sizeUsdt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    sizeCell.innerHTML = `<div>${side === 'long' ? '+' : '-'}${fmtSize} ${coinName}</div><div style="font-size: 11px; color: #888;">${fmtSizeUsdt} USDT</div>`;
                }

                if (pnlCell) {
                    pnlCell.className = `pnl-cell ${pnl >= 0 ? 'up' : 'down'}`;
                    const fmtPnl = pnl.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    const fmtRoe = roe.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    const pnlKrwVal = pnl * USD_KRW;
                    const fmtKrw = Math.round(pnlKrwVal).toLocaleString('ko-KR');
                    pnlCell.innerHTML = `<div>${pnl >= 0 ? '+' : ''}${fmtPnl} USDT (${fmtRoe}%)</div><div style="font-size: 11px; color: #888;">₩${pnlKrwVal >= 0 ? '+' : ''}${fmtKrw} KRW</div>`;
                }
            });

            // 총 미실현 PNL 업데이트
            updateUnrealizedPnlDisplay(totalUnrealizedPnl);
        }

        // 단일 심볼 포지션 실시간 업데이트 (거래소 방식)
        function updateSinglePositionPrice(symbol, price) {
            if (!userPositions || userPositions.length === 0) return;

            let totalUnrealizedPnl = 0;

            const rows = document.querySelectorAll('#positionsBody tr[data-position-id]');
            rows.forEach(row => {
                const rowSymbol = row.dataset.symbol;
                const position = userPositions.find(p => p.id == row.dataset.positionId);
                if (!position) return;

                const side = row.dataset.side;
                const entryPrice = parseFloat(row.dataset.entry);
                const size = parseFloat(position.size);
                const margin = parseFloat(position.margin);

                // 해당 심볼이면 새 가격, 아니면 저장된 가격 사용
                const priceToUse = (rowSymbol === symbol) ? price : (allSymbolPrices[rowSymbol] || parseFloat(position.mark_price));

                // PnL 계산
                let pnl = 0;
                if (side === 'long') {
                    pnl = (priceToUse - entryPrice) * size;
                } else {
                    pnl = (entryPrice - priceToUse) * size;
                }
                totalUnrealizedPnl += pnl;

                // 해당 심볼 row만 DOM 업데이트
                if (rowSymbol === symbol) {
                    const roe = margin > 0 ? (pnl / margin) * 100 : 0;
                    const markPriceCell = row.querySelector('.mark-price-cell');
                    const pnlCell = row.querySelector('.pnl-cell');
                    const sizeCell = row.querySelector('td:nth-child(2)');

                    if (markPriceCell) {
                        markPriceCell.textContent = formatPrice(price, symbol);
                    }

                    if (sizeCell) {
                        const coinName = symbol.replace('USDT', '');
                        const sizeUsdt = size * price;
                        const fmtSize = size.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const fmtSizeUsdt = sizeUsdt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        sizeCell.innerHTML = `<div>${side === 'long' ? '+' : '-'}${fmtSize} ${coinName}</div><div style="font-size: 11px; color: #888;">${fmtSizeUsdt} USDT</div>`;
                    }

                    if (pnlCell) {
                        pnlCell.className = `pnl-cell ${pnl >= 0 ? 'up' : 'down'}`;
                        const fmtPnl = pnl.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const fmtRoe = roe.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const pnlKrwVal = pnl * USD_KRW;
                        const fmtKrw = Math.round(pnlKrwVal).toLocaleString('ko-KR');
                        pnlCell.innerHTML = `<div>${pnl >= 0 ? '+' : ''}${fmtPnl} USDT (${fmtRoe}%)</div><div style="font-size: 11px; color: #888;">₩${pnlKrwVal >= 0 ? '+' : ''}${fmtKrw} KRW</div>`;
                    }
                }
            });

            // 총 미실현 PNL 업데이트
            updateUnrealizedPnlDisplay(totalUnrealizedPnl);
        }

        function renderOpenOrders(orders) {
            const panel = document.getElementById('openOrdersPanel');
            if (!panel) return;

            if (orders.length === 0) {
                panel.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <div class="empty-text">No Open Orders</div>
                    </div>
                `;
                return;
            }

            panel.innerHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Symbol</th>
                            <th>Type</th>
                            <th>Side</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orders.map(o => `
                            <tr>
                                <td>${new Date(o.created_at).toLocaleTimeString()}</td>
                                <td>${o.symbol}</td>
                                <td>${o.order_type}</td>
                                <td class="${o.side === 'buy' ? 'long' : 'short'}">${o.side.toUpperCase()}</td>
                                <td>${formatPrice(parseFloat(o.price), o.symbol)}</td>
                                <td>${parseFloat(o.quantity).toFixed(4)}</td>
                                <td><button class="cancel-btn" onclick="cancelOrder('${o.order_id}')">취소</button></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        // ========================================
        // 주문 취소
        // ========================================
        async function cancelOrder(orderId) {
            if (!authToken) return;

            try {
                const response = await fetch(`${TRADE_API}?action=cancel_order`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${authToken}`
                    },
                    body: JSON.stringify({ order_id: orderId })
                });

                const data = await response.json();
                if (data.success) {
                    showToast('주문이 취소되었습니다.', 'success');
                    loadUserOrders();
                    loadUserBalance();
                } else {
                    showToast(data.error || '취소 실패', 'error');
                }
            } catch (error) {
                showToast('주문 취소 중 오류가 발생했습니다.', 'error');
            }
        }

        // ========================================
        // 주문 체결 사운드
        // ========================================
        let orderFilledAudio = null;

        function playOrderFilledSound() {
            try {
                // 이미 생성된 오디오 객체 재사용
                if (!orderFilledAudio) {
                    // Web Audio API로 간단한 띠딩 소리 생성
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();

                    function createBeep(frequency, duration, startTime) {
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.value = frequency;
                        oscillator.type = 'sine';

                        gainNode.gain.setValueAtTime(0.3, startTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, startTime + duration);

                        oscillator.start(startTime);
                        oscillator.stop(startTime + duration);
                    }

                    // 띠딩 효과: 두 음 연속
                    createBeep(880, 0.1, audioContext.currentTime);      // A5
                    createBeep(1318.5, 0.15, audioContext.currentTime + 0.1); // E6
                } else {
                    orderFilledAudio.currentTime = 0;
                    orderFilledAudio.play();
                }
            } catch (e) {
                console.log('Sound play failed:', e);
            }
        }

        // ========================================
        // 토스트 메시지
        // ========================================
        function showToast(message, type = 'info') {
            // 새로운 Toast 시스템 사용
            if (typeof Toast !== 'undefined') {
                const titles = {
                    success: '성공',
                    error: '오류',
                    warning: '경고',
                    info: '안내'
                };
                Toast[type] ? Toast[type](titles[type] || '', message) : Toast.info('', message);
            } else {
                // 폴백: 기존 방식
                document.querySelectorAll('.toast-old').forEach(t => t.remove());
                const toast = document.createElement('div');
                toast.className = `toast-old toast-${type}`;
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.classList.add('show'), 10);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        }

        // ========================================
        // 레버리지 설정
        // ========================================
        document.querySelector('.leverage-btn')?.addEventListener('click', async function() {
            const newLeverage = prompt('레버리지를 입력하세요 (1-125):', userLeverage);
            if (newLeverage && !isNaN(newLeverage)) {
                const lev = Math.min(125, Math.max(1, parseInt(newLeverage)));

                if (authToken) {
                    try {
                        const response = await fetch(`${TRADE_API}?action=set_leverage`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${authToken}`
                            },
                            body: JSON.stringify({
                                symbol: currentSymbol.toUpperCase(),
                                leverage: lev
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            userLeverage = lev;
                            this.innerHTML = `${lev.toFixed(2)}x <span class="arrow">▼</span>`;
                            showToast(`레버리지가 ${lev}배로 설정되었습니다.`, 'success');
                        } else {
                            showToast(data.error || '설정 실패', 'error');
                        }
                    } catch (error) {
                        showToast('레버리지 설정 중 오류가 발생했습니다.', 'error');
                    }
                } else {
                    userLeverage = lev;
                    this.innerHTML = `${lev.toFixed(2)}x <span class="arrow">▼</span>`;
                }
            }
        });

        // ========================================
        // 초기화 및 인터벌 설정
        // ========================================
        initOrderbook();        // Socket.IO 연결
        setupTabs();
        setupOrderbookTabs();
        setupButtonEffects();
        setupSlider();
        setupSymbolDropdown();  // 심볼 드롭다운
        initPanelData();
        checkSession();         // 세션 확인

        // 펀딩 카운트다운만 1초 간격 유지 (필수)
        setInterval(updateFundingCountdown, 1000);

        // 1분마다 OI 업데이트 (펀딩비율은 Node 서버 @markPrice에서 실시간 수신)
        setInterval(fetchOpenInterest, 60000);

        // 로그인 상태면 30초마다 잔고/포지션 업데이트 (fallback)
        // 주요 업데이트는 WebSocket account:update 이벤트로 실시간 수신
        setInterval(() => {
            if (authToken) {
                loadUserBalance();
                loadUserPositions();
            }
        }, 30000);
    </script>
</body>
</html>
