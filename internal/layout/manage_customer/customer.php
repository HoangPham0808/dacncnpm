<?php
/**
 * Customer View
 * Chỉ hiển thị HTML và gọi controller/model
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include controller để xử lý các action
require_once 'customer_controller.php';
require_once 'customer_model.php';

// ============================================
// BIẾN MESSAGE
// ============================================
$message = '';
$messageType = '';

// ============================================
// LẤY THÔNG TIN ĐỂ CHỈNH SỬA
// ============================================
$editCustomer = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $editCustomer = getCustomerById($_GET['edit']);
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// ============================================
// LẤY THÔNG TIN ĐỂ XEM CHI TIẾT
// ============================================
$viewCustomer = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    try {
        $viewCustomer = getCustomerDetailById($_GET['view']);
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// ============================================
// TÌM KIẾM VÀ LỌC DỮ LIỆU
// ============================================
// Lấy tham số tìm kiếm và lọc từ URL
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterGioiTinh = isset($_GET['gioi_tinh']) ? $_GET['gioi_tinh'] : '';

try {
    $customers = getCustomers($searchTerm, $filterGioiTinh);
} catch (Exception $e) {
    $message = 'Lỗi: ' . $e->getMessage();
    $messageType = 'error';
    $customers = [];
}

// ============================================
// LẤY DANH SÁCH PHÒNG TẬP CHO DROPDOWN
// ============================================
try {
    $danh_sach_phong_tap = getAllPhongTap();
} catch (Exception $e) {
    $danh_sach_phong_tap = [];
}

// ============================================
// LẤY MESSAGE TỪ SESSION
// ============================================
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khách Hàng - DFC Gym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏋️ Quản Lý Khách Hàng</h1>
        </div>

        <div class="main-content">
            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $messageType == 'success' ? '✅' : '❌'; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="toolbar">
                <form method="GET" class="search-box" id="searchForm">
                    <div class="search-input-wrapper">
                        <input type="text" name="search" id="searchInput" placeholder="Tìm kiếm theo tên, email, SĐT, CCCD..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="button" class="btn-clear-search <?php echo $searchTerm ? 'show' : ''; ?>" id="btnClearSearch" onclick="clearSearch()">✕</button>
                    </div>
                    <select name="gioi_tinh" id="genderFilter">
                        <option value="">Tất cả giới tính</option>
                        <option value="Nam" <?php if($filterGioiTinh == 'Nam') echo 'selected'; ?>>Nam</option>
                        <option value="Nữ" <?php if($filterGioiTinh == 'Nữ') echo 'selected'; ?>>Nữ</option>
                        <option value="Khác" <?php if($filterGioiTinh == 'Khác') echo 'selected'; ?>>Khác</option>
                    </select>
                    <button type="submit" class="btn-search">🔍 Tìm kiếm</button>
                </form>
                <button type="button" class="btn-add" onclick="openDialog('addDialog')">➕ Thêm Khách Hàng</button>
            </div>

            <div class="table-responsive">
                <?php if (count($customers) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Đăng Nhập</th>
                            <th>Họ Tên</th>
                            <th>SĐT</th>
                            <th>Giới Tính</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['khach_hang_id']); ?></td>
                            <td><?php echo htmlspecialchars($customer['ten_dang_nhap']); ?></td>
                            <td><?php echo htmlspecialchars($customer['ho_ten']); ?></td>
                            <td><?php echo htmlspecialchars($customer['sdt'] ?? ''); ?></td>
                            <td><span class="badge badge-<?php echo strtolower($customer['gioi_tinh'] ?? ''); ?>"><?php echo htmlspecialchars($customer['gioi_tinh'] ?? ''); ?></span></td>
                            <td class="action-buttons">
                                <button type="button" class="btn-view" onclick="window.location.href='customer.php?view=<?php echo $customer['khach_hang_id']; ?>'">👁️</button>
                                <button type="button" class="btn-edit" onclick="window.location.href='customer.php?edit=<?php echo $customer['khach_hang_id']; ?>'">✏️</button>
                                <button type="button" class="btn-delete" onclick="deleteCustomer(<?php echo $customer['khach_hang_id']; ?>, '<?php echo htmlspecialchars($customer['ho_ten'], ENT_QUOTES, 'UTF-8'); ?>')">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data"><p>🔭 Không tìm thấy khách hàng nào.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dialog Thêm -->
    <div id="addDialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2>➕ Thêm Khách Hàng Mới</h2>
                <button type="button" class="btn-close" onclick="closeDialog('addDialog')">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="dialog-body">
                    <div class="form-group">
                        <label>Tên Đăng Nhập *</label>
                        <input type="text" name="ten_dang_nhap" required minlength="3">
                    </div>
                    <div class="form-group">
                        <label>Mật Khẩu *</label>
                        <input type="password" name="mat_khau" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Họ và Tên *</label>
                        <input type="text" name="ho_ten" required minlength="2">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Số Điện Thoại *</label>
                        <input type="text" name="sdt" pattern="[0-9]{10,11}" title="Số điện thoại phải có 10-11 chữ số" required>
                    </div>
                    <div class="form-group">
                        <label>CCCD *</label>
                        <input type="text" name="cccd" pattern="[0-9]{9,12}" title="CCCD phải có 9-12 chữ số" required>
                    </div>
                    <div class="form-group">
                        <label>Địa Chỉ</label>
                        <input type="text" name="dia_chi">
                    </div>
                    <div class="form-group">
                        <label>Ngày Sinh</label>
                        <input type="date" name="ngay_sinh">
                    </div>
                    <div class="form-group">
                        <label>Giới Tính *</label>
                        <select name="gioi_tinh" required>
                            <option value="">-- Chọn Giới Tính --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phòng Tập</label>
                        <select name="phong_tap_id">
                            <option value="">-- Chọn phòng tập --</option>
                            <?php foreach ($danh_sach_phong_tap as $pt): ?>
                                <option value="<?php echo htmlspecialchars($pt['phong_tap_id']); ?>">
                                    <?php echo htmlspecialchars($pt['ma_phong_tap'] . ' - ' . $pt['ten_phong_tap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Người Giới Thiệu</label>
                        <input type="text" name="nguoi_gioi_thieu" placeholder="Nhập tên người giới thiệu (nếu có)">
                    </div>
                    <div class="form-group full-width">
                        <label>Ghi Chú</label>
                        <textarea name="ghi_chu" rows="3" placeholder="Nhập ghi chú (nếu có)"></textarea>
                    </div>
                </div>
                <div class="dialog-footer">
                    <button type="button" class="btn-secondary" onclick="closeDialog('addDialog')">Hủy</button>
                    <button type="submit" class="btn-primary">➕ Thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dialog Sửa -->
    <?php if ($editCustomer): ?>
    <div id="editDialog" class="dialog-overlay active">
        <div class="dialog">
            <div class="dialog-header">
                <h2>✏️ Chỉnh Sửa Khách Hàng</h2>
                <button type="button" class="btn-close" onclick="window.location.href='customer.php'">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="khach_hang_id" value="<?php echo $editCustomer['khach_hang_id']; ?>">
                <div class="dialog-body">
                    <div class="form-group">
                        <label>Tên Đăng Nhập</label>
                        <input type="text" name="ten_dang_nhap" value="<?php echo htmlspecialchars($editCustomer['ten_dang_nhap'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Mật Khẩu Mới</label>
                        <input type="password" name="mat_khau_moi" placeholder="Để trống nếu không đổi mật khẩu" minlength="6">
                        <small style="color: #8a93a5; font-size: 12px;">Chỉ nhập nếu muốn thay đổi mật khẩu</small>
                    </div>
                    <div class="form-group">
                        <label>Họ và Tên *</label>
                        <input type="text" name="ho_ten" value="<?php echo htmlspecialchars($editCustomer['ho_ten'] ?? ''); ?>" required minlength="2">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editCustomer['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số Điện Thoại *</label>
                        <input type="text" name="sdt" value="<?php echo htmlspecialchars($editCustomer['sdt'] ?? ''); ?>" pattern="[0-9]{10,11}" required>
                    </div>
                    <div class="form-group">
                        <label>CCCD *</label>
                        <input type="text" name="cccd" value="<?php echo htmlspecialchars($editCustomer['cccd'] ?? ''); ?>" pattern="[0-9]{9,12}" required>
                    </div>
                    <div class="form-group">
                        <label>Địa Chỉ</label>
                        <input type="text" name="dia_chi" value="<?php echo htmlspecialchars($editCustomer['dia_chi'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Ngày Sinh</label>
                        <input type="date" name="ngay_sinh" value="<?php echo $editCustomer['ngay_sinh'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Giới Tính *</label>
                        <select name="gioi_tinh" required>
                            <option value="">-- Chọn Giới Tính --</option>
                            <option value="Nam" <?php if(($editCustomer['gioi_tinh'] ?? '') == 'Nam') echo 'selected'; ?>>Nam</option>
                            <option value="Nữ" <?php if(($editCustomer['gioi_tinh'] ?? '') == 'Nữ') echo 'selected'; ?>>Nữ</option>
                            <option value="Khác" <?php if(($editCustomer['gioi_tinh'] ?? '') == 'Khác') echo 'selected'; ?>>Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phòng Tập</label>
                        <select name="phong_tap_id">
                            <option value="">-- Chọn phòng tập --</option>
                            <?php foreach ($danh_sach_phong_tap as $pt): ?>
                                <option value="<?php echo htmlspecialchars($pt['phong_tap_id']); ?>" 
                                    <?php echo (isset($editCustomer['phong_tap_id']) && (int)$editCustomer['phong_tap_id'] === (int)$pt['phong_tap_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pt['ma_phong_tap'] . ' - ' . $pt['ten_phong_tap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Người Giới Thiệu</label>
                        <input type="text" name="nguoi_gioi_thieu" value="<?php echo htmlspecialchars($editCustomer['nguon_gioi_thieu'] ?? ''); ?>" placeholder="Nhập tên người giới thiệu (nếu có)">
                    </div>
                    <div class="form-group full-width">
                        <label>Ghi Chú</label>
                        <textarea name="ghi_chu" rows="3" placeholder="Nhập ghi chú (nếu có)"><?php echo htmlspecialchars($editCustomer['ghi_chu'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="dialog-footer">
                    <button type="button" class="btn-secondary" onclick="window.location.href='customer.php'">Hủy</button>
                    <button type="submit" class="btn-primary">💾 Cập Nhật</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dialog Xem Chi Tiết -->
    <?php if ($viewCustomer): ?>
    <div id="viewDialog" class="dialog-overlay active">
        <div class="dialog">
            <div class="dialog-header">
                <h2>👁️ Thông Tin Chi Tiết Khách Hàng</h2>
                <button type="button" class="btn-close" onclick="window.location.href='customer.php'">✕</button>
            </div>
            <div class="dialog-body view-mode">
                <div class="info-group">
                    <label>ID Khách Hàng</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['khach_hang_id']); ?></div>
                </div>
                <div class="info-group">
                    <label>Tên Đăng Nhập</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['ten_dang_nhap'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>Mật Khẩu</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['mat_khau'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>Loại Tài Khoản</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['loai_tai_khoan'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>Họ và Tên</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['ho_ten'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>Email</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['email'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>Số Điện Thoại</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['sdt'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>CCCD</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['cccd'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>Địa Chỉ</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['dia_chi'] ?? '') ?: '-'; ?></div>
                </div>
                <div class="info-group">
                    <label>Ngày Sinh</label>
                    <div class="info-value"><?php echo $viewCustomer['ngay_sinh'] ? date('d/m/Y', strtotime($viewCustomer['ngay_sinh'])) : '-'; ?></div>
                </div>
                <div class="info-group">
                    <label>Giới Tính</label>
                    <div class="info-value">
                        <span class="badge badge-<?php echo strtolower($viewCustomer['gioi_tinh'] ?? ''); ?>"><?php echo htmlspecialchars($viewCustomer['gioi_tinh'] ?? ''); ?></span>
                    </div>
                </div>
                <div class="info-group">
                    <label>Phòng Tập</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['ten_phong_tap'] ?? '') ?: '-'; ?></div>
                </div>
                <div class="info-group">
                    <label>Người Giới Thiệu</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['nguon_gioi_thieu'] ?? '') ?: '-'; ?></div>
                </div>
                <div class="info-group">
                    <label>Trạng Thái</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['trang_thai'] ?? ''); ?></div>
                </div>
                <div class="info-group">
                    <label>Ngày Đăng Ký</label>
                    <div class="info-value"><?php echo $viewCustomer['ngay_dang_ky'] ? date('d/m/Y', strtotime($viewCustomer['ngay_dang_ky'])) : '-'; ?></div>
                </div>
                <div class="info-group">
                    <label>Ngày Tạo</label>
                    <div class="info-value"><?php echo $viewCustomer['ngay_tao'] ? date('d/m/Y H:i:s', strtotime($viewCustomer['ngay_tao'])) : '-'; ?></div>
                </div>
                <div class="info-group">
                    <label>Ngày Cập Nhật</label>
                    <div class="info-value"><?php echo $viewCustomer['ngay_cap_nhat'] ? date('d/m/Y H:i:s', strtotime($viewCustomer['ngay_cap_nhat'])) : '-'; ?></div>
                </div>
                <div class="info-group full-width">
                    <label>Ghi Chú</label>
                    <div class="info-value"><?php echo htmlspecialchars($viewCustomer['ghi_chu'] ?? '') ?: '-'; ?></div>
                </div>
            </div>
            <div class="dialog-footer">
                <button type="button" class="btn-secondary" onclick="window.location.href='customer.php'">Đóng</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dialog Xác Nhận Xóa -->
    <div id="confirm-dialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Xác Nhận Xóa</h2>
                <button class="btn-close" onclick="closeConfirmDialog()">&times;</button>
            </div>
            <div class="dialog-body">
                <p id="confirm-message"></p>
            </div>
            <div class="dialog-footer">
                <button class="btn-secondary" onclick="closeConfirmDialog()">Hủy</button>
                <button class="btn-primary" id="confirm-ok-btn">Xác Nhận</button>
            </div>
        </div>
    </div>

    <!-- Dialog Thông Báo -->
    <div id="message-dialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2 id="message-title"><i class="fas fa-info-circle"></i> Thông Báo</h2>
                <button class="btn-close" id="message-close-btn">&times;</button>
            </div>
            <div class="dialog-body">
                <p id="message-content"></p>
            </div>
            <div class="dialog-footer">
                <button class="btn-primary" onclick="closeMessageDialog()">Đóng</button>
            </div>
        </div>
    </div>

    <script src="customer.js"></script>
</body>
</html>
