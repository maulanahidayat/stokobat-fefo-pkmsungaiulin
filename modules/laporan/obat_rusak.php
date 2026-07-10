<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE t.tujuan_pengeluaran = 'Obat Rusak' AND t.tgl_keluar BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
if ($search != '') {
    $where .= " AND (t.no_transaksi LIKE '%$search%' OR o.nama_obat LIKE '%$search%' OR o.kode_obat LIKE '%$search%' OR t.tujuan LIKE '%$search%' OR b.no_batch LIKE '%$search%')";
}

// 1. Total data for pagination
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total 
          FROM transaksi_keluar t 
          JOIN detail_keluar d ON t.id = d.transaksi_keluar_id 
          JOIN batch_obat b ON d.batch_id = b.id
          JOIN obat o ON d.obat_id = o.id
          $where");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

// 2. Main query
$query = "SELECT t.no_transaksi, t.tgl_keluar, t.tujuan AS alasan, t.foto, d.jumlah, o.nama_obat, o.kode_obat, b.no_batch, b.tgl_kadaluarsa, u.nama_lengkap, s.nama_satuan
          FROM transaksi_keluar t 
          JOIN detail_keluar d ON t.id = d.transaksi_keluar_id 
          JOIN batch_obat b ON d.batch_id = b.id
          JOIN obat o ON d.obat_id = o.id 
          LEFT JOIN satuan s ON o.satuan_id = s.id
          LEFT JOIN users u ON t.user_id = u.id
          $where
          ORDER BY t.tgl_keluar DESC, t.id DESC LIMIT $limit OFFSET $offset";
$items = mysqli_query($conn, $query);

