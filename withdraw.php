<?php
/**
 * 출금 페이지
 * Binance/Bybit 스타일 암호화폐 출금
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 로그인 체크
// requireLogin();

$csrf_token = generateCSRFToken();

// 데모 지갑 데이터
$wallets = [
    'USDT' => ['balance' => 12500.50, 'available' => 10250.00, 'locked' => 2250.50],
    'BTC' => ['balance' => 0.5234, 'available' => 0.5234, 'locked' => 0],
    'ETH' => ['balance' => 3.2145, 'available' => 2.5000, 'locked' => 0.7145],
    'SOL' => ['balance' => 45.123, 'available' => 45.123, 'locked' => 0],
];

// 네트워크 옵션
$networks = [
    'USDT' => [
        ['name' => 'TRC20', 'fee' => 1, 'min' => 10, 'confirmations' => 19],
        ['name' => 'ERC20', 'fee' => 10, 'min' => 20, 'confirmations' => 12],
        ['name' => 'BEP20', 'fee' => 0.5, 'min' => 10, 'confirmations' => 15],
    ],
    'BTC' => [
        ['name' => 'Bitcoin', 'fee' => 0.0001, 'min' => 0.001, 'confirmations' => 2],
        ['name' => 'Lightning', 'fee' => 0, 'min' => 0.00001, 'confirmations' => 0],
    ],
    'ETH' => [
        ['name' => 'ERC20', 'fee' => 0.005, 'min' => 0.01, 'confirmations' => 12],
        ['name' => 'Arbitrum', 'fee' => 0.0001, 'min' => 0.001, 'confirmations' => 1],
    ],
    'SOL' => [
        ['name' => 'Solana', 'fee' => 0.01, 'min' => 0.1, 'confirmations' => 1],
    ],
];

$pageTitle = '출금';
$pageCSS = ['withdraw.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 콘텐츠 -->
    <main class="main">
        <div class="withdraw-container">
            <!-- 왼쪽: 출금 폼 -->
            <div class="withdraw-form-section">
                <div class="withdraw-header">
                    <h1 class="withdraw-title">암호화폐 출금</h1>
                    <div class="withdraw-tabs">
                        <a href="deposit.php" class="withdraw-tab">입금</a>
                        <a href="withdraw.php" class="withdraw-tab active">출금</a>
                        <a href="transfer.php" class="withdraw-tab">전송</a>
                    </div>
                </div>

                <form id="withdrawForm" class="withdraw-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <!-- 코인 선택 -->
                    <div class="withdraw-form-group">
                        <label class="withdraw-label">코인 선택</label>
                        <div class="withdraw-coin-select" id="coinSelect">
                            <div class="withdraw-coin-selected" id="selectedCoin">
                                <img src="https://cryptologos.cc/logos/tether-usdt-logo.png" alt="USDT" class="withdraw-coin-icon">
                                <div class="withdraw-coin-info">
                                    <span class="withdraw-coin-name">USDT</span>
                                    <span class="withdraw-coin-fullname">Tether</span>
                                </div>
                                <svg class="withdraw-coin-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </div>
                            <div class="withdraw-coin-dropdown" id="coinDropdown" style="display: none;">
                                <input type="text" class="withdraw-coin-search" placeholder="코인 검색...">
                                <div class="withdraw-coin-list">
                                    <div class="withdraw-coin-item selected" data-coin="USDT">
                                        <img src="https://cryptologos.cc/logos/tether-usdt-logo.png" alt="USDT">
                                        <div class="withdraw-coin-item__info">
                                            <span class="withdraw-coin-item__name">USDT</span>
                                            <span class="withdraw-coin-item__fullname">Tether</span>
                                        </div>
                                        <span class="withdraw-coin-item__balance">12,500.50</span>
                                    </div>
                                    <div class="withdraw-coin-item" data-coin="BTC">
                                        <img src="https://cryptologos.cc/logos/bitcoin-btc-logo.png" alt="BTC">
                                        <div class="withdraw-coin-item__info">
                                            <span class="withdraw-coin-item__name">BTC</span>
                                            <span class="withdraw-coin-item__fullname">Bitcoin</span>
                                        </div>
                                        <span class="withdraw-coin-item__balance">0.5234</span>
                                    </div>
                                    <div class="withdraw-coin-item" data-coin="ETH">
                                        <img src="https://cryptologos.cc/logos/ethereum-eth-logo.png" alt="ETH">
                                        <div class="withdraw-coin-item__info">
                                            <span class="withdraw-coin-item__name">ETH</span>
                                            <span class="withdraw-coin-item__fullname">Ethereum</span>
                                        </div>
                                        <span class="withdraw-coin-item__balance">3.2145</span>
                                    </div>
                                    <div class="withdraw-coin-item" data-coin="SOL">
                                        <img src="https://cryptologos.cc/logos/solana-sol-logo.png" alt="SOL">
                                        <div class="withdraw-coin-item__info">
                                            <span class="withdraw-coin-item__name">SOL</span>
                                            <span class="withdraw-coin-item__fullname">Solana</span>
                                        </div>
                                        <span class="withdraw-coin-item__balance">45.123</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 출금 주소 -->
                    <div class="withdraw-form-group">
                        <div class="withdraw-label-row">
                            <label class="withdraw-label">출금 주소</label>
                            <button type="button" class="withdraw-address-btn" id="addressBookBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                </svg>
                                주소록
                            </button>
                        </div>
                        <div class="withdraw-address-input">
                            <input type="text" class="withdraw-input" id="withdrawAddress" name="address"
                                placeholder="출금 주소를 입력하세요" required>
                            <button type="button" class="withdraw-paste-btn" id="pasteBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </div>
                        <span class="withdraw-error" id="addressError"></span>
                    </div>

                    <!-- 네트워크 선택 -->
                    <div class="withdraw-form-group">
                        <label class="withdraw-label">네트워크</label>
                        <div class="withdraw-network-list" id="networkList">
                            <label class="withdraw-network-item selected">
                                <input type="radio" name="network" value="TRC20" checked>
                                <div class="withdraw-network-content">
                                    <span class="withdraw-network-name">TRC20</span>
                                    <span class="withdraw-network-fee">수수료: 1 USDT</span>
                                </div>
                                <svg class="withdraw-network-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </label>
                            <label class="withdraw-network-item">
                                <input type="radio" name="network" value="ERC20">
                                <div class="withdraw-network-content">
                                    <span class="withdraw-network-name">ERC20</span>
                                    <span class="withdraw-network-fee">수수료: 10 USDT</span>
                                </div>
                                <svg class="withdraw-network-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </label>
                            <label class="withdraw-network-item">
                                <input type="radio" name="network" value="BEP20">
                                <div class="withdraw-network-content">
                                    <span class="withdraw-network-name">BEP20</span>
                                    <span class="withdraw-network-fee">수수료: 0.5 USDT</span>
                                </div>
                                <svg class="withdraw-network-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </label>
                        </div>
                        <div class="withdraw-network-warning">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            <span>올바른 네트워크를 선택했는지 확인하세요. 잘못된 네트워크로 전송 시 자산을 잃을 수 있습니다.</span>
                        </div>
                    </div>

                    <!-- 출금 수량 -->
                    <div class="withdraw-form-group">
                        <div class="withdraw-label-row">
                            <label class="withdraw-label">출금 수량</label>
                            <span class="withdraw-available">출금 가능: <strong id="availableBalance">10,250.00</strong> USDT</span>
                        </div>
                        <div class="withdraw-amount-input">
                            <input type="number" class="withdraw-input withdraw-input--amount" id="withdrawAmount" name="amount"
                                placeholder="0.00" step="0.00000001" required>
                            <div class="withdraw-amount-actions">
                                <button type="button" class="withdraw-percent-btn" data-percent="25">25%</button>
                                <button type="button" class="withdraw-percent-btn" data-percent="50">50%</button>
                                <button type="button" class="withdraw-percent-btn" data-percent="75">75%</button>
                                <button type="button" class="withdraw-percent-btn" data-percent="100">MAX</button>
                            </div>
                        </div>
                        <span class="withdraw-error" id="amountError"></span>
                    </div>

                    <!-- 출금 요약 -->
                    <div class="withdraw-summary">
                        <div class="withdraw-summary-row">
                            <span>출금 수량</span>
                            <span id="summaryAmount">0.00 USDT</span>
                        </div>
                        <div class="withdraw-summary-row">
                            <span>네트워크 수수료</span>
                            <span id="summaryFee">1 USDT</span>
                        </div>
                        <div class="withdraw-summary-row withdraw-summary-row--total">
                            <span>실수령액</span>
                            <span id="summaryTotal">0.00 USDT</span>
                        </div>
                    </div>

                    <!-- 출금 버튼 -->
                    <button type="submit" class="withdraw-submit" id="withdrawBtn">
                        <span class="withdraw-submit__text">출금 신청</span>
                        <div class="withdraw-submit__loading">
                            <div class="spinner"></div>
                        </div>
                    </button>
                </form>
            </div>

            <!-- 오른쪽: 정보 영역 -->
            <div class="withdraw-info-section">
                <!-- 잔액 카드 -->
                <div class="withdraw-balance-card">
                    <div class="withdraw-balance-header">
                        <img src="https://cryptologos.cc/logos/tether-usdt-logo.png" alt="USDT" class="withdraw-balance-icon">
                        <div class="withdraw-balance-coin">
                            <span class="withdraw-balance-name">USDT</span>
                            <span class="withdraw-balance-fullname">Tether</span>
                        </div>
                    </div>
                    <div class="withdraw-balance-stats">
                        <div class="withdraw-balance-stat">
                            <span class="withdraw-balance-label">총 잔액</span>
                            <span class="withdraw-balance-value" id="totalBalance">12,500.50</span>
                        </div>
                        <div class="withdraw-balance-stat">
                            <span class="withdraw-balance-label">출금 가능</span>
                            <span class="withdraw-balance-value withdraw-balance-value--highlight" id="availableBalanceCard">10,250.00</span>
                        </div>
                        <div class="withdraw-balance-stat">
                            <span class="withdraw-balance-label">거래 중</span>
                            <span class="withdraw-balance-value" id="lockedBalance">2,250.50</span>
                        </div>
                    </div>
                </div>

                <!-- 출금 유의사항 -->
                <div class="withdraw-notice">
                    <h3 class="withdraw-notice-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        출금 유의사항
                    </h3>
                    <ul class="withdraw-notice-list">
                        <li>출금 주소를 정확히 확인하세요. 잘못된 주소로 전송된 자산은 복구가 불가능합니다.</li>
                        <li>올바른 네트워크를 선택하세요. 네트워크가 일치하지 않으면 자산을 잃을 수 있습니다.</li>
                        <li>최소 출금 수량: <strong>10 USDT</strong></li>
                        <li>일일 출금 한도: <strong>100,000 USDT</strong> (KYC 레벨에 따라 상이)</li>
                        <li>출금 처리 시간: 일반적으로 30분 이내</li>
                    </ul>
                </div>

                <!-- 최근 출금 내역 -->
                <div class="withdraw-recent">
                    <h3 class="withdraw-recent-title">최근 출금 내역</h3>
                    <div class="withdraw-recent-list">
                        <div class="withdraw-recent-item">
                            <div class="withdraw-recent-item__icon">
                                <img src="https://cryptologos.cc/logos/tether-usdt-logo.png" alt="USDT">
                            </div>
                            <div class="withdraw-recent-item__info">
                                <span class="withdraw-recent-item__amount">-500 USDT</span>
                                <span class="withdraw-recent-item__address">TRC20 · TXyz...abc</span>
                            </div>
                            <div class="withdraw-recent-item__status">
                                <span class="withdraw-status withdraw-status--completed">완료</span>
                                <span class="withdraw-recent-item__time">2024-01-12 14:30</span>
                            </div>
                        </div>
                        <div class="withdraw-recent-item">
                            <div class="withdraw-recent-item__icon">
                                <img src="https://cryptologos.cc/logos/bitcoin-btc-logo.png" alt="BTC">
                            </div>
                            <div class="withdraw-recent-item__info">
                                <span class="withdraw-recent-item__amount">-0.05 BTC</span>
                                <span class="withdraw-recent-item__address">Bitcoin · bc1q...xyz</span>
                            </div>
                            <div class="withdraw-recent-item__status">
                                <span class="withdraw-status withdraw-status--pending">처리중</span>
                                <span class="withdraw-recent-item__time">2024-01-12 12:15</span>
                            </div>
                        </div>
                        <div class="withdraw-recent-item">
                            <div class="withdraw-recent-item__icon">
                                <img src="https://cryptologos.cc/logos/ethereum-eth-logo.png" alt="ETH">
                            </div>
                            <div class="withdraw-recent-item__info">
                                <span class="withdraw-recent-item__amount">-1.5 ETH</span>
                                <span class="withdraw-recent-item__address">ERC20 · 0x1a2...def</span>
                            </div>
                            <div class="withdraw-recent-item__status">
                                <span class="withdraw-status withdraw-status--completed">완료</span>
                                <span class="withdraw-recent-item__time">2024-01-11 09:45</span>
                            </div>
                        </div>
                    </div>
                    <a href="history.php?type=withdraw" class="withdraw-recent-link">전체 내역 보기</a>
                </div>
            </div>
        </div>
    </main>

    <!-- 2FA 인증 모달 -->
    <div class="withdraw-modal" id="twoFAModal" style="display: none;">
        <div class="withdraw-modal__backdrop"></div>
        <div class="withdraw-modal__content">
            <button type="button" class="withdraw-modal__close" id="closeTwoFAModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>

            <div class="withdraw-modal__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>

            <h3 class="withdraw-modal__title">2단계 인증</h3>
            <p class="withdraw-modal__desc">보안을 위해 인증 코드를 입력해주세요</p>

            <form id="twoFAForm">
                <!-- 2FA 타입 탭 -->
                <div class="withdraw-2fa-tabs">
                    <button type="button" class="withdraw-2fa-tab active" data-type="authenticator">
                        인증 앱
                    </button>
                    <button type="button" class="withdraw-2fa-tab" data-type="email">
                        이메일
                    </button>
                </div>

                <div class="withdraw-2fa-content" id="authenticatorContent">
                    <p class="withdraw-2fa-hint">Google Authenticator 앱의 6자리 코드를 입력하세요</p>
                </div>

                <div class="withdraw-2fa-content" id="emailContent" style="display: none;">
                    <p class="withdraw-2fa-hint">u***@example.com으로 전송된 코드를 입력하세요</p>
                    <button type="button" class="withdraw-resend-btn" id="resendEmail">
                        코드 재전송 <span id="emailTimer"></span>
                    </button>
                </div>

                <div class="withdraw-code-inputs" id="codeInputs">
                    <input type="text" maxlength="1" class="withdraw-code-input" data-index="0" inputmode="numeric">
                    <input type="text" maxlength="1" class="withdraw-code-input" data-index="1" inputmode="numeric">
                    <input type="text" maxlength="1" class="withdraw-code-input" data-index="2" inputmode="numeric">
                    <input type="text" maxlength="1" class="withdraw-code-input" data-index="3" inputmode="numeric">
                    <input type="text" maxlength="1" class="withdraw-code-input" data-index="4" inputmode="numeric">
                    <input type="text" maxlength="1" class="withdraw-code-input" data-index="5" inputmode="numeric">
                </div>

                <span class="withdraw-error withdraw-error--center" id="codeError"></span>

                <button type="submit" class="btn btn--primary btn--full" id="verifyBtn" disabled>
                    출금 확인
                </button>
            </form>
        </div>
    </div>

    <!-- 성공 모달 -->
    <div class="withdraw-modal" id="successModal" style="display: none;">
        <div class="withdraw-modal__backdrop"></div>
        <div class="withdraw-modal__content withdraw-modal__content--success">
            <div class="withdraw-modal__icon withdraw-modal__icon--success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <h3 class="withdraw-modal__title">출금 신청 완료</h3>
            <p class="withdraw-modal__desc">출금 요청이 성공적으로 접수되었습니다.<br>처리가 완료되면 알림을 보내드립니다.</p>

            <div class="withdraw-success-details">
                <div class="withdraw-success-row">
                    <span>출금 수량</span>
                    <span id="successAmount">500 USDT</span>
                </div>
                <div class="withdraw-success-row">
                    <span>네트워크</span>
                    <span id="successNetwork">TRC20</span>
                </div>
                <div class="withdraw-success-row">
                    <span>주소</span>
                    <span id="successAddress" class="withdraw-success-address">TXyz...abc</span>
                </div>
            </div>

            <div class="withdraw-success-actions">
                <button class="btn btn--secondary" id="viewHistory">내역 확인</button>
                <button class="btn btn--primary" id="closeSuccessModal">확인</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 데이터
        const wallets = <?php echo json_encode($wallets); ?>;
        const networks = <?php echo json_encode($networks); ?>;

        let selectedCoin = 'USDT';
        let selectedNetwork = 'TRC20';
        let networkFee = 1;

        // Elements
        const coinSelect = document.getElementById('coinSelect');
        const coinDropdown = document.getElementById('coinDropdown');
        const withdrawForm = document.getElementById('withdrawForm');
        const withdrawAmount = document.getElementById('withdrawAmount');
        const withdrawAddress = document.getElementById('withdrawAddress');
        const networkList = document.getElementById('networkList');

        // 코인 선택 드롭다운
        document.getElementById('selectedCoin').addEventListener('click', function() {
            coinDropdown.style.display = coinDropdown.style.display === 'none' ? 'block' : 'none';
        });

        // 코인 선택
        document.querySelectorAll('.withdraw-coin-item').forEach(item => {
            item.addEventListener('click', function() {
                const coin = this.dataset.coin;
                selectCoin(coin);
                coinDropdown.style.display = 'none';
            });
        });

        function selectCoin(coin) {
            selectedCoin = coin;

            // Update selected display
            document.querySelectorAll('.withdraw-coin-item').forEach(item => {
                item.classList.toggle('selected', item.dataset.coin === coin);
            });

            // Update coin icon (simplified)
            const coinIcons = {
                'USDT': 'https://cryptologos.cc/logos/tether-usdt-logo.png',
                'BTC': 'https://cryptologos.cc/logos/bitcoin-btc-logo.png',
                'ETH': 'https://cryptologos.cc/logos/ethereum-eth-logo.png',
                'SOL': 'https://cryptologos.cc/logos/solana-sol-logo.png'
            };

            const coinNames = {
                'USDT': 'Tether',
                'BTC': 'Bitcoin',
                'ETH': 'Ethereum',
                'SOL': 'Solana'
            };

            document.querySelector('.withdraw-coin-selected img').src = coinIcons[coin];
            document.querySelector('.withdraw-coin-name').textContent = coin;
            document.querySelector('.withdraw-coin-fullname').textContent = coinNames[coin];

            // Update balance
            const wallet = wallets[coin];
            document.getElementById('availableBalance').textContent = formatNumber(wallet.available);
            document.getElementById('totalBalance').textContent = formatNumber(wallet.balance);
            document.getElementById('availableBalanceCard').textContent = formatNumber(wallet.available);
            document.getElementById('lockedBalance').textContent = formatNumber(wallet.locked);

            // Update balance card icon
            document.querySelector('.withdraw-balance-icon').src = coinIcons[coin];
            document.querySelector('.withdraw-balance-name').textContent = coin;
            document.querySelector('.withdraw-balance-fullname').textContent = coinNames[coin];

            // Update networks
            updateNetworks(coin);

            // Clear amount
            withdrawAmount.value = '';
            updateSummary();
        }

        function updateNetworks(coin) {
            const coinNetworks = networks[coin];
            networkList.innerHTML = '';

            coinNetworks.forEach((network, index) => {
                const isSelected = index === 0;
                if (isSelected) {
                    selectedNetwork = network.name;
                    networkFee = network.fee;
                }

                const label = document.createElement('label');
                label.className = 'withdraw-network-item' + (isSelected ? ' selected' : '');
                label.innerHTML = `
                    <input type="radio" name="network" value="${network.name}" ${isSelected ? 'checked' : ''}>
                    <div class="withdraw-network-content">
                        <span class="withdraw-network-name">${network.name}</span>
                        <span class="withdraw-network-fee">수수료: ${network.fee} ${coin}</span>
                    </div>
                    <svg class="withdraw-network-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                `;

                label.querySelector('input').addEventListener('change', function() {
                    document.querySelectorAll('.withdraw-network-item').forEach(item => {
                        item.classList.remove('selected');
                    });
                    this.closest('.withdraw-network-item').classList.add('selected');
                    selectedNetwork = this.value;
                    networkFee = network.fee;
                    updateSummary();
                });

                networkList.appendChild(label);
            });
        }

        // Percent buttons
        document.querySelectorAll('.withdraw-percent-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const percent = parseInt(this.dataset.percent);
                const available = wallets[selectedCoin].available;
                let amount = (available * percent / 100);

                // Subtract fee for max
                if (percent === 100) {
                    amount = Math.max(0, available - networkFee);
                }

                withdrawAmount.value = amount.toFixed(8).replace(/\.?0+$/, '');
                updateSummary();
            });
        });

        // Amount input
        withdrawAmount.addEventListener('input', updateSummary);

        function updateSummary() {
            const amount = parseFloat(withdrawAmount.value) || 0;
            const total = Math.max(0, amount - networkFee);

            document.getElementById('summaryAmount').textContent = formatNumber(amount) + ' ' + selectedCoin;
            document.getElementById('summaryFee').textContent = networkFee + ' ' + selectedCoin;
            document.getElementById('summaryTotal').textContent = formatNumber(total) + ' ' + selectedCoin;
        }

        function formatNumber(num) {
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 });
        }

        // Paste button
        document.getElementById('pasteBtn').addEventListener('click', async function() {
            try {
                const text = await navigator.clipboard.readText();
                withdrawAddress.value = text;
            } catch (err) {
                console.error('Failed to read clipboard');
            }
        });

        // Form submission
        withdrawForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const amount = parseFloat(withdrawAmount.value);
            const address = withdrawAddress.value.trim();
            const available = wallets[selectedCoin].available;

            // Validation
            let hasError = false;

            if (!address) {
                document.getElementById('addressError').textContent = '출금 주소를 입력해주세요';
                document.getElementById('addressError').style.display = 'block';
                hasError = true;
            } else {
                document.getElementById('addressError').style.display = 'none';
            }

            if (!amount || amount <= 0) {
                document.getElementById('amountError').textContent = '출금 수량을 입력해주세요';
                document.getElementById('amountError').style.display = 'block';
                hasError = true;
            } else if (amount > available) {
                document.getElementById('amountError').textContent = '출금 가능 금액을 초과했습니다';
                document.getElementById('amountError').style.display = 'block';
                hasError = true;
            } else if (amount < 10) {
                document.getElementById('amountError').textContent = '최소 출금 수량은 10 ' + selectedCoin + '입니다';
                document.getElementById('amountError').style.display = 'block';
                hasError = true;
            } else {
                document.getElementById('amountError').style.display = 'none';
            }

            if (hasError) return;

            // Show 2FA modal
            document.getElementById('twoFAModal').style.display = 'flex';
            document.querySelector('#codeInputs .withdraw-code-input').focus();
        });

        // 2FA Modal
        const twoFAModal = document.getElementById('twoFAModal');
        const twoFAForm = document.getElementById('twoFAForm');
        const verifyBtn = document.getElementById('verifyBtn');

        document.getElementById('closeTwoFAModal').addEventListener('click', function() {
            twoFAModal.style.display = 'none';
            clearCodeInputs();
        });

        document.querySelector('#twoFAModal .withdraw-modal__backdrop').addEventListener('click', function() {
            twoFAModal.style.display = 'none';
            clearCodeInputs();
        });

        // 2FA tabs
        document.querySelectorAll('.withdraw-2fa-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.withdraw-2fa-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const type = this.dataset.type;
                document.getElementById('authenticatorContent').style.display = type === 'authenticator' ? 'block' : 'none';
                document.getElementById('emailContent').style.display = type === 'email' ? 'block' : 'none';

                if (type === 'email') {
                    sendEmailCode();
                }

                clearCodeInputs();
            });
        });

        // Code inputs
        const codeInputs = document.querySelectorAll('#codeInputs .withdraw-code-input');

        codeInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                const value = this.value.replace(/\D/g, '');
                this.value = value;

                if (value && index < codeInputs.length - 1) {
                    codeInputs[index + 1].focus();
                }

                checkCodeComplete();
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    codeInputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);

                pastedData.split('').forEach((char, i) => {
                    if (codeInputs[i]) {
                        codeInputs[i].value = char;
                    }
                });

                checkCodeComplete();
            });
        });

        function checkCodeComplete() {
            const code = Array.from(codeInputs).map(i => i.value).join('');
            verifyBtn.disabled = code.length !== 6;
        }

        function clearCodeInputs() {
            codeInputs.forEach(input => input.value = '');
            verifyBtn.disabled = true;
            document.getElementById('codeError').style.display = 'none';
        }

        // Email resend
        let emailTimer = 0;
        const resendEmail = document.getElementById('resendEmail');

        function sendEmailCode() {
            if (emailTimer > 0) return;

            emailTimer = 60;
            resendEmail.disabled = true;
            updateEmailTimer();
        }

        function updateEmailTimer() {
            const timerEl = document.getElementById('emailTimer');
            if (emailTimer > 0) {
                timerEl.textContent = `(${emailTimer}s)`;
                emailTimer--;
                setTimeout(updateEmailTimer, 1000);
            } else {
                timerEl.textContent = '';
                resendEmail.disabled = false;
            }
        }

        resendEmail.addEventListener('click', sendEmailCode);

        // 2FA form submission
        twoFAForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const code = Array.from(codeInputs).map(i => i.value).join('');
            if (code.length !== 6) return;

            verifyBtn.disabled = true;
            verifyBtn.textContent = '처리 중...';

            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1500));

            // Demo: verify code
            if (code === '123456') {
                twoFAModal.style.display = 'none';
                clearCodeInputs();

                // Show success modal
                document.getElementById('successAmount').textContent = withdrawAmount.value + ' ' + selectedCoin;
                document.getElementById('successNetwork').textContent = selectedNetwork;
                document.getElementById('successAddress').textContent = withdrawAddress.value.slice(0, 8) + '...' + withdrawAddress.value.slice(-6);
                document.getElementById('successModal').style.display = 'flex';

                // Reset form
                withdrawForm.reset();
                updateSummary();
            } else {
                document.getElementById('codeError').textContent = '인증 코드가 올바르지 않습니다';
                document.getElementById('codeError').style.display = 'block';
                clearCodeInputs();
                codeInputs[0].focus();
            }

            verifyBtn.disabled = false;
            verifyBtn.textContent = '출금 확인';
        });

        // Success modal
        document.getElementById('closeSuccessModal').addEventListener('click', function() {
            document.getElementById('successModal').style.display = 'none';
        });

        document.getElementById('viewHistory').addEventListener('click', function() {
            window.location.href = 'history.php?type=withdraw';
        });

        // Click outside to close dropdown
        document.addEventListener('click', function(e) {
            if (!coinSelect.contains(e.target)) {
                coinDropdown.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>
