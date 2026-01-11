<?php
/**
 * FuturX - KYC 신원인증 페이지
 * 바이낸스/바이빗 스타일 KYC 인증 프로세스
 */

$pageTitle = '신원 인증 (KYC)';
$pageCSS = ['kyc.css'];

require_once 'includes/bootstrap.php';
require_once 'includes/head.php';
require_once 'includes/header.php';

// 현재 사용자 KYC 상태 (DB 연동 시 사용)
$kycLevel = 0; // 0: 미인증, 1: 기본인증, 2: 고급인증
$kycStatus = 'none'; // none, pending, approved, rejected

// 테스트용 - 로그인 체크는 실제 운영시 활성화
// requireLogin();
?>

<main class="main kyc-page">
    <div class="kyc-container">
        <!-- 페이지 헤더 -->
        <div class="kyc-header">
            <div class="kyc-header__title-row">
                <h1 class="kyc-header__title">신원 인증</h1>
                <span class="kyc-header__badge kyc-header__badge--level0">Level <?= e($kycLevel) ?></span>
            </div>
            <p class="kyc-header__desc">본인 인증을 완료하여 출금 한도를 높이고 모든 기능을 이용하세요</p>
        </div>

        <!-- KYC 레벨 카드 -->
        <div class="kyc-levels">
            <!-- Level 0 -->
            <div class="kyc-level <?= $kycLevel >= 0 ? 'kyc-level--completed' : '' ?>">
                <div class="kyc-level__header">
                    <div class="kyc-level__icon kyc-level__icon--done">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="kyc-level__info">
                        <h3 class="kyc-level__title">Level 0</h3>
                        <p class="kyc-level__subtitle">이메일 인증</p>
                    </div>
                    <div class="kyc-level__status kyc-level__status--done">완료</div>
                </div>
                <div class="kyc-level__body">
                    <ul class="kyc-level__features">
                        <li><i class="fas fa-check"></i> 입금 가능</li>
                        <li><i class="fas fa-check"></i> 거래 가능</li>
                    </ul>
                    <div class="kyc-level__limit">
                        <span class="kyc-level__limit-label">일일 출금 한도</span>
                        <span class="kyc-level__limit-value">0 USDT</span>
                    </div>
                </div>
            </div>

            <!-- Level 1 -->
            <div class="kyc-level <?= $kycLevel >= 1 ? 'kyc-level--completed' : 'kyc-level--active' ?>">
                <div class="kyc-level__header">
                    <div class="kyc-level__icon <?= $kycLevel >= 1 ? 'kyc-level__icon--done' : 'kyc-level__icon--pending' ?>">
                        <?php if ($kycLevel >= 1): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            <span>1</span>
                        <?php endif; ?>
                    </div>
                    <div class="kyc-level__info">
                        <h3 class="kyc-level__title">Level 1</h3>
                        <p class="kyc-level__subtitle">기본 신원 인증</p>
                    </div>
                    <div class="kyc-level__status <?= $kycLevel >= 1 ? 'kyc-level__status--done' : 'kyc-level__status--pending' ?>">
                        <?= $kycLevel >= 1 ? '완료' : '인증 필요' ?>
                    </div>
                </div>
                <div class="kyc-level__body">
                    <ul class="kyc-level__features">
                        <li><i class="fas fa-id-card"></i> 신분증 사진 제출</li>
                        <li><i class="fas fa-camera"></i> 셀피 인증</li>
                    </ul>
                    <div class="kyc-level__limit">
                        <span class="kyc-level__limit-label">일일 출금 한도</span>
                        <span class="kyc-level__limit-value">50,000 USDT</span>
                    </div>
                </div>
            </div>

            <!-- Level 2 -->
            <div class="kyc-level <?= $kycLevel >= 2 ? 'kyc-level--completed' : 'kyc-level--locked' ?>">
                <div class="kyc-level__header">
                    <div class="kyc-level__icon <?= $kycLevel >= 2 ? 'kyc-level__icon--done' : 'kyc-level__icon--locked' ?>">
                        <?php if ($kycLevel >= 2): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            <i class="fas fa-lock"></i>
                        <?php endif; ?>
                    </div>
                    <div class="kyc-level__info">
                        <h3 class="kyc-level__title">Level 2</h3>
                        <p class="kyc-level__subtitle">고급 인증</p>
                    </div>
                    <div class="kyc-level__status">
                        <?= $kycLevel >= 2 ? '완료' : 'Level 1 완료 후' ?>
                    </div>
                </div>
                <div class="kyc-level__body">
                    <ul class="kyc-level__features">
                        <li><i class="fas fa-file-alt"></i> 주소 증명 서류</li>
                        <li><i class="fas fa-search"></i> 추가 서류 검토</li>
                    </ul>
                    <div class="kyc-level__limit">
                        <span class="kyc-level__limit-label">일일 출금 한도</span>
                        <span class="kyc-level__limit-value">2,000,000 USDT</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KYC 인증 폼 영역 -->
        <div class="kyc-form-section">
            <!-- 스텝 인디케이터 -->
            <div class="kyc-steps">
                <div class="kyc-step kyc-step--completed" data-step="1">
                    <div class="kyc-step__number"><i class="fas fa-check"></i></div>
                    <div class="kyc-step__content">
                        <span class="kyc-step__title">국가 선택</span>
                        <span class="kyc-step__desc">거주 국가/지역</span>
                    </div>
                </div>
                <div class="kyc-step__connector"></div>
                <div class="kyc-step kyc-step--active" data-step="2">
                    <div class="kyc-step__number">2</div>
                    <div class="kyc-step__content">
                        <span class="kyc-step__title">개인 정보</span>
                        <span class="kyc-step__desc">이름, 생년월일</span>
                    </div>
                </div>
                <div class="kyc-step__connector"></div>
                <div class="kyc-step" data-step="3">
                    <div class="kyc-step__number">3</div>
                    <div class="kyc-step__content">
                        <span class="kyc-step__title">신분증 제출</span>
                        <span class="kyc-step__desc">신분증/여권</span>
                    </div>
                </div>
                <div class="kyc-step__connector"></div>
                <div class="kyc-step" data-step="4">
                    <div class="kyc-step__number">4</div>
                    <div class="kyc-step__content">
                        <span class="kyc-step__title">셀피 인증</span>
                        <span class="kyc-step__desc">얼굴 인증</span>
                    </div>
                </div>
            </div>

            <!-- 폼 컨테이너 -->
            <div class="kyc-form-wrapper">
                <!-- Step 1: 국가 선택 -->
                <form class="kyc-form" id="kycStep1" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">

                    <div class="kyc-form__header">
                        <h2 class="kyc-form__title">거주 국가/지역 선택</h2>
                        <p class="kyc-form__desc">현재 거주하고 계신 국가를 선택해주세요</p>
                    </div>

                    <div class="kyc-form__body">
                        <div class="kyc-form-group">
                            <label class="kyc-form-label">국가/지역 <span class="required">*</span></label>
                            <div class="kyc-select-wrapper">
                                <select class="kyc-select" id="country" name="country" required>
                                    <option value="">국가를 선택하세요</option>
                                    <option value="KR" selected>🇰🇷 대한민국</option>
                                    <option value="US">🇺🇸 미국</option>
                                    <option value="JP">🇯🇵 일본</option>
                                    <option value="CN">🇨🇳 중국</option>
                                    <option value="SG">🇸🇬 싱가포르</option>
                                    <option value="HK">🇭🇰 홍콩</option>
                                </select>
                                <i class="fas fa-chevron-down kyc-select__arrow"></i>
                            </div>
                        </div>

                        <div class="kyc-notice kyc-notice--info">
                            <i class="fas fa-info-circle"></i>
                            <div class="kyc-notice__content">
                                <strong>안내</strong>
                                <p>국가/지역은 이후 변경이 어려우니 신중하게 선택해주세요. 제출하는 신분증의 발급 국가와 일치해야 합니다.</p>
                            </div>
                        </div>
                    </div>

                    <div class="kyc-form__footer">
                        <button type="button" class="kyc-btn kyc-btn--primary kyc-btn--full" onclick="goToStep(2)">
                            다음 단계 <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>

                <!-- Step 2: 개인 정보 -->
                <form class="kyc-form" id="kycStep2">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">

                    <div class="kyc-form__header">
                        <h2 class="kyc-form__title">개인 정보 입력</h2>
                        <p class="kyc-form__desc">신분증에 기재된 정보와 동일하게 입력해주세요</p>
                    </div>

                    <div class="kyc-form__body">
                        <div class="kyc-form-row">
                            <div class="kyc-form-group">
                                <label class="kyc-form-label">성 (영문) <span class="required">*</span></label>
                                <input type="text" class="kyc-input" name="last_name" placeholder="예: HONG" required pattern="[A-Za-z\s]+" title="영문만 입력 가능합니다">
                                <span class="kyc-form-hint">여권/신분증에 표기된 영문 성</span>
                            </div>
                            <div class="kyc-form-group">
                                <label class="kyc-form-label">이름 (영문) <span class="required">*</span></label>
                                <input type="text" class="kyc-input" name="first_name" placeholder="예: GILDONG" required pattern="[A-Za-z\s]+" title="영문만 입력 가능합니다">
                                <span class="kyc-form-hint">여권/신분증에 표기된 영문 이름</span>
                            </div>
                        </div>

                        <div class="kyc-form-row">
                            <div class="kyc-form-group">
                                <label class="kyc-form-label">성명 (한글)</label>
                                <input type="text" class="kyc-input" name="name_kr" placeholder="예: 홍길동">
                            </div>
                            <div class="kyc-form-group">
                                <label class="kyc-form-label">생년월일 <span class="required">*</span></label>
                                <div class="kyc-date-inputs">
                                    <select class="kyc-select kyc-select--sm" name="birth_year" required>
                                        <option value="">연도</option>
                                        <?php for ($y = date('Y') - 18; $y >= 1940; $y--): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select class="kyc-select kyc-select--sm" name="birth_month" required>
                                        <option value="">월</option>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= sprintf('%02d', $m) ?>"><?= sprintf('%02d', $m) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select class="kyc-select kyc-select--sm" name="birth_day" required>
                                        <option value="">일</option>
                                        <?php for ($d = 1; $d <= 31; $d++): ?>
                                            <option value="<?= sprintf('%02d', $d) ?>"><?= sprintf('%02d', $d) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="kyc-form-group">
                            <label class="kyc-form-label">신분증 종류 <span class="required">*</span></label>
                            <div class="kyc-id-types">
                                <label class="kyc-id-type">
                                    <input type="radio" name="id_type" value="id_card" checked>
                                    <div class="kyc-id-type__content">
                                        <div class="kyc-id-type__icon">🪪</div>
                                        <div class="kyc-id-type__info">
                                            <span class="kyc-id-type__title">주민등록증</span>
                                            <span class="kyc-id-type__desc">대한민국 주민등록증</span>
                                        </div>
                                        <div class="kyc-id-type__check"><i class="fas fa-check"></i></div>
                                    </div>
                                </label>
                                <label class="kyc-id-type">
                                    <input type="radio" name="id_type" value="driver">
                                    <div class="kyc-id-type__content">
                                        <div class="kyc-id-type__icon">🚗</div>
                                        <div class="kyc-id-type__info">
                                            <span class="kyc-id-type__title">운전면허증</span>
                                            <span class="kyc-id-type__desc">대한민국 운전면허증</span>
                                        </div>
                                        <div class="kyc-id-type__check"><i class="fas fa-check"></i></div>
                                    </div>
                                </label>
                                <label class="kyc-id-type">
                                    <input type="radio" name="id_type" value="passport">
                                    <div class="kyc-id-type__content">
                                        <div class="kyc-id-type__icon">📘</div>
                                        <div class="kyc-id-type__info">
                                            <span class="kyc-id-type__title">여권</span>
                                            <span class="kyc-id-type__desc">대한민국 여권</span>
                                        </div>
                                        <div class="kyc-id-type__check"><i class="fas fa-check"></i></div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="kyc-form-group">
                            <label class="kyc-form-label">신분증 번호 <span class="required">*</span></label>
                            <input type="text" class="kyc-input" name="id_number" placeholder="신분증에 기재된 번호" required>
                            <span class="kyc-form-hint">주민등록번호, 운전면허번호 또는 여권번호</span>
                        </div>
                    </div>

                    <div class="kyc-form__footer">
                        <button type="button" class="kyc-btn kyc-btn--secondary" onclick="goToStep(1)">
                            <i class="fas fa-arrow-left"></i> 이전
                        </button>
                        <button type="button" class="kyc-btn kyc-btn--primary" onclick="goToStep(3)">
                            다음 단계 <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>

                <!-- Step 3: 신분증 업로드 -->
                <form class="kyc-form" id="kycStep3" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">

                    <div class="kyc-form__header">
                        <h2 class="kyc-form__title">신분증 사진 업로드</h2>
                        <p class="kyc-form__desc">신분증의 앞면과 뒷면 사진을 각각 업로드해주세요</p>
                    </div>

                    <div class="kyc-form__body">
                        <div class="kyc-upload-grid">
                            <!-- 앞면 -->
                            <div class="kyc-upload-card">
                                <div class="kyc-upload-card__header">
                                    <span class="kyc-upload-card__title">신분증 앞면</span>
                                    <span class="kyc-upload-card__badge">필수</span>
                                </div>
                                <div class="kyc-upload-dropzone" id="frontDropzone" data-target="front">
                                    <div class="kyc-upload-dropzone__content">
                                        <div class="kyc-upload-dropzone__icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <p class="kyc-upload-dropzone__text">클릭하여 파일 선택<br><span>또는 파일을 드래그하세요</span></p>
                                        <span class="kyc-upload-dropzone__formats">JPG, PNG (최대 10MB)</span>
                                    </div>
                                    <input type="file" id="frontInput" name="id_front" accept="image/jpeg,image/png" hidden>
                                </div>
                                <div class="kyc-upload-preview" id="frontPreview" style="display: none;">
                                    <img src="" alt="앞면 미리보기">
                                    <button type="button" class="kyc-upload-preview__remove" onclick="removeFile('front')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- 뒷면 -->
                            <div class="kyc-upload-card">
                                <div class="kyc-upload-card__header">
                                    <span class="kyc-upload-card__title">신분증 뒷면</span>
                                    <span class="kyc-upload-card__badge">필수</span>
                                </div>
                                <div class="kyc-upload-dropzone" id="backDropzone" data-target="back">
                                    <div class="kyc-upload-dropzone__content">
                                        <div class="kyc-upload-dropzone__icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <p class="kyc-upload-dropzone__text">클릭하여 파일 선택<br><span>또는 파일을 드래그하세요</span></p>
                                        <span class="kyc-upload-dropzone__formats">JPG, PNG (최대 10MB)</span>
                                    </div>
                                    <input type="file" id="backInput" name="id_back" accept="image/jpeg,image/png" hidden>
                                </div>
                                <div class="kyc-upload-preview" id="backPreview" style="display: none;">
                                    <img src="" alt="뒷면 미리보기">
                                    <button type="button" class="kyc-upload-preview__remove" onclick="removeFile('back')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 촬영 가이드 -->
                        <div class="kyc-upload-guide">
                            <h4 class="kyc-upload-guide__title">
                                <i class="fas fa-lightbulb"></i> 사진 촬영 가이드
                            </h4>
                            <div class="kyc-upload-guide__grid">
                                <div class="kyc-guide-item kyc-guide-item--do">
                                    <div class="kyc-guide-item__icon"><i class="fas fa-check-circle"></i></div>
                                    <div class="kyc-guide-item__content">
                                        <strong>올바른 예시</strong>
                                        <ul>
                                            <li>신분증 전체가 보이게 촬영</li>
                                            <li>밝은 곳에서 선명하게</li>
                                            <li>반사광 없이 촬영</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="kyc-guide-item kyc-guide-item--dont">
                                    <div class="kyc-guide-item__icon"><i class="fas fa-times-circle"></i></div>
                                    <div class="kyc-guide-item__content">
                                        <strong>잘못된 예시</strong>
                                        <ul>
                                            <li>흐릿하거나 잘린 사진</li>
                                            <li>반사광이 있는 사진</li>
                                            <li>흑백 또는 수정된 사진</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="kyc-form__footer">
                        <button type="button" class="kyc-btn kyc-btn--secondary" onclick="goToStep(2)">
                            <i class="fas fa-arrow-left"></i> 이전
                        </button>
                        <button type="button" class="kyc-btn kyc-btn--primary" onclick="goToStep(4)">
                            다음 단계 <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>

                <!-- Step 4: 셀피 인증 -->
                <form class="kyc-form" id="kycStep4" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">

                    <div class="kyc-form__header">
                        <h2 class="kyc-form__title">셀피 인증</h2>
                        <p class="kyc-form__desc">본인 확인을 위해 셀피(자가 촬영)를 진행해주세요</p>
                    </div>

                    <div class="kyc-form__body">
                        <div class="kyc-selfie-section">
                            <!-- 가이드 -->
                            <div class="kyc-selfie-guide">
                                <div class="kyc-selfie-guide__visual">
                                    <div class="kyc-selfie-frame">
                                        <svg viewBox="0 0 200 200" class="kyc-selfie-frame__svg">
                                            <circle cx="100" cy="80" r="50" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-dasharray="8,4"/>
                                            <ellipse cx="100" cy="150" rx="60" ry="30" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-dasharray="8,4"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="kyc-selfie-guide__instructions">
                                    <h4>촬영 안내</h4>
                                    <ul>
                                        <li><i class="fas fa-check"></i> 얼굴이 화면 중앙에 오도록 위치</li>
                                        <li><i class="fas fa-check"></i> 정면을 바라보며 자연스러운 표정</li>
                                        <li><i class="fas fa-check"></i> 모자, 선글라스, 마스크 제거</li>
                                        <li><i class="fas fa-check"></i> 밝은 곳에서 촬영</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- 촬영 옵션 -->
                            <div class="kyc-selfie-options" id="selfieOptions">
                                <button type="button" class="kyc-selfie-option" onclick="startCamera()">
                                    <div class="kyc-selfie-option__icon"><i class="fas fa-camera"></i></div>
                                    <div class="kyc-selfie-option__info">
                                        <span class="kyc-selfie-option__title">웹캠으로 촬영</span>
                                        <span class="kyc-selfie-option__desc">실시간으로 셀피를 촬영합니다</span>
                                    </div>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <button type="button" class="kyc-selfie-option" onclick="document.getElementById('selfieInput').click()">
                                    <div class="kyc-selfie-option__icon"><i class="fas fa-upload"></i></div>
                                    <div class="kyc-selfie-option__info">
                                        <span class="kyc-selfie-option__title">파일 업로드</span>
                                        <span class="kyc-selfie-option__desc">이미 촬영한 사진을 업로드합니다</span>
                                    </div>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <input type="file" id="selfieInput" name="selfie" accept="image/jpeg,image/png" hidden>
                            </div>

                            <!-- 카메라 영역 -->
                            <div class="kyc-camera" id="cameraSection" style="display: none;">
                                <div class="kyc-camera__wrapper">
                                    <video id="cameraVideo" autoplay playsinline></video>
                                    <div class="kyc-camera__overlay">
                                        <div class="kyc-camera__frame"></div>
                                    </div>
                                </div>
                                <div class="kyc-camera__controls">
                                    <button type="button" class="kyc-btn kyc-btn--secondary" onclick="closeCamera()">취소</button>
                                    <button type="button" class="kyc-btn kyc-btn--primary kyc-btn--capture" onclick="capturePhoto()">
                                        <i class="fas fa-camera"></i> 촬영하기
                                    </button>
                                </div>
                            </div>

                            <!-- 미리보기 -->
                            <div class="kyc-selfie-preview" id="selfiePreview" style="display: none;">
                                <img src="" alt="셀피 미리보기" id="selfieImage">
                                <div class="kyc-selfie-preview__actions">
                                    <button type="button" class="kyc-btn kyc-btn--secondary" onclick="retakeSelfie()">
                                        <i class="fas fa-redo"></i> 다시 촬영
                                    </button>
                                    <button type="button" class="kyc-btn kyc-btn--primary" onclick="confirmSelfie()">
                                        <i class="fas fa-check"></i> 이 사진 사용
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="kyc-form__footer">
                        <button type="button" class="kyc-btn kyc-btn--secondary" onclick="goToStep(3)">
                            <i class="fas fa-arrow-left"></i> 이전
                        </button>
                        <button type="button" class="kyc-btn kyc-btn--primary" onclick="submitKYC()" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane"></i> 제출하기
                        </button>
                    </div>
                </form>

                <!-- 제출 완료 -->
                <div class="kyc-form" id="kycComplete" style="display: none;">
                    <div class="kyc-complete">
                        <div class="kyc-complete__icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="kyc-complete__title">인증 서류가 제출되었습니다</h2>
                        <p class="kyc-complete__desc">
                            제출하신 서류는 검토 중이며, 보통 1~3 영업일 내에 완료됩니다.<br>
                            결과는 이메일과 알림으로 안내해드립니다.
                        </p>
                        <div class="kyc-complete__status">
                            <div class="kyc-complete__status-item">
                                <span class="kyc-complete__status-label">신청 일시</span>
                                <span class="kyc-complete__status-value" id="submitTime"></span>
                            </div>
                            <div class="kyc-complete__status-item">
                                <span class="kyc-complete__status-label">현재 상태</span>
                                <span class="kyc-complete__status-value kyc-complete__status-value--pending">
                                    <i class="fas fa-clock"></i> 검토 중
                                </span>
                            </div>
                            <div class="kyc-complete__status-item">
                                <span class="kyc-complete__status-label">예상 소요 시간</span>
                                <span class="kyc-complete__status-value">1~3 영업일</span>
                            </div>
                        </div>
                        <div class="kyc-complete__actions">
                            <a href="wallet.php" class="kyc-btn kyc-btn--primary">
                                <i class="fas fa-wallet"></i> 지갑으로 이동
                            </a>
                            <a href="index.php" class="kyc-btn kyc-btn--secondary">
                                <i class="fas fa-home"></i> 홈으로
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="kyc-faq">
            <h3 class="kyc-faq__title">자주 묻는 질문</h3>
            <div class="kyc-faq__list">
                <div class="kyc-faq__item">
                    <button type="button" class="kyc-faq__question">
                        <span>KYC 인증은 왜 필요한가요?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="kyc-faq__answer">
                        <p>KYC(Know Your Customer) 인증은 자금세탁방지(AML) 규정 준수 및 사용자 보호를 위해 필요합니다. 인증을 완료하면 출금 한도가 증가하고 모든 기능을 이용할 수 있습니다.</p>
                    </div>
                </div>
                <div class="kyc-faq__item">
                    <button type="button" class="kyc-faq__question">
                        <span>어떤 신분증을 사용할 수 있나요?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="kyc-faq__answer">
                        <p>대한민국의 경우 주민등록증, 운전면허증, 여권을 사용할 수 있습니다. 신분증은 유효기간 내의 것이어야 하며, 사진과 정보가 명확히 보여야 합니다.</p>
                    </div>
                </div>
                <div class="kyc-faq__item">
                    <button type="button" class="kyc-faq__question">
                        <span>인증 승인까지 얼마나 걸리나요?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="kyc-faq__answer">
                        <p>일반적으로 1~3 영업일 내에 완료됩니다. 서류에 문제가 있는 경우 추가 확인이 필요할 수 있으며, 이 경우 이메일로 안내해드립니다.</p>
                    </div>
                </div>
                <div class="kyc-faq__item">
                    <button type="button" class="kyc-faq__question">
                        <span>제출한 정보는 안전하게 보호되나요?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="kyc-faq__answer">
                        <p>네, 제출하신 모든 정보는 암호화되어 안전하게 저장됩니다. FuturX는 개인정보보호법을 준수하며, 인증 목적 외에는 절대 사용하지 않습니다.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// 현재 스텝
