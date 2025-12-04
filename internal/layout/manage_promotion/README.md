# 🎁 Quản Lý Khuyến Mại - Tài Liệu Hướng Dẫn

## 📁 Tổng Quan

Module **Quản Lý Khuyến Mại** được xây dựng theo mô hình **MVC (Model-View-Controller)** để quản lý các chương trình khuyến mại trong hệ thống DFC Gym, bao gồm thêm, sửa, xóa và tìm kiếm khuyến mại.

## 🏗️ Cấu Trúc File

### 1. **promotion.php** (View - Giao Diện)
**Chức năng:** File hiển thị giao diện người dùng và HTML

**Nhiệm vụ:**
- Hiển thị danh sách khuyến mại dưới dạng card grid
- Hiển thị form thêm/sửa khuyến mại trong modal
- Hiển thị thông báo từ session
- Xử lý tìm kiếm khuyến mại

**Các thành phần chính:**
- Header với nút "Thêm Khuyến Mại"
- Thông báo thành công/lỗi
- Form tìm kiếm
- Grid hiển thị các card khuyến mại
- Modal thêm/sửa khuyến mại
- Dialog xác nhận xóa
- Dialog thông báo

**Dependencies:**
- `promotion_controller.php` - Xử lý các action
- `promotion_model.php` - Truy vấn dữ liệu
- `promotion.js` - Xử lý JavaScript
- `promotion.css` - Styling

---

### 2. **promotion_controller.php** (Controller - Xử Lý Logic)
**Chức năng:** File xử lý các action từ người dùng và điều phối giữa View và Model

**Nhiệm vụ:**
- Xử lý thêm khuyến mại mới (`action=add`)
- Xử lý cập nhật khuyến mại (`action=edit`)
- Xử lý xóa khuyến mại (`action=delete`) - Hỗ trợ AJAX
- Validate dữ liệu đầu vào:
  - Kiểm tra mã khuyến mại trùng lặp
  - Kiểm tra ngày kết thúc phải sau ngày bắt đầu
  - Kiểm tra giá trị giảm phần trăm (0-100)
- Quản lý session messages
- Redirect sau khi xử lý thành công

**Các action được xử lý:**
- `POST action=add` - Thêm khuyến mại mới
- `POST action=edit` - Cập nhật khuyến mại
- `POST action=delete` - Xóa khuyến mại (AJAX)

**Dependencies:**
- `promotion_model.php` - Sử dụng các hàm truy vấn database

---

### 3. **promotion_model.php** (Model - Truy Vấn Database)
**Chức năng:** File chứa các hàm truy vấn và tương tác với database

**Nhiệm vụ:**
- Kết nối database (sử dụng `db.php`)
- Truy vấn dữ liệu khuyến mại
- Thực hiện các thao tác CRUD (Create, Read, Update, Delete)
- Kiểm tra ràng buộc dữ liệu

**Các hàm chính:**

#### Truy vấn dữ liệu:
- `getDBConnection()` - Lấy kết nối database
- `getPromotions($searchTerm)` - Lấy danh sách khuyến mại với tìm kiếm
- `getPromotionById($id)` - Lấy thông tin khuyến mại theo ID

#### Kiểm tra dữ liệu:
- `checkPromotionCodeExists($ma_khuyen_mai, $excludeId)` - Kiểm tra mã khuyến mại đã tồn tại
- `checkPromotionConstraints($khuyen_mai_id)` - Kiểm tra ràng buộc trước khi xóa

#### Thao tác dữ liệu:
- `addPromotion($pdo, $data)` - Thêm khuyến mại mới
- `updatePromotion($pdo, $id, $data)` - Cập nhật khuyến mại
- `deletePromotion($pdo, $khuyen_mai_id)` - Xóa khuyến mại

**Dependencies:**
- `../../../Database/db.php` - File kết nối database chung

---

### 4. **promotion.js** (JavaScript - Xử Lý Frontend)
**Chức năng:** File xử lý các tương tác phía client và AJAX

**Nhiệm vụ:**
- Xử lý mở/đóng modal
- Xử lý form submit
- Xử lý xóa khuyến mại với xác nhận (AJAX)
- Hiển thị thông báo lỗi/thành công
- Xử lý tìm kiếm

**Các hàm chính:**
- `deletePromotion(khuyen_mai_id, ten_khuyen_mai)` - Xóa khuyến mại với xác nhận
- `showConfirmDialog(message, onConfirm)` - Hiển thị dialog xác nhận
- `showMessageDialog(type, title, message)` - Hiển thị thông báo
- Các hàm xử lý modal và form

---

### 5. **promotion.css** (CSS - Styling)
**Chức năng:** File định nghĩa style và giao diện

**Nhiệm vụ:**
- Styling cho grid card khuyến mại
- Styling cho modal thêm/sửa
- Styling cho form inputs
- Styling cho các badge trạng thái
- Responsive design
- Animation và transitions

---

## 🔄 Luồng Hoạt Động

### Thêm Khuyến Mại:
1. User click nút "➕ Thêm Khuyến Mại" → Mở modal
2. User điền form và submit
3. `promotion.php` gửi POST request với `action=add`
4. `promotion_controller.php` xử lý:
   - Validate dữ liệu (mã trùng, ngày hợp lệ, phần trăm hợp lệ)
   - Gọi Model để thêm khuyến mại
   - Set session message và redirect
