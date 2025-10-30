<?php
require_once 'config.php';

// Cek apakah ID barang ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID barang tidak valid";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

 $listing_id = $_GET['id'];

// Ambil data barang
try {
    $stmt = $pdo->prepare("SELECT l.*, u.username, c.name as category_name FROM listings l 
                          JOIN users u ON l.user_id = u.id 
                          JOIN categories c ON l.category_id = c.id 
                          WHERE l.id = ?");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();
    
    if (!$listing) {
        $_SESSION['message'] = "Barang tidak ditemukan";
        $_SESSION['message_type'] = "danger";
        redirect('index.php');
    }
    
    // Ambil semua gambar barang
    $stmt = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC");
    $stmt->execute([$listing_id]);
    $images = $stmt->fetchAll();
    
    // Ambil riwayat penawaran
    $stmt = $pdo->prepare("SELECT b.*, u.username FROM bids b JOIN users u ON b.user_id = u.id WHERE b.listing_id = ? ORDER BY b.bid_amount DESC");
    $stmt->execute([$listing_id]);
    $bids = $stmt->fetchAll();
    
    // Cek apakah barang sudah memiliki penawaran
    $has_bids = count($bids) > 0;
    
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Proses penawaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_bid'])) {
    if (!isLoggedIn()) {
        $_SESSION['message'] = "Anda harus login untuk melakukan penawaran";
        $_SESSION['message_type'] = "warning";
        redirect('login.php');
    }
    
    if ($_SESSION['user_id'] == $listing['user_id']) {
        $_SESSION['message'] = "Anda tidak bisa menawar barang sendiri";
        $_SESSION['message_type'] = "danger";
        redirect("listing_details.php?id=$listing_id");
    }
    
    if ($listing['status'] != 'aktif') {
        $_SESSION['message'] = "Lelang ini tidak aktif";
        $_SESSION['message_type'] = "danger";
        redirect("listing_details.php?id=$listing_id");
    }
    
    if (strtotime($listing['end_time']) <= time()) {
        $_SESSION['message'] = "Lelang telah berakhir";
        $_SESSION['message_type'] = "danger";
        redirect("listing_details.php?id=$listing_id");
    }
    
    $bid_amount = floatval($_POST['bid_amount']);
    
    if ($bid_amount <= $listing['current_price']) {
        $_SESSION['message'] = "Penawaran harus lebih tinggi dari harga saat ini";
        $_SESSION['message_type'] = "danger";
    } else {
        try {
            // Insert penawaran baru
            $stmt = $pdo->prepare("INSERT INTO bids (listing_id, user_id, bid_amount) VALUES (?, ?, ?)");
            $stmt->execute([$listing_id, $_SESSION['user_id'], $bid_amount]);
            
            // Update harga saat ini
            $stmt = $pdo->prepare("UPDATE listings SET current_price = ? WHERE id = ?");
            $stmt->execute([$bid_amount, $listing_id]);
            
            $_SESSION['message'] = "Penawaran berhasil!";
            $_SESSION['message_type'] = "success";
            redirect("listing_details.php?id=$listing_id");
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    }
}
?>

<?php require_once 'header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><?php echo htmlspecialchars($listing['title']); ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <!-- Gambar Utama -->
                        <?php if (!empty($images)): ?>
                            <img id="mainImage" src="<?php echo $images[0]['image_url']; ?>" class="img-fluid rounded mb-3" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                            
                            <!-- Galeri Gambar -->
                            <div class="d-flex flex-wrap">
                                <?php foreach ($images as $index => $image): ?>
                                    <img src="<?php echo $image['image_url']; ?>" 
                                         class="gallery-image me-2 mb-2 <?php echo $index == 0 ? 'active' : ''; ?>" 
                                         alt="Gallery Image"
                                         onclick="changeMainImage('<?php echo $image['image_url']; ?>')">
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <img id="mainImage" src="https://picsum.photos/seed/<?php echo $listing['id']; ?>/600/400.jpg" class="img-fluid rounded" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5>Deskripsi</h5>
                        <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                        
                        <hr>
                        
                        <h5>Informasi Lelang</h5>
                        <p><strong>Kategori:</strong> <?php echo htmlspecialchars($listing['category_name']); ?></p>
                        <p><strong>Penjual:</strong> <?php echo htmlspecialchars($listing['username']); ?></p>
                        <p><strong>Harga Awal:</strong> <?php echo formatPrice($listing['start_price']); ?></p>
                        <p><strong>Harga Saat Ini:</strong> <span class="text-success"><?php echo formatPrice($listing['current_price']); ?></span></p>
                        <p><strong>Waktu Akhir Lelang:</strong> <?php echo formatTime($listing['end_time']); ?></p>
                        <p><strong>Waktu Tersisa:</strong> <span class="countdown" id="countdown-<?php echo $listing['id']; ?>"><?php echo timeRemaining($listing['end_time']); ?></span></p>
                        <p><strong>Status:</strong> 
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
                        </p>
                        
                        <?php if (isLoggedIn() && $_SESSION['user_id'] != $listing['user_id'] && $listing['status'] == 'aktif' && strtotime($listing['end_time']) > time()): ?>
                            <hr>
                            <h5>Form Penawaran</h5>
                            <form action="listing_details.php?id=<?php echo $listing_id; ?>" method="post">
                                <div class="mb-3">
                                    <label for="bid_amount" class="form-label">Jumlah Penawaran (Rp)</label>
                                    <input type="number" class="form-control" id="bid_amount" name="bid_amount" step="0.01" min="<?php echo $listing['current_price'] + 0.01; ?>" required>
                                    <div class="form-text">Minimal: <?php echo formatPrice($listing['current_price'] + 0.01); ?></div>
                                </div>
                                <button type="submit" name="place_bid" class="btn btn-primary">Ajukan Penawaran</button>
                            </form>
                        <?php elseif (isLoggedIn() && $_SESSION['user_id'] == $listing['user_id']): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Ini adalah barang Anda.
                                <?php if (!$has_bids && $listing['status'] == 'aktif'): ?>
                                    <br>Anda dapat <a href="edit_listing.php?id=<?php echo $listing_id; ?>">mengedit</a> atau <a href="cancel_listing.php?id=<?php echo $listing_id; ?>">membatalkan</a> barang ini.
                                <?php endif; ?>
                            </div>
                        <?php elseif (!isLoggedIn()): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Anda harus <a href="login.php">login</a> untuk melakukan penawaran.
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($listing['status'] != 'aktif' || strtotime($listing['end_time']) <= time()): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-clock me-2"></i>Lelang telah berakhir atau tidak aktif.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Riwayat Penawaran</h5>
            </div>
            <div class="card-body">
                <?php if (empty($bids)): ?>
                    <p class="text-muted">Belum ada penawaran</p>
                <?php else: ?>
                    <div class="bid-history">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Penawar</th>
                                    <th>Jumlah</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bids as $bid): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($bid['username']); ?></td>
                                        <td><?php echo formatPrice($bid['bid_amount']); ?></td>
                                        <td><?php echo date('d M H:i', strtotime($bid['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    updateCountdown('countdown-<?php echo $listing['id']; ?>', '<?php echo $listing['end_time']; ?>');
</script>

<?php require_once 'footer.php'; ?>