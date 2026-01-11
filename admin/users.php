<?php
/**
 * FuturX Admin - 회원관리
 */

require_once 'layout.php';

// 페이지네이션
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 필터
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$role = $_GET['role'] ?? '';

try {
    $db = getDB();

    // WHERE 조건 생성
    $where = ['1=1'];
    $params = [];

    if ($search) {
        $where[] = "(username LIKE :search OR email LIKE :search OR nickname LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($status) {
        $where[] = "status = :status";
        $params['status'] = $status;
    }

    if ($role) {
        $where[] = "role = :role";
        $params['role'] = $role;
    }

    $whereClause = implode(' AND ', $where);

    // 총 개수
    $countSQL = "SELECT COUNT(*) FROM users WHERE $whereClause";
    $stmt = $db->prepare($countSQL);
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);

    // 데이터 조회
    $sql = "SELECT u.*,
                (SELECT COALESCE(SUM(balance), 0) FROM wallets WHERE user_id = u.id) as total_balance,
                (SELECT COUNT(*) FROM trades WHERE user_id = u.id) as trade_count
            FROM users u
            WHERE $whereClause
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 통계
    $statsSQL = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
                    SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
                 FROM users";
    $stats = $db->query($statsSQL)->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $users = [];
    $totalCount = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'active' => 0, 'suspended' => 0, 'banned' => 0, 'today' => 0];
}

// 회원 상태 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(false, null, 'CSRF 토큰이 유효하지 않습니다.');
    }

    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'suspend':
                $stmt = $db->prepare("UPDATE users SET status = 'suspended' WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                logAdminAction('user_suspend', 'users', $userId);
                jsonResponse(true, null, '회원이 정지되었습니다.');
                break;

            case 'activate':
                $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                logAdminAction('user_activate', 'users', $userId);
                jsonResponse(true, null, '회원이 활성화되었습니다.');
                break;

            case 'ban':
                $stmt = $db->prepare("UPDATE users SET status = 'banned' WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                logAdminAction('user_ban', 'users', $userId);
                jsonResponse(true, null, '회원이 차단되었습니다.');
                break;

            case 'delete':
                if (!isSuperAdmin()) {
                    jsonResponse(false, null, '슈퍼관리자 권한이 필요합니다.');
                }
                $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role NOT IN ('admin', 'superadmin')");
                $stmt->execute(['id' => $userId]);
                logAdminAction('user_delete', 'users', $userId);
                jsonResponse(true, null, '회원이 삭제되었습니다.');
                break;
        }
    } catch (PDOException $e) {
        jsonResponse(false, null, '처리 중 오류가 발생했습니다.');
    }
}

renderHeader('회원관리');
renderPageHeader('회원관리', '등록된 회원을 관리합니다', [
    ['label' => '회원 추가', 'icon' => 'fa-plus', 'onclick' => "openModal('addUserModal')"]
]);
?>

<!-- 통계 카드 -->
<div class="stats-grid">
    <?php renderStatCard('fa-users', '전체 회원', number_format($stats['total']) . '명'); ?>
    <?php renderStatCard('fa-user-check', '활성 회원', number_format($stats['active']) . '명'); ?>
    <?php renderStatCard('fa-user-clock', '정지 회원', number_format($stats['suspended']) . '명'); ?>
    <?php renderStatCard('fa-user-plus', '오늘 가입', number_format($stats['today']) . '명'); ?>
</div>

<!-- 필터 -->
<div class="card mb-xl">
    <div class="card__body">
        <form method="GET" class="filter-form">
            <div class="filter-form__row">
                <div class="filter-form__group">
                    <label class="form-label">검색</label>
                    <input type="text" name="search" class="form-input" placeholder="이름, 이메일, 닉네임" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-form__group">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <option value="">전체</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>활성</option>
                        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>정지</option>
                        <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>차단</option>
                    </select>
                </div>
                <div class="filter-form__group">
                    <label class="form-label">등급</label>
                    <select name="role" class="form-select">
                        <option value="">전체</option>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>일반회원</option>
                        <option value="vip" <?= $role === 'vip' ? 'selected' : '' ?>>VIP</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>관리자</option>
                    </select>
                </div>
                <div class="filter-form__actions">
                    <button type="submit" class="btn btn--primary">
                        <i class="fas fa-search"></i> 검색
                    </button>
                    <a href="users.php" class="btn btn--secondary">초기화</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 회원 목록 -->
