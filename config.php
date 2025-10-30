<?php
// config.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auctionindo');

// Create database connection
 $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Function to redirect to a page
function redirect($page) {
    header("Location: $page");
    exit();
}

// Function to display error message
function showError($message) {
    return "<div class='error-message'>$message</div>";
}

// Function to display success message
function showSuccess($message) {
    return "<div class='success-message'>$message</div>";
}

// Function to format price
function formatPrice($price) {
    return "Rp " . number_format($price, 0, ',', '.');
}

// Function to calculate time remaining
function timeRemaining($endTime) {
    $now = new DateTime();
    $end = new DateTime($endTime);
    
    if ($now > $end) {
        return "Lelang telah berakhir";
    }
    
    $interval = $now->diff($end);
    
    if ($interval->d > 0) {
        return $interval->d . " hari " . $interval->h . " jam lagi";
    } elseif ($interval->h > 0) {
        return $interval->h . " jam " . $interval->i . " menit lagi";
    } else {
        return $interval->i . " menit " . $interval->s . " detik lagi";
    }
}
?>