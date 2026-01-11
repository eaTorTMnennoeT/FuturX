<?php
/**
 * FuturX Admin - 대회관리
 */

require_once 'layout.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$status = $_GET['status'] ?? '';

try {
    $db = getDB();

    $where = $status ? "WHERE status = :status" : "";
    $params = $status ? ['status' => $status] : [];

    // 총 개수
    $stmt = $db->prepare("SELECT COUNT(*) FROM competitions $where");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);

    // 데이터
    $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id) as participant_count,
                (SELECT COALESCE(SUM(volume), 0) FROM competition_participants WHERE competition_id = c.id) as total_volume
            FROM competitions c
            $where
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 통계
    $stats = $db->query("SELECT
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                            SUM(CASE WHEN status = 'upcoming' THEN 1 ELSE 0 END) as upcoming,
                            COALESCE(SUM(prize_pool), 0) as total_prize
                         FROM competitions")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $competitions = [];
    $totalCount = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'active' => 0, 'upcoming' => 0, 'total_prize' => 0];
}

// 대회 생성/수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(false, null, 'CSRF 토큰이 유효하지 않습니다.');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO competitions (title, slug, description, rules, type, prize_pool, prize_currency, entry_fee, min_balance, max_participants, start_at, end_at, registration_start, registration_end, created_by)
                                  VALUES (:title, :slug, :description, :rules, :type, :prize_pool, :prize_currency, :entry_fee, :min_balance, :max_participants, :start_at, :end_at, :reg_start, :reg_end, :created_by)");
            $stmt->execute([
                'title' => $_POST['title'],
                'slug' => preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $_POST['title']))),
                'description' => $_POST['description'] ?? '',
                'rules' => $_POST['rules'] ?? '',
                'type' => $_POST['type'],
                'prize_pool' => $_POST['prize_pool'],
                'prize_currency' => $_POST['prize_currency'] ?? 'USDT',
                'entry_fee' => $_POST['entry_fee'] ?? 0,
                'min_balance' => $_POST['min_balance'] ?? 0,
                'max_participants' => $_POST['max_participants'] ?: null,
                'start_at' => $_POST['start_at'],
                'end_at' => $_POST['end_at'],
                'reg_start' => $_POST['registration_start'] ?: null,
                'reg_end' => $_POST['registration_end'] ?: null,
                'created_by' => $_SESSION['admin_id']
            ]);

            logAdminAction('competition_create', 'competitions', $db->lastInsertId());
            jsonResponse(true, null, '대회가 생성되었습니다.');

        } elseif ($action === 'status') {
            $compId = (int)$_POST['competition_id'];
            $newStatus = $_POST['new_status'];

            $stmt = $db->prepare("UPDATE competitions SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $compId]);

            logAdminAction('competition_status_change', 'competitions', $compId, ['new_status' => $newStatus]);
            jsonResponse(true, null, '대회 상태가 변경되었습니다.');

        } elseif ($action === 'delete') {
            $compId = (int)$_POST['competition_id'];

            $stmt = $db->prepare("DELETE FROM competitions WHERE id = :id AND status != 'active'");
            $stmt->execute(['id' => $compId]);

            logAdminAction('competition_delete', 'competitions', $compId);
            jsonResponse(true, null, '대회가 삭제되었습니다.');
        }
    } catch (PDOException $e) {
        jsonResponse(false, null, '처리 중 오류가 발생했습니다.');
    }
}

renderHeader('대회관리');
renderPageHeader('대회관리', '트레이딩 대회를 관리합니다', [
    ['label' => '대회 생성', 'icon' => 'fa-plus', 'onclick' => "openModal('createCompModal')"]
]);
?>

<!-- 통계 -->
<div class="stats-grid">
    <?php renderStatCard('fa-trophy', '전체 대회', number_format($stats['total']) . '개'); ?>
    <?php renderStatCard('fa-play-circle', '진행중', number_format($stats['active']) . '개'); ?>
    <?php renderStatCard('fa-clock', '예정', number_format($stats['upcoming']) . '개'); ?>
    <?php renderStatCard('fa-dollar-sign', '총 상금', '$' . number_format($stats['total_prize'], 0)); ?>
</div>

