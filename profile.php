<?php
require_once 'config.php';

// Jika user tidak login, redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk mengakses halaman profil";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

// Ambil data user
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['message'] = "Pengguna tidak ditemukan";
        $_SESSION['message_type'] = "danger";
        redirect('index.php');
    }
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Proses update profil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);
    
    try {
        // Handle upload foto profil
        $profile_image = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $new_image = uploadImage($_FILES['profile_image']);
            if ($new_image) {
                $profile_image = $new_image;
                
                // Hapus foto lama jika ada
                if ($user['profile_image'] && file_exists($user['profile_image'])) {
                    unlink($user['profile_image']);
                }
            }
        }
        
        // Update profil
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, profile_image = ? WHERE id = ?");
        $stmt->execute([$full_name, $bio, $profile_image, $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['full_name'] = $full_name;
        
        $_SESSION['message'] = "Profil berhasil diperbarui!";
        $_SESSION['message_type'] = "success";
        redirect('profile.php');
    } catch(PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}
?>

<?php require_once 'header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($user['profile_image']): ?>
                    <img src="<?php echo $user['profile_image']; ?>" class="profile-image mb-3" alt="Profile Image">
                <?php else: ?>
                    <img src="https://picsum.photos/seed/<?php echo $user['id']; ?>/150/150.jpg" class="profile-image mb-3" alt="Profile Image">
                <?php endif; ?>
                <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                <p class="small"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="small">Bergabung sejak <?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit Profil</h4>
            </div>
            <div class="card-body">
                <form action="profile.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Foto Profil</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        <div class="form-text">Format yang diizinkan: JPG, JPEG, PNG, GIF</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="user_dashboard.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>

        <!-- FITUR BARU: HAPUS AKUN -->
        <div class="card mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Hapus Akun Saya</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Menghapus akun Anda akan menghapus semua data Anda secara permanen, termasuk profil, riwayat penawaran, dan semua barang lelang yang Anda miliki. Tindakan ini tidak dapat dibatalkan.</p>
                <a href="delete_account.php" 
                   class="btn btn-danger" 
                   onclick="return confirm('Apakah Anda yakin ingin menghapus akun Anda secara permanen? Semua data Anda akan hilang selamanya.');">
                    <i class="fas fa-trash-alt me-2"></i>Hapus Akun Saya
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>