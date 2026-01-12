<?php
/**
 * FuturX Admin - 공통 레이아웃
 */

require_once 'auth.php';

// 로그인 페이지가 아니면 관리자 권한 체크
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'login.php') {
    requireAdmin();
}

$admin = getCurrentAdmin();
$csrfToken = generateCSRFToken();

// 현재 페이지 확인
$menuItems = [
    'index.php' => ['icon' => 'fa-chart-pie', 'label' => '대시보드'],
    'stats.php' => ['icon' => 'fa-chart-bar', 'label' => '통계관리'],
    'online.php' => ['icon' => 'fa-signal', 'label' => '실시간 접속자'],
    'users.php' => ['icon' => 'fa-users', 'label' => '회원관리'],
    'symbols.php' => ['icon' => 'fa-coins', 'label' => '코인 관리'],
    'trades.php' => ['icon' => 'fa-exchange-alt', 'label' => '거래관리'],
    'orders.php' => ['icon' => 'fa-list-alt', 'label' => '주문관리'],
    'positions.php' => ['icon' => 'fa-chart-line', 'label' => '포지션'],
    'transactions.php' => ['icon' => 'fa-wallet', 'label' => '입출금'],
    'competitions.php' => ['icon' => 'fa-trophy', 'label' => '대회관리'],
    'announcements.php' => ['icon' => 'fa-bullhorn', 'label' => '공지사항'],
    'logs.php' => ['icon' => 'fa-history', 'label' => '활동로그'],
    'settings.php' => ['icon' => 'fa-cog', 'label' => '설정']
];

/**
 * 헤더 출력
 */
