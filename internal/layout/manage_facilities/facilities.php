<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ============================================
// KẾT NỐI DATABASE
// ============================================
try {
    $pdo = new PDO('mysql:host=localhost;dbname=dfcgym;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

// ============================================
// BIẾN THÔNG BÁO
// ============================================
$message = "";
$message_type = "";

// ============================================
// XỬ LÝ THÊM THIẾT BỊ
// ============================================
if (isset($_POST['them_thiet_bi'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO ThietBi 
                               (ma_thiet_bi, ten_thiet_bi, loai_thiet_bi, ngay_mua, gia_mua, nha_cung_cap, vi_tri, trang_thai, ghi_chu) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['ma_thiet_bi'],
            $_POST['ten_thiet_bi'],
            $_POST['loai_thiet_bi'],
            $_POST['ngay_mua'],
            $_POST['gia_mua'],
            $_POST['nha_cung_cap'],
            $_POST['vi_tri'],
            $_POST['trang_thai'],
            $_POST['ghi_chu'] ?? ''
        ]);
        $message = "Thêm thiết bị thành công!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// XỬ LÝ SỬA THIẾT BỊ
// ============================================
if (isset($_POST['sua_thiet_bi'])) {
    try {
        $stmt = $pdo->prepare("UPDATE ThietBi SET 
                               ma_thiet_bi = ?, ten_thiet_bi = ?, loai_thiet_bi = ?, ngay_mua = ?, 
                               gia_mua = ?, nha_cung_cap = ?, vi_tri = ?, trang_thai = ?, ghi_chu = ?
                               WHERE thiet_bi_id = ?");
        $stmt->execute([
            $_POST['ma_thiet_bi'],
            $_POST['ten_thiet_bi'],
            $_POST['loai_thiet_bi'],
            $_POST['ngay_mua'],
            $_POST['gia_mua'],
            $_POST['nha_cung_cap'],
            $_POST['vi_tri'],
            $_POST['trang_thai'],
            $_POST['ghi_chu'] ?? '',
            $_POST['thiet_bi_id']
        ]);
        $message = "Cập nhật thiết bị thành công!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// XỬ LÝ XÓA THIẾT BỊ
// ============================================
if (isset($_GET['xoa'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM ThietBi WHERE thiet_bi_id = ?");
        $stmt->execute([$_GET['xoa']]);
        $message = "Xóa thiết bị thành công!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// XỬ LÝ THÊM LỊCH BẢO TRÌ
// ============================================
if (isset($_POST['them_bao_tri'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO BaoTriThietBi 
                               (thiet_bi_id, ngay_bao_tri, loai_bao_tri, mo_ta, chi_phi, nhan_vien_thuc_hien_id, trang_thai) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['thiet_bi_id'],
            $_POST['ngay_bao_tri'],
            $_POST['loai_bao_tri'],
            $_POST['mo_ta'],
            $_POST['chi_phi'],
            $_POST['nhan_vien_thuc_hien_id'],
            $_POST['trang_thai']
        ]);
        $message = "Thêm lịch bảo trì thành công!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// XỬ LÝ SỬA LỊCH BẢO TRÌ
// ============================================
if (isset($_POST['sua_bao_tri'])) {
    try {
        $stmt = $pdo->prepare("UPDATE BaoTriThietBi SET 
                               ngay_bao_tri = ?, loai_bao_tri = ?, mo_ta = ?, chi_phi = ?, 
                               nhan_vien_thuc_hien_id = ?, trang_thai = ?
                               WHERE bao_tri_id = ?");
        $stmt->execute([
            $_POST['ngay_bao_tri'],
            $_POST['loai_bao_tri'],
            $_POST['mo_ta'],
            $_POST['chi_phi'],
            $_POST['nhan_vien_thuc_hien_id'],
            $_POST['trang_thai'],
            $_POST['bao_tri_id']
        ]);
        $message = "Cập nhật lịch bảo trì thành công!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// LẤY TAB HIỆN TẠI
// ============================================
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'thietbi';

// ============================================
// TÌM KIẾM VÀ LỌC THIẾT BỊ
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';

// Xây dựng điều kiện WHERE với prepared statements
$where_conditions = [];
$params = [];

if ($search != '') {
    $where_conditions[] = "(ma_thiet_bi LIKE ? OR ten_thiet_bi LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($filter_status != '') {
    $where_conditions[] = "trang_thai = ?";
    $params[] = $filter_status;
}

if ($filter_type != '') {
    $where_conditions[] = "loai_thiet_bi = ?";
    $params[] = $filter_type;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// ============================================
// LẤY DANH SÁCH THIẾT BỊ
// ============================================
try {
    $sql_thietbi = "SELECT * FROM ThietBi $where_clause ORDER BY thiet_bi_id DESC";
    $stmt = $pdo->prepare($sql_thietbi);
    $stmt->execute($params);
    $danh_sach_thiet_bi = $stmt->fetchAll();
} catch (PDOException $e) {
    $danh_sach_thiet_bi = [];
    $message = "Lỗi khi lấy danh sách thiết bị: " . $e->getMessage();
    $message_type = "error";
}

// ============================================
// LẤY DANH SÁCH BẢO TRÌ
// ============================================
try {
    $stmt = $pdo->query("SELECT bt.*, tb.ma_thiet_bi, tb.ten_thiet_bi 
                         FROM BaoTriThietBi bt 
                         LEFT JOIN ThietBi tb ON bt.thiet_bi_id = tb.thiet_bi_id 
                         ORDER BY bt.ngay_bao_tri DESC");
    $danh_sach_bao_tri = $stmt->fetchAll();
} catch (PDOException $e) {
    $danh_sach_bao_tri = [];
    $message = "Lỗi khi lấy danh sách bảo trì: " . $e->getMessage();
    $message_type = "error";
}

// ============================================
// LẤY THÔNG TIN THIẾT BỊ ĐỂ SỬA
// ============================================
$edit_thietbi = null;
if (isset($_GET['sua_tb'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ThietBi WHERE thiet_bi_id = ?");
        $stmt->execute([$_GET['sua_tb']]);
        $edit_thietbi = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "Lỗi khi lấy thông tin thiết bị: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// LẤY THÔNG TIN BẢO TRÌ ĐỂ SỬA
// ============================================
$edit_baotri = null;
if (isset($_GET['sua_bt'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM BaoTriThietBi WHERE bao_tri_id = ?");
        $stmt->execute([$_GET['sua_bt']]);
        $edit_baotri = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "Lỗi khi lấy thông tin bảo trì: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// LẤY CHI TIẾT THIẾT BỊ
// ============================================
$detail_thietbi = null;
if (isset($_GET['xem'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ThietBi WHERE thiet_bi_id = ?");
        $stmt->execute([$_GET['xem']]);
        $detail_thietbi = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "Lỗi khi lấy chi tiết thiết bị: " . $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// LẤY DANH SÁCH THIẾT BỊ CHO DROPDOWN
// ============================================
try {
    $stmt = $pdo->query("SELECT * FROM ThietBi ORDER BY ma_thiet_bi");
    $danh_sach_thiet_bi_dropdown = $stmt->fetchAll();
} catch (PDOException $e) {
    $danh_sach_thiet_bi_dropdown = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Thiết Bị</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="facilities.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-dumbbell"></i> Quản Lý Thiết Bị Gym</h1>
        </header>

        <main class="content">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <a href="?tab=thietbi" class="tab-btn <?php echo $tab == 'thietbi' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i> Danh Sách Thiết Bị
                </a>
                <a href="?tab=baotri" class="tab-btn <?php echo $tab == 'baotri' ? 'active' : ''; ?>">
                    <i class="fas fa-wrench"></i> Lịch Bảo Trì
                </a>
            </div>

            <!-- Tab Thiết Bị -->
            <?php if ($tab == 'thietbi'): ?>
            <div class="tab-content">
                <!-- Form tìm kiếm và lọc -->
                <form method="GET" class="controls">
                    <input type="hidden" name="tab" value="thietbi">
                    <div class="search-bar">
                        <input type="text" name="search" placeholder="Tìm kiếm thiết bị..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                    <div class="filter-options">
                        <select name="filter_status" onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Đang sử dụng" <?php echo $filter_status == 'Đang sử dụng' ? 'selected' : ''; ?>>Đang sử dụng</option>
                            <option value="Bảo trì" <?php echo $filter_status == 'Bảo trì' ? 'selected' : ''; ?>>Bảo trì</option>
                            <option value="Hỏng" <?php echo $filter_status == 'Hỏng' ? 'selected' : ''; ?>>Hỏng</option>
                            <option value="Thanh lý" <?php echo $filter_status == 'Thanh lý' ? 'selected' : ''; ?>>Thanh lý</option>
                        </select>
                        <select name="filter_type" onchange="this.form.submit()">
                            <option value="">Tất cả loại thiết bị</option>
                            <option value="Cardio" <?php echo $filter_type == 'Cardio' ? 'selected' : ''; ?>>Cardio</option>
                            <option value="Tạ" <?php echo $filter_type == 'Tạ' ? 'selected' : ''; ?>>Tạ</option>
                            <option value="Kiểm tra" <?php echo $filter_type == 'Kiểm tra' ? 'selected' : ''; ?>>Kiểm tra</option>
                        </select>
                        <a href="?tab=thietbi&them=1" class="btn-add">
                            <i class="fas fa-plus"></i> Thêm Thiết Bị
                        </a>
                    </div>
                </form>

                <!-- Bảng thiết bị -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã TB</th>
                                <th>Tên Thiết Bị</th>
                                <th>Loại</th>
                                <th>Ngày Mua</th>
                                <th>Giá Mua</th>
                                <th>Nhà Cung Cấp</th>
                                <th>Vị Trí</th>
                                <th>Trạng Thái</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danh_sach_thiet_bi)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Không có thiết bị nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danh_sach_thiet_bi as $row): ?>
                                <tr>
                                    <td data-label="Mã TB"><?php echo htmlspecialchars($row['ma_thiet_bi']); ?></td>
                                    <td data-label="Tên Thiết Bị"><?php echo htmlspecialchars($row['ten_thiet_bi']); ?></td>
                                    <td data-label="Loại"><?php echo htmlspecialchars($row['loai_thiet_bi']); ?></td>
                                    <td data-label="Ngày Mua"><?php echo date('d/m/Y', strtotime($row['ngay_mua'])); ?></td>
                                    <td data-label="Giá Mua"><?php echo number_format($row['gia_mua'], 0, ',', '.'); ?> đ</td>
                                    <td data-label="Nhà Cung Cấp"><?php echo htmlspecialchars($row['nha_cung_cap']); ?></td>
                                    <td data-label="Vị Trí"><?php echo htmlspecialchars($row['vi_tri']); ?></td>
                                    <td data-label="Trạng Thái">
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['trang_thai'])); ?>">
                                            <?php echo htmlspecialchars($row['trang_thai']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Thao Tác" class="actions">
                                        <a href="?tab=thietbi&xem=<?php echo $row['thiet_bi_id']; ?>" class="btn-action btn-view" title="Xem">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?tab=thietbi&sua_tb=<?php echo $row['thiet_bi_id']; ?>" class="btn-action btn-edit" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?tab=baotri&them_bt=<?php echo $row['thiet_bi_id']; ?>" class="btn-action btn-maintenance" title="Bảo trì">
                                            <i class="fas fa-wrench"></i>
                                        </a>
                                        <a href="?tab=thietbi&xoa=<?php echo $row['thiet_bi_id']; ?>" class="btn-action btn-delete" 
                                           onclick="return confirm('Bạn có chắc muốn xóa thiết bị này?')" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab Bảo Trì -->
            <?php if ($tab == 'baotri'): ?>
            <div class="tab-content">
                <div class="controls">
                    <a href="?tab=baotri&them_bt=1" class="btn-add">
                        <i class="fas fa-plus"></i> Thêm Lịch Bảo Trì
                    </a>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã TB</th>
                                <th>Tên Thiết Bị</th>
                                <th>Ngày Bảo Trì</th>
                                <th>Loại</th>
                                <th>Mô Tả</th>
                                <th>Chi Phí</th>
                                <th>Trạng Thái</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danh_sach_bao_tri)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Không có lịch bảo trì nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danh_sach_bao_tri as $row): ?>
                                <tr>
                                    <td data-label="Mã TB"><?php echo htmlspecialchars($row['ma_thiet_bi'] ?? ''); ?></td>
                                    <td data-label="Tên Thiết Bị"><?php echo htmlspecialchars($row['ten_thiet_bi'] ?? ''); ?></td>
                                    <td data-label="Ngày Bảo Trì"><?php echo date('d/m/Y', strtotime($row['ngay_bao_tri'])); ?></td>
                                    <td data-label="Loại"><?php echo htmlspecialchars($row['loai_bao_tri']); ?></td>
                                    <td data-label="Mô Tả"><?php echo htmlspecialchars($row['mo_ta']); ?></td>
                                    <td data-label="Chi Phí"><?php echo number_format($row['chi_phi'], 0, ',', '.'); ?> đ</td>
                                    <td data-label="Trạng Thái">
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['trang_thai'])); ?>">
                                            <?php echo htmlspecialchars($row['trang_thai']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Thao Tác" class="actions">
                                        <a href="?tab=baotri&sua_bt=<?php echo $row['bao_tri_id']; ?>" class="btn-action btn-edit" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Thêm/Sửa Thiết Bị -->
    <?php if (isset($_GET['them']) || $edit_thietbi): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-<?php echo $edit_thietbi ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $edit_thietbi ? 'Sửa' : 'Thêm'; ?> Thiết Bị
                </h2>
                <a href="?tab=thietbi" class="close">&times;</a>
            </div>
            <form method="POST">
                <?php if ($edit_thietbi): ?>
                <input type="hidden" name="thiet_bi_id" value="<?php echo htmlspecialchars($edit_thietbi['thiet_bi_id']); ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Mã Thiết Bị <span class="required">*</span></label>
                        <input type="text" name="ma_thiet_bi" required value="<?php echo htmlspecialchars($edit_thietbi['ma_thiet_bi'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tên Thiết Bị <span class="required">*</span></label>
                        <input type="text" name="ten_thiet_bi" required value="<?php echo htmlspecialchars($edit_thietbi['ten_thiet_bi'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Loại Thiết Bị <span class="required">*</span></label>
                        <select name="loai_thiet_bi" required>
                            <option value="">-- Chọn loại --</option>
                            <option value="Cardio" <?php echo ($edit_thietbi['loai_thiet_bi'] ?? '') == 'Cardio' ? 'selected' : ''; ?>>Cardio</option>
                            <option value="Tạ" <?php echo ($edit_thietbi['loai_thiet_bi'] ?? '') == 'Tạ' ? 'selected' : ''; ?>>Tạ</option>
                            <option value="Kiểm tra" <?php echo ($edit_thietbi['loai_thiet_bi'] ?? '') == 'Kiểm tra' ? 'selected' : ''; ?>>Kiểm tra</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày Mua <span class="required">*</span></label>
                        <input type="date" name="ngay_mua" required value="<?php echo htmlspecialchars($edit_thietbi['ngay_mua'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Giá Mua (VNĐ) <span class="required">*</span></label>
                        <input type="number" name="gia_mua" required value="<?php echo htmlspecialchars($edit_thietbi['gia_mua'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nhà Cung Cấp <span class="required">*</span></label>
                        <input type="text" name="nha_cung_cap" required value="<?php echo htmlspecialchars($edit_thietbi['nha_cung_cap'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Vị Trí <span class="required">*</span></label>
                        <input type="text" name="vi_tri" required value="<?php echo htmlspecialchars($edit_thietbi['vi_tri'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Trạng Thái <span class="required">*</span></label>
                        <select name="trang_thai" required>
                            <option value="">-- Chọn trạng thái --</option>
                            <option value="Đang sử dụng" <?php echo ($edit_thietbi['trang_thai'] ?? '') == 'Đang sử dụng' ? 'selected' : ''; ?>>Đang sử dụng</option>
                            <option value="Bảo trì" <?php echo ($edit_thietbi['trang_thai'] ?? '') == 'Bảo trì' ? 'selected' : ''; ?>>Bảo trì</option>
                            <option value="Hỏng" <?php echo ($edit_thietbi['trang_thai'] ?? '') == 'Hỏng' ? 'selected' : ''; ?>>Hỏng</option>
                            <option value="Thanh lý" <?php echo ($edit_thietbi['trang_thai'] ?? '') == 'Thanh lý' ? 'selected' : ''; ?>>Thanh lý</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Ghi Chú</label>
                    <textarea name="ghi_chu" rows="3"><?php echo htmlspecialchars($edit_thietbi['ghi_chu'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="?tab=thietbi" class="btn-cancel">Hủy</a>
                    <button type="submit" name="<?php echo $edit_thietbi ? 'sua_thiet_bi' : 'them_thiet_bi'; ?>" class="btn-submit">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Xem Chi Tiết -->
    <?php if ($detail_thietbi): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Chi Tiết Thiết Bị</h2>
                <a href="?tab=thietbi" class="close">&times;</a>
            </div>
            <div class="detail-content">
                <div class="detail-row">
                    <div class="detail-label">Mã Thiết Bị:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($detail_thietbi['ma_thiet_bi']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Tên Thiết Bị:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($detail_thietbi['ten_thiet_bi']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Loại Thiết Bị:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($detail_thietbi['loai_thiet_bi']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Ngày Mua:</div>
                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($detail_thietbi['ngay_mua'])); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Giá Mua:</div>
                    <div class="detail-value"><?php echo number_format($detail_thietbi['gia_mua'], 0, ',', '.'); ?> đ</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Nhà Cung Cấp:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($detail_thietbi['nha_cung_cap']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Vị Trí:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($detail_thietbi['vi_tri']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Trạng Thái:</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $detail_thietbi['trang_thai'])); ?>">
                            <?php echo htmlspecialchars($detail_thietbi['trang_thai']); ?>
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Ghi Chú:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($detail_thietbi['ghi_chu'] ?: 'Không có'); ?></div>
                </div>
            </div>
            <div class="form-actions">
                <a href="?tab=thietbi" class="btn-cancel">Đóng</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Thêm/Sửa Bảo Trì -->
    <?php if (isset($_GET['them_bt']) || $edit_baotri): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-<?php echo $edit_baotri ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $edit_baotri ? 'Sửa' : 'Thêm'; ?> Lịch Bảo Trì
                </h2>
                <a href="?tab=baotri" class="close">&times;</a>
            </div>
            <form method="POST">
                <?php if ($edit_baotri): ?>
                <input type="hidden" name="bao_tri_id" value="<?php echo htmlspecialchars($edit_baotri['bao_tri_id']); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Thiết Bị <span class="required">*</span></label>
                    <select name="thiet_bi_id" required <?php echo $edit_baotri ? 'disabled' : ''; ?>>
                        <option value="">-- Chọn thiết bị --</option>
                        <?php foreach ($danh_sach_thiet_bi_dropdown as $tb): ?>
                            <?php
                            $selected = '';
                            if ($edit_baotri && $tb['thiet_bi_id'] == $edit_baotri['thiet_bi_id']) {
                                $selected = 'selected';
                            } elseif (isset($_GET['them_bt']) && $_GET['them_bt'] == $tb['thiet_bi_id']) {
                                $selected = 'selected';
                            }
                            ?>
                            <option value="<?php echo htmlspecialchars($tb['thiet_bi_id']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($tb['ma_thiet_bi'] . ' - ' . $tb['ten_thiet_bi']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($edit_baotri): ?>
                    <input type="hidden" name="thiet_bi_id" value="<?php echo htmlspecialchars($edit_baotri['thiet_bi_id']); ?>">
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ngày Bảo Trì <span class="required">*</span></label>
                        <input type="date" name="ngay_bao_tri" required value="<?php echo htmlspecialchars($edit_baotri['ngay_bao_tri'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Loại Bảo Trì <span class="required">*</span></label>
                        <select name="loai_bao_tri" required>
                            <option value="">-- Chọn loại --</option>
                            <option value="Định kỳ" <?php echo ($edit_baotri['loai_bao_tri'] ?? '') == 'Định kỳ' ? 'selected' : ''; ?>>Định kỳ</option>
                            <option value="Sửa chữa" <?php echo ($edit_baotri['loai_bao_tri'] ?? '') == 'Sửa chữa' ? 'selected' : ''; ?>>Sửa chữa</option>
                            <option value="Kiểm tra" <?php echo ($edit_baotri['loai_bao_tri'] ?? '') == 'Kiểm tra' ? 'selected' : ''; ?>>Kiểm tra</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mô Tả <span class="required">*</span></label>
                    <textarea name="mo_ta" rows="3" required><?php echo htmlspecialchars($edit_baotri['mo_ta'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Chi Phí (VNĐ) <span class="required">*</span></label>
                        <input type="number" name="chi_phi" required value="<?php echo htmlspecialchars($edit_baotri['chi_phi'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nhân Viên Thực Hiện <span class="required">*</span></label>
                        <input type="number" name="nhan_vien_thuc_hien_id" required value="<?php echo htmlspecialchars($edit_baotri['nhan_vien_thuc_hien_id'] ?? '1'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Trạng Thái <span class="required">*</span></label>
                    <select name="trang_thai" required>
                        <option value="">-- Chọn trạng thái --</option>
                        <option value="Chờ xử lý" <?php echo ($edit_baotri['trang_thai'] ?? '') == 'Chờ xử lý' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="Đang thực hiện" <?php echo ($edit_baotri['trang_thai'] ?? '') == 'Đang thực hiện' ? 'selected' : ''; ?>>Đang thực hiện</option>
                        <option value="Hoàn thành" <?php echo ($edit_baotri['trang_thai'] ?? '') == 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
                    </select>
                </div>

                <div class="form-actions">
                    <a href="?tab=baotri" class="btn-cancel">Hủy</a>
                    <button type="submit" name="<?php echo $edit_baotri ? 'sua_bao_tri' : 'them_bao_tri'; ?>" class="btn-submit">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="facilities.js"></script>
</body>
</html>
