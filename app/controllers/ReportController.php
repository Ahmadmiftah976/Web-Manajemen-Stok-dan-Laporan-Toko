<?php
/**
 * app/controllers/ReportController.php
 * Menangani halaman laporan penjualan, laba rugi, dan ekspor CSV.
 * Akses: hanya role 'pemilik'.
 */

require_once APP_PATH . '/models/Report.php';
require_once APP_PATH . '/models/Warehouse.php';

class ReportController extends Controller
{
    private Report $reportModel;

    public function __construct()
    {
        $this->reportModel = new Report();
    }

    /**
     * GET /reports/sales
     */
    public function sales(): void
    {
        Auth::check();
        Auth::checkRole('pemilik');

        // Default filter: bulan ini
        $filters = [
            'date_from'      => $_GET['date_from'] ?? date('Y-m-01'),
            'date_to'        => $_GET['date_to'] ?? date('Y-m-d'),
            'warehouse_id'   => $_GET['warehouse_id'] ?? '',
            'payment_method' => $_GET['payment_method'] ?? ''
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));

        $summary      = $this->reportModel->getSalesSummary($filters);
        $transactions = $this->reportModel->getSalesReport($filters, $page);
        $revenueByDay = $this->reportModel->getRevenueByDay($filters);
        $salesByWarehouse = $this->reportModel->getSalesByWarehouse($filters);

        // Gudang untuk dropdown filter
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->all();

        $this->view('reports/sales', [
            'title'           => 'Laporan Penjualan',
            'pageTitle'       => 'Laporan Penjualan',
            'filters'         => $filters,
            'summary'         => $summary,
            'transactions'    => $transactions,
            'revenueByDay'    => $revenueByDay,
            'salesByWarehouse' => $salesByWarehouse,
            'warehouses'      => $warehouses,
            'extraCss'        => ['reports.css']
        ]);
    }

    /**
     * GET /reports/profit
     */
    public function profit(): void
    {
        Auth::check();
        Auth::checkRole('pemilik');

        $filters = [
            'date_from'    => $_GET['date_from'] ?? date('Y-m-01'),
            'date_to'      => $_GET['date_to'] ?? date('Y-m-d'),
            'warehouse_id' => $_GET['warehouse_id'] ?? ''
        ];

        $summary           = $this->reportModel->getProfitSummary($filters);
        $profitByProduct   = $this->reportModel->getProfitByProduct($filters);
        $revenueByCategory = $this->reportModel->getRevenueByCategory($filters);
        $topProducts       = $this->reportModel->getTopProducts($filters);

        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->all();

        $this->view('reports/profit', [
            'title'             => 'Laporan Laba Rugi',
            'pageTitle'         => 'Laporan Laba Rugi',
            'filters'           => $filters,
            'summary'           => $summary,
            'profitByProduct'   => $profitByProduct,
            'revenueByCategory' => $revenueByCategory,
            'topProducts'       => $topProducts,
            'warehouses'        => $warehouses,
            'extraCss'          => ['reports.css']
        ]);
    }

    /**
     * GET /reports/export?type=sales|profit
     */
    public function export(): void
    {
        Auth::check();
        Auth::checkRole('pemilik');

        $type = $_GET['type'] ?? 'sales';

        $filters = [
            'date_from'      => $_GET['date_from'] ?? date('Y-m-01'),
            'date_to'        => $_GET['date_to'] ?? date('Y-m-d'),
            'warehouse_id'   => $_GET['warehouse_id'] ?? '',
            'payment_method' => $_GET['payment_method'] ?? ''
        ];

        $filename = $type === 'profit'
            ? 'laporan_laba_rugi_' . date('Ymd') . '.csv'
            : 'laporan_penjualan_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM untuk Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($type === 'profit') {
            fputcsv($output, ['Produk', 'Kategori', 'Qty Terjual', 'Pendapatan', 'Modal', 'Laba', 'Margin (%)']);
            $data = $this->reportModel->getProfitByProduct($filters);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['product_name'],
                    $row['category'] ?? '-',
                    $row['qty_sold'],
                    $row['revenue'],
                    $row['cogs'],
                    $row['profit'],
                    $row['margin_pct'] . '%'
                ]);
            }
        } else {
            fputcsv($output, ['Kode Trx', 'Tanggal', 'Kasir', 'Gudang', 'Metode', 'Diskon', 'Total']);
            $data = $this->reportModel->getSalesForExport($filters);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['transaction_code'],
                    $row['transaction_date'],
                    $row['cashier_name'] ?? '-',
                    $row['warehouse_name'] ?? '-',
                    strtoupper($row['payment_method']),
                    $row['discount_amount'],
                    $row['total_amount']
                ]);
            }
        }

        fclose($output);
        exit;
    }
}
