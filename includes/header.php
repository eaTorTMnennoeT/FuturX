<?php
/**
 * FuturX - 공통 헤더
 * 모든 페이지에서 include하여 사용
 *
 * 필요한 변수 (head.php에서 설정됨):
 * - $currentUser: 현재 로그인한 사용자 정보 (없으면 null)
 * - $userBalance: 사용자 USDT 잔액
 */

// 현재 페이지 감지
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// 지갑 관련 페이지 그룹
$walletPages = ['wallet', 'wallet-funding', 'wallet-trading', 'wallet-pnl', 'history'];

// isActive 함수가 이미 정의되지 않은 경우에만 정의
if (!function_exists('isActive')) {
    function isActive($pages) {
        global $currentPage;
        if (is_array($pages)) {
            return in_array($currentPage, $pages) ? 'active' : '';
        }
        return $currentPage === $pages ? 'active' : '';
    }
}

if (!function_exists('isActiveGroup')) {
    function isActiveGroup($pages) {
        global $currentPage;
        return in_array($currentPage, $pages) ? 'active' : '';
    }
}

// 변수 기본값 설정
if (!isset($currentUser)) $currentUser = null;
if (!isset($userBalance)) $userBalance = 0;
?>
<!-- 헤더 -->
<header class="header">
    <div class="header__left">
        <a href="index.php" class="header__logo">
            <div class="header__logo-icon">F</div>
            <span class="header__logo-text">FuturX</span>
        </a>

        <nav class="header__nav">
            <a href="index.php" class="header__nav-link <?= isActive('index') ?>">마켓</a>
            <a href="trade.php" class="header__nav-link <?= isActive('trade') ?>">선물거래</a>
            <a href="tournament.php" class="header__nav-link <?= isActive(['tournament', 'tournament_detail']) ?>">
                <svg class="header__nav-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                </svg>
                대회
            </a>
            <a href="leaderboard.php" class="header__nav-link <?= isActive('leaderboard') ?>">리더보드</a>

            <!-- 지갑 드롭다운 -->
            <div class="header__dropdown">
                <a href="wallet.php" class="header__nav-link <?= isActiveGroup($walletPages) ?>">
                    지갑
                    <svg class="header__nav-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </a>
                <div class="header__dropdown-menu">
                    <a href="wallet.php" class="header__dropdown-item <?= isActive('wallet') ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                        Overview
                    </a>
                    <a href="wallet-funding.php" class="header__dropdown-item <?= isActive('wallet-funding') ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <path d="M2 10h20"/>
                        </svg>
                        Funding
                    </a>
                    <a href="wallet-trading.php" class="header__dropdown-item <?= isActive('wallet-trading') ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        Trading
                    </a>
                    <div class="header__dropdown-divider"></div>
                    <a href="wallet-pnl.php" class="header__dropdown-item <?= isActive('wallet-pnl') ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18"/>
                            <path d="M18 17V9M13 17V5M8 17v-3"/>
                        </svg>
                        P&L Analysis
                    </a>
                    <a href="history.php" class="header__dropdown-item <?= isActive('history') ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        거래내역
                    </a>
                </div>
            </div>

            <a href="deposit.php" class="header__nav-link <?= isActive('deposit') ?>">입금</a>
            <a href="withdraw.php" class="header__nav-link <?= isActive('withdraw') ?>">출금</a>
            <a href="referral.php" class="header__nav-link <?= isActive('referral') ?>">
                <svg class="header__nav-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
                추천인
            </a>
        </nav>
    </div>

    <div class="header__right">
        <?php if ($currentUser): ?>
        <!-- 로그인 상태 -->
        <div class="header__user">
            <div class="header__user-balance">
                <span class="header__user-balance-value"><?= number_format($userBalance, 2) ?></span>
                <span class="header__user-balance-unit">USDT</span>
            </div>
            <div class="header__dropdown">
                <button class="header__user-btn">
                    <div class="header__user-avatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <svg class="header__nav-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
                <div class="header__dropdown-menu header__dropdown-menu--right">
                    <a href="profile.php" class="header__dropdown-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        프로필
                    </a>
                    <a href="security.php" class="header__dropdown-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        보안 설정
                    </a>
                    <a href="kyc.php" class="header__dropdown-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <circle cx="9" cy="10" r="2"/>
                            <path d="M15 8h2M15 12h2M7 16h10"/>
                        </svg>
                        KYC 인증
                    </a>
                    <div class="header__dropdown-divider"></div>
                    <a href="support.php" class="header__dropdown-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        고객지원
                    </a>
                    <div class="header__dropdown-divider"></div>
                    <a href="logout.php" class="header__dropdown-item header__dropdown-item--danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        로그아웃
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- 비로그인 상태 -->
        <div class="header__auth">
            <a href="login.php" class="btn btn--ghost btn--sm">로그인</a>
            <a href="register.php" class="btn btn--primary btn--sm">회원가입</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- 모바일 메뉴 토글 -->
    <button class="header__mobile-toggle" id="mobileMenuToggle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
