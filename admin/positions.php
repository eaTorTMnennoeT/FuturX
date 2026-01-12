<?php
/**
 * FuturX Admin - 포지션 관리 (실시간)
 * Socket.IO를 통한 실시간 포지션 모니터링
 */

require_once 'layout.php';

renderHeader('포지션 관리');
renderPageHeader('포지션 관리', '실시간으로 오픈 포지션을 모니터링합니다', [
    ['label' => '새로고침', 'icon' => 'fa-sync-alt', 'class' => 'btn--secondary', 'onclick' => 'refreshPositions()']
]);
?>

<!-- 바이낸스 연결 상태 패널 -->
<div class="connection-panel" id="connectionPanel">
    <div class="connection-panel__header">
        <div class="connection-panel__status" id="binanceStatus">
            <span class="connection-dot"></span>
            <span class="connection-text">바이낸스 연결 중...</span>
        </div>
        <div class="connection-panel__server">
            <img src="https://bin.bnbstatic.com/static/images/common/favicon.ico" alt="Binance" style="width: 16px; height: 16px;">
            <span>wss://fstream.binance.com</span>
        </div>
    </div>
    <div class="connection-panel__stats">
        <div class="conn-stat">
            <div class="conn-stat__icon"><i class="fas fa-clock"></i></div>
            <div class="conn-stat__info">
                <span class="conn-stat__label">바이낸스 연결</span>
                <span class="conn-stat__value" id="binanceUptime">--</span>
            </div>
        </div>
        <div class="conn-stat">
            <div class="conn-stat__icon"><i class="fas fa-wifi"></i></div>
            <div class="conn-stat__info">
                <span class="conn-stat__label">바이낸스 Ping</span>
                <span class="conn-stat__value" id="binancePing">--ms</span>
            </div>
        </div>
        <div class="conn-stat">
            <div class="conn-stat__icon"><i class="fas fa-exchange-alt"></i></div>
            <div class="conn-stat__info">
                <span class="conn-stat__label">수신 메시지</span>
                <span class="conn-stat__value" id="binanceMsgCount">0</span>
            </div>
        </div>
        <div class="conn-stat">
            <div class="conn-stat__icon"><i class="fas fa-stream"></i></div>
            <div class="conn-stat__info">
                <span class="conn-stat__label">활성 스트림</span>
                <span class="conn-stat__value" id="binanceStreams">0</span>
            </div>
        </div>
        <div class="conn-stat">
            <div class="conn-stat__icon"><i class="fas fa-users"></i></div>
            <div class="conn-stat__info">
                <span class="conn-stat__label">접속자</span>
                <span class="conn-stat__value" id="connOnline">0</span>
            </div>
        </div>
        <div class="conn-stat">
            <div class="conn-stat__icon"><i class="fas fa-sync"></i></div>
            <div class="conn-stat__info">
                <span class="conn-stat__label">바이낸스 재연결</span>
                <span class="conn-stat__value" id="binanceReconnects">0</span>
            </div>
        </div>
    </div>
</div>

<!-- 통계 카드 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-success-bg); color: var(--color-success);">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">오픈 포지션</span>
            <span class="stat-card__value" id="stat-total-positions">0</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-success-bg); color: var(--color-success);">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">롱 포지션</span>
            <span class="stat-card__value" id="stat-long-positions">0</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-danger-bg); color: var(--color-danger);">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">숏 포지션</span>
            <span class="stat-card__value" id="stat-short-positions">0</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">총 미실현 PnL</span>
            <span class="stat-card__value" id="stat-total-pnl">$0.00</span>
        </div>
    </div>
</div>

<!-- 현재가 표시 -->
<div class="price-ticker mb-xl">
    <div class="price-ticker__symbol">
        <i class="fab fa-bitcoin"></i>
        BTC/USDT
    </div>
    <div class="price-ticker__price" id="current-price">--</div>
    <div class="price-ticker__change" id="price-change">+0.00%</div>
