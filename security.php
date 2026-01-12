<?php
/**
 * 2FA 보안 설정 페이지
 * Binance/Bybit 스타일 보안 설정
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 로그인 체크
// requireLogin();

$csrf_token = generateCSRFToken();

// 데모 사용자 데이터
$user = [
    'email' => 'user@example.com',
    'phone' => '',
    'google_2fa' => false,
    'sms_2fa' => false,
    'email_2fa' => true,
    'anti_phishing' => '',
    'withdraw_whitelist' => false,
    'last_login' => '2024-01-12 14:30:00',
    'last_login_ip' => '192.168.1.1',
    'kyc_level' => 1
];

$pageTitle = '보안 설정';
$pageCSS = ['security.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 콘텐츠 -->
    <main class="main">
        <div class="security-container">
            <!-- 사이드바 -->
            <aside class="security-sidebar">
                <nav class="security-nav">
                    <a href="#" class="security-nav__item active" data-section="overview">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <span>보안 개요</span>
                    </a>
                    <a href="#" class="security-nav__item" data-section="2fa">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span>2단계 인증</span>
                    </a>
                    <a href="#" class="security-nav__item" data-section="password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                        </svg>
                        <span>비밀번호 변경</span>
                    </a>
                    <a href="#" class="security-nav__item" data-section="devices">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                        <span>기기 관리</span>
                    </a>
                    <a href="#" class="security-nav__item" data-section="activity">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                        <span>활동 내역</span>
                    </a>
                </nav>
            </aside>

            <!-- 콘텐츠 영역 -->
            <div class="security-content">
                <!-- 보안 개요 섹션 -->
                <section class="security-section active" id="overviewSection">
                    <div class="security-header">
                        <h1 class="security-title">보안 설정</h1>
                        <p class="security-desc">계정 보안을 강화하고 자산을 안전하게 보호하세요</p>
                    </div>

                    <!-- 보안 점수 -->
                    <div class="security-score">
                        <div class="security-score__circle">
                            <svg viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="var(--bg-tertiary)" stroke-width="8"/>
                                <circle cx="50" cy="50" r="45" fill="none" stroke="var(--color-warning)" stroke-width="8"
                                    stroke-dasharray="283" stroke-dashoffset="113" stroke-linecap="round"
                                    transform="rotate(-90 50 50)"/>
                            </svg>
                            <div class="security-score__value">
                                <span class="security-score__number">60</span>
                                <span class="security-score__label">보통</span>
                            </div>
                        </div>
                        <div class="security-score__info">
                            <h3>보안 점수</h3>
                            <p>더 안전한 계정을 위해 추가 보안 설정을 완료하세요</p>
                            <ul class="security-score__tips">
                                <li class="completed">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    <span>이메일 인증 완료</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <span>Google OTP 설정</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <span>휴대폰 인증</span>
                                </li>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <span>피싱 방지 코드 설정</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- 보안 항목 목록 -->
                    <div class="security-items">
                        <!-- Google OTP -->
                        <div class="security-item">
                            <div class="security-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                                </svg>
                            </div>
                            <div class="security-item__info">
                                <h4>Google Authenticator</h4>
                                <p>로그인 및 출금 시 OTP 코드를 입력하여 보안을 강화합니다</p>
                            </div>
                            <div class="security-item__status">
                                <span class="security-badge security-badge--inactive">미설정</span>
                            </div>
                            <button class="security-item__action btn btn--primary btn--sm" data-action="setup-google-2fa">
                                설정
                            </button>
                        </div>

                        <!-- SMS 인증 -->
                        <div class="security-item">
                            <div class="security-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <div class="security-item__info">
                                <h4>SMS 인증</h4>
                                <p>휴대폰 번호로 인증 코드를 받아 보안을 강화합니다</p>
                            </div>
                            <div class="security-item__status">
                                <span class="security-badge security-badge--inactive">미설정</span>
                            </div>
                            <button class="security-item__action btn btn--primary btn--sm" data-action="setup-sms">
                                설정
                            </button>
                        </div>

                        <!-- 이메일 인증 -->
                        <div class="security-item">
                            <div class="security-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <div class="security-item__info">
                                <h4>이메일 인증</h4>
                                <p><?php echo e($user['email']); ?></p>
                            </div>
                            <div class="security-item__status">
                                <span class="security-badge security-badge--active">활성화됨</span>
                            </div>
                            <button class="security-item__action btn btn--secondary btn--sm" data-action="change-email">
                                변경
                            </button>
                        </div>

                        <!-- 피싱 방지 코드 -->
                        <div class="security-item">
                            <div class="security-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </div>
                            <div class="security-item__info">
                                <h4>피싱 방지 코드</h4>
                                <p>이메일에 표시되는 고유 코드로 피싱 메일을 구별합니다</p>
                            </div>
                            <div class="security-item__status">
                                <span class="security-badge security-badge--inactive">미설정</span>
                            </div>
                            <button class="security-item__action btn btn--primary btn--sm" data-action="setup-anti-phishing">
                                설정
                            </button>
                        </div>

                        <!-- 출금 주소 화이트리스트 -->
                        <div class="security-item">
                            <div class="security-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                            </div>
                            <div class="security-item__info">
                                <h4>출금 주소 화이트리스트</h4>
                                <p>등록된 주소로만 출금을 허용하여 자산을 보호합니다</p>
                            </div>
                            <div class="security-item__status">
                                <span class="security-badge security-badge--inactive">비활성화</span>
                            </div>
                            <button class="security-item__action btn btn--primary btn--sm" data-action="whitelist">
                                활성화
                            </button>
                        </div>
                    </div>

                    <!-- 최근 로그인 정보 -->
                    <div class="security-recent">
                        <h3>최근 로그인</h3>
                        <div class="security-recent__info">
                            <div class="security-recent__item">
                                <span class="security-recent__label">시간</span>
                                <span class="security-recent__value"><?php echo $user['last_login']; ?></span>
                            </div>
                            <div class="security-recent__item">
                                <span class="security-recent__label">IP 주소</span>
                                <span class="security-recent__value"><?php echo $user['last_login_ip']; ?></span>
                            </div>
                            <a href="#" class="security-recent__link" data-section="activity">전체 활동 내역 보기</a>
                        </div>
                    </div>
                </section>

                <!-- 2FA 설정 섹션 -->
                <section class="security-section" id="twoFASection">
                    <div class="security-header">
                        <h1 class="security-title">2단계 인증</h1>
                        <p class="security-desc">추가 인증 수단으로 계정을 더욱 안전하게 보호하세요</p>
                    </div>

                    <!-- Google OTP 설정 -->
                    <div class="security-2fa-setup" id="googleOTPSetup">
                        <div class="security-2fa-header">
                            <div class="security-2fa-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                                </svg>
                            </div>
                            <div class="security-2fa-info">
                                <h3>Google Authenticator 설정</h3>
                                <p>인증 앱을 사용하여 6자리 코드를 생성합니다</p>
                            </div>
                        </div>

                        <div class="security-2fa-steps">
                            <!-- Step 1: 앱 다운로드 -->
                            <div class="security-2fa-step">
                                <div class="security-2fa-step__number">1</div>
                                <div class="security-2fa-step__content">
                                    <h4>인증 앱 다운로드</h4>
                                    <p>Google Authenticator 또는 Authy 앱을 다운로드하세요</p>
                                    <div class="security-2fa-apps">
                                        <a href="#" class="security-2fa-app">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Google_%22G%22_Logo.svg/24px-Google_%22G%22_Logo.svg.png" alt="Google">
                                            <span>Google Authenticator</span>
                                        </a>
                                        <a href="#" class="security-2fa-app">
                                            <img src="https://www.authy.com/wp-content/uploads/authy-icon-180x180.png" alt="Authy" style="width:24px; height:24px; border-radius:4px;">
                                            <span>Authy</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: QR 코드 스캔 -->
                            <div class="security-2fa-step">
                                <div class="security-2fa-step__number">2</div>
                                <div class="security-2fa-step__content">
                                    <h4>QR 코드 스캔</h4>
                                    <p>앱에서 아래 QR 코드를 스캔하거나 키를 직접 입력하세요</p>
                                    <div class="security-2fa-qr">
                                        <div class="security-2fa-qr__code">
                                            <!-- 데모 QR 코드 플레이스홀더 -->
                                            <div class="security-2fa-qr__placeholder">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="7" height="7"/>
                                                    <rect x="14" y="3" width="7" height="7"/>
                                                    <rect x="14" y="14" width="7" height="7"/>
                                                    <rect x="3" y="14" width="7" height="7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="security-2fa-qr__key">
                                            <span class="security-2fa-qr__label">시크릿 키</span>
                                            <div class="security-2fa-qr__value">
                                                <code>ABCD EFGH IJKL MNOP</code>
                                                <button type="button" class="security-copy-btn" data-copy="ABCDEFGHIJKLMNOP">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <p class="security-2fa-qr__warning">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                                </svg>
                                                이 키를 안전한 곳에 백업하세요
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: 코드 입력 -->
                            <div class="security-2fa-step">
                                <div class="security-2fa-step__number">3</div>
                                <div class="security-2fa-step__content">
                                    <h4>인증 코드 입력</h4>
                                    <p>앱에 표시된 6자리 코드를 입력하세요</p>
                                    <form class="security-2fa-form" id="googleOTPForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <div class="security-code-inputs">
                                            <input type="text" maxlength="1" class="security-code-input" data-index="0" inputmode="numeric">
                                            <input type="text" maxlength="1" class="security-code-input" data-index="1" inputmode="numeric">
                                            <input type="text" maxlength="1" class="security-code-input" data-index="2" inputmode="numeric">
                                            <input type="text" maxlength="1" class="security-code-input" data-index="3" inputmode="numeric">
                                            <input type="text" maxlength="1" class="security-code-input" data-index="4" inputmode="numeric">
                                            <input type="text" maxlength="1" class="security-code-input" data-index="5" inputmode="numeric">
                                        </div>
                                        <button type="submit" class="btn btn--primary btn--full" id="enableOTPBtn" disabled>
                                            Google OTP 활성화
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMS 인증 설정 -->
                    <div class="security-2fa-setup" id="smsSetup" style="display: none;">
                        <div class="security-2fa-header">
                            <div class="security-2fa-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <div class="security-2fa-info">
                                <h3>SMS 인증 설정</h3>
                                <p>휴대폰 번호로 인증 코드를 받습니다</p>
                            </div>
                        </div>

                        <form class="security-sms-form" id="smsForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <div class="security-form-group">
                                <label class="security-label">국가 선택</label>
                                <select class="security-select" id="countryCode">
                                    <option value="+82">🇰🇷 대한민국 (+82)</option>
                                    <option value="+1">🇺🇸 미국 (+1)</option>
                                    <option value="+81">🇯🇵 일본 (+81)</option>
                                    <option value="+86">🇨🇳 중국 (+86)</option>
                                </select>
                            </div>

                            <div class="security-form-group">
                                <label class="security-label">휴대폰 번호</label>
                                <div class="security-phone-input">
                                    <span class="security-phone-prefix" id="phonePrefix">+82</span>
                                    <input type="tel" class="security-input" id="phoneNumber" placeholder="10-1234-5678">
                                    <button type="button" class="btn btn--secondary" id="sendSMSCode">
                                        인증코드 발송
                                    </button>
                                </div>
                            </div>

                            <div class="security-form-group" id="smsCodeGroup" style="display: none;">
                                <label class="security-label">인증 코드</label>
                                <div class="security-code-inputs">
                                    <input type="text" maxlength="1" class="security-code-input" data-index="0" inputmode="numeric">
                                    <input type="text" maxlength="1" class="security-code-input" data-index="1" inputmode="numeric">
                                    <input type="text" maxlength="1" class="security-code-input" data-index="2" inputmode="numeric">
                                    <input type="text" maxlength="1" class="security-code-input" data-index="3" inputmode="numeric">
                                    <input type="text" maxlength="1" class="security-code-input" data-index="4" inputmode="numeric">
                                    <input type="text" maxlength="1" class="security-code-input" data-index="5" inputmode="numeric">
                                </div>
                                <p class="security-timer">코드 유효시간: <span id="smsTimer">05:00</span></p>
                            </div>

                            <button type="submit" class="btn btn--primary btn--full" id="enableSMSBtn" disabled>
                                SMS 인증 활성화
                            </button>
                        </form>
                    </div>
                </section>

                <!-- 비밀번호 변경 섹션 -->
                <section class="security-section" id="passwordSection">
                    <div class="security-header">
                        <h1 class="security-title">비밀번호 변경</h1>
                        <p class="security-desc">정기적으로 비밀번호를 변경하여 계정을 안전하게 보호하세요</p>
                    </div>

                    <form class="security-password-form" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="security-form-group">
                            <label class="security-label">현재 비밀번호</label>
                            <div class="security-input-wrapper">
                                <input type="password" class="security-input" id="currentPassword" placeholder="현재 비밀번호 입력">
                                <button type="button" class="security-password-toggle" data-target="currentPassword">
                                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="security-form-group">
                            <label class="security-label">새 비밀번호</label>
                            <div class="security-input-wrapper">
                                <input type="password" class="security-input" id="newPassword" placeholder="새 비밀번호 입력">
                                <button type="button" class="security-password-toggle" data-target="newPassword">
                                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="security-password-strength">
                                <div class="security-password-strength__bar">
                                    <div class="security-password-strength__fill" id="pwStrengthBar"></div>
                                </div>
                                <span class="security-password-strength__text" id="pwStrengthText">비밀번호 강도</span>
                            </div>
                        </div>

                        <div class="security-form-group">
                            <label class="security-label">새 비밀번호 확인</label>
                            <div class="security-input-wrapper">
                                <input type="password" class="security-input" id="confirmPassword" placeholder="새 비밀번호 재입력">
                                <button type="button" class="security-password-toggle" data-target="confirmPassword">
                                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="security-alert security-alert--info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            <p>비밀번호 변경 후 모든 기기에서 로그아웃됩니다</p>
                        </div>

                        <button type="submit" class="btn btn--primary btn--full">
                            비밀번호 변경
                        </button>
                    </form>
                </section>

                <!-- 기기 관리 섹션 -->
                <section class="security-section" id="devicesSection">
                    <div class="security-header">
                        <h1 class="security-title">기기 관리</h1>
                        <p class="security-desc">계정에 로그인된 기기를 확인하고 관리하세요</p>
                    </div>

                    <div class="security-devices">
                        <!-- 현재 기기 -->
                        <div class="security-device security-device--current">
                            <div class="security-device__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                            </div>
                            <div class="security-device__info">
                                <h4>Windows PC - Chrome</h4>
                                <p>현재 기기 · 서울, 대한민국</p>
                                <span class="security-device__time">마지막 활동: 방금 전</span>
                            </div>
                            <span class="security-badge security-badge--success">현재 세션</span>
                        </div>

                        <!-- 다른 기기들 -->
                        <div class="security-device">
                            <div class="security-device__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                                </svg>
                            </div>
                            <div class="security-device__info">
                                <h4>iPhone 14 Pro - Safari</h4>
                                <p>192.168.1.50 · 서울, 대한민국</p>
                                <span class="security-device__time">마지막 활동: 2시간 전</span>
                            </div>
                            <button class="btn btn--outline btn--sm" data-action="logout-device">로그아웃</button>
                        </div>

                        <div class="security-device">
                            <div class="security-device__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="4" y="4" width="16" height="16" rx="2" ry="2"/>
                                    <rect x="9" y="9" width="6" height="6"/>
                                    <line x1="9" y1="1" x2="9" y2="4"/>
                                    <line x1="15" y1="1" x2="15" y2="4"/>
                                    <line x1="9" y1="20" x2="9" y2="23"/>
                                    <line x1="15" y1="20" x2="15" y2="23"/>
                                    <line x1="20" y1="9" x2="23" y2="9"/>
                                    <line x1="20" y1="14" x2="23" y2="14"/>
                                    <line x1="1" y1="9" x2="4" y2="9"/>
                                    <line x1="1" y1="14" x2="4" y2="14"/>
                                </svg>
                            </div>
                            <div class="security-device__info">
                                <h4>MacBook Pro - Firefox</h4>
                                <p>203.254.12.34 · 부산, 대한민국</p>
                                <span class="security-device__time">마지막 활동: 3일 전</span>
                            </div>
                            <button class="btn btn--outline btn--sm" data-action="logout-device">로그아웃</button>
                        </div>
                    </div>

                    <button class="btn btn--secondary btn--full security-logout-all">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        모든 기기에서 로그아웃
                    </button>
                </section>

                <!-- 활동 내역 섹션 -->
                <section class="security-section" id="activitySection">
                    <div class="security-header">
                        <h1 class="security-title">활동 내역</h1>
                        <p class="security-desc">계정의 최근 활동을 확인하세요</p>
                    </div>

                    <!-- 필터 -->
                    <div class="security-activity-filter">
                        <select class="security-select">
                            <option value="all">모든 활동</option>
                            <option value="login">로그인</option>
                            <option value="security">보안 변경</option>
                            <option value="withdraw">출금</option>
                        </select>
                    </div>

                    <!-- 활동 목록 -->
                    <div class="security-activity-list">
                        <div class="security-activity-item">
                            <div class="security-activity-item__icon security-activity-item__icon--login">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                    <polyline points="10 17 15 12 10 7"/>
                                    <line x1="15" y1="12" x2="3" y2="12"/>
                                </svg>
                            </div>
                            <div class="security-activity-item__info">
                                <h4>로그인 성공</h4>
                                <p>Windows · Chrome · 192.168.1.1</p>
                            </div>
                            <div class="security-activity-item__meta">
                                <span class="security-activity-item__time">방금 전</span>
                                <span class="security-activity-item__location">서울, 대한민국</span>
                            </div>
                        </div>

                        <div class="security-activity-item">
                            <div class="security-activity-item__icon security-activity-item__icon--security">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </div>
                            <div class="security-activity-item__info">
                                <h4>이메일 인증 활성화</h4>
                                <p>user@example.com</p>
                            </div>
                            <div class="security-activity-item__meta">
                                <span class="security-activity-item__time">1시간 전</span>
                                <span class="security-activity-item__location">서울, 대한민국</span>
                            </div>
                        </div>

                        <div class="security-activity-item">
                            <div class="security-activity-item__icon security-activity-item__icon--withdraw">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div class="security-activity-item__info">
                                <h4>출금 요청</h4>
                                <p>0.5 BTC → bc1q...xyz</p>
                            </div>
                            <div class="security-activity-item__meta">
                                <span class="security-activity-item__time">어제</span>
                                <span class="security-activity-item__location">서울, 대한민국</span>
                            </div>
                        </div>

                        <div class="security-activity-item security-activity-item--warning">
                            <div class="security-activity-item__icon security-activity-item__icon--warning">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </div>
                            <div class="security-activity-item__info">
                                <h4>로그인 실패</h4>
                                <p>잘못된 비밀번호 (3회 시도)</p>
                            </div>
                            <div class="security-activity-item__meta">
                                <span class="security-activity-item__time">3일 전</span>
                                <span class="security-activity-item__location">미국, 뉴욕</span>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn--secondary btn--full">더 보기</button>
                </section>
            </div>
        </div>
    </main>

    <!-- 모달: 성공 메시지 -->
    <div class="security-modal" id="successModal" style="display: none;">
        <div class="security-modal__backdrop"></div>
        <div class="security-modal__content">
            <div class="security-modal__icon security-modal__icon--success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <h3 class="security-modal__title">설정 완료</h3>
            <p class="security-modal__desc" id="successMessage">보안 설정이 성공적으로 변경되었습니다.</p>
            <button class="btn btn--primary btn--full" id="closeSuccessModal">확인</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 섹션 네비게이션
        const navItems = document.querySelectorAll('.security-nav__item');
        const sections = document.querySelectorAll('.security-section');

        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.dataset.section + 'Section';

                // Update nav
                navItems.forEach(nav => nav.classList.remove('active'));
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

        // 최근 로그인 링크
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.tagName === 'A') {
                    e.preventDefault();
                    const sectionId = this.dataset.section + 'Section';
                    const navItem = document.querySelector(`.security-nav__item[data-section="${this.dataset.section}"]`);

                    if (navItem) {
                        navItem.click();
                    }
                }
            });
        });

        // 코드 입력 핸들링
        document.querySelectorAll('.security-code-inputs').forEach(container => {
            const inputs = container.querySelectorAll('.security-code-input');

            inputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const value = this.value.replace(/\D/g, '');
                    this.value = value;

                    if (value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }

                    checkCodeComplete(container);
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);

                    pastedData.split('').forEach((char, i) => {
                        if (inputs[i]) {
                            inputs[i].value = char;
                        }
                    });

                    checkCodeComplete(container);
                });
            });
        });

        function checkCodeComplete(container) {
            const inputs = container.querySelectorAll('.security-code-input');
            const code = Array.from(inputs).map(i => i.value).join('');
            const form = container.closest('form');

            if (form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = code.length !== 6;
                }
            }
        }

        // Google OTP 폼
        const googleOTPForm = document.getElementById('googleOTPForm');
        if (googleOTPForm) {
            googleOTPForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const inputs = this.querySelectorAll('.security-code-input');
                const code = Array.from(inputs).map(i => i.value).join('');

                if (code.length !== 6) return;

                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = '처리 중...';

                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 1500));

                showSuccessModal('Google Authenticator가 성공적으로 활성화되었습니다.');

                btn.disabled = false;
                btn.textContent = 'Google OTP 활성화';
            });
        }

        // SMS 인증
        const countryCode = document.getElementById('countryCode');
        const phonePrefix = document.getElementById('phonePrefix');
        const sendSMSCode = document.getElementById('sendSMSCode');
        const smsCodeGroup = document.getElementById('smsCodeGroup');

        if (countryCode) {
            countryCode.addEventListener('change', function() {
                phonePrefix.textContent = this.value;
            });
        }

        if (sendSMSCode) {
            sendSMSCode.addEventListener('click', async function() {
                const phoneNumber = document.getElementById('phoneNumber').value;
                if (!phoneNumber) {
                    alert('휴대폰 번호를 입력해주세요');
                    return;
                }

                this.disabled = true;
                this.textContent = '발송 중...';

                await new Promise(resolve => setTimeout(resolve, 1000));

                smsCodeGroup.style.display = 'block';
                this.textContent = '재발송';
                this.disabled = false;

                startTimer('smsTimer', 300);

                // Focus first code input
                smsCodeGroup.querySelector('.security-code-input').focus();
            });
        }

        function startTimer(elementId, seconds) {
            const timerEl = document.getElementById(elementId);
            let remaining = seconds;

            const interval = setInterval(() => {
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

                if (remaining <= 0) {
                    clearInterval(interval);
                    timerEl.textContent = '만료됨';
                }
                remaining--;
            }, 1000);
        }

        // 비밀번호 토글
        document.querySelectorAll('.security-password-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);

                if (input.type === 'password') {
                    input.type = 'text';
                } else {
                    input.type = 'password';
                }
            });
        });

        // 비밀번호 강도
        const newPassword = document.getElementById('newPassword');
        if (newPassword) {
            newPassword.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
        }

        function checkPasswordStrength(password) {
            const bar = document.getElementById('pwStrengthBar');
            const text = document.getElementById('pwStrengthText');

            const checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*]/.test(password)
            };

            const passedChecks = Object.values(checks).filter(Boolean).length;
            let strength = 0;
            let label = '';
            let color = '';

            if (password.length === 0) {
                label = '비밀번호 강도';
                color = '#2b3139';
            } else if (passedChecks <= 2) {
                strength = 25;
                label = '약함';
                color = '#f6465d';
            } else if (passedChecks === 3) {
                strength = 50;
                label = '보통';
                color = '#f0b90b';
            } else if (passedChecks === 4) {
                strength = 75;
                label = '강함';
                color = '#1e88e5';
            } else {
                strength = 100;
                label = '매우 강함';
                color = '#0ecb81';
            }

            bar.style.width = strength + '%';
            bar.style.backgroundColor = color;
            text.textContent = label;
            text.style.color = color;
        }

        // 복사 버튼
        document.querySelectorAll('.security-copy-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const text = this.dataset.copy;
                await navigator.clipboard.writeText(text);

                const originalHTML = this.innerHTML;
                this.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';

                setTimeout(() => {
                    this.innerHTML = originalHTML;
                }, 2000);
            });
        });

        // 성공 모달
        function showSuccessModal(message) {
            const modal = document.getElementById('successModal');
            const messageEl = document.getElementById('successMessage');
            messageEl.textContent = message;
            modal.style.display = 'flex';
        }

        document.getElementById('closeSuccessModal').addEventListener('click', function() {
            document.getElementById('successModal').style.display = 'none';
        });

        document.querySelector('.security-modal__backdrop').addEventListener('click', function() {
            document.getElementById('successModal').style.display = 'none';
        });
    });
    </script>
</body>
</html>
