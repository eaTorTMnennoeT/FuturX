<?php
/**
 * Funding Account Page
 * 펀딩 계정 - 코인별 잔액 및 입출금/전송 관리
 */

include_once 'includes/bootstrap.php';

// 사용자 자산 데이터 (실제로는 DB에서 가져옴)
$assets = [
    [
        'symbol' => 'USDT',
        'name' => 'Tether',
        'icon' => 'usdt',
        'total' => '1,234.56',
        'available' => '1,000.00',
        'in_use' => '234.56',
        'equivalent' => '1,234.56',
        'apy' => '5.2'
    ],
    [
        'symbol' => 'BTC',
        'name' => 'Bitcoin',
        'icon' => 'btc',
        'total' => '0.05432100',
        'available' => '0.05000000',
        'in_use' => '0.00432100',
        'equivalent' => '2,345.67',
        'apy' => '1.5'
    ],
    [
        'symbol' => 'ETH',
        'name' => 'Ethereum',
        'icon' => 'eth',
        'total' => '1.23456789',
        'available' => '1.00000000',
        'in_use' => '0.23456789',
        'equivalent' => '2,890.12',
        'apy' => '3.2'
    ],
    [
        'symbol' => 'USDC',
        'name' => 'USD Coin',
        'icon' => 'usdc',
        'total' => '500.00',
        'available' => '500.00',
        'in_use' => '0.00',
        'equivalent' => '500.00',
        'apy' => '4.8'
    ],
    [
        'symbol' => 'SOL',
        'name' => 'Solana',
        'icon' => 'sol',
        'total' => '15.5000',
        'available' => '10.0000',
        'in_use' => '5.5000',
        'equivalent' => '1,550.00',
        'apy' => '6.5'
    ],
    [
        'symbol' => 'XRP',
        'name' => 'Ripple',
        'icon' => 'xrp',
        'total' => '1,000.0000',
        'available' => '1,000.0000',
        'in_use' => '0.0000',
        'equivalent' => '520.00',
        'apy' => null
    ],
    [
        'symbol' => 'DOGE',
        'name' => 'Dogecoin',
        'icon' => 'doge',
        'total' => '5,000.0000',
        'available' => '5,000.0000',
        'in_use' => '0.0000',
        'equivalent' => '450.00',
        'apy' => null
    ],
    [
        'symbol' => 'HMSTR',
        'name' => 'Hamster',
        'icon' => 'hmstr',
        'total' => '100,000.00',
        'available' => '100,000.00',
        'in_use' => '0.00',
        'equivalent' => '0.85',
        'apy' => null
    ]
];

