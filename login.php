<?php
/**
 * 로그인 페이지
 * Binance/Bybit 스타일 로그인 + 2FA
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 이미 로그인된 경우 메인으로 리다이렉트
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$csrf_token = generateCSRFToken();

$pageTitle = '로그인';
$pageCSS = ['login.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 콘텐츠 -->
    <main class="main">
        <div class="login-container">
            <!-- 왼쪽: 브랜딩 영역 -->
            <div class="login-brand-section">
                <div class="login-brand">
                    <h1 class="login-brand__title">
                        거래의 미래,<br>
                        <span class="login-brand__highlight">CryptoFutures</span>
                    </h1>
                    <p class="login-brand__desc">
                        업계 최고의 유동성과 보안으로<br>
                        신뢰할 수 있는 선물 거래를 경험하세요
                    </p>

                    <!-- 실시간 통계 -->
                    <div class="login-brand__stats">
                        <div class="login-brand__stat">
                            <span class="login-brand__stat-value" id="statVolume">$0</span>
                            <span class="login-brand__stat-label">24시간 거래량</span>
                        </div>
                        <div class="login-brand__stat">
                            <span class="login-brand__stat-value" id="statUsers">0</span>
                            <span class="login-brand__stat-label">활성 트레이더</span>
                        </div>
                    </div>

                    <!-- 마켓 티커 -->
                    <div class="login-brand__ticker">
                        <div class="login-ticker">
                            <div class="login-ticker__item">
                                <span class="login-ticker__symbol">BTC/USDT</span>
                                <span class="login-ticker__price">$43,256.78</span>
                                <span class="login-ticker__change num-positive">+2.34%</span>
                            </div>
                            <div class="login-ticker__item">
                                <span class="login-ticker__symbol">ETH/USDT</span>
                                <span class="login-ticker__price">$2,567.12</span>
                                <span class="login-ticker__change num-positive">+1.87%</span>
                            </div>
                            <div class="login-ticker__item">
                                <span class="login-ticker__symbol">SOL/USDT</span>
                                <span class="login-ticker__price">$98.45</span>
                                <span class="login-ticker__change num-negative">-0.56%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 오른쪽: 폼 영역 -->
            <div class="login-form-section">
                <div class="login-form-wrapper">
                    <!-- 로그인 폼 -->
                    <div id="loginStep" class="login-step active">
                        <h2 class="login-title">로그인</h2>
                        <p class="login-subtitle">계정에 로그인하세요</p>

                        <!-- 소셜 로그인 -->
                        <div class="login-social">
                            <button type="button" class="login-social-btn">
                                <svg viewBox="0 0 24 24" class="login-social-btn__icon">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                            </button>
                            <button type="button" class="login-social-btn">
                                <svg viewBox="0 0 24 24" class="login-social-btn__icon">
                                    <path fill="currentColor" d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                                </svg>
                            </button>
                        </div>

                        <div class="login-divider">
                            <span>또는</span>
                        </div>

                        <form id="loginForm" class="login-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <!-- 이메일 -->
                            <div class="login-form-group">
                                <label for="email" class="login-label">이메일</label>
                                <div class="login-input-wrapper">
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="login-input"
                                        placeholder="이메일 주소 입력"
                                        autocomplete="email"
                                        required
                                    >
                                    <svg class="login-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                </div>
                                <span class="login-error" id="emailError"></span>
                            </div>

                            <!-- 비밀번호 -->
                            <div class="login-form-group">
                                <div class="login-label-row">
                                    <label for="password" class="login-label">비밀번호</label>
                                    <a href="forgot-password.php" class="login-forgot-link">비밀번호 찾기</a>
                                </div>
                                <div class="login-input-wrapper">
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="login-input"
                                        placeholder="비밀번호 입력"
                                        autocomplete="current-password"
                                        required
                                    >
                                    <button type="button" class="login-password-toggle" id="passwordToggle">
                                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                            <line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </button>
                                </div>
                                <span class="login-error" id="passwordError"></span>
                            </div>

                            <!-- 로그인 유지 -->
                            <div class="login-remember">
                                <label class="login-checkbox">
                                    <input type="checkbox" id="remember" name="remember">
                                    <span class="login-checkbox__mark"></span>
                                    <span class="login-checkbox__text">로그인 상태 유지</span>
                                </label>
                            </div>

                            <!-- 로그인 버튼 -->
                            <button type="submit" class="login-submit" id="loginBtn">
                                <span class="login-submit__text">로그인</span>
                                <div class="login-submit__loading">
                                    <div class="spinner"></div>
                                </div>
                            </button>
                        </form>

                        <!-- 회원가입 링크 -->
                        <p class="login-register-link">
                            계정이 없으신가요?
                            <a href="register.php">회원가입</a>
                        </p>
                    </div>

                    <!-- 2FA 인증 단계 -->
                    <div id="twoFAStep" class="login-step">
                        <button type="button" class="login-back-btn" id="backToLogin">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="19" y1="12" x2="5" y2="12"/>
                                <polyline points="12 19 5 12 12 5"/>
                            </svg>
                            <span>뒤로</span>
                        </button>

                        <div class="login-2fa-header">
                            <div class="login-2fa-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                            <h2 class="login-title">2단계 인증</h2>
                            <p class="login-subtitle">보안을 위해 인증 코드를 입력해주세요</p>
                        </div>

                        <!-- 2FA 타입 탭 -->
                        <div class="login-2fa-tabs">
                            <button type="button" class="login-2fa-tab active" data-type="authenticator">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                                </svg>
                                <span>인증 앱</span>
                            </button>
                            <button type="button" class="login-2fa-tab" data-type="sms">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                <span>SMS</span>
                            </button>
                            <button type="button" class="login-2fa-tab" data-type="email">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <span>이메일</span>
                            </button>
                        </div>

                        <form id="twoFAForm" class="login-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="twofa_type" id="twofaType" value="authenticator">

                            <!-- Authenticator 코드 입력 -->
                            <div class="login-2fa-content" id="authenticatorContent">
                                <p class="login-2fa-desc">
                                    Google Authenticator 또는 인증 앱에서<br>
                                    6자리 코드를 입력해주세요
                                </p>
                                <div class="login-code-inputs" id="authCodeInputs">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="0" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="1" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="2" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="3" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="4" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="5" inputmode="numeric" pattern="[0-9]*">
                                </div>
                            </div>

                            <!-- SMS 인증 -->
                            <div class="login-2fa-content" id="smsContent" style="display: none;">
                                <p class="login-2fa-desc">
                                    <span id="maskedPhone">***-****-1234</span>로<br>
                                    전송된 6자리 코드를 입력해주세요
                                </p>
                                <div class="login-code-inputs" id="smsCodeInputs">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="0" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="1" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="2" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="3" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="4" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="5" inputmode="numeric" pattern="[0-9]*">
                                </div>
                                <button type="button" class="login-resend-btn" id="resendSMS">
                                    코드 재전송 <span id="smsTimer"></span>
                                </button>
                            </div>

                            <!-- 이메일 인증 -->
                            <div class="login-2fa-content" id="emailContent" style="display: none;">
                                <p class="login-2fa-desc">
                                    <span id="maskedEmail">t***@example.com</span>으로<br>
                                    전송된 6자리 코드를 입력해주세요
                                </p>
                                <div class="login-code-inputs" id="emailCodeInputs">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="0" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="1" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="2" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="3" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="4" inputmode="numeric" pattern="[0-9]*">
                                    <input type="text" maxlength="1" class="login-code-input" data-index="5" inputmode="numeric" pattern="[0-9]*">
                                </div>
                                <button type="button" class="login-resend-btn" id="resendEmail">
                                    코드 재전송 <span id="emailTimer"></span>
                                </button>
                            </div>

                            <span class="login-error login-error--center" id="twofaError"></span>

                            <button type="submit" class="login-submit" id="verifyBtn" disabled>
                                <span class="login-submit__text">확인</span>
                                <div class="login-submit__loading">
                                    <div class="spinner"></div>
                                </div>
                            </button>
                        </form>

                        <!-- 복구 코드 링크 -->
                        <p class="login-recovery-link">
                            인증 장치에 접근할 수 없나요?
                            <a href="recovery.php">복구 코드 사용</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 보안 알림 토스트 -->
    <div class="login-toast" id="securityToast" style="display: none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        <span>보안 연결이 확인되었습니다</span>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const loginStep = document.getElementById('loginStep');
        const twoFAStep = document.getElementById('twoFAStep');
        const loginForm = document.getElementById('loginForm');
        const twoFAForm = document.getElementById('twoFAForm');
        const loginBtn = document.getElementById('loginBtn');
        const verifyBtn = document.getElementById('verifyBtn');
        const backToLogin = document.getElementById('backToLogin');
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const emailInput = document.getElementById('email');

        // 2FA tabs
        const twofaTabs = document.querySelectorAll('.login-2fa-tab');
        const twofaContents = {
            authenticator: document.getElementById('authenticatorContent'),
            sms: document.getElementById('smsContent'),
            email: document.getElementById('emailContent')
        };

        // Show security toast
        setTimeout(() => {
            const toast = document.getElementById('securityToast');
            toast.style.display = 'flex';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }, 1000);

        // Animate stats
        animateValue('statVolume', 0, 52.4, '$', 'B');
        animateValue('statUsers', 0, 1.2, '', 'M');

        function animateValue(elementId, start, end, prefix, suffix) {
            const el = document.getElementById(elementId);
            const duration = 2000;
            const startTime = performance.now();

            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                const current = start + (end - start) * easeProgress;

                el.textContent = prefix + current.toFixed(1) + suffix;

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }

            requestAnimationFrame(update);
        }

        // Password toggle
        passwordToggle.addEventListener('click', function() {
            const iconEye = this.querySelector('.icon-eye');
            const iconEyeOff = this.querySelector('.icon-eye-off');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                iconEye.style.display = 'none';
                iconEyeOff.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                iconEye.style.display = 'block';
                iconEyeOff.style.display = 'none';
            }
        });

        // Login form submission
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = emailInput.value.trim();
            const password = passwordInput.value;
            const remember = document.getElementById('remember').checked;

            // Validation
            if (!email) {
                showError('emailError', '이메일을 입력해주세요');
                return;
            }
            hideError('emailError');

            if (!password) {
                showError('passwordError', '비밀번호를 입력해주세요');
                return;
            }
            hideError('passwordError');

            // Show loading
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password, remember })
                });

                const result = await response.json();

                if (result.success) {
                    // 2FA가 필요한 경우
                    if (result.data.requires_2fa) {
                        showTwoFAStep();
                    } else {
                        // 로그인 성공 - auth_token을 localStorage에 저장
                        if (result.data.token) {
                            localStorage.setItem('auth_token', result.data.token);
                        }

                        // Toast 표시 후 리다이렉트
                        if (typeof Toast !== 'undefined') {
                            Toast.success('로그인 성공', '환영합니다!');
                        }
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 500);
                    }
                } else {
                    showError('passwordError', result.error || '로그인에 실패했습니다');
                    if (typeof Toast !== 'undefined') {
                        Toast.error('로그인 실패', result.error || '이메일 또는 비밀번호를 확인해주세요');
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('passwordError', '서버 연결에 실패했습니다');
                if (typeof Toast !== 'undefined') {
                    Toast.error('연결 오류', '서버에 연결할 수 없습니다');
                }
            } finally {
                loginBtn.classList.remove('loading');
                loginBtn.disabled = false;
            }
        });

        // Show 2FA step
        function showTwoFAStep() {
            loginStep.classList.remove('active');
            twoFAStep.classList.add('active');

            // Focus first code input
            const firstInput = document.querySelector('#authCodeInputs .login-code-input');
            if (firstInput) firstInput.focus();
        }

        // Back to login
        backToLogin.addEventListener('click', function() {
            twoFAStep.classList.remove('active');
            loginStep.classList.add('active');
            clearCodeInputs();
        });

        // 2FA tabs
        twofaTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const type = this.dataset.type;

                // Update active tab
                twofaTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Show corresponding content
                Object.keys(twofaContents).forEach(key => {
                    twofaContents[key].style.display = key === type ? 'block' : 'none';
                });

                // Update hidden input
                document.getElementById('twofaType').value = type;

                // Clear inputs and focus first
                clearCodeInputs();
                const firstInput = document.querySelector(`#${type}CodeInputs .login-code-input, #authCodeInputs .login-code-input`);
                if (firstInput) firstInput.focus();

                // Send code for SMS/Email
                if (type === 'sms') {
                    sendSMSCode();
                } else if (type === 'email') {
                    sendEmailCode();
                }
            });
        });

        // Code input handling
        document.querySelectorAll('.login-code-inputs').forEach(container => {
            const inputs = container.querySelectorAll('.login-code-input');

            inputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const value = this.value.replace(/\D/g, '');
                    this.value = value;

                    if (value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }

                    checkCodeComplete();
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

                    const lastFilledIndex = Math.min(pastedData.length - 1, inputs.length - 1);
                    inputs[lastFilledIndex].focus();

                    checkCodeComplete();
                });
            });
        });

        function checkCodeComplete() {
            const activeContainer = document.querySelector('.login-2fa-content:not([style*="display: none"]) .login-code-inputs');
            if (!activeContainer) return;

            const inputs = activeContainer.querySelectorAll('.login-code-input');
            const code = Array.from(inputs).map(i => i.value).join('');

            verifyBtn.disabled = code.length !== 6;
        }

        function clearCodeInputs() {
            document.querySelectorAll('.login-code-input').forEach(input => {
                input.value = '';
            });
            verifyBtn.disabled = true;
            hideError('twofaError');
        }

        // 2FA form submission
        twoFAForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const activeContainer = document.querySelector('.login-2fa-content:not([style*="display: none"]) .login-code-inputs');
            const inputs = activeContainer.querySelectorAll('.login-code-input');
            const code = Array.from(inputs).map(i => i.value).join('');

            if (code.length !== 6) return;

            verifyBtn.classList.add('loading');
            verifyBtn.disabled = true;

            try {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 1000));

                // For demo: verify code
                if (code === '123456') {
                    window.location.href = 'index.php';
                } else {
                    showError('twofaError', '인증 코드가 올바르지 않습니다');
                    clearCodeInputs();
                    document.querySelector('.login-2fa-content:not([style*="display: none"]) .login-code-input')?.focus();
                }
            } catch (error) {
                showError('twofaError', '인증에 실패했습니다. 다시 시도해주세요.');
            } finally {
                verifyBtn.classList.remove('loading');
                checkCodeComplete();
            }
        });

        // Resend SMS
        let smsTimer = 0;
        const resendSMS = document.getElementById('resendSMS');

        function sendSMSCode() {
            if (smsTimer > 0) return;

            smsTimer = 60;
            resendSMS.disabled = true;
            updateSMSTimer();

            // Simulate sending SMS
            console.log('SMS code sent');
        }

        function updateSMSTimer() {
            const timerEl = document.getElementById('smsTimer');
            if (smsTimer > 0) {
                timerEl.textContent = `(${smsTimer}s)`;
                smsTimer--;
                setTimeout(updateSMSTimer, 1000);
            } else {
                timerEl.textContent = '';
                resendSMS.disabled = false;
            }
        }

        resendSMS.addEventListener('click', sendSMSCode);

        // Resend Email
        let emailTimer = 0;
        const resendEmail = document.getElementById('resendEmail');

        function sendEmailCode() {
            if (emailTimer > 0) return;

            emailTimer = 60;
            resendEmail.disabled = true;
            updateEmailTimer();

            // Simulate sending email
            console.log('Email code sent');
        }

        function updateEmailTimer() {
            const timerEl = document.getElementById('emailTimer');
            if (emailTimer > 0) {
                timerEl.textContent = `(${emailTimer}s)`;
                emailTimer--;
                setTimeout(updateEmailTimer, 1000);
            } else {
                timerEl.textContent = '';
                resendEmail.disabled = false;
            }
        }

        resendEmail.addEventListener('click', sendEmailCode);

        // Error helpers
        function showError(elementId, message) {
            const el = document.getElementById(elementId);
            el.textContent = message;
            el.style.display = 'block';
        }

        function hideError(elementId) {
            const el = document.getElementById(elementId);
            el.style.display = 'none';
        }
    });
    </script>
</body>
</html>
