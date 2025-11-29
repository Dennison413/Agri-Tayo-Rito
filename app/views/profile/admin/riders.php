<?php
// app/views/profile/admin/riders.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Orders.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$ridersModel = new DeliveryRiders();

// Handle CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_rider') {
        $data = [
            'rider_name' => trim($_POST['rider_name']),
            'contact_number' => trim($_POST['contact_number']),
            'vehicle_type' => trim($_POST['vehicle_type']),
            'vehicle_plate' => !empty($_POST['vehicle_plate']) ? trim($_POST['vehicle_plate']) : null
        ];
        
        $result = $ridersModel->addRider($data);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } 
    elseif ($_POST['action'] === 'update_rider') {
        $riderID = intval($_POST['rider_id']);
        $data = [
            'rider_name' => trim($_POST['rider_name']),
            'contact_number' => trim($_POST['contact_number']),
            'vehicle_type' => trim($_POST['vehicle_type']),
            'vehicle_plate' => !empty($_POST['vehicle_plate']) ? trim($_POST['vehicle_plate']) : null
        ];
        
        $result = $ridersModel->updateRider($riderID, $data);
        
        if ($result) {
            $_SESSION['success'] = 'Rider updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update rider';
        }
    }
    elseif ($_POST['action'] === 'toggle_status') {
        $riderID = intval($_POST['rider_id']);
        $currentStatus = intval($_POST['current_status']);
        
        if ($currentStatus === 1) {
            $result = $ridersModel->deactivateRider($riderID);
            $message = 'Rider deactivated successfully';
        } else {
            $result = $ridersModel->updateRider($riderID, ['is_active' => 1]);
            $message = 'Rider activated successfully';
        }
        
        if ($result) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = 'Failed to update rider status';
        }
    }
    
    header('Location: ' . BASE_URL . 'profile/admin/riders');
    exit;
}

// Get all riders
$db = new Database();
$conn = $db->connect();

$query = "SELECT * FROM delivery_riders ORDER BY is_active DESC, rider_name ASC";
$stmt = $conn->query($query);
$allRiders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$activeRiders = count(array_filter($allRiders, fn($r) => $r['is_active'] == 1));
$totalDeliveries = array_sum(array_column($allRiders, 'total_deliveries'));

// Get riders with current assignments
$assignedQuery = "SELECT dr.riderID, COUNT(o.orderID) as active_deliveries
                  FROM delivery_riders dr
                  LEFT JOIN orders o ON dr.riderID = o.assigned_rider_id 
                    AND o.lgu_delivery_status IN ('picked_up', 'in_transit')
                  GROUP BY dr.riderID";
