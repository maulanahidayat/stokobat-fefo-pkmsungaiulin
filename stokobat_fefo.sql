-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 10, 2026 at 08:23 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stokobat_fefo`
--

-- --------------------------------------------------------

--
-- Table structure for table `batch_obat`
--

CREATE TABLE `batch_obat` (
  `id` int NOT NULL,
  `obat_id` int NOT NULL,
  `no_batch` varchar(50) NOT NULL,
  `tgl_kadaluarsa` date NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `batch_obat`
--

INSERT INTO `batch_obat` (`id`, `obat_id`, `no_batch`, `tgl_kadaluarsa`, `stok`, `created_at`) VALUES
(1, 1, 'O2300055240001', '2026-07-01', 40, '2026-05-23 14:31:06'),
(2, 2, 'O2300058240001', '2028-01-27', 68, '2026-05-25 13:13:50'),
(3, 2, 'O2300058240002', '2026-12-23', 33, '2026-05-25 13:13:50'),
(4, 3, 'O2300193240002', '2029-05-04', 300, '2026-05-25 13:15:03'),
(5, 3, 'O2300193240003', '2028-05-12', 250, '2026-05-25 13:15:03'),
(6, 4, 'O2300042240001', '2026-06-02', 50, '2026-05-25 13:49:58'),
(7, 4, 'O2300042240005', '2025-05-09', 12, '2026-05-25 13:49:58'),
(8, 5, 'O2400103240001', '2028-05-05', 774, '2026-05-25 13:49:58'),
(9, 6, 'O2300050240001', '2026-07-10', 48, '2026-05-25 15:02:51'),
(10, 6, 'O2300050240002', '2026-08-10', 900, '2026-05-25 15:02:51'),
(11, 7, 'O2300194250003', '2027-06-30', 300, '2026-05-25 15:02:51'),
(12, 2, 'O2300185240001', '2026-07-17', 50, '2026-06-21 07:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `detail_keluar`
--

CREATE TABLE `detail_keluar` (
  `id` int NOT NULL,
  `transaksi_keluar_id` int NOT NULL,
  `obat_id` int NOT NULL,
  `batch_id` int NOT NULL,
  `jumlah` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_keluar`
--

