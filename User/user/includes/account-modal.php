<?php
/**
 * Account Modal - Include file cho tất cả các trang
 * Sử dụng: <?php include __DIR__ . '/includes/account-modal.php'; ?>
 */
// Xác định đường dẫn base dựa trên vị trí file include
$isInUserFolder = (strpos(__DIR__, '/user/') !== false || strpos(__DIR__, '\\user\\') !== false);
// Kiểm tra xem có đang ở trong thư mục con (như goitap/, danhgia/, etc.) không
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isInSubFolder = (strpos($scriptPath, '/goitap/') !== false || strpos($scriptPath, '\\goitap\\') !== false ||
                  strpos($scriptPath, '/danhgia/') !== false || strpos($scriptPath, '\\danhgia\\') !== false ||
                  strpos($scriptPath, '/hotro/') !== false || strpos($scriptPath, '\\hotro\\') !== false ||
                  strpos($scriptPath, '/lichtap/') !== false || strpos($scriptPath, '\\lichtap\\') !== false ||
                  strpos($scriptPath, '/homthu/') !== false || strpos($scriptPath, '\\homthu\\') !== false ||
                  strpos($scriptPath, '/khuyenmai/') !== false || strpos($scriptPath, '\\khuyenmai\\') !== false ||
                  strpos($scriptPath, '/thanhtoan/') !== false || strpos($scriptPath, '\\thanhtoan\\') !== false);
