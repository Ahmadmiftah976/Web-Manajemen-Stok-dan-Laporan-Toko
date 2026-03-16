<div align="center">

# 🕌 MajmaInsight

### Sistem Manajemen Stok & Laporan Penjualan Toko Baju Koko

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Midtrans](https://img.shields.io/badge/Midtrans-QRIS-00AA13?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTAiIGZpbGw9IndoaXRlIi8+PC9zdmc+&logoColor=white)](https://midtrans.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

**Solusi lengkap untuk manajemen inventori multi-gudang, sistem kasir (POS), pembayaran QRIS, dan pelaporan bisnis real-time.**

</div>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Tech Stack](#-tech-stack)
- [Arsitektur](#-arsitektur)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Akun Default](#-akun-default)
- [Struktur Proyek](#-struktur-proyek)
- [Database Schema](#-database-schema)
- [Screenshot](#-screenshot)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)

---

## 🎯 Tentang Proyek

**MajmaInsight** adalah aplikasi web berbasis PHP murni (tanpa framework) yang dirancang khusus untuk membantu pemilik bisnis toko baju koko dalam mengelola:

- 📦 **Stok produk** di beberapa gudang/lokasi sekaligus
- 🛒 **Transaksi penjualan** melalui sistem kasir (POS) terintegrasi
- 💳 **Pembayaran QRIS** via Midtrans Payment Gateway
- 📊 **Laporan bisnis** (penjualan, laba rugi, tren) secara real-time

Dibangun dengan arsitektur **MVC (Model-View-Controller)** yang rapi dan terstruktur, tanpa dependency pada framework besar — ringan, cepat, dan mudah dipahami.

---

## ✨ Fitur Utama

### 👤 Manajemen Pengguna (Multi-Role)
- **Pemilik** — akses penuh ke seluruh fitur (dashboard analytics, laporan, manajemen user)
- **Kasir** — akses terbatas ke POS, dashboard operasional, dan riwayat transaksi
- Aktivasi/nonaktivasi akun (soft delete)
- Session management dengan auto-expiry

### 📦 Manajemen Produk & Stok
- CRUD produk lengkap (nama, SKU, kategori, harga beli/jual)
- Stok **multi-gudang** — satu produk bisa tersimpan di beberapa lokasi
- **Transfer stok** antar gudang
- **Riwayat pergerakan stok** (masuk, keluar, transfer, penjualan, koreksi)
- Notifikasi **stok menipis** (di bawah batas minimum)

### 🛒 Sistem Kasir (POS)
- Interface kasir modern dan responsif
- Pencarian produk real-time
- Keranjang belanja interaktif
- Pembayaran **Tunai** dengan kalkulasi kembalian otomatis
- Pembayaran **QRIS** via Midtrans Snap
- Cetak struk (receipt) setelah transaksi

### 📊 Dashboard & Laporan
- **Dashboard Pemilik** — KPI ringkasan, tren pendapatan 7 hari, top 5 produk terlaris
- **Dashboard Kasir** — metode pembayaran hari ini, stok menipis, 10 transaksi terakhir
- **Laporan Penjualan** — tren harian, penjualan per gudang, filter periode
- **Laporan Laba Rugi** — analisis keuntungan dengan perbandingan HPP
- **Export data** ke CSV

### 🔒 Keamanan
- Password hashing dengan `bcrypt` (cost 12)
- Proteksi **CSRF** di setiap form POST
- **Prepared statements** (PDO) untuk pencegahan SQL Injection
- Session regeneration saat login (anti session fixation)
- Input sanitization di seluruh controller

---

## 🛠 Tech Stack

| Layer | Teknologi |
|-------|-----------|
| **Backend** | PHP 8.1+ (Native, tanpa framework) |
| **Database** | PostgreSQL 15+ |
| **Frontend** | HTML5, CSS3 (Vanilla), JavaScript (ES6) |
| **UI Framework** | Bootstrap 5.3 |
| **Charts** | Chart.js |
| **Payment** | Midtrans Snap API (QRIS) |
| **Web Server** | Apache (XAMPP) dengan mod_rewrite |
| **Alerts** | SweetAlert2 |

---

## 🏗 Arsitektur

```
Request → public/index.php → Router → Controller → Model → Database
                                          ↓
                                    View (+ Layout)
                                          ↓
                                      Response
```

Aplikasi ini menggunakan **MVC Pattern** yang dibangun dari nol:

- **`core/`** — Base classes: `Router`, `Controller`, `Model`, `Database` (Singleton)
- **`app/controllers/`** — Business logic per fitur
- **`app/models/`** — Query database & data access
- **`app/views/`** — Template HTML (dengan layout system)
- **`app/helpers/`** — Utility classes (`Auth`, `Csrf`, `Format`, `Midtrans`, `Response`)
- **`config/`** — Konfigurasi app & database
- **`public/`** — Entry point & static assets (CSS, JS, images)

---

## 🚀 Instalasi

### Prasyarat

- **PHP** ≥ 8.1 (dengan ekstensi `pdo_pgsql`, `curl`, `mbstring`)
- **PostgreSQL** ≥ 15
- **Apache** dengan `mod_rewrite` aktif (XAMPP direkomendasikan)
- **Git**

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/Ahmadmiftah976/Web-Manajemen-Stok-dan-Laporan-Toko.git
cd Web-Manajemen-Stok-dan-Laporan-Toko

# 2. Salin file environment
cp .env.example .env

# 3. Edit .env — sesuaikan kredensial database Anda
#    (gunakan text editor favorit Anda)

# 4. Buat database di PostgreSQL
psql -U postgres -c "CREATE DATABASE majmainsight_db;"

# 5. Jalankan migrasi database
psql -U postgres -d majmainsight_db -f "migration (1).sql"

# 6. Atur virtual host atau akses via XAMPP
#    Arahkan DocumentRoot ke folder public/
```

### Akses via XAMPP (Cepat)

1. Pindahkan folder proyek ke `C:\xampp\htdocs\majmainsight\`
2. Edit `.env`:
   ```env
   APP_URL=http://localhost/majmainsight/public
   ```
3. Buka browser: `http://localhost/majmainsight/public`

---

## ⚙ Konfigurasi

Edit file `.env` untuk menyesuaikan konfigurasi:

```env
# ── Aplikasi ───────────────────────────────────────────
APP_NAME="MajmaInsight"
APP_ENV=development          # development | production
APP_URL=http://localhost/majmainsight/public
APP_TIMEZONE=Asia/Makassar

# ── Database (PostgreSQL) ──────────────────────────────
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=majmainsight_db
DB_USER=postgres
DB_PASS=your_password_here

# ── Midtrans Payment Gateway ──────────────────────────
MIDTRANS_ENV=sandbox         # sandbox | production
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key

# ── Session ────────────────────────────────────────────
SESSION_LIFETIME=7200        # Durasi session (detik)
SESSION_NAME=majma_session
```

> **⚠️ Penting:** Jangan pernah commit file `.env` ke repository. Gunakan `.env.example` sebagai template.

---

## 🔑 Akun Default

Setelah menjalankan migrasi, tersedia dua akun default:

| Role | Email | Password |
|------|-------|----------|
| **Pemilik** | `admin@majmainsight.com` | `Admin@1234` |
| **Kasir** | `kasir@majmainsight.com` | `Kasir@1234` |

> **🔴 Ganti password default setelah pertama kali login!**

---

## 📁 Struktur Proyek

```
majmainsight/
├── app/
│   ├── controllers/          # Controller (business logic)
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── KasirController.php
│   │   ├── PaymentController.php
│   │   ├── ProductController.php
│   │   ├── ReportController.php
│   │   ├── StockController.php
│   │   └── UserController.php
│   ├── helpers/              # Utility classes
│   │   ├── Auth.php          # Session & role management
│   │   ├── Csrf.php          # CSRF token protection
│   │   ├── Format.php        # Formatting (rupiah, tanggal, badge)
│   │   ├── Midtrans.php      # Midtrans API integration
│   │   └── Response.php      # JSON & redirect helpers
│   ├── models/               # Data access layer
│   │   ├── Dashboard.php
│   │   ├── Product.php
│   │   ├── Report.php
│   │   ├── Stock.php
│   │   ├── Transaction.php
│   │   ├── User.php
│   │   └── Warehouse.php
│   └── views/                # Template HTML
│       ├── auth/             # Login page
│       ├── dashboard/        # Dashboard (pemilik & kasir)
│       ├── kasir/            # POS interface
│       ├── layouts/          # Layout wrapper
│       ├── products/         # Manajemen produk
│       ├── reports/          # Laporan penjualan & laba rugi
│       ├── stock/            # Manajemen stok
│       └── users/            # Manajemen pengguna
├── config/
│   ├── app.php               # Konfigurasi utama & konstanta
│   └── database.php          # Konfigurasi koneksi database
├── core/
│   ├── Controller.php        # Base controller
│   ├── Database.php          # Database singleton (PDO)
│   ├── Model.php             # Base model (CRUD generik)
│   └── Router.php            # Front controller & routing
├── public/
│   ├── assets/
│   │   ├── css/              # Stylesheet
│   │   ├── js/               # JavaScript
│   │   └── img/              # Gambar & ikon
│   ├── .htaccess             # URL rewriting rules
│   └── index.php             # Entry point aplikasi
├── storage/
│   ├── exports/              # File export (CSV)
│   └── logs/                 # Application logs
├── .env.example              # Template environment
├── .gitignore                # Git ignore rules
├── migration (1).sql         # Database schema & seeder
└── README.md                 # Dokumentasi (file ini)
```

---

## 🗄 Database Schema

Aplikasi menggunakan **7 tabel** utama di PostgreSQL:

```
┌──────────┐     ┌───────────┐     ┌──────────────┐
│  users   │     │ warehouses│     │   products   │
│──────────│     │───────────│     │──────────────│
│ id       │     │ id        │     │ id           │
│ name     │     │ name      │     │ name         │
│ email    │     │ location  │     │ sku          │
│ password │     │ is_active │     │ harga_beli   │
│ role     │     └─────┬─────┘     │ harga_jual   │
│ is_active│           │           │ stok_minimum │
└────┬─────┘           │           └──────┬───────┘
     │                 │                  │
     │        ┌────────┴────────┐         │
     │        │     stock       │         │
     │        │─────────────────│         │
     │        │ product_id  ────┼─────────┘
     │        │ warehouse_id    │
     │        │ quantity        │
     │        └─────────────────┘
     │
     │        ┌─────────────────┐     ┌───────────────────┐
     │        │  transactions   │     │ transaction_items  │
     └───────►│─────────────────│     │───────────────────│
              │ id              │◄────│ transaction_id     │
              │ transaction_code│     │ product_id         │
              │ cashier_id      │     │ quantity           │
              │ warehouse_id    │     │ harga_jual         │
              │ total_amount    │     │ harga_beli         │
              │ payment_method  │     │ subtotal           │
              │ payment_status  │     └───────────────────┘
              └────────┬────────┘
                       │
              ┌────────┴────────┐
              │ stock_movements │
              │─────────────────│
              │ product_id      │
              │ warehouse_id    │
              │ type            │
              │ quantity        │
              │ reference_id    │
              └─────────────────┘
```

## 📸 Screenshot

### 1. Halaman Login
![Halaman Login](public/assets/img/login.png)

### 2. Dashboard Pemilik
![Dashboard Pemilik](public/assets/img/dashboard_pemilik.png)

### 3. Dashboard Kasir
![Dashboard Kasir](public/assets/img/dashboard_kasir.png)

### 4. Sistem Kasir (POS)
![Sistem Kasir](public/assets/img/kasir.png)

### 5. Manajemen Produk
![Manajemen Produk](public/assets/img/manajemen_produk.png)

### 6. Manajemen Stok
![Manajemen Stok](public/assets/img/manajemen_stok.png)

### 7. Laporan Penjualan
![Laporan Penjualan](public/assets/img/laporan_penjualan.png)

### 8. Laporan Laba/Rugi
![Laporan Laba/Rugi](public/assets/img/laporan_labarugi.png)

### 9. Manajemen Pengguna
![Manajemen Pengguna](public/assets/img/manajemen_pengguna.png)

---

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan buat **Pull Request** atau buka **Issue** untuk saran dan perbaikan.

1. Fork repository ini
2. Buat branch fitur (`git checkout -b fitur/FiturBaru`)
3. Commit perubahan (`git commit -m 'Menambah fitur baru'`)
4. Push ke branch (`git push origin fitur/FiturBaru`)
5. Buat Pull Request

---


<div align="center">

**Dibuat dengan ❤️ untuk membantu pengelolaan bisnis toko baju koko**

[⬆ Kembali ke Atas](#-majmainsight)

</div>
