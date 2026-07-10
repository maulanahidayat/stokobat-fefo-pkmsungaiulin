<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$obat_id = isset($_GET['obat_id']) ? $_GET['obat_id'] : '';
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');
$search = isset($_GET['search']) ? $_GET['search'] : '';

$q_list_obat = mysqli_query($conn, "SELECT id, kode_obat, nama_obat FROM obat ORDER BY nama_obat ASC");

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Laporan Kartu Stok</h2>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Cetak</button>
    </div>
</div>

<div class="card shadow mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">Obat</label>
                <select name="obat_id" class="form-select select2" required>
                    <option value="">-- Pilih Obat --</option>
                    <?php while($o = mysqli_fetch_assoc($q_list_obat)): ?>
                        <option value="<?= $o['id']; ?>" <?= ($o['id'] == $obat_id) ? 'selected' : ''; ?>><?= $o['kode_obat'] . ' - ' . $o['nama_obat']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Tanggal Mulai</label>
                <input type="text" name="tgl_mulai" class="form-control datepicker" value="<?= htmlspecialchars($tgl_mulai) ?>" placeholder="DD-MM-YYYY" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Tanggal Selesai</label>
                <input type="text" name="tgl_selesai" class="form-control datepicker" value="<?= htmlspecialchars($tgl_selesai) ?>" placeholder="DD-MM-YYYY" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="Cari no bukti atau ket..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Cari</button>
            </div>
        </form>
    </div>
</div>

<?php if($obat_id): 
    // Ambil info obat
    $q_info = mysqli_query($conn, "SELECT o.*, s.nama_satuan FROM obat o LEFT JOIN satuan s ON o.satuan_id = s.id WHERE o.id = $obat_id");
    $info_obat = mysqli_fetch_assoc($q_info);

    // Saldo awal -> total masuk sebelum periode dikurangi total keluar sebelum periode
    $q_saldo_masuk = mysqli_query($conn, "SELECT COALESCE(SUM(dm.jumlah), 0) as ms FROM detail_masuk dm JOIN transaksi_masuk tm ON dm.transaksi_masuk_id = tm.id WHERE dm.obat_id = $obat_id AND tm.tgl_masuk < '$tgl_mulai'");
    $saldo_masuk = mysqli_fetch_assoc($q_saldo_masuk)['ms'];

    $q_saldo_keluar = mysqli_query($conn, "SELECT COALESCE(SUM(dk.jumlah), 0) as kl FROM detail_keluar dk JOIN transaksi_keluar tk ON dk.transaksi_keluar_id = tk.id WHERE dk.obat_id = $obat_id AND tk.tgl_keluar < '$tgl_mulai'");
    $saldo_keluar = mysqli_fetch_assoc($q_saldo_keluar)['kl'];

    $saldo_awal = $saldo_masuk - $saldo_keluar;
    $saldo_berjalan = $saldo_awal;

    // Data berjalan dalam periode bulan dan tahun yg dipilih
    // Gabung masuk dan keluar pakai UNION
    $query_kartu = "
        SELECT 
            tm.tgl_masuk as tanggal, 
            tm.no_transaksi as no_bukti, 
            CONCAT('Dari: ', tm.supplier, ' (Batch: ', dm.no_batch, ')') as keterangan,
            dm.jumlah as masuk,
            0 as keluar
        FROM detail_masuk dm
        JOIN transaksi_masuk tm ON dm.transaksi_masuk_id = tm.id
        WHERE dm.obat_id = $obat_id AND tm.tgl_masuk BETWEEN '$tgl_mulai' AND '$tgl_selesai'
        
        UNION ALL
        
        SELECT 
            tk.tgl_keluar as tanggal,
            tk.no_transaksi as no_bukti,
            CONCAT('Tujuan: ', tk.tujuan, ' (Batch: ', b.no_batch, ')') as keterangan,
            0 as masuk,
            dk.jumlah as keluar
        FROM detail_keluar dk
        JOIN transaksi_keluar tk ON dk.transaksi_keluar_id = tk.id
        JOIN batch_obat b ON dk.batch_id = b.id
        WHERE dk.obat_id = $obat_id AND tk.tgl_keluar BETWEEN '$tgl_mulai' AND '$tgl_selesai'
        
        ORDER BY tanggal ASC, no_bukti ASC
    ";
    
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    $kartu_all = mysqli_query($conn, $query_kartu);
    $data_kartu = [];
    $saldo_temp = $saldo_awal;
    
    while ($row = mysqli_fetch_assoc($kartu_all)) {
        $saldo_temp += $row['masuk'] - $row['keluar'];
        $row['saldo'] = $saldo_temp;
        
        if ($search != '') {
            if (stripos($row['no_bukti'], $search) !== false || stripos($row['keterangan'], $search) !== false) {
                $data_kartu[] = $row;
            }
        } else {
            $data_kartu[] = $row;
        }
    }
    
    $total_data = count($data_kartu);
    $total_pages = ceil($total_data / $limit);
?>
<div class="card shadow">
    <div class="card-body">
        <h4 class="text-center mb-0">KARTU STOK OBAT</h4>
        <p class="text-center mb-4">Puskesmas Sungai Ulin</p>
        
        <table class="table table-sm table-borderless w-50 mb-3">
            <tr><td width="30%">Kode Obat</td><td>: <?= $info_obat['kode_obat']; ?></td></tr>
            <tr><td>Nama Obat</td><td>: <?= $info_obat['nama_obat']; ?></td></tr>
            <tr><td>Satuan</td><td>: <?= $info_obat['nama_satuan']; ?></td></tr>
            <tr><td>Periode</td><td>: <?= date('d-m-Y', strtotime($tgl_mulai)); ?> s/d <?= date('d-m-Y', strtotime($tgl_selesai)); ?></td></tr>
        </table>

        <table class="table table-bordered table-striped">
            <thead class="table-dark text-center">
                <tr>
                    <th class="align-middle">Tanggal</th>
                    <th class="align-middle">No Bukti</th>
                    <th class="align-middle">Keterangan</th>
                    <th class="align-middle">Masuk</th>
                    <th class="align-middle">Keluar</th>
                    <th class="align-middle">Sisa Stok</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="text-end fw-bold">Saldo Awal Sebelum <?= date('d-m-Y', strtotime($tgl_mulai)); ?>:</td>
                    <td class="text-center fw-bold"><?= $saldo_awal; ?></td>
                </tr>
                <?php 
                $end_index = min($offset + $limit, $total_data);
                for ($i = $offset; $i < $end_index; $i++):
                    $row = $data_kartu[$i];
                ?>
                <tr>
                    <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                    <td><?= $row['no_bukti']; ?></td>
                    <td><?= $row['keterangan']; ?></td>
                    <td class="text-center text-success"><?= $row['masuk'] > 0 ? "+".$row['masuk'] : "-"; ?></td>
                    <td class="text-center text-danger"><?= $row['keluar'] > 0 ? "-".$row['keluar'] : "-"; ?></td>
                    <td class="text-center fw-bold"><?= $row['saldo']; ?></td>
                </tr>
                <?php endfor; ?>
                <?php if($total_data == 0): ?>
                <tr><td colspan="6" class="text-center">Tidak ada transaksi pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3 no-print">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?obat_id=<?= $obat_id ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?obat_id=<?= $obat_id ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?obat_id=<?= $obat_id ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5'
    });
    
    flatpickr(".datepicker", {
        allowInput: true,
        altInput: true,
        altFormat: "d-m-Y",
        dateFormat: "Y-m-d"
    });
});
</script>

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

<?php require_once '../../layouts/footer.php'; ?>
