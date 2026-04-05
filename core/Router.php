<?php
/**
 * core/Router.php
 * Front Controller sederhana.
 * Membaca URL → menentukan Controller & Method → menjalankannya.
 *
 * Format URL: /controller/method/param1/param2
 *
 * Contoh:
 *   /               → DashboardController::index()
 *   /products       → ProductController::index()
 *   /products/create → ProductController::create()
 *   /products/edit/5 → ProductController::edit(5)
 *   /auth/login     → AuthController::login()
 */

class Router
{
    /**
     * Tabel route eksplisit.
     * Format: 'METHOD /uri' => ['ControllerClass', 'method']
     *
     * Digunakan untuk route yang tidak mengikuti konvensi otomatis,
     * seperti webhook, alias URL, atau route dengan nama berbeda.
     */
    private array $routes = [
        // Auth
        'GET /login' => ['AuthController', 'loginPage'],
        'POST /login' => ['AuthController', 'login'],
        'GET /logout' => ['AuthController', 'logout'],

        // Dashboard
        'GET /' => ['DashboardController', 'index'],
        'GET /dashboard' => ['DashboardController', 'index'],

        // Products
        'GET /products' => ['ProductController', 'index'],
        'GET /products/create' => ['ProductController', 'create'],
        'POST /products/store' => ['ProductController', 'store'],
        'GET /products/edit' => ['ProductController', 'edit'],
        'POST /products/update' => ['ProductController', 'update'],
        'POST /products/delete' => ['ProductController', 'delete'],

        // Stock
        'GET /stock' => ['StockController', 'index'],
        'GET /stock/add' => ['StockController', 'addPage'],
        'POST /stock/add' => ['StockController', 'add'],
        'GET /stock/transfer' => ['StockController', 'transferPage'],
        'POST /stock/transfer' => ['StockController', 'transfer'],
        'GET /stock/history' => ['StockController', 'history'],

        // Kasir
        'GET /kasir' => ['KasirController', 'index'],
        'POST /kasir/checkout' => ['KasirController', 'checkout'],
        'GET /kasir/receipt' => ['KasirController', 'receipt'],

        // Payment
        'POST /payment/create-qris' => ['PaymentController', 'createQris'],
        'POST /payment/webhook' => ['PaymentController', 'webhook'],
        'POST /payment/cancel' => ['PaymentController', 'cancelPayment'],
        'GET /payment/status' => ['PaymentController', 'checkStatus'],

        // Reports
        'GET /reports/sales' => ['ReportController', 'sales'],
        'GET /reports/profit' => ['ReportController', 'profit'],
        'GET /reports/export' => ['ReportController', 'export'],
        'GET /reports/pdf' => ['ReportController', 'exportPdf'],

        // Users (pemilik only)
        'GET /users' => ['UserController', 'index'],
        'GET /users/inactive' => ['UserController', 'inactive'],
        'GET /users/create' => ['UserController', 'create'],
        'POST /users/store' => ['UserController', 'store'],
        'GET /users/edit' => ['UserController', 'edit'],
        'POST /users/update' => ['UserController', 'update'],
        'POST /users/delete' => ['UserController', 'delete'],
        'POST /users/activate' => ['UserController', 'activate'],
    ];

    /**
     * Titik masuk utama — dipanggil dari public/index.php.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->parseUri();

        $routeKey = "$method $uri";

        // ── Cari di tabel route eksplisit ─────────────────────────────────
        if (isset($this->routes[$routeKey])) {
            [$controllerName, $methodName] = $this->routes[$routeKey];
            $this->run($controllerName, $methodName);
            return;
        }

        // ── Fallback: parsing otomatis /controller/method/param ───────────
        $segments = explode('/', ltrim($uri, '/'));
        $controllerName = $this->toPascalCase($segments[0] ?? 'dashboard') . 'Controller';
        $methodName = $segments[1] ?? 'index';
        $params = array_slice($segments, 2);

        $this->run($controllerName, $methodName, $params);
    }

    /**
     * Instantiasi controller dan jalankan method-nya.
     */
    private function run(string $controllerName, string $methodName, array $params = []): void
    {
        $file = APP_PATH . '/controllers/' . $controllerName . '.php';

        if (!file_exists($file)) {
            $this->notFound("Controller tidak ditemukan: $controllerName");
            return;
        }

        require_once $file;

        if (!class_exists($controllerName)) {
            $this->notFound("Class tidak ditemukan: $controllerName");
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            $this->notFound("Method tidak ditemukan: $controllerName::$methodName");
            return;
        }

        call_user_func_array([$controller, $methodName], $params);
    }

    /**
     * Ambil URI bersih tanpa query string dan base path.
     */
    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];

        // Hapus query string (?foo=bar)
        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Hapus base path (misal: /majmainsight/public)
        $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * Konversi string ke PascalCase.
     * Contoh: 'stock-movements' → 'StockMovements'
     */
    private function toPascalCase(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }

    /**
     * Tampilkan halaman 404.
     */
    private function notFound(string $detail = ''): void
    {
        http_response_code(404);
        if (APP_ENV === 'development') {
            echo "<h1>404 - Tidak Ditemukan</h1><p>$detail</p>";
        } else {
            echo "<h1>404 - Halaman Tidak Ditemukan</h1>";
        }
        exit;
    }
}