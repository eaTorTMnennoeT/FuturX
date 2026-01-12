<?php
/**
 * FuturX - 공통 <head> 태그
 * $pageTitle, $pageCSS 변수를 설정 후 include
 */

// bootstrap이 아직 로드되지 않은 경우에만 로드
if (!function_exists('getDB')) {
    require_once __DIR__ . '/bootstrap.php';
}

if (!isset($pageTitle)) $pageTitle = 'FuturX';
if (!isset($pageCSS)) $pageCSS = [];

// 현재 사용자 정보 가져오기 (쿠키 기반도 지원)
$currentUser = null;
if (function_exists('getCurrentUser')) {
    $currentUser = getCurrentUser();
}

// 쿠키 기반 로그인 체크 (세션이 없는 경우)
if (!$currentUser && isset($_COOKIE['auth_token'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT u.* FROM users u JOIN user_sessions s ON u.id = s.user_id WHERE s.session_token = ? AND s.expires_at > NOW()");
        $stmt->execute([$_COOKIE['auth_token']]);
        $currentUser = $stmt->fetch();

        // 세션에도 저장
        if ($currentUser) {
            $_SESSION['user_id'] = $currentUser['id'];
        }
    } catch (Exception $e) {
        // 오류 무시
    }
}

// 잔액 가져오기
$userBalance = 0;
if ($currentUser) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'USDT'");
        $stmt->execute([$currentUser['id']]);
        $balanceRow = $stmt->fetch();
        $userBalance = $balanceRow ? floatval($balanceRow['balance']) : 0;
    } catch (Exception $e) {
        // 오류 무시
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - FuturX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="common.css">
    <?php foreach ($pageCSS as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>?v=<?= time() ?>">
    <?php endforeach; ?>
    <!-- Toast Notification System -->
    <script src="js/toast.js"></script>
    <!-- Common formatters (symbol decimals from DB) -->
    <script src="js/formatters.js"></script>
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