$basePath = $isInUserFolder ? '../' : '';
// Xác định đường dẫn assets - nếu ở trong thư mục con cần ../../assets/
$assetsPath = ($isInUserFolder && $isInSubFolder) ? '../../assets/' : ($isInUserFolder ? '../assets/' : 'assets/');
// Nếu đang ở trong thư mục con, cần thêm ../
// Các file PHP nằm trong getset/, dangky/, dangnhap/
$phpBasePath = ($isInUserFolder && $isInSubFolder) ? '../getset/' : ($isInUserFolder ? 'getset/' : 'user/getset/');
$loginPath = ($isInUserFolder && $isInSubFolder) ? '../dangnhap/login.php' : ($isInUserFolder ? 'dangnhap/login.php' : 'user/dangnhap/login.php');
$registerPath = ($isInUserFolder && $isInSubFolder) ? '../dangky/register.php' : ($isInUserFolder ? 'dangky/register.php' : 'user/dangky/register.php');
$logoutPath = ($isInUserFolder && $isInSubFolder) ? '../getset/logout.php' : ($isInUserFolder ? 'getset/logout.php' : 'user/getset/logout.php');
$forgotPasswordPath = ($isInUserFolder && $isInSubFolder) ? '../getset/forgot_password.php' : ($isInUserFolder ? 'getset/forgot_password.php' : 'user/getset/forgot_password.php');
$savePaymentPath = ($isInUserFolder && $isInSubFolder) ? '../thanhtoan/save_payment_method.php' : ($isInUserFolder ? 'thanhtoan/save_payment_method.php' : 'user/thanhtoan/save_payment_method.php');
?>
<div class="modal-overlay hidden" id="account-modal">
    <div class="modal-content" style="max-width: 750px; padding: 0;"> 
      <button class="modal-close-btn" id="account-modal-close"><i class="fas fa-times"></i></button>

      <div class="modal-header">
          <a href="<?php echo $basePath; ?>index.html" class="brand" style="margin-bottom: 0;">
              <img src="<?php echo $assetsPath; ?>img/logo.png" alt="Logo" class="logo" style="height: 40px; width: 40px; flex-shrink: 0; object-fit: contain;">
              <span style="display: flex; align-items: center; justify-content: center; height: fit-content; margin-top: 0; margin-bottom: 0; line-height: 1;">Tài khoản của tôi</span>
          </a>
      </div>

      <div class="modal-body" style="padding: 20px 30px;">
          <div class="account-tab-menu">
              <button type="button" class="account-tab-link active" data-target="#acc-profile">
                  <i class="fas fa-user-edit"></i> Thông tin
              </button>
              <button type="button" class="account-tab-link" data-target="#acc-packages">
                  <i class="fas fa-box"></i> Gói tập của bạn
              </button>
              <button type="button" class="account-tab-link" data-target="#acc-history">
                  <i class="fas fa-history"></i> Lịch sử TT
              </button>
              <button type="button" class="account-tab-link" data-target="#acc-payment">
                  <i class="fas fa-credit-card"></i> Quản lý TT
              </button>
              <button type="button" class="account-tab-link" data-target="#acc-password">
                  <i class="fas fa-key"></i> Mật khẩu
              </button>
              <a href="<?php echo $logoutPath; ?>" class="account-tab-link logout-link">
                  <i class="fas fa-sign-out-alt"></i> Đăng xuất
              </a>
          </div>

          <div class="account-tab-content">
              
              <section id="acc-profile" class="account-section-modal active">
                <div class="card form-layout" style="background: transparent; border: none; box-shadow: none; padding: 10px 0 0 0;">
                  <form action="<?php echo $phpBasePath . 'update_profile.php'; ?>" method="POST">
                    <?php
                    // Lấy thông tin đầy đủ từ bảng KhachHang
                    if (isset($_SESSION['user'])) {
                        try {
                            // Sử dụng đường dẫn database chuẩn từ database folder
                            if ($isInUserFolder && $isInSubFolder) {
                                require_once __DIR__ . '/../../database/config.php';
                                require_once __DIR__ . '/../../database/db_connect.php';
                            } elseif ($isInUserFolder) {
                                require_once __DIR__ . '/../../database/config.php';
                                require_once __DIR__ . '/../../database/db_connect.php';
                            } else {
                                require_once __DIR__ . '/../../database/config.php';
                                require_once __DIR__ . '/../../database/db_connect.php';
                            }
                            
                            $username = $_SESSION['user']['username'];
                            $email = $_SESSION['user']['email'] ?? '';
                            
                            // Tìm bằng username hoặc email (ưu tiên username)
                            $stmt = $pdo->prepare("SELECT khach_hang_id, ten_dang_nhap, ho_ten, email, sdt, cccd, dia_chi, ngay_sinh, gioi_tinh, ngay_dang_ky, ngay_cap_nhat 
                                                   FROM KhachHang 
                                                   WHERE (ten_dang_nhap = :username OR email = :email)
                                                   LIMIT 1");
                            $stmt->execute([
                                ':username' => $username,
                                ':email' => $email
                            ]);
                            $khachHangInfo = $stmt->fetch();
                            
                            if ($khachHangInfo) {
                                $ten_dang_nhap = htmlspecialchars($khachHangInfo['ten_dang_nhap'] ?? '');
                                $ho_ten = htmlspecialchars($khachHangInfo['ho_ten'] ?? '');
                                $email = htmlspecialchars($khachHangInfo['email'] ?? '');
                                $sdt = htmlspecialchars($khachHangInfo['sdt'] ?? '');
                                $cccd = htmlspecialchars($khachHangInfo['cccd'] ?? '');
                                $dia_chi = htmlspecialchars($khachHangInfo['dia_chi'] ?? '');
                                $ngay_sinh = $khachHangInfo['ngay_sinh'] ?? '';
                                $gioi_tinh = htmlspecialchars($khachHangInfo['gioi_tinh'] ?? '');
                                $ngay_dang_ky = $khachHangInfo['ngay_dang_ky'] ?? '';
                                $ngay_cap_nhat = $khachHangInfo['ngay_cap_nhat'] ?? '';
                                
                                error_log("Loaded customer info - khach_hang_id: " . ($khachHangInfo['khach_hang_id'] ?? 'N/A') . ", ho_ten: {$ho_ten}, email: {$email}");
                            } else {
                                error_log("Customer info not found - username: {$username}, email: {$email}");
                                // Fallback nếu không tìm thấy
                                $ten_dang_nhap = htmlspecialchars($_SESSION['user']['username'] ?? '');
                                $ho_ten = htmlspecialchars($_SESSION['user']['full_name'] ?? '');
                                $email = htmlspecialchars($_SESSION['user']['email'] ?? '');
                                $sdt = '';
                                $cccd = '';
                                $dia_chi = '';
                                $ngay_sinh = '';
                                $gioi_tinh = '';
                                $ngay_dang_ky = '';
                                $ngay_cap_nhat = '';
                            }
                        } catch (Exception $e) {
                            error_log("Error loading customer info: " . $e->getMessage());
                            // Fallback
                            $ten_dang_nhap = htmlspecialchars($_SESSION['user']['username'] ?? '');
                            $ho_ten = htmlspecialchars($_SESSION['user']['full_name'] ?? '');
                            $email = htmlspecialchars($_SESSION['user']['email'] ?? '');
                            $sdt = '';
                            $cccd = '';
                            $dia_chi = '';
                            $ngay_sinh = '';
                            $gioi_tinh = '';
                            $ngay_dang_ky = '';
                            $ngay_cap_nhat = '';
                        }
                    } else {
                        $ten_dang_nhap = '';
                        $ho_ten = '';
                        $email = '';
                        $sdt = '';
                        $cccd = '';
                        $dia_chi = '';
                        $ngay_sinh = '';
                        $gioi_tinh = '';
                        $ngay_dang_ky = '';
                        $ngay_cap_nhat = '';
                    }
                    ?>
                    <div class="form-group">
                      <label for="acc-username-modal">Tên đăng nhập</label>
                      <input type="text" id="acc-username-modal" name="username" value="<?php echo $ten_dang_nhap; ?>">
                    </div>
                    <div class="form-group">
                      <label for="acc-fullname-modal">Họ và tên</label>
                      <input type="text" id="acc-fullname-modal" name="full_name" value="<?php echo $ho_ten; ?>">
                    </div>
                    <div class="form-group">
                      <label for="acc-email-modal">Email</label>
                      <input type="email" id="acc-email-modal" name="email" value="<?php echo $email; ?>" readonly disabled>
                    </div>
                    <div class="form-group">
                      <label for="acc-phone-modal">Số điện thoại</label>
                      <input type="tel" id="acc-phone-modal" name="sdt" value="<?php echo $sdt; ?>" maxlength="11" pattern="[0-9]{10,11}">
                    </div>
                    <div class="form-group">
                      <label for="acc-cccd-modal">Căn cước công dân</label>
                      <input type="text" id="acc-cccd-modal" name="cccd" value="<?php echo $cccd; ?>" maxlength="12" pattern="[0-9]{9,12}">
                    </div>
                    <div class="form-group">
                      <label for="acc-address-modal">Địa chỉ</label>
                      <input type="text" id="acc-address-modal" name="dia_chi" value="<?php echo $dia_chi; ?>">
                    </div>
                    <div class="form-group">
                      <label for="acc-birthday-modal">Ngày sinh</label>
                      <input type="date" id="acc-birthday-modal" name="ngay_sinh" value="<?php echo $ngay_sinh; ?>">
                    </div>
                    <div class="form-group">
                      <label for="acc-gender-modal">Giới tính</label>
                      <select id="acc-gender-modal" name="gioi_tinh">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="Nam" <?php echo ($gioi_tinh === 'Nam') ? 'selected' : ''; ?>>Nam</option>
                        <option value="Nữ" <?php echo ($gioi_tinh === 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                        <option value="Khác" <?php echo ($gioi_tinh === 'Khác') ? 'selected' : ''; ?>>Khác</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="acc-registration-date-modal">Ngày đăng ký</label>
                      <input type="date" id="acc-registration-date-modal" name="ngay_dang_ky_display" value="<?php echo $ngay_dang_ky; ?>" readonly disabled style="background: var(--bg-2); cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                      <label for="acc-update-date-modal">Ngày cập nhật</label>
                      <input type="datetime-local" id="acc-update-date-modal" name="ngay_cap_nhat_display" value="<?php echo $ngay_cap_nhat ? date('Y-m-d\TH:i', strtotime($ngay_cap_nhat)) : ''; ?>" readonly disabled style="background: var(--bg-2); cursor: not-allowed;">
                    </div>
                    <button type="submit" class="btn">Lưu thay đổi</button>
                  </form>
                </div>
              </section>
              
              <section id="acc-packages" class="account-section-modal">
                <div class="card" style="background: transparent; border: none; box-shadow: none; padding: 10px 0 0 0;">
                  <h3 style="margin-bottom: 20px;"><i class="fas fa-box"></i> Gói tập của bạn</h3>
                  <?php
                  // Hiển thị gói tập đã đăng ký
                  if (isset($_SESSION['user'])) {
                      try {
                          // Sử dụng đường dẫn database chuẩn từ database folder
                          require_once __DIR__ . '/../../database/config.php';
                          require_once __DIR__ . '/../../database/db_connect.php';
                          
                          $username = $_SESSION['user']['username'];
                          $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
                          $stmt->execute([':username' => $username]);
                          $khachHang = $stmt->fetch();
                          
                          if ($khachHang) {
                              $khach_hang_id = $khachHang['khach_hang_id'];
                              
                              // Lấy TẤT CẢ các gói tập từ database
                              $stmt = $pdo->prepare("SELECT dk.dang_ky_id, dk.goi_tap_id, dk.ngay_bat_dau, dk.ngay_ket_thuc, 
                                                           dk.tong_tien, dk.trang_thai, gt.ten_goi, gt.ma_goi_tap,
                                                           DATEDIFF(dk.ngay_ket_thuc, CURDATE()) as so_ngay_con_lai,
                                                           DATEDIFF(CURDATE(), dk.ngay_bat_dau) as so_ngay_da_dung,
                                                           DATEDIFF(dk.ngay_ket_thuc, dk.ngay_bat_dau) as tong_so_ngay
                                                    FROM DangKyGoiTap dk
                                                    LEFT JOIN GoiTap gt ON dk.goi_tap_id = gt.goi_tap_id
                                                    WHERE dk.khach_hang_id = :kh_id 
                                                    ORDER BY dk.dang_ky_id DESC");
                              $stmt->execute([':kh_id' => $khach_hang_id]);
                              $allPackages = $stmt->fetchAll();
                              
                              if (count($allPackages) > 0) {
                                  echo '<div style="display: grid; gap: 15px;">';
                                  
                                  foreach ($allPackages as $pkg) {
                                      $dang_ky_id = $pkg['dang_ky_id'];
                                      $ten_goi = htmlspecialchars($pkg['ten_goi'] ?? 'Gói tập');
                                      $ngay_bat_dau = date('d/m/Y', strtotime($pkg['ngay_bat_dau']));
                                      $ngay_ket_thuc = date('d/m/Y', strtotime($pkg['ngay_ket_thuc']));
                                      $tong_tien = number_format($pkg['tong_tien'], 0, ',', '.');
                                      $so_ngay_con_lai = max(0, (int)$pkg['so_ngay_con_lai']);
                                      $so_ngay_da_dung = max(0, (int)$pkg['so_ngay_da_dung']);
                                      $tong_so_ngay = max(1, (int)$pkg['tong_so_ngay']);
                                      $trang_thai = $pkg['trang_thai'];
                                      
                                      $ty_le_da_dung = ($tong_so_ngay > 0) ? ($so_ngay_da_dung / $tong_so_ngay) : 0;
                                      $ty_le_con_lai = 1 - $ty_le_da_dung;
                                      
                                      $tien_hoan_lai = $pkg['tong_tien'] * $ty_le_con_lai;
                                      if ($ty_le_da_dung > 0.5) {
                                          $tien_hoan_lai = 0;
                                      }
                                      $tien_hoan_lai = round($tien_hoan_lai / 1000) * 1000;
                                      
                                      $is_active = ($trang_thai === 'Đang hoạt động' && $so_ngay_con_lai >= 0);
                                      $is_expired = ($trang_thai === 'Hết hạn' || ($trang_thai === 'Đang hoạt động' && $so_ngay_con_lai < 0));
                                      $is_cancelled = ($trang_thai === 'Hủy');
                                      
                                      echo '<div style="background: var(--bg-2); padding: 20px; border-radius: 10px; border-left: 4px solid ' . ($is_active ? 'var(--primary)' : ($is_expired ? 'var(--muted)' : 'var(--accent)')) . ';">';
                                      echo '<div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">';
                                      
                                      echo '<div style="flex: 1;">';
                                      echo '<h4 style="margin: 0 0 10px 0; color: var(--text);">';
                                      if ($is_active) {
                                          echo '<i class="fas fa-check-circle" style="color: var(--primary);"></i> ';
                                      } elseif ($is_expired) {
                                          echo '<i class="fas fa-clock" style="color: var(--muted);"></i> ';
                                      } else {
                                          echo '<i class="fas fa-times-circle" style="color: var(--accent);"></i> ';
                                      }
                                      echo $ten_goi . '</h4>';
                                      echo '<div style="color: var(--muted); font-size: 14px; line-height: 1.8;">';
                                      echo '<p style="margin: 5px 0;"><strong>Ngày bắt đầu:</strong> ' . $ngay_bat_dau . '</p>';
                                      echo '<p style="margin: 5px 0;"><strong>Ngày hết hạn:</strong> ' . $ngay_ket_thuc . '</p>';
                                      echo '<p style="margin: 5px 0;"><strong>Số tiền:</strong> ' . $tong_tien . '₫</p>';
                                      if ($tong_so_ngay > 0) {
                                          echo '<p style="margin: 5px 0;"><strong>Tiến độ:</strong> ' . $so_ngay_da_dung . '/' . $tong_so_ngay . ' ngày đã sử dụng';
                                          if ($so_ngay_con_lai > 0) {
                                              echo ' • ' . $so_ngay_con_lai . ' ngày còn lại';
                                          }
                                          echo '</p>';
                                      }
                                      if ($tien_hoan_lai > 0 && $is_active) {
                                          echo '<p style="margin: 5px 0; color: var(--primary);"><strong>Tiền hoàn lại (nếu hủy):</strong> ' . number_format($tien_hoan_lai, 0, ',', '.') . '₫</p>';
                                      } elseif ($ty_le_da_dung > 0.5 && $is_active) {
                                          echo '<p style="margin: 5px 0; color: var(--muted); font-size: 12px;"><em>(Đã sử dụng > 50%, không hoàn tiền theo chính sách)</em></p>';
                                      }
                                      echo '</div>';
                                      echo '</div>';
                                      
                                      echo '<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">';
                                      if ($is_active) {
                                          echo '<span class="status-active" style="padding: 8px 15px; border-radius: 20px; white-space: nowrap;">Đang hoạt động</span>';
                                          echo '<button type="button" class="btn cancel-package-btn" style="padding: 8px 20px; background: rgba(255, 48, 64, 0.1); color: #ff3040; border-color: #ff3040; white-space: nowrap;" 
                                                  data-dang-ky-id="' . (int)$dang_ky_id . '" 
                                                  data-ten-goi="' . htmlspecialchars($ten_goi, ENT_QUOTES, 'UTF-8') . '" 
                                                  data-tien-hoan-lai="' . (int)$tien_hoan_lai . '" 
                                                  data-so-ngay-da-dung="' . (int)$so_ngay_da_dung . '" 
                                                  data-tong-so-ngay="' . (int)$tong_so_ngay . '">';
                                          echo '<i class="fas fa-times"></i> Hủy gói</button>';
                                      } elseif ($is_expired) {
                                          echo '<span class="status-warning" style="padding: 8px 15px; border-radius: 20px; white-space: nowrap;">Đã hết hạn</span>';
                                      } elseif ($is_cancelled) {
                                          echo '<span class="status-error" style="padding: 8px 15px; border-radius: 20px; white-space: nowrap;">Đã hủy</span>';
                                      }
                                      echo '</div>';
                                      echo '</div>';
                                      echo '</div>';
                                  }
                                  
                                  echo '</div>';
                              } else {
                                  $packagesLink = $isInUserFolder ? 'goitap/packages.html' : 'user/goitap/packages.html';
                                  echo '<p class="muted center" style="padding: 20px;">Bạn chưa có gói tập nào. <a href="' . $packagesLink . '" style="color: var(--primary);">Mua gói tập ngay</a></p>';
                              }
                          }
                      } catch (Exception $e) {
                          error_log("Package display error: " . $e->getMessage());
                          echo '<p class="muted center" style="padding: 20px;">Có lỗi xảy ra khi tải thông tin gói tập.</p>';
                      }
                  }
                  ?>
                </div>
              </section>
              
              <section id="acc-history" class="account-section-modal">
                <div class="card" style="background: transparent; border: none; box-shadow: none; padding: 10px 0 0 0;">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Ngày</th>
                        <th>Gói tập</th>
                        <th>Số tiền</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if (isset($_SESSION['user'])) {
                          try {
                              require_once __DIR__ . '/../../database/config.php';
                              require_once __DIR__ . '/../../database/db_connect.php';
                              
                              $username = $_SESSION['user']['username'];
                              $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
                              $stmt->execute([':username' => $username]);
                              $khachHang = $stmt->fetch();
                              
                              if ($khachHang) {
                                  $khach_hang_id = $khachHang['khach_hang_id'];
                                  $stmt = $pdo->prepare("SELECT hd.hoa_don_id, hd.ma_hoa_don, hd.ngay_lap, hd.tong_tien, hd.phuong_thuc_thanh_toan, hd.trang_thai,
                                                        COALESCE(
                                                            (SELECT cthd.ten_goi 
                                                             FROM ChiTietHoaDon cthd 
                                                             WHERE cthd.hoa_don_id = hd.hoa_don_id 
                                                             LIMIT 1),
                                                            hd.ma_hoa_don
                                                        ) as ten_goi
                                                        FROM HoaDon hd
                                                        WHERE hd.khach_hang_id = :kh_id 
                                                        ORDER BY hd.ngay_lap DESC, hd.hoa_don_id DESC");
                                  $stmt->execute([':kh_id' => $khach_hang_id]);
                                  $payments = $stmt->fetchAll();
                                  
                                  if (count($payments) > 0) {
                                      foreach ($payments as $payment) {
                                          $ngay = date('d/m/Y', strtotime($payment['ngay_lap']));
                                          $tong_tien = number_format($payment['tong_tien'], 0, ',', '.');
                                          $phuong_thuc = $payment['phuong_thuc_thanh_toan'];
                                          $trang_thai = $payment['trang_thai'];
                                          $ten_goi = $payment['ten_goi'];
                                          
                                          $icon = '<i class="fas fa-university"></i>';
                                          if (strpos(strtolower($phuong_thuc), 'visa') !== false) {
                                              $icon = '<i class="fab fa-cc-visa"></i>';
                                          } elseif (strpos(strtolower($phuong_thuc), 'paypal') !== false) {
                                              $icon = '<i class="fab fa-paypal"></i>';
                                          } elseif (strpos(strtolower($phuong_thuc), 'momo') !== false) {
                                              $icon = '<i class="fas fa-mobile-alt"></i>';
                                          } elseif (strpos(strtolower($phuong_thuc), 'zalo') !== false) {
                                              $icon = '<i class="fas fa-mobile-alt"></i>';
                                          }
                                          
                                          $statusClass = 'status-active';
                                          $statusText = 'Thành công';
                                          if ($trang_thai === 'Chờ thanh toán') {
                                              $statusClass = 'status-warning';
                                              $statusText = 'Chờ';
                                          } elseif ($trang_thai === 'Hủy') {
                                              $statusClass = 'status-error';
                                              $statusText = 'Đã hủy';
                                          }
                                          
                                          echo "<tr>";
                                          echo "<td>{$ngay}</td>";
                                          echo "<td>{$ten_goi}</td>";
                                          echo "<td>{$tong_tien}₫</td>";
                                          echo "<td>{$icon} {$phuong_thuc}</td>";
                                          echo "<td><span class=\"{$statusClass}\">{$statusText}</span></td>";
                                          echo "</tr>";
                                      }
                                  } else {
                                      echo "<tr><td colspan=\"5\" class=\"center muted\">Chưa có lịch sử thanh toán.</td></tr>";
                                  }
                              }
                          } catch (Exception $e) {
                              error_log("Payment history error: " . $e->getMessage());
                              echo "<tr><td colspan=\"5\" class=\"center muted\">Lỗi tải lịch sử thanh toán.</td></tr>";
                          }
                      } else {
                          echo "<tr><td colspan=\"5\" class=\"center muted\">Chưa có lịch sử thanh toán.</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </section>

              <section id="acc-payment" class="account-section-modal">
                <div class="card form-layout" style="background: transparent; border: none; box-shadow: none; padding: 10px 0 0 0;">
                  <h3>Phương thức đã lưu</h3>
                  <div class="saved-methods-list">
                      <?php
                      if (isset($_SESSION['user'])) {
                          try {
                              require_once __DIR__ . '/../../database/config.php';
                              require_once __DIR__ . '/../../database/db_connect.php';
                              
                              $username = $_SESSION['user']['username'];
                              $stmt = $pdo->prepare("SELECT khach_hang_id FROM KhachHang WHERE ten_dang_nhap = :username LIMIT 1");
                              $stmt->execute([':username' => $username]);
                              $khachHang = $stmt->fetch();
                              
                              if ($khachHang) {
                                  $khach_hang_id = $khachHang['khach_hang_id'];
                                  
                                  $pdo->exec("CREATE TABLE IF NOT EXISTS PhuongThucThanhToan (
                                      phuong_thuc_id INT AUTO_INCREMENT PRIMARY KEY,
                                      khach_hang_id INT NOT NULL,
                                      loai_phuong_thuc ENUM('Tiền mặt', 'Chuyển khoản', 'Thẻ', 'Ví điện tử') NOT NULL,
                                      ten_hien_thi VARCHAR(100) NOT NULL,
                                      thong_tin_chi_tiet VARCHAR(255),
                                      mac_dinh BOOLEAN DEFAULT 0,
                                      ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
                                      FOREIGN KEY (khach_hang_id) REFERENCES KhachHang(khach_hang_id) ON DELETE CASCADE
                                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                                  
                                  $stmt = $pdo->prepare("SELECT phuong_thuc_id, loai_phuong_thuc, ten_hien_thi, thong_tin_chi_tiet, mac_dinh 
                                                         FROM PhuongThucThanhToan 
                                                         WHERE khach_hang_id = :kh_id 
                                                         ORDER BY mac_dinh DESC, ngay_tao DESC");
                                  $stmt->execute([':kh_id' => $khach_hang_id]);
                                  $savedMethods = $stmt->fetchAll();
                                  
                                  if (count($savedMethods) > 0) {
                                      foreach ($savedMethods as $method) {
                                          $phuong_thuc_id = $method['phuong_thuc_id'];
                                          $ten_hien_thi = htmlspecialchars($method['ten_hien_thi']);
                                          $loai_phuong_thuc = $method['loai_phuong_thuc'];
                                          $thong_tin = htmlspecialchars($method['thong_tin_chi_tiet'] ?? '');
                                          
                                          $icon_class = 'bank';
                                          $icon_html = '<i class="fas fa-university"></i>';
                                          
                                          if (stripos($ten_hien_thi, 'visa') !== false || stripos($ten_hien_thi, 'thẻ') !== false) {
                                              $icon_class = 'visa';
                                              $icon_html = '<i class="fab fa-cc-visa"></i>';
                                          } elseif (stripos($ten_hien_thi, 'paypal') !== false) {
                                              $icon_class = 'paypal';
                                              $icon_html = '<i class="fab fa-paypal"></i>';
                                          } elseif (stripos($ten_hien_thi, 'momo') !== false) {
                                              $icon_class = 'momo';
                                              $icon_html = '<img src="' . $assetsPath . 'img/momo.jpg" alt="Momo" style="width: 24px; height: 24px; border-radius: 50%;">';
                                          } elseif (stripos($ten_hien_thi, 'zalo') !== false) {
                                              $icon_class = 'zalo';
                                              $icon_html = '<img src="' . $assetsPath . 'img/zalopay.jpg" alt="ZaloPay" style="width: 24px; height: 24px; border-radius: 50%;">';
                                          }
                                          
                                          $display_info = $thong_tin ?: 'Đã lưu';
                                          if (stripos($display_info, '@') !== false) {
                                              $display_info = $display_info;
                                          } elseif (preg_match('/\d/', $display_info)) {
                                              $display_info = '**** ' . substr($display_info, -4);
                                          } else {
                                              $display_info = 'Đã lưu';
                                          }
                                          
                                          echo '<div class="saved-method-item">';
                                          echo '<div class="icon-brand ' . $icon_class . '">' . $icon_html . '</div>';
                                          echo '<div class="method-info">';
                                          echo '<strong>' . $ten_hien_thi . '</strong>';
                                          if ($method['mac_dinh']) {
                                              echo ' <span style="font-size: 11px; color: var(--primary);">(Mặc định)</span>';
                                          }
                                          echo '<span>' . $display_info . '</span>';
                                          echo '</div>';
                                          $deletePath = $isInUserFolder ? 'delete_payment_method.php' : 'user/delete_payment_method.php';
                                          echo '<form method="POST" action="' . $deletePath . '" style="display: inline;" onsubmit="return confirm(\'Bạn có chắc chắn muốn xóa phương thức thanh toán này?\');">';
                                          echo '<input type="hidden" name="phuong_thuc_id" value="' . (int)$phuong_thuc_id . '">';
                                          echo '<button type="submit" class="btn-icon btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>';
                                          echo '</form>';
                                          echo '</div>';
                                      }
                                  } else {
                                      echo '<p class="muted center" style="padding: 20px;">Bạn chưa có phương thức thanh toán nào được lưu.</p>';
                                  }
                              }
                          } catch (Exception $e) {
                              error_log("Error loading payment methods: " . $e->getMessage());
                              echo '<p class="muted center" style="padding: 20px;">Có lỗi xảy ra khi tải phương thức thanh toán.</p>';
                          }
                      } else {
                          echo '<p class="muted center" style="padding: 20px;">Vui lòng đăng nhập để xem phương thức thanh toán.</p>';
                      }
                      ?>
                  </div>
                  <hr style="border-color: var(--border-color); margin: 20px 0;">
                  <h3>Thêm phương thức mới</h3>
                  
                  <form action="<?php echo $savePaymentPath; ?>" method="POST" id="save-payment-form">
                      <div class="form-group">
                          <label for="payment-method-type">Loại phương thức</label>
                          <select id="payment-method-type" name="payment_type" required>
                              <option value="">-- Chọn loại phương thức --</option>
                              <option value="card">Thẻ tín dụng (Visa/Mastercard)</option>
                              <option value="bank">Chuyển khoản (Ngân hàng)</option>
                              <option value="ewallet">Ví điện tử (Momo/ZaloPay)</option>
                              <option value="paypal">PayPal</option>
                          </select>
                      </div>
                      
                      <input type="hidden" name="payment_type" id="payment-type-input" value="">
                      
                      <!-- Form thẻ tín dụng (Visa/Mastercard) -->
                      <div id="form-card" class="payment-form-section" style="display: none;">
                          <div class="form-group">
                              <label for="card-number-acc-modal">Số thẻ</label>
                              <input type="text" id="card-number-acc-modal" name="card_number" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" required>
                          </div>
                          <div class="form-group">
                              <label for="card-name-acc-modal">Tên chủ thẻ</label>
                              <input type="text" id="card-name-acc-modal" name="card_name" placeholder="NGUYEN VAN A" required>
                          </div>
                          <div class="grid-2" style="gap: 10px;">
                              <div class="form-group">
                                  <label for="card-expiry-acc-modal">Ngày hết hạn</label>
                                  <input type="text" id="card-expiry-acc-modal" name="card_expiry" placeholder="MM/YY" maxlength="5" required>
                              </div>
                              <div class="form-group">
                                  <label for="card-cvc-acc-modal">CVC</label>
                                  <input type="text" id="card-cvc-acc-modal" name="card_cvc" placeholder="123" maxlength="4" required>
                              </div>
                          </div>
                      </div>
                      
                      <!-- Form ví điện tử (Momo/ZaloPay) -->
                      <div id="form-ewallet" class="payment-form-section" style="display: none;">
                          <div class="form-group">
                              <label for="ewallet-type-acc-modal">Loại ví điện tử</label>
                              <select id="ewallet-type-acc-modal" name="ewallet_type" required>
                                  <option value="">-- Chọn loại ví --</option>
                                  <option value="Momo">Momo</option>
                                  <option value="ZaloPay">ZaloPay</option>
                                  <option value="VNPay">VNPay</option>
                                  <option value="ShopeePay">ShopeePay</option>
                                  <option value="Other">Khác</option>
                              </select>
                          </div>
                          <div class="form-group">
                              <label for="ewallet-account-acc-modal">Số tài khoản / Số điện thoại</label>
                              <input type="text" id="ewallet-account-acc-modal" name="ewallet_account" placeholder="Nhập số tài khoản hoặc số điện thoại" required>
                          </div>
                          <div class="form-group">
                              <label for="ewallet-name-acc-modal">Tên chủ tài khoản</label>
                              <input type="text" id="ewallet-name-acc-modal" name="ewallet_name" placeholder="Nhập tên chủ tài khoản" required>
                          </div>
                      </div>
                      
                      <!-- Form ngân hàng (Chuyển khoản) -->
                      <div id="form-bank" class="payment-form-section" style="display: none;">
                          <div class="form-group">
                              <label for="bank-name-acc-modal">Tên ngân hàng</label>
                              <input type="text" id="bank-name-acc-modal" name="bank_name" placeholder="Ví dụ: MB Bank, Vietcombank, BIDV" required>
                          </div>
                          <div class="form-group">
                              <label for="bank-account-acc-modal">Số tài khoản</label>
                              <input type="text" id="bank-account-acc-modal" name="bank_account" placeholder="Nhập số tài khoản" required>
                          </div>
                          <div class="form-group">
                              <label for="account-holder-acc-modal">Tên chủ tài khoản</label>
                              <input type="text" id="account-holder-acc-modal" name="account_holder" placeholder="NGUYEN VAN A" required>
                          </div>
                      </div>
                      
                      <!-- Form PayPal -->
                      <div id="form-paypal" class="payment-form-section" style="display: none;">
                          <div class="form-group">
                              <label for="paypal-email-acc-modal">Email PayPal</label>
                              <input type="email" id="paypal-email-acc-modal" name="paypal_email" placeholder="email@example.com" required>
                          </div>
                      </div>
                      
                      <button type="submit" class="btn">Lưu phương thức</button>
                  </form>
                </div>
              </section>

              <section id="acc-password" class="account-section-modal">
                <div class="card form-layout" style="background: transparent; border: none; box-shadow: none; padding: 10px 0 0 0;">
                  <form action="<?php echo $phpBasePath . 'update_password.php'; ?>" method="POST">
                    <div class="form-group">
                      <label for="acc-old-pass-modal">Mật khẩu cũ</label>
                      <input type="password" id="acc-old-pass-modal" name="old_password" required>
                    </div>
                    <div class="form-group">
                      <label for="acc-new-pass-modal">Mật khẩu mới</label>
                      <input type="password" id="acc-new-pass-modal" name="new_password" required>
                    </div>
                    <div class="form-group">
                      <label for="acc-confirm-pass-modal">Xác nhận mật khẩu mới</label>
                      <input type="password" id="acc-confirm-pass-modal" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn">Đổi mật khẩu</button>
                  </form>
                </div>
              </section>

          </div>
      </div>
    </div>
  </div>

<script>
// Xử lý hiển thị/ẩn form theo loại phương thức thanh toán
(function() {
    function initPaymentMethodForm() {
        const paymentMethodType = document.getElementById('payment-method-type');
        if (!paymentMethodType) {
            // Retry sau 100ms nếu chưa có element
            setTimeout(initPaymentMethodForm, 100);
            return;
        }
        
        const formSections = {
            'card': document.getElementById('form-card'),
            'bank': document.getElementById('form-bank'),
            'ewallet': document.getElementById('form-ewallet'),
            'paypal': document.getElementById('form-paypal')
        };
        
        const paymentTypeInput = document.getElementById('payment-type-input');
        
        // Hàm ẩn tất cả form sections
        function hideAllForms() {
            Object.values(formSections).forEach(section => {
                if (section) {
                    section.style.display = 'none';
                    // Reset required attributes và clear values
                    const inputs = section.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.removeAttribute('required');
                        input.value = '';
                    });
                }
            });
        }
        
        // Hàm hiển thị form tương ứng
        function showForm(type) {
            hideAllForms();
            
            if (formSections[type]) {
                formSections[type].style.display = 'block';
                // Set required cho các input trong form được chọn
                const inputs = formSections[type].querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], select');
                inputs.forEach(input => {
                    if (input.name) {
                        input.setAttribute('required', 'required');
                    }
                });
                
            }
            
            // Cập nhật hidden input
            if (paymentTypeInput) {
                paymentTypeInput.value = type;
            }
        }
        
        // Xử lý khi thay đổi dropdown
        paymentMethodType.addEventListener('change', function() {
            const selectedType = this.value;
            if (selectedType && formSections[selectedType]) {
                showForm(selectedType);
            } else {
                hideAllForms();
                if (paymentTypeInput) paymentTypeInput.value = '';
            }
        });
        
        // Khởi tạo: nếu đã có giá trị được chọn (khi reload form)
        if (paymentMethodType.value) {
            showForm(paymentMethodType.value);
        } else {
            // Đảm bảo ẩn các form khi khởi động
            hideAllForms();
        }
        
        // Xử lý format số thẻ (chỉ cho phép số và tự động thêm khoảng trắng)
        const cardNumberInput = document.getElementById('card-number-acc-modal');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                if (formattedValue.length <= 19) {
                    e.target.value = formattedValue;
                }
            });
        }
        
        // Xử lý format ngày hết hạn (MM/YY)
        const cardExpiryInput = document.getElementById('card-expiry-acc-modal');
        if (cardExpiryInput) {
            cardExpiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        }
        
        // Xử lý format CVC (chỉ số, tối đa 4 ký tự)
        const cardCvcInput = document.getElementById('card-cvc-acc-modal');
        if (cardCvcInput) {
            cardCvcInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
            });
        }
        
        // Xử lý format số tài khoản ví điện tử (cho phép chữ và số)
        const ewalletAccountInput = document.getElementById('ewallet-account-acc-modal');
        if (ewalletAccountInput) {
            ewalletAccountInput.addEventListener('input', function(e) {
                // Cho phép chữ, số và một số ký tự đặc biệt
                e.target.value = e.target.value.replace(/[^a-zA-Z0-9@._-]/g, '');
            });
        }
        
        // Xử lý format số tài khoản ngân hàng
        const bankAccountInput = document.getElementById('bank-account-acc-modal');
        if (bankAccountInput) {
            bankAccountInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        }
        
        // Validation trước khi submit
        const savePaymentForm = document.getElementById('save-payment-form');
        if (savePaymentForm) {
            savePaymentForm.addEventListener('submit', function(e) {
                const selectedType = paymentMethodType.value;
            
            if (!selectedType) {
                e.preventDefault();
                alert('Vui lòng chọn loại phương thức thanh toán');
                return false;
            }
            
            // Validate theo từng loại
            if (selectedType === 'card') {
                const cardNumber = document.getElementById('card-number-acc-modal').value.replace(/\s/g, '');
                const cardName = document.getElementById('card-name-acc-modal').value.trim();
                const cardExpiry = document.getElementById('card-expiry-acc-modal').value.trim();
                const cardCvc = document.getElementById('card-cvc-acc-modal').value.trim();
                
                if (!cardNumber || cardNumber.length < 13 || cardNumber.length > 19) {
                    e.preventDefault();
                    alert('Số thẻ phải có từ 13 đến 19 chữ số');
                    return false;
                }
                if (!cardName) {
                    e.preventDefault();
                    alert('Vui lòng nhập họ tên trên thẻ');
                    return false;
                }
                if (!cardExpiry || !/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                    e.preventDefault();
                    alert('Vui lòng nhập ngày hết hạn đúng định dạng MM/YY');
                    return false;
                }
                if (!cardCvc || cardCvc.length < 3) {
                    e.preventDefault();
                    alert('Vui lòng nhập CVC (3-4 chữ số)');
                    return false;
                }
            } else if (selectedType === 'ewallet') {
                const ewalletType = document.getElementById('ewallet-type-acc-modal').value.trim();
                const ewalletAccount = document.getElementById('ewallet-account-acc-modal').value.trim();
                const ewalletName = document.getElementById('ewallet-name-acc-modal').value.trim();
                
                if (!ewalletType) {
                    e.preventDefault();
                    alert('Vui lòng chọn loại ví điện tử');
                    return false;
                }
                if (!ewalletAccount) {
                    e.preventDefault();
                    alert('Vui lòng nhập số tài khoản hoặc số điện thoại');
                    return false;
                }
                if (!ewalletName) {
                    e.preventDefault();
                    alert('Vui lòng nhập tên chủ tài khoản');
                    return false;
                }
            } else if (selectedType === 'bank') {
                const bankName = document.getElementById('bank-name-acc-modal').value.trim();
                const bankAccount = document.getElementById('bank-account-acc-modal').value.trim();
                const accountHolder = document.getElementById('account-holder-acc-modal').value.trim();
                
                if (!bankName) {
                    e.preventDefault();
                    alert('Vui lòng nhập tên ngân hàng');
                    return false;
                }
                if (!bankAccount) {
                    e.preventDefault();
                    alert('Vui lòng nhập số tài khoản');
                    return false;
                }
                if (!accountHolder) {
                    e.preventDefault();
                    alert('Vui lòng nhập tên chủ tài khoản');
                    return false;
                }
            } else if (selectedType === 'paypal') {
                const paypalEmail = document.getElementById('paypal-email-acc-modal').value.trim();
                
                if (!paypalEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(paypalEmail)) {
                    e.preventDefault();
                    alert('Vui lòng nhập email PayPal hợp lệ');
                    return false;
                }
            }
        });
    }
});
</script>

