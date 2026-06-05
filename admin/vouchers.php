<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM vouchers WHERE id = $id");
    $_SESSION['admin_msg'] = "Đã xóa mã giảm giá thành công!";
    $_SESSION['admin_msg_type'] = "success";
    header('Location: vouchers.php');
    exit;
}

// Fetch vouchers
$result = $conn->query("SELECT * FROM vouchers ORDER BY id DESC");
$vouchers = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }
}

adminLayout('Quản lý Mã giảm giá', 'vouchers', function() use ($vouchers) { ?>

<div class="admin-header">
    <h1 class="admin-title">Quản lý Mã giảm giá (Vouchers)</h1>
</div>

<?php if (isset($_SESSION['admin_msg'])): ?>
    <div class="alert alert-<?= $_SESSION['admin_msg_type'] ?>">
        <?= htmlspecialchars($_SESSION['admin_msg']) ?>
    </div>
    <?php unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <span class="card-title">Danh sách Mã giảm giá</span>
        <a href="voucher_form.php" class="btn btn-primary">
            + Thêm mã mới
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Loại</th>
                    <th>Mức giảm</th>
                    <th>Đơn tối thiểu</th>
                    <th>Đã dùng / Tổng</th>
                    <th>Hạn sử dụng</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($vouchers)): ?>
                    <tr><td colspan="8" style="text-align:center;">Chưa có mã giảm giá nào</td></tr>
                <?php else: ?>
                    <?php foreach($vouchers as $v): 
                        $isExpired = $v['end_date'] && strtotime($v['end_date']) < time();
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($v['code']) ?></strong></td>
                        <td><?= $v['discount_type'] === 'percent' ? 'Giảm %' : 'Giảm tiền' ?></td>
                        <td style="color:var(--clr-error); font-weight:bold;">
                            <?= $v['discount_type'] === 'percent' ? $v['discount_value'].'%' : number_format($v['discount_value']).'đ' ?>
                        </td>
                        <td><?= number_format($v['min_order_value']) ?>đ</td>
                        <td>
                            <?= $v['used_count'] ?> / <?= $v['usage_limit'] > 0 ? $v['usage_limit'] : '∞' ?>
                        </td>
                        <td>
                            <?php if ($v['end_date']): ?>
                                <?= date('d/m/Y', strtotime($v['end_date'])) ?>
                                <?= $isExpired ? '<span style="color:var(--clr-error); font-size:12px;">(Hết hạn)</span>' : '' ?>
                            <?php else: ?>
                                Vô thời hạn
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($v['is_active']): ?>
                                <span class="badge" style="background:var(--clr-success-light);color:var(--clr-success);">Hoạt động</span>
                            <?php else: ?>
                                <span class="badge" style="background:var(--clr-text-tertiary);color:white;">Đã tắt</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="voucher_form.php?id=<?= $v['id'] ?>" class="btn-icon" title="Sửa">✏️</a>
                            <a href="vouchers.php?delete=<?= $v['id'] ?>" class="btn-icon text-error" title="Xóa" onclick="return confirm('Bạn chắc chắn muốn xóa mã này?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php }); ?>
