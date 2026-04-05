<?php
/**
 * app/models/Dashboard.php
 * Model khusus untuk mengambil data statistik dan metrik di halaman Dashboard.
 */

class Dashboard extends Model
{
    /**
     * Total Pendapatan hari ini (transaksi berstatus 'paid').
     */
    public function getTodaySales(): float
    {
        $sql = "SELECT SUM(total_amount) as total 
                FROM transactions 
                WHERE DATE(transaction_date) = CURRENT_DATE 
                  AND payment_status = 'paid'";
        $result = $this->queryOne($sql);
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Jumlah Transaksi (struk) hari ini.
     * Jika cashierId diberikan, hitung hanya untuk kasir tersebut.
     */
    public function getTodayTransactionCount(?int $cashierId = null): int
    {
        $sql = "SELECT COUNT(id) as count 
                FROM transactions 
                WHERE DATE(transaction_date) = CURRENT_DATE 
                  AND payment_status = 'paid'";
        
        $params = [];
        if ($cashierId !== null) {
            $sql .= " AND cashier_id = :user_id";
            $params[':user_id'] = $cashierId;
        }

        $result = $this->queryOne($sql, $params);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Estimasi Laba Kotor bulan ini.
     * (Total Penjualan - Total HPP) dari transaksi berstatus paid.
     */
    public function getMonthlyGrossProfit(): float
    {
        $sql = "SELECT 
                    SUM(td.quantity * td.harga_jual) as total_revenue,
                    SUM(td.quantity * td.harga_beli) as total_cogs
                FROM transaction_items td
                JOIN transactions t ON td.transaction_id = t.id
                WHERE EXTRACT(MONTH FROM t.transaction_date) = EXTRACT(MONTH FROM CURRENT_DATE)
                  AND EXTRACT(YEAR FROM t.transaction_date) = EXTRACT(YEAR FROM CURRENT_DATE)
                  AND t.payment_status = 'paid'";
        
        $result = $this->queryOne($sql);
        $revenue = (float) ($result['total_revenue'] ?? 0);
        $cogs = (float) ($result['total_cogs'] ?? 0);
        
        return $revenue - $cogs;
    }

    /**
     * Data grafik Garis (Line Chart): Tren Penjualan 7 Hari Terakhir
     */
    public function getSalesTrend7Days(): array
    {
        $sql = "
            SELECT DATE(transaction_date) AS t_date, SUM(total_amount) AS total
            FROM transactions
            WHERE transaction_date >= (CURRENT_DATE - INTERVAL '6 days')
              AND payment_status = 'paid'
            GROUP BY DATE(transaction_date)
        ";
        $results = $this->query($sql);

        // Populate the last 7 days in PHP to ensure 0-data days exist
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $label = date('d M', strtotime($date));
            $trend[$date] = [
                'label' => $label,
                'total' => 0
            ];
        }

        foreach ($results as $row) {
            $date = $row['t_date'];
            if (isset($trend[$date])) {
                $trend[$date]['total'] = (float) $row['total'];
            }
        }

        return array_values($trend);
    }

    /**
     * Data grafik Top 5 Produk Terlaris bulan ini (Doughnut Chart)
     */
    public function getTopProductsThisMonth(): array
    {
        $sql = "
            SELECT 
                p.name as label,
                SUM(td.quantity) as total_qty
            FROM transaction_items td
            JOIN transactions t ON td.transaction_id = t.id
            JOIN products p ON td.product_id = p.id
            WHERE EXTRACT(MONTH FROM t.transaction_date) = EXTRACT(MONTH FROM CURRENT_DATE)
              AND EXTRACT(YEAR FROM t.transaction_date) = EXTRACT(YEAR FROM CURRENT_DATE)
              AND t.payment_status = 'paid'
            GROUP BY p.id, p.name
            ORDER BY total_qty DESC
            LIMIT 5
        ";
        return $this->query($sql);
    }

    /**
     * Ambil sejumlah transaksi terbaru.
     * Dapat difilter berdasarkan kasir (cashierId) jika bukan pemilik.
     */
    public function getRecentTransactions(int $limit = 5, ?int $cashierId = null): array
    {
        $sql = "
            SELECT 
                t.*,
                u.name as cashier_name
            FROM transactions t
            LEFT JOIN users u ON t.cashier_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($cashierId !== null) {
            $sql .= " AND t.cashier_id = :user_id";
            $params[':user_id'] = $cashierId;
        }

        $sql .= " ORDER BY t.transaction_date DESC LIMIT :limit";
        $sql = str_replace(':limit', $limit, $sql); 
        
        return $this->query($sql, $params);
    }

    /**
     * Menghitung produk yang stoknya di bawah batas stok_minimum secara total.
     */
    public function getLowStockCount(): int
    {
        $sql = "
            SELECT COUNT(*) AS count
            FROM (
                SELECT product_id
                FROM stock
                GROUP BY product_id
                HAVING SUM(quantity) <= (
                    SELECT stok_minimum FROM products WHERE id = stock.product_id
                )
            ) subquery
        ";
        $result = $this->queryOne($sql);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Kasir: Statistik Metode Pembayaran Hari Ini
     */
    public function getPaymentMethodStatsToday(?int $cashierId = null): array
    {
        $sql = "
            SELECT payment_method, COUNT(*) as trx_count, SUM(total_amount) as total
            FROM transactions
            WHERE DATE(transaction_date) = CURRENT_DATE
              AND payment_status = 'paid'
        ";
        $params = [];
        if ($cashierId !== null) {
            $sql .= " AND cashier_id = :user_id";
            $params[':user_id'] = $cashierId;
        }
        $sql .= " GROUP BY payment_method";
        
        $results = $this->query($sql, $params);
        $stats = ['tunai' => ['count' => 0, 'total' => 0], 'qris' => ['count' => 0, 'total' => 0]];
        foreach ($results as $r) {
            $method = strtolower($r['payment_method']);
            if (isset($stats[$method])) {
                $stats[$method]['count'] = (int) $r['trx_count'];
                $stats[$method]['total'] = (float) $r['total'];
            }
        }
        return $stats;
    }

    /**
     * Kasir: Alert produk stok menipis (< stok_minimum)
     */
    public function getLowStockAlerts(int $limit = 5): array
    {
        $sql = "
            SELECT p.id, p.name, p.stok_minimum, p.sku, COALESCE(SUM(s.quantity), 0) as current_stock
            FROM products p
            LEFT JOIN stock s ON p.id = s.product_id
            GROUP BY p.id, p.name, p.stok_minimum, p.sku
            HAVING COALESCE(SUM(s.quantity), 0) > 0 AND COALESCE(SUM(s.quantity), 0) < p.stok_minimum
            ORDER BY current_stock ASC, p.name ASC
            LIMIT :lmt
        ";
        $sql = str_replace(':lmt', $limit, $sql); 
        return $this->query($sql);
    }
}
