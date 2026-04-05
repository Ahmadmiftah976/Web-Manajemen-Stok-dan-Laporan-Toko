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
     * Export data sebagai CSV. Kolom uang berformat accounting (Rp130.000,00).
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

        // BOM agar Excel membaca karakter Indonesia dengan benar
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fwrite($output, "sep=;\n");

        // Helper: format uang accounting → Rp130.000,00
        $acct = fn($n) => 'Rp' . number_format((float)$n, 2, ',', '.');

        if ($type === 'profit') {
            fputcsv($output, ['No', 'Produk', 'Kategori', 'Qty Terjual', 'Pendapatan', 'Modal', 'Laba', 'Margin'], ';');
            $data = $this->reportModel->getProfitByProduct($filters);
            foreach ($data as $i => $row) {
                fputcsv($output, [
                    $i + 1,
                    $row['product_name'],
                    $row['category'] ?? '-',
                    (int) $row['qty_sold'],
                    $acct($row['revenue']),
                    $acct($row['cogs']),
                    $acct($row['profit']),
                    $row['margin_pct'] . '%'
                ], ';');
            }
        } else {
            fputcsv($output, ['No', 'Kode Trx', 'Tanggal', 'Kasir', 'Gudang', 'Metode', 'Diskon', 'Total'], ';');
            $data = $this->reportModel->getSalesForExport($filters);
            foreach ($data as $i => $row) {
                fputcsv($output, [
                    $i + 1,
                    $row['transaction_code'],
                    date('d/m/Y H:i', strtotime($row['transaction_date'])),
                    $row['cashier_name'] ?? '-',
                    $row['warehouse_name'] ?? '-',
                    strtoupper($row['payment_method']),
                    $acct($row['discount_amount']),
                    $acct($row['total_amount'])
                ], ';');
            }
        }

        fclose($output);
        exit;
    }

    /**
     * GET /reports/pdf?type=sales|profit
     * Render halaman HTML print-ready dengan SELURUH data laporan.
     * User bisa langsung Save as PDF dari dialog print browser.
     */
    public function exportPdf(): void
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

        $periodLabel = Format::date($filters['date_from'], 'd F Y') . ' — ' . Format::date($filters['date_to'], 'd F Y');

        if ($type === 'profit') {
            $title              = 'Laporan Laba Rugi';
            $summary            = $this->reportModel->getProfitSummary($filters);
            $data               = $this->reportModel->getProfitByProduct($filters);
            $topProducts        = $this->reportModel->getTopProducts($filters);
            $revenueByCategory  = $this->reportModel->getRevenueByCategory($filters);
        } else {
            $title              = 'Laporan Penjualan';
            $summary            = $this->reportModel->getSalesSummary($filters);
            $data               = $this->reportModel->getSalesForExport($filters);
            $revenueByDay       = $this->reportModel->getRevenueByDay($filters);
            $salesByWarehouse   = $this->reportModel->getSalesByWarehouse($filters);
        }

        // Render view standalone (tanpa layout sidebar)
        require APP_PATH . '/views/reports/pdf.php';
        exit;
    }
}
