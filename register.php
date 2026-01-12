<?php
/**
 * 회원가입 페이지
 * Binance/Bybit 스타일 회원가입
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 이미 로그인된 경우 메인으로 리다이렉트
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$csrf_token = generateCSRFToken();

// 레퍼럴 코드 (GET 파라미터)
$referral_code = isset($_GET['ref']) ? e($_GET['ref']) : '';

$pageTitle = '회원가입';
$pageCSS = ['register.css'];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

    <!-- 메인 콘텐츠 -->
    <main class="main">
        <div class="register-container">
            <!-- 왼쪽: 폼 영역 -->
            <div class="register-form-section">
                <div class="register-form-wrapper">
                    <h1 class="register-title">계정 만들기</h1>
                    <p class="register-subtitle">CryptoFutures에 오신 것을 환영합니다</p>

                    <!-- 소셜 로그인 -->
                    <div class="register-social">
                        <button type="button" class="register-social-btn register-social-btn--google">
                            <svg viewBox="0 0 24 24" class="register-social-btn__icon">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span>Google로 계속하기</span>
                        </button>
                        <button type="button" class="register-social-btn register-social-btn--apple">
                            <svg viewBox="0 0 24 24" class="register-social-btn__icon">
                                <path fill="currentColor" d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                            </svg>
                            <span>Apple로 계속하기</span>
                        </button>
                    </div>

                    <div class="register-divider">
                        <span>또는 이메일로 가입</span>
                    </div>

                    <!-- 회원가입 폼 -->
                    <form id="registerForm" class="register-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- 이메일 -->
                        <div class="register-form-group">
                            <label for="email" class="register-label">이메일 주소</label>
                            <div class="register-input-wrapper">
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="register-input"
                                    placeholder="example@email.com"
                                    autocomplete="email"
                                    required
                                >
                                <svg class="register-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <span class="register-error" id="emailError"></span>
                        </div>

                        <!-- 비밀번호 -->
                        <div class="register-form-group">
                            <label for="password" class="register-label">비밀번호</label>
                            <div class="register-input-wrapper">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="register-input"
                                    placeholder="8자 이상 입력"
                                    autocomplete="new-password"
                                    required
                                >
                                <button type="button" class="register-password-toggle" data-target="password">
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

                            <!-- 비밀번호 강도 표시 -->
                            <div class="register-password-strength">
                                <div class="register-password-strength__bar">
                                    <div class="register-password-strength__fill" id="passwordStrengthBar"></div>
                                </div>
                                <span class="register-password-strength__text" id="passwordStrengthText">비밀번호 강도</span>
                            </div>

                            <!-- 비밀번호 요구사항 -->
                            <ul class="register-password-requirements">
                                <li id="reqLength">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <span>최소 8자 이상</span>
                                </li>
                                <li id="reqUppercase">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <span>대문자 포함</span>
                                </li>
                                <li id="reqLowercase">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <span>소문자 포함</span>
                                </li>
                                <li id="reqNumber">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <span>숫자 포함</span>
                                </li>
                                <li id="reqSpecial">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <span>특수문자 포함</span>
                                </li>
                            </ul>
                            <span class="register-error" id="passwordError"></span>
                        </div>

                        <!-- 비밀번호 확인 -->
                        <div class="register-form-group">
                            <label for="passwordConfirm" class="register-label">비밀번호 확인</label>
                            <div class="register-input-wrapper">
                                <input
                                    type="password"
                                    id="passwordConfirm"
                                    name="password_confirm"
                                    class="register-input"
                                    placeholder="비밀번호 재입력"
                                    autocomplete="new-password"
                                    required
                                >
                                <button type="button" class="register-password-toggle" data-target="passwordConfirm">
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
                            <span class="register-error" id="passwordConfirmError"></span>
                        </div>

                        <!-- 레퍼럴 코드 (선택) -->
                        <div class="register-form-group register-form-group--referral">
                            <div class="register-referral-toggle">
                                <span>레퍼럴 코드가 있으신가요?</span>
                                <button type="button" id="toggleReferral" class="register-referral-toggle__btn">
                                    입력하기
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="register-referral-input" id="referralInputWrapper" style="display: none;">
                                <input
                                    type="text"
                                    id="referralCode"
                                    name="referral_code"
                                    class="register-input"
                                    placeholder="레퍼럴 코드 입력"
                                    value="<?php echo $referral_code; ?>"
                                >
                                <p class="register-referral-hint">레퍼럴 코드를 입력하면 가입 보너스를 받을 수 있습니다.</p>
                            </div>
                        </div>

                        <!-- 약관 동의 -->
                        <div class="register-agreements">
                            <label class="register-checkbox">
                                <input type="checkbox" id="agreeAll" name="agree_all">
                                <span class="register-checkbox__mark"></span>
                                <span class="register-checkbox__text register-checkbox__text--bold">전체 동의</span>
                            </label>

                            <div class="register-agreements__list">
                                <label class="register-checkbox">
                                    <input type="checkbox" id="agreeTerms" name="agree_terms" required>
                                    <span class="register-checkbox__mark"></span>
                                    <span class="register-checkbox__text">
                                        <span class="register-checkbox__required">[필수]</span>
                                        서비스 이용약관 동의
                                    </span>
                                    <a href="terms.php" target="_blank" class="register-checkbox__link">보기</a>
                                </label>

                                <label class="register-checkbox">
                                    <input type="checkbox" id="agreePrivacy" name="agree_privacy" required>
                                    <span class="register-checkbox__mark"></span>
                                    <span class="register-checkbox__text">
                                        <span class="register-checkbox__required">[필수]</span>
                                        개인정보 처리방침 동의
                                    </span>
                                    <a href="privacy.php" target="_blank" class="register-checkbox__link">보기</a>
                                </label>

                                <label class="register-checkbox">
                                    <input type="checkbox" id="agreeRisk" name="agree_risk" required>
                                    <span class="register-checkbox__mark"></span>
                                    <span class="register-checkbox__text">
                                        <span class="register-checkbox__required">[필수]</span>
                                        투자 위험 고지 확인
                                    </span>
                                    <a href="risk.php" target="_blank" class="register-checkbox__link">보기</a>
                                </label>

                                <label class="register-checkbox">
                                    <input type="checkbox" id="agreeMarketing" name="agree_marketing">
                                    <span class="register-checkbox__mark"></span>
                                    <span class="register-checkbox__text">
                                        <span class="register-checkbox__optional">[선택]</span>
                                        마케팅 정보 수신 동의
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- 가입 버튼 -->
                        <button type="submit" class="register-submit" id="submitBtn" disabled>
                            <span class="register-submit__text">계정 만들기</span>
                            <div class="register-submit__loading">
                                <div class="spinner"></div>
                            </div>
                        </button>
                    </form>

                    <!-- 로그인 링크 -->
                    <p class="register-login-link">
                        이미 계정이 있으신가요?
                        <a href="login.php">로그인</a>
                    </p>
                </div>
            </div>

            <!-- 오른쪽: 프로모션 영역 -->
            <div class="register-promo-section">
                <div class="register-promo">
                    <div class="register-promo__badge">신규 가입 혜택</div>
                    <h2 class="register-promo__title">최대 $100 보너스</h2>
                    <p class="register-promo__desc">지금 가입하고 첫 입금 시 최대 $100 USDT 보너스를 받으세요</p>

                    <ul class="register-promo__features">
                        <li>
                            <div class="register-promo__feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </div>
                            <div class="register-promo__feature-text">
                                <strong>보안 최우선</strong>
                                <span>업계 최고 수준의 보안 시스템</span>
                            </div>
                        </li>
                        <li>
                            <div class="register-promo__feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div class="register-promo__feature-text">
                                <strong>낮은 수수료</strong>
                                <span>업계 최저 0.01% 거래 수수료</span>
                            </div>
                        </li>
                        <li>
                            <div class="register-promo__feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                </svg>
                            </div>
                            <div class="register-promo__feature-text">
                                <strong>최대 125x 레버리지</strong>
                                <span>다양한 레버리지 옵션 제공</span>
                            </div>
                        </li>
                        <li>
                            <div class="register-promo__feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="register-promo__feature-text">
                                <strong>24/7 거래</strong>
                                <span>언제 어디서나 실시간 거래</span>
                            </div>
                        </li>
                    </ul>

                    <div class="register-promo__stats">
                        <div class="register-promo__stat">
                            <span class="register-promo__stat-value">$50B+</span>
                            <span class="register-promo__stat-label">일일 거래량</span>
                        </div>
                        <div class="register-promo__stat">
                            <span class="register-promo__stat-value">10M+</span>
                            <span class="register-promo__stat-label">글로벌 사용자</span>
                        </div>
                        <div class="register-promo__stat">
                            <span class="register-promo__stat-value">200+</span>
                            <span class="register-promo__stat-label">지원 국가</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const form = document.getElementById('registerForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('passwordConfirm');
        const submitBtn = document.getElementById('submitBtn');
        const agreeAll = document.getElementById('agreeAll');
        const requiredCheckboxes = document.querySelectorAll('#agreeTerms, #agreePrivacy, #agreeRisk');
        const allCheckboxes = document.querySelectorAll('.register-agreements__list input[type="checkbox"]');

        // Password strength elements
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');
        const reqLength = document.getElementById('reqLength');
        const reqUppercase = document.getElementById('reqUppercase');
        const reqLowercase = document.getElementById('reqLowercase');
        const reqNumber = document.getElementById('reqNumber');
        const reqSpecial = document.getElementById('reqSpecial');

        // Referral toggle
        const toggleReferral = document.getElementById('toggleReferral');
        const referralInputWrapper = document.getElementById('referralInputWrapper');

        // Password visibility toggles
        document.querySelectorAll('.register-password-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const iconEye = this.querySelector('.icon-eye');
                const iconEyeOff = this.querySelector('.icon-eye-off');

                if (input.type === 'password') {
                    input.type = 'text';
                    iconEye.style.display = 'none';
                    iconEyeOff.style.display = 'block';
                } else {
                    input.type = 'password';
                    iconEye.style.display = 'block';
                    iconEyeOff.style.display = 'none';
                }
            });
        });

        // Referral code toggle
        if (toggleReferral) {
            toggleReferral.addEventListener('click', function() {
                const isHidden = referralInputWrapper.style.display === 'none';
                referralInputWrapper.style.display = isHidden ? 'block' : 'none';
                this.querySelector('svg').style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0)';
            });

            // Show referral input if code exists
            if (document.getElementById('referralCode').value) {
                referralInputWrapper.style.display = 'block';
                toggleReferral.querySelector('svg').style.transform = 'rotate(180deg)';
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            // Update requirement indicators
            updateRequirement(reqLength, checks.length);
            updateRequirement(reqUppercase, checks.uppercase);
            updateRequirement(reqLowercase, checks.lowercase);
            updateRequirement(reqNumber, checks.number);
            updateRequirement(reqSpecial, checks.special);

            // Calculate strength
            const passedChecks = Object.values(checks).filter(Boolean).length;
            let strength = 0;
            let strengthLabel = '';
            let strengthColor = '';

            if (password.length === 0) {
                strengthLabel = '비밀번호 강도';
                strengthColor = '#2b3139';
            } else if (passedChecks <= 2) {
                strength = 25;
                strengthLabel = '약함';
                strengthColor = '#f6465d';
            } else if (passedChecks === 3) {
                strength = 50;
                strengthLabel = '보통';
                strengthColor = '#f0b90b';
            } else if (passedChecks === 4) {
                strength = 75;
                strengthLabel = '강함';
                strengthColor = '#1e88e5';
            } else {
                strength = 100;
                strengthLabel = '매우 강함';
                strengthColor = '#0ecb81';
            }

            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = strengthColor;
            strengthText.textContent = strengthLabel;
            strengthText.style.color = strengthColor;

            return checks.length && passedChecks >= 3;
        }

        function updateRequirement(element, passed) {
            if (passed) {
                element.classList.add('passed');
            } else {
                element.classList.remove('passed');
            }
        }

        // Email validation
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Form validation
        function validateForm() {
            const email = emailInput.value.trim();
            const password = passwordInput.value;
            const passwordConfirm = passwordConfirmInput.value;

            let isValid = true;

            // Email
            if (!email) {
                showError('emailError', '이메일을 입력해주세요');
                isValid = false;
            } else if (!validateEmail(email)) {
                showError('emailError', '올바른 이메일 형식이 아닙니다');
                isValid = false;
            } else {
                hideError('emailError');
            }

            // Password
            if (!password) {
                showError('passwordError', '비밀번호를 입력해주세요');
                isValid = false;
            } else if (!checkPasswordStrength(password)) {
                showError('passwordError', '비밀번호 요구사항을 충족해주세요');
                isValid = false;
            } else {
                hideError('passwordError');
            }

            // Password confirm
            if (!passwordConfirm) {
                showError('passwordConfirmError', '비밀번호 확인을 입력해주세요');
                isValid = false;
            } else if (password !== passwordConfirm) {
                showError('passwordConfirmError', '비밀번호가 일치하지 않습니다');
                isValid = false;
            } else {
                hideError('passwordConfirmError');
            }

            // Agreements
            let allRequiredChecked = true;
            requiredCheckboxes.forEach(cb => {
                if (!cb.checked) allRequiredChecked = false;
            });

            if (!allRequiredChecked) {
                isValid = false;
            }

            submitBtn.disabled = !isValid;
            return isValid;
        }

        function showError(elementId, message) {
            const el = document.getElementById(elementId);
            el.textContent = message;
            el.style.display = 'block';
        }

        function hideError(elementId) {
            const el = document.getElementById(elementId);
            el.style.display = 'none';
        }

        // Event listeners for real-time validation
        emailInput.addEventListener('input', validateForm);
        emailInput.addEventListener('blur', validateForm);

        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            validateForm();
        });

        passwordConfirmInput.addEventListener('input', validateForm);

        // Agreement checkboxes
        agreeAll.addEventListener('change', function() {
            allCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            validateForm();
        });

        allCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const allChecked = Array.from(allCheckboxes).every(c => c.checked);
                agreeAll.checked = allChecked;
                validateForm();
            });
        });

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!validateForm()) return;

            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            const formData = new FormData(form);

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to email verification page
                    window.location.href = 'verify-email.php?email=' + encodeURIComponent(emailInput.value);
                } else {
                    alert(data.message || '회원가입에 실패했습니다. 다시 시도해주세요.');
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });

        // Initial validation
        validateForm();
    });
    </script>
</body>
</html>
