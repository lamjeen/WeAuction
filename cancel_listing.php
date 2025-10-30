<?php
require_once 'config.php';

// Jika user tidak login, redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk membatalkan barang";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

// Cek apakah ID barang ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID barang tidak valid";
    $_SESSION['message_type'] = "danger";
    redirect('my_listings.php');
}

 $listing_id = $_GET['id'];

try {
    // Cek apakah barang milik user yang sedang login
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
    $stmt->execute([$listing_id, $_SESSION['user_id']]);
    $listing = $stmt->fetch();
    
    if (!$listing) {
        $_SESSION['message'] = "Barang tidak ditemukan atau Anda tidak memiliki izin untuk membatalkannya";
        $_SESSION['message_type'] = "danger";
        redirect('my_listings.php');
    }
    
    // Cek apakah barang sudah memiliki penawaran
    $stmt = $pdo->prepare("SELECT COUNT(*) as bid_count FROM bids WHERE listing_id = ?");
    $stmt->execute([$listing_id]);
    $result = $stmt->fetch();
    $has_bids = $result['bid_count'] > 0;
    
    if ($has_bids) {
        $_SESSION['message'] = "Barang tidak dapat dibatalkan karena sudah ada penawaran";
        $_SESSION['message_type'] = "danger";
        redirect('my_listings.php');
    }
    
    // Update status barang menjadi 'dibatalkan'
    $stmt = $pdo->prepare("UPDATE listings SET status = 'dibatalkan' WHERE id = ?");
    $stmt->execute([$listing_id]);
    
    $_SESSION['message'] = "Barang lelang berhasil dibatalkan!";
    $_SESSION['message_type'] = "success";
    redirect('my_listings.php');
    
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('my_listings.php');
}
?>