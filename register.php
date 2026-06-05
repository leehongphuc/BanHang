<?php
session_start();
require './config/db.php';
require './config/auth.php';

// Đã đăng nhập rồi → về trang chủ
if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
$old = ['fullname' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['fullname'] = $fullname = trim($_POST['fullname'] ?? '');
    $old['email']    = $email    = trim($_POST['email'] ?? '');
    $old['phone']    = $phone    = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $password2       = $_POST['password_confirm'] ?? '';

    // Validate
    if (!$fullname)                      $errors[] = 'Vui lòng nhập họ và tên.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (mb_strlen($password) < 6)        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    if ($password !== $password2)         $errors[] = 'Xác nhận mật khẩu không khớp.';

    // Check email unique
    if (empty($errors)) {
        $esc = $conn->real_escape_string($email);
        $chk = $conn->query("SELECT id FROM users WHERE email = '$esc'");
        if ($chk && $chk->num_rows > 0) $errors[] = 'Email này đã được sử dụng.';
    }

    // Insert
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $fullname, $email, $phone, $hash);

        if ($stmt->execute()) {
            // Auto login
            $_SESSION['user_id']   = $stmt->insert_id;
            $_SESSION['user_name'] = $fullname;

            $redirect = $_POST['redirect'] ?? 'index.php';
            header("Location: $redirect");
            exit;
        } else {
            $errors[] = 'Đăng ký thất bại, vui lòng thử lại.';
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
    <title>Đăng ký — TechStore</title>
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
            <div class="auth-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
            </div>
            <h1 class="auth-title">Tạo tài khoản</h1>
            <p class="auth-subtitle">Đăng ký để mua sắm và theo dõi đơn hàng</p>
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
                <label for="fullname">Họ và tên *</label>
                <input type="text" id="fullname" name="fullname"
                       value="<?= htmlspecialchars($old['fullname']) ?>"
                       placeholder="Nguyễn Văn A" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($old['email']) ?>"
                       placeholder="email@example.com" required>
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= htmlspecialchars($old['phone']) ?>"
                       placeholder="0901234567">
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu * <small style="color:var(--clr-text-tertiary);">(tối thiểu 6 ký tự)</small></label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="password_confirm">Xác nhận mật khẩu *</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       placeholder="••••••••" minlength="6" required>
            </div>

            <button type="submit" class="btn-auth-submit">
                Đăng ký
            </button>
        </form>

        <p class="auth-switch">
            Đã có tài khoản? <a href="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Đăng nhập</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
