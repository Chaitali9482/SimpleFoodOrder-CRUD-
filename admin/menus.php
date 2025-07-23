<?php
require_once('../config/db_connect.php');
require_once('../utils/validation.php');
include_once('../includes/admin_header.php');

// Initialize variables
$errors = [];
$success_message = '';
$menu_item = [
    'id' => '',
    'restaurant_id' => '',
    'item_name' => '',
    'price' => '',
    'description' => ''
];

// Get restaurant filter from URL
$filter_restaurant_id = isset($_GET['restaurant_id']) && is_numeric($_GET['restaurant_id']) ? $_GET['restaurant_id'] : '';

// Handle form submissions

// ADD or UPDATE menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_menu'])) {
    // Sanitize and validate input
    $menu_item['id'] = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : '';
    $menu_item['restaurant_id'] = sanitizeInput($_POST['restaurant_id'] ?? '');
    $menu_item['item_name'] = sanitizeInput($_POST['item_name'] ?? '');
    $menu_item['price'] = sanitizeInput($_POST['price'] ?? '');
    $menu_item['description'] = sanitizeInput($_POST['description'] ?? '');
    
    // Validate required fields
    $required_fields = [
        'restaurant_id' => $menu_item['restaurant_id'],
        'item_name' => $menu_item['item_name'],
        'price' => $menu_item['price']
    ];
    
    $errors = validateRequired($required_fields);
    
    // Validate price format
    if (!empty($menu_item['price']) && !validatePrice($menu_item['price'])) {
        $errors['price'] = "Please enter a valid price (e.g. 9.99)";
    }
    
    // If no errors, process the submission
    if (empty($errors)) {
        try {
            if (!empty($menu_item['id'])) {
                // Update existing menu item
                $stmt = $pdo->prepare("UPDATE menus SET restaurant_id = ?, item_name = ?, price = ?, description = ? WHERE id = ?");
                $stmt->execute([
                    $menu_item['restaurant_id'],
                    $menu_item['item_name'],
                    $menu_item['price'],
                    $menu_item['description'],
                    $menu_item['id']
                ]);
                $success_message = "Menu item updated successfully!";
            } else {
                // Add new menu item
                $stmt = $pdo->prepare("INSERT INTO menus (restaurant_id, item_name, price, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $menu_item['restaurant_id'],
                    $menu_item['item_name'],
                    $menu_item['price'],
                    $menu_item['description']
                ]);
                $success_message = "Menu item added successfully!";
            }
            
            // Keep restaurant_id for filter but reset other fields
            $filter_restaurant_id = $menu_item['restaurant_id'];
            $menu_item = [
                'id' => '',
                'restaurant_id' => $filter_restaurant_id,
                'item_name' => '',
                'price' => '',
                'description' => ''
            ];
            
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}

// DELETE menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_menu'])) {
    $menu_id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : '';
    
    if (!empty($menu_id)) {
        try {
            // Check if menu item is used in any orders
            $check_orders = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE menu_id = ?");
            $check_orders->execute([$menu_id]);
            $has_orders = $check_orders->fetchColumn() > 0;
            
            if ($has_orders) {
                $errors['delete'] = "Cannot delete menu item with existing orders";
            } else {
                // Delete menu item
                $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
                $stmt->execute([$menu_id]);
                $success_message = "Menu item deleted successfully!";
            }
            
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}

// EDIT menu item (load data for editing)
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_menu = $stmt->fetch();
        
        if ($edit_menu) {
            $menu_item = $edit_menu;
            $filter_restaurant_id = $edit_menu['restaurant_id'];
        }
        
    } catch (PDOException $e) {
        $errors['database'] = "Database error: " . $e->getMessage();
    }
}

// Fetch all restaurants for the dropdown
try {
    $stmt = $pdo->query("SELECT * FROM restaurants ORDER BY name");
    $restaurants = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors['database'] = "Database error: " . $e->getMessage();
    $restaurants = [];
}

