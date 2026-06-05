<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$msg     = $_SESSION['admin_msg'] ?? '';
$msgType = $_SESSION['admin_msg_type'] ?? 'success';
unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']);

// Toggle lock/unlock via AJAX or GET
if (isset($_GET['toggle'])) {
    $tid  = (int)$_GET['toggle'];
    // Cannot lock other admins
    $target = $conn->query("SELECT role, is_active FROM users WHERE id=$tid")->fetch_assoc();
    if ($target && $target['role'] !== 'admin') {
        $newActive = $target['is_active'] ? 0 : 1;
        $conn->query("UPDATE users SET is_active=$newActive WHERE id=$tid");
        if ($_SERVER['HTTP_ACCEPT'] ?? '' === 'application/json') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'is_active' => $newActive]);
            exit;
        }
        $_SESSION['admin_msg'] = $newActive ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.';
    } else {
        $_SESSION['admin_msg']      = 'Không thể khóa tài khoản Admin.';
        $_SESSION['admin_msg_type'] = 'error';
    }
    header('Location: users.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$role   = $_GET['role'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = "role != 'admin'"; // Luôn lọc bỏ admin
if ($search) {
    $es = $conn->real_escape_string($search);
    $where .= " AND (fullname LIKE '%$es%' OR email LIKE '%$es%')";
}
if ($role) {
    $er = $conn->real_escape_string($role);
    $where .= " AND role = '$er'";
}

$total      = (int)$conn->query("SELECT COUNT(*) FROM users WHERE $where")->fetch_row()[0];
$totalPages = max(1, (int)ceil($total / $perPage));
$offset     = ($page - 1) * $perPage;

$users = $conn->query(
    "SELECT *, (SELECT COUNT(*) FROM orders WHERE user_id = users.id) as order_count
     FROM users WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
);

adminLayout('Quản lý Người dùng', 'users', function() use (
    $users, $search, $role, $page, $totalPages, $total, $msg, $msgType
) { ?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <form method="GET" class="filter-bar">
            <input type="search" name="q" placeholder="Tìm tên, email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-ghost btn-sm">Tìm</button>
            <?php if ($search): ?>
                <a href="users.php" class="btn btn-ghost btn-sm">✕ Xóa lọc</a>
            <?php endif; ?>
        </form>
        <span style="font-size:13px;color:var(--text-3);"><?= $total ?> người dùng</span>
    </div>

    <div class="card-body p0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Người dùng</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Đơn hàng</th>
                    <th>Ngày đăng ký</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows === 0): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <p>Không tìm thấy người dùng nào.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php while ($u = $users->fetch_assoc()):
                        $initial = mb_strtoupper(mb_substr($u['fullname'], 0, 1));
                    ?>
                    <tr id="user-row-<?= $u['id'] ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;border-radius:50%;background:var(--brand);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                                    <?= $initial ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;"><?= htmlspecialchars($u['fullname']) ?></div>
                                    <div style="font-size:12px;color:var(--text-3);">ID: <?= $u['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-2);"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="color:var(--text-2);"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                        <td>
                            <?php if ($u['order_count'] > 0): ?>
                                <a href="orders.php" style="font-weight:700;color:var(--brand);text-decoration:none;">
                                    <?= $u['order_count'] ?> đơn
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-3);">0</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-3);font-size:12px;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-locked' ?>" id="badge-<?= $u['id'] ?>">
                                <?= $u['is_active'] ? 'Hoạt động' : 'Đã khóa' ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="toggleUser(<?= $u['id'] ?>, <?= $u['is_active'] ?>)"
                                    id="btn-<?= $u['id'] ?>"
                                    class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                                <?= $u['is_active'] ? '🔒 Khóa' : '🔓 Mở' ?>
                            </button>
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
                <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleUser(uid, currentActive) {
    if (!confirm(currentActive ? 'Khóa tài khoản này?' : 'Mở khóa tài khoản này?')) return;

    fetch('/BanHang/admin/users.php?toggle=' + uid, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('badge-' + uid);
            const btn   = document.getElementById('btn-' + uid);
            if (data.is_active) {
                badge.textContent = 'Hoạt động';
                badge.className = 'badge badge-active';
                btn.textContent = '🔒 Khóa';
                btn.className = 'btn btn-sm btn-danger';
                btn.onclick = () => toggleUser(uid, 1);
            } else {
                badge.textContent = 'Đã khóa';
                badge.className = 'badge badge-locked';
                btn.textContent = '🔓 Mở';
                btn.className = 'btn btn-sm btn-success';
                btn.onclick = () => toggleUser(uid, 0);
            }
        }
    })
    .catch(() => window.location.href = '/BanHang/admin/users.php?toggle=' + uid);
}
</script>

<?php }); ?>
