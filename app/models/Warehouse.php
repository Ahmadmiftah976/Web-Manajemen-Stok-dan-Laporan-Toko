<?php
/**
 * app/models/Warehouse.php
 * Model untuk tabel warehouses (gudang/lokasi penyimpanan).
 */

class Warehouse extends Model
{
    protected string $table = 'warehouses';
    protected string $primaryKey = 'id';

    /**
     * Ambil semua gudang aktif.
     */
    public function getAllActive(): array
    {
        return $this->query(
            "SELECT * FROM warehouses WHERE is_active = TRUE ORDER BY name ASC"
        );
    }

    /**
     * Cari gudang berdasarkan ID.
     */
    public function findById(int $id): array|false
    {
        return $this->queryOne(
            "SELECT * FROM warehouses WHERE id = :id AND is_active = TRUE LIMIT 1",
            [':id' => $id]
        );
    }
}
