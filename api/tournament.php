<?php
/**
 * FuturX - 토너먼트 API
 */
require_once '../config.php';

header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// POST 요청의 JSON body 처리
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $action = $input['action'] ?? $action;
    }
}

try {
    switch ($action) {
        case 'list':
            handleList($db);
            break;

        case 'detail':
            handleDetail($db);
            break;

        case 'leaderboard':
            handleLeaderboard($db);
            break;

        case 'join':
            handleJoin($db, $input);
            break;

        case 'my_tournaments':
            handleMyTournaments($db);
            break;

        case 'claim_reward':
            handleClaimReward($db, $input);
            break;

        case 'my_rewards':
            handleMyRewards($db);
            break;

        case 'announcements':
            handleAnnouncements($db);
            break;

        default:
            errorResponse('Invalid action', 400);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}

/**
 * 토너먼트 목록 조회
 */
function handleList($db) {
    $status = $_GET['status'] ?? 'all';
    $type = $_GET['type'] ?? 'all';
    $limit = min((int)($_GET['limit'] ?? 20), 50);
    $offset = (int)($_GET['offset'] ?? 0);

    $where = [];
    $params = [];

    if ($status !== 'all') {
        $where[] = "status = ?";
        $params[] = $status;
    }

    if ($type !== 'all') {
        $where[] = "type = ?";
        $params[] = $type;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT * FROM tournaments
        $whereClause
        ORDER BY
            CASE status
                WHEN 'active' THEN 1
                WHEN 'registration' THEN 2
                WHEN 'upcoming' THEN 3
                WHEN 'calculating' THEN 4
                WHEN 'ended' THEN 5
            END,
            start_time ASC
        LIMIT ? OFFSET ?
    ");

    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $tournaments = $stmt->fetchAll();

    // 상금 분배 JSON 파싱
    foreach ($tournaments as &$t) {
        $t['prize_distribution'] = json_decode($t['prize_distribution'], true);
    }

    // 총 개수
    $countStmt = $db->prepare("SELECT COUNT(*) FROM tournaments $whereClause");
    $countStmt->execute(array_slice($params, 0, -2));
    $total = $countStmt->fetchColumn();

    successResponse([
        'tournaments' => $tournaments,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

/**
 * 토너먼트 상세 조회
 */
function handleDetail($db) {
    $tournamentId = $_GET['tournament_id'] ?? '';

    if (!$tournamentId) {
        errorResponse('Tournament ID required', 400);
    }

    $stmt = $db->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();

    if (!$tournament) {
        errorResponse('Tournament not found', 404);
    }

    $tournament['prize_distribution'] = json_decode($tournament['prize_distribution'], true);
    $tournament['allowed_symbols'] = json_decode($tournament['allowed_symbols'], true);

    // 참가자 수
    $pStmt = $db->prepare("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = ?");
    $pStmt->execute([$tournamentId]);
    $tournament['participant_count'] = $pStmt->fetchColumn();

    // 현재 사용자 참가 여부
    $userId = $_SESSION['user_id'] ?? null;
    $tournament['is_participant'] = false;
    $tournament['my_rank'] = null;

    if ($userId) {
        $myStmt = $db->prepare("SELECT * FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
        $myStmt->execute([$tournamentId, $userId]);
        $myData = $myStmt->fetch();

        if ($myData) {
            $tournament['is_participant'] = true;
            $tournament['my_rank'] = $myData['current_rank'];
            $tournament['my_stats'] = [
                'balance' => $myData['tournament_balance'],
                'pnl' => $myData['pnl'],
                'pnl_percent' => $myData['pnl_percent'],
                'total_trades' => $myData['total_trades'],
                'winning_trades' => $myData['winning_trades']
            ];
        }
    }

    successResponse($tournament);
}

/**
 * 리더보드 조회
 */
function handleLeaderboard($db) {
    $tournamentId = $_GET['tournament_id'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);

    if (!$tournamentId) {
        errorResponse('Tournament ID required', 400);
    }

    $stmt = $db->prepare("
        SELECT
            tp.current_rank,
            tp.user_id,
            tp.tournament_balance,
            tp.starting_balance,
            tp.pnl,
            tp.pnl_percent,
            tp.total_trades,
            tp.winning_trades,
            tp.losing_trades,
            tp.total_volume,
            tp.max_drawdown,
            tp.last_trade_at,
            COALESCE(u.username, CONCAT('Trader***', tp.user_id)) as username
        FROM tournament_participants tp
        LEFT JOIN users u ON tp.user_id = u.id
        WHERE tp.tournament_id = ?
        ORDER BY tp.pnl_percent DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$tournamentId, $limit, $offset]);
    $leaderboard = $stmt->fetchAll();

    // 순위 재계산 (offset 반영)
    foreach ($leaderboard as $idx => &$item) {
        $item['rank'] = $offset + $idx + 1;
        // 승률 계산
        $item['win_rate'] = $item['total_trades'] > 0
            ? round(($item['winning_trades'] / $item['total_trades']) * 100, 2)
            : 0;
    }

    // 총 참가자 수
    $countStmt = $db->prepare("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = ?");
    $countStmt->execute([$tournamentId]);
    $total = $countStmt->fetchColumn();

    successResponse([
        'leaderboard' => $leaderboard,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

/**
 * 토너먼트 참가
 */
function handleJoin($db, $input) {
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        errorResponse('Login required', 401);
    }

    $tournamentId = $input['tournament_id'] ?? '';

    if (!$tournamentId) {
        errorResponse('Tournament ID required', 400);
    }

    // 토너먼트 정보 확인
    $stmt = $db->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();

    if (!$tournament) {
        errorResponse('Tournament not found', 404);
    }

    // 등록 기간 확인
    if ($tournament['status'] !== 'registration') {
        errorResponse('Registration is not open', 400);
    }

    $now = time();
    $regEnd = strtotime($tournament['registration_end']);

    if ($now > $regEnd) {
        errorResponse('Registration has ended', 400);
    }

    // 이미 참가 여부 확인
    $checkStmt = $db->prepare("SELECT id FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
    $checkStmt->execute([$tournamentId, $userId]);

    if ($checkStmt->fetch()) {
        errorResponse('Already registered', 400);
    }

    // 최대 참가자 수 확인
    if ($tournament['max_participants'] > 0) {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = ?");
        $countStmt->execute([$tournamentId]);
        $currentCount = $countStmt->fetchColumn();

        if ($currentCount >= $tournament['max_participants']) {
            errorResponse('Tournament is full', 400);
        }
    }

    // 참가비 확인 및 차감 (필요시)
    if ($tournament['entry_fee'] > 0) {
        // 실제 구현 시 사용자 잔고 확인 및 차감 로직 추가
        // $userBalance = getUserBalance($userId);
        // if ($userBalance < $tournament['entry_fee']) {
        //     errorResponse('Insufficient balance', 400);
        // }
        // deductBalance($userId, $tournament['entry_fee']);
    }

    // 참가 등록
    $db->beginTransaction();

    try {
        $insertStmt = $db->prepare("
            INSERT INTO tournament_participants (
                tournament_id, user_id, tournament_balance, starting_balance, status
            ) VALUES (?, ?, ?, ?, 'registered')
        ");
        $insertStmt->execute([
            $tournamentId,
            $userId,
            $tournament['starting_balance'],
            $tournament['starting_balance']
        ]);

        // 토너먼트 참가자 수 업데이트
        $updateStmt = $db->prepare("
            UPDATE tournaments SET total_participants = total_participants + 1 WHERE tournament_id = ?
        ");
        $updateStmt->execute([$tournamentId]);

        $db->commit();

        successResponse([
            'tournament_id' => $tournamentId,
            'starting_balance' => $tournament['starting_balance']
        ], 'Successfully registered');

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * 내 토너먼트 목록
 */
function handleMyTournaments($db) {
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        errorResponse('Login required', 401);
    }

    $status = $_GET['status'] ?? 'all'; // all, active, ended

    $statusCondition = '';
    if ($status === 'active') {
        $statusCondition = "AND t.status IN ('active', 'registration')";
    } elseif ($status === 'ended') {
        $statusCondition = "AND t.status = 'ended'";
    }

    $stmt = $db->prepare("
        SELECT
            t.*,
            tp.tournament_balance,
            tp.pnl,
            tp.pnl_percent,
            tp.current_rank,
            tp.final_rank,
            tp.prize_amount,
            tp.prize_claimed,
            tp.total_trades,
            tp.winning_trades,
            tp.status as participation_status
        FROM tournament_participants tp
        JOIN tournaments t ON tp.tournament_id = t.tournament_id
        WHERE tp.user_id = ?
        $statusCondition
        ORDER BY t.start_time DESC
    ");
    $stmt->execute([$userId]);
    $tournaments = $stmt->fetchAll();

    foreach ($tournaments as &$t) {
        $t['prize_distribution'] = json_decode($t['prize_distribution'], true);
    }

    successResponse($tournaments);
}

/**
 * 보상 수령
 */
function handleClaimReward($db, $input) {
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        errorResponse('Login required', 401);
    }

    $rewardId = $input['reward_id'] ?? '';

    if (!$rewardId) {
        errorResponse('Reward ID required', 400);
    }

    // 보상 정보 확인
    $stmt = $db->prepare("
        SELECT * FROM tournament_rewards
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$rewardId, $userId]);
    $reward = $stmt->fetch();

    if (!$reward) {
        errorResponse('Reward not found or already claimed', 404);
    }

    // 만료 확인
    if ($reward['expires_at'] && strtotime($reward['expires_at']) < time()) {
        // 만료 처리
        $expireStmt = $db->prepare("UPDATE tournament_rewards SET status = 'expired' WHERE id = ?");
        $expireStmt->execute([$rewardId]);
        errorResponse('Reward has expired', 400);
    }

    $db->beginTransaction();

    try {
        // 보상 수령 처리
        if ($reward['reward_type'] === 'prize' && $reward['amount'] > 0) {
            // 실제 구현 시 사용자 잔고에 상금 추가
            // addBalance($userId, $reward['amount'], $reward['currency']);
        }

        // 보상 상태 업데이트
        $updateStmt = $db->prepare("
            UPDATE tournament_rewards
            SET status = 'claimed', claimed_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$rewardId]);

        // 참가자 테이블 업데이트
        $participantStmt = $db->prepare("
            UPDATE tournament_participants
            SET prize_claimed = TRUE, prize_claimed_at = NOW()
            WHERE tournament_id = ? AND user_id = ?
        ");
        $participantStmt->execute([$reward['tournament_id'], $userId]);

        $db->commit();

        successResponse([
            'reward_type' => $reward['reward_type'],
            'amount' => $reward['amount'],
            'currency' => $reward['currency']
        ], 'Reward claimed successfully');

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * 내 보상 목록
 */
function handleMyRewards($db) {
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        errorResponse('Login required', 401);
    }

    $status = $_GET['status'] ?? 'all'; // all, pending, claimed, expired

    $statusCondition = '';
    if ($status !== 'all') {
        $statusCondition = "AND r.status = '$status'";
    }

    $stmt = $db->prepare("
        SELECT
            r.*,
            t.title as tournament_title,
            t.type as tournament_type
        FROM tournament_rewards r
        JOIN tournaments t ON r.tournament_id = t.tournament_id
        WHERE r.user_id = ?
        $statusCondition
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$userId]);
    $rewards = $stmt->fetchAll();

    // 통계
    $statsStmt = $db->prepare("
        SELECT
            SUM(CASE WHEN status = 'claimed' THEN amount ELSE 0 END) as total_claimed,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
            COUNT(CASE WHEN status = 'claimed' THEN 1 END) as claimed_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
        FROM tournament_rewards
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch();

    successResponse([
        'rewards' => $rewards,
        'stats' => $stats
    ]);
}

/**
 * 공지사항
 */
function handleAnnouncements($db) {
    $tournamentId = $_GET['tournament_id'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 10), 50);

    $where = [];
    $params = [];

    if ($tournamentId) {
        $where[] = "(tournament_id = ? OR tournament_id IS NULL)";
        $params[] = $tournamentId;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT * FROM tournament_announcements
        $whereClause
        ORDER BY is_pinned DESC, created_at DESC
        LIMIT ?
    ");
    $params[] = $limit;
    $stmt->execute($params);
    $announcements = $stmt->fetchAll();

    successResponse($announcements);
}
