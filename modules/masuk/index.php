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
    $where = "WHERE t.no_transaksi LIKE '%$search%' OR t.supplier LIKE '%$search%'";
}

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi_masuk t $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT t.*, u.nama_lengkap 
          FROM transaksi_masuk t 
          LEFT JOIN users u ON t.user_id = u.id 
          $where
          ORDER BY t.id DESC LIMIT $limit OFFSET $offset";
$transaksi = mysqli_query($conn, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Data Transaksi Masuk</h2>
    <?php if ($_SESSION['role'] == 'Petugas Gudang'): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Transaksi</a>
    <?php endif; ?>
</div>



<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-10 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control" placeholder="Cari no transaksi atau supplier..." value="<?= htmlspecialchars($search) ?>">
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
                    <th>No Transaksi</th>
                    <th>Tanggal Masuk</th>
                    <th>Supplier / Asal</th>
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
                    <td><?= date('d-m-Y', strtotime($row['tgl_masuk'])); ?></td>
                    <td><?= htmlspecialchars($row['supplier']); ?></td>
                    <td>
                        <?php
                        $transaksi_id = $row['id'];
                        $q_detail = mysqli_query($conn, "
                            SELECT d.jumlah, o.nama_obat, o.kode_obat, d.no_batch, d.tgl_kadaluarsa 
                            FROM detail_masuk d 
                            JOIN obat o ON d.obat_id = o.id 
                            WHERE d.transaksi_masuk_id = $transaksi_id
                        ");
                        if (mysqli_num_rows($q_detail) > 0):
                        ?>
                        <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                            <?php while ($det = mysqli_fetch_assoc($q_detail)): ?>
                                <li class="mb-1">
                                    <i class="fas fa-pills text-success me-1"></i>
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
                        <?php if ($_SESSION['role'] == 'Petugas Gudang'): ?>
                        <a href="edit.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit Data"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
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
