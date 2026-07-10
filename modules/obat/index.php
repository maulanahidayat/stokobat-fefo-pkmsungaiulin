<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_SESSION['role'] == 'Petugas Gudang' || $_SESSION['role'] == 'Petugas Farmasi')) {
    if (isset($_POST['add'])) {
        $kode = mysqli_real_escape_string($conn, $_POST['kode_obat']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama_obat']);
        $kategori = $_POST['kategori_id'];
        $satuan = $_POST['satuan_id'];
        $min_stok = $_POST['min_stok'];

        if (mysqli_query($conn, "INSERT INTO obat (kode_obat, nama_obat, kategori_id, satuan_id, min_stok) VALUES ('$kode', '$nama', '$kategori', '$satuan', '$min_stok')")) {
            $_SESSION['success'] = "Data obat berhasil disimpan.";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data obat.";
        }
    } elseif (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $kode = mysqli_real_escape_string($conn, $_POST['kode_obat']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama_obat']);
        $kategori = $_POST['kategori_id'];
        $satuan = $_POST['satuan_id'];
        $min_stok = $_POST['min_stok'];

        if (mysqli_query($conn, "UPDATE obat SET kode_obat='$kode', nama_obat='$nama', kategori_id='$kategori', satuan_id='$satuan', min_stok='$min_stok' WHERE id=$id")) {
            $_SESSION['success'] = "Data obat berhasil diedit.";
        } else {
            $_SESSION['error'] = "Gagal mengedit data obat.";
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        if (mysqli_query($conn, "DELETE FROM obat WHERE id=$id")) {
            $_SESSION['success'] = "Data obat berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Gagal menghapus data obat.";
        }
    }
    header("Location: index.php");
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
          ORDER BY o.id DESC LIMIT $limit OFFSET $offset";
$obat = mysqli_query($conn, $query);

$q_kategori = mysqli_query($conn, "SELECT * FROM kategori");
$q_satuan = mysqli_query($conn, "SELECT * FROM satuan");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Data Obat</h2>
    <?php if ($_SESSION['role'] == 'Petugas Gudang' || $_SESSION['role'] == 'Petugas Farmasi'): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Tambah Obat</button>
    <?php endif; ?>
</div>

<div class="card shadow mb-4">
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
                    <?php if ($_SESSION['role'] == 'Petugas Gudang' || $_SESSION['role'] == 'Petugas Farmasi'): ?>
                    <th width="15%">Aksi</th>
                    <?php endif; ?>
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
                    <td>
                        <?php if ($row['total_stok'] <= $row['min_stok']): ?>
                            <span class="badge bg-danger"><?= $row['total_stok']; ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= $row['total_stok']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['min_stok']; ?></td>
                    <?php if ($_SESSION['role'] == 'Petugas Gudang' || $_SESSION['role'] == 'Petugas Farmasi'): ?>
                    <td>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>"><i class="fas fa-edit"></i></button>
                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?');">
                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                
                <!-- Edit Modal -->
                <?php if ($_SESSION['role'] == 'Petugas Gudang' || $_SESSION['role'] == 'Petugas Farmasi'): ?>
                <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="" method="POST">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Obat</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    <div class="mb-3">
                                        <label>Kode Obat</label>
                                        <input type="text" class="form-control" name="kode_obat" value="<?= htmlspecialchars($row['kode_obat']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Nama Obat</label>
                                        <input type="text" class="form-control" name="nama_obat" value="<?= htmlspecialchars($row['nama_obat']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Kategori</label>
                                        <select name="kategori_id" class="form-select" required>
                                            <option value="">Pilih Kategori</option>
                                            <?php foreach ($q_kategori as $kat): ?>
                                                <option value="<?= $kat['id']; ?>" <?= $row['kategori_id'] == $kat['id'] ? 'selected' : ''; ?>><?= $kat['nama_kategori']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Satuan</label>
                                        <select name="satuan_id" class="form-select" required>
                                            <option value="">Pilih Satuan</option>
                                            <?php foreach ($q_satuan as $sat): ?>
                                                <option value="<?= $sat['id']; ?>" <?= $row['satuan_id'] == $sat['id'] ? 'selected' : ''; ?>><?= $sat['nama_satuan']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Min Stok</label>
                                        <input type="number" class="form-control" name="min_stok" value="<?= $row['min_stok']; ?>" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="edit" class="btn btn-primary">Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
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

<!-- Add Modal -->
<?php if ($_SESSION['role'] == 'Petugas Gudang' || $_SESSION['role'] == 'Petugas Farmasi'): ?>
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Obat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Kode Obat</label>
                        <input type="text" class="form-control" name="kode_obat" required>
                    </div>
                    <div class="mb-3">
                        <label>Nama Obat</label>
                        <input type="text" class="form-control" name="nama_obat" required>
                    </div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($q_kategori as $kat): ?>
                                <option value="<?= $kat['id']; ?>"><?= $kat['nama_kategori']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Satuan</label>
                        <select name="satuan_id" class="form-select" required>
                            <option value="">Pilih Satuan</option>
                            <?php foreach ($q_satuan as $sat): ?>
                                <option value="<?= $sat['id']; ?>"><?= $sat['nama_satuan']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Min Stok</label>
                        <input type="number" class="form-control" name="min_stok" value="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../layouts/footer.php'; ?>
