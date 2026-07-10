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
$where = "WHERE t.tgl_masuk BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
if ($search != '') {
    $where .= " AND (t.no_transaksi LIKE '%$search%' OR o.nama_obat LIKE '%$search%' OR t.supplier LIKE '%$search%')";
}

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total 
          FROM transaksi_masuk t 
          JOIN detail_masuk d ON t.id = d.transaksi_masuk_id 
          JOIN obat o ON d.obat_id = o.id
          $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT t.*, d.no_batch, d.tgl_kadaluarsa, d.jumlah, o.nama_obat, o.kode_obat 
          FROM transaksi_masuk t 
          JOIN detail_masuk d ON t.id = d.transaksi_masuk_id 
          JOIN obat o ON d.obat_id = o.id 
          $where
          ORDER BY t.tgl_masuk DESC, t.id DESC LIMIT $limit OFFSET $offset";
$trans = mysqli_query($conn, $query);

// Hitung Total Masuk Periode Terpilih (Kuantitas)
$q_total_periode = mysqli_query($conn, "
    SELECT COALESCE(SUM(d.jumlah), 0) as total 
    FROM detail_masuk d 
    JOIN transaksi_masuk t ON d.transaksi_masuk_id = t.id 
    $where
");
$total_periode = mysqli_fetch_assoc($q_total_periode)['total'];

// Hitung Total Masuk Keseluruhan (Kuantitas)
$q_total_keseluruhan = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah), 0) as total FROM detail_masuk");
$total_keseluruhan = mysqli_fetch_assoc($q_total_keseluruhan)['total'];

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2>Laporan Transaksi Masuk</h2>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="text-center mb-4 d-none d-print-block">
    <h3 class="fw-bold mb-1">LAPORAN TRANSAKSI MASUK OBAT</h3>
    <h5 class="text-muted mb-2">Puskesmas Sungai Ulin</h5>
    <p class="mb-0">Periode: <strong><?= date('d-m-Y', strtotime($tgl_mulai)); ?></strong> s/d <strong><?= date('d-m-Y', strtotime($tgl_selesai)); ?></strong></p>
    <hr style="border-top: 2px solid #000; margin-top: 15px;">
</div>

<h5 class="no-print mb-4 text-muted">Periode: <?= date('d-m-Y', strtotime($tgl_mulai)); ?> s/d <?= date('d-m-Y', strtotime($tgl_selesai)); ?></h5>

<!-- Widget Summary Qty Masuk -->
<div class="row mb-4">
    <div class="col-md-6 mb-2">
        <div class="card shadow-sm border-start border-success border-4">
            <div class="card-body py-3">
                <h6 class="text-muted text-uppercase fs-7 mb-1">Total Masuk Periode Ini</h6>
                <h4 class="text-success fw-bold mb-0"><?= number_format($total_periode); ?> <span class="fs-6 fw-normal text-muted">Unit</span></h4>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-2">
        <div class="card shadow-sm border-start border-primary border-4">
            <div class="card-body py-3">
                <h6 class="text-muted text-uppercase fs-7 mb-1">Total Masuk Keseluruhan</h6>
                <h4 class="text-primary fw-bold mb-0"><?= number_format($total_keseluruhan); ?> <span class="fs-6 fw-normal text-muted">Unit</span></h4>
            </div>
        </div>
    </div>
</div>

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
                <input type="text" name="search" class="form-control" placeholder="Cari obat, no transaksi, supplier..." value="<?= htmlspecialchars($search) ?>">
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
                    <th>Tgl Masuk</th>
                    <th>No Transaksi</th>
                    <th>Supplier / Asal</th>
                    <th>Nama Obat</th>
                    <th>No Batch</th>
                    <th>Tgl Expired</th>
                    <th>Qty Masuk</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($trans)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tgl_masuk'])); ?></td>
                    <td><?= $row['no_transaksi']; ?></td>
                    <td><?= htmlspecialchars($row['supplier']); ?></td>
                    <td><?= $row['kode_obat'] . ' - ' . htmlspecialchars($row['nama_obat']); ?></td>
                    <td><?= htmlspecialchars($row['no_batch']); ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tgl_kadaluarsa'])); ?></td>
                    <td><?= number_format($row['jumlah']); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($trans) == 0): ?>
                <tr><td colspan="8" class="text-center">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
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
