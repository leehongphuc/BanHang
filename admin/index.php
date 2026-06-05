<?php
require 'auth_check.php';
require '../config/db.php';
requireAdmin();
require 'layout.php';

// ---- Stats ----
$totalOrders   = (int)$conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalRevenue  = (float)$conn->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];
$totalProducts = (int)$conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalUsers    = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$pendingOrders = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];

// ---- Chart data: revenue & orders last 30 days ----
$chartResult = $conn->query(
    "SELECT DATE(created_at) as day, 
            SUM(total) as rev,
            COUNT(*) as order_count
     FROM orders
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
       AND status != 'cancelled'
     GROUP BY DATE(created_at)
     ORDER BY day ASC"
);
$chartLabels = [];
$chartRevenue = [];
$chartOrders = [];
while ($r = $chartResult->fetch_assoc()) {
    $chartLabels[] = date('d/m', strtotime($r['day']));
    $chartRevenue[] = (float)$r['rev'];
    $chartOrders[] = (int)$r['order_count'];
}

// ---- Chart data: revenue by category ----
$catRevenueResult = $conn->query(
    "SELECT c.name, COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
     FROM categories c
     LEFT JOIN products p ON c.id = p.category_id
     LEFT JOIN order_items oi ON p.id = oi.product_id
     LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
     GROUP BY c.id, c.name
     ORDER BY revenue DESC"
);
$catLabels = [];
$catRevenue = [];
while ($r = $catRevenueResult->fetch_assoc()) {
    if ($r['revenue'] > 0) {
        $catLabels[] = $r['name'];
        $catRevenue[] = (float)$r['revenue'];
    }
}

// ---- Chart data: top 5 best selling products ----
$topProductsResult = $conn->query(
    "SELECT p.name, SUM(oi.quantity) as total_sold
     FROM products p
     INNER JOIN order_items oi ON p.id = oi.product_id
     INNER JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
     GROUP BY p.id, p.name
     ORDER BY total_sold DESC
     LIMIT 5"
);
$topProdLabels = [];
$topProdValues = [];
while ($r = $topProductsResult->fetch_assoc()) {
    $topProdLabels[] = $r['name'];
    $topProdValues[] = (int)$r['total_sold'];
}

// ---- Chart data: payment methods distribution ----
$paymentResult = $conn->query(
    "SELECT payment_method, COUNT(*) as count, SUM(total) as revenue
     FROM orders
     WHERE status != 'cancelled'
     GROUP BY payment_method"
);
$paymentLabels = [];
$paymentCounts = [];
while ($r = $paymentResult->fetch_assoc()) {
    $label = $r['payment_method'] === 'COD' ? 'Thanh toán COD' : 'Thanh toán Online';
    $paymentLabels[] = $label;
    $paymentCounts[] = (int)$r['count'];
}

// ---- Recent orders ----
$adminName = $_SESSION['user_name'] ?? 'Admin';
$recentOrders = $conn->query(
    "SELECT o.*, COALESCE(u.fullname, o.receiver_name) as customer_name
     FROM orders o LEFT JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC LIMIT 8"
);

function formatVND(float $n): string {
    return number_format($n, 0, ',', '.') . '₫';
}

