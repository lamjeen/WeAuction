<?php
require_once 'config.php';

// Jika user tidak login, redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk melihat barang Anda";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

// Ambil barang milik user yang sedang login
try {
    $stmt = $pdo->prepare("SELECT l.*, c.name as category_name FROM listings l 
                          JOIN categories c ON l.category_id = c.id 
                          WHERE l.user_id = ? ORDER BY l.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $listings = $stmt->fetchAll();
    
    // Untuk setiap barang, cek apakah sudah ada penawaran
    foreach ($listings as &$listing) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as bid_count FROM bids WHERE listing_id = ?");
        $stmt->execute([$listing['id']]);
        $result = $stmt->fetch();
        $listing['has_bids'] = $result['bid_count'] > 0;
        
        // Ambil gambar utama
        $stmt = $pdo->prepare("SELECT image_url FROM listing_images WHERE listing_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->execute([$listing['id']]);
        $primary_image = $stmt->fetch();
        $listing['primary_image'] = $primary_image ? $primary_image['image_url'] : null;
    }
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}
?>

<?php require_once 'header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="display-4">Barang Saya</h1>
        <p class="lead">Kelola barang yang Anda lelang</p>
        <a href="create_listing.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Tambah Barang Baru
        </a>
    </div>
</div>

<div class="row">
    <?php if (empty($listings)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Anda belum memiliki barang lelang.
                <a href="create_listing.php" class="alert-link">Buat barang lelang sekarang</a>.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($listings as $listing): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($listing['primary_image']): ?>
                        <img src="<?php echo $listing['primary_image']; ?>" class="card-img-top item-image" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                    <?php else: ?>
                        <img src="https://picsum.photos/seed/<?php echo $listing['id']; ?>/400/200.jpg" class="card-img-top item-image" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                        <p class="card-text"><?php echo substr(htmlspecialchars($listing['description']), 0, 100) . '...'; ?></p>
                        <div class="mt-auto">
                            <p class="card-text">
                                <small class="text-muted">Kategori: <?php echo htmlspecialchars($listing['category_name']); ?></small>
                            </p>
                            <p class="card-text">
                                <strong>Harga Awal:</strong> <?php echo formatPrice($listing['start_price']); ?>
                            </p>
                            <p class="card-text">
                                <strong>Harga Saat Ini:</strong> 
                                <span class="text-success"><?php echo formatPrice($listing['current_price']); ?></span>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <?php
                                    $statusClass = '';
                                    switch($listing['status']) {
                                        case 'aktif':
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'selesai':
                                            $statusClass = 'bg-secondary';
                                            break;
                                        case 'dibatalkan':
                                            $statusClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($listing['status']); ?></span>
                                </small>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <?php if (strtotime($listing['end_time']) > time()): ?>
                                        <span class="countdown" id="countdown-<?php echo $listing['id']; ?>">
                                            <?php echo timeRemaining($listing['end_time']); ?>
                                        </span>
                                    <?php else: ?>
                                        Lelang telah berakhir
                                    <?php endif; ?>
                                </small>
                            </p>
                            <div class="btn-group w-100" role="group">
                                <a href="listing_details.php?id=<?php echo $listing['id']; ?>" class="btn btn-outline-primary">Lihat Detail</a>
                                <?php if (!$listing['has_bids'] && $listing['status'] == 'aktif'): ?>
                                    <a href="edit_listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-outline-secondary">Edit</a>
                                    <a href="cancel_listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan lelang ini?')">Batalkan</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                updateCountdown('countdown-<?php echo $listing['id']; ?>', '<?php echo $listing['end_time']; ?>');
            </script>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>