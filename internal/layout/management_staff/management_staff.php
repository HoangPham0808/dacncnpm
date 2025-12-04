<?php
require_once __DIR__ . '/managment_function.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Nhân Viên - Gym Management</title>
    <link rel="stylesheet" href="management_staff.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍💼 Quản Lý Nhân Viên</h1>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Message -->
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Toolbar -->
            <div class="toolbar">
                <form method="GET" class="search-box">
                    <div class="search-input-wrapper">
                        <input type="text" 
                               id="searchInput"
                               name="search" 
                               placeholder="Tìm kiếm theo tên đăng nhập, họ tên, email, SĐT, chức vụ..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="button" 
                                id="btnClearSearch" 
                                class="btn-clear-search <?php echo !empty($searchTerm) ? 'show' : ''; ?>"
                                onclick="clearSearch()">×</button>
                    </div>
                    <select name="gender" id="genderFilter">
                        <option value="">Tất cả giới tính</option>
                        <option value="Nam" <?php echo $genderFilter == 'Nam' ? 'selected' : ''; ?>>Nam</option>
                        <option value="Nữ" <?php echo $genderFilter == 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                        <option value="Khác" <?php echo $genderFilter == 'Khác' ? 'selected' : ''; ?>>Khác</option>
                    </select>
                    <select name="role" id="roleFilter">
                        <option value="">Tất cả vai trò</option>
                        <option value="PR" <?php echo $roleFilter == 'PR' ? 'selected' : ''; ?>>PR</option>
                        <option value="Lễ Tân" <?php echo $roleFilter == 'Lễ Tân' ? 'selected' : ''; ?>>Lễ Tân</option>
                        <option value="PT" <?php echo $roleFilter == 'PT' ? 'selected' : ''; ?>>PT</option>
                    </select>
                    <button type="submit" class="btn-search">🔍 Tìm kiếm</button>
                </form>
                <button class="btn-add" onclick="openDialog('addDialog')">➕ Thêm Nhân Viên</button>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Đăng Nhập</th>
                            <th>Họ Tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Phòng Tập</th>
                            <th>Vai Trò</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['nhan_vien_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_dang_nhap']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sdt']); ?></td>
                                    <td><?php echo $row['ten_phong_tap'] ? htmlspecialchars($row['ten_phong_tap']) : '<span style="color: #8a93a5;">Chưa phân</span>'; ?></td>
                                    <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $row['vai_tro'])); ?>"><?php echo htmlspecialchars($row['vai_tro']); ?></span></td>
                                    <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $row['trang_thai'])); ?>"><?php echo htmlspecialchars($row['trang_thai']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-view" onclick='viewEmployee(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>👁️</button>
                                            <button class="btn-edit" onclick='editEmployee(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>✏️</button>
                                            <button class="btn-delete" onclick="deleteEmployee(<?php echo $row['nhan_vien_id']; ?>)">🗑️</button>
                                            <button class="btn-timekeep" onclick="viewAttendance(<?php echo $row['nhan_vien_id']; ?>)">📅</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-data">
                                    <p>Không có dữ liệu nhân viên</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Dialog -->
    <div id="addDialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2>➕ Thêm Nhân Viên Mới</h2>
                <button class="btn-close" onclick="closeDialog('addDialog')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="dialog-body">
                    <div class="form-group">
                        <label>Tên Đăng Nhập <span style="color: #f44336;">*</span></label>
                        <input type="text" name="tenDangNhap" required>
                    </div>
                    <div class="form-group">
                        <label>Mật Khẩu <span style="color: #f44336;">*</span></label>
                        <input type="password" name="matKhau" required>
                    </div>
                    <div class="form-group">
                        <label>Họ Tên <span style="color: #f44336;">*</span></label>
                        <input type="text" name="hoTen" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span style="color: #f44336;">*</span></label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Số Điện Thoại</label>
                        <input type="text" name="sdt" maxlength="11">
                    </div>
                    <div class="form-group">
                        <label>CCCD</label>
                        <input type="text" name="cccd" maxlength="12">
                    </div>
                    <div class="form-group">
                        <label>Ngày Sinh</label>
                        <input type="date" name="ngaySinh">
                    </div>
                    <div class="form-group">
                        <label>Giới Tính</label>
                        <select name="gioiTinh">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phòng Tập</label>
                        <select name="phongTapId">
                            <option value="">-- Chọn phòng tập --</option>
                            <?php foreach($phongTapList as $pt): ?>
                                <option value="<?php echo $pt['phong_tap_id']; ?>">
                                    <?php echo htmlspecialchars($pt['ma_phong_tap'] . ' - ' . $pt['ten_phong_tap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vai Trò <span style="color: #f44336;">*</span></label>
                        <select name="vaiTro" required>
                            <option value="PR">PR</option>
                            <option value="Lễ Tân">Lễ Tân</option>
                            <option value="PT">PT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày Vào Làm <span style="color: #f44336;">*</span></label>
                        <input type="date" name="ngayVaoLam" required>
                    </div>
                    <div class="form-group">
                        <label>Lương Cơ Bản (VNĐ)</label>
                        <input type="number" name="luongCoBan" step="0.01" min="0">
                    </div>
                    <div class="form-group full-width">
                        <label>Địa Chỉ</label>
                        <textarea name="diaChi" rows="3"></textarea>
                    </div>
                </div>
                <div class="dialog-footer">
                    <button type="button" class="btn-secondary" onclick="closeDialog('addDialog')">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Dialog -->
    <div id="editDialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2>✏️ Chỉnh Sửa Nhân Viên</h2>
                <button class="btn-close" onclick="closeDialog('editDialog')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="dialog-body">
                    <div class="form-group">
                        <label>Tên Đăng Nhập</label>
                        <input type="text" id="edit_tenDangNhap" readonly>
                    </div>
                    <div class="form-group">
                        <label>Họ Tên <span style="color: #f44336;">*</span></label>
                        <input type="text" id="edit_hoTen" name="hoTen" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span style="color: #f44336;">*</span></label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Số Điện Thoại</label>
                        <input type="text" id="edit_sdt" name="sdt" maxlength="11">
                    </div>
                    <div class="form-group">
                        <label>CCCD</label>
                        <input type="text" id="edit_cccd" name="cccd" maxlength="12">
                    </div>
                    <div class="form-group">
                        <label>Ngày Sinh</label>
                        <input type="date" id="edit_ngaySinh" name="ngaySinh">
                    </div>
                    <div class="form-group">
                        <label>Giới Tính</label>
                        <select id="edit_gioiTinh" name="gioiTinh">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phòng Tập</label>
                        <select id="edit_phongTapId" name="phongTapId">
                            <option value="">-- Chọn phòng tập --</option>
                            <?php foreach($phongTapList as $pt): ?>
                                <option value="<?php echo $pt['phong_tap_id']; ?>">
                                    <?php echo htmlspecialchars($pt['ma_phong_tap'] . ' - ' . $pt['ten_phong_tap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vai Trò <span style="color: #f44336;">*</span></label>
                        <select id="edit_vaiTro" name="vaiTro" required>
                            <option value="PR">PR</option>
                            <option value="Lễ Tân">Lễ Tân</option>
                            <option value="PT">PT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày Vào Làm <span style="color: #f44336;">*</span></label>
                        <input type="date" id="edit_ngayVaoLam" name="ngayVaoLam" required>
                    </div>
                    <div class="form-group">
                        <label>Lương Cơ Bản (VNĐ)</label>
                        <input type="number" id="edit_luongCoBan" name="luongCoBan" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Trạng Thái</label>
                        <select id="edit_trangThai" name="trangThai">
                            <option value="Đang làm">Đang làm</option>
                            <option value="Nghỉ phép">Nghỉ phép</option>
                            <option value="Đã nghỉ">Đã nghỉ</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Địa Chỉ</label>
                        <textarea id="edit_diaChi" name="diaChi" rows="3"></textarea>
                    </div>
                </div>
                <div class="dialog-footer">
                    <button type="button" class="btn-secondary" onclick="closeDialog('editDialog')">Hủy</button>
                    <button type="submit" class="btn-primary">Cập Nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Dialog -->
    <div id="viewDialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2>👁️ Thông Tin Chi Tiết Nhân Viên</h2>
                <button class="btn-close" onclick="closeDialog('viewDialog')">×</button>
            </div>
            <div class="dialog-body view-mode">
                <div class="info-group">
                    <label>ID Nhân Viên</label>
                    <div class="info-value" id="view_id"></div>
                </div>
                <div class="info-group">
                    <label>Tên Đăng Nhập</label>
                    <div class="info-value" id="view_tenDangNhap"></div>
                </div>
                <div class="info-group">
                    <label>Họ Tên</label>
                    <div class="info-value" id="view_hoTen"></div>
                </div>
                <div class="info-group">
                    <label>Email</label>
                    <div class="info-value" id="view_email"></div>
                </div>
                <div class="info-group">
                    <label>Số Điện Thoại</label>
                    <div class="info-value" id="view_sdt"></div>
                </div>
                <div class="info-group">
                    <label>CCCD</label>
                    <div class="info-value" id="view_cccd"></div>
                </div>
                <div class="info-group">
                    <label>Ngày Sinh</label>
                    <div class="info-value" id="view_ngaySinh"></div>
                </div>
                <div class="info-group">
                    <label>Giới Tính</label>
                    <div class="info-value" id="view_gioiTinh"></div>
                </div>
                <div class="info-group">
                    <label>Phòng Tập</label>
                    <div class="info-value" id="view_phongTap"></div>
                </div>
                <div class="info-group">
                    <label>Vai Trò</label>
                    <div class="info-value" id="view_vaiTro"></div>
                </div>
                <div class="info-group">
                    <label>Ngày Vào Làm</label>
                    <div class="info-value" id="view_ngayVaoLam"></div>
                </div>
                <div class="info-group">
                    <label>Lương Cơ Bản</label>
                    <div class="info-value" id="view_luongCoBan"></div>
                </div>
                <div class="info-group">
                    <label>Trạng Thái</label>
                    <div class="info-value" id="view_trangThai"></div>
                </div>
                <div class="info-group">
                    <label>Ngày Tạo</label>
                    <div class="info-value" id="view_ngayTao"></div>
                </div>
                <div class="info-group">
                    <label>Ngày Cập Nhật</label>
                    <div class="info-value" id="view_ngayCapNhat"></div>
                </div>
                <div class="info-group full-width">
                    <label>Địa Chỉ</label>
                    <div class="info-value" id="view_diaChi"></div>
                </div>
            </div>
            <div class="dialog-footer">
                <button type="button" class="btn-secondary" onclick="closeDialog('viewDialog')">Đóng</button>
            </div>
        </div>
    </div>

    <!-- View Cham Cong Dialog -->
    <div id="viewChamCongDialog" class="dialog-overlay">
        <div class="dialog large-dialog">
            <div class="dialog-header">
                <h2>📅 Bảng Chấm Công - <span id="cc_employee_name">Nhân viên</span></h2>
                <button class="btn-close" onclick="closeDialog('viewChamCongDialog')">×</button>
            </div>
            <form id="addChamCongForm" onsubmit="return submitAddChamCong(event)" style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                <input type="hidden" id="cc_nhanVienId" name="nhanVienId" value="">
                <div style="min-width:140px;">
                    <label>Trạng thái</label>
                    <select id="cc_trangThai" name="trangThai">
                        <option value="Có mặt">Có mặt</option>
                        <option value="Nghỉ phép">Nghỉ phép</option>
                        <option value="Đi muộn">Đi muộn</option>
                    </select>
                </div>
                <div style="min-width:150px;">
                    <label>Ngày</label>
                    <input type="date" id="cc_ngayChamCong" name="ngayChamCong" required>
                </div>
                <div style="min-width:120px;">
                    <label>Giờ vào</label>
                    <input type="time" id="cc_gioVao" name="gioVao">
                </div>
                <div style="min-width:120px;">
                    <label>Giờ ra</label>
                    <input type="time" id="cc_gioRa" name="gioRa">
                </div>
                <div style="flex:1; min-width:160px;">
                    <label>Ghi chú</label>
                    <input type="text" id="cc_ghiChu" name="ghiChu">
                </div>
                <div style="min-width:120px;">
                    <button type="submit" class="btn-primary" style="width:100%;">Chấm công</button>
                </div>
                <div style="min-width:140px;">
                    <button type="button" class="btn-secondary" style="width:100%;" onclick="openBangLuongDialog()">💰 Xem bảng lương</button>
                </div>
            </form>
            <div class="dialog-body" style="display:block;margin-top:-30px;">
                <div id="cc_toast_container"></div>
                <div style="overflow:auto; max-height:38vh;">
                    <table class="chamcong-table" style="min-width:760px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ngày</th>
                                <th>Giờ vào</th>
                                <th>Giờ ra</th>
                                <th>Số giờ</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody id="chamCongTableBody">
                            <tr><td colspan="7" class="no-data">Chưa chọn nhân viên</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="dialog-footer">
                <button class="btn-secondary" onclick="closeDialog('viewChamCongDialog')">Đóng</button>
            </div>
        </div>
    </div>

    <!-- View Bang Luong Dialog -->
   <!-- View Bang Luong Dialog -->
<div id="viewBangLuongDialog" class="dialog-overlay">
    <div class="dialog">
        <div class="dialog-header">
            <h2>💰 Bảng Lương - <span id="bl_employee_name">Nhân viên</span></h2>
            <button class="btn-close" onclick="closeDialog('viewBangLuongDialog')">×</button>
        </div>
        <div class="dialog-body">
            <div class="bangluong-search-form">
                <div>
                    <div>
                        <label>📅 Tháng</label>
                        <input type="number" 
                               id="bl_thang" 
                               min="1" 
                               max="12" 
                               placeholder="1-12"
                               style="width: 100px;">
                    </div>
                    <div>
                        <label>📆 Năm</label>
                        <input type="number" 
                               id="bl_nam" 
                               min="2000" 
                               max="2100"
                               placeholder="YYYY"
                               style="width: 120px;">
                    </div>
                    <button class="btn-primary" 
                            onclick="loadBangLuong()" 
                            style="padding: 10px 24px; height: 42px;">
                        🔍 Xem
                    </button>
                </div>
            </div>

            <div class="bangluong-table-wrapper">
                <table class="bangluong-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tháng</th>
                            <th style="text-align: center;">Năm</th>
                            <th style="text-align: right;">Lương</th>
                            <th style="text-align: right;">Thưởng</th>
                            <th style="text-align: right;">Khấu trừ</th>
                            <th style="text-align: right;">Thực lĩnh</th>
                            <th style="text-align: center;">Trạng thái</th>
                            <th style="text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="bangLuongTableBody">
                        <tr>
                            <td colspan="9" style="padding: 60px 20px; text-align: center; color: #8a93a5;">
                                <div style="font-size: 64px; margin-bottom: 15px; opacity: 0.5;">📊</div>
                                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Chưa có dữ liệu</div>
                                <div style="font-size: 14px;">Chọn tháng và năm rồi nhấn "Xem" để xem bảng lương</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn-secondary" onclick="closeDialog('viewBangLuongDialog')">
                Đóng
            </button>
        </div>
    </div>
</div>

    <script src="management_staff.js"></script>
</body>
</html>

<?php
$conn->close();
?>