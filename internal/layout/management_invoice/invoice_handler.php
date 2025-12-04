<?php
/**
 * File xử lý nghiệp vụ thêm/sửa hóa đơn
 * Tách riêng logic phức tạp ra khỏi managment_function.php
 */

class InvoiceHandler {
    private $conn;
    private $current_nhan_vien_id;
    private $current_vai_tro;
    private $current_phong_tap_id;
    
    public function __construct($conn, $current_nhan_vien_id, $current_vai_tro, $current_phong_tap_id) {
        $this->conn = $conn;
        $this->current_nhan_vien_id = $current_nhan_vien_id;
        $this->current_vai_tro = $current_vai_tro;
        $this->current_phong_tap_id = $current_phong_tap_id;
    }
    
    /**
     * Kiểm tra phân quyền và phòng tập
     */
    private function validatePermissions($khach_hang_id, $nhan_vien_id) {
        // Lấy phòng tập của khách hàng
        $stmt = $this->conn->prepare("SELECT phong_tap_id FROM khachhang WHERE khach_hang_id = ?");
        $stmt->bind_param("i", $khach_hang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $khach_hang_phong_tap = $result->fetch_assoc()['phong_tap_id'] ?? null;
        $stmt->close();
        
        // Lấy phòng tập của nhân viên
        $stmt = $this->conn->prepare("SELECT phong_tap_id FROM nhanvien WHERE nhan_vien_id = ?");
        $stmt->bind_param("i", $nhan_vien_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $nhan_vien_phong_tap = $result->fetch_assoc()['phong_tap_id'] ?? null;
        $stmt->close();
        
        // Kiểm tra quyền
        if ($this->current_vai_tro === 'Admin') {
            if ($khach_hang_phong_tap !== $nhan_vien_phong_tap) {
                throw new Exception('Khách hàng và nhân viên phải cùng phòng tập!');
            }
        } else {
            if ($khach_hang_phong_tap !== $this->current_phong_tap_id || 
                $nhan_vien_phong_tap !== $this->current_phong_tap_id) {
                throw new Exception('Bạn chỉ có thể tạo hóa đơn cho khách hàng và nhân viên cùng phòng tập với bạn!');
            }
        }
        
        return $khach_hang_phong_tap;
    }
    
    /**
     * Lấy thông tin gói tập
     */
    private function getPackageInfo($goi_tap_id) {
        $sql = "SELECT gia_tien, ten_goi, thoi_han_ngay FROM GoiTap WHERE goi_tap_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $goi_tap_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Không tìm thấy gói tập');
        }
        
        $package = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'gia_tien' => floatval($package['gia_tien']),
            'ten_goi' => $package['ten_goi'],
            'thoi_han_ngay' => intval($package['thoi_han_ngay'])
        ];
    }
    
    /**
     * Tính giảm giá khuyến mãi
     */
    private function calculatePromotionDiscount($khuyen_mai_id, $gia_goi_tap) {
        if (empty($khuyen_mai_id)) {
            return 0;
        }
        
        $sql = "SELECT gia_tri_giam FROM KhuyenMai 
                WHERE khuyen_mai_id = ? AND trang_thai = 'Đang áp dụng'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $khuyen_mai_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return 0;
        }
        
        $gia_tri_giam = floatval($result->fetch_assoc()['gia_tri_giam']);
        $stmt->close();
        
