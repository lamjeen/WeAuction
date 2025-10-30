<?php
require_once 'config.php';

// Jika user tidak login, redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['message'] = "Anda harus login untuk mengakses dashboard";
    $_SESSION['message_type'] = "warning";
    redirect('login.php');
}

// Ambil data barang lelang milik user
try {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $listings = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Ambil riwayat tawaran user
try {
    $stmt = $pdo->prepare("SELECT b.*, l.title, l.end_time FROM bids b JOIN listings l ON b.listing_id = l.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $bids = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}
?>

<?php require_once 'header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="display-4">Dashboard Saya</h1>
        <p class="lead">Kelola aktivitas lelang Anda</p>
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="listings-tab" data-bs-toggle="tab" data-bs-target="#listings" type="button" role="tab">Barang Saya</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="bids-tab" data-bs-toggle="tab" data-bs-target="#bids" type="button" role="tab">Riwayat Tawaran</button>
    </li>
</ul>

<div class="tab-content" id="dashboardTabsContent">
    <div class="tab-pane fade show active" id="listings" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Barang Lelang Saya</h5>
                <a href="create_listing.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle me-1"></i>Tambah Barang
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($listings)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Anda belum memiliki barang lelang.
                        <a href="create_listing.php" class="alert-link">Buat barang lelang sekarang</a>.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Harga Awal</th>
                                    <th>Harga Saat Ini</th>
                                    <th>Status</th>
                                    <th>Waktu Berakhir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listings as $listing): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($listing['title']); ?></td>
                                        <td><?php echo formatPrice($listing['start_price']); ?></td>
                                        <td><?php echo formatPrice($listing['current_price']); ?></td>
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
                                        <td><?php echo formatTime($listing['end_time']); ?></td>
                                        <td>
                                            <a href="listing_details.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-primary">Lihat</a>
                                            <?php if ($listing['status'] == 'aktif'): ?>
                                                <a href="edit_listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <a href="cancel_listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan lelang ini?')">Batalkan</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="tab-pane fade" id="bids" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Riwayat Tawaran Saya</h5>
            </div>
            <div class="card-body">
                <?php if (empty($bids)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Anda belum pernah melakukan penawaran.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Jumlah Tawaran</th>
                                    <th>Waktu Tawaran</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bids as $bid): ?>
                                    <tr>
                                        <td><a href="listing_details.php?id=<?php echo $bid['listing_id']; ?>"><?php echo htmlspecialchars($bid['title']); ?></a></td>
                                        <td><?php echo formatPrice($bid['bid_amount']); ?></td>
                                        <td><?php echo formatTime($bid['created_at']); ?></td>
                                        <td>
                                            <?php if (strtotime($bid['end_time']) > time()): ?>
                                                <span class="badge bg-success">Masih Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Berakhir</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>