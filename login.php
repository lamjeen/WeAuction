<?php
require_once 'config.php';

// Jika user sudah login, redirect ke halaman utama
if (isLoggedIn()) {
    redirect('index.php');
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $_SESSION['message'] = "Username dan password harus diisi";
        $_SESSION['message_type'] = "danger";
    } else {
        try {
            // Cari user berdasarkan username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                
                // Verifikasi password
                if (password_verify($password, $user['password_hash'])) {
                    // Cek apakah user diblokir
                    if ($user['is_blocked']) {
                        $_SESSION['message'] = "Akun Anda telah diblokir. Silakan hubungi admin.";
                        $_SESSION['message_type'] = "danger";
                    } else {
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        $_SESSION['is_blocked'] = $user['is_blocked'];
                        
                        $_SESSION['message'] = "Login berhasil! Selamat datang, " . $user['username'];
                        $_SESSION['message_type'] = "success";
                        redirect('index.php');
                    }
                } else {
                    $_SESSION['message'] = "Password salah";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Username tidak ditemukan";
                $_SESSION['message_type'] = "danger";
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
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <div class="mt-3 text-center">
                    <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>