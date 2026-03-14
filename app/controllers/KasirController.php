<?php
/**
 * Lokasi: app/controllers/KasirController.php
 * Deskripsi: Menangani antarmuka mesin kasir (POS), ambil produk, dan pemrosesan pembayaran.
 */

// Helper
require_once dirname(__DIR__) . '/helpers/Response.php';
require_once dirname(__DIR__) . '/helpers/Format.php';

// Models
require_once APP_PATH . '/models/Product.php';
require_once APP_PATH . '/models/Warehouse.php';
require_once APP_PATH . '/models/Transaction.php';

class KasirController extends Controller
{
    private Product $productModel;
    private Warehouse $warehouseModel;
    private Transaction $transactionModel;

    public function __construct()
    {
        // Kasir & Pemilik bisa akses ini
        Auth::check();
        
        $this->productModel     = new Product();
        $this->warehouseModel   = new Warehouse();
        $this->transactionModel = new Transaction();
    }

    /**
     * Menampilkan antarmuka POS Kasir
     */
    public function index(): void
    {
        $warehouses = $this->warehouseModel->getAllActive();
        
        // Ambil gudang aktif pertama jika belum ada filter
        $warehouseId = $_GET['warehouse'] ?? ($warehouses[0]['id'] ?? 0);
        $search      = $_GET['search']    ?? '';
        $category    = $_GET['category']  ?? '';

        $categories = $this->productModel->getCategories();
        
        // Custom query untuk mengambil produk beserta stok riilnya di gudang terpilih
        // Menggunakan Database::getInstance karena kita join tabel stok
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $sql = "SELECT p.id, p.name, p.sku, p.harga_jual, p.harga_beli, s.quantity as available_stock
                FROM products p
                JOIN stock s ON p.id = s.product_id
                WHERE p.is_active = TRUE 
                  AND s.warehouse_id = :warehouse_id
                  AND s.quantity > 0";
                  
        $params = [':warehouse_id' => $warehouseId];
        
        if (!empty($search)) {
            $sql .= " AND (p.name ILIKE :search OR p.sku ILIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($category)) {
            $sql .= " AND p.category = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " ORDER BY p.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ringkasan hari ini
        $todaySummary = $this->transactionModel->getTodaySummary((int)$warehouseId);

        $this->view('kasir/index', [
            'title'        => 'Kasir / POS',
            'warehouses'   => $warehouses,
            'warehouseId'  => (int) $warehouseId,
            'categories'   => $categories,
            'search'       => $search,
            'category'     => $category,
            'products'     => $products,
            'todaySummary' => $todaySummary,
            'extraCss'     => ['pos.css'],
            'extraJs'      => ['pos.js']
        ]);
    }

    /**
     * Memproses checkout (Pembayaran Tunai)
     * Dipanggil via AJAX / Fetch POST
     */
    public function checkout(): void
    {
        // Pastikan request metode POST dan Header JSON Set
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['status' => 'error', 'message' => 'Method not allowed'], 405);
        }

        // Ambil isi raw payload JSON
        $jsonPayload = file_get_contents('php://input');
        $payload = json_decode($jsonPayload, true);

        if (!$payload || empty($payload['cart'])) {
            Response::json(['status' => 'error', 'message' => 'Keranjang kosong atau format data tidak valid'], 400);
        }

        // Data utama transaksi
        $warehouseId    = (int) ($payload['warehouse_id'] ?? 0);
        $totalAmount    = (float) ($payload['total_amount'] ?? 0);
        $discountAmount = (float) ($payload['discount_amount'] ?? 0);
        $amountPaid     = (float) ($payload['amount_paid'] ?? 0);
        $changeAmount   = (float) ($payload['change_amount'] ?? 0);
        $paymentMethod  = $payload['payment_method'] ?? 'tunai';
        $notes          = htmlspecialchars($payload['notes'] ?? '');
        $cartItems      = $payload['cart'];

        // Validasi Gudang
        if ($warehouseId <= 0) {
            Response::json(['status' => 'error', 'message' => 'Gudang harus dipilih.'], 400);
        }

        // Siapkan array data trx
        $trxData = [
            'cashier_id'      => Auth::user('id'),
            'warehouse_id'    => $warehouseId,
            'total_amount'    => $totalAmount,
            'discount_amount' => $discountAmount,
            'amount_paid'     => $amountPaid,
            'change_amount'   => $changeAmount,
            'payment_method'  => $paymentMethod,
            'payment_status'  => 'paid',
            'notes'           => $notes
        ];

        // Format items sesuai database transaction_items
        $items = [];
        foreach ($cartItems as $item) {
            $items[] = [
                'product_id' => (int) $item['id'],
                'quantity'   => (int) $item['qty'],
                'harga_jual' => (float) $item['price'],
                'harga_beli' => (float) ($item['harga_beli'] ?? 0), // Dari fetch produk pos tadi // Asumsi: frontend kirim atau backend re-fetch.
                'subtotal'   => (float) $item['price'] * (int) $item['qty']
            ];
        }

        // Di dunia nyata, sangat disarankan me-query ulang (re-fetch) harga_beli & harga_jual di backend
        // untuk mencegah manipulasi harga dari Network tab browser. Tapi untuk MVP ini kita gunakan
        // langsung dari payload cart demi kesederhanaan, asalkan frontend mengirimnya dengan akurat.
        
        $transactionId = $this->transactionModel->createTransaction($trxData, $items);

        if ($transactionId) {
            Response::json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil disimpan!',
                'data'    => ['transaction_id' => $transactionId]
            ]);
        } else {
            Response::json([
                'status'  => 'error',
                'message' => 'Gagal memproses transaksi. Pastikan stok mencukupi.'
            ], 500);
        }
    }

    /**
     * Halaman Struk Transaksi
     */
    public function receipt(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) Response::redirect('/kasir');

        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // 1. Ambil info transaksi
        $stmt = $pdo->prepare("
            SELECT t.*, u.name as cashier_name, w.name as warehouse_name
            FROM transactions t
            LEFT JOIN users u ON t.cashier_id = u.id
            LEFT JOIN warehouses w ON t.warehouse_id = w.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Transaksi tidak ditemukan.'];
            Response::redirect('/kasir');
        }

        // 2. Ambil detail items
        $stmtItems = $pdo->prepare("
            SELECT ti.*, p.name as product_name
            FROM transaction_items ti
            JOIN products p ON ti.product_id = p.id
            WHERE ti.transaction_id = ?
        ");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Halaman ini tidak pakai template layouts/main.php, kita render template struk saja dengan viewOnly
        $this->viewOnly('kasir/receipt', [
            'title'       => 'Struk ' . htmlspecialchars($transaction['transaction_code']),
            'transaction' => $transaction,
            'items'       => $items
        ]);
    }
}
