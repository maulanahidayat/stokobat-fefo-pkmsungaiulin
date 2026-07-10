        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h5>SIM FEFO Obat</h5>
                <small><?= $_SESSION['nama_lengkap']; ?> (<?= $_SESSION['role']; ?>)</small>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="<?= base_url('dashboard.php') ?>"><i class="fas fa-home me-2"></i> Dashboard</a>
                </li>
                
                <?php if ($_SESSION['role'] == 'Petugas Gudang' || $_SESSION['role'] == 'Kepala Puskesmas' || $_SESSION['role'] == 'Petugas Farmasi'): ?>
                <li>
                    <a href="#masterDataSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-database me-2"></i> Master Data</a>
                    <ul class="collapse list-unstyled" id="masterDataSubmenu">
                        <?php if ($_SESSION['role'] == 'Petugas Gudang'): ?>
                        <li>
                            <a href="<?= base_url('modules/kategori/index.php') ?>" class="ps-5"><i class="fas fa-tags me-2"></i> Kategori</a>
                        </li>
                        <li>
                            <a href="<?= base_url('modules/satuan/index.php') ?>" class="ps-5"><i class="fas fa-balance-scale me-2"></i> Satuan</a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= base_url('modules/obat/index.php') ?>" class="ps-5"><i class="fas fa-pills me-2"></i> Data Obat</a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'Petugas Gudang'): ?>
                <li>
                    <a href="<?= base_url('modules/masuk/index.php') ?>"><i class="fas fa-file-import me-2"></i> Transaksi Masuk</a>
                </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'Petugas Farmasi'): ?>
                <li>
                    <a href="<?= base_url('modules/keluar/index.php') ?>"><i class="fas fa-file-export me-2"></i> Transaksi Keluar (FEFO)</a>
                </li>
                <?php endif; ?>

                <li>
                    <a href="<?= base_url('modules/obat_rusak/index.php') ?>"><i class="fas fa-trash-alt me-2 text-danger"></i> Pengendalian Obat Rusak</a>
                </li>

                <li>
                    <a href="#laporanSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-chart-bar me-2"></i> Laporan</a>
                    <ul class="collapse list-unstyled" id="laporanSubmenu">
                        <li><a href="<?= base_url('modules/laporan/stok.php') ?>" class="ps-5">1. Obat</a></li>
                        <li><a href="<?= base_url('modules/laporan/batch.php') ?>" class="ps-5">2. Batch & ED</a></li>
                        <li><a href="<?= base_url('modules/laporan/hampir_ed.php') ?>" class="ps-5">3. Hampir ED</a></li>
                        <li><a href="<?= base_url('modules/laporan/ed.php') ?>" class="ps-5">4. Sudah ED</a></li>
                        <li><a href="<?= base_url('modules/laporan/masuk.php') ?>" class="ps-5">5. Transaksi Masuk</a></li>
                        <li><a href="<?= base_url('modules/laporan/keluar.php') ?>" class="ps-5">6. Transaksi Keluar</a></li>
                        <li><a href="<?= base_url('modules/laporan/mutasi.php') ?>" class="ps-5">7. Mutasi Stok</a></li>
                        <li><a href="<?= base_url('modules/laporan/kartu_stok.php') ?>" class="ps-5">8. Kartu Stok</a></li>
                        <li><a href="<?= base_url('modules/laporan/prediksi.php') ?>" class="ps-5">9. Prediksi Kebutuhan</a></li>
                        <li><a href="<?= base_url('modules/laporan/obat_rusak.php') ?>" class="ps-5">10. Obat Rusak</a></li>
                    </ul>
                </li>

                <li>
                    <a href="<?= base_url('logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </li>
            </ul>
        </nav>
        
        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary d-lg-none">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <ul class="nav navbar-nav ms-auto align-items-center">
                        <?php
                        // H-7: Expiring in <= 7 days
                        $q_notif_h7 = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
                        $notif_h7 = mysqli_fetch_assoc($q_notif_h7)['total'];

                        // H-30: Expiring in 8 to 30 days
                        $q_notif_h30 = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
                        $notif_h30 = mysqli_fetch_assoc($q_notif_h30)['total'];

                        // H-90: Expiring in 31 to 90 days
                        $q_notif_h90 = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)");
                        $notif_h90 = mysqli_fetch_assoc($q_notif_h90)['total'];

                        // Sudah Kadaluarsa
                        $q_notif_ed = mysqli_query($conn, "SELECT COUNT(*) as total FROM batch_obat WHERE stok > 0 AND tgl_kadaluarsa < CURDATE()");
                        $notif_ed = mysqli_fetch_assoc($q_notif_ed)['total'];
                        
                        $total_notif = $notif_h7 + $notif_h30 + $notif_h90 + $notif_ed;
                        ?>
                        <li class="nav-item dropdown me-4">
                            <a class="nav-link position-relative text-danger fw-bold" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi Stok & Expired">
                                <i class="fas fa-bell fa-lg"></i>
                                <?php if ($total_notif > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                    <?= $total_notif; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notifDropdown">
                                <li><h6 class="dropdown-header">Notifikasi Sistem</h6></li>
                                <?php if ($notif_h7 > 0): ?>
                                <li>
                                    <a class="dropdown-item text-danger fw-bold" href="<?= base_url('modules/laporan/hampir_ed.php?range=7') ?>">
                                        <i class="fas fa-exclamation-circle me-2"></i><?= $notif_h7; ?> Obat Expired H-7
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if ($notif_h30 > 0): ?>
                                <li>
                                    <a class="dropdown-item text-warning fw-bold" href="<?= base_url('modules/laporan/hampir_ed.php?range=30') ?>">
                                        <i class="fas fa-exclamation-triangle me-2"></i><?= $notif_h30; ?> Obat Expired H-30
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if ($notif_h90 > 0): ?>
                                <li>
                                    <a class="dropdown-item text-info fw-bold" href="<?= base_url('modules/laporan/hampir_ed.php?range=90') ?>">
                                        <i class="fas fa-info-circle me-2"></i><?= $notif_h90; ?> Obat Expired H-90
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($notif_ed > 0): ?>
                                <li>
                                    <a class="dropdown-item text-danger fw-bold" href="<?= base_url('modules/laporan/ed.php') ?>">
                                        <i class="fas fa-times-circle me-2"></i><?= $notif_ed; ?> Obat Sudah Expired
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($total_notif == 0): ?>
                                <li><span class="dropdown-item text-muted"><i class="fas fa-check-circle me-2 text-success"></i>Tidak ada notifikasi kritis</span></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <span class="text-muted">Sistem Manajemen Persediaan Obat Puskesmas Sungai Ulin</span>
                        </li>
                    </ul>
                </div>
            </nav>
