<?php
require_once('config/db_connect.php');
require_once('utils/validation.php');
include_once('includes/header.php');

// Start or continue session to store cart data
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
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

// Handle adding items to cart
if (isset($_POST['add_to_cart']) && isset($_POST['menu_id']) && isset($_POST['quantity'])) {
    $menu_id = filter_var($_POST['menu_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    
    if ($menu_id && $quantity && $quantity > 0) {
        // Check if item exists in restaurant's menu
        $check_stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ? AND restaurant_id = ?");
        $check_stmt->execute([$menu_id, $restaurant_id]);
        $menu_item = $check_stmt->fetch();
        
        if ($menu_item) {
            // Check if item already in cart
            $item_key = null;
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['menu_id'] == $menu_id) {
                    $item_key = $key;
                    break;
                }
            }
            
            if ($item_key !== null) {
                // Update quantity if item already in cart
                $_SESSION['cart'][$item_key]['quantity'] += $quantity;
            } else {
                // Add new item to cart
                $_SESSION['cart'][] = [
                    'restaurant_id' => $restaurant_id,
                    'menu_id' => $menu_id,
                    'name' => $menu_item['item_name'],
                    'price' => $menu_item['price'],
                    'quantity' => $quantity
                ];
            }
            
            echo '<div class="alert alert-success">Item added to cart.</div>';
        }
    }
}

// Handle removing items from cart
if (isset($_GET['remove_item']) && is_numeric($_GET['remove_item'])) {
    $remove_index = $_GET['remove_item'];
    if (isset($_SESSION['cart'][$remove_index])) {
        unset($_SESSION['cart'][$remove_index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
        echo '<div class="alert alert-success">Item removed from cart.</div>';
    }
}

// Filter cart items for current restaurant
$cart_items = array_filter($_SESSION['cart'], function($item) use ($restaurant_id) {
    return $item['restaurant_id'] == $restaurant_id;
});

// Get menu items for this restaurant
$menu_stmt = $pdo->prepare("SELECT * FROM menus WHERE restaurant_id = ? ORDER BY item_name");
$menu_stmt->execute([$restaurant_id]);
$menu_items = $menu_stmt->fetchAll();
?>

<!-- Order Page -->
<div class="mb-4">
    <h1>Order from <?= htmlspecialchars($restaurant['name']) ?></h1>
    <p class="lead">
        <i class="fas fa-map-marker-alt"></i> 
        <?= htmlspecialchars($restaurant['location']) ?>
    </p>
</div>

<div class="row">
    <!-- Menu Items -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Menu Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($menu_items)): ?>
                    <div class="alert alert-info">
                        No menu items available for this restaurant.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($menu_items as $item): ?>
                            <div class="list-group-item">
                                <form method="post" class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-0"><?= htmlspecialchars($item['item_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                        <p class="mb-0 text-primary">$<?= number_format($item['price'], 2) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Qty</span>
                                            <input type="number" name="quantity" class="form-control" min="1" value="1">
                                            <input type="hidden" name="menu_id" value="<?= $item['id'] ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Cart -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Your Order</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cart_items)): ?>
                    <div class="alert alert-info">
                        Your cart is empty. Add items from the menu.
                    </div>
                <?php else: ?>
                    <div class="list-group mb-3">
                        <?php 
                        $total = 0;
                        foreach ($cart_items as $index => $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                        <small><?= $item['quantity'] ?> x $<?= number_format($item['price'], 2) ?></small>
                                    </div>
                                    <div>
                                        <span class="text-primary me-2">$<?= number_format($subtotal, 2) ?></span>
                                        <a href="?restaurant_id=<?= $restaurant_id ?>&remove_item=<?= $index ?>" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary">$<?= number_format($total, 2) ?></strong>
                    </div>
                    
                    <a href="place_order.php?restaurant_id=<?= $restaurant_id ?>" class="btn btn-success w-100">
                        <i class="fas fa-check"></i> Proceed to Checkout
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="menu.php?restaurant_id=<?= $restaurant_id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Menu
            </a>
        </div>
    </div>
</div>

<?php include_once('includes/footer.php'); ?>
