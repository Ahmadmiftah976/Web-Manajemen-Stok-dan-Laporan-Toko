<?php
/**
 * Lokasi: app/views/reports/profit.php
 * Deskripsi: Halaman Laporan Laba Rugi dengan KPI, Grafik, dan Analisis Produk
 */

$fmt = fn($n) => number_format((float)$n, 0, ',', '.');
$totalRevenue  = (float) ($summary['total_revenue'] ?? 0);
$totalCogs     = (float) ($summary['total_cogs'] ?? 0);
$grossProfit   = (float) ($summary['gross_profit'] ?? 0);
$marginPct     = (float) ($summary['margin_pct'] ?? 0);
$totalDiscount = (float) ($summary['total_discount'] ?? 0);
$netProfit     = $grossProfit - $totalDiscount;
?>

<!-- ═══ HEADER ═══ -->
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h5 mb-1 fw-700">Laporan Laba Rugi</h1>
        <p class="text-muted mb-0" style="font-size:13px;">Analisis profitabilitas dan performa produk</p>
    </div>
</div>

<!-- ═══ FILTER BAR ═══ -->
<form class="report-filter-bar" method="GET" action="<?= APP_URL ?>/reports/profit">
    <div class="filter-group">
        <label>Dari Tanggal</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
    </div>
    <div class="filter-group">
        <label>Sampai Tanggal</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
    </div>
    <div class="filter-group">
        <label>Gudang</label>
        <select name="warehouse_id">
            <option value="">Semua Gudang</option>
            <?php foreach ($warehouses as $wh): ?>
                <option value="<?= $wh['id'] ?>" <?= ($filters['warehouse_id'] == $wh['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($wh['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn-filter">Terapkan</button>
    <a href="<?= APP_URL ?>/reports/pdf?type=profit&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>&warehouse_id=<?= urlencode($filters['warehouse_id']) ?>"
       class="btn-export" target="_blank">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Export PDF
    </a>
    <a href="<?= APP_URL ?>/reports/export?type=profit&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>&warehouse_id=<?= urlencode($filters['warehouse_id']) ?>"
       class="btn-export">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </a>
</form>

<!-- ═══ KPI CARDS ═══ -->
<div class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-label">Total Pendapatan</span>
        <span class="kpi-value">Rp <?= $fmt($totalRevenue) ?></span>
        <span class="kpi-sub">Harga jual × qty terjual</span>
    </div>
    <div class="kpi-card kpi-neutral">
        <span class="kpi-label">Total Modal (HPP)</span>
        <span class="kpi-value">Rp <?= $fmt($totalCogs) ?></span>
        <span class="kpi-sub">Harga beli × qty terjual</span>
    </div>
    <div class="kpi-card <?= $grossProfit >= 0 ? 'kpi-success' : 'kpi-danger' ?>">
        <span class="kpi-label">Laba Kotor</span>
        <span class="kpi-value">Rp <?= $fmt($grossProfit) ?></span>
        <span class="kpi-sub">Margin: <?= $marginPct ?>%</span>
    </div>
    <div class="kpi-card <?= $netProfit >= 0 ? 'kpi-success' : 'kpi-danger' ?>">
        <span class="kpi-label">Laba Bersih (Setelah Diskon)</span>
        <span class="kpi-value">Rp <?= $fmt($netProfit) ?></span>
        <span class="kpi-sub">Total diskon: Rp <?= $fmt($totalDiscount) ?></span>
    </div>
</div>

<!-- ═══ GRAFIK ═══ -->
<div class="chart-grid">
    <div class="chart-box">
        <div class="chart-title">Laba per Produk (Top 10)</div>
        <canvas id="chartProfitProduct"></canvas>
    </div>
    <div class="chart-box">
        <div class="chart-title">Komposisi Pendapatan per Kategori</div>
        <canvas id="chartCategory"></canvas>
    </div>
</div>

<!-- ═══ TOP 5 PRODUK TERLARIS ═══ -->
<div class="chart-grid">
    <div class="report-table-wrapper" style="margin-bottom:0;">
        <div class="report-table-header">
            <h3>🏆 Top 5 Produk Terlaris</h3>
        </div>
        <table class="top-products-table">
            <?php foreach ($topProducts as $idx => $tp): ?>
            <tr>
                <td class="rank">#<?= $idx + 1 ?></td>
                <td class="product-name"><?= htmlspecialchars($tp['product_name']) ?></td>
                <td style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($tp['category'] ?? '-') ?></td>
                <td class="qty"><?= (int)$tp['qty_sold'] ?> unit</td>
                <td class="num" style="text-align:right;">Rp <?= $fmt($tp['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($topProducts)): ?>
            <tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada data.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Mini insight box -->
    <div class="report-table-wrapper" style="margin-bottom:0;">
        <div class="report-table-header">
            <h3>📊 Ringkasan Insight</h3>
        </div>
        <div style="padding:20px; font-size:13px; color:var(--text-secondary); line-height:1.8;">
            <?php if (!empty($profitByProduct)): ?>
                <?php
                    $bestProduct = $profitByProduct[0];
                    $worstProduct = end($profitByProduct);
                    $highMarginCount = count(array_filter($profitByProduct, fn($p) => $p['margin_pct'] >= 50));
                    $lowMarginCount = count(array_filter($profitByProduct, fn($p) => $p['margin_pct'] < 20));
                ?>
                <p>✅ <strong>Produk paling menguntungkan:</strong> <?= htmlspecialchars($bestProduct['product_name']) ?> dengan laba Rp <?= $fmt($bestProduct['profit']) ?> (margin <?= $bestProduct['margin_pct'] ?>%)</p>
                <?php if ($worstProduct !== $bestProduct): ?>
                <p>⚠️ <strong>Produk margin terendah:</strong> <?= htmlspecialchars($worstProduct['product_name']) ?> (margin <?= $worstProduct['margin_pct'] ?>%)</p>
                <?php endif; ?>
                <p>📈 <strong><?= $highMarginCount ?> produk</strong> memiliki margin ≥50% (sangat baik)</p>
                <?php if ($lowMarginCount > 0): ?>
                <p>🔴 <strong><?= $lowMarginCount ?> produk</strong> memiliki margin <20% — pertimbangkan untuk naikkan harga jual</p>
                <?php endif; ?>
            <?php else: ?>
                <p>Belum ada data transaksi untuk periode ini.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ TABEL ANALISIS PRODUK ═══ -->
<div class="report-table-wrapper">
    <div class="report-table-header">
        <h3>Analisis Laba per Produk</h3>
        <span style="font-size:12px; color:var(--text-muted);"><?= count($profitByProduct) ?> produk terjual</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Pendapatan</th>
                    <th class="text-right">Modal</th>
                    <th class="text-right">Laba</th>
                    <th class="text-center">Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($profitByProduct)): ?>
                    <tr><td colspan="7" class="text-center" style="padding:30px; color:var(--text-muted);">Tidak ada data untuk periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($profitByProduct as $prod): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($prod['product_name']) ?></strong></td>
                        <td style="color:var(--text-secondary);"><?= htmlspecialchars($prod['category'] ?? '-') ?></td>
                        <td class="num"><?= (int)$prod['qty_sold'] ?></td>
                        <td class="num">Rp <?= $fmt($prod['revenue']) ?></td>
                        <td class="num">Rp <?= $fmt($prod['cogs']) ?></td>
                        <td class="num"><strong>Rp <?= $fmt($prod['profit']) ?></strong></td>
                        <td class="text-center">
                            <?php
                                $m = (float) $prod['margin_pct'];
                                $cls = $m >= 50 ? 'high' : ($m >= 20 ? 'medium' : 'low');
                            ?>
                            <span class="margin-badge <?= $cls ?>"><?= $m ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ CHART.JS ═══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const profitByProduct = <?= json_encode(array_slice($profitByProduct, 0, 10)) ?>;
const revenueByCategory = <?= json_encode($revenueByCategory) ?>;

const profitColors = {
    revenue: '#2563eb',
    cogs: '#94a3b8',
    profit: '#16a34a',
    donut: ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#be185d', '#4f46e5']
};

// Grafik Bar Horizontal — Laba per Produk
new Chart(document.getElementById('chartProfitProduct').getContext('2d'), {
    type: 'bar',
    data: {
        labels: profitByProduct.map(d => d.product_name.length > 25 ? d.product_name.substring(0,25) + '...' : d.product_name),
        datasets: [
            {
                label: 'Pendapatan',
                data: profitByProduct.map(d => parseFloat(d.revenue)),
                backgroundColor: profitColors.revenue,
                borderRadius: 3,
                barPercentage: 0.7
            },
            {
                label: 'Modal',
                data: profitByProduct.map(d => parseFloat(d.cogs)),
                backgroundColor: profitColors.cogs,
                borderRadius: 3,
                barPercentage: 0.7
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12, padding: 16 } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': Rp ' + ctx.parsed.x.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: { callback: v => 'Rp ' + (v/1000).toLocaleString('id-ID') + 'K', font: { size: 10 } },
                grid: { color: '#f1f5f9' }
            },
            y: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});

// Grafik Donut — Pendapatan per Kategori
new Chart(document.getElementById('chartCategory').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: revenueByCategory.map(d => d.category),
        datasets: [{
            data: revenueByCategory.map(d => parseFloat(d.revenue)),
            backgroundColor: profitColors.donut,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                position: 'right',
                labels: { font: { size: 11 }, padding: 12, boxWidth: 12 }
            },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const total = ctx.dataset.data.reduce((a,b) => a + b, 0);
                        const pct = ((ctx.parsed / total) * 100).toFixed(1);
                        return ctx.label + ': Rp ' + ctx.parsed.toLocaleString('id-ID') + ' (' + pct + '%)';
                    }
                }
            }
        }
    }
});
</script>
