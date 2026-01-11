<?php
/**
 * FuturX - 공통 헤더
 * 모든 페이지에서 include하여 사용
 */

// 현재 페이지 감지
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPage = basename($currentPage, '.html');

function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<!-- 헤더 -->
<header class="header">
    <a href="index.php" class="header__logo">
        <div class="header__logo-icon">F</div>
        <span>FuturX</span>
    </a>

    <nav class="header__nav">
        <a href="index.php" class="header__nav-link <?= isActive('index') ?>">마켓</a>
        <a href="trade.php" class="header__nav-link <?= isActive('trade') ?>">선물거래</a>
        <a href="tournament.php" class="header__nav-link <?= isActive('tournament') || isActive('tournament_detail') ? 'active' : '' ?>">
            <i class="fas fa-trophy" style="margin-right: 4px; color: #f0b90b;"></i>대회
        </a>
        <a href="leaderboard.php" class="header__nav-link <?= isActive('leaderboard') ?>">리더보드</a>
        <a href="wallet.php" class="header__nav-link <?= isActive('wallet') ?>">지갑</a>
        <a href="deposit.php" class="header__nav-link <?= isActive('deposit') ?>">입금</a>
    </nav>

    <div class="header__actions">
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="wallet.php" class="btn btn--secondary btn--sm">내 지갑</a>
        <a href="logout.php" class="btn btn--outline btn--sm">로그아웃</a>
        <?php else: ?>
        <button class="btn btn--secondary btn--sm" id="loginBtn">로그인</button>
        <button class="btn btn--primary btn--sm" id="registerBtn">회원가입</button>
        <?php endif; ?>
    </div>
</header>
