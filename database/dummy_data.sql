-- Kategori
INSERT INTO kategori (nama_kategori) VALUES ('Analgesik'), ('Antibiotik'), ('Vitamin'), ('Antasida'), ('Antihistamin');

-- Satuan
INSERT INTO satuan (nama_satuan) VALUES ('Tablet'), ('Kapsul'), ('Botol'), ('Ampul'), ('Sachet');

-- Obat
-- 1
INSERT INTO obat (kode_obat, nama_obat, kategori_id, satuan_id, min_stok) VALUES ('OBT-001', 'Paracetamol 500mg', 1, 1, 100);
-- 2
INSERT INTO obat (kode_obat, nama_obat, kategori_id, satuan_id, min_stok) VALUES ('OBT-002', 'Amoxicillin 500mg', 2, 2, 50);
-- 3
INSERT INTO obat (kode_obat, nama_obat, kategori_id, satuan_id, min_stok) VALUES ('OBT-003', 'Vitamin C 50mg', 3, 1, 200);
-- 4
INSERT INTO obat (kode_obat, nama_obat, kategori_id, satuan_id, min_stok) VALUES ('OBT-004', 'Antasida Doen', 4, 1, 150);
-- 5
INSERT INTO obat (kode_obat, nama_obat, kategori_id, satuan_id, min_stok) VALUES ('OBT-005', 'CTM 4mg', 5, 1, 100);

-- Batch Obat
-- OBT-001 (Paracetamol)
INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES (1, 'BATCH-A001', '2026-12-31', 500);
INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES (1, 'BATCH-A002', '2027-05-30', 300);
-- OBT-002 (Amoxicillin)
INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES (2, 'BATCH-B001', '2026-06-15', 200);
INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES (2, 'BATCH-B002', '2028-01-10', 500);
-- OBT-003 (Vitamin C) - Hampir ED (<= 90 hari, sekarang 13 Maret 2026)
INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES (3, 'BATCH-C001', '2026-04-20', 100); 
-- OBT-004 (Antasida) - Sudah ED
INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES (4, 'BATCH-D001', '2025-10-10', 50); 
-- OBT-005 (CTM)
INSERT INTO batch_obat (obat_id, no_batch, tgl_kadaluarsa, stok) VALUES (5, 'BATCH-E001', '2028-11-10', 300); 

-- Transaksi Masuk
-- user_id 2 adalah Petugas Gudang
INSERT INTO transaksi_masuk (no_transaksi, tgl_masuk, supplier, user_id) VALUES ('TRM-1710000001', '2026-01-15', 'PT Kimia Farma', 2);
INSERT INTO transaksi_masuk (no_transaksi, tgl_masuk, supplier, user_id) VALUES ('TRM-1710000002', '2026-02-20', 'PT Kalbe Farma', 2);

-- Detail Masuk
-- dari transaksi 1
INSERT INTO detail_masuk (transaksi_masuk_id, obat_id, no_batch, tgl_kadaluarsa, jumlah) VALUES (1, 1, 'BATCH-A001', '2026-12-31', 500);
INSERT INTO detail_masuk (transaksi_masuk_id, obat_id, no_batch, tgl_kadaluarsa, jumlah) VALUES (1, 2, 'BATCH-B001', '2026-06-15', 300);
-- dari transaksi 2
INSERT INTO detail_masuk (transaksi_masuk_id, obat_id, no_batch, tgl_kadaluarsa, jumlah) VALUES (2, 3, 'BATCH-C001', '2026-04-20', 150);

-- Transaksi Keluar
-- user_id 3 adalah Petugas Farmasi
INSERT INTO transaksi_keluar (no_transaksi, tgl_keluar, tujuan, user_id) VALUES ('TRK-1710000001', '2026-03-01', 'Poli Umum', 3);
INSERT INTO transaksi_keluar (no_transaksi, tgl_keluar, tujuan, user_id) VALUES ('TRK-1710000002', '2026-03-05', 'Poli Gigi', 3);

-- Detail Keluar
-- TRK 1
-- batch_id 1 (Paracetamol Batch A001)
INSERT INTO detail_keluar (transaksi_keluar_id, obat_id, batch_id, jumlah) VALUES (1, 1, 1, 50); 
-- batch_id 3 (Amoxicillin BATCH-B001)
INSERT INTO detail_keluar (transaksi_keluar_id, obat_id, batch_id, jumlah) VALUES (1, 2, 3, 100); 

-- TRK 2
-- batch_id 5 (Vitamin C BATCH-C001)
INSERT INTO detail_keluar (transaksi_keluar_id, obat_id, batch_id, jumlah) VALUES (2, 3, 5, 50); 