        return $gia_goi_tap * $gia_tri_giam / 100;
    }
    
    /**
     * Tính tổng tiền và tiền thanh toán
     */
    private function calculateTotals($tong_tien, $giam_gia_km, $giam_gia_khac) {
        $tien_thanh_toan = $tong_tien - $giam_gia_km - $giam_gia_khac;
        return max(0, $tien_thanh_toan);
    }
    
    /**
     * Thêm hóa đơn mới
     */
    public function addInvoice($data) {
        $this->conn->begin_transaction();
        
        try {
            $khach_hang_id = intval($data['khach_hang_id']);
            $nhan_vien_id = intval($data['nhan_vien_id']);
            $goi_tap_id = intval($data['goi_tap_id']);
            
            // Kiểm tra phân quyền
            $phong_tap_id = $this->validatePermissions($khach_hang_id, $nhan_vien_id);
            
            // Kiểm tra mã hóa đơn trùng
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM HoaDon WHERE ma_hoa_don = ?");
            $stmt->bind_param("s", $data['ma_hoa_don']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_assoc()['count'] > 0) {
                throw new Exception('Mã hóa đơn đã tồn tại');
            }
            $stmt->close();
            
            // Lấy thông tin gói tập
            $package = $this->getPackageInfo($goi_tap_id);
            
            // Tính toán
            $giam_gia_km = $this->calculatePromotionDiscount(
                $data['khuyen_mai_id'] ?? null, 
                $package['gia_tien']
            );
            $giam_gia_khac = floatval($data['giam_gia_khac'] ?? 0);
            $tong_tien = $package['gia_tien'];
            $tien_thanh_toan = $this->calculateTotals($tong_tien, $giam_gia_km, $giam_gia_khac);
            
            // Thêm hóa đơn
            $hoa_don_id = $this->insertInvoice([
                'ma_hoa_don' => $data['ma_hoa_don'],
                'khach_hang_id' => $khach_hang_id,
                'khuyen_mai_id' => !empty($data['khuyen_mai_id']) ? intval($data['khuyen_mai_id']) : null,
                'ngay_lap' => $data['ngay_lap'],
                'tong_tien' => $tong_tien,
                'giam_gia_km' => $giam_gia_km,
                'giam_gia_khac' => $giam_gia_khac,
                'tien_thanh_toan' => $tien_thanh_toan,
                'phuong_thuc_thanh_toan' => $data['phuong_thuc_thanh_toan'],
                'trang_thai' => $data['trang_thai'],
                'nhan_vien_id' => $nhan_vien_id,
                'ghi_chu' => $data['ghi_chu'] ?? '',
                'phong_tap_id' => $phong_tap_id
            ]);
            
            // Thêm chi tiết hóa đơn
            $this->insertInvoiceDetail($hoa_don_id, [
                'goi_tap_id' => $goi_tap_id,
                'ten_goi' => $package['ten_goi'],
                'don_gia' => $package['gia_tien'],
                'thanh_tien' => $package['gia_tien']
            ]);
            
            // Nếu đã thanh toán, tạo đăng ký gói tập
            if ($data['trang_thai'] === 'Đã thanh toán') {
                $this->createPackageRegistration(
                    $hoa_don_id,
                    $khach_hang_id,
                    $goi_tap_id,
                    $data['ngay_lap'],
                    $package['thoi_han_ngay'],
                    $tien_thanh_toan,
                    $nhan_vien_id
                );
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Thêm hóa đơn thành công', 'id' => $hoa_don_id];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Insert hóa đơn vào database
     */
    private function insertInvoice($data) {
        $sql = "INSERT INTO HoaDon (
                ma_hoa_don, khach_hang_id, khuyen_mai_id, ngay_lap,
                tong_tien, giam_gia_khuyen_mai, giam_gia_khac, tien_thanh_toan,
                phuong_thuc_thanh_toan, trang_thai, nhan_vien_lap_id, ghi_chu, phong_tap_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "siisddddssisi",
            $data['ma_hoa_don'],
            $data['khach_hang_id'],
            $data['khuyen_mai_id'],
            $data['ngay_lap'],
            $data['tong_tien'],
            $data['giam_gia_km'],
            $data['giam_gia_khac'],
            $data['tien_thanh_toan'],
            $data['phuong_thuc_thanh_toan'],
            $data['trang_thai'],
            $data['nhan_vien_id'],
            $data['ghi_chu'],
            $data['phong_tap_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Lỗi khi thêm hóa đơn: ' . $stmt->error);
        }
        
        $hoa_don_id = $this->conn->insert_id;
        $stmt->close();
        
        return $hoa_don_id;
    }
    
    /**
     * Insert chi tiết hóa đơn
     */
    private function insertInvoiceDetail($hoa_don_id, $data) {
        $sql = "INSERT INTO ChiTietHoaDon (
                hoa_don_id, goi_tap_id, ten_goi, so_luong, don_gia, thanh_tien
            ) VALUES (?, ?, ?, 1, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iisdd",
            $hoa_don_id,
            $data['goi_tap_id'],
            $data['ten_goi'],
            $data['don_gia'],
            $data['thanh_tien']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Lỗi khi thêm chi tiết hóa đơn: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
    /**
     * Tạo đăng ký gói tập
     */
    private function createPackageRegistration($hoa_don_id, $khach_hang_id, $goi_tap_id, 
                                               $ngay_bat_dau, $thoi_han_ngay, $tien_thanh_toan, $nhan_vien_id) {
        $ngay_ket_thuc = date('Y-m-d', strtotime($ngay_bat_dau . " + $thoi_han_ngay days"));
        
        $sql = "INSERT INTO DangKyGoiTap (
                khach_hang_id, goi_tap_id, hoa_don_id, ngay_dang_ky,
                ngay_bat_dau, ngay_ket_thuc, tong_tien, trang_thai, nhan_vien_kich_hoat_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Đang hoạt động', ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iiisssdi",
            $khach_hang_id,
            $goi_tap_id,
            $hoa_don_id,
            $ngay_bat_dau,
            $ngay_bat_dau,
            $ngay_ket_thuc,
            $tien_thanh_toan,
            $nhan_vien_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Lỗi khi thêm đăng ký gói tập: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
    /**
     * Cập nhật hóa đơn
     */
    public function updateInvoice($hoa_don_id, $data) {
        $this->conn->begin_transaction();
        
        try {
            $khach_hang_id = intval($data['khach_hang_id']);
            $nhan_vien_id = intval($data['nhan_vien_id']);
            $goi_tap_id = intval($data['goi_tap_id']);
            
            // Kiểm tra phân quyền
            $phong_tap_id = $this->validatePermissions($khach_hang_id, $nhan_vien_id);
            
            // Lấy trạng thái cũ
            $old_status = $this->getInvoiceStatus($hoa_don_id);
            
            // Lấy thông tin gói tập
            $package = $this->getPackageInfo($goi_tap_id);
            
            // Tính toán
            $giam_gia_km = $this->calculatePromotionDiscount(
                $data['khuyen_mai_id'] ?? null, 
                $package['gia_tien']
            );
            $giam_gia_khac = floatval($data['giam_gia_khac'] ?? 0);
            $tong_tien = $package['gia_tien'];
            $tien_thanh_toan = $this->calculateTotals($tong_tien, $giam_gia_km, $giam_gia_khac);
            
            // Cập nhật hóa đơn
            $this->updateInvoiceRecord($hoa_don_id, [
                'ma_hoa_don' => $data['ma_hoa_don'],
                'khach_hang_id' => $khach_hang_id,
                'khuyen_mai_id' => !empty($data['khuyen_mai_id']) ? intval($data['khuyen_mai_id']) : null,
                'ngay_lap' => $data['ngay_lap'],
                'tong_tien' => $tong_tien,
                'giam_gia_km' => $giam_gia_km,
                'giam_gia_khac' => $giam_gia_khac,
                'tien_thanh_toan' => $tien_thanh_toan,
                'phuong_thuc_thanh_toan' => $data['phuong_thuc_thanh_toan'],
                'trang_thai' => $data['trang_thai'],
                'nhan_vien_id' => $nhan_vien_id,
                'ghi_chu' => $data['ghi_chu'] ?? '',
                'phong_tap_id' => $phong_tap_id
            ]);
            
            // Cập nhật chi tiết hóa đơn
            $this->updateInvoiceDetail($hoa_don_id, [
                'goi_tap_id' => $goi_tap_id,
                'ten_goi' => $package['ten_goi'],
                'don_gia' => $package['gia_tien'],
                'thanh_tien' => $package['gia_tien']
            ]);
            
            // Xử lý đăng ký gói tập
            $this->handlePackageRegistrationUpdate(
                $hoa_don_id,
                $old_status,
                $data['trang_thai'],
                $khach_hang_id,
                $goi_tap_id,
                $data['ngay_lap'],
                $package['thoi_han_ngay'],
                $tien_thanh_toan,
                $nhan_vien_id
            );
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Cập nhật hóa đơn thành công'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy trạng thái hóa đơn hiện tại
     */
    private function getInvoiceStatus($hoa_don_id) {
        $stmt = $this->conn->prepare("SELECT trang_thai FROM HoaDon WHERE hoa_don_id = ?");
        $stmt->bind_param("i", $hoa_don_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $status = $result->fetch_assoc()['trang_thai'];
        $stmt->close();
        return $status;
    }
    
    /**
     * Cập nhật record hóa đơn
     */
    private function updateInvoiceRecord($hoa_don_id, $data) {
        $sql = "UPDATE HoaDon SET
                ma_hoa_don = ?,
                khach_hang_id = ?,
                khuyen_mai_id = ?,
                ngay_lap = ?,
                tong_tien = ?,
                giam_gia_khuyen_mai = ?,
                giam_gia_khac = ?,
                tien_thanh_toan = ?,
                phuong_thuc_thanh_toan = ?,
                trang_thai = ?,
                nhan_vien_lap_id = ?,
                ghi_chu = ?,
                phong_tap_id = ?
            WHERE hoa_don_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "siisddddssisii",
            $data['ma_hoa_don'],
            $data['khach_hang_id'],
            $data['khuyen_mai_id'],
            $data['ngay_lap'],
            $data['tong_tien'],
            $data['giam_gia_km'],
            $data['giam_gia_khac'],
            $data['tien_thanh_toan'],
            $data['phuong_thuc_thanh_toan'],
            $data['trang_thai'],
            $data['nhan_vien_id'],
            $data['ghi_chu'],
            $data['phong_tap_id'],
            $hoa_don_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Lỗi cập nhật hóa đơn: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
    /**
     * Cập nhật chi tiết hóa đơn
     */
    private function updateInvoiceDetail($hoa_don_id, $data) {
        // Xóa chi tiết cũ
        $stmt = $this->conn->prepare("DELETE FROM ChiTietHoaDon WHERE hoa_don_id = ?");
        $stmt->bind_param("i", $hoa_don_id);
        $stmt->execute();
        $stmt->close();
        
        // Thêm chi tiết mới
        $this->insertInvoiceDetail($hoa_don_id, $data);
    }
    
    /**
     * Xử lý cập nhật đăng ký gói tập
     */
    private function handlePackageRegistrationUpdate($hoa_don_id, $old_status, $new_status,
                                                     $khach_hang_id, $goi_tap_id, $ngay_bat_dau,
                                                     $thoi_han_ngay, $tien_thanh_toan, $nhan_vien_id) {
        // Kiểm tra đăng ký hiện có
        $stmt = $this->conn->prepare("SELECT dang_ky_id FROM DangKyGoiTap WHERE hoa_don_id = ?");
        $stmt->bind_param("i", $hoa_don_id);
        $stmt->execute();
        $existing = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        if ($new_status === 'Đã thanh toán') {
            $ngay_ket_thuc = date('Y-m-d', strtotime($ngay_bat_dau . " + $thoi_han_ngay days"));
            
            if ($existing) {
                // Cập nhật đăng ký
                $sql = "UPDATE DangKyGoiTap SET
                        khach_hang_id = ?,
                        goi_tap_id = ?,
                        ngay_dang_ky = ?,
                        ngay_bat_dau = ?,
                        ngay_ket_thuc = ?,
                        tong_tien = ?,
                        trang_thai = 'Đang hoạt động',
                        nhan_vien_kich_hoat_id = ?
                    WHERE hoa_don_id = ?";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("iisssdii", $khach_hang_id, $goi_tap_id, $ngay_bat_dau,
                                  $ngay_bat_dau, $ngay_ket_thuc, $tien_thanh_toan,
                                  $nhan_vien_id, $hoa_don_id);
            } else {
                // Tạo đăng ký mới
                $this->createPackageRegistration($hoa_don_id, $khach_hang_id, $goi_tap_id,
                                                $ngay_bat_dau, $thoi_han_ngay, $tien_thanh_toan,
                                                $nhan_vien_id);
                return;
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Lỗi xử lý đăng ký gói tập: ' . $stmt->error);
            }
            $stmt->close();
            
        } else if ($old_status === 'Đã thanh toán' && $new_status !== 'Đã thanh toán' && $existing) {
            // Hủy đăng ký
            $stmt = $this->conn->prepare("UPDATE DangKyGoiTap SET trang_thai = 'Hủy' WHERE hoa_don_id = ?");
            $stmt->bind_param("i", $hoa_don_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>