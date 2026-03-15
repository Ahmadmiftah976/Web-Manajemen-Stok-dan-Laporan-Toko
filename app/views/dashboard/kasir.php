<?php
/**
 * app/views/dashboard/kasir.php
 * Dashboard khusus operasional Kasir.
 */
?>

<!-- Hero Section Kasir -->
<div class="dashboard-hero" style="background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-900) 100%); margin-bottom: 24px;">
    <div style="position: relative; z-index: 2;">
        <h1 class="hero-title">Halo, Kasir <?= htmlspecialchars(Auth::user('name')) ?>!</h1>
        <p class="hero-subtitle">Mulai shift Anda dan layani pelanggan dengan senyuman.</p>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
    <!-- POS / Penjualan CTA -->
    <div class="pos-cta-card">
        <h3 style="font-size:20px; font-weight:700; color:var(--text-main); margin-bottom:8px;">Siap Berjualan?</h3>
        <p style="color:var(--text-muted); font-size:14px; margin-bottom:24px;">Buka halaman kasir sekarang untuk memproses transaksi baru.</p>
        
        <a href="<?= APP_URL ?>/kasir" class="btn-start-pos">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            Buka Mesin Kasir
        </a>

        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border); width:100%;">
            <div style="font-size:13px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:8px;">Transaksi Anda Hari Ini</div>
            <div style="font-size:36px; font-weight:800; color:var(--primary-600); line-height:1;"><?= number_format($todayTxCount) ?></div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Struk Berhasil (Paid)</div>
        </div>
    </div>

    <!-- Riwayat Transaksi Kasir -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="card-title">10 Transaksi Terakhir Anda</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="act-table">
                <thead>
                    <tr>
                        <th style="width:140px;">Waktu</th>
                        <th style="width:150px;">Invoice</th>
                        <th class="text-end">Total Pembayaran</th>
                        <th class="text-center" style="width:120px;">Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTx)): ?>
                        <tr><td colspan="4" class="empty-state">Belum ada transaksi hari ini. Waktunya jualan!</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentTx as $tx): ?>
                        <tr>
                            <td style="color:var(--text-secondary);"><?= date('H:i', strtotime($tx['transaction_date'])) ?> &bull; <?= date('d M', strtotime($tx['transaction_date'])) ?></td>
                            <td style="font-family:monospace; font-weight:500;"><?= htmlspecialchars($tx['invoice_code'] ?? 'TRX-'.$tx['id']) ?></td>
                            <td class="text-end" style="font-weight:600; color:var(--text-main);">Rp <?= number_format($tx['total_amount'], 0, ',', '.') ?></td>
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
</div>
