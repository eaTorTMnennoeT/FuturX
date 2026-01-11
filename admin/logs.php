<?php
/**
 * FuturX Admin - 활동 로그
 */

require_once 'layout.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$adminId = $_GET['admin'] ?? '';
$action = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    $db = getDB();

    $where = ['1=1'];
    $params = [];

    if ($adminId) {
        $where[] = "l.admin_id = :admin_id";
        $params['admin_id'] = $adminId;
    }

    if ($action) {
        $where[] = "l.action LIKE :action";
        $params['action'] = "%$action%";
    }

    if ($dateFrom) {
        $where[] = "DATE(l.created_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }

    if ($dateTo) {
        $where[] = "DATE(l.created_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }

    $whereClause = implode(' AND ', $where);

    // 총 개수
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_logs l WHERE $whereClause");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);

    // 데이터
    $sql = "SELECT l.*, u.username, u.nickname
            FROM admin_logs l
            JOIN users u ON l.admin_id = u.id
            WHERE $whereClause
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 관리자 목록
    $admins = $db->query("SELECT id, username, nickname FROM users WHERE role IN ('admin', 'superadmin')")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $logs = [];
    $admins = [];
    $totalCount = 0;
    $totalPages = 0;
}

$actionLabels = [
    'login' => ['로그인', 'success'],
    'logout' => ['로그아웃', 'secondary'],
    'user_suspend' => ['회원 정지', 'warning'],
    'user_activate' => ['회원 활성화', 'success'],
    'user_ban' => ['회원 차단', 'danger'],
    'user_delete' => ['회원 삭제', 'danger'],
    'transaction_approve' => ['입출금 승인', 'success'],
    'transaction_reject' => ['입출금 거절', 'danger'],
    'competition_create' => ['대회 생성', 'info'],
    'competition_status_change' => ['대회 상태 변경', 'warning'],
    'competition_delete' => ['대회 삭제', 'danger'],
    'settings_update' => ['설정 변경', 'info']
];

renderHeader('활동 로그');
renderPageHeader('관리자 활동 로그', '관리자들의 활동 기록을 확인합니다');
?>

<!-- 필터 -->
<div class="card mb-xl">
    <div class="card__body">
        <form method="GET" class="filter-form">
            <div class="filter-form__row">
                <div class="filter-form__group">
                    <label class="form-label">관리자</label>
                    <select name="admin" class="form-select">
                        <option value="">전체</option>
                        <?php foreach ($admins as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $adminId == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['nickname'] ?: $a['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-form__group">
                    <label class="form-label">활동</label>
                    <input type="text" name="action" class="form-input" placeholder="login, user_ban 등" value="<?= htmlspecialchars($action) ?>">
                </div>
                <div class="filter-form__group">
                    <label class="form-label">시작일</label>
                    <input type="date" name="date_from" class="form-input" value="<?= $dateFrom ?>">
                </div>
                <div class="filter-form__group">
                    <label class="form-label">종료일</label>
                    <input type="date" name="date_to" class="form-input" value="<?= $dateTo ?>">
                </div>
                <div class="filter-form__actions">
                    <button type="submit" class="btn btn--primary"><i class="fas fa-search"></i> 검색</button>
                    <a href="logs.php" class="btn btn--secondary">초기화</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 로그 목록 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-history"></i>
            활동 로그
            <span class="text-secondary">(<?= number_format($totalCount) ?>건)</span>
        </h3>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>시간</th>
                    <th>관리자</th>
                    <th>활동</th>
                    <th>대상</th>
                    <th>상세</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>로그가 없습니다</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= date('m/d H:i:s', strtotime($log['created_at'])) ?></td>
                    <td><?= htmlspecialchars($log['nickname'] ?: $log['username']) ?></td>
                    <td>
                        <?php
                        $a = $actionLabels[$log['action']] ?? [$log['action'], 'secondary'];
                        ?>
                        <span class="badge badge--<?= $a[1] ?>"><?= $a[0] ?></span>
                    </td>
                    <td>
                        <?php if ($log['target_type'] && $log['target_id']): ?>
                        <span class="text-secondary"><?= $log['target_type'] ?></span>
                        #<?= $log['target_id'] ?>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['details']): ?>
                        <button class="btn btn--sm btn--outline" onclick="showDetails('<?= htmlspecialchars($log['details']) ?>')">
                            <i class="fas fa-info"></i>
                        </button>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td class="num-mono text-secondary"><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card__footer">
        <?php
        $baseUrl = "logs.php?admin=" . urlencode($adminId) . "&action=" . urlencode($action) . "&date_from=" . urlencode($dateFrom) . "&date_to=" . urlencode($dateTo);
        renderPagination($page, $totalPages, $baseUrl);
        ?>
    </div>
    <?php endif; ?>
</div>

<!-- 상세 모달 -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal__header">
            <h3 class="modal__title">상세 정보</h3>
            <button class="modal__close" onclick="closeModal('detailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <pre id="detailsContent" style="background: var(--bg-tertiary); padding: var(--spacing-md); border-radius: var(--radius-md); overflow-x: auto; font-family: var(--font-mono); font-size: 12px;"></pre>
        </div>
    </div>
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
</style>

<script>
function showDetails(jsonStr) {
    try {
        const data = JSON.parse(jsonStr);
        document.getElementById('detailsContent').textContent = JSON.stringify(data, null, 2);
    } catch (e) {
        document.getElementById('detailsContent').textContent = jsonStr;
    }
    openModal('detailsModal');
}
</script>

<?php renderFooter(); ?>
