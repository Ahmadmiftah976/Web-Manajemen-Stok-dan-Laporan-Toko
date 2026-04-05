<?php
/**
 * app/views/stock/history.php
 * Riwayat pergerakan stok dengan filter.
 * Variabel: $movements, $warehouses, $type, $warehouseId, $dateFrom, $dateTo
 */
?>

<div class="page-header">
    <div>
        <h1>Riwayat Pergerakan Stok</h1>
        <div class="page-header-subtitle">Log semua aktivitas stok masuk, keluar, dan transfer</div>
    </div>
    <a href="<?= APP_URL ?>/stock" class="btn btn-outline-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali
    </a>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= APP_URL ?>/stock/history" class="filter-bar">
            <select name="type" class="form-select" style="max-width:160px;">
                <option value="">Semua Tipe</option>
                <option value="masuk"     <?= $type === 'masuk'     ? 'selected' : '' ?>>Masuk</option>
                <option value="keluar"    <?= $type === 'keluar'    ? 'selected' : '' ?>>Keluar</option>
                <option value="transfer"  <?= $type === 'transfer'  ? 'selected' : '' ?>>Transfer</option>
                <option value="penjualan" <?= $type === 'penjualan' ? 'selected' : '' ?>>Penjualan</option>
                <option value="koreksi"   <?= $type === 'koreksi'   ? 'selected' : '' ?>>Koreksi</option>
            </select>
            <select name="warehouse" class="form-select" style="max-width:200px;">
                <option value="">Semua Gudang</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $warehouseId == $w['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date_from" class="form-control" style="max-width:150px;" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="Dari">
            <input type="date" name="date_to"   class="form-control" style="max-width:150px;" value="<?= htmlspecialchars($dateTo) ?>" placeholder="Sampai">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
            <?php if ($type || $warehouseId || $dateFrom || $dateTo): ?>
                <a href="<?= APP_URL ?>/stock/history" class="btn btn-outline-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <?php if (empty($movements)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <h3>Belum ada riwayat</h3>
            <p>Belum ada data pergerakan stok.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Produk</th>
                        <th class="text-center">Tipe</th>
                        <th class="text-center">Qty</th>
                        <th>Gudang</th>
                        <th>Catatan</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m): ?>
                    <tr>
                        <td class="text-muted-custom fs-12 text-nowrap">
                            <?= Format::datetime($m['created_at']) ?>
                        </td>
                        <td>
                            <div class="fw-600"><?= htmlspecialchars($m['product_name']) ?></div>
                            <div class="text-muted-custom fs-12"><?= htmlspecialchars($m['sku']) ?></div>
                        </td>
                        <td class="text-center">
                            <?php
                            $typeBadge = match ($m['type']) {
                                'masuk'     => '<span class="badge bg-success">Masuk</span>',
                                'keluar'    => '<span class="badge bg-danger">Keluar</span>',
                                'transfer'  => '<span class="badge bg-info text-dark">Transfer</span>',
                                'penjualan' => '<span class="badge bg-primary">Penjualan</span>',
                                'koreksi'   => '<span class="badge bg-warning text-dark">Koreksi</span>',
                                default     => '<span class="badge bg-secondary">' . htmlspecialchars($m['type']) . '</span>',
                            };
                            echo $typeBadge;
                            ?>
                        </td>
                        <td class="text-center fw-700">
                            <?php if ($m['type'] === 'masuk'): ?>
                                <span class="text-success">+<?= (int) $m['quantity'] ?></span>
                            <?php elseif (in_array($m['type'], ['keluar', 'penjualan'])): ?>
                                <span class="text-danger">-<?= (int) $m['quantity'] ?></span>
                            <?php elseif ($m['type'] === 'koreksi'): ?>
                                <?php $qty = (int) $m['quantity']; ?>
                                <?php if ($qty > 0): ?>
                                    <span class="text-success">+<?= $qty ?></span>
                                <?php elseif ($qty < 0): ?>
                                    <span class="text-danger"><?= $qty ?></span>
                                <?php else: ?>
                                    <span>0</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= (int) $m['quantity'] ?>
                            <?php endif; ?>
                        </td>
                        <td class="fs-12">
                            <?php if ($m['type'] === 'transfer'): ?>
                                <?= htmlspecialchars($m['from_warehouse_name'] ?? '—') ?>
                                → <?= htmlspecialchars($m['to_warehouse_name'] ?? '—') ?>
                            <?php else: ?>
                                <?= htmlspecialchars($m['warehouse_name'] ?? '—') ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted-custom fs-12">
                            <?= $m['notes'] ? htmlspecialchars(Format::truncate($m['notes'], 40)) : '—' ?>
                        </td>
                        <td class="fs-12"><?= htmlspecialchars($m['created_by_name'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="data-count px-3 py-2 border-top">
            Menampilkan <?= count($movements) ?> riwayat
        </div>
    <?php endif; ?>
</div>
