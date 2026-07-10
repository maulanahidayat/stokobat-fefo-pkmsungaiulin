<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

// Cek hak akses: Petugas Farmasi & Gudang bisa input, Kapus hanya bisa lihat
$can_edit = ($_SESSION['role'] == 'Petugas Farmasi' || $_SESSION['role'] == 'Petugas Gudang');

// Handle Post request to report damaged medicine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_rusak']) && $can_edit) {
    $tgl_keluar = $_POST['tgl_keluar'];
    $obat_id = (int)$_POST['obat_id'];
    $batch_id = (int)$_POST['batch_id'];
    $jumlah = (int)$_POST['jumlah'];
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);
    $user_id = $_SESSION['user_id'];
    $no_transaksi = 'TRK-RSK-' . time();

    mysqli_begin_transaction($conn);
    try {
        if ($jumlah <= 0) {
            throw new Exception("Jumlah obat rusak harus lebih dari 0.");
        }

        // Cek stok batch terpilih
        $q_batch = mysqli_query($conn, "SELECT stok, no_batch FROM batch_obat WHERE id = $batch_id AND obat_id = $obat_id");
        if (mysqli_num_rows($q_batch) == 0) {
            throw new Exception("Batch obat tidak valid atau tidak cocok dengan obat terpilih.");
        }
        
        $batch_data = mysqli_fetch_assoc($q_batch);
        $stok_tersedia = $batch_data['stok'];
        $no_batch = $batch_data['no_batch'];

        if ($jumlah > $stok_tersedia) {
            throw new Exception("Stok batch $no_batch tidak mencukupi. Diminta: $jumlah, Tersedia: $stok_tersedia");
        }

        // Handle file upload / camera capture
        $filename = null;
        $target_dir = "../../assets/img/obat_rusak/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // 1. Check direct camera captured base64
        if (!empty($_POST['foto_captured'])) {
            $foto_data = $_POST['foto_captured'];
            if (preg_match('/^data:image\/(\w+);base64,/', $foto_data, $type)) {
                $foto_data = substr($foto_data, strpos($foto_data, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    throw new Exception('Format foto kamera tidak didukung.');
                }
                $foto_data = base64_decode($foto_data);
                if ($foto_data === false) {
                    throw new Exception('Dekode foto kamera gagal.');
                }
                $filename = 'captured_' . uniqid() . '.' . $type;
                $target_file = $target_dir . $filename;
                if (file_put_contents($target_file, $foto_data) === false) {
                    throw new Exception('Gagal menyimpan foto kamera ke server.');
                }
            }
        } 
        // 2. Check file upload
        elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                throw new Exception('Format file gambar tidak didukung. Gunakan JPG, JPEG, atau PNG.');
            }
            $filename = 'uploaded_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                throw new Exception('Gagal menyimpan file foto yang diupload.');
            }
        }

        // Insert Transaksi Keluar bertipe Obat Rusak
        $ins_trans = mysqli_query($conn, "INSERT INTO transaksi_keluar (no_transaksi, tgl_keluar, tujuan_pengeluaran, tujuan, foto, user_id) VALUES ('$no_transaksi', '$tgl_keluar', 'Obat Rusak', '$alasan', " . ($filename ? "'$filename'" : "NULL") . ", $user_id)");
        if (!$ins_trans) {
            throw new Exception("Gagal membuat transaksi: " . mysqli_error($conn));
        }
        $trans_id = mysqli_insert_id($conn);

        // Kurangi stok di batch terpilih
        $upd_stok = mysqli_query($conn, "UPDATE batch_obat SET stok = stok - $jumlah WHERE id = $batch_id");
        if (!$upd_stok) {
            throw new Exception("Gagal mengurangi stok batch: " . mysqli_error($conn));
        }

        // Insert ke detail_keluar
        $ins_detail = mysqli_query($conn, "INSERT INTO detail_keluar (transaksi_keluar_id, obat_id, batch_id, jumlah) VALUES ($trans_id, $obat_id, $batch_id, $jumlah)");
        if (!$ins_detail) {
            throw new Exception("Gagal menyimpan detail transaksi: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        $_SESSION['success'] = "Obat rusak berhasil dilaporkan dan didepresiasi dari stok.";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Gagal melaporkan obat rusak: " . $e->getMessage();
    }
}

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';

