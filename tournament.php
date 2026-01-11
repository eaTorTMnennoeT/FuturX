<?php
/**
 * FuturX - 토너먼트(대회) 메인 페이지
 */
$pageTitle = '트레이딩 토너먼트';
$pageCSS = ['tournament.css'];
require_once 'includes/head.php';
require_once 'includes/header.php';
require_once 'config.php';

$db = getDB();

// 진행중인 대회
$activeStmt = $db->query("
    SELECT * FROM tournaments
    WHERE status IN ('active', 'registration')
    ORDER BY
        CASE status
            WHEN 'active' THEN 1
            WHEN 'registration' THEN 2
        END,
        start_time ASC
    LIMIT 5
");
$activeTournaments = $activeStmt->fetchAll();

// 예정된 대회
$upcomingStmt = $db->query("
    SELECT * FROM tournaments
    WHERE status = 'upcoming'
    ORDER BY start_time ASC
    LIMIT 5
");
$upcomingTournaments = $upcomingStmt->fetchAll();

// 종료된 대회
$endedStmt = $db->query("
    SELECT * FROM tournaments
    WHERE status = 'ended'
    ORDER BY end_time DESC
    LIMIT 10
");
$endedTournaments = $endedStmt->fetchAll();

// 공지사항
$announcementsStmt = $db->query("
    SELECT * FROM tournament_announcements
    WHERE is_pinned = 1 OR created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY is_pinned DESC, created_at DESC
    LIMIT 5
");
$announcements = $announcementsStmt->fetchAll();

// 전체 통계
$statsStmt = $db->query("
    SELECT
        COUNT(DISTINCT t.id) as total_tournaments,
        SUM(t.prize_pool) as total_prize,
        SUM(t.total_participants) as total_participants,
        SUM(t.total_volume) as total_volume
    FROM tournaments t
");
$globalStats = $statsStmt->fetch();

// 뱃지 아이콘 매핑
function getBadgeIcon($icon) {
    $icons = [
        'trophy' => '<i class="fas fa-trophy"></i>',
        'crown' => '<i class="fas fa-crown"></i>',
        'bolt' => '<i class="fas fa-bolt"></i>',
        'gift' => '<i class="fas fa-gift"></i>',
        'star' => '<i class="fas fa-star"></i>',
        'medal' => '<i class="fas fa-medal"></i>',
        'fire' => '<i class="fas fa-fire"></i>',
    ];
    return $icons[$icon] ?? '<i class="fas fa-trophy"></i>';
}

// 상태 라벨
function getStatusLabel($status) {
    $labels = [
        'upcoming' => ['text' => '예정', 'class' => 'upcoming'],
        'registration' => ['text' => '등록중', 'class' => 'registration'],
        'active' => ['text' => '진행중', 'class' => 'active'],
        'calculating' => ['text' => '정산중', 'class' => 'calculating'],
        'ended' => ['text' => '종료', 'class' => 'ended'],
    ];
    return $labels[$status] ?? ['text' => $status, 'class' => ''];
}

// 타입 라벨
function getTypeLabel($type) {
    $labels = [
        'daily' => '일간',
        'weekly' => '주간',
        'monthly' => '월간',
        'special' => '스페셜',
    ];
    return $labels[$type] ?? $type;
}

// 남은 시간 계산
function getTimeRemaining($endTime) {
    $end = strtotime($endTime);
    $now = time();
    $diff = $end - $now;

    if ($diff <= 0) return '종료됨';

    $days = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    $mins = floor(($diff % 3600) / 60);

    if ($days > 0) return "{$days}일 {$hours}시간";
    if ($hours > 0) return "{$hours}시간 {$mins}분";
    return "{$mins}분";
}
?>

    <main class="main tournament-page">
        <!-- 히어로 배너 -->
        <section class="tournament-hero">
            <div class="tournament-hero__bg">
                <div class="hero-particles" id="heroParticles"></div>
                <div class="hero-glow"></div>
            </div>
            <div class="container">
                <div class="tournament-hero__content">
                    <div class="tournament-hero__badge">
                        <i class="fas fa-trophy"></i>
                        <span>TRADING TOURNAMENT</span>
                    </div>
                    <h1 class="tournament-hero__title">
                        <span class="gradient-text">트레이딩 실력을</span>
                        <br>증명하세요
                    </h1>
                    <p class="tournament-hero__desc">
                        전 세계 트레이더들과 경쟁하고 막대한 상금을 획득하세요.<br>
                        매일, 매주, 매월 다양한 토너먼트가 진행됩니다.
                    </p>
                    <div class="tournament-hero__stats">
                        <div class="hero-stat">
                            <span class="hero-stat__value" data-count="<?= $globalStats['total_prize'] ?? 0 ?>">$<?= number_format($globalStats['total_prize'] ?? 0) ?></span>
                            <span class="hero-stat__label">총 상금 지급</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat__value"><?= number_format($globalStats['total_participants'] ?? 0) ?>+</span>
                            <span class="hero-stat__label">누적 참가자</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat__value"><?= $globalStats['total_tournaments'] ?? 0 ?></span>
                            <span class="hero-stat__label">진행된 대회</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 공지사항 띠배너 -->
        <?php if (!empty($announcements)): ?>
        <div class="announcement-ticker">
            <div class="container">
                <div class="announcement-ticker__inner">
                    <span class="announcement-ticker__label">
                        <i class="fas fa-bullhorn"></i> 공지
                    </span>
                    <div class="announcement-ticker__content">
                        <?php foreach ($announcements as $ann): ?>
                        <span class="announcement-ticker__item"><?= htmlspecialchars($ann['title']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 필터 탭 -->
        <section class="tournament-filters">
            <div class="container">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> 전체
                    </button>
                    <button class="filter-tab" data-filter="active">
                        <i class="fas fa-play-circle"></i> 진행중
                    </button>
                    <button class="filter-tab" data-filter="registration">
                        <i class="fas fa-user-plus"></i> 등록중
                    </button>
                    <button class="filter-tab" data-filter="upcoming">
                        <i class="fas fa-clock"></i> 예정
                    </button>
                    <button class="filter-tab" data-filter="ended">
                        <i class="fas fa-flag-checkered"></i> 종료
                    </button>
                </div>
                <div class="filter-sort">
                    <select id="sortSelect">
                        <option value="prize">상금순</option>
                        <option value="time">시작일순</option>
                        <option value="participants">참가자순</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- 진행중/등록중 대회 (메인) -->
        <?php if (!empty($activeTournaments)): ?>
        <section class="tournament-section tournament-section--featured">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="live-dot"></span>
                        진행중인 대회
                    </h2>
                    <a href="#" class="section-link">전체보기 <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="tournament-featured-grid">
                    <?php foreach ($activeTournaments as $idx => $t):
                        $status = getStatusLabel($t['status']);
                        $prizeData = json_decode($t['prize_distribution'], true);
                        $isMain = ($idx === 0);
                    ?>
                    <div class="tournament-card <?= $isMain ? 'tournament-card--main' : '' ?>" data-status="<?= $t['status'] ?>">
                        <div class="tournament-card__badge" style="background: <?= $t['badge_color'] ?>;">
                            <?= getBadgeIcon($t['badge_icon']) ?>
                        </div>

                        <div class="tournament-card__header">
                            <span class="tournament-card__type"><?= getTypeLabel($t['type']) ?></span>
                            <span class="tournament-card__status tournament-card__status--<?= $status['class'] ?>">
                                <?= $status['text'] ?>
                            </span>
                        </div>

                        <h3 class="tournament-card__title"><?= htmlspecialchars($t['title']) ?></h3>
                        <p class="tournament-card__subtitle"><?= htmlspecialchars($t['subtitle']) ?></p>

                        <div class="tournament-card__prize">
                            <span class="prize-label">총 상금</span>
                            <span class="prize-amount">
                                <span class="prize-currency">$</span>
                                <span class="prize-value"><?= number_format($t['prize_pool']) ?></span>
                                <span class="prize-unit"><?= $t['prize_currency'] ?></span>
                            </span>
                        </div>

                        <?php if ($isMain && $prizeData): ?>
                        <div class="tournament-card__rewards">
                            <?php foreach (array_slice($prizeData, 0, 3) as $i => $prize): ?>
                            <div class="reward-tier reward-tier--<?= $i + 1 ?>">
                                <span class="reward-tier__rank">
                                    <?php if ($i === 0): ?>
                                    <i class="fas fa-crown"></i>
                                    <?php else: ?>
                                    <?= $prize['rank'] ?? ($prize['rank_from'] . '-' . $prize['rank_to']) ?>위
                                    <?php endif; ?>
                                </span>
                                <span class="reward-tier__amount">
                                    <?php if (isset($prize['amount'])): ?>
                                    $<?= number_format($prize['amount']) ?>
                                    <?php else: ?>
                                    <?= $prize['percent'] ?>%
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="tournament-card__info">
                            <div class="info-item">
                                <i class="fas fa-users"></i>
                                <span><?= number_format($t['total_participants']) ?>명 참가</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>
                                    <?php if ($t['status'] === 'active'): ?>
                                    <?= getTimeRemaining($t['end_time']) ?> 남음
                                    <?php elseif ($t['status'] === 'registration'): ?>
                                    <?= getTimeRemaining($t['registration_end']) ?> 내 등록
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-wallet"></i>
                                <span>
                                    <?php if ($t['entry_fee'] > 0): ?>
                                    참가비 $<?= number_format($t['entry_fee']) ?>
                                    <?php else: ?>
                                    무료 참가
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div class="tournament-card__progress">
                            <?php if ($t['status'] === 'active'):
                                $start = strtotime($t['start_time']);
                                $end = strtotime($t['end_time']);
                                $now = time();
                                $progress = min(100, max(0, (($now - $start) / ($end - $start)) * 100));
                            ?>
                            <div class="progress-bar">
                                <div class="progress-bar__fill" style="width: <?= $progress ?>%"></div>
                            </div>
                            <div class="progress-labels">
                                <span><?= date('m/d H:i', $start) ?></span>
                                <span><?= round($progress) ?>% 진행</span>
                                <span><?= date('m/d H:i', $end) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="tournament-card__actions">
                            <a href="tournament_detail.php?id=<?= $t['tournament_id'] ?>" class="btn btn--primary btn--block">
                                <?php if ($t['status'] === 'registration'): ?>
                                <i class="fas fa-user-plus"></i> 참가 신청
                                <?php else: ?>
                                <i class="fas fa-chart-line"></i> 자세히 보기
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 실시간 리더보드 미리보기 -->
        <section class="tournament-section tournament-section--leaderboard">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i>
                        실시간 리더보드
                    </h2>
                    <div class="leaderboard-tabs">
                        <?php foreach (array_slice($activeTournaments, 0, 3) as $idx => $t): ?>
                        <button class="leaderboard-tab <?= $idx === 0 ? 'active' : '' ?>" data-tournament="<?= $t['tournament_id'] ?>">
                            <?= htmlspecialchars(mb_substr($t['title'], 0, 10)) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="leaderboard-preview">
                    <div class="leaderboard-podium">
                        <div class="podium-item podium-item--2">
                            <div class="podium-avatar">
                                <img src="https://api.dicebear.com/7.x/identicon/svg?seed=user2" alt="">
                            </div>
                            <div class="podium-info">
                                <span class="podium-name">Trader***2</span>
                                <span class="podium-pnl num-positive">+18.00%</span>
                            </div>
                            <div class="podium-rank">2</div>
                        </div>
                        <div class="podium-item podium-item--1">
                            <div class="podium-crown"><i class="fas fa-crown"></i></div>
                            <div class="podium-avatar">
                                <img src="https://api.dicebear.com/7.x/identicon/svg?seed=user1" alt="">
                            </div>
                            <div class="podium-info">
                                <span class="podium-name">Trader***1</span>
                                <span class="podium-pnl num-positive">+25.00%</span>
                            </div>
                            <div class="podium-rank">1</div>
                        </div>
                        <div class="podium-item podium-item--3">
                            <div class="podium-avatar">
                                <img src="https://api.dicebear.com/7.x/identicon/svg?seed=user3" alt="">
                            </div>
                            <div class="podium-info">
                                <span class="podium-name">Trader***3</span>
                                <span class="podium-pnl num-positive">+12.00%</span>
                            </div>
                            <div class="podium-rank">3</div>
                        </div>
                    </div>

                    <div class="leaderboard-table-wrapper">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>순위</th>
                                    <th>트레이더</th>
                                    <th>수익률</th>
                                    <th>거래수</th>
                                    <th>거래량</th>
                                </tr>
                            </thead>
                            <tbody id="leaderboardBody">
                                <!-- JS로 로드 -->
                            </tbody>
                        </table>
                    </div>

                    <div class="leaderboard-footer">
                        <a href="#" class="btn btn--secondary">전체 순위 보기 <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </section>

        <!-- 예정된 대회 -->
        <?php if (!empty($upcomingTournaments)): ?>
        <section class="tournament-section tournament-section--upcoming">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-alt"></i>
                        예정된 대회
                    </h2>
                </div>

                <div class="tournament-list">
                    <?php foreach ($upcomingTournaments as $t):
                        $status = getStatusLabel($t['status']);
                    ?>
                    <div class="tournament-list-item" data-status="<?= $t['status'] ?>">
                        <div class="tournament-list-item__badge" style="background: <?= $t['badge_color'] ?>;">
                            <?= getBadgeIcon($t['badge_icon']) ?>
                        </div>
                        <div class="tournament-list-item__info">
                            <div class="tournament-list-item__meta">
                                <span class="type-badge"><?= getTypeLabel($t['type']) ?></span>
                                <span class="status-badge status-badge--<?= $status['class'] ?>"><?= $status['text'] ?></span>
                            </div>
                            <h4 class="tournament-list-item__title"><?= htmlspecialchars($t['title']) ?></h4>
                            <p class="tournament-list-item__subtitle"><?= htmlspecialchars($t['subtitle']) ?></p>
                        </div>
                        <div class="tournament-list-item__details">
                            <div class="detail-item">
                                <span class="detail-label">시작</span>
                                <span class="detail-value"><?= date('m/d H:i', strtotime($t['start_time'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">상금</span>
                                <span class="detail-value prize">$<?= number_format($t['prize_pool']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">참가비</span>
                                <span class="detail-value"><?= $t['entry_fee'] > 0 ? '$' . number_format($t['entry_fee']) : '무료' ?></span>
                            </div>
                        </div>
                        <div class="tournament-list-item__action">
                            <a href="tournament_detail.php?id=<?= $t['tournament_id'] ?>" class="btn btn--secondary">
                                <i class="fas fa-bell"></i> 알림 설정
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 보상 안내 -->
        <section class="tournament-section tournament-section--rewards">
            <div class="container">
                <div class="section-header section-header--center">
                    <h2 class="section-title">다양한 보상 시스템</h2>
                    <p class="section-desc">순위에 따른 상금 외에도 다양한 보상이 준비되어 있습니다</p>
                </div>

                <div class="rewards-grid">
                    <div class="reward-card reward-card--prize">
                        <div class="reward-card__icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h3 class="reward-card__title">상금 보상</h3>
                        <p class="reward-card__desc">순위별로 상금풀의 일정 비율을 분배합니다</p>
                        <div class="reward-card__example">
                            <div class="example-row">
                                <span class="rank rank--1"><i class="fas fa-crown"></i> 1위</span>
                                <span class="percent">40%</span>
                            </div>
                            <div class="example-row">
                                <span class="rank rank--2"><i class="fas fa-medal"></i> 2위</span>
                                <span class="percent">25%</span>
                            </div>
                            <div class="example-row">
                                <span class="rank rank--3"><i class="fas fa-medal"></i> 3위</span>
                                <span class="percent">15%</span>
                            </div>
                            <div class="example-row">
                                <span class="rank">4~10위</span>
                                <span class="percent">15%</span>
                            </div>
                            <div class="example-row">
                                <span class="rank">11~50위</span>
                                <span class="percent">5%</span>
                            </div>
                        </div>
                    </div>

                    <div class="reward-card reward-card--badge">
                        <div class="reward-card__icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h3 class="reward-card__title">뱃지 & 칭호</h3>
                        <p class="reward-card__desc">우승자에게는 특별한 뱃지와 칭호가 부여됩니다</p>
                        <div class="reward-card__badges">
                            <div class="badge-item badge-item--gold">
                                <i class="fas fa-trophy"></i>
                                <span>챔피언</span>
                            </div>
                            <div class="badge-item badge-item--silver">
                                <i class="fas fa-medal"></i>
                                <span>준우승</span>
                            </div>
                            <div class="badge-item badge-item--bronze">
                                <i class="fas fa-award"></i>
                                <span>Top 3</span>
                            </div>
                            <div class="badge-item badge-item--special">
                                <i class="fas fa-fire"></i>
                                <span>연승왕</span>
                            </div>
                        </div>
                    </div>

                    <div class="reward-card reward-card--vip">
                        <div class="reward-card__icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h3 class="reward-card__title">VIP 혜택</h3>
                        <p class="reward-card__desc">우수 성적자에게 VIP 등급 혜택 제공</p>
                        <ul class="reward-card__list">
                            <li><i class="fas fa-check"></i> 수수료 할인 (최대 50%)</li>
                            <li><i class="fas fa-check"></i> 전용 고객 지원</li>
                            <li><i class="fas fa-check"></i> 우선 출금 처리</li>
                            <li><i class="fas fa-check"></i> 특별 이벤트 초대</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- 종료된 대회 -->
        <?php if (!empty($endedTournaments)): ?>
        <section class="tournament-section tournament-section--ended">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i>
                        지난 대회 결과
                    </h2>
                </div>

                <div class="ended-tournaments-grid">
                    <?php foreach ($endedTournaments as $t): ?>
                    <div class="ended-card" data-status="ended">
                        <div class="ended-card__header">
                            <span class="type-badge"><?= getTypeLabel($t['type']) ?></span>
                            <span class="ended-date"><?= date('Y.m.d', strtotime($t['end_time'])) ?></span>
                        </div>
                        <h4 class="ended-card__title"><?= htmlspecialchars($t['title']) ?></h4>
                        <div class="ended-card__stats">
                            <div class="stat">
                                <span class="stat-value"><?= number_format($t['total_participants']) ?></span>
                                <span class="stat-label">참가자</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">$<?= number_format($t['prize_pool']) ?></span>
                                <span class="stat-label">상금</span>
                            </div>
                        </div>
                        <a href="tournament_detail.php?id=<?= $t['tournament_id'] ?>" class="ended-card__link">
                            결과 보기 <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CTA 섹션 -->
        <section class="tournament-cta">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">지금 바로 도전하세요!</h2>
                    <p class="cta-desc">무료로 참가할 수 있는 토너먼트로 시작해보세요</p>
                    <div class="cta-buttons">
                        <a href="trade.php" class="btn btn--primary btn--lg">
                            <i class="fas fa-rocket"></i> 거래 시작하기
                        </a>
                        <a href="#" class="btn btn--secondary btn--lg">
                            <i class="fas fa-question-circle"></i> 도움말 보기
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
    // 리더보드 데이터 로드
    async function loadLeaderboard(tournamentId) {
        try {
            const response = await fetch(`api/tournament.php?action=leaderboard&tournament_id=${tournamentId}&limit=10`);
            const data = await response.json();

            if (data.success) {
                renderLeaderboard(data.data);
            }
        } catch (error) {
            console.error('리더보드 로드 실패:', error);
            renderSampleLeaderboard();
        }
    }

    // 샘플 리더보드 (API 없을 때)
    function renderSampleLeaderboard() {
        const sampleData = [
            { rank: 4, username: 'Trader***4', pnl_percent: 8.00, total_trades: 28, total_volume: 420000 },
            { rank: 5, username: 'Trader***5', pnl_percent: 5.00, total_trades: 41, total_volume: 550000 },
            { rank: 6, username: 'Trader***6', pnl_percent: 2.00, total_trades: 19, total_volume: 280000 },
            { rank: 7, username: 'Trader***7', pnl_percent: -2.00, total_trades: 33, total_volume: 470000 },
            { rank: 8, username: 'Trader***8', pnl_percent: -5.00, total_trades: 25, total_volume: 380000 },
            { rank: 9, username: 'Trader***9', pnl_percent: -8.00, total_trades: 44, total_volume: 720000 },
            { rank: 10, username: 'Trader***10', pnl_percent: -12.00, total_trades: 38, total_volume: 510000 },
        ];
        renderLeaderboard(sampleData);
    }

    // 리더보드 렌더링
    function renderLeaderboard(data) {
        const tbody = document.getElementById('leaderboardBody');
        if (!tbody) return;

        tbody.innerHTML = data.map(item => `
            <tr>
                <td>
                    <span class="rank-num ${item.rank <= 3 ? 'rank-num--top' : ''}">${item.rank}</span>
                </td>
                <td>
                    <div class="trader-cell">
                        <img src="https://api.dicebear.com/7.x/identicon/svg?seed=user${item.rank}" class="trader-avatar" alt="">
                        <span class="trader-name">${item.username || 'Trader***' + item.rank}</span>
                    </div>
                </td>
                <td>
                    <span class="${item.pnl_percent >= 0 ? 'num-positive' : 'num-negative'}">
                        ${item.pnl_percent >= 0 ? '+' : ''}${item.pnl_percent.toFixed(2)}%
                    </span>
                </td>
                <td>${item.total_trades}</td>
                <td>$${formatVolume(item.total_volume)}</td>
            </tr>
        `).join('');
    }

    // 볼륨 포맷
    function formatVolume(vol) {
        if (vol >= 1e6) return (vol / 1e6).toFixed(2) + 'M';
        if (vol >= 1e3) return (vol / 1e3).toFixed(1) + 'K';
        return vol.toFixed(0);
    }

    // 필터 탭
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const filter = this.dataset.filter;
            filterTournaments(filter);
        });
    });

    // 토너먼트 필터링
    function filterTournaments(filter) {
        const cards = document.querySelectorAll('[data-status]');
        cards.forEach(card => {
            if (filter === 'all' || card.dataset.status === filter) {
                card.style.display = '';
                card.style.animation = 'fadeIn 0.3s ease';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // 리더보드 탭
    document.querySelectorAll('.leaderboard-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.leaderboard-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const tournamentId = this.dataset.tournament;
            loadLeaderboard(tournamentId);
        });
    });

    // 파티클 애니메이션
    function createHeroParticles() {
        const container = document.getElementById('heroParticles');
        if (!container) return;

        for (let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.className = 'hero-particle';
            particle.style.cssText = `
                position: absolute;
                width: ${Math.random() * 4 + 2}px;
                height: ${Math.random() * 4 + 2}px;
                background: rgba(240, 185, 11, ${Math.random() * 0.5 + 0.2});
                border-radius: 50%;
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                animation: floatParticle ${Math.random() * 10 + 10}s linear infinite;
                animation-delay: -${Math.random() * 10}s;
            `;
            container.appendChild(particle);
        }
    }

    // 카운트업 애니메이션
    function animateCountUp() {
        const counters = document.querySelectorAll('[data-count]');
        counters.forEach(counter => {
            const target = parseFloat(counter.dataset.count);
            const duration = 2000;
            const start = performance.now();

            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);

                counter.textContent = '$' + Math.floor(target * eased).toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }

            requestAnimationFrame(update);
        });
    }

    // 공지사항 티커 애니메이션
    function initAnnouncementTicker() {
        const content = document.querySelector('.announcement-ticker__content');
        if (!content) return;

        const items = content.querySelectorAll('.announcement-ticker__item');
        if (items.length <= 1) return;

        let currentIndex = 0;

        setInterval(() => {
            items[currentIndex].style.opacity = '0';
            currentIndex = (currentIndex + 1) % items.length;
            items[currentIndex].style.opacity = '1';
        }, 4000);
    }

    // 초기화
    document.addEventListener('DOMContentLoaded', () => {
        createHeroParticles();
        animateCountUp();
        initAnnouncementTicker();
        renderSampleLeaderboard();
    });
    </script>

<?php require_once 'includes/footer.php'; ?>
