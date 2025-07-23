<?php
require_once('config/db_connect.php');
include_once('includes/header.php');

// Fetch featured restaurants (showing 4 random restaurants)
$stmt = $pdo->query("SELECT * FROM restaurants ORDER BY RAND() LIMIT 4");
$featured_restaurants = $stmt->fetchAll();
?>

<!-- Welcome Section -->
<div class="jumbotron my-4 bg-light p-5 rounded">
    <h1 class="display-4">Welcome to SimpleEats</h1>
    <p class="lead">Order delicious food from your favorite restaurants.</p>
    <hr class="my-4">
    <p>Browse our restaurant collection and enjoy great food delivered to your doorstep.</p>
    <a class="btn btn-primary" href="restaurants.php" role="button">View All Restaurants</a>
</div>

<!-- Featured Restaurants -->
<section class="my-5">
    <h2 class="mb-4">Featured Restaurants</h2>
    
    <?php if (empty($featured_restaurants)): ?>
        <div class="alert alert-info">
            No restaurants available at the moment. Please check back later.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($featured_restaurants as $restaurant): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($restaurant['name']) ?></h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?= htmlspecialchars($restaurant['location']) ?>
                            </p>
                            <?php
                            // Get menu item count for this restaurant
                            $menu_stmt = $pdo->prepare("SELECT COUNT(*) FROM menus WHERE restaurant_id = ?");
                            $menu_stmt->execute([$restaurant['id']]);
                            $menu_count = $menu_stmt->fetchColumn();
                            ?>
                            <p class="card-text"><?= $menu_count ?> menu items available</p>
                        </div>
                        <div class="card-footer">
                            <a href="menu.php?restaurant_id=<?= $restaurant['id'] ?>" class="btn btn-primary btn-sm">View Menu</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="text-center mt-4">
        <a href="restaurants.php" class="btn btn-outline-secondary">View All Restaurants</a>
    </div>
</section>

<!-- How It Works -->
<section class="my-5 bg-light p-4 rounded">
    <h2 class="mb-4">How SimpleEats Works</h2>
    
    <div class="row text-center">
        <div class="col-md-4 mb-3">
            <div class="p-3">
                <i class="fas fa-store fa-3x mb-3 text-primary"></i>
                <h4>Choose a Restaurant</h4>
                <p>Browse our collection of restaurants and select your favorite.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="p-3">
                <i class="fas fa-utensils fa-3x mb-3 text-primary"></i>
                <h4>Select Your Food</h4>
                <p>Browse through the menu items and add to your cart.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="p-3">
                <i class="fas fa-check-circle fa-3x mb-3 text-primary"></i>
                <h4>Place Your Order</h4>
                <p>Complete your order and track its status in real-time.</p>
            </div>
        </div>
    </div>
</section>

<?php include_once('includes/footer.php'); ?>
