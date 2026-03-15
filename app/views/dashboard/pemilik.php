<?php
/**
 * app/views/dashboard/pemilik.php
 * Dashboard untuk role Pemilik (Insight & Analitik Utama)
 */
?>

<!-- Hero Section -->
<div class="dashboard-hero">
    <div style="position: relative; z-index: 2;">
        <h1 class="hero-title">Selamat datang, <?= htmlspecialchars(Auth::user('name')) ?> 👋</h1>
        <p class="hero-subtitle">Berikut adalah ringkasan performa bisnis Anda hari ini.</p>
    </div>
</div>

<!-- KPI Grid -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-title">Pendapatan Hari Ini</div>
        <div class="kpi-value text-primary">Rp <?= number_format($todaySales, 0, ',', '.') ?></div>
        <div class="kpi-sub">Total omzet dari transaksi Paid</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-title">Transaksi Hari Ini</div>
        <div class="kpi-value"><?= number_format($todayTxCount) ?></div>
        <div class="kpi-sub">Jumlah struk yang berhasil dibuat</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-title">Stok Menipis</div>
        <div class="kpi-value text-warning"><?= number_format($lowStockCount) ?></div>
        <div class="kpi-sub">Item yang menyentuh batas minimum</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-title">Estimasi Laba Kotor</div>
        <div class="kpi-value text-success">Rp <?= number_format($monthlyProfit, 0, ',', '.') ?></div>
        <div class="kpi-sub">Akumulasi bulan ini (Penjualan - HPP)</div>
    </div>
</div>

<!-- Charts Grid -->
<div class="dashboard-grid">
    <!-- Tren Penjualan -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">Tren Pendapatan (7 Hari Terakhir)</h3>
        </div>
        <div class="card-body">
            <div class="chart-container-line">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Produk -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">Top 5 Produk Terlaris (Bulan Ini)</h3>
        </div>
        <div class="card-body" style="display:flex; align-items:center; justify-content:center;">
            <?php if (empty($topProducts)): ?>
                <div class="empty-state">Belum ada data penjualan bulan ini.</div>
            <?php else: ?>
                <div class="chart-container-donut">
                    <canvas id="topProductChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="dashboard-card" style="margin-bottom: 40px;">
    <div class="card-header">
        <h3 class="card-title">Transaksi Terbaru</h3>
        <a href="<?= APP_URL ?>/reports/sales" style="font-size:13px; color:var(--primary-600); font-weight:600; text-decoration:none;">Lihat Laporan Lengkap &rarr;</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="act-table">
            <thead>
                <tr>
                    <th style="width:160px;">ID Transaksi</th>
                    <th style="width:160px;">Waktu</th>
                    <th>Kasir</th>
                    <th class="text-end">Total</th>
                    <th class="text-center" style="width:120px;">Metode</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTx)): ?>
                    <tr><td colspan="5" class="empty-state">Belum ada transaksi sama sekali.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentTx as $tx): ?>
                    <tr>
                        <td style="font-family:monospace; color:var(--text-secondary);"><?= htmlspecialchars($tx['invoice_code'] ?? 'TRX-'.$tx['id']) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($tx['transaction_date'])) ?></td>
                        <td><?= htmlspecialchars($tx['cashier_name'] ?? 'Kasir') ?></td>
                        <td class="text-end" style="font-weight:600;">Rp <?= number_format($tx['total_amount'], 0, ',', '.') ?></td>
                        <td class="text-center">
                            <?php if ($tx['payment_method'] === 'qris'): ?>
                                <span style="font-size:11px; padding:2px 8px; border-radius:4px; font-weight:600; background:var(--primary-100); color:var(--primary-700);">QRIS</span>
                            <?php else: ?>
                                <span style="font-size:11px; padding:2px 8px; border-radius:4px; font-weight:600; background:var(--gray-200); color:var(--gray-700);">TUNAI</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // --- 1. Line Chart : Tren Penjualan ---
        const rawSalesTrend = <?= json_encode($salesTrend) ?>;
        
        const trendLabels = rawSalesTrend.map(row => row.label);
        const trendData = rawSalesTrend.map(row => parseFloat(row.total));

        const ctxLine = document.getElementById('salesTrendChart')?.getContext('2d');
        if (ctxLine) {
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: trendData,
                        borderColor: '#2563eb', // primary-600
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3, // smooth curves
                        pointBackgroundColor: '#2563eb',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => 'Rp ' + Number(value).toLocaleString('id-ID')
                            },
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // --- 2. Doughnut Chart : Top Produk ---
        const rawTopProducts = <?= json_encode($topProducts) ?>;
        const donutCtx = document.getElementById('topProductChart')?.getContext('2d');
        
        if (donutCtx && rawTopProducts.length > 0) {
            const donutLabels = rawTopProducts.map(row => row.label);
            const donutData = rawTopProducts.map(row => parseInt(row.total_qty));
            
            new Chart(donutCtx, {
                type: 'doughnut',
                data: {
                    labels: donutLabels,
                    datasets: [{
                        data: donutData,
                        backgroundColor: [
                            '#3b82f6', // blue-500
                            '#10b981', // emerald-500
                            '#f59e0b', // amber-500
                            '#6366f1', // indigo-500
                            '#ec4899'  // pink-500
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: { size: 11, family: 'Inter, sans-serif' }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
