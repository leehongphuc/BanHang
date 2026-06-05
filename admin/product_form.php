<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

$id      = (int)($_GET['id'] ?? 0);
$isEdit  = $id > 0;
$product = [];
$errors  = [];

if ($isEdit) {
    $product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
    if (!$product) { header('Location: products.php'); exit; }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$brands     = $conn->query("SELECT * FROM brands ORDER BY name");

// Load existing variants (khi edit)
$existingVersions = [];
$existingColors   = [];
$existingSpecs    = [];
if ($isEdit) {
    $vr = $conn->query("SELECT * FROM product_variants WHERE product_id=$id ORDER BY sort_order,id");
    while ($v = $vr->fetch_assoc()) {
        if ($v['type'] === 'version') $existingVersions[] = $v;
        else                           $existingColors[]   = $v;
    }

    $sr = $conn->query("SELECT * FROM product_specifications WHERE product_id=$id ORDER BY sort_order, id");
    if ($sr) {
        while ($s = $sr->fetch_assoc()) {
            $existingSpecs[] = $s;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $cat         = (int)($_POST['category_id'] ?? 0);
    $brand       = (int)($_POST['brand_id'] ?? 0);
    $price       = (float)str_replace(',', '', $_POST['price'] ?? '0');
    $stock       = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $featured    = isset($_POST['is_featured']) ? 1 : 0;
    $bestseller  = isset($_POST['is_bestseller']) ? 1 : 0;
    $suggested   = isset($_POST['is_suggested']) ? 1 : 0;

    if (!$name)      $errors[] = 'Tên sản phẩm không được để trống.';
    if (!$cat)       $errors[] = 'Vui lòng chọn danh mục.';
    if ($price <= 0) $errors[] = 'Giá phải lớn hơn 0.';

    // Handle image upload
    $imageName = $product['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $file    = $_FILES['image'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Ảnh phải là JPG, PNG, WEBP hoặc GIF.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Ảnh tối đa 5MB.';
        } else {
            $newName = uniqid('prod_') . '.' . $ext;
            $dest    = dirname(__DIR__) . '/assets/images/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $imageName = $newName;
            } else {
                $errors[] = 'Upload ảnh thất bại.';
            }
        }
    }

    if (empty($errors)) {
        $en    = $conn->real_escape_string($name);
        $edesc = $conn->real_escape_string($description);
        $eimg  = $conn->real_escape_string($imageName);

        if ($isEdit) {
            $brandSql = $brand ? "brand_id=$brand," : "brand_id=NULL,";
            $conn->query("UPDATE products SET
                name='$en', category_id=$cat, $brandSql price=$price, stock=$stock,
                description='$edesc', image='$eimg',
                is_featured=$featured, is_bestseller=$bestseller, is_suggested=$suggested
                WHERE id=$id");
        } else {
            $brandVal = $brand ? $brand : "NULL";
            $conn->query("INSERT INTO products
                (name,category_id,brand_id,price,stock,description,image,is_featured,is_bestseller,is_suggested)
                VALUES ('$en',$cat,$brandVal,$price,$stock,'$edesc','$eimg',$featured,$bestseller,$suggested)");
            $id = $conn->insert_id;
        }

        // ---- Save Variants ----
        // Xóa hết variants cũ rồi insert lại
        $conn->query("DELETE FROM product_variants WHERE product_id=$id");

        $vNames  = $_POST['v_name']  ?? [];
        $vPrices = $_POST['v_price'] ?? [];
        $vOrder  = 0;
        foreach ($vNames as $i => $vName) {
            $vName = trim($vName);
            if (!$vName) continue;
            $vPrice = (float)str_replace(',', '', $vPrices[$i] ?? '0');
            $evn    = $conn->real_escape_string($vName);
            $vOrder++;
            $conn->query("INSERT INTO product_variants (product_id,type,name,price,sort_order)
                          VALUES ($id,'version','$evn',$vPrice,$vOrder)");
        }

        $cNames  = $_POST['c_name']  ?? [];
        $cPrices = $_POST['c_price'] ?? [];
        $cHexes  = $_POST['c_hex']   ?? [];
        $cOrder  = 0;
        foreach ($cNames as $i => $cName) {
            $cName = trim($cName);
            if (!$cName) continue;
            $cPrice = (float)str_replace(',', '', $cPrices[$i] ?? '0');
            $cHex   = preg_match('/^#[0-9a-fA-F]{6}$/', $cHexes[$i] ?? '') ? $cHexes[$i] : '#888888';
            $ecn    = $conn->real_escape_string($cName);
            $cOrder++;
            $conn->query("INSERT INTO product_variants (product_id,type,name,price,color_hex,sort_order)
                          VALUES ($id,'color','$ecn',$cPrice,'$cHex',$cOrder)");
        }
        // ---- End Variants ----

        // ---- Save Specifications ----
        $conn->query("DELETE FROM product_specifications WHERE product_id=$id");

        $specGroups = $_POST['spec_group'] ?? [];
        $specNames  = $_POST['spec_name']  ?? [];
        $specValues = $_POST['spec_value'] ?? [];
        $specOrder  = 0;

        foreach ($specNames as $i => $specName) {
            $specName  = trim($specName);
            $specGroup = trim($specGroups[$i] ?? 'Thông số chung');
            $specValue = trim($specValues[$i] ?? '');

            if ($specName === '' && $specValue === '') continue; // Skip empty specs

            $esg = $conn->real_escape_string($specGroup);
            $esn = $conn->real_escape_string($specName);
            $esv = $conn->real_escape_string($specValue);
            $specOrder++;

            $conn->query("INSERT INTO product_specifications (product_id, spec_group, spec_name, spec_value, sort_order)
                          VALUES ($id, '$esg', '$esn', '$esv', $specOrder)");
        }
        // ---- End Specifications ----

        $_SESSION['admin_msg']      = $isEdit ? 'Đã cập nhật sản phẩm thành công!' : 'Đã thêm sản phẩm thành công!';
        $_SESSION['admin_msg_type'] = 'success';
        header('Location: products.php'); exit;
    }

    $product = array_merge($product, compact('name','cat','price','stock','description','featured','bestseller','suggested'));
}

$pageTitle = $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
adminLayout($pageTitle, 'products', function() use (
    $product, $categories, $brands, $errors, $isEdit, $id,
    $existingVersions, $existingColors, $existingSpecs
) { ?>

<div style="max-width:900px;">

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">⚠ <?= implode(' | ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <!-- Basic info -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title">Thông tin cơ bản</span></div>
        <div class="card-body">

            <div class="admin-form-group">
                <label for="name">Tên sản phẩm *</label>
                <input type="text" id="name" name="name"
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                       placeholder="VD: iPhone 15 Pro Max 256GB" required>
            </div>

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="category_id">Danh mục *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($product['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label for="brand_id">Thương hiệu</label>
                    <select id="brand_id" name="brand_id">
                        <option value="">-- Chọn thương hiệu --</option>
                        <?php if(isset($brands) && $brands): $brands->data_seek(0); while ($b = $brands->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>"
                                <?= ($product['brand_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label for="price">Giá bán (₫) *</label>
                    <input type="number" id="price" name="price" min="0" step="1000"
                           value="<?= (int)($product['price'] ?? 0) ?>" required>
                </div>
            </div>



            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="stock">Tồn kho</label>
                    <input type="number" id="stock" name="stock" min="0"
                           value="<?= (int)($product['stock'] ?? 0) ?>">
                </div>
                <div class="admin-form-group">
                    <label>Gắn nhãn</label>
                    <div class="checkbox-group" style="margin-top:8px;">
                        <label class="checkbox-item">
                            <input type="checkbox" name="is_featured" <?= !empty($product['is_featured']) ? 'checked' : '' ?>>
                            <span>⭐ Nổi bật</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="is_bestseller" <?= !empty($product['is_bestseller']) ? 'checked' : '' ?>>
                            <span>🔥 Bán chạy</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="is_suggested" <?= !empty($product['is_suggested']) ? 'checked' : '' ?>>
                            <span>💡 Gợi ý</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="description">Mô tả sản phẩm</label>
                <textarea id="description" name="description" rows="5"
                          placeholder="Mô tả chi tiết về sản phẩm..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Versions -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">🔖 Phiên bản</span>
            <button type="button" class="btn btn-ghost btn-sm" onclick="addVersionRow()">+ Thêm phiên bản</button>
        </div>
        <div class="card-body">
            <p class="admin-form-hint" style="margin-bottom:14px;">VD: 128GB, 256GB · Note 12 Pro 5G, Note 12 Pro 4G. Để giá = 0 thì dùng giá gốc sản phẩm.</p>

            <div style="display:grid;grid-template-columns:1fr 160px 36px;gap:8px;margin-bottom:8px;font-size:12px;font-weight:700;color:var(--text-3);padding:0 4px;">
                <span>Tên phiên bản</span><span>Giá (₫) — 0 = giá gốc</span><span></span>
            </div>

            <div id="version-rows">
                <?php foreach ($existingVersions as $v): ?>
                <div class="variant-row" style="display:grid;grid-template-columns:1fr 160px 36px;gap:8px;margin-bottom:8px;">
                    <input type="text" name="v_name[]" value="<?= htmlspecialchars($v['name']) ?>"
                           placeholder="VD: 256GB" style="height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                    <input type="number" name="v_price[]" value="<?= (int)$v['price'] ?>"
                           min="0" step="1000" placeholder="0"
                           style="height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                    <button type="button" onclick="this.closest('.variant-row').remove()"
                            style="height:36px;width:36px;border:1.5px solid var(--error);background:var(--error-bg);border-radius:6px;cursor:pointer;color:var(--error);font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-ghost btn-sm" onclick="addVersionRow()" style="margin-top:4px;">
                + Thêm phiên bản
            </button>
        </div>
    </div>

    <!-- Colors -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">🎨 Màu sắc</span>
            <button type="button" class="btn btn-ghost btn-sm" onclick="addColorRow()">+ Thêm màu</button>
        </div>
        <div class="card-body">
            <p class="admin-form-hint" style="margin-bottom:14px;">Chọn màu từ color picker. Để giá = 0 thì dùng giá gốc.</p>

            <div style="display:grid;grid-template-columns:44px 1fr 160px 36px;gap:8px;margin-bottom:8px;font-size:12px;font-weight:700;color:var(--text-3);padding:0 4px;">
                <span>Màu</span><span>Tên màu</span><span>Giá (₫)</span><span></span>
            </div>

            <div id="color-rows">
                <?php foreach ($existingColors as $c): ?>
                <div class="variant-row" style="display:grid;grid-template-columns:44px 1fr 160px 36px;gap:8px;margin-bottom:8px;align-items:center;">
                    <input type="color" name="c_hex[]" value="<?= htmlspecialchars($c['color_hex'] ?? '#888888') ?>"
                           style="width:44px;height:36px;border:1.5px solid var(--border);border-radius:6px;padding:2px;cursor:pointer;">
                    <input type="text" name="c_name[]" value="<?= htmlspecialchars($c['name']) ?>"
                           placeholder="VD: Titan Đen" style="height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                    <input type="number" name="c_price[]" value="<?= (int)$c['price'] ?>"
                           min="0" step="1000" placeholder="0"
                           style="height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                    <button type="button" onclick="this.closest('.variant-row').remove()"
                            style="height:36px;width:36px;border:1.5px solid var(--error);background:var(--error-bg);border-radius:6px;cursor:pointer;color:var(--error);font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-ghost btn-sm" onclick="addColorRow()" style="margin-top:4px;">
                + Thêm màu
            </button>
        </div>
    </div>

    <!-- Specifications -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <span class="card-title">📋 Thông số kỹ thuật</span>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <span style="font-size:12px; color:var(--text-3); font-weight:600;">Mẫu nhanh:</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="applyTemplate('phone')" style="padding:4px 8px; font-size:12px;">📱 Điện thoại</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="applyTemplate('laptop')" style="padding:4px 8px; font-size:12px;">💻 Laptop</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="applyTemplate('tablet')" style="padding:4px 8px; font-size:12px;">📁 Tablet</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="applyTemplate('accessory')" style="padding:4px 8px; font-size:12px;">🎧 Phụ kiện</button>
            </div>
        </div>
        <div class="card-body">
            <p class="admin-form-hint" style="margin-bottom:14px;">Thêm thông số kỹ thuật chi tiết theo nhóm (VD: Màn hình, Hiệu năng, Camera, Pin & Sạc...).</p>

            <div style="display:grid;grid-template-columns:180px 200px 1fr 36px;gap:8px;margin-bottom:8px;font-size:12px;font-weight:700;color:var(--text-3);padding:0 4px;">
                <span>Nhóm thông số</span><span>Tên thông số</span><span>Giá trị thông số</span><span></span>
            </div>

            <div id="spec-rows">
                <?php foreach ($existingSpecs as $s): ?>
                <div class="variant-row" style="display:grid;grid-template-columns:180px 200px 1fr 36px;gap:8px;margin-bottom:8px;align-items:center;">
                    <input type="text" name="spec_group[]" value="<?= htmlspecialchars($s['spec_group']) ?>"
                           placeholder="VD: Màn hình" style="height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                    <input type="text" name="spec_name[]" value="<?= htmlspecialchars($s['spec_name']) ?>"
                           placeholder="VD: Kích thước" style="height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                    <input type="text" name="spec_value[]" value="<?= htmlspecialchars($s['spec_value']) ?>"
                           placeholder="VD: 6.7 inch" style="height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                    <button type="button" onclick="this.closest('.variant-row').remove()"
                            style="height:36px;width:36px;border:1.5px solid var(--error);background:var(--error-bg);border-radius:6px;cursor:pointer;color:var(--error);font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-ghost btn-sm" onclick="addSpecRow()" style="margin-top:4px;">
                + Thêm thông số
            </button>
        </div>
    </div>

    <!-- Image upload -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title">Hình ảnh sản phẩm</span></div>
        <div class="card-body">
            <?php $currentImg = $product['image'] ?? ''; ?>
            <?php if ($currentImg): ?>
                <img src="/BanHang/assets/images/<?= htmlspecialchars($currentImg) ?>"
                     alt="Ảnh hiện tại" class="img-preview" id="imgPreview">
                <p style="text-align:center;font-size:12px;color:var(--text-3);margin-bottom:12px;">Ảnh hiện tại</p>
            <?php else: ?>
                <img src="" alt="" class="img-preview" id="imgPreview" style="display:none;">
            <?php endif; ?>

            <label class="img-upload-wrap" for="image">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--text-3);margin:0 auto 8px;display:block;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p style="color:var(--text-2);font-size:13px;">Click hoặc kéo thả ảnh vào đây</p>
                <p class="admin-form-hint">JPG, PNG, WEBP, GIF · Tối đa 5MB</p>
                <input type="file" id="image" name="image" accept="image/*"
                       style="display:none;" onchange="previewImg(this)">
            </label>
        </div>
    </div>

    <div style="display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary">
            <?= $isEdit ? '✓ Cập nhật sản phẩm' : '+ Thêm sản phẩm' ?>
        </button>
        <a href="products.php" class="btn btn-ghost">Hủy</a>
    </div>
</form>
</div>

<script>
function rowStyle(cols) {
    return `display:grid;grid-template-columns:${cols};gap:8px;margin-bottom:8px;align-items:center;`;
}
const inputStyle = 'height:36px;padding:0 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;';
const delBtn = `<button type="button" onclick="this.closest('.variant-row').remove()" style="height:36px;width:36px;border:1.5px solid #dc2626;background:#fee2e2;border-radius:6px;cursor:pointer;color:#dc2626;font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>`;

function addVersionRow() {
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.style.cssText = rowStyle('1fr 160px 36px');
    row.innerHTML = `
        <input type="text" name="v_name[]" placeholder="VD: 256GB" style="${inputStyle}">
        <input type="number" name="v_price[]" value="0" min="0" step="1000" style="${inputStyle}">
        ${delBtn}`;
    document.getElementById('version-rows').appendChild(row);
    row.querySelector('input[type=text]').focus();
}

function addColorRow() {
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.style.cssText = rowStyle('44px 1fr 160px 36px');
    row.innerHTML = `
        <input type="color" name="c_hex[]" value="#2563eb" style="width:44px;height:36px;border:1.5px solid var(--border);border-radius:6px;padding:2px;cursor:pointer;">
        <input type="text" name="c_name[]" placeholder="VD: Titan Đen" style="${inputStyle}">
        <input type="number" name="c_price[]" value="0" min="0" step="1000" style="${inputStyle}">
        ${delBtn}`;
    document.getElementById('color-rows').appendChild(row);
    row.querySelector('input[type=text]').focus();
}

function addSpecRow(group = '', name = '', value = '') {
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.style.cssText = rowStyle('180px 200px 1fr 36px');
    row.innerHTML = `
        <input type="text" name="spec_group[]" value="${group}" placeholder="VD: Màn hình" style="${inputStyle}">
        <input type="text" name="spec_name[]" value="${name}" placeholder="VD: Kích thước" style="${inputStyle}">
        <input type="text" name="spec_value[]" value="${value}" placeholder="VD: 6.7 inch" style="${inputStyle}">
        ${delBtn}`;
    document.getElementById('spec-rows').appendChild(row);
    if (!group && !name && !value) {
        row.querySelector('input[name="spec_group[]"]').focus();
    }
}

const specTemplates = {
    phone: [
        { group: 'Màn hình', name: 'Công nghệ màn hình' },
        { group: 'Màn hình', name: 'Kích thước màn hình' },
        { group: 'Màn hình', name: 'Độ phân giải' },
        { group: 'Màn hình', name: 'Tần số quét' },
        { group: 'Hiệu năng', name: 'Chipset (CPU)' },
        { group: 'Hiệu năng', name: 'Dung lượng RAM' },
        { group: 'Camera', name: 'Camera sau' },
        { group: 'Camera', name: 'Camera trước' },
        { group: 'Pin & Sạc', name: 'Dung lượng pin' },
        { group: 'Pin & Sạc', name: 'Hỗ trợ sạc tối đa' },
        { group: 'Thiết kế', name: 'Kích thước' },
        { group: 'Thiết kế', name: 'Trọng lượng' },
        { group: 'Hệ điều hành', name: 'OS' }
    ],
    laptop: [
        { group: 'Màn hình', name: 'Kích thước màn hình' },
        { group: 'Màn hình', name: 'Độ phân giải' },
        { group: 'Hiệu năng', name: 'Bộ vi xử lý (CPU)' },
        { group: 'Hiệu năng', name: 'Card đồ họa (GPU)' },
        { group: 'Hiệu năng', name: 'Dung lượng RAM' },
        { group: 'Lưu trữ', name: 'Ổ cứng' },
        { group: 'Pin & Sạc', name: 'Dung lượng pin' },
        { group: 'Kết nối', name: 'Cổng giao tiếp' },
        { group: 'Thiết kế', name: 'Trọng lượng' },
        { group: 'Hệ điều hành', name: 'Hệ điều hành (OS)' }
    ],
    tablet: [
        { group: 'Màn hình', name: 'Công nghệ màn hình' },
        { group: 'Màn hình', name: 'Kích thước màn hình' },
        { group: 'Màn hình', name: 'Độ phân giải' },
        { group: 'Hiệu năng', name: 'Chipset (CPU)' },
        { group: 'Hiệu năng', name: 'Dung lượng RAM' },
        { group: 'Camera', name: 'Camera sau' },
        { group: 'Camera', name: 'Camera trước' },
        { group: 'Pin & Sạc', name: 'Dung lượng pin' },
        { group: 'Hệ điều hành', name: 'OS' }
    ],
    accessory: [
        { group: 'Thông số chung', name: 'Tương thích' },
        { group: 'Thông số chung', name: 'Chất liệu' },
        { group: 'Kết nối', name: 'Phương thức kết nối' },
        { group: 'Pin & Sạc', name: 'Thời lượng pin' },
        { group: 'Pin & Sạc', name: 'Cổng sạc' },
        { group: 'Thiết kế', name: 'Trọng lượng' }
    ]
};

function applyTemplate(type) {
    if (!specTemplates[type]) return;
    const container = document.getElementById('spec-rows');
    const hasExisting = container.querySelectorAll('.variant-row').length > 0;
    if (hasExisting && !confirm('Việc này sẽ thêm các dòng thông số mẫu mới vào danh sách hiện tại. Bạn có muốn tiếp tục?')) {
        return;
    }
    specTemplates[type].forEach(item => {
        addSpecRow(item.group, item.name, '');
    });
}

function previewImg(input) {
    const preview = document.getElementById('imgPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php }); ?>
