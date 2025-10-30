<?php
// index.php

require_once 'header.php';

// Get active listings
 $sql = "SELECT l.*, c.category_name, u.username 
        FROM Listings l 
        JOIN Categories c ON l.category_id = c.category_id 
        JOIN Users u ON l.user_id = u.user_id 
        WHERE l.status = 'aktif' 
        ORDER BY l.end_time ASC 
        LIMIT 12";
 $result = $conn->query($sql);
?>

<div class="hero">
    <h2>Selamat Datang di AuctionIndo</h2>
    <p>Platform lelang online terpercaya di Indonesia</p>
</div>

<section class="featured-listings">
    <h2>Lelang Sedang Berlangsung</h2>
    <div class="listings-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="listing-card">
                    <?php
                    // Get primary image for this listing
                    $img_sql = "SELECT image_url FROM Listing_Images WHERE listing_id = ? LIMIT 1";
                    $img_stmt = $conn->prepare($img_sql);
                    $img_stmt->bind_param("i", $row['listing_id']);
                    $img_stmt->execute();
                    $img_result = $img_stmt->get_result();
                    $image_url = $img_result->num_rows > 0 ? $img_result->fetch_assoc()['image_url'] : 'https://picsum.photos/seed/auction' . $row['listing_id'] . '/300/200.jpg';
                    ?>
                    <div class="listing-image">
                        <img src="<?php echo $image_url; ?>" alt="<?php echo $row['title']; ?>">
                    </div>
                    <div class="listing-info">
                        <h3><a href="item_details.php?id=<?php echo $row['listing_id']; ?>"><?php echo $row['title']; ?></a></h3>
                        <p class="listing-category"><?php echo $row['category_name']; ?></p>
                        <p class="listing-price">Harga Saat Ini: <?php echo formatPrice($row['current_price']); ?></p>
                        <p class="listing-time"><?php echo timeRemaining($row['end_time']); ?></p>
                        <p class="listing-seller">Penjual: <?php echo $row['username']; ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Tidak ada lelang yang sedang berlangsung saat ini.</p>
        <?php endif; ?>
    </div>
    <div class="view-all">
        <a href="search.php" class="btn">Lihat Semua Barang</a>
    </div>
</section>

<?php require_once 'footer.php'; ?>