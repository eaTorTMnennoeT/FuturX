/**
 * FuturX Admin - 관리자 패널 JavaScript
 */

// 사이드바 토글
let sidebar, sidebarOverlay;
let sidebarInitialized = false;

function toggleSidebar() {
    sidebar?.classList.toggle('open');
    sidebarOverlay?.classList.toggle('active');
}

function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarOverlay?.classList.remove('active');
}

// DOM 로드 후 초기화
function initSidebar() {
    // 여러 번 호출되어도 이벤트 중복 바인딩되지 않도록 가드
    if (sidebarInitialized) return;
    sidebarInitialized = true;

    sidebar = document.querySelector('.sidebar');
    sidebarOverlay = document.getElementById('sidebarOverlay');

    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);

    // X(닫기) 버튼
    const closeBtn = document.getElementById('sidebarClose');
    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeSidebar();
        });
    }

    // 오버레이 클릭 시 닫기
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // 모바일에서 메뉴 클릭 시 자동 닫기
    const isMobile = () => window.matchMedia && window.matchMedia('(max-width: 992px)').matches;
    if (sidebar) {
        sidebar.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && isMobile()) closeSidebar();
        });
    }

    // ESC로 닫기 (모달 로직과 충돌 없게: 사이드바만 닫고 끝)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (sidebar?.classList.contains('open')) closeSidebar();
        }
    });
}

// DOMContentLoaded에서 초기화 (중복 호출 방지 가드 포함)
document.addEventListener('DOMContentLoaded', initSidebar);

// CSRF 토큰
const csrfToken = document.getElementById('csrf_token')?.value || '';

/**
 * API 요청 헬퍼
 */
async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || '요청 처리에 실패했습니다.');
        }

        return result;
    } catch (error) {
        showToast('error', '오류', error.message);
        throw error;
    }
}

/**
 * 토스트 알림
 */
function showToast(type, title, message) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: 'fa-check',
        error: 'fa-times',
        warning: 'fa-exclamation',
        info: 'fa-info'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
        <div class="toast__icon">
            <i class="fas ${icons[type]}"></i>
        </div>
        <div class="toast__content">
            <div class="toast__title">${title}</div>
            <div class="toast__message">${message}</div>
        </div>
        <button class="toast__close">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    // 닫기 버튼
    toast.querySelector('.toast__close').addEventListener('click', () => {
        toast.remove();
    });

    // 자동 제거
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * 모달
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// 모달 외부 클릭시 닫기
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

/**
 * 확인 다이얼로그
 */
function confirmAction(message) {
    return new Promise((resolve) => {
        if (confirm(message)) {
            resolve(true);
        } else {
            resolve(false);
        }
    });
}

/**
 * 숫자 포맷
 */
function formatNumber(num, decimals = 0) {
    return new Intl.NumberFormat('ko-KR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(num);
}

/**
 * 통화 포맷
 */
function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * 날짜 포맷
 */
function formatDate(dateStr, format = 'full') {
    const date = new Date(dateStr);

    switch (format) {
        case 'date':
            return date.toLocaleDateString('ko-KR');
        case 'time':
            return date.toLocaleTimeString('ko-KR');
        case 'full':
        default:
            return date.toLocaleString('ko-KR');
    }
}

/**
 * 테이블 정렬
 */
function sortTable(table, column, type = 'string') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    const sortedRows = rows.sort((a, b) => {
        const aVal = a.cells[column].textContent.trim();
        const bVal = b.cells[column].textContent.trim();

        if (type === 'number') {
            return parseFloat(aVal.replace(/[^0-9.-]/g, '')) - parseFloat(bVal.replace(/[^0-9.-]/g, ''));
        } else if (type === 'date') {
            return new Date(aVal) - new Date(bVal);
        } else {
            return aVal.localeCompare(bVal, 'ko');
        }
    });

    tbody.innerHTML = '';
    sortedRows.forEach(row => tbody.appendChild(row));
}

/**
 * 필터 검색
 */
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}

/**
 * 폼 유효성 검사
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;

    // 필수 필드 검사
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });

    // 이메일 검사
    form.querySelectorAll('[type="email"]').forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            field.classList.add('error');
            isValid = false;
        }
    });

    return isValid;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * 차트 기본 설정
 */
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#848e9c',
                font: {
                    family: 'Inter'
                }
            }
        }
    },
    scales: {
        x: {
            grid: {
                color: '#2b3139'
            },
            ticks: {
                color: '#848e9c'
            }
        },
        y: {
            grid: {
                color: '#2b3139'
            },
            ticks: {
                color: '#848e9c'
            }
        }
    }
};

/**
 * 라인 차트 생성
 */
function createLineChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: datasets.map((ds, i) => ({
                label: ds.label,
                data: ds.data,
                borderColor: ds.color || ['#f0b90b', '#0ecb81', '#f6465d'][i % 3],
                backgroundColor: 'transparent',
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4
            }))
        },
        options: chartDefaults
    });
}

/**
 * 도넛 차트 생성
 */
function createDoughnutChart(canvasId, labels, data, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors || ['#f0b90b', '#0ecb81', '#f6465d', '#1e88e5', '#848e9c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#848e9c',
                        font: {
                            family: 'Inter'
                        },
                        padding: 16
                    }
                }
            }
        }
    });
}

/**
 * 바 차트 생성
 */
function createBarChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: datasets.map((ds, i) => ({
                label: ds.label,
                data: ds.data,
                backgroundColor: ds.color || ['#f0b90b', '#0ecb81', '#f6465d'][i % 3],
                borderRadius: 4
            }))
        },
        options: chartDefaults
    });
}

/**
 * 데이터 새로고침
 */
function refreshData(elementId, url) {
    const element = document.getElementById(elementId);
    if (!element) return;

    element.classList.add('loading');

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.html) {
            element.innerHTML = data.html;
        }
        element.classList.remove('loading');
    })
    .catch(error => {
        console.error('Refresh error:', error);
        element.classList.remove('loading');
    });
}

/**
 * 파일 업로드 미리보기
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
}

/**
 * 클립보드 복사
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('success', '복사 완료', '클립보드에 복사되었습니다.');
    } catch (err) {
        showToast('error', '복사 실패', '클립보드 복사에 실패했습니다.');
    }
}

/**
 * 다크 모드 토글 (향후 확장용)
 */
function toggleDarkMode() {
    document.body.classList.toggle('light-mode');
    localStorage.setItem('theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
}

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 사이드바 초기화
    initSidebar();
    
    // 테마 로드
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
    }

    // 툴팁 초기화 (필요시)
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function() {
            // 툴팁 표시 로직
        });
    });
});

// 전역 함수 노출
window.apiRequest = apiRequest;
window.showToast = showToast;
window.openModal = openModal;
window.closeModal = closeModal;
window.confirmAction = confirmAction;
window.formatNumber = formatNumber;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.createLineChart = createLineChart;
window.createDoughnutChart = createDoughnutChart;
window.createBarChart = createBarChart;
window.copyToClipboard = copyToClipboard;