INSERT INTO `detail_keluar` (`id`, `transaksi_keluar_id`, `obat_id`, `batch_id`, `jumlah`) VALUES
(1, 1, 1, 1, 10),
(2, 2, 1, 1, 3),
(3, 3, 1, 1, 2),
(4, 4, 5, 8, 5),
(5, 5, 1, 1, 3),
(6, 6, 6, 9, 2),
(7, 7, 1, 1, 3),
(8, 8, 1, 1, 5),
(9, 9, 2, 3, 6),
(10, 10, 1, 1, 21),
(11, 11, 2, 3, 21),
(12, 12, 5, 8, 21),
(13, 13, 2, 2, 2),
(14, 14, 1, 1, 3),
(15, 15, 1, 1, 5),
(16, 16, 1, 1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `detail_masuk`
--

CREATE TABLE `detail_masuk` (
  `id` int NOT NULL,
  `transaksi_masuk_id` int NOT NULL,
  `obat_id` int NOT NULL,
  `no_batch` varchar(50) NOT NULL,
  `tgl_kadaluarsa` date NOT NULL,
  `jumlah` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_masuk`
--

INSERT INTO `detail_masuk` (`id`, `transaksi_masuk_id`, `obat_id`, `no_batch`, `tgl_kadaluarsa`, `jumlah`) VALUES
(1, 1, 1, 'O2300055240001', '2026-07-01', 50),
(2, 1, 1, 'O2300055240001', '2026-08-13', 50),
(3, 2, 2, 'O2300058240001', '2028-01-27', 70),
(4, 2, 2, 'O2300058240002', '2026-12-23', 60),
(5, 3, 3, 'O2300193240002', '2029-05-04', 300),
(6, 3, 3, 'O2300193240003', '2028-05-12', 250),
(7, 4, 4, 'O2300042240001', '2026-06-02', 50),
(8, 4, 4, 'O2300042240005', '2025-05-09', 12),
(9, 4, 5, 'O2400103240001', '2028-05-05', 800),
(10, 5, 6, 'O2300050240001', '2026-07-10', 50),
(11, 5, 6, 'O2300050240002', '2026-08-10', 900),
(12, 5, 7, 'O2300194250003', '2027-06-30', 300),
(13, 6, 2, 'O2300185240001', '2026-07-17', 50);

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`) VALUES
(1, 'ALKES HABIS PAKAI'),
(2, 'ALKES HABIS PAKAI'),
(3, 'DARAH, AS. URAT KOLESTEROL'),
(4, 'VITAMIN DAN MINERAL'),
(5, 'VITAMIN DAN MINERAL'),
(8, 'SISTEM PENCERNAAN');

-- --------------------------------------------------------

--
-- Table structure for table `obat`
--

CREATE TABLE `obat` (
  `id` int NOT NULL,
  `kode_obat` varchar(20) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `kategori_id` int DEFAULT NULL,
  `satuan_id` int DEFAULT NULL,
  `min_stok` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `obat`
--

INSERT INTO `obat` (`id`, `kode_obat`, `nama_obat`, `kategori_id`, `satuan_id`, `min_stok`, `created_at`) VALUES
(1, 'O2300055', 'MASKER', 1, 1, 100, '2026-05-23 14:23:56'),
(2, 'O2300058', 'SARUNG TANGAN NON STERIL', 2, 2, 200, '2026-05-23 14:23:56'),
(3, 'O2300193', 'AMLODIPIN 10 MG TAB', 3, 3, 100, '2026-05-23 14:23:56'),
(4, 'O2300042', 'VITAMIN B KOMPLEKS TABLET', 4, 3, 800, '2026-05-23 14:23:56'),
(5, 'O2400103', 'Piridoksin Hcl Tablet 10 Mg /Vitamin B6', 5, 3, 800, '2026-05-23 14:23:56'),
(6, 'O2300050', 'METFORMIN HCL TAB 500 MG', 3, 3, 50, '2026-05-25 13:51:21'),
(7, 'O2300194', 'AMLODIPIN 5 MG TAB', 3, 3, 300, '2026-05-25 13:52:15'),
(8, 'O2500059', 'BENEURON', 4, 3, 320, '2026-06-21 07:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `satuan`
--

CREATE TABLE `satuan` (
  `id` int NOT NULL,
  `nama_satuan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `satuan`
--

INSERT INTO `satuan` (`id`, `nama_satuan`) VALUES
(1, 'Lembar'),
(2, 'Lembar'),
(3, 'Tablet'),
(6, 'Tablet');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_keluar`
--

CREATE TABLE `transaksi_keluar` (
  `id` int NOT NULL,
  `no_transaksi` varchar(50) NOT NULL,
  `tgl_keluar` date NOT NULL,
  `tujuan_pengeluaran` enum('Pasien','Karyawan','Obat Rusak') NOT NULL DEFAULT 'Pasien',
  `tujuan` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi_keluar`
--

INSERT INTO `transaksi_keluar` (`id`, `no_transaksi`, `tgl_keluar`, `tujuan_pengeluaran`, `tujuan`, `foto`, `user_id`, `created_at`) VALUES
(1, 'TRK-1779546802', '2026-05-23', 'Pasien', 'Poli Umum', NULL, 3, '2026-05-23 14:33:22'),
(2, 'TRK-RSK-1779679933', '2026-05-25', 'Obat Rusak', 'kotor', NULL, 3, '2026-05-25 03:32:13'),
(3, 'TRK-1779683081', '2026-05-25', 'Obat Rusak', 'lepas masker', NULL, 3, '2026-05-25 04:24:41'),
(4, 'TRK-1779721417', '2026-05-25', 'Karyawan', 'maulana', NULL, 3, '2026-05-25 15:03:37'),
(5, 'TRK-1779757155', '2026-05-26', 'Karyawan', 'barak', NULL, 3, '2026-05-26 00:59:16'),
(6, 'TRK-1779776548', '2026-05-26', 'Pasien', 'Poli Umum', NULL, 3, '2026-05-26 06:22:28'),
(7, 'TRK-1779776671', '2026-05-26', 'Pasien', 'ruang gigi', NULL, 3, '2026-05-26 06:24:31'),
(8, 'TRK-1779776969', '2026-05-26', 'Pasien', 'poli gigi', NULL, 3, '2026-05-26 06:29:29'),
(9, 'TRK-RSK-1779777128', '2026-05-26', 'Obat Rusak', 'robek', NULL, 3, '2026-05-26 06:32:08'),
(10, 'TRK-1779810219', '2026-05-26', 'Pasien', 'poli umum', NULL, 3, '2026-05-26 15:43:39'),
(11, 'TRK-1779810231', '2026-05-26', 'Karyawan', 'poli umum', NULL, 3, '2026-05-26 15:43:51'),
(12, 'TRK-1779810514', '2026-05-26', 'Pasien', 'poli umum', NULL, 3, '2026-05-26 15:48:34'),
(13, 'TRK-RSK-1781019047', '2026-06-09', 'Obat Rusak', 'pecah', 'captured_6a2831a7af3ff.jpeg', 2, '2026-06-09 15:30:47'),
(14, 'TRK-1782050938', '2026-06-21', 'Pasien', 'poli umum', NULL, 3, '2026-06-21 14:08:58'),
(15, 'TRK-RSK-1782052205', '2026-06-21', 'Obat Rusak', 'terkena makanan', NULL, 3, '2026-06-21 14:30:05'),
(16, 'TRK-1782052434', '2026-06-21', 'Obat Rusak', 'poli umum', 'captured_6a37f652e8b0e.jpeg', 3, '2026-06-21 14:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_masuk`
--

CREATE TABLE `transaksi_masuk` (
  `id` int NOT NULL,
  `no_transaksi` varchar(50) NOT NULL,
  `tgl_masuk` date NOT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi_masuk`
--

INSERT INTO `transaksi_masuk` (`id`, `no_transaksi`, `tgl_masuk`, `supplier`, `user_id`, `created_at`) VALUES
(1, 'TRM-1779546666', '2026-05-23', 'APBD Dinas Kesehatan', 2, '2026-05-23 14:31:06'),
(2, 'TRM-1779714830', '2026-05-25', 'APBD Dinas Kesehatan', 2, '2026-05-25 13:13:50'),
(3, 'TRM-1779714903', '2026-05-25', 'APBD Dinas Kesehatan', 2, '2026-05-25 13:15:03'),
(4, 'TRM-1779716998', '2026-05-25', 'APBD Dinas Kesehatan', 2, '2026-05-25 13:49:58'),
(5, 'TRM-1779721371', '2026-05-25', 'APBD Dinas Kesehatan', 2, '2026-05-25 15:02:51'),
(6, 'TRM-1782025493', '2026-06-21', 'APBD Dinas Kesehatan', 2, '2026-06-21 07:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('Kepala Puskesmas','Petugas Gudang','Petugas Farmasi') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'kapus', '778c847283be052a986149fbb5597884', 'Kepala Puskesmas', 'Kepala Puskesmas', '2026-03-13 12:26:32'),
(2, 'gudang', 'cbb7449d78314665f9e7c7dd0a18a68a', 'Apt Akbar (Gudang)', 'Petugas Gudang', '2026-03-13 12:26:32'),
(3, 'farmasi', 'ab5b5f8e9b15685db78734f9dbaa2b44', 'Nadya (Farmasi)', 'Petugas Farmasi', '2026-03-13 12:26:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batch_obat`
--
ALTER TABLE `batch_obat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `obat_id` (`obat_id`);

--
-- Indexes for table `detail_keluar`
--
ALTER TABLE `detail_keluar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_keluar_id` (`transaksi_keluar_id`),
  ADD KEY `obat_id` (`obat_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `detail_masuk`
--
ALTER TABLE `detail_masuk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_masuk_id` (`transaksi_masuk_id`),
  ADD KEY `obat_id` (`obat_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_obat` (`kode_obat`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `satuan_id` (`satuan_id`);

--
-- Indexes for table `satuan`
--
ALTER TABLE `satuan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi_keluar`
--
ALTER TABLE `transaksi_keluar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_transaksi` (`no_transaksi`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaksi_masuk`
--
ALTER TABLE `transaksi_masuk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_transaksi` (`no_transaksi`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `batch_obat`
--
ALTER TABLE `batch_obat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `detail_keluar`
--
ALTER TABLE `detail_keluar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `detail_masuk`
--
ALTER TABLE `detail_masuk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `satuan`
--
ALTER TABLE `satuan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaksi_keluar`
--
ALTER TABLE `transaksi_keluar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `transaksi_masuk`
--
ALTER TABLE `transaksi_masuk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batch_obat`
--
ALTER TABLE `batch_obat`
  ADD CONSTRAINT `batch_obat_ibfk_1` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `detail_keluar`
--
ALTER TABLE `detail_keluar`
  ADD CONSTRAINT `detail_keluar_ibfk_1` FOREIGN KEY (`transaksi_keluar_id`) REFERENCES `transaksi_keluar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_keluar_ibfk_2` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_keluar_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `batch_obat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `detail_masuk`
--
ALTER TABLE `detail_masuk`
  ADD CONSTRAINT `detail_masuk_ibfk_1` FOREIGN KEY (`transaksi_masuk_id`) REFERENCES `transaksi_masuk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_masuk_ibfk_2` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `obat`
--
ALTER TABLE `obat`
  ADD CONSTRAINT `obat_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `obat_ibfk_2` FOREIGN KEY (`satuan_id`) REFERENCES `satuan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaksi_keluar`
--
ALTER TABLE `transaksi_keluar`
  ADD CONSTRAINT `transaksi_keluar_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaksi_masuk`
--
ALTER TABLE `transaksi_masuk`
  ADD CONSTRAINT `transaksi_masuk_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
