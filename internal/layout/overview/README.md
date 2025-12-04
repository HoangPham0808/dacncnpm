# 📊 Tổng Quan Hệ Thống - Tài Liệu Hướng Dẫn

## 📁 Tổng Quan

Module **Tổng Quan Hệ Thống** được xây dựng theo mô hình **MVC (Model-View)** để hiển thị các thống kê tổng quan về hệ thống DFC Gym. Module này chỉ đọc và hiển thị dữ liệu, không có các action thêm/sửa/xóa.

## 🏗️ Cấu Trúc File

### 1. **overview.php** (View - Giao Diện)
**Chức năng:** File hiển thị giao diện người dùng và HTML

**Nhiệm vụ:**
- Hiển thị các thẻ thống kê (stat cards)
- Hiển thị bảng hóa đơn gần đây
- Hiển thị ngày giờ hiện tại
- Xử lý lỗi nếu có

**Các thành phần chính:**
- Header với ngày giờ hiện tại
- Grid 6 thẻ thống kê:
  - Tổng khách hàng
  - Tổng nhân viên
  - Doanh thu tháng
  - Doanh thu hôm nay
  - Hóa đơn chờ thanh toán
  - Khách check-in hôm nay
- Bảng hóa đơn gần đây (5 hóa đơn mới nhất)

**Dependencies:**
- `overview_model.php` - Truy vấn dữ liệu
- `overview.js` - Xử lý JavaScript (nếu có)
- `overview.css` - Styling

---

### 2. **overview_model.php** (Model - Truy Vấn Database)
**Chức năng:** File chứa các hàm truy vấn và tương tác với database

**Nhiệm vụ:**
- Kết nối database (sử dụng `db.php`)
- Truy vấn các thống kê từ database
- Xử lý lỗi và trả về dữ liệu

**Các hàm chính:**

#### Thống kê:
- `getDBConnection()` - Lấy kết nối database
- `getTotalActiveCustomers()` - Lấy tổng số khách hàng đang hoạt động
- `getTotalActiveEmployees()` - Lấy tổng số nhân viên đang làm việc
- `getMonthlyRevenue($thang, $nam)` - Lấy doanh thu tháng
- `getTodayRevenue($ngay)` - Lấy doanh thu hôm nay
- `getPendingInvoices()` - Lấy số hóa đơn chờ thanh toán
- `getTodayCheckIns($ngay)` - Lấy số khách hàng check-in hôm nay
- `getRecentInvoices($limit)` - Lấy danh sách hóa đơn gần đây

**Dependencies:**
- `../../../Database/db.php` - File kết nối database chung

---

### 3. **overview.js** (JavaScript - Xử Lý Frontend)
**Chức năng:** File xử lý các tương tác phía client (nếu có)

**Nhiệm vụ:**
- Cập nhật thời gian real-time (nếu có)
- Xử lý các tương tác khác

---

### 4. **overview.css** (CSS - Styling)
**Chức năng:** File định nghĩa style và giao diện

**Nhiệm vụ:**
- Styling cho các thẻ thống kê (stat cards)
- Styling cho bảng hóa đơn
- Responsive design
- Animation và transitions

---

## 🔄 Luồng Hoạt Động

### Hiển Thị Trang Tổng Quan:
1. User truy cập trang `overview.php`
2. `overview.php` gọi các hàm từ Model để lấy dữ liệu:
   - `getTotalActiveCustomers()` - Tổng khách hàng
   - `getTotalActiveEmployees()` - Tổng nhân viên
   - `getMonthlyRevenue()` - Doanh thu tháng
   - `getTodayRevenue()` - Doanh thu hôm nay
   - `getPendingInvoices()` - Hóa đơn chờ thanh toán
   - `getTodayCheckIns()` - Khách check-in hôm nay
   - `getRecentInvoices()` - Hóa đơn gần đây
3. Hiển thị dữ liệu trong các thẻ thống kê và bảng
4. JavaScript cập nhật thời gian real-time (nếu có)

---

## 📊 Cấu Trúc Database

### Các bảng được sử dụng:

#### Bảng `khachhang`:
- Đếm số khách hàng có `trang_thai = 'Hoạt động'`

#### Bảng `nhanvien`:
- Đếm số nhân viên có `trang_thai = 'Đang làm'`

#### Bảng `hoadon`:
- Tính tổng `tien_thanh_toan` với điều kiện:
  - `trang_thai = 'Đã thanh toán'`
  - Lọc theo tháng/năm hoặc ngày
