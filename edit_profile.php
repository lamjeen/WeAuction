<?php
// edit_profile.php

require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

 $user_id = $_SESSION['user_id'];
 $errors = [];
 $success = '';

// Get user information
 $sql = "SELECT * FROM Users WHERE user_id = ?";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("i", $user_id);
 $stmt->execute();
 $result = $stmt->get_result();
 $user = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    
    // Validation
    if (empty($full_name)) {
        $errors[] = "Nama lengkap harus diisi";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    // Check if email already exists (but not the current user's email)
    if (empty($errors) && $email !== $user['email']) {
        $check_sql = "SELECT user_id FROM Users WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = "Email sudah digunakan";
        }
    }
    
    // Handle profile image upload
    $profile_image = $user['profile_image']; // Default to current image
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_type = $_FILES['profile_image']['type'];
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Hanya file gambar (JPEG, PNG, GIF) yang diizinkan";
        }
        
        // Check file size (max 2MB)
        if ($file_size > 2097152) {
            $errors[] = "Ukuran file maksimal 2MB";
        }
        
        // If no errors, upload file
        if (empty($errors)) {
            $upload_dir = 'uploads/profiles/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_image = $upload_path;
            } else {
                $errors[] = "Gagal mengupload gambar";
            }
        }
    }
    
    // Update user profile if no errors
    if (empty($errors)) {
        $update_sql = "UPDATE Users SET full_name = ?, email = ?, bio = ?, profile_image = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $full_name, $email, $bio, $profile_image, $user_id);
        
        if ($update_stmt->execute()) {
            // Update session variables
            $_SESSION['full_name'] = $full_name;
            
            $success = "Profil berhasil diperbarui!";
            
            // Refresh user data
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}
?>

<div class="form-container">
    <h2>Edit Profil</h2>
    
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
    <?php endif; ?>
    
    <form action="edit_profile.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="full_name">Nama Lengkap</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="profile_image">Foto Profil</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">
            <small>Maksimal 2MB. Format: JPEG, PNG, GIF</small>
            <?php if (!empty($user['profile_image'])): ?>
                <div class="current-image">
                    <p>Foto profil saat ini:</p>
                    <img src="<?php echo $user['profile_image']; ?>" alt="Foto Profil" width="150">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Simpan Perubahan</button>
            <a href="dashboard.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>