adminLayout('Dashboard', 'dashboard', function() use (
    $totalOrders, $totalRevenue, $totalProducts, $totalUsers, $pendingOrders,
    $chartLabels, $chartRevenue, $chartOrders,
    $catLabels, $catRevenue,
    $topProdLabels, $topProdValues,
    $paymentLabels, $paymentCounts,
    $recentOrders
) { ?>

<!-- Stat cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div>
            <div class="stat-label">Tổng đơn hàng</div>
            <div class="stat-value"><?= number_format($totalOrders) ?></div>
            <?php if ($pendingOrders > 0): ?>
            <div class="stat-sub" style="color:#d97706;"><?= $pendingOrders ?> đơn chờ xử lý</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div>
            <div class="stat-label">Tổng doanh thu</div>
            <div class="stat-value" style="font-size:18px;"><?= formatVND($totalRevenue) ?></div>
            <div class="stat-sub">Không tính đơn hủy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </div>
        <div>
            <div class="stat-label">Sản phẩm</div>
            <div class="stat-value"><?= number_format($totalProducts) ?></div>
            <div class="stat-sub">Đang kinh doanh</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div class="stat-label">Người dùng</div>
            <div class="stat-value"><?= number_format($totalUsers) ?></div>
            <div class="stat-sub">Đã đăng ký</div>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:24px;margin-bottom:24px;">
    <!-- Chart 1: Revenue & Orders Trend (30 days) -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Doanh thu & Đơn hàng (30 ngày)</span>
            <span style="font-size:12px;color:var(--text-3);">Xu hướng kinh doanh</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:280px;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Revenue by Category -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Doanh thu theo Danh mục</span>
            <span style="font-size:12px;color:var(--text-3);">Cơ cấu sản phẩm</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:280px;">
                <canvas id="categoryRevenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 3: Top 5 Best Selling Products -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Top 5 Sản phẩm bán chạy</span>
            <span style="font-size:12px;color:var(--text-3);">Theo số lượng đã bán</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:280px;">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 4: Payment Methods -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Phương thức thanh toán</span>
            <span style="font-size:12px;color:var(--text-3);">Phân bổ theo loại</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="height:280px;">
                <canvas id="paymentMethodChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent orders -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Đơn hàng gần đây</span>
        <a href="/BanHang/admin/orders.php" class="btn btn-ghost btn-sm">Xem tất cả →</a>
    </div>
    <div class="card-body p0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày đặt</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($o = $recentOrders->fetch_assoc()):
                    $statusMap = [
                        'pending'   => ['Chờ xác nhận', 'badge-pending'],
                        'confirmed' => ['Đã xác nhận',  'badge-confirmed'],
                        'shipping'  => ['Đang giao',    'badge-shipping'],
                        'delivered' => ['Đã giao',      'badge-delivered'],
                        'cancelled' => ['Đã hủy',       'badge-cancelled'],
                    ];
                    $s = $statusMap[$o['status']] ?? ['Không rõ', 'badge-pending'];
                ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td style="font-weight:700;"><?= formatVND((float)$o['total']) ?></td>
                    <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
                    <td style="color:var(--text-3);"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    <td>
                        <a href="/BanHang/admin/order_detail.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Chi tiết</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Chart 1: Revenue & Orders Trend (Combined Bar + Line)
new Chart(document.getElementById('revenueTrendChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Doanh thu (₫)',
            data: <?= json_encode($chartRevenue) ?>,
            backgroundColor: 'rgba(37,99,235,.15)',
            borderColor: 'rgba(37,99,235,.8)',
            borderWidth: 2,
            borderRadius: 6,
            yAxisID: 'y'
        }, {
            label: 'Số đơn hàng',
            data: <?= json_encode($chartOrders) ?>,
            type: 'line',
            borderColor: 'rgba(239,68,68,.8)',
            backgroundColor: 'rgba(239,68,68,.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: 'rgba(239,68,68,1)',
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        if (ctx.datasetIndex === 0) {
                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(ctx.raw);
                        } else {
                            return 'Đơn hàng: ' + ctx.raw + ' đơn';
                        }
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                position: 'left',
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,.05)' },
                ticks: { callback: v => new Intl.NumberFormat('vi-VN', {notation:'compact'}).format(v) }
            },
            y1: {
                type: 'linear',
                position: 'right',
                beginAtZero: true,
                grid: { display: false },
                ticks: { callback: v => v + ' đơn' }
            },
            x: { grid: { display: false } }
        }
    }
});

// Chart 2: Revenue by Category (Doughnut)
new Chart(document.getElementById('categoryRevenueChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            data: <?= json_encode($catRevenue) ?>,
            backgroundColor: [
                'rgba(37,99,235,.7)',
                'rgba(239,68,68,.7)',
                'rgba(34,197,94,.7)',
                'rgba(234,179,8,.7)',
                'rgba(168,85,247,.7)',
                'rgba(236,72,153,.7)'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'right' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.label + ': ' + new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(ctx.raw)
                }
            }
        }
    }
});

// Chart 3: Top 5 Best Selling Products (Horizontal Bar)
new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($topProdLabels) ?>,
        datasets: [{
            label: 'Số lượng đã bán',
            data: <?= json_encode($topProdValues) ?>,
            backgroundColor: [
                'rgba(37,99,235,.7)',
                'rgba(239,68,68,.7)',
                'rgba(34,197,94,.7)',
                'rgba(234,179,8,.7)',
                'rgba(168,85,247,.7)'
            ],
            borderWidth: 0,
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Đã bán: ' + ctx.raw + ' sản phẩm'
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,.05)' }
            },
            y: { grid: { display: false } }
        }
    }
});

// Chart 4: Payment Methods (Pie)
new Chart(document.getElementById('paymentMethodChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($paymentLabels) ?>,
        datasets: [{
            data: <?= json_encode($paymentCounts) ?>,
            backgroundColor: [
                'rgba(34,197,94,.7)',
                'rgba(37,99,235,.7)'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.label + ': ' + ctx.raw + ' đơn hàng'
                }
            }
        }
    }
});
</script>

<?php }); ?>
