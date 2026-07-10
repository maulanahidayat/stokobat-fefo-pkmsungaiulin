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
$where = "";
if ($search != '') {
    $where = "WHERE o.nama_obat LIKE '%$search%' OR o.kode_obat LIKE '%$search%'";
}

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM obat o $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT o.*, k.nama_kategori, s.nama_satuan, 
          COALESCE((SELECT SUM(stok) FROM batch_obat WHERE obat_id = o.id), 0) as total_stok
          FROM obat o 
          LEFT JOIN kategori k ON o.kategori_id = k.id 
          LEFT JOIN satuan s ON o.satuan_id = s.id 
          $where
          ORDER BY o.nama_obat ASC LIMIT $limit OFFSET $offset";
$obat = mysqli_query($conn, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Laporan Stok Obat (Keseluruhan)</h2>
    <button onclick="window.print()" class="btn btn-primary no-print"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="card shadow mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-10 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control" placeholder="Cari kode atau nama obat..." value="<?= htmlspecialchars($search) ?>">
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
                    <th>Kode</th>
                    <th>Nama Obat</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th>Total Stok</th>
                    <th>Min Stok</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($obat)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $row['kode_obat']; ?></td>
                    <td><?= $row['nama_obat']; ?></td>
                    <td><?= $row['nama_kategori']; ?></td>
                    <td><?= $row['nama_satuan']; ?></td>
                    <td><?= $row['total_stok']; ?></td>
                    <td><?= $row['min_stok']; ?></td>
                    <td>
                        <?php if ($row['total_stok'] <= $row['min_stok'] && $row['total_stok'] > 0): ?>
                            <span class="badge bg-warning text-dark">Menipis</span>
                        <?php elseif ($row['total_stok'] == 0): ?>
                            <span class="badge bg-danger">Habis</span>
                        <?php else: ?>
                            <span class="badge bg-success">Aman</span>
                        <?php endif; ?>
                    </td>
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