// Pagination & Query listing
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE t.tujuan_pengeluaran = 'Obat Rusak'";
if ($search != '') {
    $where .= " AND (t.no_transaksi LIKE '%$search%' OR o.nama_obat LIKE '%$search%' OR t.tujuan LIKE '%$search%' OR b.no_batch LIKE '%$search%')";
}

// Hitung total data
$total_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM transaksi_keluar t 
    JOIN detail_keluar d ON t.id = d.transaksi_keluar_id 
    JOIN batch_obat b ON d.batch_id = b.id
    JOIN obat o ON d.obat_id = o.id
    $where
");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

// Query utama
$query = "
    SELECT t.no_transaksi, t.tgl_keluar, t.tujuan AS alasan, t.foto, d.jumlah, o.nama_obat, o.kode_obat, b.no_batch, b.tgl_kadaluarsa, u.nama_lengkap
    FROM transaksi_keluar t
    JOIN detail_keluar d ON t.id = d.transaksi_keluar_id
    JOIN batch_obat b ON d.batch_id = b.id
    JOIN obat o ON d.obat_id = o.id
    LEFT JOIN users u ON t.user_id = u.id
    $where
    ORDER BY t.tgl_keluar DESC, t.id DESC 
    LIMIT $limit OFFSET $offset
";
$items = mysqli_query($conn, $query);

