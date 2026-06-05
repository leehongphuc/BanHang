<?php
session_start();
require './config/db.php';
require './includes/email_helper.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!$email) {
        $message = 'Vui lòng nhập địa chỉ email.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Địa chỉ email không hợp lệ.';
        $messageType = 'error';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Delete old tokens for this email
            $conn->query("DELETE FROM password_resets WHERE email = '" . $conn->real_escape_string($email) . "'");
            
            // Generate secure token
            $token = generateSecureToken();
            
            // Save token to database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $stmt->bind_param('ss', $email, $token);
            $stmt->execute();
            
            // Send email
            if (sendPasswordResetEmail($email, $token)) {
                $message = 'Đã gửi email hướng dẫn đặt lại mật khẩu. Vui lòng kiểm tra hộp thư của bạn.';
                $messageType = 'success';
            } else {
                $message = 'Không thể gửi email. Vui lòng thử lại sau.';
                $messageType = 'error';
            }
        } else {
            // Don't reveal whether email exists for security
            $message = 'Nếu email này tồn tại trong hệ thống, bạn sẽ nhận được email hướng dẫn đặt lại mật khẩu.';
            $messageType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .forgot-password-container {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--sp-6) var(--sp-4);
        }
        .forgot-password-card {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            padding: var(--sp-8);
            max-width: 480px;
            width: 100%;
        }
        .forgot-password-title {
            font-size: var(--text-2xl);
            font-weight: 700;
            color: var(--clr-text-primary);
            margin-bottom: var(--sp-2);
            text-align: center;
        }
        .forgot-password-subtitle {
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
    </style>
</head>
<body>
<?php include 'header.php'; ?>
    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <h1 class="forgot-password-title">Quên mật khẩu?</h1>
            <p class="forgot-password-subtitle">
                Nhập địa chỉ email bạn đã đăng ký. Chúng tôi sẽ gửi link đặt lại mật khẩu đến email của bạn.
            </p>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($messageType !== 'success'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Địa chỉ Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input"
                        placeholder="your@email.com"
                        required
                        autofocus
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    Gửi email đặt lại mật khẩu
                </button>
            </form>
            <?php endif; ?>

            <a href="login.php" class="back-link">← Quay lại đăng nhập</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
