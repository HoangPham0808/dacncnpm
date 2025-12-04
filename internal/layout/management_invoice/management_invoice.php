<?php
// Kết nối database
require_once __DIR__ . '/../../../Database/db.php';

// Bắt đầu session để lấy thông tin nhân viên đăng nhập
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Sử dụng kết nối từ db.php
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thông tin nhân viên hiện tại
$current_nhan_vien_id = $_SESSION['nhan_vien_id'] ?? null;
$current_vai_tro = $_SESSION['vai_tro'] ?? null;
$current_phong_tap_id = null;

// Lấy phòng tập của nhân viên hiện tại
if ($current_nhan_vien_id) {
    $stmt = $conn->prepare("SELECT phong_tap_id, ho_ten FROM NhanVien WHERE nhan_vien_id = ?");
    $stmt->bind_param("i", $current_nhan_vien_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_phong_tap_id = $row['phong_tap_id'];
    }
    $stmt->close();
}

// Lấy tham số tìm kiếm và lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$phong_tap_filter = isset($_GET['phong_tap_id']) ? $_GET['phong_tap_id'] : '';

// Tạo câu truy vấn SQL với phân quyền
$sql = "SELECT hd.*, 
           kh.ho_ten AS ten_khach_hang,
           pt.ten_phong_tap
    FROM HoaDon hd
    LEFT JOIN KhachHang kh ON hd.khach_hang_id = kh.khach_hang_id
    LEFT JOIN phongtap pt ON hd.phong_tap_id = pt.phong_tap_id
    WHERE 1=1";

$conditions = [];
$types = "";
$params = [];

// *** PHÂN QUYỀN: Lễ Tân chỉ xem hóa đơn của phòng mình ***
if ($current_vai_tro !== 'Admin') {
    $conditions[] = "hd.phong_tap_id = ?";
    $params[] = $current_phong_tap_id;
    $types .= "i";
}

// Admin có thể lọc theo phòng tập
if ($current_vai_tro === 'Admin' && !empty($phong_tap_filter)) {
    $conditions[] = "hd.phong_tap_id = ?";
    $params[] = intval($phong_tap_filter);
    $types .= "i";
}

