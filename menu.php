<?php
require_once('config/db_connect.php');
include_once('includes/header.php');

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

// Get menu items for this restaurant
$menu_stmt = $pdo->prepare("SELECT * FROM menus WHERE restaurant_id = ? ORDER BY item_name");
$menu_stmt->execute([$restaurant_id]);
$menu_items = $menu_stmt->fetchAll();
?>

<!-- Restaurant Menu -->
<div class="mb-4">
    <h1><?= htmlspecialchars($restaurant['name']) ?></h1>
    <p class="lead">
        <i class="fas fa-map-marker-alt"></i> 
        <?= htmlspecialchars($restaurant['location']) ?>
    </p>
</div>

<?php if (empty($menu_items)): ?>
    <div class="alert alert-info">
        No menu items available for this restaurant. Please check back later.
    </div>
<?php else: ?>
    <div class="row mb-4">
        <div class="col">
            <a href="order.php?restaurant_id=<?= $restaurant_id ?>" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> Order from this restaurant
            </a>
            <a href="restaurants.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Restaurants
            </a>
        </div>
    </div>

    <div class="row">
        <?php foreach ($menu_items as $item): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($item['item_name']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($item['description']) ?></p>
                        <p class="card-text text-primary fw-bold">$<?= number_format($item['price'], 2) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include_once('includes/footer.php'); ?>
