<?php
// login.php

require_once 'header.php';

 $errors = [];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username harus diisi";
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    }
    
    // Authenticate user if no validation errors
    if (empty($errors)) {
        $sql = "SELECT user_id, username, password_hash, full_name, is_admin, is_blocked FROM Users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is blocked
            if ($user['is_blocked']) {
                $errors[] = "Akun Anda telah diblokir. Silakan hubungi admin.";
            } elseif (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirect to dashboard
                redirect('dashboard.php');
            } else {
                $errors[] = "Username atau password salah";
            }
        } else {
            $errors[] = "Username atau password salah";
        }
    }
}
?>

<div class="form-container">
    <h2>Login ke Akun Anda</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <?php echo showError($error); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form action="login.php" method="post">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Login</button>
        </div>
        
        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </form>
</div>

<?php require_once 'footer.php'; ?>