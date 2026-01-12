<?php
/**
 * FuturX Admin - 실시간 접속자
 * Socket.IO를 통한 실시간 접속자 모니터링
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'layout.php';

renderHeader('실시간 접속자');
renderPageHeader('실시간 접속자', '현재 접속 중인 사용자를 실시간으로 모니터링합니다');
?>

<!-- 연결 상태 패널 -->
<div class="connection-panel" id="connectionPanel">
    <div class="connection-panel__header">
        <div class="connection-panel__status" id="serverStatus">
            <span class="connection-dot"></span>
            <span class="connection-text">서버 연결 중...</span>
        </div>
        <div class="connection-panel__server">
            <i class="fas fa-server"></i>
            <span>WebSocket Server (localhost:3002)</span>
        </div>
    </div>
</div>

<!-- 통계 카드 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-success-bg); color: var(--color-success);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">총 접속자</span>
            <span class="stat-card__value" id="stat-total">0</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-primary-bg); color: var(--color-primary);">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">로그인 사용자</span>
            <span class="stat-card__value" id="stat-authenticated">0</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
            <i class="fas fa-user-clock"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">게스트</span>
            <span class="stat-card__value" id="stat-guests">0</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-info-bg); color: var(--color-info);">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">활성 거래자</span>
            <span class="stat-card__value" id="stat-traders">0</span>
        </div>
    </div>
</div>

<!-- 접속자 테이블 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-signal"></i>
            실시간 접속자 목록
            <span class="badge badge--success" id="liveIndicator">LIVE</span>
        </h3>
        <div class="card__tools">
            <input type="text" class="form-control" placeholder="검색..." id="searchInput" style="width: 200px;">
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table" id="onlineTable">
            <thead>
                <tr>
                    <th>상태</th>
                    <th>사용자</th>
                    <th>IP 주소</th>
                    <th>브라우저</th>
                    <th>현재 페이지</th>
                    <th>접속 시간</th>
                    <th>마지막 활동</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody id="onlineTableBody">
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> 연결 중...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 사용자 상세보기 모달 -->
<div class="user-detail-modal" id="userDetailModal">
    <div class="user-detail-modal__backdrop" onclick="closeUserModal()"></div>
    <div class="user-detail-modal__box">
        <div class="user-detail-modal__header">
            <h3 class="user-detail-modal__title"><i class="fas fa-user-circle"></i> 사용자 상세 정보</h3>
            <button class="user-detail-modal__close" onclick="closeUserModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="user-detail-modal__body" id="userDetailContent">
            <!-- 동적으로 채워짐 -->
        </div>
    </div>
</div>

<!-- 최근 접속/이탈 로그 -->
<div class="grid grid--2">
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-sign-in-alt text-success"></i>
                최근 접속
            </h3>
        </div>
        <div class="card__body" id="recentConnections" style="max-height: 300px; overflow-y: auto;">
            <div class="text-center text-muted py-4">로딩 중...</div>
        </div>
    </div>
    <div class="card">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-sign-out-alt text-danger"></i>
                최근 이탈
            </h3>
        </div>
        <div class="card__body" id="recentDisconnections" style="max-height: 300px; overflow-y: auto;">
            <div class="text-center text-muted py-4">로딩 중...</div>
        </div>
    </div>
</div>

<style>
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
}

.connection-panel__status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.connection-panel__status .connection-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--color-warning);
    animation: pulse 2s infinite;
}

.connection-panel__status.connected .connection-dot {
    background: var(--color-success);
}

.connection-panel__status.disconnected .connection-dot {
    background: var(--color-danger);
    animation: none;
}

.connection-panel__server {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    font-size: 13px;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
}

.grid--2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 20px;
}

@media (max-width: 992px) {
    .grid--2 { grid-template-columns: 1fr; }
}

#liveIndicator {
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.user-status {
    display: flex;
    align-items: center;
    gap: 6px;
}

.user-status__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.user-status__dot.online { background: var(--color-success); }
.user-status__dot.idle { background: var(--color-warning); }
.user-status__dot.away { background: var(--color-danger); }

.user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bg-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: var(--text-secondary);
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
    font-size: 13px;
}

.user-email {
    font-size: 11px;
    color: var(--text-secondary);
}

.page-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: var(--bg-tertiary);
    border-radius: 4px;
    font-size: 12px;
}

.log-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.log-item:last-child {
    border-bottom: none;
}

.log-item__icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.log-item__icon.connect {
    background: var(--color-success-bg);
    color: var(--color-success);
}

.log-item__icon.disconnect {
    background: var(--color-danger-bg);
    color: var(--color-danger);
}

.log-item__content {
    flex: 1;
}

.log-item__user {
    font-weight: 500;
    font-size: 13px;
}

.log-item__time {
    font-size: 11px;
    color: var(--text-secondary);
}

.log-item__meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.log-item__ip {
    font-size: 11px;
    color: var(--text-tertiary);
    font-family: var(--font-mono);
}

.log-item__duration {
    font-size: 10px;
    color: var(--text-tertiary);
}

.log-item__userid {
    font-size: 11px;
    color: var(--color-primary);
    margin-left: 4px;
}

.log-item__sid {
    font-size: 10px;
    color: var(--text-tertiary);
    font-family: var(--font-mono);
    background: var(--bg-tertiary);
    padding: 2px 6px;
    border-radius: 4px;
}

.log-item__sid i {
    margin-right: 3px;
    font-size: 9px;
}

.log-item__device {
    font-size: 11px;
    color: var(--text-secondary);
}

.log-item__page {
    font-size: 10px;
    color: var(--color-primary);
    background: rgba(99, 102, 241, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
}

.log-item__page i {
    margin-right: 3px;
    font-size: 9px;
}

.table-wrapper {
    overflow-x: auto;
}

#onlineTable {
    min-width: 900px;
}

.browser-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.browser-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    background: var(--bg-tertiary);
}

.browser-badge.desktop {
    color: var(--color-primary);
}

.browser-badge.mobile {
    color: var(--color-success);
}

.browser-badge.tablet {
    color: var(--color-warning);
}

.os-info {
    font-size: 10px;
    color: var(--text-tertiary);
}

/* 사용자 상세보기 모달 - 고유 스타일 */
.user-detail-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 999999;
}

