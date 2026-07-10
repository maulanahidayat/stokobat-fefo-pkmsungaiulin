<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

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

$query = "SELECT 
            o.id,
            o.kode_obat,
            o.nama_obat,
            s.nama_satuan,
            COALESCE((SELECT SUM(jumlah) FROM detail_masuk dm JOIN transaksi_masuk tm ON dm.transaksi_masuk_id = tm.id WHERE dm.obat_id = o.id AND tm.tgl_masuk BETWEEN '$tgl_mulai' AND '$tgl_selesai'), 0) as total_masuk,
            COALESCE((SELECT SUM(jumlah) FROM detail_keluar dk JOIN transaksi_keluar tk ON dk.transaksi_keluar_id = tk.id WHERE dk.obat_id = o.id AND tk.tgl_keluar BETWEEN '$tgl_mulai' AND '$tgl_selesai'), 0) as total_keluar
          FROM obat o
          LEFT JOIN satuan s ON o.satuan_id = s.id
          $where
          ORDER BY o.nama_obat ASC LIMIT $limit OFFSET $offset";
$mutasi = mysqli_query($conn, $query);

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2>Laporan Mutasi Stok (Masuk & Keluar)</h2>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Cetak</button>
    </div>
</div>

<div class="text-center mb-4 d-none d-print-block">
    <h3 class="fw-bold mb-1">LAPORAN MUTASI STOK OBAT</h3>
    <h5 class="text-muted mb-2">Puskesmas Sungai Ulin</h5>
    <p class="mb-0">Periode: <strong><?= date('d-m-Y', strtotime($tgl_mulai)); ?></strong> s/d <strong><?= date('d-m-Y', strtotime($tgl_selesai)); ?></strong></p>
    <hr style="border-top: 2px solid #000; margin-top: 15px;">
</div>

<h5 class="no-print mb-4 text-muted">Periode: <?= date('d-m-Y', strtotime($tgl_mulai)); ?> s/d <?= date('d-m-Y', strtotime($tgl_selesai)); ?></h5>

<div class="card shadow mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">Tanggal Mulai</label>
                <input type="text" name="tgl_mulai" class="form-control datepicker" value="<?= htmlspecialchars($tgl_mulai) ?>" placeholder="DD-MM-YYYY" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Tanggal Selesai</label>
                <input type="text" name="tgl_selesai" class="form-control datepicker" value="<?= htmlspecialchars($tgl_selesai) ?>" placeholder="DD-MM-YYYY" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode obat..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="card-body">
        <table class="table table-bordered table-striped text-center">
            <thead class="table-dark">
                <tr>
                    <th rowspan="2" class="align-middle" width="5%">No</th>
                    <th rowspan="2" class="align-middle">Kode Obat</th>
                    <th rowspan="2" class="align-middle">Nama Obat</th>
                    <th rowspan="2" class="align-middle">Satuan</th>
                    <th colspan="2">Jumlah Mutasi</th>
                </tr>
                <tr>
                    <th>Masuk</th>
                    <th>Keluar</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($mutasi)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td class="text-start"><?= $row['kode_obat']; ?></td>
                    <td class="text-start"><?= $row['nama_obat']; ?></td>
                    <td><?= $row['nama_satuan']; ?></td>
                    <td><span class="text-success fw-bold"><?= $row['total_masuk']; ?></span></td>
                    <td><span class="text-danger fw-bold"><?= $row['total_keluar']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3 no-print">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<style>
    @media print {
        #sidebar, .navbar, .no-print, .btn {
            display: none !important;
        }
        #content {
            margin: 0;
            padding: 0;
            width: 100%;
        }
    }
</style>

<script>
$(document).ready(function() {
    flatpickr(".datepicker", {
        allowInput: true,
        altInput: true,
        altFormat: "d-m-Y",
        dateFormat: "Y-m-d"
    });
});
</script>

<?php require_once '../../layouts/footer.php'; ?>
