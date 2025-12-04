# 📋 Quản Lý Khách Hàng - Tài Liệu Hướng Dẫn

## 📁 Tổng Quan

Module **Quản Lý Khách Hàng** được xây dựng theo mô hình **MVC (Model-View-Controller)** để quản lý thông tin khách hàng trong hệ thống DFC Gym.

## 🏗️ Cấu Trúc File

### 1. **customer.php** (View - Giao Diện)
**Chức năng:** File hiển thị giao diện người dùng và HTML

**Nhiệm vụ:**
- Hiển thị danh sách khách hàng dưới dạng bảng
- Hiển thị form thêm/sửa khách hàng (dialog)
- Hiển thị thông tin chi tiết khách hàng
- Xử lý tìm kiếm và lọc theo giới tính
- Hiển thị thông báo từ session

**Các thành phần chính:**
- Form tìm kiếm và lọc
- Bảng danh sách khách hàng
- Dialog thêm khách hàng
- Dialog sửa khách hàng
- Dialog xem chi tiết
- Dialog xác nhận xóa
- Dialog thông báo

**Dependencies:**
- `customer_controller.php` - Xử lý các action
- `customer_model.php` - Truy vấn dữ liệu
- `customer.js` - Xử lý JavaScript
- `customer.css` - Styling

---

### 2. **customer_controller.php** (Controller - Xử Lý Logic)
**Chức năng:** File xử lý các action từ người dùng và điều phối giữa View và Model

**Nhiệm vụ:**
- Xử lý thêm khách hàng mới (`action=add`)
- Xử lý cập nhật thông tin khách hàng (`action=edit`)
- Xử lý xóa khách hàng (`action=delete`) - Hỗ trợ AJAX
- Kiểm tra dữ liệu đầu vào
- Quản lý session messages
- Redirect sau khi xử lý thành công

**Các action được xử lý:**
- `POST action=add` - Thêm khách hàng mới
- `POST action=edit` - Cập nhật thông tin khách hàng
- `POST action=delete` - Xóa khách hàng (AJAX)

**Dependencies:**
- `customer_model.php` - Sử dụng các hàm truy vấn database

---

### 3. **customer_model.php** (Model - Truy Vấn Database)
**Chức năng:** File chứa các hàm truy vấn và tương tác với database

**Nhiệm vụ:**
- Kết nối database (sử dụng `db.php`)
- Truy vấn dữ liệu khách hàng
- Thực hiện các thao tác CRUD (Create, Read, Update, Delete)
- Kiểm tra ràng buộc dữ liệu

**Các hàm chính:**

#### Truy vấn dữ liệu:
- `getDBConnection()` - Lấy kết nối database
- `getCustomers($searchTerm, $filterGioiTinh)` - Lấy danh sách khách hàng với tìm kiếm và lọc
- `getCustomerById($id)` - Lấy thông tin khách hàng theo ID
- `getCustomerDetailById($id)` - Lấy thông tin chi tiết (bao gồm tài khoản)

#### Kiểm tra dữ liệu:
- `checkUsernameExists($username)` - Kiểm tra tên đăng nhập đã tồn tại
- `checkEmailExists($email)` - Kiểm tra email đã tồn tại
- `checkCustomerConstraints($khach_hang_id)` - Kiểm tra ràng buộc trước khi xóa

#### Thao tác dữ liệu:
- `addAccount($pdo, $tenDangNhap, $matKhau, $loaiTaiKhoan)` - Thêm tài khoản
- `addCustomer($pdo, $data)` - Thêm khách hàng
- `updateCustomer($pdo, $id, $data)` - Cập nhật thông tin khách hàng
- `updatePassword($pdo, $tenDangNhap, $matKhauMoi)` - Cập nhật mật khẩu
- `deleteCustomer($pdo, $khach_hang_id, $tenDangNhap)` - Xóa khách hàng

**Dependencies:**
- `../../Database/db.php` - File kết nối database chung

---

### 4. **customer.js** (JavaScript - Xử Lý Frontend)
**Chức năng:** File xử lý các tương tác phía client và AJAX

**Nhiệm vụ:**
- Xử lý mở/đóng dialog
- Xử lý form submit bằng AJAX
- Xử lý xóa khách hàng với xác nhận
- Hiển thị thông báo lỗi/thành công
- Xử lý tìm kiếm và clear search

**Các hàm chính:**
- `openDialog(dialogId)` - Mở dialog
- `closeDialog(dialogId)` - Đóng dialog
- `clearSearch()` - Xóa bộ lọc tìm kiếm
- `deleteCustomer(khach_hang_id, ho_ten)` - Xóa khách hàng với xác nhận
- `showConfirmDialog(message, onConfirm)` - Hiển thị dialog xác nhận
- `showMessageDialog(type, title, message)` - Hiển thị thông báo

