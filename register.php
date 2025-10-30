<?php
// register.php

require_once 'header.php';

 $errors = [];
 $success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username harus diisi";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username minimal 4 karakter";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }
    
    if (empty($full_name)) {
        $errors[] = "Nama lengkap harus diisi";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $check_sql = "SELECT user_id FROM Users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $user = $check_result->fetch_assoc();
            if ($user['username'] === $username) {
                $errors[] = "Username sudah digunakan";
            } else {
                $errors[] = "Email sudah digunakan";
            }
        }
    }
    
    // Register user if no errors
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_sql = "INSERT INTO Users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $username, $email, $password_hash, $full_name);
        
        if ($insert_stmt->execute()) {
            $success = "Registrasi berhasil! Silakan <a href='login.php'>login</a> untuk melanjutkan.";
        } else {
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}
?>

<div class="form-container">
    <h2>Daftar Akun Baru</h2>
    
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
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="full_name">Nama Lengkap</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Daftar</button>
            </div>
            
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>