// 총 자산 계산
$totalEquity = 9490.35;
$totalBtc = 0.21985432;
$pageTitle = 'Funding Account';
$pageCSS = ['wallet.css', 'wallet-funding.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<main class="main">
    <div class="wallet-container">
        <!-- 사이드바 -->
        <aside class="wallet-sidebar">
            <button class="wallet-sidebar__toggle" id="sidebarToggle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>
            <nav class="wallet-nav">
                <a href="wallet.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Overview
                </a>
                <a href="wallet-funding.php" class="wallet-nav__item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="M2 10h20"/>
                    </svg>
                    Funding
                </a>
                <a href="wallet-trading.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Unified Trading
                </a>
                <div class="wallet-nav__divider"></div>
                <span class="wallet-nav__label">분석</span>
                <a href="wallet-pnl.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18h18"/>
                        <path d="M18 17V9"/>
                        <path d="M13 17V5"/>
                        <path d="M8 17v-3"/>
                    </svg>
                    P&L Analysis
                </a>
                <a href="history.php" class="wallet-nav__item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Orders
                </a>
            </nav>
        </aside>

        <!-- 메인 콘텐츠 -->
        <main class="wallet-content">
            <!-- 페이지 헤더 -->
            <div class="funding-header">
                <div class="funding-header__left">
                    <h1>Funding Account</h1>
                    <button class="wallet-header__eye" id="toggleBalance" title="잔액 숨기기">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <div class="funding-header__right">
                    <a href="deposit.php" class="btn btn--primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12l7 7 7-7"/>
                        </svg>
                        Deposit
                    </a>
                    <a href="withdraw.php" class="btn btn--outline">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 19V5M5 12l7-7 7 7"/>
                        </svg>
                        Withdraw
                    </a>
                    <button class="btn btn--outline" id="transferBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 16V4M7 4L3 8M7 4l4 4M17 8v12M17 20l4-4M17 20l-4-4"/>
                        </svg>
                        Transfer
                    </button>
                </div>
            </div>

            <!-- 총 자산 요약 -->
            <div class="funding-summary">
                <div class="funding-summary__item">
                    <span class="funding-summary__label">Total Equity</span>
                    <div class="funding-summary__value">
                        <span class="funding-summary__amount" data-value="<?php echo $totalEquity; ?>">$<?php echo number_format($totalEquity, 2); ?></span>
                        <span class="funding-summary__btc"><?php echo $totalBtc; ?> BTC</span>
                    </div>
                </div>
                <div class="funding-summary__item">
                    <span class="funding-summary__label">Available Balance</span>
                    <div class="funding-summary__value">
                        <span class="funding-summary__amount" data-value="7654.56">$7,654.56</span>
                    </div>
                </div>
                <div class="funding-summary__item">
                    <span class="funding-summary__label">In Use</span>
                    <div class="funding-summary__value">
                        <span class="funding-summary__amount" data-value="1835.79">$1,835.79</span>
                    </div>
                </div>
            </div>

            <!-- 자산 프로모션 배너 -->
            <div class="funding-promo">
                <div class="funding-promo__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div class="funding-promo__content">
                    <span class="funding-promo__text">Automatically earn yield on idle assets</span>
                    <span class="funding-promo__highlight">Up to 8% APY</span>
                </div>
                <a href="earn.php" class="funding-promo__link">
                    Learn More
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <!-- 필터 및 검색 -->
            <div class="funding-filters">
                <div class="funding-filters__left">
                    <div class="funding-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                        <input type="text" placeholder="Search coin..." id="coinSearch">
                    </div>
                    <label class="funding-checkbox">
                        <input type="checkbox" id="hideSmall">
                        <span class="funding-checkbox__mark"></span>
                        Hide assets &lt; $1
                    </label>
                </div>
                <div class="funding-filters__right">
                    <button class="funding-convert" id="convertSmall">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 4v6h-6M1 20v-6h6"/>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                        </svg>
                        Convert Small Balances
                    </button>
                </div>
            </div>

            <!-- 자산 테이블 -->
            <div class="funding-table">
                <div class="funding-table__header">
                    <div class="funding-table__col funding-table__col--coin">Coin</div>
                    <div class="funding-table__col funding-table__col--total">All</div>
                    <div class="funding-table__col funding-table__col--available">Available Balance</div>
                    <div class="funding-table__col funding-table__col--inuse">In Use</div>
                    <div class="funding-table__col funding-table__col--equiv">Equivalent</div>
                    <div class="funding-table__col funding-table__col--action">Action</div>
                </div>
                <div class="funding-table__body" id="assetList">
                    <?php foreach ($assets as $asset): ?>
                    <div class="funding-table__row" data-symbol="<?php echo $asset['symbol']; ?>" data-equivalent="<?php echo floatval(str_replace(',', '', $asset['equivalent'])); ?>">
                        <div class="funding-table__col funding-table__col--coin">
                            <div class="funding-coin">
                                <div class="funding-coin__icon funding-coin__icon--<?php echo strtolower($asset['icon']); ?>">
                                    <?php echo substr($asset['symbol'], 0, 1); ?>
                                </div>
                                <div class="funding-coin__info">
                                    <span class="funding-coin__symbol"><?php echo $asset['symbol']; ?></span>
                                    <span class="funding-coin__name"><?php echo $asset['name']; ?></span>
                                </div>
                                <?php if ($asset['apy']): ?>
                                <span class="funding-coin__apy"><?php echo $asset['apy']; ?>% APY</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="funding-table__col funding-table__col--total">
                            <span class="funding-amount" data-value="<?php echo $asset['total']; ?>"><?php echo $asset['total']; ?></span>
                        </div>
                        <div class="funding-table__col funding-table__col--available">
                            <span class="funding-amount" data-value="<?php echo $asset['available']; ?>"><?php echo $asset['available']; ?></span>
                        </div>
                        <div class="funding-table__col funding-table__col--inuse">
                            <span class="funding-amount" data-value="<?php echo $asset['in_use']; ?>"><?php echo $asset['in_use']; ?></span>
                        </div>
                        <div class="funding-table__col funding-table__col--equiv">
                            <span class="funding-amount" data-value="<?php echo $asset['equivalent']; ?>">$<?php echo $asset['equivalent']; ?></span>
                        </div>
                        <div class="funding-table__col funding-table__col--action">
                            <div class="funding-actions">
                                <a href="deposit.php?coin=<?php echo $asset['symbol']; ?>" class="funding-actions__link">Deposit</a>
                                <a href="withdraw.php?coin=<?php echo $asset['symbol']; ?>" class="funding-actions__link">Withdraw</a>
                                <button class="funding-actions__link" data-action="transfer" data-coin="<?php echo $asset['symbol']; ?>">Transfer</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 빈 상태 (검색 결과 없음) -->
            <div class="funding-empty" id="emptyState" style="display: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <p>No assets found</p>
                <span>Try adjusting your search or filters</span>
            </div>
        </main>
    </div>

    <!-- 전송 모달 -->
    <div class="modal" id="transferModal">
        <div class="modal__backdrop"></div>
        <div class="modal__container">
            <div class="modal__header">
                <h3>Transfer</h3>
                <button class="modal__close" id="closeTransferModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal__body">
                <div class="transfer-direction">
                    <div class="transfer-account">
                        <label>From</label>
                        <select id="transferFrom">
                            <option value="funding">Funding Account</option>
                            <option value="trading">Unified Trading Account</option>
                        </select>
                    </div>
                    <button class="transfer-swap" id="swapAccounts">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 16V4M7 4L3 8M7 4l4 4M17 8v12M17 20l4-4M17 20l-4-4"/>
                        </svg>
                    </button>
                    <div class="transfer-account">
                        <label>To</label>
                        <select id="transferTo">
                            <option value="trading">Unified Trading Account</option>
                            <option value="funding">Funding Account</option>
                        </select>
                    </div>
                </div>

                <div class="transfer-coin">
                    <label>Coin</label>
                    <div class="transfer-coin__select" id="coinSelectBtn">
                        <div class="transfer-coin__selected">
                            <span class="transfer-coin__icon">U</span>
                            <span>USDT</span>
                        </div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                </div>

                <div class="transfer-amount">
                    <label>Amount</label>
                    <div class="transfer-amount__input">
                        <input type="text" id="transferAmount" placeholder="0.00">
                        <div class="transfer-amount__right">
                            <span class="transfer-amount__coin">USDT</span>
                            <button class="transfer-amount__max">MAX</button>
                        </div>
                    </div>
                    <div class="transfer-amount__info">
                        <span>Available: <strong>1,000.00 USDT</strong></span>
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button class="btn btn--primary btn--full" id="confirmTransfer">Confirm Transfer</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 사이드바 토글
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.wallet-sidebar');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }

        // 잔액 숨기기/보이기
        const toggleBalance = document.getElementById('toggleBalance');
        let balanceHidden = false;

        if (toggleBalance) {
            toggleBalance.addEventListener('click', function() {
                balanceHidden = !balanceHidden;
                document.querySelectorAll('.funding-amount, .funding-summary__amount').forEach(el => {
                    if (balanceHidden) {
                        el.dataset.original = el.textContent;
                        el.textContent = '****';
                    } else {
                        el.textContent = el.dataset.original || el.dataset.value;
                    }
                });

                // 아이콘 변경
                toggleBalance.innerHTML = balanceHidden ?
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>' :
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            });
        }

        // 코인 검색
        const coinSearch = document.getElementById('coinSearch');
        const assetList = document.getElementById('assetList');
        const emptyState = document.getElementById('emptyState');

        if (coinSearch) {
            coinSearch.addEventListener('input', filterAssets);
        }

        // 소액 자산 숨기기
        const hideSmall = document.getElementById('hideSmall');
        if (hideSmall) {
            hideSmall.addEventListener('change', filterAssets);
        }

        function filterAssets() {
            const searchTerm = coinSearch.value.toLowerCase();
            const hideSmallChecked = hideSmall.checked;
            const rows = assetList.querySelectorAll('.funding-table__row');
            let visibleCount = 0;

            rows.forEach(row => {
                const symbol = row.dataset.symbol.toLowerCase();
                const equivalent = parseFloat(row.dataset.equivalent);

                let show = symbol.includes(searchTerm);
                if (hideSmallChecked && equivalent < 1) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            emptyState.style.display = visibleCount === 0 ? '' : 'none';
            document.querySelector('.funding-table').style.display = visibleCount === 0 ? 'none' : '';
        }

        // 전송 모달
        const transferBtn = document.getElementById('transferBtn');
        const transferModal = document.getElementById('transferModal');
        const closeTransferModal = document.getElementById('closeTransferModal');
        const modalBackdrop = transferModal.querySelector('.modal__backdrop');

        function openTransferModal(coin = 'USDT') {
            transferModal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            transferModal.classList.remove('open');
            document.body.style.overflow = '';
        }

        if (transferBtn) {
            transferBtn.addEventListener('click', () => openTransferModal());
        }

        if (closeTransferModal) {
            closeTransferModal.addEventListener('click', closeModal);
        }

        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', closeModal);
        }

        // 개별 자산 전송 버튼
        document.querySelectorAll('[data-action="transfer"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const coin = this.dataset.coin;
                openTransferModal(coin);
            });
        });

        // From/To 스왑
        const swapAccounts = document.getElementById('swapAccounts');
        const transferFrom = document.getElementById('transferFrom');
        const transferTo = document.getElementById('transferTo');

        if (swapAccounts) {
            swapAccounts.addEventListener('click', function() {
                const fromValue = transferFrom.value;
                const toValue = transferTo.value;
                transferFrom.value = toValue;
                transferTo.value = fromValue;
            });
        }

        // 전송 확인
        const confirmTransfer = document.getElementById('confirmTransfer');
        if (confirmTransfer) {
            confirmTransfer.addEventListener('click', function() {
                const amount = document.getElementById('transferAmount').value;
                if (!amount || parseFloat(amount) <= 0) {
                    alert('Please enter a valid amount');
                    return;
                }
                // 실제로는 API 호출
                alert('Transfer successful!');
                closeModal();
            });
        }

        // 소액 잔액 변환
        const convertSmall = document.getElementById('convertSmall');
        if (convertSmall) {
            convertSmall.addEventListener('click', function() {
                alert('Small balances will be converted to USDT. This feature is coming soon!');
            });
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && transferModal.classList.contains('open')) {
                closeModal();
            }
        });
    });
    </script>
</body>
</html>