</div>

<!-- 포지션 테이블 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-chart-line"></i>
            실시간 오픈 포지션
            <span class="live-badge">LIVE</span>
        </h3>
        <div class="card__actions">
            <input type="text" class="form-input" placeholder="회원 검색..." id="searchInput" style="width: 200px;">
        </div>
    </div>
    <div class="card__body" style="padding: 0;">
        <div class="table-wrapper">
            <table class="table" id="positionsTable">
                <thead>
                    <tr>
                        <th>회원</th>
                        <th>심볼</th>
                        <th>방향</th>
                        <th>수량</th>
                        <th>진입가</th>
                        <th>현재가</th>
                        <th>레버리지</th>
                        <th>청산가</th>
                        <th>미실현 PnL</th>
                        <th>ROE</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody id="positionsBody">
                    <tr>
                        <td colspan="11" class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>포지션 데이터 로딩 중...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 포지션 상세 모달 -->
<div class="modal-overlay" id="positionModal" onclick="closePositionModal()">
    <div class="modal" style="max-width: 600px;" onclick="event.stopPropagation()">
        <div class="modal__header">
            <h3 class="modal__title">포지션 상세</h3>
            <button type="button" class="modal__close" id="modalCloseBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body" id="positionModalBody">
            <!-- 동적으로 채워짐 -->
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--danger" id="forceLiquidateBtn">
                <i class="fas fa-bolt"></i>
                강제청산
            </button>
            <button type="button" class="btn btn--secondary" id="modalCloseBtnFooter">닫기</button>
        </div>
    </div>
</div>

<style>
/* 연결 상태 패널 */
.connection-panel {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    margin-bottom: 20px;
    overflow: hidden;
}

.connection-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border-color);
}

.connection-panel__status {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 500;
}

.connection-panel__server {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--text-secondary);
    font-family: var(--font-mono);
}

.connection-panel__server i {
    color: var(--color-primary);
}

.connection-panel__stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1px;
    background: var(--border-color);
}

.conn-stat {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--bg-secondary);
}

.conn-stat__icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: var(--bg-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: var(--text-secondary);
}

.conn-stat__info {
    display: flex;
    flex-direction: column;
}

.conn-stat__label {
    font-size: 10px;
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.conn-stat__value {
    font-size: 14px;
    font-weight: 600;
    font-family: var(--font-mono);
    color: var(--text-primary);
}

/* 연결 상태 아이콘 색상 */
.conn-stat:nth-child(1) .conn-stat__icon { color: var(--color-info); }
.conn-stat:nth-child(2) .conn-stat__icon { color: var(--color-success); }
.conn-stat:nth-child(3) .conn-stat__icon { color: var(--color-warning); }
.conn-stat:nth-child(4) .conn-stat__icon { color: var(--color-primary); }
.conn-stat:nth-child(5) .conn-stat__icon { color: #a855f7; }
.conn-stat:nth-child(6) .conn-stat__icon { color: var(--color-danger); }

/* Ping 색상 */
.conn-stat__value.ping-good { color: var(--color-success); }
.conn-stat__value.ping-medium { color: var(--color-warning); }
.conn-stat__value.ping-bad { color: var(--color-danger); }

.connection-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--color-warning);
    animation: pulse 1.5s infinite;
}

.connection-panel__status.connected .connection-dot {
    background: var(--color-success);
    box-shadow: 0 0 10px var(--color-success);
}

.connection-panel__status.disconnected .connection-dot {
    background: var(--color-danger);
    animation: none;
}

.connection-panel__status.connected .connection-text {
    color: var(--color-success);
}

