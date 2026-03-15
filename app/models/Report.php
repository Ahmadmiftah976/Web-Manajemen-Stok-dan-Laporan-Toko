<?php
/**
 * app/models/Report.php
 * Model untuk query laporan penjualan dan laba rugi.
 */

class Report extends Model
{
    protected string $table = 'transactions';
    protected string $primaryKey = 'id';

    private function getConnection(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    /**
     * Bangun WHERE clause dari filter standar.
     */
    private function buildFilters(array $filters): array
    {
        $where = ["t.payment_status = 'paid'"];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = "t.transaction_date >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "t.transaction_date <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['warehouse_id'])) {
            $where[] = "t.warehouse_id = :warehouse_id";
            $params[':warehouse_id'] = (int) $filters['warehouse_id'];
        }
        if (!empty($filters['payment_method'])) {
            $where[] = "t.payment_method = :method";
            $params[':method'] = $filters['payment_method'];
        }

        return [implode(' AND ', $where), $params];
    }

    // ================================================================
    //  LAPORAN PENJUALAN
    // ================================================================

    /**
     * KPI ringkasan penjualan.
     */
    public function getSalesSummary(array $filters = []): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT 
                    COUNT(*) AS total_trx,
                    COALESCE(SUM(t.total_amount), 0) AS total_revenue,
                    COALESCE(AVG(t.total_amount), 0) AS avg_per_trx,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'tunai' THEN 1 ELSE 0 END), 0) AS count_tunai,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'qris' THEN 1 ELSE 0 END), 0) AS count_qris
                FROM transactions t
                WHERE $where";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Daftar transaksi untuk tabel.
     */
    public function getSalesReport(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        // Count total
        $countSql = "SELECT COUNT(*) FROM transactions t WHERE $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Data
        $sql = "SELECT t.id, t.transaction_code, t.transaction_date, t.payment_method,
                       t.total_amount, t.discount_amount, t.amount_paid, t.change_amount,
                       u.name AS cashier_name, w.name AS warehouse_name
                FROM transactions t
                LEFT JOIN users u ON t.cashier_id = u.id
                LEFT JOIN warehouses w ON t.warehouse_id = w.id
                WHERE $where
                ORDER BY t.transaction_date DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'      => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'totalPages' => (int) ceil($total / $perPage)
        ];
    }

    /**
     * Pendapatan per hari (untuk grafik garis tren).
     */
    public function getRevenueByDay(array $filters = []): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT DATE(t.transaction_date) AS day,
                       SUM(t.total_amount) AS revenue,
                       COUNT(*) AS trx_count
                FROM transactions t
                WHERE $where
                GROUP BY DATE(t.transaction_date)
                ORDER BY day ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Penjualan per gudang (untuk grafik bar).
     */
    public function getSalesByWarehouse(array $filters = []): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT w.name AS warehouse_name,
                       SUM(t.total_amount) AS revenue,
                       COUNT(*) AS trx_count
                FROM transactions t
                LEFT JOIN warehouses w ON t.warehouse_id = w.id
                WHERE $where
                GROUP BY w.name
                ORDER BY revenue DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================================================================
    //  LAPORAN LABA RUGI
    // ================================================================

    /**
     * KPI ringkasan laba rugi.
     */
    public function getProfitSummary(array $filters = []): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT 
                    COALESCE(SUM(ti.harga_jual * ti.quantity), 0) AS total_revenue,
                    COALESCE(SUM(ti.harga_beli * ti.quantity), 0) AS total_cogs,
                    COALESCE(SUM((ti.harga_jual - ti.harga_beli) * ti.quantity), 0) AS gross_profit,
                    COALESCE(SUM(t.discount_amount), 0) AS total_discount
                FROM transaction_items ti
                JOIN transactions t ON ti.transaction_id = t.id
                WHERE $where";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Hitung margin
        $revenue = (float) $row['total_revenue'];
        $row['margin_pct'] = $revenue > 0 ? round(((float)$row['gross_profit'] / $revenue) * 100, 1) : 0;

        return $row;
    }

    /**
     * Analisis laba per produk (untuk tabel detail).
     */
    public function getProfitByProduct(array $filters = []): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT p.name AS product_name, p.category,
                       SUM(ti.quantity) AS qty_sold,
                       SUM(ti.harga_jual * ti.quantity) AS revenue,
                       SUM(ti.harga_beli * ti.quantity) AS cogs,
                       SUM((ti.harga_jual - ti.harga_beli) * ti.quantity) AS profit
                FROM transaction_items ti
                JOIN transactions t ON ti.transaction_id = t.id
                JOIN products p ON ti.product_id = p.id
                WHERE $where
                GROUP BY p.id, p.name, p.category
                ORDER BY profit DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $rev = (float) $row['revenue'];
            $row['margin_pct'] = $rev > 0 ? round(((float)$row['profit'] / $rev) * 100, 1) : 0;
        }

        return $data;
    }

    /**
     * Pendapatan per kategori (untuk grafik donut).
     */
    public function getRevenueByCategory(array $filters = []): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT COALESCE(p.category, 'Tanpa Kategori') AS category,
                       SUM(ti.harga_jual * ti.quantity) AS revenue
                FROM transaction_items ti
                JOIN transactions t ON ti.transaction_id = t.id
                JOIN products p ON ti.product_id = p.id
                WHERE $where
                GROUP BY p.category
                ORDER BY revenue DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top 5 produk terlaris (by quantity).
     */
    public function getTopProducts(array $filters = [], int $limit = 5): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT p.name AS product_name, p.category,
                       SUM(ti.quantity) AS qty_sold,
                       SUM(ti.subtotal) AS revenue
                FROM transaction_items ti
                JOIN transactions t ON ti.transaction_id = t.id
                JOIN products p ON ti.product_id = p.id
                WHERE $where
                GROUP BY p.id, p.name, p.category
                ORDER BY qty_sold DESC
                LIMIT :lmt";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lmt', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================================================================
    //  EXPORT CSV
    // ================================================================

    /**
     * Ambil semua data penjualan tanpa pagination (untuk CSV export).
     */
    public function getSalesForExport(array $filters = []): array
    {
        $pdo = $this->getConnection();
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT t.transaction_code, t.transaction_date, u.name AS cashier_name,
                       w.name AS warehouse_name, t.payment_method, t.discount_amount, t.total_amount
                FROM transactions t
                LEFT JOIN users u ON t.cashier_id = u.id
                LEFT JOIN warehouses w ON t.warehouse_id = w.id
                WHERE $where
                ORDER BY t.transaction_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
