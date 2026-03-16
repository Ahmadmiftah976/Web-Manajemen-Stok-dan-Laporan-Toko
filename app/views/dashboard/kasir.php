<?php
/**
 * app/views/dashboard/kasir.php
 * Dashboard khusus operasional Kasir.
 */
?>

<!-- Hero Section Kasir — uses sidebar color for consistent dark bg -->
<div class="dashboard-hero" style="margin-bottom: 24px;">
    <div style="position: relative; z-index: 2;">
        <h1 class="hero-title" style="color: #ffffff !important;">Halo, <?= htmlspecialchars(Auth::user('name')) ?> 👋</h1>
        <p class="hero-subtitle" style="color: rgba(255,255,255,0.7) !important;">Mulai shift Anda dan layani pelanggan dengan senyuman.</p>
    </div>
</div>

<!-- Main Grid: Left stats + Right table -->
<div class="dashboard-grid" style="grid-template-columns: 1fr 2fr; align-items: start;">

    <!-- LEFT COLUMN -->
    <div style="display:flex; flex-direction:column; gap:20px;">

        <!-- Metode Pembayaran -->
        <div class="dashboard-card" style="padding: 20px;">
            <div style="font-size:13px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; letter-spacing:0.3px; margin-bottom:14px;">
                💰 Metode Pembayaran Hari Ini
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 20px;">
                <!-- Tunai -->
                <div style="background:#f0fdf4; padding:14px; border-radius:8px; border:1px solid #bbf7d0;">
                    <div style="font-size:11px; color:#16a34a; font-weight:700; letter-spacing:0.5px; margin-bottom:6px;">TUNAI</div>
                    <div style="font-size:22px; font-weight:700; color:#15803d;"><?= number_format($paymentStats['tunai']['count']) ?> <span style="font-size:12px; font-weight:500; color:#4ade80;">trx</span></div>
                    <div style="font-size:13px; font-weight:600; color:#059669; margin-top:4px;">Rp <?= number_format($paymentStats['tunai']['total'], 0, ',', '.') ?></div>
                </div>
                <!-- QRIS -->
                <div style="background:#eff6ff; padding:14px; border-radius:8px; border:1px solid #bfdbfe;">
                    <div style="font-size:11px; color:#2563eb; font-weight:700; letter-spacing:0.5px; margin-bottom:6px;">QRIS</div>
                    <div style="font-size:22px; font-weight:700; color:#1d4ed8;"><?= number_format($paymentStats['qris']['count']) ?> <span style="font-size:12px; font-weight:500; color:#60a5fa;">trx</span></div>
                    <div style="font-size:13px; font-weight:600; color:#2563eb; margin-top:4px;">Rp <?= number_format($paymentStats['qris']['total'], 0, ',', '.') ?></div>
                </div>
            </div>

            <!-- Total Transaksi -->
            <div style="padding-top: 16px; border-top: 1px solid var(--border);">
                <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; letter-spacing:0.3px; margin-bottom:6px;">Total Transaksi Anda</div>
                <div style="display:flex; align-items:baseline; gap:8px;">
                    <span style="font-size:36px; font-weight:800; color:var(--primary-600); line-height:1;"><?= number_format($todayTxCount) ?></span>
                    <span style="font-size:13px; color:var(--text-muted);">struk berhasil</span>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="dashboard-card" style="padding:0;">
            <div class="card-header" style="border-bottom:1px solid var(--border);">
                <h3 class="card-title" style="display:flex; align-items:center; gap:8px; color:#d97706;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Stok Menipis
                </h3>
            </div>
            <div style="padding:0;">
                <?php if (empty($lowStockAlerts)): ?>
                    <div style="padding:24px; text-align:center; color:var(--text-muted); font-size:13px;">
                        ✅ Semua stok produk aman.
                    </div>
                <?php else: ?>
                    <?php foreach ($lowStockAlerts as $i => $alert): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; <?= $i < count($lowStockAlerts) - 1 ? 'border-bottom:1px solid var(--border);' : '' ?>">
                            <div>
                                <div style="font-size:13px; font-weight:600; color:var(--text-main);"><?= htmlspecialchars($alert['name']) ?></div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">SKU: <?= htmlspecialchars($alert['sku']) ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:15px; font-weight:700; color:#dc2626;"><?= (int)$alert['current_stock'] ?> <span style="font-size:11px; font-weight:500; color:var(--text-muted);">pcs</span></div>
                                <div style="font-size:10px; color:var(--text-muted);">min: <?= (int)$alert['stok_minimum'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Riwayat Transaksi Kasir -->
    <div class="dashboard-card" style="align-self: start;">
        <div class="card-header">
            <h3 class="card-title">📋 10 Transaksi Terakhir Anda</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="act-table">
                <thead>
                    <tr>
                        <th style="width:130px;">Waktu</th>
                        <th style="width:140px;">Invoice</th>
                        <th class="text-end">Total</th>
                        <th class="text-center" style="width:90px;">Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTx)): ?>
                        <tr><td colspan="4" class="empty-state">Belum ada transaksi hari ini. Waktunya jualan! 🛒</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentTx as $tx): ?>
                        <tr>
                            <td style="color:var(--text-secondary); font-size:12px;"><?= date('H:i', strtotime($tx['transaction_date'])) ?> &bull; <?= date('d M', strtotime($tx['transaction_date'])) ?></td>
                            <td style="font-family:monospace; font-weight:500; font-size:12px;"><?= htmlspecialchars($tx['invoice_code'] ?? 'TRX-'.$tx['id']) ?></td>
                            <td class="text-end" style="font-weight:600; color:var(--text-main);">Rp <?= number_format($tx['total_amount'], 0, ',', '.') ?></td>
                            <td class="text-center">
                                <?php if ($tx['payment_method'] === 'qris'): ?>
                                    <span style="font-size:10px; padding:2px 8px; border-radius:4px; font-weight:600; background:#dbeafe; color:#1d4ed8;">QRIS</span>
                                <?php else: ?>
                                    <span style="font-size:10px; padding:2px 8px; border-radius:4px; font-weight:600; background:#f3f4f6; color:#374151;">TUNAI</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
