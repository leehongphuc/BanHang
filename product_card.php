<?php
$inStock = ($p['stock'] ?? 0) > 0;
$stockClass = $inStock ? '' : 'out-of-stock';
$price = $p['price'] ?? 0;
$pCat  = (int)($p['category_id'] ?? 0);

// Tính giảm giá từ old_price hoặc voucher đang active
$oldPrice = $p['old_price'] ?? 0;

// Nếu chưa có old_price thủ công, tính từ voucher
if (!($oldPrice > $price)) {
    $bestDiscount = 0;
    foreach ($activeVouchers ?? [] as $v) {
        // Voucher áp dụng cho tất cả hoặc đúng danh mục sản phẩm
        if (!$v['category_id'] || $v['category_id'] == $pCat) {
            if ($v['discount_type'] === 'percent') {
                $disc = ($price * $v['discount_value']) / 100;
            } else {
                $disc = min((float)$v['discount_value'], $price);
            }
            if ($disc > $bestDiscount) $bestDiscount = $disc;
        }
    }
    if ($bestDiscount > 0) {
        $oldPrice = $price;
        $price    = round($price - $bestDiscount);
    }
}

$hasDiscount     = $oldPrice > $price;
$discountPercent = $hasDiscount ? round((($oldPrice - $price) / $oldPrice) * 100) : 0;
$imgFile = htmlspecialchars($p['image'] ?? 'placeholder.jpg');
$pName   = htmlspecialchars($p['name'] ?? 'Sản phẩm');
$pId     = (int)($p['id'] ?? 0);
?>
<article class="product-card <?= $stockClass ?>" role="listitem"
         itemscope itemtype="https://schema.org/Product">
    <div class="card-image-wrap">
        <div class="card-badges" style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
            <?php if (!$inStock): ?>
                <span class="badge" style="background:var(--clr-text-secondary);color:white;">Hết hàng</span>
            <?php endif; ?>
            <?php if ($hasDiscount): ?>
                <span class="badge" style="background:var(--clr-error-light);color:var(--clr-error);">-<?= $discountPercent ?>%</span>
            <?php endif; ?>
        </div>

        <a href="product_detail.php?id=<?= $pId ?>" class="product-img-wrapper">
            <img src="assets/images/<?= $imgFile ?>"
                 alt="<?= $pName ?> - ảnh sản phẩm"
                 loading="lazy"
                 width="300" height="300"
                 itemprop="image">
        </a>

        <?php
        $isWishlisted = in_array($pId, $wishlistProductIds ?? []);
        ?>
        <button class="btn-wishlist <?= $isWishlisted ? 'active' : '' ?>"
                aria-label="Thêm <?= $pName ?> vào yêu thích"
                aria-pressed="<?= $isWishlisted ? 'true' : 'false' ?>"
                onclick="toggleWishlist(this, <?= $pId ?>)">
            <svg viewBox="0 0 24 24" fill="<?= $isWishlisted ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </button>
    </div>

    <div class="card-body">
        <h3 class="card-name" itemprop="name">
            <a href="product_detail.php?id=<?= $pId ?>"><?= $pName ?></a>
        </h3>

        <div class="card-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer" style="display:flex; align-items:center; gap:8px;">
            <meta itemprop="priceCurrency" content="VND">
            <span class="price-current"
                  aria-label="Giá: <?= formatPrice($price) ?>"
                  itemprop="price" content="<?= $price ?>">
                <?= formatPrice($price) ?>
            </span>
            <?php if ($hasDiscount): ?>
                <span style="font-size:13px; color:var(--clr-text-tertiary); text-decoration:line-through; font-weight:500;">
                    <?= formatPrice($oldPrice) ?>
                </span>
            <?php endif; ?>
        </div>

        <form action="cart.php" method="POST" class="ajax-cart-form" style="margin-top:auto">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $pId ?>">
            <button type="submit"
                    class="btn-add-cart"
                    <?= !$inStock ? 'disabled aria-disabled="true"' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <?= $inStock ? 'Thêm vào giỏ' : 'Hết hàng' ?>
            </button>
        </form>
    </div>
</article>