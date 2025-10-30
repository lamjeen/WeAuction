<?php
// search.php

require_once 'header.php';

// Get search parameters
 $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
 $category_id = isset($_GET['category']) ? $_GET['category'] : '';
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $limit = 12;
 $offset = ($page - 1) * $limit;

// Get categories for filter
 $categories_sql = "SELECT * FROM Categories ORDER BY category_name";
 $categories_result = $conn->query($categories_sql);

// Build query
 $where_conditions = ["l.status = 'aktif'"];
 $params = [];
 $types = "";

if (!empty($keyword)) {
    $where_conditions[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $keyword_param = "%$keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= "ss";
}

if (!empty($category_id)) {
    $where_conditions[] = "l.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

 $where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total listings count
 $count_sql = "SELECT COUNT(*) as total FROM Listings l $where_clause";
 $count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
 $count_stmt->execute();
 $count_result = $count_stmt->get_result();
 $total_listings = $count_result->fetch_assoc()['total'];
 $total_pages = ceil($total_listings / $limit);

// Get listings
 $sql = "SELECT l.*, c.category_name, u.username 
        FROM Listings l 
        JOIN Categories c ON l.category_id = c.category_id 
        JOIN Users u ON l.user_id = u.user_id 
        $where_clause 
        ORDER BY l.end_time ASC 
        LIMIT ? OFFSET ?";
 $stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types . "ii", ...$params, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
 $stmt->execute();
 $result = $stmt->get_result();
?>

<div class="search-page">
    <h1>Cari Barang Lelang</h1>
    
    <div class="search-form">
        <form action="search.php" method="get">
            <div class="form-group">
                <input type="text" name="keyword" placeholder="Cari berdasarkan judul atau deskripsi" value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            
            <div class="form-group">
                <select name="category">
                    <option value="">Semua Kategori</option>
                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_id == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo $category['category_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Cari</button>
            </div>
        </form>
    </div>
    
    <div class="search-results">
        <h2>Hasil Pencarian</h2>
        <p>Ditemukan <?php echo $total_listings; ?> barang</p>
        
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
                <p>Tidak ada barang yang ditemukan dengan kriteria pencarian Anda.</p>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category_id; ?>&page=<?php echo $page - 1; ?>" class="btn">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current-page"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category_id; ?>&page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category_id; ?>&page=<?php echo $page + 1; ?>" class="btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>