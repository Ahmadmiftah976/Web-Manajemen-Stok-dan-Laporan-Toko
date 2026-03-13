-- =============================================================
-- MAJMAINSIGHT - Database Migration
-- PostgreSQL
-- Jalankan file ini di pgAdmin (Query Tool) atau psql
-- =============================================================

-- -------------------------------------------------------------
-- 0. SETUP: Buat database (jalankan terpisah sebagai superuser)
-- -------------------------------------------------------------
-- CREATE DATABASE majmainsight_db
--     WITH ENCODING = 'UTF8'
--          LC_COLLATE = 'id_ID.UTF-8'
--          LC_CTYPE   = 'id_ID.UTF-8'
--          TEMPLATE   = template0;

-- Jika locale id_ID tidak tersedia di sistem kamu, gunakan:
-- CREATE DATABASE majmainsight_db WITH ENCODING = 'UTF8';

-- Setelah database dibuat, connect ke database tersebut,
-- lalu jalankan semua perintah di bawah ini.
-- =============================================================


-- Aktifkan extension untuk UUID (opsional, tidak wajib)
-- CREATE EXTENSION IF NOT EXISTS "pgcrypto";


-- =============================================================
-- 1. TABEL: users
--    Menyimpan semua pengguna (pemilik & kasir)
-- =============================================================
CREATE TABLE IF NOT EXISTS users (
    id            SERIAL          PRIMARY KEY,
    name          VARCHAR(100)    NOT NULL,
    email         VARCHAR(150)    NOT NULL UNIQUE,
    password_hash VARCHAR(255)    NOT NULL,
    role          VARCHAR(20)     NOT NULL DEFAULT 'kasir'
                                  CHECK (role IN ('pemilik', 'kasir')),
    is_active     BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  users              IS 'Pengguna aplikasi: pemilik bisnis dan kasir';
COMMENT ON COLUMN users.role         IS 'pemilik = akses penuh, kasir = akses terbatas';
COMMENT ON COLUMN users.is_active    IS 'FALSE = akun dinonaktifkan (soft delete)';


-- =============================================================
-- 2. TABEL: warehouses
--    Lokasi penyimpanan stok (gudang / toko / rumah)
-- =============================================================
CREATE TABLE IF NOT EXISTS warehouses (
    id          SERIAL          PRIMARY KEY,
    name        VARCHAR(100)    NOT NULL,
    location    VARCHAR(200)    NOT NULL,
    description TEXT,
    is_active   BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE warehouses IS 'Lokasi penyimpanan stok (bisa lebih dari satu gudang)';


-- =============================================================
-- 3. TABEL: products
--    Katalog produk baju koko
-- =============================================================
CREATE TABLE IF NOT EXISTS products (
    id            SERIAL          PRIMARY KEY,
    name          VARCHAR(200)    NOT NULL,
    sku           VARCHAR(50)     NOT NULL UNIQUE,
    category      VARCHAR(100),
    harga_beli    NUMERIC(12, 2)  NOT NULL DEFAULT 0
                                  CHECK (harga_beli >= 0),
    harga_jual    NUMERIC(12, 2)  NOT NULL DEFAULT 0
                                  CHECK (harga_jual >= 0),
    stok_minimum  INTEGER         NOT NULL DEFAULT 5
                                  CHECK (stok_minimum >= 0),
    description   TEXT,
    is_active     BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  products             IS 'Katalog produk baju koko';
COMMENT ON COLUMN products.sku         IS 'Stock Keeping Unit — kode unik per produk';
COMMENT ON COLUMN products.harga_beli  IS 'Harga modal / harga beli dari supplier';
COMMENT ON COLUMN products.harga_jual  IS 'Harga jual ke pelanggan';
COMMENT ON COLUMN products.stok_minimum IS 'Batas bawah stok sebelum notifikasi muncul';
COMMENT ON COLUMN products.is_active   IS 'FALSE = produk diarsipkan (soft delete)';


-- =============================================================
-- 4. TABEL: stock
--    Jumlah stok per produk per gudang (many-to-many)
-- =============================================================
CREATE TABLE IF NOT EXISTS stock (
    id            SERIAL      PRIMARY KEY,
    product_id    INTEGER     NOT NULL REFERENCES products(id)   ON DELETE CASCADE,
    warehouse_id  INTEGER     NOT NULL REFERENCES warehouses(id) ON DELETE CASCADE,
    quantity      INTEGER     NOT NULL DEFAULT 0
                              CHECK (quantity >= 0),
    updated_at    TIMESTAMP   NOT NULL DEFAULT NOW(),

    -- Satu produk hanya boleh punya satu baris per gudang
    UNIQUE (product_id, warehouse_id)
);

COMMENT ON TABLE  stock            IS 'Jumlah stok setiap produk di setiap gudang';
COMMENT ON COLUMN stock.quantity   IS 'Jumlah unit yang tersedia saat ini';


-- =============================================================
-- 5. TABEL: stock_movements
--    Log semua pergerakan stok (masuk, keluar, transfer)
-- =============================================================
CREATE TABLE IF NOT EXISTS stock_movements (
    id                  SERIAL      PRIMARY KEY,
    product_id          INTEGER     NOT NULL REFERENCES products(id)   ON DELETE RESTRICT,
    warehouse_id        INTEGER     REFERENCES warehouses(id) ON DELETE SET NULL,
    -- Untuk transfer: from_warehouse dan to_warehouse diisi
    from_warehouse_id   INTEGER     REFERENCES warehouses(id) ON DELETE SET NULL,
    to_warehouse_id     INTEGER     REFERENCES warehouses(id) ON DELETE SET NULL,
    quantity            INTEGER     NOT NULL CHECK (quantity > 0),
    type                VARCHAR(20) NOT NULL
                                    CHECK (type IN ('masuk', 'keluar', 'transfer', 'penjualan', 'koreksi')),
    reference_id        INTEGER,            -- ID transaksi jika type = 'penjualan'
    notes               TEXT,
    created_by          INTEGER     REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMP   NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  stock_movements              IS 'Riwayat semua pergerakan stok';
COMMENT ON COLUMN stock_movements.type         IS 'masuk=restok, keluar=pengurangan manual, transfer=pindah gudang, penjualan=dari kasir, koreksi=penyesuaian';
COMMENT ON COLUMN stock_movements.reference_id IS 'ID dari tabel transactions jika berasal dari penjualan';


-- =============================================================
-- 6. TABEL: transactions
--    Header transaksi penjualan di kasir
-- =============================================================
CREATE TABLE IF NOT EXISTS transactions (
    id                  SERIAL          PRIMARY KEY,
    transaction_code    VARCHAR(50)     NOT NULL UNIQUE,
    cashier_id          INTEGER         REFERENCES users(id) ON DELETE SET NULL,
    warehouse_id        INTEGER         REFERENCES warehouses(id) ON DELETE SET NULL,
    total_amount        NUMERIC(12, 2)  NOT NULL DEFAULT 0
                                        CHECK (total_amount >= 0),
    payment_method      VARCHAR(10)     NOT NULL
                                        CHECK (payment_method IN ('tunai', 'qris')),
    amount_paid         NUMERIC(12, 2)  DEFAULT 0,  -- Nominal yang dibayarkan (untuk tunai)
    change_amount       NUMERIC(12, 2)  DEFAULT 0,  -- Kembalian
    midtrans_order_id   VARCHAR(100),               -- Order ID Midtrans (khusus QRIS)
    payment_status      VARCHAR(10)     NOT NULL DEFAULT 'pending'
                                        CHECK (payment_status IN ('pending', 'paid', 'failed', 'expired')),
    notes               TEXT,
    transaction_date    TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  transactions                   IS 'Header transaksi penjualan';
COMMENT ON COLUMN transactions.transaction_code  IS 'Format: TRX-YYYYMMDD-XXXX';
COMMENT ON COLUMN transactions.warehouse_id      IS 'Gudang yang stoknya dikurangi';
COMMENT ON COLUMN transactions.midtrans_order_id IS 'Diisi saat payment_method = qris';


-- =============================================================
-- 7. TABEL: transaction_items
--    Detail item per transaksi (one-to-many dari transactions)
-- =============================================================
CREATE TABLE IF NOT EXISTS transaction_items (
    id              SERIAL          PRIMARY KEY,
    transaction_id  INTEGER         NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
    product_id      INTEGER         NOT NULL REFERENCES products(id)     ON DELETE RESTRICT,
    quantity        INTEGER         NOT NULL CHECK (quantity > 0),
    harga_jual      NUMERIC(12, 2)  NOT NULL CHECK (harga_jual >= 0),  -- Snapshot harga saat transaksi
    harga_beli      NUMERIC(12, 2)  NOT NULL CHECK (harga_beli >= 0),  -- Snapshot HPP saat transaksi
    subtotal        NUMERIC(12, 2)  NOT NULL CHECK (subtotal >= 0)
);

COMMENT ON TABLE  transaction_items           IS 'Detail produk dalam setiap transaksi';
COMMENT ON COLUMN transaction_items.harga_jual IS 'Snapshot harga jual saat transaksi terjadi (bukan dari products)';
COMMENT ON COLUMN transaction_items.harga_beli IS 'Snapshot HPP untuk kalkulasi laba rugi';


-- =============================================================
-- 8. INDEXES
--    Mempercepat query yang sering dijalankan
-- =============================================================

-- Index untuk pencarian produk
CREATE INDEX IF NOT EXISTS idx_products_sku        ON products(sku);
CREATE INDEX IF NOT EXISTS idx_products_category   ON products(category);
CREATE INDEX IF NOT EXISTS idx_products_is_active  ON products(is_active);

-- Index untuk query stok
CREATE INDEX IF NOT EXISTS idx_stock_product       ON stock(product_id);
CREATE INDEX IF NOT EXISTS idx_stock_warehouse     ON stock(warehouse_id);

-- Index untuk riwayat pergerakan stok
CREATE INDEX IF NOT EXISTS idx_movements_product   ON stock_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_movements_type      ON stock_movements(type);
CREATE INDEX IF NOT EXISTS idx_movements_created   ON stock_movements(created_at DESC);

-- Index untuk transaksi (query laporan sering filter by date)
CREATE INDEX IF NOT EXISTS idx_transactions_date   ON transactions(transaction_date DESC);
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(payment_status);
CREATE INDEX IF NOT EXISTS idx_transactions_cashier ON transactions(cashier_id);
CREATE INDEX IF NOT EXISTS idx_trx_items_transaction ON transaction_items(transaction_id);
CREATE INDEX IF NOT EXISTS idx_trx_items_product     ON transaction_items(product_id);


-- =============================================================
-- 9. FUNCTION & TRIGGER: auto-update kolom updated_at
-- =============================================================
CREATE OR REPLACE FUNCTION fn_set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger untuk tabel users
CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

-- Trigger untuk tabel products
CREATE TRIGGER trg_products_updated_at
    BEFORE UPDATE ON products
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();


-- =============================================================
-- 10. DATA AWAL (Seeder)
--     Data minimal untuk langsung bisa menjalankan aplikasi
-- =============================================================

-- ── Akun Pemilik default ──────────────────────────────────────
-- Password: Admin@1234
-- Hash dibuat dengan: password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost'=>12])
-- 🔴 SESUAIKAN: ganti name dan email sebelum dijalankan!
-- 🔴 GANTI PASSWORD setelah pertama kali login!
INSERT INTO users (name, email, password_hash, role)
VALUES (
    'Ahmad Miftah',
    'admin@majmainsight.com',
    '$2y$12$ycJqN7Od8EyjuEk/J26qEe71RUThc5.r5l2oQmMhsh4oknPXGtBma',
    'pemilik'
)
ON CONFLICT (email) DO NOTHING;

-- ── Akun Kasir contoh ─────────────────────────────────────────
-- Password: Kasir@1234
-- Hash dibuat dengan: password_hash('Kasir@1234', PASSWORD_BCRYPT, ['cost'=>12])
-- 🔴 SESUAIKAN: ganti name dan email sebelum dijalankan!
INSERT INTO users (name, email, password_hash, role)
VALUES (
    'Kasir Utama',
    'kasir@majmainsight.com',
    '$2y$12$tVDSAlQbNWMELMFpFgkMwOMLJFTFKPbXqV.4D5GUPkJpX1gSS3IDy',
    'kasir'
)
ON CONFLICT (email) DO NOTHING;

-- ── Gudang/Lokasi awal ────────────────────────────────────────
INSERT INTO warehouses (name, location, description) VALUES
    ('Gudang Utama',  'Rumah - Lantai 1',      'Gudang penyimpanan stok utama'),
    ('Toko Pasar A',  'Pasar Sentral Kios 12',  'Lokasi jualan pasar pagi'),
    ('Toko Pasar B',  'Pasar Minggu Kios 5',    'Lokasi jualan pasar mingguan')
ON CONFLICT DO NOTHING;

-- ── Produk sampel ─────────────────────────────────────────────
INSERT INTO products (name, sku, category, harga_beli, harga_jual, stok_minimum, description) VALUES
    ('Baju Koko Putih Polos - S',   'BK-PUTIH-S',   'Polos',  65000,  95000,  10, 'Baju koko putih polos ukuran S'),
    ('Baju Koko Putih Polos - M',   'BK-PUTIH-M',   'Polos',  65000,  95000,  10, 'Baju koko putih polos ukuran M'),
    ('Baju Koko Putih Polos - L',   'BK-PUTIH-L',   'Polos',  65000,  95000,  10, 'Baju koko putih polos ukuran L'),
    ('Baju Koko Putih Polos - XL',  'BK-PUTIH-XL',  'Polos',  68000,  98000,   8, 'Baju koko putih polos ukuran XL'),
    ('Baju Koko Batik Biru - M',    'BK-BATIK-BM',  'Batik',  85000, 130000,   5, 'Baju koko motif batik warna biru ukuran M'),
    ('Baju Koko Batik Biru - L',    'BK-BATIK-BL',  'Batik',  85000, 130000,   5, 'Baju koko motif batik warna biru ukuran L'),
    ('Baju Koko Batik Coklat - M',  'BK-BATIK-CM',  'Batik',  87000, 135000,   5, 'Baju koko motif batik warna coklat ukuran M'),
    ('Baju Koko Bordir Putih - L',  'BK-BORDIR-L',  'Bordir', 95000, 150000,   3, 'Baju koko bordir khas ukuran L'),
    ('Baju Koko Bordir Putih - XL', 'BK-BORDIR-XL', 'Bordir', 98000, 155000,   3, 'Baju koko bordir khas ukuran XL'),
    ('Baju Koko Anak - 8 Tahun',    'BK-ANAK-8',    'Anak',   45000,  70000,   8, 'Baju koko anak usia 8 tahun')
ON CONFLICT (sku) DO NOTHING;

-- ── Stok awal (semua produk di Gudang Utama) ─────────────────
INSERT INTO stock (product_id, warehouse_id, quantity)
SELECT p.id, w.id, 50
FROM   products p
CROSS  JOIN warehouses w
WHERE  w.name = 'Gudang Utama'
ON CONFLICT (product_id, warehouse_id) DO NOTHING;

-- Stok di Toko Pasar A (beberapa produk saja)
INSERT INTO stock (product_id, warehouse_id, quantity)
SELECT p.id, w.id, 20
FROM   products p
CROSS  JOIN warehouses w
WHERE  w.name = 'Toko Pasar A'
  AND  p.category IN ('Polos', 'Batik')
ON CONFLICT (product_id, warehouse_id) DO NOTHING;


-- =============================================================
-- 11. VERIFIKASI
--     Jalankan query ini untuk memastikan semua tabel & data OK
-- =============================================================

-- Cek semua tabel yang berhasil dibuat
SELECT table_name
FROM   information_schema.tables
WHERE  table_schema = 'public'
ORDER  BY table_name;

-- Cek jumlah data awal
SELECT 'users'              AS tabel, COUNT(*) AS jumlah FROM users
UNION ALL
SELECT 'warehouses',                  COUNT(*) FROM warehouses
UNION ALL
SELECT 'products',                    COUNT(*) FROM products
UNION ALL
SELECT 'stock',                       COUNT(*) FROM stock;
