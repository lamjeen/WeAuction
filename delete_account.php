<?php
require_once 'config.php';

// Jika user tidak login, redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk mengakses halaman ini";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

 $user_id = $_SESSION['user_id'];

try {
    // 1. Hapus gambar profil pengguna dari server jika ada
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && $user['profile_image'] && file_exists($user['profile_image'])) {
        unlink($user['profile_image']);
    }

    // 2. Hapus semua gambar dari listing yang dimiliki pengguna
    // Pertama, dapatkan semua path gambar dari listing milik user
    $stmt = $pdo->prepare("SELECT li.image_url FROM listing_images li 
                            JOIN listings l ON li.listing_id = l.id 
                            WHERE l.user_id = ?");
    $stmt->execute([$user_id]);
    $images = $stmt->fetchAll();

    // Hapus setiap file gambar dari server
    foreach ($images as $image) {
        if ($image['image_url'] && file_exists($image['image_url'])) {
            unlink($image['image_url']);
        }
    }

    // 3. Hapus user dari database
    // Karena kita menggunakan ON DELETE CASCADE, menghapus user akan otomatis
    // menghapus semua baris terkait di tabel listings, bids, dan listing_images.
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // 4. Hancurkan session dan redirect
    session_unset();
    session_destroy();

    $_SESSION['message'] = "Akun Anda telah berhasil dihapus. Kami berharap dapat bertemu dengan Anda lagi di masa depan.";
    $_SESSION['message_type'] = "success";
    redirect('index.php');

} catch(PDOException $e) {
    // Jika terjadi error, tampilkan pesan dan jangan hapus apa-apa
    $_SESSION['message'] = "Terjadi kesalahan saat mencoba menghapus akun. Silakan coba lagi atau hubungi admin.";
    $_SESSION['message_type'] = "danger";
    redirect('profile.php');
}
?>