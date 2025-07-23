<?php
require_once('../config/db_connect.php');
require_once('../utils/validation.php');
include_once('../includes/admin_header.php');

// Initialize variables
$errors = [];
$success_message = '';
$restaurant = [
    'id' => '',
    'name' => '',
    'location' => ''
];

// Handle form submissions

// ADD or UPDATE restaurant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_restaurant'])) {
    // Sanitize and validate input
    $restaurant['name'] = sanitizeInput($_POST['name'] ?? '');
    $restaurant['location'] = sanitizeInput($_POST['location'] ?? '');
    $restaurant['id'] = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : '';
    
    // Validate required fields
    $required_fields = [
        'name' => $restaurant['name'],
        'location' => $restaurant['location']
    ];
    
    $errors = validateRequired($required_fields);
    
    // If no errors, process the submission
    if (empty($errors)) {
        try {
            if (!empty($restaurant['id'])) {
                // Update existing restaurant
                $stmt = $pdo->prepare("UPDATE restaurants SET name = ?, location = ? WHERE id = ?");
                $stmt->execute([$restaurant['name'], $restaurant['location'], $restaurant['id']]);
                $success_message = "Restaurant updated successfully!";
            } else {
                // Add new restaurant
                $stmt = $pdo->prepare("INSERT INTO restaurants (name, location) VALUES (?, ?)");
                $stmt->execute([$restaurant['name'], $restaurant['location']]);
                $success_message = "Restaurant added successfully!";
            }
            
            // Reset form after successful submission
            $restaurant = [
                'id' => '',
                'name' => '',
                'location' => ''
            ];
            
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}

// DELETE restaurant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_restaurant'])) {
    $restaurant_id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : '';
    
    if (!empty($restaurant_id)) {
        try {
            // Check if restaurant has orders
            $check_orders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ?");
            $check_orders->execute([$restaurant_id]);
            $has_orders = $check_orders->fetchColumn() > 0;
            
            if ($has_orders) {
                $errors['delete'] = "Cannot delete restaurant with existing orders";
            } else {
                // Delete restaurant (will cascade delete menu items due to foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
                $stmt->execute([$restaurant_id]);
                $success_message = "Restaurant deleted successfully!";
            }
            
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}

// EDIT restaurant (load data for editing)
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_restaurant = $stmt->fetch();
        
        if ($edit_restaurant) {
            $restaurant = $edit_restaurant;
        }
        
    } catch (PDOException $e) {
        $errors['database'] = "Database error: " . $e->getMessage();
    }
}

// Fetch all restaurants for display
try {
    $stmt = $pdo->query("SELECT r.*, 
                         (SELECT COUNT(*) FROM menus WHERE restaurant_id = r.id) as menu_count,
                         (SELECT COUNT(*) FROM orders WHERE restaurant_id = r.id) as order_count
                        FROM restaurants r
                        ORDER BY r.name");
    $restaurants = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors['database'] = "Database error: " . $e->getMessage();
    $restaurants = [];
}
?>

<!-- Restaurants Management Page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Manage Restaurants</h1>
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

<div class="row">
    <!-- Restaurant Form -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?= !empty($restaurant['id']) ? 'Edit' : 'Add' ?> Restaurant</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if (!empty($restaurant['id'])): ?>
                        <input type="hidden" name="id" value="<?= $restaurant['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Restaurant Name</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= htmlspecialchars($restaurant['name']) ?>">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>" id="location" name="location" value="<?= htmlspecialchars($restaurant['location']) ?>">
                        <?php if (isset($errors['location'])): ?>
                            <div class="invalid-feedback"><?= $errors['location'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="save_restaurant" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= !empty($restaurant['id']) ? 'Update' : 'Add' ?> Restaurant
                        </button>
                    </div>
                    
                    <?php if (!empty($restaurant['id'])): ?>
                        <div class="d-grid mt-2">
                            <a href="restaurants.php" class="btn btn-secondary">
                                <i class="fas fa-plus"></i> Add New Restaurant
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Restaurants List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">All Restaurants</h5>
            </div>
            <div class="card-body">
                <?php if (empty($restaurants)): ?>
                    <div class="alert alert-info">No restaurants found. Add a restaurant using the form.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Menu Items</th>
                                    <th>Orders</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restaurants as $rest): ?>
                                    <tr>
                                        <td><?= $rest['id'] ?></td>
                                        <td><?= htmlspecialchars($rest['name']) ?></td>
                                        <td><?= htmlspecialchars($rest['location']) ?></td>
                                        <td><?= $rest['menu_count'] ?></td>
                                        <td><?= $rest['order_count'] ?></td>
                                        <td>
                                            <a href="restaurants.php?edit=<?= $rest['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="menus.php?restaurant_id=<?= $rest['id'] ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-utensils"></i>
                                            </a>
                                            <?php if ($rest['order_count'] == 0): ?>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $rest['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?= $rest['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $rest['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel<?= $rest['id'] ?>">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete the restaurant "<?= htmlspecialchars($rest['name']) ?>"?</p>
                                                    <p class="text-danger">This will also delete all menu items associated with this restaurant.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form method="post">
                                                        <input type="hidden" name="id" value="<?= $rest['id'] ?>">
                                                        <button type="submit" name="delete_restaurant" class="btn btn-danger">Delete</button>
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