<div class="card">
    <div class="card__header">
        <h3 class="card__title">
            <i class="fas fa-list"></i>
            회원 목록
            <span class="text-secondary">(<?= number_format($totalCount) ?>명)</span>
        </h3>
    </div>
    <div class="table-wrapper">
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>회원정보</th>
                    <th>등급</th>
                    <th>상태</th>
                    <th>잔액</th>
                    <th>거래 수</th>
                    <th>가입일</th>
                    <th>최근 로그인</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>회원이 없습니다</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-cell__avatar">
                                <?php if ($user['avatar']): ?>
                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="user-cell__info">
                                <span class="user-cell__name"><?= htmlspecialchars($user['nickname'] ?: $user['username']) ?></span>
                                <span class="user-cell__email"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                        $roleLabels = [
                            'user' => ['일반', 'secondary'],
                            'vip' => ['VIP', 'warning'],
                            'admin' => ['관리자', 'info'],
                            'superadmin' => ['슈퍼관리자', 'danger']
                        ];
                        $r = $roleLabels[$user['role']] ?? ['알수없음', 'secondary'];
                        ?>
                        <span class="badge badge--<?= $r[1] ?>"><?= $r[0] ?></span>
                    </td>
                    <td>
                        <?php
                        $statusLabels = [
                            'active' => ['활성', 'success'],
                            'suspended' => ['정지', 'warning'],
                            'banned' => ['차단', 'danger']
                        ];
                        $s = $statusLabels[$user['status']] ?? ['알수없음', 'secondary'];
                        ?>
                        <span class="badge badge--<?= $s[1] ?>"><?= $s[0] ?></span>
                    </td>
                    <td class="num-mono">$<?= number_format($user['total_balance'], 2) ?></td>
                    <td><?= number_format($user['trade_count']) ?></td>
                    <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?= $user['last_login_at'] ? date('m/d H:i', strtotime($user['last_login_at'])) : '-' ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="user_detail.php?id=<?= $user['id'] ?>" class="btn btn--sm btn--outline" title="상세보기">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($user['role'] !== 'superadmin'): ?>
                            <button class="btn btn--sm btn--secondary" onclick="showUserActions(<?= $user['id'] ?>, '<?= $user['status'] ?>')" title="관리">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <?php endif; ?>
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
        <?php
        $baseUrl = "users.php?search=" . urlencode($search) . "&status=" . urlencode($status) . "&role=" . urlencode($role);
        renderPagination($page, $totalPages, $baseUrl);
        ?>
    </div>
    <?php endif; ?>
</div>

<!-- 회원 추가 모달 -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal__header">
            <h3 class="modal__title">회원 추가</h3>
            <button class="modal__close" onclick="closeModal('addUserModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <form id="addUserForm">
                <div class="form-group">
                    <label class="form-label required">이메일</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">사용자명</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">비밀번호</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">닉네임</label>
                        <input type="text" name="nickname" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">등급</label>
                        <select name="role" class="form-select">
                            <option value="user">일반회원</option>
                            <option value="vip">VIP</option>
                            <?php if (isSuperAdmin()): ?>
                            <option value="admin">관리자</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal__footer">
            <button class="btn btn--secondary" onclick="closeModal('addUserModal')">취소</button>
            <button class="btn btn--primary" onclick="submitAddUser()">추가</button>
        </div>
    </div>
</div>

<!-- 회원 액션 모달 -->
<div class="modal-overlay" id="userActionsModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal__header">
            <h3 class="modal__title">회원 관리</h3>
            <button class="modal__close" onclick="closeModal('userActionsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <input type="hidden" id="actionUserId">
            <div class="action-list">
                <button class="action-list__btn" onclick="userAction('activate')" id="activateBtn">
                    <i class="fas fa-user-check text-success"></i>
                    <span>회원 활성화</span>
                </button>
                <button class="action-list__btn" onclick="userAction('suspend')" id="suspendBtn">
                    <i class="fas fa-user-clock text-warning"></i>
                    <span>회원 정지</span>
                </button>
                <button class="action-list__btn" onclick="userAction('ban')">
                    <i class="fas fa-user-slash text-danger"></i>
                    <span>회원 차단</span>
                </button>
                <?php if (isSuperAdmin()): ?>
                <hr>
                <button class="action-list__btn action-list__btn--danger" onclick="userAction('delete')">
                    <i class="fas fa-trash"></i>
                    <span>회원 삭제</span>
                </button>
                <?php endif; ?>
            </div>
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
    min-width: 200px;
}

.filter-form__actions {
    display: flex;
    gap: var(--spacing-sm);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.user-cell__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bg-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-tertiary);
    overflow: hidden;
}

.user-cell__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-cell__info {
    display: flex;
    flex-direction: column;
}

.user-cell__name {
    font-weight: 500;
}

.user-cell__email {
    font-size: 12px;
    color: var(--text-secondary);
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

.action-list__btn--danger:hover {
    background: var(--color-danger-bg);
    color: var(--color-danger);
}

.action-list hr {
    border: none;
    border-top: 1px solid var(--border-color);
    margin: var(--spacing-sm) 0;
}
</style>

<script>
function showUserActions(userId, status) {
    document.getElementById('actionUserId').value = userId;

    // 현재 상태에 따라 버튼 표시/숨김
    document.getElementById('activateBtn').style.display = status !== 'active' ? '' : 'none';
    document.getElementById('suspendBtn').style.display = status !== 'suspended' ? '' : 'none';

    openModal('userActionsModal');
}

async function userAction(action) {
    const userId = document.getElementById('actionUserId').value;

    const messages = {
        activate: '이 회원을 활성화하시겠습니까?',
        suspend: '이 회원을 정지하시겠습니까?',
        ban: '이 회원을 차단하시겠습니까?',
        delete: '이 회원을 영구 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.'
    };

    if (!await confirmAction(messages[action])) return;

    const formData = new FormData();
    formData.append('action', action);
    formData.append('user_id', userId);
    formData.append('csrf_token', document.getElementById('csrf_token').value);

    try {
        const response = await fetch('users.php', {
            method: 'POST',
            body: formData
        });
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

    closeModal('userActionsModal');
}

async function submitAddUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    formData.append('action', 'add');
    formData.append('csrf_token', document.getElementById('csrf_token').value);

    try {
        const response = await fetch('user_api.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            showToast('success', '완료', '회원이 추가되었습니다.');
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
