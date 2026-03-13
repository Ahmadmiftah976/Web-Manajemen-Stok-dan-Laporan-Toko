<?php
/**
 * app/views/stock/transfer.php
 * Form transfer stok antar gudang.
 * Variabel: $warehouses, $products
 */
?>

<div class="page-header">
    <div>
        <h1>Transfer Stok</h1>
        <div class="page-header-subtitle">Pindahkan stok antar gudang</div>
    </div>
    <a href="<?= APP_URL ?>/stock" class="btn btn-outline-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali
    </a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/stock/transfer" novalidate>
            <?= Csrf::field() ?>

            <div class="row g-3">
                <!-- Produk -->
                <div class="col-12">
                    <label class="form-label" for="product_id">Produk <span class="text-danger">*</span></label>
                    <select id="product_id" name="product_id" class="form-select" required>
                        <option value="">— Pilih Produk —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Gudang Asal -->
                <div class="col-md-6">
                    <label class="form-label" for="from_warehouse_id">Dari Gudang <span class="text-danger">*</span></label>
                    <select id="from_warehouse_id" name="from_warehouse_id" class="form-select" required>
                        <option value="">— Gudang Asal —</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>">
                                <?= htmlspecialchars($w['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Gudang Tujuan -->
                <div class="col-md-6">
                    <label class="form-label" for="to_warehouse_id">Ke Gudang <span class="text-danger">*</span></label>
                    <select id="to_warehouse_id" name="to_warehouse_id" class="form-select" required>
                        <option value="">— Gudang Tujuan —</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>">
                                <?= htmlspecialchars($w['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Jumlah -->
                <div class="col-md-6">
                    <label class="form-label" for="quantity">Jumlah <span class="text-danger">*</span></label>
                    <input type="number" id="quantity" name="quantity" class="form-control" placeholder="0" min="1" required>
                </div>

                <!-- Catatan -->
                <div class="col-12">
                    <label class="form-label" for="notes">Catatan</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Catatan opsional (contoh: pindah stok ke toko pasar)"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
                    Transfer Stok
                </button>
                <a href="<?= APP_URL ?>/stock" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