</header>

<!-- 모바일 메뉴 -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu__header">
        <a href="index.php" class="header__logo">
            <div class="header__logo-icon">F</div>
            <span class="header__logo-text">FuturX</span>
        </a>
        <button class="mobile-menu__close" id="mobileMenuClose">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    <nav class="mobile-menu__nav">
        <a href="index.php" class="mobile-menu__link <?= isActive('index') ?>">마켓</a>
        <a href="trade.php" class="mobile-menu__link <?= isActive('trade') ?>">선물거래</a>
        <a href="tournament.php" class="mobile-menu__link <?= isActive(['tournament', 'tournament_detail']) ?>">대회</a>
        <a href="leaderboard.php" class="mobile-menu__link <?= isActive('leaderboard') ?>">리더보드</a>
        <div class="mobile-menu__divider"></div>
        <span class="mobile-menu__label">지갑</span>
        <a href="wallet.php" class="mobile-menu__link <?= isActive('wallet') ?>">Overview</a>
        <a href="wallet-funding.php" class="mobile-menu__link <?= isActive('wallet-funding') ?>">Funding</a>
        <a href="wallet-pnl.php" class="mobile-menu__link <?= isActive('wallet-pnl') ?>">P&L Analysis</a>
        <a href="history.php" class="mobile-menu__link <?= isActive('history') ?>">거래내역</a>
        <div class="mobile-menu__divider"></div>
        <a href="deposit.php" class="mobile-menu__link <?= isActive('deposit') ?>">입금</a>
        <a href="withdraw.php" class="mobile-menu__link <?= isActive('withdraw') ?>">출금</a>
        <a href="referral.php" class="mobile-menu__link mobile-menu__link--highlight <?= isActive('referral') ?>">추천인</a>
        <div class="mobile-menu__divider"></div>
        <?php if ($currentUser): ?>
        <div class="mobile-menu__user">
            <span class="mobile-menu__user-balance"><?= number_format($userBalance, 2) ?> USDT</span>
        </div>
        <a href="profile.php" class="mobile-menu__link">프로필</a>
        <a href="security.php" class="mobile-menu__link">보안 설정</a>
        <a href="kyc.php" class="mobile-menu__link">KYC 인증</a>
        <a href="support.php" class="mobile-menu__link">고객지원</a>
        <a href="logout.php" class="mobile-menu__link mobile-menu__link--danger">로그아웃</a>
        <?php else: ?>
        <a href="login.php" class="mobile-menu__link">로그인</a>
        <a href="register.php" class="mobile-menu__link mobile-menu__link--primary">회원가입</a>
        <?php endif; ?>
    </nav>
</div>
<div class="mobile-menu__backdrop" id="mobileMenuBackdrop"></div>

<script>
// 모바일 메뉴 토글
(function() {
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileClose = document.getElementById('mobileMenuClose');
    const mobileBackdrop = document.getElementById('mobileMenuBackdrop');

    function openMobileMenu() {
        mobileMenu.classList.add('open');
        mobileBackdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        mobileMenu.classList.remove('open');
        mobileBackdrop.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (mobileToggle) mobileToggle.addEventListener('click', openMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);
    if (mobileBackdrop) mobileBackdrop.addEventListener('click', closeMobileMenu);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu && mobileMenu.classList.contains('open')) {
            closeMobileMenu();
        }
    });
})();
</script>