// Tìm kiếm
if (!empty($search)) {
    $conditions[] = "(hd.ma_hoa_don LIKE ? OR kh.ho_ten LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Lọc theo trạng thái
if (!empty($status)) {
    $conditions[] = "hd.trang_thai = ?";
    $params[] = $status;
    $types .= "s";
}

// Lọc theo ngày
if (!empty($date)) {
    $conditions[] = "DATE(hd.ngay_lap) = ?";
    $params[] = $date;
    $types .= "s";
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY hd.ngay_lap DESC, hd.hoa_don_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Lấy danh sách phòng tập (chỉ cho Admin)
$phongtap_list = [];
if ($current_vai_tro === 'Admin') {
    $pt_result = $conn->query("SELECT phong_tap_id, ten_phong_tap FROM phongtap ORDER BY ten_phong_tap");
    if ($pt_result) {
        while ($row = $pt_result->fetch_assoc()) {
            $phongtap_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hóa đơn - DFC Gym</title>
    <link rel="stylesheet" href="management_invoice.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-file-invoice"></i> Quản lý hóa đơn</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Thêm hóa đơn
                </button>
                <button class="btn btn-secondary" onclick="exportAllInvoices()">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
            </div>
        </div>

        <div class="filter-section">
            <form id="filterForm" method="GET" action="management_invoice.php">
                
                <!-- Lọc theo phòng tập (chỉ hiển thị cho Admin) -->
                <?php if ($current_vai_tro === 'Admin'): ?>
                <div class="filter-group">
                    <label>Phòng tập:</label>
                    <select name="phong_tap_id" class="filter-select">
                        <option value="">Tất cả</option>
                        <?php foreach ($phongtap_list as $pt): ?>
                            <option value="<?php echo $pt['phong_tap_id']; ?>" 
                                    <?php echo $phong_tap_filter == $pt['phong_tap_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pt['ten_phong_tap']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="filter-group">
                    <label>Tìm kiếm:</label>
                    <input type="text" name="search" class="search-input" 
                           placeholder="Nhập mã hóa đơn, tên khách hàng..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Trạng thái:</label>
                    <select name="status" class="filter-select">
                        <option value="">Tất cả</option>
                        <option value="Đã thanh toán" <?php echo $status === 'Đã thanh toán' ? 'selected' : ''; ?>>Đã thanh toán</option>
                        <option value="Chờ thanh toán" <?php echo $status === 'Chờ thanh toán' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                        <option value="Hủy" <?php echo $status === 'Hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Ngày:</label>
                    <input type="date" name="date" class="filter-date" value="<?php echo htmlspecialchars($date); ?>">
                </div>
                <button type="submit" class="btn btn-filter">
                    <i class="fas fa-filter"></i> Lọc
                </button>
                <button type="button" class="btn btn-filter" onclick="resetFilter()">
                    <i class="fas fa-redo"></i> Đặt lại
                </button>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mã hóa đơn</th>
                        <th>Khách hàng</th>
                        <?php if ($current_vai_tro === 'Admin'): ?>
                        <th>Phòng tập</th>
                        <?php endif; ?>
                        <th>Ngày lập</th>
                        <th>Tổng tiền</th>
                        <th>Giảm giá</th>
                        <th>Thanh toán</th>
                        <th>PT thanh toán</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="<?php echo $current_vai_tro === 'Admin' ? '10' : '9'; ?>" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: rgba(255,255,255,0.3);"></i>
                                <p style="margin-top: 15px; color: rgba(255,255,255,0.6);">Không có dữ liệu</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($invoice = $result->fetch_assoc()): 
                            $statusClass = '';
                            switch ($invoice['trang_thai']) {
                                case 'Đã thanh toán':
                                    $statusClass = 'status-paid';
                                    break;
                                case 'Chờ thanh toán':
                                    $statusClass = 'status-pending';
                                    break;
                                case 'Hủy':
                                    $statusClass = 'status-cancelled';
                                    break;
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['ma_hoa_don']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['ten_khach_hang']?? 'N/A'); ?></td>
                                <?php if ($current_vai_tro === 'Admin'): ?>
                                <td><?php echo htmlspecialchars($invoice['ten_phong_tap'] ?? 'N/A'); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d/m/Y', strtotime($invoice['ngay_lap'])); ?></td>
                                <td><?php echo number_format($invoice['tong_tien'], 0, ',', '.'); ?>₫</td>
                                <td><?php echo number_format($invoice['giam_gia_khuyen_mai'] + $invoice['giam_gia_khac'], 0, ',', '.'); ?>₫</td>
                                <td><strong><?php echo number_format($invoice['tien_thanh_toan'], 0, ',', '.'); ?>₫</strong></td>
                                <td><?php echo htmlspecialchars($invoice['phuong_thuc_thanh_toan']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($invoice['trang_thai']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-icon" title="Xem chi tiết" onclick="viewInvoice(<?php echo $invoice['hoa_don_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon" title="Sửa" onclick="editInvoice(<?php echo $invoice['hoa_don_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button class="btn-icon btn-danger" title="Xóa" onclick="deleteInvoice(<?php echo $invoice['hoa_don_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Modal Thêm/Sửa hóa đơn -->
    <div id="invoiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm hóa đơn mới</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="invoiceForm">
                <input type="hidden" id="hoa_don_id" name="hoa_don_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Mã hóa đơn <span class="required">*</span></label>
                        <input type="text" id="ma_hoa_don" name="ma_hoa_don" required>
                    </div>
                    <div class="form-group">
                        <label>Khách hàng <span class="required">*</span></label>
                        <select id="khach_hang_id" name="khach_hang_id" required>
                            <option value="">-- Chọn khách hàng --</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Gói tập <span class="required">*</span></label>
                        <select id="goi_tap_id" name="goi_tap_id" required>
                            <option value="">-- Chọn gói tập --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nhân viên <span class="required">*</span></label>
                        <select id="nhan_vien_id" name="nhan_vien_id" required>
                            <option value="">-- Chọn nhân viên --</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ngày lập <span class="required">*</span></label>
                        <input type="date" id="ngay_lap" name="ngay_lap" required>
                    </div>
                    <div class="form-group">
                        <label>Khuyến mãi</label>
                        <select id="khuyen_mai_id" name="khuyen_mai_id">
                            <option value="">-- Không áp dụng --</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Giảm giá khác</label>
                        <input type="number" id="giam_gia_khac" name="giam_gia_khac" step="0.01" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea id="ghi_chu" name="ghi_chu" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Chi tiết hóa đơn -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Chi tiết hóa đơn</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div id="invoiceDetail" class="invoice-detail"></div>
        </div>
    </div>

    <script src="management_invoice.js"></script>

<!-- Modal Thanh Toán -->
<div id="paymentModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Thanh toán hóa đơn</h2>
            <span class="close" onclick="closePaymentModal()">&times;</span>
        </div>
        
        <div class="payment-container">
            <!-- Thông tin hóa đơn -->
            <div class="payment-invoice-summary">
                <h3><i class="fas fa-file-invoice"></i> Thông tin thanh toán</h3>
                <div class="invoice-info-grid">
                    <div class="info-item">
                        <label>Mã hóa đơn:</label>
                        <span id="payment_ma_hoa_don">-</span>
                    </div>
                    <div class="info-item">
                        <label>Khách hàng:</label>
                        <span id="payment_khach_hang">-</span>
                    </div>
                    <div class="info-item">
                        <label>Gói tập:</label>
                        <span id="payment_goi_tap">-</span>
                    </div>
                    <div class="info-item highlight">
                        <label>Số tiền thanh toán:</label>
                        <span id="payment_tien_thanh_toan" class="amount">0₫</span>
                    </div>
                </div>
            </div>

            <!-- Phương thức thanh toán -->
            <div class="payment-methods-section">
                <h3><i class="fas fa-credit-card"></i> Chọn phương thức thanh toán</h3>
                <div class="payment-methods-grid">
                    <!-- Ngân hàng -->
                    <div class="payment-method-card" data-method="bank" onclick="selectPaymentMethod('bank')">
                        <div class="method-icon bank">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="method-info">
                            <h4>Ngân hàng</h4>
                            <p>Chuyển khoản ngân hàng</p>
                        </div>
                        <div class="method-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <!-- Momo -->
                    <div class="payment-method-card" data-method="momo" onclick="selectPaymentMethod('momo')">
                        <div class="method-icon momo">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="method-info">
                            <h4>Momo</h4>
                            <p>Ví điện tử Momo</p>
                        </div>
                        <div class="method-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <!-- ZaloPay -->
                    <div class="payment-method-card" data-method="zalopay" onclick="selectPaymentMethod('zalopay')">
                        <div class="method-icon zalopay">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="method-info">
                            <h4>ZaloPay</h4>
                            <p>Ví điện tử ZaloPay</p>
                        </div>
                        <div class="method-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <!-- Visa/Card -->
                    <div class="payment-method-card" data-method="card" onclick="selectPaymentMethod('card')">
                        <div class="method-icon card">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="method-info">
                            <h4>Visa</h4>
                            <p>Thẻ tín dụng/ghi nợ</p>
                        </div>
                        <div class="method-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <!-- Tiền mặt -->
                    <div class="payment-method-card" data-method="cash" onclick="selectPaymentMethod('cash')">
                        <div class="method-icon cash">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="method-info">
                            <h4>Tiền mặt</h4>
                            <p>Thanh toán trực tiếp</p>
                        </div>
                        <div class="method-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div id="bank_details" class="payment-details">
                <div class="qr-payment-section">
                    <div class="qr-code-container">
                        <img src="../../../User/assets/img/bank.jpg" alt="QR Code" class="qr-image">
                        <p class="qr-hint">Quét mã QR để thanh toán</p>
                    </div>
                    <div class="bank-info">
                        <h4>Thông tin chuyển khoản</h4>
                        <div class="bank-detail-item">
                            <label>Ngân hàng:</label>
                            <strong>MB Bank</strong>
                        </div>
                        <div class="bank-detail-item">
                            <label>Số tài khoản:</label>
                            <strong>0365102985</strong>
                            <button class="copy-btn" onclick="copyToClipboard('0365102985')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="bank-detail-item">
                            <label>Chủ tài khoản:</label>
                            <strong>DINH BA VU</strong>
                        </div>
                        <div class="bank-detail-item">
                            <label>Nội dung:</label>
                            <strong id="bank_noi_dung">DFC + Mã HĐ</strong>
                            <button class="copy-btn" onclick="copyToClipboard(document.getElementById('bank_noi_dung').textContent)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Momo -->
            <div id="momo_details" class="payment-details" style="display: none;">
                <div class="qr-payment-section">
                    <div class="qr-code-container">
                        <img src="../../../User/assets/img/momo.jpg" alt="Momo QR" class="qr-image">
                        <p class="qr-hint">Quét mã QR bằng app Momo</p>
                    </div>
                    <div class="bank-info">
                        <h4>Thông tin Momo</h4>
                        <div class="bank-detail-item">
                            <label>Số điện thoại:</label>
                            <strong>0365102985</strong>
                        </div>
                        <div class="bank-detail-item">
                            <label>Tên:</label>
                            <strong>DINH BA VU</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ZaloPay -->
            <div id="zalopay_details" class="payment-details" style="display: none;">
                <div class="qr-payment-section">
                    <div class="qr-code-container">
                        <img src="../../../User/assets/img/zalopay.jpg" alt="ZaloPay QR" class="qr-image">
                        <p class="qr-hint">Quét mã QR bằng app ZaloPay</p>
                    </div>
                    <div class="bank-info">
                        <h4>Thông tin ZaloPay</h4>
                        <div class="bank-detail-item">
                            <label>Số điện thoại:</label>
                            <strong>0365102985</strong>
                        </div>
                        <div class="bank-detail-item">
                            <label>Tên:</label>
                            <strong>DINH BA VU</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card/Visa -->
            <div id="card_details" class="payment-details" style="display: none;">
                <div class="card-payment-notice">
                    <i class="fas fa-info-circle"></i>
                    <p>Thanh toán bằng thẻ sẽ được xử lý qua cổng thanh toán an toàn. Vui lòng chọn trạng thái "Chờ thanh toán" và cập nhật sau khi khách hàng hoàn tất thanh toán.</p>
                </div>
            </div>

            <!-- Tiền mặt -->
            <div id="cash_details" class="payment-details" style="display: none;">
                <div class="cash-payment-notice success">
                    <i class="fas fa-check-circle"></i>
                    <p>Thanh toán tiền mặt sẽ tự động đánh dấu hóa đơn là "Đã thanh toán". Vui lòng xác nhận đã nhận đủ tiền từ khách hàng.</p>
                </div>
            </div>

            <!-- Trạng thái thanh toán -->
            <div class="payment-status-section">
                <label for="payment_trang_thai">Trạng thái thanh toán:</label>
                <select id="payment_trang_thai" class="status-select">
                    <option value="Chờ thanh toán">Chờ thanh toán</option>
                    <option value="Đã thanh toán">Đã thanh toán</option>
                    <option value="Hủy">Hủy</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="payment-actions">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-success" onclick="markAsPaid()">
                    <i class="fas fa-check"></i> Đã thanh toán
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmPayment()">
                    <i class="fas fa-save"></i> Xác nhận
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Đã sao chép: ' + text, 'success');
    }).catch(err => {
        showAlert('Không thể sao chép', 'error');
    });
}
</script>
</body>
</html>
<?php
$conn->close();
?>