// Total Akumulasi Kuantitas Obat Rusak
$q_total_qty = mysqli_query($conn, "
    SELECT COALESCE(SUM(d.jumlah), 0) as total 
    FROM detail_keluar d 
    JOIN transaksi_keluar t ON d.transaksi_keluar_id = t.id 
    WHERE t.tujuan_pengeluaran = 'Obat Rusak'
");
$total_qty_rusak = mysqli_fetch_assoc($q_total_qty)['total'];

// List obat untuk form report
$q_obat_form = mysqli_query($conn, "
    SELECT o.id, o.kode_obat, o.nama_obat, SUM(b.stok) as total_stok 
    FROM obat o 
    JOIN batch_obat b ON o.id = b.obat_id 
    GROUP BY o.id 
    HAVING total_stok > 0
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-prescription-bottle-alt text-danger me-2"></i> Pengendalian Obat Rusak</h2>
    <div>
        <?php if ($can_edit): ?>
        <button class="btn btn-danger shadow" data-bs-toggle="modal" data-bs-target="#reportModal">
            <i class="fas fa-exclamation-triangle"></i> Laporkan Obat Rusak
        </button>
        <?php endif; ?>
    </div>
</div>



<?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= $error; ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-danger shadow text-white">
            <div class="card-body py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase opacity-75">Akumulasi Obat Rusak</h6>
                        <p class="card-text fs-1 fw-bold"><?= number_format($total_qty_rusak); ?></p>
                        <small>Total unit obat yang telah didepresiasi dari persediaan</small>
                    </div>
                    <div class="fs-1 opacity-50"><i class="fas fa-trash-restore-alt fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow h-100 bg-white">
            <div class="card-body d-flex flex-column justify-content-center">
                <h5>Kebijakan Pengendalian Obat Rusak</h5>
                <p class="text-muted">
                    Fitur ini memungkinkan penyesuaian stok langsung pada <strong>batch spesifik</strong> obat yang rusak secara fisik (misalnya karena pecah, basah, terkontaminasi, atau rusak kemasannya). Tindakan ini akan secara permanen mengurangi persediaan obat bersangkutan untuk menjamin keakuratan stok fisik di puskesmas.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-center">
            <div class="col-md-10 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control" placeholder="Cari obat, no batch, atau penyebab kerusakan..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">No</th>
                        <th>Tgl Dilaporkan</th>
                        <th>Kode</th>
                        <th class="text-start">Nama Obat</th>
                        <th>No Batch</th>
                        <th>Expired</th>
                        <th>Qty Rusak</th>
                        <th class="text-start">Keterangan / Penyebab</th>
                        <th width="10%">Foto Bukti</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tgl_keluar'])); ?></td>
                        <td><?= $row['kode_obat']; ?></td>
                        <td class="text-start"><?= htmlspecialchars($row['nama_obat']); ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['no_batch']); ?></span></td>
                        <td><?= date('d-m-Y', strtotime($row['tgl_kadaluarsa'])); ?></td>
                        <td><strong class="text-danger"><?= number_format($row['jumlah']); ?></strong></td>
                        <td class="text-start italic"><?= htmlspecialchars($row['alasan']); ?></td>
                        <td>
                            <?php if ($row['foto']): ?>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#viewPhotoModal<?= $row['no_transaksi']; ?>">
                                    <img src="../../assets/img/obat_rusak/<?= $row['foto']; ?>" alt="Foto" class="img-thumbnail shadow-sm hover-zoom" style="max-width: 50px; max-height: 50px; object-fit: cover; cursor: pointer; transition: transform 0.2s;">
                                </a>
                                
                                <!-- Modal Preview Foto -->
                                <div class="modal fade" id="viewPhotoModal<?= $row['no_transaksi']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header bg-danger text-white py-2">
                                                <h6 class="modal-title fs-6"><i class="fas fa-image me-2"></i>Bukti Kerusakan: <?= htmlspecialchars($row['nama_obat']); ?></h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center p-2 bg-light">
                                                <img src="../../assets/img/obat_rusak/<?= $row['foto']; ?>" class="img-fluid rounded shadow" alt="Foto Obat Rusak" style="max-height: 500px;">
                                            </div>
                                            <div class="modal-footer bg-light py-1 justify-content-between">
                                                <span class="small text-muted"><i class="fas fa-barcode me-1"></i><?= $row['no_transaksi']; ?></span>
                                                <a href="../../assets/img/obat_rusak/<?= $row['foto']; ?>" target="_blank" class="btn btn-outline-danger btn-sm"><i class="fas fa-external-link-alt me-1"></i>Buka Penuh</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-image-slash opacity-50"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($items) == 0): ?>
                    <tr><td colspan="10" class="text-center text-muted">Belum ada laporan obat rusak.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
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

<!-- Modal Report Obat Rusak -->
<?php if ($can_edit): ?>
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <form action="" method="POST" id="formReportRusak" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="reportModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Laporkan Obat Rusak</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="report_rusak" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Pelaporan</label>
                        <input type="text" class="form-control datepicker" name="tgl_keluar" required value="<?= date('Y-m-d'); ?>" placeholder="DD-MM-YYYY">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Obat</label>
                        <select name="obat_id" id="modalObatSelect" class="form-select select2-modal" required style="width: 100%;">
                            <option value="">Pilih Obat</option>
                            <?php while ($o = mysqli_fetch_assoc($q_obat_form)): ?>
                                <option value="<?= $o['id']; ?>"><?= $o['kode_obat']; ?> - <?= $o['nama_obat']; ?> (Stok: <?= $o['total_stok']; ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Batch Spesifik</label>
                        <select name="batch_id" id="modalBatchSelect" class="form-select select2-modal" required style="width: 100%;">
                            <option value="">Pilih Batch</option>
                        </select>
                        <small class="text-muted text-info" id="batchInfoText" style="display: none;"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Rusak (QTY)</label>
                        <input type="number" name="jumlah" id="modalJumlahInput" class="form-control" min="1" required placeholder="Masukkan jumlah unit yang rusak...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan / Penyebab Kerusakan</label>
                        <textarea class="form-control" name="alasan" rows="3" required placeholder="Contoh: Pecah saat penataan, terkena tetesan air hujan, segel terbuka, dsb..."></textarea>
                    </div>

                    <div class="mb-3 border-top pt-3">
                        <label class="form-label fw-bold d-block text-danger"><i class="fas fa-camera me-1"></i> Foto Bukti Kerusakan</label>
                        
                        <!-- Tab Navigation for Camera vs Upload -->
                        <ul class="nav nav-pills mb-3" id="photoTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active btn-sm py-1 px-3" id="upload-tab" data-bs-toggle="pill" data-bs-target="#photoUploadTab" type="button" role="tab" aria-selected="true"><i class="fas fa-file-upload me-1"></i> Upload File</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link btn-sm py-1 px-3" id="camera-tab" data-bs-toggle="pill" data-bs-target="#photoCameraTab" type="button" role="tab" aria-selected="false"><i class="fas fa-video me-1"></i> Kamera Langsung</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="photoTabContent">
                            <!-- File Upload Tab -->
                            <div class="tab-pane fade show active" id="photoUploadTab" role="tabpanel" aria-labelledby="upload-tab">
                                <input type="file" name="foto" id="inputFotoFile" class="form-control" accept="image/*">
                                <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                            </div>
                            
                            <!-- Live Camera Tab -->
                            <div class="tab-pane fade" id="photoCameraTab" role="tabpanel" aria-labelledby="camera-tab">
                                <div class="text-center bg-light p-3 border rounded shadow-inner position-relative">
                                    <video id="webcamVideo" autoplay playsinline class="rounded border w-100 d-none bg-dark" style="max-height: 240px; transform: scaleX(-1); object-fit: cover;"></video>
                                    
                                    <div id="cameraControls" class="mt-2 d-none">
                                        <button type="button" class="btn btn-success btn-sm px-3" id="captureBtn"><i class="fas fa-camera me-1"></i> Ambil Foto</button>
                                        <button type="button" class="btn btn-secondary btn-sm px-3" id="stopCameraBtn"><i class="fas fa-times me-1"></i> Tutup Kamera</button>
                                    </div>
                                    
                                    <button type="button" class="btn btn-primary btn-sm px-4 py-2" id="startCameraBtn"><i class="fas fa-video me-1"></i> Aktifkan Kamera Device</button>
                                    <div id="cameraStatus" class="small text-muted mt-2">Kamera non-aktif</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Photo Preview -->
                        <div id="photoPreviewContainer" class="mt-3 text-center d-none bg-light p-2 border rounded">
                            <label class="form-label d-block text-start small fw-bold"><i class="fas fa-eye me-1"></i>Preview Foto Terpilih:</label>
                            <div class="position-relative d-inline-block">
                                <img id="photoPreviewImg" class="img-thumbnail rounded shadow-sm" style="max-height: 180px;" src="" alt="Preview">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle m-1 shadow" id="clearPhotoBtn" title="Hapus Foto" style="width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; padding: 0;"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        
                        <!-- Hidden input for Camera Captured base64 data -->
                        <input type="hidden" name="foto_captured" id="inputFotoCaptured">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt me-2"></i> Laporkan & Kurangi Stok</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Init flatpickr inside modal
    flatpickr(".datepicker", {
        allowInput: true,
        altInput: true,
        altFormat: "d-m-Y",
        dateFormat: "Y-m-d"
    });

    // Make select2 work in Bootstrap modal
    $('.select2-modal').select2({
        dropdownParent: $('#reportModal'),
        theme: 'bootstrap-5'
    });

    // Dynamic batch fetch when drug is selected
    $('#modalObatSelect').change(function() {
        var obatId = $(this).val();
        if (obatId) {
            $.ajax({
                url: '../keluar/get_batches.php',
                type: 'GET',
                data: { obat_id: obatId },
                success: function(response) {
                    $('#modalBatchSelect').html(response);
                    $('#modalBatchSelect').trigger('change');
                }
            });
        } else {
            $('#modalBatchSelect').html('<option value="">Pilih Batch</option>').trigger('change');
        }
    });

    // Batch details change
    $('#modalBatchSelect').change(function() {
        var selected = $(this).find('option:selected');
        var maxStok = selected.data('stok');
        if (maxStok) {
            $('#batchInfoText').text('Stok tersedia di batch ini: ' + maxStok).show();
            $('#modalJumlahInput').attr('max', maxStok);
        } else {
            $('#batchInfoText').hide();
            $('#modalJumlahInput').removeAttr('max');
        }
    });

    // ==========================================
    // CAMERA / CAPTURE AND UPLOAD PHOTO LOGIC
    // ==========================================
    let stream = null;
    const video = document.getElementById('webcamVideo');
    const captureBtn = document.getElementById('captureBtn');
    const startCameraBtn = document.getElementById('startCameraBtn');
    const stopCameraBtn = document.getElementById('stopCameraBtn');
    const cameraControls = document.getElementById('cameraControls');
    const cameraStatus = document.getElementById('cameraStatus');
    const photoPreviewContainer = document.getElementById('photoPreviewContainer');
    const photoPreviewImg = document.getElementById('photoPreviewImg');
    const clearPhotoBtn = document.getElementById('clearPhotoBtn');
    const inputFotoCaptured = document.getElementById('inputFotoCaptured');
    const inputFotoFile = document.getElementById('inputFotoFile');

    // Handle File Input selection preview
    if (inputFotoFile) {
        inputFotoFile.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Limit size to 2MB
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Terlalu Besar',
                        text: 'Ukuran file foto maksimal adalah 2MB.'
                    });
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreviewImg.src = e.target.result;
                    photoPreviewContainer.classList.remove('d-none');
                    // Clear any captured base64 photo
                    if (inputFotoCaptured) inputFotoCaptured.value = '';
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Start Camera Stream
    if (startCameraBtn) {
        startCameraBtn.addEventListener('click', async function() {
            try {
                cameraStatus.textContent = "Menghubungkan kamera...";
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "environment" }, 
                    audio: false 
                });
                video.srcObject = stream;
                video.classList.remove('d-none');
                cameraControls.classList.remove('d-none');
                startCameraBtn.classList.add('d-none');
                cameraStatus.textContent = "Kamera Aktif";
            } catch (err) {
                console.error("Gagal mengakses kamera: ", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Akses Kamera Gagal',
                    text: 'Browser gagal mengakses kamera. Pastikan izin kamera aktif dan menggunakan koneksi HTTPS/localhost.'
                });
                cameraStatus.textContent = "Kamera non-aktif (gagal)";
            }
        });
    }

    // Stop Camera helper
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        if (video) video.srcObject = null;
        if (video) video.classList.add('d-none');
        if (cameraControls) cameraControls.classList.add('d-none');
        if (startCameraBtn) startCameraBtn.classList.remove('d-none');
        if (cameraStatus) cameraStatus.textContent = "Kamera non-aktif";
    }

    // Stop Camera button click
    if (stopCameraBtn) {
        stopCameraBtn.addEventListener('click', stopCamera);
    }

    // Capture Frame
    if (captureBtn) {
        captureBtn.addEventListener('click', function() {
            if (!stream) return;
            
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            // Handle horizontal reflection (mirroring) for front camera
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const dataURL = canvas.toDataURL('image/jpeg', 0.85);
            inputFotoCaptured.value = dataURL;
            photoPreviewImg.src = dataURL;
            photoPreviewContainer.classList.remove('d-none');
            
            // Reset file input
            if (inputFotoFile) inputFotoFile.value = '';
            
            // Turn off camera after capture
            stopCamera();
            
            // Show success toast
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Foto diambil!',
                showConfirmButton: false,
                timer: 1000
            });
        });
    }

    // Clear Selected or Captured Photo
    if (clearPhotoBtn) {
        clearPhotoBtn.addEventListener('click', function() {
            if (inputFotoFile) inputFotoFile.value = '';
            if (inputFotoCaptured) inputFotoCaptured.value = '';
            photoPreviewImg.src = '';
            photoPreviewContainer.classList.add('d-none');
        });
    }

    // Turn off camera when modal is closed
    $('#reportModal').on('hidden.bs.modal', function () {
        stopCamera();
    });

    // ==========================================
    // FORM SUBMISSION CONFIRMATION
    // ==========================================
    $('#formReportRusak').submit(function(e) {
        e.preventDefault();
        var form = this;

        var selectedObat = $('#modalObatSelect option:selected').text();
        var selectedBatch = $('#modalBatchSelect option:selected').text();
        var jumlah = $('#modalJumlahInput').val();

        Swal.fire({
            title: 'Apakah Anda Yakin?',
            text: "Laporan obat rusak akan langsung memotong stok sebanyak " + jumlah + " unit dari batch '" + selectedBatch + "' pada obat '" + selectedObat + "'!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Laporkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
<?php endif; ?>

<?php require_once '../../layouts/footer.php'; ?>
