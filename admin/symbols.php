<?php
/**
 * FuturX Admin - 코인(심볼) 관리
 */

require_once 'layout.php';

// 심볼 목록 조회
$db = getDB();
$symbols = $db->query("SELECT * FROM symbols ORDER BY sort_order ASC, symbol ASC")->fetchAll();

renderHeader('코인 관리');
renderPageHeader('코인 관리', '거래 가능한 코인 목록 및 설정을 관리합니다.');
?>

<style>
.symbols-table {
    width: 100%;
    border-collapse: collapse;
}

.symbols-table th,
.symbols-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.symbols-table th {
    background: var(--bg-tertiary);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--text-secondary);
}

.symbols-table tr:hover {
    background: var(--bg-secondary);
}

.symbol-name {
    display: flex;
    align-items: center;
    gap: 12px;
}

.symbol-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--color-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    color: #000;
}

.symbol-info strong {
    display: block;
    font-size: 14px;
}

.symbol-info span {
    font-size: 12px;
    color: var(--text-secondary);
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge--active {
    background: rgba(14, 203, 129, 0.15);
    color: var(--color-success);
}

.status-badge--inactive {
    background: rgba(246, 70, 93, 0.15);
    color: var(--color-danger);
}

.input-mini {
    width: 80px;
    padding: 6px 10px;
    text-align: center;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 13px;
}

.input-mini:focus {
    border-color: var(--color-primary);
    outline: none;
}

.actions-cell {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon--edit {
    background: rgba(246, 190, 0, 0.15);
    color: var(--color-warning);
}

.btn-icon--edit:hover {
    background: rgba(246, 190, 0, 0.25);
}

.btn-icon--delete {
    background: rgba(246, 70, 93, 0.15);
    color: var(--color-danger);
}

.btn-icon--delete:hover {
    background: rgba(246, 70, 93, 0.25);
}

.btn-icon--toggle {
    background: rgba(14, 203, 129, 0.15);
    color: var(--color-success);
}

.btn-icon--toggle:hover {
    background: rgba(14, 203, 129, 0.25);
}

/* 모달 */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal {
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal__header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal__title {
    font-size: 18px;
    font-weight: 600;
}

.modal__close {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 20px;
    padding: 4px;
}

.modal__close:hover {
    color: var(--text-primary);
}

.modal__body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--text-secondary);
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 14px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--color-primary);
    outline: none;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.modal__footer {
    padding: 16px 24px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

/* 테이블 반응형 */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.symbols-table th,
.symbols-table td {
    white-space: nowrap;
}

.symbols-table th {
    min-width: 80px;
}

.symbols-table th:nth-child(2) {
    min-width: 180px;
}

/* 태블릿 */
@media (max-width: 1024px) {
    .symbols-table th,
    .symbols-table td {
        padding: 10px 12px;
        font-size: 13px;
    }

    .input-mini {
        width: 60px;
        padding: 4px 6px;
        font-size: 12px;
    }

    .btn-icon {
        width: 28px;
        height: 28px;
    }

    .symbol-icon,
    .symbol-logo {
        width: 28px !important;
        height: 28px !important;
    }

    .symbol-info strong {
        font-size: 13px;
    }

    .symbol-info span {
        font-size: 11px;
    }
}

/* 모바일 */
@media (max-width: 768px) {
    .symbols-header {
        flex-direction: column;
        gap: 12px;
        align-items: stretch !important;
    }

    .symbols-header .btn {
        width: 100%;
        justify-content: center;
    }

    .symbols-table {
        width: 100%;
        table-layout: fixed;
    }

    .symbols-table th,
    .symbols-table td {
        padding: 6px 4px;
        font-size: 11px;
    }

    .symbols-table th:nth-child(1),
    .symbols-table td:nth-child(1) {
        display: none; /* 순서 컬럼 숨김 */
    }

    .symbols-table th:nth-child(3),
    .symbols-table td:nth-child(3),
    .symbols-table th:nth-child(4),
    .symbols-table td:nth-child(4),
    .symbols-table th:nth-child(5),
    .symbols-table td:nth-child(5),
    .symbols-table th:nth-child(6),
    .symbols-table td:nth-child(6) {
        display: none; /* 상세 컬럼 숨김 */
    }

    /* 컬럼 너비 조정 */
    .symbols-table th:nth-child(2),
    .symbols-table td:nth-child(2) {
        width: 45%;
    }

    .symbols-table th:nth-child(7),
    .symbols-table td:nth-child(7) {
        width: 18%;
        text-align: center;
    }

    .symbols-table th:nth-child(8),
    .symbols-table td:nth-child(8) {
        width: 17%;
        text-align: center;
    }

    .symbols-table th:nth-child(9),
    .symbols-table td:nth-child(9) {
        width: 20%;
    }

    .input-mini {
        width: 50px;
    }

    .btn-icon {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .actions-cell {
        gap: 2px;
        flex-wrap: nowrap;
    }

    .symbol-name {
        gap: 6px;
    }

    .symbol-icon,
    .symbol-logo {
        width: 22px !important;
        height: 22px !important;
        min-width: 22px;
    }

    .symbol-info {
        overflow: hidden;
    }

    .symbol-info strong {
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .symbol-info span {
        font-size: 9px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    .status-badge {
        padding: 2px 6px;
        font-size: 10px;
    }

    /* 모달 반응형 */
    .modal {
        margin: 16px;
        max-height: calc(100vh - 32px);
    }

    .modal__header,
    .modal__body,
    .modal__footer {
        padding: 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- 헤더 액션 -->
<div class="card mb-lg">
    <div class="card__body symbols-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="color: var(--text-secondary);">총 <?= count($symbols) ?>개 코인</span>
        </div>
        <button class="btn btn--primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> 코인 추가
        </button>
    </div>
</div>

<!-- 심볼 목록 -->
<div class="card">
    <div class="card__body" style="padding: 0;">
        <div class="table-wrapper">
        <table class="symbols-table">
            <thead>
                <tr>
                    <th style="width: 50px;">순서</th>
                    <th>심볼</th>
                    <th class="text-center">가격 소수점</th>
                    <th class="text-center">수량 소수점</th>
                    <th class="text-right">틱 사이즈</th>
                    <th class="text-right">최소 수량</th>
                    <th class="text-center">최대 레버리지</th>
                    <th class="text-center">상태</th>
                    <th class="text-center">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($symbols as $s): ?>
                <tr data-id="<?= $s['id'] ?>">
                    <td>
                        <input type="number" class="input-mini" value="<?= $s['sort_order'] ?>"
                               onchange="updateSortOrder(<?= $s['id'] ?>, this.value)" min="0">
                    </td>
                    <td>
                        <div class="symbol-name">
                            <?php if (!empty($s['logo_url'])): ?>
                            <img src="<?= htmlspecialchars($s['logo_url']) ?>" alt="<?= htmlspecialchars($s['base_asset']) ?>" class="symbol-logo" style="width:32px;height:32px;border-radius:50%;">
                            <?php else: ?>
                            <div class="symbol-icon" style="background:<?= htmlspecialchars($s['color'] ?? '#f7931a') ?>"><?= htmlspecialchars($s['icon'] ?? substr($s['base_asset'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <div class="symbol-info">
                                <strong><?= htmlspecialchars($s['symbol']) ?></strong>
                                <span><?= htmlspecialchars($s['display_name'] ?? $s['base_asset']) ?> (<?= htmlspecialchars($s['base_asset']) ?>/<?= htmlspecialchars($s['quote_asset']) ?>)</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><?= $s['price_decimals'] ?></td>
                    <td class="text-center"><?= $s['qty_decimals'] ?></td>
                    <td class="text-right"><?= rtrim(rtrim($s['tick_size'], '0'), '.') ?></td>
                    <td class="text-right"><?= rtrim(rtrim($s['min_qty'], '0'), '.') ?></td>
                    <td class="text-center"><?= $s['max_leverage'] ?>x</td>
                    <td class="text-center">
                        <span class="status-badge status-badge--<?= $s['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $s['is_active'] ? '활성' : '비활성' ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions-cell" style="justify-content: center;">
                            <button class="btn-icon btn-icon--toggle" onclick="toggleStatus(<?= $s['id'] ?>, <?= $s['is_active'] ?>)" title="<?= $s['is_active'] ? '비활성화' : '활성화' ?>">
                                <i class="fas fa-<?= $s['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                            <button class="btn-icon btn-icon--edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($s)) ?>)" title="수정">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon btn-icon--delete" onclick="deleteSymbol(<?= $s['id'] ?>, '<?= $s['symbol'] ?>')" title="삭제">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- 추가/수정 모달 -->
<div class="modal-overlay" id="symbolModal">
    <div class="modal">
        <div class="modal__header">
            <h3 class="modal__title" id="modalTitle">코인 추가</h3>
            <button class="modal__close" onclick="closeModal()">&times;</button>
        </div>
        <form id="symbolForm" onsubmit="saveSymbol(event)">
            <input type="hidden" name="id" id="symbolId">
            <div class="modal__body">
                <div class="form-row">
                    <div class="form-group">
                        <label>심볼 *</label>
                        <input type="text" name="symbol" id="symbolName" required placeholder="BTCUSDT" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label>기초 자산 *</label>
                        <input type="text" name="base_asset" id="baseAsset" required placeholder="BTC" style="text-transform: uppercase;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>표시명</label>
                        <input type="text" name="display_name" id="displayName" placeholder="Bitcoin">
                    </div>
                    <div class="form-group">
                        <label>아이콘 (이모지/문자)</label>
                        <input type="text" name="icon" id="symbolIcon" placeholder="₿" maxlength="2">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>색상</label>
                        <input type="color" name="color" id="symbolColor" value="#f7931a" style="height:40px;">
                    </div>
                    <div class="form-group">
                        <label>로고 URL</label>
                        <input type="url" name="logo_url" id="logoUrl" placeholder="https://cryptologos.cc/...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>가격 소수점 자릿수</label>
                        <input type="number" name="price_decimals" id="priceDecimals" value="2" min="0" max="8">
                    </div>
                    <div class="form-group">
                        <label>수량 소수점 자릿수</label>
                        <input type="number" name="qty_decimals" id="qtyDecimals" value="3" min="0" max="8">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>틱 사이즈 (최소 가격 단위)</label>
                        <input type="text" name="tick_size" id="tickSize" value="0.01" placeholder="0.01">
                    </div>
                    <div class="form-group">
                        <label>최소 주문 수량</label>
                        <input type="text" name="min_qty" id="minQty" value="0.001" placeholder="0.001">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>최대 레버리지</label>
                        <input type="number" name="max_leverage" id="maxLeverage" value="125" min="1" max="500">
                    </div>
                    <div class="form-group">
                        <label>정렬 순서</label>
                        <input type="number" name="sort_order" id="sortOrder" value="0" min="0">
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeModal()">취소</button>
                <button type="submit" class="btn btn--primary">저장</button>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>

<script>
// csrfToken은 admin.js에서 전역 선언됨

function openAddModal() {
    document.getElementById('modalTitle').textContent = '코인 추가';
    document.getElementById('symbolForm').reset();
    document.getElementById('symbolId').value = '';
    document.getElementById('symbolName').disabled = false;
    document.getElementById('symbolModal').classList.add('active');
}

function openEditModal(data) {
    document.getElementById('modalTitle').textContent = '코인 수정';
    document.getElementById('symbolId').value = data.id;
    document.getElementById('symbolName').value = data.symbol;
    document.getElementById('symbolName').disabled = true; // 심볼명은 수정 불가
    document.getElementById('baseAsset').value = data.base_asset;
    document.getElementById('displayName').value = data.display_name || '';
    document.getElementById('symbolIcon').value = data.icon || '';
    document.getElementById('symbolColor').value = data.color || '#f7931a';
    document.getElementById('logoUrl').value = data.logo_url || '';
    document.getElementById('priceDecimals').value = data.price_decimals;
    document.getElementById('qtyDecimals').value = data.qty_decimals;
    document.getElementById('tickSize').value = parseFloat(data.tick_size);
    document.getElementById('minQty').value = parseFloat(data.min_qty);
    document.getElementById('maxLeverage').value = data.max_leverage;
    document.getElementById('sortOrder').value = data.sort_order;
    document.getElementById('symbolModal').classList.add('active');
}

function closeModal() {
    document.getElementById('symbolModal').classList.remove('active');
}

async function saveSymbol(e) {
    e.preventDefault();
    const form = document.getElementById('symbolForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    data.symbol = data.symbol.toUpperCase();
    data.base_asset = data.base_asset.toUpperCase();

    try {
        const response = await fetch('api/symbols.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'save', ...data })
        });
        const result = await response.json();
        if (result.success) {
            showToast('success', '완료', result.message);
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('error', '오류', result.error);
        }
    } catch (err) {
        showToast('error', '오류', '서버 통신 오류');
    }
}

async function toggleStatus(id, currentStatus) {
    try {
        const response = await fetch('api/symbols.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'toggle', id, is_active: currentStatus ? 0 : 1 })
        });
        const result = await response.json();
        if (result.success) {
            showToast('success', '완료', result.message);
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('error', '오류', result.error);
        }
    } catch (err) {
        showToast('error', '오류', '서버 통신 오류');
    }
}

async function updateSortOrder(id, order) {
    try {
        const response = await fetch('api/symbols.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'sort', id, sort_order: order })
        });
        const result = await response.json();
        if (result.success) {
            showToast('success', '완료', '순서가 변경되었습니다.');
        }
    } catch (err) {
        showToast('error', '오류', '서버 통신 오류');
    }
}

async function deleteSymbol(id, symbol) {
    if (!confirm(`정말 ${symbol}을(를) 삭제하시겠습니까?\n\n이 코인과 관련된 주문/포지션이 있으면 문제가 발생할 수 있습니다.`)) {
        return;
    }
    try {
        const response = await fetch('api/symbols.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'delete', id })
        });
        const result = await response.json();
        if (result.success) {
            showToast('success', '완료', result.message);
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('error', '오류', result.error);
        }
    } catch (err) {
        showToast('error', '오류', '서버 통신 오류');
    }
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});

// 모달 외부 클릭시 닫기
document.getElementById('symbolModal').addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) closeModal();
});
</script>
