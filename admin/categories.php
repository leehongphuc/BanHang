<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$msg     = $_SESSION['admin_msg'] ?? '';
$msgType = $_SESSION['admin_msg_type'] ?? 'success';
unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']);

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cat'])) {
    $name = trim($_POST['cat_name'] ?? '');
    if ($name) {
        $en = $conn->real_escape_string($name);
        $conn->query("INSERT INTO categories (name) VALUES ('$en')");
        $_SESSION['admin_msg'] = "Đã thêm danh mục \"$name\".";
        header('Location: categories.php'); exit;
    }
}

// Rename
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_cat'])) {
    $rid  = (int)$_POST['cat_id'];
    $rname = trim($_POST['cat_name'] ?? '');
    if ($rid && $rname) {
        $en = $conn->real_escape_string($rname);
        $conn->query("UPDATE categories SET name='$en' WHERE id=$rid");
        $_SESSION['admin_msg'] = 'Đã cập nhật danh mục.';
        header('Location: categories.php'); exit;
    }
}

// Delete
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    
    // 1. Kiểm tra xem có phải đang xóa danh mục "Khác" không
    $checkRes = $conn->query("SELECT name FROM categories WHERE id=$did")->fetch_assoc();
    if ($checkRes && $checkRes['name'] === 'Khác') {
        $_SESSION['admin_msg'] = "Không thể xóa danh mục mặc định 'Khác'!";
        $_SESSION['admin_msg_type'] = 'error';
    } else {
        // 2. Tìm hoặc tạo danh mục "Khác" để làm nơi chứa sản phẩm mồ côi
        $otherCat = $conn->query("SELECT id FROM categories WHERE name='Khác' LIMIT 1")->fetch_assoc();
        if (!$otherCat) {
            $conn->query("INSERT INTO categories (name) VALUES ('Khác')");
            $otherId = $conn->insert_id;
        } else {
            $otherId = (int)$otherCat['id'];
        }

        // 3. Chuyển sản phẩm sang danh mục "Khác"
        $conn->query("UPDATE products SET category_id=$otherId WHERE category_id=$did");
        
        // 4. Xóa danh mục cũ
        $conn->query("DELETE FROM categories WHERE id=$did");
        $_SESSION['admin_msg'] = "Đã xóa danh mục. Sản phẩm cũ đã được chuyển sang danh mục 'Khác'.";
        $_SESSION['admin_msg_type'] = 'success';
    }
    header('Location: categories.php'); exit;
}

$categories = $conn->query(
    "SELECT c.*, COUNT(p.id) as product_count
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name"
);

adminLayout('Quản lý Danh mục', 'categories', function() use ($categories, $msg, $msgType) { ?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start;">

    <!-- Add category -->
    <div class="card">
        <div class="card-header"><span class="card-title">Thêm danh mục mới</span></div>
        <div class="card-body">
            <form method="POST">
                <div class="admin-form-group">
                    <label for="cat_name">Tên danh mục *</label>
                    <input type="text" id="cat_name" name="cat_name"
                           placeholder="VD: Máy tính bảng" required>
                </div>
                <button type="submit" name="add_cat" class="btn btn-primary" style="width:100%;">
                    + Thêm danh mục
                </button>
            </form>
        </div>
    </div>

    <!-- Category list -->
    <div class="card">
        <div class="card-header"><span class="card-title">Danh sách danh mục</span></div>
        <div class="card-body p0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên danh mục</th>
                        <th>Số sản phẩm</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                    <tr id="cat-row-<?= $c['id'] ?>">
                        <td style="color:var(--text-3);">#<?= $c['id'] ?></td>
                        <td>
                            <form method="POST" style="display:flex;gap:8px;align-items:center;">
                                <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                                <input type="text" name="cat_name"
                                       value="<?= htmlspecialchars($c['name']) ?>"
                                       style="flex:1;height:34px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                                <button type="submit" name="rename_cat" class="btn btn-success btn-sm">✓ Lưu</button>
                            </form>
                        </td>
                        <td>
                            <span class="badge <?= $c['product_count'] > 0 ? 'badge-confirmed' : 'badge-user' ?>">
                                <?= $c['product_count'] ?> SP
                            </span>
                        </td>
                        <td style="color:var(--text-3);"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <a href="categories.php?delete=<?= $c['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Xóa danh mục <?= htmlspecialchars(addslashes($c['name'])) ?>?')">
                                🗑 Xóa
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php }); ?>