let currentStep = 2;
let selfieConfirmed = false;
let cameraStream = null;

// 스텝 이동
function goToStep(step) {
    // 현재 폼 숨기기
    document.getElementById(`kycStep${currentStep}`).style.display = 'none';

    // 새 폼 표시
    document.getElementById(`kycStep${step}`).style.display = 'block';

    // 스텝 인디케이터 업데이트
    updateStepIndicator(step);

    currentStep = step;

    // 스크롤 상단으로
    document.querySelector('.kyc-form-wrapper').scrollTop = 0;
    window.scrollTo({ top: document.querySelector('.kyc-form-section').offsetTop - 100, behavior: 'smooth' });
}

function updateStepIndicator(activeStep) {
    document.querySelectorAll('.kyc-step').forEach((el, idx) => {
        const stepNum = idx + 1;
        el.classList.remove('kyc-step--active', 'kyc-step--completed');

        if (stepNum < activeStep) {
            el.classList.add('kyc-step--completed');
            el.querySelector('.kyc-step__number').innerHTML = '<i class="fas fa-check"></i>';
        } else if (stepNum === activeStep) {
            el.classList.add('kyc-step--active');
            el.querySelector('.kyc-step__number').textContent = stepNum;
        } else {
            el.querySelector('.kyc-step__number').textContent = stepNum;
        }
    });
}

