<?php
require_once __DIR__ . '/management_function.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đánh giá</title>
    <link rel="stylesheet" href="management_reviews.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-star"></i> Quản lý Đánh giá</h1>
            <div class="header-actions">
                <button class="btn btn-back" onclick="window.location.href='../management_support/management_support.php'">
                    <i class="fas fa-arrow-left"></i> Quay lại Hỗ trợ
                </button>
            </div>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['avg_rating'], 1); ?>/5.0</h3>
                    <p>Điểm trung bình</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_reviews']); ?></h3>
                    <p>Tổng đánh giá</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['five_star']); ?></h3>
                    <p>Đánh giá 5 sao</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['this_month']); ?></h3>
                    <p>Đánh giá tháng này</p>
                </div>
            </div>
        </div>

        <!-- Phân bố đánh giá theo sao -->
        <div class="rating-distribution">
            <h2><i class="fas fa-chart-bar"></i> Phân bố đánh giá</h2>
            <div class="distribution-chart">
                <?php for ($i = 5; $i >= 1; $i--): 
                    $count = $stats['distribution'][$i] ?? 0;
                    $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews'] * 100) : 0;
                ?>
                <div class="distribution-row">
                    <div class="stars">
                        <?php for ($j = 0; $j < $i; $j++): ?>
                            <i class="fas fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="count"><?php echo $count; ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-section">
            <select class="filter-select" id="typeFilter" onchange="filterReviews()">
                <option value="">Tất cả loại đánh giá</option>
                <option value="Dịch vụ" <?php echo ($typeFilter === 'Dịch vụ') ? 'selected' : ''; ?>>Dịch vụ</option>
                <option value="Thiết bị" <?php echo ($typeFilter === 'Thiết bị') ? 'selected' : ''; ?>>Thiết bị</option>
                <option value="Huấn luyện viên" <?php echo ($typeFilter === 'Huấn luyện viên') ? 'selected' : ''; ?>>Huấn luyện viên</option>
                <option value="Gói tập" <?php echo ($typeFilter === 'Gói tập') ? 'selected' : ''; ?>>Gói tập</option>
                <option value="Tổng thể" <?php echo ($typeFilter === 'Tổng thể') ? 'selected' : ''; ?>>Tổng thể</option>
            </select>
            <select class="filter-select" id="ratingFilter" onchange="filterReviews()">
                <option value="">Tất cả đánh giá</option>
                <option value="5" <?php echo ($ratingFilter === '5') ? 'selected' : ''; ?>>5 sao</option>
                <option value="4" <?php echo ($ratingFilter === '4') ? 'selected' : ''; ?>>4 sao</option>
                <option value="3" <?php echo ($ratingFilter === '3') ? 'selected' : ''; ?>>3 sao</option>
                <option value="2" <?php echo ($ratingFilter === '2') ? 'selected' : ''; ?>>2 sao</option>
                <option value="1" <?php echo ($ratingFilter === '1') ? 'selected' : ''; ?>>1 sao</option>
            </select>
        </div>

        <!-- Danh sách đánh giá -->
        <div class="reviews-list">
            <?php if (empty($reviewsList)): ?>
                <div class="no-data">
                    <i class="fas fa-star-half-alt"></i>
                    <p>Chưa có đánh giá nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviewsList as $review): 
                    $ten_khach_hang = htmlspecialchars($review['ten_khach_hang'] ?? 'Khách hàng');
                    $loai = htmlspecialchars($review['loai_danh_gia']);
                    $diem = intval($review['diem_danh_gia']);
                    $noi_dung = nl2br(htmlspecialchars($review['noi_dung'] ?? ''));
                    $ngay = formatDate($review['ngay_danh_gia']);
                    $target = '';
                    
                    // Xác định đối tượng đánh giá
                    if ($review['ten_pt']) {
                        $target = 'PT: ' . htmlspecialchars($review['ten_pt']);
                    } elseif ($review['ten_thiet_bi']) {
                        $target = htmlspecialchars($review['ten_thiet_bi']);
                    } elseif ($review['ten_goi_tap']) {
                        $target = htmlspecialchars($review['ten_goi_tap']);
                    }
                ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h3><?php echo $ten_khach_hang; ?></h3>
                                <div class="review-meta">
                                    <span class="review-type">
                                        <i class="fas fa-tag"></i> <?php echo $loai; ?>
                                    </span>
                                    <?php if ($target): ?>
                                        <span class="review-target">
                                            <i class="fas fa-arrow-right"></i> <?php echo $target; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo ($i <= $diem) ? ' active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-number"><?php echo $diem; ?>/5</span>
                        </div>
                    </div>
                    
                    <?php if ($noi_dung): ?>
                    <div class="review-content">
                        <p><?php echo $noi_dung; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="review-footer">
                        <span class="review-date">
                            <i class="fas fa-clock"></i> <?php echo $ngay; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="management_reviews.js"></script>
</body>
</html>
<?php
$conn->close();
?>