.connection-panel__status.disconnected .connection-text {
    color: var(--color-danger);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* 반응형 */
@media (max-width: 1200px) {
    .connection-panel__stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .connection-panel__stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .connection-panel__header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}

/* 가격 티커 */
.price-ticker {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 24px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
}

.price-ticker__symbol {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
}

.price-ticker__symbol i {
    color: #f7931a;
    font-size: 24px;
}

.price-ticker__price {
    font-size: 32px;
    font-weight: 700;
    font-family: var(--font-mono);
}

.price-ticker__price.up {
    color: var(--color-success);
}

.price-ticker__price.down {
    color: var(--color-danger);
}

.price-ticker__change {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    font-family: var(--font-mono);
}

.price-ticker__change.up {
    background: var(--color-success-bg);
    color: var(--color-success);
}

.price-ticker__change.down {
    background: var(--color-danger-bg);
    color: var(--color-danger);
}

/* LIVE 배지 */
.live-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    background: var(--color-danger);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    border-radius: 4px;
    margin-left: 10px;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* 테이블 스타일 개선 */
#positionsTable .user-cell {
    display: flex;
    flex-direction: column;
}

#positionsTable .user-email {
    font-weight: 500;
    font-size: 13px;
}

#positionsTable .user-id {
    font-size: 11px;
    color: var(--color-primary);
}

#positionsTable .side-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

#positionsTable .side-badge.long {
    background: var(--color-success-bg);
    color: var(--color-success);
}

#positionsTable .side-badge.short {
    background: var(--color-danger-bg);
    color: var(--color-danger);
}

#positionsTable .leverage-badge {
    background: var(--color-warning-bg);
    color: var(--color-warning);
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

/* 테이블 반응형 - 가로 스크롤 */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

#positionsTable {
    table-layout: auto;
    width: 100%;
    min-width: 1100px;
}

#positionsTable th,
#positionsTable td {
    white-space: nowrap;
    padding: 12px 10px;
}

#positionsTable th {
    font-size: 12px;
}

#positionsTable td {
    font-size: 13px;
}

#positionsTable .pnl-value {
    font-weight: 600;
    font-family: var(--font-mono);
}

#positionsTable .pnl-value.profit {
    color: var(--color-success);
}

#positionsTable .pnl-value.loss {
    color: var(--color-danger);
}

#positionsTable .roe-value {
    font-weight: 600;
    font-family: var(--font-mono);
    font-size: 12px;
}

#positionsTable .liq-price {
    color: var(--color-danger);
    font-family: var(--font-mono);
}

/* 포지션 행 하이라이트 */
.position-row {
    transition: background 0.2s ease;
}

.position-row:hover {
    background: var(--bg-tertiary);
}

.position-row.new {
    animation: highlightNew 1s ease;
}

@keyframes highlightNew {
    0% { background: rgba(14, 203, 129, 0.2); }
    100% { background: transparent; }
}

/* 모달 상세 스타일 */
.position-detail {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.position-detail__item {
    padding: 12px;
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
}

.position-detail__label {
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.position-detail__value {
    font-size: 15px;
    font-weight: 600;
    font-family: var(--font-mono);
}

.position-detail__item.full-width {
    grid-column: span 2;
}

/* 카드 액션 */
.card__actions {
    display: flex;
    gap: 10px;
}

/* 모달 버튼 수정 */
#positionModal .modal__close,
#positionModal .btn {
    cursor: pointer !important;
    pointer-events: auto !important;
}

#positionModal .modal__footer {
    position: relative;
    z-index: 10;
}

#positionModal .modal__header {
    position: relative;
    z-index: 10;
}

/* 모달 내부 클릭 영역 보장 */
#positionModal .modal {
    position: relative;
    z-index: 1001;
}
</style>

<!-- Socket.IO 클라이언트 -->
<script src="<?= WS_SERVER_URL ?>/socket.io/socket.io.js"></script>
<script>
const WS_SERVER = window.FuturXConfig?.wsServerUrl || 'http://localhost:3002';
let socket = null;
let currentPrice = 0; // (상단 티커용 - 기본 심볼)
let allPrices = {};   // 심볼별 최신가 (prices:all)
let positions = new Map();
let selectedPositionId = null;

