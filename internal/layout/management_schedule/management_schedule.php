<?php
require_once __DIR__ . '/management_function.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lịch tập</title>
    <link rel="stylesheet" href="management_schedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Quản lý lịch tập</h1>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Thêm lớp tập
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="schedule-container">
            <div class="schedule-header">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <button class="btn-nav" onclick="changeWeek(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h2>Tuần <?php echo $weekDisplay; ?></h2>
                    <button class="btn-nav" onclick="changeWeek(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                
                <?php if ($vai_tro === 'Admin' && !empty($phongTapList)): ?>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <label style="color: rgba(255, 255, 255, 0.9); font-weight: 600;">
                        <i class="fas fa-building"></i> Phòng tập:
                    </label>
                    <select id="phongTapSelector" onchange="changePhongTap()" style="padding: 8px 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #ffffff; cursor: pointer;">
                        <?php foreach ($phongTapList as $phong): ?>
                        <option value="<?php echo $phong['phong_tap_id']; ?>" <?php echo $phong['phong_tap_id'] == $selected_phong_tap_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($phong['ten_phong_tap']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php elseif ($vai_tro === 'PT'): ?>
                <div style="color: rgba(255, 255, 255, 0.8);">
                    <i class="fas fa-building"></i> 
                    <?php 
                    $phongName = '';
                    foreach ($phongTapList as $phong) {
                        if ($phong['phong_tap_id'] == $selected_phong_tap_id) {
                            $phongName = $phong['ten_phong_tap'];
                            break;
                        }
                    }
                    echo htmlspecialchars($phongName);
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="schedule-table-wrapper">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Thứ Hai</th>
                            <th>Thứ Ba</th>
                            <th>Thứ Tư</th>
                            <th>Thứ Năm</th>
                            <th>Thứ Sáu</th>
                            <th>Thứ Bảy</th>
                            <th>Chủ Nhật</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($sortedTimeSlots)):
                        ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.5);">
                                <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                <p style="font-size: 16px;">Không có lịch tập nào trong tuần này</p>
                            </td>
                        </tr>
                        <?php
                        else:
                            foreach ($sortedTimeSlots as $timeSlot):
                        ?>
                        <tr>
                            <td class="time-slot"><?php echo htmlspecialchars($timeSlot); ?></td>
                            <?php for ($day = 1; $day <= 7; $day++): ?>
                                <?php if (isset($scheduleData[$timeSlot][$day])): 
                                    $class = $scheduleData[$timeSlot][$day];
                                    $lich_tap_id = $class['lich_tap_id'];
                                    $ten_lop = htmlspecialchars($class['ten_lop']);
                                    $ten_hv = htmlspecialchars($class['ten_huan_luyen_vien'] ?? 'Chưa có');
                                    $so_luong_da_dk = intval($class['so_luong_da_dang_ky'] ?? 0);
                                    $so_luong_toi_da = intval($class['so_luong_toi_da']);
                                    $trang_thai = $class['trang_thai'];
                                    $trangThaiClass = $trang_thai === 'Đã đầy' ? 'full' : ($trang_thai === 'Hủy' ? 'cancelled' : '');
                                ?>
                                <td>
                                    <div class="class-item <?php echo $trangThaiClass; ?>" data-id="<?php echo $lich_tap_id; ?>">
                                        <span class="class-name"><?php echo $ten_lop; ?></span>
                                        <span class="class-instructor"><?php echo $ten_hv; ?></span>
                                        <span class="class-count"><?php echo $so_luong_da_dk; ?>/<?php echo $so_luong_toi_da; ?></span>
                                        <div class="class-actions">
                                            <button class="btn-icon" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($class, JSON_UNESCAPED_UNICODE)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon btn-danger" onclick="confirmDelete(<?php echo $lich_tap_id; ?>, '<?php echo htmlspecialchars($ten_lop, ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <?php else: ?>
                                <td class="empty-cell">-</td>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; 
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal thêm/sửa lớp tập -->
    <div id="classModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Thêm lớp tập</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="classForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="lich_tap_id" id="lich_tap_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="ten_lop"><i class="fas fa-dumbbell"></i> Tên lớp *</label>
                        <input type="text" id="ten_lop" name="ten_lop" required maxlength="100" placeholder="VD: Yoga Chào buổi sáng">
                    </div>

                    <div class="form-group">
                        <label for="mo_ta"><i class="fas fa-align-left"></i> Mô tả</label>
                        <textarea id="mo_ta" name="mo_ta" rows="3" maxlength="500" placeholder="Mô tả về lớp tập"></textarea>
                    </div>

                    <?php if ($vai_tro === 'Admin'): ?>
                    <div class="form-group">
                        <label for="phong_tap_id"><i class="fas fa-building"></i> Phòng tập *</label>
                        <select id="phong_tap_id" name="phong_tap_id" required onchange="loadTrainersByRoom()">
                            <option value="">Chọn phòng tập</option>
                            <?php foreach ($phongTapList as $phong): ?>
                            <option value="<?php echo $phong['phong_tap_id']; ?>" <?php echo $phong['phong_tap_id'] == $selected_phong_tap_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($phong['ten_phong_tap']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" id="phong_tap_id" name="phong_tap_id" value="<?php echo $selected_phong_tap_id; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nhan_vien_pt_id"><i class="fas fa-user-tie"></i> Huấn luyện viên *</label>
                            <select id="nhan_vien_pt_id" name="nhan_vien_pt_id" required>
                                <option value="">Chọn HLV</option>
                                <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo $trainer['nhan_vien_id']; ?>" <?php echo ($vai_tro === 'PT' && $trainer['nhan_vien_id'] == $nhan_vien_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($trainer['ho_ten']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="phong"><i class="fas fa-door-open"></i> Phòng</label>
                            <input type="text" id="phong" name="phong" maxlength="50" placeholder="VD: Phòng A1">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ngay_tap"><i class="fas fa-calendar"></i> Ngày tập *</label>
                            <input type="date" id="ngay_tap" name="ngay_tap" required>
                        </div>

                        <div class="form-group">
                            <label for="so_luong_toi_da"><i class="fas fa-users"></i> Số lượng tối đa *</label>
                            <input type="number" id="so_luong_toi_da" name="so_luong_toi_da" min="1" max="100" value="20" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gio_bat_dau"><i class="fas fa-clock"></i> Giờ bắt đầu *</label>
                            <input type="time" id="gio_bat_dau" name="gio_bat_dau" required>
                        </div>

                        <div class="form-group">
                            <label for="gio_ket_thuc"><i class="fas fa-clock"></i> Giờ kết thúc *</label>
                            <input type="time" id="gio_ket_thuc" name="gio_ket_thuc" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="trang_thai"><i class="fas fa-toggle-on"></i> Trạng thái *</label>
                        <select id="trang_thai" name="trang_thai" required>
                            <option value="Đang mở">Đang mở</option>
                            <option value="Đã đầy">Đã đầy</option>
                            <option value="Hủy">Hủy</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal xác nhận xóa -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Xác nhận xóa</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa lớp tập <strong id="deleteClassName"></strong>?</p>
                <p class="warning-text"><i class="fas fa-info-circle"></i> Nếu lớp đã có người đăng ký, trạng thái sẽ được đổi thành 'Hủy' thay vì xóa.</p>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="lich_tap_id" id="deleteClassId">
                <?php if (isset($_GET['week'])): ?>
                <input type="hidden" name="week" value="<?php echo intval($_GET['week']); ?>">
                <?php endif; ?>
                <?php if (isset($_GET['phong_tap_id'])): ?>
                <input type="hidden" name="phong_tap_id" value="<?php echo intval($_GET['phong_tap_id']); ?>">
                <?php endif; ?>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentWeek = <?php echo $weekOffset; ?>;
        let currentPhongTapId = <?php echo $selected_phong_tap_id ?? 'null'; ?>;
        const vaiTro = '<?php echo $vai_tro; ?>';
        const nhanVienId = <?php echo $nhan_vien_id; ?>;
        
        // Dữ liệu trainers theo phòng
        const trainersByRoom = <?php 
            $trainersByRoomData = [];
            foreach ($phongTapList as $phong) {
                $phong_id = $phong['phong_tap_id'];
                $trainersStmt = $conn->prepare("SELECT nhan_vien_id, ho_ten FROM nhanvien WHERE vai_tro = 'PT' AND trang_thai = 'Đang làm' AND phong_tap_id = ? ORDER BY ho_ten");
                $trainersStmt->bind_param("i", $phong_id);
                $trainersStmt->execute();
                $result = $trainersStmt->get_result();
                $trainersByRoomData[$phong_id] = [];
                while ($row = $result->fetch_assoc()) {
                    $trainersByRoomData[$phong_id][] = $row;
                }
                $trainersStmt->close();
            }
            echo json_encode($trainersByRoomData);
        ?>;
        
        function changeWeek(offset) {
            currentWeek += offset;
            const url = new URL(window.location);
            url.searchParams.set('week', currentWeek);
            if (currentPhongTapId) {
                url.searchParams.set('phong_tap_id', currentPhongTapId);
            }
            window.location.href = url.toString();
        }
        
        function changePhongTap() {
            const selector = document.getElementById('phongTapSelector');
            const phongTapId = selector.value;
            const url = new URL(window.location);
            url.searchParams.set('phong_tap_id', phongTapId);
            if (currentWeek !== 0) {
                url.searchParams.set('week', currentWeek);
            }
            window.location.href = url.toString();
        }
        
        function loadTrainersByRoom() {
            const phongTapId = document.getElementById('phong_tap_id').value;
            const trainerSelect = document.getElementById('nhan_vien_pt_id');
            
            trainerSelect.innerHTML = '<option value="">Chọn HLV</option>';
            
            if (phongTapId && trainersByRoom[phongTapId]) {
                trainersByRoom[phongTapId].forEach(trainer => {
                    const option = document.createElement('option');
                    option.value = trainer.nhan_vien_id;
                    option.textContent = trainer.ho_ten;
                    trainerSelect.appendChild(option);
                });
            }
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Thêm lớp tập';
            document.getElementById('formAction').value = 'add';
            document.getElementById('lich_tap_id').value = '';
            document.getElementById('classForm').reset();
            document.getElementById('trang_thai').value = 'Đang mở';
            document.getElementById('so_luong_toi_da').value = '20';
            
            // Set giá trị mặc định
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('ngay_tap').value = today;
            
            if (vaiTro === 'PT') {
                document.getElementById('nhan_vien_pt_id').value = nhanVienId;
            } else if (currentPhongTapId) {
                document.getElementById('phong_tap_id').value = currentPhongTapId;
                loadTrainersByRoom();
            }
            
            const modal = document.getElementById('classModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function openEditModal(classData) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Sửa lớp tập';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('lich_tap_id').value = classData.lich_tap_id;
            document.getElementById('ten_lop').value = classData.ten_lop || '';
            document.getElementById('mo_ta').value = classData.mo_ta || '';
            document.getElementById('phong').value = classData.phong || '';
            document.getElementById('ngay_tap').value = classData.ngay_tap;
            document.getElementById('gio_bat_dau').value = classData.gio_bat_dau.substring(0, 5);
            document.getElementById('gio_ket_thuc').value = classData.gio_ket_thuc.substring(0, 5);
            document.getElementById('so_luong_toi_da').value = classData.so_luong_toi_da || 20;
            document.getElementById('trang_thai').value = classData.trang_thai || 'Đang mở';
            
            if (vaiTro === 'Admin') {
                document.getElementById('phong_tap_id').value = classData.phong_tap_id;
                loadTrainersByRoom();
            }
            
            setTimeout(() => {
                document.getElementById('nhan_vien_pt_id').value = classData.nhan_vien_pt_id || '';
            }, 100);
            
            const modal = document.getElementById('classModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeModal() {
            const modal = document.getElementById('classModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('classForm').reset();
            }, 300);
        }

        function confirmDelete(id, className) {
            document.getElementById('deleteClassId').value = id;
            document.getElementById('deleteClassName').textContent = className;
            
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        window.onclick = function(event) {
            const classModal = document.getElementById('classModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === classModal) closeModal();
            if (event.target === deleteModal) closeDeleteModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeDeleteModal();
            }
        });

        window.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            }
        });
    </script>
    <script src="management_schedule.js"></script>
</body>
</html>
<?php
$conn->close();
?>