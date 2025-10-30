<?php
require_once 'config.php';

// Jika user sudah login, redirect ke halaman utama
if (isLoggedIn()) {
    redirect('index.php');
}

// Proses registrasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $_SESSION['message'] = "Semua field harus diisi";
        $_SESSION['message_type'] = "danger";
    } elseif ($password != $confirm_password) {
        $_SESSION['message'] = "Password tidak cocok";
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
                $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password_hash) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $full_name, $password_hash]);
                
                $_SESSION['message'] = "Registrasi berhasil! Silakan login.";
                $_SESSION['message_type'] = "success";
                redirect('login.php');
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
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Registrasi Akun Baru</h4>
            </div>
            <div class="card-body">
                <form action="register.php" method="post">
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
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Daftar</button>
                </form>
                <div class="mt-3 text-center">
                    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>