- Đếm số hóa đơn có `trang_thai = 'Chờ thanh toán'`
- Lấy danh sách hóa đơn gần đây (JOIN với `khachhang`)

#### Bảng `lichsuravao`:
- Đếm số khách hàng check-in hôm nay (DISTINCT `khach_hang_id`)

---

## 📈 Các Thống Kê Hiển Thị

### 1. Tổng Khách Hàng
- **Nguồn:** Bảng `khachhang`
- **Điều kiện:** `trang_thai = 'Hoạt động'`
- **Hiển thị:** Số lượng khách hàng đang hoạt động

### 2. Tổng Nhân Viên
- **Nguồn:** Bảng `nhanvien`
- **Điều kiện:** `trang_thai = 'Đang làm'`
- **Hiển thị:** Số lượng nhân viên đang làm việc

### 3. Doanh Thu Tháng
- **Nguồn:** Bảng `hoadon`
- **Điều kiện:** 
  - `MONTH(ngay_lap) = tháng hiện tại`
  - `YEAR(ngay_lap) = năm hiện tại`
  - `trang_thai = 'Đã thanh toán'`
- **Hiển thị:** Tổng `tien_thanh_toan` (VNĐ)

### 4. Doanh Thu Hôm Nay
- **Nguồn:** Bảng `hoadon`
- **Điều kiện:**
  - `DATE(ngay_lap) = ngày hôm nay`
  - `trang_thai = 'Đã thanh toán'`
- **Hiển thị:** Tổng `tien_thanh_toan` (VNĐ)

### 5. Hóa Đơn Chờ Thanh Toán
- **Nguồn:** Bảng `hoadon`
- **Điều kiện:** `trang_thai = 'Chờ thanh toán'`
- **Hiển thị:** Số lượng hóa đơn cần xử lý

### 6. Khách Check-in Hôm Nay
- **Nguồn:** Bảng `lichsuravao`
- **Điều kiện:** `DATE(thoi_gian_vao) = ngày hôm nay`
- **Hiển thị:** Số lượng khách hàng đã check-in (DISTINCT)

### 7. Hóa Đơn Gần Đây
- **Nguồn:** Bảng `hoadon` JOIN `khachhang`
- **Điều kiện:** Lấy 5 hóa đơn mới nhất
- **Hiển thị:** 
  - Mã hóa đơn
  - Tên khách hàng
  - Ngày lập
  - Thành tiền
  - Trạng thái

---

## 🎨 Giao Diện

### Stat Cards:
- **Card Blue** - Tổng khách hàng
- **Card Green** - Tổng nhân viên
- **Card Orange** - Doanh thu tháng
- **Card Purple** - Doanh thu hôm nay
- **Card Red** - Hóa đơn chờ thanh toán
- **Card Teal** - Khách check-in hôm nay

### Status Badge trong bảng:
- **Success** (xanh) - Đã thanh toán
- **Warning** (vàng) - Chờ thanh toán
- **Danger** (đỏ) - Các trạng thái khác

---

## 📝 Lưu Ý Khi Phát Triển

1. **Kết nối Database:** Luôn sử dụng `getDBConnection()` từ Model, không tạo kết nối mới
2. **Error Handling:** Luôn sử dụng try-catch và xử lý lỗi đúng cách
3. **Performance:** 
   - Các truy vấn đã được tối ưu với COUNT(*) và COALESCE
   - Sử dụng LIMIT cho danh sách hóa đơn gần đây
4. **Data Formatting:** 
   - Số tiền được format với `number_format()`
   - Ngày tháng được format với `date()`
5. **Null Handling:** Sử dụng `??` hoặc `?:` để xử lý giá trị null

---

## 🚀 Cách Sử Dụng

1. Truy cập trang: `overview.php`
2. Xem các thống kê tổng quan:
   - Tổng khách hàng và nhân viên
   - Doanh thu tháng và hôm nay
   - Hóa đơn chờ thanh toán
   - Khách check-in hôm nay
3. Xem danh sách hóa đơn gần đây trong bảng

---

## 🔄 Cập Nhật Dữ Liệu

Trang tổng quan sẽ tự động cập nhật dữ liệu mỗi khi được load lại. Để có dữ liệu real-time, có thể:
- Thêm AJAX để refresh định kỳ
- Sử dụng WebSocket (nếu cần)
- Reload trang để cập nhật

---

## 📞 Liên Hệ

Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng liên hệ đội phát triển.

**Phiên bản:** 1.0  
**Cập nhật lần cuối:** 2024

