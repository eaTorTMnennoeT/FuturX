<?php
/**
 * FuturX - 토너먼트 상세 페이지
 */
require_once 'config.php';

$tournamentId = $_GET['id'] ?? '';
if (!$tournamentId) {
    header('Location: tournament.php');
    exit;
}

$db = getDB();

// 토너먼트 정보
$stmt = $db->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
$stmt->execute([$tournamentId]);
$tournament = $stmt->fetch();

if (!$tournament) {
    header('Location: tournament.php');
    exit;
}

$pageTitle = $tournament['title'];
$pageCSS = ['tournament.css', 'tournament_detail.css'];
require_once 'includes/head.php';
require_once 'includes/header.php';

// 상금 분배
$prizeDistribution = json_decode($tournament['prize_distribution'], true) ?? [];

// 참가자 수
$participantsStmt = $db->prepare("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = ?");
$participantsStmt->execute([$tournamentId]);
$participantCount = $participantsStmt->fetchColumn();

// 리더보드 (상위 50명)
$leaderboardStmt = $db->prepare("
    SELECT tp.*, u.username, u.email
    FROM tournament_participants tp
    LEFT JOIN users u ON tp.user_id = u.id
    WHERE tp.tournament_id = ?
    ORDER BY tp.pnl_percent DESC
    LIMIT 50
");
$leaderboardStmt->execute([$tournamentId]);
$leaderboard = $leaderboardStmt->fetchAll();

// 현재 사용자 참가 여부 확인 (로그인 시)
$isParticipant = false;
$myRank = null;
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    $checkStmt = $db->prepare("SELECT * FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
    $checkStmt->execute([$tournamentId, $userId]);
    $myParticipation = $checkStmt->fetch();
    $isParticipant = (bool)$myParticipation;
    $myRank = $myParticipation['current_rank'] ?? null;
}

// 최근 공지
$announcementsStmt = $db->prepare("
    SELECT * FROM tournament_announcements
    WHERE tournament_id = ? OR tournament_id IS NULL
    ORDER BY is_pinned DESC, created_at DESC
    LIMIT 5
");
$announcementsStmt->execute([$tournamentId]);
$announcements = $announcementsStmt->fetchAll();

// 상태 & 타입 헬퍼 함수
function getStatusInfo($status) {
    $info = [
        'upcoming' => ['text' => '시작 예정', 'class' => 'upcoming', 'icon' => 'clock'],
        'registration' => ['text' => '참가 등록중', 'class' => 'registration', 'icon' => 'user-plus'],
        'active' => ['text' => '진행중', 'class' => 'active', 'icon' => 'play-circle'],
        'calculating' => ['text' => '정산중', 'class' => 'calculating', 'icon' => 'calculator'],
        'ended' => ['text' => '종료됨', 'class' => 'ended', 'icon' => 'flag-checkered'],
    ];
    return $info[$status] ?? ['text' => $status, 'class' => '', 'icon' => 'circle'];
}

function getTypeLabel($type) {
    $labels = ['daily' => '일간', 'weekly' => '주간', 'monthly' => '월간', 'special' => '스페셜'];
    return $labels[$type] ?? $type;
}

function getBadgeIcon($icon) {
    $icons = [
        'trophy' => '<i class="fas fa-trophy"></i>',
        'crown' => '<i class="fas fa-crown"></i>',
        'bolt' => '<i class="fas fa-bolt"></i>',
        'gift' => '<i class="fas fa-gift"></i>',
    ];
    return $icons[$icon] ?? '<i class="fas fa-trophy"></i>';
}

$statusInfo = getStatusInfo($tournament['status']);

// 시간 계산
$now = time();
$regStart = strtotime($tournament['registration_start']);
$regEnd = strtotime($tournament['registration_end']);
$startTime = strtotime($tournament['start_time']);
$endTime = strtotime($tournament['end_time']);

// 진행률
$progress = 0;
if ($tournament['status'] === 'active' && $startTime < $now) {
    $progress = min(100, max(0, (($now - $startTime) / ($endTime - $startTime)) * 100));
}

// 카운트다운 대상 시간
$countdownTarget = null;
$countdownLabel = '';
if ($tournament['status'] === 'upcoming' && $now < $regStart) {
    $countdownTarget = $regStart;
    $countdownLabel = '등록 시작까지';
} elseif ($tournament['status'] === 'registration') {
    $countdownTarget = $regEnd;
    $countdownLabel = '등록 마감까지';
} elseif ($tournament['status'] === 'active') {
    $countdownTarget = $endTime;
    $countdownLabel = '대회 종료까지';
}
?>

    <main class="main tournament-detail-page">
        <!-- 헤더 배너 -->
        <section class="detail-hero">
            <div class="detail-hero__bg">
                <div class="hero-gradient" style="--badge-color: <?= $tournament['badge_color'] ?>;"></div>
            </div>
            <div class="container">
                <div class="detail-hero__content">
                    <div class="detail-hero__breadcrumb">
                        <a href="tournament.php"><i class="fas fa-arrow-left"></i> 대회 목록</a>
                    </div>

                    <div class="detail-hero__badge" style="background: <?= $tournament['badge_color'] ?>;">
                        <?= getBadgeIcon($tournament['badge_icon']) ?>
                    </div>

                    <div class="detail-hero__meta">
                        <span class="type-badge"><?= getTypeLabel($tournament['type']) ?></span>
                        <span class="status-badge status-badge--<?= $statusInfo['class'] ?>">
                            <i class="fas fa-<?= $statusInfo['icon'] ?>"></i>
                            <?= $statusInfo['text'] ?>
                        </span>
                    </div>

                    <h1 class="detail-hero__title"><?= htmlspecialchars($tournament['title']) ?></h1>
                    <p class="detail-hero__subtitle"><?= htmlspecialchars($tournament['subtitle']) ?></p>

                    <div class="detail-hero__prize">
                        <span class="prize-label">총 상금</span>
                        <div class="prize-amount">
                            <span class="prize-currency">$</span>
                            <span class="prize-value"><?= number_format($tournament['prize_pool']) ?></span>
                            <span class="prize-unit"><?= $tournament['prize_currency'] ?></span>
                        </div>
                    </div>

                    <!-- 카운트다운 -->
                    <?php if ($countdownTarget): ?>
                    <div class="countdown-section">
                        <span class="countdown-label"><?= $countdownLabel ?></span>
                        <div class="countdown" id="countdown" data-target="<?= $countdownTarget ?>">
                            <div class="countdown-item">
                                <span class="countdown-value" data-days>00</span>
                                <span class="countdown-unit">일</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-value" data-hours>00</span>
                                <span class="countdown-unit">시간</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-value" data-minutes>00</span>
                                <span class="countdown-unit">분</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-value" data-seconds>00</span>
                                <span class="countdown-unit">초</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 진행률 바 -->
                    <?php if ($tournament['status'] === 'active'): ?>
                    <div class="progress-section">
                        <div class="progress-bar">
                            <div class="progress-bar__fill" style="width: <?= $progress ?>%"></div>
                        </div>
                        <div class="progress-info">
                            <span><?= date('m/d H:i', $startTime) ?> 시작</span>
                            <span class="progress-percent"><?= round($progress) ?>% 진행</span>
                            <span><?= date('m/d H:i', $endTime) ?> 종료</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 참가 카드 & 요약 정보 -->
        <section class="detail-summary">
            <div class="container">
                <div class="summary-grid">
                    <!-- 참가 카드 -->
                    <div class="participation-card">
                        <?php if ($tournament['status'] === 'registration'): ?>
                            <?php if ($isParticipant): ?>
                            <div class="participation-card__status participation-card__status--joined">
                                <i class="fas fa-check-circle"></i>
                                <span>참가 등록 완료</span>
                            </div>
                            <p class="participation-card__message">대회 시작을 기다려주세요!</p>
                            <?php else: ?>
                            <h3 class="participation-card__title">지금 참가하세요!</h3>
                            <div class="participation-card__fee">
                                <?php if ($tournament['entry_fee'] > 0): ?>
                                <span class="fee-label">참가비</span>
                                <span class="fee-amount">$<?= number_format($tournament['entry_fee']) ?></span>
                                <?php else: ?>
                                <span class="fee-free"><i class="fas fa-gift"></i> 무료 참가</span>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn--primary btn--lg btn--block" id="joinTournamentBtn">
                                <i class="fas fa-user-plus"></i> 참가 신청하기
                            </button>
                            <p class="participation-card__note">
                                시작 자금: $<?= number_format($tournament['starting_balance']) ?> 지급
                            </p>
                            <?php endif; ?>
                        <?php elseif ($tournament['status'] === 'active'): ?>
                            <?php if ($isParticipant): ?>
                            <div class="participation-card__rank">
                                <span class="rank-label">내 현재 순위</span>
                                <span class="rank-value">#<?= $myRank ?? '-' ?></span>
                            </div>
                            <a href="trade.php" class="btn btn--primary btn--lg btn--block">
                                <i class="fas fa-chart-line"></i> 거래하러 가기
                            </a>
                            <?php else: ?>
                            <div class="participation-card__closed">
                                <i class="fas fa-lock"></i>
                                <span>등록 마감됨</span>
                            </div>
                            <p class="participation-card__message">다음 대회를 기대해주세요!</p>
                            <?php endif; ?>
                        <?php elseif ($tournament['status'] === 'ended'): ?>
                            <div class="participation-card__ended">
                                <i class="fas fa-flag-checkered"></i>
                                <span>대회 종료</span>
                            </div>
                            <a href="#results" class="btn btn--secondary btn--lg btn--block">
                                <i class="fas fa-medal"></i> 최종 결과 보기
                            </a>
                        <?php else: ?>
                            <div class="participation-card__upcoming">
                                <i class="fas fa-clock"></i>
                                <span>곧 시작됩니다</span>
                            </div>
                            <button class="btn btn--secondary btn--lg btn--block">
                                <i class="fas fa-bell"></i> 알림 받기
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- 요약 통계 -->
                    <div class="summary-stats">
                        <div class="stat-card">
                            <div class="stat-card__icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-card__content">
                                <span class="stat-card__value"><?= number_format($participantCount) ?></span>
                                <span class="stat-card__label">참가자</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="stat-card__content">
                                <span class="stat-card__value"><?= number_format($tournament['total_trades']) ?></span>
                                <span class="stat-card__label">총 거래 수</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="stat-card__content">
                                <span class="stat-card__value">$<?= number_format($tournament['total_volume'] / 1000000, 1) ?>M</span>
                                <span class="stat-card__label">총 거래량</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="stat-card__content">
                                <span class="stat-card__value">$<?= number_format($tournament['starting_balance']) ?></span>
                                <span class="stat-card__label">시작 자금</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 탭 네비게이션 -->
        <section class="detail-tabs">
            <div class="container">
                <div class="tabs-nav">
                    <button class="tab-btn active" data-tab="leaderboard">
                        <i class="fas fa-trophy"></i> 리더보드
                    </button>
                    <button class="tab-btn" data-tab="prizes">
                        <i class="fas fa-gift"></i> 상금 구조
                    </button>
                    <button class="tab-btn" data-tab="rules">
                        <i class="fas fa-list-alt"></i> 대회 규칙
                    </button>
                    <button class="tab-btn" data-tab="schedule">
                        <i class="fas fa-calendar-alt"></i> 일정
                    </button>
                </div>
            </div>
        </section>

        <!-- 탭 컨텐츠 -->
        <section class="detail-content">
            <div class="container">
                <!-- 리더보드 탭 -->
                <div class="tab-content active" id="tab-leaderboard">
                    <div class="leaderboard-section">
                        <!-- 포디움 (상위 3명) -->
                        <?php if (count($leaderboard) >= 3): ?>
                        <div class="podium">
                            <!-- 2위 -->
                            <div class="podium-item podium-item--2">
                                <div class="podium-avatar">
                                    <img src="https://api.dicebear.com/7.x/identicon/svg?seed=<?= $leaderboard[1]['user_id'] ?? 'user2' ?>" alt="">
                                </div>
                                <div class="podium-info">
                                    <span class="podium-name"><?= htmlspecialchars($leaderboard[1]['username'] ?? 'Trader***') ?></span>
                                    <span class="podium-pnl <?= $leaderboard[1]['pnl_percent'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                        <?= $leaderboard[1]['pnl_percent'] >= 0 ? '+' : '' ?><?= number_format($leaderboard[1]['pnl_percent'], 2) ?>%
                                    </span>
                                    <span class="podium-balance">$<?= number_format($leaderboard[1]['tournament_balance'], 2) ?></span>
                                </div>
                                <div class="podium-rank">2</div>
                                <div class="podium-pedestal podium-pedestal--2"></div>
                            </div>
                            <!-- 1위 -->
                            <div class="podium-item podium-item--1">
                                <div class="podium-crown"><i class="fas fa-crown"></i></div>
                                <div class="podium-avatar">
                                    <img src="https://api.dicebear.com/7.x/identicon/svg?seed=<?= $leaderboard[0]['user_id'] ?? 'user1' ?>" alt="">
                                </div>
                                <div class="podium-info">
                                    <span class="podium-name"><?= htmlspecialchars($leaderboard[0]['username'] ?? 'Trader***') ?></span>
                                    <span class="podium-pnl <?= $leaderboard[0]['pnl_percent'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                        <?= $leaderboard[0]['pnl_percent'] >= 0 ? '+' : '' ?><?= number_format($leaderboard[0]['pnl_percent'], 2) ?>%
                                    </span>
                                    <span class="podium-balance">$<?= number_format($leaderboard[0]['tournament_balance'], 2) ?></span>
                                </div>
                                <div class="podium-rank">1</div>
                                <div class="podium-pedestal podium-pedestal--1"></div>
                            </div>
                            <!-- 3위 -->
                            <div class="podium-item podium-item--3">
                                <div class="podium-avatar">
                                    <img src="https://api.dicebear.com/7.x/identicon/svg?seed=<?= $leaderboard[2]['user_id'] ?? 'user3' ?>" alt="">
                                </div>
                                <div class="podium-info">
                                    <span class="podium-name"><?= htmlspecialchars($leaderboard[2]['username'] ?? 'Trader***') ?></span>
                                    <span class="podium-pnl <?= $leaderboard[2]['pnl_percent'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                        <?= $leaderboard[2]['pnl_percent'] >= 0 ? '+' : '' ?><?= number_format($leaderboard[2]['pnl_percent'], 2) ?>%
                                    </span>
                                    <span class="podium-balance">$<?= number_format($leaderboard[2]['tournament_balance'], 2) ?></span>
                                </div>
                                <div class="podium-rank">3</div>
                                <div class="podium-pedestal podium-pedestal--3"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 전체 순위 테이블 -->
                        <div class="leaderboard-table-wrapper">
                            <table class="leaderboard-table">
                                <thead>
                                    <tr>
                                        <th>순위</th>
                                        <th>트레이더</th>
                                        <th>현재 잔고</th>
                                        <th>수익률</th>
                                        <th>수익금</th>
                                        <th>거래 수</th>
                                        <th>승률</th>
                                        <th>예상 상금</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard as $idx => $participant):
                                        $rank = $idx + 1;
                                        $winRate = $participant['total_trades'] > 0
                                            ? ($participant['winning_trades'] / $participant['total_trades']) * 100
                                            : 0;

                                        // 예상 상금 계산
                                        $expectedPrize = 0;
                                        foreach ($prizeDistribution as $prize) {
                                            if (isset($prize['rank']) && $prize['rank'] === $rank) {
                                                $expectedPrize = isset($prize['amount'])
                                                    ? $prize['amount']
                                                    : $tournament['prize_pool'] * ($prize['percent'] / 100);
                                                break;
                                            }
                                            if (isset($prize['rank_from']) && isset($prize['rank_to'])) {
                                                if ($rank >= $prize['rank_from'] && $rank <= $prize['rank_to']) {
                                                    $rangeCount = $prize['rank_to'] - $prize['rank_from'] + 1;
                                                    $expectedPrize = ($tournament['prize_pool'] * ($prize['percent'] / 100)) / $rangeCount;
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                    <tr class="<?= $rank <= 3 ? 'top-rank top-rank--' . $rank : '' ?>">
                                        <td>
                                            <span class="rank-badge <?= $rank <= 3 ? 'rank-badge--' . $rank : '' ?>">
                                                <?php if ($rank === 1): ?>
                                                <i class="fas fa-crown"></i>
                                                <?php elseif ($rank <= 3): ?>
                                                <i class="fas fa-medal"></i>
                                                <?php else: ?>
                                                <?= $rank ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="trader-info">
                                                <img src="https://api.dicebear.com/7.x/identicon/svg?seed=<?= $participant['user_id'] ?>" class="trader-avatar" alt="">
                                                <span class="trader-name"><?= htmlspecialchars($participant['username'] ?? 'Trader***' . $participant['user_id']) ?></span>
                                            </div>
                                        </td>
                                        <td class="balance">$<?= number_format($participant['tournament_balance'], 2) ?></td>
                                        <td>
                                            <span class="<?= $participant['pnl_percent'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                                <?= $participant['pnl_percent'] >= 0 ? '+' : '' ?><?= number_format($participant['pnl_percent'], 2) ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="<?= $participant['pnl'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                                <?= $participant['pnl'] >= 0 ? '+' : '' ?>$<?= number_format($participant['pnl'], 2) ?>
                                            </span>
                                        </td>
                                        <td><?= $participant['total_trades'] ?></td>
                                        <td><?= number_format($winRate, 1) ?>%</td>
                                        <td>
                                            <?php if ($expectedPrize > 0): ?>
                                            <span class="prize-preview">$<?= number_format($expectedPrize, 0) ?></span>
                                            <?php else: ?>
                                            <span class="no-prize">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($leaderboard)): ?>
                                    <tr>
                                        <td colspan="8" class="empty-message">
                                            <i class="fas fa-users"></i>
                                            <p>아직 참가자가 없습니다</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($participantCount > 50): ?>
                        <div class="load-more">
                            <button class="btn btn--secondary" id="loadMoreBtn">
                                더보기 (<?= $participantCount - 50 ?>명 더)
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 상금 구조 탭 -->
                <div class="tab-content" id="tab-prizes">
                    <div class="prizes-section">
                        <div class="prizes-header">
                            <h3>상금 분배 구조</h3>
                            <div class="total-pool">
                                <span class="pool-label">총 상금풀</span>
                                <span class="pool-value">$<?= number_format($tournament['prize_pool']) ?></span>
                            </div>
                        </div>

                        <div class="prizes-grid">
                            <?php foreach ($prizeDistribution as $idx => $prize): ?>
                            <div class="prize-tier <?= $idx < 3 ? 'prize-tier--top prize-tier--' . ($idx + 1) : '' ?>">
                                <div class="prize-tier__rank">
                                    <?php if ($idx === 0): ?>
                                    <i class="fas fa-crown"></i>
                                    <?php elseif ($idx === 1): ?>
                                    <i class="fas fa-medal silver"></i>
                                    <?php elseif ($idx === 2): ?>
                                    <i class="fas fa-medal bronze"></i>
                                    <?php endif; ?>
                                    <span>
                                        <?php if (isset($prize['rank'])): ?>
                                        <?= $prize['rank'] ?>위
                                        <?php else: ?>
                                        <?= $prize['rank_from'] ?>~<?= $prize['rank_to'] ?>위
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="prize-tier__amount">
                                    <?php if (isset($prize['amount'])): ?>
                                    $<?= number_format($prize['amount']) ?>
                                    <?php else: ?>
                                    <?= $prize['percent'] ?>% (~$<?= number_format($tournament['prize_pool'] * $prize['percent'] / 100) ?>)
                                    <?php endif; ?>
                                </div>
                                <div class="prize-tier__percent">
                                    <div class="percent-bar">
                                        <div class="percent-bar__fill" style="width: <?= $prize['percent'] ?>%"></div>
                                    </div>
                                    <span><?= $prize['percent'] ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="prizes-note">
                            <i class="fas fa-info-circle"></i>
                            <p>상금은 대회 종료 후 24시간 내 자동으로 계정에 지급됩니다. 동순위 시 먼저 해당 수익률에 도달한 참가자가 상위입니다.</p>
                        </div>
                    </div>
                </div>

                <!-- 대회 규칙 탭 -->
                <div class="tab-content" id="tab-rules">
                    <div class="rules-section">
                        <div class="rules-content">
                            <?php if ($tournament['description']): ?>
                            <div class="rules-description markdown">
                                <?= nl2br(htmlspecialchars($tournament['description'])) ?>
                            </div>
                            <?php endif; ?>

                            <div class="rules-list">
                                <h4><i class="fas fa-gavel"></i> 기본 규칙</h4>
                                <ul>
                                    <li>참가자는 대회 시작 시 <strong>$<?= number_format($tournament['starting_balance']) ?></strong>의 가상 자금을 지급받습니다.</li>
                                    <li>최종 순위는 <strong>수익률(PnL %)</strong> 기준으로 산정됩니다.</li>
                                    <li>모든 거래는 실시간 시세 기준으로 체결됩니다.</li>
                                    <li>대회 기간 중 청산(Liquidation) 시 해당 포지션 손실이 반영됩니다.</li>
                                </ul>

                                <h4><i class="fas fa-shield-alt"></i> 부정행위 방지</h4>
                                <ul>
                                    <li>복수 계정 참가 금지 (적발 시 실격 및 상금 몰수)</li>
                                    <li>담합 거래 금지</li>
                                    <li>시스템 악용 행위 금지</li>
                                    <li>부정행위 의심 시 운영진 판단에 따라 실격 처리될 수 있습니다.</li>
                                </ul>

                                <h4><i class="fas fa-coins"></i> 상금 지급</h4>
                                <ul>
                                    <li>대회 종료 후 24시간 내 자동 지급</li>
                                    <li>동순위 시 먼저 해당 수익률에 도달한 참가자 우선</li>
                                    <li>최소 <strong>5회 이상</strong> 거래 완료 필수</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 일정 탭 -->
                <div class="tab-content" id="tab-schedule">
                    <div class="schedule-section">
                        <div class="timeline">
                            <div class="timeline-item <?= $now >= $regStart ? 'completed' : ($now < $regStart ? 'upcoming' : '') ?>">
                                <div class="timeline-marker">
                                    <i class="fas fa-<?= $now >= $regStart ? 'check' : 'clock' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>등록 시작</h4>
                                    <p class="timeline-date"><?= date('Y년 m월 d일 H:i', $regStart) ?></p>
                                    <p class="timeline-desc">참가 등록이 시작됩니다.</p>
                                </div>
                            </div>

                            <div class="timeline-item <?= $now >= $regEnd ? 'completed' : ($now >= $regStart && $now < $regEnd ? 'active' : 'upcoming') ?>">
                                <div class="timeline-marker">
                                    <i class="fas fa-<?= $now >= $regEnd ? 'check' : 'user-plus' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>등록 마감</h4>
                                    <p class="timeline-date"><?= date('Y년 m월 d일 H:i', $regEnd) ?></p>
                                    <p class="timeline-desc">이후 신규 참가가 불가능합니다.</p>
                                </div>
                            </div>

                            <div class="timeline-item <?= $now >= $startTime ? 'completed' : ($now >= $regEnd && $now < $startTime ? 'active' : 'upcoming') ?>">
                                <div class="timeline-marker">
                                    <i class="fas fa-<?= $now >= $startTime ? 'check' : 'play' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>대회 시작</h4>
                                    <p class="timeline-date"><?= date('Y년 m월 d일 H:i', $startTime) ?></p>
                                    <p class="timeline-desc">거래가 시작되며 순위가 집계됩니다.</p>
                                </div>
                            </div>

                            <div class="timeline-item <?= $now >= $endTime ? 'completed' : ($now >= $startTime && $now < $endTime ? 'active' : 'upcoming') ?>">
                                <div class="timeline-marker">
                                    <i class="fas fa-<?= $now >= $endTime ? 'check' : 'flag-checkered' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>대회 종료</h4>
                                    <p class="timeline-date"><?= date('Y년 m월 d일 H:i', $endTime) ?></p>
                                    <p class="timeline-desc">최종 순위가 확정됩니다.</p>
                                </div>
                            </div>

                            <div class="timeline-item <?= $tournament['status'] === 'ended' ? 'completed' : 'upcoming' ?>">
                                <div class="timeline-marker">
                                    <i class="fas fa-<?= $tournament['status'] === 'ended' ? 'check' : 'gift' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>상금 지급</h4>
                                    <p class="timeline-date"><?= date('Y년 m월 d일 H:i', $endTime + 86400) ?> 이내</p>
                                    <p class="timeline-desc">수상자에게 상금이 지급됩니다.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
    // 카운트다운
    function initCountdown() {
        const countdown = document.getElementById('countdown');
        if (!countdown) return;

        const targetTime = parseInt(countdown.dataset.target) * 1000;

        function updateCountdown() {
            const now = Date.now();
            const diff = targetTime - now;

            if (diff <= 0) {
                location.reload();
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            countdown.querySelector('[data-days]').textContent = String(days).padStart(2, '0');
            countdown.querySelector('[data-hours]').textContent = String(hours).padStart(2, '0');
            countdown.querySelector('[data-minutes]').textContent = String(minutes).padStart(2, '0');
            countdown.querySelector('[data-seconds]').textContent = String(seconds).padStart(2, '0');
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // 탭 전환
    function initTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.dataset.tab;

                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                btn.classList.add('active');
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
    }

    // 참가 신청
    function initJoinButton() {
        const joinBtn = document.getElementById('joinTournamentBtn');
        if (!joinBtn) return;

        joinBtn.addEventListener('click', async () => {
            if (!confirm('이 토너먼트에 참가하시겠습니까?')) return;

            joinBtn.disabled = true;
            joinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 처리중...';

            try {
                const response = await fetch('api/tournament.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'join',
                        tournament_id: '<?= $tournamentId ?>'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('참가 신청이 완료되었습니다!');
                    location.reload();
                } else {
                    alert(data.error || '참가 신청에 실패했습니다.');
                    joinBtn.disabled = false;
                    joinBtn.innerHTML = '<i class="fas fa-user-plus"></i> 참가 신청하기';
                }
            } catch (error) {
                alert('오류가 발생했습니다. 다시 시도해주세요.');
                joinBtn.disabled = false;
                joinBtn.innerHTML = '<i class="fas fa-user-plus"></i> 참가 신청하기';
            }
        });
    }

    // 초기화
    document.addEventListener('DOMContentLoaded', () => {
        initCountdown();
        initTabs();
        initJoinButton();
    });
    </script>

<?php require_once 'includes/footer.php'; ?>
