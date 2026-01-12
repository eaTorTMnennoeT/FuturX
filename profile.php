<?php
/**
 * FuturX - 프로필 설정 페이지
 * 사용자 프로필 정보 관리
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 로그인 체크
// requireLogin();

$csrf_token = generateCSRFToken();

// 데모 사용자 데이터
$user = [
    'id' => 1,
    'email' => 'user@example.com',
    'nickname' => 'CryptoTrader',
    'phone' => '010-****-5678',
    'created_at' => '2023-06-15',
    'kyc_level' => 1,
    'vip_level' => 0,
    'referral_code' => 'FTX8K2M9',
    'referral_count' => 12,
    'avatar' => null
];

$pageTitle = '프로필';
$pageCSS = ['profile.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 콘텐츠 -->
    <main class="main">
        <div class="profile-container">
            <!-- 사이드바 -->
            <aside class="profile-sidebar">
                <nav class="profile-nav">
                    <a href="profile.php" class="profile-nav__item active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>프로필</span>
                    </a>
                    <a href="security.php" class="profile-nav__item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <span>보안 설정</span>
                    </a>
                    <a href="kyc.php" class="profile-nav__item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <circle cx="9" cy="10" r="2"/>
                            <path d="M15 8h2M15 12h2M7 16h10"/>
                        </svg>
                        <span>KYC 인증</span>
                    </a>
                    <a href="#" class="profile-nav__item" data-section="notification">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span>알림 설정</span>
                    </a>
                    <a href="#" class="profile-nav__item" data-section="api">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                        </svg>
                        <span>API 관리</span>
                    </a>
                    <a href="#" class="profile-nav__item" data-section="referral">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span>추천인</span>
                    </a>
                </nav>
            </aside>

            <!-- 콘텐츠 영역 -->
            <div class="profile-content">
                <!-- 프로필 섹션 -->
                <section class="profile-section active" id="profileSection">
                    <div class="profile-header">
                        <h1 class="profile-title">프로필 설정</h1>
                        <p class="profile-desc">계정 정보를 확인하고 관리하세요</p>
                    </div>

                    <!-- 프로필 카드 -->
                    <div class="profile-card">
                        <div class="profile-card__avatar">
                            <div class="profile-avatar">
                                <?php if ($user['avatar']): ?>
                                    <img src="<?php echo e($user['avatar']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <button class="profile-avatar-edit" title="프로필 사진 변경">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                    <circle cx="12" cy="13" r="4"/>
                                </svg>
                            </button>
                        </div>
                        <div class="profile-card__info">
                            <h2 class="profile-card__name"><?php echo e($user['nickname']); ?></h2>
                            <p class="profile-card__email"><?php echo e($user['email']); ?></p>
                            <div class="profile-card__badges">
                                <span class="profile-badge profile-badge--kyc">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    KYC Level <?php echo $user['kyc_level']; ?>
                                </span>
                                <span class="profile-badge profile-badge--vip">VIP <?php echo $user['vip_level']; ?></span>
                            </div>
                        </div>
                        <div class="profile-card__stats">
                            <div class="profile-stat">
                                <span class="profile-stat__value"><?php echo date('Y.m.d', strtotime($user['created_at'])); ?></span>
                                <span class="profile-stat__label">가입일</span>
                            </div>
                            <div class="profile-stat">
                                <span class="profile-stat__value"><?php echo $user['referral_count']; ?>명</span>
                                <span class="profile-stat__label">추천인</span>
                            </div>
                        </div>
                    </div>

                    <!-- 기본 정보 -->
                    <div class="profile-info-section">
                        <h3 class="profile-section-title">기본 정보</h3>

                        <div class="profile-info-list">
                            <div class="profile-info-item">
                                <div class="profile-info-item__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                                <div class="profile-info-item__content">
                                    <span class="profile-info-item__label">닉네임</span>
                                    <span class="profile-info-item__value"><?php echo e($user['nickname']); ?></span>
                                </div>
                                <button class="btn btn--secondary btn--sm" data-action="edit-nickname">변경</button>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-item__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                </div>
                                <div class="profile-info-item__content">
                                    <span class="profile-info-item__label">이메일</span>
                                    <span class="profile-info-item__value"><?php echo e($user['email']); ?></span>
                                </div>
                                <span class="profile-status profile-status--verified">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    인증됨
                                </span>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-item__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                    </svg>
                                </div>
                                <div class="profile-info-item__content">
                                    <span class="profile-info-item__label">휴대폰</span>
                                    <span class="profile-info-item__value"><?php echo $user['phone'] ?: '미등록'; ?></span>
                                </div>
                                <button class="btn btn--secondary btn--sm" data-action="edit-phone">
                                    <?php echo $user['phone'] ? '변경' : '등록'; ?>
                                </button>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-item__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                                        <circle cx="9" cy="10" r="2"/>
                                        <path d="M15 8h2M15 12h2M7 16h10"/>
                                    </svg>
                                </div>
                                <div class="profile-info-item__content">
                                    <span class="profile-info-item__label">KYC 인증</span>
                                    <span class="profile-info-item__value">Level <?php echo $user['kyc_level']; ?></span>
                                </div>
                                <a href="kyc.php" class="btn btn--primary btn--sm">인증하기</a>
                            </div>
                        </div>
                    </div>

                    <!-- 거래 설정 -->
                    <div class="profile-info-section">
                        <h3 class="profile-section-title">거래 설정</h3>

                        <div class="profile-settings-list">
                            <div class="profile-setting-item">
                                <div class="profile-setting-item__info">
                                    <h4>기본 레버리지</h4>
                                    <p>새 포지션의 기본 레버리지 설정</p>
                                </div>
                                <select class="profile-select" id="defaultLeverage">
                                    <option value="5">5x</option>
                                    <option value="10" selected>10x</option>
                                    <option value="20">20x</option>
                                    <option value="50">50x</option>
                                    <option value="100">100x</option>
                                </select>
                            </div>

                            <div class="profile-setting-item">
                                <div class="profile-setting-item__info">
                                    <h4>주문 확인</h4>
                                    <p>주문 전 확인 팝업 표시</p>
                                </div>
                                <label class="profile-toggle">
                                    <input type="checkbox" checked>
                                    <span class="profile-toggle__slider"></span>
                                </label>
                            </div>

                            <div class="profile-setting-item">
                                <div class="profile-setting-item__info">
                                    <h4>사운드 알림</h4>
                                    <p>체결 및 청산 시 사운드 알림</p>
                                </div>
                                <label class="profile-toggle">
                                    <input type="checkbox">
                                    <span class="profile-toggle__slider"></span>
                                </label>
                            </div>

                            <div class="profile-setting-item">
                                <div class="profile-setting-item__info">
                                    <h4>다크 모드</h4>
                                    <p>어두운 테마 사용</p>
                                </div>
                                <label class="profile-toggle">
                                    <input type="checkbox" checked>
                                    <span class="profile-toggle__slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 알림 설정 섹션 -->
                <section class="profile-section" id="notificationSection">
                    <div class="profile-header">
                        <h1 class="profile-title">알림 설정</h1>
                        <p class="profile-desc">알림 수신 방법을 설정하세요</p>
                    </div>

                    <div class="profile-notification-groups">
                        <div class="profile-notification-group">
                            <h3 class="profile-notification-group__title">거래 알림</h3>

                            <div class="profile-notification-item">
                                <div class="profile-notification-item__info">
                                    <h4>주문 체결</h4>
                                    <p>주문이 체결되었을 때 알림</p>
                                </div>
                                <div class="profile-notification-item__options">
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>이메일</span>
                                    </label>
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>푸시</span>
                                    </label>
                                </div>
                            </div>

                            <div class="profile-notification-item">
                                <div class="profile-notification-item__info">
                                    <h4>청산 경고</h4>
                                    <p>청산 가격 접근 시 알림</p>
                                </div>
                                <div class="profile-notification-item__options">
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>이메일</span>
                                    </label>
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>푸시</span>
                                    </label>
                                </div>
                            </div>

                            <div class="profile-notification-item">
                                <div class="profile-notification-item__info">
                                    <h4>가격 알림</h4>
                                    <p>설정한 가격 도달 시 알림</p>
                                </div>
                                <div class="profile-notification-item__options">
                                    <label class="profile-checkbox">
                                        <input type="checkbox">
                                        <span>이메일</span>
                                    </label>
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>푸시</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="profile-notification-group">
                            <h3 class="profile-notification-group__title">계정 알림</h3>

                            <div class="profile-notification-item">
                                <div class="profile-notification-item__info">
                                    <h4>로그인 알림</h4>
                                    <p>새로운 기기에서 로그인 시 알림</p>
                                </div>
                                <div class="profile-notification-item__options">
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>이메일</span>
                                    </label>
                                    <label class="profile-checkbox">
                                        <input type="checkbox">
                                        <span>푸시</span>
                                    </label>
                                </div>
                            </div>

                            <div class="profile-notification-item">
                                <div class="profile-notification-item__info">
                                    <h4>입출금 알림</h4>
                                    <p>입금/출금 완료 시 알림</p>
                                </div>
                                <div class="profile-notification-item__options">
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>이메일</span>
                                    </label>
                                    <label class="profile-checkbox">
                                        <input type="checkbox" checked>
                                        <span>푸시</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="profile-notification-group">
                            <h3 class="profile-notification-group__title">마케팅</h3>

                            <div class="profile-notification-item">
                                <div class="profile-notification-item__info">
                                    <h4>프로모션 알림</h4>
                                    <p>이벤트 및 프로모션 정보</p>
                                </div>
                                <div class="profile-notification-item__options">
                                    <label class="profile-checkbox">
                                        <input type="checkbox">
                                        <span>이메일</span>
                                    </label>
                                    <label class="profile-checkbox">
                                        <input type="checkbox">
                                        <span>푸시</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn--primary">설정 저장</button>
                </section>

                <!-- API 관리 섹션 -->
                <section class="profile-section" id="apiSection">
                    <div class="profile-header">
                        <h1 class="profile-title">API 관리</h1>
                        <p class="profile-desc">API 키를 생성하고 관리하세요</p>
                    </div>

                    <div class="profile-api-notice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <p>API 키는 프로그래밍 방식으로 거래를 수행할 때 사용됩니다. 시크릿 키는 절대 공유하지 마세요.</p>
                    </div>

                    <button class="btn btn--primary" id="createApiKey">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        새 API 키 생성
                    </button>

                    <div class="profile-api-list">
                        <div class="profile-api-item">
                            <div class="profile-api-item__info">
                                <h4>Trading Bot Key</h4>
                                <p class="profile-api-item__key">FTX-API-***************ABC</p>
                                <div class="profile-api-item__permissions">
                                    <span class="profile-api-permission">읽기</span>
                                    <span class="profile-api-permission">거래</span>
                                </div>
                            </div>
                            <div class="profile-api-item__meta">
                                <span class="profile-api-item__date">생성: 2024-01-10</span>
                                <span class="profile-api-item__status">활성</span>
                            </div>
                            <div class="profile-api-item__actions">
                                <button class="btn btn--secondary btn--sm">수정</button>
                                <button class="btn btn--outline btn--sm btn--danger">삭제</button>
                            </div>
                        </div>

                        <div class="profile-api-empty" style="display: none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                            </svg>
                            <p>생성된 API 키가 없습니다</p>
                        </div>
                    </div>
                </section>

                <!-- 추천인 섹션 -->
                <section class="profile-section" id="referralSection">
                    <div class="profile-header">
                        <h1 class="profile-title">추천인 프로그램</h1>
                        <p class="profile-desc">친구를 초대하고 보상을 받으세요</p>
                    </div>

                    <div class="profile-referral-card">
                        <div class="profile-referral-card__header">
                            <h3>내 추천 코드</h3>
                        </div>
                        <div class="profile-referral-card__body">
                            <div class="profile-referral-code">
                                <code><?php echo e($user['referral_code']); ?></code>
                                <button class="profile-copy-btn" data-copy="<?php echo e($user['referral_code']); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                    복사
                                </button>
                            </div>
                            <div class="profile-referral-link">
                                <input type="text" readonly value="https://futurx.com/r/<?php echo e($user['referral_code']); ?>">
                                <button class="profile-copy-btn" data-copy="https://futurx.com/r/<?php echo e($user['referral_code']); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                    복사
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="profile-referral-stats">
                        <div class="profile-referral-stat">
                            <span class="profile-referral-stat__value"><?php echo $user['referral_count']; ?></span>
                            <span class="profile-referral-stat__label">총 추천인</span>
                        </div>
                        <div class="profile-referral-stat">
                            <span class="profile-referral-stat__value">$125.50</span>
                            <span class="profile-referral-stat__label">총 수익</span>
                        </div>
                        <div class="profile-referral-stat">
                            <span class="profile-referral-stat__value">20%</span>
                            <span class="profile-referral-stat__label">커미션 비율</span>
                        </div>
                    </div>

                    <div class="profile-referral-rules">
                        <h3>추천 보상 안내</h3>
                        <ul>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                추천받은 친구의 거래 수수료 20% 적립
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                추천받은 친구도 수수료 10% 할인 혜택
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                월 100명 이상 추천 시 VIP 커미션 적용
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- 닉네임 변경 모달 -->
    <div class="profile-modal" id="nicknameModal" style="display: none;">
        <div class="profile-modal__backdrop"></div>
        <div class="profile-modal__content">
            <div class="profile-modal__header">
                <h3>닉네임 변경</h3>
                <button class="profile-modal__close" id="closeNicknameModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="profile-modal__body">
                <div class="profile-form-group">
                    <label class="profile-form-label">새 닉네임</label>
                    <input type="text" class="profile-input" id="newNickname" placeholder="2-20자 영문, 숫자, 한글" maxlength="20">
                    <p class="profile-form-hint">닉네임은 30일에 한 번 변경할 수 있습니다</p>
                </div>
            </div>
            <div class="profile-modal__footer">
                <button class="btn btn--secondary" onclick="document.getElementById('nicknameModal').style.display='none'">취소</button>
                <button class="btn btn--primary" id="saveNickname">저장</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 섹션 네비게이션
        const navItems = document.querySelectorAll('.profile-nav__item[data-section]');
        const sections = document.querySelectorAll('.profile-section');

        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.dataset.section + 'Section';

                // Update nav - remove active from all nav items
                document.querySelectorAll('.profile-nav__item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');

                // Update sections
                sections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === sectionId) {
                        section.classList.add('active');
                    }
                });
            });
        });

        // 닉네임 변경 모달
        const nicknameModal = document.getElementById('nicknameModal');
        const editNicknameBtn = document.querySelector('[data-action="edit-nickname"]');

        if (editNicknameBtn) {
            editNicknameBtn.addEventListener('click', () => {
                nicknameModal.style.display = 'flex';
            });
        }

        document.getElementById('closeNicknameModal')?.addEventListener('click', () => {
            nicknameModal.style.display = 'none';
        });

        nicknameModal.querySelector('.profile-modal__backdrop')?.addEventListener('click', () => {
            nicknameModal.style.display = 'none';
        });

        document.getElementById('saveNickname')?.addEventListener('click', async function() {
            const newNickname = document.getElementById('newNickname').value;
            if (!newNickname || newNickname.length < 2) {
                alert('닉네임은 2자 이상이어야 합니다');
                return;
            }

            this.disabled = true;
            this.textContent = '저장 중...';

            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));

            // Update displayed nickname
            document.querySelector('.profile-card__name').textContent = newNickname;
            document.querySelector('[data-action="edit-nickname"]').closest('.profile-info-item')
                .querySelector('.profile-info-item__value').textContent = newNickname;

            nicknameModal.style.display = 'none';
            this.disabled = false;
            this.textContent = '저장';

            // Show toast (if available)
            if (typeof Toast !== 'undefined') {
                Toast.success('닉네임이 변경되었습니다');
            }
        });

        // 복사 버튼
        document.querySelectorAll('.profile-copy-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const text = this.dataset.copy;
                await navigator.clipboard.writeText(text);

                const originalHTML = this.innerHTML;
                this.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> 복사됨';

                setTimeout(() => {
                    this.innerHTML = originalHTML;
                }, 2000);
            });
        });

        // 토글 스위치 변경
        document.querySelectorAll('.profile-toggle input').forEach(toggle => {
            toggle.addEventListener('change', function() {
                // 자동 저장 로직 추가 가능
                console.log('Setting changed:', this.checked);
            });
        });
    });
    </script>

<?php require_once 'includes/footer.php'; ?>
