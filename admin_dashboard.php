<?php
require_once 'config.php';

// Jika user tidak login atau bukan admin, redirect ke halaman utama
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Anda tidak memiliki izin untuk mengakses halaman ini";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// --- LOGIKA LAPORAN ---
 $total_users = 0;
 $active_listings = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM listings WHERE status = 'aktif'");
    $active_listings = $stmt->fetch()['count'];
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// --- LOGIKA FILTER & PENCARIAN PENGGUNA ---
 $user_search = $_GET['user_search'] ?? '';
 $user_status = $_GET['user_status'] ?? 'all'; // all, active, blocked

 $user_query = "SELECT * FROM users WHERE 1=1";
 $params = [];

if ($user_status == 'active') {
    $user_query .= " AND is_blocked = 0";
} elseif ($user_status == 'blocked') {
    $user_query .= " AND is_blocked = 1";
}

if (!empty($user_search)) {
    $user_query .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $search_term = '%' . $user_search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

 $user_query .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($user_query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    $users = [];
}


// --- LOGIKA FILTER & PENCARIAN LELANG ---
 $listing_search = $_GET['listing_search'] ?? '';
 $listing_status = $_GET['listing_status'] ?? 'all'; // all, aktif, selesai, dibatalkan

 $listing_query = "SELECT l.*, u.username, c.name as category_name FROM listings l 
                  JOIN users u ON l.user_id = u.id 
                  JOIN categories c ON l.category_id = c.id 
                  WHERE 1=1";
 $params_listing = [];

if ($listing_status != 'all') {
    $listing_query .= " AND l.status = ?";
    $params_listing[] = $listing_status;
}

if (!empty($listing_search)) {
    $listing_query .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    $search_term = '%' . $listing_search . '%';
    $params_listing[] = $search_term;
    $params_listing[] = $search_term;
}

 $listing_query .= " ORDER BY l.created_at DESC";

try {
    $stmt = $pdo->prepare($listing_query);
    $stmt->execute($params_listing);
    $listings = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    $listings = [];
}

?>

<?php require_once 'header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="display-4">Admin Dashboard</h1>
        <p class="lead">Kelola pengguna dan barang lelang</p>
    </div>
</div>

<!-- LAPORAN -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Pengguna Terdaftar</h5>
                <p class="card-text display-4"><?php echo $total_users; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Lelang Aktif Saat Ini</h5>
                <p class="card-text display-4"><?php echo $active_listings; ?></p>
            </div>
        </div>
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
    <!-- TAB PENGGUNA -->
    <div class="tab-pane fade show active" id="users" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pengguna</h5>
                <a href="add_user_admin.php" class="btn btn-success btn-sm">
                    <i class="fas fa-user-plus me-1"></i>Tambah Pengguna Baru
                </a>
            </div>
            <div class="card-body">
                <!-- FORM PENCARIAN & FILTER PENGGUNA -->
                <form method="GET" action="admin_dashboard.php" class="row g-3 mb-4">
                    <input type="hidden" name="tab" value="users">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="user_search" placeholder="Cari berdasarkan nama, username, atau email..." value="<?php echo htmlspecialchars($user_search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="user_status">
                            <option value="all" <?php echo $user_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="active" <?php echo $user_status == 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="blocked" <?php echo $user_status == 'blocked' ? 'selected' : ''; ?>>Diblokir</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>

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
                                    <td>
                                        <a href="edit_user_admin.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-info">Edit</a>
                                        <?php if (!$user['is_admin']): ?>
                                            <?php if ($user['is_blocked']): ?>
                                                <a href="unblock_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-success">Unblock</a>
                                            <?php else: ?>
                                                <a href="block_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning">Block</a>
                                            <?php endif; ?>
                                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">Hapus</a>
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

    <!-- TAB BARANG LELANG -->
    <div class="tab-pane fade" id="listings" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Daftar Barang Lelang</h5>
            </div>
            <div class="card-body">
                <!-- FORM PENCARIAN & FILTER LELANG -->
                <form method="GET" action="admin_dashboard.php" class="row g-3 mb-4">
                    <input type="hidden" name="tab" value="listings">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="listing_search" placeholder="Cari berdasarkan nama atau deskripsi barang..." value="<?php echo htmlspecialchars($listing_search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="listing_status">
                            <option value="all" <?php echo $listing_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="aktif" <?php echo $listing_status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="selesai" <?php echo $listing_status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="dibatalkan" <?php echo $listing_status == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Barang</th>
                                <th>Penjual</th>
                                <th>Kategori</th>
                                <th>Harga Saat Ini</th>
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
                                    <td><?php echo formatPrice($listing['current_price']); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($listing['status']) {
                                            case 'aktif': $statusClass = 'bg-success'; break;
                                            case 'selesai': $statusClass = 'bg-secondary'; break;
                                            case 'dibatalkan': $statusClass = 'bg-danger'; break;
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