<?php
session_start();
require './config/db.php';
require './config/auth.php';
requireLogin();

$user = currentUser();
$tab = $_GET['tab'] ?? 'profile';
$msg = '';
$msgType = 'success';
$errors = [];

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    // == Update Profile ==
    if ($action === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');

        if (!$fullname) { $errors[] = 'Họ và tên không được để trống.'; }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, address=? WHERE id=?");
            $uid = $user['id'];
            $stmt->bind_param('sssi', $fullname, $phone, $address, $uid);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $fullname;
                $msg = 'Cập nhật thông tin thành công!';
                $user = null; // Force reload
                $user = currentUser();
            } else {
                $errors[] = 'Cập nhật thất bại.';
            }
        }
        $tab = 'profile';
    }

    // == Change Password ==
    if ($action === 'change_password') {
        $oldPw  = $_POST['old_password'] ?? '';
        $newPw  = $_POST['new_password'] ?? '';
        $newPw2 = $_POST['new_password_confirm'] ?? '';

        // Fetch current hash
        $uid = (int)$user['id'];
        $res = $conn->query("SELECT password FROM users WHERE id = $uid");
        $row = $res->fetch_assoc();

        if (!password_verify($oldPw, $row['password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        }
        if (mb_strlen($newPw) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        }
        if ($newPw !== $newPw2) {
            $errors[] = 'Xác nhận mật khẩu mới không khớp.';
        }

        if (empty($errors)) {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $uid);
            if ($stmt->execute()) {
                $msg = 'Đổi mật khẩu thành công!';
            } else {
                $errors[] = 'Đổi mật khẩu thất bại.';
            }
        }
        $tab = 'password';
    }
}

// ---- Load orders ----
$orders = [];
if ($tab === 'orders') {
    $uid = (int)$user['id'];
    $res = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $orders[] = $row;
    }
}

// Refresh user data
if (!$user) $user = currentUser();
$initials = mb_strtoupper(mb_substr($user['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container" style="margin-top:var(--sp-6);margin-bottom:var(--sp-10);">

    <!-- Account header -->
    <div class="account-hero">
        <div class="account-avatar"><?= $initials ?></div>
        <div>
            <h1 class="account-name"><?= htmlspecialchars($user['fullname']) ?></h1>
            <p class="account-email"><?= htmlspecialchars($user['email']) ?></p>
        </div>
    </div>

    <!-- Tabs -->

    <!-- Tabs -->
    <div class="account-tabs" role="tablist">
        <a href="?tab=profile" class="account-tab <?= $tab === 'profile' ? 'active' : '' ?>" role="tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Thông tin cá nhân
        </a>
        <a href="?tab=password" class="account-tab <?= $tab === 'password' ? 'active' : '' ?>" role="tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Đổi mật khẩu
        </a>
        <a href="?tab=orders" class="account-tab <?= $tab === 'orders' ? 'active' : '' ?>" role="tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Lịch sử đơn hàng
        </a>
    </div>

    <!-- Tab content -->
    <div class="account-content">
        <!-- Messages -->
        <?php if ($msg): ?>
            <div class="alert-success" role="alert" style="margin-bottom: var(--sp-4);"><p>✓ <?= htmlspecialchars($msg) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert" style="margin-bottom: var(--sp-4);">
                <?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'profile'): ?>
        <!-- === PROFILE === -->
        <form method="POST" class="account-form-card">
            <input type="hidden" name="form_action" value="update_profile">
            <h2 class="account-section-title">Thông tin cá nhân</h2>

            <div class="form-group">
                <label for="fullname">Họ và tên *</label>
                <input type="text" id="fullname" name="fullname"
                       value="<?= htmlspecialchars($user['fullname']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email (cố định)</label>
                <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>"
                       disabled style="opacity:.6;cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= htmlspecialchars($user['phone']) ?>"
                       placeholder="0901234567">
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ giao hàng mặc định</label>
                <textarea id="address" name="address" rows="3"
                          placeholder="Số nhà, đường, phường, quận, thành phố..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn-auth-submit" style="width:auto; padding:0 var(--sp-6); margin-top:var(--sp-3);">
                    Lưu thay đổi
                </button>
            </div>
        </form>

        <?php elseif ($tab === 'password'): ?>
        <!-- === PASSWORD === -->
        <form method="POST" class="account-form-card">
            <input type="hidden" name="form_action" value="change_password">
            <h2 class="account-section-title">Đổi mật khẩu</h2>

            <div class="form-group">
                <label for="old_password">Mật khẩu hiện tại *</label>
                <input type="password" id="old_password" name="old_password"
                       placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label for="new_password">Mật khẩu mới * <small style="color:var(--clr-text-tertiary);">(tối thiểu 6 ký tự)</small></label>
                <input type="password" id="new_password" name="new_password"
                       placeholder="••••••••" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="new_password_confirm">Xác nhận mật khẩu mới *</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm"
                       placeholder="••••••••" minlength="6" required>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn-auth-submit" style="width:auto; padding:0 var(--sp-6); margin-top:var(--sp-3);">
                    Lưu thay đổi
                </button>
            </div>
        </form>

        <?php elseif ($tab === 'orders'): ?>
        <!-- === ORDERS === -->
        <div class="account-form-card">
            <h2 class="account-section-title">Lịch sử đơn hàng</h2>

            <?php if (empty($orders)): ?>
                <div style="text-align:center;padding:var(--sp-8) 0;color:var(--clr-text-tertiary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48" style="margin:0 auto var(--sp-3);opacity:.4;display:block;">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <p>Bạn chưa có đơn hàng nào.</p>
                    <a href="products.php" style="color:var(--clr-brand);font-weight:600;display:inline-block;margin-top:var(--sp-2);">Mua sắm ngay →</a>
                </div>
            <?php else: ?>
                <div class="orders-table-wrap">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o):
                                $statusMap = [
                                    'pending'   => ['Chờ xác nhận', 'badge-pending'],
                                    'confirmed' => ['Đã xác nhận',  'badge-confirmed'],
                                    'shipping'  => ['Đang giao',    'badge-shipping'],
                                    'delivered' => ['Đã giao',      'badge-delivered'],
                                    'cancelled' => ['Đã hủy',       'badge-cancelled'],
                                ];
                                $s = $statusMap[$o['status']] ?? ['Không rõ', 'badge-pending'];
                            ?>
                            <tr>
                                <td><strong>#<?= $o['id'] ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td style="font-weight:700;color:var(--clr-brand);"><?= formatPrice($o['total']) ?></td>
                                <td><span class="order-badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                                <td><a href="order_detail.php?id=<?= $o['id'] ?>" class="order-detail-link">Xem →</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
