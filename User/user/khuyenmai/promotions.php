<?php
// Luôn bắt đầu session ở dòng đầu tiên
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Khuyến mãi - DFC Gym</title>
  <meta name="description" content="Khám phá các chương trình khuyến mãi hấp dẫn tại DFC Gym">
  <link rel="icon" type="image/png" href="../../assets/img/logo.png">
  <link rel="shortcut icon" type="image/png" href="../../assets/img/logo.png">
  <link rel="apple-touch-icon" href="../../assets/img/logo.png">
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries" onerror="console.error('Tailwind CDN failed to load'); this.onerror=null; this.src='https://cdn.jsdelivr.net/npm/tailwindcss@3.4.0/lib/index.min.js';"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#22c55e",
            "primary-dark": "#16a34a",
            "background-light": "#f6f8f6",
            "background-dark": "#102213",
          },
          fontFamily: {
            "display": ["Lexend", "sans-serif"]
          },
          borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
        },
      },
    };
  </script>
</head>

<body data-logged-in="<?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>" class="dark bg-background-dark font-display text-gray-300 antialiased">

  <?php if (isset($_SESSION['user'])): ?>
    <!-- Header Desktop cho người đã đăng nhập - chỉ hiển thị trên PC -->
    <header class="hidden lg:flex items-center justify-between whitespace-nowrap border-b border-solid border-white/10 px-6 sm:px-10 lg:px-20 py-4 fixed top-0 left-0 right-0 z-50 bg-background-dark/80 backdrop-blur-sm">
      <div class="flex items-center gap-2 text-white">
        <img src="../../assets/img/logo.png" alt="DFC Gym" class="h-14 w-auto self-center">
        <h2 class="nameweb text-white text-xl font-bold leading-none self-center">DFC GYM</h2>
      </div>
      <nav class="flex items-center gap-9">
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../../index.html">Trang chủ</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../goitap/packages.html">Gói tập</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="promotions.php">Khuyến mãi</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../lichtap/schedule.html">Lịch tập</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../hotro/support.html">Hỗ trợ</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../danhgia/review.html">Đánh giá</a>
      </nav>
      <div class="flex items-center gap-3">
        <span class="text-white/80 text-sm font-medium">Chào, <?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']); ?>!</span>
        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
          <a href="../admin/index.html" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-12 px-5 bg-primary text-background-dark text-sm font-bold leading-normal tracking-[0.015em]">Vào Admin</a>
        <?php endif; ?>
        <div class="user-menu-wrapper relative z-50">
          <button class="user-icon-button p-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 relative z-50 cursor-pointer" id="user-menu-button" type="button" aria-label="Mở menu người dùng">
            <i class="fas fa-user-circle text-xl pointer-events-none"></i>
          </button>
          <div class="user-dropdown hidden absolute top-full right-0 mt-2 bg-background-dark border border-white/10 rounded-lg shadow-lg min-w-[200px] z-[60]" id="user-dropdown-menu">
            <a href="#profile" data-modal-target="profile-modal" class="block px-4 py-2 text-white/80 hover:text-white hover:bg-white/10"><i class="fas fa-user-edit mr-2"></i> Thông tin tài khoản</a>
            <a href="#inbox" data-modal-target="inbox-modal" class="block px-4 py-2 text-white/80 hover:text-white hover:bg-white/10"><i class="fas fa-inbox mr-2"></i> Hòm thư</a>
            <a href="#my-packages" data-modal-target="my-packages-modal" class="block px-4 py-2 text-white/80 hover:text-white hover:bg-white/10"><i class="fas fa-box mr-2"></i> Gói tập của bạn</a>
            <a href="#payment-history" data-modal-target="payment-history-modal" class="block px-4 py-2 text-white/80 hover:text-white hover:bg-white/10"><i class="fas fa-history mr-2"></i> Lịch sử thanh toán</a>
            <a href="#payment-management" data-modal-target="payment-management-modal" class="block px-4 py-2 text-white/80 hover:text-white hover:bg-white/10"><i class="fas fa-credit-card mr-2"></i> Quản lý thanh toán</a>
            <a href="#change-password" data-modal-target="change-password-modal" class="block px-4 py-2 text-white/80 hover:text-white hover:bg-white/10"><i class="fas fa-key mr-2"></i> Đổi mật khẩu</a>
            <a href="../getset/logout.php" class="block px-4 py-2 text-red-400 hover:text-red-300 hover:bg-white/10"><i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất</a>
          </div>
        </div>
      </div>
    </header>
    
    <!-- Mobile Header với Logo và Menu Button - chỉ hiển thị trên mobile/tablet -->
    <header class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-background-dark/80 backdrop-blur-sm border-b border-solid border-white/10 px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-2 text-white">
        <img src="../../assets/img/logo.png" alt="DFC Gym" class="h-12 w-auto self-center">
        <h2 class="nameweb text-white text-lg font-bold leading-none self-center">DFC GYM</h2>
      </div>
      <button id="mobile-menu-toggle-logged-in" class="p-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 relative z-50 cursor-pointer" type="button" aria-label="Mở menu">
        <span class="material-symbols-outlined text-2xl pointer-events-none">menu</span>
      </button>
    </header>
    <!-- Mobile Menu cho người đã đăng nhập - chỉ hiển thị trên mobile/tablet -->
    <div id="mobile-menu-logged-in" class="hidden lg:hidden fixed inset-0 z-[100] bg-background-dark/95 backdrop-blur-sm" style="display: none;">
      <div class="flex flex-col h-full w-full" style="display: flex;">
        <div class="flex items-center justify-between p-6 border-b border-white/10 flex-shrink-0">
          <div class="flex items-center gap-2 text-white">
            <img src="../../assets/img/logo.png" alt="DFC Gym" class="h-12 w-auto">
            <h2 class="nameweb nameweb-mobile text-white text-xl font-bold">DFC GYM</h2>
          </div>
          <button id="mobile-menu-close-logged-in" class="p-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 relative z-[101] cursor-pointer" type="button" aria-label="Đóng menu">
            <span class="material-symbols-outlined text-2xl pointer-events-none">close</span>
          </button>
        </div>
        <nav class="flex flex-col gap-4 p-6 flex-1 overflow-y-auto w-full">
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../../index.html">Trang chủ</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../goitap/packages.html">Gói tập</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="promotions.php">Khuyến mãi</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../lichtap/schedule.html">Lịch tập</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../hotro/support.html">Hỗ trợ</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../danhgia/review.html">Đánh giá</a>
          <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../admin/index.html">Vào Admin</a>
          <?php endif; ?>
          <div class="flex flex-col gap-2 mt-auto pt-6 border-t border-white/10">
            <div class="text-white/80 text-sm mb-2">Chào, <?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']); ?>!</div>
            <a href="#profile" data-modal-target="profile-modal" class="text-white/80 hover:text-white transition-colors text-base font-medium py-2 flex items-center gap-2"><i class="fas fa-user-edit"></i> Thông tin tài khoản</a>
            <a href="#inbox" data-modal-target="inbox-modal" class="text-white/80 hover:text-white transition-colors text-base font-medium py-2 flex items-center gap-2"><i class="fas fa-inbox"></i> Hòm thư</a>
            <a href="#my-packages" data-modal-target="my-packages-modal" class="text-white/80 hover:text-white transition-colors text-base font-medium py-2 flex items-center gap-2"><i class="fas fa-box"></i> Gói tập của bạn</a>
            <a href="#payment-history" data-modal-target="payment-history-modal" class="text-white/80 hover:text-white transition-colors text-base font-medium py-2 flex items-center gap-2"><i class="fas fa-history"></i> Lịch sử thanh toán</a>
            <a href="#payment-management" data-modal-target="payment-management-modal" class="text-white/80 hover:text-white transition-colors text-base font-medium py-2 flex items-center gap-2"><i class="fas fa-credit-card"></i> Quản lý thanh toán</a>
            <a href="#change-password" data-modal-target="change-password-modal" class="text-white/80 hover:text-white transition-colors text-base font-medium py-2 flex items-center gap-2"><i class="fas fa-key"></i> Đổi mật khẩu</a>
            <a href="../getset/logout.php" class="text-red-400 hover:text-red-300 transition-colors text-base font-medium py-2 flex items-center gap-2"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
          </div>
        </nav>
      </div>
    </div>
  <?php else: ?>
    <!-- Header cho người chưa đăng nhập -->
    <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-white/10 px-6 sm:px-10 lg:px-20 py-4 fixed top-0 left-0 right-0 z-50 bg-background-dark/80 backdrop-blur-sm">
      <div class="flex items-center gap-2 text-white">
        <img src="../../assets/img/logo.png" alt="DFC Gym" class="h-14 w-auto self-center">
        <h2 class="nameweb text-white text-xl font-bold leading-none self-center">DFC GYM</h2>
      </div>
      <nav class="hidden lg:flex items-center gap-9">
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../../index.html#top">Trang chủ</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../../index.html#dich-vu">Dịch vụ</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../../index.html#thiet-bi">Thiết bị</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../../index.html#hlv">HLV</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="promotions.php">Khuyến mãi</a>
        <a class="text-white/80 hover:text-white transition-colors text-sm font-medium leading-normal" href="../../index.html#lien-he">Liên hệ</a>
      </nav>
      <div class="flex items-center gap-3">
        <button type="button" class="hidden lg:flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-12 px-5 bg-primary text-background-dark text-sm font-bold leading-normal tracking-[0.015em]" data-modal-target="register-modal">Tham gia ngay</button>
        <button type="button" class="hidden lg:flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-12 px-5 bg-white/10 text-white text-sm font-bold leading-normal tracking-[0.015em]" data-modal-target="login-modal">Đăng nhập</button>
        <button id="mobile-menu-toggle-guest" class="lg:hidden p-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 relative z-50 cursor-pointer" type="button" aria-label="Mở menu">
          <span class="material-symbols-outlined pointer-events-none">menu</span>
        </button>
      </div>
    </header>
    <!-- Mobile Menu cho người chưa đăng nhập -->
    <div id="mobile-menu-guest" class="hidden lg:hidden fixed inset-0 z-[100] bg-background-dark/95 backdrop-blur-sm" style="display: none;">
      <div class="flex flex-col h-full w-full" style="display: flex;">
        <div class="flex items-center justify-between p-6 border-b border-white/10 flex-shrink-0">
          <div class="flex items-center gap-4 text-white">
            <img src="../../assets/img/logo.png" alt="DFC Gym" class="h-12 w-auto self-center">
            <h2 class="nameweb nameweb-mobile text-white text-xl font-bold leading-none self-center">DFC GYM</h2>
          </div>
          <button id="mobile-menu-close-guest" class="p-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 relative z-[101] cursor-pointer" type="button" aria-label="Đóng menu">
            <span class="material-symbols-outlined pointer-events-none">close</span>
          </button>
        </div>
        <nav class="flex flex-col gap-4 p-6 flex-1 overflow-y-auto w-full">
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../../index.html#top">Trang chủ</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../../index.html#dich-vu">Dịch vụ</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../../index.html#thiet-bi">Thiết bị</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../../index.html#hlv">HLV</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="promotions.php">Khuyến mãi</a>
          <a class="text-white/80 hover:text-white transition-colors text-base font-medium py-2" href="../../index.html#lien-he">Liên hệ</a>
          <div class="flex flex-col gap-3 mt-auto pt-6 border-t border-white/10">
            <button type="button" class="flex items-center justify-center rounded-full h-14 px-7 bg-primary text-background-dark text-base font-bold" data-modal-target="register-modal">Tham gia ngay</button>
            <button type="button" class="flex items-center justify-center rounded-full h-14 px-7 bg-white/10 text-white text-base font-bold" data-modal-target="login-modal">Đăng nhập</button>
          </div>
        </nav>
      </div>
    </div>
  <?php endif; ?>
  
  <div class="<?php echo isset($_SESSION['user']) ? 'lg:pt-28 pt-20' : 'lg:pt-28 pt-20'; ?>">
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
      <div class="text-center max-w-2xl mx-auto mb-12">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-4">Chương Trình Khuyến Mãi</h1>
        <p class="text-gray-400 text-lg">Khám phá các ưu đãi hấp dẫn và tiết kiệm chi phí khi đăng ký gói tập tại DFC Gym</p>
      </div>
      
      <?php
      // Lấy danh sách khuyến mãi từ database
      $promotions = [];
      try {
          // Kiểm tra xem đã require config và db_connect chưa
          if (!defined('DB_HOST')) {
              require_once __DIR__ . '/../../database/config.php';
          }
          if (!isset($pdo)) {
              require_once __DIR__ . '/../../database/db_connect.php';
          }
          
          // Kiểm tra xem $pdo đã được định nghĩa chưa
          if (!isset($pdo) || !$pdo) {
              throw new Exception("Database connection not available");
          }
          
          // Lấy tất cả khuyến mãi có trạng thái "Đang áp dụng" và còn trong thời gian hiệu lực
          $today = date('Y-m-d');
          $stmt = $pdo->prepare("
              SELECT 
                  km.khuyen_mai_id,
                  km.ma_khuyen_mai,
                  km.ten_khuyen_mai,
                  km.mo_ta,
                  km.loai_giam,
                  km.gia_tri_giam,
                  km.giam_toi_da,
                  km.gia_tri_don_hang_toi_thieu,
                  km.ngay_bat_dau,
                  km.ngay_ket_thuc,
                  km.so_luong_ma,
                  km.so_luong_da_dung,
                  km.trang_thai,
                  gt.ten_goi AS ten_goi_tap
              FROM KhuyenMai km
              LEFT JOIN GoiTap gt ON km.ap_dung_cho_goi_tap_id = gt.goi_tap_id
              WHERE km.trang_thai = 'Đang áp dụng'
                AND km.ngay_bat_dau <= :today
                AND km.ngay_ket_thuc >= :today
                AND (km.so_luong_ma IS NULL OR km.so_luong_da_dung < km.so_luong_ma)
              ORDER BY 
                  km.gia_tri_giam DESC,
                  km.ngay_bat_dau DESC
          ");
          $stmt->execute([':today' => $today]);
          $promotions = $stmt->fetchAll();
      } catch (PDOException $e) {
          error_log("Database error getting promotions: " . $e->getMessage());
          $promotions = [];
      } catch (Exception $e) {
          error_log("Error getting promotions: " . $e->getMessage());
          $promotions = [];
      }
      
      // Hàm format giá tiền
      function formatPrice($price) {
          return number_format($price, 0, ',', '.') . '₫';
      }
      
      // Hàm format ngày
      function formatDate($date) {
          if (empty($date)) return '';
          $timestamp = strtotime($date);
          return date('d/m/Y', $timestamp);
      }
      
      // Hàm tính số ngày còn lại
      function getDaysRemaining($endDate) {
          if (empty($endDate)) return 0;
          $today = strtotime(date('Y-m-d'));
          $end = strtotime($endDate);
          $diff = $end - $today;
          return max(0, ceil($diff / 86400));
      }
      ?>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($promotions)): ?>
          <div class="col-span-full text-center py-12">
            <div class="mb-4">
              <i class="fas fa-tag text-6xl text-gray-600"></i>
            </div>
            <p class="text-gray-400 text-lg mb-2">Hiện tại chưa có chương trình khuyến mãi nào.</p>
            <p class="text-gray-500 text-sm">Vui lòng quay lại sau để cập nhật các ưu đãi mới nhất!</p>
          </div>
        <?php else: ?>
          <?php foreach ($promotions as $promo): 
            $daysRemaining = getDaysRemaining($promo['ngay_ket_thuc']);
            $isPercent = ($promo['loai_giam'] === 'Phần trăm');
            $discountValue = $promo['gia_tri_giam'];
            $isHot = ($discountValue >= ($isPercent ? 30 : 500000));
            $remainingCodes = ($promo['so_luong_ma'] !== null) ? max(0, $promo['so_luong_ma'] - $promo['so_luong_da_dung']) : null;
          ?>
          <div class="bg-gradient-to-br from-gray-800/50 to-gray-900/50 rounded-xl p-6 border border-gray-700/50 hover:border-primary/50 transition-all duration-300 relative overflow-hidden <?php echo $isHot ? 'ring-2 ring-primary/50' : ''; ?>">
            <?php if ($isHot): ?>
            <div class="absolute top-0 right-0 bg-gradient-to-r from-red-500 to-orange-500 text-white text-xs font-bold px-4 py-1.5 rounded-bl-lg z-10">
              <i class="fas fa-fire mr-1"></i> HOT
            </div>
            <?php endif; ?>
            
            <div class="mb-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-primary uppercase tracking-wider"><?php echo htmlspecialchars($promo['ma_khuyen_mai']); ?></span>
                <?php if ($daysRemaining > 0): ?>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                  <i class="fas fa-clock"></i>
                  <?php echo $daysRemaining; ?> ngày
                </span>
                <?php endif; ?>
              </div>
              <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($promo['ten_khuyen_mai']); ?></h3>
            </div>
            
            <div class="mb-4 p-4 bg-gradient-to-r from-primary/20 to-primary/10 rounded-lg border border-primary/30">
              <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-primary">
                  <?php if ($isPercent): ?>
                    -<?php echo number_format($discountValue, 0); ?>%
                  <?php else: ?>
                    -<?php echo formatPrice($discountValue); ?>
                  <?php endif; ?>
                </span>
                <?php if ($promo['giam_toi_da'] && $isPercent): ?>
                <span class="text-sm text-gray-400">
                  (Tối đa <?php echo formatPrice($promo['giam_toi_da']); ?>)
                </span>
                <?php endif; ?>
              </div>
            </div>
            
            <?php if (!empty($promo['mo_ta'])): ?>
            <p class="text-gray-300 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars($promo['mo_ta']); ?></p>
            <?php endif; ?>
            
            <div class="space-y-2 mb-4 text-sm text-gray-400">
              <?php if ($promo['gia_tri_don_hang_toi_thieu']): ?>
              <div class="flex items-center gap-2">
                <i class="fas fa-shopping-cart text-primary w-4"></i>
                <span>Áp dụng cho đơn hàng từ <?php echo formatPrice($promo['gia_tri_don_hang_toi_thieu']); ?></span>
              </div>
              <?php endif; ?>
              
              <?php if ($promo['ten_goi_tap']): ?>
              <div class="flex items-center gap-2">
                <i class="fas fa-dumbbell text-primary w-4"></i>
                <span>Áp dụng cho: <?php echo htmlspecialchars($promo['ten_goi_tap']); ?></span>
              </div>
              <?php else: ?>
              <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-primary w-4"></i>
                <span>Áp dụng cho tất cả gói tập</span>
              </div>
              <?php endif; ?>
              
              <?php if ($remainingCodes !== null): ?>
              <div class="flex items-center gap-2">
                <i class="fas fa-ticket-alt text-primary w-4"></i>
                <span>Còn lại: <strong class="text-white"><?php echo $remainingCodes; ?></strong> mã</span>
              </div>
              <?php endif; ?>
              
              <div class="flex items-center gap-2">
                <i class="fas fa-calendar-alt text-primary w-4"></i>
                <span>Hết hạn: <?php echo formatDate($promo['ngay_ket_thuc']); ?></span>
              </div>
            </div>
            
            <?php if (!isset($_SESSION['user'])): ?>
              <a href="../../index.html#dang-ky" class="block w-full bg-primary text-background-dark font-semibold py-3 rounded-lg hover:bg-primary-dark transition-colors text-center">
                <i class="fas fa-gift mr-2"></i> Đăng ký ngay
              </a>
            <?php else: ?>
              <a href="../goitap/packages.html" class="block w-full bg-primary text-background-dark font-semibold py-3 rounded-lg hover:bg-primary-dark transition-colors text-center">
                <i class="fas fa-shopping-bag mr-2"></i> Chọn gói tập
              </a>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Modals và Scripts -->
  <?php if (isset($_SESSION['user'])): ?>
    <?php require_once __DIR__ . '/../includes/account-modal.php'; ?>
    <!-- Simple Modals - thay thế account-modal phức tạp -->
    <?php include __DIR__ . '/../includes/simple-modals.php'; ?>
    
    <!-- Inbox Modal -->
    <div class="modal-overlay hidden" id="inbox-modal">
      <div class="modal-content">
        <button class="modal-close-btn" id="inbox-modal-close" onclick="if(typeof closeInboxModal === 'function') closeInboxModal(); else document.getElementById('inbox-modal').classList.remove('active'); return false;"><i class="fas fa-times"></i></button>
        
        <div class="modal-header">
          <a href="../../index.html" class="brand">
            <img src="../../assets/img/logo.png" alt="Logo">
            <span><i class="fas fa-inbox"></i> Hòm thư</span>
          </a>
          <button id="mark-all-read-btn" onclick="if(typeof markAllInboxAsRead === 'function') markAllInboxAsRead();" 
                  title="Đánh dấu tất cả đã đọc"
                  class="mark-all-read-btn">
            <i class="fas fa-check-double"></i>
            <span class="mark-all-read-text">Đã đọc tất cả</span>
          </button>
        </div>
        
        <div class="modal-body">
          <p class="muted center">
            Xem thông báo, phản hồi hỗ trợ và cập nhật từ DFC Gym
          </p>

          <div class="inbox-stats">
            <div class="stat-card">
              <div class="stat-number" id="inbox-total-count">0</div>
              <div class="stat-label">Tổng số thông báo</div>
            </div>
            <div class="stat-card">
              <div class="stat-number" id="inbox-unread-count">0</div>
              <div class="stat-label">Chưa đọc</div>
            </div>
          </div>

          <div id="inbox-notifications-list">
            <div class="empty-inbox">
              <i class="fas fa-inbox"></i>
              <p>Bạn chưa có thông báo nào</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <?php require_once __DIR__ . '/../includes/auth-modals.php'; ?>
  <?php endif; ?>

  <!-- Scripts -->
  <!-- Load auth.js trực tiếp cho phần chưa đăng nhập để đảm bảo modal hoạt động -->
  <?php if (!isset($_SESSION['user'])): ?>
    <script src="../../assets/js/auth.js?v=7"></script>
  <?php endif; ?>
  
  <script>
    // Load scripts sau khi DOM ready để tránh conflict
    document.addEventListener('DOMContentLoaded', function() {
      // Load navigation.js
      const navScript = document.createElement('script');
      navScript.src = '../../assets/js/navigation.js?v=4';
      document.body.appendChild(navScript);
      
      // Load user-menu.js hoặc auth.js sau khi navigation.js load xong
      navScript.onload = function() {
        <?php if (isset($_SESSION['user'])): ?>
          const userMenuScript = document.createElement('script');
          userMenuScript.src = '../../assets/js/user-menu.js?v=5';
          document.body.appendChild(userMenuScript);
          
          // Khởi tạo user menu sau khi script load xong
          userMenuScript.onload = function() {
            setTimeout(function() {
              if (window.initUserMenu) {
                window.initUserMenu();
              }
            }, 200);
            
            // Load auth.js - CẦN THIẾT để xử lý mở tất cả các modal
            const authScript = document.createElement('script');
            authScript.src = '../../assets/js/auth.js?v=7';
            document.body.appendChild(authScript);
            
            // Load account-modal.js sau khi auth.js load xong
            authScript.onload = function() {
              const accountModalScript = document.createElement('script');
              accountModalScript.src = '../../assets/js/account-modal.js?v=1';
              document.body.appendChild(accountModalScript);
              
              // Load inbox-modal-v2.js
              const inboxModalScript = document.createElement('script');
              inboxModalScript.src = '../../assets/js/inbox-modal-v2.js?v=1';
              document.body.appendChild(inboxModalScript);
              
              // Load modals-loader.js sau khi inbox modal script load xong
              inboxModalScript.onload = function() {
                const modalsLoaderScript = document.createElement('script');
                modalsLoaderScript.src = '../../assets/js/modals-loader.js?v=3';
                document.body.appendChild(modalsLoaderScript);
              };
            };
          };
        <?php else: ?>
          // auth.js đã được load trực tiếp trong HTML, không cần load lại
          console.log('Auth.js should already be loaded');
          if (window.openModalById) {
            console.log('openModalById function is available');
          } else {
            console.warn('openModalById function not found, auth.js may not have loaded');
          }
        <?php endif; ?>
      };
      
      // Mobile Menu Toggle - Xử lý riêng cho logged-in và guest
      setTimeout(function() {
        initMobileMenu();
      }, 300);
    });
    
    // Mobile Menu Toggle - Xử lý riêng cho logged-in và guest
    function initMobileMenu() {
      console.log('Initializing mobile menu...');
      
      // Menu cho người đã đăng nhập
      const loggedInToggle = document.getElementById('mobile-menu-toggle-logged-in');
      const loggedInMenu = document.getElementById('mobile-menu-logged-in');
      const loggedInClose = document.getElementById('mobile-menu-close-logged-in');
      
      console.log('Logged in elements:', { loggedInToggle, loggedInMenu, loggedInClose });
      
      if (loggedInToggle && loggedInMenu && loggedInClose) {
        // Remove any existing listeners by cloning
        const newToggle = loggedInToggle.cloneNode(true);
        loggedInToggle.parentNode.replaceChild(newToggle, loggedInToggle);
        
          newToggle.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          console.log('Toggle clicked, opening menu');
          // Force remove hidden class and set display
          loggedInMenu.classList.remove('hidden');
          loggedInMenu.style.display = 'flex';
          loggedInMenu.style.visibility = 'visible';
          loggedInMenu.style.opacity = '1';
          loggedInMenu.style.zIndex = '100';
          // Force inner div display
          const innerDiv = loggedInMenu.querySelector('div:first-child');
          if (innerDiv) {
            innerDiv.style.display = 'flex';
          }
          document.body.style.overflow = 'hidden';
        }, true); // Use capture phase
        
        loggedInClose.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          console.log('Close clicked, closing menu');
          loggedInMenu.classList.add('hidden');
          loggedInMenu.style.display = 'none';
          loggedInMenu.style.visibility = 'hidden';
          document.body.style.overflow = '';
        }, true);
        
        // Close when clicking on links
        const loggedInLinks = loggedInMenu.querySelectorAll('a[href]:not([data-modal-target])');
        loggedInLinks.forEach(function(link) {
          link.addEventListener('click', function() {
            loggedInMenu.classList.add('hidden');
            loggedInMenu.style.display = 'none';
            document.body.style.overflow = '';
          });
        });
        
        // Close when clicking outside
        loggedInMenu.addEventListener('click', function(e) {
          if (e.target === loggedInMenu) {
            loggedInMenu.classList.add('hidden');
            loggedInMenu.style.display = 'none';
            document.body.style.overflow = '';
          }
        });
      }
      
      // Menu cho người chưa đăng nhập
      const guestToggle = document.getElementById('mobile-menu-toggle-guest');
      const guestMenu = document.getElementById('mobile-menu-guest');
      const guestClose = document.getElementById('mobile-menu-close-guest');
      
      console.log('Guest elements:', { guestToggle, guestMenu, guestClose });
      
      if (guestToggle && guestMenu && guestClose) {
        // Remove any existing listeners by cloning
        const newGuestToggle = guestToggle.cloneNode(true);
        guestToggle.parentNode.replaceChild(newGuestToggle, guestToggle);
        
          newGuestToggle.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          console.log('Guest toggle clicked, opening menu');
          // Force remove hidden class and set display
          guestMenu.classList.remove('hidden');
          guestMenu.style.display = 'flex';
          guestMenu.style.visibility = 'visible';
          guestMenu.style.opacity = '1';
          guestMenu.style.zIndex = '100';
          // Force inner div display
          const innerDiv = guestMenu.querySelector('div:first-child');
          if (innerDiv) {
            innerDiv.style.display = 'flex';
          }
          document.body.style.overflow = 'hidden';
        }, true);
        
        guestClose.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          console.log('Guest close clicked, closing menu');
          guestMenu.classList.add('hidden');
          guestMenu.style.display = 'none';
          guestMenu.style.visibility = 'hidden';
          document.body.style.overflow = '';
        }, true);
        
        // Close when clicking on links
        const guestLinks = guestMenu.querySelectorAll('a[href]:not([data-modal-target])');
        guestLinks.forEach(function(link) {
          link.addEventListener('click', function() {
            guestMenu.classList.add('hidden');
            guestMenu.style.display = 'none';
            document.body.style.overflow = '';
          });
        });
        
        // Close when clicking outside
        guestMenu.addEventListener('click', function(e) {
          if (e.target === guestMenu) {
            guestMenu.classList.add('hidden');
            guestMenu.style.display = 'none';
            document.body.style.overflow = '';
          }
        });
      }
    }
    
    // Fallback: Initialize ngay nếu DOM đã sẵn sàng
    if (document.readyState !== 'loading') {
      setTimeout(initMobileMenu, 500);
    }
  </script>
</body>
</html>

