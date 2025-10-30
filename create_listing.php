<?php
require_once 'config.php';

// Jika user tidak login, redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk membuat lelang";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

// Ambil data kategori
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Proses pembuatan barang lelang
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $start_price = floatval($_POST['start_price']);
    $duration = intval($_POST['duration']); // dalam hari
    
    // Validasi input
    if (empty($title) || empty($description) || empty($category_id) || empty($start_price) || empty($duration)) {
        $_SESSION['message'] = "Semua field harus diisi";
        $_SESSION['message_type'] = "danger";
    } elseif ($start_price <= 0) {
        $_SESSION['message'] = "Harga awal harus lebih dari 0";
        $_SESSION['message_type'] = "danger";
    } else {
        try {
            // Hitung waktu akhir lelang
            $end_time = date('Y-m-d H:i:s', strtotime("+$duration days"));
            
            // Insert barang baru
            $stmt = $pdo->prepare("INSERT INTO listings (user_id, category_id, title, description, start_price, current_price, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $category_id,
                $title,
                $description,
                $start_price,
                $start_price,
                $end_time
            ]);
            
            $listing_id = $pdo->lastInsertId();
            
            // Handle upload gambar
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
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
            
            $_SESSION['message'] = "Barang lelang berhasil dibuat!";
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
                <h4 class="mb-0">Buat Barang Lelang Baru</h4>
            </div>
            <div class="card-body">
                <form action="create_listing.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Nama Barang</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="start_price" class="form-label">Harga Awal (Rp)</label>
                        <input type="number" class="form-control" id="start_price" name="start_price" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Durasi Lelang</label>
                        <select class="form-select" id="duration" name="duration" required>
                            <option value="">Pilih Durasi</option>
                            <option value="3">3 Hari</option>
                            <option value="5">5 Hari</option>
                            <option value="7">7 Hari</option>
                            <option value="14">14 Hari</option>
                            <option value="30">30 Hari</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="images" class="form-label">Foto Barang</label>
                        <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                        <div class="form-text">Anda dapat mengunggah beberapa foto. Format yang diizinkan: JPG, JPEG, PNG, GIF</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Buat Lelang</button>
                    <a href="my_listings.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>