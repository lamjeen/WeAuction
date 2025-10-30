<?php
// item_details.php

require_once 'header.php';

// Check if listing ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('index.php');
}

 $listing_id = $_GET['id'];
 $errors = [];
 $success = '';

// Get listing details
 $sql = "SELECT l.*, c.category_name, u.username, u.full_name 
        FROM Listings l 
        JOIN Categories c ON l.category_id = c.category_id 
        JOIN Users u ON l.user_id = u.user_id 
        WHERE l.listing_id = ?";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("i", $listing_id);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('index.php');
}

 $listing = $result->fetch_assoc();

// Get listing images
 $images_sql = "SELECT * FROM Listing_Images WHERE listing_id = ?";
 $images_stmt = $conn->prepare($images_sql);
 $images_stmt->bind_param("i", $listing_id);
 $images_stmt->execute();
 $images_result = $images_stmt->get_result();

// Get bid history
 $bids_sql = "SELECT b.*, u.username 
            FROM Bids b 
            JOIN Users u ON b.user_id = u.user_id 
            WHERE b.listing_id = ? 
            ORDER BY b.bid_amount DESC 
            LIMIT 10";
 $bids_stmt = $conn->prepare($bids_sql);
 $bids_stmt->bind_param("i", $listing_id);
 $bids_stmt->execute();
 $bids_result = $bids_stmt->get_result();

// Process bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $errors[] = "Anda harus login untuk melakukan tawaran";
    } else {
        $user_id = $_SESSION['user_id'];
        $bid_amount = $_POST['bid_amount'];
        
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
                
                // Refresh listing data
                $stmt->execute();
                $result = $stmt->get_result();
                $listing = $result->fetch_assoc();
                
                $success = "Tawaran Anda berhasil disimpan!";
                
                // Refresh bid history
                $bids_stmt->execute();
                $bids_result = $bids_stmt->get_result();
            } else {
                $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
            }
        }
    }
}
?>

<div class="item-details">
    <div class="item-header">
        <h1><?php echo htmlspecialchars($listing['title']); ?></h1>
        <div class="item-meta">
            <span class="category">Kategori: <?php echo htmlspecialchars($listing['category_name']); ?></span>
            <span class="seller">Penjual: <a href="#"><?php echo htmlspecialchars($listing['username']); ?></a></span>
            <span class="status">Status: <?php echo ucfirst($listing['status']); ?></span>
        </div>
    </div>
    
    <div class="item-content">
        <div class="item-images">
            <?php if ($images_result->num_rows > 0): ?>
                <div class="main-image">
                    <?php $main_image = $images_result->fetch_assoc(); ?>
                    <img src="<?php echo $main_image['image_url']; ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                </div>
                
                <?php if ($images_result->num_rows > 1): ?>
                    <div class="image-gallery">
                        <?php $images_result->data_seek(0); // Reset pointer ?>
                        <?php while ($image = $images_result->fetch_assoc()): ?>
                            <img src="<?php echo $image['image_url']; ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>" class="gallery-thumb">
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="main-image">
                    <img src="https://picsum.photos/seed/auction<?php echo $listing_id; ?>/600/400.jpg" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="item-info">
            <div class="item-description">
                <h2>Deskripsi</h2>
                <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
            </div>
            
            <div class="item-bidding">
                <h2>Informasi Lelang</h2>
                <div class="bid-info">
                    <div class="current-price">
                        <span>Harga Saat Ini:</span>
                        <span class="price"><?php echo formatPrice($listing['current_price']); ?></span>
                    </div>
                    
                    <div class="time-remaining">
                        <span>Sisa Waktu:</span>
                        <span><?php echo timeRemaining($listing['end_time']); ?></span>
                    </div>
                    
                    <div class="start-price">
                        <span>Harga Awal:</span>
                        <span><?php echo formatPrice($listing['start_price']); ?></span>
                    </div>
                </div>
                
                <?php if ($listing['status'] === 'aktif' && strtotime($listing['end_time']) > time()): ?>
                    <?php if (!isLoggedIn()): ?>
                        <div class="login-prompt">
                            <p>Anda harus <a href="login.php">login</a> untuk melakukan tawaran</p>
                        </div>
                    <?php elseif ($listing['user_id'] == $_SESSION['user_id']): ?>
                        <div class="owner-prompt">
                            <p>Ini adalah barang Anda. <a href="edit_listing.php?id=<?php echo $listing_id; ?>">Edit</a> atau <a href="cancel_listing.php?id=<?php echo $listing_id; ?>">Batalkan</a> lelang ini.</p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="error-messages">
                                <?php foreach ($errors as $error): ?>
                                    <?php echo showError($error); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="success-message">
                                <?php echo showSuccess($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="item_details.php?id=<?php echo $listing_id; ?>" method="post" class="bid-form">
                            <div class="form-group">
                                <label for="bid_amount">Jumlah Tawaran (Rp)</label>
                                <input type="number" id="bid_amount" name="bid_amount" min="<?php echo $listing['current_price'] + 1; ?>" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="place_bid" class="btn">Tawar</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="auction-ended">
                        <p>Lelang telah berakhir</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="bid-history">
        <h2>Riwayat Tawaran</h2>
        <?php if ($bids_result->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Penawar</th>
                        <th>Jumlah Tawaran</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($bid = $bids_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bid['username']); ?></td>
                            <td><?php echo formatPrice($bid['bid_amount']); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($bid['bid_time'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Belum ada tawaran untuk barang ini.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>