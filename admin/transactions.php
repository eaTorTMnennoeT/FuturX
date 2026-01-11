<?php
/**
 * FuturX Admin - 입출금 관리
 */

require_once 'layout.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

try {
    $db = getDB();

    $where = ['1=1'];
    $params = [];

    if ($type) {
        $where[] = "t.type = :type";
        $params['type'] = $type;
    }

    if ($status) {
        $where[] = "t.status = :status";
        $params['status'] = $status;
    }

    if ($search) {
        $where[] = "(u.username LIKE :search OR u.email LIKE :search OR t.tx_id LIKE :search)";
        $params['search'] = "%$search%";
    }

    $whereClause = implode(' AND ', $where);

    // 총 개수
    $stmt = $db->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON t.user_id = u.id WHERE $whereClause");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);

    // 데이터
    $sql = "SELECT t.*, u.username, u.nickname, u.email
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE $whereClause
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 통계
    $stats = $db->query("SELECT
                            SUM(CASE WHEN type = 'deposit' AND status = 'pending' THEN 1 ELSE 0 END) as pending_deposits,
                            SUM(CASE WHEN type = 'withdraw' AND status = 'pending' THEN 1 ELSE 0 END) as pending_withdraws,
                            SUM(CASE WHEN type = 'deposit' AND status = 'completed' AND DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as today_deposits,
                            SUM(CASE WHEN type = 'withdraw' AND status = 'completed' AND DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as today_withdraws
                         FROM transactions")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $transactions = [];
    $totalCount = 0;
    $totalPages = 0;
    $stats = ['pending_deposits' => 0, 'pending_withdraws' => 0, 'today_deposits' => 0, 'today_withdraws' => 0];
}

// 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(false, null, 'CSRF 토큰이 유효하지 않습니다.');
    }

    $action = $_POST['action'] ?? '';
    $txId = (int)($_POST['tx_id'] ?? 0);

    try {
        if ($action === 'approve') {
            $db->beginTransaction();

            // 트랜잭션 정보 조회
            $stmt = $db->prepare("SELECT * FROM transactions WHERE id = :id AND status = 'pending' FOR UPDATE");
            $stmt->execute(['id' => $txId]);
            $tx = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tx) {
                throw new Exception('처리할 트랜잭션이 없습니다.');
            }

            // 입금인 경우 잔액 추가
            if ($tx['type'] === 'deposit') {
                $stmt = $db->prepare("INSERT INTO wallets (user_id, currency, balance, available) VALUES (:user_id, :currency, :amount, :amount)
                                      ON DUPLICATE KEY UPDATE balance = balance + :amount2, available = available + :amount3");
                $stmt->execute([
                    'user_id' => $tx['user_id'],
                    'currency' => $tx['currency'],
                    'amount' => $tx['amount'],
                    'amount2' => $tx['amount'],
                    'amount3' => $tx['amount']
                ]);
            }

            // 상태 업데이트
            $stmt = $db->prepare("UPDATE transactions SET status = 'completed', processed_by = :admin, processed_at = NOW() WHERE id = :id");
            $stmt->execute(['admin' => $_SESSION['admin_id'], 'id' => $txId]);

            $db->commit();
            logAdminAction('transaction_approve', 'transactions', $txId);
            jsonResponse(true, null, '승인 처리되었습니다.');

        } elseif ($action === 'reject') {
            $db->beginTransaction();

            // 트랜잭션 정보 조회
            $stmt = $db->prepare("SELECT * FROM transactions WHERE id = :id AND status = 'pending' FOR UPDATE");
            $stmt->execute(['id' => $txId]);
            $tx = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tx) {
                throw new Exception('처리할 트랜잭션이 없습니다.');
            }

            // 출금인 경우 잔액 복구
            if ($tx['type'] === 'withdraw') {
                $stmt = $db->prepare("UPDATE wallets SET available = available + :amount, frozen = frozen - :amount2 WHERE user_id = :user_id AND currency = :currency");
                $stmt->execute([
                    'amount' => $tx['amount'],
                    'amount2' => $tx['amount'],
                    'user_id' => $tx['user_id'],
                    'currency' => $tx['currency']
                ]);
            }

            // 상태 업데이트
            $stmt = $db->prepare("UPDATE transactions SET status = 'cancelled', admin_note = :note, processed_by = :admin, processed_at = NOW() WHERE id = :id");
            $stmt->execute([
                'note' => $_POST['reason'] ?? '',
                'admin' => $_SESSION['admin_id'],
                'id' => $txId
            ]);

            $db->commit();
            logAdminAction('transaction_reject', 'transactions', $txId);
            jsonResponse(true, null, '거절 처리되었습니다.');
        }

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonResponse(false, null, $e->getMessage());
    }
}

renderHeader('입출금 관리');
renderPageHeader('입출금 관리', '회원들의 입출금 요청을 관리합니다');
?>

<!-- 통계 -->
<div class="stats-grid">
    <?php renderStatCard('fa-inbox', '입금 대기', number_format($stats['pending_deposits']) . '건'); ?>
    <?php renderStatCard('fa-paper-plane', '출금 대기', number_format($stats['pending_withdraws']) . '건'); ?>
    <?php renderStatCard('fa-arrow-down', '오늘 입금', '$' . number_format($stats['today_deposits'], 0)); ?>
    <?php renderStatCard('fa-arrow-up', '오늘 출금', '$' . number_format($stats['today_withdraws'], 0)); ?>
