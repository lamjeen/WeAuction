<?php
require_once 'config.php';

// Jika user tidak login atau bukan admin, redirect ke halaman utama
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Anda tidak memiliki izin untuk mengakses halaman ini";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Cek apakah ID pengguna ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID pengguna tidak valid";
    $_SESSION['message_type'] = "danger";
    redirect('admin_dashboard.php#users');
}

 $user_id = $_GET['id'];

// Ambil data pengguna
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['message'] = "Pengguna tidak ditemukan";
        $_SESSION['message_type'] = "danger";
        redirect('admin_dashboard.php#users');
    }
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    redirect('admin_dashboard.php#users');
}

// Proses update profil (satu form untuk semua)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    $new_password = trim($_POST['new_password']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Membangun query secara dinamis
    $sql = "UPDATE users SET full_name = ?, email = ?, bio = ?, is_admin = ?";
    $params = [$full_name, $email, $bio, $is_admin];

    // Jika password baru diisi, tambahkan ke query
    if (!empty($new_password)) {
        $sql .= ", password_hash = ?";
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        // Sisipkan hash password ke dalam array parameter
        array_splice($params, 3, 0, $password_hash);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Jika admin mengedit profilnya sendiri, update session
        if ($user['id'] == $_SESSION['user_id']) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['is_admin'] = $is_admin;
        }

        $_SESSION['message'] = "Profil pengguna berhasil diperbarui!";
        $_SESSION['message_type'] = "success";
        redirect("edit_user_admin.php?id=$user_id");

    } catch(PDOException $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}
?>

<?php require_once 'header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit Pengguna: <?php echo htmlspecialchars($user['username']); ?></h4>
            </div>
            <div class="card-body">
                <!-- SATU FORM UNTUK SEMUA PERUBAHAN -->
                <form action="edit_user_admin.php?id=<?php echo $user_id; ?>" method="post">
                    <h5 class="mb-3">Informasi Profil</h5>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>

                    <hr>

                    <h5 class="mb-3">Keamanan & Role</h5>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_admin">
                            Berikan hak akses Admin
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="admin_dashboard.php#users" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>