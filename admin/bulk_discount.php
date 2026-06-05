<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $discount_pct = (int)($_POST['discount_percent'] ?? 0);
    $discount_end = !empty($_POST['discount_end']) ? $_POST['discount_end'] : null;
    
    if ($discount_pct < 0 || $discount_pct > 100) {
        $msg = "Phần trăm giảm giá phải từ 0 đến 100.";
        $msgType = "error";
    } else {
        $where = $category_id > 0 ? "category_id = $category_id" : "1=1";
        
        if ($discount_pct === 0) {
            // Restore original price if discount is 0
            $conn->query("UPDATE products SET price = IFNULL(old_price, price), old_price = NULL, discount_end = NULL WHERE $where");
            $msg = "Đã hủy bỏ giảm giá cho các sản phẩm đã chọn.";
        } else {
            // Apply discount
            // 1. If old_price is NULL, move price to old_price
            $conn->query("UPDATE products SET old_price = price WHERE old_price IS NULL AND $where");
            // 2. Set new price based on old_price
            $factor = 1 - ($discount_pct / 100);
            $endSql = $discount_end ? "'$discount_end'" : "NULL";
            $conn->query("UPDATE products SET price = ROUND(old_price * $factor), discount_end = $endSql WHERE $where");
            $msg = "Đã áp dụng giảm giá $discount_pct% thành công.";
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

adminLayout('Giảm giá hàng loạt', 'products', function() use ($categories, $msg, $msgType) { ?>

<div style="max-width:600px;">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Thiết lập giảm giá theo danh mục</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-3); font-size:14px; margin-bottom:20px;">
                Công cụ này giúp bạn thiết lập giảm giá nhanh cho toàn bộ sản phẩm thuộc một danh mục. <br>
                Hệ thống sẽ lấy <b>Giá gốc</b> làm chuẩn và tính ra <b>Giá bán</b> mới. <br>
                <i>Lưu ý: Nhập 0% để hủy giảm giá và quay về giá gốc.</i>
            </p>

            <form method="POST">
                <div class="admin-form-group">
                    <label for="category_id">Danh mục áp dụng</label>
                    <select id="category_id" name="category_id">
                        <option value="0">Tất cả danh mục (Toàn bộ cửa hàng)</option>
                        <?php $categories->data_seek(0); while($c = $categories->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="discount_percent">Mức giảm giá (%)</label>
                    <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" required placeholder="Ví dụ: 10">
                </div>

                <div class="admin-form-group">
                    <label for="discount_end">Ngày kết thúc (Không bắt buộc)</label>
                    <input type="datetime-local" id="discount_end" name="discount_end">
                    <p style="font-size:12px; color:var(--text-3); margin-top:5px;">Hệ thống sẽ tự động khôi phục giá gốc khi quá thời hạn này.</p>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Bạn có chắc chắn muốn thay đổi giá hàng loạt không?')">
                        Áp dụng giảm giá
                    </button>
                    <a href="products.php" class="btn btn-ghost" style="margin-left:10px;">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php }); ?>
