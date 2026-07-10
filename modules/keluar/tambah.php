<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Petugas Farmasi') {
    header("Location: ../../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tgl_keluar = $_POST['tgl_keluar'];
    $tujuan_pengeluaran = $_POST['tujuan_pengeluaran'];
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $user_id = $_SESSION['user_id'];
    $no_transaksi = 'TRK-' . time();

    mysqli_begin_transaction($conn);
    try {
        $obat_ids = $_POST['obat_id'];
        $jumlahs = $_POST['jumlah'];
        $batch_ids = isset($_POST['batch_id']) ? $_POST['batch_id'] : [];

        // Cek stok keseluruhan / batch terlebih dahulu
        for ($i = 0; $i < count($obat_ids); $i++) {
            $obat_id = $obat_ids[$i];
            $jumlah_diminta = $jumlahs[$i];

            if(!empty($obat_id) && !empty($jumlah_diminta)) {
                if ($tujuan_pengeluaran == 'Obat Rusak') {
                    $batch_id = $batch_ids[$i];
                    if (empty($batch_id)) {
                        throw new Exception("Batch obat harus dipilih untuk pengeluaran Obat Rusak.");
                    }
                    $q_batch_stok = mysqli_query($conn, "SELECT stok, no_batch FROM batch_obat WHERE id = $batch_id");
                    $batch_data = mysqli_fetch_assoc($q_batch_stok);
                    $stok_tersedia = $batch_data['stok'];
                    $no_batch = $batch_data['no_batch'];

                    if ($jumlah_diminta > $stok_tersedia) {
                        throw new Exception("Stok batch $no_batch tidak mencukupi. Diminta: $jumlah_diminta, Tersedia: $stok_tersedia");
                    }
                } else {
                    $q_total_stok = mysqli_query($conn, "SELECT COALESCE(SUM(stok), 0) as total FROM batch_obat WHERE obat_id = $obat_id AND tgl_kadaluarsa >= CURDATE()");
                    $total_stok = mysqli_fetch_assoc($q_total_stok)['total'];

                    if ($jumlah_diminta > $total_stok) {
                        throw new Exception("Stok obat ID $obat_id tidak mencukupi atau sudah kadaluarsa. Diminta: $jumlah_diminta, Tersedia: $total_stok");
                    }
                }
            }
        }

        // Handle file upload / camera capture
        $filename = null;
        if ($tujuan_pengeluaran == 'Obat Rusak') {
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
        }

        // Insert Transaksi
        $ins_trans = mysqli_query($conn, "INSERT INTO transaksi_keluar (no_transaksi, tgl_keluar, tujuan_pengeluaran, tujuan, foto, user_id) VALUES ('$no_transaksi', '$tgl_keluar', '$tujuan_pengeluaran', '$tujuan', " . ($filename ? "'$filename'" : "NULL") . ", $user_id)");
        if (!$ins_trans) {
            throw new Exception("Gagal menyimpan transaksi pengeluaran: " . mysqli_error($conn));
        }
        $trans_id = mysqli_insert_id($conn);

        // FEFO or Direct Batch Logic
        for ($i = 0; $i < count($obat_ids); $i++) {
            $obat_id = $obat_ids[$i];
            $jumlah_sisa = $jumlahs[$i];

            if(!empty($obat_id) && !empty($jumlah_sisa)) {
                if ($tujuan_pengeluaran == 'Obat Rusak') {
                    $batch_id = $batch_ids[$i];
                    // Langsung kurangi stok di batch terpilih (tanpa FEFO)
                    mysqli_query($conn, "UPDATE batch_obat SET stok = stok - $jumlah_sisa WHERE id = $batch_id");

                    // Insert ke detail keluar
                    mysqli_query($conn, "INSERT INTO detail_keluar (transaksi_keluar_id, obat_id, batch_id, jumlah) VALUES ($trans_id, $obat_id, $batch_id, $jumlah_sisa)");
                } else {
                    // Ambil batch dengan stok > 0 dan belum kadaluarsa, urut tanggal kadaluarsa paling awal (FEFO)
                    $q_batch = mysqli_query($conn, "SELECT * FROM batch_obat WHERE obat_id = $obat_id AND stok > 0 AND tgl_kadaluarsa >= CURDATE() ORDER BY tgl_kadaluarsa ASC");
                    
                    while ($b = mysqli_fetch_assoc($q_batch)) {
                        if ($jumlah_sisa <= 0) break;

                        $batch_id = $b['id'];
                        $stok_batch = $b['stok'];

                        if ($stok_batch >= $jumlah_sisa) {
                            // Stok batch cukup
                            $jumlah_diambil = $jumlah_sisa;
                            $jumlah_sisa = 0;
                        } else {
                            // Stok batch kurang, ambil semua stok batch ini, sisa lanjut ke batch berikutnya
                            $jumlah_diambil = $stok_batch;
                            $jumlah_sisa -= $stok_batch;
                        }

                        // Update stok di tabel batch
                        mysqli_query($conn, "UPDATE batch_obat SET stok = stok - $jumlah_diambil WHERE id = $batch_id");

                        // Insert ke detail keluar
                        mysqli_query($conn, "INSERT INTO detail_keluar (transaksi_keluar_id, obat_id, batch_id, jumlah) VALUES ($trans_id, $obat_id, $batch_id, $jumlah_diambil)");
                    }
                }
            }
        }
        
        mysqli_commit($conn);
        $_SESSION['success'] = "Transaksi pengeluaran berhasil disimpan.";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil obat dengan stok aktif (belum kadaluarsa) > 0 untuk transaksi FEFO (Pasien/Karyawan)
$q_obat_fefo = mysqli_query($conn, "
    SELECT o.id, o.kode_obat, o.nama_obat, o.satuan_id, SUM(b.stok) as total_stok, s.nama_satuan 
    FROM obat o 
    JOIN batch_obat b ON o.id = b.obat_id 
    LEFT JOIN satuan s ON o.satuan_id = s.id 
    WHERE b.tgl_kadaluarsa >= CURDATE()
    GROUP BY o.id 
    HAVING total_stok > 0
");
$obat_options_fefo = "";
while ($o = mysqli_fetch_assoc($q_obat_fefo)) {
    $obat_options_fefo .= "<option value='{$o['id']}'>{$o['kode_obat']} - {$o['nama_obat']} (Stok: {$o['total_stok']} {$o['nama_satuan']})</option>";
}

// Untuk Obat Rusak, ambil obat yang total stoknya > 0 (termasuk yang kadaluarsa)
$q_obat_all = mysqli_query($conn, "
    SELECT o.id, o.kode_obat, o.nama_obat, o.satuan_id, SUM(b.stok) as total_stok, s.nama_satuan 
    FROM obat o 
    JOIN batch_obat b ON o.id = b.obat_id 
    LEFT JOIN satuan s ON o.satuan_id = s.id 
    GROUP BY o.id 
    HAVING total_stok > 0
");
$obat_options_all = "";
while ($o = mysqli_fetch_assoc($q_obat_all)) {
    $obat_options_all .= "<option value='{$o['id']}'>{$o['kode_obat']} - {$o['nama_obat']} (Stok: {$o['total_stok']} {$o['nama_satuan']})</option>";
}

// Default dropdown menggunakan obat_options_fefo
$obat_options = $obat_options_fefo;

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Tambah Transaksi Keluar</h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error; ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form action="" method="POST" id="formTransaksiKeluar" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tanggal Keluar</label>
                    <input type="text" class="form-control datepicker" name="tgl_keluar" required value="<?= date('Y-m-d'); ?>" placeholder="DD-MM-YYYY">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tujuan Pengeluaran</label>
                    <select class="form-select" name="tujuan_pengeluaran" id="tujuanPengeluaran" required>
                        <option value="Pasien">Pasien</option>
                        <option value="Karyawan">Karyawan</option>
                        <option value="Obat Rusak">Obat Rusak</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" id="labelTujuan">Tujuan Pasien/Unit</label>
                    <input type="text" class="form-control" name="tujuan" id="inputTujuan" required placeholder="Masukkan nama poli, pasien, atau unit...">
                </div>
            </div>

            <!-- Section Foto Bukti khusus Obat Rusak -->
            <div id="photoSection" class="mb-3 border-top pt-3" style="display: none;">
                <label class="form-label fw-bold text-danger d-block"><i class="fas fa-camera me-1"></i> Foto Bukti Kerusakan</label>
                
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

            <div class="alert alert-info" id="infoFefo">
                <i class="fas fa-info-circle"></i> <strong>Info:</strong> Sistem akan otomatis mengurangi stok dari obat dengan tanggal kadaluarsa paling awal (metode FEFO).
            </div>

            <h5 class="mt-4 mb-3">Daftar Obat</h5>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Obat</th>
                        <th id="thBatch" style="display: none; width: 35%;">Batch Spesifik</th>
                        <th>Jumlah Diberikan</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                    <tr>
                        <td>
                            <select name="obat_id[]" class="form-select select2 select-obat" required>
                                <option value="">Pilih Obat</option>
                                <?= $obat_options; ?>
                            </select>
                        </td>
                        <td class="td-batch" style="display: none;">
                            <select name="batch_id[]" class="form-select select-batch select2-batch">
                                <option value="">Pilih Batch</option>
                            </select>
                        </td>
                        <td><input type="number" name="jumlah[]" class="form-control input-jumlah" min="1" required></td>
                        <td><button type="button" class="btn btn-danger btn-sm hapus-baris"><i class="fas fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-success btn-sm mb-3" id="tambahBaris"><i class="fas fa-plus"></i> Tambah Obat</button>
            <br>
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Proses Pengeluaran</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var obatOptionsFefo = `<?= $obat_options_fefo; ?>`;
    var obatOptionsAll = `<?= $obat_options_all; ?>`;

    // ==========================================
    // CAMERA / CAPTURE AND UPLOAD PHOTO DOM
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

    function initSelect2(elem) {
        elem.select2({
            theme: 'bootstrap-5'
        });
    }

    initSelect2($('.select2'));

    function reloadBatches(row) {
        var obatSelect = row.find('.select-obat');
        var batchSelect = row.find('.select-batch');
        var obatId = obatSelect.val();
        if (obatId) {
            $.ajax({
                url: 'get_batches.php',
                type: 'GET',
                data: { obat_id: obatId },
                success: function(response) {
                    batchSelect.html(response);
                    initSelect2(batchSelect);
                }
            });
        } else {
            batchSelect.html('<option value="">Pilih Batch</option>');
        }
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

    $('#tujuanPengeluaran').change(function() {
        var val = $(this).val();
        var isObatRusak = (val === 'Obat Rusak');
        var optionsHtml = isObatRusak ? obatOptionsAll : obatOptionsFefo;

        $('.select-obat').each(function() {
            var currentVal = $(this).val();
            $(this).html('<option value="">Pilih Obat</option>' + optionsHtml);
            $(this).val(currentVal).trigger('change');
        });

        if (isObatRusak) {
            $('#thBatch').show();
            $('.td-batch').show();
            $('.select-batch').prop('required', true);
            $('#labelTujuan').text('Keterangan / Penyebab Kerusakan');
            $('#inputTujuan').attr('placeholder', 'Masukkan penyebab kerusakan (cth: pecah, basah, dll)...');
            $('#infoFefo').html('<i class="fas fa-exclamation-triangle text-warning"></i> <strong>Peringatan Obat Rusak:</strong> Pengeluaran obat rusak akan mengurangi stok langsung pada batch spesifik yang dipilih (mengabaikan aturan FEFO otomatis).');
            $('#infoFefo').removeClass('alert-info').addClass('alert-warning');
            $('#photoSection').show();
            $('#detailBody tr').each(function() { reloadBatches($(this)); });
        } else {
            $('#thBatch').hide();
            $('.td-batch').hide();
            $('.select-batch').prop('required', false).val('').trigger('change');
            $('#labelTujuan').text('Tujuan Pasien/Unit');
            $('#inputTujuan').attr('placeholder', 'Masukkan nama poli, pasien, atau unit...');
            $('#infoFefo').html('<i class="fas fa-info-circle"></i> <strong>Info:</strong> Sistem akan otomatis mengurangi stok dari obat dengan tanggal kadaluarsa paling awal (metode FEFO).');
            $('#infoFefo').removeClass('alert-warning').addClass('alert-info');
            $('#photoSection').hide();
            stopCamera();
            if (clearPhotoBtn) clearPhotoBtn.click();
        }
    });

    $(document).on('change', '.select-obat', function() {
        if ($('#tujuanPengeluaran').val() === 'Obat Rusak') {
            reloadBatches($(this).closest('tr'));
        }
    });

    $('#tambahBaris').click(function() {
        var isObatRusak = $('#tujuanPengeluaran').val() === 'Obat Rusak';
        var displayStyle = isObatRusak ? '' : 'display: none;';
        var requiredAttr = isObatRusak ? 'required' : '';
        var activeObatOptions = isObatRusak ? obatOptionsAll : obatOptionsFefo;

        var html = `<tr>
                        <td>
                            <select name="obat_id[]" class="form-select select-obat select2-new" required>
                                <option value="">Pilih Obat</option>
                                ${activeObatOptions}
                            </select>
                        </td>
                        <td class="td-batch" style="${displayStyle}">
                            <select name="batch_id[]" class="form-select select-batch select2-batch-new" ${requiredAttr}>
                                <option value="">Pilih Batch</option>
                            </select>
                        </td>
                        <td><input type="number" name="jumlah[]" class="form-control input-jumlah" min="1" required></td>
                        <td><button type="button" class="btn btn-danger btn-sm hapus-baris"><i class="fas fa-trash"></i></button></td>
                    </tr>`;
        $('#detailBody').append(html);
        var newRow = $('#detailBody tr').last();
        initSelect2(newRow.find('.select2-new'));
        if (isObatRusak) initSelect2(newRow.find('.select2-batch-new'));
    });

    $(document).on('click', '.hapus-baris', function() {
        if ($('#detailBody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('Minimal satu obat harus diisi.');
        }
    });

    function initDatepicker() {
        flatpickr(".datepicker", { allowInput: true, altInput: true, altFormat: "d-m-Y", dateFormat: "Y-m-d" });
    }
    
    initDatepicker();

    if (inputFotoFile) {
        inputFotoFile.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File Terlalu Besar', text: 'Ukuran file foto maksimal adalah 2MB.' });
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreviewImg.src = e.target.result;
                    photoPreviewContainer.classList.remove('d-none');
                    if (inputFotoCaptured) inputFotoCaptured.value = '';
                }
                reader.readAsDataURL(file);
            }
        });
    }

    if (startCameraBtn) {
        startCameraBtn.addEventListener('click', async function() {
            try {
                cameraStatus.textContent = "Menghubungkan kamera...";
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false });
                video.srcObject = stream;
                video.classList.remove('d-none');
                cameraControls.classList.remove('d-none');
                startCameraBtn.classList.add('d-none');
                cameraStatus.textContent = "Kamera Aktif";
            } catch (err) {
                console.error("Gagal mengakses kamera: ", err);
                Swal.fire({ icon: 'error', title: 'Akses Kamera Gagal', text: 'Browser gagal mengakses kamera.' });
                cameraStatus.textContent = "Kamera non-aktif (gagal)";
            }
        });
    }

    if (stopCameraBtn) {
        stopCameraBtn.addEventListener('click', stopCamera);
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', function() {
            if (!stream) return;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataURL = canvas.toDataURL('image/jpeg', 0.85);
            inputFotoCaptured.value = dataURL;
            photoPreviewImg.src = dataURL;
            photoPreviewContainer.classList.remove('d-none');
            if (inputFotoFile) inputFotoFile.value = '';
            stopCamera();
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Foto diambil!', showConfirmButton: false, timer: 1000 });
        });
    }

    if (clearPhotoBtn) {
        clearPhotoBtn.addEventListener('click', function() {
            if (inputFotoFile) inputFotoFile.value = '';
            if (inputFotoCaptured) inputFotoCaptured.value = '';
            photoPreviewImg.src = '';
            photoPreviewContainer.classList.add('d-none');
        });
    }

    $('#formTransaksiKeluar').on('submit', function(e) {
        e.preventDefault();
        var tglKeluar = $('input[name="tgl_keluar"]').val();
        var tujuanPengeluaran = $('#tujuanPengeluaran').val();
        var tujuan = $('#inputTujuan').val();
        var rincianHtml = `
            <div style="text-align: left; font-size: 14px;">
                <p><strong>Tanggal:</strong> ${tglKeluar}</p>
                <p><strong>Kategori Tujuan:</strong> <span class="badge bg-primary">${tujuanPengeluaran}</span></p>
                <p><strong>Detail / Penerima:</strong> ${tujuan}</p>
                <hr>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Obat</th>
                            ${tujuanPengeluaran === 'Obat Rusak' ? '<th>Batch</th>' : ''}
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        $('#detailBody tr').each(function() {
            var obatText = $(this).find('.select-obat option:selected').text();
            var batchText = $(this).find('.select-batch option:selected').text();
            var jumlah = $(this).find('.input-jumlah').val();

            if (obatText && obatText !== 'Pilih Obat') {
                rincianHtml += `
                    <tr>
                        <td>${obatText}</td>
                        ${tujuanPengeluaran === 'Obat Rusak' ? '<td>' + (batchText || '-') + '</td>' : ''}
                        <td>${jumlah}</td>
                    </tr>
                `;
            }
        });

        rincianHtml += `
                    </tbody>
                </table>
            </div>
        `;

        Swal.fire({
            title: 'Konfirmasi Pengeluaran',
            html: 'Pastikan data transaksi berikut sudah benar:<br><br>' + rincianHtml,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Proses',
            cancelButtonText: 'Batal',
            width: '600px'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit native form after camera cleanup
                stopCamera();
                $('#formTransaksiKeluar')[0].submit();
            }
        });
    });
});
</script>

<?php require_once '../../layouts/footer.php'; ?>