function renderHeader($pageTitle = '관리자') {
    global $admin, $csrfToken, $currentPage, $menuItems;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?> - FuturX Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Common formatters (symbol decimals from DB) -->
    <script src="../js/formatters.js"></script>
    <!-- PHP Config to JS -->
    <script>
        <?php
        // DB symbols -> decimals maps (single source of truth)
        $symbols = function_exists('getSymbols') ? getSymbols(true) : [];
        $priceDecimalsBySymbol = [];
        $qtyDecimalsBySymbol = [];
        foreach ($symbols as $sym => $row) {
            $priceDecimalsBySymbol[$sym] = isset($row['price_decimals']) ? (int)$row['price_decimals'] : 2;
            $qtyDecimalsBySymbol[$sym] = isset($row['qty_decimals']) ? (int)$row['qty_decimals'] : 3;
        }
        ?>

        window.FuturXConfig = window.FuturXConfig || {};
        window.FuturXConfig.wsServerUrl = '<?= WS_SERVER_URL ?>';
        window.FuturXConfig.priceDecimalsBySymbol = <?= json_encode($priceDecimalsBySymbol, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.FuturXConfig.qtyDecimalsBySymbol = <?= json_encode($qtyDecimalsBySymbol, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>
    <input type="hidden" id="csrf_token" value="<?= $csrfToken ?>">

    <!-- 사이드바 오버레이 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- 사이드바 -->
    <aside class="sidebar">
        <div class="sidebar__header">
            <a href="index.php" class="sidebar__logo">
                <div class="sidebar__logo-icon">F</div>
                <span>FuturX Admin</span>
            </a>
            <button class="sidebar__close" id="sidebarClose" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="sidebar__nav">
            <ul class="sidebar__menu">
                <?php foreach ($menuItems as $page => $item): ?>
                <?php
                    // 설정 페이지는 슈퍼관리자만
                    if ($page === 'settings.php' && !isSuperAdmin()) continue;
                ?>
                <li class="sidebar__menu-item">
                    <a href="<?= $page ?>" class="sidebar__menu-link <?= $currentPage === $page ? 'active' : '' ?>">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="sidebar__footer">
            <a href="../index.html" class="sidebar__link" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>사이트 보기</span>
            </a>
            <a href="logout.php" class="sidebar__link sidebar__link--logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>로그아웃</span>
            </a>
        </div>
    </aside>

    <!-- 메인 컨텐츠 -->
    <div class="main-wrapper">
        <!-- 상단 헤더 -->
        <header class="topbar">
            <button class="topbar__toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="topbar__search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="검색..." id="globalSearch">
            </div>

            <div class="topbar__actions">
                <button class="topbar__btn" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="topbar__badge">3</span>
                </button>

                <div class="topbar__user">
                    <div class="topbar__avatar">
                        <?php if ($admin['avatar']): ?>
                            <img src="<?= htmlspecialchars($admin['avatar']) ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="topbar__user-info">
                        <span class="topbar__user-name"><?= htmlspecialchars($admin['nickname']) ?></span>
                        <span class="topbar__user-role"><?= $admin['role'] === 'superadmin' ? '슈퍼관리자' : '관리자' ?></span>
                    </div>
                    <i class="fas fa-chevron-down"></i>

                    <div class="topbar__dropdown">
                        <a href="profile.php"><i class="fas fa-user-cog"></i> 내 정보</a>
                        <a href="logs.php?user=<?= $admin['id'] ?>"><i class="fas fa-history"></i> 내 활동</a>
                        <hr>
                        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> 로그아웃</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- 페이지 컨텐츠 -->
        <main class="main-content">
<?php
}

/**
 * 푸터 출력
 */
function renderFooter() {
?>
        </main>

        <!-- 하단 푸터 -->
        <footer class="main-footer">
            <div class="main-footer__left">
                © 2024 FuturX. All rights reserved.
            </div>
            <div class="main-footer__right">
                Version 1.0.0
            </div>
        </footer>
    </div>

    <!-- 토스트 컨테이너 -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="admin.js"></script>
</body>
</html>
<?php
}

/**
 * 페이지 타이틀
 */
function renderPageHeader($title, $subtitle = '', $actions = []) {
?>
    <div class="page-header">
        <div class="page-header__info">
            <h1 class="page-header__title"><?= htmlspecialchars($title) ?></h1>
            <?php if ($subtitle): ?>
            <p class="page-header__subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($actions)): ?>
        <div class="page-header__actions">
            <?php foreach ($actions as $action): ?>
            <a href="<?= $action['href'] ?? '#' ?>" class="btn <?= $action['class'] ?? 'btn--primary' ?>" <?= isset($action['onclick']) ? 'onclick="' . $action['onclick'] . '"' : '' ?>>
                <?php if (isset($action['icon'])): ?>
                <i class="fas <?= $action['icon'] ?>"></i>
                <?php endif; ?>
                <?= htmlspecialchars($action['label']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php
}

/**
 * 통계 카드
 */
function renderStatCard($icon, $label, $value, $change = null, $changeType = 'up') {
?>
    <div class="stat-card">
        <div class="stat-card__icon">
            <i class="fas <?= $icon ?>"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label"><?= htmlspecialchars($label) ?></span>
            <span class="stat-card__value"><?= htmlspecialchars($value) ?></span>
            <?php if ($change !== null): ?>
            <span class="stat-card__change stat-card__change--<?= $changeType ?>">
                <i class="fas fa-<?= $changeType === 'up' ? 'arrow-up' : 'arrow-down' ?>"></i>
                <?= htmlspecialchars($change) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
<?php
}

/**
 * 페이지네이션
 */
function renderPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return;
?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
        <a href="<?= $baseUrl ?>&page=1" class="pagination__btn">
            <i class="fas fa-angle-double-left"></i>
        </a>
        <a href="<?= $baseUrl ?>&page=<?= $currentPage - 1 ?>" class="pagination__btn">
            <i class="fas fa-angle-left"></i>
        </a>
        <?php endif; ?>

        <?php
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);

        for ($i = $start; $i <= $end; $i++):
        ?>
        <a href="<?= $baseUrl ?>&page=<?= $i ?>" class="pagination__btn <?= $i === $currentPage ? 'active' : '' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
        <a href="<?= $baseUrl ?>&page=<?= $currentPage + 1 ?>" class="pagination__btn">
            <i class="fas fa-angle-right"></i>
        </a>
        <a href="<?= $baseUrl ?>&page=<?= $totalPages ?>" class="pagination__btn">
            <i class="fas fa-angle-double-right"></i>
        </a>
        <?php endif; ?>
    </div>
<?php
}
