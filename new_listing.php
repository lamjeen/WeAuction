<?php
// new_listing.php

require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

 $errors = [];
 $success = '';

// Get categories for dropdown
 $categories_sql = "SELECT * FROM Categories ORDER BY category_name";
 $categories_result = $conn->query($categories_sql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $start_price = $_POST['start_price'];
    $duration = $_POST['duration']; // in days
    
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
    
    // Handle image uploads
    $image_urls = [];
    
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $file_count = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_name = $_FILES['images']['name'][$i];
                $file_size = $_FILES['images']['size'][$i];
                $file_type = $_FILES['images']['type'][$i];
                
                // Check file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Hanya file gambar (JPEG, PNG, GIF) yang diizinkan";
                    continue;
                }
                
                // Check file size (max 2MB)
                if ($file_size > 2097152) {
                    $errors[] = "Ukuran file maksimal 2MB";
                    continue;
                }
                
                // If no errors, upload file
                if (empty($errors)) {
                    $upload_dir = 'uploads/listings/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_filename = 'listing_' . time() . '_' . $i . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $image_urls[] = $upload_path;
                    } else {
                        $errors[] = "Gagal mengupload gambar " . ($i + 1);
                    }
                }
            }
        }
    }
    
    // Create listing if no errors
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        $start_time = date('Y-m-d H:i:s');
        $end_time = date('Y-m-d H:i:s', strtotime("+$duration days", strtotime($start_time)));
        
        // Insert listing
        $insert_sql = "INSERT INTO Listings (user_id, category_id, title, description, start_price, current_price, start_time, end_time) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iissddss", $user_id, $category_id, $title, $description, $start_price, $start_price, $start_time, $end_time);
        
        if ($insert_stmt->execute()) {
            $listing_id = $conn->insert_id;
            
            // Insert images if any
            if (!empty($image_urls)) {
                foreach ($image_urls as $image_url) {
                    $img_sql = "INSERT INTO Listing_Images (listing_id, image_url) VALUES (?, ?)";
                    $img_stmt = $conn->prepare($img_sql);
                    $img_stmt->bind_param("is", $listing_id, $image_url);
                    $img_stmt->execute();
                }
            }
            
            $success = "Lelang berhasil dibuat! <a href='item_details.php?id=$listing_id'>Lihat barang</a>";
            
            // Reset form
            $_POST = [];
        } else {
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}
?>

<div class="form-container">
    <h2>Buat Lelang Baru</h2>
    
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
        <form action="new_listing.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Judul Barang</label>
                <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo $category['category_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" rows="6" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="start_price">Harga Awal (Rp)</label>
                <input type="number" id="start_price" name="start_price" min="0" step="0.01" value="<?php echo isset($_POST['start_price']) ? htmlspecialchars($_POST['start_price']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="duration">Durasi Lelang</label>
                <select id="duration" name="duration" required>
                    <option value="3" <?php echo (isset($_POST['duration']) && $_POST['duration'] == 3) ? 'selected' : ''; ?>>3 Hari</option>
                    <option value="5" <?php echo (isset($_POST['duration']) && $_POST['duration'] == 5) ? 'selected' : ''; ?>>5 Hari</option>
                    <option value="7" <?php echo (isset($_POST['duration']) && $_POST['duration'] == 7) ? 'selected' : ''; ?>>7 Hari</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="images">Foto Barang</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*">
                <small>Anda dapat mengupload beberapa foto. Maksimal 2MB per foto. Format: JPEG, PNG, GIF</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Buat Lelang</button>
                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>