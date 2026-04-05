<?php
/**
 * Lokasi: app/views/reports/sales.php
 * Deskripsi: Halaman Laporan Penjualan dengan KPI Cards, Grafik, dan Tabel Data
 */

$fmt = fn($n) => number_format((float)$n, 0, ',', '.');
$totalTrx   = (int) ($summary['total_trx'] ?? 0);
$totalRev   = (float) ($summary['total_revenue'] ?? 0);
$avgPerTrx  = (float) ($summary['avg_per_trx'] ?? 0);
$countTunai = (int) ($summary['count_tunai'] ?? 0);
$countQris  = (int) ($summary['count_qris'] ?? 0);
$qrisPct    = $totalTrx > 0 ? round(($countQris / $totalTrx) * 100, 1) : 0;
?>

<!-- ═══ HEADER ═══ -->
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h5 mb-1 fw-700">Laporan Penjualan</h1>
        <p class="text-muted mb-0" style="font-size:13px;">Analisis transaksi dan tren penjualan</p>
    </div>
</div>

<!-- ═══ FILTER BAR ═══ -->
<form class="report-filter-bar" method="GET" action="<?= APP_URL ?>/reports/sales">
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
    <div class="filter-group">
        <label>Metode Bayar</label>
        <select name="payment_method">
            <option value="">Semua</option>
            <option value="tunai" <?= ($filters['payment_method'] === 'tunai') ? 'selected' : '' ?>>Tunai</option>
            <option value="qris" <?= ($filters['payment_method'] === 'qris') ? 'selected' : '' ?>>QRIS</option>
        </select>
    </div>
    <button type="submit" class="btn-filter">Terapkan</button>
    <a href="<?= APP_URL ?>/reports/pdf?type=sales&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>&warehouse_id=<?= urlencode($filters['warehouse_id']) ?>&payment_method=<?= urlencode($filters['payment_method']) ?>"
       class="btn-export" target="_blank">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Export PDF
    </a>
    <a href="<?= APP_URL ?>/reports/export?type=sales&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>&warehouse_id=<?= urlencode($filters['warehouse_id']) ?>&payment_method=<?= urlencode($filters['payment_method']) ?>"
       class="btn-export">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </a>
</form>

<!-- ═══ KPI CARDS ═══ -->
<div class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-label">Total Pendapatan</span>
        <span class="kpi-value">Rp <?= $fmt($totalRev) ?></span>
        <span class="kpi-sub">Periode <?= date('d M', strtotime($filters['date_from'])) ?> — <?= date('d M Y', strtotime($filters['date_to'])) ?></span>
    </div>
    <div class="kpi-card kpi-neutral">
        <span class="kpi-label">Jumlah Transaksi</span>
        <span class="kpi-value"><?= $totalTrx ?></span>
        <span class="kpi-sub">Transaksi berhasil (paid)</span>
    </div>
    <div class="kpi-card kpi-success">
        <span class="kpi-label">Rata-rata / Transaksi</span>
        <span class="kpi-value">Rp <?= $fmt($avgPerTrx) ?></span>
        <span class="kpi-sub">Nilai rata-rata belanja</span>
    </div>
    <div class="kpi-card kpi-warning">
        <span class="kpi-label">Adopsi QRIS</span>
        <span class="kpi-value"><?= $qrisPct ?>%</span>
        <span class="kpi-sub"><?= $countQris ?> QRIS / <?= $countTunai ?> Tunai</span>
    </div>
</div>

<!-- ═══ GRAFIK ═══ -->
<div class="chart-grid">
    <div class="chart-box">
        <div class="chart-title">Tren Penjualan Harian</div>
        <canvas id="chartRevenue"></canvas>
    </div>
    <div class="chart-box">
        <div class="chart-title">Penjualan per Gudang</div>
        <canvas id="chartWarehouse"></canvas>
    </div>
</div>

<!-- ═══ TABEL TRANSAKSI ═══ -->
<div class="report-table-wrapper">
    <div class="report-table-header">
        <h3>Daftar Transaksi</h3>
        <span style="font-size:12px; color:var(--text-muted);"><?= $transactions['total'] ?> transaksi ditemukan</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Kode Trx</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th>Gudang</th>
                    <th>Metode</th>
                    <th class="text-right">Diskon</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions['data'])): ?>
                    <tr><td colspan="7" class="text-center" style="padding:30px; color:var(--text-muted);">Tidak ada transaksi untuk periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions['data'] as $trx): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($trx['transaction_code']) ?></strong></td>
                        <td><?= date('d M Y, H:i', strtotime($trx['transaction_date'])) ?></td>
                        <td><?= htmlspecialchars($trx['cashier_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($trx['warehouse_name'] ?? '-') ?></td>
                        <td>
                            <span class="method-badge <?= $trx['payment_method'] ?>">
                                <?= strtoupper($trx['payment_method']) ?>
                            </span>
                        </td>
                        <td class="num"><?= $fmt($trx['discount_amount'] ?? 0) ?></td>
                        <td class="num"><strong>Rp <?= $fmt($trx['total_amount']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($transactions['totalPages'] > 1): ?>
    <div class="report-pagination">
        <?php
        $baseUrl = APP_URL . '/reports/sales?' . http_build_query(array_diff_key($filters, ['page' => '']));
        for ($i = 1; $i <= $transactions['totalPages']; $i++):
        ?>
            <?php if ($i === $transactions['page']): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= $baseUrl ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ CHART.JS ═══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const revenueByDay = <?= json_encode($revenueByDay) ?>;
const salesByWarehouse = <?= json_encode($salesByWarehouse) ?>;

const colors = {
    primary: '#2563eb',
    primaryLight: 'rgba(37, 99, 235, 0.1)',
    bars: ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#be185d', '#4f46e5']
};

// Grafik Garis — Tren Harian
new Chart(document.getElementById('chartRevenue').getContext('2d'), {
    type: 'line',
    data: {
        labels: revenueByDay.map(d => {
            const date = new Date(d.day);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
        }),
        datasets: [{
            label: 'Pendapatan',
            data: revenueByDay.map(d => parseFloat(d.revenue)),
            borderColor: colors.primary,
            backgroundColor: colors.primaryLight,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 6,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') } }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => 'Rp ' + (v/1000).toLocaleString('id-ID') + 'K', font: { size: 11 } },
                grid: { color: '#f1f5f9' }
            },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});

// Grafik Bar — Per Gudang
const whCtx = document.getElementById('chartWarehouse')?.getContext('2d');
if (whCtx && salesByWarehouse.length > 0) {
    new Chart(whCtx, {
        type: 'bar',
        data: {
            labels: salesByWarehouse.map(d => d.warehouse_name ?? 'N/A'),
            datasets: [{
                label: 'Pendapatan',
                data: salesByWarehouse.map(d => parseFloat(d.revenue)),
                backgroundColor: colors.bars,
                borderRadius: 4,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Rp ' + ctx.parsed.x.toLocaleString('id-ID') + ' (' + salesByWarehouse[ctx.dataIndex].trx_count + ' trx)'
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { callback: v => 'Rp ' + (v/1000).toLocaleString('id-ID') + 'K', font: { size: 11 } },
                    grid: { color: '#f1f5f9' }
                },
                y: { ticks: { font: { size: 12, weight: 600 } }, grid: { display: false } }
            }
        }
    });
}
</script>
