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
const Redis = require('ioredis');
const Queue = require('bull');

// ========================================
// 설정
// ========================================
const PORT = 3002;
const BINANCE_WS_URL = 'wss://fstream.binance.com/ws';
const BINANCE_REST_URL = 'https://fapi.binance.com';

// DB 설정
const DB_CONFIG = {
    host: 'localhost',
    user: 'root',
    password: '123123',
    database: 'trade',
    waitForConnections: true,
    connectionLimit: 10
};

// Redis 설정
const REDIS_CONFIG = {
    host: 'localhost',
    port: 6379,
    maxRetriesPerRequest: 1,
    retryStrategy: (times) => {
        if (times > 3) return null; // 3회 시도 후 포기
        return Math.min(times * 100, 3000);
    },
    lazyConnect: true,  // 수동 연결
    enableOfflineQueue: false  // 오프라인 큐 비활성화
};

// Redis 사용 여부 플래그
let redisEnabled = false;

// Redis 클라이언트
let redis = null;
let redisSub = null;
let orderQueue = null;

// ========================================
// 지원 심볼 목록
// ========================================
const SUPPORTED_SYMBOLS = [
    'BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'XRPUSDT',
    'DOGEUSDT', 'ADAUSDT', 'AVAXUSDT', 'DOTUSDT', 'LINKUSDT',
    'LTCUSDT', 'BCHUSDT', 'ATOMUSDT', 'ETCUSDT', 'XLMUSDT',
    'TRXUSDT', 'NEARUSDT', 'APTUSDT', 'ARBUSDT', 'OPUSDT',
    'MATICUSDT', 'FILUSDT', 'ICPUSDT', 'SHIBUSDT', 'UNIUSDT',
    'XMRUSDT', 'DASHUSDT', 'ZECUSDT', 'XTZUSDT', 'ONTUSDT', 'IOTAUSDT'
];

// ========================================
// 글로벌 상태
// ========================================
let currentPrice = 0;
let previousPrice = 0;
let ticker24h = {};
let recentTrades = [];
let klineCache = []; // 초기 캔들 데이터 캐시 (BTCUSDT 기본)
let activeSymbol = 'BTCUSDT'; // 현재 활성 심볼

// 심볼별 kline 캐시
const symbolKlineCache = new Map(); // symbol -> kline[]

// 심볼별 가격/티커 데이터
const symbolData = new Map(); // symbol -> { price, ticker, markPrice, indexPrice, fundingRate }

// 오더북 (Map으로 관리)
let orderbookBids = new Map(); // price -> quantity
let orderbookAsks = new Map(); // price -> quantity
let orderbookReady = false;

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
// 심볼별 가격 저장소
const symbolPrices = new Map();
const symbolPrevPrices = new Map();

// 통합 aggTrade 핸들러 - 모든 심볼 처리
function handleAggTrade(data) {
    const symbol = data.s;
    if (!symbol) return;

    const price = parseFloat(data.p);
    const prevPrice = symbolPrices.get(symbol) || price;
    symbolPrices.set(symbol, price);
    symbolPrevPrices.set(symbol, prevPrice);

    // 심볼별 가격 데이터 저장
    if (!symbolData.has(symbol)) {
        symbolData.set(symbol, {});
    }
    symbolData.get(symbol).price = price;

    // 최근 체결 추가
    const trade = {
        symbol: symbol,
        price: price,
        quantity: parseFloat(data.q),
        time: data.T,
        isBuyerMaker: data.m
    };

    recentTrades.unshift(trade);
    if (recentTrades.length > 50) recentTrades.pop();

    // 해당 심볼을 구독한 클라이언트에게만 가격 브로드캐스트 (room 기반)
    io.to(`symbol:${symbol}`).emit('price:update', {
        symbol: symbol,
        price: price,
        previousPrice: prevPrice,
        change: price - prevPrice,
        time: Date.now()
    });

    // 해당 심볼 구독자에게 체결 브로드캐스트
    io.to(`symbol:${symbol}`).emit('trade:new', trade);

    // 지정가 주문 매칭 체크 (throttle: 500ms마다)
    const throttleKey = `matching_${symbol}`;
    if (!handleAggTrade[throttleKey]) {
        handleAggTrade[throttleKey] = true;
        setTimeout(() => {
            checkLimitOrders(symbol, price);
            handleAggTrade[throttleKey] = false;
        }, 500);
    }
}

// 모든 심볼 가격을 브로드캐스트 (포지션 업데이트용)
let lastAllPricesBroadcast = 0;
function broadcastAllPrices() {
    const now = Date.now();
    // 500ms 쓰로틀링
    if (now - lastAllPricesBroadcast < 500) return;
    lastAllPricesBroadcast = now;

    const allPrices = {};
    for (const [symbol, price] of symbolPrices) {
        allPrices[symbol] = price;
    }

    // 모든 클라이언트에게 전송
    io.emit('prices:all', allPrices);
}

// 1초마다 모든 심볼 가격 브로드캐스트 (포지션 PnL 업데이트용)
setInterval(() => {
    if (symbolPrices.size > 0) {
        const allPrices = {};
        for (const [symbol, price] of symbolPrices) {
            allPrices[symbol] = price;
        }
        io.emit('prices:all', allPrices);
    }
}, 1000);

// 심볼별 오더북 저장소
const symbolOrderbooks = new Map(); // symbol -> { bids: Map, asks: Map }

// 심볼별 오더북 초기화
function getSymbolOrderbook(symbol) {
    if (!symbolOrderbooks.has(symbol)) {
        symbolOrderbooks.set(symbol, {
            bids: new Map(),
            asks: new Map()
        });
    }
    return symbolOrderbooks.get(symbol);
}

// 오더북을 배열로 변환하여 특정 심볼 구독자에게 전송
function broadcastOrderbook(symbol) {
    const ob = getSymbolOrderbook(symbol);

    const bids = Array.from(ob.bids.entries())
        .map(([price, quantity]) => ({ price, quantity }))
        .sort((a, b) => b.price - a.price);

    const asks = Array.from(ob.asks.entries())
        .map(([price, quantity]) => ({ price, quantity }))
        .sort((a, b) => a.price - b.price);

    io.to(`symbol:${symbol}`).emit('orderbook:update', { bids, asks, symbol });
}