.user-detail-modal.active {
    display: block !important;
}

.user-detail-modal__backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 999999;
}

.user-detail-modal__box {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #1a1a2e;
    border: 1px solid #333;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
    z-index: 1000000;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.user-detail-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #333;
    background: #151525;
}

.user-detail-modal__title {
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    margin: 0;
}

.user-detail-modal__close {
    background: none;
    border: none;
    color: #888;
    cursor: pointer;
    padding: 4px 8px;
    font-size: 16px;
    transition: color 0.2s;
}

.user-detail-modal__close:hover {
    color: #fff;
}

.user-detail-modal__body {
    padding: 16px;
}

/* 모달 상세 정보 - 고유 클래스 */
.user-detail-grid {
    display: grid;
    gap: 10px;
}

.user-detail-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-detail-item__label {
    font-size: 10px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-detail-item__value {
    font-size: 13px;
    color: #fff;
    font-weight: 500;
}

.user-detail-item__value code {
    background: #252540;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 11px;
}

.user-detail-section {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #333;
}

.user-detail-section__title {
    font-size: 11px;
    font-weight: 600;
    color: #aaa;
    margin-bottom: 8px;
}

.user-detail-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.user-detail-row + .user-detail-row {
    margin-top: 8px;
}
</style>

<script src="<?= WS_SERVER_URL ?>/socket.io/socket.io.js"></script>
<script>
const WS_SERVER = window.FuturXConfig?.wsServerUrl || 'http://localhost:3002';
let socket = null;
let onlineUsers = new Map();
let recentConnections = [];
let recentDisconnections = [];

// 소켓 연결
function connectSocket() {
    socket = io(WS_SERVER, {
        transports: ['websocket', 'polling'],
        reconnection: true,
        reconnectionDelay: 1000,
        reconnectionAttempts: 10
    });

    socket.on('connect', () => {
        console.log('[Socket] Connected');
        updateServerStatus(true);

        // 관리자 식별
        socket.emit('admin:identify', { role: 'admin' });

        // 초기 데이터 요청
        socket.emit('get:users');
        socket.emit('get:connection_logs');
    });

    socket.on('disconnect', () => {
        console.log('[Socket] Disconnected');
        updateServerStatus(false);
    });

    socket.on('connect_error', (error) => {
        console.error('[Socket] Connection error:', error);
        updateServerStatus(false);
    });

    // 사용자 목록 수신
    socket.on('users:list', (users) => {
        onlineUsers.clear();
        (users || []).forEach(u => onlineUsers.set(u.socketId, u));
        renderOnlineUsers();
        updateStats();
    });

    // 사용자 연결
    socket.on('user:connected', (user) => {
        onlineUsers.set(user.socketId, user);
        addConnectionLog(user, 'connect');
        renderOnlineUsers();
        updateStats();
    });

    // 사용자 연결 해제
    socket.on('user:disconnected', (data) => {
        const user = onlineUsers.get(data.socketId);
        if (user) {
            addConnectionLog(user, 'disconnect');
            onlineUsers.delete(data.socketId);
            renderOnlineUsers();
            updateStats();
        }
    });

    // 사용자 업데이트
    socket.on('user:updated', (user) => {
        onlineUsers.set(user.socketId, user);
        renderOnlineUsers();
        updateStats();
    });

    // 유저 수 업데이트
    socket.on('users:count', (count) => {
        document.getElementById('stat-total').textContent = count;
    });

    // 접속/이탈 로그 수신
    socket.on('connection_logs', (data) => {
        recentConnections = data.connections || [];
        recentDisconnections = data.disconnections || [];
        renderConnectionLogs();
        renderDisconnectionLogs();
    });
}

// 서버 상태 업데이트
function updateServerStatus(connected) {
    const statusEl = document.getElementById('serverStatus');
    if (connected) {
        statusEl.className = 'connection-panel__status connected';
        statusEl.querySelector('.connection-text').textContent = '서버 연결됨';
    } else {
        statusEl.className = 'connection-panel__status disconnected';
        statusEl.querySelector('.connection-text').textContent = '서버 연결 끊김';
    }
}

// 통계 업데이트
function updateStats() {
    const total = onlineUsers.size;
    let authenticated = 0;
    let traders = 0;

    onlineUsers.forEach(user => {
        if (user.userId) authenticated++;
        if (user.hasActivePosition) traders++;
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-authenticated').textContent = authenticated;
    document.getElementById('stat-guests').textContent = total - authenticated;
    document.getElementById('stat-traders').textContent = traders;
}

// 접속자 테이블 렌더링
function renderOnlineUsers() {
    const tbody = document.getElementById('onlineTableBody');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    const users = Array.from(onlineUsers.values())
        .filter(u => {
            if (!searchTerm) return true;
            const name = (u.nickname || u.email || 'Guest').toLowerCase();
            const ip = (u.ip || '').toLowerCase();
            return name.includes(searchTerm) || ip.includes(searchTerm);
        })
        .sort((a, b) => (b.connectedAt || 0) - (a.connectedAt || 0));

    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-users-slash"></i>
                    접속 중인 사용자가 없습니다
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = users.map(user => {
        const status = getActivityStatus(user.lastActivity);
        const connectedAt = user.connectedAt ? new Date(user.connectedAt).toLocaleString() : '-';
        const lastActivity = user.lastActivity ? formatTimeAgo(user.lastActivity) : '-';
        const pageName = getPageName(user.currentPage);

        return `
            <tr data-socket-id="${user.socketId}">
                <td>
                    <div class="user-status">
                        <span class="user-status__dot ${status.class}"></span>
                        <span>${status.label}</span>
                    </div>
                </td>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar">
                            ${user.avatar ? `<img src="${user.avatar}" alt="">` : '<i class="fas fa-user"></i>'}
                        </div>
                        <div class="user-info">
                            <span class="user-name">${user.nickname || 'Guest'}</span>
                            <span class="user-email">${user.email || user.socketId.substring(0, 8)}</span>
                        </div>
                    </div>
                </td>
                <td><code>${user.ip || '-'}</code></td>
                <td>
                    <div class="browser-info">
                        <span class="browser-badge ${(user.device || 'desktop').toLowerCase()}">
                            <i class="fas ${getDeviceIcon(user.device)}"></i>
                            ${user.browser || 'Unknown'}
                        </span>
                        <span class="os-info">${user.os || ''}</span>
                    </div>
                </td>
                <td>
                    <span class="page-badge">
                        <i class="fas ${pageName.icon}"></i>
                        ${pageName.label}
                    </span>
                </td>
                <td>${connectedAt}</td>
                <td>${lastActivity}</td>
                <td>
                    <button class="btn btn--sm btn--outline" onclick="viewUserDetail('${user.socketId}')" title="상세보기">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${user.userId ? `
                    <button class="btn btn--sm btn--danger-outline" onclick="kickUser('${user.socketId}')" title="강제퇴장">
                        <i class="fas fa-ban"></i>
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

// 활동 상태 판단
function getActivityStatus(lastActivity) {
    if (!lastActivity) return { class: 'online', label: '활성' };

    const diff = Date.now() - lastActivity;
    if (diff < 60000) return { class: 'online', label: '활성' };
    if (diff < 300000) return { class: 'idle', label: '대기' };
    return { class: 'away', label: '자리비움' };
}

// 페이지 이름 변환
function getPageName(page) {
    const pages = {
        'trade': { icon: 'fa-chart-line', label: '거래소' },
        'dashboard': { icon: 'fa-chart-pie', label: '대시보드' },
        'wallet': { icon: 'fa-wallet', label: '지갑' },
        'settings': { icon: 'fa-cog', label: '설정' },
        'competition': { icon: 'fa-trophy', label: '대회' },
        'admin': { icon: 'fa-shield-alt', label: '관리자' }
    };

    return pages[page] || { icon: 'fa-globe', label: page || '알 수 없음' };
}

// 디바이스 아이콘
function getDeviceIcon(device) {
    const icons = {
        'Desktop': 'fa-desktop',
        'Mobile': 'fa-mobile-alt',
        'Tablet': 'fa-tablet-alt'
    };
    return icons[device] || 'fa-desktop';
}

// 시간 포맷
function formatTimeAgo(timestamp) {
    const diff = Date.now() - timestamp;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    if (seconds < 60) return '방금 전';
    if (minutes < 60) return `${minutes}분 전`;
    if (hours < 24) return `${hours}시간 전`;
    return new Date(timestamp).toLocaleDateString();
}

// 연결 로그 추가
function addConnectionLog(user, type) {
    const log = {
        user: user,
        time: Date.now(),
        type: type
    };

    if (type === 'connect') {
        recentConnections.unshift(log);
        if (recentConnections.length > 20) recentConnections.pop();
        renderConnectionLogs();
    } else {
        recentDisconnections.unshift(log);
        if (recentDisconnections.length > 20) recentDisconnections.pop();
        renderDisconnectionLogs();
    }
}

// 접속 로그 렌더링
function renderConnectionLogs() {
    const container = document.getElementById('recentConnections');

    if (recentConnections.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">최근 접속 기록이 없습니다</div>';
        return;
    }

    container.innerHTML = recentConnections.map(log => {
        const user = log.user || log;
        const displayName = user.nickname || user.email || 'Guest';
        const isLoggedIn = user.userId && user.userId !== 'guest';
        const socketShort = (user.socketId || '').substring(0, 8);
        const deviceIcon = getDeviceIcon(user.device);
        const pageName = getPageName(user.currentPage);

        return `
            <div class="log-item">
                <div class="log-item__icon connect">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="log-item__content">
                    <div class="log-item__user">
                        ${isLoggedIn ? `<i class="fas fa-user-check" style="color:#4ade80;margin-right:4px;" title="로그인 유저"></i>` : `<i class="fas fa-user" style="color:#888;margin-right:4px;" title="Guest"></i>`}
                        ${displayName}
                        ${isLoggedIn ? `<span class="log-item__userid">(${user.userId})</span>` : ''}
                    </div>
                    <div class="log-item__meta">
                        <span class="log-item__sid" title="Socket ID"><i class="fas fa-fingerprint"></i> ${socketShort}</span>
                        <span class="log-item__device"><i class="fas ${deviceIcon}"></i></span>
                        <span class="log-item__page"><i class="fas ${pageName.icon}"></i> ${pageName.label}</span>
                        <span class="log-item__time">${formatTimeAgo(log.time)}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// 이탈 로그 렌더링
function renderDisconnectionLogs() {
    const container = document.getElementById('recentDisconnections');

    if (recentDisconnections.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">최근 이탈 기록이 없습니다</div>';
        return;
    }

    container.innerHTML = recentDisconnections.map(log => {
        const user = log.user || log;
        const displayName = user.nickname || user.email || 'Guest';
        const isLoggedIn = user.userId && user.userId !== 'guest';
        const socketShort = (user.socketId || '').substring(0, 8);
        const deviceIcon = getDeviceIcon(user.device);
        const pageName = getPageName(user.currentPage);

        return `
            <div class="log-item">
                <div class="log-item__icon disconnect">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="log-item__content">
                    <div class="log-item__user">
                        ${isLoggedIn ? `<i class="fas fa-user-check" style="color:#f87171;margin-right:4px;" title="로그인 유저"></i>` : `<i class="fas fa-user" style="color:#888;margin-right:4px;" title="Guest"></i>`}
                        ${displayName}
                        ${isLoggedIn ? `<span class="log-item__userid">(${user.userId})</span>` : ''}
                    </div>
                    <div class="log-item__meta">
                        <span class="log-item__sid" title="Socket ID"><i class="fas fa-fingerprint"></i> ${socketShort}</span>
                        <span class="log-item__device"><i class="fas ${deviceIcon}"></i></span>
                        <span class="log-item__page"><i class="fas ${pageName.icon}"></i> ${pageName.label}</span>
                        <span class="log-item__time">${formatTimeAgo(log.time)}</span>
                        ${log.duration ? `<span class="log-item__duration">(${formatDuration(log.duration)})</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// 접속 시간 포맷
function formatDuration(ms) {
    const seconds = Math.floor(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    if (hours > 0) return `${hours}h ${minutes % 60}m`;
    if (minutes > 0) return `${minutes}m ${seconds % 60}s`;
    return `${seconds}s`;
}

// 사용자 상세보기 모달
function viewUserDetail(socketId) {
    const user = onlineUsers.get(socketId);
    if (!user) return;

    const sessionDuration = user.connectedAt ? formatDuration(Date.now() - user.connectedAt) : '-';
    const status = getActivityStatus(user.lastActivity);
    const statusColor = status.class === 'online' ? '#4ade80' : (status.class === 'idle' ? '#fbbf24' : '#f87171');

    const content = `
        <div class="user-detail-grid">
            <div class="user-detail-row">
                <div class="user-detail-item">
                    <span class="user-detail-item__label">사용자 ID</span>
                    <span class="user-detail-item__value">${user.userId || '-'}</span>
                </div>
                <div class="user-detail-item">
                    <span class="user-detail-item__label">상태</span>
                    <span class="user-detail-item__value">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;background:${statusColor};"></span>
                        ${status.label}
                    </span>
                </div>
            </div>
            <div class="user-detail-row">
                <div class="user-detail-item">
                    <span class="user-detail-item__label">닉네임</span>
                    <span class="user-detail-item__value">${user.nickname || 'Guest'}</span>
                </div>
                <div class="user-detail-item">
                    <span class="user-detail-item__label">이메일</span>
                    <span class="user-detail-item__value">${user.email || '-'}</span>
                </div>
            </div>

            <div class="user-detail-section">
                <div class="user-detail-section__title">연결 정보</div>
                <div class="user-detail-row">
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">IP 주소</span>
                        <span class="user-detail-item__value"><code>${user.ip || '-'}</code></span>
                    </div>
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">Socket ID</span>
                        <span class="user-detail-item__value"><code>${user.socketId?.substring(0, 10) || '-'}..</code></span>
                    </div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">접속 시간</span>
                        <span class="user-detail-item__value">${user.connectedAt ? new Date(user.connectedAt).toLocaleString() : '-'}</span>
                    </div>
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">세션 / 활동</span>
                        <span class="user-detail-item__value">${sessionDuration} / ${user.lastActivity ? formatTimeAgo(user.lastActivity) : '-'}</span>
                    </div>
                </div>
            </div>

            <div class="user-detail-section">
                <div class="user-detail-section__title">환경 정보</div>
                <div class="user-detail-row">
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">브라우저</span>
                        <span class="user-detail-item__value">${user.browser || 'Unknown'}</span>
                    </div>
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">운영체제</span>
                        <span class="user-detail-item__value">${user.os || 'Unknown'}</span>
                    </div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">디바이스</span>
                        <span class="user-detail-item__value">${user.device || 'Unknown'}</span>
                    </div>
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">해상도</span>
                        <span class="user-detail-item__value">${user.screenSize || 'Unknown'}</span>
                    </div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">언어</span>
                        <span class="user-detail-item__value">${user.language || 'Unknown'}</span>
                    </div>
                    <div class="user-detail-item">
                        <span class="user-detail-item__label">현재 페이지</span>
                        <span class="user-detail-item__value">${getPageName(user.currentPage).label}</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('userDetailContent').innerHTML = content;
    document.getElementById('userDetailModal').classList.add('active');
}

// 모달 닫기
function closeUserModal() {
    document.getElementById('userDetailModal').classList.remove('active');
}

// 사용자 강제퇴장
function kickUser(socketId) {
    const user = onlineUsers.get(socketId);
    if (!user) return;

    if (!confirm(`${user.nickname || user.email || 'Guest'}님을 강제 퇴장시키겠습니까?`)) return;

    socket.emit('admin:kick_user', { socketId: socketId });
    showToast('info', '강제퇴장', '사용자를 강제퇴장 처리했습니다.');
}

// 검색 이벤트
document.getElementById('searchInput').addEventListener('input', () => {
    renderOnlineUsers();
});

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    // 모달을 body로 이동 (사이드바 영향 제거)
    const modal = document.getElementById('userDetailModal');
    if (modal) {
        document.body.appendChild(modal);
    }
    connectSocket();
    renderConnectionLogs();
    renderDisconnectionLogs();

    // 5초마다 유저 목록 새로고침
    setInterval(() => {
        if (socket && socket.connected) {
            socket.emit('get:users');
        }
    }, 5000);
});

// 페이지 종료 시 소켓 정리
window.addEventListener('beforeunload', () => {
    if (socket) {
        socket.disconnect();
    }
});
</script>

<?php renderFooter(); ?>
