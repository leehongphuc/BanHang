<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$msg      = $_SESSION['admin_msg'] ?? '';
$msgType  = $_SESSION['admin_msg_type'] ?? 'success';
unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']);

// Delete product
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $did");
    $conn->query("DELETE FROM product_variants WHERE product_id = $did");
    $conn->query("DELETE FROM product_specifications WHERE product_id = $did");
    $conn->query("DELETE FROM order_items WHERE product_id = $did");
    $_SESSION['admin_msg']      = 'Đã xóa sản phẩm.';
    $_SESSION['admin_msg_type'] = 'success';
    header('Location: products.php'); exit;
}

// Filters
$search   = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;

$where = '1=1';
if ($search)    $where .= " AND p.name LIKE '%" . $conn->real_escape_string($search) . "%'";
if ($catFilter) $where .= " AND p.category_id = $catFilter";

$total = (int)$conn->query("SELECT COUNT(*) FROM products p WHERE $where")->fetch_row()[0];
$totalPages = (int)ceil($total / $perPage);
$offset  = ($page - 1) * $perPage;

$products   = $conn->query(
    "SELECT p.*, c.name as cat_name, b.name as brand_name FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset"
);
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

adminLayout('Quản lý Sản phẩm', 'products', function() use (
    $products, $categories, $search, $catFilter, $page, $totalPages, $total, $perPage, $msg, $msgType
) { ?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>">
        <?= $msgType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <!-- Filter -->
        <form method="GET" class="filter-bar">
            <input type="search" name="q" placeholder="Tìm tên sản phẩm..." value="<?= htmlspecialchars($search) ?>">
            <select name="cat" onchange="this.form.submit()">
                <option value="">Tất cả danh mục</option>
                <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-ghost btn-sm">Tìm</button>
            <?php if ($search || $catFilter): ?>
                <a href="products.php" class="btn btn-ghost btn-sm">✕ Xóa lọc</a>
            <?php endif; ?>
        </form>

        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:13px;color:var(--text-3);"><?= $total ?> sản phẩm</span>

            <a href="product_form.php" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Thêm sản phẩm
            </a>
        </div>
    </div>

    <div class="card-body p0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Thương hiệu</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Tồn kho</th>
                    <th>Nổi bật</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products->num_rows === 0): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            <p>Không tìm thấy sản phẩm nào.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="/BanHang/assets/images/<?= htmlspecialchars($p['image'] ?: 'placeholder.jpg') ?>"
                                 alt="" class="td-img">
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($p['name']) ?></strong>
                            <div style="font-size:12px;color:var(--text-3);margin-top:2px;">ID: <?= $p['id'] ?></div>
                        </td>
                        <td><?= htmlspecialchars($p['brand_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
                        <td style="font-weight:700;"><?= number_format((float)$p['price'], 0, ',', '.') ?>₫</td>
                        <td>
                            <?php if ((int)$p['stock'] === 0): ?>
                                <span class="badge badge-cancelled">Hết hàng</span>
                            <?php elseif ((int)$p['stock'] < 10): ?>
                                <span class="badge badge-pending"><?= $p['stock'] ?></span>
                            <?php else: ?>
                                <span style="font-weight:600;"><?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['is_featured'] ? '⭐' : '—' ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">✏ Sửa</a>
                                <a href="products.php?delete=<?= $p['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Xóa sản phẩm <?= htmlspecialchars(addslashes($p['name'])) ?>?')">🗑 Xóa</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:13px;color:var(--text-3);">
            Trang <?= $page ?>/<?= $totalPages ?>
        </span>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php }); ?>
