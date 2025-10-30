<?php
require_once 'config.php';

// Ambil data kategori untuk filter
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Query untuk mendapatkan semua barang yang sedang dilelang
try {
    $query = "SELECT l.*, u.username, c.name as category_name FROM listings l 
              JOIN users u ON l.user_id = u.id 
              JOIN categories c ON l.category_id = c.id 
              WHERE l.status = 'aktif' AND l.end_time > NOW()";
    
    // Filter berdasarkan kategori jika dipilih
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $query .= " AND l.category_id = " . intval($_GET['category']);
    }
    
    // Pencarian berdasarkan kata kunci
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $query .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    }
    
    $query .= " ORDER BY l.end_time ASC";
    
    $stmt = $pdo->prepare($query);
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $stmt->execute([$searchTerm, $searchTerm]);
    } else {
        $stmt->execute();
    }
    
    $listings = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}
?>

<?php require_once 'header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="display-4">Barang Lelang Aktif</h1>
        <p class="lead">Temukan barang impian Anda dengan harga terbaik</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col">
        <form method="GET" action="index.php" class="d-flex">
            <input class="form-control me-2" type="search" name="search" placeholder="Cari barang..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <select class="form-select me-2" name="category" style="max-width: 200px;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary" type="submit">Cari</button>
        </form>
    </div>
</div>

<div class="row">
    <?php if (empty($listings)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Tidak ada barang lelang yang aktif saat ini.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($listings as $listing): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php
                    // Ambil gambar utama
                    $stmt = $pdo->prepare("SELECT image_url FROM listing_images WHERE listing_id = ? AND is_primary = 1 LIMIT 1");
                    $stmt->execute([$listing['id']]);
                    $primary_image = $stmt->fetch();
                    
                    if ($primary_image): ?>
                        <img src="<?php echo $primary_image['image_url']; ?>" class="card-img-top item-image" alt="<?php echo htmlspecialchars($listing['title']); ?>">
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
                                <small class="text-muted">Penjual: <?php echo htmlspecialchars($listing['username']); ?></small>
                            </p>
                            <p class="card-text">
                                <strong>Harga Saat Ini:</strong> 
                                <span class="text-success"><?php echo formatPrice($listing['current_price']); ?></span>
                            </p>
                            <p class="card-text">
                                <small class="text-muted countdown" id="countdown-<?php echo $listing['id']; ?>">
                                    <?php echo timeRemaining($listing['end_time']); ?>
                                </small>
                            </p>
                            <a href="listing_details.php?id=<?php echo $listing['id']; ?>" class="btn btn-primary">Lihat Detail</a>
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