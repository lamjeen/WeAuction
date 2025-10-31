<?php
require_once 'config.php';

// Jika user tidak login atau bukan admin, redirect ke halaman utama
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Anda tidak memiliki izin untuk mengakses halaman ini";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Proses penambahan pengguna
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password']; // Tidak ada konfirmasi password
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $_SESSION['message'] = "Semua field harus diisi";
        $_SESSION['message_type'] = "danger";
    } elseif (strlen($password) < 6) {
        $_SESSION['message'] = "Password minimal 6 karakter";
        $_SESSION['message_type'] = "danger";
    } else {
        try {
            // Cek apakah username atau email sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['message'] = "Username atau email sudah digunakan";
                $_SESSION['message_type'] = "danger";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user baru
                $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password_hash, is_admin) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $full_name, $password_hash, $is_admin]);
                
                $_SESSION['message'] = "Pengguna baru berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
                redirect('admin_dashboard.php#users');
            }
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
                <h4 class="mb-0">Tambah Pengguna Baru</h4>
            </div>
            <div class="card-body">
                <form action="add_user_admin.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password minimal 6 karakter</div>
                    </div>
                    <!-- Field Konfirmasi Password Dihapus -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                        <label class="form-check-label" for="is_admin">
                            Set sebagai Admin
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah Pengguna</button>
                    <a href="admin_dashboard.php#users" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>