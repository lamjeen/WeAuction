<?php
// dashboard.php

require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

 $user_id = $_SESSION['user_id'];

// Get user information
 $user_sql = "SELECT * FROM Users WHERE user_id = ?";
 $user_stmt = $conn->prepare($user_sql);
 $user_stmt->bind_param("i", $user_id);
 $user_stmt->execute();
 $user_result = $user_stmt->get_result();
 $user = $user_result->fetch_assoc();

// Get user's active listings
 $listings_sql = "SELECT l.*, c.category_name, 
                (SELECT COUNT(*) FROM Bids WHERE listing_id = l.listing_id) as bid_count
                FROM Listings l 
                JOIN Categories c ON l.category_id = c.category_id 
                WHERE l.user_id = ? 
                ORDER BY l.created_at DESC";
 $listings_stmt = $conn->prepare($listings_sql);
 $listings_stmt->bind_param("i", $user_id);
 $listings_stmt->execute();
 $listings_result = $listings_stmt->get_result();

// Get user's bids
 $bids_sql = "SELECT b.*, l.title, l.end_time, l.status 
            FROM Bids b 
            JOIN Listings l ON b.listing_id = l.listing_id 
            WHERE b.user_id = ? 
            ORDER BY b.bid_time DESC";
 $bids_stmt = $conn->prepare($bids_sql);
 $bids_stmt->bind_param("i", $user_id);
 $bids_stmt->execute();
 $bids_result = $bids_stmt->get_result();
?>

<div class="dashboard">
    <h1>Dashboard Pengguna</h1>
    
    <div class="dashboard-section">
        <h2>Profil Saya</h2>
        <div class="user-profile">
            <div class="profile-avatar">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?php echo $user['profile_image']; ?>" alt="<?php echo $user['full_name']; ?>">
                <?php else: ?>
                    <img src="https://picsum.photos/seed/user<?php echo $user_id; ?>/150/150.jpg" alt="<?php echo $user['full_name']; ?>">
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?php echo $user['full_name']; ?></h3>
                <p>Username: <?php echo $user['username']; ?></p>
                <p>Email: <?php echo $user['email']; ?></p>
                <?php if (!empty($user['bio'])): ?>
                    <p>Bio: <?php echo $user['bio']; ?></p>
                <?php endif; ?>
                <a href="edit_profile.php" class="btn">Edit Profil</a>
            </div>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Barang Saya</h2>
        <div class="user-listings">
            <?php if ($listings_result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Harga Saat Ini</th>
                            <th>Status</th>
                            <th>Tawaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($listing = $listings_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php
                                    // Get primary image for this listing
                                    $img_sql = "SELECT image_url FROM Listing_Images WHERE listing_id = ? LIMIT 1";
                                    $img_stmt = $conn->prepare($img_sql);
                                    $img_stmt->bind_param("i", $listing['listing_id']);
                                    $img_stmt->execute();
                                    $img_result = $img_stmt->get_result();
                                    $image_url = $img_result->num_rows > 0 ? $img_result->fetch_assoc()['image_url'] : 'https://picsum.photos/seed/auction' . $listing['listing_id'] . '/50/50.jpg';
                                    ?>
                                    <img src="<?php echo $image_url; ?>" alt="<?php echo $listing['title']; ?>" width="50">
                                </td>
                                <td><a href="item_details.php?id=<?php echo $listing['listing_id']; ?>"><?php echo $listing['title']; ?></a></td>
                                <td><?php echo $listing['category_name']; ?></td>
                                <td><?php echo formatPrice($listing['current_price']); ?></td>
                                <td><?php echo ucfirst($listing['status']); ?></td>
                                <td><?php echo $listing['bid_count']; ?></td>
                                <td>
                                    <?php if ($listing['status'] === 'aktif' && $listing['bid_count'] == 0): ?>
                                        <a href="edit_listing.php?id=<?php echo $listing['listing_id']; ?>" class="btn-small">Edit</a>
                                        <a href="cancel_listing.php?id=<?php echo $listing['listing_id']; ?>" class="btn-small btn-danger">Batalkan</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Anda belum memiliki barang lelang. <a href="new_listing.php">Buat lelang baru</a>.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Riwayat Tawaran Saya</h2>
        <div class="user-bids">
            <?php if ($bids_result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Jumlah Tawaran</th>
                            <th>Waktu Tawaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bid = $bids_result->fetch_assoc()): ?>
                            <tr>
                                <td><a href="item_details.php?id=<?php echo $bid['listing_id']; ?>"><?php echo $bid['title']; ?></a></td>
                                <td><?php echo formatPrice($bid['bid_amount']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($bid['bid_time'])); ?></td>
                                <td>
                                    <?php 
                                    if ($bid['status'] === 'aktif') {
                                        echo "Lelang Berlangsung";
                                    } elseif ($bid['status'] === 'selesai') {
                                        echo "Lelang Selesai";
                                    } else {
                                        echo "Lelang Dibatalkan";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Anda belum pernah melakukan tawaran.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>