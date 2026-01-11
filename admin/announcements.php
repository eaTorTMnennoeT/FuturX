<?php
/**
 * FuturX Admin - 공지사항 관리
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'layout.php';

$db = getDB();

// 공지사항 테이블 확인 및 생성
try {
    $db->query("SELECT 1 FROM announcements LIMIT 1");
} catch (PDOException $e) {
    // 테이블 생성
    $db->exec("
        CREATE TABLE announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            type ENUM('notice', 'event', 'maintenance', 'update') DEFAULT 'notice',
            priority INT DEFAULT 0,
            is_popup TINYINT(1) DEFAULT 0,
            is_pinned TINYINT(1) DEFAULT 0,
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            views INT DEFAULT 0,
            author_id INT,
            published_at DATETIME,
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
}

// 액션 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'notice';
        $priority = intval($_POST['priority'] ?? 0);
        $isPopup = isset($_POST['is_popup']) ? 1 : 0;
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $status = $_POST['status'] ?? 'draft';
        $expiresAt = $_POST['expires_at'] ?: null;

        if ($action === 'create') {
            $stmt = $db->prepare("
                INSERT INTO announcements (title, content, type, priority, is_popup, is_pinned, status, author_id, published_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
            $stmt->execute([$title, $content, $type, $priority, $isPopup, $isPinned, $status, $_SESSION['admin_id'] ?? 0, $publishedAt, $expiresAt]);

            header('Location: announcements.php?success=created');
            exit;
        } else {
            $stmt = $db->prepare("
                UPDATE announcements SET
                    title = ?, content = ?, type = ?, priority = ?, is_popup = ?, is_pinned = ?, status = ?, expires_at = ?,
                    published_at = CASE WHEN status = 'published' AND published_at IS NULL THEN NOW() ELSE published_at END
                WHERE id = ?
            ");
            $stmt->execute([$title, $content, $type, $priority, $isPopup, $isPinned, $status, $expiresAt, $id]);

            header('Location: announcements.php?success=updated');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: announcements.php?success=deleted');
        exit;
    }
}

// 필터
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

$where = ["1=1"];
$params = [];

if ($status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($type !== 'all') {
    $where[] = "type = ?";
    $params[] = $type;
}

$whereClause = implode(' AND ', $where);

// 총 개수
$stmt = $db->prepare("SELECT COUNT(*) FROM announcements WHERE $whereClause");
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 공지사항 목록
$sql = "SELECT * FROM announcements WHERE $whereClause ORDER BY is_pinned DESC, priority DESC, created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 통계
$stats = [];
$stmt = $db->query("SELECT COUNT(*) FROM announcements WHERE status = 'published'");
$stats['published'] = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM announcements WHERE status = 'draft'");
$stats['draft'] = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM announcements WHERE is_popup = 1 AND status = 'published'");
$stats['popup'] = $stmt->fetchColumn();
$stmt = $db->query("SELECT COALESCE(SUM(views), 0) FROM announcements");
$stats['total_views'] = $stmt->fetchColumn();

renderHeader('공지사항 관리');
renderPageHeader('공지사항 관리', '플랫폼 공지사항을 관리합니다', [
    ['label' => '새 공지 작성', 'icon' => 'fa-plus', 'class' => 'btn--primary', 'onclick' => 'openCreateModal()']
]);
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">
    <i class="fas fa-check-circle"></i>
    <?php
    switch ($_GET['success']) {
        case 'created': echo '공지사항이 등록되었습니다.'; break;
        case 'updated': echo '공지사항이 수정되었습니다.'; break;
        case 'deleted': echo '공지사항이 삭제되었습니다.'; break;
    }
    ?>
</div>
<?php endif; ?>

<!-- 통계 카드 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-success-bg); color: var(--color-success);">
            <i class="fas fa-bullhorn"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">게시중</span>
            <span class="stat-card__value"><?= number_format($stats['published']) ?>개</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
            <i class="fas fa-edit"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">임시저장</span>
            <span class="stat-card__value"><?= number_format($stats['draft']) ?>개</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-info-bg); color: var(--color-info);">
            <i class="fas fa-window-restore"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">팝업 공지</span>
            <span class="stat-card__value"><?= number_format($stats['popup']) ?>개</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon" style="background: var(--color-primary-bg); color: var(--color-primary);">
            <i class="fas fa-eye"></i>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__label">총 조회수</span>
            <span class="stat-card__value"><?= number_format($stats['total_views']) ?></span>
        </div>
    </div>
</div>

<!-- 필터 -->
<div class="filter-bar">
    <form method="get" class="filter-form">
        <div class="filter-group">
            <label>상태</label>
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>전체</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>게시중</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>임시저장</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>보관됨</option>
            </select>
        </div>
        <div class="filter-group">
            <label>유형</label>
            <select name="type" class="form-control" onchange="this.form.submit()">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>전체</option>
                <option value="notice" <?= $type === 'notice' ? 'selected' : '' ?>>공지</option>
                <option value="event" <?= $type === 'event' ? 'selected' : '' ?>>이벤트</option>
                <option value="maintenance" <?= $type === 'maintenance' ? 'selected' : '' ?>>점검</option>
                <option value="update" <?= $type === 'update' ? 'selected' : '' ?>>업데이트</option>
            </select>
        </div>
    </form>
</div>

<!-- 공지사항 테이블 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-list"></i>
            공지사항 목록
            <span class="badge badge--secondary"><?= number_format($totalCount) ?>개</span>
        </h3>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th>제목</th>
                    <th width="80">유형</th>
                    <th width="80">상태</th>
                    <th width="60">팝업</th>
                    <th width="60">고정</th>
                    <th width="80">조회수</th>
                    <th width="120">게시일</th>
                    <th width="120">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-inbox"></i> 공지사항이 없습니다
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                <tr>
                    <td><?= $ann['id'] ?></td>
                    <td>
                        <div class="title-cell">
                            <?php if ($ann['is_pinned']): ?>
                            <i class="fas fa-thumbtack text-warning" title="고정됨"></i>
                            <?php endif; ?>
                            <a href="#" onclick="viewAnnouncement(<?= $ann['id'] ?>); return false;">
                                <?= htmlspecialchars($ann['title']) ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <span class="type-badge type-<?= $ann['type'] ?>">
                            <?= getTypeName($ann['type']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $ann['status'] ?>">
                            <?= getStatusName($ann['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($ann['is_popup']): ?>
                        <i class="fas fa-check-circle text-success"></i>
                        <?php else: ?>
                        <i class="fas fa-minus-circle text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($ann['is_pinned']): ?>
                        <i class="fas fa-check-circle text-success"></i>
                        <?php else: ?>
                        <i class="fas fa-minus-circle text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= number_format($ann['views']) ?></td>
                    <td class="text-muted">
                        <?= $ann['published_at'] ? date('Y-m-d', strtotime($ann['published_at'])) : '-' ?>
                    </td>
                    <td>
                        <button class="btn btn--sm btn--outline" onclick="editAnnouncement(<?= $ann['id'] ?>)" title="수정">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn--sm btn--danger-outline" onclick="deleteAnnouncement(<?= $ann['id'] ?>)" title="삭제">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card__footer">
        <?php renderPagination($page, $totalPages, "announcements.php?status=$status&type=$type"); ?>
    </div>
    <?php endif; ?>
</div>

<!-- 공지사항 작성/수정 모달 -->
<div class="modal-overlay" id="announcementModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal__header">
            <h3 class="modal__title" id="modalTitle">새 공지 작성</h3>
            <button type="button" class="modal__close" onclick="closeModal('announcementModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="announcementForm" method="post">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">

            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label">제목 <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="formTitle" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">유형</label>
                        <select name="type" id="formType" class="form-control">
                            <option value="notice">공지</option>
                            <option value="event">이벤트</option>
                            <option value="maintenance">점검</option>
                            <option value="update">업데이트</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">상태</label>
                        <select name="status" id="formStatus" class="form-control">
                            <option value="draft">임시저장</option>
                            <option value="published">게시</option>
                            <option value="archived">보관</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">우선순위</label>
                        <input type="number" name="priority" id="formPriority" class="form-control" value="0" min="0" max="100">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">내용 <span class="text-danger">*</span></label>
                    <textarea name="content" id="formContent" class="form-control" rows="8" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">만료일 (선택)</label>
                        <input type="datetime-local" name="expires_at" id="formExpiresAt" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-check">
                        <input type="checkbox" name="is_popup" id="formIsPopup" class="form-check-input">
                        <label for="formIsPopup" class="form-check-label">팝업으로 표시</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_pinned" id="formIsPinned" class="form-check-input">
                        <label for="formIsPinned" class="form-check-label">상단 고정</label>
                    </div>
                </div>
            </div>

            <div class="modal__footer">
                <button type="button" class="btn btn--secondary" onclick="closeModal('announcementModal')">취소</button>
                <button type="submit" class="btn btn--primary">
                    <i class="fas fa-save"></i> 저장
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 공지사항 보기 모달 -->
<div class="modal-overlay" id="viewModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal__header">
            <h3 class="modal__title" id="viewTitle">공지사항</h3>
            <button type="button" class="modal__close" onclick="closeModal('viewModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <div id="viewContent"></div>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--secondary" onclick="closeModal('viewModal')">닫기</button>
        </div>
    </div>
</div>

<?php
function getTypeName($type) {
    $types = ['notice' => '공지', 'event' => '이벤트', 'maintenance' => '점검', 'update' => '업데이트'];
    return $types[$type] ?? $type;
}

function getStatusName($status) {
    $statuses = ['draft' => '임시저장', 'published' => '게시중', 'archived' => '보관됨'];
    return $statuses[$status] ?? $status;
}

// 공지사항 데이터를 JavaScript에서 사용하기 위해 JSON으로 변환
$announcementsJson = json_encode($announcements);
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
}

.filter-bar {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 16px;
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-secondary);
}

.title-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.title-cell a {
    color: var(--text-primary);
    text-decoration: none;
    font-weight: 500;
}

.title-cell a:hover {
    color: var(--color-primary);
}

.type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.type-badge.type-notice { background: var(--color-primary-bg); color: var(--color-primary); }
.type-badge.type-event { background: var(--color-success-bg); color: var(--color-success); }
.type-badge.type-maintenance { background: var(--color-warning-bg); color: var(--color-warning); }
.type-badge.type-update { background: var(--color-info-bg); color: var(--color-info); }

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.status-badge.status-published { background: var(--color-success-bg); color: var(--color-success); }
.status-badge.status-draft { background: var(--color-warning-bg); color: var(--color-warning); }
.status-badge.status-archived { background: var(--bg-tertiary); color: var(--text-secondary); }

.alert {
    padding: 12px 16px;
    border-radius: var(--radius-lg);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert--success {
    background: var(--color-success-bg);
    color: var(--color-success);
    border: 1px solid var(--color-success);
}

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check-input {
    width: 18px;
    height: 18px;
}

.form-check-label {
    font-size: 14px;
}
</style>

<script>
const announcements = <?= $announcementsJson ?>;

function openCreateModal() {
    document.getElementById('modalTitle').textContent = '새 공지 작성';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('formTitle').value = '';
    document.getElementById('formContent').value = '';
    document.getElementById('formType').value = 'notice';
    document.getElementById('formStatus').value = 'draft';
    document.getElementById('formPriority').value = '0';
    document.getElementById('formExpiresAt').value = '';
    document.getElementById('formIsPopup').checked = false;
    document.getElementById('formIsPinned').checked = false;

    openModal('announcementModal');
}

function editAnnouncement(id) {
    const ann = announcements.find(a => a.id == id);
    if (!ann) return;

    document.getElementById('modalTitle').textContent = '공지 수정';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = ann.id;
    document.getElementById('formTitle').value = ann.title;
    document.getElementById('formContent').value = ann.content;
    document.getElementById('formType').value = ann.type;
    document.getElementById('formStatus').value = ann.status;
    document.getElementById('formPriority').value = ann.priority;
    document.getElementById('formExpiresAt').value = ann.expires_at ? ann.expires_at.slice(0, 16) : '';
    document.getElementById('formIsPopup').checked = ann.is_popup == 1;
    document.getElementById('formIsPinned').checked = ann.is_pinned == 1;

    openModal('announcementModal');
}

function viewAnnouncement(id) {
    const ann = announcements.find(a => a.id == id);
    if (!ann) return;

    document.getElementById('viewTitle').textContent = ann.title;
    document.getElementById('viewContent').innerHTML = `
        <div class="view-meta" style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
            <span class="type-badge type-${ann.type}">${getTypeName(ann.type)}</span>
            <span class="text-muted" style="margin-left: 12px;">
                <i class="fas fa-eye"></i> ${ann.views}회
            </span>
            <span class="text-muted" style="margin-left: 12px;">
                <i class="fas fa-calendar"></i> ${ann.published_at || ann.created_at}
            </span>
        </div>
        <div class="view-body" style="white-space: pre-wrap; line-height: 1.6;">
            ${escapeHtml(ann.content)}
        </div>
    `;

    openModal('viewModal');
}

function deleteAnnouncement(id) {
    if (!confirm('이 공지사항을 삭제하시겠습니까?')) return;

    const form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function getTypeName(type) {
    const types = { notice: '공지', event: '이벤트', maintenance: '점검', update: '업데이트' };
    return types[type] || type;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php renderFooter(); ?>