// 바이낸스 연결 상태
let binanceStats = {
    connected: false,
    uptime: 0,
    latency: 0,
    messagesReceived: 0,
    activeStreams: 0,
    reconnectCount: 0
};

// 소켓 연결 상태
let socketConnected = false;

// 소켓 연결
function connectSocket() {
    socket = io(WS_SERVER, {
        transports: ['websocket', 'polling'],
        reconnection: true,
        reconnectionDelay: 1000,
        reconnectionAttempts: 10
    });

    // 연결 성공
    socket.on('connect', () => {
        console.log('[Socket] Connected to WebSocket server');
        socketConnected = true;

        // 초기 데이터 요청
        socket.emit('get:positions');
        socket.emit('get:users');
        socket.emit('get:binance_status');
    });

    // 연결 해제
    socket.on('disconnect', () => {
        console.log('[Socket] Disconnected');
        socketConnected = false;

        // 소켓 끊기면 바이낸스 상태도 끊김으로 표시
        binanceStats.connected = false;
        updateBinanceStatusDisplay();
    });

    // 재연결
    socket.on('reconnect', (attemptNumber) => {
        showToast('success', '재연결', `서버에 재연결되었습니다. (시도 ${attemptNumber}회)`);
    });

    // 연결 에러
    socket.on('connect_error', (error) => {
        console.error('[Socket] Connection error:', error);
        socketConnected = false;
    });

    // 바이낸스 연결 상태 수신
    socket.on('binance:status', (data) => {
        binanceStats.connected = data.connected;
        binanceStats.uptime = data.uptime || 0;
        binanceStats.latency = data.latency || 0;
        binanceStats.messagesReceived = data.messagesReceived || 0;
        binanceStats.activeStreams = data.activeStreams || 0;
        binanceStats.reconnectCount = data.reconnectCount || 0;
        updateBinanceStatusDisplay();
    });

    // 유저 수 업데이트
    socket.on('users:count', (count) => {
        document.getElementById('connOnline').textContent = count;
    });

    // 유저 목록 (접속자 수 파악용)
    socket.on('users:list', (list) => {
        document.getElementById('connOnline').textContent = list ? list.length : 0;
    });

    // 초기 데이터
    socket.on('init', (data) => {
        if (data.price) {
            updatePrice(data.price);
        }
        if (data.ticker) {
            updateTicker(data.ticker);
        }
    });

    // 가격 업데이트
    socket.on('price:update', (data) => {
        updatePrice(data.price, data.previousPrice);
        updatePositionsPnL();
    });

// 모든 심볼 가격 (포지션/관리자용)
socket.on('prices:all', (prices) => {
    if (prices && typeof prices === 'object') {
        allPrices = prices;
        // 심볼별 가격이 바뀌었으니 전체 업데이트
        updatePositionsPnL();
    }
});

    // 티커 업데이트
    socket.on('ticker:update', (data) => {
        updateTicker(data);
    });

    // 포지션 목록
    socket.on('positions:list', (list) => {
        positions.clear();
        (list || []).forEach(p => positions.set(p.id, p));
        renderPositions();
        updateStats();
    });

    // 포지션 오픈
    socket.on('position:opened', (data) => {
        console.log('[Socket] Position opened:', data);
        socket.emit('get:positions');
        showToast('success', '포지션 오픈', `${data.email || '회원'}님이 ${data.side.toUpperCase()} 포지션을 오픈했습니다.`);
    });

    // 포지션 종료
    socket.on('position:closed', (data) => {
        console.log('[Socket] Position closed:', data);
        socket.emit('get:positions');
        const pnlText = data.realized_pnl >= 0 ? `+$${data.realized_pnl}` : `-$${Math.abs(data.realized_pnl)}`;
        showToast(data.realized_pnl >= 0 ? 'success' : 'warning', '포지션 종료', `${data.email || '회원'}님 포지션 종료: ${pnlText}`);
    });

    // 강제 청산
    socket.on('position:liquidated', (data) => {
        console.log('[Socket] Position liquidated:', data);
        socket.emit('get:positions');
        showToast('error', '강제 청산', `${data.email || '회원'}님의 포지션이 청산되었습니다.`);
    });
}

