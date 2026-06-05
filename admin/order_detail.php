<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: orders.php'); exit; }

$order = $conn->query(
    "SELECT o.*, COALESCE(u.fullname, o.receiver_name) as customer_name, u.email
     FROM orders o LEFT JOIN users u ON o.user_id = u.id
     WHERE o.id = $id"
)->fetch_assoc();

if (!$order) { header('Location: orders.php'); exit; }

$msg = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];
    $currentStatus = $order['status'];
    
    // Logic cho phép chuyển trạng thái theo đúng luồng
    $allowedTransitions = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'cancelled'],
        'shipping'  => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => []
    ];
    
    if (in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
        $conn->query("UPDATE orders SET status='$newStatus' WHERE id=$id");
        $_SESSION['admin_msg']      = 'Cập nhật trạng thái thành công!';
        $_SESSION['admin_msg_type'] = 'success';
    } else {
        $_SESSION['admin_msg']      = 'Thao tác không hợp lệ với trạng thái hiện tại!';
        $_SESSION['admin_msg_type'] = 'error';
    }
    header("Location: order_detail.php?id=$id");
    exit;
}

// Load items
$items = $conn->query(
    "SELECT oi.*, p.name as product_name, p.image
     FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = $id"
);

$statusLabels = [
    'pending'   => ['Chờ xác nhận', 'badge-pending'],
    'confirmed' => ['Đã xác nhận',  'badge-confirmed'],
    'shipping'  => ['Đang giao',    'badge-shipping'],
    'delivered' => ['Đã giao',      'badge-delivered'],
    'cancelled' => ['Đã hủy',       'badge-cancelled'],
];
$s = $statusLabels[$order['status']] ?? ['Không rõ', 'badge-pending'];
$msg     = $_SESSION['admin_msg'] ?? '';
$msgType = $_SESSION['admin_msg_type'] ?? 'success';
unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']);

adminLayout("Đơn hàng #$id", 'orders', function() use ($order, $items, $statusLabels, $s, $id, $msg, $msgType) { ?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>">✓ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

    <!-- Order items -->
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">Sản phẩm trong đơn</span>
                <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
            </div>
            <div class="card-body p0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="/BanHang/assets/images/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>"
                                     alt="" class="td-img">
                            </td>
                            <td><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm đã xóa') ?></td>
                            <td><?= number_format((float)$item['price'], 0, ',', '.') ?>₫</td>
                            <td><?= $item['quantity'] ?></td>
                            <td style="font-weight:700;">
                                <?= number_format((float)$item['price'] * (int)$item['quantity'], 0, ',', '.') ?>₫
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;font-weight:700;padding:16px 20px;">Tổng cộng:</td>
                            <td style="font-weight:800;font-size:16px;color:var(--brand);padding:16px 20px;">
                                <?= number_format((float)$order['total'], 0, ',', '.') ?>₫
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Customer info -->
        <div class="card">
            <div class="card-header"><span class="card-title">Thông tin giao hàng</span></div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <?php $rows = [
                        'Người nhận'    => $order['receiver_name'],
                        'Số điện thoại' => $order['receiver_phone'],
                        'Địa chỉ'       => $order['receiver_address'],
                        'Thanh toán'    => $order['payment_method'] === 'COD' ? 'COD — Thanh toán khi nhận' : 'Thanh toán online',
                        'Tài khoản'     => $order['email'] ?? 'Khách vãng lai',
                        'Ngày đặt'      => date('d/m/Y H:i:s', strtotime($order['created_at'])),
                    ];
                    foreach ($rows as $label => $val): ?>
                    <tr>
                        <td style="padding:8px 0;font-weight:600;color:var(--text-2);width:140px;font-size:13px;"><?= $label ?></td>
                        <td style="padding:8px 0;font-size:13px;"><?= htmlspecialchars($val ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Status update -->
    <div class="card">
        <div class="card-header"><span class="card-title">Cập nhật trạng thái</span></div>
        <div class="card-body">
            <?php if (in_array($order['status'], ['delivered', 'cancelled'])): ?>
                <div style="padding:12px 16px; border-radius:6px; background:var(--surface-2); color:var(--text-2); font-size:14px; text-align:center; font-weight:600;">
                    🔒 Đơn hàng đã <?= $order['status'] === 'delivered' ? 'giao thành công' : 'hủy' ?>. Không thể thay đổi trạng thái.
                </div>
            <?php else: ?>
                <form method="POST">
                    <div style="display:flex; gap:12px; margin-bottom:16px;">
                        <?php if ($order['status'] === 'pending'): ?>
                            <button type="submit" name="status" value="confirmed" class="btn btn-primary" style="flex:1;" onclick="return confirm('Xác nhận bắt đầu xử lý đơn hàng này?');">✓ Xác nhận đơn</button>
                        <?php elseif ($order['status'] === 'confirmed'): ?>
                            <button type="submit" name="status" value="shipping" class="btn btn-primary" style="flex:1;" onclick="return confirm('Chuyển đơn hàng sang trạng thái Đang giao hàng?');">🚚 Giao hàng</button>
                        <?php elseif ($order['status'] === 'shipping'): ?>
                            <button type="submit" name="status" value="delivered" class="btn btn-success" style="flex:1;" onclick="return confirm('Xác nhận khách hàng đã nhận được hàng và thanh toán?');">📦 Đã giao hàng</button>
                        <?php endif; ?>
                        
                        <button type="submit" name="status" value="cancelled" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">✕ Hủy đơn</button>
                    </div>
                </form>
            <?php endif; ?>

                <!-- Status flow visual -->
                <div style="margin:16px 0;padding:16px;background:var(--surface-2);border-radius:8px;">
                    <?php
                    $flow = ['pending','confirmed','shipping','delivered'];
                    $flowLabels = ['Chờ xác nhận','Xác nhận','Đang giao','Hoàn thành'];
                    $curIdx = array_search($order['status'], $flow);
                    ?>
                    <div style="display:flex;align-items:center;gap:4px;">
                    <?php foreach ($flow as $i => $st): ?>
                        <div style="display:flex;align-items:center;flex:1;min-width:0;">
                            <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;
                                        background:<?= ($curIdx !== false && $i <= $curIdx) ? 'var(--brand)' : 'var(--border)' ?>;
                                        color:<?= ($curIdx !== false && $i <= $curIdx) ? 'white' : 'var(--text-3)' ?>;">
                                <?= $i + 1 ?>
                            </div>
                            <?php if ($i < count($flow) - 1): ?>
                            <div style="flex:1;height:2px;background:<?= ($curIdx !== false && $i < $curIdx) ? 'var(--brand)' : 'var(--border)' ?>;"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <div style="display:flex;gap:4px;margin-top:6px;">
                    <?php foreach ($flow as $i => $st): ?>
                        <div style="flex:1;text-align:center;font-size:10px;color:<?= ($curIdx !== false && $i <= $curIdx) ? 'var(--brand)' : 'var(--text-3)' ?>;font-weight:600;">
                            <?= $flowLabels[$i] ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>



            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                <a href="orders.php" class="btn btn-ghost" style="width:100%;">← Về danh sách đơn hàng</a>
            </div>
        </div>
    </div>

</div>

<?php }); ?>
