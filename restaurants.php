<?php
require_once('config/db_connect.php');
include_once('includes/header.php');

// Fetch all restaurants
$stmt = $pdo->query("SELECT * FROM restaurants ORDER BY name");
$restaurants = $stmt->fetchAll();
?>

<!-- Restaurants List -->
<h1 class="mb-4">All Restaurants</h1>

<?php if (empty($restaurants)): ?>
    <div class="alert alert-info">
        No restaurants available at the moment. Please check back later.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($restaurants as $restaurant): ?>
            <div class="col-md-4 mb-4">
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

<?php include_once('includes/footer.php'); ?>
