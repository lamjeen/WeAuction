<?php
require_once 'config.php';

// Jika user tidak login atau bukan admin, redirect ke halaman utama
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Anda tidak memiliki izin untuk mengakses halaman ini";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Cek apakah ID pengguna ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID pengguna tidak valid";
    $_SESSION['message_type'] = "danger";
    redirect('admin_dashboard.php');
}

 $user_id = $_GET['id'];

try {
    // Cek apakah pengguna adalah admin
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['message'] = "Pengguna tidak ditemukan";
        $_SESSION['message_type'] = "danger";
        redirect('admin_dashboard.php');
    }
    
    if ($user['is_admin']) {
        $_SESSION['message'] = "Admin tidak dapat diblokir";
        $_SESSION['message_type'] = "danger";
        redirect('admin_dashboard.php');
    }
    
    // Block pengguna
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $_SESSION['message'] = "Pengguna berhasil diblokir!";
    $_SESSION['message_type'] = "success";
    redirect('admin_dashboard.php');
    
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('admin_dashboard.php');
}
?>