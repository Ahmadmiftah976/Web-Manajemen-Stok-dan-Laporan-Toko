<?php
/**
 * app/controllers/StockController.php
 * Menangani manajemen stok: daftar, tambah/kurangi, transfer, riwayat.
 * Hanya bisa diakses oleh pemilik.
 */

require_once APP_PATH . '/models/Stock.php';
require_once APP_PATH . '/models/Warehouse.php';
require_once APP_PATH . '/models/Product.php';

class StockController extends Controller
{
    private Stock     $stockModel;
    private Warehouse $warehouseModel;
    private Product   $productModel;

    public function __construct()
    {
        $this->stockModel     = new Stock();
        $this->warehouseModel = new Warehouse();
        $this->productModel   = new Product();
    }

    // ── GET /stock ───────────────────────────────────────────────────────────

    /**
     * Tampilkan daftar stok per gudang.
     */
    public function index(): void
    {
        Auth::checkRole('pemilik');

        $warehouses    = $this->warehouseModel->getAllActive();
        $warehouseId   = (int) ($this->query('warehouse') ?? ($warehouses[0]['id'] ?? 0));
        $search        = $this->query('search', '');

        $stocks   = $warehouseId > 0
            ? $this->stockModel->getStockByWarehouse($warehouseId, $search)
            : [];
        $lowStock = $this->stockModel->getLowStock();

        $this->view('stock/index', [
            'title'       => 'Manajemen Stok — ' . APP_NAME,
            'pageTitle'   => 'Manajemen Stok',
            'warehouses'  => $warehouses,
            'warehouseId' => $warehouseId,
            'stocks'      => $stocks,
            'lowStock'    => $lowStock,
            'search'      => $search,
            'extraCss'    => ['stock.css'],
        ]);
    }

    // ── GET /stock/add ───────────────────────────────────────────────────────

    /**
     * Tampilkan form stok masuk / keluar.
     */
    public function addPage(): void
    {
        Auth::checkRole('pemilik');

        $warehouses = $this->warehouseModel->getAllActive();
        $products   = $this->productModel->getAllActive();

        $this->view('stock/add', [
            'title'      => 'Stok Masuk / Keluar — ' . APP_NAME,
            'pageTitle'  => 'Stok Masuk / Keluar',
            'warehouses' => $warehouses,
            'products'   => $products,
            'extraCss'   => ['stock.css'],
        ]);
    }

    // ── POST /stock/add ──────────────────────────────────────────────────────

    /**
     * Proses form stok masuk / keluar.
     */
    public function add(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $productId   = (int) ($_POST['product_id']   ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id']  ?? 0);
        $quantity    = (int) ($_POST['quantity']       ?? 0);
        $type        = $this->input('type', 'masuk');
        $notes       = $this->input('notes');

        // Validasi
        $errors = $this->validateStockInput($productId, $warehouseId, $quantity, $type);

        // Cek stok cukup jika keluar/koreksi
        if ($type !== 'masuk' && empty($errors)) {
            $currentQty = $this->stockModel->getQuantity($productId, $warehouseId);
            if ($quantity > $currentQty) {
                $errors[] = "Stok tidak cukup. Stok saat ini: {$currentQty}.";
            }
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/stock/add');
        }

        // Proses
        $this->stockModel->adjustStock([
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'quantity'     => $quantity,
            'type'         => $type,
            'notes'        => $notes,
            'created_by'   => Auth::user('id'),
        ]);

        $label = $type === 'masuk' ? 'ditambahkan' : 'dikurangi';
        $this->flash('success', "Stok berhasil {$label} ({$quantity} unit).");
        $this->redirect('/stock');
    }

    // ── GET /stock/transfer ──────────────────────────────────────────────────

    /**
     * Tampilkan form transfer stok antar gudang.
     */
    public function transferPage(): void
    {
        Auth::checkRole('pemilik');

        $warehouses = $this->warehouseModel->getAllActive();
        $products   = $this->productModel->getAllActive();

        $this->view('stock/transfer', [
            'title'      => 'Transfer Stok — ' . APP_NAME,
            'pageTitle'  => 'Transfer Stok',
            'warehouses' => $warehouses,
            'products'   => $products,
            'extraCss'   => ['stock.css'],
        ]);
    }

    // ── POST /stock/transfer ─────────────────────────────────────────────────

    /**
     * Proses transfer stok antar gudang.
     */
    public function transfer(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $productId       = (int) ($_POST['product_id']        ?? 0);
        $fromWarehouseId = (int) ($_POST['from_warehouse_id']  ?? 0);
        $toWarehouseId   = (int) ($_POST['to_warehouse_id']    ?? 0);
        $quantity        = (int) ($_POST['quantity']            ?? 0);
        $notes           = $this->input('notes');

        // Validasi
        $errors = [];

        if ($productId <= 0) {
            $errors[] = 'Pilih produk.';
        }

        if ($fromWarehouseId <= 0 || $toWarehouseId <= 0) {
            $errors[] = 'Pilih gudang asal dan tujuan.';
        }

        if ($fromWarehouseId === $toWarehouseId) {
            $errors[] = 'Gudang asal dan tujuan tidak boleh sama.';
        }

        if ($quantity <= 0) {
            $errors[] = 'Jumlah harus lebih dari 0.';
        }

        // Cek stok cukup
        if (empty($errors)) {
            $currentQty = $this->stockModel->getQuantity($productId, $fromWarehouseId);
            if ($quantity > $currentQty) {
                $errors[] = "Stok di gudang asal tidak cukup. Stok saat ini: {$currentQty}.";
            }
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/stock/transfer');
        }

        // Proses
        $this->stockModel->transferStock([
            'product_id'        => $productId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id'   => $toWarehouseId,
            'quantity'          => $quantity,
            'notes'             => $notes,
            'created_by'        => Auth::user('id'),
        ]);

        $this->flash('success', "Transfer {$quantity} unit berhasil dilakukan.");
        $this->redirect('/stock');
    }

    // ── GET /stock/history ───────────────────────────────────────────────────

    /**
     * Tampilkan riwayat pergerakan stok.
     */
    public function history(): void
    {
        Auth::checkRole('pemilik');

        $type        = $this->query('type', '');
        $warehouseId = (int) ($this->query('warehouse') ?? 0);
        $dateFrom    = $this->query('date_from', '');
        $dateTo      = $this->query('date_to', '');

        $movements  = $this->stockModel->getMovementHistory($type, $warehouseId, $dateFrom, $dateTo);
        $warehouses = $this->warehouseModel->getAllActive();

        $this->view('stock/history', [
            'title'       => 'Riwayat Stok — ' . APP_NAME,
            'pageTitle'   => 'Riwayat Pergerakan Stok',
            'movements'   => $movements,
            'warehouses'  => $warehouses,
            'type'        => $type,
            'warehouseId' => $warehouseId,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'extraCss'    => ['stock.css'],
        ]);
    }

    // ── Validasi ─────────────────────────────────────────────────────────────

    /**
     * Validasi input stok masuk/keluar.
     */
    private function validateStockInput(int $productId, int $warehouseId, int $quantity, string $type): array
    {
        $errors = [];

        if ($productId <= 0) {
            $errors[] = 'Pilih produk.';
        }

        if ($warehouseId <= 0) {
            $errors[] = 'Pilih gudang.';
        }

        if ($quantity <= 0) {
            $errors[] = 'Jumlah harus lebih dari 0.';
        }

        $validTypes = ['masuk', 'keluar', 'koreksi'];
        if (!in_array($type, $validTypes, true)) {
            $errors[] = 'Tipe stok tidak valid.';
        }

        return $errors;
    }
}
