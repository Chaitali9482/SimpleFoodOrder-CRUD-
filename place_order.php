<?php
require_once('config/db_connect.php');
require_once('utils/validation.php');
include_once('includes/header.php');

// Start or continue session to access cart data
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if cart exists and is not empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo '<div class="alert alert-warning">Your cart is empty. Please add items before checking out.</div>';
    echo '<p><a href="restaurants.php" class="btn btn-primary">Browse Restaurants</a></p>';
    include_once('includes/footer.php');
    exit;
}

// Validate restaurant ID
if (!isset($_GET['restaurant_id']) || !is_numeric($_GET['restaurant_id'])) {
    echo '<div class="alert alert-danger">Invalid restaurant ID.</div>';
    include_once('includes/footer.php');
    exit;
}

$restaurant_id = $_GET['restaurant_id'];

// Get restaurant details
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    echo '<div class="alert alert-danger">Restaurant not found.</div>';
    include_once('includes/footer.php');
    exit;
}

// Filter cart items for current restaurant
$cart_items = array_filter($_SESSION['cart'], function($item) use ($restaurant_id) {
    return $item['restaurant_id'] == $restaurant_id;
});

if (empty($cart_items)) {
    echo '<div class="alert alert-warning">Your cart is empty for this restaurant.</div>';
    echo '<p><a href="order.php?restaurant_id=' . $restaurant_id . '" class="btn btn-primary">Add Items</a></p>';
    include_once('includes/footer.php');
    exit;
}

// Process order submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate customer information
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    // Validate required fields
    $required_fields = [
        'name' => $name,
        'email' => $email
    ];
    
    $errors = validateRequired($required_fields);
    
    // Validate email format
    if (!empty($email) && !validateEmail($email)) {
        $errors['email'] = "Please enter a valid email address";
    }
    
    // If no errors, process the order
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if customer exists, otherwise create new customer
            $customer_stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
            $customer_stmt->execute([$email]);
            $customer = $customer_stmt->fetch();
            
            if ($customer) {
                $customer_id = $customer['id'];
                
                // Update customer name if different
                $update_stmt = $pdo->prepare("UPDATE customers SET name = ? WHERE id = ?");
                $update_stmt->execute([$name, $customer_id]);
            } else {
                // Create new customer
                $insert_stmt = $pdo->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
                $insert_stmt->execute([$name, $email]);
                $customer_id = $pdo->lastInsertId();
            }
            
            // Create order
            $order_stmt = $pdo->prepare("INSERT INTO orders (customer_id, restaurant_id, status) VALUES (?, ?, 'Pending')");
            $order_stmt->execute([$customer_id, $restaurant_id]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, quantity) VALUES (?, ?, ?)");
            
            foreach ($cart_items as $item) {
                $item_stmt->execute([$order_id, $item['menu_id'], $item['quantity']]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Clear cart for this restaurant
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($restaurant_id) {
                return $item['restaurant_id'] != $restaurant_id;
            });
            
            // Set success flag
            $success = true;
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors['system'] = "An error occurred while processing your order. Please try again.";
        }
    }
}

// Calculate order total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!-- Checkout Page -->
<div class="mb-4">
    <h1>Checkout</h1>
    <p class="lead">Complete your order from <?= htmlspecialchars($restaurant['name']) ?></p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <h4 class="alert-heading">Order Placed Successfully!</h4>
        <p>Thank you for your order. Your food will be prepared shortly.</p>
        <hr>
        <p class="mb-0">You can track your order status in the <a href="order_history.php">Order History</a> page.</p>
    </div>
    
    <div class="mt-4">
        <a href="restaurants.php" class="btn btn-primary">Continue Shopping</a>
        <a href="order_history.php" class="btn btn-secondary">View Order History</a>
    </div>
<?php else: ?>
    <?php if (isset($errors['system'])): ?>
        <div class="alert alert-danger"><?= $errors['system'] ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Order Summary -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="list-group mb-3">
                        <?php foreach ($cart_items as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
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
            
            <div class="mb-4">
                <a href="order.php?restaurant_id=<?= $restaurant_id ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Order
                </a>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= $errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check"></i> Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once('includes/footer.php'); ?>
