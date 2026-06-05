<?php
session_start();
require './config/db.php';
require './config/auth.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $errors[] = 'Vui lòng nhập email và mật khẩu.';
    } else {
        $esc = $conn->real_escape_string($email);
        $res = $conn->query("SELECT id, fullname, password, role, is_active FROM users WHERE email = '$esc' LIMIT 1");
        $user = $res ? $res->fetch_assoc() : null;

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        } elseif (!$user['is_active']) {
            $errors[] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.';
        } else {
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['user_role'] = $user['role'];

            $redirect = $_POST['redirect'] ?? 'index.php';
            header("Location: $redirect");
            exit;
        }
    }
}

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon auth-icon--login">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
            </div>
            <h1 class="auth-title">Đăng nhập</h1>
            <p class="auth-subtitle">Chào mừng bạn quay lại TechStore</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert">
                <?php foreach ($errors as $e): ?>
                    <p>⚠ <?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <?php if ($redirect): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($oldEmail) ?>"
                       placeholder="email@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth-submit">
                Đăng nhập
            </button>
        </form>

        <p class="auth-switch" style="margin-top: var(--sp-3); text-align: center;">
            <a href="forgot_password.php" style="color: var(--clr-text-secondary); text-decoration: none; font-size: var(--text-sm);">Quên mật khẩu?</a>
        </p>

        <p class="auth-switch">
            Chưa có tài khoản? <a href="register.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Đăng ký ngay</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
