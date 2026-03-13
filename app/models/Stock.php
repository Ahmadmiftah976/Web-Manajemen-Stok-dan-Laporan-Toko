<?php
/**
 * app/models/Stock.php
 * Model untuk tabel stock dan stock_movements.
 * Mengelola stok per produk per gudang dan riwayat pergerakan.
 */

class Stock extends Model
{
    protected string $table = 'stock';
    protected string $primaryKey = 'id';

    // ── Read: Stok ───────────────────────────────────────────────────────────

    /**
     * Ambil stok per gudang tertentu, join dengan data produk.
     */
    public function getStockByWarehouse(int $warehouseId, string $search = ''): array
    {
        $sql = "SELECT p.id AS product_id, p.name AS product_name, p.sku, p.category,
                       p.harga_jual, p.stok_minimum,
                       COALESCE(s.quantity, 0) AS quantity
                FROM   products p
                LEFT   JOIN stock s ON s.product_id = p.id AND s.warehouse_id = :wid
                WHERE  p.is_active = TRUE";
        $params = [':wid' => $warehouseId];

        if ($search !== '') {
            $sql .= " AND (LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.sku) LIKE LOWER(:search2))";
            $params[':search']  = "%{$search}%";
            $params[':search2'] = "%{$search}%";
        }

        $sql .= " ORDER BY p.name ASC";

        return $this->query($sql, $params);
    }

    /**
     * Ambil ringkasan stok: total quantity per produk (semua gudang).
     */
    public function getStockSummary(): array
    {
        return $this->query(
            "SELECT p.id, p.name, p.sku, p.category, p.stok_minimum,
                    COALESCE(SUM(s.quantity), 0) AS total_stock
             FROM   products p
             LEFT   JOIN stock s ON s.product_id = p.id
             WHERE  p.is_active = TRUE
             GROUP  BY p.id, p.name, p.sku, p.category, p.stok_minimum
             ORDER  BY p.name ASC"
        );
    }

    /**
     * Ambil produk yang stoknya di bawah minimum di gudang manapun (untuk notifikasi).
     * Cek per gudang, bukan total semua gudang.
     */
    public function getLowStock(): array
    {
        return $this->query(
            "SELECT p.id, p.name, p.sku, p.stok_minimum,
                    w.name AS warehouse_name,
                    COALESCE(s.quantity, 0) AS total_stock
             FROM   products p
             CROSS  JOIN warehouses w
             LEFT   JOIN stock s ON s.product_id = p.id AND s.warehouse_id = w.id
             WHERE  p.is_active = TRUE
               AND  w.is_active = TRUE
               AND  COALESCE(s.quantity, 0) < p.stok_minimum
             ORDER  BY COALESCE(s.quantity, 0) ASC"
        );
    }

    /**
     * Ambil quantity stok satu produk di satu gudang.
     */
    public function getQuantity(int $productId, int $warehouseId): int
    {
        $result = $this->queryOne(
            "SELECT quantity FROM stock
             WHERE product_id = :pid AND warehouse_id = :wid",
            [':pid' => $productId, ':wid' => $warehouseId]
        );

        return (int) ($result['quantity'] ?? 0);
    }

    // ── Read: Riwayat ────────────────────────────────────────────────────────

