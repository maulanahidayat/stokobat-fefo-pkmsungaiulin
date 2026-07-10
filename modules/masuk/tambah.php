<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Petugas Gudang') {
    header("Location: ../../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tgl_masuk = $_POST['tgl_masuk'];
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
    $user_id = $_SESSION['user_id'];
    $no_transaksi = 'TRM-' . time();

    mysqli_begin_transaction($conn);
    try {
        // Insert Transaksi
        mysqli_query($conn, "INSERT INTO transaksi_masuk (no_transaksi, tgl_masuk, supplier, user_id) VALUES ('$no_transaksi', '$tgl_masuk', '$supplier', $user_id)");
        $trans_id = mysqli_insert_id($conn);

        // Loop detail
        $obat_ids = $_POST['obat_id'];
        $batches = $_POST['no_batch'];
        $tgl_eds = $_POST['tgl_kadaluarsa'];
        $jumlahs = $_POST['jumlah'];

        for ($i = 0; $i < count($obat_ids); $i++) {
            $obat_id = $obat_ids[$i];
            $no_batch = mysqli_real_escape_string($conn, $batches[$i]);
            $tgl_ed = $tgl_eds[$i];
            $jumlah = $jumlahs[$i];

            if(!empty($obat_id) && !empty($no_batch) && !empty($jumlah)) {
                // Insert detail
                mysqli_query($conn, "INSERT INTO detail_masuk (transaksi_masuk_id, obat_id, no_batch, tgl_kadaluarsa, jumlah) VALUES ($trans_id, $obat_id, '$no_batch', '$tgl_ed', $jumlah)");

                // Cek atau buat batch
                $cek_batch = mysqli_query($conn, "SELECT id FROM batch_obat WHERE obat_id = $obat_id AND no_batch = '$no_batch'");
                if (mysqli_num_rows($cek_batch) > 0) {
                    $b = mysqli_fetch_assoc($cek_batch);
                    $b_id = $b['id'];
                    mysqli_query($conn, "UPDATE batch_obat SET stok = stok + $jumlah WHERE id = $b_id");
                } else {
                    mysqli_query($conn, "INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES ($obat_id, '$no_batch', '$tgl_ed', $jumlah)");
                }
            }
        }
        mysqli_commit($conn);
        $_SESSION['success'] = "Transaksi masuk berhasil disimpan.";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

$q_obat = mysqli_query($conn, "SELECT * FROM obat");
$obat_options = "";
while ($o = mysqli_fetch_assoc($q_obat)) {
    $obat_options .= "<option value='{$o['id']}'>{$o['kode_obat']} - {$o['nama_obat']}</option>";
}

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Tambah Transaksi Masuk</h2>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error; ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form action="" method="POST" id="formTransaksiMasuk">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Tanggal Masuk</label>
                    <input type="text" class="form-control datepicker" name="tgl_masuk" required value="<?= date('Y-m-d'); ?>" placeholder="DD-MM-YYYY">
                </div>
                <div class="col-md-6">
                    <label>Supplier / Asal</label>
                    <input type="text" class="form-control" name="supplier" required>
                </div>
            </div>

            <h5 class="mt-4 mb-3">Detail Obat</h5>
            <table class="table table-bordered" id="tabelDetail">
                <thead class="table-light">
                    <tr>
                        <th>Obat</th>
                        <th>No Batch</th>
                        <th>Tgl Expired</th>
                        <th>Jumlah</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                    <tr>
                        <td>
                            <select name="obat_id[]" class="form-select" required>
                                <option value="">Pilih Obat</option>
                                <?= $obat_options; ?>
                            </select>
                        </td>
                        <td><input type="text" name="no_batch[]" class="form-control" required></td>
                        <td><input type="text" name="tgl_kadaluarsa[]" class="form-control datepicker" required placeholder="DD-MM-YYYY"></td>
                        <td><input type="number" name="jumlah[]" class="form-control" min="1" required></td>
                        <td><button type="button" class="btn btn-danger btn-sm hapus-baris"><i class="fas fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-success btn-sm mb-3" id="tambahBaris"><i class="fas fa-plus"></i> Tambah Obat</button>
            <br>
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Simpan Transaksi</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tambahBaris').click(function() {
        var html = `<tr>
                        <td>
                            <select name="obat_id[]" class="form-select" required>
                                <option value="">Pilih Obat</option>
                                <?= $obat_options; ?>
                            </select>
                        </td>
                        <td><input type="text" name="no_batch[]" class="form-control" required></td>
                        <td><input type="text" name="tgl_kadaluarsa[]" class="form-control datepicker" required placeholder="DD-MM-YYYY"></td>
                        <td><input type="number" name="jumlah[]" class="form-control" min="1" required></td>
                        <td><button type="button" class="btn btn-danger btn-sm hapus-baris"><i class="fas fa-trash"></i></button></td>
                    </tr>`;
        $('#detailBody').append(html);
        initDatepicker();
    });

    $(document).on('click', '.hapus-baris', function() {
        if ($('#detailBody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('Minimal satu obat harus diisi.');
        }
    });

    // Init datepicker
    function initDatepicker() {
        flatpickr(".datepicker", {
            allowInput: true,
            altInput: true,
            altFormat: "d-m-Y",
            dateFormat: "Y-m-d"
        });
    }
    
    initDatepicker();

    $('#formTransaksiMasuk').on('submit', function(e) {
        e.preventDefault();

        // Ambil data untuk rincian
        var tglMasuk = $('input[name="tgl_masuk"]').val();
        var supplier = $('input[name="supplier"]').val();
        
        var rincianHtml = `
            <div style="text-align: left; font-size: 14px;">
                <p><strong>Tanggal:</strong> ${tglMasuk}</p>
                <p><strong>Supplier:</strong> ${supplier}</p>
                <hr>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Obat</th>
                            <th>Batch</th>
                            <th>Expired</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        $('#detailBody tr').each(function() {
            var obatText = $(this).find('select[name="obat_id[]"] option:selected').text();
            var batch = $(this).find('input[name="no_batch[]"]').val();
            var expired = $(this).find('input[name="tgl_kadaluarsa[]"]').val();
            var jumlah = $(this).find('input[name="jumlah[]"]').val();

            if (obatText && obatText !== 'Pilih Obat') {
                rincianHtml += `
                    <tr>
                        <td>${obatText}</td>
                        <td>${batch}</td>
                        <td>${expired}</td>
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
            title: 'Konfirmasi Transaksi Masuk',
            html: 'Pastikan data berikut sudah benar:<br><br>' + rincianHtml,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal',
            width: '600px'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form
                $('#formTransaksiMasuk')[0].submit();
            }
        });
    });
});
</script>

<?php require_once '../../layouts/footer.php'; ?>
