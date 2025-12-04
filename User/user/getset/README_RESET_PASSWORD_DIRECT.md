# Chức năng Đổi mật khẩu với xác thực thông tin

## Tổng quan
Chức năng cho phép người dùng đổi mật khẩu bằng cách xác thực thông tin cá nhân thay vì qua email.

## Luồng hoạt động

### Bước 1: Xác thực thông tin
1. Người dùng click vào "Quên mật khẩu?"
2. Nhập các thông tin:
   - Số điện thoại (10 số)
   - Họ và tên
   - Ngày sinh
   - Căn cước công dân (9-12 số)
3. Hệ thống kiểm tra thông tin với database
4. Nếu khớp, tạo reset token có hiệu lực 15 phút

### Bước 2: Đặt mật khẩu mới
1. Sau khi xác thực thành công, hiển thị form nhập mật khẩu mới
2. Người dùng nhập:
   - Mật khẩu mới (tối thiểu 6 ký tự)
   - Xác nhận mật khẩu
3. Hệ thống cập nhật mật khẩu và xóa reset token
4. Chuyển về trang đăng nhập

## Files liên quan

### 1. Frontend
- `user/includes/auth-modals.php`: Modal xác thực và đổi mật khẩu
- `assets/js/auth.js`: Xử lý JavaScript cho cả 2 bước

### 2. Backend
- `user/getset/verify_identity.php`: API xác thực thông tin
- `user/getset/reset_password_direct.php`: API đổi mật khẩu
- `database/check_and_add_reset_token.php`: Script kiểm tra/thêm cột database

### 3. Database
Bảng TaiKhoan cần có 2 cột:
- `reset_token`: VARCHAR(255) - Lưu token reset
- `reset_token_expiry`: DATETIME - Thời hạn token

## Cài đặt

### 1. Chạy migration database
```bash
php database/check_and_add_reset_token.php
```

### 2. Kiểm tra cấu hình
- Đảm bảo `database/config.php` có cấu hình đúng
- Local: dfc_gym database
- Production: if0_40194494_dfcgym database

## Bảo mật
- Token ngẫu nhiên 32 bytes
- Token hết hạn sau 15 phút
- Mật khẩu được hash bằng bcrypt
- Kiểm tra chính xác tất cả 4 trường thông tin
- Xóa token ngay sau khi đổi mật khẩu thành công

## Xử lý lỗi
- Thông báo lỗi rõ ràng cho người dùng
- Log lỗi chi tiết để debug
- Tự động retry kết nối database 3 lần
- Rollback transaction nếu có lỗi

## Testing
1. Thử với thông tin sai -> Báo lỗi
2. Thử với thông tin đúng -> Chuyển sang bước 2
3. Thử nhập mật khẩu không khớp -> Báo lỗi
4. Thử với token hết hạn -> Báo lỗi và quay lại bước 1
5. Thử đổi mật khẩu thành công -> Đăng nhập với mật khẩu mới
