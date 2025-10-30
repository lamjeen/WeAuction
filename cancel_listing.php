<?php
// cancel_listing.php

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if listing ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('dashboard.php');
}

 $listing_id = $_GET['id'];
 $user_id = $_SESSION['user_id'];

// Get listing details
 $sql = "SELECT * FROM Listings WHERE listing_id = ? AND user_id = ?";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("ii", $listing_id, $user_id);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('dashboard.php');
}

 $listing = $result->fetch_assoc();

// Check if there are any bids for this listing
 $bid_check_sql = "SELECT COUNT(*) as bid_count FROM Bids WHERE listing_id = ?";
 $bid_check_stmt = $conn->prepare($bid_check_sql);
 $bid_check_stmt->bind_param("i", $listing_id);
 $bid_check_stmt->execute();
 $bid_check_result = $bid_check_stmt->get_result();
 $bid_count = $bid_check_result->fetch_assoc()['bid_count'];

// If there are bids, cancellation is not allowed
if ($bid_count > 0) {
    $_SESSION['error'] = "Tidak dapat membatalkan lelang yang sudah memiliki tawaran";
    redirect('dashboard.php');
}

// Update listing status to 'dibatalkan'
 $update_sql = "UPDATE Listings SET status = 'dibatalkan' WHERE listing_id = ?";
 $update_stmt = $conn->prepare($update_sql);
 $update_stmt->bind_param("i", $listing_id);

if ($update_stmt->execute()) {
    $_SESSION['success'] = "Lelang berhasil dibatalkan";
} else {
    $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
}

redirect('dashboard.php');
?>