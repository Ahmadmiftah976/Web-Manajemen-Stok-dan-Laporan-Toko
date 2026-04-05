<?php
/**
 * app/views/reports/pdf.php
 * Halaman print-ready untuk export PDF.
 * Standalone (tidak menggunakan layout utama).
 * Berisi: header, KPI, grafik, dan seluruh data tanpa pagination.
 */

$rp  = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
$num = fn($n) => number_format((float)$n, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?> — MajmaInsight</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.5;
            padding: 20px 30px;
        }

        /* ── Header ── */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .report-header h1 { font-size: 16pt; font-weight: 700; color: #1e293b; }
        .report-header .brand { font-size: 9pt; color: #64748b; }
        .report-header .meta { text-align: right; font-size: 9pt; color: #64748b; }

        /* ── KPI ── */
        .kpi-row { display: flex; gap: 12px; margin-bottom: 20px; }
        .kpi-box { flex: 1; border: 1px solid #d1d5db; border-radius: 4px; padding: 10px 14px; }
        .kpi-box .label { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; }
        .kpi-box .value { font-size: 14pt; font-weight: 700; color: #1e293b; margin-top: 2px; }
        .kpi-box .sub { font-size: 8pt; color: #94a3b8; }

        /* ── Charts ── */
        .chart-row { display: flex; gap: 16px; margin-bottom: 20px; }
        .chart-box { flex: 1; border: 1px solid #d1d5db; border-radius: 4px; padding: 14px; }
        .chart-box .chart-title { font-size: 9pt; font-weight: 700; text-transform: uppercase; color: #1e293b; margin-bottom: 8px; letter-spacing: 0.3px; }
        .chart-box canvas { width: 100% !important; height: 200px !important; }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9pt; }
        .section-title { font-size: 11pt; font-weight: 700; color: #1e293b; margin: 20px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
        th { background: #1e293b; color: #fff; font-weight: 600; padding: 7px 10px; text-align: left; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; }
        td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: 600; }
        .mono { font-family: 'Consolas', 'Courier New', monospace; font-size: 8.5pt; }
        .total-row td { background: #e2e8f0 !important; font-weight: 700; border-top: 2px solid #1e293b; }

        /* ── Print ── */
        .no-print { margin-bottom: 20px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 12mm 15mm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            .chart-row { page-break-inside: avoid; }
        }

        .btn-print { display: inline-flex; align-items: center; gap: 6px; padding: 8px 20px; font-size: 10pt; font-weight: 600; color: #fff; background: #1e293b; border: none; border-radius: 6px; cursor: pointer; }
        .btn-print:hover { background: #334155; }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button>
</div>

<!-- ═══ HEADER ═══ -->
<div class="report-header">
    <div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <div class="brand">MajmaInsight — Sistem Manajemen Bisnis</div>
    </div>
    <div class="meta">
        <div>Periode: <?= $periodLabel ?></div>
        <div>Dicetak: <?= Format::datetime(date('Y-m-d H:i:s')) ?></div>
        <div>Oleh: <?= htmlspecialchars(Auth::user('name')) ?></div>
    </div>
</div>

<?php if ($type === 'profit'): ?>
<!-- ═══════════════════════════════════════════════════════════
     LAPORAN LABA RUGI
     ═══════════════════════════════════════════════════════ -->

<?php
    $totalRevenue  = (float)($summary['total_revenue'] ?? 0);
    $totalCogs     = (float)($summary['total_cogs'] ?? 0);
    $grossProfit   = (float)($summary['gross_profit'] ?? 0);
    $totalDiscount = (float)($summary['total_discount'] ?? 0);
    $netProfit     = $grossProfit - $totalDiscount;
    $marginPct     = $summary['margin_pct'] ?? 0;
?>

<div class="kpi-row">
    <div class="kpi-box">
        <div class="label">Total Pendapatan</div>
        <div class="value"><?= $rp($totalRevenue) ?></div>
    </div>
    <div class="kpi-box">
        <div class="label">Total Modal (HPP)</div>
        <div class="value"><?= $rp($totalCogs) ?></div>
    </div>
    <div class="kpi-box">
        <div class="label">Laba Kotor</div>
        <div class="value"><?= $rp($grossProfit) ?></div>
        <div class="sub">Margin: <?= $marginPct ?>%</div>
    </div>
    <div class="kpi-box">
        <div class="label">Laba Bersih</div>
        <div class="value"><?= $rp($netProfit) ?></div>
        <div class="sub">Setelah diskon <?= $rp($totalDiscount) ?></div>
    </div>
</div>

<!-- Grafik -->
<div class="chart-row">
    <div class="chart-box">
        <div class="chart-title">Laba per Produk (Top 10)</div>
        <canvas id="chartProfitProduct"></canvas>
    </div>
    <div class="chart-box">
        <div class="chart-title">Komposisi Pendapatan per Kategori</div>
        <canvas id="chartCategory"></canvas>
    </div>
</div>

<!-- Top 5 Produk -->
<?php if (!empty($topProducts)): ?>
<div class="section-title">Top 5 Produk Terlaris</div>
<table>
    <thead><tr><th style="width:30px;">#</th><th>Produk</th><th>Kategori</th><th class="text-right">Qty Terjual</th><th class="text-right">Pendapatan</th></tr></thead>
    <tbody>
        <?php foreach ($topProducts as $i => $tp): ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td class="fw-bold"><?= htmlspecialchars($tp['product_name']) ?></td>
            <td><?= htmlspecialchars($tp['category'] ?? '-') ?></td>
            <td class="text-right"><?= $num($tp['qty_sold']) ?></td>
            <td class="text-right"><?= $rp($tp['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Detail Per Produk -->
<div class="section-title">Analisis Laba per Produk (<?= count($data) ?> produk)</div>
<table>
    <thead><tr><th style="width:30px;">No</th><th>Produk</th><th>Kategori</th><th class="text-right">Qty</th><th class="text-right">Pendapatan</th><th class="text-right">Modal</th><th class="text-right">Laba</th><th class="text-center">Margin</th></tr></thead>
    <tbody>
        <?php foreach ($data as $i => $row): ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td class="fw-bold"><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= htmlspecialchars($row['category'] ?? '-') ?></td>
            <td class="text-right"><?= $num($row['qty_sold']) ?></td>
            <td class="text-right"><?= $rp($row['revenue']) ?></td>
            <td class="text-right"><?= $rp($row['cogs']) ?></td>
            <td class="text-right fw-bold"><?= $rp($row['profit']) ?></td>
            <td class="text-center"><?= $row['margin_pct'] ?>%</td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="4" class="text-right">TOTAL</td>
            <td class="text-right"><?= $rp($totalRevenue) ?></td>
            <td class="text-right"><?= $rp($totalCogs) ?></td>
            <td class="text-right"><?= $rp($grossProfit) ?></td>
            <td class="text-center"><?= $marginPct ?>%</td>
        </tr>
    </tbody>
</table>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════
     LAPORAN PENJUALAN
     ═══════════════════════════════════════════════════════ -->

<?php
    $totalTrx   = (int)($summary['total_trx'] ?? 0);
    $totalRev   = (float)($summary['total_revenue'] ?? 0);
    $avgPerTrx  = (float)($summary['avg_per_trx'] ?? 0);
    $countTunai = (int)($summary['count_tunai'] ?? 0);
    $countQris  = (int)($summary['count_qris'] ?? 0);
    $qrisPct    = $totalTrx > 0 ? round(($countQris / $totalTrx) * 100, 1) : 0;
?>

<div class="kpi-row">
    <div class="kpi-box">
        <div class="label">Total Pendapatan</div>
        <div class="value"><?= $rp($totalRev) ?></div>
    </div>
    <div class="kpi-box">
        <div class="label">Jumlah Transaksi</div>
        <div class="value"><?= $totalTrx ?></div>
        <div class="sub"><?= $countTunai ?> Tunai / <?= $countQris ?> QRIS</div>
    </div>
    <div class="kpi-box">
        <div class="label">Rata-rata / Transaksi</div>
        <div class="value"><?= $rp($avgPerTrx) ?></div>
    </div>
    <div class="kpi-box">
        <div class="label">Adopsi QRIS</div>
        <div class="value"><?= $qrisPct ?>%</div>
        <div class="sub">dari total transaksi</div>
    </div>
</div>

<!-- Grafik -->
<div class="chart-row">
    <div class="chart-box">
        <div class="chart-title">Tren Penjualan Harian</div>
        <canvas id="chartRevenue"></canvas>
    </div>
    <div class="chart-box">
        <div class="chart-title">Penjualan per Gudang</div>
        <canvas id="chartWarehouse"></canvas>
    </div>
</div>

<!-- Daftar Transaksi -->
<div class="section-title">Daftar Transaksi (<?= count($data) ?> transaksi)</div>
<table>
    <thead><tr><th style="width:30px;">No</th><th>Kode Transaksi</th><th>Tanggal</th><th>Kasir</th><th>Gudang</th><th class="text-center">Metode</th><th class="text-right">Diskon</th><th class="text-right">Total</th></tr></thead>
    <tbody>
        <?php $grandTotal = 0; $grandDiskon = 0; ?>
        <?php foreach ($data as $i => $row): ?>
        <?php $grandTotal += (float)$row['total_amount']; $grandDiskon += (float)$row['discount_amount']; ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td class="mono fw-bold"><?= htmlspecialchars($row['transaction_code']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($row['transaction_date'])) ?></td>
            <td><?= htmlspecialchars($row['cashier_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['warehouse_name'] ?? '-') ?></td>
            <td class="text-center"><?= strtoupper($row['payment_method']) ?></td>
            <td class="text-right"><?= $rp($row['discount_amount']) ?></td>
            <td class="text-right fw-bold"><?= $rp($row['total_amount']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="6" class="text-right">TOTAL (<?= count($data) ?> transaksi)</td>
            <td class="text-right"><?= $rp($grandDiskon) ?></td>
            <td class="text-right"><?= $rp($grandTotal) ?></td>
        </tr>
    </tbody>
</table>

<?php endif; ?>

<!-- ═══ Chart.js ═══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartColors = {
    primary: '#2563eb',
    primaryLight: 'rgba(37, 99, 235, 0.15)',
    bars: ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#be185d', '#4f46e5'],
    cogs: '#94a3b8',
    profit: '#16a34a'
};

<?php if ($type === 'profit'): ?>

// ── Laba per Produk (Bar chart) ──
const profitData = <?= json_encode(array_slice($data, 0, 10)) ?>;
new Chart(document.getElementById('chartProfitProduct').getContext('2d'), {
    type: 'bar',
    data: {
        labels: profitData.map(d => d.product_name.length > 20 ? d.product_name.substring(0,20) + '…' : d.product_name),
        datasets: [
            { label: 'Pendapatan', data: profitData.map(d => parseFloat(d.revenue)), backgroundColor: chartColors.primary, borderRadius: 3, barPercentage: 0.7 },
            { label: 'Modal', data: profitData.map(d => parseFloat(d.cogs)), backgroundColor: chartColors.cogs, borderRadius: 3, barPercentage: 0.7 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
        plugins: { legend: { position: 'top', labels: { font: { size: 9 }, boxWidth: 10 } } },
        scales: {
            x: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000).toLocaleString('id-ID') + 'K', font: { size: 8 } }, grid: { color: '#f1f5f9' } },
            y: { ticks: { font: { size: 8 } }, grid: { display: false } }
        }
    }
});

// ── Pendapatan per Kategori (Donut) ──
const catData = <?= json_encode($revenueByCategory ?? []) ?>;
if (catData.length > 0) {
    new Chart(document.getElementById('chartCategory').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: catData.map(d => d.category),
            datasets: [{ data: catData.map(d => parseFloat(d.revenue)), backgroundColor: chartColors.bars, borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '55%',
            plugins: {
                legend: { position: 'right', labels: { font: { size: 9 }, padding: 8, boxWidth: 10 } }
            }
        }
    });
}

<?php else: ?>

// ── Tren Penjualan Harian (Line chart) ──
const revenueByDay = <?= json_encode($revenueByDay ?? []) ?>;
new Chart(document.getElementById('chartRevenue').getContext('2d'), {
    type: 'line',
    data: {
        labels: revenueByDay.map(d => { const dt = new Date(d.day); return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }); }),
        datasets: [{
            label: 'Pendapatan', data: revenueByDay.map(d => parseFloat(d.revenue)),
            borderColor: chartColors.primary, backgroundColor: chartColors.primaryLight,
            fill: true, tension: 0.3, pointRadius: 3, borderWidth: 2
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000).toLocaleString('id-ID') + 'K', font: { size: 8 } }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { size: 8 } }, grid: { display: false } }
        }
    }
});

// ── Penjualan per Gudang (Bar chart) ──
const salesByWarehouse = <?= json_encode($salesByWarehouse ?? []) ?>;
if (salesByWarehouse.length > 0) {
    new Chart(document.getElementById('chartWarehouse').getContext('2d'), {
        type: 'bar',
        data: {
            labels: salesByWarehouse.map(d => d.warehouse_name ?? 'N/A'),
            datasets: [{ label: 'Pendapatan', data: salesByWarehouse.map(d => parseFloat(d.revenue)), backgroundColor: chartColors.bars, borderRadius: 4, barThickness: 30 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000).toLocaleString('id-ID') + 'K', font: { size: 8 } }, grid: { color: '#f1f5f9' } },
                y: { ticks: { font: { size: 9, weight: 600 } }, grid: { display: false } }
            }
        }
    });
}

<?php endif; ?>

// Auto print setelah grafik selesai render
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 800);
});
</script>

</body>
</html>
