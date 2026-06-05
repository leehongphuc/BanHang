<?php
require 'header.php';
$pageTitle = 'Kho Mã Giảm Giá';

$result = $conn->query("SELECT * FROM vouchers WHERE is_active = 1 AND (end_date IS NULL OR end_date > NOW()) AND (usage_limit = 0 OR used_count < usage_limit) ORDER BY discount_value DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - TechStore</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .voucher-container {
            max-width: 1000px;
            margin: var(--sp-10) auto;
            padding: 0 var(--sp-4);
        }
        .voucher-header {
            text-align: center;
            margin-bottom: var(--sp-8);
        }
        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--sp-6);
        }
        .voucher-card {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            display: flex;
            overflow: hidden;
            border: 1px solid var(--clr-border-muted);
            position: relative;
        }
        .voucher-left {
            background: var(--clr-brand);
            color: white;
            padding: var(--sp-4);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 100px;
            border-right: 2px dashed rgba(255,255,255,0.5);
            text-align: center;
        }
        .voucher-right {
            padding: var(--sp-4);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .v-discount {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
        }
        .v-code {
            font-family: var(--font-mono);
            font-weight: 700;
            color: var(--clr-brand);
            background: var(--clr-brand-light);
            padding: 4px 8px;
            border-radius: var(--r-sm);
            display: inline-block;
            margin-bottom: var(--sp-2);
        }
        .v-desc {
            font-size: var(--text-sm);
            color: var(--clr-text-secondary);
            margin-bottom: var(--sp-2);
        }
        .v-date {
            font-size: var(--text-xs);
            color: var(--clr-error);
            margin-bottom: var(--sp-3);
        }
        .v-copy-btn {
            background: var(--clr-surface-overlay);
            color: var(--clr-brand);
            border: 1px solid var(--clr-brand);
            padding: 6px 12px;
            border-radius: var(--r-full);
            font-size: var(--text-xs);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .v-copy-btn:hover {
            background: var(--clr-brand);
            color: white;
        }
    </style>
</head>
<body>

<div class="voucher-container">
    <div class="voucher-header">
        <h1 style="font-size: 32px; margin-bottom: 10px; color: var(--clr-brand);">🎟 Kho Mã Giảm Giá</h1>
        <p style="color: var(--clr-text-secondary);">Săn ngay mã giảm giá cực hot áp dụng cho đơn hàng của bạn!</p>
    </div>

    <div class="voucher-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($v = $result->fetch_assoc()): ?>
            <div class="voucher-card">
                <div class="voucher-left">
                    <span class="v-discount">
                        <?= $v['discount_type'] === 'percent' ? $v['discount_value'].'%' : number_format($v['discount_value']/1000).'K' ?>
                    </span>
                    <span style="font-size: 11px; margin-top: 5px;">GIẢM</span>
                </div>
                <div class="voucher-right">
                    <div><span class="v-code"><?= htmlspecialchars($v['code']) ?></span></div>
                    <div class="v-desc">
                        Đơn tối thiểu <?= number_format($v['min_order_value']) ?>đ
                    </div>
                    <div class="v-date">
                        HSD: <?= $v['end_date'] ? date('d/m/Y', strtotime($v['end_date'])) : 'Không giới hạn' ?>
                    </div>
                    <button class="v-copy-btn" onclick="copyCode('<?= htmlspecialchars($v['code']) ?>', this)">
                        Sao chép mã
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 50px; background:var(--clr-surface); border-radius: var(--r-md);">
                Chưa có mã giảm giá nào trong thời điểm hiện tại. Vui lòng quay lại sau!
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btn.innerText;
        btn.innerText = 'Đã sao chép!';
        btn.style.background = 'var(--clr-brand)';
        btn.style.color = 'white';
        setTimeout(() => {
            btn.innerText = originalText;
            btn.style.background = 'var(--clr-surface-overlay)';
            btn.style.color = 'var(--clr-brand)';
        }, 2000);
    });
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>
