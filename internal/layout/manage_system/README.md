# 🔐 Quản Lý Hệ Thống - Tài Liệu Hướng Dẫn

## 📁 Tổng Quan

Module **Quản Lý Hệ Thống** được xây dựng theo mô hình **MVC (Model-View-Controller)** để quản lý tài khoản trong hệ thống DFC Gym, bao gồm khóa và mở khóa tài khoản.

## 🏗️ Cấu Trúc File

### 1. **system.php** (View - Giao Diện)
**Chức năng:** File hiển thị giao diện người dùng và HTML

**Nhiệm vụ:**
- Hiển thị danh sách tài khoản dưới dạng bảng
- Hiển thị form tìm kiếm và lọc
- Hiển thị các nút khóa/mở khóa tài khoản
- Hiển thị thông báo lỗi (nếu có)

**Các thành phần chính:**
- Form tìm kiếm tài khoản
- Bộ lọc theo trạng thái và loại tài khoản
- Bảng danh sách tài khoản
- Dialog xác nhận
- Dialog thông báo

**Dependencies:**
- `system_controller.php` - Xử lý các action
- `system_model.php` - Truy vấn dữ liệu
- `system.js` - Xử lý JavaScript
- `system.css` - Styling

---

### 2. **system_controller.php** (Controller - Xử Lý Logic)
**Chức năng:** File xử lý các action từ người dùng và điều phối giữa View và Model

**Nhiệm vụ:**
- Xử lý khóa tài khoản (`action=lock_account`) - AJAX
- Xử lý mở khóa tài khoản (`action=unlock_account`) - AJAX
- Kiểm tra dữ liệu đầu vào
- Trả về JSON response cho AJAX requests

**Các action được xử lý:**
- `POST action=lock_account` - Khóa tài khoản
- `POST action=unlock_account` - Mở khóa tài khoản
- `POST action=view_login_history` - Xem lịch sử đăng nhập (AJAX)

**Dependencies:**
- `system_model.php` - Sử dụng các hàm truy vấn database

---

### 3. **system_model.php** (Model - Truy Vấn Database)
**Chức năng:** File chứa các hàm truy vấn và tương tác với database

**Nhiệm vụ:**
- Kết nối database (sử dụng `db.php`)
- Truy vấn dữ liệu tài khoản
- Thực hiện các thao tác khóa/mở khóa tài khoản

**Các hàm chính:**

#### Truy vấn dữ liệu:
- `getDBConnection()` - Lấy kết nối database
- `getAllAccounts()` - Lấy danh sách tất cả tài khoản
- `getAccountByUsername($ten_dang_nhap)` - Lấy thông tin tài khoản theo tên đăng nhập
- `getLoginHistory($ten_dang_nhap, $limit)` - Lấy lịch sử đăng nhập của tài khoản

#### Thao tác dữ liệu:
- `lockAccount($conn, $ten_dang_nhap)` - Khóa tài khoản
- `unlockAccount($conn, $ten_dang_nhap)` - Mở khóa tài khoản

**Dependencies:**
- `../../../Database/db.php` - File kết nối database chung

---

### 4. **system.js** (JavaScript - Xử Lý Frontend)
**Chức năng:** File xử lý các tương tác phía client và AJAX

**Nhiệm vụ:**
- Xử lý tìm kiếm và lọc tài khoản
- Xử lý khóa/mở khóa tài khoản bằng AJAX
- Hiển thị dialog xác nhận
- Hiển thị thông báo lỗi/thành công
- Cập nhật giao diện sau khi thao tác

**Các hàm chính:**
- `lockAccount(ten_dang_nhap)` - Khóa tài khoản với xác nhận
- `unlockAccount(ten_dang_nhap)` - Mở khóa tài khoản với xác nhận
- `viewLoginHistory(ten_dang_nhap)` - Xem lịch sử đăng nhập của tài khoản
- `closeLoginHistoryDialog()` - Đóng dialog lịch sử đăng nhập
- `showConfirmDialog(message, onConfirm)` - Hiển thị dialog xác nhận
- `showMessageDialog(type, title, message)` - Hiển thị thông báo
- `filterTable()` - Lọc và tìm kiếm tài khoản

---

### 5. **system.css** (CSS - Styling)
**Chức năng:** File định nghĩa style và giao diện

**Nhiệm vụ:**
- Styling cho bảng danh sách tài khoản
- Styling cho các dialog
- Styling cho form tìm kiếm và lọc
- Styling cho các nút khóa/mở khóa
- Responsive design
- Animation và transitions

---

## 🔄 Luồng Hoạt Động

### Khóa Tài Khoản:
1. User click nút "Khóa" trên dòng tài khoản cần khóa
2. JavaScript gọi `lockAccount(ten_dang_nhap)`
3. Hiển thị dialog xác nhận
4. User xác nhận → Gửi AJAX POST với `action=lock_account`
5. `system_controller.php` xử lý:
   - Validate dữ liệu
   - Gọi Model để khóa tài khoản
   - Trả về JSON response
6. JavaScript cập nhật giao diện và hiển thị thông báo thành công

