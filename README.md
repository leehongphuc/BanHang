# TechStore - E-commerce Website

## 📋 Về dự án

TechStore là website thương mại điện tử bán sản phẩm công nghệ (điện thoại, laptop, phụ kiện).

### Tính năng chính:
- 🛍️ Giỏ hàng & Checkout
- 👤 Đăng ký/Đăng nhập
- 🔐 Quên mật khẩu (email reset)
- 📧 Email xác nhận đơn hàng
- 🎫 Mã giảm giá (vouchers)
- ⭐ Đánh giá sản phẩm
- 📱 Responsive design
- 🔒 Admin panel

## 🛠️ Công nghệ

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Email:** PHPMailer với Gmail SMTP
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Server:** Apache với .htaccess

## 📦 Cài đặt (Development)

### Yêu cầu:
- WAMP/XAMPP/LAMP
- PHP 7.4 hoặc cao hơn
- MySQL 5.7 hoặc cao hơn
- Gmail account với App Password

### Các bước:

1. **Clone repository:**
   ```bash
   git clone https://github.com/your-username/techstore.git
   cd techstore
   ```

2. **Import database:**
   - Mở phpMyAdmin
   - Tạo database: `shop`
   - Import file: `shop.sql`

3. **Cấu hình database:**
   - Mở `config/db.php`
   - Cập nhật thông tin database nếu cần

4. **Cấu hình email:**
   - Copy `config/email.php.example` thành `config/email.php`
   - Cập nhật Gmail credentials

5. **Cấu hình app:**
   - Mở `config/app.php`
   - Đảm bảo `APP_ENV = 'development'`
   - Đảm bảo `APP_URL = 'http://localhost/BanHang'` (hoặc path của bạn)

6. **Truy cập:**
   ```
   http://localhost/BanHang
   ```

## 🚀 Deploy lên Production

Xem hướng dẫn chi tiết trong file: [`deployment_guide.md`](deployment_guide.md)

### Tóm tắt nhanh:

1. Mua hosting (khuyến nghị: Azdigi, HostVN)
2. Setup database và import `shop.sql`
3. Clone code từ Git hoặc upload FTP
4. Cập nhật `config/app.php`:
   - `APP_ENV = 'production'`
   - `APP_URL = 'https://yourdomain.com'`
5. Tạo file `config/email.php` trên server
6. Cập nhật `config/db.php` với thông tin DB production
7. Set file permissions (755 cho folders, 644 cho files)
8. Cài SSL certificate (Let's Encrypt)
9. Test toàn bộ tính năng

## 🔐 Bảo mật

- ✅ `.gitignore` bảo vệ `config/email.php`
- ✅ `.htaccess` bảo vệ config directory
- ✅ Password hashing với `password_hash()`
- ✅ SQL injection protection với prepared statements
- ✅ XSS protection với `htmlspecialchars()`
- ✅ CSRF protection cho admin actions
- ✅ SSL/HTTPS required cho production

## 📁 Cấu trúc Project

```
BanHang/
├── admin/              # Admin panel
├── api/                # API endpoints (nếu có)
├── assets/             # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── config/             # Configuration files
│   ├── db.php          # Database config
│   ├── email.php       # Email config (gitignored)
│   ├── app.php         # App config
│   └── auth.php        # Auth helpers
├── includes/           # Shared includes
│   ├── PHPMailer/      # Email library
│   └── email_helper.php
├── .htaccess           # Apache config
├── .gitignore          # Git ignore rules
├── shop.sql            # Database schema
├── index.php           # Homepage
├── products.php        # Product listing
├── product_detail.php  # Product details
├── cart.php            # Shopping cart
├── checkout.php        # Checkout
├── login.php           # Login
├── register.php        # Register
├── forgot_password.php # Password reset request
├── reset_password.php  # Password reset form
└── README.md           # This file
```

## 👤 Accounts mặc định

### Admin:
- Email: `admin@techstore.vn`
- Password: (xem trong shop.sql)

### User test:
- Tạo account mới qua trang Register

## 📧 Email Configuration

Email system sử dụng Gmail SMTP với PHPMailer.

**Tạo Gmail App Password:**
1. Vào https://myaccount.google.com/security
2. Bật 2-Step Verification
3. Vào https://myaccount.google.com/apppasswords
4. Tạo App Password cho "Mail"
5. Copy password vào `config/email.php`

## 🧪 Testing

### Test Email System:
1. **Forgot Password:**
   - Vào `/forgot_password.php`
   - Nhập email user
   - Check Gmail inbox

2. **Order Confirmation:**
   - Login user account
   - Thêm sản phẩm vào giỏ
   - Checkout
   - Check Gmail inbox

## 🐛 Troubleshooting

### Email không gửi được?
- Kiểm tra `config/email.php` tồn tại
- Verify Gmail App Password còn valid
- Check PHP error logs

### Database connection failed?
- Kiểm tra thông tin trong `config/db.php`
- Verify database đã được import
- Check MySQL service đang chạy

### CSS/JS không load?
- Clear browser cache
- Check file permissions
- Verify paths trong code

## 📝 Git Workflow

### Development:
```bash
git checkout -b feature/new-feature
# Code changes...
git add .
git commit -m "Add new feature"
git push origin feature/new-feature
# Create Pull Request
```

### Production Deploy:
```bash
# Trên server
cd ~/public_html
git pull origin main
# Test website
```

## 📞 Support

Nếu gặp vấn đề, kiểm tra:
1. PHP error logs
2. Browser console
3. Network tab trong DevTools
4. Database logs

## 📄 License

Private project - All rights reserved

## 🙏 Credits

- PHPMailer: https://github.com/PHPMailer/PHPMailer
- Fonts: Google Fonts (Be Vietnam Pro)
- Icons: Hand-coded SVGs

---

**Version:** 1.0.0  
**Last Updated:** June 2026  
**Developed by:** [Your Name]
