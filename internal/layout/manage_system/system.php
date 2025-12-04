<?php
/**
 * System View
 * Chỉ hiển thị HTML và gọi controller/model
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include controller để xử lý các action
require_once 'system_controller.php';
require_once 'system_model.php';

// ============================================
// BIẾN MESSAGE
// ============================================
$error_message = '';

// ============================================
// LẤY DANH SÁCH TÀI KHOẢN
// ============================================
try {
    $danh_sach_tai_khoan = getAllAccounts();
} catch (Exception $e) {
    $danh_sach_tai_khoan = [];
    $error_message = "Lỗi khi lấy danh sách tài khoản: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Tài Khoản</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="system.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-users-cog"></i> Quản Lý Tài Khoản</h1>
        </header>

        <main class="content">
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <div class="controls">
                <div class="search-bar">
                    <input type="text" id="search-input" placeholder="Tìm kiếm tên đăng nhập...">
                    <button id="search-btn" class="btn-search">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                </div>
                <div class="filter-options">
                    <select id="status-filter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Hoạt động">Hoạt động</option>
                        <option value="Khóa">Khóa</option>
                    </select>
                    <select id="type-filter">
                        <option value="">Tất cả loại tài khoản</option>
                        <option value="Nhân viên">Nhân viên</option>
                        <option value="Khách hàng">Khách hàng</option>
                    </select>
                </div>
            </div>
            
            <div class="table-container">
                <table id="accounts-table">
                    <thead>
                        <tr>
                            <th>Tên đăng nhập</th>
                            <th>Loại tài khoản</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Đăng nhập cuối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="accounts-tbody">
                        <?php if (empty($danh_sach_tai_khoan)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Không có tài khoản nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danh_sach_tai_khoan as $account): ?>
                            <tr data-username="<?php echo htmlspecialchars($account['ten_dang_nhap']); ?>">
                                <td><?php echo htmlspecialchars($account['ten_dang_nhap']); ?></td>
                                <td><?php echo htmlspecialchars($account['loai_tai_khoan']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $account['trang_thai'])); ?>">
                                        <?php echo htmlspecialchars($account['trang_thai']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($account['ngay_tao'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($account['ngay_cap_nhat'])); ?></td>
                                <td>
                                    <?php echo $account['lan_dang_nhap_cuoi'] ? date('d/m/Y H:i', strtotime($account['lan_dang_nhap_cuoi'])) : 'Chưa đăng nhập'; ?>
                                </td>
                                <td class="actions">
                                    <button class="btn-history" onclick="viewRaVaoHistory('<?php echo htmlspecialchars($account['ten_dang_nhap'], ENT_QUOTES); ?>')" title="Xem lịch sử ra vào">
                                        <i class="fas fa-history"></i> Lịch sử
                                    </button>
                                    <?php if ($account['trang_thai'] == 'Hoạt động'): ?>
                                        <button class="btn-lock" onclick="lockAccount('<?php echo htmlspecialchars($account['ten_dang_nhap'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-lock"></i> Khóa
                                        </button>
                                    <?php elseif ($account['trang_thai'] == 'Khóa'): ?>
                                        <button class="btn-unlock" onclick="unlockAccount('<?php echo htmlspecialchars($account['ten_dang_nhap'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-unlock"></i> Mở khóa
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Dialog Xác Nhận -->
    <div id="confirm-dialog" class="dialog-overlay">
        <div class="dialog">
            <div class="dialog-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Xác Nhận</h2>
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

    <!-- Dialog Lịch Sử Ra Vào -->
    <div id="ra-vao-history-dialog" class="dialog-overlay">
        <div class="dialog dialog-large">
            <div class="dialog-header">
                <h2><i class="fas fa-history"></i> Lịch Sử Ra Vào</h2>
                <button class="btn-close" onclick="closeRaVaoHistoryDialog()">&times;</button>
            </div>
            <div class="dialog-body">
                <div class="history-header">
                    <p><strong>Tài khoản:</strong> <span id="ra-vao-username"></span></p>
                </div>
                <div class="history-container">
                    <div id="ra-vao-loading" class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                    <div id="ra-vao-content" style="display: none;">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Thời gian vào</th>
                                    <th>Thời gian trước</th>
                                </tr>
                            </thead>
                            <tbody id="ra-vao-tbody">
                            </tbody>
                        </table>
                        <div id="ra-vao-empty" class="empty-message" style="display: none;">
                            <i class="fas fa-info-circle"></i> Tài khoản này chưa có lịch sử ra vào nào
                        </div>
                    </div>
                </div>
            </div>
            <div class="dialog-footer">
                <button class="btn-primary" onclick="closeRaVaoHistoryDialog()">Đóng</button>
            </div>
        </div>
    </div>

    <script src="system.js"></script>
</body>
</html>
