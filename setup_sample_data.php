<?php
/**
 * Sample data setup for SimpleEats
 * 
 * This script adds sample restaurants and menu items to the database
 */

require_once('config/db_connect.php');

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Clear existing data (careful with this in production!)
    $pdo->exec("DELETE FROM order_items");
    $pdo->exec("DELETE FROM orders");
    $pdo->exec("DELETE FROM menus");
    $pdo->exec("DELETE FROM restaurants");
    $pdo->exec("DELETE FROM customers");
    
    // Add sample restaurants
    $restaurants = [
        ['Burger Palace', 'Downtown'],
        ['Pizza Heaven', 'Westside'],
        ['Taco Fiesta', 'Northside'],
        ['Sushi World', 'Eastside'],
        ['Pasta Paradise', 'Southside']
    ];
    
    $restaurant_stmt = $pdo->prepare("INSERT INTO restaurants (name, location) VALUES (?, ?)");
    
    foreach ($restaurants as $restaurant) {
        $restaurant_stmt->execute($restaurant);
    }
    
    // Add sample menu items
    $menus = [
        // Burger Palace items
        [1, 'Classic Cheeseburger', 8.99, 'Juicy beef patty with American cheese, lettuce, tomato and special sauce'],
        [1, 'Bacon Deluxe Burger', 10.99, 'Beef patty with crispy bacon, cheddar cheese, onion rings and BBQ sauce'],
        [1, 'Veggie Burger', 9.99, 'Plant-based patty with avocado, sprouts and vegan mayo'],
        [1, 'Chicken Sandwich', 9.99, 'Grilled chicken breast with lettuce, tomato and honey mustard'],
        [1, 'French Fries', 3.99, 'Crispy, golden fries with seasoning'],
        
        // Pizza Heaven items
        [2, 'Margherita Pizza', 12.99, 'Classic pizza with tomato sauce, mozzarella and fresh basil'],
        [2, 'Pepperoni Pizza', 14.99, 'Pizza with tomato sauce, mozzarella and pepperoni'],
        [2, 'Vegetarian Pizza', 13.99, 'Pizza with bell peppers, onions, mushrooms, olives and mozzarella'],
        [2, 'Meat Lovers Pizza', 16.99, 'Pizza with pepperoni, sausage, bacon and ham'],
        [2, 'Garlic Bread', 4.99, 'Toasted bread with garlic butter and herbs'],
        
        // Taco Fiesta items
        [3, 'Beef Taco', 2.99, 'Seasoned ground beef in a corn tortilla with lettuce, cheese and salsa'],
        [3, 'Chicken Taco', 2.99, 'Grilled chicken in a corn tortilla with lettuce, cheese and salsa'],
        [3, 'Vegetarian Taco', 2.99, 'Black beans and grilled vegetables in a corn tortilla with lettuce and salsa'],
        [3, 'Nachos Supreme', 8.99, 'Tortilla chips with beans, cheese, jalapeños, sour cream and guacamole'],
        [3, 'Burrito Grande', 9.99, 'Large flour tortilla filled with rice, beans, meat, cheese and salsa'],
        
        // Sushi World items
        [4, 'California Roll', 6.99, 'Crab, avocado and cucumber roll'],
        [4, 'Spicy Tuna Roll', 7.99, 'Fresh tuna with spicy mayo roll'],
        [4, 'Salmon Nigiri', 5.99, 'Fresh salmon slices on seasoned rice (2 pieces)'],
        [4, 'Vegetable Tempura', 8.99, 'Assorted vegetables fried in light tempura batter'],
        [4, 'Miso Soup', 3.99, 'Traditional Japanese soup with tofu and seaweed'],
        
        // Pasta Paradise items
        [5, 'Spaghetti Bolognese', 11.99, 'Spaghetti with rich meat sauce and parmesan'],
        [5, 'Fettuccine Alfredo', 12.99, 'Fettuccine pasta in creamy garlic parmesan sauce'],
        [5, 'Lasagna', 13.99, 'Layers of pasta, meat sauce, and three cheeses'],
        [5, 'Garlic Shrimp Linguine', 15.99, 'Linguine with garlic shrimp in white wine sauce'],
        [5, 'Tiramisu', 6.99, 'Classic Italian dessert with coffee and mascarpone']
    ];
    
    $menu_stmt = $pdo->prepare("INSERT INTO menus (restaurant_id, item_name, price, description) VALUES (?, ?, ?, ?)");
    
    foreach ($menus as $menu) {
        $menu_stmt->execute($menu);
    }
    
    // Sample customer
    $pdo->exec("INSERT INTO customers (name, email) VALUES ('John Doe', 'john@example.com')");
    
    // Commit transaction
    $pdo->commit();
    
    echo "Sample data added successfully!";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo "Error adding sample data: " . $e->getMessage();
}
?>