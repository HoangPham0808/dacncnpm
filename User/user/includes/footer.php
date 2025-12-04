<?php
// Xác định đường dẫn base dựa trên vị trí file
// Nếu file đang được include từ user/[subfolder]/, dùng ../../
// Nếu từ user/, dùng ../
// Nếu từ root, không dùng ../
$isInUserFolder = (strpos(__DIR__, '/user/') !== false || strpos(__DIR__, '\\user\\') !== false);
// Kiểm tra xem có đang ở trong thư mục con (như goitap/, danhgia/, etc.) không
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isInSubFolder = (strpos($scriptPath, '/goitap/') !== false || strpos($scriptPath, '\\goitap\\') !== false ||
                  strpos($scriptPath, '/danhgia/') !== false || strpos($scriptPath, '\\danhgia\\') !== false ||
                  strpos($scriptPath, '/hotro/') !== false || strpos($scriptPath, '\\hotro\\') !== false ||
                  strpos($scriptPath, '/lichtap/') !== false || strpos($scriptPath, '\\lichtap\\') !== false ||
                  strpos($scriptPath, '/homthu/') !== false || strpos($scriptPath, '\\homthu\\') !== false ||
                  strpos($scriptPath, '/thanhtoan/') !== false || strpos($scriptPath, '\\thanhtoan\\') !== false);
// Kiểm tra xem script đang chạy có nằm trong /user/ hay không
$scriptInUserFolder = (strpos($scriptPath, '/user/') !== false || strpos($scriptPath, '\\user\\') !== false);
// Xác định base path: nếu script không nằm trong /user/, thì từ root (không cần ../)
$basePath = ($scriptInUserFolder && $isInSubFolder) ? '../../' : ($scriptInUserFolder ? '../' : '');
$assetsPath = $basePath . 'assets/';
$indexPath = $basePath . 'index.html';
$packagesPath = $scriptInUserFolder ? 'goitap/packages.html' : 'user/goitap/packages.html';
$schedulePath = $scriptInUserFolder ? 'lichtap/schedule.html' : 'user/lichtap/schedule.html';
$supportPath = $scriptInUserFolder ? 'hotro/support.html' : 'user/hotro/support.html';
$reviewPath = $scriptInUserFolder ? 'danhgia/review.html' : 'user/danhgia/review.html';
?>
<footer class="footer-new">
  <div class="container">
    <div class="footer-new-grid">
      <div class="footer-new-col" style="grid-column: span 1 / span 2;">
        <a class="brand" href="<?php echo $indexPath; ?>">
          <img src="<?php echo $assetsPath; ?>img/logo.png" alt="DFC Gym" class="logo">
          <span>DFC GYM</span>
        </a>
        <p>Bền bỉ • Dẻo dai • Tự tin. Đồng hành cùng bạn trên hành trình chinh phục vóc dáng và sức khỏe.</p>
      </div>
      <div class="footer-new-col">
        <h4>Liên kết</h4>
        <a href="<?php echo $indexPath; ?>#gioi-thieu">Giới thiệu</a>
        <a href="<?php echo $indexPath; ?>#dich-vu">Dịch vụ</a>
        <a href="<?php echo $packagesPath; ?>">Gói tập</a>
        <?php if ($isInUserFolder): ?>
          <a href="<?php echo $schedulePath; ?>">Lịch tập</a>
        <?php endif; ?>
        <a href="<?php echo $indexPath; ?>#hlv">HLV</a>
      </div>
      <div class="footer-new-col">
        <h4>Dịch vụ</h4>
        <a href="<?php echo $indexPath; ?>#dich-vu">HLV cá nhân 1-1</a>
        <a href="<?php echo $indexPath; ?>#dich-vu">Lớp nhóm (Yoga, HIIT)</a>
        <a href="<?php echo $indexPath; ?>#dich-vu">Đo InBody & Tư vấn</a>
      </div>
      <div class="footer-new-col">
        <h4>Liên hệ</h4>
        <p style="margin-top: 0;">
          <strong>Hotline:</strong> 0912 345 678<br>
          <strong>Email:</strong> contact@dfcgym.vn<br>
          <strong>Địa chỉ:</strong> 123 Đường Fitness, Hà Nội
        </p>
        <div class="footer-new-socials">
          <a href="#" aria-label="Facebook">FB</a>
          <a href="#" aria-label="Instagram">IG</a>
          <a href="#" aria-label="Tiktok">TK</a>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-new-bottom">
    <div class="container"> © 2025 DFC Gym. All rights reserved. </div>
  </div>
</footer>

