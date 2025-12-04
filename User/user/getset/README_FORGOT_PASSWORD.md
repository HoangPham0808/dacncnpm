# Hướng dẫn sử dụng chức năng Quên mật khẩu

## Tổng quan

Chức năng quên mật khẩu cho phép người dùng đặt lại mật khẩu của họ thông qua email.

## Cài đặt

### 1. Cập nhật Database

**Cách 1: Chạy script PHP (Khuyến nghị)**
```bash
# Truy cập URL trong trình duyệt hoặc chạy từ command line:
php database/check_and_add_reset_token.php
```

**Cách 2: Chạy file SQL migration**

Nếu bạn gặp lỗi "Duplicate column name 'reset_token'", có nghĩa là cột `reset_token` đã tồn tại. Chạy file:
```sql
-- Chạy file: database/add_reset_token_expiry_only.sql trong phpMyAdmin
-- File này chỉ thêm cột reset_token_expiry nếu chưa có
```

Hoặc chạy file migration an toàn (tự động kiểm tra):
```sql
-- Chạy file: database/migration_add_reset_token_safe.sql trong phpMyAdmin
```

**Cách 3: Chạy SQL trực tiếp**
```sql
ALTER TABLE TaiKhoan 
ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL,
ADD COLUMN reset_token_expiry DATETIME NULL DEFAULT NULL;

CREATE INDEX idx_reset_token ON TaiKhoan(reset_token);
CREATE INDEX idx_reset_token_expiry ON TaiKhoan(reset_token_expiry);
```

**Lưu ý:** Code đã được cải thiện để tự động kiểm tra và thêm cột nếu chưa có. Tuy nhiên, nên chạy migration trước để đảm bảo.

### 2. Cấu hình Email (Tùy chọn)

Trong môi trường production, bạn cần cấu hình SMTP để gửi email. Hiện tại code sử dụng hàm `mail()` của PHP.

**Lưu ý:**
- Trên môi trường local (XAMPP), hàm `mail()` có thể không hoạt động
- Trên hosting miễn phí như InfinityFree, hàm `mail()` có thể bị chặn
- Để gửi email đáng tin cậy, nên sử dụng SMTP (PHPMailer, SwiftMailer, etc.)

## Cách sử dụng

### Người dùng

1. Vào trang chủ, click "Quên mật khẩu?"
2. Nhập email đã đăng ký
3. Kiểm tra email để nhận link đặt lại mật khẩu
4. Click vào link trong email (link có hiệu lực 1 giờ)
5. Nhập mật khẩu mới và xác nhận
6. Đăng nhập với mật khẩu mới

### Developer

#### Test trên môi trường Local

Trên môi trường local, link reset password sẽ được lưu vào:
- Session: `$_SESSION['reset_link']`
- Error log: Kiểm tra file log PHP

Để xem link reset password:
```php
// Trong forgot_password.php, link được log ra:
error_log("Password Reset Link for {$email}: {$reset_link}");
```

#### Kiểm tra Database

Sau khi user yêu cầu reset password:
```sql
SELECT ten_dang_nhap, reset_token, reset_token_expiry 
FROM TaiKhoan 
WHERE reset_token IS NOT NULL;
```

## Cấu trúc Files

- `user/getset/forgot_password.php` - Xử lý yêu cầu quên mật khẩu
- `user/getset/reset_password.php` - Trang đặt lại mật khẩu
- `user/includes/auth-modals.php` - Modal quên mật khẩu
- `assets/js/auth.js` - JavaScript xử lý form
- `database/migration_add_reset_token.sql` - SQL migration

## Bảo mật

1. **Token ngẫu nhiên**: Token được tạo bằng `random_bytes(32)` (64 ký tự hex)
2. **Thời hạn token**: Token hết hạn sau 1 giờ
3. **Không lộ thông tin**: Khi email không tồn tại, vẫn hiển thị thông báo thành công
4. **Xóa token sau khi dùng**: Token được xóa sau khi đặt lại mật khẩu thành công
5. **Validation**: Kiểm tra token hợp lệ và chưa hết hạn trước khi cho phép reset

## Troubleshooting

### Email không được gửi

1. Kiểm tra error log PHP
2. Kiểm tra cấu hình mail server
3. Trên local, kiểm tra session để lấy link reset

### Token không hợp lệ

1. Kiểm tra token chưa hết hạn (1 giờ)
2. Kiểm tra token đúng format (64 ký tự hex)
3. Kiểm tra database có cột `reset_token` và `reset_token_expiry`

### Link reset không hoạt động

1. Kiểm tra BASE_URL trong config.php
2. Kiểm tra đường dẫn file reset_password.php
3. Kiểm tra token trong URL có đúng không

## Cải tiến tương lai

- [ ] Tích hợp PHPMailer để gửi email qua SMTP
- [ ] Thêm rate limiting để tránh spam
- [ ] Thêm CAPTCHA
- [ ] Gửi email thông báo khi mật khẩu được đặt lại thành công
- [ ] Cho phép user xem lịch sử đặt lại mật khẩu

