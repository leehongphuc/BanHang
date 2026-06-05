<?php
session_start();
require './config/db.php';

$token = $_GET['token'] ?? '';
$message = '';
$messageType = '';
$validToken = false;

// Validate token
if ($token) {
    // Check if token exists and is not expired (within 1 hour)
    $stmt = $conn->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $createdAt = strtotime($result['created_at']);
        $now = time();
        $expiryTime = 3600; // 1 hour
        
        if (($now - $createdAt) <= $expiryTime) {
            $validToken = true;
            $email = $result['email'];
        } else {
            $message = 'Link đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu link mới.';
            $messageType = 'error';
        }
    } else {
        $message = 'Link đặt lại mật khẩu không hợp lệ.';
        $messageType = 'error';
    }
} else {
    $message = 'Thiếu token xác thực.';
    $messageType = 'error';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!$password || !$confirmPassword) {
        $message = 'Vui lòng nhập đầy đủ thông tin.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Mật khẩu phải có ít nhất 6 ký tự.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Mật khẩu xác nhận không khớp.';
        $messageType = 'error';
    } else {
        // Hash new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param('ss', $hashedPassword, $email);
        
        if ($stmt->execute()) {
            // Delete used token
            $conn->query("DELETE FROM password_resets WHERE token = '" . $conn->real_escape_string($token) . "'");
            
            // Set success message in session
            $_SESSION['reset_success'] = 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.';
            
            // Redirect to login
            header('Location: login.php');
            exit;
        } else {
            $message = 'Có lỗi xảy ra. Vui lòng thử lại.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu — TechStore</title>
    <?php include 'header.php'; ?>
    <style>
        .reset-password-container {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--sp-6) var(--sp-4);
        }
        .reset-password-card {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            padding: var(--sp-8);
            max-width: 480px;
            width: 100%;
        }
        .reset-password-title {
            font-size: var(--text-2xl);
            font-weight: 700;
            color: var(--clr-text-primary);
            margin-bottom: var(--sp-2);
            text-align: center;
        }
        .reset-password-subtitle {
            font-size: var(--text-sm);
            color: var(--clr-text-secondary);
            margin-bottom: var(--sp-6);
            text-align: center;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: var(--sp-4);
        }
        .form-label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--clr-text-secondary);
            margin-bottom: var(--sp-2);
        }
        .form-input {
            width: 100%;
            height: 48px;
            padding: 0 var(--sp-3);
            border: 1.5px solid var(--clr-border);
            border-radius: var(--r-sm);
            font-family: inherit;
            font-size: var(--text-md);
            color: var(--clr-text-primary);
            transition: all var(--dur-fast);
        }
        .form-input:focus {
            outline: none;
            border-color: var(--clr-brand);
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }
        .btn-submit {
            width: 100%;
            height: 48px;
            background: var(--clr-brand);
            color: white;
            border: none;
            border-radius: var(--r-sm);
            font-family: inherit;
            font-size: var(--text-md);
            font-weight: 700;
            cursor: pointer;
            transition: all var(--dur-fast);
        }
        .btn-submit:hover {
            background: var(--clr-brand-hover);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: var(--sp-4);
            color: var(--clr-text-secondary);
            font-size: var(--text-sm);
            text-decoration: none;
        }
        .back-link:hover {
            color: var(--clr-brand);
        }
        .alert {
            padding: var(--sp-3) var(--sp-4);
            border-radius: var(--r-sm);
            margin-bottom: var(--sp-4);
            font-size: var(--text-sm);
        }
        .alert-success {
            background: var(--clr-success-light);
            color: var(--clr-success);
            border: 1px solid var(--clr-success);
        }
        .alert-error {
            background: var(--clr-error-light);
            color: var(--clr-error);
            border: 1px solid var(--clr-error);
        }
        .password-hint {
            font-size: var(--text-xs);
            color: var(--clr-text-tertiary);
            margin-top: var(--sp-1);
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="reset-password-card">
            <h1 class="reset-password-title">Đặt lại mật khẩu</h1>
            <p class="reset-password-subtitle">
                Nhập mật khẩu mới cho tài khoản của bạn.
            </p>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password" class="form-label">Mật khẩu mới</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        placeholder="Nhập mật khẩu mới"
                        required
                        autofocus
                        minlength="6"
                    >
                    <div class="password-hint">Mật khẩu phải có ít nhất 6 ký tự</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input"
                        placeholder="Nhập lại mật khẩu mới"
                        required
                        minlength="6"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    Đặt lại mật khẩu
                </button>
            </form>
            <?php else: ?>
                <a href="forgot_password.php" class="btn-submit" style="display: block; text-align: center; line-height: 48px; text-decoration: none;">
                    Yêu cầu link mới
                </a>
            <?php endif; ?>

            <a href="login.php" class="back-link">← Quay lại đăng nhập</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
