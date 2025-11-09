<?php
require_once 'config.php';

// Pastikan pengguna sudah login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk melakukan tindakan ini.";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

// Pastikan ID tawaran dan ID listing ada
if (!isset($_GET['id']) || !isset($_GET['listing_id'])) {
    $_SESSION['message'] = "Parameter tidak valid.";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

 $bid_id = $_GET['id'];
 $listing_id = $_GET['listing_id'];
 $user_id = $_SESSION['user_id'];

try {
    // --- LANGKAH 1: Validasi SEMUA kondisi dalam SATU query database ---
    // Query ini akan mengembalikan tawaran HANYA JIKA:
    // 1. ID tawaran dan ID pengguna cocok.
    // 2. Tawaran dibuat kurang dari 5 detik yang lalu.
    // 3. Tawaran ini adalah yang tertinggi untuk listing tersebut.
    $stmt = $pdo->prepare("
        SELECT b.id, b.bid_amount, l.start_price 
        FROM bids b
        JOIN listings l ON b.listing_id = l.id
        WHERE b.id = ? 
          AND b.user_id = ?
          AND b.listing_id = ?
          AND TIMESTAMPDIFF(SECOND, b.created_at, NOW()) < 5
          AND b.bid_amount = (SELECT MAX(bid_amount) FROM bids WHERE listing_id = ?)
    ");
    $stmt->execute([$bid_id, $user_id, $listing_id, $listing_id]);
    $bid_to_delete = $stmt->fetch();

    if (!$bid_to_delete) {
        // Jika query tidak mengembalikan hasil, berarti salah satu kondisi tidak terpenuhi.
        $_SESSION['message'] = "Tawaran tidak dapat dihapus. Pastikan tawaran dibuat kurang dari 5 detik yang lalu dan masih menjadi tawaran tertinggi.";
        $_SESSION['message_type'] = "danger";
        redirect("listing_details.php?id=$listing_id");
    }

    // --- LANGKAH 2: Hapus tawaran (karena sudah valid) ---
    $stmt = $pdo->prepare("DELETE FROM bids WHERE id = ?");
    $stmt->execute([$bid_id]);

    // --- LANGKAH 3: Perbarui harga saat ini di tabel listings ---
    // Cari tawaran tertinggi berikutnya
    $stmt = $pdo->prepare("SELECT bid_amount FROM bids WHERE listing_id = ? ORDER BY bid_amount DESC LIMIT 1");
    $stmt->execute([$listing_id]);
    $next_highest_bid = $stmt->fetch();

    if ($next_highest_bid) {
        // Jika ada tawaran lain, update ke tawaran tertinggi berikutnya
        $new_price = $next_highest_bid['bid_amount'];
    } else {
        // Jika tidak ada tawaran lain, kembalikan ke harga awal
        $new_price = $bid_to_delete['start_price'];
    }

    $stmt = $pdo->prepare("UPDATE listings SET current_price = ? WHERE id = ?");
    $stmt->execute([$new_price, $listing_id]);

    $_SESSION['message'] = "Tawaran berhasil dihapus.";
    $_SESSION['message_type'] = "success";
    redirect("listing_details.php?id=$listing_id");

} catch (PDOException $e) {
    $_SESSION['message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect("listing_details.php?id=$listing_id");
}
?>