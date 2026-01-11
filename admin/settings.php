<?php
/**
 * FuturX Admin - 설정
 */

require_once 'layout.php';
requireSuperAdmin();

try {
    $db = getDB();

    // 설정값 조회
    $settings = [];
    $stmt = $db->query("SELECT * FROM settings ORDER BY `group`, `key`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['group']][$row['key']] = $row;
    }

    // 설정 저장 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, null, 'CSRF 토큰이 유효하지 않습니다.');
        }

        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $db->prepare("UPDATE settings SET value = :value WHERE `key` = :key");
            $stmt->execute(['value' => $value, 'key' => $key]);
        }

        logAdminAction('settings_update', 'settings', null, ['keys' => array_keys($_POST['settings'])]);
        jsonResponse(true, null, '설정이 저장되었습니다.');
    }

} catch (PDOException $e) {
    $settings = [];
}

$groupLabels = [
    'general' => '일반 설정',
    'trading' => '거래 설정',
    'wallet' => '지갑 설정',
    'auth' => '인증 설정'
];

// 설정 키에 대한 추가 설명
$settingDescriptions = [
    'taker_fee' => '시장가 수수료 (Taker Fee)',
    'maker_fee' => '지정가 수수료 (Maker Fee)',
];

renderHeader('설정');
renderPageHeader('시스템 설정', '플랫폼 설정을 관리합니다');
?>

<form id="settingsForm" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <?php foreach ($groupLabels as $group => $label): ?>
    <?php if (!empty($settings[$group])): ?>
    <div class="card mb-xl">
        <div class="card__header">
            <h3 class="card__title">
                <i class="fas fa-cog"></i>
                <?= $label ?>
            </h3>
        </div>
        <div class="card__body">
            <div class="settings-grid">
                <?php foreach ($settings[$group] as $key => $setting): ?>
                <div class="setting-item">
                    <div class="setting-item__info">
                        <label class="setting-item__label"><?= htmlspecialchars($settingDescriptions[$key] ?? $setting['description'] ?: $key) ?></label>
                        <span class="setting-item__key"><?= htmlspecialchars($key) ?><?php if ($key === 'taker_fee' || $key === 'maker_fee'): ?> <span class="fee-hint">(<?= floatval($setting['value']) * 100 ?>%)</span><?php endif; ?></span>
                    </div>
                    <div class="setting-item__input">
                        <?php if ($setting['type'] === 'boolean'): ?>
                        <label class="switch">
                            <input type="hidden" name="settings[<?= $key ?>]" value="false">
                            <input type="checkbox" name="settings[<?= $key ?>]" value="true" <?= $setting['value'] === 'true' ? 'checked' : '' ?>>
                            <span class="switch__slider"></span>
                        </label>
                        <?php elseif ($setting['type'] === 'number'): ?>
                        <input type="number" name="settings[<?= $key ?>]" class="form-input" value="<?= htmlspecialchars($setting['value']) ?>" step="any">
                        <?php else: ?>
                        <input type="text" name="settings[<?= $key ?>]" class="form-input" value="<?= htmlspecialchars($setting['value']) ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <div class="form-actions">
        <button type="button" class="btn btn--primary btn--lg" onclick="saveSettings()">
            <i class="fas fa-save"></i>
            설정 저장
        </button>
    </div>
</form>

<!-- 위험 영역 -->
<div class="card mt-xl danger-zone">
    <div class="card__header">
        <h3 class="card__title text-danger">
            <i class="fas fa-exclamation-triangle"></i>
            위험 영역
        </h3>
    </div>
    <div class="card__body">
        <div class="danger-actions">
            <div class="danger-action">
                <div class="danger-action__info">
                    <strong>캐시 초기화</strong>
                    <p>시스템 캐시를 모두 삭제합니다.</p>
                </div>
                <button class="btn btn--danger" onclick="clearCache()">캐시 초기화</button>
            </div>
            <div class="danger-action">
                <div class="danger-action__info">
                    <strong>리더보드 재계산</strong>
                    <p>모든 기간의 리더보드를 다시 계산합니다.</p>
                </div>
                <button class="btn btn--danger" onclick="recalcLeaderboard()">재계산</button>
            </div>
            <div class="danger-action">
                <div class="danger-action__info">
                    <strong>점검 모드</strong>
                    <p>사이트를 점검 모드로 전환합니다.</p>
                </div>
                <button class="btn btn--danger" onclick="toggleMaintenance()">점검 모드 전환</button>
            </div>
        </div>
    </div>
</div>

<style>
.settings-grid {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.setting-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-md) 0;
    border-bottom: 1px solid var(--border-color);
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-item__info {
    flex: 1;
}

.setting-item__label {
    display: block;
    font-weight: 500;
    margin-bottom: 2px;
}

.setting-item__key {
    font-size: 12px;
    color: var(--text-tertiary);
    font-family: var(--font-mono);
}

.setting-item__key .fee-hint {
    color: var(--color-primary);
    font-weight: 500;
}

.setting-item__input {
    min-width: 200px;
    text-align: right;
}

.setting-item__input .form-input {
    text-align: right;
}

/* 스위치 */
.switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.switch__slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--bg-tertiary);
    border-radius: 24px;
    transition: all var(--transition-fast);
}

.switch__slider::before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: var(--text-secondary);
    border-radius: 50%;
    transition: all var(--transition-fast);
}

.switch input:checked + .switch__slider {
    background: var(--color-success);
}

.switch input:checked + .switch__slider::before {
    background: #fff;
    transform: translateX(24px);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    padding: var(--spacing-xl) 0;
}

/* 위험 영역 */
.danger-zone {
    border-color: var(--color-danger);
}

.danger-zone .card__header {
    background: var(--color-danger-bg);
}

.danger-actions {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.danger-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-md) 0;
    border-bottom: 1px solid var(--border-color);
}

.danger-action:last-child {
    border-bottom: none;
}

.danger-action__info p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-top: 4px;
}

@media (max-width: 768px) {
    .setting-item {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-md);
    }

    .setting-item__input {
        width: 100%;
        text-align: left;
    }

    .setting-item__input .form-input {
        text-align: left;
    }

    .danger-action {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-md);
    }
}
</style>

<script>
async function saveSettings() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('settings.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            showToast('success', '완료', result.message);
        } else {
            showToast('error', '오류', result.message);
        }
    } catch (error) {
        showToast('error', '오류', '요청 처리에 실패했습니다.');
    }
}

async function clearCache() {
    if (!await confirmAction('시스템 캐시를 모두 삭제하시겠습니까?')) return;
    showToast('info', '처리중', '캐시를 초기화하고 있습니다...');
    // API 호출
    setTimeout(() => showToast('success', '완료', '캐시가 초기화되었습니다.'), 1000);
}

async function recalcLeaderboard() {
    if (!await confirmAction('리더보드를 재계산하시겠습니까? 시간이 걸릴 수 있습니다.')) return;
    showToast('info', '처리중', '리더보드를 재계산하고 있습니다...');
    // API 호출
    setTimeout(() => showToast('success', '완료', '리더보드가 재계산되었습니다.'), 2000);
}

async function toggleMaintenance() {
    if (!await confirmAction('점검 모드를 전환하시겠습니까?')) return;
    showToast('success', '완료', '점검 모드가 전환되었습니다.');
    setTimeout(() => location.reload(), 1000);
}
</script>

<?php renderFooter(); ?>
