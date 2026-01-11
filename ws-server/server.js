/**
 * Futures Trading WebSocket Server
 * 실시간 주문/포지션/청산 브로드캐스트
 *
 * 기능:
 * 1. Binance 실시간 가격 스트리밍
 * 2. 사용자 주문 체결 브로드캐스트
 * 3. 포지션 오픈/청산 알림
 * 4. 실시간 오더북 업데이트
 * 5. 거래 피드 (최근 체결 내역)
 * 6. 모든 데이터 DB 기반 (서버 재시작해도 유지)
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const { Server } = require('socket.io');
const WebSocket = require('ws');
const mysql = require('mysql2/promise');

// ========================================
// 설정
// ========================================
const PORT = 3002;
const BINANCE_WS_URL = 'wss://fstream.binance.com/ws';

// DB 설정
const DB_CONFIG = {
    host: 'localhost',
    user: 'root',
    password: '123123',
    database: 'trade',
    waitForConnections: true,
    connectionLimit: 10
};

// ========================================
// 글로벌 상태
// ========================================
let currentPrice = 0;
let previousPrice = 0;
let ticker24h = {};
let recentTrades = [];
let orderbook = { bids: [], asks: [] };

// 연결된 클라이언트
const connectedUsers = new Map(); // socketId -> { userId, nickname, ip, connectedAt, lastActivity, currentPage }

// 최근 접속/이탈 로그
const recentConnectionLogs = [];
const recentDisconnectionLogs = [];
const MAX_LOG_SIZE = 50;

// DB 연결 풀
let dbPool = null;

// 바이낸스 연결 상태
const binanceStatus = {
    connected: false,
    connectedAt: null,
    reconnectCount: 0,
    lastPing: null,
    lastPong: null,
    latency: 0,
    messagesReceived: 0,
    lastMessageAt: null
};

// ========================================
// HTTP 서버 + Socket.IO
// ========================================
const server = http.createServer((req, res) => {
    // 대시보드 HTML (외부 파일에서 읽기)
    if (req.url === '/' || req.url === '/dashboard') {
        const htmlPath = path.join(__dirname, 'public', 'dashboard.html');
        fs.readFile(htmlPath, 'utf8', (err, html) => {
            if (err) {
                res.writeHead(500, { 'Content-Type': 'text/plain' });
                res.end('Error loading dashboard: ' + err.message);
                return;
            }
            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
            res.end(html);
        });
        return;
    }

    // API 상태
    if (req.url === '/api/status') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'running',
            connectedClients: io.engine.clientsCount,
            currentPrice,
            ticker24h,
            uptime: process.uptime()
        }));
        return;
    }

    // 브로드캐스트 API (PHP에서 호출)
    if (req.url === '/api/broadcast' && req.method === 'POST') {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', () => {
            try {
                const data = JSON.parse(body);
                handleBroadcast(data);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true }));
            } catch (e) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ error: e.message }));
            }
        });
        return;
    }

    res.writeHead(404);
    res.end('Not Found');
});

const io = new Server(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    },
    pingInterval: 10000,
    pingTimeout: 5000
});

// ========================================
// Binance WebSocket Manager
// ========================================
class BinanceWSManager {
    constructor() {
        this.connections = new Map();
        this.subscribers = new Map();
        this.pingInterval = null;
    }

    subscribe(stream, callback) {
        if (!this.subscribers.has(stream)) {
            this.subscribers.set(stream, new Set());
        }
        this.subscribers.get(stream).add(callback);

        if (!this.connections.has(stream)) {
            this.connect(stream);
        }
    }

    unsubscribe(stream, callback) {
        const subs = this.subscribers.get(stream);
        if (subs) {
            subs.delete(callback);
            if (subs.size === 0) {
                this.disconnect(stream);
            }
        }
    }

    connect(stream) {
        const url = `${BINANCE_WS_URL}/${stream}`;
        console.log(`[Binance] Connecting: ${stream}`);

        const ws = new WebSocket(url);

        ws.on('open', () => {
            console.log(`[Binance] Connected: ${stream}`);

            // 첫 번째 스트림 연결 시 바이낸스 상태 업데이트
            if (!binanceStatus.connected) {
                binanceStatus.connected = true;
                binanceStatus.connectedAt = Date.now();
                this.startPingInterval();
                // 연결 상태 브로드캐스트
                io.emit('binance:status', this.getStatus());
            }
        });

        ws.on('message', (data) => {
            try {
                const parsed = JSON.parse(data);

                // 메시지 카운트 업데이트
                binanceStatus.messagesReceived++;
                binanceStatus.lastMessageAt = Date.now();

                const callbacks = this.subscribers.get(stream);
                if (callbacks) {
                    callbacks.forEach(cb => cb(parsed));
                }
            } catch (e) {
                console.error(`[Binance] Parse error:`, e);
            }
        });

        ws.on('close', () => {
            console.log(`[Binance] Disconnected: ${stream}`);
            this.connections.delete(stream);

            // 모든 연결이 끊어지면 상태 업데이트
            if (this.connections.size === 0) {
                binanceStatus.connected = false;
                binanceStatus.reconnectCount++;
                this.stopPingInterval();
                io.emit('binance:status', this.getStatus());
            }

            // 자동 재연결
            if (this.subscribers.has(stream) && this.subscribers.get(stream).size > 0) {
                setTimeout(() => this.connect(stream), 3000);
            }
        });

        ws.on('error', (err) => {
            console.error(`[Binance] Error on ${stream}:`, err.message);
        });

        // WebSocket pong 응답 처리
        ws.on('pong', () => {
            binanceStatus.lastPong = Date.now();
            if (binanceStatus.lastPing) {
                binanceStatus.latency = binanceStatus.lastPong - binanceStatus.lastPing;
            }
        });

        this.connections.set(stream, ws);
    }

    disconnect(stream) {
        const ws = this.connections.get(stream);
        if (ws) {
            ws.close();
            this.connections.delete(stream);
            this.subscribers.delete(stream);
        }
    }

    // 바이낸스 WebSocket에 ping 전송
    startPingInterval() {
        this.stopPingInterval();
        this.pingInterval = setInterval(() => {
            binanceStatus.lastPing = Date.now();
            for (const [stream, ws] of this.connections) {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.ping();
                }
            }
            // 상태 브로드캐스트 (1초마다)
            io.emit('binance:status', this.getStatus());
        }, 1000);
    }

    stopPingInterval() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
    }

    // 현재 바이낸스 연결 상태 반환
    getStatus() {
        return {
            connected: binanceStatus.connected,
            connectedAt: binanceStatus.connectedAt,
            uptime: binanceStatus.connectedAt ? Date.now() - binanceStatus.connectedAt : 0,
            reconnectCount: binanceStatus.reconnectCount,
            latency: binanceStatus.latency,
            messagesReceived: binanceStatus.messagesReceived,
            lastMessageAt: binanceStatus.lastMessageAt,
            activeStreams: this.connections.size,
            streams: Array.from(this.connections.keys())
        };
    }
}

const binanceWS = new BinanceWSManager();

// ========================================
// 가격 스트림 핸들러
// ========================================
function handleAggTrade(data) {
    previousPrice = currentPrice;
    currentPrice = parseFloat(data.p);

    // 최근 체결 추가
    const trade = {
        price: currentPrice,
        quantity: parseFloat(data.q),
        time: data.T,
        isBuyerMaker: data.m
    };

    recentTrades.unshift(trade);
    if (recentTrades.length > 50) recentTrades.pop();

    // 모든 클라이언트에게 가격 브로드캐스트
    io.emit('price:update', {
        price: currentPrice,
        previousPrice,
        change: currentPrice - previousPrice,
        time: Date.now()
    });

    // 체결 브로드캐스트
    io.emit('trade:new', trade);
}

function handleDepth(data) {
    orderbook = {
        bids: data.b.slice(0, 20).map(([price, qty]) => ({
            price: parseFloat(price),
            quantity: parseFloat(qty)
        })),
        asks: data.a.slice(0, 20).map(([price, qty]) => ({
            price: parseFloat(price),
            quantity: parseFloat(qty)
        }))
    };

    io.emit('orderbook:update', orderbook);
}

function handleTicker(data) {
    ticker24h = {
        high: parseFloat(data.h),
        low: parseFloat(data.l),
        volume: parseFloat(data.v),
        quoteVolume: parseFloat(data.q),
        priceChange: parseFloat(data.p),
        priceChangePercent: parseFloat(data.P),
        lastPrice: parseFloat(data.c)
    };

    io.emit('ticker:update', ticker24h);
}

function handleKline(data) {
    const kline = data.k;
    io.emit('kline:update', {
        time: kline.t,
        open: parseFloat(kline.o),
        high: parseFloat(kline.h),
        low: parseFloat(kline.l),
        close: parseFloat(kline.c),
        volume: parseFloat(kline.v),
        isClosed: kline.x
    });
}

// ========================================
// PHP에서 호출하는 브로드캐스트
// ========================================
function handleBroadcast(data) {
    const { type, payload } = data;

    console.log(`[Broadcast] ${type}:`, payload);

    switch (type) {
        case 'order:placed':
            // 새 주문 등록
            io.emit('order:placed', {
                ...payload,
                timestamp: Date.now()
            });
            break;

        case 'order:filled':
            // 주문 체결
            io.emit('order:filled', {
                ...payload,
                timestamp: Date.now()
            });

            // 거래 피드에 추가
            io.emit('trade:user', {
                userId: payload.user_id,
                nickname: payload.nickname || 'Anonymous',
                symbol: payload.symbol,
                side: payload.side,
                positionSide: payload.position_side,
                price: payload.price,
                quantity: payload.quantity,
                type: 'FILLED',
                timestamp: Date.now()
            });
            break;

        case 'order:cancelled':
            // 주문 취소
            io.emit('order:cancelled', {
                ...payload,
                timestamp: Date.now()
            });
            break;

        case 'position:opened':
            // 포지션 오픈
            io.emit('position:opened', {
                ...payload,
                timestamp: Date.now()
            });

            // 거래 피드
            io.emit('trade:user', {
                userId: payload.user_id,
                nickname: payload.nickname || 'Anonymous',
                symbol: payload.symbol,
                side: payload.side,
                price: payload.entry_price,
                quantity: payload.size,
                leverage: payload.leverage,
                type: 'OPEN_POSITION',
                timestamp: Date.now()
            });
            break;

        case 'position:closed':
            // 포지션 청산
            io.emit('position:closed', {
                ...payload,
                timestamp: Date.now()
            });

            // 거래 피드
            io.emit('trade:user', {
                userId: payload.user_id,
                nickname: payload.nickname || 'Anonymous',
                symbol: payload.symbol,
                side: payload.side,
                closePrice: payload.close_price,
                quantity: payload.quantity,
                pnl: payload.realized_pnl,
                type: 'CLOSE_POSITION',
                timestamp: Date.now()
            });
            break;

        case 'position:liquidated':
            // 강제 청산
            io.emit('position:liquidated', {
                ...payload,
                timestamp: Date.now()
            });

            // 청산 알림 (더 눈에 띄게)
            io.emit('alert:liquidation', {
                userId: payload.user_id,
                nickname: payload.nickname || 'Anonymous',
                symbol: payload.symbol,
                side: payload.side,
                liqPrice: payload.liquidation_price,
                size: payload.size,
                loss: payload.loss,
                timestamp: Date.now()
            });
            break;

        case 'balance:updated':
            // 특정 사용자에게만 잔고 업데이트
            const userSocket = findUserSocket(payload.user_id);
            if (userSocket) {
                io.to(userSocket).emit('balance:updated', payload);
            }
            break;

        default:
            console.log(`[Broadcast] Unknown type: ${type}`);
    }
}

function findUserSocket(userId) {
    for (const [socketId, user] of connectedUsers) {
        if (user.userId == userId) {
            return socketId;
        }
    }
    return null;
}

// ========================================
// User Agent 파싱 함수
// ========================================
function parseBrowser(ua) {
    if (!ua) return 'Unknown';
    if (ua.includes('Edg/')) return 'Edge';
    if (ua.includes('Chrome/')) return 'Chrome';
    if (ua.includes('Firefox/')) return 'Firefox';
    if (ua.includes('Safari/') && !ua.includes('Chrome')) return 'Safari';
    if (ua.includes('MSIE') || ua.includes('Trident/')) return 'IE';
    if (ua.includes('Opera') || ua.includes('OPR/')) return 'Opera';
    return 'Other';
}

function parseDevice(ua) {
    if (!ua) return 'Unknown';
    if (/Mobile|Android|iPhone|iPad|iPod/i.test(ua)) {
        if (/iPad|Tablet/i.test(ua)) return 'Tablet';
        return 'Mobile';
    }
    return 'Desktop';
}

function parseOS(ua) {
    if (!ua) return 'Unknown';
    if (ua.includes('Windows NT 10')) return 'Windows 10';
    if (ua.includes('Windows NT 6.3')) return 'Windows 8.1';
    if (ua.includes('Windows NT 6.1')) return 'Windows 7';
    if (ua.includes('Windows')) return 'Windows';
    if (ua.includes('Mac OS X')) return 'macOS';
    if (ua.includes('Android')) return 'Android';
    if (ua.includes('iPhone') || ua.includes('iPad')) return 'iOS';
    if (ua.includes('Linux')) return 'Linux';
    return 'Other';
}

// ========================================
// Socket.IO 이벤트 핸들러
// ========================================
io.on('connection', (socket) => {
    // IP 주소 추출
    const ip = socket.handshake.headers['x-forwarded-for'] ||
               socket.handshake.headers['x-real-ip'] ||
               socket.handshake.address ||
               socket.request.connection.remoteAddress ||
               'Unknown';

    const connectedAt = Date.now();

    console.log(`[Socket] Connected: ${socket.id.substring(0,8)} | IP: ${ip.replace('::ffff:', '').replace('::1', 'localhost')}`);

    // 기본 사용자 정보 저장
    const userData = {
        socketId: socket.id,
        ip: ip.replace('::ffff:', ''), // IPv6 prefix 제거
        connectedAt,
        lastActivity: connectedAt,
        currentPage: 'unknown',
        userId: null,
        email: null,
        nickname: null,
        isAdmin: false,
        hasActivePosition: false
    };
    connectedUsers.set(socket.id, userData);

    // 접속 로그 추가
    recentConnectionLogs.unshift({
        socketId: socket.id,
        ip: userData.ip,
        userId: null,
        email: null,
        nickname: 'Guest',
        time: connectedAt
    });
    if (recentConnectionLogs.length > MAX_LOG_SIZE) recentConnectionLogs.pop();

    // 연결된 클라이언트 수 브로드캐스트
    io.emit('users:count', io.engine.clientsCount);

    // 새 사용자 접속 알림
    io.emit('user:connected', userData);

    // 초기 데이터 전송
    socket.emit('init', {
        price: currentPrice,
        ticker: ticker24h,
        orderbook,
        recentTrades: recentTrades.slice(0, 20),
        onlineCount: io.engine.clientsCount
    });

    // 사용자 인증/등록
    socket.on('auth', (data) => {
        const { userId, email, nickname, token, currentPage, userAgent, screenSize, language } = data;
        const existingData = connectedUsers.get(socket.id);

        const updatedData = {
            ...existingData,
            userId,
            email,
            nickname,
            token,
            currentPage: currentPage || existingData.currentPage,
            userAgent: userAgent || existingData.userAgent,
            browser: parseBrowser(userAgent),
            device: parseDevice(userAgent),
            os: parseOS(userAgent),
            screenSize: screenSize || existingData.screenSize,
            language: language || existingData.language,
            lastActivity: Date.now()
        };
        connectedUsers.set(socket.id, updatedData);

        // 접속 로그 업데이트
        const logIndex = recentConnectionLogs.findIndex(l => l.socketId === socket.id);
        if (logIndex !== -1) {
            recentConnectionLogs[logIndex].userId = userId;
            recentConnectionLogs[logIndex].email = email;
            recentConnectionLogs[logIndex].nickname = nickname || email;
        }

        console.log(`[Socket] User authenticated: ${email} (ID: ${userId})`);
        console.log(`  ├─ Browser: ${updatedData.browser} | OS: ${updatedData.os} | Device: ${updatedData.device}`);
        console.log(`  ├─ Screen: ${updatedData.screenSize || 'Unknown'} | Lang: ${updatedData.language || 'Unknown'}`);
        console.log(`  ├─ Page: ${updatedData.currentPage || 'Unknown'}`);
        console.log(`  └─ Binance: ${binanceStatus.connected ? 'Connected' : 'Disconnected'} | Price: $${currentPrice.toLocaleString()} | Users: ${connectedUsers.size}`);

        // 업데이트된 정보 브로드캐스트
        io.emit('user:updated', updatedData);
    });

    // 관리자 식별
    socket.on('admin:identify', (data) => {
        const existingData = connectedUsers.get(socket.id);
        connectedUsers.set(socket.id, {
            ...existingData,
            isAdmin: true,
            currentPage: 'admin',
            lastActivity: Date.now()
        });
    });

    // 페이지 변경 알림
    socket.on('page:change', (page) => {
        const existingData = connectedUsers.get(socket.id);
        if (existingData) {
            existingData.currentPage = page;
            existingData.lastActivity = Date.now();
            connectedUsers.set(socket.id, existingData);
            io.emit('user:updated', existingData);
        }
    });

    // 활동 업데이트 (heartbeat)
    socket.on('activity', () => {
        const existingData = connectedUsers.get(socket.id);
        if (existingData) {
            existingData.lastActivity = Date.now();
            connectedUsers.set(socket.id, existingData);
        }
    });

    // 구독 요청
    socket.on('subscribe', (channels) => {
        if (Array.isArray(channels)) {
            channels.forEach(ch => socket.join(ch));
        } else {
            socket.join(channels);
        }
    });

    // 구독 해제
    socket.on('unsubscribe', (channels) => {
        if (Array.isArray(channels)) {
            channels.forEach(ch => socket.leave(ch));
        } else {
            socket.leave(channels);
        }
    });

    // Ping/Pong (레이턴시 측정용 - 클라이언트-서버 간)
    socket.on('ping', () => {
        socket.emit('pong');
    });

    // 바이낸스 연결 상태 요청
    socket.on('get:binance_status', () => {
        socket.emit('binance:status', binanceWS.getStatus());
    });

    // 현재가 요청
    socket.on('get:price', () => {
        socket.emit('price:update', {
            price: currentPrice,
            previousPrice,
            time: Date.now()
        });
    });

    // 오더북 요청
    socket.on('get:orderbook', () => {
        socket.emit('orderbook:update', orderbook);
    });

    // 유저 목록 요청 (대시보드용)
    socket.on('get:users', () => {
        const userList = Array.from(connectedUsers.values());
        socket.emit('users:list', userList);
    });

    // 최근 접속/이탈 로그 요청
    socket.on('get:connection_logs', () => {
        socket.emit('connection_logs', {
            connections: recentConnectionLogs,
            disconnections: recentDisconnectionLogs
        });
    });

    // 관리자: 사용자 강제퇴장
    socket.on('admin:kick_user', (data) => {
        const { socketId } = data;
        const targetSocket = io.sockets.sockets.get(socketId);
        if (targetSocket) {
            const user = connectedUsers.get(socketId);
            console.log(`[Admin] Kicking user: ${user?.email || socketId}`);
            targetSocket.emit('kicked', { reason: '관리자에 의해 강제퇴장 되었습니다.' });
            targetSocket.disconnect(true);
        }
    });

    // 포지션 목록 요청 (대시보드용 - DB 기반)
    socket.on('get:positions', async () => {
        try {
            if (!dbPool) {
                socket.emit('positions:list', []);
                return;
            }
            const [rows] = await dbPool.execute(`
                SELECT p.*, u.email, u.nickname
                FROM positions p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'open'
                ORDER BY p.created_at DESC
                LIMIT 50
            `);
            socket.emit('positions:list', rows);
        } catch (error) {
            console.error('[Socket] get:positions error:', error);
            socket.emit('positions:list', []);
        }
    });

    // 최근 주문 요청 (대시보드용 - DB 기반)
    socket.on('get:orders', async () => {
        try {
            if (!dbPool) {
                socket.emit('orders:list', { recent: [], pending: [] });
                return;
            }
            // 최근 체결 주문
            const [recent] = await dbPool.execute(`
                SELECT o.*, u.email, u.nickname
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.status = 'filled'
                ORDER BY o.updated_at DESC
                LIMIT 30
            `);
            // 대기 주문
            const [pending] = await dbPool.execute(`
                SELECT o.*, u.email, u.nickname
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.status = 'open'
                ORDER BY o.created_at DESC
                LIMIT 30
            `);
            socket.emit('orders:list', { recent, pending });
        } catch (error) {
            console.error('[Socket] get:orders error:', error);
            socket.emit('orders:list', { recent: [], pending: [] });
        }
    });

    // 최근 거래 기록 요청 (대시보드용 - DB 기반)
    // trades 테이블 또는 orders에서 filled 된 것을 가져옴
    socket.on('get:trades', async () => {
        try {
            if (!dbPool) {
                socket.emit('trades:list', []);
                return;
            }
            // 체결된 주문을 거래 기록으로 사용
            const [trades] = await dbPool.execute(`
                SELECT
                    o.id,
                    o.user_id,
                    u.email,
                    u.nickname,
                    CASE
                        WHEN o.reduce_only = 1 THEN 'CLOSE_POSITION'
                        ELSE 'OPEN_POSITION'
                    END as type,
                    o.side,
                    o.filled_quantity as quantity,
                    o.avg_fill_price as price,
                    0 as pnl,
                    o.created_at
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.status = 'filled'
                ORDER BY o.updated_at DESC
                LIMIT 50
            `);
            socket.emit('trades:list', trades);
        } catch (error) {
            console.error('[Socket] get:trades error:', error);
            socket.emit('trades:list', []);
        }
    });

    // 유저 목록 요청 (관리자용)
    socket.on('get:all_users', async () => {
        try {
            if (!dbPool) {
                socket.emit('all_users:list', []);
                return;
            }
            const [users] = await dbPool.execute(`
                SELECT id, email, nickname, role, status, vip_level, created_at, last_login
                FROM users
                ORDER BY id DESC
                LIMIT 100
            `);
            socket.emit('all_users:list', users);
        } catch (error) {
            console.error('[Socket] get:all_users error:', error);
            socket.emit('all_users:list', []);
        }
    });

    // 지갑 정보 요청
    socket.on('get:wallets', async () => {
        try {
            if (!dbPool) {
                socket.emit('wallets:list', []);
                return;
            }
            const [wallets] = await dbPool.execute(`
                SELECT w.*, u.email, u.nickname
                FROM wallets w
                LEFT JOIN users u ON w.user_id = u.id
                ORDER BY w.balance DESC
                LIMIT 100
            `);
            socket.emit('wallets:list', wallets);
        } catch (error) {
            console.error('[Socket] get:wallets error:', error);
            socket.emit('wallets:list', []);
        }
    });

    // 연결 해제
    socket.on('disconnect', () => {
        const user = connectedUsers.get(socket.id);
        if (user) {
            console.log(`[Socket] User disconnected: ${user.email || 'Guest'} (${user.ip})`);

            // 이탈 로그 추가
            recentDisconnectionLogs.unshift({
                socketId: socket.id,
                ip: user.ip,
                userId: user.userId,
                email: user.email,
                nickname: user.nickname || 'Guest',
                time: Date.now(),
                duration: Date.now() - user.connectedAt
            });
            if (recentDisconnectionLogs.length > MAX_LOG_SIZE) recentDisconnectionLogs.pop();

            // 이탈 알림
            io.emit('user:disconnected', {
                socketId: socket.id,
                userId: user.userId,
                email: user.email,
                nickname: user.nickname
            });
        }
        connectedUsers.delete(socket.id);

        // 약간의 딜레이 후 카운트 전송 (disconnect 완료 후)
        setTimeout(() => {
            io.emit('users:count', io.engine.clientsCount);
        }, 100);
    });
});

// ========================================
// 서버 시작
// ========================================
async function start() {
    try {
        // DB 연결
        dbPool = mysql.createPool(DB_CONFIG);
        console.log('[DB] Connection pool created');

        // Binance 스트림 구독
        binanceWS.subscribe('btcusdt@aggTrade', handleAggTrade);
        binanceWS.subscribe('btcusdt@depth20@100ms', handleDepth);
        binanceWS.subscribe('btcusdt@ticker', handleTicker);
        binanceWS.subscribe('btcusdt@kline_1m', handleKline);

        // HTTP 서버 시작
        server.listen(PORT, () => {
            console.log(`\n========================================`);
            console.log(`  Futures WebSocket Server`);
            console.log(`========================================`);
            console.log(`  Dashboard: http://localhost:${PORT}/`);
            console.log(`  Status API: http://localhost:${PORT}/api/status`);
            console.log(`  Broadcast API: POST http://localhost:${PORT}/api/broadcast`);
            console.log(`========================================\n`);
        });

    } catch (error) {
        console.error('[Start] Error:', error);
        process.exit(1);
    }
}

start();
