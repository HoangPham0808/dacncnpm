<?php
require_once __DIR__ . '/managment_function.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỗ trợ, liên hệ người dùng</title>
    <link rel="stylesheet" href="management_support.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-headset"></i> Hỗ trợ, liên hệ người dùng</h1>
            <div class="header-actions">
                <button class="btn btn-reviews" onclick="window.location.href='../management_reviews/management_reviews.php'">
                    <i class="fas fa-star"></i> Xem Đánh giá
                </button>
                <select class="filter-select" id="statusFilter" onchange="filterByStatus()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?php echo (isset($statusFilter) && $statusFilter === 'pending') ? 'selected' : ''; ?>>Chờ xử lý</option>
                    <option value="processing" <?php echo (isset($statusFilter) && $statusFilter === 'processing') ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="resolved" <?php echo (isset($statusFilter) && $statusFilter === 'resolved') ? 'selected' : ''; ?>>Đã giải quyết</option>
                    <option value="closed" <?php echo (isset($statusFilter) && $statusFilter === 'closed') ? 'selected' : ''; ?>>Đã đóng</option>
                </select>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="support-list">
            <?php if (empty($supportList)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có yêu cầu hỗ trợ nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($supportList as $support): 
                    $statusClass = getStatusClass($support['trang_thai']);
                    $statusText = getStatusText($support['trang_thai']);
                    $ten_khach_hang = $support['ten_khach_hang'] ?? 'Khách hàng';
                    $email = htmlspecialchars($support['email']);
                    $phone = htmlspecialchars($support['so_dien_thoai']);
                    $content = nl2br(htmlspecialchars($support['content']));
                    $phan_hoi = !empty($support['phan_hoi']) ? nl2br(htmlspecialchars($support['phan_hoi'])) : null;
                    $thoi_gian = formatDate($support['thoi_gian'] ?? $support['ngay_tao']);
                    $ho_tro_id = $support['ho_tro_id'];
                ?>
                <div class="support-item" data-id="<?php echo $ho_tro_id; ?>">
                    <div class="support-header">
                        <div class="support-info">
                            <h3><?php echo htmlspecialchars($ten_khach_hang); ?></h3>
                            <p class="support-email"><i class="fas fa-envelope"></i> <?php echo $email; ?></p>
                            <p class="support-phone"><i class="fas fa-phone"></i> <?php echo $phone; ?></p>
                            <p class="support-date"><i class="fas fa-clock"></i> <?php echo $thoi_gian; ?></p>
                        </div>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                    <div class="support-content">
                        <p><strong>Nội dung yêu cầu:</strong></p>
                        <div class="support-message"><?php echo $content; ?></div>
                        
                        <?php if ($phan_hoi): ?>
                            <div class="support-reply">
                                <p><strong><i class="fas fa-reply"></i> Phản hồi từ nhân viên:</strong></p>
                                <div class="reply-content"><?php echo $phan_hoi; ?></div>
                                <?php if ($support['ten_nhan_vien']): ?>
                                    <p class="reply-author"><i class="fas fa-user"></i> <?php echo htmlspecialchars($support['ten_nhan_vien']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="support-actions">
                        <button class="btn btn-primary" onclick="openReplyModal(<?php echo $ho_tro_id; ?>, '<?php echo htmlspecialchars($ten_khach_hang, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($support['content'], ENT_QUOTES); ?>')">
                            <i class="fas fa-reply"></i> Trả lời
                        </button>
                        <button class="btn btn-success" onclick="markResolved(<?php echo $ho_tro_id; ?>, '<?php echo htmlspecialchars($ten_khach_hang, ENT_QUOTES); ?>')">
                            <i class="fas fa-check"></i> Đánh dấu đã xử lý
                        </button>
                        <button class="btn btn-danger" onclick="closeSupport(<?php echo $ho_tro_id; ?>, '<?php echo htmlspecialchars($ten_khach_hang, ENT_QUOTES); ?>')">
                            <i class="fas fa-times"></i> Đóng
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal trả lời -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-reply"></i> Trả lời yêu cầu hỗ trợ</h2>
                <button class="close-btn" onclick="closeReplyModal()">&times;</button>
            </div>
            <form id="replyForm" method="POST" action="">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="ho_tro_id" id="reply_ho_tro_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Khách hàng:</label>
                        <p id="reply_customer_name" class="form-info"></p>
                    </div>
                    <div class="form-group">
                        <label>Nội dung yêu cầu:</label>
                        <div id="reply_request_content" class="form-info"></div>
                    </div>
                    <div class="form-group">
                        <label for="reply_phan_hoi"><i class="fas fa-comment"></i> Phản hồi *</label>
                        <textarea id="reply_phan_hoi" name="phan_hoi" rows="6" required placeholder="Nhập phản hồi của bạn..."></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeReplyModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Gửi phản hồi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="management_support.js"></script>
</body>
</html>
<?php
$conn->close();
?>

