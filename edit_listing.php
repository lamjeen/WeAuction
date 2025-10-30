<?php
require_once 'config.php';

// Jika user tidak login, redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk mengedit barang";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

// Cek apakah ID barang ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID barang tidak valid";
    $_SESSION['message_type'] = "danger";
    redirect('my_listings.php');
}

 $listing_id = $_GET['id'];

// Ambil data barang
try {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
    $stmt->execute([$listing_id, $_SESSION['user_id']]);
    $listing = $stmt->fetch();
    
    if (!$listing) {
        $_SESSION['message'] = "Barang tidak ditemukan atau Anda tidak memiliki izin untuk mengeditnya";
        $_SESSION['message_type'] = "danger";
        redirect('my_listings.php');
    }
    
    // Cek apakah barang sudah memiliki penawaran
    $stmt = $pdo->prepare("SELECT COUNT(*) as bid_count FROM bids WHERE listing_id = ?");
    $stmt->execute([$listing_id]);
    $result = $stmt->fetch();
    $has_bids = $result['bid_count'] > 0;
    
    if ($has_bids) {
        $_SESSION['message'] = "Barang tidak dapat diedit karena sudah ada penawaran";
        $_SESSION['message_type'] = "danger";
        redirect('my_listings.php');
    }
    
    // Ambil data kategori
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Ambil gambar barang
    $stmt = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC");
    $stmt->execute([$listing_id]);
    $images = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('my_listings.php');
}

// Proses update barang
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $start_price = floatval($_POST['start_price']);
    
    // Validasi input
    if (empty($title) || empty($description) || empty($category_id) || empty($start_price)) {
        $_SESSION['message'] = "Semua field harus diisi";
        $_SESSION['message_type'] = "danger";
    } elseif ($start_price <= 0) {
        $_SESSION['message'] = "Harga awal harus lebih dari 0";
        $_SESSION['message_type'] = "danger";
    } else {
        try {
            // Update barang
            $stmt = $pdo->prepare("UPDATE listings SET title = ?, description = ?, category_id = ?, start_price = ?, current_price = ? WHERE id = ?");
            $stmt->execute([
                $title,
                $description,
                $category_id,
                $start_price,
                $start_price,
                $listing_id
            ]);
            
            // Handle upload gambar baru
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                // Hapus gambar lama
                foreach ($images as $image) {
                    if ($image['image_url'] && file_exists($image['image_url'])) {
                        unlink($image['image_url']);
                    }
                }
                
                // Hapus record gambar lama dari database
                $stmt = $pdo->prepare("DELETE FROM listing_images WHERE listing_id = ?");
                $stmt->execute([$listing_id]);
                
                // Upload gambar baru
                $is_primary = true;
                
                foreach ($_FILES['images']['name'] as $key => $name) {
                    if ($_FILES['images']['error'][$key] == 0) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $_FILES['images']['tmp_name'][$key],
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        
                        $image_url = uploadImage($file);
                        
                        if ($image_url) {
                            $stmt = $pdo->prepare("INSERT INTO listing_images (listing_id, image_url, is_primary) VALUES (?, ?, ?)");
                            $stmt->execute([$listing_id, $image_url, $is_primary]);
                            
                            // Hanya gambar pertama yang menjadi primary
                            $is_primary = false;
                        }
                    }
                }
            }
            
            $_SESSION['message'] = "Barang lelang berhasil diperbarui!";
            $_SESSION['message_type'] = "success";
            redirect('my_listings.php');
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    }
}
?>

<?php require_once 'header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit Barang Lelang</h4>
            </div>
            <div class="card-body">
                <form action="edit_listing.php?id=<?php echo $listing_id; ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Nama Barang</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($listing['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($listing['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="start_price" class="form-label">Harga Awal (Rp)</label>
                        <input type="number" class="form-control" id="start_price" name="start_price" step="0.01" min="0.01" value="<?php echo $listing['start_price']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="images" class="form-label">Ganti Foto Barang</label>
                        <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                        <div class="form-text">Kosongkan jika tidak ingin mengubah gambar. Format yang diizinkan: JPG, JPEG, PNG, GIF</div>
                        <?php if (!empty($images)): ?>
                            <div class="mt-2">
                                <p>Foto saat ini:</p>
                                <div class="d-flex flex-wrap">
                                    <?php foreach ($images as $image): ?>
                                        <img src="<?php echo $image['image_url']; ?>" alt="Current image" class="img-thumbnail me-2 mb-2" style="max-height: 100px;">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="my_listings.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>