// 바이낸스 연결 상태 표시 업데이트
function updateBinanceStatusDisplay() {
    const statusEl = document.getElementById('binanceStatus');

    if (binanceStats.connected) {
        statusEl.className = 'connection-panel__status connected';
        statusEl.querySelector('.connection-text').textContent = '바이낸스 실시간 연결됨';
    } else {
        statusEl.className = 'connection-panel__status disconnected';
        statusEl.querySelector('.connection-text').textContent = '바이낸스 연결 끊김';
    }

    // 연결 시간
    document.getElementById('binanceUptime').textContent = formatUptime(binanceStats.uptime);

    // Ping (레이턴시)
    const pingEl = document.getElementById('binancePing');
    const latency = binanceStats.latency;
    pingEl.textContent = latency + 'ms';
    pingEl.classList.remove('ping-good', 'ping-medium', 'ping-bad');
    if (latency < 50) {
        pingEl.classList.add('ping-good');
    } else if (latency < 150) {
        pingEl.classList.add('ping-medium');
    } else {
        pingEl.classList.add('ping-bad');
    }

    // 수신 메시지
    document.getElementById('binanceMsgCount').textContent = binanceStats.messagesReceived.toLocaleString();

    // 활성 스트림
    document.getElementById('binanceStreams').textContent = binanceStats.activeStreams;

    // 재연결 횟수
    document.getElementById('binanceReconnects').textContent = binanceStats.reconnectCount;
}

