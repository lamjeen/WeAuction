<?php
require_once 'config.php';

// Jika user tidak login atau bukan admin, redirect ke halaman utama
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Anda tidak memiliki izin untuk mengakses halaman ini";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Cek apakah ID barang ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID barang tidak valid";
    $_SESSION['message_type'] = "danger";
    redirect('admin_dashboard.php');
}

 $listing_id = $_GET['id'];

try {
    // Ambil data barang
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();
    
    if (!$listing) {
        $_SESSION['message'] = "Barang tidak ditemukan";
        $_SESSION['message_type'] = "danger";
        redirect('admin_dashboard.php');
    }
    
    // Ambil gambar barang
    $stmt = $pdo->prepare("SELECT image_url FROM listing_images WHERE listing_id = ?");
    $stmt->execute([$listing_id]);
    $images = $stmt->fetchAll();
    
    // Hapus gambar jika ada
    foreach ($images as $image) {
        if ($image['image_url'] && file_exists($image['image_url'])) {
            unlink($image['image_url']);
        }
    }
    
    // Hapus barang (cascade akan menghapus penawaran terkait)
    $stmt = $pdo->prepare("DELETE FROM listings WHERE id = ?");
    $stmt->execute([$listing_id]);
    
    $_SESSION['message'] = "Barang lelang berhasil dihapus!";
    $_SESSION['message_type'] = "success";
    redirect('admin_dashboard.php');
    
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('admin_dashboard.php');
}
?>