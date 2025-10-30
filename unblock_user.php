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
    // Unblock pengguna
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $_SESSION['message'] = "Pengguna berhasil di-unblock!";
    $_SESSION['message_type'] = "success";
    redirect('admin_dashboard.php');
    
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('admin_dashboard.php');
}
?>