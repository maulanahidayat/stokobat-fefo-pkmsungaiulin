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
    $where = "WHERE t.no_transaksi LIKE '%$search%' OR t.tujuan LIKE '%$search%' OR t.tujuan_pengeluaran LIKE '%$search%'";
}

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi_keluar t $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT t.*, u.nama_lengkap 
          FROM transaksi_keluar t 
          LEFT JOIN users u ON t.user_id = u.id 
          $where
          ORDER BY t.id DESC LIMIT $limit OFFSET $offset";
$transaksi = mysqli_query($conn, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Data Transaksi Keluar</h2>
    <?php if ($_SESSION['role'] == 'Petugas Farmasi'): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Transaksi</a>
    <?php endif; ?>
</div>



<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-10 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control" placeholder="Cari no transaksi, jenis tujuan, atau detail penerima..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="card-body">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th width="5%">No</th>
                    <th>No Transaksi</th>
                    <th>Tanggal Keluar</th>
                    <th>Kategori Tujuan</th>
                    <th>Tujuan Pasien/Unit / Ket</th>
                    <th>Item Obat</th>
                    <th>Petugas</th>
                    <th width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($transaksi)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $row['no_transaksi']; ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tgl_keluar'])); ?></td>
                    <td>
                        <?php
                        $badge_class = 'bg-info';
                        if ($row['tujuan_pengeluaran'] == 'Karyawan') $badge_class = 'bg-primary';
                        if ($row['tujuan_pengeluaran'] == 'Obat Rusak') $badge_class = 'bg-danger';
                        ?>
                        <span class="badge <?= $badge_class; ?>"><?= htmlspecialchars($row['tujuan_pengeluaran']); ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['tujuan']); ?></td>
                    <td>
                        <?php
                        $transaksi_id = $row['id'];
                        $q_detail = mysqli_query($conn, "
                            SELECT d.jumlah, o.nama_obat, o.kode_obat, b.no_batch, b.tgl_kadaluarsa 
                            FROM detail_keluar d 
                            JOIN obat o ON d.obat_id = o.id 
                            JOIN batch_obat b ON d.batch_id = b.id 
                            WHERE d.transaksi_keluar_id = $transaksi_id
                        ");
                        if (mysqli_num_rows($q_detail) > 0):
                        ?>
                        <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                            <?php while ($det = mysqli_fetch_assoc($q_detail)): ?>
                                <li class="mb-1">
                                    <i class="fas fa-pills text-danger me-1"></i>
                                    <strong><?= htmlspecialchars($det['nama_obat']); ?></strong> 
                                    <span class="badge bg-secondary text-white ms-1"><?= number_format($det['jumlah']); ?></span>
                                    <br>
                                    <small class="text-muted" style="margin-left: 15px;">
                                        Batch: <?= htmlspecialchars($det['no_batch']); ?> | Exp: <?= date('d-m-Y', strtotime($det['tgl_kadaluarsa'])); ?>
                                    </small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td>
                        <a href="detail.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-info text-white" title="Detail"><i class="fas fa-eye"></i></a>
                        <?php if ($_SESSION['role'] == 'Petugas Farmasi'): ?>
                        <a href="edit.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit Data"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($transaksi) == 0): ?>
                <tr><td colspan="8" class="text-center">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
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

<?php require_once '../../layouts/footer.php'; ?>