// Fetch menu items for display (with optional restaurant filter)
try {
    if (!empty($filter_restaurant_id)) {
        $stmt = $pdo->prepare("
            SELECT m.*, r.name as restaurant_name,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.menu_id = m.id) as order_count
            FROM menus m
            JOIN restaurants r ON m.restaurant_id = r.id
            WHERE m.restaurant_id = ?
            ORDER BY m.item_name
        ");
        $stmt->execute([$filter_restaurant_id]);
    } else {
        $stmt = $pdo->query("
            SELECT m.*, r.name as restaurant_name,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.menu_id = m.id) as order_count
            FROM menus m
            JOIN restaurants r ON m.restaurant_id = r.id
            ORDER BY r.name, m.item_name
        ");
    }
    $menu_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors['database'] = "Database error: " . $e->getMessage();
    $menu_items = [];
}
?>

<!-- Menus Management Page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Manage Menu Items</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($errors['database'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $errors['database'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($errors['delete'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $errors['delete'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Restaurant Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row align-items-end">
            <div class="col-md-6">
                <label for="restaurant_filter" class="form-label">Filter by Restaurant</label>
                <select class="form-select" id="restaurant_filter" name="restaurant_id">
                    <option value="">All Restaurants</option>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <option value="<?= $restaurant['id'] ?>" <?= $filter_restaurant_id == $restaurant['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($restaurant['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <div class="d-flex">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="menus.php" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <!-- Menu Item Form -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?= !empty($menu_item['id']) ? 'Edit' : 'Add' ?> Menu Item</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if (!empty($menu_item['id'])): ?>
                        <input type="hidden" name="id" value="<?= $menu_item['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="restaurant_id" class="form-label">Restaurant</label>
                        <select class="form-select <?= isset($errors['restaurant_id']) ? 'is-invalid' : '' ?>" id="restaurant_id" name="restaurant_id" required>
                            <option value="">Select Restaurant</option>
                            <?php foreach ($restaurants as $restaurant): ?>
                                <option value="<?= $restaurant['id'] ?>" <?= $menu_item['restaurant_id'] == $restaurant['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($restaurant['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['restaurant_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['restaurant_id'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control <?= isset($errors['item_name']) ? 'is-invalid' : '' ?>" id="item_name" name="item_name" value="<?= htmlspecialchars($menu_item['item_name']) ?>" required>
                        <?php if (isset($errors['item_name'])): ?>
                            <div class="invalid-feedback"><?= $errors['item_name'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" id="price" name="price" value="<?= htmlspecialchars($menu_item['price']) ?>" required>
                            <?php if (isset($errors['price'])): ?>
                                <div class="invalid-feedback"><?= $errors['price'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($menu_item['description']) ?></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="save_menu" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= !empty($menu_item['id']) ? 'Update' : 'Add' ?> Menu Item
                        </button>
                    </div>
                    
                    <?php if (!empty($menu_item['id'])): ?>
                        <div class="d-grid mt-2">
                            <a href="menus.php<?= !empty($filter_restaurant_id) ? '?restaurant_id=' . $filter_restaurant_id : '' ?>" class="btn btn-secondary">
                                <i class="fas fa-plus"></i> Add New Menu Item
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Menu Items List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Menu Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($menu_items)): ?>
                    <div class="alert alert-info">No menu items found. Add a menu item using the form.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Restaurant</th>
                                    <th>Item Name</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td><?= htmlspecialchars($item['restaurant_name']) ?></td>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td><?= htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : '') ?></td>
                                        <td>
                                            <a href="menus.php?edit=<?= $item['id'] ?><?= !empty($filter_restaurant_id) ? '&restaurant_id=' . $filter_restaurant_id : '' ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($item['order_count'] == 0): ?>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $item['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?= $item['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $item['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel<?= $item['id'] ?>">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete the menu item "<?= htmlspecialchars($item['item_name']) ?>"?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form method="post">
                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                        <button type="submit" name="delete_menu" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
