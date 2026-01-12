/**
 * Toast Notification System
 * 전역 알림 시스템 - 모든 페이지에서 사용 가능
 *
 * 사용법:
 * Toast.success('성공!', '주문이 체결되었습니다');
 * Toast.error('오류', '주문 실패');
 * Toast.warning('경고', '잔액이 부족합니다');
 * Toast.info('안내', '새로운 업데이트가 있습니다');
 *
 * 옵션:
 * Toast.show({ type: 'success', title: '제목', message: '내용', duration: 5000 });
 */

(function() {
    'use strict';

    // Toast 컨테이너 생성
    let container = null;
    const toasts = [];
    const MAX_TOASTS = 5;

    // 아이콘 정의
    const icons = {
        success: '✓',
        error: '✕',
        warning: '!',
        info: 'i'
    };

    // 컨테이너 초기화
    function initContainer() {
        if (container) return container;

        container = document.createElement('div');
        container.className = 'toast-container';
        container.id = 'toastContainer';
        document.body.appendChild(container);

        return container;
    }

    // Toast 생성
    function createToast(options) {
        const {
            type = 'info',
            title = '',
            message = '',
            duration = 4000,
            closable = true,
            showProgress = true
        } = options;

        initContainer();

        // 최대 개수 초과 시 가장 오래된 것 제거
        if (toasts.length >= MAX_TOASTS) {
            removeToast(toasts[0].element);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <div class="toast__icon">${icons[type] || icons.info}</div>
            <div class="toast__content">
                ${title ? `<div class="toast__title">${escapeHtml(title)}</div>` : ''}
                ${message ? `<div class="toast__message">${escapeHtml(message)}</div>` : ''}
            </div>
            ${closable ? '<button class="toast__close" aria-label="닫기">×</button>' : ''}
            ${showProgress && duration > 0 ? '<div class="toast__progress"></div>' : ''}
        `;

        // 닫기 버튼 이벤트
        if (closable) {
            const closeBtn = toast.querySelector('.toast__close');
            closeBtn.addEventListener('click', () => removeToast(toast));
        }

        // 컨테이너에 추가
        container.appendChild(toast);

        // 토스트 객체 저장
        const toastObj = { element: toast, timeout: null };
        toasts.push(toastObj);

        // 애니메이션 시작 (약간의 딜레이 후)
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });
        });

        // 프로그레스 바 애니메이션
        if (showProgress && duration > 0) {
            const progress = toast.querySelector('.toast__progress');
            if (progress) {
                progress.style.width = '100%';
                requestAnimationFrame(() => {
                    progress.style.transitionDuration = duration + 'ms';
                    progress.style.width = '0%';
                });
            }
        }

        // 자동 제거 타이머
        if (duration > 0) {
            toastObj.timeout = setTimeout(() => {
                removeToast(toast);
            }, duration);
        }

        // 호버 시 타이머 일시정지
        toast.addEventListener('mouseenter', () => {
            if (toastObj.timeout) {
                clearTimeout(toastObj.timeout);
                toastObj.timeout = null;
            }
            const progress = toast.querySelector('.toast__progress');
            if (progress) {
                progress.style.transitionDuration = '0ms';
            }
        });

        // 호버 해제 시 타이머 재시작
        toast.addEventListener('mouseleave', () => {
            if (duration > 0 && !toastObj.timeout) {
                const progress = toast.querySelector('.toast__progress');
                const remainingWidth = progress ? parseFloat(getComputedStyle(progress).width) : 0;
                const containerWidth = progress ? parseFloat(getComputedStyle(progress.parentElement).width) : 1;
                const remainingRatio = remainingWidth / containerWidth;
                const remainingTime = duration * remainingRatio;

                if (progress && remainingTime > 0) {
                    progress.style.transitionDuration = remainingTime + 'ms';
                    progress.style.width = '0%';
                }

                toastObj.timeout = setTimeout(() => {
                    removeToast(toast);
                }, remainingTime > 0 ? remainingTime : 1000);
            }
        });

        return toast;
    }

    // Toast 제거
    function removeToast(toast) {
        if (!toast || !toast.parentElement) return;

        // 타이머 정리
        const toastIndex = toasts.findIndex(t => t.element === toast);
        if (toastIndex > -1) {
            if (toasts[toastIndex].timeout) {
                clearTimeout(toasts[toastIndex].timeout);
            }
            toasts.splice(toastIndex, 1);
        }

        // 애니메이션 후 제거
        toast.classList.remove('show');
        toast.classList.add('hide');

        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    }

    // 모든 Toast 제거
    function clearAll() {
        toasts.forEach(t => {
            if (t.timeout) clearTimeout(t.timeout);
        });
        toasts.length = 0;

        if (container) {
            container.innerHTML = '';
        }
    }

    // HTML 이스케이프
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 공개 API
    window.Toast = {
        show: createToast,

        success: function(title, message, duration) {
            return createToast({
                type: 'success',
                title: title,
                message: message,
                duration: duration || 4000
            });
        },

        error: function(title, message, duration) {
            return createToast({
                type: 'error',
                title: title,
                message: message,
                duration: duration || 6000
            });
        },

        warning: function(title, message, duration) {
            return createToast({
                type: 'warning',
                title: title,
                message: message,
                duration: duration || 5000
            });
        },

        info: function(title, message, duration) {
            return createToast({
                type: 'info',
                title: title,
                message: message,
                duration: duration || 4000
            });
        },

        // 간단한 메시지 (제목 없이)
        message: function(text, type) {
            return createToast({
                type: type || 'info',
                title: '',
                message: text,
                duration: 3000
            });
        },

        // 거래 알림 (특수 케이스)
        trade: function(side, symbol, qty, price) {
            const isBuy = side.toLowerCase() === 'buy' || side.toLowerCase() === 'long';
            return createToast({
                type: isBuy ? 'success' : 'error',
                title: `${isBuy ? 'Long' : 'Short'} 주문 체결`,
                message: `${symbol} ${qty} @ ${price.toLocaleString()}`,
                duration: 5000
            });
        },

        // 모든 알림 지우기
        clear: clearAll,

        // 제거 함수 (외부에서 직접 호출용)
        remove: removeToast
    };

    // DOM 준비 후 컨테이너 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initContainer);
    } else {
        initContainer();
    }
})();