---

### 5. **customer.css** (CSS - Styling)
**Chức năng:** File định nghĩa style và giao diện

**Nhiệm vụ:**
- Styling cho bảng danh sách
- Styling cho các dialog
- Styling cho form inputs
- Responsive design
- Animation và transitions

---

## 🔄 Luồng Hoạt Động

### Thêm Khách Hàng:
1. User click nút "Thêm Khách Hàng" → Mở dialog
2. User điền form và submit
3. `customer.php` gửi POST request với `action=add`
4. `customer_controller.php` xử lý:
   - Validate dữ liệu
   - Kiểm tra username/email đã tồn tại (qua Model)
   - Gọi Model để thêm tài khoản và khách hàng
   - Set session message và redirect
5. `customer.php` hiển thị thông báo thành công

### Sửa Khách Hàng:
1. User click nút "Sửa" → Chuyển đến `customer.php?edit=ID`
2. `customer.php` gọi `getCustomerById()` từ Model để lấy dữ liệu
3. Hiển thị dialog sửa với dữ liệu đã điền
4. User submit form → `customer_controller.php` xử lý `action=edit`
5. Model cập nhật dữ liệu trong database
6. Redirect về danh sách với thông báo thành công

### Xóa Khách Hàng:
1. User click nút "Xóa" → JavaScript gọi `deleteCustomer()`
2. Hiển thị dialog xác nhận
3. User xác nhận → Gửi AJAX POST với `action=delete`
4. `customer_controller.php` xử lý:
   - Kiểm tra ràng buộc (qua Model)
   - Xóa khách hàng và tài khoản (qua Model)
   - Trả về JSON response
5. JavaScript reload trang để cập nhật danh sách

### Tìm Kiếm:
1. User nhập từ khóa và submit form GET
2. `customer.php` lấy `$_GET['search']` và `$_GET['gioi_tinh']`
3. Gọi `getCustomers($searchTerm, $filterGioiTinh)` từ Model
4. Hiển thị kết quả tìm kiếm

---

## 📊 Cấu Trúc Database

### Bảng `khachhang`:
- `khach_hang_id` (PK)
- `ten_dang_nhap` (FK → taikhoan)
- `ho_ten`, `email`, `sdt`, `cccd`
- `dia_chi`, `ngay_sinh`, `gioi_tinh`
- `nguon_gioi_thieu`, `ghi_chu`
- `trang_thai`, `ngay_dang_ky`
- `ngay_tao`, `ngay_cap_nhat`

### Bảng `taikhoan`:
- `ten_dang_nhap` (PK)
- `mat_khau`, `loai_tai_khoan`
- `trang_thai`, `lan_dang_nhap_cuoi`
- `ngay_tao`, `ngay_cap_nhat`

---

## 🔒 Ràng Buộc Khi Xóa

Khách hàng không thể xóa nếu có:
- Hóa đơn liên quan (`hoadon`)
- Đăng ký gói tập (`dangkygoitap`)
- Lịch sử khuyến mãi (`lichsukhuyenmai`)
- Đăng ký lịch tập (`dangkylichtap`)
- Lịch sử ra vào (`lichsuravao`)
- Đánh giá (`danhgia`)

---

## 📝 Lưu Ý Khi Phát Triển

1. **Kết nối Database:** Luôn sử dụng `getDBConnection()` từ Model, không tạo kết nối mới
2. **Session Messages:** Sử dụng `$_SESSION['message']` và `$_SESSION['messageType']` để hiển thị thông báo
3. **AJAX Requests:** Kiểm tra `HTTP_X_REQUESTED_WITH` header để phân biệt AJAX và form submit thông thường
4. **Error Handling:** Luôn sử dụng try-catch và rollback transaction khi có lỗi
5. **Security:** Luôn validate và sanitize dữ liệu đầu vào, sử dụng prepared statements

---

## 🚀 Cách Sử Dụng

1. Truy cập trang: `customer.php`
2. Xem danh sách khách hàng
3. Tìm kiếm: Nhập từ khóa và chọn giới tính (nếu cần)
4. Thêm mới: Click "➕ Thêm Khách Hàng"
5. Sửa: Click nút "✏️" trên dòng cần sửa
6. Xem chi tiết: Click nút "👁️"
7. Xóa: Click nút "🗑️" và xác nhận

---

## 📞 Liên Hệ

Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng liên hệ đội phát triển.

**Phiên bản:** 1.0  
**Cập nhật lần cuối:** 2024

