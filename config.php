<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auction_db');

// Membuat koneksi ke database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Memulai session
session_start();

// Fungsi helper
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function isBlocked() {
    return isset($_SESSION['is_blocked']) && $_SESSION['is_blocked'] == 1;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// Format harga
function formatPrice($price) {
    return 'Rp ' . number_format($price, 2, ',', '.');
}

// Format waktu
function formatTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

// Hitung waktu tersisa
function timeRemaining($endtime) {
    $now = new DateTime();
    $end = new DateTime($endtime);
    
    if ($now > $end) {
        return "Lelang telah berakhir";
    }
    
    $interval = $now->diff($end);
    return $interval->days . " hari, " . $interval->h . " jam, " . $interval->i . " menit";
}

// Fungsi untuk upload gambar
function uploadImage($file, $directory = 'uploads/') {
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($filetype), $allowed)) {
        $newname = uniqid() . '.' . $filetype;
        
        if (move_uploaded_file($file['tmp_name'], $directory . $newname)) {
            return $directory . $newname;
        }
    }
    
    return false;
}
?>