// depth WebSocket 핸들러 (@depth20@100ms - 전체 교체 방식)
function handleDepth(data) {
    // 심볼 추출
    const symbol = data.s || (data.stream ? data.stream.split('@')[0].toUpperCase() : null);
    if (!symbol) return;

    const ob = getSymbolOrderbook(symbol);

    // @depth20@100ms 데이터 구조: { b: [[price, qty], ...], a: [[price, qty], ...] }
    ob.bids.clear();
    ob.asks.clear();

    if (data.b) {
        data.b.forEach(([price, qty]) => {
            const p = parseFloat(price);
            const q = parseFloat(qty);
            if (q > 0) ob.bids.set(p, q);
        });
    }

    if (data.a) {
        data.a.forEach(([price, qty]) => {
            const p = parseFloat(price);
            const q = parseFloat(qty);
            if (q > 0) ob.asks.set(p, q);
        });
    }

    // 클라이언트에 브로드캐스트 (throttle: 심볼별 100ms에 한번)
    const throttleKey = `depth_${symbol}`;
    if (!handleDepth[throttleKey]) {
        handleDepth[throttleKey] = true;
        setTimeout(() => {
            broadcastOrderbook(symbol);
            handleDepth[throttleKey] = false;
        }, 100);
    }
}

function handleTicker(data) {
    const symbol = data.s || 'BTCUSDT';

    const tickerInfo = {
        symbol: symbol,
        high: parseFloat(data.h),
        low: parseFloat(data.l),
        volume: parseFloat(data.v),
        quoteVolume: parseFloat(data.q),
        priceChange: parseFloat(data.p),
        priceChangePercent: parseFloat(data.P),
        lastPrice: parseFloat(data.c)
    };

    // 심볼별 데이터 저장
    if (!symbolData.has(symbol)) {
        symbolData.set(symbol, {});
    }
    const symData = symbolData.get(symbol);
    symData.price = tickerInfo.lastPrice;
    symData.ticker = tickerInfo;

    // 해당 심볼 구독자에게 티커 업데이트
    io.to(`symbol:${symbol}`).emit('ticker:update', { ...tickerInfo, symbol });

    // 모든 클라이언트에게 심볼별 가격 업데이트 (심볼 목록용)
    io.emit('symbol:price', {
        symbol: symbol,
        price: tickerInfo.lastPrice,
        priceChangePercent: tickerInfo.priceChangePercent
    });
}

function handleKline(data) {
    const kline = data.k;
    const symbol = kline.s; // 심볼 추출

    const klineData = {
        time: kline.t / 1000,  // 초 단위로 변환 (Lightweight Charts 요구사항)
        open: parseFloat(kline.o),
        high: parseFloat(kline.h),
        low: parseFloat(kline.l),
        close: parseFloat(kline.c),
        volume: parseFloat(kline.v),
        isClosed: kline.x,
        symbol: symbol
    };

    // 심볼별 캐시 업데이트
    updateKlineCacheForSymbol(symbol, klineData);

    // 해당 심볼을 구독한 클라이언트에게만 전송 (room 기반)
    io.to(`symbol:${symbol}`).emit('kline', klineData);
}

// ========================================
// 초기 캔들 데이터 로드 (Binance REST API)
// ========================================
async function fetchInitialKlines(symbol = 'BTCUSDT', interval = '1m', limit = 500) {
    try {
        const url = `${BINANCE_REST_URL}/fapi/v1/klines?symbol=${symbol}&interval=${interval}&limit=${limit}`;
        console.log(`[Klines] Fetching initial data: ${symbol} ${interval} (${limit} candles)`);

        const response = await fetch(url);
        const data = await response.json();

        if (!Array.isArray(data)) {
            console.error('[Klines] Invalid response:', data);
            return [];
        }

        // Lightweight Charts 형식으로 변환
        const klines = data.map(k => ({
            time: k[0] / 1000,  // 초 단위
            open: parseFloat(k[1]),
            high: parseFloat(k[2]),
            low: parseFloat(k[3]),
            close: parseFloat(k[4]),
            volume: parseFloat(k[5])
        }));

        // 심볼별 캐시에 저장
        symbolKlineCache.set(symbol, klines);

        console.log(`[Klines] ✅ Loaded ${klines.length} candles for ${symbol}`);
        return klines;
    } catch (error) {
        console.error('[Klines] ❌ Error fetching klines:', error.message);
        return [];
    }
}

// 특정 분봉 캔들 데이터 가져오기 (캐시 X, 즉시 반환)
async function fetchKlinesForInterval(symbol = 'BTCUSDT', interval = '1m', limit = 500) {
    try {
        const url = `${BINANCE_REST_URL}/fapi/v1/klines?symbol=${symbol}&interval=${interval}&limit=${limit}`;
        console.log(`[Klines] Fetching ${interval} data: ${symbol} (${limit} candles)`);

        const response = await fetch(url);
        const data = await response.json();

        if (!Array.isArray(data)) {
            console.error('[Klines] Invalid response:', data);
            return [];
        }

        // Lightweight Charts 형식으로 변환
        const klines = data.map(k => ({
            time: k[0] / 1000,  // 초 단위
            open: parseFloat(k[1]),
            high: parseFloat(k[2]),
            low: parseFloat(k[3]),
            close: parseFloat(k[4]),
            volume: parseFloat(k[5])
        }));

        console.log(`[Klines] ✅ Loaded ${klines.length} ${interval} candles`);
        return klines;
    } catch (error) {
        console.error('[Klines] ❌ Error fetching klines:', error.message);
        return [];
    }
}

// 초기 오더북 데이터 로드 (Binance REST API)
async function fetchInitialOrderbook(symbol = 'BTCUSDT', limit = 20) {
    try {
        const url = `${BINANCE_REST_URL}/fapi/v1/depth?symbol=${symbol}&limit=${limit}`;
        console.log(`[Orderbook] Fetching initial data: ${symbol}`);

        const response = await fetch(url);
        const data = await response.json();

        if (!data.bids || !data.asks) {
            console.error('[Orderbook] Invalid response:', data);
            return { bids: [], asks: [] };
        }

        // 심볼별 오더북 Map 초기화 및 채우기
        const ob = getSymbolOrderbook(symbol);
        ob.bids.clear();
        ob.asks.clear();

        data.bids.forEach(([price, qty]) => {
            const p = parseFloat(price);
            const q = parseFloat(qty);
            if (q > 0) ob.bids.set(p, q);
        });

        data.asks.forEach(([price, qty]) => {
            const p = parseFloat(price);
            const q = parseFloat(qty);
            if (q > 0) ob.asks.set(p, q);
        });

        // 클라이언트 전송용 배열로 변환
        const bids = Array.from(ob.bids.entries())
            .map(([price, quantity]) => ({ price, quantity }))
            .sort((a, b) => b.price - a.price);

        const asks = Array.from(ob.asks.entries())
            .map(([price, quantity]) => ({ price, quantity }))
            .sort((a, b) => a.price - b.price);

        console.log(`[Orderbook] ✅ ${symbol} Loaded ${bids.length} bids, ${asks.length} asks`);
        return { bids, asks };
    } catch (error) {
        console.error('[Orderbook] ❌ Error fetching orderbook:', error.message);
        return { bids: [], asks: [] };
    }
}

