<?php
/**
 * app/controllers/DashboardController.php
 */

require_once APP_PATH . '/models/Dashboard.php';

class DashboardController extends Controller
{
    private Dashboard $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new Dashboard();
    }

    public function index(): void
    {
        Auth::check();

        if (Auth::isPemilik()) {
            $this->loadPemilikDashboard();
        } else {
            $this->loadKasirDashboard();
        }
    }

    private function loadPemilikDashboard(): void
    {
        $todaySales = $this->dashboardModel->getTodaySales();
        $todayTxCount = $this->dashboardModel->getTodayTransactionCount();
        $monthlyProfit = $this->dashboardModel->getMonthlyGrossProfit();
        $lowStockCount = $this->dashboardModel->getLowStockCount();
        
        $salesTrend = $this->dashboardModel->getSalesTrend7Days();
        $topProducts = $this->dashboardModel->getTopProductsThisMonth();
        $recentTx = $this->dashboardModel->getRecentTransactions(5);

        $this->view('dashboard/pemilik', [
            'title'         => 'Dashboard Pemilik — ' . APP_NAME,
            'pageTitle'     => 'Dashboard Utama',
            'extraCss'      => 'dashboard.css',
            'todaySales'    => $todaySales,
            'todayTxCount'  => $todayTxCount,
            'monthlyProfit' => $monthlyProfit,
            'lowStockCount' => $lowStockCount,
            'salesTrend'    => $salesTrend,
            'topProducts'   => $topProducts,
            'recentTx'      => $recentTx,
        ]);
    }

    private function loadKasirDashboard(): void
    {
        $cashierId = (int) Auth::user('id');
        $todayTxCount = $this->dashboardModel->getTodayTransactionCount($cashierId);
        $recentTx = $this->dashboardModel->getRecentTransactions(10, $cashierId);

        $this->view('dashboard/kasir', [
            'title'        => 'Dashboard Kasir — ' . APP_NAME,
            'pageTitle'    => 'Ringkasan Kasir',
            'extraCss'     => 'dashboard.css',
            'todayTxCount' => $todayTxCount,
            'recentTx'     => $recentTx,
        ]);
    }
}