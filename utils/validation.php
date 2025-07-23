<?php
/**
 * Validation utility functions
 * 
 * This file contains functions for input validation and sanitation
 * to ensure data security and prevent SQL injection.
 */

/**
 * Validates and sanitizes input data
 * 
 * @param string $data - Raw input data
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validates email format
 * 
 * @param string $email - Email to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validates numerical input
 * 
 * @param mixed $number - Number to validate
 * @return bool - True if valid, false otherwise
 */
function validateNumber($number) {
    return is_numeric($number);
}

/**
 * Validates price format
 * 
 * @param mixed $price - Price to validate
 * @return bool - True if valid, false otherwise
 */
function validatePrice($price) {
    return preg_match('/^\d+(\.\d{1,2})?$/', $price);
}

/**
 * Validates required fields are not empty
 * 
 * @param array $fields - Array of fields to check
 * @return array - Array of errors if any
 */
function validateRequired($fields) {
    $errors = [];
    
    foreach ($fields as $field => $value) {
        if (empty(trim($value))) {
            $errors[$field] = ucfirst($field) . " is required";
        }
    }
    
    return $errors;
}
?>
