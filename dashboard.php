<?php
require_once 'config/database.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';

// Fetch summary data
// Total Obat
$q_obat = mysqli_query($conn, "SELECT COUNT(*) as total FROM obat");
$total_obat = mysqli_fetch_assoc($q_obat)['total'];

// H-7: Expiring in <= 7 days
$q_h7 = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$h7_ed = mysqli_fetch_assoc($q_h7)['total'];

// H-30: Expiring in 8 to 30 days
$q_h30 = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$h30_ed = mysqli_fetch_assoc($q_h30)['total'];

// H-90: Expiring in 31 to 90 days
$q_h90 = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)");
$h90_ed = mysqli_fetch_assoc($q_h90)['total'];

// Obat Kadaluarsa
$q_ed = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa < CURDATE()");
$ed = mysqli_fetch_assoc($q_ed)['total'];

// Stok Menipis
$q_menipis = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM obat o 
    JOIN (SELECT obat_id, SUM(stok) as total_stok FROM batch_obat GROUP BY obat_id) b ON o.id = b.obat_id 
    WHERE b.total_stok <= o.min_stok AND b.total_stok > 0
");
$menipis = mysqli_fetch_assoc($q_menipis)['total'];

// Total Kuantitas Obat Masuk
$q_masuk = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah), 0) as total FROM detail_masuk");
$total_masuk = mysqli_fetch_assoc($q_masuk)['total'];

// Total Kuantitas Obat Keluar
$q_keluar = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah), 0) as total FROM detail_keluar");
$total_keluar = mysqli_fetch_assoc($q_keluar)['total'];
?>

<div class="container-fluid">
    <h2 class="mb-4">Dashboard</h2>
    
    <!-- Baris Pertama: Status Utama -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-primary shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Jenis Obat</h5>
                    <p class="card-text fs-2"><?= $total_obat; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-warning shadow">
                <div class="card-body">
                    <h5 class="card-title">Stok Menipis</h5>
                    <p class="card-text fs-2"><?= $menipis; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-danger shadow">
                <div class="card-body">
                    <h5 class="card-title">Sudah Kadaluarsa</h5>
                    <p class="card-text fs-2"><?= $ed; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Baris Kedua: Total Obat Masuk & Keluar (QTY) -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow text-white" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-uppercase opacity-75">Total Obat Masuk (Kuantitas)</h5>
                            <p class="card-text fs-1 fw-bold mb-1"><?= number_format($total_masuk); ?> <span class="fs-5 fw-normal">Unit</span></p>
                            <small>Akumulasi seluruh pasokan obat yang masuk ke gudang</small>
                        </div>
                        <div class="fs-1 opacity-50"><i class="fas fa-file-import fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow text-white" style="background: linear-gradient(135deg, #1565c0, #2196f3);">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-uppercase opacity-75">Total Obat Keluar (Kuantitas)</h5>
                            <p class="card-text fs-1 fw-bold mb-1"><?= number_format($total_keluar); ?> <span class="fs-5 fw-normal">Unit</span></p>
                            <small>Akumulasi pengeluaran obat (Pasien, Karyawan, Obat Rusak)</small>
                        </div>
                        <div class="fs-1 opacity-50"><i class="fas fa-file-export fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Baris Ketiga: Notifikasi Kadaluarsa -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white shadow" style="background-color: #fd7e14;"> <!-- Orange -->
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-exclamation-circle me-2"></i>Exp. H-7</h5>
                    <p class="card-text fs-2"><?= $h7_ed; ?></p>
                    <a href="modules/laporan/hampir_ed.php?range=7" class="text-white text-decoration-none">Lihat Detail <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-dark bg-warning shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-exclamation-triangle me-2"></i>Exp. H-30</h5>
                    <p class="card-text fs-2"><?= $h30_ed; ?></p>
                    <a href="modules/laporan/hampir_ed.php?range=30" class="text-dark text-decoration-none">Lihat Detail <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card text-dark bg-info shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Exp. H-90</h5>
                    <p class="card-text fs-2"><?= $h90_ed; ?></p>
                    <a href="modules/laporan/hampir_ed.php?range=90" class="text-dark text-decoration-none">Lihat Detail <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow mt-2">
        <div class="card-header bg-white">
            <h5 class="mb-0">Informasi Sistem</h5>
        </div>
        <div class="card-body">
            <p>Selamat datang <strong><?= $_SESSION['nama_lengkap']; ?></strong> di Sistem Informasi Manajemen Persediaan Obat Puskesmas Sungai Ulin.</p>
            <p>Sistem ini menggunakan metode <strong>FEFO (First Expired, First Out)</strong> yang akan secara otomatis mengeluarkan stok obat dengan tanggal kadaluarsa paling awal saat transaksi keluar dilakukan.</p>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
