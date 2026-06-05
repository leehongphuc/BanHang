<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];

$voucher = [
    'code' => '',
    'category_id' => 0,
    'discount_type' => 'percent',
    'discount_value' => '',
    'min_order_value' => 0,
    'usage_limit' => 0,
    'end_date' => '',
    'is_active' => 1
];

$categories = $conn->query("SELECT id, name FROM categories ORDER BY id");

if ($isEdit) {
    $res = $conn->query("SELECT * FROM vouchers WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $voucher = $res->fetch_assoc();
    } else {
        header('Location: vouchers.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $category_id = (int)($_POST['category_id'] ?? 0);
    $discount_type = $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percent';
    $discount_value = (float)str_replace(',', '', $_POST['discount_value'] ?? '0');
    $min_order_value = (float)str_replace(',', '', $_POST['min_order_value'] ?? '0');
    $usage_limit = (int)($_POST['usage_limit'] ?? 0);
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$code) $errors[] = 'Vui lòng nhập mã giảm giá.';
    if ($discount_value <= 0) $errors[] = 'Mức giảm giá phải lớn hơn 0.';
    if ($discount_type === 'percent' && $discount_value > 100) {
        $errors[] = 'Mức giảm theo phần trăm không được vượt quá 100%.';
    }

    // Check unique code
    $ecode = $conn->real_escape_string($code);
    $check = $conn->query("SELECT id FROM vouchers WHERE code = '$ecode' AND id != $id");
    if ($check && $check->num_rows > 0) {
        $errors[] = 'Mã giảm giá này đã tồn tại, vui lòng nhập mã khác.';
    }

    if (empty($errors)) {
        $endSql = $end_date ? "'$end_date'" : "NULL";
        $catSql = $category_id > 0 ? $category_id : "NULL";
        if ($isEdit) {
            $conn->query("UPDATE vouchers SET 
                code='$ecode', category_id=$catSql, discount_type='$discount_type', discount_value=$discount_value, 
                min_order_value=$min_order_value, usage_limit=$usage_limit, end_date=$endSql, is_active=$is_active
                WHERE id=$id");
            $_SESSION['admin_msg'] = 'Cập nhật mã giảm giá thành công!';
        } else {
            $conn->query("INSERT INTO vouchers (code, category_id, discount_type, discount_value, min_order_value, usage_limit, end_date, is_active)
                          VALUES ('$ecode', $catSql, '$discount_type', $discount_value, $min_order_value, $usage_limit, $endSql, $is_active)");
            $_SESSION['admin_msg'] = 'Thêm mã giảm giá thành công!';
        }
        $_SESSION['admin_msg_type'] = 'success';
        header('Location: vouchers.php');
        exit;
    }
    
    $voucher = array_merge($voucher, compact('code', 'category_id', 'discount_type', 'discount_value', 'min_order_value', 'usage_limit', 'end_date', 'is_active'));
}

adminLayout($isEdit ? 'Sửa Mã giảm giá' : 'Thêm Mã giảm giá', 'vouchers', function() use ($voucher, $errors, $isEdit, $categories) { ?>

<div class="admin-header">
    <a href="vouchers.php" class="back-link">← Quay lại</a>
    <h1 class="admin-title"><?= $isEdit ? 'Sửa Mã Giảm Giá' : 'Thêm Mã Giảm Giá' ?></h1>
</div>

<?php if(!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left:20px;">
            <?php foreach($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" class="card" style="max-width:800px;">
    <div class="card-body">
        
        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label for="code">Mã Voucher (Code) *</label>
                <input type="text" id="code" name="code" value="<?= htmlspecialchars($voucher['code']) ?>" 
                       placeholder="VD: FREESHIP, GIAM10K" style="text-transform:uppercase;" required>
            </div>
            <div class="admin-form-group">
                <label for="category_id">Áp dụng cho danh mục</label>
                <select id="category_id" name="category_id">
                    <option value="0">Tất cả danh mục</option>
                    <?php if(isset($categories) && $categories): $categories->data_seek(0); while($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $voucher['category_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
                <p class="admin-form-hint">Mã chỉ áp dụng khi giỏ hàng có sản phẩm thuộc danh mục này.</p>
            </div>
        </div>

        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label for="discount_type">Loại giảm giá</label>
                <select id="discount_type" name="discount_type">
                    <option value="percent" <?= $voucher['discount_type'] === 'percent' ? 'selected' : '' ?>>Theo phần trăm (%)</option>
                    <option value="fixed" <?= $voucher['discount_type'] === 'fixed' ? 'selected' : '' ?>>Số tiền cố định (VNĐ)</option>
                </select>
            </div>
            <div class="admin-form-group">
                <label for="discount_value">Mức giảm *</label>
                <input type="number" id="discount_value" name="discount_value" 
                       value="<?= (int)$voucher['discount_value'] ?>" min="1" required>
            </div>
        </div>

        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label for="min_order_value">Đơn hàng tối thiểu (VNĐ)</label>
                <input type="number" id="min_order_value" name="min_order_value" 
                       value="<?= (int)$voucher['min_order_value'] ?>" min="0">
                <p class="admin-form-hint">Để 0 nếu áp dụng cho mọi đơn hàng.</p>
            </div>
            <div class="admin-form-group">
                <label for="usage_limit">Lượt sử dụng tối đa</label>
                <input type="number" id="usage_limit" name="usage_limit" 
                       value="<?= (int)$voucher['usage_limit'] ?>" min="0">
                <p class="admin-form-hint">Để 0 nếu không giới hạn.</p>
            </div>
        </div>

        <div class="admin-form-group">
            <label for="end_date">Ngày hết hạn</label>
            <input type="datetime-local" id="end_date" name="end_date" 
                   value="<?= $voucher['end_date'] ? date('Y-m-d\TH:i', strtotime($voucher['end_date'])) : '' ?>">
            <p class="admin-form-hint">Bỏ trống nếu mã không có hạn.</p>
        </div>

        <div class="admin-form-group" style="display:flex; align-items:center; gap:10px;">
            <input type="checkbox" id="is_active" name="is_active" value="1" 
                   <?= $voucher['is_active'] ? 'checked' : '' ?> style="width:auto;">
            <label for="is_active" style="margin:0;">Kích hoạt mã này</label>
        </div>

    </div>
    <div class="card-footer" style="text-align:right;">
        <button type="submit" class="btn btn-primary">Lưu Mã Giảm Giá</button>
    </div>
</form>

<?php }); ?>
