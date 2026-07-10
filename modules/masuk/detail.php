<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];
$query = "SELECT t.*, u.nama_lengkap 
          FROM transaksi_masuk t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE t.id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Data transaksi tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$transaksi = mysqli_fetch_assoc($result);

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2>Detail Transaksi Masuk</h2>
    <div>
        <button onclick="window.print()" class="btn btn-secondary me-2"><i class="fas fa-print"></i> Cetak</button>
        <a href="index.php" class="btn btn-danger"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="m-0 font-weight-bold">Informasi Transaksi</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="35%">No Transaksi</th>
                        <td width="5%">:</td>
                        <td><strong><?= $transaksi['no_transaksi']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Tanggal Masuk</th>
                        <td>:</td>
                        <td><?= date('d-m-Y', strtotime($transaksi['tgl_masuk'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="35%">Supplier / Asal</th>
                        <td width="5%">:</td>
                        <td><?= htmlspecialchars($transaksi['supplier'] ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Petugas (User)</th>
                        <td>:</td>
                        <td><?= htmlspecialchars($transaksi['nama_lengkap']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-success text-white">
        <h6 class="m-0 font-weight-bold">Daftar Item Obat</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Kode Obat</th>
                        <th>Nama Obat</th>
                        <th>No Batch</th>
                        <th>Tanggal Kadaluarsa</th>
                        <th class="text-center">Jumlah (Qty)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q_detail = mysqli_query($conn, "
                        SELECT d.*, o.nama_obat, o.kode_obat 
                        FROM detail_masuk d 
                        JOIN obat o ON d.obat_id = o.id 
                        WHERE d.transaksi_masuk_id = $id
                    ");
                    $no = 1;
                    $total_qty = 0;
                    while ($detail = mysqli_fetch_assoc($q_detail)):
                        $total_qty += $detail['jumlah'];
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td><?= $detail['kode_obat']; ?></td>
                        <td><?= $detail['nama_obat']; ?></td>
                        <td><?= $detail['no_batch']; ?></td>
                        <td><?= date('d-m-Y', strtotime($detail['tgl_kadaluarsa'])); ?></td>
                        <td class="text-center"><?= number_format($detail['jumlah']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end">Total Jumlah Obat Masuk :</th>
                        <th class="text-center"><?= number_format($total_qty); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="row mt-5 d-none d-print-flex">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p>Mengetahui,</p>
                <br><br><br>
                <p><strong><?= htmlspecialchars($transaksi['nama_lengkap']); ?></strong></p>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body { font-size: 14px; background: white; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background-color: transparent !important; color: black !important; padding-left: 0; padding-right: 0; border-bottom: 2px solid #000; margin-bottom: 15px; }
        .card-header h6 { font-size: 18px; font-weight: bold; }
        #sidebar, .navbar, .no-print, .btn { display: none !important; }
        #content { margin: 0; padding: 0; width: 100%; }
        .table-dark { background-color: transparent !important; color: black !important; }
        .table-dark th { background-color: #f8f9fa !important; border-color: #dee2e6 !important; color: black !important; -webkit-print-color-adjust: exact; }
    }
</style>

<?php require_once '../../layouts/footer.php'; ?>
