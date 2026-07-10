<?php
session_start();
require_once '../../config/database.php';

// Cek hak akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Petugas Farmasi') {
    header("Location: ../../dashboard.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Ambil data transaksi
$query = "SELECT * FROM transaksi_keluar WHERE id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Data transaksi tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$transaksi = mysqli_fetch_assoc($result);

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tgl_keluar = $_POST['tgl_keluar'];
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);

    $update_query = "UPDATE transaksi_keluar SET tgl_keluar = '$tgl_keluar', tujuan = '$tujuan' WHERE id = $id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Data transaksi berhasil diperbarui.";
        header("Location: index.php");
        exit;
    } else {
        $error = "Terjadi kesalahan saat menyimpan data.";
    }
}

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Transaksi Keluar</h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error; ?></div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> <strong>Informasi:</strong> Untuk menjaga integritas histori dan kalkulasi stok obat dengan metode FEFO, Anda hanya diizinkan untuk mengubah informasi dasar transaksi (Tanggal dan Tujuan). Detail obat yang dikeluarkan tidak dapat diubah setelah transaksi disimpan.
</div>

<div class="card shadow">
    <div class="card-body">
        <form action="" method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>No Transaksi</label>
                    <input type="text" class="form-control" value="<?= $transaksi['no_transaksi']; ?>" readonly disabled>
                </div>
                <div class="col-md-6">
                    <label>Tanggal Keluar <span class="text-danger">*</span></label>
                    <input type="text" class="form-control datepicker" name="tgl_keluar" required value="<?= $transaksi['tgl_keluar']; ?>" placeholder="DD-MM-YYYY">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label>Tujuan Pasien/Unit <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="tujuan" required value="<?= htmlspecialchars($transaksi['tujuan']); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </form>
    </div>
</div>

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