// 실시간 kline을 캐시에 반영 (기존 호환용)
function updateKlineCache(kline) {
    if (!klineCache.length) return;

    const lastCandle = klineCache[klineCache.length - 1];

    // 같은 시간의 캔들이면 업데이트
    if (lastCandle && lastCandle.time === kline.time) {
        lastCandle.open = kline.open;
        lastCandle.high = Math.max(lastCandle.high, kline.high);
        lastCandle.low = Math.min(lastCandle.low, kline.low);
        lastCandle.close = kline.close;
        lastCandle.volume = kline.volume;
    }
    // 새 캔들이면 추가
    else if (kline.isClosed || (lastCandle && kline.time > lastCandle.time)) {
        klineCache.push({
            time: kline.time,
            open: kline.open,
            high: kline.high,
            low: kline.low,
            close: kline.close,
            volume: kline.volume
        });
        // 캐시 크기 제한 (최대 1000개)
        if (klineCache.length > 1000) {
            klineCache.shift();
        }
    }
}

// 심볼별 kline 캐시 업데이트
function updateKlineCacheForSymbol(symbol, kline) {
    if (!symbolKlineCache.has(symbol)) {
        symbolKlineCache.set(symbol, []);
    }
    const cache = symbolKlineCache.get(symbol);
    if (!cache.length) return;

    const lastCandle = cache[cache.length - 1];

    // 같은 시간의 캔들이면 업데이트
    if (lastCandle && lastCandle.time === kline.time) {
        lastCandle.open = kline.open;
        lastCandle.high = Math.max(lastCandle.high, kline.high);
        lastCandle.low = Math.min(lastCandle.low, kline.low);
        lastCandle.close = kline.close;
        lastCandle.volume = kline.volume;
    }
    // 새 캔들이면 추가
    else if (kline.isClosed || (lastCandle && kline.time > lastCandle.time)) {
        cache.push({
            time: kline.time,
            open: kline.open,
            high: kline.high,
            low: kline.low,
            close: kline.close,
            volume: kline.volume
        });
        // 캐시 크기 제한 (최대 1000개)
        if (cache.length > 1000) {
            cache.shift();
        }
    }
}

// 마크가격 + 펀딩비율 핸들러
function handleMarkPrice(data) {
    const symbol = data.s || 'BTCUSDT';

    // 심볼별 데이터 저장
    if (!symbolData.has(symbol)) {
        symbolData.set(symbol, {});
    }
    const symData = symbolData.get(symbol);
    symData.markPrice = parseFloat(data.p);
    symData.indexPrice = parseFloat(data.i);
    symData.fundingRate = parseFloat(data.r);
    symData.nextFundingTime = data.T;

    // 해당 심볼 구독자에게 마크가격 브로드캐스트
    io.to(`symbol:${symbol}`).emit('markPrice', {
        symbol: symbol,
        markPrice: parseFloat(data.p),
        indexPrice: parseFloat(data.i),
        fundingRate: parseFloat(data.r),
        nextFundingTime: data.T,
        timestamp: data.E
    });
}

