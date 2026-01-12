<?php
/**
 * FuturX - P&L Analysis (Trading Performance)
 * Bybit 스타일 손익 분석 페이지
 */

require_once 'config.php';

// 인증 체크
$authToken = $_COOKIE['auth_token'] ?? null;
$currentUser = null;

if ($authToken) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT u.* FROM users u JOIN user_sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW()");
        $stmt->execute([$authToken]);
        $currentUser = $stmt->fetch();
    } catch (Exception $e) {
        // 무시
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P&L Analysis - FuturX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-dark: #0b0e11;
            --bg-card: #161a1e;
            --bg-input: #1e2329;
            --bg-hover: #2b3139;
            --border: #2b3139;
            --text: #eaecef;
            --text-secondary: #848e9c;
            --text-muted: #5e6673;
            --green: #0ecb81;
            --green-bg: rgba(14, 203, 129, 0.1);
            --red: #f6465d;
            --red-bg: rgba(246, 70, 93, 0.1);
            --yellow: #f0b90b;
            --orange: #f7931a;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --mono: 'JetBrains Mono', monospace;
        }

        body {
            font-family: var(--font);
            background: #fafafa;
            color: #1e2329;
            font-size: 14px;
            line-height: 1.5;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #1e2329;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fff;
            border: 1px solid #e6e8ea;
            border-radius: 8px;
            color: #1e2329;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #f5f5f5;
        }

        /* Tabs */
        .main-tabs {
            display: flex;
            gap: 24px;
            border-bottom: 1px solid #e6e8ea;
            margin-bottom: 24px;
        }

        .main-tab {
            padding: 12px 0;
            background: transparent;
            border: none;
            font-size: 14px;
            color: #848e9c;
            cursor: pointer;
            position: relative;
            transition: color 0.2s;
        }

        .main-tab:hover {
            color: #1e2329;
        }

        .main-tab.active {
            color: #1e2329;
            font-weight: 500;
        }

        .main-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #1e2329;
        }

        /* Filters */
        .filters-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-label {
            font-size: 12px;
            color: #848e9c;
        }

        .filter-select {
            padding: 8px 12px;
            background: #fff;
            border: 1px solid #e6e8ea;
            border-radius: 6px;
            font-size: 13px;
            color: #1e2329;
            min-width: 140px;
            cursor: pointer;
        }

        /* Period Tabs */
        .period-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
        }

        .period-tab {
            padding: 8px 16px;
            background: transparent;
            border: 1px solid #e6e8ea;
            border-radius: 6px;
            font-size: 13px;
            color: #848e9c;
            cursor: pointer;
            transition: all 0.2s;
        }

        .period-tab:hover {
            border-color: #1e2329;
            color: #1e2329;
        }

        .period-tab.active {
            background: transparent;
            border-color: var(--orange);
            color: var(--orange);
        }

        /* P&L Summary */
        .pnl-summary-section {
            margin-bottom: 32px;
        }

        .pnl-summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e2329;
        }

        .data-timestamp {
            font-size: 12px;
            color: #848e9c;
        }

        .pnl-summary-cards {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }

        .pnl-summary-card {
            flex: 1;
        }

        .pnl-summary-label {
            font-size: 12px;
            color: #848e9c;
            margin-bottom: 4px;
        }

        .pnl-summary-value {
            font-size: 28px;
            font-weight: 600;
            font-family: var(--mono);
        }

        .pnl-summary-value.negative {
            color: var(--red);
        }

        .pnl-summary-value.positive {
            color: var(--green);
        }

        .pnl-summary-unit {
            font-size: 14px;
            color: #848e9c;
            margin-left: 4px;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-card {
            background: #fff;
            border: 1px solid #e6e8ea;
            border-radius: 12px;
            padding: 20px;
        }

        .chart-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .chart-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e2329;
        }

        .chart-link {
            color: #848e9c;
            font-size: 12px;
        }

        .chart-container {
            height: 250px;
            position: relative;
        }

        /* P&L Ranking */
        .ranking-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ranking-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #848e9c;
            padding-bottom: 8px;
            border-bottom: 1px solid #e6e8ea;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ranking-coin {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 100px;
        }

        .ranking-coin img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }

        .ranking-coin-name {
            font-size: 13px;
            font-weight: 500;
        }

        .ranking-bar-container {
            flex: 1;
            height: 16px;
            background: #f5f5f5;
            border-radius: 4px;
            overflow: hidden;
        }

        .ranking-bar {
            height: 100%;
            border-radius: 4px;
        }

        .ranking-bar.positive {
            background: var(--green);
        }

        .ranking-bar.negative {
            background: var(--red);
        }

        .ranking-value {
            min-width: 120px;
            text-align: right;
            font-size: 13px;
            font-family: var(--mono);
        }

        .ranking-value.positive {
            color: var(--green);
        }

        .ranking-value.negative {
            color: var(--red);
        }

        /* Stats Section */
        .stats-section {
            margin-bottom: 32px;
        }

        .stats-tabs {
            display: flex;
            gap: 24px;
            border-bottom: 1px solid #e6e8ea;
            margin-bottom: 24px;
        }

        .stats-tab {
            padding: 12px 0;
            background: transparent;
            border: none;
            font-size: 14px;
            font-weight: 500;
            color: #848e9c;
            cursor: pointer;
            position: relative;
        }

        .stats-tab.active {
            color: #1e2329;
        }

        .stats-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #1e2329;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .stats-card {
            background: #fff;
            border: 1px solid #e6e8ea;
            border-radius: 12px;
            padding: 20px;
        }

        .stats-card-header {
            font-size: 12px;
            color: #848e9c;
            margin-bottom: 12px;
        }

        .stats-card-value {
            font-size: 24px;
            font-weight: 600;
            font-family: var(--mono);
            margin-bottom: 4px;
        }

        .stats-card-value.negative {
            color: var(--red);
        }

        .stats-card-value.positive {
            color: var(--green);
        }

        .stats-card-sub {
            font-size: 12px;
            color: #848e9c;
        }

        .stats-card-sub span {
            margin-right: 8px;
        }

        .stats-card-sub .green {
            color: var(--green);
        }

        .stats-card-sub .red {
            color: var(--red);
        }

        .stats-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .stats-icon.red {
            background: var(--red-bg);
            color: var(--red);
        }

        .stats-icon.green {
            background: var(--green-bg);
            color: var(--green);
        }

        /* Details Table */
        .details-section {
            background: #fff;
            border: 1px solid #e6e8ea;
            border-radius: 12px;
            overflow: hidden;
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e6e8ea;
        }

        .details-title {
            font-size: 14px;
            font-weight: 600;
        }

        .details-controls {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #848e9c;
            cursor: pointer;
        }

        .refresh-info {
            font-size: 11px;
            color: #848e9c;
        }

        .refresh-btn {
            background: none;
            border: none;
            color: #848e9c;
            cursor: pointer;
            padding: 4px;
        }

        /* Type Tabs */
        .type-tabs {
            display: flex;
            gap: 0;
            padding: 0 20px;
            border-bottom: 1px solid #e6e8ea;
        }

        .type-tab {
            padding: 12px 16px;
            background: transparent;
            border: none;
            font-size: 12px;
            color: #848e9c;
            cursor: pointer;
            position: relative;
            white-space: nowrap;
        }

        .type-tab.active {
            color: var(--orange);
            background: rgba(247, 147, 26, 0.05);
            border-radius: 6px 6px 0 0;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 500;
            color: #848e9c;
            background: #fafafa;
            border-bottom: 1px solid #e6e8ea;
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px 16px;
            font-size: 13px;
            border-bottom: 1px solid #f5f5f5;
            white-space: nowrap;
        }

        .data-table tbody tr:hover {
            background: #fafafa;
        }

        .coin-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .coin-cell img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }

        .trade-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .trade-type.long {
            background: var(--green-bg);
            color: var(--green);
        }

        .trade-type.short {
            background: var(--red-bg);
            color: var(--red);
        }

        .result-badge {
            font-size: 12px;
            font-weight: 500;
        }

        .result-badge.win {
            color: var(--green);
        }

        .result-badge.loss {
            color: var(--red);
        }

        .num-positive {
            color: var(--green);
        }

        .num-negative {
            color: var(--red);
        }

        /* Pagination */
        .table-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 16px 20px;
            gap: 8px;
        }

        .page-btn {
            padding: 8px 16px;
            background: #fff;
            border: 1px solid #e6e8ea;
            border-radius: 6px;
            font-size: 13px;
            color: #1e2329;
            cursor: pointer;
            transition: all 0.2s;
        }

        .page-btn:hover:not(:disabled) {
            background: #f5f5f5;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #848e9c;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Loading */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #848e9c;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #e6e8ea;
            border-top-color: var(--orange);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .charts-section {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }

            .pnl-summary-cards {
                flex-direction: column;
                gap: 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .period-tabs {
                flex-wrap: wrap;
            }

            .type-tabs {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">P&L Analysis</h1>
            <a href="trade.php" class="back-btn">
                <span>←</span> Back to Trade
            </a>
        </div>

        <!-- Main Tabs -->
        <div class="main-tabs">
            <button class="main-tab" data-tab="assets">Assets</button>
            <button class="main-tab active" data-tab="perpetuals">Perpetuals & Futures</button>
            <button class="main-tab" data-tab="spot">Spot</button>
            <button class="main-tab" data-tab="options">Options</button>
        </div>

        <!-- Filters -->
        <div class="filters-row">
            <div class="filter-group">
                <span class="filter-label">Contracts</span>
                <select class="filter-select" id="contractFilter">
                    <option value="all">All Contracts</option>
                    <option value="BTCUSDT">BTCUSDT</option>
                    <option value="ETHUSDT">ETHUSDT</option>
                    <option value="SOLUSDT">SOLUSDT</option>
                    <option value="XRPUSDT">XRPUSDT</option>
                    <option value="DOGEUSDT">DOGEUSDT</option>
                </select>
            </div>
        </div>

        <!-- Period Tabs -->
        <div class="period-tabs">
            <button class="period-tab" data-period="7">Last 7 D</button>
            <button class="period-tab" data-period="30">Last 30 D</button>
            <button class="period-tab" data-period="60">Last 60 D</button>
            <button class="period-tab" data-period="90">Last 90 D</button>
            <button class="period-tab active" data-period="180">Last 180 D</button>
            <button class="period-tab" data-period="custom">Custom Time Frame</button>
        </div>

        <!-- P&L Summary -->
        <div class="pnl-summary-section">
            <div class="pnl-summary-header">
                <span class="section-title">P&L</span>
                <span class="data-timestamp" id="dataTimestamp">Data as of 2026-01-12 23:59:59 (UTC)</span>
            </div>
            <div class="pnl-summary-cards">
                <div class="pnl-summary-card">
                    <div class="pnl-summary-label">Total P&L</div>
                    <div class="pnl-summary-value negative" id="totalPnl">
                        -366,700.84<span class="pnl-summary-unit">USD</span>
                    </div>
                </div>
                <div class="pnl-summary-card">
                    <div class="pnl-summary-label">Trading Volume</div>
                    <div class="pnl-summary-value" id="tradingVolume">
                        +401,986,088.26<span class="pnl-summary-unit">USD</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-section">
            <!-- P&L Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <span class="chart-title">P&L Chart</span>
                    <span class="chart-link">↗</span>
                </div>
                <div class="chart-container">
                    <canvas id="pnlChart"></canvas>
                </div>
            </div>

            <!-- P&L Ranking -->
            <div class="chart-card">
                <div class="chart-header">
                    <span class="chart-title">P&L Ranking</span>
                    <span class="chart-link">↗</span>
                </div>
                <div class="ranking-header">
                    <span>Contracts</span>
                    <span>P&L</span>
                </div>
                <div class="ranking-list" id="pnlRanking">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stats-tabs">
                <button class="stats-tab active" data-stats="orders">Closed Orders</button>
                <button class="stats-tab" data-stats="positions">Closed Positions</button>
            </div>
            <div class="data-timestamp" style="text-align: right; margin-bottom: 16px;">
                Data as of 2026-01-12 23:59:59 (UTC)
            </div>
            <div class="stats-grid" id="statsGrid">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Details Table -->
        <div class="details-section">
            <div class="details-header">
                <span class="details-title">Details of Closed Orders</span>
                <div class="details-controls">
                    <label class="checkbox-label">
                        <input type="checkbox" id="showPreUta">
                        Show Pre-UTA Data
                    </label>
                    <span class="refresh-info" id="refreshInfo">Real-time updates, last refreshed at 2026-01-12 21:31:41 (UTC)</span>
                    <button class="refresh-btn" onclick="refreshData()">↻</button>
                </div>
            </div>
            <div class="type-tabs">
                <button class="type-tab active" data-type="usdt-perp">USDT Perpetuals</button>
                <button class="type-tab" data-type="usdc-perp">USDC Perpetuals</button>
                <button class="type-tab" data-type="usdc-futures">USDC Futures</button>
                <button class="type-tab" data-type="inverse-perp">Inverse Perpetuals</button>
                <button class="type-tab" data-type="inverse-futures">Inverse Futures</button>
                <button class="type-tab" data-type="usdt-futures">USDT Futures</button>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Contracts</th>
                            <th>Qty</th>
                            <th>Entry Price</th>
                            <th>Exit Price</th>
                            <th>Trade Type</th>
                            <th>Closed P&L</th>
                            <th>Result</th>
                            <th>Open Trade Volume</th>
                            <th>Closed</th>
                        </tr>
                    </thead>
                    <tbody id="detailsTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <button class="page-btn" id="prevBtn" disabled>Previous</button>
                <button class="page-btn" id="nextBtn">Next</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const TRADE_API = 'trade_api.php';
        const authToken = document.cookie.split('; ').find(row => row.startsWith('auth_token='))?.split('=')[1];

        // 코인 아이콘
        const COIN_ICONS = {
            'BTCUSDT': 'https://assets.coingecko.com/coins/images/1/small/bitcoin.png',
            'ETHUSDT': 'https://assets.coingecko.com/coins/images/279/small/ethereum.png',
            'SOLUSDT': 'https://assets.coingecko.com/coins/images/4128/small/solana.png',
            'XRPUSDT': 'https://assets.coingecko.com/coins/images/44/small/xrp-symbol-white-128.png',
            'DOGEUSDT': 'https://assets.coingecko.com/coins/images/5/small/dogecoin.png',
            'LTCUSDT': 'https://assets.coingecko.com/coins/images/2/small/litecoin.png',
            'ZECUSDT': 'https://assets.coingecko.com/coins/images/486/small/circle-zcash-color.png'
        };

        let currentPeriod = 180;
        let currentPage = 1;
        let pnlChart = null;
        let closedPositions = [];

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            initTabs();
            loadPerformanceData();
        });

        function initTabs() {
            // Period tabs
            document.querySelectorAll('.period-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    currentPeriod = parseInt(this.dataset.period) || 180;
                    loadPerformanceData();
                });
            });

            // Stats tabs
            document.querySelectorAll('.stats-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.stats-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    updateStatsGrid(this.dataset.stats);
                });
            });

            // Type tabs
            document.querySelectorAll('.type-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.type-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Pagination
            document.getElementById('prevBtn').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderDetailsTable();
                }
            });

            document.getElementById('nextBtn').addEventListener('click', () => {
                currentPage++;
                renderDetailsTable();
            });
        }

        async function loadPerformanceData() {
            if (!authToken) {
                showEmptyState();
                return;
            }

            try {
                const response = await fetch(`${TRADE_API}?action=get_closed_positions&period=${currentPeriod}`, {
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });

                const data = await response.json();
                if (data.success) {
                    closedPositions = data.data.positions || [];
                    calculateAndRenderStats();
                    renderPnlChart();
                    renderPnlRanking();
                    renderDetailsTable();
                    updateTimestamp();
                } else {
                    showEmptyState();
                }
            } catch (error) {
                console.error('Load performance error:', error);
                showEmptyState();
            }
        }

        function calculateAndRenderStats() {
            const totalPnl = closedPositions.reduce((sum, p) => sum + (parseFloat(p.realized_pnl) || 0), 0);
            const totalVolume = closedPositions.reduce((sum, p) => {
                const qty = p.size || (p.margin / p.entry_price * p.leverage);
                return sum + (qty * p.exit_price);
            }, 0);

            const longPositions = closedPositions.filter(p => p.side === 'long');
            const shortPositions = closedPositions.filter(p => p.side === 'short');

            const longWins = longPositions.filter(p => parseFloat(p.realized_pnl) > 0);
            const shortWins = shortPositions.filter(p => parseFloat(p.realized_pnl) > 0);

            const longPnl = longPositions.reduce((sum, p) => sum + (parseFloat(p.realized_pnl) || 0), 0);
            const shortPnl = shortPositions.reduce((sum, p) => sum + (parseFloat(p.realized_pnl) || 0), 0);

            const totalCount = closedPositions.length;
            const winCount = closedPositions.filter(p => parseFloat(p.realized_pnl) > 0).length;
            const winRate = totalCount > 0 ? (winCount / totalCount * 100).toFixed(0) : 0;

            // Update summary
            const totalPnlEl = document.getElementById('totalPnl');
            totalPnlEl.innerHTML = `${totalPnl >= 0 ? '+' : ''}${formatNumber(totalPnl)}<span class="pnl-summary-unit">USD</span>`;
            totalPnlEl.className = `pnl-summary-value ${totalPnl >= 0 ? 'positive' : 'negative'}`;

            document.getElementById('tradingVolume').innerHTML =
                `+${formatNumber(totalVolume)}<span class="pnl-summary-unit">USD</span>`;

            // Update stats grid
            const statsGrid = document.getElementById('statsGrid');
            statsGrid.innerHTML = `
                <div class="stats-card">
                    <div class="stats-icon red">○</div>
                    <div class="stats-card-header">Total Closed Orders</div>
                    <div class="stats-card-value">${totalCount}</div>
                    <div class="stats-card-sub">
                        <span class="green">${longPositions.length} Close Long</span> /
                        <span class="red">${shortPositions.length} Close Short</span>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-icon green">◐</div>
                    <div class="stats-card-header">Win Rate Of Closed Orders</div>
                    <div class="stats-card-value">${winRate} %</div>
                    <div class="stats-card-sub">
                        ${winCount} Win / ${totalCount - winCount} Loss
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-header">P&L of Closed Long Orders</div>
                    <div class="stats-card-value ${longPnl >= 0 ? 'positive' : 'negative'}">
                        ${longPnl >= 0 ? '+' : ''}${formatNumber(longPnl)} <small>USD</small>
                    </div>
                    <div class="stats-card-sub">
                        Win Rate: ${longPositions.length > 0 ? (longWins.length / longPositions.length * 100).toFixed(0) : 0} %
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-header">P&L of Closed Short Orders</div>
                    <div class="stats-card-value ${shortPnl >= 0 ? 'positive' : 'negative'}">
                        ${shortPnl >= 0 ? '+' : ''}${formatNumber(shortPnl)} <small>USD</small>
                    </div>
                    <div class="stats-card-sub">
                        Win Rate: ${shortPositions.length > 0 ? (shortWins.length / shortPositions.length * 100).toFixed(0) : 0} %
                    </div>
                </div>
            `;
        }

        function renderPnlChart() {
            const ctx = document.getElementById('pnlChart').getContext('2d');

            if (pnlChart) {
                pnlChart.destroy();
            }

            // 날짜별로 그룹화
            const dailyPnl = {};
            closedPositions.forEach(pos => {
                const date = pos.closed_at?.split(' ')[0] || pos.created_at?.split(' ')[0];
                if (date) {
                    if (!dailyPnl[date]) dailyPnl[date] = 0;
                    dailyPnl[date] += parseFloat(pos.realized_pnl) || 0;
                }
            });

            const sortedDates = Object.keys(dailyPnl).sort();
            let cumulative = 0;
            const cumulativeData = sortedDates.map(date => {
                cumulative += dailyPnl[date];
                return cumulative;
            });

            pnlChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: sortedDates,
                    datasets: [{
                        label: 'Cumulative P&L',
                        data: cumulativeData,
                        borderColor: cumulativeData[cumulativeData.length - 1] >= 0 ? '#0ecb81' : '#f6465d',
                        backgroundColor: cumulativeData[cumulativeData.length - 1] >= 0
                            ? 'rgba(14, 203, 129, 0.1)'
                            : 'rgba(246, 70, 93, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            grid: { color: '#f0f0f0' }
                        }
                    }
                }
            });
        }

        function renderPnlRanking() {
            const rankingEl = document.getElementById('pnlRanking');

            // 심볼별 P&L 집계
            const symbolPnl = {};
            closedPositions.forEach(pos => {
                if (!symbolPnl[pos.symbol]) symbolPnl[pos.symbol] = 0;
                symbolPnl[pos.symbol] += parseFloat(pos.realized_pnl) || 0;
            });

            const sortedSymbols = Object.entries(symbolPnl)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 5);

            const maxAbs = Math.max(...sortedSymbols.map(([_, v]) => Math.abs(v)));

            rankingEl.innerHTML = sortedSymbols.map(([symbol, pnl]) => {
                const barWidth = maxAbs > 0 ? (Math.abs(pnl) / maxAbs * 100) : 0;
                const icon = COIN_ICONS[symbol] || COIN_ICONS['BTCUSDT'];

                return `
                    <div class="ranking-item">
                        <div class="ranking-coin">
                            <img src="${icon}" alt="${symbol}">
                            <span class="ranking-coin-name">${symbol}</span>
                        </div>
                        <div class="ranking-bar-container">
                            <div class="ranking-bar ${pnl >= 0 ? 'positive' : 'negative'}"
                                 style="width: ${barWidth}%"></div>
                        </div>
                        <div class="ranking-value ${pnl >= 0 ? 'positive' : 'negative'}">
                            ${pnl >= 0 ? '+' : ''}${formatNumber(pnl)} USD
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderDetailsTable() {
            const tbody = document.getElementById('detailsTableBody');
            const perPage = 10;
            const start = (currentPage - 1) * perPage;
            const pageData = closedPositions.slice(start, start + perPage);

            if (pageData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">No data available</td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = pageData.map(pos => {
                const icon = COIN_ICONS[pos.symbol] || COIN_ICONS['BTCUSDT'];
                const qty = pos.size || (pos.margin / pos.entry_price * pos.leverage);
                const pnl = parseFloat(pos.realized_pnl) || 0;
                const isWin = pnl > 0;
                const tradeType = pos.side === 'long' ? 'Close Short' : 'Close Long';
                const volume = qty * pos.entry_price;

                return `
                    <tr>
                        <td>
                            <div class="coin-cell">
                                <img src="${icon}" alt="${pos.symbol}">
                                ${pos.symbol}
                            </div>
                        </td>
                        <td style="font-family: var(--mono);">${qty.toFixed(2)}</td>
                        <td style="font-family: var(--mono);">${formatPrice(pos.entry_price)}</td>
                        <td style="font-family: var(--mono);">${formatPrice(pos.exit_price)}</td>
                        <td>
                            <span class="trade-type ${pos.side === 'long' ? 'short' : 'long'}">
                                ${tradeType}
                            </span>
                        </td>
                        <td class="${pnl >= 0 ? 'num-positive' : 'num-negative'}" style="font-family: var(--mono);">
                            ${pnl >= 0 ? '+' : ''}${formatNumber(pnl)}
                        </td>
                        <td>
                            <span class="result-badge ${isWin ? 'win' : 'loss'}">
                                ${isWin ? 'Win' : 'Loss'}
                            </span>
                        </td>
                        <td style="font-family: var(--mono);">${formatNumber(volume)}</td>
                        <td style="font-family: var(--mono);">${formatNumber(volume)}</td>
                    </tr>
                `;
            }).join('');

            // Update pagination
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = start + perPage >= closedPositions.length;
        }

        function updateTimestamp() {
            const now = new Date();
            const formatted = now.toISOString().replace('T', ' ').substring(0, 19) + ' (UTC)';
            document.getElementById('dataTimestamp').textContent = `Data as of ${formatted}`;
            document.getElementById('refreshInfo').textContent = `Real-time updates, last refreshed at ${formatted}`;
        }

        function showEmptyState() {
            document.getElementById('detailsTableBody').innerHTML = `
                <tr>
                    <td colspan="9" class="empty-state">
                        <div>No trading data available</div>
                        <div style="font-size: 12px; margin-top: 8px;">Start trading to see your performance</div>
                    </td>
                </tr>
            `;
        }

        function formatNumber(num) {
            return parseFloat(num).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatPrice(price) {
            return parseFloat(price).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function refreshData() {
            loadPerformanceData();
        }

        function updateStatsGrid(type) {
            // Re-render stats based on type (orders vs positions)
            calculateAndRenderStats();
        }
    </script>
</body>
</html>
