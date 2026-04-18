<?php
/**
 * app/views/stock/index.php
 * Daftar stok per gudang dengan filter dan indikator stok rendah.
 * Variabel: $warehouses, $warehouseId, $stocks, $lowStock, $search, $categories, $category
 */
?>

<div class="page-header">
    <div>
        <h1>Manajemen Stok</h1>
        <div class="page-header-subtitle">Pantau stok produk di setiap gudang</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/stock/transfer" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            Transfer Stok
        </a>
        <a href="<?= APP_URL ?>/stock/history" class="btn btn-outline-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Riwayat
        </a>
    </div>
</div>

<!-- Notifikasi Stok Rendah -->
<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <strong><?= count($lowStock) ?> produk</strong> dengan stok di bawah minimum!
</div>
<?php endif; ?>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= APP_URL ?>/stock" class="filter-bar">
            <select name="warehouse" class="form-select" style="max-width:220px;" onchange="this.form.submit()">
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $warehouseId == $w['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select" style="max-width:150px;" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="aman" <?= (isset($status) && $status === 'aman') ? 'selected' : '' ?>>Aman</option>
                <option value="menipis" <?= (isset($status) && $status === 'menipis') ? 'selected' : '' ?>>Menipis</option>
                <option value="habis" <?= (isset($status) && $status === 'habis') ? 'selected' : '' ?>>Habis</option>
            </select>
            <select name="category" class="form-select" style="max-width:180px;" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['category']) ?>" <?= (isset($category) && $category === $cat['category']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="input-group" style="max-width:250px;">
                <span class="input-group-text bg-white border-end-0">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
            <?php if ($search || $status || $category): ?>
                <a href="<?= APP_URL ?>/stock?warehouse=<?= $warehouseId ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <?php if (empty($stocks)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
            <h3>Belum ada stok</h3>
            <p>Belum ada data stok di gudang ini.</p>
            <a href="<?= APP_URL ?>/stock/add" class="btn btn-primary btn-sm">Tambah Stok</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Min.</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stocks as $s): ?>
                    <?php $isLow = $s['quantity'] < $s['stok_minimum']; ?>
                    <tr>
                        <td class="fw-600">
                            <a href="<?= APP_URL ?>/stock/add?product_id=<?= $s['product_id'] ?>&warehouse_id=<?= $warehouseId ?>" class="text-decoration-none text-primary">
                                <?= htmlspecialchars($s['product_name']) ?>
                            </a>
                        </td>
                        <td><code class="fs-12"><?= htmlspecialchars($s['sku']) ?></code></td>
                        <td>
                            <?php if (!empty($s['category'])): ?>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['category']) ?></span>
                            <?php else: ?>
                                <span class="text-muted-custom fs-12">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-700 <?= $isLow ? 'text-danger' : '' ?>">
                            <?= (int) $s['quantity'] ?>
                        </td>
                        <td class="text-center text-muted-custom"><?= (int) $s['stok_minimum'] ?></td>
                        <td class="text-center">
                            <?php if ($s['quantity'] <= 0): ?>
                                <span class="badge bg-danger">Habis</span>
                            <?php elseif ($isLow): ?>
                                <span class="badge bg-warning text-dark">Menipis</span>
                            <?php else: ?>
                                <span class="badge bg-success">Aman</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="data-count px-3 py-2 border-top">
            Menampilkan <?= count($stocks) ?> produk
        </div>
    <?php endif; ?>
</div>
