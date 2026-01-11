<?php
/**
 * FuturX Admin - 로그인 페이지
 */

require_once 'auth.php';

// 이미 로그인되어 있으면 대시보드로
if (isAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';

// 로그인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = '이메일과 비밀번호를 입력해주세요.';
    } else {
        $result = adminLogin($email, $password);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 - FuturX Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primary: #f0b90b;
            --color-primary-hover: #d9a60a;
            --bg-primary: #0b0e11;
            --bg-secondary: #1e2329;
            --bg-tertiary: #2b3139;
            --text-primary: #eaecef;
            --text-secondary: #848e9c;
            --border-color: #2b3139;
            --color-error: #f6465d;
            --color-success: #0ecb81;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .login-logo__icon {
            width: 48px;
            height: 48px;
            background: var(--color-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: var(--bg-primary);
        }

        .login-logo__text {
            font-size: 24px;
            font-weight: 700;
        }

        .login-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert--error {
            background: rgba(246, 70, 93, 0.1);
            border: 1px solid var(--color-error);
            color: var(--color-error);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 15px;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: var(--color-primary);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .input-wrapper .form-input {
            padding-left: 42px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            accent-color: var(--color-primary);
        }

        .forgot-link {
            font-size: 14px;
            color: var(--color-primary);
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn--primary {
            background: var(--color-primary);
            color: var(--bg-primary);
        }

        .btn--primary:hover {
            background: var(--color-primary-hover);
        }

        .btn--primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 14px;
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--text-primary);
        }

        .login-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse 60% 40% at 80% 20%, rgba(240, 185, 11, 0.1), transparent),
                radial-gradient(ellipse 60% 40% at 20% 80%, rgba(14, 203, 129, 0.05), transparent);
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="login-bg"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <div class="login-logo__icon">F</div>
                    <span class="login-logo__text">FuturX</span>
                </div>
                <h1 class="login-title">관리자 로그인</h1>
            </div>

            <?php if ($error): ?>
            <div class="alert alert--error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label">이메일</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="form-input" placeholder="admin@futurx.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">비밀번호</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required id="passwordInput">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        로그인 상태 유지
                    </label>
                    <a href="#" class="forgot-link">비밀번호 찾기</a>
                </div>

                <button type="submit" class="btn btn--primary">
                    로그인
                </button>
            </form>

            <div class="login-footer">
                <a href="../index.html" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    메인 사이트로 돌아가기
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
