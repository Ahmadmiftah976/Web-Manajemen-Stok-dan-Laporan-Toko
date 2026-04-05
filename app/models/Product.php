<?php
/**
 * app/models/Product.php
 * Model untuk tabel products.
 */

class Product extends Model
{
    protected string $table = 'products';
    protected string $primaryKey = 'id';

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Ambil semua produk aktif.
     */
    public function getAllActive(string $search = '', string $category = ''): array
    {
        $sql = "SELECT * FROM products WHERE is_active = TRUE";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (LOWER(name) LIKE LOWER(:search) OR LOWER(sku) LIKE LOWER(:search2))";
            $params[':search']  = "%{$search}%";
            $params[':search2'] = "%{$search}%";
        }

        if ($category !== '') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->query($sql, $params);
    }

    /**
     * Cari produk berdasarkan ID.
     */
    public function findById(int $id): array|false
    {
        return $this->queryOne(
            "SELECT * FROM products WHERE id = :id AND is_active = TRUE LIMIT 1",
            [':id' => $id]
        );
    }

    /**
     * Ambil semua kategori unik (untuk filter dropdown).
     */
    public function getCategories(): array
    {
        return $this->query(
            "SELECT DISTINCT category FROM products WHERE is_active = TRUE AND category IS NOT NULL ORDER BY category ASC"
        );
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Siapkan data produk untuk insert/update.
     * Mapping key input ke kolom database.
     */
    private function prepareData(array $data): array
    {
        return [
            'name'         => $data['name'],
            'sku'          => $data['sku'],
            'category'     => $data['category'] ?: null,
            'harga_beli'   => $data['harga_beli'],
            'harga_jual'   => $data['harga_jual'],
            'stok_minimum' => $data['stok_minimum'] ?? 5,
            'description'  => $data['description'] ?: null,
        ];
    }

    /**
     * Buat produk baru.
     */
    public function createProduct(array $data): int
    {
        return $this->insert($this->prepareData($data));
    }

    /**
     * Update data produk.
     */
    public function updateProduct(int $id, array $data): int
    {
        return $this->update($id, $this->prepareData($data));
    }

    /**
     * Nonaktifkan produk (soft delete).
     */
    public function deactivate(int $id): int
    {
        return $this->update($id, ['is_active' => 'false']);
    }

    // ── Validasi ──────────────────────────────────────────────────────────────

    /**
     * Cek apakah SKU sudah dipakai produk lain.
     */
    public function isSkuTaken(string $sku, int $excludeId = 0): bool
    {
        $result = $this->queryOne(
            "SELECT 1 FROM products WHERE sku = :sku AND id != :id AND is_active = TRUE LIMIT 1",
            [':sku' => $sku, ':id' => $excludeId]
        );
        return $result !== false;
    }
}