$assignedStmt = $conn->query($assignedQuery);
$assignments = [];
while ($row = $assignedStmt->fetch(PDO::FETCH_ASSOC)) {
    $assignments[$row['riderID']] = $row['active_deliveries'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Riders - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <?php 
    // Include unified sidebar component
    require_once __DIR__ . '/admin-nav.php'; 
    ?>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">🏍️ LGU Delivery Riders</h1>
            <p class="page-subtitle">Manage delivery personnel for LGU delivery service</p>
        </div>

        <!-- Stats -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">👥</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo count($allRiders); ?></h3>
                    <p class="stat-label">Total Riders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo $activeRiders; ?></h3>
                    <p class="stat-label">Active Riders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($totalDeliveries); ?></h3>
                    <p class="stat-label">Total Deliveries</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">🚚</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo array_sum($assignments); ?></h3>
                    <p class="stat-label">Active Deliveries</p>
                </div>
            </div>
        </section>

        <!-- Add Rider Button -->
        <section class="action-section">
            <button onclick="showAddModal()" class="btn-add-rider">
                ➕ Add New Rider
            </button>
        </section>

        <!-- Riders Grid -->
        <section class="riders-section">
            <div class="section-header">
                <h2 class="section-title">All Riders</h2>
            </div>

            <div class="riders-grid">
                <?php foreach ($allRiders as $rider): ?>
                <div class="rider-card <?php echo $rider['is_active'] ? '' : 'inactive'; ?>">
                    <div class="rider-header">
                        <div class="rider-avatar">🏍️</div>
                        <div class="rider-status">
                            <?php if ($rider['is_active']): ?>
                                <span class="status-badge active">✓ Active</span>
                            <?php else: ?>
                                <span class="status-badge inactive">⊘ Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rider-info">
                        <h3><?php echo htmlspecialchars($rider['rider_name']); ?></h3>
                        <div class="info-row">
                            <span class="label">📞 Contact:</span>
                            <span class="value"><?php echo htmlspecialchars($rider['contact_number']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">🚗 Vehicle:</span>
                            <span class="value"><?php echo htmlspecialchars($rider['vehicle_type']); ?></span>
                        </div>
                        <?php if ($rider['vehicle_plate']): ?>
                        <div class="info-row">
                            <span class="label">🔢 Plate:</span>
                            <span class="value"><?php echo htmlspecialchars($rider['vehicle_plate']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="rider-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $rider['total_deliveries']; ?></span>
                            <span class="stat-label">Total Deliveries</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $assignments[$rider['riderID']] ?? 0; ?></span>
                            <span class="stat-label">Active Now</span>
                        </div>
                    </div>

                    <div class="rider-actions">
                        <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($rider)); ?>)" 
                                class="btn-edit">✏️ Edit</button>
                        <button onclick="toggleRiderStatus(<?php echo $rider['riderID']; ?>, <?php echo $rider['is_active']; ?>, '<?php echo htmlspecialchars($rider['rider_name']); ?>')" 
                                class="btn-toggle">
                            <?php echo $rider['is_active'] ? '⊘ Deactivate' : '✓ Activate'; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Riders Table -->
        <section class="table-section">
            <div class="section-header">
                <h2 class="section-title">Riders List</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Vehicle</th>
                            <th>Plate</th>
                            <th>Total Deliveries</th>
                            <th>Active Deliveries</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRiders as $rider): ?>
                        <tr>
                            <td>#<?php echo $rider['riderID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($rider['rider_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($rider['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($rider['vehicle_type']); ?></td>
                            <td><?php echo $rider['vehicle_plate'] ? htmlspecialchars($rider['vehicle_plate']) : '-'; ?></td>
                            <td><?php echo $rider['total_deliveries']; ?></td>
                            <td><?php echo $assignments[$rider['riderID']] ?? 0; ?></td>
                            <td>
                                <?php if ($rider['is_active']): ?>
                                    <span class="status-badge active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($rider)); ?>)" 
                                            class="btn-action btn-edit-sm">Edit</button>
                                    <button onclick="toggleRiderStatus(<?php echo $rider['riderID']; ?>, <?php echo $rider['is_active']; ?>, '<?php echo htmlspecialchars($rider['rider_name']); ?>')" 
                                            class="btn-action <?php echo $rider['is_active'] ? 'btn-deactivate-sm' : 'btn-activate-sm'; ?>">
                                        <?php echo $rider['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Add Rider Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3>➕ Add New Rider</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label>Rider Name *</label>
                    <input type="text" name="rider_name" required placeholder="e.g., Juan Dela Cruz">
                </div>

                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="text" name="contact_number" required placeholder="e.g., 09171234567">
                </div>

                <div class="form-group">
                    <label>Vehicle Type *</label>
                    <select name="vehicle_type" required>
                        <option value="">Select vehicle type</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Tricycle">Tricycle</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vehicle Plate Number (Optional)</label>
                    <input type="text" name="vehicle_plate" placeholder="e.g., ABC 1234">
                </div>

                <input type="hidden" name="action" value="add_rider">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Add Rider</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Rider Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>✏️ Edit Rider</h3>
            
            <form method="POST">
                <input type="hidden" name="rider_id" id="edit_rider_id">
                
                <div class="form-group">
                    <label>Rider Name *</label>
                    <input type="text" name="rider_name" id="edit_rider_name" required>
                </div>

                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="text" name="contact_number" id="edit_contact_number" required>
                </div>

                <div class="form-group">
                    <label>Vehicle Type *</label>
                    <select name="vehicle_type" id="edit_vehicle_type" required>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Tricycle">Tricycle</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vehicle Plate Number (Optional)</label>
                    <input type="text" name="vehicle_plate" id="edit_vehicle_plate">
                </div>

                <input type="hidden" name="action" value="update_rider">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Update Rider</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toggle Status Form (hidden) -->
    <form id="toggleStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="rider_id" id="toggle_rider_id">
        <input type="hidden" name="current_status" id="toggle_current_status">
        <input type="hidden" name="action" value="toggle_status">
    </form>

    <style>
        .action-section {
            margin-bottom: 30px;
        }

        .btn-add-rider {
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }

        .btn-add-rider:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .riders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin: 20px 0 40px 0;
        }

        .rider-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .rider-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .rider-card.inactive {
            opacity: 0.7;
            background: #f9fafb;
        }

        .rider-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .rider-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .rider-info h3 {
            margin: 0 0 12px 0;
            font-size: 1.2rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.9rem;
        }

        .info-row .label {
            color: #666;
            font-weight: 500;
        }

        .info-row .value {
            color: #333;
            font-weight: 600;
        }

        .rider-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 2px solid #f0f0f0;
            border-bottom: 2px solid #f0f0f0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-top: 4px;
        }

        .rider-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-edit {
            flex: 1;
            padding: 10px;
            background: #dbeafe;
            color: #1e40af;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-edit:hover {
            background: #bfdbfe;
        }

        .btn-toggle {
            flex: 1;
            padding: 10px;
            background: #fef3c7;
            color: #92400e;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-toggle:hover {
            background: #fde68a;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-edit-sm {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-edit-sm:hover {
            background: #bfdbfe;
        }

        .btn-deactivate-sm {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-deactivate-sm:hover {
            background: #fecaca;
        }

        .btn-activate-sm {
            background: #dcfce7;
            color: #166534;
        }

        .btn-activate-sm:hover {
            background: #bbf7d0;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }

        .modal-content h3 {
            margin: 0 0 20px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #e5e7eb;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-confirm {
            flex: 1;
            padding: 12px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .riders-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function showEditModal(riderData) {
            document.getElementById('edit_rider_id').value = riderData.riderID;
            document.getElementById('edit_rider_name').value = riderData.rider_name;
            document.getElementById('edit_contact_number').value = riderData.contact_number;
            document.getElementById('edit_vehicle_type').value = riderData.vehicle_type;
            document.getElementById('edit_vehicle_plate').value = riderData.vehicle_plate || '';
            document.getElementById('editModal').classList.add('active');
        }

        function toggleRiderStatus(riderId, currentStatus, riderName) {
            const action = currentStatus === 1 ? 'deactivate' : 'activate';
            const confirmed = confirm(`Are you sure you want to ${action} ${riderName}?`);
            
            if (confirmed) {
                document.getElementById('toggle_rider_id').value = riderId;
                document.getElementById('toggle_current_status').value = currentStatus;
                document.getElementById('toggleStatusForm').submit();
            }
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }

        // Close modal on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>