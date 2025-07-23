<?php
require_once('../config/db_connect.php');
include_once('../includes/admin_header.php');

// Get statistics for the dashboard
try {
    // Count restaurants
    $restaurant_stmt = $pdo->query("SELECT COUNT(*) FROM restaurants");
    $restaurant_count = $restaurant_stmt->fetchColumn();
    
    // Count menu items
    $menu_stmt = $pdo->query("SELECT COUNT(*) FROM menus");
    $menu_count = $menu_stmt->fetchColumn();
    
    // Count customers
    $customer_stmt = $pdo->query("SELECT COUNT(*) FROM customers");
    $customer_count = $customer_stmt->fetchColumn();
    
    // Count orders
    $order_stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $order_count = $order_stmt->fetchColumn();
    
    // Count pending orders
    $pending_stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
    $pending_count = $pending_stmt->fetchColumn();
    
    // Recent orders
    $recent_orders_stmt = $pdo->query("
        SELECT o.id, o.order_date, o.status, r.name as restaurant_name, c.name as customer_name
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.id
        JOIN customers c ON o.customer_id = c.id
        ORDER BY o.order_date DESC
        LIMIT 5
    ");
    $recent_orders = $recent_orders_stmt->fetchAll();
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching dashboard data: ' . $e->getMessage() . '</div>';
}
?>

<!-- Admin Dashboard -->
<h1 class="mb-4">Admin Dashboard</h1>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white mb-3">
            <div class="card-body">
                <h5 class="card-title">Restaurants</h5>
                <p class="card-text display-4"><?= $restaurant_count ?></p>
                <a href="restaurants.php" class="btn btn-light btn-sm">Manage</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white mb-3">
            <div class="card-body">
                <h5 class="card-title">Menu Items</h5>
                <p class="card-text display-4"><?= $menu_count ?></p>
                <a href="menus.php" class="btn btn-light btn-sm">Manage</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white mb-3">
            <div class="card-body">
                <h5 class="card-title">Customers</h5>
                <p class="card-text display-4"><?= $customer_count ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white mb-3">
            <div class="card-body">
                <h5 class="card-title">Orders</h5>
                <p class="card-text display-4"><?= $order_count ?></p>
                <a href="orders.php" class="btn btn-light btn-sm">Manage</a>
            </div>
        </div>
    </div>
</div>

<!-- Pending Orders Alert -->
<?php if ($pending_count > 0): ?>
    <div class="alert alert-warning">
        <h5><i class="fas fa-exclamation-triangle"></i> You have <?= $pending_count ?> pending orders!</h5>
        <p class="mb-0">These orders are waiting for processing. <a href="orders.php" class="alert-link">View Orders</a></p>
    </div>
<?php endif; ?>

<!-- Recent Orders -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Recent Orders</h5>
    </div>
    <div class="card-body">
        <?php if (empty($recent_orders)): ?>
            <div class="alert alert-info">No orders found.</div>
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
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="orders.php" class="btn btn-primary">View All Orders</a>
        </div>
    </div>
</div>

<!-- Quick Access Buttons -->
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="d-grid">
            <a href="restaurants.php" class="btn btn-lg btn-outline-primary">
                <i class="fas fa-store"></i> Manage Restaurants
            </a>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="d-grid">
            <a href="menus.php" class="btn btn-lg btn-outline-success">
                <i class="fas fa-utensils"></i> Manage Menu Items
            </a>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="d-grid">
            <a href="orders.php" class="btn btn-lg btn-outline-warning">
                <i class="fas fa-list-alt"></i> Manage Orders
            </a>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
