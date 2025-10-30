<?php
require_once 'config.php';

// Jika user tidak login atau bukan admin, redirect ke halaman utama
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Anda tidak memiliki izin untuk mengakses halaman ini";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Ambil data pengguna
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Ambil data barang
try {
    $stmt = $pdo->query("SELECT l.*, u.username, c.name as category_name FROM listings l 
                         JOIN users u ON l.user_id = u.id 
                         JOIN categories c ON l.category_id = c.id 
                         ORDER BY l.created_at DESC");
    $listings = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}
?>

<?php require_once 'header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="display-4">Admin Dashboard</h1>
        <p class="lead">Kelola pengguna dan barang lelang</p>
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Pengguna</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="listings-tab" data-bs-toggle="tab" data-bs-target="#listings" type="button" role="tab">Barang Lelang</button>
    </li>
</ul>

<div class="tab-content" id="adminTabsContent">
    <div class="tab-pane fade show active" id="users" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Daftar Pengguna</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_blocked']): ?>
                                            <span class="badge bg-danger">Diblokir</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if (!$user['is_admin']): ?>
                                            <?php if ($user['is_blocked']): ?>
                                                <a href="unblock_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-success">Unblock</a>
                                            <?php else: ?>
                                                <a href="block_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning">Block</a>
                                            <?php endif; ?>
                                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">Hapus</a>
                                        <?php else: ?>
                                            <span class="text-muted">Tidak dapat diubah</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tab-pane fade" id="listings" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Daftar Barang Lelang</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Barang</th>
                                <th>Penjual</th>
                                <th>Kategori</th>
                                <th>Harga Awal</th>
                                <th>Harga Saat Ini</th>
                                <th>Waktu Akhir</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings as $listing): ?>
                                <tr>
                                    <td><?php echo $listing['id']; ?></td>
                                    <td><?php echo htmlspecialchars($listing['title']); ?></td>
                                    <td><?php echo htmlspecialchars($listing['username']); ?></td>
                                    <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
                                    <td><?php echo formatPrice($listing['start_price']); ?></td>
                                    <td><?php echo formatPrice($listing['current_price']); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($listing['end_time'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($listing['status']) {
                                            case 'aktif':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'selesai':
                                                $statusClass = 'bg-secondary';
                                                break;
                                            case 'dibatalkan':
                                                $statusClass = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($listing['status']); ?></span>
                                    </td>
                                    <td>
                                        <a href="listing_details.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-primary">Lihat</a>
                                        <a href="delete_listing_admin.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>