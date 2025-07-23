<?php
require_once('config/db_connect.php');
require_once('utils/validation.php');

// This file is for handling customer information form submission from place_order.php
// It doesn't have its own UI but processes the customer data

// Validate and sanitize customer data
$name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';

$errors = [];

// Validate required fields
if (empty($name)) {
    $errors['name'] = "Name is required";
}

if (empty($email)) {
    $errors['email'] = "Email is required";
} elseif (!validateEmail($email)) {
    $errors['email'] = "Please enter a valid email address";
}

// Return response based on validation
if (empty($errors)) {
    // Check if customer already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        // Customer exists, update information
        $customer = $stmt->fetch();
        $customer_id = $customer['id'];
        
        $update_stmt = $pdo->prepare("UPDATE customers SET name = ? WHERE id = ?");
        $update_stmt->execute([$name, $customer_id]);
    } else {
        // Create new customer
        $insert_stmt = $pdo->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
        $insert_stmt->execute([$name, $email]);
        $customer_id = $pdo->lastInsertId();
    }
    
    echo json_encode(['success' => true, 'customer_id' => $customer_id]);
} else {
    echo json_encode(['success' => false, 'errors' => $errors]);
}
?>
