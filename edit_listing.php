<?php
// edit_listing.php

require_once 'header.php';

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
 $errors = [];
 $success = '';

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

// If there are bids, editing is not allowed
if ($bid_count > 0) {
    redirect('dashboard.php');
}

// Get categories for dropdown
 $categories_sql = "SELECT * FROM Categories ORDER BY category_name";
 $categories_result = $conn->query($categories_sql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $start_price = $_POST['start_price'];
    
    // Validation
    if (empty($title)) {
        $errors[] = "Judul barang harus diisi";
    }
    
    if (empty($description)) {
        $errors[] = "Deskripsi harus diisi";
    }
    
    if (empty($category_id)) {
        $errors[] = "Kategori harus dipilih";
    }
    
    if (empty($start_price) || !is_numeric($start_price) || $start_price <= 0) {
        $errors[] = "Harga awal harus berupa angka positif";
    }
    
    // Update listing if no errors
    if (empty($errors)) {
        $update_sql = "UPDATE Listings SET title = ?, description = ?, category_id = ?, start_price = ?, current_price = ? WHERE listing_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssiddi", $title, $description, $category_id, $start_price, $start_price, $listing_id);
        
        if ($update_stmt->execute()) {
            $success = "Lelang berhasil diperbarui! <a href='item_details.php?id=$listing_id'>Lihat barang</a>";
            
            // Refresh listing data
            $stmt->execute();
            $result = $stmt->get_result();
            $listing = $result->fetch_assoc();
        } else {
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}
?>

<div class="form-container">
    <h2>Edit Lelang</h2>
    
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
    <?php else: ?>
        <form action="edit_listing.php?id=<?php echo $listing_id; ?>" method="post">
            <div class="form-group">
                <label for="title">Judul Barang</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php 
                    // Reset result pointer
                    $categories_result->data_seek(0);
                    while ($category = $categories_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo ($listing['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo $category['category_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($listing['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="start_price">Harga Awal (Rp)</label>
                <input type="number" id="start_price" name="start_price" min="0" step="0.01" value="<?php echo $listing['start_price']; ?>" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Simpan Perubahan</button>
                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>