# 💊 Sistem Informasi Manajemen Persediaan Obat Berbasis Web (FEFO)

Sistem Informasi Manajemen Persediaan Obat berbasis web yang dikembangkan untuk membantu pengelolaan stok obat di **Puskesmas Sungai Ulin** dengan menerapkan metode **FEFO (First Expired First Out)** agar obat dengan masa kedaluwarsa paling dekat digunakan terlebih dahulu.

---

## 📖 Tentang Proyek

Aplikasi ini dibuat sebagai tugas akhir (skripsi) Program Studi Sistem Informasi Universitas Islam Kalimantan Muhammad Arsyad Al Banjari (UNISKA MAB) Banjarbaru.

Metode **FEFO (First Expired First Out)** digunakan untuk membantu pengelolaan persediaan obat sehingga dapat mengurangi risiko obat kedaluwarsa dan meningkatkan efisiensi pelayanan kefarmasian.

---

## ✨ Fitur Utama

- Login Multi User
- Dashboard Persediaan Obat
- Master Data Obat
- Master Kategori Obat
- Master Satuan Obat
- Pencatatan Obat Masuk
- Pencatatan Obat Keluar
- Manajemen Batch/Lot Obat
- Penerapan Metode FEFO
- Monitoring Stok Obat
- Monitoring Masa Kedaluwarsa
- Data Obat Rusak/Kedaluwarsa
- Laporan Stok Obat
- Laporan Obat Masuk
- Laporan Obat Keluar
- Laporan Mutasi Obat
- Laporan Batch Obat
- Laporan Kartu Stok
- Laporan Obat Hampir Kedaluwarsa
- Laporan Obat Kedaluwarsa
- Prediksi Kebutuhan Obat

---

## 🛠️ Teknologi

- PHP Native
- MySQL
- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- Font Awesome
- DataTables

---

## 💻 Persyaratan Sistem

- PHP 8.x
- MySQL 8.x atau MariaDB
- Apache Web Server
- Laragon / XAMPP

---

## 🚀 Cara Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/maulanahidayat/stokobat-fefo-pkmsungaiulin.git
```

### 2. Pindahkan ke Folder Web Server

Contoh Laragon:

```
C:\laragon\www\
```

### 3. Import Database

Import file:

```
stokobat_fefo.sql
```

atau

```
database/stokobat_fefo.sql
```

menggunakan phpMyAdmin.

### 4. Konfigurasi Database

Edit file:

```
config/database.php
```

Sesuaikan konfigurasi berikut:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "stokobat_fefo";
```

### 5. Jalankan Aplikasi

Buka browser:

```
http://localhost/stokobat_fefo
```

---

## 📂 Struktur Folder

```
stokobat_fefo/
│
├── assets/
├── config/
├── database/
├── layouts/
├── modules/
│   ├── obat/
│   ├── masuk/
│   ├── keluar/
│   ├── laporan/
│   ├── kategori/
│   ├── satuan/
│   └── obat_rusak/
├── auth.php
├── dashboard.php
├── index.php
└── logout.php
```

---

## 📊 Metode

Aplikasi menggunakan metode **FEFO (First Expired First Out)**, yaitu sistem secara otomatis memprioritaskan pengeluaran obat berdasarkan tanggal kedaluwarsa terdekat sehingga dapat meminimalkan risiko obat kedaluwarsa.

---

## 🎯 Tujuan

- Mengelola persediaan obat secara efektif.
- Mengurangi jumlah obat kedaluwarsa.
- Membantu monitoring stok obat.
- Mendukung proses pengambilan keputusan melalui laporan yang informatif.
- Meningkatkan efisiensi pelayanan kefarmasian.

---

## 👨‍💻 Pengembang

**Maulana Hidayat**

Program Studi Sistem Informasi

Universitas Islam Kalimantan Muhammad Arsyad Al Banjari

Banjarbaru, Kalimantan Selatan

---

## 📄 Lisensi

Project ini dibuat untuk keperluan akademik (skripsi) dan pengembangan sistem informasi.

© 2026 Maulana Hidayat. All Rights Reserved.
