CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('Kepala Puskesmas', 'Petugas Gudang', 'Petugas Farmasi') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS satuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_satuan VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS obat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_obat VARCHAR(20) NOT NULL UNIQUE,
    nama_obat VARCHAR(100) NOT NULL,
    kategori_id INT,
    satuan_id INT,
    min_stok INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
    FOREIGN KEY (satuan_id) REFERENCES satuan(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS batch_obat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obat_id INT NOT NULL,
    no_batch VARCHAR(50) NOT NULL,
    tgl_kadaluarsa DATE NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (obat_id) REFERENCES obat(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transaksi_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tgl_masuk DATE NOT NULL,
    supplier VARCHAR(100),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS detail_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_masuk_id INT NOT NULL,
    obat_id INT NOT NULL,
    no_batch VARCHAR(50) NOT NULL,
    tgl_kadaluarsa DATE NOT NULL,
    jumlah INT NOT NULL,
    FOREIGN KEY (transaksi_masuk_id) REFERENCES transaksi_masuk(id) ON DELETE CASCADE,
    FOREIGN KEY (obat_id) REFERENCES obat(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transaksi_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tgl_keluar DATE NOT NULL,
    tujuan VARCHAR(100),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS detail_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_keluar_id INT NOT NULL,
    obat_id INT NOT NULL,
    batch_id INT NOT NULL,
    jumlah INT NOT NULL,
    FOREIGN KEY (transaksi_keluar_id) REFERENCES transaksi_keluar(id) ON DELETE CASCADE,
    FOREIGN KEY (obat_id) REFERENCES obat(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batch_obat(id) ON DELETE CASCADE
);

-- Default data
INSERT INTO users (username, password, nama_lengkap, role) VALUES 
('kapus', MD5('kapus123'), 'Dr. Kepala Puskesmas', 'Kepala Puskesmas'),
('gudang', MD5('gudang123'), 'Budi Gudang', 'Petugas Gudang'),
('farmasi', MD5('farmasi123'), 'Siti Farmasi', 'Petugas Farmasi') ON DUPLICATE KEY UPDATE username=username;
