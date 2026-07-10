<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Petugas Gudang' && $_SESSION['role'] != 'Kepala Puskesmas')) {
    header("Location: ../../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'Petugas Gudang') {
    if (isset($_POST['add'])) {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
        if (mysqli_query($conn, "INSERT INTO kategori (nama_kategori) VALUES ('$nama')")) {
            $_SESSION['success'] = "Data kategori berhasil disimpan.";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data kategori.";
        }
    } elseif (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $nama = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
        if (mysqli_query($conn, "UPDATE kategori SET nama_kategori='$nama' WHERE id=$id")) {
            $_SESSION['success'] = "Data kategori berhasil diedit.";
        } else {
            $_SESSION['error'] = "Gagal mengedit data kategori.";
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        if (mysqli_query($conn, "DELETE FROM kategori WHERE id=$id")) {
            $_SESSION['success'] = "Data kategori berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Gagal menghapus data kategori.";
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
    $where = "WHERE nama_kategori LIKE '%$search%'";
}

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM kategori $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT * FROM kategori $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$kategori = mysqli_query($conn, $query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Data Kategori Obat</h2>
    <?php if ($_SESSION['role'] == 'Petugas Gudang'): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Tambah Kategori</button>
    <?php endif; ?>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-10 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control" placeholder="Cari nama kategori..." value="<?= htmlspecialchars($search) ?>">
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
                    <th>Nama Kategori</th>
                    <?php if ($_SESSION['role'] == 'Petugas Gudang'): ?>
                    <th width="15%">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($kategori)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $row['nama_kategori']; ?></td>
                    <?php if ($_SESSION['role'] == 'Petugas Gudang'): ?>
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
                <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="" method="POST">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Kategori</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    <div class="mb-3">
                                        <label>Nama Kategori</label>
                                        <input type="text" class="form-control" name="nama_kategori" value="<?= htmlspecialchars($row['nama_kategori']); ?>" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="edit" class="btn btn-primary">Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Kategori</label>
                        <input type="text" class="form-control" name="nama_kategori" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../layouts/footer.php'; ?>
