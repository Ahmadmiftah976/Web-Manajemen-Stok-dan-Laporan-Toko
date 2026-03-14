<?php
/**
 * Lokasi: app/models/Transaction.php
 * Deskripsi: Model untuk mengelola transaksi kasir dan mutasi item.
 */

class Transaction extends Model
{
    protected string $table = 'transactions';
    protected string $primaryKey = 'id';

    /**
     * Membuat transaksi baru beserta detail item dan memotong stok.
     * Menggunakan Database Transaction (BEGIN/COMMIT).
     */
    public function createTransaction(array $data, array $items): int|false
    {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        try {
            // Mulai DB Transaction
            $pdo->beginTransaction();

            // 1. Generate Transaction Code (Format: TRX-YYYYMMDD-XXXX)
            $datePrefix = 'TRX-' . date('Ymd') . '-';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date) = CURRENT_DATE");
            $stmt->execute();
            $countToday = (int) $stmt->fetchColumn();
            $transactionCode = $datePrefix . str_pad((string)($countToday + 1), 4, '0', STR_PAD_LEFT);

            // 2. Insert ke tabel transactions
            $stmtTrx = $pdo->prepare("
                INSERT INTO transactions 
                (transaction_code, cashier_id, warehouse_id, total_amount, payment_method, amount_paid, change_amount, payment_status, notes, discount_amount) 
                VALUES 
                (:code, :cashier, :warehouse, :total, :method, :paid, :change, :status, :notes, :discount)
                RETURNING id
            ");
            $stmtTrx->execute([
                ':code'      => $transactionCode,
                ':cashier'   => $data['cashier_id'] ?? null,
                ':warehouse' => $data['warehouse_id'],
                ':total'     => $data['total_amount'],
                ':method'    => $data['payment_method'],
                ':paid'      => $data['amount_paid'],
                ':change'    => $data['change_amount'],
                ':status'    => $data['payment_status'] ?? 'paid',
                ':notes'     => $data['notes'] ?? null,
                ':discount'  => $data['discount_amount'] ?? 0
            ]);
            
            $transactionId = $stmtTrx->fetchColumn();

            // 3. Loop Item: Insert ke transaction_items & Kurangi Stok & Catat Movement
            $stmtItem = $pdo->prepare("
                INSERT INTO transaction_items (transaction_id, product_id, quantity, harga_jual, harga_beli, subtotal)
                VALUES (:trx_id, :prod_id, :qty, :hjual, :hbeli, :subtotal)
            ");

            $stmtStock = $pdo->prepare("
                UPDATE stock 
                SET quantity = quantity - :qty, updated_at = NOW() 
                WHERE product_id = :prod_id AND warehouse_id = :warehouse_id
            ");

            $stmtMovement = $pdo->prepare("
                INSERT INTO stock_movements (product_id, warehouse_id, quantity, type, reference_id, notes, created_by)
                VALUES (:prod_id, :warehouse_id, :qty, 'penjualan', :ref_id, :notes, :user_id)
            ");

            foreach ($items as $item) {
                // Insert Item Transaksi
                $stmtItem->execute([
                    ':trx_id'   => $transactionId,
                    ':prod_id'  => $item['product_id'],
                    ':qty'      => $item['quantity'],
                    ':hjual'    => $item['harga_jual'],
                    ':hbeli'    => $item['harga_beli'],
                    ':subtotal' => $item['subtotal']
                ]);

                // Kurangi Stok di Gudang yang dipilih
                $stmtStock->execute([
                    ':qty'          => $item['quantity'],
                    ':prod_id'      => $item['product_id'],
                    ':warehouse_id' => $data['warehouse_id']
                ]);

                // Catat Riwayat Pergerakan Stok
                $stmtMovement->execute([
                    ':prod_id'      => $item['product_id'],
                    ':warehouse_id' => $data['warehouse_id'],
                    ':qty'          => $item['quantity'],
                    ':ref_id'       => $transactionId,
                    ':notes'        => 'Penjualan ' . $transactionCode,
                    ':user_id'      => $data['cashier_id'] ?? null
                ]);
            }

            // Commit perubahan bila semua insert/update berhasil
            $pdo->commit();
            
            return $transactionId;

        } catch (Exception $e) {
            // Rollback jika ada error
            $pdo->rollBack();
            error_log('[TransactionModel] createTransaction failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hitung ringkasan hari ini (Pendapatan & Jumlah Transaksi) untuk Kasir
     */
    public function getTodaySummary(int $warehouseId = 0): array
    {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $sql = "SELECT COUNT(*) as total_trx, COALESCE(SUM(total_amount), 0) as total_revenue 
                FROM transactions 
                WHERE DATE(transaction_date) = CURRENT_DATE AND payment_status = 'paid'";
                
        $params = [];
        if ($warehouseId > 0) {
            $sql .= " AND warehouse_id = :warehouse";
            $params[':warehouse'] = $warehouseId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
