<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE b.stok > 0";
if ($search != '') {
    $where .= " AND (o.nama_obat LIKE '%$search%' OR b.no_batch LIKE '%$search%')";
}

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat b JOIN obat o ON b.obat_id = o.id $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT b.*, o.kode_obat, o.nama_obat, s.nama_satuan 
          FROM batch_obat b 
          JOIN obat o ON b.obat_id = o.id 
          LEFT JOIN satuan s ON o.satuan_id = s.id 
          $where
          ORDER BY o.nama_obat ASC, b.tgl_kadaluarsa ASC LIMIT $limit OFFSET $offset";
$batch = mysqli_query($conn, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Laporan Stok Berdasarkan Batch & ED</h2>
    <button onclick="window.print()" class="btn btn-primary no-print"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="card shadow mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row">
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
            <thead class="table-dark">
                <tr>
                    <th width="5%">No</th>
                    <th>Kode Obat</th>
                    <th>Nama Obat</th>
                    <th>No Batch</th>
                    <th>Tgl Expired (ED)</th>
                    <th>Sisa Stok (FEFO)</th>
                    <th>Satuan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($batch)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $row['kode_obat']; ?></td>
                    <td><?= $row['nama_obat']; ?></td>
                    <td><?= $row['no_batch']; ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tgl_kadaluarsa'])); ?></td>
                    <td><?= $row['stok']; ?></td>
                    <td><?= $row['nama_satuan']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3 no-print">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
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