    /**
     * Ambil riwayat pergerakan stok dengan filter opsional.
     */
    public function getMovementHistory(
        string $type = '',
        int $warehouseId = 0,
        string $dateFrom = '',
        string $dateTo = ''
    ): array {
        $sql = "SELECT sm.*, p.name AS product_name, p.sku,
                       w.name  AS warehouse_name,
                       fw.name AS from_warehouse_name,
                       tw.name AS to_warehouse_name,
                       u.name  AS created_by_name
                FROM   stock_movements sm
                JOIN   products p  ON p.id  = sm.product_id
                LEFT   JOIN warehouses w  ON w.id  = sm.warehouse_id
                LEFT   JOIN warehouses fw ON fw.id = sm.from_warehouse_id
                LEFT   JOIN warehouses tw ON tw.id = sm.to_warehouse_id
                LEFT   JOIN users u       ON u.id  = sm.created_by
                WHERE  1 = 1";
        $params = [];

        if ($type !== '') {
            $sql .= " AND sm.type = :type";
            $params[':type'] = $type;
        }

        if ($warehouseId > 0) {
            $sql .= " AND (sm.warehouse_id = :wid OR sm.from_warehouse_id = :wid2 OR sm.to_warehouse_id = :wid3)";
            $params[':wid']  = $warehouseId;
            $params[':wid2'] = $warehouseId;
            $params[':wid3'] = $warehouseId;
        }

        if ($dateFrom !== '') {
            $sql .= " AND sm.created_at >= :dfrom";
            $params[':dfrom'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $sql .= " AND sm.created_at <= :dto";
            $params[':dto'] = $dateTo . ' 23:59:59';
        }

        $sql .= " ORDER BY sm.created_at DESC LIMIT 200";

        return $this->query($sql, $params);
    }

    // ── Write: Stok Masuk / Keluar ───────────────────────────────────────────

    /**
     * Tambah stok (type = masuk) atau kurangi stok (type = keluar/koreksi).
     */
    public function adjustStock(array $data): void
    {
        $this->beginTransaction();

        try {
            $productId   = (int) $data['product_id'];
            $warehouseId = (int) $data['warehouse_id'];
            $quantity    = (int) $data['quantity'];
            $type        = $data['type'];      // 'masuk' atau 'keluar' atau 'koreksi'
            $notes       = $data['notes'] ?? null;
            $createdBy   = (int) $data['created_by'];

            // Pastikan row stock ada (upsert)
            $this->execute(
                "INSERT INTO stock (product_id, warehouse_id, quantity)
                 VALUES (:pid, :wid, 0)
                 ON CONFLICT (product_id, warehouse_id) DO NOTHING",
                [':pid' => $productId, ':wid' => $warehouseId]
            );

            // Update quantity
            if ($type === 'masuk') {
                $this->execute(
                    "UPDATE stock SET quantity = quantity + :qty, updated_at = NOW()
                     WHERE product_id = :pid AND warehouse_id = :wid",
                    [':qty' => $quantity, ':pid' => $productId, ':wid' => $warehouseId]
                );
            } else {
                $this->execute(
                    "UPDATE stock SET quantity = quantity - :qty, updated_at = NOW()
                     WHERE product_id = :pid AND warehouse_id = :wid",
                    [':qty' => $quantity, ':pid' => $productId, ':wid' => $warehouseId]
                );
            }

            // Catat movement
            $this->execute(
                "INSERT INTO stock_movements
                    (product_id, warehouse_id, quantity, type, notes, created_by)
                 VALUES
                    (:pid, :wid, :qty, :type, :notes, :cby)",
                [
                    ':pid'   => $productId,
                    ':wid'   => $warehouseId,
                    ':qty'   => $quantity,
                    ':type'  => $type,
                    ':notes' => $notes,
                    ':cby'   => $createdBy,
                ]
            );

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ── Write: Transfer Antar Gudang ─────────────────────────────────────────

    /**
     * Transfer stok dari satu gudang ke gudang lain.
     */
    public function transferStock(array $data): void
    {
        $this->beginTransaction();

        try {
            $productId       = (int) $data['product_id'];
            $fromWarehouseId = (int) $data['from_warehouse_id'];
            $toWarehouseId   = (int) $data['to_warehouse_id'];
            $quantity        = (int) $data['quantity'];
            $notes           = $data['notes'] ?? null;
            $createdBy       = (int) $data['created_by'];

            // Kurangi dari gudang asal
            $this->execute(
                "UPDATE stock SET quantity = quantity - :qty, updated_at = NOW()
                 WHERE product_id = :pid AND warehouse_id = :wid",
                [':qty' => $quantity, ':pid' => $productId, ':wid' => $fromWarehouseId]
            );

            // Pastikan row tujuan ada (upsert)
            $this->execute(
                "INSERT INTO stock (product_id, warehouse_id, quantity)
                 VALUES (:pid, :wid, 0)
                 ON CONFLICT (product_id, warehouse_id) DO NOTHING",
                [':pid' => $productId, ':wid' => $toWarehouseId]
            );

            // Tambah ke gudang tujuan
            $this->execute(
                "UPDATE stock SET quantity = quantity + :qty, updated_at = NOW()
                 WHERE product_id = :pid AND warehouse_id = :wid",
                [':qty' => $quantity, ':pid' => $productId, ':wid' => $toWarehouseId]
            );

            // Catat movement
            $this->execute(
                "INSERT INTO stock_movements
                    (product_id, from_warehouse_id, to_warehouse_id, quantity, type, notes, created_by)
                 VALUES
                    (:pid, :fwid, :twid, :qty, 'transfer', :notes, :cby)",
                [
                    ':pid'   => $productId,
                    ':fwid'  => $fromWarehouseId,
                    ':twid'  => $toWarehouseId,
                    ':qty'   => $quantity,
                    ':notes' => $notes,
                    ':cby'   => $createdBy,
                ]
            );

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