// 3. Hitung Total Rusak Periode Ini (Kuantitas)
$q_total_periode = mysqli_query($conn, "
    SELECT COALESCE(SUM(d.jumlah), 0) as total 
    FROM detail_keluar d 
    JOIN transaksi_keluar t ON d.transaksi_keluar_id = t.id 
    JOIN batch_obat b ON d.batch_id = b.id
    JOIN obat o ON d.obat_id = o.id
    $where
");
$total_periode = mysqli_fetch_assoc($q_total_periode)['total'];

// 4. Hitung Total Kasus/Laporan Kerusakan Periode Ini (Frekuensi)
$q_total_kasus = mysqli_query($conn, "
    SELECT COUNT(DISTINCT t.id) as total
    FROM transaksi_keluar t
    JOIN detail_keluar d ON t.id = d.transaksi_keluar_id
    JOIN batch_obat b ON d.batch_id = b.id
    JOIN obat o ON d.obat_id = o.id
    $where
");
$total_kasus = mysqli_fetch_assoc($q_total_kasus)['total'];

// 5. Hitung Total Rusak Keseluruhan (Kuantitas)
$q_total_keseluruhan = mysqli_query($conn, "
    SELECT COALESCE(SUM(d.jumlah), 0) as total 
    FROM detail_keluar d 
    JOIN transaksi_keluar t ON d.transaksi_keluar_id = t.id 
    WHERE t.tujuan_pengeluaran = 'Obat Rusak'
");
$total_keseluruhan = mysqli_fetch_assoc($q_total_keseluruhan)['total'];

require_once '../../layouts/header.php';
require_once '../../layouts/sidebar.php';
?>

<!-- Custom Premium Styling Block -->
<style>
    /* Card Hover elevation */
    .premium-widget {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: none;
        position: relative;
        overflow: hidden;
    }
    .premium-widget:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
    }
    /* Glow effect on widgets */
    .premium-widget::before {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 80%);
        transform: rotate(30deg);
        transition: all 0.5s ease;
        opacity: 0;
    }
    .premium-widget:hover::before {
        opacity: 1;
        transform: rotate(35deg) translate(10%, 10%);
    }

    /* Gradient definitions using polished and energetic color palettes */
    .widget-grad-danger {
        background: linear-gradient(135deg, hsl(355, 80%, 58%), hsl(15, 80%, 55%)) !important;
    }
    .widget-grad-purple {
        background: linear-gradient(135deg, hsl(265, 55%, 55%), hsl(240, 50%, 50%)) !important;
    }
    .widget-grad-dark {
        background: linear-gradient(135deg, hsl(210, 20%, 35%), hsl(210, 25%, 20%)) !important;
    }

    /* Soft shadow details for table */
    .premium-table-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    /* Interactive Image Zooming */
    .hover-zoom-proof {
        transition: all 0.2s ease-in-out;
    }
    .hover-zoom-proof:hover {
        transform: scale(1.15);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* Print styling overrides */
    @media print {
        #sidebar, .navbar, .no-print, .btn, .alert, .pagination {
            display: none !important;
        }
        #content {
            margin: 0;
            padding: 0;
            width: 100% !important;
            box-shadow: none !important;
        }
        .premium-table-card {
            box-shadow: none !important;
        }
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .table-dark {
            background-color: #343a40 !important;
            color: #fff !important;
        }
        .table td, .table th {
            padding: 8px !important;
            border: 1px solid #dee2e6 !important;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2><i class="fas fa-file-invoice text-danger me-2"></i> Laporan Pengendalian Obat Rusak</h2>
    <div>
        <button onclick="window.print()" class="btn btn-primary shadow-sm"><i class="fas fa-print me-1"></i> Cetak Laporan</button>
    </div>
</div>

<!-- Header Cetak Resmi (Hanya muncul saat print) -->
<div class="text-center mb-4 d-none d-print-block">
    <h3 class="fw-bold mb-1">LAPORAN REKAPITULASI OBAT RUSAK</h3>
    <h5 class="text-muted mb-2">Puskesmas Sungai Ulin</h5>
    <p class="mb-0">Periode: <strong><?= date('d-m-Y', strtotime($tgl_mulai)); ?></strong> s/d <strong><?= date('d-m-Y', strtotime($tgl_selesai)); ?></strong></p>
    <hr style="border-top: 2px solid #000; margin-top: 15px;">
</div>

<h5 class="no-print mb-4 text-muted">Periode: <?= date('d-m-Y', strtotime($tgl_mulai)); ?> s/d <?= date('d-m-Y', strtotime($tgl_selesai)); ?></h5>

<!-- Premium Widgets Summary -->
<div class="row mb-4">
    <!-- Total Unit Rusak Periode -->
    <div class="col-md-4 mb-3">
        <div class="card premium-widget widget-grad-danger shadow text-white h-100">
            <div class="card-body py-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title text-uppercase opacity-75 fs-7 mb-1 fw-bold">Qty Rusak (Periode Ini)</h6>
                    <p class="card-text fs-1 fw-bold mb-0"><?= number_format($total_periode); ?></p>
                    <small class="opacity-75">Unit obat didepresiasi dari filter saat ini</small>
                </div>
                <div class="fs-1 opacity-50"><i class="fas fa-dumpster-fire fa-2x"></i></div>
            </div>
        </div>
    </div>
    
    <!-- Total Kasus Laporan -->
    <div class="col-md-4 mb-3">
        <div class="card premium-widget widget-grad-purple shadow text-white h-100">
            <div class="card-body py-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title text-uppercase opacity-75 fs-7 mb-1 fw-bold">Kasus Kerusakan (Periode Ini)</h6>
                    <p class="card-text fs-1 fw-bold mb-0"><?= number_format($total_kasus); ?></p>
                    <small class="opacity-75">Frekuensi laporan kerusakan obat</small>
                </div>
                <div class="fs-1 opacity-50"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
            </div>
        </div>
    </div>

    <!-- Akumulasi Keseluruhan -->
    <div class="col-md-4 mb-3">
        <div class="card premium-widget widget-grad-dark shadow text-white h-100">
            <div class="card-body py-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title text-uppercase opacity-75 fs-7 mb-1 fw-bold">Akumulasi Rusak (Total)</h6>
                    <p class="card-text fs-1 fw-bold mb-0"><?= number_format($total_keseluruhan); ?></p>
                    <small class="opacity-75">Keseluruhan unit terbuang dari awal pencatatan</small>
                </div>
                <div class="fs-1 opacity-50"><i class="fas fa-trash-restore-alt fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="card shadow-sm mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">Tanggal Mulai</label>
                <input type="text" name="tgl_mulai" class="form-control datepicker" value="<?= htmlspecialchars($tgl_mulai) ?>" placeholder="DD-MM-YYYY" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Tanggal Selesai</label>
                <input type="text" name="tgl_selesai" class="form-control datepicker" value="<?= htmlspecialchars($tgl_selesai) ?>" placeholder="DD-MM-YYYY" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="Cari kode obat, nama obat, no batch, atau alasan..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-search"></i> Cari Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table Card -->
<div class="card premium-table-card shadow-sm mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle text-center mb-0">
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
                        <th width="10%" class="no-print">Foto Bukti</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tgl_keluar'])); ?></td>
                        <td><code><?= $row['kode_obat']; ?></code></td>
                        <td class="text-start"><?= htmlspecialchars($row['nama_obat']); ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['no_batch']); ?></span></td>
                        <td><?= date('d-m-Y', strtotime($row['tgl_kadaluarsa'])); ?></td>
                        <td><strong class="text-danger"><?= number_format($row['jumlah']); ?></strong> <span class="small text-muted"><?= htmlspecialchars($row['nama_satuan']); ?></span></td>
                        <td class="text-start text-wrap" style="max-width: 250px;"><span class="fst-italic"><?= htmlspecialchars($row['alasan']); ?></span></td>
                        <td class="no-print">
                            <?php if ($row['foto']): ?>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#viewPhotoModal<?= $row['no_transaksi']; ?>">
                                    <img src="../../assets/img/obat_rusak/<?= $row['foto']; ?>" alt="Foto Bukti" class="img-thumbnail shadow-sm hover-zoom-proof" style="max-width: 45px; max-height: 45px; object-fit: cover; cursor: pointer;">
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
                                <span class="text-muted small"><i class="fas fa-image-slash opacity-50"></i> Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($items) == 0): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada laporan obat rusak ditemukan untuk periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (Hanya muncul saat render desktop, disembunyikan saat print) -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4 no-print">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link shadow-sm" href="?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link shadow-sm" href="?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link shadow-sm" href="?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Berikutnya <i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inisialisasi datepicker dengan format rapi
    flatpickr(".datepicker", {
        allowInput: true,
        altInput: true,
        altFormat: "d-m-Y",
        dateFormat: "Y-m-d"
    });
});
</script>

<?php require_once '../../layouts/footer.php'; ?>
