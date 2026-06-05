<?php
// auth_check.php sẽ gọi session_name('ts_admin') + session_start()
require 'auth_check.php';
require '../config/db.php';

// Đã đăng nhập admin thì vào thẳng dashboard
if (isAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id, fullname, password, role, is_active FROM users WHERE email = ?"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || $user['role'] !== 'admin') {
            $error = 'Tài khoản không tồn tại hoặc không có quyền admin.';
        } elseif (!$user['is_active']) {
            $error = 'Tài khoản đã bị khóa.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Mật khẩu không chính xác.';
        } else {
            // Lưu vào session riêng của admin
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_role'] = 'admin';
            $_SESSION['user_name']  = $user['fullname'];
            $redirect = $_GET['redirect'] ?? '/BanHang/admin/index.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --brand: #2563eb; --brand-dark: #1d4ed8;
            --surface: #ffffff; --bg: #f1f5f9; --text: #0f172a;
            --text-2: #64748b; --border: #e2e8f0;
            --error: #ef4444; --error-light: #fef2f2;
            --r: 12px; --shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .login-card { background: var(--surface); border-radius: var(--r); padding: 48px 40px; width: 100%; max-width: 420px; box-shadow: var(--shadow); }
        .login-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .login-logo-icon { width: 44px; height: 44px; background: var(--brand); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .login-logo-icon svg { width: 22px; height: 22px; }
        .login-logo-text { font-size: 20px; font-weight: 800; color: var(--text); }
        .login-logo-sub { font-size: 12px; color: var(--text-2); font-weight: 500; }
        h1 { font-size: 22px; font-weight: 800; color: var(--text); margin-bottom: 6px; }
        .subtitle { color: var(--text-2); font-size: 14px; margin-bottom: 28px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--text-2); margin-bottom: 6px; }
        input[type=email], input[type=password] { width: 100%; height: 44px; padding: 0 14px; border: 1.5px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; color: var(--text); transition: border-color .2s; outline: none; }
        input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .btn-login { width: 100%; height: 46px; background: var(--brand); color: white; border: none; border-radius: 8px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: background .2s; margin-top: 8px; }
        .btn-login:hover { background: var(--brand-dark); }
        .alert-error { background: var(--error-light); border: 1px solid var(--error); border-radius: 8px; padding: 12px 14px; margin-bottom: 20px; color: var(--error); font-size: 13px; font-weight: 600; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--text-2); font-size: 13px; text-decoration: none; }
        .back-link:hover { color: var(--brand); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">
                <svg viewBox="0 0 24 24" fill="white"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <div>
                <div class="login-logo-text">TechStore</div>
                <div class="login-logo-sub">Admin Panel</div>
            </div>
        </div>

        <h1>Đăng nhập Admin</h1>
        <p class="subtitle">Nhập thông tin quản trị viên để tiếp tục</p>

        <?php if ($error): ?>
            <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Admin</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="admin@techstore.vn" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Đăng nhập →</button>
        </form>

        <a href="/BanHang/index.php" class="back-link">← Về trang chủ TechStore</a>
    </div>
</body>
</html>
