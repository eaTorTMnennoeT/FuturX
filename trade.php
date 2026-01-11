<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTCUSDT 무기한 - FuturX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="trade.css">
    <!-- Socket.IO Client -->
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <!-- Lightweight Charts for custom markers -->
    <script src="https://unpkg.com/lightweight-charts@4.1.0/dist/lightweight-charts.standalone.production.js"></script>
</head>
<body>
    <!-- 연결 상태 오버레이 -->
    <div id="connectionOverlay" class="connection-overlay">
        <div class="connection-content">
            <div class="connection-spinner"></div>
            <div class="connection-text">서버에 연결중...</div>
            <div class="connection-subtext">실시간 데이터를 불러오는 중입니다</div>
        </div>
    </div>

    <!-- 헤더 -->
    <header class="header">
        <div class="header-left">
            <div class="logo">
                <span class="logo-icon">F</span>
                <span class="logo-text">FuturX</span>
            </div>
            <nav class="nav">
                <a href="index.php" class="nav-link">마켓</a>
                <a href="trade.php" class="nav-link active">선물거래</a>
                <a href="leaderboard.php" class="nav-link">리더보드</a>
                <a href="competition.php" class="nav-link">대회</a>
                <a href="wallet.php" class="nav-link">지갑</a>
                <a href="deposit.php" class="nav-link">입금</a>
            </nav>
        </div>
        <div class="header-right">
            <button class="header-btn">API</button>
            <button class="header-btn">설정</button>
            <!-- 로그인 전 -->
            <div id="authButtons" class="auth-buttons">
                <button class="header-btn" id="loginBtn">로그인</button>
                <button class="header-btn primary" id="registerBtn">회원가입</button>
            </div>
            <!-- 로그인 후 -->
            <div id="userInfo" class="user-info" style="display: none;">
                <span class="user-balance" id="userBalance">0.00 USDT</span>
                <span class="user-name" id="userName">User</span>
                <button class="header-btn" id="logoutBtn">로그아웃</button>
            </div>
        </div>
    </header>

    <!-- 로그인 모달 -->
    <div class="modal-overlay" id="loginModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>로그인</h3>
                <button class="modal-close" onclick="closeModal('loginModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>이메일</label>
                    <input type="email" id="loginEmail" placeholder="이메일을 입력하세요">
                </div>
                <div class="form-group">
                    <label>비밀번호</label>
                    <input type="password" id="loginPassword" placeholder="비밀번호를 입력하세요">
                </div>
                <div class="form-error" id="loginError"></div>
                <button class="btn-primary" onclick="handleLogin()">로그인</button>
                <div class="form-footer">
                    계정이 없으신가요? <a href="#" onclick="switchModal('loginModal', 'registerModal')">회원가입</a>
                </div>
                <div class="form-test-info">
                    <small>테스트 계정: test@test.com / password</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 회원가입 모달 -->
    <div class="modal-overlay" id="registerModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>회원가입</h3>
                <button class="modal-close" onclick="closeModal('registerModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>이메일</label>
                    <input type="email" id="registerEmail" placeholder="이메일을 입력하세요">
                </div>
                <div class="form-group">
                    <label>닉네임</label>
                    <input type="text" id="registerNickname" placeholder="닉네임을 입력하세요">
                </div>
                <div class="form-group">
                    <label>비밀번호</label>
                    <input type="password" id="registerPassword" placeholder="비밀번호를 입력하세요">
                </div>
                <div class="form-group">
                    <label>비밀번호 확인</label>
                    <input type="password" id="registerConfirmPassword" placeholder="비밀번호를 다시 입력하세요">
                </div>
                <div class="form-group">
                    <label>추천인 코드 (선택)</label>
                    <input type="text" id="registerReferral" placeholder="추천인 코드">
                </div>
                <div class="form-error" id="registerError"></div>
                <button class="btn-primary" onclick="handleRegister()">회원가입</button>
                <div class="form-footer">
                    이미 계정이 있으신가요? <a href="#" onclick="switchModal('registerModal', 'loginModal')">로그인</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 심볼 정보 바 (바이비트 스타일 - 전체 너비) -->
    <div class="symbol-info-bar">
        <button class="menu-toggle-btn" title="메뉴">☰</button>
        <div class="symbol-selector">
            <div class="symbol-main" id="symbolDropdownTrigger">
                <img src="https://cryptologos.cc/logos/bitcoin-btc-logo.png" alt="BTC" class="coin-logo" id="currentCoinLogo">
                <div class="symbol-text">
                    <span class="symbol-name" id="currentSymbolName">BTCUSDT</span>
                    <span class="symbol-type">USDT Perpetual</span>
                </div>
                <span class="dropdown-arrow">▾</span>
            </div>
            <!-- 코인 드롭다운 -->
            <div class="symbol-dropdown" id="symbolDropdown">
                <div class="dropdown-header">
                    <input type="text" class="dropdown-search" placeholder="심볼 검색..." id="symbolSearch">
                </div>
                <div class="dropdown-tabs">
                    <button class="dropdown-tab active">USDT</button>
                    <button class="dropdown-tab">USDC</button>
                    <button class="dropdown-tab">즐겨찾기</button>
                </div>
                <div class="dropdown-list" id="symbolList">
                    <div class="dropdown-item active" data-symbol="BTCUSDT">
                        <img src="https://cryptologos.cc/logos/bitcoin-btc-logo.png" class="item-logo">
                        <span class="item-name">BTCUSDT</span>
                        <span class="item-price" id="dropdown-btc-price">90,673.90</span>
                        <span class="item-change up" id="dropdown-btc-change">+0.01%</span>
                    </div>
                    <div class="dropdown-item" data-symbol="ETHUSDT">
                        <img src="https://cryptologos.cc/logos/ethereum-eth-logo.png" class="item-logo">
                        <span class="item-name">ETHUSDT</span>
                        <span class="item-price" id="dropdown-eth-price">3,104.00</span>
                        <span class="item-change up" id="dropdown-eth-change">+0.32%</span>
                    </div>
                    <div class="dropdown-item" data-symbol="SOLUSDT">
                        <img src="https://cryptologos.cc/logos/solana-sol-logo.png" class="item-logo">
                        <span class="item-name">SOLUSDT</span>
                        <span class="item-price" id="dropdown-sol-price">185.50</span>
                        <span class="item-change up" id="dropdown-sol-change">+0.08%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="price-section">
            <span class="current-price up" id="mainPrice">90,995.40</span>
            <span class="mark-price" id="markPrice">91,000.87</span>
        </div>
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
            <span class="stat-label">Open Interest(BTC)</span>
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
                        <option value="0.01">0.01</option>
                        <option value="0.1" selected>0.1</option>
                        <option value="1">1</option>
                        <option value="10">10</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="precision-arrow">▼</span>
                </div>
            </div>
            <div class="orderbook-header">
                <span>Price(BTC)</span>
                <span>Qty(BTC)</span>
                <span>Total(BTC)</span>
            </div>
            <div class="orderbook-content">
                <div class="asks-list" id="asksList"></div>
                <div class="orderbook-spread">
                    <span class="spread-price up" id="spreadPrice">90,670.80</span>
                    <span class="spread-mark">▼ 90,670.80</span>
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
                    <button class="panel-tab">Borrowings <span class="tab-count">1</span></button>
                    <button class="panel-tab">Tools <span class="tab-count">0</span></button>
                    <button class="panel-tab">P&L</button>
                </div>
                <div class="panel-filters">
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
                    <!-- Order History -->
                    <div class="panel-section" id="orderHistoryPanel">
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
                    <!-- Trade History -->
                    <div class="panel-section" id="tradeHistoryPanel">
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
                    <!-- Assets -->
                    <div class="panel-section" id="assetsPanel">
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
                    <!-- Borrowings -->
                    <div class="panel-section" id="borrowingsPanel">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Coin</th>
                                    <th>Borrowed</th>
                                    <th>Interest</th>
                                    <th>Hourly Rate</th>
                                </tr>
                            </thead>
                            <tbody id="borrowingsBody"></tbody>
                        </table>
                    </div>
                    <!-- Tools -->
                    <div class="panel-section" id="toolsPanel">
                        <div class="empty-state">
                            <div class="empty-icon">🔧</div>
                            <div class="empty-text">No Active Tools</div>
                        </div>
                    </div>
                    <!-- P&L -->
                    <div class="panel-section" id="pnlPanel">
                        <div class="pnl-summary">
                            <div class="pnl-card">
                                <span class="pnl-label">Today's PNL</span>
                                <span class="pnl-value up" id="todayPnl">+0.00 USDT</span>
                            </div>
                            <div class="pnl-card">
                                <span class="pnl-label">7D PNL</span>
                                <span class="pnl-value up" id="weekPnl">+0.00 USDT</span>
                            </div>
                            <div class="pnl-card">
                                <span class="pnl-label">30D PNL</span>
                                <span class="pnl-value" id="monthPnl">+0.00 USDT</span>
                            </div>
                            <div class="pnl-card">
                                <span class="pnl-label">Total PNL</span>
                                <span class="pnl-value up" id="totalPnl">+0.00 USDT</span>
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
                    <input type="text" value="90,670.80" id="priceInput">
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
                        <span class="qty-unit">BTC</span>
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
                    <span id="orderMaxSize">-- BTC</span>
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
                            <div class="bar-fill green" style="width: 89%"></div>
                        </div>
                        <span>89.13%</span>
                    </div>
                    <div class="margin-bar-row">
                        <span>Maintenance Margin</span>
                        <div class="bar-container">
                            <div class="bar-fill green" style="width: 35%"></div>
                        </div>
                        <span>35.65%</span>
                    </div>
                </div>
                <div class="balance-rows">
                    <div class="balance-row">
                        <span>Margin Balance</span>
                        <span>0.3044 USDT</span>
                    </div>
                    <div class="balance-row">
                        <span>Available Balance</span>
                        <span>0.0330 USDT</span>
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
                        <span>90,700.55</span>
                    </div>
                    <div class="contract-row">
                        <span>Mark Price</span>
                        <span>90,670.80</span>
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
                // 기본 TradingView 위젯
                chartContainer.innerHTML = `
                    <iframe
                        src="https://www.tradingview.com/widgetembed/?frameElementId=tradingview_chart&symbol=BINANCE%3ABTCUSDT&interval=1&hidesidetoolbar=0&symboledit=0&saveimage=0&toolbarbg=161a1e&studies=%5B%5D&theme=dark&style=1&timezone=Asia%2FSeoul&studies_overrides=%7B%7D&overrides=%7B%7D&enabled_features=%5B%5D&disabled_features=%5B%5D&locale=kr&utm_source=&utm_medium=widget&utm_campaign=chart&utm_term=BINANCE%3ABTCUSDT"
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

            // 캔들스틱 시리즈
            candleSeries = chart.addCandlestickSeries({
                upColor: '#0ecb81',
                downColor: '#f6465d',
                borderUpColor: '#0ecb81',
                borderDownColor: '#f6465d',
                wickUpColor: '#0ecb81',
                wickDownColor: '#f6465d',
            });

            // 볼륨 시리즈
            volumeSeries = chart.addHistogramSeries({
                color: '#26a69a',
                priceFormat: { type: 'volume' },
                priceScaleId: '',
                scaleMargins: { top: 0.85, bottom: 0 },
            });

            // 바이낸스에서 캔들 데이터 로드
            loadCandleData();

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

        // 바이낸스 캔들 데이터 로드
        async function loadCandleData() {
            try {
                const symbol = currentSymbol.toUpperCase();
                const response = await fetch(`https://api.binance.com/api/v3/klines?symbol=${symbol}&interval=1m&limit=500`);
                const data = await response.json();

                const candles = data.map(d => ({
                    time: d[0] / 1000,
                    open: parseFloat(d[1]),
                    high: parseFloat(d[2]),
                    low: parseFloat(d[3]),
                    close: parseFloat(d[4]),
                }));

                const volumes = data.map(d => ({
                    time: d[0] / 1000,
                    value: parseFloat(d[5]),
                    color: parseFloat(d[4]) >= parseFloat(d[1]) ? 'rgba(14, 203, 129, 0.5)' : 'rgba(246, 70, 93, 0.5)',
                }));

                if (candleSeries) candleSeries.setData(candles);
                if (volumeSeries) volumeSeries.setData(volumes);

                // 포지션/주문 라인 업데이트
                updateChartMarkers();

            } catch (error) {
                console.error('캔들 데이터 로드 실패:', error);
            }
        }

        // 차트 마커 업데이트 (포지션, 청산라인, 주문)
        function updateChartMarkers() {
            if (!candleSeries || chartMode !== 'lightweight') return;

            // 기존 라인 제거
            positionLines.forEach(line => candleSeries.removePriceLine(line));
            orderLines.forEach(line => candleSeries.removePriceLine(line));
            if (liquidationLine) candleSeries.removePriceLine(liquidationLine);

            positionLines = [];
            orderLines = [];
            liquidationLine = null;

            // 포지션 라인 추가
            const positionsBody = document.getElementById('positionsBody');
            if (positionsBody) {
                const rows = positionsBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 5) {
                        const entryPriceText = cells[2]?.textContent;
                        const liqPriceText = cells[4]?.textContent;
                        const sideText = cells[1]?.textContent?.toLowerCase() || '';

                        const entryPrice = parseFloat(entryPriceText?.replace(/,/g, ''));
                        const liqPrice = parseFloat(liqPriceText?.replace(/,/g, ''));
                        const isLong = sideText.includes('long') || parseFloat(cells[1]?.textContent) > 0;

                        if (entryPrice && !isNaN(entryPrice)) {
                            // 진입가 라인
                            const entryLine = candleSeries.createPriceLine({
                                price: entryPrice,
                                color: isLong ? '#0ecb81' : '#f6465d',
                                lineWidth: 2,
                                lineStyle: LightweightCharts.LineStyle.Solid,
                                axisLabelVisible: true,
                                title: isLong ? 'Long Entry' : 'Short Entry',
                            });
                            positionLines.push(entryLine);
                        }

                        if (liqPrice && !isNaN(liqPrice) && liqPrice > 0) {
                            // 청산가 라인
                            liquidationLine = candleSeries.createPriceLine({
                                price: liqPrice,
                                color: '#ff4444',
                                lineWidth: 1,
                                lineStyle: LightweightCharts.LineStyle.Dashed,
                                axisLabelVisible: true,
                                title: 'Liquidation',
                            });
                        }
                    }
                });
            }

            // 주문 라인 추가 (미체결 주문)
            const ordersBody = document.getElementById('openOrdersBody');
            if (ordersBody) {
                const rows = ordersBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 4) {
                        const priceText = cells[3]?.textContent;
                        const sideText = cells[2]?.textContent?.toLowerCase() || '';

                        const price = parseFloat(priceText?.replace(/,/g, ''));
                        const isBuy = sideText.includes('buy');

                        if (price && !isNaN(price)) {
                            const orderLine = candleSeries.createPriceLine({
                                price: price,
                                color: isBuy ? '#0ecb81' : '#f6465d',
                                lineWidth: 1,
                                lineStyle: LightweightCharts.LineStyle.Dotted,
                                axisLabelVisible: true,
                                title: isBuy ? 'Buy Order' : 'Sell Order',
                            });
                            orderLines.push(orderLine);
                        }
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
                fetchFundingAndOI(); // 펀딩레이트 & OI 초기 로드
            });
        } else {
            initBinanceChart();
            setupChartModeToggle();
            fetchFundingAndOI(); // 펀딩레이트 & OI 초기 로드
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
        const SOCKET_SERVER = 'http://localhost:3002';
        let socket = null;

        let currentPrice = 0;
        let previousPrice = 0;
        let indexPrice = 0;
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
                if (data.onlineCount) {
                    const el = document.getElementById('onlineUsers');
                    if (el) el.textContent = data.onlineCount;
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
                priceEl.textContent = formatPrice(currentPrice);
                priceEl.className = `current-price ${isUp ? 'up flash-green' : 'down flash-red'}`;

                setTimeout(() => {
                    priceEl.className = `current-price ${isUp ? 'up' : 'down'}`;
                }, 300);
            }

            if (spreadPriceEl) {
                spreadPriceEl.textContent = formatPrice(currentPrice);
                spreadPriceEl.className = `spread-price ${isUp ? 'up' : 'down'}`;
            }

            // 주문 입력창 업데이트 (Last 버튼이 활성화된 경우에만)
            const priceInput = document.getElementById('priceInput');
            const lastBtn = document.querySelector('.last-btn');
            if (priceInput && lastBtn && lastBtn.classList.contains('active')) {
                priceInput.value = formatPrice(currentPrice);
            }

            // 포지션 실시간 업데이트
            updatePositionsPrices(currentPrice);
        }

        // ========================================
        // 오더북 데이터 처리
        // ========================================
        // 확장 데이터 저장 (정밀도별 가짜 데이터 포함)
        let extendedData = {};

        function processOrderbookData(data) {
            // 확장 데이터 저장 (정밀도별)
            extendedData = {
                1: data.extended || { asks: data.asks, bids: data.bids },
                10: data.extended10 || { asks: data.asks, bids: data.bids },
                50: data.extended50 || { asks: data.asks, bids: data.bids },
                100: data.extended100 || { asks: data.asks, bids: data.bids }
            };

            // 원본 데이터 저장 (정밀도 0.01, 0.1용)
            rawAsksData = data.asks || [];
            rawBidsData = data.bids || [];

            // 정밀도 적용하여 데이터 가공
            applyPrecisionAndRender();
        }

        // 정밀도 적용 및 렌더링
        function applyPrecisionAndRender() {
            let sourceAsks, sourceBids;

            // 정밀도가 1 이상이면 서버에서 미리 계산된 확장 데이터 사용
            if (obPrecision >= 1 && extendedData[obPrecision]) {
                sourceAsks = extendedData[obPrecision].asks;
                sourceBids = extendedData[obPrecision].bids;
                // 이미 정밀도별로 그룹화되어 있으므로 직접 변환
                asksData = sourceAsks.map(([price, qty]) => ({
                    price: parseFloat(price),
                    qty: parseFloat(qty),
                    total: 0,
                    isUser: false
                })).slice(0, obViewMode === 'asks' ? 30 : 15);

                bidsData = sourceBids.map(([price, qty]) => ({
                    price: parseFloat(price),
                    qty: parseFloat(qty),
                    total: 0,
                    isUser: false
                })).slice(0, obViewMode === 'bids' ? 30 : 15);
            } else {
                // 정밀도가 1 미만이면 원본 데이터에서 그룹화
                asksData = groupByPrecision(rawAsksData, obPrecision, 'asks');
                bidsData = groupByPrecision(rawBidsData, obPrecision, 'bids');
            }

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

            // 배열로 변환 후 정렬
            let result = Array.from(grouped.values());

            // 뷰 모드에 따라 최대 표시 행 수 결정
            let maxRows = 15;
            if (obViewMode === 'bids' && side === 'bids') maxRows = 30;
            if (obViewMode === 'asks' && side === 'asks') maxRows = 30;

            if (side === 'asks') {
                result.sort((a, b) => a.price - b.price); // 오름차순 후 역순 표시
                result = result.slice(0, maxRows).reverse();
            } else {
                result.sort((a, b) => b.price - a.price); // 내림차순
                result = result.slice(0, maxRows);
            }

            // 그룹화된 데이터 개수 반환 (있는 만큼만)
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
            // Socket.IO 서버에서 오는 형식에 맞게 처리
            previousPrice = currentPrice;
            currentPrice = parseFloat(data.lastPrice || data.c || 0);
            high24h = parseFloat(data.highPrice || data.h || 0);
            low24h = parseFloat(data.lowPrice || data.l || 0);
            turnover = parseFloat(data.quoteVolume || data.q || 0);
            indexPrice = currentPrice;

            const priceChange = parseFloat(data.priceChange || data.p || 0);
            const isUp = currentPrice >= previousPrice;

            updatePriceDisplay();
            updateStats(isUp, priceChange);
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
            // Socket.IO 연결
            connectSocketIO();
        }

        // 가격 포맷팅
        function formatPrice(price) {
            return price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

            // 최대 수량 계산 (깊이 바 스케일링용)
            const maxAskQty = asksData.length > 0 ? Math.max(...asksData.map(a => a.qty)) : 0.1;
            const maxBidQty = bidsData.length > 0 ? Math.max(...bidsData.map(b => b.qty)) : 0.1;
            const maxQty = Math.max(maxAskQty, maxBidQty, 0.1);

            // 정밀도에 따른 소수점 자릿수 계산
            const priceDecimals = obPrecision < 1 ? Math.abs(Math.log10(obPrecision)) : 0;

            // DocumentFragment 사용하여 리플로우 최소화
            if (obViewMode !== 'bids' && asksData.length > 0) {
                const fragment = document.createDocumentFragment();
                asksData.forEach(a => {
                    const row = document.createElement('div');
                    row.className = `ob-row ask${a.isUser ? ' user-order' : ''}`;
                    row.dataset.price = a.price.toFixed(priceDecimals);
                    row.innerHTML = `
                        <span class="ob-price">${a.price.toLocaleString(undefined, {minimumFractionDigits: priceDecimals, maximumFractionDigits: priceDecimals})}</span>
                        <span class="ob-qty">${a.qty.toFixed(4)}</span>
                        <span class="ob-total">${a.total.toFixed(4)}</span>
                        <div class="ob-depth" style="width: ${(a.qty / maxQty) * 100}%"></div>
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
                        <span class="ob-price">${b.price.toLocaleString(undefined, {minimumFractionDigits: priceDecimals, maximumFractionDigits: priceDecimals})}</span>
                        <span class="ob-qty">${b.qty.toFixed(4)}</span>
                        <span class="ob-total">${b.total.toFixed(4)}</span>
                        <div class="ob-depth" style="width: ${(b.qty / maxQty) * 100}%"></div>
                    `;
                    fragment.appendChild(row);
                });
                bidsList.innerHTML = '';
                bidsList.appendChild(fragment);
            }
        }

        // ========================================
        // Recent Trades 렌더링
        // ========================================
        function renderRecentTrades() {
            const container = document.getElementById('recentTradesContent');
            if (!container) return;

            container.innerHTML = recentTrades.slice(0, 20).map(t => `
                <div class="trade-row ${t.side}">
                    <span class="trade-price">${formatPrice(t.price)}</span>
                    <span class="trade-qty">${t.qty.toFixed(3)}</span>
                    <span class="trade-time">${t.time}</span>
                </div>
            `).join('');
        }

        // ========================================
        // 통계 정보 업데이트
        // ========================================
        function updateStats(isUp, priceChange) {
            const stats = document.querySelectorAll('.stat-item');

            // Index Price
            if (stats[0]) {
                stats[0].querySelector('.stat-value').textContent = formatPrice(indexPrice);
            }

            // Change %
            if (stats[1]) {
                const changePercent = priceChange && currentPrice ? (priceChange / (currentPrice - priceChange) * 100) : 0;
                const statVal = stats[1].querySelector('.stat-value');
                statVal.textContent = `${priceChange >= 0 ? '+' : ''}${priceChange.toFixed(2)} (${changePercent >= 0 ? '+' : ''}${changePercent.toFixed(2)}%)`;
                statVal.className = `stat-value ${priceChange >= 0 ? 'up' : 'down'}`;
            }

            // 24H High
            if (stats[2]) {
                stats[2].querySelector('.stat-value').textContent = formatPrice(high24h);
            }

            // 24H Low
            if (stats[3]) {
                stats[3].querySelector('.stat-value').textContent = formatPrice(low24h);
            }

            // Turnover
            if (stats[4]) {
                stats[4].querySelector('.stat-value').textContent = formatNumber(turnover);
            }

            // Open Interest - 별도 업데이트 (stats[5])
            // 바이낸스 API에서 가져오는 데이터이므로 여기선 스킵

            // Funding Rate (stats[6])
            if (stats[6]) {
                stats[6].querySelector('.stat-value').textContent = `${fundingRate.toFixed(4)}% / ${formatTime(fundingCountdown)}`;
            }

            // Contract Details 업데이트
            const contractRows = document.querySelectorAll('.contract-row');
            if (contractRows[1]) {
                contractRows[1].querySelector('span:last-child').textContent = formatPrice(indexPrice);
            }
            if (contractRows[2]) {
                contractRows[2].querySelector('span:last-child').textContent = formatPrice(currentPrice);
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
        // 바이낸스 펀딩레이트 & 오픈인터레스트 조회
        // ========================================
        async function fetchFundingAndOI() {
            try {
                // 펀딩레이트 조회
                const fundingRes = await fetch(`https://fapi.binance.com/fapi/v1/premiumIndex?symbol=${currentSymbol}`);
                const fundingData = await fundingRes.json();

                if (fundingData.lastFundingRate) {
                    fundingRate = parseFloat(fundingData.lastFundingRate) * 100;
                }

                // 다음 펀딩 시간까지 남은 초 계산
                if (fundingData.nextFundingTime) {
                    const nextFunding = fundingData.nextFundingTime;
                    const now = Date.now();
                    fundingCountdown = Math.max(0, Math.floor((nextFunding - now) / 1000));
                }

                // 오픈인터레스트 조회
                const oiRes = await fetch(`https://fapi.binance.com/fapi/v1/openInterest?symbol=${currentSymbol}`);
                const oiData = await oiRes.json();

                if (oiData.openInterest) {
                    openInterest = parseFloat(oiData.openInterest);
                    // Open Interest 표시 업데이트
                    const oiElement = document.getElementById('openInterest');
                    if (oiElement) {
                        oiElement.textContent = formatNumber(openInterest);
                    }
                }
            } catch (error) {
                console.error('펀딩/OI 데이터 로드 실패:', error);
            }
        }

        // ========================================
        // 펀딩 카운트다운
        // ========================================
        function updateFundingCountdown() {
            fundingCountdown--;
            if (fundingCountdown <= 0) {
                fundingCountdown = 8 * 3600; // 8시간 리셋
                fetchFundingAndOI(); // 새로운 펀딩레이트 조회
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
        const panelIds = ['openOrdersPanel', 'positionsPanel', 'orderHistoryPanel', 'tradeHistoryPanel', 'assetsPanel', 'borrowingsPanel', 'toolsPanel', 'pnlPanel'];

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
                            loadUserBalance();
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

            // Interval Buttons
            document.querySelectorAll('.interval-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
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
                                priceInput.value = formatPrice(currentPrice);
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
                            priceInput.value = formatPrice(currentPrice);
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
        // 심볼 드롭다운 설정
        // ========================================
        let currentSymbol = 'btcusdt';
        const symbolConfigs = {
            'BTCUSDT': { icon: '₿', color: '#f7931a', name: 'Bitcoin' },
            'ETHUSDT': { icon: 'Ξ', color: '#627eea', name: 'Ethereum' },
            'SOLUSDT': { icon: '◎', color: '#00d18c', name: 'Solana' },
            'XRPUSDT': { icon: '✕', color: '#23292f', name: 'Ripple' },
            'DOGEUSDT': { icon: 'Ð', color: '#c2a633', name: 'Dogecoin' }
        };

        function setupSymbolDropdown() {
            const selector = document.querySelector('.symbol-selector');
            const trigger = document.getElementById('symbolDropdownTrigger');
            const dropdown = document.getElementById('symbolDropdown');
            const searchInput = document.getElementById('symbolSearch');
            const symbolList = document.getElementById('symbolList');

            if (!trigger || !dropdown) return;

            // 드롭다운 토글
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                selector.classList.toggle('open');
                if (selector.classList.contains('open')) {
                    searchInput.focus();
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
                    e.target.textContent = e.target.classList.contains('active') ? '★' : '☆';
                    return;
                }

                const symbol = item.dataset.symbol;
                selectSymbol(symbol);
                selector.classList.remove('open');
            });

            // 드롭다운 탭
            document.querySelectorAll('.dropdown-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.dropdown-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        }

        function selectSymbol(symbol) {
            const config = symbolConfigs[symbol];
            if (!config) return;

            currentSymbol = symbol.toLowerCase();

            // UI 업데이트
            document.getElementById('currentSymbolName').textContent = symbol;
            const iconEl = document.getElementById('currentSymbolIcon');
            iconEl.textContent = config.icon;
            iconEl.style.background = config.color;

            // 활성 항목 표시
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.classList.toggle('active', item.dataset.symbol === symbol);
            });

            // 차트 업데이트
            updateChartSymbol(symbol);

            // WebSocket 심볼 변경 (서버에 알림)
            if (socket && socket.connected) {
                socket.emit('symbol:change', { symbol: currentSymbol });
            }

            console.log('Symbol changed to:', symbol);
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
        let userAvailableBalance = 0;
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

            // Available
            if (orderAvailable) {
                orderAvailable.textContent = userAvailableBalance.toFixed(2) + ' USDT';
            }

            // Max Size
            if (orderMaxSize && currentPrice > 0 && userAvailableBalance > 0) {
                const maxQty = (userAvailableBalance * userLeverage) / currentPrice;
                orderMaxSize.textContent = maxQty.toFixed(6) + ' BTC';
            }

            // Cost
            if (orderCost) {
                orderCost.textContent = margin.toFixed(2) + ' USDT';
            }

            // Liq Price
            if (orderLiqPrice && qty > 0 && currentPrice > 0) {
                const liqPrice = currentPrice * (1 - (1 / userLeverage) * 0.9);
                orderLiqPrice.textContent = formatPrice(liqPrice);
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
                        <td>${formatPrice(parseFloat(o.price))}</td>
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
                        <td>${formatPrice(parseFloat(t.price))}</td>
                        <td>${parseFloat(t.quantity).toFixed(6)}</td>
                        <td>${parseFloat(t.fee).toFixed(4)} USDT</td>
                        <td class="${pnlClass}">${parseFloat(t.realized_pnl).toFixed(2)} USDT</td>
                    </tr>
                `;
            }).join('');
        }

        // PNL 데이터 업데이트 (실제 API에서 가져옴 - 랜덤 제거)
        function updatePnlData() {
            // 빈 함수 - API 연동 시 구현
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
                    updateWalletDisplay(data.data.wallets);
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

            if (isLoggedIn && currentUser) {
                authButtons.style.display = 'none';
                userInfo.style.display = 'flex';
                document.getElementById('userName').textContent = currentUser.nickname;
            } else {
                authButtons.style.display = 'flex';
                userInfo.style.display = 'none';
            }
        }

        function updateWalletDisplay(wallets) {
            const usdtWallet = wallets.find(w => w.currency === 'USDT');
            if (usdtWallet) {
                const balance = parseFloat(usdtWallet.balance) || 0;
                const availableBalance = parseFloat(usdtWallet.available_balance) || 0;

                // 전역 변수 업데이트 (주문 패널용)
                userAvailableBalance = availableBalance;

                // 헤더 잔고 표시
                document.getElementById('userBalance').textContent =
                    availableBalance.toFixed(2) + ' USDT';

                // 계좌 정보 업데이트
                const balanceRows = document.querySelectorAll('.balance-row');
                if (balanceRows[0]) {
                    balanceRows[0].querySelector('span:last-child').textContent =
                        balance.toFixed(4) + ' USDT';
                }
                if (balanceRows[1]) {
                    balanceRows[1].querySelector('span:last-child').textContent =
                        availableBalance.toFixed(4) + ' USDT';
                }

                // 주문 패널 정보 업데이트
                updateOrderInfo();
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
                    updateWalletDisplay(data.data.wallets);
                }
            } catch (error) {
                console.error('Load balance error:', error);
            }
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

                    // 탭 카운트 업데이트
                    const orderTab = document.querySelectorAll('.panel-tab')[0];
                    if (orderTab) {
                        orderTab.innerHTML = `Open Orders <span class="tab-count">${data.data.count}</span>`;
                    }

                    // 차트 마커 업데이트 (Lightweight Charts 모드일 때)
                    updateChartMarkers();
                }
            } catch (error) {
                console.error('Load orders error:', error);
            }
        }

        // ========================================
        // 렌더링 함수
        // ========================================
        // 포지션 데이터 저장 (실시간 업데이트용)
        let userPositions = [];

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
                const roeClass = parseFloat(p.roe) >= 0 ? 'up' : 'down';
                return `
                    <tr data-position-id="${p.id}" data-symbol="${p.symbol}" data-side="${p.side}" data-entry="${p.entry_price}">
                        <td><div class="coin-cell"><span class="coin-icon btc">₿</span>${p.symbol}</div></td>
                        <td class="${p.side}">${p.side === 'long' ? '+' : '-'}${parseFloat(p.size).toFixed(4)}</td>
                        <td>${formatPrice(parseFloat(p.entry_price))}</td>
                        <td class="mark-price-cell">${formatPrice(parseFloat(p.mark_price))}</td>
                        <td>${formatPrice(parseFloat(p.liquidation_price))}</td>
                        <td>${parseFloat(p.margin).toFixed(2)} USDT</td>
                        <td class="pnl-cell ${pnlClass}">${parseFloat(p.unrealized_pnl).toFixed(2)} (${parseFloat(p.roe).toFixed(2)}%)</td>
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
                    alert('포지션이 청산되었습니다.');
                    loadUserPositions();
                    loadUserBalance();
                } else {
                    alert('오류: ' + data.error);
                }
            } catch (error) {
                console.error('Close position error:', error);
                alert('포지션 청산 중 오류가 발생했습니다.');
            }
        }

        // 포지션 실시간 가격/PnL 업데이트
        function updatePositionsPrices(newPrice) {
            if (!userPositions || userPositions.length === 0) return;

            const rows = document.querySelectorAll('#positionsBody tr[data-position-id]');
            rows.forEach(row => {
                const symbol = row.dataset.symbol;
                if (symbol !== 'BTCUSDT') return; // 현재는 BTC만

                const side = row.dataset.side;
                const entryPrice = parseFloat(row.dataset.entry);
                const position = userPositions.find(p => p.id == row.dataset.positionId);
                if (!position) return;

                const size = parseFloat(position.size);
                const leverage = parseFloat(position.leverage) || 10;

                // PnL 계산
                let pnl = 0;
                if (side === 'long') {
                    pnl = (newPrice - entryPrice) * size;
                } else {
                    pnl = (entryPrice - newPrice) * size;
                }

                const margin = parseFloat(position.margin);
                const roe = margin > 0 ? (pnl / margin) * 100 : 0;

                // DOM 업데이트
                const markPriceCell = row.querySelector('.mark-price-cell');
                const pnlCell = row.querySelector('.pnl-cell');

                if (markPriceCell) {
                    markPriceCell.textContent = formatPrice(newPrice);
                }

                if (pnlCell) {
                    pnlCell.className = `pnl-cell ${pnl >= 0 ? 'up' : 'down'}`;
                    pnlCell.textContent = `${pnl.toFixed(2)} (${roe.toFixed(2)}%)`;
                }
            });
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
                                <td>${formatPrice(parseFloat(o.price))}</td>
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
        // 토스트 메시지
        // ========================================
        function showToast(message, type = 'info') {
            // 기존 토스트 제거
            document.querySelectorAll('.toast').forEach(t => t.remove());

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
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

        // 1분마다 펀딩레이트 & OI 업데이트
        setInterval(fetchFundingAndOI, 60000);

        // 로그인 상태면 30초마다 잔고/포지션 업데이트 (최적화)
        setInterval(() => {
            if (authToken) {
                loadUserBalance();
                loadUserPositions();
            }
        }, 30000);
    </script>
</body>
</html>
