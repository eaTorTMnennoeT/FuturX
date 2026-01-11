<?php
/**
 * FuturX - 대회 페이지
 * 트레이딩 대회, 보상, 순위, 마감 타이머
 */

require_once 'config.php';

try {
    $db = getDB();

    // 진행중인 대회 조회
    $activeSQL = "SELECT c.*,
                    (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id) as participant_count
                  FROM competitions c
                  WHERE c.status = 'active'
                  ORDER BY c.end_at ASC";
    $activeCompetitions = $db->query($activeSQL)->fetchAll(PDO::FETCH_ASSOC);

    // 예정된 대회 조회
    $upcomingSQL = "SELECT c.*,
                      (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id) as participant_count
                    FROM competitions c
                    WHERE c.status = 'upcoming'
                    ORDER BY c.start_at ASC
                    LIMIT 6";
    $upcomingCompetitions = $db->query($upcomingSQL)->fetchAll(PDO::FETCH_ASSOC);

    // 종료된 대회 조회
    $endedSQL = "SELECT c.*,
                    (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id) as participant_count
                 FROM competitions c
                 WHERE c.status = 'ended'
                 ORDER BY c.end_at DESC
                 LIMIT 6";
    $endedCompetitions = $db->query($endedSQL)->fetchAll(PDO::FETCH_ASSOC);

    // 대회 상세 보기 (ID가 있을 경우)
    $competitionDetail = null;
    $prizes = [];
    $leaderboard = [];

    if (isset($_GET['id'])) {
        $compId = (int)$_GET['id'];

        // 대회 정보
        $detailSQL = "SELECT c.*,
                        (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id) as participant_count
                      FROM competitions c
                      WHERE c.id = :id";
        $stmt = $db->prepare($detailSQL);
        $stmt->execute(['id' => $compId]);
        $competitionDetail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($competitionDetail) {
            // 상금 정보
            $prizeSQL = "SELECT * FROM competition_prizes WHERE competition_id = :id ORDER BY rank_from ASC";
            $stmt = $db->prepare($prizeSQL);
            $stmt->execute(['id' => $compId]);
            $prizes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 순위표
            $leaderSQL = "SELECT cp.*, u.username, u.nickname, u.avatar, u.vip_level
                          FROM competition_participants cp
                          JOIN users u ON cp.user_id = u.id
                          WHERE cp.competition_id = :id AND cp.status != 'disqualified'
                          ORDER BY cp.current_rank ASC
                          LIMIT 100";
            $stmt = $db->prepare($leaderSQL);
            $stmt->execute(['id' => $compId]);
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    $activeCompetitions = [];
    $upcomingCompetitions = [];
    $endedCompetitions = [];
}

// 대회 타입 라벨
$typeLabels = [
    'roi' => 'ROI 대회',
    'pnl' => 'PnL 대회',
    'volume' => '거래량 대회',
    'team' => '팀 대회'
];

// 상금 타입 아이콘
$prizeIcons = [
    'usdt' => 'fa-dollar-sign',
    'bonus' => 'fa-gift',
    'nft' => 'fa-image',
    'other' => 'fa-trophy'
];
?>
<?php
$pageTitle = ($competitionDetail ? htmlspecialchars($competitionDetail['title']) . ' - ' : '') . '대회';
$pageCSS = ['css/competition.css'];
require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 컨텐츠 -->
    <main class="main">
        <?php if ($competitionDetail): ?>
        <!-- 대회 상세 페이지 -->
        <section class="competition-detail">
            <!-- 배너 -->
            <div class="competition-banner">
                <?php if ($competitionDetail['banner_image']): ?>
                    <img src="<?= htmlspecialchars($competitionDetail['banner_image']) ?>" alt="">
                <?php else: ?>
                    <div class="competition-banner__gradient"></div>
                <?php endif; ?>
                <div class="competition-banner__overlay"></div>
                <div class="container">
                    <div class="competition-banner__content">
                        <div class="competition-badge competition-badge--<?= $competitionDetail['status'] ?>">
                            <?= $competitionDetail['status'] === 'active' ? '진행중' : ($competitionDetail['status'] === 'upcoming' ? '예정' : '종료') ?>
                        </div>
                        <h1 class="competition-banner__title"><?= htmlspecialchars($competitionDetail['title']) ?></h1>
                        <p class="competition-banner__type">
                            <i class="fas fa-chart-line"></i>
                            <?= $typeLabels[$competitionDetail['type']] ?? $competitionDetail['type'] ?>
                        </p>

                        <!-- 카운트다운 타이머 -->
                        <div class="countdown" id="countdown" data-end="<?= $competitionDetail['end_at'] ?>">
                            <div class="countdown__item">
                                <span class="countdown__value" id="days">00</span>
                                <span class="countdown__label">일</span>
                            </div>
                            <div class="countdown__separator">:</div>
                            <div class="countdown__item">
                                <span class="countdown__value" id="hours">00</span>
                                <span class="countdown__label">시간</span>
                            </div>
                            <div class="countdown__separator">:</div>
                            <div class="countdown__item">
                                <span class="countdown__value" id="minutes">00</span>
                                <span class="countdown__label">분</span>
                            </div>
                            <div class="countdown__separator">:</div>
                            <div class="countdown__item">
                                <span class="countdown__value" id="seconds">00</span>
                                <span class="countdown__label">초</span>
                            </div>
                        </div>

                        <?php if ($competitionDetail['status'] === 'active' || $competitionDetail['status'] === 'upcoming'): ?>
                        <button class="btn btn--primary btn--lg" id="joinBtn">
                            <i class="fas fa-user-plus"></i>
                            대회 참가하기
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="container">
                <div class="competition-layout">
                    <!-- 왼쪽: 정보 및 순위 -->
                    <div class="competition-main">
                        <!-- 대회 정보 카드 -->
                        <div class="info-cards">
                            <div class="info-card">
                                <div class="info-card__icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="info-card__content">
                                    <span class="info-card__label">총 상금</span>
                                    <span class="info-card__value">
                                        $<?= number_format($competitionDetail['prize_pool'], 0) ?>
                                        <small><?= $competitionDetail['prize_currency'] ?></small>
                                    </span>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card__icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="info-card__content">
                                    <span class="info-card__label">참가자</span>
                                    <span class="info-card__value">
                                        <?= number_format($competitionDetail['participant_count']) ?>
                                        <?php if ($competitionDetail['max_participants']): ?>
                                        <small>/ <?= number_format($competitionDetail['max_participants']) ?></small>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card__icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="info-card__content">
                                    <span class="info-card__label">참가비</span>
                                    <span class="info-card__value">
                                        <?= $competitionDetail['entry_fee'] > 0 ? '$' . number_format($competitionDetail['entry_fee'], 2) : '무료' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card__icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="info-card__content">
                                    <span class="info-card__label">최소 잔액</span>
                                    <span class="info-card__value">
                                        $<?= number_format($competitionDetail['min_balance'], 0) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 순위표 -->
                        <div class="card">
                            <div class="card__header">
                                <h2 class="card__title">
                                    <i class="fas fa-medal"></i>
                                    실시간 순위
                                </h2>
                                <span class="update-time">
                                    <i class="fas fa-sync-alt"></i>
                                    실시간 업데이트
                                </span>
                            </div>
                            <div class="card__body">
                                <?php if (empty($leaderboard)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>아직 참가자가 없습니다</p>
                                </div>
                                <?php else: ?>
                                <table class="rank-table">
                                    <thead>
                                        <tr>
                                            <th>순위</th>
                                            <th>트레이더</th>
                                            <th>PnL</th>
                                            <th>ROI</th>
                                            <th>거래 수</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leaderboard as $index => $participant): ?>
                                        <tr>
                                            <td>
                                                <?php if ($index < 3): ?>
                                                <span class="rank-medal rank-medal--<?= ['gold', 'silver', 'bronze'][$index] ?>">
                                                    <?= $index + 1 ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="rank-num"><?= $participant['current_rank'] ?: $index + 1 ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="trader-cell">
                                                    <div class="trader-cell__avatar">
                                                        <?php if ($participant['avatar']): ?>
                                                            <img src="<?= htmlspecialchars($participant['avatar']) ?>" alt="">
                                                        <?php else: ?>
                                                            <i class="fas fa-user"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="trader-cell__name">
                                                        <?= htmlspecialchars($participant['nickname'] ?: $participant['username']) ?>
                                                    </span>
                                                    <?php if ($participant['vip_level'] > 0): ?>
                                                    <span class="vip-badge">VIP</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="<?= $participant['pnl'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                                <?= $participant['pnl'] >= 0 ? '+' : '' ?>$<?= number_format($participant['pnl'], 2) ?>
                                            </td>
                                            <td class="<?= $participant['roi'] >= 0 ? 'num-positive' : 'num-negative' ?>">
                                                <?= $participant['roi'] >= 0 ? '+' : '' ?><?= number_format($participant['roi'], 2) ?>%
                                            </td>
                                            <td><?= number_format($participant['trade_count']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 규칙 -->
                        <?php if ($competitionDetail['rules']): ?>
                        <div class="card">
                            <div class="card__header">
                                <h2 class="card__title">
                                    <i class="fas fa-book"></i>
                                    대회 규칙
                                </h2>
                            </div>
                            <div class="card__body">
                                <div class="rules-content">
                                    <?= nl2br(htmlspecialchars($competitionDetail['rules'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 오른쪽: 상금 정보 -->
                    <div class="competition-sidebar">
                        <!-- 상금 구조 -->
                        <div class="prize-card">
                            <div class="prize-card__header">
                                <i class="fas fa-gift"></i>
                                <span>상금 구조</span>
                            </div>
                            <div class="prize-card__total">
                                <span class="prize-card__total-label">총 상금</span>
                                <span class="prize-card__total-value">
                                    $<?= number_format($competitionDetail['prize_pool'], 0) ?>
                                </span>
                            </div>
                            <div class="prize-tiers">
                                <?php if (empty($prizes)): ?>
                                <div class="prize-tier prize-tier--1">
                                    <div class="prize-tier__rank">
                                        <i class="fas fa-crown"></i>
                                        <span>1위</span>
                                    </div>
                                    <div class="prize-tier__amount">
                                        $<?= number_format($competitionDetail['prize_pool'] * 0.5, 0) ?>
                                    </div>
                                </div>
                                <div class="prize-tier prize-tier--2">
                                    <div class="prize-tier__rank">
                                        <i class="fas fa-medal"></i>
                                        <span>2위</span>
                                    </div>
                                    <div class="prize-tier__amount">
                                        $<?= number_format($competitionDetail['prize_pool'] * 0.3, 0) ?>
                                    </div>
                                </div>
                                <div class="prize-tier prize-tier--3">
                                    <div class="prize-tier__rank">
                                        <i class="fas fa-award"></i>
                                        <span>3위</span>
                                    </div>
                                    <div class="prize-tier__amount">
                                        $<?= number_format($competitionDetail['prize_pool'] * 0.2, 0) ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php foreach ($prizes as $prize): ?>
                                <div class="prize-tier">
                                    <div class="prize-tier__rank">
                                        <i class="fas <?= $prizeIcons[$prize['prize_type']] ?? 'fa-trophy' ?>"></i>
                                        <span>
                                            <?php if ($prize['rank_from'] === $prize['rank_to']): ?>
                                                <?= $prize['rank_from'] ?>위
                                            <?php else: ?>
                                                <?= $prize['rank_from'] ?>~<?= $prize['rank_to'] ?>위
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="prize-tier__amount">
                                        <?php if ($prize['prize_type'] === 'usdt'): ?>
                                            $<?= number_format($prize['prize_amount'], 0) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($prize['prize_description'] ?: $prize['prize_amount']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 대회 일정 -->
                        <div class="schedule-card">
                            <div class="schedule-card__header">
                                <i class="fas fa-calendar-alt"></i>
                                <span>대회 일정</span>
                            </div>
                            <div class="schedule-items">
                                <?php if ($competitionDetail['registration_start']): ?>
                                <div class="schedule-item">
                                    <div class="schedule-item__icon">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                    <div class="schedule-item__content">
                                        <span class="schedule-item__label">등록 시작</span>
                                        <span class="schedule-item__date">
                                            <?= date('Y.m.d H:i', strtotime($competitionDetail['registration_start'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="schedule-item">
                                    <div class="schedule-item__icon schedule-item__icon--start">
                                        <i class="fas fa-flag"></i>
                                    </div>
                                    <div class="schedule-item__content">
                                        <span class="schedule-item__label">대회 시작</span>
                                        <span class="schedule-item__date">
                                            <?= date('Y.m.d H:i', strtotime($competitionDetail['start_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="schedule-item">
                                    <div class="schedule-item__icon schedule-item__icon--end">
                                        <i class="fas fa-flag-checkered"></i>
                                    </div>
                                    <div class="schedule-item__content">
                                        <span class="schedule-item__label">대회 종료</span>
                                        <span class="schedule-item__date">
                                            <?= date('Y.m.d H:i', strtotime($competitionDetail['end_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 공유 -->
                        <div class="share-card">
                            <span class="share-card__label">대회 공유하기</span>
                            <div class="share-buttons">
                                <button class="share-btn share-btn--twitter" onclick="shareTwitter()">
                                    <i class="fab fa-twitter"></i>
                                </button>
                                <button class="share-btn share-btn--telegram" onclick="shareTelegram()">
                                    <i class="fab fa-telegram"></i>
                                </button>
                                <button class="share-btn share-btn--copy" onclick="copyLink()">
                                    <i class="fas fa-link"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php else: ?>
        <!-- 대회 목록 페이지 -->
        <section class="competition-hero">
            <div class="competition-hero__bg"></div>
            <div class="container">
                <div class="competition-hero__content">
                    <h1 class="competition-hero__title">
                        <i class="fas fa-trophy"></i>
                        트레이딩 대회
                    </h1>
                    <p class="competition-hero__subtitle">
                        최고의 트레이더들과 경쟁하고 풍성한 상금을 획득하세요
                    </p>
                </div>
            </div>
        </section>

        <div class="container">
            <!-- 진행중인 대회 -->
            <?php if (!empty($activeCompetitions)): ?>
            <section class="competition-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="live-dot"></span>
                        진행중인 대회
                    </h2>
                </div>

                <div class="competition-grid competition-grid--featured">
                    <?php foreach ($activeCompetitions as $comp): ?>
                    <a href="?id=<?= $comp['id'] ?>" class="competition-card competition-card--active">
                        <div class="competition-card__banner">
                            <?php if ($comp['banner_image']): ?>
                                <img src="<?= htmlspecialchars($comp['banner_image']) ?>" alt="">
                            <?php else: ?>
                                <div class="competition-card__banner-gradient"></div>
                            <?php endif; ?>
                            <div class="competition-card__badge">진행중</div>
                        </div>
                        <div class="competition-card__content">
                            <div class="competition-card__type">
                                <?= $typeLabels[$comp['type']] ?? $comp['type'] ?>
                            </div>
                            <h3 class="competition-card__title"><?= htmlspecialchars($comp['title']) ?></h3>

                            <div class="competition-card__prize">
                                <i class="fas fa-trophy"></i>
                                <span class="prize-amount">$<?= number_format($comp['prize_pool'], 0) ?></span>
                                <span class="prize-currency"><?= $comp['prize_currency'] ?></span>
                            </div>

                            <div class="competition-card__timer" data-end="<?= $comp['end_at'] ?>">
                                <i class="fas fa-clock"></i>
                                <span class="timer-text">마감까지 계산중...</span>
                            </div>

                            <div class="competition-card__footer">
                                <div class="competition-card__participants">
                                    <i class="fas fa-users"></i>
                                    <?= number_format($comp['participant_count']) ?>명 참가
                                </div>
                                <button class="btn btn--primary btn--sm">참가하기</button>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 예정된 대회 -->
            <?php if (!empty($upcomingCompetitions)): ?>
            <section class="competition-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-alt"></i>
                        예정된 대회
                    </h2>
                </div>

                <div class="competition-grid">
                    <?php foreach ($upcomingCompetitions as $comp): ?>
                    <a href="?id=<?= $comp['id'] ?>" class="competition-card">
                        <div class="competition-card__banner">
                            <?php if ($comp['banner_image']): ?>
                                <img src="<?= htmlspecialchars($comp['banner_image']) ?>" alt="">
                            <?php else: ?>
                                <div class="competition-card__banner-gradient competition-card__banner-gradient--upcoming"></div>
                            <?php endif; ?>
                            <div class="competition-card__badge competition-card__badge--upcoming">예정</div>
                        </div>
                        <div class="competition-card__content">
                            <div class="competition-card__type">
                                <?= $typeLabels[$comp['type']] ?? $comp['type'] ?>
                            </div>
                            <h3 class="competition-card__title"><?= htmlspecialchars($comp['title']) ?></h3>

                            <div class="competition-card__prize">
                                <i class="fas fa-trophy"></i>
                                <span class="prize-amount">$<?= number_format($comp['prize_pool'], 0) ?></span>
                            </div>

                            <div class="competition-card__date">
                                <i class="fas fa-play"></i>
                                <?= date('m월 d일 H:i', strtotime($comp['start_at'])) ?> 시작
                            </div>

                            <div class="competition-card__footer">
                                <div class="competition-card__participants">
                                    <i class="fas fa-users"></i>
                                    <?= number_format($comp['participant_count']) ?>명 등록
                                </div>
                                <button class="btn btn--outline btn--sm">사전등록</button>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 종료된 대회 -->
            <?php if (!empty($endedCompetitions)): ?>
            <section class="competition-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i>
                        지난 대회
                    </h2>
                    <a href="?history=all" class="section-link">전체보기 <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="competition-grid">
                    <?php foreach ($endedCompetitions as $comp): ?>
                    <a href="?id=<?= $comp['id'] ?>" class="competition-card competition-card--ended">
                        <div class="competition-card__banner">
                            <?php if ($comp['banner_image']): ?>
                                <img src="<?= htmlspecialchars($comp['banner_image']) ?>" alt="">
                            <?php else: ?>
                                <div class="competition-card__banner-gradient competition-card__banner-gradient--ended"></div>
                            <?php endif; ?>
                            <div class="competition-card__badge competition-card__badge--ended">종료</div>
                        </div>
                        <div class="competition-card__content">
                            <div class="competition-card__type">
                                <?= $typeLabels[$comp['type']] ?? $comp['type'] ?>
                            </div>
                            <h3 class="competition-card__title"><?= htmlspecialchars($comp['title']) ?></h3>

                            <div class="competition-card__prize">
                                <i class="fas fa-trophy"></i>
                                <span class="prize-amount">$<?= number_format($comp['prize_pool'], 0) ?></span>
                            </div>

                            <div class="competition-card__date">
                                <i class="fas fa-flag-checkered"></i>
                                <?= date('Y.m.d', strtotime($comp['end_at'])) ?> 종료
                            </div>

                            <div class="competition-card__footer">
                                <div class="competition-card__participants">
                                    <i class="fas fa-users"></i>
                                    <?= number_format($comp['participant_count']) ?>명 참가
                                </div>
                                <span class="view-results">결과보기 <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 대회가 없을 때 -->
            <?php if (empty($activeCompetitions) && empty($upcomingCompetitions) && empty($endedCompetitions)): ?>
            <div class="empty-competitions">
                <i class="fas fa-trophy"></i>
                <h3>대회 준비중</h3>
                <p>곧 흥미진진한 트레이딩 대회가 시작됩니다</p>
                <button class="btn btn--primary">알림 받기</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- 푸터 -->
    <footer class="footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__logo">
                    <div class="header__logo-icon">F</div>
                    <span>FuturX</span>
                </div>
                <div class="footer__links">
                    <a href="#">이용약관</a>
                    <a href="#">개인정보처리방침</a>
                    <a href="#">고객센터</a>
                </div>
                <div class="footer__copyright">
                    © 2024 FuturX. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <script>
    // 카운트다운 타이머
    function updateCountdown() {
        const countdownEl = document.getElementById('countdown');
        if (!countdownEl) return;

        const endTime = new Date(countdownEl.dataset.end).getTime();
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            document.getElementById('days').textContent = '00';
            document.getElementById('hours').textContent = '00';
            document.getElementById('minutes').textContent = '00';
            document.getElementById('seconds').textContent = '00';
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById('days').textContent = String(days).padStart(2, '0');
        document.getElementById('hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
    }

    // 카드 타이머 업데이트
    function updateCardTimers() {
        document.querySelectorAll('.competition-card__timer').forEach(el => {
            const endTime = new Date(el.dataset.end).getTime();
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                el.querySelector('.timer-text').textContent = '종료됨';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

            let text = '';
            if (days > 0) {
                text = `${days}일 ${hours}시간 남음`;
            } else if (hours > 0) {
                text = `${hours}시간 ${minutes}분 남음`;
            } else {
                text = `${minutes}분 남음`;
            }
            el.querySelector('.timer-text').textContent = text;
        });
    }

    // 공유 기능
    function shareTwitter() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('FuturX 트레이딩 대회에 참가하세요!');
        window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
    }

    function shareTelegram() {
        const url = encodeURIComponent(window.location.href);
        window.open(`https://t.me/share/url?url=${url}`, '_blank');
    }

    function copyLink() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('링크가 복사되었습니다!');
        });
    }

    // 초기화
    setInterval(updateCountdown, 1000);
    setInterval(updateCardTimers, 60000);
    updateCountdown();
    updateCardTimers();
    </script>
</body>
</html>
