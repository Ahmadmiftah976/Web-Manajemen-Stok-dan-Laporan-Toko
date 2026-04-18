<?php
/**
 * app/controllers/ProductController.php
 * Menangani CRUD produk.
 * Hanya bisa diakses oleh pemilik.
 */

require_once APP_PATH . '/models/Product.php';
require_once APP_PATH . '/models/Stock.php';
require_once APP_PATH . '/models/Warehouse.php';

class ProductController extends Controller
{
    private Product   $productModel;
    private Stock     $stockModel;
    private Warehouse $warehouseModel;

    public function __construct()
    {
        $this->productModel   = new Product();
        $this->stockModel     = new Stock();
        $this->warehouseModel = new Warehouse();
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Kumpulkan dan sanitasi input produk dari form POST.
     * Dipakai bersama oleh store() dan update().
     */
    private function getProductInput(): array
    {
        return [
            'name'         => $this->input('name'),
            'sku'          => strtoupper($this->input('sku', '')),
            'category'     => $this->input('category'),
            'harga_beli'   => (float) ($_POST['harga_beli'] ?? 0),
            'harga_jual'   => (float) ($_POST['harga_jual'] ?? 0),
            'stok_minimum' => (int)   ($_POST['stok_minimum'] ?? 5),
            'description'  => $this->input('description'),
        ];
    }

    // ── GET /products ────────────────────────────────────────────────────────

    /**
     * Tampilkan daftar produk dengan filter & pencarian.
     */
    public function index(): void
    {
        Auth::checkRole('pemilik');

        $search   = $this->query('search', '');
        $category = $this->query('category', '');

        $products   = $this->productModel->getAllActive($search, $category);
        $categories = $this->productModel->getCategories();

        $this->view('products/index', [
            'title'      => 'Produk — ' . APP_NAME,
            'pageTitle'  => 'Produk',
            'products'   => $products,
            'categories' => $categories,
            'search'     => $search,
            'category'   => $category,
            'extraCss'   => ['products.css'],
        ]);
    }

    // ── GET /products/create ─────────────────────────────────────────────────

    /**
     * Tampilkan form tambah produk baru.
     */
    public function create(): void
    {
        Auth::checkRole('pemilik');

        $categories = $this->productModel->getCategories();
        $warehouses = $this->warehouseModel->getAllActive();

        $this->view('products/create', [
            'title'      => 'Tambah Produk — ' . APP_NAME,
            'pageTitle'  => 'Tambah Produk',
            'categories' => $categories,
            'warehouses' => $warehouses,
            'extraCss'   => ['products.css'],
        ]);
    }

    // ── POST /products/store ─────────────────────────────────────────────────

    /**
     * Proses form tambah produk.
     */
    public function store(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        // Ambil input
        $data = $this->getProductInput();

        // Ambil input stok awal
        $initialStock       = (int) ($_POST['initial_stock'] ?? 0);
        $initialWarehouseId = (int) ($_POST['initial_warehouse_id'] ?? 0);

        // Validasi
        $errors = $this->validateProduct($data);

        // Validasi stok awal
        if ($initialStock < 0) {
            $errors[] = 'Stok awal tidak boleh negatif.';
        }

        if ($initialStock > 0 && $initialWarehouseId <= 0) {
            $errors[] = 'Pilih gudang untuk stok awal.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/products/create');
        }

        // Cek SKU unik
        if ($this->productModel->isSkuTaken($data['sku'])) {
            $this->flash('error', 'SKU sudah digunakan oleh produk lain.');
            $this->redirect('/products/create');
        }

        // Simpan produk
        $productId = $this->productModel->createProduct($data);

        // Simpan stok awal jika diisi > 0
        if ($initialStock > 0 && $initialWarehouseId > 0) {
            $this->stockModel->adjustStock([
                'product_id'   => $productId,
                'warehouse_id' => $initialWarehouseId,
                'quantity'     => $initialStock,
                'type'         => 'masuk',
                'notes'        => 'Stok awal saat pembuatan produk',
                'created_by'   => Auth::user('id'),
            ]);
        }

        $this->flash('success', 'Produk berhasil ditambahkan.' . ($initialStock > 0 ? " Stok awal: {$initialStock} unit." : ''));
        $this->redirect('/products');
    }

    // ── GET /products/edit?id=X ──────────────────────────────────────────────

    /**
     * Tampilkan form edit produk.
     */
    public function edit(): void
    {
        Auth::checkRole('pemilik');

        $id = (int) ($this->query('id') ?? 0);
        $product = $this->productModel->findById($id);

        if (!$product) {
            $this->flash('error', 'Produk tidak ditemukan.');
            $this->redirect('/products');
        }

        $categories = $this->productModel->getCategories();

        $this->view('products/edit', [
            'title'      => 'Edit Produk — ' . APP_NAME,
            'pageTitle'  => 'Edit Produk',
            'product'    => $product,
            'categories' => $categories,
            'extraCss'   => ['products.css'],
        ]);
    }

    // ── POST /products/update ────────────────────────────────────────────────

    /**
     * Proses form edit produk.
     */
    public function update(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $id = (int) ($_POST['id'] ?? 0);
        $product = $this->productModel->findById($id);

        if (!$product) {
            $this->flash('error', 'Produk tidak ditemukan.');
            $this->redirect('/products');
        }

        // Ambil input
        $data = $this->getProductInput();

        // Validasi
        $errors = $this->validateProduct($data);

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/products/edit?id=' . $id);
        }

        // Cek SKU unik (abaikan produk saat ini)
        if ($this->productModel->isSkuTaken($data['sku'], $id)) {
            $this->flash('error', 'SKU sudah digunakan oleh produk lain.');
            $this->redirect('/products/edit?id=' . $id);
        }

        // Update
        $this->productModel->updateProduct($id, $data);

        $this->flash('success', 'Produk berhasil diperbarui.');
        $this->redirect('/products');
    }

    // ── POST /products/delete ────────────────────────────────────────────────

    /**
     * Nonaktifkan produk (soft delete).
     */
    public function delete(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $id = (int) ($_POST['id'] ?? 0);
        $product = $this->productModel->findById($id);

        if (!$product) {
            $this->flash('error', 'Produk tidak ditemukan.');
            $this->redirect('/products');
        }

        $this->productModel->deactivate($id);

        $this->flash('success', 'Produk berhasil dihapus.');
        $this->redirect('/products');
    }

    // ── Validasi ─────────────────────────────────────────────────────────────

    /**
     * Validasi input produk.
     */
    private function validateProduct(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Nama produk wajib diisi.';
        }

        if (empty($data['sku'])) {
            $errors[] = 'SKU wajib diisi.';
        }

        if ($data['harga_beli'] < 0) {
            $errors[] = 'Harga beli tidak boleh negatif.';
        }

        if ($data['harga_jual'] < 0) {
            $errors[] = 'Harga jual tidak boleh negatif.';
        }

        if ($data['harga_jual'] > 0 && $data['harga_beli'] > 0 && $data['harga_jual'] < $data['harga_beli']) {
            $errors[] = 'Harga jual sebaiknya tidak lebih rendah dari harga beli.';
        }

        if ($data['stok_minimum'] < 0) {
            $errors[] = 'Stok minimum tidak boleh negatif.';
        }

        return $errors;
    }
}
