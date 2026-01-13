<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTCUSDT 무기한 | FuturX Pro</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Design CSS -->
    <link rel="stylesheet" href="css/trade_design5.css">
</head>
<body>

    <!-- ========================================
         Premium Header
         ======================================== -->
    <header class="header">
        <div class="header__left">
            <a href="#" class="logo">
                <div class="logo__icon">F</div>
                <span class="logo__text">FuturX Pro</span>
            </a>
            <nav class="header__nav">
                <a href="#" class="nav-link">거래소</a>
                <a href="#" class="nav-link active">선물거래</a>
                <a href="#" class="nav-link">카피트레이딩</a>
                <a href="#" class="nav-link">런치패드</a>
                <a href="#" class="nav-link">NFT</a>
            </nav>
        </div>
        <div class="header__right">
            <div class="header__balance">
                <span class="header__balance-value">12,458.32</span>
                <span class="header__balance-unit">USDT</span>
            </div>
            <button class="header__btn header__btn--secondary">입금</button>
            <button class="header__btn header__btn--primary">거래하기</button>
            <div class="header__user">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
    </header>

    <!-- ========================================
         Symbol Info Bar
         ======================================== -->
    <div class="symbol-bar">
        <div class="symbol-selector">
            <span class="symbol-selector__star">★</span>
            <img src="https://cryptologos.cc/logos/bitcoin-btc-logo.png" alt="BTC" class="symbol-selector__icon">
            <div class="symbol-selector__info">
                <span class="symbol-selector__name">BTCUSDT</span>
                <span class="symbol-selector__type">USDT Perpetual</span>
            </div>
            <span class="symbol-selector__arrow">▾</span>
        </div>
        
        <div class="symbol-price">
            <span class="symbol-price__current up">94,287.50</span>
            <span class="symbol-price__mark">Mark: 94,290.12</span>
        </div>
        
        <div class="symbol-bar__divider"></div>
        
        <div class="symbol-stats">
            <div class="stat-item">
                <span class="stat-item__label">24h Change</span>
                <span class="stat-item__value up">+2,456.80 (+2.68%)</span>
            </div>
            <div class="stat-item">
                <span class="stat-item__label">24h High</span>
                <span class="stat-item__value">95,120.00</span>
            </div>
            <div class="stat-item">
                <span class="stat-item__label">24h Low</span>
                <span class="stat-item__value">91,830.70</span>
            </div>
            <div class="stat-item">
                <span class="stat-item__label">24h Volume</span>
                <span class="stat-item__value">2.84B USDT</span>
            </div>
            <div class="stat-item">
                <span class="stat-item__label">Open Interest</span>
                <span class="stat-item__value">52,847 BTC</span>
            </div>
            <div class="stat-item">
                <span class="stat-item__label">Funding Rate</span>
                <span class="stat-item__value up">0.0082% / 02:45:30</span>
            </div>
        </div>
    </div>

    <!-- ========================================
         Main Trade Layout
         ======================================== -->
    <div class="trade-wrapper">
        
        <!-- ========================================
             Chart Section
             ======================================== -->
        <section class="chart-section">
            <div class="chart-header">
                <div class="chart-tabs">
                    <button class="chart-tab active">차트</button>
                    <button class="chart-tab">Overview</button>
                    <button class="chart-tab">Data</button>
                    <button class="chart-tab">Feed</button>
                </div>
                <div class="chart-modes">
                    <span class="chart-mode active">Standard</span>
                    <span class="chart-mode">TradingView</span>
                    <span class="chart-mode">Depth</span>
                </div>
            </div>
            
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
            
            <div class="chart-container">
                <div class="chart-placeholder">
                    <div class="chart-placeholder__icon">📈</div>
                    <div class="chart-placeholder__text">TradingView 차트 영역<br>(디자인 시안용)</div>
                </div>
            </div>
        </section>

        <!-- ========================================
             Orderbook Section
             ======================================== -->
        <section class="orderbook-section">
            <div class="orderbook-header">
                <button class="orderbook-tab active">Order Book</button>
                <button class="orderbook-tab">Recent Trades</button>
            </div>
            
            <div class="orderbook-options">
                <div class="ob-view-btns">
                    <button class="ob-view-btn active">
                        <span class="bar red"></span>
                        <span class="bar green"></span>
                    </button>
                    <button class="ob-view-btn">
                        <span class="bar green"></span>
                        <span class="bar green"></span>
                    </button>
                    <button class="ob-view-btn">
                        <span class="bar red"></span>
                        <span class="bar red"></span>
                    </button>
                </div>
                <select class="precision-select">
                    <option>0.1</option>
                    <option>1</option>
                    <option selected>10</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </div>
            
            <div class="orderbook-cols">
                <span>Price(USDT)</span>
                <span>Qty(BTC)</span>
                <span>Total</span>
            </div>
            
            <div class="orderbook-content">
                <!-- Asks (Sell Orders) -->
                <div class="asks-list">
                    <div class="ob-row ask">
                        <div class="depth" style="width: 25%"></div>
                        <span class="price">94,350.00</span>
                        <span class="qty">0.8542</span>
                        <span class="total">8.2456</span>
                    </div>
                    <div class="ob-row ask">
                        <div class="depth" style="width: 35%"></div>
                        <span class="price">94,340.00</span>
                        <span class="qty">1.2456</span>
                        <span class="total">7.3914</span>
                    </div>
                    <div class="ob-row ask">
                        <div class="depth" style="width: 55%"></div>
                        <span class="price">94,330.00</span>
                        <span class="qty">2.4582</span>
                        <span class="total">6.1458</span>
                    </div>
                    <div class="ob-row ask">
                        <div class="depth" style="width: 42%"></div>
                        <span class="price">94,320.00</span>
                        <span class="qty">1.5847</span>
                        <span class="total">3.6876</span>
                    </div>
                    <div class="ob-row ask">
                        <div class="depth" style="width: 68%"></div>
                        <span class="price">94,310.00</span>
                        <span class="qty">3.2458</span>
                        <span class="total">2.1029</span>
                    </div>
                    <div class="ob-row ask">
                        <div class="depth" style="width: 30%"></div>
                        <span class="price">94,300.00</span>
                        <span class="qty">0.9542</span>
                        <span class="total">1.1571</span>
                    </div>
                    <div class="ob-row ask">
                        <div class="depth" style="width: 15%"></div>
                        <span class="price">94,290.00</span>
                        <span class="qty">0.2029</span>
                        <span class="total">0.2029</span>
                    </div>
                </div>
                
                <!-- Spread -->
                <div class="orderbook-spread">
                    <span class="spread-price up">94,287.50</span>
                    <span class="spread-info">≈ $94,287.50</span>
                </div>
                
                <!-- Bids (Buy Orders) -->
                <div class="bids-list">
                    <div class="ob-row bid">
                        <div class="depth" style="width: 22%"></div>
                        <span class="price">94,280.00</span>
                        <span class="qty">0.4582</span>
                        <span class="total">0.4582</span>
                    </div>
                    <div class="ob-row bid">
                        <div class="depth" style="width: 45%"></div>
                        <span class="price">94,270.00</span>
                        <span class="qty">1.8457</span>
                        <span class="total">2.3039</span>
                    </div>
                    <div class="ob-row bid">
                        <div class="depth" style="width: 60%"></div>
                        <span class="price">94,260.00</span>
                        <span class="qty">2.5847</span>
                        <span class="total">4.8886</span>
                    </div>
                    <div class="ob-row bid">
                        <div class="depth" style="width: 38%"></div>
                        <span class="price">94,250.00</span>
                        <span class="qty">1.2458</span>
                        <span class="total">6.1344</span>
                    </div>
                    <div class="ob-row bid">
                        <div class="depth" style="width: 72%"></div>
                        <span class="price">94,240.00</span>
                        <span class="qty">3.5847</span>
                        <span class="total">9.7191</span>
                    </div>
                    <div class="ob-row bid">
                        <div class="depth" style="width: 50%"></div>
                        <span class="price">94,230.00</span>
                        <span class="qty">2.1458</span>
                        <span class="total">11.8649</span>
                    </div>
                    <div class="ob-row bid">
                        <div class="depth" style="width: 85%"></div>
                        <span class="price">94,220.00</span>
                        <span class="qty">4.8542</span>
                        <span class="total">16.7191</span>
                    </div>
                </div>
            </div>
            
            <div class="orderbook-footer">
                <div class="depth-bar">
                    <div class="depth-bar__bid" style="width: 58%">B 58%</div>
                    <div class="depth-bar__ask" style="width: 42%">42% S</div>
                </div>
            </div>
        </section>

        <!-- ========================================
             Trade Section (Order Panel)
             ======================================== -->
        <section class="trade-section">
            <div class="trade-header">
                <span class="trade-title">Trade</span>
                <div class="trade-icons">
                    <button class="trade-icon-btn" title="설정">⚙️</button>
                    <button class="trade-icon-btn" title="계산기">🔢</button>
                    <button class="trade-icon-btn" title="더보기">⋮</button>
                </div>
            </div>
            
            <!-- Margin & Leverage -->
            <div class="margin-leverage-row">
                <button class="margin-btn">
                    Cross <span class="btn-arrow">▼</span>
                </button>
                <button class="leverage-btn">
                    50.00x <span class="btn-arrow">▼</span>
                </button>
            </div>
            
            <!-- Order Type Tabs -->
            <div class="order-type-tabs">
                <button class="order-type-tab active">Limit</button>
                <button class="order-type-tab">Market</button>
                <button class="order-type-tab">Conditional ▼</button>
            </div>
            
            <!-- Price Input -->
            <div class="input-group">
                <label class="input-group__label">Price</label>
                <div class="input-wrapper">
                    <input type="text" value="94,287.50" placeholder="Price">
                    <span class="input-suffix">
                        <button class="input-suffix__btn active">Last</button>
                        <span class="input-suffix__unit">⇄</span>
                    </span>
                </div>
            </div>
            
            <!-- Quantity Input -->
            <div class="input-group">
                <label class="input-group__label">Quantity</label>
                <div class="input-wrapper">
                    <input type="text" value="0.0500" placeholder="0.001">
                    <span class="input-suffix">
                        <span class="input-suffix__unit">BTC</span>
                    </span>
                </div>
            </div>
            
            <!-- Amount Input -->
            <div class="input-group">
                <label class="input-group__label">Amount (USDT)</label>
                <div class="input-wrapper">
                    <input type="text" value="4,714.38" placeholder="0.00">
                    <span class="input-suffix">
                        <span class="input-suffix__unit">USDT</span>
                    </span>
                </div>
            </div>
            
            <!-- Slider -->
            <div class="slider-section">
                <div class="slider-track">
                    <div class="slider-fill" style="width: 25%"></div>
                    <div class="slider-thumb" style="left: 25%"></div>
                </div>
                <div class="slider-labels">
                    <span class="slider-label">0%</span>
                    <span class="slider-label">25%</span>
                    <span class="slider-label">50%</span>
                    <span class="slider-label">75%</span>
                    <span class="slider-label">100%</span>
                </div>
            </div>
            
            <!-- Order Info -->
            <div class="order-info">
                <div class="info-row">
                    <span class="info-row__label">Available</span>
                    <span class="info-row__value">12,458.32 USDT</span>
                </div>
                <div class="info-row">
                    <span class="info-row__label">Max Size</span>
                    <span class="info-row__value">6.6124 BTC</span>
                </div>
                <div class="info-row">
                    <span class="info-row__label">Cost</span>
                    <span class="info-row__value">94.29 USDT</span>
                </div>
                <div class="info-row">
                    <span class="info-row__label">Liq. Price</span>
                    <span class="info-row__value link">Calculate</span>
                </div>
            </div>
            
            <!-- Options -->
            <div class="order-options">
                <label class="checkbox-label">
                    <input type="checkbox"> TP/SL
                </label>
                <label class="checkbox-label">
                    <input type="checkbox"> Reduce-Only
                </label>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn action-btn--long">Long</button>
                <button class="action-btn action-btn--short">Short</button>
            </div>
            
            <!-- Account Section -->
            <div class="account-section">
                <div class="account-header">
                    <span class="account-title">Unified Trading Account</span>
                    <span class="account-icons">⚙ 📊 P&L</span>
                </div>
                
                <div class="margin-bars">
                    <div class="margin-bar-row">
                        <span class="margin-bar-row__label">Initial Margin</span>
                        <div class="margin-bar-row__track">
                            <div class="margin-bar-row__fill green" style="width: 23%"></div>
                        </div>
                        <span class="margin-bar-row__value">23.45%</span>
                    </div>
                    <div class="margin-bar-row">
                        <span class="margin-bar-row__label">Maintenance Margin</span>
                        <div class="margin-bar-row__track">
                            <div class="margin-bar-row__fill yellow" style="width: 8%"></div>
                        </div>
                        <span class="margin-bar-row__value">8.12%</span>
                    </div>
                </div>
                
                <div class="balance-rows">
                    <div class="balance-row">
                        <span class="balance-row__label">Margin Balance</span>
                        <span class="balance-row__value">15,847.26 USDT</span>
                    </div>
                    <div class="balance-row">
                        <span class="balance-row__label">Available Balance</span>
                        <span class="balance-row__value">12,458.32 USDT</span>
                    </div>
                    <div class="balance-row">
                        <span class="balance-row__label">Unrealized PNL</span>
                        <span class="balance-row__value positive">+3,388.94 USDT</span>
                    </div>
                </div>
                
                <div class="account-actions">
                    <button class="acc-btn">Deposit</button>
                    <button class="acc-btn">Convert</button>
                    <button class="acc-btn">Transfer</button>
                </div>
            </div>
        </section>

        <!-- ========================================
             Bottom Panel
             ======================================== -->
        <div class="bottom-panel">
            <div class="panel-header">
                <div class="panel-tabs">
                    <button class="panel-tab">Open Orders <span class="tab-count">3</span></button>
                    <button class="panel-tab active">Positions <span class="tab-count">4</span></button>
                    <button class="panel-tab">Order History</button>
                    <button class="panel-tab">Trade History</button>
                    <button class="panel-tab">Assets</button>
                    <button class="panel-tab">P&L</button>
                </div>
            </div>
            
            <div class="panel-filters">
                <button class="filter-btn active">All Positions</button>
                <button class="filter-btn">USDT-M</button>
                <button class="filter-btn">COIN-M</button>
                <button class="filter-btn">Hide Others</button>
            </div>
            
            <div class="panel-content">
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
                    <tbody>
                        <tr>
                            <td>
                                <div class="coin-cell">
                                    <span class="coin-icon btc">₿</span>
                                    <div>
                                        <div style="font-weight: 600;">BTCUSDT</div>
                                        <div style="font-size: 10px; color: var(--text-muted);">50x Cross</div>
                                    </div>
                                </div>
                            </td>
                            <td class="long" style="font-family: var(--font-mono);">0.2500 BTC<br><span style="font-size: 10px; color: var(--text-muted);">$23,571.88</span></td>
                            <td style="font-family: var(--font-mono);">91,456.20</td>
                            <td style="font-family: var(--font-mono);">94,287.50</td>
                            <td style="font-family: var(--font-mono); color: var(--red);">85,124.30</td>
                            <td style="font-family: var(--font-mono);">471.44 USDT</td>
                            <td>
                                <div class="pnl-cell">
                                    <span class="pnl-cell__main up">+$707.83</span>
                                    <span class="pnl-cell__roe up">+150.18%</span>
                                </div>
                            </td>
                            <td><button class="close-btn">Close</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="coin-cell">
                                    <span class="coin-icon eth">Ξ</span>
                                    <div>
                                        <div style="font-weight: 600;">ETHUSDT</div>
                                        <div style="font-size: 10px; color: var(--text-muted);">25x Isolated</div>
                                    </div>
                                </div>
                            </td>
                            <td class="short" style="font-family: var(--font-mono);">-2.5000 ETH<br><span style="font-size: 10px; color: var(--text-muted);">$8,456.25</span></td>
                            <td style="font-family: var(--font-mono);">3,520.80</td>
                            <td style="font-family: var(--font-mono);">3,382.50</td>
                            <td style="font-family: var(--font-mono); color: var(--red);">3,892.45</td>
                            <td style="font-family: var(--font-mono);">338.25 USDT</td>
                            <td>
                                <div class="pnl-cell">
                                    <span class="pnl-cell__main up">+$345.75</span>
                                    <span class="pnl-cell__roe up">+102.22%</span>
                                </div>
                            </td>
                            <td><button class="close-btn">Close</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="coin-cell">
                                    <span class="coin-icon sol">◎</span>
                                    <div>
                                        <div style="font-weight: 600;">SOLUSDT</div>
                                        <div style="font-size: 10px; color: var(--text-muted);">20x Cross</div>
                                    </div>
                                </div>
                            </td>
                            <td class="long" style="font-family: var(--font-mono);">45.0000 SOL<br><span style="font-size: 10px; color: var(--text-muted);">$8,482.50</span></td>
                            <td style="font-family: var(--font-mono);">178.50</td>
                            <td style="font-family: var(--font-mono);">188.50</td>
                            <td style="font-family: var(--font-mono); color: var(--red);">156.20</td>
                            <td style="font-family: var(--font-mono);">424.13 USDT</td>
                            <td>
                                <div class="pnl-cell">
                                    <span class="pnl-cell__main up">+$450.00</span>
                                    <span class="pnl-cell__roe up">+106.11%</span>
                                </div>
                            </td>
                            <td><button class="close-btn">Close</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="coin-cell">
                                    <span class="coin-icon xrp">X</span>
                                    <div>
                                        <div style="font-weight: 600;">XRPUSDT</div>
                                        <div style="font-size: 10px; color: var(--text-muted);">30x Isolated</div>
                                    </div>
                                </div>
                            </td>
                            <td class="short" style="font-family: var(--font-mono);">-5000 XRP<br><span style="font-size: 10px; color: var(--text-muted);">$2,485.00</span></td>
                            <td style="font-family: var(--font-mono);">0.5280</td>
                            <td style="font-family: var(--font-mono);">0.4970</td>
                            <td style="font-family: var(--font-mono); color: var(--red);">0.6124</td>
                            <td style="font-family: var(--font-mono);">82.83 USDT</td>
                            <td>
                                <div class="pnl-cell">
                                    <span class="pnl-cell__main up">+$155.00</span>
                                    <span class="pnl-cell__roe up">+187.11%</span>
                                </div>
                            </td>
                            <td><button class="close-btn">Close</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="coin-cell">
                                    <span class="coin-icon doge">Ð</span>
                                    <div>
                                        <div style="font-weight: 600;">DOGEUSDT</div>
                                        <div style="font-size: 10px; color: var(--text-muted);">10x Cross</div>
                                    </div>
                                </div>
                            </td>
                            <td class="long" style="font-family: var(--font-mono);">25000 DOGE<br><span style="font-size: 10px; color: var(--text-muted);">$3,125.00</span></td>
                            <td style="font-family: var(--font-mono);">0.11500</td>
                            <td style="font-family: var(--font-mono);">0.12500</td>
                            <td style="font-family: var(--font-mono); color: var(--red);">0.09850</td>
                            <td style="font-family: var(--font-mono);">312.50 USDT</td>
                            <td>
                                <div class="pnl-cell">
                                    <span class="pnl-cell__main up">+$250.00</span>
                                    <span class="pnl-cell__roe up">+80.00%</span>
                                </div>
                            </td>
                            <td><button class="close-btn">Close</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================
         Interactive Scripts (Design Only)
         ======================================== -->
    <script>
        // Tab functionality
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.orderbook-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.orderbook-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.chart-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.order-type-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.order-type-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.interval-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.interval-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.ob-view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.ob-view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.chart-mode').forEach(mode => {
            mode.addEventListener('click', function() {
                document.querySelectorAll('.chart-mode').forEach(m => m.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Slider interaction
        const slider = document.querySelector('.slider-track');
        const fill = document.querySelector('.slider-fill');
        const thumb = document.querySelector('.slider-thumb');

        if (slider) {
            let isDragging = false;

            function updateSlider(e) {
                const rect = slider.getBoundingClientRect();
                let percent = ((e.clientX - rect.left) / rect.width) * 100;
                percent = Math.max(0, Math.min(100, percent));
                
                fill.style.width = percent + '%';
                thumb.style.left = percent + '%';
            }

            slider.addEventListener('mousedown', (e) => {
                isDragging = true;
                updateSlider(e);
            });

            document.addEventListener('mousemove', (e) => {
                if (isDragging) updateSlider(e);
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
            });

            document.querySelectorAll('.slider-label').forEach(label => {
                label.addEventListener('click', function() {
                    const percent = parseInt(this.textContent);
                    fill.style.width = percent + '%';
                    thumb.style.left = percent + '%';
                });
            });
        }

        // Button hover effects
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });

        // Price animation simulation (design demo)
        function animatePrice() {
            const priceEl = document.querySelector('.symbol-price__current');
            const spreadEl = document.querySelector('.spread-price');
            
            if (priceEl && spreadEl) {
                const basePrice = 94287.50;
                const variation = (Math.random() - 0.5) * 50;
                const newPrice = (basePrice + variation).toFixed(2);
                const formatted = parseFloat(newPrice).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                priceEl.textContent = formatted;
                spreadEl.textContent = formatted;
                
                if (variation > 0) {
                    priceEl.classList.remove('down');
                    priceEl.classList.add('up');
                    spreadEl.classList.remove('down');
                    spreadEl.classList.add('up');
                } else {
                    priceEl.classList.remove('up');
                    priceEl.classList.add('down');
                    spreadEl.classList.remove('up');
                    spreadEl.classList.add('down');
                }
            }
        }

        // Update price every 2 seconds for demo
        setInterval(animatePrice, 2000);

        console.log('FuturX Pro Design V5 Loaded Successfully');
    </script>

</body>
</html>
