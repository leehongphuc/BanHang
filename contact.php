<?php
session_start();
require './config/db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ — TechStore</title>
    <meta name="description" content="Thông tin liên hệ và địa chỉ cửa hàng TechStore. Chúng tôi luôn sẵn sàng hỗ trợ bạn.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--sp-6);
            margin-bottom: var(--sp-8);
        }
        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; }
        }
        .contact-card {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            padding: var(--sp-6);
            box-shadow: var(--shadow-card);
        }
        .contact-card-title {
            font-size: var(--text-lg);
            font-weight: 700;
            color: var(--clr-text-primary);
            margin-bottom: var(--sp-4);
            display: flex;
            align-items: center;
            gap: var(--sp-2);
        }
        .contact-card-icon {
            width: 40px;
            height: 40px;
            background: var(--clr-brand-light);
            color: var(--clr-brand);
            border-radius: var(--r-sm);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .contact-info-item {
            display: flex;
            gap: var(--sp-3);
            padding: var(--sp-3) 0;
            border-bottom: 1px solid var(--clr-border-muted);
        }
        .contact-info-item:last-child { border-bottom: none; }
        .contact-info-label {
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--clr-text-secondary);
            min-width: 100px;
        }
        .contact-info-value {
            font-size: var(--text-md);
            color: var(--clr-text-primary);
            font-weight: 600;
        }
        .map-container {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            height: 450px;
        }
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="margin-top:var(--sp-6);margin-bottom:var(--sp-10);">
        <!-- Page Title -->
        <div class="section-header" style="margin-bottom:var(--sp-6);">
            <h1 class="section-title">Liên hệ <span>với chúng tôi</span></h1>
            <p style="font-size:var(--text-md);color:var(--clr-text-secondary);margin-top:var(--sp-2);">
                TechStore luôn sẵn sàng hỗ trợ bạn. Hãy liên hệ với chúng tôi qua các kênh dưới đây.
            </p>
        </div>

        <!-- Contact Info Grid -->
        <div class="contact-grid">
            <!-- Store Info -->
            <div class="contact-card">
                <div class="contact-card-title">
                    <div class="contact-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    Thông tin cửa hàng
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-label">Địa chỉ:</div>
                    <div class="contact-info-value">123 Nguyễn Huệ, Quận 1, TP.HCM</div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-label">Điện thoại:</div>
                    <div class="contact-info-value">
                        <a href="tel:19001234" style="color:var(--clr-brand);text-decoration:none;">1900 1234</a>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-label">Email:</div>
                    <div class="contact-info-value">
                        <a href="mailto:support@techstore.vn" style="color:var(--clr-brand);text-decoration:none;">support@techstore.vn</a>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-label">Giờ làm việc:</div>
                    <div class="contact-info-value">Thứ 2 – CN: 8:00 – 22:00</div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="contact-card">
                <div class="contact-card-title">
                    <div class="contact-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    Hỗ trợ khách hàng
                </div>
                <div style="display:flex;flex-direction:column;gap:var(--sp-3);">
                    <div style="padding:var(--sp-3);background:var(--clr-surface-overlay);border-radius:var(--r-sm);">
                        <div style="font-weight:600;color:var(--clr-text-primary);margin-bottom:var(--sp-1);">🚚 Chính sách giao hàng</div>
                        <div style="font-size:var(--text-sm);color:var(--clr-text-secondary);">Miễn phí vận chuyển cho đơn hàng trên 500.000đ</div>
                    </div>
                    <div style="padding:var(--sp-3);background:var(--clr-surface-overlay);border-radius:var(--r-sm);">
                        <div style="font-weight:600;color:var(--clr-text-primary);margin-bottom:var(--sp-1);">🔄 Chính sách đổi trả</div>
                        <div style="font-size:var(--text-sm);color:var(--clr-text-secondary);">Đổi trả trong 30 ngày nếu có lỗi từ nhà sản xuất</div>
                    </div>
                    <div style="padding:var(--sp-3);background:var(--clr-surface-overlay);border-radius:var(--r-sm);">
                        <div style="font-weight:600;color:var(--clr-text-primary);margin-bottom:var(--sp-1);">🛡️ Chính sách bảo hành</div>
                        <div style="font-size:var(--text-sm);color:var(--clr-text-secondary);">Bảo hành chính hãng 12 tháng cho tất cả sản phẩm</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Google Map -->
        <div style="margin-bottom:var(--sp-6);">
            <h2 style="font-size:var(--text-xl);font-weight:700;color:var(--clr-text-primary);margin-bottom:var(--sp-4);">
                Vị trí cửa hàng trên <span style="color:var(--clr-brand);">bản đồ</span>
            </h2>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4916472908596!2d106.70190431533427!3d10.776171092318826!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f4b3330bcc9%3A0x5a981a5b48cdf740!2zTmd1eeG7hW4gSHXhu4csIFF1YW4gMSwgVGjDoG5oIHBo4buRIEjhu5MgQ2jDrSBNaW5oLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2s!4v1633024800000!5m2!1svi!2s"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Vị trí TechStore trên Google Maps">
                </iframe>
            </div>
        </div>

        <!-- Social Media -->
        <div class="contact-card" style="text-align:center;">
            <h3 style="font-size:var(--text-lg);font-weight:700;color:var(--clr-text-primary);margin-bottom:var(--sp-4);">
                Kết nối với chúng tôi
            </h3>
            <div style="display:flex;justify-content:center;gap:var(--sp-3);flex-wrap:wrap;">
                <a href="#" class="social-btn" style="width:48px;height:48px;background:var(--clr-brand-light);color:var(--clr-brand);border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;transition:all var(--dur-fast);">
                    f
                </a>
                <a href="#" class="social-btn" style="width:48px;height:48px;background:var(--clr-error-light);color:var(--clr-error);border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;transition:all var(--dur-fast);">
                    ▶
                </a>
                <a href="#" class="social-btn" style="width:48px;height:48px;background:var(--clr-brand-light);color:var(--clr-brand);border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;transition:all var(--dur-fast);">
                    Z
                </a>
                <a href="#" class="social-btn" style="width:48px;height:48px;background:var(--clr-text-primary);color:white;border-radius:var(--r-full);display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;transition:all var(--dur-fast);">
                    ♪
                </a>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer" role="region" aria-live="polite"></div>
    <?php include 'footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