### Mở Khóa Tài Khoản:
1. User click nút "Mở khóa" trên dòng tài khoản cần mở khóa
2. JavaScript gọi `unlockAccount(ten_dang_nhap)`
3. Hiển thị dialog xác nhận
4. User xác nhận → Gửi AJAX POST với `action=unlock_account`
5. `system_controller.php` xử lý:
   - Validate dữ liệu
   - Gọi Model để mở khóa tài khoản
   - Trả về JSON response
6. JavaScript cập nhật giao diện và hiển thị thông báo thành công

### Tìm Kiếm và Lọc:
1. User nhập từ khóa hoặc chọn bộ lọc
2. JavaScript lọc danh sách tài khoản theo điều kiện
3. Cập nhật bảng hiển thị kết quả

---

## 📊 Cấu Trúc Database

### Bảng `TaiKhoan`:
- `ten_dang_nhap` (PK) - Tên đăng nhập
- `mat_khau` - Mật khẩu
- `loai_tai_khoan` - Loại tài khoản (Nhân viên/Khách hàng)
- `trang_thai` - Trạng thái (Hoạt động/Khóa)
- `ngay_tao` - Ngày tạo
- `ngay_cap_nhat` - Ngày cập nhật
- `lan_dang_nhap_cuoi` - Lần đăng nhập cuối

### Bảng `LichSuDangNhap` (Tùy chọn):
- `id` (PK) - ID tự tăng
- `ten_dang_nhap` (FK) - Tên đăng nhập (liên kết với TaiKhoan)
- `thoi_gian_dang_nhap` - Thời gian đăng nhập
- `ip_address` - Địa chỉ IP
- `user_agent` - Thông tin trình duyệt
- `trang_thai` - Trạng thái đăng nhập (Thành công/Thất bại)

**Lưu ý:** Nếu bảng `LichSuDangNhap` chưa được tạo, hệ thống sẽ hiển thị thông tin từ cột `lan_dang_nhap_cuoi` của bảng `TaiKhoan`. Để có lịch sử đăng nhập đầy đủ, vui lòng chạy file SQL `create_login_history_table.sql` để tạo bảng.

---

## 🔒 Trạng Thái Tài Khoản

### Hoạt động:
- Tài khoản có thể đăng nhập bình thường
- Hiển thị nút "Khóa" để khóa tài khoản

### Khóa:
- Tài khoản không thể đăng nhập
- Hiển thị nút "Mở khóa" để mở khóa tài khoản

---

## 📝 Lưu Ý Khi Phát Triển

1. **Kết nối Database:** Luôn sử dụng `getDBConnection()` từ Model, không tạo kết nối mới
2. **AJAX Requests:** Tất cả các action đều trả về JSON response
3. **Error Handling:** Luôn sử dụng try-catch và xử lý lỗi đúng cách
4. **Security:** 
   - Luôn validate và sanitize dữ liệu đầu vào
   - Sử dụng prepared statements
   - Kiểm tra quyền truy cập trước khi cho phép khóa/mở khóa
5. **User Experience:** 
   - Hiển thị dialog xác nhận trước khi thực hiện hành động
   - Cập nhật giao diện ngay sau khi thao tác thành công
   - Hiển thị thông báo rõ ràng cho người dùng

---

## 🚀 Cách Sử Dụng

1. Truy cập trang: `system.php`
2. Xem danh sách tài khoản
3. Tìm kiếm: Nhập tên đăng nhập vào ô tìm kiếm
4. Lọc: Chọn trạng thái hoặc loại tài khoản từ dropdown
5. **Xem lịch sử đăng nhập:** Click nút "📜 Lịch sử" để xem lịch sử đăng nhập của tài khoản
6. Khóa tài khoản: Click nút "🔒 Khóa" và xác nhận
7. Mở khóa tài khoản: Click nút "🔓 Mở khóa" và xác nhận

### Xem Lịch Sử Đăng Nhập:
- Click nút "Lịch sử" ở cột "Thao tác" của bất kỳ tài khoản nào
- Dialog sẽ hiển thị:
  - Tên đăng nhập
  - Danh sách các lần đăng nhập với thời gian, địa chỉ IP, trình duyệt và trạng thái
- Nếu chưa có lịch sử, hệ thống sẽ hiển thị thông báo "Chưa có lịch sử đăng nhập"

---

## 🔍 Tính Năng

- ✅ Xem danh sách tất cả tài khoản
- ✅ Tìm kiếm tài khoản theo tên đăng nhập
- ✅ Lọc tài khoản theo trạng thái (Hoạt động/Khóa)
- ✅ Lọc tài khoản theo loại (Nhân viên/Khách hàng)
- ✅ Khóa tài khoản với xác nhận
- ✅ Mở khóa tài khoản với xác nhận
- ✅ **Xem lịch sử đăng nhập của tài khoản** (MỚI)
- ✅ Hiển thị thông tin chi tiết: ngày tạo, ngày cập nhật, lần đăng nhập cuối

---

## 📞 Liên Hệ

Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng liên hệ đội phát triển.

**Phiên bản:** 1.0  
**Cập nhật lần cuối:** 2024

