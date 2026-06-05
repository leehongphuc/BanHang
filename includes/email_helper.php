<?php
/**
 * Email Helper Functions
 * Handles sending emails for password reset and order confirmations
 * Uses PHPMailer with Gmail SMTP for reliable email delivery
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Load email configuration
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/app.php';

/**
 * Generate a random secure token
 */
function generateSecureToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Initialize PHPMailer with SMTP settings
 */
function getMailer() {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = EMAIL_DEBUG;
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = EMAIL_CHARSET;
        
        // Default sender
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_REPLY_TO, SMTP_FROM_NAME);
        
        return $mail;
    } catch (Exception $e) {
        error_log("PHPMailer initialization error: " . $e->getMessage());
        return null;
    }
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $token) {
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        $resetLink = APP_URL . "/reset_password.php?token=" . $token;
        
        // Recipients
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Đặt lại mật khẩu - TechStore";
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Đặt lại mật khẩu - TechStore</title>
        </head>
        <body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;">
            <div style="max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2 style="color: #2563eb; margin-top: 0;">Đặt lại mật khẩu</h2>
                <p style="font-size: 16px; line-height: 1.6; color: #333;">Xin chào,</p>
                <p style="font-size: 16px; line-height: 1.6; color: #333;">Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản TechStore của mình.</p>
                <p style="font-size: 16px; line-height: 1.6; color: #333;">Vui lòng click vào nút bên dưới để đặt lại mật khẩu:</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $resetLink . '" style="display: inline-block; background: #2563eb; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">Đặt lại mật khẩu</a>
                </div>
                <p style="color: #666; font-size: 14px; line-height: 1.6;"><strong>Link này sẽ hết hạn sau 1 giờ.</strong></p>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
                <p style="color: #999; font-size: 12px; text-align: center;">© 2026 TechStore. All rights reserved.</p>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = "Đặt lại mật khẩu TechStore\n\n" .
                        "Bạn đã yêu cầu đặt lại mật khẩu.\n\n" .
                        "Vui lòng truy cập link sau để đặt lại mật khẩu:\n" .
                        $resetLink . "\n\n" .
                        "Link này sẽ hết hạn sau 1 giờ.\n\n" .
                        "Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.\n\n" .
                        "© 2026 TechStore";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($orderId, $conn) {
    $mail = getMailer();
    if (!$mail) return false;
    
    try {
        // Fetch order details
        $order = $conn->query("SELECT o.*, u.email, u.fullname 
                               FROM orders o 
                               LEFT JOIN users u ON o.user_id = u.id 
                               WHERE o.id = " . (int)$orderId)->fetch_assoc();
        
        if (!$order) return false;
        
        $email = $order['email'] ?: $order['receiver_name'] . '@example.com';
        $customerName = $order['receiver_name'];
        $orderDate = date('d/m/Y H:i', strtotime($order['created_at']));
        $orderDetailLink = APP_URL . "/order_detail.php?id=" . $orderId;
        
        // Fetch order items
        $items = $conn->query("SELECT oi.*, p.name 
                               FROM order_items oi 
                               INNER JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = " . (int)$orderId);
        
        $itemsHtml = '';
        while ($item = $items->fetch_assoc()) {
            $itemPrice = number_format($item['price'], 0, ',', '.') . '₫';
            $itemTotal = number_format($item['price'] * $item['quantity'], 0, ',', '.') . '₫';
            $itemsHtml .= '
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px 8px;">' . htmlspecialchars($item['name']) . '</td>
                <td style="padding: 12px 8px; text-align: center;">' . $item['quantity'] . '</td>
                <td style="padding: 12px 8px; text-align: right;">' . $itemPrice . '</td>
                <td style="padding: 12px 8px; text-align: right; font-weight: bold;">' . $itemTotal . '</td>
            </tr>';
        }
        
        $totalAmount = number_format($order['total'], 0, ',', '.') . '₫';
        
        // Recipients
        $mail->addAddress($email, $customerName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Xác nhận đơn hàng #" . $orderId . " - TechStore";
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Xác nhận đơn hàng - TechStore</title>
        </head>
        <body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;">
            <div style="max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2 style="color: #22c55e; margin-top: 0;">✓ Đơn hàng đã được đặt thành công!</h2>
                <p style="font-size: 16px; line-height: 1.6; color: #333;">Xin chào <strong>' . htmlspecialchars($customerName) . '</strong>,</p>
                <p style="font-size: 16px; line-height: 1.6; color: #333;">Cảm ơn bạn đã mua hàng tại TechStore. Đơn hàng của bạn đã được tiếp nhận.</p>
                
                <div style="background: #f9fafb; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #2563eb;">
                    <p style="margin: 0; font-size: 14px;"><strong>Mã đơn hàng:</strong> <span style="color: #2563eb; font-size: 18px;">#' . $orderId . '</span></p>
                    <p style="margin: 10px 0 0 0; font-size: 14px;"><strong>Ngày đặt:</strong> ' . $orderDate . '</p>
                    <p style="margin: 10px 0 0 0; font-size: 14px;"><strong>Địa chỉ giao hàng:</strong> ' . htmlspecialchars($order['receiver_address']) . '</p>
                </div>

                <h3 style="color: #333; margin-top: 30px;">Chi tiết đơn hàng:</h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px 8px; text-align: left; font-size: 13px;">Sản phẩm</th>
                            <th style="padding: 12px 8px; text-align: center; font-size: 13px;">SL</th>
                            <th style="padding: 12px 8px; text-align: right; font-size: 13px;">Đơn giá</th>
                            <th style="padding: 12px 8px; text-align: right; font-size: 13px;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $itemsHtml . '
                    </tbody>
                </table>

                <div style="text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                    <p style="font-size: 20px; margin: 0;"><strong>Tổng cộng:</strong> <span style="color: #2563eb; font-weight: bold;">' . $totalAmount . '</span></p>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $orderDetailLink . '" style="display: inline-block; background: #2563eb; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">Xem chi tiết đơn hàng</a>
                </div>

                <p style="color: #666; font-size: 14px; line-height: 1.6;">Chúng tôi sẽ liên hệ với bạn sớm nhất để xác nhận và giao hàng.</p>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ hotline: <strong>1900 1234</strong></p>

                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
                <p style="color: #999; font-size: 12px; text-align: center;">© 2026 TechStore. All rights reserved.</p>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = "Đơn hàng #" . $orderId . " đã được đặt thành công!\n\n" .
                        "Xin chào " . $customerName . ",\n\n" .
                        "Cảm ơn bạn đã mua hàng tại TechStore.\n\n" .
                        "Chi tiết đơn hàng: " . $orderDetailLink;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Order confirmation email failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
