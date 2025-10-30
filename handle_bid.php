<?php
// handle_bid.php

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Anda harus login untuk melakukan tawaran";
    redirect('login.php');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    $listing_id = $_POST['listing_id'];
    $user_id = $_SESSION['user_id'];
    $bid_amount = $_POST['bid_amount'];
    
    // Get listing details
    $sql = "SELECT * FROM Listings WHERE listing_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Barang tidak ditemukan";
        redirect('index.php');
    }
    
    $listing = $result->fetch_assoc();
    
    // Validation
    $errors = [];
    
    // Check if user is not the listing owner
    if ($user_id == $listing['user_id']) {
        $errors[] = "Anda tidak bisa menawar barang Anda sendiri";
    }
    
    // Check if auction is still active
    if ($listing['status'] !== 'aktif') {
        $errors[] = "Lelang ini sudah tidak aktif";
    }
    
    // Check if auction has ended
    if (strtotime($listing['end_time']) < time()) {
        $errors[] = "Lelang sudah berakhir";
    }
    
    // Validate bid amount
    if (empty($bid_amount) || !is_numeric($bid_amount) || $bid_amount <= 0) {
        $errors[] = "Jumlah tawaran harus berupa angka positif";
    }
    
    // Check if bid is higher than current price
    if ($bid_amount <= $listing['current_price']) {
        $errors[] = "Tawaran harus lebih tinggi dari harga saat ini (" . formatPrice($listing['current_price']) . ")";
    }
    
    // Place bid if no errors
    if (empty($errors)) {
        $insert_bid_sql = "INSERT INTO Bids (listing_id, user_id, bid_amount) VALUES (?, ?, ?)";
        $insert_bid_stmt = $conn->prepare($insert_bid_sql);
        $insert_bid_stmt->bind_param("iid", $listing_id, $user_id, $bid_amount);
        
        if ($insert_bid_stmt->execute()) {
            // Update current price
            $update_sql = "UPDATE Listings SET current_price = ? WHERE listing_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("di", $bid_amount, $listing_id);
            $update_stmt->execute();
            
            $_SESSION['success'] = "Tawaran Anda berhasil disimpan!";
        } else {
            $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    // Redirect back to item details page
    redirect("item_details.php?id=$listing_id");
} else {
    // If not a POST request, redirect to homepage
    redirect('index.php');
}
?>