</div>

<!-- 필터 -->
<div class="card mb-xl">
    <div class="card__body">
        <form method="GET" class="filter-form">
            <div class="filter-form__row">
                <div class="filter-form__group">
                    <label class="form-label">검색</label>
                    <input type="text" name="search" class="form-input" placeholder="회원, TX ID" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-form__group">
                    <label class="form-label">유형</label>
                    <select name="type" class="form-select">
                        <option value="">전체</option>
                        <option value="deposit" <?= $type === 'deposit' ? 'selected' : '' ?>>입금</option>
                        <option value="withdraw" <?= $type === 'withdraw' ? 'selected' : '' ?>>출금</option>
                    </select>
                </div>
                <div class="filter-form__group">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <option value="">전체</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>대기</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>완료</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>취소</option>
                    </select>
                </div>
                <div class="filter-form__actions">
                    <button type="submit" class="btn btn--primary"><i class="fas fa-search"></i> 검색</button>
                    <a href="transactions.php" class="btn btn--secondary">초기화</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 목록 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-list"></i>
            입출금 내역
            <span class="text-secondary">(<?= number_format($totalCount) ?>건)</span>
        </h3>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>TX ID</th>
                    <th>회원</th>
                    <th>유형</th>
                    <th>금액</th>
                    <th>상태</th>
                    <th>주소</th>
                    <th>신청일</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-wallet"></i>
                            <p>입출금 내역이 없습니다</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td class="num-mono"><?= htmlspecialchars(substr($tx['tx_id'], 0, 12)) ?>...</td>
                    <td>
                        <div><?= htmlspecialchars($tx['nickname'] ?: $tx['username']) ?></div>
                        <small class="text-secondary"><?= htmlspecialchars($tx['email']) ?></small>
                    </td>
                    <td>
                        <?php
                        $typeLabels = [
                            'deposit' => ['입금', 'success'],
                            'withdraw' => ['출금', 'danger'],
                            'transfer' => ['이체', 'info'],
                            'bonus' => ['보너스', 'warning']
                        ];
                        $t = $typeLabels[$tx['type']] ?? ['기타', 'secondary'];
                        ?>
                        <span class="badge badge--<?= $t[1] ?>"><?= $t[0] ?></span>
                    </td>
                    <td class="num-mono">
                        $<?= number_format($tx['amount'], 2) ?>
                        <small class="text-secondary"><?= $tx['currency'] ?></small>
                    </td>
                    <td>
                        <?php
                        $statusLabels = [
                            'pending' => ['대기', 'warning'],
                            'processing' => ['처리중', 'info'],
                            'completed' => ['완료', 'success'],
                            'failed' => ['실패', 'danger'],
                            'cancelled' => ['취소', 'secondary']
                        ];
                        $s = $statusLabels[$tx['status']] ?? ['알수없음', 'secondary'];
                        ?>
                        <span class="badge badge--<?= $s[1] ?>"><?= $s[0] ?></span>
                    </td>
                    <td>
                        <?php if ($tx['address']): ?>
                        <span class="num-mono text-xs"><?= htmlspecialchars(substr($tx['address'], 0, 16)) ?>...</span>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td><?= date('m/d H:i', strtotime($tx['created_at'])) ?></td>
                    <td>
                        <?php if ($tx['status'] === 'pending'): ?>
                        <div class="action-btns">
                            <button class="btn btn--sm btn--success" onclick="processTx(<?= $tx['id'] ?>, 'approve')" title="승인">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn--sm btn--danger" onclick="processTx(<?= $tx['id'] ?>, 'reject')" title="거절">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <span class="text-secondary">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card__footer">
        <?php
        $baseUrl = "transactions.php?type=" . urlencode($type) . "&status=" . urlencode($status) . "&search=" . urlencode($search);
        renderPagination($page, $totalPages, $baseUrl);
        ?>
    </div>
    <?php endif; ?>
</div>

<style>
.filter-form__row {
    display: flex;
    gap: var(--spacing-lg);
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-form__group {
    flex: 1;
    min-width: 150px;
}

.filter-form__actions {
    display: flex;
    gap: var(--spacing-sm);
}

.action-btns {
    display: flex;
    gap: var(--spacing-xs);
}

.text-xs {
    font-size: 11px;
}
</style>

<script>
async function processTx(txId, action) {
    const messages = {
        approve: '이 요청을 승인하시겠습니까?',
        reject: '이 요청을 거절하시겠습니까?'
    };

    if (!await confirmAction(messages[action])) return;

    const formData = new FormData();
    formData.append('action', action);
    formData.append('tx_id', txId);
    formData.append('csrf_token', document.getElementById('csrf_token').value);

    if (action === 'reject') {
        const reason = prompt('거절 사유를 입력하세요:');
        if (reason !== null) {
            formData.append('reason', reason);
        }
    }

    try {
        const response = await fetch('transactions.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            showToast('success', '완료', result.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', '오류', result.message);
        }
    } catch (error) {
        showToast('error', '오류', '요청 처리에 실패했습니다.');
    }
}
</script>

<?php renderFooter(); ?>
