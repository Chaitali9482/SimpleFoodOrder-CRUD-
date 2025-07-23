<?php
require_once('config/db_connect.php');
require_once('utils/validation.php');
include_once('includes/header.php');

$customer_id = null;
$orders = [];
$errors = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    $email = sanitizeInput($_POST['email'] ?? '');
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!validateEmail($email)) {
        $errors['email'] = "Please enter a valid email address";
    } else {
        // Get customer ID from email
        $customer_stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $customer_stmt->execute([$email]);
        $customer = $customer_stmt->fetch();
        
        if ($customer) {
            $customer_id = $customer['id'];
            
            // Get orders for this customer
            $orders_stmt = $pdo->prepare("
                SELECT o.*, r.name as restaurant_name, r.location as restaurant_location
                FROM orders o
                JOIN restaurants r ON o.restaurant_id = r.id
                WHERE o.customer_id = ?
                ORDER BY o.order_date DESC
            ");
            $orders_stmt->execute([$customer_id]);
            $orders = $orders_stmt->fetchAll();
        } else {
            $errors['email'] = "No orders found for this email address";
        }
    }
}
?>

<!-- Order History Page -->
<div class="mb-4">
    <h1>Order History</h1>
    <p class="lead">View your past and current orders</p>
</div>

<!-- Email Verification Form -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Verify Your Email</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Find My Orders
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Orders List -->
<?php if ($submitted && empty($errors) && !empty($orders)): ?>
    <h3 class="mb-3">Your Orders</h3>
    
    <div class="row">
        <?php foreach ($orders as $order): 
            // Get order items
            $items_stmt = $pdo->prepare("
                SELECT oi.*, m.item_name, m.price
                FROM order_items oi
                JOIN menus m ON oi.menu_id = m.id
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$order['id']]);
            $items = $items_stmt->fetchAll();
            
            // Calculate order total
            $total = 0;
            foreach ($items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header <?= $order['status'] === 'Completed' ? 'bg-success' : 'bg-warning' ?> text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order #<?= $order['id'] ?></h5>
                            <span class="badge bg-light text-dark">
                                <?= $order['status'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Restaurant:</strong> <?= htmlspecialchars($order['restaurant_name']) ?>
                        </p>
                        <p class="mb-2">
                            <strong>Location:</strong> <?= htmlspecialchars($order['restaurant_location']) ?>
                        </p>
                        <p class="mb-2">
                            <strong>Date:</strong> <?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?>
                        </p>
                        
                        <h6 class="mt-4 mb-2">Order Items:</h6>
                        <div class="list-group mb-3">
                            <?php foreach ($items as $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($item['item_name']) ?></h6>
                                            <small><?= $item['quantity'] ?> x $<?= number_format($item['price'], 2) ?></small>
                                        </div>
                                        <span class="text-primary">$<?= number_format($subtotal, 2) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="text-primary">$<?= number_format($total, 2) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($submitted && empty($errors)): ?>
    <div class="alert alert-info">
        No orders found for this email address. Try placing an order from our restaurants.
    </div>
    <p>
        <a href="restaurants.php" class="btn btn-primary">Browse Restaurants</a>
    </p>
<?php endif; ?>

<?php include_once('includes/footer.php'); ?>