// 파일 업로드 설정
function setupDropzone(dropzoneId, inputId, previewId) {
    const dropzone = document.getElementById(dropzoneId);
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!dropzone || !input) return;

    dropzone.addEventListener('click', () => input.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('kyc-upload-dropzone--dragover');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('kyc-upload-dropzone--dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('kyc-upload-dropzone--dragover');
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            handleFile(file, dropzone, preview);
        }
    });

    input.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            handleFile(file, dropzone, preview);
        }
    });
}

function handleFile(file, dropzone, preview) {
    // 파일 크기 체크 (10MB)
    if (file.size > 10 * 1024 * 1024) {
        alert('파일 크기는 10MB 이하여야 합니다.');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        preview.querySelector('img').src = e.target.result;
        preview.style.display = 'block';
        dropzone.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeFile(side) {
    const dropzone = document.getElementById(`${side}Dropzone`);
    const preview = document.getElementById(`${side}Preview`);
    const input = document.getElementById(`${side}Input`);

    preview.style.display = 'none';
    dropzone.style.display = 'flex';
    input.value = '';
}

// 카메라 기능
function startCamera() {
    const cameraSection = document.getElementById('cameraSection');
    const selfieOptions = document.getElementById('selfieOptions');
    const video = document.getElementById('cameraVideo');

    navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'user',
            width: { ideal: 1280 },
            height: { ideal: 720 }
        }
    })
    .then(stream => {
        cameraStream = stream;
        video.srcObject = stream;
        cameraSection.style.display = 'block';
        selfieOptions.style.display = 'none';
    })
    .catch(err => {
        alert('카메라 접근 권한이 필요합니다.\n' + err.message);
        console.error(err);
    });
}

function closeCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    document.getElementById('cameraSection').style.display = 'none';
    document.getElementById('selfieOptions').style.display = 'flex';
}

function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    const imageData = canvas.toDataURL('image/jpeg', 0.9);
    document.getElementById('selfieImage').src = imageData;
    document.getElementById('selfiePreview').style.display = 'block';

    closeCamera();
}

function retakeSelfie() {
    document.getElementById('selfiePreview').style.display = 'none';
    document.getElementById('selfieOptions').style.display = 'flex';
    selfieConfirmed = false;
    document.getElementById('submitBtn').disabled = true;
}

function confirmSelfie() {
    selfieConfirmed = true;
    document.getElementById('submitBtn').disabled = false;

    // 성공 피드백
    const preview = document.getElementById('selfiePreview');
    preview.classList.add('kyc-selfie-preview--confirmed');
}

// 셀피 파일 업로드
document.getElementById('selfieInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert('파일 크기는 10MB 이하여야 합니다.');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('selfieImage').src = e.target.result;
            document.getElementById('selfiePreview').style.display = 'block';
            document.getElementById('selfieOptions').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// KYC 제출
function submitKYC() {
    if (!selfieConfirmed) {
        alert('셀피 사진을 확인해주세요.');
        return;
    }

    // 로딩 표시
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 제출 중...';

    // 실제로는 FormData로 서버 전송
    setTimeout(() => {
        // 현재 폼 숨기기
        document.getElementById('kycStep4').style.display = 'none';

        // 완료 화면 표시
        const completeSection = document.getElementById('kycComplete');
        completeSection.style.display = 'block';

        // 제출 시간 표시
        const now = new Date();
        document.getElementById('submitTime').textContent =
            `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

        // 스텝 인디케이터 모두 완료
        document.querySelectorAll('.kyc-step').forEach(el => {
            el.classList.add('kyc-step--completed');
            el.classList.remove('kyc-step--active');
            el.querySelector('.kyc-step__number').innerHTML = '<i class="fas fa-check"></i>';
        });
    }, 1500);
}

// FAQ 토글
document.querySelectorAll('.kyc-faq__question').forEach(btn => {
    btn.addEventListener('click', function() {
        const item = this.parentElement;
        const isOpen = item.classList.contains('kyc-faq__item--open');

        // 모두 닫기
        document.querySelectorAll('.kyc-faq__item').forEach(i => {
            i.classList.remove('kyc-faq__item--open');
        });

        // 현재 항목 토글
        if (!isOpen) {
            item.classList.add('kyc-faq__item--open');
        }
    });
});

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    setupDropzone('frontDropzone', 'frontInput', 'frontPreview');
    setupDropzone('backDropzone', 'backInput', 'backPreview');
});
</script>

<?php require_once 'includes/footer.php'; ?>
