<?php
require_once('../config/db_connect.php');
require_once('../utils/validation.php');
include_once('../includes/admin_header.php');

// Initialize variables
$errors = [];
$success_message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = isset($_POST['order_id']) && is_numeric($_POST['order_id']) ? $_POST['order_id'] : '';
    $status = sanitizeInput($_POST['status'] ?? '');
    
    if (!empty($order_id) && in_array($status, ['Pending', 'Completed'])) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $success_message = "Order status updated successfully!";
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = isset($_POST['order_id']) && is_numeric($_POST['order_id']) ? $_POST['order_id'] : '';
    
    if (!empty($order_id)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete order items first
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Then delete the order
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $success_message = "Order deleted successfully!";
        } catch (PDOException $e) {
            // Rollback in case of error
            $pdo->rollBack();
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}

// Check for filter parameters
$status_filter = isset($_GET['status']) && in_array($_GET['status'], ['Pending', 'Completed', 'All']) ? $_GET['status'] : 'All';
$restaurant_filter = isset($_GET['restaurant_id']) && is_numeric($_GET['restaurant_id']) ? $_GET['restaurant_id'] : '';

// View specific order
$view_order = null;
$order_items = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = $_GET['view'];
    
    try {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   c.name as customer_name, c.email as customer_email,
                   r.name as restaurant_name, r.location as restaurant_location
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN restaurants r ON o.restaurant_id = r.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $view_order = $stmt->fetch();
        
        // Get order items
        if ($view_order) {
            $items_stmt = $pdo->prepare("
                SELECT oi.*, m.item_name, m.price
                FROM order_items oi
                JOIN menus m ON oi.menu_id = m.id
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$order_id]);
            $order_items = $items_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $errors['database'] = "Database error: " . $e->getMessage();
    }
}

// Fetch all restaurants for filter dropdown
try {
    $stmt = $pdo->query("SELECT * FROM restaurants ORDER BY name");
    $restaurants = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors['database'] = "Database error: " . $e->getMessage();
    $restaurants = [];
}

// Fetch orders with filters
try {
    $sql = "
        SELECT o.*, 
               c.name as customer_name, c.email as customer_email,
               r.name as restaurant_name
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        JOIN restaurants r ON o.restaurant_id = r.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($status_filter !== 'All') {
        $sql .= " AND o.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($restaurant_filter)) {
        $sql .= " AND o.restaurant_id = ?";
        $params[] = $restaurant_filter;
    }
    
    $sql .= " ORDER BY o.order_date DESC";
    
    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($sql);
    }
    
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors['database'] = "Database error: " . $e->getMessage();
    $orders = [];
}
?>

<!-- Orders Management Page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Manage Orders</h1>
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

<?php if ($view_order): ?>
    <!-- Order Details View -->
    <div class="card mb-4">
        <div class="card-header <?= $view_order['status'] === 'Completed' ? 'bg-success' : 'bg-warning' ?> text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order #<?= $view_order['id'] ?> Details</h5>
                <span class="badge bg-light text-dark"><?= $view_order['status'] ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <p>
                        <strong>Name:</strong> <?= htmlspecialchars($view_order['customer_name']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($view_order['customer_email']) ?>
                    </p>
                    
                    <h6 class="mt-4">Order Information</h6>
                    <p>
                        <strong>Date:</strong> <?= date('F j, Y, g:i a', strtotime($view_order['order_date'])) ?><br>
                        <strong>Status:</strong> <?= $view_order['status'] ?>
                    </p>
                    
                    <!-- Status Update Form -->
                    <form method="post" class="mb-3">
                        <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
                        <div class="input-group">
                            <select class="form-select" name="status">
                                <option value="Pending" <?= $view_order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Completed" <?= $view_order['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <h6>Restaurant Information</h6>
                    <p>
                        <strong>Name:</strong> <?= htmlspecialchars($view_order['restaurant_name']) ?><br>
                        <strong>Location:</strong> <?= htmlspecialchars($view_order['restaurant_location']) ?>
                    </p>
                </div>
            </div>
            
            <h6 class="mt-4">Order Items</h6>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        foreach ($order_items as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td>$<?= number_format($item['price'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>$<?= number_format($subtotal, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th>$<?= number_format($total, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3">
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal">
                    <i class="fas fa-trash"></i> Delete Order
                </button>
            </div>
            
            <!-- Delete Order Modal -->
            <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteOrderModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete Order #<?= $view_order['id'] ?>?</p>
                            <p class="text-danger">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
                                <button type="submit" name="delete_order" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Orders List View -->
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row align-items-end">
                <div class="col-md-5">
                    <label for="status_filter" class="form-label">Filter by Status</label>
                    <select class="form-select" id="status_filter" name="status">
                        <option value="All" <?= $status_filter === 'All' ? 'selected' : '' ?>>All Orders</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending Orders</option>
                        <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed Orders</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="restaurant_filter" class="form-label">Filter by Restaurant</label>
                    <select class="form-select" id="restaurant_filter" name="restaurant_id">
                        <option value="">All Restaurants</option>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <option value="<?= $restaurant['id'] ?>" <?= $restaurant_filter == $restaurant['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($restaurant['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Orders</h5>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="alert alert-info">No orders found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Restaurant</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($order['customer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($order['restaurant_name']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <span class="badge <?= $order['status'] === 'Completed' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?view=<?= $order['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?= $order['id'] ?>">
                                            <i class="fas fa-edit"></i> Status
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Status Update Modal -->
                                <div class="modal fade" id="statusModal<?= $order['id'] ?>" tabindex="-1" aria-labelledby="statusModalLabel<?= $order['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="statusModalLabel<?= $order['id'] ?>">Update Order Status</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <div class="mb-3">
                                                        <label for="status<?= $order['id'] ?>" class="form-label">Status</label>
                                                        <select class="form-select" id="status<?= $order['id'] ?>" name="status">
                                                            <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                        </select>
                                                    </div>
                                                    <div class="d-grid">
                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                    </div>
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
<?php endif; ?>

<?php include_once('../includes/footer.php'); ?>