// ========================================
// PHP에서 호출하는 브로드캐스트
// ========================================
function handleBroadcast(data) {
    const { type, payload } = data;

    // 사용자 표시명
    const userDisplay = payload.login_id || payload.nickname || `ID:${payload.user_id}`;
    const sideKr = payload.side === 'buy' ? '매수' : '매도';
    const positionKr = payload.position_side === 'long' ? '롱' : '숏';

    switch (type) {
        case 'order:placed':
            // 새 주문 등록
            io.emit('order:placed', {
                ...payload,
                timestamp: Date.now()
            });

            // 한국어 로그
            if (payload.order_type === 'limit') {
                console.log(`\n┌─────────────────────────────────────────┐`);
                console.log(`│  📝 지정가 주문 접수                      │`);
                console.log(`├─────────────────────────────────────────┤`);
                console.log(`│  👤 ${userDisplay}`);
                console.log(`│  📊 ${sideKr} ${positionKr} @ $${parseFloat(payload.price).toLocaleString()}`);
                console.log(`│  📦 수량: ${payload.quantity} | 주문번호: ${payload.order_id.slice(-12)}`);
                console.log(`└─────────────────────────────────────────┘`);

                // Redis에 추가
                if (redisEnabled) {
                    (async () => {
                        try {
                            const [orders] = await dbPool.execute(`
                                SELECT id, order_id, user_id, symbol, side, position_side, price, quantity, leverage, reduce_only
                                FROM orders
                                WHERE order_id = ? AND status = 'open'
                            `, [payload.order_id]);

                            if (orders.length > 0) {
                                await addOrderToRedis(orders[0]);
                                console.log(`│  ✅ Redis 대기열 등록 완료`);
                            }
                        } catch (err) {
                            console.error(`│  ❌ Redis 등록 실패: ${err.message}`);
                        }
                    })();
                }
            }
            break;

        case 'order:filled':
            // 시장가 주문 체결 (PHP에서 호출)
            io.emit('order:filled', {
                ...payload,
                timestamp: Date.now()
            });

            console.log(`\n┌─────────────────────────────────────────┐`);
            console.log(`│  ⚡ 시장가 주문 체결                       │`);
            console.log(`├─────────────────────────────────────────┤`);
            console.log(`│  👤 ${userDisplay}`);
            console.log(`│  📊 ${sideKr} ${positionKr}`);
            console.log(`│  💰 체결가: $${parseFloat(payload.price).toLocaleString()}`);
            console.log(`│  📦 수량: ${payload.quantity}`);
            console.log(`│  ⚡ 레버리지: ${payload.leverage}x`);
            console.log(`└─────────────────────────────────────────┘`);

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

            console.log(`\n┌─────────────────────────────────────────┐`);
            console.log(`│  ❌ 주문 취소                             │`);
            console.log(`├─────────────────────────────────────────┤`);
            console.log(`│  👤 ${userDisplay}`);
            console.log(`│  📊 ${sideKr} @ $${parseFloat(payload.price).toLocaleString()}`);
            console.log(`│  📦 수량: ${payload.quantity}`);
            console.log(`└─────────────────────────────────────────┘`);
            break;

        case 'position:opened':
            // 포지션 오픈 (시장가)
            io.emit('position:opened', {
                ...payload,
                timestamp: Date.now()
            });

            console.log(`\n┌─────────────────────────────────────────┐`);
            console.log(`│  🚀 포지션 오픈                           │`);
            console.log(`├─────────────────────────────────────────┤`);
            console.log(`│  👤 ${userDisplay}`);
            console.log(`│  📊 ${positionKr} 포지션`);
            console.log(`│  💰 진입가: $${parseFloat(payload.entry_price).toLocaleString()}`);
            console.log(`│  📦 수량: ${payload.size}`);
            console.log(`│  ⚡ 레버리지: ${payload.leverage}x`);
            console.log(`└─────────────────────────────────────────┘`);

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
            // 포지션 청산 (시장가)
            io.emit('position:closed', {
                ...payload,
                timestamp: Date.now()
            });

            const pnl = parseFloat(payload.realized_pnl || 0);
            const pnlEmoji = pnl >= 0 ? '🟢' : '🔴';
            const pnlStr = pnl >= 0 ? `+${pnl.toFixed(2)}` : pnl.toFixed(2);

            console.log(`\n┌─────────────────────────────────────────┐`);
            console.log(`│  💫 포지션 청산                           │`);
            console.log(`├─────────────────────────────────────────┤`);
            console.log(`│  👤 ${userDisplay}`);
            console.log(`│  📊 ${positionKr} 포지션 청산`);
            console.log(`│  💰 청산가: $${parseFloat(payload.close_price).toLocaleString()}`);
            console.log(`│  📦 수량: ${payload.quantity}`);
            console.log(`│  ${pnlEmoji} 실현손익: $${pnlStr} USDT`);
            console.log(`└─────────────────────────────────────────┘`);

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

            console.log(`\n╔═════════════════════════════════════════╗`);
            console.log(`║  💀 강제 청산 (LIQUIDATION)              ║`);
            console.log(`╠═════════════════════════════════════════╣`);
            console.log(`║  👤 ${userDisplay}`);
            console.log(`║  📊 ${positionKr} 포지션`);
            console.log(`║  💰 청산가: $${parseFloat(payload.liquidation_price).toLocaleString()}`);
            console.log(`║  📦 수량: ${payload.size}`);
            console.log(`║  🔴 손실: -$${Math.abs(parseFloat(payload.loss || 0)).toFixed(2)}`);
            console.log(`╚═════════════════════════════════════════╝`);

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
            console.log(`[Broadcast] 알 수 없는 타입: ${type}`);
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

    // 기본 심볼 room 참가 (BTCUSDT)
    socket.currentSymbol = 'BTCUSDT';
    socket.join('symbol:BTCUSDT');

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

    // 초기 데이터 전송 (소켓의 현재 심볼 기준)
    const initSymbol = socket.currentSymbol || 'BTCUSDT';
    const initOb = getSymbolOrderbook(initSymbol);
    const initOrderbookData = {
        bids: Array.from(initOb.bids.entries())
            .map(([price, quantity]) => ({ price, quantity }))
            .sort((a, b) => b.price - a.price),
        asks: Array.from(initOb.asks.entries())
            .map(([price, quantity]) => ({ price, quantity }))
            .sort((a, b) => a.price - b.price),
        symbol: initSymbol
    };

    socket.emit('init', {
        price: symbolPrices.get(initSymbol) || currentPrice,
        ticker: ticker24h,
        orderbook: initOrderbookData,
        recentTrades: recentTrades.slice(0, 20),
        onlineCount: io.engine.clientsCount,
        symbol: initSymbol
    });

    // 초기 캔들 데이터 전송
    if (klineCache.length > 0) {
        socket.emit('klines', klineCache);
    }

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
        const symbol = socket.currentSymbol || 'BTCUSDT';
        const ob = getSymbolOrderbook(symbol);
        const orderbookData = {
            symbol: symbol,
            bids: Array.from(ob.bids.entries())
                .map(([price, quantity]) => ({ price, quantity }))
                .sort((a, b) => b.price - a.price),
            asks: Array.from(ob.asks.entries())
                .map(([price, quantity]) => ({ price, quantity }))
                .sort((a, b) => a.price - b.price)
        };
        socket.emit('orderbook:update', orderbookData);
    });

    // 캔들(klines) 데이터 요청
    socket.on('get:klines', async (params = {}) => {
        const socketSymbol = socket.currentSymbol || 'BTCUSDT';
        const { symbol = socketSymbol, interval = '1m', limit = 500 } = params;

        // Binance API에서 가져오기
        const data = await fetchKlinesForInterval(symbol, interval, limit);
        socket.emit('klines', data);
    });

    // 심볼 변경 요청
    socket.on('switch:symbol', async (data) => {
        const { symbol } = data;
        if (!symbol || !SUPPORTED_SYMBOLS.includes(symbol)) {
            socket.emit('error', { message: '지원하지 않는 심볼입니다.' });
            return;
        }

        // 소켓별 심볼 저장
        const oldSocketSymbol = socket.currentSymbol || 'BTCUSDT';
        socket.currentSymbol = symbol;

        console.log(`[Socket ${socket.id.slice(-6)}] 심볼 변경: ${oldSocketSymbol} -> ${symbol}`);

        // REST API로 초기 데이터 로드
        const [klines, orderbook] = await Promise.all([
            fetchInitialKlines(symbol, '1m', 500),
            fetchInitialOrderbook(symbol, 20)
        ]);

        console.log(`[Socket ${socket.id.slice(-6)}] 오더북 로드 완료: bids=${orderbook.bids?.length}, asks=${orderbook.asks?.length}`);

        // 소켓을 심볼별 room에 참가시킴 (실시간 업데이트용)
        socket.leave(`symbol:${oldSocketSymbol}`);
        socket.join(`symbol:${symbol}`);

        // 요청한 클라이언트에게만 응답
        socket.emit('symbol:switched', {
            symbol: symbol,
            klines: klines,
            orderbook: orderbook,
            symbolData: symbolData.get(symbol) || {}
        });
    });

    // 심볼 목록 및 현재 가격 요청
    socket.on('get:symbols', () => {
        const symbolsList = SUPPORTED_SYMBOLS.map(symbol => {
            const data = symbolData.get(symbol) || {};
            return {
                symbol: symbol,
                price: data.price || 0,
                priceChangePercent: data.ticker?.priceChangePercent || 0,
                markPrice: data.markPrice || 0,
                indexPrice: data.indexPrice || 0,
                fundingRate: data.fundingRate || 0
            };
        });
        socket.emit('symbols:list', {
            symbols: symbolsList,
            activeSymbol: socket.currentSymbol || 'BTCUSDT'
        });
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
// Redis 기반 지정가 주문 매칭 엔진
// ========================================
// 심볼별 동적 Redis 키 생성
function getRedisKeys(symbol = 'BTCUSDT') {
    return {
        PRICE: `futures:price:${symbol}`,
        BUY_ORDERS: `futures:orders:buy:${symbol}`,
        SELL_ORDERS: `futures:orders:sell:${symbol}`,
        ORDER_DATA: 'futures:order:',
        LOCK: 'futures:lock:order:'
    };
}

let lastMatchingPrice = {};  // 심볼별 마지막 매칭 가격

// Redis에 가격 캐시 업데이트
async function updatePriceCache(symbol, price) {
    if (!redisEnabled) return;
    try {
        const keys = getRedisKeys(symbol);
        await redis.set(keys.PRICE, price, 'EX', 60); // 60초 TTL
    } catch (error) {
        // Redis 오류 시 비활성화
        console.error('[Redis] Price cache error, disabling Redis:', error.message);
        redisEnabled = false;
    }
}

// 지정가 주문을 Redis Sorted Set에 추가
async function addOrderToRedis(order) {
    if (!redisEnabled) return;
    try {
        const keys = getRedisKeys(order.symbol);
        const orderKey = keys.ORDER_DATA + order.order_id;
        const sortedSetKey = order.side === 'buy' ? keys.BUY_ORDERS : keys.SELL_ORDERS;

        // 주문 데이터 저장 (Hash)
        await redis.hmset(orderKey, {
            id: order.id,
            order_id: order.order_id,
            user_id: order.user_id,
            symbol: order.symbol,
            side: order.side,
            position_side: order.position_side,
            price: order.price,
            quantity: order.quantity,
            leverage: order.leverage,
            reduce_only: order.reduce_only ? '1' : '0'
        });
        await redis.expire(orderKey, 86400); // 24시간 TTL

        // Sorted Set에 추가 (score = price)
        await redis.zadd(sortedSetKey, parseFloat(order.price), order.order_id);

        const sideKr = order.side === 'buy' ? '매수' : '매도';
        console.log(`[Redis] 📋 ${order.symbol} ${sideKr} 대기열 등록: $${parseFloat(order.price).toLocaleString()} (${order.order_id.slice(-8)})`);
    } catch (error) {
        console.error('[Redis] ❌ 주문 등록 실패:', error.message);
    }
}

// Redis에서 주문 제거
async function removeOrderFromRedis(orderId, side, symbol = 'BTCUSDT') {
    if (!redisEnabled) return;
    try {
        const keys = getRedisKeys(symbol);
        const orderKey = keys.ORDER_DATA + orderId;
        const sortedSetKey = side === 'buy' ? keys.BUY_ORDERS : keys.SELL_ORDERS;

        await redis.del(orderKey);
        await redis.zrem(sortedSetKey, orderId);

        console.log(`[Redis] 🗑️ 대기열 제거: ${orderId.slice(-8)}`);
    } catch (error) {
        console.error('[Redis] ❌ 주문 제거 실패:', error.message);
    }
}

// 분산 락 획득
async function acquireLock(orderId, ttl = 5000) {
    if (!redisEnabled) return true; // Redis 없으면 그냥 진행
    const keys = getRedisKeys();
    const lockKey = keys.LOCK + orderId;
    try {
        const result = await redis.set(lockKey, '1', 'PX', ttl, 'NX');
        return result === 'OK';
    } catch (error) {
        console.error('[Redis] Lock acquire error:', error.message);
        return false;
    }
}

// 분산 락 해제
async function releaseLock(orderId) {
    if (!redisEnabled) return;
    const keys = getRedisKeys();
    const lockKey = keys.LOCK + orderId;
    try {
        await redis.del(lockKey);
    } catch (error) {
        console.error('[Redis] Lock release error:', error.message);
    }
}

// Redis 기반 지정가 주문 체크 (심볼별)
async function checkLimitOrders(symbol, currentPrice) {
    if (!lastMatchingPrice[symbol]) lastMatchingPrice[symbol] = 0;
    if (currentPrice === lastMatchingPrice[symbol] || currentPrice <= 0) return;
    lastMatchingPrice[symbol] = currentPrice;

    if (!dbPool) return;

    // Redis 비활성화 상태면 DB fallback
    if (!redisEnabled) {
        return checkLimitOrdersDB(symbol, currentPrice);
    }

    try {
        const keys = getRedisKeys(symbol);

        // 가격 캐시 업데이트
        await updatePriceCache(symbol, currentPrice);

        // BUY 주문: 현재가 <= 주문가 (주문가가 현재가 이상인 것)
        // Redis ZRANGEBYSCORE: score >= currentPrice 인 주문들
        const buyOrders = await redis.zrangebyscore(
            keys.BUY_ORDERS,
            currentPrice,  // min
            '+inf',        // max
            'LIMIT', 0, 10
        );

        // SELL 주문: 현재가 >= 주문가 (주문가가 현재가 이하인 것)
        // Redis ZRANGEBYSCORE: score <= currentPrice 인 주문들
        const sellOrders = await redis.zrangebyscore(
            keys.SELL_ORDERS,
            '-inf',        // min
            currentPrice,  // max
            'LIMIT', 0, 10
        );

        // 매칭할 주문들 처리
        const ordersToFill = [...buyOrders, ...sellOrders];

        for (const orderId of ordersToFill) {
            // Bull Queue에 체결 작업 추가
            if (orderQueue) {
                await orderQueue.add('fill', {
                    orderId,
                    fillPrice: currentPrice,
                    symbol: symbol
                }, {
                    attempts: 3,
                    backoff: { type: 'exponential', delay: 1000 },
                    removeOnComplete: true
                });
            } else {
                // Queue 없으면 직접 처리
                await processOrderFill(orderId, currentPrice, symbol);
            }
        }
    } catch (error) {
        console.error('[Matching] Error checking limit orders:', error.message);
    }
}

// DB Fallback: Redis 없을 때 직접 DB 조회
async function checkLimitOrdersDB(symbol, currentPrice) {
    try {
        const [orders] = await dbPool.execute(`
            SELECT o.*, u.nickname, u.email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.status = 'open'
              AND o.order_type = 'limit'
              AND o.symbol = ?
              AND (
                  (o.side = 'buy' AND o.price >= ?) OR
                  (o.side = 'sell' AND o.price <= ?)
              )
            ORDER BY o.created_at ASC
            LIMIT 10
        `, [symbol, currentPrice, currentPrice]);

        for (const order of orders) {
            await fillLimitOrder(order, currentPrice);
        }
    } catch (error) {
        console.error('[Matching] DB fallback error:', error.message);
    }
}

// 주문 체결 처리 (Bull Queue Worker 또는 직접 호출)
async function processOrderFill(orderId, fillPrice, symbol = 'BTCUSDT') {
    // 락 획득
    if (!await acquireLock(orderId)) {
        console.log(`[Matching] ⏳ 주문 처리 중 (다른 워커): ${orderId.slice(-8)}`);
        return;
    }

    try {
        const keys = getRedisKeys(symbol);
        // Redis에서 주문 데이터 조회
        const orderKey = keys.ORDER_DATA + orderId;
        const orderData = await redis.hgetall(orderKey);

        if (!orderData || !orderData.id) {
            // Redis에 없으면 DB에서 조회
            const [rows] = await dbPool.execute(`
                SELECT o.*, u.nickname, u.email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.order_id = ? AND o.status = 'open'
            `, [orderId]);

            if (rows.length === 0) {
                await removeOrderFromRedis(orderId, 'buy', symbol);
                await removeOrderFromRedis(orderId, 'sell', symbol);
                return;
            }

            await fillLimitOrder(rows[0], fillPrice);
        } else {
            // Redis 데이터로 체결
            const order = {
                id: parseInt(orderData.id),
                order_id: orderData.order_id,
                user_id: parseInt(orderData.user_id),
                symbol: orderData.symbol,
                side: orderData.side,
                position_side: orderData.position_side,
                price: parseFloat(orderData.price),
                quantity: parseFloat(orderData.quantity),
                leverage: parseInt(orderData.leverage),
                reduce_only: orderData.reduce_only === '1'
            };

            // DB에서 최신 상태 확인 및 사용자 정보 조회
            const [rows] = await dbPool.execute(`
                SELECT o.status, u.nickname, u.email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?
            `, [order.id]);

            if (rows.length === 0 || rows[0].status !== 'open') {
                await removeOrderFromRedis(orderId, order.side, order.symbol);
                return;
            }

            order.nickname = rows[0].nickname;
            order.email = rows[0].email;

            await fillLimitOrder(order, fillPrice);
        }

        // Redis에서 주문 제거
        const orderSymbol = orderData?.symbol || symbol;
        await removeOrderFromRedis(orderId, orderData?.side || 'buy', orderSymbol);

    } catch (error) {
        console.error(`[Matching] ❌ 주문 처리 오류 (${orderId.slice(-8)}):`, error.message);
    } finally {
        await releaseLock(orderId);
    }
}

async function fillLimitOrder(order, fillPrice) {
    const connection = await dbPool.getConnection();

    try {
        await connection.beginTransaction();

        // 지정가 주문은 주문 가격으로 체결 (fillPrice는 트리거 조건 확인용)
        const orderPrice = parseFloat(order.price);
        console.log(`[Matching] 🔄 지정가 체결 시작: $${orderPrice.toLocaleString()} (시장가 $${fillPrice.toLocaleString()}에서 트리거)`);

        // 1. 주문 상태 업데이트 - 지정가로 체결
        await connection.execute(`
            UPDATE orders
            SET status = 'filled',
                filled_quantity = quantity,
                avg_fill_price = ?,
                updated_at = NOW()
            WHERE id = ? AND status = 'open'
        `, [orderPrice, order.id]);

        // 2. 지갑 정보 조회
        const [wallets] = await connection.execute(`
            SELECT * FROM wallets WHERE user_id = ? AND currency = 'USDT' FOR UPDATE
        `, [order.user_id]);

        if (!wallets.length) {
            throw new Error('Wallet not found');
        }

        const wallet = wallets[0];
        const quantity = parseFloat(order.quantity);
        const leverage = parseInt(order.leverage);
        // 지정가 주문: orderPrice로 명목가치 계산 (이미 위에서 정의됨)
        const notionalValue = quantity * orderPrice;
        const requiredMargin = notionalValue / leverage;
        const fee = notionalValue * 0.0004; // taker fee

        // 3. 동결 금액 해제 (지정가 주문 시 동결했던 금액)
        const frozenAmount = (quantity * orderPrice / leverage) + (quantity * orderPrice * 0.0004);

        // 4. 포지션 처리
        const isOpening = (order.side === 'buy' && order.position_side === 'long') ||
                          (order.side === 'sell' && order.position_side === 'short');

        // 기존 포지션 확인
        const [positions] = await connection.execute(`
            SELECT * FROM positions
            WHERE user_id = ? AND symbol = ? AND side = ? AND status = 'open'
            FOR UPDATE
        `, [order.user_id, order.symbol, order.position_side]);

        let positionId = null;
        let realizedPnl = 0;
        let isClose = false;

        if (positions.length > 0) {
            const existingPosition = positions[0];

            if (isOpening && !order.reduce_only) {
                // 포지션 추가 - 지정가로 평균 진입가 계산
                const newSize = parseFloat(existingPosition.size) + quantity;
                const newEntryPrice = ((parseFloat(existingPosition.entry_price) * parseFloat(existingPosition.size)) + (orderPrice * quantity)) / newSize;
                const newMargin = parseFloat(existingPosition.margin) + requiredMargin;

                await connection.execute(`
                    UPDATE positions SET size = ?, entry_price = ?, margin = ?, leverage = ? WHERE id = ?
                `, [newSize, newEntryPrice, newMargin, leverage, existingPosition.id]);

                positionId = existingPosition.id;

                // 새 마진만큼 동결 유지, 나머지 반환
                const refund = frozenAmount - requiredMargin - fee;
                await connection.execute(`
                    UPDATE wallets SET frozen_balance = frozen_balance - ? WHERE id = ?
                `, [refund, wallet.id]);
            } else {
                // 포지션 청산
                isClose = true;
                const closeQty = Math.min(quantity, parseFloat(existingPosition.size));
                const remainingSize = parseFloat(existingPosition.size) - closeQty;

                // PNL 계산
                if (order.position_side === 'long') {
                    realizedPnl = (fillPrice - parseFloat(existingPosition.entry_price)) * closeQty;
                } else {
                    realizedPnl = (parseFloat(existingPosition.entry_price) - fillPrice) * closeQty;
                }
                realizedPnl -= fee;

                if (remainingSize <= 0) {
                    // 완전 청산
                    await connection.execute(`
                        UPDATE positions SET status = 'closed', size = 0, realized_pnl = realized_pnl + ?, closed_at = NOW() WHERE id = ?
                    `, [realizedPnl, existingPosition.id]);

                    // 마진 반환 + PNL + 동결 해제
                    const closedMargin = parseFloat(existingPosition.margin);
                    await connection.execute(`
                        UPDATE wallets SET
                            available_balance = available_balance + ? + ?,
                            frozen_balance = frozen_balance - ?
                        WHERE id = ?
                    `, [closedMargin + realizedPnl, frozenAmount, frozenAmount, wallet.id]);
                } else {
                    // 부분 청산
                    const partialMargin = parseFloat(existingPosition.margin) * (closeQty / parseFloat(existingPosition.size));

                    await connection.execute(`
                        UPDATE positions SET size = ?, margin = margin - ?, realized_pnl = realized_pnl + ? WHERE id = ?
                    `, [remainingSize, partialMargin, realizedPnl, existingPosition.id]);

                    await connection.execute(`
                        UPDATE wallets SET
                            available_balance = available_balance + ? + ?,
                            frozen_balance = frozen_balance - ?
                        WHERE id = ?
                    `, [partialMargin + realizedPnl, frozenAmount, frozenAmount, wallet.id]);
                }

                positionId = existingPosition.id;

                // PNL 트랜잭션 기록
                if (realizedPnl !== 0) {
                    await connection.execute(`
                        INSERT INTO transactions (user_id, type, currency, amount, reference_id, reference_type, memo)
                        VALUES (?, 'pnl', 'USDT', ?, ?, 'order', ?)
                    `, [order.user_id, realizedPnl, order.order_id, realizedPnl > 0 ? '포지션 수익' : '포지션 손실']);
                }
            }
        } else if (isOpening && !order.reduce_only) {
            // 신규 포지션 생성 - 지정가(orderPrice)로 진입
            const margin = requiredMargin;
            const maintenanceMarginRate = 0.005;
            let liqPrice;
            if (order.position_side === 'long') {
                liqPrice = orderPrice * (1 - (1 / leverage) + maintenanceMarginRate);
            } else {
                liqPrice = orderPrice * (1 + (1 / leverage) - maintenanceMarginRate);
            }

            const [result] = await connection.execute(`
                INSERT INTO positions (user_id, symbol, side, size, entry_price, mark_price, liquidation_price, margin, leverage, margin_mode)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'cross')
            `, [order.user_id, order.symbol, order.position_side, quantity, orderPrice, orderPrice, liqPrice, margin, leverage]);

            positionId = result.insertId;

            // 마진 동결 유지, 수수료 차감, 초과분 반환
            const refund = frozenAmount - requiredMargin - fee;
            await connection.execute(`
                UPDATE wallets SET
                    available_balance = available_balance - ?,
                    frozen_balance = frozen_balance - ?
                WHERE id = ?
            `, [fee, refund, wallet.id]);
        }

        // 5. 거래 기록 생성 - 지정가로 기록
        await connection.execute(`
            INSERT INTO trades (user_id, order_id, position_id, symbol, side, position_side, price, quantity, quote_quantity, fee, realized_pnl)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [order.user_id, order.order_id, positionId, order.symbol, order.side, order.position_side, orderPrice, quantity, notionalValue, fee, realizedPnl]);

        await connection.commit();

        // 한국어 체결 안내 로그
        const userDisplay = order.nickname || order.email || `ID:${order.user_id}`;
        const sideKr = order.side === 'buy' ? '매수' : '매도';
        const positionKr = order.position_side === 'long' ? '롱' : '숏';
        const actionKr = isClose ? '청산' : '진입';
        const pnlStr = isClose ? (realizedPnl >= 0 ? `+${realizedPnl.toFixed(2)}` : realizedPnl.toFixed(2)) : '';

        console.log(`\n════════════════════════════════════════`);
        console.log(`  ✅ 지정가 주문 체결 완료`);
        console.log(`════════════════════════════════════════`);
        console.log(`  👤 사용자: ${userDisplay}`);
        console.log(`  📋 주문번호: ${order.order_id}`);
        console.log(`  💹 종목: ${order.symbol}`);
        console.log(`  📊 ${sideKr} ${positionKr} ${actionKr}`);
        console.log(`  💰 체결가: $${orderPrice.toLocaleString()}`);
        console.log(`  📦 수량: ${quantity} 개`);
        console.log(`  ⚡ 레버리지: ${leverage}x`);
        console.log(`  💵 거래대금: $${notionalValue.toLocaleString()}`);
        console.log(`  🏦 필요증거금: $${requiredMargin.toFixed(2)}`);
        console.log(`  💸 수수료: $${fee.toFixed(4)}`);
        if (isClose) {
            console.log(`  ${realizedPnl >= 0 ? '🟢' : '🔴'} 실현손익: $${pnlStr} USDT`);
        }
        console.log(`════════════════════════════════════════\n`);

        // 6. WebSocket 브로드캐스트 - 지정가로 체결
        io.emit('order:filled', {
            user_id: order.user_id,
            nickname: order.nickname || order.email,
            order_id: order.order_id,
            symbol: order.symbol,
            side: order.side,
            position_side: order.position_side,
            price: orderPrice,
            quantity: quantity,
            leverage: leverage,
            timestamp: Date.now()
        });

        // 거래 피드 - 지정가로 표시
        io.emit('trade:user', {
            userId: order.user_id,
            nickname: order.nickname || 'Anonymous',
            symbol: order.symbol,
            side: order.side,
            positionSide: order.position_side,
            price: orderPrice,
            quantity: quantity,
            type: isClose ? 'CLOSE_POSITION' : 'OPEN_POSITION',
            pnl: realizedPnl,
            timestamp: Date.now()
        });

        // 해당 유저에게 잔고 업데이트 알림
        const userSocket = findUserSocket(order.user_id);
        if (userSocket) {
            io.to(userSocket).emit('balance:updated', { user_id: order.user_id });
            io.to(userSocket).emit('order:my_filled', {
                order_id: order.order_id,
                fill_price: orderPrice,
                realized_pnl: realizedPnl
            });
        }

    } catch (error) {
        await connection.rollback();
        console.error(`[Matching] ❌ 체결 실패 (${order.order_id.slice(-8)}):`, error.message);
    } finally {
        connection.release();
    }
}

// ========================================
// Redis 초기화 및 기존 주문 로드
// ========================================
async function initRedis() {
    return new Promise((resolve) => {
        try {
            console.log('[Redis] 🔌 연결 시도 중...');

            // Redis 연결 (lazyConnect 사용)
            redis = new Redis(REDIS_CONFIG);

            // 연결 타임아웃 설정
            const connectTimeout = setTimeout(() => {
                console.log('[Redis] ⚠️ 연결 타임아웃 - DB 폴백 모드로 실행');
                cleanupRedis();
                resolve(false);
            }, 3000);

            redis.on('connect', async () => {
                clearTimeout(connectTimeout);
                console.log('[Redis] ✅ 연결 성공');

                try {
                    // 두 번째 연결 (Pub/Sub용)
                    redisSub = new Redis(REDIS_CONFIG);
                    await redisSub.connect();

                    // Bull Queue 설정 (별도 Redis 설정 - enableOfflineQueue 활성화 필요)
                    const bullRedisConfig = {
                        host: REDIS_CONFIG.host,
                        port: REDIS_CONFIG.port,
                        maxRetriesPerRequest: null,  // Bull은 null 필요
                        enableReadyCheck: false
                    };
                    orderQueue = new Queue('order-fill', {
                        redis: bullRedisConfig,
                        defaultJobOptions: {
                            attempts: 3,
                            backoff: { type: 'exponential', delay: 1000 },
                            removeOnComplete: true,
                            removeOnFail: false
                        }
                    });

                    // Queue Worker 설정
                    orderQueue.process('fill', 5, async (job) => {
                        const { orderId, fillPrice, symbol } = job.data;
                        await processOrderFill(orderId, fillPrice, symbol);
                        return { orderId, fillPrice, symbol, processed: true };
                    });

                    orderQueue.on('completed', (job, result) => {
                        // 완료 로그는 fillLimitOrder에서 상세하게 출력하므로 생략
                    });

                    orderQueue.on('failed', (job, err) => {
                        console.error(`[Queue] ❌ 작업 실패 (${job.id}):`, err.message);
                    });

                    // Pub/Sub 구독
                    redisSub.subscribe('futures:new_order', (err) => {
                        if (err) console.error('[Redis] ❌ 구독 실패:', err);
                        else console.log('[Redis] 📡 주문 채널 구독 완료');
                    });

                    redisSub.on('message', async (channel, message) => {
                        if (channel === 'futures:new_order') {
                            try {
                                const order = JSON.parse(message);
                                console.log(`[Redis] 📥 새 주문 수신: ${order.order_id.slice(-8)}`);
                                await addOrderToRedis(order);
                            } catch (e) {
                                console.error('[Redis] ❌ 메시지 파싱 실패:', e);
                            }
                        }
                    });

                    // 기존 미체결 주문 로드
                    await loadPendingOrders();

                    redisEnabled = true;
                    console.log('[Redis] 🚀 초기화 완료');
                    resolve(true);

                } catch (err) {
                    console.error('[Redis] ❌ 설정 오류:', err.message);
                    cleanupRedis();
                    resolve(false);
                }
            });

            redis.on('error', (err) => {
                clearTimeout(connectTimeout);
                // 에러 로그 한 번만 출력
                if (redisEnabled || redis) {
                    console.log('[Redis] ⚠️ 연결 실패 - DB 폴백 모드로 실행');
                }
                cleanupRedis();
                resolve(false);
            });

            // 수동 연결 시작
            redis.connect().catch(() => {});

        } catch (error) {
            console.log('[Redis] Init error, running without Redis');
            cleanupRedis();
            resolve(false);
        }
    });
}

// Redis 정리
function cleanupRedis() {
    redisEnabled = false;
    if (redis) {
        redis.disconnect();
        redis = null;
    }
    if (redisSub) {
        redisSub.disconnect();
        redisSub = null;
    }
    if (orderQueue) {
        orderQueue.close();
        orderQueue = null;
    }
}

// 서버 시작 시 DB의 미체결 주문을 Redis에 로드 (모든 심볼)
async function loadPendingOrders() {
    if (!redis || !dbPool) return;

    try {
        // 모든 지원 심볼의 미체결 주문 로드
        const [orders] = await dbPool.execute(`
            SELECT id, order_id, user_id, symbol, side, position_side, price, quantity, leverage, reduce_only
            FROM orders
            WHERE status = 'open' AND order_type = 'limit' AND symbol IN (${SUPPORTED_SYMBOLS.map(() => '?').join(',')})
            ORDER BY created_at ASC
        `, SUPPORTED_SYMBOLS);

        console.log(`[Redis] 📋 미체결 주문 ${orders.length}건 로드 중...`);

        // 심볼별 카운트
        const symbolCounts = {};
        for (const order of orders) {
            await addOrderToRedis(order);
            symbolCounts[order.symbol] = (symbolCounts[order.symbol] || 0) + 1;
        }

        console.log(`[Redis] ✅ 미체결 주문 ${orders.length}건 로드 완료`);
        for (const [symbol, count] of Object.entries(symbolCounts)) {
            console.log(`       └─ ${symbol}: ${count}건`);
        }
    } catch (error) {
        console.error('[Redis] ❌ 미체결 주문 로드 실패:', error.message);
    }
}

// ========================================
// 서버 시작
// ========================================
async function start() {
    try {
        // DB 연결
        dbPool = mysql.createPool(DB_CONFIG);
        console.log('[DB] ✅ 연결 풀 생성 완료');

        // Redis 초기화
        await initRedis();

        // 초기 캔들 데이터 로드 (Binance REST API)
        await fetchInitialKlines('BTCUSDT', '1m', 500);

        // 모든 심볼에 대해 스트림 구독
        console.log('[Binance] 📊 멀티 심볼 스트림 구독 시작...');
        for (const symbol of SUPPORTED_SYMBOLS) {
            const lowerSymbol = symbol.toLowerCase();
            // 티커 (24h 가격정보, 변동률)
            binanceWS.subscribe(`${lowerSymbol}@ticker`, handleTicker);
            // 마크가격 + 펀딩비율
            binanceWS.subscribe(`${lowerSymbol}@markPrice@1s`, handleMarkPrice);
            // 체결가 스트림 - 모든 심볼 통합 핸들러로 구독
            binanceWS.subscribe(`${lowerSymbol}@aggTrade`, handleAggTrade);
            // 오더북 스트림 - 모든 심볼 구독
            binanceWS.subscribe(`${lowerSymbol}@depth20@100ms`, handleDepth);
            // 캔들(kline) 스트림 - 1분봉
            binanceWS.subscribe(`${lowerSymbol}@kline_1m`, handleKline);
        }
        console.log(`[Binance] ✅ ${SUPPORTED_SYMBOLS.length}개 심볼 구독 완료 (ticker, markPrice, aggTrade, depth, kline)`);

        // HTTP 서버 시작
        server.listen(PORT, () => {
            console.log(`\n========================================`);
            console.log(`  Futures WebSocket Server`);
            console.log(`========================================`);
            console.log(`  Dashboard: http://localhost:${PORT}/`);
            console.log(`  Status API: http://localhost:${PORT}/api/status`);
            console.log(`  Broadcast API: POST http://localhost:${PORT}/api/broadcast`);
            console.log(`  Redis: ${redis ? 'Connected' : 'Disabled'}`);
            console.log(`  Queue: ${orderQueue ? 'Active' : 'Disabled'}`);
            console.log(`  Symbols: ${SUPPORTED_SYMBOLS.join(', ')}`);
            console.log(`========================================\n`);
        });

    } catch (error) {
        console.error('[Start] Error:', error);
        process.exit(1);
    }
}

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('\n[Server] Shutting down...');
    if (orderQueue) await orderQueue.close();
    if (redis) redis.disconnect();
    if (redisSub) redisSub.disconnect();
    process.exit(0);
});

start();