5. `promotion.php` hiển thị thông báo thành công

### Sửa Khuyến Mại:
1. User click nút "Sửa" trên card → Chuyển đến `promotion.php?edit=ID`
2. `promotion.php` gọi `getPromotionById()` từ Model để lấy dữ liệu
3. Hiển thị modal với dữ liệu đã điền
4. User submit form → `promotion_controller.php` xử lý `action=edit`
5. Model cập nhật dữ liệu trong database
6. Redirect về danh sách với thông báo thành công

### Xóa Khuyến Mại:
1. User click nút "Xóa" trên card → JavaScript gọi `deletePromotion()`
2. Hiển thị dialog xác nhận
3. User xác nhận → Gửi AJAX POST với `action=delete`
4. `promotion_controller.php` xử lý:
   - Kiểm tra ràng buộc (qua Model)
   - Xóa khuyến mại (qua Model)
   - Trả về JSON response
5. JavaScript reload trang để cập nhật danh sách

### Tìm Kiếm:
1. User nhập từ khóa và submit form GET
2. `promotion.php` lấy `$_GET['search']`
3. Gọi `getPromotions($searchTerm)` từ Model
4. Hiển thị kết quả tìm kiếm

---

## 📊 Cấu Trúc Database

### Bảng `khuyenmai`:
- `khuyen_mai_id` (PK) - ID khuyến mại
- `ma_khuyen_mai` - Mã khuyến mại (unique)
- `ten_khuyen_mai` - Tên khuyến mại
- `mo_ta` - Mô tả
- `loai_giam` - Loại giảm (Phần trăm/Số tiền)
- `gia_tri_giam` - Giá trị giảm
- `giam_toi_da` - Giảm tối đa (VNĐ)
- `gia_tri_don_hang_toi_thieu` - Đơn tối thiểu (VNĐ)
- `ap_dung_cho_goi_tap_id` - ID gói tập áp dụng (nullable)
- `ngay_bat_dau` - Ngày bắt đầu
- `ngay_ket_thuc` - Ngày kết thúc
- `so_luong_ma` - Số lượng mã (nullable)
- `so_luong_da_dung` - Số lượng đã dùng
- `trang_thai` - Trạng thái (Đang áp dụng/Hết hạn/Tạm dừng)

---

## 🔒 Ràng Buộc Khi Xóa

Khuyến mại không thể xóa nếu có:
- Lịch sử khuyến mại liên quan (`lichsukhuyenmai`)
- Hóa đơn đã áp dụng (`hoadon`)

---

## ✅ Validation Rules

### Mã Khuyến Mại:
- Bắt buộc nhập
- Không được trùng với mã khác (trừ khi đang sửa chính nó)

### Ngày:
- Ngày kết thúc phải sau ngày bắt đầu
- Cả hai đều bắt buộc

### Giá Trị Giảm:
- Bắt buộc nhập
- Nếu loại là "Phần trăm": giá trị phải từ 0-100
- Nếu loại là "Số tiền": giá trị phải >= 0

### Trạng Thái:
- Bắt buộc chọn
- Các giá trị: "Đang áp dụng", "Hết hạn", "Tạm dừng"

---

## 📝 Lưu Ý Khi Phát Triển

1. **Kết nối Database:** Luôn sử dụng `getDBConnection()` từ Model, không tạo kết nối mới
2. **Session Messages:** Sử dụng `$_SESSION['message']` và `$_SESSION['messageType']` để hiển thị thông báo
3. **AJAX Requests:** Action delete trả về JSON response, các action khác redirect
4. **Error Handling:** Luôn sử dụng try-catch và rollback transaction khi có lỗi
5. **Security:** 
   - Luôn validate và sanitize dữ liệu đầu vào
   - Sử dụng prepared statements
   - Kiểm tra ID hợp lệ trước khi xử lý
6. **Transaction:** Sử dụng transaction cho các thao tác quan trọng (add, edit, delete)

---

## 🚀 Cách Sử Dụng

1. Truy cập trang: `promotion.php`
2. Xem danh sách khuyến mại dạng card
3. Tìm kiếm: Nhập từ khóa vào ô tìm kiếm và submit
4. Thêm mới: Click "➕ Thêm Khuyến Mại" → Điền form → Submit
5. Sửa: Click nút "Sửa" trên card → Chỉnh sửa form → Submit
6. Xóa: Click nút "Xóa" trên card → Xác nhận

---

## 🎨 Giao Diện

### Card Khuyến Mại hiển thị:
- Badge giảm giá (phần trăm hoặc số tiền)
- Badge trạng thái (màu sắc khác nhau)
- Tên khuyến mại
- Mô tả
- Mã khuyến mại
- Ngày bắt đầu - kết thúc
- Đơn tối thiểu (nếu có)
- Số lượng đã dùng/tổng số (nếu có)
- Nút Sửa và Xóa

### Trạng Thái:
- **Đang áp dụng** - Màu xanh lá
- **Hết hạn** - Màu đỏ
- **Tạm dừng** - Màu vàng/cam

---

## 📞 Liên Hệ

Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng liên hệ đội phát triển.

**Phiên bản:** 1.0  
**Cập nhật lần cuối:** 2024