// 연결 시간 포맷
function formatUptime(ms) {
    const seconds = Math.floor(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days}d ${hours % 24}h`;
    if (hours > 0) return `${hours}h ${minutes % 60}m`;
    if (minutes > 0) return `${minutes}m ${seconds % 60}s`;
    return `${seconds}s`;
}

// 가격 업데이트
function updatePrice(price, prevPrice) {
    currentPrice = price;
    const priceEl = document.getElementById('current-price');
    // 상단 티커는 기본적으로 BTC 가격 표시 (정확 포맷은 DB decimals 기반)
    priceEl.textContent = '$' + (window.FuturX?.formatPrice ? window.FuturX.formatPrice(price, 'BTCUSDT') : price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

    if (prevPrice) {
        priceEl.className = 'price-ticker__price ' + (price >= prevPrice ? 'up' : 'down');
    }
}

// 티커 업데이트
function updateTicker(data) {
    const changeEl = document.getElementById('price-change');
    const pct = data.priceChangePercent || 0;
    changeEl.textContent = (pct >= 0 ? '+' : '') + pct.toFixed(2) + '%';
    changeEl.className = 'price-ticker__change ' + (pct >= 0 ? 'up' : 'down');
}

// 통계 업데이트
function updateStats() {
    let total = 0, longs = 0, shorts = 0, totalPnl = 0;

    positions.forEach(p => {
        total++;
        const side = (p.side || 'long').toLowerCase();
        if (side === 'long') longs++;
        else shorts++;

        const entryPrice = parseFloat(p.entry_price) || 0;
        const size = parseFloat(p.size) || 0;
        const symbol = p.symbol || 'BTCUSDT';
        const markPrice = (allPrices && allPrices[symbol] !== undefined) ? (parseFloat(allPrices[symbol]) || 0) : currentPrice;
        const pnl = side === 'long'
            ? (markPrice - entryPrice) * size
            : (entryPrice - markPrice) * size;
        totalPnl += pnl;
    });

    document.getElementById('stat-total-positions').textContent = total;
    document.getElementById('stat-long-positions').textContent = longs;
    document.getElementById('stat-short-positions').textContent = shorts;

    const pnlEl = document.getElementById('stat-total-pnl');
    pnlEl.textContent = (totalPnl >= 0 ? '+' : '') + '$' + totalPnl.toFixed(2);
    pnlEl.style.color = totalPnl >= 0 ? 'var(--color-success)' : 'var(--color-danger)';
}

// 포지션 PnL 실시간 업데이트
function updatePositionsPnL() {
    positions.forEach((p, id) => {
        const row = document.getElementById('position-' + id);
        if (!row) return;

        const entryPrice = parseFloat(p.entry_price) || 0;
        const size = parseFloat(p.size) || 0;
        const leverage = parseInt(p.leverage) || 1;
        const side = (p.side || 'long').toLowerCase();
        const symbol = p.symbol || 'BTCUSDT';
        const markPrice = (allPrices && allPrices[symbol] !== undefined) ? (parseFloat(allPrices[symbol]) || 0) : currentPrice;

        // PnL 계산
        const pnl = side === 'long'
            ? (markPrice - entryPrice) * size
            : (entryPrice - markPrice) * size;

        // ROE 계산 (PnL / 마진 * 100)
        const margin = (entryPrice * size) / leverage;
        const roe = margin > 0 ? (pnl / margin) * 100 : 0;

        // 현재가 업데이트
        const markPriceCell = row.querySelector('.mark-price');
        if (markPriceCell) {
            markPriceCell.textContent = '$' + (window.FuturX?.formatPrice ? window.FuturX.formatPrice(markPrice, symbol) : markPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        }

        // PnL 업데이트
        const pnlCell = row.querySelector('.pnl-value');
        if (pnlCell) {
            pnlCell.textContent = (pnl >= 0 ? '+' : '') + '$' + pnl.toFixed(2);
            pnlCell.className = 'pnl-value ' + (pnl >= 0 ? 'profit' : 'loss');
        }

        // ROE 업데이트
        const roeCell = row.querySelector('.roe-value');
        if (roeCell) {
            roeCell.textContent = (roe >= 0 ? '+' : '') + roe.toFixed(2) + '%';
            roeCell.className = 'roe-value ' + (roe >= 0 ? 'profit' : 'loss');
        }
    });

    updateStats();
}

// 포지션 목록 렌더링
function renderPositions() {
    const tbody = document.getElementById('positionsBody');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    if (positions.size === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>오픈된 포지션이 없습니다</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    positions.forEach((p, id) => {
        // 검색 필터
        const email = (p.email || '').toLowerCase();
        const nickname = (p.nickname || '').toLowerCase();
        if (searchTerm && !email.includes(searchTerm) && !nickname.includes(searchTerm)) {
            return;
        }

        const entryPrice = parseFloat(p.entry_price) || 0;
        const size = parseFloat(p.size) || 0;
        const liqPrice = parseFloat(p.liquidation_price) || 0;
        const leverage = parseInt(p.leverage) || 1;
        const side = (p.side || 'long').toLowerCase();
        const symbol = p.symbol || 'BTCUSDT';
        const markPrice = (allPrices && allPrices[symbol] !== undefined) ? (parseFloat(allPrices[symbol]) || 0) : currentPrice;
        const baseAsset = symbol.replace(/USDT$/i, '');

        // PnL 계산
        const pnl = side === 'long'
            ? (markPrice - entryPrice) * size
            : (entryPrice - markPrice) * size;

        // ROE 계산
        const margin = (entryPrice * size) / leverage;
        const roe = margin > 0 ? (pnl / margin) * 100 : 0;

        html += `
            <tr class="position-row" id="position-${id}">
                <td>
                    <div class="user-cell">
                        <span class="user-email">${p.email || p.nickname || 'Unknown'}</span>
                        <span class="user-id">#${p.user_id}</span>
                    </div>
                </td>
                <td><strong>${symbol}</strong></td>
                <td><span class="side-badge ${side}">${side.toUpperCase()}</span></td>
                <td class="num-mono">${window.FuturX?.formatQty ? window.FuturX.formatQty(size, symbol) : size.toFixed(4)}</td>
                <td class="num-mono">$${window.FuturX?.formatPrice ? window.FuturX.formatPrice(entryPrice, symbol) : entryPrice.toLocaleString()}</td>
                <td class="num-mono mark-price">$${window.FuturX?.formatPrice ? window.FuturX.formatPrice(markPrice, symbol) : markPrice.toLocaleString()}</td>
                <td><span class="leverage-badge">${leverage}x</span></td>
                <td class="liq-price">$${window.FuturX?.formatPrice ? window.FuturX.formatPrice(liqPrice, symbol) : liqPrice.toLocaleString()}</td>
                <td class="pnl-value ${pnl >= 0 ? 'profit' : 'loss'}">${pnl >= 0 ? '+' : ''}$${pnl.toFixed(2)}</td>
                <td class="roe-value ${roe >= 0 ? 'profit' : 'loss'}">${roe >= 0 ? '+' : ''}${roe.toFixed(2)}%</td>
                <td>
                    <button class="btn btn--sm btn--outline" onclick="viewPosition(${id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html || `
        <tr>
            <td colspan="11" class="empty-state">
                <i class="fas fa-search"></i>
                <p>검색 결과가 없습니다</p>
            </td>
        </tr>
    `;
}

// 포지션 상세 보기
function viewPosition(id) {
    const p = positions.get(id);
    if (!p) return;

    selectedPositionId = id;

    const entryPrice = parseFloat(p.entry_price) || 0;
    const size = parseFloat(p.size) || 0;
    const liqPrice = parseFloat(p.liquidation_price) || 0;
    const leverage = parseInt(p.leverage) || 1;
    const side = (p.side || 'long').toLowerCase();
    const symbol = p.symbol || 'BTCUSDT';
    const markPrice = (allPrices && allPrices[symbol] !== undefined) ? (parseFloat(allPrices[symbol]) || 0) : currentPrice;
    const baseAsset = symbol.replace(/USDT$/i, '');

    const pnl = side === 'long'
        ? (markPrice - entryPrice) * size
        : (entryPrice - markPrice) * size;

    const margin = (entryPrice * size) / leverage;
    const roe = margin > 0 ? (pnl / margin) * 100 : 0;

    const body = document.getElementById('positionModalBody');
    body.innerHTML = `
        <div class="position-detail">
            <div class="position-detail__item">
                <div class="position-detail__label">회원</div>
                <div class="position-detail__value">${p.email || 'Unknown'}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">회원 ID</div>
                <div class="position-detail__value">#${p.user_id}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">심볼</div>
                <div class="position-detail__value">${symbol}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">방향</div>
                <div class="position-detail__value">
                    <span class="side-badge ${side}">${side.toUpperCase()}</span>
                </div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">수량</div>
                <div class="position-detail__value">${window.FuturX?.formatQty ? window.FuturX.formatQty(size, symbol) : size.toFixed(4)} ${baseAsset}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">레버리지</div>
                <div class="position-detail__value">${leverage}x</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">진입가</div>
                <div class="position-detail__value">$${window.FuturX?.formatPrice ? window.FuturX.formatPrice(entryPrice, symbol) : entryPrice.toLocaleString()}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">현재가</div>
                <div class="position-detail__value">$${window.FuturX?.formatPrice ? window.FuturX.formatPrice(markPrice, symbol) : markPrice.toLocaleString()}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">청산가</div>
                <div class="position-detail__value" style="color: var(--color-danger);">$${window.FuturX?.formatPrice ? window.FuturX.formatPrice(liqPrice, symbol) : liqPrice.toLocaleString()}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">마진</div>
                <div class="position-detail__value">$${margin.toFixed(2)}</div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">미실현 PnL</div>
                <div class="position-detail__value" style="color: ${pnl >= 0 ? 'var(--color-success)' : 'var(--color-danger)'};">
                    ${pnl >= 0 ? '+' : ''}$${pnl.toFixed(2)}
                </div>
            </div>
            <div class="position-detail__item">
                <div class="position-detail__label">ROE</div>
                <div class="position-detail__value" style="color: ${roe >= 0 ? 'var(--color-success)' : 'var(--color-danger)'};">
                    ${roe >= 0 ? '+' : ''}${roe.toFixed(2)}%
                </div>
            </div>
            <div class="position-detail__item full-width">
                <div class="position-detail__label">오픈 시간</div>
                <div class="position-detail__value">${p.created_at ? new Date(p.created_at).toLocaleString() : '-'}</div>
            </div>
        </div>
    `;

    // 모달 열기
    const modal = document.getElementById('positionModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// 모달 닫기
function closePositionModal() {
    const modal = document.getElementById('positionModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    selectedPositionId = null;
}

// 강제 청산
async function forceLiquidate() {
    if (!selectedPositionId) return;

    const p = positions.get(selectedPositionId);
    if (!p) return;

    const confirmMsg = `정말 이 포지션을 강제 청산하시겠습니까?\n\n` +
        `회원: ${p.email || 'Unknown'}\n` +
        `방향: ${p.side?.toUpperCase()}\n` +
        `수량: ${p.size} ${String(p.symbol || 'BTCUSDT').replace(/USDT$/i, '')}`;

    if (!confirm(confirmMsg)) return;

    // 버튼 비활성화
    const btn = document.getElementById('forceLiquidateBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 처리중...';

    try {
        const response = await fetch('api/liquidate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                position_id: selectedPositionId,
                reason: 'admin_manual_liquidation'
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast('success', '강제 청산 완료',
                `${result.data.user_email}님의 포지션이 청산되었습니다.\n` +
                `PnL: ${result.data.pnl >= 0 ? '+' : ''}$${result.data.pnl}`);

            // 포지션 목록 새로고침
            socket.emit('get:positions');
            closePositionModal();
        } else {
            showToast('error', '청산 실패', result.error || '알 수 없는 오류가 발생했습니다.');
        }
    } catch (error) {
        console.error('Liquidation error:', error);
        showToast('error', '청산 실패', '서버와 통신 중 오류가 발생했습니다.');
    } finally {
        // 버튼 복원
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// 새로고침
function refreshPositions() {
    if (socket && socket.connected) {
        socket.emit('get:positions');
        showToast('success', '새로고침', '포지션 데이터를 새로고침했습니다.');
    } else {
        showToast('error', '오류', '서버에 연결되지 않았습니다.');
    }
}

// 토스트 메시지
function showToast(type, title, message) {
    const container = document.getElementById('toastContainer');
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
        <div class="toast__icon">
            <i class="fas ${icons[type] || icons.success}"></i>
        </div>
        <div class="toast__content">
            <div class="toast__title">${title}</div>
            <div class="toast__message">${message}</div>
        </div>
        <button class="toast__close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => toast.remove(), 5000);
}

// 검색 이벤트
document.getElementById('searchInput').addEventListener('input', () => {
    renderPositions();
});

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    connectSocket();

    // 1초마다 PnL 업데이트
    setInterval(() => {
        updatePositionsPnL();
    }, 1000);

    // 5초마다 유저 수 업데이트
    setInterval(() => {
        if (socket && socket.connected) {
            socket.emit('get:users');
        }
    }, 5000);

    // 모달 닫기 버튼 이벤트
    document.getElementById('modalCloseBtn').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closePositionModal();
    });

    document.getElementById('modalCloseBtnFooter').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closePositionModal();
    });

    // 강제청산 버튼 이벤트
    document.getElementById('forceLiquidateBtn').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        forceLiquidate();
    });

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePositionModal();
        }
    });
});

// 페이지 종료 시 소켓 정리
window.addEventListener('beforeunload', () => {
    if (socket) {
        socket.disconnect();
    }
});
</script>

<?php renderFooter(); ?>
