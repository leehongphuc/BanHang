<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$msg     = $_SESSION['admin_msg'] ?? '';
$msgType = $_SESSION['admin_msg_type'] ?? 'success';
unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']);

$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;

$statusList = ['pending','confirmed','shipping','delivered','cancelled'];
$statusLabels = [
    'pending'   => ['Chờ xác nhận', 'badge-pending'],
    'confirmed' => ['Đã xác nhận',  'badge-confirmed'],
    'shipping'  => ['Đang giao',    'badge-shipping'],
    'delivered' => ['Đã giao',      'badge-delivered'],
    'cancelled' => ['Đã hủy',       'badge-cancelled'],
];

$where = '1=1';
if ($statusFilter && in_array($statusFilter, $statusList)) {
    $where .= " AND o.status = '$statusFilter'";
}
if ($search) {
    $es = $conn->real_escape_string($search);
    $where .= " AND (o.id = '$es' OR o.receiver_name LIKE '%$es%' OR o.receiver_phone LIKE '%$es%')";
}

$total      = (int)$conn->query("SELECT COUNT(*) FROM orders o WHERE $where")->fetch_row()[0];
$totalPages = max(1, (int)ceil($total / $perPage));
$offset     = ($page - 1) * $perPage;

$orders = $conn->query(
    "SELECT o.*, COALESCE(u.fullname, o.receiver_name) as customer_name
     FROM orders o LEFT JOIN users u ON o.user_id = u.id
     WHERE $where ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"
);

// Count per status
$counts = [];
foreach ($statusList as $st) {
    $counts[$st] = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status='$st'")->fetch_row()[0];
}
$counts[''] = (int)$conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];

adminLayout('Quản lý Đơn hàng', 'orders', function() use (
    $orders, $statusFilter, $search, $page, $totalPages, $total, $perPage,
    $statusLabels, $statusList, $counts, $msg, $msgType
) { ?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Status tabs -->
<div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;">
    <?php
    $tabs = ['' => 'Tất cả'] + array_map(fn($s) => $s[0], $statusLabels);
    foreach ($tabs as $val => $label):
        $isActive = ($statusFilter === $val);
        $cnt      = $counts[$val] ?? 0;
    ?>
    <a href="?status=<?= $val ?>&q=<?= urlencode($search) ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;
              background:<?= $isActive ? 'var(--brand)' : 'white' ?>;
              color:<?= $isActive ? 'white' : 'var(--text-2)' ?>;
              border:1.5px solid <?= $isActive ? 'var(--brand)' : 'var(--border)' ?>;">
        <?= $label ?>
        <span style="background:<?= $isActive ? 'rgba(255,255,255,.25)' : 'var(--surface-2)' ?>;
                     color:<?= $isActive ? 'white' : 'var(--text-3)' ?>;
                     padding:1px 6px;border-radius:99px;font-size:11px;"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="filter-bar">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <input type="search" name="q" placeholder="Mã đơn, tên, SĐT..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-ghost btn-sm">Tìm</button>
            <?php if ($search): ?>
                <a href="?status=<?= $statusFilter ?>" class="btn btn-ghost btn-sm">✕ Xóa lọc</a>
            <?php endif; ?>
        </form>
        <span style="font-size:13px;color:var(--text-3);"><?= $total ?> đơn hàng</span>
    </div>

    <div class="card-body p0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Ngày đặt</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders->num_rows === 0): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <p>Không có đơn hàng nào.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php while ($o = $orders->fetch_assoc()):
                        $s = $statusLabels[$o['status']] ?? ['Không rõ', 'badge-pending'];
                    ?>
                    <tr>
                        <td><strong>#<?= $o['id'] ?></strong></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td style="color:var(--text-2);"><?= htmlspecialchars($o['receiver_phone']) ?></td>
                        <td style="font-weight:700;"><?= number_format((float)$o['total'], 0, ',', '.') ?>₫</td>
                        <td><?= $o['payment_method'] === 'COD' ? 'COD' : 'Online' ?></td>
                        <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                        <td style="color:var(--text-3);font-size:12px;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        <td>
                            <a href="order_detail.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Chi tiết →</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="padding:14px 24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:13px;color:var(--text-3);">Trang <?= $page ?>/<?= $totalPages ?></span>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>&q=<?= urlencode($search) ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php }); ?>
