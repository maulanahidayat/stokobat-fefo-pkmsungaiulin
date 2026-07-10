<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';

// Ambil parameter jumlah bulan dari request, default 3 bulan
$n_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 3;
if ($n_bulan < 1) $n_bulan = 1;

// Hitung rentang tanggal
$start_date = date('Y-m-d', strtotime("-$n_bulan months"));
$end_date = date('Y-m-d');

// Query untuk menghitung total pemakaian (transaksi keluar) obat pada rentang waktu tersebut
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

$query = "SELECT o.kode_obat, o.nama_obat, s.nama_satuan, 
                 COALESCE(SUM(dk.jumlah), 0) as total_pemakaian
          FROM obat o
          LEFT JOIN satuan s ON o.satuan_id = s.id
          LEFT JOIN detail_keluar dk ON o.id = dk.obat_id
          LEFT JOIN transaksi_keluar tk ON dk.transaksi_keluar_id = tk.id AND tk.tgl_keluar >= '$start_date' AND tk.tgl_keluar <= '$end_date'
          $where
          GROUP BY o.id
          ORDER BY o.nama_obat ASC LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Prediksi Kebutuhan Obat (Moving Average)</h2>
    <button onclick="window.print()" class="btn btn-primary d-print-none"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="card shadow mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="bulan" class="col-form-label fw-bold">Periode Analisis (Bulan Terakhir):</label>
            </div>
            <div class="col-auto">
                <input type="number" id="bulan" name="bulan" class="form-control" value="<?= $n_bulan; ?>" min="1" max="24" style="width: 100px;">
            </div>
            <div class="col-auto">
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode obat..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="fas fa-calculator me-1"></i> Hitung Prediksi</button>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info d-print-none shadow-sm">
    <i class="fas fa-info-circle me-2"></i> Prediksi dihitung menggunakan metode <strong>Simple Moving Average (SMA)</strong> berdasarkan data pemakaian obat dalam <strong><?= $n_bulan; ?> bulan terakhir</strong> (Periode: <?= date('d M Y', strtotime($start_date)); ?> s.d <?= date('d M Y', strtotime($end_date)); ?>).<br>
    <strong>Rumus:</strong> Total Pemakaian / <?= $n_bulan; ?> = Rata-rata per bulan (Dibulatkan ke atas untuk prediksi kebutuhan bulan berikutnya).
</div>

<div class="card shadow">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary"><i class="fas fa-chart-line me-2"></i> Hasil Prediksi Kebutuhan Bulan Depan</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th width="5%">No</th>
                        <th>Kode Obat</th>
                        <th>Nama Obat</th>
                        <th>Satuan</th>
                        <th width="15%">Total Keluar<br><small>(<?= $n_bulan; ?> Bulan)</small></th>
                        <th width="15%">Rata-rata / Bulan</th>
                        <th width="15%" class="bg-warning text-dark border-warning">Prediksi Kebutuhan<br>Bulan Depan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($result)): 
                        $total = $row['total_pemakaian'];
                        $rata_rata = $total / $n_bulan;
                        // Pembulatan ke atas (ceil) agar stok tidak kurang
                        $prediksi = ceil($rata_rata);
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?= $row['kode_obat']; ?></span></td>
                        <td class="fw-bold"><?= $row['nama_obat']; ?></td>
                        <td class="text-center"><?= $row['nama_satuan']; ?></td>
                        <td class="text-center fs-5"><?= $total; ?></td>
                        <td class="text-center"><?= number_format($rata_rata, 2, ',', '.'); ?></td>
                        <td class="text-center fw-bold fs-4 text-danger table-warning border-warning"><?= $prediksi; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($result) == 0): ?>
                    <tr><td colspan="7" class="text-center py-4">Tidak ada data obat di sistem.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3 d-print-none">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?bulan=<?= $n_bulan ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?bulan=<?= $n_bulan ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?bulan=<?= $n_bulan ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<style>
    @media print {
        #sidebar, .navbar, .btn, form, .d-print-none, .alert {
            display: none !important;
        }
        #content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .card-header {
            border-bottom: 2px solid #000 !important;
        }
        .table-dark th {
            background-color: #f8f9fa !important;
            color: #000 !important;
            border-color: #dee2e6 !important;
        }
        .table-warning, .bg-warning {
            background-color: transparent !important;
        }
        .badge {
            border: 1px solid #000 !important;
            color: #000 !important;
            background-color: transparent !important;
        }
        body {
            background-color: #fff;
        }
    }
</style>

<?php require_once '../../layouts/footer.php'; ?>
