<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';

// Handle range parameter
$range = isset($_GET['range']) ? (int)$_GET['range'] : 90;

if ($range == 7) {
    $where_date = "BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    $title_range = "H-7 (<= 7 Hari)";
} elseif ($range == 30) {
    $where_date = "BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $title_range = "H-30 (8 - 30 Hari)";
} elseif ($range == 90) {
    $where_date = "BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
    $title_range = "H-90 (31 - 90 Hari)";
} else {
    // Default show all <= 90
    $where_date = "BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
    $title_range = "<= 90 Hari";
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE b.stok > 0 AND b.tgl_kadaluarsa $where_date";
if ($search != '') {
    $where .= " AND (o.nama_obat LIKE '%$search%' OR b.no_batch LIKE '%$search%')";
}

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat b JOIN obat o ON b.obat_id = o.id $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT b.*, o.kode_obat, o.nama_obat, s.nama_satuan, DATEDIFF(b.tgl_kadaluarsa, CURDATE()) as sisa_hari
          FROM batch_obat b 
          JOIN obat o ON b.obat_id = o.id 
          LEFT JOIN satuan s ON o.satuan_id = s.id 
          $where
          ORDER BY b.tgl_kadaluarsa ASC LIMIT $limit OFFSET $offset";
$ed = mysqli_query($conn, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Laporan Obat Hampir Kadaluarsa (<?= $title_range; ?>)</h2>
    <button onclick="window.print()" class="btn btn-primary no-print"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="card shadow mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row">
            <input type="hidden" name="range" value="<?= $range ?>">
            <div class="col-md-10 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control" placeholder="Cari nama obat atau no batch..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead class="table-info">
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Obat</th>
                    <th>No Batch</th>
                    <th>Tgl Expired (ED)</th>
                    <th>Sisa Hari</th>
                    <th>Stok Obat</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($ed)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $row['kode_obat'] . ' - ' . $row['nama_obat']; ?></td>
                    <td><?= $row['no_batch']; ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tgl_kadaluarsa'])); ?></td>
                    <td><span class="badge bg-warning text-dark"><?= $row['sisa_hari']; ?> Hari</span></td>
                    <td><?= $row['stok'] . ' ' . $row['nama_satuan']; ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($ed) == 0): ?>
                <tr><td colspan="6" class="text-center">Tidak ada obat yang hampir kadaluarsa.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3 no-print">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?range=<?= $range ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?range=<?= $range ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?range=<?= $range ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<style>
    @media print {
        #sidebar, .navbar, .btn, .no-print {
            display: none !important;
        }
        #content {
            margin: 0;
            padding: 0;
            width: 100%;
        }
    }
</style>

<?php require_once '../../layouts/footer.php'; ?>