<!-- 필터 -->
<div class="card mb-xl">
    <div class="card__body">
        <form method="GET" class="filter-form">
            <div class="filter-form__row">
                <div class="filter-form__group">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <option value="">전체</option>
                        <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : '' ?>>예정</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>진행중</option>
                        <option value="ended" <?= $status === 'ended' ? 'selected' : '' ?>>종료</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>취소</option>
                    </select>
                </div>
                <div class="filter-form__actions">
                    <button type="submit" class="btn btn--primary"><i class="fas fa-search"></i> 검색</button>
                    <a href="competitions.php" class="btn btn--secondary">초기화</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 대회 목록 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-list"></i>
            대회 목록
        </h3>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>대회명</th>
                    <th>유형</th>
                    <th>상태</th>
                    <th>상금</th>
                    <th>참가자</th>
                    <th>기간</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($competitions)): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-trophy"></i>
                            <p>등록된 대회가 없습니다</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($competitions as $comp): ?>
                <tr>
                    <td><?= $comp['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($comp['title']) ?></strong>
                        <div class="text-secondary text-xs"><?= htmlspecialchars($comp['slug']) ?></div>
                    </td>
                    <td>
                        <?php
                        $typeLabels = ['roi' => 'ROI', 'pnl' => 'PnL', 'volume' => '거래량', 'team' => '팀'];
                        ?>
                        <span class="badge badge--info"><?= $typeLabels[$comp['type']] ?? $comp['type'] ?></span>
                    </td>
                    <td>
                        <?php
                        $statusLabels = [
                            'upcoming' => ['예정', 'info'],
                            'active' => ['진행중', 'success'],
                            'ended' => ['종료', 'secondary'],
                            'cancelled' => ['취소', 'danger']
                        ];
                        $s = $statusLabels[$comp['status']] ?? ['알수없음', 'secondary'];
                        ?>
                        <span class="badge badge--<?= $s[1] ?>"><?= $s[0] ?></span>
                    </td>
                    <td class="num-mono">$<?= number_format($comp['prize_pool'], 0) ?></td>
                    <td>
                        <?= number_format($comp['participant_count']) ?>명
                        <?php if ($comp['max_participants']): ?>
                        <small class="text-secondary">/ <?= number_format($comp['max_participants']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= date('Y-m-d', strtotime($comp['start_at'])) ?></div>
                        <div class="text-secondary text-xs">~ <?= date('Y-m-d', strtotime($comp['end_at'])) ?></div>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="competition_detail.php?id=<?= $comp['id'] ?>" class="btn btn--sm btn--outline" title="상세">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn--sm btn--secondary" onclick="showCompActions(<?= $comp['id'] ?>, '<?= $comp['status'] ?>')" title="관리">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card__footer">
        <?php renderPagination($page, $totalPages, "competitions.php?status=" . urlencode($status)); ?>
    </div>
    <?php endif; ?>
</div>

<!-- 대회 생성 모달 -->
<div class="modal-overlay" id="createCompModal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal__header">
            <h3 class="modal__title">대회 생성</h3>
            <button class="modal__close" onclick="closeModal('createCompModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <form id="createCompForm">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label required">대회명</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">유형</label>
                        <select name="type" class="form-select" required>
                            <option value="roi">ROI 대회</option>
                            <option value="pnl">PnL 대회</option>
                            <option value="volume">거래량 대회</option>
                            <option value="team">팀 대회</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">총 상금</label>
                        <input type="number" name="prize_pool" class="form-input" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">참가비</label>
                        <input type="number" name="entry_fee" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">최소 잔액</label>
                        <input type="number" name="min_balance" class="form-input" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">시작일시</label>
                        <input type="datetime-local" name="start_at" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">종료일시</label>
                        <input type="datetime-local" name="end_at" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">설명</label>
                    <textarea name="description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">규칙</label>
                    <textarea name="rules" class="form-textarea" rows="4"></textarea>
                </div>
            </form>
        </div>
        <div class="modal__footer">
            <button class="btn btn--secondary" onclick="closeModal('createCompModal')">취소</button>
            <button class="btn btn--primary" onclick="submitCreateComp()">생성</button>
        </div>
    </div>
</div>

<!-- 대회 액션 모달 -->
<div class="modal-overlay" id="compActionsModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal__header">
            <h3 class="modal__title">대회 관리</h3>
            <button class="modal__close" onclick="closeModal('compActionsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <input type="hidden" id="actionCompId">
            <div class="action-list" id="compActionList">
                <!-- 동적으로 생성 -->
            </div>
        </div>
    </div>
</div>

<style>
.filter-form__row {
    display: flex;
    gap: var(--spacing-lg);
    align-items: flex-end;
}

.filter-form__group {
    min-width: 200px;
}

.filter-form__actions {
    display: flex;
    gap: var(--spacing-sm);
}

.action-btns {
    display: flex;
    gap: var(--spacing-xs);
}

.action-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.action-list__btn {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 14px;
    transition: background var(--transition-fast);
}

.action-list__btn:hover {
    background: var(--bg-hover);
}

.text-xs {
    font-size: 11px;
}
</style>

<script>
function showCompActions(compId, status) {
    document.getElementById('actionCompId').value = compId;

    const actionList = document.getElementById('compActionList');
    let html = '';

    if (status === 'upcoming') {
        html += `<button class="action-list__btn" onclick="changeCompStatus('active')">
            <i class="fas fa-play text-success"></i><span>대회 시작</span>
        </button>`;
        html += `<button class="action-list__btn" onclick="changeCompStatus('cancelled')">
            <i class="fas fa-ban text-warning"></i><span>대회 취소</span>
        </button>`;
    } else if (status === 'active') {
        html += `<button class="action-list__btn" onclick="changeCompStatus('ended')">
            <i class="fas fa-stop text-info"></i><span>대회 종료</span>
        </button>`;
    }

    if (status !== 'active') {
        html += `<button class="action-list__btn" onclick="deleteComp()">
            <i class="fas fa-trash text-danger"></i><span>대회 삭제</span>
        </button>`;
    }

    actionList.innerHTML = html;
    openModal('compActionsModal');
}

async function changeCompStatus(newStatus) {
    const compId = document.getElementById('actionCompId').value;

    const formData = new FormData();
    formData.append('action', 'status');
    formData.append('competition_id', compId);
    formData.append('new_status', newStatus);
    formData.append('csrf_token', document.getElementById('csrf_token').value);

    try {
        const response = await fetch('competitions.php', { method: 'POST', body: formData });
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

    closeModal('compActionsModal');
}

async function deleteComp() {
    if (!await confirmAction('이 대회를 삭제하시겠습니까?')) return;

    const compId = document.getElementById('actionCompId').value;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('competition_id', compId);
    formData.append('csrf_token', document.getElementById('csrf_token').value);

    try {
        const response = await fetch('competitions.php', { method: 'POST', body: formData });
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

    closeModal('compActionsModal');
}

async function submitCreateComp() {
    const form = document.getElementById('createCompForm');
    const formData = new FormData(form);
    formData.append('csrf_token', document.getElementById('csrf_token').value);

    try {
        const response = await fetch('competitions.php', { method: 'POST', body: formData });
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
