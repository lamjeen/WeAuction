<?php
// admin_panel.php

require_once 'header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

 $errors = [];
 $success = '';

// Handle user blocking/unblocking
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $action = $_GET['action'];
    $user_id = $_GET['user_id'];
    
    if ($action === 'block') {
        $update_sql = "UPDATE Users SET is_blocked = 1 WHERE user_id = ?";
        $message = "Pengguna berhasil diblokir";
    } elseif ($action === 'unblock') {
        $update_sql = "UPDATE Users SET is_blocked = 0 WHERE user_id = ?";
        $message = "Pengguna berhasil dibuka blokirnya";
    } else {
        redirect('admin_panel.php');
    }
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
    }
    
    redirect('admin_panel.php');
}

// Handle listing deletion
if (isset($_GET['delete_listing']) && !empty($_GET['delete_listing'])) {
    $listing_id = $_GET['delete_listing'];
    
    // First, delete all bids for this listing
    $delete_bids_sql = "DELETE FROM Bids WHERE listing_id = ?";
    $delete_bids_stmt = $conn->prepare($delete_bids_sql);
    $delete_bids_stmt->bind_param("i", $listing_id);
    $delete_bids_stmt->execute();
    
    // Then, delete all images for this listing
    $delete_images_sql = "DELETE FROM Listing_Images WHERE listing_id = ?";
    $delete_images_stmt = $conn->prepare($delete_images_sql);
    $delete_images_stmt->bind_param("i", $listing_id);
    $delete_images_stmt->execute();
    
    // Finally, delete the listing
    $delete_listing_sql = "DELETE FROM Listings WHERE listing_id = ?";
    $delete_listing_stmt = $conn->prepare($delete_listing_sql);
    $delete_listing_stmt->bind_param("i", $listing_id);
    
    if ($delete_listing_stmt->execute()) {
        $_SESSION['success'] = "Lelang berhasil dihapus";
    } else {
        $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
    }
    
    redirect('admin_panel.php');
}

// Get all users
 $users_sql = "SELECT * FROM Users ORDER BY created_at DESC";
 $users_result = $conn->query($users_sql);

// Get all listings
 $listings_sql = "SELECT l.*, c.category_name, u.username 
                FROM Listings l 
                JOIN Categories c ON l.category_id = c.category_id 
                JOIN Users u ON l.user_id = u.user_id 
                ORDER BY l.created_at DESC";
 $listings_result = $conn->query($listings_sql);

// Get statistics
 $total_users_sql = "SELECT COUNT(*) as count FROM Users";
 $total_users_result = $conn->query($total_users_sql);
 $total_users = $total_users_result->fetch_assoc()['count'];

 $total_listings_sql = "SELECT COUNT(*) as count FROM Listings";
 $total_listings_result = $conn->query($total_listings_sql);
 $total_listings = $total_listings_result->fetch_assoc()['count'];

 $active_listings_sql = "SELECT COUNT(*) as count FROM Listings WHERE status = 'aktif'";
 $active_listings_result = $conn->query($active_listings_sql);
 $active_listings = $active_listings_result->fetch_assoc()['count'];

 $total_bids_sql = "SELECT COUNT(*) as count FROM Bids";
 $total_bids_result = $conn->query($total_bids_sql);
 $total_bids = $total_bids_result->fetch_assoc()['count'];
?>

<div class="admin-panel">
    <h1>Admin Panel</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-messages">
            <?php echo showError($_SESSION['error']); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            <?php echo showSuccess($_SESSION['success']); ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <div class="admin-stats">
        <h2>Statistik</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Pengguna</h3>
                <p><?php echo $total_users; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Lelang</h3>
                <p><?php echo $total_listings; ?></p>
            </div>
            <div class="stat-card">
                <h3>Lelang Aktif</h3>
                <p><?php echo $active_listings; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Tawaran</h3>
                <p><?php echo $total_bids; ?></p>
            </div>
        </div>
    </div>
    
    <div class="admin-section">
        <h2>Manajemen Pengguna</h2>
        <div class="admin-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="status-badge admin">Admin</span>
                                <?php elseif ($user['is_blocked']): ?>
                                    <span class="status-badge blocked">Diblokir</span>
                                <?php else: ?>
                                    <span class="status-badge active">Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if (!$user['is_admin']): ?>
                                    <?php if ($user['is_blocked']): ?>
                                        <a href="admin_panel.php?action=unblock&user_id=<?php echo $user['user_id']; ?>" class="btn-small">Buka Blokir</a>
                                    <?php else: ?>
                                        <a href="admin_panel.php?action=block&user_id=<?php echo $user['user_id']; ?>" class="btn-small btn-danger">Blokir</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="admin-section">
        <h2>Manajemen Lelang</h2>
        <div class="admin-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Penjual</th>
                        <th>Kategori</th>
                        <th>Harga Saat Ini</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($listing = $listings_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $listing['listing_id']; ?></td>
                            <td><a href="item_details.php?id=<?php echo $listing['listing_id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></td>
                            <td><?php echo htmlspecialchars($listing['username']); ?></td>
                            <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
                            <td><?php echo formatPrice($listing['current_price']); ?></td>
                            <td>
                                <?php if ($listing['status'] === 'aktif'): ?>
                                    <span class="status-badge active">Aktif</span>
                                <?php elseif ($listing['status'] === 'selesai'): ?>
                                    <span class="status-badge completed">Selesai</span>
                                <?php else: ?>
                                    <span class="status-badge cancelled">Dibatalkan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="admin_panel.php?delete_listing=<?php echo $listing['listing_id']; ?>" class="btn-small btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus lelang ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>