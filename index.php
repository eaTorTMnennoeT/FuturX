<?php
/**
 * FuturX - 메인 페이지
 */
$pageTitle = '선물거래 플랫폼';
$pageCSS = ['index.css'];
require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 컨텐츠 -->
    <main class="main">
        <!-- 히어로 섹션 -->
        <section class="hero">
            <!-- 캔버스 배경 애니메이션 -->
            <canvas id="heroCanvas" class="hero__canvas"></canvas>

            <!-- 플로팅 코인 -->
            <div class="hero__floating">
                <div class="hero__floating-coin">₿</div>
                <div class="hero__floating-coin">Ξ</div>
                <div class="hero__floating-coin">◎</div>
                <div class="hero__floating-coin">B</div>
            </div>

            <div class="container">
                <h1 class="hero__title">
                    <span class="hero__title-main">
                        <span class="text-reveal">암호화폐 선물거래의</span>
                    </span>
                    <span class="hero__title-sub">
                        <span class="text-reveal text-reveal--delay">새로운 기준</span>
                    </span>
                </h1>
                <p class="hero__subtitle">
                    <span class="subtitle-line subtitle-line--1">최대 <strong class="highlight-num">125배</strong> 레버리지, 업계 최저 수수료 <strong class="highlight-num">0.02%</strong></span>
                    <span class="subtitle-line subtitle-line--2">BTC, ETH 및 <strong class="highlight-num">200개</strong> 이상의 암호화폐를 안전하게 거래하세요</span>
                </p>
                <div class="hero__cta">
                    <a href="trade.php" class="btn btn--primary btn--lg">거래 시작하기</a>
                    <a href="#markets" class="btn btn--secondary btn--lg">시세 확인</a>
                </div>

                <!-- 히어로 통계 -->
                <div class="hero__stats">
                    <div class="hero__stat">
                        <span class="hero__stat-value">$24.5B+</span>
                        <span class="hero__stat-label">24시간 거래량</span>
                    </div>
                    <div class="hero__stat">
                        <span class="hero__stat-value">200+</span>
                        <span class="hero__stat-label">거래 가능 종목</span>
                    </div>
                    <div class="hero__stat">
                        <span class="hero__stat-value">125x</span>
                        <span class="hero__stat-label">최대 레버리지</span>
                    </div>
                    <div class="hero__stat">
                        <span class="hero__stat-value">0.02%</span>
                        <span class="hero__stat-label">최저 수수료</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 마켓 개요 티커 -->
        <section class="market-overview">
            <div class="container">
                <div class="market-ticker" id="marketTicker">
                    <!-- 첫 번째 세트 -->
                    <div class="ticker-item" data-symbol="BTCUSDT">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #f7931a 0%, #ff9500 100%);">₿</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">BTC</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="ETHUSDT">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #627eea 0%, #8c9eff 100%);">Ξ</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">ETH</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="SOLUSDT">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #14f195 0%, #9945ff 100%);">◎</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">SOL</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="BNBUSDT">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #f3ba2f 0%, #f0b90b 100%);">B</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">BNB</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="XRPUSDT">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #23292f 0%, #2a3540 100%); color: #fff;">X</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">XRP</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="DOGEUSDT">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #c3a634 0%, #ba9f33 100%);">D</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">DOGE</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="ADAUSDT">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #0033ad 0%, #2a52be 100%); color: #fff;">A</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">ADA</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <!-- 복제 세트 (무한 스크롤용) -->
                    <div class="ticker-item" data-symbol="BTCUSDT" aria-hidden="true">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #f7931a 0%, #ff9500 100%);">₿</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">BTC</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="ETHUSDT" aria-hidden="true">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #627eea 0%, #8c9eff 100%);">Ξ</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">ETH</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="SOLUSDT" aria-hidden="true">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #14f195 0%, #9945ff 100%);">◎</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">SOL</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="BNBUSDT" aria-hidden="true">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #f3ba2f 0%, #f0b90b 100%);">B</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">BNB</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="XRPUSDT" aria-hidden="true">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #23292f 0%, #2a3540 100%); color: #fff;">X</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">XRP</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="DOGEUSDT" aria-hidden="true">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #c3a634 0%, #ba9f33 100%);">D</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">DOGE</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                    <div class="ticker-item" data-symbol="ADAUSDT" aria-hidden="true">
                        <div class="ticker-item__icon" style="background: linear-gradient(135deg, #0033ad 0%, #2a52be 100%); color: #fff;">A</div>
                        <div class="ticker-item__info">
                            <div class="ticker-item__symbol">ADA</div>
                            <div class="ticker-item__pair">USDT 무기한</div>
                        </div>
                        <div class="ticker-item__price">
                            <div class="ticker-item__value" data-price>--</div>
                            <div class="ticker-item__change" data-change>--%</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 시세 테이블 -->
        <section class="price-table" id="markets">
            <div class="container">
                <div class="price-table__header">
                    <div class="price-table__title-section">
                        <h2 class="price-table__title">USDT 무기한 선물</h2>
                        <span class="price-table__badge">PERPETUAL</span>
                    </div>
                    <div class="price-table__search">
                        <svg class="price-table__search-icon icon--sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" placeholder="코인 검색...">
                    </div>
                </div>

                <table class="market-table" id="marketTable">
                    <thead>
                        <tr>
                            <th>거래쌍</th>
                            <th>최근가</th>
                            <th>24h 변동</th>
                            <th>24h 최고</th>
                            <th>24h 최저</th>
                            <th>24h 거래량</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="marketTableBody">
                        <!-- 실시간 데이터로 채워짐 -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 기능 섹션 -->
        <section class="features">
            <div class="container">
                <h2 class="features__title">왜 FuturX인가?</h2>
                <div class="features__grid">
                    <div class="feature-card">
                        <div class="feature-card__icon">⚡</div>
                        <h3 class="feature-card__title">초저지연 체결</h3>
                        <p class="feature-card__desc">
                            매칭 엔진 응답속도 5ms 이하. 고빈도 트레이딩에 최적화된 인프라
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-card__icon">🔒</div>
                        <h3 class="feature-card__title">보안 최우선</h3>
                        <p class="feature-card__desc">
                            콜드월렛 95% 보관, 다중 서명, 실시간 리스크 모니터링 시스템
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-card__icon">💰</div>
                        <h3 class="feature-card__title">업계 최저 수수료</h3>
                        <p class="feature-card__desc">
                            메이커 0.02%, 테이커 0.05%. VIP 등급별 추가 할인 적용
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-card__icon">📈</div>
                        <h3 class="feature-card__title">최대 125배 레버리지</h3>
                        <p class="feature-card__desc">
                            크로스/격리 마진 선택. 리스크 관리 도구 제공
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-card__icon">🌐</div>
                        <h3 class="feature-card__title">200+ 거래쌍</h3>
                        <p class="feature-card__desc">
                            주요 코인부터 알트코인까지 다양한 선물 상품 지원
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-card__icon">📱</div>
                        <h3 class="feature-card__title">어디서나 거래</h3>
                        <p class="feature-card__desc">
                            웹, iOS, Android, API 지원. 언제 어디서나 거래 가능
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3D 글로벌 트레이딩 섹션 -->
        <section class="global-trading">
            <div class="global-trading__bg">
                <div class="particles" id="particles"></div>
                <div class="grid-floor"></div>
            </div>
            <div class="global-trading__content">
                <div class="global-trading__text">
                    <h2 class="global-trading__title">
                        <span class="gradient-text">Global Trading</span>
                        <br>Infrastructure
                    </h2>
                    <p class="global-trading__desc">
                        전 세계 180개국, 2천만 트레이더가 신뢰하는<br>
                        차세대 글로벌 거래 인프라
                    </p>
                    <div class="global-trading__stats">
                        <div class="gstat">
                            <span class="gstat__value" data-count="24.5">0</span>
                            <span class="gstat__unit">B+</span>
                            <span class="gstat__label">24h 거래량</span>
                        </div>
                        <div class="gstat">
                            <span class="gstat__value" data-count="12">0</span>
                            <span class="gstat__unit">M+</span>
                            <span class="gstat__label">글로벌 유저</span>
                        </div>
                        <div class="gstat">
                            <span class="gstat__value" data-count="99.99">0</span>
                            <span class="gstat__unit">%</span>
                            <span class="gstat__label">가동률</span>
                        </div>
                    </div>
                </div>
                <div class="global-trading__visual">
                    <div class="gyroscope" id="gyroscope">
                        <div class="gyroscope__ring gyroscope__ring--outer">
                            <div class="ring-glow"></div>
                        </div>
                        <div class="gyroscope__ring gyroscope__ring--middle">
                            <div class="ring-glow"></div>
                        </div>
                        <div class="gyroscope__ring gyroscope__ring--inner">
                            <div class="ring-glow"></div>
                        </div>
                        <div class="gyroscope__core">
                            <div class="core-glow"></div>
                            <span class="core-icon">F</span>
                        </div>
                        <!-- 오비탈 파티클 -->
                        <div class="orbital orbital--1"><span></span></div>
                        <div class="orbital orbital--2"><span></span></div>
                        <div class="orbital orbital--3"><span></span></div>
                        <div class="orbital orbital--4"><span></span></div>
                        <div class="orbital orbital--5"><span></span></div>
                        <div class="orbital orbital--6"><span></span></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 미니 푸터 -->
        <footer class="mini-footer">
            <div class="container">
                <div class="mini-footer__inner">
                    <span>© 2024 FuturX</span>
                    <div class="mini-footer__links">
                        <a href="#">이용약관</a>
                        <a href="#">개인정보처리방침</a>
                        <a href="#">고객센터</a>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <script>
    // ========================================
    // Binance WebSocket 실시간 시세
    // ========================================
    const COINS_CONFIG = {
        'BTCUSDT': { icon: '₿', color: '#f7931a', textColor: 'white' },
        'ETHUSDT': { icon: 'Ξ', color: '#627eea', textColor: 'white' },
        'SOLUSDT': { icon: '◎', color: '#14f195', textColor: '#111' },
        'BNBUSDT': { icon: 'B', color: '#f3ba2f', textColor: '#111' },
        'XRPUSDT': { icon: 'X', color: '#2775ca', textColor: 'white' },
        'AVAXUSDT': { icon: 'A', color: '#e84142', textColor: 'white' },
        'DOGEUSDT': { icon: 'D', color: '#c3a634', textColor: 'white' },
        'ADAUSDT': { icon: 'A', color: '#0033ad', textColor: 'white' }
    };

    const priceData = {};
    let ws = null;
    let wsConnected = false;
    let restFallbackInterval = null;

    // REST API로 초기 데이터 로드 (폴백 및 초기 로딩용)
    async function fetchPricesREST() {
        try {
            const symbols = Object.keys(COINS_CONFIG);
            const response = await fetch(`https://api.binance.com/api/v3/ticker/24hr?symbols=${JSON.stringify(symbols)}`);
            const data = await response.json();

            data.forEach(item => {
                const symbol = item.symbol;
                if (COINS_CONFIG[symbol]) {
                    priceData[symbol] = {
                        price: parseFloat(item.lastPrice),
                        change: parseFloat(item.priceChangePercent),
                        high: parseFloat(item.highPrice),
                        low: parseFloat(item.lowPrice),
                        volume: parseFloat(item.quoteVolume),
                        prevPrice: priceData[symbol]?.price || parseFloat(item.lastPrice)
                    };
                    updateTickerUI(symbol);
                    updateTableUI(symbol);
                }
            });

            console.log('REST API로 시세 로드 완료');
            return true;
        } catch (error) {
            console.error('REST API 에러:', error);
            return false;
        }
    }

    // WebSocket 초기화
    function initWebSocket() {
        // WebSocket 미지원 브라우저 체크
        if (!window.WebSocket) {
            console.log('WebSocket 미지원, REST API 폴백 사용');
            startRESTFallback();
            return;
        }

        const symbols = Object.keys(COINS_CONFIG).map(s => s.toLowerCase() + '@ticker');
        const streamUrl = `wss://stream.binance.com:9443/stream?streams=${symbols.join('/')}`;

        try {
            ws = new WebSocket(streamUrl);
        } catch (e) {
            console.log('WebSocket 생성 실패, REST API 폴백 사용');
            startRESTFallback();
            return;
        }

        // 연결 타임아웃 (5초 내 연결 안되면 REST 폴백)
        const connectionTimeout = setTimeout(() => {
            if (!wsConnected) {
                console.log('WebSocket 연결 타임아웃, REST API 폴백 사용');
                ws.close();
                startRESTFallback();
            }
        }, 5000);

        ws.onopen = () => {
            clearTimeout(connectionTimeout);
            wsConnected = true;
            console.log('Binance WebSocket 연결됨');

            // REST 폴백 중지
            if (restFallbackInterval) {
                clearInterval(restFallbackInterval);
                restFallbackInterval = null;
            }

            document.querySelectorAll('[data-price]').forEach(el => {
                el.classList.add('ws-connected');
            });
        };

        ws.onmessage = (event) => {
            const msg = JSON.parse(event.data);
            if (msg.data) {
                const data = msg.data;
                const symbol = data.s;

                priceData[symbol] = {
                    price: parseFloat(data.c),
                    change: parseFloat(data.P),
                    high: parseFloat(data.h),
                    low: parseFloat(data.l),
                    volume: parseFloat(data.q),
                    prevPrice: priceData[symbol]?.price || parseFloat(data.c)
                };

                updateTickerUI(symbol);
                updateTableUI(symbol);
            }
        };

        ws.onerror = (error) => {
            console.error('WebSocket 에러:', error);
        };

        ws.onclose = () => {
            wsConnected = false;
            document.querySelectorAll('[data-price]').forEach(el => {
                el.classList.remove('ws-connected');
            });

            console.log('WebSocket 연결 끊김, 3초 후 재연결 시도...');

            // REST 폴백 시작
            if (!restFallbackInterval) {
                startRESTFallback();
            }

            // 재연결 시도
            setTimeout(initWebSocket, 3000);
        };
    }

    // REST API 폴백 (주기적 폴링)
    function startRESTFallback() {
        if (restFallbackInterval) return;

        console.log('REST API 폴백 모드 시작 (5초 간격)');
        fetchPricesREST(); // 즉시 한번 실행

        restFallbackInterval = setInterval(() => {
            if (!wsConnected) {
                fetchPricesREST();
            }
        }, 5000);
    }

    // 티커 UI 업데이트
    function updateTickerUI(symbol) {
        const ticker = document.querySelector(`.ticker-item[data-symbol="${symbol}"]`);
        if (!ticker) return;

        const data = priceData[symbol];
        const priceEl = ticker.querySelector('[data-price]');
        const changeEl = ticker.querySelector('[data-change]');

        // 가격 표시
        const formattedPrice = formatPrice(data.price);
        priceEl.textContent = `$${formattedPrice}`;

        // 가격 변동 애니메이션
        if (data.price > data.prevPrice) {
            priceEl.classList.remove('flash-down');
            priceEl.classList.add('flash-up');
        } else if (data.price < data.prevPrice) {
            priceEl.classList.remove('flash-up');
            priceEl.classList.add('flash-down');
        }
        setTimeout(() => priceEl.classList.remove('flash-up', 'flash-down'), 300);

        // 변동률 표시
        const changeSign = data.change >= 0 ? '+' : '';
        changeEl.textContent = `${changeSign}${data.change.toFixed(2)}%`;
        changeEl.className = 'ticker-item__change ' + (data.change >= 0 ? 'num-positive' : 'num-negative');
    }

    // 테이블 UI 업데이트
    function updateTableUI(symbol) {
        const row = document.querySelector(`#marketTableBody tr[data-symbol="${symbol}"]`);
        if (!row) return;

        const data = priceData[symbol];
        const cells = row.querySelectorAll('td');

        // 최근가
        const priceCell = cells[1];
        priceCell.textContent = formatPrice(data.price);

        // 가격 변동 애니메이션
        if (data.price > data.prevPrice) {
            priceCell.classList.remove('flash-down');
            priceCell.classList.add('flash-up');
        } else if (data.price < data.prevPrice) {
            priceCell.classList.remove('flash-up');
            priceCell.classList.add('flash-down');
        }
        setTimeout(() => priceCell.classList.remove('flash-up', 'flash-down'), 300);

        // 24h 변동
        const changeSign = data.change >= 0 ? '+' : '';
        cells[2].textContent = `${changeSign}${data.change.toFixed(2)}%`;
        cells[2].className = 'market-table__change ' + (data.change >= 0 ? 'num-positive' : 'num-negative');

        // 24h 최고/최저
        cells[3].textContent = formatPrice(data.high);
        cells[4].textContent = formatPrice(data.low);

        // 24h 거래량
        cells[5].textContent = formatVolume(data.volume);
    }

    // 테이블 초기 렌더링
    function renderMarketTable() {
        const tbody = document.getElementById('marketTableBody');
        if (!tbody) return;

        tbody.innerHTML = Object.entries(COINS_CONFIG).map(([symbol, config]) => `
            <tr data-symbol="${symbol}">
                <td>
                    <div class="market-table__pair">
                        <div class="market-table__coin-icon" style="background: ${config.color}; color: ${config.textColor};">${config.icon}</div>
                        <div>
                            <div class="market-table__coin-name">${symbol}</div>
                            <div class="market-table__coin-base">무기한</div>
                        </div>
                    </div>
                </td>
                <td class="market-table__price">--</td>
                <td class="market-table__change">--%</td>
                <td class="market-table__price">--</td>
                <td class="market-table__price">--</td>
                <td class="market-table__volume">--</td>
                <td class="market-table__action">
                    <a href="trade.php?symbol=${symbol}" class="btn btn--primary btn--sm">거래</a>
                </td>
            </tr>
        `).join('');
    }

    // 가격 포맷
    function formatPrice(price) {
        if (price >= 1000) {
            return price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else if (price >= 1) {
            return price.toFixed(4);
        } else {
            return price.toFixed(6);
        }
    }

    // 거래량 포맷
    function formatVolume(volume) {
        if (volume >= 1e9) {
            return (volume / 1e9).toFixed(2) + 'B USDT';
        } else if (volume >= 1e6) {
            return (volume / 1e6).toFixed(2) + 'M USDT';
        } else if (volume >= 1e3) {
            return (volume / 1e3).toFixed(2) + 'K USDT';
        }
        return volume.toFixed(2) + ' USDT';
    }

    // 히어로 캔버스 애니메이션 (파티클 + 연결선 + 빛줄기)
    function initHeroCanvas() {
        const canvas = document.getElementById('heroCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        let width, height;
        let particles = [];
        let lightBeams = [];
        let mouse = { x: null, y: null };

        function resize() {
            width = canvas.width = canvas.offsetWidth;
            height = canvas.height = canvas.offsetHeight;
        }

        resize();
        window.addEventListener('resize', resize);

        // 마우스 추적
        canvas.parentElement.addEventListener('mousemove', (e) => {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });

        canvas.parentElement.addEventListener('mouseleave', () => {
            mouse.x = null;
            mouse.y = null;
        });

        // 파티클 클래스
        class Particle {
            constructor() {
                this.reset();
            }

            reset() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.size = Math.random() * 2 + 1;
                this.speedX = (Math.random() - 0.5) * 0.5;
                this.speedY = (Math.random() - 0.5) * 0.5;
                this.opacity = Math.random() * 0.5 + 0.2;
                this.color = Math.random() > 0.7 ? '#f0b90b' : '#ffffff';
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                // 마우스 반응
                if (mouse.x && mouse.y) {
                    const dx = mouse.x - this.x;
                    const dy = mouse.y - this.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 150) {
                        const force = (150 - dist) / 150;
                        this.x -= dx * force * 0.02;
                        this.y -= dy * force * 0.02;
                    }
                }

                // 경계 처리
                if (this.x < 0 || this.x > width) this.speedX *= -1;
                if (this.y < 0 || this.y > height) this.speedY *= -1;
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.globalAlpha = this.opacity;
                ctx.fill();
                ctx.globalAlpha = 1;
            }
        }

        // 빛줄기 클래스
        class LightBeam {
            constructor() {
                this.reset();
            }

            reset() {
                this.x = Math.random() * width;
                this.y = -100;
                this.length = Math.random() * 150 + 100;
                this.speed = Math.random() * 3 + 2;
                this.opacity = Math.random() * 0.3 + 0.1;
                this.width = Math.random() * 2 + 1;
            }

            update() {
                this.y += this.speed;
                if (this.y > height + this.length) {
                    this.reset();
                }
            }

            draw() {
                const gradient = ctx.createLinearGradient(this.x, this.y, this.x, this.y - this.length);
                gradient.addColorStop(0, `rgba(240, 185, 11, ${this.opacity})`);
                gradient.addColorStop(1, 'rgba(240, 185, 11, 0)');

                ctx.beginPath();
                ctx.moveTo(this.x, this.y);
                ctx.lineTo(this.x, this.y - this.length);
                ctx.strokeStyle = gradient;
                ctx.lineWidth = this.width;
                ctx.stroke();
            }
        }

        // 초기화
        for (let i = 0; i < 80; i++) {
            particles.push(new Particle());
        }

        for (let i = 0; i < 15; i++) {
            const beam = new LightBeam();
            beam.y = Math.random() * height; // 시작 위치 랜덤
            lightBeams.push(beam);
        }

        // 연결선 그리기
        function drawConnections() {
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < 120) {
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.strokeStyle = `rgba(240, 185, 11, ${0.15 * (1 - dist / 120)})`;
                        ctx.lineWidth = 0.5;
                        ctx.stroke();
                    }
                }
            }
        }

        // 애니메이션 루프
        function animate() {
            ctx.clearRect(0, 0, width, height);

            // 빛줄기
            lightBeams.forEach(beam => {
                beam.update();
                beam.draw();
            });

            // 파티클
            particles.forEach(p => {
                p.update();
                p.draw();
            });

            // 연결선
            drawConnections();

            // 마우스 글로우
            if (mouse.x && mouse.y) {
                const gradient = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 150);
                gradient.addColorStop(0, 'rgba(240, 185, 11, 0.15)');
                gradient.addColorStop(1, 'rgba(240, 185, 11, 0)');
                ctx.beginPath();
                ctx.arc(mouse.x, mouse.y, 150, 0, Math.PI * 2);
                ctx.fillStyle = gradient;
                ctx.fill();
            }

            requestAnimationFrame(animate);
        }

        animate();
    }

    // 숫자 카운트업 애니메이션
    function animateCountUp() {
        const counters = document.querySelectorAll('.gstat__value[data-count]');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseFloat(entry.target.dataset.count);
                    const duration = 2000;
                    const startTime = performance.now();

                    function updateCount(currentTime) {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);

                        // Easing function
                        const easeOut = 1 - Math.pow(1 - progress, 3);
                        const current = target * easeOut;

                        // Format number
                        if (target % 1 !== 0) {
                            entry.target.textContent = current.toFixed(2);
                        } else {
                            entry.target.textContent = Math.floor(current);
                        }

                        if (progress < 1) {
                            requestAnimationFrame(updateCount);
                        } else {
                            entry.target.textContent = target % 1 !== 0 ? target.toFixed(2) : target;
                        }
                    }

                    requestAnimationFrame(updateCount);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    }

    // 파티클 생성
    function createParticles() {
        const container = document.getElementById('particles');
        if (!container) return;

        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.cssText = `
                position: absolute;
                width: ${Math.random() * 3 + 1}px;
                height: ${Math.random() * 3 + 1}px;
                background: rgba(240, 185, 11, ${Math.random() * 0.5 + 0.2});
                border-radius: 50%;
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                animation: particleFloat ${Math.random() * 10 + 10}s linear infinite;
                animation-delay: -${Math.random() * 10}s;
            `;
            container.appendChild(particle);
        }
    }

    // 마우스 인터랙션
    function initGyroscopeInteraction() {
        const gyroscope = document.getElementById('gyroscope');
        if (!gyroscope) return;

        let mouseX = 0, mouseY = 0;
        let currentX = 0, currentY = 0;

        document.addEventListener('mousemove', (e) => {
            const rect = gyroscope.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;

            mouseX = (e.clientX - centerX) / rect.width * 20;
            mouseY = (e.clientY - centerY) / rect.height * 20;
        });

        function animate() {
            currentX += (mouseX - currentX) * 0.05;
            currentY += (mouseY - currentY) * 0.05;

            gyroscope.style.transform = `rotateX(${-currentY}deg) rotateY(${currentX}deg)`;
            requestAnimationFrame(animate);
        }

        // animate(); // 마우스 인터랙션 활성화 시 주석 해제
    }

    // 파티클 CSS 애니메이션 추가
    const style = document.createElement('style');
    style.textContent = `
        @keyframes particleFloat {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) translateX(${Math.random() * 100 - 50}px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // 초기화
    document.addEventListener('DOMContentLoaded', () => {
        // 실시간 시세
        renderMarketTable();
        initWebSocket();

        // UI 애니메이션
        initHeroCanvas();
        animateCountUp();
        createParticles();
        initGyroscopeInteraction();

        // 티커 클릭 시 거래 페이지로 이동
        document.querySelectorAll('.ticker-item').forEach(item => {
            item.addEventListener('click', () => {
                const symbol = item.dataset.symbol;
                if (symbol) {
                    window.location.href = `trade.php?symbol=${symbol}`;
                }
            });
        });

        // 테이블 행 클릭 시 거래 페이지로 이동 (이벤트 위임)
        const marketTable = document.getElementById('marketTableBody');
        if (marketTable) {
            marketTable.addEventListener('click', (e) => {
                // 거래 버튼 클릭은 제외 (이미 링크가 있음)
                if (e.target.closest('.market-table__action')) return;
                const row = e.target.closest('tr');
                if (row && row.dataset.symbol) {
                    window.location.href = `trade.php?symbol=${row.dataset.symbol}`;
                }
            });
        }
    });

    // 페이지 벗어날 때 WebSocket 정리
    window.addEventListener('beforeunload', () => {
        if (ws) ws.close();
    });
    </script>
</body>
</html>
