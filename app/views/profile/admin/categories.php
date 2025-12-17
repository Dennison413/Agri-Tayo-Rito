<?php
// app/views/profile/admin/categories.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Category.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$categoryModel = new Category();

// Handle CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_category') {
        $categoryName = trim($_POST['category_name']);
        $result = $categoryModel->createCategory($categoryName);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } 
    elseif ($_POST['action'] === 'update_category') {
        $categoryID = intval($_POST['category_id']);
        $newName = trim($_POST['category_name']);
        $result = $categoryModel->updateCategory($categoryID, $newName);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    elseif ($_POST['action'] === 'delete_category') {
        $categoryID = intval($_POST['category_id']);
        $result = $categoryModel->deleteCategory($categoryID);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    header('Location: ' . BASE_URL . 'profile/admin/category');
    exit;
}

// Get all categories with product counts
$categories = $categoryModel->getAllCategories();
$categoriesWithCounts = [];
foreach ($categories as $cat) {
    $cat['product_count'] = $categoryModel->getProductCountByCategory($cat['categoryID']);
    $categoriesWithCounts[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
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
    
    <nav class="top-navbar">
        <div class="logo-wrapper">
            <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Logo">
            <span class="logo">Admin Panel</span>
        </div>
        <div class="navbar-actions">
            <button class="nav-btn" onclick="toggleSidebar()">
                <span class="menu-icon">☰</span>
            </button>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🛡️</div>
            <div class="sidebar-title">
                <h2>Admin Dashboard</h2>
                <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?></p>
            </div>
            <div class="sidebar-actions">
                <button class="sidebar-action-btn" onclick="toggleSidebar()">✕</button>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-section">
                <p class="nav-section-title">Main Menu</p>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/dashboard'">
                    <span class="nav-icon-menu">📊</span>
                    <span>Dashboard</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/applications'">
                    <span class="nav-icon-menu">📋</span>
                    <span>Applications</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/withdrawals'">
                    <span class="nav-icon-menu">💰</span>
                    <span>Withdrawals</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/cards'">
                    <span class="nav-icon-menu">💳</span>
                    <span>ATM Cards</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/deliveries'">
                    <span class="nav-icon-menu">🚚</span>
                    <span>Deliveries</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/riders'">
                    <span class="nav-icon-menu">🏍️</span>
                    <span>Riders</span>
                </button>
                <button class="nav-item active" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/category'">
                    <span class="nav-icon-menu">🏷️</span>
                    <span>Categories</span>
                </button>
            </div>
        </nav>

        <div class="sidebar-footer">
            <button class="logout-btn" onclick="location.href='<?php echo BASE_URL; ?>auth/logout'">
                <span>🚪</span>
                <span>Logout</span>
            </button>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">🏷️ Product Categories</h1>
            <p class="page-subtitle">Manage product categories for the marketplace</p>
        </div>

        <!-- Add Category Button -->
        <section class="action-section">
            <button onclick="showAddModal()" class="btn-add-category">
                ➕ Add New Category
            </button>
        </section>

        <!-- Categories Grid -->
        <section class="categories-section">
            <div class="categories-grid">
                <?php foreach ($categoriesWithCounts as $category): ?>
                <div class="category-card">
                    <div class="category-icon">🏷️</div>
                    <div class="category-info">
                        <h3><?php echo htmlspecialchars($category['category']); ?></h3>
                        <p class="product-count"><?php echo $category['product_count']; ?> products</p>
                    </div>
                    <div class="category-actions">
                        <button onclick="showEditModal(<?php echo $category['categoryID']; ?>, '<?php echo htmlspecialchars($category['category']); ?>')" 
                                class="btn-edit" title="Edit">
                            ✏️
                        </button>
                        <button onclick="showDeleteModal(<?php echo $category['categoryID']; ?>, '<?php echo htmlspecialchars($category['category']); ?>', <?php echo $category['product_count']; ?>)" 
                                class="btn-delete" title="Delete">
                            🗑️
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="table-section">
            <div class="section-header">
                <h2 class="section-title">All Categories</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoriesWithCounts as $category): ?>
                        <tr>
                            <td>#<?php echo $category['categoryID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($category['category']); ?></strong></td>
                            <td><?php echo $category['product_count']; ?> products</td>
                            <td>
                                <div class="table-actions">
                                    <button onclick="showEditModal(<?php echo $category['categoryID']; ?>, '<?php echo htmlspecialchars($category['category']); ?>')" 
                                            class="btn-action btn-edit-sm">Edit</button>
                                    <button onclick="showDeleteModal(<?php echo $category['categoryID']; ?>, '<?php echo htmlspecialchars($category['category']); ?>', <?php echo $category['product_count']; ?>)" 
                                            class="btn-action btn-delete-sm">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Add Category Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3>➕ Add New Category</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="category_name" required 
                           placeholder="e.g., Vegetables, Fruits, Seeds">
                </div>

                <input type="hidden" name="action" value="add_category">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>✏️ Edit Category</h3>
            
            <form method="POST">
                <input type="hidden" name="category_id" id="edit_category_id">
                
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="category_name" id="edit_category_name" required>
                </div>

                <input type="hidden" name="action" value="update_category">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>🗑️ Delete Category</h3>
            <p>Are you sure you want to delete <strong id="delete_category_name"></strong>?</p>
            <p id="delete_warning" class="warning-text" style="display: none;">
                ⚠️ This category has products. You cannot delete it until all products are removed or moved to another category.
            </p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="category_id" id="delete_category_id">
                <input type="hidden" name="action" value="delete_category">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" id="delete_confirm_btn" class="btn-confirm-delete">Delete Category</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .action-section {
            margin-bottom: 30px;
        }

        .btn-add-category {
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

        .btn-add-category:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s;
            position: relative;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .category-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .category-info h3 {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
            color: #333;
        }

        .product-count {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }

        .category-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #dbeafe;
        }

        .btn-edit:hover {
            background: #bfdbfe;
        }

        .btn-delete {
            background: #fee2e2;
        }

        .btn-delete:hover {
            background: #fecaca;
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

        .btn-delete-sm {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete-sm:hover {
            background: #fecaca;
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

        .form-group input {
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

        .btn-confirm-delete {
            flex: 1;
            padding: 12px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-confirm-delete:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .warning-text {
            padding: 12px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            color: #92400e;
            margin: 15px 0;
        }

        @media (max-width: 768px) {
            .categories-grid {
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

        function showEditModal(categoryId, categoryName) {
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_category_name').value = categoryName;
            document.getElementById('editModal').classList.add('active');
        }

        function showDeleteModal(categoryId, categoryName, productCount) {
            document.getElementById('delete_category_id').value = categoryId;
            document.getElementById('delete_category_name').textContent = categoryName;
            
            const warning = document.getElementById('delete_warning');
            const confirmBtn = document.getElementById('delete_confirm_btn');
            const deleteForm = document.getElementById('deleteForm');
            
            if (productCount > 0) {
                warning.style.display = 'block';
                confirmBtn.disabled = true;
                deleteForm.onsubmit = (e) => e.preventDefault();
            } else {
                warning.style.display = 'none';
                confirmBtn.disabled = false;
                deleteForm.onsubmit = null;
            }
            
            document.getElementById('deleteModal').classList.add('active');
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